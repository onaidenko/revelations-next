# Текущее состояние

## Future-tech editorial gate and scan diagnostics

- All editorial sections now use strict `AI OR meaningful future-tech`
  relevance. News, Places and Tech retain their deployment-focused future-tech
  branch. People requires a named, central technical figure; Unspoken requires
  a substantive, evidence-backed hidden or second-order angle. Generic
  "futuristic" coverage remains rejected before qualification.
- Private RSS run logs now retain bounded source fetch results, rejection
  counts and up to ten closest rejected records with safe signal/score
  diagnostics. RSS summaries and full payloads are excluded.
- Focused diagnostic cases cover future-tech positives/noise, unchanged AI
  branch, People centrality/significance, Unspoken hidden-angle cases and safe
  permanent-diagnostic shaping.
- News now recognizes concrete model, research and consequential investment
  events while retaining significance thresholds. Unspoken recognizes
  privacy/control angles around neural/brain data and persists available angle
  signals on hard rejection. Run logs also retain bounded qualified-item
  diagnostics after preview-transient expiry.
- Unspoken preview now uses the same session-transient state boundary and
  summary/table pattern as the other RSS sections. A stale `scan_ready` query
  parameter cannot show a completion notice when the user-specific preview
  payload is absent; the renderer remains presentation-only and continues to
  display Unspoken-specific evidence and advisory metadata from scanner output.

Дата изменения: 2026-08-12.

## Pre-commit blocker resolution

- Generated public-copy dash normalization now protects deterministic technical
  spans byte-for-byte: absolute URLs, href/src values and other HTML markup,
  code/pre/script/style elements, HTML comments and inline/fenced code. Public
  prose around those spans is still normalized, while existing ASCII compound
  hyphens remain unchanged. Focused validation passes 55/55.
- The restore review-hash failure was reproduced on clean `HEAD` and traced to
  an absent `_revelations_author_profile_ids` value being restored as the
  different serialized value `[]`. Restore now preserves absence as absence and
  preserves a saved relation value; the existing deterministic hash/freshness
  assertion passes. AI generation integration passes 81/81 and the complete PHP
  diagnostic runner passes all suites.
- Current policy documentation now records REVELATIONS as the sole public brand
  name, includes public quotations and historical public CMS copy in the ASCII
  hyphen rule, preserves private evidence verbatim and excludes technical
  values. Migration documentation describes the completed operation and the
  retained read-only audit tooling; no ephemeral local artifact path remains.
- Final safe verification: Node 113/113, ESLint, PHP lint, all PHP diagnostic
  suites, migration round trip 3/3, production build 104/104 pages, standalone
  artifact checks, no staging-domain runtime reference and `git diff --check`
  pass. No production CMS/database/content write, migration rerun, OpenAI
  request, deploy, commit or push was performed.

Дата проверки: 2026-08-08.

## Byte-preserving dash migration v2 and publication recovery

- A raw-byte normalizer, read-only plan builder, representative round-trip
  fixtures and an apply-safety design were added. The approved production work
  used one guarded storage-level transaction; historical HTML was not passed
  through `wp_update_post` or KSES. The repository tooling remains read-only and
  reusable for future audit/maintenance.
- Product-owner review established that records 214, 222 and 293 were intended
  to remain public and that their incident-time `draft` status was erroneous.
  Their current raw editorial fields matched the exact recovery backup. A new
  mode-0600 backup was created at
  `/var/backups/revelations-cms/revelations-status-recovery-20260808-HZ7m1N.json`.
- One guarded database transaction changed only `wp_posts.post_status` from
  `draft` to `publish` for IDs 214, 222 and 293. The transaction locked the full
  post rows, all postmeta and taxonomy relationships; in-transaction and
  post-commit comparisons proved every protected value unchanged. It did not
  call `wp_update_post`, alter timestamps or inspect/change other drafts.
- Object caches were cleaned only for those three IDs. The first CLI sender
  call omitted the required event timestamp and was rejected; corrected signed
  `publish` events using the protected runtime environment succeeded 3/3.
  Public verification returned 200 for all detail pages and cover images,
  exact canonicals and article markup, 53 CMS API records, all three sitemap
  entries, News cards for 214/222, the Tech AGIBOT card and all homepage links.
- The final direct raw scan covers all 53 published records and produces the
  expected current plan: 25 records, 34 fields, 147 em dashes and 9 en dashes
  (156 replacements), with zero blocked or failed fields. All 34 current hashes
  exactly matched the post-recovery raw plan.
- Product-owner-approved production apply used plan SHA-256
  `60b871c809462e383dbf3753634c0e422da5df73971aadd7ce210139c1d7492d`.
  The immediate mode-0600 backup is
  `/var/backups/revelations-cms/revelations-dash-byte-preserving-20260808-LpRAD0.json`
  with SHA-256
  `9dd6d4e77d1ebc8cd49848c1a759ef349f31b594c2b79ec9090ca0bfc136bf6e`.
- One storage-level InnoDB transaction applied the approved 156 replacements
  across 34 fields and 25 records. Preflight matched 34/34 before hashes;
  in-transaction and post-commit verification matched 34/34 after hashes.
  Protected post columns, unrelated postmeta and taxonomy relationships were
  unchanged; `wp_update_post`, KSES and WordPress save hooks were not used.
- The post-commit direct raw scan covered 53 published records and found zero
  public editorial em dashes, zero en dashes and zero blocked fields. Object
  caches were cleaned only for the 25 affected IDs and signed frontend
  revalidation succeeded 25/25. CMS API, homepage, News, People, Tech, Places,
  Unspoken and representative IDs 70, 71, 79, 214, 222 and 293 passed public
  checks for HTTP, title, excerpt, body, image, links, canonical and metadata.
- Final regression results: dash round trip 3/3; AI generation validation
  54/54; focused Constitution/style/migration/SEO tests 16/16; `git diff
  --check` passed. The previously successful production build was not repeated
  because no code changed afterward. No frontend deploy, OpenAI request,
  commit or push was performed.

Дата проверки: 2026-08-08.

## SEO and generated-editorial dash consistency

- `/podcast` now emits the exact metadata title `Podcast - REVELATIONS` while
  retaining its visible `REVELATIONS Podcast` H1 and existing description.
  `/about` now emits `About REVELATIONS` without changing its visible H1 or
  description.
- Root WebSite and NewsMediaOrganization JSON-LD retain `REVELATIONS` as the
  sole public brand name; the former `REVELATIONS Media` alternate names are
  no longer emitted. Header primary editorial navigation is semantically
  distinct from the Access and theme utility controls without a visual change.
- The Podcast featured card uses one responsive semantic H2 instead of
  duplicate desktop/mobile headings. Footer, Access, canonical URLs, robots,
  sitemap, breadcrumbs and hub-detail links remain unchanged.
- `build:production` now removes local `.next` before compiling. This is safe
  because the production release package contains only standalone runtime,
  `.next/static`, `BUILD_ID` and `public`; it prevents a stale non-deployable
  `.next/cache/fetch-cache` response from failing the staging-domain guard.
- Product-owner constitutional amendment extends ASCII-hyphen normalization to
  all public editorial copy, including public quotations and historical CMS
  content, while private source evidence remains verbatim. The AI pipeline
  normalizes public quote blocks too and compares them against exact private
  evidence using dash-only public representation; URLs, slugs, IDs and other
  technical fields are never normalized. Focused PHP diagnostics pass in
  isolation (54/54); focused Constitution/style Node tests pass (5/5).
- The first production dash migration changed the approved 34 fields, but
  three historical `post_content` values diverged because the CLI
  `wp_update_post` save path applied KSES to legacy HTML. A subsequent guarded
  rollback restored 31/34 fields exactly; for IDs 70, 71 and 79 it removed
  existing preload links or canonicalized void tags. Revisions proved those
  third-state values came from the recovery operation and not a later editor.
- Product-owner-authorized direct DB recovery then restored only
  `wp_posts.post_content` for IDs 70, 71 and 79 inside one transaction, using
  frozen current-hash guards and exact pre-mutation backup values. In-transaction
  and post-commit checks passed, protected columns/taxonomy/meta were unchanged,
  and the complete scope is now 34/34 exact pre-mutation hashes. Object caches
  were cleaned only for those IDs and existing signed frontend revalidation was
  sent. This recovery established the exact before-state later used by the
  approved byte-preserving storage-level dash migration documented above.

Дата проверки: 2026-08-08.

## Podcast SEO (staging-first)

- `/podcast` has the approved page-specific title and description, retains the
  exact H1 and canonical tagline, and adds a concise Dubai-based introduction.
- The page emits one `PodcastSeries` JSON-LD entity linked to the existing
  publisher. Published podcast pages emit one `PodcastEpisode` entity only
  when their existing title, description, publication date, cover and YouTube
  URL are all present; otherwise the existing Article schema remains.
- The implementation does not add hosts, duration, episode numbers, feeds,
  transcripts, biographies or CMS data. Thin episode bodies are reported for
  editorial follow-up and are not rewritten.

Дата фиксации: 2026-07-28.

## Staging release packaging

- Staging releases are prepared and deployed through dedicated staging-only
  scripts. Their artifact includes standalone runtime files, `.next/static`,
  `.next/BUILD_ID` and `public`; checksum, commit and build ID are verified
  before switch. Candidate validation requires every CSS/JS asset referenced
  by its homepage to return 200.
- This closes the prior failure where a standalone-only staging artifact served
  current HTML but lacked all Next static assets.

## Legacy article URL handling

- Legacy `/article/{value}` handling now lives in the App Router route handler
  `app/article/[legacy]/route.js`, rather than in Proxy or a generic Next.js
  redirect. It redirects only verified Base44 IDs and canonical fallback
  slugs from `data/articles.json` with one permanent hop; numeric, 24-hex and
  other unknown values return 404.
- The separate Proxy retains only the existing `/Access` to `/access` redirect.
  `/Home` and `/section/{sectionId}` redirects remain in `next.config.mjs`.
- The route is included as dynamic `/article/[legacy]` in the production build
  manifest. Focused runtime checks confirm GET and HEAD behavior and the
  sitemap remains at 95 URLs.

Дата фиксации: 2026-07-28.

## REVELATIONS Article Template — Stage 1

- Shared frontend article template in `app/[slug]/page.jsx` now presents the
  existing article data in this order: up to three taxonomy-derived labels,
  H1, existing excerpt, author/date/reading-time metadata, hero, unchanged
  body, existing taxonomy and related content.
- Header labels use only the existing section and taxonomy presentation data:
  section first, then the assigned primary topic, then one secondary topic;
  the deterministic fallback uses existing series, locations and tags. Terms
  link only when the existing taxonomy hub resolver provides a public URL.
- The visible publication date is semantic `<time dateTime>` markup. A pure
  presentation helper calculates reading time from CMS HTML or legacy Markdown
  body text at 220 words per minute, with a one-minute minimum and no output
  for empty content.
- Canonical URLs, metadata, JSON-LD (including BreadcrumbList and publisher
  relationships), sitemap and redirect behavior, article bodies, taxonomy
  components and image loading attributes remain unchanged. No CMS fields,
  author cards, FAQ, THE REVELATION block or production changes were made.
