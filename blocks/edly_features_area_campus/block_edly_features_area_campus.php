<?php
global $CFG;
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_features_area_campus extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_features_area_campus');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();

            $this->config->style = 1;
            $this->config->features_title1          = 'Earn certificates and degrees';
            $this->config->features_content1        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text1      = 'Start Now';
            $this->config->features_button_link1    = $CFG->wwwroot . '/course';
            $this->config->bg_img1    = $CFG->wwwroot.'/theme/edly/pix/features/features1.png';
            $this->config->img1    = $CFG->wwwroot.'/theme/edly/pix/features/icon1.svg';

            $this->config->features_title2          = 'In-Demand Trendy Topics';
            $this->config->features_content2        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text2      = 'Start Now';
            $this->config->features_button_link2    = $CFG->wwwroot . '/course';
            $this->config->bg_img2    = $CFG->wwwroot.'/theme/edly/pix/features/features2.png';
            $this->config->img2    = $CFG->wwwroot.'/theme/edly/pix/features/icon2.svg';

            $this->config->features_title3          = 'Segment Your Learning';
            $this->config->features_content3        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text3      = 'Start Now';
            $this->config->features_button_link3    = $CFG->wwwroot . '/course';
            $this->config->bg_img3    = $CFG->wwwroot.'/theme/edly/pix/features/features3.png';
            $this->config->img3    = $CFG->wwwroot.'/theme/edly/pix/features/icon3.svg';

            $this->config->features_title4          = 'Always Interactive Learning';
            $this->config->features_content4        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text4      = 'Start Now';
            $this->config->features_button_link4    = $CFG->wwwroot . '/course';
            $this->config->bg_img4    = $CFG->wwwroot.'/theme/edly/pix/features/features4.png';
            $this->config->img4    = $CFG->wwwroot.'/theme/edly/pix/features/icon4.svg';

            $this->config->features_title5          = 'Always Interactive Learning';
            $this->config->features_content5        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text5      = 'Start Now';
            $this->config->features_button_link5    = $CFG->wwwroot . '/course';
            $this->config->bg_img5    = $CFG->wwwroot.'/theme/edly/pix/features/features4.png';
            $this->config->img5    = $CFG->wwwroot.'/theme/edly/pix/features/icon4.svg';

            $this->config->features_title6          = 'Always Interactive Learning';
            $this->config->features_content6        = 'Break into a new field like format technology or data science get started.';
            $this->config->features_link_text6      = 'Start Now';
            $this->config->features_button_link6    = $CFG->wwwroot . '/course';
            $this->config->bg_img6    = $CFG->wwwroot.'/theme/edly/pix/features/features4.png';
            $this->config->img6    = $CFG->wwwroot.'/theme/edly/pix/features/icon4.svg';
        }
    }

    public function get_content() {
        global $CFG, $DB;

        $this->content         =  new stdClass;

        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }

        $features_number = 6;
        if(isset($this->config->features_number)){
            $features_number = $this->config->features_number;
        }

        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}

        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        $uid = 'fc' . (int)$this->instance->id; // unique par instance
        $dotsid = $uid . '-dots';

        $text = '';
        if($style == 2):
            $text .= '
            <div class="features-area pt-100 pb-75">
                <div class="container">';
                    if($this->content->top_title || $this->content->title):
                        $text .= '
                        <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                            <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                        </div>';
                    endif;
                // ⤵️ wrapper + scroller
                $text .= '
                    <div id="'.$uid.'" class="fc-scroller" role="list">
                        <div class="row fc-row">';
                        for($i = 1; $i <= $features_number; $i++) {
                            $img                    = 'img' . $i;
                            $bg_img                 = 'bg_img' . $i;
                            $features_title         = 'features_title' . $i;
                            $features_content       = 'features_content' . $i;
                            $features_button_link   = 'features_button_link' . $i;
                            $features_link_text     = 'features_link_text' . $i;

                            // Bg Image
                            if(isset($this->config->$bg_img)) { $bg_img = $this->config->$bg_img; }else{ $bg_img = ''; }

                            // Image
                            if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                            // Title
                            if(isset($this->config->$features_title)) { $features_title = $this->config->$features_title; }else{ $features_title = ''; }

                            // Link Title
                            if(isset($this->config->$features_link_text)) { $features_link_text = $this->config->$features_link_text; }else{ $features_link_text = ''; }

                            // Content
                            if(isset($this->config->$features_content)) { $features_content = $this->config->$features_content; }else{ $features_content = ''; }

                            // Button Link
                            if(isset($this->config->$features_button_link)) { $features_button_link = $this->config->$features_button_link; }else{ $features_button_link = ''; }
                            $text .= '
                            <div class="fc-item" role="listitem">
                                <div class="features-card with-box-shadow">
                                    <div class="image">';
                                        if($bg_img):
                                            $text .= '                    
                                            <img src="'.edly_block_image_process($bg_img).'" alt="'.strip_tags($features_title).'">';
                                        endif;
                                        if($img):
                                            $text .= '
                                            <div class="icon">
                                                <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($features_title).'">
                                            </div>';
                                        endif;
                                        $text .= '
                                    </div>
                                    <h3>'.format_text($features_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                    <p>'.format_text($features_content, FORMAT_HTML, array('filter' => true)).'</p>';
                                    if($features_button_link):
                                        $text .= '
                                        <a href="'.$features_button_link.'" class="features-btn">
                                            '.format_text($features_link_text, FORMAT_HTML, array('filter' => true)).'
                                        </a>';
                                    endif;
                                    $text .= '
                                </div>
                            </div>';
                        } 

                // 🔒 ferme la row ET le scroller avant la flèche “next”
                $text .= '
                        </div> <!-- /.row.fc-row -->
                    </div> <!-- /#'.$uid.'.fc-scroller -->

                    <div id="'.$dotsid.'" class="fc-dots" aria-label="Pagination des features"></div>';

                $text .= '
            </div>
        </div>';
        elseif($style == 3):
            $text .= '
            <div class="features-area pt-100 pb-75">
                <div class="container">';
                    if($this->content->top_title || $this->content->title):
                        $text .= '
                        <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                            <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>
                        </div>';
                    endif;

                // ⤵️ wrapper + scroller
                $text .= '
                    <div id="'.$uid.'" class="fc-scroller" role="list">
                        <div class="row fc-row">';
                        for($i = 1; $i <= $features_number; $i++) {
                            $img                    = 'img' . $i;
                            $bg_img                 = 'bg_img' . $i;
                            $features_title         = 'features_title' . $i;
                            $features_content       = 'features_content' . $i;
                            $features_button_link   = 'features_button_link' . $i;
                            $features_link_text     = 'features_link_text' . $i;

                            // Bg Image
                            if(isset($this->config->$bg_img)) { $bg_img = $this->config->$bg_img; }else{ $bg_img = ''; }

                            // Image
                            if(isset($this->config->$img)) { $img = $this->config->$img; }else{ $img = ''; }

                            // Title
                            if(isset($this->config->$features_title)) { $features_title = $this->config->$features_title; }else{ $features_title = ''; }

                            // Link Title
                            if(isset($this->config->$features_link_text)) { $features_link_text = $this->config->$features_link_text; }else{ $features_link_text = ''; }

                            // Content
                            if(isset($this->config->$features_content)) { $features_content = $this->config->$features_content; }else{ $features_content = ''; }

                            // Button Link
                            if(isset($this->config->$features_button_link)) { $features_button_link = $this->config->$features_button_link; }else{ $features_button_link = ''; }
                            $text .= '
                            
                            <div class="fc-item" role="listitem">
                                <div class="features-card with-box-shadow">';
                                    if($img):
                                        $text .= '
                                        <div class="icon-image bg-'.$i.'">
                                            <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($features_title).'">
                                        </div>';
                                    endif;
                                    $text .= '
                                    <h3>'.format_text($features_title, FORMAT_HTML, array('filter' => true)).'</h3>
                                    <p>'.format_text($features_content, FORMAT_HTML, array('filter' => true)).'</p>';
                                    if($features_button_link):
                                        $text .= '
                                        <a href="'.$features_button_link.'" class="features-btn">
                                            '.format_text($features_link_text, FORMAT_HTML, array('filter' => true)).'
                                        </a>';
                                    endif;
                                    $text .= '
                                </div>
                            </div>';
                        } 

                // 🔒 ferme la row ET le scroller avant la flèche “next”
                $text .= '
                        </div> <!-- /.row.fc-row -->
                    </div> <!-- /#'.$uid.'.fc-scroller -->

                    <div id="'.$dotsid.'" class="fc-dots" aria-label="Pagination des features"></div>';

                $text .= '
            </div>
        </div>';
    else:
        $text .= '
        <div class="features-area pb-75">
            <div class="container">';
                if ($this->content->top_title || $this->content->title):
                    $text .= '
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, ['filter' => true]).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, ['filter' => true]).'</h2>
                    </div>';
                endif;

                // ⤵️ wrapper + scroller
                $text .= '
                    <div id="'.$uid.'" class="fc-scroller" role="list">
                        <div class="row fc-row">';

                            for ($i = 1; $i <= $features_number; $i++) {
                                $img                  = 'img'.$i;
                                $bg_img               = 'bg_img'.$i;
                                $features_title       = 'features_title'.$i;
                                $features_content     = 'features_content'.$i;
                                $features_button_link = 'features_button_link'.$i;
                                $features_link_text   = 'features_link_text'.$i;

                                $bg_img               = isset($this->config->$bg_img) ? $this->config->$bg_img : '';
                                $img                  = isset($this->config->$img) ? $this->config->$img : '';
                                $features_title       = isset($this->config->$features_title) ? $this->config->$features_title : '';
                                $features_link_text   = isset($this->config->$features_link_text) ? $this->config->$features_link_text : '';
                                $features_content     = isset($this->config->$features_content) ? $this->config->$features_content : '';
                                $features_button_link = isset($this->config->$features_button_link) ? $this->config->$features_button_link : '';

                                $text .= '
                                <div class="fc-item" role="listitem">
                                    <div class="features-card">
                                        <div class="image">';
                                            if ($bg_img) {
                                                $text .= '<img src="'.edly_block_image_process($bg_img).'" alt="'.strip_tags($features_title).'">';
                                            }
                                            if ($img) {
                                                $text .= '<div class="icon">
                                                    <img src="'.edly_block_image_process($img).'" alt="'.strip_tags($features_title).'">
                                                </div>';
                                            }
                                        $text .= '</div>
                                        <h3>'.format_text($features_title, FORMAT_HTML, ['filter' => true]).'</h3>
                                        <p>'.format_text($features_content).'</p>';
                                        if ($features_button_link) {
                                            $text .= '<a href="'.$features_button_link.'" class="features-btn">'
                                                .format_text($features_link_text, FORMAT_HTML, ['filter' => true]).'</a>';
                                        }
                                $text .= '    </div>
                                </div>';
                            }

                // 🔒 ferme la row ET le scroller avant la flèche “next”
                $text .= '
                        </div> <!-- /.row.fc-row -->
                    </div> <!-- /#'.$uid.'.fc-scroller -->

                    <div id="'.$dotsid.'" class="fc-dots" aria-label="Pagination des features"></div>';

                $text .= '
            </div>
        </div>';
    endif;


    $text .= '<script>
            (function(){
            const scroller = document.getElementById("'.$uid.'");
            const dots = document.getElementById("'.$dotsid.'");
            if(!scroller || !dots) return;

            const items = scroller.querySelectorAll(".fc-item");
            if(!items.length) return;

            // Crée les dots
            items.forEach((_, i)=>{
                const b = document.createElement("button");
                b.type = "button";
                b.className = "fc-dot";
                b.setAttribute("aria-label", "Aller à la carte " + (i+1));
                b.addEventListener("click", ()=> {
                const x = items[i].offsetLeft;
                scroller.scrollTo({left:x, behavior:"smooth"});
                });
                dots.appendChild(b);
            });

            function activeIndex(){
                // Choisit l’item le plus à gauche dans le viewport
                let idx = 0, min = Infinity;
                const left = scroller.scrollLeft;
                items.forEach((el, i)=>{
                const d = Math.abs(el.offsetLeft - left);
                if (d < min){ min = d; idx = i; }
                });
                return idx;
            }

            function updateDots(){
                const idx = activeIndex();
                dots.querySelectorAll(".fc-dot").forEach((d, i)=>{
                if (i === idx) d.setAttribute("aria-current","true");
                else d.removeAttribute("aria-current");
                });
            }

            scroller.addEventListener("scroll", updateDots, {passive:true});
            window.addEventListener("resize", updateDots);
            updateDots();
            })();
            </script>';

        $this->content->footer = '';
        $this->content->text   = $text;

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