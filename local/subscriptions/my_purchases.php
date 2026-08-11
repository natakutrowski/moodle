<?php
// local/subscriptions/my_purchases.php.

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\CommerceCustomerPublicUrlResolver;
use local_subscriptions\output\my_purchases\MyPurchasesFilter;
use local_subscriptions\commerce\order\presentation\CommerceCustomerStatusResolver;
use local_subscriptions\commerce\catalog\resolution\CommerceLegacyStorefrontProductResolver;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\output\my_purchases\MyPurchasesPage;
use local_subscriptions\url\UrlFactory;

require_login();

if (isguestuser()) {
    redirect(new moodle_url('/login/index.php'));
}

global $OUTPUT, $PAGE, $USER;

$requesteduserid = optional_param('userid', 0, PARAM_INT);
$targetuserid = (int)$USER->id;

if ($requesteduserid > 0 && $requesteduserid !== (int)$USER->id) {
    $targetcontext = context_user::instance($requesteduserid, IGNORE_MISSING);

    if ($targetcontext && has_capability('moodle/user:viewdetails', $targetcontext)) {
        $targetuserid = $requesteduserid;
    } else {
        redirect(UrlFactory::my_purchases());
    }
}

$targetuser = core_user::get_user($targetuserid, '*', MUST_EXIST);
$isadminview = (int)$targetuser->id !== (int)$USER->id;
$pageurlparams = $isadminview ? ['userid' => $targetuserid] : [];

$PAGE->set_context(context_user::instance($targetuserid));
$PAGE->set_url(UrlFactory::my_purchases(), $pageurlparams);
$PAGE->set_pagelayout('standard');
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/my_purchases.css'));

$pagetitle = $isadminview
    ? get_string('user_purchases_title', 'local_subscriptions', fullname($targetuser))
    : get_string('mysubs_title', 'local_subscriptions');

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(
    get_string('commerce_customer_hub_title', 'local_subscriptions'),
    UrlFactory::my_campus()
);
$PAGE->navbar->add($pagetitle);

$page = new MyPurchasesPage(
    $targetuser,
    $isadminview,
    MyPurchasesFilter::from_request()
);

/** @var local_subscriptions\output\renderer $renderer */
$renderer = $PAGE->get_renderer('local_subscriptions');

$purchasecontent = $renderer->render_my_purchases_page($page);

$nativeorders = (new CommercePurchaseReadRepository($DB))->find_details_for_customer(
    (int)$targetuser->id,
    (string)$targetuser->email
);
$presentations = [];
$presentationservice = CommerceOrderPresentationService::create();
$legacyproductresolver = new CommerceLegacyStorefrontProductResolver($DB);
$courseaccesssinglelabel = get_string('commerce_i49_open_course', 'local_subscriptions');
[$courseaccessmultiplelabel] = match (current_language()) {
    'fr', 'fr_ca' => ['Accéder à mes cours'],
    'ru' => ['Перейти к моим курсам'],
    default => ['Access my courses'],
};
$productpagelabel = get_string('digital_product_view_page', 'local_subscriptions');
foreach ($nativeorders as $nativeorder) {
    $presentations[$nativeorder->summary->reference] = $presentationservice->present($nativeorder);
}

