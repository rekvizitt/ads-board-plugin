<?php
/**
 * Frontend Query Helper for Ads Board
 * Handles optimized database queries for public-facing ads listing.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Frontend_Query
{
    private $wpdb;
    private $table_ads;
    private $table_categories;
    private $table_images;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_ads = $wpdb->prefix . "ads";
        $this->table_categories = $wpdb->prefix . "ads_categories";
        $this->table_images = $wpdb->prefix . "ads_images";
    }

    public function get_ads($args = [])
    {
        $defaults = [
            "paged" => 1,
            "per_page" => 12,
            "orderby" => "newest",
            "search" => "",
            "category" => 0,
            "status" => "active",
        ];
        $args = wp_parse_args($args, $defaults);

        $where = ["a.status = %s"];
        $params = [$args["status"]];

        if ($args["search"]) {
            $where[] = "(a.title LIKE %s OR a.description LIKE %s)";
            $like = "%" . $this->wpdb->esc_like($args["search"]) . "%";
            $params[] = $like;
            $params[] = $like;
        }

        if ($args["category"]) {
            $where[] = "a.category_id = %d";
            $params[] = $args["category"];
        }

        // Фильтр по датам: только опубликованные и не истёкшие
        $now = current_time("mysql");
        $where[] = "(a.published_at IS NULL OR a.published_at <= %s)";
        $params[] = $now;
        $where[] = "(a.expires_at IS NULL OR a.expires_at >= %s)";
        $params[] = $now;

        $where_sql = "WHERE " . implode(" AND ", $where);
        $order_sql = $this->get_order_sql($args["orderby"]);

        // Подсчёт для пагинации
        $count_query = "SELECT COUNT(*) FROM {$this->table_ads} a $where_sql";
        $total_items = (int) $this->wpdb->get_var(
            $this->wpdb->prepare($count_query, $params),
        );
        $total_pages = (int) ceil($total_items / $args["per_page"]);
        $offset = ($args["paged"] - 1) * $args["per_page"];

        // Основной запрос: JOIN категории + подзапрос для главного изображения
        $query = "
            SELECT
                a.*,
                c.name as category_name,
                (
                    SELECT file_path
                    FROM {$this->table_images}
                    WHERE ad_id = a.id AND is_primary = 1
                    LIMIT 1
                ) as primary_image_path
            FROM {$this->table_ads} a
            LEFT JOIN {$this->table_categories} c ON a.category_id = c.id
            $where_sql
            $order_sql
            LIMIT %d OFFSET %d
        ";
        $params[] = $args["per_page"];
        $params[] = $offset;

        $items = $this->wpdb->get_results(
            $this->wpdb->prepare($query, $params),
        );

        // Форматируем результат
        foreach ($items as &$item) {
            $item->primary_image = $item->primary_image_path
                ? (object) ["file_path" => $item->primary_image_path]
                : null;
            unset($item->primary_image_path);
        }

        return [
            "items" => $items,
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "current_page" => $args["paged"],
            "per_page" => $args["per_page"],
        ];
    }

    private function get_order_sql($orderby)
    {
        $allowed = [
            "newest" =>
                "a.is_pinned DESC, a.is_important DESC, a.created_at DESC",
            "oldest" =>
                "a.is_pinned DESC, a.is_important DESC, a.created_at ASC",
            "price_asc" => "a.is_pinned DESC, a.is_important DESC, a.price ASC",
            "price_desc" =>
                "a.is_pinned DESC, a.is_important DESC, a.price DESC",
        ];
        $order = $allowed[$orderby] ?? $allowed["newest"];
        return "ORDER BY $order";
    }

    public function get_categories_list()
    {
        return $this->wpdb->get_results("
            SELECT c.id, c.name, COUNT(a.id) as ads_count
            FROM {$this->table_categories} c
            LEFT JOIN {$this->table_ads} a ON c.id = a.category_id AND a.status = 'active'
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");
    }

    public static function format_date($datetime, $format)
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
}
