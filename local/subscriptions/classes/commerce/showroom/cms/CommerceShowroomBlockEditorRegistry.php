<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Field schemas and validation for the visual Showroom block editors.
 */
final class CommerceShowroomBlockEditorRegistry {
    /** @return array<string,array<string,mixed>> */
    public static function schema(string $type): array {
        $schemas = self::schemas();
        return $schemas[$type] ?? $schemas['html'];
    }

    /** @return array<string,array<string,mixed>> */
    public static function schemas(): array {
        return [
            'hero' => self::definition([
                self::text('expeditionlabel', 'Badge expédition'),
                self::text('stagelabel', 'Badge étapes'),
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie rose du titre'),
                self::textarea('text', 'Sous-titre'),
                self::text('primarylabel', 'Libellé du CTA principal'),
                self::text('primarytarget', 'Cible du CTA principal', false, false),
                self::text('secondarylabel', 'Libellé du CTA secondaire'),
                self::text('secondarytarget', 'Cible du CTA secondaire', false, false),
                self::media('backgroundurl', 'Image de fond du Hero', 'image'),
                self::media('desktopimageurl', 'Image dans l’écran ordinateur / tablette', 'image'),
                self::media('mobileimageurl', 'Image dans l’écran téléphone', 'image'),
                self::select('herovariant', 'Variante du Hero', [
                    'standard' => 'Standard', 'cover' => 'Cover', 'split' => 'Split', 'minimal' => 'Minimal',
                ]),
                self::select('mediaposition', 'Position du visuel', [
                    'right' => 'À droite', 'left' => 'À gauche',
                ]),
                self::checkbox('showgustave', 'Afficher Gustave'),
            ]),
            'stats' => self::definition([
                self::text('title', 'Titre du bloc chiffres (optionnel)'),
                self::textarea(
                    'items',
                    '4 chiffres sous le Hero (une ligne : valeur|libellé|icône)',
                    true
                ),
                self::checkbox('animate', 'Animer les chiffres au scroll'),
            ]),
            'video' => self::definition([
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte'),
                self::media('posterurl', 'Image poster', 'image'),
                self::media('videourl', 'Vidéo', 'video'),
                self::select('ratio', 'Ratio', ['16:9' => '16:9', '21:9' => '21:9', '4:3' => '4:3']),
                self::checkbox('modal', 'Lecture dans une modale'),
            ]),
            'problem' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Début du titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'Cartes (titre|texte|icône)', true),
                self::text('solutiontitle', 'Début du titre du bloc solution'),
                self::text('solutiontitleaccent', 'Passage rose/gras du titre solution'),
                self::text('solutiontext', 'Début du texte du bloc solution'),
                self::text('solutiontextaccent', 'Premier passage rose/gras du texte solution'),
                self::text('solutiontextmiddle', 'Texte entre les passages mis en valeur'),
                self::text('solutiontextaccent2', 'Second passage rose/gras du texte solution'),
            ]),
            'problem_interactive' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::textarea('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::text('choiceslabel', 'Titre des propositions'),
                self::textarea('choices', 'Propositions (une par ligne)', true),
                self::text('draghint', 'Aide au glisser-déposer'),
                self::text('taphint', 'Aide mobile au toucher'),
                self::text('correctanswer', 'Réponse correcte', true),
                self::text('targetlabel', 'Question au-dessus de la cible'),
                self::textarea('consequences', 'Conséquences (une par ligne)', true),
                self::text('successfeedback', 'Feedback bonne réponse'),
                self::text('errorfeedback', 'Feedback mauvaise réponse'),
                self::text('solutiontitle', 'Début du titre du bloc solution'),
                self::text('solutiontitleaccent', 'Passage rose du titre solution'),
                self::textarea('solutiontext', 'Début du texte du bloc solution'),
                self::text('solutiontextaccent', 'Passage rose du texte solution'),
            ]),
            'learning_method' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('subtitle', 'Sous-titre — ligne 1'),
                self::text('subtitleaccent', 'Sous-titre — ligne rose'),
                self::textarea('intro', 'Texte introductif'),
                self::text('stage1title', 'Bloc 1 — titre'),
                self::textarea('stage1items', 'Bloc 1 — bulles (une par ligne)', true),
                self::text('stage1footer', 'Bloc 1 — texte du bas'),
                self::text('stage2title', 'Bloc 2 — titre'),
                self::textarea('stage2items', 'Bloc 2 — lignes (texte|icône)', true),
                self::text('stage2footer', 'Bloc 2 — texte du bas'),
                self::text('stage3title', 'Bloc 3 — titre'),
                self::textarea('stage3items', 'Bloc 3 — lignes (une par ligne)', true),
                self::text('stage3footer', 'Bloc 3 — texte rose du bas'),
                self::text('summarytitle', 'Bloc inférieur — titre'),
                self::textarea('summaryitems', 'Bloc inférieur — étapes (texte|icône)', true),
            ]),
            'content_highlights' => self::definition([
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte'),
                self::textarea('items', 'Points clés (titre|texte|icône)', true),
            ]),
            'ascent' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Sous-titre'),
                self::textarea(
                    'cards',
                    'Cartes (numéro|titre|texte|icône|sommet 0/1)',
                    true
                ),
                self::textarea(
                    'summaryitems',
                    'Indicateurs bas (titre|texte|icône)',
                    true
                ),
                self::color(
                    'gradientstart',
                    'Couleur de départ du parcours',
                    '#ff8ac6'
                ),
                self::color(
                    'gradientend',
                    'Couleur du sommet',
                    '#6226ad'
                ),
                self::media('backgroundurl', 'Image de fond alternative', 'image'),
                self::range('backgroundopacity', 'Opacité de l’image de fond', 0, 100, 1, 100),
                self::range('backgroundblur', 'Flou de l’image de fond (px)', 0, 24, 1, 0),
                self::checkbox('backgrounddesktop', 'Utiliser l’image de fond sur Desktop'),
                self::checkbox('backgroundmobile', 'Utiliser l’image de fond sur Mobile'),
                self::media('cardimage1', 'Carte 01 — image (remplace l’icône)', 'image'),
                self::media('cardimage2', 'Carte 02 — image (remplace l’icône)', 'image'),
                self::media('cardimage3', 'Carte 03 — image (remplace l’icône)', 'image'),
                self::media('cardimage4', 'Carte 04 — image (remplace l’icône)', 'image'),
                self::media('cardimage5', 'Carte 05 — image (remplace l’icône)', 'image'),
                self::textarea(
                    'checkpoints',
                    'Anciennes étapes (compatibilité)',
                    false
                ),
            ]),
            'stage_method' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Début du titre', true),
                self::text('titlehighlight', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'Étapes (une ligne : titre|texte)', true),
                self::media('backgroundurl', 'Image de fond', 'image'),
                self::range('backgroundopacity', 'Opacité de l’image de fond', 0, 100, 1, 100),
                self::range('backgroundblur', 'Flou de l’image de fond (px)', 0, 24, 1, 0),
            ]),
            'exercise_explorer' => self::definition(array_merge([
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte'),
                self::checkbox('previewenabled', 'Afficher la prévisualisation interactive'),
            ], self::exercise_fields())),
            'offers' => self::definition([
                self::text('badge', 'Badge de section'),
                self::text('title', 'Début du titre'),
                self::text('titlehighlight', 'Partie du titre en rose'),
                self::text('titlesuffix', 'Fin du titre'),
                self::textarea('text', 'Sous-titre'),
                self::text('pdfrolelabel', 'PDF — Type commercial'),
                self::text('pdftitle', 'PDF — Titre'),
                self::textarea('pdfdescription', 'PDF — Description'),
                self::textarea('pdffeatures', 'PDF — Caractéristiques (une ligne par caractéristique)'),
                self::text('courserolelabel', 'Cours — Type commercial'),
                self::text('coursetitle', 'Cours — Titre'),
                self::textarea('coursedescription', 'Cours — Description'),
                self::textarea('coursefeatures', 'Cours — Caractéristiques (une ligne par caractéristique)'),
                self::text('bundlefeaturedlabel', 'Bundle — Badge'),
                self::text('bundlerolelabel', 'Bundle — Type'),
                self::text('bundletitle', 'Bundle — Titre'),
                self::text('bundlesubtitle', 'Bundle — Sous-titre'),
                self::textarea('bundledescription', 'Bundle — Description complémentaire (optionnelle)'),
                self::textarea('bundlefeatures', 'Bundle — Caractéristiques (une ligne par caractéristique)'),
                self::textarea('skus', 'SKUs (un par ligne)', true, false, false),
                self::select('featuredrole', 'Offre mise en avant', [
                    'bundle' => 'Bundle',
                    'pdf' => 'PDF',
                    'course' => 'Cours',
                ]),
                self::text('featuredsku', 'SKU mis en avant (option avancée)', false, false),
                self::checkbox('showsavings', 'Afficher les économies'),
                self::checkbox('showbadges', 'Afficher les badges'),
                self::select('cardvariant', 'Style des cartes', [
                    'classic' => 'Classique', 'premium' => 'Premium', 'minimal' => 'Minimal', 'horizontal' => 'Horizontal',
                ]),
                self::select('columns', 'Nombre de colonnes', ['2' => '2', '3' => '3', '4' => '4']),
                self::select('mobilepresentation', 'Présentation mobile', [
                    'stack' => 'Cartes les unes sous les autres',
                    'slider' => 'Slider horizontal avec indication de glissement',
                ]),
                self::select('cardstopspacing', 'Espace entre le sous-titre et les cartes', [
                    'compact' => 'Compact',
                    'normal' => 'Normal',
                    'airy' => 'Aéré',
                    'veryairy' => 'Très aéré',
                ]),
            ]),
            'comparison' => self::definition([
                self::text('eyebrow', 'Sur-titre / badge'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Sous-titre'),
                self::text('featurelabel', 'Case en haut à gauche'),
                self::textarea(
                    'rows',
                    'Lignes du comparatif (critère|PDF|Bundle|Cours)',
                    true
                ),
                self::text('order', 'Ordre des offres (rôles séparés par des virgules)', false, false),
            ]),
            'memory_method' => self::definition([
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte'),
                self::textarea('items', 'Principes (titre|texte|icône)', true),
            ]),
            'trust' => self::definition([
                self::textarea('items', 'Éléments de réassurance (titre|texte|icône)', true),
            ]),
            'testimonials' => self::definition([
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::textarea(
                    'items',
                    'Témoignages (une ligne : citation|auteur)',
                    true
                ),
            ]),
            'faq' => self::definition([
                self::text('eyebrow', 'Sur-titre / badge'),
                self::text('title', 'Titre'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'Questions / réponses (une ligne : question|réponse)', true),
                self::checkbox('singleopen', 'Une seule réponse ouverte à la fois'),
            ]),
            'support' => self::definition([
                self::text('title', 'Titre', true),
                self::textarea('text', 'Sous-titre'),
                self::text('buttonlabel', 'Libellé du bouton principal'),
                self::url('telegramurl', 'Lien Telegram'),
                self::url('whatsappurl', 'Lien WhatsApp'),
            ]),
            'bonus' => self::definition([
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte'),
                self::text('buttonlabel', 'Libellé du bouton', true),
                self::text('buttontarget', 'Cible du bouton', true, false),
            ]),
            'verbs_cards' => self::definition([
                self::media('imageurl', 'Image carrée', 'image'),
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'Bénéfices (titre|texte|icône ; utiliser \\n pour un retour à la ligne)', true),
                self::text('buttonlabel', 'Libellé du bouton', true),
                self::text('buttontarget', 'Lien du bouton', true, false),
            ]),
            'final_cta' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Sous-titre'),
                self::text('buttonlabel', 'Libellé du bouton'),
                self::text('buttontarget', 'Cible du bouton', false, false),
                self::select('style', 'Style', [
                    'primary' => 'Principal',
                    'secondary' => 'Secondaire',
                    'dark' => 'Sombre',
                ]),
                self::media('backgroundurl', 'Image de fond', 'image'),
                self::range('backgroundopacity', 'Opacité de l’image de fond', 0, 100, 1, 100),
                self::range('backgroundblur', 'Flou de l’image de fond (px)', 0, 24, 1, 0),

                self::checkbox('legalshowname', 'Legal — afficher le nom de l’entreprise', true),
                self::checkbox('legalshowaddress', 'Legal — afficher l’adresse', true),
                self::checkbox('legalshowlegal', 'Legal — afficher les mentions légales', true),
                self::checkbox('legalshowemail', 'Legal — afficher l’e-mail', true),
                self::checkbox('legalshowphone', 'Legal — afficher le téléphone', true),
                self::checkbox('legalshowwebsite', 'Legal — afficher le site web', true),
                self::checkbox('legalshowtaxnotice', 'Legal — afficher la note fiscale', true),
                self::checkbox('legalshowfooter', 'Legal — afficher le footer facture', true),
                self::checkbox('legalshowprivacy', 'Legal — afficher le lien Confidentialité', true),
                self::checkbox('legalshowterms', 'Legal — afficher le lien CGU', true),
                self::checkbox('legalshowoffer', 'Legal — afficher le lien CGV', true),
            ]),
            'html' => self::definition([
                self::text('title', 'Titre'),
                self::textarea('html', 'Contenu HTML', true, true),
            ]),
        ];
    }

    public static function is_media_field(string $type, string $fieldname): bool {
        foreach (self::schema($type)['fields'] as $field) {
            if (
                (string)$field['name'] === $fieldname
                && (string)$field['type'] === 'media'
            ) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string,mixed> $config @return array<string,mixed> */
    public static function normalise(string $type, array $config): array {
        $schema = self::schema($type);
        $result = [];
        $translations = is_array($config['translations'] ?? null)
            ? $config['translations']
            : [];

        foreach ($schema['fields'] as $field) {
            $name = (string)$field['name'];

            if (!empty($field['translatable'])) {
                foreach (CommerceShowroomBlockDefaultsCatalog::LANGUAGES as $language) {
                    $raw = $translations[$language][$name]
                        ?? ($language === 'fr' ? ($config[$name] ?? '') : '');
                    $result['translations'][$language][$name] =
                        self::normalise_value($field, $raw);
                }
                $result[$name] = $result['translations']['fr'][$name] ?? '';
                continue;
            }

            $raw = $config[$name]
                ?? ($field['type'] === 'checkbox'
                    ? (bool)($field['default'] ?? false)
                    : '');
            $result[$name] = self::normalise_value($field, $raw);
        }

        self::validate($type, $result);
        return $result;
    }

    /**
     * Validates only technical value formats.
     *
     * Empty editorial values are valid and deliberately activate the runtime
     * fallback chain (current language -> FR -> legacy presenter content).
     * URL and select validation remains handled by normalise_value().
     *
     * @param array<string,mixed> $config
     */
    public static function validate(string $type, array $config): void {
        // No semantic required-field validation by design.
    }

    private static function normalise_value(array $field, mixed $value): mixed {
        if ($field['type'] === 'checkbox') {
            return !empty($value);
        }

        if (in_array($field['type'], ['url', 'media'], true)) {
            $value = trim((string)$value);
            if (
                $value !== ''
                && !filter_var($value, FILTER_VALIDATE_URL)
                && !str_starts_with($value, '/')
            ) {
                throw new \invalid_parameter_exception(
                    'Invalid URL for ' . $field['name'] . '.'
                );
            }
            return $value;
        }

        if ($field['type'] === 'color') {
            $value = strtolower(trim((string)$value));
            $default = strtolower((string)($field['default'] ?? '#000000'));
            return preg_match('/^#[0-9a-f]{6}$/', $value) === 1
                ? $value
                : $default;
        }

        if ($field['type'] === 'range') {
            $min = (int)($field['min'] ?? 0);
            $max = (int)($field['max'] ?? 100);
            $default = (int)($field['default'] ?? $min);
            $number = is_numeric($value) ? (int)$value : $default;
            return (string)max($min, min($max, $number));
        }

        if ($field['type'] === 'select') {
            $allowed = array_keys($field['options'] ?? []);
            return in_array((string)$value, $allowed, true)
                ? (string)$value
                : (string)($allowed[0] ?? '');
        }

        return trim((string)$value);
    }

    /** @param array<int,array<string,mixed>> $fields @return array<string,mixed> */
    private static function definition(array $fields): array {
        $existing = array_map(
            static fn(array $field): string => (string)($field['name'] ?? ''),
            $fields
        );
        return ['fields' => array_merge($fields, self::layout_fields($existing))];
    }

    /**
     * Common, reusable presentation controls available on every block.
     *
     * Blocks with a specialised background implementation (Hero, Ascent,
     * Journey, Final CTA) keep their existing media/opacity/blur controls;
     * the common panel does not duplicate them.
     *
     * @param array<int,string> $existingfields
     * @return array<int,array<string,mixed>>
     */
    private static function layout_fields(array $existingfields = []): array {
        $fields = [
            self::select('sectionwidth', 'Largeur du contenu', [
                'contained' => 'Centré', 'wide' => 'Large', 'full' => 'Pleine largeur',
            ]),
            self::select('sectionbackground', 'Fond', [
                'default' => 'Par défaut',
                'white' => 'Blanc',
                'light' => 'Clair',
                'soft' => 'Doux',
                'campuspink' => 'Rose très pâle CampusFR',
                'dark' => 'Sombre',
                'gradient' => 'Dégradé CampusFR',
                'custom' => 'Couleur personnalisée',
                'image' => 'Image personnalisée',
            ]),
            self::color('sectionbackgroundcolor', 'Couleur de fond personnalisée', '#fff0f7'),
        ];

        if (!in_array('backgroundurl', $existingfields, true)) {
            $fields[] = self::media('sectionbackgroundimageurl', 'Image de fond', 'image');
        }
        if (!in_array('backgroundopacity', $existingfields, true)) {
            $fields[] = self::range('sectionbackgroundopacity', 'Opacité de l’image de fond', 0, 100, 1, 100);
        }
        if (!in_array('backgroundblur', $existingfields, true)) {
            $fields[] = self::range('sectionbackgroundblur', 'Flou de l’image de fond (px)', 0, 24, 1, 0);
        }

        $fields[] = self::select('sectionspacing', 'Espacement vertical', [
            'compact' => 'Compact', 'normal' => 'Normal', 'large' => 'Large',
        ]);
        $fields[] = self::select('sectionanimation', 'Animation', [
            'none' => 'Aucune', 'fade' => 'Fondu', 'rise' => 'Montée légère',
        ]);
        return $fields;
    }


    /**
     * Fixed Exercise Explorer editorial fields.
     *
     * These remain ordinary translatable Builder fields so they are persisted in
     * configjson and keep the standard advanced-JSON workflow. Metadata is only
     * used by showroom_builder.js to group them into twelve compact accordions.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function exercise_fields(): array {
        $fields = [];

        foreach (CommerceShowroomExerciseCatalog::all('fr') as $exercise) {
            $position = (int)$exercise['position'];
            $prefix = 'exercise' . str_pad((string)$position, 2, '0', STR_PAD_LEFT);
            $translations = (array)$exercise['translations'];

            $titlefallbacks = [];
            $textfallbacks = [];
            foreach (CommerceShowroomExerciseCatalog::LANGUAGES as $language) {
                $titlefallbacks[$language] = (string)($translations[$language]['title'] ?? '');
                $textfallbacks[$language] = (string)($translations[$language]['text'] ?? '');
            }

            $fields[] = self::text($prefix . 'title', 'Titre')
                + [
                    'exercise' => true,
                    'exercisekey' => (string)$exercise['key'],
                    'exerciseposition' => $position,
                    'exerciselabel' => (string)$exercise['title'],
                    'exerciseicon' => (string)$exercise['icon'],
                    'fallbacks' => $titlefallbacks,
                ];
            $fields[] = self::textarea($prefix . 'text', 'Sous-titre')
                + [
                    'exercise' => true,
                    'exercisekey' => (string)$exercise['key'],
                    'exerciseposition' => $position,
                    'exerciselabel' => (string)$exercise['title'],
                    'exerciseicon' => (string)$exercise['icon'],
                    'fallbacks' => $textfallbacks,
                ];
        }

        return $fields;
    }

    /** @return array<string,mixed> */
    private static function text(
        string $name,
        string $label,
        bool $required = false,
        bool $translatable = true
    ): array {
        return compact('name', 'label', 'required', 'translatable')
            + ['type' => 'text'];
    }

    /** @return array<string,mixed> */
    private static function textarea(
        string $name,
        string $label,
        bool $required = false,
        bool $editor = false,
        bool $translatable = true
    ): array {
        return compact('name', 'label', 'required', 'editor', 'translatable')
            + ['type' => 'textarea'];
    }

    /** @return array<string,mixed> */
    private static function media(
        string $name,
        string $label,
        string $kind = 'image'
    ): array {
        $isvideo = $kind === 'video';
        return compact('name', 'label', 'kind') + [
            'type' => 'media',
            'required' => false,
            'translatable' => false,
            'acceptedtypes' => $isvideo ? ['.mp4', '.webm'] : ['.png', '.jpg', '.jpeg', '.webp'],
            'maxbytes' => $isvideo
                ? CommerceShowroomBlockMediaManager::MAX_VIDEO_BYTES
                : CommerceShowroomBlockMediaManager::MAX_IMAGE_BYTES,
            'help' => get_string(
                $isvideo
                    ? 'commerce_showroom_builder_video_help'
                    : 'commerce_showroom_builder_image_help',
                'local_subscriptions'
            ),
        ];
    }

    /** @return array<string,mixed> */
    private static function url(string $name, string $label, bool $required = false): array {
        return compact('name', 'label', 'required') + ['type' => 'url', 'translatable' => false];
    }

    /** @return array<string,mixed> */
    private static function checkbox(
        string $name,
        string $label,
        bool $default = false
    ): array {
        return compact('name', 'label', 'default') + [
            'type' => 'checkbox',
            'required' => false,
            'translatable' => false,
        ];
    }

    /** @return array<string,mixed> */
    private static function color(
        string $name,
        string $label,
        string $default = '#000000'
    ): array {
        return compact('name', 'label', 'default') + [
            'type' => 'color',
            'required' => false,
            'translatable' => false,
        ];
    }

    /** @return array<string,mixed> */
    private static function range(
        string $name,
        string $label,
        int $min,
        int $max,
        int $step = 1,
        int $default = 0
    ): array {
        return compact('name', 'label', 'min', 'max', 'step', 'default') + [
            'type' => 'range',
            'required' => false,
            'translatable' => false,
        ];
    }

    /** @param array<string,string> $options @return array<string,mixed> */
    private static function select(string $name, string $label, array $options): array {
        return compact('name', 'label', 'options') + ['type' => 'select', 'required' => false, 'translatable' => false];
    }
}
