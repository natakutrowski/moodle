<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\sales;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\context\CommercePurchaseMailLanguageResolver;
use local_subscriptions\commerce\mail\library\CommerceMailLibrary;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseDetails;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\url\UrlFactory;

final class CommerceSalesFollowupService {
    private const ELIGIBLE_PAYMENT = [
        'created', 'prepared', 'redirected', 'pending', 'payment_pending',
        'authorized', 'failed', 'cancelled', 'canceled', 'declined', 'error',
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePurchaseReadRepository $purchases,
        private readonly CommerceMailLibraryRepository $library
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db ??= $DB;
        return new self(
            $db,
            new CommercePurchaseReadRepository($db),
            new CommerceMailLibraryRepository($db)
        );
    }

    public function details(int $purchaseid): CommercePurchaseDetails {
        $details = $this->purchases->find_by_id($purchaseid);
        if ($details === null) {
            throw new \moodle_exception('invalidrecordunknown');
        }
        return $details;
    }

    public function is_eligible(CommercePurchaseDetails $details): bool {
        return $this->is_summary_eligible($details->summary);
    }

    public function is_summary_eligible(
        \local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary $summary
    ): bool {
        if ($summary->customer->email === '') {
            return false;
        }
        if (in_array(
            strtolower($summary->paymentstatus),
            self::ELIGIBLE_PAYMENT,
            true
        )) {
            return true;
        }

        return in_array(
            strtolower($summary->commercialstatus),
            ['payment_failed', 'cancelled'],
            true
        );
    }

    public function assert_eligible(CommercePurchaseDetails $details): void {
        if (!$this->is_eligible($details)) {
            throw new \moodle_exception(
                'commerce_sales_followup_not_eligible',
                'local_subscriptions'
            );
        }
    }

    public function language(CommercePurchaseDetails $details): string {
        return (new CommercePurchaseMailLanguageResolver($this->db))->resolve(
            $details->summary->customer->userid,
            $details
        );
    }

    /** @return array<int,string> */
    public function template_options(int $userid): array {
        (new CommerceSalesFollowupTemplateSeeder($this->library))->ensure($userid);
        $options = [];
        foreach ($this->library->all(CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP) as $template) {
            if ((string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE) {
                continue;
            }
            $options[(int)$template->id] = (string)$template->name;
        }
        return $options;
    }

    /** @return array{subject:string,bodyhtml:string,templateid:int} */
    public function template_content(int $templateid, string $language): array {
        $template = $this->library->get($templateid);
        if ((string)$template->category !== CommerceMailLibrary::CATEGORY_SALES_FOLLOWUP
                || (string)$template->status !== CommerceMailLibrary::STATUS_ACTIVE) {
            throw new \moodle_exception(
                'commerce_sales_followup_invalid_template',
                'local_subscriptions'
            );
        }

        $contents = $this->library->contents($templateid);
        $content = $contents[$language] ?? $contents['fr'] ?? reset($contents);
        if (!$content) {
            throw new \moodle_exception(
                'commerce_sales_followup_invalid_template',
                'local_subscriptions'
            );
        }
        $document = json_decode((string)$content->contentjson, true) ?: [];

        return [
            'subject' => (string)$content->subject,
            'bodyhtml' => (string)($document['bodyhtml'] ?? ''),
            'templateid' => $templateid,
        ];
    }

    /** @return array<string,string|int|null> */
    public function context(CommercePurchaseDetails $details): array {
        global $CFG;

        $summary = $details->summary;
        $product = trim((string)($summary->productlabels[0] ?? ''));
        if (count($summary->productlabels) > 1) {
            $product .= ' +' . (count($summary->productlabels) - 1);
        }

        $paymentlink = '';
        foreach (array_reverse($details->payments) as $payment) {
            if ($payment->paymentrequest === null) {
                continue;
            }
            foreach (['payment_link', 'checkout_url', 'resume_payment_url'] as $key) {
                $candidate = trim((string)($payment->paymentrequest->details[$key] ?? ''));
                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL)) {
                    $paymentlink = $candidate;
                    break 2;
                }
            }
        }

        $fullname = $summary->customer->display_name();
        $supportemail = trim((string)get_config('local_subscriptions', 'support_email'));
        if ($supportemail === '') {
            $supportemail = (string)($CFG->supportemail ?? '');
        }

        return [
            'firstname' => $summary->customer->firstname,
            'fullname' => $fullname,
            'email' => $summary->customer->email,
            'order_reference' => $summary->publicreference !== ''
                ? $summary->publicreference
                : $summary->reference,
            'product_name' => $product !== '' ? $product : '—',
            'order_total' => CommercePurchasePresentation::money(
                $summary->totalminor,
                $summary->currency
            ),
            'currency' => strtoupper($summary->currency),
            'payment_provider' => (string)($summary->provider ?? ''),
            'payment_status' => $summary->paymentstatus,
            'checkout_url' => $paymentlink,
            'my_purchases_url' => UrlFactory::my_purchases()->out(false),
            'support_email' => $supportemail,
            'purchaseid' => $summary->id,
        ];
    }

    /** @return array{count:int,last:?stdClass} */
    public function previous_followups(int $purchaseid): array {
        $records = $this->db->get_records(
            'local_subs_commerce_mail',
            [
                'purchaseid' => $purchaseid,
                'mailtype' => \local_subscriptions\commerce\mail\CommerceMailType::SALES_FOLLOWUP,
            ],
            'timecreated DESC, id DESC',
            '*',
            0,
            20
        );
        $records = array_values($records);
        return [
            'count' => count($records),
            'last' => $records[0] ?? null,
        ];
    }

}
