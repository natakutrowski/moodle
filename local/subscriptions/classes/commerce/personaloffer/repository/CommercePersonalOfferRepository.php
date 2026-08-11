<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;

interface CommercePersonalOfferRepository {
    public function get_by_id(int $id): ?CommercePersonalOffer;
    public function get_by_uuid(string $offeruuid): ?CommercePersonalOffer;
    public function save(CommercePersonalOffer $offer): CommercePersonalOffer;
    public function count(array $filters = []): int;
    /** @return CommercePersonalOffer[] */
    public function find(array $filters = [], int $limit = 100, int $offset = 0): array;
}
