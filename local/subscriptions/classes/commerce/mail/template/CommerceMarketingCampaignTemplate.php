<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\commerce\mail\builder\CommerceMailBuilderCtaRenderer;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingOptoutService;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignContentPolicy;
use local_subscriptions\commerce\mail\campaign\CommerceMarketingCampaignRepository;
use local_subscriptions\commerce\mail\CommerceMailTerminalCancellationException;
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

        $contents = (new CommerceMarketingCampaignRepository($DB))->contents($campaignid);
        $content = CommerceMarketingCampaignContentPolicy::select($contents, $language);
        if ($content === null) {
            // An already-queued incompatible message must never be retried forever.
            throw new CommerceMailTerminalCancellationException(
                'marketing_language_unavailable',
                'No compatible campaign translation for recipient language: ' . $language
            );
        }
        // Ensure greeting, unsubscribe text and message metadata match the actual
        // translation if we fell back to French.
        $language = (string)$content->language;

        $campaign = $DB->get_record(
            'local_subs_mail_campaign',
            ['id' => $campaignid],
            'id,name,ctaurl,status',
            MUST_EXIST
        );

        $recipient = $request->get_recipient();
        $optouts = CommerceMarketingOptoutService::create($DB);
        if ($optouts->is_suppressed($recipient->get_email())) {
            throw new CommerceMailTerminalCancellationException(
                'marketing_optout', 'Recipient has opted out of marketing emails.'
            );
        }

        // Resolve the frozen recipient, not the current Moodle user profile.
        // Required to build a tamper-resistant, email-free unsubscribe URL.
        $campaignrecipient = $DB->get_record('local_subs_mail_campaign_recipient', [
            'campaignid' => $campaignid,
            'email' => strtolower(trim($recipient->get_email())),
        ], 'id,email', MUST_EXIST);
        $unsubscribeurl = $optouts->url_for((int)$campaignrecipient->id,
            (string)$campaignrecipient->email)->out(false);

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

        // HTML + plain-text alternatives must both contain the unsubscribe URL.
        $unsubscribe = match ($language) {
            'ru' => 'Отписаться от рекламных писем CampusFR',
            'en' => 'Unsubscribe from CampusFR marketing emails',
            default => 'Se désabonner des emails commerciaux CampusFR',
        };
        $formatted .= '<p style="margin-top:26px;font-size:12px;color:#6b7280">'
            . '<a href="' . s($unsubscribeurl) . '">' . s($unsubscribe) . '</a></p>';

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

        // MailRenderer's HTML-to-text conversion removes <a href>; make the
        // plain-text opt-out link explicit so every mail client has a working URL.
        $text .= "\n\n" . $unsubscribe . ': ' . $unsubscribeurl;

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
