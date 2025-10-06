<?php
$string['pluginname'] = 'Subscriptions';

// -- Subscription config
// Plans
$string['plan_1month'] = '1 month'; // do not delete
$string['plan_3months'] = '3 months'; // do not delete
$string['plan_6months'] = '6 months'; // do not delete
$string['plan_1year'] = '1 year'; // do not delete
$string['plan_3years'] = '3 years'; // do not delete
$string['plan_lifetime'] = 'Lifetime'; // do not delete

// Buttons
$string['btn_import_csv'] = 'Import subscriptions from CSV';

// -- Manage subscriptions
$string['manage_subscriptions'] = 'Manage subscriptions';
$string['updated_subscriptions'] = 'Updated {$a} subscription(s).';
$string['delete_subscriptions'] = '{$a} subscription(s) have been deleted.';
$string['edit_subscriptions'] = 'Edit subscriptions';
$string['user'] = 'User';
$string['plan'] = 'Plan';
$string['start_date'] = 'Start date';
$string['end_date'] = 'End date';
$string['status'] = 'Status';
$string['creation_date'] = 'Creation date';
$string['save_modifications'] = 'Save modifications';
$string['delete_selected'] = 'Delete selected subscriptions';
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

// -- Manage plans
$string['scopes'] = '🎓 Access scope';
$string['plans'] = '📝 Plans';
$string['user_subscriptions'] = '👨‍🎓 / 👩‍🎓 User subscriptions';
$string['translatetooltip'] = 'Translation tooltip'; // to be checked
$string['pricestooltip'] = 'Prices tooltip'; // to be checked

// Scopes
$string['scopename'] = 'Scope name';
$string['includedcourses'] = 'Included courses';
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
$string['modifiedon'] = 'Modified on:';
$string['editscope'] = '✏️ Edit this scope';
$string['deletescope'] = '🗑️ Delete this scope';
$string['edit'] = 'Edit scope';
$string['add'] = 'Add scope';
$string['scopecreated'] = 'Scope created. Now add a translation.';
$string['scopecreateerror'] = 'Error while creating the scope.';
$string['scopedeleted'] = 'The scope and its translations have been deleted.';
$string['scopedeleteerror'] = 'Error while deleting the scope.';
$string['error_scope_name_exists'] = 'A scope with this name already exists.';

// Translations scopes
$string['translationspagetitle'] = 'Translations';
$string['scopedefaultname'] = 'Default scope name';
$string['translatedlanguages'] = 'Translated languages';
$string['addtranslation'] = 'Add a translation';
$string['backtoscopelist'] = 'Back to the scope list';
$string['language'] = 'Language';
$string['alreadyused'] = 'Already used';
$string['defaultscopename'] = 'Default name of the scope';
$string['translatedname'] = 'Translated name';
$string['save'] = 'Save';
$string['deletetranslation'] = 'Delete this translation';
$string['errorduplicatetranslation'] = 'A translation already exists in the selected language.';
$string['showalltranslations'] = 'Show all translations';
$string['cancel'] = 'Cancel';
$string['confirmdeletetranslation'] = 'Are you sure you want to permanently delete this translation?';

// Plans
$string['delete'] = 'Delete';
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
$string['deleteplan'] = 'Delete this plan';
$string['editplan'] = 'Edit this plan';
$string['thisplan'] = 'this plan';
$string['plandefaultname'] = 'Default name of the plan';
$string['plandeleted'] = 'The plan and all its translations and prices have been deleted.';
$string['plandeleteerror'] = 'Error while deleting the plan.';
$string['backtoplanlist'] = 'Back to the plan list';
$string['addplan'] = 'Add a new plan';
$string['duration'] = '⌛ Duration';
$string['availabletranslations'] = 'Available translations';
$string['notranslation'] = 'No translation available';
$string['availablecurrencies'] = 'Available currencies';
$string['nocurrency'] = 'No currency available';
$string['planincomplete'] = 'Cannot activate: plan requires at least one translation and one price.';
$string['cannotactivateplan'] = 'You must define at least one translation and one price before activating this plan.';
$string['is_recurring'] = 'Recurring subscription (auto-renew)';
$string['is_recurring_help'] = 'If enabled, the plan will be sold via Stripe Subscriptions. Make sure you have set a stripe_price_id for each currency.'; // do not delete

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
$string['stripe_price_id_help'] = 'Recurring price identifier on Stripe (e.g. price_123…). Required for recurring plans.'; // do not delete
$string['badge_recurring'] = 'Auto-renew';

