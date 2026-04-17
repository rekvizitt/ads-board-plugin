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

    <style>
        /* === Base === */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 0; background: #f6f7f7; color: #23282d; line-height: 1.5; }
        a { color: #007cba; text-decoration: none; }
        a:hover { color: #005a87; }
        img { max-width: 100%; height: auto; display: block; }

        /* === Container === */
        .ads-board-container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        /* === Breadcrumbs === */
        .ads-breadcrumbs { margin: 0 0 20px; font-size: 14px; color: #646970; }
        .ads-breadcrumbs .separator { margin: 0 5px; color: #999; }
        .ads-breadcrumbs .current { color: #23282d; font-weight: 500; }

        /* === Unavailable Notice === */
        .ads-unavailable { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 30px; text-align: center; margin: 40px 0; }
        .ads-unavailable h1 { margin: 0 0 15px; font-size: 22px; }
        .ads-unavailable p { color: #646970; margin: 0 0 20px; }
        .ads-unavailable .button { background: #007cba; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }

        /* === Ad Layout === */
        .ad-single { display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
        @media (max-width: 900px) { .ad-single { grid-template-columns: 1fr; } }

        /* === Gallery === */
        .ad-gallery { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
        .ad-gallery-main { position: relative; aspect-ratio: 4/3; background: #f0f0f1; }
        .ad-gallery-main img { width: 100%; height: 100%; object-fit: contain; }
        .ad-gallery-nav { display: flex; gap: 5px; padding: 10px; background: #fff; overflow-x: auto; }
        .ad-gallery-thumb { width: 60px; height: 60px; border: 2px solid transparent; border-radius: 4px; cursor: pointer; object-fit: cover; flex-shrink: 0; }
        .ad-gallery-thumb.active, .ad-gallery-thumb:hover { border-color: #007cba; }
        .ad-gallery-counter { position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; }

        /* === Ad Info === */
        .ad-info { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .ad-title { margin: 0 0 10px; font-size: 24px; }
        .ad-meta { display: flex; gap: 15px; color: #646970; font-size: 14px; margin-bottom: 15px; flex-wrap: wrap; }
        .ad-meta span { display: flex; align-items: center; gap: 5px; }
        .ad-price { font-size: 28px; font-weight: 600; color: #2e7d32; margin: 15px 0; }
        .ad-description { color: #23282d; line-height: 1.6; }
        .ad-description p { margin: 0 0 15px; }
        .ad-description img { margin: 10px 0; border-radius: 4px; }

        /* === Author Card === */
        .ad-author { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 20px; position: sticky; top: 20px; }
        .ad-author h3 { margin: 0 0 15px; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .ad-author-info { margin-bottom: 15px; }
        .ad-author-info p { margin: 8px 0; color: #646970; }
        .ad-author-info strong { color: #23282d; }
        .ad-contact-btn { display: block; width: 100%; padding: 12px; background: #007cba; color: #fff; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; text-align: center; margin-bottom: 10px; }
        .ad-contact-btn:hover { background: #005a87; }
        .ad-contact-hidden { display: none; background: #f0f0f1; color: #23282d; font-weight: 500; }
        .ad-share { margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; }
        .ad-share p { margin: 0 0 10px; font-size: 14px; color: #646970; }
        .ad-share-links { display: flex; gap: 10px; }
        .ad-share-link { width: 36px; height: 36px; border-radius: 50%; background: #f0f0f1; display: flex; align-items: center; justify-content: center; color: #23282d; font-size: 16px; transition: background 0.2s; }
        .ad-share-link:hover { background: #007cba; color: #fff; }

        /* === Related Ads === */
        .ad-related { margin-top: 40px; }
        .ad-related h3 { margin: 0 0 15px; font-size: 20px; }
        .ad-related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; }
        .ad-related-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
        .ad-related-card img { width: 100%; height: 150px; object-fit: cover; }
        .ad-related-card .content { padding: 12px; }
        .ad-related-card .title { font-size: 14px; font-weight: 500; margin: 0 0 5px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ad-related-card .price { color: #2e7d32; font-weight: 600; font-size: 16px; }

        /* === Utilities === */
        .text-muted { color: #646970; font-size: 13px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .badge-pinned { background: #007cba; color: #fff; }
        .badge-important { background: #f0ad4e; color: #fff; }
        .badge-sold { background: #dc3232; color: #fff; }
    </style>
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
                    <div class="ad-gallery" style="background: #f0f0f1; aspect-ratio: 4/3; display: flex; align-items: center; justify-content: center; color: #999; border-radius: 8px;">
                        Нет изображений
                    </div>
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
                        <span>📅 <?php echo format_ad_date(
                            $ad->created_at,
                            $date_format,
                        ); ?></span>
                        <?php if ($ad->category_name): ?>
                            <span>📁 <a href="<?php echo esc_url(
                                home_url(
                                    "/board/category/" .
                                        $ad->category_slug .
                                        "/",
                                ),
                            ); ?>"><?php echo esc_html(
    $ad->category_name,
); ?></a></span>
                        <?php endif; ?>
                        <span>👁 <?php echo number_format_i18n(
                            $ad->views_count,
                        ); ?> просмотров</span>
                        <?php if ($ad->expires_at): ?>
                            <span>⏰ До <?php echo date_i18n(
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
                                        <div style="height: 150px; background: #f0f0f1; display: flex; align-items: center; justify-content: center; color: #999;">Нет фото</div>
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
                            <?php if ($ad->author_phone): ?>
                                <p><strong>Телефон:</strong>
                                    <span id="ad-phone-display"><?php echo esc_html(
                                        preg_replace(
                                            "/(\d{3})(\d{2})(\d{2})(\d{2})/",
                                            '+$1 ($2) $3-$4-$5',
                                            $ad->author_phone,
                                        ),
                                    ); ?></span>
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

                        <?php if ($ad->author_phone): ?>
                            <button class="ad-contact-btn" id="ad-show-phone">Показать телефон</button>
                            <a href="tel:<?php echo esc_attr(
                                preg_replace("/[^\d+]/", "", $ad->author_phone),
                            ); ?>"
                               class="ad-contact-btn ad-contact-hidden" id="ad-phone-link">
                                Позвонить
                            </a>
                        <?php endif; ?>
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
                               target="_blank" rel="noopener" class="ad-share-link" title="Twitter" aria-label="Поделиться в Twitter">𝕏</a>
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

<!-- Скрипты для галереи и контактов -->
<?php if ($ad && !$is_unavailable): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Галерея ===
    const mainImg = document.getElementById('ad-gallery-main');
    const thumbs = document.querySelectorAll('.ad-gallery-thumb');
    const counter = document.querySelector('.ad-gallery-counter');

    if (mainImg && thumbs.length > 0) {
        thumbs.forEach(function(thumb) {
            thumb.addEventListener('click', function() {
                // Обновляем главное изображение
                mainImg.src = this.dataset.full;
                mainImg.alt = this.alt;

                // Обновляем активный класс
                thumbs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Обновляем счётчик
                if (counter) {
                    counter.textContent = (parseInt(this.dataset.index) + 1) + ' / ' + thumbs.length;
                }
            });
        });

        // Поддержка клавиатуры
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

    // === Показать телефон ===
    const showBtn = document.getElementById('ad-show-phone');
    const phoneLink = document.getElementById('ad-phone-link');

    if (showBtn && phoneLink) {
        showBtn.addEventListener('click', function() {
            showBtn.style.display = 'none';
            phoneLink.style.display = 'block';

            // Отправляем событие для аналитики (опционально)
            if (typeof gtag === 'function') {
                gtag('event', 'click', {
                    'event_category': 'ad_contact',
                    'event_label': 'phone_reveal'
                });
            }
        });
    }
});
</script>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
