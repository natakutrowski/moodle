<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\readiness;

use local_subscriptions\commerce\certification\CommerceCertificationReport;

/**
 * Read-only Git and release-state auditor for 7.95F8A.
 */
final class CommerceGitReadinessAuditor {
    /** @var callable(string): array{code:int, output:string} */
    private $runner;

    public function __construct(
        private readonly string $repositoryroot,
        ?callable $runner = null
    ) {
        $this->runner = $runner ?? static function(string $command): array {
            $output = [];
            $code = 0;
            exec($command . ' 2>&1', $output, $code);
            return ['code' => $code, 'output' => trim(implode("\n", $output))];
        };
    }

    public function audit(?string $expectedbranch = null): CommerceCertificationReport {
        $report = new CommerceCertificationReport('7.95F8A');
        $root = realpath($this->repositoryroot) ?: $this->repositoryroot;
        $report->add_inventory('repository_root', $root);

        $inside = $this->git('rev-parse --is-inside-work-tree');
        $isrepo = $inside['code'] === 0 && trim($inside['output']) === 'true';
        $report->add_inventory('git_repository', $isrepo);
        if (!$isrepo) {
            $report->add_issue('blocking', 'not_git_repository', 'The Moodle root is not a readable Git working tree.', ['root' => $root]);
            return $report;
        }

        $branch = $this->git('branch --show-current')['output'];
        $commit = $this->git('rev-parse HEAD')['output'];
        $status = $this->git('status --porcelain')['output'];
        $upstream = $this->git('rev-parse --abbrev-ref --symbolic-full-name @{u}');
        $tags = $this->git('tag --points-at HEAD')['output'];

        $report->add_inventory('branch', $branch);
        $report->add_inventory('commit', $commit);
        $report->add_inventory('head_tags', $tags === '' ? [] : preg_split('/\R/', $tags));
        $report->add_inventory('working_tree_clean', $status === '');
        $report->add_inventory('upstream', $upstream['code'] === 0 ? $upstream['output'] : null);

        if ($status !== '') {
            $lines = preg_split('/\R/', $status) ?: [];
            $report->add_issue('blocking', 'dirty_working_tree', 'The Git working tree contains uncommitted changes.', [
                'count' => count($lines),
                'sample' => array_slice($lines, 0, 20),
            ]);
        }

        if ($expectedbranch !== null && $expectedbranch !== '' && $branch !== $expectedbranch) {
            $report->add_issue('blocking', 'unexpected_branch', 'The current branch does not match the requested release branch.', [
                'expected' => $expectedbranch,
                'actual' => $branch,
            ]);
        }

        if ($upstream['code'] !== 0 || $upstream['output'] === '') {
            $report->add_issue('important', 'missing_upstream', 'The current branch has no configured upstream.', ['branch' => $branch]);
            $report->add_inventory('ahead', null);
            $report->add_inventory('behind', null);
            return $report;
        }

        $counts = $this->git('rev-list --left-right --count @{u}...HEAD');
        $behind = null;
        $ahead = null;
        if ($counts['code'] === 0 && preg_match('/^(\d+)\s+(\d+)$/', trim($counts['output']), $matches)) {
            $behind = (int)$matches[1];
            $ahead = (int)$matches[2];
        }
        $report->add_inventory('ahead', $ahead);
        $report->add_inventory('behind', $behind);

        if ($behind === null || $ahead === null) {
            $report->add_issue('important', 'upstream_comparison_failed', 'Git could not compare HEAD with its upstream.', []);
        } else {
            if ($behind > 0) {
                $report->add_issue('blocking', 'branch_behind_upstream', 'The current branch is behind its upstream.', ['behind' => $behind]);
            }
            if ($ahead > 0) {
                $report->add_issue('important', 'unpushed_commits', 'The current branch contains commits not present on its upstream.', ['ahead' => $ahead]);
            }
        }

        return $report;
    }

    /** @return array{code:int, output:string} */
    private function git(string $arguments): array {
        $command = 'git -C ' . escapeshellarg($this->repositoryroot) . ' ' . $arguments;
        return ($this->runner)($command);
    }
}
