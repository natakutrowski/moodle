<?php

namespace local_subscriptions\commerce\purchase\digital;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only source of digital product information.
 */
interface DigitalProductRepository {

    public function find(
        int $productid
    ): ?DigitalProductDescriptor;
}