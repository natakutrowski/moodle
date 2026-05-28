<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../config.php');

global $DB, $USER;

$v = required_param('v', PARAM_RAW_TRIMMED);

$audioimagebase = __DIR__ . '/privateaudioimages/';
$levelsorder = ['a1', 'a2', 'b1p'];

$parts = explode('/', $v);
$level = count($parts) > 1 ? clean_param($parts[0], PARAM_ALPHANUMEXT) : '';
$currentverb = end($parts);

$previousurl = null;
$nexturl = null;
$previouslabel = null;
$nextlabel = null;

$allitems = [];

foreach ($levelsorder as $levelname) {
    $dir = $audioimagebase . $levelname . '/';

    if (!is_dir($dir)) {
        continue;
    }

    foreach (scandir($dir) as $file) {
        if (preg_match('/^(\d+)\s*-\s*(.+)\.png$/u', $file, $matches)) {
            $allitems[] = [
                'level' => $levelname,
                'number' => (int)$matches[1],
                'verb' => $matches[2],
            ];
        }
    }
}

usort($allitems, function($a, $b) use ($levelsorder) {
    $levela = array_search($a['level'], $levelsorder);
    $levelb = array_search($b['level'], $levelsorder);

    if ($levela === $levelb) {
        return $a['number'] <=> $b['number'];
    }

    return $levela <=> $levelb;
});

$titles = [];

$titlesfile = __DIR__ . '/audio_titles.json';

if (file_exists($titlesfile)) {
    $json = file_get_contents($titlesfile);
    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        $titles = $decoded;
    }
}

$getaudiotitle = function(string $level, string $verb) use ($titles): string {
    $keywithlevel = $level ? $level . '/' . $verb : $verb;

    if (isset($titles[$keywithlevel])) {
        return $titles[$keywithlevel];
    }

    if (isset($titles[$verb])) {
        return $titles[$verb];
    }

    return str_replace('-', ' ', $verb);
};

foreach ($allitems as $index => $item) {
    if ($item['level'] === $level && $item['verb'] === $currentverb) {
        if ($index > 0) {
            $prev = $allitems[$index - 1];
            $previouslabel = $getaudiotitle($prev['level'], $prev['verb']);
            $previousurl = '/audio/' . rawurlencode($prev['level']) . '/' . rawurlencode($prev['verb']);
        }

        if ($index < count($allitems) - 1) {
            $next = $allitems[$index + 1];
            $nextlabel = $getaudiotitle($next['level'], $next['verb']);
            $nexturl = '/audio/' . rawurlencode($next['level']) . '/' . rawurlencode($next['verb']);
        }

        break;
    }
}

// Fichier attendu.
$basepath = __DIR__ . '/privateaudio/';
$filepath = $basepath . $v . '.mp3';

$realbase = realpath($basepath);
$realfile = realpath($filepath);

