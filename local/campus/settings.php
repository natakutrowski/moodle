<?php
defined('MOODLE_INTERNAL') || die();

if ($h = new admin_settingpage('local_campus', get_string('pluginname', 'local_campus'))) {

    // 1) Liste multi-select des cours d’essai (friendly)
    $options = [];
    // On affiche uniquement les cours visibles (adapte si tu veux tout)
    $rs = $DB->get_records_sql("SELECT id, fullname FROM {course} WHERE id > 1 ORDER BY fullname ASC");
    foreach ($rs as $c) {
        $options[$c->id] = format_string($c->fullname) . " [{$c->id}]";
    }
    $h->add(new admin_setting_configmultiselect(
        'local_campus/trialcourses',               // stocké en CSV par Moodle
        get_string('set_trialcourses', 'local_campus'),
        get_string('set_trialcourses_desc', 'local_campus'),
        [],                                        // défaut
        $options
    ));

    // ... au même endroit que ton setting 'trialcourses'
    $h->add(new admin_setting_configmultiselect(
        'local_campus/subscribercourses',
        get_string('set_subscribercourses', 'local_campus'),
        get_string('set_subscribercourses_desc', 'local_campus'),
        [],
        // même $options de cours que pour trialcourses
        $options
    ));


    // 2) Durée en jours
    $h->add(new admin_setting_configtext(
        'local_campus/trialdays',
        get_string('set_trialdays', 'local_campus'),
        '', 7, PARAM_INT
    ));

    // 3) Rôle pour les comptes d’essai
    $h->add(new admin_setting_configtext(
        'local_campus/trialrole',
        get_string('set_trialrole', 'local_campus'),
        get_string('set_trialrole_desc', 'local_campus'),
        'trialstudent', PARAM_ALPHANUMEXT
    ));

    // 4) Préfixes pour comptes “fantômes”
    $h->add(new admin_setting_configtext(
        'local_campus/trialusernameprefix',
        get_string('set_trialusernameprefix', 'local_campus'),
        get_string('set_trialusernameprefix_desc', 'local_campus'),
        'trial_', PARAM_ALPHANUMEXT
    ));
    $h->add(new admin_setting_configtext(
        'local_campus/trialemailprefix',
        get_string('set_trialemailprefix', 'local_campus'),
        get_string('set_trialemailprefix_desc', 'local_campus'),
        'trial+', PARAM_RAW_TRIMMED
    ));
    // Domaine forcé pour l’e-mail pseudo (laisser vide = conserve le domaine d’origine)
    $h->add(new admin_setting_configtext(
        'local_campus/trialemaildomain',
        get_string('set_trialemaildomain', 'local_campus'),
        get_string('set_trialemaildomain_desc', 'local_campus'),
        '', PARAM_RAW_TRIMMED
    ));

    $h->add(new admin_setting_configtext(
        'local_campus/trial_discount_reminder_days',
        get_string('trial_discount_reminder_days', 'local_campus'),
        get_string('trial_discount_reminder_days_desc', 'local_campus'),
        2,            // valeur par défaut = 2 jours
        PARAM_INT
    ));

    // Jours après expiration (J + N) pour SUSPENDRE le compte
    $h->add(new admin_setting_configtext(
        'local_campus/trial_suspend_after_days',
        get_string('set_trial_suspend_after_days', 'local_campus'),
        get_string('set_trial_suspend_after_days_desc', 'local_campus'),
        30, // valeur par défaut : J+30
        PARAM_INT
    ));

    // Jours après expiration (J + N) pour SUPPRIMER le compte
    $h->add(new admin_setting_configtext(
        'local_campus/trial_delete_after_days',
        get_string('set_trial_delete_after_days', 'local_campus'),
        get_string('set_trial_delete_after_days_desc', 'local_campus'),
        90, // valeur par défaut : J+90
        PARAM_INT
    ));


    // Style du catalogue (1 ou 2).
    $h->add(new admin_setting_configselect(
        'local_campus/catalogue_style',
        'Style du catalogue',
        'Choisissez entre le style 1 (cards) ou 2 (box).',
        1,
        [1 => 'Style 1', 2 => 'Style 2']
    ));

    // Apparence & textes
    $h->add(new admin_setting_configtext('local_campus/catalogue_class', 'Classe CSS container', '', 'courses-area ptb-100'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_top_title', 'Sous-titre', '', 'Parcourez nos formations'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_title', 'Titre', '', 'Cours de niveau'));
    $h->add(new admin_setting_configtextarea('local_campus/catalogue_body', 'Texte sous les cartes (HTML)', '', ''));

    // Champs perso & comportements
    $h->add(new admin_setting_configtext('local_campus/catalogue_label_field', 'Champ perso — badge', '', 'cardlabel'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_trial_field', 'Champ perso — mapping cours d’essai', '', 'trialcourseid'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_real_field', 'Champ perso — mapping cours réel', '', 'realcourseid'));
    $h->add(new admin_setting_configcheckbox('local_campus/catalogue_force_direct_loggedin', 'Connectés → lien direct vers le cours réel', '', 0));

    // Fiche cours & libellés boutons
    $h->add(new admin_setting_configtext('local_campus/catalogue_desc_baseurl', 'URL de la fiche cours (placeholders {id},{shortname},{categoryid})', '', '/local/campus/course.php?id={id}&checktrial=1'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_desc_label', 'Libellé bouton “En savoir plus”', '', 'En savoir plus'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_cta_guest', 'Libellé CTA invité/essai', '', 'Accéder au cours d’essai'));
    $h->add(new admin_setting_configtext('local_campus/catalogue_cta_connected', 'Libellé CTA connecté', '', 'Accéder au cours'));


    $ADMIN->add('localplugins', $h);
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_campus_mobile_covers',
        get_string('mobilecoverspage', 'local_campus'),
        new moodle_url('/local/campus/mobile_course_covers.php'),
        'moodle/site:config'
    ));
}
