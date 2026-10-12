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
 * Tests for document_builder.
 *
 * Runs against this test site's real, live external_functions catalog --
 * unlike type_mapper_test, there is no way around that here (this class's
 * whole job is reading that table) -- so assertions stick to structural
 * facts true on any Moodle install, never an exact document comparison,
 * for the same version-drift reason recorded in type_mapper_test.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\generator\document_builder
 */
final class document_builder_test extends \advanced_testcase {
    /**
     * The document has a valid OpenAPI 3.1 envelope tied to this site.
     */
    public function test_build_returns_a_valid_openapi_envelope(): void {
        global $CFG;

        $this->resetAfterTest();

        $doc = document_builder::build();

        $this->assertSame('3.1.0', $doc['openapi']);
        $this->assertSame($CFG->release, $doc['info']['version']);
        $this->assertSame($CFG->wwwroot, $doc['servers'][0]['url']);
        $this->assertArrayHasKey('paths', $doc);
        $this->assertArrayHasKey('components', $doc);
        $this->assertArrayHasKey('wstoken', $doc['components']['parameters']);
        $this->assertArrayHasKey('moodlewsrestformat', $doc['components']['parameters']);
    }

    /**
     * Every installed function gets at most one path -- not exactly one:
     * a real site can have a function that fails to introspect (see
     * build_path_or_skip's docblock), which build() skips rather than
     * crashing on.
     */
    public function test_every_installed_function_becomes_a_path(): void {
        global $DB;

        $this->resetAfterTest();

        $installed = $DB->count_records('external_functions');
        $doc = document_builder::build();

        $this->assertGreaterThan(0, count($doc['paths']));
        $this->assertLessThanOrEqual($installed, count($doc['paths']));
    }

    /**
     * core_webservice_get_site_info exists on every Moodle site and returns
     * data, so it exercises both requestBody and a real response schema.
     */
    public function test_documents_a_known_stable_function(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();

        $this->assertArrayHasKey('/core_webservice_get_site_info', $doc['paths']);
        $operation = $doc['paths']['/core_webservice_get_site_info']['post'];

        $this->assertSame('core_webservice_get_site_info', $operation['operationId']);
        $this->assertSame('/webservice/rest/server.php', $operation['x-moodle-real-endpoint']);
        $this->assertArrayHasKey('schema', $operation['requestBody']['content']['application/x-www-form-urlencoded']);
        $this->assertArrayNotHasKey('application/json', $operation['requestBody']['content']);
        $this->assertArrayHasKey('content', $operation['responses']['200']);
        $this->assertContains(
            ['$ref' => '#/components/schemas/WebserviceError'],
            $operation['responses']['200']['content']['application/json']['schema']['anyOf']
        );

        $wsfunctionparam = end($operation['parameters']);
        $this->assertSame('wsfunction', $wsfunctionparam['name']);
        $this->assertSame('core_webservice_get_site_info', $wsfunctionparam['schema']['const']);
        $this->assertContains(['$ref' => '#/components/parameters/wstoken'], $operation['parameters']);
    }

    /**
     * A function Moodle itself reports as deprecated is flagged as such --
     * found dynamically rather than hardcoded, since which functions are
     * deprecated changes release to release.
     */
    public function test_flags_a_deprecated_function_when_one_exists(): void {
        global $DB;

        $this->resetAfterTest();

        $deprecatedname = null;
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            try {
                $info = \core_external\external_api::external_function_info($function);
            } catch (\Throwable) {
                continue;
            }
            if ($info->deprecated ?? false) {
                $deprecatedname = $info->name;
                break;
            }
        }

        if ($deprecatedname === null) {
            $this->markTestSkipped('No deprecated external function installed on this site.');
        }

        $doc = document_builder::build();
        $this->assertTrue($doc['paths']['/' . $deprecatedname]['post']['deprecated']);
    }

    /**
     * The shared error schema matches what webservice/rest/locallib.php's
     * generate_error() emits: errorcode only when the exception has one,
     * debuginfo only with debugging on.
     */
    public function test_shared_error_schema_is_well_formed(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();
        $schema = $doc['components']['schemas']['WebserviceError'];

        $this->assertSame('object', $schema['type']);
        $this->assertSame(['exception', 'message'], $schema['required']);
        $this->assertSame(
            ['exception', 'errorcode', 'message', 'debuginfo'],
            array_keys($schema['properties'])
        );
    }

    /**
     * Moodle's REST server answers errors with HTTP 200, so every error
     * must be documented inside the 200 response, never under another
     * status code a client would wait for in vain.
     */
    public function test_errors_are_documented_inside_the_200_response(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();

        foreach ($doc['paths'] as $path => $item) {
            $responses = $item['post']['responses'];
            $this->assertArrayNotHasKey('default', $responses, $path);
            $this->assertContains(
                ['$ref' => '#/components/schemas/WebserviceError'],
                $responses['200']['content']['application/json']['schema']['anyOf'],
                $path
            );
            $this->assertArrayHasKey('403', $responses, $path);
        }
    }

    /**
     * A function with no returns_desc answers the JSON literal null on
     * success, not an empty body.
     */
    public function test_a_function_without_return_value_documents_null(): void {
        global $DB;

        $this->resetAfterTest();

        $voidname = null;
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            try {
                $info = \core_external\external_api::external_function_info($function);
            } catch (\Throwable) {
                continue;
            }
            if ($info->returns_desc === null) {
                $voidname = $info->name;
                break;
            }
        }

        if ($voidname === null) {
            $this->markTestSkipped('No external function without return value installed on this site.');
        }

        $doc = document_builder::build();
        $anyof = $doc['paths']['/' . $voidname]['post']['responses']['200']['content']['application/json']['schema']['anyOf'];
        $this->assertContains(['type' => 'null'], $anyof);
    }

    /**
     * Omitting moodlewsrestformat gets XML back (locallib.php falls back to
     * xml), and every response schema here is JSON: the parameter must be
     * required and json, with no default hinting it could be left out.
     */
    public function test_moodlewsrestformat_is_required_json(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();
        $param = $doc['components']['parameters']['moodlewsrestformat'];

        $this->assertTrue($param['required']);
        $this->assertSame(['type' => 'string', 'enum' => ['json']], $param['schema']);
    }

    /**
     * Moodle parses the form body with PHP's bracket convention
     * (courseids[0]=2), which no OpenAPI style expresses for arrays of
     * objects, so the convention is spelled out on every request body.
     */
    public function test_request_body_documents_the_php_bracket_encoding(): void {
        $this->resetAfterTest();

        $doc = document_builder::build();
        $body = $doc['paths']['/core_webservice_get_site_info']['post']['requestBody'];

        $this->assertSame('php-brackets', $body['x-moodle-encoding']);
        $this->assertStringContainsString('[0]', $body['description']);
    }
}
