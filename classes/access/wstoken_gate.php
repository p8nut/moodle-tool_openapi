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

use tool_openapi\local\service_functions;

/**
 * Authorizes a request bearing an existing Moodle webservice token
 * (external_tokens), reused for reading documentation instead of issuing a
 * second secret to an integrator that already has one.
 *
 * The scope is always that token's own service functions, computed live
 * from external_services_functions -- never the full catalog, not even for
 * an administrator's token, and never stored on any tool_openapi table.
 * Enabling this gate adds no new authorization surface: anyone who could
 * already obtain a legitimate wstoken already had access to call those
 * functions; seeing them documented gives nothing they did not already
 * have.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wstoken_gate implements access_gate {
    /**
     * Whether the wstoken-reuse gate is turned on.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('tool_openapi', 'enablewstokengate');
    }

    /**
     * Authorizes via an existing Moodle webservice token.
     *
     * A token documents exactly what it could call, so it is accepted only
     * when webservice/lib.php's authenticate_user() would accept it: same
     * checks, minus its side effects (logging the user in, deleting expired
     * tokens, login-failed events) which have no place on a read-only
     * endpoint. Maintenance mode is not checked: reading documentation runs
     * no function.
     *
     * @param string|null $requestedservice Unused: the scope is always the token's own service.
     * @return scope|null
     */
    public function authorize(?string $requestedservice): ?scope {
        global $CFG, $DB;

        $token = optional_param('wstoken', '', PARAM_ALPHANUM);
        if ($token === '' || empty($CFG->enablewebservices)) {
            return null;
        }

        $record = $DB->get_record('external_tokens', ['token' => $token], '*', IGNORE_MISSING);
        if (!$record || !self::token_is_usable($record)) {
            return null;
        }

        $user = $DB->get_record('user', ['id' => $record->userid, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user || empty($user->confirmed) || !empty($user->suspended) || $user->auth === 'nologin') {
            return null;
        }

        $service = $DB->get_record('external_services', ['id' => $record->externalserviceid, 'enabled' => 1], '*', IGNORE_MISSING);
        if (!$service || !self::service_allows($service, $user)) {
            return null;
        }

        return scope::limited_to(service_functions::for_id((int) $service->id));
    }

    /**
     * The token's own limits: expiry, IP restriction, and for a
     * session-bound token, its session still existing.
     *
     * @param \stdClass $record An external_tokens row.
     * @return bool
     */
    private static function token_is_usable(\stdClass $record): bool {
        if (!empty($record->validuntil) && $record->validuntil < time()) {
            return false;
        }
        if (!empty($record->iprestriction) && !address_in_subnet(getremoteaddr(), $record->iprestriction)) {
            return false;
        }

        return empty($record->sid) || \core\session\manager::session_exists($record->sid);
    }

    /**
     * The service's own limits on who may use it: a required capability,
     * and for a restricted service, the user's entry in its allowed users
     * with that entry's expiry and IP restriction.
     *
     * @param \stdClass $service An external_services row.
     * @param \stdClass $user
     * @return bool
     */
    private static function service_allows(\stdClass $service, \stdClass $user): bool {
        global $DB;

        if ($service->requiredcapability && !has_capability($service->requiredcapability, \context_system::instance(), $user)) {
            return false;
        }
        if (!$service->restrictedusers) {
            return true;
        }

        $allowed = $DB->get_record(
            'external_services_users',
            ['externalserviceid' => $service->id, 'userid' => $user->id],
            '*',
            IGNORE_MISSING
        );

        return $allowed
            && (empty($allowed->validuntil) || $allowed->validuntil >= time())
            && (empty($allowed->iprestriction) || address_in_subnet(getremoteaddr(), $allowed->iprestriction));
    }
}
