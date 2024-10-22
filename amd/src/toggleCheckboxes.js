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
 * toggleCheckboxes
 *
 * @module     block_bookchapter_pdf/toggleCheckboxes
 * @copyright  2024 Luca Demicheli Rubio <luca.demicheli@babelgroup.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    function toggleCheckboxes(button) {
        var checkboxes = document.querySelectorAll('input[type="checkbox"]');

        var allCheckedBeforeClick = Array.from(checkboxes).every(checkbox => checkbox.checked);
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = !allCheckedBeforeClick;
        });

        var allCheckedAfterClick = Array.from(checkboxes).every(checkbox => checkbox.checked);
        button.textContent = allCheckedAfterClick ? 'Deselect all' : 'Select all';
    }

    return {
        toggleCheckboxes: toggleCheckboxes
    };
});
