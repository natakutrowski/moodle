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

$string['your_subscriptions'] = 'Your purchases';
$string['no_active_subscriptions'] = 'You don’t have any active purchases.';

$string['pricepaid'] = 'Price paid';

$string['courselist'] = 'Course list';

$string['close'] = 'Close';

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

$string['details'] = 'Details';
$string['subscription_details'] = 'Purchase details';

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
$string['provider_trial']  = 'Trial';

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

$string['pricing_missing_price'] = 'No price is defined for this plan and currency ({$a}).';
$string['cannot_purchase_trial_plan'] = 'This plan is a trial plan and cannot be purchased.';
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

$string['digital_pdf_badge'] = 'Campus<small><sup>FR</sup></small> PDF';
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
$string['digital_success_summary_title'] = 'Purchase summary';
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

$string['planentitlements'] = 'Plan access rights';
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

$string['planupgrades'] = 'Plan upgrades';
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
$string['active'] = 'Active';
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
$string['unlock_full_button'] = 'Upgrade to full access';

$string['restricted_access_title'] = 'Restricted access';
$string['restricted_access_text'] = 'Buy the course to unlock this activity.';
$string['buy'] = 'Buy';

$string['plan_already_covered'] = 'You already have equivalent or higher access to this content.';
$string['all_courses_owned_title'] = 'You already have access to all available courses';
$string['all_courses_owned_text'] = 'No new purchase is needed right now. You can continue learning from your course area.';

$string['unlock_subscriber_title'] = 'Subscribers-only activity';
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
$string['digital_purchase_details'] = 'Digital purchase details';
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
$string['crm_user_profile'] = 'User profile';
$string['crm_search_user_placeholder'] = 'Search by first name, last name or email';
$string['crm_no_users_found'] = 'No users found.';
$string['crm_no_subscriptions'] = 'No subscriptions found for this user.';
$string['crm_no_digital_purchases'] = 'No digital purchases found for this user.';
$string['view_moodle_profile'] = 'View Moodle profile';

$string['admin_card_crm_users_title'] = 'CRM users';
$string['admin_card_crm_users_desc'] = 'Search for a user and view their complete profile.';
$string['subscriptions'] = 'Subscriptions';
$string['digital_purchases'] = 'Digital purchases';
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
$string['adminlog_digital_link_resent'] = 'Digital link resent';

$string['adminlog_payment_request_created'] = 'Payment request created';
$string['adminlog_payment_request_paid'] = 'Payment request paid';
$string['adminlog_payment_request_failed'] = 'Payment request failed';
$string['adminlog_payment_request_cancelled'] = 'Payment request cancelled';

$string['adminlog_trial_started'] = 'Trial started';
$string['adminlog_trial_expired'] = 'Trial expired';

$string['change_user'] = 'Change user';
$string['crm_accessible_courses'] = 'Accessible courses';
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
$string['dashboard_stats_new_subscriptions'] = 'New subscriptions';
$string['dashboard_stats_digital_purchases'] = 'Digital purchases';
$string['dashboard_stats_revenue'] = 'Digital revenue';
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

$string['crm_timeline_collapse_all'] = 'Collapse / expand details';
$string['crm_timeline_view_details'] = 'View details';
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

$string['command_action_users_title'] = 'View CRM users';
$string['command_action_users_subtitle'] = 'Open the user and customer list';

$string['command_action_products_title'] = 'View digital products';
$string['command_action_products_subtitle'] = 'Manage CampusFR digital products';

$string['command_action_product_create_title'] = 'Create digital product';
$string['command_action_product_create_subtitle'] = 'Add a new digital product';

$string['command_action_purchases_title'] = 'View digital purchases';
$string['command_action_purchases_subtitle'] = 'Review digital purchases and payments';

$string['command_action_subscriptions_title'] = 'View subscriptions';
$string['command_action_subscriptions_subtitle'] = 'Review and manage user subscriptions';

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
$string['dashboard_issue_email_error_title'] = 'Emails not sent';
$string['dashboard_issue_email_error_desc'] = 'Digital purchases with an email sending error.';
$string['dashboard_issue_expired_token_title'] = 'Expired download links';
$string['dashboard_issue_expired_token_desc'] = 'Purchases with an expired download token.';

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

$string['crm_user_column_user'] = 'User';
$string['crm_user_column_tags'] = 'Tags';
$string['crm_user_column_score'] = 'CRM score';
$string['crm_user_column_risk'] = 'Risk';
$string['crm_user_column_intelligence'] = 'Intelligence';

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