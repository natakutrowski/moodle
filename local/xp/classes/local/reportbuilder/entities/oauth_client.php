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

namespace local_xp\local\reportbuilder\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use html_writer;
use lang_string;
use local_xp\local\model\oauth_client as oauth_client_model;

/**
 * OAuth client entity.
 *
 * @package    local_xp
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth_client extends base {
    use pre_404_compat_trait;
    use pre_502_compat_trait;

    /**
     * Get the database tables.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [oauth_client_model::TABLE];
    }

    /**
     * Get the entity title.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('oauthclients', 'block_xp');
    }

    /**
     * Get the available columns.
     *
     * @return column[]
     */
    protected function get_available_columns(): array {
        $tablealias = $this->get_table_alias(oauth_client_model::TABLE);

        $columns[] = (new column('displayname', new lang_string('name', 'core'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.displayname")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_callback(static function (string $value): string {
                return s($value);
            });

        $columns[] = (new column('displaynamewithlink', new lang_string('name', 'core'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.displayname")
            ->add_fields("{$tablealias}.id")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_callback(static function (string $value, $row): string {
                return html_writer::link('#', s($value), [
                    'data-xp-action' => 'open-oauthclient',
                    'data-id' => $row->id,
                ]);
            });

        $columns[] = (new column('clientid', new lang_string('clientid', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.clientid")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function (string $value): string {
                return s($value);
            });

        $columns[] = (new column('clientidshort', new lang_string('clientid', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.clientid")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function (string $value): string {
                return \html_writer::tag('abbr', s(shorten_text($value, 24, true, '…')), ['title' => $value]);
            });

        $columns[] = (new column('type', new lang_string('type', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.type")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(false)
            ->add_callback(static function (?int $type): string {
                $types = [
                    oauth_client_model::TYPE_MANUAL => get_string('manual', 'block_xp'),
                    oauth_client_model::TYPE_CIMD => get_string('cimd', 'block_xp'),
                ];
                return $types[$type] ?? get_string('unknowntypea', 'block_xp', $type);
            });

        $columns[] = (new column('isenabled', new lang_string('enabled', 'core_admin'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.isenabled")
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(false)
            ->add_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column('isvalid', new lang_string('valid', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.isvalid")
            ->set_type(column::TYPE_BOOLEAN)
            ->set_is_sortable(false)
            ->add_callback([format::class, 'boolean_as_text']);

        $columns[] = (new column('status', new lang_string('status', 'core'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_fields("{$tablealias}.isvalid, {$tablealias}.isenabled")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function ($value, $record): string {
                if (!$record->isvalid) {
                    return get_string('invalid', 'block_xp');
                } else if (!$record->isenabled) {
                    return get_string('disabled', 'core_admin');
                }
                return get_string('enabled', 'core_admin');
            });

        $columns[] = (new column('statustag', new lang_string('status', 'core'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_fields("{$tablealias}.isvalid, {$tablealias}.isenabled")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function ($value, $record): string {
                $label = get_string('enabled', 'core_admin');
                $variant = 'success';
                if (!$record->isvalid) {
                    $label = get_string('invalid', 'block_xp');
                    $variant = 'danger';
                } else if (!$record->isenabled) {
                    $label = get_string('disabled', 'core_admin');
                    $variant = 'secondary';
                }
                return html_writer::tag('span', $label, ['class' => "badge bg-{$variant} badge-{$variant}"]);
            });

        $columns[] = (new column('invalidhint', new lang_string('invalidhint', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.invalidhint")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value): string {
                return s($value ?? '');
            });

        $columns[] = (new column('redirecturis', new lang_string('redirecturls', 'block_xp'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.redirecturis")
            ->set_type(column::TYPE_LONGTEXT)
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value): string {
                $uris = array_map('trim', explode(',', (string) $value));
                $uris = array_values(array_filter($uris, static function ($uri): bool {
                    return preg_match('@^https?://@i', $uri) === 1;
                }));
                return nl2br(s(implode("\n", $uris)));
            });

        $columns[] = (new column(
            'lastmetadatacheck',
            new lang_string('lastmetadatacheck', 'block_xp'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.lastmetadatacheck")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        $columns[] = (new column('timecreated', new lang_string('timecreated', 'core_reportbuilder'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.timecreated")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        $columns[] = (new column('timemodified', new lang_string('timemodified', 'core_reportbuilder'), $this->get_entity_name()))
            ->add_joins($this->get_joins())
            ->add_field("{$tablealias}.timemodified")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }
}
