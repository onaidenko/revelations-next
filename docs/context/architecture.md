# Подтверждённая архитектура

Project-wide governance, evidence boundaries and the staging-first visual
approval requirement are canonical in
[`../PROJECT_CONSTITUTION.md`](../PROJECT_CONSTITUTION.md).

## WordPress CMS

Редакционная CMS реализована набором WordPress MU plugins в `wordpress-cms/mu-plugins/`. Файлы хранятся в Git как снимок исходников; live-каталог WordPress не является Git-репозиторием.

- `wordpress-cms/bin/deploy-to-server.sh` is the canonical executable CMS
  deployment procedure. Its targeted mode accepts direct allowlisted files
  from `mu-plugins`, stages and lints them before mutation, and never uses a
  delete operation; `snapshot-live.sh` remains the separate reverse snapshot
  operation.

## CMS-authoritative public article catalog

- When `REVELATIONS_CMS_API_URL` is configured, published CMS API results are
  the sole source for public article lists, slug routes, sitemaps and legacy-ID
  redirects. `data/articles.json` is used only in explicit local/legacy mode
  when no CMS URL is configured.
- A CMS fetch failure remains a failure and never becomes JSON article content.
  A legacy-ID redirect resolves only from a current published CMS article's
  `revelations_legacy_id`; Trash, unpublished and permanently deleted posts
  fail closed.

## Scanner subsystem

- `revelations-editorial-scanner-engine.php` — общий dry-run RSS engine: загрузка feeds, нормализация, валидация, one-time source freshness horizon, глобальная дедупликация, high-recall AI/future-tech prefilter и section scorer. Prefilter принимает clear explicit AI или recognised future-tech family без universal material-action requirement; action labels остаются downstream semantics. Он recognises autonomy/driverless freight, robotics/cobots, smart infrastructure, advanced construction, future mobility и human-machine systems, including the bounded `begin/start + daily/commercial + autonomous/driverless + run/service/operations` deployment phrase. Section identity, а не global gate, решает concrete News event, central person и central physical deployment. A callback seam permits a named section-specific relevance gate without changing the global gate for existing sections.
- Preview Scanner применяет pipeline `feed -> freshness horizon -> dedup -> relevance gate -> categorical section identity -> existing score ranking -> top five`. News, People, Places, Tech and Unspoken retain the global AI/future-tech gate; Art instead uses its isolated Art relevance gate. После identity existing score components и total объясняют и сортируют Preview, но aggregate threshold/freshness floor/significance route больше не создают второй rejection для News, People или Places. Deterministic ties use publication time then canonical URL. Tech qualification remains unchanged.
- Places accepts a named route/site (for example I-45) as central only when
  the passed action label and autonomous/driverless context establish a
  physical deployment. It separately converts confirmed shared `deploy` or
  `operations` labels into an implementation signal only after that physical
  predicate succeeds; a bare route or generic company launch remains outside
  Places implementation semantics.
- `revelations-editorial-{news,people,tech,places,art,unspoken}-scanner.php` — источники, ключевые слова, пороги и scoring соответствующего раздела. News separates product/deployment, concrete-research, major-capital and strategic-company events; only exceptional capital events use their impact-and-scale path instead of implementation maturity. Каждый scanner вызывает общий engine.
- `revelations-editorial-scanner-settings.php` — профили News, People, Tech, Places, Art и Unspoken: включение, preview limit, активные и отключённые источники, keyword groups и thresholds. Active source carries discovery-only `priority` and `volume`; neither value is a factual authority, reliability or evidence role.
- Runtime settings выполняют pure-нормализацию Unspoken и source coverage:
  legacy-пустой профиль получает актуальные default feeds, а exact former
  shipped source pool получает approved replacement. Частичный профиль,
  `enabled=false` и любой operator-edited source list сохраняются; обычное
  чтение option не выполняет запись. Отдельная idempotent migration function
  доступна только для явного вызова.
- `revelations-editorial-preview-engine.php` — registry и общий запуск preview для шести разделов, runtime-настройки, transient preview и run logging.
- `revelations-editorial-{news,people,tech,places,art,unspoken}-preview.php` — административные preview-интерфейсы разделов.
- Art is an approved first-class section (`art` / `Art`). Its disabled-by-default seven-feed discovery pool is Hyperallergic, The Art Newspaper, ARTnews, Artforum, Designboom Art, Guggenheim and La Biennale di Venezia; the pool is discovery-only and does not establish factual authority. Art's gate accepts central visual/contemporary-art developments without AI or future-tech, while rejecting generic listings, promotions, incidental celebrity/design coverage and generic technology coverage. Its diagnostics use the separate `art_relevance_gate` scope, so Art Preview does not imply that an AI gate filtered its items.
- Unspoken scanner по умолчанию выключен. Его discovery pool combines broad
  technology reporting with accountability sources: TechCrunch, TechCrunch AI,
  WIRED, The Verge, 404 Media, Rest of World, MIT Technology Review, BBC
  Technology, The Markup, EFF Deeplinks, Grist and Smart Cities Dive.
