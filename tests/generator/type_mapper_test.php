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

use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Tests for type_mapper.
 *
 * The nested-structure fixture mirrors core_course_get_courses's real
 * execute_parameters() shape, as confirmed against live Moodle 4.5 and 5.2
 * installs -- hand-built here rather than fetched from the live core
 * function, because that comparison also showed the real shape already
 * drifting once between the two supported Moodle versions (5.2 adds a
 * field 4.5 does not have), which would make an exact comparison against
 * the live function version-fragile.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\type_mapper
 */
final class type_mapper_test extends \basic_testcase {
    /**
     * A null return description maps to null.
     */
    public function test_returns_null_for_a_function_with_no_return_value(): void {
        $this->assertNull(type_mapper::map(null));
    }

    /**
     * Each PARAM_* constant maps to the right JSON Schema type.
     */
    public function test_maps_scalar_param_types_to_json_schema(): void {
        $cases = [
            [PARAM_INT, ['type' => 'integer']],
            [PARAM_FLOAT, ['type' => 'number']],
            [PARAM_BOOL, ['type' => 'boolean']],
            [PARAM_PERMISSION, ['type' => 'integer']],
            // Relative URLs and '' are valid PARAM_URL values.
            [PARAM_URL, ['type' => 'string', 'format' => 'uri-reference']],
            // An invalid address is cleaned to '', which format "email" rejects.
            [PARAM_EMAIL, ['type' => 'string']],
            // Request bodies are form-encoded: a raw value arrives as a string.
            [PARAM_RAW, ['type' => 'string']],
            // Not in type_mapper's explicit map: must fall back to "string".
            [PARAM_ALPHANUM, ['type' => 'string']],
            [PARAM_COMPONENT, ['type' => 'string']],
        ];

        foreach ($cases as [$paramtype, $expected]) {
            $value = new external_value($paramtype, '', VALUE_REQUIRED, null, NULL_NOT_ALLOWED);
            $this->assertEquals($expected, type_mapper::map($value));
        }
    }

    /**
     * A nullable value's type becomes a two-element [type, "null"] array.
     */
    public function test_allownull_turns_type_into_a_two_element_array(): void {
        $value = new external_value(PARAM_INT, '', VALUE_REQUIRED, null, NULL_ALLOWED);
        $schema = type_mapper::map($value);
        $this->assertSame(['integer', 'null'], $schema['type']);
    }

    /**
     * A multi-line desc, indentation included, collapses to one line.
     */
    public function test_normalises_multiline_descriptions(): void {
        // Real desc string from core_course_get_contents on both 4.5 and 5.2,
        // indentation and all.
        $desc = "Type of completion tracking:\n"
            . "                                        0 means none, 1 manual, 2 automatic.";
        $value = new external_value(PARAM_INT, $desc, VALUE_REQUIRED, null, NULL_NOT_ALLOWED);
        $schema = type_mapper::map($value);
        $this->assertSame('Type of completion tracking: 0 means none, 1 manual, 2 automatic.', $schema['description']);
    }

