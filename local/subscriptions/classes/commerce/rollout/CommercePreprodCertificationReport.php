<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommercePreprodCertificationReport {
 public function __construct(private readonly array $evidence) {}
 public function missing(array $required): array { $passed=[]; foreach($this->evidence as $item){if($item->passed())$passed[$item->get_key()]=true;}
  return array_values(array_filter($required,static fn(string $key):bool=>empty($passed[$key]))); }
 public function is_ready(array $required): bool{return $this->missing($required)===[];}
}
