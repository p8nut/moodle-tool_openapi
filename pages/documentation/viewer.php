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
 * Browses the generated catalog with the bundled Swagger UI.
 *
 * The library is a plain browser bundle, not an AMD module: it defines a
 * global that tool_openapi/viewer then configures. The template includes it
 * directly -- see its own comment for why it cannot go through
 * $PAGE->requires->js() -- so all this page adds is the stylesheet, which
 * has no such problem, and the AMD call the footer emits after it.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_openapi_viewer');

$pagetitle = get_string('viewerheading', 'tool_openapi');

$protocol = optional_param('protocol', 'rest', PARAM_ALPHA) === 'restful' ? 'restful' : 'rest';

$PAGE->set_url(new moodle_url('/admin/tool/openapi/pages/documentation/viewer.php', ['protocol' => $protocol]));
$PAGE->navbar->add($pagetitle);
$PAGE->set_title($pagetitle);

$renderable = new \tool_openapi\output\documentation\viewer_page($protocol);
$context = $renderable->export_for_template($OUTPUT);

$PAGE->requires->css(new moodle_url('/admin/tool/openapi/thirdparty/swagger-ui/swagger-ui.css'));
$PAGE->requires->js_call_amd('tool_openapi/viewer', 'init', [
    $context['specurl'],
    $context['endpoint'],
    $context['elementid'],
    $protocol,
]);

echo $OUTPUT->header();
echo $OUTPUT->render(\tool_openapi\local\settings_nav::tabtree(\tool_openapi\local\settings_nav::TAB_DOCS));
echo $OUTPUT->render_from_template('tool_openapi/documentation/viewer', $context);
echo $OUTPUT->footer();
