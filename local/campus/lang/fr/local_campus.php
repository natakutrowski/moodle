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

$string['trial_popup_title'] = 'Accès gratuit à tous les cours pendant 7 jours';
$string['trial_popup_lead']  = 'Accès immédiat à l\'ensemble des cours.<br>Aucune carte bancaire requise.<span class="hero-emoji">🛡</span>';
$string['trial_popup_tos']   = 'J\'accepte les Conditions d\'utilisation et la Politique de confidentialité.';
$string['trial_popup_accept']= 'Veuillez confirmer votre accord avec les conditions.';
$string['trial_firstname']   = 'Prénom';
$string['trial_lastname']    = 'Nom';
$string['trial_email']       = 'E-mail';
$string['trial_btn_continue']= 'Commencer l\'apprentissage';
$string['trial_btn_subscribe']= 'Souscrire un abonnement';
$string['trial_expired_msg'] = 'Votre période d’essai est terminée. Souscrivez un abonnement pour continuer.';
$string['trial_tos_html'] =
    'En créant un compte, vous acceptez la <a href="{$a->policyurl}" target="_blank" rel="noopener">Politique de confidentialité</a> '
    .'et les <a href="{$a->termsurl}" target="_blank" rel="noopener">Conditions d’utilisation</a>.';
$string['trial_footer_note'] =
    "À l’issue des 7 jours, l’accès s’arrête automatiquement — aucun prélèvement.";
$string['trial_firstname_ph'] = 'Votre prénom';
$string['trial_lastname_ph']  = 'Votre nom';
$string['trial_email_ph']     = 'Votre e-mail';

$string['mail_trial_started_subject'] = 'Votre période d’essai a commencé';
$string['mail_trial_started_body']    = 'Bonjour {$a->firstname}, votre période d’essai de 7 jours vient de commencer !';
$string['mail_trial_rem3_subject']    = 'Rappel : votre période d\'essai continue — {$a}';
$string['mail_trial_rem3_body']       = 'Bonjour {$a->firstname}, il vous reste encore quelques jours de période d’essai.';
$string['mail_trial_expired_subject'] = 'Votre période d’essai est terminée — {$a}';
$string['mail_trial_expired_body']    = 'Bonjour {$a->firstname}, votre période d’essai de 7 jours est arrivée à son terme.';
$string['mail_trial_cta_subscribe']   = 'Souscrire un abonnement';
$string['mail_trial_cta_continue']    = 'Continuer l’accès d’essai';
$string['mail_trial_rem3_subject_generic']    = 'Rappel : votre période d’essai est en cours';
$string['mail_trial_expired_subject_generic'] = 'Votre période d’essai est terminée';



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

$string['trial_password']      = 'Mot de passe';
$string['trial_password_ph']   = 'Créez un mot de passe';
$string['trial_password_help'] = 'Minimum 8 caractères. Ce mot de passe vous permettra de vous connecter.';
$string['trial_password_min']  = 'Le mot de passe doit contenir au moins 8 caractères.';
$string['trial_password_policy_error'] = 'Le mot de passe ne respecte pas la politique de sécurité. {$a}';

$string['emailalreadysubscribed'] =
    "Cette adresse est déjà associée à un compte. Veuillez vous connecter pour commencer la période d’essai.";

$string['trial_already_subscribed_html'] =
    'Vous avez déjà un abonnement actif. Veuillez <a href="{$a->login}" class="link-primary" target="_top" rel="noopener">vous connecter</a>.';

$string['trial_expired_html'] =
    'Votre période d’essai est terminée. <a href="{$a->subscribe}" class="link-primary" rel="noopener" data-subs-modal="1">Souscrivez un abonnement</a> pour continuer.';

$string['trial_discount_banner_title'] = 'Réduction de −{$a}% pour la période d’essai.';
$string['trial_discount_banner_body']  = 'Il reste : ';
$string['trial_discount_banner_cta']   = 'Souscrire un abonnement';
$string['trial_days_word']             = 'j.';

$string['trial_banner_reminder_title'] = 'Rappel concernant votre période d’essai';
$string['trial_banner_reminder_body']  = 'Votre accès d’essai se termine le {$a}. Pour continuer, souscrivez un abonnement.';

$string['trial_banner_expired_html'] =
    'Votre accès d’essai a expiré le <strong>{$a->date}</strong>. '
    .'<a href="{$a->url}" class="link-primary" data-subs-modal="1">Souscrivez un abonnement</a> pour continuer.';

