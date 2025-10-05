<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

/**
 * Récupère un scope à éditer avec les cours associés.
 */
function local_subscriptions_get_scope_for_edit(int $id): array {
    global $DB;
    require_sesskey();

    $accessscope = $DB->get_record('subscription_access_scope', ['id' => $id], '*', MUST_EXIST);
    $course_ids = explode(',', $accessscope->course_ids);

    return [$accessscope, $course_ids];
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
			new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes'])
		);
    }

    $plans = $DB->count_records('subscription_plan', ['accessscopeid' => $id]);
    if ($plans > 0) {
        throw new moodle_exception(
			'scopedeleteinuse',
			'local_subscriptions',
			new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes'])
		);
    }

    $transaction = $DB->start_delegated_transaction();
    try {
        $DB->delete_records('subscription_access_scope_translation', ['accessscopeid' => $id]);
        $DB->delete_records('subscription_access_scope', ['id' => $id]);
        $transaction->allow_commit();
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
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
            ON s.id = t.accessscopeid AND t.lang = ?
        ORDER BY s.name " . $orderdir, [$lang]);
}

function local_subscriptions_get_scope_translations(int $accessscopeid = 0): array {
    global $DB;

    return $accessscopeid
        ? $DB->get_records('subscription_access_scope_translation', ['accessscopeid' => $accessscopeid])
        : $DB->get_records('subscription_access_scope_translation');
}

function local_subscriptions_delete_scope_translation(int $id): void {
    global $DB;
    $DB->delete_records('subscription_access_scope_translation', ['id' => $id]);
}

function local_subscriptions_save_scope_translation(): void {
    global $DB, $CFG;

    require_sesskey();

    $id = optional_param('id', 0, PARAM_INT);
    $accessscopeid= required_param('accessscopeid', PARAM_INT);
    $lang = required_param('lang', PARAM_LANG);
    $name = required_param('name', PARAM_TEXT);

    // description depuis l’éditeur riche (HTML)
    $editor = required_param_array('description_editor', PARAM_RAW); // attend ['text'=>..., 'format'=>...]

    $texthtml   = '';
    $textformat = FORMAT_HTML;
    $draftitemid= 0;
    if (is_array($editor)) {
        $texthtml   = (string)($editor['text'] ?? '');
        $textformat = (int)($editor['format'] ?? FORMAT_HTML);
        $draftitemid= (int)($editor['itemid'] ?? 0);
    }

    $context = \context_system::instance();
    $editoroptions = [
        'maxfiles'  => 50,
        'maxbytes'  => $CFG->maxbytes,
        'trusttext' => false,
        'subdirs'   => 0,
        'context'   => $context,
    ];

    $now = time();

    if ($id) {
        // UPDATE
        $data = (object)[
            'id'                 => $id,
            'description'        => $texthtml,
            'descriptionformat'  => $textformat,
            'description_editor' => [
                'text'   => $texthtml,
                'format' => $textformat,
                'itemid' => $draftitemid,
            ],
        ];
        $data = file_postupdate_standard_editor(
            $data, 'description', $editoroptions, $context,
            'local_subscriptions', 'scope_desc', $id
        );

        $rec = (object)[
            'id'                 => $id,
            'accessscopeid'      => $accessscopeid,
            'lang'               => $lang,
            'name'               => $name,
            'description'        => $data->description,
            'descriptionformat'  => $data->descriptionformat,
            'last_update'        => $now,
        ];
        $DB->update_record('subscription_access_scope_translation', $rec);

    } else {
        // INSERT
        $exists = $DB->record_exists('subscription_access_scope_translation', [
            'accessscopeid' => $accessscopeid,
            'lang'          => $lang
        ]);
        if ($exists) {
            redirect(
                new moodle_url(subscription_config::scopes_translations_page(), [
                    'add' => $accessscopeid,
                ]),
                get_string('errorduplicatetranslation', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }

        $newid = $DB->insert_record('subscription_access_scope_translation', (object)[
            'accessscopeid'     => $accessscopeid,
            'lang'              => $lang,
            'name'              => $name,
            'description'       => '',
            'descriptionformat' => FORMAT_HTML,
            'creation_date'     => $now,
            'last_update'       => $now,
        ]);

        $data = (object)[
            'id'                 => $newid,
            'description'        => $texthtml,
            'descriptionformat'  => $textformat,
            'description_editor' => [
                'text'   => $texthtml,
                'format' => $textformat,
                'itemid' => $draftitemid,
            ],
        ];
        $data = file_postupdate_standard_editor(
            $data, 'description', $editoroptions, $context,
            'local_subscriptions', 'scope_desc', $newid
        );

        $DB->update_record('subscription_access_scope_translation', (object)[
            'id'                 => $newid,
            'description'        => $data->description,
            'descriptionformat'  => $data->descriptionformat,
            'last_update'        => $now,
        ]);
    }

    redirect(new moodle_url(subscription_config::scopes_translations_page(), [
        'accessscopeid' => $accessscopeid
    ]));
}
