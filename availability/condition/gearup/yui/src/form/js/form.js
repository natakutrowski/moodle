// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * @package    availability_gearup
 * @copyright  2023 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const MODE_IS_ASSIGNED = 1;
const MODE_IS_STARTED = 2;
const MODE_IS_COMPLETED = 3;
const MODE_IS_ENDED = 4;
const MODE_IS_RECRUIT = 5;
const MODE_HAS_STARTED = 6;
const MODE_HAS_COMPLETED = 7;

const TEMPLATE = `
<div class="availability_gearup_frontend">
    <div>
        <label for="availability_gearup_missionid" class="sr-only">{{get_string "mission" "availability_gearup"}}</label>
        <select name="missionid" id="availability_gearup_missionid" class="custom-select form-select">
            <optgroup label="{{get_string "quests" "availability_gearup"}}" data-type="quest">
                {{#each quests}}
                <option value="{{ value }}">{{ label }}</option>
                {{/each}}
            </optgroup>
            <optgroup label="{{get_string "achievements" "availability_gearup"}}" data-type="quest">
                {{#each achievements}}
                <option value="{{ value }}">{{ label }}</option>
                {{/each}}
            </optgroup>
            <optgroup label="{{get_string "challenges" "availability_gearup"}}" data-type="quest">
                {{#each challenges}}
                <option value="{{ value }}">{{ label }}</option>
                {{/each}}
            </optgroup>
        </select>
        <label for="availability_gearup_mode" class="sr-only"></label>
        <select name="mode" id="availability_gearup_mode" class="custom-select form-select">
            {{#each modes}}
            <option value="{{ value }}">{{ label }}</option>
            {{/each}}
        </select>
        <a class="btn btn-link p-0" role="button"
            data-container="body"
            data-bs-toggle="popover"
            data-toggle="popover"
            data-content="{{ help.text }}"
            data-bs-content="{{ help.text }}"
            data-html="true"
            data-bs-html="true"
            tabindex="0" data-trigger="focus">
            {{{ help.icon }}}
        </a>
    </div>
</div>`;

M.availability_gearup = M.availability_gearup || {}; // eslint-disable-line

M.availability_gearup.form = Y.merge(M.core_availability.plugin, {

    achievements: null,
    challenges: null,
    quests: null,
    helphtml: null,
    helpiconhtml: null,
    _node: null,

    initInner: function(params) {
        this.achievements = params.achievements;
        this.challenges = params.challenges;
        this.quests = params.quests;
        this.helphtml = params.helphtml;
        this.helpiconhtml = params.helpiconhtml;
    },

    getNode: function(json) {
        var template;

        if (!this._node) {
            template = Y.Handlebars.compile(TEMPLATE);
            this._node = Y.Node.create(template({
                modes: [
                    {value: MODE_IS_RECRUIT, label: M.util.get_string('isrecruit', 'availability_gearup')},
                    {value: MODE_HAS_STARTED, label: M.util.get_string('hasstarted', 'availability_gearup')},
                    {value: MODE_HAS_COMPLETED, label: M.util.get_string('hascompleted', 'availability_gearup')},
                    {value: MODE_IS_ASSIGNED, label: M.util.get_string('isassigned', 'availability_gearup')},
                    {value: MODE_IS_STARTED, label: M.util.get_string('isstarted', 'availability_gearup')},
                    // {value: MODE_IS_COMPLETED, label: M.util.get_string('iscompleted', 'availability_gearup')},
                    {value: MODE_IS_ENDED, label: M.util.get_string('isended', 'availability_gearup')},
                ],
                achievements: this.achievements.map(function(m) {
                    return {value: m.id, label: m.title};
                }),
                challenges: this.challenges.map(function(m) {
                    return {value: m.id, label: m.title};
                }),
                quests: this.quests.map(function(m) {
                    return {value: m.id, label: m.title};
                }),
                help: {
                    text: this.helphtml,
                    icon: this.helpiconhtml,
                }
            }));

            // When any select changes.
            Y.one('#fitem_id_availabilityconditionsjson, .availability-field').delegate('change', function() {
                M.core_availability.form.update();
            }, '.availability_gearup select');
        }


        const node = this._node.cloneNode(true);
        if (typeof json.missionid !== 'undefined') {
            node.one('[name=missionid]').set('value', json.missionid);
        }

        const mode = typeof json.mode === 'undefined' ? MODE_IS_ENDED : json.mode;
        node.one('[name=mode]').set('value', mode);

        return node;
    },

    fillValue: function(value, node) {
        const missions = node.one('[name=missionid]');
        const modes = node.one('[name=mode]');
        value.missionid = parseInt(missions.get('value'), 10);
        value.mode = parseInt(modes.get('value'), 10);
    },

    fillErrors: function(errors, node) {
        const missions = node.one('[name=missionid]');
        const missionid = parseInt(missions.get('value'), 10);
        if (isNaN(missionid) || !missionid || missionid < 0) {
            errors.push('availability_gearup:pleaseselectone');
        }
    }
});
