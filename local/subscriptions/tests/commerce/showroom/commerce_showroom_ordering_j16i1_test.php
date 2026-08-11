<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_ordering_j16i1_test extends \advanced_testcase {
    public function test_runtime_exposes_saved_enabled_blocks_in_sequence_order(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $showroomid = (int)$DB->insert_record('local_subs_showroom', (object)[
            'showroomkey' => 'ordering-current-contract', 'status' => 'published', 'name' => 'Ordering',
            'template' => 'third_group_verbs', 'productsjson' => '[]', 'settingsjson' => '{}',
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        foreach ([['hero', 10], ['offers', 20], ['faq', 30], ['support', 40]] as [$type, $sort]) {
            $DB->insert_record('local_subs_showroom_block', (object)[
                'showroomid' => $showroomid, 'blockkey' => $type, 'blocktype' => $type,
                'sortorder' => $sort, 'enabled' => 1, 'configjson' => '{}',
                'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $runtime = CommerceShowroomRuntimeBlockSet::load($DB, 'ordering-current-contract');
        self::assertSame(['hero', 'stage_method', 'exercise_explorer', 'offers', 'faq', 'support'], $runtime->sequence());
        $data = $runtime->to_template_data();
        self::assertSame('hero,stage_method,exercise_explorer,offers,faq,support', $data['showroomblocksequence']);
        self::assertCount(6, $data['showroomorderedblocks']);
    }
}
