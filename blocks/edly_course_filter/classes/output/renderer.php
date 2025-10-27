<?php
namespace block_edly_course_filter\output;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    public function catalogue(array $courses, array $opts = []): string {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/theme/edly/inc/course_handler/edly_course_handler.php');
        // Si Campus est installé, on pourra lire ses helpers/CFG.
        $campuslib = $CFG->dirroot . '/local/campus/lib.php';
        if (file_exists($campuslib)) { require_once($campuslib); }

        // Options par défaut
        $d = (object) array_merge([
            'style' => 1,                                 // 1 ou 2 (comme le block)
            'class' => 'courses-area ptb-100',
            'title' => '',
            'top_title' => '',
            'body' => '',
            'label_field' => 'cardlabel',
            'trial_field' => 'trialcourseid',
            'real_field' => 'realcourseid',
            'force_direct_loggedin' => 0,
            'desc_baseurl' => '',                         // ex: /local/campus/course.php?id={id}&checktrial=1
            'desc_label' => get_string('moreinfo', 'local_campus'),
            'cta_guest'  => get_string('trial_access', 'theme_edly'),
            'cta_connected' => get_string('cta_connected','local_campus'),
            // on peut exposer ces deux-là avec un fallback i18n :
            'cta_connected_start'  => get_string('cta_connected_start', 'local_campus'),
            'cta_connected_resume' => get_string('cta_connected_resume','local_campus'),
            'cta_connected_free'   => get_string('cta_connected', 'local_campus'),
            'restricted' => false,                        // true = visiteur / compte d’essai
            'shape_img' => '',
        ], $opts);

        // S’assurer d’avoir les styles du block (sélecteurs préfixés par .block_edly_course_filter)
        //$this->page->requires->css('/blocks/edly_course_filter/styles.css');

        $handler = new \edlyCourseHandler();

        // Wrapper avec la classe du block pour réutiliser le CSS existant
        $html  = '<div class="block_edly_course_filter">';
        $html .= '<div class="'.s($d->class).'"><div class="container">';

        $html .= '<div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">';
        if ($d->top_title !== '') { $html .= '<span class="sub">'.format_text($d->top_title, FORMAT_HTML, ['filter'=>true]).'</span>'; }
        if ($d->title !== '')     { $html .= '<h2>'.format_text($d->title, FORMAT_HTML, ['filter'=>true]).'</h2>'; }
        $html .= '</div>';

        if (!empty($d->tabs_html)) {
            $html .= $d->tabs_html;     // Les onglets apparaissent en haut du bloc, dans le container
        }

        $html .= '<div class="row justify-content-center gx-4 gy-4">';

