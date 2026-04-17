<?php
/**
 * Template: Ads Archive (Main Page)
 * URL: /board/
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

// Подключаем зависимости
if (!class_exists("Ads_Frontend_Query")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-frontend-query.php";
}
if (!class_exists("Ads_Settings")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-settings.php";
}

// === ПОЛНЫЙ HTML-ВЫВОД БЕЗ get_header()/get_footer() ===
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo("charset"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(wp_get_document_title()); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Минимальные стили для изоляции плагина */
        .ads-board-container { max-width: 1200px; margin: 0 auto; padding: 20px; box-sizing: border-box; }
        .ads-grid { display: grid; grid-template-columns: repeat(var(--grid-columns, 3), 1fr); gap: 20px; }
        .ad-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; position: relative; }
        .ad-image { display: block; width: 100%; height: 200px; object-fit: cover; }
        .ad-content { padding: 15px; }
        .ad-title { margin: 0 0 10px; font-size: 18px; }
        .ad-title a { color: #222; text-decoration: none; }
        .ad-title a:hover { color: #0073aa; }
        .ad-price { color: #2e7d32; font-weight: 600; margin: 5px 0; }
        .ad-excerpt { color: #666; font-size: 14px; line-height: 1.4; margin: 5px 0 10px; }
        .ad-meta { display: flex; justify-content: space-between; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .ad-badge { position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 500; z-index: 2; color: #fff; }
        .ad-badge-pinned { background: #0073aa; }
        .ad-badge-important { background: #f0ad4e; }
        .ads-pagination { margin: 40px 0; text-align: center; }
        .ads-pagination .page-numbers { display: inline-flex; gap: 5px; list-style: none; padding: 0; }
        .ads-pagination .page-numbers a, .ads-pagination .page-numbers span { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #333; }
        .ads-pagination .page-numbers .current { background: #0073aa; color: #fff; border-color: #0073aa; }
        @media (max-width: 1024px) { .ads-grid { --grid-columns: 2; } }
        @media (max-width: 640px) { .ads-grid { --grid-columns: 1; } .ads-search-form { flex-direction: column; } .ads-search-form input, .ads-search-form select, .ads-search-form button { width: 100%; } }
    </style>
</head>
<body <?php body_class("ads-board-page"); ?>>

<?php
// === Основной контент ===

$paged = max(1, get_query_var("paged", 1));
$sort =
    isset($_GET["sort"]) &&
    in_array(
        $_GET["sort"],
        ["newest", "oldest", "price_asc", "price_desc"],
        true,
    )
        ? sanitize_text_field($_GET["sort"])
        : "newest";
$search = isset($_GET["s"]) ? sanitize_text_field(wp_unslash($_GET["s"])) : "";
$category = isset($_GET["category"]) ? absint($_GET["category"]) : 0;

$per_page = function_exists("ads_get_setting")
    ? ads_get_setting("ads_per_page", 12)
    : 12;
$date_format = function_exists("ads_get_setting")
    ? ads_get_setting("date_format", "relative")
    : "relative";
$show_views = function_exists("ads_get_setting")
    ? ads_get_setting("show_views_count", 1)
    : 1;
$grid_columns = function_exists("ads_get_setting")
    ? ads_get_setting("grid_columns", "3")
    : "3";

$query = new Ads_Frontend_Query();
$result = $query->get_ads([
    "paged" => $paged,
    "per_page" => $per_page,
    "orderby" => $sort,
    "search" => $search,
    "category" => $category,
]);

$categories = $query->get_categories_list();
?>

<div class="ads-board-container">
    <div class="ads-header" style="margin-bottom: 30px;">
        <h1 style="margin: 0 0 20px;">Доска объявлений</h1>

        <form method="get" class="ads-search-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="sort" value="<?php echo esc_attr(
                $sort,
            ); ?>">

            <input type="search" name="s" value="<?php echo esc_attr(
                $search,
            ); ?>"
                   placeholder="Поиск объявлений..."
                   style="flex: 1; min-width: 200px; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px;">

            <select name="category" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr(
                        $cat->id,
                    ); ?>" <?php selected($category, $cat->id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort" onchange="this.form.submit()" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="newest" <?php selected(
                    $sort,
                    "newest",
                ); ?>>Сначала новые</option>
                <option value="oldest" <?php selected(
                    $sort,
                    "oldest",
                ); ?>>Сначала старые</option>
                <option value="price_asc" <?php selected(
                    $sort,
                    "price_asc",
                ); ?>>Цена: по возрастанию</option>
                <option value="price_desc" <?php selected(
                    $sort,
                    "price_desc",
                ); ?>>Цена: по убыванию</option>
            </select>

            <button type="submit" class="button" style="padding: 10px 20px;">Найти</button>
            <?php if ($search || $category): ?>
                <a href="<?php echo esc_url(
                    home_url("/board/"),
                ); ?>" class="button" style="padding: 10px 20px;">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="ads-grid" style="--grid-columns: <?php echo esc_attr(
        $grid_columns,
    ); ?>;">
        <?php if (!empty($result["items"])): ?>
            <?php foreach ($result["items"] as $ad):

                $ad_url = home_url("/board/ad/" . $ad->slug . "/");
                $image_src = $ad->primary_image
                    ? home_url($ad->primary_image->file_path)
                    : "";
                $placeholder = ADS_PLUGIN_URL . "public/images/placeholder.png";
                ?>
                <article class="ad-card <?php echo $ad->is_pinned
                    ? "is-pinned"
                    : ""; ?> <?php echo $ad->is_important
     ? "is-important"
     : ""; ?>">
                    <?php if (
                        $ad->is_pinned
                    ): ?><span class="ad-badge ad-badge-pinned">Закреплено</span><?php endif; ?>
                    <?php if (
                        $ad->is_important
                    ): ?><span class="ad-badge ad-badge-important">Важное</span><?php endif; ?>

                    <a href="<?php echo esc_url(
                        $ad_url,
                    ); ?>" class="ad-image-link">
                        <img src="<?php echo esc_url(
                            $image_src ?: $placeholder,
                        ); ?>"
                             alt="<?php echo esc_attr($ad->title); ?>"
                             class="ad-image" loading="lazy">
                    </a>

                    <div class="ad-content">
                        <h2 class="ad-title">
                            <a href="<?php echo esc_url($ad_url); ?>">
                                <?php echo esc_html(
                                    mb_strimwidth($ad->title, 0, 60, "…"),
                                ); ?>
                            </a>
                        </h2>
                        <?php if ($ad->price): ?>
                            <p class="ad-price">$<?php echo number_format_i18n(
                                $ad->price,
                                2,
                            ); ?></p>
                        <?php endif; ?>
                        <p class="ad-excerpt">
                            <?php echo esc_html(
                                wp_trim_words(
                                    wp_strip_all_tags($ad->description),
                                    20,
                                ),
                            ); ?>
                        </p>
                        <div class="ad-meta">
                            <span class="ad-category"><?php echo esc_html(
                                $ad->category_name ?? "Без категории",
                            ); ?></span>
                            <span class="ad-date">
                                <?php echo Ads_Frontend_Query::format_date(
                                    $ad->created_at,
                                    $date_format,
                                ); ?>
                            </span>
                        </div>
                        <?php if ($show_views): ?>
                            <span class="ad-views"><?php echo number_format_i18n(
                                $ad->views_count,
                            ); ?> просмотров</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php
            endforeach; ?>
        <?php else: ?>
            <p class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                Объявлений не найдено.
            </p>
        <?php endif; ?>
    </div>

    <?php if ($result["total_pages"] > 1): ?>
        <div class="ads-pagination">
            <?php
            $base =
                home_url("/board/") .
                ($result["total_pages"] > 1 ? "page/%#%/" : "");
            $args = array_filter([
                "s" => $search ?: null,
                "category" => $category ?: null,
                "sort" => $sort !== "newest" ? $sort : null,
            ]);

            echo paginate_links([
                "base" => $base . (empty($args) ? "" : "%_%"),
                "format" => empty($args) ? "" : "&" . http_build_query($args),
                "current" => $paged,
                "total" => $result["total_pages"],
                "prev_text" => "«",
                "next_text" => "»",
                "type" => "list",
                "add_args" => $args,
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
