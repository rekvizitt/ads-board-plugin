<?php
/**
 * Класс активации плагина
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Activator
{
    public static function activate()
    {
        // Подключаем класс для работы с БД
        require_once ADS_BOARD_PLUGIN_DIR .
            "includes/class-ads-board-database.php";

        $database = new Ads_Board_Database();

        // Создаем таблицы
        $database->create_tables();

        // Добавляем настройки по умолчанию
        $database->insert_default_settings();

        // Опционально: добавляем тестовые категории (можно закомментировать для продакшена)
        $database->insert_sample_categories();

        // Сохраняем время активации
        update_option("ads_board_activated_time", current_time("mysql"));

        // Flush rewrite rules для работы пользовательских URL
        flush_rewrite_rules();

        // Логируем активацию
        self::log_activation();
    }
    private static function log_activation()
    {
        $log_data = [
            "plugin" => "Ads Board",
            "version" => ADS_BOARD_VERSION,
            "activated_at" => current_time("mysql"),
            "site_url" => get_site_url(),
            "wp_version" => get_bloginfo("version"),
        ];

        update_option("ads_board_activation_log", $log_data);
    }
}
