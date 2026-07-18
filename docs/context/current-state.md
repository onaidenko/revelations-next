# Текущее состояние

Дата фиксации: 2026-07-18.

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
- Unspoken scanner и preview зафиксированы отдельным коммитом
  `9643e3e` (`Complete Unspoken scanner and preview`) и отправлены в
  `origin/admin-editorial`; deploy не выполнялся.
- Scanner runtime hardening зафиксирован отдельным коммитом `b56828c`
  (`Harden scanner runtime diagnostics`) и отправлен в
  `origin/admin-editorial`; deploy не выполнялся.
- Context-only commit `c1ecff4`
  (`Document controlled scanner dry-run`) отправлен в
  `origin/admin-editorial`; после push ahead/behind равен `0/0`.
- Context-only commit `d91d5ee`
  (`Update project state before production rollout`) отправлен в
  `origin/admin-editorial`; перед production rollout working tree был
  чистым, ahead/behind — `0/0`.
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
- Controlled scanner dry-run подтвердил загрузку реальных feeds, но
  выявил legacy-пустой сохранённый Unspoken profile и недостаточную
  детализацию причин section rejection.

## Следующий безопасный шаг

Scanner signal changes отложены до нескольких RSS snapshots; global AI
gate, Tech signals, thresholds и scoring weights не менять. Не
повторять платный generation smoke без отдельного решения: единственный
разрешённый request выявил `invalid_fact_check_evidence`. Для frontend
сначала устранить stale staging cache отдельным свежим build, собрать
тот же source с production `NEXT_PUBLIC_SITE_URL`, проверить candidate
service по Host header и только затем отдельно согласовать отключение
Base44 и DNS/domain cutover.

## Этап 9: Unspoken scanner and preview

- Функциональная реализация зафиксирована коммитом `9643e3e` и
  отправлена в `origin/admin-editorial`.
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

## Этап 10: Scanner runtime diagnostics hardening

- Runtime-нормализация изменяет только Unspoken settings: legacy-пустой
  профиль получает четыре утверждённых feed, частичный профиль —
  отсутствующие default fields. Operator source overrides и явный
  `enabled=false` сохраняются; обычный `get_option` path не пишет
  option. Отдельная migration function является явной и idempotent.
- Global AI gate и пять section scorers возвращают стабильные
  machine-readable rejection codes. Общий engine агрегирует counts,
  ограниченные безопасные samples и section scores для результатов
  ниже threshold; RSS summary/source snapshot не входят в diagnostics
  и постоянный run log.
- People scorer выполняет deterministic hard reject generic leadership
  advice без нового события. Синтетическая новость о конкретном
  человеке и его действии остаётся допустимой для дальнейшего scoring.
- Qualification thresholds и scoring weights не менялись. Tech
  candidates GPT-Red и Applied Computing до повторного dry-run не
  настраиваются.
- Изолированные remote diagnostics на `revelations-prod`: scanner
  runtime 49/49, Unspoken 27/27 и global AI gate 23/23; все exit code
  0.
- PHP 8.5.4 с `mbstring=yes` выполнил lint 49 production/diagnostic
  PHP-файлов: 49 passed, exit code 0. Bash syntax и Node 22.22.1
  syntax checks также прошли.
- Полный regression runner сохранил успешные результаты всех прежних
  component/integration suites и новых scanner runtime diagnostics;
  итоговый exit code 0.
- Commit `b56828c` отправлен в `origin/admin-editorial`. Локальный и
  удалённый regression `/tmp`-каталоги удалены, отсутствие проверено.
- Повторный controlled scanner dry-run выполнен из copied MU directory
  в уникальной `/tmp` с production WordPress bootstrap, process-local
  theme/feed/cron guards и read-only DB transaction. Unspoken получил
  четыре default feeds через runtime normalization без profile
  override; сохранённый `enabled=false` не изменился.
- Все 19 feeds пяти sections загрузились без HTTP/parser errors.
  Qualified candidates: 0. Below threshold: News 2, Tech 2; People,
  Places и Unspoken — 0. Exact rejection counts сохранены в отчёте.