- Unspoken сохраняет legacy tracks `documented_harm`, `failure_or_reversal`, `economic_model_failure`, `legal_or_governance_conflict` и `labor_or_social_cost`, а также принимает evidence-backed hidden-labor, limitation, human-effect, trust/identity, infrastructure-cost, autonomy-gap и second-order angles. Negative sentiment сам по себе не квалифицирует материал. A plausible RSS lead may receive one of at most eight scanner-only article snapshots per Preview run when it has both a potential Unspoken family and a concrete anchor. The 8-second/2-redirect/768 KB/8,000-character snapshot is in-memory only; its body is not written to candidates, transients, run logs or fetched-item metadata. The existing Unspoken angle, evidence and allegation gate remains the only eligibility evaluator after enrichment.
- После общего relevance gate Unspoken требует evidence, свежесть и конкретный hidden/overlooked angle либо legacy track. Total threshold равен `5.2`, что выше самого строгого действующего total threshold остальных разделов `4.8`; implementation сохраняется как diagnostic score, но evidence-backed emerging issues не требуют deployment-maturity minimum. Обязательные evidence/angle gates нельзя компенсировать aggregate score.
- Общий engine агрегирует machine-readable rejection counts отдельно
  для global gate, Art relevance gate и section scorer. Discovery volume selects bounded source
  windows: high 50 entries/72h, medium 30/7d and low 20/14d. Постоянный run
  log хранит source counts, rejection counts, не более десяти closest rejected
  и bounded qualified items, а также complete bounded fetched-item metadata
  for only the latest RSS run in each section. Fetched metadata is source,
  title, canonical URL, timestamp, stage and rejection codes; RSS summary,
  article body и source snapshot не сохраняются. `_rev_fetched_items` is
  encoded once, validates the existing bounded JSON payload without generic
  textarea sanitization or re-serialization, and is read back after write;
  encode/persistence failure is retained in bounded run errors without
  expanding the fetched-item schema.
- People scorer до scoring отклоняет generic leadership advice без
  нового события, но сохраняет новости о решении, назначении,
  заявлении или действии конкретного человека.
- Single-source allegation допускается только в preview при явном evidence signal. Transient preview содержит evidence type и reputational safeguards; эти поля не создают новый candidate storage contract.

## Preview candidate persistence

- `revelations-editorial-preview-candidate-save.php` — общий backend
  сохранения одного qualified preview candidate для News, People,
  Tech, Places, Art и Unspoken.
- Формы разделов передают общий action, исходный section и candidate
  index с section-specific nonce.
- Handler проверяет capability, server-side section registry, nonce и
  user-specific preview transient; повторно проверяет duplicate key и
  только затем создаёт `rev_candidate` с общим meta mapping.
- Section preview-файлы отвечают только за свои формы и notices;
  shared save handler не зависит от загрузки Tech preview.

## AI generation

- Production generation currently uses known stable baseline `2051799`, restored
  by rollback commit `09cb32cd5c92a8dc664e8e968756a8679fe7ec24`. Its correctness
  architecture is stable and is not a hardening experiment; any further change
  requires separate approval and isolated validation. The approved canonical
  `THE REVELATION` integration is bounded to final structured output and the
  publication/readiness boundary; it does not modify factual gates.

- `revelations-editorial-ai-research.php` separates factual research from
  editorial judgment. It classifies a private lead snapshot and uses the
  official OpenAI Responses `web_search` tool for independent corroboration
  without Editorial Policy, thesis or voice instructions. A separate brief
  stage receives the saved Editorial Policy, section profile and evidence pack
  before the final writer.
  Research sources must be two distinct hosts beyond the lead host; restricted
  lead types also require a primary-authoritative source. A failed or
  insufficient research pack returns a safe status before any draft mutation.
- `revelations-editorial-ai-generate.php` uses the resulting evidence pack for
  existing deterministic paragraph/evidence and sensitive-claim validation.
  The saved Editorial Policy is injected only into the brief and final writing.
  Pillar-level provenance distinguishes primary detail dominance from a
  secondary-source rewrite risk. Private draft/run metadata stores compact provenance and counts;
  public content receives a multi-source footer only.
