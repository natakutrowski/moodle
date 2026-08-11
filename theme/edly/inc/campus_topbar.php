<?php
$storefrontpath = '/boutique';
if (class_exists('\local_subscriptions\subscription_config')) {
    $configuredstorefront = \local_subscriptions\subscription_config::storefront_page();
    $storefrontpath = $configuredstorefront instanceof \moodle_url
        ? $configuredstorefront->out(false)
        : (string)$configuredstorefront;
}
$storefronturl = (new \moodle_url($storefrontpath))->out(false);
?>
<header class="campus-topbar">
    <div class="campus-topbar-inner">

        <!-- LEFT : LOGO -->
        <div class="campus-topbar-left">
            <a href="<?php echo $CFG->wwwroot; ?>" class="campus-topbar-logo">
                <?php
                    $themeconfig = \theme_config::load('edly');
                    $mainlogo = $themeconfig->setting_file_url('main_logo', 'main_logo');
                ?>

                <?php if ($mainlogo): ?>
                    <img src="<?php echo $mainlogo; ?>" alt="CampusFR">
                <?php endif; ?>
            </a>
        </div>

        <!-- RIGHT -->
        <div class="campus-topbar-right">

            <!-- LANGUE -->
            <div class="campus-topbar-lang">
                <?php
                $currentlang = current_language();

                $languages = [
                    'en' => ['label' => 'English',  'flag' => '🇬🇧'],
                    'fr' => ['label' => 'Français', 'flag' => '🇫🇷'],
                    'ru' => ['label' => 'Русский',  'flag' => '🇷🇺'],
                ];

                $action = $PAGE->url->out(false);
                ?>

                <div class="campus-lang-dropdown">
                    <button class="campus-lang-trigger" type="button">
                        <span class="campus-lang-flag">
                            <?php echo $languages[$currentlang]['flag'] ?? ''; ?>
                        </span>
                        <span class="campus-lang-code">
                            <?php echo strtoupper($currentlang); ?>
                        </span>
                        <span class="campus-lang-arrow">▾</span>
                    </button>

                    <div class="campus-lang-menu">
                        <?php foreach ($languages as $code => $lang): ?>
                            <a href="<?php echo $action . '?lang=' . $code; ?>"
                            class="campus-lang-item <?php echo ($code === $currentlang) ? 'active' : ''; ?>">
                                <?php echo $lang['flag']; ?>
                                <?php echo $lang['label']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="campus-topbar-ctas">
                <a href="<?php echo s($storefronturl); ?>"
                   class="campus-topbar-btn campus-topbar-btn-primary">
                    Commencer
                </a>

                <a href="<?php echo $CFG->wwwroot; ?>/login/index.php"
                   class="campus-topbar-btn campus-topbar-btn-secondary">
                    Connexion
                </a>
            </div>

        </div>
    </div>
</header>