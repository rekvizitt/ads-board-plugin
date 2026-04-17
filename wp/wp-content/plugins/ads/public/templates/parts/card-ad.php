<?php
/**
 * Template Part: Ad Card Component
 * @package Ads_Board
 *
 * @var stdClass $ad          Объект объявления
 * @var bool     $show_price  Показывать цену? (по умолчанию true)
 * @var string   $date_format Формат даты (по умолчанию 'relative')
 */

if (!defined("ABSPATH")) {
    exit();
}
if (empty($ad)) {
    return;
}

$show_price = isset($show_price) ? (bool) $show_price : true;
$date_format = $date_format ?? "relative";

// URL
$ad_url = home_url("/board/ad/" . $ad->slug . "/");

// Изображение (поддержка строки или объекта из разных запросов)
$image_src = ADS_PLUGIN_URL . "public/images/placeholder.png";
if (!empty($ad->primary_image)) {
    $image_src = is_object($ad->primary_image)
        ? home_url($ad->primary_image->file_path)
        : home_url($ad->primary_image);
}

// Цена
$price_html = "";
if (
    $show_price &&
    isset($ad->price) &&
    $ad->price !== null &&
    (float) $ad->price > 0
) {
    $price_html =
        '<p class="ad-card-price">$' .
        number_format_i18n((float) $ad->price, 2) .
        "</p>";
}

// Дата
$date_html = "";
if (!empty($ad->created_at)) {
    if ($date_format === "relative") {
        $date_html =
            human_time_diff(
                strtotime($ad->created_at),
                current_time("timestamp"),
            ) . " назад";
    } else {
        $date_html = date_i18n($date_format, strtotime($ad->created_at));
    }
}

// Бейджи — ИСПРАВЛЕНО
$badges = [];
if (!empty($ad->is_pinned)) {
    $badges[] = '<span class="ad-badge ad-badge-pinned">Закреплено</span>';
}
if (!empty($ad->is_important)) {
    $badges[] = '<span class="ad-badge ad-badge-important">Важное</span>';
}
if (!empty($ad->status) && $ad->status === "sold") {
    $badges[] = '<span class="ad-badge ad-badge-sold">Продано</span>';
}
$badges_html = !empty($badges)
    ? '<div class="ad-badges">' . implode("", $badges) . "</div>"
    : "";

// Категория
$category_html = "";
if (!empty($ad->category_name)) {
    $cat_slug = $ad->category_slug ?? "";
    $cat_url = $cat_slug ? home_url("/board/category/" . $cat_slug . "/") : "#";
    $category_html =
        '<a href="' .
        esc_url($cat_url) .
        '" class="ad-card-category">' .
        esc_html($ad->category_name) .
        "</a>";
}
?>

<article class="ad-card <?php echo !empty($ad->is_pinned)
    ? "is-pinned"
    : ""; ?> <?php echo !empty($ad->is_important)
     ? "is-important"
     : ""; ?> <?php echo !empty($ad->status) && $ad->status === "sold"
     ? "is-sold"
     : ""; ?>">
    <a href="<?php echo esc_url($ad_url); ?>" class="ad-card-image">
        <img src="<?php echo esc_url($image_src); ?>"
             alt="<?php echo esc_attr($ad->title); ?>"
             loading="lazy"
             decoding="async">
        <?php echo $badges_html;
// Теперь бейджи отобразятся корректно
?>
    </a>

    <div class="ad-card-content">
        <h3 class="ad-card-title">
            <a href="<?php echo esc_url($ad_url); ?>">
                <?php echo esc_html(wp_trim_words($ad->title, 8, "…")); ?>
            </a>
        </h3>

        <?php echo $price_html; ?>

        <div class="ad-card-meta">
            <?php echo $category_html; ?>
            <span class="ad-card-date"><?php echo esc_html(
                $date_html,
            ); ?></span>
        </div>
    </div>
</article>