- Unspoken hard-rejected три истории с `harm_not_central`. Tech:
  Applied Computing получил `insufficient_event_signal` с total `2.9`,
  implementation `0`; mixed GPT-Red Download получил
  `insufficient_section_signal` с relevance/implementation `0`.
- Подозрительные upstream rejects для отдельного решения: WIRED о
  требовании удалить AI nudify apps (`no_meaningful_ai_action`) и BBC
  о снятой после backlash AI image feature
  (`insufficient_ai_context`).
- Backlog: не менять global AI gate, Tech signals, thresholds или
  scoring weights по одному snapshot. Сначала повторно проверить эти
  два возможных false negative, а также GPT-Red и Applied Computing,
  на нескольких RSS snapshots в разные даты; сравнить rejection codes
  и section scores и только затем принимать решение о signal changes.
- До/после совпали posts, postmeta, candidates, preview transients,
  run logs, scanner option, authors и categories. PHP guard не
  зафиксировал write queries; candidates/run logs created — 0.
- OpenAI API, база, live WordPress actions и deploy не выполнялись.

## Production CMS rollout 2026-07-18

- Перед rollout ветка `admin-editorial` была синхронизирована с
  `origin/admin-editorial` на `d91d5ee`, working tree и index были
  чистыми.
- Production target подтверждён как
  `/var/www/revelations-cms/public/wp-content/mu-plugins`.
- До dry-run создан и проверен backup:
  `/root/revelations-mu-plugins-before-deploy-20260717-230914-d91d5ee.tar.gz`;
  SHA-256:
  `c0e52a824bf0d26bd532d525f52d45ea05b6e0cba45a1bb150d7b5c1723692d1`.
- Checksum-based deploy dry-run показал только ожидаемые новые и
  изменённые MU plugins, без deletions. Тот же scope развернут через
  SSH-алиас `revelations-prod`; raw IP deploy script не использовался.
- После deploy все 40 production PHP MU plugins прошли `php -l`.
  Повторный `rsync --dry-run` не обнаружил различий с локальным source.
  PHP-FPM и CMS Nginx error logs после deploy пусты.
- Public API health, articles и sections возвращают HTTP 200.
  Editorial Desk зарегистрирован с `manage_options`; preview registry
  содержит callable scanners News, People, Tech, Places и Unspoken.
- Runtime Unspoken содержит четыре утверждённых feed, thresholds и
  `enabled=false`. AI readiness обнаруживает key и модель
  `gpt-5.6-luna`, но постоянный enabled flag остаётся `false`.
- CMS login и Editorial Desk защищены Nginx Basic Auth и без
  credentials ожидаемо возвращают HTTP 401; REST/Public API routes
  доступны без Basic Auth.

## Controlled production AI smoke

- Выполнен ровно один OpenAI Responses API request с process-local
  enabled flag и production model `gpt-5.6-luna`; постоянная
  конфигурация не менялась, `store=false`.
- Disposable candidate `227` и source draft `228` прошли Save
  candidate, Create source draft и Fetch source. Source extraction
  вернул HTTP 200, `main_paragraphs`, 7096 символов и 17 paragraphs.
- API response был отклонён strict server validation с кодом
  `invalid_fact_check_evidence`: model-provided fact-check evidence не
  найдено точным фрагментом в source snapshot.
- Ошибка произошла до draft update и private version backup. Human
  Review invalidation, generated metadata и regeneration backup не
  смогли быть подтверждены этим live request. Повторный API request не
  выполнялся.
- Candidate и draft помечены run ID и перемещены в Trash. Private AI
  versions, AI run logs и публикации не созданы; editorial locks
  отсутствуют. Временный local/remote runner удалён.

## AI fact-check evidence hardening

- Причина production `invalid_fact_check_evidence` локализована в
  сравнении model-provided evidence с raw source snapshot без
  технической нормализации. Test source `227` содержит 7096 символов
  plain text и LF-разделители; source snapshot остаётся обязательным
  и единственным основанием проверки.
- Evidence comparison теперь допускает только HTML entity decoding,
  CRLF/LF normalization и сворачивание незначащего whitespace. Регистр,
  слова и пунктуация не нормализуются; semantic и fuzzy matching
  отсутствуют.
