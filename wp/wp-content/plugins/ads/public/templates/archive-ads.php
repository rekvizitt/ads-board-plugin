<?php
/**
 * Template: Ads Archive (Main Page)
 * URL: /board/
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

// Подключаем классы, если ещё не подключены
if (!class_exists("Ads_Frontend_Query")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-frontend-query.php";
}
if (!class_exists("Ads_Settings")) {
    require_once ADS_PLUGIN_DIR . "includes/class-ads-settings.php";
}

get_header();

// Получаем параметры из URL
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

// Настройки из админки
$per_page = ads_get_setting("ads_per_page", 12);
$date_format = ads_get_setting("date_format", "relative");
$show_views = ads_get_setting("show_views_count", 1);
$show_author = ads_get_setting("show_author", 1);
$grid_columns = ads_get_setting("grid_columns", "3");
$image_size = ads_get_setting("image_size", "medium");

// Запрос объявлений
$query = new Ads_Frontend_Query();
$result = $query->get_ads([
    "paged" => $paged,
    "per_page" => $per_page,
    "orderby" => $sort,
    "search" => $search,
    "category" => $category,
]);

// Получаем категории для фильтра
$categories = $query->get_categories_list();
?>

<div class="ads-board-container">

    <!-- Заголовок и поиск -->
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

    <!-- Сетка объявлений -->
    <div class="ads-grid" style="--grid-columns: <?php echo esc_attr(
        $grid_columns,
    ); ?>;">
        <?php if (!empty($result["items"])): ?>
            <?php foreach ($result["items"] as $ad):

                $ad_url = home_url("/board/ad/" . $ad->slug . "/");
                $primary_image = $ad->primary_image ?? null;
                $image_src = $primary_image
                    ? home_url($primary_image->file_path)
                    : ADS_PLUGIN_URL . "public/images/placeholder.png";
                ?>
                <article class="ad-card <?php echo $ad->is_pinned
                    ? "is-pinned"
                    : ""; ?> <?php echo $ad->is_important
     ? "is-important"
     : ""; ?>">

                    <?php if ($ad->is_pinned): ?>
                        <span class="ad-badge ad-badge-pinned">Закреплено</span>
                    <?php endif; ?>
                    <?php if ($ad->is_important): ?>
                        <span class="ad-badge ad-badge-important">Важное</span>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(
                        $ad_url,
                    ); ?>" class="ad-image-link">
                        <img src="<?php echo esc_url($image_src); ?>"
                             alt="<?php echo esc_attr($ad->title); ?>"
                             class="ad-image"
                             loading="lazy"
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px 4px 0 0;">
                    </a>

                    <div class="ad-content" style="padding: 15px;">
                        <h2 class="ad-title" style="margin: 0 0 10px; font-size: 18px;">
                            <a href="<?php echo esc_url(
                                $ad_url,
                            ); ?>" style="text-decoration: none; color: inherit;">
                                <?php echo esc_html(
                                    mb_strimwidth($ad->title, 0, 60, "…"),
                                ); ?>
                            </a>
                        </h2>

                        <?php if ($ad->price): ?>
                            <p class="ad-price" style="margin: 0 0 10px; font-weight: bold; font-size: 20px; color: #2e7d32;">
                                $<?php echo number_format_i18n(
                                    $ad->price,
                                    2,
                                ); ?>
                            </p>
                        <?php endif; ?>

                        <p class="ad-excerpt" style="margin: 0 0 15px; color: #666; font-size: 14px; line-height: 1.4;">
                            <?php echo esc_html(
                                wp_trim_words(
                                    wp_strip_all_tags($ad->description),
                                    20,
                                ),
                            ); ?>
                        </p>

                        <div class="ad-meta" style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 10px;">
                            <span class="ad-category">
                                <?php echo esc_html(
                                    $ad->category_name ?? "Без категории",
                                ); ?>
                            </span>
                            <span class="ad-date">
                                <?php echo Ads_Frontend_Query::format_date(
                                    $ad->created_at,
                                    $date_format,
                                ); ?>
                            </span>
                        </div>

                        <?php if ($show_views): ?>
                            <span class="ad-views" style="display: block; margin-top: 5px; font-size: 11px; color: #999;">
                                👁 <?php echo number_format_i18n(
                                    $ad->views_count,
                                ); ?> просмотров
                            </span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php
            endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #666;">
                Объявлений не найдено. <?php if (
                    $search ||
                    $category
                ): ?>Попробуйте изменить параметры поиска.<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Пагинация -->
    <?php if ($result["total_pages"] > 1): ?>
        <div class="ads-pagination" style="margin: 40px 0; text-align: center;">
            <?php
            $base_url =
                home_url("/board/") .
                ($result["total_pages"] > 1 ? "page/%#%/" : "");
            $query_args = array_filter([
                "s" => $search ?: null,
                "category" => $category ?: null,
                "sort" => $sort !== "newest" ? $sort : null,
            ]);

            echo paginate_links([
                "base" => $base_url . (empty($query_args) ? "" : "%_%"),
                "format" => empty($query_args)
                    ? ""
                    : "&" . http_build_query($query_args),
                "current" => $paged,
                "total" => $result["total_pages"],
                "prev_text" => "«",
                "next_text" => "»",
                "type" => "list",
                "add_args" => $query_args,
            ]);
            ?>
        </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
