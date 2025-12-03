<?php
$string['pluginname'] = 'Campus — Course page';
$string['view_trial'] = 'Access the trial course';
$string['view_real']  = 'Go to the course';
$string['course_hidden'] = 'This course is not visible to you.';
$string['course_notfound'] = 'Course not found.';
$string['subscribe_now'] = 'Subscribe';
$string['close'] = 'Close';

$string['set_trialcourses'] = 'Trial courses (IDs, comma-separated)';
$string['set_trialcourses_desc'] = 'Enter the IDs of the trial courses, e.g., 12,34,56.';
$string['set_trialdays'] = 'Trial duration (days)';
$string['set_trialrole'] = 'Role for trial accounts';
$string['set_trialrole_desc'] = 'Shortname of the role (e.g., trialstudent). The installer creates a default role.';
$string['set_deleteafterdays'] = 'Delete trial accounts (days after expiration, 0 = never)';
$string['set_deleteafterdays_desc'] = 'Number of days after the trial expires before deleting the trial account record (0 = never).';
$string['set_trialusernameprefix'] = 'Username prefix for trial accounts';
$string['set_trialusernameprefix_desc'] = 'E.g., trial_ → results in trial_jdoe';
$string['set_trialemailprefix'] = 'Email prefix for trial accounts';
$string['set_trialemailprefix_desc'] = 'E.g., trial+ → results in trial+jdoe@… (or trial+md5@forced domain)';
$string['set_trialemaildomain'] = 'Forced email domain (optional)';
$string['set_trialemaildomain_desc'] = 'Leave empty to keep the original domain; otherwise replace with this domain (e.g., noreply.campusfr.invalid).';

$string['rolename_trialstudent'] = 'Student (trial)';
$string['roledesc_trialstudent'] = 'Read-only role for trial access.';
$string['cron_trial_maint'] = 'Campus — Trial reminders & cleanup';

$string['trial_popup_title'] = 'Free access to all courses for 7 days';
$string['trial_popup_lead']  = 'Instant access to all courses.<br>No credit card required.<span class="hero-emoji">🛡</span>';
$string['trial_popup_tos']   = 'I accept the Terms of Use and the Privacy Policy.';
$string['trial_popup_accept']= 'Please confirm your acceptance of the terms.';
$string['trial_firstname']   = 'First name';
$string['trial_lastname']    = 'Last name';
$string['trial_email']       = 'E-mail';
$string['trial_btn_continue']= 'Start learning';
$string['trial_btn_subscribe']= 'Subscribe';
$string['trial_expired_msg'] = 'Your trial period has ended. Subscribe to continue.';
$string['trial_tos_html'] =
    'By creating an account, you agree to the <a href="{$a->policyurl}" target="_blank" rel="noopener">Privacy Policy</a> '
    .'and the <a href="{$a->termsurl}" target="_blank" rel="noopener">Terms of Use</a>.';
$string['trial_footer_note'] =
    'After 7 days, access ends automatically — no charges.';
$string['trial_firstname_ph'] = 'Your first name';
$string['trial_lastname_ph']  = 'Your last name';
$string['trial_email_ph']     = 'Your e-mail';

$string['mail_trial_started_subject'] = 'Your trial period has started';
$string['mail_trial_started_body']    = 'Hello {$a->firstname}, your 7-day trial has just begun!';
$string['mail_trial_rem3_subject']    = 'Reminder: your trial is still active — {$a}';
$string['mail_trial_rem3_body'] = '<p>Bonjour, {$a->firstname} !</p>
    <p>We hope your first days on Campus<small><sup>FR</sup></small> have been inspiring and enjoyable.</p>

<p>If you’d like to continue learning, your {$a->dpct}% discount on all subscriptions will remain available for another 24 hours.</p>

<p>After that, the regular price will apply.</p>

<p>You can subscribe at the reduced price here:</p>';

$string['mail_trial_rem3_body2'] =
    '<p>Once your subscription is activated, you\'ll immediately get full access to all lessons and future updates — everything you need to learn French with confidence, structure, and enjoyment.</p>

<p>If you have any questions, just write to us at <a href="mailto:{$a}">{$a}</a>. We’re always here for you.</p>

<p>À très bientôt,<br>
Nata and the Campus<small><sup>FR</sup></small> team</p>';
$string['mail_trial_rem3_button'] = 'Subscribe with {$a}% discount';

