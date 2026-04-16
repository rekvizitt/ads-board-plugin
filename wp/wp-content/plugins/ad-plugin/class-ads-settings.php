<?php
/**
 * Класс для работы с настройками плагина
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Settings
{
    private $database;
    public function __construct()
    {
        require_once ADS_BOARD_PLUGIN_DIR .
            "includes/class-ads-board-database.php";
        $this->database = new Ads_Board_Database();
    }
    public function get_option($option_name, $default = null)
    {
        global $wpdb;

        $table = $this->database->get_settings_table();

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$table} WHERE option_name = %s",
                $option_name,
            ),
        );

        return $value !== null ? maybe_unserialize($value) : $default;
    }
    public function update_option($option_name, $option_value)
    {
        global $wpdb;

        $table = $this->database->get_settings_table();

        // Сериализуем значение, если это массив или объект
        $option_value = maybe_serialize($option_value);

        // Проверяем, существует ли опция
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE option_name = %s",
                $option_name,
            ),
        );

        if ($exists) {
            // Обновляем существующую
            return $wpdb->update(
                $table,
                ["option_value" => $option_value],
                ["option_name" => $option_name],
                ["%s"],
                ["%s"],
            );
        } else {
            // Создаем новую
            return $wpdb->insert(
                $table,
                [
                    "option_name" => $option_name,
                    "option_value" => $option_value,
                ],
                ["%s", "%s"],
            );
        }
    }
    public function delete_option($option_name)
    {
        global $wpdb;

        $table = $this->database->get_settings_table();

        return $wpdb->delete($table, ["option_name" => $option_name], ["%s"]);
    }
    public function get_all_options()
    {
        global $wpdb;

        $table = $this->database->get_settings_table();

        $results = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$table}",
            ARRAY_A,
        );

        $options = [];
        foreach ($results as $row) {
            $options[$row["option_name"]] = maybe_unserialize(
                $row["option_value"],
            );
        }

        return $options;
    }
}
