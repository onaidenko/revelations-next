# Подтверждённая архитектура

## WordPress CMS

Редакционная CMS реализована набором WordPress MU plugins в `wordpress-cms/mu-plugins/`. Файлы хранятся в Git как снимок исходников; live-каталог WordPress не является Git-репозиторием.

## Scanner subsystem

- `revelations-editorial-scanner-engine.php` — общий dry-run RSS engine: загрузка feeds, нормализация, валидация, глобальная дедупликация, AI gate, вызов section scorer, сортировка и формирование результата.
- `revelations-editorial-{news,people,tech,places}-scanner.php` — источники, ключевые слова, пороги и scoring соответствующего раздела. Каждый из этих scanner-файлов вызывает общий engine.
- `revelations-editorial-scanner-settings.php` — профили разделов: включение, preview limit, активные и отключённые источники, keyword groups и thresholds. Settings перечисляют News, People, Tech, Places и Unspoken; подтверждённые scanner backends существуют для первых четырёх. Unspoken имеет пустой default profile.
- `revelations-editorial-preview-engine.php` — registry и общий запуск preview для Tech, News, People и Places, runtime-настройки, transient preview и run logging.
- `revelations-editorial-{news,people,tech,places}-preview.php` — административные preview-интерфейсы разделов.

## Preview candidate persistence

- `revelations-editorial-preview-candidate-save.php` — общий backend
  сохранения одного qualified preview candidate для News, People,
  Tech и Places.
- Формы разделов передают общий action, исходный section и candidate
  index с section-specific nonce.
- Handler проверяет capability, server-side section registry, nonce и
  user-specific preview transient; повторно проверяет duplicate key и
  только затем создаёт `rev_candidate` с общим meta mapping.
- Section preview-файлы отвечают только за свои формы и notices;
  shared save handler не зависит от загрузки Tech preview.

## Порядок обработки истории

1. Нормализовать section и limits.
2. Загрузить существующие duplicate keys.
3. Получить RSS feed и элементы источника.
4. Очистить title и summary, проверить обязательные поля и вычислить дату.
5. Вычислить duplicate key и удалить дубли.
6. Сформировать общий story record.
7. Выполнить обязательный глобальный AI relevance gate.
8. Только прошедшую gate историю передать section-specific scoring callback.
9. Отдельно учесть section hard filters, отсортировать scores и выбрать qualified stories.
10. Вернуть dry-run result без создания кандидатов.

Глобальный AI gate расположен в общем engine непосредственно перед `call_user_func($score_story, $story)`, поэтому он предшествует section-specific scoring для News, People, Tech и Places.
