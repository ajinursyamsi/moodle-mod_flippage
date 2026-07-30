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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace mod_flippage\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for Flip page.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Gets the completion state for a rule.
     *
     * @param string $rule rule name
     * @return int
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $completed = $DB->record_exists('flippage_views', [
            'flippageid' => $this->cm->instance,
            'userid' => $this->userid,
            'completed' => 1,
        ]);

        return $completed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Defines custom rules.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionlastpage'];
    }

    /**
     * Returns rule descriptions.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionlastpage' => get_string('completiondetail:lastpage', 'flippage'),
        ];
    }

    /**
     * Completion condition ordering.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionlastpage',
        ];
    }
}
