<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Generic per-user CRM Workspace preference service.
 */
final class WorkspacePreferenceService {

    public function __construct(
        private readonly WorkspaceDefinition $definition
    ) {
    }

    /**
     * Loads one user's normalized Workspace layout.
     */
    public function load(?int $userid = null): WorkspaceLayout {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        if ($userid <= 0) {
            return $this->defaults();
        }

        $raw = get_user_preferences(
            $this->definition->preferencekey,
            '',
            $userid
        );

        if (!is_string($raw) || trim($raw) === '') {
            return $this->defaults();
        }

        try {
            $decoded = json_decode(
                $raw,
                true,
                32,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return $this->defaults();
        }

        if (!is_array($decoded)) {
            return $this->defaults();
        }

        return $this->normalize($decoded);
    }

    /**
     * Saves one user's normalized Workspace layout.
     */
    public function save(
        array $layout,
        ?int $userid = null
    ): WorkspaceLayout {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        if ($userid <= 0) {
            throw new \coding_exception(
                'A valid user is required to save a Workspace layout.'
            );
        }

        $normalized = $this->normalize($layout);

        set_user_preference(
            $this->definition->preferencekey,
            json_encode(
                $normalized->to_array(),
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            ),
            $userid
        );

        return $normalized;
    }

    /**
     * Removes one user's Workspace preference.
     */
    public function reset(
        ?int $userid = null
    ): WorkspaceLayout {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        if ($userid > 0) {
            unset_user_preference(
                $this->definition->preferencekey,
                $userid
            );
        }

        return $this->defaults();
    }

    /**
     * Returns the default Workspace layout.
     */
    public function defaults(): WorkspaceLayout {
        return new WorkspaceLayout(
            $this->definition->default_hidden(),
            $this->definition->default_order()
        );
    }

    /**
     * Normalizes persisted or submitted layout data.
     *
     * Old 7.90B layouts remain supported:
     * - version 1 is accepted;
     * - future items are appended automatically;
     * - unknown items are discarded;
     * - items cannot be moved into an invalid zone.
     */
    public function normalize(
        array $layout
    ): WorkspaceLayout {

        $version = $layout['version'] ?? 1;

        if (
            !is_int($version)
            && !is_numeric($version)
        ) {
            $version = 1;
        }

        $version = max(1, (int)$version);

        /*
        * Versions 1 and 2 currently share the same persisted shape.
        * Unknown future versions are normalized defensively instead of
        * being trusted directly.
        */
        if ($version > WorkspaceLayout::VERSION) {
            $layout = [
                'version' => WorkspaceLayout::VERSION,
                'hidden' =>
                    is_array($layout['hidden'] ?? null)
                        ? $layout['hidden']
                        : [],
                'order' =>
                    is_array($layout['order'] ?? null)
                        ? $layout['order']
                        : [],
            ];
        }

        $items = $this->definition->items();
        $zones = $this->definition->zones();

        $validkeys = array_fill_keys(
            array_keys($items),
            true
        );

        $hidden = [];

        foreach (($layout['hidden'] ?? []) as $key) {
            if (
                !is_string($key)
                || !isset($validkeys[$key])
                || !$items[$key]->hideable
            ) {
                continue;
            }

            $hidden[$key] = true;
        }

        $submittedorder = $layout['order'] ?? [];

        if (!is_array($submittedorder)) {
            $submittedorder = [];
        }

        $defaultorder =
            $this->definition->default_order();

        $normalizedorder = [];
        $knownsubmitteditems = [];

        foreach ($zones as $zone) {
            $normalizedorder[$zone] = [];
            $seeninzone = [];

            $submittedzone =
                $submittedorder[$zone] ?? [];

            if (is_array($submittedzone)) {
                foreach ($submittedzone as $key) {
                    if (
                        !is_string($key)
                        || isset($seeninzone[$key])
                        || !isset($items[$key])
                        || $items[$key]->zone !== $zone
                    ) {
                        continue;
                    }

                    $normalizedorder[$zone][] = $key;
                    $seeninzone[$key] = true;
                    $knownsubmitteditems[$key] = true;
                }
            }

            foreach ($defaultorder[$zone] as $key) {
                if (isset($seeninzone[$key])) {
                    continue;
                }

                $normalizedorder[$zone][] = $key;
                $seeninzone[$key] = true;

                /*
                 * A newly registered item that was not present in the
                 * persisted order adopts its default visibility.
                 */
                if (
                    !isset($knownsubmitteditems[$key])
                    && !$items[$key]->defaultvisible
                    && $items[$key]->hideable
                ) {
                    $hidden[$key] = true;
                }
            }
        }

        return new WorkspaceLayout(
            array_keys($hidden),
            $normalizedorder
        );
    }
}