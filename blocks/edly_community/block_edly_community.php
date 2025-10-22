<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_community extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_edly_community');
    }

    public function specialization() {
        global $CFG;
        if (empty($this->config)) { $this->config = new \stdClass(); }

        // HERO (haut)
        $this->config->bgimage  = $this->config->bgimage  ?? $CFG->wwwroot . '/theme/edly/pix/main-banner/banner-large.webp';
        $this->config->bigtext  = $this->config->bigtext  ?? 'Сообщество<br>Campus<sup>FR</sup>';
        $this->config->smalltext= $this->config->smalltext?? 'Вы учите французский самостоятельно, но не в одиночку!';

        // Strapline sous l’image
        $this->config->strap    = $this->config->strap    ?? 'Campus<sup>FR</sup> — это не просто курсы, а <strong>активное сообщество</strong> единомышленников, где всегда можно получить поддержку, задать вопросы и найти друзей.';

        // Items du scroller : un par ligne, format "Titre|||Texte"
        if (empty($this->config->scroller_raw)) {
            $this->config->scroller_raw =
                "Общение с единомышленниками|||Знакомьтесь, общайтесь, делитесь опытом и находите друзей\n".
                "Поддержка и ответы на вопросы|||Преподаватель и участники помогут разобраться с любыми темами\n".
                "Мотивация и вдохновение|||Совместные челленджи, интерактивы и новые форматы обучения\n".
                "Практика в естественной среде|||Обсуждайте на французском и применяйте язык";
        }
        // Icône RemixIcon pour le badge
        $this->config->item_icon = $this->config->item_icon ?? 'ri-checkbox-circle-fill';
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) return $this->content;

        $PAGE->requires->css('/blocks/edly_community/styles.css');

        $ctx  = $this->context ?? \context_system::instance();
        $bg   = (string)($this->config->bgimage ?? '');
        $big  = format_text($this->config->bigtext ?? '',   FORMAT_HTML, ['context'=>$ctx,'filter'=>true]);
        $sml  = format_text($this->config->smalltext ?? '', FORMAT_HTML, ['context'=>$ctx,'filter'=>true]);
        $strap= format_text($this->config->strap ?? '',     FORMAT_HTML, ['context'=>$ctx,'filter'=>true]);
        $icon = preg_replace('~[^a-z0-9\-]~i', '', $this->config->item_icon ?? 'ri-checkbox-circle-fill');

        // Liste d’items (titre/texte)
        $items = [];
        $raw = preg_split('~\r\n|\n|\r~', (string)$this->config->scroller_raw);
        foreach ($raw as $i => $line) {
            $line = trim($line);
            if ($line === '') continue;
            [$t, $b] = array_pad(explode('|||', $line, 2), 2, '');
            $items[] = [
                'title' => format_text($t, FORMAT_HTML, ['context'=>$ctx,'filter'=>true]),
                'body'  => format_text($b, FORMAT_HTML, ['context'=>$ctx,'filter'=>true]),
            ];
        }

        // ID unique pour le JS de scroll
        $uid = 'ec_' . ($this->instance->id ?? uniqid());

        // HERO (image en background sur tout le bandeau)
        $html = '';
        $stylebg = $bg ? ' style="--ec-hero-bg:url(\''.s($bg).'\')"' : '';
        $html .= '<div class="ec-community" id="'.$uid.'">';
        $html .= '  <div class="ec-hero"'.$stylebg.'>';

        $html .= '    <div class="ec-hero-left">';
        $html .= '      <h2 class="ec-big">'.$big.'</h2>';
        $html .= '    </div>';

        $html .= '    <div class="ec-hero-righttext">';
        $html .= '      <p class="ec-small">'.$sml.'</p>';
        $html .= '    </div>';

        $html .= '  </div>';

        // Strapline
        $html .= '<div class="ec-strap">'.$strap.'</div>';

        // SCROLLER
        $html .= '<div class="ec-scroller-wrap">';
        $html .= '  <button class="ec-scroll-btn prev" type="button" aria-label="Prev"><i class="ri-arrow-left-line"></i></button>';
        $html .= '  <button class="ec-scroll-btn next" type="button" aria-label="Next"><i class="ri-arrow-right-line"></i></button>';
        $html .= '  <div class="ec-track">';

        foreach ($items as $k => $it) {
            $color = ($k % 2 === 0) ? 'pink' : 'green';
            $html .= '    <article class="ec-card '.$color.'" tabindex="0">';
            $html .= '      <span class="ec-badge"><i class="'.$icon.'"></i></span>';
            $html .= '      <h3 class="ec-card-title">'.$it['title'].'</h3>';
            if (!empty(trim(strip_tags($it['body'])))) {
                $html .= '  <div class="ec-card-text">'.$it['body'].'</div>';
            }
            $html .= '    </article>';
        }

        $html .= '  </div>'; // .ec-track
        $html .= '</div>';   // .ec-scroller-wrap
        $html .= '</div>';   // .ec-community

        // JS de scroll (simple & accessible)
        $html .= \html_writer::script("
            (function(){
              const root  = document.getElementById('".$uid."');
              if(!root) return;
              const track = root.querySelector('.ec-track');
              const prev  = root.querySelector('.ec-scroll-btn.prev');
              const next  = root.querySelector('.ec-scroll-btn.next');
              const step  = () => {
                const card = track.querySelector('.ec-card');
                return card ? Math.max(card.getBoundingClientRect().width + 16, track.clientWidth * 0.6) : 320;
              };
              prev && prev.addEventListener('click', ()=> track.scrollBy({left: -step(), behavior:'smooth'}));
              next && next.addEventListener('click', ()=> track.scrollBy({left:  step(), behavior:'smooth'}));
            })();
        ");

        $this->content = (object)[ 'text' => $html, 'footer' => '' ];
        return $this->content;
    }

    public function applicable_formats() {
        return ['all' => true, 'my' => true];
    }
    public function instance_allow_multiple() { return true; }
}