- Prompt и JSON Schema явно требуют копировать `source_evidence`
  дословно с сохранением слов, регистра и пунктуации.
- Regression fixture использует фактический Inkling source fragment
  из disposable candidate. Transport-only различия проходят;
  paraphrase, изменение регистра и пунктуации отклоняются.
- PHP 8.5.4 lint четырёх изменённых PHP-файлов прошёл. Полная
  AI generation regression suite прошла: stage 1 — 26/26, action UI —
  26/26, candidate-save — 31/31, profiles — 35/35, schema/storage —
  49/49, validation — 41/41, review metadata — 23/23, integration —
  79/79, Unspoken scanner — 27/27, Unspoken preview — 11/11,
  scanner runtime — 49/49 и global AI gate — 23/23.
- Проверки выполнялись изолированно в уникальной remote `/tmp`;
  временные local/remote директории удалены. API, production
  WordPress и база на этом шаге не изменялись.

## Staging frontend и Base44 baseline

- `staging.revelations.me` обслуживается systemd service
  `revelations-staging.service` из `/var/www/revelations-staging` на
  loopback port 3001 через Nginx.
- Deployed `BUILD_ID` `UkKDfZiUsQnvqSaHuBIL6`, `server.js` и
  `.env.production` checksum точно совпадают с локальным verified
  `.next` build. Build использует
  `REVELATIONS_CMS_API_URL=https://cms.revelations.me/wp-json/revelations/v1`
  и staging site URL.
- Read-only SEO/HTTP audit проверил 62/62 страницы, 62 sitemap URLs,
  63 внутренних ссылки и 58 images. Все основные страницы, sections,
  articles, sitemap и robots отвечают HTTP 200; mixed-content
  references на контрольных страницах не найдены.
- Обнаружена одна ошибка: stale Next runtime cache содержит удалённый
  route `/wordpress-editorial-test`, а внутренняя ссылка на него
  возвращает 404. Два предупреждения относятся только к title длиной
  68 и 70 символов. Staging cache/build не изменялись.
- На момент baseline `revelations.me` и `www.revelations.me`
  обслуживались только Base44 с DNS TTL 600; production Nginx vhost,
  service и SSL certificate на `revelations-prod` отсутствовали.
- Baseline-аудит не изменял Base44, DNS, domain binding, reverse
  proxy, SSL или основной домен.

## AI evidence production follow-up

- Strict evidence hardening зафиксирован коммитом `da4cb12`
  (`Harden AI fact-check evidence validation`) и отправлен в
  `origin/admin-editorial`.
- Два изменённых MU plugins точечно развернуты в production CMS после
  backup
  `/root/revelations-ai-evidence-before-deploy-20260718-082419`.
  Live PHP lint и checksum verification прошли.
- Выполнен ровно один дополнительный разрешённый OpenAI request на
  disposable candidate `227` и draft `228`. Ответ снова отклонён с
  `invalid_fact_check_evidence` до draft update и version backup.
- Третий API request не выполнялся. Prompt-only требование дословного
  evidence оказалось недостаточным: модель по-прежнему вернула
  evidence, не совпадающее со snapshot после разрешённой технической
  нормализации. Strict validation и защита от semantic/fuzzy matching
  сохранены.
- Candidate `227` и draft `228` возвращены в Trash; private AI
  versions для draft отсутствуют. Full live generation workflow
  остаётся заблокированным до отдельного решения о более
  детерминированном evidence contract.

## Certbot normalization

- Существующие certificate lineage CMS и staging сохранены без
  изменения serial или validity.
- Устаревший apt `certbot.timer`, запускавший `/usr/bin/certbot 4.0.0`
  без nginx plugin, отключён и неактивен.
- Существующий snap `certbot 5.7.0` и
  `snap.certbot.renew.timer` остаются enabled/active. Его предыдущие
  scheduled runs завершались успешно.
- `/snap/bin/certbot renew --dry-run --no-random-sleep-on-renew`
  успешно проверил `cms.revelations.me` и
  `staging.revelations.me`; `nginx -t` и оба endpoint после dry-run
  сохранили ожидаемое состояние.
