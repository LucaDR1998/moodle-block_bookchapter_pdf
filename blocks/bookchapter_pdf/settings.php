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
 * Settings for block_bookchapter_pdf plugin
 *
 * @package    block_bookchapter_pdf
 * @copyright  2024 Luca <lucademichelirubio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    // Create a new settings page for the block
    $settings = new admin_settingpage('block_bookchapter_pdf_settings', get_string('pluginname', 'block_bookchapter_pdf'));

    // Add the settings page to the block settings category
    // $ADMIN->add('blocksettings', $settings);

    if ($ADMIN->fulltree) {
        // Add a text field setting for the chapter prefix
        $settings->add(new admin_setting_configtext(
            'block_bookchapter_pdf/chapterprefix',
            get_string('chapterprefix', 'block_bookchapter_pdf'),
            get_string('chapterprefix_desc', 'block_bookchapter_pdf'),
            '', // Default value
            PARAM_TEXT
        ));
    }
}
