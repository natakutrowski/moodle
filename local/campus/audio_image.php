<?php
require_once(__DIR__ . '/../../config.php');

$v = required_param('v', PARAM_RAW_TRIMMED);

$basepath = __DIR__ . '/privateaudioimages/';
$realbase = realpath($basepath);

if (!$realbase) {
    http_response_code(404);
    exit;
}

$parts = explode('/', $v);
$lastpart = end($parts);

$dirs = [$basepath];

if (count($parts) > 1) {
    $level = clean_param($parts[0], PARAM_ALPHANUMEXT);
    $dirs[] = $basepath . $level . '/';
}

$imagefile = null;

foreach ($dirs as $dir) {
    $realdir = realpath($dir);

    if (!$realdir || strpos($realdir, $realbase) !== 0) {
        continue;
    }

    foreach (scandir($realdir) as $file) {
        if (preg_match('/^\d+\s*-\s*' . preg_quote($lastpart, '/') . '\.png$/u', $file)) {
            $candidate = $realdir . '/' . $file;
            $realcandidate = realpath($candidate);

            if ($realcandidate && strpos($realcandidate, $realbase) === 0) {
                $imagefile = $realcandidate;
                break 2;
            }
        }
    }
}

if (!$imagefile || !file_exists($imagefile)) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/png');
header('Content-Length: ' . filesize($imagefile));
header('Cache-Control: public, max-age=86400');

readfile($imagefile);
exit;