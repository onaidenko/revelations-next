# Подтверждённая архитектура

## WordPress CMS

Редакционная CMS реализована набором WordPress MU plugins в `wordpress-cms/mu-plugins/`. Файлы хранятся в Git как снимок исходников; live-каталог WordPress не является Git-репозиторием.

## Scanner subsystem

- `revelations-editorial-scanner-engine.php` — общий dry-run RSS engine: загрузка feeds, нормализация, валидация, глобальная дедупликация, AI gate, вызов section scorer, сортировка и формирование результата.
- `revelations-editorial-{news,people,tech,places,unspoken}-scanner.php` — источники, ключевые слова, пороги и scoring соответствующего раздела. Каждый scanner вызывает общий engine.
- `revelations-editorial-scanner-settings.php` — профили News, People, Tech, Places и Unspoken: включение, preview limit, активные и отключённые источники, keyword groups и thresholds.
- Runtime settings выполняют pure-нормализацию только Unspoken:
  legacy-пустой профиль получает актуальные default feeds, частичный
  профиль — только отсутствующие поля. Явные operator values и
  `enabled=false` сохраняются; обычное чтение option не выполняет
  запись. Отдельная idempotent migration function доступна только для
  явного вызова.
- `revelations-editorial-preview-engine.php` — registry и общий запуск preview для пяти разделов, runtime-настройки, transient preview и run logging.
- `revelations-editorial-{news,people,tech,places,unspoken}-preview.php` — административные preview-интерфейсы разделов.
- Unspoken scanner по умолчанию выключен. Его source pool ограничен MIT Technology Review, WIRED, BBC Technology и The Verge.
- Unspoken использует только пять явных tracks: `documented_harm`, `failure_or_reversal`, `economic_model_failure`, `legal_or_governance_conflict` и `labor_or_social_cost`; fallback track отсутствует.
- После общего AI gate Unspoken отдельно требует harm/failure signal, подтверждённое событие, attribution/evidence, свежесть и значимость. Total threshold равен `5.2`, что выше самого строгого действующего total threshold остальных разделов `4.8`; обязательные gates нельзя компенсировать aggregate score.
- Общий engine агрегирует machine-readable rejection counts отдельно
  для global AI gate и section scorer. Для section results ниже
  threshold возвращаются ограниченные score diagnostics; rejection
  samples не содержат RSS summary или source snapshot и не
  переносятся в постоянный run log.
- People scorer до scoring отклоняет generic leadership advice без
  нового события, но сохраняет новости о решении, назначении,
  заявлении или действии конкретного человека.
- Single-source allegation допускается только в preview при явном evidence signal. Transient preview содержит evidence type и reputational safeguards; эти поля не создают новый candidate storage contract.

## Preview candidate persistence

- `revelations-editorial-preview-candidate-save.php` — общий backend
  сохранения одного qualified preview candidate для News, People,
  Tech, Places и Unspoken.
- Формы разделов передают общий action, исходный section и candidate
  index с section-specific nonce.
- Handler проверяет capability, server-side section registry, nonce и
  user-specific preview transient; повторно проверяет duplicate key и
  только затем создаёт `rev_candidate` с общим meta mapping.
- Section preview-файлы отвечают только за свои формы и notices;
  shared save handler не зависит от загрузки Tech preview.

## AI generation

- `revelations-editorial-ai-generation-profiles.php` — pure registry
  профилей генерации для News, Tech, People, Places и Unspoken. Профиль
  выбирается только по точному исходному `_rev_section`; fallback
  отсутствует.
- Пустой, неизвестный и legacy `podcast` section блокируются с
  `unsupported_generation_section` до чтения source snapshot,
  generation settings и OpenAI request.
- Выбранный section profile дополняет общий editorial policy, tone,
  structure, banned phrases и factual-safety rules в generation prompt.
- `source_section` читается сервером из `_rev_section`, передаётся
  модели только как неизменяемый контекст и не входит в model output.
- Structured output содержит recommended и два alternative titles,
  advisory section fields, fact-check flags, direct-quote evidence,
  excerpt, SEO fields и article blocks. Legacy `title` и `section`
  удалены из schema.
- Generation path устанавливает recommended title, content и excerpt,
  но не назначает WordPress category из model output. Existing
  conditional default author `Julia U.` не управляется моделью.
- Generation metadata хранится на draft в ключах:
  `_revelations_ai_alternative_titles`,
  `_revelations_ai_source_section`,
  `_revelations_ai_section_mismatch`,
  `_revelations_ai_suggested_section`,
  `_revelations_ai_section_mismatch_reason`,
  `_revelations_ai_fact_check_flags` и
  `_revelations_ai_direct_quotes`.
- Private `rev_ai_version` сохраняет соответствующие ключи с префиксом
  `_rev_ai_`; restore переносит их обратно как optional metadata.
  Legacy versions без этих ключей остаются совместимыми, а сохранённые
  category, title, content и excerpt восстанавливаются существующим
  контрактом.
- AI generation logs получают section только из server result
  `source_section` или candidate `_rev_section`, не из advisory fields
  и не из WordPress category.
