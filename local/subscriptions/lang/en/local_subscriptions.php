<?php
$string['pluginname'] = 'Subscriptions';

// -- Subscription config
// Plans
$string['plan_1month'] = '1 month';
$string['plan_3months'] = '3 months';
$string['plan_6months'] = '6 months';
$string['plan_1year'] = '1 year';
$string['plan_3years'] = '3 years';
$string['plan_lifetime'] = 'Lifetime';

// Access scopes
$string['access_full'] = 'All courses';
$string['access_a0'] = 'Course A0';
$string['access_a1'] = 'Course A1';
$string['access_a2'] = 'Course A2';
$string['access_test'] = 'Test course';

// Buttons
$string['btn_add_subscription'] = 'Add subscription';
$string['btn_manage_subscriptions'] = 'Manage subscriptions';
$string['btn_import_csv'] = 'Import subscriptions from CSV';

// -- Manage subscriptions
$string['manage_subscriptions'] = 'Manage subscriptions';
$string['updated_subscriptions'] = 'Updated {$a} subscription(s).';
$string['delete_subscriptions'] = '{$a} subscription(s) have been deleted.';
$string['no_active_subscriptions'] = 'No active subscriptions found';
$string['edit_subscriptions'] = 'Edit subscriptions';
$string['user'] = 'User';
$string['plan'] = 'Plan';
$string['access_scope'] = 'Access scope';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['status'] = 'Status';
$string['creation_date'] = 'Creation date';
$string['save_modifications'] = 'Save modifications';
$string['delete_selected'] = 'Delete selected subscriptions';
$string['popover_description'] = 'Description';
$string['popover_duration'] = 'Duration';
$string['popover_scope'] = 'Access scope';
$string['popover_courses'] = 'Courses';
$string['popover_no_courses'] = 'No courses defined';


// -- Add subscription
$string['add_subscription'] = 'Add subscription';
$string['unknown_user'] = 'Unknown user';
$string['sub_created'] = '{$a->user} has been subscribed to the <strong>{$a->plan}</strong> plan.';
$string['sub_exists'] = '{$a->user} subscription already exists ({$a->plan}).';
$string['sub_test_done'] = '{$a} has been subscribed to the test course.';
$string['select_user'] = 'Select a user';
$string['submit_sub'] = 'Subscribe to selected scope';
$string['submit_sub_test'] = 'Subscribe to test only';

// -- Import CSV
$string['import_subscriptions'] = 'Import subscriptions';
$string['import_subscriptions_csv'] = 'Import subscriptions from CSV file';
$string['email'] = 'Email';
$string['already_exists'] = 'Already exists';
$string['import_preview'] = 'Preview of subscriptions to import';
$string['confirm_import'] = 'Import subscriptions';
$string['select_csv_file'] = 'Select CSV file';
$string['submit_csv_file'] = 'Upload CSV file';
$string['import_count_valid'] = 'line(s) will be imported.';
$string['import_count_ignored'] = '{$a} line(s) have been skipped (subscription already exists).';

// -- Process CSV
$string['missing_param'] = 'Missing parameter';
$string['no_valid_rows'] = 'No valid rows to import';
$string['import_success_count'] = 'Successfully imported {$a} subscriptions.';
$string['import_skipped'] = 'Skipped entries (missing or invalid data)';
$string['invalid_or_missing_fields'] = 'Invalid or missing fields';
$string['user_not_found'] = 'User not found';

// -- Manage plans
$string['managesubscriptions'] = 'Manage subscriptions';
$string['scopes'] = '🎓 Access scope';
$string['plans'] = '📝 Plans';
$string['user_subscriptions'] = '👨‍🎓 / 👩‍🎓 User subscriptions';
$string['translatetooltip'] = 'Translation tooltip'; // to be checked
$string['pricestooltip'] = 'Prices tooltip'; // to be checked

