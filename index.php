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
 * Lists Flip page activities in a course.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$PAGE->set_url('/mod/flippage/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'flippage'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'flippage'));

$modinfo = get_fast_modinfo($course);
$instances = $modinfo->get_instances_of('flippage');

if (!$instances) {
    echo $OUTPUT->notification(get_string('noactivities', 'moodle'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [get_string('name'), get_string('sectionname', 'format_' . $course->format)];
$table->attributes['class'] = 'generaltable mod-flippage-index';

foreach ($instances as $cm) {
    if (!$cm->uservisible) {
        continue;
    }
    $sectionname = get_section_name($course, $cm->sectionnum);
    $table->data[] = [
        html_writer::link(new moodle_url('/mod/flippage/view.php', ['id' => $cm->id]), format_string($cm->name)),
        format_string($sectionname),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
