<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\repositories\InboxTemplateRepository;

final class InboxTemplateService {

    public const TYPE_SIGNATURE = 'signature';
    public const TYPE_QUICK_REPLY = 'quickreply';

    public function __construct(
        private readonly InboxTemplateRepository $repository =
            new InboxTemplateRepository(),
        private readonly InboxReplyHtmlService $html =
            new InboxReplyHtmlService()
    ) {
    }

    public static function valid_type(
        string $type
    ): bool {
        return in_array(
            $type,
            [
                self::TYPE_SIGNATURE,
                self::TYPE_QUICK_REPLY,
            ],
            true
        );
    }

    public function save(
        ?int $id,
        ?int $accountid,
        string $type,
        string $name,
        ?string $subject,
        string $bodyhtml,
        bool $enabled,
        int $sortorder,
        int $actorid
    ): object {
        if (!self::valid_type($type)) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox template type.'
            );
        }

        $name = trim($name);

        if ($name === '') {
            throw new \moodle_exception(
                'crm_inbox_template_name_required_o9',
                'local_subscriptions'
            );
        }

        $safehtml = $this->html->sanitize(
            $bodyhtml
        );

        if (trim($safehtml) === '') {
            throw new \moodle_exception(
                'crm_inbox_template_body_required_o9',
                'local_subscriptions'
            );
        }

        $bodytext = $this->html->text_version(
            $safehtml
        );

        return $this->repository->upsert(
            $id,
            $accountid,
            $type,
            $name,
            $type === self::TYPE_QUICK_REPLY
                ? clean_param(
                    (string)$subject,
                    PARAM_TEXT
                )
                : null,
            $bodytext,
            $safehtml,
            $enabled,
            $sortorder,
            $actorid
        );
    }

    public function default_signature(
        int $accountid
    ): ?object {
        $signatures =
            $this->repository
                ->get_enabled_for_account(
                    $accountid,
                    self::TYPE_SIGNATURE
                );

        return $signatures[0] ?? null;
    }

    /**
     * @return object[]
     */
    public function quick_replies(
        int $accountid
    ): array {
        return $this->repository
            ->get_enabled_for_account(
                $accountid,
                self::TYPE_QUICK_REPLY
            );
    }

    public function append_signature(
        int $accountid,
        string $bodyhtml
    ): string {
        $signature = $this->default_signature(
            $accountid
        );

        if (
            !$signature
            || trim(
                (string)$signature->bodyhtml
            ) === ''
        ) {
            return $bodyhtml;
        }

        $marker =
            'data-crm-inbox-signature="'
            . (int)$signature->id
            . '"';

        if (str_contains(
            $bodyhtml,
            $marker
        )) {
            return $bodyhtml;
        }

        $signaturehtml =
            '<div class="crm-inbox-signature" '
            . $marker
            . '>'
            . (string)$signature->bodyhtml
            . '</div>';

        return rtrim($bodyhtml)
            . '<p><br></p>'
            . $signaturehtml;
    }
}
