<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcolourpicker(
        'block_campus_slider/backgroundcolor',
        'Couleur de fond du slider',
        'Couleur du fond du bloc slider',
        '#FFFFFF'
    ));

}