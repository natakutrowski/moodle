<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\validation;

defined('MOODLE_INTERNAL') || die();

/** Reports Native price availability independently for each currency. */
final class CommerceProductSellabilityInspector {
    public function __construct(private readonly \moodle_database $db) {}

    /** @return array<int,array{currency:string,configured:bool,active:bool,sellable:bool}> */
    public function inspect(int $productid): array {
        $rows = $this->db->get_records('local_subs_commerce_prod_price', ['productid' => $productid], 'currency ASC');
        $bycurrency = [];
        foreach ($rows as $row) {
            $currency = strtoupper((string)$row->currency);
            $active = (int)$row->active === 1;
            if (!isset($bycurrency[$currency])) {
                $bycurrency[$currency] = ['currency'=>$currency, 'configured'=>true, 'active'=>false, 'sellable'=>false];
            }
            $bycurrency[$currency]['active'] = $bycurrency[$currency]['active'] || $active;
            $bycurrency[$currency]['sellable'] = $bycurrency[$currency]['sellable'] || $active;
        }
        return array_values($bycurrency);
    }

    public function has_any_sellable_price(int $productid): bool {
        foreach ($this->inspect($productid) as $currency) {
            if ($currency['sellable']) { return true; }
        }
        return false;
    }
}
