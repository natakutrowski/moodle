<?php
namespace local_subscriptions\task;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;
final class process_personal_offer_scheduled_campaigns_task extends \core\task\scheduled_task {
    public function get_name(): string { return 'Personal Offer scheduled campaigns'; }
    public function execute(): void {
        global $DB;
        $now = time();
        $campaigns = $DB->get_records_select('local_subs_commerce_offer_campaign', 'status = :status AND scheduledat IS NOT NULL AND scheduledat <= :now', ['status'=>'snapshot','now'=>$now], 'scheduledat ASC');
        foreach ($campaigns as $campaign) {
            try {
                $userid = (int)($campaign->scheduledby ?: $campaign->usermodified ?: $campaign->usercreated ?: 0);
                CommercePersonalOfferCampaignManager::create($DB)->generate((int)$campaign->id, $userid);
                $DB->set_field('local_subs_commerce_offer_campaign', 'startedat', time(), ['id'=>(int)$campaign->id]);
                CommercePersonalOfferMailService::create($DB)->queue_missing_campaign((int)$campaign->id);
                mtrace('Personal Offer campaign #' . $campaign->id . ' started.');
            } catch (\Throwable $e) { mtrace('Personal Offer campaign #' . $campaign->id . ' failed to start: ' . $e->getMessage()); }
        }
    }
}