- Focused article-presentation coverage, the complete Node suite (77/77),
  ESLint and the production build pass. A local production-artifact audit
  verified People, News, Tech, Places and Unspoken representative articles:
  semantic main/article/header structure, one H1, valid ISO publication dates,
  labels, reading time, and header → hero → body order.
- Final desktop/mobile visual QA verified the Sam, long-headline and News
  templates: label-to-H1 spacing, deck hierarchy, metadata/share placement,
  hero-to-body transition and responsive label wrapping. Header labels use
  normal inline flow so long terms wrap naturally. The checked mobile layout
  has no horizontal overflow.
- Stage 1 was committed as `a9d6fd7` and pushed to `origin/admin-editorial`;
  no deploy was performed.
- Article publication timestamps are authoritative UTC instants. Visible CMS
  article dates use their UTC calendar date explicitly, so rendering is stable
  across build, server and visitor timezones; JSON-LD preserves the original
  full timestamp.

Дата проверки: 2026-07-24.

## REVELATIONS Article Template — Stage 2A

- Added optional, human-approved public enrichment: THE REVELATION, Source
  Note, Editorial Note, Disclosure and ordered Public Sources.
- Enrichment is additive across WordPress meta, Gutenberg, Human Review,
  publication change detection, private version backup/restore, public API,
  Next.js normalization and the article page. Empty legacy values remain safe.
- The public API has no fallback to private source snapshots, AI metadata or
  fact-check evidence. Stage 2A adds no Author CPT, FAQ, image-credit feature,
  CMS migration, deploy or live-CMS write.
- Future Stage 2B author identities and profile/indexability policy are now
  recorded in `docs/context/decisions.md`.

## REVELATIONS Article Template — Stage 2B

- Added the `rev_author` editorial entity, ordered optional article relation,
  additive public author API, canonical frontend bylines, author cards and
  `/authors/{slug}` route.
- Public profiles are indexable and added to the sitemap only when active,
  named, meaningful-biography complete and linked to published articles.
- Canonical article relations resolve the approved byline immediately even for
  thin profiles; those relations have no profile URL, link, card or sitemap
  entry until the separate public-readiness threshold is met.
- The production rollout now has three canonical profiles and ordered
  canonical relations on all 53 published articles: Julia Upiterskaya 15,
  Alina B. 31 and Editorial Team 7. The legacy `revelations_author` values
  remain unchanged.

## REVELATIONS Article Template — Stage 2B.1

- Stage 2A and Stage 2B infrastructure were deployed to production at
  `2f71f3e7536c0ee61180ec0775bcfca2f97e391d`. The frontend and CMS health
  checks passed; no production author entities or article relations were
  created because the approved source did not yet contain an executable,
  auditable migration CLI.
- The controlled CLI is deployed at
  `/var/www/revelations-cms/public/bin/migrate-editorial-authors.php`. Its
  audit, provisioning, repair, migration and verification modes require a
  full preflight; write modes require `--confirm`.

## REVELATIONS Article Template - Stage 2B.2 production rollout

- Production provisioning initially exposed a safe conflict: WordPress returns
  the registered `person` meta default for a newly created entity, so the
  former CLI skipped Editorial Team's required `organization` write. Stage
  2B.2 commit `28149b94160fe69404ea477c94f35de7a53a61da` writes and verifies
  the canonical schema type on creation.
- A separate `--repair-canonical` mode may change only the approved schema
  type of an exact, unique canonical `rev_author` identity. It refuses wrong
  type, slug, name, duplicates and ambiguity; it does not alter bio, role,
  portrait, unrelated sameAs or editorial content.
- Editorial Team ID 302 was repaired from `person` to `organization` after a
  fresh backup at
  `/root/revelations-author-repair-before-20260724-185803/revelations-cms.sql.gz`.
  Provisioning and repair then returned zero pending changes.
- A separate pre-migration backup is
  `/root/revelations-author-migration-before-20260724-185901/revelations-cms.sql.gz`.
  Migration applied all 53 relations, and immediate dry-run plus `--verify`
  returned zero pending writes, conflicts, unexpected values, malformed
  relations and skips.
- All 53 published articles passed the relation and public API audit. Thin
  profiles keep canonical visible bylines but have no public author route,
  card or sitemap entry. Representative JSON-LD emits Person for Julia and
  Alina, Organization for Editorial Team, and no thin-profile `@id`.

## REVELATIONS Stage 2B.3 and Stage 2C production rollout

- Julia entity ID 300 was reconciled in place from the superseded
  `Julia Yupiterskaya` / `julia-yupiterskaya` identity to Julia Upiterskaya /
  `julia-upiterskaya`; all 15 relations were preserved. Legacy source values
  remain accepted only as explicit migration aliases.
- Fresh backups: identity `/root/revelations-julia-identity-before-20260724-192758/revelations-cms.sql.gz` and profile `/root/revelations-julia-profile-before-20260724-192959/revelations-cms.sql.gz`.
- Julia is active and public-ready with the approved bio, role and LinkedIn.
  Her profile and Person ID are live at `/authors/julia-upiterskaya`; all 15
  bylines and cards use the canonical identity. Alina and Editorial Team remain thin.
- Final frontend release `7f68c77b4bcd3d7263abb633687474befff01a14` and CMS API
  lookup fixes expose numeric JSON relations correctly. Audit remains 53/53:
  Julia 15, Alina 31 and Editorial Team 7, with zero pending writes or conflicts.

## REVELATIONS brand and editorial style contract (superseded historical state)

This dated 2026-07-21 snapshot is retained as history. Its quotation exclusions
and public alternate-name policy were superseded by the current Constitution and
the pre-commit state recorded above.

- Canonical source-controlled brand assets are `BRAND_NAME` (`REVELATIONS`),
  `BRAND_TAGLINE` (`Born as a podcast. Built as a media platform.`) and
  `BRAND_DESCRIPTOR` (`Future-Facing Media from Dubai`) in `lib/site.js`.
- Owned UI, metadata templates, schema descriptions and future AI generation
  use the uppercase brand spelling and ASCII hyphen-minus. Source evidence,
  verbatim quotations, technical identifiers, URLs and historical article
  bodies are explicitly outside automatic normalization.
- `docs/editorial-style.md` is the authoritative style contract; `AGENTS.md`
  requires it for editorial, UI, SEO, metadata, CMS generation, AI generation
  and publisher-identity work. Focused Node coverage guards these assets and
  the prompt boundary between generated editorial copy and exact evidence.
- The root `NewsMediaOrganization` retains its stable identity and publisher
  relationships while exposing the exact canonical tagline through Schema.org
  `slogan`. WebSite alternate names remain `REVELATIONS Media` and
  `revelations.me`; the Organization alternate name is only `REVELATIONS Media`.
- Final regression passed: full Node suite (74/74), ESLint, focused
  brand/schema/style diagnostics and the production build. A local production
  artifact audit verified homepage, Podcast, People, Tech, Places, About and
  the Sam Kaploushenko article: expected titles/H1s, production canonical URLs,
  exact root schema identity and no staging URLs. Static title inputs do not
  duplicate the layout-level ` - REVELATIONS` suffix.

Дата фиксации: 2026-07-21.

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

## Final post-launch cleanup 2026-07-18

- Реализация разделена на три согласованных commits:
  `Decode WordPress text entities`,
  `Use deterministic AI evidence references` и
  `Fix frontend lint and legacy links`. После итогового context amend
  ветка отправлена в `origin/admin-editorial`; functional source
  staging и production соответствует тому же tree и lockfile.
- Общий frontend mapper декодирует WordPress plain-text entities ровно
  один раз через `he`; HTML article content decoder не затрагивает.
  `npm test`, ESLint 9 flat config и production build прошли без
  ошибок или warnings.
- Новая AI schema использует server-generated evidence units
  `p001`, `p002`, … . Модель возвращает evidence IDs, сервер
  восстанавливает source evidence, а direct quotes проверяются только
  точным substring без fuzzy/semantic matching. Legacy metadata
  остаётся читаемой.
- Полная изолированная PHP suite прошла: config 26/26, action UI
  26/26, candidate-save 31/31, profiles 35/35, schema 49/49,
  validation 47/47, review 23/23, integration 79/79, Unspoken scanner
  27/27, Unspoken preview 11/11, scanner runtime 49/49 и global AI
  gate 23/23. Production lint всех 40 MU plugins прошёл.
- CMS backup:
  `/root/revelations-mu-plugins-before-postlaunch-20260718-095013.tar.gz`.
  Три изменённых AI MU plugins развернуты; live checksums совпали с
  Git source, CMS health/articles API отвечают HTTP 200, generation
  постоянно остаётся disabled.
- Legacy-link backup:
  `/root/revelations-legacy-links-before-migration-20260718-0950-c78081c`.
  В posts 73 и 74 точечно заменены пять проверенных HTTP navigation
  links на HTTPS; повторный migration dry-run возвращает ноль
  изменений. Title, excerpt, slug, status, author и categories
  сохранены.
- Staging и production собраны из одного functional commit и lockfile
  SHA-256
  `83e118c53343e7d5d4fcbd3a211d6b4724b8e93e58290a261c1ad4c5d0b5c5c5`
  с разными site URL. Build IDs: staging
  `bXG0hOp0zPZs7O8HZaerF`, production
  `hDsyU_OEvSh7Hwb-GM3Xn`.
- Full public audit: 62 production pages и sitemap URLs, 57 images и
  11 Next assets прошли без failures; canonical, robots и metadata не
  содержат staging URL, noindex, active HTTP resources или 5xx.
  Staging остаётся HTTP 200 и сохраняет `X-Robots-Tag: noindex`.
- `Morocco&#8217;s Tower Reaches for Tomorrow` теперь отображается как
  `Morocco’s Tower Reaches for Tomorrow` на странице, в cards, title,
  Open Graph и JSON-LD; literal entity на staging/production не
  обнаружена.
- Final frontend rollback:
  `/root/revelations-frontend-final-before-20260718-1025-2175d3f`.
  Предшествующий полный frontend backup также сохранён в
  `/root/revelations-frontend-postlaunch-20260718-1010-501796b`.

## Final deterministic-evidence AI smoke

- После production deploy выполнен ровно один разрешённый OpenAI
  request с process-local enabled flag. Постоянный resolver до и после
  теста возвращает `requests_enabled=false`.
- Request использовал disposable Tech candidate 227 и draft 228,
  deterministic evidence contract и production model. OpenAI
  transport был вызван ровно один раз; retry не выполнялся.
- Ответ отклонён до draft update и private version backup с
  `invalid_fact_check_evidence: Fact-check evidence could not be
  verified.` Новые evidence IDs дошли до server resolution; по
  фактической ветке validation оставшийся blocker — model claim не
  найден как точный substring в сгенерированных editorial fields.
  Validation не изменялась и fuzzy matching не добавлялся.
- Candidate и draft возвращены в Trash. Baseline title/content/excerpt
  и актуальный Human Review не изменились; active private versions —
  0, generation lock отсутствует, публикаций и AI run artifacts нет.
- Оставшаяся проблема: production AI generation всё ещё блокируется
  строгой проверкой точного использования fact-check claim. Следующее
  изменение контракта или validation требует отдельного продуктового
  решения и нового явно разрешённого API test.

## SEO Stage 1A 2026-07-19

