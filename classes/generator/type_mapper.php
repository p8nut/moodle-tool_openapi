<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_openapi\generator;

use core_external\external_api;
use core_external\external_description;
use core_external\external_files;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

/**
 * Converts a Moodle external API parameter/return description into JSON Schema.
 *
 * Stateless by design: every method is static and nothing here touches the
 * database or a cache. It only ever runs during a full catalog
 * (re)generation, the same rare event (plugin install/upgrade/uninstall,
 * or an administrator forcing it) that already purges tool_openapi's own
 * cache -- a live site's webservice traffic never reaches this class.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class type_mapper {
    /**
     * Moodle PARAM_* constants that need a JSON Schema type other than the
     * "string" fallback in map(). Most of Moodle's PARAM_* validators
     * constrain the *content* of a string (alphanumeric, a safe path, a
     * component name, ...), not its JSON representation, so leaving them
     * out here and falling back to "string" is correct, not incomplete.
     *
     * URLs are uri-references, not uris: PARAM_URL accepts relative URLs,
     * PARAM_LOCALURL root-relative ones, and both clean an invalid value to
     * ''. PARAM_EMAIL has no format for the same reason: an invalid address
     * is cleaned to '', which format "email" rejects.
     *
     * @var array<string, array<string, string>>
     */
    private const SCALAR_TYPES = [
        PARAM_INT => ['type' => 'integer'],
        PARAM_FLOAT => ['type' => 'number'],
        PARAM_LOCALISEDFLOAT => ['type' => 'number'],
        PARAM_BOOL => ['type' => 'boolean'],
        PARAM_PERMISSION => ['type' => 'integer'],
        PARAM_URL => ['type' => 'string', 'format' => 'uri-reference'],
        PARAM_LOCALURL => ['type' => 'string', 'format' => 'uri-reference'],
    ];

    /**
     * PARAM_RAW in a response: clean_returnvalue() hands the value back
     * uncleaned, so a PHP int, float or bool comes out as a JSON number or
     * boolean. A request body is form-encoded, where everything is a string.
     */
    private const RAW_RESPONSE_TYPE = ['type' => ['string', 'number', 'boolean']];

    /**
     * Convert one node of a Moodle parameter/return description tree into
     * JSON Schema, recursing into its children.
     *
     * @param external_description|null $description Null for a function with no return value.
     * @param bool $response True when $description is a function's returns, false for its parameters.
     * @return array|null The JSON Schema fragment, or null when $description is null.
     * @throws \coding_exception If $description is a subclass this method does not know how to map.
     */
    public static function map(?external_description $description, bool $response = false): ?array {
        if ($description === null) {
            return null;
        }

        $schema = [];
        if ($description->desc !== '') {
            $schema['description'] = self::normalise_description($description->desc);
        }

        if ($description instanceof external_multiple_structure) {
            $schema['type'] = 'array';
            $schema['items'] = self::ref_if_shared(self::map($description->content, $response));
        } else if ($description instanceof external_single_structure) {
            $schema += self::map_object($description, $response);
        } else if ($response && $description instanceof external_value && $description->type === PARAM_RAW) {
            $schema += self::RAW_RESPONSE_TYPE;
        } else if ($description instanceof external_value) {
            $schema += self::SCALAR_TYPES[$description->type] ?? ['type' => 'string'];
        } else {
            // Every real external_description subclass Moodle ships (including
            // external_format_value and external_warnings) is one of the three
            // above; a plugin cannot make validate_parameters()/
            // clean_returnvalue() accept a fourth kind, so reaching this means
            // something Moodle itself would already have rejected.
            throw new \coding_exception('Unsupported external_description subclass: ' . get_class($description));
        }

        if ($description->allownull) {
            $schema['type'] = array_merge((array) $schema['type'], ['null']);
        }

        return $schema;
    }

    /**
     * Item schemas that hundreds of functions repeat word for word: core's
     * warning and file structures, as they come out of a response. Shared
     * through components so a client generator makes one type of each.
     *
     * @return array<string, array> Component name => schema.
     */
    public static function shared_schemas(): array {
        return [
            'Warning' => self::map((new external_warnings())->content, true),
            'File' => self::map((new external_files())->content, true),
        ];
    }

    /**
     * Swap an item schema for a $ref when it is exactly a shared one. Only
     * an exact match: a function that customised a warning's descriptions
     * keeps its own schema rather than lose them.
     *
     * @param array $schema
     * @return array
     */
    private static function ref_if_shared(array $schema): array {
        static $shared = null;
        $shared ??= self::shared_schemas();

        foreach ($shared as $name => $candidate) {
            if ($schema === $candidate) {
                return ['$ref' => '#/components/schemas/' . $name];
            }
        }

        return $schema;
    }

    /**
     * Map a single_structure's own keys into the "type", "properties" and
     * "required" of a JSON Schema object.
     *
     * Split out of map() so that method's own cyclomatic complexity stays
     * under the project's threshold -- this is a real, separately-testable
     * concern (turning a structure's keys into an object schema), not a
     * split done only to satisfy the linter.
     *
     * In a response, a missing VALUE_DEFAULT scalar is filled in by
     * clean_returnvalue(), so it is always there: required, not defaulted.
     * A missing VALUE_DEFAULT structure is left out, so it stays optional.
     *
     * @param external_single_structure $description
     * @param bool $response See map().
     * @return array
     */
    private static function map_object(external_single_structure $description, bool $response): array {
        $properties = [];
        $required = [];
        foreach ($description->keys as $key => $child) {
            $properties[$key] = self::map($child, $response);
            $isdefault = $child->required === VALUE_DEFAULT;
            if ($child->required === VALUE_REQUIRED || ($response && $isdefault && $child instanceof external_value)) {
                // Cast: a numeric key (core_message's conversation types) is
                // an int in PHP, but JSON Schema's "required" lists strings.
                $required[] = (string) $key;
            } else if ($isdefault && !$response) {
                self::add_default($properties[$key], $child);
            }
        }

        // PHP's json_encode() renders an empty array as "[]", not "{}" --
        // invalid JSON Schema, since "properties" must be an object. Only
        // the empty case needs the (object) cast: a non-empty associative
        // array already encodes correctly, and every existing test
        // compares against one.
        $schema = ['type' => 'object', 'properties' => $properties === [] ? new \stdClass() : $properties];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Document a VALUE_DEFAULT parameter's default as the value Moodle
     * actually uses: a scalar's default is declared as written in PHP ('0'
     * for an int, 0 for a bool...), and validate_parameters() cleans it
     * like a sent value. clean_returnvalue() does that cleaning and turns
     * PARAM_BOOL into a real boolean. A default Moodle itself would reject
     * (null on a non-nullable value) is left undocumented.
     *
     * @param array $schema The property schema to add the default to.
     * @param external_description $child
     */
    private static function add_default(array &$schema, external_description $child): void {
        if (!$child instanceof external_value) {
            $schema['default'] = $child->default;
            return;
        }

        try {
            $schema['default'] = external_api::clean_returnvalue($child, $child->default);
        } catch (\Exception) {
            // Moodle would reject this default itself: nothing to document.
            unset($schema['default']);
        }
    }

    /**
     * Collapse the literal whitespace Moodle's own source code leaves in
     * multi-line external_value descriptions, keeping blank lines: those
     * separate paragraphs or list items the author meant, and a viewer
     * renders them as such.
     *
     * @param string $desc
     * @return string
     */
    private static function normalise_description(string $desc): string {
        $paragraphs = preg_split('/\n\s*\n/', trim($desc));

        return implode("\n\n", array_map(static fn (string $p): string => trim(preg_replace('/\s+/', ' ', $p)), $paragraphs));
    }
}
