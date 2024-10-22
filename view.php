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

use \Mpdf\Mpdf;

/**
 * View of the mod/book chapters in the course
 *
 * @package    block_bookchapter_pdf
 * @copyright  2024 Luca <lucademichelirubio@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
set_time_limit(300);

require_once('../../config.php');
require_once('localib.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/bookchapter_pdf/classes/output/renderer.php');

if (!defined('PDF_MARGIN_LEFT')) {
    define('PDF_MARGIN_LEFT', 15);
}
if (!defined('PDF_MARGIN_TOP')) {
    define('PDF_MARGIN_TOP', 16);
}
if (!defined('PDF_MARGIN_RIGHT')) {
    define('PDF_MARGIN_RIGHT', 15);
}
if (!defined('PDF_MARGIN_BOTTOM')) {
    define('PDF_MARGIN_BOTTOM', 16);
}

$id = required_param('id', PARAM_INT); // block ID
$courseid = required_param('courseid', PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/bookchapter_pdf:view', $context);

$PAGE->set_url(new moodle_url('/blocks/bookchapter_pdf/view.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('exportpdf', 'block_bookchapter_pdf'));
$PAGE->set_heading(get_string('exportpdf', 'block_bookchapter_pdf'));

$renderer = $PAGE->get_renderer('block_bookchapter_pdf');

if (data_submitted()) {

    if (!confirm_sesskey()) {
        throw new moodle_exception('invalidsesskey', 'error');
    }
    // check if there are any selected checkboxes
    $selectedbooks = array_filter((array)data_submitted(), function($key) {
        return strpos($key, 'book_') === 0; // reference to checkbox's prefix name 'book_'
    }, ARRAY_FILTER_USE_KEY);

    if (empty($selectedbooks)) {
        redirect($PAGE->url, get_string('nobookselected', 'block_bookchapter_pdf'), null, \core\output\notification::NOTIFY_WARNING);
        return;
    }
    // Get all chapters grouped by book in the course
    $groupedchapters = process_and_find_chapterskey_for_course($COURSE->id, $DB);

    $tempdir = make_temp_directory('exportpdf');
    $zip = new zipArchive();
    $zipfilename = tempnam($tempdir, 'exportpdf') . '.zip';

    // Create a temporary directory for images within moodledata
    $imgtempdir = make_temp_directory('exportpdf_images');

    if ($zip->open($zipfilename, \ZipArchive::CREATE)) {

        foreach ($groupedchapters as $bookprefix => $book) {

            if (!array_key_exists('book_' . $book['id'], $selectedbooks)) {
                continue; // skip book not selected
            }

            // Export each book and its chapters directly
            $mpdf = new Mpdf([
                'margin_left' => PDF_MARGIN_LEFT,
                'margin_right' => PDF_MARGIN_RIGHT,
                'margin_top' => PDF_MARGIN_TOP,
                'margin_bottom' => PDF_MARGIN_BOTTOM,
                'margin_header' => 0,
                'margin_footer' => 0
            ]);

            $mpdf->SetFooter('|Page {PAGENO} / {nb}|');

            $content = '';
            $clean_html = '';
            $bookid = $book['id'];
            $imageurls = image_url($bookid);
            $filemapping = get_file_mapping_by_filename($bookid);
            $imagepath = '';

            foreach ($book['chapters'] as $chapter) {
                $content = $chapter->content;

                // Process the images in the chapter content
                $content = preg_replace_callback(
                    '/<img\s+([^>]*)src="(https?:\/\/[^"]+|@@PLUGINFILE@@\/([^"]*(?:%20)*[^"]*))"([^>]*)>/i',
                    function ($matches) use ($filemapping, $imgtempdir) {
                        // Check if the URL is external and download the image
                        if (preg_match('/^https?:\/\//', $matches[2])) {
                            $imageurl = $matches[2];                              
                            $imagepath = download_image($imageurl, $imgtempdir);
                            if ($imagepath && file_exists($imagepath) && filesize($imagepath) > 0) {
                                return '<img ' . $matches[1] . 'src="' . $imagepath . '"' . $matches[4] . '>';
                            } else {
                                return '';
                            }
                        } 
                        // Handle local files via @@PLUGINFILE@@
                        $filename = urldecode($matches[3]);
                        if (isset($filemapping[$filename])) {
                            $newsrc = $filemapping[$filename][0];
                            if (file_exists($newsrc) && filesize($newsrc) > 0) {
                                return '<img ' . $matches[1] . 'src="' . $newsrc . '"' . $matches[4] . '>';
                            } else {
                                return '';
                            }
                        }
                    },
                    $content
                );

                $content = '<h1>' . $chapter->title . '</h1>' . $content;
                $cleanHtml = clean_html_for_pdf_export($content);

                $mpdf->AddPage();
                $mpdf->WriteHTML($cleanHtml);
            }

            if (count($mpdf->pages) > 0) {
                $pdfcontent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
                $zip->addFromString(clean_filename($bookprefix) . '.pdf', $pdfcontent);
            }
        }
        
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="exported_chapters.zip"');
        readfile($zipfilename);
        unlink($zipfilename);
        exit;
    }

    // Clear the temporary image folder in moodledata
    $files = glob($imgtempdir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }

} else {
    // Get all chapters grouped by book in the course
    $groupedchapters = process_and_find_chapterskey_for_course($COURSE->id, $DB);
    $booksdata = [];
    
    foreach ($groupedchapters as $bookprefix => $book) {
        // Add header for each book
        $booksdata[] = [
            'type' => 'checkbox',
            'name' => 'book_' . $book['id'],
            'label' => format_string($bookprefix),
            'checked' => '',
            'isGroupHeader' => true,
        ];
    }
    
    $backtocourseurl = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
    $data = [
        'actionurl' => $PAGE->url->out(false),
        'fields' => $booksdata,
        'toggleButtonText' => get_string('selectall', 'block_bookchapter_pdf'),
        'backtocourseurl' => $backtocourseurl->out(false),
        'sesskey' => sesskey(),
    ];
    
    echo $OUTPUT->header();
    echo $renderer->render_export_form((object)$data);
    echo $OUTPUT->footer();
}

