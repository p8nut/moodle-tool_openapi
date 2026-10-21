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

use tool_openapi\generator\document_builder;
use tool_openapi\generator\restful_converter;
use tool_openapi\generator\service_filter;

/**
 * The document cli/generate.php writes, kept out of the script so it can be
 * tested.
 *
 * Same pipeline as openapi.php minus the access check (whoever runs a CLI
 * script already has the site's code and database): a fresh build rather
 * than the cache, so a function installed since the last cache rebuild is
 * not missed, then the service filter and the protocol conversion.
 *
 * The server URL is made relative to the site: a CLI run sees the wwwroot
 * of its own config.php, which in a container or a CI job is rarely the URL
 * clients call, and a document committed to a repository should not change
 * with the machine that generated it.
 *
 * @package    tool_openapi
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cli_generator {
    /** @var string[] The protocols openapi.php accepts in ?protocol=. */
    public const PROTOCOLS = ['rest', 'restful'];

    /**
     * Build the document for the whole catalog or one external service.
     *
     * @param string|null $service An external_services.shortname, or null for the whole catalog.
     * @param string $protocol One of self::PROTOCOLS.
     * @return array
     * @throws \moodle_exception If the protocol is unknown or no service has that shortname.
     */
    public static function generate(?string $service, string $protocol = 'rest'): array {
        global $CFG;

        if (!in_array($protocol, self::PROTOCOLS, true)) {
            throw new \moodle_exception('invalidprotocol', 'tool_openapi', '', $protocol);
        }

        $document = document_builder::build();
        if ($service !== null) {
            $document = service_filter::filter($document, $service);
        }
        if ($protocol === 'restful') {
            $document = restful_converter::convert($document);
        }

        foreach ($document['servers'] as $i => $server) {
            if (str_starts_with($server['url'], $CFG->wwwroot)) {
                $document['servers'][$i]['url'] = substr($server['url'], strlen($CFG->wwwroot)) ?: '/';
            }
        }

        return $document;
    }

    /**
     * The document as written to a file: the same JSON flags as openapi.php,
     * plus a trailing newline.
     *
     * @param array $document
     * @return string
     */
    public static function encode(array $document): string {
        return json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}