$string['sub_expiry_banner'] =
    'Votre abonnement « {$a->plan} » expire le <strong>{$a->date}</strong> (dans {$a->days} j.).';

$string['login_suspended_html'] =
    'Votre compte est <strong>suspendu</strong> (période d’essai terminée). '
    .'Veuillez <a class="link-primary" href="{$a->link}">souscrire un abonnement</a> pour rétablir l’accès.';


$string['login_suspended_html'] = 'Votre compte est <strong>suspendu</strong> (fin de période d’essai). '
    .'Veuillez <a class="link-primary" href="{$a->link}">vous abonner</a> pour réactiver l’accès.';

$string['set_trialdays'] = 'Durée du probatoire (jours)';
$string['set_trialdays_desc'] = 'Nombre de jours d’accès gratuit (J). Par défaut : 7.';

$string['set_trial_suspend_after_days'] = 'Suspension du compte (J + N jours)';
$string['set_trial_suspend_after_days_desc'] = 'Nombre de jours après la fin de l’essai (J) avant de suspendre le compte (l’utilisateur ne peut plus se connecter). Par défaut : 30.';

$string['set_trial_delete_after_days'] = 'Suppression du compte (J + N jours)';
$string['set_trial_delete_after_days_desc'] = 'Nombre de jours après la fin de l’essai (J) avant suppression définitive du compte (si aucun autre abonnement actif). Par défaut : 90.';

// Démarrage — ligne “remise”
$string['mail_trial_discount_line'] = 'Une remise de <strong>{$a->pct}%</strong> est active jusqu’au <strong>{$a->date}</strong>.';

// Pré-suspension (J+suspendAfter−2)
$string['trial_presuspend_subject'] = 'Votre compte d’essai sera suspendu bientôt';
$string['trial_presuspend_body']    = 'Bonjour {$a->firstname},<br>Votre compte d’essai sera suspendu le <strong>{$a->date}</strong>. '
    .'Pour conserver l’accès, nous vous invitons à vous abonner dès maintenant.';

// Suspension J+30
$string['trial_suspended_subject']  = 'Votre compte d’essai a été suspendu';
$string['trial_suspended_body']     = 'Bonjour {$a->firstname},<br>Votre compte d’essai a été suspendu le <strong>{$a->sdate}</strong>. '
    .'Sans action de votre part, il sera supprimé le <strong>{$a->ddate}</strong>. '
    .'Vous pouvez réactiver l’accès en souscrivant à un abonnement.';

// Expiration J — ajout d’une mention “actif jusqu’à J+30”
$string['mail_trial_expired_hint_suspend'] = 'Votre compte restera actif (connexion possible) jusqu’au <strong>{$a}</strong>. '
    .'Abonnez-vous pour retrouver l’accès aux cours.';

$string['myaccompt'] = 'Mon compte';

// Confirmation du mot de passe + « œil »
$string['trial_password_confirm']      = 'Confirmez le mot de passe';
$string['trial_password_confirm_ph']   = 'Répétez le mot de passe';
$string['trial_password_confirm_help'] = 'Saisissez le même mot de passe une seconde fois pour confirmation.';
$string['trial_password_toggle']       = 'Afficher ou masquer le mot de passe';
$string['trial_password_mismatch']     = 'Les mots de passe ne correspondent pas.';

// Début du trial – informations supplémentaires
$string['mail_trial_started_credentials'] = 'Voici vos identifiants de connexion :<br>
Nom d’utilisateur : {$a->username}<br>
Mot de passe : {$a->password}<br>
Vous pouvez vous connecter ici : <a href="{$a->login_url}">Connexion à Campus<small><sup>FR</sup></small></a>.';

$string['mail_trial_started_mycourses'] =
    'Vous pouvez accéder à tous vos cours d’essai ici : <a href="{$a->mycourses_url}">Mes cours</a>.';

// Tableau des identifiants
$string['trial_username_label'] = 'E-mail de connexion';
$string['trial_password_label'] = 'Mot de passe';
$string['mail_trial_security_hint'] = 'Merci de garder ces informations confidentielles. Pour votre sécurité, vous pouvez modifier votre mot de passe à tout moment dans les paramètres de votre compte CampusFR.';
$string['mail_trial_started_mycourses'] =
    'Vous pouvez accéder à vos cours d’essai à tout moment depuis votre espace personnel sur Campus<small><sup>FR</sup></small>.';

