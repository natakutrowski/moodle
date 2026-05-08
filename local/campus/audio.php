<?php
// This file is part of Moodle - https://moodle.org/

require_once(__DIR__ . '/../../config.php');

global $DB, $USER;

$v = required_param('v', PARAM_RAW_TRIMMED);

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
        <?php echo get_string('audio_player_instruction', 'local_campus'); ?>
    </p>

    <audio id="campus-audio-player" controls autoplay preload="auto">
        <source src="<?php echo $streamurl->out(false); ?>" type="audio/mpeg">
        <?php echo get_string('audio_browser_not_supported', 'local_campus'); ?>
    </audio>

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

</body>
</html>
<?php
exit;