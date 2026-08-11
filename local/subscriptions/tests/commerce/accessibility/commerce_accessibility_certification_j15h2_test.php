<?php

namespace local_subscriptions\commerce\accessibility;

defined('MOODLE_INTERNAL') || die();

/**
 * Static certification checks for the J15H.2 accessibility pass.
 *
 * @coversNothing
 */
final class commerce_accessibility_certification_j15h2_test extends \advanced_testcase {
    public function test_shared_dialogs_expose_accessible_names_and_descriptions(): void {
        global $CFG;

        $guestdialog = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/commerce/guest_account_dialog.mustache'
        );
        $providerdialogs = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/provider_experience.mustache'
        );

        $this->assertStringContainsString('aria-modal="true"', $guestdialog);
        $this->assertStringContainsString('aria-describedby="commerce-account-dialog-description"', $guestdialog);
        $this->assertStringContainsString('data-account-primary', $guestdialog);

        $this->assertStringContainsString('aria-labelledby="commerce-provider-experience-title"', $providerdialogs);
        $this->assertStringContainsString('aria-describedby="commerce-provider-experience-message"', $providerdialogs);
        $this->assertStringContainsString('aria-labelledby="commerce-provider-currency-title"', $providerdialogs);
    }

    public function test_showroom_has_localised_skip_link_and_semantic_statistics(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        $this->assertStringContainsString('commerce_a11y_skip_to_content', $template);
        $this->assertStringContainsString('role="list"', $template);
        $this->assertStringContainsString('role="listitem"', $template);
        $this->assertStringNotContainsString('aria-label="CampusFR sur ordinateur et mobile"', $template);
    }

    public function test_checkout_required_fields_expose_aria_required(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertGreaterThanOrEqual(4, substr_count($template, 'aria-required="true"'));
    }

    public function test_global_commerce_focus_and_forced_colours_rules_exist(): void {
        global $CFG;

        $styles = file_get_contents($CFG->dirroot . '/local/subscriptions/styles.css');

        $this->assertStringContainsString('CampusFR J15H.2 — Commerce accessibility certification.', $styles);
        $this->assertStringContainsString(':focus-visible', $styles);
        $this->assertStringContainsString('@media (forced-colors: active)', $styles);
    }
}
