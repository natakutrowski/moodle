<?php

namespace local_subscriptions\output\myprofile;

use renderable;
use templatable;
use renderer_base;

class subscriptions_node implements renderable, templatable {

    public array $subscriptions;

    public function __construct(array $subscriptions) {
        $this->subscriptions = $subscriptions;
    }

    public function export_for_template(renderer_base $output): array {
        $items = [];
        foreach ($this->subscriptions as $sub) {
            $items[] = [
                'planname' => format_string($sub->planname),
                'enddate' => userdate($sub->end_date, get_string('strftimedate', 'langconfig')),
            ];
        }
        return ['subscriptions' => $items];
    }
}
