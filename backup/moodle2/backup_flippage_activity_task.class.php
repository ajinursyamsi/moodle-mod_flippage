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
 * Defines the backup task for the Flip page activity.
 *
 * @package    mod_flippage
 * @category   backup
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/flippage/backup/moodle2/backup_flippage_stepslib.php');

/**
 * Backup task for one Flip page activity instance.
 */
class backup_flippage_activity_task extends backup_activity_task {

    /**
     * Flip page does not define additional backup settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Adds the structure step for the activity backup.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_flippage_activity_structure_step('flippage_structure', 'flippage.xml'));
    }

    /**
     * Encodes links to Flip page scripts.
     *
     * @param string $content HTML content that may contain links to Flip page.
     * @return string Content with encoded links.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/flippage\/index.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@FLIPPAGEINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/flippage\/view.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@FLIPPAGEVIEWBYID*$2@$', $content);

        return $content;
    }
}
