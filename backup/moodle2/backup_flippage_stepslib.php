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
 * Backup structure for the Flip page activity.
 *
 * @package    mod_flippage
 * @category   backup
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete Flip page backup structure.
 */
class backup_flippage_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the nested backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $flippage = new backup_nested_element('flippage', ['id'], [
            'name',
            'intro',
            'introformat',
            'maxviews',
            'completionlastpage',
            'displayincourse',
            'coursepageheight',
            'revision',
            'timemodified',
        ]);

        $views = new backup_nested_element('views');
        $view = new backup_nested_element('view', ['id'], [
            'userid',
            'views',
            'currentpage',
            'totalpages',
            'completed',
            'firstaccess',
            'lastaccess',
        ]);

        $flippage->add_child($views);
        $views->add_child($view);

        $flippage->set_source_table('flippage', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $view->set_source_table('flippage_views', ['flippageid' => backup::VAR_PARENTID]);
            $view->annotate_ids('user', 'userid');
        }

        $flippage->annotate_files('mod_flippage', 'intro', null);
        $flippage->annotate_files('mod_flippage', 'content', 0);

        return $this->prepare_activity_structure($flippage);
    }
}
