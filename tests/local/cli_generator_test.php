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

namespace tool_openapi\local;

/**
 * Tests for cli_generator.
 *
 * @package    tool_openapi
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\local\cli_generator
 */
final class cli_generator_test extends \advanced_testcase {
    /**
     * Insert a bare external service and assign it a set of functions.
     *
     * @param string $shortname
     * @param string[] $functionnames
     */
    private function create_service(string $shortname, array $functionnames): void {
        global $DB;

        $serviceid = $DB->insert_record('external_services', (object) [
            'name' => $shortname,
            'shortname' => $shortname,
            'enabled' => 1,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'timecreated' => time(),
        ]);

        foreach ($functionnames as $functionname) {
            $DB->insert_record('external_services_functions', (object) [
                'externalserviceid' => $serviceid,
                'functionname' => $functionname,
            ]);
        }
    }

    /**
     * Without a service, the whole catalog, with the server relative to the site.
     */
    public function test_generates_the_whole_catalog_over_rest(): void {
        $this->resetAfterTest();

        $doc = cli_generator::generate(null);

        $this->assertArrayHasKey('/core_webservice_get_site_info', $doc['paths']);
        $this->assertGreaterThan(1, count($doc['paths']));
        $this->assertSame([['url' => '/']], $doc['servers']);
        $this->assertArrayHasKey('moodlewsrestformat', $doc['components']['parameters']);
    }

    /**
     * --service keeps only that service's functions.
     */
    public function test_respects_the_service(): void {
        $this->resetAfterTest();

        $this->create_service('tool_openapi_test_service', ['core_webservice_get_site_info']);

        $doc = cli_generator::generate('tool_openapi_test_service');

        $this->assertSame(['/core_webservice_get_site_info'], array_keys($doc['paths']));
    }

    /**
     * --protocol=restful converts the document, with the endpoint relative to the site.
     */
    public function test_converts_to_restful(): void {
        $this->resetAfterTest();

        $this->create_service('tool_openapi_test_service', ['core_webservice_get_site_info']);

        $doc = cli_generator::generate('tool_openapi_test_service', 'restful');

        $this->assertSame([['url' => '/webservice/restful/server.php']], $doc['servers']);
        $this->assertSame('Authorization', $doc['components']['securitySchemes']['wstoken']['name']);
        $this->assertArrayNotHasKey('parameters', $doc['components']);
        $operation = $doc['paths']['/core_webservice_get_site_info']['post'];
        $this->assertSame(['application/json'], array_keys($operation['requestBody']['content']));
    }

    /**
     * An unknown protocol is rejected before anything is built.
     */
    public function test_rejects_an_unknown_protocol(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidprotocol', 'tool_openapi', 'soap'));
        cli_generator::generate(null, 'soap');
    }

    /**
     * An unknown service is rejected, not answered with the whole catalog.
     */
    public function test_rejects_an_unknown_service(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('invalidservice', 'tool_openapi', 'does-not-exist'));
        cli_generator::generate('does-not-exist');
    }

    /**
     * The encoding matches openapi.php's, with a trailing newline.
     */
    public function test_encode_matches_openapi_php(): void {
        $doc = ['info' => ['title' => 'Café/é']];

        $this->assertSame(
            json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
            cli_generator::encode($doc)
        );
        $this->assertStringContainsString('Café/é', cli_generator::encode($doc));
    }
}
