<?php
namespace local_subscriptions\digital;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class product_manager {

    public const TABLE_PRODUCT = 'subscription_digital_product';

    public const TABLE_PRODUCT_LANG = 'subscription_digital_product_lang';
    public const TABLE_PAYMENT_REQUEST = 'subscription_digital_payment_request';

    public static function get_product_by_slug(string $slug, bool $onlyenabled = true): ?stdClass {
        global $DB;

        $conditions = ['slug' => $slug];

        if ($onlyenabled) {
            $conditions['enabled'] = 1;
        }

        return $DB->get_record(self::TABLE_PRODUCT, $conditions, '*', IGNORE_MISSING) ?: null;
    }

    public static function get_product_by_id(int $id, bool $onlyenabled = true): ?stdClass {
        global $DB;

        $conditions = ['id' => $id];

        if ($onlyenabled) {
            $conditions['enabled'] = 1;
        }

        return $DB->get_record(self::TABLE_PRODUCT, $conditions, '*', IGNORE_MISSING) ?: null;
    }

    public static function get_price(stdClass $product, string $currency): float {
        $currency = strtoupper($currency);

        if ($currency === 'EUR') {
            return round((float)$product->price_eur, 2);
        }

        if ($currency === 'RUB') {
            return round((float)$product->price_rub, 2);
        }

        throw new \moodle_exception('Unsupported digital product currency: ' . $currency);
    }

    public static function provider_for_currency(string $currency): string {
        $currency = strtoupper($currency);

        if ($currency === 'RUB') {
            return \local_subscriptions\payment\Provider::ALFA;
        }

        return \local_subscriptions\payment\Provider::STRIPE;
    }

    public static function create_payment_request(
        stdClass $product,
        string $email,
        string $firstname,
        string $lastname,
        string $currency,
        string $buyerlang = ''
    ): stdClass {
        global $DB;

        $currency = strtoupper($currency);

        $buyerlang = strtolower(substr($buyerlang, 0, 2));

        if (!in_array($buyerlang, ['fr', 'en', 'ru'], true)) {
            $buyerlang = 'ru';
        }

        if (!in_array($currency, ['EUR', 'RUB'], true)) {
            throw new \moodle_exception('invalid_currency', 'local_subscriptions');
        }

        $price = self::get_price($product, $currency);
        $provider = self::provider_for_currency($currency);

        $now = time();

        $record = new stdClass();
        $record->productid = (int)$product->id;
        $record->email = \core_text::strtolower(trim($email));
        $record->firstname = trim($firstname);
        $record->lastname = trim($lastname);
        $record->currency = $currency;
        $record->price = $price;
        $record->amount_minor = (int)round($price * 100);
        $record->locked_list_price = $price;
        $record->locked_discount_percent = 0;
        $record->locked_discount_amount = 0.00;
        $record->locked_discount_reason = null;
        $record->locked_final_price = $price;
        $record->locked_at = $now;
        $record->payment_provider = $provider;
        $record->status = \local_subscriptions\constants\Status::PENDING;
        $record->created_ip = self::get_client_ip();
        $record->created_useragent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $record->accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        $record->http_referer = $_SERVER['HTTP_REFERER'] ?? null;
        $record->creation_date = $now;
        $record->last_update = $now;
        $record->expiration_date = $now + DAYSECS;
        $record->buyer_lang = $buyerlang;

        $record->id = $DB->insert_record(self::TABLE_PAYMENT_REQUEST, $record);

        return $DB->get_record(self::TABLE_PAYMENT_REQUEST, ['id' => $record->id], '*', MUST_EXIST);
    }

    public static function generate_download_token(): string {
        return bin2hex(random_bytes(32));
    }

    private static function get_client_ip(): ?string {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function get_product_translation(int $productid, ?string $lang = null): ?stdClass {
        global $DB;

        $lang = $lang ?: current_language();
        $lang = strtolower(substr($lang, 0, 2));

        $fallbacks = array_unique([
            $lang,
            'fr',
            'en',
            'ru',
        ]);

        foreach ($fallbacks as $candidate) {
            $translation = $DB->get_record(self::TABLE_PRODUCT_LANG, [
                'productid' => $productid,
                'lang' => $candidate,
            ], '*', IGNORE_MISSING);

            if ($translation) {
                return $translation;
            }
        }

        return null;
    }

    public static function get_localized_product_by_slug(string $slug, ?string $lang = null, bool $onlyenabled = true): ?stdClass {
        $product = self::get_product_by_slug($slug, $onlyenabled);

        if (!$product) {
            return null;
        }

        $translation = self::get_product_translation((int)$product->id, $lang);

        if ($translation) {
            $product->localized_title = $translation->title;
            $product->sales_intro = $translation->sales_intro;
            $product->content_items = $translation->content_items;
            $product->forwho_items = $translation->forwho_items;
            $product->translation_lang = $translation->lang;
            $product->access_note = $translation->access_note ?? '';
            $product->content_title = $translation->content_title ?? '';
            $product->forwho_title = $translation->forwho_title ?? '';
            $product->buy_title = $translation->buy_title ?? '';
        } else {
            $product->localized_title = $product->name;
            $product->sales_intro = '';
            $product->content_items = '';
            $product->forwho_items = '';
            $product->translation_lang = null;
            $product->access_note = '';
            $product->content_title = '';
            $product->forwho_title = '';
            $product->buy_title = '';
        }

        return $product;
    }

    public static function lines_from_text(?string $text): array {
        if (empty($text)) {
            return [];
        }

        $lines = preg_split('/\R/u', trim($text));

        return array_values(array_filter(array_map('trim', $lines), static function ($line) {
            return $line !== '';
        }));
    }

    public static function get_available_products(?string $lang = null): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE_PRODUCT,
            ['enabled' => 1],
            'sortorder ASC, id ASC'
        );

        $products = [];

        foreach ($records as $product) {
            $translation = self::get_product_translation((int)$product->id, $lang);

            if ($translation) {
                $product->localized_title = $translation->title;
                $product->sales_intro = $translation->sales_intro;
                $product->content_items = $translation->content_items;
                $product->forwho_items = $translation->forwho_items;
                $product->translation_lang = $translation->lang;
                $product->access_note = $translation->access_note ?? '';
                $product->content_title = $translation->content_title ?? '';
                $product->forwho_title = $translation->forwho_title ?? '';
                $product->buy_title = $translation->buy_title ?? '';
            } else {
                $product->localized_title = $product->name;
                $product->sales_intro = '';
                $product->content_items = '';
                $product->forwho_items = '';
                $product->translation_lang = null;
                $product->access_note = '';
                $product->content_title = '';
                $product->forwho_title = '';
                $product->buy_title = '';
            }

            $products[] = $product;
        }

        return $products;
    }    

}