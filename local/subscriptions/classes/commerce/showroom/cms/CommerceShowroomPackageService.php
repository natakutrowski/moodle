<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/** Import/export service for portable Showroom definitions. */
final class CommerceShowroomPackageService {
    public function __construct(private readonly CommerceShowroomCmsRepository $repository) {
    }

    /** @return array<string,mixed> */
    public function export(int $showroomid): array {
        $showroom = $this->repository->get($showroomid);
        if ($showroom === null) {
            throw new \invalid_parameter_exception('Unknown showroom.');
        }
        $showroomdata = (array)$showroom;
        unset($showroomdata['id'], $showroomdata['timecreated'], $showroomdata['timemodified'], $showroomdata['usermodified']);
        $blocks = [];
        foreach ($this->repository->blocks($showroomid) as $block) {
            $data = (array)$block;
            unset($data['id'], $data['showroomid'], $data['timecreated'], $data['timemodified'], $data['usermodified']);
            $blocks[] = $data;
        }
        return [
            'format' => 'campusfr-showroom',
            'version' => 1,
            'exportedat' => time(),
            'showroom' => $showroomdata,
            'blocks' => $blocks,
        ];
    }

    public function import(string $json, int $userid): int {
        $package = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($package) || ($package['format'] ?? '') !== 'campusfr-showroom' || !isset($package['showroom'])) {
            throw new \invalid_parameter_exception('Invalid showroom package.');
        }
        $showroom = (array)$package['showroom'];
        $basekey = clean_param((string)($showroom['showroomkey'] ?? 'imported-showroom'), PARAM_ALPHANUMEXT);
        $showroom['showroomkey'] = $this->unique_showroom_key($basekey);
        $showroom['name'] = trim((string)($showroom['name'] ?? 'Showroom importé')) . ' (import)';
        $showroom['status'] = 'draft';
        foreach (['slugfr', 'slugen', 'slugru'] as $slugfield) {
            $showroom[$slugfield] = '';
        }
        $showroomid = $this->repository->save($showroom, $userid);
        foreach ((array)($package['blocks'] ?? []) as $block) {
            if (!is_array($block) || !CommerceShowroomBlockTypeRegistry::exists((string)($block['blocktype'] ?? ''))) {
                continue;
            }
            $block['blockkey'] = '';
            $this->repository->save_block($showroomid, $block, $userid);
        }
        return $showroomid;
    }

    private function unique_showroom_key(string $base): string {
        $base = $base !== '' ? $base : 'imported-showroom';
        $candidate = $base;
        $counter = 2;
        while ($this->repository->get_by_key($candidate) !== null) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }
        return $candidate;
    }
}
