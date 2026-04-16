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

function activate_ads()
{
    require_once plugin_dir_path(__FILE__) . "includes/class-ads-activator.php";
    Ads_Activator::activate();
}

function deactivate_ads()
{
    require_once plugin_dir_path(__FILE__) .
        "includes/class-ads-deactivator.php";
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
