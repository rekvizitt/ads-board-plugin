<?php
/**
 * Assets Manager for Ads Board
 * Handles enqueueing of CSS/JS for admin and frontend.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Assets
{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function register()
    {
        // Admin
        add_action("admin_enqueue_scripts", [$this, "enqueue_admin_assets"]);

        // Frontend
        add_action("wp_enqueue_scripts", [$this, "enqueue_public_assets"]);
    }

    public function enqueue_admin_assets($hook)
    {
        // Загружаем только на страницах плагина
        if (
            strpos($hook, "ads-board") === false &&
            strpos($hook, "ads-") === false
        ) {
            return;
        }

        // Стили
        wp_enqueue_style(
            $this->plugin_name . "-admin-vars",
            ADS_PLUGIN_URL . "admin/css/board-admin-variables.css",
            [],
            $this->version,
            "all",
        );
        wp_enqueue_style(
            $this->plugin_name . "-admin",
            ADS_PLUGIN_URL . "admin/css/board-admin.css",
            [$this->plugin_name . "-admin-vars"],
            $this->version,
            "all",
        );

        // Скрипты
        wp_enqueue_script(
            $this->plugin_name . "-admin",
            ADS_PLUGIN_URL . "admin/js/board-admin.js",
            ["jquery"],
            $this->version,
            true,
        );

        // Передача данных в JS
        wp_localize_script($this->plugin_name . "-admin", "adsAdmin", [
            "ajaxUrl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("ads_admin_nonce"),
            "i18n" => [
                "confirmDelete" => __("Удалить?", "ads-board"),
                "phoneRequired" => __("Укажите телефон или email", "ads-board"),
            ],
        ]);
    }

    public function enqueue_public_assets()
    {
        // Стили
        wp_enqueue_style(
            $this->plugin_name . "-public-vars",
            ADS_PLUGIN_URL . "public/css/board-public-variables.css",
            [],
            $this->version,
            "all",
        );
        wp_enqueue_style(
            $this->plugin_name . "-public",
            ADS_PLUGIN_URL . "public/css/board-public.css",
            [$this->plugin_name . "-public-vars"],
            $this->version,
            "all",
        );

        // Скрипты
        wp_enqueue_script(
            $this->plugin_name . "-public",
            ADS_PLUGIN_URL . "public/js/board-public.js",
            ["jquery"],
            $this->version,
            true,
        );

        // Передача данных в JS
        wp_localize_script($this->plugin_name . "-public", "adsPublic", [
            "ajaxUrl" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("ads_public_nonce"),
            "settings" => [
                "dateFormat" => function_exists("ads_get_setting")
                    ? ads_get_setting("date_format", "relative")
                    : "relative",
                "showViews" => function_exists("ads_get_setting")
                    ? ads_get_setting("show_views_count", 1)
                    : 1,
            ],
        ]);
    }
}
