<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

/**
 * Récupère un scope à éditer avec les cours associés.
 */
function local_subscriptions_get_scope_for_edit(int $id): array {
    global $DB;
    require_sesskey();

    $scope = $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST);
    $course_ids = explode(',', $scope->course_ids);

    return [$scope, $course_ids];
}

/**
 * Supprime un scope et ses traductions après vérification.
 */
function local_subscriptions_delete_scope(int $id): void {
    global $DB;
    require_sesskey();

    if (!$DB->record_exists('subscription_access_scope', ['id' => $id])) {
        throw new moodle_exception(
			'scopenotfound',
			'local_subscriptions',
			new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes'])
		);
    }

    $plans = $DB->count_records('subscription_plan', ['access_scope_id' => $id]);
    if ($plans > 0) {
        throw new moodle_exception(
			'scopedeleteinuse',
			'local_subscriptions',
			new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes'])
		);
    }

    $transaction = $DB->start_delegated_transaction();
    try {
        $DB->delete_records('subscription_access_scope_translation', ['scope_id' => $id]);
        $DB->delete_records('subscription_access_scope', ['id' => $id]);
        $transaction->allow_commit();
        redirect(new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes']),
                get_string('scopedeleted', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        throw new moodle_exception(get_string('scopedeleteerror', 'local_subscriptions'), '', '', null, $e->getMessage());
    }
}

/**
 * Récupère les scopes avec leurs traductions dans la langue courante.
 */
function local_subscriptions_get_all_scopes_with_translations(string $lang, string $orderdir): array {
    global $DB;

    return $DB->get_records_sql("
        SELECT s.*, t.name AS translated_name, t.description AS translated_description
        FROM {subscription_access_scope} s
        LEFT JOIN {subscription_access_scope_translation} t
            ON s.id = t.scope_id AND t.lang = ?
        ORDER BY s.name " . $orderdir, [$lang]);
}

function local_subscriptions_get_scope_translations(int $scopeid = 0): array {
    global $DB;

    return $scopeid
        ? $DB->get_records('subscription_access_scope_translation', ['scope_id' => $scopeid])
        : $DB->get_records('subscription_access_scope_translation');
}

function local_subscriptions_delete_scope_translation(int $id): void {
    global $DB;
    $DB->delete_records('subscription_access_scope_translation', ['id' => $id]);
}

function local_subscriptions_save_scope_translation(): void {
    global $DB;

    require_sesskey();

    $id = optional_param('id', 0, PARAM_INT);
    $record = (object)[
        'scope_id' => required_param('scope_id', PARAM_INT),
        'lang' => required_param('lang', PARAM_LANG),
        'name' => required_param('name', PARAM_TEXT),
        'description' => required_param('description', PARAM_TEXT),
        'last_update' => time()
    ];

    if ($id) {
        $record->id = $id;
        $DB->update_record('subscription_access_scope_translation', $record);
    } else {
        $exists = $DB->record_exists('subscription_access_scope_translation', [
            'scope_id' => $record->scope_id,
            'lang' => $record->lang
        ]);

        if ($exists) {
            redirect(new moodle_url(subscription_config::scopes_translations_page(), [
                'add' => $record->scope_id, 'sesskey' => sesskey()
            ]), get_string('errorduplicatetranslation', 'local_subscriptions'), null, \core\output\notification::NOTIFY_ERROR);
        }

        $record->creation_date = time();
        $DB->insert_record('subscription_access_scope_translation', $record);
    }

    redirect(new moodle_url(subscription_config::scopes_translations_page()));
}
