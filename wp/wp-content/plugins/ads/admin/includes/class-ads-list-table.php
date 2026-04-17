<?php
/**
 * List Table Controller for Ads
 * Handles data fetching, filtering, and actions for the admin listings page.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_List_Table
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
     * Получение параметров фильтрации из $_GET
     */
    public function get_filters()
    {
        return [
            "search" => isset($_GET["s"])
                ? sanitize_text_field(wp_unslash($_GET["s"]))
                : "",
            "status" => isset($_GET["status"])
                ? sanitize_text_field(wp_unslash($_GET["status"]))
                : "all",
            "category" => isset($_GET["category"])
                ? absint($_GET["category"])
                : 0,
            "paged" => max(
                1,
                isset($_GET["paged"]) ? absint($_GET["paged"]) : 1,
            ),
            "per_page" => apply_filters("ads_board_admin_per_page", 20),
        ];
    }

    /**
     * Получение списка категорий для фильтра
     */
    public function get_categories_list()
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, name FROM {$this->table_categories} ORDER BY name ASC",
        );
    }

    /**
     * Построение SQL WHERE-условий
     */
    private function build_where_clause($filters, &$params)
    {
        $where = [];

        if ($filters["search"]) {
            $where[] =
                "(title LIKE %s OR description LIKE %s OR author_name LIKE %s OR author_email LIKE %s)";
            $like = "%" . $GLOBALS["wpdb"]->esc_like($filters["search"]) . "%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if ($filters["status"] !== "all") {
            $where[] = "status = %s";
            $params[] = $filters["status"];
        }

        if ($filters["category"]) {
            $where[] = "category_id = %d";
            $params[] = $filters["category"];
        }

        return $where ? "WHERE " . implode(" AND ", $where) : "";
    }

    /**
     * Получение данных для таблицы
     */
    public function get_items($filters)
    {
        global $wpdb;
        $params = [];
        $where = $this->build_where_clause($filters, $params);

        // Подсчёт общего количества
        if (!empty($params)) {
            $count_sql = "SELECT COUNT(*) FROM {$this->table_ads} $where";
            $total_items = $wpdb->get_var($wpdb->prepare($count_sql, $params));
        } else {
            $total_items = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->table_ads}",
            );
        }
        $total_pages = ceil($total_items / $filters["per_page"]);
        $offset = ($filters["paged"] - 1) * $filters["per_page"];

        // Получение записей
        $query = "
            SELECT a.*, c.name as category_name
            FROM {$this->table_ads} a
            LEFT JOIN {$this->table_categories} c ON a.category_id = c.id
            $where
            ORDER BY a.is_pinned DESC, a.created_at DESC
            LIMIT %d OFFSET %d
        ";
        $params[] = $filters["per_page"];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, $params));

        return [
            "items" => $items,
            "total_items" => $total_items,
            "total_pages" => $total_pages,
            "current_page" => $filters["paged"],
            "per_page" => $filters["per_page"],
        ];
    }

    /**
     * Обработка массовых действий
     */
    public function handle_bulk_actions()
    {
        if (!isset($_POST["ads_bulk_action"]) || !isset($_POST["ads_ids"])) {
            return null;
        }

        check_admin_referer("ads_bulk_action_nonce", "ads_bulk_nonce");

        $action = sanitize_text_field($_POST["ads_bulk_action"]);
        $ids = array_map("absint", $_POST["ads_ids"]);

        if (empty($ids)) {
            return null;
        }

        global $wpdb;
        $message = "";

        $format = implode(",", array_fill(0, count($ids), "%d"));

        switch ($action) {
            case "delete":
                $this->delete_ads($ids);
                $message = __("Объявления удалены.", "ads-board");
                break;
            case "activate":
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->table_ads} SET status = 'active' WHERE id IN (" .
                            implode(",", $ids) .
                            ")",
                    ),
                );
                $message = __("Объявления активированы.", "ads-board");
                break;
            case "deactivate":
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->table_ads} SET status = 'draft' WHERE id IN (" .
                            implode(",", $ids) .
                            ")",
                    ),
                );
                $message = __("Объявления сняты с публикации.", "ads-board");
                break;
        }

        return $message ? ["type" => "success", "message" => $message] : null;
    }

    /**
     * Удаление объявлений с очисткой изображений
     */
    private function delete_ads($ids)
    {
        global $wpdb;

        if (empty($ids)) {
            return;
        }
        $format = implode(",", array_fill(0, count($ids), "%d"));
        foreach ($ids as $ad_id) {
            // Удаляем файлы изображений
            $images = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT file_path FROM {$this->table_images} WHERE ad_id = %d",
                    $ad_id,
                ),
            );
            foreach ($images as $img) {
                $full_path = ABSPATH . ltrim($img->file_path, "/");
                if (file_exists($full_path)) {
                    @unlink($full_path);
                }
            }
        }
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_images} WHERE ad_id IN ($format)",
                $ids,
            ),
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_ads} WHERE id IN ($format)",
                $ids,
            ),
        );
    }

    /**
     * Обработка одиночного действия (удаление/статус)
     */
    public function handle_single_action($action, $ad_id)
    {
        global $wpdb;
        $ad_id = absint($ad_id);

        switch ($action) {
            case "delete":
                check_admin_referer("delete_ad_" . $ad_id);
                $this->delete_ads([$ad_id]);
                return [
                    "type" => "success",
                    "message" => __("Объявление удалено.", "ads-board"),
                ];

            case "activate":
            case "deactivate":
                check_admin_referer("toggle_status_" . $ad_id);
                $new_status = $action === "activate" ? "active" : "draft";
                $wpdb->update(
                    $this->table_ads,
                    ["status" => $new_status],
                    ["id" => $ad_id],
                );
                $msg =
                    $action === "activate"
                        ? __("Объявление активировано.", "ads-board")
                        : __("Объявление скрыто.", "ads-board");
                return ["type" => "success", "message" => $msg];
        }
        return null;
    }
}
