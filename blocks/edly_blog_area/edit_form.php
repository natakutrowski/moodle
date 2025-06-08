<?php

class block_edly_blog_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('select', 'config_style', get_string('config_style', 'theme_edly'), array(1 => 'Style 1', 2 => 'Style 2'));

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setDefault('config_top_title', 'Top Articles');
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Want To <span>Learn</span> More? Read Blog');
        $mform->setType('config_title', PARAM_RAW);

        // By text
        $mform->addElement('text', 'config_by_title', 'Post By Title');
        $mform->setDefault('config_by_title', 'By');
        $mform->setType('config_by_title', PARAM_RAW);

        // Button Text
        $mform->addElement('text', 'config_button_text', 'Button Text');
        $mform->setDefault('config_button_text', 'View All');
        $mform->setType('config_button_text', PARAM_RAW);

        // Button Link
        $mform->addElement('text', 'config_button_link', 'Button Text');
        $mform->setDefault('config_button_link', $CFG->wwwroot . '/blog');
        $mform->setType('config_button_link', PARAM_RAW);

        // Shape Image
        $mform->addElement('text', 'config_shape_image', 'Shape Image URL');
        $mform->setType('config_shape_image', PARAM_RAW);


        if (!empty($this->block->config) && is_object($this->block->config)) {
            $data = $this->block->config;
        } else {
            $data = new stdClass();
            $data->slidesnumber = 0;
        }

        $searchareas = \core_search\manager::get_search_areas_list(true);
        $areanames = array();
        foreach ($searchareas as $areaid => $searcharea) {
            $areanames[$areaid] = $searcharea->get_visible_name();
        }

        $bloglisting = new blog_listing();

        $entries = $bloglisting->get_entries();
        $entrieslist = array();

        foreach ($entries as $entryid => $entry) {
          $entrieslist[$entry->id] = $entry->subject;
        }

        $options = array(
            'multiple' => true,
        );
        $mform->addElement('autocomplete', 'config_posts', get_string('posts'), $entrieslist, $options);
    }
}
