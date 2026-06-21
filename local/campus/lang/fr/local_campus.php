<?php
$string['pluginname'] = 'Campus — Fiche cours';
$string['view_trial'] = 'Accéder au cours d’essai';
$string['view_real']  = 'Accéder au cours';
$string['course_hidden'] = 'Ce cours n’est pas visible pour vous.';
$string['course_notfound'] = 'Cours introuvable.';
$string['subscribe_now'] = 'Acheter maintenant';
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

$string['trial_popup_title'] = 'Essayez gratuitement votre première leçon !';
$string['trial_popup_lead']  = 'Aucune limite de temps.<br>Aucune carte bancaire requise.<span class="hero-emoji">🛡</span>';
$string['trial_popup_tos']   = 'J\'accepte les Conditions d\'utilisation et la Politique de confidentialité.';
$string['trial_popup_accept']= 'Veuillez confirmer votre accord avec les conditions.';
$string['trial_firstname']   = 'Prénom';
$string['trial_lastname']    = 'Nom';
$string['trial_email']       = 'E-mail';
$string['trial_btn_continue']= 'Commencer l\'apprentissage';
$string['trial_btn_subscribe']= 'Acheter un cours';
$string['trial_expired_msg'] = 'Votre période d’essai est terminée. Acheter un cours pour continuer.';
$string['trial_tos_html'] =
    'En créant un compte, vous acceptez la <a href="{$a->policyurl}" target="_blank" rel="noopener">Politique de confidentialité</a> '
    .'et les <a href="{$a->termsurl}" target="_blank" rel="noopener">Conditions d’utilisation</a>.';
$string['trial_footer_note'] =
    "L'essai gratuit vous donne un accès illimité à la première leçon de chaque cours.";
$string['trial_firstname_ph'] = 'Votre prénom';
$string['trial_lastname_ph']  = 'Votre nom';
$string['trial_email_ph']     = 'Votre e-mail';

$string['mail_trial_started_subject'] = 'Votre période d’essai a commencé';
$string['mail_trial_started_body']    = 'Bonjour {$a->firstname}, votre période d’essai de 7 jours vient de commencer !';
$string['mail_trial_rem3_subject']    = 'Rappel : votre période d\'essai continue — {$a}';
$string['mail_trial_rem3_body'] = '<p>Bonjour, {$a->firstname} !</p> 
    <p>Nous espérons que vos premiers jours sur Campus<small><sup>FR</sup></small> sont intéressants et motivants.</p>

<p>Si vous souhaitez continuer votre apprentissage, votre réduction de {$a->dpct}% sur tous les abonnements reste valable encore pendant 24 heures.</p>

<p>Ensuite, le tarif standard s’appliquera.</p>

<p>Vous pouvez souscrire votre abonnement à prix réduit ici :</p>';

$string['mail_trial_rem3_body2'] =
    '<p>Après l\'activation de l’abonnement, vous obtiendrez immédiatement un accès complet à toutes les leçons, ainsi qu’aux futures mises à jour — tout ce qui vous aide à apprendre le français avec confiance, méthode et plaisir.</p>

<p>Si vous avez des questions, écrivez-nous simplement à <a href="mailto:{$a}">{$a}</a>. Nous sommes toujours là pour vous.</p>

<p>À très bientôt,<br>
Nata et l’équipe Campus<small><sup>FR</sup></small></p>';
$string['mail_trial_rem3_button'] = 'Souscrire un abonnement avec {$a}% de réduction';

$string['mail_trial_expired_subject'] = 'Votre période d’essai est terminée — {$a}';
$string['mail_trial_expired_body']    = 'Bonjour {$a->firstname}, votre période d’essai de 7 jours est arrivée à son terme.';
$string['mail_trial_cta_subscribe']   = 'Souscrire un abonnement';
$string['mail_trial_cta_continue']    = 'Continuer l’accès d’essai';
$string['mail_trial_rem3_subject_generic'] = '⏳ Encore 24 heures pour profiter de −{$a}% 🇫🇷';



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
$string['subscribe_now'] = 'Acheter maintenant';
$string['mycourses_empty'] = 'Connectez-vous pour retrouver vos cours. Vous pouvez aussi acheter de nouveaux cours.';
$string['no_courses_banner_title'] = 'Aucun cours disponible pour le moment.';
$string['no_courses_banner_text']  = 'Parcourez le catalogue et découvrez nos cours.';
$string['login_now']               = 'Connexion';
$string['browse_catalog']          = 'Parcourir le catalogue';
$string['access_trial_courses']    = 'Accéder aux cours d’essai';
$string['subscribe_now']           = 'Acheter maintenant';
$string['hint_go_to_header_cta'] = 'Achetez ou connectez-vous ici';

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
$string['trial_discount_banner_cta']   = 'Acheter un cours';
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

