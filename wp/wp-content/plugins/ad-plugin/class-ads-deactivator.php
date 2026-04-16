<?php
/**
 * Класс деактивации плагина
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Deactivator
{
    public static function deactivate()
    {
        // Flush rewrite rules для очистки пользовательских URL
        flush_rewrite_rules();

        // Очищаем запланированные события (если будут)
        self::clear_scheduled_events();

        // Очищаем временные данные (transients)
        self::clear_transients();

        // Логируем деактивацию
        self::log_deactivation();

        // ВАЖНО: НЕ удаляем таблицы и данные при деактивации
        // Данные должны сохраняться согласно требованиям
    }
    private static function clear_scheduled_events()
    {
        // Если в будущем добавим cron задачи
        $timestamp = wp_next_scheduled("ads_board_cleanup_expired");
        if ($timestamp) {
            wp_unschedule_event($timestamp, "ads_board_cleanup_expired");
        }
    }
    private static function clear_transients()
    {
        global $wpdb;

        // Удаляем все transients плагина
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_ads_board_%'
                OR option_name LIKE '_transient_timeout_ads_board_%'",
        );
    }
    private static function log_deactivation()
    {
        $log_data = [
            "plugin" => "Ads Board",
            "version" => ADS_BOARD_VERSION,
            "deactivated_at" => current_time("mysql"),
            "site_url" => get_site_url(),
        ];

        update_option("ads_board_deactivation_log", $log_data);
    }
}
