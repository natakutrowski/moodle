<?php
// This file is part of Level Up XP+.
//
// Level Up XP+ is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up XP+ is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up XP+.  If not, see <https://www.gnu.org/licenses/>.
//
// See <https://levelup.plus>.

namespace local_xp\local\report;

use block_xp\di;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\action;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use html_writer;
use lang_string;
use local_xp\local\model\oauth_client as oauth_client_model;
use local_xp\local\model\oauth_token as oauth_token_model;
use local_xp\local\reportbuilder\entities\oauth_client;
use moodle_url;
use pix_icon;
use stdClass;

/**
 * OAuth clients report.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_clients extends system_report {
    /**
     * Initialise.
     */
    protected function initialise(): void {
        $entity = new oauth_client();
        $client = $entity->get_table_alias(oauth_client_model::TABLE);

        $this->set_main_table(oauth_client_model::TABLE, $client);
        $this->add_entity($entity);
        $this->add_base_fields("{$client}.id, {$client}.type, {$client}.isenabled, {$client}.isvalid");

        $grant = database::generate_alias('oauthgrant');
        [$revokedparam, $nowparam, $accessparam, $refreshparam] = database::generate_param_names(4, 'oauthgrant');
        $this->add_join("LEFT JOIN (
                            SELECT token.clientid,
                                   COUNT(DISTINCT token.userid) AS usercount,
                                   COUNT(DISTINCT token.familyhash) AS grantcount
                              FROM {" . oauth_token_model::TABLE . "} token
                             WHERE token.revoked = :{$revokedparam}
                               AND token.expires > :{$nowparam}
                               AND token.type IN (:{$accessparam}, :{$refreshparam})
                          GROUP BY token.clientid
                        ) {$grant} ON {$grant}.clientid = {$client}.clientid", [
            $revokedparam => 0,
            $nowparam => di::get('clock')->time(),
            $accessparam => oauth_token_model::TYPE_ACCESS,
            $refreshparam => oauth_token_model::TYPE_REFRESH,
        ]);

        $this->add_columns($entity, $grant);
        $this->add_actions();

        $this->set_initial_sort_column('oauth_client:displaynamewithlink', SORT_ASC);
    }

    /**
     * Can view.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', $this->get_context());
    }

    /**
     * Report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('oauthclients', 'block_xp');
    }

    /**
     * Adds the columns.
     */
    protected function add_columns(oauth_client $entity, string $grant): void {
        $this->add_columns_from_entities([
            'oauth_client:displaynamewithlink',
            'oauth_client:clientidshort',
            'oauth_client:type',
        ]);

        $this->add_column((new column(
            'activegrants',
            new lang_string('currentgrants', 'block_xp'),
            $entity->get_entity_name()
        ))
            ->add_field("COALESCE({$grant}.usercount, 0)", 'activegrantusers')
            ->add_field("COALESCE({$grant}.grantcount, 0)", 'activegrants')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function ($value, stdClass $row): string {
                $users = (int) $value;
                $grants = (int) $row->activegrants;
                return html_writer::tag('abbr', "{$users} / {$grants}", [
                    'title' => get_string('currentgrantsinfo', 'block_xp', (object) [
                        'users' => $users,
                        'grants' => $grants,
                    ]),
                ]);
            }));

        $this->add_columns_from_entities([
            'oauth_client:statustag',
            'oauth_client:timecreated',
        ]);
    }

    /**
     * Adds actions.
     */
    protected function add_actions(): void {
        $baseurl = di::get('url_resolver')->reverse('admin/oauthclients')->get_compatible_url();

        $this->add_action(new action(
            new moodle_url('#'),
            new pix_icon('t/preview', ''),
            [
                'data-xp-action' => 'open-oauthclient',
                'data-id' => ':id',
            ],
            false,
            new lang_string('view', 'core')
        ));

        $this->add_action((new action(
            new moodle_url('#'),
            new pix_icon('t/edit', ''),
            [
                'data-xp-action' => 'open-form',
                'data-form-class' => 'local_xp\\form\\admin\\oauth_client',
                'data-form-args__id' => ':id',
                'data-modal-title' => get_string('edit', 'core'),
            ],
            false,
            new lang_string('edit', 'core')
        ))
            ->add_callback(static function (stdClass $row): bool {
                return (int) $row->type !== oauth_client_model::TYPE_CIMD;
            }));

        $this->add_action((new action(
            new moodle_url($baseurl, ['action' => 'enable', 'id' => ':id', 'sesskey' => sesskey()]),
            new pix_icon('t/show', ''),
            [],
            false,
            new lang_string('enable', 'core')
        ))
            ->add_callback(static function (stdClass $row): bool {
                return (int) $row->isenabled === 0 && (int) $row->isvalid === 1;
            }));

        $this->add_action((new action(
            new moodle_url($baseurl, ['action' => 'disable', 'id' => ':id', 'sesskey' => sesskey()]),
            new pix_icon('t/hide', ''),
            [],
            false,
            new lang_string('disable', 'core')
        ))
            ->add_callback(static function (stdClass $row): bool {
                return (int) $row->isenabled === 1;
            }));

        $this->add_action(new action(
            new moodle_url($baseurl, ['action' => 'revoke', 'id' => ':id', 'sesskey' => sesskey()]),
            new pix_icon('t/block', ''),
            [
                'data-confirmation' => 'modal',
                'data-confirmation-type' => 'delete',
                'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                'data-confirmation-content-str' => json_encode(['revokegrantsconfirm', 'block_xp']),
                'data-confirmation-yes-button-str' => json_encode(['revokegrants', 'block_xp']),
                'class' => 'text-danger',
            ],
            false,
            new lang_string('revokegrants', 'block_xp')
        ));

        $this->add_action(new action(
            new moodle_url($baseurl, ['action' => 'delete', 'id' => ':id', 'sesskey' => sesskey()]),
            new pix_icon('t/delete', ''),
            [
                'data-confirmation' => 'modal',
                'data-confirmation-type' => 'delete',
                'data-confirmation-title-str' => json_encode(['confirm', 'core']),
                'data-confirmation-content-str' => json_encode(['deleteoauthclientconfirm', 'block_xp']),
                'data-confirmation-yes-button-str' => json_encode(['delete', 'core']),
                'class' => 'text-danger',
            ],
            false,
            new lang_string('delete', 'core')
        ));
    }
}