$string['mail_trial_expired_subject'] = 'Your trial has ended — {$a}';
$string['mail_trial_expired_body']    = 'Hello {$a->firstname}, your 7-day trial period has ended.';
$string['mail_trial_cta_subscribe']   = 'Subscribe';
$string['mail_trial_cta_continue']    = 'Continue trial access';
$string['mail_trial_rem3_subject_generic'] = '⏳ Last 24 hours to get −{$a}% 🇫🇷';



$string['mail_trial_expired_subject_generic'] = 'Your trial has ended';


$string['cataloguetitle'] = 'Catalogue';
$string['catalogueheading'] = 'Cours de niveau';
$string['cataloguesub'] = 'Parcourez nos formations';
$string['moreinfo'] = 'En savoir plus';
$string['trial_access_label'] = 'Accéder au cours d’essai';
$string['cta_connected'] = 'Accéder au cours';
$string['nocoursesconfigured'] = 'Aucun cours configuré à afficher.';
$string['set_subscribercourses'] = 'Cours abonnés (IDs, séparés par des virgules)';
$string['set_subscribercourses_desc'] = 'Cours visibles uniquement pour les abonnés. Les visiteurs et comptes d’essai ne les voient pas.';

$string['cataloguetitle'] = 'Catalogue';
$string['catalogueheading'] = 'Level-based courses';
$string['cataloguesub'] = 'Browse our courses';
$string['moreinfo'] = 'Learn more';
$string['trial_access_label'] = 'Access the trial course';
$string['cta_connected'] = 'Access the course';
$string['nocoursesconfigured'] = 'No courses configured to display.';
$string['set_subscribercourses'] = 'Subscriber courses (IDs, comma-separated)';
$string['set_subscribercourses_desc'] = 'Courses visible to subscribers only. Visitors and trial accounts will not see them.';
$string['back_to_all_courses'] = '← Back to all courses';

$string['tab_catalogue'] = 'Catalog';
$string['tab_mycourses'] = 'My courses';
$string['mycourses_title'] = 'My courses';
$string['mycourses_sub'] = 'Overview of your courses';
$string['mycourses_empty'] = 'You are not enrolled in any course yet.';
$string['mycourses_browse'] = 'Browse catalog';
$string['cta_connected'] = 'Continue';
$string['cta_connected_start'] = 'Start';
$string['cta_connected_resume'] = 'Resume';
$string['completed'] = 'completed';
$string['completed_badge'] = 'Completed';
$string['notenrolled'] = 'Not enrolled';
$string['course_not_started'] = 'You haven’t started this course yet';
$string['resume_here'] = 'Resume where you left off';
$string['congrats_completed'] = 'Congratulations! You have completed this course.';
$string['browse_catalog'] = 'Browse the catalog';
$string['access_trial_courses'] = 'Access trial courses';
$string['subscribe_now'] = 'Subscribe';
$string['mycourses_empty'] = 'Log in to see your courses. You can also try our trial courses or subscribe.';
$string['no_courses_banner_title'] = 'No courses available yet.';
$string['no_courses_banner_text']  = 'Browse the catalog, try our trial courses, or subscribe to get started.';
$string['login_now']               = 'Log in';
$string['mycourses_empty']         = 'Log in to see your courses. You can also try our trial courses or subscribe.';
$string['browse_catalog']          = 'Browse the catalog';
$string['access_trial_courses']    = 'Access trial courses';
$string['subscribe_now']           = 'Subscribe';
$string['hint_go_to_header_cta'] = 'Subscribe or sign in here';

$string['admin_native_page'] = 'Moodle native page';
$string['admin_show_hidden'] = 'Also show hidden courses';
$string['admin_hide_hidden'] = 'Hide hidden courses';

$string['trial_password']      = 'Password';
$string['trial_password_ph']   = 'Create a password';
$string['trial_password_help'] = 'Minimum 8 characters. This password will be used to log in.';
$string['trial_password_min']  = 'The password must contain at least 8 characters.';
$string['trial_password_policy_error'] = 'The password does not meet the security policy requirements. {$a}';

$string['emailalreadysubscribed'] =
    "This email address is already associated with an account. Please log in to start your trial.";

