<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\storefront\recommendation;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\storefront\presentation\CommerceStorefrontPresenter;
use local_subscriptions\commerce\storefront\repository\CommerceStorefrontRepository;

/** Builds recommendation cards only from explicitly configured Native SKUs. */
final class CommerceStorefrontRecommendationService {
    public function __construct(private readonly CommerceStorefrontRepository $repository) {}
    public function cards(array $skus, string $language, string $currency): array {
        $cards=[];
        foreach ($skus as $sku) {
            $product=$this->repository->find_by_sku($sku, $language, $currency);
            if ($product !== null && !$product->is_owned()) { $cards[]=CommerceStorefrontPresenter::card($product, $currency, 'recommendation'); }
        }
        return $cards;
    }
}
