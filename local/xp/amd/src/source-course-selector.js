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

/**
 * Source course selector.
 *
 * @module     local_xp/source-course-selector
 * @copyright  2026 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import {getString} from 'core/str';
import {createModal} from 'block_xp/compat';
import Throttler from 'block_xp/throttler';
import {ws} from 'block_xp/utils';

/**
 * Course search and source selection widget.
 */
class SourceCourseSelector {
    /**
     * Initialise the widget.
     *
     * @param {HTMLElement} root The widget container.
     * @param {Object} options
     * @param {Number} options.contextId The world's context ID.
     * @param {Number|null} options.siteId The site course ID to exclude.
     * @param {Number[]} options.sourceContextIds The selected source context IDs.
     * @param {String} options.removeLabel The translated Remove label.
     */
    constructor(root, {contextId, siteId, sourceContextIds, removeLabel}) {
        this.root = root;
        this.contextId = contextId;
        this.siteId = siteId;
        this.removeLabel = removeLabel;
        this.sources = new Map(sourceContextIds.map(id => [Number(id), {
            selected: true,
            saved: true,
            revision: 0,
            queue: Promise.resolve(),
        }]));
        this.changed = false;
        this.throttler = new Throttler(300);
        this.searchId = 0;

        this.termField = root.querySelector('.search-term-course');
        this.results = root.querySelector('.search-results');
        this.loading = root.querySelector('.searching-results');
        this.empty = root.querySelector('.results-empty');
        this.status = root.querySelector('.search-status');
        this.body = this.results.querySelector('tbody');
        this.rowTemplate = this.body.querySelector('.resource-template');
        this.addLabel = this.rowTemplate.querySelector('button').textContent;
        this.rowTemplate.classList.remove('resource-template');
        this.rowTemplate.remove();

        this.termField.addEventListener('input', () => this.onInput());
    }

    /**
     * Focus the search field.
     */
    focus() {
        this.termField.focus();
    }

    /**
     * Wait for queued writes and report whether any succeeded.
     *
     * @returns {Promise<Boolean>}
     */
    async finish() {
        this.throttler.cancel();
        await Promise.all(Array.from(this.sources.values(), source => source.queue));
        return this.changed;
    }

    /**
     * Schedule a search when the query changes.
     */
    onInput() {
        const query = this.termField.value.trim();
        const requestId = ++this.searchId;
        this.throttler.cancel();
        this.showState(query.length >= 3 ? 'loading' : null);
        if (query.length >= 3) {
            this.throttler.schedule(() => this.search(query, requestId));
        }
    }

    /**
     * Search for courses, ignoring obsolete responses.
     *
     * @param {String} query The search query.
     * @param {Number} requestId The scheduled search ID.
     */
    async search(query, requestId) {
        if (!this.root.isConnected || requestId !== this.searchId) {
            return;
        }
        let courses;
        try {
            courses = await ws('block_xp_search_courses', {query});
        } catch (error) {
            if (this.root.isConnected && requestId === this.searchId) {
                this.showState(null);
                Notification.exception(error);
            }
            return;
        }
        courses = courses.filter(course => Number(course.id) !== Number(this.siteId));
        const resultsLabel = await getString('resultsfound', 'core', courses.length);
        if (!this.root.isConnected || requestId !== this.searchId) {
            return;
        }
        this.displayResults(courses, resultsLabel);
    }

    /**
     * Display courses with their current selection state.
     *
     * @param {Object[]} courses The search results.
     * @param {String} resultsLabel The translated result count.
     */
    displayResults(courses, resultsLabel) {
        this.body.textContent = '';
        courses.forEach(course => {
            const sourcecontextid = Number(course.contextid);
            if (!this.sources.has(sourcecontextid)) {
                this.sources.set(sourcecontextid, {
                    selected: false,
                    saved: false,
                    revision: 0,
                    queue: Promise.resolve(),
                });
            }
            const source = this.sources.get(sourcecontextid);
            const row = this.rowTemplate.cloneNode(true);
            const name = row.querySelector('.resource-name');
            name.id = this.termField.id + '-course-' + sourcecontextid;
            name.textContent = course.fullname;
            row.querySelector('.resource-subname').textContent = course.shortname;
            source.button = row.querySelector('button');
            source.button.id = name.id + '-action';
            source.button.setAttribute('aria-labelledby', source.button.id + ' ' + name.id);
            this.updateButton(source);
            source.button.addEventListener('click', () => this.toggle(sourcecontextid, source));
            this.body.appendChild(row);
        });
        this.showState(this.body.children.length ? 'results' : 'empty', resultsLabel);
    }

    /**
     * Show the current search state.
     *
     * @param {String|null} state The state to display, or null to clear it.
     * @param {String} [message] The status message to announce.
     */
    showState(state, message = '') {
        this.loading.style.display = state === 'loading' ? '' : 'none';
        this.empty.style.display = state === 'empty' ? '' : 'none';
        this.results.style.display = state === 'results' ? '' : 'none';
        const status = state === 'loading' ? this.loading.textContent.trim() : message;
        if (this.status.textContent !== status) {
            this.status.textContent = status;
        }
    }

    /**
     * Toggle a source optimistically and queue its write.
     *
     * @param {Number} sourcecontextid The source course's context ID.
     * @param {Object} source The course's selection and queue state.
     */
    toggle(sourcecontextid, source) {
        const selected = !source.selected;
        const revision = ++source.revision;
        source.selected = selected;
        this.updateButton(source);

        // Keep each course's writes in order, even when Remove is clicked before the add finishes.
        source.queue = source.queue.then(() => ws(selected ? 'local_xp_add_source' : 'local_xp_remove_source', {
            contextid: this.contextId,
            sourcecontextid,
        })).then(() => {
            source.saved = selected;
            this.changed = true;
            return;
        }).catch(error => {
            // An older failure must not overwrite a more recent click.
            if (source.revision === revision) {
                source.selected = source.saved;
                this.updateButton(source);
            }
            Notification.exception(error);
        });
    }

    /**
     * Update the course's Add or Remove label.
     *
     * @param {Object} source The course's selection and button state.
     */
    updateButton(source) {
        source.button.textContent = source.selected ? this.removeLabel : this.addLabel;
    }
}

/**
 * Open the course selector for a global world.
 *
 * @param {Object} options
 * @param {Number} options.contextId The world's context ID.
 * @param {Number} [options.siteId] The site course ID to exclude.
 * @param {Number[]} [options.sourceContextIds] The selected source context IDs.
 * @returns {Promise<void>}
 */
export const openSelector = async({contextId, siteId = null, sourceContextIds = []}) => {
    const removeLabel = await getString('remove', 'core');
    const modal = await createModal({
        template: 'local_xp/modal-source-courses-selector',
        removeOnClose: true,
    });
    const root = modal.getRoot()[0];
    root.classList.add('block_xp');
    const selector = new SourceCourseSelector(root, {contextId, siteId, sourceContextIds, removeLabel});
    modal.getRoot().one(ModalEvents.hidden, async() => {
        if (await selector.finish()) {
            window.location.reload();
        }
    });
    await modal.show();
    selector.focus();
};