- `revelations-editorial-ai-generation-validation.php` — pure
  server-side validation layer без WordPress, API и database calls.
  Он проверяет output после parsing и до private version backup,
  `wp_update_post` и metadata writes.
- Validation детерминированно проверяет normalized title uniqueness,
  advisory section invariants, различие excerpt/SEO description,
  структуру и source evidence fact-check flags, high-confidence
  sensitive claims и Unspoken safeguards.
- Source snapshot разбивается на стабильные нормализованные paragraph
  units `p001`, `p002`, … . Новая model schema возвращает для
  fact-check flags только claim, обязательность ручной проверки и
  `evidence_ids`; точный `source_evidence` восстанавливает сервер.
  Direct quote ссылается на одну evidence unit и принимается только
  как точный substring этой unit. Неизвестные и пустые references
  отклоняются; fuzzy или semantic matching отсутствует.
- Quote validation использует только CRLF/LF normalization и HTML
  entity decoding; case, punctuation и wording не меняются. Quote
  blocks и `direct_quotes` сопоставляются как multiset, а успешно
  проверенные metadata получают server-generated
  `verbatim_match=true`.
- При validation error generation возвращает безопасный error code до
  backup и WordPress writes; draft, category, author, content и AI
  version остаются неизменными. Handler также не записывает
  `_revelations_ai_error` в draft для validation-class errors.
- `revelations-editorial-ai-review-metadata.php` — read-only panel
  текущих AI review metadata в Editorial Desk. Панель расположена
  после AI readiness/actions и до Human Review, безопасно декодирует
  metadata, экранирует model/source strings и не содержит форм или
  mutation actions.
- Панель показывает alternative titles, advisory section data,
  fact-check flags и server-validated quote evidence. Suggested section
  явно обозначен рекомендацией и не меняет WordPress category.
- Human Review hash включает только нормализованные metadata текущей
  draft version: immutable source section, alternative titles,
  advisory section fields, fact-check flags и validated direct quotes.
  Associative keys и unordered metadata lists canonicalized перед
  hashing; metadata предыдущих private AI versions не участвуют.
- Restore сохраняет существующий контракт: восстановленные metadata
  становятся текущими, поэтому панель и Human Review hash используют
  именно восстановленное состояние.

## AI generation diagnostics

- `test-editorial-ai-generation-integration.php` запускает реальный
  production pipeline конфигурации, профилей и prompt, structured
  response parsing, validation, Gutenberg conversion, version
  backup/restore и Human Review hash против in-memory WordPress stores.
- Внешние WordPress posts/meta/categories, `$wpdb`, HTTP transport и
  cache заменены изолированными stubs. Fake transport только считает
  вызовы и возвращает заранее заданный structured response; сеть,
  WordPress bootstrap и база не используются.
- Integration scenarios покрывают pre-transport blocking, успешную
  генерацию пяти sections, validation failures без writes,
  regeneration, restore и legacy-version compatibility.
- `test-editorial-ai-generation-suite.sh` последовательно запускает
  component diagnostics этапов 1–7 и integration diagnostic. Global
  scanner AI gate запускается тем же runner как отдельная upstream
  regression-проверка и не смешивается с generation integration.

## Public frontend content mapping

- `app/page.jsx` получает десять последних опубликованных материалов через
  `getPublishedArticles()` без ограничения по editorial section.
- Hero, secondary cards и общий latest feed показывают фактический раздел
  каждой статьи. Section pages продолжают использовать собственные фильтры.
- Общая ссылка homepage feed ведёт в `/archive`, а не в `/news`.

## Editorial taxonomy and related content

- `revelations-editorial-taxonomy.php` defines separate non-hierarchical
  `revelations_topic`, `revelations_series` and `revelations_location`
  taxonomies for editorial posts. Ordinary WordPress tags remain unchanged.
- Multiple topics are allowed; `_revelations_primary_topic` must refer to an
  assigned topic. Series is limited by its taxonomy contract to one term in
  the importer/admin workflow; locations are independent from topics.
- The model stores ordered manual related post IDs (maximum three), public
  topic eligibility and a validated taxonomy status. The public API returns
  only published, unique, non-self manual targets and exposes safe defaults
  for legacy posts without taxonomy data.
- The frontend normalizes the additive API fields and selects Related content
  deterministically: manual order, series, primary/secondary topic overlap,
  section, date proximity and slug tie-break. Topic eligibility affects future
  topic hubs, not Related selection.
- `data/seo/editorial-taxonomy-v2.json` is the normalized approved proposal.
  `import-editorial-taxonomy.php` validates and plans it in dry-run by default;
  apply requires explicit flags and is not run against production in this stage.

## CMS cache revalidation

- The frontend shares cache tag `revelations:cms:articles`; its 60-second TTL
  remains a fallback. The deployed WordPress sender posts signed public-state
  events to the deployed frontend endpoint when protected URL/secret runtime
  configuration is present.
- Sender snapshots old status/slug/section request-locally before post update,
  then uses take-and-clear after terms are saved. It has no database queue or
  persistent deduplication.

- `lib/cms-articles.js` преобразует WordPress REST payload в единый
  article model для страниц, cards, metadata и JSON-LD.
