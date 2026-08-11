<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\readiness\CommerceGitReadinessAuditor;

final class commerce_795f8a_git_readiness_test extends advanced_testcase {
    public function test_clean_synced_release_branch_is_certifiable(): void {
        $runner = static function(string $command): array {
            $responses = [
                'rev-parse --is-inside-work-tree' => 'true',
                'branch --show-current' => 'commerce-7.95',
                'rev-parse HEAD' => str_repeat('a', 40),
                'status --porcelain' => '',
                'rev-parse --abbrev-ref --symbolic-full-name @{u}' => 'origin/commerce-7.95',
                'tag --points-at HEAD' => '',
                'rev-list --left-right --count @{u}...HEAD' => "0\t0",
            ];
            foreach ($responses as $needle => $output) {
                if (str_contains($command, $needle)) {
                    return ['code' => 0, 'output' => $output];
                }
            }
            return ['code' => 1, 'output' => 'unexpected command'];
        };
        $report = (new CommerceGitReadinessAuditor('/tmp/project', $runner))->audit('commerce-7.95')->to_array();
        $this->assertTrue($report['certifiable']);
        $this->assertSame(0, $report['summary']['blocking']);
    }

    public function test_dirty_tree_is_blocking(): void {
        $runner = static function(string $command): array {
            if (str_contains($command, 'rev-parse --is-inside-work-tree')) {
                return ['code' => 0, 'output' => 'true'];
            }
            if (str_contains($command, 'status --porcelain')) {
                return ['code' => 0, 'output' => ' M local/subscriptions/version.php'];
            }
            if (str_contains($command, 'branch --show-current')) {
                return ['code' => 0, 'output' => 'commerce-7.95'];
            }
            if (str_contains($command, 'rev-parse --abbrev-ref')) {
                return ['code' => 1, 'output' => ''];
            }
            return ['code' => 0, 'output' => ''];
        };
        $report = (new CommerceGitReadinessAuditor('/tmp/project', $runner))->audit()->to_array();
        $this->assertFalse($report['certifiable']);
        $this->assertSame(1, $report['summary']['blocking']);
    }
}
