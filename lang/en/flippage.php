<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English strings for Flip page.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Flip page';
$string['modulename'] = 'Flip page';
$string['modulenameplural'] = 'Flip pages';
$string['pluginadministration'] = 'Flip page administration';
$string['flippage:addinstance'] = 'Add a new Flip page activity';
$string['flippage:view'] = 'View Flip page activity';
$string['flippage:manage'] = 'Manage Flip page activity';
$string['contentfile'] = 'Source file';
$string['contentfile_help'] = 'Upload one PDF file. The PDF pages will be rendered as a flip page activity.';
$string['maxviews'] = 'Allowed accesses';
$string['maxviews_help'] = 'Set to 0 for unlimited access. Teacher and manager roles are not blocked by this limit.';
$string['completionlastpage'] = 'Student must reach the final page';
$string['completiondetail:lastpage'] = 'Reach the final page';
$string['eventcoursemoduleviewed'] = 'Flip page viewed';
$string['eventprogressupdated'] = 'Flip page progress updated';
$string['filenotfound'] = 'No readable file is available for this activity.';
$string['accesslimitreached'] = 'You have reached the allowed number of accesses for this activity.';
$string['pagecounter'] = 'Page {$a->page} of {$a->total}';
$string['previouspage'] = 'Previous page';
$string['nextpage'] = 'Next page';
$string['zoomin'] = 'Zoom in';
$string['zoomout'] = 'Zoom out';
$string['resetzoom'] = 'Reset zoom';
$string['exitactivity'] = 'Exit activity';
$string['downloadsource'] = 'Download source file';
$string['loadingdocument'] = 'Loading document...';
$string['uploadonepdfonly'] = 'Upload one PDF file only.';
$string['privacy:metadata:flippage_views'] = 'Stores user access count and reading progress for Flip page activities.';
$string['privacy:metadata:flippage_views:userid'] = 'The user whose reading progress is stored.';
$string['privacy:metadata:flippage_views:views'] = 'The number of times the user opened the activity.';
$string['privacy:metadata:flippage_views:currentpage'] = 'The latest page reached by the user.';
$string['privacy:metadata:flippage_views:totalpages'] = 'The number of pages detected for the document.';
$string['privacy:metadata:flippage_views:completed'] = 'Whether the user has reached the final page.';
$string['privacy:metadata:flippage_views:firstaccess'] = 'The first time the user opened the activity.';
$string['privacy:metadata:flippage_views:lastaccess'] = 'The latest time the user opened the activity.';
