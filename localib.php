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
 * File localib
 *
 * @package    block_bookchapter_pdf
 * @copyright  2024 Luca <lucademichelirubio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/lib/filestorage/file_storage.php');
require_once($CFG->dirroot . '/lib/filelib.php');
require_once($CFG->libdir.'/pdflib.php');

/**
 * Processes and finds chapters key for a given course.
 *
 * This function filters books in a course based on their title starting with the configured chapter prefix.
 * If the prefix is empty, it retrieves all books in the course. It retrieves the chapters for each selected book
 * and groups them for export or display.
 *
 * @param int $courseid The ID of the course to process.
 * @param moodle_database $DB The Moodle database object for executing queries.
 * @return array An associative array where each key is the book name and each value is an array containing the book ID and its chapters.
 *               Each chapter includes the ID, title, content, and subchapter status.
 */
function process_and_find_chapterskey_for_course($courseid, moodle_database $DB) {

    $chapterprefix = get_config('block_bookchapter_pdf', 'chapterprefix');
    $grouped = [];
    $params = array('course' => $courseid);

    // Builds the SQL query based on the presence of the prefix
    if (!empty($chapterprefix)) {
        $like = $DB->sql_like('b.name', ':prefix', false, false);
        $params['prefix'] = "%{$chapterprefix}%";
        $sql = "b.course = :course AND $like AND cm.visible = 1";
    } else {
        $sql = "b.course = :course AND cm.visible = 1";
    }
    // Join the book and course_modules tables to get only visible books
    $sql = "SELECT b.* FROM {book} b 
            JOIN {course_modules} cm ON cm.instance = b.id
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name = 'book' AND $sql";
    // Execute the query selecting only visible books based on the course and the name prefix (if present)
    $books = $DB->get_records_sql($sql, $params);

    foreach ($books as $book) {
        // Retrieve only visible chapters (hidden = 0)
        $chapters = $DB->get_records_select('book_chapters', "bookid = ? AND hidden = 0", 
            [$book->id], 'pagenum ASC', 'id, title, content, subchapter');
        // If there are no visible chapters, skip the book
        if (empty($chapters)) {
            continue;
        }
        // Add the book and its chapters to the array for export/display
        $grouped[$book->name] = [
            'id' => $book->id,
            'chapters' => array_values($chapters) // Convert to a numerical array to facilitate export/display
        ];
    }
    return $grouped;
}

/**
 * Cleans HTML content for PDF export by removing specific tags and links with certain classes.
 *
 * This function processes HTML content to remove unwanted tags and specific links,
 * making the content suitable for PDF export.
 *
 * @param string $html The HTML content to be cleaned.
 * @return string The cleaned HTML content, ready for PDF export.
 */
function clean_html_for_pdf_export($html) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // Remove specific tags
    $tagstoremove = ['script', 'form', 'nav', 'section', 'aside', 'footer', 'svg', 'a', 'figure'];
    foreach ($tagstoremove as $tag) {
        $elems = $xpath->query("//{$tag}");
        foreach ($elems as $elem) {
            $elem->parentNode->removeChild($elem);
        }
    }

    // Add specific removal for links with specific classes
    $specificlinkstoremove = $xpath->query("//a[contains(@class, 'btn btn-secondary')]");
    foreach ($specificlinkstoremove as $link) {
        $link->parentNode->removeChild($link);
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ? $dom->saveHTML($body) : '';
}

/**
 * Retrieves image URLs associated with a book's chapters.
 *
 * This function gets the URLs of all images associated with the chapters of a given book,
 * organized by chapter ID.
 *
 * @param int $bookid The ID of the book.
 * @return array An associative array where keys are chapter IDs and values are arrays of image URLs.
 */
function image_url($bookid) {
    global $DB, $CFG;

    $fs = get_file_storage();

    // get context module for the book
    $cm = get_coursemodule_from_instance('book', $bookid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Get all files associated with the context and the file area
    $files = $fs->get_area_files($context->id, 'mod_book', 'chapter', false, 'itemid, filepath, filename', false);

    $imageurls = [];
    foreach ($files as $file) {
        if ($file->get_filename() !== '.') {
            $itemid = $file->get_itemid(); // chapter ID
            $url = $CFG->dataroot . '/filedir/' . substr($file->get_contenthash(), 0, 2) . '/' 
            . substr($file->get_contenthash(), 2, 2) . '/' . $file->get_contenthash();
            // Organize the URLs by itemid (chapter ID)
            if (!isset($imageurls[$itemid])) {
                $imageurls[$itemid] = [];
            }
            $imageurls[$itemid][] = $url;
        }
    }
    return $imageurls;
}

/**
 * Maps files by their filenames for a given book.
 *
 * This function retrieves all files associated with a book's chapters and maps them
 * by their filenames to their physical paths on the server.
 *
 * @param int $bookid The ID of the book.
 * @return array An associative array where keys are filenames and values are arrays of physical file paths.
 */
function get_file_mapping_by_filename($bookid) {
    global $CFG, $DB, $USER;

    // get context module for the book
    $cm = get_coursemodule_from_instance('book', $bookid, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_book', 'chapter', false, 'itemid, filepath, filename', false);

    $filemapping = [];

    foreach ($files as $file) {
        // Ignore directories
        if ($file->is_directory()) {
            continue;
        }
        $filename = $file->get_filename();
        $hash = $file->get_contenthash();
        // Build the physical path based on the hash, as done in image_url
        $path = $CFG->dataroot . '/filedir/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
        // Add the mapping; consider that there might be files with the same name in different contexts
        if (!array_key_exists($filename, $filemapping)) {
            $filemapping[$filename] = [];
        }
        $filemapping[$filename][] = $path;
    }
    return $filemapping;
}


/**
 * Downloads an image from a URL to a specified temporary directory.
 *
 * This function downloads an image from the given URL and saves it to the specified temporary directory.
 * It uses file_get_contents and file_put_contents functions for the download process.
 *
 * @param string $url The URL of the image to be downloaded.
 * @param string $tempdir The temporary directory where the image will be saved.
 * @return string|false The file path of the downloaded image if successful, false otherwise.
 */
function download_image($url, $tempdir) {
    $filename = basename($url);
    $filepath = $tempdir . '/' . $filename;
    // Use file_get_contents and file_put_contents to download the image
    // First check if allow_url_fopen is enabled
    if (ini_get('allow_url_fopen')) {
        $imagedata = file_get_contents($url);
        if ($imagedata !== false) {
            file_put_contents($filepath, $imagedata);
            return $filepath;
        }
    }
    return false;
}
