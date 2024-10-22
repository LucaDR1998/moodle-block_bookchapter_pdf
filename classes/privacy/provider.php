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

namespace block_bookchapter_pdf\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for block_bookchapter_pdf.
 *
 * This plugin does not store any personal user data but accesses data from the mod_book component.
 */
class provider implements \core_privacy\local\metadata\provider {

    /**
     * Returns metadata about the data that this plugin stores or accesses.
     *
     * @param \core_privacy\local\metadata\collection $collection The collection to add items to.
     * @return \core_privacy\local\metadata\collection The updated collection.
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection) : \core_privacy\local\metadata\collection {
        // Indicate that the plugin accesses data from the mod_book component.
        $collection->link_subsystem('mod_book', 'privacy:metadata:mod_book');
        return $collection;
    }
}
