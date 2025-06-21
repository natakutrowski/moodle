<?php

require('../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
use core\output\icon_system;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/test_icons.php'));
$PAGE->set_title('Test FontAwesome Icons');
$PAGE->set_heading('Test FontAwesome Icons');



echo $OUTPUT->header();

echo html_writer::tag('h2', 'Icônes FontAwesome disponibles');

//$iconmap = \core\output\icon_system_fontawesome::get_core_icon_map();

$iconmap = icon_system::instance()->get_core_icon_map(); 

echo html_writer::start_tag('ul', ['style' => 'columns: 3; -webkit-columns: 3; -moz-columns: 3; list-style: none; padding-left: 0;']);

foreach ($iconmap as $key => $class) {
    echo html_writer::tag('li',
        html_writer::tag('i', '', ['class' => "fa {$class}", 'style' => 'margin-right: 8px; font-size: 1.2em;']) .
        html_writer::tag('code', $key) .
        ' → ' .
        html_writer::tag('span', $class),
        ['style' => 'margin-bottom: 10px;']
    );
}

echo html_writer::end_tag('ul');

echo $OUTPUT->footer();
