<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

/**
 * Récupère un plan à éditer avec le scope associé.
 */
function local_subscriptions_get_plan_for_edit(int $id): array {
    global $DB;
    require_sesskey();

    $plan = $DB->get_record('subscription_plan', ['id' => $id], '*', MUST_EXIST);
    $accessscopeid = $plan->accessscopeid;
    $duration_key = $plan->duration_key;
    return [$plan, $accessscopeid, $duration_key];
}

/**
 * Supprime un plan et ses traductions après vérification.
 */
function local_subscriptions_delete_plan(int $id): void {
    global $DB;
    require_sesskey();

    if (!$DB->record_exists('subscription_plan', ['id' => $id])) {
        throw new moodle_exception(
			'plannotfound',
			'local_subscriptions',
			new moodle_url(subscription_config::manage_page(), ['tab' => 'plans'])
		);
    }
    
    // ajouter ici check qu'un plan n'est pas utilisé par un utilisateur

    $transaction = $DB->start_delegated_transaction();
    try {
        $DB->delete_records('subscription_plan_translation', ['planid' => $id]);
        $DB->delete_records('subscription_plan_price', ['planid' => $id]);
        $DB->delete_records('subscription_plan', ['id' => $id]);
        $transaction->allow_commit();
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
                get_string('plandeleted', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        throw new moodle_exception(get_string('plandeleteerror', 'local_subscriptions'), '', '', null, $e->getMessage());
    }
}

/**
 * Récupère les plans avec leurs traductions dans la langue courante.
 */
function local_subscriptions_get_all_plans_with_translations(string $lang, string $orderdir): array {
    global $DB;

    return $DB->get_records_sql("
        SELECT s.*, t.name AS translated_name, t.description AS translated_description
        FROM {subscription_plan} s
        LEFT JOIN {subscription_plan_translation} t
            ON s.id = t.planid AND t.lang = ?
        ORDER BY s.name " . $orderdir, [$lang]);
}

function local_subscriptions_get_plan_translations(int $planid = 0): array {
    global $DB;

    return $planid
        ? $DB->get_records('subscription_plan_translation', ['planid' => $planid])
        : $DB->get_records('subscription_plan_translation');
}

function local_subscriptions_delete_plan_translation(int $id): void {
    global $DB;
    $DB->delete_records('subscription_plan_translation', ['id' => $id]);
}

function local_subscriptions_save_plan_translation(): void {
    global $DB;

    require_sesskey();

    $id = optional_param('id', 0, PARAM_INT);
    $record = (object)[
        'planid' => required_param('planid', PARAM_INT),
        'lang' => required_param('lang', PARAM_LANG),
        'name' => required_param('name', PARAM_TEXT),
        'description' => required_param('description', PARAM_TEXT),
        'last_update' => time()
    ];

    if ($id) {
        $record->id = $id;
        $DB->update_record('subscription_plan_translation', $record);
    } else {
        $exists = $DB->record_exists('subscription_plan_translation', [
            'planid' => $record->planid,
            'lang' => $record->lang
        ]);

        if ($exists) {
            redirect(new moodle_url(subscription_config::plans_translations_page(), [
                'add' => $record->plan_id, 'sesskey' => sesskey()
            ]), get_string('errorduplicatetranslation', 'local_subscriptions'), null, \core\output\notification::NOTIFY_ERROR);
        }

        $record->creation_date = time();
        $DB->insert_record('subscription_plan_translation', $record);
    }

    redirect(new moodle_url(subscription_config::plans_translations_page()));
}

/**
 * Récupère tous les prix pour un plan donné.
 */
function local_subscriptions_get_plan_prices(int $planid): array {
    global $DB;
    return $DB->get_records('subscription_plan_price', ['planid' => $planid], 'currency ASC');
}

/**
 * Récupère un prix par ID.
 */
function local_subscriptions_get_price(int $id) {
    global $DB;
    return $DB->get_record('subscription_plan_price', ['id' => $id], '*', MUST_EXIST);
}

/**
 * Supprime un prix.
 */
function local_subscriptions_delete_price(int $id): void {
    global $DB;
    $DB->delete_records('subscription_plan_price', ['id' => $id]);
}

function local_subscriptions_get_used_currencies_for_plan(int $planid): array {
    global $DB;
    return $DB->get_fieldset_select('subscription_plan_price', 'currency', 'planid = ?', [$planid]);
}

function local_subscriptions_plan_has_translation(int $planid): bool {
    global $DB;
    return $DB->record_exists('subscription_plan_translation', ['planid' => $planid]);
}

function local_subscriptions_plan_has_price(int $planid): bool {
    global $DB;
    return $DB->record_exists('subscription_plan_price', ['planid' => $planid]);
}
