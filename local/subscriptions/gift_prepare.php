<?php
// CampusFR Gifts G5B.1 — isolated DEV-only preparation preview, no checkout writes.
require_once(__DIR__ . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\gift\CommerceGiftCommercialRuntimeGate;

require_login();
if (!(new \local_subscriptions\commerce\gift\CommerceGiftFeatureToggle())->can_issue()) {
    throw new moodle_exception('nopermissions');
}
try {
    CommerceGiftCommercialRuntimeGate::assert_buyer($DB, (int)$USER->id, (string)$USER->email);
} catch (\Throwable $e) {
    throw new moodle_exception('nopermissions');
}
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');

$component = 'local_subscriptions';
$t = static fn(string $key): string => get_string('cfr_gift_prepare_' . $key, $component);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/gift_prepare.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($t('title'));
$PAGE->set_heading($t('title'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/gift_prepare.css', ['v' => (string)filemtime(__DIR__ . '/styles/gift_prepare.css')]));

// G5B4B1: server-observed Showroom currency, bound to this browser session
// and buyer. The form, URL and saved preview are never currency authorities.
$giftcurrencycontext = \local_subscriptions\commerce\gift\CommerceGiftShowroomCurrencyContext::resolve(
    $SESSION, (int)$USER->id, time()
);
// G6D3D: When a visitor chose RUB/EUR on the public Advent showroom before
// signing in, carry over only the freshly observed server-side selection.
// Require_login() has already completed above; bind the currency to THIS buyer.
// Native Commerce still independently resolves the authoritative price/provider.
if (!$giftcurrencycontext['fromshowroom']) {
    $guestselection = $SESSION->cfr_g6d3d_guestgiftcurrency ?? null;
    unset($SESSION->cfr_g6d3d_guestgiftcurrency);
    $now = time();
    if (is_object($guestselection)
        && (string)($guestselection->showroom ?? '') === 'avent_2026'
        && in_array((string)($guestselection->currency ?? ''), ['EUR', 'RUB'], true)
        && (int)($guestselection->at ?? 0) <= $now
        && (int)($guestselection->at ?? 0) >= $now - 1200) {
        \local_subscriptions\commerce\gift\CommerceGiftShowroomCurrencyContext::capture(
            $SESSION, (int)$USER->id, (string)$guestselection->currency, $now
        );
        $giftcurrencycontext = \local_subscriptions\commerce\gift\CommerceGiftShowroomCurrencyContext::resolve(
            $SESSION, (int)$USER->id, $now
        );
    }
}
$giftcurrency = $giftcurrencycontext['currency'];
$values = [
    'recipientname' => '', 'recipientemail' => '', 'deliverymode' => 'email',
    'personalmessage' => '', 'locale' => in_array(substr(current_language(), 0, 2), ['fr', 'en', 'ru'], true) ? substr(current_language(), 0, 2) : 'fr', 'schedule' => 'now',
    'deliverydate' => '', 'currency' => $giftcurrency,
];
$submitted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$errors = [];
$parseddate = null;
// The review's Modify action restores the last short-lived, buyer-bound draft
// instead of unexpectedly clearing all fields. No gift or payment is written.
$editing = !$submitted && optional_param('edit', 0, PARAM_BOOL) === 1;
if ($editing && !empty($SESSION->cfr_g5b2_preview)) {
    $saved = $SESSION->cfr_g5b2_preview;
    if ((int)($saved['buyerid'] ?? 0) === (int)$USER->id
            && (int)($saved['at'] ?? 0) >= time() - 1200
            && is_array($saved['values'] ?? null)) {
        foreach (array_keys($values) as $key) {
            if (isset($saved['values'][$key]) && is_scalar($saved['values'][$key])) {
                $values[$key] = (string)$saved['values'][$key];
            }
        }
    }
}

$values['currency'] = $giftcurrency;

if ($submitted) {
    require_sesskey();
    foreach ($values as $key => $value) {
        // Currency comes only from the current buyer-bound Showroom context.
        if ($key === 'currency') { continue; }
        $values[$key] = trim((string)optional_param($key, $value, PARAM_RAW));
    }
    $values['currency'] = $giftcurrency;
    $validation = \local_subscriptions\commerce\gift\CommerceGiftPrepareInputValidator::validate($values, time());
    $values = $validation['values'];
    $errors = array_map(static fn(string $key): string => $t($key), $validation['errors']);
    $parseddate = $validation['scheduledat'] === null ? null
        : (new DateTimeImmutable('@' . $validation['scheduledat']))->setTimezone(new DateTimeZone('Europe/Paris'));
}

$esc = static fn(string $text): string => s($text);
$preview = $submitted && !$errors;
// A short-lived, buyer-bound server session context is used only to bridge the
// review page to the DEV checkout. Form values are revalidated at handoff.
if (!$editing) {
    unset($SESSION->cfr_g5b2_preview);
}
if ($preview) {
    $SESSION->cfr_g5b2_preview = [
        'buyerid' => (int)$USER->id,
        'values' => $values,
        'scheduledat' => (int)($validation['scheduledat'] ?? 0),
        'at' => time(),
    ];
}

$mode = $values['deliverymode'];

// Present, but never checkout or save this as an authorization. This page is a
// strict UX stage, not an issuance endpoint; later G5B.2 will bind it to purchase.
echo $OUTPUT->header();
?>
<div class="cf-giftprep">
  <div class="cf-giftprep-hero">
    <div class="cf-giftprep-eyebrow"><?php echo $esc($t('eyebrow')); ?></div>
    <h2><?php echo $esc($t('title')); ?></h2>
    <?php if (!$preview): ?><p><?php echo $esc($t('intro')); ?></p><?php endif; ?>
    <div class="cf-giftprep-steps">
      <span>1 · <?php echo $esc($t('step1')); ?></span>
      <span>2 · <?php echo $esc($t('step2')); ?></span>
      <span>3 · <?php echo $esc($t('step3')); ?></span>
    </div>
  </div>
  <?php if ($errors): ?>
    <div class="cf-giftprep-errors" role="alert">
      <?php foreach (array_unique($errors) as $error): ?>
        <p><?php echo $esc($error); ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($preview): ?>
    <div class="cf-giftprep-panel cf-giftprep-confirm">
      <h3><?php echo $esc($t('summary')); ?></h3>
      <p class="cf-giftprep-review-hint"><?php echo $esc($t('check')); ?></p>
      <dl>
        <dt><?php echo $esc($t('recipient')); ?></dt>
        <dd><?php echo $esc($mode === 'email' ? ($values['recipientname'] !== '' ? $values['recipientname'] . ' · ' : '') . $values['recipientemail'] : $t('mode_link')); ?></dd>
        <dt><?php echo $esc($t('personalmessage')); ?></dt>
        <dd class="cf-giftprep-note"><?php echo nl2br($esc($values['personalmessage'] !== '' ? $values['personalmessage'] : $t('no_message'))); ?></dd>
        <dt><?php echo $esc($t('schedule')); ?></dt>
        <dd><?php echo $esc($values['schedule'] === 'now' ? $t('immediate') : $parseddate->format('d/m/Y H:i') . ' · Europe/Paris'); ?></dd>
        <dt><?php echo $esc($t('locale')); ?></dt><dd><?php echo $esc(strtoupper($values['locale'])); ?></dd>
        <dt><?php echo $esc($t('currency')); ?></dt><dd><?php echo $esc($values['currency']); ?></dd>
      </dl>
      <div class="cf-giftprep-devnote"><?php echo $esc($t('checkout_info')); ?></div>
      <div class="cf-giftprep-actions">
        <a class="cf-giftprep-secondary" href="<?php echo (new moodle_url('/local/subscriptions/gift_prepare.php', ['edit' => 1]))->out(); ?>"><?php echo $esc($t('back')); ?></a>
        <?php if (!is_siteadmin((int)$USER->id)
            && (new \local_subscriptions\commerce\gift\CommerceGiftFeatureToggle())->can_issue()
            && in_array($values['currency'], ['EUR', 'RUB'], true)
            && $values['deliverymode'] === 'email'): ?>
          <form method="post" action="<?php echo (new moodle_url('/local/subscriptions/gift_prepare_checkout.php'))->out(); ?>">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
            <button type="submit" class="cf-giftprep-submit"><?php echo $esc($t('checkout_button')); ?></button>
          </form>
        <?php else: ?>
          <button type="button" class="cf-giftprep-submit" disabled aria-disabled="true" title="<?php echo $esc($t('checkout_unavailable')); ?>"><?php echo $esc($t('checkout_button')); ?></button>
        <?php endif; ?>
      </div>
      <?php if (is_siteadmin((int)$USER->id)
          || !(new \local_subscriptions\commerce\gift\CommerceGiftFeatureToggle())->can_issue()
          || !in_array($values['currency'], ['EUR', 'RUB'], true)
          || $values['deliverymode'] !== 'email'): ?>
        <p class="cf-giftprep-hint cf-giftprep-availability" role="status"><?php echo $esc($t('checkout_unavailable')); ?></p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <form method="post" action="<?php echo (new moodle_url('/local/subscriptions/gift_prepare.php'))->out(); ?>" class="cf-giftprep-panel" autocomplete="off">
      <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
      <section>
        <h3>01 · <?php echo $esc($t('step1')); ?></h3>
        <div class="cf-giftprep-grid">
          <label><?php echo $esc($t('receivername')); ?><input name="recipientname" maxlength="80" value="<?php echo $esc($values['recipientname']); ?>" placeholder="Camille"></label>
          <label><?php echo $esc($t('receiveremail')); ?><input type="email" name="recipientemail" maxlength="254" value="<?php echo $esc($values['recipientemail']); ?>" placeholder="camille@example.com"></label>
        </div>
        <p class="cf-giftprep-hint"><?php echo $esc($t('receiveremailhelp')); ?></p>
        <fieldset class="cf-giftprep-options"><legend><?php echo $esc($t('deliverymode')); ?></legend>
          <?php foreach (['email' => 'emailmode'] as $k => $v): ?>
            <label><input type="radio" name="deliverymode" value="<?php echo $k; ?>" <?php echo $values['deliverymode'] === $k ? 'checked' : ''; ?>> <?php echo $esc($t($v)); ?></label>
          <?php endforeach; ?>
        </fieldset>
      </section>
      <section>
        <h3>02 · <?php echo $esc($t('step2')); ?></h3>
        <label><?php echo $esc($t('message')); ?><textarea name="personalmessage" maxlength="500" rows="4"><?php echo $esc($values['personalmessage']); ?></textarea></label>
        <div class="cf-giftprep-language-row">
          <label><?php echo $esc($t('language')); ?><select name="locale">
            <?php foreach (['fr' => 'Français', 'en' => 'English', 'ru' => 'Русский'] as $k => $v): ?>
              <option value="<?php echo $k; ?>" <?php echo $values['locale'] === $k ? 'selected' : ''; ?>><?php echo $esc($v); ?></option>
            <?php endforeach; ?></select></label>
          <!-- Currency is chosen in the Showroom, never by a posted gift form field.
               The DEV handoff revalidates the buyer-bound server session and
               retrieves the authoritative EUR/Stripe or RUB/Alfa Native price. -->
          <!-- No currency input: server-side Showroom session is authoritative. -->
          <p class="cf-giftprep-hint cf-giftprep-currency-note"><?php echo $esc($t('currency_from_showroom_dev')); ?></p>
        </div>
      </section>
      <section>
        <h3>03 · <?php echo $esc($t('step3')); ?></h3>
        <fieldset class="cf-giftprep-options"><legend><?php echo $esc($t('datechoice')); ?></legend>
          <?php foreach (['now' => 'now', 'later' => 'later'] as $k => $v): ?>
            <label><input type="radio" name="schedule" value="<?php echo $k; ?>" <?php echo $values['schedule'] === $k ? 'checked' : ''; ?>> <?php echo $esc($t($v)); ?></label>
          <?php endforeach; ?>
        </fieldset>
        <label><?php echo $esc($t('date')); ?><input type="datetime-local" name="deliverydate" value="<?php echo $esc($values['deliverydate']); ?>"></label>
      </section>
      <p class="cf-giftprep-devnote"><?php echo $esc($t('devonly')); ?></p>
      <button type="submit" class="cf-giftprep-submit"><?php echo $esc($t('preview')); ?></button>
    </form>
  <?php endif; ?>
</div>
<?php echo $OUTPUT->footer(); ?>
