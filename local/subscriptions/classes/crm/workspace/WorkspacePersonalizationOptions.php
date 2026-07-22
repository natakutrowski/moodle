<?php

namespace local_subscriptions\crm\workspace;

defined('MOODLE_INTERNAL') || die();

/**
 * Presentation options for one generic Workspace personalization panel.
 */
final class WorkspacePersonalizationOptions {

    /**
     * @param array<string, string> $zonelabels
     * @param array<string, array{
     *     classes?: string[],
     *     attributes?: array<string, string>,
     *     badges?: array<int, array{
     *         label: string,
     *         kind?: string
     *     }>
     * }> $itempresentation
     */
    public function __construct(
        public readonly string $panelid,
        public readonly string $titleid,
        public readonly string $openlabel,
        public readonly string $title,
        public readonly string $description,
        public readonly string $closelabel,
        public readonly string $resetlabel,
        public readonly string $saveerror,
        public readonly string $resetconfirm,
        public readonly string $savemethod,
        public readonly array $zonelabels,
        public readonly array $itempresentation = [],
        public readonly string $orderhint = '',
        public readonly string $visibilitylabeltemplate = '',
        public readonly bool $includefixeditems = true,
        public readonly string $rootclass = ''
    ) {
        if ($this->panelid === '') {
            throw new \coding_exception(
                'Workspace personalization requires a panel ID.'
            );
        }

        if ($this->titleid === '') {
            throw new \coding_exception(
                'Workspace personalization requires a title ID.'
            );
        }

        if ($this->savemethod === '') {
            throw new \coding_exception(
                'Workspace personalization requires an external method.'
            );
        }
    }

    /**
     * Returns the translated label for one Workspace zone.
     */
    public function zone_label(string $zone): string {
        return $this->zonelabels[$zone] ?? $zone;
    }

    /**
     * Returns presentation metadata for one Workspace item.
     *
     * @return array{
     *     classes: string[],
     *     attributes: array<string, string>,
     *     badges: array<int, array{
     *         label: string,
     *         kind?: string
     *     }>
     * }
     */
    public function item_presentation(string $key): array {
        $presentation =
            $this->itempresentation[$key] ?? [];

        return [
            'classes' =>
                is_array($presentation['classes'] ?? null)
                    ? $presentation['classes']
                    : [],

            'attributes' =>
                is_array($presentation['attributes'] ?? null)
                    ? $presentation['attributes']
                    : [],

            'badges' =>
                is_array($presentation['badges'] ?? null)
                    ? $presentation['badges']
                    : [],
        ];
    }

    /**
     * Builds an accessible visibility label for one item.
     */
    public function visibility_label(
        WorkspaceItemDefinition $item
    ): string {
        if ($this->visibilitylabeltemplate === '') {
            return $item->label;
        }

        return str_replace(
            '{$a}',
            $item->label,
            $this->visibilitylabeltemplate
        );
    }
}