$string['trial_already_subscribed_html'] =
    'You already have an active subscription. Please <a href="{$a->login}" class="link-primary" target="_top" rel="noopener">log in</a>.';

$string['trial_expired_html'] =
    'Your trial period has ended. <a href="{$a->subscribe}" class="link-primary" rel="noopener" data-subs-modal="1">Subscribe</a> to continue.';

$string['trial_discount_banner_title'] = '−{$a}% discount for trial users.';
$string['trial_discount_banner_body']  = 'Time left: ';
$string['trial_discount_banner_cta']   = 'Subscribe';
$string['trial_days_word']             = 'd.';

$string['trial_banner_reminder_title'] = 'Trial period reminder';
$string['trial_banner_reminder_body']  = 'Your trial access ends on {$a}. To continue, please subscribe.';

$string['trial_banner_expired_html'] =
    'Your trial access expired on <strong>{$a->date}</strong>. '
    .'<a href="{$a->url}" class="link-primary" data-subs-modal="1">Subscribe</a> to continue.';

$string['sub_expiry_banner'] =
    'Your subscription “{$a->plan}” expires on <strong>{$a->date}</strong> (in {$a->days} days).';

$string['login_suspended_html'] =
    'Your account is <strong>suspended</strong> (trial period ended). '
    .'Please <a class="link-primary" href="{$a->link}">subscribe</a> to restore access.';

$string['login_suspended_html'] = 'Your account is <strong>suspended</strong> (trial ended). '
    .'Please <a class="link-primary" href="{$a->link}">subscribe</a> to restore access.';

$string['set_trialdays'] = 'Trial duration (days)';
$string['set_trialdays_desc'] = 'Number of days of free access (J). Default: 7.';

$string['set_trial_suspend_after_days'] = 'Account suspension (J + N days)';
$string['set_trial_suspend_after_days_desc'] = 'Number of days after the end of the trial (J) before suspending the account (the user can no longer sign in). Default: 30.';

$string['set_trial_delete_after_days'] = 'Account deletion (J + N days)';
$string['set_trial_delete_after_days_desc'] = 'Number of days after the end of the trial (J) before permanently deleting the account (if no other active subscription). Default: 90.';

$string['mail_trial_discount_line'] = 'A <strong>{$a->pct}%</strong> discount is active until <strong>{$a->date}</strong>.';

$string['trial_presuspend_subject'] = 'Your trial account will be suspended soon';
$string['trial_presuspend_body']    = 'Hi {$a->firstname},<br>Your trial account will be suspended on <strong>{$a->date}</strong>. '
    .'To keep access, please subscribe now.';

$string['trial_suspended_subject']  = 'Your trial account has been suspended';
$string['trial_suspended_body']     = 'Hi {$a->firstname},<br>Your trial account was suspended on <strong>{$a->sdate}</strong>. '
    .'Without action, it will be deleted on <strong>{$a->ddate}</strong>. '
    .'You can reactivate access by subscribing.';

$string['mail_trial_expired_hint_suspend'] = 'Your account will remain active (login possible) until <strong>{$a}</strong>. '
    .'Subscribe to regain access to the courses.';

$string['myaccompt'] = 'My accompt';

// Password confirmation + “eye”
$string['trial_password_confirm']      = 'Confirm password';
$string['trial_password_confirm_ph']   = 'Repeat password';
$string['trial_password_confirm_help'] = 'Enter the same password again to confirm.';
$string['trial_password_toggle']       = 'Show or hide the password';
$string['trial_password_mismatch']     = 'Passwords do not match.';

// Trial start – extra info
$string['mail_trial_started_credentials'] = 'Here are your login details:<br>
Username: {$a->username}<br>
Password: {$a->password}<br>
You can log in here: <a href="{$a->login_url}">Campus<small><sup>FR</sup></small> login</a>.';

$string['mail_trial_started_mycourses'] =
    'You can access all your trial courses here: <a href="{$a->mycourses_url}">My courses</a>.';

// Login table
$string['trial_username_label'] = 'Login e-mail';
$string['trial_password_label'] = 'Password';
$string['mail_trial_security_hint'] = 'Please keep these details confidential. For security, you can change your password at any time in your CampusFR account settings.';
$string['mail_trial_started_mycourses'] =
    'You can open your trial courses anytime from your Campus<small><sup>FR</sup></small> dashboard.';

