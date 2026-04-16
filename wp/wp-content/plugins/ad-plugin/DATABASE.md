# Структура базы данных плагина "Доска объявлений"

## Таблица: wp_ads_board_categories
Хранит информацию о категориях объявлений

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint(20) | Уникальный ID категории |
| name | varchar(255) | Название категории |
| slug | varchar(255) | URL-slug (уникальный) |
| description | text | Описание категории |
| parent_id | bigint(20) | ID родительской категории (для вложенности) |
| sort_order | int(11) | Порядок сортировки |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |

**Индексы:**
- PRIMARY KEY (id)
- UNIQUE KEY (slug)
- KEY (parent_id)
- KEY (sort_order)

---

## Таблица: wp_ads_board_ads
Хранит информацию об объявлениях

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint(20) | Уникальный ID объявления |
| title | varchar(255) | Заголовок объявления |
| slug | varchar(255) | URL-slug (уникальный) |
| description | longtext | Полное описание |
| price | decimal(10,2) | Цена (необязательно) |
| author_name | varchar(255) | ФИО автора |
| author_phone | varchar(50) | Телефон автора |
| author_email | varchar(100) | Email автора |
| category_id | bigint(20) | ID категории |
| status | varchar(20) | Статус (active, expired, draft, moderation) |
| is_pinned | tinyint(1) | Закреплено (0/1) |
| is_important | tinyint(1) | Важное (0/1) |
| views_count | bigint(20) | Счетчик просмотров |
| published_at | datetime | Дата публикации |
| expires_at | datetime | Дата окончания публикации |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |

**Индексы:**
- PRIMARY KEY (id)
- UNIQUE KEY (slug)
- KEY (category_id, status, is_pinned, is_important, published_at, expires_at, views_count)

---

## Таблица: wp_ads_board_images
Хранит информацию об изображениях объявлений

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint(20) | Уникальный ID изображения |
| ad_id | bigint(20) | ID объявления |
| file_path | varchar(500) | Путь к файлу |
| file_name | varchar(255) | Название файла |
| file_type | varchar(50) | Тип файла (mime-type) |
| file_size | bigint(20) | Размер файла в байтах |
| is_primary | tinyint(1) | Главное изображение (0/1) |
| sort_order | int(11) | Порядок отображения |
| created_at | datetime | Дата загрузки |

**Индексы:**
- PRIMARY KEY (id)
- KEY (ad_id, is_primary, sort_order)
- FOREIGN KEY (ad_id) → wp_ads_board_ads(id) ON DELETE CASCADE

---

## Таблица: wp_ads_board_settings
Хранит настройки плагина

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint(20) | Уникальный ID |
| option_name | varchar(191) | Название настройки (уникальное) |
| option_value | longtext | Значение настройки |
| autoload | varchar(20) | Автозагрузка (yes/no) |
| created_at | datetime | Дата создания |
| updated_at | datetime | Дата обновления |

**Индексы:**
- PRIMARY KEY (id)
- UNIQUE KEY (option_name)
- KEY (autoload)

---

## Таблица: wp_ads_board_views
Хранит статистику просмотров объявлений

| Поле | Тип | Описание |
|------|-----|----------|
| id | bigint(20) | Уникальный ID |
| ad_id | bigint(20) | ID объявления |
| user_ip | varchar(45) | IP адрес пользователя |
| user_agent | text | User Agent браузера |
| viewed_at | datetime | Дата и время просмотра |

**Индексы:**
- PRIMARY KEY (id)
- KEY (ad_id, user_ip, viewed_at)
- FOREIGN KEY (ad_id) → wp_ads_board_ads(id) ON DELETE CASCADE
