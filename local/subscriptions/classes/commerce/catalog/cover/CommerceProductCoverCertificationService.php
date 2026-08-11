<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\cover;

defined('MOODLE_INTERNAL') || die();

/** Read-only structural certification for specialised Commerce product visuals. */
final class CommerceProductCoverCertificationService {
    /** @return array<int,array{status:string,label:string,detail:string}> */
    public function certify(): array {
        global $CFG;

        $findings = [];
        $contexts = CommerceProductCoverContext::all();
        $expected = [
            'storefront',
            'product',
            'recommendation',
            'resources',
            'checkout',
            'email',
            'social',
            'showroom',
        ];

        $findings[] = $this->finding(
            $contexts === $expected ? 'ok' : 'error',
            'Cover contexts',
            count($contexts) . ' specialised contexts registered.'
        );

        $required = [
            'classes/commerce/catalog/cover/CommerceProductCover.php',
            'classes/commerce/catalog/cover/CommerceProductCoverContext.php',
            'classes/commerce/catalog/cover/CommerceProductCoverService.php',
            'admin/commerce/products/assets.php',
        ];
        $missing = [];
        foreach ($required as $relative) {
            if (!is_file($CFG->dirroot . '/local/subscriptions/' . $relative)) {
                $missing[] = $relative;
            }
        }
        $findings[] = $this->finding(
            $missing === [] ? 'ok' : 'error',
            'Required components',
            $missing === []
                ? count($required) . ' required components available.'
                : 'Missing: ' . implode(', ', $missing)
        );

        $assets = $CFG->dirroot . '/local/subscriptions/admin/commerce/products/assets.php';
        $assetssource = is_file($assets) ? (string)file_get_contents($assets) : '';
        $headercount = substr_count($assetssource, '$OUTPUT->header()');
        $findings[] = $this->finding(
            $headercount === 1 ? 'ok' : 'error',
            'Product visual administration',
            $headercount === 1
                ? 'One coherent product visual administration page is rendered.'
                : 'Unexpected renderer header count: ' . $headercount . '.'
        );

        $activation = $CFG->dirroot . '/local/subscriptions/guest_account_activate.php';
        $activationcss = $CFG->dirroot . '/local/subscriptions/styles/guest_account_activation.css';
        $activationsource = is_file($activation) ? (string)file_get_contents($activation) : '';
        $csssource = is_file($activationcss) ? (string)file_get_contents($activationcss) : '';
        $layoutok = str_contains($activationsource, "add_body_class('commerce-guest-activation-page')")
            && str_contains($csssource, '#page-local-subscriptions-guest_account_activate')
            && str_contains($csssource, 'max-width: none !important');
        $findings[] = $this->finding(
            $layoutok ? 'ok' : 'error',
            'Guest activation layout',
            $layoutok
                ? 'The Edly login container width is explicitly neutralised.'
                : 'The theme-safe activation layout contract is incomplete.'
        );

        return $findings;
    }

    /** @param array<int,array{status:string,label:string,detail:string}> $findings */
    public function has_errors(array $findings): bool {
        foreach ($findings as $finding) {
            if ($finding['status'] === 'error') {
                return true;
            }
        }
        return false;
    }

    /** @return array{status:string,label:string,detail:string} */
    private function finding(string $status, string $label, string $detail): array {
        return [
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
        ];
    }
}
