/* eslint-env amd */
define([
    'local_subscriptions/command_center/render'
], function(Render) {
    'use strict';

    function debounce(state, setActive) {
        window.clearTimeout(state.debounceTimer);

        state.debounceTimer = window.setTimeout(function() {
            search(state, state.input.value, setActive);
        }, 180);
    }

    function search(state, query, setActive) {
        query = query.trim();

        if (query === state.lastQuery) {
            return;
        }

        state.lastQuery = query;

        if (query.length < 2) {
            Render.showInitialState(state, setActive);
            return;
        }

        if (!state.searchUrl) {
            Render.showMessage(state, state.errorLabel);
            return;
        }

        Render.showMessage(state, state.loadingLabel);

        if (state.controller) {
            state.controller.abort();
        }

        state.controller = new AbortController();

        fetch(state.searchUrl + '?q=' + encodeURIComponent(query), {
            method: 'GET',
            credentials: 'same-origin',
            signal: state.controller.signal
        })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Invalid response');
                }

                return response.json();
            })
            .then(function(data) {
                if (!data || data.success === false) {
                    Render.showMessage(state, state.errorLabel);
                    return;
                }

                Render.renderResults(state, data.results || [], setActive);
            })
            .catch(function(error) {
                if (error.name !== 'AbortError') {
                    Render.showMessage(state, state.errorLabel);
                }
            });
    }

    return {
        debounce: debounce,
        search: search
    };
});