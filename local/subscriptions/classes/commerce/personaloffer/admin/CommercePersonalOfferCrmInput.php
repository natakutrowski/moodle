<?php
namespace local_subscriptions\commerce\personaloffer\admin;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
final class CommercePersonalOfferCrmInput {
    public static function terms(string $strategy,string $amounts,int $percent): CommercePersonalOfferTerms {
        if ($strategy===CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT) return CommercePersonalOfferTerms::percentage_discount($percent*100);
        $out=[]; foreach(array_filter(array_map('trim',explode(',',$amounts))) as $part){[$cur,$value]=array_pad(explode(':',$part,2),2,null); if(!$cur||$value===null||!ctype_digit($value))throw new \coding_exception('Amounts must use EUR:3000,RUB:299000 format.');$out[strtoupper($cur)]=(int)$value;}
        return $strategy===CommercePersonalOfferTerms::STRATEGY_FIXED_DISCOUNT?CommercePersonalOfferTerms::fixed_discount($out):CommercePersonalOfferTerms::fixed_price($out);
    }
    public static function amounts_from_major(array $values): string {
        $parts = [];
        foreach ($values as $currency => $value) {
            $value = trim((string)$value);
            if ($value === '') { continue; }
            $normalized = str_replace(',', '.', $value);
            if (!is_numeric($normalized) || (float)$normalized < 0) {
                throw new \coding_exception('Invalid monetary amount.');
            }
            $parts[] = strtoupper((string)$currency) . ':' . (string)(int)round(((float)$normalized) * 100);
        }
        return implode(',', $parts);
    }

    public static function resolve_purchase_id(\moodle_database $db, string $value): ?int {
        $value = trim($value);
        if ($value === '') { return null; }
        if (ctype_digit($value)) {
            return $db->record_exists('local_subscriptions_commerce_purchase', ['id' => (int)$value]) ? (int)$value : null;
        }
        $purchase = $db->get_record('local_subscriptions_commerce_purchase', ['reference' => $value], 'id', IGNORE_MISSING);
        return $purchase ? (int)$purchase->id : null;
    }

    /** Resolve a CRM beneficiary field accepting email or "Firstname Lastname <email>". */
    public static function resolve_beneficiary_email(\moodle_database $db, string $value): string {
        $value = trim($value);
        if (preg_match('/<([^>]+)>$/', $value, $m) === 1 && validate_email(trim($m[1]))) {
            return strtolower(trim($m[1]));
        }
        if (validate_email($value)) {
            return strtolower($value);
        }
        $needle = trim(core_text::strtolower($value));
        if ($needle === '') { throw new \coding_exception('A beneficiary email or customer name is required.'); }
        $users = $db->get_records_select('user', 'deleted = 0', [], 'id ASC', 'id,firstname,lastname,email');
        $matches = [];
        foreach ($users as $user) {
            $fullname = trim(core_text::strtolower((string)$user->firstname . ' ' . (string)$user->lastname));
            if ($fullname === $needle && validate_email((string)$user->email)) { $matches[] = strtolower((string)$user->email); }
        }
        $matches = array_values(array_unique($matches));
        if (count($matches) !== 1) { throw new \coding_exception('Customer name must resolve to exactly one Moodle account.'); }
        return $matches[0];
    }

