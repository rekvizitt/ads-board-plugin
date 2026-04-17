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
    <div class="ads-header">
        <div class="ads-header__top">
            <h1 class="ads-header__title">Доска объявлений</h1>
            <a href="<?php echo esc_url(
                home_url("/board/category/"),
            ); ?>" class="ads-header__categories-btn">
                Все категории
            </a>
        </div>
        <form method="get" class="ads-search-form">
            <input type="hidden" name="sort" value="<?php echo esc_attr(
                $sort,
            ); ?>">

            <input type="search" name="s" value="<?php echo esc_attr(
                $search,
            ); ?>"
                   placeholder="Поиск объявлений..."
                   class="ads-search-form__input">

            <select name="category" class="ads-search-form__select">
                <option value="">Все категории</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr(
                        $cat->id,
                    ); ?>" <?php selected($category, $cat->id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="sort" onchange="this.form.submit()" class="ads-search-form__select">
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

            <button type="submit" class="ads-search-form__btn">Найти</button>
            <?php if ($search || $category): ?>
                <a href="<?php echo esc_url(
                    home_url("/board/"),
                ); ?>" class="ads-search-form__reset">Сбросить</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="ads-grid" style="--grid-columns: <?php echo esc_attr(
        $grid_columns,
    ); ?>;">
        <?php if (!empty($result["items"])): ?>
            <?php foreach ($result["items"] as $ad):
                include ADS_PLUGIN_DIR . "public/templates/parts/card-ad.php";
            endforeach; ?>
        <?php else: ?>
            <p class="ads-no-results">Объявлений не найдено.</p>
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
