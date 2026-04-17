<?php
/**
 * Template: Main Page - Список объявлений
 * @package Ads_Board
 *
 * @var array $data {
 *     @type array  $items          Список объявлений
 *     @type int    $total_items    Всего записей
 *     @type int    $total_pages    Всего страниц
 *     @type int    $current_page   Текущая страница
 *     @type array  $filters        Параметры фильтрации
 *     @type array  $categories     Список категорий
 *     @type string $base_url       Базовый URL для пагинации
 * }
 */

if (!defined("ABSPATH")) {
    exit();
}

// Данные уже подготовлены контроллером, здесь только вывод
$items = $data["items"];
$pagination = [
    "total" => $data["total_pages"],
    "current" => $data["current_page"],
    "base_url" => $data["base_url"],
];
$filters = $data["filters"];
$categories = $data["categories"];
?>

<div class="wrap ads-board-page">
    <h1 class="wp-heading-inline">
        📋 <?php _e("Все объявления", "ads-board"); ?>
        <span class="count">(<?php echo (int) $data["total_items"]; ?>)</span>
    </h1>

    <a href="<?php echo admin_url(
        "admin.php?page=ads-add-new",
    ); ?>" class="page-title-action">
        ➕ <?php _e("Добавить новое", "ads-board"); ?>
    </a>

    <!-- Фильтры -->
    <form method="get" class="ads-filters" style="margin: 20px 0; padding: 15px; background: #fff; border-left: 4px solid #0073aa;">
        <input type="hidden" name="page" value="ads-board">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="search" name="s" value="<?php echo esc_attr(
                $filters["search"],
            ); ?>"
                   placeholder="🔍 <?php esc_attr_e(
                       "Поиск...",
                       "ads-board",
                   ); ?>"
                   style="min-width: 250px; height: 32px; padding: 0 10px;">

            <select name="status" style="height: 32px; padding: 0 10px;">
                <option value="all" <?php selected(
                    $filters["status"],
                    "all",
                ); ?>><?php _e("Все статусы", "ads-board"); ?></option>
                <option value="active" <?php selected(
                    $filters["status"],
                    "active",
                ); ?>>✅ <?php _e("Активные", "ads-board"); ?></option>
                <option value="draft" <?php selected(
                    $filters["status"],
                    "draft",
                ); ?>>📝 <?php _e("Черновики", "ads-board"); ?></option>
                <option value="expired" <?php selected(
                    $filters["status"],
                    "expired",
                ); ?>>⏰ <?php _e("Истёкшие", "ads-board"); ?></option>
                <option value="sold" <?php selected(
                    $filters["status"],
                    "sold",
                ); ?>>💰 <?php _e("Продано", "ads-board"); ?></option>
            </select>

            <select name="category" style="height: 32px; padding: 0 10px;">
                <option value="0"><?php _e(
                    "Все категории",
                    "ads-board",
                ); ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr(
                        $cat->id,
                    ); ?>" <?php selected($filters["category"], $cat->id); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="button"><?php _e(
                "Фильтровать",
                "ads-board",
            ); ?></button>
            <?php if (
                $filters["search"] ||
                $filters["status"] !== "all" ||
                $filters["category"]
            ): ?>
                <a href="<?php echo admin_url(
                    "admin.php?page=ads-board",
                ); ?>" class="button"><?php _e("Сбросить", "ads-board"); ?></a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Таблица -->
    <form method="post" id="ads-list-form">
        <?php wp_nonce_field("ads_bulk_action_nonce", "ads_bulk_nonce"); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="ads_bulk_action">
                    <option value=""><?php _e(
                        "— Массовые действия —",
                        "ads-board",
                    ); ?></option>
                    <option value="delete">🗑️ <?php _e(
                        "Удалить",
                        "ads-board",
                    ); ?></option>
                    <option value="activate">✅ <?php _e(
                        "Активировать",
                        "ads-board",
                    ); ?></option>
                    <option value="deactivate">📝 <?php _e(
                        "Снять с публикации",
                        "ads-board",
                    ); ?></option>
                </select>
                <button type="submit" class="button action"><?php _e(
                    "Применить",
                    "ads-board",
                ); ?></button>
            </div>
            <?php if ($pagination["total"] > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(
                            _n(
                                "Показано %d элемент",
                                "Показано %d элементов",
                                count($items),
                                "ads-board",
                            ),
                            count($items),
                        ); ?>
                    </span>
                    <?php echo paginate_links([
                        "base" => $pagination["base_url"] . "%#%",
                        "format" => "",
                        "prev_text" => "«",
                        "next_text" => "»",
                        "total" => $pagination["total"],
                        "current" => $pagination["current"],
                        "type" => "list",
                        "add_args" => array_filter([
                            "s" => $filters["search"] ?: null,
                            "status" =>
                                $filters["status"] !== "all"
                                    ? $filters["status"]
                                    : null,
                            "category" => $filters["category"] ?: null,
                        ]),
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="ads-select-all">
                    </td>
                    <th scope="col" style="width: 50px;">ID</th>
                    <th scope="col"><?php _e("Название", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Категория", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Цена", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Автор", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Статус", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Просмотры", "ads-board"); ?></th>
                    <th scope="col"><?php _e("Дата", "ads-board"); ?></th>
                    <th scope="col" style="width: 120px;"><?php _e(
                        "Действия",
                        "ads-board",
                    ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="10" class="text-center" style="padding: 40px; text-align: center;">
                            <p>😕 <?php _e(
                                "Объявления не найдены.",
                                "ads-board",
                            ); ?></p>
                            <a href="<?php echo admin_url(
                                "admin.php?page=ads-add-new",
                            ); ?>" class="button button-primary">
                                ➕ <?php _e(
                                    "Создать первое объявление",
                                    "ads-board",
                                ); ?>
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $ad):

                        $edit_link = admin_url(
                            "admin.php?page=ads-add-new&edit=" . $ad->id,
                        );
                        $delete_link = wp_nonce_url(
                            admin_url(
                                "admin.php?page=ads-board&action=delete&ad_id=" .
                                    $ad->id,
                            ),
                            "delete_ad_" . $ad->id,
                        );
                        $status_config = Ads_Helpers::get_status_config(
                            $ad->status,
                        );
                        ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="ads_ids[]" value="<?php echo esc_attr(
                                $ad->id,
                            ); ?>">
                        </th>
                        <td><?php echo (int) $ad->id; ?></td>
                        <td>
                            <strong><a href="<?php echo esc_url(
                                $edit_link,
                            ); ?>"><?php echo esc_html(
    mb_strimwidth($ad->title, 0, 50, "…"),
); ?></a></strong>
                            <?php if ($ad->is_pinned): ?>
                                <span class="dashicons dashicons-flag" style="color: #f0ad4e;" title="<?php esc_attr_e(
                                    "Закреплено",
                                    "ads-board",
                                ); ?>"></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(
                            $ad->category_name ?: "—",
                        ); ?></td>
                        <td><?php echo $ad->price
                            ? "<strong>" .
                                number_format_i18n($ad->price, 2) .
                                " ₽</strong>"
                            : '<span style="color:#777">—</span>'; ?></td>
                        <td>
                            <?php echo esc_html($ad->author_name); ?><br>
                            <small style="color:#666"><?php echo esc_html(
                                $ad->author_email ?: $ad->author_phone ?: "",
                            ); ?></small>
                        </td>
                        <td>
                            <span class="ads-status status-<?php echo esc_attr(
                                $ad->status,
                            ); ?>">
                                <?php echo esc_html($status_config["label"]); ?>
                            </span>
                        </td>
                        <td style="text-align:center"><?php echo number_format_i18n(
                            $ad->views_count,
                        ); ?></td>
                        <td>
                            <small>
                                <?php echo date_i18n(
                                    "d.m.Y",
                                    strtotime($ad->created_at),
                                ); ?>
                                <?php if (
                                    $ad->expires_at &&
                                    $ad->status === "active"
                                ): ?>
                                    <br><span style="color:#666"><?php _e(
                                        "до",
                                        "ads-board",
                                    ); ?> <?php echo date_i18n(
     "d.m.Y",
     strtotime($ad->expires_at),
 ); ?></span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="<?php echo esc_url(
                                    $edit_link,
                                ); ?>">✏️ <?php _e(
    "Ред.",
    "ads-board",
); ?></a> |
                                <a href="<?php echo esc_url(
                                    $delete_link,
                                ); ?>" onclick="return confirm('<?php echo esc_js(
    sprintf(__("Удалить «%s»?", "ads-board"), $ad->title),
); ?>');" style="color:#dc3232">
                                    🗑️ <?php _e("Удалить", "ads-board"); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                    endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($pagination["total"] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php echo paginate_links([
                        "base" => $pagination["base_url"] . "%#%",
                        "format" => "",
                        "prev_text" => "«",
                        "next_text" => "»",
                        "total" => $pagination["total"],
                        "current" => $pagination["current"],
                        "type" => "list",
                        "add_args" => array_filter([
                            "s" => $filters["search"] ?: null,
                            "status" =>
                                $filters["status"] !== "all"
                                    ? $filters["status"]
                                    : null,
                            "category" => $filters["category"] ?: null,
                        ]),
                    ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>
