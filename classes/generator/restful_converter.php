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
 * Rewrites a document_builder document for Catalyst's webservice_restful
 * protocol instead of core's REST protocol.
 *
 * webservice_restful serves each function at its own URL
 * (webservice/restful/server.php/<function>), so the document's synthetic
 * paths become the real ones once the server URL points there. It reads the
 * token from the Authorization header (a "Bearer " prefix is stripped), a
 * JSON body, and the response format from the Accept header, and answers
 * errors with 4xx instead of 200. Read from its locallib.php and checked
 * against a Moodle 5.3 site, not taken from its README alone.
 *
 * One gap, left out of the schemas on purpose: a return value Moodle fails
 * to validate still comes back as HTTP 200 with an error body. That is a
 * bug in the function, not an answer a client can act on. Nor is the empty
 * 403 sent when web services or the protocol are off: an operation-level
 * response without a body turns a generated client's error type into
 * unknown, for a site misconfiguration no call can fix.
 *
 * @package    tool_openapi
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restful_converter {
    /** The protocol's entry point; each function is a path below it. */
    private const ENDPOINT = '/webservice/restful/server.php';

    /**
     * Convert a document_builder document (before any 3.0 downgrade).
     *
     * @param array $document
     * @return array
     */
    public static function convert(array $document): array {
        global $CFG;

        $document['servers'] = [['url' => $CFG->wwwroot . self::ENDPOINT]];
        $document['security'] = [['wstoken' => []]];
        $document['components']['securitySchemes'] = [
            'wstoken' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'Authorization',
                'description' => 'A Moodle webservice token, bare or as "Bearer <token>", whose service '
                    . 'includes the function and whose user has webservice/restful:use. Send '
                    . '"Accept: application/json" too: without it the answer is XML.',
            ],
        ];
        unset($document['components']['parameters']['moodlewsrestformat']);
        if (empty($document['components']['parameters'])) {
            unset($document['components']['parameters']);
        }

        foreach ($document['paths'] as $path => $item) {
            $document['paths'][$path]['post'] = self::convert_operation($item['post'], ltrim($path, '/'));
        }

        return $document;
    }

    /**
     * One operation: JSON body, real endpoint, errors under their own status.
     *
     * @param array $operation
     * @param string $name The function name.
     * @return array
     */
    private static function convert_operation(array $operation, string $name): array {
        $operation['description'] = 'Request target: POST ' . self::ENDPOINT . '/' . $name
            . ' (webservice_restful protocol).';
        $operation['x-moodle-real-endpoint'] = self::ENDPOINT . '/' . $name;
        unset($operation['parameters']);

        $schema = $operation['requestBody']['content']['application/x-www-form-urlencoded']['schema'];
        $operation['requestBody'] = [
            'required' => true,
            'description' => 'JSON, nested as the schema says. Keys the function does not declare are rejected.',
            'content' => ['application/json' => ['schema' => $schema]],
        ];

        $success = $operation['responses']['200']['content']['application/json']['schema']['anyOf'][0];
        $error = ['application/json' => ['schema' => ['$ref' => '#/components/schemas/WebserviceError']]];
        $operation['responses'] = [
            '200' => [
                'description' => 'The function\'s result.',
                'content' => ['application/json' => ['schema' => $success]],
            ],
            '400' => [
                'description' => 'Any error raised by Moodle: invalid token or parameter, missing '
                    . 'capability, function not in the token\'s service.',
                'content' => $error,
            ],
            '401' => ['description' => 'No Authorization header.', 'content' => $error],
        ];

        return $operation;
    }
}
