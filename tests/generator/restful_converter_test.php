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
 * Tests for restful_converter.
 *
 * @package    tool_openapi
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\restful_converter
 */
final class restful_converter_test extends \advanced_testcase {
    /**
     * The whole document moves to the restful protocol: per-function URLs,
     * the token in the Authorization header, no moodlewsrestformat, JSON
     * bodies and errors under their own status.
     */
    public function test_converts_a_document_to_the_restful_protocol(): void {
        global $CFG;

        $this->resetAfterTest();

        $rest = document_builder::keep_functions(document_builder::build(), ['core_course_get_courses']);
        $doc = restful_converter::convert($rest);
        $operation = $doc['paths']['/core_course_get_courses']['post'];

        $this->assertSame($CFG->wwwroot . '/webservice/restful/server.php', $doc['servers'][0]['url']);
        $this->assertSame('header', $doc['components']['securitySchemes']['wstoken']['in']);
        $this->assertSame('Authorization', $doc['components']['securitySchemes']['wstoken']['name']);
        $this->assertArrayNotHasKey('parameters', $doc['components']);
        $this->assertArrayNotHasKey('parameters', $operation);

        $this->assertSame(['application/json'], array_keys($operation['requestBody']['content']));
        $form = $rest['paths']['/core_course_get_courses']['post']['requestBody']['content'];
        $this->assertSame(
            $form['application/x-www-form-urlencoded']['schema'],
            $operation['requestBody']['content']['application/json']['schema']
        );

        $this->assertSame(['200', '400', '401'], array_map('strval', array_keys($operation['responses'])));
        $this->assertSame('array', $operation['responses']['200']['content']['application/json']['schema']['type']);
        $this->assertSame(
            '#/components/schemas/WebserviceError',
            $operation['responses']['400']['content']['application/json']['schema']['$ref']
        );
    }
}