if (!$realbase || !$realfile || strpos($realfile, $realbase) !== 0 || !file_exists($realfile)) {
    http_response_code(404);

    $title = get_string('audio_not_found_title', 'local_campus');
    $homeurl = new moodle_url('/');
    $logourl = new moodle_url('/pix/logoCampusFRNew.png');

    $faviconurl = new moodle_url('/local/campus/pix/favicon.ico');  
    ?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo s($title); ?></title>

    <link rel="icon" href="<?php echo $faviconurl->out(false); ?>">

    <style>
        body {
            margin: 0;
            background: #f7f7f7;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .audio-page-card {
            background: #ffffff;
            width: 90%;
            max-width: 500px;
            padding: 32px;
            border-radius: 24px;
            box-shadow: 0 12px 36px rgba(0,0,0,.12);
            text-align: center;
            box-sizing: border-box;
        }

        .audio-page-logo {
            max-width: 180px;
            width: 100%;
            height: auto;
            margin-bottom: 20px;
        }

        .audio-page-icon {
            font-size: 44px;
            margin-bottom: 12px;
        }

        .audio-page-title {
            margin: 0 0 12px;
            font-size: 24px;
        }

        .audio-page-text {
            color: #666666;
            margin: 0 0 24px;
            line-height: 1.5;
        }

        .audio-page-button {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 999px;
            background: #2f2550;
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="audio-page-card">

        <a href="<?php echo $homeurl->out(false); ?>" aria-label="CampusFR">
            <img
                class="audio-page-logo"
                src="<?php echo $logourl->out(false); ?>"
                alt="CampusFR">
        </a>

        <div class="audio-page-icon">🎧</div>

        <h1 class="audio-page-title">
            <?php echo s($title); ?>
        </h1>

        <p class="audio-page-text">
            <?php echo get_string('audio_not_found_message', 'local_campus'); ?>
        </p>

        <a class="audio-page-button" href="<?php echo $homeurl->out(false); ?>">
            <?php echo get_string('audio_back_to_home', 'local_campus'); ?>
        </a>
    </div>
</body>
</html>
    <?php
    exit;
}

// Tracking.
$log = new stdClass();
$log->v = $v;
$log->userid = (isloggedin() && !isguestuser()) ? $USER->id : null;
$log->ip = getremoteaddr();
$log->useragent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$log->referer = $_SERVER['HTTP_REFERER'] ?? '';
$log->timecreated = time();

$DB->insert_record('local_campus_audio_log', $log);

// Titre simple depuis le chemin du fichier.
$titlemapfile = __DIR__ . '/audio_titles.json';

$titlemap = [];

if (file_exists($titlemapfile)) {

    $json = file_get_contents($titlemapfile);

    $decoded = json_decode($json, true);

    if (is_array($decoded)) {
        $titlemap = $decoded;
    }
}

// Extraction des parties du chemin.
$parts = explode('/', $v);

// Dernière partie = nom du verbe/fichier.
$lastpart = end($parts);

// Niveau éventuel.
$prefix = '';

if (count($parts) > 1) {

    $prefix = mb_strtoupper($parts[0], 'UTF-8') . ' - ';
}

// Titre affiché.
$display = $titlemap[$lastpart] ?? str_replace('-', ' ', $lastpart);

// special case replace B1P by B1+
$prefix = str_replace('B1P', 'B1+', $prefix);

$title = $prefix . mb_strtoupper($display, 'UTF-8');

$streamurl = new moodle_url('/local/campus/stream.php', ['v' => $v]);
$homeurl = new moodle_url('/');
$logourl = new moodle_url('/pix/logoCampusFRNew.png');

$faviconurl = new moodle_url('/local/campus/pix/favicon.ico');
?>
<!DOCTYPE html>
<html lang="<?php echo current_language(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo s($title); ?></title>

    <link rel="icon" href="<?php echo $faviconurl->out(false); ?>">

    <style>
        body {
            margin: 0;
            background: #f7f7f7;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .audio-page-card {
            background: #ffffff;
            width: 90%;
            max-width: 500px;
            padding: 32px;
            border-radius: 24px;
            box-shadow: 0 12px 36px rgba(0,0,0,.12);
            text-align: center;
            box-sizing: border-box;
        }

        .audio-page-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-top: 10px;
            transform: translateY(-52px);
        }        

        .audio-page-logo {
            max-width: 180px;
            width: 100%;
            height: auto;
            margin-bottom: 20px;
        }

        .audio-page-icon {
            font-size: 44px;
            margin-bottom: 12px;
        }

        .audio-page-title {
            margin: 0 0 12px;
            font-size: 24px;
        }

        .audio-page-text {
            color: #666666;
            margin: 0 0 24px;
            line-height: 1.5;
        }

        audio {
            width: 100%;
        }

        .audio-page-verbe-image-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 24px auto 0;
            border-radius: 18px;
            overflow: hidden;
        }

        .audio-page-verbe-image {
            display: block;
            width: 100%;
            height: auto;
            transform: scale(1.015);
            transform-origin: center;
        } 

        .audio-page-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;

            width: 100%;
            max-width: 420px;

            margin: 18px auto 0;
            gap: 10px;
        }

        .audio-page-navigation-side {
            flex: 1;
            display: flex;
        }

        .audio-page-navigation-left {
            justify-content: flex-start;
        }

        .audio-page-navigation-right {
            justify-content: flex-end;
        }

        .audio-page-nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 13px;

            padding: 9px 14px;

            border-radius: 999px;

            background: #f3f4f6;
            border: 1px solid #d1d5db;

            color: #374151;
            text-decoration: none;

            font-size: 14px;
            font-weight: 600;
            line-height: 1;

            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.7),
                0 1px 2px rgba(0,0,0,0.04);

            transition:
                background 0.15s ease,
                border-color 0.15s ease,
                transform 0.15s ease;
        }

        .audio-page-nav-link:hover {
            background: #e5e7eb;
            border-color: #c4c9d2;
            color: #111827;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .audio-page-nav-arrow {
            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
            line-height: 1;

            transform: translateY(-1px);
        }

        .audio-page-nav-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 480px) {
            .audio-page-navigation {
                gap: 5px;
            }

            .audio-page-nav-link {
                padding: 8px 12px;
                font-size: 13px;
            }

            .audio-page-nav-arrow {
                font-size: 18px;
                transform: translateY(-3px);
            }
        }

        .audio-page-card {
            opacity: 0;
            transition: opacity 0.18s ease;
        }

        .audio-page-card.audio-page-ready {
            opacity: 1;
        }

    </style>
