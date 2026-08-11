<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\digital\library;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable read model for the customer digital library.
 */
final class CommerceDigitalLibrary {
    /**
     * @param CommerceDigitalResourcePresentation[] $resources
     */
    public function __construct(
        private readonly array $resources,
        private readonly int $userid,
        private readonly string $customeremail
    ) {
        if ($userid <= 0) {
            throw new \coding_exception('A digital library requires a valid user id.');
        }
        foreach ($resources as $resource) {
            if (!$resource instanceof CommerceDigitalResourcePresentation) {
                throw new \coding_exception('Digital library resources must use CommerceDigitalResourcePresentation.');
            }
        }
    }

    public function get_resources(): array {
        return $this->resources;
    }

    public function is_empty(): bool {
        return $this->resources === [];
    }

    public function count(): int {
        return count($this->resources);
    }

    public function count_downloadable_resources(): int {
        return count(array_filter(
            $this->resources,
            static fn(CommerceDigitalResourcePresentation $resource): bool => $resource->has_downloads()
        ));
    }

    public function get_userid(): int {
        return $this->userid;
    }

    public function get_customeremail(): string {
        return $this->customeremail;
    }
}
