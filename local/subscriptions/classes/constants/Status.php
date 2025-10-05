<?php
namespace local_subscriptions\constants;
defined('MOODLE_INTERNAL') || die();
final class Status {
    public const ACTIVE   = 'active';

    public const INACTIVE   = 'inactive';
    public const QUEUED   = 'queued';
    public const EXPIRED  = 'expired';
    public const REPLACED = 'replaced';
    public const CANCELED = 'canceled';
    public const PENDING = 'pending';
    public const FAILED = 'failed';
    public const ERROR = 'error';
    public const PAID = 'paid';
    public const COMPLETED = 'completed';
    public const SUSPENDED = 'suspended';
}