- Frontend pagination теперь получает весь published CMS collection:
  endpoint pagination metadata используется при наличии, а
  empty/incomplete-page fallback и repeated-page guard не допускают
  бесконечный loop. Legacy fallback merge не менялся.
- Main sitemap включает canonical public static/section/article URLs
  без duplicates и fake current dates. Section `lastModified` берётся
  из newest article; article date использует modified с publication
  fallback. Добавлен `/news-sitemap.xml`: только News за последние 48
  часов по publication date, Google News XML с escaping и cache 300 s.
- Общий SEO helper нормализует site URL и абсолютные asset URLs.
  Cover Image используется для Open Graph/Twitter; logo применяется
  только как social fallback и publisher logo, не как Article image.
- Article JSON-LD различает NewsArticle/Article, truthful author,
  timestamps and cover image; добавлен BreadcrumbList. Root layout
  публикует один stable WebSite/Organization graph.
- Выполнены `npm test` (18 passing), `npm run lint` и `npm run build`.
  Build запускался локально вне sandbox из-за Turbopack process/port
  restriction и успешно сгенерировал `/news-sitemap.xml`. Deploy,
  SSH/scp, production CMS/frontend changes и external API calls не
  выполнялись.

## SEO Stage 1B-1 production rollout 2026-07-18

- Развёрнут frontend commit `daa026275321e899e4ca105bae80659cd909d907`
  (`Build SEO foundation and social previews`). Local `npm test`
  (18 passing), ESLint и production standalone build прошли; build
  запускался с production public site/API endpoints, без staging URL в
  runtime output. Production build ID изменился с
  `hDsyU_OEvSh7Hwb-GM3Xn` на `fbnR1W5HI1kT-fjAKO_d3`.
- Полный rollback backup создан до переключения:
  `/root/revelations-seo-stage1a-before-20260718-215101`; он содержит
  release archive, service unit, active vhost, checksum manifest,
  прежний build ID и rollback procedure без secrets. Previous release
  также сохранён в
  `/var/www/revelations-production.previous-seo-stage1b-20260718-215930`.
- Атомарно переключён только `/var/www/revelations-production` и
  перезапущен только `revelations-production.service`. Service active,
  listener `127.0.0.1:3002`, loopback и public homepage возвращают 200.
  Nginx, TLS, DNS, staging, WordPress CMS/MU plugins и база не менялись.
- Live main sitemap: HTTP 200, `application/xml`, XML programmatically
  parsed; 62 canonical production URLs, 50 article URLs и 50 published
  CMS articles, duplicates/invalid URLs/5xx — 0. Host crawl 64 routes
  и 11 Next assets прошёл без 5xx, noindex, staging leakage или
  unavailable assets. HTTP и HTTPS www сохраняют direct 301 на apex с
  path/query.
- News sitemap: HTTP 200, valid Google News namespace, 0 items. Это
  корректно: на момент audit не было News с publication date за
  последние 48 часов; empty XML remains valid. Поэтому publication name
  и language отсутствуют как нет publication nodes.
- Проверены live samples с cover: News
  `elys-life-to-host-private-launch-event-in-dubai` и People
  `daria-barkova-art-profiling-turns-the-inner-world-into-a-visual-map`.
  Обе страницы 200; canonical, article OG/Twitter metadata,
  `summary_large_image`, actual CMS cover URLs (200), timestamps,
  NewsArticle/Article и BreadcrumbList присутствуют; raw HTML entities
  в title/description не найдены. Среди 50 current published CMS
  articles sample без Cover Image отсутствует, поэтому branded fallback
  in live HTML не был exercised in this audit.
- Homepage/site graph и sampled article JSON-LD render server-side;
  audit found types `graph`, `NewsArticle`/`Article` and
  `BreadcrumbList`. Staging remains 200, service active and retains
  `X-Robots-Tag: noindex, nofollow, noarchive`.
- Nginx active; no Nginx error-log lines after deploy. The only service
  journal `Failed` line is the intentional stop of the previous Node
  process (exit 143) during `systemctl restart`; new process immediately
  started and remains active, with no restart loop. Disk free: 39G.
- Не выполнялись CMS/database writes, OpenAI requests, GSC submission,
  staging changes or functional code changes. No automatic rollback was
  needed. Remaining SEO limitation: fresh News and no-cover social
  fallback need a later live recheck when matching published data exists.

## CMS Publication Review Synchronization and Single Category

- Production read-only diagnosis of draft 214: `post`/`draft`, one `news`
  category, Featured Image 285, current review hash equals stored hash,
  review is current, 312 words; no AI review metadata keys were present.
  The legacy common hash cannot identify a historical changed field.
- Read-only category audit: 50 published posts have exactly one category;
  zero/multiple/Uncategorized and draft/pending multi-category records are
  absent. Used editorial slugs: news, people, tech, places, unspoken,
  podcast. No production data was changed.
- Local implementation adds per-field review fingerprints, exact incoming
  field comparisons, single-category Gutenberg select and server category
  validation. Featured Image remains excluded from review hashes but is
  required by publication readiness. Legacy common-hash records remain
  fail-closed and compatible when their hash matches.
- Local Node publication-contract diagnostics: 12 passed. Remote PHP 8.5.4
  lint passed for four modified PHP files; full isolated AI regression suite
  passed. No deploy, CMS/DB write, OpenAI, scanner, frontend or staging
  action occurred.

## CMS editorial publishing production rollout 2026-07-18

- Deployed functional commit `0d0dc3173a04857457a4b18872a23f63d2a4bed1`
  to `/var/www/revelations-cms/public/wp-content/mu-plugins`: category
  contract, editorial review, publish gate, CMS core and editor JS only.
  Live SHA-256 values matched Git source for all five files; owner/mode is
  `root:www-data` and `0640`.
- Rollback backup:
  `/root/revelations-editorial-publishing-before-20260718-223341`.
  It preserves four former files, their checksum/permission manifest,
  baseline health and a procedure that removes the newly added category
  contract on rollback.
- PHP 8.5.4 lint passed for every changed PHP file and all 41 production
  MU-plugin PHP files. CMS health/articles/sections returned HTTP 200;
  PHP-FPM and Nginx stayed active, with no new fatal/parse/warning log
  matches. CMS login remains Basic-Auth protected (HTTP 401 unauthenticated).
- Runtime registry resolves six existing allowed terms: News, People, Tech,
  Places, Unspoken and Podcast. Uncategorized is not allowed (and has no
  current production term). Synthetic empty/multiple/not-allowed inputs
  returned their expected category codes; deployed JS syntax and the
  publication contract diagnostic passed 12/12.
- Read-only draft 214 remained `draft`, category `news`, no tags, Featured
  Image 285 and current editorial review. Its modified timestamp and
  stored/current common hash stayed unchanged; legacy absence of per-field
  hashes remains valid. Editor data exposes six editorial categories,
  saved category/tags/displayed author and current review state.
- Repeated category audit remains: published 0/50/0 for zero/one/multiple;
  Uncategorized and draft/pending multiple-category records remain 0.
  Persistent AI generation remains disabled. No post, metadata, category,
  DB, OpenAI, scanner, frontend or staging change occurred; no publication
  or review action was executed.
- Manual next check: open draft 214, confirm the standard category checklist
  is absent and a single Editorial category select shows News; do not save
  or publish during that visual verification. Deploy rollback path is the
  backup above; no rollback was required.

## REST editorial publication double-gate hotfix

- Production diagnosis of draft 214 proved that review status, common hash
  and all nine per-field fingerprints were current. The exact saved proposal
  passed; only the internally slashed Gutenberg content produced a false
  `content` difference in the classic fallback.
- `revelations-editorial-publish-gate.php` now treats
  `rest_pre_insert_post` as the single REST publication authority. Its
  `wp_insert_post_data` fallback detects REST through
  `wp_is_serving_rest_request()` with a `REST_REQUEST` compatibility
  fallback, including autosaves, and returns without a second comparison.
  Classic editor and WP-CLI retain their guard; title/content/excerpt are
  normalized through `wp_unslash` before comparison.
- Focused isolated REST diagnostic: 6/6 passed, including unchanged REST
  acceptance, fallback bypass, slashed-content reproduction/normalization,
  classic/WP-CLI protection and a real content-change block. Node publication
  contract diagnostics: 18/18; PHP lint and full isolated AI regression suite
  passed. No production CMS, DB, OpenAI, scanner, frontend or staging change
  occurred. The hotfix is committed/pushed but requires a separate CMS deploy.

## REST publication hotfix production rollout retry 2026-07-18

- Successfully deployed commit
  `ccf78f17d8ecc1b648cc6d208565c6c499c02cac` to exactly one file:
  `revelations-editorial-publish-gate.php` in the production MU-plugin
  directory. Source and live SHA-256 matched
  `661187b46d1945f81fd0c853b0ca27fadeeeae5a2f2c1914054ee498ad928960`.
- New rollback backup:
  `/root/revelations-rest-publish-gate-retry-before-20260718-233106`.
  The earlier attempt was safely rolled back because of a shell validation
  bug; this retry used `cut -d ' ' -f1`, not `awk`, for every SHA read.
- Live target lint and lint of all production MU-plugin PHP files passed;
  owner/mode is `root:www-data 0640`. CMS health, articles and sections
  endpoints returned HTTP 200; PHP-FPM and Nginx remained active with no new
  fatal or parse errors.
- Focused diagnostic against the exact deployed file passed 6/6. It confirms
  REST bypass, unchanged REST acceptance, slashed-text normalization,
  classic/WP-CLI fallback and real-change blocking.
- Draft 214 remained `draft`, category `news`, `reviewed/current`, with nine
  fingerprints, Featured Image 285, matching hashes, and unchanged modified
  and review timestamps. No WordPress/DB writes, publication attempt, OpenAI
  request, scanner run, frontend or staging change occurred.
- Next manual step: the user may open draft 214 and publish it through
  Gutenberg when ready; do not save unrelated changes before that action.

## SEO Stage 1B-3: News sitemap declaration

- Live audit of the new News article passed: the main sitemap contains all 63
  URLs and the new article exactly once; the News sitemap contains the same
  article exactly once. Both dynamic sitemaps updated through Next ISR without
  a new build.
- Local frontend source now declares both existing sitemap endpoints in
  `robots.txt`: `/sitemap.xml` and `/news-sitemap.xml`. This changes only the
  crawler declaration, not sitemap content, generation or caching.
- Production deploy and GSC submission have not been performed.

## SEO Stage 1B-3 production rollout 2026-07-19

- Deployed frontend source commit
  `8add3663e0781647bf4ee2a64014b53bdd039b8a`
  (`Declare both sitemaps in robots`).
- Next.js frontend was rebuilt and switched atomically. The only functional
  frontend change was `app/robots.js`.
- Previous build ID:
  `fbnR1W5HI1kT-fjAKO_d3`.
- Active build ID:
  `Bq7-JrvFPKzqBxWBL-8T8`.
- Rollback backup:
  `/root/revelations-robots-stage1b3-retry-before-20260719-002057`.
- Previous release:
  `/var/www/revelations-production.previous-robots-retry-20260719-002057`.
- Production `robots.txt` now declares both:
  `https://revelations.me/sitemap.xml` and
  `https://revelations.me/news-sitemap.xml`.
