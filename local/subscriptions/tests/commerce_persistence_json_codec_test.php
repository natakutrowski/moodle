<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceJsonCodec;

final class commerce_persistence_json_codec_test extends \advanced_testcase {
    public function test_encoding_is_deterministic_and_unicode_safe(): void {
        $codec = new CommercePersistenceJsonCodec();

        $first = $codec->encode(['z' => 1, 'a' => ['école' => 'français', 'b' => 2]]);
        $second = $codec->encode(['a' => ['b' => 2, 'école' => 'français'], 'z' => 1]);

        $this->assertSame($first, $second);
        $this->assertStringContainsString('français', $first);
        $this->assertSame(['a' => ['b' => 2, 'école' => 'français'], 'z' => 1], $codec->decode($first));
    }

    public function test_invalid_json_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        (new CommercePersistenceJsonCodec())->decode('{invalid');
    }
}
