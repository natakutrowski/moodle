<?php

class block_edly_features_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;

        $features_number = 4;
        if(isset($this->block->config->features_number)){
            $features_number = $this->block->config->features_number;
        }

        $style = 1;
        if(isset($this->block->config->style)){
            $style = $this->block->config->style;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('select', 'config_style', get_string('config_style', 'theme_edly'), array(1 => 'Style 1', 2 => 'Style 2', 3 => 'Style 3'));
        $mform->setDefault('config_style', 1);

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setType('config_title', PARAM_RAW);

        $featuresrange = array(
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
            6 => '6',
            7 => '7',
            8 => '8',
            9 => '9',
            10 => '10',
            11 => '11',
            12 => '12',
            13 => '13',
            14 => '14',
            15 => '15',
            16 => '16',
            17 => '17',
            18 => '18',
            19 => '19',
            20 => '20',
            21 => '21',
            22 => '22',
            23 => '23',
            24 => '24',
            25 => '25',
            26 => '26',
            27 => '27',
            28 => '28',
            29 => '29',
            30 => '30',
        );

        $mform->addElement('select', 'config_features_number', get_string('config_items', 'theme_edly'), $featuresrange);
        $mform->setDefault('config_features_number', 4);

        for($i = 1; $i <= $features_number; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') . $i);

            // Image URL
            $mform->addElement('static', 'config_image_doc' . $i, '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 

            $mform->addElement('text', 'config_bg_img' . $i, 'Feature Icon Background Shape' . $i);
            if($i <= 4):
                $mform->setDefault('config_bg_img' . $i, $CFG->wwwroot.'/theme/edly/pix/features/features'.$i.'.png');
            else:
                $mform->setDefault('config_bg_img' . $i, $CFG->wwwroot.'/theme/edly/pix/features/features1.png');
            endif;
            $mform->setType('config_bg_img' . $i, PARAM_TEXT);

            $mform->addElement('text', 'config_img' . $i, 'Feature Icon' . $i);
            if($i <= 4):
                $mform->setDefault('config_img' . $i, $CFG->wwwroot.'/theme/edly/pix/features/icon'.$i.'.svg');
            else:
                $mform->setDefault('config_img' . $i, $CFG->wwwroot.'/theme/edly/pix/features/icon1.svg');
            endif;
            $mform->setType('config_img' . $i, PARAM_TEXT);

            // Title
            $mform->addElement('text', 'config_features_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_features_title' . $i, 'Earn certificates and degrees');
            $mform->setType('config_features_title' . $i, PARAM_TEXT);

            // Card Content
            $mform->addElement('text', 'config_features_content' . $i, get_string('config_content', 'theme_edly', $i));
            $mform->setDefault('config_features_content' . $i, 'Break into a new field like format technology or data science get started.');
            $mform->setType('config_features_content' . $i, PARAM_TEXT);

            // Card link text
            $mform->addElement('text', 'config_features_link_text' . $i, 'Link Title '.$i.'');
            $mform->setDefault('config_features_link_text' . $i, 'Start Now');
            $mform->setType('config_features_link_text' . $i, PARAM_TEXT);

            // Card Button Link
            $mform->addElement('text', 'config_features_button_link' . $i, get_string('config_button_link', 'theme_edly', $i));
            $mform->setDefault('config_features_button_link', $CFG->wwwroot . '/course');
            $mform->setType('config_features_button_link' . $i, PARAM_TEXT);
                
        }
    }
}
