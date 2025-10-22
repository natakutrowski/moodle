<?php

class block_edly_course_filter_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        global $CFG;
        $edlyFontList = include($CFG->dirroot . '/theme/edly/inc/font_handler/edly_font_select.php');

        $style = 1;
        if(isset($this->block->config->style)){
            $style = $this->block->config->style;
        }

        // Section header title according to language file.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
        
        $mform->addElement('select', 'config_style', get_string('config_style', 'theme_edly'), array(1 => 'Style 1', 2 => 'Style 2'));
        $mform->setDefault('config_style', 1);

        // Class
        $mform->addElement('text', 'config_class', get_string('config_class', 'theme_edly'));
        $mform->setDefault('config_class', 'courses-area ptb-100');
        $mform->setType('config_class', PARAM_RAW);

        // Top Title
        $mform->addElement('text', 'config_top_title', get_string('config_top_title', 'theme_edly'));
        $mform->setDefault('config_top_title', 'Popular Courses');
        $mform->setType('config_top_title', PARAM_RAW);

        // Title
        $mform->addElement('text', 'config_title', get_string('config_title', 'theme_edly'));
        $mform->setDefault('config_title', 'Discover Your Perfect Program In Our Courses');
        $mform->setType('config_title', PARAM_RAW);

        // Liste complète des cours (y compris noms redondants), libellé "fullname (shortname) — Category"
        global $DB;
        $courseoptions = [];
        $sql = "SELECT c.id, c.fullname, c.shortname, c.category, cc.path
                FROM {course} c
                JOIN {course_categories} cc ON cc.id = c.category
                WHERE c.id <> :siteid
            ORDER BY cc.path, c.sortorder, c.fullname";
        $recs = $DB->get_records_sql($sql, ['siteid' => SITEID]);

        foreach ($recs as $r) {
            // Nom de catégorie formaté (chemin complet)
            $cat = \core_course_category::get($r->category, IGNORE_MISSING);
            $catlabel = $cat ? format_string($cat->get_formatted_name()) : '';
            $label = trim(format_string($r->fullname));
            $short = trim($r->shortname ?? '');
            if ($short !== '') {
                $label .= ' (' . s($short) . ')';
            }
            if ($catlabel !== '') {
                $label .= ' — ' . $catlabel;
            }
            $courseoptions[(int)$r->id] = $label;
        }

        // Select multiple (on garde les valeurs choisies si l’admin rouvre le formulaire)
        $select = $mform->addElement('autocomplete', 'config_courses', get_string('courses'), $courseoptions, [
            'multiple' => true,
            'tags'     => false,
            'showsuggestions' => true,
        ]);

        $select->setMultiple(true);
        // Valeurs par défaut si on réédite le bloc
        if (!empty($this->block->config) && !empty($this->block->config->courses)) {
            $mform->setDefault('config_courses', (array)$this->block->config->courses);
        }


        // Content
        $mform->addElement('text', 'config_body', get_string('config_body', 'theme_edly'));
        $mform->setDefault('config_body', 'Enjoy the top notch learning methods and achieve next level skills! You are the creator of your own career &amp; we will guide you through that. <a href="#">Register Free Now!</a>');
        $mform->setType('config_body', PARAM_RAW);

        // Section Shape Image
        $mform->addElement('text', 'config_shape_img', 'Shape Image URL');
        $mform->setType('config_shape_img', PARAM_TEXT);

        // Image URL
        $mform->addElement('static', 'config_image_doc', '<b><a style="color: var(--main-color)" href="https://docs.hibootstrap.com/docs/edly-moodle-theme-documentation/faqs/how-to-get-the-image-url/" target="_blank">Doc link: How to make Image URL?</a></b>'); 


        // ===== CampusFR: options (mapping + libellés + page description) =====
        $mform->addElement('text', 'config_label_field', 'Shortname du champ badge (cours)');
        $mform->setDefault('config_label_field', 'cardlabel');
        $mform->setType('config_label_field', PARAM_TEXT);

        $mform->addElement('text', 'config_trial_field', 'Shortname champ “ID cours d’essai” (cours)');
        $mform->setDefault('config_trial_field', 'trialcourseid');
        $mform->setType('config_trial_field', PARAM_TEXT);

        $mform->addElement('text', 'config_real_field', 'Shortname champ “ID cours réel” (cours)');
        $mform->setDefault('config_real_field', 'realcourseid');
        $mform->setType('config_real_field', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'config_force_direct_loggedin',
            'Utilisateurs connectés → cours réel');
        $mform->setDefault('config_force_direct_loggedin', 1);

        $mform->addElement('text', 'config_desc_baseurl', 'URL de base pour “En savoir plus”');
        $mform->setType('config_desc_baseurl', PARAM_TEXT);
        // Astuce : tu peux utiliser {id}, {shortname}, {categoryid}
        // ex: /local/campusfr/course.php?id={id}

        $mform->addElement('text', 'config_desc_label', 'Libellé bouton “En savoir plus”');
        $mform->setDefault('config_desc_label', 'En savoir plus');
        $mform->setType('config_desc_label', PARAM_TEXT);

        // Libellé CTA pour les invités (anonymes)
        $mform->addElement('text', 'config_cta_guest', 'Libellé CTA (invité)');
        $mform->setDefault('config_cta_guest', 'Accéder au cours d’essai');
        $mform->setType('config_cta_guest', PARAM_TEXT);

        // (déjà ajouté précédemment)
        $mform->addElement('text', 'config_cta_connected', 'Libellé CTA (connecté)');
        $mform->setDefault('config_cta_connected', 'Accéder au cours');
        $mform->setType('config_cta_connected', PARAM_TEXT);


    }
}