- The same single final structured response includes required plain-text
  `revelation`. Server-side validation rejects an empty, headed or
  over-180-word value before version backup and draft mutation. On success the
  existing transaction stores it only in canonical `revelations_revelation`
  metadata, never Gutenberg body blocks; no extra OpenAI or research request
  exists. Existing version backup/restore and review snapshots already include
  that canonical field.
- Research and the editorial brief may inspect the broad evidence pack. The
  final writer receives a pre-writing `final_evidence_pack`: only evidence IDs
  selected by factual pillars, sensitive-claim, attribution or essential
  context fields in the brief. A shared source registry holds source metadata
  once and units reference its `sNNN` ID. Before final writing the compact pack
  rechecks independent hosts, restricted-lead primary support and dominance;
  public source resolution still uses the original private provenance.
- A single global final-writing factuality safeguard preserves material scope,
  qualifiers, conditions, category boundaries and uncertainty for legal,
  regulatory, scientific and formal definitions. It is not duplicated in
  section profiles. Private diagnostics record per-request stage usage and
  bounded context-size counts, while aggregate generation usage remains.
- The legacy deterministic fact-check validator receives a flat serialization
  reconstructed from the selected normalized `pNNN` units, never the
  registry-containing prompt representation. Thus its existing unit parser
  retains the original IDs, while source registry/provenance remains solely a
  compact downstream prompt and public-source-resolution concern.
- Successful research immediately produces a bounded private registry summary
  and `pNNN -> sNNN` mapping; a parsed brief records bounded pillar support and
  dominance before its local checks. The generation failure handler persists
  these diagnostics, so a research-to-brief failure has research/brief totals
  without a fabricated final stage. Raw prompts, URLs, excerpts and Responses
  payloads are excluded.
- If, and only if, the initial editorial brief fails the existing dominance
  validator for a secondary source that solely supports a central pillar or a
  majority of pillars, generation may make one in-memory brief-repair request
  against the same research pack. The repair can retain genuinely matching
  support, narrow, demote or remove a pillar; it cannot collect sources,
  reclassify roles, weaken validation or retry. The repaired brief passes the
  unchanged validator once. Run diagnostics retain only the attempt flag,
  initial failure code, repaired-validation result and central/supporting
  counts before and after; no brief, claim or evidence text is newly retained.
- Global Research & Source Policy is shared across every generated section:
  lead classification, independent corroboration, source roles, restricted
  lead safeguards, evidence provenance, sensitive claims, attribution and
  source dominance never live in section profiles. After final validation,
  only source records resolved from actually used block/claim evidence IDs can
  enter the public `Sources:` footer; broader research remains private.

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

- The existing public CMS API exposes a non-empty, valid canonical
  `revelations_revelation` as `revelation`; `lib/cms-articles.js` maps it and
  the existing article template renders `THE REVELATION`. This integration
  changes neither that API contract nor the frontend renderer.

## Article author entities

- `revelations-editorial-authors.php` owns the non-login `rev_author` CPT and
  the ordered article relation `_revelations_author_profile_ids`.
- Public-ready profiles require active state, canonical name, meaningful bio
  and at least one published linked article. Next.js renders them at
  `/authors/{slug}`; only these profiles enter the regular sitemap.
- The public article API keeps `displayed_author` and additively exposes
  `author_profiles`. Canonical relation identity is independent of profile
  readiness: thin entities canonicalize bylines without links/cards/profile
  URLs, while public-ready profiles receive stable Person/Organization IDs.
- The exact historical-string migration map is applied only by the controlled
  CLI after full audit gates. Julia Upiterskaya is the public-ready Person;
  Julia U. and Julia Yupiterskaya remain explicit legacy aliases. Alina and
  Editorial Team remain thin. Production has 53 ordered article relations
  (Julia 15, Alina 31, Editorial Team 7).
- Canonical provisioning explicitly persists and reads back schema type because
  WordPress metadata defaults do not prove that a desired non-default value was
  written. A separate repair mode can reconcile only that approved field for
  an exact, unique configured canonical identity; ordinary conflicts remain
  fail-closed.

- `app/page.jsx` получает опубликованные материалы через
  `getPublishedArticles()`, оставляет разделы News, People, Tech, Places,
  Unspoken и Podcast и показывает десять самых новых в общей хронологии.
- Hero, secondary cards и общий latest feed показывают фактический раздел
  каждой статьи. Access не является editorial section. Section pages
  продолжают использовать собственные фильтры.
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
- Taxonomy governance treats the approved taxonomy and tag maps as snapshots.
  Existing mapped articles remain fail-closed: missing approved articles,
  map disagreement and metadata drift remain blocking. Newly published articles
  outside both snapshots are reported as `taxonomy_governance_pending` and do
  not block unrelated frontend deployments.

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

