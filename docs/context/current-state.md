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
- Этап 6 зафиксирован отдельным коммитом `ebaf18f`
  (`Validate generated editorial claims`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
- Этап 7 зафиксирован отдельным коммитом `e542af2`
  (`Expose AI editorial review metadata`) и отправлен в
  `origin/admin-editorial`; после функционального push ветки были
  синхронизированы.
- Этап 8 зафиксирован отдельным коммитом `be9002c`
  (`Add AI generation integration diagnostics`) и отправлен в
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
- Unspoken поддержан отдельными scanner и preview; его profile
  выключен по умолчанию.

## Расхождения текущего кода с контрактом

- Structured output и storage уже содержат recommended/alternative
  titles, advisory section fields, fact-check flags и direct-quote
  candidates.
- Server validation уже проверяет title uniqueness, conditional
  advisory fields, различие excerpt/SEO description, fact flags,
  high-confidence sensitive claims и exact direct quotes.
- Review metadata текущей версии показаны в отдельной read-only панели
  Editorial Desk и включены в Human Review hash.
- Source section является server-only context; generation больше не
  назначает WordPress category из model output.
- Source snapshot ограничен одним источником; автоматического
  multi-source verification нет.
- Присвоение `Julia U.` только пустому author уже соответствует
  контракту. Image generation, batch generation и auto-publish
  отсутствуют, что также соответствует контракту.
- Unspoken поддержан storage, ручным candidate intake, общей AI
  schema, scanner backend и preview.

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

## Этап 6: Generated editorial claim validation

- Добавлен pure
  `revelations-editorial-ai-generation-validation.php`; он не вызывает
  WordPress, API или базу и выполняется после parsing model output, но
  до private version backup, `wp_update_post` и metadata writes.
- Titles проходят deterministic normalization: HTML stripping, entity
  decoding, Unicode lowercase, NBSP/typographic mark normalization,
  whitespace collapse и punctuation removal. Все три word sequences
  должны быть непустыми и уникальными.
- Advisory section fields проверяются условно; suggested section
  остаётся рекомендацией и не меняет source section или category.
  Excerpt и SEO description не могут совпадать после базовой
  normalization.
- Fact-check flags требуют непустые claim/evidence/reason, утверждённый
  claim type, `verification_required=true`, exact claim presence в
  generated units, case-sensitive exact evidence в source snapshot и
  отсутствие duplicate claim/type.
- High-confidence detector сканирует titles, excerpt/SEO и article
  blocks на numbers, dates, money/investment/valuation, measurable
  benchmarks, superlatives, quotes, medical/legal/regulatory и явные
  reputational claims. Каждый detection требует compatible flag;
  Unspoken дополнительно запрещает пустой flags list.
- Direct quotes проходят exact source/fragment validation и двустороннее
  multiset-сопоставление с quote blocks. Разрешены только CRLF/LF
  normalization и HTML entity decoding; successful metadata получают
  server-generated `verbatim_match=true`.
- При любой validation error generation возвращает безопасный code до
  backup и WordPress writes; model output не меняет draft, category,
  author, content или AI versions. Validation-class errors также не
  записывают `_revelations_ai_error` в draft.
- На `revelations-prod` PHP 8.5.4 с `mbstring=yes` выполнил lint восьми
  PHP-файлов: 8 passed, все exit code 0.
- Validation diagnostics: 37 passed, 0 failed; profile diagnostics:
  35 passed, 0 failed; schema/storage diagnostics: 48 passed, 0 failed.
  Все scripts завершились с exit code 0 без WordPress bootstrap, API
  и базы.
- Локальная и удалённая уникальные `/tmp`-директории удалены. Live
  WordPress, база, OpenAI API и deploy не использовались.

## Этап 7: AI editorial review metadata

- Добавлен отдельный read-only MU-plugin
  `revelations-editorial-ai-review-metadata.php`; его панель находится
  между AI readiness/actions и существующим Human Review.
- Панель показывает только metadata текущей draft version:
  alternative titles, immutable source section, advisory section
  fields, fact-check flags и validated direct quote evidence.
- UI различает `Found in source`, `Exact source match` и
  `Requires manual verification`; suggested section обозначен только
  рекомендацией и не меняет WordPress category.
- Model-generated и source-derived strings экранируются через
  WordPress escaping. Invalid JSON, пустые metadata и legacy drafts
  обрабатываются без warning/fatal и без mutation actions.
- Human Review hash включает детерминированно нормализованные metadata
  текущей версии. Порядок associative JSON keys и unordered flags/quote
  lists не влияет на hash; изменение текущих metadata его меняет.
  Metadata предыдущих AI versions не участвуют.
- На `revelations-prod` PHP 8.5.4 с `mbstring=yes` выполнил lint четырёх
  PHP-файлов: 4 passed, все exit code 0.
- `test-editorial-ai-review-metadata.php` выполнил 23 synthetic cases:
  23 passed, 0 failed, exit code 0 без WordPress bootstrap, API и базы.
- Локальная и удалённая уникальные `/tmp`-директории удалены. Live
  WordPress, база, OpenAI API и deploy не использовались.

## Этап 8: Combined AI generation diagnostics

- Добавлен автономный
  `test-editorial-ai-generation-integration.php` с in-memory WordPress
  stores и fake OpenAI transport. Diagnostic вызывает реальные
  production-функции config guard, profiles/prompt, schema parsing,
  validation, Gutenberg conversion, backup/restore и Human Review
  hash; production logic в test не копируется.
- Fake transport не обращается к сети, считает attempts и возвращает
  заранее заданный structured response. Disabled, missing key/model,
  пустой/неизвестный section и legacy podcast подтверждённо дают
  transport count 0 и не меняют WordPress state в памяти.
- Успешный pipeline проверен для News, Tech, People, Places и Unspoken.
  Validation failures, regeneration, current metadata, review
  invalidation, restore и legacy version без новых metadata также
  покрыты.
- Integration diagnostics: 79 passed, 0 failed, exit code 0.
- Общий `test-editorial-ai-generation-suite.sh` сохраняет отдельные
  результаты component diagnostics этапов 1–7, integration result и
  отдельный upstream AI gate result; любой failed suite даёт итоговый
  non-zero exit code.
- Component diagnostics: AI config 26/26, action UI 26/26,
  candidate-save 28/28, profiles 35/35, schema/storage 48/48,
  validation 37/37 и review metadata 23/23. Отдельный upstream AI gate:
  23/23. Полный runner завершился с exit code 0.
- Локальные и удалённые Node syntax checks прошли. Удалённая среда:
  PHP 8.5.4, `mbstring=yes`, Node 22.22.1.
- На `revelations-prod` в уникальной `/tmp`-директории PHP lint прошёл
  для всех 45 переданных production и diagnostic PHP-файлов.
- Локальная и удалённая временные директории удалены, отсутствие обеих
  проверено. OpenAI API, сеть из PHP diagnostic, WordPress bootstrap,
  база, live WordPress, scanner runtime, scanner dry-run и deploy не
  использовались.

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

- `ai_gate_filtered` отображается в Unspoken preview, но не сохраняется
  в run log; preview других разделов это поле не показывает.
- Keyword-based gate остаётся эвристикой и может потребовать настройки после проверки на реальных RSS summaries.
- Полный scanner dry-run и взаимодействие с реальными feeds не проверялись.

## Следующий безопасный шаг

После отдельного разрешения последовательно выполнить controlled
scanner dry-run, controlled OpenAI generation test, deployment dry-run
и только затем production deploy с live CMS smoke test.

## Этап 9: Unspoken scanner and preview

- Добавлены отдельные Unspoken scanner и read-only preview поверх
  общего scanner/preview engine и shared candidate-save backend.
- Profile выключен по умолчанию. Source pool содержит только MIT
  Technology Review, WIRED, BBC Technology и The Verge.
- 2026-07-17 отдельные безопасные GET-проверки вернули HTTP 200 и
  валидный RSS/Atom для всех четырёх feeds. В каждом feed обнаружены
  title, link, date и usable summary; production scanner dry-run не
  запускался.
- Scorer поддерживает ровно пять утверждённых tracks без catch-all.
  Global AI gate выполняется раньше scorer; далее обязательны
  harm/failure, confirmed-event, evidence/attribution, freshness и
  significance gates. Total threshold `5.2` выше максимального
  действующего threshold остальных разделов `4.8`.
- Opinion, speculation, promotional material, anonymous unsupported
  allegations, headline-only sensationalism, stale stories и
  нецентральные AI-сюжеты hard-reject. Общий engine по-прежнему
  удаляет duplicates до AI gate и section scoring.
- Негативный центральный сюжет остаётся Unspoken; optional
  `secondary_section` не меняет сохраняемый раздел. Attributed
  single-source allegations получают preview-only safeguards и
  evidence type без утверждения об истинности.
- Unspoken зарегистрирован в preview engine, Editorial Desk и
  server-side shared candidate-save registry. Используются общий
  action, section-specific nonce, user transient, duplicate checks и
  прежний candidate/meta storage contract.
- Component diagnostics: scorer 27/27, Unspoken preview 11/11,
  shared candidate-save 31/31 и editorial action UI 26/26; все
  завершились с exit code 0.
- На `revelations-prod` в уникальной `/tmp`-директории PHP 8.5.4 с
  `mbstring=yes` выполнил lint всех 48 переданных production и
  diagnostic PHP-файлов: 48 passed, 0 failed. Bash syntax и Node
  syntax checks также прошли.
- Полный regression runner сохранил успешные результаты всех прежних
  component/integration suites, новых Unspoken suites 27/27 и 11/11,
  а также upstream AI gate 23/23; итоговый exit code 0.
- Feed snapshots и локальные/удалённые scorer test-директории удалены.
  Candidates, WordPress/DB writes, OpenAI API, production scanner
  dry-run, live WordPress и deploy не использовались.
