<?php

class block_campus_slider_renderer extends plugin_renderer_base {

    public function render_slider($cards) {

        return $this->render_from_template(
            'block_campus_slider/slider',
            ['cards' => $cards]
        );
    }
}