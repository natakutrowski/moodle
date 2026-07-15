<?php

namespace local_subscriptions\crm\inbox\ai\cache;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;

final class InboxAiCacheKeyBuilder {

    public function build(
        InboxAiRequest $request,
        string $providerkey,
        string $promptversion
    ): string {
        $inputhash = $request->input_hash(
            $promptversion
        );

        return hash(
            'sha256',
            implode('|', [
                'crm-inbox-ai',
                $request->capability,
                $providerkey,
                $promptversion,
                $inputhash,
            ])
        );
    }
}