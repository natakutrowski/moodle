<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\domain\CommerceBundle;
use local_subscriptions\commerce\bundle\domain\CommerceBundleCollection;
use local_subscriptions\commerce\bundle\repository\CommerceBundleRepository;

/**
 * Public read boundary for Commerce bundle aggregates.
 */
final class CommerceBundleReadService {

    public function __construct(
        private readonly CommerceBundleRepository $bundles
    ) {
    }

    public function find(string $sku): ?CommerceBundle {
        return $this->bundles->find_by_sku($sku);
    }

    public function require(string $sku): CommerceBundle {
        return $this->find($sku)
            ?? throw new \coding_exception('Unknown Commerce bundle "' . strtoupper(trim($sku)) . '".');
    }

    public function all(): CommerceBundleCollection {
        return $this->bundles->find_all();
    }
}