- Общий `lib/decode-wordpress-text.js` декодирует HTML entities ровно
  один раз через `he` только в plain-text полях WordPress: title,
  excerpt, SEO, author/section/tag names и image alt.
- Gutenberg/HTML article content не передаётся в plain-text decoder и
  продолжает проходить отдельный existing render/sanitization path.

## SEO foundation

- Public CMS `/articles` endpoint принимает `page` и `per_page` (до
  100) и возвращает `items` plus `pagination.page`, `per_page`,
  `total` и `total_pages`; backend выбирает только `publish` posts.
  Frontend запрашивает все страницы последовательно, использует
  metadata при наличии, останавливается на пустой/неполной странице
  без metadata и защищён finite limit/repeated-page guard.
- `lib/seo.js` является общим pure layer для canonical URL, дат,
  sitemap, XML escaping, social image и JSON-LD. Site-relative assets
  становятся абсолютными только от normalized `SITE_URL`; внешние
  HTTP(S) cover URLs сохраняются.
- `app/sitemap.js` включает только public static routes, public section
  routes и deduplicated published articles. Static routes не получают
  synthetic current `lastModified`; section route получает дату самой
  новой article в section, article — real modified date с publication
  fallback.
- `app/news-sitemap.xml/route.js` отдаёт валидный Google News sitemap
  только для News articles, опубликованных в последние 48 часов по
  publication date. Revalidation равна 300 seconds.
- Article metadata использует cover image для Open Graph/Twitter; при
  её отсутствии применяется только branded social fallback. Article
  JSON-LD добавляет `image` только для фактической cover image.
- Root layout публикует один стабильный WebSite/Organization graph с
  `/#website` и `/#organization`. Article page публикует NewsArticle
  для News, Article для остальных sections и BreadcrumbList
  Home → known Section → Article.

## Порядок обработки истории

1. Нормализовать section и limits.
2. Загрузить существующие duplicate keys.
3. Получить RSS feed и элементы источника.
4. Очистить title и summary, проверить обязательные поля и вычислить дату.
5. Вычислить duplicate key и удалить дубли.
6. Сформировать общий story record.
7. Выполнить обязательный глобальный AI relevance gate.
8. Только прошедшую gate историю передать section-specific scoring callback.
9. Агрегировать безопасные rejection codes и scores ниже threshold.
10. Отдельно учесть section hard filters, отсортировать scores и выбрать qualified stories.
11. Вернуть dry-run result без создания кандидатов.

## Publication review and category contract

- `revelations-editorial-category-contract.php` normalizes category IDs
  and validates one allowed category against `revelations_editorial_sections()`.
  It applies only to editorial `post` workflow, not internal post types.
- `revelations-editorial-review.php` keeps the legacy common review hash
  for compatibility and stores optional per-field hashes for new reviews.
  Featured Image is intentionally not in either review fingerprint.
- `revelations-editorial-publish-gate.php` validates incoming REST and
  classic publish proposals, blocks invalid category state, and compares
  incoming editorial fields to the saved reviewed version.
- `rest_pre_insert_post` is the sole publication gate for Gutenberg REST
  requests. The `wp_insert_post_data` fallback detects REST through
  `wp_is_serving_rest_request()` with `REST_REQUEST` fallback and skips
  the internal second save; classic/WP-CLI input is `wp_unslash`-normalized
  before comparison.
- `revelations-cms-editor.js` removes the standard category checklist for
  Articles and provides one Gutenberg select backed by the editor data API.

Глобальный AI gate расположен в общем engine непосредственно перед `call_user_func($score_story, $story)`, поэтому он предшествует section-specific scoring для News, People, Tech, Places и Unspoken.

## Production frontend deployment

- `scripts/deploy-production.sh` is the permanent production frontend
  orchestrator. It requires an exact full commit SHA, a matching confirmation
  token, a clean synchronized `admin-editorial` branch, full tests, lint,
  taxonomy governance, a production build and a secret-free artifact.
- The uploaded artifact never contains `.env`, `.env.*`, private keys or the
  staging domain. The isolated candidate receives only a generated non-secret
  environment containing the canonical production site URL and CMS API URL.
- After candidate verification and process shutdown, the complete
  `.env.production` runtime file is copied server-side from the active release
  into the candidate and compared byte-for-byte. The production secret is
  never sent to the local machine, printed, hashed into logs or exposed to the
  running candidate.
- `scripts/verify-production-release.py` derives the taxonomy hub inventory from
  the candidate sitemap, validates all four hub families and captures a
  metadata/schema manifest. After the atomic switch, public HTTPS must match
  that manifest exactly. Fixed hub counts are not part of the deploy contract.
- Any failed candidate, service, loopback, Nginx, public-manifest, CMS,
  revalidation-GET or staging-noindex check exits explicitly through `fail`.
  The EXIT trap restores the timestamped previous release and checks it against
  a pre-deploy public manifest.
- Remote deploy output is captured without an SSH-to-`tee` pipeline. Success is
  emitted only after runtime transfer, public verification and all surrounding
  checks have passed.
