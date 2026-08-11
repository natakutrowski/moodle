<?php

declare(strict_types=1);

namespace local_subscriptions\output\my_digital_products;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\digital\library\CommerceDigitalLibrary;
use renderable;
use renderer_base;
use templatable;

/**
 * Output model for the customer digital library.
 */
final class MyDigitalProductsPage implements renderable, templatable {
    public function __construct(
        private readonly CommerceDigitalLibrary $library,
        private readonly \stdClass $targetuser,
        private readonly bool $isadminview
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        $presentation = new CurrentPresentationRenderer($this->library, $this->isadminview);
        $data = $presentation->export($output);
        $data['pagetitle'] = $this->isadminview
            ? get_string('digital_library_user_title', 'local_subscriptions', fullname($this->targetuser))
            : get_string('digital_library_title', 'local_subscriptions');
        $data['pagesubtitle'] = get_string('digital_library_subtitle', 'local_subscriptions');
        return $data;
    }
}
