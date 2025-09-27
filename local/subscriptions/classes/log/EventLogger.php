<?php
namespace local_subscriptions\log;

final class EventLogger {
    public static function log(int $subid, string $type, ?string $providerid, array $meta=[]): void {
        global $DB;
        if (!$DB->get_manager()->table_exists('subscription_event')) return;
        $rec = (object)[
            'subscriptionid'    =>$subid, 
            'eventtype'         =>$type, 
            'provider_event_id' =>$providerid,
            'occurred_at'       =>time(), 
            'payload_json'      =>json_encode($meta, JSON_UNESCAPED_UNICODE)
        ];
        $DB->insert_record('subscription_event', $rec);
    }
}
