<?php
/**
 * Удаление плагина
 *
 * Этот файл выполняется при полном удалении плагина через WordPress
 *
 * @package Ads_Board
 */

if (!defined("WP_UNINSTALL_PLUGIN")) {
    exit();
}

require_once plugin_dir_path(__FILE__) .
    "includes/class-ads-board-database.php";

function ads_board_uninstall()
{
    global $wpdb;

    $database = new Ads_Board_Database();

    // Удаляем все таблицы
    $database->drop_tables();

    // Удаляем все опции плагина
    delete_option("ads_board_db_version");
    delete_option("ads_board_activated_time");
    delete_option("ads_board_activation_log");
    delete_option("ads_board_deactivation_log");

    // Удаляем все transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_ads_board_%'
            OR option_name LIKE '_transient_timeout_ads_board_%'",
    );

    // Удаляем папку с загруженными изображениями
    $upload_dir = wp_upload_dir();
    $ads_upload_dir = $upload_dir["basedir"] . "/ads-board";

    if (is_dir($ads_upload_dir)) {
        ads_board_delete_directory($ads_upload_dir);
    }

    // Очищаем rewrite rules
    flush_rewrite_rules();
}

function ads_board_delete_directory($dir)
{
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == "." || $item == "..") {
            continue;
        }

        if (!ads_board_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// ads_board_uninstall();
