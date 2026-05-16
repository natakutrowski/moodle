<?php
namespace local_subscriptions\digital;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;
use local_subscriptions\payment\dto\InternalEvent;
use stdClass;

final class digital_payment_service {

    public static function on_checkout_completed(InternalEvent $e): void {
        global $DB;

        $pr = null;

        if (!empty($e->payment_request_id)) {
            $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, [
                'id' => (int)$e->payment_request_id,
            ], '*', IGNORE_MISSING);
        }

        if (!$pr && !empty($e->meta['session'])) {
            $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, [
                'sessionid' => $e->meta['session'],
            ], '*', IGNORE_MISSING);
        }

        if (!$pr) {
            return;
        }

        if (in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true)) {
            return;
        }

        $now = time();

        $update = new stdClass();
        $update->id = $pr->id;
        $update->status = Status::PAID;
        $update->payment_date = $now;
        $update->last_update = $now;

        if (empty($pr->download_token)) {
            $update->download_token = product_manager::generate_download_token();
            $update->download_token_expires = null;
        }

        if (empty($pr->transactionid)) {
            $update->transactionid = $e->meta['session']
                ?? $e->meta['orderId']
                ?? $e->meta['payment_intent']
                ?? null;
        }

        $update->response_json = json_encode([
            'event' => 'digital_checkout_completed',
            'provider' => $e->meta['provider'] ?? null,
            'amount_minor' => $e->amount_minor ?? null,
            'currency' => $e->currency ?? null,
            'meta' => $e->meta ?? [],
        ], JSON_UNESCAPED_UNICODE);

        $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, $update);

        $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pr->id], '*', MUST_EXIST);
        $product = product_manager::get_product_by_id((int)$pr->productid, false);

        if (!$product || empty($pr->download_token)) {
            return;
        }

        $lang = $pr->buyer_lang ?? 'ru';
        $translation = product_manager::get_product_translation((int)$pr->productid, $lang);

        if ($product && $translation) {
            $product->localized_title = $translation->title;
        } else if ($product) {
            $product->localized_title = $product->name;
        }

        $downloadurl = (new \moodle_url('/download/pdf/' . $pr->download_token))->out(false);

        $downloadurlmobile = '';
        if (!empty($product->mobile_filename)) {
            $downloadurlmobile = (new \moodle_url('/download/pdf/' . $pr->download_token, [
                'version' => 'mobile',
            ]))->out(false);
        }

        // Mini-lock pour éviter double envoi email accès.
        $prfresh = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pr->id], '*', MUST_EXIST);

        if (empty($prfresh->emailsent)) {
            $DB->set_field(product_manager::TABLE_PAYMENT_REQUEST, 'emailsent', 2, ['id' => $pr->id]);

            try {
                \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_DIGITAL_ACCESS, [
                    'pr' => $prfresh,
                    'product' => $product,
                    'downloadurl' => $downloadurl,
                    'downloadurlmobile' => $downloadurlmobile,
                ]);

                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'emailsent' => 1,
                    'last_update' => time(),
                ]);
            } catch (\Throwable $e) {
                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'emailsent' => 0,
                    'last_error' => '[digital_access_email] ' . $e->getMessage(),
                    'last_update' => time(),
                ]);

                debugging('[digital_product] Access email failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // Mini-lock pour éviter double envoi reçu.
        $prfresh = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, ['id' => $pr->id], '*', MUST_EXIST);

        if (empty($prfresh->receipt_sent)) {
            $DB->set_field(product_manager::TABLE_PAYMENT_REQUEST, 'receipt_sent', 2, ['id' => $pr->id]);

            try {
                \local_subscriptions\mailer::dispatch(\local_subscriptions\mailer::T_DIGITAL_RECEIPT, [
                    'pr' => $prfresh,
                    'product' => $product,
                ]);

                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'receipt_sent' => 1,
                    'last_update' => time(),
                ]);
            } catch (\Throwable $e) {
                $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                    'id' => $pr->id,
                    'receipt_sent' => 0,
                    'last_error' => '[digital_receipt_email] ' . $e->getMessage(),
                    'last_update' => time(),
                ]);

                debugging('[digital_product] Receipt email failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }

    public static function on_payment_failed(InternalEvent $e): void {
        global $DB;

        if (empty($e->payment_request_id)) {
            return;
        }

        $pr = $DB->get_record(product_manager::TABLE_PAYMENT_REQUEST, [
            'id' => (int)$e->payment_request_id,
        ], '*', IGNORE_MISSING);

        if (!$pr || in_array($pr->status ?? '', [Status::PAID, Status::COMPLETED], true)) {
            return;
        }

        $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'id' => $pr->id,
            'status' => Status::FAILED,
            'last_error' => $e->meta['reason'] ?? $e->meta['errorMessage'] ?? 'payment_failed',
            'last_update' => time(),
        ]);
    }
}