// JS delete...
$string['thisscope'] = 'this scope';
$string['confirmdeletetitle'] = 'Confirm deletion';
$string['confirmdeletemessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations will also be deleted.';
$string['confirmdeleteplanmessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations and prices will also be deleted.';

$string['scope_and_duration'] = 'Scope and duration';
$string['courses_included'] = 'Courses included';
$string['select_price'] = 'Select price and currency';

$string['your_subscriptions'] = 'Your subscriptions';
$string['no_active_subscriptions'] = 'You have no active subscriptions.';

$string['pricepaid'] = 'Price paid';

$string['courselist'] = 'Course list';

$string['close'] = 'Close';

$string['subscribe'] = 'Subscribe';
$string['change_currency'] = 'Change currency';

$string['payment_success_check_email'] = 'Please check your email: a message is waiting to finish signing in and set your password.';
$string['payment_pending_msg'] = 'Your payment is being validated. This usually takes a few seconds.';
$string['payment_success_title'] = 'Payment successful';
$string['payment_success_thanks'] = 'Thank you! Your payment has been processed successfully.';
$string['payment_canceled_title'] = 'Payment canceled';
$string['payment_canceled_msg'] = 'Your payment has been canceled. No subscription has been created.';
$string['back_to_plans'] = 'Back to available plans';

$string['checkout_title'] = 'Checkout';
$string['checkout_duration'] = 'Duration:';
$string['checkout_go_to_payment'] = 'Go to payment';

$string['welcome_subject'] = 'Welcome to {$a}';
$string['welcome_body_intro'] = 'Your account has been created and your subscription is now active.';
$string['welcome_username'] = 'Your username: {$a}';
$string['welcome_plan_summary'] = 'Plan: {$a}';
$string['welcome_amount_summary'] = 'Amount: {$a}';

$string['receipt_title'] = 'Payment receipt';
$string['receipt_plan'] = 'Plan: ';
$string['receipt_amount'] = 'Amount: ';
$string['receipt_tx'] = 'Transaction ID: ';
$string['receipt_period'] = 'Access period: ';

$string['welcome_temp_password_label'] = 'Temporary password';
$string['welcome_security_hint'] = 'For your security, you will be asked to set a new password on your first login.';
$string['receipt_intro'] = 'Here is a copy of your purchase details:';
$string['receipt_button_open'] = 'Open my courses';

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

$string['mail_recurring_started_subject'] = 'Your recurring subscription to « {$a} » is active';
$string['mail_recurring_started_body'] = 'Thank you! Your recurring subscription for « {$a->plan} » started on {$a->start}.';
$string['view_my_subscriptions'] = 'View my subscriptions';

$string['plan_highlight'] = 'Highlight';
$string['highlight_popular'] = 'Popular';
$string['highlight_premium'] = 'Premium';
$string['plan_highlight_help'] = 'Choose how this plan is highlighted on the public page:
<ul>
  <li><b>None</b>: standard card</li>
  <li><b>Popular</b>: yellow badge and accent styling</li>
  <li><b>Premium</b>: premium styling with a standout call-to-action</li>
</ul>'; // do not delete

$string['task_cleanup_login_tokens'] = 'Clean up expired login tokens';

$string['option_queue_future'] = 'Extend (activates on {$a})';
$string['option_purchase_new'] = 'New subscription';
$string['choose_option'] = 'Choose an option';
$string['have_account_login_to_see_options'] = 'I already have an account — sign in to see upgrade options';

// Above the options
$string['advisor_help_upgrade']  = 'You can either extend your current subscription in sequence or switch to a longer plan. The upgrade price is adjusted based on your elapsed time.';
$string['advisor_help_standard'] = 'Choose how you want to activate this subscription.';
$string['advisor_help_guest']    = 'Sign in to see upgrade options. Otherwise, you can start a new subscription by entering your details.';

// Price summary
$string['summary_price_title'] = 'Total price';

$string['personal_info_title'] = 'Personal information';
$string['personal_info_help']  = 'This information will be used to create your account and send you the confirmation.';

$string['mail_hello'] = 'Hello {$a},';
$string['mail_button_manage'] = 'Manage my subscriptions';

$string['subupdate_subject'] = 'Your subscription to « {$a} » is active';
$string['subupdate_body']    = 'Here are the updated details of your subscription to « {$a} »:';
$string['renewal_subject']   = 'Renewal confirmed – {$a}';
$string['renewal_body']      = 'Your subscription to « {$a} » has been renewed. Here are the details:';
$string['recurring_failed_subject'] = 'Payment failed – {$a}';
$string['recurring_failed_body']    = 'The payment for your subscription « {$a} » failed. Please update your payment information.';
$string['recurring_failed_button']  = 'Update my payment method';

$string['recurring_canceled_subject'] = 'Your subscription has been canceled – {$a}';
$string['recurring_canceled_body']    = 'Your subscription to « {$a} » has been canceled. You will keep access until the end of the current period.';
$string['recurring_canceled_button']  = 'Subscribe again';

$string['details'] = 'Details';
$string['subscription_details'] = 'Subscription details';

$string['mysubs_title'] = 'My subscriptions';
$string['mysubs_empty'] = 'You don’t have any subscription yet.';
$string['period'] = 'Period';

$string['btn_extend']    = 'Extend';

$string['option_upgrade_now_replace'] = 'Upgrade now to the selected duration (replace the queue)';

$string['task_send_expiry_reminders'] = 'Send expiry reminders for non-recurring subscriptions';
$string['expiry_reminder_subject'] = 'Your access ends in {$a} day(s)';
$string['expiry_reminder_body']    = 'Your subscription to "{$a->plan}" will end on {$a->date}. You can renew now to keep your access without interruption.';

$string['subscription_activated_subject'] = 'Your subscription to {$a} is now active';
$string['subscription_activated_body']    = 'Great news! Your queued subscription for "{$a}" is now active.';

$string['subscription_expired_subject'] = 'Your subscription to {$a} has ended';
$string['subscription_expired_body']    = 'Your subscription to "{$a->plan}" ended on {$a->date}. Renew now to regain access.';
$string['expired_button_renew']         = 'Renew / Subscribe';
$string['task_expire_enrolments'] = 'Expire subscriptions and update enrolments';
$string['task_repair_paid_pr']        = 'Repair paid PRs: recreate missing subscriptions';

// Flags & statuses
$string['payment_failed'] = 'Payment failed';

$string['subscribe_now']  = 'Subscribe now';

$string['upgrade_window_label']  = 'Calculation window: {$a}';
$string['upgrade_tariffs']       = 'Reference prices: current = {$a->p1}, target = {$a->p2}';
$string['upgrade_consumed_since_t0'] = 'Elapsed time since window start: {$a}';
$string['upgrade_equation_past']  = 'Past part (current rate): {$a->p1} × t/{$a->d1} = {$a->val}';
$string['upgrade_equation_future']= 'Future part (target rate): {$a->p2} × (D2−t)/{$a->d2} = {$a->val}';
$string['upgrade_spent_window']   = 'Already paid within this window: {$a}';
$string['upgrade_base_cap']       = 'Base = {$a->base}; Degressive cap = {$a->cap}';
$string['upgrade_final_amount']   = 'Proposed amount: <strong>{$a}</strong>';
$string['upgrade_details_summary'] = 'How is this price calculated?';

$string['upgrade_confirmed_subject']  = 'Your upgrade to « {$a} » is confirmed';
$string['upgrade_confirmed_body']     = 'Good news! Your subscription has been upgraded. Here is the summary:';

$string['unknown_plan']             = 'Unknown plan';

$string['manage_billing'] = 'Manage billing';
$string['provider_portal_not_supported'] = 'Billing portal not available';
$string['provider_portal_not_supported_desc'] = 'The provider « {$a} » does not offer a customer portal yet. You can manage your subscription from your profile.';

$string['subfield_id']                 = 'ID';
$string['subfield_userid']             = 'User ID';
$string['subfield_planid']             = 'Plan ID';
$string['subfield_start']              = 'Start';
$string['subfield_end']                = 'End';
$string['subfield_amount']             = 'Amount paid';
$string['subfield_txn']                = 'Transaction';
$string['subfield_provider']           = 'Provider';
$string['subfield_provider_sub']       = 'Provider subscription';
$string['subfield_provider_customer']  = 'Provider customer';
$string['subfield_last_invoice']       = 'Last invoice';
$string['subfield_last_failed_at']     = 'Last failed at';
$string['subfield_fail_reason']        = 'Failure reason';
$string['subfield_created']            = 'Created';
$string['subfield_updated']            = 'Updated';
$string['subfield_unlimited']          = 'Unlimited';
$string['subfield_payment_status']  = 'Payment status';
$string['subpayment_action']        = 'Action required';

// (optionnel) labels traduits pour tes statuts
$string['substatus_active']            = 'Active'; // do not delete
$string['substatus_queued']            = 'Queued'; // do not delete
$string['substatus_replaced']          = 'Replaced'; // do not delete
$string['substatus_expired']           = 'Expired'; // do not delete
$string['substatus_canceled']          = 'Canceled'; // do not delete
$string['substatus_pending']           = 'Pending'; // do not delete
$string['substatus_error']             = 'Error'; // do not delete
$string['substatus_suspended']         = 'Suspended'; // do not delete
$string['substatus_paid']              = 'Paid'; // do not delete
$string['substatus_failed']            = 'Failed'; // do not delete
$string['substatus_completed']         = 'Completed'; // do not delete
$string['substatus_unknown']           = 'Unknown'; // do not delete

$string['optional_error_msg'] = 'Optional error message';

$string['summary_price_wait'] = 'Select an option to see the total price.';
$string['existing_account_hint_html'] = 'An account already exists with this email. <a class="link-primary fw-semibold" href="{$a->url}">Sign in</a>.';

$string['email_footer_copyright'] = '© {$a->year} {$a->brand}. All rights reserved.';
$string['email_footer_unexpected'] = 'If you didn’t expect this email, you can safely ignore it.';
$string['receipt_total']  = 'Total paid';
$string['receipt_invoice']= 'Invoice';

$string['email_show_pr_ref'] = 'Show PR reference in emails';
$string['email_show_pr_ref_desc'] = 'Add a small technical reference (PR # and date) at the bottom of emails. Disabled by default.';
$string['unknown_payment_event'] = 'Unknown payment event: {$a}';

$string['stripe:missingamount'] = 'Missing amount on payment request.';
$string['stripe:productname'] = '{$a} plan';
$string['stripe:missingpriceidforsubscription'] = 'Missing stripe_price_id for subscription.';
$string['stripe:missingpriceid'] = 'Missing price_id.';
$string['stripe:sdkautoloadnotfound'] = 'Stripe SDK autoload not found at {$a}.';
$string['sessiondisplay'] = 'Session: {$a}';

// Headings
$string['emails_links_heading'] = 'Emails & links';
$string['emails_links_heading_desc'] = 'Settings for follow-up emails and resume links.';
$string['followups_heading'] = 'Follow-ups & expiration';
$string['followups_heading_desc'] = 'Delays (in minutes) to expire and send reminders.';

// Brand logo (general/email)
$string['brand_logo_url_label'] = 'Brand logo URL';
$string['brand_logo_url_desc'] = 'Absolute URL to a small logo (PNG/SVG, height ~32px) used in emails.';

// Email link secret
$string['email_link_secret_label'] = 'Secret for resume links';
$string['email_link_secret_desc'] = 'String used to sign resume links (fallback: $CFG->passwordsaltmain).';

// Expiration & reminders
$string['expire_pending_after_minutes_label'] = 'Expire pending payments';
$string['expire_pending_after_minutes_desc'] = 'Switch pending → expired after N minutes without a payment.';
$string['reminder1_after_minutes_label'] = 'Reminder #1';
$string['reminder1_after_minutes_desc'] = 'Send a first reminder if status ∈ (pending, expired, failed) and age ≥ N minutes.';
$string['reminder2_after_minutes_label'] = 'Reminder #2';
$string['reminder2_after_minutes_desc'] = 'Send a second reminder if still unpaid and age ≥ N minutes (since creation).';

// Featured plan
$string['featured_planid_label'] = 'Featured plan';
$string['featured_planid_desc'] = 'ID of the plan highlighted on the offers page.';

$string['alfa_missing_api_base'] = 'Alfa API base URL is missing.';
$string['alfa_rub_only'] = 'Alfa (token) is configured for RUB currency only.';
$string['alfa_register_error'] = 'Payment initialization failed: {$a}';
$string['alfa_missing_formurl'] = 'Payment initialized but the bank did not return a payment URL.';
$string['alfa_not_paid'] = 'Payment not completed';

$string['subfield_pr_id'] = 'Payment Request #';
$string['subfield_pr_status'] = 'PR status';
$string['subfield_pr_provider'] = 'PR provider';
$string['subfield_pr_amount'] = 'PR amount';
$string['subfield_pr_orderid'] = 'PR orderId';
$string['subfield_pr_txnid'] = 'PR transactionId';
$string['subfield_pr_paidat'] = 'PR paid at';
$string['subfield_pr_link'] = 'PR payment link';
$string['subfield_pr_lasterror'] = 'PR last error';
$string['notavailable'] = 'N/A';

$string['err_cannot_determine_price'] = 'Cannot determine price to create payment request.';
$string['err_no_redirect_url'] = 'Checkout init returned no redirect URL.';

$string['btn_signin'] = 'Sign in';

$string['provider_alfa'] = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Manual';
$string['provider_csv'] = 'CSV';
$string['provider_dev'] = 'Dev';

$string['configmissing'] = 'Missing configuration: {$a}.';
$string['missing_customer_id'] = 'Stripe customer ID is missing.';
$string['invalidcsvupload'] = 'The uploaded CSV file is invalid.';
$string['csvwritefail'] = 'Failed to write CSV file.';
$string['invalidpricecurrency'] = 'Invalid price/currency combination.';
$string['plan_not_found'] = 'Subscription plan not found.';
$string['scopenotfound'] = 'Access scope not found.';
$string['scopedeleteinuse'] = 'Cannot delete this scope because it is in use.';
$string['plannotfound'] = 'Plan not found.';
$string['paymentgatewayerror'] = 'Payment gateway error: {$a}';

$string['retry_invalid_status'] = 'This payment request cannot be retried in its current state.';
$string['retry_link_expired'] = 'This retry link is invalid or has expired. Please start a new checkout.';

// Sections
$string['providers_header'] = 'Payment providers';
$string['provider_default'] = 'Default provider';
$string['provider_default_desc'] = 'Which provider to use when no routing rule applies.';

// Common env
$string['env_mode'] = 'Environment';
$string['env_mode_desc'] = 'Choose which credentials to use.';
$string['env_test'] = 'Test';
$string['env_live'] = 'Live';

// Stripe
$string['stripe_secret_test'] = 'Secret key (TEST)';
$string['stripe_publishable_test'] = 'Publishable key (TEST)';
$string['stripe_webhook_secret_test'] = 'Stripe Webhook secret (TEST)';
$string['stripe_portal_configuration_id_test'] = 'Stripe Portal configuration ID (TEST)';
$string['stripe_portal_configuration_id_desc'] = 'Optional: Customer Portal configuration ID (e.g. pc_xxx). If empty, Stripe’s default configuration will be used.';

$string['stripe_secret_live'] = 'Secret key (LIVE)';
$string['stripe_publishable_live'] = 'Publishable key (LIVE)';
$string['stripe_webhook_secret_live'] = 'Stripe Webhook secret (LIVE)';
$string['stripe_portal_configuration_id_live'] = 'Stripe Portal configuration ID (LIVE)';


// Alfa
$string['alfa_settings_header'] = 'Alfa Bank';
$string['alfa_api_base_test'] = 'API base URL (TEST)';
$string['alfa_username_test'] = 'Login (TEST)';
$string['alfa_password_test'] = 'Password (TEST)';
$string['alfa_token_test'] = 'API token (TEST)';
$string['alfa_webhook_secret_test'] = 'Alfa Webhook secret (TEST)';
$string['alfa_api_base_live'] = 'API base URL (LIVE)';
$string['alfa_username_live'] = 'Login (LIVE)';
$string['alfa_password_live'] = 'Password (LIVE)';
$string['alfa_token_live'] = 'API token (LIVE)';
$string['alfa_webhook_secret_live'] = 'Alfa Webhook secret (LIVE)';


$string['policy_url_ru'] = 'Privacy policy URL (Russia)';
$string['policy_url_row'] = 'Privacy policy URL (Rest of world)';
$string['terms_url_ru'] = 'Terms (CGU/CGV) URL (Russia)';
$string['terms_url_row'] = 'Terms (CGU/CGV) URL (Rest of world)';
$string['privacy_policy'] = 'Privacy policy';
$string['terms_cgu'] = 'Terms & Conditions';
$string['i_accept_policy'] = 'I agree to the {$a}.';
$string['i_accept_terms']  = 'I agree to the {$a}.';

$string['availability_mode'] = 'Plugin visibility';
$string['availability_mode_desc'] = 'Temporarily restrict all public pages of the Subscriptions plugin.';
$string['availability_enabled'] = 'Enabled (public)';
$string['availability_adminonly'] = 'Admin only';
$string['availability_disabled'] = 'Disabled';

$string['subs_unavailable'] = 'Subscriptions are temporarily unavailable.';
$string['subs_unavailable_adminonly'] = 'Subscriptions pages are currently restricted to administrators.';

$string['label_inactive'] = '(inactive)';


$string['edittranslation'] = 'Edit translation'; //  do not delete
$string['newtranslation'] = 'New translation'; //  do not delete

$string['task_subscription_rollover'] = 'Activate queued subscriptions and expire finished ones';
$string['renew_now'] = 'Renew now';
$string['renew_soon_msg'] = 'Your access ends in {$a} day(s). Renew now to avoid interruption.';
$string['queued_starts_in'] = 'Starts in {$a} day(s)';
$string['none'] = 'None';
$string['mycourses_profile_heading'] = 'Mes cours';

$string['plan_inactive'] = 'This plan is not available anymore. Please choose an active plan.';
$string['plan_inactive_redirect'] = 'This plan is no longer available. Please select a new plan.';
$string['plan_description_show'] = 'Show description';

$string['email_copy_to'] = 'Admin e-mail copy';
$string['email_copy_to_desc'] = 'One or more addresses (comma-separated) will receive a copy of emails sent by the Subscriptions plugin.';