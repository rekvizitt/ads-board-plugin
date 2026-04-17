<?php
/**
 * Frontend Router for Ads Board
 * Handles custom URL registration, query vars, and template loading.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/includes
 */
if (!defined("ABSPATH")) {
    exit();
}

class Ads_Router
{
    private $template_map = [
        "ads_archive" => "archive-ads.php",
        "ads_categories_list" => "category-archive.php",
        "ads_category" => "category-archive.php",
        "ads_single" => "single-ads.php",
    ];

    public function handle_template_redirect()
    {
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        $uri = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
        $path_parts = explode("/", $uri);

        // /board/ или /board/page/2/
        if (
            $path_parts[0] === "board" &&
            (!isset($path_parts[1]) || $path_parts[1] === "")
        ) {
            $this->load_archive_template();
            exit();
        }

        // /board/page/2/
        if (
            $path_parts[0] === "board" &&
            $path_parts[1] === "page" &&
            is_numeric($path_parts[2] ?? "")
        ) {
            set_query_var("paged", absint($path_parts[2]));
            $this->load_archive_template();
            exit();
        }

        // /board/category/ или /board/category/{slug}/
        if ($path_parts[0] === "board" && $path_parts[1] === "category") {
            global $wp_query, $wpdb;

            $category_slug = $path_parts[2] ?? "";

            // Получаем ID категории по слаг
            $category = null;
            if ($category_slug) {
                $table = $wpdb->prefix . "ads_categories";
                $category = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id, name, slug, description FROM {$table} WHERE slug = %s",
                        sanitize_title($category_slug),
                    ),
                );
            }

            // Если категория не найдена — 404 или редирект на список
            if ($category_slug && !$category) {
                wp_safe_redirect(home_url("/board/category/"));
                exit();
            }

            $wp_query->is_404 = false;
            $wp_query->is_archive = true;
            status_header(200);

            // Передаём данные в шаблон
            if ($category) {
                set_query_var("ads_category_id", $category->id);
                set_query_var("ads_category_name", $category->name);
                set_query_var("ads_category_slug", $category->slug);
                set_query_var(
                    "ads_category_description",
                    $category->description,
                );
            }

