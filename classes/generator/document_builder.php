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

/**
 * Assembles the full OpenAPI document from this site's external_functions catalog.
 *
 * One operation per installed webservice function, POST-only (the one HTTP
 * method Moodle's REST protocol accepts for every function, regardless of
 * that function's own read/write type) under a synthetic path named after
 * the function. Moodle itself has a single real endpoint
 * (webservice/rest/server.php) with wsfunction as a selector, which OpenAPI
 * has no native way to express as one path with many operations (a given
 * path+method pair can only ever describe one Operation Object), so this
 * documents each function as its own path instead, matching how Swagger UI
 * (and every other OpenAPI consumer) expects to browse an API. Every
 * operation carries an x-moodle-real-endpoint extension and a note in its
 * own description pointing at the one real URL, so a consumer (or a future
 * Swagger UI requestInterceptor -- see the viewer page's own plan) knows
 * this path is an organisational convenience, not the literal request target.
 *
 * The request shape documented here (requestBody as
 * application/x-www-form-urlencoded, wstoken/wsfunction/moodlewsrestformat
 * as query parameters) is not a guess: webservice/rest/lib.php's
 * parse_request() does array_merge($_GET, $_POST) and never reads a JSON
 * body -- confirmed reading that file directly, not assumed from Moodle's
 * public docs, since PHP only auto-populates $_POST for
 * x-www-form-urlencoded/multipart bodies, never for application/json.
 *
 * Only ever runs during a full catalog (re)generation -- see type_mapper's
 * docblock for why that keeps this off any live request path.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class document_builder {
    /**
     * The one real Moodle REST endpoint every synthetic path documented
     * here actually resolves to -- see the class docblock.
     */
    private const REAL_ENDPOINT = '/webservice/rest/server.php';

    /**
     * How Moodle actually reads the form body, which no OpenAPI encoding
     * style expresses for arrays of objects. webservice/rest/locallib.php
     * hands $_POST to validate_parameters() untouched, so nesting is PHP's
     * own bracket syntax, and external_api::validate_parameters() rejects
     * any key the function does not declare. PARAM_BOOL only accepts
     * 0/1 (or a real bool, which a form body cannot carry): "true" fails
     * core\param::validate_param().
     */
    private const REQUEST_ENCODING_NOTE = 'Form-encoded with PHP bracket syntax: arrays and objects nest as '
        . 'courseids[0]=2&courseids[1]=3 or users[0][username]=jdoe&users[0][email]=j@example.org, '
        . 'never repeated keys (courseids=2&courseids=3 keeps only the last value). '
        . 'Booleans are sent as 0 or 1. Keys the function does not declare are rejected.';

    /**
     * Build the OpenAPI 3.1 document for every installed webservice function.
     *
     * @return array
     */
    public static function build(): array {
        global $CFG, $DB;

        $paths = [];
        $tags = [];
        $skipped = [];
        foreach ($DB->get_records('external_functions', null, 'name ASC') as $function) {
            $item = self::build_path_or_skip($function);
            if ($item === null) {
                $skipped[] = $function->name;
                continue;
            }
            $paths['/' . $function->name] = $item;
            foreach ($item['post']['tags'] as $tag) {
                $tags[$tag] = true;
            }
        }

        $document = [
            'openapi' => '3.1.0',
            'info' => self::build_info(),
            'servers' => [['url' => $CFG->wwwroot]],
            'security' => [['wstoken' => []]],
            'tags' => self::build_tags(array_keys($tags)),
            'paths' => $paths,
            'components' => [
                'schemas' => self::build_shared_schemas(),
                'parameters' => self::build_shared_parameters(),
                'securitySchemes' => self::build_security_schemes(),
            ],
        ];
        if ($skipped !== []) {
            $document['x-moodle-skipped-functions'] = $skipped;
        }

        return $document;
    }

    /**
     * Narrow a document to some functions: their paths, the tags those
     * paths use, and the skipped functions among them. Every narrowing
     * (a ?service=, an access scope) goes through here, so nothing at the
     * document's root names a function or group outside what is shown.
     *
     * @param array $document A document as returned by build().
     * @param string[] $names Function names to keep.
     * @return array
     */
    public static function keep_functions(array $document, array $names): array {
        $keep = array_flip($names);
        $document['paths'] = array_filter(
            $document['paths'],
            static fn (string $path): bool => isset($keep[ltrim($path, '/')]),
            ARRAY_FILTER_USE_KEY
        );

        if (isset($document['tags'])) {
            $used = [];
            foreach ($document['paths'] as $item) {
                foreach ($item['post']['tags'] ?? [] as $tag) {
                    $used[$tag] = true;
                }
            }
            $document['tags'] = array_values(array_filter(
                $document['tags'],
                static fn (array $tag): bool => isset($used[$tag['name']])
            ));
        }

        if (isset($document['x-moodle-skipped-functions'])) {
            $skipped = array_values(array_filter(
                $document['x-moodle-skipped-functions'],
                static fn (string $name): bool => isset($keep[$name])
            ));
            if ($skipped === []) {
                unset($document['x-moodle-skipped-functions']);
            } else {
                $document['x-moodle-skipped-functions'] = $skipped;
            }
        }

        return $document;
    }

    /**
     * The document's info block, tied to the Moodle site's own version --
     * this documents whatever the site currently exposes, not tool_openapi's
     * own version.
     *
     * The context is passed to format_string() rather than left to default,
     * because this runs from places that have no page context to fall back
     * on: the scheduled task, and openapi.php, which serves a machine and
     * deliberately never calls require_login(). Without it, format_string()
     * reaches for $PAGE->context, finds none, and emits a debugging notice
     * that lands in the middle of the JSON being sent.
     *
     * @return array
     */
    private static function build_info(): array {
        global $CFG, $SITE;

        return [
            'title' => format_string($SITE->fullname, true, ['context' => \context_system::instance()])
                . ' web services',
            'version' => $CFG->release,
            'description' => 'Generated from this site\'s external_functions catalog by tool_openapi.',
        ];
    }

    /**
     * The document's root tag list: every tag actually used by an
     * operation, in alphabetical order.
     *
     * Declared explicitly rather than left implicit because a viewer that
     * meets a tag only on an operation has nothing to order the groups by
     * and no description to show above them, so the catalog comes out in
     * whatever order the paths happen to be in.
     *
     * @param string[] $names Tags used by at least one operation.
     * @return array
     */
    private static function build_tags(array $names): array {
        sort($names);

        $tags = [];
        foreach ($names as $name) {
            $tag = ['name' => $name];

            $description = tag_mapper::describe($name);
            if ($description !== null) {
                $tag['description'] = $description;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Introspect one function and build its path item, or null if it can't
     * be introspected.
     *
     * Confirmed for real, not hypothetical: a stock Moodle 4.5 site can
     * have an installed function (mod_quiz's add_random_questions, seen in
     * CI) whose implementation class does not autoload and has no working
     * legacy classpath fallback either, so external_function_info() throws.
     * One such function must not take down the whole catalog.
     *
     * The caller lists it under x-moodle-skipped-functions, so a missing
     * function is visible in the document rather than silently absent.
     *
     * Deliberately not reported via debugging(): the broken registration
     * belongs to the other plugin, not to tool_openapi, and Moodle's own
     * PHPUnit harness fails any test that triggers an unexpected
     * debugging() call, which would make this catalog untestable against
     * a real site that happens to have one such function installed.
     *
     * @param \stdClass $function A raw external_functions row.
     * @return array|null
     */
    private static function build_path_or_skip(\stdClass $function): ?array {
        try {
            $info = external_api::external_function_info($function);
        } catch (\Throwable) {
            return null;
        }

        return self::build_path_item($info);
    }

    /**
     * The path item (containing the single POST operation) for one function.
     *
     * @param \stdClass $info As returned by external_api::external_function_info().
     * @return array
     */
    private static function build_path_item(\stdClass $info): array {
        $realendpointnote = 'Real request target: POST ' . self::REAL_ENDPOINT
            . ' with wsfunction=' . $info->name . ' merged into the query string or form body -- '
            . 'this path exists for documentation only, Moodle has no per-function URL.';

        $operation = [
            'operationId' => $info->name,
            'tags' => [tag_mapper::for_function($info->component, $info->name)],
            'deprecated' => $info->deprecated ?? false,
            'description' => $realendpointnote,
            'x-moodle-real-endpoint' => self::REAL_ENDPOINT,
            'x-moodle-capabilities' => empty($info->capabilities) ? [] : explode(',', $info->capabilities),
            'x-moodle-ajax-allowed' => $info->allowed_from_ajax ?? false,
            'parameters' => [
                ['$ref' => '#/components/parameters/moodlewsrestformat'],
                // An enum of one with a default, not a const: a viewer fills
                // a default in, and Swagger UI does nothing with const.
                [
                    'name' => 'wsfunction',
                    'in' => 'query',
                    'required' => true,
                    'schema' => ['type' => 'string', 'enum' => [$info->name], 'default' => $info->name],
                ],
            ],
            'requestBody' => [
                'required' => true,
                'description' => self::REQUEST_ENCODING_NOTE,
                'x-moodle-encoding' => 'php-brackets',
                'content' => [
                    'application/x-www-form-urlencoded' => ['schema' => type_mapper::map($info->parameters_desc)],
                ],
            ],
            'responses' => self::build_responses($info->returns_desc),
        ];

        if (!empty($info->description)) {
            $operation['summary'] = $info->description;
        }

        return ['post' => $operation];
    }

    /**
     * The responses object for one function.
     *
     * Errors are documented inside the 200 response, not under another
     * status: webservice/rest/locallib.php's send_headers() never sets a
     * status code, so an invalid token, a missing capability or an invalid
     * parameter all come back as HTTP 200 with a WebserviceError body. anyOf,
     * not oneOf: a success schema with only optional keys would also match
     * an error body, and oneOf would then reject a perfectly real response.
     *
     * A function without returns_desc answers json_encode(null), the
     * literal null, on success -- not an empty body.
     *
     * The only non-200 answer is server.php refusing before any function
     * runs, when web services or the REST protocol are disabled.
     *
     * @param external_description|null $returns
     * @return array
     */
    private static function build_responses(?external_description $returns): array {
        $success = $returns === null ? ['type' => 'null'] : type_mapper::map($returns, true);

        return [
            '200' => [
                'description' => 'The function\'s result, or a WebserviceError: Moodle reports every '
                    . 'webservice error with HTTP 200, so check for an "exception" key.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'anyOf' => [$success, ['$ref' => '#/components/schemas/WebserviceError']],
                        ],
                    ],
                ],
            ],
            '403' => [
                'description' => 'Web services or the REST protocol are disabled on this site.',
            ],
        ];
    }

    /**
     * Schemas shared across every operation, referenced by $ref.
     *
     * WebserviceError mirrors webservice/rest/locallib.php's
     * generate_error(): errorcode is only set when the exception carries
     * one, debuginfo only when debugging is on. The others are type_mapper's
     * shared warning and file items.
     *
     * @return array
     */
    private static function build_shared_schemas(): array {
        return type_mapper::shared_schemas() + [
            'WebserviceError' => [
                'type' => 'object',
                'properties' => [
                    'exception' => ['type' => 'string', 'description' => 'PHP class of the exception.'],
                    'errorcode' => ['type' => 'string', 'description' => 'Language string key, when the exception has one.'],
                    'message' => ['type' => 'string'],
                    'debuginfo' => ['type' => 'string', 'description' => 'Only present when the site has debugging on.'],
                ],
                'required' => ['exception', 'message'],
            ],
        ];
    }

    /**
     * The webservice token, as an apiKey in the query string: on the wire
     * it is the same ?wstoken= as a parameter, but a viewer asks for it once
     * (Swagger UI's Authorize) and a generated client takes it as auth
     * instead of an argument to every call.
     *
     * @return array
     */
    private static function build_security_schemes(): array {
        return [
            'wstoken' => [
                'type' => 'apiKey',
                'in' => 'query',
                'name' => 'wstoken',
                'description' => 'A Moodle webservice token (Site administration > Server > Web services > '
                    . 'Manage tokens) whose service includes the function. Tokens issued by this plugin '
                    . 'only read documentation.',
            ],
        ];
    }

    /**
     * Parameters identical across every operation, referenced by $ref
     * instead of repeated in each of the (possibly hundreds of) operations.
     * wsfunction is not here -- its value differs per operation, see
     * build_path_item().
     *
     * @return array
     */
    private static function build_shared_parameters(): array {
        return [            // Required and json-only, not optional with a json default:
            // locallib.php falls back to xml when it is omitted, and every
            // response schema in this document is JSON.
            'moodlewsrestformat' => [
                'name' => 'moodlewsrestformat',
                'in' => 'query',
                'required' => true,
                'description' => 'Must be json for the responses documented here. Moodle answers in XML '
                    . 'when this is omitted; that format is not described by this document.',
                'schema' => ['type' => 'string', 'enum' => ['json']],
            ],
        ];
    }
}