- Homepage, News section, published Apple Intelligence article, robots,
  main sitemap and News sitemap returned HTTP 200.
- Main sitemap contained 63 unique URLs and the target article exactly once.
  News sitemap contained the target article exactly once with all required
  Google News fields.
- Production service remained active on port 3002. There were zero critical
  Nginx errors after the deployment timestamp; earlier reported matches were
  historical log entries.
- Staging retained `noindex, nofollow, noarchive`.
- CMS, WordPress, database, OpenAI, editorial scanners and Google Search
  Console were not changed. No rollback was required.
- Next stage: verify both sitemaps and request indexing of the new article in
  Google Search Console.

## SEO Stage 2A-1 frontend cache revalidation

- Frontend CMS fetches share cache tag `revelations:cms:articles` while
  retaining the 60-second TTL fallback. The signed POST `/api/revalidate`
  contract accepts only bounded v1 editorial events, HMAC-SHA256 over exact
  `timestamp.raw-body`, and timestamps within 300 seconds.
- Valid events invalidate the tag with `{ expire: 0 }` and only canonical
  shared, article-slug and editorial-section paths. The Stage 2A-3 rollout
  completed the earlier pending sender, secret, deploy and smoke scope. Built
  runtime checks passed without a secret (503) and with a temporary
  process-only secret (401 invalid, 200 valid and repeated valid request).

## SEO Stage 2A-2 WordPress revalidation sender

- Added isolated sender source for signed frontend invalidation. It reads only
  `REVELATIONS_REVALIDATION_URL` and `REVELATIONS_REVALIDATION_SECRET`, sends
  HMAC-SHA256 over exact `timestamp.raw_body`, and is fail-open for config,
  JSON and HTTP errors. Payload contains public state only, never article text
  or personal data.
- Request-local pre-update snapshots preserve old section, then take-and-clear
  after save. Public transitions emit publish, update or unpublish; direct
  published deletion emits delete, while trash deletion emits no second event.
  Duplicate state events are suppressed only in the current request.
- Isolated PHP lint passed and sender diagnostic passed 60/60.

## SEO Stage 2A-3 cache revalidation production rollout

- Production commit `aff28ffdb7ff1e5f67c71b447fffefa41272bfb2` is deployed;
  active frontend build is `EE-Mukl8iXKcvOLhMQUxo`. The frontend endpoint and
  WordPress MU sender `revelations-frontend-revalidation.php` are live.
- One shared HMAC secret is provisioned only in protected runtime
  configuration: `/etc/revelations-production-revalidation.env`, the systemd
  drop-in `/etc/systemd/system/revelations-production.service.d/10-revalidation.conf`,
  and WordPress `wp-config.php`. Its value is not recorded here.
- GET `/api/revalidate` returned 405. A real signed webhook smoke succeeded
  with `path_count=4`, revalidating `/`, `/archive`, `/sitemap.xml` and
  `/news-sitemap.xml`; no publication or database write occurred.
- Frontend TTL remains 60 seconds as fail-open fallback. Staging retains
  `noindex`; rollback was unnecessary. Backups:
  `/root/revelations-stage2a3-before-20260719-111439` and
  `/var/www/revelations-production.previous-stage2a3-20260719-111439`.

## SEO Hotfix 2A-4: production URL and sharing metadata

- Root cause of the production-domain regression: the active production
  artifact `EE-Mukl8iXKcvOLhMQUxo` was built with
  `NEXT_PUBLIC_SITE_URL=https://staging.revelations.me`. This propagated to
  sitemap, canonical URLs, JSON-LD and the server-provided ShareButton URL.
- `ShareButton` now uses the browser's `origin + pathname` as its primary
  value, omitting query strings and fragments. A validated server URL is only
  a browser-unavailable fallback; clipboard denial leaves the control usable.
- Static sitemap routes now use one metadata helper so canonical and
  route-specific `og:url` remain identical. Article metadata already used
  its route-specific canonical and retains that contract.
- `npm run build:production` explicitly sets the production public site URL
  and CMS API URL without changing `.env.production`, then rejects an artifact
  containing the staging domain. Local production build
  `3gTn0QmgvZF1xhmocY2w2` passed the guard; tests 32/32 and ESLint passed.
- Production frontend deployment succeeded from commit
  `8f4c4693a2abc78bb441d04230d68e08cb94a930`. The old build
  `EE-Mukl8iXKcvOLhMQUxo` was atomically replaced by
  `3gTn0QmgvZF1xhmocY2w2`; candidate and live sitemap audits each passed
  63/63 URLs, with zero wrong canonical, `og:url` or staging-domain findings.
- Rollback release:
  `/var/www/revelations-production.previous-2a4-20260719-141000`.
  Backup: `/root/revelations-production-2a4-before-20260719-141000`.
  Rollback was not required. Production service, port 3002, Nginx syntax,
  CMS public API and `GET /api/revalidate` (405) passed; the runtime secret
  file was present but never read or recorded.
- Staging service and release were unchanged, returned HTTP 200 and retained
  `X-Robots-Tag: noindex`. WordPress, database, OpenAI and scanners were not
  changed during this frontend hotfix.

## Editorial UI and YouTube rollout 2026-07-19

- Functional commits `1931123c73c122cf940ff7025251996718994ba1`
  (`Fix Unspoken scan progress handling`) and
  `1ae2f243753ffb54fda80417245e538b89924d5d`
  (`Embed YouTube videos in articles`) are deployed. The Unspoken scan action
  now uses the existing shared long-action progress handler; the root cause
  was its omission from that handler's supported action registry.
- The production MU-plugin deployment changed only
  `revelations-editorial-scan-ui.php`; backup:
  `/root/revelations-editorial-ui-ai-before-20260719-142128`.
  Persistent manual AI generation is enabled in protected WordPress runtime
  configuration. Readiness is `Ready`, model is `gpt-5.6-luna`, and requests
  are enabled. Enablement performed zero OpenAI requests, scanner runs,
  publications and database writes.
- Articles now support canonical YouTube `watch`, `youtu.be`, `shorts` and
  `embed` URLs. The initial page renders a thumbnail from `i.ytimg.com` and a
  retained external `Watch on YouTube` link (`noopener noreferrer`); click
  replaces the preview with an autoplaying `youtube-nocookie.com` iframe.
  Invalid URLs render no empty watch block. Local diagnostics passed: action
  UI 32/32, YouTube parser 3/3, full Node suite 35/35 and ESLint.
- Frontend source commit `1ae2f243753ffb54fda80417245e538b89924d5d` was
  rebuilt and atomically deployed. Previous build
  `3gTn0QmgvZF1xhmocY2w2` is retained at
  `/var/www/revelations-production.previous-youtube-20260719-144133`; backup:
  `/root/revelations-production-youtube-before-20260719-144133`. Active build:
  `eJZmpMkybdDTtgVtfEn7r`. No rollback was required.
- Candidate and live audits each passed all 64 current sitemap URLs (the
  count increased from the earlier 63 through published CMS content): HTTP
  200, zero canonical errors, zero `og:url` errors and zero staging URL
  leakage. `robots.txt` declares both production sitemaps; the News sitemap
  has no staging URL; CMS public API returned 200; `GET /api/revalidate`
  returned 405 and the protected runtime secret was confirmed loaded without
  reading its value.
- Published YouTube article
  `bwiga-will-be-held-in-montenegro-again-in-september` verified the `youtu.be`
  form with video ID `R7oU8BMeStQ`: preview and `i.ytimg.com` thumbnail are
  present, initial HTML has no iframe, and the external fallback link remains.
  The click-to-load and `allowFullScreen` client contract is covered by the
  local component tests.
- Staging was unchanged: service active, homepage HTTP 200,
  `X-Robots-Tag: noindex`, build `bXG0hOp0zPZs7O8HZaerF`. No CMS, database,
  OpenAI, scanner or staging mutation occurred during this rollout.

## SEO Stage 2B-3: editorial taxonomy and Related scoring

- Approved taxonomy v2 was materialized as the normalized, deterministic
  `data/seo/editorial-taxonomy-v2.json`: 52 assignments, 9 topics, 3 series,
  6 manual Related override sources, no target-graph orphans and two
  review-required/public-topic exclusions. Automatic Related suggestions were
  not stored as manual CMS configuration.
- Prepared, **not deployed**: separate WordPress topic, series and location
  taxonomies; primary-topic, ordered manual Related, public-topic-eligibility
  and taxonomy-status metadata; safe editor meta box and additive public API
  fields. Existing category, tags, content, publication and API pagination
  contracts remain unchanged.
- The importer is dry-run by default and validates schema, all 52 slugs,
  definitions, manual target references and review exclusions before any
  possible write path. Apply needs explicit flags and was not run.
- Frontend normalization has safe legacy defaults. Related selection is
  deterministic: ordered manual overrides, same series, primary/secondary
  topic intersections, same section, date proximity and slug tie-break.
- Tests: taxonomy map/Related Node diagnostics 4/4; full Node suite 39/39;
  ESLint passed; isolated remote PHP lint 4/4 and taxonomy diagnostics 8/8.
  CMS DB, production, staging, importer apply, OpenAI, scanners and
  publications remain unchanged (all zero).

## SEO Stage 2B-3A0: production-aware importer hardening

- The prior CMS deploy was stopped before mutation because the first importer
  could validate JSON but could not resolve production WordPress slugs.
- The importer is now prepared, **not deployed**, for an explicit
  `--wordpress-root=/path` read-only dry-run. It resolves exact `post_name`
  records across statuses, validates manual target IDs/order, reports term and
  assignment plans, and compares deterministic before/after state fingerprints.
- Static validation without a bootstrap remains available; production-aware
  mode requires a valid bootstrap. Both modes report `db_writes=0`; apply was
  not run. Isolated PHP diagnostics 8/8, full Node suite and ESLint passed.
  Production, CMS DB, frontend and staging remain unchanged.

## SEO Stage 2B-3A: taxonomy CMS dry-run

- Deployed only `revelations-editorial-taxonomy.php`,
  `revelations-public-api.php`, `import-editorial-taxonomy.php` and the
  approved map at `public/data/seo/editorial-taxonomy-v2.json`. Backup:
  `/root/revelations-taxonomy-before-20260719-164950`; rollback was not
  required.
- Production-aware importer dry-run resolved 52/52 assignments with no
  missing, duplicate or conflicting slugs; planned 9 topics, 3 series and 19
  locations, resolved 6 manual sources/18 targets and 2 public-topic
  exclusions. Before/after fingerprint matched; `db_writes=0` and apply was
  not run.
- All production MU plugins passed lint. CMS health/API and frontend returned
  HTTP 200; additive API taxonomy fields return safe legacy defaults. Robots
  remained canonical, AI readiness stayed `ready` without requests, and
  staging retained `noindex`. No frontend deploy, DB write, scanner,
  publication, OpenAI request or staging change occurred.

## SEO Stage 2B-3B0: importer apply hardening

- A pure taxonomy diff planner now reports term creation, relationship add/remove
  and meta add/update operations with a computed `planned_changes` total.
  Isolated planner diagnostics cover empty and partial state, zero post-apply
  plan, manual order and dry-run zero writes. This hardening is not deployed;
  no production apply or database write was performed.

## SEO Stage 2B-3B0: final diff-plan integration

