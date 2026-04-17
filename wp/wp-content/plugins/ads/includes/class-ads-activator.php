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
        require_once ADS_PLUGIN_DIR . "includes/class-ads-database.php";
        Ads_Database::create_tables();
        Ads_Database::insert_default_settings();
        Ads_Database::insert_sample_categories();

        $router_path = ADS_PLUGIN_DIR . "includes/class-ads-router.php";
        if (file_exists($router_path)) {
            require_once $router_path;
            $router = new Ads_Router();
            $router->flush();
        }
    }
}
