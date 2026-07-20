<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

use local_subscriptions\subscription_config;

class local_subscriptions_scopes_renderer extends plugin_renderer_base {

    /**
     * Affiche le bouton "Ajouter un scope".
     */
    public static function render_add_button(moodle_url $url): string {
        return html_writer::link($url, get_string('addscope', 'local_subscriptions'), [
            'class' => 'btn btn-primary',
            'style' => 'margin-bottom: 1em; display: inline-block;'
        ]);
    }

    /**
     * Affiche la table des scopes avec actions.
     */
    public static function render_scopes_table(array $scopes, string $currentlang, string $order, string $dir): string {
        global $DB, $OUTPUT;

        $context = \context_system::instance();
        
        $upstyle = ($order === 'name' && $dir === 'asc') ? 'font-weight:bold;' : '';
        $downstyle = ($order === 'name' && $dir === 'desc') ? 'font-weight:bold;' : '';
        $sorticons = html_writer::link(
            new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes', 'order' => 'name', 'dir' => 'asc']),
            '🔼',
            ['style' => "text-decoration:none; $upstyle", 'title' => get_string('sortaz', 'local_subscriptions')]
        ) . ' ' .
        html_writer::link(
            new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes', 'order' => 'name', 'dir' => 'desc']),
            '🔽',
            ['style' => "text-decoration:none; $downstyle", 'title' => get_string('sortza', 'local_subscriptions')]
        );

        $table = new html_table();
        $table->head = [
            get_string('name', 'local_subscriptions') . ' ' . $sorticons,
            get_string('description', 'local_subscriptions'),
            get_string('courses', 'local_subscriptions'),
            get_string('dates', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions')
        ];

        $table->attributes['class'] = 'generaltable table table-bordered table-striped';

        foreach ($scopes as $s) {
            $translations = local_subscriptions_get_scope_translations($s->id); // par ex. [['lang' => 'en'], ['lang' => 'fr']]

            // 🈂️ Traductions
            $availablelangs = array_map(fn($t) => $t->lang, $translations);
            if (!empty($availablelangs)) {
                $translationtooltip = get_string('availabletranslations', 'local_subscriptions') . ': ';
                $translationtooltip .= implode(', ', array_map(function($lang) {
                    return local_subscriptions_get_lang_flag($lang) . ' ' . strtoupper($lang);
                }, $availablelangs));
            } else {
                $translationtooltip = get_string('notranslation', 'local_subscriptions');
            }

            $coursenames = [];
            foreach (explode(',', $s->course_ids) as $cid) {
                if ($course = $DB->get_record('course', ['id' => $cid], 'fullname', IGNORE_MISSING)) {
                    $url = new moodle_url('/course/view.php', ['id' => $cid]);
                    $coursenames[] = html_writer::link($url, $course->fullname, ['target' => '_blank']);
                }
            }

            $editurl = new moodle_url(subscription_config::manage_page(), [
                'tab' => 'scopes',
                'edit' => $s->id,
            ]);
            $deleteurl = new moodle_url(subscription_config::manage_page(), [
                'tab' => 'scopes',
                'delete' => $s->id,
                'sesskey' => sesskey()
            ]);
            $translationurl = new moodle_url(subscription_config::scopes_translations_page(), [
                'scopeid' => $s->id,
            ]);

            $icons = [];

            // ✏️ Éditer
            $icons[] = html_writer::link($editurl,
                $OUTPUT->pix_icon('i/edit', get_string('editscope', 'local_subscriptions'), ''),
                [
                    'title' => get_string('editscope', 'local_subscriptions'),
                    'class' => 'me-2 action-icon'
                ]
            );

            // 🗑️ Supprimer
            $icons[] = html_writer::link('#',
                $OUTPUT->pix_icon('i/delete', get_string('deletescope', 'local_subscriptions'), ''),
                [
                    'class' => 'deletescope me-2 action-icon',
                    'data-deleteurl' => $deleteurl->out(false),
                    'data-name' => $s->name,
                    'data-id' => $s->id,
                    'title' => get_string('deletescope', 'local_subscriptions')
                ]
            );

            // 🌐 Traductions
            $icons[] = html_writer::link($translationurl,
                $OUTPUT->pix_icon('i/siteevent', get_string('translatetooltip', 'local_subscriptions'), '', ['title' => $translationtooltip]),
                ['class' => 'me-2 action-icon']
            );

            // Conteneur flex aligné
            $actions = html_writer::div(implode('', $icons), '', ['class' => 'd-flex align-items-center']);

            $actions_container = html_writer::div($actions, 'subscription_actions');

            $created = userdate($s->creation_date);
            $updated = userdate($s->last_update);
            $is_updated = $s->last_update > $s->creation_date;

            $datescell = '<small>' . get_string('createdon', 'local_subscriptions') . ' <strong>' . $created . '</strong><br>';
            $datescell .= get_string('modifiedon', 'local_subscriptions') . ' <strong' . ($is_updated ? ' style="color:#007bff;"' : '') . '>' . $updated . '</strong></small>';

            if ($s->translated_name){
                $namecell = $s->translated_name . "<br><small><i>(" . $s->name . ")</i></small>";
            } else {
                $namecell = "-<br><small><i>(" . $s->name . ")</i></small>";
            }

            // … avant $table->data[] …

            // Récupérer la traduction courante pour obtenir l'itemid (id de la traduction)
            $curtrans = $DB->get_record(
                'subscription_access_scope_translation',
                ['accessscopeid' => $s->id, 'lang' => $currentlang],
                'id, description, descriptionformat',
                IGNORE_MISSING
            );

            // Texte brut + format (avec fallback si tu as déjà joint ces champs côté $p)
            $raw    = $curtrans->description ?? ($s->translated_description ?? '');
            $format = $curtrans->descriptionformat ?? ($s->translated_descriptionformat ?? FORMAT_HTML);

            $desccell = '-';
            if ($raw !== '' && $raw !== null) {
                // Si le texte contient encore @@PLUGINFILE@@ ou un draftfile, réécrire les URLs pluginfile.
                if (strpos($raw, '@@PLUGINFILE@@') !== false || strpos($raw, '/draftfile.php') !== false) {
                    $itemid = $curtrans->id ?? 0; // idéalement l'id de la TRADUCTION courante
                    $raw = file_rewrite_pluginfile_urls(
                        $raw,
                        'pluginfile.php',
                        $context->id,
                        'local_subscriptions',
                        'scope_desc',
                        $itemid
                    );
                }

                // Rendu HTML sécurisé (avec filtres si besoin)
                $desccell = format_text($raw, $format, [
                    'context'     => $context,
                    'overflowdiv' => true,
                    'filter'      => true,
                ]);

                // Option: classe pour rendre les images responsives
                $desccell = html_writer::div($desccell, 'scope-desc');
            }

            $table->data[] = [
                $namecell,
                $desccell,
                implode('<br>', $coursenames),
                $datescell,
                $actions_container
            ];
        }

        return html_writer::table($table);
    }

    public static function local_subscriptions_render_scopes_translations_table(array $scopes, array $translations, int $scopeid = 0, int $adding = 0, int $editing = 0): string {
        global $DB;

        $currentlang = current_language();
        $table = new html_table();
        $table->head = [
            get_string('scopedefaultname', 'local_subscriptions'),
            get_string('translatedlanguages', 'local_subscriptions'),
            get_string('actions', 'local_subscriptions')
        ];
        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($scopes as $scope) {
            if (($scopeid && $scope->id != $scopeid) || ($adding && $scope->id != $adding)) {
                continue;
            }

            if ($editing) {
                $translation = $DB->get_record('subscription_access_scope_translation', ['id' => $editing], '*', MUST_EXIST);
                if ($scope->id != $translation->accessscopeid) {
                    continue;
                }
            }

            $rows = array_filter($translations, fn($t) => $t->accessscopeid == $scope->id);
            $rows = array_values($rows);

            $langues = [];
            foreach ($rows as $t) {
                $url = new moodle_url(subscription_config::scopes_translations_page(), [
                    'edit' => $t->id,
                    'sesskey' => sesskey()
                ]);

                $flag = local_subscriptions_get_lang_flag($t->lang);
                $name = local_subscriptions_get_lang_name($t->lang);

                $langues[] = html_writer::link($url, $flag, [
                    'title' => $name
                ]);
            }

            $current = array_filter($rows, fn($t) => $t->lang === $currentlang);
            $current = reset($current);

            $translatedname = $current ? format_string($current->name) : format_string($scope->name);
            $tooltip = $current && !empty($current->description) ? s(strip_tags($current->description)) : '';
            $icon = $current ? ' 🌐' : '';

            $namediv = html_writer::tag('span', $translatedname . $icon, [
                'title' => $tooltip,
                'style' => $tooltip ? 'cursor: help;' : ''
            ]);

            $addurl = new moodle_url(subscription_config::scopes_translations_page(), [
                'add' => $scope->id, 'sesskey' => sesskey()
            ]);

            $table->data[] = [
                $namediv,
                !empty($langues) ? implode(' ', $langues) : '-',
                html_writer::link($addurl, '➕ ' . get_string('addtranslation', 'local_subscriptions'))
            ];
        }

        return html_writer::table($table);
    }

}

