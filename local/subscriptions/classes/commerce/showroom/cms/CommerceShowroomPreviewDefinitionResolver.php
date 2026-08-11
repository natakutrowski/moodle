<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\CommerceShowroomDefinition;

/**
 * Resolves the current CMS state for an authenticated Builder preview.
 *
 * This class does not grant access by itself. The admin endpoint must enforce
 * login + local/subscriptions:manage_showrooms before calling it.
 */
final class CommerceShowroomPreviewDefinitionResolver {
    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public function require(int $showroomid): CommerceShowroomDefinition {
        $repository = new CommerceShowroomCmsRepository($this->db);
        $record = $repository->get($showroomid);
        if ($record === null) {
            throw new \moodle_exception('invalidrecord');
        }

        return (new CommerceShowroomCmsDefinitionFactory())
            ->create($record);
    }
}
