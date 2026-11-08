<?php
// Background compile of non-publishable Lovable drafts only.
declare(strict_types=1);
namespace local_subscriptions\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../commerce/showroom/bridge/CommerceShowroomBridgeCompileService.php');

use local_subscriptions\commerce\showroom\bridge\CommerceShowroomBridgeCompileService as Builder;

final class lovable_build_task extends \core\task\adhoc_task {
    public function execute(): void {
        $payload = $this->get_custom_data();
        Builder::run((int)($payload->userid ?? 0), (string)($payload->key ?? ''), (string)($payload->id ?? ''));
    }
}
