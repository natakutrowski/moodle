<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\admin;

use local_subscriptions\commerce\mail\service\CommerceGrantCampaignMailService;
defined('MOODLE_INTERNAL') || die();

use moodle_database;
use moodle_url;

/** Resolve CRM navigation targets from one persisted mail record/context. */
final class CommerceMailAdminContextResolver {
    public function __construct(private readonly moodle_database $db) {}

    /** @return array<string,mixed> */
    public function resolve(\stdClass $record): array {
        $context = json_decode((string)($record->contextjson ?? ''), true);
        $context = is_array($context) ? $context : [];

        $recipienturl = null;
        if (!empty($record->userid)) {
            $recipienturl = new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => (int)$record->userid]);
        } else if (trim((string)$record->recipientemail) !== '') {
            $recipienturl = new moodle_url('/local/subscriptions/admin/users/view.php', [
                'email' => trim((string)$record->recipientemail),
            ]);
        }

        $purchaseurl = null;
        if (!empty($record->purchaseid)) {
            $purchaseurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', [
                'id' => (int)$record->purchaseid,
            ]);
        }

        $productlabel = '';
        $producturl = null;
        $items = is_array($context['items'] ?? null) ? $context['items'] : [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $productlabel = trim((string)($item['title'] ?? ''));
            $sku = trim((string)($item['productsku'] ?? ''));
            if ($sku !== '') {
                $product = $this->db->get_record(
                    'local_subs_commerce_product',
                    ['sku' => $sku],
                    'id,sku,name',
                    IGNORE_MISSING
                );
                if ($product) {
                    if ($productlabel === '') {
                        $productlabel = trim((string)$product->name);
                    }
                    $producturl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', [
                        'catalogkey' => 'native:' . (int)$product->id,
                    ]);
                }
            }
            if ($productlabel !== '' || $producturl !== null) {
                break;
            }
        }

        $contexttitle = '';
        $contextsubtitle = '';
        $contexturl = null;

        $grantcampaigncontext = is_array($context['grantcampaign'] ?? null)
            ? $context['grantcampaign']
            : [];
        $offercontext = is_array($context['personaloffer'] ?? null)
            ? $context['personaloffer']
            : [];

        $grantcampaign = null;
        if ((string)($record->mailtype ?? '') === 'grant_access') {
            $grantcampaign = (new CommerceGrantCampaignMailService($this->db))
                ->campaign_for_mail($record, $context);
        }

        if ($grantcampaign) {
            $contexttitle = get_string(
                'commerce_mail_context_grant_campaign',
                'local_subscriptions'
            ) . ' · ' . (string)$grantcampaign->name;
            $contextsubtitle = $productlabel;
            $contexturl = new moodle_url(
                '/local/subscriptions/admin/commerce/grants/campaign_view.php',
                ['id' => (int)$grantcampaign->id]
            );
        } else if ($grantcampaigncontext !== []) {
            // Explicit but unresolved campaign context: preserve the business
            // label, but never fall back to the product URL as a campaign link.
            $campaignname = trim(
                (string)($grantcampaigncontext['campaignname'] ?? '')
            );
            $contexttitle = get_string(
                'commerce_mail_context_grant_campaign',
                'local_subscriptions'
            )
                . ($campaignname !== '' ? ' · ' . $campaignname : '');
            $contextsubtitle = $productlabel;
            $contexturl = null;
        } else if ($offercontext !== []) {
            $campaignname = trim((string)($offercontext['campaignname'] ?? ''));
            $contexttitle = get_string('commerce_mail_context_personal_offer', 'local_subscriptions')
                . ($campaignname !== '' ? ' · ' . $campaignname : '');

            $offersku = trim((string)($offercontext['productsku'] ?? ''));
            $offerproductname = trim((string)($offercontext['productname'] ?? ''));
            if ($offersku !== '') {
                $product = $this->db->get_record(
                    'local_subs_commerce_product',
                    ['sku' => $offersku],
                    'id,sku,name',
                    IGNORE_MISSING
                );
                if ($product) {
                    $productlabel = $offerproductname !== '' ? $offerproductname : trim((string)$product->name);
                    $producturl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', [
                        'catalogkey' => 'native:' . (int)$product->id,
                    ]);
                }
            }
            $contextsubtitle = $productlabel !== '' ? $productlabel : $offerproductname;
            $campaignid = (int)($offercontext['campaignid'] ?? 0);
            $offeruuid = strtolower(trim((string)($offercontext['offeruuid'] ?? '')));
            if ($campaignid > 0) {
                $contexturl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', [
                    'id' => $campaignid,
                ]);
            } else if ($offeruuid !== '') {
                $offerid = $this->db->get_field(
                    'local_subs_commerce_offer',
                    'id',
                    ['offeruuid' => $offeruuid],
                    IGNORE_MISSING
                );
                if ($offerid) {
                    $contexturl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', [
                        'id' => (int)$offerid,
                    ]);
                }
            }
        } else if ((int)($context['campaignid'] ?? 0) > 0
                && (string)($record->mailtype ?? '') === 'marketing_campaign') {
            $campaignid = (int)$context['campaignid'];
            $campaign = $this->db->get_record(
                'local_subs_mail_campaign',
                ['id' => $campaignid],
                'id,name',
                IGNORE_MISSING
            );
            $contexttitle = get_string('commerce_mail_context_marketing_campaign', 'local_subscriptions')
                . ($campaign ? ' · ' . (string)$campaign->name : '');
            $contexturl = new moodle_url(
                '/local/subscriptions/admin/commerce/mail/campaigns/edit.php',
                ['id' => $campaignid]
            );
        } else if (!empty($record->purchaseid)) {
            $contexttitle = get_string('commerce_mail_context_order', 'local_subscriptions', (int)$record->purchaseid);
            $contextsubtitle = $productlabel;
            $contexturl = $purchaseurl;
        } else if ($productlabel !== '') {
            $contexttitle = $productlabel;
            $contexturl = $producturl;
        }

        return [
            'recipienturl' => $recipienturl,
            'purchaseurl' => $purchaseurl,
            'productlabel' => $productlabel,
            'producturl' => $producturl,
            'contexttitle' => $contexttitle,
            'contextsubtitle' => $contextsubtitle,
            'contexturl' => $contexturl,
        ];
    }
}
