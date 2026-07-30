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
 * Displays a Flip page activity.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/flippage/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('flippage', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$flippage = $DB->get_record('flippage', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/flippage:view', $context);

$existingprogress = $DB->get_record('flippage_views', ['flippageid' => $flippage->id, 'userid' => $USER->id]);
$canmanage = has_capability('mod/flippage:manage', $context);
if (!$canmanage && $flippage->maxviews > 0 && $existingprogress && $existingprogress->views >= $flippage->maxviews) {
    throw new moodle_exception('accesslimitreached', 'flippage');
}

$progress = flippage_record_access($flippage, $USER->id);
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

\mod_flippage\event\course_module_viewed::create([
    'objectid' => $flippage->id,
    'context' => $context,
])->trigger();

$PAGE->set_url('/mod/flippage/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($flippage->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/flippage/styles.css');

$files = flippage_get_content_files($context);
$filedata = [];
foreach ($files as $file) {
    $url = moodle_url::make_pluginfile_url(
        $context->id,
        'mod_flippage',
        'content',
        0,
        '/',
        $file->get_filename(),
        false
    );
    $mimetype = $file->get_mimetype();
    $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
    $filedata[] = [
        'filename' => $file->get_filename(),
        'url' => $url->out(false),
        'mimetype' => $mimetype,
        'ispdf' => $mimetype === 'application/pdf' || $extension === 'pdf',
        'isimage' => false,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($flippage->name));

if (trim(strip_tags($flippage->intro)) !== '') {
    echo $OUTPUT->box(format_module_intro('flippage', $flippage, $cm->id), 'generalbox mod_introbox');
}

if (!$filedata) {
    echo $OUTPUT->notification(get_string('filenotfound', 'flippage'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

$config = [
    'cmid' => $cm->id,
    'exiturl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
    'pageflipurl' => (new moodle_url('/mod/flippage/vendor/stpageflip/page-flip.browser.min.js'))->out(false),
    'pdfjsloaderurl' => (new moodle_url('/mod/flippage/pdfjsloader.mjs'))->out(false),
    'pdfworkerurl' => (new moodle_url('/mod/flippage/vendor/pdfjs/pdf.worker.mjs'))->out(false),
    'files' => $filedata,
    'strings' => [
        'loading' => get_string('loadingdocument', 'flippage'),
        'previous' => get_string('previouspage', 'flippage'),
        'next' => get_string('nextpage', 'flippage'),
        'zoomin' => get_string('zoomin', 'flippage'),
        'zoomout' => get_string('zoomout', 'flippage'),
        'resetzoom' => get_string('resetzoom', 'flippage'),
        'exitactivity' => get_string('exitactivity', 'flippage'),
        'counter' => get_string('pagecounter', 'flippage', ['page' => '__PAGE__', 'total' => '__TOTAL__']),
    ],
    'progress' => [
        'currentpage' => (int)$progress->currentpage,
        'totalpages' => (int)$progress->totalpages,
        'views' => (int)$progress->views,
        'maxviews' => (int)$flippage->maxviews,
        'completed' => (int)$progress->completed,
    ],
];

echo html_writer::start_div('flippage-activity');
echo html_writer::start_div('flippage-toolbar');
echo html_writer::tag('button', s(get_string('previouspage', 'flippage')), [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'data-flippage-prev' => '1',
]);
echo html_writer::tag('span', '', [
    'class' => 'flippage-counter',
    'data-flippage-counter' => '1',
]);
echo html_writer::tag('button', s(get_string('nextpage', 'flippage')), [
    'type' => 'button',
    'class' => 'btn btn-primary',
    'data-flippage-next' => '1',
]);
echo html_writer::link(new moodle_url('/course/view.php', ['id' => $course->id]), s(get_string('exitactivity', 'flippage')), [
    'class' => 'btn btn-success flippage-exit',
    'data-flippage-exit' => '1',
    'hidden' => 'hidden',
]);
echo html_writer::start_div('flippage-zoom-controls', ['role' => 'group']);
echo html_writer::tag('button', '-', [
    'type' => 'button',
    'class' => 'btn btn-secondary flippage-icon-button',
    'title' => get_string('zoomout', 'flippage'),
    'aria-label' => get_string('zoomout', 'flippage'),
    'data-flippage-zoom-out' => '1',
]);
echo html_writer::tag('span', '100%', [
    'class' => 'flippage-zoom-value',
    'data-flippage-zoom-value' => '1',
]);
echo html_writer::tag('button', '+', [
    'type' => 'button',
    'class' => 'btn btn-secondary flippage-icon-button',
    'title' => get_string('zoomin', 'flippage'),
    'aria-label' => get_string('zoomin', 'flippage'),
    'data-flippage-zoom-in' => '1',
]);
echo html_writer::tag('button', '100%', [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'title' => get_string('resetzoom', 'flippage'),
    'aria-label' => get_string('resetzoom', 'flippage'),
    'data-flippage-zoom-reset' => '1',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::div(s(get_string('loadingdocument', 'flippage')), 'flippage-status', ['data-flippage-status' => '1']);
echo html_writer::start_div('flippage-book-viewport', ['data-flippage-viewport' => '1']);
echo html_writer::div('', 'flippage-book', ['id' => 'flippage-book']);
echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_call_amd('mod_flippage/viewer', 'init', [$config]);

echo $OUTPUT->footer();