- The production-aware importer now builds canonical expected state from the
  approved map and resolved WordPress IDs, reads only its owned taxonomy/meta
  state, and returns the real `terms_create`, relationship add/remove and
  meta add/update plan. `planned_changes` is computed from that plan rather
  than assigned a constant.
- Apply accepts the already-built plan, mutates only its listed operations,
  counts successful writes, then rebuilds the plan and fails if any operation
  remains. Its contract is dry-run `db_writes=0`, first apply nonzero planned
  and actual writes, and an idempotent second apply with zero plan/writes.
  Content, status, categories, ordinary tags, modified timestamps and manual
  Related metadata on articles without an override remain outside the plan.
- Isolated remote PHP lint passed for importer and diagnostic; stubbed
  diff-plan diagnostics passed 8/8. Full Node suite (39 tests), ESLint and
  `git diff --check` passed. This hardening remains local and not deployed:
  production CMS, database, frontend and staging are unchanged; no importer
  apply was run.

## SEO 2B-3B primary topic storage hardening

- Importer canonical diff продолжает использовать topic slug.
- Текущее WordPress meta `_revelations_primary_topic` преобразуется из term ID в slug перед сравнением.
- Apply преобразует запланированный primary-topic slug обратно в реальный term ID перед `update_post_meta`.
- Isolated PHP lint прошёл для importer и focused diagnostic.
- Focused diagnostics: 9 passed, 0 failed.
- Full Node suite: 39 passed, 0 failed; ESLint и `git diff --check` прошли.
- Production deploy, WordPress bootstrap, importer apply и DB writes не выполнялись.

## SEO 2B-3B production taxonomy import

- Production backup: `/root/revelations-taxonomy-before-20260719-180823` (DB export 4.4 MB plus importer and audit snapshots).
- Production importer SHA-256: `ec6d0286328e0ba33062f1ce82c600c9ef272c06c6b8add51649a67bf1fba34a`; matches local importer.
- Pre-apply dry-run: 52/52 resolved, 0 missing, 0 duplicates, 0 conflicts, 350 planned changes, 0 writes.
- One confirmed production apply completed successfully: exit 0, 286 actual mutation calls.
- Post-apply dry-run is idempotent: 0 planned changes, 0 writes.
- Protected-state audit: 52 articles checked, 0 differences in content, status, modified date, categories, tags and non-overridden manual Related metadata.
- Primary-topic storage audit: 52/52 valid WordPress term IDs; taxonomy totals are 9 topics, 3 series and 19 locations.
- CMS health is OK; homepage and audited article return HTTP 200; canonical matches; robots contains both production sitemaps.
- No frontend or staging deploy and no paid AI requests were performed.

## Legacy WordPress tag cleanup

- Read-only audit found 6 legacy `post_tag` terms with one assignment each.
- Kept product-specific tags `Canon` and `EOS R6 V` on article 83.
- Removed redundant tags `architecture`, `futurism`, `Morocco`, and `video technology`.
- Article 84 now has no legacy WordPress tags; its classification remains covered by Topics and Locations.
- Backup: `/root/revelations-post-tags-before-20260719-184356/post-tags-before.json`.
- Final verification: 2 remaining tags, CMS HTTP 200, public site HTTP 200.

## Entity-only WordPress tag assignment

- Approved map: `wordpress-cms/data/seo/editorial-post-tags-v1.json` (SHA-256 `9ac7784a156353c4d0187cf8bca4c7577622c3731c7303e2dad0f1acdcd23ca8`).
- Policy: `post_tag` is reserved for named people, organizations, brands, products, initiatives and venues; Topics, Series and Locations are not duplicated.
- Production preflight: 52/52 articles resolved, 0 errors, 0 drift, 182 planned changes and 0 pre-apply writes.
- The first transactional attempt detected an HTML-entity comparison mismatch on `Dolce & Gabbana` and rolled back.
- Recovery verified the exact pre-apply state before retrying.
- The corrected production apply created 86 terms and updated 42 posts, adding 96 relationships and removing none.
- Final state: 88 unique tags, 98 assignments, 43 tagged articles and 9 generic articles without entity tags.
- Existing `Canon` and `EOS R6 V` tags were preserved.
- Post-apply audit: 0 remaining changes and 0 protected-field differences.
- Backup and audit directory: `/root/revelations-post-tags-v1-20260719-190829`.
- CMS and public site returned HTTP 200. No frontend/staging deploy and no paid AI request were performed.

## SEO Stage 2B-4: public taxonomy and Related (2026-07-20)

- Added canonical public routes for `/topics/[slug]`, `/series/[slug]`, `/locations/[slug]`, and `/tags/[slug]`.
- Topic hubs exclude articles marked non-public or `needs-editorial-review`; Series hubs require one published article; Location and entity-tag hubs require at least two published articles.
- Current production-CMS dataset prerenders 9 Topic, 3 Series, 4 Location, and 9 Tag hubs.
- Hub pages include route-specific metadata, canonical and Open Graph URLs, `BreadcrumbList`, `CollectionPage`, stable article ordering, and 404 handling for unknown or ineligible terms.
- Article pages show primary Topic, secondary Topics, Series, Locations, and entity tags. Terms without an eligible public hub remain plain text rather than thin internal links.
- Related Articles use ordered manual overrides, then Series, primary Topic, secondary Topic intersections, section, publication-date proximity, and slug tie-breaking. Results are published-only, unique, exclude the current article, and are capped at three.
- The main sitemap includes eligible taxonomy hubs; the Google News sitemap remains article-only.
- Signed revalidation v1 remains backward-compatible and now accepts bounded canonical old/new taxonomy paths. HMAC, timestamp, payload-size, fail-open sender, and private-payload safeguards remain in place.
- Production build temporarily rewrites only the two public production URL variables in `.env.production`, restores the developer file after the build, and verifies that the standalone artifact contains production rather than staging URLs.
- Validation: full Node test suite passed, ESLint passed, production build passed, all four taxonomy route families were present, and remote isolated PHP lint/diagnostics passed.
- No frontend, staging, CMS, or production deploy was performed. No production database writes, OpenAI requests, scanners, or publication actions were performed.

## SEO Stage 2B-4 production rollout (20260719-215226)

- Production frontend commit `9ae0f86bd2de6ba57ca02687f42b07c1f08fe58b` was rebuilt from the live CMS without local fallback and deployed through an isolated loopback candidate followed by a directory switch.
- Previous frontend build: `eJZmpMkybdDTtgVtfEn7r`. Active frontend build: `lrWLL98gDeeCnPiNg7HuL`.
- Frontend rollback release: `/var/www/revelations-production.previous-seo2b4-20260719-215226`.
- Production backup directory: `/root/revelations-seo2b4-before-20260719-215226`.
- The production MU plugin `revelations-frontend-revalidation.php` was updated to SHA-256 `09f9387f43e7b774ecfb3106276ca1cc07c957e09d57bd93834615280f46f745`; all production MU-plugin PHP files passed lint.
- Candidate and public checks passed for the homepage, both sitemaps, representative Topic, Series, Location and Tag hubs, a published article, canonical metadata, JSON-LD, article taxonomy, Related Articles and unknown-hub 404 behavior.
- The public sitemap contains representative taxonomy hubs and the Google News sitemap contains no taxonomy routes. Public responses contain no staging-domain leakage.
- The frontend service and loopback port are healthy, Nginx syntax passed, CMS health returned HTTP 200, and GET on the signed revalidation endpoint remained HTTP 405.
- Staging remained HTTP 200 with `X-Robots-Tag: noindex`.
- No WordPress post, metadata, taxonomy or database writes were performed. No OpenAI request, scanner run or publication action occurred.

## Daria Barkova taxonomy correction (20260720-090807)

- Removed the erroneous secondary Topic `ai-data` from `daria-barkova-art-profiling-turns-the-inner-world-into-a-visual-map`; its primary Topic remains `creative-industries-media` and its only secondary Topic is `startups-founders-investment`.
- The user had already removed the relationship manually in production CMS. A production-aware importer dry-run against the corrected map resolved 52/52 articles and reported zero planned changes and zero writes.
- Corrected approved map commit: `3ec721118ad5d8b1857615e418719defa21d5439`.
- Production map SHA-256 changed from `ad142158eb3640dddcbfcb0e17181bef0b35846175b9d4c42efa02de889aa5d2` to `55e116e8442acece86be5cb1df6af8987328225e72ec2d6758e9d84c55cf7c3e`.
- Production map backup: `/root/revelations-taxonomy-map-before-20260720-090807`.
- The public AI & Data hub no longer contains the Daria article, and the article page links to Creative Industries & Media without linking to AI & Data.
- CMS health and the public AI topic page returned HTTP 200. No importer apply, database write, frontend deploy, OpenAI request, scanner run or publication action occurred.

## SEO 2C taxonomy governance and editorial corrections (20260720-103727)

- Added the permanent read-only command `npm run audit:taxonomy`, backed by `scripts/audit-editorial-taxonomy.py`.
- The audit canonicalizes HTML entities before entity-tag comparison, recognizes `OpenxAI` as an AI signal, and records narrow human-reviewed semantic decisions in `data/seo/editorial-taxonomy-audit-decisions-v1.json`.
- Corrected the Related fixture test to enforce the actual per-source contract (three unique bounded results) without imposing synthetic global inbound coverage, which is not guaranteed by the ranking algorithm.
- Corrected `the-co-founder-divorce-nobody-talks-about`: primary Topic is now `startups-founders-investment`; `future-work-leadership` remains secondary.
- Corrected `quantum-computing-is-finally-trying-to-be-useful-starting-with-medicine`: removed the unsupported secondary Topic `ai-data`; primary remains `health-longevity-medtech`.
- Production importer dry-run planned exactly two changes: one Topic relationship removal and one primary-Topic metadata update. Apply completed with two mutation calls, then the production map was updated.
- Production backup and rollback: `/root/revelations-seo2c-governance-before-20260720-103744`.
- Production map SHA-256 changed from `55e116e8442acece86be5cb1df6af8987328225e72ec2d6758e9d84c55cf7c3e` to `03485e7877f80fe31d939486dada83d5d99388f3d4ee8042f0a3dcec41f5bd40`.
- Post-change governance result: 52 articles, critical=0, high=1, drift=0. The only remaining review candidate is `33-qs-for-sergei-medvedev`, whose current public text is insufficient for deterministic validation.
- Public Topic hubs and both affected article pages converged to the approved state. No frontend deploy, staging change, OpenAI request, scanner run or publication action occurred.

## SEO 2C recovery completion (20260720-110313)

- The first SEO 2C governance run reached a valid production state and pushed commit `8570aed`, but its final cleanliness check detected generated Python bytecode after the push. The run's safety trap therefore restored the two affected production articles and the previous production taxonomy map.
- Recovery restored the local working tree exactly from pushed commit `8570aed`, then reapplied or verified the same approved two-change production plan.
- Recovery backup and rollback: `/root/revelations-seo2c-recovery-before-20260720-110336`.
- Production taxonomy map SHA-256 is `03485e7877f80fe31d939486dada83d5d99388f3d4ee8042f0a3dcec41f5bd40` (previous recovery-time SHA: `55e116e8442acece86be5cb1df6af8987328225e72ec2d6758e9d84c55cf7c3e`).
- Final live governance audit: 52 articles, critical=0, high=1, drift=0; the only manual review candidate remains `33-qs-for-sergei-medvedev`.
- Public Topic hubs and both affected article pages match the approved taxonomy state.
- Python cache artifacts are now ignored through `__pycache__/` and `*.py[cod]`.
- No frontend deploy, staging change, OpenAI request, scanner run or publication action occurred.

