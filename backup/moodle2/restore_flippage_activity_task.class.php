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

/**
 * Defines the restore task for the Flip page activity.
 *
 * @package    mod_flippage
 * @category   backup
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flippage/backup/moodle2/restore_flippage_stepslib.php');

/**
 * Restore task for one Flip page activity instance.
 */
class restore_flippage_activity_task extends restore_activity_task {

    /**
     * Flip page does not define additional restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Adds the structure step for activity restore.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_flippage_activity_structure_step('flippage_structure', 'flippage.xml'));
    }

    /**
     * Defines content fields that may contain encoded links.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('flippage', ['intro'], 'flippage'),
        ];
    }

    /**
     * Defines link decoding rules for Flip page URLs.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('FLIPPAGEVIEWBYID', '/mod/flippage/view.php?id=$1', 'course_module'),
            new restore_decode_rule('FLIPPAGEINDEX', '/mod/flippage/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Defines activity log restore rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('flippage', 'view', 'view.php?id={course_module}', '{flippage}'),
        ];
    }

    /**
     * Defines course-level log restore rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('flippage', 'view all', 'index.php?id={course}', null),
        ];
    }
}