// Scopes
$string['scopename'] = 'Scope name';
$string['includedcourses'] = 'Included courses';
$string['savescope'] = 'Save';
$string['addscope'] = '➕ Add a new scope';
$string['scopelist'] = 'List of scopes';
$string['sortaz'] = 'Sort A to Z';
$string['sortza'] = 'Sort Z to A';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['courses'] = '📖 Courses';
$string['dates'] = '📅 Dates';
$string['actions'] = '🛠️ Actions';
$string['createdon'] = 'Created on:';
$string['modifiedon'] = 'Last updated:';
$string['editscope'] = '✏️ Edit this scope';
$string['deletescope'] = '🗑️ Delete this scope';
$string['viewtranslations'] = '🌐 View translations';
$string['edit'] = 'Edit scope';
$string['add'] = 'Add scope';
$string['scopecreated'] = 'Scope created. Now add a translation.';
$string['scopecreateerror'] = 'Error while creating the scope.';
$string['scopedeleted'] = 'The scope and its translations have been deleted.';
$string['scopedeleteerror'] = 'Error while deleting the scope.';
$string['scopenotfound'] = 'Scope not found.';
$string['scopedeleteinuse'] = 'Cannot delete this scope: it is used by one or more plans.';
$string['error_scope_name_exists'] = 'A scope with this name already exists.';

// Translations scopes
$string['translationspagetitle'] = 'Translations';
$string['scopedefaultname'] = 'Default scope name';
$string['translatedlanguages'] = 'Translated languages';
$string['addtranslation'] = 'Add a translation';
$string['backtoscopelist'] = 'Back to the scope list';
$string['edittranslation'] = 'Edit the translation';
$string['newtranslation'] = 'Add a new translation';
$string['language'] = 'Language';
$string['alreadyused'] = 'Already used';
$string['defaultscopename'] = 'Default name of the scope';
$string['translatedname'] = 'Translated name';
$string['translateddescription'] = 'Translated description';
$string['save'] = 'Save';
$string['deletetranslation'] = 'Delete this translation';
$string['confirmdeletetranslation'] = 'Are you sure you want to delete this translation?';
$string['errorduplicatetranslation'] = 'A translation already exists in the selected language.';
$string['modifiedon'] = 'Modified on';
$string['showalltranslations'] = 'Show all translations';
$string['cancel'] = 'Cancel';
$string['confirmdeletetranslation'] = 'Are you sure you want to permanently delete this translation?';

// Plans
$string['delete'] = 'Delete';
$string['cancel'] = 'Cancel';
$string['deactivateplan'] = 'Deactivate this plan';
$string['activateplan'] = 'Activate this plan';
$string['planname'] = 'Plan name';
$string['planduration'] = '⌛ Plan duration';
$string['saveplan'] = 'Save plan';
$string['plancreated'] = 'The plan has been created successfully.';
$string['plancreateerror'] = 'An error occurred while creating the plan.';
$string['error_plan_name_exists'] = 'A plan with this name already exists.';
$string['planstatusupdated'] = 'The plan status has been updated.';
$string['planlist'] = 'List of plans';
$string['viewtranslations'] = 'View translations';
$string['deleteplan'] = 'Delete this plan';
$string['editplan'] = 'Edit this plan';
$string['thisplan'] = 'this plan';
$string['plandefaultname'] = 'Default name of the plan';
$string['plandeleted'] = 'The plan and all its translations and prices have been deleted.';
$string['plandeleteerror'] = 'Error while deleting the plan.';
$string['backtoplanlist'] = 'Back to the plan list';
$string['addplan'] = 'Add a new plan';
$string['editplan'] = 'Edit plan';
$string['scope'] = '🎓 Access scope';
$string['duration'] = '⌛ Duration';
$string['availabletranslations'] = 'Available translations';
$string['notranslation'] = 'No translation available';
$string['availablecurrencies'] = 'Available currencies';
$string['nocurrency'] = 'No currency available';
$string['planincomplete'] = 'Cannot activate: plan requires at least one translation and one price.';
$string['cannotactivateplan'] = 'You must define at least one translation and one price before activating this plan.';
$string['is_recurring'] = 'Abonnement récurrent (auto-renouvellement)';
$string['is_recurring_help'] = 'Si activé, le plan sera vendu via Stripe Subscriptions. Assurez-vous d’avoir saisi stripe_price_id pour chaque devise.';


// Prices
$string['currency'] = 'Currency';
$string['price'] = 'Price';
$string['saveprice'] = 'Save price';
$string['error_invalid_price'] = 'Please enter a valid positive price.';
$string['planprices'] = 'Prices';
$string['planpricesfor'] = 'Prices for {$a}';
$string['addprice'] = 'Add price';
$string['editprice'] = 'Edit price';
$string['deleteprice'] = 'Delete price';
$string['priceadded'] = 'Price added successfully.';
$string['priceupdated'] = 'Price updated.';
$string['pricedeleted'] = 'Price deleted.';
$string['confirmdeleteprice'] = 'Are you sure you want to delete this price?';
$string['error_currency_already_exists'] = 'This currency is already defined for this plan.';
$string['noprices'] = 'No price';