    /**
     * VALUE_REQUIRED/VALUE_DEFAULT/VALUE_OPTIONAL each map differently.
     */
    public function test_required_tristate_matches_moodles_value_constants(): void {
        $structure = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
            'tags' => new external_multiple_structure(
                new external_value(PARAM_RAW, '', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
                'extra options',
                VALUE_DEFAULT,
                [],
                NULL_NOT_ALLOWED
            ),
            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL, null, NULL_ALLOWED),
        ]);

        $schema = type_mapper::map($structure);

        $this->assertSame(['id'], $schema['required']);
        $this->assertSame([], $schema['properties']['tags']['default']);
        $this->assertArrayNotHasKey('default', $schema['properties']['idnumber']);
        $this->assertSame(['string', 'null'], $schema['properties']['idnumber']['type']);
    }

    /**
     * A PARAM_RAW return value is handed back uncleaned, so whatever
     * scalar PHP had comes out as is.
     */
    public function test_raw_in_a_response_is_any_scalar(): void {
        $value = new external_value(PARAM_RAW, '', VALUE_REQUIRED, null, NULL_NOT_ALLOWED);
        $this->assertSame(['string', 'number', 'boolean'], type_mapper::map($value, true)['type']);

        $value = new external_value(PARAM_RAW, '', VALUE_REQUIRED, null, NULL_ALLOWED);
        $this->assertSame(['string', 'number', 'boolean', 'null'], type_mapper::map($value, true)['type']);
    }

    /**
     * A default is documented as the value Moodle uses, not as written
     * in PHP, and a default Moodle would reject is not documented.
     */
    public function test_parameter_defaults_are_cleaned_like_moodle_does(): void {
        $structure = new external_single_structure([
            'limit' => new external_value(PARAM_INT, '', VALUE_DEFAULT, '0'),
            'visible' => new external_value(PARAM_BOOL, '', VALUE_DEFAULT, 1),
            'courseid' => new external_value(PARAM_INT, '', VALUE_DEFAULT, null, NULL_NOT_ALLOWED),
        ]);

        $schema = type_mapper::map($structure);

        $this->assertSame(0, $schema['properties']['limit']['default']);
        $this->assertTrue($schema['properties']['visible']['default']);
        $this->assertArrayNotHasKey('default', $schema['properties']['courseid']);
    }

    /**
     * clean_returnvalue() fills in a missing VALUE_DEFAULT scalar, so it
     * is always in the response; a missing VALUE_DEFAULT structure is not.
     */
    public function test_response_default_scalars_are_required(): void {
        $structure = new external_single_structure([
            'visible' => new external_value(PARAM_BOOL, '', VALUE_DEFAULT, true),
            1 => new external_value(PARAM_INT, 'Numeric key, as in core_message', VALUE_DEFAULT, 0),
            'note' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
            'files' => new external_multiple_structure(
                new external_value(PARAM_RAW, ''),
                '',
                VALUE_DEFAULT,
                []
            ),
        ]);

        $schema = type_mapper::map($structure, true);

        $this->assertSame(['visible', '1'], $schema['required']);
        $this->assertArrayNotHasKey('default', $schema['properties']['visible']);
        $this->assertArrayNotHasKey('default', $schema['properties']['files']);
    }

    /**
     * A warning list exactly as core builds it points at the shared item;
     * one with its own descriptions, or in a request, keeps its schema.
     */
    public function test_only_canonical_warning_items_become_a_ref(): void {
        $ref = ['$ref' => '#/components/schemas/Warning'];

        $this->assertSame($ref, type_mapper::map(new \core_external\external_warnings(), true)['items']);
        $this->assertNotSame($ref, type_mapper::map(new \core_external\external_warnings('course'), true)['items']);
        $this->assertNotSame($ref, type_mapper::map(new \core_external\external_warnings())['items']);
    }

    /**
     * Blank lines in a description are paragraph breaks the author meant.
     */
    public function test_keeps_paragraph_breaks_in_descriptions(): void {
        $desc = "First paragraph,\n            still first.\n\n            Second paragraph.";
        $value = new external_value(PARAM_INT, $desc);

        $this->assertSame("First paragraph, still first.\n\nSecond paragraph.", type_mapper::map($value)['description']);
    }

    /**
     * A structure with no declared keys still maps "properties" as a JSON
     * object, not an empty array -- json_encode() renders a plain empty PHP
     * array as "[]", which is invalid JSON Schema ("properties" must be an
     * object). Confirmed as a real bug generating the catalog against a
     * live Moodle install: some external_single_structure descriptions in
     * core genuinely declare zero keys.
     */
    public function test_empty_structure_maps_properties_as_an_object_not_an_array(): void {
        $schema = type_mapper::map(new external_single_structure([]));

        $this->assertInstanceOf(\stdClass::class, $schema['properties']);
        $this->assertSame('{}', json_encode($schema['properties']));
    }

    /**
     * A nested object/array/scalar tree maps exactly.
     */
    public function test_maps_a_nested_structure_exactly(): void {
        $params = new external_function_parameters([
            'options' => new external_single_structure([
                'ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED, null, NULL_ALLOWED),
                    'List of course id.',
                    VALUE_OPTIONAL,
                    null,
                    NULL_NOT_ALLOWED
                ),
            ], 'options - operator OR is used', VALUE_DEFAULT, [], NULL_NOT_ALLOWED),
        ]);

        $schema = type_mapper::map($params);

        $this->assertEquals([
            'type' => 'object',
            'properties' => [
                'options' => [
                    'description' => 'options - operator OR is used',
                    'type' => 'object',
                    'properties' => [
                        'ids' => [
                            'description' => 'List of course id.',
                            'type' => 'array',
                            'items' => [
                                'description' => 'Course id',
                                'type' => ['integer', 'null'],
                            ],
                        ],
                    ],
                    'default' => [],
                ],
            ],
        ], $schema);
    }

    /**
     * An external_description subclass that is none of the three known
     * shapes is rejected rather than silently mismapped.
     */
    public function test_rejects_an_unsupported_external_description_subclass(): void {
        $this->expectException(\coding_exception::class);
        type_mapper::map(new class ('', VALUE_REQUIRED, null, false) extends external_description {
        });
    }
}
