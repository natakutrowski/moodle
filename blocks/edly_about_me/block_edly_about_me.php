<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_about_me extends block_base {

    public function init() {
        $this->title = ''; // pas de titre de bloc visible (design plein)
    }

    public function specialization() {
        global $CFG;
        if (empty($this->config)) { $this->config = new \stdClass(); }

        // HERO par défaut
        $this->config->hero_title = $this->config->hero_title ?? 'Кто ведёт курсы<br>и кто создал Кампус?';
        $this->config->hero_lead  = $this->config->hero_lead  ?? 'Campus<sup>FR</sup> — это не просто курсы, а тщательно продуманная система обучения, созданная с любовью к французскому языку.';
        // Image de la personne (PNG) — ton lien :
        $this->config->hero_image = $this->config->hero_image ?? 'https://static.tildacdn.net/tild6465-6561-4633-a266-303966653233/Group_1597880848.png';
        // Badge @handle
        $this->config->hero_badge_text = $this->config->hero_badge_text ?? '@nata.kutrowski';
        $this->config->hero_badge_url  = $this->config->hero_badge_url  ?? '';

        // Cartes du bas
        $this->config->card1_content = $this->config->card1_content ?? '';
        $this->config->card2_content = $this->config->card2_content ?? '';
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) return $this->content;
        
        $context = $this->context ?? \context_system::instance();

        $hero_title = format_text($this->config->hero_title ?? '', FORMAT_HTML, ['context'=>$context, 'filter'=>true]);
        $hero_lead  = format_text($this->config->hero_lead  ?? '', FORMAT_HTML, ['context'=>$context, 'filter'=>true]);
        $hero_img   = (string)($this->config->hero_image ?? '');
        $badge_text = format_string($this->config->hero_badge_text ?? '');
        $badge_url  = trim((string)($this->config->hero_badge_url ?? ''));

        $card1_html = format_text($this->config->card1_content ?? '', FORMAT_HTML, ['context'=>$context,'filter'=>true]);
        $card2_html = format_text($this->config->card2_content ?? '', FORMAT_HTML, ['context'=>$context,'filter'=>true]);

        // Badge HTML
        $badge = '';
        if ($badge_text !== '') {
            $badgeinner = \html_writer::span($badge_text, 'label');
            $badge = $badge_url !== ''
                ? \html_writer::link($badge_url, $badgeinner, ['class'=>'ea-badge', 'target'=>'_blank', 'rel'=>'noopener'])
                : \html_writer::span($badgeinner, 'ea-badge');
        }

        $html = '';
        $html .= \html_writer::start_div('edly-about-me');

        // === HERO (image en background sur tout le bloc)
        $bgstyle = $hero_img ? ' style="--ea-hero-bg:url(\''.s($hero_img).'\')"' : '';
        $html .= '<div class="ea-hero"'.$bgstyle.'>';
        $html .= '  <div class="ea-hero-left">';
        $html .= '    <h2 class="ea-title">'.$hero_title.'</h2>';
        $html .= '    <p class="ea-lead">'.$hero_lead.'</p>';
        $html .= '  </div>';
        // pas de <img> ici : l’image est maintenant en background du hero
        $html .=      $badge; // badge en surimpression
        $html .= '</div>';

        // CARDS
        $html .= '<div class="ea-cards">';

        $html .= '  <div class="ea-card">';
        $html .= '    <div class="ea-card-icon" aria-hidden="true"><i class="ri-user-follow-fill"></i></div>';
        $html .= '    <div class="ea-card-content">'.$card1_html.'</div>';
        $html .= '  </div>';

        $html .= '  <div class="ea-card">';
        $html .= '    <div class="ea-card-icon" aria-hidden="true"><i class="ri-team-fill"></i></div>';
        $html .= '    <div class="ea-card-content">'.$card2_html.'</div>';
        $html .= '  </div>';

        $html .= '</div>';

        $html .= \html_writer::end_div();

        $this->content = (object)[
            'text' => $html,
            'footer' => ''
        ];
        return $this->content;
    }

    public function applicable_formats() {
        return ['all' => true, 'my' => false];
    }

    public function instance_allow_multiple() {
        return true;
    }
}
