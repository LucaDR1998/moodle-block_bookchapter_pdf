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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block block_bookchapter_pdf
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/blocks}
 *
 * @package    block_bookchapter_pdf
 * @copyright  2024 Luca <lucademichelirubio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class block_bookchapter_pdf extends block_base {
    public function init() {
        $this->title = get_string('exportpdf', 'block_bookchapter_pdf');
    }

    public function get_content() {

        global $CFG, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $url = new moodle_url('/blocks/bookchapter_pdf/view.php', ['id' => $this->instance->id, 'courseid' => $COURSE->id]);
        $icon = $OUTPUT->pix_icon('icon', get_string('exportpdf', 'block_bookchapter_pdf'), 'block_bookchapter_pdf', array('class' => 'icon'));
        $link = html_writer::link($url, $icon . get_string('exportpdf', 'block_bookchapter_pdf'));
        $this->content->text = $link;
        $this->content->footer = '';

        return $this->content;
    }

    public function has_add_block_capability($context, $caps) {
        // Se siamo nel contesto di un corso, controlla solo 'addinstance'
        if ($context->contextlevel == CONTEXT_COURSE) {
            return has_any_capability($caps, $context);
        }
        // Altrimenti, non permettere l'aggiunta del blocco
        return false;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }
}
