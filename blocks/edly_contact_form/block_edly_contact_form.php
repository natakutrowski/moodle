<?php
defined('MOODLE_INTERNAL') || die();

class block_edly_contact_form extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_edly_contact_form');
    }

    public function specialization() {
        if (empty($this->config)) {
            $this->config = (object)[
                'recipient' => '',  // email destinataire
                'title'     => get_string('defaulttitle', 'block_edly_contact_form'),
                'strap'     => get_string('defaultstrap', 'block_edly_contact_form'),
            ];
        }
    }

    public function get_content() {
        global $PAGE, $OUTPUT, $USER;

        if ($this->content !== null) { return $this->content; }

        // CSS du bloc + RemixIcon pour les pictos
        $PAGE->requires->css('/blocks/edly_contact_form/styles.css');
        
        $instanceid = $this->instance->id ?? 0;
        $formid  = 'ecf-form-'.$instanceid;
        $btnid   = 'ecf-submit-'.$instanceid;
        $emailid = 'ecf-email-'.$instanceid;
        $chkid   = 'ecf-accept-'.$instanceid;

        // Traitement soumission
        $sentok = false; $errmsg = '';
        if (optional_param('ecf_submit', 0, PARAM_BOOL) && confirm_sesskey()) {
            $fullname = trim(optional_param('ecf_fullname', '', PARAM_RAW_TRIMMED));
            $email    = trim(optional_param('ecf_email',    '', PARAM_RAW_TRIMMED));
            $message  = trim(optional_param('ecf_message',  '', PARAM_RAW));
            $accept   = optional_param('ecf_accept', 0, PARAM_BOOL);

            if (!validate_email($email)) {
                $errmsg = get_string('invalidemail', 'block_edly_contact_form');
            } else if (!$accept) {
                $errmsg = get_string('mustaccept', 'block_edly_contact_form');
            } else {
                // Compose & send
                $to = trim((string)($this->config->recipient ?? ''));
                if ($to === '') {
                    // si non configuré → admin
                    $admin = get_admin();
                    $to = $admin->email;
                }

                $subject = get_string('mailsubject', 'block_edly_contact_form', format_string($fullname ?: get_string('anonymous', 'block_edly_contact_form')));
                $plain   = "Nom: ".($fullname ?: '-')."\nEmail: {$email}\n\nMessage:\n{$message}\n";
                $html    = html_writer::tag('p', '<strong>Nom</strong>: '.s($fullname ?: '-'))
                         . html_writer::tag('p', '<strong>Email</strong>: '.s($email))
                         . html_writer::tag('p', '<strong>Message</strong>:<br>'.format_text($message, FORMAT_PLAIN));

                // from user
                $fromuser = isloggedin() ? $USER : core_user::get_support_user();

                // to user (si l'email ne correspond pas à un compte, on fabrique un objet)
                $touser = \core_user::get_user_by_email($to);
                if (!$touser) {
                    $touser = (object)[
                        'id' => 0, 'email' => $to, 'maildisplay' => true, 'mailformat' => 1,
                        'firstname' => 'Contact', 'lastname' => ''
                    ];
                }

                $sentok = email_to_user($touser, $fromuser, $subject, $plain, $html);
                if (!$sentok) {
                    $errmsg = get_string('sendfail', 'block_edly_contact_form');
                }
            }
        }

        // Feedback
        $notif = '';
        if ($sentok) {
            $notif = html_writer::div(get_string('sendsuccess', 'block_edly_contact_form'), 'ecf-alert success');
        } else if ($errmsg !== '') {
            $notif = html_writer::div(s($errmsg), 'ecf-alert error');
        }

        // HTML
        $title = format_text($this->config->title ?? '', FORMAT_HTML, ['filter'=>true]);
        $strap = format_text($this->config->strap ?? '', FORMAT_HTML, ['filter'=>true]);

        $action = new moodle_url($PAGE->url, ['blockid' => $instanceid]);

        $imgurl = trim((string)($this->config->imageurl ?? ''));
        $imgalt = trim((string)($this->config->imagealt ?? ''));

        // Liens sociaux
        $yu = trim((string)($this->config->youtube   ?? ''));
        $ig = trim((string)($this->config->instagram ?? ''));
        $tg = trim((string)($this->config->telegram  ?? ''));

        $social = [];
        if ($yu !== '') $social[] = '<a href="'.s($yu).'" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a>';
        if ($ig !== '') $social[] = '<a href="'.s($ig).'" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-fill"></i></a>';
        if ($tg !== '') $social[] = '<a href="'.s($tg).'" target="_blank" rel="noopener" aria-label="Telegram"><i class="ri-telegram-fill"></i></a>';

        $figure = $imgurl ? '
          <figure class="ecf-figure">
            <img src="'.s($imgurl).'" alt="'.s($imgalt ?: 'Contact illustration').'" loading="lazy">
          </figure>' : '';

        $left = '
          <div class="ecf-left">
            <h2 class="ecf-title">'.$title.'</h2>
            '.$figure.'
            <div class="ecf-address">
              '.get_string('defaultaddress', 'block_edly_contact_form').'
            </div>
            '.(count($social) ? '<div class="ecf-social">'.implode("\n", $social).'</div>' : '').'
          </div>';


        $right  = '
          <div class="ecf-right">
            <p class="ecf-strap">'.$strap.'</p>

            '.$notif.'

            <form id="'.$formid.'" class="ecf-form" action="'.$action.'" method="post" novalidate>
              '.html_writer::input_hidden_params($action).'
              <input type="hidden" name="sesskey" value="'.sesskey().'">
              <input type="hidden" name="ecf_submit" value="1">

              <label class="ecf-field">
                <span>'.get_string('fullname', 'block_edly_contact_form').'</span>
                <input type="text" name="ecf_fullname" placeholder="'.get_string('ph_fullname','block_edly_contact_form').'" autocomplete="name">
              </label>

              <label class="ecf-field">
                <span>'.get_string('email', 'block_edly_contact_form').' <b class="req">*</b></span>
                <input id="'.$emailid.'" type="email" name="ecf_email" required placeholder="'.get_string('ph_email','block_edly_contact_form').'" autocomplete="email">
              </label>

              <label class="ecf-field">
                <span>'.get_string('message', 'block_edly_contact_form').'</span>
                <textarea name="ecf_message" rows="6" placeholder="'.get_string('ph_message','block_edly_contact_form').'"></textarea>
              </label>

              <label class="ecf-accept">
                <input id="'.$chkid.'" type="checkbox" name="ecf_accept" value="1">
                <span>'.get_string('acceptpolicy','block_edly_contact_form').'</span>
              </label>

              <button id="'.$btnid.'" type="submit" class="ecf-submit" disabled>'.get_string('send','block_edly_contact_form').'</button>
            </form>
          </div>';

        // JS (activation bouton si email valide + checkbox cochée)
        $ajaxurl = new moodle_url('/blocks/edly_contact_form/ajax.php');

        $PAGE->requires->js_amd_inline("
        (function(){
        var form = document.getElementById('{$formid}');
        if (!form) return;

        var btn  = form.querySelector('#{$btnid}');
        var mail = form.querySelector('input[name=\"ecf_email\"]');
        var chk  = form.querySelector('input[name=\"ecf_accept\"]');

        function emailOK(){
            if (!mail) return false;
            var v = (mail.value || '').trim();
            // Validation native si dispo, sinon regex de secours
            return (typeof mail.checkValidity === 'function') ? mail.checkValidity() : (/^\\S+@\\S+\\.\\S+$/.test(v));
        }

        function toggle(){
            if (!btn) return;
            btn.disabled = !(emailOK() && chk && chk.checked);
        }

        // Active/désactive en live (SCOPÉ au form)
        form.addEventListener('input',  toggle, true);
        form.addEventListener('change', toggle, true);
        toggle();

        // Soumission Ajax
        form.addEventListener('submit', function(ev){
            ev.preventDefault();
            if (!btn) return;

            btn.disabled = true;
            btn.classList.add('loading');

            var fd = new FormData(form);
            fd.set('blockid', '{$instanceid}');

            fetch('{$ajaxurl}', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
            })
            .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
            .then(function(res){
            if (res && res.ok){
                // Remplacer le formulaire par le message de succès
                var note = document.createElement('div');
                note.className = 'ecf-ajax-note ok';
                note.textContent = res.message || '".addslashes(get_string('sendsuccess','block_edly_contact_form'))."';
                form.parentNode.replaceChild(note, form);
            } else {
                showError((res && res.error) ? res.error : 'error');
            }
            })
            .catch(function(){ showError('network'); })
            .finally(function(){
            if (btn) btn.classList.remove('loading');
            });
        });

        function showError(code){
            var map = {
            invalidsesskey : '".addslashes(get_string('invalidsesskey', 'error'))."',
            invalidemail   : '".addslashes(get_string('invalidemail','block_edly_contact_form'))."',
            mustaccept     : '".addslashes(get_string('mustaccept','block_edly_contact_form'))."',
            network        : '".addslashes(get_string('sendfail','block_edly_contact_form'))."',
            error          : '".addslashes(get_string('sendfail','block_edly_contact_form'))."'
            };
            var box = form.querySelector('.ecf-alert.error');
            if (!box){
            box = document.createElement('div');
            box.className = 'ecf-alert error';
            form.insertBefore(box, form.firstChild);
            }
            box.textContent = map[code] || map.error;
            // Réévalue les conditions pour éventuellement réactiver le bouton
            toggle();
        }
        })();
        ");


        $html =
          '<div class="ecf-wrap">
             <div class="ecf-card">
               '.$left.$right.'
             </div>
           </div>';

        $this->content           = new stdClass();
        $this->content->text     = $html;
        $this->content->footer   = '';

        return $this->content;
    }

    public function instance_allow_multiple(){ return true; }
    public function has_config(){ return false; }
    public function applicable_formats(){
        return ['all'=>true,'my'=>false,'admin'=>false,'course-view'=>true,'course'=>true];
    }
}
