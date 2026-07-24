<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Flip page activity form.
 *
 * @package    mod_flippage
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/flippage/lib.php');

/**
 * Activity settings form.
 */
class mod_flippage_mod_form extends moodleform_mod {
    /**
     * Defines the form.
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', !empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEANHTML);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'contenthdr', get_string('content'));
        $options = flippage_get_filemanager_options($this->context);
        $mform->addElement('filemanager', 'contentfiles', get_string('contentfile', 'flippage'), null, $options);
        $mform->addHelpButton('contentfiles', 'contentfile', 'flippage');
        $mform->addRule('contentfiles', null, 'required', null, 'client');

        $mform->addElement('header', 'accesshdr', get_string('availability'));
        $mform->addElement('text', 'maxviews', get_string('maxviews', 'flippage'), ['size' => 6]);
        $mform->setType('maxviews', PARAM_INT);
        $mform->setDefault('maxviews', 0);
        $mform->addHelpButton('maxviews', 'maxviews', 'flippage');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();

        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Prepares draft files for editing.
     *
     * @param array $defaultvalues form defaults
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!empty($this->current->instance)) {
            $draftitemid = file_get_submitted_draft_itemid('contentfiles');
            file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_flippage',
                'content',
                0,
                flippage_get_filemanager_options($this->context)
            );
            $defaultvalues['contentfiles'] = $draftitemid;
        }
    }

    /**
     * Adds custom completion rules.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $element = 'completionlastpage' . $suffix;
        $mform->addElement('checkbox', $element, '', get_string('completionlastpage', 'flippage'));
        return [$element];
    }

    /**
     * Checks whether custom completion is enabled.
     *
     * @param array $data submitted data
     * @return bool
     */
    public function completion_rule_enabled($data): bool {
        $suffix = $this->get_suffix();
        return !empty($data['completionlastpage' . $suffix]);
    }

    /**
     * Normalises completion values.
     *
     * @param stdClass $data form data
     */
    public function data_postprocessing($data): void {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix};
            $automatic = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (!$automatic || empty($data->{'completionlastpage' . $suffix})) {
                $data->{'completionlastpage' . $suffix} = 0;
            }
        }
    }

    /**
     * Validates uploaded source files.
     *
     * @param array $data submitted data
     * @param array $files submitted files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['contentfiles'])) {
            return $errors;
        }

        $draftfiles = file_get_drafarea_files($data['contentfiles'], '/');
        $pdfcount = 0;
        $unsupported = 0;
        foreach ($draftfiles->list as $file) {
            if (!empty($file->isref)) {
                continue;
            }
            $mimetype = $file->mimetype ?? '';
            $filename = $file->filename ?? '';
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($mimetype === 'application/pdf' || $extension === 'pdf') {
                $pdfcount++;
            } else {
                $unsupported++;
            }
        }

        if ($pdfcount < 1) {
            $errors['contentfiles'] = get_string('filenotfound', 'flippage');
        } else if ($pdfcount > 1 || $unsupported > 0) {
            $errors['contentfiles'] = get_string('uploadonepdfonly', 'flippage');
        }

        return $errors;
    }
}