$string['course_progress_ratio'] = '{$a->done} / {$a->total} items completed';

// Phone
$string['trial_phone']      = 'Phone';
$string['trial_phone_ph']   = 'Your phone number';
$string['trial_phone_help'] = 'Your phone number helps us answer your questions quickly and guide you as you start learning.';
$string['trial_phone_label'] = 'Phone';

$string['mail_trial_reset_hint'] =
    '<p>You created your password during registration. If you ever forget it, you can reset it here:</p>' .
    '👉 <a href="{$a->url}">Reset password</a></p>';

// E-mail subject
$string['mail_trial_started_subject'] =
    'Your Campus<small><sup>FR</sup></small> trial access is activated 🎉';

// Main body
$string['mail_trial_started_body'] =
    '<p>Hello {$a->firstname},</p>' .
    '<p>Your 7-day trial access to Campus<small><sup>FR</sup></small> is now active.</p>' .
    '<p>You can explore the platform, try the learning format, complete a few exercises and earn your first croissants 🥐 — and see whether this learning style feels right for you.</p>' .
    '<p>Your login details:</p>';

// “My courses” section
$string['mail_trial_started_mycourses'] =
    '<p>You can access your dashboard using the link below:</p>' .
    '👉 <a href="{$a->url}">Enter the campus</a></p>';

// Discount line
$string['mail_trial_discount_line'] =
    '<p>Subscribe within the first {$a->duration} days after activating your trial and get {$a->pct}% off your full Campus<small><sup>FR</sup></small> subscription.</p>
<p>This discount is available for these three days only — afterwards the standard price applies.</p>';

$string['mail_trial_discount_btn'] =
    'Buy the full CampusFR subscription with {$a->pct}% off';

// Buttons
$string['mail_trial_cta_continue']  = 'Open trial courses';
$string['mail_trial_cta_subscribe'] = 'Subscribe to full access';

// Labels
$string['trial_username_label'] = 'Login e-mail';
$string['trial_phone_label']    = 'Phone';

// Security hint (fallback)
$string['mail_trial_security_hint'] =
    'For better security, use a unique password for Campus<small><sup>FR</sup></small> and update it occasionally if needed.';

// Other fields
$string['trial_phone_country_placeholder'] = 'Code';
$string['trial_password_toggle_show'] = 'Show password';
$string['trial_password_toggle_hide'] = 'Hide password';

$string['trial_welcome_banner_html'] =
    'Welcome to Campus<small><sup>FR</sup></small>! Your 7-day trial is activated. ' .
    'Start with the level that suits you (A0, A1, A2 or B1). ' .
    'You can always return to your courses via the “My courses” page.';

$string['mail_trial_started_support'] =
    '<p>All your progress (completed tasks, croissant-points earned) is saved. When you subscribe, you simply continue from where you left off.</p>

<p>This message was sent automatically.</br>
If you have any questions, write to us at <a href="mailto:{$a->url}">{$a->url}</a> — we’ll be happy to help.</p>

<p>We wish you joyful learning, steady progress and many small victories ❤️</p>

<p>Nata and the Campus<small><sup>FR</sup></small> team</p>';

$string['trial_discount_reminder_days'] =
    'How many days before sending the discount email (in days)';

$string['trial_discount_reminder_days_desc'] =
    'Number of days after the trial start before the discount email is sent. Default: 2 days.';

$string['phone_country_group_popular'] = 'Popular countries';
$string['phone_country_group_all']     = 'All countries';

$string['trialreport_title'] = 'Trial subscriptions report';
$string['trialreport_col_firstname'] = 'First name';
$string['trialreport_col_lastname'] = 'Last name';
$string['trialreport_col_email'] = 'E-mail';
$string['trialreport_col_phone'] = 'Phone (with country code)';
$string['trialreport_col_country'] = 'Country';
$string['trialreport_col_date_48h'] = 'Date: trial start + 48h';
$string['trialreport_col_date_72h'] = 'Date: trial start + 72h';
$string['trialreport_col_date_7d'] = 'Date: trial start + 7 days';
$string['trialreport_col_status'] = 'Status';

$string['trialreport_export_xls'] = 'Save as XLS';
$string['trialreport_export_csv'] = 'Save as CSV';
