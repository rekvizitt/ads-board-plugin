<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/includes
 * @author     Vladislav Chekaviy
 */

class Ads_Deactivator
{
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
