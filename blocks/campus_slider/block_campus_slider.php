<?php

class block_campus_slider extends block_base {

    public function init() {
        $this->title = 'Campus Slider';
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $cards = [];

        for ($i = 1; $i <= 10; $i++) {

            $image = $this->config->{'card'.$i.'_image_url'} ?? '';
            $text  = $this->config->{'card'.$i.'_button_text'} ?? '';
            $url   = $this->config->{'card'.$i.'_button_url'} ?? '';

            if (!empty($image)) {

                $cards[] = [
                    'image' => $image,
                    'button_text' => $text,
                    'button_url' => $url,
                    'highlight' => ($i == 2)
                ];

            }
        }

        $this->content->text =
            $this->page->get_renderer('block_campus_slider')
            ->render_slider($cards);

        $this->page->requires->js_call_amd(
            'block_campus_slider/carousel',
            'init'
        );

        return $this->content;
    }

    public function applicable_formats() {
    return [
        'site-index' => true,
        'course-view' => true,
        'my' => true
    ];
}
}

