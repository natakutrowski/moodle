<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailMessage;
use local_subscriptions\commerce\mail\CommerceMailRequest;
use local_subscriptions\commerce\mail\CommerceMailTemplate;
use local_subscriptions\commerce\mail\CommerceMailType;
use context_system;
use local_subscriptions\commerce\mail\presentation\CommerceMailPurchasePresentation;
use local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateDefaults;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTokenResolver;
use local_subscriptions\mail\MailRenderer;

/**
 * Common safe renderer for Native transactional Commerce messages.
 */
abstract class AbstractCommerceMailTemplate implements CommerceMailTemplate {

    final public function render(CommerceMailRequest $request): CommerceMailMessage {
        $previouslanguage = force_current_language($request->get_language());

        try {
            global $DB;

            $presentation = CommerceMailPurchasePresentation::from_context($request->get_context());
            $context = $this->template_context($presentation->export()) + $this->additional_context($request);
            $defaults = CommerceMailTemplateDefaults::get($this->get_type(), $request->get_language());
            $record = (new CommerceMailTemplateRepository($DB))->get($this->get_type(), $request->get_language());
            $editorial = ($record !== null && !empty($record->enabled))
                ? [
                    'subject' => (string)$record->subject,
                    'preheader' => (string)$record->preheader,
                    'heading' => (string)$record->heading,
                    'introhtml' => (string)$record->introhtml,
                    'outrohtml' => (string)$record->outrohtml,
                    'signaturehtml' => (string)$record->signaturehtml,
                    'headerimage' => !empty($record->headerimage),
                    'templateid' => (int)$record->id,
                ]
                : $defaults + ['templateid' => 0];

            $editorial = $this->resolve_editorial($request, $context, $editorial);

            if ($this->get_type() === CommerceMailType::PURCHASE_RECEIPT) {
                $legacyoutros = [
                    '<p>Votre facture sera jointe à cet e-mail dès l’activation de cette fonctionnalité. Vous pouvez déjà consulter votre commande à tout moment depuis votre espace CampusFR.</p>',
                    '<p>Your invoice will be attached once that feature is enabled. You can already review your order at any time from your CampusFR account.</p>',
                    '<p>Счёт будет прикрепляться к письму после активации этой функции. Уже сейчас заказ можно в любой момент открыть в вашем аккаунте CampusFR.</p>',
                ];
                if (in_array(trim((string)$editorial['outrohtml']), $legacyoutros, true)) {
                    $editorial['outrohtml'] = (string)$defaults['outrohtml'];
                }
            }

            if ($this->get_type() === CommerceMailType::ACCOUNT_ACTIVATION) {
                $legacyheadings = [
                    'Bienvenue dans votre espace CampusFR !',
                    'Welcome to your CampusFR space!',
                    'Добро пожаловать в ваш CampusFR!',
                ];
                $legacyintros = [
                    '<p>Bonjour {firstname},</p><p>Votre achat est confirmé et votre espace CampusFR est prêt. Il ne vous reste plus qu’à choisir votre mot de passe pour accéder à vos cours, vos ressources et vos commandes.</p>',
                    '<p>Hello {firstname},</p><p>Your purchase is confirmed and your CampusFR space is ready. All that remains is to choose your password to access your courses, resources and orders.</p>',
                    '<p>Здравствуйте, {firstname}!</p><p>Ваша покупка подтверждена, и ваше пространство CampusFR готово. Осталось только выбрать пароль, чтобы получить доступ к курсам, материалам и заказам.</p>',
                ];
                if (
                    in_array(trim((string)$editorial['heading']), $legacyheadings, true)
                    || in_array(trim((string)$editorial['introhtml']), $legacyintros, true)
                ) {
                    $editorial = $defaults + ['templateid' => (int)($editorial['templateid'] ?? 0)];
                }
            }

            $tokens = CommerceMailTokenResolver::values($context);
            $subject = trim(CommerceMailTokenResolver::replace((string)$editorial['subject'], $tokens));
            if ($subject === '') {
                $subject = get_string($this->subject_key(), 'local_subscriptions', $context['reference'] ?: null);
            }
            $auditcopy = $request->get_context()->get('auditcopy', []);
            if (is_array($auditcopy) && !empty($auditcopy['enabled'])) {
                $subject = '[AUDIT] ' . $subject;
            }

            $formatoptions = [
                'context' => context_system::instance(),
                'filter' => false,
                'noclean' => false,
                'para' => false,
            ];
            $context['editorial_heading'] = s(CommerceMailTokenResolver::replace((string)$editorial['heading'], $tokens));
            $context['has_editorial_heading'] = $context['editorial_heading'] !== '';
            foreach (['introhtml' => 'editorial_intro', 'outrohtml' => 'editorial_outro', 'signaturehtml' => 'editorial_signature'] as $source => $target) {
                $resolved = CommerceMailTokenResolver::replace((string)$editorial[$source], $tokens);

                if ($this->get_type() === CommerceMailType::TRIAL_WELCOME && $source === 'outrohtml') {
                    $supportemail = trim((string)($tokens['support_email'] ?? ''));
                    if (
                        $supportemail !== ''
                        && strpos($resolved, 'mailto:' . $supportemail) === false
                        && strpos($resolved, $supportemail) !== false
                    ) {
                        $supportlink = '<a href="mailto:' . s($supportemail)
                            . '" style="color:#f72585;text-decoration:none;font-weight:700;">'
                            . s($supportemail)
                            . '</a>';
                        $resolved = str_replace($supportemail, $supportlink, $resolved);
                    }
                }

                $context[$target] = format_text($resolved, FORMAT_HTML, $formatoptions);
                $context['has_' . $target] = trim(strip_tags($context[$target])) !== '';
            }

            // Personal Offer layout order:
            // offer content -> CTA -> editorial signature -> offer illustration.
            // The generic renderer always places the CTA after $body, so keep the
            // signature aside and let CommercePersonalOfferTemplate render it
            // through the after-button hook.
            if (
                $this->get_type() === CommerceMailType::PERSONAL_OFFER
                && !empty($context['has_editorial_signature'])
            ) {
                $context['personaloffer_after_cta_signature'] = (string)$context['editorial_signature'];
                $context['editorial_signature'] = '';
                $context['has_editorial_signature'] = false;
            }

            $headerimageurl = '';
            if (!empty($editorial['headerimage']) && !empty($editorial['templateid'])) {
                $headerimageurl = CommerceMailHeaderImageService::url((int)$editorial['templateid']);
            }
            if (!empty($editorial['headerimageurl'])) {
                $headerimageurl = trim((string)$editorial['headerimageurl']);
            }

            $body = $this->render_body($this->template_name(), $context);
            [$html, $text] = MailRenderer::layout(
                $subject,
                $body,
                $this->primary_action_label($context),
                $this->primary_action_url($context),
                [
                    'preheader' => CommerceMailTokenResolver::replace((string)$editorial['preheader'], $tokens),
                    'headerimageurl' => $headerimageurl,
                    'buttonvariant' => $this->primary_action_variant($context),
                    'buttonicon' => $this->primary_action_icon($context),
                    'afterbuttonhtml' => $this->primary_action_after_html($context),
                ]
            );

            return new CommerceMailMessage(
                $request->get_recipient(),
                $subject,
                $html,
                $text,
                [
                    'language' => $request->get_language(),
                    'idempotencykey' => $request->get_idempotency_key(),
                    'purchaseid' => $request->get_purchase_id(),
                    'template' => $this->template_name(),
                ],
                $this->attachments($request)
            );
        } finally {
            force_current_language($previouslanguage);
        }
    }

