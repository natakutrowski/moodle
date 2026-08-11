<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_purchases;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Output model for the "Mes achats" page.
 *
 * The page model now only exposes template data. The preserved presentation is
 * delegated to a dedicated transition renderer until the component templates
 * are introduced progressively during I4.
 */
final class MyPurchasesPage implements renderable, templatable {
    public function __construct(
        private readonly \stdClass $targetuser,
        private readonly bool $isadminview,
        private readonly MyPurchasesFilter $filter
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        $presentation = new CurrentPresentationRenderer(
            $this->targetuser,
            $this->isadminview,
            $this->filter
        );

        return [
            'content' => $presentation->render($output),
            'hasfilters' => $this->filter->has_active_filters(),
        ];
    }
}
