<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\editing;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Defines which editor sections are meaningful for one Native Commerce product. */
final class CommerceProductEditorCapabilities {
    public function __construct(private readonly string $type) {
    }

    public static function for_product(CommerceProduct $product): self {
        return new self($product->get_type());
    }

    public function can_edit_identity(): bool {
        return true;
    }

    public function can_edit_prices(): bool {
        return $this->type !== CommerceProductType::BUNDLE;
    }

    public function can_edit_fulfillments(): bool {
        return false;
    }

    public function can_manage_access_scope(): bool {
        return $this->type === CommerceProductType::COURSE_ACCESS;
    }

    public function can_edit_components(): bool {
        return $this->type === CommerceProductType::BUNDLE;
    }

    public function can_preview_bundle(): bool {
        return $this->type === CommerceProductType::BUNDLE;
    }
}