$string['mail_trial_reset_hint'] = '<p>Si vous avez oublié votre mot de passe, cliquez sur ce lien pour le réinitialiser.</p>' .
'👉 <a href="{$a->url}">Réinitialiser le mot de passe</a></p>';

// Sujet
$string['mail_trial_started_subject'] = 'Votre compte Campus<small><sup>FR</sup></small> a été créé 🎉';

// Corps
$string['mail_trial_started_body'] =
    '<p>Salut, {$a->firstname} !</p>' .
    '<p>Bienvenue à l’école de français Campus<small><sup>FR</sup></small> — votre compte a bien été créé.</p>';

// Bloc "Mes cours"
$string['mail_trial_started_mycourses'] =
    '<p>Nous avons déjà débloqué votre première leçon gratuite — suivez-la dès aujourd’hui et faites un premier pas vers vos objectifs !</p>' .
'👉 <a href="{$a->url}">Commencer</a></p>';

$string['mail_trial_desc'] =
    '<p>Sur Campus<small><sup>FR</sup></small>, vous trouverez :</p>' .
    '<ul><li>des vidéos de grammaire claires</li>' .
    '    <li>de la pratique avec un locuteur natif</li>' .
    '    <li>des exercices avec correction instantanée</li>' .
    '</ul>' .
    '<p>Le français paraît difficile au premier abord.<br/>' .
    'Avec les cours de Nata Kutrowski, vous en comprendrez rapidement la logique et prendrez plaisir à apprendre.</p>' .
    '<p>Consacrez seulement 20 minutes par jour — les progrès seront rapides.</p>' .
    '<p>À très bientôt en cours !</p>' .
    '<p>Cordialement,<br/>' .
    'l’équipe Campus<small><sup>FR</sup></small>.</p>';

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
    'Pour la sécurité de votre compte, utilisez un mot de passe unique pour Campus<small><sup>FR</sup></small> et pensez à le modifier régulièrement.';

// Autres champs
$string['trial_phone_country_placeholder'] = 'Code';
$string['trial_password_toggle_show'] = 'Afficher le mot de passe';
$string['trial_password_toggle_hide'] = 'Masquer le mot de passe';

$string['trial_welcome_banner_html'] =
    'Bienvenue sur Campus<small><sup>FR</sup></small> ! Votre compte d’essai est activé. ' .
    'Commencez par le niveau qui vous convient (A1 ou A2), la première leçon est offerte. ' .
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

$string['phone_country_group_popular'] = 'Pays les plus fréquents';
$string['phone_country_group_all']     = 'Tous les pays';

$string['trialreport_title'] = 'Rapport des abonnements d’essai';
$string['trialreport_col_firstname'] = 'Prénom';
$string['trialreport_col_lastname'] = 'Nom';
$string['trialreport_col_email'] = 'E-mail';
$string['trialreport_col_phone'] = 'Téléphone (avec indicatif)';
$string['trialreport_col_country'] = 'Pays';
$string['trialreport_col_start_date'] = 'Date de début';
$string['trialreport_col_end_date'] = 'Date de fin';
$string['trialreport_col_status'] = 'Statut';

$string['trialreport_export_xls'] = 'Enregistrer en XLS';
$string['trialreport_export_csv'] = 'Enregistrer en CSV';

$string['task_cleanup_notifications'] = 'Nettoyage des notifications système (Moodle updates / connexions)';


$string['audio_not_found_title'] = 'Audio introuvable';
$string['audio_not_found_message'] = 'Cet audio est introuvable ou n’est plus disponible.';
$string['audio_back_to_home'] = 'Retour à l’accueil';
$string['audio_player_instruction'] = 'Cliquez sur lecture pour écouter l’audio.';
$string['audio_browser_not_supported'] = 'Votre navigateur ne supporte pas l’audio.';

$string['other_courses_available_title'] = 'Découvrir d’autres cours';
$string['other_courses_available_text'] = 'Vous pouvez compléter votre parcours avec les autres cours disponibles sur CampusFR.';
$string['trial_badge'] = 'Essai';