        foreach ($courses as $c) {
            $courseid = is_object($c) ? (int)$c->id : (int)$c;
            if (!$DB->record_exists('course', ['id'=>$courseid])) { continue; }

            $ec = $handler->edlyGetCourseDetails($courseid);
            $cover = (string)($ec->edlyRender->coverImage ?? '');

            $badge = '';
            if (!empty($d->completed_ids) && in_array($courseid, (array)$d->completed_ids, true)) {
                $badge = get_string('completed_badge', 'local_campus'); // "Terminé"
            } else {
                $badge = trim((string)($this->cf_value($courseid, $d->label_field ?? 'cardlabel') ?? ''));
            }
            $cardhref = $this->resolve_card_href($courseid, $d);
            $descurl  = $this->resolve_desc_href($courseid, $d);

            $disabled = !empty($d->disabled_ids) && in_array($courseid, (array)$d->disabled_ids, true);
            $cardclass = $disabled ? ' cf-disabled' : '';

            if ((int)$d->style === 2) {
                // ===== STYLE 2 =====
                $html .= '<div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="80" data-aos-duration="800" data-aos-once="true">
                    <div class="courses-box'.$cardclass.'">
                        <div class="courses-image">
                            <a href="'. s($cardhref) .'">'.$cover.'</a>'.
                            (!empty($badge) ? '<span class="cf-card-badge">'.s($badge).'</span>' : '');
                if (!empty($ec->course_price)) {
                    $html .= '<div class="price">'.format_text(get_config('theme_edly', 'site_currency').$ec->course_price, FORMAT_HTML, ['filter'=>true]).'</div>';
                } else {
                    $html .= '<div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, ['filter'=>true]).'</div>';
                }
                $html .= '</div>
                        <div class="courses-content">
                            <h3><a href="'. s($cardhref) .'">'.format_text($ec->fullName, FORMAT_HTML, ['filter'=>true]).'</a></h3>'.
                            $this->card_footer($courseid, $d, $descurl).
                        '</div>
                    </div>
                </div>';
            } else {
                // ===== STYLE 1 =====
                $html .= '<div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                    <div class="courses-card'.$cardclass.'">
                        <div class="courses-image">
                            <a href="'. s($cardhref) .'">'.$cover.'</a>'.
                            (!empty($badge) ? '<span class="cf-card-badge">'.s($badge).'</span>' : '').
                        '</div>
                        <div class="courses-content">
                            <div class="top-content">
                                <h3><a href="'. s($cardhref) .'">'.format_text($ec->fullName, FORMAT_HTML, ['filter'=>true]).'</a></h3>';
                if (!empty($ec->course_price)) {
                    $html .= '<div class="price">'.format_text(get_config('theme_edly', 'site_currency').$ec->course_price, FORMAT_HTML, ['filter'=>true]).'</div>';
                } else {
                    $html .= '<div class="price">'.format_text(get_config('theme_edly', 'free_course_price'), FORMAT_HTML, ['filter'=>true]).'</div>';
                }
                $html .= '    </div>'.
                         $this->card_footer($courseid, $d, $descurl).
                        '</div>
                    </div>
                </div>';
            }
        }

        $html .= '</div>'; // row

        if (trim($d->body) !== '') {
            $html .= '<div class="courses-bottom-content"><p>'.format_text($d->body, FORMAT_HTML, ['filter'=>true]).'</p></div>';
        }

        $html .= '</div>'; // container

        if (!empty($d->shape_img)) {
            $shape = \html_writer::empty_tag('img', ['src'=>$d->shape_img, 'class'=>'shape shape-1', 'alt'=>strip_tags($d->title)]);
            $html .= '<div class="courses-pot-shape">'.$shape.'</div>';
        }

        $html .= '</div>'; // area
        $html .= '</div>'; // wrapper .block_edly_course_filter

