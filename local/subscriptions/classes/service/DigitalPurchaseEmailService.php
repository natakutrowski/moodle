<?php

namespace local_subscriptions\service;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\digital\product_manager;
use local_subscriptions\mailer;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminEvents;

final class DigitalPurchaseEmailService {

    public static function resend_access_email(int $purchaseid): void {
        global $DB;

        $pr = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        $product = product_manager::get_product_by_id((int)$pr->productid, false);

        if (!$product) {
            throw new \moodle_exception('digital_product_not_found', 'local_subscriptions');
        }

        if (empty($pr->download_token)) {
            $pr->download_token = product_manager::generate_download_token();

            $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
                'id' => $pr->id,
                'download_token' => $pr->download_token,
                'download_token_expires' => null,
                'last_update' => time(),
            ]);
        }

        $lang = $pr->buyer_lang ?? 'ru';
        $translation = product_manager::get_product_translation((int)$pr->productid, $lang);

        if ($translation) {
            $product->localized_title = $translation->title;
        } else {
            $product->localized_title = $product->name;
        }

        $downloadurl = (new \moodle_url('/download/pdf/' . $pr->download_token))->out(false);

        $downloadurlmobile = '';
        if (!empty($product->mobile_filename)) {
            $downloadurlmobile = (new \moodle_url('/download/pdf/' . $pr->download_token, [
                'version' => 'mobile',
            ]))->out(false);
        }

        mailer::dispatch(mailer::T_DIGITAL_ACCESS, [
            'pr' => $pr,
            'product' => $product,
            'downloadurl' => $downloadurl,
            'downloadurlmobile' => $downloadurlmobile,
        ]);

        $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'id' => $pr->id,
            'emailsent' => 1,
            'last_update' => time(),
            'last_error' => null,
        ]);

        $targetuserid = !empty($pr->userid) ? (int)$pr->userid : 0;

        if ($targetuserid <= 0 && !empty($pr->email)) {
            $targetuserid = (int)$DB->get_field('user', 'id', [
                'email' => $pr->email,
                'deleted' => 0,
            ], IGNORE_MISSING);
        }

        self::log_digital_action(
            AdminEvents::DIGITAL_LINK_RESENT,
            $pr,
            [
                'email' => $pr->email ?? '',
                'productid' => $pr->productid ?? 0,
                'purchaseid' => $pr->id,
            ]
        );
    }

    public static function regenerate_token(int $purchaseid): void {
        global $DB;

        $pr = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        $oldtoken = $pr->download_token ?? '';

        $newtoken = product_manager::generate_download_token();

        $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'id' => $pr->id,
            'download_token' => $newtoken,
            'download_token_expires' => time() + 30 * DAYSECS,
            'last_update' => time(),
        ]);

        self::log_digital_action(
            AdminEvents::DIGITAL_TOKEN_REGENERATED,
            $pr,
            [
                'email' => $pr->email ?? '',
                'productid' => $pr->productid ?? 0,
                'purchaseid' => $pr->id,
                'oldtoken' => $oldtoken ? substr($oldtoken, 0, 8) . '…' : '-',
            ]
        );
    }

    public static function extend_token(int $purchaseid, int $days = 30): void {
        global $DB;

        $pr = $DB->get_record(
            product_manager::TABLE_PAYMENT_REQUEST,
            ['id' => $purchaseid],
            '*',
            MUST_EXIST
        );

        if (empty($pr->download_token)) {
            $pr->download_token = product_manager::generate_download_token();
        }

        $base = !empty($pr->download_token_expires) && (int)$pr->download_token_expires > time()
            ? (int)$pr->download_token_expires
            : time();

        $expires = $base + $days * DAYSECS;

        $DB->update_record(product_manager::TABLE_PAYMENT_REQUEST, (object)[
            'id' => $pr->id,
            'download_token' => $pr->download_token,
            'download_token_expires' => $expires,
            'last_update' => time(),
        ]);

        self::log_digital_action(
            AdminEvents::DIGITAL_TOKEN_EXTENDED,
            $pr,
            [
                'email' => $pr->email ?? '',
                'productid' => $pr->productid ?? 0,
                'purchaseid' => $pr->id,
                'expires' => \local_subscriptions\admin\AdminFormatter::datetime($expires),
            ]
        );
    }

    private static function log_digital_action(string $event, \stdClass $pr, array $details = []): void {
        global $DB;

        $targetuserid = !empty($pr->userid) ? (int)$pr->userid : 0;

        if ($targetuserid <= 0 && !empty($pr->email)) {
            $targetuserid = (int)$DB->get_field_sql("
                SELECT id
                FROM {user}
                WHERE deleted = 0
                AND " . $DB->sql_compare_text('email') . " = " . $DB->sql_compare_text(':email') . "
            ORDER BY id DESC
            ", [
                'email' => $pr->email,
            ], IGNORE_MISSING);
        }

        AdminLog::log(
            $event,
            $targetuserid > 0 ? $targetuserid : null,
            'digital_purchase',
            (int)$pr->id,
            $details
        );
    }


}