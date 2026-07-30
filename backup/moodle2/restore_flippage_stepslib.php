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
 * Restore structure for the Flip page activity.
 *
 * @package    mod_flippage
 * @category   backup
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step for restoring one Flip page activity.
 */
class restore_flippage_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines restore path elements.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('flippage', '/activity/flippage');

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('flippage_view', '/activity/flippage/views/view');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restores the activity instance.
     *
     * @param array $data Backup data.
     */
    protected function process_flippage($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('flippage', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('flippage', $oldid, $newitemid);
    }

    /**
     * Restores a user progress record.
     *
     * @param array $data Backup data.
     */
    protected function process_flippage_view($data) {
        global $DB;

        $data = (object)$data;
        $data->flippageid = $this->get_new_parentid('flippage');
        $data->userid = $this->get_mappingid('user', $data->userid);

        if (empty($data->userid)) {
            return;
        }

        $DB->insert_record('flippage_views', $data);
    }

    /**
     * Restores files after all records have been created.
     */
    protected function after_execute() {
        $this->add_related_files('mod_flippage', 'intro', null);
        $this->add_related_files('mod_flippage', 'content', 0);
    }
}
