<?php
$string['pluginname'] = 'Purchases';

// -- Subscription config
// Plans
$string['plan_1week'] = '1 week'; // do not delete
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

$string['your_subscriptions'] = 'Your purchases';
$string['no_active_subscriptions'] = 'You don’t have any active purchases.';

$string['pricepaid'] = 'Price paid';

$string['courselist'] = 'Course list';


$string['subscribe'] = 'Buy';
$string['subscribe_to_campus'] = 'Buy on Campus<small><sup>FR</sup></small>';
$string['change_currency'] = 'Change currency';

$string['payment_success_check_email'] = 'Please check your email: a message is waiting to finish signing in and set your password.';
$string['payment_pending_msg'] = 'Your payment is being validated. This usually takes a few seconds.';
$string['payment_success_title'] = 'Payment successful';
$string['payment_success_thanks'] = 'Thank you! Your payment has been processed successfully.';
$string['payment_canceled_title'] = 'Payment canceled';
$string['payment_canceled_msg']        = 'Your payment was canceled. Try again to access the course.';
$string['back_to_plans']               = 'Back to available courses';

$string['checkout_title'] = 'Checkout';
$string['checkout_duration'] = 'Duration:';
$string['checkout_go_to_payment'] = 'Go to payment';

$string['welcome_subject'] = 'Your access to CampusFR is activated ✅';

$string['welcome_body_intro'] =
    '<p>Hello, {$a}!</p>' .
    '<p>Your access to Campus<small><sup>FR</sup></small> is now active.</p>' .
    '<p>If you have already used Campus<small><sup>FR</sup></small> during the trial period, you can simply continue with the same account — your completed exercises and croissant points are محفوظ. If you are new here, this account will be used for all your logins.</p>' .
    '<p>Here are your login details:</p>';

$string['welcome_username'] = 'Email:';
$string['welcome_plan_summary'] = 'Course: {$a}';
$string['welcome_amount_summary'] = 'Amount: {$a}';

$string['welcome_text_canal'] =
    'Make sure to join the Campus<small><sup>FR</sup></small> channel: all important updates and announcements are posted there, and you can ask teachers your questions.';
$string['welcome_button_canal'] = 'Campus<small><sup>FR</sup></small> Channel';

$string['welcome_text_group'] =
    'You can also join the community group to chat with others, ask for advice, support each other, and feel part of the family.';
$string['welcome_button_group'] = 'Campus<small><sup>FR</sup></small> Group';

$string['welcome_footer'] =
    '<p>This e-mail was sent automatically.<br>
If you have any questions, write to us at <a href="mailto:{$a}">{$a}</a> — we\'ll be happy to help.</p>
<p>We wish you joyful learning, inspiring lessons and steady progress in French ❤️</p>
<p>Nata and the Campus<small><sup>FR</sup></small> team</p>';

$string['receipt_title'] = 'Your CampusFR course purchase is confirmed ✅';
$string['receipt_plan'] = 'Course: ';
$string['receipt_amount'] = 'Amount: ';
$string['receipt_tx'] = 'Transaction ID: ';
$string['receipt_period'] = 'Access period: ';

$string['welcome_temp_password_label'] = 'Temporary password:';

$string['welcome_security_hint'] =
    '<p>You created your password during registration. If you ever forget it, you can easily reset it here:</p>' .
    '👉 <a href="{$a->url}">Reset password</a></p>';

$string['welcome_mycourses'] =
    '<p>You can access your dashboard using the link below:</p>' .
    '👉 <a href="{$a->url}">Enter the campus</a></p>' .
    '<p>Your subscription information:</p>';

$string['receipt_intro'] =
    '<p>Your access to your course on Campus<small><sup>FR</sup></small> has been successfully activated, and your payment has been confirmed.</p>
<p>Here are the main details of your purchase:</p>';

$string['receipt_button_open'] = 'Access Campus<small><sup>FR</sup></small>';

$string['receipt_footer'] =
    '<p>See you soon on Campus<small><sup>FR</sup></small> 🇫🇷🥐</p>
<p>The Campus<small><sup>FR</sup></small> team</p>';


// Emails – failure/abandoned/reminder
$string['email_failed_subject'] = 'Your payment could not be completed';
$string['email_failed_intro'] = 'Unfortunately, your payment attempt didn’t succeed.';
$string['email_failed_help'] = 'You can try again in a few seconds using the button below. If the issue persists, try another card or contact your bank.';
$string['email_button_retry'] = 'Try payment again';

$string['email_abandoned_subject'] = 'Finish your purchase';
$string['email_abandoned_intro'] = 'You didn’t complete your purchase. Pick up where you left off:';

$string['email_reminder_subject'] = 'Still interested? Complete your purchase';
$string['email_reminder_intro']   = 'You can complete your purchase in one click:';

// Scheduled task
$string['task_followup'] = 'Subscriptions – follow-up emails';

$string['payment_error_title'] = 'Payment error';
$string['payment_error_intro'] = 'Something went wrong while preparing your payment. Please try again in a moment.';
$string['email_reminder2_subject'] = 'Last reminder: complete your purchase';
$string['email_reminder2_intro'] = 'This is a gentle reminder to complete your purchase. You can finalize in one click:';

$string['mail_recurring_started_subject'] = 'Your recurring subscription to « {$a} » is active';
$string['mail_recurring_started_body'] = 'Thank you! Your recurring subscription for « {$a->plan} » started on {$a->start}.';
$string['view_my_subscriptions'] = 'View my purchases';

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
$string['have_account_login_to_see_options'] = 'Already have an account? Log in to renew your subscription.';

// Above the options
$string['advisor_help_upgrade']  = 'You can either extend your current subscription in sequence or switch to a longer plan. The upgrade price is adjusted based on your elapsed time.';
$string['advisor_help_standard'] = 'Choose how you want to activate this subscription.';
$string['advisor_help_guest']    = 'Sign in to see upgrade options. Otherwise, you can start a new subscription by entering your details.';

// Price summary
$string['summary_price_title'] = 'Total price';

$string['personal_info_title'] = 'Personal information';
$string['personal_info_help']  = 'This information is required to create your account and activate your access.';

$string['mail_hello']         = 'Hi {$a},';
$string['mail_button_manage'] = 'Manage my purchases';

$string['subupdate_subject'] = 'Your access to "{$a}" is active';
$string['subupdate_body']    = 'Here are the updated details of your access to "{$a}":';
$string['renewal_subject']   = 'Renewal confirmed – {$a}';
$string['renewal_body']      = 'Your access to "{$a}" has been renewed. Details:';
$string['recurring_failed_subject'] = 'Payment failed – {$a}';
$string['recurring_failed_body']    = 'The payment for your subscription « {$a} » failed. Please update your payment information.';
$string['recurring_failed_button']  = 'Update my payment method';

$string['recurring_canceled_subject'] = 'Your subscription has been canceled – {$a}';
$string['recurring_canceled_body']    = 'Your subscription to « {$a} » has been canceled. You will keep access until the end of the current period.';
$string['recurring_canceled_button']  = 'Subscribe again';


$string['mysubs_title'] = 'My purchases';
$string['mysubs_empty'] = 'You have not purchased any courses yet.';
$string['period'] = 'Period';

$string['btn_extend']    = 'Extend';

$string['option_upgrade_now_replace'] = 'Upgrade now to the selected duration (replace the queue)';

$string['task_send_expiry_reminders'] = 'Send expiry reminders for non-recurring subscriptions';
$string['expiry_reminder_subject'] = 'Your access ends in {$a} day(s)';
$string['expiry_reminder_body']    = 'Your subscription to "{$a->plan}" will end on {$a->date}. You can renew now to keep your access without interruption.';

$string['subscription_activated_subject'] = 'Your access to {$a} is now active';
$string['subscription_activated_body']    = 'Good news! Your access to "{$a}" is now active.';

$string['subscription_expired_subject'] = 'Your subscription to {$a} has ended';
$string['subscription_expired_body']    = 'Your subscription to "{$a->plan}" ended on {$a->date}. Renew now to regain access.';
$string['expired_button_renew']         = 'Renew / Subscribe';
$string['task_expire_enrolments'] = 'Expire subscriptions and update enrolments';
$string['task_repair_paid_pr']        = 'Repair paid PRs: recreate missing subscriptions';

// Flags & statuses
$string['payment_failed'] = 'Payment failed';

$string['subscribe_now']  = 'Buy now';


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


$string['btn_signin'] = 'Sign in';

$string['provider_alfa'] = 'AlfaBank';
$string['provider_stripe'] = 'Stripe';
$string['provider_manual'] = 'Manual';
$string['provider_csv'] = 'CSV';
$string['provider_dev'] = 'Dev';
$string['provider_trial']  = 'Trial';

$string['configmissing'] = 'Missing configuration: {$a}.';

$string['invalidcsvupload'] = 'The uploaded CSV file is invalid.';
$string['csvwritefail'] = 'Failed to write CSV file.';
$string['invalidpricecurrency'] = 'Invalid price/currency combination.';
$string['plan_not_found'] = 'Subscription plan not found.';
$string['scopenotfound'] = 'Access scope not found.';
$string['scopedeleteinuse'] = 'Cannot delete this scope because it is in use.';
$string['plannotfound'] = 'Plan not found.';


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
$string['stripe_profile_test'] = 'Test';
$string['stripe_profile_live_ei'] = 'Live EI';
$string['stripe_profile_live_sas'] = 'Live SAS';
$string['stripe_secret_live_sas'] = 'Secret key (LIVE SAS)';
$string['stripe_publishable_live_sas'] = 'Publishable key (LIVE SAS)';
$string['stripe_webhook_secret_live_sas'] = 'Stripe webhook secret (LIVE SAS)';
$string['stripe_portal_configuration_id_live_sas'] = 'Stripe Portal configuration ID (LIVE SAS)';

// Stripe
$string['stripe_secret_test'] = 'Secret key (TEST)';
$string['stripe_publishable_test'] = 'Publishable key (TEST)';
$string['stripe_webhook_secret_test'] = 'Stripe Webhook secret (TEST)';
$string['stripe_portal_configuration_id_test'] = 'Stripe Portal configuration ID (TEST)';
$string['stripe_portal_configuration_id_desc'] = 'Optional: Customer Portal configuration ID (e.g. pc_xxx). If empty, Stripe’s default configuration will be used.';

$string['stripe_secret_live'] = 'Secret key (LIVE EI)';
$string['stripe_publishable_live'] = 'Publishable key (LIVE EI)';
$string['stripe_webhook_secret_live'] = 'Stripe Webhook secret (LIVE EI)';
$string['stripe_portal_configuration_id_live'] = 'Stripe Portal configuration ID (LIVE EI)';


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
$string['terms_url_ru'] = 'Terms of use URL (Russia)';
$string['terms_url_row'] = 'Terms of use URL (Rest of world)';
$string['offer_url_ru'] = 'Offer agreement URL (Russia)';
$string['offer_url_row'] = 'Offer agreement URL (Rest of world)';
$string['privacy_policy'] = 'Privacy policy';
$string['terms_cgu'] = 'Terms & Conditions';
$string['terms_cgv'] = 'Offer agreement';
$string['i_accept_policy'] = 'I agree to the {$a}.';
$string['i_accept_terms']  = 'I agree to the {$a}.';
$string['i_accept_all_terms'] =
    'I accept the {$a->policy}, the {$a->terms} and the {$a->offer}.';

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

$string['settings:sitedefault'] = 'Site default language';
$string['settings:defaultuserlang'] = 'Default language for new accounts';
$string['settings:defaultuserlang_desc'] = 'If empty, new users inherit the site default language. Choose a language to force it on creation.';
$string['settings:defaultemaillang'] = 'Language for emails sent by the plugin';
$string['settings:defaultemaillang_desc'] = 'If empty, emails use the recipient’s preferred language (or the site default). Choose a language to force it.';

$string['recurring_canceled_effect_now'] = 'Cancellation takes effect immediately. Your access is suspended.';
$string['recurring_canceled_effect_on']  = 'Cancellation will take effect on {$a}. You keep access until then.';
$string['payment_failcode'] = 'Reason';
$string['payment_nextretry'] = 'Next attempt';
$string['email_retry_expires'] = 'Link valid until';

$string['contact_admin_subject'] = 'New contact message';
$string['contact_copy_subject']  = 'We received your message';
$string['contact_copy_intro']    = 'Thank you for your message. We’ll get back to you shortly.';
$string['contact_label_name']    = 'Name';
$string['contact_label_email']   = 'Email';
$string['contact_label_msg']     = 'Message';
$string['view_site']             = 'Open website';
$string['contact_label_ip'] = 'IP';
$string['contact_label_ua'] = 'User-Agent';
$string['reply_now']              = 'Reply now';
$string['contact_reply_greeting'] = 'Hello {$a}, we have received your message.';
$string['contact_reply_reminder'] = 'Reminder of your message:';
$string['contact_reply_marker']   = '— Write your reply below —';
$string['contact_reply_subject']  = 'Re: your message to CampusFR';
$string['reply_in_admin'] = 'Reply from admin (HTML editor)';
$string['reply_text']             = 'Your reply';
$string['contact_reply_sent_hint']  = 'Your reply has been sent to the recipient. You can close this page.';

$string['trial_checkout_banner'] = 'You are signed in with a trial account. Please enter your details to purchase a subscription.';

// Generic / create_session safeguards
$string['invalid_operation'] = 'Invalid payment operation.';
$string['invalid_payment_request_status'] = 'This payment request can’t be used anymore.';
$string['invalid_payment_request_owner'] = 'You do not have access to this payment request.';
$string['invalid_currency_for_alfa'] = 'Invalid currency for Alfa: only RUB is supported.';
$string['err_no_redirect_url'] = 'Payment provider did not return a redirect URL.';
$string['err_cannot_determine_price'] = 'Could not determine the plan price for the selected currency.';

// Alfa gateway
$string['alfa_missing_api_base'] = 'Missing Alfa configuration: API base URL is not set.';
$string['alfa_rub_only'] = 'Alfa: only RUB currency is supported.';
$string['alfa_register_error'] = 'Alfa register error: {$a}';
$string['alfa_missing_formurl'] = 'Invalid Alfa response: missing formUrl or orderId.';
$string['paymentgatewayerror'] = 'Payment gateway error: {$a}';

// Extra hardening (optional guards)
$string['alfa_price_mismatch'] = 'Price mismatch detected for Alfa. Please try again or contact support. ({$a})';
$string['alfa_amount_mismatch'] = 'Amount mismatch detected for Alfa. Please try again or contact support. ({$a})';

// Stripe gateway
$string['stripe:missingamount'] = 'Stripe: missing amount to create the checkout session.';
$string['stripe:productname'] = '{$a} — payment';
$string['stripe:missingpriceidforsubscription'] = 'Stripe: missing price_id for subscription mode.';
$string['stripe:missingpriceid'] = 'Stripe: missing price_id.';
$string['stripe:sdkautoloadnotfound'] = 'Stripe SDK autoload file not found: {$a}';
$string['missing_customer_id'] = 'Missing customer ID.';

// Stripe extra hardening (optional guards)
$string['stripe_invalid_currency'] = 'Stripe: invalid or unsupported currency: {$a}.';
$string['stripe_nonpositive_amount'] = 'Stripe: amount must be greater than 0.';

// UI — generic
$string['payui_error_title'] = 'We couldn’t complete your payment';
$string['payui_error_subtitle'] = 'Something went wrong on our side or with the payment service.';
$string['payui_error_generic'] = 'Please try again. If the problem persists, contact us and we’ll help you complete the order.';

$string['payui_cta_retry'] = 'Try again';
$string['payui_cta_back'] = 'Back to plans';
$string['payui_cta_contact'] = 'Contact support';
$string['payui_support_hint'] = 'Need help? Write to {$a}';

$string['payui_order_ref'] = 'Order reference: {$a}';

// UI — reasons (short, user-friendly)
$string['payui_reason_security'] = 'Your session expired. Refresh the page and try again.';
$string['payui_reason_link'] = 'The payment page link was not available. Please try again.';
$string['payui_reason_currency'] = 'We couldn’t confirm the payment currency. Please try again.';
$string['payui_reason_amount'] = 'We couldn’t confirm the amount. Please try again.';
$string['payui_reason_gateway'] = 'The payment service returned an error. Please try again.';
$string['payui_reason_canceled'] = 'The payment was canceled.';
$string['payui_reason_declined'] = 'The payment was declined by your bank.';
$string['payui_reason_expired'] = 'The payment session expired. Please start again.';
$string['payui_reason_owner'] = 'This payment link does not belong to your account.';
$string['payui_reason_status'] = 'This payment link is no longer valid.';

// Success & pending UI
$string['payui_success_title'] = 'Payment confirmed';
$string['payui_success_subtitle'] = 'Thank you! Your payment was successful.';
$string['payui_success_thanks'] = 'Welcome! Your access has been activated.';
$string['payui_success_check_email'] = 'We’ve sent you an email with your access details and next steps. Please sign in to start learning.';
$string['payui_pending_title'] = 'Almost there…';
$string['payui_pending_msg'] = 'Your payment is being confirmed. This can take up to a minute. You can safely close this page; we’ll email you once it’s done.';

// CTAs & labels
$string['payui_cta_my_subscriptions'] = 'Go to my purchases';
$string['payui_cta_signin'] = 'Sign in';
$string['payui_session_display'] = 'Checkout session: {$a}';
$string['payui_label_price'] = 'Price';
$string['payui_label_plan'] = 'Plan';
$string['payui_cta_mycourses'] = 'Go to My Courses';


$string['settings_support_email'] = 'Support email';
$string['settings_support_email_desc'] = 'Used on payment pages (error/success) to show a contact link.';
$string['stripe_price_mismatch'] = 'Stripe: price mismatch detected. Please try again or contact support. ({$a})';

$string['settings_trial_section'] = '7-day Trial';
$string['settings_trial_planid'] = 'Trial plan (ID)';
$string['settings_trial_planid_desc'] = 'ID of the plan flagged as "is_trial".';
$string['settings_trial_duration_days'] = 'Trial duration (days)';
$string['settings_trial_duration_days_desc'] = 'Number of days for the free trial.';
$string['settings_trial_discount_percent'] = 'Discount (%) during window';
$string['settings_trial_discount_percent_desc'] = 'For tracing and PSP-agnostic application.';
$string['settings_trial_discount_hours'] = 'Discount window (hours)';
$string['settings_trial_discount_hours_desc'] = 'E.g., 72 hours after the trial starts.';
$string['missing_trial_plan'] = 'No trial plan configured (trial_plan_id).';


$string['settings_paylock_section'] = 'Price lock (checkout)';
$string['settings_paylock_strict'] = 'Strict mode on mismatch';
$string['settings_paylock_strict_desc'] = 'If enabled, subscription creation fails when the paid amount differs from the locked amount.';
$string['settings_paylock_tolerance'] = 'Mismatch tolerance (in cents)';
$string['settings_paylock_tolerance_desc'] = 'Maximum allowed difference between locked and paid amounts (default: 2 cents).';

$string['pricing_missing_price'] = 'No price is defined for this plan and currency ({$a}).';
$string['cannot_purchase_trial_plan'] = 'This plan is a trial plan and cannot be purchased.';
$string['payment_mismatch_too_large'] = 'Payment mismatch exceeds allowed tolerance.';
$string['paylock_missing_lockdata'] = 'No price-lock (locked_*) data is available for this request.';
$string['paylock_invalid_minor'] = 'Invalid locked amount.';
$string['stripe_lock_requires_payment_mode'] = 'Price lock requires Checkout mode "payment" (fixed amount). Use one-time payment or re-enable coupons.';
$string['alfa_nonpositive_amount'] = 'Non-positive Alfa amount after price lock.';
$string['alfa:productname'] = 'Subscription';
$string['paylock_missing_context'] = 'Cannot compute price: user or plan is not defined.';

$string['currency_selector_label'] = 'Currency';
$string['currency_eur'] = '€ EUR';
$string['currency_rub'] = '₽ RUB';
$string['set_display_currency_symbols'] = 'Show currency symbol';
$string['set_display_currency_symbols_desc'] = 'If enabled, prices use the symbol (e.g., €49). Otherwise, the code is shown (e.g., 49 EUR).';
$string['badge_limited_offer'] = 'Limited offer -{$a}%';
$string['price_unavailable_in'] = 'Not available in {$a->curr} — showing {$a->fallback}.';
$string['checkout_discount_note']        = 'Your intro offer is still active';
$string['checkout_discount_note_prefix'] = '🎁 −{$a}% on all course purchases. Discount available only';
$string['days_short']                    = 'd';

$string['cancel_price_title']  = 'Expected price';
$string['error_price_title']   = 'Expected price';
$string['success_price_title'] = 'Amount paid';
$string['reason_trial72h']     = 'A -{$a}% discount was applied during your trial period.';
$string['task_sub_expiry_reminders'] = 'Subscription expiry reminders';
$string['expiry_reminder_subject_today'] = 'Your subscription expires today';

$string['email_copy_verbose'] = 'Technical appendix in copies (log@...)';
$string['email_copy_verbose_desc'] = 'If enabled, each copy appends a technical summary (PR/User/Plan/Sub, locked_*, etc.).';

$string['existing_account_login_first'] = 'We found an existing account for this email. Please log in to continue so we can attach the purchase to your account.';
$string['task_enrol_scope_fill'] = 'Subscriptions — fill enrolments from plan scope';

$string['paymentsuccess_redirect_msg'] = 'You will be redirected to "My courses" in {$a} seconds…';
$string['paymentsuccess_mascot_alt']   = 'Gustave the giraffe congratulates you on your successful payment.';
$string['paymentcancel_mascot_alt'] = 'Illustration of a cancelled payment.';
$string['paymenterror_mascot_alt'] = 'Illustration of a payment error.';
$string['plan_price_per_month'] = '(equivalent to {$a}/month)';

$string['upgrade_window_label']    = 'Calculation window: {$a->start} → {$a->end}';
$string['upgrade_ref_prices']      = 'Reference prices: current = {$a->current}, target = {$a->target}';
$string['upgrade_part_past']       = 'Part already used at the current rate: {$a}';
$string['upgrade_part_future']     = 'Remaining part at the target plan rate: {$a}';
$string['upgrade_base_total']      = 'Theoretical total for this window: {$a}';
$string['upgrade_already_paid']    = 'Already paid in this window: {$a}';
$string['upgrade_base_minus_paid'] = 'Upgrade amount before promotion: {$a->base} − {$a->paid} = {$a->diff}';
$string['upgrade_discount_line']   = 'Promotion −{$a->pct}% applied to {$a->before} ⇒ {$a->after}';
$string['upgrade_amount_proposed'] = 'Proposed amount: {$a}';

$string['trial_subscribe_now'] = 'Buy now';
$string['plan_label'] = 'Plan';
$string['checkout_go_to_payment_discount'] = 'Buy with discount';
$string['checkout_full_access_line'] = 'Unlimited access to all lessons in the course.';
$string['summary_price_title_single'] = 'Subscription for {$a}';


$string['digital_pdf_intro'] = 'A practical guide to understand, memorize and review French third-group verbs without getting lost in endless tables.';
$string['digital_pdf_item_1'] = 'The main families of third-group verbs.';
$string['digital_pdf_item_2'] = 'The most useful conjugation patterns.';
$string['digital_pdf_item_3'] = 'Clear explanations to help you spot regular patterns.';
$string['digital_pdf_item_4'] = 'A PDF support you can keep and review at your own pace.';
$string['digital_pdf_price_eur'] = 'EUR price';
$string['digital_pdf_price_rub'] = 'RUB price';
$string['digital_pdf_buy_title'] = 'Buy the PDF';
$string['digital_pdf_firstname'] = 'First name';
$string['digital_pdf_lastname'] = 'Last name';
$string['digital_pdf_email'] = 'Email';
$string['digital_pdf_email_help'] = 'The download link will be sent to this address.';
$string['digital_pdf_buy_eur'] = 'Buy in EUR: {$a->price} €';
$string['digital_pdf_buy_rub'] = 'Buy in RUB: {$a->price} ₽';
$string['digital_payment_created'] = 'PDF payment request created. PSP connection will be added in the next step.';
$string['digital_success_title'] = 'Campus<small><sup>FR</sup></small> PDF purchase';
$string['digital_success_preview'] = 'Preview mode: the payment request has been created, but Stripe/Alfa payment is not connected yet.';
$string['digital_success_request_created'] = 'Request created';
$string['digital_success_product'] = 'Product';
$string['digital_success_email'] = 'Email';
$string['digital_success_amount'] = 'Amount';
$string['digital_success_provider'] = 'Provider';
$string['digital_success_status'] = 'Status';
$string['digital_cancel_title'] = 'Payment cancelled';
$string['digital_cancel_message'] = 'The payment was not completed. You can try again if you wish.';
$string['digital_cancel_retry'] = 'Try payment again';
$string['digital_success_download'] = 'Download the PDF';
$string['digital_success_payment_pending'] = 'Your payment is being validated. If you have just paid, refresh this page in a few seconds.';
$string['digital_download_not_paid'] = 'This download is not available because the payment has not been validated.';
$string['digital_download_expired'] = 'This download link has expired.';
$string['digital_download_file_missing'] = 'The PDF file could not be found.';
$string['digital_success_payment_confirmed'] = 'Your payment has been confirmed. You can now download your PDF.';
$string['digital_mail_access_subject'] = 'Your Campus<small><sup>FR</sup></small> PDF is ready 📘';
$string['digital_mail_access_intro'] = 'Thank you for your purchase! Your PDF is now available.';
$string['digital_mail_access_hint'] = 'You can download your PDF using the button below. We recommend saving it on your device for easy access anytime.';
$string['digital_mail_download_button'] = 'Download the PDF';

$string['digital_mail_receipt_subject'] = 'Your CampusFR purchase receipt';
$string['digital_mail_receipt_intro'] = 'This email confirms your purchase on Campus<small><sup>FR</sup></small>. You will find your order summary below.';

$string['digital_mail_product'] = 'Product';
$string['digital_mail_amount'] = 'Amount';
$string['digital_mail_payment_date'] = 'Payment date';

$string['digital_success_paid_heading'] = 'Thank you for your purchase!';
$string['digital_success_paid_intro'] = 'Your payment has been confirmed. Your PDF is ready to download.';
$string['digital_success_pending_heading'] = 'Payment is being validated';

$string['digital_success_email_sent_hint'] = 'We have also sent you the download link and receipt by email.';
$string['digital_success_pending_hint'] = 'If you have just paid, refresh this page in a few seconds.';

$string['digital_sales_hero_intro'] = 'A practical and easy-to-follow guide to understand French third-group verbs, recognize patterns and stop learning them “randomly”.';

$string['digital_sales_lifetime_access'] = '✔ Lifetime access to the PDF after purchase';

$string['digital_sales_content_title'] = 'What you will find inside this PDF';
$string['digital_sales_content_item_1'] = 'The main families of third-group verbs.';
$string['digital_sales_content_item_2'] = 'The most useful conjugation patterns.';
$string['digital_sales_content_item_3'] = 'Simple and visual explanations.';
$string['digital_sales_content_item_4'] = 'Tables designed for quick review.';
$string['digital_sales_content_item_5'] = 'Logical groupings to help memorization.';
$string['digital_sales_content_item_6'] = 'A practical support to keep during your revision sessions.';

$string['digital_sales_forwho_title'] = 'This PDF is ideal if…';
$string['digital_sales_forwho_item_1'] = 'you often confuse third-group verbs.';
$string['digital_sales_forwho_item_2'] = 'you want to finally recognize patterns.';
$string['digital_sales_forwho_item_3'] = 'you are learning French independently.';
$string['digital_sales_forwho_item_4'] = 'you are preparing for an exam or certification.';
$string['digital_sales_forwho_item_5'] = 'you want a practical support to keep close at hand.';

$string['digital_sales_secure_payment'] = '🔒 Secure payment via Stripe or Alfa.';
$string['digital_sales_instant_access'] = '⚡ Instant access after payment + download link sent by email.';
$string['digital_cover_zoom_hint'] = 'Click the image to enlarge it.';

$string['digital_purchases_title'] = 'Digital product purchases';
$string['digital_purchases_export_xlsx'] = 'Export to XLSX';
$string['digital_purchases_count'] = '{$a} purchase(s) found.';
$string['digital_purchases_payment_date'] = 'Payment date';
$string['digital_purchases_emails'] = 'Emails';
$string['digital_purchases_access_email_short'] = 'PDF';
$string['digital_purchases_receipt_email_short'] = 'Receipt';

$string['digital_download_mobile_missing'] = 'The mobile version of this file is not available.';
$string['digital_success_download_main'] = 'Download standard version';
$string['digital_success_download_mobile'] = 'Download mobile version';
$string['digital_mail_download_main_button'] = 'Download standard version';
$string['digital_mail_download_mobile_button'] = 'Download mobile version';

$string['task_reconcile_digital_payments'] = 'Reconcile pending digital product payments';
$string['digital_purchases_emails_status'] = 'PDF / Receipt';
$string['digital_purchases_payment_or_creation_date'] = 'Payment / creation date';
$string['digital_purchases_db_status'] = 'DB status';
$string['digital_purchases_provider_status'] = 'Provider status';
$string['digital_purchases_provider_reason'] = 'Provider reason / details';

$string['digital_sales_stats_button'] = 'Sales statistics';

$string['digital_sales_stats_days'] = '{$a} day';
$string['digital_sales_stats_days_plural'] = '{$a} days';

$string['digital_purchases_show_paid'] = 'Show PAID';
$string['digital_purchases_show_pending'] = 'Show pending';
$string['digital_purchases_show_pending_paid_provider'] = 'Pending / PAID provider';
$string['digital_purchases_show_all'] = 'Show all';
$string['digital_purchases_reconcile_pending'] = 'Reconcile paid pending payments';
$string['digital_purchases_hide_provider_status'] = 'Hide live provider statuses';
$string['digital_purchases_check_provider_status'] = 'Check live provider statuses';
$string['digital_purchases_provider_status_info'] = 'Provider statuses are checked in read-only mode. No database changes and no emails are sent.';
$string['digital_purchases_reconcile_confirm'] = 'Confirm reconciliation of provider-paid pending payments?';

$string['digital_download_classic'] = 'Classic';
$string['digital_download_mobile'] = 'Mobile';

$string['digital_sales_stats_title'] = 'Digital sales statistics';
$string['digital_sales_stats_back_to_purchases'] = 'Back to purchases';
$string['digital_sales_stats_sales_found'] = 'Paid sales found: {$a}';
$string['digital_sales_stats_no_sales'] = 'No paid sales during this period.';
$string['digital_sales_stats_histogram'] = 'Number of sales over time';
$string['digital_sales_stats_cumulative'] = 'Cumulative sales';
$string['digital_sales_stats_show_from'] = 'Show from';

$string['digital_catalog_title'] = 'Campus<small><sup>FR</sup></small> Digital Store';
$string['digital_catalog_intro'] = 'Discover our PDFs, guides and practical resources to improve your French at your own pace.';
$string['digital_catalog_empty'] = 'No digital products are currently available.';
$string['digital_catalog_view_product'] = 'View product';

$string['digital_pdf_badge'] = 'Digital PDF';

$string['digital_product_not_found_redirect'] = 'This product is not available. You have been redirected to the store.';
$string['digital_product_not_found_catalog_notice'] = 'The requested product is not available or no longer exists. You can browse the available products below.';

$string['digital_rub_confirm_title'] = 'Payment in roubles';
$string['digital_rub_confirm_vpn'] = '💡 Before making the payment, it is recommended to temporarily disable your VPN — otherwise the payment page may sometimes open incorrectly or display an error 😊';
$string['digital_rub_confirm_continue'] = 'Continue to payment';

$string['digital_products_admin_title'] = 'Digital products';
$string['digital_products_add'] = 'Add digital product';
$string['digital_products_view_purchases'] = 'View purchases';
$string['digital_products_view_catalog'] = 'View store';
$string['digital_products_count'] = '{$a} digital product(s)';
$string['digital_products_cover'] = 'Cover';
$string['digital_products_slug'] = 'Slug';
$string['digital_products_titles'] = 'Titles';
$string['digital_products_prices'] = 'Prices';
$string['digital_products_files'] = 'Files';
$string['digital_products_status'] = 'Status';
$string['digital_products_purchases'] = 'Purchases';
$string['digital_products_sortorder'] = 'Order';
$string['digital_products_actions'] = 'Actions';
$string['digital_products_cover_missing'] = 'Missing image';
$string['digital_products_file_main'] = 'Classic';
$string['digital_products_file_mobile'] = 'Mobile';
$string['digital_products_enabled'] = 'Enabled';
$string['digital_products_disabled'] = 'Disabled';
$string['digital_products_open_public'] = 'Open';
$string['digital_products_delete_confirm'] = 'Delete this digital product?';
$string['digital_product_edit_new_title'] = 'New digital product';
$string['digital_product_edit_edit_title'] = 'Edit digital product';

$string['digital_product_edit_main_info'] = 'Main information';
$string['digital_product_edit_internal_name'] = 'Internal name';

$string['digital_product_edit_price_eur'] = 'EUR price';
$string['digital_product_edit_price_rub'] = 'RUB price';

$string['digital_product_edit_files_hint'] = 'Files must be placed manually in the appropriate folders: PDFs in moodledata/local_subscriptions/private_pdfs, covers in local/subscriptions/pix/cover.';

$string['digital_product_edit_translations'] = 'Translations';

$string['digital_product_edit_title'] = 'Title';

$string['digital_product_edit_saved'] = 'Digital product saved.';
$string['digital_product_edit_slug_exists'] = 'This slug already exists.';
$string['digital_product_edit_current_file'] = 'Current file';
$string['digital_product_edit_no_file'] = 'No file currently selected.';
$string['digital_product_edit_click_to_upload'] = 'Click here to choose or replace the file.';
$string['digital_product_edit_access_note'] = 'Access note after purchase';
$string['digital_product_edit_content_title'] = 'Content block title';
$string['digital_product_edit_forwho_title'] = '“Who it is for” block title';
$string['digital_product_edit_buy_title'] = 'Purchase block title';
$string['digital_products_status_updated'] = 'Product status updated.';
$string['digital_products_enable'] = 'Enable';
$string['digital_products_disable'] = 'Disable';
$string['digital_products_duplicate'] = 'Duplicate';
$string['digital_products_duplicated'] = 'Product duplicated. You can now edit the copy.';
$string['digital_products_deleted'] = 'Digital product deleted.';
$string['digital_products_delete_has_purchases'] = 'This product cannot be deleted because it already has purchases.';

$string['digital_reassurance_instant'] = 'Instant access after payment';
$string['digital_reassurance_versions'] = 'Classic + mobile versions included';
$string['digital_reassurance_email'] = 'Download links automatically sent by email';
$string['digital_reassurance_nocampus'] = 'No Campus<small><sup>FR</sup></small> account required';
$string['digital_reassurance_secure'] = 'Secure payment via Stripe / Alfa Bank';

$string['digital_redirecting_payment'] = 'Redirecting to the payment page…';

$string['digital_redirecting_payment_desc'] =
'Please wait a few seconds. Do not close this page.';

$string['digital_success_thank_you'] = 'Thank you for your purchase!';
$string['digital_success_confirmed_intro'] = 'Your payment is confirmed. Your files are ready to download.';
$string['digital_success_pending_title'] = 'Payment confirmation in progress';
$string['digital_success_payment_pending_support'] = 'Your payment is being confirmed. If you have just paid, refresh this page in a few seconds. If nothing changes, contact us at support@campusfr.fr.';
$string['digital_success_summary_title'] = 'Purchase summary';
$string['digital_success_main_version_hint'] = 'Standard version: ideal for computer, tablet or printing.';
$string['digital_success_mobile_version_hint'] = 'Mobile version: optimized for reading the PDF on your phone.';
$string['digital_success_email_sent_notice'] = 'We have also sent the download links and receipt by email.';
$string['digital_success_support_title'] = 'Having trouble downloading?';
$string['digital_success_support_text'] = 'Write to us at {$a}. We will help you as soon as possible.';
$string['digital_success_back_to_shop'] = 'View other products';

$string['digital_cancel_heading'] = 'Payment not completed';
$string['digital_cancel_intro'] = 'Your purchase has not been confirmed. If you left the payment page before confirmation, access has not been activated yet.';
$string['digital_cancel_vpn_hint'] = '💡 If you are paying in RUB, please temporarily disable your VPN: the payment page may sometimes open incorrectly or display an error.';
$string['digital_cancel_support_text'] = 'If you think you have been charged or need help, write to us at {$a}.';
$string['digital_cancel_gateway_timeout'] =
'The payment page could not be opened at the moment. This may be temporary. Please try again in a few minutes.';


$string['planentitlementsfor'] = 'Access rights for plan: {$a}';
$string['addentitlement'] = 'Add access right';
$string['editentitlement'] = 'Edit access right';
$string['deleteentitlement'] = 'Delete access right';
$string['saveentitlement'] = 'Save access right';
$string['noentitlements'] = 'No access rights have been configured for this plan yet.';
$string['entitlementcreated'] = 'Access right created successfully.';
$string['entitlementupdated'] = 'Access right updated successfully.';
$string['entitlementdeleted'] = 'Access right deleted successfully.';
$string['confirmdeleteentitlement'] = 'Do you really want to delete this access right?';

$string['entitlement_course'] = 'Course';
$string['entitlement_accesslevel'] = 'Access level';
$string['entitlement_role'] = 'Role in the course';
$string['entitlement_groupname'] = 'Group name';
$string['entitlement_groupname_help'] = 'Optional. If filled, the user will be added to this Moodle group in the selected course.';
$string['entitlement_priority'] = 'Priority';
$string['entitlement_priority_help'] = 'Higher priority access can replace lower priority access. Suggested values: trial = 10, grammar = 50, full = 100.';
$string['entitlement_already_exists'] = 'This plan already has this access level for this course.';

$string['accesslevel_trial'] = 'Trial';
$string['accesslevel_grammar'] = 'Grammar only';
$string['accesslevel_full'] = 'Full access';
$string['invalidplanid'] = 'Invalid or missing plan.';


$string['planupgradesintro'] = 'Define which plans can be upgraded to another plan. For example: A2 Grammar → A2 Full. With difference pricing, the upgrade price is calculated as the target plan price minus the current plan price in the same currency.';
$string['addupgrade'] = 'Add upgrade';
$string['editupgrade'] = 'Edit upgrade';
$string['deleteupgrade'] = 'Delete upgrade';
$string['saveupgrade'] = 'Save upgrade';
$string['noupgrades'] = 'No plan upgrades have been configured yet.';
$string['upgradecreated'] = 'Upgrade rule created successfully.';
$string['upgradeupdated'] = 'Upgrade rule updated successfully.';
$string['upgradedeleted'] = 'Upgrade rule deleted successfully.';
$string['confirmdeleteupgrade'] = 'Do you really want to delete this upgrade rule?';

$string['upgrade_fromplan'] = 'From plan';
$string['upgrade_toplan'] = 'To plan';
$string['upgrade_pricingmode'] = 'Pricing mode';
$string['upgrade_pricingmode_help'] = 'For now, only difference pricing is supported: upgrade price = target plan price - current plan price, in the same currency.';
$string['upgrade_pricing_difference'] = 'Difference between the two plan prices';
$string['upgrade_isactive'] = 'Active';
$string['upgrade_same_plan_error'] = 'The source plan and target plan must be different.';
$string['upgrade_already_exists'] = 'This upgrade rule already exists.';

$string['inactive'] = 'Inactive';
$string['status'] = 'Status';

$string['planentitlements'] = 'Plan access rights';
$string['planupgrades'] = 'Plan upgrades';
$string['option_upgrade_difference'] = 'Upgrade to the full version';
$string['plan_already_owned'] = 'You already have access to this plan.';
$string['upgrade_from_to_summary'] = 'You are upgrading from "{$a->from}" to "{$a->to}". You only pay the price difference.';
$string['upgrade_badge'] = 'Upgrade';
$string['upgrade_discount_applied'] = 'Your trial discount has been applied: {$a->discount}%.';
$string['upgrade_cta'] = 'Upgrade now';

$string['unlock_grammar_title'] = 'Activity included in the Grammar module';
$string['unlock_grammar_text'] = 'This activity is part of the Grammar module. You can buy the Grammar module only or the full course.';
$string['unlock_grammar_button'] = 'Buy Grammar';

$string['unlock_full_title'] = 'Activity reserved for the full version';
$string['unlock_full_text'] = 'This activity is not included in the Grammar module. Upgrade to the full version to access all course content.';
$string['unlock_full_button'] = 'Buy the full version';

$string['restricted_access_title'] = 'Restricted access';
$string['restricted_access_text'] = 'Buy the course to unlock this activity.';
$string['buy'] = 'Buy';

$string['plan_already_covered'] = 'You already have equivalent or higher access to this content.';
$string['all_courses_owned_title'] = 'You already have access to all available courses';
$string['all_courses_owned_text'] = 'No new purchase is needed right now. You can continue learning from your course area.';

$string['unlock_subscriber_title'] = 'Activity reserved for members';
$string['unlock_subscriber_text'] = 'This activity is not available with trial access. Choose a plan to continue.';
$string['unlock_subscriber_button'] = 'View plans';

$string['digital_purchases_profile_title'] = 'Your digital purchases';
$string['digital_purchase_date'] = 'Purchase date';
$string['digital_purchases_filter_registered'] = 'Registered buyers';
$string['digital_purchases_filter_guests'] = 'Unregistered buyers';
$string['digital_purchases_campus_account'] = 'Campus account';

$string['course_purchases_profile_title'] = 'Your course purchases';
$string['purchase_date'] = 'Purchase date';
$string['available_courses'] = 'Available courses';
$string['digital_product_view_page'] = 'View product page';

$string['digital_purchases_empty'] = 'You have not purchased any digital product yet.';

$string['digital_purchase_downloads'] = 'Downloads';
$string['digital_product'] = 'Digital product';
$string['user_purchases_title'] = '{$a}’s purchases';
$string['admin_details'] = 'Admin information';
$string['subfield_id'] = 'ID';
$string['subfield_userid'] = 'User ID';
$string['subfield_productid'] = 'Product ID';
$string['subfield_slug'] = 'Slug';
$string['subfield_created_at'] = 'Created on';
$string['subfield_paid_at'] = 'Paid on';
$string['subfield_expires_at'] = 'Expires on';
$string['subfield_paymentid'] = 'Payment ID';
$string['subfield_provider_paymentid'] = 'Provider payment ID';
$string['subfield_checkout_url'] = 'Checkout URL';
$string['subfield_success_url'] = 'Success URL';
$string['subfield_cancel_url'] = 'Cancel URL';
$string['subfield_download_token'] = 'Download token';
$string['subfield_raw_response'] = 'Raw provider response';
$string['admin_subscription_details'] = 'Admin subscription information';
$string['admin_payment_request_details'] = 'Payment request information';
$string['payment_request_not_found'] = 'No linked payment request found';

$string['subfield_planid'] = 'Plan ID';
$string['subfield_status'] = 'Status';
$string['subfield_payment_request_id'] = 'Payment request ID';
$string['subfield_provider_subscription_id'] = 'Provider subscription ID';
$string['subfield_provider_customer_id'] = 'Provider customer ID';
$string['subfield_renewal_date'] = 'Renewal date';
$string['subfield_updated_at'] = 'Updated on';
$string['subfield_operation'] = 'Operation';
$string['subfield_sessionid'] = 'Session ID';
$string['subfield_price'] = 'Price';
$string['subfield_amount_minor'] = 'Amount in minor units';
$string['subfield_locked_list_price'] = 'Locked list price';
$string['subfield_locked_discount_percent'] = 'Locked discount (%)';
$string['subfield_locked_discount_amount'] = 'Locked discount amount';
$string['subfield_locked_discount_reason'] = 'Locked discount reason';
$string['subfield_locked_final_price'] = 'Locked final price';
$string['subfield_locked_at'] = 'Price locked on';
$string['subfield_attempts'] = 'Attempts';
$string['subfield_last_attempt'] = 'Last attempt';
$string['subfield_last_error'] = 'Last error';
$string['subfield_created_ip'] = 'Creation IP';
$string['subfield_accept_language'] = 'Accept language';
$string['subfield_http_referer'] = 'HTTP referer';
$string['subfield_payment_link'] = 'Payment link';
$string['subfield_response_json'] = 'Provider JSON response';
$string['subfield_created_useragent'] = 'Creation User-Agent';

$string['manage_user_subscriptions'] = 'Manage user subscriptions';
$string['all_plans'] = 'All plans';
$string['filter_by_plan'] = 'Filter by plan';
$string['perpage'] = 'Results per page';
$string['filter'] = 'Filter';
$string['actions'] = 'Actions';
$string['no_subscriptions_found'] = 'No subscriptions found.';
$string['confirm_delete_subscription'] = 'Confirm deletion';
$string['confirm_delete_subscription_body'] = 'Do you really want to delete this subscription? This will also remove access to the courses linked to this plan.';
$string['subscription_deleted_successfully'] = 'Subscription deleted successfully.';
$string['close'] = 'Close';
$string['delete'] = 'Delete';

$string['edit_user_subscription'] = 'Edit user subscription';
$string['subscription_summary'] = 'Subscription summary';
$string['no_end_date'] = 'Subscription without end date';
$string['end_date_before_start_date'] = 'The end date cannot be earlier than the start date.';
$string['subscription_updated_successfully'] = 'Subscription updated successfully.';
$string['invalid_subscription_status'] = 'Invalid subscription status.';

$string['status_active'] = 'Active';
$string['status_queued'] = 'Queued';
$string['status_inactive'] = 'Inactive';
$string['status_expired'] = 'Expired';
$string['status_suspended'] = 'Suspended';
$string['status_canceled'] = 'Canceled';
$string['status_replaced'] = 'Replaced';
$string['status_pending'] = 'Pending payment';
$string['status_failed'] = 'Failed';
$string['status_error'] = 'Error';
$string['status_paid'] = 'Paid';
$string['status_completed'] = 'Completed';

$string['existing_user'] = 'Existing user';
$string['new_user'] = 'New user';
$string['search_user_placeholder'] = 'Search by first name, last name or email';
$string['manual_subscription_user_section'] = 'User';
$string['manual_subscription_plan_section'] = 'Subscription';
$string['missing_user_for_manual_subscription'] = 'No valid user was selected or created.';
$string['not_set'] = 'Not set';

$string['admin_dashboard'] = 'CampusFR administration';
$string['admin_dashboard_intro'] = 'Find the main tools for managing subscriptions, plans and digital products.';

$string['admin_card_user_subscriptions_title'] = 'User subscriptions';
$string['admin_card_user_subscriptions_desc'] = 'View, filter, edit or delete active and past subscriptions.';

$string['admin_card_add_subscription_title'] = 'Add subscription';
$string['admin_card_add_subscription_desc'] = 'Manually create a subscription for an existing or new user.';

$string['admin_card_import_csv_title'] = 'CSV import';
$string['admin_card_import_csv_desc'] = 'Import subscriptions in bulk from a CSV file.';

$string['admin_card_plans_title'] = 'Plans and access';
$string['admin_card_plans_desc'] = 'Manage plans, prices, translations, scopes, entitlements and upgrades.';

$string['admin_card_digital_products_title'] = 'Digital products';
$string['admin_card_digital_products_desc'] = 'Create and manage PDF files, guides and separately sold resources.';

$string['admin_card_digital_purchases_title'] = 'Digital purchases';
$string['admin_card_digital_purchases_desc'] = 'Review digital purchases and related payment information.';

$string['admin_card_digital_stats_title'] = 'Digital statistics';
$string['admin_card_digital_stats_desc'] = 'Track sales, revenue and digital product performance.';
$string['date_format_placeholder'] = 'dd/mm/yyyy';
$string['digital_invalid_email'] = 'Please enter a valid email address.';
$string['subscription_period'] = 'Period';
$string['unlimited'] = 'Unlimited';

$string['back_to_admin_dashboard'] = 'Back to Campus<small><sup>FR</sup></small> back-office';

$string['crm_users'] = 'CRM users';

$string['crm_search_user_placeholder'] = 'Search by first name, last name or email';
$string['crm_no_users_found'] = 'No users found.';
$string['crm_no_subscriptions'] = 'No subscriptions found for this user.';
$string['crm_no_digital_purchases'] = 'No digital purchases found for this user.';
$string['view_moodle_profile'] = 'View Moodle profile';

$string['admin_card_crm_users_title'] = 'CRM users';
$string['admin_card_crm_users_desc'] = 'Search for a user and view their complete profile.';
$string['subscriptions'] = 'Subscriptions';

$string['product'] = 'Product';
$string['digital_purchases'] = 'Digital purchases';
$string['crm_quick_actions'] = 'Quick actions';
$string['crm_send_email'] = 'Send email';
$string['crm_reset_password'] = 'Reset password';
$string['subject'] = 'Subject';
$string['message'] = 'Message';
$string['send'] = 'Send';
$string['crm_email_button_optional'] = 'Optional button';
$string['crm_email_button_label'] = 'Button text';
$string['crm_email_button_url'] = 'Button link';
$string['crm_email_button_url_required'] = 'The button link is required when button text is provided.';
$string['crm_email_button_label_required'] = 'The button text is required when a button link is provided.';
$string['crm_email_subject_required'] = 'The email subject is required.';
$string['crm_email_body_required'] = 'The email message is required.';
$string['crm_email_sent_successfully'] = 'Email sent successfully.';
$string['crm_notify_user_by_email'] = 'Send the new password by email';
$string['crm_password_too_short'] = 'The password must contain at least 8 characters.';
$string['crm_password_updated_successfully'] = 'Password updated successfully.';
$string['crm_reset_password_warning'] = 'This action immediately replaces the user’s current password.';
$string['crm_password_email_subject'] = 'Your Campus<small><sup>FR</sup></small> password has been updated';
$string['crm_password_email_intro'] = 'Hello {$a},';
$string['crm_password_email_password'] = 'Here is your new password:';
$string['crm_password_email_security'] = 'For security reasons, we recommend changing it after your next login.';
$string['crm_login_button'] = 'Log in to CampusFR';
$string['crm_admin_history'] = 'CRM history';
$string['crm_no_admin_history'] = 'No recorded action for this user.';
$string['admin_action'] = 'Action';
$string['admin_actor'] = 'Performed by';
$string['details'] = 'Details';
$string['date'] = 'Date';

$string['adminlog_email_custom_sent'] = 'Custom email sent';
$string['adminlog_email_password_reset_notice_sent'] = 'Password reset email sent';
$string['adminlog_user_password_updated'] = 'Password changed';
$string['crm_internal_notes'] = 'Internal notes';
$string['crm_note_placeholder'] = 'Add an internal note visible only to the team…';
$string['crm_add_note'] = 'Add note';
$string['crm_no_notes'] = 'No internal notes for this user.';
$string['crm_note_required'] = 'The note cannot be empty.';
$string['crm_note_added_successfully'] = 'Note added successfully.';
$string['adminlog_user_note_added'] = 'Internal note added';

$string['crm_timeline'] = 'CRM timeline';
$string['crm_timeline_empty'] = 'No event recorded for this user.';
$string['crm_timeline_note_added'] = 'Internal note added';
$string['adminlog_subscription_created'] = 'Subscription created';
$string['adminlog_subscription_created_manual'] = 'Subscription created manually';
$string['adminlog_subscription_updated'] = 'Subscription updated';
$string['adminlog_subscription_deleted'] = 'Subscription deleted';
$string['adminlog_subscription_status_updated'] = 'Subscription status updated';
$string['adminlog_subscription_dates_updated'] = 'Subscription dates updated';

$string['adminlog_digital_purchase_created'] = 'Digital purchase created';
$string['adminlog_digital_purchase_paid'] = 'Digital purchase paid';
$string['adminlog_digital_purchase_failed'] = 'Digital purchase failed';


$string['adminlog_payment_request_created'] = 'Payment request created';
$string['adminlog_payment_request_paid'] = 'Payment request paid';
$string['adminlog_payment_request_failed'] = 'Payment request failed';
$string['adminlog_payment_request_cancelled'] = 'Payment request cancelled';

$string['adminlog_trial_started'] = 'Trial started';
$string['adminlog_trial_expired'] = 'Trial expired';

$string['change_user'] = 'Change user';

$string['crm_no_accessible_courses'] = 'No course currently accessible.';
$string['access'] = 'Access';
$string['active'] = 'Active';
$string['until'] = 'until';

$string['digital_purchases_more_actions'] = 'More actions';
$string['digital_purchases_reconcile_done'] = 'Reconciliation completed: {$a->reconciled} payment(s) fixed, {$a->failed} payment(s) marked as failed, {$a->skipped} skipped, {$a->errors} error(s).';

$string['digital_purchases_export_filename'] = 'campusfr_pdf_purchases';
$string['digital_purchases_export_sheet'] = 'PDF purchases';
$string['digital_purchases_export_slug'] = 'Slug';
$string['digital_purchases_export_file_classic'] = 'Classic file';
$string['digital_purchases_export_file_mobile'] = 'Mobile file';
$string['digital_purchases_export_transaction_id'] = 'Transaction ID';
$string['digital_purchases_export_session_id'] = 'Session ID';
$string['digital_purchases_export_pdf_email_sent'] = 'PDF email sent';
$string['digital_purchases_export_receipt_sent'] = 'Receipt sent';
$string['digital_purchases_export_payment_date'] = 'Payment date';
$string['digital_purchases_export_last_update'] = 'Last update';
$string['digital_purchases_export_link_expiration'] = 'Link expiration';
$string['digital_purchases_export_download_classic'] = 'Classic download link';
$string['digital_purchases_export_download_mobile'] = 'Mobile download link';
$string['digital_purchases_export_last_error'] = 'Last DB error';
$string['no_expiration'] = 'No expiration';

$string['crm_timeline_digital_purchase'] = 'Digital purchase';
$string['digital_purchase_details'] = 'Digital purchase details';
$string['digital_purchase_resend_email'] = 'Resend email';
$string['digital_purchase_resend_email_confirm'] = 'Do you really want to resend the email for this digital purchase?';
$string['digital_purchase_resend_email_logged_only'] = 'Action logged. The actual sending will be connected in the next step.';
$string['digital_purchase_resend_email_success'] = 'Access email resent successfully.';
$string['adminlog_digital_link_resent'] = 'Digital access email resent';

$string['digital_purchase_regenerate_token'] = 'Regenerate link';
$string['digital_purchase_extend_token'] = 'Extend link';
$string['digital_purchase_regenerate_token_confirm'] = 'Do you really want to regenerate the download link? The old link will stop working.';
$string['digital_purchase_extend_token_confirm'] = 'Do you want to extend this download link by 30 days?';
$string['digital_purchase_token_regenerated_success'] = 'Download link regenerated successfully.';
$string['digital_purchase_token_extended_success'] = 'Download link extended successfully.';
$string['adminlog_digital_token_regenerated'] = 'Digital link regenerated';
$string['adminlog_digital_token_extended'] = 'Digital link extended';
$string['digital_purchase_link_expires'] = 'Link expiration';
$string['digital_purchase_old_token'] = 'Old token';
$string['digital_purchase'] = 'Digital purchase';
$string['digital_payment_provider'] = 'Payment provider';
$string['digital_session_id'] = 'Session / Order ID';
$string['digital_transaction_id'] = 'Transaction ID';
$string['digital_payment_link'] = 'Payment link';
$string['digital_attempts'] = 'Attempts';
$string['digital_last_attempt'] = 'Last attempt';
$string['digital_last_error'] = 'Last error';
$string['digital_created_ip'] = 'Creation IP';
$string['digital_accept_language'] = 'Browser language';
$string['digital_http_referer'] = 'HTTP referrer';
$string['digital_response_json'] = 'Provider JSON response';

$string['digital_check_provider_now'] = 'Check provider now';
$string['digital_check_provider_now_confirm'] = 'Do you want to check the payment status with the provider now?';
$string['digital_provider_check_done'] = 'Provider check completed: {$a->status}.';
$string['adminlog_digital_provider_checked'] = 'Provider manually checked';
$string['openlinkinnewwindow'] = 'Open link in a new window';
$string['last_update'] = 'Last update';
$string['digital_product_total_purchases'] = 'Total purchases';
$string['digital_product_paid_purchases'] = 'Paid purchases';
$string['digital_product_total_revenue'] = 'Revenue';
$string['digital_product_error_count'] = 'Errors';
$string['digital_product_recent_purchases'] = 'Recent purchases';
$string['digital_product_no_recent_purchases'] = 'No recent purchase for this product.';

$string['dashboard_team_card_title'] = 'Team session';
$string['dashboard_team_permissions'] = 'Active permissions';
$string['dashboard_team_no_permissions'] = 'No back-office permission detected.';
$string['dashboard_permission_users'] = 'CRM users';
$string['dashboard_permission_subscriptions'] = 'Subscriptions';
$string['dashboard_permission_digital'] = 'Digital products and purchases';
$string['dashboard_permission_payments'] = 'Payments';
$string['dashboard_permission_configuration'] = 'Configuration';
$string['dashboard_today'] = 'Today';
$string['dashboard_stats_new_users'] = 'New users';
$string['dashboard_stats_digital_purchases'] = 'Digital purchases';
$string['dashboard_stats_revenue'] = 'Revenue';
$string['dashboard_alerts'] = 'Needs attention';
$string['dashboard_alert_pending_digital'] = 'Pending digital purchases';
$string['dashboard_alert_failed_digital'] = 'Failed digital purchases';
$string['dashboard_alert_email_errors'] = 'Purchases with email/internal error';
$string['dashboard_alert_expired_tokens'] = 'Expired digital links';
$string['dashboard_recent_activity'] = 'Recent activity';
$string['dashboard_no_recent_activity'] = 'No recent activity.';

$string['crm_resend_welcome_email'] = 'Resend welcome';
$string['crm_resend_access_email'] = 'Resend access';
$string['crm_resend_receipt'] = 'Resend receipt';

$string['crm_welcome_email_resent_success'] = 'Welcome email resent.';
$string['crm_access_email_resent_success'] = 'Access email resent.';
$string['crm_receipt_resent_success'] = 'Receipt resent.';
$string['crm_subscription_extended_success'] = 'Subscription extended by {$a} days.';

$string['crm_receipt_not_available'] = 'No receipt is available for this subscription.';
$string['crm_timeline_course_purchase_paid'] = 'Course purchase paid';
$string['crm_timeline_payment_request'] = 'Payment request';
$string['crm_timeline_subscription_created'] = 'Subscription created';
$string['crm_timeline_trial_started'] = 'Trial started';
$string['payment_provider'] = 'Payment provider';
$string['transactionid'] = 'Transaction ID';
$string['crm_timeline_email_receipt_sent'] = 'Receipt sent';
$string['crm_timeline_email_access_sent'] = 'Access email sent';
$string['crm_timeline_email_welcome_sent'] = 'Welcome email sent';

$string['crm_email_type_receipt'] = 'Purchase receipt';
$string['crm_email_type_access'] = 'Subscription access';
$string['crm_email_type_welcome'] = 'Welcome';


$string['payment_request'] = 'Payment request';
$string['type'] = 'Type';
$string['subscription_details'] = 'Subscription details';
$string['crm_user_profile'] = 'User CRM profile';
$string['crm_no_payment_request_for_subscription'] = 'No payment request is linked to this subscription.';
$string['view_details'] = 'View details';
$string['admin_section_discounts'] = 'Discounts';
$string['admin_section_provider'] = 'Provider information';
$string['admin_section_payment_failures'] = 'Payment failures';
$string['admin_section_dates'] = 'Dates';

$string['admin_section_payment_request_identity'] = 'Identity / Contact';
$string['admin_section_payment_status'] = 'Status and operation';
$string['admin_section_amounts'] = 'Locked amounts';
$string['admin_section_links_tokens'] = 'Links and tokens';
$string['admin_section_reminders_attempts'] = 'Reminders and attempts';
$string['admin_section_request_context'] = 'Creation context';

$string['discount_percent'] = 'Discount (%)';
$string['discount_amount'] = 'Discount amount';
$string['discount_reason'] = 'Discount reason';

$string['phone'] = 'Phone';
$string['phone_country'] = 'Phone country';
$string['operation'] = 'Operation';
$string['reference_subscription_id'] = 'Reference subscription';
$string['amount_minor'] = 'Minor amount';

$string['locked_list_price'] = 'Locked list price';
$string['locked_discount_percent'] = 'Locked discount (%)';
$string['locked_discount_amount'] = 'Locked discount amount';
$string['locked_discount_reason'] = 'Locked discount reason';
$string['locked_final_price'] = 'Locked final price';
$string['locked_at'] = 'Locked on';

$string['retry_token'] = 'Retry token';
$string['retry_expires'] = 'Retry expiration';
$string['login_token'] = 'Login token';
$string['login_token_expires'] = 'Login token expiration';

$string['emailsent'] = 'Email sent';
$string['reminder_stage'] = 'Reminder stage';
$string['reminder1_at'] = 'First reminder sent on';
$string['reminder2_at'] = 'Second reminder sent on';

$string['created_ip'] = 'Creation IP';
$string['created_useragent'] = 'Creation User-Agent';
$string['accept_language'] = 'Accepted language';
$string['http_referer'] = 'HTTP referrer';
$string['expiration_date'] = 'Expiration date';
$string['subscription'] = 'Subscription';

$string['crm_timeline_expand_all'] = 'Expand all';
$string['crm_timeline_collapse_all'] = 'Collapse all';
$string['crm_timeline_view_details'] = 'View details';

$string['crm_timeline_recent'] = 'Recent history (last 30 days)';
$string['crm_timeline_middle'] = 'History (31 to 90 days)';
$string['crm_timeline_old'] = 'Older history (over 90 days)';

$string['crm_filter_purchases'] = 'Purchases';
$string['crm_filter_emails'] = 'Emails';
$string['crm_filter_other'] = 'Other';
$string['crm_timeline_by_actor'] = 'by {$a}';
$string['crm_timeline_by_admin'] = 'by an administrator';
$string['crm_email_preview'] = 'Email preview';
$string['recipient'] = 'Recipient';

$string['crm_timeline_title'] = 'Timeline';
$string['crm_suspend_moodle_profile'] = 'Suspend Moodle profile';
$string['crm_activate_moodle_profile'] = 'Activate Moodle profile';
$string['crm_moodle_profile_suspended'] = 'Moodle profile suspended.';
$string['crm_moodle_profile_activated'] = 'Moodle profile activated.';
$string['adminlog_user_suspended'] = 'Moodle profile suspended';
$string['adminlog_user_reactivated'] = 'Moodle profile reactivated';

$string['crm_stats_title'] = 'CRM summary';
$string['crm_accessible_courses'] = 'Accessible courses';
$string['crm_total_spent'] = 'Total spent';
$string['crm_last_activity'] = 'Last activity';

$string['crm_stats_subscriptions_hint'] = 'Subscriptions linked to this profile';
$string['crm_stats_digital_hint'] = 'Digital products purchased';
$string['crm_stats_courses_hint'] = 'Courses currently accessible';
$string['crm_stats_spent_hint'] = 'Total paid by currency';
$string['crm_stats_activity_hint'] = 'Last known event';

$string['crm_status'] = 'CRM status';
$string['crm_stats_status_hint'] = 'Current profile situation';
$string['crm_status_active_customer'] = 'Active customer';
$string['crm_status_trial'] = 'Trial';
$string['crm_status_former_customer'] = 'Former customer';
$string['crm_status_suspended'] = 'Suspended';
$string['crm_status_lead'] = 'Lead';
$string['crm_status_unknown'] = 'Unknown';

$string['command_center_type_user'] = 'User';
$string['command_center_user_suspended'] = 'Suspended account';
$string['command_center_open'] = 'Open Command Center';
$string['command_center_placeholder'] = 'Search for a user, purchase, product…';
$string['command_center_input_placeholder'] = 'Search… or type > for actions';
$string['command_center_hint'] = 'Enter to open · Esc to close · ↑ ↓ to navigate';
$string['command_center_close'] = 'Close Command Center';
$string['command_center_empty'] = 'No results';
$string['command_center_error'] = 'Search error';
$string['command_center_loading'] = 'Searching…';
$string['command_center_type_digital_product'] = 'Digital product';
$string['command_center_type_digital_purchase'] = 'Digital purchase';
$string['command_center_type_subscription'] = 'Subscription';
$string['command_center_disabled'] = 'Disabled';

$string['command_center_product_subtitle'] = '{$a->slug} · {$a->eur} EUR · {$a->rub} RUB';
$string['command_center_purchase_subtitle'] = '{$a->product} · {$a->status} · {$a->price} · {$a->date}';
$string['command_center_subscription_subtitle'] = '{$a->plan} · {$a->status} · {$a->period}';
$string['command_center_type_action'] = 'Action';

$string['command_action_dashboard_title'] = 'Open dashboard';
$string['command_action_dashboard_subtitle'] = 'Go back to the main CRM view';


$string['command_action_products_title'] = 'View digital products';
$string['command_action_products_subtitle'] = 'Manage CampusFR digital products';

$string['command_action_product_create_title'] = 'Create digital product';
$string['command_action_product_create_subtitle'] = 'Add a new digital product';

$string['command_action_purchases_title'] = 'View digital purchases';
$string['command_action_purchases_subtitle'] = 'Review digital purchases and payments';


$string['command_center_initial'] = 'Start typing to search for a user, product, purchase, subscription, or action.';

$string['command_center_group_actions'] = 'Actions';
$string['command_center_group_users'] = 'Users';
$string['command_center_group_products'] = 'Products';
$string['command_center_group_purchases'] = 'Purchases';
$string['command_center_group_subscriptions'] = 'Subscriptions';

$string['command_center_action_open'] = 'Open';
$string['command_center_action_view'] = 'View';
$string['command_center_action_edit'] = 'Edit';
$string['command_center_hint_navigate'] = 'navigate';
$string['command_center_hint_open'] = 'open';
$string['command_center_hint_close'] = 'close';
$string['command_center_best_match'] = 'Best';
$string['command_center_recent'] = 'Recent';
$string['command_center_key_enter'] = 'Enter';
$string['command_center_key_escape'] = 'Esc';
$string['command_center_favorites'] = 'Favorites';
$string['command_center_favorite_toggle'] = 'Add or remove from favorites';
$string['command_center_clear_recent'] = 'Clear';

$string['command_center_action_missing_url'] = 'URL manquante.';
$string['command_center_action_unknown'] = 'Commande inconnue.';
$string['command_center_action_error'] = 'La commande n’a pas pu être exécutée.';
$string['command_center_action_failed'] = 'L’action a échoué.';
$string['command_center_action_missing_user'] = 'Missing user.';
$string['command_center_action_missing_product'] = 'Missing product.';
$string['command_center_action_missing_purchase'] = 'Missing purchase.';
$string['command_center_action_missing_subscription'] = 'Missing subscription.';
$string['command_action_user_email_title'] = 'Send an email to a user';
$string['command_action_user_email_subtitle'] = 'Open the users list to choose a contact.';

$string['command_action_user_note_title'] = 'Add a user note';
$string['command_action_user_note_subtitle'] = 'Open the users list before adding a CRM note.';

$string['command_action_purchase_resend_email_title'] = 'Resend a purchase email';
$string['command_action_purchase_resend_email_subtitle'] = 'Open digital purchases to choose the relevant purchase.';
$string['command_menu_user_email'] = 'Send email';
$string['command_menu_user_note'] = 'Add note';
$string['command_menu_user_reset_password'] = 'Reset password';
$string['command_menu_purchase_resend_email'] = 'Resend email';
$string['command_menu_purchase_regenerate_token'] = 'Regenerate token';
$string['command_menu_purchase_extend_token'] = 'Extend token';

$string['command_menu_product_edit'] = 'Edit product';

$string['command_menu_subscription_open'] = 'Open subscription';
$string['command_center_purchase_email_resent'] = 'The access email has been resent.';
$string['command_confirm_purchase_resend_email'] = 'Resend the access email for this purchase?';
$string['command_menu_purchase_check_provider'] = 'Check payment';
$string['command_confirm_purchase_regenerate_token'] = 'Regenerate the access token for this purchase?';
$string['command_confirm_purchase_extend_token'] = 'Extend the access token for this purchase?';
$string['command_confirm_user_reset_password'] = 'Reset this user’s password?';
$string['command_action_users_title'] = 'Users';
$string['command_action_users_subtitle'] = 'Open CRM user management.';
$string['command_action_digital_purchases_title'] = 'Digital purchases';
$string['command_action_digital_purchases_subtitle'] = 'Open digital purchases and sales.';
$string['command_action_digital_products_title'] = 'Digital products';
$string['command_action_digital_products_subtitle'] = 'Open digital product management.';
$string['command_action_subscriptions_title'] = 'Subscriptions';
$string['command_action_subscriptions_subtitle'] = 'Open subscriptions and access management.';
$string['command_center_action_invalid_url'] = 'Invalid URL.';
$string['command_center_confirm'] = 'Confirm';
$string['command_center_cancel'] = 'Cancel';
$string['command_center_danger_confirm'] = 'Sensitive action';
$string['command_center_group_intents'] = 'Commands';
$string['command_center_action_execute'] = 'Execute';

$string['command_intent_open_user'] = 'Open user';
$string['command_intent_open_purchase'] = 'Open purchase';
$string['command_intent_open_product'] = 'Open product';
$string['command_intent_open_subscription'] = 'Open subscription';
$string['command_intent_direct_entity_subtitle'] = 'Direct command from the Command Center.';
$string['command_intent_email_user'] = 'Send email to user';
$string['command_intent_note_user'] = 'Add note to user';
$string['command_intent_reset_user'] = 'Reset user password';
$string['command_intent_user_quick_action_subtitle'] = 'Quick user action from the Command Center.';

$string['command_intent_resend_purchase_email'] = 'Resend purchase email';
$string['command_intent_check_purchase'] = 'Check payment';
$string['command_intent_purchase_quick_action_subtitle'] = 'Quick purchase action from the Command Center.';
$string['command_center_action_suggestion'] = 'Suggestion';

$string['command_suggestion_email_user_title'] = 'Send user email';
$string['command_suggestion_email_user_subtitle'] = 'Example: > email 12';

$string['command_suggestion_note_user_title'] = 'Add user note';
$string['command_suggestion_note_user_subtitle'] = 'Example: > note 12';

$string['command_suggestion_reset_user_title'] = 'Reset password';
$string['command_suggestion_reset_user_subtitle'] = 'Example: > reset 12';

$string['command_suggestion_resend_purchase_title'] = 'Resend purchase email';
$string['command_suggestion_resend_purchase_subtitle'] = 'Example: > resend 7';

$string['command_suggestion_check_purchase_title'] = 'Check payment';
$string['command_suggestion_check_purchase_subtitle'] = 'Example: > check 7';

$string['command_action_user_email'] = 'Send email';
$string['command_action_user_note'] = 'Add note';
$string['command_action_user_reset_password'] = 'Reset password';
$string['crm_section_overview'] = 'Overview';
$string['crm_section_quick_actions'] = 'Quick actions';
$string['crm_section_subscriptions'] = 'Active and past subscriptions';
$string['crm_section_digital_purchases'] = 'Digital purchases';
$string['crm_section_courses'] = 'Accessible courses';
$string['crm_section_notes'] = 'Internal notes';
$string['crm_note_empty'] = 'The note cannot be empty.';
$string['crm_note_too_long'] = 'The note is too long.';
$string['crm_note_type_general'] = 'General';
$string['crm_note_type_followup'] = 'Follow-up';
$string['crm_note_type_payment'] = 'Payment';
$string['crm_note_type_access'] = 'Access';
$string['crm_note_type_sensitive'] = 'Sensitive';
$string['crm_invalid_tag'] = 'Invalid CRM tag.';
$string['crm_tag_vip'] = 'VIP';
$string['crm_tag_followup'] = 'Follow-up';
$string['crm_tag_payment_issue'] = 'Payment issue';
$string['crm_tag_refund'] = 'Refund';
$string['crm_tag_manual_access'] = 'Manual access';
$string['crm_tag_sensitive'] = 'Sensitive case';
$string['crm_section_timeline'] = 'CRM timeline';
$string['command_action_purchase_resend_email'] = 'Resend purchase email';
$string['task_run_crm_automations'] = 'Run CRM automations';
$string['crm_timeline_automation_executed'] = 'Automatisation CRM exécutée';
$string['crm_automations'] = 'CRM automations';
$string['crm_automation_history'] = 'Automation history';
$string['crm_automation_trigger'] = 'Trigger';
$string['crm_automation_rule'] = 'Rule';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['priority'] = 'Priority';

$string['command_action_automations_title'] = 'CRM automations';
$string['command_action_automations_subtitle'] = 'Manage CRM rules and workflows';
$string['command_action_automation_history_title'] = 'Automation history';
$string['command_action_automation_history_subtitle'] = 'View recent CRM automation executions';

$string['crm_automation_no_rules'] = 'No automation rules found.';
$string['crm_automation_no_history'] = 'No automation history available.';
$string['crm_automation_recent_history'] = 'Recent executions';
$string['crm_automation_trial_expired'] = 'Expired trial detected';
$string['crm_automation_payment_failed'] = 'Failed payment detected';
$string['crm_automation_digital_purchase_paid'] = 'Digital purchase paid';
$string['crm_automation_subscription_expired'] = 'Expired subscription detected';
$string['crm_automation_note_added'] = 'CRM note added';
$string['crm_automation_tag_added'] = 'CRM tag added';
$string['crm_automation_tag_removed'] = 'CRM tag removed';
$string['crm_automation_rules_count'] = '{$a} automation rule(s) configured';
$string['crm_automation_status_success'] = 'Success';
$string['crm_automation_status_failed'] = 'Failed';
$string['crm_automation_status_skipped'] = 'Skipped';

$string['crm_section_intelligence'] = 'CRM Intelligence';

$string['crm_intelligence_commercial_score'] = 'Commercial score';
$string['crm_intelligence_engagement_score'] = 'Engagement';
$string['crm_intelligence_risk_score'] = 'Risk';
$string['crm_intelligence_global_score'] = 'Overall score';

$string['crm_intelligence_reason_active_customer'] = 'Active customer';
$string['crm_intelligence_reason_trial_user'] = 'Trial user';
$string['crm_intelligence_reason_paid_digital_purchase'] = 'Paid digital purchase';
$string['crm_intelligence_reason_high_value'] = 'High-value customer';
$string['crm_intelligence_reason_recent_activity'] = 'Recent activity';
$string['crm_intelligence_reason_inactive'] = 'Inactive user';
$string['crm_intelligence_reason_expired_subscription'] = 'Expired subscription';
$string['crm_intelligence_reason_suspended'] = 'Suspended account';
$string['crm_intelligence_level_very_low'] = 'Very low';
$string['crm_intelligence_level_low'] = 'Low';
$string['crm_intelligence_level_medium'] = 'Medium';
$string['crm_intelligence_level_high'] = 'High';
$string['crm_intelligence_level_excellent'] = 'Excellent';

$string['crm_intelligence_summary_very_low'] = 'Not enough useful signals yet.';
$string['crm_intelligence_summary_low'] = 'Low-priority profile for now, but worth monitoring.';
$string['crm_intelligence_summary_medium'] = 'Interesting profile with several useful signals.';
$string['crm_intelligence_summary_high'] = 'Priority profile with strong commercial potential.';
$string['crm_intelligence_summary_excellent'] = 'Very high-priority profile with strong CRM value.';
$string['crm_intelligence_segments'] = 'Segments';
$string['crm_intelligence_opportunities'] = 'Opportunities';
$string['crm_intelligence_recommendations'] = 'Recommendations';

$string['crm_intelligence_segment_customer'] = 'Customer';
$string['crm_intelligence_segment_trial'] = 'Trial';
$string['crm_intelligence_segment_hot_lead'] = 'Hot lead';
$string['crm_intelligence_segment_at_risk'] = 'At risk';
$string['crm_intelligence_segment_vip'] = 'VIP';
$string['crm_intelligence_segment_cold_user'] = 'Cold user';

$string['crm_intelligence_opportunity_trial_to_purchase'] = 'Trial → purchase conversion';
$string['crm_intelligence_opportunity_cross_sell_digital_product'] = 'Digital product cross-sell';
$string['crm_intelligence_opportunity_upgrade_subscription'] = 'Likely upgrade';
$string['crm_intelligence_opportunity_winback_expired_customer'] = 'Expired customer winback';

$string['crm_intelligence_recommendation_send_trial_conversion_email'] = 'Send conversion email';
$string['crm_intelligence_recommendation_propose_upgrade'] = 'Propose an upgrade';
$string['crm_intelligence_recommendation_send_winback_message'] = 'Send winback message';
$string['crm_intelligence_recommendation_suggest_digital_product'] = 'Suggest a digital product';
$string['crm_intelligence_recommendation_review_user_manually'] = 'Review this profile manually';
$string['crm_intelligence_recommendation_create_first_crm_note'] = 'Create a first CRM note';

$string['crm_intelligence_dashboard_title'] = 'CRM Intelligence';
$string['crm_intelligence_dashboard_analysed_users'] = 'Analysed users';
$string['crm_intelligence_dashboard_hot_leads'] = 'Hot leads';
$string['crm_intelligence_dashboard_at_risk'] = 'At-risk profiles';
$string['crm_intelligence_dashboard_vip'] = 'VIP';
$string['crm_intelligence_dashboard_trial_opportunities'] = 'Trial opportunities';
$string['crm_intelligence_dashboard_upgrade_opportunities'] = 'Upgrade opportunities';
$string['crm_intelligence_dashboard_priority_profiles'] = 'Priority profiles';
$string['crm_intelligence_dashboard_no_priority_profiles'] = 'No priority profile detected yet.';
$string['crm_intelligence_alerts_title'] = 'Smart CRM alerts';
$string['crm_intelligence_alerts_empty'] = 'No important CRM alert for now.';
$string['crm_intelligence_alert_open_profile'] = 'Open user profile';

$string['crm_intelligence_alert_high_risk_user'] = 'User with high CRM risk';
$string['crm_intelligence_alert_trial_without_purchase'] = 'Active trial without detected purchase';
$string['crm_intelligence_alert_expired_without_reactivation'] = 'Expired subscription without reactivation';
$string['crm_intelligence_alert_inactive_user'] = 'User inactive for a long time';
$string['crm_intelligence_alert_hot_opportunity'] = 'Hot commercial opportunity';
$string['command_crm_intelligence_dashboard'] = 'CRM Intelligence Dashboard';
$string['command_crm_intelligence_dashboard_desc'] = 'View CRM scores, alerts and recommendations.';
$string['command_crm_alert_desc'] = 'Smart CRM alert detected.';

$string['crm_funnel_title'] = 'CRM Funnel';
$string['crm_funnel_users'] = 'Users';
$string['crm_funnel_trials'] = 'Trials';
$string['crm_funnel_customers'] = 'Customers';
$string['crm_funnel_digital_customers'] = 'Digital customers';
$string['crm_funnel_expired_customers'] = 'Expired customers';
$string['crm_funnel_trial_conversion_rate'] = 'Trial → customer conversion';
$string['task_run_crm_intelligence_snapshot'] = 'Create CRM Intelligence snapshots';
$string['crm_trends_title'] = 'CRM trends';
$string['crm_trends_empty'] = 'Not enough CRM history yet.';
$string['crm_trend_label'] = 'Trend';
$string['crm_trend_direction_up'] = 'Increasing';
$string['crm_trend_direction_down'] = 'Decreasing';
$string['crm_trend_direction_stable'] = 'Stable';

$string['crm_explanation_active_customer'] = 'Active customer';
$string['crm_explanation_trial_user'] = 'Trial user';
$string['crm_explanation_paid_digital_purchase'] = 'Paid digital purchase';
$string['crm_explanation_high_value'] = 'High customer value';
$string['crm_explanation_recent_activity'] = 'Recent activity';
$string['crm_explanation_inactive'] = 'Inactivity detected';
$string['crm_explanation_expired_subscription'] = 'Expired subscription';
$string['crm_explanation_suspended'] = 'Suspended account';
$string['crm_explanation_no_crm_note'] = 'No CRM note';
$string['crm_explanations_title'] = 'Why this score?';

$string['crm_daily_priorities_title'] = 'Today’s CRM priorities';
$string['crm_daily_priorities_empty'] = 'No important CRM priority for now.';

$string['command_crm_priority_desc'] = 'Today’s CRM priority detected.';
$string['crm_recommendation_action_permission_denied'] = 'Insufficient permission to execute this action.';
$string['crm_recommendation_action_unsupported'] = 'This recommended action is not supported yet.';
$string['crm_recommendation_action_open_user_profile'] = 'Open user profile';

$string['dashboard_period_today'] = 'Today';
$string['dashboard_period_week'] = 'This week';
$string['dashboard_period_month'] = 'This month';
$string['dashboard_command_center_title'] = 'Command center';
$string['crm_user_filter_all'] = 'All users';
$string['crm_user_filter_hot_lead'] = 'Hot leads';
$string['crm_user_filter_at_risk'] = 'At-risk profiles';
$string['crm_user_filter_vip'] = 'VIP customers';
$string['crm_user_filter_cold_user'] = 'Inactive users';
$string['crm_user_filter_trial_to_purchase'] = 'Trials to convert';
$string['crm_user_filter_upgrade_subscription'] = 'Upgrade opportunities';
$string['crm_user_active_filter'] = 'Active filter: {$a}';

$string['dashboard_issues_title'] = 'To handle';
$string['dashboard_issues_subtitle'] = 'Items that require an admin check or action.';

$string['dashboard_issue_pending_digital_title'] = 'Pending digital payments';
$string['dashboard_issue_pending_digital_desc'] = 'Payment requests created but not confirmed yet.';
$string['dashboard_issue_failed_digital_title'] = 'Failed digital payments';
$string['dashboard_issue_failed_digital_desc'] = 'Rejected or interrupted payments to review.';


$string['dashboard_issue_open_queue'] = 'Open queue';
$string['dashboard_issue_review_failures'] = 'Review';
$string['dashboard_issue_resend_emails'] = 'Resend';
$string['dashboard_issue_regenerate_tokens'] = 'Regenerate';
$string['digital_purchase_filter_no_issue'] = 'No issue';
$string['digital_purchase_filter_issue_email_error'] = 'Email error';
$string['digital_purchase_filter_issue_expired_token'] = 'Expired link';
$string['digital_purchase_filter_clear_issue'] = 'Clear issue filter';
$string['digital_purchase_action_resend_email'] = 'Resend email';
$string['digital_purchase_action_regenerate_token'] = 'Regenerate link';
$string['digital_purchase_action_extend_token'] = 'Extend link';
$string['digital_purchase_action_email_resent'] = 'Email resent successfully.';
$string['digital_purchase_action_token_regenerated'] = 'Download link regenerated.';
$string['digital_purchase_action_failed'] = 'Action failed: {$a}';
$string['digital_purchases_actions'] = 'Actions';

$string['digital_purchase_action_resend_email_confirm'] = 'Resend the access email for this purchase?';
$string['digital_purchase_action_regenerate_token_confirm'] = 'Regenerate the download link? The previous link will no longer work.';
$string['digital_purchase_action_extend_token_confirm'] = 'Extend this download link by 30 days?';
$string['digital_purchase_access_action_requires_paid_status'] =
    'This action is only available for confirmed payments.';
$string['digital_payment_help_email_subject'] =
    'Did you experience a problem with your payment?';

$string['digital_payment_help_email_body'] =
    '<p>Hello {$a->firstname},</p>
    <p>We noticed that your payment attempt was not completed.</p>
    <p>Did you experience any difficulty, or would you like help completing your purchase?</p>
    <p>You can simply reply to this message. Our team will be happy to help.</p>
    <p>Kind regards,<br>The CampusFR team</p>';
$string['digital_purchase_action_contact_buyer'] = 'Contact buyer';
$string['digital_purchase_action_cancel'] = 'Cancel';
$string['digital_purchase_action_cancel_confirm'] =
    'Cancel this payment attempt? It will no longer appear in the items to handle.';
$string['digital_purchase_cancel_success'] =
    'The payment attempt has been cancelled.';

$string['digital_purchase_cancel_invalid_status'] =
    'This purchase cannot be cancelled because its current status is {$a}.';
$string['digital_payment_help_email_context_title'] =
    'Follow-up for an incomplete payment attempt';

$string['digital_payment_help_email_context_description'] =
    'The subject and message have been pre-filled. You can customise them before sending.';

$string['digital_payment_help_purchase_user_mismatch'] =
    'This digital purchase does not match the selected user.';

$string['dashboard_issue_open_purchases'] = 'View purchases';
$string['dashboard_issue_review_queue'] = 'Review queue';

$string['dashboard_issues_empty_title'] = 'Everything is under control';
$string['dashboard_issues_empty_description'] =
    'No payment or digital access currently requires your attention.';

$string['dashboard_issue_email_error_title'] =
    'Access emails not sent';

$string['dashboard_issue_email_error_desc'] =
    'Paid purchases for which the access email could not be sent.';

$string['dashboard_issue_expired_token_title'] =
    'Expired download links';

$string['dashboard_issue_expired_token_desc'] =
    'Paid purchases whose download link has expired.';

$string['admin_event_unknown'] = 'Administrative event';

$string['admin_event_email_custom_sent'] =
    'Custom email sent';

$string['admin_event_digital_purchase_created'] =
    'Digital payment attempt created';

$string['admin_event_digital_purchase_paid'] =
    'Digital payment confirmed';

$string['admin_event_digital_purchase_failed'] =
    'Digital payment failed';

$string['admin_event_digital_purchase_cancelled'] =
    'Payment attempt cancelled';

$string['admin_event_digital_link_resent'] =
    'Digital access email resent';

$string['admin_event_digital_token_regenerated'] =
    'Download link regenerated';

$string['admin_event_digital_token_extended'] =
    'Download link extended';

$string['admin_event_user_suspended'] =
    'Moodle profile suspended';

$string['admin_event_user_reactivated'] =
    'Moodle profile reactivated';

$string['crm_help_title'] = 'CRM Help Center';

$string['crm_help_subtitle'] =
    'Discover the CampusFR CRM tools and quickly find answers to your questions.';

$string['crm_help_search_placeholder'] =
    'Search the documentation…';

$string['crm_help_search_results'] =
    'Results for "{$a}"';

$string['crm_help_no_results'] =
    'No article matches your search.';

$string['crm_help_article_count'] =
    '{$a} article(s)';

$string['crm_help_category_getting_started'] =
    'Getting started';

$string['crm_help_category_getting_started_desc'] =
    'Understand the CRM and get productive quickly.';

$string['crm_help_category_daily_work'] =
    'Daily work';

$string['crm_help_category_daily_work_desc'] =
    'Essential tools for managing your daily CRM activity.';

$string['crm_help_category_users'] =
    'CRM users';

$string['crm_help_category_users_desc'] =
    'Search, profiles, filters, segments, tags and user actions.';

$string['crm_help_category_digital'] =
    'Digital purchases';

$string['crm_help_category_digital_desc'] =
    'Payments, access, emails, download links and issue resolution.';

$string['crm_help_category_automation'] =
    'Automation';

$string['crm_help_category_automation_desc'] =
    'Create and understand CRM automation rules.';

$string['crm_help_category_intelligence'] =
    'CRM Intelligence';

$string['crm_help_category_intelligence_desc'] =
    'Scores, risks, opportunities, recommendations and priorities.';

$string['crm_help_category_shortcuts'] =
    'Shortcuts';

$string['crm_help_category_shortcuts_desc'] =
    'Save time using the Command Center and keyboard shortcuts.';

$string['crm_help_category_developer'] =
    'Developer documentation';

$string['crm_help_category_developer_desc'] =
    'Internal architecture, conventions and CRM extensibility.';

$string['crm_help_article_overview_title'] =
    'Discover the CampusFR CRM';

$string['crm_help_article_overview_summary'] =
    'Overview of the Dashboard, Command Center and CRM modules.';

$string['crm_help_article_overview_content'] =
    '<p>The CampusFR CRM centralizes users, subscriptions, digital purchases, automation and intelligence tools.</p>';

$string['crm_help_article_dashboard_periods_title'] =
    'Using Dashboard periods';

$string['crm_help_article_dashboard_periods_summary'] =
    'Switch between Today, This week and This month.';

$string['crm_help_article_dashboard_periods_content'] =
    '<p>Dashboard periods recalculate all KPIs using the selected time range.</p>';

$string['crm_help_article_user_filters_title'] =
    'Filter users with CRM Intelligence';

$string['crm_help_article_user_filters_summary'] =
    'Display hot prospects, at-risk users, VIPs and opportunities.';

$string['crm_help_article_user_filters_content'] =
    '<p>Intelligence filters let you open CRM segments detected by the intelligence engine.</p>';

$string['crm_help_article_digital_issues_title'] =
    'Handle digital payment issues';

$string['crm_help_article_digital_issues_summary'] =
    'Understand pending, failed and cancelled payments, as well as access issues.';

$string['crm_help_article_digital_issues_content'] =
    '<p>An unconfirmed payment must never grant access. Administrators can contact the customer or cancel the payment attempt.</p>';

$string['crm_help_article_shortcuts_title'] =
    'Using the Command Center';

$string['crm_help_article_shortcuts_summary'] =
    'Quickly search and launch CRM actions.';

$string['crm_help_article_shortcuts_content'] =
    '<p>Press Ctrl or Cmd + K to instantly open the Command Center.</p>';

$string['crm_help_article_developer_architecture_title'] =
    'CRM architecture';

$string['crm_help_article_developer_architecture_summary'] =
    'Understand repositories, services, renderers and security rules.';

$string['crm_help_article_developer_architecture_content'] =
    '<p>SQL belongs in repositories, business logic in services and rendering in renderers.</p>';

$string['crm_help_all_categories'] = 'All categories';
$string['crm_help_category_empty'] =
    'No article is currently available in this category.';
$string['crm_help_read_article'] = 'Read article';
$string['crm_help_home'] = 'Help Center';
$string['crm_help_article_navigation'] =
    'Documentation navigation';

$string['crm_help_article_not_found'] =
    'The requested article could not be found.';
$string['crm_help_article_read_error'] =
    'The article content could not be read.';
$string['crm_help_article_content_missing'] =
    'The content for article "{$a}" could not be found.';
$string['crm_help_content_directory_missing'] =
    'The CRM documentation directory could not be found.';

$string['crm_context_help_trigger'] =
    'Help for this page';

$string['crm_context_help_title'] =
    'Need help?';

$string['crm_context_help_description'] =
    'These articles are relevant to the page you are currently viewing.';

$string['crm_context_help_empty'] =
    'No contextual article is currently available.';

$string['crm_context_help_open_center'] =
    'Open the Help Center';

$string['admin_dashboard_description'] =
    'Manage CRM activity, monitor indicators and handle priorities.';

$string['crm_users_explorer_description'] =
    'Search, filter and analyse CRM users.';

$string['digital_purchases_help_description'] =
    'Review digital payments, handle issues and manage access.';

$string['crm_user_profile_help_description'] =
    'Review this user’s history, subscriptions, purchases and recommendations.';

$string['crm_onboarding_title'] =
    'Get started with the CRM';

$string['crm_onboarding_description'] =
    'Follow these steps to discover the main tools and quickly become confident using the CRM.';

$string['crm_onboarding_progress_label'] =
    '{$a->completed} of {$a->total} step(s)';

$string['crm_onboarding_mark_complete'] =
    'Mark as complete';

$string['crm_onboarding_mark_incomplete'] =
    'Reopen';

$string['crm_onboarding_complete_title'] =
    'Onboarding complete';

$string['crm_onboarding_complete_desc'] =
    'You have discovered the main features of the CampusFR CRM.';

$string['crm_onboarding_restart'] =
    'Restart checklist';

$string['crm_onboarding_restart_confirm'] =
    'Reset all your onboarding progress?';

$string['crm_onboarding_reset_success'] =
    'Your onboarding progress has been reset.';

$string['crm_onboarding_invalid_step'] =
    'This onboarding step is unknown.';

$string['crm_onboarding_invalid_action'] =
    'This onboarding action is invalid.';

$string['crm_onboarding_step_dashboard_title'] =
    'Discover the Dashboard';

$string['crm_onboarding_step_dashboard_desc'] =
    'Review KPIs, priorities and items requiring attention.';

$string['crm_onboarding_step_command_center_title'] =
    'Try the Command Center';

$string['crm_onboarding_step_command_center_desc'] =
    'Quickly search for a user or administrative action.';

$string['crm_onboarding_step_users_title'] =
    'Explore CRM users';

$string['crm_onboarding_step_users_desc'] =
    'Use search and open a complete CRM profile.';

$string['crm_onboarding_step_intelligence_title'] =
    'Discover Intelligence filters';

$string['crm_onboarding_step_intelligence_desc'] =
    'Display hot leads and at-risk profiles.';

$string['crm_onboarding_step_digital_title'] =
    'Review digital purchases';

$string['crm_onboarding_step_digital_desc'] =
    'Understand payment statuses and available actions.';

$string['crm_onboarding_step_automations_title'] =
    'Discover automation';

$string['crm_onboarding_step_automations_desc'] =
    'Review automation rules and their history.';

$string['crm_onboarding_step_help_title'] =
    'Browse the Help Center';

$string['crm_onboarding_step_help_desc'] =
    'Find the CRM functional documentation.';

$string['crm_onboarding_step_architecture_title'] =
    'Read the architecture rules';

$string['crm_onboarding_step_architecture_desc'] =
    'Understand the plugin technical conventions.';

$string['crm_help_guides_title'] = 'Practical guides';

$string['crm_help_guides_description'] =
    'Follow simple workflows to complete the main CRM tasks.';

$string['crm_help_guide_step_count'] =
    '{$a} steps';

$string['crm_help_guide_progress'] =
    '{$a->completed} of {$a->total} step(s)';

$string['crm_help_guide_complete_step'] =
    'Mark step as complete';

$string['crm_help_guide_reopen_step'] =
    'Reopen';

$string['crm_help_guide_complete'] =
    'You have completed this guide.';

$string['crm_help_guide_reset'] =
    'Reset guide';

$string['crm_help_guide_reset_confirm'] =
    'Reset the progress for this guide?';

$string['crm_help_guide_reset_success'] =
    'The guide progress has been reset.';

$string['crm_help_guide_not_found'] =
    'The requested guide could not be found.';

$string['crm_help_guide_step_not_found'] =
    'This guide step could not be found.';

$string['crm_help_guide_invalid_action'] =
    'This guide action is invalid.';

$string['crm_help_guide_dashboard_title'] =
    'Get started with the Dashboard';

$string['crm_help_guide_dashboard_desc'] =
    'Understand indicators, priorities and items requiring attention.';

$string['crm_help_guide_dashboard_period_title'] =
    'Choose a period';

$string['crm_help_guide_dashboard_period_desc'] =
    'Switch between Today, This week and This month.';

$string['crm_help_guide_dashboard_kpis_title'] =
    'Review the indicators';

$string['crm_help_guide_dashboard_kpis_desc'] =
    'Analyse new users, subscriptions, purchases and revenue.';

$string['crm_help_guide_dashboard_issues_title'] =
    'Review items requiring attention';

$string['crm_help_guide_dashboard_issues_desc'] =
    'Open payment or access queues that require administrative action.';

$string['crm_help_guide_dashboard_priority_title'] =
    'Open a priority profile';

$string['crm_help_guide_dashboard_priority_desc'] =
    'Review the score explanation and choose an appropriate action.';

$string['crm_help_guide_open_dashboard'] =
    'Open Dashboard';

$string['crm_help_guide_digital_title'] =
    'Handle a digital payment';

$string['crm_help_guide_digital_desc'] =
    'Review, contact or cancel a payment attempt.';

$string['crm_help_guide_digital_open_title'] =
    'Open the payment queue';

$string['crm_help_guide_digital_open_desc'] =
    'Start with pending or failed payments.';

$string['crm_help_guide_digital_verify_title'] =
    'Verify the actual status';

$string['crm_help_guide_digital_verify_desc'] =
    'Confirm that the payment was not completed before taking action.';

$string['crm_help_guide_digital_contact_title'] =
    'Contact the buyer';

$string['crm_help_guide_digital_contact_desc'] =
    'Use the pre-filled message to offer assistance.';

$string['crm_help_guide_digital_cancel_title'] =
    'Cancel the payment attempt';

$string['crm_help_guide_digital_cancel_desc'] =
    'Only cancel pending or failed attempts that are no longer useful.';

$string['crm_help_guide_open_pending'] =
    'View pending payments';

$string['crm_help_guide_hot_lead_title'] =
    'Analyse a hot lead';

$string['crm_help_guide_hot_lead_desc'] =
    'Understand the score and choose the best next action.';

$string['crm_help_guide_hot_lead_open_title'] =
    'Open the segment';

$string['crm_help_guide_hot_lead_open_desc'] =
    'Display the list of hot leads.';

$string['crm_help_guide_hot_lead_score_title'] =
    'Review the score';

$string['crm_help_guide_hot_lead_score_desc'] =
    'Review the factors that increased the lead potential.';

$string['crm_help_guide_hot_lead_history_title'] =
    'Review the timeline';

$string['crm_help_guide_hot_lead_history_desc'] =
    'Check recent purchases, subscriptions and interactions.';

$string['crm_help_guide_hot_lead_action_title'] =
    'Choose an action';

$string['crm_help_guide_hot_lead_action_desc'] =
    'Contact the prospect only when the context justifies it.';

$string['crm_help_guide_open_hot_leads'] =
    'View hot leads';

$string['crm_help_guide_command_title'] =
    'Master the Command Center';

$string['crm_help_guide_command_desc'] =
    'Search and navigate quickly through the CRM.';

$string['crm_help_guide_command_open_title'] =
    'Open the Command Center';

$string['crm_help_guide_command_open_desc'] =
    'Use Ctrl or Cmd + K.';

$string['crm_help_guide_command_search_title'] =
    'Search for an entity';

$string['crm_help_guide_command_search_desc'] =
    'Search for a user, purchase, subscription or product.';

$string['crm_help_guide_command_keyboard_title'] =
    'Navigate with the keyboard';

$string['crm_help_guide_command_keyboard_desc'] =
    'Use the arrow keys, Enter and Escape.';

$string['crm_help_guide_command_favorites_title'] =
    'Use favourites and recent commands';

$string['crm_help_guide_command_favorites_desc'] =
    'Quickly find commands you use frequently.';

$string['crm_help_guide_profile_title'] =
    'Understand a user profile';

$string['crm_help_guide_profile_desc'] =
    'Review all available information before making a decision.';

$string['crm_help_guide_profile_identity_title'] =
    'Verify the user identity';

$string['crm_help_guide_profile_identity_desc'] =
    'Check contact details and account status.';

$string['crm_help_guide_profile_timeline_title'] =
    'Review the timeline';

$string['crm_help_guide_profile_timeline_desc'] =
    'Reconstruct important events in chronological order.';

$string['crm_help_guide_profile_intelligence_title'] =
    'Understand CRM Intelligence';

$string['crm_help_guide_profile_intelligence_desc'] =
    'Review the score, segment and recommendations.';

$string['crm_help_guide_profile_action_title'] =
    'Take action';

$string['crm_help_guide_profile_action_desc'] =
    'Choose a quick action appropriate to the current context.';
$string['crm_context_help_articles_title'] =
    'Recommended articles';

$string['crm_context_help_guides_title'] =
    'Practical guides';
$string['command_help_center_title'] =
    'Open the CRM Help Center';

$string['command_help_center_subtitle'] =
    'Documentation, guides and contextual help';
$string['crm_help_diagnostics_title'] =
    'Help Center diagnostics';

$string['crm_help_diagnostics_description'] =
    'Check the consistency of CRM articles, translations, Markdown files, categories and guides.';

$string['crm_help_diagnostics_successes'] =
    'Successful validations';

$string['crm_help_diagnostics_warnings'] =
    'Warnings';

$string['crm_help_diagnostics_errors'] =
    'Errors';

$string['crm_help_diagnostics_valid'] =
    'The Help Center is valid and ready to use.';

$string['crm_help_diagnostics_invalid'] =
    'The Help Center contains errors that must be corrected.';

$string['crm_help_open_diagnostics'] =
    'Validate documentation';

$string['crm_user_sort_name_asc'] = 'Name: A to Z';
$string['crm_user_sort_name_desc'] = 'Name: Z to A';
$string['crm_user_sort_score_desc'] = 'Highest CRM score';
$string['crm_user_sort_risk_desc'] = 'Highest risk';
$string['crm_user_sort_last_access_desc'] = 'Most recent activity';
$string['crm_user_sort_created_desc'] = 'Most recent registration';

$string['crm_user_account_status_all'] = 'All accounts';
$string['crm_user_account_status_active'] = 'Active accounts';
$string['crm_user_account_status_suspended'] = 'Suspended accounts';

$string['crm_user_account_active'] = 'Active';
$string['crm_user_account_suspended'] = 'Suspended';
$string['crm_user_account_status'] = 'Account status';

$string['crm_user_explorer_result_count'] = 'user(s)';
$string['crm_user_explorer_active_filters'] = '{$a} active filter(s)';
$string['crm_user_explorer_clear_filters'] = 'Reset filters';
$string['crm_user_explorer_search_label'] = 'Search';
$string['crm_user_country_all'] = 'All countries';
$string['crm_user_tag_all'] = 'All tags';
$string['crm_user_sort_label'] = 'Sort by';
$string['crm_user_per_page'] = 'Per page';
$string['crm_user_apply_filters'] = 'Apply filters';


$string['crm_user_explorer_empty_title'] = 'No user found';
$string['crm_user_explorer_empty_description'] =
    'Change the filters or search query to display other profiles.';

$string['crm_user_score_level_unknown'] = 'Not analysed';
$string['crm_user_score_level_very_low'] = 'Very low';
$string['crm_user_score_level_low'] = 'Low';
$string['crm_user_score_level_medium'] = 'Medium';
$string['crm_user_score_level_high'] = 'High';
$string['crm_user_score_level_excellent'] = 'Excellent';
$string['country'] = 'Country';
$string['crm_user_tags'] = 'Tags';

$string['crm_user_column_user'] = 'User';
$string['crm_user_column_tags'] = 'Tags';
$string['crm_user_column_score'] = 'CRM score';
$string['crm_user_column_risk'] = 'Risk';
$string['crm_user_column_intelligence'] = 'Intelligence';
$string['crm_user_column_subscriptions'] = 'Subscriptions';
$string['crm_user_column_purchases'] = 'Digital purchases';
$string['crm_user_column_country'] = 'Country';
$string['crm_user_column_registered'] = 'Registration';
$string['crm_user_column_last_access'] = 'Last activity';

$string['crm_user_configure_columns'] = 'Configure columns';
$string['crm_user_columns_saved'] = 'The columns have been saved.';
$string['crm_user_columns_reset'] = 'The default columns have been restored.';

$string['crm_user_save_view'] = 'Save view';
$string['crm_user_view_name_placeholder'] = 'View name';
$string['crm_user_view_name_required'] = 'The view name is required.';
$string['crm_user_view_limit_reached'] =
    'You cannot save more than {$a} views.';
$string['crm_user_view_saved'] = 'The view has been saved.';
$string['crm_user_view_deleted'] = 'The view has been deleted.';
$string['crm_user_view_delete'] = 'Delete this view';
$string['crm_user_view_delete_confirm'] =
    'Permanently delete this saved view?';

$string['crm_user_explorer_invalid_action'] =
    'This User Explorer action is invalid.';

$string['crm_user_advanced_filters'] = 'Advanced filters';
$string['crm_user_score_min'] = 'Minimum score';
$string['crm_user_score_max'] = 'Maximum score';
$string['crm_user_risk_min'] = 'Minimum risk';
$string['crm_user_risk_max'] = 'Maximum risk';

$string['crm_user_presence_all'] = 'All';
$string['crm_user_presence_yes'] = 'Yes';
$string['crm_user_presence_no'] = 'No';

$string['crm_user_has_subscription'] = 'Has a subscription';
$string['crm_user_has_purchase'] = 'Has a digital purchase';

$string['crm_user_activity_filter'] = 'Last activity';
$string['crm_user_activity_all'] = 'Any activity';
$string['crm_user_activity_7days'] = 'Within the last 7 days';
$string['crm_user_activity_30days'] = 'Within the last 30 days';
$string['crm_user_activity_90days'] = 'Within the last 90 days';
$string['crm_user_activity_never'] = 'Never logged in';

$string['crm_user_export_csv'] = 'Export CSV';

$string['crm_inbox_navigation'] = 'CRM Inbox';
$string['crm_inbox_title'] = 'CRM Inbox';
$string['crm_inbox_foundation_ready'] =
    'The CRM Inbox foundation is installed. No email account is connected yet.';
$string['crm_inbox_no_account_configured'] =
    'OVH configuration and IMAP synchronisation will be added during the next steps.';

$string['privacy:metadata:inbox'] =
    'The CRM Inbox stores support messages and their optional links to CampusFR users.';
$string['privacy:metadata:inbox:email'] =
    'Email address of the message participant.';
$string['privacy:metadata:inbox:name'] =
    'Displayed name of the message participant.';
$string['privacy:metadata:inbox:message'] =
    'Content of the received or sent message.';
$string['privacy:metadata:inbox:userid'] =
    'Moodle user optionally linked to the Inbox contact.';

$string['crm_inbox_credential_missing'] =
    'Inbox credentials “{$a}” are missing from the Moodle configuration.';
$string['crm_inbox_credential_invalid'] =
    'Inbox credentials “{$a}” have an invalid configuration.';
$string['crm_inbox_credential_field_missing'] =
    'Field “{$a->field}” is missing from Inbox credentials “{$a->key}”.';

$string['crm_inbox_account_disabled'] =
    'The CRM Inbox account is disabled.';
$string['crm_inbox_account_no_credential'] =
    'No credential reference is configured for this Inbox account.';
$string['crm_inbox_account_not_found'] =
    'The requested CRM Inbox account could not be found.';

$string['crm_inbox_imap_configuration_missing'] =
    'The Inbox account IMAP configuration is missing.';
$string['crm_inbox_imap_field_missing'] =
    'Required IMAP field “{$a}” is missing.';
$string['crm_inbox_imap_extension_missing'] =
    'The PHP IMAP extension is not installed or enabled on the server.';

$string['task_sync_crm_inbox'] =
    'Synchronise the CRM Inbox';
$string['task_reconcile_crm_inbox_contacts'] =
    'Match CRM Inbox contacts with users';

$string['crm_inbox_empty'] =
    'No conversation matches these criteria.';
$string['crm_inbox_search'] = 'Search';
$string['crm_inbox_status'] = 'Status';
$string['crm_inbox_priority'] = 'Priority';
$string['crm_inbox_assignment'] = 'Assignment';
$string['crm_inbox_assignment_mine'] = 'My conversations';
$string['crm_inbox_assignment_unassigned'] = 'Unassigned';
$string['crm_inbox_assignment_team'] = 'Assigned to a team';

$string['crm_inbox_status_open'] = 'Open';
$string['crm_inbox_status_pending'] = 'Pending';
$string['crm_inbox_status_resolved'] = 'Resolved';
$string['crm_inbox_status_closed'] = 'Closed';
$string['crm_inbox_status_spam'] = 'Spam';

$string['crm_inbox_priority_low'] = 'Low';
$string['crm_inbox_priority_normal'] = 'Normal';
$string['crm_inbox_priority_high'] = 'High';
$string['crm_inbox_priority_urgent'] = 'Urgent';

$string['crm_inbox_unknown_contact'] = 'Unknown contact';
$string['crm_inbox_no_subject'] = 'No subject';
$string['crm_inbox_unread_count'] =
    '{$a} unread message(s)';
$string['crm_inbox_back'] = 'Back to Inbox';
$string['crm_inbox_matched_user'] =
    'CampusFR user: {$a}';
$string['crm_inbox_external_contact'] =
    'Unregistered external contact';

$string['crm_inbox_reply'] = 'Reply';
$string['crm_inbox_save_draft'] = 'Save draft';
$string['crm_inbox_send'] = 'Send';
$string['crm_inbox_draft_saved'] =
    'The draft has been saved.';
$string['crm_inbox_reply_sent'] =
    'The reply has been sent.';
$string['crm_inbox_send_failed'] =
    'The reply could not be sent: {$a}';
$string['crm_inbox_invalid_recipient'] =
    'The recipient of this conversation is invalid.';

$string['crm_inbox_direction_inbound'] = 'Received';
$string['crm_inbox_direction_outbound'] = 'Sent';
$string['crm_inbox_message_status_draft'] = 'Draft';

$string['crm_inbox_thread_not_found'] =
    'This Inbox conversation could not be found.';
$string['crm_inbox_archive'] = 'Archive';
$string['crm_inbox_move_to_trash'] = 'Move to trash';
$string['crm_inbox_trash_confirm'] =
    'Move this conversation to the provider trash folder?';
$string['crm_inbox_moved_to_trash'] =
    'The conversation was moved to trash.';
$string['crm_inbox_deleted_locally'] =
    'The conversation was deleted from the CRM.';
$string['crm_inbox_folder_not_configured'] =
    'Provider folder “{$a}” is not configured.';

$string['crm_timeline_inbox_received'] =
    'Email received in the CRM Inbox';
$string['crm_timeline_inbox_sent'] =
    'Reply sent from the CRM Inbox';

$string['command_action_inbox_title'] =
    'Open the CRM Inbox';
$string['command_action_inbox_subtitle'] =
    'Review and process CampusFR support conversations.';

$string['task_download_crm_inbox_attachments'] =
    'Download CRM Inbox attachments';

$string['crm_inbox_diagnostics'] =
    'CRM Inbox diagnostics';
$string['crm_inbox_diagnostics_metrics'] =
    'Inbox metrics';

$string['crm_help_category_inbox'] =
    'CRM Inbox';
$string['crm_help_category_inbox_desc'] =
    'Support emails, conversations, contacts and assignments.';

$string['crm_help_article_inbox_title'] =
    'Using the CRM Inbox';
$string['crm_help_article_inbox_summary'] =
    'Receive, match, assign and process CampusFR support emails.';

$string['crm_help_guide_inbox_title'] =
    'Process an Inbox conversation';
$string['crm_help_guide_inbox_desc'] =
    'Complete workflow for handling a support email.';
$string['crm_help_guide_inbox_open_title'] =
    'Open the Inbox';
$string['crm_help_guide_inbox_open_desc'] =
    'Review new and unassigned conversations.';
$string['crm_help_guide_open_inbox'] =
    'Open the CRM Inbox';
$string['crm_help_guide_inbox_contact_title'] =
    'Identify the contact';
$string['crm_help_guide_inbox_contact_desc'] =
    'Check whether the contact is external or matched to a CampusFR user.';
$string['crm_help_guide_inbox_assign_title'] =
    'Assign the conversation';
$string['crm_help_guide_inbox_assign_desc'] =
    'Assign the request to an administrator or a team.';
$string['crm_help_guide_inbox_reply_title'] =
    'Prepare and send the reply';
$string['crm_help_guide_inbox_reply_desc'] =
    'Save a draft or send directly from the CRM.';
$string['crm_help_guide_inbox_close_title'] =
    'Resolve and archive';
$string['crm_help_guide_inbox_close_desc'] =
    'Mark the conversation as resolved or archive it.';

$string['crm_inbox_account_validation_failed'] =
    'The Inbox account configuration is invalid: {$a}';

$string['crm_inbox_validation_invalid_email'] =
    'The Inbox account email address is missing or invalid.';
$string['crm_inbox_validation_provider_missing'] =
    'The Inbox account provider is missing.';
$string['crm_inbox_validation_smtp_missing'] =
    'The Inbox account SMTP configuration is missing.';
$string['crm_inbox_validation_sync_missing'] =
    'The Inbox synchronisation configuration is missing.';
$string['crm_inbox_validation_host_missing'] =
    'The {$a} host is missing.';
$string['crm_inbox_validation_port_invalid'] =
    'The {$a} port is invalid.';
$string['crm_inbox_validation_encryption_invalid'] =
    'The {$a} encryption setting is invalid.';
$string['crm_inbox_validation_unencrypted'] =
    'The {$a} connection is not encrypted.';
$string['crm_inbox_validation_batchsize'] =
    'The Inbox batch size must be between 1 and 200.';
$string['crm_inbox_validation_interval'] =
    'The synchronisation interval must be between 5 and 1440 minutes.';
$string['crm_inbox_validation_inbox_folder_missing'] =
    'The main IMAP folder is not configured; INBOX will be used by default.';
$string['crm_inbox_validation_folders_missing'] =
    'The Inbox folder configuration is missing.';
$string['crm_inbox_validation_folder_missing'] =
    'Inbox folder “{$a}” has not been resolved yet.';

$string['crm_inbox_folder_discovery_success'] =
    '{$a->count} folders detected. Inbox: {$a->inbox}; sent: {$a->sent}; trash: {$a->trash}; archive: {$a->archive}; drafts: {$a->drafts}.';
$string['crm_inbox_folder_discovery_missing'] =
    'Some required folders could not be found: {$a}.';

$string['crm_inbox_remote_image_blocked'] =
    'Remote image blocked to protect your privacy.';
$string['privacy:metadata:inbox_contact'] =
    'External or matched contacts used by the CRM Inbox.';
$string['privacy:metadata:inbox_contact:displayname'] =
    'The contact display name.';
$string['privacy:metadata:inbox_contact:primaryemail'] =
    'The contact primary email address.';
$string['privacy:metadata:inbox_contact:normalizedemail'] =
    'The normalised email address used for contact matching.';
$string['privacy:metadata:inbox_contact:matcheduserid'] =
    'The identifier of the matched Moodle user.';
$string['privacy:metadata:inbox_contact:matchstatus'] =
    'The current user matching status.';
$string['privacy:metadata:inbox_contact:matchsource'] =
    'The source used to match the contact.';
$string['privacy:metadata:inbox_contact:matchconfidence'] =
    'The confidence level of the contact match.';
$string['privacy:metadata:inbox_contact:lastmatchedat'] =
    'The date of the most recent matching operation.';

$string['privacy:metadata:inbox_thread'] =
    'Conversations managed in the CRM Inbox.';
$string['privacy:metadata:inbox_thread:contactid'] =
    'The primary contact of the conversation.';
$string['privacy:metadata:inbox_thread:subject'] =
    'The conversation subject.';
$string['privacy:metadata:inbox_thread:assigneduserid'] =
    'The administrator assigned to the conversation.';
$string['privacy:metadata:inbox_thread:status'] =
    'The conversation status.';
$string['privacy:metadata:inbox_thread:priority'] =
    'The conversation priority.';
$string['privacy:metadata:inbox_thread:lastmessageat'] =
    'The date of the most recent message.';

$string['privacy:metadata:inbox_message'] =
    'Messages received or sent through the CRM Inbox.';
$string['privacy:metadata:inbox_message:threadid'] =
    'The conversation containing the message.';
$string['privacy:metadata:inbox_message:direction'] =
    'The message direction.';
$string['privacy:metadata:inbox_message:subject'] =
    'The message subject.';
$string['privacy:metadata:inbox_message:bodytext'] =
    'The plain-text message body.';
$string['privacy:metadata:inbox_message:bodyhtml'] =
    'The HTML message body.';
$string['privacy:metadata:inbox_message:receivedat'] =
    'The date when the message was received.';
$string['privacy:metadata:inbox_message:sentat'] =
    'The date when the message was sent.';
$string['privacy:metadata:inbox_message:createdby'] =
    'The administrator who created the outgoing message.';

$string['privacy:metadata:inbox_participant'] =
    'Participants associated with CRM Inbox messages.';
$string['privacy:metadata:inbox_participant:messageid'] =
    'The related message.';
$string['privacy:metadata:inbox_participant:contactid'] =
    'The contact associated with the participant.';
$string['privacy:metadata:inbox_participant:participanttype'] =
    'The participant type, such as sender, recipient, carbon copy or reply-to.';
$string['privacy:metadata:inbox_participant:email'] =
    'The participant email address.';
$string['privacy:metadata:inbox_participant:displayname'] =
    'The participant display name.';

$string['privacy:metadata:inbox_attachment'] =
    'Attachments associated with CRM Inbox messages.';
$string['privacy:metadata:inbox_attachment:messageid'] =
    'The message containing the attachment.';
$string['privacy:metadata:inbox_attachment:filename'] =
    'The attachment filename.';
$string['privacy:metadata:inbox_attachment:mimetype'] =
    'The attachment MIME type.';
$string['privacy:metadata:inbox_attachment:filesize'] =
    'The attachment file size.';

$string['privacy:path:inbox'] =
    'CRM Inbox';
$string['crm_inbox_match_status'] =
    'User match';
$string['crm_inbox_match_matched'] =
    'Matched user';
$string['crm_inbox_match_unmatched'] =
    'External contact';
$string['crm_inbox_match_ambiguous'] =
    'Ambiguous match';
$string['crm_inbox_team'] =
    'Team';
$string['crm_inbox_unread_only'] =
    'Unread only';
$string['crm_inbox_per_page'] =
    'Per page';

$string['task_cleanup_crm_inbox'] =
    'Clean up old CRM Inbox data';

$string['task_cleanup_crm_inbox_ai_results'] =
    'Clean up expired CRM Inbox AI results';

$string['crm_inbox_ai_empty_content'] =
    'The content to analyse is empty.';

$string['crm_inbox_ai_empty_conversation'] =
    'This conversation contains no analysable messages.';

$string['crm_inbox_ai_language_unknown'] =
    'Unknown language';

$string['crm_inbox_ai_urgency_low'] =
    'Low urgency';
$string['crm_inbox_ai_urgency_normal'] =
    'Normal urgency';
$string['crm_inbox_ai_urgency_high'] =
    'High urgency';
$string['crm_inbox_ai_urgency_critical'] =
    'Critical urgency';

$string['crm_inbox_ai_category_payment'] =
    'Payment';
$string['crm_inbox_ai_category_access'] =
    'Access';
$string['crm_inbox_ai_category_subscription'] =
    'Subscription';
$string['crm_inbox_ai_category_technical'] =
    'Technical issue';
$string['crm_inbox_ai_category_course_content'] =
    'Course content';
$string['crm_inbox_ai_category_account'] =
    'User account';
$string['crm_inbox_ai_category_refund'] =
    'Refund';
$string['crm_inbox_ai_category_billing'] =
    'Billing';
$string['crm_inbox_ai_category_commercial'] =
    'Commercial request';
$string['crm_inbox_ai_category_feedback'] =
    'User feedback';
$string['crm_inbox_ai_category_spam'] =
    'Spam';
$string['crm_inbox_ai_category_other'] =
    'Other';

$string['crm_inbox_ai_reply_requires_review'] =
    'This suggestion must be reviewed and approved before sending.';

$string['crm_inbox_ai_tone_professional'] =
    'Professional';
$string['crm_inbox_ai_tone_friendly'] =
    'Friendly';
$string['crm_inbox_ai_tone_empathetic'] =
    'Empathetic';
$string['crm_inbox_ai_tone_concise'] =
    'Concise';

$string['crm_inbox_ai_translation_failed'] =
    'The translation could not be generated.';

$string['crm_inbox_ai_reply_unavailable'] =
    'Reply suggestions are not available with the current provider.';

$string['crm_inbox_ai_context_partial'] =
    'Some CRM data could not be added to the context.';

$string['crm_inbox_ai_panel_title'] =
    'AI assistance';
$string['crm_inbox_ai_panel_description'] =
    'Analysis and suggestions designed to assist administrators.';
$string['crm_inbox_ai_human_review_badge'] =
    'Human review required';
$string['crm_inbox_ai_permission_required'] =
    'You do not have permission to use AI assistance.';
$string['crm_inbox_ai_no_analysis'] =
    'No AI analysis has been requested for this conversation yet.';
$string['crm_inbox_ai_analyse'] =
    'Analyse conversation';
$string['crm_inbox_ai_suggest_reply'] =
    'Suggest a reply';
$string['crm_inbox_ai_reply_language'] =
    'Reply language';
$string['crm_inbox_ai_reply_tone'] =
    'Reply tone';
$string['crm_inbox_ai_analysis_completed'] =
    'The AI analysis is complete.';
$string['crm_inbox_ai_detected_language'] =
    'Detected language';
$string['crm_inbox_ai_urgency'] =
    'Urgency';
$string['crm_inbox_ai_category'] =
    'Category';
$string['crm_inbox_ai_summary'] =
    'Summary';
$string['crm_inbox_ai_key_points'] =
    'Key points';
$string['crm_inbox_ai_pending_questions'] =
    'Pending questions';
$string['crm_inbox_ai_customer_requests'] =
    'Customer requests';
$string['crm_inbox_ai_suggested_reply'] =
    'Suggested reply';
$string['crm_inbox_ai_confidence'] =
    'Confidence: {$a}%';

$string['crm_inbox_ai_quota_exceeded'] =
    'The daily AI assistance quota has been reached.';
$string['task_analyse_crm_inbox'] =
    'Analyse CRM Inbox conversations';
$string['crm_inbox_ai_diagnostics'] =
    'CRM Inbox AI diagnostics';
$string['crm_inbox_ai_diagnostic_table_ok'] =
    'The AI results table is available.';
$string['crm_inbox_ai_diagnostic_table_missing'] =
    'The AI results table is missing.';
$string['crm_inbox_ai_diagnostic_fallback'] =
    'The local fallback provider is available.';
$string['crm_inbox_ai_diagnostic_orchestrator_ok'] =
    'The AI orchestrator can be constructed.';
$string['crm_inbox_ai_usage_today'] =
    'Usage today';
$string['crm_inbox_ai_usage_global'] =
    'Global usage: {$a->used} / {$a->limit}';
$string['crm_inbox_ai_usage_user'] =
    'Your usage: {$a->used} / {$a->limit}';
$string['crm_inbox_ai_failures_today'] =
    'Failed analyses today: {$a}';

$string['crm_help_article_inbox_ai_title'] =
    'Using CRM Inbox AI assistance';
$string['crm_help_article_inbox_ai_summary'] =
    'Analyse, summarise, translate and draft replies while retaining human review.';

$string['settings:inbox_ai_header'] =
    'CRM Inbox AI assistance';

$string['settings:inbox_ai_header_desc'] =
    'Configure the AI provider used to analyse support conversations.';

$string['settings:inbox_ai_openai_enabled'] =
    'Enable OpenAI';

$string['settings:inbox_ai_openai_enabled_desc'] =
    'Email content may be transmitted to OpenAI when an analysis is requested.';

$string['settings:inbox_ai_openai_model'] =
    'OpenAI model';

$string['settings:inbox_ai_openai_model_desc'] =
    'Exact identifier of the OpenAI model authorised for the CRM Inbox.';

$string['settings:inbox_ai_openai_endpoint'] =
    'OpenAI endpoint';

$string['settings:inbox_ai_openai_endpoint_desc'] =
    'Endpoint used for the OpenAI Responses API.';

$string['settings:inbox_ai_openai_timeout'] =
    'OpenAI timeout';

$string['settings:inbox_ai_openai_max_output_tokens'] =
    'Maximum output tokens';

$string['settings:inbox_ai_openai_store'] =
    'Allow remote response storage';

$string['settings:inbox_ai_openai_store_desc'] =
    'Keep this disabled unless remote storage has been explicitly reviewed and approved.';

$string['settings:inbox_ai_include_crm_context'] =
    'Include verified CRM context';

$string['settings:inbox_ai_include_contact_email'] =
    'Include the contact email address';

$string['settings:inbox_ai_include_contact_email_desc'] =
    'Disabled by default to minimise personal data sent to the AI provider.';

$string['settings:inbox_ai_global_daily_limit'] =
    'Global daily quota';

$string['settings:inbox_ai_user_daily_limit'] =
    'Daily quota per administrator';

$string['settings:inbox_ai_automatic_analysis'] =
    'Enable automatic analysis';

$string['settings:inbox_ai_automatic_analysis_desc'] =
    'Enable only after costs, privacy rules and provider limits have been reviewed.';

$string['crm_inbox_ai_openai_enabled'] =
    'OpenAI is enabled.';

$string['crm_inbox_ai_openai_disabled'] =
    'OpenAI is disabled.';

$string['crm_inbox_ai_openai_key_available'] =
    'The OpenAI API key is available on the server.';

$string['crm_inbox_ai_openai_key_missing'] =
    'The OpenAI API key is missing.';

$string['crm_inbox_ai_openai_model_configured'] =
    'Configured OpenAI model: {$a}.';

$string['crm_inbox_ai_openai_model_missing'] =
    'No OpenAI model is configured.';

$string['crm_inbox_ai_data_transmission_notice'] =
    'Email content may be transmitted to the configured AI provider to generate analyses or suggestions.';

$string['crm_inbox_ai_provider_label'] =
    'Provider';

$string['crm_inbox_ai_model_label'] =
    'Model';

$string['crm_inbox_ai_cache_hit'] =
    'Cached result';

$string['crm_inbox_ai_cache_miss'] =
    'New provider analysis';

$string['crm_inbox_ai_force_refresh'] =
    'Refresh analysis';

$string['crm_inbox_ai_request_tokens'] =
    'Input tokens: {$a}';

$string['crm_inbox_ai_response_tokens'] =
    'Output tokens: {$a}';

$string['crm_inbox_ai_total_tokens'] =
    'Total tokens: {$a}';

$string['crm_inbox_ai_latency'] =
    'Processing time: {$a} ms';

$string['crm_inbox_ai_validation_failed'] =
    'The AI result failed local validation and was not presented as a successful analysis.';

$string['crm_inbox_ai_provider_unavailable'] =
    'The selected AI provider is unavailable.';

$string['crm_inbox_ai_provider_error'] =
    'The AI provider could not complete the analysis.';

$string['crm_inbox_ai_rate_limit'] =
    'The AI provider rate limit has been reached. Please try again later.';

$string['crm_inbox_ai_authentication_error'] =
    'The AI provider rejected the configured credentials.';

$string['crm_inbox_ai_privacy_notice'] =
    'AI results are suggestions only. Review all content before using or sending it.';

// Phase 6.5F — CRM Inbox integration.
$string['admin_event_inbox_message_received'] =
    'Email received in the Inbox';

$string['admin_event_inbox_reply_sent'] =
    'Reply sent from the Inbox';

$string['admin_event_inbox_thread_assigned'] =
    'Inbox conversation assigned';

$string['admin_event_inbox_thread_unassigned'] =
    'Inbox conversation unassigned';

$string['admin_event_inbox_thread_status_changed'] =
    'Inbox conversation status changed';

$string['admin_event_inbox_thread_priority_changed'] =
    'Inbox conversation priority changed';

$string['admin_event_inbox_ai_analysis_executed'] =
    'AI analysis run in the Inbox';

$string['admin_event_inbox_ai_reply_suggested'] =
    'AI reply suggestion generated';

// Phase 6.5F — Inbox on the user profile.
$string['crm_user_inbox_section'] =
    'CRM Inbox';

$string['crm_user_inbox_badge'] =
    'Inbox';

$string['crm_user_inbox_badge_empty'] =
    'No conversations';

$string['crm_user_inbox_badge_unread'] =
    '{$a} unread email(s)';

$string['crm_user_inbox_conversations'] =
    'Conversations';

$string['crm_user_inbox_open_conversations'] =
    'Open';

$string['crm_user_inbox_unread'] =
    'Unread emails';

$string['crm_user_inbox_ai_suggestions'] =
    'AI suggestions';

$string['crm_user_inbox_last_email'] =
    'Latest email';

$string['crm_user_inbox_last_received'] =
    'Latest email received';

$string['crm_user_inbox_last_sent'] =
    'Latest reply sent';

$string['crm_user_inbox_recent_conversations'] =
    'Recent conversations';

$string['crm_user_inbox_no_conversations'] =
    'No Inbox conversation is currently linked to this user.';

$string['crm_user_inbox_open_all'] =
    'Open conversations in the Inbox';

$string['crm_user_inbox_unread_badge'] =
    '{$a} unread';

// Phase 6.5F — Command Center Inbox.
$string['command_center_type_inbox_thread'] =
    'Inbox conversation';

$string['command_center_type_inbox_contact'] =
    'Inbox contact';

$string['command_center_group_inbox_threads'] =
    'Inbox conversations';

$string['command_center_group_inbox_contacts'] =
    'Inbox contacts';

$string['command_inbox_thread_status'] =
    'Status: {$a}';

$string['command_inbox_thread_priority'] =
    'Priority: {$a}';

$string['command_inbox_thread_unread'] =
    '{$a} unread';

$string['command_inbox_contact_conversations'] =
    '{$a} conversation(s)';

$string['command_inbox_contact_unread'] =
    '{$a} unread';

$string['command_inbox_unknown_contact'] =
    'Unknown Inbox contact';

$string['command_action_inbox_unassigned_title'] =
    'Open unassigned conversations';

$string['command_action_inbox_unassigned_subtitle'] =
    'Show Inbox conversations without an assigned administrator or team.';

$string['command_action_inbox_urgent_title'] =
    'Open urgent conversations';

$string['command_action_inbox_urgent_subtitle'] =
    'Show Inbox conversations with urgent priority.';

$string['command_action_inbox_diagnostics_title'] =
    'Open Inbox diagnostics';

$string['command_action_inbox_diagnostics_subtitle'] =
    'Check Inbox accounts, connectors, synchronisations and errors.';

$string['command_action_inbox_ai_diagnostics_title'] =
    'Open Inbox AI diagnostics';

$string['command_action_inbox_ai_diagnostics_subtitle'] =
    'Check the AI provider, models, prompts, cache and configuration.';

$string['command_action_inbox_sync_title'] =
    'Synchronise Inbox';

$string['command_action_inbox_sync_subtitle'] =
    'Fetch one page of new emails for each configured folder now.';

$string['command_confirm_inbox_sync'] =
    'Run a manual synchronisation for all active Inbox accounts now?';

$string['command_center_action_run'] =
    'Run';

$string['command_inbox_sync_no_accounts'] =
    'No active Inbox account is configured.';

$string['command_inbox_sync_success'] =
    'Synchronisation completed: {$a->fetched} fetched, {$a->created} created, {$a->skipped} skipped, {$a->errors} error(s).';

$string['command_inbox_sync_has_more'] =
    'More messages are available and will be fetched during the next run.';

$string['command_inbox_sync_failed'] =
    'Manual Inbox synchronisation failed. Check the Inbox diagnostics.';

// Phase 6.5F — User Explorer and Intelligence Inbox.
$string['crm_user_column_inbox'] =
    'Inbox';

$string['crm_user_has_inbox'] =
    'Has an Inbox conversation';

$string['crm_user_has_inbox_unread'] =
    'Has unread Inbox emails';

$string['crm_user_inbox_none'] =
    'No conversations';

$string['crm_user_inbox_conversation_count'] =
    '{$a} conversation(s)';

$string['crm_user_inbox_open_count'] =
    '{$a} open';

$string['crm_user_inbox_unread_count'] =
    '{$a} unread';

$string['crm_user_inbox_urgent_count'] =
    '{$a} urgent';

$string['crm_intelligence_inbox_conversations'] =
    '{$a} Inbox conversation(s)';

$string['crm_intelligence_inbox_open'] =
    '{$a} open';

$string['crm_intelligence_inbox_unread'] =
    '{$a} unread';

$string['crm_intelligence_inbox_urgent'] =
    '{$a} urgent';

$string['crm_intelligence_inbox_open_link'] =
    'Open conversations';

// Phase 6.5F — Inbox Help Center.
$string['crm_help_article_inbox_diagnostics_title'] =
    'Diagnosing the CRM Inbox';

$string['crm_help_article_inbox_diagnostics_summary'] =
    'Check IMAP, SMTP, synchronisation, attachments, matching and AI assistance.';

$string['crm_help_guide_inbox_ai_title'] =
    'Use AI assistance';

$string['crm_help_guide_inbox_ai_desc'] =
    'Analyse the conversation, review language and urgency, and always check suggestions before using them.';

$string['crm_help_guide_inbox_ai_action'] =
    'Read the AI guide';

$string['crm_help_guide_inbox_diagnostics_title'] =
    'Review diagnostics';

$string['crm_help_guide_inbox_diagnostics_desc'] =
    'Check IMAP, SMTP, synchronisation, attachments, the AI provider and quotas.';

$string['crm_help_guide_inbox_diagnostics_action'] =
    'Read the diagnostics guide';

$string['crm_onboarding_step_inbox_title'] =
    'Discover the CRM Inbox';

$string['crm_onboarding_step_inbox_desc'] =
    'Review support conversations, identify contacts, check priorities and explore reply tools.';

$string['crm_help_open_inbox_help'] =
    'Inbox diagnostics guide';

$string['crm_help_open_inbox_diagnostics'] =
    'Inbox diagnostics';

$string['crm_help_open_inbox_ai_diagnostics'] =
    'Inbox AI diagnostics';

$string['crm_inbox_help_subtitle'] =
    'Centralise, assign and process CampusFR support conversations.';

$string['crm_inbox_thread_help_subtitle'] =
    'Review the complete history, CRM information and available AI suggestions.';

$string['crm_inbox_diagnostics_help_subtitle'] =
    'Check Inbox accounts, connections, synchronisation and technical errors.';

$string['crm_inbox_ai_diagnostics_help_subtitle'] =
    'Check the AI provider, models, quotas, cache and recent errors.';

// Phase 6.5F — Inbox UX and accessibility.
$string['crm_inbox_region_label'] =
    'CRM Inbox';

$string['crm_inbox_result_count'] =
    '{$a} conversation(s) found';

$string['crm_inbox_empty_title'] =
    'No conversations found';

$string['crm_inbox_thread_list_label'] =
    'Inbox conversation list';

$string['crm_inbox_filters_label'] =
    'CRM Inbox filters';

$string['crm_inbox_unread_count_accessible'] =
    '{$a} unread message(s) in this conversation';

$string['crm_inbox_thread_region_label'] =
    'Inbox conversation';

$string['crm_inbox_thread_actions_label'] =
    'Conversation actions';

$string['crm_inbox_action_processing'] =
    'The action is being processed.';

$string['crm_inbox_processing'] =
    'Processing…';

$string['crm_inbox_message_content_label'] =
    'Message content';

$string['crm_inbox_attachments_label'] =
    'Message attachments';

$string['crm_inbox_download_attachment'] =
    'Download attachment {$a}';

$string['crm_inbox_reply_form_label'] =
    'Inbox reply form';

$string['crm_inbox_reply_processing'] =
    'The reply is being processed.';

$string['crm_inbox_reply_help'] =
    'Review the subject and message before sending. AI suggestions remain editable and must always be reviewed by a person.';

$string['crm_inbox_reply_actions_label'] =
    'Reply form actions';

$string['crm_inbox_saving'] =
    'Saving…';

$string['crm_inbox_sending'] =
    'Sending…';

$string['dashboard_inbox_title'] =
    'CRM Inbox';

$string['dashboard_inbox_subtitle'] =
    'Operational overview of conversations and requests requiring attention.';

$string['dashboard_inbox_open'] =
    'Open Inbox';

$string['dashboard_inbox_open_conversations'] =
    'Open conversations';

$string['dashboard_inbox_unassigned'] =
    'Unassigned';

$string['dashboard_inbox_urgent'] =
    'Urgent';

$string['dashboard_inbox_pending'] =
    'Replies pending';

$string['dashboard_inbox_recent_activity'] =
    'Recent activity';

$string['dashboard_inbox_empty'] =
    'No recent Inbox conversations.';

$string['dashboard_inbox_metric_aria'] =
    '{$a->label}: {$a->count}';

$string['crm_inbox_status_unknown'] =
    'Unknown status';

$string['crm_inbox_priority_unknown'] =
    'Unknown priority';

$string['crm_user_view_delete_processing'] =
    'Deleting the saved view.';

$string['crm_user_view_delete_processing_short'] =
    'Deleting…';

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
    'Conversation messages';

$string['crm_inbox_attachment_unavailable'] =
    'unavailable';

$string['crm_inbox_attachment_unavailable_aria'] =
    'Attachment {$a}, currently unavailable';

$string['crm_user_inbox_statistics_label'] =
    'User Inbox statistics';

$string['crm_user_inbox_stat_aria'] =
    '{$a->label}: {$a->value}';

$string['command_center_menu_actions'] =
    'Result actions';

$string['command_center_confirmation_dialog'] =
    'Action confirmation';

$string['subscriptions:view_work_items'] =
    'View CRM work items';

$string['subscriptions:manage_work_items'] =
    'Create and manage CRM work items';

$string['subscriptions:manage_work_configuration'] =
    'Configure CRM work teams and workflows';

$string['crm_work_title'] = 'Work Items';
$string['crm_work_subtitle'] =
    'Manage CampusFR tasks, tickets, incidents and internal requests.';

$string['crm_work_region_label'] =
    'Work Items list';

$string['crm_work_result_count'] =
    '{$a} Work Item(s)';

$string['crm_work_empty'] =
    'No Work Items match these criteria.';

$string['crm_work_create'] =
    'Create a Work Item';

$string['crm_work_created'] =
    'The Work Item has been created.';

$string['crm_work_back'] =
    'Back to Work Items';

$string['crm_work_status'] =
    'Status';

$string['crm_work_priority'] =
    'Priority';

$string['crm_work_type'] =
    'Type';

$string['crm_work_team'] =
    'Team';

$string['crm_work_due'] =
    'Due date';

$string['crm_work_assigned_user'] =
    'Assigned to a team member';

$string['crm_work_filter_mine'] =
    'My tasks';

$string['crm_work_filter_unassigned'] =
    'Unassigned';

$string['crm_work_filter_overdue'] =
    'Overdue';

$string['crm_work_comments'] =
    'Internal comments';

$string['crm_work_add_comment'] =
    'Add comment';

$string['crm_work_subtasks'] =
    'Subtasks';

$string['crm_work_links'] =
    'Linked objects';

$string['crm_work_history'] =
    'History';

$string['crm_work_teams'] =
    'Work Management teams';

$string['crm_work_team_name'] =
    'Team name';

$string['crm_work_team_create'] =
    'Create team';

$string['crm_work_field_title'] =
    'Title';

$string['crm_work_field_description'] =
    'Description';

$string['crm_work_create_from_thread'] =
    'Create a Work Item';

$string['crm_work_user_section'] =
    'Work Items';

$string['crm_work_total'] =
    'Total';

$string['crm_work_active'] =
    'Active';

$string['crm_work_urgent'] =
    'Urgent';

$string['crm_work_overdue'] =
    'Overdue';

$string['crm_work_unassigned'] =
    'Unassigned';

$string['crm_work_my_items'] =
    'My tasks';

$string['crm_work_open_user_items'] =
    'View all Work Items';

$string['crm_work_create_for_user'] =
    'Create a task';

$string['crm_work_dashboard_title'] =
    'Work Management';

$string['crm_work_status_open'] =
    'Open';

$string['crm_work_status_in_progress'] =
    'In progress';

$string['crm_work_status_blocked'] =
    'Blocked';

$string['crm_work_status_waiting'] =
    'Waiting';

$string['crm_work_status_resolved'] =
    'Resolved';

$string['crm_work_status_closed'] =
    'Closed';

$string['crm_work_status_cancelled'] =
    'Cancelled';

$string['crm_work_priority_low'] =
    'Low';

$string['crm_work_priority_normal'] =
    'Normal';

$string['crm_work_priority_high'] =
    'High';

$string['crm_work_priority_urgent'] =
    'Urgent';

$string['crm_work_priority_critical'] =
    'Critical';

$string['crm_work_type_task'] =
    'Task';

$string['crm_work_type_support'] =
    'Support';

$string['crm_work_type_bug'] =
    'Bug';

$string['crm_work_type_incident'] =
    'Incident';

$string['crm_work_type_feature'] =
    'Feature';

$string['crm_work_type_content'] =
    'Content';

$string['crm_work_type_marketing'] =
    'Marketing';

$string['crm_work_type_finance'] =
    'Finance';

$string['crm_work_type_administration'] =
    'Administration';

$string['crm_work_type_follow_up'] =
    'Follow-up';

$string['command_action_work_items_title'] =
    'Open Work Items';

$string['command_action_work_items_subtitle'] =
    'View all tasks, tickets and internal requests.';

$string['command_action_work_items_mine_title'] =
    'My tasks';

$string['command_action_work_items_mine_subtitle'] =
    'View Work Items assigned to you.';

$string['command_action_work_items_urgent_title'] =
    'Urgent Work Items';

$string['command_action_work_items_urgent_subtitle'] =
    'View Work Items with urgent priority.';

$string['command_action_work_items_overdue_title'] =
    'Overdue Work Items';

$string['command_action_work_items_overdue_subtitle'] =
    'View Work Items whose due date has passed.';

$string['command_action_work_items_unassigned_title'] =
    'Unassigned Work Items';

$string['command_action_work_items_unassigned_subtitle'] =
    'View active Work Items without an assignee.';

$string['crm_work_team_role_member'] =
    'Member';

$string['crm_work_team_role_lead'] =
    'Lead';

$string['crm_work_remove_member_confirm'] =
    'Remove this member from the team?';

$string['crm_help_category_work_management'] =
    'Work Management';

$string['crm_help_category_work_management_desc'] =
    'Organize CampusFR tasks, tickets, incidents and internal requests.';

$string['crm_help_article_work_management_title'] =
    'Managing Work Items';

$string['crm_help_article_work_management_summary'] =
    'Understand Work Item statuses, priorities, teams, assignments, subtasks and CRM links.';

$string['crm_assistant_title'] = 'CRM Assistant';
$string['crm_assistant_navigation'] = 'CRM Assistant';
$string['crm_assistant_description'] = 'Priority situations detected by the CRM cross-domain intelligence. The assistant explains and proposes actions but never makes automatic decisions.';
$string['crm_assistant_open'] = 'Open CRM Assistant';
$string['crm_assistant_empty'] = 'No recommendation currently requires your attention.';
$string['crm_assistant_user_section'] = 'CRM Assistant';

$string['crm_assistant_metric_active'] = 'Active';
$string['crm_assistant_metric_critical'] = 'Critical';
$string['crm_assistant_metric_urgent'] = 'Urgent';
$string['crm_assistant_metric_accepted'] = 'Accepted';
$string['crm_assistant_metric_crossdomain'] = 'Cross-domain';
$string['crm_assistant_metric_users'] = 'Affected users';

$string['crm_assistant_filter_scope'] = 'Scope';
$string['crm_assistant_filter_priority'] = 'Priority';
$string['crm_assistant_filter_status'] = 'Status';
$string['crm_assistant_filter_any'] = 'All';
$string['crm_assistant_scope_active'] = 'Active recommendations';
$string['crm_assistant_scope_all'] = 'Full history';

$string['crm_assistant_priority_critical'] = 'Critical';
$string['crm_assistant_priority_urgent'] = 'Urgent';
$string['crm_assistant_priority_high'] = 'High';
$string['crm_assistant_priority_normal'] = 'Normal';
$string['crm_assistant_priority_low'] = 'Low';

$string['crm_assistant_status_proposed'] = 'Proposed';
$string['crm_assistant_status_accepted'] = 'Accepted';
$string['crm_assistant_status_dismissed'] = 'Dismissed';
$string['crm_assistant_status_completed'] = 'Completed';
$string['crm_assistant_status_expired'] = 'Expired';

$string['crm_assistant_target'] = 'User';
$string['crm_assistant_why'] = 'Why this recommendation?';
$string['crm_assistant_priority_score'] = 'Priority score: {$a}';
$string['crm_assistant_evidence_count'] = '{$a} evidence item(s)';
$string['crm_assistant_source_count'] = '{$a} source(s)';
$string['crm_assistant_last_detected'] = 'Last detected: {$a}';

$string['crm_assistant_action_accept'] = 'Accept';
$string['crm_assistant_action_complete'] = 'Mark completed';
$string['crm_assistant_action_dismiss'] = 'Dismiss';
$string['crm_assistant_accepted'] = 'The recommendation was accepted.';
$string['crm_assistant_completed'] = 'The recommendation was marked as completed.';
$string['crm_assistant_dismissed'] = 'The recommendation was dismissed.';
$string['crm_assistant_action_failed'] = 'The recommendation action could not be completed.';

$string['command_crm_assistant'] = 'Open CRM Assistant';
$string['command_crm_assistant_desc'] = 'Display recommendations and priority situations.';
$string['command_crm_recommendation_desc'] = 'Active CRM recommendation.';

$string['crm_assistant_recommendation_intervene_disengagement_spiral'] = 'Intervene in progressive disengagement';
$string['crm_assistant_recommendation_intervene_disengagement_spiral_desc'] = 'Several signals indicate a lasting decline in activity and progress.';

$string['crm_assistant_recommendation_coordinate_learning_support_response'] = 'Coordinate learning and support follow-up';
$string['crm_assistant_recommendation_coordinate_learning_support_response_desc'] = 'A learning difficulty is accompanied by an active support request.';

$string['crm_assistant_recommendation_coordinate_payment_support_resolution'] = 'Resolve payment and support issues together';
$string['crm_assistant_recommendation_coordinate_payment_support_resolution_desc'] = 'A payment issue and a support conversation appear to be related.';

$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk'] = 'Intervene in a high churn-risk situation';
$string['crm_assistant_recommendation_intervene_cross_domain_churn_risk_desc'] = 'Access, activity and several friction points indicate a departure risk.';

$string['crm_assistant_recommendation_coordinate_operational_overload'] = 'Coordinate unresolved operational requests';
$string['crm_assistant_recommendation_coordinate_operational_overload_desc'] = 'Inbox pressure and unresolved Work Items require coordinated intervention.';

$string['crm_assistant_recommendation_review_customer_success_risk'] = 'Review Customer Success risk';
$string['crm_assistant_recommendation_review_learning_difficulty'] = 'Review learning difficulty';
$string['crm_assistant_recommendation_review_support_situation'] = 'Review support situation';
$string['crm_assistant_recommendation_review_blocked_payment'] = 'Review blocked payment';
$string['crm_assistant_recommendation_review_active_work_items'] = 'Review active Work Items';
$string['crm_assistant_recommendation_recognise_positive_progress'] = 'Recognise learner progress';

$string['crm_assistant_action_propose_work_item'] = 'Prepare a Work Item';

$string['crm_work_suggestion_title'] = 'Work Item suggestion';
$string['crm_work_suggestion_summary'] = 'CRM Assistant proposal';
$string['crm_work_suggestion_confidence'] = 'Suggestion confidence: {$a}%';
$string['crm_work_suggestion_suggested_type'] = 'Suggested type: {$a}';
$string['crm_work_suggestion_suggested_priority'] = 'Suggested priority: {$a}';
$string['crm_work_suggestion_suggested_due'] = 'Suggested due date: {$a}';
$string['crm_work_suggestion_duplicates'] = 'Similar Work Items';
$string['crm_work_suggestion_probable_duplicate_warning'] = 'A probably equivalent Work Item already exists. Review it before creating another item.';
$string['crm_work_suggestion_similarity'] = 'Estimated similarity: {$a}%';
$string['crm_work_suggestion_teams'] = 'Suggested teams';
$string['crm_work_suggestion_team_score'] = 'Relevance: {$a->score}% · Active workload: {$a->workload}';
$string['crm_work_suggestion_allow_duplicate'] = 'Create this Work Item despite the probable duplicate';
$string['crm_work_suggestion_create'] = 'Create Work Item';
$string['crm_work_suggestion_created'] = 'The Work Item was created from the recommendation.';
$string['crm_work_suggestion_duplicate_blocked'] = 'Creation was blocked because a probable duplicate already exists.';

$string['crm_work_suggestion_description_intro'] = 'This Work Item was prepared by the CRM Assistant. Its content must be reviewed and validated by an administrator.';
$string['crm_work_suggestion_source_recommendation'] = 'Source recommendation: {$a}';
$string['crm_work_suggestion_priority_score'] = 'Recommendation priority score: {$a}';
$string['crm_work_suggestion_evidence_heading'] = 'Evidence supporting the recommendation:';

$string['crm_work_suggestion_title_intervene_disengagement_spiral'] = 'Follow up on sustained learner disengagement';
$string['crm_work_suggestion_title_coordinate_learning_support_response'] = 'Coordinate learning and support follow-up';
$string['crm_work_suggestion_title_coordinate_payment_support_resolution'] = 'Resolve the payment and support issue';
$string['crm_work_suggestion_title_intervene_cross_domain_churn_risk'] = 'Set up a churn-prevention intervention';
$string['crm_work_suggestion_title_coordinate_operational_overload'] = 'Coordinate pending support requests and Work Items';
$string['crm_work_suggestion_title_review_customer_success_risk'] = 'Review the Customer Success situation';
$string['crm_work_suggestion_title_review_learning_difficulty'] = 'Set up learning follow-up';
$string['crm_work_suggestion_title_review_support_situation'] = 'Handle the support situation';
$string['crm_work_suggestion_title_review_blocked_payment'] = 'Handle the blocked payment';
$string['crm_work_suggestion_title_review_active_work_items'] = 'Review active Work Items';

$string['local/subscriptions:use_crm_assistant_ai'] = 'Use the conversational CRM Assistant';

$string['crm_assistant_ai_title'] = 'Ask the CRM Assistant';
$string['crm_assistant_ai_description'] = 'Ask a question about recommendations, affected users and active Work Items. Answers are based only on information already calculated by the CRM.';
$string['crm_assistant_ai_question'] = 'Your question';
$string['crm_assistant_ai_placeholder'] = 'Example: which learners require intervention today?';
$string['crm_assistant_ai_ask'] = 'Ask the Assistant';
$string['crm_assistant_ai_thinking'] = 'The Assistant is analysing the available information…';
$string['crm_assistant_ai_request_failed'] = 'The CRM Assistant could not answer this question.';
$string['crm_assistant_ai_human_review'] = 'Assistant answers are suggestions and must always be reviewed by an administrator.';
$string['crm_assistant_ai_keypoints'] = 'Key points';
$string['crm_assistant_ai_suggested_actions'] = 'Suggested actions';
$string['crm_assistant_ai_warnings'] = 'Warnings';
$string['crm_assistant_ai_references'] = 'Related CRM records';
$string['crm_assistant_ai_confidence'] = 'Estimated confidence';

$string['crm_assistant_ai_example_priorities'] = 'Which users should I handle first today?';
$string['crm_assistant_ai_example_risks'] = 'Which situations present the greatest risk?';
$string['crm_assistant_ai_example_work'] = 'Which Work Items appear urgent or blocked?';

$string['crm_assistant_question_rejected'] = 'This question cannot be processed by the CRM Assistant.';

$string['task_run_crm_recommendations'] = 'Refresh CRM recommendations';

$string['crm_recommendation_health_healthy'] = 'The Recommendation Engine is operating normally.';
$string['crm_recommendation_health_degraded'] = 'The Recommendation Engine is operating with warnings.';
$string['crm_recommendation_health_unhealthy'] = 'The Recommendation Engine requires attention.';
$string['crm_recommendation_run_completed'] = 'Recommendation run completed';
$string['crm_recommendation_run_partial'] = 'Recommendation run partially completed';
$string['crm_recommendation_run_failed'] = 'Recommendation run failed';
$string['crm_recommendation_run_skipped'] = 'Recommendation run skipped';

$string['csplanpage'] = 'Customer Success plan';
$string['csplanusersection'] = 'Customer Success plan';
$string['csplannoneforuser'] = 'No open Customer Success plan.';
$string['csplanblocked'] = 'Blocked';

$string['csplanstatus_draft'] = 'Draft';
$string['csplanstatus_active'] = 'Active';
$string['csplanstatus_paused'] = 'Paused';
$string['csplanstatus_completed'] = 'Completed';
$string['csplanstatus_cancelled'] = 'Cancelled';

$string['csplanstepstatus_pending'] = 'Pending';
$string['csplanstepstatus_ready'] = 'Ready';
$string['csplanstepstatus_blocked'] = 'Blocked';
$string['csplanstepstatus_in_progress'] = 'In progress';
$string['csplanstepstatus_completed'] = 'Completed';
$string['csplanstepstatus_skipped'] = 'Skipped';

$string['csplanpriority_low'] = 'Low';
$string['csplanpriority_normal'] = 'Normal';
$string['csplanpriority_high'] = 'High';
$string['csplanpriority_urgent'] = 'Urgent';
$string['csplanpriority_critical'] = 'Critical';

$string['csplanprogressvalue'] = '{$a->completed}/{$a->total} steps — {$a->percentage}%';
$string['csplanprogresspercentage'] = 'Progress: {$a}%';
$string['csplanstepdependency'] = 'Depends on step #{$a}';

$string['csplanaction_activate'] = 'Activate';
$string['csplanaction_pause'] = 'Pause';
$string['csplanaction_cancel'] = 'Cancel';
$string['csplanaction_startstep'] = 'Start';
$string['csplanaction_completestep'] = 'Complete';
$string['csplanaction_skipstep'] = 'Skip';
$string['csplanaction_unblockstep'] = 'Unblock';
$string['csplanactioncompleted'] = 'The Customer Success plan was updated.';

$string['csplantimelinecreated'] = 'Customer Success plan created';
$string['csplantimelineactivated'] = 'Customer Success plan activated';
$string['csplantimelinestepcompleted'] = 'Plan step processed';
$string['csplantimelinecompleted'] = 'Customer Success plan completed';

$string['csplandashboard_title'] = 'Customer Success plans';
$string['csplandashboard_open'] = 'Open plans';
$string['csplandashboard_active'] = 'Active plans';
$string['csplandashboard_blocked'] = 'Blocked steps';
$string['csplandashboard_critical'] = 'Critical plans';
$string['csplandashboard_completedtoday'] = 'Completed today';
$string['csplandashboard_averageprogress'] = 'Average progress: {$a}%';

$string['csplancommand_open'] = 'Open plan #{$a}';
$string['csplancommand_open_desc'] = 'Open the Customer Success plan';

$string['crm_user_has_customer_success_plan'] =
    'Has an open Customer Success plan';

$string['crm_user_customer_success_plan_blocked'] =
    'Has a blocked Customer Success step';

$string['crm_user_customer_success_plan_status'] =
    'Customer Success plan status';

$string['crm_user_customer_success_plan_status_all'] =
    'All statuses';

$string['crm_user_column_customer_success_plans'] =
    'Customer Success';

$string['crm_user_customer_success_none'] =
    'No open plan';

$string['crm_user_customer_success_open_count'] =
    '{$a} open plan(s)';

$string['crm_user_customer_success_blocked_count'] =
    '{$a} blocked';

$string['csplanobjective_reduce_churn_risk'] =
    'Reduce churn risk';

$string['csplanobjective_resolve_payment_friction'] =
    'Resolve payment difficulties';

$string['csplanobjective_resolve_support_pressure'] =
    'Resolve support requests';

$string['csplanobjective_restore_learning_access'] =
    'Restore learning access';

$string['csplanobjective_restore_learning_engagement'] =
    'Restore learning engagement';

$string['csplanobjective_develop_customer_opportunity'] =
    'Develop the customer opportunity';

$string['csplanobjective_coordinate_customer_success'] =
    'Coordinate Customer Success follow-up';

$string['csplandescription_recommendations'] =
    'Customer Success plan prepared from {$a} CRM recommendation(s).';

$string['csplanblockedreason_dependency_cycle'] =
    'This step is blocked by a dependency cycle.';

$string['csplanblockedreason_manual'] =
    'This step was blocked manually.';

$string['csplanblockedreason_unknown'] =
    'This step is blocked. Technical reason: {$a}';

$string['csplansource_manual'] =
    'Manual creation';

$string['csplansource_recommendation_engine'] =
    'Recommendation engine';

$string['csplansource_correlation_engine'] =
    'Correlation engine';

$string['csplansource_crm_assistant'] =
    'CRM Assistant';

$string['csplansource_user_360'] =
    'User 360° profile';

$string['csplanprogresslabel'] =
    'Customer Success plan progress';

$string['csplanactionfailed'] =
    'The Customer Success plan action could not be completed.';

$string['admin_event_customer_success_plan_created'] =
    'Customer Success plan created';

$string['admin_event_customer_success_plan_activated'] =
    'Customer Success plan activated';

$string['admin_event_customer_success_plan_paused'] =
    'Customer Success plan paused';

$string['admin_event_customer_success_plan_cancelled'] =
    'Customer Success plan cancelled';

$string['admin_event_customer_success_plan_completed'] =
    'Customer Success plan completed';

$string['admin_event_customer_success_plan_auto_completed'] =
    'Customer Success plan completed automatically';

$string['admin_event_customer_success_step_started'] =
    'Customer Success step started';

$string['admin_event_customer_success_step_completed'] =
    'Customer Success step completed';

$string['admin_event_customer_success_step_skipped'] =
    'Customer Success step skipped';

$string['admin_event_customer_success_step_blocked'] =
    'Customer Success step blocked';

$string['admin_event_customer_success_step_unblocked'] =
    'Customer Success step unblocked';

$string['csplanconfirmtitle'] =
    'Confirm action';

$string['csplanconfirmcancel'] =
    'Are you sure you want to cancel the “{$a}” plan? Its history will not be deleted.';

$string['csplanconfirmskipstep'] =
    'Are you sure you want to skip the “{$a}” step? Dependent steps may then become available.';

$string['csplanblockreasonlabel'] =
    'Blocking reason';

$string['csplanblockreasonplaceholder'] =
    'Explain why this step is blocked';

$string['csplanblockreasonhelp'] =
    'The reason will be displayed in the plan and recorded in the administrative history.';

$string['csplanblockreasonrequired'] =
    'You must provide a blocking reason.';

$string['csplanblockreasontoolong'] =
    'The blocking reason cannot exceed 500 characters.';

$string['csplanaction_blockstep'] =
    'Block step';

$string['crm_filter_customer_success'] =
    'Customer Success';

$string['crm_assistant_evidence_activity_inactive_30d'] =
    'No activity for at least 30 days';

$string['crm_assistant_evidence_value_activity_inactive_30d'] =
    '{$a} day(s) since the last activity';

$string['crm_assistant_evidence_loyalty_no_current_access'] =
    'No active access at present';

$string['crm_assistant_evidence_value_loyalty_no_current_access'] =
    '{$a} expired or cancelled access record(s)';

$string['crm_assistant_recommendation_send_trial_conversion_email'] =
    'Support conversion after the trial';

$string['crm_assistant_recommendation_send_trial_conversion_email_desc'] =
    'This user tried the platform but has not yet purchased paid access.';

$string['crm_assistant_recommendation_propose_upgrade'] =
    'Recommend a more complete plan';

$string['crm_assistant_recommendation_propose_upgrade_desc'] =
    'This customer’s current access could be expanded with a higher plan.';

$string['crm_assistant_recommendation_send_winback_message'] =
    'Reconnect with a former customer';

$string['crm_assistant_recommendation_send_winback_message_desc'] =
    'This former customer no longer has active access and may be worth contacting again.';

$string['crm_assistant_recommendation_suggest_digital_product'] =
    'Recommend a digital product';

$string['crm_assistant_recommendation_suggest_digital_product_desc'] =
    'This customer may be interested in a complementary digital product.';

$string['crm_assistant_recommendation_create_first_crm_note'] =
    'Create the first CRM note';

$string['crm_assistant_recommendation_create_first_crm_note_desc'] =
    'No qualitative information has yet been recorded in the CRM for this customer.';

$string['crm_assistant_evidence_crm_customer_without_notes'] =
    'No CRM note has yet been added for this customer';

$string['crm_assistant_evidence_opportunity_trial_to_purchase'] =
    'The free trial has not yet resulted in a purchase';

$string['crm_assistant_evidence_opportunity_upgrade_subscription'] =
    'A more complete plan may be relevant';

$string['crm_assistant_evidence_opportunity_winback_expired_customer'] =
    'The customer no longer has active access';

$string['crm_assistant_evidence_opportunity_cross_sell_digital_product'] =
    'A complementary digital product may be recommended';

$string['crm_work_source_manual'] =
    'Manual creation';

$string['crm_work_source_inbox'] =
    'CRM Inbox';

$string['crm_work_source_user_360'] =
    'User 360 profile';

$string['crm_work_source_dashboard'] =
    'CRM dashboard';

$string['crm_work_source_automation'] =
    'CRM automation';

$string['crm_work_source_intelligence'] =
    'CRM Intelligence';

$string['crm_work_source_assistant'] =
    'CRM Assistant';

$string['crm_work_source_command_center'] =
    'Command Center';

$string['crm_work_source_system'] =
    'System';

$string['crm_work_suggestion_reason_generated_from_recommendation'] =
    'Suggestion created from a CRM recommendation';

$string['crm_work_suggestion_reason_priority_derived_from_recommendation'] =
    'Priority calculated from the recommendation urgency';

$string['crm_work_suggestion_reason_type_derived_from_scenario'] =
    'Work type determined from the detected situation';

$string['crm_work_suggestion_reason_team_suggested_from_domain_and_workload'] =
    'Team suggested according to its domain and current workload';

$string['crm_work_suggestion_reason_duplicate_candidates_detected'] =
    'Similar Work Items were detected';

$string['crm_assistant_unknown_label'] =
    'Information unavailable';

$string['crm_assistant_evidence_learning_low_progress'] =
    'Insufficient learning progress';

$string['crm_assistant_evidence_recommendation_review_customer_success_risk'] =
    'A Customer Success risk review is required';

$string['crm_assistant_evidence_recommendation_review_learning_difficulty'] =
    'A potential learning difficulty was detected';

$string['crm_assistant_evidence_activity_inactive_14d'] =
    'Inactive for more than 14 days';

$string['crm_assistant_evidence_learning_not_started'] =
    'The learning journey has not started yet';

$string['crm_assistant_evidence_activity_never_accessed'] =
    'No learning activity has been accessed yet';

$string['crm_assistant_evidence_value_learning_low_progress'] =
    'Current progress: {$a}%';

$string['crm_assistant_evidence_value_activity_inactive_14d'] =
    '{$a} day(s) since the last activity';

$string['crm_daily_priorities_item_fallback'] =
    'Recommended CRM action';

$string['crm_intelligence_alert_fallback'] =
    'CRM alert to review';

$string['dashboard_revenue_currency_select'] =
    'Select the revenue currency';

$string['dashboard_revenue_subscriptions'] =
    'Subscriptions';

$string['dashboard_revenue_digital'] =
    'Digital products';

$string['dashboard_revenue_no_data'] =
    'No revenue during this period';

$string['dashboard_new_trials'] = 'New trials';
$string['dashboard_new_customers'] = 'New customers';

$string['dashboard_trial_customer_ratio'] =
    'Period customer / trial ratio';

$string['dashboard_trial_customer_ratio_help'] =
    'Compares new customers with users starting a trial during the selected period. This is not a cohort conversion rate: a customer may have started their trial during an earlier period.';

$string['dashboard_trial_customer_ratio_unavailable'] = '—';

$string['dashboard_trial_customer_ratio_value'] = '{$a}%';

$string['dashboard_funnel_title'] =
    'Acquisition funnel';

$string['dashboard_funnel_subtitle'] =
    'Cohorts and verifiable {$a}-day conversion';

$string['dashboard_funnel_new_users'] =
    'New users';

$string['dashboard_funnel_trial_users'] =
    'First trials';

$string['dashboard_funnel_new_customers'] =
    'New paying customers';

$string['dashboard_funnel_digital_buyers'] =
    'Digital buyers';

$string['dashboard_funnel_conversion'] =
    'Trial conversion';

$string['dashboard_funnel_conversion_details'] =
    '{$a->converted} conversion(s) among {$a->mature} trial(s) whose {$a->days}-day observation window has ended';

$string['dashboard_funnel_pending_observation'] =
    '{$a} recent trial(s) are still inside their observation window.';

$string['dashboard_funnel_rate_unavailable'] =
    'Unavailable';

$string['dashboard_funnel_rate_value'] =
    '{$a}%';

$string['dashboard_funnel_trend_stable'] =
    'Stable compared with the previous period';

$string['dashboard_funnel_trend_not_comparable'] =
    'No comparison available';

$string['dashboard_funnel_trend_absolute'] =
    '{$a} compared with the previous period';

$string['dashboard_funnel_trend_percent'] =
    '{$a}% compared with the previous period';

$string['dashboard_funnel_trend_points'] =
    '{$a} point(s) compared with the previous period';

$string['dashboard_funnel_explorer_active'] =
    'Funnel filter active';

$string['dashboard_funnel_explorer_new_users'] =
    'Users created during the period';

$string['dashboard_funnel_explorer_trial_users'] =
    'Users whose first trial started during the period';

$string['dashboard_funnel_explorer_new_customers'] =
    'Users whose first successful payment occurred during the period';

$string['dashboard_funnel_explorer_digital_buyers'] =
    'Distinct digital buyers during the period';

$string['dashboard_funnel_explorer_converted_trials'] =
    'Cohort trials converted inside the {$a}-day window';

// Phase 7.75E - Dashboard CRM trends.
$string['crm_trends_subtitle'] = '{$a->analysed} comparable users out of {$a->available} updated profiles.';
$string['crm_trends_users'] = 'user(s)';
$string['crm_trends_previous_value'] = 'Previous period: {$a}';
$string['crm_trends_difference_only'] = '{$a} user(s)';
$string['crm_trends_difference_with_percent'] = '{$a->difference} user(s) · {$a->variation}%';
$string['crm_trends_stable'] = 'Stable';
$string['crm_trends_open_explorer'] = 'Open Explorer';
$string['crm_trends_freshness'] = 'Latest snapshot: {$a}';
$string['crm_trends_freshness_unknown'] = 'Latest snapshot date is unavailable.';
$string['crm_trends_no_current_data'] = 'No Intelligence snapshot is available for this period.';
$string['crm_trends_insufficient_data'] = 'Snapshots are available, but there is not enough history yet to calculate changes.';
$string['crm_trends_no_movements'] = 'No significant movement was detected during this period.';
$string['crm_trends_error'] = 'CRM trends cannot currently be loaded.';

$string['crm_trends_metric_risk_up'] = 'Risk increasing';
$string['crm_trends_metric_risk_up_desc'] = 'Profiles whose risk score increased significantly.';
$string['crm_trends_metric_risk_down'] = 'Risk decreasing';
$string['crm_trends_metric_risk_down_desc'] = 'Profiles whose risk score improved significantly.';

$string['crm_trends_metric_engagement_up'] = 'Engagement increasing';
$string['crm_trends_metric_engagement_up_desc'] = 'Profiles whose engagement score improved.';
$string['crm_trends_metric_engagement_down'] = 'Engagement decreasing';
$string['crm_trends_metric_engagement_down_desc'] = 'Profiles whose engagement score declined.';

$string['crm_trends_metric_global_up'] = 'Global score increasing';
$string['crm_trends_metric_global_up_desc'] = 'Profiles whose overall CRM health improved.';
$string['crm_trends_metric_global_down'] = 'Global score decreasing';
$string['crm_trends_metric_global_down_desc'] = 'Profiles whose overall CRM health declined.';

$string['crm_trends_metric_unknown'] = 'CRM movement';
$string['crm_trends_metric_unknown_desc'] = 'A change was detected in CRM data.';

// Phase 7.75E - User Explorer trend drill-down.
$string['crm_trends_metric_open'] = 'Show users affected by: {$a}';
$string['crm_user_explorer_trend_active'] = 'Active trend filter';
$string['crm_user_explorer_trend_period'] = 'From {$a->start} to {$a->end}';
$string['crm_user_explorer_trend_threshold'] = 'Minimum change: {$a} points';
$string['crm_user_explorer_trend_clear'] = 'Leave trend view';

$string['crm_intelligence_alert_priority_critical'] =
    'Critical';

$string['crm_intelligence_alert_priority_high'] =
    'High';

$string['crm_intelligence_alert_priority_normal'] =
    'Normal';

$string['crm_intelligence_alert_priority_label'] =
    'Priority: {$a}';

$string['crm_intelligence_alert_signal_date'] =
    'CRM signal evaluated on {$a}';

$string['crm_intelligence_alert_signal_age'] =
    'Signal age: {$a}';

$string['crm_intelligence_alert_next_action_label'] =
    'Recommended next action';

$string['crm_intelligence_alert_next_action_high_risk_user'] =
    'Review the customer situation and arrange a priority follow-up.';

$string['crm_intelligence_alert_next_action_trial_without_purchase'] =
    'Contact the user to identify what is preventing the purchase.';

$string['crm_intelligence_alert_next_action_expired_without_reactivation'] =
    'Offer a reactivation or an offer adapted to the customer history.';

$string['crm_intelligence_alert_next_action_inactive_user'] =
    'Review the latest activity and prepare a personalized follow-up.';

$string['crm_intelligence_alert_next_action_hot_opportunity'] =
    'Contact the user quickly with a suitable commercial proposal.';

$string['crm_intelligence_alert_next_action_default'] =
    'Open the user profile and determine the next action.';

$string['crm_intelligence_alert_work_item'] =
    'Active Work Item';

$string['crm_intelligence_alert_cs_plan'] =
    'Customer Success plan';

$string['crm_intelligence_alert_responsible'] =
    'Owner: {$a}';

$string['crm_intelligence_alert_due_date'] =
    'Due date: {$a}';

$string['crm_intelligence_alert_target_date'] =
    'Target date: {$a}';

$string['crm_intelligence_alert_open_work_item'] =
    'Open Work Item';

$string['crm_intelligence_alert_create_work_item'] =
    'Create Work Item';

$string['crm_intelligence_alert_open_cs_plan'] =
    'Open CS plan';

$string['dashboard_state_loading_title'] =
    'Loading';

$string['dashboard_state_loading_description'] =
    'The information for this card is being prepared.';

$string['dashboard_state_error_title'] =
    'Unable to load this card';

$string['dashboard_state_error_description'] =
    'An error occurred while loading the information.';

$string['dashboard_state_empty_title'] =
    'No information available';

$string['dashboard_state_empty_description'] =
    'There is nothing to display at the moment.';

$string['dashboard_state_retry'] =
    'Try again';

$string['dashboard_open_all'] =
    'View all';

$string['admin_event_email_password_reset_notice_sent'] =
    'Password reset notification sent';

$string['admin_event_email_welcome_sent'] =
    'Welcome email sent';

$string['admin_event_email_receipt_sent'] =
    'Payment receipt sent';

$string['admin_event_email_subscription_access_sent'] =
    'Subscription access information sent';

$string['admin_event_user_password_updated'] =
    'User password updated';

$string['admin_event_user_note_added'] =
    'CRM note added';

$string['admin_event_subscription_created'] =
    'Subscription created';

$string['admin_event_subscription_created_manual'] =
    'Subscription created manually';

$string['admin_event_subscription_updated'] =
    'Subscription updated';

$string['admin_event_subscription_deleted'] =
    'Subscription deleted';

$string['admin_event_subscription_status_updated'] =
    'Subscription status updated';

$string['admin_event_subscription_dates_updated'] =
    'Subscription dates updated';

$string['admin_event_subscription_created_auto'] =
    'Subscription created automatically';

$string['admin_event_subscription_extended'] =
    'Subscription extended';

$string['admin_event_digital_provider_checked'] =
    'Digital payment status checked';

$string['admin_event_payment_request_created'] =
    'Payment request created';

$string['admin_event_payment_request_paid'] =
    'Payment request paid';

$string['admin_event_payment_request_failed'] =
    'Payment request failed';

$string['admin_event_payment_request_cancelled'] =
    'Payment request cancelled';

$string['admin_event_trial_started'] =
    'Trial started';

$string['admin_event_trial_expired'] =
    'Trial expired';

$string['admin_event_work_item_created'] =
    'Work Item created';

$string['admin_event_work_item_status_changed'] =
    'Work Item status changed';

$string['admin_event_work_item_priority_changed'] =
    'Work Item priority changed';

$string['admin_event_work_item_assigned'] =
    'Work Item assigned';

$string['admin_event_work_item_comment_added'] =
    'Comment added to Work Item';

$string['admin_event_work_item_linked'] =
    'Item linked to Work Item';

$string['admin_event_work_item_suggestion_opened'] =
    'Work Item suggestion opened';

$string['admin_event_work_item_created_from_recommendation'] =
    'Work Item created from recommendation';

$string['admin_event_work_item_duplicate_override'] =
    'Work Item created despite a possible duplicate';

$string['admin_event_recommendation_created'] =
    'Recommendation created';

$string['admin_event_recommendation_refreshed'] =
    'Recommendation refreshed';

$string['admin_event_recommendation_accepted'] =
    'Recommendation accepted';

$string['admin_event_recommendation_dismissed'] =
    'Recommendation dismissed';

$string['admin_event_recommendation_completed'] =
    'Recommendation completed';

$string['admin_event_recommendation_expired'] =
    'Recommendation expired';

$string['admin_event_recommendation_run_completed'] =
    'Recommendation generation completed';

$string['admin_event_recommendation_run_partial'] =
    'Recommendation generation partially completed';

$string['admin_event_recommendation_run_failed'] =
    'Recommendation generation failed';

$string['admin_event_recommendation_run_skipped'] =
    'Recommendation generation skipped';

$string['admin_event_description_reference'] =
    'Reference: {$a}';

$string['admin_event_description_transition'] =
    '{$a->from} → {$a->to}';

$string['admin_event_description_status'] =
    'Status: {$a}';

$string['admin_event_description_priority'] =
    'Priority: {$a}';

$string['admin_event_description_plan'] =
    'Plan: {$a}';

$string['admin_event_description_contact'] =
    'Contact: {$a}';

$string['admin_event_description_recommendation'] =
    'Recommendation: {$a}';

$string['admin_event_description_cs_plan'] =
    '{$a->reference} — {$a->title}';

$string['admin_event_description_cs_step'] =
    '{$a->plan} — {$a->step}';

$string['dashboard_activity_actor'] =
    'By {$a}';

$string['dashboard_activity_system_actor'] =
    'Automated action';

$string['dashboard_activity_open'] =
    'Open';

$string['dashboard_activity_target'] =
    'Customer: {$a}';

$string['dashboard_activity_exact_date'] =
    'Recorded on {$a}';

$string['crm_app_navigation'] =
    'Main CRM navigation';

$string['crm_admin_tools_title'] =
    'Administrator toolbox';

$string['crm_admin_tools_description'] =
    'Run and monitor CRM technical operations through a secured interface.';

$string['crm_admin_tool_busy'] =
    'This operation is already running.';

$string['crm_admin_tool_failed'] =
    'The operation failed. Review its history for more details.';

$string['crm_admin_tool_status_running'] =
    'Running';

$string['crm_admin_tool_status_success'] =
    'Completed';

$string['crm_admin_tool_status_failed'] =
    'Failed';

$string['crm_admin_tool_status_busy'] =
    'Already running';

$string['crm_admin_tool_status_cancelled'] =
    'Cancelled';

$string['crm_admin_tool_risk_low'] =
    'Low risk';

$string['crm_admin_tool_risk_normal'] =
    'Moderate risk';

$string['crm_admin_tool_risk_high'] =
    'High risk';

$string['crm_admin_tools_nav'] = 'Tools';
$string['crm_admin_tool_unknown'] =
    'The requested administrator tool could not be found.';
$string['crm_admin_tools_empty'] =
    'No administrator tools are available for your role.';
$string['crm_admin_tool_open'] = 'Open';
$string['crm_admin_tool_execute'] = 'Run now';
$string['crm_admin_tool_confirmation_warning'] =
    'This operation may modify CRM data. Check the parameters before continuing.';
$string['crm_admin_tool_limit'] =
    'Maximum number of items to process';
$string['crm_admin_tool_reset_cursor'] =
    'Restart recommendation processing from the beginning';
$string['crm_admin_tool_never_run'] = 'Never run';
$string['crm_admin_tool_last_run'] =
    'Last run: {$a->date} — {$a->status}';
$string['crm_admin_tool_history'] =
    'Operation history';
$string['crm_admin_tool_history_empty'] =
    'No administrator operation has been run yet.';
$string['crm_admin_tool_history_date'] = 'Date';
$string['crm_admin_tool_history_tool'] = 'Tool';
$string['crm_admin_tool_history_actor'] = 'User';
$string['crm_admin_tool_history_status'] = 'Status';
$string['crm_admin_tool_history_duration'] = 'Duration';

$string['crm_admin_tool_inbox_sync'] =
    'Synchronise CRM Inbox';
$string['crm_admin_tool_inbox_sync_desc'] =
    'Retrieves new messages from enabled Inbox accounts.';
$string['crm_admin_tool_inbox_sync_success'] =
    'Inbox synchronisation completed.';
$string['crm_admin_tool_inbox_sync_partial'] =
    'Inbox synchronisation completed with errors.';

$string['crm_admin_tool_inbox_diagnostics'] =
    'Diagnose CRM Inbox';
$string['crm_admin_tool_inbox_diagnostics_desc'] =
    'Checks configuration, tables, credentials and IMAP/SMTP connections.';
$string['crm_admin_tool_inbox_diagnostics_success'] =
    'All Inbox checks passed.';
$string['crm_admin_tool_inbox_diagnostics_failed'] =
    'Some Inbox checks failed.';

$string['crm_admin_tool_automations'] =
    'Run automations';
$string['crm_admin_tool_automations_desc'] =
    'Immediately executes CRM automation scanners and rules.';
$string['crm_admin_tool_automations_success'] =
    'CRM automations were executed.';

$string['crm_admin_tool_intelligence'] =
    'Recompute Intelligence scores';
$string['crm_admin_tool_intelligence_desc'] =
    'Recomputes and stores CRM score snapshots.';
$string['crm_admin_tool_intelligence_success'] =
    'Intelligence snapshots were recomputed.';

$string['crm_admin_tool_recommendations'] =
    'Recompute recommendations';
$string['crm_admin_tool_recommendations_desc'] =
    'Runs a new CRM Recommendation Engine batch.';
$string['crm_admin_tool_recommendations_success'] =
    'Recommendation batch completed.';
$string['crm_admin_tool_recommendations_partial'] =
    'Recommendation batch completed partially or with errors.';

$string['crm_admin_tool_digital_reconciliation'] =
    'Reconcile digital payments';
$string['crm_admin_tool_digital_reconciliation_desc'] =
    'Checks pending digital payment requests with payment providers.';
$string['crm_admin_tool_digital_reconciliation_success'] =
    'Digital payment reconciliation completed.';
$string['crm_admin_tool_digital_reconciliation_partial'] =
    'Digital reconciliation completed with errors.';

$string['crm_admin_tool_help_validation'] =
    'Validate Help Center';
$string['crm_admin_tool_help_validation_desc'] =
    'Checks Help Center articles, guides, onboarding and translations.';
$string['crm_admin_tool_help_validation_success'] =
    'The Help Center is valid.';
$string['crm_admin_tool_help_validation_failed'] =
    'The Help Center contains errors.';

$string['csplancommandsubtitle'] =
    'Open and manage this user’s Customer Success plan';

$string['crm_admin_tool_confirmation_required'] =
    'You must explicitly confirm this operation before running it.';

$string['crm_admin_tool_confirmation_checkbox'] =
    'I understand the consequences of this operation and confirm that it should be run.';

$string['crm_admin_tool_limit_help'] =
    'Default value: {$a->default}. Maximum allowed: {$a->maximum}.';

$string['crm_admin_tool_unknown_actor'] =
    'Unavailable user (#{$a})';

$string['err_invalid_redirect_url'] =
    'The payment gateway returned an invalid redirect URL.';

$string['payment_error_session_create'] =
    'The payment page could not be opened. No payment was made. Please try again in a few moments.';

$string['payment_error_digital_session_create'] =
    'The payment page for your purchase could not be opened. No payment was made.';

$string['payment_error_retry'] =
    'The payment retry could not be started. No new payment was made.';

$string['payment_error_invalid_redirect'] =
    'The payment gateway returned an invalid address. No payment was made.';

$string['payment_error_provider_unavailable'] =
    'The payment gateway is temporarily unavailable. No payment was made.';

$string['payment_error_reference'] =
    'Incident reference: {$a}';

$string['crm_topbar_brand_suffix'] = 'CRM';
$string['crm_topbar_dashboard_link'] = 'Open the CampusFR CRM Dashboard';
$string['crm_topbar_moodle_admin'] = 'Moodle administration';

$string['crm_topbar_user_menu'] = 'Open user menu';
$string['crm_topbar_user_navigation'] = 'User account navigation';
$string['crm_topbar_view_profile'] = 'View profile';
$string['crm_topbar_my_courses'] = 'My courses';
$string['crm_topbar_my_campus'] = 'My Campus';
$string['crm_topbar_my_resources'] = 'My resources';
$string['crm_topbar_my_purchases'] = 'My purchases';
$string['crm_topbar_shop'] = 'Shop';
$string['crm_topbar_grades'] = 'Grades';
$string['crm_topbar_calendar'] = 'Calendar';
$string['crm_topbar_preferences'] = 'Preferences';
$string['crm_topbar_switch_role'] = 'Switch role…';
$string['crm_topbar_logout'] = 'Log out';

$string['crm_topbar_language'] = 'Language';
$string['crm_topbar_language_menu'] = 'Choose language';
$string['crm_topbar_language_navigation'] = 'Available languages';

$string['dashboard_personalization_open'] = 'Customize Dashboard';
$string['dashboard_personalization_title'] = 'Customize Dashboard';
$string['dashboard_personalization_description'] = 'Choose which Cards are displayed and reorder them using drag and drop or the Move up and Move down buttons.';
$string['dashboard_personalization_close'] = 'Close Dashboard customization';
$string['dashboard_personalization_save'] = 'Save layout';
$string['dashboard_personalization_reset'] = 'Restore default layout';
$string['dashboard_personalization_reset_confirm'] = 'Restore the default Dashboard layout?';
$string['dashboard_personalization_save_error'] = 'The Dashboard layout could not be saved.';
$string['dashboard_personalization_drag'] = 'Drag to move';
$string['dashboard_personalization_move_up'] = 'Move the “{$a}” Card up';
$string['dashboard_personalization_move_down'] = 'Move the “{$a}” Card down';
$string['dashboard_personalization_visibility'] = 'Display the “{$a}” Card';
$string['dashboard_personalization_zone_hero'] = 'Main indicators';
$string['dashboard_personalization_zone_main'] = 'Main Dashboard';
$string['dashboard_personalization_zone_side'] = 'Side column';
$string['dashboard_personalization_main_empty'] = 'All Cards in the main Dashboard are currently hidden. Use the Customize button to display them again.';

$string['dashboard_personalization_card_stats'] = 'Main indicators';
$string['dashboard_personalization_card_stats_description'] = 'Users, subscriptions, trials, purchases and revenue.';
$string['dashboard_personalization_card_intelligence'] = 'CRM Intelligence';
$string['dashboard_personalization_card_intelligence_description'] = 'Scores, segments, opportunities and priority profiles.';
$string['dashboard_personalization_card_assistant'] = 'CRM Assistant';
$string['dashboard_personalization_card_assistant_description'] = 'Recommendations and actions suggested by the Assistant.';
$string['dashboard_personalization_card_inbox'] = 'CRM Inbox';
$string['dashboard_personalization_card_inbox_description'] = 'Messages, unread conversations and recent activity.';
$string['dashboard_personalization_card_work'] = 'Work Items';
$string['dashboard_personalization_card_work_description'] = 'Assigned, urgent, overdue and unassigned tasks.';
$string['dashboard_personalization_card_customer_success'] = 'Customer Success';
$string['dashboard_personalization_card_customer_success_description'] = 'Active plans, progress, blockers and critical situations.';
$string['dashboard_personalization_card_issues'] = 'Needs attention';
$string['dashboard_personalization_card_issues_description'] = 'Problems and anomalies requiring intervention.';
$string['dashboard_personalization_card_priorities'] = 'Daily priorities';
$string['dashboard_personalization_card_priorities_description'] = 'Today’s priority profiles and actions.';
$string['dashboard_personalization_card_funnel'] = 'Funnel';
$string['dashboard_personalization_card_funnel_description'] = 'Acquisition, trials, conversions and new customers.';
$string['dashboard_personalization_card_trends'] = 'Trends';
$string['dashboard_personalization_card_trends_description'] = 'Changes in risk, engagement and progress.';
$string['dashboard_personalization_card_intelligence_alerts'] = 'Intelligence alerts';
$string['dashboard_personalization_card_intelligence_alerts_description'] = 'Enriched CRM alerts and Customer Success context.';
$string['dashboard_personalization_card_navigation'] = 'Administrative shortcuts';
$string['dashboard_personalization_card_navigation_description'] = 'Access users, plans, purchases and tools.';
$string['dashboard_personalization_card_activity'] = 'Recent activity';
$string['dashboard_personalization_card_activity_description'] = 'Latest events recorded in the CRM.';
$string['dashboard_personalization_card_team'] = 'Team';
$string['dashboard_personalization_card_team_description'] = 'Summary of items assigned to the current user.';
$string['dashboard_personalization_zone_onboarding'] = 'CRM onboarding';

$string['workspace_toolbar_title'] = 'Edit mode';
$string['workspace_toolbar_description'] = 'Customize your workspace. Layout changes will be applied after they are saved.';
$string['workspace_toolbar_status_clean'] = 'No unsaved changes';
$string['workspace_toolbar_status_dirty'] = 'Unsaved changes';
$string['workspace_toolbar_status_saving'] = 'Saving…';
$string['workspace_toolbar_hidden_singular'] = 'hidden item';
$string['workspace_toolbar_hidden_plural'] = 'hidden items';
$string['workspace_toolbar_reset'] = 'Restore defaults';
$string['workspace_toolbar_cancel'] = 'Cancel';
$string['workspace_toolbar_save'] = 'Save';
$string['workspace_item_type_card'] = 'Card';
$string['workspace_item_type_widget'] = 'Widget';
$string['workspace_item_type_system'] = 'System';
$string['workspace_item_drag_handle'] = 'Move this item';
$string['workspace_item_drag_handle_named'] = 'Move the “{$a}” item';
$string['workspace_item_menu_open_named'] = 'Open actions for the “{$a}” item';
$string['workspace_item_menu_label_named'] = 'Available actions for the “{$a}” item';
$string['workspace_item_move_before'] = 'Move before';
$string['workspace_item_move_after'] = 'Move after';
$string['workspace_item_hide'] = 'Hide';
$string['workspace_item_reset'] = 'Reset this item';
$string['workspace_action_configure'] = 'Configure';
$string['workspace_action_duplicate'] = 'Duplicate';

$string['dashboard_category_overview'] = 'Overview';
$string['dashboard_category_intelligence'] = 'Intelligence';
$string['dashboard_category_operations'] = 'Operations';
$string['dashboard_category_customer_success'] = 'Customer Success';
$string['dashboard_category_navigation_activity'] = 'Navigation and activity';
$string['dashboard_category_team'] = 'Team';
$string['dashboard_category_system'] = 'System';
$string['dashboard_category_other'] = 'Other';

$string['dashboard_personalization_width_compact'] = 'Compact';
$string['dashboard_personalization_width_medium'] = 'Medium';
$string['dashboard_personalization_width_full'] = 'Full width';

$string['dashboard_personalization_type_card'] = 'Card';
$string['dashboard_personalization_type_widget'] = 'Widget';
$string['dashboard_personalization_type_system'] = 'System';

$string['dashboard_personalization_period_aware'] = 'Uses the period';
$string['dashboard_personalization_order_hint'] = 'Reorder items directly in the Dashboard while edit mode is active.';
$string['dashboard_workspace_action_open_details'] = 'Open detailed view';
$string['dashboard_workspace_empty_hero'] = 'No main indicator is currently displayed.';
$string['dashboard_workspace_empty_main'] = 'No Card is currently displayed in the main area.';
$string['dashboard_workspace_empty_side'] = 'No item is currently displayed in the side column.';
$string['dashboard_period_year'] = 'This year';
$string['dashboard_period_all'] = 'All time';

$string['dashboard_trends_all_time_title'] = 'Cumulative view';
$string['dashboard_trends_all_time_subtitle'] = 'Data since the CRM was created';
$string['dashboard_trends_all_time_message'] = 'Trends require a comparable previous period. Select Today, This week, This month or This year to display changes.';

$string['inbox_workspace_name'] = 'Inbox workspace';

$string['inbox_workspace_navigation'] = 'Navigation';

$string['inbox_workspace_list'] = 'Conversations';

$string['inbox_workspace_reading'] = 'Reading';

$string['inbox_workspace_context'] = 'Customer context';
$string['inbox_workspace_filters_label'] =
    'Inbox filters';

$string['inbox_workspace_filters_description'] =
    'Search and filter Inbox conversations.';

$string['inbox_workspace_thread_list_label'] =
    'Conversation list';

$string['inbox_workspace_thread_list_description'] =
    'View conversations matching the active filters.';

$string['inbox_thread_workspace_messages'] = 'Messages';
$string['inbox_thread_workspace_messages_description'] =
    'View the complete conversation history.';
$string['inbox_thread_workspace_reply'] = 'Reply';
$string['inbox_thread_workspace_reply_description'] =
    'Write a reply to this conversation.';
$string['inbox_thread_workspace_context'] =
    'Conversation and contact';
$string['inbox_thread_workspace_context_description'] =
    'View the contact, status and available actions.';
$string['inbox_thread_workspace_ai'] = 'AI assistant';
$string['inbox_thread_workspace_ai_description'] =
    'Analyse the conversation and prepare a reply.';
$string['inbox_thread_workspace_context_zone'] =
    'Conversation context';
$string['inbox_workspace_personalization_open'] =
    'Customize';
$string['inbox_workspace_personalization_title'] =
    'Customize conversation';
$string['inbox_workspace_personalization_description'] =
    'Choose visible panels and reorder the conversation context.';
$string['inbox_workspace_personalization_close'] =
    'Close customization';
$string['inbox_workspace_personalization_save_error'] =
    'The conversation layout could not be saved.';
$string['inbox_workspace_personalization_reset_confirm'] =
    'Reset the conversation layout?';

$string['inbox_workspace_zone_reading'] = 'Conversation';
$string['inbox_workspace_zone_context'] = 'Context';

$string['inbox_workspace_reading_placeholder_label'] =
    'Conversation preview';

$string['inbox_workspace_reading_placeholder_item_description'] =
    'Area reserved for reading the selected conversation.';

$string['inbox_workspace_reading_placeholder_title'] =
    'Select a conversation';

$string['inbox_workspace_reading_placeholder_description'] =
    'The conversation preview will appear here.';

$string['inbox_workspace_context_placeholder_label'] =
    'Conversation context';

$string['inbox_workspace_context_placeholder_item_description'] =
    'Area reserved for information about the contact and conversation.';

$string['inbox_workspace_context_placeholder_title'] =
    'Contextual information';

$string['inbox_workspace_context_placeholder_description'] =
    'Select a conversation to display the contact, status and useful information.';

$string['inbox_thread_workspace_overview'] =
    'Overview';

$string['inbox_thread_workspace_overview_description'] =
    'Status, priority, mailbox and main conversation information.';

$string['inbox_thread_workspace_contact'] =
    'Contact';

$string['inbox_thread_workspace_contact_description'] =
    'Contact details and link to the corresponding CRM profile.';

$string['inbox_thread_workspace_actions'] =
    'Actions';

$string['inbox_thread_workspace_actions_description'] =
    'Management actions available for this conversation.';

$string['inbox_thread_overview_account'] =
    'Mailbox';

$string['inbox_thread_overview_folder'] =
    'Folder';

$string['inbox_thread_overview_messages'] =
    'Messages';

$string['inbox_thread_overview_unread'] =
    'Unread';

$string['inbox_thread_overview_assignment'] =
    'Assignment';

$string['inbox_thread_overview_last_message'] =
    'Last message';

$string['inbox_thread_assignment_team'] =
    'Team: {$a}';

$string['inbox_thread_assignment_user'] =
    'User: {$a}';

$string['inbox_thread_assignment_unassigned'] =
    'Unassigned';

$string['inbox_thread_contact_title'] =
    'Contact';

$string['inbox_thread_contact_unavailable'] =
    'No contact details are available.';

$string['inbox_thread_contact_open_profile'] =
    'Open CRM profile';

$string['inbox_thread_contact_external_description'] =
    'This contact is not currently linked to a Moodle user.';

$string['inbox_thread_actions_title'] =
    'Actions';

$string['inbox_thread_actions_description'] =
    'Change the status, archive the conversation or create a follow-up task.';

$string['user360_workspace_region_label'] =
    'User profile workspace';

$string['user360_workspace_hero'] =
    'User identity';

$string['user360_workspace_hero_description'] =
    'User identity, CRM status, tags and main account information.';

$string['user360_workspace_zone_hero'] =
    'Identity';

$string['user360_workspace_zone_main'] =
    'Main information';

$string['user360_workspace_zone_sidebar'] =
    'Additional information';

$string['user360_workspace_zone_timeline'] =
    'Timeline';

$string['user360_workspace_personalization_open'] =
    'Customize profile';

$string['user360_workspace_personalization_title'] =
    'Customize user profile';

$string['user360_workspace_personalization_description'] =
    'Choose which panels to display and arrange them to match your workflow.';

$string['user360_workspace_personalization_close'] =
    'Close customization';

$string['user360_workspace_personalization_save_error'] =
    'The user profile customization could not be saved.';

$string['user360_workspace_personalization_reset_confirm'] =
    'Reset the user profile layout?';

$string['user360_workspace_intelligence'] =
    'CRM Intelligence';

$string['user360_workspace_intelligence_description'] =
    'Scores, trends, segments, opportunities and recommendations for this user.';

$string['user360_workspace_customer_success'] =
    'Customer Success';

$string['user360_workspace_customer_success_description'] =
    'Customer Success plans, follow-up actions and user support.';

$string['user360_workspace_inbox'] =
    'Inbox';

$string['user360_workspace_inbox_description'] =
    'Conversations, unread messages and recent exchanges with this user.';

$string['user360_workspace_notes'] =
    'Notes';

$string['user360_workspace_notes_description'] =
    'Internal CRM notes associated with this user.';

$string['user360_workspace_work_items'] =
    'Work items';

$string['user360_workspace_work_items_description'] =
    'Tasks and work items associated with this user.';

$string['user360_workspace_timeline'] =
    'Timeline';

$string['user360_workspace_timeline_description'] =
    'Complete chronological history of events related to this user.';

$string['user360_workspace_zone_summary'] =
    'Summary';

$string['user360_workspace_stats'] =
    'Overview';

$string['user360_workspace_stats_description'] =
    'CRM status, subscriptions, purchases, accessible courses, revenue and latest activity.';

$string['user360_workspace_quick_actions'] =
    'Quick actions';

$string['user360_workspace_quick_actions_description'] =
    'Administrative actions and quick note creation for this user.';

$string['user360_workspace_assistant'] =
    'CRM Assistant';

$string['user360_workspace_assistant_description'] =
    'Analysis, recommendations and actions suggested by the CRM Assistant.';

$string['user360_workspace_commercial'] =
    'Commercial activity';

$string['user360_workspace_commercial_description'] =
    'Subscriptions and digital purchases associated with this user.';

$string['user360_workspace_courses'] =
    'Accessible courses';

$string['user360_workspace_courses_description'] =
    'Courses currently accessible to this user.';

$string['crm_user_not_found'] = 'User not found';
$string['crm_user_not_found_description'] = 'The requested CRM profile cannot be displayed.';
$string['crm_user_not_found_message'] = 'No active Moodle user matches ID {$a}. The user may have been deleted or the link may be outdated.';
$string['crm_user_not_found_back'] = 'Back to users';
$string['crm_user_deleted'] = 'Deleted Moodle account';
$string['crm_user_deleted_description'] = 'This user is no longer active in Moodle.';
$string['crm_user_deleted_message'] = 'The Moodle account associated with ID {$a} has been deleted. Some historical CRM data may still be available.';
$string['crm_user_history_title'] = 'Historical CRM profile · user {$a}';
$string['crm_user_history_description'] = 'CRM data retained for a deleted Moodle account.';
$string['crm_user_history_readonly'] = 'Read-only historical profile';
$string['crm_user_history_readonly_description'] = 'The Moodle account associated with ID {$a} has been deleted. The data shown here cannot be used to perform actions on the account.';
$string['crm_user_history_summary'] = 'Historical CRM profile summary';
$string['crm_user_history_userid'] = 'Moodle ID';
$string['crm_user_history_subscriptions'] = 'Historical subscriptions';
$string['crm_user_history_digital_purchases'] = 'Digital purchases';
$string['crm_user_history_courses'] = 'Historical courses';
$string['crm_user_history_last_activity'] = 'Last CRM activity';
$string['crm_user_history_revenue'] = 'Historical revenue';
$string['crm_user_history_open_users'] = 'Back to users';
$string['crm_user_history_open_inbox'] = 'View in Inbox';
$string['crm_user_history_open_work'] = 'View Work Items';
$string['crm_user_history_no_subscriptions'] = 'No historical subscriptions were found.';
$string['crm_user_history_no_digital_purchases'] = 'No historical digital purchases were found.';
$string['crm_user_history_no_notes'] = 'No historical CRM notes were found.';
$string['crm_user_history_no_tags'] = 'No historical CRM tags were found.';
$string['crm_user_history_unknown_plan'] = 'Unavailable plan';
$string['crm_user_history_unknown_product'] = 'Unavailable product';
$string['crm_user_history_plan'] = 'Plan';
$string['crm_user_history_amount'] = 'Amount';
$string['crm_notes'] = 'CRM notes';
$string['crm_tags'] = 'CRM tags';

$string['crm_inbox_invalid_form_action'] = 'The requested Inbox form action is invalid or missing.';

$string['crm_timeline_category_commercial'] = 'Commercial';
$string['crm_timeline_category_learning'] = 'Learning';
$string['crm_timeline_category_inbox'] = 'Inbox';
$string['crm_timeline_category_notes'] = 'Notes and tags';
$string['crm_timeline_category_work'] = 'Work Items';
$string['crm_timeline_category_customer_success'] = 'Customer Success';
$string['crm_timeline_category_automation'] = 'Automations';
$string['crm_timeline_category_administration'] = 'Administration';

$string['crm_timeline_search'] = 'Search the Timeline';
$string['crm_timeline_period'] = 'Timeline period';
$string['crm_timeline_period_all'] = 'All time';
$string['crm_timeline_period_7_days'] = 'Last 7 days';
$string['crm_timeline_period_30_days'] = 'Last 30 days';
$string['crm_timeline_period_90_days'] = 'Last 90 days';
$string['crm_timeline_period_year'] = 'Last 12 months';
$string['crm_timeline_important_only'] = 'Important events only';
$string['crm_timeline_filter_categories'] = 'Filter the Timeline by category';
$string['crm_timeline_results_count'] = '{$a} event(s) displayed';
$string['crm_timeline_no_filtered_results'] = 'No events match the selected filters.';
$string['crm_timeline_open_event'] = 'Open';
$string['crm_timeline_event'] = 'CRM event';
$string['crm_timeline_yesterday'] = 'Yesterday';
$string['crm_timeline_load_more'] = 'Load more events';
$string['crm_timeline_loading'] = 'Loading…';
$string['crm_timeline_loading_error'] = 'Retry loading';
$string['crm_timeline_loaded_events'] = 'events loaded';
$string['crm_timeline_important_events'] = 'important events';
$string['crm_timeline_latest_event'] = 'Latest event';
$string['crm_timeline_view_full'] = 'View full Timeline';

$string['user360_workspace_timeline_summary'] = 'Timeline summary';
$string['user360_workspace_timeline_summary_description'] = 'Displays the latest events and the number of important items.';

$string['crm_navigation_toggle'] = 'Navigation';
$string['crm_navigation_open'] = 'Open CRM navigation';
$string['crm_navigation_close'] = 'Close CRM navigation';
$string['crm_command_center_short_label'] = 'Search';

$string['crm_inbox_back_to_thread'] =
    'Back to the conversation';

$string['crm_inbox_reply_help_subtitle'] =
    'Write, save or send a reply in this conversation.';
$string['crm_work_create_subtitle'] =
    'Create a CRM task, follow-up or action and assign it to the appropriate person or team.';
$string['crm_work_teams_subtitle'] =
    'Create CRM teams and manage their members, leads and availability.';
$string['crm_customer_success_plan_subtitle'] =
    'Review the goals, actions, deadlines and signals associated with this Customer Success plan.';
$string['crm_work_suggestion_subtitle'] =
    'Review the Assistant proposal before creating the Work Item.';
$string['crm_admin_tool_history_subtitle'] =
    'Review the latest administrative tool runs and their results.';

$string['crm_breadcrumb_navigation'] =
    'CRM breadcrumb';
$string['crm_help_home_subtitle'] =
    'Access the CampusFR CRM documentation, practical guides and diagnostic tools.';
$string['crm_skip_to_content'] =
    'Skip directly to the content';

$string['crm_inbox_preview_loading'] =
    'Loading conversation…';

$string['crm_inbox_preview_error'] =
    'The conversation preview could not be loaded.';

$string['crm_inbox_preview_loaded'] =
    'Conversation “{$a}” loaded.';

$string['crm_inbox_preview_open_full'] =
    'Open full conversation';

$string['crm_inbox_preview_manage'] =
    'Reply to and manage the conversation';

$string['crm_inbox_preview_reading_region'] =
    'Conversation preview';

$string['crm_inbox_preview_context_region'] =
    'Contact context';

$string['crm_commerce_nav'] = 'Commerce';
$string['crm_commerce_title'] = 'Commerce';
$string['crm_commerce_description'] = 'Manage subscriptions, digital purchases and products from a unified commerce workspace.';

$string['crm_commerce_no_access'] = 'You currently do not have access to any commerce modules.';

$string['crm_commerce_subscriptions_title'] = 'Subscriptions';
$string['crm_commerce_subscriptions_description'] = 'View and manage subscriptions, paid enrolments and their history.';

$string['crm_commerce_imports_title'] = 'Imports';
$string['crm_commerce_imports_description'] = 'Import subscriptions and access the related import tools.';

$string['crm_commerce_configuration_title'] = 'Commerce configuration';
$string['crm_commerce_configuration_description'] = 'Manage plans, prices, access entitlements, translations and plan upgrades.';

$string['crm_commerce_digital_products_title'] = 'Digital products';
$string['crm_commerce_digital_products_description'] = 'Create and manage the digital products available in the store.';

$string['crm_commerce_digital_purchases_title'] = 'Digital purchases';
$string['crm_commerce_digital_purchases_description'] = 'Review digital purchases, payments and customer access delivery.';

$string['crm_commerce_statistics_title'] = 'Commerce statistics';
$string['crm_commerce_statistics_description'] = 'Analyse digital sales, revenue and the main commerce indicators.';

$string['admin_card_commerce_title'] = 'Commerce';
$string['admin_card_commerce_description'] = 'Access subscriptions, digital products, purchases, imports, statistics and commerce tools.';

$string['crm_subscriptions_title'] = 'Subscriptions';
$string['crm_subscriptions_description'] = 'View and manage paid enrolments, access periods and plans assigned to users.';
$string['crm_subscriptions_breadcrumb'] = 'Subscriptions';
$string['crm_subscription_view_description'] = 'Review commerce information, access dates, the associated payment and provider references.';
$string['crm_subscription_edit_description'] = 'Update the access dates and status of this subscription.';
$string['crm_subscription_add_description'] = 'Manually assign a plan to an existing user or create a new account before enrolment.';
$string['crm_subscriptions_export_title'] = 'Export subscriptions';
$string['crm_subscriptions_export_description'] = 'Download subscriptions and their main commerce information as an Excel workbook.';
$string['crm_subscriptions_export_help'] = 'The workbook contains separate sheets for long-term plans, the A1 course and trial subscriptions.';
$string['crm_subscriptions_export_download'] = 'Download the Excel file';
$string['crm_subscriptions_export_sheet_long'] = '1 year - 3 years - lifetime';
$string['crm_subscriptions_export_sheet_a1'] = 'A1 course';
$string['crm_subscriptions_export_sheet_trial'] = 'Trial';
$string['crm_subscriptions_import_description'] = 'Import multiple subscriptions from a CSV file and review the data before creation.';
$string['crm_subscriptions_import_result_title'] = 'Import result';
$string['crm_subscriptions_import_result_description'] = 'Review imported subscriptions and the rows skipped during processing.';
$string['crm_subscriptions_view_list'] = 'View subscriptions';
$string['crm_subscriptions_import_another'] = 'Import another file';
$string['crm_subscription_configuration_title'] = 'Subscription configuration';
$string['crm_subscription_configuration_description'] = 'Manage commerce plans, their durations and the course access scopes they provide.';
$string['crm_plan_prices_description'] = 'Manage multi-currency prices and payment-provider price identifiers for this plan.';
$string['crm_plan_translations_title'] = 'Plan translations';
$string['crm_plan_translations_description'] = 'Manage translated names and content for commerce plans in the available languages.';
$string['crm_plan_entitlements_description'] = 'Define the courses, roles, groups and access levels automatically granted by this plan.';
$string['crm_plan_upgrades_description'] = 'Configure allowed plan upgrades and the pricing method used for each path.';
$string['crm_scope_translations_title'] = 'Access scope translations';
$string['crm_scope_translations_description'] = 'Manage translated labels for the course access scopes used by plans.';
$string['crm_digital_products_description'] = 'Manage digital products, files, translations, prices and storefront availability.';
$string['crm_digital_product_add_description'] = 'Create a digital product, upload its files and prepare its sales content in French, English and Russian.';
$string['crm_digital_product_edit_description'] = 'Update this digital product\'s files, prices, availability and translated content.';
$string['crm_digital_purchase_view_description'] = 'Review commerce information, payment, file access and technical data for this digital purchase.';
$string['crm_digital_sales_stats_description'] = 'Analyse digital product sales volume and cumulative growth over the selected period.';

$string['crm_commerce_section_navigation'] = 'Commerce secondary navigation';
$string['crm_commerce_nav_overview'] = 'Overview';
$string['crm_commerce_nav_subscriptions'] = 'Subscriptions';
$string['crm_commerce_nav_digital_purchases'] = 'Digital purchases';
$string['crm_commerce_nav_digital_products'] = 'Digital products';
$string['crm_commerce_nav_statistics'] = 'Statistics';
$string['crm_commerce_nav_configuration'] = 'Configuration';

$string['settings:commerce_migration_heading'] = 'Commerce — migration and safety';
$string['settings:commerce_migration_heading_desc'] = 'Advanced settings controlling Commerce payment flows. Changes may affect revenue: validate each scenario and keep a rollback plan.';
$string['settings:commerce_fulfillment_enabled'] = 'Enable Commerce fulfillment';
$string['settings:commerce_fulfillment_enabled_desc'] =
    'Uses certified Commerce fulfillment after payment confirmation. Disable this setting to immediately return to Legacy post-payment processing.';
$string['settings:commerce_checkout_enabled'] =
    'Enable Commerce checkout';

$string['settings:commerce_checkout_enabled_desc'] =
    'Uses the certified Commerce architecture to initialize Stripe EUR and Alfa RUB payments. Disable this setting to immediately return to the Legacy checkout.';

$string['crm_help_category_commerce'] =
    'Commerce and payments';

$string['crm_help_category_commerce_desc'] =
    'Commerce architecture, checkout, providers, fulfillment, operations and diagnostics.';

$string['crm_help_article_commerce_overview_title'] =
    'Understanding the Commerce architecture';

$string['crm_help_article_commerce_overview_summary'] =
    'Overview of Commerce purchases, payments, checkout and fulfillment.';

$string['crm_help_article_commerce_operations_title'] =
    'Operating Commerce in production';

$string['crm_help_article_commerce_operations_summary'] =
    'Configuration, kill switches, providers and safe rollback procedures.';

$string['crm_help_article_commerce_diagnostics_title'] =
    'Auditing and diagnosing Commerce';

$string['crm_help_article_commerce_diagnostics_summary'] =
    'Validation, certification, integrity and fulfillment commands for incident diagnosis.';

$string['crm_help_article_commerce_extension_title'] =
    'Extending the Commerce architecture';

$string['crm_help_article_commerce_extension_summary'] =
    'Add a provider, purchase type or fulfillment handler without bypassing Commerce contracts.';

$string['settings:commerce_dual_write_enabled'] = 'Enable native Commerce dual-write';
$string['settings:commerce_dual_write_enabled_desc'] = 'After a Legacy Commerce purchase changes, synchronise and verify its native Commerce snapshot. Disabled by default.';
$string['settings:commerce_dual_write_strict'] = 'Strict native Commerce dual-write';
$string['settings:commerce_dual_write_strict_desc'] = 'Abort the triggering operation when native synchronisation fails. Keep disabled during the initial observation period.';
$string['settings:commerce_native_read_shadow_enabled'] = 'Enable native Commerce shadow reads';
$string['settings:commerce_native_read_shadow_enabled_desc'] = 'Also reads and compares the native snapshot while always returning Legacy. Disabled by default.';
$string['settings:commerce_native_read_shadow_strict'] = 'Strict native Commerce shadow reads';
$string['settings:commerce_native_read_shadow_strict_desc'] = 'Throw an exception when a mismatch is detected. Intended for DEV tests and audits only.';

$string['settings:commerce_runtime_read_mode'] = 'Commerce runtime read mode';
$string['settings:commerce_runtime_read_mode_desc'] = 'Selects the persistence source used by the I7 runtime reader. Consumer screens are migrated later in I8 and I9.';
$string['settings:commerce_runtime_read_mode_legacy'] = 'Legacy only';
$string['settings:commerce_runtime_read_mode_shadow'] = 'Shadow: return Legacy and compare Native';
$string['settings:commerce_runtime_read_mode_native'] = 'Native only';
$string['settings:commerce_runtime_read_mode_auto'] = 'Auto: Native with automatic Legacy fallback';
$string['settings:commerce_runtime_read_strict'] = 'Strict Commerce runtime reads';
$string['settings:commerce_runtime_read_strict_desc'] = 'Throws an exception on fallback, mismatch or missing data. Intended for DEV certification only.';
$string['settings:commerce_native_crm_reads_enabled'] = 'Native Commerce reads for CRM';
$string['settings:commerce_native_crm_reads_enabled_desc'] = 'Uses the I10C native read layer for CRM consumers.';
$string['settings:commerce_native_admin_reads_enabled'] = 'Native Commerce reads for administration';
$string['settings:commerce_native_admin_reads_enabled_desc'] = 'Uses the I10C native read layer for administration consumers.';
$string['settings:commerce_native_user_reads_enabled'] = 'Native Commerce reads for user pages';
$string['settings:commerce_native_user_reads_enabled_desc'] = 'Uses the I10C native read layer for user-facing consumers.';
$string['settings:commerce_native_email_reads_enabled'] = 'Native Commerce reads for emails';
$string['settings:commerce_native_email_reads_enabled_desc'] = 'Uses the I10C native read layer for email contexts.';
$string['settings:commerce_native_task_reads_enabled'] = 'Native Commerce reads for scheduled tasks';
$string['settings:commerce_native_task_reads_enabled_desc'] = 'Uses the I10C native read layer for Commerce scheduled tasks.';
$string['settings:commerce_native_shadow_compare_enabled'] = 'Compare native and Legacy Commerce reads';
$string['settings:commerce_native_shadow_compare_enabled_desc'] = 'Runs non-blocking shadow comparisons while returning the configured source.';
$string['settings:commerce_native_legacy_fallback_enabled'] = 'Allow Legacy Commerce read fallback';
$string['settings:commerce_native_legacy_fallback_enabled_desc'] = 'Falls back to Legacy data when a native read is unavailable.';

// I10D Native-aware commands.
$string['settings:commerce_native_dual_write_enabled'] = 'Enable I10D Native dual-write';
$string['settings:commerce_native_dual_write_enabled_desc'] = 'Allows runtime Commerce command services to synchronise Legacy writes to Native persistence. Disabled by default.';
$string['settings:commerce_native_task_dual_write_enabled'] = 'Enable I10D task dual-write';
$string['settings:commerce_native_task_dual_write_enabled_desc'] = 'Allows Commerce scheduled tasks to synchronise their Legacy mutations to Native persistence. Disabled by default.';
$string['settings:commerce_native_shadow_write_compare_enabled'] = 'Enable I10D write shadow comparison';
$string['settings:commerce_native_shadow_write_compare_enabled_desc'] = 'Compares Legacy and Native state after command execution without changing the user-facing result.';

$string['commerce_native_reconciliation_enabled'] = 'Native Commerce reconciliation';
$string['commerce_native_reconciliation_enabled_desc'] = 'Enables Native Commerce reconciliation.';
$string['commerce_native_repair_enabled'] = 'Native Commerce repair';
$string['commerce_native_repair_enabled_desc'] = 'Allows explicit repairs during reconciliation.';

// Phase 7.94E4 - Unified Commerce Product Editor.
$string['crm_commerce_nav_products'] = 'Products';
$string['commerce_products_title'] = 'Commerce products';
$string['commerce_products_description'] = 'Manage the unified Native Commerce catalogue.';
$string['commerce_product_add'] = 'Add a product';
$string['commerce_product_sku'] = 'SKU';
$string['commerce_product_name'] = 'Name';
$string['commerce_product_type'] = 'Type';
$string['commerce_product_status'] = 'Status';
$string['commerce_product_description'] = 'Description';
$string['commerce_product_definition'] = 'Definition';
$string['commerce_product_definition_counts'] = 'Prices: {$a->prices}; translations: {$a->translations}; components: {$a->components}; entitlements: {$a->entitlements}';
$string['commerce_bundle_edit_components'] = 'Edit bundle components';

// Phase 7.94E5 - Bundle visual component editor.
$string['commerce_bundle_components_title'] = 'Components — {$a}';
$string['commerce_bundle_components_help'] = 'Select products, quantities and display order. Empty rows are ignored. Saving validates the complete recursive expansion.';
$string['commerce_bundle_component_number'] = 'Component {$a}';
$string['commerce_bundle_component_product'] = 'Product';
$string['commerce_bundle_component_quantity'] = 'Quantity';
$string['commerce_bundle_component_order'] = 'Order';
$string['commerce_bundle_add_rows'] = 'Add more rows';
$string['commerce_bundle_preview_title'] = 'Expanded bundle preview';

// Phase 7.94E6 - Bundle preview and guided CRM workflow.
$string['commerce_product_workflow'] = 'Product configuration steps';
$string['commerce_product_step_information'] = 'Information';
$string['commerce_product_step_components'] = 'Composition';
$string['commerce_product_step_preview'] = 'Preview';
$string['commerce_product_step_pricing'] = 'Pricing';
$string['commerce_bundle_open_preview'] = 'Open full preview';
$string['commerce_bundle_preview_eyebrow'] = 'Pre-release control';
$string['commerce_bundle_preview_intro'] = 'Review the products actually included, their quantities, available prices and the rights that will be granted.';
$string['commerce_bundle_preview_unavailable'] = 'The preview cannot be generated yet';
$string['commerce_bundle_fix_components'] = 'Fix the composition';
$string['commerce_bundle_preview_products'] = 'Terminal products';
$string['commerce_bundle_preview_quantity'] = 'Total quantity';
$string['commerce_bundle_preview_entitlements'] = 'Declared rights';
$string['commerce_bundle_preview_depth'] = 'Maximum depth';
$string['commerce_bundle_preview_empty'] = 'This bundle does not contain any terminal product yet.';
$string['commerce_bundle_preview_prices'] = 'Active product prices';
$string['commerce_bundle_preview_rights'] = 'Granted rights';
$string['commerce_bundle_preview_paths'] = 'Composition paths';
$string['commerce_no_active_price'] = 'No active price';
$string['commerce_no_entitlement'] = 'No entitlement defined';
$string['commerce_entitlement_lifetime'] = 'Lifetime';
$string['commerce_back_to_products'] = 'Back to products';

// Phase 7.94E7 - Bundle pricing.
$string['commerce_bundle_pricing_title'] = 'Pricing — {$a}';
$string['commerce_bundle_pricing_eyebrow'] = 'Commercial strategy';
$string['commerce_bundle_pricing_intro'] = 'Choose how the Bundle price is obtained and immediately review the result in each currency.';
$string['commerce_bundle_pricing_method'] = 'Calculation method';
$string['commerce_bundle_pricing_method_help'] = 'Fixed pricing uses the Bundle own price. Component sum adds included product prices. Discount applies a percentage to that sum.';
$string['commerce_bundle_pricing_fixed'] = 'Fixed Bundle price';
$string['commerce_bundle_pricing_sum'] = 'Component price sum';
$string['commerce_bundle_pricing_discount'] = 'Component sum with discount';
$string['commerce_bundle_discount_percent'] = 'Discount (%)';
$string['commerce_bundle_fixed_prices'] = 'Fixed Bundle prices';
$string['commerce_bundle_fixed_prices_help'] = 'Used only by fixed pricing. Leave blank to keep an existing price unchanged.';
$string['commerce_bundle_price_simulation'] = 'Current simulation';
$string['commerce_bundle_final_price'] = 'Final Bundle price';
$string['commerce_bundle_component_total'] = 'Separate value';
$string['commerce_bundle_savings'] = 'Customer saving';

// 7.94E8 - Unified Commerce product manager.
$string['commerce_product_type_course_access'] = 'Course access';
$string['commerce_product_type_digital_download'] = 'Digital product';
$string['commerce_product_type_bundle'] = 'Pack / Bundle';
$string['commerce_product_type_service'] = 'Service';
$string['commerce_product_status_draft'] = 'Draft';
$string['commerce_product_status_active'] = 'Active';
$string['commerce_product_status_inactive'] = 'Inactive';
$string['commerce_product_status_archived'] = 'Archived';
$string['commerce_product_edit_steps'] = 'Product configuration steps';
$string['commerce_product_type_help'] = 'The type may be changed while the product remains a draft. The SKU is a stable technical identifier and cannot be changed after creation.';
$string['commerce_product_description_help'] = 'This default description is fallback content. Customer-facing copy should be entered in the translations below.';
$string['commerce_product_translations_title'] = 'Multilingual content';
$string['commerce_product_translations_help'] = 'Enter the customer-facing name and commercial descriptions for each language.';
$string['commerce_product_short_description'] = 'Short description';
$string['commerce_product_summary'] = 'Product overview';
$string['commerce_prices'] = 'Price';
$string['commerce_translations'] = 'translations';
$string['commerce_components'] = 'components';
$string['commerce_entitlements'] = 'entitlements';
$string['commerce_products_empty'] = 'No products match the selected filters.';
$string['commerce_product_archived'] = 'The product has been archived. It remains available in history and is no longer offered for sale.';
$string['commerce_products_card_description'] = 'Manage the unified catalogue, packs, translations, prices and associated entitlements.';
$string['commerce_entitlement_course_access'] = 'Course {$a->courseid} access — {$a->level}';
$string['commerce_entitlement_course_generic'] = 'Course access: {$a}';
$string['commerce_entitlement_digital_product'] = 'Digital product download #{$a}';
$string['commerce_entitlement_digital_generic'] = 'Digital product: {$a}';
$string['commerce_entitlement_generic'] = '{$a->type}: {$a->resource}';
$string['commerce_entitlement_access_full'] = 'full access';
$string['commerce_entitlement_access_grammar'] = 'grammar access';
$string['commerce_entitlement_access_trial'] = 'trial access';
$string['commerce_bundle_preview_pricing'] = 'Pack pricing';
$string['commerce_bundle_pricing_incomplete'] = 'Pricing is not yet complete for this currency.';

// 7.94E9 - Final certification.
$string['commerce_bundle_phase_certification'] = 'Commerce Products and Bundles certification';

$string['commerce_product_type_unknown'] = 'Other product';
$string['commerce_product_status_unknown'] = 'Unknown status';
$string['commerce_entitlement_course_named'] = 'Access to course “{$a->course}” — {$a->level}';
$string['commerce_entitlement_digital_named'] = 'Access to digital product “{$a}”';
$string['commerce_entitlement_generic_readable'] = '{$a->type}: {$a->resource}';
$string['commerce_entitlement_type_course'] = 'Course access';
$string['commerce_entitlement_type_digital_product'] = 'Digital access';
$string['commerce_entitlement_type_other'] = 'Other entitlement';
$string['commerce_course_fallback'] = 'Course #{$a}';
$string['commerce_digital_product_fallback'] = 'Digital product #{$a}';
$string['commerce_entitlement_access_generic'] = 'standard access';
$string['commerce_product_archive'] = 'Archive';
$string['commerce_bundle_add_currency'] = 'Add another currency';
$string['commerce_bundle_add_currency_help'] = 'Enter any ISO 4217 currency code, such as USD, GBP, CAD or AUD.';

$string['commerce_price'] = 'Price';

$string['commerce_bundle_component_comparison_unavailable'] = 'The Bundle price is active. Component value and customer savings will be available when every component has an active price in this currency.';

$string['commerce_fulfillment_shadow_enabled'] = 'Enable Native fulfillment Shadow';
$string['commerce_fulfillment_shadow_enabled_desc'] = 'Run the Native fulfillment engine in read-only dry-run after Legacy checkout fulfillment and persist comparisons.';
$string['commerce_runtime_mode'] = 'Commerce fulfillment runtime mode';
$string['commerce_runtime_mode_desc'] = 'Select the authoritative fulfillment runtime. Legacy is the safe default; Shadow keeps Legacy authoritative; Native makes the Native engine authoritative.';
$string['commerce_runtime_mode_legacy'] = 'Legacy';
$string['commerce_runtime_mode_shadow'] = 'Shadow';
$string['commerce_runtime_mode_native'] = 'Native';
$string['commerce_runtime_native_fallback_enabled'] = 'Enable automatic Legacy fallback';
$string['commerce_runtime_native_fallback_enabled_desc'] = 'When Native fulfillment throws an exception, immediately execute the Legacy path. Keep enabled during the DEV rollout.';


// Commerce 7.95B1 — shared UX vocabulary.
$string['commerce_vocabulary_product_type_client_course_access'] = 'Course';
$string['commerce_vocabulary_product_type_client_digital_download'] = 'Digital resource';
$string['commerce_vocabulary_product_type_client_bundle'] = 'Pack';
$string['commerce_vocabulary_product_type_client_service'] = 'Service';
$string['commerce_vocabulary_product_type_crm_course_access'] = 'Course access';
$string['commerce_vocabulary_product_type_crm_digital_download'] = 'Digital product';
$string['commerce_vocabulary_product_type_crm_bundle'] = 'Bundle';
$string['commerce_vocabulary_product_type_crm_service'] = 'Service';
$string['commerce_vocabulary_product_type_unknown'] = 'Other product';
$string['commerce_vocabulary_product_status_client_active'] = 'Available';
$string['commerce_vocabulary_product_status_client_draft'] = 'Coming soon';
$string['commerce_vocabulary_product_status_client_inactive'] = 'Unavailable';
$string['commerce_vocabulary_product_status_client_archived'] = 'Unavailable';
$string['commerce_vocabulary_product_status_crm_active'] = 'Active';
$string['commerce_vocabulary_product_status_crm_draft'] = 'Draft';
$string['commerce_vocabulary_product_status_crm_inactive'] = 'Inactive';
$string['commerce_vocabulary_product_status_crm_archived'] = 'Archived';
$string['commerce_vocabulary_product_status_unknown'] = 'Status not provided';
$string['commerce_vocabulary_purchase_status_client_draft'] = 'Draft';
$string['commerce_vocabulary_purchase_status_client_created'] = 'Created';
$string['commerce_vocabulary_purchase_status_client_prepared'] = 'Prepared';
$string['commerce_vocabulary_purchase_status_client_payment_pending'] = 'Payment pending';
$string['commerce_vocabulary_purchase_status_client_authorized'] = 'Authorised';
$string['commerce_vocabulary_purchase_status_client_captured'] = 'Captured';
$string['commerce_vocabulary_purchase_status_client_paid'] = 'Paid';
$string['commerce_vocabulary_purchase_status_client_fulfillment_pending'] = 'Fulfilment pending';
$string['commerce_vocabulary_purchase_status_client_fulfilled'] = 'Fulfilled';
$string['commerce_vocabulary_purchase_status_client_completed'] = 'Completed';
$string['commerce_vocabulary_purchase_status_client_active'] = 'Active';
$string['commerce_vocabulary_purchase_status_client_expired'] = 'Expired';
$string['commerce_vocabulary_purchase_status_client_replaced'] = 'Replaced';
$string['commerce_vocabulary_purchase_status_client_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_purchase_status_client_failed'] = 'Failed';
$string['commerce_vocabulary_purchase_status_client_refunded'] = 'Refunded';
$string['commerce_vocabulary_purchase_status_client_unknown'] = 'Status not provided';
$string['commerce_vocabulary_purchase_status_crm_draft'] = 'Draft';
$string['commerce_vocabulary_purchase_status_crm_created'] = 'Created';
$string['commerce_vocabulary_purchase_status_crm_prepared'] = 'Prepared';
$string['commerce_vocabulary_purchase_status_crm_payment_pending'] = 'Payment pending';
$string['commerce_vocabulary_purchase_status_crm_authorized'] = 'Authorised';
$string['commerce_vocabulary_purchase_status_crm_captured'] = 'Captured';
$string['commerce_vocabulary_purchase_status_crm_paid'] = 'Paid';
$string['commerce_vocabulary_purchase_status_crm_fulfillment_pending'] = 'Fulfilment pending';
$string['commerce_vocabulary_purchase_status_crm_fulfilled'] = 'Fulfilled';
$string['commerce_vocabulary_purchase_status_crm_completed'] = 'Completed';
$string['commerce_vocabulary_purchase_status_crm_active'] = 'Active';
$string['commerce_vocabulary_purchase_status_crm_expired'] = 'Expired';
$string['commerce_vocabulary_purchase_status_crm_replaced'] = 'Replaced';
$string['commerce_vocabulary_purchase_status_crm_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_purchase_status_crm_failed'] = 'Failed';
$string['commerce_vocabulary_purchase_status_crm_refunded'] = 'Refunded';
$string['commerce_vocabulary_purchase_status_crm_unknown'] = 'Status not provided';
$string['commerce_vocabulary_purchase_status_unknown'] = 'Purchase status not provided';
$string['commerce_vocabulary_payment_status_client_created'] = 'Created';
$string['commerce_vocabulary_payment_status_client_requires_action'] = 'Action required';
$string['commerce_vocabulary_payment_status_client_pending'] = 'Pending';
$string['commerce_vocabulary_payment_status_client_authorized'] = 'Authorised';
$string['commerce_vocabulary_payment_status_client_captured'] = 'Captured';
$string['commerce_vocabulary_payment_status_client_paid'] = 'Paid';
$string['commerce_vocabulary_payment_status_client_succeeded'] = 'Succeeded';
$string['commerce_vocabulary_payment_status_client_failed'] = 'Failed';
$string['commerce_vocabulary_payment_status_client_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_payment_status_client_expired'] = 'Expired';
$string['commerce_vocabulary_payment_status_client_refunded'] = 'Refunded';
$string['commerce_vocabulary_payment_status_client_partially_refunded'] = 'Partially refunded';
$string['commerce_vocabulary_payment_status_client_unknown'] = 'Status not provided';
$string['commerce_vocabulary_payment_status_crm_created'] = 'Created';
$string['commerce_vocabulary_payment_status_crm_requires_action'] = 'Action required';
$string['commerce_vocabulary_payment_status_crm_pending'] = 'Pending';
$string['commerce_vocabulary_payment_status_crm_authorized'] = 'Authorised';
$string['commerce_vocabulary_payment_status_crm_captured'] = 'Captured';
$string['commerce_vocabulary_payment_status_crm_paid'] = 'Paid';
$string['commerce_vocabulary_payment_status_crm_succeeded'] = 'Succeeded';
$string['commerce_vocabulary_payment_status_crm_failed'] = 'Failed';
$string['commerce_vocabulary_payment_status_crm_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_payment_status_crm_expired'] = 'Expired';
$string['commerce_vocabulary_payment_status_crm_refunded'] = 'Refunded';
$string['commerce_vocabulary_payment_status_crm_partially_refunded'] = 'Partially refunded';
$string['commerce_vocabulary_payment_status_crm_unknown'] = 'Status not provided';
$string['commerce_vocabulary_payment_status_unknown'] = 'Payment status not provided';
$string['commerce_vocabulary_fulfillment_status_client_pending'] = 'Pending';
$string['commerce_vocabulary_fulfillment_status_client_processing'] = 'Processing';
$string['commerce_vocabulary_fulfillment_status_client_fulfilled'] = 'Fulfilled';
$string['commerce_vocabulary_fulfillment_status_client_completed'] = 'Completed';
$string['commerce_vocabulary_fulfillment_status_client_failed'] = 'Failed';
$string['commerce_vocabulary_fulfillment_status_client_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_fulfillment_status_client_unknown'] = 'Status not provided';
$string['commerce_vocabulary_fulfillment_status_crm_pending'] = 'Pending';
$string['commerce_vocabulary_fulfillment_status_crm_processing'] = 'Processing';
$string['commerce_vocabulary_fulfillment_status_crm_fulfilled'] = 'Fulfilled';
$string['commerce_vocabulary_fulfillment_status_crm_completed'] = 'Completed';
$string['commerce_vocabulary_fulfillment_status_crm_failed'] = 'Failed';
$string['commerce_vocabulary_fulfillment_status_crm_cancelled'] = 'Cancelled';
$string['commerce_vocabulary_fulfillment_status_crm_unknown'] = 'Status not provided';
$string['commerce_vocabulary_fulfillment_status_unknown'] = 'Availability status not provided';
$string['commerce_vocabulary_access_type_client_course'] = 'Course access';
$string['commerce_vocabulary_access_type_client_digital_product'] = 'Resource access';
$string['commerce_vocabulary_access_type_client_subscription'] = 'Subscription';
$string['commerce_vocabulary_access_type_client_bundle'] = 'Pack access';
$string['commerce_vocabulary_access_type_crm_course'] = 'Course access right';
$string['commerce_vocabulary_access_type_crm_digital_product'] = 'Digital access right';
$string['commerce_vocabulary_access_type_crm_subscription'] = 'Subscription right';
$string['commerce_vocabulary_access_type_crm_bundle'] = 'Bundle access right';
$string['commerce_vocabulary_access_type_unknown'] = 'Other access right';

// Commerce 7.95B2-B4 UX foundation.
$string['commerce_products_empty_title'] = 'No products yet';
$string['commerce_products_table_label'] = 'Commerce products list';
$string['commerce_product_eyebrow'] = 'Commerce product';

// Commerce 7.95C4-C6 — Native statistics dashboard.
$string['commerce_statistics_title'] = 'Commerce statistics';
$string['commerce_statistics_description'] = 'Track sales, payments and Commerce operations from Native data.';
$string['commerce_statistics_period'] = 'Period';
$string['commerce_statistics_currency'] = 'Currency';
$string['commerce_statistics_provider'] = 'Payment provider';
$string['commerce_statistics_period_today'] = 'Today';
$string['commerce_statistics_period_7_days'] = 'Last 7 days';
$string['commerce_statistics_period_30_days'] = 'Last 30 days';
$string['commerce_statistics_period_90_days'] = 'Last 90 days';
$string['commerce_statistics_period_year'] = 'Last 12 months';
$string['commerce_statistics_all_currencies'] = 'All currencies';
$string['commerce_statistics_all_providers'] = 'All providers';
$string['commerce_statistics_period_summary'] = 'Analysed period: {$a->from} to {$a->to}. Compared with the previous period of the same length.';
$string['commerce_statistics_empty_title'] = 'No Commerce data';
$string['commerce_statistics_empty_description'] = 'No Native activity was found for the selected filters and period.';
$string['commerce_statistics_payment_health'] = 'Payments and fulfilment';
$string['commerce_statistics_metric_net_paid_minor'] = 'Net revenue';
$string['commerce_statistics_metric_orders'] = 'Orders';
$string['commerce_statistics_metric_average_order_minor'] = 'Average order';
$string['commerce_statistics_metric_customers'] = 'Paying customers';
$string['commerce_statistics_metric_successful_payments'] = 'Successful payments';
$string['commerce_statistics_metric_failed_payments'] = 'Failed payments';
$string['commerce_statistics_metric_refunded_minor'] = 'Refunded amount';
$string['commerce_statistics_metric_pending_fulfillments'] = 'Fulfilments requiring action';
$string['commerce_statistics_open_details'] = 'Open related records';
$string['commerce_statistics_metric_link'] = 'View details for {$a->metric} in {$a->currency}';
$string['commerce_statistics_no_comparison'] = 'Comparison unavailable';
$string['commerce_statistics_comparison_unavailable'] = 'Change cannot be calculated';
$string['commerce_statistics_vs_previous'] = '{$a} compared with the previous period';
$string['commerce_statistics_operational_shortcuts'] = 'Operational shortcuts';
$string['commerce_statistics_open_subscriptions'] = 'Manage subscriptions';
$string['commerce_statistics_open_digital_purchases'] = 'Manage digital purchases';
$string['commerce_statistics_open_products'] = 'Manage products';

$string['commerce_statistics_charts_title'] = 'Trends and breakdowns';
$string['commerce_statistics_chart_revenue'] = 'Revenue trend';
$string['commerce_statistics_chart_orders'] = 'Order trend';
$string['commerce_statistics_chart_top_products'] = 'Top-selling products';
$string['commerce_statistics_chart_payment_health'] = 'Payment health';
$string['commerce_statistics_chart_product_revenue'] = 'Product sales trend';
$string['commerce_statistics_payment_successful'] = 'Successful';
$string['commerce_statistics_payment_failed'] = 'Failed';
$string['commerce_statistics_payment_refunded'] = 'Refunded';
$string['commerce_statistics_accessible_table'] = 'Show data as a table';
$string['commerce_product_statistics_title'] = 'Commercial performance';
$string['commerce_product_statistics_period'] = 'Data for the last 90 days, separated by currency.';
$string['commerce_statistics_table_period'] = 'Period';
$string['commerce_statistics_table_value'] = 'Value';


// 7.95D4-D6 — Unified Commerce sales.
$string['crm_commerce_nav_purchases'] = 'Sales';
$string['commerce_purchases_title'] = 'Sales';
$string['commerce_purchases_description'] = 'Review all Native Commerce sales from one operational workspace.';
$string['commerce_purchases_search'] = 'Search';
$string['commerce_purchases_results'] = 'Matching sales';
$string['commerce_purchases_empty_title'] = 'No sales found';
$string['commerce_purchases_empty'] = 'No Native Commerce sale matches the selected filters.';
$string['commerce_purchases_table_label'] = 'Unified Commerce sales';
$string['commerce_purchase_reference'] = 'Reference';
$string['commerce_purchase_customer'] = 'Customer';
$string['commerce_purchase_products'] = 'Products';
$string['commerce_purchase_amount'] = 'Amount';
$string['commerce_purchase_status'] = 'Status';
$string['commerce_purchase_type'] = 'Sale type';
$string['commerce_purchase_commercial_status'] = 'Commercial status';
$string['commerce_purchase_payment_status'] = 'Payment';
$string['commerce_purchase_fulfillment_status'] = 'Fulfillment';
$string['commerce_purchase_provider'] = 'Provider';
$string['commerce_purchase_view_title'] = 'Sale {$a}';
$string['commerce_purchase_view_description'] = 'Unified Native view of the sale, its payment and fulfillment.';
$string['commerce_purchase_items_count'] = 'Items';
$string['commerce_purchase_summary_section'] = 'Summary';
$string['commerce_purchase_customer_section'] = 'Customer';
$string['commerce_purchase_products_section'] = 'Products';
$string['commerce_purchase_payments_section'] = 'Payments';
$string['commerce_purchase_fulfillments_section'] = 'Fulfillments';
$string['commerce_purchase_diagnostics_section'] = 'Technical diagnostics';
$string['commerce_purchase_product'] = 'Product';
$string['commerce_purchase_quantity'] = 'Quantity';
$string['commerce_purchase_provider_reference'] = 'Provider reference';
$string['commerce_purchase_fulfillment'] = 'Fulfillment';
$string['commerce_purchase_source'] = 'Source';
$string['commerce_purchase_legacy_family'] = 'Legacy family';
$string['commerce_purchase_legacy_id'] = 'Legacy ID';
$string['commerce_purchase_open_customer'] = 'Open customer';
$string['commerce_purchase_open_product'] = 'Open product';
$string['commerce_purchase_no_payments'] = 'No payment attempt is recorded.';
$string['commerce_purchase_no_fulfillments'] = 'No fulfillment operation is recorded.';
$string['commerce_purchase_not_found'] = 'The requested Commerce sale was not found.';
$string['commerce_purchase_commercial_status_pending'] = 'Pending';
$string['commerce_purchase_commercial_status_paid'] = 'Paid';
$string['commerce_purchase_commercial_status_to_fulfill'] = 'Paid, to fulfill';
$string['commerce_purchase_commercial_status_partially_fulfilled'] = 'Partially fulfilled';
$string['commerce_purchase_commercial_status_fulfilled'] = 'Fulfilled';
$string['commerce_purchase_commercial_status_payment_failed'] = 'Payment failed';
$string['commerce_purchase_commercial_status_refunded'] = 'Refunded';
$string['commerce_purchase_commercial_status_cancelled'] = 'Cancelled';
$string['commerce_purchase_commercial_status_replaced'] = 'Replaced';
$string['commerce_purchase_commercial_status_unknown'] = 'Unknown';
$string['commerce_purchase_type_subscription'] = 'Subscription';
$string['commerce_purchase_type_digital'] = 'Digital product';
$string['commerce_purchase_type_bundle'] = 'Bundle';

// 7.95D7-D10 unified purchase actions and compatibility.
$string['commerce_purchase_actions_section'] = 'Actions';
$string['commerce_purchase_retry_fulfillment'] = 'Retry delivery';
$string['commerce_purchase_retry_confirm'] = 'Retry Native fulfillment for this purchase? The operation is idempotent.';
$string['commerce_purchase_retry_success'] = 'Delivery completed successfully.';
$string['commerce_purchase_retry_failed'] = 'Delivery could not be completed. Check the fulfillment details.';
$string['commerce_purchase_internal_note'] = 'Internal note';
$string['commerce_purchase_add_note'] = 'Add note';
$string['commerce_purchase_note_added'] = 'The internal note was added.';
$string['commerce_purchase_destructive_actions_deferred'] = 'Cancellation, replacement and refund remain unavailable until their Native provider-aware commands are certified.';
$string['commerce_purchase_action_not_allowed'] = 'This action is not allowed for the current purchase state.';
$string['commerce_purchase_note_required'] = 'An internal note is required.';
$string['commerce_purchase_note_too_long'] = 'The internal note is too long.';

// 7.95D11-D12 — Unified sales polish and certification.
$string['commerce_purchase_identifier'] = 'Moodle user ID';
$string['commerce_purchase_open_user360'] = 'Open User360';


// 7.95D13 — Unified sales visual and operational completion.
$string['commerce_purchase_open_moodle_profile'] = 'Open Moodle profile';
$string['commerce_purchase_retry_short'] = 'Retry';
$string['commerce_purchase_payment_request'] = 'Payment request';
$string['commerce_purchase_payment_request_attempts'] = 'Attempts: {$a}';
$string['commerce_purchase_payment_request_expires'] = 'Expires: {$a}';
$string['commerce_purchase_fulfillment_type_subscription_enrolment'] = 'Course enrolment';
$string['commerce_purchase_fulfillment_type_course_access'] = 'Course access';
$string['commerce_purchase_fulfillment_type_digital_download'] = 'Digital download';
$string['commerce_purchase_fulfillment_type_digital_product'] = 'Digital product access';
$string['commerce_purchase_payment_status_none'] = 'No payment';
$string['commerce_purchase_payment_status_created'] = 'Created';
$string['commerce_purchase_payment_status_pending'] = 'Pending';
$string['commerce_purchase_payment_status_processing'] = 'Processing';
$string['commerce_purchase_payment_status_paid'] = 'Paid';
$string['commerce_purchase_payment_status_succeeded'] = 'Succeeded';
$string['commerce_purchase_payment_status_completed'] = 'Completed';
$string['commerce_purchase_payment_status_failed'] = 'Failed';
$string['commerce_purchase_payment_status_error'] = 'Error';
$string['commerce_purchase_payment_status_refunded'] = 'Refunded';
$string['commerce_purchase_payment_status_cancelled'] = 'Cancelled';
$string['commerce_purchase_payment_status_canceled'] = 'Cancelled';
$string['commerce_purchase_payment_status_expired'] = 'Expired';
$string['commerce_purchase_fulfillment_status_none'] = 'Not started';
$string['commerce_purchase_fulfillment_status_pending'] = 'Pending';
$string['commerce_purchase_fulfillment_status_processing'] = 'Processing';
$string['commerce_purchase_fulfillment_status_queued'] = 'Queued';
$string['commerce_purchase_fulfillment_status_fulfilled'] = 'Completed';
$string['commerce_purchase_fulfillment_status_completed'] = 'Completed';
$string['commerce_purchase_fulfillment_status_failed'] = 'Failed';
$string['commerce_purchase_fulfillment_status_error'] = 'Error';
$string['commerce_purchase_fulfillment_status_active'] = 'Active';
$string['commerce_purchase_type_course_access'] = 'Course access';
$string['commerce_purchase_type_digital_download'] = 'Digital product';

// 7.95D14 purchase polish.
$string['commerce_purchase_payment_request_open'] = 'View request #{$a}';
$string['commerce_purchase_payment_request_family'] = 'Family';
$string['commerce_purchase_payment_requests_section'] = 'Related payment requests';
$string['commerce_purchase_payment_request_summary'] = '{$a->family} — request #{$a->id}';
$string['commerce_purchase_payment_request_field_userid'] = 'Userid';
$string['commerce_purchase_payment_request_field_email'] = 'Email';
$string['commerce_purchase_payment_request_field_firstname'] = 'Firstname';
$string['commerce_purchase_payment_request_field_lastname'] = 'Lastname';
$string['commerce_purchase_payment_request_field_price'] = 'Price';
$string['commerce_purchase_payment_request_field_sessionid'] = 'Sessionid';
$string['commerce_purchase_payment_request_field_transactionid'] = 'Transactionid';
$string['commerce_purchase_payment_request_field_payment_link'] = 'Payment Link';
$string['commerce_purchase_payment_request_field_creation_date'] = 'Creation Date';
$string['commerce_purchase_payment_request_field_last_update'] = 'Last Update';
$string['commerce_purchase_payment_request_field_payment_date'] = 'Payment Date';
$string['commerce_purchase_payment_request_field_expiration_date'] = 'Expiration Date';
$string['commerce_purchase_payment_request_field_attempts'] = 'Attempts';
$string['commerce_purchase_payment_request_field_last_attempt'] = 'Last Attempt';
$string['commerce_purchase_payment_request_field_last_error'] = 'Last Error';
$string['commerce_purchase_payment_request_field_locked_list_price'] = 'Locked List Price';
$string['commerce_purchase_payment_request_field_locked_discount_percent'] = 'Locked Discount Percent';
$string['commerce_purchase_payment_request_field_locked_discount_amount'] = 'Locked Discount Amount';
$string['commerce_purchase_payment_request_field_locked_discount_reason'] = 'Locked Discount Reason';
$string['commerce_purchase_payment_request_field_locked_final_price'] = 'Locked Final Price';
$string['commerce_purchase_payment_request_field_locked_at'] = 'Locked At';
$string['commerce_purchase_payment_request_field_created_ip'] = 'Created Ip';
$string['commerce_purchase_payment_request_field_created_useragent'] = 'Created Useragent';
$string['commerce_purchase_payment_request_field_accept_language'] = 'Accept Language';
$string['commerce_purchase_payment_request_field_http_referer'] = 'Http Referer';
$string['commerce_purchase_payment_request_field_response_json'] = 'Response Json';
$string['commerce_purchase_payment_request_field_emailsent'] = 'Emailsent';
$string['commerce_purchase_payment_request_field_planid'] = 'Planid';
$string['commerce_purchase_payment_request_field_phone'] = 'Phone';
$string['commerce_purchase_payment_request_field_phone_country'] = 'Phone Country';
$string['commerce_purchase_payment_request_field_subscriptionid'] = 'Subscriptionid';
$string['commerce_purchase_payment_request_field_retry_expires'] = 'Retry Expires';
$string['commerce_purchase_payment_request_field_reminder_stage'] = 'Reminder Stage';
$string['commerce_purchase_payment_request_field_reminder1_at'] = 'Reminder1 At';
$string['commerce_purchase_payment_request_field_reminder2_at'] = 'Reminder2 At';
$string['commerce_purchase_payment_request_field_login_token_expires'] = 'Login Token Expires';
$string['commerce_purchase_payment_request_field_operation'] = 'Operation';
$string['commerce_purchase_payment_request_field_reference_subscription_id'] = 'Reference Subscription Id';
$string['commerce_purchase_payment_request_field_productid'] = 'Productid';
$string['commerce_purchase_payment_request_field_download_token_expires'] = 'Download Token Expires';
$string['commerce_purchase_payment_request_field_receipt_sent'] = 'Receipt Sent';
$string['commerce_purchase_payment_request_field_buyer_lang'] = 'Buyer Lang';

// 7.95E — Unified commercial catalogue.
$string['commerce_catalog_title'] = 'Commercial catalogue';
$string['commerce_catalog_description'] = 'View Native products, legacy plans and digital products in one workspace.';
$string['commerce_catalog_product_eyebrow'] = 'Catalogue product';
$string['commerce_catalog_table_label'] = 'Unified list of commercial products';
$string['commerce_catalog_origin'] = 'Origin';
$string['commerce_catalog_editorial'] = 'Editorial status';
$string['commerce_catalog_visibility'] = 'Visibility';
$string['commerce_catalog_availability'] = 'Availability';
$string['commerce_catalog_technical'] = 'Technical state';
$string['commerce_catalog_content'] = 'Delivered content';
$string['commerce_catalog_compatibility'] = 'Legacy compatibility';
$string['commerce_catalog_available_from'] = 'Available from';
$string['commerce_catalog_available_until'] = 'Available until';
$string['commerce_catalog_fulfillments_count'] = '{$a} fulfillment(s)';
$string['commerce_catalog_product_not_found'] = 'The requested product was not found in the unified catalogue.';
$string['commerce_catalog_editorial_draft'] = 'Draft';
$string['commerce_catalog_editorial_published'] = 'Published';
$string['commerce_catalog_editorial_archived'] = 'Archived';
$string['commerce_catalog_visibility_visible'] = 'Visible';
$string['commerce_catalog_visibility_hidden'] = 'Hidden';
$string['commerce_catalog_visibility_direct_link'] = 'Direct link';
$string['commerce_catalog_availability_on_sale'] = 'On sale';
$string['commerce_catalog_availability_upcoming'] = 'Upcoming';
$string['commerce_catalog_availability_unavailable'] = 'Unavailable';
$string['commerce_catalog_availability_ended'] = 'Sale ended';
$string['commerce_catalog_technical_valid'] = 'Valid';
$string['commerce_catalog_technical_incomplete'] = 'Incomplete configuration';
$string['commerce_catalog_technical_error'] = 'Configuration error';
$string['commerce_catalog_origin_native'] = 'Native';
$string['commerce_catalog_origin_legacy_plan'] = 'Legacy plan';
$string['commerce_catalog_origin_legacy_digital'] = 'Legacy digital product';
$string['commerce_catalog_type_course_access'] = 'Course access';
$string['commerce_catalog_type_digital_download'] = 'Digital product';
$string['commerce_catalog_type_bundle'] = 'Bundle';
$string['commerce_catalog_type_service'] = 'Service';
$string['commerce_catalog_fulfillment_course'] = 'Access to course “{$a}”';
$string['commerce_catalog_fulfillment_download'] = 'Digital download';
$string['commerce_catalog_fulfillment_course_enrolment'] = 'Course enrolment';
$string['commerce_catalog_fulfillment_digital_download'] = 'Digital download';

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
$string['commerce_edit_in_source'] = 'Edit in source';
$string['commerce_product_step_bundle_pricing'] = 'Bundle pricing rules';
$string['commerce_product_step_assets'] = 'Media and files';
$string['commerce_product_assets_title'] = 'Media and files';
$string['commerce_product_assets_help'] = 'Manage the product cover image and, for digital products, the desktop and mobile files delivered to customers.';
$string['commerce_cover_image'] = 'Cover image';
$string['commerce_digital_files'] = 'Delivered digital files';
$string['commerce_desktop_file'] = 'Desktop version';
$string['commerce_mobile_file'] = 'Mobile version';
$string['commerce_digital_files_need_mapping'] = 'This Native digital product must be mapped to a legacy digital product to manage its files during this phase.';
$string['commerce_invalid_asset_type'] = 'The uploaded file type is not allowed.';
$string['commerce_product_prices_guided_help'] = 'Choose currencies and providers from the lists. Add as many price rows as needed.';
$string['commerce_add_price_row'] = 'Add a price';
$string['commerce_invalid_price'] = 'The entered amount is invalid.';
$string['commerce_product_fulfillments_guided_help'] = 'Define what the customer receives using guided lists. Two rows are shown initially and more can be added.';
$string['commerce_add_fulfillment_row'] = 'Add a fulfillment';
$string['commerce_incomplete_fulfillment_row'] = 'Fulfillment row {$a} is incomplete.';
$string['commerce_unknown_fulfillment_type'] = 'The selected fulfillment type is not recognised.';
$string['commerce_invalid_fulfillment_resource'] = 'The selected fulfillment resource does not exist.';
$string['commerce_fulfillment_course_access'] = 'Moodle course access';
$string['commerce_fulfillment_course_enrolment'] = 'Moodle course enrolment';
$string['commerce_fulfillment_digital_download'] = 'Digital file download';
$string['commerce_fulfillment_digital_product'] = 'Digital product access';
$string['commerce_fulfillment_custom'] = 'Custom fulfillment';
$string['commerce_resource_course'] = 'Course: {$a}';
$string['commerce_resource_digital'] = 'Digital product: {$a}';
$string['commerce_duration_lifetime'] = 'Lifetime access';
$string['commerce_duration_30_days'] = '30 days';
$string['commerce_duration_90_days'] = '90 days';
$string['commerce_duration_365_days'] = '365 days';
$string['commerce_missing_course'] = 'Missing course (#{$a})';
$string['commerce_missing_digital_product'] = 'Missing digital product (#{$a})';
$string['commerce_product_diagnostic'] = 'Product diagnostic';
$string['commerce_validation_no_active_price'] = 'No active price is configured.';
$string['commerce_validation_no_fulfillment'] = 'No fulfillment is configured.';
$string['commerce_validation_hidden'] = 'The product is hidden from the shop.';
$string['commerce_validation_not_on_sale'] = 'The product is not currently on sale.';
$string['commerce_technical_reference'] = 'Technical reference';
$string['commerce_status_publication'] = 'Publication';
$string['commerce_status_sale'] = 'Sale';
$string['commerce_status_visibility'] = 'Visibility';
$string['commerce_status_configuration'] = 'Configuration';
$string['settings:commerce_catalog_heading'] = 'Commerce catalogue';
$string['settings:commerce_catalog_heading_desc'] = 'Shared catalogue administration settings.';
$string['settings:commerce_enabled_currencies'] = 'Enabled currencies';
$string['settings:commerce_enabled_currencies_desc'] = 'Comma-separated ISO codes. Supported values: EUR, RUB, USD, GBP, CHF, CAD and JPY.';

$string['commerce_price_deleted'] = 'Price deleted.';
$string['commerce_price_currency_duplicate'] = 'A price already exists for this currency. A product can have only one commercial price per currency.';
$string['commerce_prices_unique_currency_help'] = 'Only one commercial price is allowed per currency. Edit the existing row instead of creating another one.';
$string['commerce_add_price'] = 'Add a price';
$string['commerce_plans_title'] = 'Access plans';
$string['commerce_plan_add'] = 'Add a plan';
$string['commerce_plan_edit'] = 'Edit plan';
$string['commerce_scopes_title'] = 'Access scopes';
$string['commerce_scope_add'] = 'Add an access scope';
$string['commerce_scope_edit'] = 'Edit access scope';
$string['commerce_scope_plans_count'] = 'Associated plans';
$string['commerce_scope_used_by_plans'] = 'Plans using this scope';

$string['commerce_catalog_lifecycle_active'] = 'Active';
$string['commerce_catalog_lifecycle_inactive'] = 'Inactive';
$string['commerce_catalog_lifecycle_archived'] = 'Archived';
$string['commerce_product_activate'] = 'Activate';
$string['commerce_product_deactivate'] = 'Deactivate';
$string['commerce_product_activated'] = 'The product is now active.';
$string['commerce_product_deactivated'] = 'The product is now inactive.';
$string['commerce_product_archived'] = 'The product has been archived.';
$string['commerce_product_status_managed_help'] = 'Status is managed from the product list. Incomplete configuration prevents activation.';
$string['commerce_validation_missing_plan'] = 'No Plan is linked to this product.';
$string['commerce_validation_missing_scope'] = 'The Plan has no Access Scope.';
$string['commerce_validation_empty_scope'] = 'The Access Scope contains no courses.';
$string['commerce_validation_missing_digital'] = 'No digital product is linked to this product.';
$string['commerce_validation_missing_digital_file'] = 'The digital product has neither a desktop nor a mobile file.';
$string['commerce_validation_bundle_too_small'] = 'The bundle must contain at least two components.';
$string['commerce_validation_inactive_bundle_component'] = 'All bundle components must be active.';
$string['commerce_edit_digital_source'] = 'Edit digital files';
$string['commerce_prices_catalogue_help'] = 'Only one commercial price can be defined per currency. The payment provider is selected by checkout; existing provider metadata is preserved in the background.';
$string['commerce_scope_plans_count'] = 'Number of associated Plans';
$string['commerce_scope_delete_blocked'] = 'This access scope cannot be deleted: {$a} Plan(s) still use it.';
$string['commerce_scope_deleted'] = 'The access scope has been deleted.';
$string['commerce_plan_current_subscriptions'] = 'Active or queued subscriptions';
$string['commerce_plan_delete_blocked'] = 'This Plan cannot be deleted: {$a} active or queued subscription(s) are attached to it.';
$string['commerce_plan_deleted'] = 'The Plan has been deleted.';

// 7.95E19B.
$string['commerce_plan_toggle_help'] = 'Enable or disable this Plan';
$string['commerce_plan_business_information'] = 'Plan information';
$string['commerce_technical_information'] = 'Technical information';
$string['commerce_date_created'] = 'Creation date';
$string['commerce_date_modified'] = 'Last modified';
$string['commerce_cover_error_maxbytes'] = 'The cover could not be saved: the file exceeds the maximum size of {$a}.';
$string['commerce_cover_error_upload'] = 'The cover could not be saved. Check its format and try again.';
$string['commerce_product_back_to_view'] = 'Product page';
$string['commerce_internal_id'] = 'Internal identifier';
$string['commerce_plan_entitlements_explanation'] = 'These access rights are the Plan\'s historical execution rules (course, role, group and access level). They complement the Plan → Access Scope business view without recreating editable Fulfillments.';
$string['commerce_plan_upgrades_explanation'] = 'These rules define the permitted transitions between this Plan and other Plans, together with the pricing method used.';
$string['commerce_manage_entitlements'] = 'Manage access rights';
$string['commerce_manage_upgrades'] = 'Manage upgrade rules';
$string['commerce_back_to_plan'] = 'Back to Plan details';
$string['commerce_plan_upgrades_for'] = 'Upgrade rules for Plan: {$a}';

$string['commerce_purchase_status_overview'] = 'Purchase status';
$string['commerce_purchase_dimension_payment'] = 'Payment';
$string['commerce_purchase_dimension_order'] = 'Order';
$string['commerce_purchase_dimension_delivery'] = 'Delivery';
$string['commerce_purchase_dimension_access'] = 'Access';
$string['commerce_purchase_payment_not_required'] = 'No payment required';
$string['commerce_purchase_fulfillment_status_not_started'] = 'Not started';
$string['commerce_purchase_access_status_active'] = 'Active';
$string['commerce_purchase_access_status_pending'] = 'Pending';
$string['commerce_purchase_access_status_blocked'] = 'Blocked';

$string['commerce_purchase_order_status_completed'] = 'Completed';

// 7.95E19D final fulfillment distinction.
$string['commerce_purchase_start_fulfillment'] = 'Start fulfillment';
$string['commerce_purchase_start_fulfillment_confirm'] = 'Start the initial fulfillment for this purchase now?';
$string['commerce_purchase_fulfillment_process_success'] = 'Fulfillment completed successfully.';
$string['commerce_purchase_fulfillment_missing_grants'] = 'Fulfillment cannot start because no Native entitlement grant is recorded for this purchase. This is probably historical or incomplete data; no operation was created to avoid granting incorrect access.';

// Commerce 7.95E19E fix.
$string['admin_event_commerce_purchase_fulfillment_retried'] = 'Purchase fulfillment retried';
$string['admin_event_commerce_purchase_note_added'] = 'Note added to purchase';
$string['admin_event_commerce_purchase_fulfillment_closed_without_delivery'] = 'Purchase closed without fulfillment';
$string['commerce_purchase_close_without_fulfillment'] = 'Close without fulfillment';
$string['commerce_purchase_close_without_fulfillment_confirm'] = 'Confirm closure without fulfillment';
$string['commerce_purchase_close_without_fulfillment_confirm_text'] = 'This purchase will be marked as closed without fulfillment. No access will be created or removed.';
$string['commerce_purchase_closed_without_fulfillment_success'] = 'The purchase was closed without fulfillment. No access was created.';
$string['commerce_purchase_closed_without_fulfillment_notice'] = 'Fulfillment closed without delivery: no access was created.';

$string['commerce_statistics_products_title'] = 'Product statistics';
$string['commerce_statistics_products_description'] = 'Revenue only includes orders with a successful payment. Free orders are counted separately, and currencies are never converted or combined.';
$string['commerce_statistics_products_empty'] = 'No product sales match the selected period and filters.';
$string['commerce_statistics_products_table_label'] = 'Product commercial statistics in {$a}';
$string['commerce_statistics_product'] = 'Product';
$string['commerce_statistics_product_orders'] = 'Orders';
$string['commerce_statistics_product_paid_orders'] = 'Paid';
$string['commerce_statistics_product_free_orders'] = 'Free';
$string['commerce_statistics_product_quantity'] = 'Quantity';
$string['commerce_statistics_product_revenue'] = 'Collected revenue';

$string['commerce_product_statistics_empty'] = 'No sales were recorded for this product during this period.';

$string['commerce_digital_file_unavailable'] = 'The requested digital file is not available.';

// Commerce 7.95E21 fix: product performance filters.
$string['commerce_statistics_period_label'] = 'Period';
$string['commerce_statistics_period_180_days'] = 'Last 6 months';
$string['commerce_statistics_period_365_days'] = 'Last 12 months';
$string['commerce_statistics_period_all_time'] = 'All time';

// 7.95E21 statistics refinements.
$string['commerce_statistics_chart_mode'] = 'Revenue display';
$string['commerce_statistics_chart_mode_instant'] = 'Revenue by period';
$string['commerce_statistics_chart_mode_cumulative'] = 'Cumulative revenue';
$string['commerce_statistics_chart_revenue_cumulative'] = 'Cumulative revenue';
$string['commerce_statistics_chart_product_revenue_cumulative'] = 'Cumulative product revenue';
$string['commerce_statistics_chart_product_orders'] = 'Product sales';
$string['commerce_statistics_product_failed_payments'] = 'Failed payments';

// Commerce 7.95F2 — unified storefront.
$string['commerce_storefront_title'] = 'CampusFR Store';
$string['commerce_storefront_intro'] = 'Courses, digital resources and bundles: discover everything you need to progress in French.';
$string['commerce_storefront_search_placeholder'] = 'Search for a course, resource or bundle…';
$string['commerce_storefront_filter_type'] = 'Product type';
$string['commerce_storefront_buy_now'] = 'Buy now';
$string['commerce_storefront_discover'] = 'Discover';
$string['commerce_storefront_empty_title'] = 'No products found';
$string['commerce_storefront_empty'] = 'Change the filters or currency to display other products.';
$string['commerce_storefront_result_count'] = '{$a} product(s)';
$string['commerce_storefront_product_not_found'] = 'This product is not available in the store.';
$string['commerce_storefront_back'] = 'Back to store';
$string['commerce_storefront_detail_scaffold_notice'] = 'This first product page uses the generic Storefront foundation. Phase F3 will enable fully customised editorial compositions.';

// Capabilities.
$string['subscriptions:view_dashboard'] = 'View the subscriptions dashboard';
$string['subscriptions:view_users'] = 'View users';
$string['subscriptions:manage_users'] = 'Manage users';
$string['subscriptions:manage_subscriptions'] = 'Manage subscriptions';
$string['subscriptions:view_digital'] = 'View digital products';
$string['subscriptions:manage_digital'] = 'Manage digital products';
$string['subscriptions:view_payments'] = 'View payments';
$string['subscriptions:view_statistics'] = 'View commerce statistics';
$string['subscriptions:view_inbox'] = 'View the CRM inbox';
$string['subscriptions:manage_inbox'] = 'Manage the CRM inbox';
$string['subscriptions:manage_configuration'] = 'Manage Commerce configuration';
$string['subscriptions:use_inbox_ai'] = 'Use AI features in the CRM inbox';
$string['subscriptions:use_crm_assistant_ai'] = 'Use the CRM AI assistant';
$string['subscriptions:manage_crm_admin_tools'] = 'Manage CRM administration tools';

// Commerce 7.95F3 — customisable product pages.
$string['commerce_storefront_components_title'] = 'Included in this product';
$string['commerce_storefront_faq_title'] = 'Frequently asked questions';

// Commerce 7.95F4 — Storefront page editor.
$string['commerce_product_step_storefront'] = 'Storefront page';
$string['commerce_storefront_editor_title'] = 'Product presentation page';
$string['commerce_storefront_editor_intro'] = 'Compose the editorial page shown to customers. Prices and purchase actions remain controlled by Commerce.';
$string['commerce_storefront_preview'] = 'Preview page';
$string['commerce_storefront_layout_title'] = 'Page presentation';
$string['commerce_storefront_template'] = 'Page template';
$string['commerce_storefront_template_default'] = 'Standard';
$string['commerce_storefront_template_editorial'] = 'Editorial';
$string['commerce_storefront_template_immersive'] = 'Immersive';
$string['commerce_storefront_theme'] = 'Theme key';
$string['commerce_storefront_theme_help'] = 'Optional technical key used for product-specific styling, for example a1-premium.';
$string['commerce_storefront_section_empty'] = 'Unused section';
$string['commerce_storefront_section_rich_text'] = 'Rich text';
$string['commerce_storefront_section_features'] = 'Feature cards';
$string['commerce_storefront_section_media'] = 'Image or media';
$string['commerce_storefront_section_testimonial'] = 'Testimonial';
$string['commerce_storefront_section_faq'] = 'Frequently asked questions';
$string['commerce_storefront_section_cta'] = 'Call to action';
$string['commerce_storefront_section_components'] = 'Bundle components';
$string['commerce_storefront_section_number'] = 'Section {$a}';
$string['commerce_storefront_section_type'] = 'Section type';
$string['commerce_storefront_section_title'] = 'Title';
$string['commerce_storefront_section_subtitle'] = 'Subtitle';
$string['commerce_storefront_section_content'] = 'Main content';
$string['commerce_storefront_section_content_help'] = 'Used by rich text, media captions, testimonials and call-to-action sections. HTML is supported.';
$string['commerce_storefront_section_auxiliary'] = 'URL or author';
$string['commerce_storefront_section_auxiliary_help'] = 'For media, enter the image URL. For a testimonial, enter the author.';
$string['commerce_storefront_section_alt'] = 'Alternative text';
$string['commerce_storefront_section_items'] = 'Cards or questions';
$string['commerce_storefront_section_items_help'] = 'One item per line using: title ||| content. For FAQs, use: question ||| answer.';

$string['settings:storefront_header'] = 'Unified storefront';
$string['settings:storefront_header_desc'] = 'Progressively switch historical public catalogue entry points to the unified Commerce storefront.';
$string['settings:storefront_enabled'] = 'Enable the unified storefront redirect';
$string['settings:storefront_enabled_desc'] = 'Redirect the historical course catalogue entry point to the unified storefront. Embedded displays and direct historical plan links remain unchanged.';

$string['commerce_storefront_merchandising_title'] = 'Commercial merchandising';
$string['commerce_storefront_merchandising_intro'] = 'Control product order, visibility and marketing signals shown in the storefront.';
$string['commerce_storefront_featured_product'] = 'Feature this product';
$string['commerce_storefront_display_order'] = 'Display order';
$string['commerce_storefront_display_order_help'] = 'Featured products appear first, followed by the others in ascending order. The default value is 1000.';
$string['commerce_storefront_badges'] = 'Marketing badges';
$string['commerce_storefront_badge_new'] = 'New';
$string['commerce_storefront_badge_bestseller'] = 'Best seller';
$string['commerce_storefront_badge_popular'] = 'Most popular';
$string['commerce_storefront_badge_limited_offer'] = 'Limited offer';
$string['commerce_storefront_badge_gustave_choice'] = "Gustave's choice";
$string['commerce_storefront_badge_premium'] = 'Premium';
$string['commerce_storefront_badge_lifetime_access'] = 'Lifetime access';
$string['commerce_storefront_badge_complete_course'] = 'Complete course';
$string['commerce_storefront_badge_promotion'] = 'Sale';
$string['commerce_storefront_featured'] = 'Featured product';
$string['commerce_storefront_promotions_title'] = 'Comparison prices and promotions';
$string['commerce_storefront_promotions_help'] = 'The active Native price remains the amount actually charged. Enter a higher comparison price here to display a struck-through price. Dates are optional.';
$string['commerce_storefront_compare_price'] = 'Comparison price';
$string['commerce_storefront_promotion_start'] = 'Start';
$string['commerce_storefront_promotion_end'] = 'End';
$string['commerce_storefront_discount_percentage'] = '-{$a}%';
$string['commerce_storefront_promotion_until'] = 'Offer valid until {$a}';


// 7.95F6B — Storefront customer experience.
$string['commerce_storefront_group_auto'] = 'Automatic (based on product type)';
$string['commerce_storefront_group_courses'] = 'Courses';
$string['commerce_storefront_group_resources'] = 'Resources';
$string['commerce_storefront_group_bundles'] = 'Packs';
$string['commerce_storefront_group_courses_intro'] = 'Structured learning paths to progress in French.';
$string['commerce_storefront_group_resources_intro'] = 'Practical resources to train and revise.';
$string['commerce_storefront_group_bundles_intro'] = 'Combined offers designed to give you more value.';
$string['commerce_storefront_owned'] = 'Already yours';
$string['commerce_storefront_access_course'] = 'Access the course';
$string['commerce_storefront_access_purchase'] = 'View my purchase';
$string['commerce_storefront_trust_secure_payment'] = 'Secure payment';
$string['commerce_storefront_trust_immediate_access'] = 'Immediate access';
$string['commerce_storefront_trust_support'] = 'CampusFR support';
$string['commerce_storefront_trust_lifetime_access'] = 'Lifetime access';
$string['commerce_storefront_experience_title'] = 'Customer experience';
$string['commerce_storefront_experience_intro'] = 'Choose the catalogue group, reassurance items and concise product facts.';
$string['commerce_storefront_group'] = 'Catalogue group';
$string['commerce_storefront_trust_title'] = 'Reassurance items';
$string['commerce_storefront_quickfacts'] = 'Quick facts';
$string['commerce_storefront_quickfacts_help'] = 'One fact per line: value ||| label. Example: 82 ||| videos. Up to 6 facts are displayed.';

$string['commerce_product_technical_name'] = 'English technical name';
$string['commerce_product_sku_generated_help'] = 'The SKU will be generated automatically from the type and English technical name. Public names can then be overridden by translations.';
$string['commerce_product_sku_immutable_help'] = 'Immutable technical reference generated when the product is created.';
$string['commerce_access_scope_no_plan'] = 'No linked Legacy plan';
$string['commerce_access_scope_plan_without_scope'] = 'without access scope';
$string['commerce_access_scope_link_plan'] = 'Linked Legacy plan';
$string['commerce_access_scope_link_plan_help'] = 'This link lets the Native product reuse the existing access scope. A plan can only be linked to one Native product.';
$string['commerce_storefront_recommendations_title'] = 'Complete your learning path';
$string['commerce_storefront_recommendations_help'] = 'One Native SKU per line, up to four recommendations. Products already owned are hidden.';

$string['commerce_access_scope_mapping_conflict'] = 'This plan is already linked to another Native product. Review the link before transferring it.';
$string['commerce_access_scope_already_linked_to'] = 'already linked to {$a}';
$string['commerce_access_scope_transfer_warning'] = 'This plan is linked elsewhere. Saving again explicitly transfers the mapping to this product.';
$string['commerce_storefront_edit_language'] = 'Editorial content language';
$string['commerce_storefront_edit_language_help'] = 'Sections and quick facts are stored separately for this language; commercial structure remains shared.';
$string['commerce_product_lifecycle_title'] = 'Product lifecycle';
$string['commerce_product_lifecycle_intro'] = 'Archive a product to remove it from sale. Permanent deletion is intended for test-product cleanup.';
$string['commerce_product_archive_title'] = 'Archive product';
$string['commerce_product_archive_action'] = 'Archive';
$string['commerce_product_delete_title'] = 'Delete permanently';
$string['commerce_product_delete_safe_help'] = 'No sale or grant is attached; deletion is allowed.';
$string['commerce_product_delete_blocked_help'] = 'Sales or grants exist. Forced destruction is disabled by default and must be explicitly enabled in config.php.';
$string['commerce_product_delete_action'] = 'Delete product';
$string['commerce_product_force_delete_action'] = 'Destroy product and sales';
$string['commerce_product_admin_password'] = 'Administrator password';
$string['commerce_product_delete_confirmation'] = 'Type SUPPRIMER to confirm';
$string['commerce_product_force_delete_disabled'] = 'Forced product destruction is disabled. Enable $CFG->local_subscriptions_allow_destructive_product_delete only in a controlled environment.';
$string['commerce_product_force_delete_confirmation_failed'] = 'The password or confirmation text is incorrect.';
$string['commerce_product_deleted'] = 'Product deleted.';

$string['commerce_access_scope_f6e_help'] = 'Commercial access scope and the canonical migration mapping are now configured separately.';
$string['commerce_access_scope_shared_title'] = 'Shared access scope';
$string['commerce_access_scope_shared_help'] = 'Several Native products may reuse the same access scope. This selection does not change the Legacy migration mapping.';
$string['commerce_access_scope_source_plan'] = 'Scope source plan';
$string['commerce_access_scope_no_scope'] = 'No access scope';
$string['commerce_access_scope_canonical_title'] = 'Canonical Legacy → Native mapping';
$string['commerce_access_scope_canonical_help'] = 'Reserved for compatibility and the production backfill: one Legacy plan may have only one canonical Native product.';
$string['commerce_access_scope_canonical_plan'] = 'Canonical Legacy plan';
$string['commerce_access_scope_no_canonical_plan'] = 'No canonical mapping';
$string['commerce_access_scope_canonical_conflict'] = 'This Legacy plan already has a canonical Native product. Reuse its shared scope without transferring the mapping.';

// 7.95F6F — final UX and product lifecycle.
$string['settings:commerce_security_header'] = 'Commerce security';
$string['settings:commerce_security_header_desc'] = 'Security settings for sensitive Native catalogue operations.';
$string['settings:commerce_allow_destructive_product_delete'] = 'Allow destructive product deletion';
$string['settings:commerce_allow_destructive_product_delete_desc'] = 'Allows an administrator to delete a product with related sales or grants after reinforced confirmation. Keep this disabled in production.';
$string['commerce_product_restored'] = 'The product was restored as a draft.';
$string['commerce_product_restore_title'] = 'Restore product';
$string['commerce_product_restore_help'] = 'The archived product will be restored as a draft so it can be reviewed before publication.';
$string['commerce_product_restore_action'] = 'Restore as draft';
$string['commerce_product_archive_help'] = 'The product will disappear from the storefront and will no longer be purchasable. Its history will be retained.';
$string['commerce_product_back_to_editor'] = 'Back to product';
$string['commerce_product_dependencies_title'] = 'Related data and history';
$string['commerce_product_dependency_prices'] = 'Prices';
$string['commerce_product_dependency_translations'] = 'Translations';
$string['commerce_product_dependency_components'] = 'Bundle components';
$string['commerce_product_dependency_entitlements'] = 'Configured entitlements';
$string['commerce_product_dependency_mappings'] = 'Legacy mappings';
$string['commerce_product_dependency_native_purchase_items'] = 'Native purchase items';
$string['commerce_product_dependency_native_purchases'] = 'Native purchases';
$string['commerce_product_dependency_legacy_plan_sales'] = 'Legacy plan sales';
$string['commerce_product_dependency_legacy_digital_sales'] = 'Legacy digital sales';
$string['commerce_product_dependency_grants'] = 'Granted rights';
$string['commerce_product_delete_checkbox'] = 'I confirm that I want to permanently delete this product.';
$string['commerce_product_delete_confirmation_failed'] = 'The confirmation or administrator password is incorrect.';
$string['commerce_product_force_delete_confirmation_failed'] = 'For destructive deletion, enter SUPPRIMER exactly.';
$string['commerce_product_force_delete_disabled_help'] = 'Destructive deletion is disabled in Commerce settings. This product cannot be deleted while history exists.';

$string['commerce_catalog_origin_legacy_only'] = 'Legacy only';
$string['commerce_catalog_origin_native_short'] = 'NATIVE';
$string['commerce_catalog_origin_legacy_short'] = 'LEGACY';
$string['commerce_catalog_open_legacy_plan'] = 'Open historical plan';
$string['commerce_catalog_open_legacy_digital'] = 'Open historical digital product';
// 7.95G4-G5 — Storefront cart UI and cart page.
$string['commerce_cart_title'] = 'Your cart';
$string['commerce_cart_add'] = 'Add to cart';
$string['commerce_cart_view'] = 'View cart';
$string['commerce_cart_already_owned'] = 'Already purchased';
$string['commerce_cart_empty_title'] = 'Your cart is empty';
$string['commerce_cart_empty_text'] = 'Explore the CampusFR shop and add the courses or resources that suit you.';
$string['commerce_cart_continue_shopping'] = 'Continue shopping';
$string['commerce_cart_checkout'] = 'Review cart';
$string['commerce_cart_quantity'] = 'Quantity';
$string['commerce_cart_unit_price'] = 'Unit price';
$string['commerce_cart_subtotal'] = 'Subtotal';
$string['commerce_cart_discount'] = 'Discount';
$string['commerce_cart_tax'] = 'Tax';
$string['commerce_cart_total'] = 'Total';
$string['commerce_cart_remove'] = 'Remove';
$string['commerce_cart_update'] = 'Update';
$string['commerce_cart_message_already_owned'] = 'You already own this product.';
$string['commerce_cart_message_already_in_cart'] = 'This product is already in your cart.';
$string['commerce_cart_message_item_not_found'] = 'This cart item could not be found.';
$string['commerce_cart_message_add_success'] = 'Product added to your cart.';
$string['commerce_cart_message_remove_success'] = 'Product removed from your cart.';
$string['commerce_cart_message_update_success'] = 'Quantity updated.';
$string['commerce_cart_message_clear_success'] = 'Cart cleared.';
$string['commerce_cart_message_unchanged'] = 'Your cart was not changed.';
$string['commerce_cart_message_error'] = 'The cart could not be updated.';

// 7.95G6 — Cart UX and polish.
$string['commerce_cart_total_ttc'] = 'Total including tax';
$string['commerce_cart_view_product'] = 'View product page';
$string['commerce_cart_payment_secure'] = 'Secure payment';
$string['commerce_cart_instant_access'] = 'Immediate access after payment';

// 7.95G7C-G7D — Cart promotions.
$string['commerce_cart_promo_code'] = 'Promo code';
$string['commerce_cart_promo_placeholder'] = 'Enter your code';
$string['commerce_cart_promo_apply'] = 'Apply';
$string['commerce_cart_promo_remove'] = 'Remove';
$string['commerce_cart_message_promotion_code_saved'] = 'Promo code saved.';
$string['commerce_cart_message_promotion_removed'] = 'Promo code removed.';
$string['commerce_cart_message_promotion_already_applied'] = 'This promo code is already applied.';
$string['commerce_cart_message_promotion_code_required'] = 'Enter a promo code.';
$string['commerce_cart_message_promotion_not_found'] = 'This promo code does not exist.';
$string['commerce_cart_message_promotion_inactive'] = 'This promo code is not active.';
$string['commerce_cart_message_promotion_not_started'] = 'This promo code is not valid yet.';
$string['commerce_cart_message_promotion_expired'] = 'This promo code has expired.';
$string['commerce_cart_message_promotion_currency_mismatch'] = 'This promo code does not apply to this currency.';
$string['commerce_cart_message_promotion_minimum_cart_not_reached'] = 'The minimum order amount for this promo code has not been reached.';
$string['commerce_cart_message_promotion_no_eligible_product'] = 'This promo code does not apply to the products in your cart.';
$string['commerce_cart_message_promotion_global_usage_limit_reached'] = 'This promo code has reached its usage limit.';
$string['commerce_cart_message_promotion_user_usage_limit_reached'] = 'You have already used this promo code the maximum number of times.';

// 7.95G7E — Promotion administration and stabilisation.
$string['commerce_promotions_title'] = 'Promotions';
$string['commerce_promotions_description'] = 'Create and manage promotional codes and automatic discounts for Commerce.';
$string['commerce_promotions_empty'] = 'No promotion has been created yet.';
$string['commerce_promotion_add'] = 'Add promotion';
$string['commerce_promotion_edit'] = 'Edit promotion';
$string['commerce_promotion_name'] = 'Name';
$string['commerce_promotion_code'] = 'Code';
$string['commerce_promotion_type'] = 'Type';
$string['commerce_promotion_value'] = 'Value';
$string['commerce_promotion_value_minor'] = 'Value (basis points or minor units)';
$string['commerce_promotion_percentage'] = 'Percentage';
$string['commerce_promotion_fixed'] = 'Fixed amount';
$string['commerce_promotion_minimum'] = 'Minimum cart amount (minor units)';
$string['commerce_promotion_priority'] = 'Priority';
$string['commerce_promotion_uses'] = 'Uses';
$string['commerce_promotion_global_limit'] = 'Global usage limit';
$string['commerce_promotion_user_limit'] = 'Per-user usage limit';
$string['commerce_promotion_active'] = 'Active';
$string['commerce_promotion_automatic'] = 'Automatic';
$string['commerce_promotion_stackable'] = 'Stackable';
$string['commerce_promotion_productskus'] = 'Eligible SKUs (one per line)';
$string['commerce_promotion_producttypes'] = 'Eligible product types (one per line)';


// 7.95G7F — Promotion polish and Commerce configuration hub.
$string['commerce_configuration_title'] = 'Commerce configuration';
$string['commerce_configuration_description'] = 'Configure access rules, commercial plans and promotional campaigns from one place.';
$string['commerce_configuration_scopes_title'] = 'Access scopes';
$string['commerce_configuration_scopes_description'] = 'Define the courses and resources granted to customers.';
$string['commerce_configuration_plans_title'] = 'Plans';
$string['commerce_configuration_plans_description'] = 'Manage plans, durations, access and commercial availability.';
$string['commerce_configuration_promotions_title'] = 'Promotions';
$string['commerce_configuration_promotions_description'] = 'Create and manage promotion codes and automatic campaigns.';
$string['commerce_configuration_open'] = 'Open';
$string['commerce_promotion_back_to_list'] = 'Back to promotion codes';
$string['commerce_promotion_all_currencies'] = 'All currencies';
$string['commerce_promotion_select_all'] = 'All — no restriction';
$string['commerce_promotion_name_help'] = 'Internal name used to identify the campaign in the CRM.';
$string['commerce_promotion_code_help'] = 'Code entered by the customer. It is not required for an automatic promotion.';
$string['commerce_promotion_type_help'] = 'Choose a percentage discount or a fixed amount.';
$string['commerce_promotion_value_display'] = 'Discount value';
$string['commerce_promotion_value_display_help'] = 'Enter a readable value: 20 means 20% for a percentage, or 20 currency units for a fixed discount.';
$string['commerce_promotion_currency_help'] = 'Limit the promotion to a configured currency, or choose all currencies.';
$string['commerce_promotion_minimum_display'] = 'Minimum cart amount';
$string['commerce_promotion_minimum_help'] = 'Minimum amount before discount, in the displayed currency unit. Enter 0 for no minimum.';
$string['commerce_promotion_priority_help'] = 'Promotions with the highest priority are evaluated first.';
$string['commerce_promotion_global_limit_help'] = 'Maximum number of uses across all customers. Leave empty for unlimited use.';
$string['commerce_promotion_user_limit_help'] = 'Maximum number of uses per customer. Leave empty for unlimited use.';
$string['commerce_promotion_active_help'] = 'The promotion can be evaluated and applied.';
$string['commerce_promotion_automatic_help'] = 'The promotion is evaluated without a customer entering a code.';
$string['commerce_promotion_stackable_help'] = 'The promotion can be combined with other compatible promotions.';
$string['commerce_promotion_productskus_help'] = 'Select eligible products. “All” creates no product restriction.';
$string['commerce_promotion_producttypes_help'] = 'Select eligible product types. “All” creates no type restriction.';
$string['commerce_promotion_validation_required'] = 'This field is required.';
$string['commerce_promotion_validation_duplicate'] = 'This code is already in use.';
$string['commerce_promotion_validation_invalid'] = 'The entered value is invalid.';

// Commerce 7.95H2 — Unified Checkout UI.
$string['commerce_checkout_title'] = 'Complete my order';
$string['commerce_checkout_eyebrow'] = 'CampusFR Commerce';
$string['commerce_checkout_subtitle'] = 'Review your order and choose your payment method.';
$string['commerce_checkout_order_summary'] = 'Order summary';
$string['commerce_checkout_payment_title'] = 'Payment';
$string['commerce_checkout_payment_description'] = 'Choose the provider available for your currency.';
$string['commerce_checkout_provider_label'] = 'Payment method';
$string['commerce_checkout_provider_stripe'] = 'Stripe';
$string['commerce_checkout_provider_stripe_desc'] = 'Secure card payment.';
$string['commerce_checkout_provider_alfa'] = 'Alfa Bank';
$string['commerce_checkout_provider_alfa_desc'] = 'Secure payment in roubles.';
$string['commerce_checkout_continue_payment'] = 'Pay now';
$string['commerce_checkout_back_cart'] = 'Back to cart';
$string['commerce_checkout_prepare_error'] = 'Checkout could not be prepared. Please review your cart.';
$string['commerce_checkout_launch_h2_hint'] = 'Provider launch will be enabled with the H4 payment bridge.';
$string['commerce_checkout_issue_empty_cart'] = 'Your cart is empty.';
$string['commerce_checkout_issue_customer_mismatch'] = 'This cart belongs to another account.';
$string['commerce_checkout_issue_currency_mismatch'] = 'The cart currency does not match the checkout currency.';
$string['commerce_checkout_issue_generic'] = 'The order must be reviewed before you continue.';

$string['commerce_checkout_launch_error'] = 'The payment could not be initialized. You have not been charged.';

$string['commerce_checkout_steps_label'] = 'Payment steps';
$string['commerce_checkout_step_cart'] = 'Cart';
$string['commerce_checkout_step_review'] = 'Review';
$string['commerce_checkout_step_payment'] = 'Payment';
$string['commerce_checkout_step_confirmation'] = 'Confirmation';
$string['commerce_checkout_prepare_error_reference'] = 'The checkout could not be prepared. Please review your cart. Reference: {$a}';
$string['commerce_checkout_launch_error_reference'] = 'The payment could not be initialized. You have not been charged. Reference: {$a}';
$string['commerce_purchase_grants_section'] = 'Granted entitlements';
$string['commerce_purchase_no_grants'] = 'No Native entitlement is recorded for this purchase.';
$string['commerce_purchase_grant_type'] = 'Entitlement type';
$string['commerce_purchase_resource'] = 'Delivered resource';
$string['commerce_purchase_beneficiary'] = 'Beneficiary';
$string['commerce_purchase_handler'] = 'Executed handler';
$string['commerce_purchase_attempts'] = 'Attempts';
$string['commerce_purchase_duration'] = 'Duration';
$string['commerce_purchase_duration_seconds'] = '{$a} s';
$string['commerce_purchase_execution_reference'] = 'Execution reference';
$string['commerce_purchase_message'] = 'Message';
$string['commerce_purchase_error'] = 'Error';
$string['commerce_purchase_fulfillment_attempts_section'] = 'Fulfillment attempt history';
$string['commerce_purchase_no_fulfillment_attempts'] = 'No Native fulfillment attempt is recorded.';

// Commerce 7.95 H4.8.5 — Native digital files.
$string['commerce_digital_files_native_help'] = 'Files are stored directly by the Native product in Moodle’s private File API. The desktop version is used first for delivery.';
$string['commerce_digital_files_legacy_fallback'] = 'No Native file is stored yet. The mapped legacy file remains available temporarily as a compatibility fallback.';
$string['commerce_invalid_digital_file_type'] = 'The digital file must be a PDF document.';
$string['commerce_digital_file_error_upload'] = 'The digital file could not be saved. Make sure it is a valid PDF.';
$string['commerce_digital_file_error_maxbytes'] = 'The digital file exceeds the maximum allowed size ({$a}).';
$string['commerce_guest_checkout_title'] = 'Your details';
$string['commerce_guest_checkout_description'] = 'Enter your details to create a secure provisional account and continue to payment.';
$string['commerce_guest_checkout_continue'] = 'Continue to checkout';
$string['commerce_guest_checkout_existing_account'] = 'An account already exists with this email address. Sign in to continue without creating a duplicate.';
$string['commerce_guest_checkout_provisional_ready'] = 'Your provisional account and cart are ready. You can now continue to secure payment.';

$string['commerce_guest_checkout_identity_required'] = 'Please enter your details before continuing to payment.';
$string['commerce_guest_checkout_login_required'] = 'This email belongs to an existing account. Please sign in to continue.';
$string['commerce_guest_checkout_account_activated'] = 'Your purchase is confirmed. Your CampusFR account is now active; set a new password before your first sign-in.';
$string['commerce_guest_checkout_activation_subject'] = 'Your CampusFR account is ready';
$string['commerce_guest_checkout_activation_message'] = 'Hello {$a->firstname},\n\nYour payment has been confirmed and your CampusFR account is now active. Set your password here before your first sign-in:\n{$a->reseturl}';
$string['commerce_guest_checkout_invalid_email'] = 'Enter a valid email address.';
$string['commerce_guest_checkout_invalid_firstname'] = 'Enter your first name (maximum 100 characters).';
$string['commerce_guest_checkout_invalid_lastname'] = 'Enter your last name (maximum 100 characters).';
$string['commerce_guest_checkout_duplicate_email_accounts'] = 'Several accounts use this email address. Sign in to your account before continuing.';
$string['commerce_guest_checkout_session_expired'] = 'Your checkout session has expired. Your cart is still available: simply start checkout again.';
$string['commerce_guest_checkout_account_mismatch'] = 'The signed-in account does not match the email entered for this checkout. Sign in with the correct account.';
$string['commerce_i2_order_not_found'] = 'This order could not be found.';
$string['commerce_i2_title_success'] = 'Your purchase is confirmed!';
$string['commerce_i2_message_success'] = 'Your payment was confirmed and your access is ready.';
$string['commerce_i2_title_processing'] = 'Payment confirmed, access being prepared';
$string['commerce_i2_message_processing'] = 'We received your payment. CampusFR is now finalising your access.';
$string['commerce_i2_title_pending'] = 'Payment verification in progress';
$string['commerce_i2_message_pending'] = 'The payment provider is still confirming the transaction. Your access will appear once validated.';
$string['commerce_i2_title_failed'] = 'The payment was unsuccessful';
$string['commerce_i2_message_failed'] = 'No confirmed charge was recorded. You can retry or choose another option.';
$string['commerce_i2_title_cancelled'] = 'Payment cancelled';
$string['commerce_i2_message_cancelled'] = 'Your order was kept, but no payment was confirmed.';
$string['commerce_i2_title_unknown'] = 'We are checking your order';
$string['commerce_i2_message_unknown'] = 'The received status requires an additional check. Your order information remains saved.';
$string['commerce_i2_order_label'] = 'CampusFR order';
$string['commerce_i2_quantity'] = 'Quantity: {$a}';
$string['commerce_i2_open_course'] = 'Open course';
$string['commerce_i2_download_file'] = 'Download file';
$string['commerce_i2_retry'] = 'Retry payment';
$string['commerce_i2_back_cart'] = 'Return to cart';
$string['commerce_i2_my_orders'] = 'My purchases';
$string['commerce_i2_my_courses'] = 'My courses';
$string['commerce_i2_my_resources'] = 'My resources';
$string['commerce_i2_support'] = 'Get support';
$string['commerce_i2_support_subject'] = 'Help with order {$a}';
$string['commerce_i2_next_title'] = 'Find my content';
$string['commerce_i3_access_unavailable'] = 'This access is not available yet or is no longer available.';
$string['commerce_i3_access_missing'] = 'The requested content could not be found.';
$string['commerce_i3_access_unsupported'] = 'This type of access is not supported.';
$string['commerce_i3_access_pending'] = 'Access is being prepared.';
$string['commerce_i3_access_expired'] = 'This access has expired.';
$string['commerce_i3_access_download_limit_reached'] = 'The download limit has been reached.';

$string['commerce_i43_page_title'] = 'Order {$a}';
$string['commerce_i43_back'] = 'Back to My purchases';
$string['commerce_i43_order'] = 'CampusFR order';
$string['commerce_i43_total'] = 'Total';
$string['commerce_i43_statuses'] = 'Order statuses';
$string['commerce_i43_order_status'] = 'Order';
$string['commerce_i43_payment_status'] = 'Payment';
$string['commerce_i43_access_status'] = 'Access';
$string['commerce_i43_items'] = 'Order contents';
$string['commerce_i43_unit_price'] = 'Unit price';
$string['commerce_i43_gross'] = 'Subtotal';
$string['commerce_i43_discount'] = 'Discount';
$string['commerce_i43_summary'] = 'Summary';
$string['commerce_i43_provider'] = 'Payment method';
$string['commerce_i43_paid_at'] = 'Payment confirmed on';
$string['commerce_i43_technical'] = 'Technical information';


// Commerce 7.95 I4.4-I4.5 — Order timeline and payment information.
$string['commerce_i44_timeline'] = 'Order timeline';
$string['commerce_i44_event_order_created'] = 'Order created';
$string['commerce_i44_event_payment_confirmed'] = 'Payment confirmed';
$string['commerce_i44_event_payment_pending'] = 'Payment pending';
$string['commerce_i44_event_payment_processing'] = 'Payment processing';
$string['commerce_i44_event_payment_failed'] = 'Payment failed';
$string['commerce_i44_event_payment_cancelled'] = 'Payment cancelled';
$string['commerce_i44_event_access_available'] = 'Access available';
$string['commerce_i44_event_access_planned'] = 'Access preparation';
$string['commerce_i44_event_access_processing'] = 'Access activation in progress';
$string['commerce_i44_event_access_failed'] = 'Access activation failed';
$string['commerce_i45_payment_information'] = 'Payment information';
$string['commerce_i45_payment_status'] = 'Status';
$string['commerce_i45_provider'] = 'Provider';
$string['commerce_i45_amount'] = 'Processed amount';
$string['commerce_i45_paid_at'] = 'Payment confirmed on';
$string['commerce_i45_request_status'] = 'Request status';
$string['commerce_i45_requested_at'] = 'Request created on';
$string['commerce_i45_expires_at'] = 'Request expires on';

// 7.95 I4.6-I4.8.
$string['commerce_i46_support_body'] = 'Hello,\\n\\nI need help with order {$a->reference}.\\nAccount: {$a->email}\\n\\nProblem description:\\n';
$string['commerce_i46_contact_support'] = 'Contact support';
$string['commerce_i46_support_title'] = 'Need help?';
$string['commerce_i46_support_description'] = 'Our team can help with access, downloads or payment questions. This order reference will be added automatically to your message.';
$string['commerce_i46_reference_to_share'] = 'Reference to share';
$string['commerce_i47_bundle_eyebrow'] = 'Bundle offer';
$string['commerce_i47_bundle_title'] = 'Your bundle contents';
$string['commerce_i47_bundle_description'] = 'Every item included in this offer is shown separately below with its available access.';
$string['commerce_i47_bundle_items'] = 'items';
$string['commerce_i47_bundle_courses'] = 'courses';
$string['commerce_i47_bundle_digitals'] = 'digital resources';
$string['commerce_i47_bundle_accesses'] = 'available accesses';

// Commerce 7.95 I4.9 — customer purchase projection.
$string['commerce_i49_course_purchase'] = 'CampusFR course';
$string['commerce_i49_view_course_page'] = 'View presentation';
$string['commerce_i49_open_course'] = 'Open course';
$string['commerce_i49_bundle_purchases'] = 'Purchased bundles';
$string['commerce_i49_bundle_default_name'] = 'CampusFR offer';
$string['commerce_i49_bundle_badge'] = 'Bundle';
$string['commerce_i49_bundle_contains'] = 'Includes';

// 7.95 I4.10 — Customer Experience Certification.
$string['commerce_i410_order_confirmed'] = 'Order confirmed';
$string['commerce_i410_order_processing'] = 'Order being processed';
$string['commerce_i410_order_cancelled'] = 'Order cancelled';
$string['commerce_i410_order_failed'] = 'Order not completed';
$string['commerce_i410_payment_received'] = 'Payment received';
$string['commerce_i410_payment_pending'] = 'Payment pending';
$string['commerce_i410_payment_cancelled'] = 'Payment cancelled';
$string['commerce_i410_payment_failed'] = 'Payment declined';
$string['commerce_i410_access_available'] = 'Access available';
$string['commerce_i410_access_preparing'] = 'Access being prepared';
$string['commerce_i410_access_failed'] = 'Access activation interrupted';
$string['commerce_i410_step_completed'] = 'Completed';
$string['commerce_i410_step_pending'] = 'In progress';
$string['commerce_i410_step_failed'] = 'Action required';
$string['commerce_i410_order_update'] = 'Order update';
$string['commerce_i410_product_access'] = 'Product access';
$string['commerce_i410_payment_method_unknown'] = 'Online payment';
$string['commerce_i410_not_available'] = 'Not available';
$string['commerce_i410_type_bundle'] = 'Bundle';
$string['commerce_i410_type_course'] = 'Course';
$string['commerce_i410_type_digital'] = 'Digital resource';
$string['commerce_i410_type_product'] = 'Product';
$string['commerce_i410_bundle_includes'] = 'Included items';
$string['commerce_i410_open_course'] = 'Open course';
$string['commerce_i410_reference'] = 'Reference';
$string['commerce_i410_order_date'] = 'Date';
$string['commerce_i410_article_count'] = 'Number of items';
$string['commerce_i410_payment_method'] = 'Payment method';
$string['commerce_i410_amount_paid'] = 'Amount paid';
$string['commerce_i410_invoice'] = 'Invoice';
$string['commerce_i410_invoice_description'] = 'Download the document for this order.';
$string['commerce_i410_download_invoice'] = 'Download PDF invoice';
$string['commerce_i410_invoice_title'] = 'Invoice {$a}';
$string['commerce_i410_invoice_reference'] = 'Invoice reference';
$string['commerce_i410_invoice_date'] = 'Date';
$string['commerce_i410_invoice_customer'] = 'Customer';
$string['commerce_i410_invoice_item'] = 'Item';
$string['commerce_i410_invoice_quantity'] = 'Quantity';
$string['commerce_i410_invoice_total'] = 'Total';
$string['commerce_i410_invoice_generated_notice'] = 'Document generated automatically from the CampusFR customer area.';

$string['commerce_i411_original_amount'] = 'Original amount';
$string['commerce_i411_discount'] = 'Discount';
$string['commerce_i411_promo_code'] = 'Promotion code';
$string['commerce_i411_paid_badge'] = 'Paid';
$string['commerce_i411_product_page'] = 'View product';
$string['commerce_i411_invoice_settings'] = 'Commerce invoicing';
$string['commerce_i411_invoice_settings_desc'] = 'Configure separate invoice issuers for EUR and RUB.';
$string['commerce_i411_invoice_profile_eur'] = 'EUR / Stripe invoice entity';
$string['commerce_i411_invoice_profile_rub'] = 'RUB / Alfa invoice entity';
$string['commerce_i411_invoice_name'] = 'Nom ou raison sociale';
$string['commerce_i411_invoice_address'] = 'Adresse complète';
$string['commerce_i411_invoice_legal'] = 'Informations légales';
$string['commerce_i411_invoice_email'] = 'E-mail';
$string['commerce_i411_invoice_phone'] = 'Téléphone';
$string['commerce_i411_invoice_website'] = 'Site web';
$string['commerce_i411_invoice_tax_notice'] = 'Mention fiscale';
$string['commerce_i411_invoice_footer'] = 'Pied de facture';

$string['commerce_order_access_preparing'] = 'Access being prepared';

$string['commerce_multi_item_order_title'] = 'Order {$a}';
$string['commerce_purchase_origin'] = 'Purchase origin';
$string['commerce_purchase_origin_legacy'] = 'Legacy';
$string['commerce_purchase_origin_native'] = 'Native';
$string['commerce_invoice_purchase_date'] = 'Purchase date';
$string['commerce_invoice_bundle_includes'] = 'Includes:';
$string['commerce_invoice_subtotal'] = 'Subtotal';
$string['commerce_invoice_discount'] = 'Discount';
$string['commerce_invoice_promotion_code'] = 'Promotion code';
$string['commerce_invoice_total_paid'] = 'Total paid';
$string['commerce_invoice_payment_information'] = 'Payment information';
$string['commerce_invoice_payment_provider'] = 'Payment method';
$string['commerce_invoice_transaction_id'] = 'Transaction ID';
$string['commerce_invoice_generated_at'] = 'Invoice generated on {$a}';

$string['digital_library_title'] = 'My digital resources';
$string['digital_library_user_title'] = '{$a}\'s digital resources';
$string['digital_library_subtitle'] = 'Find your digital products and download the available files directly.';
$string['digital_library_empty_title'] = 'No digital resources yet';
$string['digital_library_empty_description'] = 'Your digital products will appear here as soon as a purchase grants access to a file.';
$string['digital_library_open_catalog'] = 'Browse the store';
$string['digital_library_download'] = 'Download';
$string['digital_library_source_legacy'] = 'Historical';
$string['digital_library_source_native'] = 'Commerce';
$string['digital_library_bundle_badge'] = 'Included in a bundle';
$string['digital_library_resource_fallback'] = 'Digital resource';
$string['digital_library_resource_number'] = 'Digital resource #{$a}';

$string['digital_library_view_product'] = 'View product page';
$string['digital_library_file'] = 'Available file';
$string['digital_library_files'] = 'Available files';
$string['digital_library_file_type'] = 'Type';
$string['digital_library_file_size'] = 'Size';
$string['digital_library_already_downloaded'] = 'Already downloaded';
$string['digital_library_not_downloaded_yet'] = 'Not downloaded yet';
$string['digital_library_last_download'] = 'Last download';
$string['digital_library_download_count_one'] = '1 download';
$string['digital_library_download_count_many'] = '{$a} downloads';
$string['digital_library_download_file'] = 'Download';
$string['digital_library_download_aria'] = 'Download {$a->file} — {$a->product}';
$string['digital_library_history_unavailable'] = 'Download history unavailable';
$string['event_digital_file_downloaded'] = 'Digital file downloaded';

$string['task_process_commerce_mail_queue'] = 'Process the Commerce transactional mail queue';

$string['commerce_mail_purchase_access_subject'] = 'Your CampusFR access is ready';
$string['commerce_mail_purchase_receipt_subject'] = 'Your CampusFR purchase confirmation';
$string['commerce_mail_payment_pending_subject'] = 'Your CampusFR payment is being processed';
$string['commerce_mail_payment_failed_subject'] = 'Your CampusFR payment was unsuccessful';
$string['commerce_mail_payment_cancelled_subject'] = 'Your CampusFR payment was cancelled';
$string['commerce_mail_greeting'] = 'Hello {$a},';
$string['commerce_mail_customer_fallback'] = 'there';
$string['commerce_mail_purchase_access_intro'] = 'Your purchase is confirmed and your resources are now available.';
$string['commerce_mail_purchase_receipt_intro'] = 'Thank you for your purchase. Here is your order summary.';
$string['commerce_mail_payment_pending_intro'] = 'Your payment is still being processed. No new access will be activated until it is confirmed.';
$string['commerce_mail_payment_pending_help'] = 'You can check your order status from your CampusFR account.';
$string['commerce_mail_payment_failed_intro'] = 'We could not confirm your payment.';
$string['commerce_mail_payment_failed_help'] = 'You can try again from your CampusFR account or use another payment method.';
$string['commerce_mail_payment_cancelled_intro'] = 'Your payment was cancelled and no amount was confirmed by CampusFR.';
$string['commerce_mail_payment_cancelled_help'] = 'Your cart remains available if you would like to resume your purchase.';
$string['commerce_mail_reference'] = 'Reference';
$string['commerce_mail_quantity'] = 'Quantity';
$string['commerce_mail_total'] = 'Total';
$string['commerce_mail_payment_information'] = 'Payment information';
$string['commerce_mail_payment_provider'] = 'Payment method';
$string['commerce_mail_transaction_reference'] = 'Transaction';
$string['commerce_mail_payment_status'] = 'Status';
$string['commerce_mail_access_course'] = 'Access the course';
$string['commerce_mail_download_file'] = 'Download the file';
$string['commerce_mail_view_product'] = 'View product page';
$string['commerce_mail_view_order'] = 'View my order';
$string['commerce_mail_view_purchases'] = 'View my purchases';
$string['commerce_mail_view_resources'] = 'View my digital resources';
$string['commerce_mail_view_courses'] = 'Access my courses';
$string['commerce_mail_product_fallback'] = 'CampusFR product';
$string['commerce_mail_no_item_details'] = 'Resource details will be available in your CampusFR account.';

$string['crm_commerce_nav_mail'] = 'Emails';
$string['commerce_mail_admin_title'] = 'Transactional emails';
$string['commerce_mail_admin_description'] = 'Monitor the Commerce email queue, preview messages and retry failures.';
$string['commerce_mail_preview'] = 'Email preview';
$string['attempts'] = 'Attempts';
$string['retry'] = 'Retry';
$string['commerce_mail_type_purchase_access'] = 'Access available';
$string['commerce_mail_type_purchase_receipt'] = 'Purchase confirmation';
$string['commerce_mail_type_payment_pending'] = 'Payment pending';
$string['commerce_mail_type_payment_failed'] = 'Payment failed';
$string['commerce_mail_type_payment_cancelled'] = 'Payment cancelled';
$string['commerce_mail_status_queued'] = 'Queued';
$string['commerce_mail_status_processing'] = 'Processing';
$string['commerce_mail_status_sent'] = 'Sent';
$string['commerce_mail_status_failed'] = 'Failed';
$string['commerce_mail_status_cancelled'] = 'Cancelled';
$string['commerce_mail_language_fr'] = 'French';
$string['commerce_mail_language_en'] = 'English';
$string['commerce_mail_language_ru'] = 'Russian';
$string['commerce_mail_filter_all'] = 'All';
$string['commerce_mail_status_filter'] = 'Status';
$string['commerce_mail_type_filter'] = 'Email type';
$string['commerce_mail_language_filter'] = 'Language';
$string['commerce_mail_purchase_id'] = 'Purchase ID';
$string['commerce_mail_search_placeholder'] = 'Email, recipient or idempotency key';
$string['commerce_mail_dashboard_description'] = 'Monitor transactional emails, preview their content and retry failed deliveries.';
$string['commerce_mail_templates_title'] = 'Email templates';
$string['commerce_mail_templates_description'] = 'Customise the editorial areas of transactional emails without changing their secure technical blocks.';
$string['commerce_mail_templates_manage'] = 'Manage templates';
$string['commerce_mail_template_type'] = 'Email type';
$string['commerce_mail_template_language'] = 'Language';
$string['commerce_mail_template_enabled'] = 'Custom template enabled';
$string['commerce_mail_template_subject'] = 'Subject';
$string['commerce_mail_template_preheader'] = 'Preheader';
$string['commerce_mail_template_heading'] = 'Main heading';
$string['commerce_mail_template_intro'] = 'Introduction';
$string['commerce_mail_template_outro'] = 'Closing text';
$string['commerce_mail_template_signature'] = 'Signature';
$string['commerce_mail_template_headerimage_enabled'] = 'Prepare a header image';
$string['commerce_mail_template_headerimage_note'] = 'Add a lightweight horizontal image. It will replace the generic banner for this email template.';
$string['commerce_mail_template_tokens'] = 'Allowed tokens';
$string['commerce_mail_template_default'] = 'Default content';
$string['commerce_mail_template_edit_title'] = '{$a->type} — {$a->language}';
$string['commerce_mail_template_edit_description'] = 'Editorial content will surround the technical message block. Access links, products, amounts and actions remain generated by Commerce.';
$string['commerce_mail_template_invalid_type'] = 'Invalid email type.';
$string['commerce_mail_template_invalid_language'] = 'Invalid language.';
$string['commerce_mail_back_to_log'] = 'Back to email log';
$string['commerce_mail_template_reset'] = 'Restore default';
$string['commerce_mail_template_reset_confirm'] = 'Delete this customisation and restore the default content?';
$string['commerce_mail_template_reset_done'] = 'The default content has been restored.';

$string['commerce_mail_template_headerimage_file'] = 'Header image';
$string['commerce_mail_template_preview_title'] = 'Preview — {$a->type} — {$a->language}';
$string['commerce_mail_template_preview_description'] = 'Preview generated with sample data. No email is sent.';
$string['plaintext'] = 'Plain-text version';

$string['settings:commerce_mail_audit_heading'] = 'Commerce email audit copies';
$string['settings:commerce_mail_audit_heading_desc'] = 'Configure an independent transactional copy recorded in the outbox without affecting customer delivery.';
$string['settings:commerce_mail_audit_copy_enabled'] = 'Enable audit copies';
$string['settings:commerce_mail_audit_copy_enabled_desc'] = 'Creates a separate email intention for the selected message types.';
$string['settings:commerce_mail_audit_copy_address'] = 'Audit copy address';
$string['settings:commerce_mail_audit_copy_address_desc'] = 'Technical destination for copies, for example log@campusfr.fr.';
$string['settings:commerce_mail_audit_copy_types'] = 'Email types to copy';
$string['settings:commerce_mail_audit_copy_types_desc'] = 'Selects the transactional intentions that should create an independent audit copy.';
$string['settings:commerce_mail_audit_copy_include_attachment'] = 'Attach the invoice to the audit copy';
$string['settings:commerce_mail_audit_copy_include_attachment_desc'] = 'Disabled by default to limit duplication of personal data and attachments.';

$string['commerce_mail_preview_modes'] = 'Email preview modes';
$string['commerce_mail_preview_desktop'] = 'Desktop';
$string['commerce_mail_preview_mobile'] = 'Mobile';
$string['commerce_mail_preview_text'] = 'Plain text';
$string['commerce_mail_preview_source'] = 'HTML source';
$string['commerce_mail_preview_desktop_title'] = 'Desktop email preview';
$string['commerce_mail_preview_mobile_title'] = 'Mobile email preview';
$string['commerce_mail_health_certified'] = 'Mail Engine certified';
$string['commerce_mail_health_attention'] = 'Mail Engine requires attention';
$string['commerce_mail_health_readonly'] = 'Read-only health check of the transactional mail engine.';
$string['commerce_mail_health_ok'] = 'OK';
$string['commerce_mail_health_warnings'] = 'warnings';
$string['commerce_mail_health_errors'] = 'errors';

$string['commerce_cart_upgrade_label'] = 'Upgrade';
$string['commerce_cart_message_upgrade_not_eligible'] = 'This upgrade is no longer available for this account.';
$string['commerce_cart_upgrade_not_eligible'] = 'This upgrade is no longer available. Refresh the store and try again.';
$string['crm_commerce_orders'] = 'Orders';
$string['crm_commerce_orders_hint'] = 'Native Commerce orders linked to this customer.';
$string['crm_commerce_active_grants'] = 'Active access';
$string['crm_commerce_active_grants_hint'] = 'Active course and digital Grants.';
$string['crm_commerce_no_purchases'] = 'No Commerce purchase found for this customer.';
$string['crm_commerce_reference'] = 'Reference';
$string['crm_commerce_purchase_type'] = 'Type';
$string['crm_commerce_contents'] = 'Contents';
$string['crm_commerce_amount'] = 'Amount';
$string['crm_commerce_view_order'] = 'View order';
$string['crm_commerce_type_course'] = 'Course';
$string['crm_commerce_type_digital'] = 'Digital product';
$string['crm_commerce_type_bundle'] = 'Bundle';
$string['crm_commerce_type_upgrade'] = 'Upgrade';
$string['crm_commerce_type_mixed'] = 'Mixed cart';
$string['crm_commerce_status_created'] = 'Created';
$string['crm_commerce_status_payment_pending'] = 'Payment pending';
$string['crm_commerce_status_paid'] = 'Paid';
$string['crm_commerce_status_fulfillment_pending'] = 'Fulfillment pending';
$string['crm_commerce_status_fulfilled'] = 'Fulfilled';
$string['crm_commerce_status_failed'] = 'Failed';
$string['crm_commerce_status_cancelled'] = 'Cancelled';
$string['crm_timeline_commerce_purchase_course'] = 'Course order created';
$string['crm_timeline_commerce_purchase_digital'] = 'Digital order created';
$string['crm_timeline_commerce_purchase_bundle'] = 'Bundle order created';
$string['crm_timeline_commerce_purchase_upgrade'] = 'Upgrade order created';
$string['crm_timeline_commerce_purchase_mixed'] = 'Mixed order created';
$string['crm_timeline_commerce_purchase_purchase'] = 'Order created';
$string['crm_timeline_commerce_purchase_description'] = '{$a->reference} · {$a->items} · {$a->amount}';
$string['crm_timeline_commerce_payment_paid'] = 'Payment confirmed';
$string['crm_timeline_commerce_payment_pending'] = 'Payment pending';
$string['crm_timeline_commerce_payment_failed'] = 'Payment failed';
$string['crm_timeline_commerce_payment_description'] = '{$a->reference} · {$a->amount} · {$a->provider}';
$string['crm_timeline_commerce_grant_course_access'] = 'Course access granted';
$string['crm_timeline_commerce_grant_digital_download'] = 'Digital resource available';
$string['crm_timeline_commerce_grant_access'] = 'Access granted';

$string['commerce_order_print'] = 'Print order';
$string['commerce_support_page_title'] = 'Support — {$a}';
$string['commerce_support_heading'] = 'How can we help?';
$string['commerce_support_intro'] = 'Your request will be sent directly to the CampusFR team together with the useful details of your order.';
$string['commerce_support_back_to_order'] = 'Back to order';
$string['commerce_support_order'] = 'Order';
$string['commerce_support_customer'] = 'Customer';
$string['commerce_support_email'] = 'Email address';
$string['commerce_support_category'] = 'Request type';
$string['commerce_support_category_payment'] = 'Payment';
$string['commerce_support_category_course_access'] = 'Course access';
$string['commerce_support_category_download'] = 'Download';
$string['commerce_support_category_invoice'] = 'Invoice';
$string['commerce_support_category_refund'] = 'Refund';
$string['commerce_support_category_other'] = 'Other';
$string['commerce_support_subject'] = 'Subject';
$string['commerce_support_default_subject'] = 'Question about order {$a}';
$string['commerce_support_message'] = 'Describe your request';
$string['commerce_support_send'] = 'Send request';
$string['commerce_support_success'] = 'Your request has been sent to the CampusFR team. We will reply as soon as possible.';
$string['commerce_support_unavailable'] = 'Integrated support is temporarily unavailable. Please try again later.';
$string['commerce_support_internal_reference'] = 'Order reference';
$string['commerce_support_payment_status'] = 'Payment status';
$string['commerce_support_fulfillment_status'] = 'Fulfilment status';
$string['commerce_support_products'] = 'Related products';

$string['event_commerce_customer_action_clicked'] = 'Commerce customer action tracked';

$string['commerce_tracking_invalid'] = 'This Commerce tracking link is invalid or has expired.';

$string['commerce_access_preparing'] = 'Access is being prepared';

$string['commerce_access_temporarily_unavailable'] = 'Access is temporarily unavailable';

$string['commerce_view_order'] = 'View order';

$string['profile_customer_space_title'] = 'My CampusFR space';
$string['profile_link_courses'] = 'My courses';
$string['profile_link_resources'] = 'My resources';
$string['profile_link_purchases'] = 'My purchases';
$string['nav_my_courses'] = 'My courses';
$string['nav_my_resources'] = 'My resources';
$string['nav_my_purchases'] = 'My purchases';
$string['nav_my_profile'] = 'My profile';
$string['commerce_cart_clear'] = 'Clear cart';
$string['commerce_cart_clear_confirm'] = 'Are you sure you want to clear your cart?';

$string['commerce_cart_buy_now'] = 'Buy now';
$string['commerce_cart_remove_from_cart'] = 'Remove from cart';
$string['commerce_cart_added_modal_title'] = 'Added to cart';
$string['commerce_cart_added_modal_text'] = 'Your item has been added to the cart.';
$string['commerce_cart_clear_confirm_action'] = 'Clear cart';
$string['commerce_cart_message_bundle_all_owned'] = 'You already own every item included in this offer.';
$string['commerce_cart_message_bundle_partial_owned'] = 'This offer contains one or more items you already own. The bundle price remains unchanged.';
$string['commerce_cart_message_buynow_success'] = 'The product is ready for checkout.';

// Account activation after Guest Checkout.
$string['commerce_guest_checkout_activation_message'] = 'Hello {$a->firstname},\n\nYour payment has been confirmed. Set your CampusFR password using this secure link:\n{$a->activationurl}\n\nThis personal link is valid for 48 hours.';
$string['commerce_guest_activation_title'] = 'Activate your CampusFR account';
$string['commerce_guest_activation_title_prefix'] = 'Activate your account';
$string['commerce_guest_activation_quick_note'] = 'One last step: it only takes a few seconds, and your access will be available immediately.';
$string['commerce_guest_activation_intro'] = 'Hello {$a->firstname}, choose your password to access your courses, resources and purchases immediately.';
$string['commerce_guest_activation_email'] = 'Account associated with: {$a}';
$string['commerce_guest_activation_submit'] = 'Set my password';
$string['commerce_guest_activation_success'] = 'Your password has been set. You are now signed in to CampusFR.';
$string['commerce_guest_activation_invalid'] = 'This activation link is invalid or has expired. Return to your order page to obtain a new link.';
$string['commerce_guest_activation_failed'] = 'Your account could not be activated. Technical detail: {$a}';
$string['commerce_guest_activation_password_invalid'] = 'The password does not meet the security policy: {$a}';
$string['commerce_guest_activation_result_title'] = 'Finish setting up your account';
$string['commerce_guest_activation_result_message'] = 'Your purchase is confirmed. Set your password now to access your courses, resources and purchases.';
$string['commerce_guest_activation_result_cta'] = 'Set my password';
$string['commerce_guest_existing_account_result_title'] = 'Find your purchase in your account';
$string['commerce_guest_existing_account_result_message'] = 'This email address is already linked to a CampusFR account. Sign in to access your order and content.';

$string['commerce_guest_activation_confirm_password'] = 'Confirm new password';

$string['commerce_guest_activation_email_label'] = 'Account associated with:';
$string['commerce_guest_activation_security_title'] = 'Security requirements';
$string['commerce_guest_activation_security_minlength'] = 'At least {$a} characters';
$string['commerce_guest_activation_security_lowercase'] = 'At least one lowercase letter';
$string['commerce_guest_activation_security_uppercase'] = 'At least one uppercase letter';
$string['commerce_guest_activation_security_digit'] = 'At least one number';
$string['commerce_guest_activation_security_special'] = 'At least one special character';
$string['commerce_guest_activation_secure_link_title'] = 'Secure link';
$string['commerce_guest_activation_secure_link'] = 'This link is personal and can only be used once.';
$string['commerce_guest_activation_secure_link_expiry'] = 'This link is personal, can only be used once, and will expire on {$a}.';
$string['commerce_guest_activation_email_cta'] = 'Choose my password';

$string['commerce_guest_activation_email_expiry'] = 'This link is valid until {$a}.';

$string['commerce_mail_type_account_activation'] = 'Welcome / activation';
$string['commerce_product_covers_title'] = 'Product visuals';
$string['commerce_product_covers_help'] = 'Upload artwork for each usage. When a visual is missing, Commerce automatically falls back to the Storefront visual and then the legacy main cover.';
$string['commerce_product_cover_fallback_notice'] = 'No dedicated visual. The automatic fallback will be used.';
$string['commerce_product_cover_role_storefront'] = 'Storefront';
$string['commerce_product_cover_role_storefront_help'] = 'Product card in the Storefront. Suggested ratio: 4:3 or short portrait.';
$string['commerce_product_cover_role_product'] = 'Product page';
$string['commerce_product_cover_role_product_help'] = 'Main visual on the detailed product page. Suggested ratio: 16:9.';
$string['commerce_product_cover_role_recommendation'] = 'Recommendations';
$string['commerce_product_cover_role_recommendation_help'] = 'Compact card in My Courses. Suggested ratio: 4:3.';
$string['commerce_product_cover_role_resources'] = 'My resources';
$string['commerce_product_cover_role_resources_help'] = 'Digital resource library. Suggested ratio: 3:4.';
$string['commerce_product_cover_role_checkout'] = 'Cart and checkout';
$string['commerce_product_cover_role_checkout_help'] = 'Compact thumbnail in the order summary.';
$string['commerce_product_cover_role_email'] = 'Emails';
$string['commerce_product_cover_role_email_help'] = 'Visual available to transactional emails.';
$string['commerce_product_cover_role_social'] = 'Social sharing';
$string['commerce_product_cover_role_social_help'] = 'Open Graph preview. Suggested size: 1200 × 630.';

$string['commerce_guest_activation_protected_title'] = 'Your information is protected';
$string['commerce_guest_activation_protected_text'] = 'Your purchase is confirmed. Choose a password now to secure your CampusFR account. Once this step is complete, you will be signed in automatically and can immediately access your courses, resources and purchases.';
$string['commerce_guest_activation_show_password'] = 'Show password';
$string['commerce_guest_activation_hide_password'] = 'Hide password';

$string['settings_trial_conversion_product_sku'] = 'Native Trial conversion product';
$string['settings_trial_conversion_product_sku_desc'] = 'SKU of the Native Commerce product shown first to Trial users. Leave empty to use the configured target plan or the Storefront.';
$string['settings_trial_conversion_plan_id'] = 'Legacy Trial conversion target plan';
$string['settings_trial_conversion_plan_id_desc'] = 'ID of the paid Legacy plan whose mapped Native product should be offered. An explicit SKU takes precedence.';
$string['commerce_trial_conversion_bridge_notice'] = 'Your {$a->percent}% Trial discount is active until {$a->deadline}. It will be applied automatically in your cart.';

$string['commerce_trial_conversion_label'] = 'Trial offer';
$string['commerce_trial_conversion_adjustment'] = 'Trial conversion discount';
$string['commerce_cart_message_trial_conversion_not_eligible'] = 'This Trial offer is no longer available for this product. The cart was not changed.';

$string['unlock_subscriber_button_single'] = 'Buy the course';
$string['unlock_grammar_button_single'] = 'Buy the course';
$string['unlock_full_button_single'] = 'Buy the course';
$string['commerce_trial_price_explanation'] = 'Your trial access saves you {$a->saving}. Offer available until {$a->deadline}.';
$string['unlock_course_title'] = 'Course access required';
$string['unlock_course_text'] = 'Buy the course to unlock this activity.';
$string['unlock_course_button'] = 'Buy the course';
$string['commerce_trial_storefront_badge'] = 'Special Trial offer';
$string['commerce_trial_storefront_discount'] = 'Trial −{$a}%';
$string['commerce_trial_storefront_explanation'] = 'This discount is reserved for members currently using trial access.';
$string['commerce_trial_storefront_product_promotion'] = 'Promotion';
$string['commerce_trial_storefront_final_price'] = 'Your Trial price';
$string['commerce_trial_storefront_deadline'] = 'Trial offer available until {$a}.';
$string['commerce_cart_list_total'] = 'Price before discounts';
$string['commerce_cart_product_promotions_total'] = 'Product promotions';
$string['commerce_cart_total_reductions'] = 'Total discounts';
$string['commerce_trial_storefront_initial_price'] = 'Initial price';
$string['commerce_cart_badge_course'] = 'Course';
$string['commerce_cart_badge_digital'] = 'Digital resource';
$string['commerce_cart_badge_bundle'] = 'Bundle';
$string['commerce_cart_badge_trial'] = 'Trial offer';
$string['commerce_cart_badge_upgrade'] = 'Upgrade';
$string['commerce_cart_badge_product'] = 'Product';
$string['commerce_purchase_pricing_section'] = 'Pricing and discounts';
$string['commerce_purchase_native_payment_attempt'] = 'Native payment attempt';
$string['commerce_pricing_initial_product'] = 'Initial product price';
$string['commerce_pricing_owned_credit'] = '{$a} credit';
$string['commerce_pricing_upgrade_price'] = 'Upgrade price';
$string['commerce_pricing_final_price'] = 'Your final price';
$string['commerce_pricing_details'] = 'Price details';
$string['commerce_pricing_initial_promotion'] = 'Initial promotion';
$string['commerce_pricing_upgrade_offer'] = 'Upgrade offer';
$string['commerce_pricing_you_save'] = 'You save';
$string['commerce_invoice_owned_credit'] = 'Previously owned product credit';
$string['commerce_invoice_other_discount'] = 'Other discounts';
$string['commerce_invoice_item_paid_price'] = 'Price paid for this item';
$string['commerce_storefront_hide_owned'] = 'Hide products you already own';
$string['commerce_storefront_hide_owned_help'] = 'Turn this filter off to view the complete catalogue.';
$string['commerce_storefront_price_standard'] = 'Price';
$string['commerce_storefront_price_promotional'] = 'Promotional price';
$string['commerce_storefront_price_trial'] = 'Your Trial price';
$string['commerce_storefront_price_upgrade'] = 'Upgrade price';
$string['commerce_storefront_price_discovery'] = 'Discovery price';
$string['commerce_storefront_upgrade_offer_badge'] = 'Special Upgrade offer';
$string['commerce_storefront_upgrade_owned_explanation'] = 'You already have access to {$a}. The amount already paid is deducted from the price.';
$string['commerce_pricing_initial_promotion_percent'] = 'Initial promotion −{$a}%';
$string['commerce_cart_trial_discount_total'] = 'Trial discount';
$string['commerce_cart_upgrade_credit_total'] = 'Upgrade credit';
$string['commerce_checkout_print_summary'] = 'Print summary';
$string['commerce_cart_print_detailed'] = 'Printable detailed cart';
$string['commerce_cart_print_detailed_subtitle'] = 'Detailed items, promotions and credits applied before payment.';
$string['commerce_cart_print_generated'] = 'Document generated on {$a}';
$string['commerce_storefront_section_hero'] = 'Editorial hero';
$string['commerce_storefront_section_image_text'] = 'Image + text';
$string['commerce_storefront_section_video'] = 'Video';
$string['commerce_storefront_section_program'] = 'Programme';
$string['commerce_storefront_section_instructor'] = 'Instructor';
$string['commerce_storefront_section_testimonials'] = 'Testimonials';
$string['commerce_storefront_section_gallery'] = 'Gallery';
$string['commerce_storefront_section_id'] = 'Technical identifier';
$string['commerce_storefront_section_order'] = 'Order';
$string['commerce_storefront_section_style'] = 'Style';
$string['commerce_storefront_section_visible'] = 'Visible section';
$string['commerce_storefront_section_style_default'] = 'Standard';
$string['commerce_storefront_section_style_soft'] = 'Soft';
$string['commerce_storefront_section_style_accent'] = 'CampusFR accent';
$string['commerce_storefront_section_style_contrast'] = 'Contrast';
$string['commerce_storefront_section_style_boxed'] = 'Boxed';
$string['commerce_storefront_section_style_full_width'] = 'Full width';
$string['commerce_product_visual_format_square'] = 'Square visual — 1:1';
$string['commerce_product_visual_format_square_help'] = 'Checkout, confirmation, compact thumbnails and CRM. Recommended size: {$a}.';
$string['commerce_product_visual_format_landscape'] = 'Landscape visual — 4:3';
$string['commerce_product_visual_format_landscape_help'] = 'Store, recommendations and product cards. Recommended size: {$a}.';
$string['commerce_product_visual_format_wide'] = 'Wide visual — 16:9';
$string['commerce_product_visual_format_wide_help'] = 'Hero, video, social sharing and Open Graph. Recommended size: {$a}.';
$string['commerce_product_visual_format_portrait'] = 'Portrait visual — 4:5';
$string['commerce_product_visual_format_portrait_help'] = 'Cart, digital resources and vertical cards. Recommended size: {$a}.';
$string['commerce_product_visual_ratio_ok'] = 'Valid ratio: {$a}.';
$string['commerce_product_visual_ratio_warning'] = 'The file is accepted, but its ratio differs from {$a}. It will be cropped when displayed.';
$string['commerce_storefront_seo_title'] = 'SEO and social sharing';
$string['commerce_storefront_seo_help'] = 'These values are localised. The social image automatically uses the 16:9 master.';
$string['commerce_storefront_seo_page_title'] = 'SEO title';
$string['commerce_storefront_seo_description'] = 'Meta description';
$string['commerce_storefront_seo_description_help'] = 'Recommended length: about 150–160 characters. Content is cleaned and limited to 320 characters.';
$string['commerce_storefront_view_my_products'] = 'View my products';
$string['commerce_product_visual_status_ok'] = 'OK';
$string['commerce_product_visual_status_fallback'] = 'Fallback';
$string['commerce_product_visual_status_missing'] = 'Missing';
$string['commerce_product_visual_preview_alt'] = '{$a} artwork preview';
$string['commerce_product_visual_fallback_source'] = 'Fallback: {$a}';
$string['commerce_product_visual_metadata_dimensions'] = 'Dimensions';
$string['commerce_product_visual_metadata_ratio'] = 'Actual / target ratio';
$string['commerce_product_visual_metadata_weight'] = 'File size';
$string['commerce_product_visual_metadata_file'] = 'File';
$string['commerce_product_visual_fallback_help'] = 'This format has no dedicated master yet. The preview shows the current fallback cropped to the target ratio.';
$string['commerce_product_visual_missing_help'] = 'No master or fallback is available. The product-type placeholder will be used.';
$string['commerce_product_visual_context_preview_title'] = 'Simulated Commerce surface previews';
$string['commerce_product_visual_context_preview_help'] = 'These mockups use the same CSS classes as the real pages. They show the currently resolved master, fallback or placeholder.';
$string['commerce_product_visual_context_preview_badge'] = 'Real CSS';
$string['commerce_product_visual_context_preview_description'] = 'Example of the product presentation in its real context.';
$string['commerce_product_visual_context_boutique'] = 'Store';
$string['commerce_product_visual_context_storefront'] = 'Storefront';
$string['commerce_product_visual_context_checkout'] = 'Checkout';
$string['commerce_product_visual_context_resources'] = 'My resources';
$string['commerce_product_visual_context_available'] = 'Available';
$string['commerce_product_visual_save_format'] = 'Save this format';
$string['commerce_product_visual_no_file_selected'] = 'Select an image before saving this format.';
$string['commerce_storefront_rich_text_editor_help'] = 'TinyMCE is active: you can insert images, videos, files, links and H5P media. Files are stored in Moodle with this block.';
$string['commerce_storefront_section_h5p'] = 'H5P';
$string['commerce_storefront_image_settings'] = 'Image + text';
$string['commerce_storefront_image_upload'] = 'Moodle image';
$string['commerce_storefront_image_position'] = 'Image position';
$string['commerce_storefront_column_ratio'] = 'Column ratio';
$string['commerce_storefront_video_settings'] = 'Video';
$string['commerce_storefront_video_source'] = 'Source';
$string['commerce_storefront_video_upload'] = 'Moodle file';
$string['commerce_storefront_video_file'] = 'Video file';
$string['commerce_storefront_video_ratio'] = 'Ratio';
$string['commerce_storefront_video_poster'] = 'Poster image';
$string['commerce_storefront_h5p_settings'] = 'H5P content';
$string['commerce_storefront_h5p_content'] = 'Moodle Content Bank item (optional)';
$string['commerce_storefront_h5p_height'] = 'Minimum height';
$string['commerce_storefront_h5p_help'] = 'Priority: uploaded .h5p package, then Moodle Content Bank selection, then auxiliary URL.';
$string['commerce_storefront_h5p_none'] = 'No H5P content selected';
$string['commerce_storefront_h5p_missing'] = 'No valid H5P content is configured.';
$string['commerce_storefront_builder_sections'] = 'Page structure';
$string['commerce_storefront_builder_sections_help'] = 'Add and organise editorial blocks. The Commerce block remains protected.';
$string['commerce_storefront_builder_add'] = 'Section type';
$string['commerce_storefront_builder_add_button'] = 'Add section';
$string['commerce_storefront_builder_untitled'] = 'Untitled';
$string['commerce_storefront_builder_ready'] = 'Ready';
$string['commerce_storefront_builder_incomplete'] = 'Incomplete';
$string['commerce_storefront_builder_empty'] = 'No editorial sections. Add your first block.';
$string['commerce_storefront_builder_action_first'] = 'Move to start';
$string['commerce_storefront_builder_action_up'] = 'Move up';
$string['commerce_storefront_builder_action_down'] = 'Move down';
$string['commerce_storefront_builder_action_last'] = 'Move to end';
$string['commerce_storefront_builder_action_toggle'] = 'Show or hide';
$string['commerce_storefront_builder_action_duplicate'] = 'Duplicate';
$string['commerce_storefront_builder_action_delete'] = 'Delete';
$string['commerce_storefront_builder_drag_help'] = 'Drag blocks using the handle. With the keyboard, use Alt + Up or Down, then save the page.';
$string['commerce_storefront_builder_drag_handle'] = 'Move the {$a} block';
$string['commerce_storefront_repository_picker_help'] = 'Image, Media, Link and H5P buttons open Moodle’s file picker for computer uploads and enabled repositories.';
$string['commerce_storefront_h5p_upload'] = 'Upload an H5P file';
$string['commerce_storefront_h5p_bank_empty'] = 'No H5P content is currently stored in Moodle Content Bank. You can upload a .h5p package directly above.';
$string['commerce_storefront_h5p_open_bank'] = 'Open Content Bank';

// Visual Page Composer.
$string['commerce_storefront_composer_layout'] = 'Visual layout';
$string['commerce_storefront_composer_layout_help'] = 'Blocks sharing the same row identifier can be distributed across several columns. On mobile, columns stack automatically.';
$string['commerce_storefront_composer_columns'] = 'Columns';
$string['commerce_storefront_composer_column'] = 'Position in row';
$string['commerce_storefront_composer_ratio'] = 'Column ratio';
$string['commerce_storefront_composer_row'] = 'Row identifier';
$string['commerce_storefront_composer_width'] = 'Width';
$string['commerce_storefront_composer_width_contained'] = 'Contained';
$string['commerce_storefront_composer_width_wide'] = 'Wide';
$string['commerce_storefront_composer_width_full'] = 'Full width';
$string['commerce_storefront_composer_background'] = 'Background';
$string['commerce_storefront_composer_background_default'] = 'Default';
$string['commerce_storefront_composer_background_soft'] = 'Soft';
$string['commerce_storefront_composer_background_accent'] = 'Accent';
$string['commerce_storefront_composer_background_contrast'] = 'Contrast';
$string['commerce_storefront_composer_background_transparent'] = 'Transparent';
$string['commerce_storefront_composer_spacing'] = 'Vertical spacing';
$string['commerce_storefront_composer_spacing_none'] = 'None';
$string['commerce_storefront_composer_spacing_small'] = 'Small';
$string['commerce_storefront_composer_spacing_medium'] = 'Medium';
$string['commerce_storefront_composer_spacing_large'] = 'Large';
$string['commerce_storefront_composer_alignment'] = 'Vertical alignment';
$string['commerce_storefront_composer_alignment_start'] = 'Top';
$string['commerce_storefront_composer_alignment_center'] = 'Centre';
$string['commerce_storefront_composer_alignment_end'] = 'Bottom';
$string['commerce_storefront_composer_alignment_stretch'] = 'Stretch';
$string['commerce_storefront_responsive_preview'] = 'Responsive preview';
$string['commerce_storefront_responsive_preview_help'] = 'Simulate the builder display width without leaving the page.';
$string['commerce_storefront_preview_desktop'] = 'Desktop';
$string['commerce_storefront_preview_tablet'] = 'Tablet';
$string['commerce_storefront_preview_mobile'] = 'Mobile';
$string['commerce_storefront_composer_templates'] = 'Composer templates';
$string['commerce_storefront_composer_templates_help'] = 'Add a ready-to-customise structure. Existing sections are never replaced.';
$string['commerce_storefront_composer_template'] = 'Template to insert';
$string['commerce_storefront_composer_template_insert'] = 'Insert template';
$string['commerce_storefront_composer_template_sales'] = 'Sales page';
$string['commerce_storefront_composer_template_course'] = 'Course';
$string['commerce_storefront_composer_template_digital'] = 'Digital product';
$string['commerce_storefront_composer_template_bundle'] = 'Bundle';

$string['commerce_storefront_section_timeline'] = 'Timeline';
$string['commerce_storefront_section_comparison'] = 'Comparison';
$string['commerce_storefront_section_accordion'] = 'Accordion';
$string['commerce_storefront_section_style_glass'] = 'Frosted glass';
$string['commerce_storefront_section_style_gradient'] = 'Premium gradient';
$string['commerce_storefront_section_style_minimal'] = 'Minimal';
$string['commerce_storefront_premium_presentation'] = 'Premium presentation';
$string['commerce_storefront_premium_presentation_default'] = 'Standard';
$string['commerce_storefront_premium_presentation_split'] = 'Split composition';
$string['commerce_storefront_premium_presentation_overlay'] = 'Immersive overlay';
$string['commerce_storefront_premium_presentation_cards'] = 'Premium cards';
$string['commerce_storefront_premium_presentation_carousel'] = 'Horizontal carousel';
$string['commerce_storefront_premium_presentation_masonry'] = 'Masonry gallery';
$string['commerce_storefront_premium_presentation_timeline'] = 'Timeline';
$string['commerce_storefront_premium_presentation_comparison'] = 'Comparison';
$string['commerce_storefront_premium_presentation_premium'] = 'Premium CampusFR';
$string['commerce_storefront_premium_presentation_statement'] = 'Statement / transition';
$string['commerce_storefront_premium_presentation_feature'] = 'Product feature';
$string['commerce_storefront_premium_presentation_commerce'] = 'Premium commerce';
$string['commerce_storefront_premium_animation'] = 'Entrance animation';
$string['commerce_storefront_premium_animation_none'] = 'None';
$string['commerce_storefront_premium_animation_fade'] = 'Fade';
$string['commerce_storefront_premium_animation_slide_up'] = 'Slide up';
$string['commerce_storefront_premium_animation_zoom'] = 'Subtle zoom';

$string['commerce_storefront_shell_title'] = 'Global Storefront layout';
$string['commerce_storefront_commerce_position'] = 'Commerce panel position';
$string['commerce_storefront_commerce_position_hero'] = 'Integrated into Hero';
$string['commerce_storefront_commerce_position_below'] = 'Below Hero';
$string['commerce_storefront_commerce_position_sidebar'] = 'Sticky sidebar';
$string['commerce_storefront_commerce_position_intro'] = 'After introduction';
$string['commerce_storefront_commerce_position_bottom'] = 'Page bottom';
$string['commerce_storefront_shell_mode'] = 'Moodle shell';
$string['commerce_storefront_shell_standard'] = 'Standard Edly';
$string['commerce_storefront_shell_fullwidth'] = 'Full-width Edly';
$string['commerce_storefront_shell_landing'] = 'Landing page';
$string['commerce_storefront_shell_immersive'] = 'Immersive';
$string['commerce_storefront_layout_visibility'] = 'Edly shell visibility';
$string['commerce_storefront_show_header'] = 'Show Edly header';
$string['commerce_storefront_show_footer'] = 'Show Edly footer';
$string['commerce_storefront_section_save'] = 'Save this block';
$string['commerce_storefront_section_saved'] = 'Block saved';
$string['commerce_storefront_section_save_error'] = 'This block could not be saved.';

$string['commerce_storefront_reset_title'] = 'Reset Storefront page';
$string['commerce_storefront_reset_help'] = 'Removes the complete Storefront configuration and media attached to its sections. The product, prices and entitlements are not changed.';
$string['commerce_storefront_reset_button'] = 'Delete Storefront configuration';
$string['commerce_storefront_reset_confirm_title'] = 'Delete the complete Storefront configuration?';
$string['commerce_storefront_reset_confirm_help'] = 'This permanently deletes sections, layout settings, SEO and Storefront files for this product. This action cannot be undone.';
$string['commerce_storefront_reset_confirm_button'] = 'Yes, delete the Storefront';
$string['commerce_storefront_reset_success'] = 'The Storefront configuration and its files have been deleted.';
$string['commerce_storefront_package_title'] = 'Storefront page transfer';
$string['commerce_storefront_package_help'] = 'Export or import the complete Storefront page configuration, including media, in a .cfrproduct file.';
$string['commerce_storefront_package_export'] = 'Export configuration';
$string['commerce_storefront_package_import'] = 'Import configuration';
$string['commerce_storefront_package_file'] = '.cfrproduct file';
$string['commerce_storefront_package_import_success'] = 'The Storefront page configuration was imported.';
$string['commerce_storefront_package_invalid'] = 'The Storefront file is invalid or incompatible.';
$string['commerce_storefront_global_zones_title'] = 'Global page organisation';
$string['commerce_storefront_global_zones_help'] = 'Drag the zones to position the Commerce panel in the page journey. Alt + Up/Down also works from the keyboard.';
$string['commerce_storefront_global_zone_hero'] = 'Hero';
$string['commerce_storefront_global_zone_commerce'] = 'Commerce panel';
$string['commerce_storefront_global_zone_content'] = 'Editorial content';
$string['commerce_storefront_global_zone_recommendations'] = 'Recommendations';
$string['commerce_storefront_media_audit_title'] = 'Storefront media audit';

$string['commerce_storefront_image_position_left'] = 'Left';
$string['commerce_storefront_image_position_right'] = 'Right';

// J9A — CRM purchase public references and navigation.
$string['commerce_purchase_public_reference'] = 'Order number';
$string['commerce_purchase_internal_reference'] = 'Internal reference';
$string['commerce_purchase_internal_reference_short'] = 'Internal';
$string['commerce_purchase_open_order_details'] = 'Open Order Details';

$string['commerce_purchase_download_invoice'] = 'Download invoice';
$string['commerce_purchase_open_mail_journal'] = 'View email journal';
$string['commerce_purchase_resend_receipt'] = 'Resend receipt';
$string['commerce_purchase_resend_receipt_confirm'] = 'A new receipt will be sent to the customer email address and logged as a manual delivery. Continue?';
$string['commerce_purchase_receipt_resent'] = 'The receipt was resent successfully.';
$string['commerce_purchase_receipt_queued'] = 'The receipt was queued and will be retried automatically.';
$string['commerce_purchase_receipt_resend_failed'] = 'The receipt could not be resent. Review the email journal for diagnostics.';

// J10A — My Campus student hub.
$string['commerce_customer_hub_title'] = 'My Campus';
$string['commerce_customer_hub_eyebrow'] = 'Your personal space';
$string['commerce_customer_hub_welcome'] = 'Hello {$a}!';
$string['commerce_customer_hub_intro'] = 'Find your courses, resources, purchases and CampusFR progress in one place.';
$string['commerce_customer_hub_shortcuts'] = 'My Campus shortcuts';
$string['commerce_customer_hub_courses'] = 'My courses';
$string['commerce_customer_hub_resources'] = 'My resources';
$string['commerce_customer_hub_purchases'] = 'My purchases';
$string['commerce_customer_hub_profile'] = 'My profile';
$string['commerce_customer_hub_profile_help'] = 'Information and preferences';
$string['commerce_customer_hub_available'] = 'available';
$string['commerce_customer_hub_orders'] = 'order(s)';
$string['commerce_customer_hub_continue'] = 'Continue learning';
$string['commerce_customer_hub_view_all'] = 'View all';
$string['commerce_customer_hub_no_courses'] = 'No course is available in your space yet.';
$string['commerce_customer_hub_discover'] = 'Explore the store';
$string['commerce_customer_hub_xp_title'] = 'My progress';
$string['commerce_customer_hub_level'] = 'Level';
$string['commerce_customer_hub_total_xp'] = 'Total XP';
$string['commerce_customer_hub_xp_30d'] = 'Last 30 days';
$string['commerce_customer_hub_xp_ranking'] = 'Ranking';
$string['commerce_customer_hub_last_activity'] = 'Last activity';
$string['commerce_customer_hub_xp_no_activity'] = 'No recent activity';
$string['commerce_customer_hub_xp_unavailable'] = 'Your LevelXP progress will appear here when available.';

// J10A.1 — Navigation du parcours étudiant.
$string['commerce_customer_hub_shop'] = 'Shop';
$string['commerce_customer_hub_shop_help'] = 'Discover CampusFR courses and resources';
$string['commerce_i2_my_campus'] = 'Go to My Campus';

$string['commerce_routes_product_title'] = 'Public product URL';
$string['commerce_routes_product_help'] = 'Define a memorable slug for each language. Leave blank to keep the technical URL.';
$string['commerce_routes_slug_fr'] = 'French slug';
$string['commerce_routes_slug_en'] = 'English slug';
$string['commerce_routes_slug_ru'] = 'Russian slug';
$string['commerce_route_not_found'] = 'This CampusFR page could not be found.';

$string['commerce_storefront_filters_toggle'] = 'Search and filters';

$string['commerce_guest_activation_security_match'] = 'Both passwords match';


// J12E — Guest Checkout security and account finalisation.
$string['commerce_guest_checkout_other_email'] = 'Use another email address';
$string['commerce_guest_checkout_email_valid'] = 'Valid email address';
$string['commerce_guest_checkout_email_invalid_live'] = 'Enter a valid email address.';
$string['commerce_guest_activation_modal_title'] = 'Finish setting up your account to access your courses';
$string['commerce_guest_activation_modal_message'] = 'Your purchase is confirmed. Create your password now to open your courses without being sent to a login page that you cannot use yet.';
$string['commerce_guest_activation_modal_courses'] = 'Access your courses';
$string['commerce_guest_activation_modal_resources'] = 'Find your resources and downloads';
$string['commerce_guest_activation_modal_orders'] = 'View your purchases and future orders';
$string['commerce_guest_activation_modal_primary'] = 'Create my account';
$string['commerce_guest_activation_modal_later'] = 'Later';
$string['commerce_guest_activation_ready_confirmation'] = 'Your CampusFR account is now ready.';

// J12H — Support experience and CRM Inbox.
$string['commerce_support_page_title_generic'] = 'CampusFR Support';
$string['commerce_support_default_subject_generic'] = 'CampusFR support request';
$string['commerce_support_back_to_campus'] = 'Back to My Campus';
$string['commerce_support_gustave_alt'] = 'Gustave, CampusFR support adviser';
$string['commerce_support_visual_title'] = 'Need help?';
$string['commerce_support_visual_text'] = 'Our team is here to help you.';
$string['commerce_support_confirmation_title'] = 'Request sent successfully!';
$string['commerce_support_confirmation_intro'] = 'We have received your request. The CampusFR team will reply as soon as possible.';
$string['commerce_support_reference'] = 'Request reference';
$string['commerce_support_return_to_campus'] = 'Back to My Campus';
$string['commerce_support_mail_technical_heading'] = 'Technical information';
$string['commerce_support_mail_message_heading'] = 'Customer message';
$string['commerce_support_category_account'] = 'My account';
$string['commerce_support_category_technical'] = 'Technical issue';
$string['commerce_support_category_course_question'] = 'Question about a course';
$string['commerce_support_status_paid'] = 'Paid';
$string['commerce_support_status_completed'] = 'Completed';
$string['commerce_support_status_pending'] = 'Pending';
$string['commerce_support_status_failed'] = 'Failed';
$string['commerce_support_status_cancelled'] = 'Cancelled';
$string['commerce_support_status_refunded'] = 'Refunded';
$string['commerce_support_status_partial'] = 'Partial';
$string['commerce_support_status_processing'] = 'Processing';
$string['commerce_support_status_succeeded'] = 'Succeeded';
$string['commerce_customer_hub_view_profile'] = 'View my profile';
$string['commerce_customer_hub_support'] = 'Support';
$string['commerce_customer_hub_support_help_short'] = 'Need help?';
$string['commerce_storefront_currency_displayed'] = 'Currency';
$string['crm_inbox_direction_incoming'] = 'Received';
$string['crm_inbox_direction_outgoing'] = 'Sent';

// Commerce Showroom.
$string['commerce_showroom_not_found'] = 'This presentation page could not be found.';
$string['commerce_showroom_third_group_verbs_title'] = 'Master French third-group verbs at last';
$string['commerce_showroom_third_group_verbs_description'] = 'Discover the upcoming CampusFR experience dedicated to French third-group verbs: an immersive course, a PDF resource and a complete bundle.';
$string['commerce_showroom_eyebrow'] = 'New on CampusFR';
$string['commerce_showroom_foundation_note'] = 'J13B technical foundation — final design and marketing content will arrive in J13D.';
$string['commerce_showroom_offers_heading'] = 'Choose your option';
$string['commerce_showroom_offer_course'] = 'Interactive course';
$string['commerce_showroom_offer_pdf'] = 'PDF guide';
$string['commerce_showroom_offer_bundle'] = 'Course + PDF bundle';
$string['commerce_showroom_offer_pending'] = 'This product will be connected to the Showroom as soon as it is created in the Commerce catalogue.';
$string['commerce_showroom_price_pending'] = 'Coming soon';
$string['commerce_showroom_buy_now'] = 'Buy now';
$string['commerce_showroom_view_details'] = 'View details';
$string['commerce_showroom_back_to_shop'] = 'View the full shop';

$string['commerce_showroom_owned_access'] = 'Access product';

// J13D — Third-group verbs Showroom.
$string['commerce_showroom_hero_cta'] = 'Choose my climb';
$string['commerce_showroom_hero_secondary_cta'] = 'Watch the presentation';
$string['commerce_showroom_hero_proof'] = 'Immediate access after payment · designed for practice until automatic';
$string['commerce_showroom_problem_eyebrow'] = 'Why does it feel so difficult?';
$string['commerce_showroom_problem_title'] = 'Third-group verbs look complicated for the wrong reasons';
$string['commerce_showroom_problem_description'] = 'The issue is not your memory: isolated lists, context-free tables and too little guided repetition make the forms disappear.';
$string['commerce_showroom_method_title'] = 'Learn verbs the way you learn to drive';
$string['commerce_showroom_method_description'] = 'Observe, practise and repeat until the right response comes naturally.';
$string['commerce_showroom_video_title'] = 'What awaits you on your journey to the summit of third-group verbs?';
$string['commerce_showroom_video_description'] = 'Watch a short presentation of the trainer and discover how ordinary verb study turns into a real ascent to the summit of Mont Blanc.';
$string['commerce_showroom_video_placeholder'] = 'Presentation video coming soon';
$string['commerce_showroom_content_eyebrow'] = 'Real training, not another list';
$string['commerce_showroom_content_title'] = 'All essential verbs in one adventure';
$string['commerce_showroom_content_description'] = 'Climb 30 stages from base camp to the summit, with Gustave as your guide.';
$string['commerce_showroom_journey_title'] = 'What happens in each stage?';
$string['commerce_showroom_journey_description'] = 'Every group follows the same effective routine.';
$string['commerce_showroom_exercises_title'] = 'More than 10 types of exercises';
$string['commerce_showroom_exercises_description'] =
    'Each exercise trains your memory in a different way, which is why you practise each verb in several different ways.';
$string['commerce_showroom_offers_description'] = 'Choose the format that fits your learning style. The complete bundle combines interactive practice and the PDF reference.';
$string['commerce_showroom_offer_featured'] = 'Complete offer';
$string['commerce_showroom_bonus_heading'] = 'Complete your learning toolkit';
$string['commerce_showroom_bonus_text'] = 'Discover the CampusFR third-group verb cards for quick revision anywhere.';
$string['commerce_showroom_bonus_cta'] = 'Explore other resources';
$string['commerce_showroom_faq_heading'] = 'Frequently asked questions';
$string['commerce_showroom_final_eyebrow'] = 'The summit is waiting';
$string['commerce_showroom_final_title'] = 'Ready to make third-group verbs automatic?';
$string['commerce_showroom_final_text'] = 'Choose your format, start the first stage and climb to the summit with Gustave.';
$string['commerce_showroom_final_cta'] = 'Start now';
$string['commerce_showroom_problem_1_title'] = 'No single rule';
$string['commerce_showroom_problem_1_text'] = 'Forms change from one verb to another and patterns overlap.';
$string['commerce_showroom_problem_2_title'] = 'The right form disappears';
$string['commerce_showroom_problem_2_text'] = 'You know the verb, but the conjugation does not come at the right moment.';
$string['commerce_showroom_problem_3_title'] = 'Too many tables';
$string['commerce_showroom_problem_3_text'] = 'Reading a conjugation is not enough to make it available in speech.';
$string['commerce_showroom_problem_4_title'] = 'Not enough practice';
$string['commerce_showroom_problem_4_text'] = 'Without regular retrieval, learned forms quickly fade.';
$string['commerce_showroom_method_1_title'] = 'Understand the pattern';
$string['commerce_showroom_method_1_text'] = 'Spot useful forms and regularities without drowning in theory.';
$string['commerce_showroom_method_2_title'] = 'Move stage by stage';
$string['commerce_showroom_method_2_text'] = 'Each climb introduces six verbs and a clear progression.';
$string['commerce_showroom_method_3_title'] = 'Build automatic recall';
$string['commerce_showroom_method_3_text'] = 'More than ten exercise formats reactivate the same forms in different ways.';
$string['commerce_showroom_stat_1_title'] = '30 stages';
$string['commerce_showroom_stat_1_text'] = 'A clear and motivating progression.';
$string['commerce_showroom_stat_2_title'] = '180 verbs';
$string['commerce_showroom_stat_2_text'] = 'High-frequency verbs and their families.';
$string['commerce_showroom_stat_3_title'] = 'Native audio';
$string['commerce_showroom_stat_3_text'] = 'Hear the forms before producing them.';
$string['commerce_showroom_stat_4_title'] = 'Smart repetition';
$string['commerce_showroom_stat_4_text'] = 'The same forms return at the right time.';
$string['commerce_showroom_stat_5_title'] = 'Tests and rewards';
$string['commerce_showroom_stat_5_text'] = 'Validate every stage and track progress.';
$string['commerce_showroom_stat_6_title'] = 'Alpine universe';
$string['commerce_showroom_stat_6_text'] = 'A visual adventure around Mont Blanc.';
$string['commerce_showroom_journey_1_title'] = 'Discover the meaning';
$string['commerce_showroom_journey_1_text'] = 'Understand the verb and its most useful uses.';
$string['commerce_showroom_journey_2_title'] = 'Listen to the forms';
$string['commerce_showroom_journey_2_text'] = 'Connect conjugation with correct pronunciation.';
$string['commerce_showroom_journey_3_title'] = 'Rebuild';
$string['commerce_showroom_journey_3_text'] = 'Assemble forms and spot regularities.';
$string['commerce_showroom_journey_4_title'] = 'Produce';
$string['commerce_showroom_journey_4_text'] = 'Complete, write and choose the right form in context.';
$string['commerce_showroom_journey_5_title'] = 'Validate';
$string['commerce_showroom_journey_5_text'] = 'Take a final quiz and reinforce weak points.';
$string['commerce_showroom_journey_6_title'] = 'Keep climbing';
$string['commerce_showroom_journey_6_text'] = 'Unlock the next stage and move toward the summit.';
$string['commerce_showroom_exercise_1_title'] = 'Drag and drop';
$string['commerce_showroom_exercise_1_text'] = 'Match pronouns and forms.';
$string['commerce_showroom_exercise_2_title'] = 'Multiple choice';
$string['commerce_showroom_exercise_2_text'] = 'Identify the right answer quickly.';
$string['commerce_showroom_exercise_3_title'] = 'True or false';
$string['commerce_showroom_exercise_3_text'] = 'Detect incorrect forms.';
$string['commerce_showroom_exercise_4_title'] = 'Find the form';
$string['commerce_showroom_exercise_4_text'] = 'Spot the target form in a sentence.';
$string['commerce_showroom_exercise_5_title'] = 'Build the word';
$string['commerce_showroom_exercise_5_text'] = 'Reconstruct the verb letter by letter.';
$string['commerce_showroom_exercise_6_title'] = 'Fill in the blank';
$string['commerce_showroom_exercise_6_text'] = 'Produce the expected form in context.';
$string['commerce_showroom_exercise_7_title'] = 'Audio dictation';
$string['commerce_showroom_exercise_7_text'] = 'Write what you hear.';
$string['commerce_showroom_exercise_8_title'] = 'Targeted translation';
$string['commerce_showroom_exercise_8_text'] = 'Retrieve the French infinitive.';
$string['commerce_showroom_exercise_9_title'] = 'Quick response';
$string['commerce_showroom_exercise_9_text'] = 'Make the form appear without hesitation.';
$string['commerce_showroom_exercise_10_title'] = 'Final quiz';
$string['commerce_showroom_exercise_10_text'] = 'Validate the stage before continuing.';
$string['commerce_showroom_offer_course_feature_1'] = '30 interactive stages';
$string['commerce_showroom_offer_course_feature_2'] = '180 third-group verbs';
$string['commerce_showroom_offer_course_feature_3'] = 'Audio, quizzes and repetition';
$string['commerce_showroom_offer_course_feature_4'] = 'Progress and rewards';
$string['commerce_showroom_offer_pdf_feature_1'] = 'Clear structured guide';
$string['commerce_showroom_offer_pdf_feature_2'] = 'Tables and verb families';
$string['commerce_showroom_offer_pdf_feature_3'] = 'Offline consultation';
$string['commerce_showroom_offer_pdf_feature_4'] = 'Immediate download access';
$string['commerce_showroom_offer_bundle_feature_1'] = 'Complete interactive course';
$string['commerce_showroom_offer_bundle_feature_2'] = 'PDF guide included';
$string['commerce_showroom_offer_bundle_feature_3'] = 'Best value';
$string['commerce_showroom_offer_bundle_feature_4'] = 'Everything to revise and practise';
$string['commerce_showroom_faq_1_q'] = 'What level is the trainer suitable for?';
$string['commerce_showroom_faq_1_a'] = 'The trainer is suitable for every level. If you are just starting to learn French, it will help you build a solid foundation. If you already speak French, you can organise your knowledge and confidently master verbs encountered at B1 level and above.';
$string['commerce_showroom_faq_2_q'] = 'How much time will I need?';
$string['commerce_showroom_faq_2_a'] = 'Everyone has their own climb. It all depends on your pace. You can complete a training session in one go or split it across several sessions. There is no need to rush or follow a rigid schedule. The important thing is to practise regularly. Consistent practice is what helps make verb forms automatic.';
$string['commerce_showroom_faq_3_q'] = 'Can I repeat the exercises?';
$string['commerce_showroom_faq_3_a'] = 'Yes. You can repeat every exercise as many times as you need. Repetition is precisely what helps make verb forms automatic.';
$string['commerce_showroom_faq_4_q'] = 'Does the trainer work on a phone?';
$string['commerce_showroom_faq_4_a'] = 'Yes. The trainer is fully adapted for computers, tablets and smartphones. You can practise at home, while travelling or whenever you have a free moment.';
$string['commerce_showroom_faq_5_q'] = 'What is included in the digital cards?';
$string['commerce_showroom_faq_5_a'] = '178 cards covering all third-group verbs in modern French. Each card includes the translation, all present-tense forms, the past participle, the future-simple stem and audio recorded by a native speaker. You can use the cards digitally or print them.';
$string['commerce_showroom_faq_6_q'] = 'What is the difference between the trainer and the complete bundle?';
$string['commerce_showroom_faq_6_a'] = 'The trainer helps you make verb use automatic through interactive practice. The cards complement your learning: they let you quickly find the conjugation you need, keep all the forms close at hand and revise even without Internet access. The complete bundle combines both formats so you can both practise and conveniently review the material whenever you want.';
$string['commerce_showroom_faq_7_q'] = 'How do I get access after purchase?';
$string['commerce_showroom_faq_7_a'] = 'Immediately after payment, you will receive an email with all the information you need to access the content. If you already have an account on the CampusFR platform, you will also receive a purchase confirmation and the trainer will automatically appear under “My courses”. No additional action is required — you can start straight away.';
$string['commerce_storefront_showroom_media_title'] = 'Showroom';
$string['commerce_storefront_showroom_media_help'] = 'Link this product to a Showroom page and upload a dedicated marketing visual. This visual takes priority in Showroom offer cards.';
$string['commerce_storefront_showroom_key'] = 'Linked Showroom';
$string['commerce_storefront_showroom_image'] = 'Showroom visual';
$string['commerce_storefront_showroom_alt'] = 'Visual alternative text';
$string['commerce_storefront_showroom_link'] = 'Discover the full presentation';

// J13F1 — Third-group verbs Showroom premium Hero.
$string['commerce_showroom_hero_expedition'] = 'Mont Blanc expedition';
$string['commerce_showroom_hero_stage'] = 'Stage 0 / 30';
$string['commerce_showroom_hero_stat_verbs'] = 'verbs to master';
$string['commerce_showroom_hero_stat_stages'] = 'progressive stages';
$string['commerce_showroom_hero_stat_exercises'] = 'exercises and challenges';
$string['commerce_showroom_hero_stat_lifetime_value'] = 'Instant';
$string['commerce_showroom_hero_stat_lifetime'] = 'after purchase';
$string['commerce_showroom_hero_summary'] = 'More than 4,000 exercises, quizzes, audio, videos, rewards and a complete progression all the way to the summit of Mont Blanc.';
$string['commerce_showroom_hero_cta_start'] = 'Start my ascent';
$string['commerce_showroom_hero_cta_resume'] = 'Resume my ascent';
$string['commerce_showroom_hero_cta_complete_course'] = 'Add the course';
$string['commerce_showroom_hero_cta_complete_pdf'] = 'Add the PDF to my gear';

// J13F2 — immersive journey and exercise explorer.
$string['commerce_showroom_ascent_eyebrow'] = 'The 30-stage ascent';
$string['commerce_showroom_ascent_title'] = 'From the valley to the summit, every stage builds automatic recall';
$string['commerce_showroom_ascent_description'] = 'The route follows a genuine progression: essential verbs lead the way, then difficulty rises gradually until the most demanding forms.';
$string['commerce_showroom_ascent_aria'] = 'The 30-stage training route to the summit of Mont Blanc';
$string['commerce_showroom_ascent_stages'] = 'Stages {$a}';
$string['commerce_showroom_ascent_1_title'] = 'Base camp';
$string['commerce_showroom_ascent_1_text'] = 'Essential verbs and first reflexes. You learn the method and build confidence.';
$string['commerce_showroom_ascent_2_title'] = 'Alpine forest';
$string['commerce_showroom_ascent_2_text'] = 'Form families become recognisable. Repetition starts turning knowledge into instinct.';
$string['commerce_showroom_ascent_3_title'] = 'Rocky passage';
$string['commerce_showroom_ascent_3_text'] = 'You tackle the most common irregular verbs through varied, focused practice.';
$string['commerce_showroom_ascent_4_title'] = 'Glacier crossing';
$string['commerce_showroom_ascent_4_text'] = 'Rare and difficult forms are reinforced without losing pace or motivation.';
$string['commerce_showroom_ascent_5_title'] = 'Mont Blanc summit';
$string['commerce_showroom_ascent_5_text'] = 'All 180 verbs have been learned, tested and are ready to appear naturally in your sentences.';
$string['commerce_showroom_ascent_legend_1'] = 'One validated stage at a time';
$string['commerce_showroom_ascent_legend_2'] = 'The route unlocks progressively';
$string['commerce_showroom_ascent_legend_3'] = 'A reward at every arrival';
$string['commerce_showroom_exercises_eyebrow'] = 'SEE HOW THE TRAINING WORKS';
$string['commerce_showroom_exercises_aria'] = 'Choose an exercise type to display its preview';
$string['commerce_showroom_exercises_preview_label'] = 'Interactive preview';
$string['commerce_showroom_exercises_preview_step'] = 'Inside a CampusFR stage';
$string['commerce_showroom_exercises_preview_caption'] = 'Select an exercise to discover another way to memorise.';

// J13F3 — offer comparison.
$string['commerce_showroom_comparison_eyebrow'] = 'CHOOSE YOUR GEAR';
$string['commerce_showroom_comparison_title'] = 'Compare your gear options';
$string['commerce_showroom_comparison_description'] = 'There are different routes to the summit. Choose yours.';
$string['commerce_showroom_comparison_feature'] = 'Criteria';
$string['commerce_showroom_comparison_included'] = 'Included';
$string['commerce_showroom_comparison_not_included'] = 'Not included';
$string['commerce_showroom_comparison_bundle_badge'] = 'Recommended';
$string['commerce_showroom_comparison_interactive_course'] = 'Complete interactive training';
$string['commerce_showroom_comparison_downloadable_pdf'] = 'Downloadable PDF';
$string['commerce_showroom_comparison_verbs_180'] = 'All 180 verbs in the journey';
$string['commerce_showroom_comparison_exercises_4000'] = 'More than 4,000 exercises';
$string['commerce_showroom_comparison_audio_video'] = 'Audio, videos and rewards';
$string['commerce_showroom_comparison_offline_revision'] = 'Offline revision';
$string['commerce_showroom_comparison_lifetime_access'] = 'Lifetime access';


// J13F4 — Showroom reassurance and conversion.
$string['commerce_showroom_video_close'] = 'Close video';
$string['commerce_showroom_why_eyebrow'] = 'Built for long-term memory';
$string['commerce_showroom_why_title'] = 'Why this method works';
$string['commerce_showroom_why_description'] = 'You do not memorise a list once: you meet the forms again in varied contexts until they become natural.';
$string['commerce_showroom_why_1_title'] = 'Smart repetition';
$string['commerce_showroom_why_1_text'] = 'Forms return at the right time, without pointless cramming.';
$string['commerce_showroom_why_2_title'] = 'Varied contexts';
$string['commerce_showroom_why_2_text'] = 'Each verb is reused in several practical situations.';
$string['commerce_showroom_why_3_title'] = 'Listening and production';
$string['commerce_showroom_why_3_text'] = 'Audio connects written forms with the French you actually hear.';
$string['commerce_showroom_why_4_title'] = 'Motivating progress';
$string['commerce_showroom_why_4_text'] = 'Quizzes, stages and rewards keep you moving forward.';
$string['commerce_showroom_why_5_title'] = 'Lasting memory';
$string['commerce_showroom_why_5_text'] = 'Repeated practice gradually turns answers into reflexes.';
$string['commerce_showroom_trust_1_title'] = 'Secure payment';
$string['commerce_showroom_trust_1_text'] = 'Protected CampusFR checkout';
$string['commerce_showroom_trust_2_title'] = 'Immediate access';
$string['commerce_showroom_trust_2_text'] = 'Start as soon as payment is confirmed';
$string['commerce_showroom_trust_3_title'] = 'Lifetime access';
$string['commerce_showroom_trust_3_text'] = 'Return to practise whenever you need';
$string['commerce_showroom_trust_4_title'] = 'CampusFR support';
$string['commerce_showroom_trust_4_text'] = 'A team available when you have a question';
$string['commerce_showroom_testimonials_eyebrow'] = 'Learning with CampusFR';
$string['commerce_showroom_testimonials_title'] = 'Ready for the summit';
$string['commerce_showroom_faq_eyebrow'] = 'PRE-CLIMB BRIEFING';
$string['commerce_showroom_faq_description'] = 'Answers to the questions that come up most often before starting the climb.';
$string['commerce_showroom_support_title'] = 'Still have questions before the climb?';
$string['commerce_showroom_support_text'] = 'We won’t send you to the summit without a safety line 😄
Gustave and the CampusFR team can help with access, purchases and any questions about the trainer.';
$string['commerce_showroom_support_cta'] = 'Contact support';
$string['commerce_showroom_expedition_card_label'] = 'Mont Blanc Expedition';
$string['commerce_showroom_expedition_card_stage'] = 'Stage 0 of 30';
$string['commerce_showroom_expedition_card_altitude'] = 'Starting altitude: 1,035 m';
$string['commerce_showroom_desktop_sticky_label'] = 'Complete pack';

$string['commerce_showroom_status_draft'] = 'Draft';
$string['commerce_showroom_status_review'] = 'Awaiting review';
$string['commerce_showroom_status_published'] = 'Published';
$string['commerce_showroom_status_archived'] = 'Archived';
$string['commerce_showroom_currency_update_error'] = 'The currency could not be changed right now. Please try again in a moment.';
$string['commerce_showroom_cms_title'] = 'Showrooms Commerce';
$string['commerce_showroom_cms_create'] = 'Créer un showroom';
$string['commerce_showroom_cms_edit'] = 'Éditer le showroom';
$string['commerce_showroom_cms_key'] = 'Clé technique';
$string['commerce_showroom_cms_slugs'] = 'URLs publiques';
$string['commerce_showroom_cms_template'] = 'Template Moodle';
$string['commerce_showroom_cms_blocks'] = 'Blocs du showroom';
$string['commerce_showroom_cms_blocks_help'] = 'Le premier lot J13G enregistre l’ordre et la configuration des blocs. Le builder visuel avec glisser-déposer arrivera dans J13G2.';
$string['capability:manage_showrooms'] = 'Gérer les showrooms Commerce';
$string['commerce_showroom_builder_help'] = 'Drag blocks to reorder them, enable or disable them, duplicate them and edit their configuration without leaving the page.';
$string['commerce_showroom_builder_choose_block'] = 'Choose a block type';
$string['commerce_showroom_builder_preview'] = 'Preview showroom';
$string['commerce_showroom_builder_edit_block'] = 'Configure block';
$string['commerce_showroom_builder_block_key'] = 'Block key';
$string['commerce_showroom_builder_configuration'] = 'JSON configuration';
$string['commerce_showroom_builder_configuration_help'] = 'J13G2 keeps advanced configuration in JSON. Type-specific forms will be introduced in the next delivery.';
$string['commerce_showroom_builder_toggle'] = 'Enable or disable';
$string['commerce_showroom_builder_confirm_delete'] = 'Permanently delete this block?';
$string['commerce_showroom_builder_saved'] = 'The showroom has been updated.';
$string['commerce_showroom_builder_advanced_json'] = 'Advanced JSON configuration';
$string['commerce_showroom_builder_live_preview'] = 'Block preview';
$string['commerce_showroom_builder_required'] = 'Required field';
$string['commerce_showroom_choose_template'] = 'Choose a template';
$string['commerce_showroom_apply_template'] = 'Apply template';
$string['commerce_showroom_export'] = 'Export';
$string['commerce_showroom_import'] = 'Import';
$string['commerce_showroom_import_help'] = 'Paste the contents of a .showroom.json file exported from CampusFR.';

// J13G5 showroom publication workflow.
$string['commerce_showroom_history'] = 'History';
$string['commerce_showroom_revision'] = 'Revision';
$string['commerce_showroom_revision_action'] = 'Action';
$string['commerce_showroom_revision_note'] = 'Publication note';
$string['commerce_showroom_restore_revision'] = 'Restore';
$string['commerce_showroom_revision_restored'] = 'The revision was restored as a draft.';
$string['commerce_showroom_no_revisions'] = 'No revisions have been recorded.';
$string['commerce_showroom_submit_review'] = 'Submit for review';
$string['commerce_showroom_publish'] = 'Publish';
$string['commerce_showroom_return_draft'] = 'Return to draft';
$string['commerce_showroom_submitted_review'] = 'The showroom was submitted for review.';
$string['commerce_showroom_published'] = 'The showroom was published and a revision was created.';
$string['commerce_showroom_returned_draft'] = 'The showroom is back in draft.';

$string['commerce_showroom_owned_compact'] = 'Already yours';

$string['commerce_showroom_bundle_partial_owned'] = 'You already own one item in this pack. Complete your equipment with the highlighted offer.';
$string['commerce_product_visual_format_showroom'] = 'Showroom visual — 16:9';
$string['commerce_product_visual_format_showroom_help'] = 'Showroom cards and marketing compositions. Recommended size: {$a}.';
$string['commerce_checkout_back_offer'] = 'Back to the offer';
$string['commerce_guest_checkout_identity_title'] = 'Your details';
$string['commerce_guest_checkout_identity_checkout_description'] = 'We use this information to secure your purchase and give you access to your products after payment.';

$string['commerce_checkout_terms_required'] = 'You must accept the terms of sale and privacy policy before continuing.';

$string['commerce_smart_terms'] = 'Terms and conditions';
$string['commerce_smart_privacy'] = 'Privacy policy';

$string['commerce_provider_experience_title'] = 'Confirm payment';
$string['commerce_provider_experience_message'] = 'You are about to be redirected to our secure payment platform.';
$string['commerce_provider_experience_continue'] = 'Continue';
$string['commerce_provider_experience_cancel'] = 'Cancel';
$string['commerce_provider_experience_other_method'] = 'Choose another payment method';
$string['commerce_provider_experience_stripe_title'] = 'Continue to secure payment';
$string['commerce_provider_experience_stripe_message'] = 'Review your purchase one last time before being redirected to Stripe.';
$string['commerce_provider_experience_stripe_advice'] = 'Secure payment: you stay in control and return automatically to CampusFR after payment.';
$string['commerce_provider_experience_stripe_continue'] = 'Continue to Stripe';
$string['commerce_provider_experience_alfa_title'] = 'Before continuing to Alfa';
$string['commerce_provider_experience_alfa_message'] = 'Alfa may have connection issues while a VPN is active.';
$string['commerce_provider_experience_alfa_advice'] = 'Before continuing, make sure your VPN is disabled to avoid Alfa page connection issues.';
$string['commerce_provider_experience_alfa_continue'] = 'Continue to Alfa';

$string['commerce_provider_experience_stay'] = 'Stay on CampusFR';
$string['commerce_provider_experience_alfa_standard_secondary'] = 'Close and choose another payment method';$string['commerce_cart_currency_switch'] = 'Cart currency';
$string['commerce_cart_currency_switch_help'] = 'Prices and discounts recalculated';
$string['commerce_cart_currency_switched'] = 'Your cart has been recalculated in {$a}.';
$string['commerce_cart_currency_removed_items'] = 'Unavailable in this currency and removed from the cart: {$a}.';
$string['commerce_cart_currency_promotion_removed'] = 'The promotion code was removed because it is not applicable in this currency.';
$string['commerce_provider_experience_alfa_other_currency'] = 'Pay in another currency';
$string['commerce_provider_currency_title'] = 'Choose another currency';
$string['commerce_provider_currency_message'] = 'Your cart will be recalculated using the prices, promotions and conditions available in the selected currency. Unavailable items may be removed.';
$string['commerce_provider_currency_submit'] = 'Recalculate my cart';
$string['commerce_provider_currency_empty'] = 'No other currency is currently available.';
$string['commerce_provider_currency_error'] = 'The available currencies could not be loaded. Close this window and try again.';

$string['commerce_cart_currency_removed_item_fallback'] = 'An item';

// J14F — Conditional customer promotions.
$string['commerce_cart_message_promotion_requires_login'] = 'Sign in to your CampusFR account to use this offer.';
$string['commerce_cart_message_promotion_missing_required_product'] = 'This offer is reserved for students who already own the required product.';
$string['commerce_cart_message_promotion_already_owns_excluded_product'] = 'This offer does not apply because you already own the discounted product.';
$string['commerce_cart_message_promotion_customer_not_eligible'] = 'Your account does not currently meet this offer’s conditions.';
$string['commerce_cart_message_promotion_customer_rule_runtime_unavailable'] = 'This offer cannot be verified at the moment.';
$string['commerce_promotion_customer_eligibility'] = 'Customer eligibility conditions';
$string['commerce_promotion_customer_eligibility_help'] = 'Reserve the promotion for selected students according to products they already own. These rules work with coupon codes and automatic discounts.';
$string['commerce_promotion_requires_login'] = 'Require a signed-in user';
$string['commerce_promotion_requires_login_help'] = 'The student must sign in before the promotion can be applied.';
$string['commerce_promotion_eligibility_mode'] = 'Condition combination';
$string['commerce_promotion_eligibility_mode_help'] = '“All” requires every condition. “At least one” requires only one condition.';
$string['commerce_promotion_eligibility_all'] = 'All conditions';
$string['commerce_promotion_eligibility_any'] = 'At least one condition';
$string['commerce_promotion_required_owned_products'] = 'Must already own';
$string['commerce_promotion_required_owned_products_help'] = 'Select products the student must already own to receive this promotion.';
$string['commerce_promotion_excluded_owned_products'] = 'Must not already own';
$string['commerce_promotion_excluded_owned_products_help'] = 'Select products the student must not own yet. This prevents discounting a product already purchased.';
$string['commerce_promotion_eligibility_everyone'] = 'All customers';
$string['commerce_promotion_eligibility_conditional'] = 'Conditional promotion';

// J14G1 — Order result polish.
$string['commerce_order_result_access_contents'] = 'Access my space';
$string['commerce_order_result_discover_store'] = 'Discover the shop';

// J15A4 — Default Showroom block configuration.
$string['commerce_showroom_builder_initialise_defaults'] = 'Initialise with current content';
$string['commerce_showroom_builder_confirm_defaults'] = 'Initialise blocks with an empty configuration? Existing customisations will not be changed.';
$string['commerce_showroom_builder_defaults_initialised'] = '{count} block(s) initialised with the current content.';

// J15B — CMS content runtime.
$string['commerce_showroom_back_to_list'] = 'Back to the Showroom list';
$string['commerce_showroom_stats_title'] = 'Programme figures';
$string['commerce_showroom_journey_eyebrow'] = 'Your progress';
$string['commerce_showroom_offers_title'] = 'Choose your offer';
$string['commerce_showroom_method_eyebrow'] = 'The CampusFR method';
$string['commerce_showroom_faq_title'] = 'Frequently asked questions';
$string['commerce_showroom_support_description'] = 'We won’t send you to the summit without a safety line 😄
Gustave and the CampusFR team can help with access, purchases and any questions about the trainer.';
$string['commerce_showroom_final_description'] = 'Choose your offer and begin your journey.';

// J15E1-2-3 — Showroom editor hardening.
$string['commerce_showroom_builder_advanced_json_help'] = 'Edited JSON becomes authoritative. Custom keys are preserved. Use “Apply JSON to fields” to reload the visual form.';
$string['commerce_showroom_builder_apply_json'] = 'Apply JSON to fields';
$string['commerce_showroom_builder_sync_json'] = 'Regenerate JSON from fields';
$string['commerce_showroom_builder_invalid_json'] = 'The JSON configuration is invalid.';
$string['commerce_showroom_builder_json_object_required'] = 'The configuration must be a JSON object.';

// J15E4 — Showroom media manager.
$string['commerce_showroom_media_choose'] = 'Choose an image';
$string['commerce_showroom_media_choose_video'] = 'Choose a video';
$string['commerce_showroom_media_remove'] = 'Remove image';
$string['commerce_showroom_media_remove_video'] = 'Remove video';
$string['commerce_showroom_media_uploading'] = 'Processing…';
$string['commerce_showroom_media_empty'] = 'No custom image. The default visual will be used.';
$string['commerce_showroom_media_empty_video'] = 'No custom video. The default content will be used.';
$string['commerce_showroom_media_uploaded'] = 'Image saved.';
$string['commerce_showroom_media_uploaded_video'] = 'Video saved.';

$string['commerce_showroom_video_play'] = 'Play video';
$string['commerce_showroom_video_pause'] = 'Pause the video';
$string['commerce_showroom_video_replay'] = 'Replay the video';

$string['commerce_mypurchases_store_link'] = 'Visit the store';

// J15H.1H — Mobile offer presentation.
$string['commerce_showroom_offers_badge'] = 'Plans';
$string['commerce_showroom_offers_title_prefix'] = 'Where will your';
$string['commerce_showroom_offers_title_highlight'] = 'ascent';
$string['commerce_showroom_offers_title_suffix'] = ' begin?';
$string['commerce_showroom_offers_subtitle'] = 'Whichever path you choose, we’ll be by your side — from the very first step all the way to the summit.';
$string['commerce_showroom_offers_slider_hint'] = 'Swipe to see the other offers';


// J15H.1I.1 — provisional account navigation and login guidance.
$string['commerce_guest_activation_nav_cta'] = 'Finish setting up my account';
$string['commerce_guest_login_notice_title'] = 'Your CampusFR account still needs a password';
$string['commerce_guest_login_notice_message'] = 'Your purchase is confirmed. You do not have a password yet: finish setting up your account instead of trying to sign in.';
$string['commerce_guest_login_notice_cta'] = 'Create my password';

$string['commerce_a11y_skip_to_content'] = 'Skip to main content';

$string['commerce_a11y_showroom_devices'] = 'Preview of CampusFR on desktop and mobile';

$string['commerce_a11y_key_figures'] = 'Key figures';

$string['commerce_showroom_builder_image_help'] = 'PNG, JPG or WebP • Recommended: 1920 × 1080 px • Maximum size: 20 MB';

$string['commerce_showroom_builder_video_help'] = 'MP4 or WebM • H.264, 1920 × 1080 recommended • Maximum size: 500 MB';

$string['commerce_price_currency_delete_title'] = 'Delete currency price';
$string['commerce_price_currency_delete_confirm'] = 'Delete the {$a} price? This product will no longer be available in this currency. Historical orders will not be changed.';
$string['commerce_price_currency_deleted'] = 'The {$a} price has been deleted.';
$string['crm_commerce_nav_identities'] = 'Identities';
$string['commerce_identity_reconciliation_title'] = 'Customer identity reconciliation';
$string['commerce_identity_reconciliation_description'] = 'Audit Native Commerce purchases without a linked Moodle account and diagnose possible email-based matches.';
$string['commerce_identity_reconciliation_dryrun_notice'] = 'This page is read-only. It analyses matches without changing data. Manual reconciliation runs only after an explicit protected action.';
$string['commerce_identity_unresolved_total'] = 'Unlinked purchases';
$string['commerce_identity_matched_on_page'] = 'Matches on this page';
$string['commerce_identity_not_found_on_page'] = 'No account on this page';
$string['commerce_identity_ambiguous_on_page'] = 'Ambiguous on this page';
$string['commerce_identity_filter_email'] = 'Filter by customer email';
$string['commerce_identity_reconciliation_empty'] = 'No unlinked Native Commerce purchase matches the criteria.';
$string['commerce_identity_purchase'] = 'Purchase';
$string['commerce_identity_email'] = 'Customer email';
$string['commerce_identity_diagnostic'] = 'Diagnostic';
$string['commerce_identity_candidate'] = 'Candidate account';
$string['commerce_identity_status_matched'] = 'Unique match';
$string['commerce_identity_status_not_found'] = 'No account';
$string['commerce_identity_status_ambiguous'] = 'Ambiguous';
$string['commerce_identity_status_skipped'] = 'Skipped';
$string['commerce_identity_status_unchanged'] = 'Already linked';
$string['commerce_identity_status_reconciled'] = 'Reconciled';
$string['commerce_identity_user_link'] = 'User #{$a}';
$string['commerce_identity_reconcile_action'] = 'Reconcile';
$string['commerce_identity_reconcile_confirm'] = 'This action will permanently link this Native purchase and compatible resources to the identified Moodle account. Use it only after validating the diagnostic.';
$string['commerce_identity_reconcile_success'] = 'Purchase {$a} was reconciled.';
$string['commerce_identity_reconcile_not_applied'] = 'Reconciliation was not applied. Current diagnostic: {$a}.';
$string['crm_commerce_nav_personal_offers'] = 'Personal offers';
$string['commerce_personal_offers_title'] = 'Personal offers';
$string['commerce_personal_offers_description'] = 'Individual Commerce offers assigned to a customer identity, a target Native product and versioned commercial terms.';
$string['commerce_personal_offers_readonly_notice'] = 'This screen is read-only in this phase. Offer creation, secure links, revocation and redemption are introduced by the following Personal Offer phases.';
$string['commerce_personal_offers_empty'] = 'No Personal Offer matches the current filters.';
$string['commerce_personal_offer_id'] = 'Offer';
$string['commerce_personal_offer_campaign'] = 'Campaign';
$string['commerce_personal_offer_email'] = 'Email';
$string['commerce_personal_offer_beneficiary'] = 'Beneficiary';
$string['commerce_personal_offer_target'] = 'Target product';
$string['commerce_personal_offer_pricing'] = 'Terms';
$string['commerce_personal_offer_validity'] = 'Validity';
$string['commerce_personal_offer_status'] = 'Status';
$string['commerce_personal_offer_status_issued'] = 'Issued';
$string['commerce_personal_offer_status_redeemed'] = 'Redeemed';
$string['commerce_personal_offer_status_revoked'] = 'Revoked';
$string['commerce_personal_offer_status_expired'] = 'Expired';
$string['commerce_personal_offer_not_found'] = 'Personal Offer not found.';
$string['commerce_personal_offer_not_revocable'] = 'This Personal Offer can no longer be revoked.';
$string['commerce_personal_offer_not_redeemable'] = 'This Personal Offer cannot be redeemed.';
$string['commerce_personal_offer_purchase_not_paid'] = 'The Personal Offer can only be consumed by a successfully paid Native Commerce purchase.';
$string['commerce_personal_offer_identity_mismatch'] = 'The purchase identity does not match the Personal Offer beneficiary.';
$string['commerce_personal_offer_campaign_source_missing'] = 'The Personal Offer campaign source product could not be found.';

$string['commerce_personal_offer_page_title'] = 'Your personal offer';
$string['commerce_personal_offer_link_unavailable'] = 'This personal offer link is invalid, expired, revoked, already used, or unavailable for this account.';
$string['commerce_personal_offer_back_store'] = 'Back to the store';
$string['commerce_personal_offer_target_mismatch'] = 'This personal offer does not apply to this product.';
$string['commerce_personal_offer_target_unavailable'] = 'The product attached to this personal offer is currently unavailable.';
$string['commerce_personal_offer_currency_unavailable'] = 'This personal offer is not available in the selected currency.';
$string['commerce_personal_offer_cart_failed'] = 'The personal offer could not be prepared for checkout.';

$string['commerce_personal_offers_admin_notice'] = 'Personal offers can be managed from their detail page. Exported secure links are personal and must be handled as sensitive data.';
$string['commerce_personal_offer_detail_title'] = 'Personal offer details';
$string['commerce_personal_offer_source_purchase'] = 'Source purchase';
$string['commerce_personal_offer_created'] = 'Issued at';
$string['commerce_personal_offer_redeemed_purchase'] = 'Redemption purchase';
$string['commerce_personal_offer_revocation'] = 'Revocation';
$string['commerce_personal_offer_secure_link'] = 'Secure personal link';
$string['commerce_personal_offer_revoke'] = 'Revoke offer';
$string['commerce_personal_offer_revoke_reason'] = 'Revocation reason (optional)';
$string['commerce_personal_offer_reissue'] = 'Reissue a new offer';
$string['commerce_personal_offer_validity_days'] = 'New validity period (days)';
$string['commerce_personal_offer_metadata'] = 'Metadata';
$string['commerce_personal_offer_revoked_success'] = 'The personal offer has been revoked.';
$string['commerce_personal_offer_reissued_success'] = 'A new personal offer has been issued from the previous offer.';
$string['commerce_personal_offer_reissue_active'] = 'An active offer cannot be reissued. Use its current link or revoke it first.';
$string['commerce_personal_offer_stats_title'] = 'Personal offer statistics';
$string['commerce_personal_offer_campaign_stats'] = 'Statistics by campaign';
$string['commerce_personal_offer_export'] = 'Export links as CSV';

$string['commerce_personal_offer_campaigns'] = "Personal Offer campaigns";
$string['commerce_personal_offer_new_campaign'] = "New campaign";
$string['commerce_personal_offer_campaigns_empty'] = "No Personal Offer campaign yet.";
$string['commerce_personal_offer_create_individual'] = "Create individual offer";
$string['commerce_personal_offer_create'] = "Create offer";
$string['commerce_personal_offer_audience'] = "Audience";
$string['commerce_personal_offer_audience_criteria'] = "CRM / purchase criteria";
$string['commerce_personal_offer_audience_list'] = "Explicit list (emails or user IDs)";
$string['commerce_personal_offer_source_sku'] = "Source product SKU";
$string['commerce_personal_offer_purchase_from'] = "Purchase from";
$string['commerce_personal_offer_purchase_to'] = "Purchase until";
$string['commerce_personal_offer_valid_from'] = "Valid from";
$string['commerce_personal_offer_expires_at'] = "Expires on";
$string['commerce_personal_offer_account_filter'] = "Moodle account required?";
$string['commerce_personal_offer_exclude_owned'] = "Exclude customers already owning target product";
$string['commerce_personal_offer_explicit_list'] = "Email or user ID list";
$string['commerce_personal_offer_amounts'] = "Amounts in minor units";
$string['commerce_personal_offer_percent'] = "Discount %";
$string['commerce_personal_offer_preview'] = "Preview / recalculate audience";
$string['commerce_personal_offer_generate'] = "Generate offers";

// Personal Offer CRM UX.
$string['commerce_personal_offer_create_individual_help'] = 'Create a one-off Personal Offer for a specific customer. The customer can have a Moodle account already, or only an email address.';
$string['commerce_personal_offer_email_help'] = 'Start typing an existing Moodle customer email. You can also enter a valid email that does not yet have an account.';
$string['commerce_personal_offer_campaign_optional'] = 'Campaign (optional)';
$string['commerce_personal_offer_campaign_none'] = 'No campaign — individual offer';
$string['commerce_personal_offer_campaign_optional_help'] = 'Attach this offer to an existing CRM campaign for reporting, or leave it as an individual offer.';
$string['commerce_personal_offer_source_purchase_optional'] = 'Source purchase (optional)';
$string['commerce_personal_offer_source_purchase_placeholder'] = 'Search by order reference';
$string['commerce_personal_offer_source_purchase_help'] = 'The historical purchase that justifies the offer. Leave empty for a VIP, goodwill or manually targeted offer.';
$string['commerce_personal_offer_target_help'] = 'The Native Commerce product that the customer will be able to buy with this Personal Offer.';
$string['commerce_personal_offer_strategy_fixed_price'] = 'Personal final price';
$string['commerce_personal_offer_strategy_fixed_discount'] = 'Fixed discount';
$string['commerce_personal_offer_strategy_percentage_discount'] = 'Percentage discount';
$string['commerce_personal_offer_pricing_help'] = 'Choose how the Personal Offer changes the public price. Only the selected strategy is applied.';
$string['commerce_personal_offer_amounts_display_title'] = 'Amounts by currency';
$string['commerce_personal_offer_amounts_display_help'] = 'Enter normal customer-facing amounts (for example 30.00 € or 2990.00 ₽). Commerce converts them internally; no minor units are required here.';
$string['commerce_personal_offer_valid_from_help'] = 'Optional. Leave empty for the offer to be usable immediately.';
$string['commerce_personal_offer_expires_at_help'] = 'Optional. After this date the offer remains in history but can no longer be used.';
$string['commerce_personal_offer_new_campaign_help'] = 'Define who should receive an offer and what commercial conditions they will receive. No offer or email is generated until you preview and confirm the population.';
$string['commerce_personal_offer_campaign_identity_title'] = 'Campaign identity';
$string['commerce_personal_offer_campaign_name_placeholder'] = 'Example: Historic cards buyers — Trainer launch';
$string['commerce_personal_offer_campaign_name_help'] = 'Human-readable name shown in the CRM. You can change your naming convention without affecting the technical identifier.';
$string['commerce_personal_offer_campaign_key'] = 'Campaign key';
$string['commerce_personal_offer_campaign_key_auto'] = 'Generated automatically if left empty';
$string['commerce_personal_offer_campaign_key_help'] = 'Stable technical identifier used by Commerce for idempotency and reporting. Usually leave it empty and let CRM generate it.';
$string['commerce_personal_offer_audience_title'] = 'Audience';
$string['commerce_personal_offer_audience_help'] = 'Criteria calculates the recipient list from Commerce data. Explicit list lets you provide selected customers directly.';
$string['commerce_personal_offer_source_sku_help'] = 'For criteria-based campaigns, choose the product that customers must already have purchased.';
$string['commerce_personal_offer_account_all'] = 'With or without a Moodle account';
$string['commerce_personal_offer_account_yes'] = 'Only customers with a Moodle account';
$string['commerce_personal_offer_account_no'] = 'Only customers without a Moodle account';
$string['commerce_personal_offer_account_filter_help'] = 'Filter recipients according to whether their Commerce identity is already linked to Moodle.';
$string['commerce_personal_offer_purchase_from_help'] = 'Optional lower purchase date bound for the source product.';
$string['commerce_personal_offer_purchase_to_help'] = 'Optional upper purchase date bound for the source product.';
$string['commerce_personal_offer_exclude_owned_help'] = 'Recommended: prevents offering a product to customers who already own it.';
$string['commerce_personal_offer_explicit_list_help'] = 'Use this only for an explicit-list audience. Pick known emails with autocomplete or paste one email per line.';
$string['commerce_personal_offer_recipient_picker_placeholder'] = 'Start typing a customer email';
$string['commerce_personal_offer_explicit_list_placeholder'] = 'One email per line';
$string['commerce_personal_offer_offer_title'] = 'Offer conditions';
$string['commerce_personal_offer_campaigns_help'] = 'Prepare, preview and generate Personal Offer campaigns. Email delivery will be managed separately.';
$string['commerce_personal_offer_campaign_view_help'] = 'Review the calculated population before generating offers. You can include or exclude individual recipients while the campaign is still in preparation.';
$string['commerce_personal_offer_metric_total'] = 'Population';
$string['commerce_personal_offer_metric_eligible'] = 'Selected';
$string['commerce_personal_offer_metric_excluded'] = 'Excluded';
$string['commerce_personal_offer_metric_error'] = 'Errors';
$string['commerce_personal_offer_metric_issued'] = 'Offers generated';
$string['commerce_personal_offer_criteria_generated_list_help'] = 'CRM simulation: this list materialises customers detected from the selected Legacy/Native source and current rules. No offer is created until generation is explicitly launched.';
$string['commerce_personal_offer_reason_manual_exclusion'] = 'Excluded manually';
$string['commerce_personal_offer_reason_target_owned'] = 'Already owns target product';
$string['commerce_personal_offer_reason_invalid_email'] = 'Invalid email';
$string['commerce_personal_offer_save_selection'] = 'Save recipient selection';
$string['commerce_personal_offer_campaign_preview_empty'] = 'Preview the campaign to calculate its recipient list.';
$string['commerce_personal_offer_detail_help'] = 'Review the recipient, commercial conditions, lifecycle and secure link of this Personal Offer.';
$string['commerce_personal_offer_stats_help'] = 'Global and campaign-level Personal Offer lifecycle statistics.';

// Personal Offer email campaign (K9).
$string['commerce_mail_type_personal_offer'] = 'Personal offer';
$string['commerce_mail_personal_offer_subject'] = 'A CampusFR offer reserved for you';
$string['commerce_mail_personal_offer_cta'] = 'View my offer';
$string['commerce_mail_personal_offer_card_label'] = 'Your personal offer';
$string['commerce_mail_personal_offer_expiry_label'] = 'Valid until:';
$string['task_process_personal_offer_mail_queue'] = 'Batched Personal Offer email delivery';
$string['settings:personal_offer_mail_header'] = 'Personal Offer emails';
$string['settings:personal_offer_mail_header_desc'] = 'Safety limits applied to Personal Offer marketing emails. Commerce transactional emails are not affected.';
$string['settings:personal_offer_mail_batch_size'] = 'Personal Offer batch size';
$string['settings:personal_offer_mail_batch_size_desc'] = 'Maximum Personal Offer emails sent per scheduled-task run. Conservative default: 20.';
$string['settings:personal_offer_mail_hourly_limit'] = 'Personal Offer hourly cap';
$string['settings:personal_offer_mail_hourly_limit_desc'] = 'Maximum Personal Offer emails sent over a rolling one-hour window. Adjust to the actual OVH limit before production.';
$string['commerce_personal_offer_mail_title'] = 'Send offers by email';
$string['commerce_personal_offer_mail_help'] = 'Emails are queued in the Commerce outbox and progressively sent by cron. Queueing does not trigger an immediate mass send.';
$string['commerce_personal_offer_mail_queue_campaign'] = 'Queue campaign emails';
$string['commerce_personal_offer_mail_queue_single'] = 'Send this offer by email';
$string['commerce_personal_offer_mail_queued_success'] = 'Personal Offer email added to the Commerce queue.';
$string['commerce_personal_offer_mail_campaign_queued'] = 'Email campaign prepared: {$a->queued} new queued emails, {$a->existing} already present, {$a->errors} error(s).';
$string['commerce_personal_offer_mail_notqueued'] = 'Not queued';
$string['commerce_personal_offer_mail_queued'] = 'Queued';
$string['commerce_personal_offer_mail_processing'] = 'Processing';
$string['commerce_personal_offer_mail_sent'] = 'Sent';
$string['commerce_personal_offer_mail_failed'] = 'Failed';
$string['commerce_personal_offer_mail_cancelled'] = 'Cancelled';
$string['commerce_personal_offer_mail_status'] = 'Email';
$string['commerce_personal_offer_mail_error'] = 'Last error';
$string['commerce_personal_offer_mail_studio'] = 'Edit email template';
$string['commerce_personal_offer_mail_log'] = 'Open Commerce mail log';
$string['commerce_personal_offer_mail_batch_notice'] = 'Delivery is intentionally batched to respect provider limits. Errors are retained in the Commerce log and automatically retried by the existing retry policy.';

// Personal Offer CRM identity/display hotfix (K9.1).
$string['commerce_identity_customer'] = 'Customer';
$string['commerce_personal_offer_beneficiary_search'] = 'Beneficiary';
$string['commerce_personal_offer_beneficiary_search_placeholder'] = 'Email, first name or last name';
$string['commerce_personal_offer_source_basis'] = 'Offer eligibility basis';
$string['commerce_personal_offer_source_basis_help'] = 'Choose how Commerce proves eligibility: no purchase required, ownership of a product, or one specific purchase.';
$string['commerce_personal_offer_source_none'] = 'None — standalone offer / goodwill gesture';
$string['commerce_personal_offer_source_product'] = 'Product ownership';
$string['commerce_personal_offer_source_purchase_mode'] = 'Specific purchase';
$string['commerce_personal_offer_source_purchase_help'] = 'Advanced option: choose one specific order. The CFR reference is shown first and the technical cmp_ reference remains visible for diagnostics.';
$string['commerce_personal_offer_email_help'] = 'Search Moodle accounts by first name, last name or email. You can also enter a valid email directly for a customer without a Moodle account.';

$string['commerce_personal_offer_edit'] = 'Edit offer';
$string['commerce_personal_offer_edit_help'] = 'Change the commercial terms of this offer. The original offer remains in the audit history and is revoked when the replacement is created.';
$string['commerce_personal_offer_edit_replace_notice'] = 'Editing does not overwrite the original offer. A replacement offer with a new secure link will be created and the current offer will be revoked.';
$string['commerce_personal_offer_delete'] = 'Delete';
$string['commerce_personal_offer_delete_confirm'] = 'Permanently delete this offer? This is only allowed for offers that have never been sent, redeemed, or attached to a campaign.';
$string['commerce_personal_offer_delete_not_allowed'] = 'This offer cannot be deleted because it has already been sent, redeemed, or belongs to a campaign. Revoke it instead.';
$string['commerce_personal_offer_deleted_success'] = 'The Personal Offer was deleted.';
$string['commerce_personal_offer_edit_not_allowed'] = 'Only an active Personal Offer can be modified.';
$string['commerce_personal_offer_replaced_success'] = 'The replacement offer was created and the previous offer was revoked.';
$string['commerce_personal_offer_terms_fixed_price_label'] = 'Personal price';
$string['commerce_personal_offer_terms_fixed_discount_label'] = 'Fixed discount';
$string['commerce_personal_offer_terms_percentage_label'] = 'Percentage discount';
$string['commerce_personal_offer_ownership_native_entitlement'] = 'Native entitlement';
$string['commerce_personal_offer_ownership_native_purchase'] = 'Native purchase';
$string['commerce_personal_offer_ownership_bundle'] = 'Ownership through bundle components';
$string['commerce_personal_offer_ownership_legacy_digital'] = 'Legacy digital purchase';
$string['commerce_personal_offer_ownership_legacy_plan'] = 'Legacy subscription';
$string['commerce_personal_offer_eligibility_free'] = 'Standalone offer';
$string['commerce_personal_offer_eligibility_free_help'] = 'No source purchase or source product is required for this offer.';
$string['commerce_personal_offer_eligibility_product'] = 'Product ownership';
$string['commerce_personal_offer_eligibility_purchase'] = 'Specific purchase';
$string['commerce_personal_offer_eligibility_campaign'] = 'Campaign criteria';
$string['commerce_personal_offer_evidence_purchase'] = 'Evidence purchase';
$string['commerce_personal_offer_campaign_criteria_source'] = 'Campaign source product';
$string['commerce_personal_offer_no_campaign'] = 'No campaign — individual offer';
$string['commerce_personal_offer_summary_title'] = 'Offer summary';
$string['commerce_personal_offer_eligibility_title'] = 'Why is this customer eligible?';
$string['commerce_personal_offer_lifecycle_title'] = 'Validity and tracking';
$string['commerce_personal_offer_technical_title'] = 'Technical references';
$string['commerce_personal_offer_ownership_source'] = 'Evidence source';
$string['commerce_personal_offer_metadata_technical'] = 'Technical metadata';
$string['commerce_personal_offer_owned_product'] = 'Owned product';
$string['commerce_personal_offer_product_evidence_missing'] = 'Source product was not recorded on this older offer';
$string['commerce_personal_offer_legacy_purchase_reference'] = 'Legacy digital purchase #{$a}';
$string['commerce_personal_offer_revoke_confirm'] = 'Revoke this offer? Its personal link will become unusable immediately.';
$string['commerce_personal_offer_checkout_temporary_error'] = 'A temporary technical error interrupted the opening of your offer. Your offer has not been consumed; you can try again.';
$string['commerce_personal_offer_checkout_badge'] = 'Personal offer';
$string['commerce_personal_offer_checkout_reserved_title'] = 'This offer is reserved for you';
$string['commerce_personal_offer_checkout_reserved_for'] = 'Offer reserved for {$a->name} ({$a->email})';
$string['commerce_personal_offer_checkout_currency_title'] = 'Choose your currency';
$string['commerce_personal_offer_checkout_currency_help'] = 'Your personal price remains applied. Only currencies available for this offer are shown.';
$string['commerce_checkout_existing_account_login_title'] = 'Sign in to continue';
$string['commerce_checkout_existing_account_login_help'] = 'This account already exists. Sign in here: your cart and offer will be preserved and you will automatically return to payment.';
$string['commerce_checkout_existing_account_login_submit'] = 'Sign in and continue';
$string['commerce_checkout_existing_account_login_alternative'] = 'Another sign-in method';
$string['commerce_personal_offer_order_discount_label'] = 'Personal offer';
$string['commerce_personal_offer_order_admin_reference'] = 'Personal offer (admin)';
$string['commerce_personal_offer_order_open'] = 'Open offer';
$string['task_process_commerce_mail_audit_queue'] = 'Send Commerce audit copies at low priority';
$string['settings:commerce_mail_audit_batch_size'] = 'Audit batch size';
$string['settings:commerce_mail_audit_batch_size_desc'] = 'Maximum audit copies processed per run. These messages remain behind customer mail and campaigns.';
$string['settings:commerce_mail_audit_hourly_limit'] = 'Audit hourly limit';
$string['settings:commerce_mail_audit_hourly_limit_desc'] = 'Maximum audit copies sent in a rolling one-hour window.';
$string['commerce_mail_resend'] = 'Resend email';
$string['commerce_mail_resend_confirm'] = 'Create a new delivery of this email? The original delivery will remain in the history.';
$string['commerce_mail_resend_queued'] = 'The resend has been queued.';
$string['commerce_mail_resend_not_allowed'] = 'Only an email that has already been sent can be resent this way.';
$string['commerce_mail_personal_offer_validity_label'] = 'Offer valid';
$string['commerce_mail_personal_offer_valid_from_label'] = 'Valid from';
$string['commerce_mail_personal_offer_from_label'] = 'from';
$string['commerce_mail_personal_offer_to_label'] = 'to';
$string['commerce_mail_preview_description'] = 'Preview exactly what was sent to the customer, check display variants and manage any resends.';
$string['commerce_mail_preview_font_label'] = 'Font';
$string['commerce_mail_preview_font_brand'] = 'CampusFR (Nunito)';
$string['commerce_mail_preview_font_fallback'] = 'Email fallback';


// J16C.2 — Exercise Explorer Builder.
$string['commerce_showroom_exercise_builder_title'] = 'Content for the 12 exercises';
$string['commerce_showroom_exercise_builder_content'] = 'Text';
$string['commerce_showroom_exercise_builder_media'] = 'Screenshots';
$string['commerce_showroom_exercise_builder_default'] = 'Main image';
$string['commerce_showroom_exercise_builder_import'] = 'Import a screenshot batch';
$string['commerce_showroom_exercise_builder_import_help'] = 'Import a ZIP containing up to 12 images. Technical names and the Russian titles from the initial batch are recognised automatically. Choose the target language before importing.';
$string['commerce_showroom_exercise_builder_import_button'] = 'Choose a ZIP';
$string['commerce_showroom_exercise_builder_import_done'] = '{stored} image(s) saved, {matched} exercise(s) recognised.';
$string['commerce_showroom_exercise_builder_choose_image'] = 'Choose';
$string['commerce_showroom_exercise_builder_remove_image'] = 'Remove';
$string['commerce_showroom_exercise_builder_image_empty'] = 'No image';
$string['commerce_showroom_exercise_builder_image_fallback'] = 'The main image is used when no localised image is available.';

// J16C.3 — Exercise Explorer public preview.
$string['commerce_showroom_exercise_preview_unavailable'] = 'Preview coming soon';

// J16C.4 — Exercise Explorer mobile.
$string['commerce_showroom_exercise_mobile_previous'] = 'Previous exercise';
$string['commerce_showroom_exercise_mobile_next'] = 'Next exercise';
$string['commerce_showroom_exercise_mobile_counter'] = 'Exercise';

// J16C.6 — Exercise Explorer navigation.
$string['commerce_showroom_exercise_navigation_hint'] = 'Swipe right or left, or use the buttons to view the other exercises.';
$string['commerce_showroom_exercise_navigation_label'] = 'Exercise navigation';

// J16C.6.2 — Exercise Explorer Builder UX.
$string['commerce_showroom_exercise_builder_fallback_badge'] = 'Fallback';
$string['commerce_mail_download_desktop'] = 'Standard';
$string['commerce_mail_download_mobile'] = 'Mobile';
$string['commerce_mail_bundle_contents'] = 'Your bundle contents';
$string['commerce_mail_access_my_campus'] = 'Go to My Campus';

// J16C.6.3 — Exercise Explorer preview polish.
$string['commerce_showroom_exercise_builder_localized_empty'] = 'No localised image';
$string['commerce_showroom_exercise_builder_localized_fallback'] = 'The main image will be used automatically.';

// J16C.6.5 — Exercise Explorer heading and desktop hint.
$string['commerce_showroom_exercise_desktop_hint'] = 'Click any exercise type to see what it looks like.';
$string['commerce_mail_receipt_price_before_discounts'] = 'Subtotal';
$string['commerce_mail_receipt_discounts'] = 'Discounts';
$string['commerce_mail_receipt_total_paid'] = 'Total paid';
$string['commerce_mail_payment_status_paid_value'] = 'Paid';
$string['commerce_mail_payment_status_pending_value'] = 'Pending';
$string['commerce_mail_payment_status_failed_value'] = 'Failed';
$string['commerce_mail_payment_status_cancelled_value'] = 'Cancelled';
// J16D.2 — Comparatif mobile.
$string['commerce_showroom_comparison_swipe_hint'] = 'Swipe right or left to compare';
$string['commerce_mail_receipt_product_promotions'] = 'Product promotions';
$string['commerce_mail_receipt_trial_discount'] = 'Trial discount';
$string['commerce_mail_receipt_owned_credit'] = 'Owned credit';
$string['commerce_mail_receipt_promo_code'] = 'Promo code';
$string['commerce_mail_receipt_personal_offer'] = 'Personal offer';
$string['commerce_mail_receipt_other_discount'] = 'Other discounts';
$string['commerce_mail_type_trial_welcome'] = 'Trial Welcome';
$string['commerce_mail_trial_welcome_subject'] = 'Welcome to CampusFR — your trial starts now';
$string['commerce_mail_trial_welcome_cta'] = 'Start learning';
$string['commerce_mail_welcome_login_email'] = 'LOGIN E-MAIL';
$string['commerce_mail_welcome_telegram_heading'] = 'Join the CampusFR community';
$string['commerce_mail_welcome_telegram_intro'] = 'Join our channel for important CampusFR news and updates. You can also use the group to chat, ask for advice and progress alongside other members.';
$string['commerce_mail_welcome_telegram_channel'] = 'CampusFR Channel';
$string['commerce_mail_welcome_telegram_group'] = 'CampusFR Group';
$string['commerce_mail_welcome_forgot_password'] = 'Forgot your password?';
$string['commerce_mail_welcome_reset_password'] = 'Reset my password';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_q'] = 'Can I complete the trainer more than once?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_8_a'] = 'Of course! You have lifetime access to the trainer, so you can return to any lesson or exercise as many times as you like. Many learners repeat individual training sessions to reinforce the most difficult verbs and make them automatic.';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_q'] = 'What if I can’t do it?';

// J16E.1 — FAQ content.
$string['commerce_showroom_faq_9_a'] = 'Of course you can! 😊 That is exactly why we created this trainer. It guides you step by step through even the most difficult third-group verbs. You can repeat the exercises as many times as you need, and if you have any questions, we will always be happy to help. You will find all the teacher contact details inside the trainer under “Practical information”. Every climb starts with small steps. 😉';
$string['commerce_mail_welcome_credentials_heading'] = 'Your login details';
$string['commerce_mail_welcome_activation_explanation'] = 'To secure your account and choose your password, use the button below. It only takes a moment.';
$string['commerce_mail_welcome_activation_security'] = 'This activation link is personal and can only be used once. If you did not make this purchase, you can simply ignore this email.';
$string['commerce_guest_activation_email_expiry_soft'] = 'For your security, the activation link will remain available until {$a}.';
$string['commerce_mail_welcome_postactivation'] = 'Once your account is activated, your courses, resources and purchases will always be available in your CampusFR space.';

// J16I2 — Showroom general configuration.
$string['commerce_showroom_config_general'] = 'General configuration';
$string['commerce_showroom_config_general_help'] = 'Showroom identity, publication status and rendering engine.';
$string['commerce_showroom_config_key_help'] = 'Stable technical key. Avoid changing it after publication.';
$string['commerce_showroom_config_render_template'] = 'Moodle render template';
$string['commerce_showroom_config_render_template_help'] = 'Defines the Mustache template used for the public page. This is different from the block starter template available below.';
$string['commerce_showroom_config_urls_legacy'] = 'URLs';
$string['commerce_showroom_config_urls_legacy_help'] = 'Slugs remain configurable here. Multilingual SEO metadata will be handled in J16I3.';
$string['commerce_showroom_config_products'] = 'Linked products';
$string['commerce_showroom_config_products_help'] = 'Select the Commerce products used by showroom offers. Only compatible product types are listed.';
$string['commerce_showroom_config_product_course'] = 'Course / Trainer';
$string['commerce_showroom_config_product_pdf'] = 'Digital product / Cards';
$string['commerce_showroom_config_product_bundle'] = 'Bundle';
$string['commerce_showroom_config_product_none'] = '— No product —';
$string['commerce_showroom_config_advanced'] = 'Advanced configuration';
$string['commerce_showroom_config_titlekey_legacy'] = 'Title language key (legacy)';
$string['commerce_showroom_config_descriptionkey_legacy'] = 'Description language key (legacy)';
$string['commerce_showroom_config_seo_legacy_help'] = 'These keys are retained as fallbacks when the CMS SEO title or description is empty.';
$string['commerce_showroom_config_settings_json'] = 'Global configuration (JSON)';
$string['commerce_showroom_config_settings_json_help'] = 'Reserved for advanced technical showroom settings.';

// J16I3 — multilingual Showroom SEO.
$string['commerce_showroom_config_seo'] = 'SEO & sharing';
$string['commerce_showroom_config_seo_help'] = 'Configure language-specific URLs and metadata. If a title or description is empty, the legacy Moodle language string is used as a fallback.';
$string['commerce_showroom_config_seo_slug'] = 'Slug';
$string['commerce_showroom_config_seo_title'] = 'SEO title';
$string['commerce_showroom_config_seo_title_help'] = 'Aim for roughly 50–60 characters, with the main topic near the beginning.';
$string['commerce_showroom_config_seo_description'] = 'Meta description';
$string['commerce_showroom_config_seo_social'] = 'Social sharing';
$string['commerce_showroom_config_seo_social_title'] = 'Social title';
$string['commerce_showroom_config_seo_social_description'] = 'Social description';
$string['commerce_showroom_config_seo_keywords'] = 'Keywords';
$string['commerce_showroom_config_seo_keywords_help'] = 'Optional. Modern search engines give little weight to the meta keywords tag.';
$string['commerce_support_guest_contact_help'] = 'Enter your email address so our team can reply to you. First and last name are optional, but we recommend adding them.';
$string['commerce_support_back_to_store'] = 'Back to the store';
$string['commerce_support_return_to_store'] = 'Back to the store';
$string['commerce_personal_offer_mail_image'] = 'Email image';
$string['commerce_personal_offer_mail_image_help'] = 'Optional. This image is displayed at the bottom of the offer email. If no custom image is provided, the default CampusFR visual is used. JPG, PNG or WebP, maximum 8 MB.';
$string['commerce_personal_offer_mail_image_edit_help'] = 'Upload a new image to replace it. If you do not select a file, the current custom image is kept.';
$string['commerce_personal_offer_mail_image_upload_error'] = 'The offer image could not be uploaded.';
$string['commerce_personal_offer_mail_image_too_large'] = 'The offer image exceeds the maximum allowed size of 8 MB.';
$string['commerce_personal_offer_mail_image_invalid_type'] = 'The offer image must be a valid JPG, PNG or WebP file.';
$string['commerce_manual_grant_access_type'] = 'Access type';
$string['commerce_manual_grant_mode_legacy'] = 'Legacy subscription';
$string['commerce_manual_grant_mode_native'] = 'Native product';
$string['commerce_manual_grant_native_section'] = 'Native Commerce access';
$string['commerce_manual_grant_native_product'] = 'Product to grant';
$string['commerce_manual_grant_native_product_help'] = 'The product is fulfilled by the Native Commerce engine. Bundles are expanded and all component entitlements are granted.';
$string['commerce_manual_grant_reason'] = 'Grant reason';
$string['commerce_manual_grant_reason_help'] = 'Optional. This information is stored in the entitlement audit metadata.';
$string['commerce_manual_grant_submit'] = 'Grant access';
$string['commerce_manual_grant_success'] = 'Product “{$a->product}” was granted to {$a->user}. {$a->count} Native entitlement(s) were processed.';
$string['commerce_manual_grant_invalid_mode'] = 'Invalid manual grant type.';
$string['commerce_manual_grant_product_unavailable'] = 'The selected Native product is missing or inactive.';
$string['commerce_manual_grant_missing_entitlement'] = 'No effective Native entitlement is configured for product {$a}.';
$string['commerce_manual_grant_empty_plan'] = 'The selected product generates no Native entitlement.';
$string['commerce_grants_title'] = 'Grants';
$string['commerce_grants_description'] = 'Manage Legacy and Native access individually or in bulk.';
$string['commerce_grants_card_description'] = 'Grant access to one customer or prepare a bulk grant with a dry-run first.';
$string['commerce_grants_manual_title'] = 'Individual grant';
$string['commerce_grants_manual_description'] = 'Manually add a Legacy subscription or grant a Native Commerce product to a customer.';
$string['commerce_grants_manual_action'] = 'Grant access manually';
$string['commerce_grants_back'] = 'Back to grants';
$string['commerce_bulk_grant_title'] = 'Bulk grant';
$string['commerce_bulk_grant_description'] = 'Select an audience by Legacy plan or Native product ownership, then simulate the exact beneficiaries before granting anything.';
$string['commerce_bulk_grant_open'] = 'Prepare a bulk grant';
$string['commerce_bulk_grant_source_type'] = 'Eligibility criterion';
$string['commerce_bulk_grant_source_legacy'] = 'Legacy plan membership';
$string['commerce_bulk_grant_source_native'] = 'Native product ownership';
$string['commerce_bulk_grant_source_plan'] = 'Source Legacy plan';
$string['commerce_bulk_grant_source_product'] = 'Source Native product';
$string['commerce_bulk_grant_target_product'] = 'Product to grant';
$string['commerce_bulk_grant_target_help'] = 'No access is granted at this stage. The target is only used to validate eligibility and the entitlements that would be delivered.';
$string['commerce_bulk_grant_simulate'] = 'Simulate beneficiaries';
$string['commerce_bulk_grant_preview_title'] = 'Beneficiary simulation';
$string['commerce_bulk_grant_preview_help'] = 'This list is the exact population detected with the current criteria. Review identities and exclusions before continuing.';
$string['commerce_bulk_grant_metric_total'] = 'Customers found';
$string['commerce_bulk_grant_metric_eligible'] = 'To grant';
$string['commerce_bulk_grant_metric_owned'] = 'Already owned';
$string['commerce_bulk_grant_metric_identity'] = 'Identity review';
$string['commerce_bulk_grant_metric_error'] = 'Errors';
$string['commerce_bulk_grant_dry_run_badge'] = 'DRY-RUN';
$string['commerce_bulk_grant_no_mutation'] = 'No access, purchase or entitlement has been created or changed.';
$string['commerce_bulk_grant_filter_all'] = 'All ({$a})';
$string['commerce_bulk_grant_filter_eligible'] = 'To grant ({$a})';
$string['commerce_bulk_grant_filter_owned'] = 'Already owned ({$a})';
$string['commerce_bulk_grant_filter_identity'] = 'Identity review ({$a})';
$string['commerce_bulk_grant_filter_error'] = 'Errors ({$a})';
$string['commerce_bulk_grant_customer'] = 'Customer';
$string['commerce_bulk_grant_moodle_account'] = 'Moodle account';
$string['commerce_bulk_grant_evidence'] = 'Eligibility evidence';
$string['commerce_bulk_grant_current_ownership'] = 'Target situation';
$string['commerce_bulk_grant_decision'] = 'Decision';
$string['commerce_bulk_grant_account_unresolved'] = 'Unresolved';
$string['commerce_bulk_grant_decision_eligible'] = 'To grant';
$string['commerce_bulk_grant_decision_already_owned'] = 'Already owned';
$string['commerce_bulk_grant_decision_identity_review'] = 'Review';
$string['commerce_bulk_grant_decision_error'] = 'Error';
$string['commerce_bulk_grant_planned_entitlements'] = '{$a} entitlement(s) would be delivered';
$string['commerce_bulk_grant_reason_missing_moodle_user'] = 'No Moodle account could be resolved.';
$string['commerce_bulk_grant_reason_invalid_email'] = 'The account email address is invalid.';
$string['commerce_bulk_grant_reason_target_already_owned'] = 'The customer already owns the target product.';
$string['commerce_bulk_grant_ownership_none'] = 'Not owned';
$string['commerce_bulk_grant_ownership_native_entitlement'] = 'Active Native entitlement';
$string['commerce_bulk_grant_ownership_native_purchase'] = 'Native purchase';
$string['commerce_bulk_grant_ownership_bundle_components'] = 'All bundle components are already owned';
$string['commerce_bulk_grant_ownership_legacy_digital_purchase'] = 'Legacy digital purchase';
$string['commerce_bulk_grant_ownership_legacy_plan'] = 'Legacy plan';
$string['commerce_bulk_grant_next_phase_notice'] = 'The dry-run is intentionally non-executable at this stage. K14E will add final beneficiary selection and the campaign snapshot before execution.';
$string['commerce_bulk_grant_invalid_source_type'] = 'Invalid eligibility criterion type.';
$string['commerce_bulk_grant_source_product_missing'] = 'The Native source product could not be found.';
$string['crm_commerce_nav_grants'] = 'Grants';
$string['commerce_bulk_grant_select_all'] = 'Select all eligible';
$string['commerce_bulk_grant_select_none'] = 'Deselect all';
$string['commerce_bulk_grant_snapshot_title'] = 'Create the final snapshot';
$string['commerce_bulk_grant_snapshot_help'] = 'Only checked beneficiaries that are still eligible are frozen into the campaign. The source audience will not be recalculated during execution.';
$string['commerce_bulk_grant_campaign_name'] = 'Campaign name';
$string['commerce_bulk_grant_campaign_name_placeholder'] = 'E.g. Legacy Lifetime → Third-group verbs';
$string['commerce_bulk_grant_campaign_reason'] = 'Grant reason';
$string['commerce_bulk_grant_create_snapshot'] = 'Create campaign snapshot';
$string['commerce_bulk_grant_campaign_name_required'] = 'Campaign name is required.';
$string['commerce_bulk_grant_campaign_selection_required'] = 'Select at least one eligible beneficiary.';
$string['commerce_bulk_grant_campaign_selection_changed'] = 'User #{$a} is no longer eligible when creating the snapshot. Run the simulation again and review the selection.';
$string['commerce_bulk_grant_campaign_not_launchable'] = 'This campaign cannot be launched in its current state.';
$string['commerce_bulk_grant_campaign_retry_unavailable'] = 'Failures cannot be retried in the campaign’s current state.';
$string['commerce_bulk_grant_campaign_view_help'] = 'Review the frozen snapshot, launch the grant and follow every beneficiary through to the final result.';
$string['commerce_bulk_grant_campaign_snapshot_title'] = 'Campaign snapshot';
$string['commerce_bulk_grant_campaign_source'] = 'Source audience';
$string['commerce_bulk_grant_campaign_metric_queued'] = 'Queued';
$string['commerce_bulk_grant_campaign_metric_success'] = 'Successful';
$string['commerce_bulk_grant_campaign_metric_skipped'] = 'Skipped';
$string['commerce_bulk_grant_campaign_launch'] = 'Launch grant for {$a} beneficiary(ies)';
$string['commerce_bulk_grant_campaign_launch_confirm'] = 'This will launch the campaign using the snapshot shown here. Access will be granted in batches by cron. Continue?';
$string['commerce_bulk_grant_campaign_launched'] = 'The campaign is queued. Grants will be processed in batches.';
$string['commerce_bulk_grant_campaign_retry'] = 'Retry {$a} failure(s)';
$string['commerce_bulk_grant_campaign_retried'] = '{$a} failed beneficiary(ies) were queued again.';
$string['commerce_bulk_grant_campaign_cron_notice'] = 'The campaign is running. Cron processes up to 25 beneficiaries per run; successful members are never processed again.';
$string['commerce_bulk_grant_campaign_attempts'] = 'Attempts';
$string['commerce_bulk_grant_campaign_error'] = 'Last error';
$string['commerce_bulk_grant_new'] = 'New bulk grant';
$string['commerce_bulk_grant_campaigns_title'] = 'Grant campaigns';
$string['commerce_bulk_grant_campaigns_empty'] = 'No bulk grant campaign has been created yet.';
$string['commerce_bulk_grant_campaign_status_ready'] = 'Ready';
$string['commerce_bulk_grant_campaign_status_queued'] = 'Queued';
$string['commerce_bulk_grant_campaign_status_running'] = 'Running';
$string['commerce_bulk_grant_campaign_status_completed'] = 'Completed';
$string['commerce_bulk_grant_campaign_status_completed_errors'] = 'Completed with errors';
$string['commerce_bulk_grant_member_status_queued'] = 'Queued';
$string['commerce_bulk_grant_member_status_completed'] = 'Granted';
$string['commerce_bulk_grant_member_status_skipped'] = 'Skipped';
$string['commerce_bulk_grant_member_status_failed'] = 'Failed';
$string['task_process_commerce_grant_campaigns'] = 'Process Commerce grant campaigns';
$string['commerce_mail_type_grant_access'] = 'Granted access';
$string['commerce_mail_grant_access_subject'] = 'New CampusFR access is now available';
$string['commerce_manual_grant_send_email'] = 'Send the “Access available” email';
$string['commerce_manual_grant_send_email_help'] = 'Recommended. The transactional email shows the granted product and its access links. For an individual grant, delivery is attempted immediately and remains in the transactional queue for retry if needed.';
$string['commerce_bulk_grant_send_email'] = 'Send the “Access available” email to beneficiaries';
$string['commerce_bulk_grant_send_email_help'] = 'Recommended. One email is queued per beneficiary and granted root product. Bulk grant cron does not send these emails directly: they use the transactional queue and its throttling.';
$string['commerce_bulk_grant_email_notification'] = 'Email notification';
$string['commerce_personal_offer_source_type'] = 'Eligibility source';
$string['commerce_personal_offer_source_type_help'] = 'Choose the ownership or subscription that makes a customer eligible for this campaign. The simulation creates no offer.';
$string['commerce_personal_offer_source_legacy_plan'] = 'Legacy plan / subscription';
$string['commerce_personal_offer_source_legacy_digital'] = 'Legacy digital product';
$string['commerce_personal_offer_source_native_product'] = 'Native product';
$string['commerce_personal_offer_source_missing'] = 'The selected eligibility source could not be found.';
$string['commerce_personal_offer_invalid_source_type'] = 'Invalid eligibility source type.';
$string['commerce_personal_offer_metric_covered'] = 'Already covered';
$string['commerce_personal_offer_metric_identity_review'] = 'Identity review';
$string['commerce_personal_offer_customer'] = 'Customer';
$string['commerce_personal_offer_moodle_account'] = 'Moodle account';
$string['commerce_personal_offer_eligibility_evidence'] = 'Eligibility evidence';
$string['commerce_personal_offer_existing_offer'] = 'Existing active offer';
$string['commerce_personal_offer_account_unresolved'] = 'Unresolved';
$string['commerce_personal_offer_reason_ambiguous_email'] = 'More than one Moodle account matches this email.';
$string['commerce_personal_offer_reason_account_required'] = 'The campaign requires a resolved Moodle account.';
$string['commerce_personal_offer_reason_account_not_allowed'] = 'The campaign targets customers without a Moodle account only.';
$string['commerce_personal_offer_reason_active_offer_exists'] = 'An active personal offer already exists for this product.';
$string['commerce_personal_offer_member_status_eligible'] = 'Eligible';
$string['commerce_personal_offer_member_status_covered'] = 'Already covered';
$string['commerce_personal_offer_member_status_identity_review'] = 'Identity review';
$string['commerce_personal_offer_member_status_excluded'] = 'Excluded';
$string['commerce_personal_offer_member_status_error'] = 'Error';
$string['commerce_personal_offer_member_status_issued'] = 'Offer created';
$string['commerce_personal_offer_member_status_replayed'] = 'Existing offer replayed';
$string['commerce_personal_offer_preview'] = 'Simulate eligible customers';
$string['commerce_personal_offer_create_snapshot'] = 'Create final snapshot';
$string['commerce_personal_offer_snapshot_confirm'] = 'The current selection will be frozen. After the snapshot is created, the audience cannot be recalculated or edited. Continue?';
$string['commerce_personal_offer_generate_snapshot'] = 'Generate offers for {$a} beneficiary(ies)';
$string['commerce_personal_offer_generate_snapshot_confirm'] = 'Personal offers will be created for the beneficiaries frozen in this snapshot. Continue?';
$string['commerce_personal_offer_snapshot_title'] = 'Campaign snapshot';
$string['commerce_personal_offer_snapshot_selected'] = 'Selected beneficiaries';
$string['commerce_personal_offer_snapshot_date'] = 'Snapshot created';
$string['commerce_personal_offer_snapshot_hash'] = 'Snapshot fingerprint';
$string['commerce_personal_offer_snapshot_frozen_notice'] = 'The audience is now frozen. Generation will use only this snapshot; the eligibility source will not be recalculated.';
$string['commerce_personal_offer_snapshot_empty'] = 'No eligible beneficiary is selected for the snapshot.';
$string['commerce_personal_offer_snapshot_changed'] = 'The campaign snapshot was changed or no longer matches the frozen beneficiaries. Create a new campaign instead of continuing.';
$string['commerce_personal_offer_reason_target_acquired_after_snapshot'] = 'The customer acquired the target product after the snapshot.';
$string['commerce_personal_offer_reason_active_offer_created_after_snapshot'] = 'Another active personal offer was created after the snapshot.';
$string['commerce_personal_offer_campaign_status_draft'] = 'Draft';
$string['commerce_personal_offer_campaign_status_previewed'] = 'Simulated';
$string['commerce_personal_offer_campaign_status_snapshot'] = 'Frozen snapshot';
$string['commerce_personal_offer_campaign_status_issued'] = 'Offers generated';
$string['commerce_personal_offer_campaign_status_closed'] = 'Closed';
$string['commerce_personal_offer_retry_generation'] = 'Retry {$a} generation failure(s)';
$string['commerce_personal_offer_mail_queue_missing'] = 'Queue {$a} remaining email(s)';
$string['commerce_personal_offer_mail_queue_confirm'] = 'Missing emails will be added to the transactional queue and sent under the existing throttling rules. Continue?';
$string['commerce_personal_offer_mail_retry_failed'] = 'Retry {$a} failed email(s)';
$string['commerce_personal_offer_mail_campaign_retried'] = '{$a->requeued} email(s) were requeued from {$a->failed} detected failure(s).';
$string['commerce_personal_offer_mail_expected'] = 'Expected';
$string['commerce_personal_offer_certification_title'] = 'Campaign certification';
$string['commerce_personal_offer_certification_ready'] = 'The campaign is ready for certification: there are no generation errors and no pending or failed emails.';
$string['commerce_personal_offer_certification_blocked'] = 'Certification blocked: {$a->generationerrors} generation failure(s), {$a->selectedpending} beneficiary(ies) still pending and {$a->mailblocking} unfinished email(s).';
$string['commerce_personal_offer_certify_campaign'] = 'Certify and close campaign';
$string['commerce_personal_offer_certify_confirm'] = 'The campaign will be marked as certified and closed. This confirms all expected offers were processed and all transactional emails reached a final state. Continue?';
$string['commerce_personal_offer_campaign_certified'] = 'The campaign has been certified and closed.';
$string['commerce_personal_offer_campaign_not_certifiable'] = 'This campaign cannot be certified yet. Resolve generation errors and finish all expected emails first.';
$string['commerce_personal_offer_certified_at'] = 'Certified at';
$string['commerce_personal_offer_certified_by'] = 'Certified by';

// 7.95L1 — Showroom offer discovery CTA.
$string['commerce_showroom_config_offer_details_enabled'] = 'Show the “Learn more” link';
$string['commerce_product_discovery_destination'] = '“Discover” button destination';
$string['commerce_product_discovery_storefront'] = 'Storefront product page';
$string['commerce_product_discovery_showroom'] = 'Associated Showroom';
$string['commerce_product_discovery_help'] = 'Controls where “Discover” links from the Store, My purchases and other customer journeys send visitors. If the Showroom is not published, the Storefront product page is used automatically.';
$string['commerce_product_show_full_presentation_cta'] = 'Show “View full presentation” on the Storefront product page';
$string['commerce_product_show_full_presentation_cta_help'] = 'Keeps the Storefront product page accessible and offers a link to the associated Showroom. No automatic redirect is performed.';
$string['commerce_storefront_full_presentation'] = 'View full presentation';
$string['commerce_storefront_commerce_position_none'] = 'Hidden (Builder only)';
$string['commerce_storefront_product_header_mode'] = 'Product header';
$string['commerce_storefront_product_header_automatic'] = 'Automatic';
$string['commerce_storefront_product_header_builder'] = 'Managed by Builder';
$string['commerce_storefront_product_header_hidden'] = 'Hidden';
$string['commerce_storefront_product_header_help'] = 'In Builder mode, the first visible Hero block replaces the automatic product header. If no Hero is present, the automatic header remains visible.';
$string['commerce_storefront_hero_layout'] = 'Hero layout';
$string['commerce_storefront_hero_layout_text_media'] = 'Text left / media right';
$string['commerce_storefront_hero_layout_media_text'] = 'Media left / text right';
$string['commerce_storefront_hero_layout_stacked'] = 'Stacked';
$string['commerce_storefront_hero_layout_overlay'] = 'Text over image';
$string['commerce_storefront_hero_ratio'] = 'Text / media ratio';
$string['commerce_storefront_hero_media_ratio'] = 'Media format';
$string['commerce_storefront_media_ratio_original'] = 'Original format';
$string['commerce_currency'] = 'Currency';
$string['commerce_storefront_builder_attention'] = 'To complete';
$string['commerce_storefront_builder_empty_status'] = 'Empty';

$string['commerce_storefront_image_fit'] = 'Image fit';
$string['commerce_storefront_image_fit_cover'] = 'Fill frame (cover)';
$string['commerce_storefront_image_fit_contain'] = 'Show full image (contain)';
$string['commerce_storefront_content_alignment'] = 'Content alignment';
$string['commerce_storefront_content_alignment_left'] = 'Left';
$string['commerce_storefront_content_alignment_center'] = 'Center';
$string['commerce_storefront_content_alignment_right'] = 'Right';

// Storefront locale copy and AI translation.
$string['settings:storefront_ai_translation_enabled'] = 'Enable Storefront AI translation';
$string['settings:storefront_ai_translation_enabled_desc'] = 'Allows the Storefront Builder to use the already configured OpenAI account (key, project, organization, model and endpoint) to prepare locale translations. A preview must be manually approved before anything is saved.';
$string['commerce_storefront_locale_tools_title'] = 'Locales and translation';
$string['commerce_storefront_locale_tools_help'] = 'Duplicate an existing locale or prepare an automatic translation into the language currently being edited.';
$string['commerce_storefront_locale_copy_title'] = 'Copy a locale';
$string['commerce_storefront_locale_copy_help'] = 'Copies the editorial structure, sections, SEO, localized data and media references into the language currently being edited. Global Commerce settings and slugs are not changed.';
$string['commerce_storefront_locale_source'] = 'Source locale';
$string['commerce_storefront_locale_copy_button'] = 'Copy into this locale';
$string['commerce_storefront_locale_copy_confirm'] = 'The locale currently being edited will be replaced with a copy of the source locale. Continue?';
$string['commerce_storefront_locale_copy_success'] = 'The locale has been copied.';
$string['commerce_storefront_locale_source_empty'] = 'The source locale does not contain any Storefront configuration to copy.';
$string['commerce_storefront_ai_translation_title'] = 'Translate with OpenAI';
$string['commerce_storefront_ai_translation_help'] = 'Prepares a translated copy of the source locale. Only textual fields are sent to OpenAI; IDs, URLs, media, SKUs and technical settings remain unchanged.';
$string['commerce_storefront_ai_translation_preview_button'] = 'Prepare translation';
$string['commerce_storefront_ai_translation_unavailable'] = 'OpenAI Storefront translation is unavailable.';
$string['commerce_storefront_ai_translation_unavailable_help'] = 'Enable Storefront AI translation in the plugin settings and check that the OpenAI API key and model are configured.';
$string['commerce_storefront_ai_translation_no_content'] = 'No translatable textual field was found in the source locale.';
$string['commerce_storefront_ai_translation_too_many_fields'] = 'The locale contains too many translatable fields for one operation (maximum: {$a}).';
$string['commerce_storefront_ai_translation_preview_expired'] = 'This translation preview has expired. Generate a new one.';
$string['commerce_storefront_ai_translation_preview_title'] = 'Translation preview';
$string['commerce_storefront_ai_translation_preview_summary'] = '{$a->source} → {$a->target}: {$a->count} changed field(s), model {$a->model}. Nothing has been saved yet.';
$string['commerce_storefront_ai_translation_source_text'] = 'Source';
$string['commerce_storefront_ai_translation_target_text'] = 'Translation';
$string['commerce_storefront_ai_translation_apply'] = 'Apply translations';
$string['commerce_storefront_ai_translation_applied'] = 'The translations have been applied to the locale.';
$string['subscriptions:manage_showrooms'] = 'Manage Commerce showrooms';
$string['commerce_showroom_status_workflow_only'] = 'Status is controlled by the review and publication workflow.';
$string['commerce_showroom_publish_requires_block'] = 'This showroom cannot be published until at least one block is enabled.';
$string['commerce_showroom_invalid_transition'] = 'This showroom status transition is not allowed.';
$string['commerce_showroom_import_create'] = 'Create from JSON import';
$string['commerce_showroom_import_file'] = 'Showroom JSON file';
$string['commerce_showroom_import_or_paste'] = 'Or paste the JSON content manually below.';
$string['commerce_showroom_import_created_draft'] = 'Showroom imported and created as a draft.';
$string['commerce_showroom_import_media_warning'] = 'The JSON file carries the structure, translations and complete configuration of every block. Media files stored by Moodle (images/videos) are not embedded in the JSON: their references must be checked or the media re-uploaded before publishing in production.';
$string['commerce_showroom_export_portable'] = 'Export portable package';
$string['commerce_showroom_export_json'] = 'Export JSON';
$string['commerce_showroom_import_portable_help'] = 'For a DEV → PROD migration, prefer the .showroom.zip portable package: it contains the complete JSON and all showroom media. JSON-only import remains available for configuration without files.';
$string['commerce_showroom_import_portable_done'] = 'Portable showroom imported as a draft: {$a->blocks} blocks, {$a->media} media files, {$a->remapped} references remapped.';
$string['commerce_showroom_export_preflight_title'] = 'Portable package preparation';
$string['commerce_showroom_export_preflight_media'] = 'Media files';
$string['commerce_showroom_export_preflight_total'] = 'Total media size';
$string['commerce_showroom_export_preflight_largest'] = 'Largest file';
$string['commerce_showroom_export_preflight_required'] = 'Recommended temporary space';
$string['commerce_showroom_export_preflight_available'] = 'Available temporary space';
$string['commerce_showroom_export_preflight_ready'] = 'The export can start. Already-compressed media (images and videos) will be stored in the ZIP without recompression.';
$string['commerce_showroom_export_portable_start'] = 'Create and download package';
$string['commerce_showroom_export_insufficient_disk'] = 'Not enough temporary disk space. Required: {$a->required}. Available: {$a->available}.';
$string['commerce_showroom_export_invalid_archive'] = 'The generated portable package is invalid or incomplete.';
$string['commerce_showroom_publish_requires_slug'] = 'This showroom cannot be published until at least one public slug is configured.';
$string['commerce_showroom_publish_slug_conflict'] = 'The public slug “{$a}” is already used by another route, product or published showroom.';
$string['commerce_storefront_bundle_includes'] = 'This bundle includes';
// 7.95M1 - CRM Commerce profiles without a Moodle account.
$string['crm_commerce_guest_profile'] = 'Commerce customer profile';
$string['crm_commerce_guest_profile_description'] = 'User360 view for a customer with Commerce or Legacy purchase history and no associated Moodle account.';
$string['crm_commerce_identity_type'] = 'Identity type';
$string['crm_commerce_identity_legacy_guest'] = 'Commerce / Legacy customer';
$string['crm_first_purchase'] = 'First purchase';
$string['crm_no_moodle_account'] = 'No associated Moodle account';
$string['crm_commerce_native_history'] = 'Native Commerce history';
$string['crm_commerce_guest_no_actions'] = 'No action is currently available for this customer.';
$string['crm_user_account_commerce_only'] = 'Commerce customer · no Moodle account';
$string['commerce_showroom_publish_integrity_failed'] = 'Showroom publication integrity check failed: {$a}.';
$string['commerce_storefront_access_bundle_contents'] = 'Access my content';
$string['commerce_storefront_back_to_showroom'] = 'Back to presentation';
$string['commerce_personal_offer_campaign_email_title'] = 'Campaign email';
$string['commerce_personal_offer_campaign_email_help'] = 'Configure the marketing copy used by Personal Offer emails. Product, authoritative prices, validity and the secure CTA remain generated by Commerce.';
$string['commerce_personal_offer_campaign_email_saved'] = 'Campaign email configuration saved.';
$string['commerce_personal_offer_campaign_email_locked'] = 'This campaign has already been issued or closed. Its email configuration is read-only.';
$string['commerce_personal_offer_campaign_email_destination'] = 'CTA destination';
$string['commerce_personal_offer_campaign_email_destination_checkout'] = 'Personal offer checkout';
$string['commerce_personal_offer_campaign_email_destination_showroom'] = 'Showroom';
$string['commerce_personal_offer_campaign_email_showroom'] = 'Target showroom';
$string['commerce_personal_offer_campaign_email_showroom_choose'] = 'Choose a compatible published showroom';
$string['commerce_personal_offer_campaign_email_showroom_help'] = 'Only published showrooms containing the campaign target product are offered. The public URL is never entered manually.';
$string['commerce_personal_offer_campaign_email_content'] = 'Email content';
$string['commerce_personal_offer_campaign_email_content_help'] = 'Leave a language completely empty to use the French fallback, or the historical Personal Offer email when French is also empty. Marketing content and the closing use Moodle’s rich-text editor; HTML is cleaned before storage and again when the email is rendered.';
$string['commerce_personal_offer_campaign_email_variables'] = 'Available dynamic variables';
$string['commerce_personal_offer_campaign_email_subject'] = 'Subject';
$string['commerce_personal_offer_campaign_email_body'] = 'Marketing content';
$string['commerce_personal_offer_campaign_email_cta_label'] = 'CTA label';
$string['commerce_personal_offer_campaign_email_closing'] = 'Closing / conclusion';
$string['commerce_personal_offer_campaign_email_manage'] = 'Configure campaign email';
$string['commerce_personal_offer_campaign_email_fallback_active'] = 'No custom campaign copy is configured. The historical Personal Offer email fallback remains active.';
$string['commerce_personal_offer_campaign_email_languages_configured'] = 'Custom campaign copy configured for: {$a}.';
$string['commerce_personal_offer_campaign_email_destination_summary'] = 'CTA destination';
$string['commerce_identity_bulk_execute'] = 'Reconcile matched purchases';
$string['commerce_identity_bulk_execute_confirm'] = 'This will write the selected reconciliations. Each purchase is re-checked at execution time; ambiguous or changed identities are not force-linked.';
$string['commerce_identity_bulk_execute_result'] = '{$a->done} of {$a->total} selected purchase(s) were reconciled.';
$string['commerce_identity_bulk_none_selected'] = 'Select at least one uniquely matched purchase.';
$string['commerce_identity_bulk_preview'] = 'Preview selected reconciliations';
$string['commerce_identity_bulk_preview_description'] = 'Review every selected identity match and its expected data impact before writing anything.';
$string['commerce_identity_bulk_preview_title'] = 'Bulk reconciliation dry-run';
$string['commerce_identity_bulk_preview_warning'] = 'Dry-run only: no data has been changed. Only rows still having one unique exact-email Moodle match can be executed.';
$string['commerce_identity_dryrun_impact'] = 'Dry-run impact';
$string['commerce_identity_dryrun_impact_summary'] = '{$a->total} change(s): {$a->grants} grant(s), {$a->digital} digital access, {$a->guests} guest session(s), {$a->legacy} legacy record(s).';
$string['commerce_identity_filter_any'] = 'Search email, reference or customer data';
$string['commerce_identity_filter_candidateuserid'] = 'Candidate Moodle user ID';
$string['commerce_identity_filter_email_partial'] = 'Email contains';
$string['commerce_identity_filter_name'] = 'Customer name contains';
$string['commerce_identity_filter_purchaseid'] = 'Purchase ID';
$string['commerce_identity_filter_reference'] = 'Purchase reference contains';
$string['commerce_identity_filter_sku'] = 'Product SKU / item reference contains';
$string['commerce_identity_filter_status'] = 'Diagnostic status';
$string['commerce_identity_results_count'] = '{$a} unresolved purchase(s) match the filters.';
$string['commerce_identity_select'] = 'Select';
$string['commerce_identity_select_purchase'] = 'Select purchase {$a}';
$string['commerce_identity_nav_label'] = 'Identity operations';
$string['commerce_identity_nav_reconciliation'] = 'Reconciliation';
$string['commerce_identity_nav_similarities'] = 'Similar accounts';
$string['commerce_identity_similarity_title'] = 'Potentially similar accounts';
$string['commerce_identity_similarity_description'] = 'Find Moodle accounts that may belong to the same person before any manual merge.';
$string['commerce_identity_similarity_manual_only'] = 'These matches are suggestions only. No account is merged, modified or suspended automatically.';
$string['commerce_identity_similarity_filter_query'] = 'Email, first name or last name';
$string['commerce_identity_similarity_filter_status'] = 'Account status';
$string['commerce_identity_similarity_filter_minscore'] = 'Minimum score';
$string['commerce_identity_similarity_account_active'] = 'Active';
$string['commerce_identity_similarity_account_suspended'] = 'Suspended';
$string['commerce_identity_similarity_scan_summary'] = '{$a->users} accounts scanned · {$a->matches} suggested matches';
$string['commerce_identity_similarity_truncated'] = 'The scan was limited to the {$a} most recently active accounts. Use search to target a specific person if needed.';
$string['commerce_identity_similarity_empty'] = 'No similar accounts were detected with these criteria.';
$string['commerce_identity_similarity_score'] = 'Score';
$string['commerce_identity_similarity_account_first'] = 'Account A';
$string['commerce_identity_similarity_account_second'] = 'Account B';
$string['commerce_identity_similarity_signals'] = 'Detected signals';
$string['commerce_identity_similarity_reason_email_exact'] = 'Same email';
$string['commerce_identity_similarity_reason_email_local_exact'] = 'Same email identifier';
$string['commerce_identity_similarity_reason_email_local_close'] = 'Similar emails';
$string['commerce_identity_similarity_reason_name_exact'] = 'Same first and last name';
$string['commerce_identity_similarity_reason_name_reversed'] = 'First / last name reversed';
$string['commerce_identity_similarity_reason_firstname_close'] = 'Similar first names';
$string['commerce_identity_similarity_reason_lastname_close'] = 'Similar last names';
$string['commerce_identity_similarity_reason_phone_exact'] = 'Same phone number';
// Personal Offer Campaign Email — M3D preview/test send.
$string['commerce_personal_offer_campaign_email_preview'] = 'Preview campaign email';
$string['commerce_personal_offer_campaign_email_preview_help'] = 'Safe pre-issue preview calculated from campaign terms and authoritative catalogue prices. The preview CTA stays inside the administration area and creates no offer.';
$string['commerce_personal_offer_campaign_email_preview_refresh'] = 'Refresh';
$string['commerce_personal_offer_campaign_email_test_send'] = 'Send test email';
$string['commerce_personal_offer_campaign_email_test_sent'] = 'Test email sent to {$a}.';
$string['commerce_identity_merge_blockers'] = 'Checks required before merging';
$string['commerce_identity_merge_blocker_pedagogy'] = 'Account #{$a->userid} contains learning history requiring review ({$a->count} item(s)).';
$string['commerce_identity_merge_blocker_legacy_subscription'] = 'Account #{$a->userid} contains {$a->count} commercial record(s) requiring review.';
$string['commerce_identity_merge_blocker_already_merged'] = 'Source account #{$a->userid} has already been used as a source in a previous merge.';
$string['commerce_identity_merge_blocker_suspended_target'] = 'Primary account #{$a->userid} is suspended.';
$string['commerce_identity_merge_blocker_generic'] = 'The merge has a blocker requiring manual review.';
$string['commerce_identity_merge_execution_warning'] = 'This action is transactional but irreversible from the UI: learning state, Legacy/Commerce ownership and CRM identity data will be consolidated into the retained account, then source accounts will be suspended. Historical audit logs are preserved.';
$string['commerce_identity_merge_execution_confirm'] = 'I confirm that I reviewed the preview and want to permanently merge these accounts.';
$string['commerce_identity_merge_execute'] = 'Execute merge';
$string['commerce_identity_merge_confirmation_required'] = 'You must explicitly confirm the merge.';
$string['commerce_identity_merge_execution_blocked'] = 'This merge is blocked because one or more accounts contain data that cannot be transferred automatically with sufficient safety.';
$string['commerce_identity_merge_execution_success'] = 'Merge completed: {$a->sources} source account(s) were attached to account #{$a->targetuserid}. Audit reference: {$a->mergeuuid}.';
$string['privacy:metadata:identity_merge'] = 'Audit log of administrative identity merges.';
$string['privacy:metadata:identity_merge:targetuserid'] = 'Primary Moodle account retained.';
$string['privacy:metadata:identity_merge:performedby'] = 'Administrator who executed the merge.';
$string['privacy:metadata:identity_merge_source'] = 'Source Moodle accounts participating in a merge.';
$string['privacy:metadata:identity_merge_source:sourceuserid'] = 'Merged source Moodle account.';
$string['privacy:metadata:identity_merge_source:sourceemail'] = 'Historical source account email at merge time.';
$string['crm_topbar_admin_general'] = 'General';
$string['crm_topbar_admin_users'] = 'Users';
$string['crm_topbar_admin_courses'] = 'Courses';
$string['crm_topbar_admin_grades'] = 'Grades';
$string['crm_topbar_admin_plugins'] = 'Plugins';
$string['crm_topbar_admin_appearance'] = 'Appearance';
$string['crm_topbar_admin_server'] = 'Server';
$string['crm_topbar_admin_reports'] = 'Reports';
$string['crm_topbar_admin_development'] = 'Development';
$string['crm_topbar_admin_shortcuts'] = 'My admin shortcuts';
$string['crm_topbar_admin_purge_caches'] = 'Purge caches';
$string['crm_topbar_admin_maintenance_mode'] = 'Maintenance mode';
$string['crm_topbar_admin_subscriptions_config'] = 'Commerce configuration';
$string['crm_topbar_admin_campus_config'] = 'Campus configuration';
$string['commerce_identity_nav_merge'] = 'Merge identities';
$string['commerce_identity_merge_title'] = 'Merge accounts';
$string['commerce_identity_merge_ids'] = 'Moodle IDs to compare';
$string['commerce_identity_merge_preview'] = 'Preview merge';
$string['commerce_identity_merge_select_account'] = 'Select Moodle account #{$a}';
$string['commerce_identity_merge_prepare'] = 'Prepare merge';
$string['commerce_identity_merge_accounts'] = 'Compared accounts';
$string['commerce_identity_merge_keep'] = 'Keep';
$string['commerce_identity_merge_account'] = 'Account';
$string['commerce_identity_merge_pedagogy'] = 'Learning history';
$string['commerce_identity_merge_commerce'] = 'Commerce data';
$string['commerce_identity_merge_account_quality'] = 'Account quality';
$string['commerce_identity_merge_recommended'] = 'Recommended';
$string['commerce_identity_merge_pedagogy_summary'] = '{$a->courses} enrolled courses
{$a->completedcourses} completed courses
{$a->activities} completed activities
{$a->grades} grades · average {$a->average}%
Learning score: {$a->score}';
$string['commerce_identity_merge_commerce_summary'] = '{$a->purchases} purchases · {$a->grants} grants · {$a->digital} digital accesses
Commerce score: {$a->score}';
$string['commerce_identity_merge_confirmed'] = 'Confirmed account';
$string['commerce_identity_merge_unconfirmed'] = 'Unconfirmed account';
$string['commerce_identity_merge_lastaccess'] = 'Last access: {$a}';
$string['commerce_identity_merge_recalculate'] = 'Recalculate with this primary account';
$string['commerce_identity_merge_virtual_profile'] = 'Simulated final profile';
$string['commerce_identity_merge_virtual_profile_summary'] = 'Primary account: #{$a->userid} — {$a->name} — {$a->email}';
$string['commerce_identity_merge_transfer_summary'] = 'Planned Commerce transfer: {$a->purchases} purchases, {$a->grants} grants, {$a->digital} digital accesses and {$a->guests} guest sessions.';
$string['commerce_identity_merge_warnings'] = 'Points to review';
$string['commerce_identity_merge_warning_pedagogical_history'] = 'Account #{$a->userid} contains learning history. Its supported learning state will be consolidated into the retained account.';
$string['commerce_identity_merge_warning_shared_courses'] = 'The accounts share {$a->count} courses. Progress, grades and attempts may conflict.';
$string['commerce_identity_merge_warning_different_emails'] = 'The accounts use different email addresses. The primary account email will be kept.';
$string['commerce_identity_merge_warning_suspended_target'] = 'Selected primary account #{$a->userid} is suspended.';
$string['commerce_identity_merge_warning_generic'] = 'A point requires manual review.';
$string['commerce_identity_nav_provisioning'] = 'Create accounts';
$string['commerce_identity_provisioning_title'] = 'Create accounts for digital buyers';
$string['commerce_identity_provisioning_description'] = 'Create a Moodle space for Legacy Digital buyers who do not yet have an account, with dry-run and similar-account checks.';
$string['commerce_identity_provisioning_safety'] = 'No account is created during dry-run. Exact existing accounts and ambiguous identities are never recreated. Similar accounts require explicit confirmation before forcing creation.';
$string['commerce_identity_provisioning_filter_query'] = 'Email, first name or last name';
$string['commerce_identity_provisioning_filter_status'] = 'Status';
$string['commerce_identity_provisioning_email'] = 'Email';
$string['commerce_identity_provisioning_identity'] = 'Identity';
$string['commerce_identity_provisioning_purchases'] = 'Legacy purchases';
$string['commerce_identity_provisioning_status'] = 'Diagnostic';
$string['commerce_identity_provisioning_details'] = 'Details';
$string['commerce_identity_provisioning_override'] = 'Override';
$string['commerce_identity_provisioning_status_creatable'] = 'Safe to create';
$string['commerce_identity_provisioning_status_existing'] = 'Existing Moodle account';
$string['commerce_identity_provisioning_status_ambiguous'] = 'Multiple exact accounts';
$string['commerce_identity_provisioning_status_similar'] = 'Similar account to review';
$string['commerce_identity_provisioning_status_invalid'] = 'Invalid email';
$string['commerce_identity_provisioning_existing_user'] = 'Existing account: #{$a}. Use reconciliation instead of creating a duplicate.';
$string['commerce_identity_provisioning_ambiguous_users'] = 'Several accounts exist for this email: {$a}. Manual review is required.';
$string['commerce_identity_provisioning_preview_selected'] = 'Preview selected account creations';
$string['commerce_identity_provisioning_dryrun_title'] = 'Account creation dry-run';
$string['commerce_identity_provisioning_force_similar'] = 'Create despite similar account';
$string['commerce_identity_provisioning_confirm'] = 'I confirm that I reviewed this dry-run and any similarity warnings.';
$string['commerce_identity_provisioning_execute'] = 'Create confirmed accounts';
$string['commerce_identity_provisioning_confirmation_required'] = 'You must explicitly confirm account creation.';
$string['commerce_identity_provisioning_execution_summary'] = 'Created: {$a->created} · skipped/blocked: {$a->skipped} · errors: {$a->errors}';
$string['commerce_identity_provisioning_scan_truncated'] = 'The scan is limited to the {$a} most recent Legacy purchases. Use the filter to target an identity.';
$string['commerce_legacy_account_activation_title'] = 'Activate your CampusFR space';
$string['commerce_legacy_account_activation_intro'] = 'Hello {$a->firstname}, we created your CampusFR space to bring together the purchases and resources linked to {$a->email}. Simply choose your password to access it.';
$string['commerce_legacy_account_activation_submit'] = 'Activate my space';
$string['commerce_legacy_account_activation_invalid'] = 'This activation link is invalid or has expired.';
$string['commerce_legacy_account_activation_failed'] = 'We could not activate your CampusFR space.';

$string['commerce_personal_offer_validity_title'] = 'Offer validity';
$string['commerce_personal_offer_validity_help'] = 'Choose one fixed campaign deadline or an individual duration calculated from the issuance of each offer.';
$string['commerce_personal_offer_validity_mode'] = 'Validity mode';
$string['commerce_personal_offer_validity_fixed'] = 'Fixed date and time';
$string['commerce_personal_offer_validity_duration'] = 'Duration after issuance';
$string['commerce_personal_offer_validity_duration_value'] = 'Duration';
$string['commerce_personal_offer_validity_duration_unit'] = 'Unit';
$string['commerce_personal_offer_validity_hours'] = 'Hours';
$string['commerce_personal_offer_validity_duration_help'] = 'The duration starts when the offer is issued. Resending the same email does not extend its deadline.';
$string['commerce_personal_offer_validity_timezone'] = 'Time zone';
$string['commerce_personal_offer_validity_timezone_help'] = 'Used to interpret and display fixed dates and times. Europe/Paris is recommended for CampusFR campaigns.';

$string['admin_event_user_legacy_digital_provisioned'] = 'Legacy digital access provisioned';

$string['commerce_mail_personal_offer_direct_checkout'] = 'Pay directly';

$string['commerce_personal_offer_workflow_title'] = 'Campaign preparation';
$string['commerce_personal_offer_workflow_help'] = 'Follow the preparation steps before issuing offers. The checks shown reflect the campaign’s actual state.';
$string['commerce_personal_offer_workflow_commercial'] = 'Commercial offer';
$string['commerce_personal_offer_workflow_commercial_ready'] = 'Product, commercial terms and validity are saved.';
$string['commerce_personal_offer_workflow_email'] = 'Email and journey';
$string['commerce_personal_offer_workflow_email_ready'] = 'Custom content and destination are configured.';
$string['commerce_personal_offer_workflow_email_missing'] = 'Configure the email content and destination before sending.';
$string['commerce_personal_offer_workflow_audience'] = 'Audience';
$string['commerce_personal_offer_workflow_audience_ready'] = '{$a} currently eligible recipient(s).';
$string['commerce_personal_offer_workflow_audience_missing'] = 'Preview the campaign to calculate eligible recipients.';
$string['commerce_personal_offer_workflow_snapshot'] = 'Frozen selection';
$string['commerce_personal_offer_workflow_snapshot_ready'] = 'The recipient selection is frozen.';
$string['commerce_personal_offer_workflow_snapshot_missing'] = 'Create the snapshot after reviewing the audience.';
$string['commerce_personal_offer_workflow_issue'] = 'Offer issuance';
$string['commerce_personal_offer_workflow_issue_ready'] = 'Personal offers have been generated.';
$string['commerce_personal_offer_workflow_issue_missing'] = 'Final step after validating the snapshot.';
$string['commerce_personal_offer_workflow_configure_email'] = 'Configure email';
$string['commerce_personal_offer_workflow_preview_test'] = 'Preview / send a test';
$string['commerce_personal_offer_workflow_view_audience'] = 'View audience';
$string['commerce_personal_offer_workflow_showroom'] = 'Showroom';
$string['commerce_personal_offer_workflow_direct_checkout_also'] = 'direct payment is also available in the email';
$string['commerce_personal_offer_campaign_email_saved_preview_next'] = 'Email configuration saved. Now review the preview and send a test email before issuing offers.';

$string['commerce_personal_offer_campaign_banner_title'] = 'Email banner';
$string['commerce_personal_offer_campaign_banner_help'] = 'Add a banner for this campaign. It replaces the default CampusFR header only in emails from this campaign.';
$string['commerce_personal_offer_campaign_banner_file'] = 'Banner image';
$string['commerce_personal_offer_campaign_banner_format_help'] = 'JPEG, PNG or WebP, maximum 8 MB. For best results, use a horizontal image around 1600 × 440 px.';
$string['commerce_personal_offer_campaign_banner_delete'] = 'Remove the custom banner and restore the default header';
$string['commerce_personal_offer_campaign_banner_upload_error'] = 'The campaign banner could not be uploaded.';
$string['commerce_personal_offer_campaign_banner_too_large'] = 'The campaign banner exceeds the 8 MB maximum size.';
$string['commerce_personal_offer_campaign_banner_invalid_type'] = 'The banner must be a JPEG, PNG or WebP image.';
$string['commerce_identity_similarity_reason_email_name_combination'] = 'Similar email + same last name';
$string['commerce_identity_legacy_link_action'] = 'Attach to this account';
$string['commerce_identity_legacy_link_title'] = 'Attach a Legacy identity to a Moodle account';
$string['commerce_identity_legacy_link_description'] = 'Keep the existing Moodle account and attach Legacy Digital purchases from another identity without changing its email or learning progress.';
$string['commerce_identity_legacy_link_dryrun'] = 'Dry-run: no data is modified. Carefully review both identities before confirming.';
$string['commerce_identity_legacy_link_source'] = 'Legacy Digital identity';
$string['commerce_identity_legacy_link_target'] = 'Moodle account to keep';
$string['commerce_identity_legacy_link_purchase_count'] = '{$a} Legacy Digital purchase(s)';
$string['commerce_identity_legacy_link_preserves_target'] = 'The target Moodle account is kept unchanged: email, password, enrolments, progress, grades and learning history are not modified. Only the userid of unlinked Legacy Digital purchases is populated.';
$string['commerce_identity_legacy_link_confirm'] = 'I confirm that these identities belong to the same person and that this Moodle account must be kept.';
$string['commerce_identity_legacy_link_execute'] = 'Attach purchases to Moodle account';
$string['commerce_identity_legacy_link_success'] = '{$a->count} Legacy Digital purchase(s) were attached to Moodle account #{$a->userid}.';
$string['commerce_identity_legacy_link_no_purchases'] = 'No unlinked Legacy Digital purchase was found for this email.';
$string['commerce_identity_legacy_link_similarity_too_low'] = 'The similarity level is too low to allow this attachment. Review the identities manually.';
$string['commerce_identity_legacy_link_confirmation_required'] = 'You must explicitly confirm that both identities belong to the same person.';
$string['commerce_personal_offer_correct_beneficiary'] = 'Correct beneficiary';
$string['commerce_personal_offer_correct_beneficiary_help'] = 'Use this exceptional repair only for an issued Personal Offer that has not been redeemed and whose email has not been sent. The campaign snapshot key and secure offer token are preserved.';
$string['commerce_personal_offer_correct_beneficiary_current'] = 'Current beneficiary';
$string['commerce_personal_offer_correct_beneficiary_email'] = 'Correct Moodle account email';
$string['commerce_personal_offer_correct_beneficiary_preview'] = 'Preview correction';
$string['commerce_personal_offer_correct_beneficiary_preview_title'] = 'Identity correction preview';
$string['commerce_personal_offer_correct_beneficiary_confirm'] = 'Confirm beneficiary correction';
$string['commerce_personal_offer_correct_beneficiary_success'] = 'The Personal Offer beneficiary was corrected and the unsent email was safely requeued when applicable.';
$string['commerce_personal_offer_correct_beneficiary_unavailable'] = 'This Personal Offer can no longer have its beneficiary corrected safely.';
$string['commerce_personal_offer_correct_beneficiary_user_not_unique'] = 'Exactly one active Moodle account must match this email address.';

// Commerce 7.95 M5.1 — Product Statistics 2.0.
$string['commerce_statistics_period_custom'] = 'Custom range';
$string['commerce_m51_title'] = 'Product performance';
$string['commerce_m51_subtitle'] = 'Collected sales, actual deliveries and payment health. Pending payments are never counted as sales.';
$string['commerce_m51_paid_orders'] = 'Paid orders';
$string['commerce_m51_paid_orders_help'] = 'Orders containing this product with a confirmed payment.';
$string['commerce_m51_units_sold'] = 'Units sold';
$string['commerce_m51_units_sold_help'] = 'Sum of product quantities in paid orders.';
$string['commerce_m51_manual_grants'] = 'Free grants';
$string['commerce_m51_manual_grants_help'] = 'Administrative CRM deliveries without a purchase. Currency-independent.';
$string['commerce_m51_total_delivered'] = 'Total delivered';
$string['commerce_m51_total_delivered_help'] = 'Sold units + fulfilled free orders + administrative grants.';
$string['commerce_m51_payment_pending'] = 'Pending payments';
$string['commerce_m51_payment_pending_help'] = 'Latest payment attempt is still pending. Never counted as a sale.';
$string['commerce_m51_payment_failed'] = 'Failed payments';
$string['commerce_m51_payment_failed_help'] = 'Latest attempt failed, was declined or expired.';
$string['commerce_m51_payment_cancelled'] = 'Cancelled payments';
$string['commerce_m51_payment_cancelled_help'] = 'Latest payment attempt was explicitly cancelled.';
$string['commerce_m51_revenue_collected'] = 'Collected revenue';
$string['commerce_m51_revenue_evolution'] = 'Collected revenue over time';
$string['commerce_m51_deliveries_evolution'] = 'Product deliveries';
$string['commerce_m51_delivery_paid'] = 'Purchased and paid';
$string['commerce_m51_delivery_free_order'] = 'Free order';
$string['commerce_m51_delivery_manual'] = 'Administrative grant';
$string['commerce_m51_payment_paid'] = 'Paid';
$string['commerce_m51_payment_refunded'] = 'Refunded';
$string['commerce_m51_payment_distribution'] = 'Payment status distribution';
$string['commerce_m51_from'] = 'From';
$string['commerce_m51_until'] = 'To';
$string['commerce_m51_export_excel'] = 'Export to Excel';
$string['commerce_m51_export_summary'] = 'Summary';
$string['commerce_m51_export_orders'] = 'Orders';
$string['commerce_m51_export_deliveries'] = 'Grants';

// Commerce 7.95 M5.1G — comparison trends.
$string['commerce_m51_comparison_previous'] = 'Change compared with the immediately preceding period of the same duration: {$a->from} to {$a->until}.';
$string['commerce_m51_comparison_today'] = 'Change compared with yesterday over the same time window: {$a->from} to {$a->until}.';
$string['commerce_m51_trend_new'] = 'New';
$string['commerce_m51_show_chart_data'] = "Show chart data";

// Commerce 7.95 M5.2 — product steering analytics.
$string['commerce_m52_revenue_period'] = 'Revenue by period';
$string['commerce_m52_revenue_cumulative'] = 'Cumulative revenue';
$string['commerce_m52_revenue_display'] = 'Revenue display mode';
$string['commerce_m52_average_order'] = 'Average collected order';
$string['commerce_m52_payment_quality'] = 'Payments';
$string['commerce_m52_success_rate'] = 'Payment success rate';
$string['commerce_m52_funnel'] = 'Conversion funnel';
$string['commerce_m52_attempts'] = 'Payment attempts';
$string['commerce_m52_confirmed_payments'] = 'Confirmed payments';
$string['commerce_m52_deliveries'] = 'Units delivered';
$string['commerce_m52_acquisition_origin'] = 'Acquisition source';
$string['commerce_m52_acq_standard'] = 'Standard purchase';
$string['commerce_m52_acq_promotion'] = 'Promotion';
$string['commerce_m52_acq_personaloffer'] = 'Personal offer';
$string['commerce_m52_acq_free'] = 'Free order';
$string['commerce_m52_acq_manual'] = 'Admin grant';
$string['commerce_m52_provider_distribution'] = 'Collected payments by provider';
$string['commerce_m52_provider_orders'] = '{$a} order(s)';
$string['commerce_m52_export_payments'] = 'Payments';

// Commerce 7.95 M5.3 — premium global statistics.
$string['commerce_m53_export_excel'] = 'Export to Excel';
$string['commerce_m53_paid_orders'] = 'Paid orders';
$string['commerce_m53_paid_customers'] = 'Paying customers';
$string['commerce_m53_units_sold'] = 'Units sold';
$string['commerce_m53_total_delivered'] = 'Total delivered';
$string['commerce_m53_revenue_collected'] = 'Collected revenue';
$string['commerce_m53_average_order'] = 'Average order';
$string['commerce_m53_payment_success_rate'] = 'Payment success rate';
$string['commerce_m53_pending_fulfillments'] = 'Pending deliveries';
$string['commerce_m53_funnel'] = 'Global conversion funnel';
$string['commerce_m53_payment_attempts'] = 'Payment attempts';
$string['commerce_m53_commercial_evolution'] = 'Commercial evolution';
$string['commerce_m53_revenue_evolution'] = 'Collected revenue evolution';
$string['commerce_m53_paid_orders_evolution'] = 'Paid orders evolution';
$string['commerce_m53_payment_health'] = 'Payment health';
$string['commerce_m53_payment_distribution'] = 'Payment status distribution';
$string['commerce_m53_export_summary'] = 'Summary';
$string['commerce_m53_export_orders'] = 'Orders';
$string['commerce_m53_export_grants'] = 'Grants';
$string['commerce_statistics_period_from'] = 'From';
$string['commerce_statistics_period_until'] = 'To';

// Commerce 7.95 M5.3B — branched payment journeys.
$string['commerce_m53_payment_journey'] = 'Payment journey';
$string['commerce_m53_payment_journey_help'] = 'Each attempt ends in one current state. Branches are exclusive, so together they never exceed the root number of attempts.';
$string['commerce_m53_payment_not_completed'] = 'Not completed';
$string['commerce_m53_global_conversion'] = 'Overall conversion: {$a->rate}% ({$a->paid} successful payments out of {$a->attempts} attempts).';
$string['commerce_m53_deliveries_breakdown'] = 'Deliveries';
$string['commerce_m53_delivered_from_paid'] = 'From paid orders';
$string['commerce_m53_delivered_from_free'] = 'From free orders';
$string['commerce_m53_delivered_from_manual'] = 'Administrative grants';
$string['commerce_m53_acquisition_help'] = 'This breakdown explains how units were acquired. It is independent from the current payment status.';
$string['commerce_m53_product_payments'] = 'Payments by product';
$string['commerce_m53_product_payments_help'] = 'Each product shows the latest payment state of its orders. Categories are mutually exclusive.';
$string['commerce_m53_product'] = 'Product';
$string['commerce_m53_conversion'] = 'Conversion';

// 7.95M6 — Legacy digital identity quality and correction.
$string['admin_event_user_legacy_digital_identity_updated'] = 'Legacy Digital customer details corrected';
$string['commerce_identity_email_quality_invalid'] = 'Invalid email';
$string['commerce_identity_email_quality_ok'] = 'No issue detected';
$string['commerce_identity_email_quality_suspect'] = 'Suspicious email';
$string['commerce_identity_legacy_edit_description'] = 'Edit the customer details used by Legacy Digital purchases and future Personal Offer audience selections.';
$string['commerce_identity_legacy_edit_detected'] = 'A likely typo was detected. Suggested address: {$a}';
$string['commerce_identity_legacy_edit_scope_notice'] = 'This operation changes Legacy Digital data only. It never changes a Moodle account email automatically. Personal Offers that have already been issued remain unchanged and must be corrected or reissued separately when needed.';
$string['commerce_identity_legacy_edit_success'] = '{$a} Legacy Digital record(s) updated.';
$string['commerce_identity_legacy_edit_title'] = 'Correct Legacy Digital customer details';
$string['commerce_identity_legacy_edit_update_same'] = 'Also apply this correction to every other Legacy Digital purchase carrying the exact same old email';
$string['commerce_identity_legacy_quality_customer'] = 'Legacy customer';
$string['commerce_identity_legacy_quality_description'] = 'Find potentially incorrect Legacy Digital buyer details and correct their email, first name or last name at the source.';
$string['commerce_identity_legacy_quality_diagnostic'] = 'Diagnostic';
$string['commerce_identity_legacy_quality_empty'] = 'No email address matches this filter.';
$string['commerce_identity_legacy_quality_filter'] = 'Email quality';
$string['commerce_identity_legacy_quality_latest_purchase'] = 'Latest purchase: #{$a}';
$string['commerce_identity_legacy_quality_notice'] = 'The diagnostic is deliberately conservative: it flags invalid syntax and domains very close to known providers (for example gmai.com → gmail.com). Unknown custom domains are not treated as errors.';
$string['commerce_identity_legacy_quality_purchase'] = 'Legacy purchase';
$string['commerce_identity_legacy_quality_purchase_count'] = '{$a} purchase(s) with this email';
$string['commerce_identity_legacy_quality_search'] = 'Search by email, first name or last name';
$string['commerce_identity_legacy_quality_suggestion'] = 'Suggestion: {$a}';
$string['commerce_identity_legacy_quality_title'] = 'Legacy Digital email quality';
$string['commerce_identity_nav_legacy_quality'] = 'Legacy email quality';

$string['commerce_identity_similarity_reason_email_domain_close'] = 'Similar email provider (possible typo)';
$string['commerce_identity_similarity_reason_alternate_name'] = 'Matching alternate / phonetic name';
$string['commerce_identity_similarity_score_help'] = 'The score is an explainable indication, not an automatic merge decision. Signal badges show their contribution.';

// Commerce 7.95 M7.3/M7.4 — manual merge selection and preview.
$string['commerce_identity_merge_description'] = 'Search for and manually select Moodle accounts, compare them, choose the primary account, then execute only transfers certified as safe.';
$string['commerce_identity_merge_dryrun_only'] = 'Selection and preview do not modify data. The merge runs only after explicit confirmation and after every safety check has passed.';
$string['commerce_identity_merge_nonmergeable'] = 'Supported learning progress and commercial data will be consolidated before old accounts are disabled. Historical logs and audits intentionally retain their original actor references; privileged accounts remain protected from merging.';
$string['commerce_identity_merge_manual_selection_title'] = 'Manual account selection';
$string['commerce_identity_merge_manual_selection_help'] = 'Search for any Moodle account by ID, first name, last name, username or email. This selection is independent from the similar-account engine.';
$string['commerce_identity_merge_search_label'] = 'Search for an account to add';
$string['commerce_identity_merge_search_placeholder'] = 'E.g. 847, natalia@example.com, Natalia Kutrowski…';
$string['commerce_identity_merge_search_results'] = 'Search results';
$string['commerce_identity_merge_search_empty'] = 'No matching Moodle account was found.';
$string['commerce_identity_merge_add_account'] = 'Add to merge';
$string['commerce_identity_merge_reset_selection'] = 'Reset selection';
$string['commerce_identity_merge_select_two_hint'] = 'Select at least two accounts to preview a merge.';
$string['commerce_identity_merge_direction_sources'] = 'Source account(s)';
$string['commerce_identity_merge_direction_target'] = 'Primary account kept';

$string['commerce_identity_merge_blocker_privileged'] = 'Account #{$a->userid} has privileged or system-level access and cannot participate in this merge.';
$string['commerce_identity_merge_m756_scope_title'] = 'Data covered by the merge';
$string['commerce_identity_merge_m756_scope_detail'] = 'The preview found {$a->learning} learning item(s) and {$a->commerce} commercial item(s) on the accounts being merged. They will be consolidated before the old accounts are disabled.';
$string['commerce_identity_merge_conflicts_title'] = 'Learning conflict decisions';
$string['commerce_identity_merge_conflicts_help'] = 'The items below exist on both accounts with different states. Choose individually which data to keep. Unambiguous unions are merged automatically.';
$string['commerce_identity_merge_conflict_grade'] = 'Grade — Moodle item #{$a->id}';
$string['commerce_identity_merge_conflict_activity'] = 'Progress — Moodle activity #{$a->id}';
$string['commerce_identity_merge_conflict_recommended'] = 'Recommended choice: account {$a}. You can override this recommendation.';
$string['commerce_identity_merge_conflict_choice'] = 'Account {$a->letter} — user #{$a->userid} — value: {$a->value}';

$string['commerce_identity_merge_certification_failed'] = 'The post-merge integrity check failed. No changes were kept.';

$string['commerce_identity_merge_certification_title'] = 'Certified merge';

$string['commerce_identity_merge_certification_summary'] = '{$a->checks} integrity check(s) passed. {$a->decisions} manual learning decision(s) recorded.';

$string['commerce_identity_merge_certification_primary_account_active'] = 'Primary account is active and accessible.';

$string['commerce_identity_merge_certification_merged_account_suspended'] = '{$a} old account(s) correctly disabled.';

$string['commerce_identity_merge_certification_ownership_transferred'] = '{$a} commercial ownership check(s) passed: no supported data remains attached to old accounts.';

$string['commerce_identity_merge_certification_learning_state_transferred'] = '{$a} learning-state check(s) passed: supported progress is attached to the retained account.';

$string['commerce_identity_merge_certification_manual_learning_decision_applied'] = '{$a} manual learning decision(s) applied and verified.';

$string['commerce_identity_merge_certification_customer_email_aligned'] = '{$a} commercial identity check(s) passed: active entitlements use the retained account email.';

$string['commerce_identity_merge_certification_audit'] = 'Audit reference: {$a}. Transfer details and manual decisions are retained in the merge history.';

$string['user360_merge_history_title'] = 'Merge history';

$string['user360_merge_history_description'] = 'Certified history of accounts merged with this identity.';

$string['user360_merge_certified'] = 'Certified merge';

$string['user360_merge_completed'] = 'Merge completed';

$string['user360_merge_retained_account'] = 'This is the retained account.';

$string['user360_merge_absorbed_accounts'] = 'Merged accounts:';

$string['user360_merge_absorbed_notice'] = 'This account was merged into another account.';

$string['user360_merge_open_retained'] = 'Open retained account';

$string['user360_merge_summary'] = '{$a->transfers} item(s) transferred · {$a->decisions} manual decision(s) · {$a->checks} check(s) passed';

$string['user360_merge_performed_by'] = 'Merge performed by {$a}.';

$string['user360_merge_audit_reference'] = 'Audit reference: {$a}';

$string['user360_merge_view_details'] = 'View transfer details';

$string['user360_merge_transfer_accounts'] = 'Disabled accounts';

$string['user360_merge_transfer_notes'] = 'CRM notes';

$string['user360_merge_transfer_scores'] = 'CRM scores';

$string['user360_merge_transfer_inbox'] = 'Inbox contacts';

$string['user360_merge_transfer_tags'] = 'Tags';

$string['user360_merge_transfer_tags_deduplicated'] = 'Deduplicated tags';

$string['user360_merge_transfer_learning'] = 'Learning data';

$string['user360_merge_transfer_legacy'] = 'Legacy data';

$string['user360_merge_transfer_commerce'] = 'Commerce data';

// Alfa payment reconciliation.
$string['commerce_alfa_reconciliation_payment_not_found'] = 'The Commerce payment attempt was not found.';
$string['commerce_alfa_reconciliation_attempt_not_found'] = 'No Alfa payment attempt was found for this purchase.';
$string['commerce_alfa_reconciliation_wrong_provider'] = 'This payment is not an Alfa payment and cannot use Alfa reconciliation.';
$string['commerce_alfa_reconciliation_missing_orderid'] = 'The Alfa order identifier is missing; the payment cannot be reconciled safely.';
$string['commerce_alfa_reconciliation_not_safe'] = 'Alfa reconciliation was refused because the provider and Campus data do not match safely: {$a}';

$string['commerce_alfa_crm_title'] = 'Alfa payment reconciliation';

$string['commerce_alfa_crm_description'] = 'Verify the actual bank status before automatically restoring the CampusFR payment, access and emails.';

$string['commerce_alfa_crm_live_warning'] = 'Alfa information shown on this page is checked live with the bank. Opening this page does not modify CampusFR data.';

$string['commerce_alfa_crm_provider_error'] = 'Unable to verify this payment with Alfa: {$a}';

$string['commerce_alfa_crm_state_complete'] = 'Payment already fully processed';

$string['commerce_alfa_crm_state_complete_help'] = 'CampusFR and Alfa are consistent. No further reconciliation is required.';

$string['commerce_alfa_crm_state_reconcilable'] = 'Payment deposited by Alfa, reconciliation available';

$string['commerce_alfa_crm_state_reconcilable_help'] = 'All safety checks match. CampusFR can resume its normal pipeline to confirm the payment and restore access.';

$string['commerce_alfa_crm_state_blocked'] = 'Reconciliation blocked';

$string['commerce_alfa_crm_state_blocked_help'] = 'At least one check does not match. No payment action will be executed.';

$string['commerce_alfa_crm_campus_section'] = 'CampusFR status';

$string['commerce_alfa_crm_alfa_section'] = 'Live Alfa status';

$string['commerce_alfa_crm_payment_id'] = 'Payment attempt';

$string['commerce_alfa_crm_order_id'] = 'Alfa reference';

$string['commerce_alfa_crm_order_status'] = 'Alfa order status';

$string['commerce_alfa_crm_payment_state'] = 'Alfa payment state';

$string['commerce_alfa_crm_deposited_amount'] = 'Actually deposited amount';

$string['commerce_alfa_crm_checks_section'] = 'Safety checks';

$string['commerce_alfa_crm_check_provider_paid'] = 'Alfa confirms the payment is deposited';

$string['commerce_alfa_crm_check_amount'] = 'Alfa amount matches CampusFR amount';

$string['commerce_alfa_crm_check_currency'] = 'Alfa currency matches CampusFR currency';

$string['commerce_alfa_crm_check_approved'] = 'Approved amount matches the expected amount';

$string['commerce_alfa_crm_check_deposited'] = 'Deposited amount matches the expected amount';

$string['commerce_alfa_crm_check_ok'] = 'Matched';

$string['commerce_alfa_crm_check_failed'] = 'Review';

$string['commerce_alfa_crm_blockers'] = 'Reconciliation cannot run for the following reasons:';

$string['commerce_alfa_crm_refresh'] = 'Check again with Alfa';

$string['commerce_alfa_crm_execute'] = 'Reconcile payment and restore access';

$string['commerce_alfa_crm_execute_confirm'] = 'CampusFR will query Alfa again and, only if all checks still match, run the normal payment pipeline: confirm the order, create entitlements and send emails. Continue?';

$string['commerce_alfa_crm_success'] = 'The Alfa payment was successfully reconciled. The CampusFR order and access have been restored.';

$string['commerce_alfa_crm_verify'] = 'Check with Alfa';

$string['commerce_alfa_crm_verify_short'] = 'Check Alfa';

$string['commerce_alfa_crm_purchase_panel'] = 'Alfa payment';

$string['commerce_alfa_crm_purchase_pending_help'] = 'This Alfa payment is not yet fully finalized in CampusFR. You can request a live bank check without changing the order.';

$string['commerce_alfa_crm_purchase_complete_help'] = 'This Alfa order is already finalized. A live check remains available to verify consistency with the bank.';

$string['commerce_alfa_reconciliation_blocker_provider_not_paid'] = 'Alfa does not yet confirm a deposited payment.';

$string['commerce_alfa_reconciliation_blocker_amount_mismatch'] = 'The amount returned by Alfa does not match the CampusFR amount.';

$string['commerce_alfa_reconciliation_blocker_currency_mismatch'] = 'The currency returned by Alfa does not match the CampusFR currency.';

$string['commerce_alfa_reconciliation_blocker_approved_amount_mismatch'] = 'The amount approved by Alfa does not match the expected amount.';

$string['commerce_alfa_reconciliation_blocker_deposited_amount_mismatch'] = 'The amount actually deposited by Alfa does not match the expected amount.';

$string['commerce_alfa_reconciliation_blocker_provider_event_not_completed'] = 'The Alfa status does not produce a completed payment event.';

$string['task_reconcile_alfa_payments'] = 'Automatic Alfa payment reconciliation';

$string['settings:alfa_reconciliation_header'] = 'Automatic Alfa reconciliation';

$string['settings:alfa_reconciliation_header_desc'] = 'Automatically recovers payments deposited by Alfa when the browser return or server notification did not finalize the CampusFR order.';

$string['settings:alfa_reconciliation_cron_enabled'] = 'Enable automatic Alfa reconciliation';

$string['settings:alfa_reconciliation_cron_enabled_desc'] = 'The scheduled task checks Alfa for CampusFR payments still pending and finalizes only payments whose provider status, amount and currency are confirmed by Alfa.';

$string['settings:alfa_reconciliation_min_age'] = 'Minimum age before checking (seconds)';

$string['settings:alfa_reconciliation_min_age_desc'] = 'Minimum time after payment creation before it can be checked automatically. Recommended value: 300 seconds.';

$string['settings:alfa_reconciliation_max_age'] = 'Maximum payment age to check (seconds)';

$string['settings:alfa_reconciliation_max_age_desc'] = 'Payments older than this are not queried automatically. Recommended value: 172800 seconds (48 h).';

$string['settings:alfa_reconciliation_batch_size'] = 'Maximum payments per run';

$string['settings:alfa_reconciliation_batch_size_desc'] = 'Limits the number of Alfa API calls made by one scheduled-task run. Recommended value: 20.';

$string['commerce_alfa_confirmation_title'] = 'We are confirming your payment…';

$string['commerce_alfa_confirmation_message'] = 'This usually takes only a few seconds.';

$string['commerce_alfa_confirmation_security_title'] = 'Your data is secure';

$string['commerce_alfa_confirmation_security_message'] = 'We are verifying the transaction directly with our payment provider.';

$string['commerce_alfa_confirmation_confirmed_title'] = 'Payment confirmed!';

$string['commerce_alfa_confirmation_confirmed_message'] = 'Your access is ready. Redirecting you now…';

$string['commerce_provider_transition_title'] = 'Preparing your secure payment…';

$string['commerce_provider_transition_message'] = 'One moment: we are opening the secure payment page.';

$string['commerce_provider_transition_security_title'] = 'Secure connection';

$string['commerce_provider_transition_security_message'] = 'You are about to be redirected to our payment provider.';

$string['commerce_provider_transition_alfa'] = 'Redirecting to Alfa';

$string['commerce_provider_transition_default'] = 'Redirecting to the payment provider';

$string['commerce_payment_splash_preview_title'] = 'Payment screen preview';

$string['commerce_payment_splash_preview_outbound'] = 'Leaving for provider';

$string['commerce_payment_splash_preview_return'] = 'Payment confirmation';

$string['task_reconcile_stripe_payments'] = 'Automatic Stripe payment reconciliation';
$string['stripe_reconciliation_heading'] = 'Automatic Stripe reconciliation';
$string['stripe_reconciliation_desc'] = 'Safety net: checks Stripe for Campus payments still pending and only fulfils Checkout Sessions that are actually paid.';
$string['stripe_reconciliation_cron_enabled'] = 'Enable automatic Stripe reconciliation';
$string['stripe_reconciliation_cron_enabled_desc'] = 'Periodically checks pending Stripe payments. Stripe status, amount and currency must all match before fulfilment.';
$string['stripe_reconciliation_batch_size'] = 'Maximum Stripe batch size';
$string['stripe_reconciliation_min_age'] = 'Minimum age before Stripe check (seconds)';
$string['stripe_reconciliation_max_age'] = 'Maximum Stripe age inspected (seconds)';
$string['commerce_stripe_reconciliation_payment_not_found'] = 'Stripe payment not found.';
$string['commerce_stripe_reconciliation_not_safe'] = 'Stripe reconciliation cannot be executed safely: {$a}';
$string['commerce_stripe_reconciliation_wrong_provider'] = 'This payment is not a Stripe payment.';
$string['commerce_stripe_reconciliation_missing_session'] = 'The Stripe Checkout Session is missing from this payment.';

$string['commerce_guest_unfinished_recovery_title'] = 'We found your interrupted checkout';

$string['commerce_guest_unfinished_recovery_message'] = 'Your provisional CampusFR account has been preserved. You can resume payment without a password; once payment is confirmed, we will ask you to create one.';

// 7.95M10 - Advanced personal-offer audiences.
$string['commerce_personal_offer_m10_sources_title'] = 'Alternative audience sources';
$string['commerce_personal_offer_m10_sources_help'] = 'The primary source and the sources added here are combined with OR. A customer found in at least one source enters the initial audience.';
$string['commerce_personal_offer_m10_add_source_or'] = '+ Add an OR source';
$string['commerce_personal_offer_m10_filters_title'] = 'Advanced eligibility filters';
$string['commerce_personal_offer_m10_filters_help'] = 'Rules inside one group are combined with AND. Groups are combined with OR. Ownership is checked across Native and Legacy data.';
$string['commerce_personal_offer_m10_add_rule'] = '+ Add a rule';
$string['commerce_personal_offer_m10_add_or_group'] = '+ Add an OR group';
$string['commerce_personal_offer_m10_filters_example'] = 'Example: owns the Cards (Native or Legacy), AND does not own the Native Trainer, AND does not own the Legacy Trainer.';
$string['commerce_personal_offer_m10_group_first'] = 'All rules in this group must match (AND)';
$string['commerce_personal_offer_m10_group_or'] = 'OR — group {n}';
$string['commerce_personal_offer_m10_operator_owns'] = 'Owns';
$string['commerce_personal_offer_m10_operator_not_owns'] = 'Does not own';
$string['commerce_personal_offer_m10_source_native_prefix'] = 'Native';
$string['commerce_personal_offer_m10_source_legacy_digital_prefix'] = 'Legacy digital';
$string['commerce_personal_offer_m10_source_legacy_plan_prefix'] = 'Legacy subscription';
$string['commerce_personal_offer_reason_advanced_rules_not_matched'] = 'Does not match the advanced audience filters.';

