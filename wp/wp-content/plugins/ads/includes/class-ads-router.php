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
        "ads_categories_list" => "categories-list.php",
        "ads_category" => "category-archive.php",
        "ads_single" => "single-ads.php",
    ];

    /**
     * Прямой парсинг URI без зависимости от rewrite rules
     */
    public function handle_template_redirect()
    {
        // Если это уже админка или AJAX — не трогаем
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        $uri = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
        $path_parts = explode("/", $uri);

        // /board/ или /board/page/2/
        if ($path_parts[0] === "board") {
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_singular = true;
            status_header(200);

            // Пагинация
            if (!empty($path_parts[2]) && is_numeric($path_parts[2])) {
                set_query_var("paged", absint($path_parts[2]));
            }

            $this->load_template("ads_archive");
            exit();
        }

        // /board/category/ или /board/category/{slug}/
        if ($path_parts[0] === "board" && $path_parts[1] === "category") {
            global $wp_query;
            $wp_query->is_404 = false;
            status_header(200);

            set_query_var("ads_category_slug", $path_parts[2] ?? "");
            $page = !empty($path_parts[2])
                ? "ads_category"
                : "ads_categories_list";
            $this->load_template($page);
            exit();
        }

        // /board/ad/{slug}/
        if (
            $path_parts[0] === "board" &&
            $path_parts[1] === "ad" &&
            !empty($path_parts[2])
        ) {
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_singular = true;
            status_header(200);

            set_query_var("ads_ad_slug", sanitize_title($path_parts[2]));
            $this->load_template("ads_single");
            exit();
        }
    }

    /**
     * Загрузка шаблона с проверкой существования
     */
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

    /**
     * (Опционально) Оставляем для совместимости, но не используем
     */
    public function register_rewrite_rules() {}
    public function register_query_vars($vars)
    {
        $vars[] = "ads_page";
        $vars[] = "ads_category_slug";
        $vars[] = "ads_ad_slug";
        return $vars;
    }
    public function flush()
    {
        flush_rewrite_rules();
    }
}
