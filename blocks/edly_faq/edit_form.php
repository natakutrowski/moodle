<?php

class block_edly_faq_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        $tabNumber = 2;
        if(isset($this->block->config->tabNumber)){
            $tabNumber = $this->block->config->tabNumber;
        }

        $faqnumber = 3;
        if(isset($this->block->config->faqnumber)){
            $faqnumber = $this->block->config->faqnumber;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
        $faqrange = array(
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

        $mform->addElement('select', 'config_tabNumber', 'Tab Number', $faqrange);
        $mform->setDefault('config_tabNumber', 2);

        for($i = 1; $i <= $tabNumber; $i++) {
            $mform->addElement('header', 'config_edly_tab_item' . $i , 'Tab Item ' . $i);

            $select = $mform->addElement('select', 'config_icon' . $i, get_string('config_icon', 'theme_edly'), $edlyFontList, array('class'=>'edly_icon_class'));
            $mform->setType('config_icon' . $i, PARAM_TEXT);

            $mform->addElement('text', 'config_tab_title' . $i, 'Tab Title' . $i);
            $mform->setDefault('config_tab_title' . $i, 'Getting Started');
            $mform->setType('config_tab_title' . $i, PARAM_TEXT);
        
            $mform->addElement('textarea', 'config_tab_content' . $i, 'Faq Content' . $i, 'wrap="virtual" rows="10" cols="50"');
            $mform->setDefault('config_tab_content' . $i, '
            <li class="accordion-item">
                <a class="accordion-title active" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Do We Create Course Content?
                </a>
                <div class="accordion-content show">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>
            <li class="accordion-item">
                <a class="accordion-title" href="javascript:void(0)">
                    <i class="ri-arrow-down-s-line"></i>
                    How Can I Manage My Account?
                </a>
                <div class="accordion-content">
                    <p>Break into a new field like some information technology or data science. No prior experience support necessary. Break into a new field like some information technology or data science. No prior experience support necessary.</p>
                </div>
            </li>');
            
            $mform->setType('config_tab_content' . $i, PARAM_RAW);
        }
    }
}
