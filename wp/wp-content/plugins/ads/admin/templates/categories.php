<?php
/**
 * Template: Categories Management
 * @package Ads_Board
 *
 * @var array $data {
 *     @type array  $items          Список категорий
 *     @type int    $total_items    Всего записей
 *     @type int    $total_pages    Всего страниц
 *     @type int    $current_page   Текущая страница
 *     @type string $search         Поисковый запрос
 *     @type object $edit_item      Категория для редактирования (или null)
 *     @type array  $errors         Ошибки валидации
 *     @type string $base_url       Базовый URL для пагинации
 * }
 */

if (!defined("ABSPATH")) {
    exit();
}

$items = $data["items"] ?? [];
$edit = $data["edit_item"] ?? null;
$errors = $data["errors"] ?? [];
$old = $data["old_input"] ?? []; // Для сохранения значений при ошибке
$pagination = [
    "total" => $data["total_pages"] ?? 1,
    "current" => $data["current_page"] ?? 1,
    "base_url" =>
        $data["base_url"] ??
        admin_url("admin.php?page=ads-categories&paged=%#%"),
];
$search = $data["search"] ?? "";
?>

<div class="wrap ads-board-categories">
    <h1 class="wp-heading-inline">
        📁 <?php _e("Категории объявлений", "ads-board"); ?>
        <span class="count">(<?php echo (int) ($data["total_items"] ??
            0); ?>)</span>
    </h1>

    <?php if (!$edit): ?>
        <button type="button" class="page-title-action" id="ads-show-add-form">
            ➕ <?php _e("Добавить категорию", "ads-board"); ?>
        </button>
    <?php else: ?>
        <a href="<?php echo esc_url(
            admin_url("admin.php?page=ads-categories"),
        ); ?>" class="page-title-action">
            ← <?php _e("Вернуться к списку", "ads-board"); ?>
        </a>
    <?php endif; ?>

    <!-- 🔔 Уведомления -->
    <?php // 1. Из редиректа
    if (!empty($data["notice"])): ?>
        <div class="notice notice-<?php echo esc_attr(
            $data["notice"]["type"],
        ); ?> is-dismissible">
            <p><?php echo esc_html($data["notice"]["msg"]); ?></p>
        </div>
    <?php
        // 2. Из settings_errors (для ошибок валидации без редиректа, если нужно)

        else:settings_errors("ads_categories");endif; ?>

    <!-- ➕ Форма добавления (скрыта по умолчанию) -->
    <?php if (!$edit): ?>
    <div id="ads-add-category-form" class="postbox" style="display: none; margin-top: 20px;">
        <h2 class="hndle"><span>➕ <?php _e(
            "Новая категория",
            "ads-board",
        ); ?></span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field(
                    "ads_categories_nonce",
                    "ads_categories_nonce_field",
                ); ?>
                <input type="hidden" name="ads_action" value="create">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cat_name"><?php _e(
                            "Название",
                            "ads-board",
                        ); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="name" id="cat_name" class="regular-text"
                                   value="<?php echo esc_attr(
                                       $old["name"] ?? "",
                                   ); ?>" required>
                            <?php if (isset($errors["name"])): ?>
                                <p class="description" style="color: #dc3232;"><?php echo esc_html(
                                    $errors["name"],
                                ); ?></p>
                            <?php endif; ?>
                            <p class="description"><?php _e(
                                "Например: Недвижимость, Транспорт",
                                "ads-board",
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_slug"><?php _e(
                            "Ярлык (slug)",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <input type="text" name="slug" id="cat_slug" class="regular-text code"
                                   value="<?php echo esc_attr(
                                       $old["slug"] ?? "",
                                   ); ?>"
                                   placeholder="<?php esc_attr_e(
                                       "Автогенерация из названия",
                                       "ads-board",
                                   ); ?>">
                            <?php if (isset($errors["slug"])): ?>
                                <p class="description" style="color: #dc3232;"><?php echo esc_html(
                                    $errors["slug"],
                                ); ?></p>
                            <?php endif; ?>
                            <p class="description"><?php _e(
                                "Латиницей, без пробелов. Например: real-estate",
                                "ads-board",
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_desc"><?php _e(
                            "Описание",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <textarea name="description" id="cat_desc" class="large-text" rows="3"><?php echo esc_textarea(
                                $old["description"] ?? "",
                            ); ?></textarea>
                            <?php if (isset($errors["description"])): ?>
                                <p class="description" style="color: #dc3232;"><?php echo esc_html(
                                    $errors["description"],
                                ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_sort"><?php _e(
                            "Порядок сортировки",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <input type="number" name="sort_order" id="cat_sort" class="small-text"
                                   value="<?php echo esc_attr(
                                       $old["sort_order"] ?? "0",
                                   ); ?>" min="0">
                            <p class="description"><?php _e(
                                "Меньшее число = выше в списке",
                                "ads-board",
                            ); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e(
                        "Создать категорию",
                        "ads-board",
                    ); ?></button>
                    <button type="button" class="button" id="ads-cancel-add"><?php _e(
                        "Отмена",
                        "ads-board",
                    ); ?></button>
                </p>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ✏️ Форма редактирования (показывается если есть $edit) -->
    <?php if ($edit): ?>
    <div class="postbox" style="margin-top: 20px;">
        <h2 class="hndle"><span>✏️ <?php _e(
            "Редактирование категории",
            "ads-board",
        ); ?></span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field(
                    "ads_categories_nonce",
                    "ads_categories_nonce_field",
                ); ?>
                <input type="hidden" name="ads_action" value="update">
                <input type="hidden" name="id" value="<?php echo esc_attr(
                    $edit->id,
                ); ?>">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cat_name"><?php _e(
                            "Название",
                            "ads-board",
                        ); ?> <span class="required">*</span></label></th>
                        <td>
                            <input type="text" name="name" id="cat_name" class="regular-text"
                                   value="<?php echo esc_attr(
                                       $old["name"] ?? ($edit->name ?? ""),
                                   ); ?>" required>
                            <?php if (isset($errors["name"])): ?>
                                <p class="description" style="color: #dc3232;"><?php echo esc_html(
                                    $errors["name"],
                                ); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_slug"><?php _e(
                            "Ярлык (slug)",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <input type="text" name="slug" id="cat_slug" class="regular-text code"
                                   value="<?php echo esc_attr(
                                       $old["slug"] ?? ($edit->slug ?? ""),
                                   ); ?>">
                            <?php if (isset($errors["slug"])): ?>
                                <p class="description" style="color: #dc3232;"><?php echo esc_html(
                                    $errors["slug"],
                                ); ?></p>
                            <?php endif; ?>
                            <p class="description"><?php _e(
                                "Латиницей, без пробелов",
                                "ads-board",
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_desc"><?php _e(
                            "Описание",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <textarea name="description" id="cat_desc" class="large-text" rows="3"><?php echo esc_textarea(
                                $old["description"] ??
                                    ($edit->description ?? ""),
                            ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cat_sort"><?php _e(
                            "Порядок сортировки",
                            "ads-board",
                        ); ?></label></th>
                        <td>
                            <input type="number" name="sort_order" id="cat_sort" class="small-text"
                                   value="<?php echo esc_attr(
                                       $old["sort_order"] ??
                                           ($edit->sort_order ?? "0"),
                                   ); ?>" min="0">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e(
                            "Статистика",
                            "ads-board",
                        ); ?></th>
                        <td>
                            <p class="description">
                                <?php
                                global $wpdb;
                                $ads_count = $wpdb->get_var(
                                    $wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->prefix}ads WHERE category_id = %d",
                                        $edit->id,
                                    ),
                                );
                                printf(
                                    _n(
                                        "В категории %d объявление",
                                        "В категории %d объявлений",
                                        $ads_count,
                                        "ads-board",
                                    ),
                                    $ads_count,
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e(
                        "Сохранить изменения",
                        "ads-board",
                    ); ?></button>
                    <a href="<?php echo esc_url(
                        admin_url("admin.php?page=ads-categories"),
                    ); ?>" class="button"><?php _e(
    "Отмена",
    "ads-board",
); ?></a>
                </p>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- 📋 Таблица категорий -->
    <?php if (!$edit && !empty($items)): ?>
    <form method="get" class="ads-categories-filters" style="margin: 20px 0;">
        <input type="hidden" name="page" value="ads-categories">
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="search" name="s" value="<?php echo esc_attr(
                $search,
            ); ?>"
                   placeholder="🔍 <?php esc_attr_e(
                       "Поиск категорий...",
                       "ads-board",
                   ); ?>"
                   style="min-width: 250px; height: 32px; padding: 0 10px;">
            <button type="submit" class="button"><?php _e(
                "Найти",
                "ads-board",
            ); ?></button>
            <?php if ($search): ?>
                <a href="<?php echo esc_url(
                    admin_url("admin.php?page=ads-categories"),
                ); ?>" class="button"><?php _e("Сбросить", "ads-board"); ?></a>
            <?php endif; ?>
        </div>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" style="width: 60px;">ID</th>
                <th scope="col"><?php _e("Название", "ads-board"); ?></th>
                <th scope="col"><?php _e("Ярлык", "ads-board"); ?></th>
                <th scope="col"><?php _e("Описание", "ads-board"); ?></th>
                <th scope="col" style="width: 80px;"><?php _e(
                    "Порядок",
                    "ads-board",
                ); ?></th>
                <th scope="col" style="width: 100px;"><?php _e(
                    "Объявлений",
                    "ads-board",
                ); ?></th>
                <th scope="col" style="width: 150px;"><?php _e(
                    "Действия",
                    "ads-board",
                ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $cat):

                $edit_link = admin_url(
                    "admin.php?page=ads-categories&edit=" . $cat->id,
                );
                $delete_link = wp_nonce_url(
                    admin_url(
                        "admin.php?page=ads-categories&action=delete&id=" .
                            $cat->id,
                    ),
                    "ads_categories_nonce",
                    "ads_categories_nonce_field",
                );

                global $wpdb;
                $ads_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}ads WHERE category_id = %d",
                        $cat->id,
                    ),
                );
                ?>
            <tr>
                <td><?php echo (int) $cat->id; ?></td>
                <td>
                    <strong><?php echo esc_html($cat->name); ?></strong>
                </td>
                <td><code><?php echo esc_html($cat->slug); ?></code></td>
                <td><?php echo esc_html(
                    wp_trim_words($cat->description, 20, "…"),
                ); ?></td>
                <td style="text-align: center;"><?php echo (int) $cat->sort_order; ?></td>
                <td style="text-align: center;">
                    <a href="<?php echo admin_url(
                        "admin.php?page=ads-board&category=" . $cat->id,
                    ); ?>">
                        <?php echo (int) $ads_count; ?>
                    </a>
                </td>
                <td>
                    <div class="row-actions">
                        <a href="<?php echo esc_url(
                            $edit_link,
                        ); ?>">✏️ <?php _e("Ред.", "ads-board"); ?></a> |
                        <a href="<?php echo esc_url($delete_link); ?>"
                           onclick="return confirm('<?php echo esc_js(
                               sprintf(
                                   __("Удалить категорию «%s»?", "ads-board"),
                                   $cat->name,
                               ),
                           ); ?>');"
                           style="color: #dc3232;">
                            🗑️ <?php _e("Удалить", "ads-board"); ?>
                        </a>
                    </div>
                </td>
            </tr>
            <?php
            endforeach; ?>
        </tbody>
    </table>

    <!-- Пагинация -->
    <?php if ($pagination["total"] > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo paginate_links([
                    "base" => $pagination["base_url"],
                    "format" => "",
                    "prev_text" => "«",
                    "next_text" => "»",
                    "total" => $pagination["total"],
                    "current" => $pagination["current"],
                    "type" => "list",
                    "add_args" => array_filter(["s" => $search ?: null]),
                ]); ?>
            </div>
        </div>
    <?php endif; ?>
    <?php elseif (!$edit): ?>
        <div class="card" style="margin-top: 20px; padding: 20px; text-align: center;">
            <p>😕 <?php _e("Категории не найдены.", "ads-board"); ?></p>
            <button type="button" class="button button-primary" id="ads-show-add-form">
                ➕ <?php _e("Создать первую категорию", "ads-board"); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Скрипты для интерактива -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Показать/скрыть форму добавления
    const showBtn = document.getElementById('ads-show-add-form');
    const addForm = document.getElementById('ads-add-category-form');
    const cancelBtn = document.getElementById('ads-cancel-add');

    if (showBtn && addForm) {
        showBtn.addEventListener('click', function() {
            addForm.style.display = 'block';
            showBtn.style.display = 'none';
            document.getElementById('cat_name')?.focus();
        });
    }

    if (cancelBtn && addForm && showBtn) {
        cancelBtn.addEventListener('click', function() {
            addForm.style.display = 'none';
            showBtn.style.display = 'inline-block';
        });
    }

    // Автогенерация slug при вводе названия (если slug пустой)
    const nameInput = document.getElementById('cat_name');
    const slugInput = document.getElementById('cat_slug');

    if (nameInput && slugInput) {
        let slugTouched = false;

        slugInput.addEventListener('focus', function() {
            slugTouched = this.value.length > 0;
        });

        nameInput.addEventListener('input', function() {
            if (!slugTouched && !slugInput.value) {
                // Простая транслитерация для примера
                let slug = this.value.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
    }
});
</script>

<style>
.ads-board-categories .required { color: #dc3232; }
.ads-board-categories code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
.ads-board-categories .row-actions { visibility: hidden; }
.ads-board-categories tr:hover .row-actions { visibility: visible; }
</style>
