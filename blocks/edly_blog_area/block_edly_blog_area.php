<?php
global $CFG;
require_once($CFG->dirroot .'/blog/lib.php');
require_once($CFG->dirroot .'/blog/locallib.php');
require_once($CFG->dirroot . '/theme/edly/inc/block_handler/get-content.php');
class block_edly_blog_area extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_edly_blog_area');
    }

    // Declare second
    public function specialization()
    {
        global $CFG, $DB;
        include($CFG->dirroot . '/theme/edly/inc/block_handler/specialization.php');
        if (empty($this->config)) {
            $this->config = new \stdClass();            
            $this->config->style = 1;
            $this->config->top_title        = 'Top Articles';
            $this->config->title            = 'Want To <span>Learn</span> More? Read Blog';
            $this->config->by_title         = 'By';
            $this->config->button_text      = 'View All';
            $this->config->button_link      = $CFG->wwwroot . '/blog';
        }
    }

    public function get_content() {
        global $CFG, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content         =  new stdClass;
        
        if(!empty($this->config->title)){$this->content->title = $this->config->title;} else {$this->content->title = '';}
        
        if(!empty($this->config->top_title)){$this->content->top_title = $this->config->top_title;} else {$this->content->top_title = '';}

        if(!empty($this->config->by_title)){$this->content->by_title = $this->config->by_title;} else {$this->content->by_title = '';}
        
        if(!empty($this->config->button_text)){$this->content->button_text = $this->config->button_text;} else {$this->content->button_text = '';}

        if(!empty($this->config->shape_image)){$this->content->shape_image = $this->config->shape_image;} else {$this->content->shape_image = '';}

        if(!empty($this->config->button_link)){$this->content->button_link = $this->config->button_link;} else {$this->content->button_link = '';}

        if(!empty($this->config->posts)){$this->content->posts = $this->config->posts;} else { $this->content->posts = '';}

        $url = new moodle_url('/blog/index.php');

        global $CFG;
        $bloglisting = new blog_listing();

        $entries = $bloglisting->get_entries();
        
        $entrieslist = array();
        $viewblogurl = new moodle_url('/blog/index.php');
        
        $style = 1;
        if(isset($this->config->style)){
            $style = $this->config->style;
        }

        $text = '';
        if($style = 2):
            $text .= '
            <div class="blog-area pt-100 pb-75">
                <div class="container">
                    <div class="section-title" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                        <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                        <h2>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h2>';
                        if(!empty($this->content->button_text) && !empty($this->content->button_link)){
                            $text .= '
                            <div class="find-events-btn">
                                <a href="'.$this->content->button_link.'" class="default-btn">'.format_text($this->content->button_text, FORMAT_HTML, array('filter' => true)).'</a>
                            </div>';
                        }
                        $text .= '
                    </div>

                    <div class="row justify-content-center">';
                        if($this->content->posts):
                            foreach ($entries as $entryid => $entry) {
                                $viewblogurl->param('entryid', $entryid);
                                $entrylink = html_writer::link($viewblogurl, shorten_text($entry->subject));
                                $entrieslist[] = $entrylink;
                
                                $blogentry = new blog_entry($entryid);
                                $blogattachments = $blogentry->get_attachments();

                                $short_summary = $entry->summary;
                                $short_summary = strip_tags( $short_summary);
                                $short_summary = implode(' ', array_slice(str_word_count($short_summary,1), 0, 15));

                                if(in_array($entry->id, $this->content->posts)):
                                    $text .= '
                                    <div class="col-xl-4 col-md-6">
                                        <div class="blog-card">
                                            <div class="blog-image">
                                                <a href="'.$viewblogurl.'">
                                                    <img src="'.$blogattachments[0]->url.'" alt="'.strip_tags($entry->subject).'">
                                                </a>
                                                <div class="tag">
                                                    <span>'.format_text( userdate($entry->created, '%d %b', 0), FORMAT_HTML, array('filter' => true) ).'</span>
                                                </div>
                                            </div>
                                            <div class="blog-content">
                                                <h3>
                                                    <a href="'.$viewblogurl.'">'.format_text($entry->subject, FORMAT_HTML, array('filter' => true)).'</a>
                                                </h3>
                                                <ul class="meta">
                                                    <li><i class="ri-user-3-line"></i> '.$this->content->by_title.' '.$entry->firstname.' '.$entry->lastname.'</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>';
                                endif;
                            }
                        endif;
                        $text .= '
                    </div>
                </div>
            </div>';
        else:
            $text .= '
            <div class="find-events-area ptb-100">
                <div class="container">
                    <div class="row justify-content-center align-items-center">
                        <div class="col-xl-3 col-md-12" data-aos="zoom-out-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                            <div class="find-events-content">
                                <span class="sub">'.format_text($this->content->top_title, FORMAT_HTML, array('filter' => true)).'</span>
                                <h3>'.format_text($this->content->title, FORMAT_HTML, array('filter' => true)).'</h3>';

                                if(!empty($this->content->button_text) && !empty($this->content->button_link)){
                                    $text .= '
                                    <div class="find-events-btn">
                                        <a href="'.$this->content->button_link.'" class="default-btn">'.format_text($this->content->button_text, FORMAT_HTML, array('filter' => true)).'</a>
                                    </div>';
                                }
                                $text .= '
                            </div>
                        </div>
                        <div class="col-xl-9 col-md-12">
                            <div class="row justify-content-center">';
                                if($this->content->posts):
                                    foreach ($entries as $entryid => $entry) {
                                        $viewblogurl->param('entryid', $entryid);
                                        $entrylink = html_writer::link($viewblogurl, shorten_text($entry->subject));
                                        $entrieslist[] = $entrylink;
                        
                                        $blogentry = new blog_entry($entryid);
                                        $blogattachments = $blogentry->get_attachments();
            
                                        $short_summary = $entry->summary;
                                        $short_summary = strip_tags( $short_summary);
                                        $short_summary = implode(' ', array_slice(str_word_count($short_summary,1), 0, 15));
            
                                        if(in_array($entry->id, $this->content->posts)):
                                            $text .= '
                                            <div class="col-xl-4 col-md-6" data-aos="fade-up" data-aos-delay="70" data-aos-duration="700" data-aos-once="true">
                                                <div class="find-events-card">
                                                    <div class="events-image">
                                                        <a href="'.$viewblogurl.'">
                                                            <img src="'.$blogattachments[0]->url.'" alt="'.strip_tags($entry->subject).'">
                                                        </a>
                                                    </div>
                                                    <div class="events-content">
                                                        <div class="date">'.format_text( userdate($entry->created, '%d %b', 0), FORMAT_HTML, array('filter' => true) ).'</div>
                                                        <h3>
                                                            <a href="'.$viewblogurl.'">'.format_text($entry->subject, FORMAT_HTML, array('filter' => true)).'</a>
                                                        </h3>
                                                        <span><i class="ri-user-3-line"></i> '.$this->content->by_title.' '.$entry->firstname.' '.$entry->lastname.'</span>
                                                    </div>
                                                </div>
                                            </div>';
                                        endif;
                                    }
                                endif;
                                $text .= '
                            </div>
                        </div>
                    </div>
                </div>';

                if($this->content->shape_image):
                    $shape_image = $this->content->shape_image;
                    $text .= '
                    <div class="find-events-shape">
                        <img src="'.edly_block_image_process($shape_image).'" alt="'. strip_tags($this->content->title).'">
                    </div>';
                endif;
                $text .= '
            </div>'; 
        endif;
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