        return $html;
    }

    /* ---------- Helpers partagés (copie fidèle de la logique du block) ---------- */

    private function card_footer(int $courseid, \stdClass $d, string $descurl): string {
        // Libellés
        $desclabel = trim($d->desc_label ?? get_string('moreinfo','local_campus'));
        $lblStart  = trim($d->cta_connected_start  ?? get_string('cta_connected_start','local_campus'));
        $lblResume = trim($d->cta_connected_resume ?? get_string('cta_connected_resume','local_campus'));
        $lblGuest  = trim($d->cta_guest            ?? get_string('trial_access','theme_edly'));
        $lblFree   = trim($d->cta_connected_free   ?? get_string('cta_connected','local_campus'));

        // Progression fournie par mycourses.php
        $pct = null;
        if (!empty($d->progress_map) && array_key_exists($courseid, (array)$d->progress_map)) {
            $pct = max(0, min(100, (int)$d->progress_map[$courseid]));
        }

        // Etats
        $disabled    = !empty($d->disabled_ids) && in_array($courseid, (array)$d->disabled_ids, true);
        $isrestricted= !empty($d->restricted);
        $isfree      = !empty($d->free_ids) && in_array($courseid, (array)$d->free_ids, true);

        // Ligne boutons (toujours présente pour garder l’alignement)
        $html  = '<div class="cf-footer d-flex align-items-center justify-content-between flex-wrap">';
        $html .= '  <a class="btn btn-outline-dark cf-desc" href="'. s($descurl) .'">'. s($desclabel) .'</a>';

        // CTA
        if ($isrestricted) {
            $redir = $this->trial_redirect_id_for($courseid, $d);
            $html .= $redir
                ? '<a href="#" class="default-btn cf-cta" data-campus-trial-redirect="'.(int)$redir.'">'. s($lblGuest) .'</a>'
                : '<span class="default-btn cf-cta is-disabled">'. s($lblGuest) .'</span>';
        } else if ($disabled) {
            $html .= '<span class="default-btn cf-cta is-disabled">'. s(get_string('notenrolled','local_campus')) .'</span>';
        } else {
            // On pointe TOUJOURS vers la page du cours (plus de “reprendre l’activité”)
            $target = $this->resolve_card_href($courseid, $d);
            $label  = ($pct === null || $pct <= 0) ? $lblStart : $lblResume;
            // Cours “libre” (pas de suivi) => label générique
            if ($isfree) { $label = $lblFree; }
            $html .= '<a class="default-btn cf-cta" href="'. s($target) .'">'. s($label) .'</a>';
        }
        $html .= '</div>';

        // Barre / placeholder sous les boutons
        if (!empty($d->progress_below)) {
            if ($pct !== null) {
                if ($pct >= 100) {
                    // Pas de barre : uniquement le message de félicitations
                    $html .= '<div class="cf-progress cf-progress-below">'
                        . ' <div class="cf-progress-bar"><span style="width:'.$pct.'%"></span></div>'
                        . ' <div class="cf-progress-label" style="color:#16a34a;">'.s(get_string('congrats_completed','local_campus')).'</div>'
                        . '</div>';
                } else {
                    // Barre + pourcentage
                    $html .= '<div class="cf-progress cf-progress-below">'
                        . ' <div class="cf-progress-bar"><span style="width:'.$pct.'%"></span></div>'
                        . ' <div class="cf-progress-label">'.$pct.'% '.get_string('completed','local_campus').'</div>'
                        . '</div>';
                }
            } else {
                // Placeholder (garde l’alignement des cartes)
                $html .= '<div class="cf-progress cf-progress-below">'
                    . ' <div class="cf-progress-bar"><span style="width:0"></span></div>'
                    . ' <div class="cf-progress-label">'. s(get_string('course_not_started','local_campus')) .'</div>'
                    . '</div>';
            }
        }

        return $html;
    }
    private function resolve_card_href(int $courseid, \stdClass $d): string {
        // Invité / compte d’essai : on laisse la carte inerte (le CTA gère la popup essai)
        if (!empty($d->restricted)) {
            return '#';
        }
        // Cours ACTUEL (la carte représente le trial OU le real)
        return (new \moodle_url('/course/view.php', ['id' => $courseid]))->out(false);
    }


    private function resolve_desc_href(int $courseid, \stdClass $d): string {
        $descid = $this->cf_int($courseid, $d->real_field ?? 'realcourseid');
        if ($descid <= 0) { $descid = $courseid; }

        $base = trim((string)($d->desc_baseurl ?? ''));
        if ($base === '') {
            return (new \moodle_url('/course/view.php', ['id'=>$descid]))->out(false);
        }
        $c = get_course($descid);
        $search  = ['{id}', '{shortname}', '{categoryid}'];
        $replace = [$descid, $c->shortname ?? '', $c->category ?? 0];
        return str_replace($search, $replace, $base);
    }

    private function trial_redirect_id_for(int $courseid, \stdClass $d): ?int {
        $trialids = function_exists('local_campus_trial_course_ids') ? local_campus_trial_course_ids() : [];
        if (in_array($courseid, $trialids, true)) { return $courseid; }
        $trialshort = trim($d->trial_field ?? 'trialcourseid');
        $mapped = $this->cf_int($courseid, $trialshort);
        if ($mapped > 0 && in_array($mapped, $trialids, true)) { return $mapped; }
        return null;
    }

    private function cf_value(int $courseid, string $shortname): ?string {
        global $DB;
        $shortname = trim($shortname ?? '');
        if ($shortname === '') { return null; }
        $sql = "SELECT d.value
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE d.instanceid = :cid
                   AND f.shortname = :shortname
                   AND c.component = 'core_course'
                   AND c.area = 'course'";
        $params = ['cid' => $courseid, 'shortname' => $shortname];
        $val = $DB->get_field_sql($sql, $params);
        if ($val === false || $val === null) { return null; }
        return trim((string)$val);
    }

    private function cf_int(int $courseid, string $shortname): int {
        $v = $this->cf_value($courseid, $shortname);
        if ($v === null || $v === '') { return 0; }
        return (int)$v;
    }
}