- Staging deployment artifacts package the standalone runtime together with
  `.next/static`, `.next/BUILD_ID` and `public`. The staging candidate checks
  archive checksum/build identity and verifies its homepage-referenced CSS/JS
  assets before an atomic switch.

- Legacy article requests use `app/article/[legacy]/route.js`. It maps only
  verified Base44 IDs and canonical fallback slugs from `data/articles.json`
  to their canonical article path with a single 308 redirect; every unknown
  value fails closed with 404. This route handler owns `/article/*` because a
  generic Next.js redirect would run before Proxy and cannot distinguish known
  IDs from unknown values.
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
  для свежих публикаций разделов News, People, Tech, Places, Unspoken и
  Podcast, опубликованных в последние 48 часов по publication date.
  Access и неизвестные sections исключаются. Revalidation равна
  300 seconds.
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

- Production frontend deployment is intentionally split into two permanent
  commands. `scripts/prepare-production-release.sh` performs branch checks,
  tests, lint, taxonomy governance, production build and secret-free package
  creation without SSH or production mutation. It writes an immutable,
  full-commit-bound artifact and JSON manifest below ignored
  `.release/production/<full-sha>/`. `scripts/deploy-production.sh` requires
  that prepared release, independently validates its manifest, checksum,
  commit marker, build ID, production targets and artifact contents, then
  performs the remote deployment.
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
- Remote deployment output is streamed live to the controlling process while
  being retained in the local deploy log. The SSH status and the log writer
  status are checked separately, with an SSH failure taking precedence.
  Shared SSH/SCP options enforce batch mode, bounded connection and liveness
  checks without weakening host-key verification. Success is emitted only after
  runtime transfer, public verification and all surrounding checks have passed.


## Public brand identity graph

## Art source-draft and diagnostic boundary

- Art candidate-to-draft conversion requires the canonical Art category before
  creating a post. A missing Art term stops conversion, retains the candidate
  for review and records a bounded error; existing-section draft behavior is
  unchanged.
- The generic scanner runner accepts an optional section relevance callback.
  Art provides its own central-subject/development gate and writes
  `art_relevance_gate` diagnostics only for Art; legacy global scanner
  diagnostics retain their original fields.

## Podcast structured data

- `app/podcast/page.jsx` renders the page-specific `PodcastSeries` JSON-LD;
  its stable ID is `${SITE_URL}/podcast#podcast-series` and its publisher is
  the existing NewsMediaOrganization ID.
- `app/[slug]/page.jsx` selects `PodcastEpisode` JSON-LD only through the pure
  `buildPodcastEpisodeJsonLd` eligibility gate in `lib/seo.js`; all other
  pages retain the existing Article or NewsArticle entity. Article breadcrumb
  behavior is unchanged.

- `REVELATIONS` is the canonical publication and site name.
- `Revelations Media` and `revelations.me` are alternative names.
- The root graph identifies REVELATIONS as a `NewsMediaOrganization`
  based in Dubai and connects its official Instagram, X and YouTube
  profiles through `sameAs`.
- JULS is represented as the publishing parent brand. No `legalName`,
  company registration or legal identifier is asserted.
- About, Contact, footer and metadata use the same canonical public
  description.


## Social distribution drafts

- Editorial Desk contains a separate Social Drafts tab.
- The first channel entity is X.
- Every published WordPress article may store one private, copy-ready X draft.
- Unpublished posts are eligible only when linked to an Editorial Desk candidate.
- The entity stores text, status, source hash, origin, model, timestamps,
  operator ID and one previous version.
- X draft generation uses only the saved WordPress article.
- The public canonical article URL is appended server-side.
- No X API integration or automatic social publication is active.

## Historical editorial dash migration

- `wordpress-cms/bin/revelations-dash-migration-lib.php` performs pure
  raw-string planning. It rebuilds the planned value from untouched byte slices
  and explicit dash/adjacent-horizontal-space replacements and verifies an
  untouched-byte SHA-256 invariant.
- `wordpress-cms/bin/build-dash-migration-plan.php` is read-only with respect to
  WordPress. It reads published post columns and the fixed public meta registry
  directly from database rows, emits a mode-0600 plan, and blocks protected
  technical regions or duplicate meta rows.
- The completed production migration used prepared storage-level updates in one
  transaction after record-wide optimistic concurrency checks. It verified all
  planned hashes before commit, then invalidated only affected WordPress caches
  and invoked existing signed frontend revalidation after commit. WordPress save
  APIs, KSES and API/storage hybrid semantics were intentionally excluded. The
  retained repository tooling performs read-only planning and byte-invariant
  diagnostics for future audit/maintenance; it contains no production apply
  path.
