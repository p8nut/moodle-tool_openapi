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
 * OpenAPI document export endpoint for tool_openapi.
 *
 * Machine-facing, not an admin UI page -- lives at the plugin root, the
 * same convention Moodle's own webservice/rest/server.php follows, instead
 * of under pages/. Skipping require_login() in favour of a script's own
 * auth is an accepted pattern for an entry point like this one.
 *
 * Access is decided before anything else touches the cache or the catalog,
 * on purpose: a request that is not authorized gets a 403 without any hint
 * of whether the requested ?service= even exists.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);

// Access is decided by access_checker below, not a single require_login() call: session/IP/token/
// wstoken gates are OR'd, and forcing require_login() would redirect an unauthenticated IP/token/
// wstoken caller to the login page instead of a JSON 403.
require(__DIR__ . '/../../../config.php'); // phpcs:ignore moodle.Files.RequireLogin.Missing

/**
 * Emit a minimal JSON error body.
 *
 * Deliberately never says *why* a request was rejected, so an
 * unauthorized caller cannot use the error message to probe which gate
 * failed. Does not stop the script itself: every call site is immediately
 * followed by its own exit, kept at the top level of this script rather
 * than inside a reusable function -- a function whose whole point is to
 * unconditionally terminate the request is not a unit worth separating
 * from where that termination actually happens.
 *
 * @param int $httpstatus
 * @param string $error
 */
function tool_openapi_send_error(int $httpstatus, string $error): void {
    http_response_code($httpstatus);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $error]);
}

$service = optional_param('service', null, PARAM_ALPHANUMEXT);
$version = optional_param('version', '3.1', PARAM_RAW_TRIMMED);
$format = optional_param('format', 'json', PARAM_ALPHA);
$protocol = optional_param('protocol', 'rest', PARAM_ALPHA);

if (!in_array($version, ['3.1', '3.0'], true)) {
    tool_openapi_send_error(400, 'invalid_version');
    exit;
}
if (!in_array($format, ['json', 'yaml'], true)) {
    tool_openapi_send_error(400, 'invalid_format');
    exit;
}
if (!in_array($protocol, ['rest', 'restful'], true)) {
    tool_openapi_send_error(400, 'invalid_protocol');
    exit;
}

$scope = \tool_openapi\access\access_checker::default()->authorize($service);
if ($scope === null) {
    tool_openapi_send_error(403, 'access_denied');
    exit;
}

$document = \tool_openapi\local\document_cache::get();

if ($service !== null) {
    try {
        $document = \tool_openapi\generator\service_filter::filter($document, $service);
    } catch (\moodle_exception) {
        tool_openapi_send_error(400, 'invalid_service');
        exit;
    }
}

$document = $scope->restrict($document);

if ($protocol === 'restful') {
    $document = \tool_openapi\generator\restful_converter::convert($document);
}

if ($version === '3.0') {
    $document = \tool_openapi\generator\openapi_downgrader::to_3_0($document);
}

if ($format === 'yaml') {
    header('Content-Type: application/yaml; charset=utf-8');
    echo \tool_openapi\generator\yaml_encoder::encode($document);
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
