<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace mod_flippage\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for Flip page.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about stored user data.
     *
     * @param collection $collection metadata collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('flippage_views', [
            'userid' => 'privacy:metadata:flippage_views:userid',
            'views' => 'privacy:metadata:flippage_views:views',
            'currentpage' => 'privacy:metadata:flippage_views:currentpage',
            'totalpages' => 'privacy:metadata:flippage_views:totalpages',
            'completed' => 'privacy:metadata:flippage_views:completed',
            'firstaccess' => 'privacy:metadata:flippage_views:firstaccess',
            'lastaccess' => 'privacy:metadata:flippage_views:lastaccess',
        ], 'privacy:metadata:flippage_views');

        return $collection;
    }

    /**
     * Gets the contexts that contain data for a user.
     *
     * @param int $userid user id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {flippage_views} fv
                  JOIN {flippage} f ON f.id = fv.flippageid
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE fv.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'modname' => 'flippage',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Gets users with data in a context.
     *
     * @param userlist $userlist userlist object
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT fv.userid
                  FROM {flippage_views} fv
                  JOIN {flippage} f ON f.id = fv.flippageid
                  JOIN {modules} m ON m.name = :modname
                  JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, [
            'modname' => 'flippage',
            'cmid' => $context->instanceid,
        ]);
    }

    /**
     * Exports user data for approved contexts.
     *
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $sql = "SELECT fv.*
                      FROM {flippage_views} fv
                      JOIN {flippage} f ON f.id = fv.flippageid
                      JOIN {modules} m ON m.name = :modname
                      JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                     WHERE cm.id = :cmid
                       AND fv.userid = :userid";
            $record = $DB->get_record_sql($sql, [
                'modname' => 'flippage',
                'cmid' => $context->instanceid,
                'userid' => $user->id,
            ]);

            if (!$record) {
                continue;
            }

            writer::with_context($context)->export_data([], (object)[
                'views' => (int)$record->views,
                'currentpage' => (int)$record->currentpage,
                'totalpages' => (int)$record->totalpages,
                'completed' => transform::yesno((bool)$record->completed),
                'firstaccess' => transform::datetime($record->firstaccess),
                'lastaccess' => transform::datetime($record->lastaccess),
            ]);
        }
    }

    /**
     * Deletes all user data in a context.
     *
     * @param \context $context context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "flippageid IN (
                    SELECT f.id
                      FROM {flippage} f
                      JOIN {modules} m ON m.name = :modname
                      JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                     WHERE cm.id = :cmid
                )";
        $DB->delete_records_select('flippage_views', $sql, [
            'modname' => 'flippage',
            'cmid' => $context->instanceid,
        ]);
    }

    /**
     * Deletes user data for approved contexts.
     *
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $sql = "userid = :userid
                    AND flippageid IN (
                        SELECT f.id
                          FROM {flippage} f
                          JOIN {modules} m ON m.name = :modname
                          JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                         WHERE cm.id = :cmid
                    )";
            $DB->delete_records_select('flippage_views', $sql, [
                'userid' => $user->id,
                'modname' => 'flippage',
                'cmid' => $context->instanceid,
            ]);
        }
    }

    /**
     * Deletes data for approved users in a context.
     *
     * @param approved_userlist $userlist approved users
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        list($usersql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['modname'] = 'flippage';
        $params['cmid'] = $context->instanceid;

        $sql = "userid {$usersql}
                AND flippageid IN (
                    SELECT f.id
                      FROM {flippage} f
                      JOIN {modules} m ON m.name = :modname
                      JOIN {course_modules} cm ON cm.instance = f.id AND cm.module = m.id
                     WHERE cm.id = :cmid
                )";
        $DB->delete_records_select('flippage_views', $sql, $params);
    }
}
