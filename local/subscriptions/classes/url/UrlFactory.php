<?php
namespace local_subscriptions\url;
defined('MOODLE_INTERNAL') || die();

use moodle_url;
use local_subscriptions\subscription_config;

final class UrlFactory {

    public static function my_campus(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::MY_CAMPUS), $params);
    }

    public static function storefront(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::STOREFRONT), $params);
    }

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

    public static function my_purchases(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::MY_PURCHASES), $params);
    }

    public static function my_digital_products(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::MY_RESOURCES), $params);
    }

    public static function order_result(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::ORDER_RESULT), $params);
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
    public static function return(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment/return.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment/return.php');
        }
    }
    public static function retry(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/payment/retry_payment.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/payment/retry_payment.php');
        }
    }
    public static function portal(array $params=[]): moodle_url {
        if ($params){
            return new moodle_url('/local/subscriptions/portal.php',$params);
        } else {
            return new moodle_url('/local/subscriptions/portal.php');
        }
    }

    public static function digital_catalog(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::STOREFRONT), $params);
    }    

    public static function digital_product(string $slug): moodle_url {
        return new moodle_url('/digital/' . $slug);
    }

    public static function digital_checkout(array $params = []): moodle_url {
        return new moodle_url('/local/subscriptions/payment/digital_create_session.php', $params);
    }

    public static function digital_success(array $params = []): moodle_url {
        return new moodle_url('/local/subscriptions/digital_success.php', $params);
    }

    public static function digital_cancel(array $params = []): moodle_url {
        return new moodle_url('/local/subscriptions/digital_cancel.php', $params);
    }

    public static function digital_download(array $params = []): moodle_url {
        return new moodle_url('/local/subscriptions/download_pdf.php', $params);
    }

    public static function my_courses(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::MY_COURSES), $params);
    }

    public static function my_profile(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::MY_PROFILE), $params);
    }


    public static function customer_checkout(array $params = []): moodle_url {
        return new moodle_url(
            CommerceRouteRegistry::path(CommerceRouteRegistry::CHECKOUT),
            $params
        );
    }

    public static function course(int $courseid, array $params = []): moodle_url {
        return new moodle_url(
            rtrim(CommerceRouteRegistry::path(CommerceRouteRegistry::COURSE), '/') . '/' . $courseid,
            $params
        );
    }

    public static function cart(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::CART), $params);
    }

    public static function order_details(array $params = []): moodle_url {
        return new moodle_url(CommerceRouteRegistry::path(CommerceRouteRegistry::ORDER_DETAILS), $params);
    }

    public static function support(array $params = []): moodle_url {
        return new moodle_url(
            CommerceRouteRegistry::path(CommerceRouteRegistry::SUPPORT),
            $params
        );
    }


    /** Stable public route for regional terms and conditions. */
    public static function terms(array $params = []): moodle_url {
        return new moodle_url('/terms', $params);
    }

    /** Stable public route for the regional privacy policy. */
    public static function privacy(array $params = []): moodle_url {
        return new moodle_url('/privacy', $params);
    }

    public static function showroom(
        string $key,
        array $params = [],
        ?string $language = null
    ): moodle_url {
        return \local_subscriptions\commerce\showroom\CommerceShowroomUrl::make(
            $key,
            $params,
            $language
        );
    }

    public static function support_for_order(
        string $reference,
        array $params = []
    ): moodle_url {
        $reference = trim($reference);
        if ($reference !== '') {
            $params = ['reference' => $reference] + $params;
        }
        return self::support($params);
    }

    public static function product_slug(
        string $slug,
        array $params = [],
        string $producttype = 'digital_download',
        ?string $language = null
    ): moodle_url {
        $slug = CommerceProductSlugService::clean($slug);
        return new moodle_url(
            subscription_config::public_product_path($producttype, $slug, $language),
            $params
        );
    }

    /**
     * Valide une URL de redirection externe fournie par une passerelle.
     *
     * Seules les URLs HTTPS absolues sont acceptées.
     *
     * @param string $url URL fournie par le provider.
     * @return string URL validée.
     */
    public static function validate_external_payment_url(
        string $url
    ): string {
        $url = trim($url);

        if ($url === '') {
            throw new \moodle_exception(
                'err_no_redirect_url',
                'local_subscriptions'
            );
        }

        $parts = parse_url($url);

        if (
            !is_array($parts) ||
            strtolower((string)($parts['scheme'] ?? '')) !==
                'https' ||
            trim((string)($parts['host'] ?? '')) === ''
        ) {
            throw new \moodle_exception(
                'err_invalid_redirect_url',
                'local_subscriptions'
            );
        }

        return $url;
    }

}
