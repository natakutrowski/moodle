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
$string['status']                 = 'Statut';
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
$string['actions']          = '🛠️ Actions';
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
$string['delete']                 = 'Supprimer';
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

$string['close'] = 'Fermer';

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

$string['details']               = 'Détails';
$string['subscription_details']  = 'Détails de l’achat';

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

$string['upgrade_window_label']       = 'Fenêtre de calcul : {$a}';
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

$string['subfield_id']                = 'ID';
$string['subfield_userid']            = 'ID utilisateur';
$string['subfield_planid']            = 'ID plan';
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

$string['stripe:missingamount']                 = 'Montant manquant sur la demande de paiement.';
$string['stripe:productname']                   = 'Plan {$a}';
$string['stripe:missingpriceidforsubscription'] = 'stripe_price_id manquant pour l’abonnement.';
$string['stripe:missingpriceid']                = 'price_id manquant.';
$string['stripe:sdkautoloadnotfound']           = 'Autoload du SDK Stripe introuvable à {$a}.';
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

$string['alfa_missing_api_base'] = 'URL de base de l’API Alfa manquante.';
$string['alfa_rub_only']         = 'Alfa (token) est configuré uniquement pour la devise RUB.';
$string['alfa_register_error']   = 'Échec de l’initialisation du paiement : {$a}';
$string['alfa_missing_formurl']  = 'Paiement initialisé mais la banque n’a pas renvoyé d’URL de paiement.';
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

$string['err_cannot_determine_price'] = 'Impossible de déterminer le prix pour créer la demande de paiement.';
$string['err_no_redirect_url']        = 'L’initialisation du checkout n’a renvoyé aucune URL de redirection.';

$string['btn_signin'] = 'Se connecter';

$string['provider_alfa']   = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Manuel';
$string['provider_csv']    = 'CSV';
$string['provider_dev']    = 'Dev';
$string['provider_trial']  = 'Essai';

$string['configmissing']        = 'Configuration manquante : {$a}.';
$string['missing_customer_id']  = 'L’ID client Stripe est manquant.';
$string['invalidcsvupload']     = 'Le fichier CSV téléversé est invalide.';
$string['csvwritefail']         = 'Échec d’écriture du fichier CSV.';
$string['invalidpricecurrency'] = 'Combinaison prix/devise invalide.';
$string['plan_not_found']       = 'Plan d’abonnement introuvable.';
$string['scopenotfound']        = 'Périmètre d’accès introuvable.';
$string['scopedeleteinuse']     = 'Impossible de supprimer ce périmètre car il est utilisé.';
$string['plannotfound']         = 'Plan introuvable.';
$string['paymentgatewayerror']  = 'Erreur de passerelle de paiement : {$a}';

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

// Stripe
$string['stripe_secret_test']                 = 'Clé secrète (TEST)';
$string['stripe_publishable_test']            = 'Clé publique (TEST)';
$string['stripe_webhook_secret_test']         = 'Secret du webhook Stripe (TEST)';
$string['stripe_portal_configuration_id_test']= 'ID de configuration du portail Stripe (TEST)';
$string['stripe_portal_configuration_id_desc']= 'Optionnel : ID de configuration du portail client (ex. pc_xxx). Si vide, la configuration par défaut de Stripe sera utilisée.';

$string['stripe_secret_live']                 = 'Clé secrète (LIVE)';
$string['stripe_publishable_live']            = 'Clé publique (LIVE)';
$string['stripe_webhook_secret_live']         = 'Secret du webhook Stripe (LIVE)';
$string['stripe_portal_configuration_id_live']= 'ID de configuration du portail Stripe (LIVE)';

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

$string['pricing_missing_price'] = 'Aucun prix n’est défini pour ce plan et cette devise ({$a}).';
$string['cannot_purchase_trial_plan'] = 'Ce plan est un plan d’essai et ne peut pas être acheté.';
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
$string['error_price_title']   = 'Prix prévu';
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
$string['digital_success_summary_title'] = 'Résumé de votre achat';
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

$string['planentitlements'] = 'Droits d’accès des plans';
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

$string['planupgrades'] = 'Upgrades de plans';
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
$string['active'] = 'Actif';
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
$string['unlock_full_button'] = 'Passer à la version complète';

$string['restricted_access_title'] = 'Accès réservé';
$string['restricted_access_text'] = 'Acheter le cours pour débloquer cette activité.';
$string['buy'] = 'Acheter';

$string['plan_already_covered'] = 'Vous avez déjà un accès équivalent ou supérieur à ce contenu.';
$string['all_courses_owned_title'] = 'Vous avez déjà accès à tous les cours disponibles';
$string['all_courses_owned_text'] = 'Aucun nouvel achat n’est nécessaire pour le moment. Vous pouvez continuer votre apprentissage depuis votre espace cours.';

$string['unlock_subscriber_title'] = 'Activité réservée aux abonnés';
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
$string['digital_purchase_details'] = 'Détails de l’achat digital';
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
$string['crm_user_profile'] = 'Fiche utilisateur';
$string['crm_search_user_placeholder'] = 'Rechercher par nom, prénom ou email';
$string['crm_no_users_found'] = 'Aucun utilisateur trouvé.';
$string['crm_no_subscriptions'] = 'Aucun abonnement trouvé pour cet utilisateur.';
$string['crm_no_digital_purchases'] = 'Aucun achat digital trouvé pour cet utilisateur.';
$string['view_moodle_profile'] = 'Voir le profil Moodle';

$string['admin_card_crm_users_title'] = 'Utilisateurs CRM';
$string['admin_card_crm_users_desc'] = 'Rechercher un utilisateur et consulter sa fiche complète.';
$string['subscriptions'] = 'Abonnements';
$string['digital_purchases'] = 'Achats digitaux';
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
$string['adminlog_digital_link_resent'] = 'Lien digital renvoyé';

$string['adminlog_payment_request_created'] = 'Demande de paiement créée';
$string['adminlog_payment_request_paid'] = 'Demande de paiement payée';
$string['adminlog_payment_request_failed'] = 'Demande de paiement échouée';
$string['adminlog_payment_request_cancelled'] = 'Demande de paiement annulée';

$string['adminlog_trial_started'] = 'Essai démarré';
$string['adminlog_trial_expired'] = 'Essai expiré';

$string['change_user'] = 'Changer d’utilisateur';
$string['crm_accessible_courses'] = 'Cours accessibles';
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
$string['dashboard_stats_new_subscriptions'] = 'Nouveaux abonnements';
$string['dashboard_stats_digital_purchases'] = 'Achats digitaux';
$string['dashboard_stats_revenue'] = 'CA digital';
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

$string['crm_timeline_collapse_all'] = 'Replier / déplier les détails';
$string['crm_timeline_view_details'] = 'Voir détails';
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

$string['command_action_users_title'] = 'Voir les utilisateurs CRM';
$string['command_action_users_subtitle'] = 'Ouvrir la liste des utilisateurs et clients';

$string['command_action_products_title'] = 'Voir les produits digitaux';
$string['command_action_products_subtitle'] = 'Gérer les produits digitaux CampusFR';

$string['command_action_product_create_title'] = 'Créer un produit digital';
$string['command_action_product_create_subtitle'] = 'Ajouter un nouveau produit digital';

$string['command_action_purchases_title'] = 'Voir les achats digitaux';
$string['command_action_purchases_subtitle'] = 'Consulter les achats et paiements digitaux';

$string['command_action_subscriptions_title'] = 'Voir les abonnements';
$string['command_action_subscriptions_subtitle'] = 'Consulter et gérer les abonnements utilisateurs';

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