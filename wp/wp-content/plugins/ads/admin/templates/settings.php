<?php
/**
 * Template: Plugin Settings Page
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
} ?>

<div class="wrap ads-board-settings">
    <h1>Настройки доски объявлений</h1>

    <?php settings_errors("ads_board_settings"); ?>

    <form method="post" action="options.php" class="ads-settings-form">
        <?php settings_fields("ads_board_settings"); ?>

        <!-- Вкладки -->
        <div class="ads-settings-tabs">
            <button type="button" class="ads-tab-button active" data-tab="general">Общие</button>
            <button type="button" class="ads-tab-button" data-tab="display">Отображение</button>
            <button type="button" class="ads-tab-button" data-tab="moderation">Модерация</button>
            <button type="button" class="ads-tab-button" data-tab="advanced">Дополнительно</button>
        </div>

        <!-- Контент вкладок: оборачиваем секции -->
        <div class="ads-settings-content">
            <?php
            // Получаем секции вручную, чтобы обернуть их
            global $wp_settings_sections;
            $page = "ads_board_settings_page";

            if (isset($wp_settings_sections[$page])) {
                foreach ($wp_settings_sections[$page] as $section) {
                    $section_id = $section["id"]; ?>
                       <div id="settings-section-<?php echo esc_attr(
                           $section_id,
                       ); ?>" class="settings-section">
                           <?php if ($section["title"]): ?>
                               <h2 class="title"><?php echo esc_html(
                                   $section["title"],
                               ); ?></h2>
                           <?php endif; ?>

                           <?php if ($section["callback"]): ?>
                               <?php call_user_func(
                                   $section["callback"],
                                   $section,
                               ); ?>
                           <?php endif; ?>

                           <!-- ✅ ОБЯЗАТЕЛЬНО оборачиваем в table + tbody -->
                           <table class="form-table">
                               <tbody>
                                   <?php do_settings_fields(
                                       $page,
                                       $section_id,
                                   ); ?>
                               </tbody>
                           </table>
                       </div>
                       <?php
                }
            }
            ?>
        </div>

        <p class="submit" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <?php submit_button(
                "Сохранить изменения",
                "primary",
                "submit",
                false,
                ["style" => "padding: 10px 20px;"],
            ); ?>
            <a href="<?php echo admin_url(
                "admin.php?page=ads-board",
            ); ?>" class="button" style="padding: 10px 20px;">Отмена</a>
        </p>
    </form>
</div>

<style>
.ads-board-settings .ads-settings-tabs {
    display: flex;
    gap: 5px;
    margin: 20px 0 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 0;
}
.ads-board-settings .ads-tab-button {
    padding: 10px 20px;
    background: #f6f7f7;
    border: 1px solid #ddd;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
    cursor: pointer;
    font-size: 14px;
}
.ads-board-settings .ads-tab-button.active {
    background: #fff;
    border-color: #ddd;
    border-bottom: 1px solid #fff;
    font-weight: 500;
    margin-bottom: -1px;
}
.ads-board-settings .settings-section {
    display: none;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 0 4px 4px 4px;
    padding: 20px;
}
.ads-board-settings .settings-section.active {
    display: block;
}
.ads-board-settings .form-table th {
    padding: 15px 10px 15px 0;
    width: 250px;
    font-weight: 500;
}
.ads-board-settings .form-table td {
    padding: 15px 0;
}
.ads-board-settings .description {
    margin: 5px 0 0;
    color: #666;
}
@media screen and (max-width: 782px) {
    .ads-board-settings .ads-settings-tabs {
        flex-wrap: wrap;
    }
    .ads-board-settings .ads-tab-button {
        flex: 1 1 auto;
        text-align: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ads-tab-button');
    const sections = document.querySelectorAll('.settings-section');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;

            tabs.forEach(function(t) { t.classList.remove('active'); });
            sections.forEach(function(s) { s.classList.remove('active'); });

            this.classList.add('active');
            const targetSection = document.getElementById('settings-section-' + target);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });

    // Активация первой вкладки
    if (tabs[0] && sections[0]) {
        tabs[0].click();
    }
});
</script>
