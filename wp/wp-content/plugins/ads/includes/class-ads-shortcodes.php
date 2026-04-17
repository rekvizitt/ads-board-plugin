<?php
/**
 * Shortcodes Handler for Ads Board
 * Registers and renders plugin shortcodes on frontend pages/posts.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Shortcodes
{
    public function __construct()
    {
        add_shortcode("latest_ads", [$this, "render_latest_ads"]);
        add_action("wp_enqueue_scripts", [$this, "ensure_frontend_assets"]);
    }

    /**
     * Гарантирует подключение стилей перед рендером шорткода
     */
    public function ensure_frontend_assets()
    {
        if (!wp_style_is("ads-public", "enqueued")) {
            wp_enqueue_style(
                "ads-public",
                ADS_PLUGIN_URL . "public/css/ads-public.css",
                [],
                defined("ADS_PLUGIN_VERSION") ? ADS_PLUGIN_VERSION : "0.0.1",
                "all",
            );
        }
    }

    /**
     * Рендер шорткода [latest_ads]
     */
    public function render_latest_ads($atts)
    {
        $this->ensure_frontend_assets();
        // Парсинг и валидация атрибутов
        $atts = shortcode_atts(
            [
                "count" => 6,
                "category" => "",
                "sort" => "newest",
                "show_price" => "true",
                "columns" => 3,
                "class" => "",
            ],
            $atts,
            "latest_ads",
        );

        $count = max(1, min(50, absint($atts["count"])));
        $category_input = sanitize_text_field($atts["category"]);
        $sort = in_array(
            $atts["sort"],
            ["newest", "oldest", "price_asc", "price_desc"],
            true,
        )
            ? $atts["sort"]
            : "newest";
        $show_price = filter_var($atts["show_price"], FILTER_VALIDATE_BOOLEAN);
        $columns = in_array($atts["columns"], ["1", "2", "3", "4"], true)
            ? $atts["columns"]
            : 3;
        $extra_class = sanitize_html_class($atts["class"]);

        // Загрузка зависимостей
        if (!class_exists("Ads_Frontend_Query")) {
            require_once ADS_PLUGIN_DIR .
                "includes/class-ads-frontend-query.php";
        }

        // Определение категории
        $category_id = 0;
        if (!empty($category_input)) {
            if (is_numeric($category_input)) {
                $category_id = absint($category_input);
            } else {
                global $wpdb;
                $table = $wpdb->prefix . "ads_categories";
                $category_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$table} WHERE slug = %s",
                        sanitize_title($category_input),
                    ),
                );
            }
        }

        // Запрос объявлений
        $query = new Ads_Frontend_Query();
        $result = $query->get_ads([
            "paged" => 1,
            "per_page" => $count,
            "orderby" => $sort,
            "status" => "active",
            "category" => $category_id,
        ]);

        if (empty($result["items"])) {
            return '<p class="ads-no-ads" style="text-align:center; color:#646970; padding:20px;">Объявления не найдены.</p>';
        }

        // Рендер через буфер
        ob_start();
        ?>
        <div class="ads-shortcode-wrapper <?php echo esc_attr(
            $extra_class,
        ); ?>">
            <div class="ads-grid" style="--grid-columns: <?php echo (int) $columns; ?>;">
                <?php
                // Переменные для шаблона карточки
                $date_format = function_exists("ads_get_setting")
                    ? ads_get_setting("date_format", "relative")
                    : "relative";

                foreach ($result["items"] as $ad):
                    $show_price = $show_price; // Передаём в карточку
                    include ADS_PLUGIN_DIR .
                        "public/templates/parts/card-ad.php";
                endforeach;
                ?>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}
