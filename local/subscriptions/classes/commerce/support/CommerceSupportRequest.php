<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\support;

defined('MOODLE_INTERNAL') || die();

final class CommerceSupportRequest {
    public const CATEGORY_PAYMENT = 'payment';
    public const CATEGORY_COURSE_ACCESS = 'course_access';
    public const CATEGORY_DOWNLOAD = 'download';
    public const CATEGORY_INVOICE = 'invoice';
    public const CATEGORY_ACCOUNT = 'account';
    public const CATEGORY_TECHNICAL = 'technical';
    public const CATEGORY_COURSE_QUESTION = 'course_question';
    public const CATEGORY_OTHER = 'other';

    public function __construct(
        public readonly string $orderreference,
        public readonly string $publicreference,
        public readonly ?int $userid,
        public readonly string $customername,
        public readonly string $customeremail,
        public readonly string $category,
        public readonly string $subject,
        public readonly string $message,
        public readonly string $paymentstatus = '',
        public readonly string $fulfillmentstatus = '',
        public readonly array $products = []
    ) {
        if (!in_array($category, self::categories(), true)) {
            throw new \invalid_parameter_exception('Unsupported support request category.');
        }
        if (!validate_email($customeremail)) {
            throw new \invalid_parameter_exception('A valid customer email is required.');
        }
        if (trim($subject) === '' || trim($message) === '') {
            throw new \invalid_parameter_exception('A subject and message are required.');
        }
    }

    /** @return string[] */
    public static function categories(): array {
        return [
            self::CATEGORY_PAYMENT,
            self::CATEGORY_COURSE_ACCESS,
            self::CATEGORY_DOWNLOAD,
            self::CATEGORY_INVOICE,
            self::CATEGORY_ACCOUNT,
            self::CATEGORY_TECHNICAL,
            self::CATEGORY_COURSE_QUESTION,
            self::CATEGORY_OTHER,
        ];
    }
}
