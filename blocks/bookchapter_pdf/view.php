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

$id = required_param('id', PARAM_INT); // ID del blocco
$courseid = required_param('courseid', PARAM_INT); // ID del corso

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('block/bookchapter_pdf:view', $context);

$PAGE->set_url(new moodle_url('/blocks/bookchapter_pdf/view.php', ['id' => $id, 'courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('exportpdf', 'block_bookchapter_pdf'));
$PAGE->set_heading(get_string('exportpdf', 'block_bookchapter_pdf'));

$renderer = $PAGE->get_renderer('block_bookchapter_pdf');

if (data_submitted()) {

    // Ottieni tutti i capitoli selezionati e i loro gruppi basati sui prefissi "Lesson #"
    $groupedchapters = process_and_find_chapterskey_for_course($COURSE->id, $DB);
    $selectedlessons = array_filter((array)data_submitted(), function($key) {
        return strpos($key, 'lesson_') === 0;
    }, ARRAY_FILTER_USE_KEY);

    $tempdir = make_temp_directory('exportpdf');
    $zip = new zipArchive();
    $zipfilename = tempnam($tempdir, 'exportpdf') . '.zip';

    $imgtemp = __DIR__ . '/tempimgs';

    if ($zip->open($zipfilename, \ZipArchive::CREATE)) {

        foreach ($groupedchapters as $lessonprefix => $lesson) {

            if (array_key_exists('lesson_' . $lesson['id'], $selectedlessons)) {

                $mpdf = new \Mpdf\Mpdf([
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
                $bookid = $lesson['id'];
                $imageurls = image_url($bookid);
                $filemapping = get_file_mapping_by_filename($bookid);
                $imagepath = '';

// // DEBUG
//                 $imageurlsContent = "<h2>Image URLs</h2><pre>" . htmlspecialchars(print_r($imageurls, true)) . "</pre>";
//                 // Preparazione del contenuto di $filemapping per l'HTML
//                 $filemappingContent = "<h2>File Mapping</h2><pre>" . htmlspecialchars(print_r($filemapping, true)) . "</pre>";
//                 // Concatenazione del contenuto in una singola stringa HTML
//                 $htmlContent = $imageurlsContent . $filemappingContent;
//                 // Percorso del file HTML a cui fare append
//                 $filePath = 'output.html';
//                 // Append del contenuto al file, creando il file se non esiste e acquisendo un lock esclusivo durante la scrittura
//                 file_put_contents($filePath, $htmlContent, FILE_APPEND | LOCK_EX);

                foreach ($lesson['chapters'] as $chapter) {

                    $content = $chapter->content;
        
                    $content = preg_replace_callback(
                        // '/<img\s+([^>]*)src="(https?:\/\/[^"]+|@@PLUGINFILE@@\/([^"]+))"([^>]*)>/i',
                        '/<img\s+([^>]*)src="(https?:\/\/[^"]+|@@PLUGINFILE@@\/([^"]*(?:%20)*[^"]*))"([^>]*)>/i',
                        function ($matches) use ($filemapping, $imgtemp) {
                            // Verifica se l'URL è esterno
                            if (preg_match('/^https?:\/\//', $matches[2])) {
                                $imageurl = $matches[2];                              
                                // Verifica se l'URL è esterno e scarica l'immagine
                                $imagepath = download_image($imageurl, $imgtemp);
                                // Aggiungi un controllo per verificare se l'immagine esiste e è valida
                                if ($imagepath && file_exists($imagepath) && filesize($imagepath) > 0) {
                                    // Se l'immagine è stata scaricata, usa il percorso locale
                                    return '<img ' . $matches[1] . 'src="' . $imagepath . '"' . $matches[4] . '>';
                                } else {
                                    return '';
                                }
                            } 
                            // else {
                                // Gestione dei file locali tramite @@PLUGINFILE@@
                                $filename = urldecode($matches[3]); // Ottieni il nome del file dall'URL interno
                                // $filename = rawurlencode($filename);
                                if (isset($filemapping[$filename])) {
                                    $newsrc = $filemapping[$filename][0]; // Prendi il primo percorso disponibile
                                    // Ricostruisce il tag <img> mantenendo tutti gli attributi originali
                                    // e sostituendo solo l'attributo src con il nuovo percorso
                                    if (file_exists($newsrc) && filesize($newsrc) > 0) {
                                        return '<img ' . $matches[1] . 'src="' . $newsrc . '"' . $matches[4] . '>';
                                    } else {
                                        return '';
                                    }
                                }
                            // }
                            // Se non viene trovata alcuna corrispondenza o il download fallisce, mantiene il tag <img> originale
                            // return $matches[0];
                        },
                        $content
                    );
                    $content = '<h1>' . $chapter->title . '</h1>' . $content;
                    // file_put_contents('content.html', $chapter->content);
                    $cleanHtml = clean_html_for_pdf_export($content);
                    // file_put_contents('cleanHtml.html', $cleanHtml);

                    $mpdf->AddPage();
                    $mpdf->WriteHTML($cleanHtml);
                }

                if (count($mpdf->pages) > 0) {
                    $pdfcontent = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
                    $zip->addFromString(clean_filename($lessonprefix) . '.pdf', $pdfcontent);

                }
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="exported_chapters.zip"');
        readfile($zipfilename);
        unlink($zipfilename);
        exit;
    }
    // Una volta completato il processo di aggiunta al ZIP, svuota la cartella tempimgs
    $files = glob($imgtemp . '/*'); // Ottiene tutti i file nella directory delle immagini temporanee
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // Elimina il file
        }
    }
        
} else {
    // Ottieni i capitoli raggruppati per prefissi "Lesson #" dal corso corrente
    $groupedchapters = process_and_find_chapterskey_for_course($COURSE->id, $DB);
    $booksdata = [];
    
    foreach ($groupedchapters as $lessonprefix => $lesson) {
        // Aggiungi un'intestazione di gruppo per ogni lezione, ma solo una volta per lezione
        $booksdata[] = [
            'type' => 'checkbox',
            'name' => 'lesson_' . $lesson['id'],
            'label' => format_string($lessonprefix),
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
