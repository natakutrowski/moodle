<?php
/**
 * Block Content Handler
 */

function edly_block_image_process($img) {
    global $CFG;
    $old_url = 'http://localhost/moodle/edly/';
    $new_url = $CFG->wwwroot.'/';

    if (strpos($img, $old_url) !== false) {
        $img = str_replace($old_url,$new_url,$img);
        return $img;
    }else{
        return $img;
    }
}
