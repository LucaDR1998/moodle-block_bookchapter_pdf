# Export Book Chapters to PDF #

The Export Book Chapters Block is a Moodle block plugin that provides a convenient way for users to download book chapters in PDF format directly from their course.

Key Features:
Keyword-Based Filtering: Users can specify a keyword in the block's configuration. Only book chapters containing this keyword will be included in the download.
Full Book Download Option: If the keyword field is left empty, the block allows users to download all chapters of all books in the course.
Zip File Export: The exported chapters are packaged into a ZIP file named exported_chapters.zip, ensuring an organized and easy-to-handle download.
Pagination Support: The plugin ensures that the downloaded PDFs maintain the original pagination of the book chapters, providing a seamless reading experience.
This block is especially useful for courses with extensive book content, allowing students to easily access and download the material for offline reading and study. It integrates seamlessly into any Moodle course, providing a user-friendly interface for both teachers and students.

Installation and Configuration:
Installation: Install the plugin through the Moodle plugin installer.
Configuration: Add the block to your course and configure it by specifying a keyword if desired.
Usage: Users can click the download button to get a ZIP file containing the filtered or full book chapters in PDF format.
With the Export Book Chapters Block, managing and distributing course book content has never been easier. Simplify your course material downloads and enhance the learning experience for your students with this essential Moodle plugin.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/exportpdf

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2024 Luca Demicheli Rubio <luca@telehealth.org>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
