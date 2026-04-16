<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/admin
 * @author     Vladislav Chekaviy
 */

class Ads_Admin
{
    private $plugin_name;
    private $version;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    public function enqueue_styles() {}
    public function enqueue_scripts() {}
}
