<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\experience;

defined('MOODLE_INTERNAL') || die();

/** Immutable public Storefront experience configuration. */
final class CommerceStorefrontExperience {
    /** @param string[] $trustitems @param array<int,array{value:string,label:string}> $quickfacts */
    public function __construct(
        private readonly string $group,
        private readonly array $trustitems,
        private readonly array $quickfacts
    ) {
    }

    public function get_group(): string { return $this->group; }
    /** @return string[] */
    public function get_trust_items(): array { return $this->trustitems; }
    /** @return array<int,array{value:string,label:string}> */
    public function get_quick_facts(): array { return $this->quickfacts; }
}