$string['course_progress_ratio'] = '{$a->done} / {$a->total} éléments complétés';

// Téléphone
$string['trial_phone']      = 'Téléphone';
$string['trial_phone_ph']   = 'Votre numéro de téléphone';
$string['trial_phone_help'] = 'Le téléphone nous permet de répondre rapidement à vos questions et de vous aider à démarrer.';
$string['trial_phone_label'] = 'Téléphone';

$string['mail_trial_reset_hint'] =
    '<p>Vous avez créé votre mot de passe lors de l’inscription. Si vous l’oubliez, vous pouvez toujours le réinitialiser ici :</p>' .
    '👉 <a href="{$a->url}">Réinitialiser le mot de passe</a></p>';

// Sujet de l’e-mail
$string['mail_trial_started_subject'] =
    'Votre accès d’essai à Campus<small><sup>FR</sup></small> est activé 🎉';

// Corps principal
$string['mail_trial_started_body'] =
    '<p>Bonjour, {$a->firstname} !</p>' .
    '<p>Votre accès d’essai de 7 jours à Campus<small><sup>FR</sup></small> est maintenant actif.</p>' .
    '<p>Vous avez le temps de découvrir la plateforme, d’essayer le format, de faire quelques exercices et de gagner vos premiers croissants 🥐 — pour sentir si ce mode d’apprentissage vous convient.</p>' .
    '<p>Voici vos identifiants :</p>';

// Bloc “Mes cours”
$string['mail_trial_started_mycourses'] =
    '<p>Vous pouvez accéder à votre espace sur la plateforme via le lien ci-dessous :</p>' .
    '👉 <a href="{$a->url}">Accéder au campus</a></p>';

// Ligne de réduction
$string['mail_trial_discount_line'] =
    '<p>Souscrivez un abonnement complet dans les {$a->duration} premiers jours suivant l’activation de l’essai et obtenez une réduction de {$a->pct}% sur la poursuite de votre apprentissage sur Campus<small><sup>FR</sup></small>.</p>
<p>Cette réduction est valable uniquement pendant ces trois jours, ensuite le tarif classique s’appliquera.</p>';

$string['mail_trial_discount_btn'] =
    'Souscrire l’abonnement CampusFR avec {$a->pct}% de réduction';

// Boutons
$string['mail_trial_cta_continue']  = 'Ouvrir les cours d’essai';
$string['mail_trial_cta_subscribe'] = 'Souscrire l’abonnement complet';

// Étiquettes du tableau
$string['trial_username_label'] = 'E-mail de connexion';
$string['trial_phone_label']    = 'Téléphone';

// Indice de sécurité (fallback)
$string['mail_trial_security_hint'] =
    'Pour plus de sécurité, utilisez un mot de passe unique pour Campus<small><sup>FR</sup></small> et changez-le au besoin.';

// Autres champs
$string['trial_phone_country_placeholder'] = 'Code';
$string['trial_password_toggle_show'] = 'Afficher le mot de passe';
$string['trial_password_toggle_hide'] = 'Masquer le mot de passe';

$string['trial_welcome_banner_html'] =
    'Bienvenue sur Campus<small><sup>FR</sup></small> ! Votre accès d’essai de 7 jours est activé. ' .
    'Commencez par le niveau qui vous convient (A0, A1, A2 ou B1). ' .
    'Vous pouvez retrouver vos cours à tout moment via la page « Mes cours ».';

$string['mail_trial_started_support'] =
    '<p>Tout votre progrès sur la plateforme (exercices complétés, points-croissants accumulés) est conservé. En souscrivant l’abonnement complet, vous reprenez exactement là où vous vous êtes arrêté.</p>

<p>Ce message a été envoyé automatiquement.</br>
Si vous avez des questions, écrivez-nous à <a href="mailto:{$a->url}">{$a->url}</a> — nous serons heureux de vous aider.</p>

<p>Nous vous souhaitons de la joie dans chaque petit progrès, des leçons passionnantes et un avancement confiant en français ❤️</p>

<p>Nata et l’équipe Campus<small><sup>FR</sup></small></p>';

$string['trial_discount_reminder_days'] =
    'Délai avant l’envoi de l’e-mail de réduction (en jours)';

$string['trial_discount_reminder_days_desc'] =
    'Nombre de jours après le début de l’essai avant d’envoyer l’e-mail de réduction. Par défaut : 2 jours.';

