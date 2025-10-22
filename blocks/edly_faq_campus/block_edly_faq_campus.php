<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_faq_campus extends block_base {

    public function init() {
        // Titre d’en-tête du block (peut être masqué par le thème) – on met aussi un titre dans le contenu.
        $this->title = get_string('pluginname', 'block_edly_faq_campus');
    }

    public function specialization() {
        if (empty($this->config)) {
            $this->config            = (object)[];
            $this->config->title     = 'FAQ';
            $this->config->question_count = 6;

            // Petits exemples par défaut.
            for ($i = 1; $i <= $this->config->question_count; $i++) {
                $this->config->{'question_'.$i} = 'Votre question '.$i;
                $this->config->{'answer_'.$i}   = '<p>Votre réponse en HTML…</p>';
            }
        }
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object)['text' => '', 'footer' => ''];

        $count = isset($this->config->question_count) ? (int)$this->config->question_count : 0;
        $count = max(0, min(30, $count));

        $title = isset($this->config->title) ? $this->config->title : 'FAQ';

        // Rendu : on garde les classes .accordion de ton thème pour l’effet accordéon.
        $html  = '';
        $html .= '<div class="faq-area ptb-100">';
        $html .= '  <div class="container">';

        // 1) Titre (pas de boutons/tabs)
        $html .= '    <div class="section-title">';
        $html .= '      <h2>'.format_text($title, FORMAT_HTML, ['filter' => true]).'</h2>';
        $html .= '    </div>';

        // 2) Accordéon simple
        $html .= '    <div class="faq-accordion">';
        $html .= '      <ul class="accordion">';

        for ($i = 1; $i <= $count; $i++) {
            $q = format_text($this->config->{'question_'.$i} ?? '', FORMAT_HTML, ['filter' => true]);
            $a = format_text($this->config->{'answer_'.$i}   ?? '', FORMAT_HTML, ['filter' => true]);

            // Ouvre le premier par défaut.
            $activeA = ($i === 1) ? ' active' : '';
            $activeC = ($i === 1) ? ' show'   : '';

            $html .= '        <li class="accordion-item">';
            $html .= '          <a class="accordion-title'.$activeA.'" href="javascript:void(0)">';
            $html .= '            <i class="ri-arrow-down-s-line"></i>'.$q;
            $html .= '          </a>';
            $html .= '          <div class="accordion-content'.$activeC.'">'.$a.'</div>';
            $html .= '        </li>';
        }

        $html .= '      </ul>';
        $html .= '    </div>'; // .faq-accordion

        $html .= '  </div>';   // .container
        $html .= '</div>';     // .faq-area

        $this->content->text = $html;
        return $this->content;
    }

    public function instance_allow_multiple() { return true; }

    public function has_config() { return false; }

    public function applicable_formats() {
        return [
            'all' => true,
            'my' => false,
            'admin' => false,
            'course-view' => true,
            'course' => true,
        ];
    }
}
