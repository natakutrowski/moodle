<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\course\recommendation;

defined('MOODLE_INTERNAL') || die();

/** Public-safe recommendation displayed outside the Commerce plugin. */
final class CommerceCourseRecommendationPresentation {
    public function __construct(
        public readonly string $sku,
        public readonly string $type,
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $imageurl,
        public readonly string $producturl,
        public readonly string $priceformatted,
        public readonly ?string $comparepriceformatted,
        public readonly ?int $discountpercentage,
        public readonly bool $upgrade,
        public readonly ?string $upgradepriceformatted = null,
        public readonly ?string $upgradefromlabel = null,
        public readonly ?string $upgradetolabel = null,
        public readonly bool $trialoffer = false,
        public readonly ?string $trialpriceformatted = null,
        public readonly ?string $trialcomparepriceformatted = null,
        public readonly ?int $trialdiscountpercentage = null,
        public readonly ?string $upgradecomparepriceformatted = null,
        public readonly ?int $upgradediscountpercentage = null,
        public readonly ?string $upgradesavingformatted = null
    ) {
        if (trim($sku) === '' || trim($title) === '' || trim($producturl) === '') {
            throw new \coding_exception('A course recommendation requires a SKU, title and product URL.');
        }
    }

    /** @return array<string, mixed> */
    public function to_array(): array {
        $upgradeprice = trim((string)$this->upgradepriceformatted);
        $upgradefrom = trim((string)$this->upgradefromlabel);
        $upgradeto = trim((string)$this->upgradetolabel);
        $trialprice = trim((string)$this->trialpriceformatted);
        $trialcompare = trim((string)$this->trialcomparepriceformatted);
        $upgradecompare = trim((string)$this->upgradecomparepriceformatted);
        $upgradesaving = trim((string)$this->upgradesavingformatted);

        return [
            'sku' => $this->sku,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'hasdescription' => $this->description !== '',
            'imageurl' => $this->imageurl ?? '',
            'hasimage' => $this->imageurl !== null && $this->imageurl !== '',
            'producturl' => $this->producturl,
            'priceformatted' => $this->priceformatted,
            'hasprice' => $this->priceformatted !== '',
            'comparepriceformatted' => $this->comparepriceformatted ?? '',
            'hascompareprice' => $this->comparepriceformatted !== null && $this->comparepriceformatted !== '',
            'discountpercentage' => $this->discountpercentage,
            'hasdiscount' => $this->discountpercentage !== null,
            'upgrade' => $this->upgrade,
            'upgradepriceformatted' => $upgradeprice,
            'hasupgradeprice' => $this->upgrade && $upgradeprice !== '',
            'upgradefromlabel' => $upgradefrom,
            'upgradetolabel' => $upgradeto,
            'hasupgradepath' => $this->upgrade && $upgradefrom !== '' && $upgradeto !== '',
            'trialoffer' => $this->trialoffer,
            'trialpriceformatted' => $trialprice,
            'hastrialprice' => $this->trialoffer && $trialprice !== '',
            'trialcomparepriceformatted' => $trialcompare,
            'hastrialcompareprice' => $this->trialoffer && $trialcompare !== '',
            'trialdiscountpercentage' => $this->trialdiscountpercentage,
            'upgradecomparepriceformatted' => $upgradecompare,
            'hasupgradecompareprice' => $this->upgrade
                && $upgradecompare !== '',
            'upgradediscountpercentage' => $this->upgradediscountpercentage,
            'hasupgradediscount' => $this->upgrade
                && $this->upgradediscountpercentage !== null
                && $this->upgradediscountpercentage > 0,
            'upgradesavingformatted' => $upgradesaving,
            'hasupgradesaving' => $this->upgrade
                && $upgradesaving !== '',
        ];
    }
}
