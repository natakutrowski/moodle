<?php
$string['pluginname'] = 'Achats';

// -- Configuration des abonnements
// Plans
$string['plan_1week'] = '1 semaine'; // do not delete
$string['plan_1month']   = '1 mois'; // do not delete
$string['plan_3months']  = '3 mois'; // do not delete
$string['plan_6months']  = '6 mois'; // do not delete
$string['plan_1year']    = '1 an'; // do not delete
$string['plan_3years']   = '3 ans'; // do not delete
$string['plan_lifetime'] = 'À vie'; // do not delete

// Boutons
$string['btn_import_csv'] = 'Importer des abonnements depuis un CSV';

// -- Gérer les abonnements
$string['manage_subscriptions']   = 'Gérer les abonnements';
$string['updated_subscriptions']  = '{$a} abonnement(s) mis à jour.';
$string['delete_subscriptions']   = '{$a} abonnement(s) ont été supprimés.';
$string['edit_subscriptions']     = 'Modifier les abonnements';
$string['user']                   = 'Utilisateur';
$string['plan']                   = 'Plan';
$string['start_date']             = 'Date de début';
$string['end_date']               = 'Date de fin';

$string['creation_date']          = 'Date de création';
$string['save_modifications']     = 'Enregistrer les modifications';
$string['delete_selected']        = 'Supprimer les abonnements sélectionnés';
$string['popover_duration']       = 'Durée';
$string['popover_scope']          = 'Périmètre d’accès';
$string['popover_courses']        = 'Cours';
$string['popover_no_courses']     = 'Aucun cours défini';

// -- Ajouter un abonnement
$string['add_subscription'] = 'Ajouter un abonnement';
$string['unknown_user']     = 'Utilisateur inconnu';
$string['sub_created']      = '{$a->user} a été abonné au plan <strong>{$a->plan}</strong>.';
$string['sub_exists']       = 'Un abonnement existe déjà pour {$a->user} ({$a->plan}).';
$string['sub_test_done']    = '{$a} a été abonné au cours de test.';
$string['select_user']      = 'Sélectionner un utilisateur';
$string['submit_sub']       = 'Abonner au périmètre sélectionné';
$string['submit_sub_test']  = 'Abonner uniquement au test';

// -- Import CSV
$string['import_subscriptions']      = 'Importer des abonnements';
$string['import_subscriptions_csv']  = 'Importer des abonnements depuis un fichier CSV';
$string['email']                     = 'E-mail';
$string['already_exists']            = 'Existe déjà';
$string['import_preview']            = 'Aperçu des abonnements à importer';
$string['select_csv_file']           = 'Sélectionner un fichier CSV';
$string['submit_csv_file']           = 'Téléverser le fichier CSV';
$string['import_count_valid']        = 'ligne(s) seront importées.';
$string['import_count_ignored']      = '{$a} ligne(s) ont été ignorées (abonnement déjà existant).';

// -- Traitement CSV
$string['missing_param']          = 'Paramètre manquant';
$string['no_valid_rows']          = 'Aucune ligne valide à importer';
$string['import_success_count']   = '{$a} abonnement(s) importé(s) avec succès.';
$string['import_skipped']         = 'Entrées ignorées (données manquantes ou invalides)';
$string['invalid_or_missing_fields'] = 'Champs invalides ou manquants';

// -- Gérer les plans
$string['scopes']              = '🎓 Périmètre d’accès';
$string['plans']               = '📝 Plans';
$string['user_subscriptions']  = '👨‍🎓 / 👩‍🎓 Abonnements utilisateur';
$string['translatetooltip']    = 'Info-bulle de traduction'; // to be checked
$string['pricestooltip']       = 'Info-bulle des prix'; // to be checked

// Périmètres
$string['scopename']        = 'Nom du périmètre';
$string['includedcourses']  = 'Cours inclus';
$string['addscope']         = '➕ Ajouter un nouveau périmètre';
$string['scopelist']        = 'Liste des périmètres';
$string['sortaz']           = 'Trier de A à Z';
$string['sortza']           = 'Trier de Z à A';
$string['name']             = 'Nom';
$string['description']      = 'Description';
$string['courses']          = '📖 Cours';
$string['dates']            = '📅 Dates';

$string['createdon']        = 'Créé le :';
$string['modifiedon']       = 'Modifié le :';
$string['editscope']        = '✏️ Modifier ce périmètre';
$string['deletescope']      = '🗑️ Supprimer ce périmètre';
$string['edit']             = 'Modifier le périmètre';
$string['add']              = 'Ajouter un périmètre';
$string['scopecreated']     = 'Périmètre créé. Ajoutez maintenant une traduction.';
$string['scopecreateerror'] = 'Erreur lors de la création du périmètre.';
$string['scopedeleted']     = 'Le périmètre et ses traductions ont été supprimés.';
$string['scopedeleteerror'] = 'Erreur lors de la suppression du périmètre.';
$string['error_scope_name_exists'] = 'Un périmètre portant ce nom existe déjà.';

// Traductions des périmètres
$string['translationspagetitle']   = 'Traductions';
$string['scopedefaultname']        = 'Nom par défaut du périmètre';
$string['translatedlanguages']     = 'Langues traduites';
$string['addtranslation']          = 'Ajouter une traduction';
$string['backtoscopelist']         = 'Retour à la liste des périmètres';
$string['language']                = 'Langue';
$string['alreadyused']             = 'Déjà utilisé';
$string['defaultscopename']        = 'Nom par défaut du périmètre';
$string['translatedname']          = 'Nom traduit';
$string['save']                    = 'Enregistrer';
$string['deletetranslation']       = 'Supprimer cette traduction';
$string['errorduplicatetranslation'] = 'Une traduction existe déjà dans la langue sélectionnée.';
$string['showalltranslations']     = 'Afficher toutes les traductions';
$string['cancel']                  = 'Annuler';
$string['confirmdeletetranslation'] = 'Voulez-vous vraiment supprimer définitivement cette traduction ?';

// Plans

$string['deactivateplan']         = 'Désactiver ce plan';
$string['activateplan']           = 'Activer ce plan';
$string['planname']               = 'Nom du plan';
$string['planduration']           = '⌛ Durée du plan';
$string['saveplan']               = 'Enregistrer le plan';
$string['plancreated']            = 'Le plan a été créé avec succès.';
$string['plancreateerror']        = 'Une erreur est survenue lors de la création du plan.';
$string['error_plan_name_exists'] = 'Un plan portant ce nom existe déjà.';
$string['planstatusupdated']      = 'Le statut du plan a été mis à jour.';
$string['planlist']               = 'Liste des plans';
$string['deleteplan']             = 'Supprimer ce plan';
$string['editplan']               = 'Modifier ce plan';
$string['thisplan']               = 'ce plan';
$string['plandefaultname']        = 'Nom par défaut du plan';
$string['plandeleted']            = 'Le plan et toutes ses traductions et ses prix ont été supprimés.';
$string['plandeleteerror']        = 'Erreur lors de la suppression du plan.';
$string['backtoplanlist']         = 'Retour à la liste des plans';
$string['addplan']                = 'Ajouter un nouveau plan';
$string['duration']               = '⌛ Durée';
$string['availabletranslations']  = 'Traductions disponibles';
$string['notranslation']          = 'Aucune traduction disponible';
$string['availablecurrencies']    = 'Devises disponibles';
$string['nocurrency']             = 'Aucune devise disponible';
$string['planincomplete']         = 'Activation impossible : le plan doit avoir au moins une traduction et un prix.';
$string['cannotactivateplan']     = 'Vous devez définir au moins une traduction et un prix avant d’activer ce plan.';
$string['is_recurring']           = 'Abonnement récurrent (renouvellement auto)';
$string['is_recurring_help']      = 'Si activé, le plan sera vendu via Stripe Subscriptions. Assurez-vous d’avoir défini un stripe_price_id pour chaque devise.'; // do not delete

// Prix
$string['currency']                    = 'Devise';
$string['price']                       = 'Prix';
$string['saveprice']                   = 'Enregistrer le prix';
$string['error_invalid_price']         = 'Veuillez saisir un prix positif valide.';
$string['planprices']                  = 'Prix';
$string['planpricesfor']               = 'Prix pour {$a}';
$string['addprice']                    = 'Ajouter un prix';
$string['editprice']                   = 'Modifier le prix';
$string['deleteprice']                 = 'Supprimer le prix';
$string['priceadded']                  = 'Prix ajouté avec succès.';
$string['priceupdated']                = 'Prix mis à jour.';
$string['pricedeleted']                = 'Prix supprimé.';
$string['confirmdeleteprice']          = 'Voulez-vous vraiment supprimer ce prix ?';
$string['error_currency_already_exists'] = 'Cette devise est déjà définie pour ce plan.';
$string['noprices']                    = 'Aucun prix';

$string['stripe_price_id']       = 'ID de prix Stripe';
$string['stripe_price_id_help']  = 'Identifiant du prix récurrent sur Stripe (ex. price_123…). Requis pour les plans récurrents.'; // do not delete
$string['badge_recurring']       = 'Renouvellement auto';

// JS suppression...
$string['thisscope']              = 'ce périmètre';
$string['confirmdeletetitle']     = 'Confirmer la suppression';
$string['confirmdeletemessage']   = '⚠️ Cette action est irréversible.<br><br>Voulez-vous vraiment supprimer <strong>{$a}</strong> ?<br><br>Toutes les traductions associées seront également supprimées.';
$string['confirmdeleteplanmessage'] = '⚠️ Cette action est irréversible.<br><br>Voulez-vous vraiment supprimer <strong>{$a}</strong> ?<br><br>Toutes les traductions et tous les prix associés seront également supprimés.';

$string['scope_and_duration'] = 'Périmètre et durée';
$string['courses_included']  = 'Cours inclus';
$string['select_price']      = 'Sélectionner le prix et la devise';

$string['your_subscriptions']       = 'Vos achats';
$string['no_active_subscriptions']  = 'Vous n’avez aucun achat actif.';

$string['pricepaid'] = 'Prix payé';

$string['courselist'] = 'Liste des cours';


$string['subscribe']       = 'Acheter';
$string['subscribe_to_campus'] = 'Acheter sur Campus<small><sup>FR</sup></small>';
$string['change_currency'] = 'Changer de devise';

$string['payment_success_check_email'] = 'Veuillez vérifier votre e-mail : un message vous attend pour finaliser la connexion et définir votre mot de passe.';
$string['payment_pending_msg']         = 'Votre paiement est en cours de validation. Cela prend généralement quelques secondes.';
$string['payment_success_title']       = 'Paiement réussi';
$string['payment_success_thanks']      = 'Merci ! Votre paiement a bien été traité.';
$string['payment_canceled_title']      = 'Paiement annulé';
$string['payment_canceled_msg']        = 'Votre paiement a été annulé. Recommencez pour accéder au cours.';
$string['back_to_plans']               = 'Retour aux cours disponibles';

$string['checkout_title']        = 'Paiement';
$string['checkout_duration']     = 'Durée :';
$string['checkout_go_to_payment']= 'Aller au paiement';

$string['welcome_subject'] = 'Votre accès à CampusFR est activé ✅';

$string['welcome_body_intro'] =
    '<p>Bonjour, {$a} !</p>' .
    '<p>Votre accès à Campus<small><sup>FR</sup></small> est activé.</p>' .
    '<p>Si vous aviez déjà utilisé Campus<small><sup>FR</sup></small> pendant la période d’essai, vous continuez simplement avec le même compte — vos exercices réalisés et vos points-croissants sont conservés. Si vous venez de nous rejoindre, ce compte sera utilisé pour toutes vos connexions.</p>' .
    '<p>Voici vos identifiants :</p>';

$string['welcome_username'] = 'E-mail :';
$string['welcome_plan_summary'] = 'Cours : {$a}';
$string['welcome_amount_summary'] = 'Montant : {$a}';

$string['welcome_text_canal'] =
    'Ajoutez-vous au canal Campus<small><sup>FR</sup></small> : nous y publions toutes les actualités importantes, les mises à jour et vous pouvez y poser vos questions aux enseignants.';
$string['welcome_button_canal'] = 'Canal Campus<small><sup>FR</sup></small>';

$string['welcome_text_group'] =
    'Vous pouvez également rejoindre le groupe pour échanger, demander des conseils, soutenir les autres et sentir que vous faites partie de la communauté.';
$string['welcome_button_group'] = 'Groupe Campus<small><sup>FR</sup></small>';

$string['welcome_footer'] =
    '<p>Ce message a été envoyé automatiquement.<br>
Si vous avez des questions, écrivez-nous à <a href="mailto:{$a}">{$a}</a> — nous serons ravis de vous aider.</p>
<p>Nous vous souhaitons beaucoup de joie dans chaque petit progrès, des leçons passionnantes et une belle avancée en français ❤️</p>
<p>Nata et l’équipe Campus<small><sup>FR</sup></small></p>';

$string['receipt_title'] = 'Achat de votre cours sur CampusFR confirmé ✅';
$string['receipt_plan'] = 'Cours : ';
$string['receipt_amount'] = 'Montant : ';
$string['receipt_tx'] = 'ID de transaction : ';
$string['receipt_period'] = 'Période d’accès : ';

$string['welcome_temp_password_label'] = 'Mot de passe temporaire :';

$string['welcome_security_hint'] =
    '<p>Vous avez créé votre mot de passe lors de l’inscription. Si vous l’oubliez, vous pouvez le réinitialiser ici :</p>' .
    '👉 <a href="{$a->url}">Réinitialiser le mot de passe</a></p>';

$string['welcome_mycourses'] =
    '<p>Vous pouvez accéder à votre profil via le lien ci-dessous :</p>' .
    '👉 <a href="{$a->url}">Entrer sur le campus</a></p>' .
    '<p>Informations sur votre abonnement :</p>';

$string['receipt_intro'] =
    '<p>L’accès à votre cours sur Campus<small><sup>FR</sup></small> a été activé avec succès, et le paiement a été confirmé.</p>
<p>Voici les informations principales concernant votre achat :</p>';

$string['receipt_button_open'] = 'Accéder à Campus<small><sup>FR</sup></small>';

$string['receipt_footer'] =
    '<p>À très bientôt sur Campus<small><sup>FR</sup></small> 🇫🇷🥐</p>
<p>L’équipe Campus<small><sup>FR</sup></small></p>';


// E-mails – échec / abandonné / relance
$string['email_failed_subject'] = 'Votre paiement n’a pas pu aboutir';
$string['email_failed_intro']   = 'Malheureusement, votre tentative de paiement a échoué.';
$string['email_failed_help']    = 'Vous pouvez réessayer dans quelques secondes avec le bouton ci-dessous. Si le problème persiste, essayez une autre carte ou contactez votre banque.';
$string['email_button_retry']   = 'Réessayer le paiement';

$string['email_abandoned_subject'] = 'Finalisez votre achat';
$string['email_abandoned_intro']   = 'Vous n’avez pas terminé votre achat. Reprenez là où vous vous êtes arrêté :';

$string['email_reminder_subject'] = 'Toujours intéressé ? Finalisez votre achat';
$string['email_reminder_intro']   = 'Vous pouvez finaliser votre achat en un clic :';

// Tâche planifiée
$string['task_followup'] = 'Abonnements – e-mails de suivi';

$string['payment_error_title'] = 'Erreur de paiement';
$string['payment_error_intro'] = 'Un problème est survenu lors de la préparation de votre paiement. Veuillez réessayer dans un instant.';
$string['email_reminder2_subject'] = 'Dernier rappel : finalisez votre achat';
$string['email_reminder2_intro']   = 'Petit rappel pour finaliser votre achat. Vous pouvez conclure en un clic :';

$string['mail_recurring_started_subject'] = 'Votre abonnement récurrent à « {$a} » est actif';
$string['mail_recurring_started_body']    = 'Merci ! Votre abonnement récurrent « {$a->plan} » a débuté le {$a->start}.';
$string['view_my_subscriptions']          = 'Voir mes achats';

$string['plan_highlight']      = 'Mise en avant';
$string['highlight_popular']   = 'Populaire';
$string['highlight_premium']   = 'Premium';
$string['plan_highlight_help'] = 'Choisissez la mise en avant de ce plan sur la page publique :
<ul>
  <li><b>Aucune</b> : carte standard</li>
  <li><b>Populaire</b> : badge jaune et style accentué</li>
  <li><b>Premium</b> : style premium avec appel à l’action mis en valeur</li>
</ul>'; // do not delete

$string['task_cleanup_login_tokens'] = 'Nettoyer les jetons de connexion expirés';

$string['option_queue_future']  = 'Prolonger (activation le {$a})';
$string['option_purchase_new']  = 'Nouvel abonnement';
$string['choose_option']        = 'Choisir une option';
$string['have_account_login_to_see_options'] = 'Vous avez déjà un compte ? Connectez-vous pour renouveler votre abonnement.';

// Au-dessus des options
$string['advisor_help_upgrade']  = 'Vous pouvez enchaîner sur votre abonnement actuel ou passer à un plan plus long. Le prix de la mise à niveau est ajusté selon le temps écoulé.';
$string['advisor_help_standard'] = 'Choisissez comment vous souhaitez activer cet abonnement.';
$string['advisor_help_guest']    = 'Connectez-vous pour voir les options de mise à niveau. Sinon, vous pouvez démarrer un nouvel abonnement en saisissant vos informations.';

// Récapitulatif du prix
$string['summary_price_title'] = 'Prix total';

$string['personal_info_title'] = 'Informations personnelles';
$string['personal_info_help']  = 'Ces informations sont nécessaires pour créer votre compte et activer votre accès.';

$string['mail_hello']         = 'Salut {$a},';
$string['mail_button_manage'] = 'Gérer mes achats';

$string['subupdate_subject'] = 'Votre accès à « {$a} » est actif';
$string['subupdate_body']    = 'Voici les informations mises à jour de votre accès à « {$a} » :';
$string['renewal_subject']   = 'Renouvellement confirmé – {$a}';
$string['renewal_body']      = 'Votre accès à « {$a} » a été renouvelé. Détails :';
$string['recurring_failed_subject'] = 'Paiement échoué – {$a}';
$string['recurring_failed_body']    = 'Le paiement de votre abonnement « {$a} » a échoué. Veuillez mettre à jour vos informations de paiement.';
$string['recurring_failed_button']  = 'Mettre à jour mon moyen de paiement';

$string['recurring_canceled_subject'] = 'Votre abonnement a été annulé – {$a}';
$string['recurring_canceled_body']    = 'Votre abonnement à « {$a} » a été annulé. Vous conservez l’accès jusqu’à la fin de la période en cours.';
$string['recurring_canceled_button']  = 'Se réabonner';


$string['mysubs_title']  = 'Mes achats';
$string['mysubs_empty'] = 'Vous n’avez pas encore acheté de cours.';
$string['period']        = 'Période';

$string['btn_extend'] = 'Prolonger';

$string['option_upgrade_now_replace'] = 'Mettre à niveau maintenant vers la durée sélectionnée (remplacer la file)';

$string['task_send_expiry_reminders'] = 'Envoyer des rappels d’expiration pour les abonnements non récurrents';
$string['expiry_reminder_subject']    = 'Votre accès se termine dans {$a} jour(s)';
$string['expiry_reminder_body']       = 'Votre abonnement « {$a->plan} » se terminera le {$a->date}. Renouvelez maintenant pour conserver un accès continu.';

$string['subscription_activated_subject'] = 'Votre accès à {$a} est maintenant actif';
$string['subscription_activated_body']    = 'Bonne nouvelle ! Votre accès à « {$a} » est maintenant actif.';

$string['subscription_expired_subject'] = 'Votre abonnement à {$a} a pris fin';
$string['subscription_expired_body']    = 'Votre abonnement « {$a->plan} » a pris fin le {$a->date}. Renouvelez maintenant pour retrouver l’accès.';
$string['expired_button_renew']         = 'Renouveler / S’abonner';
$string['task_expire_enrolments']       = 'Expirer les abonnements et mettre à jour les inscriptions';
$string['task_repair_paid_pr']          = 'Réparer les PR payées : recréer les abonnements manquants';

// Indicateurs & statuts
$string['payment_failed'] = 'Paiement échoué';

$string['subscribe_now']  = 'Acheter maintenant';


$string['upgrade_tariffs']            = 'Tarifs de référence : actuel = {$a->p1}, cible = {$a->p2}';
$string['upgrade_consumed_since_t0']  = 'Temps écoulé depuis le début de la fenêtre : {$a}';
$string['upgrade_equation_past']      = 'Partie passée (tarif actuel) : {$a->p1} × t/{$a->d1} = {$a->val}';
$string['upgrade_equation_future']    = 'Partie future (tarif cible) : {$a->p2} × (D2−t)/{$a->d2} = {$a->val}';
$string['upgrade_spent_window']       = 'Déjà payé dans cette fenêtre : {$a}';
$string['upgrade_base_cap']           = 'Base = {$a->base} ; Plafond dégressif = {$a->cap}';
$string['upgrade_final_amount']       = 'Montant proposé : <strong>{$a}</strong>';
$string['upgrade_details_summary']    = 'Comment ce prix est-il calculé ?';

$string['upgrade_confirmed_subject']  = 'Votre mise à niveau vers « {$a} » est confirmée';
$string['upgrade_confirmed_body']     = 'Bonne nouvelle ! Votre abonnement a été mis à niveau. Récapitulatif :';

$string['unknown_plan'] = 'Plan inconnu';

$string['manage_billing']                   = 'Gérer la facturation';
$string['provider_portal_not_supported']    = 'Portail client indisponible';
$string['provider_portal_not_supported_desc'] = 'Le prestataire « {$a} » ne propose pas encore de portail client. Vous pouvez gérer votre abonnement depuis votre profil.';


$string['subfield_start']             = 'Début';
$string['subfield_end']               = 'Fin';
$string['subfield_amount']            = 'Montant payé';
$string['subfield_txn']               = 'Transaction';
$string['subfield_provider']          = 'Fournisseur';
$string['subfield_provider_sub']      = 'Abonnement fournisseur';
$string['subfield_provider_customer'] = 'Client fournisseur';
$string['subfield_last_invoice']      = 'Dernière facture';
$string['subfield_last_failed_at']    = 'Dernier échec le';
$string['subfield_fail_reason']       = 'Raison de l’échec';
$string['subfield_created']           = 'Créé le';
$string['subfield_updated']           = 'Mis à jour le';
$string['subfield_unlimited']         = 'Illimité';
$string['subfield_payment_status']    = 'Statut du paiement';
$string['subpayment_action']          = 'Action requise';

// (optionnel) libellés traduits pour tes statuts
$string['substatus_active']    = 'Actif';            // do not delete
$string['substatus_queued']    = 'En file d’attente'; // do not delete
$string['substatus_replaced']  = 'Remplacé';         // do not delete
$string['substatus_expired']   = 'Expiré';           // do not delete
$string['substatus_canceled']  = 'Annulé';           // do not delete
$string['substatus_pending']   = 'En attente';       // do not delete
$string['substatus_error']     = 'Erreur';           // do not delete
$string['substatus_suspended'] = 'Suspendu';         // do not delete
$string['substatus_paid']      = 'Payé';             // do not delete
$string['substatus_failed']    = 'Échec';            // do not delete
$string['substatus_completed'] = 'Terminé';          // do not delete
$string['substatus_unknown']   = 'Inconnu';          // do not delete


$string['optional_error_msg'] = 'Message d’erreur optionnel';

$string['summary_price_wait']        = 'Sélectionnez une option pour voir le prix total.';
$string['existing_account_hint_html'] = 'Un compte existe déjà avec cet e-mail. <a class="link-primary fw-semibold" href="{$a->url}">Se connecter</a>.';

$string['email_footer_copyright'] = '© {$a->year} {$a->brand}. Tous droits réservés.';
$string['email_footer_unexpected'] = 'Si vous ne vous attendiez pas à cet e-mail, vous pouvez l’ignorer en toute sécurité.';
$string['receipt_total']   = 'Total payé';
$string['receipt_invoice'] = 'Facture';

$string['email_show_pr_ref']      = 'Afficher la référence PR dans les e-mails';
$string['email_show_pr_ref_desc'] = 'Ajouter une petite référence technique (numéro de PR et date) en bas des e-mails. Désactivé par défaut.';
$string['unknown_payment_event']  = 'Événement de paiement inconnu : {$a}';


$string['sessiondisplay']                       = 'Session : {$a}';

// Titres
$string['emails_links_heading']      = 'E-mails & liens';
$string['emails_links_heading_desc'] = 'Paramètres des e-mails de suivi et des liens de reprise.';
$string['followups_heading']         = 'Relances & expiration';
$string['followups_heading_desc']    = 'Délais (en minutes) d’expiration et d’envoi des rappels.';

// Logo de marque (général/e-mail)
$string['brand_logo_url_label'] = 'URL du logo de marque';
$string['brand_logo_url_desc']  = 'URL absolue vers un petit logo (PNG/SVG, hauteur ~32 px) utilisé dans les e-mails.';

// Secret des liens e-mail
$string['email_link_secret_label'] = 'Secret pour les liens de reprise';
$string['email_link_secret_desc']  = 'Chaîne utilisée pour signer les liens de reprise (par défaut : $CFG->passwordsaltmain).';

// Expiration & rappels
$string['expire_pending_after_minutes_label'] = 'Expirer les paiements en attente';
$string['expire_pending_after_minutes_desc']  = 'Basculer en attente → expiré après N minutes sans paiement.';
$string['reminder1_after_minutes_label']      = 'Rappel n°1';
$string['reminder1_after_minutes_desc']       = 'Envoyer un premier rappel si le statut ∈ (pending, expired, failed) et ancienneté ≥ N minutes.';
$string['reminder2_after_minutes_label']      = 'Rappel n°2';
$string['reminder2_after_minutes_desc']       = 'Envoyer un second rappel si toujours impayé et ancienneté ≥ N minutes (depuis la création).';

// Plan mis en avant
$string['featured_planid_label'] = 'Plan mis en avant';
$string['featured_planid_desc']  = 'ID du plan mis en avant sur la page des offres.';


$string['alfa_not_paid']         = 'Paiement non effectué';

$string['subfield_pr_id']        = 'Demande de paiement n°';
$string['subfield_pr_status']    = 'Statut PR';
$string['subfield_pr_provider']  = 'Fournisseur PR';
$string['subfield_pr_amount']    = 'Montant PR';
$string['subfield_pr_orderid']   = 'PR orderId';
$string['subfield_pr_txnid']     = 'PR transactionId';
$string['subfield_pr_paidat']    = 'PR payé le';
$string['subfield_pr_link']       = 'Lien de paiement PR';
$string['subfield_pr_lasterror'] = 'Dernière erreur PR';
$string['notavailable']          = 'N/D';


$string['btn_signin'] = 'Se connecter';

$string['provider_alfa']   = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Manuel';
$string['provider_csv']    = 'CSV';
$string['provider_dev']    = 'Dev';
$string['provider_trial']  = 'Essai';

$string['configmissing']        = 'Configuration manquante : {$a}.';

$string['invalidcsvupload']     = 'Le fichier CSV téléversé est invalide.';
$string['csvwritefail']         = 'Échec d’écriture du fichier CSV.';
$string['invalidpricecurrency'] = 'Combinaison prix/devise invalide.';
$string['plan_not_found']       = 'Plan d’abonnement introuvable.';
$string['scopenotfound']        = 'Périmètre d’accès introuvable.';
$string['scopedeleteinuse']     = 'Impossible de supprimer ce périmètre car il est utilisé.';
$string['plannotfound']         = 'Plan introuvable.';


$string['retry_invalid_status'] = 'Cette demande de paiement ne peut pas être relancée dans son état actuel.';
$string['retry_link_expired']   = 'Ce lien de relance est invalide ou expiré. Veuillez démarrer un nouveau checkout.';

// Sections
$string['providers_header']      = 'Fournisseurs de paiement';
$string['provider_default']      = 'Fournisseur par défaut';
$string['provider_default_desc'] = 'Prestataire utilisé lorsqu’aucune règle de routage ne s’applique.';

// Environnements
$string['env_mode']      = 'Environnement';
$string['env_mode_desc'] = 'Choisissez les identifiants à utiliser.';
$string['env_test']      = 'Test';
$string['env_live']      = 'Production';
$string['stripe_profile_test'] = 'Test';
$string['stripe_profile_live_ei'] = 'Live EI';
$string['stripe_profile_live_sas'] = 'Live SAS';
$string['stripe_secret_live_sas'] = 'Clé secrète (LIVE SAS)';
$string['stripe_publishable_live_sas'] = 'Clé publique (LIVE SAS)';
$string['stripe_webhook_secret_live_sas'] = 'Secret du webhook Stripe (LIVE SAS)';
$string['stripe_portal_configuration_id_live_sas'] = 'ID de configuration du portail Stripe (LIVE SAS)';

// Stripe
$string['stripe_secret_test']                 = 'Clé secrète (TEST)';
$string['stripe_publishable_test']            = 'Clé publique (TEST)';
$string['stripe_webhook_secret_test']         = 'Secret du webhook Stripe (TEST)';
$string['stripe_portal_configuration_id_test']= 'ID de configuration du portail Stripe (TEST)';
$string['stripe_portal_configuration_id_desc']= 'Optionnel : ID de configuration du portail client (ex. pc_xxx). Si vide, la configuration par défaut de Stripe sera utilisée.';

$string['stripe_secret_live']                 = 'Clé secrète (LIVE EI)';
$string['stripe_publishable_live']            = 'Clé publique (LIVE EI)';
$string['stripe_webhook_secret_live']         = 'Secret du webhook Stripe (LIVE EI)';
$string['stripe_portal_configuration_id_live']= 'ID de configuration du portail Stripe (LIVE EI)';

// Alfa
$string['alfa_settings_header']     = 'Alfa Bank';
$string['alfa_api_base_test']       = 'URL de base de l’API (TEST)';
$string['alfa_username_test']       = 'Identifiant (TEST)';
$string['alfa_password_test']       = 'Mot de passe (TEST)';
$string['alfa_token_test']          = 'Jeton d’API (TEST)';
$string['alfa_webhook_secret_test'] = 'Secret du webhook Alfa (TEST)';
$string['alfa_api_base_live']       = 'URL de base de l’API (LIVE)';
$string['alfa_username_live']       = 'Identifiant (LIVE)';
$string['alfa_password_live']       = 'Mot de passe (LIVE)';
$string['alfa_token_live']          = 'Jeton d’API (LIVE)';
$string['alfa_webhook_secret_live'] = 'Secret du webhook Alfa (LIVE)';

$string['policy_url_ru']  = 'URL de la politique de confidentialité (Russie)';
$string['policy_url_row'] = 'URL de la politique de confidentialité (Reste du monde)';
$string['terms_url_ru']   = 'URL des Conditions générales d\'utilisation (Russie)';
$string['terms_url_row']  = 'URL des Conditions générales d\'utilisation (Reste du monde)';
$string['offer_url_ru']   = 'URL des Conditions générales de vente (Russie)';
$string['offer_url_row']  = 'URL des Conditions générales de vente (Reste du monde)';
$string['privacy_policy'] = 'Politique de confidentialité';
$string['terms_cgu']      = 'Conditions générales d\'utilisation';
$string['terms_cgv']      = 'Conditions générales de vente';
$string['i_accept_policy']= 'J’accepte la {$a}.';
$string['i_accept_terms'] = 'J’accepte les {$a}.';
$string['i_accept_all_terms'] =
    'J’accepte la {$a->policy}, les {$a->terms} et le {$a->offer}.';

$string['availability_mode']       = 'Visibilité du plugin';
$string['availability_mode_desc']  = 'Restreindre temporairement toutes les pages publiques du plugin Abonnements.';
$string['availability_enabled']    = 'Activé (public)';
$string['availability_adminonly']  = 'Administrateurs uniquement';
$string['availability_disabled']   = 'Désactivé';

$string['subs_unavailable']           = 'Les abonnements sont temporairement indisponibles.';
$string['subs_unavailable_adminonly'] = 'Les pages d’abonnement sont actuellement réservées aux administrateurs.';

$string['label_inactive'] = '(inactif)';

$string['edittranslation'] = 'Modifier la traduction'; // do not delete
$string['newtranslation']  = 'Nouvelle traduction'; // do not delete

$string['task_subscription_rollover'] = 'Activer les abonnements en file et expirer ceux terminés';
$string['renew_now']        = 'Renouveler maintenant';
$string['renew_soon_msg']   = 'Votre accès se termine dans {$a} jour(s). Renouvelez maintenant pour éviter une interruption.';
$string['queued_starts_in'] = 'Démarre dans {$a} jour(s)';
$string['none']             = 'Aucun';
$string['mycourses_profile_heading'] = 'Mes cours';

$string['plan_inactive']          = 'Ce plan n’est plus disponible. Veuillez choisir un plan actif.';
$string['plan_inactive_redirect'] = 'Ce plan n’est plus disponible. Veuillez sélectionner un nouveau plan.';
$string['plan_description_show']  = 'Afficher la description';

$string['email_copy_to']      = 'Copie e-mail admin';
$string['email_copy_to_desc'] = 'Une ou plusieurs adresses (séparées par des virgules) recevront une copie des e-mails envoyés par le plugin Abonnements.';

$string['settings:sitedefault'] = 'Langue du site (par défaut)';
$string['settings:defaultuserlang'] = 'Langue par défaut pour les nouveaux comptes';
$string['settings:defaultuserlang_desc'] = 'Si vide, les nouveaux utilisateurs héritent de la langue par défaut du site. Choisissez une langue pour la forcer à la création.';
$string['settings:defaultemaillang'] = 'Langue des e-mails envoyés par le plugin';
$string['settings:defaultemaillang_desc'] = 'Si vide, les e-mails utilisent la langue préférée du destinataire (ou la langue du site). Choisissez une langue pour la forcer.';

$string['recurring_canceled_effect_now'] = 'La résiliation prend effet immédiatement. Votre accès est suspendu.';
$string['recurring_canceled_effect_on']  = 'La résiliation prendra effet le {$a}. Vous conservez l’accès jusqu’à cette date.';
$string['payment_failcode'] = 'Raison';
$string['payment_nextretry'] = 'Prochaine tentative';
$string['email_retry_expires'] = 'Lien valable jusqu’au';

$string['contact_admin_subject'] = 'Nouveau message de contact';
$string['contact_copy_subject']  = 'Nous avons bien reçu votre message';
$string['contact_copy_intro']    = 'Merci pour votre message. Nous revenons vers vous très vite.';
$string['contact_label_name']    = 'Nom';
$string['contact_label_email']   = 'E-mail';
$string['contact_label_msg']     = 'Message';
$string['view_site']             = 'Ouvrir le site';
$string['contact_label_ip'] = 'IP';
$string['contact_label_ua'] = 'User-Agent';
$string['reply_now']              = 'Répondre maintenant';
$string['contact_reply_subject']  = 'Re : votre message à CampusFR';
$string['contact_reply_greeting'] = 'Bonjour {$a}, nous avons bien reçu votre message.';
$string['contact_reply_reminder'] = 'Rappel de votre message :';
$string['contact_reply_marker']   = '— Réponse ci-dessous —';
$string['reply_in_admin'] = 'Répondre depuis l’admin (éditeur HTML)';
$string['reply_text']             = 'Votre réponse';
$string['contact_reply_sent_hint']  = 'La réponse a été envoyée à l’adresse indiquée. Vous pouvez fermer cette page.';

$string['trial_checkout_banner'] = 'Vous êtes connecté avec un compte d’essai. Merci d’indiquer vos coordonnées pour créer votre abonnement.';

// Générique / protections create_session
$string['invalid_operation'] = 'Opération de paiement invalide.';
$string['invalid_payment_request_status'] = 'Cette demande de paiement n’est plus utilisable.';
$string['invalid_payment_request_owner'] = 'Vous n’avez pas accès à cette demande de paiement.';
$string['invalid_currency_for_alfa'] = 'Devise invalide pour Alfa : seule la devise RUB est acceptée.';
$string['err_no_redirect_url'] = 'Le prestataire de paiement n’a pas renvoyé d’URL de redirection.';
$string['err_cannot_determine_price'] = 'Impossible de déterminer le prix du plan pour la devise sélectionnée.';

// Passerelle Alfa
$string['alfa_missing_api_base'] = 'Configuration Alfa incomplète : URL de base de l’API non définie.';
$string['alfa_rub_only'] = 'Alfa : seule la devise RUB est prise en charge.';
$string['alfa_register_error'] = 'Erreur lors de l’enregistrement Alfa : {$a}';
$string['alfa_missing_formurl'] = 'Réponse Alfa invalide : formUrl ou orderId manquant.';
$string['paymentgatewayerror'] = 'Erreur de passerelle de paiement : {$a}';

// Renforcements supplémentaires (gardes optionnelles)
$string['alfa_price_mismatch'] = 'Incohérence de prix détectée pour Alfa. Réessayez ou contactez le support. ({$a})';
$string['alfa_amount_mismatch'] = 'Incohérence de montant détectée pour Alfa. Réessayez ou contactez le support. ({$a})';

// Passerelle Stripe
$string['stripe:missingamount'] = 'Stripe : montant manquant pour créer la session de paiement.';
$string['stripe:productname'] = '{$a} — règlement';
$string['stripe:missingpriceidforsubscription'] = 'Stripe : price_id manquant pour le mode abonnement.';
$string['stripe:missingpriceid'] = 'Stripe : price_id manquant.';
$string['stripe:sdkautoloadnotfound'] = 'Fichier d’autoload du SDK Stripe introuvable : {$a}';
$string['missing_customer_id'] = 'Identifiant client manquant.';

// Renforcements Stripe (optionnels)
$string['stripe_invalid_currency'] = 'Stripe : devise invalide ou non prise en charge : {$a}.';
$string['stripe_nonpositive_amount'] = 'Stripe : le montant doit être strictement supérieur à 0.';

// UI — générique
$string['payui_error_title'] = 'Le paiement n’a pas pu aboutir';
$string['payui_error_subtitle'] = 'Un problème est survenu côté service de paiement ou de notre côté.';
$string['payui_error_generic'] = 'Réessayez. Si le problème persiste, contactez-nous : nous finaliserons votre commande.';

$string['payui_cta_retry'] = 'Réessayer';
$string['payui_cta_back'] = 'Retour aux offres';
$string['payui_cta_contact'] = 'Contacter le support';
$string['payui_support_hint'] = 'Besoin d’aide ? Écrivez-nous : {$a}';

$string['payui_order_ref'] = 'Référence de commande : {$a}';

// UI — raisons (courtes et claires)
$string['payui_reason_security'] = 'Votre session a expiré. Rechargez la page puis réessayez.';
$string['payui_reason_link'] = 'Le lien vers la page de paiement est indisponible. Merci de réessayer.';
$string['payui_reason_currency'] = 'La devise du paiement n’a pas pu être confirmée. Merci de réessayer.';
$string['payui_reason_amount'] = 'Le montant n’a pas pu être confirmé. Merci de réessayer.';
$string['payui_reason_gateway'] = 'Le service de paiement a renvoyé une erreur. Merci de réessayer.';
$string['payui_reason_canceled'] = 'Le paiement a été annulé.';
$string['payui_reason_declined'] = 'Le paiement a été refusé par votre banque.';
$string['payui_reason_expired'] = 'La session de paiement a expiré. Merci de recommencer.';
$string['payui_reason_owner'] = 'Ce lien de paiement n’appartient pas à votre compte.';
$string['payui_reason_status'] = 'Ce lien de paiement n’est plus valide.';

// Succès & en attente
$string['payui_success_title'] = 'Paiement confirmé';
$string['payui_success_subtitle'] = 'Merci ! Votre paiement a été validé.';
$string['payui_success_thanks'] = 'Bienvenue ! Votre accès a été activé.';
$string['payui_success_check_email'] = 'Nous vous avons envoyé un e-mail avec vos accès et les prochaines étapes. Connectez-vous pour commencer.';
$string['payui_pending_title'] = 'C’est presque fini…';
$string['payui_pending_msg'] = 'Votre paiement est en cours de confirmation. Cela peut prendre jusqu’à une minute. Vous pouvez fermer cette page : nous vous écrirons dès que tout est prêt.';

// CTAs & libellés
$string['payui_cta_my_subscriptions'] = 'Aller à mes achats';
$string['payui_cta_signin'] = 'Se connecter';
$string['payui_session_display'] = 'Session de paiement : {$a}';
$string['payui_label_price'] = 'Prix';
$string['payui_label_plan'] = 'Offre';
$string['payui_cta_mycourses'] = 'Aller à mes cours';

$string['settings_support_email'] = 'E-mail du support';
$string['settings_support_email_desc'] = 'Affiché sur les pages de paiement (succès/erreur) comme lien de contact.';
$string['stripe_price_mismatch'] = 'Stripe : incohérence de prix détectée. Réessayez ou contactez le support. ({$a})';

$string['settings_trial_section'] = 'Essai 7 jours';
$string['settings_trial_planid'] = 'Plan d’essai (ID)';
$string['settings_trial_planid_desc'] = 'ID du plan marqué « is_trial ».';
$string['settings_trial_duration_days'] = 'Durée de l’essai (jours)';
$string['settings_trial_duration_days_desc'] = 'Nombre de jours de l’essai gratuit.';
$string['settings_trial_discount_percent'] = 'Réduction (%) pendant la fenêtre';
$string['settings_trial_discount_percent_desc'] = 'Pour traçage et application côté paiement (agnostique du PSP).';
$string['settings_trial_discount_hours'] = 'Fenêtre de réduction (heures)';
$string['settings_trial_discount_hours_desc'] = 'Ex. 72 heures après le début de l’essai.';
$string['missing_trial_plan'] = 'Aucun plan d’essai configuré (trial_plan_id).';


$string['settings_paylock_section'] = 'Verrouillage du prix (checkout)';
$string['settings_paylock_strict'] = 'Mode strict en cas d’écart';
$string['settings_paylock_strict_desc'] = 'Si coché, la création d’abonnement échoue si le montant payé ne correspond pas au montant verrouillé.';
$string['settings_paylock_tolerance'] = 'Tolérance d’écart (en centimes)';
$string['settings_paylock_tolerance_desc'] = 'Écart maximum autorisé entre le montant verrouillé et le montant payé (par défaut 2 centimes).';

$string['pricing_missing_price'] = 'Aucun prix n’est défini pour ce plan et cette devise ({$a}).';
$string['cannot_purchase_trial_plan'] = 'Ce plan est un plan d’essai et ne peut pas être acheté.';
$string['payment_mismatch_too_large'] = 'Écart de paiement trop important par rapport au montant verrouillé.';
$string['paylock_missing_lockdata'] = 'Aucune donnée de verrouillage du prix (locked_*) n’est disponible pour cette demande.';
$string['paylock_invalid_minor'] = 'Montant verrouillé invalide.';
$string['stripe_lock_requires_payment_mode'] = 'Le verrouillage de prix nécessite le mode « payment » (montant fixe). Utilisez un paiement unique ou réactivez les coupons.';
$string['alfa_nonpositive_amount'] = 'Montant Alfa non positif après verrouillage.';
$string['alfa:productname'] = 'Abonnement';
$string['paylock_missing_context'] = 'Impossible de calculer le prix : utilisateur ou plan non défini.';

$string['currency_selector_label'] = 'Devise';
$string['currency_eur'] = '€ EUR';
$string['currency_rub'] = '₽ RUB';
$string['set_display_currency_symbols'] = 'Afficher le symbole monétaire';
$string['set_display_currency_symbols_desc'] = 'Si activé, les prix sont affichés avec le symbole (ex. 49 €). Sinon, le code est utilisé (ex. 49 EUR).';
$string['badge_limited_offer'] = 'Offre limitée -{$a}%';
$string['price_unavailable_in'] = 'Non disponible en {$a->curr} — affichage en {$a->fallback}.';
$string['checkout_discount_note']        = 'Votre offre de lancement est encore valable';
$string['checkout_discount_note_prefix'] = '🎁 −{$a}% sur tous les achats de cours. Réduction disponible uniquement';
$string['days_short']                    = 'j';

$string['cancel_price_title']  = 'Prix prévu';

$string['success_price_title'] = 'Montant payé';
$string['error_price_title']   = 'Prix prévu';
$string['reason_trial72h']     = 'Remise -{$a}% appliquée pendant votre période d’essai.';
$string['task_sub_expiry_reminders'] = 'Rappels d’expiration d’abonnement';
$string['expiry_reminder_subject_today'] = 'Votre abonnement expire aujourd’hui';

$string['email_copy_verbose'] = 'Appendice technique dans les copies (log@...)';
$string['email_copy_verbose_desc'] = 'Si activé, chaque copie contient un résumé technique (PR/User/Plan/Sub, locked_*, etc.).';

$string['existing_account_login_first'] = 'Un compte existe déjà avec cet e-mail. Veuillez vous connecter pour poursuivre et rattacher l’achat à votre compte.';
$string['task_enrol_scope_fill'] = 'Abonnements — compléter les inscriptions selon le scope du plan';

$string['paymentsuccess_redirect_msg'] = 'Vous allez être redirigé vers « Mes cours » dans {$a} secondes…';
$string['paymentsuccess_mascot_alt']   = 'Gustave, la girafe, vous félicite pour votre paiement réussi.';
$string['paymentcancel_mascot_alt'] = 'Illustration d’un paiement annulé.';
$string['paymenterror_mascot_alt'] = 'Illustration d’une erreur de paiement.';
$string['plan_price_per_month'] = '(soit {$a}/mois)';

$string['upgrade_window_label']    = 'Fenêtre de calcul : {$a->start} → {$a->end}';
$string['upgrade_ref_prices']      = 'Tarifs de référence : actuel = {$a->current}, cible = {$a->target}';
$string['upgrade_part_past']       = 'Part déjà consommée au tarif actuel : {$a}';
$string['upgrade_part_future']     = 'Part à venir au tarif du plan cible : {$a}';
$string['upgrade_base_total']      = 'Total théorique pour cette fenêtre : {$a}';
$string['upgrade_already_paid']    = 'Déjà payé dans cette fenêtre : {$a}';
$string['upgrade_base_minus_paid'] = 'Montant d’upgrade avant promotion : {$a->base} − {$a->paid} = {$a->diff}';
$string['upgrade_discount_line']   = 'Promotion −{$a->pct}% appliquée sur {$a->before} ⇒ {$a->after}';
$string['upgrade_amount_proposed'] = 'Montant proposé : {$a}';

$string['trial_subscribe_now'] = 'Acheter maintenant';
$string['plan_label'] = 'Plan';
$string['checkout_go_to_payment_discount'] = 'Acheter avec la remise';
$string['checkout_full_access_line'] = 'Accès illimité à toutes les leçons du cours.';
$string['summary_price_title_single'] = 'Abonnement pour {$a}';

$string['digital_pdf_badge'] = 'PDF Campus<small><sup>FR</sup></small>';
$string['digital_pdf_intro'] = 'Un guide pratique pour comprendre, mémoriser et réviser les verbes du 3e groupe sans se perdre dans des tableaux interminables.';
$string['digital_pdf_item_1'] = 'Les familles principales des verbes du 3e groupe.';
$string['digital_pdf_item_2'] = 'Les modèles de conjugaison les plus utiles.';
$string['digital_pdf_item_3'] = 'Des explications claires pour repérer les régularités.';
$string['digital_pdf_item_4'] = 'Un support PDF à garder et à réviser à votre rythme.';
$string['digital_pdf_price_eur'] = 'Prix EUR';
$string['digital_pdf_price_rub'] = 'Prix RUB';
$string['digital_pdf_buy_title'] = 'Acheter le PDF';
$string['digital_pdf_firstname'] = 'Prénom';
$string['digital_pdf_lastname'] = 'Nom';
$string['digital_pdf_email'] = 'Email';
$string['digital_pdf_email_help'] = 'Le lien de téléchargement sera envoyé à cette adresse.';
$string['digital_pdf_buy_eur'] = 'Acheter en EUR : {$a->price} €';
$string['digital_pdf_buy_rub'] = 'Acheter en RUB : {$a->price} ₽';
$string['digital_payment_created'] = 'Demande de paiement PDF créée. Connexion au PSP à l’étape suivante.';
$string['digital_success_title'] = 'Achat PDF Campus<small><sup>FR</sup></small>';
$string['digital_success_preview'] = 'Mode provisoire : la demande de paiement est bien créée, mais le paiement Stripe/Alfa n’est pas encore branché.';
$string['digital_success_request_created'] = 'Demande créée';
$string['digital_success_product'] = 'Produit';
$string['digital_success_email'] = 'Email';
$string['digital_success_amount'] = 'Montant';
$string['digital_success_provider'] = 'Provider';
$string['digital_success_status'] = 'Statut';
$string['digital_cancel_title'] = 'Paiement annulé';
$string['digital_cancel_message'] = 'Le paiement n’a pas été finalisé. Vous pouvez réessayer si vous le souhaitez.';
$string['digital_cancel_retry'] = 'Réessayer le paiement';
$string['digital_success_download'] = 'Télécharger le PDF';
$string['digital_success_payment_pending'] = 'Votre paiement est en cours de validation. Si vous venez de payer, actualisez cette page dans quelques secondes.';
$string['digital_download_not_paid'] = 'Ce téléchargement n’est pas disponible car le paiement n’est pas validé.';
$string['digital_download_expired'] = 'Ce lien de téléchargement a expiré.';
$string['digital_download_file_missing'] = 'Le fichier PDF est introuvable.';
$string['digital_success_payment_confirmed'] = 'Votre paiement est confirmé. Vous pouvez maintenant télécharger votre PDF.';
$string['digital_mail_access_subject'] = 'Votre PDF Campus<small><sup>FR</sup></small> est prêt 📘';
$string['digital_mail_access_intro'] = 'Merci pour votre achat ! Votre PDF est maintenant disponible.';
$string['digital_mail_access_hint'] = 'Vous pouvez télécharger votre PDF avec le bouton ci-dessous. Pensez à le sauvegarder sur votre appareil pour y accéder facilement à tout moment.';
$string['digital_mail_download_button'] = 'Télécharger le PDF';

$string['digital_mail_receipt_subject'] = 'Reçu de votre achat CampusFR';
$string['digital_mail_receipt_intro'] = 'Ce message confirme votre achat sur Campus<small><sup>FR</sup></small>. Vous trouverez ci-dessous le récapitulatif de votre commande.';

$string['digital_mail_product'] = 'Produit';
$string['digital_mail_amount'] = 'Montant';
$string['digital_mail_payment_date'] = 'Date de paiement';

$string['digital_success_paid_heading'] = 'Merci pour votre achat !';
$string['digital_success_paid_intro'] = 'Votre paiement est confirmé. Votre PDF est prêt à être téléchargé.';
$string['digital_success_pending_heading'] = 'Paiement en cours de validation';

$string['digital_success_email_sent_hint'] = 'Nous vous avons également envoyé le lien de téléchargement et le reçu par email.';
$string['digital_success_pending_hint'] = 'Si vous venez de payer, actualisez cette page dans quelques secondes.';

$string['digital_sales_hero_intro'] = 'Un guide pratique et clair pour comprendre les verbes du 3e groupe, repérer les régularités et arrêter de les apprendre “au hasard”.';

$string['digital_sales_lifetime_access'] = '✔ Accès à vie au PDF après achat';

$string['digital_sales_content_title'] = 'Ce que vous allez trouver dans ce PDF';
$string['digital_sales_content_item_1'] = 'Les principales familles de verbes du 3e groupe.';
$string['digital_sales_content_item_2'] = 'Les modèles de conjugaison les plus utiles.';
$string['digital_sales_content_item_3'] = 'Des explications simples et visuelles.';
$string['digital_sales_content_item_4'] = 'Des tableaux faciles à relire rapidement.';
$string['digital_sales_content_item_5'] = 'Des regroupements logiques pour mieux mémoriser.';
$string['digital_sales_content_item_6'] = 'Un support pratique à garder pendant vos révisions.';

$string['digital_sales_forwho_title'] = 'Ce PDF est idéal si…';
$string['digital_sales_forwho_item_1'] = 'vous mélangez souvent les verbes du 3e groupe.';
$string['digital_sales_forwho_item_2'] = 'vous voulez enfin voir des régularités.';
$string['digital_sales_forwho_item_3'] = 'vous apprenez le français de manière autonome.';
$string['digital_sales_forwho_item_4'] = 'vous préparez un examen ou une certification.';
$string['digital_sales_forwho_item_5'] = 'vous voulez un support pratique à garder sous la main.';

$string['digital_sales_secure_payment'] = '🔒 Paiement sécurisé via Stripe ou Alfa.';
$string['digital_sales_instant_access'] = '⚡ Accès immédiat après paiement + lien envoyé par email.';
$string['digital_cover_zoom_hint'] = 'Cliquez sur l’image pour l’agrandir.';

$string['digital_purchases_title'] = 'Achats de produits digitaux';
$string['digital_purchases_export_xlsx'] = 'Exporter en XLSX';
$string['digital_purchases_count'] = '{$a} achat(s) trouvé(s).';
$string['digital_purchases_payment_date'] = 'Date de paiement';
$string['digital_purchases_emails'] = 'Emails';
$string['digital_purchases_access_email_short'] = 'PDF';
$string['digital_purchases_receipt_email_short'] = 'Reçu';

$string['digital_download_mobile_missing'] = 'La version mobile de ce fichier n’est pas disponible.';
$string['digital_success_download_main'] = 'Télécharger la version classique';
$string['digital_success_download_mobile'] = 'Télécharger la version mobile';
$string['digital_mail_download_main_button'] = 'Télécharger la version classique';
$string['digital_mail_download_mobile_button'] = 'Télécharger la version mobile';

$string['task_reconcile_digital_payments'] = 'Réconcilier les paiements digitaux en attente';
$string['digital_purchases_emails_status'] = 'PDF / Reçu';
$string['digital_purchases_payment_or_creation_date'] = 'Date paiement / création';
$string['digital_purchases_db_status'] = 'Statut DB';
$string['digital_purchases_provider_status'] = 'Statut provider';
$string['digital_purchases_provider_reason'] = 'Raison / détail provider';

$string['digital_sales_stats_button'] = 'Statistiques ventes';

$string['digital_sales_stats_days'] = '{$a} jour';
$string['digital_sales_stats_days_plural'] = '{$a} jours';

$string['digital_purchases_show_paid'] = 'Voir les PAID';
$string['digital_purchases_show_pending'] = 'Afficher les pending';
$string['digital_purchases_show_pending_paid_provider'] = 'Pending / PAID provider';
$string['digital_purchases_show_all'] = 'Afficher tout';
$string['digital_purchases_reconcile_pending'] = 'Réconcilier les pending payés';
$string['digital_purchases_hide_provider_status'] = 'Masquer les statuts provider live';
$string['digital_purchases_check_provider_status'] = 'Vérifier les statuts provider live';
$string['digital_purchases_provider_status_info'] = 'Les statuts provider live sont vérifiés en lecture seule. Aucune modification en base et aucun email ne sont envoyés.';
$string['digital_purchases_reconcile_confirm'] = 'Confirmer la réconciliation des pending payés côté provider ?';

$string['digital_download_classic'] = 'Classique';
$string['digital_download_mobile'] = 'Mobile';

$string['digital_sales_stats_title'] = 'Statistiques ventes digitales';
$string['digital_sales_stats_back_to_purchases'] = 'Retour aux achats';
$string['digital_sales_stats_sales_found'] = 'Ventes payées trouvées : {$a}';
$string['digital_sales_stats_no_sales'] = 'Aucune vente payée sur cette période.';
$string['digital_sales_stats_histogram'] = 'Nombre de ventes par période';
$string['digital_sales_stats_cumulative'] = 'Ventes cumulées';
$string['digital_sales_stats_show_from'] = 'Afficher depuis';

$string['digital_catalog_title'] = 'Boutique digitale Campus<small><sup>FR</sup></small>';
$string['digital_catalog_intro'] = 'Retrouvez ici nos PDF, guides et supports pratiques pour progresser en français à votre rythme.';
$string['digital_catalog_empty'] = 'Aucun produit digital disponible pour le moment.';
$string['digital_catalog_view_product'] = 'Voir le produit';
$string['digital_product_not_found_catalog_notice'] = 'Le produit demandé n’est pas disponible ou n’existe plus. Vous pouvez découvrir les produits disponibles ci-dessous.';

$string['digital_rub_confirm_title'] = 'Paiement en roubles';
$string['digital_rub_confirm_vpn'] = '💡 Avant le paiement, il est préférable de désactiver temporairement votre VPN — sinon la page de paiement peut parfois s’ouvrir incorrectement ou afficher une erreur 😊';
$string['digital_rub_confirm_continue'] = 'Continuer vers le paiement';

$string['digital_products_admin_title'] = 'Produits digitaux';
$string['digital_products_add'] = 'Ajouter un produit digital';
$string['digital_products_view_purchases'] = 'Voir les achats';
$string['digital_products_view_catalog'] = 'Voir la boutique';
$string['digital_products_count'] = '{$a} produit(s) digital(aux)';
$string['digital_products_cover'] = 'Cover';
$string['digital_products_slug'] = 'Slug';
$string['digital_products_titles'] = 'Titres';
$string['digital_products_prices'] = 'Prix';
$string['digital_products_files'] = 'Fichiers';
$string['digital_products_status'] = 'Statut';
$string['digital_products_purchases'] = 'Achats';
$string['digital_products_sortorder'] = 'Ordre';
$string['digital_products_actions'] = 'Actions';
$string['digital_products_cover_missing'] = 'Image absente';
$string['digital_products_file_main'] = 'Classique';
$string['digital_products_file_mobile'] = 'Mobile';
$string['digital_products_enabled'] = 'Actif';
$string['digital_products_disabled'] = 'Inactif';
$string['digital_products_open_public'] = 'Ouvrir';
$string['digital_products_delete_confirm'] = 'Supprimer ce produit digital ?';
$string['digital_product_edit_new_title'] = 'Nouveau produit digital';
$string['digital_product_edit_edit_title'] = 'Modifier le produit digital';
$string['digital_product_edit_main_info'] = 'Informations principales';
$string['digital_product_edit_internal_name'] = 'Nom interne';
$string['digital_product_edit_price_eur'] = 'Prix EUR';
$string['digital_product_edit_price_rub'] = 'Prix RUB';
$string['digital_product_edit_files_hint'] = 'Les fichiers doivent être placés manuellement dans les dossiers prévus : PDF dans moodledata/local_subscriptions/private_pdfs, covers dans local/subscriptions/pix/cover.';
$string['digital_product_edit_translations'] = 'Traductions';
$string['digital_product_edit_title'] = 'Titre';
$string['digital_product_edit_saved'] = 'Produit digital enregistré.';
$string['digital_product_edit_slug_exists'] = 'Ce slug existe déjà.';
$string['digital_product_edit_current_file'] = 'Fichier actuel';
$string['digital_product_edit_no_file'] = 'Aucun fichier actuellement.';
$string['digital_product_edit_click_to_upload'] = 'Cliquez ici pour choisir ou remplacer le fichier.';
$string['digital_product_edit_access_note'] = 'Note d’accès après achat';
$string['digital_product_edit_content_title'] = 'Titre du bloc contenu';
$string['digital_product_edit_forwho_title'] = 'Titre du bloc “pour qui”';
$string['digital_product_edit_buy_title'] = 'Titre du bloc achat';
$string['digital_products_status_updated'] = 'Statut du produit mis à jour.';
$string['digital_products_enable'] = 'Activer';
$string['digital_products_disable'] = 'Désactiver';
$string['digital_products_duplicate'] = 'Dupliquer';
$string['digital_products_duplicated'] = 'Produit dupliqué. Vous pouvez maintenant modifier la copie.';
$string['digital_products_deleted'] = 'Produit digital supprimé.';
$string['digital_products_delete_has_purchases'] = 'Ce produit ne peut pas être supprimé car il possède déjà des achats.';

$string['digital_reassurance_instant'] = 'Accès immédiat après le paiement';
$string['digital_reassurance_versions'] = 'Versions classique + mobile incluses';
$string['digital_reassurance_email'] = 'Liens envoyés automatiquement par email';
$string['digital_reassurance_nocampus'] = 'Aucun compte Campus<small><sup>FR</sup></small> nécessaire';
$string['digital_reassurance_secure'] = 'Paiement sécurisé via Stripe / Alfa Bank';
$string['digital_redirecting_payment'] = 'Redirection vers la page de paiement…';

$string['digital_redirecting_payment_desc'] =
'Veuillez patienter quelques secondes. Ne fermez pas cette page.';

$string['digital_success_thank_you'] = 'Merci pour votre achat !';
$string['digital_success_confirmed_intro'] = 'Votre paiement est confirmé. Vos fichiers sont prêts à être téléchargés.';
$string['digital_success_pending_title'] = 'Paiement en cours de validation';
$string['digital_success_payment_pending_support'] = 'Votre paiement est en cours de validation. Si vous venez de payer, actualisez cette page dans quelques secondes. Si rien ne change, contactez-nous à support@campusfr.fr.';
$string['digital_success_summary_title'] = 'Résumé de votre achat';
$string['digital_success_main_version_hint'] = 'Version classique : idéale sur ordinateur, tablette ou pour impression.';
$string['digital_success_mobile_version_hint'] = 'Version mobile : optimisée pour consulter le PDF sur téléphone.';
$string['digital_success_email_sent_notice'] = 'Nous vous avons également envoyé les liens de téléchargement et le reçu par email.';
$string['digital_success_support_title'] = 'Un problème avec votre téléchargement ?';
$string['digital_success_support_text'] = 'Écrivez-nous à {$a}. Nous vous aiderons rapidement.';
$string['digital_success_back_to_shop'] = 'Voir les autres produits';

$string['digital_cancel_heading'] = 'Paiement non finalisé';
$string['digital_cancel_intro'] = 'Votre achat n’a pas été confirmé. Si vous avez quitté la page de paiement avant la validation, aucun accès n’a encore été activé.';
$string['digital_cancel_vpn_hint'] = '💡 Si vous payez en RUB, pensez à désactiver temporairement votre VPN : la page de paiement peut parfois s’ouvrir incorrectement ou afficher une erreur.';
$string['digital_cancel_support_text'] = 'Si vous pensez avoir été débité ou si vous avez besoin d’aide, écrivez-nous à {$a}.';
$string['digital_cancel_gateway_timeout'] =
'La page de paiement n’a pas pu être ouverte pour le moment. Cela peut être temporaire. Réessayez dans quelques minutes.';


$string['planentitlementsfor'] = 'Droits d’accès du plan : {$a}';
$string['addentitlement'] = 'Ajouter un droit d’accès';
$string['editentitlement'] = 'Modifier le droit d’accès';
$string['deleteentitlement'] = 'Supprimer le droit d’accès';
$string['saveentitlement'] = 'Enregistrer le droit d’accès';
$string['noentitlements'] = 'Aucun droit d’accès n’a encore été configuré pour ce plan.';
$string['entitlementcreated'] = 'Le droit d’accès a bien été créé.';
$string['entitlementupdated'] = 'Le droit d’accès a bien été mis à jour.';
$string['entitlementdeleted'] = 'Le droit d’accès a bien été supprimé.';
$string['confirmdeleteentitlement'] = 'Voulez-vous vraiment supprimer ce droit d’accès ?';

$string['entitlement_course'] = 'Cours';
$string['entitlement_accesslevel'] = 'Niveau d’accès';
$string['entitlement_role'] = 'Rôle dans le cours';
$string['entitlement_groupname'] = 'Nom du groupe';
$string['entitlement_groupname_help'] = 'Optionnel. Si ce champ est rempli, l’utilisateur sera ajouté à ce groupe Moodle dans le cours sélectionné.';
$string['entitlement_priority'] = 'Priorité';
$string['entitlement_priority_help'] = 'Un accès avec une priorité plus élevée peut remplacer un accès avec une priorité plus basse. Valeurs conseillées : essai = 10, grammaire = 50, complet = 100.';
$string['entitlement_already_exists'] = 'Ce plan possède déjà ce niveau d’accès pour ce cours.';

$string['accesslevel_trial'] = 'Essai';
$string['accesslevel_grammar'] = 'Grammaire uniquement';
$string['accesslevel_full'] = 'Accès complet';
$string['invalidplanid'] = 'Plan invalide ou manquant.';


$string['planupgradesintro'] = 'Définissez quels plans peuvent être transformés en un autre plan. Par exemple : A2 Grammaire → A2 Complet. Avec le prix par différence, le prix de l’upgrade est calculé ainsi : prix du plan cible moins prix du plan actuel, dans la même devise.';
$string['addupgrade'] = 'Ajouter un upgrade';
$string['editupgrade'] = 'Modifier l’upgrade';
$string['deleteupgrade'] = 'Supprimer l’upgrade';
$string['saveupgrade'] = 'Enregistrer l’upgrade';
$string['noupgrades'] = 'Aucun upgrade de plan n’a encore été configuré.';
$string['upgradecreated'] = 'La règle d’upgrade a bien été créée.';
$string['upgradeupdated'] = 'La règle d’upgrade a bien été mise à jour.';
$string['upgradedeleted'] = 'La règle d’upgrade a bien été supprimée.';
$string['confirmdeleteupgrade'] = 'Voulez-vous vraiment supprimer cette règle d’upgrade ?';

$string['upgrade_fromplan'] = 'Plan de départ';
$string['upgrade_toplan'] = 'Plan cible';
$string['upgrade_pricingmode'] = 'Mode de calcul du prix';
$string['upgrade_pricingmode_help'] = 'Pour l’instant, seul le calcul par différence est pris en charge : prix de l’upgrade = prix du plan cible - prix du plan actuel, dans la même devise.';
$string['upgrade_pricing_difference'] = 'Différence entre les prix des deux plans';
$string['upgrade_isactive'] = 'Actif';
$string['upgrade_same_plan_error'] = 'Le plan de départ et le plan cible doivent être différents.';
$string['upgrade_already_exists'] = 'Cette règle d’upgrade existe déjà.';

$string['inactive'] = 'Inactif';
$string['status'] = 'Statut';

$string['planentitlements'] = 'Droits d’accès des plans';
$string['planupgrades'] = 'Upgrades de plans';
$string['option_upgrade_difference'] = 'Passer à la version complète';
$string['plan_already_owned'] = 'Vous avez déjà accès à ce plan.';
$string['upgrade_from_to_summary'] = 'Vous passez de « {$a->from} » à « {$a->to} ». Vous payez uniquement la différence.';
$string['upgrade_badge'] = 'Upgrade';
$string['upgrade_discount_applied'] = 'Votre réduction liée à l’essai a été appliquée : {$a->discount} %.';
$string['upgrade_cta'] = 'Passer à la version complète';

$string['unlock_grammar_title'] = 'Activité incluse dans le module Grammaire';
$string['unlock_grammar_text'] = 'Cette activité fait partie du module Grammaire. Vous pouvez acheter uniquement la Grammaire ou le cours complet.';
$string['unlock_grammar_button'] = 'Acheter la grammaire';

$string['unlock_full_title'] = 'Activité réservée à la version complète';
$string['unlock_full_text'] = 'Cette activité n’est pas incluse dans le module Grammaire. Passez à la version complète pour accéder à l’ensemble du contenu du cours.';
$string['unlock_full_button'] = 'Acheter la version complète';

$string['restricted_access_title'] = 'Accès réservé';
$string['restricted_access_text'] = 'Acheter le cours pour débloquer cette activité.';
$string['buy'] = 'Acheter';

$string['plan_already_covered'] = 'Vous avez déjà un accès équivalent ou supérieur à ce contenu.';
$string['all_courses_owned_title'] = 'Vous avez déjà accès à tous les cours disponibles';
$string['all_courses_owned_text'] = 'Aucun nouvel achat n’est nécessaire pour le moment. Vous pouvez continuer votre apprentissage depuis votre espace cours.';

$string['unlock_subscriber_title'] = 'Activité réservée aux membres';
$string['unlock_subscriber_text'] = 'Cette activité n’est pas disponible avec l’accès d’essai. Choisissez une formule pour continuer.';
$string['unlock_subscriber_button'] = 'Voir les formules';

$string['digital_purchases_profile_title'] = 'Vos achats digitaux';
$string['digital_purchase_date'] = 'Date d’achat';
$string['digital_purchases_filter_registered'] = 'Acheteurs inscrits';
$string['digital_purchases_filter_guests'] = 'Acheteurs non inscrits';
$string['digital_purchases_campus_account'] = 'Compte Campus';

$string['course_purchases_profile_title'] = 'Vos achats de cours';
$string['purchase_date'] = 'Date d’achat';
$string['available_courses'] = 'Cours disponibles';
$string['digital_product_view_page'] = 'Voir la page du produit';

$string['digital_purchases_empty'] = 'Vous n’avez pas encore acheté de produit digital.';

$string['digital_purchase_downloads'] = 'Téléchargements';
$string['digital_product'] = 'Produit digital';
$string['user_purchases_title'] = 'Achats de {$a}';
$string['admin_details'] = 'Informations admin';
$string['subfield_id'] = 'ID';
$string['subfield_userid'] = 'ID utilisateur';
$string['subfield_productid'] = 'ID produit';
$string['subfield_slug'] = 'Slug';
$string['subfield_created_at'] = 'Créé le';
$string['subfield_paid_at'] = 'Payé le';
$string['subfield_expires_at'] = 'Expire le';
$string['subfield_paymentid'] = 'ID paiement';
$string['subfield_provider_paymentid'] = 'ID paiement fournisseur';
$string['subfield_checkout_url'] = 'URL de paiement';
$string['subfield_success_url'] = 'URL succès';
$string['subfield_cancel_url'] = 'URL annulation';
$string['subfield_download_token'] = 'Token de téléchargement';
$string['subfield_raw_response'] = 'Réponse brute fournisseur';
$string['admin_subscription_details'] = 'Informations admin sur l’abonnement';
$string['admin_payment_request_details'] = 'Informations payment request';
$string['payment_request_not_found'] = 'Aucune payment request liée trouvée';

$string['subfield_planid'] = 'ID plan';
$string['subfield_status'] = 'Statut';
$string['subfield_payment_request_id'] = 'ID payment request';
$string['subfield_provider_subscription_id'] = 'ID abonnement fournisseur';
$string['subfield_provider_customer_id'] = 'ID client fournisseur';
$string['subfield_renewal_date'] = 'Date de renouvellement';
$string['subfield_updated_at'] = 'Mis à jour le';
$string['subfield_operation'] = 'Opération';
$string['subfield_sessionid'] = 'ID session';
$string['subfield_price'] = 'Prix';
$string['subfield_amount_minor'] = 'Montant en unité mineure';
$string['subfield_locked_list_price'] = 'Prix catalogue verrouillé';
$string['subfield_locked_discount_percent'] = 'Remise verrouillée (%)';
$string['subfield_locked_discount_amount'] = 'Montant de remise verrouillé';
$string['subfield_locked_discount_reason'] = 'Raison de la remise verrouillée';
$string['subfield_locked_final_price'] = 'Prix final verrouillé';
$string['subfield_locked_at'] = 'Prix verrouillé le';
$string['subfield_attempts'] = 'Tentatives';
$string['subfield_last_attempt'] = 'Dernière tentative';
$string['subfield_last_error'] = 'Dernière erreur';
$string['subfield_created_ip'] = 'IP de création';
$string['subfield_accept_language'] = 'Langue acceptée';
$string['subfield_http_referer'] = 'Référent HTTP';
$string['subfield_payment_link'] = 'Lien de paiement';
$string['subfield_response_json'] = 'Réponse JSON fournisseur';
$string['subfield_created_useragent'] = 'User-Agent de création';

$string['manage_user_subscriptions'] = 'Gérer les abonnements utilisateurs';
$string['all_plans'] = 'Tous les plans';
$string['filter_by_plan'] = 'Filtrer par plan';
$string['perpage'] = 'Résultats par page';
$string['filter'] = 'Filtrer';
$string['actions'] = 'Actions';
$string['no_subscriptions_found'] = 'Aucun abonnement trouvé.';
$string['confirm_delete_subscription'] = 'Confirmer la suppression';
$string['confirm_delete_subscription_body'] = 'Voulez-vous vraiment supprimer cet abonnement ? Cette action supprimera aussi les accès aux cours liés à ce plan.';
$string['subscription_deleted_successfully'] = 'Abonnement supprimé avec succès.';
$string['close'] = 'Fermer';
$string['delete'] = 'Supprimer';

$string['edit_user_subscription'] = 'Modifier l’abonnement utilisateur';
$string['subscription_summary'] = 'Résumé de l’abonnement';
$string['no_end_date'] = 'Abonnement sans date de fin';
$string['end_date_before_start_date'] = 'La date de fin ne peut pas être antérieure à la date de début.';
$string['subscription_updated_successfully'] = 'Abonnement mis à jour avec succès.';
$string['invalid_subscription_status'] = 'Statut d’abonnement invalide.';

$string['status_active'] = 'Actif';
$string['status_queued'] = 'En attente';
$string['status_inactive'] = 'Inactif';
$string['status_expired'] = 'Expiré';
$string['status_suspended'] = 'Suspendu';
$string['status_canceled'] = 'Annulé';
$string['status_replaced'] = 'Remplacé';
$string['status_pending'] = 'Paiement en attente';
$string['status_failed'] = 'Échec';
$string['status_error'] = 'Erreur';
$string['status_paid'] = 'Payé';
$string['status_completed'] = 'Terminé';

$string['existing_user'] = 'Utilisateur existant';
$string['new_user'] = 'Nouvel utilisateur';
$string['search_user_placeholder'] = 'Rechercher par nom, prénom ou email';
$string['manual_subscription_user_section'] = 'Utilisateur';
$string['manual_subscription_plan_section'] = 'Abonnement';
$string['missing_user_for_manual_subscription'] = 'Aucun utilisateur valide n’a été sélectionné ou créé.';
$string['not_set'] = 'Non renseigné';

$string['admin_dashboard'] = 'Administration CampusFR';
$string['admin_dashboard_intro'] = 'Retrouvez ici les principaux outils de gestion des abonnements, des plans et des produits digitaux.';

$string['admin_card_user_subscriptions_title'] = 'Abonnements utilisateurs';
$string['admin_card_user_subscriptions_desc'] = 'Consulter, filtrer, modifier ou supprimer les abonnements actifs et passés.';

$string['admin_card_add_subscription_title'] = 'Ajouter un abonnement';
$string['admin_card_add_subscription_desc'] = 'Créer manuellement un abonnement pour un utilisateur existant ou un nouvel utilisateur.';

$string['admin_card_import_csv_title'] = 'Import CSV';
$string['admin_card_import_csv_desc'] = 'Importer des abonnements en masse depuis un fichier CSV.';

$string['admin_card_plans_title'] = 'Plans et accès';
$string['admin_card_plans_desc'] = 'Gérer les plans, prix, traductions, scopes, entitlements et upgrades.';

$string['admin_card_digital_products_title'] = 'Produits digitaux';
$string['admin_card_digital_products_desc'] = 'Créer et gérer les fichiers PDF, guides et ressources vendues séparément.';

$string['admin_card_digital_purchases_title'] = 'Achats digitaux';
$string['admin_card_digital_purchases_desc'] = 'Consulter les achats digitaux et les informations de paiement associées.';

$string['admin_card_digital_stats_title'] = 'Statistiques digitales';
$string['admin_card_digital_stats_desc'] = 'Suivre les ventes, revenus et performances des produits digitaux.';
$string['date_format_placeholder'] = 'jj/mm/aaaa';
$string['digital_invalid_email'] = 'Veuillez saisir une adresse email valide.';
$string['subscription_period'] = 'Période';
$string['unlimited'] = 'Illimité';

$string['back_to_admin_dashboard'] = 'Retour au back-office Campus<small><sup>FR</sup></small>';

$string['crm_users'] = 'Utilisateurs CRM';

$string['crm_search_user_placeholder'] = 'Rechercher par nom, prénom ou email';
$string['crm_no_users_found'] = 'Aucun utilisateur trouvé.';
$string['crm_no_subscriptions'] = 'Aucun abonnement trouvé pour cet utilisateur.';
$string['crm_no_digital_purchases'] = 'Aucun achat digital trouvé pour cet utilisateur.';
$string['view_moodle_profile'] = 'Voir le profil Moodle';

$string['admin_card_crm_users_title'] = 'Utilisateurs CRM';
$string['admin_card_crm_users_desc'] = 'Rechercher un utilisateur et consulter sa fiche complète.';
$string['subscriptions'] = 'Abonnements';

$string['product'] = 'Produit';
$string['digital_purchases'] = 'Achats digitaux';
$string['crm_quick_actions'] = 'Actions rapides';
$string['crm_send_email'] = 'Envoyer un email';
$string['crm_reset_password'] = 'Réinitialiser le mot de passe';
$string['subject'] = 'Sujet';
$string['message'] = 'Message';
$string['send'] = 'Envoyer';
$string['crm_email_button_optional'] = 'Bouton optionnel';
$string['crm_email_button_label'] = 'Texte du bouton';
$string['crm_email_button_url'] = 'Lien du bouton';
$string['crm_email_button_url_required'] = 'Le lien du bouton est obligatoire si un texte de bouton est renseigné.';
$string['crm_email_button_label_required'] = 'Le texte du bouton est obligatoire si un lien est renseigné.';
$string['crm_email_subject_required'] = 'Le sujet de l’email est obligatoire.';
$string['crm_email_body_required'] = 'Le message de l’email est obligatoire.';
$string['crm_email_sent_successfully'] = 'Email envoyé avec succès.';
$string['crm_notify_user_by_email'] = 'Envoyer le nouveau mot de passe par email';
$string['crm_password_too_short'] = 'Le mot de passe doit contenir au moins 8 caractères.';
$string['crm_password_updated_successfully'] = 'Mot de passe mis à jour avec succès.';
$string['crm_reset_password_warning'] = 'Cette action remplace immédiatement le mot de passe de l’utilisateur.';
$string['crm_password_email_subject'] = 'Votre mot de passe Campus<small><sup>FR</sup></small> a été mis à jour';
$string['crm_password_email_intro'] = 'Bonjour {$a},';
$string['crm_password_email_password'] = 'Voici votre nouveau mot de passe :';
$string['crm_password_email_security'] = 'Pour des raisons de sécurité, nous vous conseillons de le modifier après votre prochaine connexion.';
$string['crm_login_button'] = 'Se connecter à CampusFR';
$string['crm_admin_history'] = 'Historique CRM';
$string['crm_no_admin_history'] = 'Aucune action enregistrée pour cet utilisateur.';
$string['admin_action'] = 'Action';
$string['admin_actor'] = 'Effectué par';
$string['details'] = 'Détails';
$string['date'] = 'Date';

$string['adminlog_email_custom_sent'] = 'Email personnalisé envoyé';
$string['adminlog_email_password_reset_notice_sent'] = 'Email de réinitialisation du mot de passe envoyé';
$string['adminlog_user_password_updated'] = 'Mot de passe modifié';
$string['crm_internal_notes'] = 'Notes internes';
$string['crm_note_placeholder'] = 'Ajouter une note interne visible uniquement par l’équipe…';
$string['crm_add_note'] = 'Ajouter la note';
$string['crm_no_notes'] = 'Aucune note interne pour cet utilisateur.';
$string['crm_note_required'] = 'La note ne peut pas être vide.';
$string['crm_note_added_successfully'] = 'Note ajoutée avec succès.';
$string['adminlog_user_note_added'] = 'Note interne ajoutée';

$string['crm_timeline'] = 'Timeline CRM';
$string['crm_timeline_empty'] = 'Aucun événement enregistré pour cet utilisateur.';
$string['crm_timeline_note_added'] = 'Note interne ajoutée';
$string['adminlog_subscription_created'] = 'Abonnement créé';
$string['adminlog_subscription_created_manual'] = 'Abonnement créé manuellement';
$string['adminlog_subscription_updated'] = 'Abonnement modifié';
$string['adminlog_subscription_deleted'] = 'Abonnement supprimé';
$string['adminlog_subscription_status_updated'] = 'Statut d’abonnement modifié';
$string['adminlog_subscription_dates_updated'] = 'Dates d’abonnement modifiées';

$string['adminlog_digital_purchase_created'] = 'Achat digital créé';
$string['adminlog_digital_purchase_paid'] = 'Achat digital payé';
$string['adminlog_digital_purchase_failed'] = 'Achat digital échoué';


$string['adminlog_payment_request_created'] = 'Demande de paiement créée';
$string['adminlog_payment_request_paid'] = 'Demande de paiement payée';
$string['adminlog_payment_request_failed'] = 'Demande de paiement échouée';
$string['adminlog_payment_request_cancelled'] = 'Demande de paiement annulée';

$string['adminlog_trial_started'] = 'Essai démarré';
$string['adminlog_trial_expired'] = 'Essai expiré';

$string['change_user'] = 'Changer d’utilisateur';

$string['crm_no_accessible_courses'] = 'Aucun cours accessible actuellement.';
$string['access'] = 'Accès';
$string['active'] = 'Actif';
$string['until'] = 'jusqu’au';

$string['digital_purchases_more_actions'] = 'Plus d’actions';
$string['digital_purchases_reconcile_done'] = 'Réconciliation terminée : {$a->reconciled} paiement(s) corrigé(s), {$a->failed} paiement(s) passé(s) en failed, {$a->skipped} ignoré(s), {$a->errors} erreur(s).';

$string['digital_purchases_export_filename'] = 'achats_pdf_campusfr';
$string['digital_purchases_export_sheet'] = 'Achats PDF';
$string['digital_purchases_export_slug'] = 'Slug';
$string['digital_purchases_export_file_classic'] = 'Fichier classique';
$string['digital_purchases_export_file_mobile'] = 'Fichier mobile';
$string['digital_purchases_export_transaction_id'] = 'Transaction ID';
$string['digital_purchases_export_session_id'] = 'Session ID';
$string['digital_purchases_export_pdf_email_sent'] = 'Email PDF envoyé';
$string['digital_purchases_export_receipt_sent'] = 'Reçu envoyé';
$string['digital_purchases_export_payment_date'] = 'Date paiement';
$string['digital_purchases_export_last_update'] = 'Dernière mise à jour';
$string['digital_purchases_export_link_expiration'] = 'Expiration lien';
$string['digital_purchases_export_download_classic'] = 'Lien téléchargement classique';
$string['digital_purchases_export_download_mobile'] = 'Lien téléchargement mobile';
$string['digital_purchases_export_last_error'] = 'Dernière erreur DB';
$string['no_expiration'] = 'Sans expiration';

$string['crm_timeline_digital_purchase'] = 'Achat digital';
$string['digital_purchase_details'] = 'Détails de l’achat digital';
$string['digital_purchase_resend_email'] = 'Renvoyer l’email';
$string['digital_purchase_resend_email_confirm'] = 'Voulez-vous vraiment renvoyer l’email de cet achat digital ?';
$string['digital_purchase_resend_email_logged_only'] = 'Action enregistrée. L’envoi réel sera branché à l’étape suivante.';
$string['digital_purchase_resend_email_success'] = 'Email d’accès renvoyé avec succès.';
$string['adminlog_digital_link_resent'] = 'Email d’accès digital renvoyé';

$string['digital_purchase_regenerate_token'] = 'Régénérer le lien';
$string['digital_purchase_extend_token'] = 'Prolonger le lien';
$string['digital_purchase_regenerate_token_confirm'] = 'Voulez-vous vraiment régénérer le lien de téléchargement ? L’ancien lien ne fonctionnera plus.';
$string['digital_purchase_extend_token_confirm'] = 'Voulez-vous prolonger ce lien de téléchargement de 30 jours ?';
$string['digital_purchase_token_regenerated_success'] = 'Lien de téléchargement régénéré avec succès.';
$string['digital_purchase_token_extended_success'] = 'Lien de téléchargement prolongé avec succès.';
$string['adminlog_digital_token_regenerated'] = 'Lien digital régénéré';
$string['adminlog_digital_token_extended'] = 'Lien digital prolongé';
$string['digital_purchase_link_expires'] = 'Expiration du lien';
$string['digital_purchase_old_token'] = 'Ancien token';
$string['digital_purchase'] = 'Achat digital';
$string['digital_payment_provider'] = 'Fournisseur de paiement';
$string['digital_session_id'] = 'Session / Order ID';
$string['digital_transaction_id'] = 'Transaction ID';
$string['digital_payment_link'] = 'Lien de paiement';
$string['digital_attempts'] = 'Tentatives';
$string['digital_last_attempt'] = 'Dernière tentative';
$string['digital_last_error'] = 'Dernière erreur';
$string['digital_created_ip'] = 'IP de création';
$string['digital_accept_language'] = 'Langue navigateur';
$string['digital_http_referer'] = 'Référent HTTP';
$string['digital_response_json'] = 'Réponse provider JSON';

$string['digital_check_provider_now'] = 'Vérifier provider maintenant';
$string['digital_check_provider_now_confirm'] = 'Voulez-vous vérifier maintenant le statut auprès du fournisseur de paiement ?';
$string['digital_provider_check_done'] = 'Vérification provider terminée : {$a->status}.';
$string['adminlog_digital_provider_checked'] = 'Provider vérifié manuellement';
$string['openlinkinnewwindow'] = 'Ouvrir le lien dans une nouvelle fenêtre';
$string['last_update'] = 'Dernière mise à jour';
$string['digital_product_total_purchases'] = 'Achats totaux';
$string['digital_product_paid_purchases'] = 'Achats payés';
$string['digital_product_total_revenue'] = 'Chiffre d’affaires';
$string['digital_product_error_count'] = 'Erreurs';
$string['digital_product_recent_purchases'] = 'Derniers achats';
$string['digital_product_no_recent_purchases'] = 'Aucun achat récent pour ce produit.';

$string['dashboard_team_card_title'] = 'Session équipe';
$string['dashboard_team_permissions'] = 'Droits actifs';
$string['dashboard_team_no_permissions'] = 'Aucun droit back-office détecté.';
$string['dashboard_permission_users'] = 'Utilisateurs CRM';
$string['dashboard_permission_subscriptions'] = 'Abonnements';
$string['dashboard_permission_digital'] = 'Produits et achats digitaux';
$string['dashboard_permission_payments'] = 'Paiements';
$string['dashboard_permission_configuration'] = 'Configuration';
$string['dashboard_today'] = 'Aujourd’hui';
$string['dashboard_stats_new_users'] = 'Nouveaux utilisateurs';
$string['dashboard_stats_digital_purchases'] = 'Achats digitaux';
$string['dashboard_stats_revenue'] = 'Chiffre d’affaires';
$string['dashboard_alerts'] = 'À traiter';
$string['dashboard_alert_pending_digital'] = 'Achats digitaux en attente';
$string['dashboard_alert_failed_digital'] = 'Achats digitaux échoués';
$string['dashboard_alert_email_errors'] = 'Achats avec erreur email/interne';
$string['dashboard_alert_expired_tokens'] = 'Liens digitaux expirés';
$string['dashboard_recent_activity'] = 'Activité récente';
$string['dashboard_no_recent_activity'] = 'Aucune activité récente.';

$string['crm_resend_welcome_email'] = 'Renvoyer bienvenue';
$string['crm_resend_access_email'] = 'Renvoyer accès';
$string['crm_resend_receipt'] = 'Renvoyer reçu';

$string['crm_welcome_email_resent_success'] = 'Email de bienvenue renvoyé.';
$string['crm_access_email_resent_success'] = 'Email d’accès renvoyé.';
$string['crm_receipt_resent_success'] = 'Reçu renvoyé.';
$string['crm_subscription_extended_success'] = 'Abonnement prolongé de {$a} jours.';

$string['crm_receipt_not_available'] = 'Aucun reçu disponible pour cet abonnement.';
$string['crm_timeline_course_purchase_paid'] = 'Achat de cours payé';
$string['crm_timeline_payment_request'] = 'Demande de paiement';
$string['crm_timeline_subscription_created'] = 'Abonnement créé';
$string['crm_timeline_trial_started'] = 'Essai démarré';
$string['payment_provider'] = 'Prestataire de paiement';
$string['transactionid'] = 'ID transaction';
$string['crm_timeline_email_receipt_sent'] = 'Reçu envoyé';
$string['crm_timeline_email_access_sent'] = 'Email d’accès envoyé';
$string['crm_timeline_email_welcome_sent'] = 'Email de bienvenue envoyé';

$string['crm_email_type_receipt'] = 'Reçu d’achat';
$string['crm_email_type_access'] = 'Accès abonnement';
$string['crm_email_type_welcome'] = 'Bienvenue';


$string['payment_request'] = 'Demande de paiement';
$string['type'] = 'Type';
$string['subscription_details'] = 'Détails de l’abonnement';
$string['crm_user_profile'] = 'Fiche CRM utilisateur';
$string['crm_no_payment_request_for_subscription'] = 'Aucune demande de paiement liée à cet abonnement.';
$string['view_details'] = 'Voir détail';
$string['admin_section_discounts'] = 'Remises';
$string['admin_section_provider'] = 'Informations fournisseur';
$string['admin_section_payment_failures'] = 'Échecs de paiement';
$string['admin_section_dates'] = 'Dates';

$string['admin_section_payment_request_identity'] = 'Identité / contact';
$string['admin_section_payment_status'] = 'Statut et opération';
$string['admin_section_amounts'] = 'Montants verrouillés';
$string['admin_section_links_tokens'] = 'Liens et jetons';
$string['admin_section_reminders_attempts'] = 'Relances et tentatives';
$string['admin_section_request_context'] = 'Contexte de création';

$string['discount_percent'] = 'Remise (%)';
$string['discount_amount'] = 'Montant de remise';
$string['discount_reason'] = 'Raison de la remise';

$string['phone'] = 'Téléphone';
$string['phone_country'] = 'Pays téléphone';
$string['operation'] = 'Opération';
$string['reference_subscription_id'] = 'Abonnement de référence';
$string['amount_minor'] = 'Montant mineur';

$string['locked_list_price'] = 'Prix catalogue verrouillé';
$string['locked_discount_percent'] = 'Remise verrouillée (%)';
$string['locked_discount_amount'] = 'Montant de remise verrouillé';
$string['locked_discount_reason'] = 'Raison de remise verrouillée';
$string['locked_final_price'] = 'Prix final verrouillé';
$string['locked_at'] = 'Verrouillé le';

$string['retry_token'] = 'Jeton de réessai';
$string['retry_expires'] = 'Expiration réessai';
$string['login_token'] = 'Jeton de connexion';
$string['login_token_expires'] = 'Expiration jeton de connexion';

$string['emailsent'] = 'Email envoyé';
$string['reminder_stage'] = 'Étape de relance';
$string['reminder1_at'] = 'Relance 1 envoyée le';
$string['reminder2_at'] = 'Relance 2 envoyée le';

$string['created_ip'] = 'IP de création';
$string['created_useragent'] = 'User-Agent de création';
$string['accept_language'] = 'Langue acceptée';
$string['http_referer'] = 'Référent HTTP';
$string['expiration_date'] = 'Date d’expiration';
$string['subscription'] = 'Abonnement';

$string['crm_timeline_expand_all'] = 'Tout déplier';
$string['crm_timeline_collapse_all'] = 'Tout replier';
$string['crm_timeline_view_details'] = 'Voir les détails';

$string['crm_timeline_recent'] = 'Historique récent (30 derniers jours)';
$string['crm_timeline_middle'] = 'Historique (31 à 90 jours)';
$string['crm_timeline_old'] = 'Ancien historique (plus de 90 jours)';

$string['crm_filter_purchases'] = 'Achats';
$string['crm_filter_emails'] = 'Emails';
$string['crm_filter_other'] = 'Autres';
$string['crm_timeline_by_actor'] = 'par {$a}';
$string['crm_timeline_by_admin'] = 'par un administrateur';
$string['crm_email_preview'] = 'Prévisualisation de l’email';
$string['recipient'] = 'Destinataire';

$string['crm_timeline_title'] = 'Timeline';
$string['crm_suspend_moodle_profile'] = 'Suspendre le profil Moodle';
$string['crm_activate_moodle_profile'] = 'Activer le profil Moodle';
$string['crm_moodle_profile_suspended'] = 'Profil Moodle suspendu.';
$string['crm_moodle_profile_activated'] = 'Profil Moodle activé.';
$string['adminlog_user_suspended'] = 'Profil Moodle suspendu';
$string['adminlog_user_reactivated'] = 'Profil Moodle réactivé';

$string['crm_stats_title'] = 'Résumé CRM';
$string['crm_accessible_courses'] = 'Cours accessibles';
$string['crm_total_spent'] = 'Dépenses totales';
$string['crm_last_activity'] = 'Dernière activité';

$string['crm_stats_subscriptions_hint'] = 'Abonnements liés au profil';
$string['crm_stats_digital_hint'] = 'Produits digitaux achetés';
$string['crm_stats_courses_hint'] = 'Cours actuellement accessibles';
$string['crm_stats_spent_hint'] = 'Total payé par devise';
$string['crm_stats_activity_hint'] = 'Dernier événement connu';

$string['crm_status'] = 'Statut CRM';
$string['crm_stats_status_hint'] = 'Situation actuelle du profil';
$string['crm_status_active_customer'] = 'Client actif';
$string['crm_status_trial'] = 'Essai';
$string['crm_status_former_customer'] = 'Ancien client';
$string['crm_status_suspended'] = 'Suspendu';
$string['crm_status_lead'] = 'Prospect';
$string['crm_status_unknown'] = 'Inconnu';

$string['command_center_type_user'] = 'Utilisateur';
$string['command_center_user_suspended'] = 'Compte suspendu';
$string['command_center_open'] = 'Ouvrir le Command Center';
$string['command_center_placeholder'] = 'Rechercher un utilisateur, un achat, un produit…';
$string['command_center_input_placeholder'] = 'Rechercher… ou taper > pour les actions';
$string['command_center_hint'] = 'Entrée pour ouvrir · Échap pour fermer · ↑ ↓ pour naviguer';
$string['command_center_close'] = 'Fermer le Command Center';
$string['command_center_empty'] = 'Aucun résultat';
$string['command_center_error'] = 'Erreur pendant la recherche';
$string['command_center_loading'] = 'Recherche en cours…';
$string['command_center_type_digital_product'] = 'Produit digital';
$string['command_center_type_digital_purchase'] = 'Achat digital';
$string['command_center_type_subscription'] = 'Abonnement';
$string['command_center_disabled'] = 'Désactivé';

$string['command_center_product_subtitle'] = '{$a->slug} · {$a->eur} EUR · {$a->rub} RUB';
$string['command_center_purchase_subtitle'] = '{$a->product} · {$a->status} · {$a->price} · {$a->date}';
$string['command_center_subscription_subtitle'] = '{$a->plan} · {$a->status} · {$a->period}';
$string['command_center_type_action'] = 'Action';

$string['command_action_dashboard_title'] = 'Ouvrir le dashboard';
$string['command_action_dashboard_subtitle'] = 'Retourner à la vue principale du CRM';


$string['command_action_products_title'] = 'Voir les produits digitaux';
$string['command_action_products_subtitle'] = 'Gérer les produits digitaux CampusFR';

$string['command_action_product_create_title'] = 'Créer un produit digital';
$string['command_action_product_create_subtitle'] = 'Ajouter un nouveau produit digital';

$string['command_action_purchases_title'] = 'Voir les achats digitaux';
$string['command_action_purchases_subtitle'] = 'Consulter les achats et paiements digitaux';


$string['command_center_initial'] = 'Tapez pour rechercher un utilisateur, un produit, un achat, un abonnement ou une action.';

$string['command_center_group_actions'] = 'Actions';
$string['command_center_group_users'] = 'Utilisateurs';
$string['command_center_group_products'] = 'Produits';
$string['command_center_group_purchases'] = 'Achats';
$string['command_center_group_subscriptions'] = 'Abonnements';

$string['command_center_action_open'] = 'Ouvrir';
$string['command_center_action_view'] = 'Voir';
$string['command_center_action_edit'] = 'Modifier';
$string['command_center_hint_navigate'] = 'naviguer';
$string['command_center_hint_open'] = 'ouvrir';
$string['command_center_hint_close'] = 'fermer';
$string['command_center_best_match'] = 'Meilleur';
$string['command_center_recent'] = 'Récents';
$string['command_center_key_enter'] = 'Entrée';
$string['command_center_key_escape'] = 'Échap';
$string['command_center_favorites'] = 'Favoris';
$string['command_center_favorite_toggle'] = 'Ajouter ou retirer des favoris';
$string['command_center_clear_recent'] = 'Vider';

$string['command_center_action_missing_url'] = 'Missing URL.';
$string['command_center_action_unknown'] = 'Unknown command.';
$string['command_center_action_error'] = 'The command could not be executed.';
$string['command_center_action_failed'] = 'Action failed.';
$string['command_center_action_missing_user'] = 'Utilisateur manquant.';
$string['command_center_action_missing_product'] = 'Produit manquant.';
$string['command_center_action_missing_purchase'] = 'Achat manquant.';
$string['command_center_action_missing_subscription'] = 'Abonnement manquant.';
$string['command_action_user_email_title'] = 'Envoyer un email à un utilisateur';
$string['command_action_user_email_subtitle'] = 'Ouvrir la liste des utilisateurs pour choisir un contact.';

$string['command_action_user_note_title'] = 'Ajouter une note utilisateur';
$string['command_action_user_note_subtitle'] = 'Ouvrir la liste des utilisateurs avant d’ajouter une note CRM.';

$string['command_action_purchase_resend_email_title'] = 'Renvoyer un email d’achat';
$string['command_action_purchase_resend_email_subtitle'] = 'Ouvrir les achats digitaux pour choisir l’achat concerné.';
$string['command_menu_user_email'] = 'Envoyer un email';
$string['command_menu_user_note'] = 'Ajouter une note';
$string['command_menu_user_reset_password'] = 'Réinitialiser le mot de passe';
$string['command_menu_purchase_resend_email'] = 'Renvoyer l’email';
$string['command_menu_purchase_regenerate_token'] = 'Régénérer le token';
$string['command_menu_purchase_extend_token'] = 'Prolonger le token';

$string['command_menu_product_edit'] = 'Modifier le produit';

$string['command_menu_subscription_open'] = 'Ouvrir l’abonnement';
$string['command_center_purchase_email_resent'] = 'L’email d’accès a été renvoyé.';
$string['command_confirm_purchase_resend_email'] = 'Renvoyer l’email d’accès pour cet achat ?';
$string['command_menu_purchase_check_provider'] = 'Vérifier le paiement';
$string['command_confirm_purchase_regenerate_token'] = 'Régénérer le token d’accès pour cet achat ?';
$string['command_confirm_purchase_extend_token'] = 'Prolonger le token d’accès pour cet achat ?';
$string['command_confirm_user_reset_password'] = 'Réinitialiser le mot de passe de cet utilisateur ?';
$string['command_action_users_title'] = 'Utilisateurs';
$string['command_action_users_subtitle'] = 'Ouvrir la gestion des utilisateurs CRM.';
$string['command_action_digital_purchases_title'] = 'Achats digitaux';
$string['command_action_digital_purchases_subtitle'] = 'Ouvrir les achats et ventes digitales.';
$string['command_action_digital_products_title'] = 'Produits digitaux';
$string['command_action_digital_products_subtitle'] = 'Ouvrir la gestion des produits digitaux.';
$string['command_action_subscriptions_title'] = 'Abonnements';
$string['command_action_subscriptions_subtitle'] = 'Ouvrir la gestion des abonnements et accès.';
$string['command_center_action_invalid_url'] = 'URL invalide.';
$string['command_center_confirm'] = 'Confirmer';
$string['command_center_cancel'] = 'Annuler';
$string['command_center_danger_confirm'] = 'Action sensible';
$string['command_intent_email_user'] = 'Envoyer un email à l’utilisateur';
$string['command_intent_note_user'] = 'Ajouter une note à l’utilisateur';
$string['command_intent_reset_user'] = 'Réinitialiser le mot de passe utilisateur';
$string['command_intent_user_quick_action_subtitle'] = 'Action rapide utilisateur depuis le Command Center.';

$string['command_intent_resend_purchase_email'] = 'Renvoyer l’email d’achat';
$string['command_intent_check_purchase'] = 'Vérifier le paiement';
$string['command_intent_purchase_quick_action_subtitle'] = 'Action rapide achat depuis le Command Center.';
$string['command_center_action_suggestion'] = 'Suggestion';

$string['command_suggestion_email_user_title'] = 'Envoyer un email utilisateur';
$string['command_suggestion_email_user_subtitle'] = 'Exemple : > email 12';

$string['command_suggestion_note_user_title'] = 'Ajouter une note utilisateur';
$string['command_suggestion_note_user_subtitle'] = 'Exemple : > note 12';

$string['command_suggestion_reset_user_title'] = 'Réinitialiser un mot de passe';
$string['command_suggestion_reset_user_subtitle'] = 'Exemple : > reset 12';

$string['command_suggestion_resend_purchase_title'] = 'Renvoyer un email d’achat';
$string['command_suggestion_resend_purchase_subtitle'] = 'Exemple : > resend 7';

$string['command_suggestion_check_purchase_title'] = 'Vérifier un paiement';
$string['command_suggestion_check_purchase_subtitle'] = 'Exemple : > check 7';

$string['command_action_user_email'] = 'Envoyer un email';
$string['command_action_user_note'] = 'Ajouter une note';
$string['command_action_user_reset_password'] = 'Réinitialiser le mot de passe';
$string['crm_section_overview'] = 'Vue d’ensemble';
$string['crm_section_quick_actions'] = 'Actions rapides';
$string['crm_section_subscriptions'] = 'Abonnements actifs et historiques';
$string['crm_section_digital_purchases'] = 'Achats digitaux';
$string['crm_section_courses'] = 'Cours accessibles';
$string['crm_section_notes'] = 'Notes internes';
$string['crm_note_empty'] = 'La note ne peut pas être vide.';
$string['crm_note_too_long'] = 'La note est trop longue.';
$string['crm_note_type_general'] = 'Général';
$string['crm_note_type_followup'] = 'À relancer';
$string['crm_note_type_payment'] = 'Paiement';
$string['crm_note_type_access'] = 'Accès';
$string['crm_note_type_sensitive'] = 'Sensible';
$string['crm_invalid_tag'] = 'Tag CRM invalide.';
$string['crm_tag_vip'] = 'VIP';
$string['crm_tag_followup'] = 'À relancer';
$string['crm_tag_payment_issue'] = 'Problème paiement';
$string['crm_tag_refund'] = 'Remboursement';
$string['crm_tag_manual_access'] = 'Accès manuel';
$string['crm_tag_sensitive'] = 'Cas sensible';
$string['crm_section_timeline'] = 'Timeline CRM';
$string['command_action_purchase_resend_email'] = 'Renvoyer email achat';
$string['task_run_crm_automations'] = 'Exécuter les automatisations CRM';
$string['crm_timeline_automation_executed'] = 'Automatisation CRM exécutée';
$string['crm_automations'] = 'Automatisations CRM';
$string['crm_automation_history'] = 'Historique des automatisations';
$string['crm_automation_trigger'] = 'Déclencheur';
$string['crm_automation_rule'] = 'Règle';
$string['enabled'] = 'Activée';
$string['disabled'] = 'Désactivée';
$string['priority'] = 'Priorité';

$string['command_action_automations_title'] = 'Automatisations CRM';
$string['command_action_automations_subtitle'] = 'Gérer les règles et workflows CRM';
$string['command_action_automation_history_title'] = 'Historique des automatisations';
$string['command_action_automation_history_subtitle'] = 'Voir les dernières exécutions CRM';

$string['crm_automation_no_rules'] = 'Aucune règle d’automatisation.';
$string['crm_automation_no_history'] = 'Aucun historique d’automatisation.';
$string['crm_automation_recent_history'] = 'Exécutions récentes';
$string['crm_automation_trial_expired'] = 'Essai expiré détecté';
$string['crm_automation_payment_failed'] = 'Paiement échoué détecté';
$string['crm_automation_digital_purchase_paid'] = 'Achat digital payé détecté';
$string['crm_automation_subscription_expired'] = 'Abonnement expiré détecté';
$string['crm_automation_note_added'] = 'Note CRM ajoutée';
$string['crm_automation_tag_added'] = 'Tag CRM ajouté';
$string['crm_automation_tag_removed'] = 'Tag CRM retiré';
$string['crm_automation_rules_count'] = '{$a} règle(s) configurée(s)';
$string['crm_automation_status_success'] = 'Succès';
$string['crm_automation_status_failed'] = 'Échec';
$string['crm_automation_status_skipped'] = 'Ignorée';

$string['crm_section_intelligence'] = 'Intelligence CRM';
$string['crm_intelligence_commercial_score'] = 'Score commercial';
$string['crm_intelligence_engagement_score'] = 'Engagement';
$string['crm_intelligence_risk_score'] = 'Risque';
$string['crm_intelligence_global_score'] = 'Score global';

$string['crm_intelligence_reason_active_customer'] = 'Client actif';
$string['crm_intelligence_reason_trial_user'] = 'Utilisateur en essai';
$string['crm_intelligence_reason_paid_digital_purchase'] = 'Achat digital payé';
$string['crm_intelligence_reason_high_value'] = 'Forte valeur';
$string['crm_intelligence_reason_recent_activity'] = 'Activité récente';
$string['crm_intelligence_reason_inactive'] = 'Inactif';
$string['crm_intelligence_reason_expired_subscription'] = 'Abonnement expiré';
$string['crm_intelligence_reason_suspended'] = 'Compte suspendu';
$string['crm_intelligence_level_very_low'] = 'Très faible';
$string['crm_intelligence_level_low'] = 'Faible';
$string['crm_intelligence_level_medium'] = 'Moyen';
$string['crm_intelligence_level_high'] = 'Élevé';
$string['crm_intelligence_level_excellent'] = 'Excellent';

$string['crm_intelligence_summary_very_low'] = 'Peu de signaux exploitables pour le moment.';
$string['crm_intelligence_summary_low'] = 'Profil encore peu prioritaire, mais à surveiller.';
$string['crm_intelligence_summary_medium'] = 'Profil intéressant avec plusieurs signaux utiles.';
$string['crm_intelligence_summary_high'] = 'Profil prioritaire avec un bon potentiel commercial.';
$string['crm_intelligence_summary_excellent'] = 'Profil très prioritaire avec une forte valeur CRM.';
$string['crm_intelligence_segments'] = 'Segments';
$string['crm_intelligence_opportunities'] = 'Opportunités';
$string['crm_intelligence_recommendations'] = 'Recommandations';

$string['crm_intelligence_segment_customer'] = 'Client';
$string['crm_intelligence_segment_trial'] = 'Essai';
$string['crm_intelligence_segment_hot_lead'] = 'Prospect chaud';
$string['crm_intelligence_segment_at_risk'] = 'À risque';
$string['crm_intelligence_segment_vip'] = 'VIP';
$string['crm_intelligence_segment_cold_user'] = 'Utilisateur froid';

$string['crm_intelligence_opportunity_trial_to_purchase'] = 'Conversion essai → achat';
$string['crm_intelligence_opportunity_cross_sell_digital_product'] = 'Cross-sell produit digital';
$string['crm_intelligence_opportunity_upgrade_subscription'] = 'Upgrade probable';
$string['crm_intelligence_opportunity_winback_expired_customer'] = 'Réactivation client expiré';

$string['crm_intelligence_recommendation_send_trial_conversion_email'] = 'Envoyer un email de conversion';
$string['crm_intelligence_recommendation_propose_upgrade'] = 'Proposer un upgrade';
$string['crm_intelligence_recommendation_send_winback_message'] = 'Envoyer un message de réactivation';
$string['crm_intelligence_recommendation_suggest_digital_product'] = 'Suggérer un produit digital';
$string['crm_intelligence_recommendation_review_user_manually'] = 'Analyser manuellement ce profil';
$string['crm_intelligence_recommendation_create_first_crm_note'] = 'Créer une première note CRM';

$string['crm_intelligence_dashboard_title'] = 'Intelligence CRM';
$string['crm_intelligence_dashboard_analysed_users'] = 'Utilisateurs analysés';
$string['crm_intelligence_dashboard_hot_leads'] = 'Prospects chauds';
$string['crm_intelligence_dashboard_at_risk'] = 'Profils à risque';
$string['crm_intelligence_dashboard_vip'] = 'VIP';
$string['crm_intelligence_dashboard_trial_opportunities'] = 'Opportunités essai';
$string['crm_intelligence_dashboard_upgrade_opportunities'] = 'Opportunités upgrade';
$string['crm_intelligence_dashboard_priority_profiles'] = 'Profils prioritaires';
$string['crm_intelligence_dashboard_no_priority_profiles'] = 'Aucun profil prioritaire détecté pour le moment.';
$string['crm_intelligence_alerts_title'] = 'Alertes CRM intelligentes';
$string['crm_intelligence_alerts_empty'] = 'Aucune alerte CRM importante pour le moment.';
$string['crm_intelligence_alert_open_profile'] = 'Ouvrir la fiche utilisateur';

$string['crm_intelligence_alert_high_risk_user'] = 'Utilisateur avec un risque CRM élevé';
$string['crm_intelligence_alert_trial_without_purchase'] = 'Essai actif sans achat détecté';
$string['crm_intelligence_alert_expired_without_reactivation'] = 'Abonnement expiré sans réactivation';
$string['crm_intelligence_alert_inactive_user'] = 'Utilisateur inactif depuis longtemps';
$string['crm_intelligence_alert_hot_opportunity'] = 'Opportunité commerciale chaude';
$string['command_crm_intelligence_dashboard'] = 'Dashboard Intelligence CRM';
$string['command_crm_intelligence_dashboard_desc'] = 'Voir les scores, alertes et recommandations CRM.';
$string['command_crm_alert_desc'] = 'Alerte CRM intelligente détectée.';

$string['crm_funnel_title'] = 'Funnel CRM';
$string['crm_funnel_users'] = 'Utilisateurs';
$string['crm_funnel_trials'] = 'Essais';
$string['crm_funnel_customers'] = 'Clients';
$string['crm_funnel_digital_customers'] = 'Clients digitaux';
$string['crm_funnel_expired_customers'] = 'Clients expirés';
$string['crm_funnel_trial_conversion_rate'] = 'Conversion essai → client';
$string['task_run_crm_intelligence_snapshot'] = 'Créer les snapshots Intelligence CRM';
$string['crm_trends_title'] = 'Tendances CRM';
$string['crm_trends_empty'] = 'Pas encore assez d’historique CRM.';
$string['crm_trend_label'] = 'Tendance';
$string['crm_trend_direction_up'] = 'En hausse';
$string['crm_trend_direction_down'] = 'En baisse';
$string['crm_trend_direction_stable'] = 'Stable';

$string['crm_explanation_active_customer'] = 'Client actif';
$string['crm_explanation_trial_user'] = 'Utilisateur en essai';
$string['crm_explanation_paid_digital_purchase'] = 'Achat digital payé';
$string['crm_explanation_high_value'] = 'Forte valeur client';
$string['crm_explanation_recent_activity'] = 'Activité récente';
$string['crm_explanation_inactive'] = 'Inactivité détectée';
$string['crm_explanation_expired_subscription'] = 'Abonnement expiré';
$string['crm_explanation_suspended'] = 'Compte suspendu';
$string['crm_explanation_no_crm_note'] = 'Aucune note CRM';
$string['crm_explanations_title'] = 'Pourquoi ce score ?';

$string['crm_daily_priorities_title'] = 'Priorités CRM du jour';
$string['crm_daily_priorities_empty'] = 'Aucune priorité CRM importante pour le moment.';

$string['command_crm_priority_desc'] = 'Priorité CRM du jour détectée.';
$string['crm_recommendation_action_permission_denied'] = 'Permission insuffisante pour exécuter cette action.';
$string['crm_recommendation_action_unsupported'] = 'Cette action recommandée n’est pas encore prise en charge.';
$string['crm_recommendation_action_open_user_profile'] = 'Ouvrir la fiche utilisateur';

$string['dashboard_period_today'] = 'Aujourd’hui';
$string['dashboard_period_week'] = 'Cette semaine';
$string['dashboard_period_month'] = 'Ce mois';
$string['dashboard_command_center_title'] = 'Centre de pilotage';
$string['crm_user_filter_all'] = 'Tous les utilisateurs';
$string['crm_user_filter_hot_lead'] = 'Prospects chauds';
$string['crm_user_filter_at_risk'] = 'Profils à risque';
$string['crm_user_filter_vip'] = 'Clients VIP';
$string['crm_user_filter_cold_user'] = 'Utilisateurs inactifs';
$string['crm_user_filter_trial_to_purchase'] = 'Essais à convertir';
$string['crm_user_filter_upgrade_subscription'] = 'Opportunités d’upgrade';
$string['crm_user_active_filter'] = 'Filtre actif : {$a}';

$string['dashboard_issues_title'] = 'À traiter';
$string['dashboard_issues_subtitle'] = 'Les points qui demandent une vérification ou une action admin.';

$string['dashboard_issue_pending_digital_title'] = 'Paiements digitaux en attente';
$string['dashboard_issue_pending_digital_desc'] = 'Demandes de paiement créées mais pas encore confirmées.';
$string['dashboard_issue_failed_digital_title'] = 'Paiements digitaux échoués';
$string['dashboard_issue_failed_digital_desc'] = 'Paiements refusés ou interrompus à vérifier.';


$string['dashboard_issue_open_queue'] = 'Ouvrir la file';
$string['dashboard_issue_review_failures'] = 'Vérifier';
$string['dashboard_issue_resend_emails'] = 'Renvoyer';
$string['dashboard_issue_regenerate_tokens'] = 'Régénérer';
$string['digital_purchase_filter_no_issue'] = 'Sans problème';
$string['digital_purchase_filter_issue_email_error'] = 'Erreur email';
$string['digital_purchase_filter_issue_expired_token'] = 'Lien expiré';
$string['digital_purchase_filter_clear_issue'] = 'Retirer le filtre problème';
$string['digital_purchase_action_resend_email'] = 'Renvoyer email';
$string['digital_purchase_action_regenerate_token'] = 'Régénérer lien';
$string['digital_purchase_action_extend_token'] = 'Prolonger lien';
$string['digital_purchase_action_email_resent'] = 'Email renvoyé avec succès.';
$string['digital_purchase_action_token_regenerated'] = 'Lien de téléchargement régénéré.';
$string['digital_purchase_action_failed'] = 'Action impossible : {$a}';
$string['digital_purchases_actions'] = 'Actions';

$string['digital_purchase_action_resend_email_confirm'] = 'Renvoyer l’email d’accès pour cet achat ?';
$string['digital_purchase_action_regenerate_token_confirm'] = 'Régénérer le lien de téléchargement ? L’ancien lien ne sera plus utilisable.';
$string['digital_purchase_action_extend_token_confirm'] = 'Prolonger ce lien de téléchargement de 30 jours ?';
$string['digital_purchase_access_action_requires_paid_status'] =
    'Cette action est réservée aux paiements confirmés.';
$string['digital_payment_help_email_subject'] =
    'Avez-vous rencontré une difficulté lors de votre paiement ?';

$string['digital_payment_help_email_body'] =
    '<p>Bonjour {$a->firstname},</p>
    <p>Nous avons constaté que votre tentative de paiement n’a pas été finalisée.</p>
    <p>Avez-vous rencontré une difficulté ou avez-vous besoin d’aide pour terminer votre achat ?</p>
    <p>Vous pouvez simplement répondre à ce message : notre équipe est là pour vous aider.</p>
    <p>Bien cordialement,<br>L’équipe CampusFR</p>';
$string['digital_purchase_action_contact_buyer'] = 'Contacter l’acheteur';
$string['digital_purchase_action_cancel'] = 'Annuler';
$string['digital_purchase_action_cancel_confirm'] =
    'Annuler cette tentative de paiement ? Elle disparaîtra des éléments à traiter.';
$string['digital_purchase_cancel_success'] =
    'La tentative de paiement a été annulée.';

$string['digital_purchase_cancel_invalid_status'] =
    'Impossible d’annuler cet achat : son statut actuel est {$a}.';
$string['digital_payment_help_email_context_title'] =
    'Relance pour une tentative de paiement non finalisée';

$string['digital_payment_help_email_context_description'] =
    'L’objet et le message ont été préremplis. Vous pouvez les personnaliser avant l’envoi.';

$string['digital_payment_help_purchase_user_mismatch'] =
    'Cet achat digital ne correspond pas à l’utilisateur sélectionné.';

$string['dashboard_issue_open_purchases'] = 'Voir les achats';
$string['dashboard_issue_review_queue'] = 'Examiner la file';

$string['dashboard_issues_empty_title'] = 'Tout est sous contrôle';
$string['dashboard_issues_empty_description'] =
    'Aucun paiement ni accès digital ne demande votre intervention.';

$string['dashboard_issue_email_error_title'] =
    'Emails d’accès non envoyés';

$string['dashboard_issue_email_error_desc'] =
    'Achats payés pour lesquels l’email d’accès n’a pas pu être envoyé.';

$string['dashboard_issue_expired_token_title'] =
    'Liens de téléchargement expirés';

$string['dashboard_issue_expired_token_desc'] =
    'Achats payés dont le lien de téléchargement est arrivé à expiration.';

$string['admin_event_unknown'] = 'Événement administratif';

$string['admin_event_email_custom_sent'] =
    'Email personnalisé envoyé';

$string['admin_event_digital_purchase_created'] =
    'Tentative de paiement digital créée';

$string['admin_event_digital_purchase_paid'] =
    'Paiement digital confirmé';

$string['admin_event_digital_purchase_failed'] =
    'Paiement digital échoué';

$string['admin_event_digital_purchase_cancelled'] =
    'Tentative de paiement annulée';

$string['admin_event_digital_link_resent'] =
    'Email d’accès digital renvoyé';

$string['admin_event_digital_token_regenerated'] =
    'Lien de téléchargement régénéré';

$string['admin_event_digital_token_extended'] =
    'Lien de téléchargement prolongé';

$string['admin_event_user_suspended'] =
    'Profil Moodle suspendu';

$string['admin_event_user_reactivated'] =
    'Profil Moodle réactivé';

$string['crm_help_title'] = 'Centre d’aide CRM';
$string['crm_help_subtitle'] =
    'Découvrez les outils du CRM CampusFR et trouvez rapidement la réponse à vos questions.';

$string['crm_help_search_placeholder'] =
    'Rechercher dans la documentation…';

$string['crm_help_search_results'] =
    'Résultats pour « {$a} »';

$string['crm_help_no_results'] =
    'Aucun article ne correspond à votre recherche.';

$string['crm_help_article_count'] = '{$a} article(s)';

$string['crm_help_category_getting_started'] = 'Bien démarrer';
$string['crm_help_category_getting_started_desc'] =
    'Comprendre le CRM et prendre rapidement ses repères.';

$string['crm_help_category_daily_work'] = 'Travail quotidien';
$string['crm_help_category_daily_work_desc'] =
    'Les outils essentiels pour piloter l’activité au quotidien.';

$string['crm_help_category_users'] = 'Utilisateurs CRM';
$string['crm_help_category_users_desc'] =
    'Recherche, profils, filtres, segments, tags et actions utilisateur.';

$string['crm_help_category_digital'] = 'Achats digitaux';
$string['crm_help_category_digital_desc'] =
    'Paiements, accès, emails, liens et traitement des incidents.';

$string['crm_help_category_automation'] = 'Automatisations';
$string['crm_help_category_automation_desc'] =
    'Créer et comprendre les règles d’automatisation CRM.';

$string['crm_help_category_intelligence'] = 'Intelligence CRM';
$string['crm_help_category_intelligence_desc'] =
    'Scores, risques, opportunités, recommandations et priorités.';

$string['crm_help_category_shortcuts'] = 'Raccourcis';
$string['crm_help_category_shortcuts_desc'] =
    'Gagner du temps avec le Command Center et le clavier.';

$string['crm_help_category_developer'] = 'Documentation développeur';
$string['crm_help_category_developer_desc'] =
    'Architecture interne, conventions et extension du CRM.';

$string['crm_help_article_overview_title'] = 'Découvrir le CRM CampusFR';
$string['crm_help_article_overview_summary'] =
    'Vue d’ensemble du Dashboard, du Command Center et des modules CRM.';
$string['crm_help_article_overview_content'] =
    '<p>Le CRM CampusFR centralise les utilisateurs, abonnements, achats digitaux, automatisations et outils d’intelligence.</p>';

$string['crm_help_article_dashboard_periods_title'] =
    'Utiliser les périodes du Dashboard';
$string['crm_help_article_dashboard_periods_summary'] =
    'Basculer entre aujourd’hui, cette semaine et ce mois.';
$string['crm_help_article_dashboard_periods_content'] =
    '<p>Les périodes du Dashboard permettent de recalculer les KPI sur l’intervalle sélectionné.</p>';

$string['crm_help_article_user_filters_title'] =
    'Filtrer les utilisateurs avec l’Intelligence CRM';
$string['crm_help_article_user_filters_summary'] =
    'Afficher les prospects chauds, profils à risque, VIP et opportunités.';
$string['crm_help_article_user_filters_content'] =
    '<p>Les filtres Intelligence permettent d’ouvrir directement les segments détectés par le moteur CRM.</p>';

$string['crm_help_article_digital_issues_title'] =
    'Traiter les incidents de paiement digital';
$string['crm_help_article_digital_issues_summary'] =
    'Comprendre les paiements pending, failed, cancelled et les problèmes d’accès.';
$string['crm_help_article_digital_issues_content'] =
    '<p>Un paiement non confirmé ne doit jamais générer d’accès. L’administrateur peut contacter l’acheteur ou annuler la tentative.</p>';

$string['crm_help_article_shortcuts_title'] =
    'Utiliser le Command Center';
$string['crm_help_article_shortcuts_summary'] =
    'Rechercher et lancer rapidement les principales actions CRM.';
$string['crm_help_article_shortcuts_content'] =
    '<p>Utilisez Ctrl ou Cmd + K pour ouvrir rapidement le Command Center.</p>';

$string['crm_help_article_developer_architecture_title'] =
    'Architecture du CRM';
$string['crm_help_article_developer_architecture_summary'] =
    'Comprendre les repositories, services, renderers et règles de sécurité.';
$string['crm_help_article_developer_architecture_content'] =
    '<p>La SQL reste dans les repositories, la logique métier dans les services et le rendu dans les renderers.</p>';

$string['crm_help_all_categories'] = 'Toutes les catégories';
$string['crm_help_category_empty'] =
    'Aucun article n’est encore disponible dans cette catégorie.';
$string['crm_help_read_article'] = 'Lire l’article';
$string['crm_help_home'] = 'Centre d’aide';
$string['crm_help_article_navigation'] =
    'Navigation de la documentation';

$string['crm_help_article_not_found'] =
    'L’article demandé est introuvable.';
$string['crm_help_article_read_error'] =
    'Impossible de lire le contenu de cet article.';
$string['crm_help_article_content_missing'] =
    'Le contenu de l’article « {$a} » est introuvable.';
$string['crm_help_content_directory_missing'] =
    'Le dossier de documentation CRM est introuvable.';

$string['crm_context_help_trigger'] =
    'Aide sur cette page';

$string['crm_context_help_title'] =
    'Besoin d’aide ?';

$string['crm_context_help_description'] =
    'Ces articles correspondent à l’écran que vous consultez.';

$string['crm_context_help_empty'] =
    'Aucun article contextuel n’est encore disponible.';

$string['crm_context_help_open_center'] =
    'Ouvrir le Centre d’aide';

$string['admin_dashboard_description'] =
    'Pilotez l’activité CRM, suivez les indicateurs et traitez les priorités.';

$string['crm_users_explorer_description'] =
    'Recherchez, filtrez et analysez les utilisateurs du CRM.';

$string['digital_purchases_help_description'] =
    'Consultez les paiements digitaux, traitez les incidents et gérez les accès.';

$string['crm_user_profile_help_description'] =
    'Consultez l’historique, les abonnements, les achats et les recommandations de cet utilisateur.';

$string['crm_onboarding_title'] =
    'Prendre en main le CRM';

$string['crm_onboarding_description'] =
    'Suivez ces étapes pour découvrir les principaux outils et devenir rapidement autonome.';

$string['crm_onboarding_progress_label'] =
    '{$a->completed} étape(s) sur {$a->total}';

$string['crm_onboarding_mark_complete'] =
    'Marquer comme terminé';

$string['crm_onboarding_mark_incomplete'] =
    'Rouvrir';

$string['crm_onboarding_complete_title'] =
    'Onboarding terminé';

$string['crm_onboarding_complete_desc'] =
    'Vous avez découvert les principales fonctionnalités du CRM CampusFR.';

$string['crm_onboarding_restart'] =
    'Recommencer la checklist';

$string['crm_onboarding_restart_confirm'] =
    'Réinitialiser toute votre progression d’onboarding ?';

$string['crm_onboarding_reset_success'] =
    'La progression de l’onboarding a été réinitialisée.';

$string['crm_onboarding_invalid_step'] =
    'Cette étape d’onboarding est inconnue.';

$string['crm_onboarding_invalid_action'] =
    'Cette action d’onboarding est invalide.';

$string['crm_onboarding_step_dashboard_title'] =
    'Découvrir le Dashboard';

$string['crm_onboarding_step_dashboard_desc'] =
    'Consulter les KPI, priorités et éléments à traiter.';

$string['crm_onboarding_step_command_center_title'] =
    'Essayer le Command Center';

$string['crm_onboarding_step_command_center_desc'] =
    'Rechercher rapidement un utilisateur ou une action.';

$string['crm_onboarding_step_users_title'] =
    'Explorer les utilisateurs';

$string['crm_onboarding_step_users_desc'] =
    'Utiliser la recherche et ouvrir une fiche CRM.';

$string['crm_onboarding_step_intelligence_title'] =
    'Découvrir les filtres Intelligence';

$string['crm_onboarding_step_intelligence_desc'] =
    'Afficher les prospects chauds et les profils à risque.';

$string['crm_onboarding_step_digital_title'] =
    'Consulter les achats digitaux';

$string['crm_onboarding_step_digital_desc'] =
    'Comprendre les statuts et les actions disponibles.';

$string['crm_onboarding_step_automations_title'] =
    'Découvrir les automatisations';

$string['crm_onboarding_step_automations_desc'] =
    'Consulter les règles et leur historique.';

$string['crm_onboarding_step_help_title'] =
    'Parcourir le Centre d’aide';

$string['crm_onboarding_step_help_desc'] =
    'Retrouver la documentation fonctionnelle du CRM.';

$string['crm_onboarding_step_architecture_title'] =
    'Lire les règles d’architecture';

$string['crm_onboarding_step_architecture_desc'] =
    'Comprendre les conventions techniques du plugin.';

$string['crm_help_guides_title'] = 'Guides pratiques';
$string['crm_help_guides_description'] =
    'Suivez des parcours simples pour accomplir les principales tâches du CRM.';

$string['crm_help_guide_step_count'] = '{$a} étapes';
$string['crm_help_guide_progress'] =
    '{$a->completed} étape(s) sur {$a->total}';
$string['crm_help_guide_complete_step'] = 'Étape terminée';
$string['crm_help_guide_reopen_step'] = 'Rouvrir';
$string['crm_help_guide_complete'] =
    'Vous avez terminé ce guide.';
$string['crm_help_guide_reset'] = 'Réinitialiser le guide';
$string['crm_help_guide_reset_confirm'] =
    'Réinitialiser la progression de ce guide ?';
$string['crm_help_guide_reset_success'] =
    'La progression du guide a été réinitialisée.';
$string['crm_help_guide_not_found'] =
    'Le guide demandé est introuvable.';
$string['crm_help_guide_step_not_found'] =
    'Cette étape du guide est introuvable.';
$string['crm_help_guide_invalid_action'] =
    'Cette action de guide est invalide.';

$string['crm_help_guide_dashboard_title'] =
    'Prendre en main le Dashboard';
$string['crm_help_guide_dashboard_desc'] =
    'Comprendre les indicateurs, priorités et éléments à traiter.';
$string['crm_help_guide_dashboard_period_title'] =
    'Choisir une période';
$string['crm_help_guide_dashboard_period_desc'] =
    'Basculez entre aujourd’hui, cette semaine et ce mois.';
$string['crm_help_guide_dashboard_kpis_title'] =
    'Lire les indicateurs';
$string['crm_help_guide_dashboard_kpis_desc'] =
    'Analysez les nouveaux utilisateurs, abonnements, achats et revenus.';
$string['crm_help_guide_dashboard_issues_title'] =
    'Examiner les éléments à traiter';
$string['crm_help_guide_dashboard_issues_desc'] =
    'Ouvrez les files de paiements ou accès qui demandent une intervention.';
$string['crm_help_guide_dashboard_priority_title'] =
    'Ouvrir un profil prioritaire';
$string['crm_help_guide_dashboard_priority_desc'] =
    'Consultez les raisons du score et choisissez une action adaptée.';
$string['crm_help_guide_open_dashboard'] =
    'Ouvrir le Dashboard';

$string['crm_help_guide_digital_title'] =
    'Traiter un paiement digital';
$string['crm_help_guide_digital_desc'] =
    'Vérifier, contacter ou annuler une tentative de paiement.';
$string['crm_help_guide_digital_open_title'] =
    'Ouvrir la file des paiements';
$string['crm_help_guide_digital_open_desc'] =
    'Commencez par les paiements pending ou failed.';
$string['crm_help_guide_digital_verify_title'] =
    'Vérifier le statut réel';
$string['crm_help_guide_digital_verify_desc'] =
    'Confirmez que le paiement n’a pas été validé avant toute action.';
$string['crm_help_guide_digital_contact_title'] =
    'Contacter l’acheteur';
$string['crm_help_guide_digital_contact_desc'] =
    'Utilisez le message prérempli pour proposer votre aide.';
$string['crm_help_guide_digital_cancel_title'] =
    'Annuler la tentative';
$string['crm_help_guide_digital_cancel_desc'] =
    'Annulez uniquement les tentatives pending ou failed devenues inutiles.';
$string['crm_help_guide_open_pending'] =
    'Voir les paiements pending';

$string['crm_help_guide_hot_lead_title'] =
    'Analyser un prospect chaud';
$string['crm_help_guide_hot_lead_desc'] =
    'Comprendre son score et choisir la meilleure prochaine action.';
$string['crm_help_guide_hot_lead_open_title'] =
    'Ouvrir le segment';
$string['crm_help_guide_hot_lead_open_desc'] =
    'Affichez la liste des prospects chauds.';
$string['crm_help_guide_hot_lead_score_title'] =
    'Examiner le score';
$string['crm_help_guide_hot_lead_score_desc'] =
    'Consultez les facteurs qui ont augmenté son potentiel.';
$string['crm_help_guide_hot_lead_history_title'] =
    'Lire la timeline';
$string['crm_help_guide_hot_lead_history_desc'] =
    'Vérifiez ses achats, abonnements et interactions récentes.';
$string['crm_help_guide_hot_lead_action_title'] =
    'Choisir une action';
$string['crm_help_guide_hot_lead_action_desc'] =
    'Contactez le prospect uniquement si le contexte le justifie.';
$string['crm_help_guide_open_hot_leads'] =
    'Afficher les prospects chauds';

$string['crm_help_guide_command_title'] =
    'Maîtriser le Command Center';
$string['crm_help_guide_command_desc'] =
    'Rechercher et naviguer rapidement dans le CRM.';
$string['crm_help_guide_command_open_title'] =
    'Ouvrir le Command Center';
$string['crm_help_guide_command_open_desc'] =
    'Utilisez Ctrl ou Cmd + K.';
$string['crm_help_guide_command_search_title'] =
    'Rechercher une entité';
$string['crm_help_guide_command_search_desc'] =
    'Recherchez un utilisateur, un achat, un abonnement ou un produit.';
$string['crm_help_guide_command_keyboard_title'] =
    'Naviguer au clavier';
$string['crm_help_guide_command_keyboard_desc'] =
    'Utilisez les flèches, Entrée et Échap.';
$string['crm_help_guide_command_favorites_title'] =
    'Utiliser les favoris et récents';
$string['crm_help_guide_command_favorites_desc'] =
    'Retrouvez rapidement les commandes utilisées fréquemment.';

$string['crm_help_guide_profile_title'] =
    'Comprendre une fiche utilisateur';
$string['crm_help_guide_profile_desc'] =
    'Analyser toutes les informations avant de prendre une décision.';
$string['crm_help_guide_profile_identity_title'] =
    'Vérifier l’identité';
$string['crm_help_guide_profile_identity_desc'] =
    'Contrôlez les coordonnées et l’état du compte.';
$string['crm_help_guide_profile_timeline_title'] =
    'Lire la timeline';
$string['crm_help_guide_profile_timeline_desc'] =
    'Reconstituez les événements importants dans l’ordre chronologique.';
$string['crm_help_guide_profile_intelligence_title'] =
    'Comprendre l’Intelligence CRM';
$string['crm_help_guide_profile_intelligence_desc'] =
    'Consultez le score, le segment et les recommandations.';
$string['crm_help_guide_profile_action_title'] =
    'Effectuer une action';
$string['crm_help_guide_profile_action_desc'] =
    'Choisissez une action rapide adaptée au contexte.';
$string['crm_context_help_articles_title'] =
    'Articles recommandés';

$string['crm_context_help_guides_title'] =
    'Guides pratiques';
$string['command_help_center_title'] =
    'Ouvrir le Centre d’aide CRM';

$string['command_help_center_subtitle'] =
    'Documentation, guides et aide contextuelle';
$string['crm_help_diagnostics_title'] =
    'Diagnostic du Centre d’aide';

$string['crm_help_diagnostics_description'] =
    'Vérifiez la cohérence des articles, traductions, fichiers Markdown, catégories et guides du CRM.';

$string['crm_help_diagnostics_successes'] =
    'Validations réussies';

$string['crm_help_diagnostics_warnings'] =
    'Avertissements';

$string['crm_help_diagnostics_errors'] =
    'Erreurs';

$string['crm_help_diagnostics_valid'] =
    'Le Centre d’aide est valide et prêt à être utilisé.';

$string['crm_help_diagnostics_invalid'] =
    'Le Centre d’aide contient des erreurs qui doivent être corrigées.';

$string['crm_help_open_diagnostics'] =
    'Vérifier la documentation';

$string['crm_user_sort_name_asc'] = 'Nom : A à Z';
$string['crm_user_sort_name_desc'] = 'Nom : Z à A';
$string['crm_user_sort_score_desc'] = 'Score CRM le plus élevé';
$string['crm_user_sort_risk_desc'] = 'Risque le plus élevé';
$string['crm_user_sort_last_access_desc'] = 'Dernière activité récente';
$string['crm_user_sort_created_desc'] = 'Inscription la plus récente';

$string['crm_user_account_status_all'] = 'Tous les comptes';
$string['crm_user_account_status_active'] = 'Comptes actifs';
$string['crm_user_account_status_suspended'] = 'Comptes suspendus';

$string['crm_user_account_active'] = 'Actif';
$string['crm_user_account_suspended'] = 'Suspendu';
$string['crm_user_account_status'] = 'État du compte';

$string['crm_user_explorer_result_count'] = 'utilisateur(s)';
$string['crm_user_explorer_active_filters'] = '{$a} filtre(s) actif(s)';
$string['crm_user_explorer_clear_filters'] = 'Réinitialiser les filtres';
$string['crm_user_explorer_search_label'] = 'Recherche';
$string['crm_user_country_all'] = 'Tous les pays';
$string['crm_user_tag_all'] = 'Tous les tags';
$string['crm_user_sort_label'] = 'Trier par';
$string['crm_user_per_page'] = 'Par page';
$string['crm_user_apply_filters'] = 'Appliquer les filtres';


$string['crm_user_explorer_empty_title'] = 'Aucun utilisateur trouvé';
$string['crm_user_explorer_empty_description'] =
    'Modifiez les filtres ou la recherche pour afficher d’autres profils.';

$string['crm_user_score_level_unknown'] = 'Non analysé';
$string['crm_user_score_level_very_low'] = 'Très faible';
$string['crm_user_score_level_low'] = 'Faible';
$string['crm_user_score_level_medium'] = 'Moyen';
$string['crm_user_score_level_high'] = 'Élevé';
$string['crm_user_score_level_excellent'] = 'Excellent';
$string['country'] = 'Pays';
$string['crm_user_tags'] = 'Tags';

$string['crm_user_column_user'] = 'Utilisateur';
$string['crm_user_column_tags'] = 'Tags';
$string['crm_user_column_score'] = 'Score CRM';
$string['crm_user_column_risk'] = 'Risque';
$string['crm_user_column_intelligence'] = 'Intelligence';
$string['crm_user_column_subscriptions'] = 'Abonnements';
$string['crm_user_column_purchases'] = 'Achats digitaux';
$string['crm_user_column_country'] = 'Pays';
$string['crm_user_column_registered'] = 'Inscription';
$string['crm_user_column_last_access'] = 'Dernière activité';

$string['crm_user_configure_columns'] = 'Configurer les colonnes';
$string['crm_user_columns_saved'] = 'Les colonnes ont été enregistrées.';
$string['crm_user_columns_reset'] = 'Les colonnes par défaut ont été restaurées.';

$string['crm_user_save_view'] = 'Enregistrer la vue';
$string['crm_user_view_name_placeholder'] = 'Nom de la vue';
$string['crm_user_view_name_required'] = 'Le nom de la vue est obligatoire.';
$string['crm_user_view_limit_reached'] =
    'Vous ne pouvez pas enregistrer plus de {$a} vues.';
$string['crm_user_view_saved'] = 'La vue a été enregistrée.';
$string['crm_user_view_deleted'] = 'La vue a été supprimée.';
$string['crm_user_view_delete'] = 'Supprimer cette vue';
$string['crm_user_view_delete_confirm'] =
    'Supprimer définitivement cette vue enregistrée ?';

$string['crm_user_explorer_invalid_action'] =
    'Cette action du User Explorer est invalide.';

$string['crm_user_advanced_filters'] = 'Filtres avancés';
$string['crm_user_score_min'] = 'Score minimum';
$string['crm_user_score_max'] = 'Score maximum';
$string['crm_user_risk_min'] = 'Risque minimum';
$string['crm_user_risk_max'] = 'Risque maximum';

$string['crm_user_presence_all'] = 'Tous';
$string['crm_user_presence_yes'] = 'Oui';
$string['crm_user_presence_no'] = 'Non';

$string['crm_user_has_subscription'] = 'Possède un abonnement';
$string['crm_user_has_purchase'] = 'Possède un achat digital';

$string['crm_user_activity_filter'] = 'Dernière activité';
$string['crm_user_activity_all'] = 'Toute activité';
$string['crm_user_activity_7days'] = 'Au cours des 7 derniers jours';
$string['crm_user_activity_30days'] = 'Au cours des 30 derniers jours';
$string['crm_user_activity_90days'] = 'Au cours des 90 derniers jours';
$string['crm_user_activity_never'] = 'Jamais connecté';

$string['crm_user_export_csv'] = 'Exporter en CSV';

$string['digital_product_not_found_redirect'] =
    'Ce produit n’est pas disponible. Vous avez été redirigé vers la boutique.';

$string['command_center_group_intents'] = 'Commandes';
$string['command_center_action_execute'] = 'Exécuter';

$string['command_intent_open_user'] = 'Ouvrir l’utilisateur';
$string['command_intent_open_purchase'] = 'Ouvrir l’achat';
$string['command_intent_open_product'] = 'Ouvrir le produit';
$string['command_intent_open_subscription'] = 'Ouvrir l’abonnement';
$string['command_intent_direct_entity_subtitle'] =
    'Commande directe depuis le Command Center.';

$string['crm_inbox_navigation'] = 'CRM Inbox';
$string['crm_inbox_title'] = 'Boîte de réception CRM';
$string['crm_inbox_foundation_ready'] =
    'Le socle de la CRM Inbox est installé. Aucun compte email n’est encore connecté.';
$string['crm_inbox_no_account_configured'] =
    'La configuration OVH et la synchronisation IMAP seront ajoutées lors des prochaines étapes.';

$string['privacy:metadata:inbox'] =
    'La CRM Inbox conserve les messages du support et leurs liens éventuels avec les utilisateurs CampusFR.';
$string['privacy:metadata:inbox:email'] =
    'Adresse email du participant au message.';
$string['privacy:metadata:inbox:name'] =
    'Nom affiché du participant au message.';
$string['privacy:metadata:inbox:message'] =
    'Contenu du message reçu ou envoyé.';
$string['privacy:metadata:inbox:userid'] =
    'Utilisateur Moodle éventuellement rattaché au contact Inbox.';

$string['crm_inbox_credential_missing'] =
    'Les identifiants Inbox « {$a} » sont absents de la configuration Moodle.';
$string['crm_inbox_credential_invalid'] =
    'La configuration des identifiants Inbox « {$a} » est invalide.';
$string['crm_inbox_credential_field_missing'] =
    'Le champ « {$a->field} » est absent des identifiants Inbox « {$a->key} ».';

$string['crm_inbox_account_disabled'] =
    'Le compte CRM Inbox est désactivé.';
$string['crm_inbox_account_no_credential'] =
    'Aucune référence d’identifiants n’est configurée pour ce compte Inbox.';
$string['crm_inbox_account_not_found'] =
    'Le compte CRM Inbox demandé est introuvable.';

$string['crm_inbox_imap_configuration_missing'] =
    'La configuration IMAP du compte Inbox est absente.';
$string['crm_inbox_imap_field_missing'] =
    'Le champ IMAP obligatoire « {$a} » est absent.';
$string['crm_inbox_imap_extension_missing'] =
    'L’extension PHP IMAP n’est pas installée ou activée sur le serveur.';

$string['task_sync_crm_inbox'] =
    'Synchroniser la CRM Inbox';
$string['task_reconcile_crm_inbox_contacts'] =
    'Rattacher les contacts de la CRM Inbox aux utilisateurs';

$string['crm_inbox_empty'] =
    'Aucune conversation ne correspond à ces critères.';
$string['crm_inbox_search'] = 'Rechercher';
$string['crm_inbox_status'] = 'Statut';
$string['crm_inbox_priority'] = 'Priorité';
$string['crm_inbox_assignment'] = 'Assignation';
$string['crm_inbox_assignment_mine'] = 'Mes conversations';
$string['crm_inbox_assignment_unassigned'] = 'Non assignées';
$string['crm_inbox_assignment_team'] = 'Assignées à une équipe';

$string['crm_inbox_status_open'] = 'Ouvert';
$string['crm_inbox_status_pending'] = 'En attente';
$string['crm_inbox_status_resolved'] = 'Résolu';
$string['crm_inbox_status_closed'] = 'Fermé';
$string['crm_inbox_status_spam'] = 'Spam';

$string['crm_inbox_priority_low'] = 'Faible';
$string['crm_inbox_priority_normal'] = 'Normale';
$string['crm_inbox_priority_high'] = 'Élevée';
$string['crm_inbox_priority_urgent'] = 'Urgente';

$string['crm_inbox_unknown_contact'] = 'Contact inconnu';
$string['crm_inbox_no_subject'] = 'Sans objet';
$string['crm_inbox_unread_count'] =
    '{$a} message(s) non lu(s)';
$string['crm_inbox_back'] = 'Retour à la boîte de réception';
$string['crm_inbox_matched_user'] =
    'Utilisateur CampusFR : {$a}';
$string['crm_inbox_external_contact'] =
    'Contact externe non inscrit';

$string['crm_inbox_reply'] = 'Répondre';
$string['crm_inbox_save_draft'] =
    'Enregistrer le brouillon';
$string['crm_inbox_send'] = 'Envoyer';
$string['crm_inbox_draft_saved'] =
    'Le brouillon a été enregistré.';
$string['crm_inbox_reply_sent'] =
    'La réponse a été envoyée.';
$string['crm_inbox_send_failed'] =
    'La réponse n’a pas pu être envoyée : {$a}';
$string['crm_inbox_invalid_recipient'] =
    'Le destinataire de cette conversation est invalide.';

$string['crm_inbox_direction_inbound'] = 'Reçu';
$string['crm_inbox_direction_outbound'] = 'Envoyé';
$string['crm_inbox_message_status_draft'] = 'Brouillon';

$string['crm_inbox_thread_not_found'] =
    'Cette conversation Inbox est introuvable.';
$string['crm_inbox_archive'] = 'Archiver';
$string['crm_inbox_move_to_trash'] =
    'Mettre à la corbeille';
$string['crm_inbox_trash_confirm'] =
    'Déplacer cette conversation vers la corbeille du fournisseur ?';
$string['crm_inbox_moved_to_trash'] =
    'La conversation a été déplacée vers la corbeille.';
$string['crm_inbox_deleted_locally'] =
    'La conversation a été supprimée du CRM.';
$string['crm_inbox_folder_not_configured'] =
    'Le dossier fournisseur « {$a} » n’est pas configuré.';

$string['crm_timeline_inbox_received'] =
    'Email reçu dans la CRM Inbox';
$string['crm_timeline_inbox_sent'] =
    'Réponse envoyée depuis la CRM Inbox';

$string['command_action_inbox_title'] =
    'Ouvrir la CRM Inbox';
$string['command_action_inbox_subtitle'] =
    'Consulter et traiter les conversations du support CampusFR.';

$string['task_download_crm_inbox_attachments'] =
    'Télécharger les pièces jointes de la CRM Inbox';

$string['crm_inbox_diagnostics'] =
    'Diagnostic CRM Inbox';
$string['crm_inbox_diagnostics_metrics'] =
    'Indicateurs Inbox';

$string['crm_help_category_inbox'] =
    'CRM Inbox';
$string['crm_help_category_inbox_desc'] =
    'Emails du support, conversations, contacts et assignations.';

$string['crm_help_article_inbox_title'] =
    'Utiliser la CRM Inbox';
$string['crm_help_article_inbox_summary'] =
    'Recevoir, rattacher, assigner et traiter les emails du support CampusFR.';

$string['crm_help_guide_inbox_title'] =
    'Traiter une conversation Inbox';
$string['crm_help_guide_inbox_desc'] =
    'Workflow complet de traitement d’un email du support.';
$string['crm_help_guide_inbox_open_title'] =
    'Ouvrir la boîte de réception';
$string['crm_help_guide_inbox_open_desc'] =
    'Consultez les conversations nouvelles et non assignées.';
$string['crm_help_guide_open_inbox'] =
    'Ouvrir la CRM Inbox';
$string['crm_help_guide_inbox_contact_title'] =
    'Identifier le contact';
$string['crm_help_guide_inbox_contact_desc'] =
    'Vérifiez si le contact est externe ou rattaché à un utilisateur CampusFR.';
$string['crm_help_guide_inbox_assign_title'] =
    'Assigner la conversation';
$string['crm_help_guide_inbox_assign_desc'] =
    'Attribuez la demande à un administrateur ou à une équipe.';
$string['crm_help_guide_inbox_reply_title'] =
    'Préparer et envoyer la réponse';
$string['crm_help_guide_inbox_reply_desc'] =
    'Enregistrez un brouillon ou envoyez directement depuis le CRM.';
$string['crm_help_guide_inbox_close_title'] =
    'Résoudre et archiver';
$string['crm_help_guide_inbox_close_desc'] =
    'Marquez la conversation comme résolue ou archivez-la.';

$string['crm_inbox_account_validation_failed'] =
    'La configuration du compte Inbox est invalide : {$a}';

$string['crm_inbox_validation_invalid_email'] =
    'L’adresse email du compte Inbox est absente ou invalide.';
$string['crm_inbox_validation_provider_missing'] =
    'Le fournisseur du compte Inbox est absent.';
$string['crm_inbox_validation_smtp_missing'] =
    'La configuration SMTP du compte Inbox est absente.';
$string['crm_inbox_validation_sync_missing'] =
    'La configuration de synchronisation Inbox est absente.';
$string['crm_inbox_validation_host_missing'] =
    'L’hôte {$a} est absent.';
$string['crm_inbox_validation_port_invalid'] =
    'Le port {$a} est invalide.';
$string['crm_inbox_validation_encryption_invalid'] =
    'Le chiffrement {$a} est invalide.';
$string['crm_inbox_validation_unencrypted'] =
    'La connexion {$a} n’utilise aucun chiffrement.';
$string['crm_inbox_validation_batchsize'] =
    'La taille de lot Inbox doit être comprise entre 1 et 200.';
$string['crm_inbox_validation_interval'] =
    'L’intervalle de synchronisation doit être compris entre 5 et 1440 minutes.';
$string['crm_inbox_validation_inbox_folder_missing'] =
    'Le dossier IMAP principal n’est pas configuré ; INBOX sera utilisé par défaut.';
$string['crm_inbox_validation_folders_missing'] =
    'La configuration des dossiers Inbox est absente.';
$string['crm_inbox_validation_folder_missing'] =
    'Le dossier Inbox « {$a} » n’est pas encore résolu.';

$string['crm_inbox_folder_discovery_success'] =
    '{$a->count} dossiers détectés. Réception : {$a->inbox} ; envoyés : {$a->sent} ; corbeille : {$a->trash} ; archives : {$a->archive} ; brouillons : {$a->drafts}.';
$string['crm_inbox_folder_discovery_missing'] =
    'Certains dossiers obligatoires sont introuvables : {$a}.';

$string['crm_inbox_remote_image_blocked'] =
    'Image distante bloquée pour protéger votre confidentialité.';
$string['privacy:metadata:inbox_contact'] =
    'Contacts externes ou rattachés utilisés par la CRM Inbox.';
$string['privacy:metadata:inbox_contact:displayname'] =
    'Nom affiché du contact.';
$string['privacy:metadata:inbox_contact:primaryemail'] =
    'Adresse email principale du contact.';
$string['privacy:metadata:inbox_contact:normalizedemail'] =
    'Adresse email normalisée utilisée pour le rapprochement.';
$string['privacy:metadata:inbox_contact:matcheduserid'] =
    'Identifiant de l’utilisateur Moodle rattaché.';
$string['privacy:metadata:inbox_contact:matchstatus'] =
    'Statut du rapprochement avec un utilisateur.';
$string['privacy:metadata:inbox_contact:matchsource'] =
    'Source du rapprochement.';
$string['privacy:metadata:inbox_contact:matchconfidence'] =
    'Niveau de confiance du rapprochement.';
$string['privacy:metadata:inbox_contact:lastmatchedat'] =
    'Date du dernier rapprochement.';

$string['privacy:metadata:inbox_thread'] =
    'Conversations gérées dans la CRM Inbox.';
$string['privacy:metadata:inbox_thread:contactid'] =
    'Contact principal de la conversation.';
$string['privacy:metadata:inbox_thread:subject'] =
    'Objet de la conversation.';
$string['privacy:metadata:inbox_thread:assigneduserid'] =
    'Administrateur auquel la conversation est assignée.';
$string['privacy:metadata:inbox_thread:status'] =
    'Statut de la conversation.';
$string['privacy:metadata:inbox_thread:priority'] =
    'Priorité de la conversation.';
$string['privacy:metadata:inbox_thread:lastmessageat'] =
    'Date du dernier message.';

$string['privacy:metadata:inbox_message'] =
    'Messages reçus ou envoyés depuis la CRM Inbox.';
$string['privacy:metadata:inbox_message:threadid'] =
    'Conversation à laquelle appartient le message.';
$string['privacy:metadata:inbox_message:direction'] =
    'Direction du message.';
$string['privacy:metadata:inbox_message:subject'] =
    'Objet du message.';
$string['privacy:metadata:inbox_message:bodytext'] =
    'Corps texte du message.';
$string['privacy:metadata:inbox_message:bodyhtml'] =
    'Corps HTML du message.';
$string['privacy:metadata:inbox_message:receivedat'] =
    'Date de réception.';
$string['privacy:metadata:inbox_message:sentat'] =
    'Date d’envoi.';
$string['privacy:metadata:inbox_message:createdby'] =
    'Administrateur ayant créé le message sortant.';

$string['privacy:metadata:inbox_participant'] =
    'Participants aux messages Inbox.';
$string['privacy:metadata:inbox_participant:messageid'] =
    'Message concerné.';
$string['privacy:metadata:inbox_participant:contactid'] =
    'Contact lié au participant.';
$string['privacy:metadata:inbox_participant:participanttype'] =
    'Type de participant : expéditeur, destinataire, copie ou réponse.';
$string['privacy:metadata:inbox_participant:email'] =
    'Adresse email du participant.';
$string['privacy:metadata:inbox_participant:displayname'] =
    'Nom affiché du participant.';

$string['privacy:metadata:inbox_attachment'] =
    'Pièces jointes des messages Inbox.';
$string['privacy:metadata:inbox_attachment:messageid'] =
    'Message auquel appartient la pièce jointe.';
$string['privacy:metadata:inbox_attachment:filename'] =
    'Nom du fichier.';
$string['privacy:metadata:inbox_attachment:mimetype'] =
    'Type MIME du fichier.';
$string['privacy:metadata:inbox_attachment:filesize'] =
    'Taille du fichier.';

$string['privacy:path:inbox'] =
    'CRM Inbox';
$string['crm_inbox_match_status'] =
    'Rattachement';
$string['crm_inbox_match_matched'] =
    'Utilisateur rattaché';
$string['crm_inbox_match_unmatched'] =
    'Contact externe';
$string['crm_inbox_match_ambiguous'] =
    'Rattachement ambigu';
$string['crm_inbox_team'] =
    'Équipe';
$string['crm_inbox_unread_only'] =
    'Non lus uniquement';
$string['crm_inbox_per_page'] =
    'Par page';

$string['task_cleanup_crm_inbox'] =
    'Nettoyer les anciennes données de la CRM Inbox';

$string['task_cleanup_crm_inbox_ai_results'] =
    'Nettoyer les résultats IA expirés de la CRM Inbox';

$string['crm_inbox_ai_empty_content'] =
    'Le contenu à analyser est vide.';

$string['crm_inbox_ai_empty_conversation'] =
    'Cette conversation ne contient aucun message analysable.';

$string['crm_inbox_ai_language_unknown'] =
    'Langue non déterminée';

$string['crm_inbox_ai_urgency_low'] =
    'Urgence faible';
$string['crm_inbox_ai_urgency_normal'] =
    'Urgence normale';
$string['crm_inbox_ai_urgency_high'] =
    'Urgence élevée';
$string['crm_inbox_ai_urgency_critical'] =
    'Urgence critique';

$string['crm_inbox_ai_category_payment'] =
    'Paiement';
$string['crm_inbox_ai_category_access'] =
    'Accès';
$string['crm_inbox_ai_category_subscription'] =
    'Abonnement';
$string['crm_inbox_ai_category_technical'] =
    'Problème technique';
$string['crm_inbox_ai_category_course_content'] =
    'Contenu pédagogique';
$string['crm_inbox_ai_category_account'] =
    'Compte utilisateur';
$string['crm_inbox_ai_category_refund'] =
    'Remboursement';
$string['crm_inbox_ai_category_billing'] =
    'Facturation';
$string['crm_inbox_ai_category_commercial'] =
    'Demande commerciale';
$string['crm_inbox_ai_category_feedback'] =
    'Retour utilisateur';
$string['crm_inbox_ai_category_spam'] =
    'Spam';
$string['crm_inbox_ai_category_other'] =
    'Autre';

$string['crm_inbox_ai_reply_requires_review'] =
    'Cette proposition doit être relue et validée avant tout envoi.';

$string['crm_inbox_ai_tone_professional'] =
    'Professionnel';
$string['crm_inbox_ai_tone_friendly'] =
    'Chaleureux';
$string['crm_inbox_ai_tone_empathetic'] =
    'Empathique';
$string['crm_inbox_ai_tone_concise'] =
    'Concis';

$string['crm_inbox_ai_translation_failed'] =
    'La traduction n’a pas pu être générée.';

$string['crm_inbox_ai_reply_unavailable'] =
    'La suggestion de réponse n’est pas disponible avec le fournisseur actuel.';

$string['crm_inbox_ai_context_partial'] =
    'Certaines données CRM n’ont pas pu être ajoutées au contexte.';

$string['crm_inbox_ai_panel_title'] =
    'Assistance IA';
$string['crm_inbox_ai_panel_description'] =
    'Analyse et suggestions destinées à assister l’administrateur.';
$string['crm_inbox_ai_human_review_badge'] =
    'Validation humaine obligatoire';
$string['crm_inbox_ai_permission_required'] =
    'Vous ne disposez pas de la capacité nécessaire pour utiliser l’assistance IA.';
$string['crm_inbox_ai_no_analysis'] =
    'Aucune analyse IA n’a encore été demandée pour cette conversation.';
$string['crm_inbox_ai_analyse'] =
    'Analyser la conversation';
$string['crm_inbox_ai_suggest_reply'] =
    'Proposer une réponse';
$string['crm_inbox_ai_reply_language'] =
    'Langue de la réponse';
$string['crm_inbox_ai_reply_tone'] =
    'Ton de la réponse';
$string['crm_inbox_ai_analysis_completed'] =
    'L’analyse IA est terminée.';
$string['crm_inbox_ai_detected_language'] =
    'Langue détectée';
$string['crm_inbox_ai_urgency'] =
    'Urgence';
$string['crm_inbox_ai_category'] =
    'Catégorie';
$string['crm_inbox_ai_summary'] =
    'Résumé';
$string['crm_inbox_ai_key_points'] =
    'Points clés';
$string['crm_inbox_ai_pending_questions'] =
    'Questions en attente';
$string['crm_inbox_ai_customer_requests'] =
    'Demandes du client';
$string['crm_inbox_ai_suggested_reply'] =
    'Réponse proposée';
$string['crm_inbox_ai_confidence'] =
    'Confiance : {$a} %';

$string['crm_inbox_ai_quota_exceeded'] =
    'Le quota quotidien de l’assistance IA est atteint.';
$string['task_analyse_crm_inbox'] =
    'Analyser les conversations de la CRM Inbox';
$string['crm_inbox_ai_diagnostics'] =
    'Diagnostic de l’assistance IA Inbox';
$string['crm_inbox_ai_diagnostic_table_ok'] =
    'La table des résultats IA est disponible.';
$string['crm_inbox_ai_diagnostic_table_missing'] =
    'La table des résultats IA est absente.';
$string['crm_inbox_ai_diagnostic_fallback'] =
    'Le fournisseur de fallback local est disponible.';
$string['crm_inbox_ai_diagnostic_orchestrator_ok'] =
    'L’orchestrateur IA peut être construit.';
$string['crm_inbox_ai_usage_today'] =
    'Utilisation aujourd’hui';
$string['crm_inbox_ai_usage_global'] =
    'Utilisation globale : {$a->used} / {$a->limit}';
$string['crm_inbox_ai_usage_user'] =
    'Votre utilisation : {$a->used} / {$a->limit}';
$string['crm_inbox_ai_failures_today'] =
    'Analyses en erreur aujourd’hui : {$a}';

$string['crm_help_article_inbox_ai_title'] =
    'Utiliser l’assistance IA de la CRM Inbox';
$string['crm_help_article_inbox_ai_summary'] =
    'Analyser, résumer, traduire et préparer des réponses tout en conservant une validation humaine.';

$string['settings:inbox_ai_header'] =
    'Assistance IA de la CRM Inbox';
$string['settings:inbox_ai_header_desc'] =
    'Configure le fournisseur IA utilisé pour analyser les conversations support.';

$string['settings:inbox_ai_openai_enabled'] =
    'Activer OpenAI';
$string['settings:inbox_ai_openai_enabled_desc'] =
    'Les contenus des emails pourront être transmis à OpenAI.';

$string['settings:inbox_ai_openai_model'] =
    'Modèle OpenAI';
$string['settings:inbox_ai_openai_model_desc'] =
    'Identifiant exact du modèle autorisé pour la CRM Inbox.';

$string['settings:inbox_ai_openai_endpoint'] =
    'Endpoint OpenAI';
$string['settings:inbox_ai_openai_endpoint_desc'] =
    'Endpoint de l’API Responses.';

$string['settings:inbox_ai_openai_timeout'] =
    'Timeout OpenAI';
$string['settings:inbox_ai_openai_max_output_tokens'] =
    'Maximum de tokens de sortie';

$string['settings:inbox_ai_openai_store'] =
    'Autoriser le stockage distant des réponses';
$string['settings:inbox_ai_openai_store_desc'] =
    'Laisser désactivé sauf besoin explicitement validé.';

$string['settings:inbox_ai_include_crm_context'] =
    'Inclure le contexte CRM vérifié';
$string['settings:inbox_ai_include_contact_email'] =
    'Inclure l’adresse email du contact';
$string['settings:inbox_ai_include_contact_email_desc'] =
    'Désactivé par défaut pour limiter les données personnelles transmises.';

$string['settings:inbox_ai_global_daily_limit'] =
    'Quota global quotidien';
$string['settings:inbox_ai_user_daily_limit'] =
    'Quota quotidien par administrateur';
$string['settings:inbox_ai_automatic_analysis'] =
    'Activer l’analyse automatique';
$string['settings:inbox_ai_automatic_analysis_desc'] =
    'À activer uniquement après validation des coûts et de la confidentialité.';

$string['crm_inbox_ai_openai_enabled'] =
    'OpenAI est activé.';
$string['crm_inbox_ai_openai_disabled'] =
    'OpenAI est désactivé.';
$string['crm_inbox_ai_openai_key_available'] =
    'La clé OpenAI est disponible côté serveur.';
$string['crm_inbox_ai_openai_key_missing'] =
    'La clé OpenAI est absente.';
$string['crm_inbox_ai_openai_model_configured'] =
    'Modèle OpenAI configuré : {$a}.';
$string['crm_inbox_ai_openai_model_missing'] =
    'Aucun modèle OpenAI n’est configuré.';

$string['crm_inbox_ai_data_transmission_notice'] =
    'Le contenu des emails peut être transmis au fournisseur IA configuré afin de générer des analyses ou des suggestions.';

$string['crm_inbox_ai_provider_label'] =
    'Fournisseur';

$string['crm_inbox_ai_model_label'] =
    'Modèle';

$string['crm_inbox_ai_cache_hit'] =
    'Résultat en cache';

$string['crm_inbox_ai_cache_miss'] =
    'Nouvelle analyse du fournisseur';

$string['crm_inbox_ai_force_refresh'] =
    'Actualiser l’analyse';

$string['crm_inbox_ai_request_tokens'] =
    'Tokens d’entrée : {$a}';

$string['crm_inbox_ai_response_tokens'] =
    'Tokens de sortie : {$a}';

$string['crm_inbox_ai_total_tokens'] =
    'Total des tokens : {$a}';

$string['crm_inbox_ai_latency'] =
    'Temps de traitement : {$a} ms';

$string['crm_inbox_ai_validation_failed'] =
    'Le résultat IA n’a pas passé la validation locale et n’a pas été présenté comme une analyse réussie.';

$string['crm_inbox_ai_provider_unavailable'] =
    'Le fournisseur IA sélectionné est indisponible.';

$string['crm_inbox_ai_provider_error'] =
    'Le fournisseur IA n’a pas pu terminer l’analyse.';

$string['crm_inbox_ai_rate_limit'] =
    'La limite de requêtes du fournisseur IA a été atteinte. Réessayez plus tard.';

$string['crm_inbox_ai_authentication_error'] =
    'Le fournisseur IA a refusé les identifiants configurés.';

$string['crm_inbox_ai_privacy_notice'] =
    'Les résultats IA sont uniquement des suggestions. Vérifiez toujours le contenu avant de l’utiliser ou de l’envoyer.';

// Phase 6.5F — intégration CRM Inbox.
$string['admin_event_inbox_message_received'] =
    'Email reçu dans l’Inbox';

$string['admin_event_inbox_reply_sent'] =
    'Réponse envoyée depuis l’Inbox';

$string['admin_event_inbox_thread_assigned'] =
    'Conversation Inbox assignée';

$string['admin_event_inbox_thread_unassigned'] =
    'Conversation Inbox désassignée';

$string['admin_event_inbox_thread_status_changed'] =
    'Statut de la conversation Inbox modifié';

$string['admin_event_inbox_thread_priority_changed'] =
    'Priorité de la conversation Inbox modifiée';

$string['admin_event_inbox_ai_analysis_executed'] =
    'Analyse IA exécutée dans l’Inbox';

$string['admin_event_inbox_ai_reply_suggested'] =
    'Suggestion de réponse IA générée';

// Phase 6.5F — Inbox dans la fiche utilisateur.
$string['crm_user_inbox_section'] =
    'Inbox CRM';

$string['crm_user_inbox_badge'] =
    'Inbox';

$string['crm_user_inbox_badge_empty'] =
    'Aucune conversation';

$string['crm_user_inbox_badge_unread'] =
    '{$a} email(s) non lu(s)';

$string['crm_user_inbox_conversations'] =
    'Conversations';

$string['crm_user_inbox_open_conversations'] =
    'Ouvertes';

$string['crm_user_inbox_unread'] =
    'Emails non lus';

$string['crm_user_inbox_ai_suggestions'] =
    'Suggestions IA';

$string['crm_user_inbox_last_email'] =
    'Dernier email';

$string['crm_user_inbox_last_received'] =
    'Dernier email reçu';

$string['crm_user_inbox_last_sent'] =
    'Dernière réponse envoyée';

$string['crm_user_inbox_recent_conversations'] =
    'Conversations récentes';

$string['crm_user_inbox_no_conversations'] =
    'Aucune conversation Inbox n’est encore rattachée à cet utilisateur.';

$string['crm_user_inbox_open_all'] =
    'Ouvrir les conversations dans l’Inbox';

$string['crm_user_inbox_unread_badge'] =
    '{$a} non lu(s)';

// Phase 6.5F — Command Center Inbox.
$string['command_center_type_inbox_thread'] =
    'Conversation Inbox';

$string['command_center_type_inbox_contact'] =
    'Contact Inbox';

$string['command_center_group_inbox_threads'] =
    'Conversations Inbox';

$string['command_center_group_inbox_contacts'] =
    'Contacts Inbox';

$string['command_inbox_thread_status'] =
    'Statut : {$a}';

$string['command_inbox_thread_priority'] =
    'Priorité : {$a}';

$string['command_inbox_thread_unread'] =
    '{$a} non lu(s)';

$string['command_inbox_contact_conversations'] =
    '{$a} conversation(s)';

$string['command_inbox_contact_unread'] =
    '{$a} non lu(s)';

$string['command_inbox_unknown_contact'] =
    'Contact Inbox inconnu';

$string['command_action_inbox_unassigned_title'] =
    'Ouvrir les conversations non assignées';

$string['command_action_inbox_unassigned_subtitle'] =
    'Afficher les conversations Inbox sans administrateur ni équipe responsable.';

$string['command_action_inbox_urgent_title'] =
    'Ouvrir les conversations urgentes';

$string['command_action_inbox_urgent_subtitle'] =
    'Afficher les conversations Inbox dont la priorité est urgente.';

$string['command_action_inbox_diagnostics_title'] =
    'Ouvrir les diagnostics Inbox';

$string['command_action_inbox_diagnostics_subtitle'] =
    'Contrôler les comptes, connecteurs, synchronisations et erreurs Inbox.';

$string['command_action_inbox_ai_diagnostics_title'] =
    'Ouvrir les diagnostics IA Inbox';

$string['command_action_inbox_ai_diagnostics_subtitle'] =
    'Contrôler le provider IA, les modèles, les prompts, le cache et la configuration.';

$string['command_action_inbox_sync_title'] =
    'Synchroniser l’Inbox';

$string['command_action_inbox_sync_subtitle'] =
    'Récupérer maintenant une page de nouveaux emails pour chaque dossier configuré.';

$string['command_confirm_inbox_sync'] =
    'Lancer maintenant la synchronisation manuelle de tous les comptes Inbox actifs ?';

$string['command_center_action_run'] =
    'Exécuter';

$string['command_inbox_sync_no_accounts'] =
    'Aucun compte Inbox actif n’est configuré.';

$string['command_inbox_sync_success'] =
    'Synchronisation terminée : {$a->fetched} récupéré(s), {$a->created} créé(s), {$a->skipped} ignoré(s), {$a->errors} erreur(s).';

$string['command_inbox_sync_has_more'] =
    'D’autres messages restent disponibles et seront récupérés par le prochain passage.';

$string['command_inbox_sync_failed'] =
    'La synchronisation manuelle de l’Inbox a échoué. Consultez les diagnostics Inbox.';

// Phase 6.5F — User Explorer et Intelligence Inbox.
$string['crm_user_column_inbox'] =
    'Inbox';

$string['crm_user_has_inbox'] =
    'Possède une conversation Inbox';

$string['crm_user_has_inbox_unread'] =
    'Possède des emails Inbox non lus';

$string['crm_user_inbox_none'] =
    'Aucune conversation';

$string['crm_user_inbox_conversation_count'] =
    '{$a} conversation(s)';

$string['crm_user_inbox_open_count'] =
    '{$a} ouverte(s)';

$string['crm_user_inbox_unread_count'] =
    '{$a} non lu(s)';

$string['crm_user_inbox_urgent_count'] =
    '{$a} urgente(s)';

$string['crm_intelligence_inbox_conversations'] =
    '{$a} conversation(s) Inbox';

$string['crm_intelligence_inbox_open'] =
    '{$a} ouverte(s)';

$string['crm_intelligence_inbox_unread'] =
    '{$a} non lu(s)';

$string['crm_intelligence_inbox_urgent'] =
    '{$a} urgente(s)';

$string['crm_intelligence_inbox_open_link'] =
    'Ouvrir les conversations';

// Phase 6.5F — Help Center Inbox.
$string['crm_help_article_inbox_diagnostics_title'] =
    'Diagnostiquer la CRM Inbox';

$string['crm_help_article_inbox_diagnostics_summary'] =
    'Vérifier IMAP, SMTP, la synchronisation, les pièces jointes, le matching et l’assistance IA.';

$string['crm_help_guide_inbox_ai_title'] =
    'Utiliser l’assistance IA';

$string['crm_help_guide_inbox_ai_desc'] =
    'Analysez la conversation, vérifiez la langue et l’urgence, puis relisez toute suggestion avant de l’utiliser.';

$string['crm_help_guide_inbox_ai_action'] =
    'Lire l’aide IA';

$string['crm_help_guide_inbox_diagnostics_title'] =
    'Contrôler les diagnostics';

$string['crm_help_guide_inbox_diagnostics_desc'] =
    'Vérifiez IMAP, SMTP, les synchronisations, les pièces jointes, le provider IA et les quotas.';

$string['crm_help_guide_inbox_diagnostics_action'] =
    'Lire le guide de diagnostic';

$string['crm_onboarding_step_inbox_title'] =
    'Découvrir la CRM Inbox';

$string['crm_onboarding_step_inbox_desc'] =
    'Consultez les conversations du support, identifiez les contacts, vérifiez les priorités et découvrez les outils de réponse.';

$string['crm_help_open_inbox_help'] =
    'Guide de diagnostic Inbox';

$string['crm_help_open_inbox_diagnostics'] =
    'Diagnostics Inbox';

$string['crm_help_open_inbox_ai_diagnostics'] =
    'Diagnostics IA Inbox';

$string['crm_inbox_help_subtitle'] =
    'Centralisez, assignez et traitez les conversations du support CampusFR.';

$string['crm_inbox_thread_help_subtitle'] =
    'Consultez l’historique complet, les informations CRM et les suggestions IA disponibles.';

$string['crm_inbox_diagnostics_help_subtitle'] =
    'Contrôlez les comptes, connexions, synchronisations et erreurs techniques de l’Inbox.';

$string['crm_inbox_ai_diagnostics_help_subtitle'] =
    'Contrôlez le provider IA, les modèles, les quotas, le cache et les erreurs récentes.';

// Phase 6.5F — UX et accessibilité Inbox.
$string['crm_inbox_region_label'] =
    'CRM Inbox';

$string['crm_inbox_result_count'] =
    '{$a} conversation(s) trouvée(s)';

$string['crm_inbox_empty_title'] =
    'Aucune conversation trouvée';

$string['crm_inbox_thread_list_label'] =
    'Liste des conversations Inbox';

$string['crm_inbox_filters_label'] =
    'Filtres de la CRM Inbox';

$string['crm_inbox_unread_count_accessible'] =
    '{$a} message(s) non lu(s) dans cette conversation';

$string['crm_inbox_thread_region_label'] =
    'Conversation Inbox';

$string['crm_inbox_thread_actions_label'] =
    'Actions sur la conversation';

$string['crm_inbox_action_processing'] =
    'L’action est en cours de traitement.';

$string['crm_inbox_processing'] =
    'Traitement…';

$string['crm_inbox_message_content_label'] =
    'Contenu du message';

$string['crm_inbox_attachments_label'] =
    'Pièces jointes du message';

$string['crm_inbox_download_attachment'] =
    'Télécharger la pièce jointe {$a}';

$string['crm_inbox_reply_form_label'] =
    'Formulaire de réponse Inbox';

$string['crm_inbox_reply_processing'] =
    'La réponse est en cours de traitement.';

$string['crm_inbox_reply_help'] =
    'Relisez le sujet et le message avant l’envoi. Les suggestions IA restent modifiables et doivent toujours être validées humainement.';

$string['crm_inbox_reply_actions_label'] =
    'Actions du formulaire de réponse';

$string['crm_inbox_saving'] =
    'Enregistrement…';

$string['crm_inbox_sending'] =
    'Envoi…';

$string['dashboard_inbox_title'] =
    'Inbox CRM';

$string['dashboard_inbox_subtitle'] =
    'Vue opérationnelle des conversations et des demandes à traiter.';

$string['dashboard_inbox_open'] =
    'Ouvrir l’Inbox';

$string['dashboard_inbox_open_conversations'] =
    'Conversations ouvertes';

$string['dashboard_inbox_unassigned'] =
    'Non assignées';

$string['dashboard_inbox_urgent'] =
    'Urgentes';

$string['dashboard_inbox_pending'] =
    'Réponses en attente';

$string['dashboard_inbox_recent_activity'] =
    'Activité récente';

$string['dashboard_inbox_empty'] =
    'Aucune conversation Inbox récente.';

$string['dashboard_inbox_metric_aria'] =
    '{$a->label} : {$a->count}';

$string['crm_inbox_status_unknown'] =
    'Statut inconnu';

$string['crm_inbox_priority_unknown'] =
    'Priorité inconnue';

$string['crm_user_view_delete_processing'] =
    'Suppression de la vue enregistrée en cours.';

$string['crm_user_view_delete_processing_short'] =
    'Suppression…';

$string['crm_inbox_ai_analysis_processing'] =
    'Analyse IA de la conversation en cours.';

$string['crm_inbox_ai_analysis_processing_short'] =
    'Analyse…';

$string['crm_inbox_ai_reply_processing'] =
    'Génération de la suggestion de réponse en cours.';

$string['crm_inbox_ai_reply_processing_short'] =
    'Génération…';

$string['crm_inbox_ai_actions_label'] =
    'Actions d’assistance IA';

$string['crm_inbox_messages_heading'] =
    'Messages de la conversation';

$string['crm_inbox_attachment_unavailable'] =
    'indisponible';

$string['crm_inbox_attachment_unavailable_aria'] =
    'Pièce jointe {$a}, indisponible pour le moment';

$string['crm_user_inbox_statistics_label'] =
    'Statistiques Inbox de l’utilisateur';

$string['crm_user_inbox_stat_aria'] =
    '{$a->label} : {$a->value}';

$string['command_center_menu_actions'] =
    'Actions du résultat';

$string['command_center_confirmation_dialog'] =
    'Confirmation d’action';

$string['subscriptions:view_work_items'] =
    'Consulter les éléments de travail CRM';

$string['subscriptions:manage_work_items'] =
    'Créer et gérer les éléments de travail CRM';

$string['subscriptions:manage_work_configuration'] =
    'Configurer les équipes et workflows de travail CRM';

$string['crm_work_title'] = 'Work Items';
$string['crm_work_subtitle'] = 'Pilotez les tâches, tickets, incidents et demandes internes de CampusFR.';
$string['crm_work_region_label'] = 'Liste des Work Items';
$string['crm_work_result_count'] = '{$a} Work Item(s)';
$string['crm_work_empty'] = 'Aucun Work Item ne correspond à ces critères.';
$string['crm_work_create'] = 'Créer un Work Item';
$string['crm_work_created'] = 'Le Work Item a été créé.';
$string['crm_work_back'] = 'Retour aux Work Items';
$string['crm_work_status'] = 'Statut';
$string['crm_work_priority'] = 'Priorité';
$string['crm_work_type'] = 'Type';
$string['crm_work_team'] = 'Équipe';
$string['crm_work_due'] = 'Échéance';
$string['crm_work_assigned_user'] = 'Assigné à un membre';
$string['crm_work_filter_mine'] = 'Mes tâches';
$string['crm_work_filter_unassigned'] = 'Non assignés';
$string['crm_work_filter_overdue'] = 'En retard';
$string['crm_work_comments'] = 'Commentaires internes';
$string['crm_work_add_comment'] = 'Ajouter le commentaire';
$string['crm_work_subtasks'] = 'Sous-tâches';
$string['crm_work_links'] = 'Objets liés';
$string['crm_work_history'] = 'Historique';
$string['crm_work_teams'] = 'Équipes Work Management';
$string['crm_work_team_name'] = 'Nom de l’équipe';
$string['crm_work_team_create'] = 'Créer l’équipe';
$string['crm_work_field_title'] = 'Titre';
$string['crm_work_field_description'] = 'Description';
$string['crm_work_create_from_thread'] = 'Créer un Work Item';
$string['crm_work_user_section'] = 'Work Items';
$string['crm_work_total'] = 'Total';
$string['crm_work_active'] = 'Actifs';
$string['crm_work_urgent'] = 'Urgents';
$string['crm_work_overdue'] = 'En retard';
$string['crm_work_unassigned'] = 'Non assignés';
$string['crm_work_my_items'] = 'Mes tâches';
$string['crm_work_open_user_items'] = 'Voir tous les Work Items';
$string['crm_work_create_for_user'] = 'Créer une tâche';
$string['crm_work_dashboard_title'] = 'Work Management';

$string['crm_work_status_open'] = 'Ouvert';
$string['crm_work_status_in_progress'] = 'En cours';
$string['crm_work_status_blocked'] = 'Bloqué';
$string['crm_work_status_waiting'] = 'En attente';
$string['crm_work_status_resolved'] = 'Résolu';
$string['crm_work_status_closed'] = 'Fermé';
$string['crm_work_status_cancelled'] = 'Annulé';

$string['crm_work_priority_low'] = 'Basse';
$string['crm_work_priority_normal'] = 'Normale';
$string['crm_work_priority_high'] = 'Haute';
$string['crm_work_priority_urgent'] = 'Urgente';
$string['crm_work_priority_critical'] = 'Critique';

$string['crm_work_type_task'] = 'Tâche';
$string['crm_work_type_support'] = 'Support';
$string['crm_work_type_bug'] = 'Bug';
$string['crm_work_type_incident'] = 'Incident';
$string['crm_work_type_feature'] = 'Fonctionnalité';
$string['crm_work_type_content'] = 'Contenu';
$string['crm_work_type_marketing'] = 'Marketing';
$string['crm_work_type_finance'] = 'Finance';
$string['crm_work_type_administration'] = 'Administration';
$string['crm_work_type_follow_up'] = 'Suivi';

$string['command_action_work_items_title'] =
    'Ouvrir les éléments de travail';

$string['command_action_work_items_subtitle'] =
    'Afficher toutes les tâches, tous les tickets et toutes les demandes internes.';

$string['command_action_work_items_mine_title'] =
    'Mes tâches';

$string['command_action_work_items_mine_subtitle'] =
    'Afficher les éléments de travail qui vous sont assignés.';

$string['command_action_work_items_urgent_title'] =
    'Éléments de travail urgents';

$string['command_action_work_items_urgent_subtitle'] =
    'Afficher les éléments de travail dont la priorité est urgente.';

$string['command_action_work_items_overdue_title'] =
    'Éléments de travail en retard';

$string['command_action_work_items_overdue_subtitle'] =
    'Afficher les éléments de travail dont l’échéance est dépassée.';

$string['command_action_work_items_unassigned_title'] =
    'Éléments de travail non assignés';

$string['command_action_work_items_unassigned_subtitle'] =
    'Afficher les éléments de travail actifs sans responsable.';

$string['crm_work_team_role_member'] =
    'Membre';

$string['crm_work_team_role_lead'] =
    'Responsable';

$string['crm_work_remove_member_confirm'] =
    'Retirer ce membre de l’équipe ?';

$string['crm_help_category_work_management'] =
    'Gestion du travail';

$string['crm_help_category_work_management_desc'] =
    'Organisez les tâches, tickets, incidents et demandes internes de CampusFR.';

$string['crm_help_article_work_management_title'] =
    'Gérer les éléments de travail';

$string['crm_help_article_work_management_summary'] =
    'Comprendre les statuts, les priorités, les équipes, les assignations, les sous-tâches et les liens CRM des éléments de travail.';

$string['crm_assistant_title'] = 'Assistant CRM';
$string['crm_assistant_navigation'] = 'Assistant CRM';
$string['crm_assistant_description'] = 'Les situations prioritaires détectées par l’intelligence transversale du CRM. L’assistant explique et propose des actions, mais ne décide jamais automatiquement.';
$string['crm_assistant_open'] = 'Ouvrir l’Assistant CRM';
$string['crm_assistant_empty'] = 'Aucune recommandation ne nécessite actuellement votre attention.';
$string['crm_assistant_user_section'] = 'Assistant CRM';

$string['crm_assistant_metric_active'] = 'Actives';
$string['crm_assistant_metric_critical'] = 'Critiques';
$string['crm_assistant_metric_urgent'] = 'Urgentes';
$string['crm_assistant_metric_accepted'] = 'Acceptées';
$string['crm_assistant_metric_crossdomain'] = 'Transversales';
$string['crm_assistant_metric_users'] = 'Utilisateurs concernés';

$string['crm_assistant_filter_scope'] = 'Périmètre';
$string['crm_assistant_filter_priority'] = 'Priorité';
$string['crm_assistant_filter_status'] = 'Statut';
$string['crm_assistant_filter_any'] = 'Tous';
$string['crm_assistant_scope_active'] = 'Recommandations actives';
$string['crm_assistant_scope_all'] = 'Tout l’historique';

$string['crm_assistant_priority_critical'] = 'Critique';
$string['crm_assistant_priority_urgent'] = 'Urgente';
$string['crm_assistant_priority_high'] = 'Élevée';
$string['crm_assistant_priority_normal'] = 'Normale';
$string['crm_assistant_priority_low'] = 'Faible';

$string['crm_assistant_status_proposed'] = 'Proposée';
$string['crm_assistant_status_accepted'] = 'Acceptée';
$string['crm_assistant_status_dismissed'] = 'Rejetée';
$string['crm_assistant_status_completed'] = 'Terminée';
$string['crm_assistant_status_expired'] = 'Expirée';

$string['crm_assistant_target'] = 'Utilisateur';
$string['crm_assistant_why'] = 'Pourquoi cette recommandation ?';
$string['crm_assistant_priority_score'] = 'Score de priorité : {$a}';
$string['crm_assistant_evidence_count'] = '{$a} preuve(s)';
$string['crm_assistant_source_count'] = 'Sources associées : {$a}';
$string['crm_assistant_last_detected'] = 'Dernière détection : {$a}';

$string['crm_assistant_action_accept'] = 'Accepter';
$string['crm_assistant_action_complete'] = 'Marquer comme terminée';
$string['crm_assistant_action_dismiss'] = 'Rejeter';
$string['crm_assistant_accepted'] = 'La recommandation a été acceptée.';
$string['crm_assistant_completed'] = 'La recommandation a été marquée comme terminée.';
$string['crm_assistant_dismissed'] = 'La recommandation a été rejetée.';
$string['crm_assistant_action_failed'] = 'L’action sur la recommandation n’a pas pu être réalisée.';

$string['command_crm_assistant'] = 'Ouvrir l’Assistant CRM';
$string['command_crm_assistant_desc'] = 'Afficher les recommandations et situations prioritaires.';
$string['command_crm_recommendation_desc'] = 'Recommandation active du CRM.';

$string['crm_assistant_recommendation_intervene_disengagement_spiral'] = 'Intervenir face au désengagement progressif';
$string['crm_assistant_recommendation_intervene_disengagement_spiral_desc'] = 'Plusieurs signes indiquent une baisse durable de l’activité et de la progression.';

$string['crm_assistant_recommendation_coordinate_learning_support_response'] = 'Coordonner l’accompagnement pédagogique et le support';
$string['crm_assistant_recommendation_coordinate_learning_support_response_desc'] = 'Une difficulté d’apprentissage est accompagnée d’une demande de support active.';

$string['crm_assistant_recommendation_coordinate_payment_support_resolution'] = 'Résoudre conjointement le paiement et la demande support';
$string['crm_assistant_recommendation_coordinate_payment_support_resolution_desc'] = 'Un problème de paiement et une conversation support semblent liés.';

$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk'] = 'Intervenir face à un risque élevé de désabonnement';
$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk_desc'] = 'L’accès, l’activité et plusieurs points de friction indiquent un risque de départ.';

$string['crm_assistant_recommendation_coordinate_operational_overload'] = 'Coordonner la résolution des demandes en attente';
$string['crm_assistant_recommendation_coordinate_operational_overload_desc'] = 'La pression Inbox et les Work Items non résolus nécessitent une intervention coordonnée.';

$string['crm_assistant_recommendation_review_customer_success_risk'] = 'Examiner le risque Customer Success';
$string['crm_assistant_recommendation_review_learning_difficulty'] = 'Examiner la difficulté pédagogique';
$string['crm_assistant_recommendation_review_support_situation'] = 'Examiner la situation support';
$string['crm_assistant_recommendation_review_blocked_payment'] = 'Examiner le paiement bloqué';
$string['crm_assistant_recommendation_review_active_work_items'] = 'Examiner les Work Items actifs';
$string['crm_assistant_recommendation_recognise_positive_progress'] = 'Valoriser la progression de l’étudiant';

$string['crm_assistant_action_propose_work_item'] = 'Préparer un Work Item';

$string['crm_work_suggestion_title'] = 'Suggestion de Work Item';
$string['crm_work_suggestion_summary'] = 'Proposition de l’Assistant CRM';
$string['crm_work_suggestion_confidence'] = 'Confiance de la suggestion : {$a} %';
$string['crm_work_suggestion_suggested_type'] = 'Type proposé : {$a}';
$string['crm_work_suggestion_suggested_priority'] = 'Priorité proposée : {$a}';
$string['crm_work_suggestion_suggested_due'] = 'Échéance proposée : {$a}';
$string['crm_work_suggestion_duplicates'] = 'Work Items similaires';
$string['crm_work_suggestion_probable_duplicate_warning'] = 'Un Work Item probablement équivalent existe déjà. Vérifiez-le avant de créer un nouvel élément.';
$string['crm_work_suggestion_similarity'] = 'Similarité estimée : {$a} %';
$string['crm_work_suggestion_teams'] = 'Équipes proposées';
$string['crm_work_suggestion_team_score'] = 'Pertinence : {$a->score} % · Charge active : {$a->workload}';
$string['crm_work_suggestion_allow_duplicate'] = 'Créer quand même ce Work Item malgré le doublon probable';
$string['crm_work_suggestion_create'] = 'Créer le Work Item';
$string['crm_work_suggestion_created'] = 'Le Work Item a été créé à partir de la recommandation.';
$string['crm_work_suggestion_duplicate_blocked'] = 'La création a été bloquée car un doublon probable existe déjà.';

$string['crm_work_suggestion_description_intro'] = 'Ce Work Item a été préparé par l’Assistant CRM. Son contenu doit être vérifié et validé par un administrateur.';
$string['crm_work_suggestion_source_recommendation'] = 'Recommandation source : {$a}';
$string['crm_work_suggestion_priority_score'] = 'Score de priorité de la recommandation : {$a}';
$string['crm_work_suggestion_evidence_heading'] = 'Éléments ayant motivé la recommandation :';

$string['crm_work_suggestion_title_intervene_disengagement_spiral'] = 'Relancer l’étudiant face à une baisse durable d’engagement';
$string['crm_work_suggestion_title_coordinate_learning_support_response'] = 'Coordonner l’accompagnement pédagogique et la réponse support';
$string['crm_work_suggestion_title_coordinate_payment_support_resolution'] = 'Résoudre le problème de paiement et la demande support';
$string['crm_work_suggestion_title_intervene_cross_domain_churn_risk'] = 'Mettre en place une intervention de prévention du désabonnement';
$string['crm_work_suggestion_title_coordinate_operational_overload'] = 'Coordonner les demandes support et les Work Items en attente';
$string['crm_work_suggestion_title_review_customer_success_risk'] = 'Examiner la situation Customer Success';
$string['crm_work_suggestion_title_review_learning_difficulty'] = 'Mettre en place un suivi pédagogique';
$string['crm_work_suggestion_title_review_support_situation'] = 'Traiter la situation support';
$string['crm_work_suggestion_title_review_blocked_payment'] = 'Traiter le paiement bloqué';
$string['crm_work_suggestion_title_review_active_work_items'] = 'Examiner les Work Items actifs';

$string['local/subscriptions:use_crm_assistant_ai'] = 'Utiliser l’Assistant CRM conversationnel';

$string['crm_assistant_ai_title'] = 'Interroger l’Assistant CRM';
$string['crm_assistant_ai_description'] = 'Posez une question sur les recommandations, les utilisateurs concernés et les Work Items actifs. Les réponses sont basées uniquement sur les informations déjà calculées par le CRM.';
$string['crm_assistant_ai_question'] = 'Votre question';
$string['crm_assistant_ai_placeholder'] = 'Exemple : quels étudiants nécessitent une intervention aujourd’hui ?';
$string['crm_assistant_ai_ask'] = 'Interroger l’Assistant';
$string['crm_assistant_ai_thinking'] = 'L’Assistant analyse les informations disponibles…';
$string['crm_assistant_ai_request_failed'] = 'L’Assistant CRM n’a pas pu répondre à cette question.';
$string['crm_assistant_ai_human_review'] = 'Les réponses de l’Assistant sont des propositions. Elles doivent toujours être vérifiées par un administrateur.';
$string['crm_assistant_ai_keypoints'] = 'Points importants';
$string['crm_assistant_ai_suggested_actions'] = 'Actions proposées';
$string['crm_assistant_ai_warnings'] = 'Points de vigilance';
$string['crm_assistant_ai_references'] = 'Éléments CRM concernés';
$string['crm_assistant_ai_confidence'] = 'Confiance estimée';

$string['crm_assistant_ai_example_priorities'] = 'Quels utilisateurs dois-je traiter en priorité aujourd’hui ?';
$string['crm_assistant_ai_example_risks'] = 'Quelles situations présentent le plus grand risque ?';
$string['crm_assistant_ai_example_work'] = 'Quels Work Items semblent urgents ou bloqués ?';

$string['crm_assistant_question_rejected'] = 'Cette question ne peut pas être traitée par l’Assistant CRM.';

$string['task_run_crm_recommendations'] = 'Actualiser les recommandations du CRM';

$string['crm_recommendation_health_healthy'] = 'Le moteur de recommandations fonctionne normalement.';
$string['crm_recommendation_health_degraded'] = 'Le moteur de recommandations fonctionne avec des avertissements.';
$string['crm_recommendation_health_unhealthy'] = 'Le moteur de recommandations nécessite une intervention.';
$string['crm_recommendation_run_completed'] = 'Exécution des recommandations terminée';
$string['crm_recommendation_run_partial'] = 'Exécution des recommandations partiellement terminée';
$string['crm_recommendation_run_failed'] = 'Échec de l’exécution des recommandations';
$string['crm_recommendation_run_skipped'] = 'Exécution des recommandations ignorée';

$string['csplanpage'] = 'Plan Customer Success';
$string['csplanusersection'] = 'Plan Customer Success';
$string['csplannoneforuser'] = 'Aucun plan Customer Success ouvert.';
$string['csplanblocked'] = 'Bloqué';

$string['csplanstatus_draft'] = 'Brouillon';
$string['csplanstatus_active'] = 'Actif';
$string['csplanstatus_paused'] = 'En pause';
$string['csplanstatus_completed'] = 'Terminé';
$string['csplanstatus_cancelled'] = 'Annulé';

$string['csplanstepstatus_pending'] = 'En attente';
$string['csplanstepstatus_ready'] = 'Prête';
$string['csplanstepstatus_blocked'] = 'Bloquée';
$string['csplanstepstatus_in_progress'] = 'En cours';
$string['csplanstepstatus_completed'] = 'Terminée';
$string['csplanstepstatus_skipped'] = 'Ignorée';

$string['csplanpriority_low'] = 'Faible';
$string['csplanpriority_normal'] = 'Normale';
$string['csplanpriority_high'] = 'Élevée';
$string['csplanpriority_urgent'] = 'Urgente';
$string['csplanpriority_critical'] = 'Critique';

$string['csplanprogressvalue'] = '{$a->completed}/{$a->total} étapes — {$a->percentage} %';
$string['csplanprogresspercentage'] = 'Progression : {$a} %';
$string['csplanstepdependency'] = 'Dépend de l’étape #{$a}';

$string['csplanaction_activate'] = 'Activer';
$string['csplanaction_pause'] = 'Mettre en pause';
$string['csplanaction_cancel'] = 'Annuler';
$string['csplanaction_startstep'] = 'Commencer';
$string['csplanaction_completestep'] = 'Terminer';
$string['csplanaction_skipstep'] = 'Ignorer';
$string['csplanaction_unblockstep'] = 'Débloquer';
$string['csplanactioncompleted'] = 'Le plan Customer Success a été mis à jour.';

$string['csplantimelinecreated'] = 'Plan Customer Success créé';
$string['csplantimelineactivated'] = 'Plan Customer Success activé';
$string['csplantimelinestepcompleted'] = 'Étape du plan traitée';
$string['csplantimelinecompleted'] = 'Plan Customer Success terminé';

$string['csplandashboard_title'] = 'Plans Customer Success';
$string['csplandashboard_open'] = 'Plans ouverts';
$string['csplandashboard_active'] = 'Plans actifs';
$string['csplandashboard_blocked'] = 'Étapes bloquées';
$string['csplandashboard_critical'] = 'Plans critiques';
$string['csplandashboard_completedtoday'] = 'Terminés aujourd’hui';
$string['csplandashboard_averageprogress'] = 'Progression moyenne : {$a} %';

$string['csplancommand_open'] = 'Ouvrir le plan #{$a}';
$string['csplancommand_open_desc'] = 'Afficher le plan Customer Success';

$string['crm_user_has_customer_success_plan'] =
    'Possède un plan Customer Success ouvert';

$string['crm_user_customer_success_plan_blocked'] =
    'Possède une étape Customer Success bloquée';

$string['crm_user_customer_success_plan_status'] =
    'Statut du plan Customer Success';

$string['crm_user_customer_success_plan_status_all'] =
    'Tous les statuts';

$string['crm_user_column_customer_success_plans'] =
    'Customer Success';

$string['crm_user_customer_success_none'] =
    'Aucun plan ouvert';

$string['crm_user_customer_success_open_count'] =
    '{$a} plan(s) ouvert(s)';

$string['crm_user_customer_success_blocked_count'] =
    '{$a} bloqué(s)';

$string['csplanobjective_reduce_churn_risk'] =
    'Réduire le risque de désabonnement';

$string['csplanobjective_resolve_payment_friction'] =
    'Résoudre les difficultés de paiement';

$string['csplanobjective_resolve_support_pressure'] =
    'Résoudre les demandes de support';

$string['csplanobjective_restore_learning_access'] =
    'Rétablir l’accès à la formation';

$string['csplanobjective_restore_learning_engagement'] =
    'Relancer la progression pédagogique';

$string['csplanobjective_develop_customer_opportunity'] =
    'Développer l’opportunité client';

$string['csplanobjective_coordinate_customer_success'] =
    'Coordonner le suivi Customer Success';

$string['csplandescription_recommendations'] =
    'Plan Customer Success préparé à partir de {$a} recommandation(s) CRM.';

$string['csplanblockedreason_dependency_cycle'] =
    'Cette étape est bloquée par un cycle de dépendances.';

$string['csplanblockedreason_manual'] =
    'Cette étape a été bloquée manuellement.';

$string['csplanblockedreason_unknown'] =
    'Cette étape est bloquée. Motif technique : {$a}';

$string['csplansource_manual'] =
    'Création manuelle';

$string['csplansource_recommendation_engine'] =
    'Moteur de recommandations';

$string['csplansource_correlation_engine'] =
    'Moteur de corrélations';

$string['csplansource_crm_assistant'] =
    'Assistant CRM';

$string['csplansource_user_360'] =
    'Profil utilisateur 360°';

$string['csplanprogresslabel'] =
    'Progression du plan Customer Success';

$string['csplanactionfailed'] =
    'L’action sur le plan Customer Success n’a pas pu être exécutée.';

$string['admin_event_customer_success_plan_created'] =
    'Plan Customer Success créé';

$string['admin_event_customer_success_plan_activated'] =
    'Plan Customer Success activé';

$string['admin_event_customer_success_plan_paused'] =
    'Plan Customer Success mis en pause';

$string['admin_event_customer_success_plan_cancelled'] =
    'Plan Customer Success annulé';

$string['admin_event_customer_success_plan_completed'] =
    'Plan Customer Success terminé';

$string['admin_event_customer_success_plan_auto_completed'] =
    'Plan Customer Success terminé automatiquement';

$string['admin_event_customer_success_step_started'] =
    'Étape Customer Success commencée';

$string['admin_event_customer_success_step_completed'] =
    'Étape Customer Success terminée';

$string['admin_event_customer_success_step_skipped'] =
    'Étape Customer Success ignorée';

$string['admin_event_customer_success_step_blocked'] =
    'Étape Customer Success bloquée';

$string['admin_event_customer_success_step_unblocked'] =
    'Étape Customer Success débloquée';

$string['csplanconfirmtitle'] =
    'Confirmer l’action';

$string['csplanconfirmcancel'] =
    'Voulez-vous vraiment annuler le plan « {$a} » ? Cette action ne supprimera pas son historique.';

$string['csplanconfirmskipstep'] =
    'Voulez-vous vraiment ignorer l’étape « {$a} » ? Les étapes qui en dépendent pourront alors devenir disponibles.';

$string['csplanblockreasonlabel'] =
    'Motif du blocage';

$string['csplanblockreasonplaceholder'] =
    'Indiquez pourquoi cette étape est bloquée';

$string['csplanblockreasonhelp'] =
    'Le motif sera visible dans le plan et enregistré dans l’historique administratif.';

$string['csplanblockreasonrequired'] =
    'Vous devez indiquer un motif de blocage.';

$string['csplanblockreasontoolong'] =
    'Le motif de blocage ne peut pas dépasser 500 caractères.';

$string['csplanaction_blockstep'] =
    'Bloquer l’étape';

$string['crm_filter_customer_success'] =
    'Customer Success';

$string['crm_assistant_evidence_activity_inactive_30d'] =
    'Aucune activité depuis au moins 30 jours';

$string['crm_assistant_evidence_value_activity_inactive_30d'] =
    '{$a} jour(s) depuis la dernière activité';

$string['crm_assistant_evidence_loyalty_no_current_access'] =
    'Aucun accès actif actuellement';

$string['crm_assistant_evidence_value_loyalty_no_current_access'] =
    '{$a} accès expiré(s) ou annulé(s)';

$string['crm_assistant_recommendation_send_trial_conversion_email'] =
    'Accompagner la conversion après l’essai';

$string['crm_assistant_recommendation_send_trial_conversion_email_desc'] =
    'Cet utilisateur a essayé la plateforme mais n’a pas encore souscrit d’accès payant.';

$string['crm_assistant_recommendation_propose_upgrade'] =
    'Proposer une formule plus complète';

$string['crm_assistant_recommendation_propose_upgrade_desc'] =
    'L’accès actuel de ce client peut être complété par une formule supérieure.';

$string['crm_assistant_recommendation_send_winback_message'] =
    'Réactiver un ancien client';

$string['crm_assistant_recommendation_send_winback_message_desc'] =
    'Cet ancien client ne possède plus d’accès actif et pourrait être recontacté.';

$string['crm_assistant_recommendation_suggest_digital_product'] =
    'Suggérer un produit numérique';

$string['crm_assistant_recommendation_suggest_digital_product_desc'] =
    'Ce client peut être intéressé par un produit numérique complémentaire.';

$string['crm_assistant_recommendation_create_first_crm_note'] =
    'Créer une première note CRM';

$string['crm_assistant_recommendation_create_first_crm_note_desc'] =
    'Aucune information qualitative n’a encore été consignée dans le CRM pour ce client.';

$string['crm_assistant_evidence_crm_customer_without_notes'] =
    'Aucune note CRM n’a encore été ajoutée pour ce client';

$string['crm_assistant_evidence_opportunity_trial_to_purchase'] =
    'L’essai gratuit n’a pas encore conduit à un achat';

$string['crm_assistant_evidence_opportunity_upgrade_subscription'] =
    'Une formule plus complète peut être pertinente';

$string['crm_assistant_evidence_opportunity_winback_expired_customer'] =
    'Le client ne possède plus d’accès actif';

$string['crm_assistant_evidence_opportunity_cross_sell_digital_product'] =
    'Un produit numérique complémentaire peut être proposé';

$string['crm_work_source_manual'] =
    'Création manuelle';

$string['crm_work_source_inbox'] =
    'Inbox CRM';

$string['crm_work_source_user_360'] =
    'Fiche utilisateur 360°';

$string['crm_work_source_dashboard'] =
    'Tableau de bord CRM';

$string['crm_work_source_automation'] =
    'Automatisation CRM';

$string['crm_work_source_intelligence'] =
    'Intelligence CRM';

$string['crm_work_source_assistant'] =
    'Assistant CRM';

$string['crm_work_source_command_center'] =
    'Command Center';

$string['crm_work_source_system'] =
    'Système';

$string['crm_work_suggestion_reason_generated_from_recommendation'] =
    'Suggestion créée à partir d’une recommandation CRM';

$string['crm_work_suggestion_reason_priority_derived_from_recommendation'] =
    'Priorité calculée à partir du niveau d’urgence de la recommandation';

$string['crm_work_suggestion_reason_type_derived_from_scenario'] =
    'Type de tâche déterminé selon la situation détectée';

$string['crm_work_suggestion_reason_team_suggested_from_domain_and_workload'] =
    'Équipe proposée selon son domaine et sa charge actuelle';

$string['crm_work_suggestion_reason_duplicate_candidates_detected'] =
    'Des Work Items similaires ont été détectés';

$string['crm_assistant_unknown_label'] =
    'Information non disponible';

$string['crm_assistant_evidence_learning_low_progress'] =
    'Progression pédagogique insuffisante';

$string['crm_assistant_evidence_recommendation_review_customer_success_risk'] =
    'Une vérification du risque Customer Success est nécessaire';

$string['crm_assistant_evidence_recommendation_review_learning_difficulty'] =
    'Une difficulté pédagogique potentielle a été détectée';

$string['crm_assistant_evidence_activity_inactive_14d'] =
    'Inactivité supérieure à 14 jours';

$string['crm_assistant_evidence_learning_not_started'] =
    'Le parcours pédagogique n’a pas encore été commencé';

$string['crm_assistant_evidence_activity_never_accessed'] =
    'Aucune activité pédagogique n’a encore été consultée';

$string['crm_assistant_evidence_value_learning_low_progress'] =
    'Progression actuelle : {$a} %';

$string['crm_assistant_evidence_value_activity_inactive_14d'] =
    '{$a} jour(s) depuis la dernière activité';

$string['crm_daily_priorities_item_fallback'] =
    'Action CRM recommandée';

$string['crm_intelligence_alert_fallback'] =
    'Alerte CRM à examiner';

$string['dashboard_revenue_currency_select'] =
    'Choisir la devise du chiffre d’affaires';

$string['dashboard_revenue_subscriptions'] =
    'Abonnements';

$string['dashboard_revenue_digital'] =
    'Produits digitaux';

$string['dashboard_revenue_no_data'] =
    'Aucun chiffre d’affaires sur cette période';

$string['dashboard_new_trials'] = 'Nouveaux essais';
$string['dashboard_new_customers'] = 'Nouveaux clients';

$string['dashboard_trial_customer_ratio'] =
    'Ratio clients / essais de la période';

$string['dashboard_trial_customer_ratio_help'] =
    'Compare les nouveaux clients et les utilisateurs ayant démarré un essai pendant la période sélectionnée. Cet indicateur n’est pas une conversion cohortée : un client peut avoir commencé son essai pendant une période antérieure.';

$string['dashboard_trial_customer_ratio_unavailable'] = '—';

$string['dashboard_trial_customer_ratio_value'] = '{$a} %';

$string['dashboard_funnel_title'] =
    'Funnel acquisition';

$string['dashboard_funnel_subtitle'] =
    'Cohortes et conversion vérifiable à {$a} jours';

$string['dashboard_funnel_new_users'] =
    'Nouveaux utilisateurs';

$string['dashboard_funnel_trial_users'] =
    'Premiers essais';

$string['dashboard_funnel_new_customers'] =
    'Nouveaux clients payants';

$string['dashboard_funnel_digital_buyers'] =
    'Acheteurs digitaux';

$string['dashboard_funnel_conversion'] =
    'Conversion des essais';

$string['dashboard_funnel_conversion_details'] =
    '{$a->converted} conversion(s) parmi {$a->mature} essai(s) ayant terminé leur fenêtre de {$a->days} jours';

$string['dashboard_funnel_pending_observation'] =
    '{$a} essai(s) récent(s) sont encore dans leur fenêtre d’observation.';

$string['dashboard_funnel_rate_unavailable'] =
    'Non disponible';

$string['dashboard_funnel_rate_value'] =
    '{$a} %';

$string['dashboard_funnel_trend_stable'] =
    'Stable par rapport à la période précédente';

$string['dashboard_funnel_trend_not_comparable'] =
    'Pas de comparaison disponible';

$string['dashboard_funnel_trend_absolute'] =
    '{$a} par rapport à la période précédente';

$string['dashboard_funnel_trend_percent'] =
    '{$a} % par rapport à la période précédente';

$string['dashboard_funnel_trend_points'] =
    '{$a} point(s) par rapport à la période précédente';

$string['dashboard_funnel_explorer_active'] =
    'Filtre Funnel actif';

$string['dashboard_funnel_explorer_new_users'] =
    'Utilisateurs créés pendant la période';

$string['dashboard_funnel_explorer_trial_users'] =
    'Utilisateurs dont le premier essai a commencé pendant la période';

$string['dashboard_funnel_explorer_new_customers'] =
    'Utilisateurs dont le premier paiement réussi a eu lieu pendant la période';

$string['dashboard_funnel_explorer_digital_buyers'] =
    'Acheteurs digitaux distincts pendant la période';

$string['dashboard_funnel_explorer_converted_trials'] =
    'Essais de la cohorte convertis dans la fenêtre de {$a} jours';

// Phase 7.75E - Dashboard CRM trends.
$string['crm_trends_subtitle'] = '{$a->analysed} utilisateurs comparables sur {$a->available} profils actualisés.';
$string['crm_trends_users'] = 'utilisateur(s)';
$string['crm_trends_previous_value'] = 'Période précédente : {$a}';
$string['crm_trends_difference_only'] = '{$a} utilisateur(s)';
$string['crm_trends_difference_with_percent'] = '{$a->difference} utilisateur(s) · {$a->variation} %';
$string['crm_trends_stable'] = 'Stable';
$string['crm_trends_open_explorer'] = 'Ouvrir l’Explorer';
$string['crm_trends_freshness'] = 'Dernier snapshot : {$a}';
$string['crm_trends_freshness_unknown'] = 'Date du dernier snapshot indisponible.';
$string['crm_trends_no_current_data'] = 'Aucun snapshot Intelligence n’est disponible pour cette période.';
$string['crm_trends_insufficient_data'] = 'Des snapshots existent, mais il n’y a pas encore assez d’historique pour calculer une évolution.';
$string['crm_trends_no_movements'] = 'Aucune variation significative n’a été détectée pendant cette période.';
$string['crm_trends_error'] = 'Les tendances CRM ne peuvent pas être chargées actuellement.';

$string['crm_trends_metric_risk_up'] = 'Risque en hausse';
$string['crm_trends_metric_risk_up_desc'] = 'Profils dont le score de risque a augmenté significativement.';
$string['crm_trends_metric_risk_down'] = 'Risque en baisse';
$string['crm_trends_metric_risk_down_desc'] = 'Profils dont le score de risque s’est amélioré significativement.';

$string['crm_trends_metric_engagement_up'] = 'Engagement en hausse';
$string['crm_trends_metric_engagement_up_desc'] = 'Profils dont le score d’engagement a progressé.';
$string['crm_trends_metric_engagement_down'] = 'Engagement en baisse';
$string['crm_trends_metric_engagement_down_desc'] = 'Profils dont le score d’engagement a diminué.';

$string['crm_trends_metric_global_up'] = 'Score global en hausse';
$string['crm_trends_metric_global_up_desc'] = 'Profils dont la santé CRM globale s’est améliorée.';
$string['crm_trends_metric_global_down'] = 'Score global en baisse';
$string['crm_trends_metric_global_down_desc'] = 'Profils dont la santé CRM globale s’est dégradée.';

$string['crm_trends_metric_unknown'] = 'Évolution CRM';
$string['crm_trends_metric_unknown_desc'] = 'Variation détectée dans les données CRM.';

// Phase 7.75E - User Explorer trend drill-down.
$string['crm_trends_metric_open'] = 'Afficher les utilisateurs concernés par : {$a}';
$string['crm_user_explorer_trend_active'] = 'Filtre de tendance actif';
$string['crm_user_explorer_trend_period'] = 'Du {$a->start} au {$a->end}';
$string['crm_user_explorer_trend_threshold'] = 'Variation minimale : {$a} points';
$string['crm_user_explorer_trend_clear'] = 'Quitter la tendance';

$string['crm_intelligence_alert_priority_critical'] =
    'Critique';

$string['crm_intelligence_alert_priority_high'] =
    'Élevée';

$string['crm_intelligence_alert_priority_normal'] =
    'Normale';

$string['crm_intelligence_alert_priority_label'] =
    'Priorité : {$a}';

$string['crm_intelligence_alert_signal_date'] =
    'Signal CRM évalué le {$a}';

$string['crm_intelligence_alert_signal_age'] =
    'Ancienneté du signal : {$a}';

$string['crm_intelligence_alert_next_action_label'] =
    'Prochaine action recommandée';

$string['crm_intelligence_alert_next_action_high_risk_user'] =
    'Vérifier la situation du client et organiser un suivi prioritaire.';

$string['crm_intelligence_alert_next_action_trial_without_purchase'] =
    'Contacter l’utilisateur pour identifier les freins à l’achat.';

$string['crm_intelligence_alert_next_action_expired_without_reactivation'] =
    'Proposer une réactivation ou une offre adaptée à son historique.';

$string['crm_intelligence_alert_next_action_inactive_user'] =
    'Vérifier la dernière activité et préparer une relance personnalisée.';

$string['crm_intelligence_alert_next_action_hot_opportunity'] =
    'Contacter rapidement l’utilisateur avec une proposition commerciale adaptée.';

$string['crm_intelligence_alert_next_action_default'] =
    'Consulter la fiche utilisateur et déterminer la prochaine action.';

$string['crm_intelligence_alert_work_item'] =
    'Work Item actif';

$string['crm_intelligence_alert_cs_plan'] =
    'Plan Customer Success';

$string['crm_intelligence_alert_responsible'] =
    'Responsable : {$a}';

$string['crm_intelligence_alert_due_date'] =
    'Échéance : {$a}';

$string['crm_intelligence_alert_target_date'] =
    'Date cible : {$a}';

$string['crm_intelligence_alert_open_work_item'] =
    'Ouvrir le Work Item';

$string['crm_intelligence_alert_create_work_item'] =
    'Créer un Work Item';

$string['crm_intelligence_alert_open_cs_plan'] =
    'Ouvrir le plan CS';

$string['dashboard_state_loading_title'] =
    'Chargement en cours';

$string['dashboard_state_loading_description'] =
    'Les informations de cette carte sont en cours de préparation.';

$string['dashboard_state_error_title'] =
    'Impossible de charger cette carte';

$string['dashboard_state_error_description'] =
    'Une erreur est survenue pendant le chargement des informations.';

$string['dashboard_state_empty_title'] =
    'Aucune information disponible';

$string['dashboard_state_empty_description'] =
    'Il n’y a rien à afficher pour le moment.';

$string['dashboard_state_retry'] =
    'Réessayer';

$string['dashboard_open_all'] =
    'Tout afficher';

$string['admin_event_email_password_reset_notice_sent'] =
    'Notification de réinitialisation du mot de passe envoyée';

$string['admin_event_email_welcome_sent'] =
    'E-mail de bienvenue envoyé';

$string['admin_event_email_receipt_sent'] =
    'Reçu de paiement envoyé';

$string['admin_event_email_subscription_access_sent'] =
    'Informations d’accès à l’abonnement envoyées';

$string['admin_event_user_password_updated'] =
    'Mot de passe utilisateur mis à jour';

$string['admin_event_user_note_added'] =
    'Note CRM ajoutée';

$string['admin_event_subscription_created'] =
    'Abonnement créé';

$string['admin_event_subscription_created_manual'] =
    'Abonnement créé manuellement';

$string['admin_event_subscription_updated'] =
    'Abonnement mis à jour';

$string['admin_event_subscription_deleted'] =
    'Abonnement supprimé';

$string['admin_event_subscription_status_updated'] =
    'Statut de l’abonnement mis à jour';

$string['admin_event_subscription_dates_updated'] =
    'Dates de l’abonnement mises à jour';

$string['admin_event_subscription_created_auto'] =
    'Abonnement créé automatiquement';

$string['admin_event_subscription_extended'] =
    'Abonnement prolongé';

$string['admin_event_digital_provider_checked'] =
    'Statut du paiement digital vérifié';

$string['admin_event_payment_request_created'] =
    'Demande de paiement créée';

$string['admin_event_payment_request_paid'] =
    'Demande de paiement payée';

$string['admin_event_payment_request_failed'] =
    'Échec de la demande de paiement';

$string['admin_event_payment_request_cancelled'] =
    'Demande de paiement annulée';

$string['admin_event_trial_started'] =
    'Période d’essai démarrée';

$string['admin_event_trial_expired'] =
    'Période d’essai expirée';

$string['admin_event_work_item_created'] =
    'Work Item créé';

$string['admin_event_work_item_status_changed'] =
    'Statut du Work Item modifié';

$string['admin_event_work_item_priority_changed'] =
    'Priorité du Work Item modifiée';

$string['admin_event_work_item_assigned'] =
    'Work Item attribué';

$string['admin_event_work_item_comment_added'] =
    'Commentaire ajouté au Work Item';

$string['admin_event_work_item_linked'] =
    'Élément lié au Work Item';

$string['admin_event_work_item_suggestion_opened'] =
    'Suggestion de Work Item ouverte';

$string['admin_event_work_item_created_from_recommendation'] =
    'Work Item créé depuis une recommandation';

$string['admin_event_work_item_duplicate_override'] =
    'Création forcée malgré un doublon potentiel';

$string['admin_event_recommendation_created'] =
    'Recommandation créée';

$string['admin_event_recommendation_refreshed'] =
    'Recommandation actualisée';

$string['admin_event_recommendation_accepted'] =
    'Recommandation acceptée';

$string['admin_event_recommendation_dismissed'] =
    'Recommandation ignorée';

$string['admin_event_recommendation_completed'] =
    'Recommandation terminée';

$string['admin_event_recommendation_expired'] =
    'Recommandation expirée';

$string['admin_event_recommendation_run_completed'] =
    'Génération des recommandations terminée';

$string['admin_event_recommendation_run_partial'] =
    'Génération des recommandations partiellement terminée';

$string['admin_event_recommendation_run_failed'] =
    'Échec de la génération des recommandations';

$string['admin_event_recommendation_run_skipped'] =
    'Génération des recommandations ignorée';

$string['admin_event_description_reference'] =
    'Référence : {$a}';

$string['admin_event_description_transition'] =
    '{$a->from} → {$a->to}';

$string['admin_event_description_status'] =
    'Statut : {$a}';

$string['admin_event_description_priority'] =
    'Priorité : {$a}';

$string['admin_event_description_plan'] =
    'Plan : {$a}';

$string['admin_event_description_contact'] =
    'Contact : {$a}';

$string['admin_event_description_recommendation'] =
    'Recommandation : {$a}';

$string['admin_event_description_cs_plan'] =
    '{$a->reference} — {$a->title}';

$string['admin_event_description_cs_step'] =
    '{$a->plan} — {$a->step}';

$string['dashboard_activity_actor'] =
    'Par {$a}';

$string['dashboard_activity_system_actor'] =
    'Action automatique';

$string['dashboard_activity_open'] =
    'Ouvrir';

$string['dashboard_activity_target'] =
    'Client : {$a}';

$string['dashboard_activity_exact_date'] =
    'Enregistré le {$a}';

$string['crm_app_navigation'] =
    'Navigation principale du CRM';

$string['crm_admin_tools_title'] =
    'Boîte à outils administrateur';

$string['crm_admin_tools_description'] =
    'Exécutez et contrôlez les opérations techniques du CRM depuis une interface sécurisée.';

$string['crm_admin_tool_busy'] =
    'Cette opération est déjà en cours d’exécution.';

$string['crm_admin_tool_failed'] =
    'L’opération a échoué. Consultez son historique pour plus de détails.';

$string['crm_admin_tool_status_running'] =
    'En cours';

$string['crm_admin_tool_status_success'] =
    'Terminée';

$string['crm_admin_tool_status_failed'] =
    'Échec';

$string['crm_admin_tool_status_busy'] =
    'Déjà en cours';

$string['crm_admin_tool_status_cancelled'] =
    'Annulée';

$string['crm_admin_tool_risk_low'] =
    'Risque faible';

$string['crm_admin_tool_risk_normal'] =
    'Risque modéré';

$string['crm_admin_tool_risk_high'] =
    'Risque élevé';

$string['crm_admin_tools_nav'] =
    'Outils';

$string['crm_admin_tool_unknown'] =
    'L’outil administrateur demandé est introuvable.';

$string['crm_admin_tools_empty'] =
    'Aucun outil administrateur n’est disponible pour votre rôle.';

$string['crm_admin_tool_open'] =
    'Ouvrir';

$string['crm_admin_tool_execute'] =
    'Exécuter maintenant';

$string['crm_admin_tool_confirmation_warning'] =
    'Cette opération peut modifier les données du CRM. Vérifiez les paramètres avant de continuer.';

$string['crm_admin_tool_limit'] =
    'Nombre maximal d’éléments à traiter';

$string['crm_admin_tool_reset_cursor'] =
    'Reprendre le traitement des recommandations depuis le début';

$string['crm_admin_tool_never_run'] =
    'Jamais exécuté';

$string['crm_admin_tool_last_run'] =
    'Dernière exécution : {$a->date} — {$a->status}';

$string['crm_admin_tool_history'] =
    'Historique des opérations';

$string['crm_admin_tool_history_empty'] =
    'Aucune opération administrateur n’a encore été exécutée.';

$string['crm_admin_tool_history_date'] =
    'Date';

$string['crm_admin_tool_history_tool'] =
    'Outil';

$string['crm_admin_tool_history_actor'] =
    'Utilisateur';

$string['crm_admin_tool_history_status'] =
    'État';

$string['crm_admin_tool_history_duration'] =
    'Durée';

$string['crm_admin_tool_inbox_sync'] =
    'Synchroniser la CRM Inbox';

$string['crm_admin_tool_inbox_sync_desc'] =
    'Récupère les nouveaux messages des comptes Inbox actifs.';

$string['crm_admin_tool_inbox_sync_success'] =
    'La synchronisation Inbox est terminée.';

$string['crm_admin_tool_inbox_sync_partial'] =
    'La synchronisation Inbox est terminée avec des erreurs.';

$string['crm_admin_tool_inbox_diagnostics'] =
    'Diagnostiquer la CRM Inbox';

$string['crm_admin_tool_inbox_diagnostics_desc'] =
    'Contrôle la configuration, les tables, les identifiants et les connexions IMAP/SMTP.';

$string['crm_admin_tool_inbox_diagnostics_success'] =
    'Tous les contrôles Inbox sont valides.';

$string['crm_admin_tool_inbox_diagnostics_failed'] =
    'Certains contrôles Inbox ont échoué.';

$string['crm_admin_tool_automations'] =
    'Relancer les automatisations';

$string['crm_admin_tool_automations_desc'] =
    'Exécute immédiatement les scanners et règles d’automatisation CRM.';

$string['crm_admin_tool_automations_success'] =
    'Les automatisations CRM ont été exécutées.';

$string['crm_admin_tool_intelligence'] =
    'Recalculer les scores Intelligence';

$string['crm_admin_tool_intelligence_desc'] =
    'Recalcule et mémorise les snapshots des scores CRM.';

$string['crm_admin_tool_intelligence_success'] =
    'Les snapshots Intelligence ont été recalculés.';

$string['crm_admin_tool_recommendations'] =
    'Recalculer les recommandations';

$string['crm_admin_tool_recommendations_desc'] =
    'Exécute un nouveau lot du moteur de recommandations CRM.';

$string['crm_admin_tool_recommendations_success'] =
    'Le lot de recommandations est terminé.';

$string['crm_admin_tool_recommendations_partial'] =
    'Le lot de recommandations est terminé partiellement ou avec des erreurs.';

$string['crm_admin_tool_digital_reconciliation'] =
    'Réconcilier les paiements digitaux';

$string['crm_admin_tool_digital_reconciliation_desc'] =
    'Vérifie auprès des fournisseurs les demandes de paiement digital encore en attente.';

$string['crm_admin_tool_digital_reconciliation_success'] =
    'La réconciliation des paiements digitaux est terminée.';

$string['crm_admin_tool_digital_reconciliation_partial'] =
    'La réconciliation digitale est terminée avec des erreurs.';

$string['crm_admin_tool_help_validation'] =
    'Valider le Help Center';

$string['crm_admin_tool_help_validation_desc'] =
    'Contrôle les articles, les guides, l’onboarding et les traductions du Help Center.';

$string['crm_admin_tool_help_validation_success'] =
    'Le Help Center est valide.';

$string['crm_admin_tool_help_validation_failed'] =
    'Le Help Center contient des erreurs.';

$string['csplancommandsubtitle'] =
    'Ouvrir et gérer le plan Customer Success de cet utilisateur';

$string['crm_admin_tool_confirmation_required'] =
    'Vous devez confirmer explicitement cette opération avant de l’exécuter.';

$string['crm_admin_tool_confirmation_checkbox'] =
    'Je comprends les conséquences de cette opération et je confirme son exécution.';

$string['crm_admin_tool_limit_help'] =
    'Valeur par défaut : {$a->default}. Maximum autorisé : {$a->maximum}.';

$string['crm_admin_tool_unknown_actor'] =
    'Utilisateur indisponible (#{$a})';

$string['err_invalid_redirect_url'] =
    'La passerelle de paiement a retourné une adresse de redirection invalide.';

$string['payment_error_session_create'] =
    'La page de paiement n’a pas pu être ouverte. Aucun paiement n’a été effectué. Vous pouvez réessayer dans quelques instants.';

$string['payment_error_digital_session_create'] =
    'La page de paiement de votre achat n’a pas pu être ouverte. Aucun paiement n’a été effectué.';

$string['payment_error_retry'] =
    'La nouvelle tentative de paiement n’a pas pu être lancée. Aucun nouveau paiement n’a été effectué.';

$string['payment_error_invalid_redirect'] =
    'La passerelle de paiement a retourné une adresse invalide. Aucun paiement n’a été effectué.';

$string['payment_error_provider_unavailable'] =
    'La passerelle de paiement est momentanément indisponible. Aucun paiement n’a été effectué.';

$string['payment_error_reference'] =
    'Référence de l’incident : {$a}';

$string['crm_topbar_brand_suffix'] = 'CRM';
$string['crm_topbar_dashboard_link'] = 'Ouvrir le Dashboard CampusFR CRM';
$string['crm_topbar_moodle_admin'] = 'Administration Moodle';

$string['crm_topbar_user_menu'] = 'Ouvrir le menu utilisateur';
$string['crm_topbar_user_navigation'] = 'Navigation du compte utilisateur';
$string['crm_topbar_view_profile'] = 'Voir le profil';
$string['crm_topbar_my_courses'] = 'Mes cours';
$string['crm_topbar_my_campus'] = 'Mon Campus';
$string['crm_topbar_my_resources'] = 'Mes ressources';
$string['crm_topbar_my_purchases'] = 'Mes achats';
$string['crm_topbar_shop'] = 'Boutique';
$string['crm_topbar_grades'] = 'Notes';
$string['crm_topbar_calendar'] = 'Calendrier';
$string['crm_topbar_preferences'] = 'Préférences';
$string['crm_topbar_switch_role'] = 'Prendre le rôle…';
$string['crm_topbar_logout'] = 'Déconnexion';

$string['crm_topbar_language'] = 'Langue';
$string['crm_topbar_language_menu'] = 'Choisir la langue';
$string['crm_topbar_language_navigation'] = 'Langues disponibles';

$string['dashboard_personalization_open'] = 'Personnaliser le Dashboard';
$string['dashboard_personalization_title'] = 'Personnaliser le Dashboard';
$string['dashboard_personalization_description'] = 'Choisissez les Cards à afficher et réorganisez-les par glisser-déposer ou avec les boutons Monter et Descendre.';
$string['dashboard_personalization_close'] = 'Fermer la personnalisation du Dashboard';
$string['dashboard_personalization_save'] = 'Enregistrer la disposition';
$string['dashboard_personalization_reset'] = 'Restaurer la disposition par défaut';
$string['dashboard_personalization_reset_confirm'] = 'Restaurer la disposition par défaut du Dashboard ?';
$string['dashboard_personalization_save_error'] = 'La disposition du Dashboard n’a pas pu être enregistrée.';
$string['dashboard_personalization_drag'] = 'Faire glisser pour déplacer';
$string['dashboard_personalization_move_up'] = 'Monter la Card « {$a} »';
$string['dashboard_personalization_move_down'] = 'Descendre la Card « {$a} »';
$string['dashboard_personalization_visibility'] = 'Afficher la Card « {$a} »';
$string['dashboard_personalization_zone_hero'] = 'Indicateurs principaux';
$string['dashboard_personalization_zone_main'] = 'Dashboard principal';
$string['dashboard_personalization_zone_side'] = 'Colonne latérale';
$string['dashboard_personalization_main_empty'] = 'Toutes les Cards du Dashboard principal sont actuellement masquées. Utilisez le bouton Personnaliser pour en réafficher.';

$string['dashboard_personalization_card_stats'] = 'Indicateurs principaux';
$string['dashboard_personalization_card_stats_description'] = 'Utilisateurs, abonnements, essais, achats et chiffre d’affaires.';
$string['dashboard_personalization_card_intelligence'] = 'Intelligence CRM';
$string['dashboard_personalization_card_intelligence_description'] = 'Scores, segments, opportunités et profils prioritaires.';
$string['dashboard_personalization_card_assistant'] = 'Assistant CRM';
$string['dashboard_personalization_card_assistant_description'] = 'Recommandations et actions proposées par l’Assistant.';
$string['dashboard_personalization_card_inbox'] = 'Inbox CRM';
$string['dashboard_personalization_card_inbox_description'] = 'Messages, conversations non lues et activité récente.';
$string['dashboard_personalization_card_work'] = 'Work Items';
$string['dashboard_personalization_card_work_description'] = 'Tâches assignées, urgentes, en retard ou non attribuées.';
$string['dashboard_personalization_card_customer_success'] = 'Customer Success';
$string['dashboard_personalization_card_customer_success_description'] = 'Plans actifs, progression, blocages et situations critiques.';
$string['dashboard_personalization_card_issues'] = 'À traiter';
$string['dashboard_personalization_card_issues_description'] = 'Problèmes et anomalies demandant une intervention.';
$string['dashboard_personalization_card_priorities'] = 'Priorités quotidiennes';
$string['dashboard_personalization_card_priorities_description'] = 'Profils et actions prioritaires du jour.';
$string['dashboard_personalization_card_funnel'] = 'Funnel';
$string['dashboard_personalization_card_funnel_description'] = 'Acquisition, essais, conversions et nouveaux clients.';
$string['dashboard_personalization_card_trends'] = 'Tendances';
$string['dashboard_personalization_card_trends_description'] = 'Évolution du risque, de l’engagement et de la progression.';
$string['dashboard_personalization_card_intelligence_alerts'] = 'Alertes Intelligence';
$string['dashboard_personalization_card_intelligence_alerts_description'] = 'Alertes CRM enrichies et contexte Customer Success.';
$string['dashboard_personalization_card_navigation'] = 'Raccourcis administratifs';
$string['dashboard_personalization_card_navigation_description'] = 'Accès aux utilisateurs, plans, achats et outils.';
$string['dashboard_personalization_card_activity'] = 'Activité récente';
$string['dashboard_personalization_card_activity_description'] = 'Derniers événements enregistrés dans le CRM.';
$string['dashboard_personalization_card_team'] = 'Équipe';
$string['dashboard_personalization_card_team_description'] = 'Résumé des éléments attribués à l’utilisateur connecté.';
$string['dashboard_personalization_zone_onboarding'] = 'Prise en main du CRM';

$string['workspace_toolbar_title'] = 'Mode édition';
$string['workspace_toolbar_description'] = 'Personnalisez votre espace de travail. Les modifications de disposition seront appliquées après leur enregistrement.';
$string['workspace_toolbar_status_clean'] = 'Aucune modification non enregistrée';
$string['workspace_toolbar_status_dirty'] = 'Modifications non enregistrées';
$string['workspace_toolbar_status_saving'] = 'Enregistrement en cours…';
$string['workspace_toolbar_hidden_singular'] = 'élément masqué';
$string['workspace_toolbar_hidden_plural'] = 'éléments masqués';
$string['workspace_toolbar_reset'] = 'Restaurer par défaut';
$string['workspace_toolbar_cancel'] = 'Annuler';
$string['workspace_toolbar_save'] = 'Enregistrer';
$string['workspace_item_type_card'] = 'Carte';
$string['workspace_item_type_widget'] = 'Widget';
$string['workspace_item_type_system'] = 'Système';
$string['workspace_item_drag_handle'] = 'Déplacer cet élément';
$string['workspace_item_drag_handle_named'] = 'Déplacer l’élément « {$a} »';
$string['workspace_item_menu_open_named'] = 'Ouvrir les actions de l’élément « {$a} »';
$string['workspace_item_menu_label_named'] = 'Actions disponibles pour l’élément « {$a} »';
$string['workspace_item_move_before'] = 'Déplacer avant';
$string['workspace_item_move_after'] = 'Déplacer après';
$string['workspace_item_hide'] = 'Masquer';
$string['workspace_item_reset'] = 'Réinitialiser cet élément';
$string['workspace_action_configure'] = 'Configurer';
$string['workspace_action_duplicate'] = 'Dupliquer';

$string['dashboard_category_overview'] = 'Vue d’ensemble';
$string['dashboard_category_intelligence'] = 'Intelligence';
$string['dashboard_category_operations'] = 'Opérations';
$string['dashboard_category_customer_success'] = 'Customer Success';
$string['dashboard_category_navigation_activity'] = 'Navigation et activité';
$string['dashboard_category_team'] = 'Équipe';
$string['dashboard_category_system'] = 'Système';
$string['dashboard_category_other'] = 'Autre';

$string['dashboard_personalization_width_compact'] = 'Compacte';
$string['dashboard_personalization_width_medium'] = 'Moyenne';
$string['dashboard_personalization_width_full'] = 'Pleine largeur';

$string['dashboard_personalization_type_card'] = 'Card';
$string['dashboard_personalization_type_widget'] = 'Widget';
$string['dashboard_personalization_type_system'] = 'Système';

$string['dashboard_personalization_period_aware'] = 'Suit la période';
$string['dashboard_personalization_order_hint'] = 'Réorganisez les éléments directement dans le Dashboard en mode édition.';
$string['dashboard_workspace_action_open_details'] = 'Ouvrir la vue détaillée';
$string['dashboard_workspace_empty_hero'] = 'Aucun indicateur principal n’est actuellement affiché.';
$string['dashboard_workspace_empty_main'] = 'Aucune Card n’est actuellement affichée dans la zone principale.';
$string['dashboard_workspace_empty_side'] = 'Aucun élément n’est actuellement affiché dans la colonne latérale.';
$string['dashboard_period_year'] = 'Cette année';
$string['dashboard_period_all'] = 'Depuis toujours';

$string['dashboard_trends_all_time_title'] = 'Vue cumulative';
$string['dashboard_trends_all_time_subtitle'] = 'Données depuis la création du CRM';
$string['dashboard_trends_all_time_message'] = 'Les tendances nécessitent une période précédente comparable. Sélectionnez Aujourd’hui, Cette semaine, Ce mois ou Cette année pour afficher les évolutions.';

$string['inbox_workspace_name'] = 'Espace de travail Inbox';

$string['inbox_workspace_navigation'] = 'Navigation';

$string['inbox_workspace_list'] = 'Conversations';

$string['inbox_workspace_reading'] = 'Lecture';

$string['inbox_workspace_context'] = 'Contexte client';
$string['inbox_workspace_filters_label'] =
    'Filtres de l’Inbox';

$string['inbox_workspace_filters_description'] =
    'Rechercher et filtrer les conversations de l’Inbox.';

$string['inbox_workspace_thread_list_label'] =
    'Liste des conversations';

$string['inbox_workspace_thread_list_description'] =
    'Consulter les conversations correspondant aux filtres actifs.';

$string['inbox_thread_workspace_messages'] = 'Messages';
$string['inbox_thread_workspace_messages_description'] =
    'Consulter l’historique complet de la conversation.';
$string['inbox_thread_workspace_reply'] = 'Répondre';
$string['inbox_thread_workspace_reply_description'] =
    'Rédiger une réponse à cette conversation.';
$string['inbox_thread_workspace_context'] =
    'Conversation et contact';
$string['inbox_thread_workspace_context_description'] =
    'Consulter le contact, le statut et les actions disponibles.';
$string['inbox_thread_workspace_ai'] = 'Assistant IA';
$string['inbox_thread_workspace_ai_description'] =
    'Analyser la conversation et préparer une réponse.';
$string['inbox_thread_workspace_context_zone'] =
    'Contexte de la conversation';
$string['inbox_workspace_personalization_open'] =
    'Personnaliser';
$string['inbox_workspace_personalization_title'] =
    'Personnaliser la conversation';
$string['inbox_workspace_personalization_description'] =
    'Choisissez les panneaux visibles et réorganisez le contexte de la conversation.';
$string['inbox_workspace_personalization_close'] =
    'Fermer la personnalisation';
$string['inbox_workspace_personalization_save_error'] =
    'La disposition de la conversation n’a pas pu être enregistrée.';
$string['inbox_workspace_personalization_reset_confirm'] =
    'Réinitialiser la disposition de la conversation ?';
    
$string['inbox_workspace_zone_reading'] = 'Conversation';
$string['inbox_workspace_zone_context'] = 'Contexte';

$string['inbox_workspace_reading_placeholder_label'] =
    'Aperçu de la conversation';

$string['inbox_workspace_reading_placeholder_item_description'] =
    'Zone réservée à la lecture de la conversation sélectionnée.';

$string['inbox_workspace_reading_placeholder_title'] =
    'Sélectionnez une conversation';

$string['inbox_workspace_reading_placeholder_description'] =
    'L’aperçu de la conversation apparaîtra ici.';

$string['inbox_workspace_context_placeholder_label'] =
    'Contexte de la conversation';

$string['inbox_workspace_context_placeholder_item_description'] =
    'Zone réservée aux informations sur le contact et la conversation.';

$string['inbox_workspace_context_placeholder_title'] =
    'Informations contextuelles';

$string['inbox_workspace_context_placeholder_description'] =
    'Sélectionnez une conversation pour afficher le contact, le statut et les informations utiles.';

$string['inbox_thread_workspace_overview'] =
    'Vue d’ensemble';

$string['inbox_thread_workspace_overview_description'] =
    'Statut, priorité, boîte de réception et informations principales de la conversation.';

$string['inbox_thread_workspace_contact'] =
    'Contact';

$string['inbox_thread_workspace_contact_description'] =
    'Coordonnées du contact et lien avec son profil CRM.';

$string['inbox_thread_workspace_actions'] =
    'Actions';

$string['inbox_thread_workspace_actions_description'] =
    'Actions de gestion disponibles pour cette conversation.';

$string['inbox_thread_overview_account'] =
    'Boîte de réception';

$string['inbox_thread_overview_folder'] =
    'Dossier';

$string['inbox_thread_overview_messages'] =
    'Messages';

$string['inbox_thread_overview_unread'] =
    'Non lus';

$string['inbox_thread_overview_assignment'] =
    'Assignation';

$string['inbox_thread_overview_last_message'] =
    'Dernier message';

$string['inbox_thread_assignment_team'] =
    'Équipe : {$a}';

$string['inbox_thread_assignment_user'] =
    'Utilisateur : {$a}';

$string['inbox_thread_assignment_unassigned'] =
    'Non assignée';

$string['inbox_thread_contact_title'] =
    'Contact';

$string['inbox_thread_contact_unavailable'] =
    'Aucune coordonnée disponible.';

$string['inbox_thread_contact_open_profile'] =
    'Ouvrir le profil CRM';

$string['inbox_thread_contact_external_description'] =
    'Ce contact n’est pas encore associé à un utilisateur Moodle.';

$string['inbox_thread_actions_title'] =
    'Actions';

$string['inbox_thread_actions_description'] =
    'Modifier le statut, archiver la conversation ou créer une tâche de suivi.';

$string['user360_workspace_region_label'] =
    'Espace de travail du profil utilisateur';

$string['user360_workspace_hero'] =
    'Identité utilisateur';

$string['user360_workspace_hero_description'] =
    'Identité, statut CRM, tags et informations principales de l’utilisateur.';

$string['user360_workspace_zone_hero'] =
    'Identité';

$string['user360_workspace_zone_main'] =
    'Informations principales';

$string['user360_workspace_zone_sidebar'] =
    'Informations complémentaires';

$string['user360_workspace_zone_timeline'] =
    'Historique';

$string['user360_workspace_personalization_open'] =
    'Personnaliser le profil';

$string['user360_workspace_personalization_title'] =
    'Personnaliser le profil utilisateur';

$string['user360_workspace_personalization_description'] =
    'Choisissez les panneaux à afficher et adaptez leur ordre à votre façon de travailler.';

$string['user360_workspace_personalization_close'] =
    'Fermer la personnalisation';

$string['user360_workspace_personalization_save_error'] =
    'La personnalisation du profil utilisateur n’a pas pu être enregistrée.';

$string['user360_workspace_personalization_reset_confirm'] =
    'Réinitialiser la disposition du profil utilisateur ?';

$string['user360_workspace_intelligence'] =
    'Intelligence CRM';

$string['user360_workspace_intelligence_description'] =
    'Scores, tendances, segments, opportunités et recommandations concernant cet utilisateur.';

$string['user360_workspace_customer_success'] =
    'Customer Success';

$string['user360_workspace_customer_success_description'] =
    'Plans Customer Success, actions de suivi et accompagnement de l’utilisateur.';

$string['user360_workspace_inbox'] =
    'Inbox';

$string['user360_workspace_inbox_description'] =
    'Conversations, messages non lus et échanges récents avec cet utilisateur.';

$string['user360_workspace_notes'] =
    'Notes';

$string['user360_workspace_notes_description'] =
    'Notes CRM internes associées à cet utilisateur.';

$string['user360_workspace_work_items'] =
    'Tâches';

$string['user360_workspace_work_items_description'] =
    'Tâches et éléments de travail associés à cet utilisateur.';

$string['user360_workspace_timeline'] =
    'Timeline';

$string['user360_workspace_timeline_description'] =
    'Historique chronologique complet des événements liés à cet utilisateur.';

$string['user360_workspace_zone_summary'] =
    'Résumé';

$string['user360_workspace_stats'] =
    'Vue d’ensemble';

$string['user360_workspace_stats_description'] =
    'Statut CRM, abonnements, achats, cours accessibles, revenus et dernière activité.';

$string['user360_workspace_quick_actions'] =
    'Actions rapides';

$string['user360_workspace_quick_actions_description'] =
    'Actions administratives et ajout rapide d’une note pour cet utilisateur.';

$string['user360_workspace_assistant'] =
    'Assistant CRM';

$string['user360_workspace_assistant_description'] =
    'Analyse, recommandations et actions proposées par l’assistant CRM.';

$string['user360_workspace_commercial'] =
    'Activité commerciale';

$string['user360_workspace_commercial_description'] =
    'Abonnements et achats digitaux associés à cet utilisateur.';

$string['user360_workspace_courses'] =
    'Cours accessibles';

$string['user360_workspace_courses_description'] =
    'Cours actuellement accessibles à cet utilisateur.';

$string['crm_user_not_found'] = 'Utilisateur introuvable';
$string['crm_user_not_found_description'] = 'Le profil CRM demandé ne peut pas être affiché.';
$string['crm_user_not_found_message'] = 'Aucun utilisateur Moodle actif ne correspond à l’identifiant {$a}. Il a peut-être été supprimé ou le lien utilisé est obsolète.';
$string['crm_user_not_found_back'] = 'Retour aux utilisateurs';
$string['crm_user_deleted'] = 'Compte Moodle supprimé';
$string['crm_user_deleted_description'] = 'Cet utilisateur n’est plus actif dans Moodle.';
$string['crm_user_deleted_message'] = 'Le compte Moodle associé à l’identifiant {$a} a été supprimé. Certaines données historiques du CRM peuvent néanmoins être encore disponibles.';
$string['crm_user_history_title'] = 'Profil CRM historique · utilisateur {$a}';
$string['crm_user_history_description'] = 'Données CRM conservées pour un compte Moodle supprimé.';
$string['crm_user_history_readonly'] = 'Profil historique en lecture seule';
$string['crm_user_history_readonly_description'] = 'Le compte Moodle associé à l’identifiant {$a} a été supprimé. Les données affichées ici ne peuvent pas être utilisées pour agir sur le compte.';
$string['crm_user_history_summary'] = 'Résumé du profil CRM historique';
$string['crm_user_history_userid'] = 'Identifiant Moodle';
$string['crm_user_history_subscriptions'] = 'Abonnements historiques';
$string['crm_user_history_digital_purchases'] = 'Achats digitaux';
$string['crm_user_history_courses'] = 'Cours historiques';
$string['crm_user_history_last_activity'] = 'Dernière activité CRM';
$string['crm_user_history_revenue'] = 'Chiffre d’affaires historique';
$string['crm_user_history_open_users'] = 'Retour aux utilisateurs';
$string['crm_user_history_open_inbox'] = 'Voir dans l’Inbox';
$string['crm_user_history_open_work'] = 'Voir les Work Items';
$string['crm_user_history_no_subscriptions'] = 'Aucun abonnement historique n’a été retrouvé.';
$string['crm_user_history_no_digital_purchases'] = 'Aucun achat digital historique n’a été retrouvé.';
$string['crm_user_history_no_notes'] = 'Aucune note CRM historique n’a été retrouvée.';
$string['crm_user_history_no_tags'] = 'Aucun tag CRM historique n’a été retrouvé.';
$string['crm_user_history_unknown_plan'] = 'Plan non disponible';
$string['crm_user_history_unknown_product'] = 'Produit non disponible';
$string['crm_user_history_plan'] = 'Plan';
$string['crm_user_history_amount'] = 'Montant';
$string['crm_notes'] = 'Notes CRM';
$string['crm_tags'] = 'Tags CRM';

$string['crm_inbox_invalid_form_action'] = 'L’action demandée pour ce formulaire Inbox est invalide ou absente.';

$string['crm_timeline_category_commercial'] = 'Commerce';
$string['crm_timeline_category_learning'] = 'Pédagogie';
$string['crm_timeline_category_inbox'] = 'Inbox';
$string['crm_timeline_category_notes'] = 'Notes et tags';
$string['crm_timeline_category_work'] = 'Work Items';
$string['crm_timeline_category_customer_success'] = 'Customer Success';
$string['crm_timeline_category_automation'] = 'Automatisations';
$string['crm_timeline_category_administration'] = 'Administration';

$string['crm_timeline_search'] = 'Rechercher dans la Timeline';
$string['crm_timeline_period'] = 'Période de la Timeline';
$string['crm_timeline_period_all'] = 'Toute la période';
$string['crm_timeline_period_7_days'] = '7 derniers jours';
$string['crm_timeline_period_30_days'] = '30 derniers jours';
$string['crm_timeline_period_90_days'] = '90 derniers jours';
$string['crm_timeline_period_year'] = '12 derniers mois';
$string['crm_timeline_important_only'] = 'Événements importants';
$string['crm_timeline_filter_categories'] = 'Filtrer la Timeline par catégorie';
$string['crm_timeline_results_count'] = '{$a} événement(s) affiché(s)';
$string['crm_timeline_no_filtered_results'] = 'Aucun événement ne correspond aux filtres sélectionnés.';
$string['crm_timeline_open_event'] = 'Ouvrir';
$string['crm_timeline_event'] = 'Événement CRM';
$string['crm_timeline_yesterday'] = 'Hier';
$string['crm_timeline_load_more'] = 'Afficher plus d’événements';
$string['crm_timeline_loading'] = 'Chargement…';
$string['crm_timeline_loading_error'] = 'Réessayer le chargement';
$string['crm_timeline_loaded_events'] = 'événements chargés';
$string['crm_timeline_important_events'] = 'événements importants';
$string['crm_timeline_latest_event'] = 'Dernier événement';
$string['crm_timeline_view_full'] = 'Voir la Timeline complète';

$string['user360_workspace_timeline_summary'] = 'Résumé de la Timeline';
$string['user360_workspace_timeline_summary_description'] = 'Affiche les derniers événements et le nombre d’éléments importants.';

$string['crm_navigation_toggle'] = 'Navigation';
$string['crm_navigation_open'] = 'Ouvrir la navigation CRM';
$string['crm_navigation_close'] = 'Fermer la navigation CRM';
$string['crm_command_center_short_label'] = 'Rechercher';

$string['crm_inbox_back_to_thread'] =
    'Retour à la conversation';

$string['crm_inbox_reply_help_subtitle'] =
    'Rédigez, enregistrez ou envoyez une réponse dans cette conversation.';
$string['crm_work_create_subtitle'] =
    'Créez une tâche, un suivi ou une action CRM et attribuez-la à la bonne personne ou équipe.';
$string['crm_work_teams_subtitle'] =
    'Créez les équipes CRM et gérez leurs membres, responsables et disponibilité.';
$string['crm_customer_success_plan_subtitle'] =
    'Consultez les objectifs, actions, échéances et signaux associés à ce plan Customer Success.';
$string['crm_work_suggestion_subtitle'] =
    'Vérifiez la proposition de l’Assistant avant de créer le Work Item.';
$string['crm_admin_tool_history_subtitle'] =
    'Consultez les dernières exécutions des outils administratifs et leur résultat.';

$string['crm_breadcrumb_navigation'] =
    'Fil d’Ariane CRM';
$string['crm_help_home_subtitle'] =
    'Retrouvez la documentation, les guides pratiques et les diagnostics du CRM CampusFR.';
$string['crm_skip_to_content'] =
    'Aller directement au contenu';

$string['crm_inbox_preview_loading'] =
    'Chargement de la conversation…';

$string['crm_inbox_preview_error'] =
    'Impossible de charger l’aperçu de cette conversation.';

$string['crm_inbox_preview_loaded'] =
    'Conversation « {$a} » chargée.';

$string['crm_inbox_preview_open_full'] =
    'Ouvrir la conversation complète';

$string['crm_inbox_preview_manage'] =
    'Répondre et gérer la conversation';

$string['crm_inbox_preview_reading_region'] =
    'Aperçu de la conversation';

$string['crm_inbox_preview_context_region'] =
    'Contexte du contact';

$string['crm_commerce_nav'] = 'Commerce';
$string['crm_commerce_title'] = 'Commerce';
$string['crm_commerce_description'] = 'Gérez les abonnements, les achats digitaux et les produits depuis un espace commercial unifié.';

$string['crm_commerce_no_access'] = 'Vous ne disposez actuellement d’aucun accès aux modules commerciaux.';

$string['crm_commerce_subscriptions_title'] = 'Abonnements';
$string['crm_commerce_subscriptions_description'] = 'Consultez et gérez les abonnements, les inscriptions payantes et leur historique.';

$string['crm_commerce_imports_title'] = 'Imports';
$string['crm_commerce_imports_description'] = 'Importez des abonnements et consultez les outils associés aux imports.';

$string['crm_commerce_configuration_title'] = 'Configuration commerciale';
$string['crm_commerce_configuration_description'] = 'Gérez les plans, les tarifs, les droits d’accès, les traductions et les évolutions de formule.';

$string['crm_commerce_digital_products_title'] = 'Produits digitaux';
$string['crm_commerce_digital_products_description'] = 'Créez et gérez les produits numériques proposés dans la boutique.';

$string['crm_commerce_digital_purchases_title'] = 'Achats digitaux';
$string['crm_commerce_digital_purchases_description'] = 'Consultez les achats numériques, les paiements et les accès délivrés aux clients.';

$string['crm_commerce_statistics_title'] = 'Statistiques commerciales';
$string['crm_commerce_statistics_description'] = 'Analysez les ventes digitales, les revenus et les principaux indicateurs commerciaux.';

$string['admin_card_commerce_title'] = 'Commerce';
$string['admin_card_commerce_description'] = 'Accédez aux abonnements, produits digitaux, achats, imports, statistiques et outils commerciaux.';

$string['crm_subscriptions_title'] = 'Abonnements';
$string['crm_subscriptions_description'] = 'Consultez et gérez les inscriptions payantes, les périodes d’accès et les formules attribuées aux utilisateurs.';
$string['crm_subscriptions_breadcrumb'] = 'Abonnements';
$string['crm_subscription_view_description'] = 'Consultez les informations commerciales, les dates d’accès, le paiement associé et les références du fournisseur.';
$string['crm_subscription_edit_description'] = 'Modifiez les dates d’accès et le statut de cet abonnement.';
$string['crm_subscription_add_description'] = 'Attribuez manuellement une formule à un utilisateur existant ou créez un nouveau compte avant l’inscription.';
$string['crm_subscriptions_export_title'] = 'Exporter les abonnements';
$string['crm_subscriptions_export_description'] = 'Téléchargez les abonnements et leurs principales informations commerciales au format Excel.';
$string['crm_subscriptions_export_help'] = 'Le classeur contient des feuilles distinctes pour les formules longues, le cours A1 et les abonnements d’essai.';
$string['crm_subscriptions_export_download'] = 'Télécharger le fichier Excel';
$string['crm_subscriptions_export_sheet_long'] = '1 an - 3 ans - à vie';
$string['crm_subscriptions_export_sheet_a1'] = 'Cours A1';
$string['crm_subscriptions_export_sheet_trial'] = 'Essai';
$string['crm_subscriptions_import_description'] = 'Importez plusieurs abonnements depuis un fichier CSV, puis vérifiez les données avant leur création.';
$string['crm_subscriptions_import_result_title'] = 'Résultat de l’import';
$string['crm_subscriptions_import_result_description'] = 'Consultez les abonnements importés et les lignes ignorées pendant le traitement.';
$string['crm_subscriptions_view_list'] = 'Voir les abonnements';
$string['crm_subscriptions_import_another'] = 'Importer un autre fichier';
$string['crm_subscription_configuration_title'] = 'Configuration des abonnements';
$string['crm_subscription_configuration_description'] = 'Gérez les plans commerciaux, leurs durées et les périmètres de cours auxquels ils donnent accès.';
$string['crm_plan_prices_description'] = 'Gérez les tarifs multi-devises et les identifiants de prix du fournisseur de paiement pour cette formule.';
$string['crm_plan_translations_title'] = 'Traductions des formules';
$string['crm_plan_translations_description'] = 'Gérez les noms et contenus traduits des formules commerciales dans les langues disponibles.';
$string['crm_plan_entitlements_description'] = 'Définissez les cours, rôles, groupes et niveaux d’accès automatiquement attribués par cette formule.';
$string['crm_plan_upgrades_description'] = 'Configurez les évolutions autorisées entre les formules et leur méthode de calcul tarifaire.';
$string['crm_scope_translations_title'] = 'Traductions des périmètres d’accès';
$string['crm_scope_translations_description'] = 'Gérez les libellés traduits des périmètres de cours utilisés par les formules.';
$string['crm_digital_products_description'] = 'Gérez les produits numériques, leurs fichiers, leurs traductions, leurs prix et leur disponibilité dans la boutique.';
$string['crm_digital_product_add_description'] = 'Créez un produit numérique, ajoutez ses fichiers et préparez ses contenus commerciaux en français, anglais et russe.';
$string['crm_digital_product_edit_description'] = 'Modifiez les fichiers, les prix, la disponibilité et les contenus traduits de ce produit numérique.';
$string['crm_digital_purchase_view_description'] = 'Consultez les informations commerciales, le paiement, l’accès au fichier et les données techniques de cet achat numérique.';
$string['crm_digital_sales_stats_description'] = 'Analysez le volume et l’évolution cumulée des ventes de produits numériques sur la période choisie.';

$string['crm_commerce_section_navigation'] = 'Navigation secondaire du commerce';
$string['crm_commerce_nav_overview'] = 'Vue d’ensemble';
$string['crm_commerce_nav_subscriptions'] = 'Abonnements';
$string['crm_commerce_nav_digital_purchases'] = 'Achats numériques';
$string['crm_commerce_nav_digital_products'] = 'Produits numériques';
$string['crm_commerce_nav_statistics'] = 'Statistiques';
$string['crm_commerce_nav_configuration'] = 'Configuration';

$string['settings:commerce_migration_heading'] = 'Commerce — migration et sécurité';
$string['settings:commerce_migration_heading_desc'] = 'Réglages avancés contrôlant les flux de paiement Commerce. Toute modification peut affecter les revenus : validez chaque scénario et conservez un plan de rollback.';
$string['settings:commerce_fulfillment_enabled'] = 'Activer le fulfillment Commerce';
$string['settings:commerce_fulfillment_enabled_desc'] =
    'Utilise le fulfillment Commerce certifié après confirmation du paiement. Désactivez ce réglage pour revenir immédiatement au traitement post-paiement Legacy.';
$string['settings:commerce_checkout_enabled'] =
    'Activer le checkout Commerce';

$string['settings:commerce_checkout_enabled_desc'] =
    'Utilise l’architecture Commerce certifiée pour initialiser les paiements Stripe EUR et Alfa RUB. Désactivez ce réglage pour revenir immédiatement au checkout Legacy.';

$string['crm_help_category_commerce'] =
    'Commerce et paiements';

$string['crm_help_category_commerce_desc'] =
    'Architecture Commerce, checkout, providers, fulfillment, exploitation et diagnostics.';

$string['crm_help_article_commerce_overview_title'] =
    'Comprendre l’architecture Commerce';

$string['crm_help_article_commerce_overview_summary'] =
    'Vue d’ensemble des achats Commerce, des paiements, du checkout et du fulfillment.';

$string['crm_help_article_commerce_operations_title'] =
    'Exploiter Commerce en production';

$string['crm_help_article_commerce_operations_summary'] =
    'Configuration, kill switches, providers et procédure de rollback sécurisé.';

$string['crm_help_article_commerce_diagnostics_title'] =
    'Auditer et diagnostiquer Commerce';

$string['crm_help_article_commerce_diagnostics_summary'] =
    'Commandes de validation, certification, intégrité, fulfillment et résolution des incidents.';

$string['crm_help_article_commerce_extension_title'] =
    'Étendre l’architecture Commerce';

$string['crm_help_article_commerce_extension_summary'] =
    'Ajouter un provider, un type d’achat ou un handler de fulfillment sans contourner les contrats Commerce.';

$string['settings:commerce_dual_write_enabled'] = 'Activer la double écriture Commerce native';
$string['settings:commerce_dual_write_enabled_desc'] = 'Après une modification Commerce Legacy, synchronise et vérifie son snapshot Commerce natif. Désactivé par défaut.';
$string['settings:commerce_dual_write_strict'] = 'Double écriture Commerce native stricte';
$string['settings:commerce_dual_write_strict_desc'] = 'Interrompt l’opération appelante si la synchronisation native échoue. À laisser désactivé pendant la période initiale d’observation.';
$string['settings:commerce_native_read_shadow_enabled'] = 'Activer les lectures Shadow Commerce natives';
$string['settings:commerce_native_read_shadow_enabled_desc'] = 'Lit également le snapshot natif et le compare au Legacy, tout en retournant toujours le Legacy. Désactivé par défaut.';
$string['settings:commerce_native_read_shadow_strict'] = 'Lectures Shadow Commerce natives strictes';
$string['settings:commerce_native_read_shadow_strict_desc'] = 'Lève une exception lorsqu’une divergence est détectée. À réserver aux tests et audits DEV.';

$string['settings:commerce_runtime_read_mode'] = 'Mode de lecture du runtime Commerce';
$string['settings:commerce_runtime_read_mode_desc'] = 'Sélectionne la source de persistance utilisée par le lecteur runtime I7. Les écrans consommateurs seront migrés en I8 et I9.';
$string['settings:commerce_runtime_read_mode_legacy'] = 'Legacy uniquement';
$string['settings:commerce_runtime_read_mode_shadow'] = 'Shadow : retourne Legacy et compare Native';
$string['settings:commerce_runtime_read_mode_native'] = 'Native uniquement';
$string['settings:commerce_runtime_read_mode_auto'] = 'Auto : Native avec fallback Legacy automatique';
$string['settings:commerce_runtime_read_strict'] = 'Lectures runtime Commerce strictes';
$string['settings:commerce_runtime_read_strict_desc'] = 'Lève une exception en cas de fallback, divergence ou donnée absente. À réserver à la certification DEV.';
$string['settings:commerce_native_crm_reads_enabled'] = 'Lectures Commerce Native pour le CRM';
$string['settings:commerce_native_crm_reads_enabled_desc'] = 'Utilise la couche de lecture Native I10C pour les consommateurs CRM.';
$string['settings:commerce_native_admin_reads_enabled'] = 'Lectures Commerce Native pour l’administration';
$string['settings:commerce_native_admin_reads_enabled_desc'] = 'Utilise la couche de lecture Native I10C pour les écrans d’administration.';
$string['settings:commerce_native_user_reads_enabled'] = 'Lectures Commerce Native pour les pages utilisateur';
$string['settings:commerce_native_user_reads_enabled_desc'] = 'Utilise la couche de lecture Native I10C pour les pages exposées aux utilisateurs.';
$string['settings:commerce_native_email_reads_enabled'] = 'Lectures Commerce Native pour les emails';
$string['settings:commerce_native_email_reads_enabled_desc'] = 'Utilise la couche de lecture Native I10C pour construire les contextes email.';
$string['settings:commerce_native_task_reads_enabled'] = 'Lectures Commerce Native pour les tâches';
$string['settings:commerce_native_task_reads_enabled_desc'] = 'Utilise la couche de lecture Native I10C dans les tâches planifiées Commerce.';
$string['settings:commerce_native_shadow_compare_enabled'] = 'Comparer les lectures Native et Legacy';
$string['settings:commerce_native_shadow_compare_enabled_desc'] = 'Exécute une comparaison Shadow non bloquante entre les sources Native et Legacy.';
$string['settings:commerce_native_legacy_fallback_enabled'] = 'Autoriser le fallback Legacy';
$string['settings:commerce_native_legacy_fallback_enabled_desc'] = 'Utilise les données Legacy lorsqu’une lecture Native est indisponible.';

// I10D Native-aware commands.
$string['settings:commerce_native_dual_write_enabled'] = 'Activer le dual-write Native I10D';
$string['settings:commerce_native_dual_write_enabled_desc'] = 'Autorise les services de commande Commerce à synchroniser les écritures Legacy vers la persistance Native. Désactivé par défaut.';
$string['settings:commerce_native_task_dual_write_enabled'] = 'Activer le dual-write I10D des tâches';
$string['settings:commerce_native_task_dual_write_enabled_desc'] = 'Autorise les tâches planifiées Commerce à synchroniser leurs mutations Legacy vers la persistance Native. Désactivé par défaut.';
$string['settings:commerce_native_shadow_write_compare_enabled'] = 'Activer la comparaison Shadow des écritures I10D';
$string['settings:commerce_native_shadow_write_compare_enabled_desc'] = 'Compare les états Legacy et Native après une commande sans modifier le résultat visible.';

$string['commerce_native_reconciliation_enabled'] = 'Réconciliation Native Commerce';
$string['commerce_native_reconciliation_enabled_desc'] = 'Active la réconciliation Native Commerce.';
$string['commerce_native_repair_enabled'] = 'Réparation Native Commerce';
$string['commerce_native_repair_enabled_desc'] = 'Autorise les réparations explicites pendant une réconciliation.';

// Phase 7.94E4 - Unified Commerce Product Editor.
$string['crm_commerce_nav_products'] = 'Produits';
$string['commerce_products_title'] = 'Produits Commerce';
$string['commerce_products_description'] = 'Gérer le catalogue unifié Native Commerce.';
$string['commerce_product_add'] = 'Ajouter un produit';
$string['commerce_product_sku'] = 'SKU';
$string['commerce_product_name'] = 'Nom';
$string['commerce_product_type'] = 'Type';
$string['commerce_product_status'] = 'Statut';
$string['commerce_product_description'] = 'Description';
$string['commerce_product_definition'] = 'Définition';
$string['commerce_product_definition_counts'] = 'Prix : {$a->prices} ; traductions : {$a->translations} ; composants : {$a->components} ; droits : {$a->entitlements}';
$string['commerce_bundle_edit_components'] = 'Modifier les composants du bundle';

// Phase 7.94E5 - Bundle visual component editor.
$string['commerce_bundle_components_title'] = 'Composants — {$a}';
$string['commerce_bundle_components_help'] = 'Sélectionnez les produits, les quantités et l’ordre d’affichage. Les lignes vides sont ignorées. L’enregistrement valide toute l’expansion récursive.';
$string['commerce_bundle_component_number'] = 'Composant {$a}';
$string['commerce_bundle_component_product'] = 'Produit';
$string['commerce_bundle_component_quantity'] = 'Quantité';
$string['commerce_bundle_component_order'] = 'Ordre';
$string['commerce_bundle_add_rows'] = 'Ajouter des lignes';
$string['commerce_bundle_preview_title'] = 'Aperçu du bundle développé';

// Phase 7.94E6 - Bundle preview and guided CRM workflow.
$string['commerce_product_workflow'] = 'Étapes de configuration du produit';
$string['commerce_product_step_information'] = 'Informations';
$string['commerce_product_step_components'] = 'Composition';
$string['commerce_product_step_preview'] = 'Aperçu';
$string['commerce_product_step_pricing'] = 'Tarification';
$string['commerce_bundle_open_preview'] = 'Voir l’aperçu complet';
$string['commerce_bundle_preview_eyebrow'] = 'Contrôle avant commercialisation';
$string['commerce_bundle_preview_intro'] = 'Vérifiez les produits réellement inclus, leurs quantités, leurs prix disponibles et les droits qui seront accordés.';
$string['commerce_bundle_preview_unavailable'] = 'L’aperçu ne peut pas encore être généré';
$string['commerce_bundle_fix_components'] = 'Corriger la composition';
$string['commerce_bundle_preview_products'] = 'Produits terminaux';
$string['commerce_bundle_preview_quantity'] = 'Quantité totale';
$string['commerce_bundle_preview_entitlements'] = 'Droits annoncés';
$string['commerce_bundle_preview_depth'] = 'Profondeur maximale';
$string['commerce_bundle_preview_empty'] = 'Ce bundle ne contient encore aucun produit terminal.';
$string['commerce_bundle_preview_prices'] = 'Prix actifs du produit';
$string['commerce_bundle_preview_rights'] = 'Droits accordés';
$string['commerce_bundle_preview_paths'] = 'Chemins de composition';
$string['commerce_no_active_price'] = 'Aucun prix actif';
$string['commerce_no_entitlement'] = 'Aucun droit défini';
$string['commerce_entitlement_lifetime'] = 'À vie';
$string['commerce_back_to_products'] = 'Retour aux produits';

// Phase 7.94E7 - Bundle pricing.
$string['commerce_bundle_pricing_title'] = 'Tarification — {$a}';
$string['commerce_bundle_pricing_eyebrow'] = 'Stratégie commerciale';
$string['commerce_bundle_pricing_intro'] = 'Définissez comment le prix du bundle est obtenu et vérifiez immédiatement le résultat dans chaque devise.';
$string['commerce_bundle_pricing_method'] = 'Méthode de calcul';
$string['commerce_bundle_pricing_method_help'] = 'Le prix fixe utilise le prix propre du bundle. La somme reprend les prix des produits inclus. La remise applique un pourcentage à cette somme.';
$string['commerce_bundle_pricing_fixed'] = 'Prix fixe du bundle';
$string['commerce_bundle_pricing_sum'] = 'Somme des composants';
$string['commerce_bundle_pricing_discount'] = 'Somme des composants avec remise';
$string['commerce_bundle_discount_percent'] = 'Remise (%)';
$string['commerce_bundle_fixed_prices'] = 'Prix fixes du bundle';
$string['commerce_bundle_fixed_prices_help'] = 'Utilisés uniquement avec la méthode « prix fixe ». Laissez vide pour ne pas modifier un prix existant.';
$string['commerce_bundle_price_simulation'] = 'Simulation actuelle';
$string['commerce_bundle_final_price'] = 'Prix final du bundle';
$string['commerce_bundle_component_total'] = 'Valeur séparée';
$string['commerce_bundle_savings'] = 'Économie client';

// 7.94E8 - Gestionnaire unifié des produits Commerce.
$string['commerce_product_type_course_access'] = 'Accès à un cours';
$string['commerce_product_type_digital_download'] = 'Produit numérique';
$string['commerce_product_type_bundle'] = 'Pack / Bundle';
$string['commerce_product_type_service'] = 'Service';
$string['commerce_product_status_draft'] = 'Brouillon';
$string['commerce_product_status_active'] = 'Actif';
$string['commerce_product_status_inactive'] = 'Inactif';
$string['commerce_product_status_archived'] = 'Archivé';
$string['commerce_product_edit_steps'] = 'Étapes de configuration du produit';
$string['commerce_product_type_help'] = 'Le type peut être modifié tant que le produit reste en brouillon. Le SKU est un identifiant technique stable et ne peut plus être changé après création.';
$string['commerce_product_description_help'] = 'Cette description par défaut sert de contenu de repli. Les textes visibles par le client doivent être renseignés dans les traductions ci-dessous.';
$string['commerce_product_translations_title'] = 'Contenus multilingues';
$string['commerce_product_translations_help'] = 'Renseignez le nom et les descriptions commerciales affichés au client dans chaque langue.';
$string['commerce_product_short_description'] = 'Description courte';
$string['commerce_product_summary'] = 'Vue d’ensemble du produit';
$string['commerce_prices'] = 'Prix';
$string['commerce_translations'] = 'traductions';
$string['commerce_components'] = 'composants';
$string['commerce_entitlements'] = 'droits';
$string['commerce_products_empty'] = 'Aucun produit ne correspond aux filtres sélectionnés.';
$string['commerce_product_archived'] = 'Le produit a été archivé. Il reste disponible dans l’historique et n’est plus proposé à la vente.';
$string['commerce_products_card_description'] = 'Gérer le catalogue unifié, les packs, les traductions, les prix et les droits associés.';
$string['commerce_entitlement_course_access'] = 'Accès au cours {$a->courseid} — {$a->level}';
$string['commerce_entitlement_course_generic'] = 'Accès à un cours : {$a}';
$string['commerce_entitlement_digital_product'] = 'Téléchargement du produit numérique n°{$a}';
$string['commerce_entitlement_digital_generic'] = 'Produit numérique : {$a}';
$string['commerce_entitlement_generic'] = '{$a->type} : {$a->resource}';
$string['commerce_entitlement_access_full'] = 'accès complet';
$string['commerce_entitlement_access_grammar'] = 'accès grammaire';
$string['commerce_entitlement_access_trial'] = 'accès d’essai';
$string['commerce_bundle_preview_pricing'] = 'Tarification du pack';
$string['commerce_bundle_pricing_incomplete'] = 'La tarification n’est pas encore complète pour cette devise.';

// 7.94E9 - Certification finale.
$string['commerce_bundle_phase_certification'] = 'Certification Commerce Products et Bundles';

$string['commerce_product_type_unknown'] = 'Autre produit';
$string['commerce_product_status_unknown'] = 'Statut inconnu';
$string['commerce_entitlement_course_named'] = 'Accès au cours « {$a->course} » — {$a->level}';
$string['commerce_entitlement_digital_named'] = 'Accès au produit numérique « {$a} »';
$string['commerce_entitlement_generic_readable'] = '{$a->type} : {$a->resource}';
$string['commerce_entitlement_type_course'] = 'Accès à un cours';
$string['commerce_entitlement_type_digital_product'] = 'Accès numérique';
$string['commerce_entitlement_type_other'] = 'Autre droit';
$string['commerce_course_fallback'] = 'Cours n°{$a}';
$string['commerce_digital_product_fallback'] = 'Produit numérique n°{$a}';
$string['commerce_entitlement_access_generic'] = 'accès standard';
$string['commerce_product_archive'] = 'Archiver';
$string['commerce_bundle_add_currency'] = 'Ajouter une autre devise';
$string['commerce_bundle_add_currency_help'] = 'Saisissez n’importe quel code devise ISO 4217, par exemple USD, GBP, CAD ou AUD.';

$string['commerce_price'] = 'Prix';

$string['commerce_bundle_component_comparison_unavailable'] = 'Le tarif du pack est actif. La valeur séparée et l’économie client seront disponibles lorsque tous les composants auront un tarif actif dans cette devise.';

$string['commerce_fulfillment_shadow_enabled'] = 'Activer le Shadow du fulfillment natif';
$string['commerce_fulfillment_shadow_enabled_desc'] = 'Exécute le fulfillment natif en dry-run après le fulfillment Legacy et persiste les comparaisons, sans modifier les droits.';
$string['commerce_runtime_mode'] = 'Mode d’exécution du fulfillment Commerce';
$string['commerce_runtime_mode_desc'] = 'Sélectionne le moteur de fulfillment autoritaire. Legacy est le mode sûr par défaut ; Shadow conserve Legacy comme autorité ; Native rend le moteur Native autoritaire.';
$string['commerce_runtime_mode_legacy'] = 'Legacy';
$string['commerce_runtime_mode_shadow'] = 'Shadow';
$string['commerce_runtime_mode_native'] = 'Native';
$string['commerce_runtime_native_fallback_enabled'] = 'Activer le fallback automatique vers Legacy';
$string['commerce_runtime_native_fallback_enabled_desc'] = 'Si le fulfillment Native lève une exception, exécute immédiatement le chemin Legacy. À conserver activé pendant la bascule DEV.';

// Commerce 7.95B1 — vocabulaire UX commun.
$string['commerce_vocabulary_product_type_client_course_access'] = 'Cours';
$string['commerce_vocabulary_product_type_client_digital_download'] = 'Ressource numérique';
$string['commerce_vocabulary_product_type_client_bundle'] = 'Pack';
$string['commerce_vocabulary_product_type_client_service'] = 'Service';
$string['commerce_vocabulary_product_type_crm_course_access'] = 'Accès à un cours';
$string['commerce_vocabulary_product_type_crm_digital_download'] = 'Produit numérique';
$string['commerce_vocabulary_product_type_crm_bundle'] = 'Bundle';
$string['commerce_vocabulary_product_type_crm_service'] = 'Service';
$string['commerce_vocabulary_product_type_unknown'] = 'Autre produit';
$string['commerce_vocabulary_product_status_client_active'] = 'Disponible';
$string['commerce_vocabulary_product_status_client_draft'] = 'Bientôt disponible';
$string['commerce_vocabulary_product_status_client_inactive'] = 'Indisponible';
$string['commerce_vocabulary_product_status_client_archived'] = 'Indisponible';
$string['commerce_vocabulary_product_status_crm_active'] = 'Actif';
$string['commerce_vocabulary_product_status_crm_draft'] = 'Brouillon';
$string['commerce_vocabulary_product_status_crm_inactive'] = 'Inactif';
$string['commerce_vocabulary_product_status_crm_archived'] = 'Archivé';
$string['commerce_vocabulary_product_status_unknown'] = 'Statut non renseigné';
$string['commerce_vocabulary_purchase_status_client_draft'] = 'En préparation';
$string['commerce_vocabulary_purchase_status_client_created'] = 'Créé';
$string['commerce_vocabulary_purchase_status_client_prepared'] = 'Prêt pour le paiement';
$string['commerce_vocabulary_purchase_status_client_payment_pending'] = 'Paiement en attente';
$string['commerce_vocabulary_purchase_status_client_authorized'] = 'Paiement autorisé';
$string['commerce_vocabulary_purchase_status_client_captured'] = 'Payé';
$string['commerce_vocabulary_purchase_status_client_paid'] = 'Payé';
$string['commerce_vocabulary_purchase_status_client_fulfillment_pending'] = 'Accès en préparation';
$string['commerce_vocabulary_purchase_status_client_fulfilled'] = 'Disponible';
$string['commerce_vocabulary_purchase_status_client_completed'] = 'Disponible';
$string['commerce_vocabulary_purchase_status_client_active'] = 'Actif';
$string['commerce_vocabulary_purchase_status_client_expired'] = 'Expiré';
$string['commerce_vocabulary_purchase_status_client_replaced'] = 'Remplacé';
$string['commerce_vocabulary_purchase_status_client_cancelled'] = 'Annulé';
$string['commerce_vocabulary_purchase_status_client_failed'] = 'Échec';
$string['commerce_vocabulary_purchase_status_client_refunded'] = 'Remboursé';
$string['commerce_vocabulary_purchase_status_client_unknown'] = 'État en cours de vérification';
$string['commerce_vocabulary_purchase_status_crm_draft'] = 'Brouillon';
$string['commerce_vocabulary_purchase_status_crm_created'] = 'Créé';
$string['commerce_vocabulary_purchase_status_crm_prepared'] = 'Préparé';
$string['commerce_vocabulary_purchase_status_crm_payment_pending'] = 'Paiement en attente';
$string['commerce_vocabulary_purchase_status_crm_authorized'] = 'Autorisé';
$string['commerce_vocabulary_purchase_status_crm_captured'] = 'Capturé';
$string['commerce_vocabulary_purchase_status_crm_paid'] = 'Payé';
$string['commerce_vocabulary_purchase_status_crm_fulfillment_pending'] = 'Exécution en attente';
$string['commerce_vocabulary_purchase_status_crm_fulfilled'] = 'Exécuté';
$string['commerce_vocabulary_purchase_status_crm_completed'] = 'Terminé';
$string['commerce_vocabulary_purchase_status_crm_active'] = 'Actif';
$string['commerce_vocabulary_purchase_status_crm_expired'] = 'Expiré';
$string['commerce_vocabulary_purchase_status_crm_replaced'] = 'Remplacé';
$string['commerce_vocabulary_purchase_status_crm_cancelled'] = 'Annulé';
$string['commerce_vocabulary_purchase_status_crm_failed'] = 'Échec';
$string['commerce_vocabulary_purchase_status_crm_refunded'] = 'Remboursé';
$string['commerce_vocabulary_purchase_status_crm_unknown'] = 'Statut non renseigné';
$string['commerce_vocabulary_purchase_status_unknown'] = 'État de l’achat non renseigné';
$string['commerce_vocabulary_payment_status_client_created'] = 'Paiement créé';
$string['commerce_vocabulary_payment_status_client_requires_action'] = 'Action requise';
$string['commerce_vocabulary_payment_status_client_pending'] = 'Paiement en attente';
$string['commerce_vocabulary_payment_status_client_authorized'] = 'Paiement autorisé';
$string['commerce_vocabulary_payment_status_client_captured'] = 'Payé';
$string['commerce_vocabulary_payment_status_client_paid'] = 'Payé';
$string['commerce_vocabulary_payment_status_client_succeeded'] = 'Payé';
$string['commerce_vocabulary_payment_status_client_failed'] = 'Paiement échoué';
$string['commerce_vocabulary_payment_status_client_cancelled'] = 'Paiement annulé';
$string['commerce_vocabulary_payment_status_client_expired'] = 'Paiement expiré';
$string['commerce_vocabulary_payment_status_client_refunded'] = 'Remboursé';
$string['commerce_vocabulary_payment_status_client_partially_refunded'] = 'Partiellement remboursé';
$string['commerce_vocabulary_payment_status_client_unknown'] = 'Paiement en cours de vérification';
$string['commerce_vocabulary_payment_status_crm_created'] = 'Créé';
$string['commerce_vocabulary_payment_status_crm_requires_action'] = 'Action requise';
$string['commerce_vocabulary_payment_status_crm_pending'] = 'En attente';
$string['commerce_vocabulary_payment_status_crm_authorized'] = 'Autorisé';
$string['commerce_vocabulary_payment_status_crm_captured'] = 'Capturé';
$string['commerce_vocabulary_payment_status_crm_paid'] = 'Payé';
$string['commerce_vocabulary_payment_status_crm_succeeded'] = 'Réussi';
$string['commerce_vocabulary_payment_status_crm_failed'] = 'Échec';
$string['commerce_vocabulary_payment_status_crm_cancelled'] = 'Annulé';
$string['commerce_vocabulary_payment_status_crm_expired'] = 'Expiré';
$string['commerce_vocabulary_payment_status_crm_refunded'] = 'Remboursé';
$string['commerce_vocabulary_payment_status_crm_partially_refunded'] = 'Partiellement remboursé';
$string['commerce_vocabulary_payment_status_crm_unknown'] = 'Statut non renseigné';
$string['commerce_vocabulary_payment_status_unknown'] = 'État du paiement non renseigné';
$string['commerce_vocabulary_fulfillment_status_client_pending'] = 'Accès en préparation';
$string['commerce_vocabulary_fulfillment_status_client_processing'] = 'Accès en cours de préparation';
$string['commerce_vocabulary_fulfillment_status_client_fulfilled'] = 'Disponible';
$string['commerce_vocabulary_fulfillment_status_client_completed'] = 'Disponible';
$string['commerce_vocabulary_fulfillment_status_client_failed'] = 'Accès non disponible';
$string['commerce_vocabulary_fulfillment_status_client_cancelled'] = 'Accès annulé';
$string['commerce_vocabulary_fulfillment_status_client_unknown'] = 'Accès en cours de vérification';
$string['commerce_vocabulary_fulfillment_status_crm_pending'] = 'En attente';
$string['commerce_vocabulary_fulfillment_status_crm_processing'] = 'En cours';
$string['commerce_vocabulary_fulfillment_status_crm_fulfilled'] = 'Exécuté';
$string['commerce_vocabulary_fulfillment_status_crm_completed'] = 'Terminé';
$string['commerce_vocabulary_fulfillment_status_crm_failed'] = 'Échec';
$string['commerce_vocabulary_fulfillment_status_crm_cancelled'] = 'Annulé';
$string['commerce_vocabulary_fulfillment_status_crm_unknown'] = 'Statut non renseigné';
$string['commerce_vocabulary_fulfillment_status_unknown'] = 'État de mise à disposition non renseigné';
$string['commerce_vocabulary_access_type_client_course'] = 'Accès au cours';
$string['commerce_vocabulary_access_type_client_digital_product'] = 'Accès à la ressource';
$string['commerce_vocabulary_access_type_client_subscription'] = 'Abonnement';
$string['commerce_vocabulary_access_type_client_bundle'] = 'Accès au pack';
$string['commerce_vocabulary_access_type_crm_course'] = 'Droit d’accès au cours';
$string['commerce_vocabulary_access_type_crm_digital_product'] = 'Droit d’accès numérique';
$string['commerce_vocabulary_access_type_crm_subscription'] = 'Droit d’abonnement';
$string['commerce_vocabulary_access_type_crm_bundle'] = 'Droit d’accès au bundle';
$string['commerce_vocabulary_access_type_unknown'] = 'Autre droit d’accès';

// Commerce 7.95B2-B4 UX foundation.
$string['commerce_products_empty_title'] = 'Aucun produit pour le moment';
$string['commerce_products_table_label'] = 'Liste des produits Commerce';
$string['commerce_product_eyebrow'] = 'Produit Commerce';

// Commerce 7.95C4-C6 — Tableau de bord statistique Native.
$string['commerce_statistics_title'] = 'Statistiques Commerce';
$string['commerce_statistics_description'] = 'Suivez les ventes, les paiements et les opérations Commerce à partir des données Native.';
$string['commerce_statistics_period'] = 'Période';
$string['commerce_statistics_currency'] = 'Devise';
$string['commerce_statistics_provider'] = 'Fournisseur de paiement';
$string['commerce_statistics_period_today'] = 'Aujourd’hui';
$string['commerce_statistics_period_7_days'] = '7 derniers jours';
$string['commerce_statistics_period_30_days'] = '30 derniers jours';
$string['commerce_statistics_period_90_days'] = '90 derniers jours';
$string['commerce_statistics_period_year'] = '12 derniers mois';
$string['commerce_statistics_all_currencies'] = 'Toutes les devises';
$string['commerce_statistics_all_providers'] = 'Tous les fournisseurs';
$string['commerce_statistics_period_summary'] = 'Période analysée : du {$a->from} au {$a->to}. Comparaison avec la période précédente de même durée.';
$string['commerce_statistics_empty_title'] = 'Aucune donnée Commerce';
$string['commerce_statistics_empty_description'] = 'Aucune activité Native n’a été trouvée pour les filtres et la période sélectionnés.';
$string['commerce_statistics_payment_health'] = 'Paiements et délivrance';
$string['commerce_statistics_metric_net_paid_minor'] = 'Chiffre d’affaires net';
$string['commerce_statistics_metric_orders'] = 'Commandes';
$string['commerce_statistics_metric_average_order_minor'] = 'Panier moyen';
$string['commerce_statistics_metric_customers'] = 'Clients payants';
$string['commerce_statistics_metric_successful_payments'] = 'Paiements réussis';
$string['commerce_statistics_metric_failed_payments'] = 'Paiements échoués';
$string['commerce_statistics_metric_refunded_minor'] = 'Montant remboursé';
$string['commerce_statistics_metric_pending_fulfillments'] = 'Délivrances à traiter';
$string['commerce_statistics_open_details'] = 'Ouvrir les éléments concernés';
$string['commerce_statistics_metric_link'] = 'Voir le détail de {$a->metric} en {$a->currency}';
$string['commerce_statistics_no_comparison'] = 'Comparaison indisponible';
$string['commerce_statistics_comparison_unavailable'] = 'Évolution non calculable';
$string['commerce_statistics_vs_previous'] = '{$a} par rapport à la période précédente';
$string['commerce_statistics_operational_shortcuts'] = 'Raccourcis opérationnels';
$string['commerce_statistics_open_subscriptions'] = 'Gérer les abonnements';
$string['commerce_statistics_open_digital_purchases'] = 'Gérer les achats numériques';
$string['commerce_statistics_open_products'] = 'Gérer les produits';

$string['commerce_statistics_charts_title'] = 'Tendances et répartitions';
$string['commerce_statistics_chart_revenue'] = 'Évolution du chiffre d’affaires';
$string['commerce_statistics_chart_orders'] = 'Évolution des commandes';
$string['commerce_statistics_chart_top_products'] = 'Produits les plus vendus';
$string['commerce_statistics_chart_payment_health'] = 'Santé des paiements';
$string['commerce_statistics_chart_product_revenue'] = 'Évolution des ventes du produit';
$string['commerce_statistics_payment_successful'] = 'Réussis';
$string['commerce_statistics_payment_failed'] = 'Échoués';
$string['commerce_statistics_payment_refunded'] = 'Remboursés';
$string['commerce_statistics_accessible_table'] = 'Afficher les données sous forme de tableau';
$string['commerce_product_statistics_title'] = 'Performance commerciale';
$string['commerce_product_statistics_period'] = 'Données des 90 derniers jours, séparées par devise.';
$string['commerce_statistics_table_period'] = 'Période';
$string['commerce_statistics_table_value'] = 'Valeur';


// 7.95D4-D6 — Unified Commerce sales.
$string['crm_commerce_nav_purchases'] = 'Ventes';
$string['commerce_purchases_title'] = 'Ventes';
$string['commerce_purchases_description'] = 'Consultez toutes les ventes Commerce Native depuis un espace opérationnel unique.';
$string['commerce_purchases_search'] = 'Rechercher';
$string['commerce_purchases_results'] = 'Ventes correspondantes';
$string['commerce_purchases_empty_title'] = 'Aucune vente trouvée';
$string['commerce_purchases_empty'] = 'Aucune vente Commerce Native ne correspond aux filtres sélectionnés.';
$string['commerce_purchases_table_label'] = 'Ventes Commerce unifiées';
$string['commerce_purchase_reference'] = 'Référence';
$string['commerce_purchase_customer'] = 'Client';
$string['commerce_purchase_products'] = 'Produits';
$string['commerce_purchase_amount'] = 'Montant';
$string['commerce_purchase_status'] = 'Statut';
$string['commerce_purchase_type'] = 'Type de vente';
$string['commerce_purchase_commercial_status'] = 'Statut commercial';
$string['commerce_purchase_payment_status'] = 'Paiement';
$string['commerce_purchase_fulfillment_status'] = 'Délivrance';
$string['commerce_purchase_provider'] = 'Fournisseur';
$string['commerce_purchase_view_title'] = 'Vente {$a}';
$string['commerce_purchase_view_description'] = 'Vue Native unifiée de la vente, de son paiement et de sa délivrance.';
$string['commerce_purchase_items_count'] = 'Articles';
$string['commerce_purchase_summary_section'] = 'Résumé';
$string['commerce_purchase_customer_section'] = 'Client';
$string['commerce_purchase_products_section'] = 'Produits';
$string['commerce_purchase_payments_section'] = 'Paiements';
$string['commerce_purchase_fulfillments_section'] = 'Délivrances';
$string['commerce_purchase_diagnostics_section'] = 'Diagnostic technique';
$string['commerce_purchase_product'] = 'Produit';
$string['commerce_purchase_quantity'] = 'Quantité';
$string['commerce_purchase_provider_reference'] = 'Référence fournisseur';
$string['commerce_purchase_fulfillment'] = 'Délivrance';
$string['commerce_purchase_source'] = 'Source';
$string['commerce_purchase_legacy_family'] = 'Famille Legacy';
$string['commerce_purchase_legacy_id'] = 'Identifiant Legacy';
$string['commerce_purchase_open_customer'] = 'Ouvrir le client';
$string['commerce_purchase_open_product'] = 'Ouvrir le produit';
$string['commerce_purchase_no_payments'] = 'Aucune tentative de paiement enregistrée.';
$string['commerce_purchase_no_fulfillments'] = 'Aucune opération de délivrance enregistrée.';
$string['commerce_purchase_not_found'] = 'La vente Commerce demandée est introuvable.';
$string['commerce_purchase_commercial_status_pending'] = 'En attente';
$string['commerce_purchase_commercial_status_paid'] = 'Payée';
$string['commerce_purchase_commercial_status_to_fulfill'] = 'Payée, à délivrer';
$string['commerce_purchase_commercial_status_partially_fulfilled'] = 'Partiellement délivrée';
$string['commerce_purchase_commercial_status_fulfilled'] = 'Délivrée';
$string['commerce_purchase_commercial_status_payment_failed'] = 'Échec du paiement';
$string['commerce_purchase_commercial_status_refunded'] = 'Remboursée';
$string['commerce_purchase_commercial_status_cancelled'] = 'Annulée';
$string['commerce_purchase_commercial_status_replaced'] = 'Remplacée';
$string['commerce_purchase_commercial_status_unknown'] = 'Inconnu';
$string['commerce_purchase_type_subscription'] = 'Abonnement';
$string['commerce_purchase_type_digital'] = 'Produit numérique';
$string['commerce_purchase_type_bundle'] = 'Bundle';

// 7.95D7-D10 unified purchase actions and compatibility.
$string['commerce_purchase_actions_section'] = 'Actions';
$string['commerce_purchase_retry_fulfillment'] = 'Relancer la délivrance';
$string['commerce_purchase_retry_confirm'] = 'Relancer la délivrance Native de cette vente ? L’opération est idempotente.';
$string['commerce_purchase_retry_success'] = 'La délivrance a été exécutée avec succès.';
$string['commerce_purchase_retry_failed'] = 'La délivrance n’a pas pu être terminée. Consultez le détail des délivrances.';
$string['commerce_purchase_internal_note'] = 'Note interne';
$string['commerce_purchase_add_note'] = 'Ajouter la note';
$string['commerce_purchase_note_added'] = 'La note interne a été ajoutée.';
$string['commerce_purchase_destructive_actions_deferred'] = 'L’annulation, le remplacement et le remboursement restent indisponibles tant que leurs commandes Native compatibles avec les fournisseurs ne sont pas certifiées.';
$string['commerce_purchase_action_not_allowed'] = 'Cette action n’est pas autorisée pour l’état actuel de la vente.';
$string['commerce_purchase_note_required'] = 'Une note interne est obligatoire.';
$string['commerce_purchase_note_too_long'] = 'La note interne est trop longue.';

// 7.95D11-D12 — Unified sales polish and certification.
$string['commerce_purchase_identifier'] = 'Identifiant utilisateur Moodle';
$string['commerce_purchase_open_user360'] = 'Ouvrir User360';


// 7.95D13 — Unified sales visual and operational completion.
$string['commerce_purchase_open_moodle_profile'] = 'Ouvrir le profil Moodle';
$string['commerce_purchase_retry_short'] = 'Relancer';
$string['commerce_purchase_payment_request'] = 'Demande de paiement';
$string['commerce_purchase_payment_request_attempts'] = 'Tentatives : {$a}';
$string['commerce_purchase_payment_request_expires'] = 'Expiration : {$a}';
$string['commerce_purchase_fulfillment_type_subscription_enrolment'] = 'Inscription au cours';
$string['commerce_purchase_fulfillment_type_course_access'] = 'Accès au cours';
$string['commerce_purchase_fulfillment_type_digital_download'] = 'Téléchargement numérique';
$string['commerce_purchase_fulfillment_type_digital_product'] = 'Accès au produit numérique';
$string['commerce_purchase_payment_status_none'] = 'Aucun paiement';
$string['commerce_purchase_payment_status_created'] = 'Créé';
$string['commerce_purchase_payment_status_pending'] = 'En attente';
$string['commerce_purchase_payment_status_processing'] = 'En cours';
$string['commerce_purchase_payment_status_paid'] = 'Payé';
$string['commerce_purchase_payment_status_succeeded'] = 'Réussi';
$string['commerce_purchase_payment_status_completed'] = 'Terminé';
$string['commerce_purchase_payment_status_failed'] = 'Échec';
$string['commerce_purchase_payment_status_error'] = 'Erreur';
$string['commerce_purchase_payment_status_refunded'] = 'Remboursé';
$string['commerce_purchase_payment_status_cancelled'] = 'Annulé';
$string['commerce_purchase_payment_status_canceled'] = 'Annulé';
$string['commerce_purchase_payment_status_expired'] = 'Expiré';
$string['commerce_purchase_fulfillment_status_none'] = 'Non démarrée';
$string['commerce_purchase_fulfillment_status_pending'] = 'En attente';
$string['commerce_purchase_fulfillment_status_processing'] = 'En cours';
$string['commerce_purchase_fulfillment_status_queued'] = 'En file d’attente';
$string['commerce_purchase_fulfillment_status_fulfilled'] = 'Terminée';
$string['commerce_purchase_fulfillment_status_completed'] = 'Terminée';
$string['commerce_purchase_fulfillment_status_failed'] = 'Échec';
$string['commerce_purchase_fulfillment_status_error'] = 'Erreur';
$string['commerce_purchase_fulfillment_status_active'] = 'Active';
$string['commerce_purchase_type_course_access'] = 'Accès au cours';
$string['commerce_purchase_type_digital_download'] = 'Produit numérique';

// 7.95D14 purchase polish.
$string['commerce_purchase_payment_request_open'] = 'Voir la demande #{$a}';
$string['commerce_purchase_payment_request_family'] = 'Famille';
$string['commerce_purchase_payment_requests_section'] = 'Demandes de paiement associées';
$string['commerce_purchase_payment_request_summary'] = '{$a->family} — demande #{$a->id}';
$string['commerce_purchase_payment_request_field_userid'] = 'Utilisateur';
$string['commerce_purchase_payment_request_field_email'] = 'E-mail';
$string['commerce_purchase_payment_request_field_firstname'] = 'Prénom';
$string['commerce_purchase_payment_request_field_lastname'] = 'Nom';
$string['commerce_purchase_payment_request_field_price'] = 'Prix';
$string['commerce_purchase_payment_request_field_sessionid'] = 'Identifiant de session';
$string['commerce_purchase_payment_request_field_transactionid'] = 'Identifiant de transaction';
$string['commerce_purchase_payment_request_field_payment_link'] = 'Lien de paiement';
$string['commerce_purchase_payment_request_field_creation_date'] = 'Création';
$string['commerce_purchase_payment_request_field_last_update'] = 'Dernière mise à jour';
$string['commerce_purchase_payment_request_field_payment_date'] = 'Date de paiement';
$string['commerce_purchase_payment_request_field_expiration_date'] = 'Expiration';
$string['commerce_purchase_payment_request_field_attempts'] = 'Tentatives';
$string['commerce_purchase_payment_request_field_last_attempt'] = 'Dernière tentative';
$string['commerce_purchase_payment_request_field_last_error'] = 'Dernière erreur';
$string['commerce_purchase_payment_request_field_locked_list_price'] = 'Prix catalogue verrouillé';
$string['commerce_purchase_payment_request_field_locked_discount_percent'] = 'Remise verrouillée (%)';
$string['commerce_purchase_payment_request_field_locked_discount_amount'] = 'Montant de remise verrouillé';
$string['commerce_purchase_payment_request_field_locked_discount_reason'] = 'Motif de remise';
$string['commerce_purchase_payment_request_field_locked_final_price'] = 'Prix final verrouillé';
$string['commerce_purchase_payment_request_field_locked_at'] = 'Verrouillé le';
$string['commerce_purchase_payment_request_field_created_ip'] = 'Adresse IP';
$string['commerce_purchase_payment_request_field_created_useragent'] = 'Agent utilisateur';
$string['commerce_purchase_payment_request_field_accept_language'] = 'Langue du navigateur';
$string['commerce_purchase_payment_request_field_http_referer'] = 'Page d’origine';
$string['commerce_purchase_payment_request_field_response_json'] = 'Réponse du fournisseur';
$string['commerce_purchase_payment_request_field_emailsent'] = 'E-mail envoyé';
$string['commerce_purchase_payment_request_field_planid'] = 'Plan';
$string['commerce_purchase_payment_request_field_phone'] = 'Téléphone';
$string['commerce_purchase_payment_request_field_phone_country'] = 'Pays du téléphone';
$string['commerce_purchase_payment_request_field_subscriptionid'] = 'Abonnement';
$string['commerce_purchase_payment_request_field_retry_expires'] = 'Expiration de la relance';
$string['commerce_purchase_payment_request_field_reminder_stage'] = 'Étape de relance';
$string['commerce_purchase_payment_request_field_reminder1_at'] = 'Première relance';
$string['commerce_purchase_payment_request_field_reminder2_at'] = 'Deuxième relance';
$string['commerce_purchase_payment_request_field_login_token_expires'] = 'Expiration du jeton de connexion';
$string['commerce_purchase_payment_request_field_operation'] = 'Opération';
$string['commerce_purchase_payment_request_field_reference_subscription_id'] = 'Abonnement de référence';
$string['commerce_purchase_payment_request_field_productid'] = 'Produit numérique';
$string['commerce_purchase_payment_request_field_download_token_expires'] = 'Expiration du téléchargement';
$string['commerce_purchase_payment_request_field_receipt_sent'] = 'Reçu envoyé';
$string['commerce_purchase_payment_request_field_buyer_lang'] = 'Langue de l’acheteur';

// 7.95E — Catalogue commercial unifié.
$string['commerce_catalog_title'] = 'Catalogue commercial';
$string['commerce_catalog_description'] = 'Consulter dans un même espace les produits Native, les plans historiques et les produits numériques.';
$string['commerce_catalog_product_eyebrow'] = 'Produit du catalogue';
$string['commerce_catalog_table_label'] = 'Liste unifiée des produits commerciaux';
$string['commerce_catalog_origin'] = 'Origine';
$string['commerce_catalog_editorial'] = 'Statut éditorial';
$string['commerce_catalog_visibility'] = 'Visibilité';
$string['commerce_catalog_availability'] = 'Disponibilité';
$string['commerce_catalog_technical'] = 'État technique';
$string['commerce_catalog_content'] = 'Contenu délivré';
$string['commerce_catalog_compatibility'] = 'Compatibilité historique';
$string['commerce_catalog_available_from'] = 'Disponible à partir du';
$string['commerce_catalog_available_until'] = 'Disponible jusqu’au';
$string['commerce_catalog_fulfillments_count'] = '{$a} délivrance(s)';
$string['commerce_catalog_product_not_found'] = 'Le produit demandé est introuvable dans le catalogue unifié.';
$string['commerce_catalog_editorial_draft'] = 'Brouillon';
$string['commerce_catalog_editorial_published'] = 'Publié';
$string['commerce_catalog_editorial_archived'] = 'Archivé';
$string['commerce_catalog_visibility_visible'] = 'Visible';
$string['commerce_catalog_visibility_hidden'] = 'Masqué';
$string['commerce_catalog_visibility_direct_link'] = 'Lien direct';
$string['commerce_catalog_availability_on_sale'] = 'En vente';
$string['commerce_catalog_availability_upcoming'] = 'À venir';
$string['commerce_catalog_availability_unavailable'] = 'Indisponible';
$string['commerce_catalog_availability_ended'] = 'Vente terminée';
$string['commerce_catalog_technical_valid'] = 'Valide';
$string['commerce_catalog_technical_incomplete'] = 'Configuration incomplète';
$string['commerce_catalog_technical_error'] = 'Erreur de configuration';
$string['commerce_catalog_origin_native'] = 'Native';
$string['commerce_catalog_origin_legacy_plan'] = 'Plan historique';
$string['commerce_catalog_origin_legacy_digital'] = 'Produit numérique historique';
$string['commerce_catalog_type_course_access'] = 'Accès à un cours';
$string['commerce_catalog_type_digital_download'] = 'Produit numérique';
$string['commerce_catalog_type_bundle'] = 'Bundle';
$string['commerce_catalog_type_service'] = 'Service';
$string['commerce_catalog_fulfillment_course'] = 'Accès au cours « {$a} »';
$string['commerce_catalog_fulfillment_download'] = 'Téléchargement numérique';
$string['commerce_catalog_fulfillment_course_enrolment'] = 'Inscription à un cours';
$string['commerce_catalog_fulfillment_digital_download'] = 'Téléchargement numérique';

// Commerce 7.95E7-E10 unified product editor.
$string['commerce_product_step_prices'] = 'Prices';
$string['commerce_product_step_fulfillments'] = 'Fulfillments';
$string['commerce_product_step_access_scope'] = 'Access scope';
$string['commerce_product_prices_title'] = 'Product prices';
$string['commerce_product_prices_help'] = 'Manage Native catalogue prices by currency and provider. Existing purchases keep their locked price snapshot.';
$string['commerce_price_amount'] = 'Amount';
$string['commerce_provider'] = 'Provider';
$string['commerce_provider_price_id'] = 'Provider price identifier';
$string['commerce_product_fulfillments_title'] = 'Delivered access and content';
$string['commerce_product_fulfillments_help'] = 'Define the concrete rights granted by this product. A Plan and an Access Scope remain distinct objects: the Plan is sold, while the Scope describes reusable course coverage.';
$string['commerce_fulfillment_type'] = 'Delivery type';
$string['commerce_fulfillment_resource'] = 'Resource';
$string['commerce_fulfillment_duration'] = 'Duration (seconds)';
$string['commerce_fulfillment_quantity'] = 'Quantity';
$string['commerce_product_fulfillments_lifetime_help'] = 'Use 0 seconds for lifetime access.';
$string['commerce_access_scope_relation_title'] = 'Plan and Access Scope';
$string['commerce_access_scope_relation_help'] = 'The commercial product represents the sellable Plan. Its Access Scope remains a separate reusable object defining the covered courses.';
$string['commerce_access_scope_unmapped'] = 'This Native course-access product is not mapped to a Legacy Plan, so no Access Scope relation can be displayed.';
$string['commerce_access_scope_plan'] = 'Sellable plan';
$string['commerce_access_scope_scope'] = 'Linked access scope';
$string['commerce_access_scope_edit_plan'] = 'Edit the plan';
$string['commerce_access_scope_edit_scope'] = 'Edit the access scope';
$string['commerce_access_scope_courses'] = 'Courses covered by the scope';
$string['commerce_product_step_prices'] = 'Tarification';
$string['commerce_product_step_fulfillments'] = 'Délivrances';
$string['commerce_product_step_access_scope'] = 'Périmètre d’accès';
$string['commerce_product_prices_title'] = 'Prix du produit';
$string['commerce_product_prices_help'] = 'Gérez les prix du catalogue Native par devise et fournisseur. Les ventes existantes conservent leur instantané de prix verrouillé.';
$string['commerce_price_amount'] = 'Montant';
$string['commerce_provider'] = 'Fournisseur';
$string['commerce_provider_price_id'] = 'Identifiant du prix fournisseur';
$string['commerce_product_fulfillments_title'] = 'Accès et contenus délivrés';
$string['commerce_product_fulfillments_help'] = 'Définissez les droits concrets accordés par ce produit. Le Plan et l’Access Scope restent deux objets distincts : le Plan est vendu, tandis que le Scope décrit un périmètre de cours réutilisable.';
$string['commerce_fulfillment_type'] = 'Type de délivrance';
$string['commerce_fulfillment_resource'] = 'Ressource';
$string['commerce_fulfillment_duration'] = 'Durée (secondes)';
$string['commerce_fulfillment_quantity'] = 'Quantité';
$string['commerce_product_fulfillments_lifetime_help'] = 'Utilisez 0 seconde pour un accès sans expiration.';
$string['commerce_access_scope_relation_title'] = 'Plan et Access Scope';
$string['commerce_access_scope_relation_help'] = 'Le produit commercial représente le Plan vendu. Son Access Scope reste un objet séparé et réutilisable qui définit les cours couverts.';
$string['commerce_access_scope_unmapped'] = 'Ce produit Native de type accès aux cours n’est relié à aucun Plan historique ; aucun Access Scope ne peut donc être affiché.';
$string['commerce_access_scope_plan'] = 'Plan vendu';
$string['commerce_access_scope_scope'] = 'Access Scope lié';
$string['commerce_access_scope_edit_plan'] = 'Modifier le Plan';
$string['commerce_access_scope_edit_scope'] = 'Modifier l’Access Scope';
$string['commerce_access_scope_courses'] = 'Cours couverts par le Scope';
$string['commerce_edit_in_source'] = 'Edit in source';
$string['commerce_edit_in_source'] = 'Modifier dans l’écran d’origine';
$string['commerce_product_step_bundle_pricing'] = 'Règles de prix du bundle';
$string['commerce_product_step_assets'] = 'Médias et fichiers';
$string['commerce_product_assets_title'] = 'Médias et fichiers';
$string['commerce_product_assets_help'] = 'Gérez l’image de couverture du produit et, pour un produit numérique, les versions ordinateur et mobile du fichier livré.';
$string['commerce_cover_image'] = 'Image de couverture';
$string['commerce_digital_files'] = 'Fichiers numériques livrés';
$string['commerce_desktop_file'] = 'Version ordinateur';
$string['commerce_mobile_file'] = 'Version mobile';
$string['commerce_digital_files_need_mapping'] = 'Ce produit numérique Native doit être relié à un produit numérique historique pour gérer ses fichiers dans cette phase.';
$string['commerce_invalid_asset_type'] = 'Le type de fichier envoyé n’est pas autorisé.';
$string['commerce_product_prices_guided_help'] = 'Choisissez une devise et un fournisseur dans les listes. Ajoutez autant de lignes tarifaires que nécessaire.';
$string['commerce_add_price_row'] = 'Ajouter un tarif';
$string['commerce_invalid_price'] = 'Le montant saisi est invalide.';
$string['commerce_product_fulfillments_guided_help'] = 'Définissez ce que le client reçoit à l’aide des listes proposées. Deux lignes sont affichées par défaut et vous pouvez en ajouter.';
$string['commerce_add_fulfillment_row'] = 'Ajouter une délivrance';
$string['commerce_incomplete_fulfillment_row'] = 'La délivrance {$a} est incomplète.';
$string['commerce_unknown_fulfillment_type'] = 'Le type de délivrance sélectionné n’est pas reconnu.';
$string['commerce_invalid_fulfillment_resource'] = 'La ressource sélectionnée pour la délivrance est introuvable.';
$string['commerce_fulfillment_course_access'] = 'Accès à un cours Moodle';
$string['commerce_fulfillment_course_enrolment'] = 'Inscription à un cours Moodle';
$string['commerce_fulfillment_digital_download'] = 'Téléchargement d’un fichier numérique';
$string['commerce_fulfillment_digital_product'] = 'Accès à un produit numérique';
$string['commerce_fulfillment_custom'] = 'Délivrance personnalisée';
$string['commerce_resource_course'] = 'Cours : {$a}';
$string['commerce_resource_digital'] = 'Produit numérique : {$a}';
$string['commerce_duration_lifetime'] = 'Accès permanent';
$string['commerce_duration_30_days'] = '30 jours';
$string['commerce_duration_90_days'] = '90 jours';
$string['commerce_duration_365_days'] = '365 jours';
$string['commerce_missing_course'] = 'Cours introuvable (#{$a})';
$string['commerce_missing_digital_product'] = 'Produit numérique introuvable (#{$a})';
$string['commerce_product_diagnostic'] = 'Diagnostic du produit';
$string['commerce_validation_no_active_price'] = 'Aucun tarif actif n’est configuré.';
$string['commerce_validation_no_fulfillment'] = 'Aucune délivrance n’est configurée.';
$string['commerce_validation_hidden'] = 'Le produit est masqué dans la boutique.';
$string['commerce_validation_not_on_sale'] = 'Le produit n’est pas actuellement en vente.';
$string['commerce_technical_reference'] = 'Référence technique';
$string['commerce_status_publication'] = 'Publication';
$string['commerce_status_sale'] = 'Vente';
$string['commerce_status_visibility'] = 'Visibilité';
$string['commerce_status_configuration'] = 'Configuration';
$string['settings:commerce_catalog_heading'] = 'Catalogue Commerce';
$string['settings:commerce_catalog_heading_desc'] = 'Réglages partagés par l’administration du catalogue.';
$string['settings:commerce_enabled_currencies'] = 'Devises disponibles';
$string['settings:commerce_enabled_currencies_desc'] = 'Codes ISO séparés par des virgules. Valeurs supportées : EUR, RUB, USD, GBP, CHF, CAD et JPY.';

$string['commerce_price_deleted'] = 'Tarif supprimé.';
$string['commerce_price_currency_duplicate'] = 'Un tarif existe déjà pour cette devise. Un produit ne peut avoir qu’un tarif commercial par devise.';
$string['commerce_prices_unique_currency_help'] = 'Un seul tarif commercial est autorisé par devise. Modifiez la ligne existante au lieu d’en créer une nouvelle.';
$string['commerce_add_price'] = 'Ajouter un tarif';
$string['commerce_plans_title'] = 'Plans d’accès';
$string['commerce_plan_add'] = 'Ajouter un Plan';
$string['commerce_plan_edit'] = 'Modifier le Plan';
$string['commerce_scopes_title'] = 'Périmètres d’accès';
$string['commerce_scope_add'] = 'Ajouter un périmètre d’accès';
$string['commerce_scope_edit'] = 'Modifier le périmètre d’accès';
$string['commerce_scope_plans_count'] = 'Plans associés';
$string['commerce_scope_used_by_plans'] = 'Plans utilisant ce périmètre';

$string['commerce_catalog_lifecycle_active'] = 'Actif';
$string['commerce_catalog_lifecycle_inactive'] = 'Inactif';
$string['commerce_catalog_lifecycle_archived'] = 'Archivé';
$string['commerce_product_activate'] = 'Activer';
$string['commerce_product_deactivate'] = 'Désactiver';
$string['commerce_product_activated'] = 'Le produit est maintenant actif.';
$string['commerce_product_deactivated'] = 'Le produit est maintenant inactif.';
$string['commerce_product_archived'] = 'Le produit a été archivé.';
$string['commerce_product_status_managed_help'] = 'L’état est piloté depuis la liste des produits. Une configuration incomplète empêche l’activation.';
$string['commerce_validation_missing_plan'] = 'Aucun Plan n’est associé à ce produit.';
$string['commerce_validation_missing_scope'] = 'Le Plan n’est associé à aucun Access Scope.';
$string['commerce_validation_empty_scope'] = 'L’Access Scope ne contient aucun cours.';
$string['commerce_validation_missing_digital'] = 'Aucun produit numérique n’est associé à ce produit.';
$string['commerce_validation_missing_digital_file'] = 'Le produit numérique ne contient ni fichier ordinateur ni fichier mobile.';
$string['commerce_validation_bundle_too_small'] = 'Le bundle doit contenir au moins deux composants.';
$string['commerce_validation_inactive_bundle_component'] = 'Tous les composants du bundle doivent être actifs.';
$string['commerce_edit_digital_source'] = 'Modifier les fichiers numériques';
$string['commerce_prices_catalogue_help'] = 'Un seul tarif commercial peut être défini par devise. Le fournisseur de paiement est choisi par le checkout ; les anciennes métadonnées fournisseur sont conservées en arrière-plan.';
$string['commerce_scope_plans_count'] = 'Nombre de Plans associés';
$string['commerce_scope_delete_blocked'] = 'Impossible de supprimer ce périmètre d’accès : {$a} Plan(s) lui sont encore associés.';
$string['commerce_scope_deleted'] = 'Le périmètre d’accès a été supprimé.';
$string['commerce_plan_current_subscriptions'] = 'Souscriptions actives ou en attente';
$string['commerce_plan_delete_blocked'] = 'Impossible de supprimer ce Plan : {$a} souscription(s) active(s) ou en attente lui sont attachées.';
$string['commerce_plan_deleted'] = 'Le Plan a été supprimé.';

// 7.95E19B.
$string['commerce_plan_toggle_help'] = 'Activer ou désactiver ce Plan';
$string['commerce_plan_business_information'] = 'Informations du Plan';
$string['commerce_technical_information'] = 'Informations techniques';
$string['commerce_date_created'] = 'Date de création';
$string['commerce_date_modified'] = 'Dernière modification';
$string['commerce_cover_error_maxbytes'] = 'La cover n’a pas pu être enregistrée : le fichier dépasse la taille maximale autorisée de {$a}.';
$string['commerce_cover_error_upload'] = 'La cover n’a pas pu être enregistrée. Vérifiez son format et réessayez.';
$string['commerce_product_back_to_view'] = 'Fiche produit';
$string['commerce_internal_id'] = 'Identifiant interne';
$string['commerce_plan_entitlements_explanation'] = 'Ces droits d’accès sont les règles d’exécution historiques du Plan (cours, rôle, groupe et niveau d’accès). Ils complètent la lecture métier Plan → Périmètre d’accès sans recréer des Fulfillments éditables.';
$string['commerce_plan_upgrades_explanation'] = 'Ces règles définissent les passages autorisés entre ce Plan et d’autres Plans, ainsi que le mode de calcul appliqué.';
$string['commerce_manage_entitlements'] = 'Gérer les droits d’accès';
$string['commerce_manage_upgrades'] = 'Gérer les règles d’upgrade';
$string['commerce_back_to_plan'] = 'Retour à la fiche du Plan';
$string['commerce_plan_upgrades_for'] = 'Règles d’upgrade du Plan : {$a}';

$string['commerce_purchase_status_overview'] = 'État de l’achat';
$string['commerce_purchase_dimension_payment'] = 'Paiement';
$string['commerce_purchase_dimension_order'] = 'Commande';
$string['commerce_purchase_dimension_delivery'] = 'Délivrance';
$string['commerce_purchase_dimension_access'] = 'Accès';
$string['commerce_purchase_payment_not_required'] = 'Aucun paiement requis';
$string['commerce_purchase_fulfillment_status_not_started'] = 'Non démarrée';
$string['commerce_purchase_access_status_active'] = 'Actif';
$string['commerce_purchase_access_status_pending'] = 'En attente';
$string['commerce_purchase_access_status_blocked'] = 'Bloqué';

$string['commerce_purchase_order_status_completed'] = 'Terminée';

// 7.95E19D final fulfillment distinction.
$string['commerce_purchase_start_fulfillment'] = 'Lancer la délivrance';
$string['commerce_purchase_start_fulfillment_confirm'] = 'Lancer maintenant la délivrance initiale de cet achat ?';
$string['commerce_purchase_fulfillment_process_success'] = 'La délivrance a été exécutée avec succès.';
$string['commerce_purchase_fulfillment_missing_grants'] = 'La délivrance ne peut pas être lancée : aucun droit d’accès Native n’est enregistré pour cet achat. Cet achat est probablement une donnée historique ou incomplète ; aucune opération n’a été créée afin d’éviter un accès incorrect.';

// Commerce 7.95E19E fix.
$string['admin_event_commerce_purchase_fulfillment_retried'] = 'Délivrance d’un achat relancée';
$string['admin_event_commerce_purchase_note_added'] = 'Note ajoutée à un achat';
$string['admin_event_commerce_purchase_fulfillment_closed_without_delivery'] = 'Achat clos sans délivrance';
$string['commerce_purchase_close_without_fulfillment'] = 'Clore sans délivrance';
$string['commerce_purchase_close_without_fulfillment_confirm'] = 'Confirmer la clôture sans délivrance';
$string['commerce_purchase_close_without_fulfillment_confirm_text'] = 'Cet achat sera marqué comme clos sans délivrance. Aucun accès ne sera créé ou retiré.';
$string['commerce_purchase_closed_without_fulfillment_success'] = 'L’achat a été clos sans délivrance. Aucun accès n’a été créé.';
$string['commerce_purchase_closed_without_fulfillment_notice'] = 'Délivrance close sans délivrance : aucun accès n’a été créé.';

$string['commerce_statistics_products_title'] = 'Statistiques par produit';
$string['commerce_statistics_products_description'] = 'Les revenus sont calculés uniquement à partir des commandes disposant d’un paiement réussi. Les commandes gratuites restent comptées séparément et les devises ne sont jamais converties ni additionnées entre elles.';
$string['commerce_statistics_products_empty'] = 'Aucune vente produit ne correspond à la période et aux filtres sélectionnés.';
$string['commerce_statistics_products_table_label'] = 'Statistiques commerciales des produits en {$a}';
$string['commerce_statistics_product'] = 'Produit';
$string['commerce_statistics_product_orders'] = 'Commandes';
$string['commerce_statistics_product_paid_orders'] = 'Payantes';
$string['commerce_statistics_product_free_orders'] = 'Gratuites';
$string['commerce_statistics_product_quantity'] = 'Quantité';
$string['commerce_statistics_product_revenue'] = 'Chiffre d’affaires encaissé';

$string['commerce_product_statistics_empty'] = 'Aucune vente enregistrée pour ce produit sur cette période.';

$string['commerce_digital_file_unavailable'] = 'Le fichier numérique demandé n’est pas disponible.';

// Commerce 7.95E21 fix: product performance filters.
$string['commerce_statistics_period_label'] = 'Période';
$string['commerce_statistics_period_180_days'] = '6 derniers mois';
$string['commerce_statistics_period_365_days'] = '12 derniers mois';
$string['commerce_statistics_period_all_time'] = 'Depuis le début';

// 7.95E21 statistics refinements.
$string['commerce_statistics_chart_mode'] = 'Affichage du chiffre d’affaires';
$string['commerce_statistics_chart_mode_instant'] = 'Chiffre d’affaires par période';
$string['commerce_statistics_chart_mode_cumulative'] = 'Chiffre d’affaires cumulé';
$string['commerce_statistics_chart_revenue_cumulative'] = 'Chiffre d’affaires cumulé';
$string['commerce_statistics_chart_product_revenue_cumulative'] = 'Chiffre d’affaires cumulé du produit';
$string['commerce_statistics_chart_product_orders'] = 'Ventes du produit';
$string['commerce_statistics_product_failed_payments'] = 'Paiements échoués';

// Commerce 7.95F2 — boutique unifiée.
$string['commerce_storefront_title'] = 'La boutique CampusFR';
$string['commerce_storefront_intro'] = 'Cours, ressources numériques et packs : découvrez tout ce dont vous avez besoin pour progresser en français.';
$string['commerce_storefront_search_placeholder'] = 'Rechercher un cours, une ressource ou un pack…';
$string['commerce_storefront_filter_type'] = 'Type de produit';
$string['commerce_storefront_buy_now'] = 'Acheter';
$string['commerce_storefront_discover'] = 'Découvrir';
$string['commerce_storefront_empty_title'] = 'Aucun produit trouvé';
$string['commerce_storefront_empty'] = 'Modifiez les filtres ou la devise pour afficher d’autres produits.';
$string['commerce_storefront_result_count'] = '{$a} produit(s)';
$string['commerce_storefront_product_not_found'] = 'Ce produit n’est pas disponible dans la boutique.';
$string['commerce_storefront_back'] = 'Retour à la boutique';
$string['commerce_storefront_detail_scaffold_notice'] = 'Cette première fiche utilise le socle générique de la boutique. La phase F3 permettra de composer des pages éditoriales entièrement personnalisées.';

// Capacités.
$string['subscriptions:view_dashboard'] = 'Consulter le tableau de bord des abonnements';
$string['subscriptions:view_users'] = 'Consulter les utilisateurs';
$string['subscriptions:manage_users'] = 'Gérer les utilisateurs';
$string['subscriptions:manage_subscriptions'] = 'Gérer les abonnements';
$string['subscriptions:view_digital'] = 'Consulter les produits numériques';
$string['subscriptions:manage_digital'] = 'Gérer les produits numériques';
$string['subscriptions:view_payments'] = 'Consulter les paiements';
$string['subscriptions:view_statistics'] = 'Consulter les statistiques Commerce';
$string['subscriptions:view_inbox'] = 'Consulter la boîte de réception CRM';
$string['subscriptions:manage_inbox'] = 'Gérer la boîte de réception CRM';
$string['subscriptions:manage_configuration'] = 'Gérer la configuration Commerce';
$string['subscriptions:use_inbox_ai'] = 'Utiliser les fonctions d’IA dans la boîte de réception CRM';
$string['subscriptions:use_crm_assistant_ai'] = 'Utiliser l’assistant IA du CRM';
$string['subscriptions:manage_crm_admin_tools'] = 'Gérer les outils d’administration du CRM';

// Commerce 7.95F3 — pages produit personnalisables.
$string['commerce_storefront_components_title'] = 'Inclus dans ce produit';
$string['commerce_storefront_faq_title'] = 'Questions fréquentes';

// Commerce 7.95F4 — Éditeur de page Boutique.
$string['commerce_product_step_storefront'] = 'Page Boutique';
$string['commerce_storefront_editor_title'] = 'Page de présentation du produit';
$string['commerce_storefront_editor_intro'] = 'Composez librement la page éditoriale présentée aux clients. Les prix et les actions d’achat restent contrôlés par Commerce.';
$string['commerce_storefront_preview'] = 'Prévisualiser la page';
$string['commerce_storefront_layout_title'] = 'Présentation de la page';
$string['commerce_storefront_template'] = 'Modèle de page';
$string['commerce_storefront_template_default'] = 'Standard';
$string['commerce_storefront_template_editorial'] = 'Éditorial';
$string['commerce_storefront_template_immersive'] = 'Immersif';
$string['commerce_storefront_theme'] = 'Clé de thème';
$string['commerce_storefront_theme_help'] = 'Clé technique facultative pour appliquer un style propre au produit, par exemple a1-premium.';
$string['commerce_storefront_section_empty'] = 'Section inutilisée';
$string['commerce_storefront_section_rich_text'] = 'Texte enrichi';
$string['commerce_storefront_section_features'] = 'Cartes avantages';
$string['commerce_storefront_section_media'] = 'Image ou média';
$string['commerce_storefront_section_testimonial'] = 'Témoignage';
$string['commerce_storefront_section_faq'] = 'Questions fréquentes';
$string['commerce_storefront_section_cta'] = 'Appel à l’action';
$string['commerce_storefront_section_components'] = 'Composants du bundle';
$string['commerce_storefront_section_number'] = 'Section {$a}';
$string['commerce_storefront_section_type'] = 'Type de section';
$string['commerce_storefront_section_title'] = 'Titre';
$string['commerce_storefront_section_subtitle'] = 'Sous-titre';
$string['commerce_storefront_section_content'] = 'Contenu principal';
$string['commerce_storefront_section_content_help'] = 'Utilisé pour le texte enrichi, la légende d’un média, un témoignage et un appel à l’action. Le HTML est accepté.';
$string['commerce_storefront_section_auxiliary'] = 'URL ou auteur';
$string['commerce_storefront_section_auxiliary_help'] = 'Pour un média, indiquez l’URL de l’image. Pour un témoignage, indiquez son auteur.';
$string['commerce_storefront_section_alt'] = 'Texte alternatif';
$string['commerce_storefront_section_items'] = 'Cartes ou questions';
$string['commerce_storefront_section_items_help'] = 'Un élément par ligne sous la forme : titre ||| contenu. Pour une FAQ : question ||| réponse.';

$string['settings:storefront_header'] = 'Boutique unifiée';
$string['settings:storefront_header_desc'] = 'Permet de basculer progressivement les anciens points d’entrée publics vers la boutique Commerce unifiée.';
$string['settings:storefront_enabled'] = 'Activer la redirection vers la boutique unifiée';
$string['settings:storefront_enabled_desc'] = 'Redirige l’ancien catalogue des cours vers la boutique unifiée. Les affichages intégrés et les liens directs vers un ancien plan restent inchangés.';

$string['commerce_storefront_merchandising_title'] = 'Mise en avant commerciale';
$string['commerce_storefront_merchandising_intro'] = 'Pilotez l’ordre, la visibilité et les signaux marketing affichés dans la boutique.';
$string['commerce_storefront_featured_product'] = 'Mettre ce produit en vedette';
$string['commerce_storefront_display_order'] = 'Ordre d’affichage';
$string['commerce_storefront_display_order_help'] = 'Les produits vedettes apparaissent d’abord, puis les autres par ordre croissant. La valeur par défaut est 1000.';
$string['commerce_storefront_badges'] = 'Badges marketing';
$string['commerce_storefront_badge_new'] = 'Nouveau';
$string['commerce_storefront_badge_bestseller'] = 'Best-seller';
$string['commerce_storefront_badge_popular'] = 'Le plus populaire';
$string['commerce_storefront_badge_limited_offer'] = 'Offre limitée';
$string['commerce_storefront_badge_gustave_choice'] = 'Choix de Gustave';
$string['commerce_storefront_badge_premium'] = 'Premium';
$string['commerce_storefront_badge_lifetime_access'] = 'Accès à vie';
$string['commerce_storefront_badge_complete_course'] = 'Formation complète';
$string['commerce_storefront_badge_promotion'] = 'Promotion';
$string['commerce_storefront_featured'] = 'Produit vedette';
$string['commerce_storefront_promotions_title'] = 'Prix de comparaison et promotions';
$string['commerce_storefront_promotions_help'] = 'Le prix Native actif reste le prix réellement facturé. Indiquez ici un prix de comparaison supérieur pour afficher un prix barré. Les dates sont facultatives.';
$string['commerce_storefront_compare_price'] = 'Prix de comparaison';
$string['commerce_storefront_promotion_start'] = 'Début';
$string['commerce_storefront_promotion_end'] = 'Fin';
$string['commerce_storefront_discount_percentage'] = '-{$a}%';
$string['commerce_storefront_promotion_until'] = 'Offre valable jusqu’au {$a}';


// 7.95F6B — Storefront customer experience.
$string['commerce_storefront_group_auto'] = 'Automatique (selon le type de produit)';
$string['commerce_storefront_group_courses'] = 'Cours';
$string['commerce_storefront_group_resources'] = 'Ressources';
$string['commerce_storefront_group_bundles'] = 'Packs';
$string['commerce_storefront_group_courses_intro'] = 'Des parcours structurés pour progresser en français.';
$string['commerce_storefront_group_resources_intro'] = 'Des ressources pratiques pour s’entraîner et réviser.';
$string['commerce_storefront_group_bundles_intro'] = 'Des offres combinées pensées pour offrir davantage de valeur.';
$string['commerce_storefront_owned'] = 'Déjà acquis';
$string['commerce_storefront_access_course'] = 'Accéder au cours';
$string['commerce_storefront_access_purchase'] = 'Voir mon achat';
$string['commerce_storefront_trust_secure_payment'] = 'Paiement sécurisé';
$string['commerce_storefront_trust_immediate_access'] = 'Accès immédiat';
$string['commerce_storefront_trust_support'] = 'Support CampusFR';
$string['commerce_storefront_trust_lifetime_access'] = 'Accès à vie';
$string['commerce_storefront_experience_title'] = 'Expérience client';
$string['commerce_storefront_experience_intro'] = 'Choisissez le bloc de catalogue, les éléments de réassurance et les informations clés du produit.';
$string['commerce_storefront_group'] = 'Bloc de catalogue';
$string['commerce_storefront_trust_title'] = 'Éléments de réassurance';
$string['commerce_storefront_quickfacts'] = 'Informations clés';
$string['commerce_storefront_quickfacts_help'] = 'Une information par ligne : valeur ||| libellé. Exemple : 82 ||| vidéos. Six informations maximum sont affichées.';

$string['commerce_product_technical_name'] = 'Nom technique anglais';
$string['commerce_product_sku_generated_help'] = 'Le SKU sera généré automatiquement à partir du type et du nom technique anglais. Le nom public pourra ensuite être surchargé par les traductions.';
$string['commerce_product_sku_immutable_help'] = 'Référence technique immuable, générée à la création du produit.';
$string['commerce_access_scope_no_plan'] = 'Aucun plan historique lié';
$string['commerce_access_scope_plan_without_scope'] = 'sans périmètre d’accès';
$string['commerce_access_scope_link_plan'] = 'Plan historique associé';
$string['commerce_access_scope_link_plan_help'] = 'Cette liaison permet au produit Native d’utiliser le périmètre d’accès existant. Un plan ne peut être relié qu’à un seul produit Native.';
$string['commerce_storefront_recommendations_title'] = 'Complétez votre parcours';
$string['commerce_storefront_recommendations_help'] = 'Un SKU Native par ligne, quatre recommandations maximum. Les produits déjà possédés sont masqués.';

$string['commerce_access_scope_mapping_conflict'] = 'Ce plan est déjà lié à un autre produit Native. Vérifiez la liaison avant de la transférer.';
$string['commerce_access_scope_already_linked_to'] = 'déjà lié à {$a}';
$string['commerce_access_scope_transfer_warning'] = 'Ce plan est déjà lié à un autre produit Native. Enregistrer à nouveau transférera explicitement la liaison vers ce produit.';
$string['commerce_storefront_edit_language'] = 'Langue du contenu éditorial';
$string['commerce_storefront_edit_language_help'] = 'Les sections et informations clés sont enregistrées séparément pour cette langue. La structure commerciale reste commune.';
$string['commerce_product_lifecycle_title'] = 'Cycle de vie du produit';
$string['commerce_product_lifecycle_intro'] = 'Archivez un produit pour le retirer de la vente. La suppression définitive est réservée au nettoyage de produits de test.';
$string['commerce_product_archive_title'] = 'Archiver le produit';
$string['commerce_product_archive_action'] = 'Archiver';
$string['commerce_product_delete_title'] = 'Supprimer définitivement';
$string['commerce_product_delete_safe_help'] = 'Aucune vente ni droit n\'est associé : la suppression est autorisée.';
$string['commerce_product_delete_blocked_help'] = 'Des ventes ou droits existent. La destruction forcée est désactivée par défaut et doit être explicitement autorisée dans config.php.';
$string['commerce_product_delete_action'] = 'Supprimer le produit';
$string['commerce_product_force_delete_action'] = 'Détruire le produit et ses ventes';
$string['commerce_product_admin_password'] = 'Mot de passe administrateur';
$string['commerce_product_delete_confirmation'] = 'Tapez SUPPRIMER pour confirmer';
$string['commerce_product_force_delete_disabled'] = 'La destruction forcée des produits est désactivée. Ajoutez $CFG->local_subscriptions_allow_destructive_product_delete = true; uniquement sur un environnement contrôlé.';
$string['commerce_product_force_delete_confirmation_failed'] = 'Le mot de passe ou le texte de confirmation est incorrect.';
$string['commerce_product_deleted'] = 'Produit supprimé.';

$string['commerce_access_scope_f6e_help'] = 'Le périmètre d’accès commercial et le mapping canonique de migration sont désormais séparés.';
$string['commerce_access_scope_shared_title'] = 'Périmètre d’accès partagé';
$string['commerce_access_scope_shared_help'] = 'Plusieurs produits Native peuvent réutiliser le même périmètre d’accès. Cette sélection ne modifie pas le mapping Legacy utilisé pour la migration.';
$string['commerce_access_scope_source_plan'] = 'Plan source du périmètre';
$string['commerce_access_scope_no_scope'] = 'Aucun périmètre d’accès';
$string['commerce_access_scope_canonical_title'] = 'Mapping canonique Legacy → Native';
$string['commerce_access_scope_canonical_help'] = 'Réservé à la compatibilité et au backfill PROD : un Plan Legacy ne peut avoir qu’un seul produit Native canonique.';
$string['commerce_access_scope_canonical_plan'] = 'Plan Legacy canonique';
$string['commerce_access_scope_no_canonical_plan'] = 'Aucun mapping canonique';
$string['commerce_access_scope_canonical_conflict'] = 'Ce Plan Legacy possède déjà un produit Native canonique. Utilisez son périmètre partagé sans transférer le mapping.';

// 7.95F6F — UX finale et cycle de vie produit.
$string['settings:commerce_security_header'] = 'Sécurité Commerce';
$string['settings:commerce_security_header_desc'] = 'Réglages de sécurité applicables aux opérations sensibles du catalogue Native.';
$string['settings:commerce_allow_destructive_product_delete'] = 'Autoriser la suppression destructive des produits';
$string['settings:commerce_allow_destructive_product_delete_desc'] = 'Autorise un administrateur à supprimer un produit ayant des ventes ou des droits associés, après confirmation renforcée. Laissez ce réglage désactivé en production.';
$string['commerce_product_restored'] = 'Le produit a été restauré comme brouillon.';
$string['commerce_product_restore_title'] = 'Restaurer le produit';
$string['commerce_product_restore_help'] = 'Le produit archivé sera restauré comme brouillon afin de permettre une vérification avant une nouvelle publication.';
$string['commerce_product_restore_action'] = 'Restaurer comme brouillon';
$string['commerce_product_archive_help'] = 'Le produit disparaîtra de la boutique et ne pourra plus être acheté. Son historique sera conservé.';
$string['commerce_product_back_to_editor'] = 'Retour à la fiche produit';
$string['commerce_product_dependencies_title'] = 'Données et historique associés';
$string['commerce_product_dependency_prices'] = 'Tarifs';
$string['commerce_product_dependency_translations'] = 'Traductions';
$string['commerce_product_dependency_components'] = 'Composants de bundle';
$string['commerce_product_dependency_entitlements'] = 'Droits configurés';
$string['commerce_product_dependency_mappings'] = 'Mappings Legacy';
$string['commerce_product_dependency_native_purchase_items'] = 'Lignes d’achat Native';
$string['commerce_product_dependency_native_purchases'] = 'Achats Native';
$string['commerce_product_dependency_legacy_plan_sales'] = 'Ventes de plans Legacy';
$string['commerce_product_dependency_legacy_digital_sales'] = 'Ventes numériques Legacy';
$string['commerce_product_dependency_grants'] = 'Droits accordés';
$string['commerce_product_delete_checkbox'] = 'Je confirme vouloir supprimer définitivement ce produit.';
$string['commerce_product_delete_confirmation_failed'] = 'La confirmation ou le mot de passe administrateur est incorrect.';
$string['commerce_product_force_delete_confirmation_failed'] = 'Pour une suppression destructive, saisissez exactement SUPPRIMER.';
$string['commerce_product_force_delete_disabled_help'] = 'La suppression destructive est désactivée dans les réglages Commerce. Ce produit ne peut pas être supprimé tant que son historique existe.';

$string['commerce_catalog_origin_legacy_only'] = 'Legacy uniquement';
$string['commerce_catalog_origin_native_short'] = 'NATIVE';
$string['commerce_catalog_origin_legacy_short'] = 'LEGACY';
$string['commerce_catalog_open_legacy_plan'] = 'Ouvrir le plan historique';
$string['commerce_catalog_open_legacy_digital'] = 'Ouvrir le produit numérique historique';
// 7.95G4-G5 — Interface panier de la boutique et page panier.
$string['commerce_cart_title'] = 'Votre panier';
$string['commerce_cart_add'] = 'Ajouter au panier';
$string['commerce_cart_view'] = 'Voir le panier';
$string['commerce_cart_already_owned'] = 'Déjà acheté';
$string['commerce_cart_empty_title'] = 'Votre panier est vide';
$string['commerce_cart_empty_text'] = 'Découvrez la boutique CampusFR et ajoutez les cours ou ressources qui vous correspondent.';
$string['commerce_cart_continue_shopping'] = 'Continuer mes achats';
$string['commerce_cart_checkout'] = 'Valider le panier';
$string['commerce_cart_quantity'] = 'Quantité';
$string['commerce_cart_unit_price'] = 'Prix unitaire';
$string['commerce_cart_subtotal'] = 'Sous-total';
$string['commerce_cart_discount'] = 'Réductions';
$string['commerce_cart_tax'] = 'Taxes';
$string['commerce_cart_total'] = 'Total';
$string['commerce_cart_remove'] = 'Supprimer';
$string['commerce_cart_update'] = 'Mettre à jour';
$string['commerce_cart_message_already_owned'] = 'Vous possédez déjà ce produit.';
$string['commerce_cart_message_already_in_cart'] = 'Ce produit est déjà dans votre panier.';
$string['commerce_cart_message_item_not_found'] = 'Cette ligne du panier est introuvable.';
$string['commerce_cart_message_add_success'] = 'Produit ajouté au panier.';
$string['commerce_cart_message_remove_success'] = 'Produit retiré du panier.';
$string['commerce_cart_message_update_success'] = 'Quantité mise à jour.';
$string['commerce_cart_message_clear_success'] = 'Panier vidé.';
$string['commerce_cart_message_unchanged'] = 'Le panier n’a pas été modifié.';
$string['commerce_cart_message_error'] = 'Le panier n’a pas pu être mis à jour.';

// 7.95G6 — UX et finition du panier.
$string['commerce_cart_total_ttc'] = 'Total TTC';
$string['commerce_cart_view_product'] = 'Voir la fiche du produit';
$string['commerce_cart_payment_secure'] = 'Paiement sécurisé';
$string['commerce_cart_instant_access'] = 'Accès immédiat après paiement';

// 7.95G7C-G7D — Promotions du panier.
$string['commerce_cart_promo_code'] = 'Code promo';
$string['commerce_cart_promo_placeholder'] = 'Saisissez votre code';
$string['commerce_cart_promo_apply'] = 'Appliquer';
$string['commerce_cart_promo_remove'] = 'Retirer';
$string['commerce_cart_message_promotion_code_saved'] = 'Code promo enregistré.';
$string['commerce_cart_message_promotion_removed'] = 'Code promo retiré.';
$string['commerce_cart_message_promotion_already_applied'] = 'Ce code promo est déjà appliqué.';
$string['commerce_cart_message_promotion_code_required'] = 'Saisissez un code promo.';
$string['commerce_cart_message_promotion_not_found'] = 'Ce code promo n’existe pas.';
$string['commerce_cart_message_promotion_inactive'] = 'Ce code promo n’est pas actif.';
$string['commerce_cart_message_promotion_not_started'] = 'Ce code promo n’est pas encore valable.';
$string['commerce_cart_message_promotion_expired'] = 'Ce code promo a expiré.';
$string['commerce_cart_message_promotion_currency_mismatch'] = 'Ce code promo ne s’applique pas à cette devise.';
$string['commerce_cart_message_promotion_minimum_cart_not_reached'] = 'Le montant minimal requis pour ce code promo n’est pas atteint.';
$string['commerce_cart_message_promotion_no_eligible_product'] = 'Ce code promo ne s’applique pas aux produits du panier.';
$string['commerce_cart_message_promotion_global_usage_limit_reached'] = 'Ce code promo a atteint sa limite d’utilisation.';
$string['commerce_cart_message_promotion_user_usage_limit_reached'] = 'Vous avez déjà utilisé ce code promo le nombre maximal de fois.';

// 7.95G7E — Administration et stabilisation des promotions.
$string['commerce_promotions_title'] = 'Promotions';
$string['commerce_promotions_description'] = 'Créez et gérez les codes promotionnels et les remises automatiques du moteur Commerce.';
$string['commerce_promotions_empty'] = 'Aucune promotion n’a encore été créée.';
$string['commerce_promotion_add'] = 'Ajouter une promotion';
$string['commerce_promotion_edit'] = 'Modifier la promotion';
$string['commerce_promotion_name'] = 'Nom';
$string['commerce_promotion_code'] = 'Code';
$string['commerce_promotion_type'] = 'Type';
$string['commerce_promotion_value'] = 'Valeur';
$string['commerce_promotion_value_minor'] = 'Valeur (points de base ou centimes)';
$string['commerce_promotion_percentage'] = 'Pourcentage';
$string['commerce_promotion_fixed'] = 'Montant fixe';
$string['commerce_promotion_minimum'] = 'Montant minimum du panier (centimes)';
$string['commerce_promotion_priority'] = 'Priorité';
$string['commerce_promotion_uses'] = 'Utilisations';
$string['commerce_promotion_global_limit'] = 'Limite globale d’utilisation';
$string['commerce_promotion_user_limit'] = 'Limite par utilisateur';
$string['commerce_promotion_active'] = 'Active';
$string['commerce_promotion_automatic'] = 'Automatique';
$string['commerce_promotion_stackable'] = 'Cumulable';
$string['commerce_promotion_productskus'] = 'SKU éligibles (un par ligne)';
$string['commerce_promotion_producttypes'] = 'Types de produit éligibles (un par ligne)';


// 7.95G7F — Promotion polish and Commerce configuration hub.
$string['commerce_configuration_title'] = 'Configuration Commerce';
$string['commerce_configuration_description'] = 'Configurez les règles d’accès, les plans commerciaux et les campagnes promotionnelles depuis un espace unique.';
$string['commerce_configuration_scopes_title'] = 'AccessScopes';
$string['commerce_configuration_scopes_description'] = 'Définir les périmètres de cours et de ressources accordés aux clients.';
$string['commerce_configuration_plans_title'] = 'Plans';
$string['commerce_configuration_plans_description'] = 'Gérer les plans, leurs durées, leurs accès et leur disponibilité commerciale.';
$string['commerce_configuration_promotions_title'] = 'Promotions';
$string['commerce_configuration_promotions_description'] = 'Créer et administrer les codes promo et les campagnes automatiques.';
$string['commerce_configuration_open'] = 'Ouvrir';
$string['commerce_promotion_back_to_list'] = 'Retour à la liste des codes';
$string['commerce_promotion_all_currencies'] = 'Toutes les devises';
$string['commerce_promotion_select_all'] = 'Tous — aucune restriction';
$string['commerce_promotion_name_help'] = 'Nom interne permettant d’identifier facilement la campagne dans le CRM.';
$string['commerce_promotion_code_help'] = 'Code saisi par le client. Il n’est pas requis pour une promotion automatique.';
$string['commerce_promotion_type_help'] = 'Choisissez une remise en pourcentage ou un montant fixe.';
$string['commerce_promotion_value_display'] = 'Valeur de la remise';
$string['commerce_promotion_value_display_help'] = 'Saisissez une valeur lisible : 20 signifie 20 % pour un pourcentage, ou 20 unités monétaires pour une remise fixe.';
$string['commerce_promotion_currency_help'] = 'Limitez la promotion à une devise configurée, ou choisissez toutes les devises.';
$string['commerce_promotion_minimum_display'] = 'Montant minimum du panier';
$string['commerce_promotion_minimum_help'] = 'Montant minimum avant réduction, exprimé dans l’unité monétaire affichée. Saisissez 0 pour ne fixer aucun minimum.';
$string['commerce_promotion_priority_help'] = 'Les promotions ayant la priorité la plus élevée sont évaluées en premier.';
$string['commerce_promotion_global_limit_help'] = 'Nombre maximal d’utilisations pour l’ensemble des clients. Laissez vide pour une utilisation illimitée.';
$string['commerce_promotion_user_limit_help'] = 'Nombre maximal d’utilisations par client. Laissez vide pour une utilisation illimitée.';
$string['commerce_promotion_active_help'] = 'La promotion peut être évaluée et appliquée.';
$string['commerce_promotion_automatic_help'] = 'La promotion est évaluée sans saisie d’un code par le client.';
$string['commerce_promotion_stackable_help'] = 'La promotion peut se cumuler avec d’autres promotions compatibles.';
$string['commerce_promotion_productskus_help'] = 'Sélectionnez les produits éligibles. « Tous » ne crée aucune restriction produit.';
$string['commerce_promotion_producttypes_help'] = 'Sélectionnez les types de produits éligibles. « Tous » ne crée aucune restriction de type.';
$string['commerce_promotion_validation_required'] = 'Ce champ est obligatoire.';
$string['commerce_promotion_validation_duplicate'] = 'Ce code est déjà utilisé.';
$string['commerce_promotion_validation_invalid'] = 'La valeur saisie est invalide.';

// Commerce 7.95H2 — Unified Checkout UI.
$string['commerce_checkout_title'] = 'Finaliser ma commande';
$string['commerce_checkout_eyebrow'] = 'CampusFR Commerce';
$string['commerce_checkout_subtitle'] = 'Vérifiez votre commande et choisissez votre moyen de paiement.';
$string['commerce_checkout_order_summary'] = 'Résumé de la commande';
$string['commerce_checkout_payment_title'] = 'Paiement';
$string['commerce_checkout_payment_description'] = 'Choisissez le prestataire adapté à votre devise.';
$string['commerce_checkout_provider_label'] = 'Moyen de paiement';
$string['commerce_checkout_provider_stripe'] = 'Stripe';
$string['commerce_checkout_provider_stripe_desc'] = 'Paiement sécurisé par carte bancaire.';
$string['commerce_checkout_provider_alfa'] = 'Alfa Bank';
$string['commerce_checkout_provider_alfa_desc'] = 'Paiement sécurisé en roubles.';
$string['commerce_checkout_continue_payment'] = 'Payer maintenant';
$string['commerce_checkout_back_cart'] = 'Retour au panier';
$string['commerce_checkout_prepare_error'] = 'Le checkout n’a pas pu être préparé. Vérifiez votre panier.';
$string['commerce_checkout_launch_h2_hint'] = 'Le lancement du prestataire sera activé avec le bridge de paiement H4.';
$string['commerce_checkout_issue_empty_cart'] = 'Votre panier est vide.';
$string['commerce_checkout_issue_customer_mismatch'] = 'Ce panier appartient à un autre compte.';
$string['commerce_checkout_issue_currency_mismatch'] = 'La devise du panier ne correspond pas à celle du checkout.';
$string['commerce_checkout_issue_generic'] = 'La commande doit être vérifiée avant de poursuivre.';

$string['commerce_checkout_launch_error'] = 'Le paiement n’a pas pu être initialisé. Aucun débit n’a été effectué.';

$string['commerce_checkout_steps_label'] = 'Étapes du paiement';
$string['commerce_checkout_step_cart'] = 'Panier';
$string['commerce_checkout_step_review'] = 'Vérification';
$string['commerce_checkout_step_payment'] = 'Paiement';
$string['commerce_checkout_step_confirmation'] = 'Confirmation';
$string['commerce_checkout_prepare_error_reference'] = 'Le checkout n’a pas pu être préparé. Vérifiez votre panier. Référence : {$a}';
$string['commerce_checkout_launch_error_reference'] = 'Le paiement n’a pas pu être initialisé. Aucun débit n’a été effectué. Référence : {$a}';
$string['commerce_purchase_grants_section'] = 'Droits accordés';
$string['commerce_purchase_no_grants'] = 'Aucun droit Native enregistré pour cet achat.';
$string['commerce_purchase_grant_type'] = 'Type de droit';
$string['commerce_purchase_resource'] = 'Ressource délivrée';
$string['commerce_purchase_beneficiary'] = 'Bénéficiaire';
$string['commerce_purchase_handler'] = 'Handler exécuté';
$string['commerce_purchase_attempts'] = 'Tentatives';
$string['commerce_purchase_duration'] = 'Durée';
$string['commerce_purchase_duration_seconds'] = '{$a} s';
$string['commerce_purchase_execution_reference'] = 'Référence d’exécution';
$string['commerce_purchase_message'] = 'Message';
$string['commerce_purchase_error'] = 'Erreur';
$string['commerce_purchase_fulfillment_attempts_section'] = 'Historique des tentatives de délivrance';
$string['commerce_purchase_no_fulfillment_attempts'] = 'Aucune tentative de délivrance Native enregistrée.';

// Commerce 7.95 H4.8.5 — fichiers digitaux Native.
$string['commerce_digital_files_native_help'] = 'Les fichiers sont stockés directement par le produit Native dans la File API privée de Moodle. La version ordinateur est utilisée en priorité pour la livraison.';
$string['commerce_digital_files_legacy_fallback'] = 'Aucun fichier Native n’est encore enregistré. Le fichier historique relié reste utilisé temporairement comme solution de compatibilité.';
$string['commerce_invalid_digital_file_type'] = 'Le fichier numérique doit être un document PDF.';
$string['commerce_digital_file_error_upload'] = 'Le fichier numérique n’a pas pu être enregistré. Vérifiez qu’il s’agit d’un PDF valide.';
$string['commerce_digital_file_error_maxbytes'] = 'Le fichier numérique dépasse la taille maximale autorisée ({$a}).';
$string['commerce_guest_checkout_title'] = 'Vos informations';
$string['commerce_guest_checkout_description'] = 'Indiquez vos coordonnées pour créer un compte provisoire sécurisé et poursuivre vers le paiement.';
$string['commerce_guest_checkout_continue'] = 'Continuer vers le paiement';
$string['commerce_guest_checkout_existing_account'] = 'Un compte existe déjà avec cette adresse. Connectez-vous pour continuer sans créer de doublon.';
$string['commerce_guest_checkout_provisional_ready'] = 'Votre compte provisoire et votre panier sont prêts. Vous pouvez maintenant poursuivre vers le paiement sécurisé.';

$string['commerce_guest_checkout_identity_required'] = 'Indiquez vos coordonnées avant de poursuivre vers le paiement.';
$string['commerce_guest_checkout_login_required'] = 'Cette adresse appartient à un compte existant. Connectez-vous pour continuer.';
$string['commerce_guest_checkout_account_activated'] = 'Votre achat est confirmé. Votre compte CampusFR est maintenant actif ; définissez un nouveau mot de passe avant votre première connexion.';
$string['commerce_guest_checkout_activation_subject'] = 'Votre compte CampusFR est prêt';
$string['commerce_guest_checkout_activation_message'] = 'Bonjour {$a->firstname},\n\nVotre paiement est confirmé et votre compte CampusFR est maintenant actif. Définissez votre mot de passe ici avant votre première connexion :\n{$a->reseturl}';
$string['commerce_guest_checkout_invalid_email'] = 'Saisissez une adresse email valide.';
$string['commerce_guest_checkout_invalid_firstname'] = 'Saisissez votre prénom (100 caractères maximum).';
$string['commerce_guest_checkout_invalid_lastname'] = 'Saisissez votre nom (100 caractères maximum).';
$string['commerce_guest_checkout_duplicate_email_accounts'] = 'Plusieurs comptes utilisent cette adresse email. Connectez-vous d’abord à votre compte pour poursuivre.';
$string['commerce_guest_checkout_session_expired'] = 'Votre session de checkout a expiré. Votre panier reste disponible : recommencez simplement la validation.';
$string['commerce_guest_checkout_account_mismatch'] = 'Le compte connecté ne correspond pas à l’adresse indiquée pour ce checkout. Connectez-vous avec le bon compte.';
$string['commerce_i2_order_not_found'] = 'Cette commande est introuvable.';
$string['commerce_i2_title_success'] = 'Votre achat est confirmé !';
$string['commerce_i2_message_success'] = 'Votre paiement a été validé et vos accès sont disponibles.';
$string['commerce_i2_title_processing'] = 'Paiement confirmé, accès en préparation';
$string['commerce_i2_message_processing'] = 'Nous avons bien reçu votre paiement. CampusFR finalise maintenant vos accès.';
$string['commerce_i2_title_pending'] = 'Vérification du paiement en cours';
$string['commerce_i2_message_pending'] = 'Le prestataire de paiement confirme encore la transaction. Cette page affichera vos accès dès leur validation.';
$string['commerce_i2_title_failed'] = 'Le paiement n’a pas abouti';
$string['commerce_i2_message_failed'] = 'Aucun débit confirmé n’a été enregistré. Vous pouvez réessayer ou choisir une autre solution.';
$string['commerce_i2_title_cancelled'] = 'Paiement annulé';
$string['commerce_i2_message_cancelled'] = 'Votre commande a été conservée, mais aucun paiement n’a été confirmé.';
$string['commerce_i2_title_unknown'] = 'Nous vérifions votre commande';
$string['commerce_i2_message_unknown'] = 'Le statut reçu nécessite une vérification complémentaire. Vos informations d’achat restent enregistrées.';
$string['commerce_i2_order_label'] = 'Commande CampusFR';
$string['commerce_i2_quantity'] = 'Quantité : {$a}';
$string['commerce_i2_open_course'] = 'Accéder au cours';
$string['commerce_i2_download_file'] = 'Télécharger le fichier';
$string['commerce_i2_retry'] = 'Retenter le paiement';
$string['commerce_i2_back_cart'] = 'Retourner au panier';
$string['commerce_i2_my_orders'] = 'Mes achats';
$string['commerce_i2_my_courses'] = 'Mes cours';
$string['commerce_i2_my_resources'] = 'Mes ressources';
$string['commerce_i2_support'] = 'Demander de l’aide';
$string['commerce_i2_support_subject'] = 'Aide concernant la commande {$a}';
$string['commerce_i2_next_title'] = 'Retrouver mes contenus';
$string['commerce_i3_access_unavailable'] = 'Cet accès n’est pas encore disponible ou ne l’est plus.';
$string['commerce_i3_access_missing'] = 'Le contenu demandé est introuvable.';
$string['commerce_i3_access_unsupported'] = 'Ce type d’accès n’est pas pris en charge.';
$string['commerce_i3_access_pending'] = 'Votre accès est en cours de préparation.';
$string['commerce_i3_access_expired'] = 'Cet accès a expiré.';
$string['commerce_i3_access_download_limit_reached'] = 'La limite de téléchargements a été atteinte.';

$string['commerce_i43_page_title'] = 'Commande {$a}';
$string['commerce_i43_back'] = 'Retour à Mes achats';
$string['commerce_i43_order'] = 'Commande CampusFR';
$string['commerce_i43_total'] = 'Total';
$string['commerce_i43_statuses'] = 'Statuts de la commande';
$string['commerce_i43_order_status'] = 'Commande';
$string['commerce_i43_payment_status'] = 'Paiement';
$string['commerce_i43_access_status'] = 'Accès';
$string['commerce_i43_items'] = 'Contenu de la commande';
$string['commerce_i43_unit_price'] = 'Prix unitaire';
$string['commerce_i43_gross'] = 'Sous-total';
$string['commerce_i43_discount'] = 'Réduction';
$string['commerce_i43_summary'] = 'Récapitulatif';
$string['commerce_i43_provider'] = 'Moyen de paiement';
$string['commerce_i43_paid_at'] = 'Paiement confirmé le';
$string['commerce_i43_technical'] = 'Informations techniques';


// Commerce 7.95 I4.4-I4.5 — Order timeline and payment information.
$string['commerce_i44_timeline'] = 'Suivi de la commande';
$string['commerce_i44_event_order_created'] = 'Commande enregistrée';
$string['commerce_i44_event_payment_confirmed'] = 'Paiement confirmé';
$string['commerce_i44_event_payment_pending'] = 'Paiement en attente';
$string['commerce_i44_event_payment_processing'] = 'Paiement en cours de traitement';
$string['commerce_i44_event_payment_failed'] = 'Échec du paiement';
$string['commerce_i44_event_payment_cancelled'] = 'Paiement annulé';
$string['commerce_i44_event_access_available'] = 'Accès disponible';
$string['commerce_i44_event_access_planned'] = 'Préparation de l’accès';
$string['commerce_i44_event_access_processing'] = 'Activation de l’accès en cours';
$string['commerce_i44_event_access_failed'] = 'Échec de l’activation de l’accès';
$string['commerce_i45_payment_information'] = 'Informations de paiement';
$string['commerce_i45_payment_status'] = 'Statut';
$string['commerce_i45_provider'] = 'Prestataire';
$string['commerce_i45_amount'] = 'Montant traité';
$string['commerce_i45_paid_at'] = 'Paiement confirmé le';
$string['commerce_i45_request_status'] = 'Statut de la demande';
$string['commerce_i45_requested_at'] = 'Demande créée le';
$string['commerce_i45_expires_at'] = 'Expiration de la demande';

// 7.95 I4.6-I4.8.
$string['commerce_i46_support_body'] = 'Bonjour,\\n\\nJ’ai besoin d’aide concernant la commande {$a->reference}.\\nCompte : {$a->email}\\n\\nDescription du problème :\\n';
$string['commerce_i46_contact_support'] = 'Contacter le support';
$string['commerce_i46_support_title'] = 'Besoin d’aide ?';
$string['commerce_i46_support_description'] = 'Notre équipe peut vous aider pour un accès, un téléchargement ou une question de paiement. La référence de cette commande sera ajoutée automatiquement à votre message.';
$string['commerce_i46_reference_to_share'] = 'Référence à communiquer';
$string['commerce_i47_bundle_eyebrow'] = 'Offre groupée';
$string['commerce_i47_bundle_title'] = 'Contenu de votre bundle';
$string['commerce_i47_bundle_description'] = 'Tous les éléments inclus dans cette offre sont présentés séparément ci-dessous avec leurs accès disponibles.';
$string['commerce_i47_bundle_items'] = 'éléments';
$string['commerce_i47_bundle_courses'] = 'cours';
$string['commerce_i47_bundle_digitals'] = 'ressources numériques';
$string['commerce_i47_bundle_accesses'] = 'accès disponibles';

// Commerce 7.95 I4.9 — projection client des achats.
$string['commerce_i49_course_purchase'] = 'Cours CampusFR';
$string['commerce_i49_view_course_page'] = 'Voir la présentation';
$string['commerce_i49_open_course'] = 'Accéder au cours';
$string['commerce_i49_bundle_purchases'] = 'Bundles achetés';
$string['commerce_i49_bundle_default_name'] = 'Offre CampusFR';
$string['commerce_i49_bundle_badge'] = 'Bundle';
$string['commerce_i49_bundle_contains'] = 'Contenu';

// 7.95 I4.10 — Customer Experience Certification.
$string['commerce_i410_order_confirmed'] = 'Commande confirmée';
$string['commerce_i410_order_processing'] = 'Commande en cours de traitement';
$string['commerce_i410_order_cancelled'] = 'Commande annulée';
$string['commerce_i410_order_failed'] = 'Commande non finalisée';
$string['commerce_i410_payment_received'] = 'Paiement reçu';
$string['commerce_i410_payment_pending'] = 'Paiement en attente';
$string['commerce_i410_payment_cancelled'] = 'Paiement annulé';
$string['commerce_i410_payment_failed'] = 'Paiement refusé';
$string['commerce_i410_access_available'] = 'Accès disponibles';
$string['commerce_i410_access_preparing'] = 'Accès en préparation';
$string['commerce_i410_access_failed'] = 'Activation des accès interrompue';
$string['commerce_i410_step_completed'] = 'Terminé';
$string['commerce_i410_step_pending'] = 'En cours';
$string['commerce_i410_step_failed'] = 'Action requise';
$string['commerce_i410_order_update'] = 'Mise à jour de la commande';
$string['commerce_i410_product_access'] = 'Accès aux produits';
$string['commerce_i410_payment_method_unknown'] = 'Paiement en ligne';
$string['commerce_i410_not_available'] = 'Non disponible';
$string['commerce_i410_type_bundle'] = 'Bundle';
$string['commerce_i410_type_course'] = 'Cours';
$string['commerce_i410_type_digital'] = 'Ressource numérique';
$string['commerce_i410_type_product'] = 'Produit';
$string['commerce_i410_bundle_includes'] = 'Articles inclus';
$string['commerce_i410_open_course'] = 'Accéder au cours';
$string['commerce_i410_reference'] = 'Référence';
$string['commerce_i410_order_date'] = 'Date';
$string['commerce_i410_article_count'] = 'Nombre d’articles';
$string['commerce_i410_payment_method'] = 'Moyen de paiement';
$string['commerce_i410_amount_paid'] = 'Montant payé';
$string['commerce_i410_invoice'] = 'Facture';
$string['commerce_i410_invoice_description'] = 'Téléchargez le document correspondant à cette commande.';
$string['commerce_i410_download_invoice'] = 'Télécharger la facture PDF';
$string['commerce_i410_invoice_title'] = 'Facture {$a}';
$string['commerce_i410_invoice_reference'] = 'Référence de facture';
$string['commerce_i410_invoice_date'] = 'Date';
$string['commerce_i410_invoice_customer'] = 'Client';
$string['commerce_i410_invoice_item'] = 'Article';
$string['commerce_i410_invoice_quantity'] = 'Quantité';
$string['commerce_i410_invoice_total'] = 'Total';
$string['commerce_i410_invoice_generated_notice'] = 'Document généré automatiquement depuis l’espace client CampusFR.';

$string['commerce_i411_original_amount'] = 'Montant initial';
$string['commerce_i411_discount'] = 'Remise';
$string['commerce_i411_promo_code'] = 'Code promotionnel';
$string['commerce_i411_paid_badge'] = 'Payé';
$string['commerce_i411_product_page'] = 'Voir le produit';
$string['commerce_i411_invoice_settings'] = 'Facturation Commerce';
$string['commerce_i411_invoice_settings_desc'] = 'Configurez séparément les entités émettrices des factures EUR et RUB.';
$string['commerce_i411_invoice_profile_eur'] = 'Entité de facturation EUR / Stripe';
$string['commerce_i411_invoice_profile_rub'] = 'Entité de facturation RUB / Alfa';
$string['commerce_i411_invoice_name'] = 'Nom ou raison sociale';
$string['commerce_i411_invoice_address'] = 'Adresse complète';
$string['commerce_i411_invoice_legal'] = 'Informations légales';
$string['commerce_i411_invoice_email'] = 'E-mail';
$string['commerce_i411_invoice_phone'] = 'Téléphone';
$string['commerce_i411_invoice_website'] = 'Site web';
$string['commerce_i411_invoice_tax_notice'] = 'Mention fiscale';
$string['commerce_i411_invoice_footer'] = 'Pied de facture';

$string['commerce_order_access_preparing'] = 'Accès en préparation';

$string['commerce_multi_item_order_title'] = 'Commande {$a}';
$string['commerce_purchase_origin'] = 'Origine de l’achat';
$string['commerce_purchase_origin_legacy'] = 'Legacy';
$string['commerce_purchase_origin_native'] = 'Native';
$string['commerce_invoice_purchase_date'] = 'Date de l’achat';
$string['commerce_invoice_bundle_includes'] = 'Inclus :';
$string['commerce_invoice_subtotal'] = 'Sous-total';
$string['commerce_invoice_discount'] = 'Remise';
$string['commerce_invoice_promotion_code'] = 'Code promotionnel';
$string['commerce_invoice_total_paid'] = 'Total payé';
$string['commerce_invoice_payment_information'] = 'Informations de paiement';
$string['commerce_invoice_payment_provider'] = 'Moyen de paiement';
$string['commerce_invoice_transaction_id'] = 'Identifiant de transaction';
$string['commerce_invoice_generated_at'] = 'Facture générée le {$a}';

$string['digital_library_title'] = 'Mes ressources digitales';
$string['digital_library_user_title'] = 'Ressources digitales de {$a}';
$string['digital_library_subtitle'] = 'Retrouvez vos produits numériques et téléchargez directement les fichiers disponibles.';
$string['digital_library_empty_title'] = 'Aucune ressource digitale pour le moment';
$string['digital_library_empty_description'] = 'Vos produits numériques apparaîtront ici dès qu’un achat vous donnera accès à un fichier.';
$string['digital_library_open_catalog'] = 'Découvrir la boutique';
$string['digital_library_download'] = 'Télécharger';
$string['digital_library_source_legacy'] = 'Historique';
$string['digital_library_source_native'] = 'Commerce';
$string['digital_library_bundle_badge'] = 'Inclus dans un bundle';
$string['digital_library_resource_fallback'] = 'Ressource digitale';
$string['digital_library_resource_number'] = 'Ressource digitale n°{$a}';

$string['digital_library_view_product'] = 'Voir la page du produit';
$string['digital_library_file'] = 'Fichier disponible';
$string['digital_library_files'] = 'Fichiers disponibles';
$string['digital_library_file_type'] = 'Type';
$string['digital_library_file_size'] = 'Taille';
$string['digital_library_already_downloaded'] = 'Déjà téléchargé';
$string['digital_library_not_downloaded_yet'] = 'Pas encore téléchargé';
$string['digital_library_last_download'] = 'Dernier téléchargement';
$string['digital_library_download_count_one'] = '1 téléchargement';
$string['digital_library_download_count_many'] = '{$a} téléchargements';
$string['digital_library_download_file'] = 'Télécharger';
$string['digital_library_download_aria'] = 'Télécharger {$a->file} — {$a->product}';
$string['digital_library_history_unavailable'] = 'Historique de téléchargement indisponible';
$string['event_digital_file_downloaded'] = 'Fichier numérique téléchargé';

$string['task_process_commerce_mail_queue'] = 'Traiter la file des e-mails transactionnels Commerce';

$string['commerce_mail_purchase_access_subject'] = 'Vos accès CampusFR sont disponibles';
$string['commerce_mail_purchase_receipt_subject'] = 'Confirmation de votre achat CampusFR';
$string['commerce_mail_payment_pending_subject'] = 'Votre paiement CampusFR est en cours de traitement';
$string['commerce_mail_payment_failed_subject'] = 'Votre paiement CampusFR n’a pas abouti';
$string['commerce_mail_payment_cancelled_subject'] = 'Votre paiement CampusFR a été annulé';
$string['commerce_mail_greeting'] = 'Bonjour {$a},';
$string['commerce_mail_customer_fallback'] = 'cher membre';
$string['commerce_mail_purchase_access_intro'] = 'Votre achat est confirmé et vos ressources sont maintenant disponibles.';
$string['commerce_mail_purchase_receipt_intro'] = 'Merci pour votre achat. Voici le récapitulatif de votre commande.';
$string['commerce_mail_payment_pending_intro'] = 'Votre paiement est encore en cours de traitement. Aucun nouvel accès ne sera activé avant sa confirmation.';
$string['commerce_mail_payment_pending_help'] = 'Vous pouvez consulter l’état de votre commande depuis votre espace CampusFR.';
$string['commerce_mail_payment_failed_intro'] = 'Nous n’avons pas pu confirmer votre paiement.';
$string['commerce_mail_payment_failed_help'] = 'Vous pouvez réessayer depuis votre espace CampusFR ou utiliser un autre moyen de paiement.';
$string['commerce_mail_payment_cancelled_intro'] = 'Votre paiement a été annulé et aucun montant n’a été confirmé par CampusFR.';
$string['commerce_mail_payment_cancelled_help'] = 'Votre panier reste disponible si vous souhaitez reprendre votre achat.';
$string['commerce_mail_reference'] = 'Référence';
$string['commerce_mail_quantity'] = 'Quantité';
$string['commerce_mail_total'] = 'Total';
$string['commerce_mail_payment_information'] = 'Informations de paiement';
$string['commerce_mail_payment_provider'] = 'Moyen de paiement';
$string['commerce_mail_transaction_reference'] = 'Transaction';
$string['commerce_mail_payment_status'] = 'Statut';
$string['commerce_mail_access_course'] = 'Accéder au cours';
$string['commerce_mail_download_file'] = 'Télécharger le fichier';
$string['commerce_mail_view_product'] = 'Voir la page du produit';
$string['commerce_mail_view_order'] = 'Voir ma commande';
$string['commerce_mail_view_purchases'] = 'Voir mes achats';
$string['commerce_mail_view_resources'] = 'Voir mes ressources digitales';
$string['commerce_mail_view_courses'] = 'Accéder à mes cours';
$string['commerce_mail_product_fallback'] = 'Produit CampusFR';
$string['commerce_mail_no_item_details'] = 'Le détail des ressources sera visible dans votre espace CampusFR.';

$string['crm_commerce_nav_mail'] = 'E-mails';
$string['commerce_mail_admin_title'] = 'E-mails transactionnels';
$string['commerce_mail_admin_description'] = 'Supervision de la file des e-mails Commerce, prévisualisation et reprise des erreurs.';
$string['commerce_mail_preview'] = 'Aperçu de l’e-mail';
$string['attempts'] = 'Tentatives';
$string['retry'] = 'Relancer';
$string['commerce_mail_type_purchase_access'] = 'Accès disponibles';
$string['commerce_mail_type_purchase_receipt'] = 'Confirmation d’achat';
$string['commerce_mail_type_payment_pending'] = 'Paiement en attente';
$string['commerce_mail_type_payment_failed'] = 'Paiement échoué';
$string['commerce_mail_type_payment_cancelled'] = 'Paiement annulé';
$string['commerce_mail_status_queued'] = 'En attente';
$string['commerce_mail_status_processing'] = 'En cours';
$string['commerce_mail_status_sent'] = 'Envoyé';
$string['commerce_mail_status_failed'] = 'Échec';
$string['commerce_mail_status_cancelled'] = 'Annulé';
$string['commerce_mail_language_fr'] = 'Français';
$string['commerce_mail_language_en'] = 'Anglais';
$string['commerce_mail_language_ru'] = 'Russe';
$string['commerce_mail_filter_all'] = 'Tous';
$string['commerce_mail_status_filter'] = 'Statut';
$string['commerce_mail_type_filter'] = 'Type d’e-mail';
$string['commerce_mail_language_filter'] = 'Langue';
$string['commerce_mail_purchase_id'] = 'ID de commande';
$string['commerce_mail_search_placeholder'] = 'E-mail, destinataire ou clé d’idempotence';
$string['commerce_mail_dashboard_description'] = 'Supervisez les e-mails transactionnels, prévisualisez leur contenu et reprenez les envois en erreur.';
$string['commerce_mail_templates_title'] = 'Modèles d’e-mails';
$string['commerce_mail_templates_description'] = 'Personnalisez les zones éditoriales des e-mails transactionnels sans modifier leurs blocs techniques sécurisés.';
$string['commerce_mail_templates_manage'] = 'Gérer les modèles';
$string['commerce_mail_template_type'] = 'Type d’e-mail';
$string['commerce_mail_template_language'] = 'Langue';
$string['commerce_mail_template_enabled'] = 'Modèle personnalisé actif';
$string['commerce_mail_template_subject'] = 'Objet';
$string['commerce_mail_template_preheader'] = 'Pré-en-tête';
$string['commerce_mail_template_heading'] = 'Titre principal';
$string['commerce_mail_template_intro'] = 'Introduction';
$string['commerce_mail_template_outro'] = 'Conclusion';
$string['commerce_mail_template_signature'] = 'Signature';
$string['commerce_mail_template_headerimage_enabled'] = 'Prévoir une image d’en-tête';
$string['commerce_mail_template_headerimage_note'] = 'Ajoutez une image horizontale légère. Elle remplacera le bandeau générique dans ce modèle d’e-mail.';
$string['commerce_mail_template_tokens'] = 'Jetons autorisés';
$string['commerce_mail_template_default'] = 'Contenu par défaut';
$string['commerce_mail_template_edit_title'] = '{$a->type} — {$a->language}';
$string['commerce_mail_template_edit_description'] = 'Les contenus saisis entoureront le bloc technique du message. Les accès, produits, montants et actions resteront générés par Commerce.';
$string['commerce_mail_template_invalid_type'] = 'Type d’e-mail invalide.';
$string['commerce_mail_template_invalid_language'] = 'Langue invalide.';
$string['commerce_mail_back_to_log'] = 'Retour au journal des e-mails';
$string['commerce_mail_template_reset'] = 'Restaurer par défaut';
$string['commerce_mail_template_reset_confirm'] = 'Supprimer cette personnalisation et restaurer le contenu par défaut ?';
$string['commerce_mail_template_reset_done'] = 'Le contenu par défaut a été restauré.';

$string['commerce_mail_template_headerimage_file'] = 'Image d’en-tête';
$string['commerce_mail_template_preview_title'] = 'Aperçu — {$a->type} — {$a->language}';
$string['commerce_mail_template_preview_description'] = 'Aperçu généré avec des données de démonstration. Aucun e-mail n’est envoyé.';
$string['plaintext'] = 'Version texte brut';

$string['settings:commerce_mail_audit_heading'] = 'Copies d’audit des e-mails Commerce';
$string['settings:commerce_mail_audit_heading_desc'] = 'Configure une copie transactionnelle indépendante, journalisée dans l’outbox, sans affecter l’envoi au client.';
$string['settings:commerce_mail_audit_copy_enabled'] = 'Activer les copies d’audit';
$string['settings:commerce_mail_audit_copy_enabled_desc'] = 'Crée une seconde intention d’e-mail indépendante pour les types sélectionnés.';
$string['settings:commerce_mail_audit_copy_address'] = 'Adresse des copies d’audit';
$string['settings:commerce_mail_audit_copy_address_desc'] = 'Adresse technique destinataire des copies, par exemple log@campusfr.fr.';
$string['settings:commerce_mail_audit_copy_types'] = 'Types d’e-mails à copier';
$string['settings:commerce_mail_audit_copy_types_desc'] = 'Sélectionne les intentions transactionnelles qui doivent produire une copie d’audit indépendante.';
$string['settings:commerce_mail_audit_copy_include_attachment'] = 'Joindre la facture à la copie d’audit';
$string['settings:commerce_mail_audit_copy_include_attachment_desc'] = 'Désactivé par défaut afin de limiter la duplication des données personnelles et des pièces jointes.';

$string['commerce_mail_preview_modes'] = 'Modes d’aperçu de l’e-mail';
$string['commerce_mail_preview_desktop'] = 'Ordinateur';
$string['commerce_mail_preview_mobile'] = 'Mobile';
$string['commerce_mail_preview_text'] = 'Texte brut';
$string['commerce_mail_preview_source'] = 'Source HTML';
$string['commerce_mail_preview_desktop_title'] = 'Aperçu de l’e-mail sur ordinateur';
$string['commerce_mail_preview_mobile_title'] = 'Aperçu de l’e-mail sur mobile';
$string['commerce_mail_health_certified'] = 'Mail Engine certifié';
$string['commerce_mail_health_attention'] = 'Mail Engine à contrôler';
$string['commerce_mail_health_readonly'] = 'Contrôle de santé en lecture seule du moteur transactionnel.';
$string['commerce_mail_health_ok'] = 'OK';
$string['commerce_mail_health_warnings'] = 'alertes';
$string['commerce_mail_health_errors'] = 'erreurs';

$string['commerce_cart_upgrade_label'] = 'Upgrade';
$string['commerce_cart_message_upgrade_not_eligible'] = 'Cet upgrade n’est plus disponible pour ce compte.';
$string['commerce_cart_upgrade_not_eligible'] = 'Cet upgrade n’est plus disponible. Actualisez la boutique puis réessayez.';
$string['crm_commerce_orders'] = 'Commandes';
$string['crm_commerce_orders_hint'] = 'Commandes Commerce Native rattachées à ce client.';
$string['crm_commerce_active_grants'] = 'Accès actifs';
$string['crm_commerce_active_grants_hint'] = 'Grants actifs pour les cours et ressources digitales.';
$string['crm_commerce_no_purchases'] = 'Aucun achat Commerce trouvé pour ce client.';
$string['crm_commerce_reference'] = 'Référence';
$string['crm_commerce_purchase_type'] = 'Type';
$string['crm_commerce_contents'] = 'Contenu';
$string['crm_commerce_amount'] = 'Montant';
$string['crm_commerce_view_order'] = 'Voir la commande';
$string['crm_commerce_type_course'] = 'Cours';
$string['crm_commerce_type_digital'] = 'Produit digital';
$string['crm_commerce_type_bundle'] = 'Bundle';
$string['crm_commerce_type_upgrade'] = 'Upgrade';
$string['crm_commerce_type_mixed'] = 'Panier mixte';
$string['crm_commerce_status_created'] = 'Créée';
$string['crm_commerce_status_payment_pending'] = 'Paiement en attente';
$string['crm_commerce_status_paid'] = 'Payée';
$string['crm_commerce_status_fulfillment_pending'] = 'Délivrance en attente';
$string['crm_commerce_status_fulfilled'] = 'Délivrée';
$string['crm_commerce_status_failed'] = 'Échec';
$string['crm_commerce_status_cancelled'] = 'Annulée';
$string['crm_timeline_commerce_purchase_course'] = 'Commande de cours créée';
$string['crm_timeline_commerce_purchase_digital'] = 'Commande digitale créée';
$string['crm_timeline_commerce_purchase_bundle'] = 'Commande bundle créée';
$string['crm_timeline_commerce_purchase_upgrade'] = 'Commande d’upgrade créée';
$string['crm_timeline_commerce_purchase_mixed'] = 'Commande mixte créée';
$string['crm_timeline_commerce_purchase_purchase'] = 'Commande créée';
$string['crm_timeline_commerce_purchase_description'] = '{$a->reference} · {$a->items} · {$a->amount}';
$string['crm_timeline_commerce_payment_paid'] = 'Paiement confirmé';
$string['crm_timeline_commerce_payment_pending'] = 'Paiement en attente';
$string['crm_timeline_commerce_payment_failed'] = 'Paiement échoué';
$string['crm_timeline_commerce_payment_description'] = '{$a->reference} · {$a->amount} · {$a->provider}';
$string['crm_timeline_commerce_grant_course_access'] = 'Accès au cours accordé';
$string['crm_timeline_commerce_grant_digital_download'] = 'Ressource digitale disponible';
$string['crm_timeline_commerce_grant_access'] = 'Accès accordé';

$string['commerce_order_print'] = 'Imprimer la commande';
$string['commerce_support_page_title'] = 'Support — {$a}';
$string['commerce_support_heading'] = 'Comment pouvons-nous vous aider ?';
$string['commerce_support_intro'] = 'Votre demande sera transmise directement à l’équipe CampusFR avec les informations utiles de votre commande.';
$string['commerce_support_back_to_order'] = 'Retour à la commande';
$string['commerce_support_order'] = 'Commande';
$string['commerce_support_customer'] = 'Client';
$string['commerce_support_email'] = 'Adresse e-mail';
$string['commerce_support_category'] = 'Type de demande';
$string['commerce_support_category_payment'] = 'Paiement';
$string['commerce_support_category_course_access'] = 'Accès au cours';
$string['commerce_support_category_download'] = 'Téléchargement';
$string['commerce_support_category_invoice'] = 'Facture';
$string['commerce_support_category_refund'] = 'Remboursement';
$string['commerce_support_category_other'] = 'Autre';
$string['commerce_support_subject'] = 'Sujet';
$string['commerce_support_default_subject'] = 'Question concernant la commande {$a}';
$string['commerce_support_message'] = 'Décrivez votre demande';
$string['commerce_support_send'] = 'Envoyer la demande';
$string['commerce_support_success'] = 'Votre demande a bien été transmise à l’équipe CampusFR. Nous vous répondrons dès que possible.';
$string['commerce_support_unavailable'] = 'Le support intégré est temporairement indisponible. Veuillez réessayer plus tard.';
$string['commerce_support_internal_reference'] = 'Référence de commande';
$string['commerce_support_payment_status'] = 'État du paiement';
$string['commerce_support_fulfillment_status'] = 'État de la délivrance';
$string['commerce_support_products'] = 'Produits concernés';

$string['event_commerce_customer_action_clicked'] = 'Action client Commerce suivie';

$string['commerce_tracking_invalid'] = 'Ce lien de suivi Commerce est invalide ou a expiré.';

$string['commerce_access_preparing'] = 'Accès en préparation';

$string['commerce_access_temporarily_unavailable'] = 'Accès temporairement indisponible';

$string['commerce_view_order'] = 'Voir la commande';

$string['profile_customer_space_title'] = 'Mon espace CampusFR';
$string['profile_link_courses'] = 'Mes cours';
$string['profile_link_resources'] = 'Mes ressources';
$string['profile_link_purchases'] = 'Mes achats';
$string['nav_my_courses'] = 'Mes cours';
$string['nav_my_resources'] = 'Mes ressources';
$string['nav_my_purchases'] = 'Mes achats';
$string['nav_my_profile'] = 'Mon profil';
$string['commerce_cart_clear'] = 'Vider le panier';
$string['commerce_cart_clear_confirm'] = 'Voulez-vous vraiment vider votre panier ?';

$string['commerce_cart_buy_now'] = 'Acheter maintenant';
$string['commerce_cart_remove_from_cart'] = 'Retirer du panier';
$string['commerce_cart_added_modal_title'] = 'Ajouté au panier';
$string['commerce_cart_added_modal_text'] = 'Votre article a bien été ajouté.';
$string['commerce_cart_clear_confirm_action'] = 'Vider le panier';
$string['commerce_cart_message_bundle_all_owned'] = 'Vous possédez déjà tous les éléments de cette offre.';
$string['commerce_cart_message_bundle_partial_owned'] = 'Cette offre contient un ou plusieurs éléments que vous possédez déjà. Le prix du bundle reste inchangé.';
$string['commerce_cart_message_buynow_success'] = 'Le produit est prêt pour le paiement.';

// Activation du compte après un achat Guest Checkout.
$string['commerce_guest_checkout_activation_message'] = 'Bonjour {$a->firstname},\n\nVotre paiement est confirmé. Définissez maintenant votre mot de passe CampusFR grâce à ce lien sécurisé :\n{$a->activationurl}\n\nCe lien est personnel et valable pendant 48 heures.';
$string['commerce_guest_activation_title'] = 'Activez votre compte CampusFR';
$string['commerce_guest_activation_title_prefix'] = 'Activez votre compte';
$string['commerce_guest_activation_quick_note'] = 'Une dernière étape : cela ne prend que quelques secondes et vos accès seront disponibles immédiatement.';
$string['commerce_guest_activation_intro'] = 'Bonjour {$a->firstname}, choisissez votre mot de passe pour accéder immédiatement à vos cours, ressources et achats.';
$string['commerce_guest_activation_email'] = 'Compte associé à : {$a}';
$string['commerce_guest_activation_submit'] = 'Définir mon mot de passe';
$string['commerce_guest_activation_success'] = 'Votre mot de passe a été défini. Vous êtes maintenant connecté à CampusFR.';
$string['commerce_guest_activation_invalid'] = 'Ce lien d’activation est invalide ou a expiré. Revenez sur la page de votre commande pour obtenir un nouveau lien.';
$string['commerce_guest_activation_failed'] = 'Votre compte n’a pas pu être activé. Détail technique : {$a}';
$string['commerce_guest_activation_password_invalid'] = 'Le mot de passe ne respecte pas la politique de sécurité : {$a}';
$string['commerce_guest_activation_result_title'] = 'Finalisez votre compte';
$string['commerce_guest_activation_result_message'] = 'Votre achat est confirmé. Définissez maintenant votre mot de passe pour accéder à vos cours, ressources et achats.';
$string['commerce_guest_activation_result_cta'] = 'Définir mon mot de passe';
$string['commerce_guest_existing_account_result_title'] = 'Retrouvez votre achat dans votre compte';
$string['commerce_guest_existing_account_result_message'] = 'Cette adresse est déjà associée à un compte CampusFR. Connectez-vous pour retrouver votre commande et vos accès.';

$string['commerce_guest_activation_confirm_password'] = 'Confirmer le nouveau mot de passe';

$string['commerce_guest_activation_email_label'] = 'Compte associé à :';
$string['commerce_guest_activation_security_title'] = 'Règles de sécurité';
$string['commerce_guest_activation_security_minlength'] = 'Au moins {$a} caractères';
$string['commerce_guest_activation_security_lowercase'] = 'Au moins une lettre minuscule';
$string['commerce_guest_activation_security_uppercase'] = 'Au moins une lettre majuscule';
$string['commerce_guest_activation_security_digit'] = 'Au moins un chiffre';
$string['commerce_guest_activation_security_special'] = 'Au moins un caractère spécial';
$string['commerce_guest_activation_secure_link_title'] = 'Lien sécurisé';
$string['commerce_guest_activation_secure_link'] = 'Ce lien est personnel et à usage unique.';
$string['commerce_guest_activation_secure_link_expiry'] = 'Ce lien est personnel, à usage unique et expirera le {$a}.';
$string['commerce_guest_activation_email_cta'] = 'Définir mon mot de passe';

$string['commerce_guest_activation_email_expiry'] = 'Ce lien est valable jusqu’au {$a}.';

$string['commerce_mail_type_account_activation'] = 'Bienvenue / activation';
$string['commerce_product_covers_title'] = 'Visuels du produit';
$string['commerce_product_covers_help'] = 'Chargez un visuel adapté à chaque usage. Lorsqu’un visuel manque, Commerce utilise automatiquement le visuel Boutique puis l’ancien visuel principal.';
$string['commerce_product_cover_fallback_notice'] = 'Aucun visuel spécifique. Le fallback automatique sera utilisé.';
$string['commerce_product_cover_role_storefront'] = 'Boutique';
$string['commerce_product_cover_role_storefront_help'] = 'Carte produit dans la Boutique. Format conseillé : 4:3 ou portrait court.';
$string['commerce_product_cover_role_product'] = 'Fiche produit';
$string['commerce_product_cover_role_product_help'] = 'Visuel principal de la page détaillée. Format conseillé : 16:9.';
$string['commerce_product_cover_role_recommendation'] = 'Recommandations';
$string['commerce_product_cover_role_recommendation_help'] = 'Carte compacte dans Mes Cours. Format conseillé : 4:3.';
$string['commerce_product_cover_role_resources'] = 'Mes ressources';
$string['commerce_product_cover_role_resources_help'] = 'Bibliothèque des ressources numériques. Format conseillé : 3:4.';
$string['commerce_product_cover_role_checkout'] = 'Panier et checkout';
$string['commerce_product_cover_role_checkout_help'] = 'Miniature compacte du récapitulatif de commande.';
$string['commerce_product_cover_role_email'] = 'E-mails';
$string['commerce_product_cover_role_email_help'] = 'Visuel utilisable dans les e-mails transactionnels.';
$string['commerce_product_cover_role_social'] = 'Partage social';
$string['commerce_product_cover_role_social_help'] = 'Aperçu Open Graph. Format conseillé : 1200 × 630.';

$string['commerce_guest_activation_protected_title'] = 'Vos informations sont protégées';
$string['commerce_guest_activation_protected_text'] = 'Votre achat est confirmé. Choisissez maintenant un mot de passe pour sécuriser votre compte CampusFR. Une fois cette étape terminée, vous serez automatiquement connecté et pourrez accéder immédiatement à vos cours, ressources et achats.';
$string['commerce_guest_activation_show_password'] = 'Afficher le mot de passe';
$string['commerce_guest_activation_hide_password'] = 'Masquer le mot de passe';

$string['settings_trial_conversion_product_sku'] = 'Produit Native de conversion Trial';
$string['settings_trial_conversion_product_sku_desc'] = 'SKU du produit Commerce Native proposé en priorité aux utilisateurs en essai. Laisser vide pour utiliser le plan cible configuré ou la Boutique.';
$string['settings_trial_conversion_plan_id'] = 'Plan Legacy cible de conversion Trial';
$string['settings_trial_conversion_plan_id_desc'] = 'Identifiant du plan payant Legacy dont le produit Native mappé doit être proposé. Le SKU explicite reste prioritaire.';
$string['commerce_trial_conversion_bridge_notice'] = 'Votre remise Trial de −{$a->percent}% est active jusqu’au {$a->deadline}. Elle sera appliquée automatiquement dans votre panier.';

$string['commerce_trial_conversion_label'] = 'Offre Trial';
$string['commerce_trial_conversion_adjustment'] = 'Remise de conversion Trial';
$string['commerce_cart_message_trial_conversion_not_eligible'] = 'Cette offre Trial n’est plus disponible pour ce produit. Le panier n’a pas été modifié.';

$string['unlock_subscriber_button_single'] = 'Acheter le cours';
$string['unlock_grammar_button_single'] = 'Acheter le cours';
$string['unlock_full_button_single'] = 'Acheter le cours';
$string['commerce_trial_price_explanation'] = 'Votre accès d’essai vous permet d’économiser {$a->saving}. Offre valable jusqu’au {$a->deadline}.';
$string['unlock_course_title'] = 'Accès au cours requis';
$string['unlock_course_text'] = 'Achetez le cours pour débloquer cette activité.';
$string['unlock_course_button'] = 'Acheter le cours';
$string['commerce_trial_storefront_badge'] = 'Offre spéciale Essai';
$string['commerce_trial_storefront_discount'] = 'Essai −{$a}%';
$string['commerce_trial_storefront_explanation'] = 'Cette remise est réservée aux membres actuellement en période d’essai.';
$string['commerce_trial_storefront_product_promotion'] = 'Promotion';
$string['commerce_trial_storefront_final_price'] = 'Votre prix Essai';
$string['commerce_trial_storefront_deadline'] = 'Offre Essai valable jusqu’au {$a}.';
$string['commerce_cart_list_total'] = 'Prix avant réductions';
$string['commerce_cart_product_promotions_total'] = 'Promotions produits';
$string['commerce_cart_total_reductions'] = 'Total des remises';
$string['commerce_trial_storefront_initial_price'] = 'Prix initial';
$string['commerce_cart_badge_course'] = 'Cours';
$string['commerce_cart_badge_digital'] = 'Ressource numérique';
$string['commerce_cart_badge_bundle'] = 'Pack';
$string['commerce_cart_badge_trial'] = 'Offre Essai';
$string['commerce_cart_badge_upgrade'] = 'Mise à niveau';
$string['commerce_cart_badge_product'] = 'Produit';
$string['commerce_purchase_pricing_section'] = 'Tarification et remises';
$string['commerce_purchase_native_payment_attempt'] = 'Tentative de paiement Native';
$string['commerce_pricing_initial_product'] = 'Prix initial du produit';
$string['commerce_pricing_owned_credit'] = 'Crédit {$a}';
$string['commerce_pricing_upgrade_price'] = 'Prix Upgrade';
$string['commerce_pricing_final_price'] = 'Votre prix final';
$string['commerce_pricing_details'] = 'Détails du prix';
$string['commerce_pricing_initial_promotion'] = 'Promotion initiale';
$string['commerce_pricing_upgrade_offer'] = 'Offre de mise à niveau';
$string['commerce_pricing_you_save'] = 'Vous économisez';
$string['commerce_invoice_owned_credit'] = 'Crédit produit déjà acquis';
$string['commerce_invoice_other_discount'] = 'Autres remises';
$string['commerce_invoice_item_paid_price'] = 'Prix payé pour cet article';
$string['commerce_storefront_hide_owned'] = 'Masquer les produits déjà acquis';
$string['commerce_storefront_hide_owned_help'] = 'Désactivez ce filtre pour revoir l’ensemble du catalogue.';
$string['commerce_storefront_price_standard'] = 'Prix';
$string['commerce_storefront_price_promotional'] = 'Prix promotionnel';
$string['commerce_storefront_price_trial'] = 'Votre prix Essai';
$string['commerce_storefront_price_upgrade'] = 'Prix Upgrade';
$string['commerce_storefront_price_discovery'] = 'Prix découverte';
$string['commerce_storefront_upgrade_offer_badge'] = 'Offre spéciale Upgrade';
$string['commerce_storefront_upgrade_owned_explanation'] = 'Vous avez déjà accès à {$a}. Le montant déjà payé est déduit du prix.';
$string['commerce_pricing_initial_promotion_percent'] = 'Promotion initiale −{$a} %';
$string['commerce_cart_trial_discount_total'] = 'Remise Essai';
$string['commerce_cart_upgrade_credit_total'] = 'Crédit Upgrade';
$string['commerce_checkout_print_summary'] = 'Imprimer le résumé';
$string['commerce_cart_print_detailed'] = 'Panier détaillé imprimable';
$string['commerce_cart_print_detailed_subtitle'] = 'Détail des articles, promotions et crédits appliqués avant paiement.';
$string['commerce_cart_print_generated'] = 'Document généré le {$a}';
$string['commerce_storefront_section_hero'] = 'Hero éditorial';
$string['commerce_storefront_section_image_text'] = 'Image + texte';
$string['commerce_storefront_section_video'] = 'Vidéo';
$string['commerce_storefront_section_program'] = 'Programme';
$string['commerce_storefront_section_instructor'] = 'Enseignant';
$string['commerce_storefront_section_testimonials'] = 'Témoignages';
$string['commerce_storefront_section_gallery'] = 'Galerie';
$string['commerce_storefront_section_id'] = 'Identifiant technique';
$string['commerce_storefront_section_order'] = 'Ordre';
$string['commerce_storefront_section_style'] = 'Style';
$string['commerce_storefront_section_visible'] = 'Section visible';
$string['commerce_storefront_section_style_default'] = 'Standard';
$string['commerce_storefront_section_style_soft'] = 'Doux';
$string['commerce_storefront_section_style_accent'] = 'Accent CampusFR';
$string['commerce_storefront_section_style_contrast'] = 'Contraste';
$string['commerce_storefront_section_style_boxed'] = 'Encadré';
$string['commerce_storefront_section_style_full_width'] = 'Pleine largeur';
$string['commerce_product_visual_format_square'] = 'Visuel carré — 1:1';
$string['commerce_product_visual_format_square_help'] = 'Checkout, confirmation, petites vignettes et CRM. Taille recommandée : {$a}.';
$string['commerce_product_visual_format_landscape'] = 'Visuel paysage — 4:3';
$string['commerce_product_visual_format_landscape_help'] = 'Boutique, recommandations et cartes produit. Taille recommandée : {$a}.';
$string['commerce_product_visual_format_wide'] = 'Visuel large — 16:9';
$string['commerce_product_visual_format_wide_help'] = 'Hero, vidéo, partage social et Open Graph. Taille recommandée : {$a}.';
$string['commerce_product_visual_format_portrait'] = 'Visuel portrait — 4:5';
$string['commerce_product_visual_format_portrait_help'] = 'Panier, ressources numériques et cartes verticales. Taille recommandée : {$a}.';
$string['commerce_product_visual_ratio_ok'] = 'Ratio conforme : {$a}.';
$string['commerce_product_visual_ratio_warning'] = 'Le fichier sera accepté, mais son ratio diffère du format {$a}. Il sera recadré à l’affichage.';
$string['commerce_storefront_seo_title'] = 'SEO et partage social';
$string['commerce_storefront_seo_help'] = 'Ces informations sont localisées. Le visuel social provient automatiquement du master 16:9.';
$string['commerce_storefront_seo_page_title'] = 'Titre SEO';
$string['commerce_storefront_seo_description'] = 'Meta description';
$string['commerce_storefront_seo_description_help'] = 'Recommandation : environ 150 à 160 caractères. Le texte est nettoyé et limité à 320 caractères.';
$string['commerce_storefront_view_my_products'] = 'Voir mes produits';
$string['commerce_product_visual_status_ok'] = 'OK';
$string['commerce_product_visual_status_fallback'] = 'Fallback';
$string['commerce_product_visual_status_missing'] = 'Absent';
$string['commerce_product_visual_preview_alt'] = 'Aperçu du visuel de {$a}';
$string['commerce_product_visual_fallback_source'] = 'Fallback : {$a}';
$string['commerce_product_visual_metadata_dimensions'] = 'Dimensions';
$string['commerce_product_visual_metadata_ratio'] = 'Ratio réel / cible';
$string['commerce_product_visual_metadata_weight'] = 'Poids';
$string['commerce_product_visual_metadata_file'] = 'Fichier';
$string['commerce_product_visual_fallback_help'] = 'Ce format n’a pas encore de master dédié. L’aperçu montre le fallback actuellement utilisé et son recadrage dans le ratio cible.';
$string['commerce_product_visual_missing_help'] = 'Aucun master ni fallback n’est disponible. Le placeholder du type de produit sera utilisé.';
$string['commerce_product_visual_context_preview_title'] = 'Aperçu simulé dans les surfaces Commerce';
$string['commerce_product_visual_context_preview_help'] = 'Ces maquettes utilisent les mêmes classes CSS que les vraies pages. Elles montrent le master, le fallback ou le placeholder actuellement résolu.';
$string['commerce_product_visual_context_preview_badge'] = 'CSS réel';
$string['commerce_product_visual_context_preview_description'] = 'Exemple de présentation du produit dans son contexte réel.';
$string['commerce_product_visual_context_boutique'] = 'Boutique';
$string['commerce_product_visual_context_storefront'] = 'Storefront';
$string['commerce_product_visual_context_checkout'] = 'Checkout';
$string['commerce_product_visual_context_resources'] = 'Mes ressources';
$string['commerce_product_visual_context_available'] = 'Disponible';
$string['commerce_product_visual_save_format'] = 'Enregistrer ce format';
$string['commerce_product_visual_no_file_selected'] = 'Sélectionnez une image avant d’enregistrer ce format.';
$string['commerce_storefront_rich_text_editor_help'] = 'Éditeur TinyMCE actif : vous pouvez insérer des images, vidéos, fichiers, liens et médias H5P. Les fichiers sont enregistrés dans Moodle avec ce bloc.';
$string['commerce_storefront_section_h5p'] = 'H5P';
$string['commerce_storefront_image_settings'] = 'Image + texte';
$string['commerce_storefront_image_upload'] = 'Image Moodle';
$string['commerce_storefront_image_position'] = 'Position de l’image';
$string['commerce_storefront_column_ratio'] = 'Proportions';
$string['commerce_storefront_video_settings'] = 'Vidéo';
$string['commerce_storefront_video_source'] = 'Source';
$string['commerce_storefront_video_upload'] = 'Fichier Moodle';
$string['commerce_storefront_video_file'] = 'Fichier vidéo';
$string['commerce_storefront_video_ratio'] = 'Ratio';
$string['commerce_storefront_video_poster'] = 'Image poster';
$string['commerce_storefront_h5p_settings'] = 'Contenu H5P';
$string['commerce_storefront_h5p_content'] = 'Contenu de la Banque Moodle (optionnel)';
$string['commerce_storefront_h5p_height'] = 'Hauteur minimale';
$string['commerce_storefront_h5p_help'] = 'Priorité : fichier .h5p téléversé, puis contenu choisi dans la Banque Moodle, puis URL auxiliaire.';
$string['commerce_storefront_h5p_none'] = 'Aucun contenu H5P sélectionné';
$string['commerce_storefront_h5p_missing'] = 'Aucun contenu H5P valide n’est configuré.';
$string['commerce_storefront_builder_sections'] = 'Structure de la page';
$string['commerce_storefront_builder_sections_help'] = 'Ajoutez et organisez les blocs éditoriaux. Le bloc Commerce reste protégé.';
$string['commerce_storefront_builder_add'] = 'Type de section';
$string['commerce_storefront_builder_add_button'] = 'Ajouter une section';
$string['commerce_storefront_builder_untitled'] = 'Sans titre';
$string['commerce_storefront_builder_ready'] = 'Prête';
$string['commerce_storefront_builder_incomplete'] = 'Incomplète';
$string['commerce_storefront_builder_empty'] = 'Aucune section éditoriale. Ajoutez votre premier bloc.';
$string['commerce_storefront_builder_action_first'] = 'Déplacer au début';
$string['commerce_storefront_builder_action_up'] = 'Monter';
$string['commerce_storefront_builder_action_down'] = 'Descendre';
$string['commerce_storefront_builder_action_last'] = 'Déplacer à la fin';
$string['commerce_storefront_builder_action_toggle'] = 'Afficher ou masquer';
$string['commerce_storefront_builder_action_duplicate'] = 'Dupliquer';
$string['commerce_storefront_builder_action_delete'] = 'Supprimer';
$string['commerce_storefront_builder_drag_help'] = 'Faites glisser les blocs avec la poignée. Au clavier, utilisez Alt + flèche haut ou bas, puis enregistrez la page.';
$string['commerce_storefront_builder_drag_handle'] = 'Déplacer le bloc {$a}';
$string['commerce_storefront_repository_picker_help'] = 'Les boutons Image, Média, Lien et H5P ouvrent le sélecteur Moodle : upload depuis l’ordinateur et repositories autorisés.';
$string['commerce_storefront_h5p_upload'] = 'Téléverser un fichier H5P';
$string['commerce_storefront_h5p_bank_empty'] = 'Aucun contenu H5P n’est actuellement enregistré dans la Banque de contenus Moodle. Vous pouvez téléverser directement un fichier .h5p ci-dessus.';
$string['commerce_storefront_h5p_open_bank'] = 'Ouvrir la Banque de contenus';

// Visual Page Composer.
$string['commerce_storefront_composer_layout'] = 'Mise en page visuelle';
$string['commerce_storefront_composer_layout_help'] = 'Les blocs partageant le même identifiant de ligne peuvent être répartis dans plusieurs colonnes. Sur mobile, les colonnes sont automatiquement empilées.';
$string['commerce_storefront_composer_columns'] = 'Colonnes';
$string['commerce_storefront_composer_column'] = 'Position dans la ligne';
$string['commerce_storefront_composer_ratio'] = 'Ratio des colonnes';
$string['commerce_storefront_composer_row'] = 'Identifiant de ligne';
$string['commerce_storefront_composer_width'] = 'Largeur';
$string['commerce_storefront_composer_width_contained'] = 'Contenue';
$string['commerce_storefront_composer_width_wide'] = 'Large';
$string['commerce_storefront_composer_width_full'] = 'Pleine largeur';
$string['commerce_storefront_composer_background'] = 'Fond';
$string['commerce_storefront_composer_background_default'] = 'Par défaut';
$string['commerce_storefront_composer_background_soft'] = 'Doux';
$string['commerce_storefront_composer_background_accent'] = 'Accent';
$string['commerce_storefront_composer_background_contrast'] = 'Contraste';
$string['commerce_storefront_composer_background_transparent'] = 'Transparent';
$string['commerce_storefront_composer_spacing'] = 'Espacement vertical';
$string['commerce_storefront_composer_spacing_none'] = 'Aucun';
$string['commerce_storefront_composer_spacing_small'] = 'Petit';
$string['commerce_storefront_composer_spacing_medium'] = 'Moyen';
$string['commerce_storefront_composer_spacing_large'] = 'Grand';
$string['commerce_storefront_composer_alignment'] = 'Alignement vertical';
$string['commerce_storefront_composer_alignment_start'] = 'Haut';
$string['commerce_storefront_composer_alignment_center'] = 'Centre';
$string['commerce_storefront_composer_alignment_end'] = 'Bas';
$string['commerce_storefront_composer_alignment_stretch'] = 'Étiré';
$string['commerce_storefront_responsive_preview'] = 'Prévisualisation responsive';
$string['commerce_storefront_responsive_preview_help'] = 'Simulez la largeur d’affichage du constructeur sans quitter la page.';
$string['commerce_storefront_preview_desktop'] = 'Ordinateur';
$string['commerce_storefront_preview_tablet'] = 'Tablette';
$string['commerce_storefront_preview_mobile'] = 'Mobile';
$string['commerce_storefront_composer_templates'] = 'Modèles de composition';
$string['commerce_storefront_composer_templates_help'] = 'Ajoutez une structure prête à personnaliser. Les sections existantes ne sont jamais remplacées.';
$string['commerce_storefront_composer_template'] = 'Modèle à insérer';
$string['commerce_storefront_composer_template_insert'] = 'Insérer le modèle';
$string['commerce_storefront_composer_template_sales'] = 'Page de vente';
$string['commerce_storefront_composer_template_course'] = 'Cours';
$string['commerce_storefront_composer_template_digital'] = 'Produit digital';
$string['commerce_storefront_composer_template_bundle'] = 'Bundle';

$string['commerce_storefront_section_timeline'] = 'Timeline';
$string['commerce_storefront_section_comparison'] = 'Comparatif';
$string['commerce_storefront_section_accordion'] = 'Accordéon';
$string['commerce_storefront_section_style_glass'] = 'Verre dépoli';
$string['commerce_storefront_section_style_gradient'] = 'Dégradé premium';
$string['commerce_storefront_section_style_minimal'] = 'Minimal';
$string['commerce_storefront_premium_presentation'] = 'Présentation premium';
$string['commerce_storefront_premium_presentation_default'] = 'Standard';
$string['commerce_storefront_premium_presentation_split'] = 'Composition scindée';
$string['commerce_storefront_premium_presentation_overlay'] = 'Superposition immersive';
$string['commerce_storefront_premium_presentation_cards'] = 'Cartes premium';
$string['commerce_storefront_premium_presentation_carousel'] = 'Carrousel horizontal';
$string['commerce_storefront_premium_presentation_masonry'] = 'Galerie masonry';
$string['commerce_storefront_premium_presentation_timeline'] = 'Timeline';
$string['commerce_storefront_premium_presentation_comparison'] = 'Comparatif';
$string['commerce_storefront_premium_presentation_premium'] = 'Premium CampusFR';
$string['commerce_storefront_premium_presentation_statement'] = 'Accroche / transition';
$string['commerce_storefront_premium_presentation_feature'] = 'Mise en avant produit';
$string['commerce_storefront_premium_presentation_commerce'] = 'Commerce premium';
$string['commerce_storefront_premium_animation'] = 'Animation d’entrée';
$string['commerce_storefront_premium_animation_none'] = 'Aucune';
$string['commerce_storefront_premium_animation_fade'] = 'Fondu';
$string['commerce_storefront_premium_animation_slide_up'] = 'Glissement vers le haut';
$string['commerce_storefront_premium_animation_zoom'] = 'Zoom léger';

$string['commerce_storefront_shell_title'] = 'Disposition globale de la Storefront';
$string['commerce_storefront_commerce_position'] = 'Position du bloc Commerce';
$string['commerce_storefront_commerce_position_hero'] = 'Intégré au Hero';
$string['commerce_storefront_commerce_position_below'] = 'Sous le Hero';
$string['commerce_storefront_commerce_position_sidebar'] = 'Sidebar sticky';
$string['commerce_storefront_commerce_position_intro'] = 'Après l’introduction';
$string['commerce_storefront_commerce_position_bottom'] = 'Bas de page';
$string['commerce_storefront_shell_mode'] = 'Enveloppe Moodle';
$string['commerce_storefront_shell_standard'] = 'Edly standard';
$string['commerce_storefront_shell_fullwidth'] = 'Edly pleine largeur';
$string['commerce_storefront_shell_landing'] = 'Landing page';
$string['commerce_storefront_shell_immersive'] = 'Immersif';
$string['commerce_storefront_layout_visibility'] = 'Visibilité du shell Edly';
$string['commerce_storefront_show_header'] = 'Afficher le header Edly';
$string['commerce_storefront_show_footer'] = 'Afficher le footer Edly';
$string['commerce_storefront_section_save'] = 'Enregistrer ce bloc';
$string['commerce_storefront_section_saved'] = 'Bloc enregistré';
$string['commerce_storefront_section_save_error'] = 'Impossible d’enregistrer ce bloc.';

$string['commerce_storefront_reset_title'] = 'Réinitialiser la Page Boutique';
$string['commerce_storefront_reset_help'] = 'Supprime toute la configuration Storefront et les médias rattachés à ses sections. Le produit, ses prix et ses droits d’accès ne sont pas modifiés.';
$string['commerce_storefront_reset_button'] = 'Supprimer la configuration Storefront';
$string['commerce_storefront_reset_confirm_title'] = 'Supprimer toute la configuration Storefront ?';
$string['commerce_storefront_reset_confirm_help'] = 'Cette action supprime définitivement les sections, les réglages de mise en page, le SEO et les fichiers Storefront de ce produit. Elle ne peut pas être annulée.';
$string['commerce_storefront_reset_confirm_button'] = 'Oui, supprimer la Storefront';
$string['commerce_storefront_reset_success'] = 'La configuration Storefront et ses fichiers ont été supprimés.';
$string['commerce_storefront_package_title'] = 'Transfert de la Page Boutique';
$string['commerce_storefront_package_help'] = 'Exportez ou importez la configuration complète de la Page Boutique, y compris ses médias, dans un fichier .cfrproduct.';
$string['commerce_storefront_package_export'] = 'Exporter la configuration';
$string['commerce_storefront_package_import'] = 'Importer la configuration';
$string['commerce_storefront_package_file'] = 'Fichier .cfrproduct';
$string['commerce_storefront_package_import_success'] = 'La configuration de la Page Boutique a été importée.';
$string['commerce_storefront_package_invalid'] = 'Le fichier Storefront est invalide ou incompatible.';
$string['commerce_storefront_global_zones_title'] = 'Organisation globale de la page';
$string['commerce_storefront_global_zones_help'] = 'Faites glisser les zones pour positionner le bloc Commerce dans le parcours de la page. Alt + flèche haut/bas fonctionne aussi au clavier.';
$string['commerce_storefront_global_zone_hero'] = 'Hero';
$string['commerce_storefront_global_zone_commerce'] = 'Bloc Commerce';
$string['commerce_storefront_global_zone_content'] = 'Contenu éditorial';
$string['commerce_storefront_global_zone_recommendations'] = 'Recommandations';
$string['commerce_storefront_media_audit_title'] = 'Audit médias Storefront';

$string['commerce_storefront_image_position_left'] = 'Gauche';
$string['commerce_storefront_image_position_right'] = 'Droite';

// J9A — Références publiques et navigation des ventes CRM.
$string['commerce_purchase_public_reference'] = 'Numéro de commande';
$string['commerce_purchase_internal_reference'] = 'Référence interne';
$string['commerce_purchase_internal_reference_short'] = 'Interne';
$string['commerce_purchase_open_order_details'] = 'Voir Order Details';

$string['commerce_purchase_download_invoice'] = 'Télécharger la facture';
$string['commerce_purchase_open_mail_journal'] = 'Voir le journal des emails';
$string['commerce_purchase_resend_receipt'] = 'Renvoyer le reçu';
$string['commerce_purchase_resend_receipt_confirm'] = 'Un nouveau reçu sera envoyé à l’adresse email du client et journalisé comme un envoi manuel. Continuer ?';
$string['commerce_purchase_receipt_resent'] = 'Le reçu a été renvoyé avec succès.';
$string['commerce_purchase_receipt_queued'] = 'Le reçu a été placé dans la file d’envoi et sera retenté automatiquement.';
$string['commerce_purchase_receipt_resend_failed'] = 'Le reçu n’a pas pu être renvoyé. Consultez le journal des emails pour le diagnostic.';

// J10A — Hub étudiant Mon Campus.
$string['commerce_customer_hub_title'] = 'Mon Campus';
$string['commerce_customer_hub_eyebrow'] = 'Ton espace personnel';
$string['commerce_customer_hub_welcome'] = 'Bonjour {$a} !';
$string['commerce_customer_hub_intro'] = 'Retrouve ici tes cours, tes ressources, tes achats et ta progression CampusFR.';
$string['commerce_customer_hub_shortcuts'] = 'Raccourcis Mon Campus';
$string['commerce_customer_hub_courses'] = 'Mes cours';
$string['commerce_customer_hub_resources'] = 'Mes ressources';
$string['commerce_customer_hub_purchases'] = 'Mes achats';
$string['commerce_customer_hub_profile'] = 'Mon profil';
$string['commerce_customer_hub_profile_help'] = 'Informations et préférences';
$string['commerce_customer_hub_available'] = 'disponible(s)';
$string['commerce_customer_hub_orders'] = 'commande(s)';
$string['commerce_customer_hub_continue'] = 'Continuer à apprendre';
$string['commerce_customer_hub_view_all'] = 'Voir tout';
$string['commerce_customer_hub_no_courses'] = 'Aucun cours n’est encore disponible dans ton espace.';
$string['commerce_customer_hub_discover'] = 'Découvrir la boutique';
$string['commerce_customer_hub_xp_title'] = 'Ma progression';
$string['commerce_customer_hub_level'] = 'Niveau';
$string['commerce_customer_hub_total_xp'] = 'XP total';
$string['commerce_customer_hub_xp_30d'] = '30 derniers jours';
$string['commerce_customer_hub_xp_ranking'] = 'Classement';
$string['commerce_customer_hub_last_activity'] = 'Dernière activité';
$string['commerce_customer_hub_xp_no_activity'] = 'Aucune activité récente';
$string['commerce_customer_hub_xp_unavailable'] = 'La progression LevelXP sera affichée ici dès qu’elle sera disponible.';

// J10A.1 — Navigation du parcours étudiant.
$string['commerce_customer_hub_shop'] = 'Boutique';
$string['commerce_customer_hub_shop_help'] = 'Découvrir les cours et ressources CampusFR';
$string['commerce_i2_my_campus'] = 'Accéder à Mon Campus';

$string['commerce_routes_product_title'] = 'URL publique du produit';
$string['commerce_routes_product_help'] = 'Définissez un slug mémorisable par langue. Laissez vide pour conserver l’URL technique.';
$string['commerce_routes_slug_fr'] = 'Slug français';
$string['commerce_routes_slug_en'] = 'Slug anglais';
$string['commerce_routes_slug_ru'] = 'Slug russe';
$string['commerce_route_not_found'] = 'Cette page CampusFR est introuvable.';

$string['commerce_storefront_filters_toggle'] = 'Rechercher et filtrer';

$string['commerce_guest_activation_security_match'] = 'Les deux mots de passe sont identiques';


// J12E — Guest Checkout security and account finalisation.
$string['commerce_guest_checkout_other_email'] = 'Essayer avec une autre adresse email';
$string['commerce_guest_checkout_email_valid'] = 'Adresse email valide';
$string['commerce_guest_checkout_email_invalid_live'] = 'Saisissez une adresse email valide.';
$string['commerce_guest_activation_modal_title'] = 'Finalisez votre compte pour accéder à vos cours';
$string['commerce_guest_activation_modal_message'] = 'Votre achat est bien confirmé. Créez maintenant votre mot de passe pour ouvrir vos cours sans passer par une page de connexion inadaptée.';
$string['commerce_guest_activation_modal_courses'] = 'Accéder à vos cours';
$string['commerce_guest_activation_modal_resources'] = 'Retrouver vos ressources et téléchargements';
$string['commerce_guest_activation_modal_orders'] = 'Consulter vos achats et futures commandes';
$string['commerce_guest_activation_modal_primary'] = 'Créer mon compte';
$string['commerce_guest_activation_modal_later'] = 'Plus tard';
$string['commerce_guest_activation_ready_confirmation'] = 'Votre compte CampusFR est maintenant prêt.';

// J12H — Support experience and CRM Inbox.
$string['commerce_support_page_title_generic'] = 'Support CampusFR';
$string['commerce_support_default_subject_generic'] = 'Demande de support CampusFR';
$string['commerce_support_back_to_campus'] = 'Retour à Mon Campus';
$string['commerce_support_gustave_alt'] = 'Gustave, conseiller du support CampusFR';
$string['commerce_support_visual_title'] = 'Besoin d’aide ?';
$string['commerce_support_visual_text'] = 'Notre équipe est là pour vous répondre.';
$string['commerce_support_confirmation_title'] = 'Demande envoyée avec succès !';
$string['commerce_support_confirmation_intro'] = 'Nous avons bien reçu votre demande. L’équipe CampusFR vous répondra dans les meilleurs délais.';
$string['commerce_support_reference'] = 'Référence de la demande';
$string['commerce_support_return_to_campus'] = 'Retour à Mon Campus';
$string['commerce_support_mail_technical_heading'] = 'Informations techniques';
$string['commerce_support_mail_message_heading'] = 'Message du client';
$string['commerce_support_category_account'] = 'Mon compte';
$string['commerce_support_category_technical'] = 'Problème technique';
$string['commerce_support_category_course_question'] = 'Question sur un cours';
$string['commerce_support_status_paid'] = 'Payée';
$string['commerce_support_status_completed'] = 'Terminée';
$string['commerce_support_status_pending'] = 'En attente';
$string['commerce_support_status_failed'] = 'Échouée';
$string['commerce_support_status_cancelled'] = 'Annulée';
$string['commerce_support_status_refunded'] = 'Remboursée';
$string['commerce_support_status_partial'] = 'Partielle';
$string['commerce_support_status_processing'] = 'En cours';
$string['commerce_support_status_succeeded'] = 'Réussie';
$string['commerce_customer_hub_view_profile'] = 'Voir mon profil';
$string['commerce_customer_hub_support'] = 'Support';
$string['commerce_customer_hub_support_help_short'] = 'Besoin d’aide ?';
$string['commerce_storefront_currency_displayed'] = 'Devise';
$string['crm_inbox_direction_incoming'] = 'Reçu';
$string['crm_inbox_direction_outgoing'] = 'Envoyé';

// Showroom Commerce.
$string['commerce_showroom_not_found'] = 'Cette page de présentation est introuvable.';
$string['commerce_showroom_third_group_verbs_title'] = 'Les verbes du 3e groupe, enfin maîtrisés';
$string['commerce_showroom_third_group_verbs_description'] = 'Découvrez le futur espace CampusFR consacré aux verbes du troisième groupe : un cours immersif, une ressource PDF et une offre complète.';
$string['commerce_showroom_eyebrow'] = 'Nouveau sur CampusFR';
$string['commerce_showroom_foundation_note'] = 'Socle technique J13B — le design final et les contenus marketing arriveront en J13D.';
$string['commerce_showroom_offers_heading'] = 'Choisissez votre formule';
$string['commerce_showroom_offer_course'] = 'Cours interactif';
$string['commerce_showroom_offer_pdf'] = 'Guide PDF';
$string['commerce_showroom_offer_bundle'] = 'Pack Cours + PDF';
$string['commerce_showroom_offer_pending'] = 'Ce produit sera relié au Showroom dès sa création dans le catalogue Commerce.';
$string['commerce_showroom_price_pending'] = 'Bientôt disponible';
$string['commerce_showroom_buy_now'] = 'Acheter maintenant';
$string['commerce_showroom_view_details'] = 'Voir les détails';
$string['commerce_showroom_back_to_shop'] = 'Voir toute la boutique';

$string['commerce_showroom_owned_access'] = 'Accéder au produit';

// J13D — Showroom Verbes du 3e groupe.
$string['commerce_showroom_hero_cta'] = 'Choisir mon ascension';
$string['commerce_showroom_hero_secondary_cta'] = 'Voir la présentation';
$string['commerce_showroom_hero_proof'] = 'Accès immédiat après paiement · conçu pour pratiquer jusqu’à l’automatisme';
$string['commerce_showroom_problem_eyebrow'] = 'Pourquoi est-ce si difficile ?';
$string['commerce_showroom_problem_title'] = 'Les verbes du 3e groupe semblent compliqués pour de mauvaises raisons';
$string['commerce_showroom_problem_description'] = 'Le problème n’est pas votre mémoire : ce sont les listes isolées, les tableaux sans contexte et le manque de répétition guidée.';
$string['commerce_showroom_problem_1_title'] = 'Aucune règle unique';
$string['commerce_showroom_problem_1_text'] = 'Les formes changent d’un verbe à l’autre et les modèles se mélangent.';
$string['commerce_showroom_problem_2_title'] = 'La bonne forme disparaît';
$string['commerce_showroom_problem_2_text'] = 'Vous connaissez le verbe, mais la conjugaison ne vient pas au bon moment.';
$string['commerce_showroom_problem_3_title'] = 'Trop de tableaux';
$string['commerce_showroom_problem_3_text'] = 'Lire une conjugaison ne suffit pas pour la rendre disponible à l’oral.';
$string['commerce_showroom_problem_4_title'] = 'Pas assez de pratique';
$string['commerce_showroom_problem_4_text'] = 'Sans réactivation régulière, les formes apprises s’effacent rapidement.';
$string['commerce_showroom_method_title'] = 'Apprendre les verbes comme on apprend à conduire';
$string['commerce_showroom_method_description'] = 'On observe, on pratique, puis on recommence jusqu’à ce que le bon réflexe arrive sans effort.';
$string['commerce_showroom_method_1_title'] = 'Comprendre le modèle';
$string['commerce_showroom_method_1_text'] = 'Vous repérez les formes utiles et les régularités sans vous noyer dans la théorie.';
$string['commerce_showroom_method_2_title'] = 'Avancer étape par étape';
$string['commerce_showroom_method_2_text'] = 'Chaque ascension introduit six verbes et une progression claire.';
$string['commerce_showroom_method_3_title'] = 'Créer l’automatisme';
$string['commerce_showroom_method_3_text'] = 'Plus de dix formats d’exercices réactivent les mêmes formes sous des angles différents.';
$string['commerce_showroom_video_title'] = 'Que vous réserve le chemin vers le sommet des verbes du 3e groupe ?';
$string['commerce_showroom_video_description'] = 'Regardez une courte présentation du trainer et découvrez comment l’apprentissage des verbes se transforme en une véritable ascension vers le sommet du Mont-Blanc.';
$string['commerce_showroom_video_placeholder'] = 'Vidéo de présentation bientôt disponible';
$string['commerce_showroom_content_eyebrow'] = 'Un vrai entraînement, pas une simple liste';
$string['commerce_showroom_content_title'] = 'Tous les verbes essentiels dans une seule aventure';
$string['commerce_showroom_content_description'] = 'Le parcours vous fait gravir 30 étapes, du premier camp jusqu’au sommet, avec Gustave comme guide.';
$string['commerce_showroom_stat_1_title'] = '30 étapes';
$string['commerce_showroom_stat_1_text'] = 'Une progression lisible et motivante.';
$string['commerce_showroom_stat_2_title'] = '180 verbes';
$string['commerce_showroom_stat_2_text'] = 'Les verbes fréquents et leurs familles.';
$string['commerce_showroom_stat_3_title'] = 'Audio natif';
$string['commerce_showroom_stat_3_text'] = 'Écoutez les formes avant de les produire.';
$string['commerce_showroom_stat_4_title'] = 'Répétition intelligente';
$string['commerce_showroom_stat_4_text'] = 'Les mêmes formes reviennent au bon moment.';
$string['commerce_showroom_stat_5_title'] = 'Tests et récompenses';
$string['commerce_showroom_stat_5_text'] = 'Validez chaque étape et suivez vos progrès.';
$string['commerce_showroom_stat_6_title'] = 'Univers alpin';
$string['commerce_showroom_stat_6_text'] = 'Une aventure visuelle autour du Mont-Blanc.';
$string['commerce_showroom_journey_title'] = 'Comment se déroule chaque étape ?';
$string['commerce_showroom_journey_description'] = 'Chaque groupe de verbes suit le même rituel pour rendre la progression prévisible et efficace.';
$string['commerce_showroom_journey_1_title'] = 'Découvrir le sens';
$string['commerce_showroom_journey_1_text'] = 'Comprendre rapidement le verbe et ses usages les plus utiles.';
$string['commerce_showroom_journey_2_title'] = 'Écouter les formes';
$string['commerce_showroom_journey_2_text'] = 'Associer la conjugaison à une prononciation correcte.';
$string['commerce_showroom_journey_3_title'] = 'Reconstruire';
$string['commerce_showroom_journey_3_text'] = 'Assembler les formes et repérer les régularités.';
$string['commerce_showroom_journey_4_title'] = 'Produire';
$string['commerce_showroom_journey_4_text'] = 'Compléter, écrire et choisir la bonne forme en contexte.';
$string['commerce_showroom_journey_5_title'] = 'Valider';
$string['commerce_showroom_journey_5_text'] = 'Passer un quiz final et consolider les points faibles.';
$string['commerce_showroom_journey_6_title'] = 'Continuer l’ascension';
$string['commerce_showroom_journey_6_text'] = 'Débloquer l’étape suivante et avancer vers le sommet.';
$string['commerce_showroom_exercises_title'] = 'Plus de 10 types d’exercices';
$string['commerce_showroom_exercises_description'] =
    'Chaque exercice sollicite la mémoire différemment, c’est pourquoi vous entraînez chaque verbe de plusieurs façons.';
$string['commerce_showroom_exercise_1_title'] = 'Glisser-déposer';
$string['commerce_showroom_exercise_1_text'] = 'Associer les pronoms et les formes.';
$string['commerce_showroom_exercise_2_title'] = 'Choix multiple';
$string['commerce_showroom_exercise_2_text'] = 'Identifier rapidement la bonne réponse.';
$string['commerce_showroom_exercise_3_title'] = 'Vrai ou faux';
$string['commerce_showroom_exercise_3_text'] = 'Détecter les formes incorrectes.';
$string['commerce_showroom_exercise_4_title'] = 'Repérage';
$string['commerce_showroom_exercise_4_text'] = 'Trouver la forme dans une phrase.';
$string['commerce_showroom_exercise_5_title'] = 'Assemblage';
$string['commerce_showroom_exercise_5_text'] = 'Reconstruire le verbe lettre par lettre.';
$string['commerce_showroom_exercise_6_title'] = 'Texte à trous';
$string['commerce_showroom_exercise_6_text'] = 'Produire la forme attendue en contexte.';
$string['commerce_showroom_exercise_7_title'] = 'Dictée audio';
$string['commerce_showroom_exercise_7_text'] = 'Écrire ce que vous entendez.';
$string['commerce_showroom_exercise_8_title'] = 'Traduction ciblée';
$string['commerce_showroom_exercise_8_text'] = 'Retrouver l’infinitif français.';
$string['commerce_showroom_exercise_9_title'] = 'Réponse rapide';
$string['commerce_showroom_exercise_9_text'] = 'Faire apparaître la forme sans hésitation.';
$string['commerce_showroom_exercise_10_title'] = 'Quiz final';
$string['commerce_showroom_exercise_10_text'] = 'Valider l’étape avant de continuer.';
$string['commerce_showroom_offers_description'] = 'Choisissez le support qui correspond à votre façon d’apprendre. Le pack complet réunit la pratique interactive et la référence PDF.';
$string['commerce_showroom_offer_featured'] = 'Offre complète';
$string['commerce_showroom_offer_course_feature_1'] = '30 étapes interactives';
$string['commerce_showroom_offer_course_feature_2'] = '180 verbes du 3e groupe';
$string['commerce_showroom_offer_course_feature_3'] = 'Audio, quiz et répétitions';
$string['commerce_showroom_offer_course_feature_4'] = 'Progression et récompenses';
$string['commerce_showroom_offer_pdf_feature_1'] = 'Guide clair et structuré';
$string['commerce_showroom_offer_pdf_feature_2'] = 'Tableaux et familles de verbes';
$string['commerce_showroom_offer_pdf_feature_3'] = 'Consultation hors ligne';
$string['commerce_showroom_offer_pdf_feature_4'] = 'Accès immédiat au téléchargement';
$string['commerce_showroom_offer_bundle_feature_1'] = 'Cours interactif complet';
$string['commerce_showroom_offer_bundle_feature_2'] = 'Guide PDF inclus';
$string['commerce_showroom_offer_bundle_feature_3'] = 'Meilleure valeur';
$string['commerce_showroom_offer_bundle_feature_4'] = 'Tout pour réviser et pratiquer';
$string['commerce_showroom_bonus_heading'] = 'Complétez votre boîte à outils';
$string['commerce_showroom_bonus_text'] = 'Retrouvez aussi les cartes CampusFR consacrées aux verbes du 3e groupe pour réviser partout.';
$string['commerce_showroom_bonus_cta'] = 'Découvrir les autres ressources';
$string['commerce_showroom_faq_heading'] = 'Questions fréquentes';
$string['commerce_showroom_faq_1_q'] = 'À quel niveau s’adresse le trainer ?';
$string['commerce_showroom_faq_1_a'] = 'Le trainer convient à tous les niveaux. Si vous commencez tout juste le français, il vous aidera à construire des bases solides. Si vous maîtrisez déjà la langue, vous pourrez structurer vos connaissances et consolider les verbes rencontrés au niveau B1 et au-delà.';
$string['commerce_showroom_faq_2_q'] = 'Combien de temps faut-il prévoir ?';
$string['commerce_showroom_faq_2_a'] = 'Chacun avance à son rythme. Tout dépend du vôtre. Vous pouvez faire une session en une seule fois ou la répartir sur plusieurs séances. Inutile de vous presser ou de suivre un planning rigide : l’essentiel est de pratiquer régulièrement. C’est cette régularité qui permet d’automatiser les verbes.';
$string['commerce_showroom_faq_3_q'] = 'Puis-je refaire les exercices ?';
$string['commerce_showroom_faq_3_a'] = 'Oui. Vous pouvez refaire tous les exercices autant de fois que nécessaire. C’est précisément la répétition qui permet d’automatiser les formes verbales.';
$string['commerce_showroom_faq_4_q'] = 'Le trainer fonctionne-t-il sur téléphone ?';
$string['commerce_showroom_faq_4_a'] = 'Oui. Le trainer est entièrement adapté aux ordinateurs, tablettes et smartphones. Vous pouvez vous entraîner chez vous, en déplacement ou dès que vous avez un moment de libre.';
$string['commerce_showroom_faq_5_q'] = 'Que contiennent les cartes électroniques ?';
$string['commerce_showroom_faq_5_a'] = '178 cartes regroupant tous les verbes du 3e groupe du français moderne. Chaque carte comprend la traduction, toutes les formes du présent, le participe passé, le radical du futur simple et un audio enregistré par un locuteur natif. Vous pouvez les utiliser en version numérique ou les imprimer.';
$string['commerce_showroom_faq_6_q'] = 'Quelle est la différence entre le trainer et le pack complet ?';
$string['commerce_showroom_faq_6_a'] = 'Le trainer vous aide à automatiser les verbes grâce à la pratique interactive. Les cartes complètent l’apprentissage : elles permettent de retrouver rapidement une conjugaison, de garder toutes les formes sous la main et de réviser même sans connexion Internet. Le pack complet réunit les deux formats pour vous permettre à la fois de vous entraîner et de réviser facilement à tout moment.';
$string['commerce_showroom_faq_7_q'] = 'Comment obtenir l’accès après l’achat ?';
$string['commerce_showroom_faq_7_a'] = 'Juste après le paiement, vous recevrez par e-mail toutes les informations nécessaires pour accéder au contenu. Si vous avez déjà un compte sur la plateforme CampusFR, vous recevrez également une confirmation d’achat et le trainer apparaîtra automatiquement dans la rubrique « Mes cours ». Aucune action supplémentaire n’est nécessaire : vous pouvez commencer immédiatement.';
$string['commerce_showroom_final_eyebrow'] = 'Le sommet vous attend';
$string['commerce_showroom_final_title'] = 'Prêt à rendre les verbes du 3e groupe automatiques ?';
$string['commerce_showroom_final_text'] = 'Choisissez votre formule, commencez la première étape et avancez jusqu’au sommet avec Gustave.';
$string['commerce_showroom_final_cta'] = 'Commencer maintenant';
$string['commerce_storefront_showroom_media_title'] = 'Showroom';
$string['commerce_storefront_showroom_media_help'] = 'Associez ce produit à une page Showroom et ajoutez un visuel marketing spécifique. Ce visuel est prioritaire dans les cartes du Showroom.';
$string['commerce_storefront_showroom_key'] = 'Showroom associé';
$string['commerce_storefront_showroom_image'] = 'Visuel Showroom';
$string['commerce_storefront_showroom_alt'] = 'Texte alternatif du visuel';
$string['commerce_storefront_showroom_link'] = 'Découvrir la présentation complète';

// J13F1 — Hero premium du showroom Verbes du 3e groupe.
$string['commerce_showroom_hero_expedition'] = 'Expédition Mont-Blanc';
$string['commerce_showroom_hero_stage'] = 'Étape 0 / 30';
$string['commerce_showroom_hero_stat_verbs'] = 'verbes à automatiser';
$string['commerce_showroom_hero_stat_stages'] = 'étapes progressives';
$string['commerce_showroom_hero_stat_exercises'] = 'exercices et défis';
$string['commerce_showroom_hero_stat_lifetime_value'] = 'Immédiat';
$string['commerce_showroom_hero_stat_lifetime'] = 'après votre achat';
$string['commerce_showroom_hero_summary'] = 'Plus de 4 000 exercices, des quiz, de l’audio, des vidéos, des récompenses et une progression jusqu’au sommet du Mont-Blanc.';
$string['commerce_showroom_hero_cta_start'] = 'Je commence l’ascension';
$string['commerce_showroom_hero_cta_resume'] = 'Reprendre mon ascension';
$string['commerce_showroom_hero_cta_complete_course'] = 'Compléter avec le cours';
$string['commerce_showroom_hero_cta_complete_pdf'] = 'Ajouter le PDF à mon équipement';

// J13F2 — parcours immersif et explorateur d’exercices.
$string['commerce_showroom_ascent_eyebrow'] = 'L’ascension en 30 étapes';
$string['commerce_showroom_ascent_title'] = 'De la vallée au sommet, chaque étape vous rapproche de l’automatisme';
$string['commerce_showroom_ascent_description'] = 'Le parcours suit une vraie progression : les verbes les plus utiles ouvrent la marche, puis la difficulté augmente progressivement jusqu’aux formes les plus exigeantes.';
$string['commerce_showroom_ascent_aria'] = 'Parcours des 30 étapes de l’entraînement jusqu’au sommet du Mont-Blanc';
$string['commerce_showroom_ascent_stages'] = 'Étapes {$a}';
$string['commerce_showroom_ascent_1_title'] = 'Camp de départ';
$string['commerce_showroom_ascent_1_text'] = 'Les verbes indispensables et les premiers réflexes. Vous installez la méthode et prenez confiance.';
$string['commerce_showroom_ascent_2_title'] = 'Forêt alpine';
$string['commerce_showroom_ascent_2_text'] = 'Les familles de formes deviennent reconnaissables. La répétition commence à produire de vrais automatismes.';
$string['commerce_showroom_ascent_3_title'] = 'Passage rocheux';
$string['commerce_showroom_ascent_3_text'] = 'Vous affrontez les verbes irréguliers les plus fréquents avec des exercices variés et ciblés.';
$string['commerce_showroom_ascent_4_title'] = 'Traversée du glacier';
$string['commerce_showroom_ascent_4_text'] = 'Les formes rares et difficiles sont consolidées sans perdre le rythme ni la motivation.';
$string['commerce_showroom_ascent_5_title'] = 'Sommet du Mont-Blanc';
$string['commerce_showroom_ascent_5_text'] = 'Les 180 verbes sont acquis, testés et prêts à être utilisés naturellement dans vos phrases.';
$string['commerce_showroom_ascent_legend_1'] = 'Une étape validée à la fois';
$string['commerce_showroom_ascent_legend_2'] = 'La suite se débloque progressivement';
$string['commerce_showroom_ascent_legend_3'] = 'Une récompense à chaque arrivée';
$string['commerce_showroom_exercises_eyebrow'] = 'DÉCOUVREZ COMMENT FONCTIONNE L’ENTRAÎNEMENT';
$string['commerce_showroom_exercises_aria'] = 'Choisir un type d’exercice pour afficher son aperçu';
$string['commerce_showroom_exercises_preview_label'] = 'Aperçu interactif';
$string['commerce_showroom_exercises_preview_step'] = 'Dans une étape CampusFR';
$string['commerce_showroom_exercises_preview_caption'] = 'Cliquez sur un exercice pour découvrir une autre façon de mémoriser.';

// J13F3 — offer comparison.
$string['commerce_showroom_comparison_eyebrow'] = 'CHOISISSEZ VOTRE ÉQUIPEMENT';
$string['commerce_showroom_comparison_title'] = 'Comparez les options d’équipement';
$string['commerce_showroom_comparison_description'] = 'Plusieurs itinéraires mènent au sommet. Choisissez le vôtre.';
$string['commerce_showroom_comparison_feature'] = 'Critères';
$string['commerce_showroom_comparison_included'] = 'Inclus';
$string['commerce_showroom_comparison_not_included'] = 'Non inclus';
$string['commerce_showroom_comparison_bundle_badge'] = 'Recommandé';
$string['commerce_showroom_comparison_interactive_course'] = 'Entraînement interactif complet';
$string['commerce_showroom_comparison_downloadable_pdf'] = 'PDF téléchargeable';
$string['commerce_showroom_comparison_verbs_180'] = 'Les 180 verbes du parcours';
$string['commerce_showroom_comparison_exercises_4000'] = 'Plus de 4 000 exercices';
$string['commerce_showroom_comparison_audio_video'] = 'Audio, vidéos et récompenses';
$string['commerce_showroom_comparison_offline_revision'] = 'Révision hors connexion';
$string['commerce_showroom_comparison_lifetime_access'] = 'Accès à vie';


// J13F4 — Showroom reassurance and conversion.
$string['commerce_showroom_video_close'] = 'Fermer la vidéo';
$string['commerce_showroom_why_eyebrow'] = 'Une méthode conçue pour la mémoire';
$string['commerce_showroom_why_title'] = 'Pourquoi cette méthode fonctionne';
$string['commerce_showroom_why_description'] = 'On ne mémorise pas une liste une fois : on retrouve les formes dans des contextes variés jusqu’à ce qu’elles deviennent naturelles.';
$string['commerce_showroom_why_1_title'] = 'Répétition intelligente';
$string['commerce_showroom_why_1_text'] = 'Les formes reviennent au bon moment, sans bachotage inutile.';
$string['commerce_showroom_why_2_title'] = 'Contextes variés';
$string['commerce_showroom_why_2_text'] = 'Chaque verbe est réutilisé dans plusieurs situations concrètes.';
$string['commerce_showroom_why_3_title'] = 'Écoute et production';
$string['commerce_showroom_why_3_text'] = 'L’audio relie la forme écrite à la langue réellement entendue.';
$string['commerce_showroom_why_4_title'] = 'Progression motivante';
$string['commerce_showroom_why_4_text'] = 'Quiz, étapes et récompenses entretiennent l’envie de continuer.';
$string['commerce_showroom_why_5_title'] = 'Mémoire durable';
$string['commerce_showroom_why_5_text'] = 'La pratique répétée transforme progressivement les réponses en réflexes.';
$string['commerce_showroom_trust_1_title'] = 'Paiement sécurisé';
$string['commerce_showroom_trust_1_text'] = 'Parcours de paiement CampusFR protégé';
$string['commerce_showroom_trust_2_title'] = 'Accès immédiat';
$string['commerce_showroom_trust_2_text'] = 'Commencez dès la confirmation du paiement';
$string['commerce_showroom_trust_3_title'] = 'Accès à vie';
$string['commerce_showroom_trust_3_text'] = 'Revenez vous entraîner aussi souvent que nécessaire';
$string['commerce_showroom_trust_4_title'] = 'Support CampusFR';
$string['commerce_showroom_trust_4_text'] = 'Une équipe disponible en cas de question';
$string['commerce_showroom_testimonials_eyebrow'] = 'Ils apprennent avec CampusFR';
$string['commerce_showroom_testimonials_title'] = 'Prêts pour le sommet';
$string['commerce_showroom_faq_eyebrow'] = 'BRIEFING AVANT L’ASCENSION';
$string['commerce_showroom_faq_description'] = 'Les réponses aux questions qui reviennent le plus souvent avant de commencer l’ascension.';
$string['commerce_showroom_support_title'] = 'Encore des questions avant l’ascension ?';
$string['commerce_showroom_support_text'] = 'Nous ne vous enverrons pas au sommet sans assurance 😄
Gustave et l’équipe CampusFR vous aideront pour l’accès, l’achat et toutes vos questions sur le trainer.';
$string['commerce_showroom_support_cta'] = 'Contacter le support';
$string['commerce_showroom_expedition_card_label'] = 'Expédition Mont-Blanc';
$string['commerce_showroom_expedition_card_stage'] = 'Étape 0 sur 30';
$string['commerce_showroom_expedition_card_altitude'] = 'Altitude de départ : 1 035 m';
$string['commerce_showroom_desktop_sticky_label'] = 'Pack complet';

$string['commerce_showroom_status_draft'] = 'Brouillon';
$string['commerce_showroom_status_review'] = 'À valider';
$string['commerce_showroom_status_published'] = 'Publié';
$string['commerce_showroom_status_archived'] = 'Archivé';
$string['commerce_showroom_currency_update_error'] = 'Impossible de changer la devise pour le moment. Réessayez dans quelques instants.';
$string['commerce_showroom_cms_title'] = 'Showrooms Commerce';
$string['commerce_showroom_cms_create'] = 'Créer un showroom';
$string['commerce_showroom_cms_edit'] = 'Éditer le showroom';
$string['commerce_showroom_cms_key'] = 'Clé technique';
$string['commerce_showroom_cms_slugs'] = 'URLs publiques';
$string['commerce_showroom_cms_template'] = 'Template Moodle';
$string['commerce_showroom_cms_blocks'] = 'Blocs du showroom';
$string['commerce_showroom_cms_blocks_help'] = 'Le premier lot J13G enregistre l’ordre et la configuration des blocs. Le builder visuel avec glisser-déposer arrivera dans J13G2.';
$string['capability:manage_showrooms'] = 'Gérer les showrooms Commerce';
$string['commerce_showroom_builder_help'] = 'Réorganisez les blocs par glisser-déposer, activez-les, dupliquez-les et éditez leur configuration sans quitter la page.';
$string['commerce_showroom_builder_choose_block'] = 'Choisir un type de bloc';
$string['commerce_showroom_builder_preview'] = 'Prévisualiser le showroom';
$string['commerce_showroom_builder_edit_block'] = 'Configurer le bloc';
$string['commerce_showroom_builder_block_key'] = 'Clé du bloc';
$string['commerce_showroom_builder_configuration'] = 'Configuration JSON';
$string['commerce_showroom_builder_configuration_help'] = 'La configuration avancée reste en JSON dans J13G2. Des formulaires adaptés à chaque type de bloc arriveront dans le lot suivant.';
$string['commerce_showroom_builder_toggle'] = 'Activer ou désactiver';
$string['commerce_showroom_builder_confirm_delete'] = 'Supprimer définitivement ce bloc ?';
$string['commerce_showroom_builder_saved'] = 'Le showroom a été mis à jour.';
$string['commerce_showroom_builder_advanced_json'] = 'Configuration avancée JSON';
$string['commerce_showroom_builder_live_preview'] = 'Aperçu du bloc';
$string['commerce_showroom_builder_required'] = 'Champ obligatoire';
$string['commerce_showroom_choose_template'] = 'Choisir un modèle';
$string['commerce_showroom_apply_template'] = 'Appliquer le modèle';
$string['commerce_showroom_export'] = 'Exporter';
$string['commerce_showroom_import'] = 'Importer';
$string['commerce_showroom_import_help'] = 'Collez le contenu d’un fichier .showroom.json exporté depuis CampusFR.';

// J13G5 showroom publication workflow.
$string['commerce_showroom_history'] = 'Historique';
$string['commerce_showroom_revision'] = 'Révision';
$string['commerce_showroom_revision_action'] = 'Action';
$string['commerce_showroom_revision_note'] = 'Note de publication';
$string['commerce_showroom_restore_revision'] = 'Restaurer';
$string['commerce_showroom_revision_restored'] = 'La révision a été restaurée en brouillon.';
$string['commerce_showroom_no_revisions'] = 'Aucune révision enregistrée.';
$string['commerce_showroom_submit_review'] = 'Soumettre à validation';
$string['commerce_showroom_publish'] = 'Publier';
$string['commerce_showroom_return_draft'] = 'Repasser en brouillon';
$string['commerce_showroom_submitted_review'] = 'Le showroom a été soumis à validation.';
$string['commerce_showroom_published'] = 'Le showroom a été publié et une révision a été créée.';
$string['commerce_showroom_returned_draft'] = 'Le showroom est de nouveau en brouillon.';

$string['commerce_showroom_owned_compact'] = 'Déjà acquis';

$string['commerce_showroom_bundle_partial_owned'] = 'Vous possédez déjà un article de ce pack. Complétez votre équipement avec l’offre mise en avant.';
$string['commerce_product_visual_format_showroom'] = 'Visuel Showroom — 16:9';
$string['commerce_product_visual_format_showroom_help'] = 'Cartes et compositions marketing du Showroom. Taille recommandée : {$a}.';
$string['commerce_checkout_back_offer'] = 'Retour à l’offre';
$string['commerce_guest_checkout_identity_title'] = 'Vos coordonnées';
$string['commerce_guest_checkout_identity_checkout_description'] = 'Ces informations nous permettent de sécuriser votre achat et de vous donner accès à vos produits après le paiement.';

$string['commerce_checkout_terms_required'] = 'Vous devez accepter les conditions de vente et la politique de confidentialité avant de poursuivre.';

$string['commerce_smart_terms'] = 'Conditions générales';
$string['commerce_smart_privacy'] = 'Politique de confidentialité';

$string['commerce_provider_experience_title'] = 'Confirmer le paiement';
$string['commerce_provider_experience_message'] = 'Vous allez être redirigé vers notre plateforme de paiement sécurisée.';
$string['commerce_provider_experience_continue'] = 'Continuer';
$string['commerce_provider_experience_cancel'] = 'Annuler';
$string['commerce_provider_experience_other_method'] = 'Choisir un autre moyen de paiement';
$string['commerce_provider_experience_stripe_title'] = 'Continuer vers le paiement sécurisé';
$string['commerce_provider_experience_stripe_message'] = 'Vérifiez une dernière fois votre achat avant d’être redirigé vers Stripe.';
$string['commerce_provider_experience_stripe_advice'] = 'Paiement sécurisé : vous gardez le contrôle et revenez automatiquement sur CampusFR après le paiement.';
$string['commerce_provider_experience_stripe_continue'] = 'Continuer vers Stripe';
$string['commerce_provider_experience_alfa_title'] = 'Avant de continuer vers Alfa';
$string['commerce_provider_experience_alfa_message'] = 'Alfa peut rencontrer des difficultés de connexion lorsqu’un VPN est actif.';
$string['commerce_provider_experience_alfa_advice'] = 'Avant de continuer, vérifiez que votre VPN est désactivé afin d’éviter un blocage de la page Alfa.';
$string['commerce_provider_experience_alfa_continue'] = 'Continuer vers Alfa';

$string['commerce_provider_experience_stay'] = 'Rester sur CampusFR';
$string['commerce_provider_experience_alfa_standard_secondary'] = 'Fermer et choisir un autre moyen de paiement';$string['commerce_cart_currency_switch'] = 'Devise du panier';
$string['commerce_cart_currency_switch_help'] = 'Prix et remises recalculés';
$string['commerce_cart_currency_switched'] = 'Votre panier a été recalculé en {$a}.';
$string['commerce_cart_currency_removed_items'] = 'Indisponibles dans cette devise et retirés du panier : {$a}.';
$string['commerce_cart_currency_promotion_removed'] = 'Le code promotionnel a été retiré car il n’est pas applicable dans cette devise.';
$string['commerce_provider_experience_alfa_other_currency'] = 'Payer dans une autre devise';
$string['commerce_provider_currency_title'] = 'Choisissez une autre devise';
$string['commerce_provider_currency_message'] = 'Votre panier sera recalculé avec les prix, promotions et conditions disponibles dans la devise choisie. Les articles indisponibles pourront être retirés.';
$string['commerce_provider_currency_submit'] = 'Recalculer mon panier';
$string['commerce_provider_currency_empty'] = 'Aucune autre devise n’est actuellement disponible.';
$string['commerce_provider_currency_error'] = 'Impossible de charger les devises disponibles. Fermez cette fenêtre et réessayez.';

$string['commerce_cart_currency_removed_item_fallback'] = 'Un article';

// J14F — Conditional customer promotions.
$string['commerce_cart_message_promotion_requires_login'] = 'Connectez-vous à votre compte CampusFR pour utiliser cette offre.';
$string['commerce_cart_message_promotion_missing_required_product'] = 'Cette offre est réservée aux étudiants possédant déjà le produit requis.';
$string['commerce_cart_message_promotion_already_owns_excluded_product'] = 'Cette offre ne s’applique pas, car vous possédez déjà le produit remisé.';
$string['commerce_cart_message_promotion_customer_not_eligible'] = 'Votre compte ne remplit pas encore les conditions de cette offre.';
$string['commerce_cart_message_promotion_customer_rule_runtime_unavailable'] = 'Cette offre ne peut pas être vérifiée pour le moment.';
$string['commerce_promotion_customer_eligibility'] = 'Conditions d’éligibilité client';
$string['commerce_promotion_customer_eligibility_help'] = 'Réservez la promotion à certains étudiants selon les produits qu’ils possèdent déjà. Ces règles fonctionnent pour les codes saisis et les remises automatiques.';
$string['commerce_promotion_requires_login'] = 'Réserver aux utilisateurs connectés';
$string['commerce_promotion_requires_login_help'] = 'Oblige l’étudiant à se connecter avant que la promotion puisse être appliquée.';
$string['commerce_promotion_eligibility_mode'] = 'Combinaison des conditions';
$string['commerce_promotion_eligibility_mode_help'] = '« Toutes » exige que chaque condition soit satisfaite. « Au moins une » accepte une seule condition satisfaite.';
$string['commerce_promotion_eligibility_all'] = 'Toutes les conditions';
$string['commerce_promotion_eligibility_any'] = 'Au moins une condition';
$string['commerce_promotion_required_owned_products'] = 'Doit déjà posséder';
$string['commerce_promotion_required_owned_products_help'] = 'Sélectionnez les produits que l’étudiant doit déjà posséder pour bénéficier de la promotion.';
$string['commerce_promotion_excluded_owned_products'] = 'Ne doit pas encore posséder';
$string['commerce_promotion_excluded_owned_products_help'] = 'Sélectionnez les produits que l’étudiant ne doit pas encore posséder. Idéal pour éviter de remiser un produit déjà acquis.';
$string['commerce_promotion_eligibility_everyone'] = 'Tous les clients';
$string['commerce_promotion_eligibility_conditional'] = 'Promotion conditionnelle';

// J14G1 — Order result polish.
$string['commerce_order_result_access_contents'] = 'Accéder à mon espace';
$string['commerce_order_result_discover_store'] = 'Découvrir la boutique';

// J15A4 — Default Showroom block configuration.
$string['commerce_showroom_builder_initialise_defaults'] = 'Initialiser avec le contenu actuel';
$string['commerce_showroom_builder_confirm_defaults'] = 'Initialiser les blocs dont la configuration est vide ? Les blocs déjà personnalisés ne seront pas modifiés.';
$string['commerce_showroom_builder_defaults_initialised'] = '{count} bloc(s) initialisé(s) avec le contenu actuel.';

// J15B — CMS content runtime.
$string['commerce_showroom_back_to_list'] = 'Retour à la liste des showrooms';
$string['commerce_showroom_stats_title'] = 'Les chiffres du programme';
$string['commerce_showroom_journey_eyebrow'] = 'Votre progression';
$string['commerce_showroom_offers_title'] = 'Choisissez votre formule';
$string['commerce_showroom_method_eyebrow'] = 'La méthode CampusFR';
$string['commerce_showroom_faq_title'] = 'Questions fréquentes';
$string['commerce_showroom_support_description'] = 'Nous ne vous enverrons pas au sommet sans assurance 😄
Gustave et l’équipe CampusFR vous aideront pour l’accès, l’achat et toutes vos questions sur le trainer.';
$string['commerce_showroom_final_description'] = 'Choisissez votre formule et commencez votre ascension.';

// J15E1-2-3 — Showroom editor hardening.
$string['commerce_showroom_builder_advanced_json_help'] = 'Le JSON devient prioritaire dès qu’il est modifié. Les clés personnalisées sont conservées. Utilisez « Appliquer le JSON aux champs » pour recharger le formulaire visuel.';
$string['commerce_showroom_builder_apply_json'] = 'Appliquer le JSON aux champs';
$string['commerce_showroom_builder_sync_json'] = 'Régénérer le JSON depuis les champs';
$string['commerce_showroom_builder_invalid_json'] = 'La configuration JSON est invalide.';
$string['commerce_showroom_builder_json_object_required'] = 'La configuration doit être un objet JSON.';

// J15E4 — Showroom media manager.
$string['commerce_showroom_media_choose'] = 'Choisir une image';
$string['commerce_showroom_media_choose_video'] = 'Choisir une vidéo';
$string['commerce_showroom_media_remove'] = 'Supprimer l’image';
$string['commerce_showroom_media_remove_video'] = 'Supprimer la vidéo';
$string['commerce_showroom_media_uploading'] = 'Traitement en cours…';
$string['commerce_showroom_media_empty'] = 'Aucune image personnalisée. Le visuel par défaut sera utilisé.';
$string['commerce_showroom_media_empty_video'] = 'Aucune vidéo personnalisée. Le contenu par défaut sera utilisé.';
$string['commerce_showroom_media_uploaded'] = 'Image enregistrée.';
$string['commerce_showroom_media_uploaded_video'] = 'Vidéo enregistrée.';

$string['commerce_showroom_video_play'] = 'Lire la vidéo';
$string['commerce_showroom_video_pause'] = 'Mettre la vidéo en pause';
$string['commerce_showroom_video_replay'] = 'Revoir la vidéo';

$string['commerce_mypurchases_store_link'] = 'Voir la boutique';

// J15H.1H — Mobile offer presentation.
$string['commerce_showroom_offers_badge'] = 'Tarifs';
$string['commerce_showroom_offers_title_prefix'] = 'Comment commencera votre';
$string['commerce_showroom_offers_title_highlight'] = 'ascension';
$string['commerce_showroom_offers_title_suffix'] = ' ?';
$string['commerce_showroom_offers_subtitle'] = 'Quel que soit le chemin que vous choisirez, nous serons à vos côtés — du premier pas jusqu’au sommet.';
$string['commerce_showroom_offers_slider_hint'] = 'Faites glisser pour voir les autres offres';


// J15H.1I.1 — provisional account navigation and login guidance.
$string['commerce_guest_activation_nav_cta'] = 'Finaliser mon compte';
$string['commerce_guest_login_notice_title'] = 'Votre compte CampusFR attend son mot de passe';
$string['commerce_guest_login_notice_message'] = 'Votre achat est bien confirmé. Vous n’avez pas encore de mot de passe : finalisez votre compte au lieu d’essayer de vous connecter.';
$string['commerce_guest_login_notice_cta'] = 'Créer mon mot de passe';

$string['commerce_a11y_skip_to_content'] = 'Aller au contenu principal';

$string['commerce_a11y_showroom_devices'] = 'Aperçu de CampusFR sur ordinateur et mobile';

$string['commerce_a11y_key_figures'] = 'Chiffres clés';

$string['commerce_showroom_builder_image_help'] = 'PNG, JPG ou WebP • Recommandé : 1920 × 1080 px • Taille maximale : 20 Mo';

$string['commerce_showroom_builder_video_help'] = 'MP4 ou WebM • H.264, 1920 × 1080 recommandé • Taille maximale : 500 Mo';

$string['commerce_price_currency_delete_title'] = 'Supprimer le tarif de cette devise';
$string['commerce_price_currency_delete_confirm'] = 'Supprimer le tarif {$a} ? Ce produit ne sera plus disponible dans cette devise. Les commandes historiques ne seront pas modifiées.';
$string['commerce_price_currency_deleted'] = 'Le tarif {$a} a été supprimé.';
$string['crm_commerce_nav_identities'] = 'Identités';
$string['commerce_identity_reconciliation_title'] = 'Réconciliation des identités clients';
$string['commerce_identity_reconciliation_description'] = 'Auditez les achats Commerce Native sans compte Moodle rattaché et diagnostiquez les correspondances possibles par adresse e-mail.';
$string['commerce_identity_reconciliation_dryrun_notice'] = 'Cet écran fonctionne en lecture seule. Il analyse les correspondances sans modifier les données. Une réconciliation manuelle n’est exécutée qu’après une action explicite et protégée.';
$string['commerce_identity_unresolved_total'] = 'Achats non rattachés';
$string['commerce_identity_matched_on_page'] = 'Correspondances sur cette page';
$string['commerce_identity_not_found_on_page'] = 'Sans compte sur cette page';
$string['commerce_identity_ambiguous_on_page'] = 'Ambigus sur cette page';
$string['commerce_identity_filter_email'] = 'Filtrer par e-mail client';
$string['commerce_identity_reconciliation_empty'] = 'Aucun achat Commerce Native non rattaché ne correspond aux critères.';
$string['commerce_identity_purchase'] = 'Achat';
$string['commerce_identity_email'] = 'E-mail client';
$string['commerce_identity_diagnostic'] = 'Diagnostic';
$string['commerce_identity_candidate'] = 'Compte candidat';
$string['commerce_identity_status_matched'] = 'Correspondance unique';
$string['commerce_identity_status_not_found'] = 'Aucun compte';
$string['commerce_identity_status_ambiguous'] = 'Ambigu';
$string['commerce_identity_status_skipped'] = 'Ignoré';
$string['commerce_identity_status_unchanged'] = 'Déjà rattaché';
$string['commerce_identity_status_reconciled'] = 'Réconcilié';
$string['commerce_identity_user_link'] = 'Utilisateur #{$a}';
$string['commerce_identity_reconcile_action'] = 'Réconcilier';
$string['commerce_identity_reconcile_confirm'] = 'Cette action va rattacher définitivement cet achat Native et ses ressources compatibles au compte Moodle identifié. À utiliser uniquement après validation du diagnostic.';
$string['commerce_identity_reconcile_success'] = 'L’achat {$a} a été réconcilié.';
$string['commerce_identity_reconcile_not_applied'] = 'La réconciliation n’a pas été appliquée. Diagnostic actuel : {$a}.';
$string['crm_commerce_nav_personal_offers'] = 'Offres personnelles';
$string['commerce_personal_offers_title'] = 'Offres personnelles';
$string['commerce_personal_offers_description'] = 'Offres Commerce individuelles rattachées à une identité client, un produit Native cible et des conditions commerciales versionnées.';
$string['commerce_personal_offers_readonly_notice'] = 'Cet écran est en lecture seule pour cette phase. La création, les liens sécurisés, la révocation et la consommation des offres seront ajoutés dans les phases Personal Offer suivantes.';
$string['commerce_personal_offers_empty'] = 'Aucune offre personnelle ne correspond aux filtres actuels.';
$string['commerce_personal_offer_id'] = 'Offre';
$string['commerce_personal_offer_campaign'] = 'Campagne';
$string['commerce_personal_offer_email'] = 'E-mail';
$string['commerce_personal_offer_beneficiary'] = 'Bénéficiaire';
$string['commerce_personal_offer_target'] = 'Produit cible';
$string['commerce_personal_offer_pricing'] = 'Conditions';
$string['commerce_personal_offer_validity'] = 'Validité';
$string['commerce_personal_offer_status'] = 'Statut';
$string['commerce_personal_offer_status_issued'] = 'Émise';
$string['commerce_personal_offer_status_redeemed'] = 'Consommée';
$string['commerce_personal_offer_status_revoked'] = 'Révoquée';
$string['commerce_personal_offer_status_expired'] = 'Expirée';
$string['commerce_personal_offer_not_found'] = 'Offre personnelle introuvable.';
$string['commerce_personal_offer_not_revocable'] = 'Cette offre personnelle ne peut plus être révoquée.';
$string['commerce_personal_offer_not_redeemable'] = 'Cette offre personnelle ne peut pas être consommée.';
$string['commerce_personal_offer_purchase_not_paid'] = 'L’offre personnelle ne peut être consommée que par un achat Commerce Native payé avec succès.';
$string['commerce_personal_offer_identity_mismatch'] = 'L’identité de l’achat ne correspond pas au bénéficiaire de l’offre personnelle.';
$string['commerce_personal_offer_campaign_source_missing'] = 'Le produit source de la campagne Personal Offer est introuvable.';

$string['commerce_personal_offer_page_title'] = 'Votre offre personnelle';
$string['commerce_personal_offer_link_unavailable'] = 'Ce lien d’offre personnelle est invalide, expiré, révoqué, déjà utilisé ou indisponible pour ce compte.';
$string['commerce_personal_offer_back_store'] = 'Retour à la boutique';
$string['commerce_personal_offer_target_mismatch'] = 'Cette offre personnelle ne s’applique pas à ce produit.';
$string['commerce_personal_offer_target_unavailable'] = 'Le produit associé à cette offre personnelle est actuellement indisponible.';
$string['commerce_personal_offer_currency_unavailable'] = 'Cette offre personnelle n’est pas disponible dans la devise sélectionnée.';
$string['commerce_personal_offer_cart_failed'] = 'L’offre personnelle n’a pas pu être préparée pour le checkout.';

$string['commerce_personal_offers_admin_notice'] = 'Les offres personnelles sont administrables depuis leur fiche. Les liens sécurisés exportés sont personnels et doivent être traités comme des données sensibles.';
$string['commerce_personal_offer_detail_title'] = 'Détail de l’offre personnelle';
$string['commerce_personal_offer_source_purchase'] = 'Achat source';
$string['commerce_personal_offer_created'] = 'Émise le';
$string['commerce_personal_offer_redeemed_purchase'] = 'Achat de consommation';
$string['commerce_personal_offer_revocation'] = 'Révocation';
$string['commerce_personal_offer_secure_link'] = 'Lien personnel sécurisé';
$string['commerce_personal_offer_revoke'] = 'Révoquer l’offre';
$string['commerce_personal_offer_revoke_reason'] = 'Motif de révocation (optionnel)';
$string['commerce_personal_offer_reissue'] = 'Réémettre une nouvelle offre';
$string['commerce_personal_offer_validity_days'] = 'Nouvelle durée de validité (jours)';
$string['commerce_personal_offer_metadata'] = 'Métadonnées';
$string['commerce_personal_offer_revoked_success'] = 'L’offre personnelle a été révoquée.';
$string['commerce_personal_offer_reissued_success'] = 'Une nouvelle offre personnelle a été émise à partir de l’offre précédente.';
$string['commerce_personal_offer_reissue_active'] = 'Une offre encore active ne peut pas être réémise. Utilisez son lien actuel ou révoquez-la d’abord.';
$string['commerce_personal_offer_stats_title'] = 'Statistiques des offres personnelles';
$string['commerce_personal_offer_campaign_stats'] = 'Statistiques par campagne';
$string['commerce_personal_offer_export'] = 'Exporter les liens CSV';

$string['commerce_personal_offer_campaigns'] = "Campagnes d’offres personnelles";
$string['commerce_personal_offer_new_campaign'] = "Nouvelle campagne";
$string['commerce_personal_offer_campaigns_empty'] = "Aucune campagne Personal Offer.";
$string['commerce_personal_offer_create_individual'] = "Créer une offre individuelle";
$string['commerce_personal_offer_create'] = "Créer l’offre";
$string['commerce_personal_offer_audience'] = "Audience";
$string['commerce_personal_offer_audience_criteria'] = "Critères CRM / achats";
$string['commerce_personal_offer_audience_list'] = "Liste explicite (emails ou user IDs)";
$string['commerce_personal_offer_source_sku'] = "SKU du produit source";
$string['commerce_personal_offer_purchase_from'] = "Achat à partir du";
$string['commerce_personal_offer_purchase_to'] = "Achat jusqu’au";
$string['commerce_personal_offer_valid_from'] = "Valide à partir du";
$string['commerce_personal_offer_expires_at'] = "Expire le";
$string['commerce_personal_offer_account_filter'] = "Compte Moodle requis ?";
$string['commerce_personal_offer_exclude_owned'] = "Exclure les clients possédant déjà le produit cible";
$string['commerce_personal_offer_explicit_list'] = "Liste d’emails ou user IDs";
$string['commerce_personal_offer_amounts'] = "Montants en unités mineures";
$string['commerce_personal_offer_percent'] = "Remise en %";
$string['commerce_personal_offer_preview'] = "Prévisualiser / recalculer la population";
$string['commerce_personal_offer_generate'] = "Générer les offres";

// Personal Offer CRM UX.
$string['commerce_personal_offer_create_individual_help'] = 'Créez une offre personnelle ponctuelle pour un client précis. Le client peut déjà avoir un compte Moodle ou être connu uniquement par son adresse de courriel.';
$string['commerce_personal_offer_email_help'] = 'Commencez à saisir l’adresse d’un client Moodle existant. Vous pouvez aussi saisir une adresse valide sans compte Moodle.';
$string['commerce_personal_offer_campaign_optional'] = 'Campagne (facultatif)';
$string['commerce_personal_offer_campaign_none'] = 'Aucune campagne — offre individuelle';
$string['commerce_personal_offer_campaign_optional_help'] = 'Rattachez l’offre à une campagne CRM existante pour le suivi, ou laissez-la comme offre individuelle.';
$string['commerce_personal_offer_source_purchase_optional'] = 'Achat source (facultatif)';
$string['commerce_personal_offer_source_purchase_placeholder'] = 'Rechercher par référence de commande';
$string['commerce_personal_offer_source_purchase_help'] = 'Achat historique qui justifie l’offre. Laissez vide pour une offre VIP, un geste commercial ou un ciblage manuel.';
$string['commerce_personal_offer_target_help'] = 'Produit Commerce Native que le client pourra acheter grâce à cette offre personnelle.';
$string['commerce_personal_offer_strategy_fixed_price'] = 'Prix final personnel';
$string['commerce_personal_offer_strategy_fixed_discount'] = 'Remise fixe';
$string['commerce_personal_offer_strategy_percentage_discount'] = 'Remise en pourcentage';
$string['commerce_personal_offer_pricing_help'] = 'Choisissez comment l’offre personnelle modifie le prix public. Une seule stratégie est appliquée.';
$string['commerce_personal_offer_amounts_display_title'] = 'Montants par devise';
$string['commerce_personal_offer_amounts_display_help'] = 'Saisissez les montants normalement, tels qu’ils sont vus par le client (par ex. 30,00 € ou 2 990,00 ₽). Commerce effectue la conversion interne : aucune unité mineure à saisir ici.';
$string['commerce_personal_offer_valid_from_help'] = 'Facultatif. Laissez vide pour rendre l’offre utilisable immédiatement.';
$string['commerce_personal_offer_expires_at_help'] = 'Facultatif. Après cette date, l’offre reste dans l’historique mais ne peut plus être utilisée.';
$string['commerce_personal_offer_new_campaign_help'] = 'Définissez qui doit recevoir l’offre et à quelles conditions. Aucune offre et aucun email ne sont générés avant la prévisualisation et la validation de la population.';
$string['commerce_personal_offer_campaign_identity_title'] = 'Identification de la campagne';
$string['commerce_personal_offer_campaign_name_placeholder'] = 'Ex. Acheteurs historiques des cartes — lancement Trainer';
$string['commerce_personal_offer_campaign_name_help'] = 'Nom lisible affiché dans le CRM. Il sert à vous repérer et peut suivre votre propre convention.';
$string['commerce_personal_offer_campaign_key'] = 'Clé de campagne';
$string['commerce_personal_offer_campaign_key_auto'] = 'Générée automatiquement si vide';
$string['commerce_personal_offer_campaign_key_help'] = 'Identifiant technique stable utilisé par Commerce pour l’idempotence et le reporting. En général, laissez ce champ vide et laissez le CRM le générer.';
$string['commerce_personal_offer_audience_title'] = 'Destinataires';
$string['commerce_personal_offer_audience_help'] = 'Le mode Critères calcule automatiquement la liste depuis les données Commerce. Le mode Liste explicite permet de choisir directement des clients.';
$string['commerce_personal_offer_source_sku_help'] = 'Pour une campagne par critères, choisissez le produit que les destinataires doivent avoir acheté.';
$string['commerce_personal_offer_account_all'] = 'Avec ou sans compte Moodle';
$string['commerce_personal_offer_account_yes'] = 'Uniquement avec un compte Moodle';
$string['commerce_personal_offer_account_no'] = 'Uniquement sans compte Moodle';
$string['commerce_personal_offer_account_filter_help'] = 'Filtrez les destinataires selon que leur identité Commerce est déjà rattachée ou non à Moodle.';
$string['commerce_personal_offer_purchase_from_help'] = 'Date minimale facultative de l’achat du produit source.';
$string['commerce_personal_offer_purchase_to_help'] = 'Date maximale facultative de l’achat du produit source.';
$string['commerce_personal_offer_exclude_owned_help'] = 'Recommandé : évite de proposer un produit à un client qui le possède déjà.';
$string['commerce_personal_offer_explicit_list_help'] = 'À utiliser uniquement pour une audience de type Liste explicite. Sélectionnez des emails connus avec l’autocomplétion ou collez un email par ligne.';
$string['commerce_personal_offer_recipient_picker_placeholder'] = 'Commencez à saisir l’email d’un client';
$string['commerce_personal_offer_explicit_list_placeholder'] = 'Un email par ligne';
$string['commerce_personal_offer_offer_title'] = 'Conditions de l’offre';
$string['commerce_personal_offer_campaigns_help'] = 'Préparez, prévisualisez et générez les campagnes d’offres personnelles. L’envoi des emails sera géré séparément.';
$string['commerce_personal_offer_campaign_view_help'] = 'Contrôlez la population calculée avant de générer les offres. Tant que la campagne est en préparation, vous pouvez inclure ou exclure individuellement des destinataires.';
$string['commerce_personal_offer_metric_total'] = 'Population';
$string['commerce_personal_offer_metric_eligible'] = 'Sélectionnés';
$string['commerce_personal_offer_metric_excluded'] = 'Exclus';
$string['commerce_personal_offer_metric_error'] = 'Erreurs';
$string['commerce_personal_offer_metric_issued'] = 'Offres générées';
$string['commerce_personal_offer_criteria_generated_list_help'] = 'Simulation CRM : cette liste matérialise les clients détectés par la source Legacy/Native et les règles actuelles. Aucune offre n’est créée tant que vous ne lancez pas explicitement la génération.';
$string['commerce_personal_offer_reason_manual_exclusion'] = 'Exclu manuellement';
$string['commerce_personal_offer_reason_target_owned'] = 'Possède déjà le produit cible';
$string['commerce_personal_offer_reason_invalid_email'] = 'Adresse de courriel invalide';
$string['commerce_personal_offer_save_selection'] = 'Enregistrer la sélection';
$string['commerce_personal_offer_campaign_preview_empty'] = 'Prévisualisez la campagne pour calculer la liste des destinataires.';
$string['commerce_personal_offer_detail_help'] = 'Consultez le bénéficiaire, les conditions commerciales, le cycle de vie et le lien sécurisé de cette offre personnelle.';
$string['commerce_personal_offer_stats_help'] = 'Statistiques globales et par campagne sur le cycle de vie des offres personnelles.';

// Personal Offer email campaign (K9).
$string['commerce_mail_type_personal_offer'] = 'Offre personnelle';
$string['commerce_mail_personal_offer_subject'] = 'Une offre CampusFR réservée pour vous';
$string['commerce_mail_personal_offer_cta'] = 'Découvrir mon offre';
$string['commerce_mail_personal_offer_card_label'] = 'Votre offre personnelle';
$string['commerce_mail_personal_offer_expiry_label'] = 'Valable jusqu’au :';
$string['task_process_personal_offer_mail_queue'] = 'Envoi batché des emails Personal Offer';
$string['settings:personal_offer_mail_header'] = 'Emails Personal Offer';
$string['settings:personal_offer_mail_header_desc'] = 'Limites de sécurité appliquées aux emails commerciaux Personal Offer. Les emails transactionnels Commerce ne sont pas concernés.';
$string['settings:personal_offer_mail_batch_size'] = 'Taille d’un batch Personal Offer';
$string['settings:personal_offer_mail_batch_size_desc'] = 'Nombre maximal d’emails Personal Offer envoyés à chaque exécution de la tâche planifiée. Valeur prudente par défaut : 20.';
$string['settings:personal_offer_mail_hourly_limit'] = 'Plafond horaire Personal Offer';
$string['settings:personal_offer_mail_hourly_limit_desc'] = 'Nombre maximal d’emails Personal Offer envoyés sur une fenêtre glissante d’une heure. À ajuster selon la limite OVH réelle avant la PROD.';
$string['commerce_personal_offer_mail_title'] = 'Envoi des offres par email';
$string['commerce_personal_offer_mail_help'] = 'Les emails sont ajoutés à la file Commerce puis envoyés progressivement par cron. La mise en file ne déclenche pas un envoi massif immédiat.';
$string['commerce_personal_offer_mail_queue_campaign'] = 'Mettre les emails en file';
$string['commerce_personal_offer_mail_queue_single'] = 'Envoyer cette offre par email';
$string['commerce_personal_offer_mail_queued_success'] = 'Email Personal Offer ajouté à la file Commerce.';
$string['commerce_personal_offer_mail_campaign_queued'] = 'Campagne email préparée : {$a->queued} nouveaux emails en file, {$a->existing} déjà présents, {$a->errors} erreur(s).';
$string['commerce_personal_offer_mail_notqueued'] = 'À préparer';
$string['commerce_personal_offer_mail_queued'] = 'En file';
$string['commerce_personal_offer_mail_processing'] = 'En traitement';
$string['commerce_personal_offer_mail_sent'] = 'Envoyés';
$string['commerce_personal_offer_mail_failed'] = 'Échecs';
$string['commerce_personal_offer_mail_cancelled'] = 'Annulés';
$string['commerce_personal_offer_mail_status'] = 'Email';
$string['commerce_personal_offer_mail_error'] = 'Dernière erreur';
$string['commerce_personal_offer_mail_studio'] = 'Modifier le modèle email';
$string['commerce_personal_offer_mail_log'] = 'Ouvrir le journal Commerce';
$string['commerce_personal_offer_mail_batch_notice'] = 'L’envoi est volontairement batché pour respecter les limites du fournisseur email. Les erreurs sont conservées dans le journal Commerce et automatiquement retentées selon la politique existante.';

// Personal Offer CRM identity/display hotfix (K9.1).
$string['commerce_identity_customer'] = 'Client';
$string['commerce_personal_offer_beneficiary_search'] = 'Bénéficiaire';
$string['commerce_personal_offer_beneficiary_search_placeholder'] = 'Email, prénom ou nom';
$string['commerce_personal_offer_source_basis'] = 'Justification de l’offre';
$string['commerce_personal_offer_source_basis_help'] = 'Choisissez comment Commerce doit prouver l’éligibilité : aucun achat requis, possession d’un produit, ou achat précis.';
$string['commerce_personal_offer_source_none'] = 'Aucune — offre libre / geste commercial';
$string['commerce_personal_offer_source_product'] = 'Possession d’un produit';
$string['commerce_personal_offer_source_purchase_mode'] = 'Achat précis';
$string['commerce_personal_offer_source_purchase_help'] = 'Option avancée : choisissez une commande précise. La référence CFR est affichée en priorité et la référence technique cmp_ reste visible pour le diagnostic.';
$string['commerce_personal_offer_email_help'] = 'Recherchez un compte Moodle par prénom, nom ou email. Vous pouvez aussi saisir directement une adresse valide pour un client sans compte Moodle.';

$string['commerce_personal_offer_edit'] = 'Modifier';
$string['commerce_personal_offer_edit_help'] = 'Modifiez les conditions commerciales de cette offre. L’offre d’origine reste dans l’historique et est révoquée lorsque sa remplaçante est créée.';
$string['commerce_personal_offer_edit_replace_notice'] = 'La modification n’écrase pas l’offre d’origine : une nouvelle offre avec un nouveau lien sécurisé sera créée et l’offre actuelle sera révoquée.';
$string['commerce_personal_offer_delete'] = 'Supprimer';
$string['commerce_personal_offer_delete_confirm'] = 'Supprimer définitivement cette offre ? Cette action est réservée aux offres jamais envoyées, jamais consommées et non rattachées à une campagne.';
$string['commerce_personal_offer_delete_not_allowed'] = 'Cette offre ne peut pas être supprimée car elle a déjà été envoyée, consommée ou rattachée à une campagne. Révoquez-la à la place.';
$string['commerce_personal_offer_deleted_success'] = 'L’offre personnelle a été supprimée.';
$string['commerce_personal_offer_edit_not_allowed'] = 'Seule une offre personnelle active peut être modifiée.';
$string['commerce_personal_offer_replaced_success'] = 'La nouvelle version de l’offre a été créée et l’ancienne offre a été révoquée.';
$string['commerce_personal_offer_terms_fixed_price_label'] = 'Prix personnel';
$string['commerce_personal_offer_terms_fixed_discount_label'] = 'Remise fixe';
$string['commerce_personal_offer_terms_percentage_label'] = 'Remise en pourcentage';
$string['commerce_personal_offer_ownership_native_entitlement'] = 'Droit d’accès Native';
$string['commerce_personal_offer_ownership_native_purchase'] = 'Achat Native';
$string['commerce_personal_offer_ownership_bundle'] = 'Possession via les composants du bundle';
$string['commerce_personal_offer_ownership_legacy_digital'] = 'Achat digital Legacy';
$string['commerce_personal_offer_ownership_legacy_plan'] = 'Abonnement Legacy';
$string['commerce_personal_offer_eligibility_free'] = 'Offre libre';
$string['commerce_personal_offer_eligibility_free_help'] = 'Aucun achat ou produit source n’est requis pour cette offre.';
$string['commerce_personal_offer_eligibility_product'] = 'Possession d’un produit';
$string['commerce_personal_offer_eligibility_purchase'] = 'Achat précis';
$string['commerce_personal_offer_eligibility_campaign'] = 'Critères de campagne';
$string['commerce_personal_offer_evidence_purchase'] = 'Achat justificatif';
$string['commerce_personal_offer_campaign_criteria_source'] = 'Produit source du ciblage';
$string['commerce_personal_offer_no_campaign'] = 'Aucune campagne — offre individuelle';
$string['commerce_personal_offer_summary_title'] = 'Résumé de l’offre';
$string['commerce_personal_offer_eligibility_title'] = 'Pourquoi ce client est-il éligible ?';
$string['commerce_personal_offer_lifecycle_title'] = 'Validité et suivi';
$string['commerce_personal_offer_technical_title'] = 'Références techniques';
$string['commerce_personal_offer_ownership_source'] = 'Source de la preuve';
$string['commerce_personal_offer_metadata_technical'] = 'Métadonnées techniques';
$string['commerce_personal_offer_owned_product'] = 'Produit possédé';
$string['commerce_personal_offer_product_evidence_missing'] = 'Produit source non enregistré sur cette ancienne offre';
$string['commerce_personal_offer_legacy_purchase_reference'] = 'Achat digital Legacy #{$a}';
$string['commerce_personal_offer_revoke_confirm'] = 'Révoquer cette offre ? Son lien personnel deviendra immédiatement inutilisable.';
$string['commerce_personal_offer_checkout_temporary_error'] = 'Une erreur technique temporaire a interrompu l’ouverture de votre offre. Votre offre n’a pas été consommée ; vous pouvez réessayer.';
$string['commerce_personal_offer_checkout_badge'] = 'Offre personnelle';
$string['commerce_personal_offer_checkout_reserved_title'] = 'Cette offre vous est personnellement réservée';
$string['commerce_personal_offer_checkout_reserved_for'] = 'Offre réservée à {$a->name} ({$a->email})';
$string['commerce_personal_offer_checkout_currency_title'] = 'Choisissez votre devise';
$string['commerce_personal_offer_checkout_currency_help'] = 'Le prix personnel reste appliqué. Seules les devises prévues pour cette offre sont proposées.';
$string['commerce_checkout_existing_account_login_title'] = 'Connectez-vous pour continuer';
$string['commerce_checkout_existing_account_login_help'] = 'Ce compte existe déjà. Connectez-vous ici : votre panier et votre offre seront conservés et vous reviendrez automatiquement au paiement.';
$string['commerce_checkout_existing_account_login_submit'] = 'Se connecter et continuer';
$string['commerce_checkout_existing_account_login_alternative'] = 'Autre méthode de connexion';
$string['commerce_personal_offer_order_discount_label'] = 'Offre personnelle';
$string['commerce_personal_offer_order_admin_reference'] = 'Offre personnelle (admin)';
$string['commerce_personal_offer_order_open'] = 'Ouvrir l’offre';
$string['task_process_commerce_mail_audit_queue'] = 'Envoyer les copies d’audit Commerce en basse priorité';
$string['settings:commerce_mail_audit_batch_size'] = 'Taille des lots d’audit';
$string['settings:commerce_mail_audit_batch_size_desc'] = 'Nombre maximal de copies d’audit traitées par exécution. Ces messages restent derrière les e-mails clients et les campagnes.';
$string['settings:commerce_mail_audit_hourly_limit'] = 'Plafond horaire des copies d’audit';
$string['settings:commerce_mail_audit_hourly_limit_desc'] = 'Nombre maximal de copies d’audit envoyées sur une fenêtre glissante d’une heure.';
$string['commerce_mail_resend'] = 'Renvoyer l’e-mail';
$string['commerce_mail_resend_confirm'] = 'Créer un nouvel envoi de cet e-mail ? L’envoi original restera conservé dans l’historique.';
$string['commerce_mail_resend_queued'] = 'Le réenvoi a été ajouté à la file d’attente.';
$string['commerce_mail_resend_not_allowed'] = 'Seul un e-mail déjà envoyé peut être renvoyé de cette manière.';
$string['commerce_mail_personal_offer_validity_label'] = 'Offre valable';
$string['commerce_mail_personal_offer_valid_from_label'] = 'Valable à partir du';
$string['commerce_mail_personal_offer_from_label'] = 'du';
$string['commerce_mail_personal_offer_to_label'] = 'au';
$string['commerce_mail_preview_description'] = 'Prévisualisez exactement le contenu envoyé au client, contrôlez ses variantes d’affichage et gérez les éventuels réenvois.';
$string['commerce_mail_preview_font_label'] = 'Police';
$string['commerce_mail_preview_font_brand'] = 'CampusFR (Nunito)';
$string['commerce_mail_preview_font_fallback'] = 'Fallback e-mail';


// J16C.2 — Exercise Explorer Builder.
$string['commerce_showroom_exercise_builder_title'] = 'Contenu des 12 exercices';
$string['commerce_showroom_exercise_builder_content'] = 'Textes';
$string['commerce_showroom_exercise_builder_media'] = 'Captures d’écran';
$string['commerce_showroom_exercise_builder_default'] = 'Image principale';
$string['commerce_showroom_exercise_builder_import'] = 'Importer un lot de captures';
$string['commerce_showroom_exercise_builder_import_help'] = 'Importez un ZIP contenant jusqu’à 12 images. Les noms techniques ou les titres russes du lot initial sont reconnus automatiquement. Choisissez la langue cible avant l’import.';
$string['commerce_showroom_exercise_builder_import_button'] = 'Choisir un ZIP';
$string['commerce_showroom_exercise_builder_import_done'] = '{stored} image(s) enregistrée(s), {matched} exercice(s) reconnu(s).';
$string['commerce_showroom_exercise_builder_choose_image'] = 'Choisir';
$string['commerce_showroom_exercise_builder_remove_image'] = 'Supprimer';
$string['commerce_showroom_exercise_builder_image_empty'] = 'Aucune image';
$string['commerce_showroom_exercise_builder_image_fallback'] = 'L’image principale sera utilisée si aucune image localisée n’est disponible.';

// J16C.3 — Exercise Explorer public preview.
$string['commerce_showroom_exercise_preview_unavailable'] = 'Aperçu bientôt disponible';

// J16C.4 — Exercise Explorer mobile.
$string['commerce_showroom_exercise_mobile_previous'] = 'Exercice précédent';
$string['commerce_showroom_exercise_mobile_next'] = 'Exercice suivant';
$string['commerce_showroom_exercise_mobile_counter'] = 'Exercice';

// J16C.6 — Exercise Explorer navigation.
$string['commerce_showroom_exercise_navigation_hint'] = 'Faites défiler vers la droite ou la gauche, ou utilisez les boutons pour voir les autres exercices.';
$string['commerce_showroom_exercise_navigation_label'] = 'Navigation entre les exercices';

// J16C.6.2 — Exercise Explorer Builder UX.
$string['commerce_showroom_exercise_builder_fallback_badge'] = 'Fallback';
$string['commerce_mail_download_desktop'] = 'Classique';
$string['commerce_mail_download_mobile'] = 'Mobile';
$string['commerce_mail_bundle_contents'] = 'Contenu de votre pack';
$string['commerce_mail_access_my_campus'] = 'Accéder à mon Campus';

// J16C.6.3 — Exercise Explorer preview polish.
$string['commerce_showroom_exercise_builder_localized_empty'] = 'Image localisée absente';
$string['commerce_showroom_exercise_builder_localized_fallback'] = 'L’image principale sera utilisée automatiquement.';

// J16C.6.5 — Exercise Explorer heading and desktop hint.
$string['commerce_showroom_exercise_desktop_hint'] = 'Cliquez sur n’importe quel type d’exercice pour voir à quoi il ressemble.';
$string['commerce_mail_receipt_price_before_discounts'] = 'Sous-total';
$string['commerce_mail_receipt_discounts'] = 'Réductions';
$string['commerce_mail_receipt_total_paid'] = 'Total payé';
$string['commerce_mail_payment_status_paid_value'] = 'Payé';
$string['commerce_mail_payment_status_pending_value'] = 'En attente';
$string['commerce_mail_payment_status_failed_value'] = 'Échec';
$string['commerce_mail_payment_status_cancelled_value'] = 'Annulé';
// J16D.2 — Comparatif mobile.
$string['commerce_showroom_comparison_swipe_hint'] = 'Faites défiler vers la droite ou la gauche pour comparer';
$string['commerce_mail_receipt_product_promotions'] = 'Promotions produits';
$string['commerce_mail_receipt_trial_discount'] = 'Remise d’essai';
$string['commerce_mail_receipt_owned_credit'] = 'Crédit acquis';
$string['commerce_mail_receipt_promo_code'] = 'Code promo';
$string['commerce_mail_receipt_personal_offer'] = 'Offre personnelle';
$string['commerce_mail_receipt_other_discount'] = 'Autres remises';
$string['commerce_mail_type_trial_welcome'] = 'Bienvenue Trial';
$string['commerce_mail_trial_welcome_subject'] = 'Bienvenue dans CampusFR — votre essai commence';
$string['commerce_mail_trial_welcome_cta'] = 'Commencer mon apprentissage';
$string['commerce_mail_welcome_login_email'] = 'E-MAIL DE CONNEXION';
$string['commerce_mail_welcome_telegram_heading'] = 'Rejoignez la communauté CampusFR';
$string['commerce_mail_welcome_telegram_intro'] = 'Rejoignez notre canal pour les actualités et informations importantes de CampusFR. Le groupe vous permet aussi d’échanger, de demander conseil et de progresser avec les autres membres.';
$string['commerce_mail_welcome_telegram_channel'] = 'Canal CampusFR';
$string['commerce_mail_welcome_telegram_group'] = 'Groupe CampusFR';
$string['commerce_mail_welcome_forgot_password'] = 'Vous avez oublié votre mot de passe ?';
$string['commerce_mail_welcome_reset_password'] = 'Réinitialiser mon mot de passe';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_q'] = 'Puis-je refaire le trainer plusieurs fois ?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_a'] = 'Bien sûr ! Vous bénéficiez d’un accès à vie au trainer et pouvez donc revenir sur n’importe quel cours ou exercice autant de fois que vous le souhaitez. Beaucoup d’apprenants refont certaines sessions pour consolider les verbes les plus difficiles et les automatiser.';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_q'] = 'Et si je n’y arrive pas ?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_a'] = 'Bien sûr que vous y arriverez ! 😊 C’est précisément pour cela que nous avons créé ce trainer. Il vous accompagne étape par étape, même avec les verbes du 3e groupe les plus difficiles. Vous pourrez refaire les exercices autant de fois que nécessaire et, si vous avez une question, nous serons toujours là pour vous aider. Vous trouverez tous les moyens de contacter l’enseignant dans la rubrique « Informations pratiques » du trainer. Toute ascension commence par de petits pas. 😉';
$string['commerce_mail_welcome_credentials_heading'] = 'Vos identifiants de connexion';
$string['commerce_mail_welcome_activation_explanation'] = 'Pour sécuriser votre compte et choisir votre mot de passe, utilisez le bouton ci-dessous. Cette étape ne prend qu’un instant.';
$string['commerce_mail_welcome_activation_security'] = 'Ce lien d’activation est personnel et ne peut être utilisé qu’une seule fois. Si vous n’êtes pas à l’origine de cet achat, vous pouvez simplement ignorer cet e-mail.';
$string['commerce_guest_activation_email_expiry_soft'] = 'Pour votre sécurité, le lien d’activation restera disponible jusqu’au {$a}.';
$string['commerce_mail_welcome_postactivation'] = 'Une fois votre compte activé, vous retrouverez à tout moment vos cours, vos ressources et vos achats dans votre espace CampusFR.';

// J16I2 — Showroom general configuration.
$string['commerce_showroom_config_general'] = 'Configuration générale';
$string['commerce_showroom_config_general_help'] = 'Identité, état de publication et moteur de rendu du showroom.';
$string['commerce_showroom_config_key_help'] = 'Clé technique stable. Évitez de la modifier après publication.';
$string['commerce_showroom_config_render_template'] = 'Template de rendu Moodle';
$string['commerce_showroom_config_render_template_help'] = 'Détermine le template Mustache utilisé pour afficher la page publique. Ce réglage est différent du modèle de blocs disponible plus bas.';
$string['commerce_showroom_config_urls_legacy'] = 'URLs';
$string['commerce_showroom_config_urls_legacy_help'] = 'Les slugs restent configurables ici. Les métadonnées SEO multilingues seront gérées dans l’étape J16I3.';
$string['commerce_showroom_config_products'] = 'Produits associés';
$string['commerce_showroom_config_products_help'] = 'Sélectionnez les produits Commerce utilisés par les offres du showroom. Seuls les types compatibles sont proposés.';
$string['commerce_showroom_config_product_course'] = 'Cours / Entraîneur';
$string['commerce_showroom_config_product_pdf'] = 'Produit digital / Cartes';
$string['commerce_showroom_config_product_bundle'] = 'Bundle';
$string['commerce_showroom_config_product_none'] = '— Aucun produit —';
$string['commerce_showroom_config_advanced'] = 'Configuration avancée';
$string['commerce_showroom_config_titlekey_legacy'] = 'Clé de langue du titre (legacy)';
$string['commerce_showroom_config_descriptionkey_legacy'] = 'Clé de langue de la description (legacy)';
$string['commerce_showroom_config_seo_legacy_help'] = 'Ces clés sont conservées comme fallback lorsque le titre ou la description SEO CMS est vide.';
$string['commerce_showroom_config_settings_json'] = 'Configuration globale (JSON)';
$string['commerce_showroom_config_settings_json_help'] = 'Réservé aux réglages techniques avancés du showroom.';

// J16I3 — multilingual Showroom SEO.
$string['commerce_showroom_config_seo'] = 'SEO & partage';
$string['commerce_showroom_config_seo_help'] = 'Configurez les URLs et métadonnées propres à chaque langue. Si un titre ou une description reste vide, l’ancienne chaîne Moodle est utilisée en fallback.';
$string['commerce_showroom_config_seo_slug'] = 'Slug';
$string['commerce_showroom_config_seo_title'] = 'Titre SEO';
$string['commerce_showroom_config_seo_title_help'] = 'Idéalement environ 50 à 60 caractères, avec le sujet principal au début.';
$string['commerce_showroom_config_seo_description'] = 'Meta description';
$string['commerce_showroom_config_seo_social'] = 'Partage réseaux sociaux';
$string['commerce_showroom_config_seo_social_title'] = 'Titre social';
$string['commerce_showroom_config_seo_social_description'] = 'Description sociale';
$string['commerce_showroom_config_seo_keywords'] = 'Mots-clés';
$string['commerce_showroom_config_seo_keywords_help'] = 'Optionnel. Les moteurs modernes accordent peu d’importance à la balise meta keywords.';
$string['commerce_support_guest_contact_help'] = 'Indiquez votre adresse e-mail afin que notre équipe puisse vous répondre. Le prénom et le nom sont facultatifs, mais nous vous conseillons de les renseigner.';
$string['commerce_support_back_to_store'] = 'Retour à la boutique';
$string['commerce_support_return_to_store'] = 'Retour à la boutique';
$string['commerce_personal_offer_mail_image'] = 'Image de l’e-mail';
$string['commerce_personal_offer_mail_image_help'] = 'Facultatif. Cette image sera affichée en bas de l’e-mail de l’offre. Sans image personnalisée, le visuel CampusFR par défaut sera utilisé. JPG, PNG ou WebP, 8 Mo maximum.';
$string['commerce_personal_offer_mail_image_edit_help'] = 'Téléversez une nouvelle image pour la remplacer. Si vous ne choisissez aucun fichier, l’image personnalisée actuelle est conservée.';
$string['commerce_personal_offer_mail_image_upload_error'] = 'Impossible de téléverser l’image de l’offre.';
$string['commerce_personal_offer_mail_image_too_large'] = 'L’image de l’offre dépasse la taille maximale autorisée de 8 Mo.';
$string['commerce_personal_offer_mail_image_invalid_type'] = 'L’image de l’offre doit être un fichier JPG, PNG ou WebP valide.';
$string['commerce_manual_grant_access_type'] = 'Type d’accès';
$string['commerce_manual_grant_mode_legacy'] = 'Abonnement Legacy';
$string['commerce_manual_grant_mode_native'] = 'Produit Native';
$string['commerce_manual_grant_native_section'] = 'Accès Commerce Native';
$string['commerce_manual_grant_native_product'] = 'Produit à attribuer';
$string['commerce_manual_grant_native_product_help'] = 'Le produit est délivré par le moteur Commerce Native. Les bundles sont décomposés et tous leurs accès sont attribués.';
$string['commerce_manual_grant_reason'] = 'Motif de l’attribution';
$string['commerce_manual_grant_reason_help'] = 'Facultatif. Cette information est conservée dans les métadonnées d’audit du droit attribué.';
$string['commerce_manual_grant_submit'] = 'Attribuer l’accès';
$string['commerce_manual_grant_success'] = 'Le produit « {$a->product} » a été attribué à {$a->user}. {$a->count} droit(s) Native ont été traités.';
$string['commerce_manual_grant_invalid_mode'] = 'Type d’attribution manuelle invalide.';
$string['commerce_manual_grant_product_unavailable'] = 'Le produit Native sélectionné est introuvable ou inactif.';
$string['commerce_manual_grant_missing_entitlement'] = 'Aucun droit Native effectif n’est configuré pour le produit {$a}.';
$string['commerce_manual_grant_empty_plan'] = 'Le produit sélectionné ne génère aucun droit Native.';
$string['commerce_grants_title'] = 'Attributions';
$string['commerce_grants_description'] = 'Gérez les accès Legacy et Native, individuellement ou en masse.';
$string['commerce_grants_card_description'] = 'Attribuez un accès à un client ou préparez une attribution en masse avec simulation préalable.';
$string['commerce_grants_manual_title'] = 'Attribution individuelle';
$string['commerce_grants_manual_description'] = 'Ajoutez manuellement un abonnement Legacy ou attribuez un produit Commerce Native à un client.';
$string['commerce_grants_manual_action'] = 'Attribuer un accès manuellement';
$string['commerce_grants_back'] = 'Retour aux attributions';
$string['commerce_bulk_grant_title'] = 'Attribution en masse';
$string['commerce_bulk_grant_description'] = 'Sélectionnez une population selon un plan Legacy ou la possession d’un produit Native, puis simulez précisément les bénéficiaires avant toute attribution.';
$string['commerce_bulk_grant_open'] = 'Préparer une attribution en masse';
$string['commerce_bulk_grant_source_type'] = 'Critère d’éligibilité';
$string['commerce_bulk_grant_source_legacy'] = 'Inscription à un plan Legacy';
$string['commerce_bulk_grant_source_native'] = 'Possession d’un produit Native';
$string['commerce_bulk_grant_source_plan'] = 'Plan Legacy source';
$string['commerce_bulk_grant_source_product'] = 'Produit Native source';
$string['commerce_bulk_grant_target_product'] = 'Produit à offrir';
$string['commerce_bulk_grant_target_help'] = 'Aucun accès n’est attribué pendant cette étape : le produit sert uniquement à vérifier l’éligibilité et les droits qui seraient délivrés.';
$string['commerce_bulk_grant_simulate'] = 'Simuler les bénéficiaires';
$string['commerce_bulk_grant_preview_title'] = 'Simulation des bénéficiaires';
$string['commerce_bulk_grant_preview_help'] = 'Cette liste représente exactement la population détectée avec les critères actuels. Vérifiez les identités et les exclusions avant de poursuivre.';
$string['commerce_bulk_grant_metric_total'] = 'Clients détectés';
$string['commerce_bulk_grant_metric_eligible'] = 'À attribuer';
$string['commerce_bulk_grant_metric_owned'] = 'Déjà possédé';
$string['commerce_bulk_grant_metric_identity'] = 'Identité à vérifier';
$string['commerce_bulk_grant_metric_error'] = 'Erreurs';
$string['commerce_bulk_grant_dry_run_badge'] = 'DRY-RUN';
$string['commerce_bulk_grant_no_mutation'] = 'Aucun accès, achat ou entitlement n’a été créé ou modifié.';
$string['commerce_bulk_grant_filter_all'] = 'Tous ({$a})';
$string['commerce_bulk_grant_filter_eligible'] = 'À attribuer ({$a})';
$string['commerce_bulk_grant_filter_owned'] = 'Déjà possédé ({$a})';
$string['commerce_bulk_grant_filter_identity'] = 'Identité à vérifier ({$a})';
$string['commerce_bulk_grant_filter_error'] = 'Erreurs ({$a})';
$string['commerce_bulk_grant_customer'] = 'Client';
$string['commerce_bulk_grant_moodle_account'] = 'Compte Moodle';
$string['commerce_bulk_grant_evidence'] = 'Justificatif d’éligibilité';
$string['commerce_bulk_grant_current_ownership'] = 'Situation cible';
$string['commerce_bulk_grant_decision'] = 'Décision';
$string['commerce_bulk_grant_account_unresolved'] = 'Non résolu';
$string['commerce_bulk_grant_decision_eligible'] = 'À attribuer';
$string['commerce_bulk_grant_decision_already_owned'] = 'Déjà possédé';
$string['commerce_bulk_grant_decision_identity_review'] = 'À vérifier';
$string['commerce_bulk_grant_decision_error'] = 'Erreur';
$string['commerce_bulk_grant_planned_entitlements'] = '{$a} droit(s) seraient délivrés';
$string['commerce_bulk_grant_reason_missing_moodle_user'] = 'Aucun compte Moodle n’a pu être résolu.';
$string['commerce_bulk_grant_reason_invalid_email'] = 'L’adresse e-mail du compte est invalide.';
$string['commerce_bulk_grant_reason_target_already_owned'] = 'Le client possède déjà le produit cible.';
$string['commerce_bulk_grant_ownership_none'] = 'Non possédé';
$string['commerce_bulk_grant_ownership_native_entitlement'] = 'Droit Native actif';
$string['commerce_bulk_grant_ownership_native_purchase'] = 'Achat Native';
$string['commerce_bulk_grant_ownership_bundle_components'] = 'Tous les composants du bundle sont déjà possédés';
$string['commerce_bulk_grant_ownership_legacy_digital_purchase'] = 'Achat digital Legacy';
$string['commerce_bulk_grant_ownership_legacy_plan'] = 'Plan Legacy';
$string['commerce_bulk_grant_next_phase_notice'] = 'La simulation est volontairement non exécutable à ce stade. K14E ajoutera la sélection finale des bénéficiaires et le snapshot avant lancement de la campagne.';
$string['commerce_bulk_grant_invalid_source_type'] = 'Type de critère d’éligibilité invalide.';
$string['commerce_bulk_grant_source_product_missing'] = 'Le produit Native utilisé comme critère est introuvable.';
$string['crm_commerce_nav_grants'] = 'Attributions';
$string['commerce_bulk_grant_select_all'] = 'Tout sélectionner les éligibles';
$string['commerce_bulk_grant_select_none'] = 'Tout désélectionner';
$string['commerce_bulk_grant_snapshot_title'] = 'Créer le snapshot définitif';
$string['commerce_bulk_grant_snapshot_help'] = 'Seuls les bénéficiaires cochés et encore éligibles seront figés dans la campagne. La population source ne sera plus recalculée lors de l’exécution.';
$string['commerce_bulk_grant_campaign_name'] = 'Nom de la campagne';
$string['commerce_bulk_grant_campaign_name_placeholder'] = 'Ex. Lifetime Legacy → Verbes du 3e groupe';
$string['commerce_bulk_grant_campaign_reason'] = 'Motif de l’attribution';
$string['commerce_bulk_grant_create_snapshot'] = 'Créer le snapshot de la campagne';
$string['commerce_bulk_grant_campaign_name_required'] = 'Le nom de la campagne est obligatoire.';
$string['commerce_bulk_grant_campaign_selection_required'] = 'Sélectionnez au moins un bénéficiaire éligible.';
$string['commerce_bulk_grant_campaign_selection_changed'] = 'L’utilisateur #{$a} n’est plus éligible au moment de créer le snapshot. Relancez la simulation et vérifiez la sélection.';
$string['commerce_bulk_grant_campaign_not_launchable'] = 'Cette campagne ne peut pas être lancée dans son état actuel.';
$string['commerce_bulk_grant_campaign_retry_unavailable'] = 'Les erreurs de cette campagne ne peuvent pas être relancées dans son état actuel.';
$string['commerce_bulk_grant_campaign_view_help'] = 'Consultez le snapshot figé, lancez l’attribution et suivez chaque bénéficiaire jusqu’au résultat final.';
$string['commerce_bulk_grant_campaign_snapshot_title'] = 'Snapshot de la campagne';
$string['commerce_bulk_grant_campaign_source'] = 'Population source';
$string['commerce_bulk_grant_campaign_metric_queued'] = 'En attente';
$string['commerce_bulk_grant_campaign_metric_success'] = 'Succès';
$string['commerce_bulk_grant_campaign_metric_skipped'] = 'Ignorés';
$string['commerce_bulk_grant_campaign_launch'] = 'Lancer l’attribution pour {$a} bénéficiaire(s)';
$string['commerce_bulk_grant_campaign_launch_confirm'] = 'Cette action lance la campagne sur le snapshot actuellement affiché. Les accès seront attribués par lots via le cron. Continuer ?';
$string['commerce_bulk_grant_campaign_launched'] = 'La campagne est en file d’attente. Les attributions seront traitées par lots.';
$string['commerce_bulk_grant_campaign_retry'] = 'Relancer {$a} erreur(s)';
$string['commerce_bulk_grant_campaign_retried'] = '{$a} bénéficiaire(s) en erreur ont été remis en file d’attente.';
$string['commerce_bulk_grant_campaign_cron_notice'] = 'La campagne est en cours. Le cron traite jusqu’à 25 bénéficiaires par passage ; les succès ne sont jamais retraités.';
$string['commerce_bulk_grant_campaign_attempts'] = 'Tentatives';
$string['commerce_bulk_grant_campaign_error'] = 'Dernière erreur';
$string['commerce_bulk_grant_new'] = 'Nouvelle attribution en masse';
$string['commerce_bulk_grant_campaigns_title'] = 'Campagnes d’attribution';
$string['commerce_bulk_grant_campaigns_empty'] = 'Aucune campagne d’attribution en masse n’a encore été créée.';
$string['commerce_bulk_grant_campaign_status_ready'] = 'Prête';
$string['commerce_bulk_grant_campaign_status_queued'] = 'En attente';
$string['commerce_bulk_grant_campaign_status_running'] = 'En cours';
$string['commerce_bulk_grant_campaign_status_completed'] = 'Terminée';
$string['commerce_bulk_grant_campaign_status_completed_errors'] = 'Terminée avec erreurs';
$string['commerce_bulk_grant_member_status_queued'] = 'En attente';
$string['commerce_bulk_grant_member_status_completed'] = 'Attribué';
$string['commerce_bulk_grant_member_status_skipped'] = 'Ignoré';
$string['commerce_bulk_grant_member_status_failed'] = 'Échec';
$string['task_process_commerce_grant_campaigns'] = 'Traiter les campagnes d’attribution Commerce';
$string['commerce_mail_type_grant_access'] = 'Accès attribué';
$string['commerce_mail_grant_access_subject'] = 'Un nouvel accès CampusFR est disponible';
$string['commerce_manual_grant_send_email'] = 'Envoyer l’e-mail « Accès disponibles »';
$string['commerce_manual_grant_send_email_help'] = 'Recommandé. L’e-mail transactionnel présente le produit attribué et ses liens d’accès. Pour une attribution individuelle, l’envoi est tenté immédiatement puis repris par la file transactionnelle en cas d’échec.';
$string['commerce_bulk_grant_send_email'] = 'Envoyer l’e-mail « Accès disponibles » aux bénéficiaires';
$string['commerce_bulk_grant_send_email_help'] = 'Recommandé. Un seul e-mail sera mis en file par bénéficiaire et par produit attribué. Les e-mails du bulk ne sont pas envoyés directement par le cron d’attribution : ils passent par la file transactionnelle et son throttling.';
$string['commerce_bulk_grant_email_notification'] = 'Notification par e-mail';
$string['commerce_personal_offer_source_type'] = 'Source d’éligibilité';
$string['commerce_personal_offer_source_type_help'] = 'Choisissez la possession ou la souscription qui doit rendre un client éligible à cette campagne. La simulation ne crée aucune offre.';
$string['commerce_personal_offer_source_legacy_plan'] = 'Plan / abonnement Legacy';
$string['commerce_personal_offer_source_legacy_digital'] = 'Produit digital Legacy';
$string['commerce_personal_offer_source_native_product'] = 'Produit Native';
$string['commerce_personal_offer_source_missing'] = 'La source d’éligibilité sélectionnée est introuvable.';
$string['commerce_personal_offer_invalid_source_type'] = 'Type de source d’éligibilité invalide.';
$string['commerce_personal_offer_metric_covered'] = 'Déjà couverts';
$string['commerce_personal_offer_metric_identity_review'] = 'Identité à vérifier';
$string['commerce_personal_offer_customer'] = 'Client';
$string['commerce_personal_offer_moodle_account'] = 'Compte Moodle';
$string['commerce_personal_offer_eligibility_evidence'] = 'Preuve d’éligibilité';
$string['commerce_personal_offer_existing_offer'] = 'Offre active existante';
$string['commerce_personal_offer_account_unresolved'] = 'Non résolu';
$string['commerce_personal_offer_reason_ambiguous_email'] = 'Plusieurs comptes Moodle correspondent à cet e-mail.';
$string['commerce_personal_offer_reason_account_required'] = 'La campagne exige un compte Moodle résolu.';
$string['commerce_personal_offer_reason_account_not_allowed'] = 'La campagne cible uniquement les clients sans compte Moodle.';
$string['commerce_personal_offer_reason_active_offer_exists'] = 'Une offre personnelle active existe déjà pour ce produit.';
$string['commerce_personal_offer_member_status_eligible'] = 'Éligible';
$string['commerce_personal_offer_member_status_covered'] = 'Déjà couvert';
$string['commerce_personal_offer_member_status_identity_review'] = 'Identité à vérifier';
$string['commerce_personal_offer_member_status_excluded'] = 'Exclu';
$string['commerce_personal_offer_member_status_error'] = 'Erreur';
$string['commerce_personal_offer_member_status_issued'] = 'Offre créée';
$string['commerce_personal_offer_member_status_replayed'] = 'Offre existante rejouée';
$string['commerce_personal_offer_preview'] = 'Simuler les clients éligibles';
$string['commerce_personal_offer_create_snapshot'] = 'Créer le snapshot définitif';
$string['commerce_personal_offer_snapshot_confirm'] = 'La sélection actuelle sera figée. Après création du snapshot, vous ne pourrez plus recalculer la population ni modifier les bénéficiaires. Continuer ?';
$string['commerce_personal_offer_generate_snapshot'] = 'Générer les offres pour {$a} bénéficiaire(s)';
$string['commerce_personal_offer_generate_snapshot_confirm'] = 'Les offres personnelles vont être créées pour les bénéficiaires figés dans le snapshot. Continuer ?';
$string['commerce_personal_offer_snapshot_title'] = 'Snapshot de la campagne';
$string['commerce_personal_offer_snapshot_selected'] = 'Bénéficiaires sélectionnés';
$string['commerce_personal_offer_snapshot_date'] = 'Snapshot créé le';
$string['commerce_personal_offer_snapshot_hash'] = 'Empreinte du snapshot';
$string['commerce_personal_offer_snapshot_frozen_notice'] = 'La population est maintenant figée. La génération utilisera uniquement ce snapshot ; la source d’éligibilité ne sera plus recalculée.';
$string['commerce_personal_offer_snapshot_empty'] = 'Aucun bénéficiaire éligible n’est sélectionné pour créer le snapshot.';
$string['commerce_personal_offer_snapshot_changed'] = 'Le snapshot de campagne a été modifié ou ne correspond plus aux bénéficiaires figés. Relancez une nouvelle campagne plutôt que de poursuivre.';
$string['commerce_personal_offer_reason_target_acquired_after_snapshot'] = 'Le client a acquis le produit cible après le snapshot.';
$string['commerce_personal_offer_reason_active_offer_created_after_snapshot'] = 'Une autre offre personnelle active a été créée après le snapshot.';
$string['commerce_personal_offer_campaign_status_draft'] = 'Brouillon';
$string['commerce_personal_offer_campaign_status_previewed'] = 'Simulée';
$string['commerce_personal_offer_campaign_status_snapshot'] = 'Snapshot figé';
$string['commerce_personal_offer_campaign_status_issued'] = 'Offres générées';
$string['commerce_personal_offer_campaign_status_closed'] = 'Clôturée';
$string['commerce_personal_offer_retry_generation'] = 'Relancer {$a} erreur(s) de génération';
$string['commerce_personal_offer_mail_queue_missing'] = 'Mettre en file {$a} e-mail(s) restant(s)';
$string['commerce_personal_offer_mail_queue_confirm'] = 'Les e-mails manquants seront ajoutés à la file transactionnelle et envoyés selon les règles de throttling. Continuer ?';
$string['commerce_personal_offer_mail_retry_failed'] = 'Relancer {$a} e-mail(s) en échec';
$string['commerce_personal_offer_mail_campaign_retried'] = '{$a->requeued} e-mail(s) ont été remis en file sur {$a->failed} échec(s) détecté(s).';
$string['commerce_personal_offer_mail_expected'] = 'Attendus';
$string['commerce_personal_offer_certification_title'] = 'Certification de la campagne';
$string['commerce_personal_offer_certification_ready'] = 'La campagne est prête à être certifiée : aucune erreur de génération ni aucun e-mail en attente ou en échec.';
$string['commerce_personal_offer_certification_blocked'] = 'Certification bloquée : {$a->generationerrors} erreur(s) de génération, {$a->selectedpending} bénéficiaire(s) encore en attente de traitement et {$a->mailblocking} e-mail(s) non finalisé(s).';
$string['commerce_personal_offer_certify_campaign'] = 'Certifier et clôturer la campagne';
$string['commerce_personal_offer_certify_confirm'] = 'La campagne sera marquée comme certifiée et clôturée. Cette action confirme que toutes les offres attendues ont été traitées et tous les e-mails transactionnels finalisés. Continuer ?';
$string['commerce_personal_offer_campaign_certified'] = 'La campagne a été certifiée et clôturée.';
$string['commerce_personal_offer_campaign_not_certifiable'] = 'Cette campagne ne peut pas encore être certifiée. Corrigez les erreurs de génération et finalisez tous les e-mails attendus.';
$string['commerce_personal_offer_certified_at'] = 'Certifiée le';
$string['commerce_personal_offer_certified_by'] = 'Certifiée par';

// 7.95L1 — Showroom offer discovery CTA.
$string['commerce_showroom_config_offer_details_enabled'] = 'Afficher le lien « En savoir plus »';
$string['commerce_product_discovery_destination'] = 'Destination du bouton « Découvrir »';
$string['commerce_product_discovery_storefront'] = 'Fiche produit Storefront';
$string['commerce_product_discovery_showroom'] = 'Showroom associé';
$string['commerce_product_discovery_help'] = 'Détermine où les liens « Découvrir » de la Boutique, Mes achats et des autres parcours clients envoient le visiteur. Si le Showroom n’est pas publié, la fiche Storefront est utilisée automatiquement.';
$string['commerce_product_show_full_presentation_cta'] = 'Afficher « Voir la présentation complète » sur la fiche Storefront';
$string['commerce_product_show_full_presentation_cta_help'] = 'Conserve la fiche Storefront accessible et propose un lien vers le Showroom associé. Aucune redirection automatique n’est effectuée.';
$string['commerce_storefront_full_presentation'] = 'Voir la présentation complète';
$string['commerce_storefront_commerce_position_none'] = 'Masqué (Builder uniquement)';
$string['commerce_storefront_product_header_mode'] = 'En-tête produit';
$string['commerce_storefront_product_header_automatic'] = 'Automatique';
$string['commerce_storefront_product_header_builder'] = 'Géré par le Builder';
$string['commerce_storefront_product_header_hidden'] = 'Masqué';
$string['commerce_storefront_product_header_help'] = 'En mode Builder, le premier bloc Hero visible remplace l’en-tête produit automatique. Si aucun Hero n’est présent, l’en-tête automatique reste affiché.';
$string['commerce_storefront_hero_layout'] = 'Disposition du Hero';
$string['commerce_storefront_hero_layout_text_media'] = 'Texte à gauche / média à droite';
$string['commerce_storefront_hero_layout_media_text'] = 'Média à gauche / texte à droite';
$string['commerce_storefront_hero_layout_stacked'] = 'Empilé';
$string['commerce_storefront_hero_layout_overlay'] = 'Texte sur l’image';
$string['commerce_storefront_hero_ratio'] = 'Proportions texte / média';
$string['commerce_storefront_hero_media_ratio'] = 'Format du média';
$string['commerce_storefront_media_ratio_original'] = 'Format original';
$string['commerce_currency'] = 'Devise';
$string['commerce_storefront_builder_attention'] = 'À compléter';
$string['commerce_storefront_builder_empty_status'] = 'Vide';

$string['commerce_storefront_image_fit'] = 'Ajustement de l’image';
$string['commerce_storefront_image_fit_cover'] = 'Remplir le cadre (cover)';
$string['commerce_storefront_image_fit_contain'] = 'Afficher l’image entière (contain)';
$string['commerce_storefront_content_alignment'] = 'Alignement du contenu';
$string['commerce_storefront_content_alignment_left'] = 'Gauche';
$string['commerce_storefront_content_alignment_center'] = 'Centre';
$string['commerce_storefront_content_alignment_right'] = 'Droite';

// Storefront locale copy and AI translation.
$string['settings:storefront_ai_translation_enabled'] = 'Activer la traduction IA des Storefronts';
$string['settings:storefront_ai_translation_enabled_desc'] = 'Autorise le Builder Storefront à utiliser le compte OpenAI déjà configuré (clé, projet, organisation, modèle et endpoint) pour préparer des traductions de locales. Une prévisualisation doit être validée manuellement avant tout enregistrement.';
$string['commerce_storefront_locale_tools_title'] = 'Locales et traduction';
$string['commerce_storefront_locale_tools_help'] = 'Dupliquez une locale existante ou préparez une traduction automatique vers la langue actuellement éditée.';
$string['commerce_storefront_locale_copy_title'] = 'Copier une locale';
$string['commerce_storefront_locale_copy_help'] = 'Copie la structure éditoriale, les sections, le SEO, les données localisées et les références média vers la langue actuellement éditée. Les réglages Commerce globaux et les slugs ne sont pas modifiés.';
$string['commerce_storefront_locale_source'] = 'Locale source';
$string['commerce_storefront_locale_copy_button'] = 'Copier vers cette locale';
$string['commerce_storefront_locale_copy_confirm'] = 'La locale actuellement éditée sera remplacée par une copie de la locale source. Continuer ?';
$string['commerce_storefront_locale_copy_success'] = 'La locale a été copiée.';
$string['commerce_storefront_locale_source_empty'] = 'La locale source ne contient aucune configuration Storefront à copier.';
$string['commerce_storefront_ai_translation_title'] = 'Traduire avec OpenAI';
$string['commerce_storefront_ai_translation_help'] = 'Prépare une copie traduite de la locale source. Seuls les champs textuels sont envoyés à OpenAI ; les IDs, URLs, médias, SKU et réglages techniques restent inchangés.';
$string['commerce_storefront_ai_translation_preview_button'] = 'Préparer la traduction';
$string['commerce_storefront_ai_translation_unavailable'] = 'La traduction OpenAI des Storefronts n’est pas disponible.';
$string['commerce_storefront_ai_translation_unavailable_help'] = 'Activez la traduction IA des Storefronts dans les réglages du plugin et vérifiez que la clé API OpenAI et le modèle sont configurés.';
$string['commerce_storefront_ai_translation_no_content'] = 'Aucun champ textuel traduisible n’a été trouvé dans la locale source.';
$string['commerce_storefront_ai_translation_too_many_fields'] = 'La locale contient trop de champs traduisibles pour une seule opération (maximum : {$a}).';
$string['commerce_storefront_ai_translation_preview_expired'] = 'Cette prévisualisation de traduction a expiré. Générez-en une nouvelle.';
$string['commerce_storefront_ai_translation_preview_title'] = 'Prévisualisation de la traduction';
$string['commerce_storefront_ai_translation_preview_summary'] = '{$a->source} → {$a->target} : {$a->count} champ(s) modifié(s), modèle {$a->model}. Rien n’est encore enregistré.';
$string['commerce_storefront_ai_translation_source_text'] = 'Source';
$string['commerce_storefront_ai_translation_target_text'] = 'Traduction';
$string['commerce_storefront_ai_translation_apply'] = 'Appliquer les traductions';
$string['commerce_storefront_ai_translation_applied'] = 'Les traductions ont été appliquées à la locale.';
$string['subscriptions:manage_showrooms'] = 'Gérer les showrooms Commerce';
$string['commerce_showroom_status_workflow_only'] = 'Le statut est contrôlé par le workflow de révision et de publication.';
$string['commerce_showroom_publish_requires_block'] = 'Ce showroom ne peut pas être publié tant qu’au moins un bloc n’est pas activé.';
$string['commerce_showroom_invalid_transition'] = 'Cette transition de statut du showroom n’est pas autorisée.';
$string['commerce_showroom_import_create'] = 'Créer depuis un import JSON';
$string['commerce_showroom_import_file'] = 'Fichier showroom JSON';
$string['commerce_showroom_import_or_paste'] = 'Ou collez manuellement le contenu JSON ci-dessous.';
$string['commerce_showroom_import_created_draft'] = 'Showroom importé et créé en brouillon.';
$string['commerce_showroom_import_media_warning'] = 'Le fichier JSON transporte la structure, les traductions et la configuration complète de tous les blocs. Les fichiers médias stockés par Moodle (images/vidéos) ne sont pas intégrés au JSON : leurs références doivent être vérifiées ou les médias réimportés avant publication en production.';
$string['commerce_showroom_export_portable'] = 'Exporter le package portable';
$string['commerce_showroom_export_json'] = 'Exporter le JSON';
$string['commerce_showroom_import_portable_help'] = 'Pour une migration DEV → PROD, utilisez de préférence le package .showroom.zip : il contient le JSON complet et tous les médias du showroom. L’import JSON seul reste disponible pour la configuration sans fichiers.';
$string['commerce_showroom_import_portable_done'] = 'Showroom portable importé en brouillon : {$a->blocks} blocs, {$a->media} médias, {$a->remapped} références remappées.';
$string['commerce_showroom_export_preflight_title'] = 'Préparation du package portable';
$string['commerce_showroom_export_preflight_media'] = 'Nombre de médias';
$string['commerce_showroom_export_preflight_total'] = 'Taille totale des médias';
$string['commerce_showroom_export_preflight_largest'] = 'Plus gros fichier';
$string['commerce_showroom_export_preflight_required'] = 'Espace temporaire recommandé';
$string['commerce_showroom_export_preflight_available'] = 'Espace temporaire disponible';
$string['commerce_showroom_export_preflight_ready'] = 'L’export peut démarrer. Les médias déjà compressés (images et vidéos) seront stockés dans le ZIP sans recompression.';
$string['commerce_showroom_export_portable_start'] = 'Créer et télécharger le package';
$string['commerce_showroom_export_insufficient_disk'] = 'Espace disque temporaire insuffisant. Requis : {$a->required}. Disponible : {$a->available}.';
$string['commerce_showroom_export_invalid_archive'] = 'Le package portable généré est invalide ou incomplet.';
$string['commerce_showroom_publish_requires_slug'] = 'Ce showroom ne peut pas être publié tant qu’au moins un slug public n’est pas configuré.';
$string['commerce_showroom_publish_slug_conflict'] = 'Le slug public « {$a} » est déjà utilisé par une autre route, un produit ou un showroom publié.';
$string['commerce_storefront_bundle_includes'] = 'Ce pack comprend';
// 7.95M1 - Profils CRM Commerce sans compte Moodle.
$string['crm_commerce_guest_profile'] = 'Fiche client Commerce';
$string['crm_commerce_guest_profile_description'] = 'Vue User360 d’un client ayant un historique d’achat Commerce ou Legacy sans compte Moodle associé.';
$string['crm_commerce_identity_type'] = 'Type d’identité';
$string['crm_commerce_identity_legacy_guest'] = 'Client Commerce / Legacy';
$string['crm_first_purchase'] = 'Premier achat';
$string['crm_no_moodle_account'] = 'Aucun compte Moodle associé';
$string['crm_commerce_native_history'] = 'Historique Commerce Native';
$string['crm_commerce_guest_no_actions'] = 'Aucune action disponible pour ce client.';
$string['crm_user_account_commerce_only'] = 'Client Commerce · sans compte Moodle';
$string['commerce_showroom_publish_integrity_failed'] = 'Le contrôle d’intégrité avant publication du showroom a échoué : {$a}.';
$string['commerce_storefront_access_bundle_contents'] = 'Accéder à mes contenus';
$string['commerce_storefront_back_to_showroom'] = 'Retour à la présentation';
$string['commerce_personal_offer_campaign_email_title'] = 'E-mail de la campagne';
$string['commerce_personal_offer_campaign_email_help'] = 'Configure le contenu marketing des e-mails Personal Offer. Le produit, les prix autoritatifs, la validité et le CTA sécurisé restent générés par Commerce.';
$string['commerce_personal_offer_campaign_email_saved'] = 'Configuration de l’e-mail de campagne enregistrée.';
$string['commerce_personal_offer_campaign_email_locked'] = 'Cette campagne a déjà été émise ou clôturée. Sa configuration e-mail est en lecture seule.';
$string['commerce_personal_offer_campaign_email_destination'] = 'Destination du CTA';
$string['commerce_personal_offer_campaign_email_destination_checkout'] = 'Checkout de l’offre personnelle';
$string['commerce_personal_offer_campaign_email_destination_showroom'] = 'Showroom';
$string['commerce_personal_offer_campaign_email_showroom'] = 'Showroom cible';
$string['commerce_personal_offer_campaign_email_showroom_choose'] = 'Choisir un Showroom publié compatible';
$string['commerce_personal_offer_campaign_email_showroom_help'] = 'Seuls les Showrooms publiés contenant le produit cible de la campagne sont proposés. Aucune URL publique n’est saisie manuellement.';
$string['commerce_personal_offer_campaign_email_content'] = 'Contenu de l’e-mail';
$string['commerce_personal_offer_campaign_email_content_help'] = 'Laisse une langue entièrement vide pour utiliser le fallback français, ou le mail Personal Offer historique si le français est également vide. Le contenu marketing et la conclusion utilisent l’éditeur riche Moodle ; le HTML est nettoyé avant enregistrement et au rendu e-mail.';
$string['commerce_personal_offer_campaign_email_variables'] = 'Variables dynamiques disponibles';
$string['commerce_personal_offer_campaign_email_subject'] = 'Objet';
$string['commerce_personal_offer_campaign_email_body'] = 'Contenu marketing';
$string['commerce_personal_offer_campaign_email_cta_label'] = 'Libellé du CTA';
$string['commerce_personal_offer_campaign_email_closing'] = 'Conclusion';
$string['commerce_personal_offer_campaign_email_manage'] = 'Configurer l’e-mail de campagne';
$string['commerce_personal_offer_campaign_email_fallback_active'] = 'Aucun contenu personnalisé n’est configuré. Le fallback historique Personal Offer reste actif.';
$string['commerce_personal_offer_campaign_email_languages_configured'] = 'Contenu personnalisé configuré pour : {$a}.';
$string['commerce_personal_offer_campaign_email_destination_summary'] = 'Destination du CTA';
$string['commerce_identity_bulk_execute'] = 'Réconcilier les achats correspondants';
$string['commerce_identity_bulk_execute_confirm'] = 'Cette action va écrire les réconciliations sélectionnées. Chaque achat sera contrôlé de nouveau au moment de l’exécution ; une identité devenue ambiguë ou modifiée ne sera jamais forcée.';
$string['commerce_identity_bulk_execute_result'] = '{$a->done} achat(s) sur {$a->total} sélectionné(s) ont été réconciliés.';
$string['commerce_identity_bulk_none_selected'] = 'Sélectionnez au moins un achat avec une correspondance unique.';
$string['commerce_identity_bulk_preview'] = 'Prévisualiser les réconciliations sélectionnées';
$string['commerce_identity_bulk_preview_description'] = 'Contrôlez chaque correspondance sélectionnée et l’impact attendu avant toute écriture.';
$string['commerce_identity_bulk_preview_title'] = 'Dry-run de réconciliation en masse';
$string['commerce_identity_bulk_preview_warning'] = 'Dry-run uniquement : aucune donnée n’a été modifiée. Seules les lignes ayant toujours une correspondance Moodle unique par email exact pourront être exécutées.';
$string['commerce_identity_dryrun_impact'] = 'Impact dry-run';
$string['commerce_identity_dryrun_impact_summary'] = '{$a->total} changement(s) : {$a->grants} grant(s), {$a->digital} accès digital, {$a->guests} session(s) guest, {$a->legacy} donnée(s) Legacy.';
$string['commerce_identity_filter_any'] = 'Rechercher dans email, référence ou données client';
$string['commerce_identity_filter_candidateuserid'] = 'ID utilisateur Moodle candidat';
$string['commerce_identity_filter_email_partial'] = 'Email contient';
$string['commerce_identity_filter_name'] = 'Nom du client contient';
$string['commerce_identity_filter_purchaseid'] = 'ID achat';
$string['commerce_identity_filter_reference'] = 'Référence d’achat contient';
$string['commerce_identity_filter_sku'] = 'SKU produit / référence article contient';
$string['commerce_identity_filter_status'] = 'Statut du diagnostic';
$string['commerce_identity_results_count'] = '{$a} achat(s) non rattaché(s) correspondent aux filtres.';
$string['commerce_identity_select'] = 'Sélection';
$string['commerce_identity_select_purchase'] = 'Sélectionner l’achat {$a}';
$string['commerce_identity_nav_label'] = 'Opérations d’identité';
$string['commerce_identity_nav_reconciliation'] = 'Réconciliation';
$string['commerce_identity_nav_similarities'] = 'Comptes similaires';
$string['commerce_identity_similarity_title'] = 'Comptes potentiellement similaires';
$string['commerce_identity_similarity_description'] = 'Repérez les comptes Moodle qui pourraient appartenir à la même personne avant toute fusion manuelle.';
$string['commerce_identity_similarity_manual_only'] = 'Ces correspondances sont uniquement des suggestions. Aucun compte n’est fusionné, modifié ou suspendu automatiquement.';
$string['commerce_identity_similarity_filter_query'] = 'Email, prénom ou nom';
$string['commerce_identity_similarity_filter_status'] = 'État du compte';
$string['commerce_identity_similarity_filter_minscore'] = 'Score minimum';
$string['commerce_identity_similarity_account_active'] = 'Actif';
$string['commerce_identity_similarity_account_suspended'] = 'Suspendu';
$string['commerce_identity_similarity_scan_summary'] = '{$a->users} comptes analysés · {$a->matches} correspondances proposées';
$string['commerce_identity_similarity_truncated'] = 'L’analyse a été limitée aux {$a} comptes les plus récemment actifs. Utilisez la recherche pour cibler une personne si nécessaire.';
$string['commerce_identity_similarity_empty'] = 'Aucun compte similaire n’a été détecté avec ces critères.';
$string['commerce_identity_similarity_score'] = 'Score';
$string['commerce_identity_similarity_account_first'] = 'Compte A';
$string['commerce_identity_similarity_account_second'] = 'Compte B';
$string['commerce_identity_similarity_signals'] = 'Signaux détectés';
$string['commerce_identity_similarity_reason_email_exact'] = 'Même email';
$string['commerce_identity_similarity_reason_email_local_exact'] = 'Identifiant email identique';
$string['commerce_identity_similarity_reason_email_local_close'] = 'Emails proches';
$string['commerce_identity_similarity_reason_name_exact'] = 'Même nom et prénom';
$string['commerce_identity_similarity_reason_name_reversed'] = 'Prénom / nom inversés';
$string['commerce_identity_similarity_reason_firstname_close'] = 'Prénoms proches';
$string['commerce_identity_similarity_reason_lastname_close'] = 'Noms proches';
$string['commerce_identity_similarity_reason_phone_exact'] = 'Même téléphone';
// Personal Offer Campaign Email — M3D preview/test send.
$string['commerce_personal_offer_campaign_email_preview'] = 'Prévisualiser l’e-mail';
$string['commerce_personal_offer_campaign_email_preview_help'] = 'Prévisualisation sûre avant émission, calculée avec les termes de campagne et les prix catalogue autoritatifs. Le CTA de prévisualisation reste dans l’administration et ne crée aucune offre.';
$string['commerce_personal_offer_campaign_email_preview_refresh'] = 'Actualiser';
$string['commerce_personal_offer_campaign_email_test_send'] = 'Envoyer un e-mail de test';
$string['commerce_personal_offer_campaign_email_test_sent'] = 'E-mail de test envoyé à {$a}.';
$string['commerce_identity_merge_blockers'] = 'Vérifications requises avant la fusion';
$string['commerce_identity_merge_blocker_pedagogy'] = 'Le compte à fusionner #{$a->userid} contient un historique pédagogique qui nécessite une vérification ({$a->count} élément(s)).';
$string['commerce_identity_merge_blocker_legacy_subscription'] = 'Le compte à fusionner #{$a->userid} contient {$a->count} donnée(s) commerciale(s) qui nécessitent une vérification.';
$string['commerce_identity_merge_blocker_already_merged'] = 'Le compte source #{$a->userid} a déjà été utilisé comme source d’une fusion précédente.';
$string['commerce_identity_merge_blocker_suspended_target'] = 'Le compte principal #{$a->userid} est suspendu.';
$string['commerce_identity_merge_blocker_generic'] = 'La fusion comporte un blocage nécessitant une vérification manuelle.';
$string['commerce_identity_merge_execution_warning'] = 'Cette action est transactionnelle mais irréversible depuis l’interface : l’état pédagogique, les possessions Legacy/Commerce et les données d’identité CRM seront consolidés dans le compte conservé, puis les comptes sources seront suspendus. Les journaux d’audit historiques sont préservés.';
$string['commerce_identity_merge_execution_confirm'] = 'Je confirme avoir vérifié la prévisualisation et vouloir fusionner définitivement ces comptes.';
$string['commerce_identity_merge_execute'] = 'Exécuter la fusion';
$string['commerce_identity_merge_confirmation_required'] = 'Vous devez confirmer explicitement la fusion.';
$string['commerce_identity_merge_execution_blocked'] = 'Cette fusion est bloquée car un ou plusieurs comptes contiennent des données qui ne peuvent pas être transférées automatiquement en toute sécurité.';
$string['commerce_identity_merge_execution_success'] = 'Fusion terminée : {$a->sources} compte(s) source(s) ont été rattachés au compte #{$a->targetuserid}. Référence d’audit : {$a->mergeuuid}.';
$string['privacy:metadata:identity_merge'] = 'Journal d’audit des fusions d’identités administratives.';
$string['privacy:metadata:identity_merge:targetuserid'] = 'Compte Moodle principal conservé.';
$string['privacy:metadata:identity_merge:performedby'] = 'Administrateur ayant exécuté la fusion.';
$string['privacy:metadata:identity_merge_source'] = 'Comptes Moodle sources participant à une fusion.';
$string['privacy:metadata:identity_merge_source:sourceuserid'] = 'Compte Moodle source fusionné.';
$string['privacy:metadata:identity_merge_source:sourceemail'] = 'Email historique du compte source au moment de la fusion.';
$string['crm_topbar_admin_general'] = 'Général';
$string['crm_topbar_admin_users'] = 'Utilisateurs';
$string['crm_topbar_admin_courses'] = 'Cours';
$string['crm_topbar_admin_grades'] = 'Notes';
$string['crm_topbar_admin_plugins'] = 'Plugins';
$string['crm_topbar_admin_appearance'] = 'Présentation';
$string['crm_topbar_admin_server'] = 'Serveur';
$string['crm_topbar_admin_reports'] = 'Rapports';
$string['crm_topbar_admin_development'] = 'Développement';
$string['crm_topbar_admin_shortcuts'] = 'Mes raccourcis admin';
$string['crm_topbar_admin_purge_caches'] = 'Purger les caches';
$string['crm_topbar_admin_maintenance_mode'] = 'Mode de maintenance';
$string['crm_topbar_admin_subscriptions_config'] = 'Configuration Commerce';
$string['crm_topbar_admin_campus_config'] = 'Configuration Campus';
$string['commerce_identity_nav_merge'] = 'Fusionner les identités';
$string['commerce_identity_merge_title'] = 'Fusion de comptes';
$string['commerce_identity_merge_ids'] = 'IDs Moodle à comparer';
$string['commerce_identity_merge_preview'] = 'Prévisualiser la fusion';
$string['commerce_identity_merge_select_account'] = 'Sélectionner le compte Moodle #{$a}';
$string['commerce_identity_merge_prepare'] = 'Préparer une fusion';
$string['commerce_identity_merge_accounts'] = 'Comptes comparés';
$string['commerce_identity_merge_keep'] = 'Conserver';
$string['commerce_identity_merge_account'] = 'Compte';
$string['commerce_identity_merge_pedagogy'] = 'Historique pédagogique';
$string['commerce_identity_merge_commerce'] = 'Données Commerce';
$string['commerce_identity_merge_account_quality'] = 'Qualité du compte';
$string['commerce_identity_merge_recommended'] = 'Recommandé';
$string['commerce_identity_merge_pedagogy_summary'] = '{$a->courses} cours inscrits
{$a->completedcourses} cours terminés
{$a->activities} activités complétées
{$a->grades} notes · moyenne {$a->average}%
Score pédagogique : {$a->score}';
$string['commerce_identity_merge_commerce_summary'] = '{$a->purchases} achats · {$a->grants} droits · {$a->digital} accès digitaux
Score Commerce : {$a->score}';
$string['commerce_identity_merge_confirmed'] = 'Compte confirmé';
$string['commerce_identity_merge_unconfirmed'] = 'Compte non confirmé';
$string['commerce_identity_merge_lastaccess'] = 'Dernier accès : {$a}';
$string['commerce_identity_merge_recalculate'] = 'Recalculer avec ce compte principal';
$string['commerce_identity_merge_virtual_profile'] = 'Profil final simulé';
$string['commerce_identity_merge_virtual_profile_summary'] = 'Compte principal : #{$a->userid} — {$a->name} — {$a->email}';
$string['commerce_identity_merge_transfer_summary'] = 'Transfert Commerce prévu : {$a->purchases} achats, {$a->grants} droits, {$a->digital} accès digitaux et {$a->guests} sessions guest.';
$string['commerce_identity_merge_warnings'] = 'Points d’attention';
$string['commerce_identity_merge_warning_pedagogical_history'] = 'Le compte à fusionner #{$a->userid} contient un historique pédagogique. Son état pédagogique pris en charge sera consolidé dans le compte conservé.';
$string['commerce_identity_merge_warning_shared_courses'] = 'Les comptes partagent {$a->count} cours. Les progressions, notes et tentatives peuvent entrer en conflit.';
$string['commerce_identity_merge_warning_different_emails'] = 'Les comptes utilisent des adresses email différentes. L’adresse du compte principal sera conservée.';
$string['commerce_identity_merge_warning_suspended_target'] = 'Le compte principal sélectionné #{$a->userid} est suspendu.';
$string['commerce_identity_merge_warning_generic'] = 'Un point d’attention nécessite une vérification manuelle.';
$string['commerce_identity_nav_provisioning'] = 'Créer des comptes';
$string['commerce_identity_provisioning_title'] = 'Créer des comptes pour les acheteurs digitaux';
$string['commerce_identity_provisioning_description'] = 'Créez un espace Moodle pour les acheteurs de produits digitaux Legacy qui n’ont pas encore de compte, avec dry-run et contrôle des comptes similaires.';
$string['commerce_identity_provisioning_safety'] = 'Aucune création n’est effectuée pendant le dry-run. Les comptes exacts existants et les identités ambiguës ne sont jamais recréés. Les comptes similaires nécessitent une confirmation explicite pour forcer la création.';
$string['commerce_identity_provisioning_filter_query'] = 'Email, prénom ou nom';
$string['commerce_identity_provisioning_filter_status'] = 'État';
$string['commerce_identity_provisioning_email'] = 'Email';
$string['commerce_identity_provisioning_identity'] = 'Identité';
$string['commerce_identity_provisioning_purchases'] = 'Achats Legacy';
$string['commerce_identity_provisioning_status'] = 'Diagnostic';
$string['commerce_identity_provisioning_details'] = 'Détails';
$string['commerce_identity_provisioning_override'] = 'Forçage';
$string['commerce_identity_provisioning_status_creatable'] = 'Créable sans ambiguïté';
$string['commerce_identity_provisioning_status_existing'] = 'Compte Moodle existant';
$string['commerce_identity_provisioning_status_ambiguous'] = 'Plusieurs comptes exacts';
$string['commerce_identity_provisioning_status_similar'] = 'Compte similaire à examiner';
$string['commerce_identity_provisioning_status_invalid'] = 'Email invalide';
$string['commerce_identity_provisioning_existing_user'] = 'Compte existant : #{$a}. Utilisez la réconciliation plutôt que de créer un doublon.';
$string['commerce_identity_provisioning_ambiguous_users'] = 'Plusieurs comptes existent pour cet email : {$a}. Vérification manuelle obligatoire.';
$string['commerce_identity_provisioning_preview_selected'] = 'Prévisualiser les créations sélectionnées';
$string['commerce_identity_provisioning_dryrun_title'] = 'Dry-run des créations';
$string['commerce_identity_provisioning_force_similar'] = 'Créer malgré le compte similaire';
$string['commerce_identity_provisioning_confirm'] = 'Je confirme avoir vérifié ce dry-run et les éventuelles similarités.';
$string['commerce_identity_provisioning_execute'] = 'Créer les comptes confirmés';
$string['commerce_identity_provisioning_confirmation_required'] = 'Vous devez confirmer explicitement la création des comptes.';
$string['commerce_identity_provisioning_execution_summary'] = 'Créés : {$a->created} · ignorés/bloqués : {$a->skipped} · erreurs : {$a->errors}';
$string['commerce_identity_provisioning_scan_truncated'] = 'L’analyse est limitée aux {$a} achats Legacy les plus récents. Utilisez le filtre pour cibler une identité.';
$string['commerce_legacy_account_activation_title'] = 'Activez votre espace CampusFR';
$string['commerce_legacy_account_activation_intro'] = 'Bonjour {$a->firstname}, nous avons créé votre espace CampusFR pour regrouper vos achats et ressources associés à {$a->email}. Choisissez simplement votre mot de passe pour y accéder.';
$string['commerce_legacy_account_activation_submit'] = 'Activer mon espace';
$string['commerce_legacy_account_activation_invalid'] = 'Ce lien d’activation est invalide ou a expiré.';
$string['commerce_legacy_account_activation_failed'] = 'Impossible d’activer votre espace CampusFR.';

$string['commerce_personal_offer_validity_title'] = 'Validité des offres';
$string['commerce_personal_offer_validity_help'] = 'Choisissez une échéance commune à toute la campagne ou une durée individuelle calculée à partir de l’émission de chaque offre.';
$string['commerce_personal_offer_validity_mode'] = 'Mode de validité';
$string['commerce_personal_offer_validity_fixed'] = 'Date et heure fixes';
$string['commerce_personal_offer_validity_duration'] = 'Durée après émission';
$string['commerce_personal_offer_validity_duration_value'] = 'Durée';
$string['commerce_personal_offer_validity_duration_unit'] = 'Unité';
$string['commerce_personal_offer_validity_hours'] = 'Heures';
$string['commerce_personal_offer_validity_duration_help'] = 'La durée commence à l’émission de l’offre. Un simple renvoi du même e-mail ne prolonge pas l’échéance.';
$string['commerce_personal_offer_validity_timezone'] = 'Fuseau horaire';
$string['commerce_personal_offer_validity_timezone_help'] = 'Utilisé pour interpréter et afficher les dates/heures fixes. Europe/Paris est recommandé pour les campagnes CampusFR.';

$string['admin_event_user_legacy_digital_provisioned'] = 'Accès numérique Legacy provisionné';

$string['commerce_mail_personal_offer_direct_checkout'] = 'Payer directement';

$string['commerce_personal_offer_workflow_title'] = 'Préparation de la campagne';
$string['commerce_personal_offer_workflow_help'] = 'Suivez les étapes de préparation avant l’émission des offres. Les contrôles affichés reflètent l’état réel de la campagne.';
$string['commerce_personal_offer_workflow_commercial'] = 'Offre commerciale';
$string['commerce_personal_offer_workflow_commercial_ready'] = 'Produit, termes commerciaux et validité enregistrés.';
$string['commerce_personal_offer_workflow_email'] = 'E-mail et parcours';
$string['commerce_personal_offer_workflow_email_ready'] = 'Contenu personnalisé et destination configurés.';
$string['commerce_personal_offer_workflow_email_missing'] = 'Configurez le contenu e-mail et sa destination avant l’envoi.';
$string['commerce_personal_offer_workflow_audience'] = 'Audience';
$string['commerce_personal_offer_workflow_audience_ready'] = '{$a} destinataire(s) éligible(s) actuellement.';
$string['commerce_personal_offer_workflow_audience_missing'] = 'Prévisualisez la campagne pour calculer les destinataires éligibles.';
$string['commerce_personal_offer_workflow_snapshot'] = 'Sélection figée';
$string['commerce_personal_offer_workflow_snapshot_ready'] = 'La sélection des destinataires est figée.';
$string['commerce_personal_offer_workflow_snapshot_missing'] = 'Créez le snapshot après contrôle de l’audience.';
$string['commerce_personal_offer_workflow_issue'] = 'Émission des offres';
$string['commerce_personal_offer_workflow_issue_ready'] = 'Les offres personnelles ont été générées.';
$string['commerce_personal_offer_workflow_issue_missing'] = 'Étape finale après validation du snapshot.';
$string['commerce_personal_offer_workflow_configure_email'] = 'Configurer l’e-mail';
$string['commerce_personal_offer_workflow_preview_test'] = 'Prévisualiser / envoyer un test';
$string['commerce_personal_offer_workflow_view_audience'] = 'Voir l’audience';
$string['commerce_personal_offer_workflow_showroom'] = 'Showroom';
$string['commerce_personal_offer_workflow_direct_checkout_also'] = 'paiement direct également disponible dans l’e-mail';
$string['commerce_personal_offer_campaign_email_saved_preview_next'] = 'Configuration e-mail enregistrée. Vérifiez maintenant la prévisualisation et envoyez un e-mail de test avant l’émission.';

$string['commerce_personal_offer_campaign_banner_title'] = 'Bannière de l’e-mail';
$string['commerce_personal_offer_campaign_banner_help'] = 'Ajoutez une bannière propre à cette campagne. Elle remplace le bandeau CampusFR par défaut uniquement dans les e-mails de cette campagne.';
$string['commerce_personal_offer_campaign_banner_file'] = 'Image de bannière';
$string['commerce_personal_offer_campaign_banner_format_help'] = 'JPEG, PNG ou WebP, 8 Mo maximum. Pour un rendu optimal, utilisez une image horizontale d’environ 1600 × 440 px.';
$string['commerce_personal_offer_campaign_banner_delete'] = 'Supprimer la bannière personnalisée et revenir au bandeau par défaut';
$string['commerce_personal_offer_campaign_banner_upload_error'] = 'Impossible de téléverser la bannière de la campagne.';
$string['commerce_personal_offer_campaign_banner_too_large'] = 'La bannière de la campagne dépasse la taille maximale de 8 Mo.';
$string['commerce_personal_offer_campaign_banner_invalid_type'] = 'La bannière doit être une image JPEG, PNG ou WebP.';
$string['commerce_identity_similarity_reason_email_name_combination'] = 'Email proche + même nom';
$string['commerce_identity_legacy_link_action'] = 'Rattacher à ce compte';
$string['commerce_identity_legacy_link_title'] = 'Rattacher une identité Legacy à un compte Moodle';
$string['commerce_identity_legacy_link_description'] = 'Conservez le compte Moodle existant et rattachez-lui les achats digitaux Legacy d’une autre identité, sans modifier son email ni sa progression pédagogique.';
$string['commerce_identity_legacy_link_dryrun'] = 'Dry-run : aucune donnée n’est modifiée. Vérifiez soigneusement les deux identités avant de confirmer.';
$string['commerce_identity_legacy_link_source'] = 'Identité Legacy Digital';
$string['commerce_identity_legacy_link_target'] = 'Compte Moodle conservé';
$string['commerce_identity_legacy_link_purchase_count'] = '{$a} achat(s) Legacy Digital';
$string['commerce_identity_legacy_link_preserves_target'] = 'Le compte Moodle cible est conservé tel quel : email, mot de passe, inscriptions, progression, notes et historique pédagogique ne sont pas modifiés. Seul le userid des achats Legacy Digital non rattachés est renseigné.';
$string['commerce_identity_legacy_link_confirm'] = 'Je confirme que ces deux identités appartiennent à la même personne et que ce compte Moodle doit être conservé.';
$string['commerce_identity_legacy_link_execute'] = 'Rattacher les achats au compte Moodle';
$string['commerce_identity_legacy_link_success'] = '{$a->count} achat(s) Legacy Digital ont été rattachés au compte Moodle #{$a->userid}.';
$string['commerce_identity_legacy_link_no_purchases'] = 'Aucun achat Legacy Digital non rattaché n’a été trouvé pour cet email.';
$string['commerce_identity_legacy_link_similarity_too_low'] = 'Le niveau de similarité est insuffisant pour autoriser ce rattachement. Vérifiez les identités manuellement.';
$string['commerce_identity_legacy_link_confirmation_required'] = 'Vous devez confirmer explicitement que les deux identités appartiennent à la même personne.';
$string['commerce_personal_offer_correct_beneficiary'] = 'Corriger le bénéficiaire';
$string['commerce_personal_offer_correct_beneficiary_help'] = 'Utilisez cette réparation exceptionnelle uniquement pour une offre personnelle émise, non utilisée et dont l’e-mail n’a pas été envoyé. La clé du snapshot de campagne et le jeton sécurisé de l’offre sont conservés.';
$string['commerce_personal_offer_correct_beneficiary_current'] = 'Bénéficiaire actuel';
$string['commerce_personal_offer_correct_beneficiary_email'] = 'E-mail correct du compte Moodle';
$string['commerce_personal_offer_correct_beneficiary_preview'] = 'Prévisualiser la correction';
$string['commerce_personal_offer_correct_beneficiary_preview_title'] = 'Prévisualisation de la correction d’identité';
$string['commerce_personal_offer_correct_beneficiary_confirm'] = 'Confirmer la correction du bénéficiaire';
$string['commerce_personal_offer_correct_beneficiary_success'] = 'Le bénéficiaire de l’offre personnelle a été corrigé et l’e-mail non envoyé a été remis en file d’attente lorsque nécessaire.';
$string['commerce_personal_offer_correct_beneficiary_unavailable'] = 'Le bénéficiaire de cette offre personnelle ne peut plus être corrigé en toute sécurité.';
$string['commerce_personal_offer_correct_beneficiary_user_not_unique'] = 'Un seul compte Moodle actif doit correspondre à cette adresse e-mail.';

// Commerce 7.95 M5.1 — Product Statistics 2.0.
$string['commerce_statistics_period_custom'] = 'Plage personnalisée';
$string['commerce_m51_title'] = 'Performance du produit';
$string['commerce_m51_subtitle'] = 'Ventes encaissées, délivrances réelles et santé des paiements. Les paiements en attente ne sont jamais comptés comme ventes.';
$string['commerce_m51_paid_orders'] = 'Commandes payées';
$string['commerce_m51_paid_orders_help'] = 'Nombre de commandes contenant ce produit avec un paiement confirmé.';
$string['commerce_m51_units_sold'] = 'Unités vendues';
$string['commerce_m51_units_sold_help'] = 'Somme des quantités du produit dans les commandes payées.';
$string['commerce_m51_manual_grants'] = 'Attributions gratuites';
$string['commerce_m51_manual_grants_help'] = 'Délivrances administratives CRM sans achat. Elles sont indépendantes de la devise.';
$string['commerce_m51_total_delivered'] = 'Total délivré';
$string['commerce_m51_total_delivered_help'] = 'Unités vendues + commandes gratuites finalisées + attributions administratives.';
$string['commerce_m51_payment_pending'] = 'Paiements en attente';
$string['commerce_m51_payment_pending_help'] = 'Dernière tentative de paiement encore en attente. Non comptée comme vente.';
$string['commerce_m51_payment_failed'] = 'Paiements échoués';
$string['commerce_m51_payment_failed_help'] = 'Dernière tentative refusée, échouée ou expirée.';
$string['commerce_m51_payment_cancelled'] = 'Paiements annulés';
$string['commerce_m51_payment_cancelled_help'] = 'Dernière tentative explicitement annulée.';
$string['commerce_m51_revenue_collected'] = 'Chiffre d’affaires encaissé';
$string['commerce_m51_revenue_evolution'] = 'Évolution du CA encaissé';
$string['commerce_m51_deliveries_evolution'] = 'Délivrances du produit';
$string['commerce_m51_delivery_paid'] = 'Acheté et payé';
$string['commerce_m51_delivery_free_order'] = 'Commande gratuite';
$string['commerce_m51_delivery_manual'] = 'Attribution administrative';
$string['commerce_m51_payment_paid'] = 'Payé';
$string['commerce_m51_payment_refunded'] = 'Remboursé';
$string['commerce_m51_payment_distribution'] = 'Répartition des statuts de paiement';
$string['commerce_m51_from'] = 'Du';
$string['commerce_m51_until'] = 'Au';
$string['commerce_m51_export_excel'] = 'Exporter vers Excel';
$string['commerce_m51_export_summary'] = 'Résumé';
$string['commerce_m51_export_orders'] = 'Commandes';
$string['commerce_m51_export_deliveries'] = 'Attributions';

// Commerce 7.95 M5.1G — comparison trends.
$string['commerce_m51_comparison_previous'] = 'Évolution comparée à la période précédente de même durée : du {$a->from} au {$a->until}.';
$string['commerce_m51_comparison_today'] = 'Évolution comparée à hier sur la même plage horaire : du {$a->from} au {$a->until}.';
$string['commerce_m51_trend_new'] = 'Nouveau';
$string['commerce_m51_show_chart_data'] = "Afficher les données du graphique";

// Commerce 7.95 M5.2 — product steering analytics.
$string['commerce_m52_revenue_period'] = 'CA par période';
$string['commerce_m52_revenue_cumulative'] = 'CA cumulé';
$string['commerce_m52_revenue_display'] = 'Mode d’affichage du chiffre d’affaires';
$string['commerce_m52_average_order'] = 'Panier moyen encaissé';
$string['commerce_m52_payment_quality'] = 'Paiements';
$string['commerce_m52_success_rate'] = 'Taux de paiement réussi';
$string['commerce_m52_funnel'] = 'Parcours de conversion';
$string['commerce_m52_attempts'] = 'Tentatives de paiement';
$string['commerce_m52_confirmed_payments'] = 'Paiements confirmés';
$string['commerce_m52_deliveries'] = 'Unités délivrées';
$string['commerce_m52_acquisition_origin'] = 'Origine des acquisitions';
$string['commerce_m52_acq_standard'] = 'Achat standard';
$string['commerce_m52_acq_promotion'] = 'Promotion';
$string['commerce_m52_acq_personaloffer'] = 'Offre personnelle';
$string['commerce_m52_acq_free'] = 'Commande gratuite';
$string['commerce_m52_acq_manual'] = 'Attribution admin';
$string['commerce_m52_provider_distribution'] = 'Paiements encaissés par provider';
$string['commerce_m52_provider_orders'] = '{$a} commande(s)';
$string['commerce_m52_export_payments'] = 'Paiements';

// Commerce 7.95 M5.3 — premium global statistics.
$string['commerce_m53_export_excel'] = 'Exporter vers Excel';
$string['commerce_m53_paid_orders'] = 'Commandes payées';
$string['commerce_m53_paid_customers'] = 'Clients payants';
$string['commerce_m53_units_sold'] = 'Unités vendues';
$string['commerce_m53_total_delivered'] = 'Total délivré';
$string['commerce_m53_revenue_collected'] = 'CA encaissé';
$string['commerce_m53_average_order'] = 'Panier moyen';
$string['commerce_m53_payment_success_rate'] = 'Taux de paiement réussi';
$string['commerce_m53_pending_fulfillments'] = 'Délivrances en attente';
$string['commerce_m53_funnel'] = 'Parcours global de conversion';
$string['commerce_m53_payment_attempts'] = 'Tentatives de paiement';
$string['commerce_m53_commercial_evolution'] = 'Évolution commerciale';
$string['commerce_m53_revenue_evolution'] = 'Évolution du CA encaissé';
$string['commerce_m53_paid_orders_evolution'] = 'Évolution des commandes payées';
$string['commerce_m53_payment_health'] = 'Santé des paiements';
$string['commerce_m53_payment_distribution'] = 'Répartition des statuts de paiement';
$string['commerce_m53_export_summary'] = 'Synthèse';
$string['commerce_m53_export_orders'] = 'Commandes';
$string['commerce_m53_export_grants'] = 'Attributions';
$string['commerce_statistics_period_from'] = 'Du';
$string['commerce_statistics_period_until'] = 'Au';

// Commerce 7.95 M5.3B — branched payment journeys.
$string['commerce_m53_payment_journey'] = 'Parcours des paiements';
$string['commerce_m53_payment_journey_help'] = 'Une tentative aboutit à un seul état courant. Les branches sont exclusives : elles ne doivent donc jamais additionner plus de tentatives que la racine.';
$string['commerce_m53_payment_not_completed'] = 'Non aboutis';
$string['commerce_m53_global_conversion'] = 'Conversion globale : {$a->rate} % ({$a->paid} paiements aboutis sur {$a->attempts} tentatives).';
$string['commerce_m53_deliveries_breakdown'] = 'Délivrances';
$string['commerce_m53_delivered_from_paid'] = 'Issus de commandes payées';
$string['commerce_m53_delivered_from_free'] = 'Issus de commandes gratuites';
$string['commerce_m53_delivered_from_manual'] = 'Attributions administratives';
$string['commerce_m53_acquisition_help'] = 'Cette ventilation explique comment les unités ont été acquises. Elle est indépendante du statut courant du paiement.';
$string['commerce_m53_product_payments'] = 'Paiements par produit';
$string['commerce_m53_product_payments_help'] = 'Chaque produit présente la répartition du dernier état de paiement de ses commandes. Les catégories sont mutuellement exclusives.';
$string['commerce_m53_product'] = 'Produit';
$string['commerce_m53_conversion'] = 'Conversion';

// 7.95M6 — Legacy digital identity quality and correction.
$string['admin_event_user_legacy_digital_identity_updated'] = 'Coordonnées Legacy Digital corrigées';
$string['commerce_identity_email_quality_invalid'] = 'Email invalide';
$string['commerce_identity_email_quality_ok'] = 'Aucune anomalie détectée';
$string['commerce_identity_email_quality_suspect'] = 'Email suspect';
$string['commerce_identity_legacy_edit_description'] = 'Modifiez les coordonnées utilisées par les achats digitaux Legacy et par les futures sélections d’offres personnelles.';
$string['commerce_identity_legacy_edit_detected'] = 'Une faute probable a été détectée. Adresse suggérée : {$a}';
$string['commerce_identity_legacy_edit_scope_notice'] = 'Cette opération modifie les données Legacy Digital uniquement. Elle ne change jamais automatiquement l’email du compte Moodle. Les offres personnelles déjà émises restent inchangées et doivent être corrigées ou réémises séparément si nécessaire.';
$string['commerce_identity_legacy_edit_success'] = '{$a} enregistrement(s) Legacy Digital mis à jour.';
$string['commerce_identity_legacy_edit_title'] = 'Corriger les coordonnées Legacy Digital';
$string['commerce_identity_legacy_edit_update_same'] = 'Appliquer aussi cette correction à tous les autres achats Legacy Digital portant exactement l’ancien email';
$string['commerce_identity_legacy_quality_customer'] = 'Client Legacy';
$string['commerce_identity_legacy_quality_description'] = 'Repérez les coordonnées potentiellement erronées des acheteurs Legacy Digital et corrigez leur email, prénom ou nom directement à la source.';
$string['commerce_identity_legacy_quality_diagnostic'] = 'Diagnostic';
$string['commerce_identity_legacy_quality_empty'] = 'Aucune adresse ne correspond à ce filtre.';
$string['commerce_identity_legacy_quality_filter'] = 'Qualité de l’email';
$string['commerce_identity_legacy_quality_latest_purchase'] = 'Dernier achat : #{$a}';
$string['commerce_identity_legacy_quality_notice'] = 'Le diagnostic est volontairement conservateur : il signale les syntaxes invalides et les domaines très proches de fournisseurs connus (par exemple gmai.com → gmail.com). Les domaines personnalisés inconnus ne sont pas considérés comme des erreurs.';
$string['commerce_identity_legacy_quality_purchase'] = 'Achat Legacy';
$string['commerce_identity_legacy_quality_purchase_count'] = '{$a} achat(s) avec cet email';
$string['commerce_identity_legacy_quality_search'] = 'Rechercher par email, prénom ou nom';
$string['commerce_identity_legacy_quality_suggestion'] = 'Suggestion : {$a}';
$string['commerce_identity_legacy_quality_title'] = 'Qualité des emails Legacy Digital';
$string['commerce_identity_nav_legacy_quality'] = 'Qualité emails Legacy';

$string['commerce_identity_similarity_reason_email_domain_close'] = 'Domaine e-mail proche (faute possible)';
$string['commerce_identity_similarity_reason_alternate_name'] = 'Nom alternatif / phonétique correspondant';
$string['commerce_identity_similarity_score_help'] = 'Le score est un indice explicable, pas une décision de fusion automatique. Les badges indiquent la contribution des signaux.';

// Commerce 7.95 M7.3/M7.4 — manual merge selection and preview.
$string['commerce_identity_merge_description'] = 'Recherchez et sélectionnez manuellement des comptes Moodle, comparez-les, choisissez le compte principal puis exécutez uniquement les transferts certifiés sûrs.';
$string['commerce_identity_merge_dryrun_only'] = 'La sélection et la prévisualisation ne modifient aucune donnée. La fusion n’est exécutée qu’après confirmation explicite et lorsque tous les contrôles de sécurité sont satisfaits.';
$string['commerce_identity_merge_nonmergeable'] = 'La progression pédagogique et les données commerciales prises en charge seront consolidées avant la désactivation des anciens comptes. Les journaux et audits historiques conservent volontairement leurs références d’origine ; les comptes privilégiés restent protégés contre la fusion.';
$string['commerce_identity_merge_manual_selection_title'] = 'Sélection manuelle des comptes';
$string['commerce_identity_merge_manual_selection_help'] = 'Recherchez n’importe quel compte Moodle par ID, nom, prénom, identifiant ou e-mail. Cette sélection est indépendante du moteur de comptes similaires.';
$string['commerce_identity_merge_search_label'] = 'Rechercher un compte à ajouter';
$string['commerce_identity_merge_search_placeholder'] = 'Ex. 847, natalia@example.com, Natalia Kutrowski…';
$string['commerce_identity_merge_search_results'] = 'Résultats de recherche';
$string['commerce_identity_merge_search_empty'] = 'Aucun compte Moodle correspondant n’a été trouvé.';
$string['commerce_identity_merge_add_account'] = 'Ajouter à la fusion';
$string['commerce_identity_merge_reset_selection'] = 'Réinitialiser la sélection';
$string['commerce_identity_merge_select_two_hint'] = 'Sélectionnez au moins deux comptes pour pouvoir prévisualiser une fusion.';
$string['commerce_identity_merge_direction_sources'] = 'Compte(s) source(s)';
$string['commerce_identity_merge_direction_target'] = 'Compte principal conservé';

$string['commerce_identity_merge_blocker_privileged'] = 'Le compte #{$a->userid} possède des droits privilégiés ou système. Il ne peut pas participer à cette fusion.';
$string['commerce_identity_merge_m756_scope_title'] = 'Données prises en charge par la fusion';
$string['commerce_identity_merge_m756_scope_detail'] = 'La prévisualisation a détecté {$a->learning} élément(s) pédagogiques et {$a->commerce} élément(s) commerciaux sur les comptes à fusionner. Ils seront consolidés avant la désactivation des anciens comptes.';
$string['commerce_identity_merge_conflicts_title'] = 'Arbitrages pédagogiques';
$string['commerce_identity_merge_conflicts_help'] = 'Les éléments ci-dessous existent sur les deux comptes avec des états différents. Choisissez individuellement la donnée à conserver. Les unions sans ambiguïté sont fusionnées automatiquement.';
$string['commerce_identity_merge_conflict_grade'] = 'Note — élément Moodle #{$a->id}';
$string['commerce_identity_merge_conflict_activity'] = 'Progression — activité Moodle #{$a->id}';
$string['commerce_identity_merge_conflict_recommended'] = 'Choix recommandé : compte {$a}. La recommandation reste modifiable.';
$string['commerce_identity_merge_conflict_choice'] = 'Compte {$a->letter} — utilisateur #{$a->userid} — valeur : {$a->value}';

$string['commerce_identity_merge_certification_failed'] = 'La vérification d’intégrité après fusion a échoué. Aucune modification n’a été conservée.';

$string['commerce_identity_merge_certification_title'] = 'Fusion certifiée';

$string['commerce_identity_merge_certification_summary'] = '{$a->checks} contrôle(s) d’intégrité validé(s). {$a->decisions} décision(s) pédagogique(s) manuelle(s) enregistrée(s).';

$string['commerce_identity_merge_certification_primary_account_active'] = 'Compte principal actif et accessible.';

$string['commerce_identity_merge_certification_merged_account_suspended'] = '{$a} ancien(s) compte(s) correctement désactivé(s).';

$string['commerce_identity_merge_certification_ownership_transferred'] = '{$a} contrôle(s) de propriété commerciale validé(s) : aucune donnée prise en charge ne reste rattachée aux anciens comptes.';

$string['commerce_identity_merge_certification_learning_state_transferred'] = '{$a} contrôle(s) pédagogiques validé(s) : la progression prise en charge est rattachée au compte conservé.';

$string['commerce_identity_merge_certification_manual_learning_decision_applied'] = '{$a} arbitrage(s) pédagogique(s) manuel(s) appliqué(s) et vérifié(s).';

$string['commerce_identity_merge_certification_customer_email_aligned'] = '{$a} contrôle(s) d’identité commerciale validé(s) : les droits actifs utilisent l’adresse du compte conservé.';

$string['commerce_identity_merge_certification_audit'] = 'Référence d’audit : {$a}. Le détail des transferts et des décisions manuelles est conservé dans l’historique de fusion.';

$string['user360_merge_history_title'] = 'Historique des fusions';

$string['user360_merge_history_description'] = 'Historique certifié des comptes fusionnés avec cette identité.';

$string['user360_merge_certified'] = 'Fusion certifiée';

$string['user360_merge_completed'] = 'Fusion terminée';

$string['user360_merge_retained_account'] = 'Ce compte est le compte conservé.';

$string['user360_merge_absorbed_accounts'] = 'Comptes fusionnés :';

$string['user360_merge_absorbed_notice'] = 'Ce compte a été fusionné dans un autre compte.';

$string['user360_merge_open_retained'] = 'Ouvrir le compte conservé';

$string['user360_merge_summary'] = '{$a->transfers} élément(s) transféré(s) · {$a->decisions} décision(s) manuelle(s) · {$a->checks} contrôle(s) validé(s)';

$string['user360_merge_performed_by'] = 'Fusion effectuée par {$a}.';

$string['user360_merge_audit_reference'] = 'Référence d’audit : {$a}';

$string['user360_merge_view_details'] = 'Voir le détail des transferts';

$string['user360_merge_transfer_accounts'] = 'Comptes désactivés';

$string['user360_merge_transfer_notes'] = 'Notes CRM';

$string['user360_merge_transfer_scores'] = 'Scores CRM';

$string['user360_merge_transfer_inbox'] = 'Contacts Inbox';

$string['user360_merge_transfer_tags'] = 'Tags';

$string['user360_merge_transfer_tags_deduplicated'] = 'Tags dédupliqués';

$string['user360_merge_transfer_learning'] = 'Données pédagogiques';

$string['user360_merge_transfer_legacy'] = 'Données Legacy';

$string['user360_merge_transfer_commerce'] = 'Données Commerce';

// Réconciliation des paiements Alfa.
$string['commerce_alfa_reconciliation_payment_not_found'] = 'La tentative de paiement Commerce est introuvable.';
$string['commerce_alfa_reconciliation_attempt_not_found'] = 'Aucune tentative de paiement Alfa n’a été trouvée pour cet achat.';
$string['commerce_alfa_reconciliation_wrong_provider'] = 'Ce paiement n’est pas un paiement Alfa et ne peut pas utiliser la réconciliation Alfa.';
$string['commerce_alfa_reconciliation_missing_orderid'] = 'La référence de commande Alfa est absente ; le paiement ne peut pas être réconcilié de façon sûre.';
$string['commerce_alfa_reconciliation_not_safe'] = 'La réconciliation Alfa a été refusée car les données Alfa et Campus ne concordent pas suffisamment : {$a}';
