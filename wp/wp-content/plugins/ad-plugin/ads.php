<?php
/**
 * @package Ads_Board
 * @version 0.0.1
 */
/*
Plugin Name: Ads board
Description: Plugin that adds ads board to your wordpress website.
Author: Vladislav Chekaviy
Version: 0.0.1
Author URI: http://github.com/rekvizitt
Text Domain: ads-board-plugin
*/

if (!defined("ABSPATH")) {
    exit();
}

define("ADS_BOARD_VERSION", "1.0.0");
define("ADS_BOARD_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("ADS_BOARD_PLUGIN_URL", plugin_dir_url(__FILE__));
define("ADS_BOARD_PLUGIN_BASENAME", plugin_basename(__FILE__));

require_once ADS_BOARD_PLUGIN_DIR . "includes/class-ads-board.php";

function activate_ads_board()
{
    require_once ADS_BOARD_PLUGIN_DIR .
        "includes/class-ads-board-activator.php";
    Ads_Board_Activator::activate();
}

function deactivate_ads_board()
{
    require_once ADS_BOARD_PLUGIN_DIR .
        "includes/class-ads-board-deactivator.php";
    Ads_Board_Deactivator::deactivate();
}

register_activation_hook(__FILE__, "activate_ads_board");
register_deactivation_hook(__FILE__, "deactivate_ads_board");

function run_ads_board()
{
    $plugin = new Ads_Board();
    $plugin->run();
}

run_ads_board();
