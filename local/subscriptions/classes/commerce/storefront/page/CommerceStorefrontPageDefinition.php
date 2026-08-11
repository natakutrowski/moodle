<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\page;

defined('MOODLE_INTERNAL') || die();

/** Immutable editorial composition for one public product page. */
final class CommerceStorefrontPageDefinition {
    /**
     * @param array<int, array<string, mixed>> $sections
     */
    public function __construct(
        private readonly string $template,
        private readonly array $sections,
        private readonly string $theme = 'default',
        private readonly int $schemaVersion = 1,
        private readonly string $layout = 'standard',
        private readonly string $commercePosition = 'sidebar_sticky',
        private readonly string $shellMode = 'standard',
        private readonly bool $showHeader = true,
        private readonly bool $showFooter = true,
        private readonly string $productHeaderMode = 'automatic'
    ) {
    }

    public function get_template(): string {
        return $this->template;
    }

    /** @return array<int, array<string, mixed>> */
    public function get_sections(): array {
        return $this->sections;
    }

    public function get_theme(): string {
        return $this->theme;
    }

    public function get_schema_version(): int {
        return $this->schemaVersion;
    }

    public function get_layout(): string {
        return $this->layout;
    }

    public function get_commerce_position(): string {
        return $this->commercePosition;
    }

    public function get_shell_mode(): string {
        return $this->shellMode;
    }

    public function show_header(): bool {
        return $this->showHeader;
    }

    public function show_footer(): bool {
        return $this->showFooter;
    }

    public function get_product_header_mode(): string {
        return $this->productHeaderMode;
    }
}
