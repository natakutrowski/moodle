<?php 
class block_campus_slider_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        $mform->addElement('header', 'configheader', 'Configuration des cards');

        for ($i = 1; $i <= 10; $i++) {

            $mform->addElement('header', 'card'.$i, 'Card '.$i);

            $mform->addElement(
                'text',
                'config_card'.$i.'_image_url',
                'Image (URL relative)'
            );
            $mform->setType('config_card'.$i.'_image_url', PARAM_TEXT);

            $mform->addElement(
                'text',
                'config_card'.$i.'_button_text',
                'Texte du bouton'
            );
            $mform->setType('config_card'.$i.'_button_text', PARAM_TEXT);

            $mform->addElement(
                'text',
                'config_card'.$i.'_button_url',
                'Lien du bouton'
            );
            $mform->setType('config_card'.$i.'_button_url', PARAM_TEXT);

        }
    }
}