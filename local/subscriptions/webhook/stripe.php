<?php
declare(strict_types=1);
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);
require_once dirname(__DIR__,3).'/config.php';
use local_subscriptions\payment\EventRouter;
use local_subscriptions\payment\stripe\webhook\StripeWebhookVerifier;
$payload=(string)file_get_contents('php://input');
$headers=function_exists('getallheaders')?(array)getallheaders():[];
$profile=optional_param('profile','',PARAM_ALPHANUMEXT);
header('Content-Type: text/plain; charset=utf-8');
try {
    $verified=(new StripeWebhookVerifier())->verify($payload,$headers,$profile!==''?$profile:null);
    if($verified->event->type!=='payment_pending') EventRouter::handle($verified->event);
    error_log('[local_subscriptions][stripe_webhook] '.json_encode([
        'result'=>$verified->event->type==='payment_pending'?'acknowledged_pending':'processed',
        'profile'=>$verified->profile,'event_type'=>$verified->event->type,
        'session'=>$verified->event->meta['session']??null,'event_id'=>$verified->event->meta['event_id']??null,
    ],JSON_UNESCAPED_SLASHES));
    http_response_code(200); echo 'ok';
} catch(\Stripe\Exception\SignatureVerificationException|\UnexpectedValueException $e) {
    error_log('[local_subscriptions][stripe_webhook] '.json_encode(['result'=>'invalid_signature_or_payload','message'=>$e->getMessage()],JSON_UNESCAPED_SLASHES));
    http_response_code(400); echo 'invalid webhook';
} catch(\Throwable $e) {
    error_log('[local_subscriptions][stripe_webhook] '.json_encode(['result'=>'processing_error','exception'=>get_class($e),'message'=>$e->getMessage()],JSON_UNESCAPED_SLASHES));
    http_response_code(500); echo 'processing error';
}
