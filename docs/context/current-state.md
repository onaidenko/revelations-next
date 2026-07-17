# Текущее состояние

Дата фиксации: 2026-07-17.

## Git и окружение

- Репозиторий: `/Users/admin/Projects/revelations-next`.
- Ветка: `admin-editorial`.
- Коммиты `9b67687` и `791c3d3` отправлены в
  `origin/admin-editorial`; локальная и tracking-ветка синхронизированы.
- Функциональная реализация global AI relevance gate зафиксирована
  отдельным коммитом `9b67687` (`Refine global AI relevance gate`).
- `git diff --check` проходит.
- Локальный PHP отсутствует.
- Автономный diagnostic script
  `wordpress-cms/bin/test-editorial-ai-gate.php`.
- Изолированная удалённая проверка выполнена через SSH-алиас
  `revelations-prod` в уникальной директории внутри `/tmp`.
- Удалённое окружение: PHP 8.5.4 CLI, `mbstring=yes`.
- Все шесть PHP-файлов прошли отдельный `php -l`.
- Все 23 AI gate diagnostic cases прошли; diagnostic script завершился
  с exit code 0.
- Локальная и удалённая временные директории удалены, их отсутствие
  проверено.
- Проверка не изменила PHP-файлы: SHA-256 до и после совпадают.
- Все шесть PHP-файлов и diagnostic script входят только в отдельный
  функциональный коммит.
- Push выполнен; deploy не выполнялся.

## Подтверждённая интеграция AI gate

- Gate определён и вызывается один раз в общем scanner engine.
- Он выполняется после валидации и дедупликации, но до section-specific scoring.
- Общий путь используется News, People, Tech и Places.
- `ai_gate_filtered` считает уникальные валидные истории, отклонённые gate после удаления дублей; section-specific отказы отдельно считаются как `hard_filtered`.
- Сигналы организованы в canonical families и выбираются longest-match-first с непересекающимися диапазонами.
- Прямой AI subject проходит только вместе со значимым действием и техническим контекстом либо вторым независимым direct signal.
- Неоднозначные названия продуктов требуют действия и как минимум двух независимых technical signals.
- `robot`, `robots`, robotics, self-driving и Siri являются только technical signals.
- `ai_signal_count` удалён как неиспользуемое поле вне диагностического контракта.
- Четыре section scanner fallback-ответа возвращают `ai_gate_filtered => 0`.

## Непроверенные риски

- `ai_gate_filtered` возвращается engine, но не отображается текущими preview UI и не сохраняется в run log.
- Keyword-based gate остаётся эвристикой и может потребовать настройки после проверки на реальных RSS summaries.
- Полный scanner dry-run и взаимодействие с реальными feeds не проверялись.

## Следующий безопасный шаг

Текущий этап завершён. Следующий этап будет отдельной задачей. Scanner
dry-run и deploy не выполнять без отдельного разрешения.
