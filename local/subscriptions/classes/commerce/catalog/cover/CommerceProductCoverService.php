<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\cover;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;

/** Resolves one specialised product visual with backwards-compatible fallbacks. */
final class CommerceProductCoverService {
    public function __construct(
        private readonly CommerceCatalogMediaManager $media
    ) {
    }

    public static function create(): self {
        return new self(new CommerceCatalogMediaManager(\context_system::instance()));
    }

    public function resolve(int $productid, string $context, ?string $legacyurl = null): CommerceProductCover {
        $context = CommerceProductCoverContext::require_valid($context);
        foreach ($this->fallback_chain($context) as $role) {
            $url = $this->media->get_url($productid, $role);
            if ($url !== null) {
                return new CommerceProductCover($context, $url->out(false), $role, $role !== $context);
            }
        }
        $legacyurl = trim((string)$legacyurl);
        return new CommerceProductCover(
            $context,
            $legacyurl !== '' ? $legacyurl : null,
            $legacyurl !== '' ? 'legacy' : null,
            $legacyurl !== ''
        );
    }

    /** @return array<string,CommerceProductCover> */
    public function resolve_all(int $productid, ?string $legacyurl = null): array {
        $result = [];
        foreach (CommerceProductCoverContext::all() as $context) {
            $result[$context] = $this->resolve($productid, $context, $legacyurl);
        }
        return $result;
    }

    /** Reserved API for a future controlled image derivative generator. */
    public function generate_missing_covers(int $productid): array {
        return [];
    }

    /** @return string[] */
    private function fallback_chain(string $context): array {
        return match ($context) {
            CommerceProductCoverContext::PRODUCT => [
                'product', 'social', 'email', 'storefront', 'cover',
            ],
            CommerceProductCoverContext::RECOMMENDATION => [
                'storefront', 'recommendation', 'cover',
            ],
            CommerceProductCoverContext::RESOURCES => [
                'resources', 'storefront', 'cover',
            ],
            CommerceProductCoverContext::CHECKOUT => [
                'checkout', 'storefront', 'cover',
            ],
            CommerceProductCoverContext::EMAIL => [
                'product', 'email', 'checkout', 'storefront', 'cover',
            ],
            CommerceProductCoverContext::SOCIAL => [
                'product', 'social', 'storefront', 'cover',
            ],
            CommerceProductCoverContext::STOREFRONT => [
                'storefront', 'recommendation', 'cover',
            ],
            CommerceProductCoverContext::SHOWROOM => [
                'showroom', 'product', 'storefront', 'cover',
            ],
            default => ['cover'],
        };
    }
}
