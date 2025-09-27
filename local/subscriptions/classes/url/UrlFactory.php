<?php
namespace local_subscriptions\url;
defined('MOODLE_INTERNAL') || die();

use moodle_url;

final class UrlFactory {

    public static function subscribe(?int $planid = null): moodle_url {
        if ($planid){
            $p = ['planid'=>$planid];
            return new moodle_url('/local/subscriptions/subscribe.php', $p);
        } else {
            return new moodle_url('/local/subscriptions/subscribe.php');
        }
    }
    public static function checkout(int $planid, ?string $currency = null, array $extra=[]): moodle_url {
        $p = ['planid' => $planid] + $extra; if ($currency) $p['currency'] = $currency;
        return new moodle_url('/local/subscriptions/checkout.php', $p);
    }
    public static function my_subscriptions(): moodle_url {
        return new moodle_url('/user/my_subscriptions.php');
    }
    public static function payment_success(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment_success.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment_success.php');
        }
    }
    public static function payment_cancel(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment_cancel.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment_cancel.php');
        }
    }
    public static function payment_error(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment_error.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment_error.php');
        }
    }
    public static function create_session(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment/create_session.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment/create_session.php');
        }
    }
    public static function portal(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/portal.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/portal.php');
        }
    }
}
