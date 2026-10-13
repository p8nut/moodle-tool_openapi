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

use tool_openapi\local\service_functions;

/**
 * Trims an already-built OpenAPI document down to one external service.
 *
 * Deliberately not cached: external_services_functions can change without
 * a plugin install/upgrade/uninstall (an administrator can edit a custom
 * service's function list at any time), so this filtered result does not
 * have the same cache-invalidation guarantee as the full catalog and is
 * recomputed on every request that asks for it.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class service_filter {
    /**
     * Keep only the paths for functions assigned to one external service.
     *
     * @param array $document A document as returned by document_builder::build().
     * @param string $serviceshortname The service's external_services.shortname.
     * @return array A copy of $document with paths trimmed to that service.
     * @throws \moodle_exception If no service has that shortname.
     */
    public static function filter(array $document, string $serviceshortname): array {
        return document_builder::keep_functions($document, service_functions::for_shortname($serviceshortname));
    }
}
