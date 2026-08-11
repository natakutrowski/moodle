<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\compatibility;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use moodle_url;

/** Translates historical purchase URLs to the unified Native CRM UI. */
final class CommerceLegacyPurchaseRedirector {
    public function __construct(private readonly CommercePurchaseReadRepository $repository) {}

    public function list_url(string $family): moodle_url {
        return new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php', ['type' => $family]);
    }

    public function view_url(string $family, int $legacyid): moodle_url {
        $purchase = $this->repository->find_by_legacy($family, $legacyid);
        return $purchase === null
            ? $this->list_url($family)
            : new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $purchase->summary->id]);
    }
}
