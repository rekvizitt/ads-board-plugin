<?php
/**
 * Административная часть плагина
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Admin
{
    private $plugin_name;
    private $version;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            ADS_BOARD_PLUGIN_URL . "assets/css/admin.css",
            [],
            $this->version,
            "all",
        );
    }
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            ADS_BOARD_PLUGIN_URL . "assets/js/admin.js",
            ["jquery"],
            $this->version,
            false,
        );
    }
    public function add_admin_menu()
    {
        // Главное меню
        add_menu_page(
            __("Доска объявлений", "ads-board"), // Page title
            __("Доска объявлений", "ads-board"), // Menu title
            "manage_options", // Capability
            "ads-board", // Menu slug
            [$this, "display_ads_page"], // Callback
            "dashicons-megaphone", // Icon
            30, // Position
        );

        // Подменю - Все объявления (дублирует главную)
        add_submenu_page(
            "ads-board",
            __("Все объявления", "ads-board"),
            __("Все объявления", "ads-board"),
            "manage_options",
            "ads-board",
            [$this, "display_ads_page"],
        );

        // Подменю - Добавить объявление
        add_submenu_page(
            "ads-board",
            __("Добавить объявление", "ads-board"),
            __("Добавить объявление", "ads-board"),
            "manage_options",
            "ads-board-add",
            [$this, "display_add_ad_page"],
        );

        // Подменю - Категории
        add_submenu_page(
            "ads-board",
            __("Категории", "ads-board"),
            __("Категории", "ads-board"),
            "manage_options",
            "ads-board-categories",
            [$this, "display_categories_page"],
        );

        // Подменю - Настройки
        add_submenu_page(
            "ads-board",
            __("Настройки", "ads-board"),
            __("Настройки", "ads-board"),
            "manage_options",
            "ads-board-settings",
            [$this, "display_settings_page"],
        );
    }
    public function display_ads_page()
    {
        require_once ADS_BOARD_PLUGIN_DIR . "admin/views/ads-list.php";
    }
    public function display_add_ad_page()
    {
        require_once ADS_BOARD_PLUGIN_DIR . "admin/views/ad-form.php";
    }
    public function display_categories_page()
    {
        require_once ADS_BOARD_PLUGIN_DIR . "admin/views/categories-list.php";
    }
    public function display_settings_page()
    {
        require_once ADS_BOARD_PLUGIN_DIR . "admin/views/settings.php";
    }
}
