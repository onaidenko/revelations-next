# Текущее состояние

Дата фиксации: 2026-07-17.

## Git и окружение

- Репозиторий: `/Users/admin/Projects/revelations-next`.
- Ветка: `admin-editorial`.
- Контракт AI generation зафиксирован коммитом `38722d1`
  (`Document AI generation contract`), этап 1 — отдельным коммитом
  `440f2b3` (`Stabilize AI generation controls`).
- Оба коммита и последующее context-обновление `d56b7f9` отправлены в
  `origin/admin-editorial`.
- Этап 2 зафиксирован отдельным коммитом `bd56006`
  (`Repair editorial action interfaces`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
- Context-обновление этапа 2 `c2222d1`
  (`Update project context after stage 2`) отправлено; перед этапом 3
  локальная ветка и origin были синхронизированы на `c2222d1`.
- Этап 3 зафиксирован отдельным коммитом `9f60e57`
  (`Extract shared candidate-save backend`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
- Этап 4 зафиксирован отдельным коммитом `34d47f7`
  (`Add section-specific generation profiles`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
- Этап 5 зафиксирован отдельным коммитом `ba262a9`
  (`Extend editorial generation schema`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
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
- Deploy не выполнялся.

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

- Structured output и storage уже содержат recommended/alternative
  titles, advisory section fields, fact-check flags и direct-quote
  candidates.
- Серверная точная проверка direct quotes, conditional validation
  section advisory fields, проверка уникальности titles и поиск
  пропущенных sensitive claims ещё отсутствуют.
- Excerpt и SEO description уже являются отдельными полями, но
  проверка на дословное совпадение отсутствует.
- Source section является server-only context; generation больше не
  назначает WordPress category из model output.
- Source snapshot ограничен одним источником; автоматического
  multi-source verification нет.
- Присвоение `Julia U.` только пустому author уже соответствует
  контракту. Image generation, batch generation и auto-publish
  отсутствуют, что также соответствует контракту.
- Unspoken поддержан storage, ручным candidate intake и общей AI
  schema, но scanner backend и preview отсутствуют.

## Этап 1: AI generation controls

- Реализация зафиксирована отдельным коммитом `440f2b3` и добавляет
  единый server-side resolver для API key, model и enabled flag.
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

## Этап 2: Editorial action interfaces

- Реализация в коммите `bd56006` исправляет оборванный дублированный
  `<style>`/`<script>` fragment в source-draft UI.
- `Create source draft` обслуживается одним submit listener в
  `revelations-editorial-source-draft-ui.php`.
- `Fetch source text`, `Generate/Regenerate with AI`, OpenAI connection
  test и `Restore AI version` обслуживаются одним общим listener в
  `revelations-editorial-long-actions-ui.php`.
- Отдельный generation listener удалён из AI readiness UI; один submit
  generation-формы имеет один native submission path.
- Loading state, duplicate-submit guard и восстановление после
  клиентской ошибки применяются только к отправляемой форме и не
  блокируют несвязанные action-формы.
- Backend handlers, nonce checks, AI generation transient lock,
  resolver, schema, prompts, section/author logic, source extraction и
  database contracts не менялись. Candidate-save handler и его UI не
  переносились.
- Автономный `test-editorial-action-ui.mjs` без WordPress bootstrap
  проверяет структуру inline script/style, JavaScript syntax, action
  ownership, единственность listeners, form-local controls и error
  recovery: 26 passed, 0 failed, exit code 0.
- На `revelations-prod` в уникальной `/tmp`-директории PHP 8.5.4 с
  `mbstring=yes` выполнил отдельный `php -l` для трёх изменённых
  MU-plugin PHP-файлов: все exit code 0.
- Локальная и удалённая временные директории удалены; OpenAI API,
  база, рабочий WordPress, scanners и deploy не запускались.

## Этап 3: Shared preview candidate-save backend

- В коммите `9f60e57` добавлен отдельный MU-plugin
  `revelations-editorial-preview-candidate-save.php` с общим duplicate
  helper и единственным
  `admin_post_revelations_save_preview_candidate` handler.
- Из Tech preview удалён только перенесённый backend-блок. Формы News,
  People, Tech и Places, preview engine, storage, manual backend,
  candidate-save UI и action-value formatting не менялись.
- Capability, section/index nonce, server-side registry, user-specific
  transient, порядок validation и duplicate checks, post status, meta
  mapping, redirects и сообщения сохранены.
- Межплагинные функции разрешаются только при выполнении admin_post
  callback, после загрузки всех MU plugins; новый backend не зависит
  от Tech preview и не создаёт duplicate definitions.
- Автономный `test-editorial-preview-candidate-save.mjs` без WordPress
  bootstrap проверяет ownership, четыре form/nonce contracts, security
  order, load dependencies и handler/storage meta contracts:
  28 passed, 0 failed, exit code 0.
- На `revelations-prod` в уникальной `/tmp`-директории PHP 8.5.4 с
  `mbstring=yes` выполнил отдельный `php -l` для нового backend и
  изменённого Tech preview: оба exit code 0.
- Локальная и удалённая временные директории удалены; база, рабочий
  WordPress, scanners, API и deploy не запускались.

## Этап 4: Section-specific generation profiles

- Добавлен pure registry без fallback для News, Tech, People, Places и
  Unspoken; профиль выбирается только по точному исходному
  `_rev_section`.
- Пустой, неизвестный и legacy `podcast` section возвращают
  `unsupported_generation_section` до чтения source snapshot,
  regeneration metadata, generation settings и OpenAI request.
- Prompt содержит только выбранный section profile. Он дополняет
  сохранённые global editorial policy, tone, structure, banned phrases
  и factual-safety rules.
- Временный legacy-контракт требует точного строкового equality response
  `section` и исходного `_rev_section`. Mismatch возвращает
  `generation_section_mismatch` до word-count validation, version
  backup и любых обновлений WordPress.
- Structured output schema, UI, review metadata, fact-check flags,
  image workflow и storage contracts не менялись.
- Изолированный `test-editorial-ai-generation-profiles.php` без
  WordPress bootstrap, OpenAI API и базы выполнил 40 synthetic cases:
  40 passed, 0 failed, exit code 0.
- На `revelations-prod` в уникальной `/tmp`-директории PHP 8.5.4 с
  `mbstring=yes` выполнил отдельный `php -l` для трёх файлов этапа:
  все exit code 0.
- Локальная и удалённая временные директории удалены; рабочий WordPress,
  API, база и deploy не использовались.

## Этап 5: Extended editorial generation schema

- Legacy model fields `title` и `section` заменены на
  `recommended_title`, ровно два `alternative_titles`, три advisory
  section fields, structured `fact_check_flags` и `direct_quotes`.
- `source_section` не входит в OpenAI response schema: сервер читает
  точный `_rev_section`, передаёт его как immutable prompt context,
  сохраняет в metadata и возвращает logs.
- `recommended_title` становится WordPress `post_title`. Generation
  path больше не вызывает category lookup или
  `wp_set_post_categories`; текущая category сохраняется.
- Fact-check flags содержат claim, утверждённый claim type, source
  evidence, verification flag и reason. Direct-quote candidates
  содержат только точные `quote_text` и `source_fragment`; модель не
  возвращает `verbatim_match`.
- Draft metadata:
  `_revelations_ai_alternative_titles`,
  `_revelations_ai_source_section`,
  `_revelations_ai_section_mismatch`,
  `_revelations_ai_suggested_section`,
  `_revelations_ai_section_mismatch_reason`,
  `_revelations_ai_fact_check_flags`,
  `_revelations_ai_direct_quotes`.
- Private versions сохраняют соответствующие
  `_rev_ai_alternative_titles`, `_rev_ai_source_section`,
  `_rev_ai_section_mismatch`, `_rev_ai_suggested_section`,
  `_rev_ai_section_mismatch_reason`, `_rev_ai_fact_check_flags` и
  `_rev_ai_direct_quotes`. Optional restore удаляет stale draft
  metadata, если legacy version не содержит нового ключа, не меняя
  существующий category/author/content contract.
- Generation logs используют только server result `source_section` или
  candidate `_rev_section`; suggested section, category, API key,
  prompt и source snapshot не используются для section logging.
- На `revelations-prod` PHP 8.5.4 с `mbstring=yes` выполнил lint шести
  PHP-файлов: 6 passed, все exit code 0.
- Profile diagnostics: 35 passed, 0 failed. Schema/storage diagnostics:
  48 passed, 0 failed. Оба scripts завершились с exit code 0 без
  WordPress bootstrap, OpenAI API и базы.
- Локальная и удалённая уникальные `/tmp`-директории удалены. Live
  WordPress, база, OpenAI API и deploy не использовались.

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

После синхронизации этапа 5 выполнить read-only анализ этапа 6:
`Validate generated editorial claims`. До согласования плана код этапа
6 не менять. OpenAI API test, WordPress/DB runtime, scanner dry-run и
deploy не выполнять без отдельного согласования.
