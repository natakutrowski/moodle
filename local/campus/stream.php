<?php

require('../../config.php');

$v = required_param('v', PARAM_RAW_TRIMMED);

$basepath = __DIR__ . '/privateaudio/';
$filepath = $basepath . $v . '.mp3';

$realbase = realpath($basepath);
$realfile = realpath($filepath);

if (!$realfile || strpos($realfile, $realbase) !== 0 || !file_exists($realfile)) {
    http_response_code(404);
    exit('Audio not found');
}

header('Content-Type: audio/mpeg');
header('Content-Disposition: inline; filename="' . basename($realfile) . '"');
header('Accept-Ranges: bytes');

readfile($realfile);
exit;