# Export Book Chapters to PDF #

The Export Book Chapters Block is a Moodle block plugin that provides a convenient way for users to download book chapters in PDF format directly from their course.

## Key Features:
- **Keyword-Based Filtering (Chapter Prefix)**: Users can specify a keyword (prefix) in the block's configuration. Only book chapters whose titles contain this prefix will be included in the download. This feature allows selective exports of specific chapters, making it easier to filter and focus on relevant content.
- **Full Book Download Option**: If the keyword (Chapter Prefix) field is left empty, the block allows users to download all chapters of all books in the course, offering maximum flexibility.
- **Security Enhancements**: The block respects course access restrictions and visibility settings. Students will not be able to export book chapters if the book is hidden or restricted, ensuring that only authorized users can access specific content.
- **Zip File Export**: The exported chapters are packaged into a ZIP file named `exported_chapters.zip`, ensuring an organized and easy-to-handle download.
- **Pagination Support**: The plugin ensures that the downloaded PDFs maintain the original pagination of the book chapters, providing a seamless reading experience.

This block is especially useful for courses with extensive book content, allowing students to easily access and download the material for offline reading and study. It integrates seamlessly into any Moodle course, providing a user-friendly interface for both teachers and students.

## Chapter Prefix Explained:
The **Chapter Prefix** feature allows course administrators or teachers to filter book chapters by a keyword. For example, if you set the prefix to "Unit 1", only chapters that start with "Unit 1" in the title will be exported. If no prefix is specified, the entire book will be available for export.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/blocks/bookchapter_pdf

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2024 Luca Demicheli Rubio <lucademichelirubio.portfolio@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
