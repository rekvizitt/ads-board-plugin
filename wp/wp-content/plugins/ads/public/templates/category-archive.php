<?php
/**
 * Template: Category Archive & Category List
 * URL: /board/category/ или /board/category/{slug}/
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

if (!class_exists("Ads_Frontend_Query")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-frontend-query.php";
}
if (!class_exists("Ads_Settings")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-settings.php";
}

// Определяем режим просмотра
$category_id = get_query_var("ads_category_id");
$is_list_view = empty($category_id);

$category_name = get_query_var("ads_category_name", "");
$category_slug = get_query_var("ads_category_slug", "");
$category_description = get_query_var("ads_category_description", "");

// Параметры запроса
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

// Настройки
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

// Получаем данные
$query = new Ads_Frontend_Query();
$all_categories = $query->get_categories_list();

// Если просматриваем конкретную категорию — запрашиваем её объявления
$ads_result = null;
if (!$is_list_view) {
    $ads_result = $query->get_ads([
        "paged" => $paged,
        "per_page" => $per_page,
        "orderby" => $sort,
        "search" => $search,
        "category" => $category_id,
    ]);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo("charset"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(
        $category_name
            ? $category_name . " — Доска объявлений"
            : "Категории объявлений",
    ); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class("ads-board-page"); ?>>

<div class="ads-board-container">

    <!-- Хлебные крошки -->
    <nav class="ads-breadcrumbs" aria-label="Навигация">
        <a href="<?php echo esc_url(home_url("/")); ?>">Главная</a>
        <span class="separator">/</span>
        <a href="<?php echo esc_url(home_url("/board/")); ?>">Объявления</a>
        <?php if ($is_list_view): ?>
            <span class="separator">/</span>
            <span class="current">Все категории</span>
        <?php else: ?>
            <span class="separator">/</span>
            <a href="<?php echo esc_url(
                home_url("/board/category/"),
            ); ?>">Категории</a>
            <span class="separator">/</span>
            <span class="current"><?php echo esc_html($category_name); ?></span>
        <?php endif; ?>
    </nav>

    <!-- Заголовок -->
    <header class="ads-page-header">
        <?php if ($is_list_view): ?>
            <h1>Все категории</h1>
            <p>Выберите категорию для просмотра объявлений</p>
        <?php else: ?>
            <h1><?php echo esc_html($category_name); ?></h1>
            <?php if ($category_description): ?>
                <p><?php echo esc_html($category_description); ?></p>
            <?php endif; ?>
            <p class="ads-category-count">
                Найдено объявлений: <strong><?php echo (int) $ads_result[
                    "total_items"
                ]; ?></strong>
            </p>
        <?php endif; ?>
    </header>

    <?php if ($is_list_view): ?>
        <!-- Список категорий -->
        <div class="ads-categories-grid">
            <?php foreach ($all_categories as $cat): ?>
                <div class="ads-cat-card">
                    <a href="<?php echo esc_url(
                        home_url("/board/category/" . $cat->slug . "/"),
                    ); ?>">
                        <?php echo esc_html($cat->name); ?>
                    </a>
                    <span class="ads-cat-count"><?php echo (int) $cat->ads_count; ?></span>
                    <style>
                       
                    </style>        </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Фильтры и сортировка -->
        <form method="get" class="ads-search-form">
            <input type="search" name="s" value="<?php echo esc_attr(
                $search,
            ); ?>" placeholder="Поиск в категории...">
            <select name="sort" onchange="this.form.submit()">
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
            <button type="submit">Применить</button>
            <?php if ($search): ?>
                <a href="<?php echo esc_url(
                    remove_query_arg(["s"], $_SERVER["REQUEST_URI"]),
                ); ?>" class="ads-reset-button">
                    Сбросить
                </a>
            <?php endif; ?>
        </form>

        <!-- Сетка объявлений -->
        <div class="ads-grid" style="--grid-columns: <?php echo esc_attr(
            $grid_columns,
        ); ?>;">
            <?php if (!empty($ads_result["items"])): ?>
                <?php foreach ($ads_result["items"] as $ad):
                    include ADS_PLUGIN_DIR .
                        "public/templates/parts/card-ad.php";
                endforeach; ?>
            <?php else: ?>
                <p class="ads-no-results">
                    В этой категории пока нет объявлений.
                    <br><a href="<?php echo esc_url(
                        home_url("/board/category/"),
                    ); ?>">Посмотреть другие категории</a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Пагинация -->
        <?php if ($ads_result["total_pages"] > 1): ?>
            <div class="ads-pagination">
                <?php
                $base =
                    home_url("/board/category/" . $category_slug . "/") .
                    ($ads_result["total_pages"] > 1 ? "page/%#%/" : "");
                $args = array_filter([
                    "s" => $search ?: null,
                    "sort" => $sort !== "newest" ? $sort : null,
                ]);

                echo paginate_links([
                    "base" => $base . (empty($args) ? "" : "%_%"),
                    "format" => empty($args)
                        ? ""
                        : "&" . http_build_query($args),
                    "current" => $paged,
                    "total" => $ads_result["total_pages"],
                    "prev_text" => "«",
                    "next_text" => "»",
                    "type" => "list",
                    "add_args" => $args,
                ]);
                ?>
            </div>
        <?php endif; ?>

        <p class="ads-back-link">
            <a href="<?php echo esc_url(
                home_url("/board/category/"),
            ); ?>">← Все категории</a>
        </p>
    <?php endif; ?>

</div>

<?php wp_footer(); ?>
</body>
</html>