    abstract protected function subject_key(): string;

    abstract protected function template_name(): string;

    /** @return array<string,mixed> */
    protected function additional_context(CommerceMailRequest $request): array {
        return [];
    }

    /**
     * Allow a specialised template to replace editorial copy while preserving
     * the shared CampusFR mail shell and Mail Studio fallback.
     *
     * @param array<string,mixed> $context
     * @param array<string,mixed> $editorial
     * @return array<string,mixed>
     */
    protected function resolve_editorial(CommerceMailRequest $request, array $context, array $editorial): array {
        return $editorial;
    }

    /** @return \local_subscriptions\commerce\mail\CommerceMailAttachment[] */
    protected function attachments(CommerceMailRequest $request): array {
        return [];
    }

    /**
     * @param array<string,mixed> $context
     */
    protected function primary_action_label(array $context): ?string {
        return !empty($context['links']['hasorder'])
            ? get_string('commerce_mail_view_order', 'local_subscriptions')
            : null;
    }

    /**
     * @param array<string,mixed> $context
     */
    /**
     * @param array<string,mixed> $context
     */
    protected function primary_action_variant(array $context): string {
        return 'standard';
    }

    protected function primary_action_icon(array $context): string {
        return '';
    }

    protected function primary_action_after_html(array $context): string {
        return '';
    }

    protected function primary_action_url(array $context): ?string {
        return !empty($context['links']['hasorder']) ? (string)$context['links']['order'] : null;
    }

