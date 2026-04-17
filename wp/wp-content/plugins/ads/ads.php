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
}

function deactivate_ads()
{
    require_once ADS_PLUGIN_DIR . "includes/class-ads-deactivator.php";
    Ads_Deactivator::deactivate();
}

register_activation_hook(__FILE__, "activate_ads");
register_deactivation_hook(__FILE__, "deactivate_ads");

require plugin_dir_path(__FILE__) . "includes/class-ads.php";

// 🔥 Прямая регистрация с add_rewrite_tag
add_action(
    "init",
    function () {
        // ✅ Сначала теги!
        add_rewrite_tag("%ads_test%", "([^&]+)");
        // ✅ Потом правило
        add_rewrite_rule('^board-direct/?$', "index.php?ads_test=works", "top");
    },
    1,
);

add_filter("query_vars", function ($vars) {
    $vars[] = "ads_test";
    return $vars;
});

add_action("template_redirect", function () {
    if (get_query_var("ads_test") === "works") {
        echo '<div style="max-width:600px;margin:100px auto;padding:30px;background:#dff0d8;border:1px solid #d0e9c6;border-radius:4px;text-align:center;">
            <h2 style="color:#3c763d;">✅ Rewrite + Query Vars работают!</h2>
            <p>Теперь /board/ должен заработать после сброса правил.</p>
        </div>';
        exit();
    }
});

function run_ads()
{
    $plugin = new Ads();
    $plugin->run();
}
run_ads();
