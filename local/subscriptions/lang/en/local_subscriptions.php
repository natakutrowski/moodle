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

// -- Add subscription
$string['add_subscription'] = 'Add subscription';
$string['unknown_user'] = 'Unknown user';
$string['sub_created'] = '{$a->user} has been subscribed to the {$a->plan} plan with {$a->scope} access.';
$string['sub_exists'] = '{$a->user} subscription already exists ({$a->plan}, {$a->scope}).';
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

// -- Manage plans
$string['managesubscriptions'] = 'Manage subscriptions';
$string['scopes'] = '🎓 Access scope';
$string['plans'] = '📝 Plans';
$string['prices'] = '💰 Prices';

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


// JS delete...
$string['thisscope'] = 'this scope';
$string['thisplan'] = 'this plan';
$string['confirmdeletetitle'] = 'Confirm deletion';
$string['confirmdeletemessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations will also be deleted.';
$string['confirmdeleteplanmessage'] = '⚠️ This action is irreversible.<br><br>Do you really want to delete <strong>{$a}</strong>?<br><br>All associated translations and prices will also be deleted.';
$string['delete'] = 'Delete';