- Rollback/config backup:
  `/root/revelations-certbot-normalization-before-20260718-082929`.

## Prepared production frontend

- Fresh Next.js 16.2.10 standalone build
  `Nd9Sm_9gLnbrDWgglMXxm` создан с
  `NEXT_PUBLIC_SITE_URL=https://revelations.me` и прежним public CMS
  API. Build не содержит staging URL или stale
  `/wordpress-editorial-test`; этот path возвращает 404.
- Production clone работает из `/var/www/revelations-production` под
  `deploy:deploy`, service `revelations-production.service` enabled и
  active на `127.0.0.1:3002`. Staging service и port 3001 не
  изменялись.
- HTTP-only pre-certificate Nginx vhost
  `/etc/nginx/sites-available/revelations-production` включён:
  apex proxy использует staging headers/timeouts, `www` возвращает
  301 на apex, production `noindex` отсутствует. DNS всё ещё ведёт на
  Base44, поэтому production TLS/SNI и certificate issuance намеренно
  остаются шагом финального cutover.
- Host audit прошёл: 62/62 sitemap URLs, 55 articles, все 5 sections,
  51 images и 10 Next assets; CMS API — 200, canonical и robots
  используют production apex, staging URLs, active mixed content,
  noindex и 5xx не найдены.
- В двух legacy articles остаются пять обычных внешних HTTP navigation
  links. Они не являются загружаемыми mixed-content resources, но
  зафиксированы как content backlog.
- `npm run build` прошёл. `npm run lint` не запустился из-за
  существующего отсутствия ESLint 9 flat configuration; frontend code
  этой задачей для исправления lint tooling не менялся.
- Production rollback/release:
  `/root/revelations-production-rollout-20260718-083930`.
  В нём сохранены release archive с SHA-256
  `6625514f233ad568937d8e21a6a0588695e9a096d5a1b63619896b4887ceeed0`,
  unit и vhost.
- До cutover authoritative Base44 rollback records были:
  apex `A 137.66.32.95` TTL 600 и `www A 137.66.32.95` TTL 600.
  Целевой server IPv4 был определён как `192.248.179.164`; на этапе
  подготовки DNS, Base44 binding и certificates apex/www ещё не
  изменялись.

## Production frontend cutover

- 2026-07-18 authoritative GoDaddy nameservers и публичные resolvers
  подтвердили `A 192.248.179.164` с TTL 600 для
  `revelations.me` и `www.revelations.me`.
- Snap Certbot 5.7.0 с nginx plugin выпустил отдельный ECDSA lineage
  `/etc/letsencrypt/live/revelations.me`, покрывающий apex и www.
  Сертификат действует до 2026-10-16 08:04:33 UTC.
- Production Nginx vhost обслуживает apex по HTTPS через
  `127.0.0.1:3002`. HTTP apex, HTTP www и HTTPS www возвращают
  прямой 301 на `https://revelations.me` с сохранением path/query.
  `nginx -t` и reload прошли.
- Full public audit прошёл без failures: homepage, 5 categories,
  62/62 sitemap URLs, 55 articles, 57 images, 11 Next assets,
  `robots.txt`, `sitemap.xml` и CMS API вернули ожидаемые ответы.
  Canonical URLs используют production apex; staging URLs, noindex,
  active mixed content и 5xx не обнаружены.
- В двух legacy articles по-прежнему присутствуют пять обычных
  внешних HTTP navigation links; они не загружаются как page
  resources и остаются content backlog.
- `staging.revelations.me` не изменён: homepage, все пять categories,
  sitemap и robots возвращают HTTP 200; staging service active.
- Production lineage использует `authenticator=nginx` и
  `installer=nginx`. Отдельный renewal dry-run для apex/www прошёл.
  Snap renewal timer enabled/active; устаревший apt timer остаётся
  disabled/inactive.
- Pre-certificate Nginx backup:
  `/root/revelations-production-https-before-20260718-090258`.
  Backup Certbot-generated vhost перед canonical cleanup:
  `/root/revelations-production-vhost-before-canonical-20260718-090447`.
- AI generation и ESLint в рамках cutover не изменялись.
