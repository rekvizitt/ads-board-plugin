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
    // ✅ Простые правила без лишних скобок для начала
    private $rules = [
        'board/?$' => "index.php?ads_page=ads_archive",
        'board/category/?$' => "index.php?ads_page=ads_categories_list",
        'board/category/([^/]+)/?$' =>
            'index.php?ads_page=ads_category&ads_category_slug=$matches[1]',
        'board/ad/([^/]+)/?$' =>
            'index.php?ads_page=ads_single&ads_ad_slug=$matches[1]',
        'board/page/([0-9]+)/?$' =>
            'index.php?ads_page=ads_archive&paged=$matches[1]',
    ];

    private $template_map = [
        "ads_archive" => "archive-ads.php",
        "ads_categories_list" => "categories-list.php",
        "ads_category" => "category-archive.php",
        "ads_single" => "single-ads.php",
    ];

    public function register()
    {
        foreach ($this->rules as $regex => $redirect) {
            // ✅ 'top' — чтобы наши правила имели приоритет
            add_rewrite_rule($regex, $redirect, "top");
        }
    }

    public function register_query_vars($vars)
    {
        $vars[] = "ads_page";
        $vars[] = "ads_category_slug";
        $vars[] = "ads_ad_slug";
        return $vars;
    }

    public function handle_template_redirect()
    {
        $page = get_query_var("ads_page");
        if (!$page) {
            return;
        }

        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);

        $template = $this->template_map[$page] ?? null;
        $path = ADS_PLUGIN_DIR . "public/templates/" . $template;

        if (!$template || !file_exists($path)) {
            wp_safe_redirect(home_url("/"));
            exit();
        }

        include $path;
        exit();
    }

    public function flush()
    {
        $this->register();
        flush_rewrite_rules();
    }
}
