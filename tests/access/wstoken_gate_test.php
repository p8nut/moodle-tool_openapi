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

namespace tool_openapi\access;

/**
 * Tests for wstoken_gate.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_openapi\access\wstoken_gate
 */
final class wstoken_gate_test extends \advanced_testcase {
    /**
     * Cleans up the simulated request param after each test.
     */
    protected function tearDown(): void {
        unset($_GET['wstoken']);
        parent::tearDown();
    }

    /**
     * Creates an external_services row wired to one function.
     *
     * @param int $enabled
     * @param array $fields Other external_services columns to set.
     * @return int The service id.
     */
    private function create_service(int $enabled = 1, array $fields = []): int {
        global $DB;

        $serviceid = $DB->insert_record('external_services', (object) ($fields + [
            'name' => 'svc',
            'shortname' => 'svc',
            'enabled' => $enabled,
            'restrictedusers' => 0,
            'downloadfiles' => 0,
            'uploadfiles' => 0,
            'timecreated' => time(),
        ]));
        $DB->insert_record('external_services_functions', (object) [
            'externalserviceid' => $serviceid,
            'functionname' => 'core_course_get_courses',
        ]);

        return $serviceid;
    }

    /**
     * Inserts an external_tokens row for the given service.
     *
     * @param int $serviceid
     * @param string $token
     * @param int|null $validuntil
     * @param array $fields Other external_tokens columns to set (userid, iprestriction, sid...).
     */
    private function create_token(int $serviceid, string $token, ?int $validuntil = null, array $fields = []): void {
        global $DB;

        $DB->insert_record('external_tokens', (object) ($fields + [
            'token' => $token,
            'tokentype' => 0,
            'userid' => get_admin()->id,
            'externalserviceid' => $serviceid,
            'contextid' => \context_system::instance()->id,
            'creatorid' => get_admin()->id,
            'validuntil' => $validuntil,
            'timecreated' => time(),
        ]));
    }

    /**
     * A site with web services on, and a request carrying a token for a
     * fresh service.
     *
     * @param array $tokenfields See create_token().
     * @param array $servicefields See create_service().
     * @return int The service id.
     */
    private function request_with_token(array $tokenfields = [], array $servicefields = []): int {
        set_config('enablewebservices', 1);
        $serviceid = $this->create_service(1, $servicefields);
        $this->create_token($serviceid, 'validtoken123', null, $tokenfields);
        $_GET['wstoken'] = 'validtoken123';

        return $serviceid;
    }

    /**
     * No wstoken param at all: this gate does not authorize.
     */
    public function test_no_token_param_does_not_authorize(): void {
        $this->resetAfterTest();

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A well-formed but unknown token does not authorize.
     */
    public function test_unknown_token_does_not_authorize(): void {
        $this->resetAfterTest();
        $_GET['wstoken'] = 'doesnotexist123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A valid token grants exactly its service's own functions.
     */
    public function test_valid_token_is_limited_to_its_service(): void {
        $this->resetAfterTest();
        $this->request_with_token();

        $scope = (new wstoken_gate())->authorize(null);

        $this->assertNotNull($scope);
        $this->assertSame(['core_course_get_courses'], $scope->allowed_functions());
    }

    /**
     * An expired token does not authorize.
     */
    public function test_expired_token_does_not_authorize(): void {
        $this->resetAfterTest();
        set_config('enablewebservices', 1);
        $serviceid = $this->create_service();
        $this->create_token($serviceid, 'expiredtoken123', time() - 3600);
        $_GET['wstoken'] = 'expiredtoken123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A token for a disabled service does not authorize.
     */
    public function test_token_for_disabled_service_does_not_authorize(): void {
        $this->resetAfterTest();
        set_config('enablewebservices', 1);
        $serviceid = $this->create_service(0);
        $this->create_token($serviceid, 'validtoken123');
        $_GET['wstoken'] = 'validtoken123';

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * With web services off, no token can call anything, so none reads
     * the documentation either.
     */
    public function test_token_does_not_authorize_with_web_services_off(): void {
        $this->resetAfterTest();
        $this->request_with_token();
        set_config('enablewebservices', 0);

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A token restricted to other addresses is refused, as core refuses it.
     */
    public function test_token_ip_restriction_is_enforced(): void {
        $this->resetAfterTest();
        $this->request_with_token(['iprestriction' => '192.0.2.1']);

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A session-bound token whose session is gone is refused.
     */
    public function test_session_token_without_its_session_is_refused(): void {
        $this->resetAfterTest();
        $this->request_with_token(['sid' => 'nosuchsession']);

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * The token's user must still be allowed to log in.
     */
    public function test_token_of_a_suspended_user_is_refused(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $this->request_with_token(['userid' => $user->id]);

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A service's required capability applies to the token's user.
     */
    public function test_service_required_capability_is_enforced(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->request_with_token(['userid' => $user->id], ['requiredcapability' => 'moodle/site:config']);

        $this->assertNull((new wstoken_gate())->authorize(null));
    }

    /**
     * A restricted service accepts only its allowed users, within their
     * own expiry.
     */
    public function test_restricted_service_requires_an_allowed_user(): void {
        global $DB;
        $this->resetAfterTest();
        $serviceid = $this->request_with_token([], ['restrictedusers' => 1]);

        $this->assertNull((new wstoken_gate())->authorize(null));

        $allowedid = $DB->insert_record('external_services_users', (object) [
            'externalserviceid' => $serviceid,
            'userid' => get_admin()->id,
            'timecreated' => time(),
        ]);
        $this->assertNotNull((new wstoken_gate())->authorize(null));

        $DB->set_field('external_services_users', 'validuntil', time() - 3600, ['id' => $allowedid]);
        $this->assertNull((new wstoken_gate())->authorize(null));
    }
}
