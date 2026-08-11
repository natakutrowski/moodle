<?php
require_once(__DIR__ . '/../../../../../config.php');
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$campaignkey = trim(optional_param('campaignkey', '', PARAM_TEXT));
$repo = new MoodleCommercePersonalOfferRepository($DB); $admin = new CommercePersonalOfferAdminService($DB);
$filters = $campaignkey === '' ? [] : ['campaignkey'=>$campaignkey]; $offers=$repo->find($filters, 100000, 0);
$filename='personal-offers-' . ($campaignkey!=='' ? preg_replace('/[^a-zA-Z0-9_-]+/','-', $campaignkey) : 'all') . '-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="'.$filename.'"');
$out=fopen('php://output','wb'); fwrite($out, "\xEF\xBB\xBF"); fputcsv($out,['offer_id','offer_uuid','campaign','email','userid','status','source_purchase_id','target_product_id','valid_from','expires_at','redeemed_purchase_id','personal_offer_url']);
foreach($offers as $offer){$url=$admin->secure_url($offer); fputcsv($out,[$offer->get_id(),$offer->get_offer_uuid(),$offer->get_campaign_key(),$offer->get_beneficiary_email(),$offer->get_beneficiary_user_id(),$offer->get_effective_status(time()),$offer->get_source_purchase_id(),$offer->get_target_product_id(),$offer->get_valid_from(),$offer->get_expires_at(),$offer->get_redeemed_purchase_id(),$url?->out(false) ?? '']);} fclose($out); exit;
