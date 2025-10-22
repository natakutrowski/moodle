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

    // 5) Suppression des comptes après expiration
    $h->add(new admin_setting_configtext(
        'local_campus/deleteafterdays',
        get_string('set_deleteafterdays', 'local_campus'),
        get_string('set_deleteafterdays_desc', 'local_campus'),
        60, PARAM_INT
    ));

    $ADMIN->add('localplugins', $h);
}
