<?php
/*
 * @package Ads_Board
 * @author Vladislav Chekaviy
 * @link https://github.com/rekvizitt
 *
 * Plugin Name: Ads Board
 * Description: This plugin creates a great ads board system. ;)
 * Version: 0.0.1
 *
 */

if (!defined("ABSPATH")) {
    exit();
}

define("ADS_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("ADS_PLUGIN_URL", plugin_dir_url(__FILE__));

function activate_ads()
{
    require_once ADS_PLUGIN_DIR . "includes/class-ads-activator.php";
    Ads_Activator::activate();
    if (class_exists("Ads_Router")) {
        $router = new Ads_Router();
        $router->flush();
    }
}

function deactivate_ads()
{
    require_once ADS_PLUGIN_DIR . "includes/class-ads-deactivator.php";
    Ads_Deactivator::deactivate();
}

register_activation_hook(__FILE__, "activate_ads");
register_deactivation_hook(__FILE__, "deactivate_ads");

require plugin_dir_path(__FILE__) . "includes/class-ads.php";

function run_ads()
{
    $plugin = new Ads();
    $plugin->run();
}
run_ads();
