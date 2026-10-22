<?php
/** Enhance normal GET search forms progressively, without a new UI framework. */
defined('MOODLE_INTERNAL') || die();
function crm_team_init_search(moodle_page $page): void {
    $endpoint = (new moodle_url('/local/subscriptions/admin/team/search.php'))->out(false);
    $sess = sesskey();
    $page->requires->js_init_code('(function() {
        const form = document.querySelector("[data-crm-team-search]");
        if (!form) { return; }
        const input = form.querySelector("input[name=search]");
        const panel = form.querySelector("#crm-team-suggestions");
        if (!input || !panel) { return; }
        input.setAttribute("aria-autocomplete", "list");
        panel.classList.add("list-group", "mb-2");
        let controller = null, timer = null;
        const clear = () => { panel.replaceChildren(); };
        input.addEventListener("input", () => {
            clearTimeout(timer);
            if (controller) { controller.abort(); }
            clear();
            const q = input.value.trim();
            if (q.length < 2) { return; }
            timer = setTimeout(async () => {
                controller = new AbortController();
                const url = new URL(' . json_encode($endpoint) . ', window.location.origin);
                url.searchParams.set("q", q);
                url.searchParams.set("scope", form.dataset.crmTeamSearch);
                url.searchParams.set("sesskey", ' . json_encode($sess) . ');
                try {
                    const response = await fetch(url.toString(), {credentials: "same-origin", signal: controller.signal});
                    if (!response.ok) { return; }
                    const result = await response.json();
                    if (input.value.trim() !== q) { return; }
                    clear();
                    for (const person of result.items || []) {
                        const link = document.createElement("a");
                        link.className = "list-group-item list-group-item-action";
                        link.href = person.url;
                        const name = document.createElement("strong");
                        name.textContent = person.label;
                        const email = document.createElement("small");
                        email.className = "text-muted ml-2";
                        email.textContent = person.email;
                        link.append(name, email);
                        panel.append(link);
                    }
                } catch (e) { if (e.name !== "AbortError") { clear(); } }
            }, 250);
        });
        input.addEventListener("keydown", e => {
            if (e.key === "Escape") { clear(); }
        });
    })();');
}
