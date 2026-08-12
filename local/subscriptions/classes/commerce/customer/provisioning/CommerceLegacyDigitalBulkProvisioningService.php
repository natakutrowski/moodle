<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\provisioning;

defined('MOODLE_INTERNAL') || die();

/**
 * Bulk wrapper for Legacy Digital account provisioning.
 */
final class CommerceLegacyDigitalBulkProvisioningService {
    public const MAX_BATCH = 500;

    public function __construct(
        private readonly CommerceLegacyDigitalProvisioningService $service
    ) {
    }

    /**
     * @return CommerceLegacyDigitalProvisioningPlan[]
     */
    public function preview(
        array $emails
    ): array {
        $plans = [];

        foreach ($this->normalise_emails($emails) as $email) {
            $plans[] = $this->service->plan_email($email);
        }

        return $plans;
    }

    /**
     * @param string[] $forcedemails
     * @return CommerceLegacyDigitalProvisioningResult[]
     */
    public function execute(
        array $emails,
        int $actoruserid,
        array $forcedemails = []
    ): array {
        $forced = array_fill_keys(
            $this->normalise_emails($forcedemails),
            true
        );
        $results = [];

        foreach ($this->normalise_emails($emails) as $email) {
            try {
                $results[] = $this->service->execute_email(
                    $email,
                    $actoruserid,
                    isset($forced[$email])
                );
            } catch (\Throwable $exception) {
                $results[] = new CommerceLegacyDigitalProvisioningResult(
                    $email,
                    'error',
                    null,
                    0,
                    0,
                    null,
                    $exception->getMessage()
                );
            }
        }

        return $results;
    }

    /** @return string[] */
    private function normalise_emails(
        array $emails
    ): array {
        $result = [];

        foreach ($emails as $email) {
            $email = \core_text::strtolower(
                trim((string)$email)
            );
            if ($email !== '') {
                $result[$email] = $email;
            }
            if (count($result) >= self::MAX_BATCH) {
                break;
            }
        }

        return array_values($result);
    }
}