            $page = $category_slug ? "ads_category" : "ads_categories_list";
            $this->load_template($page);
            exit();
        }

        // /board/ad/{slug}/
        if (
            $path_parts[0] === "board" &&
            $path_parts[1] === "ad" &&
            !empty($path_parts[2])
        ) {
            global $wp_query, $wpdb;

            $ad_slug = sanitize_title($path_parts[2]);
            $table_ads = $wpdb->prefix . "ads";
            $table_cats = $wpdb->prefix . "ads_categories";

            // Получаем объявление с категорией
            $ad = $wpdb->get_row(
                $wpdb->prepare(
                    "
                SELECT a.*, c.name as category_name, c.slug as category_slug
                FROM {$table_ads} a
                LEFT JOIN {$table_cats} c ON a.category_id = c.id
                WHERE a.slug = %s
            ",
                    $ad_slug,
                ),
            );

            // 404 если не найдено
            if (!$ad) {
                status_header(404);
                $wp_query->is_404 = true;
                // Можно загрузить шаблон 404 темы или редирект
                if (locate_template("404.php")) {
                    locate_template("404.php", true);
                } else {
                    echo '<h1>Объявление не найдено</h1><a href="' .
                        esc_url(home_url("/board/")) .
                        '">Вернуться к списку</a>';
                }
                exit();
            }

            // Проверка срока действия
            $now = current_time("mysql");
            $is_expired = $ad->expires_at && $ad->expires_at < $now;
            $is_scheduled = $ad->published_at && $ad->published_at > $now;
            $is_draft = $ad->status !== "active" && $ad->status !== "sold";

            // Если объявление неактивно и пользователь не админ — показываем сообщение
            if (
                ($is_expired || $is_scheduled || $is_draft) &&
                !current_user_can("manage_options")
            ) {
                status_header(410); // Gone
                $wp_query->is_404 = true;

                // Загружаем шаблон с сообщением
                set_query_var("ads_ad_unavailable", true);
                set_query_var(
                    "ads_ad_unavailable_reason",
                    $is_expired
                        ? "expired"
                        : ($is_scheduled
                            ? "scheduled"
                            : "draft"),
                );
                set_query_var("ads_ad_data", $ad);

                $this->load_template("ads_single");
                exit();
            }

            // Инкремент просмотров (с защитой)
            $this->increment_view_count($ad->id);

            // Получаем галерею
            $table_images = $wpdb->prefix . "ads_images";
            $gallery = $wpdb->get_results(
                $wpdb->prepare(
                    "
                SELECT id, file_path, file_name, is_primary
                FROM {$table_images}
                WHERE ad_id = %d
                ORDER BY is_primary DESC, sort_order ASC, id ASC
            ",
                    $ad->id,
                ),
            );

            // Получаем похожие объявления
            $related = $wpdb->get_results(
                $wpdb->prepare(
                    "
                SELECT id, title, slug, price,
                    (SELECT file_path FROM {$table_images} WHERE ad_id = a.id AND is_primary = 1 LIMIT 1) as primary_image
                FROM {$table_ads} a
                WHERE a.category_id = %d
                AND a.id != %d
                AND a.status = 'active'
                AND (a.expires_at IS NULL OR a.expires_at >= %s)
                AND (a.published_at IS NULL OR a.published_at <= %s)
                ORDER BY a.is_pinned DESC, a.created_at DESC
                LIMIT 4
            ",
                    $ad->category_id,
                    $ad->id,
                    $now,
                    $now,
                ),
            );

            // Передаём данные в шаблон
            $wp_query->is_404 = false;
            $wp_query->is_singular = true;
            status_header(200);

            set_query_var("ads_ad_data", $ad);
            set_query_var("ads_ad_gallery", $gallery);
            set_query_var("ads_ad_related", $related);
            set_query_var("ads_ad_unavailable", false);

            $this->load_template("ads_single");
            exit();
        }
    }

    private function load_archive_template()
    {
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_archive = true;
        status_header(200);
        $this->load_template("ads_archive");
    }

    private function load_template($page_key)
    {
        $template = $this->template_map[$page_key] ?? null;
        $path = ADS_PLUGIN_DIR . "public/templates/" . $template;

        if (!$template || !file_exists($path)) {
            wp_safe_redirect(home_url("/"));
            exit();
        }

        include $path;
    }

    // Заглушки для совместимости
    public function register_rewrite_rules() {}
    public function register_query_vars($vars)
    {
        $vars[] = "ads_page";
        $vars[] = "ads_category_slug";
        $vars[] = "ads_category_id";
        $vars[] = "ads_ad_slug";
        $vars[] = "ads_category_name";
        return $vars;
    }
    public function flush()
    {
        flush_rewrite_rules();
    }
    /**
     * Инкремент счётчика просмотров с защитой от накрутки
     */
    private function increment_view_count($ad_id)
    {
        // Защита: не считаем просмотры админов и ботов
        if (current_user_can("manage_options") || is_user_logged_in()) {
            return;
        }

        // Cookie-проверка: один просмотр с одного браузера в сутки
        $cookie_name = "ads_viewed_" . $ad_id;
        if (isset($_COOKIE[$cookie_name])) {
            return;
        }

        // Rate limit: не чаще 1 просмотра в 10 минут с одного IP
        $transient_key =
            "ads_view_limit_" . $ad_id . "_" . $_SERVER["REMOTE_ADDR"];
        if (get_transient($transient_key)) {
            return;
        }

        // Инкремент в БД
        global $wpdb;
        $table = $wpdb->prefix . "ads";
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET views_count = views_count + 1 WHERE id = %d",
                $ad_id,
            ),
        );

        // Устанавливаем метки
        setcookie(
            $cookie_name,
            "1",
            time() + DAY_IN_SECONDS,
            COOKIEPATH,
            COOKIE_DOMAIN,
        );
        set_transient($transient_key, "1", 10 * MINUTE_IN_SECONDS);
    }
}
