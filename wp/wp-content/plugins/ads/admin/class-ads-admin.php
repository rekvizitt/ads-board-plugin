<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/admin
 * @author     Vladislav Chekaviy
 */

class Ads_Admin
{
    private $plugin_name;
    private $version;
    private $categories_page_hook;
    private $add_new_page_hook;
    private $settings_page_hook;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name . "-admin",
            ADS_PLUGIN_URL . "admin/css/ads-admin.css",
            [],
            $this->version,
            "all",
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name . "-admin",
            ADS_PLUGIN_URL . "admin/js/ads-admin.js",
            ["jquery"],
            $this->version,
            true,
        );

        // Передаём данные в JS (локализация)
        wp_localize_script($this->plugin_name . "-admin", "adsBoardAdmin", [
            "i18n" => [
                "bulkDeleteConfirm" => __(
                    "Вы действительно хотите удалить %d объявлений?",
                    "ads-board",
                ),
            ],
        ]);
    }

    public function register_admin_menu()
    {
        // Главное меню
        add_menu_page(
            "Доска объявлений", // Заголовок страницы
            "Объявления", // Текст меню
            "manage_options", // Права доступа
            "ads-board", // Slug меню
            [$this, "ads_render_main_page"], // Функция отображения
            "dashicons-megaphone", // Иконка
            25, // Позиция в меню
        );

        // Подменю: Все объявления
        add_submenu_page(
            "ads-board", // Родительское меню
            "Все объявления", // Заголовок страницы
            "Все объявления", // Текст меню
            "manage_options", // Права доступа
            "ads-board", // Slug (совпадает с главным)
            [$this, "ads_render_main_page"], // Функция отображения
        );

        // Подменю: Добавить объявление
        $this->add_new_page_hook = add_submenu_page(
            "ads-board",
            "Добавить объявление",
            "Добавить объявление",
            "manage_options",
            "ads-add-new",
            [$this, "ads_render_add_new_page"],
        );
        add_action("load-{$this->add_new_page_hook}", [
            $this,
            "process_items_actions",
        ]);

        // Подменю: Категории
        $this->categories_page_hook = add_submenu_page(
            "ads-board",
            "Категории объявлений",
            "Категории",
            "manage_options",
            "ads-categories",
            [$this, "ads_render_categories_page"],
        );
        add_action("load-{$this->categories_page_hook}", [
            $this,
            "process_categories_actions",
        ]);

        // Подменю: Настройки
        $this->settings_page_hook = add_submenu_page(
            "ads-board",
            "Настройки доски объявлений",
            "Настройки",
            "manage_options",
            "ads-settings",
            [$this, "ads_render_settings_page"],
        );

        // Регистрация настроек
        add_action("admin_init", [$this, "register_plugin_settings"]);

        // AJAX handler для сброса
        add_action("wp_ajax_ads_reset_settings", [
            $this,
            "handle_reset_settings",
        ]);
    }

    function ads_render_main_page()
    {
        // Подключаем контроллер
        if (!class_exists("Ads_List_Table")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-list-table.php";
        }
        if (!class_exists("Ads_Helpers")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-helpers.php";
        }

        $controller = new Ads_List_Table();

        // Обработка одиночных действий (удаление и т.п.)
        if (
            isset($_GET["action"], $_GET["ad_id"]) &&
            in_array($_GET["action"], ["delete", "activate", "deactivate"])
        ) {
            $result = $controller->handle_single_action(
                $_GET["action"],
                $_GET["ad_id"],
            );
            if ($result) {
                add_settings_error(
                    "ads_board",
                    "ads_board_action",
                    $result["message"],
                    $result["type"],
                );
                settings_errors("ads_board");
            }
        }

        // Обработка массовых действий
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $result = $controller->handle_bulk_actions();
            if ($result) {
                add_settings_error(
                    "ads_board",
                    "ads_board_action",
                    $result["message"],
                    $result["type"],
                );
                settings_errors("ads_board");
            }
        }

        // Подготовка данных
        $filters = $controller->get_filters();
        $list_data = $controller->get_items($filters);
        $categories = $controller->get_categories_list();

        // Базовый URL для пагинации
        $base_url =
            admin_url("admin.php?page=ads-board") .
            (count(
                array_filter([
                    $filters["search"],
                    $filters["status"] !== "all",
                    $filters["category"],
                ]),
            )
                ? "&"
                : "") .
            http_build_query(
                array_filter(
                    [
                        "s" => $filters["search"] ?: null,
                        "status" =>
                            $filters["status"] !== "all"
                                ? $filters["status"]
                                : null,
                        "category" => $filters["category"] ?: null,
                        "paged" => "%#%",
                    ],
                    function ($v) {
                        return $v !== null;
                    },
                ),
            );
        $base_url = str_replace("%23%23%23", "%#%", $base_url); // хак для paginate_links

        // Передаём данные в шаблон
        $this->load_template("main-page", [
            "data" => [
                "items" => $list_data["items"],
                "total_items" => $list_data["total_items"],
                "total_pages" => $list_data["total_pages"],
                "current_page" => $list_data["current_page"],
                "filters" => $filters,
                "categories" => $categories,
                "base_url" => $base_url,
            ],
        ]);
    }

    function ads_render_add_new_page()
    {
        if (!current_user_can("manage_options")) {
            wp_die(__("Нет прав.", "ads-board"));
        }

        if (!class_exists("Ads_Items_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-items-controller.php";
        }
        $controller = new Ads_Items_Controller();

        // Читаем ошибки/ввод из transient
        $errors = get_transient("ads_items_form_errors") ?: [];
        $old_input = get_transient("ads_items_form_old") ?: [];
        delete_transient("ads_items_form_errors");
        delete_transient("ads_items_form_old");

        // Режим редактирования
        $item = null;
        $form_action = "create";

        if (isset($_GET["edit"])) {
            $item = $controller->get_item(absint($_GET["edit"]));
            if ($item) {
                $form_action = "update";
            } else {
                add_settings_error(
                    "ads_items",
                    "not_found",
                    __("Объявление не найдено.", "ads-board"),
                    "error",
                );
            }
        }

        $categories = $controller->get_categories_list();

        $this->load_template("add-new", [
            "data" => [
                "item" => $item,
                "categories" => $categories,
                "errors" => $errors,
                "old_input" => $old_input,
                "form_action" => $form_action,
            ],
        ]);
    }

    /**
     * Рендер страницы категорий
     */
    function ads_render_categories_page()
    {
        // 🔐 Проверка прав
        if (!current_user_can("manage_options")) {
            wp_die(__("Нет прав.", "ads-board"));
        }

        // 📦 Контроллер
        if (!class_exists("Ads_Categories_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-categories-controller.php";
        }
        $controller = new Ads_Categories_Controller();

        // 📥 Читаем ошибки/ввод из transient (если были при валидации)
        $errors = get_transient("ads_categories_errors") ?: [];
        $old_input = get_transient("ads_categories_old_input") ?: [];
        delete_transient("ads_categories_errors");
        delete_transient("ads_categories_old_input");

        // 📢 Уведомление из редиректа
        $notice = null;
        if (isset($_GET["ads_notice"])) {
            $type = sanitize_key($_GET["ads_notice"]);
            $notice = [
                "type" => $type === "success" ? "updated" : "error",
                "msg" =>
                    $type === "success"
                        ? __("Действие выполнено.", "ads-board")
                        : __("Произошла ошибка.", "ads-board"),
            ];
        }

        // 🔍 Данные для таблицы
        $search = isset($_GET["s"])
            ? sanitize_text_field(wp_unslash($_GET["s"]))
            : "";
        $paged = max(1, isset($_GET["paged"]) ? absint($_GET["paged"]) : 1);

        $list_data = $controller->get_categories([
            "search" => $search,
            "paged" => $paged,
            "per_page" => 20,
        ]);

        // ✏️ Режим редактирования
        $edit_item = null;
        if (isset($_GET["edit"]) && empty($errors)) {
            $edit_item = $controller->get_category(absint($_GET["edit"]));
            if (!$edit_item) {
                $notice = [
                    "type" => "error",
                    "msg" => __("Категория не найдена.", "ads-board"),
                ];
            }
        }

        $base_url =
            admin_url("admin.php?page=ads-categories") .
            ($search ? "&s=" . urlencode($search) : "") .
            "&paged=%#%";

        // 🎨 Шаблон
        $this->load_template("categories", [
            "data" => [
                "items" => $list_data["items"],
                "total_items" => $list_data["total_items"],
                "total_pages" => $list_data["total_pages"],
                "current_page" => $list_data["current_page"],
                "search" => $search,
                "edit_item" => $edit_item,
                "errors" => $errors,
                "old_input" => $old_input,
                "notice" => $notice,
                "base_url" => $base_url,
            ],
        ]);
    }

    /**
     * Рендер страницы настроек
     */
    public function ads_render_settings_page()
    {
        if (!current_user_can("manage_options")) {
            wp_die("Нет прав для доступа к этой странице.");
        }

        // Обработка сообщения об успешном сохранении
        if (
            isset($_GET["settings-updated"]) &&
            $_GET["settings-updated"] === "true"
        ) {
            add_settings_error(
                "ads_board_settings",
                "settings_saved",
                "Настройки сохранены.",
                "updated",
            );
        }

        $this->load_template("settings");
    }

    function load_template($template_name, $variables = [])
    {
        if (!empty($variables)) {
            extract($variables);
        }
        $template_path =
            ADS_PLUGIN_DIR . "admin/templates/" . $template_name . ".php";
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>Шаблон не найден: ' .
                esc_html($template_name) .
                "</h1></div>";
        }
    }

    /**
     * Обработка действий с категориями (выполняется ДО любого вывода)
     * Хук: load-{$page_hook}
     */
    public function process_categories_actions()
    {
        // 🔐 Проверка прав
        if (!current_user_can("manage_options")) {
            return;
        }

        // 📦 Подгрузка контроллера
        if (!class_exists("Ads_Categories_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-categories-controller.php";
        }
        $controller = new Ads_Categories_Controller();

        // 🔄 Обработка POST (Создание / Редактирование)
        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["ads_action"], $_POST["ads_categories_nonce_field"])
        ) {
            if (
                !wp_verify_nonce(
                    $_POST["ads_categories_nonce_field"],
                    "ads_categories_nonce",
                )
            ) {
                add_settings_error(
                    "ads_categories",
                    "nonce_error",
                    __("Ошибка безопасности.", "ads-board"),
                    "error",
                );
                return;
            }

            $action = sanitize_text_field($_POST["ads_action"]);
            $result = $controller->handle_action($action, $_POST);

            if ($result["success"]) {
                // ✅ Редирект с сообщением — теперь БЕЗОПАСНО, т.к. вывода ещё не было!
                wp_safe_redirect(
                    admin_url(
                        "admin.php?page=ads-categories&ads_notice=success",
                    ),
                );
                exit();
            } else {
                // ❌ Ошибка: сохраняем в transient для отображения в шаблоне
                set_transient("ads_categories_errors", $result["errors"], 30);
                set_transient("ads_categories_old_input", $_POST, 30);
                wp_safe_redirect(
                    admin_url("admin.php?page=ads-categories&ads_notice=error"),
                );
                exit();
            }
        }

        // 🗑️ Обработка GET (Удаление)
        if (
            isset(
                $_GET["action"],
                $_GET["id"],
                $_GET["ads_categories_nonce_field"],
            ) &&
            $_GET["action"] === "delete"
        ) {
            if (
                !wp_verify_nonce(
                    $_GET["ads_categories_nonce_field"],
                    "ads_categories_nonce",
                )
            ) {
                add_settings_error(
                    "ads_categories",
                    "nonce_error",
                    __("Ошибка безопасности.", "ads-board"),
                    "error",
                );
                return;
            }

            $result = $controller->handle_action("delete", [
                "id" => $_GET["id"],
            ]);
            $notice = $result["success"] ? "success" : "error";

            wp_safe_redirect(
                admin_url(
                    "admin.php?page=ads-categories&ads_notice=" . $notice,
                ),
            );
            exit();
        }
    }

    /**
     * Обработка действий с объявлениями (load-{$page_hook})
     */
    public function process_items_actions()
    {
        if (!current_user_can("manage_options")) {
            return;
        }

        if (!class_exists("Ads_Items_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-items-controller.php";
        }
        if (!class_exists("Ads_File_Uploader")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-file-uploader.php";
        }

        $controller = new Ads_Items_Controller();

        // 🔄 POST: Создание/Редактирование
        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["ads_action"], $_POST["ads_items_nonce_field"])
        ) {
            if (
                !wp_verify_nonce(
                    $_POST["ads_items_nonce_field"],
                    "ads_items_nonce",
                )
            ) {
                wp_die(__("Ошибка безопасности.", "ads-board"));
            }

            $action = sanitize_text_field($_POST["ads_action"]);
            $result = $controller->handle_action($action, $_POST);

            if ($result["success"]) {
                wp_safe_redirect(
                    admin_url("admin.php?page=ads-board&ads_notice=success"),
                );
                exit();
            } else {
                // Сохраняем ошибки для отображения в форме
                set_transient("ads_items_form_errors", $result["errors"], 30);
                set_transient(
                    "ads_items_form_old",
                    $result["sanitized"] ?? [],
                    30,
                );

                $redirect_url = admin_url("admin.php?page=ads-add-new");
                if (!empty($_POST["id"])) {
                    $redirect_url .= "&edit=" . absint($_POST["id"]);
                }
                wp_safe_redirect($redirect_url);
                exit();
            }
        }

        // 🗑️ GET: Удаление изображения
        if (
            isset($_GET["action"], $_GET["img_id"], $_GET["ad_id"]) &&
            $_GET["action"] === "delete_image"
        ) {
            if (
                !wp_verify_nonce(
                    $_GET["ads_items_nonce_field"] ?? "",
                    "ads_items_nonce",
                )
            ) {
                wp_die(__("Ошибка безопасности.", "ads-board"));
            }

            $uploader = new Ads_File_Uploader();
            $uploader->delete_image(
                absint($_GET["img_id"]),
                absint($_GET["ad_id"]),
            );

            wp_safe_redirect(
                admin_url(
                    "admin.php?page=ads-add-new&edit=" .
                        absint($_GET["ad_id"]) .
                        "&ads_notice=success",
                ),
            );
            exit();
        }

        // ⭐ GET: Установка главного изображения
        if (
            isset($_GET["action"], $_GET["img_id"], $_GET["ad_id"]) &&
            $_GET["action"] === "set_primary"
        ) {
            if (
                !wp_verify_nonce(
                    $_GET["ads_items_nonce_field"] ?? "",
                    "ads_items_nonce",
                )
            ) {
                wp_die(__("Ошибка безопасности.", "ads-board"));
            }

            $uploader = new Ads_File_Uploader();
            $uploader->set_primary_image(
                absint($_GET["ad_id"]),
                absint($_GET["img_id"]),
            );

            wp_safe_redirect(
                admin_url(
                    "admin.php?page=ads-add-new&edit=" .
                        absint($_GET["ad_id"]) .
                        "&ads_notice=success",
                ),
            );
            exit();
        }
    }

    /**
     * Регистрация настроек плагина
     */
    public function register_plugin_settings()
    {
        if (!class_exists("Ads_Settings_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-settings-controller.php";
        }

        $controller = new Ads_Settings_Controller();
        $controller->register_settings();
    }

    /**
     * AJAX handler для сброса настроек
     */
    public function handle_reset_settings()
    {
        if (!class_exists("Ads_Settings_Controller")) {
            require_once ADS_PLUGIN_DIR .
                "admin/includes/class-ads-settings-controller.php";
        }

        $controller = new Ads_Settings_Controller();
        $controller->reset_settings();
    }

    // function classifieds_get_template_data($page)
    // {
    //     global $wpdb;
    //     $data = [];

    //     switch ($page) {
    //         case "main":
    //             $table_name = $wpdb->prefix . "classifieds";
    //             $data["classifieds"] = $wpdb->get_results(
    //                 "SELECT * FROM $table_name ORDER BY created_at DESC",
    //             );
    //             break;

    //         case "categories":
    //             $table_name = $wpdb->prefix . "classifieds_categories";
    //             $data["categories"] = $wpdb->get_results(
    //                 "SELECT * FROM $table_name ORDER BY name ASC",
    //             );
    //             break;
    //     }

    //     return $data;
    // }
}
