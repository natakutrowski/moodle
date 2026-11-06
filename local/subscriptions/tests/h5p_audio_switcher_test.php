<?php
// PHPUnit checks for CampusFR H5P script selection and OFF fallback.
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** @covers \local_subscriptions\h5p\audio_switcher */
final class h5p_audio_switcher_test extends \advanced_testcase {
    private function libraries(): array {
        return [
            'H5P.Audio' => ['majorVersion' => 1, 'minorVersion' => 5],
            'H5P.MemoryGame' => ['majorVersion' => 1, 'minorVersion' => 3],
        ];
    }
    private function scripts(): array {
        return [(object)['path' => '/official/h5p-cachedassets.js', 'version' => '?v=original']];
    }
    private function resolve(array &$scripts, array $libraries, string $embedtype = 'iframe'): void {
        \local_subscriptions\h5p\audio_switcher::alter_scripts($scripts, $libraries, $embedtype);
    }
    public function test_off_keeps_official_script_untouched(): void {
        $this->resetAfterTest();
        set_config('h5p_audio_campusfr_enabled', 0, 'local_subscriptions');
        set_config('h5p_memory_campusfr_enabled', 0, 'local_subscriptions');
        $scripts = $this->scripts();
        $before = serialize($scripts);
        $this->resolve($scripts, $this->libraries());
        $this->assertSame($before, serialize($scripts));
    }
    public function test_audio_on_adds_only_audio(): void {
        $this->resetAfterTest();
        set_config('h5p_audio_campusfr_enabled', 1, 'local_subscriptions');
        set_config('h5p_memory_campusfr_enabled', 0, 'local_subscriptions');
        $scripts = $this->scripts();
        $this->resolve($scripts, $this->libraries());
        $this->assertCount(2, $scripts);
        $this->assertStringContainsString('audio-campusfr-1.5.37-ui3.js', $scripts[1]->path);
    }
    public function test_memory_on_adds_only_memory(): void {
        $this->resetAfterTest();
        set_config('h5p_audio_campusfr_enabled', 0, 'local_subscriptions');
        set_config('h5p_memory_campusfr_enabled', 1, 'local_subscriptions');
        $scripts = $this->scripts();
        $this->resolve($scripts, $this->libraries());
        $this->assertCount(2, $scripts);
        $this->assertStringContainsString('memory-card-campusfr-1.3.55.js', $scripts[1]->path);
    }
    public function test_both_on_are_appended_once_in_order(): void {
        $this->resetAfterTest();
        set_config('h5p_audio_campusfr_enabled', 1, 'local_subscriptions');
        set_config('h5p_memory_campusfr_enabled', 1, 'local_subscriptions');
        $scripts = $this->scripts();
        $this->resolve($scripts, $this->libraries());
        $this->resolve($scripts, $this->libraries());
        $this->assertCount(3, $scripts);
        $this->assertStringContainsString('audio-campusfr-1.5.37-ui3.js', $scripts[1]->path);
        $this->assertStringContainsString('memory-card-campusfr-1.3.55.js', $scripts[2]->path);
    }
    public function test_unrelated_library_and_editor_not_modified(): void {
        $this->resetAfterTest();
        set_config('h5p_audio_campusfr_enabled', 1, 'local_subscriptions');
        set_config('h5p_memory_campusfr_enabled', 1, 'local_subscriptions');
        $scripts = $this->scripts();
        $this->resolve($scripts, ['H5P.Audio' => ['majorVersion' => 1, 'minorVersion' => 2]]);
        $this->assertCount(1, $scripts);
        $this->resolve($scripts, $this->libraries(), 'editor');
        $this->assertCount(1, $scripts);
    }
}
