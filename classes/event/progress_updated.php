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

namespace mod_flippage\event;

/**
 * Event fired when reading progress is updated.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_updated extends \core\event\base {
    /**
     * Initialises the event.
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'flippage_views';
    }

    /**
     * Returns the event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventprogressupdated', 'flippage');
    }

    /**
     * Returns the URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/flippage/view.php', ['id' => $this->contextinstanceid]);
    }
}
