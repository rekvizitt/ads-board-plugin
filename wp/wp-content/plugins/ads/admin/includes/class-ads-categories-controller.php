<?php
/**
 * Categories Controller for Ads Board
 * Handles CRUD operations for ad categories in admin panel.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Categories_Controller
{
    private $table_categories;

    public function __construct()
    {
        global $wpdb;
        $this->table_categories = $wpdb->prefix . "ads_categories";
    }

    /**
     * Получение всех категорий с пагинацией
     */
    public function get_categories($args = [])
    {
        global $wpdb;

        $defaults = [
            "search" => "",
            "orderby" => "sort_order",
            "order" => "ASC",
            "paged" => 1,
            "per_page" => 20,
        ];
        $args = wp_parse_args($args, $defaults);

        // Поиск
        $where = "";
        $params = [];
        if ($args["search"]) {
            $where =
                "WHERE name LIKE %s OR slug LIKE %s OR description LIKE %s";
            $like = "%" . $wpdb->esc_like($args["search"]) . "%";
            $params = [$like, $like, $like];
        }

        // Сортировка
        $allowed_orderby = ["id", "name", "slug", "sort_order", "created_at"];
        $orderby = in_array($args["orderby"], $allowed_orderby, true)
            ? $args["orderby"]
            : "sort_order";
        $order = strtoupper($args["order"]) === "DESC" ? "DESC" : "ASC";

        // Подсчёт
        $count_sql = "SELECT COUNT(*) FROM {$this->table_categories} $where";
        $total_items = $params
            ? $wpdb->get_var($wpdb->prepare($count_sql, $params))
            : $wpdb->get_var($count_sql);

        $total_pages = ceil($total_items / $args["per_page"]);
        $offset = ($args["paged"] - 1) * $args["per_page"];

        // Получение данных
        $query = "SELECT * FROM {$this->table_categories} $where ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $params[] = $args["per_page"];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, $params));

        return [
            "items" => $items,
            "total_items" => (int) $total_items,
            "total_pages" => (int) $total_pages,
            "current_page" => (int) $args["paged"],
            "per_page" => (int) $args["per_page"],
        ];
    }

    /**
     * Получение одной категории по ID
     */
    public function get_category($id)
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_categories} WHERE id = %d",
                absint($id),
            ),
        );
    }

    /**
     * Валидация данных категории
     */
    public function validate_category_data($data, $is_update = false)
    {
        $errors = [];

        // Название
        $name = trim($data["name"] ?? "");
        if (empty($name)) {
            $errors["name"] = __(
                "Название категории обязательно.",
                "ads-board",
            );
        } elseif (strlen($name) > 255) {
            $errors["name"] = __(
                "Название не должно превышать 255 символов.",
                "ads-board",
            );
        }

        // Slug
        $slug = trim($data["slug"] ?? "");
        if (empty($slug)) {
            // Автогенерация из названия
            $slug = sanitize_title($name);
        } else {
            $slug = sanitize_title($slug);
        }

        // Проверка уникальности slug
        global $wpdb;
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_categories} WHERE slug = %s" .
                    ($is_update ? " AND id != %d" : ""),
                $slug,
                $is_update ? absint($data["id"] ?? 0) : 0,
            ),
        );

        if ($existing) {
            $errors["slug"] = __(
                "Этот ярлык (slug) уже используется.",
                "ads-board",
            );
        }

        // Описание (опционально)
        $description = sanitize_textarea_field($data["description"] ?? "");
        if (strlen($description) > 1000) {
            $errors["description"] = __(
                "Описание не должно превышать 1000 символов.",
                "ads-board",
            );
        }

        // Сортировка
        $sort_order = isset($data["sort_order"])
            ? absint($data["sort_order"])
            : 0;

        return [
            "valid" => empty($errors),
            "errors" => $errors,
            "sanitized" => [
                "name" => sanitize_text_field($name),
                "slug" => $slug,
                "description" => $description,
                "sort_order" => $sort_order,
            ],
        ];
    }

    /**
     * Создание категории
     */
    public function create_category($data)
    {
        $validation = $this->validate_category_data($data);
        if (!$validation["valid"]) {
            return ["success" => false, "errors" => $validation["errors"]];
        }

        global $wpdb;
        $result = $wpdb->insert(
            $this->table_categories,
            $validation["sanitized"] + ["created_at" => current_time("mysql")],
        );

        if ($result === false) {
            return [
                "success" => false,
                "errors" => ["db" => __("Ошибка базы данных.", "ads-board")],
            ];
        }

        return [
            "success" => true,
            "id" => $wpdb->insert_id,
            "message" => __("Категория создана.", "ads-board"),
        ];
    }

    /**
     * Обновление категории
     */
    public function update_category($id, $data)
    {
        $id = absint($id);
        $data["id"] = $id;

        $validation = $this->validate_category_data($data, true);
        if (!$validation["valid"]) {
            return ["success" => false, "errors" => $validation["errors"]];
        }

        global $wpdb;
        $result = $wpdb->update(
            $this->table_categories,
            $validation["sanitized"],
            ["id" => $id],
        );

        if ($result === false) {
            return [
                "success" => false,
                "errors" => ["db" => __("Ошибка базы данных.", "ads-board")],
            ];
        }

        return [
            "success" => true,
            "message" => __("Категория обновлена.", "ads-board"),
        ];
    }

    /**
     * Удаление категории
     */
    public function delete_category($id)
    {
        $id = absint($id);

        global $wpdb;

        // 🔒 Проверка: есть ли объявления в этой категории?
        $ads_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ads WHERE category_id = %d",
                $id,
            ),
        );

        if ($ads_count > 0) {
            return [
                "success" => false,
                "errors" => [
                    "cannot_delete" => sprintf(
                        __(
                            "Нельзя удалить категорию: в ней %d объявлений. Сначала удалите или переместите объявления.",
                            "ads-board",
                        ),
                        $ads_count,
                    ),
                ],
            ];
        }

        $result = $wpdb->delete($this->table_categories, ["id" => $id]);

        return $result
            ? [
                "success" => true,
                "message" => __("Категория удалена.", "ads-board"),
            ]
            : [
                "success" => false,
                "errors" => ["db" => __("Ошибка при удалении.", "ads-board")],
            ];
    }

    /**
     * Обработка действий (добавление/редактирование/удаление)
     */
    public function handle_action($action, $data = [])
    {
        check_admin_referer(
            "ads_categories_nonce",
            "ads_categories_nonce_field",
        );

        if (!current_user_can("manage_options")) {
            return [
                "success" => false,
                "errors" => ["permission" => __("Нет прав.", "ads-board")],
            ];
        }

        switch ($action) {
            case "create":
                return $this->create_category($data);
            case "update":
                return $this->update_category($data["id"] ?? 0, $data);
            case "delete":
                return $this->delete_category($data["id"] ?? 0);
            default:
                return [
                    "success" => false,
                    "errors" => [
                        "action" => __("Неизвестное действие.", "ads-board"),
                    ],
                ];
        }
    }
}
