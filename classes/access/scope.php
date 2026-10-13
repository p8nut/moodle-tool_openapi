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
 * What an authorized accessor is allowed to see: the full catalog, or a
 * concrete list of function names.
 *
 * @package    tool_openapi
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scope {
    /**
     * Creates a scope with an explicit restriction state.
     *
     * @param bool $unrestricted
     * @param string[] $allowedfunctions Ignored when $unrestricted is true.
     */
    private function __construct(
        /** @var bool Whether every function in the catalog is allowed. */
        private readonly bool $unrestricted,
        /** @var string[] Allowed function names, ignored when $unrestricted is true. */
        private readonly array $allowedfunctions,
    ) {
    }

    /**
     * No restriction: every function in the catalog.
     *
     * @return self
     */
    public static function full_catalog(): self {
        return new self(true, []);
    }

    /**
     * Restricted to exactly these function names.
     *
     * @param string[] $functionnames
     * @return self
     */
    public static function limited_to(array $functionnames): self {
        return new self(false, array_values($functionnames));
    }

    /**
     * Build a scope from tool_openapi_tokens.allowedfunctions or
     * tool_openapi_ip_rules.allowedfunctions: one function name per line,
     * null or blank means the full catalog.
     *
     * @param string|null $raw
     * @return self
     */
    public static function from_allowedfunctions_field(?string $raw): self {
        if ($raw === null || trim($raw) === '') {
            return self::full_catalog();
        }

        $names = array_filter(array_map('trim', explode("\n", $raw)), static fn (string $name): bool => $name !== '');

        return self::limited_to(array_values($names));
    }

    /**
     * Whether this scope allows the full catalog.
     *
     * @return bool
     */
    public function is_unrestricted(): bool {
        return $this->unrestricted;
    }

    /**
     * Whether this scope allows the given function name.
     *
     * @param string $functionname
     * @return bool
     */
    public function allows(string $functionname): bool {
        return $this->unrestricted || in_array($functionname, $this->allowedfunctions, true);
    }

    /**
     * The allowed function names, or null when unrestricted.
     *
     * @return string[]|null Null when unrestricted.
     */
    public function allowed_functions(): ?array {
        return $this->unrestricted ? null : $this->allowedfunctions;
    }

    /**
     * Trim a document's paths down to what this scope allows.
     *
     * Same kind of filtering as generator\service_filter, applied by
     * function name instead of by service shortname -- kept here, not
     * there, because this is the scope's own concern: what an
     * already-authorized accessor gets to see, independent of whether a
     * ?service= was also requested.
     *
     * @param array $document A document as returned by document_builder::build().
     * @return array A copy of $document with paths trimmed to this scope.
     */
    public function restrict(array $document): array {
        if ($this->unrestricted) {
            return $document;
        }

        return \tool_openapi\generator\document_builder::keep_functions($document, $this->allowedfunctions);
    }
}
