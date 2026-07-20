<?php

namespace local_subscriptions\crm\admin_tools\services;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core\lock\lock_config;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminTool;
use local_subscriptions\crm\admin_tools\AdminToolDataSanitizer;
use local_subscriptions\crm\admin_tools\AdminToolExecutionContext;
use local_subscriptions\crm\admin_tools\AdminToolExecutionResult;
use local_subscriptions\crm\admin_tools\repositories\AdminToolRunRepository;

/**
 * Executes administrative tools with capability checks,
 * locking and persistent execution history.
 */
final class AdminToolRunner {

    public function __construct(
        private readonly AdminToolRunRepository $repository =
            new AdminToolRunRepository(),

        private readonly AdminToolDataSanitizer $sanitizer =
            new AdminToolDataSanitizer()
    ) {
    }

    public function run(
        AdminTool $tool,
        int $actorid,
        array $parameters = []
    ): AdminToolExecutionResult {
        $context = context_system::instance();

        require_capability(
            Capabilities::MANAGE_CRM_ADMIN_TOOLS,
            $context
        );

        require_capability(
            $tool->required_capability(),
            $context
        );

        if ($actorid <= 0) {
            throw new \coding_exception(
                'Administrative tool actor ID must be greater than zero.'
            );
        }

        $requestid =
            bin2hex(
                random_bytes(16)
            );

        $sanitizedparameters =
            $this->sanitizer->sanitize(
                $parameters
            );

        $started =
            microtime(true);

        $runid =
            $this->repository
                ->create_running(
                    $tool->key(),
                    $actorid,
                    $tool->risk_level(),
                    $requestid,
                    $sanitizedparameters
                );

        try {
            $lockfactory =
                lock_config::get_lock_factory(
                    'local_subscriptions_admin_tools'
                );

            $lock =
                $lockfactory->get_lock(
                    $tool->lock_key(),
                    0
                );
        } catch (\Throwable $exception) {
            $message =
                $this->sanitizer
                    ->sanitize_message(
                        $exception->getMessage()
                    );

            $this->repository->fail(
                $runid,
                $message !== ''
                    ? $message
                    : get_class($exception),
                $this->duration_ms($started)
            );

            debugging(
                'Unable to acquire CRM administrative tool lock for ' .
                $tool->key() .
                '.',
                DEBUG_DEVELOPER
            );

            return
                AdminToolExecutionResult::failed(
                    get_string(
                        'crm_admin_tool_failed',
                        'local_subscriptions'
                    )
                );
        }

        if (!$lock) {
            $result =
                AdminToolExecutionResult::busy(
                    get_string(
                        'crm_admin_tool_busy',
                        'local_subscriptions'
                    )
                );

            $this->repository->complete(
                $runid,
                $result->status,
                $this->sanitizer->sanitize(
                    $result->to_array()
                ),
                $this->duration_ms($started)
            );

            return $result;
        }

        try {
            $executioncontext =
                new AdminToolExecutionContext(
                    $actorid,
                    $requestid,
                    $parameters
                );

            $result =
                $tool->execute(
                    $executioncontext
                );

            $sanitizedresult =
                $this->sanitizer->sanitize(
                    $result->to_array()
                );

            $this->repository->complete(
                $runid,
                $result->status,
                $sanitizedresult,
                $this->duration_ms($started)
            );

            return $result;
        } catch (\Throwable $exception) {
            $message =
                $this->sanitizer
                    ->sanitize_message(
                        $exception->getMessage()
                    );

            if ($message === '') {
                $message =
                    get_class($exception);
            }

            $this->repository->fail(
                $runid,
                $message,
                $this->duration_ms($started)
            );

            debugging(
                'CRM administrative tool failed: ' .
                $tool->key() .
                ' — ' .
                $message,
                DEBUG_DEVELOPER
            );

            return
                AdminToolExecutionResult::failed(
                    get_string(
                        'crm_admin_tool_failed',
                        'local_subscriptions'
                    )
                );
        } finally {
            $lock->release();
        }
    }

    private function duration_ms(
        float $started
    ): int {
        return max(
            0,
            (int)round(
                (
                    microtime(true) -
                    $started
                ) * 1000
            )
        );
    }
}