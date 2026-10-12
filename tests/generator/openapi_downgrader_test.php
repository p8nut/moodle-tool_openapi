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
 * Tests for openapi_downgrader.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\openapi_downgrader
 */
final class openapi_downgrader_test extends \basic_testcase {
    /**
     * Wrap one request body schema in a minimal, otherwise-empty document.
     *
     * @param array $schema
     * @return array
     */
    private function document_with_schema(array $schema): array {
        return [
            'openapi' => '3.1.0',
            'info' => [],
            'paths' => ['/x' => ['post' => ['requestBody' => ['content' => ['application/json' => [
                'schema' => $schema,
            ]]]]]],
            'components' => ['schemas' => []],
        ];
    }

    /**
     * Pull the request body schema back out of a downgraded document.
     *
     * @param array $document
     * @return array
     */
    private function schema_from(array $document): array {
        return $document['paths']['/x']['post']['requestBody']['content']['application/json']['schema'];
    }

    /**
     * The openapi field itself is rewritten to 3.0.3.
     */
    public function test_rewrites_the_openapi_version(): void {
        $document = ['openapi' => '3.1.0', 'info' => [], 'paths' => [], 'components' => []];
        $this->assertSame('3.0.3', openapi_downgrader::to_3_0($document)['openapi']);
    }

    /**
     * A nullable scalar's [type, "null"] array becomes nullable: true.
     */
    public function test_nullable_type_array_becomes_nullable_true(): void {
        $document = $this->document_with_schema(['type' => ['integer', 'null']]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'integer', 'nullable' => true], $schema);
    }

    /**
     * 3.0 has no 'null' type: a schema that is only null (a function with
     * no return value) becomes the one value it can hold. 3.0 also forbids
     * nullable without a type, hence the placeholder object type.
     */
    public function test_null_only_type_becomes_a_nullable_null_enum(): void {
        $document = $this->document_with_schema(['type' => 'null']);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'object', 'nullable' => true, 'enum' => [null]], $schema);
    }

    /**
     * A non-nullable scalar type is left untouched.
     */
    public function test_non_nullable_types_are_left_alone(): void {
        $document = $this->document_with_schema(['type' => 'string']);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'string'], $schema);
    }

    /**
     * A single-value "const" (2020-12 JSON Schema, used for wsfunction's
     * fixed value) becomes a one-element "enum" -- 3.0's dialect has no
     * "const" keyword.
     */
    public function test_const_becomes_a_one_element_enum(): void {
        $document = $this->document_with_schema(['type' => 'string', 'const' => 'core_course_get_courses']);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(['type' => 'string', 'enum' => ['core_course_get_courses']], $schema);
        $this->assertArrayNotHasKey('const', $schema);
    }

    /**
     * A nullable field nested inside an object inside an array is still found.
     */
    public function test_recurses_into_nested_object_and_array_schemas(): void {
        $document = $this->document_with_schema([
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'idnumber' => ['type' => ['string', 'null']],
                ],
            ],
        ]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame(
            ['type' => 'string', 'nullable' => true],
            $schema['items']['properties']['idnumber']
        );
    }

    /**
     * A field actually called "type" is a field, not a type declaration.
     *
     * Plenty of Moodle functions return one (calendar events, grade items,
     * enrolment instances), and reading its schema as this object's own
     * type both destroys the field and adds a nullable flag to the map of
     * properties.
     */
    public function test_a_property_named_type_is_not_read_as_a_type(): void {
        $document = $this->document_with_schema([
            'type' => 'object',
            'properties' => [
                'type' => ['type' => ['string', 'null'], 'description' => 'Event type'],
                'name' => ['type' => 'string'],
            ],
        ]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertSame('object', $schema['type']);
        $this->assertArrayNotHasKey('nullable', $schema);
        $this->assertSame(
            ['type' => 'string', 'description' => 'Event type', 'nullable' => true],
            $schema['properties']['type']
        );
        $this->assertSame(['type' => 'string'], $schema['properties']['name']);
    }

    /**
     * The same, for a field called "const": rewriting it to an enum would
     * drop the field's own schema.
     */
    public function test_a_property_named_const_is_not_rewritten(): void {
        $document = $this->document_with_schema([
            'type' => 'object',
            'properties' => [
                'const' => ['type' => 'string'],
            ],
        ]);

        $schema = $this->schema_from(openapi_downgrader::to_3_0($document));

        $this->assertArrayNotHasKey('enum', $schema);
        $this->assertSame(['type' => 'string'], $schema['properties']['const']);
    }
}
