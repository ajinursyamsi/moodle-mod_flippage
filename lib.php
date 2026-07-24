<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Core callbacks for the Flip page activity.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns supported module features.
 *
 * @param string $feature feature constant
 * @return mixed
 */
function flippage_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * File manager options for activity content.
 *
 * @param context $context module context
 * @return array
 */
function flippage_get_filemanager_options(context $context): array {
    return [
        'subdirs' => 0,
        'maxbytes' => 0,
        'maxfiles' => 1,
        'accepted_types' => ['.pdf'],
        'return_types' => FILE_INTERNAL,
    ];
}

/**
 * Adds a Flip page instance.
 *
 * @param stdClass $data form data
 * @param moodleform|null $mform form instance
 * @return int
 */
function flippage_add_instance($data, $mform = null): int {
    global $DB;

    $data->timemodified = time();
    $data->revision = 1;
    $data->maxviews = max(0, (int)($data->maxviews ?? 0));
    $data->completionlastpage = empty($data->completionlastpage) ? 0 : 1;

    $id = $DB->insert_record('flippage', $data);
    $DB->set_field('course_modules', 'instance', $id, ['id' => $data->coursemodule]);

    $context = context_module::instance($data->coursemodule);
    if (!empty($data->contentfiles)) {
        file_save_draft_area_files(
            $data->contentfiles,
            $context->id,
            'mod_flippage',
            'content',
            0,
            flippage_get_filemanager_options($context)
        );
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'flippage', $id, $completiontimeexpected);

    return $id;
}

/**
 * Updates a Flip page instance.
 *
 * @param stdClass $data form data
 * @param moodleform|null $mform form instance
 * @return bool
 */
function flippage_update_instance($data, $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->revision = ((int)($data->revision ?? 0)) + 1;
    $data->maxviews = max(0, (int)($data->maxviews ?? 0));
    $data->completionlastpage = empty($data->completionlastpage) ? 0 : 1;

    $DB->update_record('flippage', $data);

    $context = context_module::instance($data->coursemodule);
    if (!empty($data->contentfiles)) {
        file_save_draft_area_files(
            $data->contentfiles,
            $context->id,
            'mod_flippage',
            'content',
            0,
            flippage_get_filemanager_options($context)
        );
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'flippage', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Deletes a Flip page instance.
 *
 * @param int $id instance id
 * @return bool
 */
function flippage_delete_instance($id): bool {
    global $DB;

    if (!$flippage = $DB->get_record('flippage', ['id' => $id])) {
        return false;
    }

    $cm = get_coursemodule_from_instance('flippage', $id, $flippage->course, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_flippage');

    $DB->delete_records('flippage_views', ['flippageid' => $id]);
    $DB->delete_records('flippage', ['id' => $id]);
    \core_completion\api::update_completion_date_event($cm->id, 'flippage', $id, null);

    return true;
}

/**
 * Serves files from the activity file area.
 */
function mod_flippage_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== 'content') {
        return false;
    }

    require_login($course, true, $cm);
    require_capability('mod/flippage:view', $context);

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_flippage', $filearea, (int)$itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Adds cached course module information.
 *
 * @param stdClass $coursemodule course module record
 * @return cached_cm_info|bool
 */
function flippage_get_coursemodule_info(stdClass $coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completionlastpage';
    if (!$flippage = $DB->get_record('flippage', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $flippage->name;

    if ($coursemodule->showdescription) {
        $result->content = format_module_intro('flippage', $flippage, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionlastpage'] = (int)$flippage->completionlastpage;
    }

    return $result;
}

/**
 * Describes active custom completion rules.
 *
 * @param cm_info|stdClass $cm course module
 * @return array
 */
function mod_flippage_get_completion_active_rule_descriptions($cm): array {
    $descriptions = [];
    if (!empty($cm->customdata['customcompletionrules']['completionlastpage'])
            && $cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $descriptions[] = get_string('completiondetail:lastpage', 'flippage');
    }
    return $descriptions;
}

/**
 * Returns files sorted for display.
 *
 * @param context_module $context module context
 * @return stored_file[]
 */
function flippage_get_content_files(context_module $context): array {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_flippage', 'content', 0, 'filename', false);

    return array_values(array_filter($files, static function(stored_file $file): bool {
        return !$file->is_directory();
    }));
}

/**
 * Records an activity view.
 *
 * @param stdClass $flippage instance
 * @param int $userid user id
 * @return stdClass
 */
function flippage_record_access(stdClass $flippage, int $userid): stdClass {
    global $DB;

    $now = time();
    $params = ['flippageid' => $flippage->id, 'userid' => $userid];
    if ($progress = $DB->get_record('flippage_views', $params)) {
        $progress->views++;
        $progress->lastaccess = $now;
        $DB->update_record('flippage_views', $progress);
        return $progress;
    }

    $progress = (object)[
        'flippageid' => $flippage->id,
        'userid' => $userid,
        'views' => 1,
        'currentpage' => 0,
        'totalpages' => 0,
        'completed' => 0,
        'firstaccess' => $now,
        'lastaccess' => $now,
    ];
    $progress->id = $DB->insert_record('flippage_views', $progress);

    return $progress;
}
