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

/**
 * Turns a document_builder document (OpenAPI 3.1 / JSON Schema 2020-12)
 * into OpenAPI 3.0.3.
 *
 * One generator, two outputs, two rewrites in the same recursive pass:
 * - a nullable field's "type": [X, "null"] (valid in 3.1, not in 3.0)
 *   becomes 3.0's own "type": X, "nullable": true;
 * - a single-value "const" (2020-12 JSON Schema, used for wsfunction's
 *   fixed-per-operation value) becomes a one-element "enum" -- the closest
 *   3.0's older JSON Schema dialect has, since it has no "const" keyword.
 *
 * Which nodes are schemas matters here, and cannot be assumed from their
 * shape. Inside "properties" the keys are field names chosen by whoever
 * wrote the function, and plenty of Moodle functions return a field called
 * "type" -- treating that map as a schema would read a property's own
 * definition as a type declaration and overwrite it. So "properties" is
 * descended into one child at a time, and only those children are read as
 * schemas.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class openapi_downgrader {
    /**
     * Downgrade a document_builder document to OpenAPI 3.0.3.
     *
     * @param array $document A document as returned by document_builder::build().
     * @return array
     */
    public static function to_3_0(array $document): array {
        $document['openapi'] = '3.0.3';
        $document['paths'] = self::downgrade_tree($document['paths']);
        $document['components'] = self::downgrade_tree($document['components']);

        return $document;
    }

    /**
     * Rewrite this node's [type, "null"] pair and/or "const" if present,
     * then recurse.
     *
     * @param mixed $node
     * @return mixed
     */
    private static function downgrade_tree(mixed $node): mixed {
        if (!is_array($node)) {
            return $node;
        }

        // 3.0 has no 'null' type: a null-only schema (a function with no
        // return value answers the literal null) becomes the one value it
        // can hold. 3.0 also forbids nullable without a type, hence the
        // placeholder object type, which enum [null] narrows to null alone.
        if (($node['type'] ?? null) === 'null') {
            $node['type'] = 'object';
            $node['nullable'] = true;
            $node['enum'] = [null];
        }
        if (isset($node['type']) && is_array($node['type'])) {
            $realtypes = array_values(array_diff($node['type'], ['null']));
            $node['type'] = $realtypes[0];
            $node['nullable'] = true;
        }

        if (array_key_exists('const', $node)) {
            $node['enum'] = [$node['const']];
            unset($node['const']);
        }

        foreach ($node as $key => $value) {
            if ($key === 'properties' && is_array($value)) {
                $node[$key] = self::downgrade_properties($value);
                continue;
            }

            $node[$key] = self::downgrade_tree($value);
        }

        return $node;
    }

    /**
     * Recurse into a "properties" map without reading the map itself as a
     * schema -- see the class docblock.
     *
     * @param array $properties Field name => schema.
     * @return array
     */
    private static function downgrade_properties(array $properties): array {
        foreach ($properties as $name => $schema) {
            $properties[$name] = self::downgrade_tree($schema);
        }

        return $properties;
    }
}