$string['stripe_price_id'] = 'Stripe Price ID';
$string['stripe_price_id_help'] = 'Identifiant du prix récurrent côté Stripe (ex.: price_123…). Requis pour les plans récurrents.';
$string['err_stripe_price_required'] = 'Requis pour les plans récurrents.';
$string['badge_recurring'] = 'Auto-renouvellement';



// JS delete...
$string['thisscope'] = 'this scope';
$string['thisplan'] = 'this plan';
$string['confirmdeletetitle'] = 'Confirm deletion';
$string['confirmdeletemessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations will also be deleted.';
$string['confirmdeleteplanmessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations and prices will also be deleted.';
$string['delete'] = 'Delete';


///////
$string['paymentprovider_stripe'] = 'Stripe';
$string['paymentprovider_paypal'] = 'PayPal';
$string['paymentprovider_manual'] = 'Manual entry';
$string['paymentprovider_csv'] = 'CSV import';
$string['paymentprovider_offline'] = 'Offline payment';
$string['paymentprovider_giftcode'] = 'Gift code';
$string['paymentprovider_dev'] = 'Technical/dev import';


$string['description'] = 'Description';
$string['scope_and_duration'] = 'Scope and duration';
$string['courses_included'] = 'Courses included';
$string['select_price'] = 'Select price and currency';


/////
$string['subscriptions'] = 'Subscriptions';
$string['active_until'] = 'Actif jusqu’au';
$string['no_subscriptions_found'] = 'No active subscriptions for this user.';

$string['your_subscriptions'] = 'Your subscriptions';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['access_scope'] = 'Access scope';
$string['price_paid'] = 'Price paid';
$string['status_active'] = 'Active';
$string['no_active_subscriptions'] = 'You have no active subscriptions.';

$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['accessscope'] = 'Access scope';
$string['pricepaid'] = 'Price paid';
$string['coursesincluded'] = 'Included courses';

$string['courselist'] = 'Liste des cours';
$string['show'] = 'afficher';


$string['active'] = 'Active';
$string['expired'] = 'Expired';
$string['close'] = 'Close';

$string['subscribe'] = 'Subscribe';
$string['duration'] = 'Duration';
$string['price'] = 'Price';
$string['choosecurrency'] = 'Choisissez une devise :';
$string['showcoursedescription'] = 'Afficher la description';
$string['change_currency'] = 'Changer la devise';

// Stripe
$string['pluginname'] = 'Subscriptions (abonnements)';
$string['stripe_heading'] = 'Stripe';
$string['stripe_heading_desc'] = 'Renseignez vos clés Stripe en mode test ou live. Pour commencer, la clé secrète suffit. Le secret de webhook est requis quand vous activez le webhook.';
$string['stripe_publishable'] = 'Clé publique (publishable key)';
$string['stripe_publishable_desc'] = 'Optionnel pour le Checkout simple ; utile si vous utilisez Stripe Elements côté client.';
$string['stripe_secret'] = 'Clé secrète (Secret key)';
$string['stripe_secret_desc'] = 'Ex. sk_test_xxx. Utilisée côté serveur pour créer des sessions de paiement.';
$string['stripe_webhook_secret'] = 'Secret du webhook';
$string['stripe_webhook_secret_desc'] = 'Ex. whsec_xxx. Nécessaire pour vérifier la signature des événements Stripe.';
$string['stripe_sessioncreationfailed'] = 'Unable to create the Stripe Checkout session';
$string['stripe_invalidsession'] = 'Invalid or missing Stripe session ID';
$string['payment_success_title'] = 'Payment successful';
$string['payment_success_thanks'] = 'Thank you! Your payment has been processed successfully.';
$string['payment_success_details'] = 'Your subscription is now active. You can access your courses from your profile page.';
$string['payment_already_processed'] = 'Good news! This payment was already processed earlier and your subscription is active. Head over to your profile to explore your courses!';
$string['goto_my_profile'] = 'Go to my profile';
$string['stripe_paymentsucceededrequired'] = 'Payment confirmation required from Stripe.';
$string['stripe_noemail'] = 'We could not retrieve your email address from Stripe.';
$string['stripe_invalidsession'] = 'Invalid or missing Stripe session ID';
$string['payment_canceled_title'] = 'Payment canceled';
$string['payment_canceled_msg'] = 'Your payment has been canceled. No subscription has been created.';
$string['back_to_plans'] = 'Back to available plans';
$string['payment_success_check_email'] = 'Veuillez vérifier votre email : un message vous attend pour finaliser la connexion et définir votre mot de passe.';
$string['payment_pending_msg'] = 'Votre paiement est en cours de validation. Cela prend généralement quelques secondes.';

$string['checkout_title'] = 'Checkout';
$string['checkout_duration'] = 'Duration:';
$string['price_label'] = 'Price';
$string['checkout_consent_label'] = 'I agree to the Terms of Service and the Privacy Policy.';
$string['checkout_subscribe'] = 'Subscribe';
$string['checkout_go_to_payment'] = 'Go to payment';
$string['checkout_courses_included'] = 'Courses included';


$string['welcome_subject'] = 'Welcome to {$a}';
$string['welcome_hello'] = 'Hello {$a},';
$string['welcome_body_intro'] = 'Your account has been created and your subscription is now active.';
$string['welcome_username'] = 'Your username: {$a}';
$string['welcome_temp_password'] = 'Your temporary password: {$a} (you will be asked to set a new one on your first login).';
$string['welcome_loginlink'] = 'Sign in from this page: {$a}';
$string['welcome_plan_summary'] = 'Plan: {$a}';
$string['welcome_amount_summary'] = 'Amount: {$a}';

$string['receipt_subject'] = 'Your payment receipt';
$string['receipt_title'] = 'Payment receipt';
$string['receipt_plan'] = 'Plan: {$a}';
$string['receipt_amount'] = 'Amount: {$a}';
$string['receipt_tx'] = 'Transaction ID: {$a}';
$string['receipt_period'] = 'Access period: {$a}';

$string['subupdate_subject'] = 'Your subscription is active';
$string['subupdate_hello'] = 'Hello {$a},';
$string['subupdate_body'] = 'Your subscription for the plan « {$a} » is now active.';
$string['changepw_hint'] = 'To change your password, enter the temporary password we just emailed you in the “Current password” field, then choose a new one. If you didn’t receive the email, use “Forgotten your username or password?” on the login page.';
$string['welcome_temp_password_label'] = 'Temporary password';
$string['welcome_security_hint'] = 'For your security, you will be asked to set a new password on your first login.';
$string['welcome_button_login'] = 'Sign in';
$string['receipt_intro'] = 'Here is a copy of your purchase details:';
$string['receipt_button_open'] = 'Open my courses';
// Retry / follow-up
$string['retry_invalid_status'] = 'This payment request cannot be retried.';
$string['retry_link_expired'] = 'This retry link has expired. Please start a new checkout.';

// Emails – failure/abandoned/reminder
$string['email_failed_subject'] = 'Your payment could not be completed';
$string['email_failed_intro'] = 'Unfortunately, your payment attempt didn’t succeed.';
$string['email_failed_help'] = 'You can try again in a few seconds using the button below. If the issue persists, try another card or contact your bank.';
$string['email_button_retry'] = 'Try payment again';

$string['email_abandoned_subject'] = 'Finish your purchase';
$string['email_abandoned_intro'] = 'You didn’t complete your purchase. Pick up where you left off:';

$string['email_reminder_subject'] = 'Still interested? Complete your subscription';
$string['email_reminder_intro'] = 'You can finalize your subscription in one click:';

// Scheduled task
$string['task_followup'] = 'Subscriptions – follow-up emails';

$string['payment_error_title'] = 'Payment error';
$string['payment_error_intro'] = 'Something went wrong while preparing your payment. Please try again in a moment.';
$string['email_reminder2_subject'] = 'Last reminder: complete your subscription';
$string['email_reminder2_intro'] = 'This is a gentle reminder to complete your purchase. You can finalize in one click:';

$string['mail_recurring_started_subject'] = 'Votre abonnement récurrent à « {$a} » est actif';
$string['mail_recurring_started_body'] = 'Merci ! Votre abonnement récurrent pour « {$a->plan} » a démarré le {$a->start}.';
$string['view_my_subscriptions'] = 'Voir mes abonnements';

$string['popular_badge'] = 'Popular';
$string['premium_badge'] = 'Premium';

$string['plan_highlight'] = 'Highlight';
$string['highlight_none'] = 'None';
$string['highlight_popular'] = 'Popular';
$string['highlight_premium'] = 'Premium';
$string['plan_highlight_help'] = 'Choose how this plan is highlighted on the public page:
<ul>
  <li><b>None</b>: standard card</li>
  <li><b>Popular</b>: yellow badge and accent styling</li>
  <li><b>Premium</b>: premium styling with a standout call-to-action</li>
</ul>';

$string['task_cleanup_login_tokens'] = 'Clean up expired login tokens';

$string['option_queue_future'] = 'Prolonger (activation le {$a})';
$string['option_upgrade_now']  = 'Upgrade immédiat (prix ajusté)';
$string['option_purchase_new'] = 'Nouvel abonnement';
$string['choose_option'] = 'Choisissez une option';
$string['have_account_login_to_see_options'] = 'J’ai déjà un compte — me connecter pour voir les options d’upgrade';

// au-dessus des options
$string['choose_option']          = 'Choisissez une option';
$string['advisor_help_upgrade']   = 'Vous pouvez soit prolonger votre abonnement actuel à la suite, soit passer à une offre plus longue. Le prix d’upgrade est ajusté en fonction de votre ancienneté.';
$string['advisor_help_standard']  = 'Choisissez comment vous souhaitez activer cet abonnement.';
$string['advisor_help_guest']     = 'Connectez-vous pour voir les options d’upgrade. Sinon, vous pouvez souscrire un nouvel abonnement en renseignant vos coordonnées.';

// résumé prix
$string['summary_price_title']    = 'Prix total';
$string['summary_price_wait']     = 'Sélectionnez une option pour voir le prix total.';

$string['personal_info_title']    = 'Informations personnelles';
$string['personal_info_help']     = 'Ces informations seront utilisées pour créer votre compte et vous envoyer la confirmation.';

// lien invité
$string['have_account_login_to_see_options'] = 'J’ai déjà un compte — me connecter pour voir les options d’upgrade';


$string['subupdate_subject'] = 'Mise à jour de votre abonnement « {$a} »';
$string['subupdate_hello']   = 'Bonjour {$a},';
$string['subupdate_body']    = 'Voici les informations mises à jour pour votre abonnement à « {$a} » :';
$string['subupdate_button_manage'] = 'Gérer mes abonnements';
$string['receipt_amount']    = 'Montant payé';
$string['receipt_tx']        = 'Transaction';
$string['receipt_period']    = 'Période';
$string['renewal_subject'] = 'Renouvellement confirmé – {$a}';
$string['renewal_hello'] = 'Bonjour {$a},';
$string['renewal_body'] = 'Votre abonnement à « {$a} » a été renouvelé. Voici les détails :';
$string['renewal_button_manage'] = 'Gérer mes abonnements';
$string['receipt_invoice'] = 'Facture';
$string['recurring_failed_subject'] = 'Échec du prélèvement – {$a}';
$string['recurring_failed_hello'] = 'Bonjour {$a},';
$string['recurring_failed_body'] = 'Le prélèvement pour votre abonnement « {$a} » a échoué. Merci de mettre à jour vos informations de paiement.';
$string['recurring_failed_button'] = 'Mettre à jour mon moyen de paiement';

$string['recurring_canceled_subject'] = 'Votre abonnement a été annulé – {$a}';
$string['recurring_canceled_hello'] = 'Bonjour {$a},';
$string['recurring_canceled_body'] = 'Votre abonnement à « {$a} » a été annulé. Vous conserverez l’accès jusqu’à la fin de la période en cours.';
$string['recurring_canceled_button'] = 'Se réabonner';

$string['portal_no_customer'] = 'Nous n’avons pas trouvé de compte Stripe associé à votre profil. Si le problème persiste, contactez le support.';

$string['badge_recurring'] = 'Renouvellement automatique';
$string['details'] = 'Détails';
$string['subscription_details'] = 'Détails de la souscription';
$string['manage_payment'] = 'Gérer mon paiement';

$string['mysubs_title'] = 'Mes abonnements';
$string['mysubs_empty'] = 'Vous n’avez pas encore d’abonnement.';
$string['go_subscribe'] = 'S’abonner';
$string['period'] = 'Période';
$string['pricepaid'] = 'Montant payé';
$string['details'] = 'Détails';
$string['subscription_details'] = 'Détails de la souscription';
$string['manage_payment'] = 'Gérer mon paiement';
$string['badge_recurring'] = 'Auto-renouvellement';

$string['status_active']   = 'Active';
$string['status_queued']   = 'En attente';
$string['status_replaced'] = 'Remplacée';
$string['status_canceled']  = 'Annulée';
$string['status_expired']  = 'Expirée';

$string['portal_error_config'] = 'Le portail de paiement n’est pas encore configuré. Merci de réessayer plus tard ou de contacter le support.';
$string['manage_payment'] = 'Gérer mon paiement';
$string['stripe_portal_configuration_id'] = 'Stripe Portal configuration ID';
$string['stripe_portal_configuration_id_desc'] = 'Optionnel : ID de configuration du Customer Portal (ex. pc_xxx). Si vide, la configuration par défaut Stripe sera utilisée.';
$string['btn_renew_now'] = 'Renouveler maintenant';
$string['btn_extend']    = 'Prolonger';

$string['view_my_subscriptions'] = 'Voir tous mes abonnements';
$string['mysubs_empty']     = 'Vous n’avez aucune souscription active.';

$string['option_queue_future']           = 'Prolonger (activation le {$a})';
$string['option_upgrade_now_replace']    = 'Passer à 3 ans maintenant (remplace la file)';


$string['task_send_expiry_reminders'] = 'Send expiry reminders for non-recurring subscriptions';
$string['task_expire_and_activate'] = 'Activate queued and expire overdue subscriptions';
$string['expiry_reminder_subject'] = 'Your access ends in {$a} day(s)';
$string['expiry_reminder_body']    = 'Your subscription to "{$a->plan}" will end on {$a->date}. You can renew now to keep your access without interruption.';
$string['expiry_button_renew']     = 'Renew now';

$string['subscription_activated_subject'] = 'Your subscription to {$a} is now active';
$string['subscription_activated_body']    = 'Great news! Your queued subscription for "{$a}" is now active.';

$string['subscription_expired_subject'] = 'Your subscription to {$a} has ended';
$string['subscription_expired_body']    = 'Your subscription to "{$a->plan}" ended on {$a->date}. Renew now to regain access.';
$string['expired_button_renew']         = 'Renew / Subscribe';
$string['task_expire_enrolments'] = 'Expire subscriptions and update enrolments';
$string['task_repair_paid_pr']        = 'Repair paid PRs: recreate missing subscriptions';

$string['receipt_plan']   = 'Plan';    // ou 'Plan :'
$string['receipt_period'] = 'Period';  // ou 'Période :'

// Flags & statuses
$string['payment_failed_flag'] = 'Payment failed';
$string['payment_failed_flag_help'] = 'The last renewal attempt failed. Please update your payment method to avoid losing access.';
$string['next_retry_at'] = 'Next retry';
$string['last_failed_invoice_id'] = 'Last failed invoice';
$string['last_payment_failed_reason'] = 'Failure reason';

$string['mysubs_empty']   = 'No active subscriptions for this user.'; // tu l'as peut-être déjà
$string['subscribe_now']  = 'Subscribe now';
$string['view_my_subscriptions'] = 'Your subscriptions'; // déjà présent chez toi

$string['upgrade_details_title'] = 'How is this price calculated?';
$string['upgrade_window_label']  = 'Calculation window: {$a}';
$string['upgrade_tariffs']       = 'Reference prices: current = {$a->p1}, target = {$a->p2}';
$string['upgrade_consumed_since_t0'] = 'Elapsed time since window start: {$a}';
$string['upgrade_equation_past']  = 'Past part (current rate): {$a->p1} × t/{$a->d1} = {$a->val}';
$string['upgrade_equation_future']= 'Future part (target rate): {$a->p2} × (D2−t)/{$a->d2} = {$a->val}';
$string['upgrade_spent_window']   = 'Already paid within this window: {$a}';
$string['upgrade_base_cap']       = 'Base = {$a->base}; Degressive cap = {$a->cap}';
$string['upgrade_final_amount']   = 'Proposed amount: <strong>{$a}</strong>';
$string['upgrade_details_summary'] = 'How is this price calculated?';

$string['upgrade_confirmed_subject'] = 'Votre passage à « {$a} » est confirmé';
$string['upgrade_confirmed_body']    = 'Bonne nouvelle ! Votre abonnement a été mis à niveau. Voici le récapitulatif :';
$string['receipt_total']             = 'Montant réglé';
