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
    private $table_ads;
    private $table_categories;
    private $table_images;

    public function __construct()
    {
        global $wpdb;
        $this->table_ads = $wpdb->prefix . "ads";
        $this->table_categories = $wpdb->prefix . "ads_categories";
        $this->table_images = $wpdb->prefix . "ads_images";
    }

    /**
     * Получение объявлений с оптимизацией
     */
    public function get_ads($args = [])
    {
        global $wpdb;

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

        // Поиск по заголовку и описанию
        if ($args["search"]) {
            $where[] = "(a.title LIKE %s OR a.description LIKE %s)";
            $like = "%" . $wpdb->esc_like($args["search"]) . "%";
            $params[] = $like;
            $params[] = $like;
        }

        // Фильтр по категории
        if ($args["category"]) {
            $where[] = "a.category_id = %d";
            $params[] = $args["category"];
        }

        // Фильтр по датам: только опубликованные и не истёкшие
        $where[] = "(a.published_at IS NULL OR a.published_at <= %s)";
        $params[] = current_time("mysql");

        $where[] = "(a.expires_at IS NULL OR a.expires_at >= %s)";
        $params[] = current_time("mysql");

        $where_sql = "WHERE " . implode(" AND ", $where);

        // Сортировка
        $order_sql = $this->get_order_sql($args["orderby"]);

        // Подсчёт общего количества (для пагинации)
        $count_query = "SELECT COUNT(*) FROM {$this->table_ads} a $where_sql";
        $total_items = (int) $wpdb->get_var(
            $wpdb->prepare($count_query, $params),
        );
        $total_pages = (int) ceil($total_items / $args["per_page"]);
        $offset = ($args["paged"] - 1) * $args["per_page"];

        // Основной запрос с JOIN для категории и одного изображения
        // Используем подзапрос для primary image чтобы избежать дублирования строк
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

        $items = $wpdb->get_results($wpdb->prepare($query, $params));

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

    /**
     * Формирование ORDER BY
     */
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

    /**
     * Список категорий для фильтра
     */
    public function get_categories_list()
    {
        global $wpdb;
        return $wpdb->get_results("
            SELECT c.id, c.name, COUNT(a.id) as ads_count
            FROM {$this->table_categories} c
            LEFT JOIN {$this->table_ads} a ON c.id = a.category_id AND a.status = 'active'
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ");
    }

    /**
     * Форматирование даты согласно настройкам
     */
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
