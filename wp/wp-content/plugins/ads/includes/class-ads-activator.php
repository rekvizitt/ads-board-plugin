<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/includes
 * @author     Vladislav Chekaviy
 */

class Ads_Activator
{
    public static function activate()
    {
        // Создаём таблицы БД
        if (class_exists("Ads_Database")) {
            Ads_Database::create_tables();
            Ads_Database::insert_default_settings();
            Ads_Database::insert_sample_categories();
        }

        // // Регистрируем и сбрасываем rewrite rules
        // if (class_exists("Ads_Router")) {
        //     $router = new Ads_Router();
        //     $router->flush_rules();
        // } else {
        //     // Если класс ещё не подключён — подключаем вручную
        //     require_once ADS_PLUGIN_DIR . "includes/class-ads-router.php";
        //     $router = new Ads_Router();
        //     $router->flush_rules();
        // }
    }
}