if (class_exists(DOMDocument::class)) {
    $previouslibxmlstate = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $loaded = $document->loadHTML(
        '<?xml encoding="UTF-8"><div id="my-purchases-enhanced-root">' . $purchasecontent . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    if ($loaded) {
        $xpath = new DOMXPath($document);
        $detailanchors = $xpath->query('//a[contains(@href, "order_details.php")]');
        $statusresolver = new CommerceCustomerStatusResolver();

        foreach ($detailanchors as $detailanchor) {
            if (!$detailanchor instanceof DOMElement) {
                continue;
            }

            $href = html_entity_decode($detailanchor->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $query = (string)(parse_url($href, PHP_URL_QUERY) ?? '');
            parse_str($query, $params);
            $reference = trim((string)($params['reference'] ?? ''));
            $presentation = $presentations[$reference] ?? null;
            if ($presentation === null) {
                continue;
            }

            $card = $detailanchor;
            while ($card !== null
                    && !($card instanceof DOMElement
                        && $card->tagName === 'article'
                        && str_contains(' ' . $card->getAttribute('class') . ' ', ' my-purchase-card '))) {
                $card = $card->parentNode;
            }
            if (!$card instanceof DOMElement) {
                continue;
            }

            $type = strtolower(trim((string)$presentation->type));
            $actions = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " my-purchase-card__actions ")]', $card)?->item(0);

            if (in_array($type, ['digital', 'digital_download'], true) && $actions instanceof DOMElement) {
                $existinghrefs = [];
                foreach ($xpath->query('.//a[@href]', $actions) as $existinganchor) {
                    if ($existinganchor instanceof DOMElement) {
                        $existinghrefs[html_entity_decode($existinganchor->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8')] = true;
                    }
                }

                $skus = [];
                foreach ($presentation->items as $item) {
                    $sku = trim((string)($item->metadata['productsku'] ?? $item->metadata['sku'] ?? ''));
                    if ($sku !== '') {
                        $skus[$sku] = true;
                    }
                }
                foreach ($nativeorders as $nativeorder) {
                    if ($nativeorder->summary->reference !== $reference) {
                        continue;
                    }
                    foreach ($nativeorder->grants as $grant) {
                        if ($grant->type === 'digital_download' && trim($grant->productsku) !== '') {
                            $skus[trim($grant->productsku)] = true;
                        }
                    }
                    break;
                }

                $newactions = [];
                foreach (array_keys($skus) as $sku) {
                    $producturl = (CommerceCustomerPublicUrlResolver::storefront($sku))->out(false);
                    if (!isset($existinghrefs[$producturl])) {
                        $newactions[] = [
                            'url' => $producturl,
                            'label' => get_string('digital_product_view_page', 'local_subscriptions'),
                            'icon' => 'fa-solid fa-arrow-up-right-from-square',
                            'class' => 'btn btn-outline-primary btn-sm product-page-action',
                        ];
                    }
                }

                foreach ($presentation->items as $item) {
                    foreach ($item->accesses as $access) {
                        if ($access->type !== 'digital_download' || !$access->available || $access->url === null) {
                            continue;
                        }
                        $versions = [];
                        if (!empty($access->metadata['hasdesktop'])) {
                            $versions[] = ['desktop', get_string('digital_download_classic', 'local_subscriptions'), 'fa-solid fa-download'];
                        }
                        if (!empty($access->metadata['hasmobile'])) {
                            $versions[] = ['mobile', get_string('digital_download_mobile', 'local_subscriptions'), 'fa-solid fa-mobile-screen-button'];
                        }
                        if ($versions === []) {
                            $versions[] = ['', get_string('digital_download_classic', 'local_subscriptions'), 'fa-solid fa-download'];
                        }

                        foreach ($versions as [$version, $label, $icon]) {
                            $downloadurl = new moodle_url($access->url);
                            if ($version !== '') {
                                $downloadurl->param('version', $version);
                            }
                            $downloadhref = $downloadurl->out(false);
                            if (!isset($existinghrefs[$downloadhref])) {
                                $newactions[] = [
                                    'url' => $downloadhref,
                                    'label' => $label,
                                    'icon' => $icon,
                                    'class' => 'btn btn-outline-primary btn-sm',
                                ];
                                $existinghrefs[$downloadhref] = true;
                            }
                        }
                    }
                }

                foreach ($newactions as $action) {
                    $wrapper = $document->createElement('span');
                    $wrapper->setAttribute('class', 'my-purchase-card__action');
                    $anchor = $document->createElement('a');
                    $anchor->setAttribute('href', $action['url']);
                    $anchor->setAttribute('class', $action['class']);
                    if ($action['icon'] !== '') {
                        $icon = $document->createElement('i');
                        $icon->setAttribute('class', $action['icon']);
                        $icon->setAttribute('aria-hidden', 'true');
                        $anchor->appendChild($icon);
                        $anchor->appendChild($document->createTextNode(' '));
                    }
                    $anchor->appendChild($document->createTextNode($action['label']));
                    $wrapper->appendChild($anchor);
                    $actions->insertBefore($wrapper, $detailanchor->parentNode);
                }
            }

            if ($type === 'bundle') {
                $paymentstate = $statusresolver->resolve_payment((string)$presentation->paymentstatus);
                $badge = $xpath->query(
                    './/*[contains(concat(" ", normalize-space(@class), " "), " crm-commerce-status-badge ")]',
                    $card
                )?->item(0);
                if ($badge instanceof DOMElement) {
                    while ($badge->firstChild !== null) {
                        $badge->removeChild($badge->firstChild);
                    }
                    $badge->appendChild($document->createTextNode($paymentstate['label']));
                    $badge->setAttribute(
                        'class',
                        'crm-commerce-status-badge crm-commerce-status-' . $paymentstate['class']
                    );
                }

                $classes = trim($card->getAttribute('class'));
                if ($paymentstate['class'] === 'warning') {
                    $classes .= ' my-purchase-card--payment-pending';
                } elseif ($paymentstate['class'] === 'danger') {
                    $classes .= ' my-purchase-card--payment-error';
                }
                $card->setAttribute('class', trim($classes));

                foreach ($xpath->query('.//a[contains(@href, "storefront_product.php")]', $card) as $productanchor) {
                    if ($productanchor instanceof DOMElement) {
                        while ($productanchor->firstChild !== null) {
                            $productanchor->removeChild($productanchor->firstChild);
                        }
                        $productanchor->appendChild($document->createTextNode($productpagelabel));
                    }
                }
            }
        }

        foreach ($xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " my-purchase-card--course ")]') as $coursecard) {
            if (!$coursecard instanceof DOMElement) {
                continue;
            }

            $actions = $xpath->query(
                './/div[contains(concat(" ", normalize-space(@class), " "), " my-purchase-card__actions ")]',
                $coursecard
            )?->item(0);
            if (!$actions instanceof DOMElement) {
                continue;
            }

            $detailcontrol = $xpath->query(
                './/a[contains(@href, "order_details.php")] | .//button[contains(@data-bs-target, "#subModal")]',
                $actions
            )?->item(0);
            $detailwrapper = $detailcontrol?->parentNode;
            if (!$detailwrapper instanceof DOMNode) {
                continue;
            }

            $courseurls = [];
            $producturl = '';

            foreach ($xpath->query('.//li[.//a[contains(@href, "/course/view.php")]]', $coursecard) as $courseli) {
                if (!$courseli instanceof DOMElement) {
                    continue;
                }
                foreach ($xpath->query('.//a[contains(@href, "/course/view.php")]', $courseli) as $courseanchor) {
                    if ($courseanchor instanceof DOMElement) {
                        $coursehref = html_entity_decode(
                            $courseanchor->getAttribute('href'),
                            ENT_QUOTES | ENT_HTML5,
                            'UTF-8'
                        );
                        if ($coursehref !== '') {
                            $courseurls[$coursehref] = true;
                        }
                    }
                }
                $courseli->parentNode?->removeChild($courseli);
            }

            foreach ($xpath->query('.//a[@href]', $actions) as $existinganchor) {
                if (!$existinganchor instanceof DOMElement) {
                    continue;
                }
                $existinghref = html_entity_decode(
                    $existinganchor->getAttribute('href'),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );
                if (str_contains($existinghref, '/storefront_product.php')) {
                    $producturl = $existinghref;
                }
                if (str_contains($existinghref, '/course/view.php')) {
                    $courseurls[$existinghref] = true;
                }
            }

            if ($producturl === '' && $detailcontrol instanceof DOMElement && $detailcontrol->tagName === 'button') {
                $target = $detailcontrol->getAttribute('data-bs-target');
                if (preg_match('/#subModal([1-9][0-9]*)$/', $target, $matches)) {
                    $legacysubscription = $DB->get_record(
                        'user_subscription',
                        ['id' => (int)$matches[1]],
                        'id,planid',
                        IGNORE_MISSING
                    );
                    if ($legacysubscription) {
                        $resolvedurl = $legacyproductresolver->storefront_url(
                            'subscription_plan',
                            (int)$legacysubscription->planid
                        );
                        if ($resolvedurl !== null) {
                            $producturl = $resolvedurl->out(false);
                        }
                    }
                }
            }

            foreach (iterator_to_array($xpath->query('./*', $actions)) as $actionwrapper) {
                if (!$actionwrapper instanceof DOMElement || $actionwrapper === $detailwrapper) {
                    continue;
                }
                $anchor = $xpath->query('.//a[@href]', $actionwrapper)?->item(0);
                if (!$anchor instanceof DOMElement) {
                    continue;
                }
                $href = html_entity_decode($anchor->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (str_contains($href, '/storefront_product.php') || str_contains($href, '/course/view.php')) {
                    $actions->removeChild($actionwrapper);
                }
            }

            $insertcoursebutton = static function(
                DOMDocument $document,
                DOMElement $actions,
                DOMNode $before,
                string $url,
                string $label,
                string $class,
                string $icon = '',
                bool $external = false
            ): void {
                if ($url === '') {
                    return;
                }
                $wrapper = $document->createElement('span');
                $wrapper->setAttribute('class', 'my-purchase-card__action');
                $anchor = $document->createElement('a');
                $anchor->setAttribute('href', $url);
                $anchor->setAttribute('class', $class);
                if ($external) {
                    $anchor->setAttribute('target', '_blank');
                    $anchor->setAttribute('rel', 'noopener noreferrer');
                }
                if ($icon !== '') {
                    $iconelement = $document->createElement('i');
                    $iconelement->setAttribute('class', $icon);
                    $iconelement->setAttribute('aria-hidden', 'true');
                    $anchor->appendChild($iconelement);
                    $anchor->appendChild($document->createTextNode(' '));
                }
                $anchor->appendChild($document->createTextNode($label));
                $wrapper->appendChild($anchor);
                $actions->insertBefore($wrapper, $before);
            };

            // The product page is the first action for both Legacy and Native purchases.
            $insertcoursebutton(
                $document,
                $actions,
                $actions->firstChild ?? $detailwrapper,
                $producturl,
                $productpagelabel,
                'btn btn-outline-primary btn-sm product-page-action',
                'fa-solid fa-arrow-up-right-from-square',
                true
            );

            $courseurls = array_keys($courseurls);
            if (count($courseurls) === 1) {
                $insertcoursebutton(
                    $document,
                    $actions,
                    $detailwrapper,
                    $courseurls[0],
                    $courseaccesssinglelabel,
                    'btn btn-primary btn-sm',
                    'fa-solid fa-graduation-cap'
                );
            } elseif (count($courseurls) > 1) {
                $insertcoursebutton(
                    $document,
                    $actions,
                    $detailwrapper,
                    \local_subscriptions\url\UrlFactory::my_courses()->out(false),
                    $courseaccessmultiplelabel,
                    'btn btn-primary btn-sm',
                    'fa-solid fa-graduation-cap'
                );
            }
        }

        foreach ($xpath->query('//a[@href]') as $productanchor) {
            if (!$productanchor instanceof DOMElement) {
                continue;
            }

            $producthref = html_entity_decode(
                $productanchor->getAttribute('href'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            if (str_contains($producthref, '/digital_product.php')) {
                $query = (string)(parse_url($producthref, PHP_URL_QUERY) ?? '');
                parse_str($query, $productparams);
                $slug = trim((string)($productparams['p'] ?? ''));
                $legacyproductid = $slug === '' ? 0 : (int)$DB->get_field(
                    'subscription_digital_product',
                    'id',
                    ['slug' => $slug]
                );

                if ($legacyproductid > 0) {
                    $resolvedurl = $legacyproductresolver->storefront_url(
                        'subscription_digital_product',
                        $legacyproductid
                    );
                    if ($resolvedurl !== null) {
                        $producthref = $resolvedurl->out(false);
                        $productanchor->setAttribute('href', $producthref);
                    } else {
                        $wrapper = $productanchor->parentNode;
                        if ($wrapper instanceof DOMElement
                                && str_contains(' ' . $wrapper->getAttribute('class') . ' ', ' my-purchase-card__action ')) {
                            $wrapper->parentNode?->removeChild($wrapper);
                        } else {
                            $productanchor->parentNode?->removeChild($productanchor);
                        }
                        continue;
                    }
                } else {
                    // Never keep a link to the deprecated Legacy product page.
                    $wrapper = $productanchor->parentNode;
                    if ($wrapper instanceof DOMElement
                            && str_contains(' ' . $wrapper->getAttribute('class') . ' ', ' my-purchase-card__action ')) {
                        $wrapper->parentNode?->removeChild($wrapper);
                    } else {
                        $productanchor->parentNode?->removeChild($productanchor);
                    }
                    continue;
                }
            }

            if (!str_contains($producthref, '/storefront_product.php')) {
                continue;
            }

            while ($productanchor->firstChild !== null) {
                $productanchor->removeChild($productanchor->firstChild);
            }
            $externalicon = $document->createElement('i');
            $externalicon->setAttribute('class', 'fa-solid fa-arrow-up-right-from-square');
            $externalicon->setAttribute('aria-hidden', 'true');
            $productanchor->appendChild($externalicon);
            $productanchor->appendChild($document->createTextNode(' ' . $productpagelabel));
            $productanchor->setAttribute('class', 'btn btn-outline-primary btn-sm product-page-action');
            $productanchor->setAttribute('target', '_blank');
            $productanchor->setAttribute('rel', 'noopener noreferrer');
        }

        // Final customer-facing URL normalisation. Legacy renderers may still
        // expose technical Moodle URLs; convert them before returning HTML.
        foreach ($xpath->query('//a[@href]') as $anchor) {
            if (!$anchor instanceof DOMElement) {
                continue;
            }
            $href = html_entity_decode(
                $anchor->getAttribute('href'),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
            $path = (string)(parse_url($href, PHP_URL_PATH) ?? '');
            $query = (string)(parse_url($href, PHP_URL_QUERY) ?? '');
            parse_str($query, $params);

            if ($path === '/course/view.php' && (int)($params['id'] ?? 0) > 0) {
                $anchor->setAttribute(
                    'href',
                    \local_subscriptions\url\UrlFactory::course(
                        (int)$params['id']
                    )->out(false)
                );
                continue;
            }
            if ($path === '/my/courses.php') {
                $anchor->setAttribute(
                    'href',
                    \local_subscriptions\url\UrlFactory::my_courses()->out(false)
                );
                continue;
            }
            if ($path === '/local/subscriptions/storefront_product.php') {
                $sku = trim((string)($params['sku'] ?? ''));
                if ($sku !== '') {
                    unset($params['sku']);
                    $anchor->setAttribute(
                        'href',
                        \local_subscriptions\url\CommerceCustomerPublicUrlResolver::storefront(
                            $sku,
                            $params
                        )->out(false)
                    );
                }
            }
        }

        $root = $document->getElementById('my-purchases-enhanced-root');
        if ($root !== null) {
            $purchasecontent = '';
            foreach ($root->childNodes as $child) {
                $purchasecontent .= $document->saveHTML($child);
            }
        }
    }

    libxml_clear_errors();
    libxml_use_internal_errors($previouslibxmlstate);
}

echo $OUTPUT->header();
echo $purchasecontent;
echo $OUTPUT->footer();