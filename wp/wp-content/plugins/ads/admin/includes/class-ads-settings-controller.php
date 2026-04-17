<?php
/**
 * Settings Controller for Ads Board
 * Handles plugin settings registration, validation, and saving via Settings API.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Settings_Controller
{
    private $option_group = "ads_board_settings";
    private $option_name = "ads_board_options";
    private $sections = [];

    public function __construct()
    {
        $this->register_sections();
    }

    /**
     * Регистрация настроек — ВЫЗЫВАТЬ НА ХУКЕ admin_init
     */
    public function register_settings()
    {
        // ✅ register_setting вызывается ОДИН РАЗ для всей группы опций
        register_setting($this->option_group, $this->option_name, [
            "sanitize_callback" => [$this, "sanitize_options"],
            "default" => $this->get_defaults(),
            "show_in_rest" => true,
        ]);

        // Регистрация секций и полей
        foreach ($this->sections as $section_id => $section) {
            add_settings_section(
                $section_id,
                $section["title"],
                $section["callback"] ?? null,
                "ads_board_settings_page",
            );

            foreach ($section["fields"] as $field_id => $field) {
                // ✅ НЕ вызывать register_setting здесь! Только add_settings_field
                add_settings_field(
                    $field_id,
                    $field["title"],
                    $field["callback"],
                    "ads_board_settings_page",
                    $section_id,
                    [
                        "label_for" => "ads_setting_{$field_id}",
                        "class" => $field["class"] ?? "",
                        "field_data" => $field,
                    ],
                );
            }
        }
    }

    private function register_sections()
    {
        $this->sections = [
            "general" => [
                "title" => "Общие настройки",
                "callback" => [$this, "section_general_description"],
                "fields" => [
                    "ads_per_page" => [
                        "title" => "Объявлений на странице",
                        "callback" => [$this, "render_number_field"],
                        "type" => "number",
                        "min" => 1,
                        "max" => 100,
                        "default" => 12,
                        "description" =>
                            "Количество объявлений в списке на фронтенде",
                    ],
                    "date_format" => [
                        "title" => "Формат даты",
                        "callback" => [$this, "render_select_field"],
                        "type" => "select",
                        "options" => [
                            "relative" => "Относительный (2 часа назад)",
                            "d.m.Y" => "Короткий (17.04.2026)",
                            "d.m.Y H:i" => "Полный (17.04.2026 14:30)",
                            "F j, Y" => "Месяц день, год (April 17, 2026)",
                        ],
                        "default" => "relative",
                        "description" =>
                            "Как отображать дату публикации объявлений",
                    ],
                ],
            ],
            "display" => [
                "title" => "Отображение",
                "callback" => [$this, "section_display_description"],
                "fields" => [
                    "show_views_count" => [
                        "title" => "Показывать просмотры",
                        "callback" => [$this, "render_checkbox_field"],
                        "type" => "checkbox",
                        "default" => 1,
                        "description" =>
                            "Отображать счётчик просмотров на карточке объявления",
                    ],
                    "show_author" => [
                        "title" => "Показывать автора",
                        "callback" => [$this, "render_checkbox_field"],
                        "type" => "checkbox",
                        "default" => 1,
                        "description" => "Отображать имя и контакты автора",
                    ],
                    "grid_columns" => [
                        "title" => "Колонок в сетке",
                        "callback" => [$this, "render_select_field"],
                        "type" => "select",
                        "options" => [
                            "2" => "2 колонки",
                            "3" => "3 колонки",
                            "4" => "4 колонки",
                        ],
                        "default" => "3",
                        "description" =>
                            "Количество колонок в списке объявлений на десктопе",
                    ],
                    "image_size" => [
                        "title" => "Размер изображения",
                        "callback" => [$this, "render_select_field"],
                        "type" => "select",
                        "options" => [
                            "thumbnail" => "Миниатюра (150×150)",
                            "medium" => "Средний (300×300)",
                            "large" => "Большой (1024×1024)",
                            "full" => "Оригинал",
                        ],
                        "default" => "medium",
                        "description" =>
                            "Размер изображения в списке объявлений",
                    ],
                ],
            ],
            "moderation" => [
                "title" => "Модерация",
                "callback" => [$this, "section_moderation_description"],
                "fields" => [
                    "require_moderation" => [
                        "title" => "Модерация новых объявлений",
                        "callback" => [$this, "render_checkbox_field"],
                        "type" => "checkbox",
                        "default" => 0,
                        "description" =>
                            "Новые объявления требуют одобрения перед публикацией",
                    ],
                    "auto_expire_days" => [
                        "title" => "Авто-истечение срока (дней)",
                        "callback" => [$this, "render_number_field"],
                        "type" => "number",
                        "min" => 1,
                        "max" => 365,
                        "default" => 30,
                        "description" =>
                            "Через сколько дней объявление автоматически снимается с публикации (0 = бессрочно)",
                    ],
                    "max_images_per_ad" => [
                        "title" => "Макс. изображений в объявлении",
                        "callback" => [$this, "render_number_field"],
                        "type" => "number",
                        "min" => 1,
                        "max" => 20,
                        "default" => 10,
                        "description" =>
                            "Лимит загружаемых изображений для одного объявления",
                    ],
                ],
            ],
            "advanced" => [
                "title" => "Дополнительно",
                "callback" => [$this, "section_advanced_description"],
                "fields" => [
                    "enable_schema" => [
                        "title" => "Schema.org разметка",
                        "callback" => [$this, "render_checkbox_field"],
                        "type" => "checkbox",
                        "default" => 1,
                        "description" =>
                            "Добавлять микроразметку для улучшения SEO",
                    ],
                    "enable_ajax_filters" => [
                        "title" => "AJAX-фильтрация",
                        "callback" => [$this, "render_checkbox_field"],
                        "type" => "checkbox",
                        "default" => 1,
                        "description" =>
                            "Фильтры работают без перезагрузки страницы",
                    ],
                    "reset_settings" => [
                        "title" => "Сброс настроек",
                        "callback" => [$this, "render_reset_button"],
                        "type" => "custom",
                        "description" =>
                            "Вернуть все настройки к значениям по умолчанию",
                    ],
                ],
            ],
        ];
    }

    // === Описание секций ===
    public function section_general_description()
    {
        echo '<p class="description">Основные параметры работы доски объявлений.</p>';
    }
    public function section_display_description()
    {
        echo '<p class="description">Настройки внешнего вида списка и карточек объявлений.</p>';
    }
    public function section_moderation_description()
    {
        echo '<p class="description">Параметры модерации и автоматического управления объявлениями.</p>';
    }
    public function section_advanced_description()
    {
        echo '<p class="description">Расширенные настройки для разработчиков и администраторов.</p>';
    }

    // === Рендер полей ===
    public function render_number_field($args)
    {
        $options = get_option($this->option_name, $this->get_defaults());
        $field_id = str_replace("ads_setting_", "", $args["label_for"]);
        $field_data = $args["field_data"];
        $value = $options[$field_id] ?? $field_data["default"];
        ?>
        <input type="number"
               id="<?php echo esc_attr($args["label_for"]); ?>"
               name="<?php echo esc_attr(
                   $this->option_name,
               ); ?>[<?php echo esc_attr($field_id); ?>]"
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($field_data["min"] ?? 0); ?>"
               max="<?php echo esc_attr($field_data["max"] ?? 999); ?>"
               class="small-text"
               style="padding: 5px 8px;">
        <?php if (!empty($field_data["description"])): ?>
            <p class="description"><?php echo esc_html(
                $field_data["description"],
            ); ?></p>
        <?php endif; ?>
        <?php
    }

    public function render_select_field($args)
    {
        $options = get_option($this->option_name, $this->get_defaults());
        $field_id = str_replace("ads_setting_", "", $args["label_for"]);
        $field_data = $args["field_data"];
        $value = $options[$field_id] ?? $field_data["default"];
        ?>
        <select id="<?php echo esc_attr($args["label_for"]); ?>"
                name="<?php echo esc_attr(
                    $this->option_name,
                ); ?>[<?php echo esc_attr($field_id); ?>]"
                style="padding: 5px 8px; min-width: 200px;">
            <?php foreach (
                $field_data["options"]
                as $opt_value => $opt_label
            ): ?>
                <option value="<?php echo esc_attr(
                    $opt_value,
                ); ?>" <?php selected($value, $opt_value); ?>>
                    <?php echo esc_html($opt_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($field_data["description"])): ?>
            <p class="description"><?php echo esc_html(
                $field_data["description"],
            ); ?></p>
        <?php endif; ?>
        <?php
    }

    public function render_checkbox_field($args)
    {
        $options = get_option($this->option_name, $this->get_defaults());
        $field_id = str_replace("ads_setting_", "", $args["label_for"]);
        $field_data = $args["field_data"];
        $value = $options[$field_id] ?? $field_data["default"];
        ?>
        <label>
            <input type="checkbox"
                   id="<?php echo esc_attr($args["label_for"]); ?>"
                   name="<?php echo esc_attr(
                       $this->option_name,
                   ); ?>[<?php echo esc_attr($field_id); ?>]"
                   value="1"
                   <?php checked($value, 1); ?>>
            <?php if (!empty($field_data["description"])): ?>
                <span class="description"><?php echo esc_html(
                    $field_data["description"],
                ); ?></span>
            <?php endif; ?>
        </label>
        <?php
    }

    public function render_reset_button($args)
    {
        ?>
        <button type="button"
                id="ads_reset_settings_btn"
                class="button button-secondary"
                style="background: #dc3232; border-color: #dc3232; color: #fff;">
            Сбросить к значениям по умолчанию
        </button>
        <p class="description">Внимание: это действие нельзя отменить.</p>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('ads_reset_settings_btn');
            if (btn) {
                btn.addEventListener('click', function() {
                    if (confirm('Вы уверены? Все настройки будут сброшены.')) {
                        const nonce = '<?php echo wp_create_nonce(
                            "ads_reset_settings_nonce",
                        ); ?>';
                        fetch(ajaxurl, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'action=ads_reset_settings&_wpnonce=' + nonce
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Ошибка: ' + (data.data?.message || 'Неизвестная ошибка'));
                            }
                        })
                        .catch(err => alert('Ошибка сети: ' + err.message));
                    }
                });
            }
        });
        </script>
        <?php
    }

    // === Санитизация ===
    public function sanitize_options($input)
    {
        $sanitized = [];
        $defaults = $this->get_defaults();

        $sanitized["ads_per_page"] = absint(
            $input["ads_per_page"] ?? $defaults["ads_per_page"],
        );
        $sanitized["ads_per_page"] = max(
            1,
            min(100, $sanitized["ads_per_page"]),
        );

        $allowed_date_formats = ["relative", "d.m.Y", "d.m.Y H:i", "F j, Y"];
        $sanitized["date_format"] = in_array(
            $input["date_format"] ?? "",
            $allowed_date_formats,
            true,
        )
            ? $input["date_format"]
            : $defaults["date_format"];

        $sanitized["show_views_count"] = !empty($input["show_views_count"])
            ? 1
            : 0;
        $sanitized["show_author"] = !empty($input["show_author"]) ? 1 : 0;

        $allowed_columns = ["2", "3", "4"];
        $sanitized["grid_columns"] = in_array(
            $input["grid_columns"] ?? "",
            $allowed_columns,
            true,
        )
            ? $input["grid_columns"]
            : $defaults["grid_columns"];

        $allowed_sizes = ["thumbnail", "medium", "large", "full"];
        $sanitized["image_size"] = in_array(
            $input["image_size"] ?? "",
            $allowed_sizes,
            true,
        )
            ? $input["image_size"]
            : $defaults["image_size"];

        $sanitized["require_moderation"] = !empty($input["require_moderation"])
            ? 1
            : 0;
        $sanitized["auto_expire_days"] = absint(
            $input["auto_expire_days"] ?? $defaults["auto_expire_days"],
        );
        $sanitized["auto_expire_days"] = max(
            0,
            min(365, $sanitized["auto_expire_days"]),
        );
        $sanitized["max_images_per_ad"] = absint(
            $input["max_images_per_ad"] ?? $defaults["max_images_per_ad"],
        );
        $sanitized["max_images_per_ad"] = max(
            1,
            min(20, $sanitized["max_images_per_ad"]),
        );

        $sanitized["enable_schema"] = !empty($input["enable_schema"]) ? 1 : 0;
        $sanitized["enable_ajax_filters"] = !empty(
            $input["enable_ajax_filters"]
        )
            ? 1
            : 0;

        return $sanitized;
    }

    public function get_defaults()
    {
        $defaults = [];
        foreach ($this->sections as $section) {
            foreach ($section["fields"] as $field_id => $field) {
                $defaults[$field_id] = $field["default"] ?? "";
            }
        }
        return $defaults;
    }

    public function reset_settings_ajax()
    {
        check_ajax_referer("ads_reset_settings_nonce", "_wpnonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Нет прав"]);
        }

        delete_option($this->option_name);
        wp_send_json_success(["message" => "Настройки сброшены"]);
    }
}
