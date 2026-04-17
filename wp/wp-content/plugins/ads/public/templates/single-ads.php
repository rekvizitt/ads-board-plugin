<?php
/**
 * Template: Single Ad Page
 * URL: /board/ad/{slug}/
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

if (!class_exists("Ads_Settings")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-settings.php";
}

// Получаем данные
$ad = get_query_var("ads_ad_data");
$gallery = get_query_var("ads_ad_gallery", []);
$related = get_query_var("ads_ad_related", []);
$is_unavailable = get_query_var("ads_unavailable", false);
$unavailable_reason = get_query_var("ads_unavailable_reason", "");

// Настройки
$date_format = function_exists("ads_get_setting")
    ? ads_get_setting("date_format", "relative")
    : "relative";
$show_author = function_exists("ads_get_setting")
    ? ads_get_setting("show_author", 1)
    : 1;

// Форматирование даты
function format_ad_date($datetime, $format)
{
    if (!$datetime) {
        return "";
    }
    if ($format === "relative") {
        return human_time_diff(
            strtotime($datetime),
            current_time("timestamp"),
        ) . " назад";
    }
    return date_i18n($format, strtotime($datetime));
}

// Форматирование телефона: убираем дублирование плюса
function format_phone_number($phone)
{
    if (empty($phone)) {
        return "";
    }

    // Извлекаем только цифры
    $digits = preg_replace("/[^\d]/", "", $phone);

    // Форматируем под +375 (XX) XXX-XX-XX
    if (strlen($digits) === 12) {
        // Заменяем 8 в начале на 375
        if (substr($digits, 0, 1) === "8") {
            $digits = "375" . substr($digits, 1);
        }

        if (substr($digits, 0, 3) === "375") {
            return "+375 (" .
                substr($digits, 3, 2) .
                ") " .
                substr($digits, 5, 3) .
                "-" .
                substr($digits, 8, 2) .
                "-" .
                substr($digits, 10, 2);
        }
    }

    // Фолбэк: возвращаем как есть, но с одним плюсом в начале если нужно
    $clean = preg_replace("/[^\d+]/", "", $phone);
    if (strpos($clean, "+") !== 0 && !empty($clean)) {
        $clean = "+" . $clean;
    }
    return $clean;
}

// Генерация tel: ссылки (только цифры с одним плюсом)
function get_tel_link($phone)
{
    if (empty($phone)) {
        return "";
    }
    $digits = preg_replace("/[^\d]/", "", $phone);
    return "tel:+" . $digits;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo("charset"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(
        $ad ? $ad->title . " — Доска объявлений" : "Объявление",
    ); ?></title>
    <?php if ($ad && $ad->description): ?>
        <meta name="description" content="<?php echo esc_attr(
            wp_trim_words(wp_strip_all_tags($ad->description), 50),
        ); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>

    <!-- Schema.org разметка -->
    <?php if ($ad && !$is_unavailable): ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Product",
            "name": <?php echo json_encode($ad->title); ?>,
            "description": <?php echo json_encode(
                wp_trim_words(wp_strip_all_tags($ad->description), 100),
            ); ?>,
            <?php if ($ad->price): ?>
            "offers": {
                "@type": "Offer",
                "price": <?php echo (float) $ad->price; ?>,
                "priceCurrency": "USD",
                "availability": "<?php echo $ad->status === "sold"
                    ? "https://schema.org/SoldOut"
                    : "https://schema.org/InStock"; ?>"
            },
            <?php endif; ?>
            "image": <?php echo json_encode(
                !empty($gallery) ? home_url($gallery[0]->file_path) : "",
            ); ?>,
            "datePosted": "<?php echo date("c", strtotime($ad->created_at)); ?>"
        }
        </script>
    <?php endif; ?>
</head>
<body <?php body_class("ads-board-page ads-single-page"); ?>>

<div class="ads-board-container">

    <!-- Breadcrumbs -->
    <nav class="ads-breadcrumbs" aria-label="Навигация">
        <a href="<?php echo esc_url(home_url("/")); ?>">Главная</a>
        <span class="separator">/</span>
        <a href="<?php echo esc_url(home_url("/board/")); ?>">Объявления</a>
        <?php if ($ad && $ad->category_slug): ?>
            <span class="separator">/</span>
            <a href="<?php echo esc_url(
                home_url("/board/category/" . $ad->category_slug . "/"),
            ); ?>">
                <?php echo esc_html($ad->category_name ?? "Категория"); ?>
            </a>
        <?php endif; ?>
        <span class="separator">/</span>
        <span class="current"><?php echo $ad
            ? esc_html($ad->title)
            : "Объявление"; ?></span>
    </nav>

    <?php if ($is_unavailable): ?>
        <!-- Сообщение о недоступности -->
        <div class="ads-unavailable">
            <h1>
                <?php if ($unavailable_reason === "expired") {
                    echo "Срок объявления истёк";
                } elseif ($unavailable_reason === "scheduled") {
                    echo "Объявление ещё не опубликовано";
                } elseif ($unavailable_reason === "draft") {
                    echo "Объявление снято с публикации";
                } else {
                    echo "Объявление недоступно";
                } ?>
            </h1>
            <p><?php echo esc_html($ad->title); ?></p>
            <a href="<?php echo esc_url(
                home_url("/board/"),
            ); ?>" class="button">Вернуться к списку</a>
        </div>
    <?php elseif ($ad): ?>
        <!-- Основной контент -->
        <div class="ad-single">

            <!-- Левая колонка: галерея и описание -->
            <div class="ad-main">

                <!-- Галерея -->
                <?php if (!empty($gallery)): ?>
                    <div class="ad-gallery">
                        <div class="ad-gallery-main">
                            <img id="ad-gallery-main" src="<?php echo esc_url(
                                home_url($gallery[0]->file_path),
                            ); ?>" alt="<?php echo esc_attr($ad->title); ?>">
                            <?php if (count($gallery) > 1): ?>
                                <span class="ad-gallery-counter">1 / <?php echo count(
                                    $gallery,
                                ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (count($gallery) > 1): ?>
                            <div class="ad-gallery-nav">
                                <?php foreach ($gallery as $i => $img): ?>
                                    <img src="<?php echo esc_url(
                                        home_url($img->file_path),
                                    ); ?>"
                                         alt="<?php echo esc_attr(
                                             $img->file_name,
                                         ); ?>"
                                         class="ad-gallery-thumb <?php echo $i ===
                                         0
                                             ? "active"
                                             : ""; ?>"
                                         data-index="<?php echo $i; ?>"
                                         data-full="<?php echo esc_url(
                                             home_url($img->file_path),
                                         ); ?>">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="ad-gallery-placeholder">Нет изображений</div>
                <?php endif; ?>

                <!-- Информация -->
                <div class="ad-info">
                    <h1 class="ad-title">
                        <?php echo esc_html($ad->title); ?>
                        <?php if ($ad->is_pinned): ?>
                            <span class="badge badge-pinned">Закреплено</span>
                        <?php endif; ?>
                        <?php if ($ad->is_important): ?>
                            <span class="badge badge-important">Важное</span>
                        <?php endif; ?>
                        <?php if ($ad->status === "sold"): ?>
                            <span class="badge badge-sold">Продано</span>
                        <?php endif; ?>
                    </h1>

                    <div class="ad-meta">
                        <span><?php echo format_ad_date(
                            $ad->created_at,
                            $date_format,
                        ); ?></span>
                        <?php if ($ad->category_name): ?>
                            <span><a href="<?php echo esc_url(
                                home_url(
                                    "/board/category/" .
                                        $ad->category_slug .
                                        "/",
                                ),
                            ); ?>"><?php echo esc_html(
    $ad->category_name,
); ?></a></span>
                        <?php endif; ?>
                        <span><?php echo number_format_i18n(
                            $ad->views_count,
                        ); ?> просмотров</span>
                        <?php if ($ad->expires_at): ?>
                            <span>До <?php echo date_i18n(
                                "d.m.Y",
                                strtotime($ad->expires_at),
                            ); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($ad->price): ?>
                        <p class="ad-price">$<?php echo number_format_i18n(
                            $ad->price,
                            2,
                        ); ?></p>
                    <?php endif; ?>

                    <div class="ad-description">
                        <?php echo wp_kses_post(wpautop($ad->description)); ?>
                    </div>
                </div>

                <!-- Похожие объявления -->
                <?php if (!empty($related)): ?>
                    <div class="ad-related">
                        <h3>Похожие объявления</h3>
                        <div class="ad-related-grid">
                            <?php foreach ($related as $item): ?>
                                <a href="<?php echo esc_url(
                                    home_url("/board/ad/" . $item->slug . "/"),
                                ); ?>" class="ad-related-card">
                                    <?php if ($item->primary_image): ?>
                                        <img src="<?php echo esc_url(
                                            home_url($item->primary_image),
                                        ); ?>" alt="<?php echo esc_attr(
    $item->title,
); ?>">
                                    <?php else: ?>
                                        <div class="ad-related-no-image">Нет фото</div>
                                    <?php endif; ?>
                                    <div class="content">
                                        <p class="title"><?php echo esc_html(
                                            $item->title,
                                        ); ?></p>
                                        <?php if ($item->price): ?>
                                            <p class="price">$<?php echo number_format_i18n(
                                                $item->price,
                                                2,
                                            ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Правая колонка: автор и контакты -->
            <aside class="ad-sidebar">
                <div class="ad-author">
                    <h3>Контакты автора</h3>

                    <?php if ($show_author): ?>
                        <div class="ad-author-info">
                            <p><strong>Имя:</strong> <?php echo esc_html(
                                $ad->author_name,
                            ); ?></p>

                            <?php if (!empty($ad->author_phone)): ?>
                                <p><strong>Телефон:</strong>
                                    <a href="<?php echo esc_url(
                                        get_tel_link($ad->author_phone),
                                    ); ?>">
                                        <?php echo esc_html(
                                            format_phone_number(
                                                $ad->author_phone,
                                            ),
                                        ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <?php if ($ad->author_email): ?>
                                <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr(
                                    $ad->author_email,
                                ); ?>"><?php echo esc_html(
    $ad->author_email,
); ?></a></p>
                            <?php endif; ?>

                            <p class="text-muted">Размещено: <?php echo date_i18n(
                                "d.m.Y H:i",
                                strtotime($ad->created_at),
                            ); ?></p>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Контакты автора скрыты настройками сайта.</p>
                    <?php endif; ?>

                    <!-- Поделиться -->
                    <div class="ad-share">
                        <p>Поделиться:</p>
                        <div class="ad-share-links">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(
                                get_permalink(),
                            ); ?>"
                               target="_blank" rel="noopener" class="ad-share-link" title="Facebook" aria-label="Поделиться в Facebook">f</a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(
                                get_permalink(),
                            ); ?>&text=<?php echo urlencode($ad->title); ?>"
                               target="_blank" rel="noopener" class="ad-share-link" title="Twitter" aria-label="Поделиться в Twitter">X</a>
                            <a href="https://t.me/share/url?url=<?php echo urlencode(
                                get_permalink(),
                            ); ?>&text=<?php echo urlencode($ad->title); ?>"
                               target="_blank" rel="noopener" class="ad-share-link" title="Telegram" aria-label="Поделиться в Telegram">✈</a>
                            <a href="mailto:?subject=<?php echo urlencode(
                                $ad->title,
                            ); ?>&body=<?php echo urlencode(
    get_permalink(),
); ?>"
                               class="ad-share-link" title="Email" aria-label="Отправить по email">@</a>
                        </div>
                    </div>
                </div>
            </aside>

        </div>
    <?php endif; ?>

</div>

<!-- Скрипты для галереи -->
<?php if (
    $ad &&
    !$is_unavailable &&
    !empty($gallery) &&
    count($gallery) > 1
): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainImg = document.getElementById('ad-gallery-main');
    const thumbs = document.querySelectorAll('.ad-gallery-thumb');
    const counter = document.querySelector('.ad-gallery-counter');

    if (mainImg && thumbs.length > 0) {
        thumbs.forEach(function(thumb) {
            thumb.addEventListener('click', function() {
                mainImg.src = this.dataset.full;
                mainImg.alt = this.alt;

                thumbs.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');

                if (counter) {
                    counter.textContent = (parseInt(this.dataset.index) + 1) + ' / ' + thumbs.length;
                }
            });
        });

        let currentIndex = 0;
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                thumbs[currentIndex - 1].click();
                currentIndex--;
            } else if (e.key === 'ArrowRight' && currentIndex < thumbs.length - 1) {
                thumbs[currentIndex + 1].click();
                currentIndex++;
            }
        });
    }
});
</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
