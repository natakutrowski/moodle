<?php
defined('MOODLE_INTERNAL') || die();

function ls_simple_page(string $title, string $html): void {
    global $PAGE, $OUTPUT;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();
    echo html_writer::div($html, 'ls-policy max-w-3xl mx-auto p-3');
    echo $OUTPUT->footer();
}
