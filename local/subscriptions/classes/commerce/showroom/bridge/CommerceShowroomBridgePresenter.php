<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\bridge;

defined('MOODLE_INTERNAL') || die();

// X1.3.1: Protect browser rendering from stale Moodle autoload class maps.
require_once(__DIR__ . '/CommerceShowroomBridgeVideoService.php');
// DEV R1: avoid stale Moodle class-map lookups for the new, narrowly scoped gate.
require_once(__DIR__ . '/CommerceShowroomDevCheckoutTestGate.php');

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\legal\CommerceShowroomLegalFooterPresenter;

/** Public payload is an explicit allowlist; payment is never decided by React. */
final class CommerceShowroomBridgePresenter {
    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function present(
        CommerceShowroomDefinition $definition,
        \moodle_database $db,
        array $data,
        ?int $previewid = null,
        bool $checkouttest = false
    ): array {
        global $PAGE, $CFG;
        $repo = new CommerceShowroomCmsRepository($db);
        $record = $previewid !== null
            ? $repo->get($previewid)
            : $repo->get_by_key($definition->get_key());
        if ($record === null) {
            throw new \invalid_parameter_exception('Bridge requires a CMS showroom record.');
        }
        $settings = CommerceShowroomBridgeSettings::from_json((string)$record->settingsjson);
        $id = $settings['id'];
        $manifest = CommerceShowroomBridgePackageRegistry::require_manifest($id);
        $base = CommerceShowroomBridgePackageRegistry::base_directory($id);
        // The *only* exception to read-only preview: an expressly requested,
        // authenticated admin preview on exact DEV #8 with sandbox gateways.
        if ($checkouttest) {
            if ($previewid !== 8 || !has_capability('moodle/site:config', \context_system::instance())
                || !CommerceShowroomDevCheckoutTestGate::eligible(
                    (int)$record->id,
                    (string)$record->status,
                    $definition->get_key(),
                    $definition->get_template(),
                    (string)$CFG->wwwroot,
                    $id,
                    (string)$manifest['version']
                )) {
                throw new \coding_exception('DEV Commerce checkout preview authorization denied.');
            }
            CommerceShowroomDevCheckoutTestGate::require_sandbox();
        }
        // A normal published #8 may be used only for the DEV test-account pilot.
        // This is separate from the administrator-only draft preview gate.
        $publicpilot = false;
        if ($previewid === null
            && rtrim((string)$CFG->wwwroot, '/') === CommerceShowroomDevCheckoutTestGate::DEV_ROOT
            && $definition->get_key() === CommerceShowroomDevCheckoutTestGate::KEY) {
            // P2M: use the SAME CMS fields and gate as the successful CLI publication
            // verification. Do not independently re-check the Moodle login state here:
            // showroom.php already calls require_login() BEFORE invoking this presenter.
            // An extra session check here caused a public 500 after publication.
            $publicpilot = CommerceShowroomDevCheckoutTestGate::eligible_published_pilot(
                (int)$record->id,
                (string)$record->status,
                (string)$record->showroomkey,
                (string)$record->template,
                (string)$CFG->wwwroot,
                $id,
                (string)$manifest['version'],
                [
                    'slugfr' => (string)$record->slugfr,
                    'slugen' => (string)$record->slugen,
                    'slugru' => (string)$record->slugru,
                ]
            );
            if (!$publicpilot) {
                // Public requests get a normal 404, not a coding exception with internals.
                throw new \moodle_exception('commerce_showroom_not_found', 'local_subscriptions');
            }
            CommerceShowroomDevCheckoutTestGate::require_sandbox();
        }
        $language = strtolower(explode('_', str_replace('-', '_', current_language()))[0]);
        if (!in_array($language, CommerceShowroomBridgeSettings::LANGUAGES, true)) {
            $language = 'ru';
        }
        // RU canonical -> trusted FR/EN package -> editable CMS overrides.
        $copy = CommerceShowroomBridgeTextCatalog::load(
            $base, $manifest['content'], $language, $settings['content'][$language] ?? []
        );
        $offers = CommerceShowroomBridgeOfferContract::expose((array)($data['offers'] ?? []));
        $policyurls = \local_subscriptions\support\Region::policyUrls();
        $invoiceprofile = (new \local_subscriptions\commerce\order\invoice\CommerceInvoiceProfileResolver())
            ->resolve((string)($data['currency'] ?? 'EUR'), null);
        // P2E: Shared Commerce legal resolver. The Bridge payload keeps stable metadata
        // for native currency-refresh invariants; Moodle renders the only visible footer.
        $designlegal = null;
        $designlinks = null;
        // Legal presentation is portable across CMS IDs and imported keys.
        // Only this trusted design opts into the Articles / Alf footer data.
        if ($id === 'articles_alf_reimport') {
            $resolvedlegal = (new CommerceShowroomLegalFooterPresenter())
                ->resolve((string)($data['currency'] ?? ''), $language);
            $designlegal = [
                'name' => $resolvedlegal['fields']['name'],
                'address' => $resolvedlegal['fields']['address'],
                'email' => $resolvedlegal['fields']['email'],
                'website' => $resolvedlegal['fields']['website'],
            ];
            $designlinks = $resolvedlegal['urls'];
        }
        $payload = [
            'api' => 'campusfr-showroom-bridge/v1',
            'design' => ['id' => $id, 'version' => $manifest['version']],
            'showroom' => ['key' => $definition->get_key(), 'language' => $language, 'currency' => $data['currency'] ?? '',
                'ispreview' => $previewid !== null && !$checkouttest,
                'ischeckouttest' => $checkouttest,
                'isdevpublishedpilot' => $publicpilot],
            'content' => $copy,
            'media' => ['presentationVideoUrl' => (new CommerceShowroomBridgeVideoService(\context_system::instance()))
                ->get_url((int)$record->id)?->out(false) ?? $settings['videoUrl']],
            'commerce' => [
                'offers' => $offers,
                'currency' => $data['currency'] ?? '',
                'currencies' => CommerceShowroomBridgeCurrencyOptions::from_showroom_page(
                    (array)($data['currencies'] ?? []),
                    (string)($data['currency'] ?? ''),
                    $PAGE->url
                ),
            ],
            'legal' => $designlegal ?? [
                'name' => (string)($invoiceprofile['name'] ?? ''),
                'address' => (string)($invoiceprofile['address'] ?? ''),
                'email' => (string)($invoiceprofile['email'] ?? ''),
                'website' => (string)($invoiceprofile['website'] ?? ''),
            ],
            'links' => [
                'shop' => (string)($data['shopurl'] ?? ''),
                'cart' => (string)($data['carturl'] ?? ''),
                'support' => (string)($data['supporturl'] ?? 'mailto:support@campusfr.fr'),
                'privacy' => $designlinks['privacy'] ?? (string)($policyurls['policy'] ?? ''),
                'terms' => $designlinks['terms'] ?? (string)($policyurls['terms'] ?? ''),
                'offer' => $designlinks['offer'] ?? (string)($policyurls['offer'] ?? ''),
            ],
        ];
        $safejson = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
        return [
            'bridgeid' => $id,
            'bridgeversion' => $manifest['version'],
            'bridgescripturl' => CommerceShowroomBridgePackageRegistry::asset_url($id, $manifest['entry'], $manifest['version']),
            'bridgecssurl' => CommerceShowroomBridgePackageRegistry::asset_url($id, $manifest['css'], $manifest['version']),
            'bridgepayloadjson' => $safejson,
            'bridgefallbacktitle' => (string)($copy['hero']['title1'] ?? 'CampusFR'),
            'bridgefallbackdescription' => (string)($copy['hero']['description'] ?? ''),
        ];
    }
}