## SEO 3A hub content and metadata implementation

- Added curated English-language editorial content for all 25 currently eligible public taxonomy hubs: 9 Topics, 3 Series, 4 Locations and 9 entity Tags.
- Each approved hub now has a unique page-title input, a unique 90–180 character meta description and a visible 45–90 word editorial introduction.
- Hub eligibility, URL paths, article membership, ordering, sitemap behavior, canonical URLs and 404 rules were not changed.
- Hub metadata uses curated copy when available and retains the existing count-based fallback for future eligible terms without an editorial entry.
- Open Graph, Twitter metadata and CollectionPage JSON-LD use the same curated description.
- The visible page keeps the article count as a secondary collection label below the editorial introduction.
- Validation: full Node test suite passed, ESLint passed, git diff check passed and a production build completed with curated copy present and no staging-domain leakage.
- Commit and push are part of the approved local stage. Frontend deploy was intentionally not performed. CMS, database, staging, OpenAI, scanners and publication workflows were not changed.

## SEO 3A production rollout (20260720-125424)

- Frontend commit `fe25af912c5b7f34ee3a32116e0db282eeabbf20` was rebuilt from the live CMS and deployed through an isolated loopback candidate followed by an atomic same-filesystem directory switch.
- Previous build: `lrWLL98gDeeCnPiNg7HuL`. Active build: `5ICYzasjBNQx3eFA81CzK`.
- Frontend service: `revelations-production.service`; verified loopback port: ``.
- Production backup and rollback: `/root/revelations-seo3a-before-20260720-125822`.
- Previous release retained at `/var/www/revelations-production.previous-seo3a-20260720-125822`.
- Candidate and public verification passed for all 25 eligible taxonomy hubs, including curated introductions, titles, meta descriptions, canonical URLs, Open Graph, Twitter metadata, BreadcrumbList, CollectionPage, sitemap membership and unknown-hub 404 behavior.
- Homepage, a representative article, CMS health, Nginx syntax and the frontend service passed. GET on the signed revalidation endpoint remained HTTP 405.
- Runtime revalidation secret presence after restart matched the pre-deploy service state: `yes`; no secret value was read or recorded.
- Staging remained HTTP 200 with `X-Robots-Tag: noindex`.
- No CMS, taxonomy, WordPress post or database writes were performed. No signed revalidation POST, OpenAI request, scanner run or publication action occurred.

## SEO 3A production recovery (20260720-130910)

- The initial SEO 3A rollout switched production to build `5ICYzasjBNQx3eFA81CzK`, but its public verifier then reported persistent HTTP 404 responses for all 25 taxonomy hubs and a sitemap without taxonomy URLs.
- The rollout script incorrectly continued after those failed public checks and recorded documentation commit `6c384562352e0bfde20f83ccdaa9911d7c3839cd` as successful; that record is superseded by this recovery entry.
- Recovery mode: `runtime_env_restored`.
- Active build after recovery: `5ICYzasjBNQx3eFA81CzK`.
- Recovery backup: `/root/revelations-seo3a-recovery-before-20260720-130919`.
- Failed SEO 3A release location, when rollback was required: `/var/www/revelations-production.failed-seo3a-20260720-130919`.
- Public verification after recovery covered all 25 eligible hubs, the main sitemap, Google News sitemap, unknown-hub 404 behavior, homepage and a representative article.
- CMS health returned HTTP 200, GET on the signed revalidation endpoint remained HTTP 405, and staging remained HTTP 200 with `X-Robots-Tag: noindex`.
- No CMS, taxonomy, WordPress post or database writes were performed. No signed revalidation POST, OpenAI request, scanner run or publication action occurred.

## Permanent production deploy hardening

- Added permanent `scripts/deploy-production.sh` and
  `scripts/verify-production-release.py`.
- Production artifacts are now explicitly secret-free. Candidate verification
  uses only generated safe production URL variables; the active release
  `.env.production` is transferred server-side only after the candidate stops
  and is verified byte-for-byte before the switch.
- Candidate taxonomy inventory and metadata/schema signatures are captured from
  its actual sitemap. Public HTTPS must match the candidate manifest after the
  switch; there is no fixed 25-hub assumption.
- Public verification, CMS health, service, loopback, Nginx, revalidation GET
  and staging `noindex` failures now call an explicit fail path and trigger the
  timestamped rollback trap.
- Regression tests cover operation ordering, runtime-environment transfer,
  explicit fail-closed behavior, absence of non-loopback raw IPs and pipelines, sitemap-
  driven verification, Python verifier self-tests and Bash syntax.
- The pre-existing revalidation timestamp test now passes an explicit fixed
  `now` value to `validTimestamp`, eliminating a one-second boundary race
  without changing the 300-second production contract.
- This hardening stage changes repository tooling and documentation only. No
  frontend deploy, production runtime mutation, CMS/DB write, signed
  revalidation POST, OpenAI request, scanner run or publication occurred.

## Production deploy non-interactive execution hardening

- The guarded production deploy previously wrote all remote candidate and
  atomic-switch output only to a local file. In a non-interactive execution
  controller, that could make a healthy long-running remote phase appear
  stalled even while its retries continued.
- Remote output now streams to the controlling process and is retained in the
  local remote log at the same time. The deploy preserves the remote SSH exit
  status independently from `tee`, so a log-write failure cannot mask a remote
  deployment failure.
- SSH and SCP use the same batch-mode, bounded-connect and liveness options;
  host-key verification remains enabled. Major remote phases and verification
  retry attempts now emit concise progress markers.
- This execution-hardening change preserves all existing candidate, backup,
  atomic-switch, rollback and post-switch production safety semantics. No
  production deployment or CMS, database, staging, content or runtime change
  was performed.

## Production deploy prepare/deploy split

- A Codex execution-control session can disappear after a successful local
  build and before `UPLOAD_START`; in that observed case no SSH phase, remote
  backup, previous-release directory or production mutation occurred.
- Production deployment is therefore split intentionally. The mutation-free
  prepare command produces an ignored, immutable artifact and JSON manifest
  bound to the full synchronized commit. The short deploy command requires
  that exact prepared release and revalidates its manifest, checksum, build
  marker, production targets, staging-domain absence and prohibited-file
  absence before it can open an upload connection.
- Existing bounded SSH transport, streamed remote logging, candidate checks,
  backup, atomic switch, rollback and public verification remain unchanged.

## Production deploy macOS Bash compatibility

- The deployment Mac uses GNU Bash 3.2.57. The prepared-release deploy path
  originally used Bash 4 `readarray`, which failed locally before upload and
  therefore made no production mutation.
- Manifest values now use a Bash 3.2-compatible `while IFS= read -r` loop.
  A guarded `--validate-prepared-release` mode runs every local pre-upload
  check and exits before SSH/SCP, so this path can be tested without a
  production deployment. Prepared release artifacts are ignored by ESLint.

## Production upload execution-control hardening

- A subsequent guarded deployment reached `UPLOAD_START`, but execution
  control detached during a silent SCP transfer. Read-only inspection found
  only the archive in its remote temporary directory; no verifier, candidate,
  backup or release switch was created.
- SCP now runs through a Bash 3.2-compatible heartbeat wrapper that preserves
  the real child exit status. Upload completion is followed by a bounded,
  read-only remote check for both uploaded files and the archive SHA-256 before
  the remote candidate phase may begin.

## GitHub Actions production deployment

- Codex execution control can terminate SCP even while heartbeats are emitted,
  so the canonical production path is now manual GitHub Actions deployment:
  code push -> staging -> product-owner approval -> protected `production`
  environment workflow dispatch for an exact `admin-editorial` commit.
- The workflow installs dependencies once, prepares the immutable release once,
  and reuses the existing guarded candidate, backup, atomic-switch, rollback
  and public-verification script. SSH material exists only in GitHub Actions
  production environment secrets; the CMS is not part of the workflow.

## SEO 3B-1 topic discovery implementation

- Added an indexable `/topics` route driven exclusively by
  `getTaxonomyHubs('topics')`; no manual hub inventory or CMS setting was
  introduced.
- The page exposes all currently eligible Topic hubs with their curated
  descriptions and live article counts, canonical metadata, Open Graph,
  Twitter metadata, `BreadcrumbList`, `CollectionPage` and `ItemList`.
- `Topics` was added to `Footer → Explore`. `/archive` now contains a compact
  `Explore by topic` block generated from the same eligibility builder.
  Primary desktop/mobile navigation was intentionally unchanged.
- `/topics` was added to the main sitemap and to the signed revalidation path
  set so publication and taxonomy changes refresh its live counts and
  membership. The News sitemap remains article-only.
- Individual hub URLs, eligibility thresholds, taxonomy assignments, CMS/DB
  state, article ordering inside hubs and the two SEO 3B taxonomy-link
  mismatches were not changed.
- Focused Topics index tests, the full Node suite, ESLint, taxonomy governance,
  `git diff --check` and a production build must pass before commit/push.
- This implementation stage performs no frontend deploy, CMS/DB write,
  staging change, signed revalidation POST, OpenAI request, scanner run or
  publication.

## Production deploy loopback detector fix

- The first SEO 3B-1 rollout built and verified the isolated candidate, switched
  to build `Utda7Lo0wwFV6xG6KD_uc`, then failed at
  `loopback_port_not_found`.
- The fail-closed trap restored the previous production release:
  build `5ICYzasjBNQx3eFA81CzK`, frontend commit
  `fe25af912c5b7f34ee3a32116e0db282eeabbf20`. Public homepage returned 200,
  `/topics` returned 404, the old sitemap was restored, and rollback
  verification passed.
- Root cause: the permanent deploy script associated sockets with the release
  through process `cwd` or command-line text. The active Next.js listener
  belonged to the systemd unit cgroup but did not satisfy that path heuristic.
- The detector now collects the main PID and every PID in the recursive systemd
  cgroup, matches those PIDs against `ss -ltnpH`, and probes the resulting
  ports through loopback. The production state checker proved this method finds
  port 3002.
- Regression tests require cgroup-based ownership, reject the removed
  `cwd`/command-line heuristic, preserve the only allowed raw IP
  `127.0.0.1`, and verify the detector remains between service readiness and
  public manifest verification.
- This fix performs no frontend deploy, CMS/DB write, staging change, signed
  revalidation POST, OpenAI request, scanner run or publication. A separate
  retry is required after commit/push.

## Production deploy service-readiness retry fix

- The second SEO 3B-1 rollout again passed isolated candidate verification,
  switched to frontend commit
  `89a505b7c65994a243af3d4a5c700aad5b59dd7f`, and then failed at
  `service_ports_not_found`.
- Fail-closed rollback restored build `5ICYzasjBNQx3eFA81CzK` and commit
  `fe25af912c5b7f34ee3a32116e0db282eeabbf20`. Production homepage returned
  200, `/topics` returned 404, the old sitemap was restored, port 3002 was
  healthy, and rollback verification passed.
