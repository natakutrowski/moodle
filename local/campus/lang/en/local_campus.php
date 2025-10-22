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

$string['trial_popup_title'] = '7-day free trial';
$string['trial_popup_lead']  = 'Get instant access to the trial courses. No credit card.';
$string['trial_popup_tos']   = 'I accept the Terms of Use and the Privacy Policy.';
$string['trial_popup_accept']= 'Please accept the terms.';
$string['trial_firstname']   = 'First name';
$string['trial_lastname']    = 'Last name';
$string['trial_email']       = 'Email';
$string['trial_btn_continue']= 'Continue';
$string['trial_btn_subscribe']= 'Subscribe';
$string['trial_expired_msg'] = 'Your trial has ended. Subscribe to continue.';
$string['trial_tos_html'] = 'I accept the <a href="{$a}" target="_blank" rel="noopener">Privacy Policy</a>.';

$string['mail_trial_started_subject'] = 'Your trial has started';
$string['mail_trial_started_body']    = 'Hi {$a->firstname}, your 7-day trial has started!';
$string['mail_trial_rem3_subject']    = 'Reminder: trial in progress — {$a}';
$string['mail_trial_rem3_body']       = 'Hi {$a->firstname}, you still have a few days left in your trial.';
$string['mail_trial_expired_subject'] = 'Your trial has expired — {$a}';
$string['mail_trial_expired_body']    = 'Hi {$a->firstname}, your 7-day trial has ended.';
$string['mail_trial_cta_subscribe']   = 'Subscribe';
$string['mail_trial_cta_continue']    = 'Continue your trial';
$string['mail_trial_rem3_subject_generic']   = 'Reminder: your trial is in progress';
$string['mail_trial_expired_subject_generic']= 'Your trial has expired';
