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

final class CommerceMarketingCampaignTemplate implements CommerceMailTemplate {
    public function get_type(): string {
        return CommerceMailType::MARKETING_CAMPAIGN;
    }

    public function render(CommerceMailRequest $request): CommerceMailMessage {
        global $DB;

        $campaignid = (int)$request->get_context()->require('campaignid');
        $language = $request->get_language();

        $content = $DB->get_record(
            'local_subs_mail_campaign_content',
            ['campaignid' => $campaignid, 'language' => $language],
            '*',
            IGNORE_MISSING
        );
        if (!$content && $language !== 'fr') {
            $content = $DB->get_record(
                'local_subs_mail_campaign_content',
                ['campaignid' => $campaignid, 'language' => 'fr'],
                '*',
                IGNORE_MISSING
            );
        }
        if (!$content) {
            throw new \coding_exception('Marketing campaign has no usable content.');
        }

        $campaign = $DB->get_record(
            'local_subs_mail_campaign',
            ['id' => $campaignid],
            'id,name,ctaurl,status',
            MUST_EXIST
        );

        $recipient = $request->get_recipient();
        $firstname = trim((string)$request->get_context()->get('firstname', ''));
        $greeting = match ($language) {
            'ru' => $firstname !== '' ? 'Здравствуйте, ' . $firstname . '!' : 'Здравствуйте!',
            'en' => $firstname !== '' ? 'Hello ' . $firstname . '!' : 'Hello!',
            default => $firstname !== '' ? 'Bonjour ' . $firstname . ' !' : 'Bonjour !',
        };
        $tokens = [
            '{{greeting}}' => $greeting,
            '{{firstname}}' => $firstname,
            '{{fullname}}' => trim((string)$request->get_context()->get('fullname', '')),
            '{{username}}' => trim((string)$request->get_context()->get('username', '')),
            '{{email}}' => $recipient->get_email(),
        ];

        $subject = strtr((string)$content->subject, $tokens);
        $bodyhtml = strtr((string)$content->bodyhtml, $tokens);
        $preheader = strtr((string)$content->preheader, $tokens);

        $cta = new CommerceMailBuilderCtaRenderer();
        $bodyhtml = $cta->render_tags($bodyhtml, trim((string)$campaign->ctaurl));

        $formatted = format_text($bodyhtml, FORMAT_HTML, [
            'context' => context_system::instance(),
            'filter' => false,
            'noclean' => false,
            'para' => false,
        ]);

        [$html, $text] = MailRenderer::layout(
            trim($subject),
            $formatted,
            null,
            null,
            [
                'preheader' => $preheader,
                'headcss' => $cta->hover_css(),
            ]
        );

        return new CommerceMailMessage(
            $recipient,
            trim($subject),
            $html,
            $text,
            [
                'language' => $language,
                'campaignid' => $campaignid,
                'template' => 'marketing_campaign',
            ]
        );
    }
}
