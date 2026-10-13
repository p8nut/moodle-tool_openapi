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

namespace tool_openapi\output;

use tool_openapi\output\documentation\page;
use tool_openapi\output\documentation\viewer_page;

/**
 * Tests for the documentation renderables.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\output\documentation\page
 * @covers     \tool_openapi\output\documentation\viewer_page
 */
final class documentation_page_test extends \advanced_testcase {
    /**
     * Both formats are offered, and each asks the download endpoint for
     * its own.
     */
    public function test_both_download_formats_are_offered(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('format=json', $data['jsonurl']);
        $this->assertStringContainsString('format=yaml', $data['yamlurl']);
    }

    /**
     * Purging is a state change, so its link carries a sesskey.
     */
    public function test_purge_link_is_sesskey_protected(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('sesskey=', $data['purgeurl']);
    }

    /**
     * The viewer is a real destination now, not a placeholder.
     */
    public function test_viewer_is_linked(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('documentation/viewer.php', $data['viewerurl']);
    }

    /**
     * The viewer loads the document from this plugin's own admin-only
     * endpoint, inline, and points Try it out at Moodle's REST server.
     */
    public function test_viewer_page_wiring(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();

        $data = (new viewer_page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('documentation/download.php', $data['specurl']);
        $this->assertStringContainsString('inline=1', $data['specurl']);
        $this->assertSame($CFG->wwwroot . '/webservice/rest/server.php', $data['endpoint']);
        $this->assertSame(viewer_page::ELEMENT_ID, $data['elementid']);
    }

    /**
     * The viewer points at the bundled library directly.
     *
     * Not through $PAGE->requires->js(), which routes .js files through
     * lib/javascript.php and minifies an already-minified bundle into
     * something the browser cannot parse.
     */
    public function test_viewer_page_links_the_bundled_library(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new viewer_page())->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('thirdparty/swagger-ui/swagger-ui-bundle.js', $data['libraryurl']);
        $this->assertStringNotContainsString('javascript.php', $data['libraryurl']);
    }

    /**
     * With the REST protocol off, the viewer says so: Moodle answers Try it
     * out with a bare 403 and no body, which explains nothing on its own.
     */
    public function test_viewer_warns_when_rest_is_unavailable(): void {
        global $PAGE;

        $this->resetAfterTest();

        set_config('enablewebservices', 0);
        $data = (new viewer_page())->export_for_template($PAGE->get_renderer('core'));
        $this->assertNotSame('', $data['restwarning']);

        set_config('enablewebservices', 1);
        set_config('webserviceprotocols', 'rest');
        $data = (new viewer_page())->export_for_template($PAGE->get_renderer('core'));
        $this->assertSame('', $data['restwarning']);
    }

    /**
     * Every action carries an icon, and each is decorative: the label next
     * to it already names the action.
     */
    public function test_actions_carry_decorative_icons(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new page())->export_for_template($PAGE->get_renderer('core'));

        foreach (['viewericon', 'downloadicon', 'purgeicon'] as $icon) {
            $this->assertStringContainsString('aria-hidden="true"', $data[$icon]);
        }
    }

    /**
     * The restful viewer loads the restful document, and the REST / RESTful
     * switch is offered only where webservice_restful is installed.
     */
    public function test_viewer_follows_the_chosen_protocol(): void {
        global $PAGE;

        $this->resetAfterTest();

        $data = (new viewer_page('restful'))->export_for_template($PAGE->get_renderer('core'));

        $this->assertStringContainsString('protocol=restful', $data['specurl']);
        if (array_key_exists('restful', \core_component::get_plugin_list('webservice'))) {
            $this->assertSame(['REST', 'RESTful'], array_column($data['protocols'], 'label'));
            $this->assertSame([false, true], array_column($data['protocols'], 'active'));
        } else {
            $this->assertSame([], $data['protocols']);
        }
    }
}
