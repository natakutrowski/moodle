<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_features_area_two_campus extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_features_area_two_campus');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();

            $this->config->top_title    = 'Transform Your Life';
            $this->config->title        = 'Improving lives through <span>learning</span>';

            $this->config->icon1                    = 'ri-moon-clear-line';
            $this->config->image1  = ''; // ← NEW
            $this->config->features_title1          = 'Earn certificates and degrees';
            $this->config->features_content1        = 'Break into a new field like format technology or data science get started.';

            $this->config->icon2                    = 'ri-stack-line';
            $this->config->image2  = ''; // ← NEW
            $this->config->features_title2          = 'Learn anything together';
            $this->config->features_content2        = 'Break into a new field like format technology or data science get started.';

            $this->config->icon3                    = 'ri-star-line';
            $this->config->image3  = ''; // ← NEW
            $this->config->features_title3          = 'Learn with experts';
            $this->config->features_content3        = 'Break into a new field like format technology or data science get started.';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        $this->content         =  new stdClass;

        $features_number = 3;
        if(isset($this->config->features_number)){
            $features_number = $this->config->features_number;
        }

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}


        $text = '';
        $text .= '
        <div class="improving-area pt-100 pb-75">
        <div class="container">';

        if ($this->content->title || $this->content->top_title){
        $text .= '
            <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, ['filter' => true]).'</span>
            <h2>'.format_text($this->content->title, FORMAT_HTML, ['filter' => true]).'</h2>
            </div>';
        }

        $text .= '
            <div class="improving-scroller">
            <div class="improving-track" id="improving-track-'.$this->instance->id.'">';

        for ($i = 1; $i <= $features_number; $i++) {
            $icon = $this->config->{'icon'.$i} ?? '';
            $image = $this->config->{'image'.$i} ?? '';
            $features_title = $this->config->{'features_title'.$i} ?? '';
            $features_content = $this->config->{'features_content'.$i} ?? '';

            if ($image !== '') {
                $iconhtml = '<span class="improving-icon is-image"><img src="'.edly_block_image_process($image).'" alt="'.strip_tags($features_title).'"></span>';
            } else {
                $cls = $icon !== '' ? $icon : 'ri-star-line';
                $iconhtml = '<i class="improving-icon '.$cls.'"></i>';
            }

            $text .= '
                <div class="improving-slide">
                <div class="improving-card" data-index="'.$i.'">
                    '.$iconhtml.'
                    <h3>'.format_text($features_title, FORMAT_HTML, ['filter'=>true]).'</h3>
                    <p>'.format_text($features_content, FORMAT_HTML, ['filter'=>true]).'</p>
                </div>
                </div>';
        }

        $text .= '
            </div>
            <!-- Dots -->
            <div class="improving-dots" id="improving-dots-'.$this->instance->id.'">';

        for ($i = 1; $i <= $features_number; $i++) {
            $active = $i === 1 ? ' is-active' : '';
            $text .= '<span class="improving-dot'.$active.'" data-goto="'.($i-1).'"></span>';
        }

        $text .= '
            </div>
            </div>
        </div>
        </div>';

        // footer/text comme d’habitude
        $this->content->footer = '';
        $this->content->text   = $text;

        /* NEW: propager la couleur secondaire du thème Edly */
        $brand = get_config('theme_edly', 'brandcolor') ?: '';
        $secondary = get_config('theme_edly', 'secondarycolor') ?: '#1EA69A';
        $stylevars = 'style="'
            . ($brand ? '--cf-brand: '.$brand.';' : '')
            . '--cf-secondary: '.$secondary.';"';

        $this->content->text = '<div class="block_edly_features_area_two block_edly_features_area_two_campus" '.$stylevars.'>'
            . $this->content->text
            . '</div>';

        $this->content->footer = '
            <script>
            (function(){
            const root = document.currentScript.closest(".block_edly_features_area_two_campus");
            if(!root) return;

            const track = root.querySelector(".improving-track");
            const dots  = root.querySelectorAll(".improving-dot");

            const gap = 16; // doit correspondre à gap:16px du CSS
            const slideWidth = () => track.querySelector(".improving-slide")?.getBoundingClientRect().width || 0;

            function goto(index){
                const w = slideWidth();
                track.scrollTo({ left: index * (w + gap), behavior: "smooth" });
            }

            dots.forEach((d,i) => d.addEventListener("click", () => goto(i)));

            function syncDots(){
                const w = slideWidth();
                const i = Math.round(track.scrollLeft / (w + gap));
                dots.forEach((d,k) => d.classList.toggle("is-active", k === i));
            }
            track.addEventListener("scroll", () => { window.requestAnimationFrame(syncDots); });
            window.addEventListener("resize", syncDots);
            syncDots();
            })();
            </script>
        ';


        return $this->content;

    }

    /**
     * The block can be used repeatedly in a page.
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    function applicable_formats() {
        return array(
            'all' => true,
            'my' => false,
            'admin' => false,
            'course-view' => true,
            'course' => true,
        );
    }

}