<?php

namespace local_subscriptions\crm\intelligence\core;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\runtime\CrmComputationContext;
use local_subscriptions\crm\intelligence\runtime\CrmComputationSources;
use local_subscriptions\crm\intelligence\runtime\CrmUserComputationService;

/**
 * Backward-compatible facade for CRM user intelligence computation.
 *
 * Historical consumers may continue using this builder while the actual
 * computation is delegated to the unified runtime service.
 */
final class UserIntelligenceBuilder {

    public function __construct(
        private readonly CrmUserComputationService $computation =
            new CrmUserComputationService()
    ) {
    }

    /**
     * Build complete CRM Intelligence for one Moodle user.
     *
     * @param \stdClass $user Moodle user record.
     * @param bool $withtrend Whether the score trend should be loaded.
     * @param int|null $generatedat Optional stable generation timestamp.
     * @return UserIntelligence
     */
    public function build_for_user(
        \stdClass $user,
        bool $withtrend = true,
        ?int $generatedat = null
    ): UserIntelligence {
        $context =
            CrmComputationContext::create(
                source:
                    CrmComputationSources::
                        LEGACY_BUILDER,
                startedat: $generatedat
            );

        $result =
            $this->computation->compute(
                user: $user,
                context: $context,
                withtrend: $withtrend
            );

        return $result->intelligence;
    }
}