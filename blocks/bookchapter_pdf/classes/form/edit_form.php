<?php
// This file is part of Moodle - https://moodle.org/
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
 * Form for editing bookchapter_pdf block instances.
 *
 * @package    block_bookchapter_pdf
 * @copyright  2024 Luca <lucademichelirubio@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/formslib.php");

class block_bookchapter_pdf_edit_form extends moodleform {
    public function definition() {
        global $DB, $COURSE;

        $mform = $this->_form;

        // Assumi che $id e $courseid siano passati al form tramite il costruttore.
        $id = $this->_customdata['id'];
        $courseid = $this->_customdata['courseid'];

        // Aggiungi i parametri come elementi nascosti
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT); // Assicurati che l'id sia trattato come un intero

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT); // Assicurati che courseid sia trattato come un intero

        $mform->addElement('header', 'configheader', get_string('selectbookchapters', 'block_bookchapter_pdf'));

        $books = $DB->get_records('book', ['course' => $COURSE->id]);
        foreach ($books as $book) {
            $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id]);
            foreach ($chapters as $chapter) {
                $checkboxname = 'chapter_' . $chapter->id;
                $mform->addElement('advcheckbox', $checkboxname, $book->name, $chapter->title);
                $mform->setDefault($checkboxname, 0); // Default non selezionato
            }
        }

        $mform->addElement('button', 'selectall', get_string('selectall', 'block_bookchapter_pdf'), array('type' => 'button', 'onclick' => 'toggleCheckboxes(this)'));
        $mform->addElement('html', '<script type="text/javascript">
            function toggleCheckboxes(btn) {
                var checkboxes = document.querySelectorAll("input[type=\'checkbox\']");
                var allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = !allChecked;
                });
                btn.textContent = allChecked ? "'.get_string('selectall', 'block_bookchapter_pdf').'" : "'.get_string('deselectall', 'block_bookchapter_pdf').'";
            }
        </script>');
        $mform->addElement('button', 'backtocourse', get_string('backtocourse', 'block_bookchapter_pdf'), array('type' => 'button', 'onclick' => 'window.location.href=\'' . new moodle_url('/course/view.php', array('id' => $COURSE->id)) . '\''));

        $this->add_action_buttons(false, get_string('exportselected', 'block_bookchapter_pdf'));
    }

    public function validation($data, $files) {
        return array();
    }
}
