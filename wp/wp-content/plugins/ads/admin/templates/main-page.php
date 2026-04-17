<?php
/**
 * Template: Ads List (Admin)
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

$items = $data["items"] ?? [];
$pagination = [
    "total" => $data["total_pages"] ?? 1,
    "current" => $data["current_page"] ?? 1,
    "base_url" =>
        $data["base_url"] ?? admin_url("admin.php?page=ads-board&paged=%#%"),
];
$filters = $data["filters"] ?? [];
$categories = $data["categories"] ?? [];
?>

<div class="wrap ads-list-wrap">
    <h1 class="wp-heading-inline">Все объявления</h1>
    <a href="<?php echo admin_url(
        "admin.php?page=ads-add-new",
    ); ?>" class="page-title-action">Добавить новое</a>

    <!-- Фильтры -->
    <form method="get" class="ads-filters-form">
        <input type="hidden" name="page" value="ads-board">

        <div class="ads-filters-row">
            <div class="ads-filter-field">
                <label for="ads_search">Поиск</label>
                <input type="search" name="s" id="ads_search"
                       value="<?php echo esc_attr($filters["search"] ?? ""); ?>"
                       placeholder="По заголовку, описанию, автору...">
            </div>

            <div class="ads-filter-field">
                <label for="ads_status">Статус</label>
                <select name="status" id="ads_status">
                    <option value="all" <?php selected(
                        $filters["status"] ?? "",
                        "all",
                    ); ?>>Все статусы</option>
                    <option value="active" <?php selected(
                        $filters["status"] ?? "",
                        "active",
                    ); ?>>Опубликовано</option>
                    <option value="draft" <?php selected(
                        $filters["status"] ?? "",
                        "draft",
                    ); ?>>Черновик</option>
                    <option value="sold" <?php selected(
                        $filters["status"] ?? "",
                        "sold",
                    ); ?>>Продано</option>
                    <option value="expired" <?php selected(
                        $filters["status"] ?? "",
                        "expired",
                    ); ?>>Истёкшее</option>
                </select>
            </div>

            <div class="ads-filter-field">
                <label for="ads_category">Категория</label>
                <select name="category" id="ads_category">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->id); ?>"
                                <?php selected(
                                    $filters["category"] ?? 0,
                                    $cat->id,
                                ); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ads-filter-actions">
                <button type="submit" class="button">Применить</button>
                <?php if (
                    !empty($filters["search"]) ||
                    ($filters["status"] ?? "") !== "all" ||
                    ($filters["category"] ?? 0) !== 0
                ): ?>
                    <a href="<?php echo admin_url(
                        "admin.php?page=ads-board",
                    ); ?>" class="button">Сбросить</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <?php settings_errors("ads_board"); ?>

    <!-- Массовые действия -->
    <form method="post" id="ads-list-form">
        <?php wp_nonce_field("ads_bulk_action_nonce", "ads_bulk_nonce"); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text">Массовые действия</label>
                <select name="ads_bulk_action" id="bulk-action-selector-top">
                    <option value="">— Выбрать действие —</option>
                    <option value="delete">Удалить</option>
                    <option value="activate">Опубликовать</option>
                    <option value="deactivate">Снять с публикации</option>
                </select>
                <button type="submit" class="button action">Применить</button>
            </div>

            <?php if ($pagination["total"] > 1): ?>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(
                            "Показано %d–%d из %d",
                            ($pagination["current"] - 1) * 20 + 1,
                            min(
                                $pagination["current"] * 20,
                                $data["total_items"] ?? 0,
                            ),
                            $data["total_items"] ?? 0,
                        ); ?>
                    </span>
                    <?php echo paginate_links([
                        "base" => $pagination["base_url"],
                        "format" => "",
                        "prev_text" => "«",
                        "next_text" => "»",
                        "total" => $pagination["total"],
                        "current" => $pagination["current"],
                        "type" => "list",
                        "add_args" => array_filter([
                            "s" => $filters["search"] ?? null,
                            "status" =>
                                ($filters["status"] ?? "") !== "all"
                                    ? $filters["status"] ?? null
                                    : null,
                            "category" =>
                                $filters["category"] ?? 0
                                    ? $filters["category"] ?? null
                                    : null,
                        ]),
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Таблица -->
        <table class="wp-list-table widefat fixed striped ads-ads-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="ads-select-all">
                    </td>
                    <th scope="col" class="ads-col-id">ID</th>
                    <th scope="col">Заголовок</th>
                    <th scope="col">Категория</th>
                    <th scope="col">Цена</th>
                    <th scope="col">Автор</th>
                    <th scope="col">Статус</th>
                    <th scope="col">Просмотры</th>
                    <th scope="col">Дата</th>
                    <th scope="col" class="ads-col-actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="10" class="ads-empty-state-cell">
                            <p>Объявления не найдены.</p>
                            <a href="<?php echo admin_url(
                                "admin.php?page=ads-add-new",
                            ); ?>" class="button button-primary">
                                Добавить первое объявление
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
                        $status_config = [
                            "active" => [
                                "label" => "Опубликовано",
                                "class" => "status-active",
                            ],
                            "draft" => [
                                "label" => "Черновик",
                                "class" => "status-draft",
                            ],
                            "sold" => [
                                "label" => "Продано",
                                "class" => "status-sold",
                            ],
                            "expired" => [
                                "label" => "Истёкло",
                                "class" => "status-expired",
                            ],
                        ][$ad->status] ?? [
                            "label" => $ad->status,
                            "class" => "",
                        ];
                        ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="ads_ids[]" value="<?php echo esc_attr(
                                $ad->id,
                            ); ?>">
                        </th>
                        <td class="ads-col-id"><?php echo (int) $ad->id; ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url($edit_link); ?>">
                                    <?php echo esc_html(
                                        mb_strimwidth($ad->title, 0, 50, "…"),
                                    ); ?>
                                </a>
                            </strong>
                            <?php if ($ad->is_pinned): ?>
                                <span class="ads-badge" title="Закреплено">★</span>
                            <?php endif; ?>
                            <?php if ($ad->is_important): ?>
                                <span class="ads-badge ads-badge-important" title="Важное">●</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(
                            $ad->category_name ?: "—",
                        ); ?></td>
                        <td>
                            <?php if ($ad->price): ?>
                                <strong>$<?php echo number_format_i18n(
                                    $ad->price,
                                    2,
                                ); ?></strong>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($ad->author_name); ?>
                            <?php if ($ad->author_email): ?>
                                <br><small class="text-muted"><?php echo esc_html(
                                    $ad->author_email,
                                ); ?></small>
                            <?php elseif ($ad->author_phone): ?>
                                <br><small class="text-muted"><?php echo esc_html(
                                    $ad->author_phone,
                                ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ads-status <?php echo esc_attr(
                                $status_config["class"],
                            ); ?>">
                                <?php echo esc_html($status_config["label"]); ?>
                            </span>
                        </td>
                        <td class="ads-text-center"><?php echo number_format_i18n(
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
                                    <br><span class="text-muted">до <?php echo date_i18n(
                                        "d.m.Y",
                                        strtotime($ad->expires_at),
                                    ); ?></span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td class="ads-col-actions">
                            <div class="row-actions">
                                <a href="<?php echo esc_url(
                                    $edit_link,
                                ); ?>">Редактировать</a> |
                                <a href="<?php echo esc_url($delete_link); ?>"
                                   onclick="return confirm('Удалить объявление «<?php echo esc_js(
                                       $ad->title,
                                   ); ?>»?');"
                                   class="delete">
                                    Удалить
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                    endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Пагинация внизу -->
        <?php if ($pagination["total"] > 1): ?>
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="ads_bulk_action">
                        <option value="">— Выбрать действие —</option>
                        <option value="delete">Удалить</option>
                        <option value="activate">Опубликовать</option>
                        <option value="deactivate">Снять с публикации</option>
                    </select>
                    <button type="submit" class="button action">Применить</button>
                </div>
                <div class="tablenav-pages">
                    <?php echo paginate_links([
                        "base" => $pagination["base_url"],
                        "format" => "",
                        "prev_text" => "«",
                        "next_text" => "»",
                        "total" => $pagination["total"],
                        "current" => $pagination["current"],
                        "type" => "list",
                        "add_args" => array_filter([
                            "s" => $filters["search"] ?? null,
                            "status" =>
                                ($filters["status"] ?? "") !== "all"
                                    ? $filters["status"] ?? null
                                    : null,
                            "category" =>
                                $filters["category"] ?? 0
                                    ? $filters["category"] ?? null
                                    : null,
                        ]),
                    ]); ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Выбрать все / снять выделение
    const selectAll = document.getElementById('ads-select-all');
    const checkboxes = document.querySelectorAll('input[name="ads_ids[]"]');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            if (!this.checked && selectAll) {
                selectAll.checked = false;
            }
            const allChecked = [...checkboxes].every(c => c.checked);
            if (allChecked && selectAll) {
                selectAll.checked = true;
            }
        });
    });
});
</script>
