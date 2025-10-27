<?php
require(__DIR__.'/../../config.php');

if (is_siteadmin()) {
    // L’admin peut accéder à la page native (on ajoute un flag pour éviter la boucle)
    redirect(new moodle_url('/course/index.php', ['campus' => 'pass']));
} else {
    // Tout le monde → catalogue Campus
    redirect(new moodle_url('/local/campus/courses.php'));
}
