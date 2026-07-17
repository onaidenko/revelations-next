# Текущее состояние

Дата фиксации: 2026-07-17.

## Git и окружение

- Репозиторий: `/Users/admin/Projects/revelations-next`.
- Ветка: `admin-editorial`.
- Локальный `HEAD`: `38722d1` (`Document AI generation contract`);
  `origin/admin-editorial` остаётся на `3959936`, push нового коммита
  не выполнялся.
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
- Предыдущий этап был отправлен; текущие context/code изменения не
  отправлялись. Deploy не выполнялся.

## Актуальный контракт AI generation

- Утверждён section-specific редакционный контракт для News, Tech,
  People, Places и Unspoken; AI остаётся центральной темой каждого
  раздела.
- Раздел draft фиксирует scanner или оператор. AI может вернуть
  `suggested_section`, `section_mismatch` и причину, но не меняет
  WordPress-категорию.
- Требуются один recommended title и два alternative titles.
- Excerpt служит описанием карточки, SEO description хранится
  отдельно и не должен дословно его повторять.
- Требуются fact-check flags для чувствительных утверждений и строгая
  проверка прямых цитат по source snapshot.
- Генерация остаётся ручной и по одной статье; публикация требует
  редактуры и актуального human review.
- Default displayed author остаётся `Julia U.` и применяется только
  при отсутствии выбранного автора.
- Image pipeline исключён из текущего этапа; featured image остаётся
  ручным.
- Unspoken не отменён: его scanner и preview будут отдельным будущим
  этапом после согласования плана.

## Расхождения текущего кода с контрактом

- Текущий structured output возвращает один `title` и `section`;
  сгенерированный section автоматически назначается WordPress draft.
- Alternative titles, `suggested_section`, `section_mismatch`,
  fact-check flags и проверяемый контракт прямых цитат отсутствуют.
- Excerpt и SEO description уже являются отдельными полями, но
  проверка на дословное совпадение отсутствует.
- Для всех разделов используется один общий news-oriented prompt;
  section-specific generation profiles отсутствуют.
- Source snapshot ограничен одним источником; автоматического
  multi-source verification нет.
- Присвоение `Julia U.` только пустому author уже соответствует
  контракту. Image generation, batch generation и auto-publish
  отсутствуют, что также соответствует контракту.
- Unspoken поддержан storage, ручным candidate intake и общей AI
  schema, но scanner backend и preview отсутствуют.
- Повреждённый source-draft progress script, дублирующиеся submit
  handlers и размещение общего candidate-save handler в Tech preview
  остаются для следующих отдельных этапов.

## Этап 1: AI generation controls

- Незакоммиченная реализация добавляет единый server-side resolver для
  API key, model и enabled flag.
- Readiness, connection test и generation используют общий resolver и
  request guard; environment fallback теперь одинаков для потребителей.
- `REVELATIONS_AI_GENERATION_ENABLED` больше не включается кодом:
  если WordPress-константа определена, только boolean `true` включает
  requests, а boolean `false` имеет приоритет над environment.
- Если константа не определена, environment values `1` и `true`
  (case-insensitive, после trim) включают requests; `0`, `false`,
  пустое и любое другое значение оставляют `enabled=false`.
- Если ни константа, ни environment не заданы, default — false.
- Общий backend guard блокирует внешний AI request при отсутствующем
  key/model или disabled flag. Generation function вызывает guard до
  чтения WordPress draft и до обращения к OpenAI.
- Readiness не возвращает API key. В logs не добавлены key, prompt или
  полный source text.
- Editorial schema, prompts, section logic и generation UI не
  изменялись.
- На `revelations-prod` в уникальной `/tmp`-директории PHP 8.5.4 с
  `mbstring=yes` выполнил `php -l` для пяти изменённых/новых PHP-файлов:
  все exit code 0.
- После уточнения enabled semantics изолированный
  `test-editorial-ai-config.php` выполнил 26 synthetic cases:
  26 passed, 0 failed, exit code 0.
- Diagnostic не загружал WordPress, не обращался к API, сети или базе и
  не создавал WordPress-записей.
- Локальная и удалённая временные директории удалены.

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

Проверить итоговый diff этапа 1 и после отдельного разрешения создать
локальный функциональный commit. Push, OpenAI API test, WordPress/DB
runtime, Unspoken scanner, scanner dry-run и deploy не выполнять без
отдельного согласования.
