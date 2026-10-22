<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Field schemas and validation for the visual Showroom block editors.
 */
final class CommerceShowroomBlockEditorRegistry {
    /** @return array<string,array<string,mixed>> */
    public static function schema(string $type, ?array $offerroles = null): array {
        $schemas = self::schemas($offerroles);
        return $schemas[$type] ?? $schemas['html'];
    }

    /** @return array<string,array<string,mixed>> */
    public static function schemas(?array $offerroles = null): array {
        $offerroles = self::normalise_offer_roles($offerroles);

        return [
            'hero' => self::definition([
                self::text('expeditionlabel', 'Badge expédition'),
                self::text('stagelabel', 'Badge étapes'),
                self::text('eyebrow', 'Sur-titre'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie rose du titre'),
                self::text('titlesuffix', 'Suite du titre après le passage rose'),
                self::textarea('text', 'Sous-titre'),
                self::text('startlabel', 'Date de démarrage / mention sous les boutons'),
                self::text('primarylabel', 'Libellé du CTA principal'),
                self::text('primarytarget', 'Cible du CTA principal', false, false),
                self::text('secondarylabel', 'Libellé du CTA secondaire'),
                self::text('secondarytarget', 'Cible du CTA secondaire', false, false),
                self::checkbox('showvideobutton', 'Afficher le bouton vers la vidéo'),
                self::media('backgroundurl', 'Image de fond du Hero', 'image'),
                self::media('desktopimageurl', 'Image dans l’écran ordinateur / tablette', 'image'),
                self::media('mobileimageurl', 'Image dans l’écran téléphone', 'image'),
                self::select('herovariant', 'Variante du Hero', [
                    'standard' => 'Standard', 'cover' => 'Cover', 'split' => 'Split', 'minimal' => 'Minimal',
                    'a1_lavender' => 'A1 — photo lavande, 2 badges, 5 avantages',
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
                    'Jusqu’à 5 chiffres (valeur|libellé|icône) — tokens dynamiques acceptés',
                    true
                ),
                self::select('layout', 'Disposition', [
                    'bar' => 'Bandeau horizontal',
                    'grid_2x2_left' => 'Grille 2 × 2 à gauche',
                ]),
                self::checkbox('animate', 'Animer les chiffres au scroll'),
            ]),
            'video' => self::definition([
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'article_investigation' => 'Articles — enquête interactive',
                    'a1_showcase' => 'CampusFR A1 — aperçu vidéo + points clés',
                ]),
                self::text('eyebrow', 'Badge'),
                self::text('badgeicon', 'Icône du badge (classe Font Awesome)', false, false),
                self::text('title', 'Début du titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::text('textaccent', 'Ligne / passage mis en valeur'),
                self::text('checklisttitle', 'A1 vidéo — titre de la liste'),
                self::textarea('checklistitems', 'A1 vidéo — points (une ligne par point)'),
                self::text('posterlabel', 'A1 vidéo — légende sur le poster'),
                self::text('note', 'Note manuscrite (optionnel)'),
                self::textarea('steps', 'Étapes (numéro|titre|texte|icône)'),
                self::media('posterurl', 'Image poster', 'image'),
                self::media('videourl', 'Vidéo', 'video'),
                self::select('ratio', 'Ratio', ['16:9' => '16:9', '21:9' => '21:9', '4:3' => '4:3']),
                self::checkbox('modal', 'Lecture dans une modale'),
            ]),
            'anchor_nav' => self::definition([
                self::textarea('items', 'Liens (libellé|#ancre)', true),
                self::select('presentation', 'Présentation navigation', [
                    'standard' => 'Standard',
                    'a1_after_hero' => 'A1 : sticky sous la topbar, visible après le Hero',
                ]),
                self::checkbox('sticky', 'Navigation collante au défilement'),
                self::text('ctalabel', 'CTA à droite'),
                self::text('ctatarget', 'Cible du CTA', false, false),
            ]),
            'adventure_overview' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::media('centerimageurl', 'Visuel central', 'image'),
                self::text('centerlabel', 'Légende du visuel central'),
                self::textarea('items', 'Composantes (titre|texte|icône)', true),
                self::text('note', 'Note manuscrite / conclusion'),
            ]),
            'community_clan' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('badgeicon', 'Icône du badge (classe Font Awesome)', false, false),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::media('imageurl', 'Image principale', 'image'),
                self::text('imagenote', 'Note manuscrite sur la photo'),
                self::textarea('items', 'Bénéfices (titre|texte|icône) — tokens dynamiques acceptés', true),
                self::text('buttonlabel', 'Texte du bouton'),
                self::text('buttontarget', 'Cible du bouton', false, false),
            ]),
            'tutor_chat' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::media('imageurl', 'Portrait / visuel du tuteur', 'image'),
                self::textarea('features', 'Bénéfices (titre|texte|icône)', true),
                self::text('conversationtitle', 'Titre du mini chat'),
                self::textarea('messages', 'Conversation (auteur|message|tutor/student|heure)'),
                self::text('sidenote', 'Note manuscrite'),
                self::text('buttonlabel', 'Texte du bouton'),
                self::text('buttontarget', 'Cible du bouton', false, false),
            ]),
            'curriculum_tabs' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::text('tab1label', 'Onglet 1 — libellé'),
                self::text('tab2label', 'Onglet 2 — libellé'),
                self::select('defaulttab', 'Onglet ouvert par défaut', [
                    'topics' => 'Onglet 1', 'grammar' => 'Onglet 2',
                ], 'topics'),
                self::textarea('topics', 'Onglet 1 — lignes (groupe|icône|numéro|titre)', true),
                self::textarea('grammar', 'Onglet 2 — lignes (groupe|icône|numéro|titre)'),
            ]),
            'journey_map' => self::definition([
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard historique',
                    'interactive_a1' => 'Carte A1 géoréférencée interactive',
                ], 'standard'),
                self::text('counttitle', 'Carte — encadré haut gauche (titre)'),
                self::text('countsubtitle', 'Carte — encadré haut gauche (sous-titre)'),
                self::text('hint', 'Carte — encadré bas droite'),
                self::text('mobileswipelabel', 'Carte — indice de glissement mobile'),
                self::text('adventurelabel', 'Fiche — libellé aventure'),
                self::text('closelabel', 'Fiche — fermer'),
                self::textarea('captions', '30 légendes traduites (01|Jour 1 · titre)', true),
                self::textarea('geopoints', 'Carte A1 — positions uniques (01|lieu|lat|lon|photo)', false, false, false),
                self::textarea('pointpositions', 'Carte A1 — pastilles en % (01|x|y)', false, false, false),
                self::textarea('maplabels', 'Carte A1 — libellés superposés (clé|texte|x|y|rotation)', false, false, false),
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::media('imageurl', 'Carte / illustration', 'image'),
                self::textarea('stops', 'Standard historique : lieu|région'),
                self::textarea('items', 'Légende (titre|texte|icône)', true),
                self::text('note', 'Note manuscrite'),
            ]),
            'preparation_flow' => self::definition([
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard historique',
                    'lovable_a0' => 'A0 Lovable (icônes rondes + flèches)',
                ], 'standard'),
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Début du titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::text('titlesuffix', 'Suite du titre'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('steps', 'A0 Lovable : étapes (titre|icône)', false, false, true),
                self::text('annotation', 'Annotation manuscrite (en haut à droite)'),
                self::textarea('items', 'Standard : étapes (titre|texte|icône)', false, false, true),
                self::media('imageurl', 'Visuel central optionnel', 'image'),
                self::text('note', 'Note / précision'),
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
                self::select('presentation', 'Présentation', [
                    'interactive' => 'Interactive (glisser / toucher)',
                    'static_ppt' => 'Statique — présentation PPT',
                    'four_columns_ppt' => '4 colonnes — présentation PPT',
                ]),
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
                self::text('col1title', '4 colonnes — Colonne 1 : titre'),
                self::textarea('col1items', '4 colonnes — Colonne 1 : choix (une ligne par choix)'),
                self::textarea('col1footer', '4 colonnes — Colonne 1 : texte du bas'),
                self::text('col2title', '4 colonnes — Colonne 2 : titre'),
                self::text('col2centertext', '4 colonnes — Colonne 2 : texte dans le rond'),
                self::text('col2subtext', '4 colonnes — Colonne 2 : note sous le rond'),
                self::textarea('col3title', '4 colonnes — Colonne 3 : titre'),
                self::textarea('col3items', '4 colonnes — Colonne 3 : exemples (une ligne par exemple)'),
                self::textarea('col4title', '4 colonnes — Colonne 4 : titre'),
                self::textarea('col4items', '4 colonnes — Colonne 4 : points (titre|texte|icône)'),
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
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'article_meaning' => 'Exemples pédagogiques — 3 cartes + 1 colonne',
                ]),
                self::text('eyebrow', 'Sur-titre'),
                self::text('badgeicon', 'Icône du sur-titre (classe Font Awesome)'),
                self::text('sidenote', 'Note manuscrite en haut à droite'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Texte'),
                self::textarea('items', 'Points clés (titre|texte|icône|exemple facultatif)'),
                self::select('columns', 'Nombre de colonnes', [
                    '2' => '2', '3' => '3', '4' => '4', '6' => '6',
                ], '6'),
                self::textarea(
                    'learningcards',
                    'Exemples pédagogiques — 3 cartes (article|nom|sens|exemple|traduction)'
                ),
                self::text('sidetitle', 'Exemples pédagogiques — Colonne 4 : titre'),
                self::textarea('sideintro', 'Exemples pédagogiques — Colonne 4 : introduction'),
                self::textarea('sideitems', 'Exemples pédagogiques — Colonne 4 : points (texte|icône)'),
                self::textarea('sidefooter', 'Exemples pédagogiques — Colonne 4 : conclusion'),
                self::text('sidefooteraccent', 'Exemples pédagogiques — Colonne 4 : conclusion rose'),
                self::textarea('summarytext', 'Exemples pédagogiques — Bandeau final : texte'),
                self::text('summaryaccent', 'Exemples pédagogiques — Bandeau final : passage rose'),
                self::media('image1', 'Carte 1 — image', 'image'),
                self::media('image2', 'Carte 2 — image', 'image'),
                self::media('image3', 'Carte 3 — image', 'image'),
                self::media('image4', 'Carte 4 — image', 'image'),
                self::media('image5', 'Carte 5 — image', 'image'),
                self::media('image6', 'Carte 6 — image', 'image'),
            ]),
            'audience_profiles' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::text('titlesuffix', 'Fin du titre après la partie rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', '4 profils (titre|texte|icône)', true),
                self::media('image1', 'Profil 1 — image', 'image'),
                self::media('image2', 'Profil 2 — image', 'image'),
                self::media('image3', 'Profil 3 — image', 'image'),
                self::media('image4', 'Profil 4 — image', 'image'),
            ]),
            'heart_puzzle' => self::definition([
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Début du titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea(
                    'pieces',
                    '5 pièces (numéro|titre|texte|exemple|icône)',
                    true
                ),
                self::media('centerimageurl', 'Image au centre du cœur', 'image'),
                self::text('centerlabel', 'Texte au centre du cœur'),
                self::text('footertitle', 'Bloc final — titre'),
                self::textarea('footertext', 'Bloc final — texte'),
                self::text('footeraccent', 'Bloc final — passage rose'),
            ]),
            'before_after' => self::definition([
                self::select(
                    'presentation',
                    'Présentation',
                    [
                        'standard' => 'Standard',
                        'article_outcome' => 'Articles — dossier classé',
                    ],
                    'standard'
                ),
                self::text('eyebrow', 'Badge'),
                self::text('title', 'Début du titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::text('beforelabel', 'Colonne avant — badge'),
                self::text('beforetitle', 'Colonne avant — titre'),
                self::textarea('beforeitems', 'Colonne avant — points (titre|texte|icône)', true),
                self::textarea('beforenote', 'Colonne avant — note manuscrite'),
                self::text('afterlabel', 'Colonne après — badge'),
                self::text('aftertitle', 'Colonne après — titre'),
                self::textarea('afteritems', 'Colonne après — points (titre|texte|icône)', true),
                self::media('imageurl', 'Image centrale', 'image'),
                self::textarea('medianote', 'Image centrale — note manuscrite'),
                self::text('summarytitle', 'Conclusion — titre'),
                self::textarea('summarytext', 'Conclusion — texte'),
                self::text('summaryaccent', 'Conclusion — passage rose'),
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
            'offers' => self::definition(array_merge([
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'single_course' => 'Grande carte unique',
                    'alf_support' => 'Articles / Alf — offres solidaires',
                ]),
                self::text('badge', 'Badge de section'),
                self::text('badgeicon', 'Icône du badge', false, false),
                self::text('title', 'Début du titre'),
                self::text('titlehighlight', 'Partie du titre en rose'),
                self::text('titlesuffix', 'Fin du titre'),
                self::textarea('text', 'Sous-titre'),
                self::text('footertext', 'Bandeau final — texte'),
                self::text('footeraccent', 'Bandeau final — texte rose'),
            ], self::offer_role_fields($offerroles), [
                self::select(
                    'singleofferrole',
                    'Grande carte unique — rôle affiché',
                    self::offer_role_options($offerroles),
                    in_array('course', $offerroles, true) ? 'course' : (string)($offerroles[0] ?? '')
                ),
                self::textarea('skus', 'SKUs (un par ligne)', false, false, false),
                self::select(
                    'featuredrole',
                    'Offre mise en avant',
                    self::offer_role_options($offerroles),
                    in_array('bundle', $offerroles, true) ? 'bundle' : (string)($offerroles[0] ?? '')
                ),
                self::text('featuredsku', 'SKU mis en avant (option avancée)', false, false),
                self::text('order', 'Ordre des offres (rôles séparés par des virgules)', false, false)
                    + ['default' => self::default_offer_order($offerroles)],
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
            ])),
            'comparison' => self::definition([
                self::text('eyebrow', 'Sur-titre / badge'),
                self::text('title', 'Titre', true),
                self::textarea('text', 'Sous-titre'),
                self::text('featurelabel', 'Case en haut à gauche'),
                self::textarea(
                    'rows',
                    self::comparison_rows_label($offerroles),
                    true
                ),
                self::text('order', 'Ordre des offres (rôles séparés par des virgules)', false, false)
                    + ['default' => self::default_offer_order($offerroles)],
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
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'article_course' => 'Articles / Alf — FAQ enquête',
                    'a1_travel' => 'CampusFR A1 — briefing avant départ',
                ]),
                self::text('eyebrow', 'Sur-titre / badge'),
                self::text('title', 'Début du titre'),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::text('titlesuffix', 'Fin du titre après la partie rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'FAQ historique (question|réponse|icône)', true),
                self::textarea('entriesjson', 'FAQ A1 — questions et réponses multiligne (JSON)', false, false, true),
                self::checkbox('singleopen', 'Une seule réponse ouverte à la fois'),
                self::text('supporttitle', 'FAQ A1 — titre du contact'),
                self::textarea('supporttext', 'FAQ A1 — texte du contact'),
                self::text('supportbuttonlabel', 'FAQ A1 — bouton contact'),
                self::text('supportbuttontarget', 'FAQ A1 — lien contact / ancre', false, false),
                self::text('footernote', 'Note manuscrite finale'),
            ]),
            'support' => self::definition([
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'teacher_help' => 'CampusFR A1 — professeur / questions',
                ]),
                self::text('eyebrow', 'Sur-titre / badge'),
                self::text('note', 'Note manuscrite haute'),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Partie du titre en rose'),
                self::textarea('text', 'Sous-titre'),
                self::textarea('items', 'Cartes (titre|texte|icône)'),
                self::text('conversationtitle', 'Titre du bloc central'),
                self::textarea('messages', 'Conversation (auteur|message|tutor/student|heure)'),
                self::text('sidenote', 'Note manuscrite basse'),
                self::text('buttonlabel', 'Libellé du bouton principal'),
                self::url('telegramurl', 'Lien Telegram'),
                self::url('whatsappurl', 'Lien WhatsApp'),
            ]),
            'faq_contact' => self::definition([
                self::text('title', 'Titre', true),
                self::textarea('text', 'Texte explicatif'),
                self::text('buttonlabel', 'Bouton principal'),
                self::url('buttonurl', 'Lien du bouton principal (optionnel, support par défaut)'),
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
                self::select('presentation', 'Présentation', [
                    'standard' => 'Standard',
                    'article_heart' => 'Articles / Alf — dernier appel',
                ]),
                self::select('titlesize', 'Taille du titre', [
                    'auto' => 'Automatique',
                    'normal' => 'Normale',
                    'compact' => 'Compacte',
                ]),
                self::select('badgedisplay', 'Badge', [
                    'auto' => 'Automatique',
                    'show' => 'Afficher',
                    'hide' => 'Masquer',
                ]),
                self::select('textdisplay', 'Sous-titre', [
                    'auto' => 'Automatique',
                    'show' => 'Afficher',
                    'hide' => 'Masquer',
                ]),
                self::select('perksdisplay', 'Bandeau bas', [
                    'auto' => 'Automatique',
                    'show' => 'Afficher',
                    'hide' => 'Masquer',
                ]),
                self::text('eyebrow', 'Badge — contenu'),
                self::text('badgeicon', 'Icône du badge', false, false),
                self::text('title', 'Titre', true),
                self::text('titleaccent', 'Titre — partie accentuée'),
                self::textarea('text', 'Sous-titre'),
                self::select('handnotedisplay', 'Affichage note manuscrite', [
                    'auto' => 'Automatique',
                    'show' => 'Afficher',
                    'hide' => 'Masquer',
                ]),
                self::textarea('handnote', 'Note manuscrite'),
                self::text('buttonlabel', 'Libellé du bouton'),
                self::text('buttontarget', 'Cible du bouton', false, false),
                self::text('secondarylabel', 'Lien secondaire'),
                self::text('secondarytarget', 'Lien secondaire — cible', false, false),
                self::textarea('perkitems', 'Arguments bas de bloc (un par ligne : Titre | icône)'),
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

    public static function is_media_field(
        string $type,
        string $fieldname,
        ?array $offerroles = null
    ): bool {
        foreach (self::schema($type, $offerroles)['fields'] as $field) {
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
    public static function normalise(string $type, array $config, ?array $offerroles = null): array {
        $schema = self::schema($type, $offerroles);
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
                    : ($field['default'] ?? ''));
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
            $default = (string)($field['default'] ?? ($allowed[0] ?? ''));
            return in_array((string)$value, $allowed, true)
                ? (string)$value
                : (in_array($default, $allowed, true) ? $default : (string)($allowed[0] ?? ''));
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
                'dark2' => 'Sombre 2',
                'gradient' => 'Dégradé CampusFR',
                'custom' => 'Couleur personnalisée',
                'image' => 'Image personnalisée',
            ]),
            self::select('sectiontextcolor', 'Couleur du texte', [
                'auto' => 'Automatique selon le fond',
                'brand' => 'Bleu / violet / gris',
                'white' => 'Blanc',
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


    /** @param string[]|null $roles @return string[] */
    private static function normalise_offer_roles(?array $roles): array {
        if ($roles === null) {
            return ['pdf', 'bundle', 'course'];
        }

        $normalised = [];
        foreach ($roles as $rawrole) {
            $role = CommerceShowroomProductConfig::normalise_role((string)$rawrole);
            if ($role !== null) {
                $normalised[$role] = $role;
            }
        }

        return array_values($normalised);
    }

    /** @param string[] $roles @return array<int,array<string,mixed>> */
    private static function offer_role_fields(array $roles): array {
        $fields = [];
        foreach ($roles as $role) {
            $label = self::offer_role_label($role);
            $fields[] = self::text($role . 'rolelabel', $label . ' — Type commercial');
            $fields[] = self::media($role . 'imageurl', $label . ' — Image de la carte', 'image');
            $fields[] = self::text($role . 'title', $label . ' — Titre');
            $fields[] = self::text($role . 'subtitle', $label . ' — Sous-titre');
            $fields[] = self::textarea($role . 'description', $label . ' — Description');
            $fields[] = self::textarea(
                $role . 'features',
                $label . ' — Caractéristiques (une ligne par caractéristique)'
            );
            $fields[] = self::text($role . 'featuredlabel', $label . ' — Badge mise en avant');
            $fields[] = self::text($role . 'buylabel', $label . ' — Texte du bouton');
        }
        return $fields;
    }

    /** @param string[] $roles @return array<string,string> */
    private static function offer_role_options(array $roles): array {
        $options = [];
        foreach ($roles as $role) {
            $options[$role] = self::offer_role_label($role);
        }
        return $options;
    }

    private static function offer_role_label(string $role): string {
        return match ($role) {
            'pdf' => 'PDF',
            'course' => 'Cours',
            'course_fr' => 'A1 · accompagnement français',
            'course_ru' => 'A1 · accompagnement russe',
            'bundle' => 'Bundle',
            default => ucfirst(str_replace(['_', '-'], ' ', $role)),
        };
    }

    /** @param string[] $roles */
    private static function default_offer_order(array $roles): string {
        if (
            count($roles) === 3
            && count(array_intersect($roles, ['pdf', 'bundle', 'course'])) === 3
        ) {
            return 'pdf,bundle,course';
        }
        return implode(',', $roles);
    }

    /** @param string[] $roles */
    private static function comparison_rows_label(array $roles): string {
        $columns = array_merge(['critère'], $roles);
        return 'Lignes du comparatif (' . implode('|', $columns) . ')';
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
    private static function select(
        string $name,
        string $label,
        array $options,
        string $default = ''
    ): array {
        return compact('name', 'label', 'options', 'default')
            + ['type' => 'select', 'required' => false, 'translatable' => false];
    }
}
