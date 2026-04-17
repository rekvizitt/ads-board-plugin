<?php
/**
 * Items Controller for Ads Board
 * Handles CRUD operations for ads in admin panel.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Items_Controller
{
    private $table_ads;
    private $table_categories;
    private $table_images;
    private $uploader;

    public function __construct()
    {
        global $wpdb;
        $this->table_ads = $wpdb->prefix . "ads";
        $this->table_categories = $wpdb->prefix . "ads_categories";
        $this->table_images = $wpdb->prefix . "ads_images";
        $this->uploader = new Ads_File_Uploader();
    }

    /**
     * Получение списка объявлений с фильтрацией
     */
    public function get_items($args = [])
    {
        global $wpdb;

        $defaults = [
            "search" => "",
            "status" => "all",
            "category" => 0,
            "orderby" => "created_at",
            "order" => "DESC",
            "paged" => 1,
            "per_page" => 20,
        ];
        $args = wp_parse_args($args, $defaults);

        $where = [];
        $params = [];

        // Поиск
        if ($args["search"]) {
            $where[] =
                "(title LIKE %s OR description LIKE %s OR author_name LIKE %s OR author_email LIKE %s)";
            $like = "%" . $wpdb->esc_like($args["search"]) . "%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        // Статус
        if ($args["status"] !== "all") {
            $where[] = "status = %s";
            $params[] = $args["status"];
        }

        // Категория
        if ($args["category"]) {
            $where[] = "category_id = %d";
            $params[] = $args["category"];
        }

        $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

        // Подсчёт
        $count_sql = "SELECT COUNT(*) FROM {$this->table_ads} $where_sql";
        $total_items = $params
            ? $wpdb->get_var($wpdb->prepare($count_sql, $params))
            : $wpdb->get_var($count_sql);

        $total_pages = ceil($total_items / $args["per_page"]);
        $offset = ($args["paged"] - 1) * $args["per_page"];

        // Получение данных
        $query = "
            SELECT a.*, c.name as category_name
            FROM {$this->table_ads} a
            LEFT JOIN {$this->table_categories} c ON a.category_id = c.id
            $where_sql
            ORDER BY {$args["orderby"]} {$args["order"]}
            LIMIT %d OFFSET %d
        ";
        $params[] = $args["per_page"];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($query, $params));

        // Добавляем галерею к каждому объявлению
        foreach ($items as &$item) {
            $item->gallery = $this->uploader->get_ad_gallery($item->id);
        }

        return [
            "items" => $items,
            "total_items" => (int) $total_items,
            "total_pages" => (int) $total_pages,
            "current_page" => (int) $args["paged"],
            "per_page" => (int) $args["per_page"],
        ];
    }

    /**
     * Получение одного объявления
     */
    public function get_item($id)
    {
        global $wpdb;
        $item = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT a.*, c.name as category_name FROM {$this->table_ads} a
             LEFT JOIN {$this->table_categories} c ON a.category_id = c.id
             WHERE a.id = %d",
                absint($id),
            ),
        );

        if ($item) {
            $item->gallery = $this->uploader->get_ad_gallery($item->id);
        }
        return $item;
    }

    /**
     * Получение списка категорий для select
     */
    public function get_categories_list()
    {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, name FROM {$this->table_categories} ORDER BY name ASC",
        );
    }

    /**
     * Валидация данных объявления
     */
    public function validate_item_data($data, $is_update = false)
    {
        $errors = [];

        // 🔹 Заголовок (обязателен, из него генерируем slug)
        $title = trim($data["title"] ?? "");
        if (empty($title)) {
            $errors["title"] = __("Заголовок обязателен.", "ads-board");
        } elseif (strlen($title) > 255) {
            $errors["title"] = __(
                "Заголовок не должен превышать 255 символов.",
                "ads-board",
            );
        }

        // 🔹 Slug — генерируем ОБЯЗАТЕЛЬНО, даже если есть ошибки в других полях
        $slug = "";
        if (!empty($data["slug"])) {
            $slug = sanitize_title($data["slug"]);
        } elseif (!empty($title)) {
            // Автогенерация из заголовка
            $slug = sanitize_title($title);
        }

        // Если slug всё ещё пустой (заголовок тоже пустой) — генерируем уникальный
        if (empty($slug)) {
            $slug = "ad-" . time() . "-" . substr(md5(uniqid()), 0, 6);
        }

        // Проверка уникальности slug
        global $wpdb;
        $table_ads = $wpdb->prefix . "ads";
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_ads} WHERE slug = %s" .
                    ($is_update ? " AND id != %d" : ""),
                $slug,
                $is_update ? absint($data["id"] ?? 0) : 0,
            ),
        );

        if ($existing) {
            $errors["slug"] = __("Этот ярлок уже используется.", "ads-board");
            // Генерируем уникальный, чтобы запрос не упал
            $slug = $slug . "-" . substr(md5(uniqid()), 0, 4);
        }

        // 🔹 Описание
        $description = $data["description"] ?? "";
        $allowed_html = wp_kses_allowed_html("post");
        if (
            empty($description) ||
            strlen(wp_strip_all_tags($description)) < 10
        ) {
            $errors["description"] = __(
                "Описание должно содержать минимум 10 символов.",
                "ads-board",
            );
        }

        // 🔹 Цена
        $price = $data["price"] ?? "";
        if ($price !== "" && !is_numeric($price)) {
            $errors["price"] = __("Цена должна быть числом.", "ads-board");
        } elseif ($price !== "" && (float) $price < 0) {
            $errors["price"] = __(
                "Цена не может быть отрицательной.",
                "ads-board",
            );
        }

        // 🔹 Автор
        $author_name = trim($data["author_name"] ?? "");
        if (empty($author_name)) {
            $errors["author_name"] = __("Укажите имя автора.", "ads-board");
        }

        // 🔹 Контакты (хотя бы один)
        $phone = trim($data["author_phone"] ?? "");
        $email = trim($data["author_email"] ?? "");
        if (empty($phone) && empty($email)) {
            $errors["contacts"] = __("Укажите телефон или email.", "ads-board");
        }
        if ($email && !is_email($email)) {
            $errors["author_email"] = __("Некорректный email.", "ads-board");
        }

        if (
            $phone &&
            !preg_match(
                '/^(\+375|80)?[\s\-]?\(?\d{2}\)?[\s\-]?\d{3}[\s\-]?\d{2}[\s\-]?\d{2}$/',
                preg_replace("/[\s\-\(\)]/", "", $phone),
            )
        ) {
            $errors["author_phone"] =
                "Некорректный телефон. Пример: +375 (29) 123-45-67";
        }

        // 🔹 Категория
        $category_id = absint($data["category_id"] ?? 0);
        if (!$category_id) {
            $errors["category_id"] = __("Выберите категорию.", "ads-board");
        }

        // 🔹 Даты
        $published_at = $data["published_at"] ?? "";
        $expires_at = $data["expires_at"] ?? "";

        if ($published_at && !strtotime($published_at)) {
            $errors["published_at"] = __(
                "Некорректная дата публикации.",
                "ads-board",
            );
        }
        if ($expires_at && !strtotime($expires_at)) {
            $errors["expires_at"] = __(
                "Некорректная дата окончания.",
                "ads-board",
            );
        }
        if (
            $published_at &&
            $expires_at &&
            strtotime($expires_at) <= strtotime($published_at)
        ) {
            $errors["expires_at"] = __(
                "Дата окончания должна быть позже даты публикации.",
                "ads-board",
            );
        }

        // 🔹 Флаги
        $is_pinned = !empty($data["is_pinned"]) ? 1 : 0;
        $is_important = !empty($data["is_important"]) ? 1 : 0;

        // 🔹 Статус
        $status = in_array(
            $data["status"] ?? "draft",
            ["draft", "active", "sold"],
            true,
        )
            ? $data["status"]
            : "draft";

        // 🔹 Возвращаем ВСЕ поля в sanitized, даже если есть ошибки
        // Это нужно, чтобы форма не очищалась при валидации
        $sanitized = [
            "title" => sanitize_text_field($title),
            "slug" => $slug, // ← всегда определён
            "description" => wp_kses($description, $allowed_html),
            "price" => $price !== "" ? (float) $price : null,
            "author_name" => sanitize_text_field($author_name),
            "author_phone" => sanitize_text_field($phone),
            "author_email" => sanitize_email($email),
            "category_id" => $category_id,
            "status" => $status,
            "is_pinned" => $is_pinned,
            "is_important" => $is_important,
            "published_at" => $published_at
                ? date("Y-m-d H:i:s", strtotime($published_at))
                : null,
            "expires_at" => $expires_at
                ? date("Y-m-d H:i:s", strtotime($expires_at))
                : null,
        ];

        return [
            "valid" => empty($errors),
            "errors" => $errors,
            "sanitized" => $sanitized,
        ];
    }

    /**
     * Создание объявления
     */
    public function create_item($data)
    {
        $validation = $this->validate_item_data($data);

        if (!$validation["valid"]) {
            return ["success" => false, "errors" => $validation["errors"]];
        }

        global $wpdb;
        $sanitized = $validation["sanitized"];
        $sanitized["created_at"] = current_time("mysql");

        $result = $wpdb->insert($this->table_ads, $sanitized);

        if ($result === false) {
            return [
                "success" => false,
                "errors" => [
                    "db" => "Ошибка базы данных: " . $wpdb->last_error,
                ],
            ];
        }

        $ad_id = $wpdb->insert_id;

        // Обработка загрузки изображений
        $upload_result = $this->uploader->handle_upload();

        if ($upload_result["success"] && !empty($upload_result["files"])) {
            foreach ($upload_result["files"] as $i => $file) {
                $img_result = $wpdb->insert($this->table_images, [
                    "ad_id" => $ad_id,
                    "file_path" => $file["file_path"],
                    "file_name" => $file["file_name"],
                    "is_primary" => $i === 0 ? 1 : 0,
                    "sort_order" => $i,
                    "created_at" => current_time("mysql"),
                ]);
            }
        }

        return [
            "success" => true,
            "id" => $ad_id,
            "message" => "Объявление создано.",
        ];
    }

    /**
     * Обновление объявления
     */
    public function update_item($id, $data)
    {
        $id = absint($id);
        $validation = $this->validate_item_data($data, true);
        if (!$validation["valid"]) {
            return ["success" => false, "errors" => $validation["errors"]];
        }

        global $wpdb;
        $sanitized = $validation["sanitized"];

        $result = $wpdb->update($this->table_ads, $sanitized, ["id" => $id]);

        if ($result === false) {
            return [
                "success" => false,
                "errors" => ["db" => __("Ошибка базы данных.", "ads-board")],
            ];
        }

        // 🖼️ Обработка новых изображений
        $upload_result = $this->uploader->handle_upload();
        if ($upload_result["success"] && !empty($upload_result["files"])) {
            // Получаем текущий максимум sort_order
            $max_order =
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT MAX(sort_order) FROM {$this->table_images} WHERE ad_id = %d",
                        $id,
                    ),
                ) ?? -1;

            foreach ($upload_result["files"] as $i => $file) {
                $wpdb->insert($this->table_images, [
                    "ad_id" => $id,
                    "file_path" => $file["file_path"],
                    "file_name" => $file["file_name"],
                    "is_primary" => 0, // Новые — не главные по умолчанию
                    "sort_order" => $max_order + $i + 1,
                    "created_at" => current_time("mysql"),
                ]);
            }
        }

        return [
            "success" => true,
            "message" => __("Объявление обновлено.", "ads-board"),
        ];
    }

    /**
     * Удаление объявления с изображениями
     */
    public function delete_item($id)
    {
        $id = absint($id);
        global $wpdb;

        // Получаем пути к файлам перед удалением
        $images = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT file_path FROM {$this->table_images} WHERE ad_id = %d",
                $id,
            ),
        );

        // Удаляем изображения из БД
        $wpdb->delete($this->table_images, ["ad_id" => $id]);

        // Удаляем файлы с диска
        foreach ($images as $img) {
            $this->uploader->delete_file($img->file_path);
        }

        // Удаляем объявление
        $result = $wpdb->delete($this->table_ads, ["id" => $id]);

        return $result
            ? [
                "success" => true,
                "message" => __("Объявление удалено.", "ads-board"),
            ]
            : [
                "success" => false,
                "errors" => ["db" => __("Ошибка при удалении.", "ads-board")],
            ];
    }

    /**
     * Массовые действия
     */
    public function bulk_action($action, $ids)
    {
        if (empty($ids)) {
            return [
                "success" => false,
                "errors" => [
                    "empty" => __("Выберите объявления.", "ads-board"),
                ],
            ];
        }

        $ids = array_map("absint", $ids);
        $format = implode(",", array_fill(0, count($ids), "%d"));
        global $wpdb;

        switch ($action) {
            case "delete":
                foreach ($ids as $id) {
                    $this->delete_item($id);
                }
                return [
                    "success" => true,
                    "message" => sprintf(
                        _n(
                            "Удалено %d объявление",
                            "Удалено %d объявлений",
                            count($ids),
                            "ads-board",
                        ),
                        count($ids),
                    ),
                ];

            case "activate":
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->table_ads} SET status = 'active' WHERE id IN ($format)",
                        $ids,
                    ),
                );
                return [
                    "success" => true,
                    "message" => __("Объявления активированы.", "ads-board"),
                ];

            case "deactivate":
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->table_ads} SET status = 'draft' WHERE id IN ($format)",
                        $ids,
                    ),
                );
                return [
                    "success" => true,
                    "message" => __(
                        "Объявления сняты с публикации.",
                        "ads-board",
                    ),
                ];

            case "pin":
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$this->table_ads} SET is_pinned = 1 WHERE id IN ($format)",
                        $ids,
                    ),
                );
                return [
                    "success" => true,
                    "message" => __("Объявления закреплены.", "ads-board"),
                ];

            default:
                return [
                    "success" => false,
                    "errors" => [
                        "action" => __("Неизвестное действие.", "ads-board"),
                    ],
                ];
        }
    }

    /**
     * Обработка действий (публичный метод для контроллера)
     */
    public function handle_action($action, $data = [])
    {
        check_admin_referer("ads_items_nonce", "ads_items_nonce_field");

        if (!current_user_can("manage_options")) {
            return [
                "success" => false,
                "errors" => ["permission" => __("Нет прав.", "ads-board")],
            ];
        }

        switch ($action) {
            case "create":
                return $this->create_item($data);
            case "update":
                return $this->update_item($data["id"] ?? 0, $data);
            case "delete":
                return $this->delete_item($data["id"] ?? 0);
            case "bulk":
                return $this->bulk_action(
                    $data["bulk_action"] ?? "",
                    $data["ids"] ?? [],
                );
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
