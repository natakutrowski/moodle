<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail\template\studio;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\mail\CommerceMailType;

/**
 * Built-in editorial content used when no customised template is active.
 */
final class CommerceMailTemplateDefaults {

    /** @return array<string,mixed> */
    public static function get(string $mailtype, string $language): array {
        $language = in_array($language, ['fr', 'en', 'ru'], true) ? $language : 'fr';
        $all = self::all();

        if (!isset($all[$language][$mailtype])) {
            throw new \coding_exception('Unsupported default Commerce mail template: ' . $mailtype . ':' . $language);
        }

        return $all[$language][$mailtype] + [
            'enabled' => 1,
            'headerimage' => 0,
        ];
    }

    /** @return array<string,array<string,array<string,mixed>>> */
    private static function all(): array {
        return [
            'fr' => [
                CommerceMailType::PERSONAL_OFFER => [
                    'subject' => 'Une offre CampusFR réservée pour vous — {offer_product}',
                    'preheader' => 'Votre offre personnelle est prête. Découvrez-la avant le {offer_expiry}.',
                    'heading' => 'Une offre rien que pour vous',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Parce que vous faites déjà partie de l’aventure CampusFR, nous avons préparé pour vous une condition spéciale sur <strong>{offer_product}</strong>.</p>',
                    'outrohtml' => '<p>Cette offre est personnelle et liée à votre adresse e-mail. Le bouton ci-dessous vous permet d’accéder directement au tarif qui vous est réservé.</p>',
                    'signaturehtml' => '<p>À très vite,<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::ACCOUNT_ACTIVATION => [
                    'subject' => 'Bienvenue dans CampusFR — activez votre compte',
                    'preheader' => 'Votre achat est confirmé. Activez votre compte et découvrez votre espace CampusFR.',
                    'heading' => 'Bienvenue dans CampusFR ! 🎉',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Merci pour votre achat et bienvenue dans CampusFR ! Votre commande est confirmée et vos nouveaux contenus vous attendent déjà.</p><p>Pour les retrouver facilement aujourd’hui et lors de vos prochaines visites, il ne vous reste qu’à finaliser l’activation de votre compte.</p>',
                    'outrohtml' => '<p>Une question ? Notre équipe est disponible à <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p><p>Nous vous souhaitons beaucoup de plaisir à chaque petite victoire, de belles découvertes dans vos cours et de beaux progrès en français ❤️</p>',
                    'signaturehtml' => '<p>À très vite sur CampusFR !<br><strong>Nata et l’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::TRIAL_WELCOME => [
                    'subject' => 'Bienvenue dans CampusFR — votre essai commence 🎉',
                    'preheader' => 'Votre premier cours gratuit est déjà disponible.',
                    'heading' => 'Votre essai CampusFR commence !',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Bienvenue dans CampusFR ! Votre compte a bien été créé et votre période d’essai vient de commencer.</p>',
                    'outrohtml' => '<p>Nous avons déjà ouvert votre premier cours gratuit. Découvrez-le dès aujourd’hui et faites un premier pas vers vos objectifs !</p><p><strong>Dans CampusFR, vous trouverez :</strong></p><ul><li>des vidéos de grammaire claires et faciles à comprendre ;</li><li>de la pratique avec un vrai Français ;</li><li>des exercices avec correction instantanée.</li></ul><p>Le français ne semble compliqué qu’au premier abord. Avec les cours de Nata Kutrowski, vous en comprendrez rapidement la logique et commencerez à prendre plaisir à apprendre.</p><p>Consacrez seulement <strong>20 minutes par jour</strong> à votre apprentissage et les progrès ne se feront pas attendre.</p><p>Une question ? Notre équipe est disponible à <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>À très vite dans nos cours !<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::PURCHASE_ACCESS => [
                    'subject' => 'Vos accès CampusFR sont disponibles — {order_reference}',
                    'preheader' => 'Vos cours et ressources sont prêts dans votre espace CampusFR.',
                    'heading' => 'Tout est prêt !',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Bonne nouvelle : votre achat est confirmé et vos accès sont maintenant disponibles. Vous pouvez commencer dès maintenant, à votre rythme.</p>',
                    'outrohtml' => '<p>Gardez cet e-mail : il vous permettra de retrouver facilement vos accès. Toutes vos ressources restent également disponibles dans votre espace CampusFR.</p>',
                    'signaturehtml' => '<p>Nous vous souhaitons un très bel apprentissage !<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::GRANT_ACCESS => [
                    'subject' => 'Un nouvel accès CampusFR est disponible',
                    'preheader' => 'Un nouveau cours ou une nouvelle ressource vient d’être ajouté à votre espace CampusFR.',
                    'heading' => 'Un nouvel accès vous attend !',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Bonne nouvelle : un nouvel accès vient d’être ajouté à votre espace CampusFR. Vous pouvez en profiter dès maintenant.</p>',
                    'outrohtml' => '<p>Gardez cet e-mail pour retrouver facilement vos accès. Vos contenus restent également disponibles à tout moment dans votre espace CampusFR.</p>',
                    'signaturehtml' => '<p>Nous vous souhaitons un très bel apprentissage !<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::PURCHASE_RECEIPT => [
                    'subject' => 'Confirmation de votre achat CampusFR — {order_reference}',
                    'preheader' => 'Votre commande est confirmée. Retrouvez ici son récapitulatif.',
                    'heading' => 'Merci pour votre confiance !',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Nous sommes ravis de confirmer votre achat. Vous trouverez ci-dessous le récapitulatif de votre commande.</p>',
                    'outrohtml' => '<p>Votre facture est jointe à cet e-mail au format PDF. Votre commande reste également accessible à tout moment depuis votre espace CampusFR.</p>',
                    'signaturehtml' => '<p>Merci de faire partie de l’aventure CampusFR.<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_PENDING => [
                    'subject' => 'Votre paiement CampusFR est en cours — {order_reference}',
                    'preheader' => 'Votre paiement est en cours de validation. Nous vous préviendrons dès sa confirmation.',
                    'heading' => 'Votre paiement est en cours de validation',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Votre commande a bien été enregistrée. Le prestataire de paiement finalise encore la validation de l’opération.</p>',
                    'outrohtml' => '<p>Vous n’avez rien à faire pour le moment. Dès que le paiement sera confirmé, vos accès seront activés automatiquement.</p>',
                    'signaturehtml' => '<p>À très vite,<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_FAILED => [
                    'subject' => 'Votre paiement CampusFR n’a pas abouti — {order_reference}',
                    'preheader' => 'Votre paiement n’a pas été confirmé. Vous pouvez réessayer depuis votre espace CampusFR.',
                    'heading' => 'Le paiement n’a pas pu être confirmé',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Nous n’avons pas pu confirmer votre paiement. Aucun accès supplémentaire n’a été activé et aucun achat n’est considéré comme finalisé.</p>',
                    'outrohtml' => '<p>Vous pouvez réessayer depuis votre espace CampusFR ou choisir un autre moyen de paiement. En cas de doute, notre équipe est là pour vous aider à l’adresse <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>À bientôt,<br><strong>L’équipe CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_CANCELLED => [
                    'subject' => 'Votre paiement CampusFR a été annulé — {order_reference}',
                    'preheader' => 'Votre commande est conservée, mais aucun paiement n’a été confirmé.',
                    'heading' => 'Paiement annulé',
                    'introhtml' => '<p>Bonjour {firstname},</p><p>Le paiement a été annulé avant sa confirmation. Aucun montant n’a été validé par CampusFR.</p>',
                    'outrohtml' => '<p>Votre sélection reste disponible si vous souhaitez reprendre votre achat plus tard. Besoin d’aide ? Écrivez-nous à <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>À bientôt,<br><strong>L’équipe CampusFR</strong></p>',
                ],
            ],
            'en' => [
                CommerceMailType::PERSONAL_OFFER => [
                    'subject' => 'A CampusFR offer reserved for you — {offer_product}',
                    'preheader' => 'Your personal offer is ready. Discover it before {offer_expiry}.',
                    'heading' => 'An offer just for you',
                    'introhtml' => '<p>Hello {firstname},</p><p>Because you are already part of the CampusFR adventure, we have prepared a special condition for you on <strong>{offer_product}</strong>.</p>',
                    'outrohtml' => '<p>This offer is personal and linked to your email address. Use the button below to access the price reserved for you.</p>',
                    'signaturehtml' => '<p>See you soon,<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::ACCOUNT_ACTIVATION => [
                    'subject' => 'Welcome to CampusFR — activate your account',
                    'preheader' => 'Your purchase is confirmed. Activate your account and discover your CampusFR space.',
                    'heading' => 'Welcome to CampusFR! 🎉',
                    'introhtml' => '<p>Hello {firstname},</p><p>Thank you for your purchase and welcome to CampusFR! Your order is confirmed and your new content is already waiting for you.</p><p>To find everything easily today and on your future visits, all that remains is to complete the activation of your account.</p>',
                    'outrohtml' => '<p>Any questions? Our team is available at <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p><p>We wish you lots of satisfaction with every small achievement, wonderful discoveries in your lessons and steady progress in French ❤️</p>',
                    'signaturehtml' => '<p>See you soon on CampusFR!<br><strong>Nata and the CampusFR team</strong></p>',
                ],
                CommerceMailType::TRIAL_WELCOME => [
                    'subject' => 'Welcome to CampusFR — your trial starts now 🎉',
                    'preheader' => 'Your first free lesson is already available.',
                    'heading' => 'Your CampusFR trial starts now!',
                    'introhtml' => '<p>Hello {firstname},</p><p>Welcome to CampusFR! Your account has been successfully created and your trial has just started.</p>',
                    'outrohtml' => '<p>We have already opened your first free lesson. Start it today and take the first step towards your goals!</p><p><strong>On CampusFR, you will find:</strong></p><ul><li>clear, easy-to-understand grammar video lessons;</li><li>practice with a real French person;</li><li>exercises with instant feedback.</li></ul><p>French only seems difficult at first. With Nata Kutrowski’s lessons, you will quickly understand how it works and start enjoying the learning process.</p><p>Spend just <strong>20 minutes a day</strong> learning and you will soon see progress.</p><p>Any questions? Our team is available at <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>See you in class soon!<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::PURCHASE_ACCESS => [
                    'subject' => 'Your CampusFR access is ready — {order_reference}',
                    'preheader' => 'Your courses and digital resources are now ready in your CampusFR account.',
                    'heading' => 'Everything is ready!',
                    'introhtml' => '<p>Hello {firstname},</p><p>Great news: your purchase is confirmed and your access is now available. You can start learning right away, at your own pace.</p>',
                    'outrohtml' => '<p>Keep this email so you can easily find your access links. All your resources also remain available in your CampusFR account.</p>',
                    'signaturehtml' => '<p>We wish you a wonderful learning experience!<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::GRANT_ACCESS => [
                    'subject' => 'New CampusFR access is now available',
                    'preheader' => 'A new course or resource has just been added to your CampusFR account.',
                    'heading' => 'New access is waiting for you!',
                    'introhtml' => '<p>Hello {firstname},</p><p>Great news: new access has just been added to your CampusFR account. You can start using it right away.</p>',
                    'outrohtml' => '<p>Keep this email so you can easily find your access links. Your content also remains available at any time in your CampusFR account.</p>',
                    'signaturehtml' => '<p>We wish you a wonderful learning experience!<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::PURCHASE_RECEIPT => [
                    'subject' => 'Your CampusFR purchase confirmation — {order_reference}',
                    'preheader' => 'Your order is confirmed. Find its full summary here.',
                    'heading' => 'Thank you for your trust!',
                    'introhtml' => '<p>Hello {firstname},</p><p>We are delighted to confirm your purchase. You will find your order summary below.</p>',
                    'outrohtml' => '<p>Your invoice is attached to this email as a PDF. Your order also remains available at any time from your CampusFR account.</p>',
                    'signaturehtml' => '<p>Thank you for being part of the CampusFR adventure.<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::PAYMENT_PENDING => [
                    'subject' => 'Your CampusFR payment is being processed — {order_reference}',
                    'preheader' => 'Your payment is being validated. We will notify you as soon as it is confirmed.',
                    'heading' => 'Your payment is being validated',
                    'introhtml' => '<p>Hello {firstname},</p><p>Your order has been recorded. The payment provider is still completing the transaction validation.</p>',
                    'outrohtml' => '<p>You do not need to do anything for now. As soon as payment is confirmed, your access will be activated automatically.</p>',
                    'signaturehtml' => '<p>See you soon,<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::PAYMENT_FAILED => [
                    'subject' => 'Your CampusFR payment was unsuccessful — {order_reference}',
                    'preheader' => 'Your payment was not confirmed. You can try again from your CampusFR account.',
                    'heading' => 'We could not confirm the payment',
                    'introhtml' => '<p>Hello {firstname},</p><p>We could not confirm your payment. No additional access has been activated and the purchase has not been finalised.</p>',
                    'outrohtml' => '<p>You can try again from your CampusFR account or choose another payment method. If you need help, contact us at <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>See you soon,<br><strong>The CampusFR team</strong></p>',
                ],
                CommerceMailType::PAYMENT_CANCELLED => [
                    'subject' => 'Your CampusFR payment was cancelled — {order_reference}',
                    'preheader' => 'Your order has been saved, but no payment was confirmed.',
                    'heading' => 'Payment cancelled',
                    'introhtml' => '<p>Hello {firstname},</p><p>The payment was cancelled before confirmation. No amount has been confirmed by CampusFR.</p>',
                    'outrohtml' => '<p>Your selection remains available if you wish to resume your purchase later. Need help? Contact us at <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>See you soon,<br><strong>The CampusFR team</strong></p>',
                ],
            ],
            'ru' => [
                CommerceMailType::PERSONAL_OFFER => [
                    'subject' => 'Персональное предложение CampusFR — {offer_product}',
                    'preheader' => 'Ваше персональное предложение готово. Воспользуйтесь им до {offer_expiry}.',
                    'heading' => 'Предложение специально для вас',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Вы уже часть CampusFR, поэтому мы подготовили для вас специальные условия на <strong>{offer_product}</strong>.</p>',
                    'outrohtml' => '<p>Это предложение персональное и связано с вашим адресом электронной почты. Нажмите кнопку ниже, чтобы воспользоваться специальной ценой.</p>',
                    'signaturehtml' => '<p>До скорой встречи,<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::ACCOUNT_ACTIVATION => [
                    'subject' => 'Добро пожаловать в CampusFR — активируйте аккаунт',
                    'preheader' => 'Покупка подтверждена. Активируйте аккаунт и откройте свой CampusFR.',
                    'heading' => 'Добро пожаловать в CampusFR! 🎉',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Спасибо за покупку и добро пожаловать в CampusFR! Ваш заказ подтверждён, а новые материалы уже ждут вас.</p><p>Чтобы легко находить их сегодня и при следующих посещениях, осталось только завершить активацию вашего аккаунта.</p>',
                    'outrohtml' => '<p>Есть вопрос? Наша команда всегда на связи: <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p><p>Желаем вам радости от каждого маленького результата, интересных занятий и уверенного прогресса во французском ❤️</p>',
                    'signaturehtml' => '<p>До встречи в CampusFR!<br><strong>Ната и команда CampusFR</strong></p>',
                ],
                CommerceMailType::TRIAL_WELCOME => [
                    'subject' => 'Добро пожаловать в CampusFR — ваш пробный период начался 🎉',
                    'preheader' => 'Ваш первый бесплатный урок уже открыт.',
                    'heading' => 'Ваш пробный период CampusFR начался!',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Добро пожаловать в школу французского языка CampusFR — ваш аккаунт успешно создан, и пробный период уже начался.</p>',
                    'outrohtml' => '<p>Мы уже открыли первый бесплатный урок, пройдите его сегодня и сделайте шаг навстречу своим целям!</p><p><strong>В CampusFR вас ждут:</strong></p><ul><li>понятные видеоуроки по грамматике;</li><li>практика с настоящим французом;</li><li>задания с мгновенной проверкой.</li></ul><p>Французский язык кажется сложным только на первый взгляд. На уроках Наты Кутровски вы быстро поймёте его логику и начнёте получать удовольствие от обучения.</p><p>Уделяйте занятиям всего по <strong>20 минут в день</strong>, и прогресс не заставит себя ждать.</p><p>Есть вопросы? Наша команда всегда на связи: <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>До встречи на уроках!<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::PURCHASE_ACCESS => [
                    'subject' => 'Ваши материалы CampusFR доступны — {order_reference}',
                    'preheader' => 'Курсы и цифровые материалы уже доступны в вашем аккаунте CampusFR.',
                    'heading' => 'Всё готово!',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Отличная новость: покупка подтверждена, и все доступы уже активированы. Можно начинать заниматься прямо сейчас в удобном темпе.</p>',
                    'outrohtml' => '<p>Сохраните это письмо, чтобы быстро находить ссылки на материалы. Все ресурсы также всегда доступны в вашем аккаунте CampusFR.</p>',
                    'signaturehtml' => '<p>Желаем приятного и успешного обучения!<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::GRANT_ACCESS => [
                    'subject' => 'В CampusFR вам открыт новый доступ',
                    'preheader' => 'Новый курс или материал уже добавлен в ваш аккаунт CampusFR.',
                    'heading' => 'Вам открыт новый доступ!',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Отличная новость: в вашем пространстве CampusFR появился новый доступ. Вы можете воспользоваться им прямо сейчас.</p>',
                    'outrohtml' => '<p>Сохраните это письмо, чтобы быстро находить ссылки на материалы. Все ваши доступы также остаются доступны в аккаунте CampusFR.</p>',
                    'signaturehtml' => '<p>Желаем приятного и успешного обучения!<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::PURCHASE_RECEIPT => [
                    'subject' => 'Подтверждение покупки CampusFR — {order_reference}',
                    'preheader' => 'Ваш заказ подтверждён. Ниже вы найдёте его подробное описание.',
                    'heading' => 'Спасибо за ваше доверие!',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Мы рады подтвердить вашу покупку. Ниже находится подробная информация о заказе.</p>',
                    'outrohtml' => '<p>Счёт в формате PDF прикреплён к этому письму. Заказ также в любой момент доступен в вашем аккаунте CampusFR.</p>',
                    'signaturehtml' => '<p>Спасибо, что вы с CampusFR.<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_PENDING => [
                    'subject' => 'Платёж CampusFR обрабатывается — {order_reference}',
                    'preheader' => 'Платёж проходит проверку. Мы сообщим вам сразу после подтверждения.',
                    'heading' => 'Платёж проходит проверку',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Заказ успешно зарегистрирован. Платёжный сервис пока завершает проверку операции.</p>',
                    'outrohtml' => '<p>Сейчас ничего делать не нужно. После подтверждения оплаты доступы активируются автоматически.</p>',
                    'signaturehtml' => '<p>До скорой встречи,<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_FAILED => [
                    'subject' => 'Платёж CampusFR не прошёл — {order_reference}',
                    'preheader' => 'Платёж не подтверждён. Вы можете повторить попытку в аккаунте CampusFR.',
                    'heading' => 'Не удалось подтвердить платёж',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Нам не удалось подтвердить платёж. Дополнительные доступы не активированы, а покупка не считается завершённой.</p>',
                    'outrohtml' => '<p>Попробуйте оплатить ещё раз в аккаунте CampusFR или выберите другой способ. Если нужна помощь, напишите нам: <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>До скорой встречи,<br><strong>Команда CampusFR</strong></p>',
                ],
                CommerceMailType::PAYMENT_CANCELLED => [
                    'subject' => 'Платёж CampusFR отменён — {order_reference}',
                    'preheader' => 'Заказ сохранён, но оплата не была подтверждена.',
                    'heading' => 'Платёж отменён',
                    'introhtml' => '<p>Здравствуйте, {firstname}!</p><p>Платёж был отменён до подтверждения. CampusFR не получил подтверждение списания средств.</p>',
                    'outrohtml' => '<p>Выбранные товары останутся доступны, если вы решите вернуться к покупке позже. Нужна помощь? Напишите нам: <a href="mailto:{support_email}" style="color:#f72585;text-decoration:none;font-weight:700;">{support_email}</a>.</p>',
                    'signaturehtml' => '<p>До скорой встречи,<br><strong>Команда CampusFR</strong></p>',
                ],
            ],
        ];
    }

    private function __construct() {
    }
}
