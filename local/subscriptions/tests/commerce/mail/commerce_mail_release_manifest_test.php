<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\certification\CommerceMailReleaseManifest;

final class commerce_mail_release_manifest_test extends \advanced_testcase {

    public function test_release_manifest_is_frozen_and_complete(): void {
        global $CFG;

        $this->assertSame('Transactional Mail Engine', CommerceMailReleaseManifest::RELEASE);
        $this->assertSame('CERTIFIED', CommerceMailReleaseManifest::STATUS);
        $this->assertSame('FROZEN', CommerceMailReleaseManifest::LIFECYCLE);
        $this->assertSame('2026-08-01', CommerceMailReleaseManifest::FROZEN_ON);
        $this->assertGreaterThanOrEqual(20, count(CommerceMailReleaseManifest::required_files()));
        $this->assertSame([], CommerceMailReleaseManifest::missing_files(
            $CFG->dirroot . '/local/subscriptions'
        ));
    }

    public function test_manifest_export_is_machine_readable(): void {
        $export = CommerceMailReleaseManifest::export();

        $this->assertSame('Transactional Mail Engine', $export['release']);
        $this->assertSame('CERTIFIED', $export['status']);
        $this->assertSame('FROZEN', $export['lifecycle']);
        $this->assertGreaterThanOrEqual(20, $export['requiredfiles']);
    }
}
