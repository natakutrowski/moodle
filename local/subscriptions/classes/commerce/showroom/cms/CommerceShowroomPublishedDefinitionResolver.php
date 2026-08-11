<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;

/**
 * Resolves a public Showroom exclusively from published CMS state.
 *
 * The Registry is an optional compatibility fallback for legacy fields; it is
 * never an authorization requirement and never makes a showroom public.
 */
final class CommerceShowroomPublishedDefinitionResolver {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public function require(string $showroomkey): CommerceShowroomDefinition {
        $repository = new CommerceShowroomCmsRepository($this->db);
        $record = $repository->get_by_key($showroomkey);

        if (
            $record === null
            || (string)$record->status
                !== CommerceShowroomStatus::PUBLISHED
            || !$this->has_enabled_blocks($repository, $record)
        ) {
            throw new \moodle_exception(
                'commerce_showroom_not_found',
                'local_subscriptions'
            );
        }

        return (new CommerceShowroomCmsDefinitionFactory())->create(
            $record,
            CommerceShowroomRegistry::get($showroomkey)
        );
    }

    private function has_enabled_blocks(
        CommerceShowroomCmsRepository $repository,
        \stdClass $record
    ): bool {
        foreach ($repository->blocks((int)$record->id) as $block) {
            if ((int)$block->enabled === 1) {
                return true;
            }
        }

        return false;
    }
}
