<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\bundle\audit;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\bundle\service\CommerceBundleDomainValidator;
use local_subscriptions\commerce\bundle\service\CommerceBundleReadService;
use local_subscriptions\commerce\producttype\CommerceProductTypeRegistry;

/**
 * Read-only certification audit for the Commerce bundle domain.
 */
final class CommerceBundleDomainAuditor {

    public function __construct(
        private readonly CommerceBundleReadService $bundles,
        private readonly CommerceBundleDomainValidator $validator,
        private readonly CommerceProductTypeRegistry $types
    ) {
    }

    public function audit(): array {
        $report = $this->validator->validate($this->bundles->all());
        $report['registeredtypes'] = array_map(
            static fn($type): string => $type->get_code(),
            $this->types->all()
        );
        $report['bundletyperegistered'] = $this->types->has('bundle');
        $report['certified'] = $report['errors'] === [] && $report['bundletyperegistered'];

        return $report;
    }
}
