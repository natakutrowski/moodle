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
