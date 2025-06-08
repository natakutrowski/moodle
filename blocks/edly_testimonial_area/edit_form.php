<?php

class block_edly_testimonial_area_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;

        $style = 1;
        if(isset($this->block->config->style)){
            $style = $this->block->config->style;
        }

        $sliderNumber = 4;
        if(isset($this->block->config->sliderNumber)){
            $sliderNumber = $this->block->config->sliderNumber;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->addElement('select', 'config_style', get_string('config_style', 'theme_edly'), array(1 => 'Style 1', 2 => 'Style 2'));
        $mform->setDefault('config_style', 1);
        
        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'review-area bg-F9F6F2 ptb-100');
        $mform->setType('config_class', PARAM_RAW);

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setDefault('config_top_title', 'Our Review');
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'What our students have <span>to say</span>');
        $mform->setType('config_title', PARAM_RAW);

        // Content
        $mform->addElement('text', 'config_body', get_string('config_body', 'theme_edly'));
        $mform->setDefault('config_body', 'Career &amp; we will guide you through that. <a href="#">Register Free Now!</a>');
        $mform->setType('config_body', PARAM_RAW);

        // Image URL
        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 

        // Section Shape Image 2
        $mform->addElement('text', 'config_shape_img', 'Shape Image 1 URL');
        $mform->setType('config_shape_img', PARAM_TEXT);

        // Section Shape Image 2
        $mform->addElement('text', 'config_shape_img2', 'Shape Image 2 URL');
        $mform->setType('config_shape_img2', PARAM_TEXT);

        $sliderrange = array(
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

        $mform->addElement('select', 'config_sliderNumber', get_string('config_items', 'theme_edly'), $sliderrange);
        $mform->setDefault('config_sliderNumber', 4);

        for($i = 1; $i <= $sliderNumber; $i++) {
            $mform->addElement('header', 'config_edly_item' . $i , get_string('config_item', 'theme_edly') . $i);

            $mform->addElement('text', 'config_img' . $i, 'Slider Image' . $i);
            $mform->setType('config_img' . $i, PARAM_TEXT);

            // Title
            $mform->addElement('text', 'config_slider_title' . $i, get_string('config_title', 'theme_edly', $i));
            $mform->setDefault('config_slider_title' . $i, 'Great Quality Trainer!');
            $mform->setType('config_slider_title' . $i, PARAM_TEXT);

            // Content
            $mform->addElement('text', 'config_slider_content' . $i, get_string('config_content', 'theme_edly', $i));
            $mform->setDefault('config_slider_content' . $i, 'Break into a new field like some information technology or data science. No prior experience support necessary.');
            $mform->setType('config_slider_content' . $i, PARAM_TEXT);

            // Name
            $mform->addElement('text', 'config_slider_name' . $i, ' Name', $i);
            $mform->setDefault('config_slider_name', 'James katliv');
            $mform->setType('config_slider_name' . $i, PARAM_TEXT);

            // Designation
            $mform->addElement('text', 'config_slider_designation' . $i, ' Designation', $i);
            $mform->setDefault('config_slider_designation', 'Python Expert');
            $mform->setType('config_slider_designation' . $i, PARAM_TEXT);
        }
    }
}
