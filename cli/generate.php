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
 * Write the OpenAPI document from the command line: no HTTP request, no
 * token, no access method to turn on.
 *
 * @package    tool_openapi
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['service' => null, 'protocol' => 'rest', 'output' => null, 'help' => false],
    ['s' => 'service', 'p' => 'protocol', 'o' => 'output', 'h' => 'help']
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help']) {
    echo "Write this site's OpenAPI document (3.1, JSON) without going through openapi.php.

Options:
-s, --service=<shortname>  Only the functions of this external service (default: the whole catalog)
-p, --protocol=rest|restful
                           rest: core's webservice/rest/server.php (default);
                           restful: webservice_restful's per-function URLs
-o, --output=<file>        Write to this file instead of standard output
-h, --help                 Print this help

Server URLs are relative to the site, so the document does not depend on the
wwwroot of the machine that generated it.

Example:
\$ php admin/tool/openapi/cli/generate.php --service=my_service --protocol=restful --output=openapi.json
";
    exit(0);
}

// An option given without "=value" comes back as true.
foreach (['service', 'protocol', 'output'] as $name) {
    if ($options[$name] === true || $options[$name] === '') {
        cli_error("--{$name} needs a value, see --help");
    }
}

try {
    $document = \tool_openapi\local\cli_generator::generate($options['service'], (string) $options['protocol']);
} catch (\moodle_exception $e) {
    cli_error($e->getMessage());
}

$json = \tool_openapi\local\cli_generator::encode($document);
if ($options['output'] === null) {
    echo $json;
} else if (file_put_contents($options['output'], $json) === false) {
    cli_error("Cannot write {$options['output']}");
}
