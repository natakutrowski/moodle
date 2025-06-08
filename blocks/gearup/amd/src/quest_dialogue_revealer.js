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
 * Quest dialogue.
 *
 * @module     block_gearup/quest_dialogue_revealer
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default class QuestDialogueRevealer {

    typingDelay = 1000;

    messageTimeout = null;
    typingTimeout = null;

    constructor(rootElement) {
        this.root = rootElement;
        this.typing = rootElement.querySelector('[data-content="typing"]');
        this.narrative = rootElement.querySelector('[data-region="narrative"]');
        this.scrollableParent = rootElement.closest('.modal-dialog-scrollable .modal-body');
    }

    beginFromTyping() {
        this.scrollToBottom();
        this.startRevealingFromTyping();
        this.registerEventListeners();
    }

    beginWithoutTyping() {
        this.scrollToBottom();
        this.startRevealingImmediately();
        this.registerEventListeners();
    }

    getTimeSpentTypingForNode(node) {
        let delay = 3000;
        if (node && node.dataset.contentType !== 'message') {
            delay = 0;
        }
        return delay;
    }

    isScrollAtBottom() {
        const scrollableParent = this.scrollableParent;
        if (!scrollableParent) {
            return false;
        }
        return scrollableParent.scrollTop + scrollableParent.clientHeight >= scrollableParent.scrollHeight;
    }

    queueRevealOf(node, advanceTyping) {
        if (!node) {
            return;
        }

        const timeSpentTyping = this.getTimeSpentTypingForNode(node);
        if (!timeSpentTyping) {
            this.revealNext();
            return;
        }

        const actualTypingDelay = advanceTyping ? 300 : this.typingDelay;
        const nextDelay = timeSpentTyping + actualTypingDelay;
        this.messageTimeout = setTimeout(() => this.revealNext(), nextDelay);
        const nodes = this.root.querySelectorAll(`[data-reveal="next"]`);
        if (nextDelay > actualTypingDelay && nodes.length > 0) {
            this.typingTimeout = setTimeout(() => this.revealTyping(), actualTypingDelay);
        }
    }

    registerEventListeners() {
        document.addEventListener('keydown', (event) => {
            if (event.code === 'Enter' || event.code === 'Space') {
                const target = event.target;
                if (!target || !['A', 'BUTTON'].includes(target.tagName)) {
                    clearTimeout(this.typingTimeout);
                    clearTimeout(this.messageTimeout);
                    this.revealNext(true);
                }
            }
        });
    }

    revealTyping() {
        const shouldScrollAfter = this.isScrollAtBottom();
        this.switchTypingVisibility(true);
        if (shouldScrollAfter) {
            this.scrollToBottom();
        }
    }

    revealNext(advanceTyping) {
        const nodes = this.root.querySelectorAll(`[data-reveal="next"]`);
        const shouldScrollAfter = this.isScrollAtBottom();

        const node = nodes[0];
        if (!node) {
            return;
        }

        this.switchTypingVisibility(false);
        node.removeAttribute('data-reveal');
        node.style.display = 'block';

        if (shouldScrollAfter) {
            this.scrollToBottom();
        }

        this.queueRevealOf(nodes[1], advanceTyping);
    }

    scrollToBottom() {
        const scrollableParent = this.scrollableParent;
        if (!scrollableParent) {
            return;
        }
        scrollableParent.scrollTop = Math.max(0, scrollableParent.scrollHeight - scrollableParent.clientHeight);
    }

    startRevealingFromTyping() {
        const nodes = this.root.querySelectorAll(`[data-reveal="next"]`);
        if (!nodes.length) {
            this.switchTypingVisibility(false);
            return;
        }
        this.queueRevealOf(nodes[0], true);
    }

    startRevealingImmediately() {
        this.revealNext();
    }

    switchTypingVisibility(show) {
        const typing = this.typing;
        if (!typing) {
            return;
        }
        if (show) {
            typing.style.display = 'block';
            return;
        }
        typing.style.display = 'none';
    }
}
