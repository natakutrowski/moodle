<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\digital\DigitalFulfillmentGateway;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionFulfillmentGateway;
use local_subscriptions\constants\Status;

/**
 * Tests concrete Commerce fulfillment handlers.
 *
 * @covers \local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler
 * @covers \local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler
 */
final class commerce_fulfillment_handlers_test extends advanced_testcase {

    public function test_subscription_handler_fulfills_once(): void {
        $gateway = new class implements SubscriptionFulfillmentGateway {
            public int $calls = 0;

            public function find_by_transaction(
                string $transactionid
            ): ?\stdClass {
                return null;
            }

            public function fulfill(
                CommerceFulfillmentOperation $operation,
                CommerceFulfillmentContext $context
            ): array {
                $this->calls++;
                return [
                    'status' => 'created',
                    'subscription' => (object)['id' => 501],
                    'userid' => 96,
                    'planid' => 14,
                    'start_date' => 1700000000,
                    'end_date' => 1731536000,
                ];
            }
        };

        $handler = new SubscriptionEnrolmentFulfillmentHandler($gateway);
        $result = $handler->fulfill(
            new CommerceFulfillmentOperation(
                'purchase-request:test:item:1',
                'subscription_enrolment',
                ['userid' => 96, 'planid' => 14]
            ),
            $this->create_context()
        );

        $this->assertSame(1, $gateway->calls);
        $this->assertSame(
            CommerceFulfillmentResult::STATUS_COMPLETED,
            $result->get_status()
        );
        $this->assertSame(501, $result->get_metadata()['subscriptionid']);
    }

    public function test_subscription_handler_skips_existing_transaction(): void {
        $gateway = new class implements SubscriptionFulfillmentGateway {
            public function find_by_transaction(
                string $transactionid
            ): ?\stdClass {
                return (object)['id' => 501];
            }

            public function fulfill(
                CommerceFulfillmentOperation $operation,
                CommerceFulfillmentContext $context
            ): array {
                throw new \RuntimeException('Must not be called.');
            }
        };

        $result = (new SubscriptionEnrolmentFulfillmentHandler($gateway))
            ->fulfill(
                new CommerceFulfillmentOperation(
                    'purchase-request:test:item:1',
                    'subscription_enrolment'
                ),
                $this->create_context()
            );

        $this->assertSame(
            CommerceFulfillmentResult::STATUS_SKIPPED,
            $result->get_status()
        );
    }

    public function test_digital_handler_grants_download_access(): void {
        $gateway = new class implements DigitalFulfillmentGateway {
            public function find_payment_request(
                int $paymentrequestid
            ): ?\stdClass {
                return (object)[
                    'id' => $paymentrequestid,
                    'productid' => 8,
                    'status' => Status::PENDING,
                    'download_token' => null,
                ];
            }

            public function fulfill(
                CommerceFulfillmentOperation $operation,
                CommerceFulfillmentContext $context
            ): \stdClass {
                return (object)[
                    'id' => 91,
                    'productid' => 8,
                    'status' => Status::PAID,
                    'download_token' => 'token-test',
                    'download_token_expires' => null,
                ];
            }
        };

        $result = (new DigitalDownloadFulfillmentHandler($gateway))
            ->fulfill(
                new CommerceFulfillmentOperation(
                    'purchase-request:test:item:1',
                    'digital_download',
                    ['productid' => 8]
                ),
                $this->create_context()
            );

        $this->assertSame(
            CommerceFulfillmentResult::STATUS_COMPLETED,
            $result->get_status()
        );
        $this->assertSame(
            'token-test',
            $result->get_metadata()['download_token']
        );
    }

    public function test_digital_handler_skips_existing_access(): void {
        $gateway = new class implements DigitalFulfillmentGateway {
            public function find_payment_request(
                int $paymentrequestid
            ): ?\stdClass {
                return (object)[
                    'id' => $paymentrequestid,
                    'productid' => 8,
                    'status' => Status::PAID,
                    'download_token' => 'existing-token',
                ];
            }

            public function fulfill(
                CommerceFulfillmentOperation $operation,
                CommerceFulfillmentContext $context
            ): \stdClass {
                throw new \RuntimeException('Must not be called.');
            }
        };

        $result = (new DigitalDownloadFulfillmentHandler($gateway))
            ->fulfill(
                new CommerceFulfillmentOperation(
                    'purchase-request:test:item:1',
                    'digital_download',
                    ['productid' => 8]
                ),
                $this->create_context()
            );

        $this->assertSame(
            CommerceFulfillmentResult::STATUS_SKIPPED,
            $result->get_status()
        );
    }

    private function create_context(): CommerceFulfillmentContext {
        return CommerceFulfillmentContext::confirmed(
            'purchase-request:test',
            'stripe',
            'pi_test',
            12000,
            'EUR',
            1700000000,
            91,
            'phpunit'
        );
    }
}
