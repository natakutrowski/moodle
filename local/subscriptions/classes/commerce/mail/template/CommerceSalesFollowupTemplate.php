<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailTemplate;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\mail\MailRenderer;

final class CommerceSalesFollowupTemplate implements CommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::SALES_FOLLOWUP;
    }

    public function render(CommerceMailRequest $request): CommerceMailMessage {
        $context = $request->get_context();
        $subject = trim((string)$context->require('subject'));
        $bodyhtml = (string)$context->require('bodyhtml');
        $values = (array)$context->get('tokens', []);

        $tokens = [];
        foreach ($values as $name => $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $tokens['{{' . $name . '}}'] = (string)$value;
            $tokens['{' . $name . '}'] = (string)$value;
        }

        $subject = strtr($subject, $tokens);
        $bodyhtml = strtr($bodyhtml, $tokens);

        $ctaurl = trim((string)($values['checkout_url'] ?? ''));
        $resumelabel = trim((string)$context->get('resume_payment_label', ''));
        $resumehtml = '';
        if ($ctaurl !== '' && $resumelabel !== '') {
            $resumehtml = (new CommerceMailBuilderCtaRenderer())->button(
                $ctaurl,
                $resumelabel,
                \local_subscriptions\commerce\mail\builder\CommerceMailBuilder::CTA_CAMPUS_PINK
            );
        }
        $bodyhtml = str_replace(
            ['<p>{{resume_payment}}</p>', '{{resume_payment}}'],
            [$resumehtml, $resumehtml],
            $bodyhtml
        );

        $formatted = format_text($bodyhtml, FORMAT_HTML, [
            'context' => context_system::instance(),
            'filter' => false,
            'noclean' => false,
            'para' => false,
        ]);

        [$html, $text] = MailRenderer::layout(
            $subject,
            $formatted,
            null,
            null,
            [
                'preheader' => '',
                'headcss' => (new CommerceMailBuilderCtaRenderer())->hover_css(),
            ]
        );

        return new CommerceMailMessage(
            $request->get_recipient(),
            $subject,
            $html,
            $text,
            [
                'language' => $request->get_language(),
                'purchaseid' => $request->get_purchase_id(),
                'template' => 'sales_followup',
                'source_template_id' => (int)$context->get('source_template_id', 0),
                'manual' => true,
            ]
        );
    }
}
