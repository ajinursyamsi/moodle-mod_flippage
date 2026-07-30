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

namespace mod_flippage\external;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use context_module;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External service for updating Flip page reading progress.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_progress extends external_api {

    /**
     * Defines service parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'page' => new external_value(PARAM_INT, 'Highest visible page number'),
            'total' => new external_value(PARAM_INT, 'Total page count'),
        ]);
    }

    /**
     * Stores reading progress for the current user.
     *
     * @param int $cmid Course module id.
     * @param int $page Highest visible page number.
     * @param int $total Total page count.
     * @return array
     */
    public static function execute(int $cmid, int $page, int $total): array {
        global $DB, $USER;

        [
            'cmid' => $cmid,
            'page' => $page,
            'total' => $total,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'page' => $page,
            'total' => $total,
        ]);

        $cm = get_coursemodule_from_id('flippage', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $flippage = $DB->get_record('flippage', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);

        self::validate_context($context);
        require_login($course, true, $cm);
        require_capability('mod/flippage:view', $context);

        $page = max(1, $page);
        $total = max(1, $total);
        $completed = $page >= $total ? 1 : 0;
        $now = time();

        $params = ['flippageid' => $flippage->id, 'userid' => $USER->id];
        if ($progress = $DB->get_record('flippage_views', $params)) {
            $progress->currentpage = max((int)$progress->currentpage, $page);
            $progress->totalpages = max((int)$progress->totalpages, $total);
            $progress->completed = max((int)$progress->completed, $completed);
            $progress->lastaccess = $now;
            $DB->update_record('flippage_views', $progress);
        } else {
            $progress = (object)[
                'flippageid' => $flippage->id,
                'userid' => $USER->id,
                'views' => 0,
                'currentpage' => $page,
                'totalpages' => $total,
                'completed' => $completed,
                'firstaccess' => $now,
                'lastaccess' => $now,
            ];
            $progress->id = $DB->insert_record('flippage_views', $progress);
        }

        if (!empty($flippage->completionlastpage) && $progress->completed) {
            $completion = new completion_info($course);
            if ($completion->is_enabled($cm)) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $USER->id);
            }
        }

        \mod_flippage\event\progress_updated::create([
            'objectid' => $progress->id,
            'context' => $context,
            'relateduserid' => $USER->id,
        ])->trigger();

        return [
            'success' => true,
            'page' => (int)$progress->currentpage,
            'total' => (int)$progress->totalpages,
            'completed' => (int)$progress->completed,
        ];
    }

    /**
     * Defines service return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether progress was stored'),
            'page' => new external_value(PARAM_INT, 'Stored current page'),
            'total' => new external_value(PARAM_INT, 'Stored total page count'),
            'completed' => new external_value(PARAM_BOOL, 'Whether the activity is completed'),
        ]);
    }
}