</head>

<body>

<div class="audio-page-card" id="audio-page-card">

    <a href="<?php echo $homeurl->out(false); ?>" aria-label="CampusFR">
        <img
            class="audio-page-logo"
            src="<?php echo $logourl->out(false); ?>"
            alt="CampusFR">
    </a>

    <div class="audio-page-icon">🎧</div>

    <h1 class="audio-page-title">
        <?php echo s($title); ?>
    </h1>

    <p class="audio-page-text">
        <?php echo get_string('audio_player_instruction', 'local_campus'); ?>
    </p>

    <audio id="campus-audio-player" controls autoplay preload="auto">
        <source src="<?php echo $streamurl->out(false); ?>" type="audio/mpeg">
        <?php echo get_string('audio_browser_not_supported', 'local_campus'); ?>
    </audio>

    <div class="audio-page-navigation">

        <div class="audio-page-navigation-side audio-page-navigation-left">
            <?php if ($previousurl): ?>
                <a class="audio-page-nav-link" href="<?php echo s($previousurl); ?>">
                    <span class="audio-page-nav-arrow">⟵</span>
                    <span class="audio-page-nav-text"><?php echo s($previouslabel); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <div class="audio-page-navigation-side audio-page-navigation-right">
            <?php if ($nexturl): ?>
                <a class="audio-page-nav-link" href="<?php echo s($nexturl); ?>">
                    <span class="audio-page-nav-text"><?php echo s($nextlabel); ?></span>
                    <span class="audio-page-nav-arrow">⟶</span>
                </a>
            <?php endif; ?>
        </div>

    </div>

    <?php
    $imageurl = new moodle_url('/local/campus/audio_image.php', ['v' => $v]);
    ?>

    <div class="audio-page-verbe-image-wrapper">
        <img
            class="audio-page-verbe-image"
            src="<?php echo $imageurl->out(false); ?>"
            alt="<?php echo s($title); ?>"
            loading="lazy"
            onerror="this.parentElement.style.display='none';">
    </div> 

</div>

<script>
window.addEventListener('load', async () => {
    const audio = document.getElementById('campus-audio-player');

    if (!audio) {
        return;
    }

    try {
        await audio.play();
    } catch (e) {
        console.log('Autoplay blocked');
    }
});

const artwork = '<?php echo (new moodle_url('/local/campus/pix/audio-cover.png'))->out(false); ?>';

if ('mediaSession' in navigator) {

    navigator.mediaSession.metadata = new MediaMetadata({
        title: '<?php echo addslashes($title); ?>',
        artist: 'CampusFR',
        album: 'CampusFR Audio',
        artwork: [
            {
                src: artwork,
                sizes: '1024x1024',
                type: 'image/png'
            }
        ]
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const card = document.getElementById('audio-page-card');
    const image = document.querySelector('.audio-page-verbe-image');

    if (!card) {
        return;
    }

    // Pas d'image -> on affiche directement.
    if (!image) {
        card.classList.add('audio-page-ready');
        return;
    }

    // Image déjà en cache.
    if (image.complete) {
        card.classList.add('audio-page-ready');
        return;
    }

    image.addEventListener('load', function () {
        card.classList.add('audio-page-ready');
    });

    image.addEventListener('error', function () {
        card.classList.add('audio-page-ready');
    });
});
</script>
</body>
</html>
<?php
exit;