<?php
$string['pluginname'] = 'Campus — Fiche cours';
$string['view_trial'] = 'Accéder au cours d’essai';
$string['view_real']  = 'Accéder au cours';
$string['course_hidden'] = 'Ce cours n’est pas visible pour vous.';
$string['course_notfound'] = 'Cours introuvable.';
$string['subscribe_now'] = 'S’abonner';
$string['close'] = 'Fermer';

$string['set_trialcourses'] = 'Cours d’essai (IDs, séparés par des virgules)';
$string['set_trialcourses_desc'] = 'Saisissez les IDs des cours d’essai, par exemple: 12,34,56';
$string['set_trialdays'] = 'Durée de l’essai (jours)';
$string['set_trialrole'] = 'Rôle pour les comptes d’essai';
$string['set_trialrole_desc'] = 'Shortname du rôle (par ex. trialstudent). L’installateur crée un rôle par défaut.';
$string['set_deleteafterdays'] = 'Suppression des comptes (jours après expiration, 0 = jamais)';
$string['set_deleteafterdays_desc'] = 'Nombre de jours après expiration avant suppression du compte d’essai (0 = jamais).';
$string['set_trialusernameprefix'] = 'Préfixe pour le nom d’utilisateur des comptes d’essai';
$string['set_trialusernameprefix_desc'] = 'Ex.: trial_ → donnera trial_jdoe';
$string['set_trialemailprefix'] = 'Préfixe pour l’email des comptes d’essai';
$string['set_trialemailprefix_desc'] = 'Ex.: trial+ → donnera trial+jdoe@… (ou trial+md5@domaine forcé)';
$string['set_trialemaildomain'] = 'Domaine e-mail forcé (optionnel)';
$string['set_trialemaildomain_desc'] = 'Laisser vide pour conserver le domaine d’origine ; sinon remplacer par ce domaine (ex.: noreply.campusfr.invalid).';

$string['rolename_trialstudent'] = 'Étudiant (essai)';
$string['roledesc_trialstudent'] = 'Rôle lecture-seule pour l’accès d’essai.';
$string['cron_trial_maint'] = 'Campus — Relances & ménage essais';

$string['trial_popup_title'] = 'Essai gratuit de 7 jours';
$string['trial_popup_lead']  = 'Accédez immédiatement aux cours d’essai. Sans carte bancaire.';
$string['trial_popup_tos']   = 'J’accepte les Conditions générales et la Politique de confidentialité.';
$string['trial_popup_accept']= 'Merci d’accepter les conditions.';
$string['trial_firstname'] = 'Prénom';
$string['trial_lastname']  = 'Nom';
$string['trial_email']     = 'Email';
$string['trial_btn_continue']  = 'Continuer';
$string['trial_btn_subscribe'] = 'S’abonner';
$string['trial_expired_msg']   = 'Votre période d’essai est terminée. Abonnez-vous pour continuer.';
$string['trial_tos_html'] = 'J’accepte la <a href="{$a}" target="_blank" rel="noopener">Politique de confidentialité</a>.';

$string['mail_trial_started_subject'] = 'Votre essai a commencé';
$string['mail_trial_started_body']    = 'Bonjour {$a->firstname}, votre essai de 7 jours a commencé !';
$string['mail_trial_rem3_subject']    = 'Rappel : essai en cours — {$a}';
$string['mail_trial_rem3_body']       = 'Bonjour {$a->firstname}, il vous reste encore quelques jours pour profiter de votre essai.';
$string['mail_trial_expired_subject'] = 'Votre essai a expiré — {$a}';
$string['mail_trial_expired_body']    = 'Bonjour {$a->firstname}, votre essai est arrivé à expiration.';
$string['mail_trial_cta_subscribe']   = 'S’abonner';
$string['mail_trial_cta_continue']    = 'Continuer l’essai';
$string['mail_trial_rem3_subject_generic']   = 'Rappel : votre essai est en cours';
$string['mail_trial_expired_subject_generic']= 'Votre essai a expiré';

$string['cataloguetitle'] = 'Catalogue';
$string['catalogueheading'] = 'Cours de niveau';
$string['cataloguesub'] = 'Parcourez nos formations';
$string['moreinfo'] = 'En savoir plus';
$string['trial_access_label'] = 'Accéder au cours d’essai';
$string['cta_connected'] = 'Accéder au cours';
$string['nocoursesconfigured'] = 'Aucun cours configuré à afficher.';
$string['set_subscribercourses'] = 'Cours abonnés (IDs, séparés par des virgules)';
$string['set_subscribercourses_desc'] = 'Cours visibles uniquement pour les abonnés. Les visiteurs et comptes d’essai ne les voient pas.';
$string['back_to_all_courses'] = '← Revenir à tous les cours';

$string['tab_catalogue'] = 'Catalogue';
$string['tab_mycourses'] = 'Mes cours';
$string['mycourses_title'] = 'Mes cours';
$string['mycourses_sub'] = 'Vue d’ensemble des cours';
$string['mycourses_empty'] = 'Vous n’êtes inscrit à aucun cours pour le moment.';
$string['mycourses_browse'] = 'Parcourir le catalogue';
$string['cta_connected'] = 'Continuer';
$string['cta_connected_start'] = 'Commencer';
$string['cta_connected_resume'] = 'Reprendre';
$string['completed'] = 'terminé';
$string['completed_badge'] = 'Terminé';
$string['notenrolled'] = 'Non inscrit';
$string['course_not_started'] = 'Vous n’avez pas encore commencé ce cours';
$string['resume_here'] = 'Reprendre ici';
$string['congrats_completed'] = 'Félicitations ! Vous avez terminé ce cours.';
$string['browse_catalog'] = 'Parcourir le catalogue';
$string['access_trial_courses'] = 'Accéder aux cours d’essai';
$string['subscribe_now'] = 'S’abonner';
$string['mycourses_empty'] = 'Connectez-vous pour retrouver vos cours. Vous pouvez aussi découvrir nos cours d’essai ou vous abonner.';
$string['no_courses_banner_title'] = 'Aucun cours disponible pour le moment.';
$string['no_courses_banner_text']  = 'Parcourez le catalogue, découvrez nos cours d’essai ou abonnez-vous pour commencer.';
$string['login_now']               = 'Connexion';
$string['mycourses_empty']         = 'Connectez-vous pour retrouver vos cours. Vous pouvez aussi découvrir nos cours d’essai ou vous abonner.';
$string['browse_catalog']          = 'Parcourir le catalogue';
$string['access_trial_courses']    = 'Accéder aux cours d’essai';
$string['subscribe_now']           = 'S’abonner';
$string['hint_go_to_header_cta'] = 'Abonnez-vous ou connectez-vous ici';

// Admin (barre outils catalogue)
$string['admin_native_page'] = 'Page native Moodle';
$string['admin_show_hidden'] = 'Afficher aussi les cours cachés';
$string['admin_hide_hidden'] = 'Masquer les cours cachés';


