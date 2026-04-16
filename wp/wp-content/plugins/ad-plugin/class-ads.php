<?php
/**
 * Основной класс плагина
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class AdsBoard
{
    protected $loader;
    protected $plugin_name;
    protected $version;
    public function __construct()
    {
        $this->version = ADS_BOARD_VERSION;
        $this->plugin_name = "ads-board";

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    private function load_dependencies()
    {
        // Загрузчик хуков
        require_once ADS_BOARD_PLUGIN_DIR .
            "includes/class-ads-board-loader.php";

        // Интернационализация
        require_once ADS_BOARD_PLUGIN_DIR . "includes/class-ads-board-i18n.php";

        // Административная часть
        require_once ADS_BOARD_PLUGIN_DIR . "admin/class-ads-board-admin.php";

        // Публичная часть
        require_once ADS_BOARD_PLUGIN_DIR . "public/class-ads-board-public.php";

        // Работа с базой данных
        require_once ADS_BOARD_PLUGIN_DIR .
            "includes/class-ads-board-database.php";

        $this->loader = new Ads_Board_Loader();
    }
    private function define_admin_hooks()
    {
        $plugin_admin = new Ads_Board_Admin(
            $this->get_plugin_name(),
            $this->get_version(),
        );

        // Подключение стилей и скриптов
        $this->loader->add_action(
            "admin_enqueue_scripts",
            $plugin_admin,
            "enqueue_styles",
        );
        $this->loader->add_action(
            "admin_enqueue_scripts",
            $plugin_admin,
            "enqueue_scripts",
        );

        // Регистрация меню
        $this->loader->add_action(
            "admin_menu",
            $plugin_admin,
            "add_admin_menu",
        );
    }
    private function define_public_hooks()
    {
        $plugin_public = new Ads_Board_Public(
            $this->get_plugin_name(),
            $this->get_version(),
        );

        // Подключение стилей и скриптов
        $this->loader->add_action(
            "wp_enqueue_scripts",
            $plugin_public,
            "enqueue_styles",
        );
        $this->loader->add_action(
            "wp_enqueue_scripts",
            $plugin_public,
            "enqueue_scripts",
        );

        // Регистрация шорткодов
        $this->loader->add_action(
            "init",
            $plugin_public,
            "register_shortcodes",
        );

        // Регистрация rewrite rules
        $this->loader->add_action(
            "init",
            $plugin_public,
            "register_rewrite_rules",
        );
    }
    public function run()
    {
        $this->loader->run();
    }
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }
    public function get_version()
    {
        return $this->version;
    }
    public function get_loader()
    {
        return $this->loader;
    }
}
