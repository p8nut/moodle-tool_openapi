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

/**
 * Downloads the full OpenAPI catalog as a file, JSON or YAML.
 *
 * Deliberately not the same code path as openapi.php: that endpoint is
 * gated by the runtime access gates (session/IP/token/wstoken), which can
 * all be turned off, but an administrator looking at this plugin's own
 * settings must always be able to get the catalog out. Also always the
 * unrestricted full catalog -- an admin capable of reaching this page can
 * already see everything via document_cache::get() directly, so filtering
 * by service or token scope would not hide anything, only be confusing.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');

require_login();
require_capability('tool/openapi:manage', \context_system::instance());

// This page sends a file, never a rendered page, so nothing else here sets
// a context -- and format_string() below needs one.
$PAGE->set_context(\context_system::instance());

$format = required_param('format', PARAM_ALPHA);
if (!in_array($format, ['json', 'yaml'], true)) {
    throw new \invalid_parameter_exception('Unknown format: ' . $format);
}

// The viewer fetches the same document through this same page, and has
// nowhere to put a file: asking for it inline is what keeps the browser
// from turning that fetch into a download.
$inline = optional_param('inline', 0, PARAM_BOOL);
$protocol = optional_param('protocol', 'rest', PARAM_ALPHA);
if (!in_array($protocol, ['rest', 'restful'], true)) {
    throw new \invalid_parameter_exception('Unknown protocol: ' . $protocol);
}

$document = \tool_openapi\local\document_cache::get();
if ($protocol === 'restful') {
    $document = \tool_openapi\generator\restful_converter::convert($document);
}

if (!$inline) {
    $filename = clean_filename(format_string($SITE->shortname)) . '-openapi.' . ($format === 'yaml' ? 'yaml' : 'json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

if ($format === 'yaml') {
    header('Content-Type: application/yaml; charset=utf-8');
    echo \tool_openapi\generator\yaml_encoder::encode($document);
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
