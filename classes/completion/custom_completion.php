<?php
// This file is part of Moodle - http://moodle.org/

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
