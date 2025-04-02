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
 * Serving image for the speechace question type plugin.
 *
 * @package    qtype
 * @subpackage speechace
 * @copyright  2014 SpeechAce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/speechacelib.php');

// Check login and get context.
require_login(null, false, null, false, true);

$expert = required_param('expert', PARAM_TEXT);
$expertid = required_param('expertid', PARAM_TEXT);

try {
    $expertimageURL = qtype_speechace_form_expert_image_url($expert, $expertid);
    $filename = $expert . "-" + $expertid + ".jpg";
    $filetype = "image/jpeg";
    qtype_speechace_serve_content($expertimageURL, $filename, $filetype);
} catch (qtype_speechace_exception $ex) {
    header('HTTP/1.1 ' . $ex->getHttpErrorStatusMessage());
}

function qtype_speechace_serve_content($url, $filename, $contenttype)
{       
    if ($url) {
        $content = qtype_speechace_get_url_contents($url);
    }
    if (!$content) {
        list($url, $contenttype, $filename) = qtype_speechace_get_fallback_image_info();
        $content = file_get_contents($url);
    }
    if ($content) {
        $file_size = strlen($content);
        header('Content-Type: '. $contenttype);
        header('Content-Length:' . $file_size);
        header("Content-Disposition:attachment; filename=\"". $filename ."\"");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache');
        print $content;
    } else {
        $status_error_code = 404;
        $status_error_message = "Not Found";
        header("HTTP/1.1 {$status_error_code} {$status_error_message}");
    }
    
    return;
}
