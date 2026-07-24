<?php
// This file is part of Moodle - http://moodle.org/

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
