<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\entitlement\domain\CommerceEntitlementGrant;
use local_subscriptions\commerce\fulfillment\native\CommerceNativeFulfillmentContext;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalAccessRepository;
use local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler;

/** @covers \local_subscriptions\commerce\fulfillment\native\digital\CommerceDigitalDownloadFulfillmentHandler */
final class commerce_native_digital_fulfillment_test extends advanced_testcase {
    public function test_dry_run_does_not_persist_access(): void {
        $repository = new class implements CommerceDigitalAccessRepository {
            public int $writes = 0;
            public function find_by_grant_reference(string $grantreference): ?\stdClass { return null; }
            public function grant(CommerceEntitlementGrant $grant, string $token, ?int $maxdownloads, int $now): array {
                $this->writes++;
                return [];
            }
        };

        $result = (new CommerceDigitalDownloadFulfillmentHandler($repository))->fulfill(
            $this->grant(['maxdownloads' => 5]),
            CommerceNativeFulfillmentContext::dry_run('f4-dry', time(), null, 'phpunit')
        );

        self::assertTrue($result->is_skipped());
        self::assertSame(0, $repository->writes);
        self::assertSame(5, $result->get_payload()['maxdownloads']);
        self::assertSame('would_create', $result->get_payload()['action']);
    }

    public function test_runtime_persists_native_digital_access(): void {
        $repository = new class implements CommerceDigitalAccessRepository {
            public ?array $write = null;
            public function find_by_grant_reference(string $grantreference): ?\stdClass { return null; }
            public function grant(CommerceEntitlementGrant $grant, string $token, ?int $maxdownloads, int $now): array {
                $this->write = compact('token', 'maxdownloads', 'now');
                return ['action' => 'created', 'accessid' => 9, 'token' => $token];
            }
        };

        $result = (new CommerceDigitalDownloadFulfillmentHandler($repository))->fulfill(
            $this->grant(),
            CommerceNativeFulfillmentContext::runtime('f4-runtime', time(), 2, 'phpunit')
        );

        self::assertTrue($result->is_completed());
        self::assertSame('created', $result->get_payload()['action']);
        self::assertSame(64, strlen((string) $repository->write['token']));
        self::assertNull($repository->write['maxdownloads']);
    }

    public function test_existing_access_reuses_its_token(): void {
        $repository = new class implements CommerceDigitalAccessRepository {
            public string $token = '';
            public function find_by_grant_reference(string $grantreference): ?\stdClass {
                return (object) ['downloadtoken' => str_repeat('a', 64)];
            }
            public function grant(CommerceEntitlementGrant $grant, string $token, ?int $maxdownloads, int $now): array {
                $this->token = $token;
                return ['action' => 'unchanged', 'accessid' => 4, 'token' => $token];
            }
        };

        (new CommerceDigitalDownloadFulfillmentHandler($repository))->fulfill(
            $this->grant(),
            CommerceNativeFulfillmentContext::runtime('f4-repeat', time(), null, 'phpunit')
        );

        self::assertSame(str_repeat('a', 64), $repository->token);
    }

    public function test_negative_download_limit_is_rejected(): void {
        $repository = new class implements CommerceDigitalAccessRepository {
            public function find_by_grant_reference(string $grantreference): ?\stdClass { return null; }
            public function grant(CommerceEntitlementGrant $grant, string $token, ?int $maxdownloads, int $now): array { return []; }
        };
        $this->expectException(\coding_exception::class);
        (new CommerceDigitalDownloadFulfillmentHandler($repository))->fulfill(
            $this->grant(['maxdownloads' => -1]),
            CommerceNativeFulfillmentContext::dry_run('f4-invalid', time(), null, 'phpunit')
        );
    }

    private function grant(array $configuration = []): CommerceEntitlementGrant {
        return new CommerceEntitlementGrant(
            'grant-f4', 'purchase-f4', 'item-f4', 'DIGITAL.VERBES', 'digital_download',
            'digital-product:2', 1, 2, 'student@example.com', time(), null, $configuration
        );
    }
}