- Root cause: the post-switch code retried only `systemctl is-active`; after
  the first active result it performed one cgroup/socket lookup. The unit can
  be active before the Next.js child has joined the cgroup and opened its
  listening socket.
- The readiness gate now retries the complete sequence for up to 40 seconds:
  active unit → current MainPID and ControlGroup → recursive cgroup PIDs →
  matching `ss` listeners → successful loopback HTTP response.
- Empty socket results during startup are retryable. The deployment fails
  closed only when no healthy service-owned loopback listener appears before
  the deadline.
- Tests require the integrated retry gate and reject the former immediate
  `service_ports_not_found` and `service_loopback_health_failed` exits.
- This fix performs no frontend deploy, CMS/DB write, staging change, signed
  revalidation POST, OpenAI request, scanner run or publication. A separate
  production retry is required after commit/push.

## SEO 3B-1 taxonomy soft-404 root cause and fix

- The third SEO 3B-1 rollout passed candidate verification and service
  readiness, but public verification found four false missing hubs:
  `/series/mena-blockchain-week`, `/tags/arman-mamyan`,
  `/tags/d33-agenda`, and `/tags/dolce-gabbana`.
- The deploy failed closed and restored build `5ICYzasjBNQx3eFA81CzK` /
  commit `fe25af912c5b7f34ee3a32116e0db282eeabbf20`. Runtime environments
  matched and rollback verification passed.
- Read-only relocation diagnostics proved the failed release contained all
  expected prerender routes. The affected hubs initially rendered the
  application Not Found page, then recovered after repeated requests and a
  fresh runtime location. `/topics` remained healthy throughout.
- Root cause: `fetchCmsArticles()` caught any CMS request failure and returned
  an empty CMS collection. The taxonomy builder then used legacy-only data;
  threshold hubs absent from that fallback were treated as genuinely missing,
  producing cacheable `notFound()` renders.
- The CMS fetch policy now retries each request three times, uses a 15-second
  timeout, and rethrows the final error. It no longer turns transport failures
  into empty valid data. This lets Next.js retain the last successfully
  generated ISR output when regeneration fails and retry later.
- The implementation adds functional retry tests plus a source contract that
  forbids the former local-fallback-on-error behavior.
- No frontend deploy, CMS/DB write, staging change, signed revalidation POST,
  OpenAI request, scanner run or publication is performed by this repository
  fix. A separate production deploy is required after commit/push.

## SEO 3B-1 production rollout (20260720-162459)

- Production frontend commit `a94acab1a2a651a9701c9d784d93dfe517ede083` was deployed through the
  permanent fail-closed release pipeline. Active build:
  `Jz3vzoa0fWE0pkxTSsYSh`; service: `revelations-production.service`; verified loopback port:
  `3002`.
- Read-only post-deploy verification passed for the indexable `/topics`
  collection and all 25 eligible taxonomy hubs: 9 Topics,
  3 Series, 4 Locations and 9 entity Tags.
- `/topics` returns HTTP 200 with route-specific canonical, Open Graph and
  Twitter metadata, one H1, no `noindex`, and valid `BreadcrumbList`,
  `CollectionPage` and nested `ItemList` JSON-LD covering all 9 Topic hubs.
- The homepage footer exposes `Topics` under Explore. `/archive` exposes
  `Explore by topic`, links to `/topics`, and links to the same 9 eligible
  Topic hubs.
- The main sitemap contains `/topics` and the exact 25-hub taxonomy set. The
  Google News sitemap remains taxonomy-free, unknown taxonomy URLs return 404,
  and no staging-domain leakage was detected.
- CMS health returned HTTP 200. GET on `/api/revalidate` remained HTTP 405.
  Staging returned HTTP 200 with `X-Robots-Tag: noindex`.
- Runtime environment transfer was confirmed by deployment and remains present
  in the active release. Public candidate-manifest matching passed.
- Production backup: `/root/revelations-production-before-20260720-162459`. Previous release:
  `/var/www/revelations-production.previous-20260720-162459`.
- Read-only audit report: `/Users/admin/Downloads/revelations-seo-3b1-postdeploy-20260720-171812/seo-3b1-postdeploy-audit.md`.
- No CMS, WordPress post, taxonomy or database writes were performed. No signed
  revalidation POST, OpenAI request, scanner run or publication action occurred.

## Signed revalidation production smoke test

- A single controlled production HMAC POST was sent to `/api/revalidate`
  using the secret already present in the active frontend process environment.
  The secret and signature were not printed, stored in the repository or
  transferred to the local machine.
- Event ID: `seo-3b1-signed-revalidation-smoke-20260720-180106`. Synthetic payload version: `1`; action: `update`;
  taxonomy target: `/topics/ai-data`. No WordPress post was created or changed.
- The endpoint returned HTTP 200 with `ok=true`, `revalidated=true` and
  `path_count=6`. The affected set covered `/`, `/archive`,
  `/topics`, `/sitemap.xml`, `/news-sitemap.xml` and `/topics/ai-data`.
- After invalidation, `/topics` and `/topics/ai-data` regenerated
  successfully with HTTP 200, correct canonical/Open Graph URLs, no `noindex`
  and their required JSON-LD.
- Full public production verification passed again for all 25 taxonomy hubs,
  the main sitemap, the taxonomy-free News sitemap, unknown-hub 404 behavior,
  the homepage and a representative article.
- Active frontend remained commit `a94acab1a2a651a9701c9d784d93dfe517ede083`, build
  `Jz3vzoa0fWE0pkxTSsYSh`, service `revelations-production.service`. CMS health returned HTTP 200.
- Report: `/Users/admin/Downloads/revelations-signed-revalidation-smoke-20260720-180202/signed-revalidation-smoke.md`.
- Production mutation was limited to frontend cache/tag/path invalidation.
  CMS writes, WordPress post/taxonomy changes and database writes were `0`.
  Staging was unchanged. OpenAI requests, scanners and publication actions
  were not performed.

## SEO 3B-2 collection discovery implementation

- The post-SEO-3B-1 read-only audit found 90 sitemap URLs, 52 published
  articles and 25 eligible taxonomy hubs: 9 Topics, 3 Series, 4 Locations
  and 9 entity Tags.
- `/topics` reduced hubs discoverable only through articles from 25 to 16
  and hubs deeper than two clicks from 8 to 6. Article orphans, zero-inlink
  hubs, unreachable hubs, taxonomy mismatches and broken internal links were
  all zero. The remaining structural gap was that `/series`, `/locations`
  and `/tags` returned 404.
- SEO 3B-2 adds indexable collection roots for `/series`, `/locations` and
  `/tags`, while retaining `/topics`. All four routes use one shared server
  component and the existing `getTaxonomyHubs(type)` eligibility builder;
  no hub slug inventory or CMS setting is duplicated.
- `/tags` is presented publicly as `Entities` and exposes only currently
  eligible entity-tag hubs. It does not expose every WordPress tag.
- Each collection root has unique title/description metadata, canonical,
  Open Graph and Twitter data, plus `BreadcrumbList`, `CollectionPage` and
  nested `ItemList` JSON-LD. Cards show curated hub descriptions and live
  article counts with the existing safe fallback for future eligible hubs.
- Footer Explore links now include Topics, Series, Locations and Entities.
  Archive uses one compact four-collection discovery block derived from
  `buildAllTaxonomyHubs(articles)`.
- `/series`, `/locations` and `/tags` are added to the main sitemap and all
  four collection roots are included in signed revalidation. Primary desktop
  and mobile navigation remain unchanged.
- Individual hub URLs, eligibility thresholds, taxonomy assignments, article
  membership/order and the Google News sitemap contract are unchanged.
- Validation requires the full Node suite, ESLint, taxonomy governance,
  `git diff --check` and a production build with all four collection roots
  in the prerender manifest.
- This implementation performs no frontend deploy, production mutation,
  CMS/DB write, signed revalidation POST, staging change, OpenAI request,
  scanner run or publication action.

## SEO 3B-2 production rollout and final audit

- Frontend commit `b3d5b289e01c583c80e5e250e928bb6908ea76bd`
  was deployed atomically to production through
  `scripts/deploy-production.sh`.
- Active build: `uIm3yh6oLx8xm2GjHa8xQ`; service:
  `revelations-production.service`; healthy loopback port: `3002`.
- Rollback assets:
  `/root/revelations-production-before-20260720-185005` and
  `/var/www/revelations-production.previous-20260720-185005`.
- Runtime environment transfer succeeded. Candidate and public manifests
  matched. CMS health returned HTTP 200; GET `/api/revalidate` returned 405.
- The production release exposes four indexable collection roots:
  `/topics`, `/series`, `/locations` and `/tags` (`Entities`). Their hub
  inventory remains dynamic and driven by the existing eligibility builders.
- Footer Explore and Archive collection discovery are live. Primary navigation
  remains unchanged.
- Main sitemap contains 93 canonical public URLs: 52 published articles,
  25 eligible taxonomy hubs and the four collection roots. Hub inventory is
  9 Topics, 3 Series, 4 Locations and 9 entity Tags.
- The News sitemap currently contains zero URLs because no published article
  falls inside its active Google News time window; taxonomy URLs remain
  excluded from it.
- Final read-only public graph audit passed:
  article orphans `0`, zero-inlink hubs `0`, hubs linked only from articles
  `0`, hubs unreachable from home `0`, hubs deeper than two clicks `0`,
  taxonomy link/membership mismatches `0`, and broken internal links `0`.
- `/topics`, `/series`, `/locations` and `/tags` each returned HTTP 200 and
  passed metadata, canonical, Open Graph, Twitter and structured-data checks.
- Audit severity: high `0`, medium `0`, low `1`. The remaining low finding is
  a non-blocking slow-page observation recorded in the audit report.
- Audit report:
  `/Users/admin/Downloads/revelations-seo-3b2-postdeploy-20260720-190500/seo-3b2-postdeploy-audit.md`.
- Deployment and audit performed zero CMS writes, zero database writes, zero
  OpenAI requests and no staging change. No signed revalidation POST was
  performed during the deployment or final audit.

## SEO 4A: GSC legacy URL audit and robots cleanup

- Google Search Console drilldown exports contained 36 historical examples:
  9 crawled-not-indexed, 13 Google/user canonical mismatches, 2 former 5xx,
  1 former 404 and 11 former noindex URLs.
- Corrected live redirect walking found no current indexability defect in
  35 examples: 17 are resolved by permanent redirects, 15 obsolete URLs now
  return 404, and 3 current pages are live and indexable.
- The remaining `http://revelations.me/quisp` example now resolves through
  HTTPS to a clean 404 and is absent from the sitemap; no replacement or
  redirect is justified without a known equivalent page.
- `/admin/`, `/editorial-desk/` and `/private/` all resolve to current 404
  responses. They are not public frontend routes and are not protected
  resources; their robots exclusions are obsolete.
- Frontend source removes those three `Disallow` entries. The resulting
  robots policy allows crawling of the public frontend and continues to
  declare both production sitemaps.
- This source change is committed and pushed but not deployed in this step.
  Production, CMS, database, staging, signed revalidation and OpenAI remain
  unchanged.

## Homepage all-section feed

- Обнаружено, что `app/page.jsx` запрашивал только
  `getArticlesBySection('news')`, поэтому опубликованные материалы других
  разделов не могли появляться на главной.