    /** Resolve product ownership to the most recent paid purchase proving eligibility. */
    public static function resolve_purchase_for_product(\moodle_database $db, string $email, ?int $userid, int $productid): ?int {
        if ($productid <= 0) { return null; }
        $product = $db->get_record('local_subs_commerce_product', ['id' => $productid], 'id,sku', MUST_EXIST);
        $identity = []; $params = ['sku' => strtoupper((string)$product->sku)];
        if ($userid !== null && $userid > 0) { $identity[] = 'p.userid = :userid'; $params['userid'] = $userid; }
        if (validate_email($email)) { $identity[] = $db->sql_equal('p.customeremail', ':email', false, false); $params['email'] = strtolower($email); }
        if ($identity === []) { return null; }
        $statuses = ['paid','completed','captured','succeeded','success']; $holders = [];
        foreach ($statuses as $i => $status) { $key='paid'.$i; $params[$key]=$status; $holders[]=':'.$key; }
        $sql = 'SELECT p.id FROM {local_subscriptions_commerce_purchase} p'
            . ' JOIN {local_subscriptions_commerce_purchase_item} i ON i.purchaseid = p.id'
            . ' WHERE (' . implode(' OR ', $identity) . ')'
            . ' AND UPPER(i.itemreference) = :sku'
            . ' AND EXISTS (SELECT 1 FROM {local_subscriptions_commerce_payment} pay WHERE pay.purchaseid = p.id AND pay.status IN (' . implode(',', $holders) . '))'
            . ' ORDER BY p.timecreated DESC, p.id DESC';
        $rows = $db->get_records_sql($sql, $params, 0, 1);
        if ($rows === []) { return null; }
        $row = reset($rows);
        return (int)$row->id;
    }


    /**
     * Resolves effective ownership of a Native product for a beneficiary.
     *
     * A paid Native purchase is preferred as the historical source purchase when one exists,
     * but ownership itself is delegated to the Commerce storefront resolver so grants,
     * bundles and transitional Legacy ownership are also accepted.
     *
     * @return array{owned:bool, sourcepurchaseid:?int, source:string}
     */
    public static function resolve_product_ownership(\moodle_database $db, string $email, ?int $userid, int $productid): array {
        if ($productid <= 0) {
            return [
                'owned' => false,
                'sourcepurchaseid' => null,
                'source' => 'none',
                'productid' => null,
                'productsku' => null,
            ];
        }

        $product = $db->get_record('local_subs_commerce_product', ['id' => $productid], 'id,sku', MUST_EXIST);
        $sourcepurchaseid = self::resolve_purchase_for_product($db, $email, $userid, $productid);

        if ($userid !== null && $userid > 0) {
            $resolver = new \local_subscriptions\commerce\storefront\ownership\CommerceStorefrontOwnershipResolver($db);
            $source = $resolver->resolve_source($userid, (string)$product->sku);
            if ($source !== 'none') {
                return [
                    'owned' => true,
                    'sourcepurchaseid' => $sourcepurchaseid,
                    'source' => $source,
                    'productid' => (int)$product->id,
                    'productsku' => (string)$product->sku,
                ];
            }
        }

        if ($sourcepurchaseid !== null) {
            return [
                'owned' => true,
                'sourcepurchaseid' => $sourcepurchaseid,
                'source' => 'native_purchase_email',
                'productid' => (int)$product->id,
                'productsku' => (string)$product->sku,
            ];
        }

        return [
            'owned' => false,
            'sourcepurchaseid' => null,
            'source' => 'none',
            'productid' => (int)$product->id,
            'productsku' => (string)$product->sku,
        ];
    }

    public static function datetime_local(string $value, string $timezone): ?int {
        $value = trim($value);
        if ($value === '') { return null; }
        try { $tz = new \DateTimeZone($timezone); } catch (\Throwable) {
            throw new \coding_exception('Invalid timezone.');
        }
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $tz);
        if (!$dt || $dt->format('Y-m-d\TH:i') !== $value) {
            throw new \coding_exception('Invalid date and time.');
        }
        return $dt->getTimestamp();
    }

    public static function timestamp(string $date,bool $end=false): ?int { if(trim($date)==='')return null;$dt=\DateTimeImmutable::createFromFormat('!Y-m-d',trim($date),new \DateTimeZone('UTC'));if(!$dt||$dt->format('Y-m-d')!==trim($date))throw new \coding_exception('Invalid date.');if($end)$dt=$dt->setTime(23,59,59);return $dt->getTimestamp(); }
}
