<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Stores Flip page reading progress.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

require_sesskey();

$cmid = required_param('cmid', PARAM_INT);
$page = required_param('page', PARAM_INT);
$total = required_param('total', PARAM_INT);

$cm = get_coursemodule_from_id('flippage', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$flippage = $DB->get_record('flippage', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

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

@header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'page' => (int)$progress->currentpage,
    'total' => (int)$progress->totalpages,
    'completed' => (int)$progress->completed,
]);
die;