- Homepage переведён на `getPublishedArticles()` и показывает десять
  последних опубликованных материалов из всех разделов.
- Hero, secondary cards и latest feed показывают фактический section.
- Общая ссылка latest feed ведёт в `/archive`.
- Добавлен автономный Node contract test против возврата News-only фильтра.

## Unified homepage and News Sitemap sections

- Подтверждён единый public editorial registry: News, People, Tech, Places,
  Unspoken и Podcast.
- Access остаётся отдельной страницей и не участвует в article feeds.
- Homepage показывает десять самых новых материалов шести разделов в общей
  хронологии без section priority.
- News Sitemap использует тот же registry и 48-часовое ограничение по
  publication date.
- Добавлены regression checks для всех шести разделов, Access, неизвестного
  section, старой и будущей публикации.

## Additive-safe taxonomy deployment audit

- Frontend deploy коммита `bc543b2` остановился до build/upload, потому что
  taxonomy audit жёстко ожидал 52 published articles, а после публикации
  AGIBOT CMS вернул 53.
- Удалён frozen article count.
- Утверждённые taxonomy/tag snapshots продолжают проверяться fail-closed.
- Новые статьи вне обоих snapshots получают
  `taxonomy_governance_pending` и не создают critical/drift failure.
- Pending slugs и их количество добавлены в JSON и текстовый summary.
- Добавлен Python self-test и Node regression test.


## REVELATIONS entity identity foundation

- Canonical identity defined as a Dubai-based future-facing media
  publication published by JULS.
- Added alternative names `Revelations Media` and `revelations.me`.
- Organization graph upgraded to `NewsMediaOrganization`.
- Added official Instagram, X and YouTube profiles through `sameAs`.
- Added Dubai location, editorial contact and JULS parent brand.
- About now reflects News, People, Tech, Places, Unspoken and Podcast.
- Contact and footer expose the same publisher and social identity.
- No unsupported legal-company claims were added.


## X social draft MVP

- Added a Social Drafts tab to Editorial Desk.
- Added private X draft entities for every published WordPress article.
- Unpublished articles remain available only when linked to Editorial Desk;
  unrelated technical drafts are excluded.
- Added explicit AI generation from the saved WordPress article only.
- Added server-owned canonical URL construction and 280-character
  validation.
- Added manual editing, live character count, copy action and manual
  posted status.
- Added article-change detection through a source hash.
- Added one previous-version backup before replacement.
- No X credentials, X API calls or automatic posting were introduced.

## Visual redesign - local implementation awaiting staging QA (2026-07-24)

- The visual redesign is implemented locally only. The homepage removes its
  duplicate identity block because the fixed header already carries the logo
  and canonical tagline.
- Article layout now uses a wider `max-w-4xl` header, `max-w-[88rem]` hero,
  `max-w-6xl` THE REVELATION treatment and `max-w-2xl` reading column. Body
  type is 17px on mobile and 18px from tablet upwards.
- THE REVELATION now sits between hero and body as a full editorial break.
  The author card, text-link Copy Link control and taxonomy presentation were
  redesigned without changing CMS data or public schema contracts.
- Homepage, section cards and related cards now resolve the canonical ordered
  author profile names through `formatArticleAuthors()`, so listing bylines do
  not fall back to legacy displayed-author aliases when a canonical profile
  relation exists.
- Local validation passed: `git diff --check`, 86/86 Node tests, ESLint and
  the production-configured build. No production deploy, production CMS write,
  production cache invalidation or staging mutation has occurred yet.

## Visual redesign - staging deployment (2026-07-24)

- Staging now runs frontend commit
  `1c80ab90f4e7aa85d2b6b18739a9fdd00a19d057` at
  `https://staging.revelations.me`. The active release carries a `REVISION`
  marker; rollback remains at
  `/var/www/revelations-staging-backup-retry-20260724-210000`.
- The initial switch failed before service startup because the uploaded release
  directory was not traversable by the `deploy` service user. It was rolled
  back immediately; ownership was corrected on the isolated candidate and the
  retry then passed. Staging is active and returns public HTTP 200.
- The public staging homepage has its route-specific staging canonical and
  `X-Robots-Tag: noindex, nofollow, noarchive`. The five requested pilot
  routes return 200. Sam, Daria, Arman, Anastasia and ELYS render THE
  REVELATION; the checked non-pilot AGIBOT article does not.
- The three People pilot articles render their populated THE REVELATION data
  from the fresh frontend build. The prior issue was the old staging
  build/cache path, not a missing or rewritten CMS value. No CMS data was
  changed.
- Production frontend, CMS, cache and content remain unchanged. Product-owner
  visual review on staging is the next gate; no production deployment is
  authorized by this stage.

## Project Constitution (2026-07-25)

- `docs/PROJECT_CONSTITUTION.md` is the canonical permanent governance source
  for brand identity, ASCII-hyphen copy, canonical Julia resolution, Human
  Review, public/private evidence, non-fabrication and staging-first visual
  approval.
- Governance surfaces reference the Constitution and a Node regression test
  protects its six required markers. Any user-visible frontend change now has
  an explicit staging QA and product-owner approval gate before a separately
  authorized production deployment.

## Visual refinement awaiting staging approval (2026-07-25)

- The rejected first staging treatment is refined locally without CMS, SEO,
  schema, author-relation or content changes. The accepted broad hero remains
  `max-w-[88rem]`; the article body, public editorial context, author module
  and taxonomy now share `max-w-5xl` rather than the rejected `max-w-2xl`
  reading strip.
- THE REVELATION returns to a restrained left rose rule with the shared
  `max-w-5xl` grid and 24px mobile, 30px tablet and 36px desktop type. The
  dominant horizontal rules and 48-60px poster treatment are removed.
- Copy Link shares the desktop metadata row and naturally wraps below it on
  small screens. Public taxonomy now exposes Topics, Location, Entities and
  optional Series without separate Primary topic or More topics concepts.
- This visual refinement requires a fresh staging-only deployment and explicit
  product-owner review. Production remains frozen for this task.

## Visual refinement staging deployment (2026-07-25)

- Staging now runs visual commit
  `f832c3d291985f8c28c24be381d6f2ee3ec57129`; rollback is retained at
  `/var/www/revelations-staging-backup-20260725-000100`.
- Staging service is active, homepage and six checked article routes return
  HTTP 200, staging canonical is route-correct and the response retains
  `X-Robots-Tag: noindex, nofollow, noarchive`.
- All five pilot articles render THE REVELATION through the shared template;
  the checked non-pilot article remains without it. Homepage listing output
  includes Julia Upiterskaya and has zero `Julia U.` occurrences.
- The visual candidate requires explicit product-owner review on staging.
  Production frontend, CMS content and cache were not changed.

## Footer refinement staging deployment (2026-07-25)

- Staging now runs footer commit
  `eef452cf2ea56da3cf76f906a35a86fb1f12f14a`; rollback is retained at
  `/var/www/revelations-staging-backup-20260725-000200`.
- Visible footer brand copy now contains only REVELATIONS and the exact
  canonical tagline. The verbose semantic/publisher description is no longer
  rendered by the footer component; metadata, schema, About and publisher
  contexts remain unchanged.
- Instagram, X and YouTube retain their official URLs and are compact outline
  text controls with rose hover and keyboard-focus treatment. Staging remains
  HTTP 200 and `noindex, nofollow, noarchive`.
- Production frontend, CMS and cache remain untouched. This visual change is
  staging-only and awaits explicit product-owner approval.

## Article header grid alignment awaiting staging deployment (2026-07-25)

- The article header now uses the same `max-w-5xl` (1024px) editorial grid as
  the body and THE REVELATION. The previous header cap was `max-w-4xl`
  (896px), which made the labels, title, deck, metadata and Copy Link begin
  on a narrower column than the reading content.
- The redundant nested `max-w-5xl` on the title and the restrictive
  `max-w-3xl` on the deck are removed, so they inherit the shared header
  width. Hero width, typography, THE REVELATION, author/taxonomy, footer,
  homepage, CMS and all content remain unchanged.
- Local regression tests, lint and the production-configured build pass. This
  is a staging-only visual candidate and still requires deployment followed by
  explicit product-owner approval; production remains frozen.

## Article header grid alignment staging deployment (2026-07-25)

- Staging now runs `dadc7cce8943274fe3d01a66983a5d6b86330123`; the preceding
  footer release is retained at
  `/var/www/revelations-staging-backup-20260725-000300` for rollback.
- The active service is healthy, staging public HTTP is 200, and the homepage
  retains `X-Robots-Tag: noindex, nofollow, noarchive`. AGIBOT, ELYS, Sam,
  Daria and a short-title Apple Intelligence article all return 200 with the
  shared-grid markup and Copy Link present.
- The footer canonical tagline remains rendered. No production frontend, CMS,
  cache, content or configuration was accessed or changed. This release awaits
  explicit product-owner visual approval on staging.

## Approved visual frontend production rollout (2026-07-25)

- The explicit staging-first approval gate completed and production now runs
  `aa0acda27877be09552a69621cc6fd2f828f6b32`, the documentation-only
  descendant of the approved visual commit `dadc7cce8943274fe3d01a66983a5d6b86330123`.
  The exact descendant diff affects only this state document; no runtime or
  visual artifact changed after staging approval.
- The reviewed fail-closed deployment retained rollback assets at
  `/root/revelations-production-before-20260724-220350` and
  `/var/www/revelations-production.previous-20260724-220350`, both containing
  prior release `7f68c77b4bcd3d7263abb633687474befff01a14`.
- Production service, loopback/public HTTPS, CMS health, Nginx configuration,
  sitemap, News sitemap, robots and representative article smoke tests passed.
  The five existing pilot revelations remain rendered and AGIBOT remains
  without one. No CMS data, cache, signed revalidation or publication action
  occurred in the frontend rollout.
- The local control session disconnected while the reviewed remote workflow was
  still reporting. Read-only recovery checks found the exact same approved
  artifact active; the runner performed two healthy same-artifact service
  restarts during the interrupted/retried control sessions. Final post-deploy
  logs contain no Nginx error after the switch, and no rollback was required.

## Isolated staging CMS and THE REVELATION backfill (2026-07-25)

- Staging CMS is isolated at `https://staging.revelations.me/cms` with separate
  root `/var/www/revelations-staging-cms`, database `revelations_staging_wp`,
  database user, PHP-FPM pool and staging-only runtime. It was initialized from
  the fresh production backup
  `/root/revelations-staging-cms-provision-before-20260724-225717/production-cms-20260724-225717.sql`.
  Staging cron, AI generation, outbound HTTP/mail and production revalidation
  are disabled; the endpoint inherits staging noindex protection.
- The staging frontend now reads only the isolated CMS endpoint. A disposable
  staging-only option was created, verified absent from production, then
  removed. Reviewer `oleg_revelations` is staging user ID `1`.
- Product-owner-supplied manifest v3 passed canonical validation and its 31
  exact texts were applied only to staging through the shared Human Review
  function. Readback and second audit are clean: 31 new current reviews plus
  five preserved pilots equals 36 staging revelations. Podcast and seven thin
  Unspoken records remain excluded. Production remains at five revelations;
  Stage 2D.2 dateModified remains open.
