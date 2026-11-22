<?php
/** CampusFR D2.17.3.2 - Moodle cron executes confirmed DEV QA import. */
declare(strict_types=1);
namespace local_subscriptions\task;
defined('MOODLE_INTERNAL') || die();

final class reader_unified_qa_import extends \core\task\adhoc_task {
    public function execute(): void {
        $data = $this->get_custom_data();
        $id = is_object($data) ? ($data->jobid ?? '') : '';
        if (!is_string($id) || preg_match('/\A[a-f0-9]{32}\z/D', $id) !== 1) {
            throw new \RuntimeException('Identifiant du job QA invalide');
        }
        \local_subscriptions\crm\library\ReaderUnifiedQAImportService::process($id);
    }
}
