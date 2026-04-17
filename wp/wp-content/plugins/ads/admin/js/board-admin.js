/**
 * Ads Board Admin - Form Enhancements
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // === Автогенерация slug ===
    const $title = $("#ad_title");
    const $slug = $("#ad_slug");

    if ($title.length && $slug.length && !$slug.val()) {
      let slugTouched = false;

      $slug.on("focus", function () {
        slugTouched = $(this).val().length > 0;
      });

      $title.on("input", function () {
        if (!slugTouched && !$slug.val()) {
          $slug.val(
            $(this)
              .val()
              .toLowerCase()
              .replace(/[^\w\s-]/g, "")
              .replace(/[\s_]+/g, "-")
              .replace(/^-+|-+$/g, ""),
          );
        }
      });
    }

    // === Предпросмотр изображений ===
    const $fileInput = $("#ad_images");
    const $preview = $("#ads-upload-preview");

    if ($fileInput.length && $preview.length) {
      $fileInput.on("change", function (e) {
        $preview.empty();
        const files = Array.from(e.target.files).slice(0, 10);

        files.forEach(function (file) {
          if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = function (ev) {
              const $item = $('<div class="ads-gallery-item preview">').append(
                $("<img>").attr("src", ev.target.result).attr("alt", file.name),
              );
              $preview.append($item);
            };
            reader.readAsDataURL(file);
          }
        });
      });
    }

    // === Валидация контактов ===
    const $form = $("#ads-form");
    const $phone = $("#ad_phone");
    const $email = $("#ad_email");

    if ($form.length && $phone.length && $email.length) {
      $form.on("submit", function (e) {
        if (!$phone.val().trim() && !$email.val().trim()) {
          e.preventDefault();
          alert("Укажите телефон или email");
          $phone.focus();
          return false;
        }
      });
    }

    // === Подсветка обязательных полей при ошибке ===
    $(".ads-field-error").each(function () {
      const $field = $(this)
        .closest(".ads-form-field")
        .find("input, select, textarea")
        .first();
      $field.addClass("ads-field-invalid").css("border-color", "#d63638");
    });
  });
})(jQuery);
