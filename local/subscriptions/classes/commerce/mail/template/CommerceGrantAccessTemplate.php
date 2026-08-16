<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\service\CommerceGrantMailStudioSelection;

final class CommerceGrantAccessTemplate extends AbstractCommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::GRANT_ACCESS;
    }


    protected function resolve_editorial(
        CommerceMailRequest $request,
        array $context,
        array $editorial
    ): array {
        $grant = $request->get_context()->get('grantaccess', []);
        $snapshot = is_array($grant)
            && is_array($grant['mailtemplatesnapshot'] ?? null)
            ? $grant['mailtemplatesnapshot']
            : [];

        if ($snapshot === []) {
            return $editorial;
        }

        return CommerceGrantMailStudioSelection::create()
            ->resolve($snapshot, $request->get_language())
            ?? $editorial;
    }

    protected function additional_context(CommerceMailRequest $request): array {
        $grant = $request->get_context()->get('grantaccess', []);
        $activationurl = is_array($grant) ? trim((string)($grant['activationurl'] ?? '')) : '';
        $expiresat = is_array($grant) ? (int)($grant['activationexpiresat'] ?? 0) : 0;
        return [
            'hasaccountactivation' => $activationurl !== '',
            'accountactivationurl' => $activationurl,
            'accountactivationexpires' => $expiresat > 0 ? userdate($expiresat, get_string('strftimedatetime')) : '',
            'accountactivationtitle' => get_string('commerce_manual_grant_activation_mail_title', 'local_subscriptions'),
            'accountactivationhelp' => get_string('commerce_manual_grant_activation_mail_help', 'local_subscriptions'),
            'accountactivationbutton' => get_string('commerce_manual_grant_activation_mail_button', 'local_subscriptions'),
        ];
    }

    protected function subject_key(): string {
        return 'commerce_mail_grant_access_subject';
    }

    protected function template_name(): string {
        return 'grant_access';
    }

    protected function primary_action_label(array $context): ?string {
        return get_string('commerce_mail_access_my_campus', 'local_subscriptions');
    }

    protected function primary_action_icon(array $context): string {
        return 'external';
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['links']['hascampus'])
            ? (string)$context['links']['campus']
            : (new \moodle_url('/mon-campus'))->out(false);
    }
}
