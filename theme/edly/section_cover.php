<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Serves the promoted Text & Media cover for a pedagogically locked section.
 *
 * Moodle normally protects mod_label intro files using the course-module
 * access state. That also hides the visual section cover when the whole
 * section is unavailable. CampusFR deliberately keeps only the first image
 * visible as a locked-section illustration; no other Text & Media content is
 * exposed by this endpoint.
 *
 * @package theme_edly
 */

require_once(__DIR__ . '/../../config.php');

$sectionid = required_param('sectionid', PARAM_INT);

$sectionrecord = $DB->get_record('course_sections', ['id' => $sectionid], '*', MUST_EXIST);
$course = get_course((int)$sectionrecord->course);

require_login($course);

$modinfo = get_fast_modinfo($course);
$section = $modinfo->get_section_info((int)$sectionrecord->section);

if (!$section || (int)$section->id !== $sectionid || !$section->visible || $section->uservisible) {
    throw new moodle_exception('nopermissions', 'error', '', null, 'Section cover is not available.');
}

/**
 * Extract all availability condition types from a restriction tree.
 *
 * @param mixed $node
 * @return string[]
 */
function theme_edly_section_cover_condition_types($node): array {
    $types = [];

    if (is_object($node)) {
        if (!empty($node->type) && is_string($node->type)) {
            $types[] = strtolower($node->type);
        }
        foreach (get_object_vars($node) as $value) {
            $types = array_merge($types, theme_edly_section_cover_condition_types($value));
        }
    } else if (is_array($node)) {
        foreach ($node as $value) {
            $types = array_merge($types, theme_edly_section_cover_condition_types($value));
        }
    }

    $types = array_values(array_unique(array_filter($types)));
    sort($types);
    return $types;
}

/**
 * Whether a date tree actually contains a future opening date.
 *
 * @param mixed $node
 * @return bool
 */
function theme_edly_section_cover_has_future_start_date($node): bool {
    if (is_object($node)) {
        if (($node->type ?? '') === 'date') {
            $direction = (string)($node->d ?? $node->direction ?? '');
            $timestamp = (int)($node->t ?? $node->time ?? 0);
            if (in_array($direction, ['>=', '>'], true) && $timestamp > time()) {
                return true;
            }
        }
        foreach (get_object_vars($node) as $value) {
            if (theme_edly_section_cover_has_future_start_date($value)) {
                return true;
            }
        }
    } else if (is_array($node)) {
        foreach ($node as $value) {
            if (theme_edly_section_cover_has_future_start_date($value)) {
                return true;
            }
        }
    }

    return false;
}

$availability = json_decode((string)$sectionrecord->availability);
$types = $availability ? theme_edly_section_cover_condition_types($availability) : [];
$allowedrestriction = $types === ['completion']
    || ($types === ['date'] && theme_edly_section_cover_has_future_start_date($availability));

if (!$allowedrestriction) {
    throw new moodle_exception('nopermissions', 'error', '', null, 'Unsupported section restriction.');
}

$cmids = $modinfo->sections[(int)$sectionrecord->section] ?? [];

foreach ($cmids as $cmid) {
    if (empty($modinfo->cms[$cmid])) {
        continue;
    }

    $cm = $modinfo->cms[$cmid];
    if ($cm->modname !== 'label') {
        continue;
    }

    $intro = $DB->get_field('label', 'intro', ['id' => $cm->instance], IGNORE_MISSING);
    if (!$intro || !preg_match('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $intro, $matches)) {
        continue;
    }

    $src = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // External images are already public resources; preserve them as-is.
    if (preg_match('~^https?://~i', $src) && !str_starts_with($src, $CFG->wwwroot . '/pluginfile.php/')) {
        redirect($src);
    }

    $relative = null;
    if (str_starts_with($src, '@@PLUGINFILE@@')) {
        $relative = substr($src, strlen('@@PLUGINFILE@@'));
    } else {
        $context = context_module::instance((int)$cmid);
        $prefix = $CFG->wwwroot . '/pluginfile.php/' . $context->id . '/mod_label/intro/0';
        if (str_starts_with($src, $prefix)) {
            $relative = substr($src, strlen($prefix));
        }
    }

    if ($relative === null) {
        continue;
    }

    $relative = preg_split('/[?#]/', $relative, 2)[0];
    $relative = rawurldecode($relative);
    $relative = '/' . ltrim($relative, '/');

    $filename = basename($relative);
    $filepath = dirname($relative);
    $filepath = $filepath === '/' ? '/' : '/' . trim($filepath, '/') . '/';

    $context = context_module::instance((int)$cmid);
    $fs = get_file_storage();
    $file = $fs->get_file(
        $context->id,
        'mod_label',
        'intro',
        0,
        $filepath,
        $filename
    );

    if (!$file || $file->is_directory()) {
        continue;
    }

    send_stored_file($file, DAYSECS, 0, false, [
        'cacheability' => 'public',
        'immutable' => true,
    ]);
}

throw new moodle_exception('filenotfound', 'error');