    /**
     * @param array<string,mixed> $presentation
     * @return array<string,mixed>
     */
    private function template_context(array $presentation): array {
        global $CFG;
        $emailiconbase = rtrim((string)$CFG->wwwroot, '/')
            . '/local/subscriptions/pix/email/';

        return $presentation + [
            'greeting' => get_string('commerce_mail_greeting', 'local_subscriptions',
                $presentation['customername'] ?: get_string('commerce_mail_customer_fallback', 'local_subscriptions')),
            'reference_label' => get_string('commerce_mail_reference', 'local_subscriptions'),
            'quantity_label' => get_string('commerce_mail_quantity', 'local_subscriptions'),
            'total_label' => get_string('commerce_mail_total', 'local_subscriptions'),
            'payment_label' => get_string('commerce_mail_payment_information', 'local_subscriptions'),
            'provider_label' => get_string('commerce_mail_payment_provider', 'local_subscriptions'),
            'transaction_label' => get_string('commerce_mail_transaction_reference', 'local_subscriptions'),
            'status_label' => get_string('commerce_mail_payment_status', 'local_subscriptions'),
            'access_course_label' => get_string('commerce_mail_access_course', 'local_subscriptions'),
            'download_label' => get_string('commerce_mail_download_file', 'local_subscriptions'),
            'download_desktop_label' => get_string('commerce_mail_download_desktop', 'local_subscriptions'),
            'download_mobile_label' => get_string('commerce_mail_download_mobile', 'local_subscriptions'),
            'bundle_contents_label' => get_string('commerce_mail_bundle_contents', 'local_subscriptions'),
            'course_icon_url' => $emailiconbase . 'graduation-cap-white.png',
            'download_icon_url' => $emailiconbase . 'download-white.png',
            'mobile_icon_url' => $emailiconbase . 'mobile-pink.png',
            'view_product_label' => get_string('commerce_mail_view_product', 'local_subscriptions'),
            'view_purchases_label' => get_string('commerce_mail_view_purchases', 'local_subscriptions'),
            'view_resources_label' => get_string('commerce_mail_view_resources', 'local_subscriptions'),
            'view_courses_label' => get_string('commerce_mail_view_courses', 'local_subscriptions'),
            'receipt_price_before_discounts_label' => get_string(
                'commerce_mail_receipt_price_before_discounts',
                'local_subscriptions'
            ),
            'receipt_discounts_label' => get_string(
                'commerce_mail_receipt_discounts',
                'local_subscriptions'
            ),
            'receipt_total_paid_label' => get_string(
                'commerce_mail_receipt_total_paid',
                'local_subscriptions'
            ),
            'receipt_product_promotions_label' => get_string(
                'commerce_mail_receipt_product_promotions',
                'local_subscriptions'
            ),
            'receipt_trial_discount_label' => get_string(
                'commerce_mail_receipt_trial_discount',
                'local_subscriptions'
            ),
            'receipt_owned_credit_label' => get_string(
                'commerce_mail_receipt_owned_credit',
                'local_subscriptions'
            ),
            'receipt_promo_code_label' => get_string(
                'commerce_mail_receipt_promo_code',
                'local_subscriptions'
            ),
            'receipt_personal_offer_label' => get_string(
                'commerce_mail_receipt_personal_offer',
                'local_subscriptions'
            ),
            'receipt_other_discount_label' => get_string(
                'commerce_mail_receipt_other_discount',
                'local_subscriptions'
            ),
            'receipt_my_campus_label' => get_string(
                'commerce_mail_access_my_campus',
                'local_subscriptions'
            ),
            'receipt_view_order_label' => get_string(
                'commerce_view_order',
                'local_subscriptions'
            ),
            'receipt_external_icon_url' => rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/external-white.png',
            'receipt_order_icon_url' => rtrim((string)$CFG->wwwroot, '/')
                . '/local/subscriptions/pix/email/receipt-pink.png',
            'receipt_course_type_label' => get_string(
                'commerce_product_type_course_access',
                'local_subscriptions'
            ),
            'receipt_digital_type_label' => get_string(
                'commerce_product_type_digital_download',
                'local_subscriptions'
            ),
            'receipt_bundle_type_label' => get_string(
                'commerce_product_type_bundle',
                'local_subscriptions'
            ),
            'receipt_service_type_label' => get_string(
                'commerce_product_type_service',
                'local_subscriptions'
            ),
            'welcome_login_email_label' => get_string(
                'commerce_mail_welcome_login_email',
                'local_subscriptions'
            ),
            'welcome_credentials_heading' => get_string(
                'commerce_mail_welcome_credentials_heading',
                'local_subscriptions'
            ),
            'welcome_activation_explanation' => get_string(
                'commerce_mail_welcome_activation_explanation',
                'local_subscriptions'
            ),
            'welcome_activation_security' => get_string(
                'commerce_mail_welcome_activation_security',
                'local_subscriptions'
            ),
            'welcome_postactivation' => get_string(
                'commerce_mail_welcome_postactivation',
                'local_subscriptions'
            ),
            'welcome_activation_cta' => get_string(
                'commerce_guest_activation_email_cta',
                'local_subscriptions'
            ),
            'welcome_telegram_heading' => get_string(
                'commerce_mail_welcome_telegram_heading',
                'local_subscriptions'
            ),
            'welcome_telegram_intro' => get_string(
                'commerce_mail_welcome_telegram_intro',
                'local_subscriptions'
            ),
            'welcome_telegram_channel' => get_string(
                'commerce_mail_welcome_telegram_channel',
                'local_subscriptions'
            ),
            'welcome_telegram_group' => get_string(
                'commerce_mail_welcome_telegram_group',
                'local_subscriptions'
            ),
            'welcome_forgot_password' => get_string(
                'commerce_mail_welcome_forgot_password',
                'local_subscriptions'
            ),
            'welcome_reset_password' => get_string(
                'commerce_mail_welcome_reset_password',
                'local_subscriptions'
            ),
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    private function render_body(string $template, array $context): string {
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_subscriptions');
        return $renderer->render_from_template('local_subscriptions/commerce/mail/' . $template, $context);
    }
}
