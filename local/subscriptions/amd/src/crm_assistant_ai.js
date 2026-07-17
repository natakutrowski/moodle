/* eslint-env amd */
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';

const selectors = {
    root: '[data-crm-assistant-ai]',
    question: '.crm-assistant-ai-question',
    submit: '.crm-assistant-ai-submit',
    status: '.crm-assistant-ai-status',
    answer: '.crm-assistant-ai-answer',
    example: '.crm-assistant-ai-example',
};

const escapeHtml = (value) => {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
};

const renderList = (title, items) => {
    if (!Array.isArray(items) || items.length === 0) {
        return '';
    }

    return `
        <section class="crm-assistant-ai-section">
            <h4 class="h6">${escapeHtml(title)}</h4>
            <ul>
                ${items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}
            </ul>
        </section>
    `;
};

const renderReferences = (references, title) => {
    if (!Array.isArray(references) || references.length === 0) {
        return '';
    }

    return `
        <section class="crm-assistant-ai-section">
            <h4 class="h6">${escapeHtml(title)}</h4>
            <ul>
                ${references.map((reference) => `
                    <li>
                        <strong>${escapeHtml(reference.label)}</strong>
                        ${reference.reason ? ` — ${escapeHtml(reference.reason)}` : ''}
                    </li>
                `).join('')}
            </ul>
        </section>
    `;
};

const renderAnswer = async(root, answer) => {
    const answerNode = root.querySelector(selectors.answer);

    const [
        keypointsLabel,
        actionsLabel,
        warningsLabel,
        referencesLabel,
        confidenceLabel,
    ] = await Promise.all([
        getString('crm_assistant_ai_keypoints', 'local_subscriptions'),
        getString('crm_assistant_ai_suggested_actions', 'local_subscriptions'),
        getString('crm_assistant_ai_warnings', 'local_subscriptions'),
        getString('crm_assistant_ai_references', 'local_subscriptions'),
        getString('crm_assistant_ai_confidence', 'local_subscriptions'),
    ]);

    answerNode.innerHTML = `
        <div class="alert alert-light border">
            <div class="crm-assistant-ai-main-answer">
                ${escapeHtml(answer.answer)}
            </div>

            ${renderList(keypointsLabel, answer.keypoints)}
            ${renderList(actionsLabel, answer.suggestedactions)}
            ${renderList(warningsLabel, answer.warnings)}
            ${renderReferences(answer.references, referencesLabel)}

            <div class="small text-muted mt-3">
                ${escapeHtml(confidenceLabel)}:
                ${Math.round((answer.confidence ?? 0) * 100)}%
            </div>
        </div>
    `;
};

const ask = async(root) => {
    const questionNode = root.querySelector(selectors.question);
    const submitNode = root.querySelector(selectors.submit);
    const statusNode = root.querySelector(selectors.status);
    const answerNode = root.querySelector(selectors.answer);

    const question = questionNode.value.trim();

    if (!question) {
        questionNode.focus();
        return;
    }

    submitNode.disabled = true;
    root.setAttribute('aria-busy', 'true');
    answerNode.innerHTML = '';

    statusNode.textContent = await getString(
        'crm_assistant_ai_thinking',
        'local_subscriptions'
    );

    try {
        const body = new URLSearchParams({
            sesskey: root.dataset.sesskey,
            question,
            scope: root.dataset.scope || 'global',
            userid: root.dataset.userid || '0',
            recommendationid: root.dataset.recommendationid || '0',
        });

        const response = await fetch(root.dataset.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body,
        });

        const payload = await response.json();

        if (!response.ok || !payload.success || !payload.result?.answer) {
            throw new Error(
                payload.error ||
                payload.result?.reason ||
                'CRM Assistant request failed'
            );
        }

        await renderAnswer(
            root,
            payload.result.answer
        );

        statusNode.textContent = '';
    } catch (error) {
        statusNode.textContent = await getString(
            'crm_assistant_ai_request_failed',
            'local_subscriptions'
        );

        Notification.exception(error);
    } finally {
        submitNode.disabled = false;
        root.removeAttribute('aria-busy');
    }
};

export const init = () => {
    document.querySelectorAll(selectors.root).forEach((root) => {
        const submitNode = root.querySelector(selectors.submit);
        const questionNode = root.querySelector(selectors.question);

        submitNode?.addEventListener('click', () => ask(root));

        questionNode?.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                ask(root);
            }
        });

        root.querySelectorAll(selectors.example).forEach((button) => {
            button.addEventListener('click', () => {
                questionNode.value = button.dataset.question || '';
                questionNode.focus();
            });
        });
    });
};