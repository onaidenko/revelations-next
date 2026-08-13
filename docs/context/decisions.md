# Архитектурные и редакционные решения

The permanent conflict and staging-first rules live in
[`../PROJECT_CONSTITUTION.md`](../PROJECT_CONSTITUTION.md). This decision log
cannot override that Constitution without explicit product-owner approval.

## Evidence-first AI drafting

- **Решение:** RSS lead and its private source snapshot are discovery input,
  not the factual boundary for a generated article. Factual web research gets
  topic, section, lead and evidence requirements only. The saved Editorial
  Policy enters only the subsequent evidence-to-brief stage and final writing;
  it remains the sole editable policy source.
- **Safeguard:** two independently hosted corroborating sources are required.
  Book excerpts, opinion, sponsored/native material, press releases and
  likely-paywalled leads additionally require primary-authoritative support;
  otherwise the pipeline returns `insufficient_independent_evidence` and does
  not write a draft. Private evidence/prompt material stays private.
- **Safeguard:** dominance is assessed from three to five factual pillars and
  their source roles, not raw claim counts. Primary-authoritative technical
  detail dominance is logged as a note. A secondary source dominating more
  than 70% of pillars is a warning; it blocks when it is the sole substantive
  support for a central pillar or a majority of pillars. Lead support is excluded from evidence
  and a brief must supply its own complete pillar order.

## Global research source usage

- **Решение:** Global Research & Source Policy applies uniformly to News,
  Tech, Places, People and Unspoken. Section profiles retain only the section
  angle and story focus.
- **Решение:** research discovery is not public attribution. A generated
  footer is derived solely from final article evidence IDs, then deduplicated
  and ordered by source role. Sensitive claim provenance is retained; unused
  research sources stay private. This selection never rewrites article facts.
- **Решение:** the brief is the boundary between broad corroboration and final
  writing. It selects pillar, sensitive, attribution and essential-context
  evidence before prose is requested; final writing cannot use other research
  units. The compact set repeats independence, restricted-lead primary and
  dominance checks before it is sent.
- **Safeguard:** shared source metadata is serialized once in a private
  `sNNN` registry. A global factuality instruction preserves material scope,
  qualifiers, conditions, category boundaries and uncertainty in formal legal,
  regulatory, scientific and technical definitions. It is shared, not copied
  into section profiles.
- **Safeguard:** the compact registry representation is prompt-only. Existing
  fact-check validation receives a separate flat serialization of selected
  stable evidence units, preserving `pNNN` identity without weakening
  evidence, sensitive-claim or attribution validation. Completed stage
  diagnostics are retained on a validation failure, but raw model material is
  not persisted.
- **Observability:** successful research and parsed-brief boundaries emit
  bounded private lifecycle diagnostics before later local gates run. Failed
  runs retain stage usage plus ID-only research provenance and pillar support;
  no raw evidence, prompt, web result or model output is persisted. A final
  stage exists in diagnostics only after its actual Responses request.

## Publication dates

- **Решение:** visible CMS-derived article dates use the UTC calendar date of
  the authoritative `publication_date` timestamp. JSON-LD retains that full
  timestamp unchanged.
- **Причина:** the public CMS API exports a GMT instant but not WordPress's
  editorial local-date value. An explicit UTC formatter prevents build-server
  or visitor timezone from changing a published calendar date.

## Article Template Stage 2A: editorial enrichment

- **Решение:** Articles may optionally expose editor-approved plain-text `revelation`, `source_note`, `editorial_note`, `disclosure` and ordered `public_sources`. Blank values never block legacy articles or publication.
- **Решение:** `THE REVELATION` is the editorial takeaway, distinct from H1, deck, SEO description and first body paragraph. Its approved public value is plain text, 60-150 words recommended and 180 words maximum; AI may only make a private suggestion and never silently populate it.
- **Решение:** public source context is strictly opt-in. Source snapshots, evidence, fact-check flags, AI prompts/responses, internal review reasoning, generation metadata and private-version metadata never enter the public API or become a fallback for an empty public field.
- **Решение:** public enrichment is review-protected, versioned and restored with the article, while remaining optional and absent from article schema in this stage.

## Future Stage 2B author profiles

- **Решение:** author entities use dedicated `rev_author` records and public `/authors/{slug}` routes. Article relationships are ordered `author_profiles` arrays from the start. Entity schema type is explicitly `person` or `organization`; Editorial Team is an Organization, never a Person.
- **Решение:** canonical mapping is exact, never fuzzy: Julia U., the historical Julia Yupiterskaya spelling and Julia Upiterskaya map to Julia Upiterskaya; Alina K. and empty authors map to Alina B.; Anonymous and Editorial Team map to Editorial Team.
- **Решение:** Julia Upiterskaya is the canonical display name and has the verified LinkedIn URL `https://www.linkedin.com/in/julia-upiter/`. The former Yupiterskaya spelling is a legacy migration alias only. Her current approved positioning is Founder of JULS and REVELATIONS, focused on Artificial Intelligence and Wellness within Emerging Technology. Do not infer further biography, affiliations or Web3-primary positioning.
- **Решение:** an author profile may be indexable only when active, named, meaningfully biographical and linked to at least one published article.
- **Решение:** Stage 2B implements the `rev_author`/ordered-relation/API/profile
  infrastructure and exact dry-run mapping only. Production provisioning and
  migration remain a separately approved operation.
- **Решение:** canonical provisioning writes and verifies the configured schema
  type even when WordPress supplies a metadata default. A canonical repair is
  an explicit, separately confirmed operation limited to the exact configured
  `rev_author` identity and approved schema-type field; generic schema
  conflicts remain blocking.

## Future FAQ and image attribution

- **Решение:** FAQ is optional, normally three to five useful questions and never more than six. It is not SEO filler; visible content must exactly match FAQ schema when emitted.
- **Решение:** future image attribution keeps caption, credit and source URL separate. Native attachment captions may be used; credit and source facts must be editor-verified and blank credit never defaults to REVELATIONS.

## Общая AI-направленность

- **Решение:** все editorial sections принимают material через strict
  `AI OR meaningful future-tech` global relevance. Existing AI branch
  сохраняется без изменений. News/Places/Tech future-tech branch требует
  family signal, действие и независимое technical/implementation
  corroboration; People/Unspoken используют common future-tech families без
  deployment requirement, но только после собственных строгих section gates.
- **Причина:** REVELATIONS освещает AI и доказуемые future-facing
  technological transformations, не превращая scanner в общий фильтр
  по словам technology, smart или futuristic.
- **Причина:** разделы меняют редакционный угол, но не общую
  AI-направленность REVELATIONS.

- **Решение:** global relevance gate остаётся обязательным этапом до
  section-specific scoring. Generic hospitality, architecture, real estate,
  concepts, renders, plans и opinion pieces не получают обхода через
  future-tech branch.
- **Причина:** неподходящий материал не должен получать section score
  или становиться qualified candidate.
- **Решение:** People требует named central person и substantive technical
  significance (например, founder, inventor, researcher или technical leader).
  Unspoken требует evidence-backed hidden, limitation, human-effect,
  trust/identity, infrastructure-cost, autonomy-gap или second-order angle;
  ordinary negative business news не является достаточным.
- **Решение:** News различает narrow product/deployment, concrete research,
  major-capital и strategic-company event tracks. Product/research сохраняют
  общий score path; major-capital event может обойти implementation minimum
  только при exceptional capital scale, strong impact, relevance и freshness.
  Routine feature updates и funding без масштаба не получают significance.
  Unspoken privacy/control включает neural/brain data, mental privacy и
  ownership/control of intimate data только как evidence-backed angle, а не
  generic negative sentiment; implementation остаётся diagnostic component,
  но не является mandatory maturity minimum для evidence-backed emerging issue.

## Раздел и классификация

- **Решение:** AI generation не меняет раздел WordPress draft.
  Сохраняется раздел, выбранный scanner или оператором.
- **Решение:** при явном несоответствии AI может вернуть только
  `suggested_section`, `section_mismatch` и краткую причину. Смену
  раздела выполняет исключительно оператор.
- **Причина:** классификационная рекомендация не должна незаметно
  менять редакционное решение и WordPress-категорию.

- **News:** значимые события AI-отрасли — анонсы, запуски, сделки,
  партнёрства, инвестиции и изменения рынка. Главная ценность —
  событие, а не конкретная технология, человек или место.
- **Tech:** конкретная AI-технология, система, архитектура,
  инфраструктура или практическое внедрение. Общего корпоративного
  анонса без технической сути недостаточно.
- **People:** конкретный человек, его решение, заявление, назначение,
  конфликт, проект или иное действие, влияющее на AI-отрасль. Не
  создавать рекламные биографии и не приписывать мотивы.
- **Places:** физическое место, где AI или связанная технология уже
  внедрена и существенно влияет на работу пространства или опыт.
  Концепты, планы, визуализации и обычные открытия не подходят.
- **Unspoken:** доказательные материалы о негативных последствиях,
  провалах, скрытых издержках, злоупотреблениях, конфликтах интересов,
  этических, социальных и психологических проблемах AI. Требуются
  строгая атрибуция, репутационные safeguards и human review.

## AI generation

- **Решение:** генерировать один recommended title и два alternative
  titles. В draft устанавливается только recommended title;
  альтернативы хранятся отдельно и показываются оператору.
- **Решение:** excerpt является описанием карточки, SEO description —
  отдельным полем; они не должны дословно совпадать. Отдельное поле
  card description сейчас не нужно.
- **Решение:** генерация запускается вручную для одной статьи. Batch
  generation и автоматическая публикация отсутствуют.

## Факты и цитаты

- **Решение:** фактчекинг обязателен для каждого материала. AI не
  добавляет сведения вне source snapshot или других явно подключённых
  источников.
- **Решение:** до появления multi-source verification даты, числа,
  деньги, инвестиции, оценки, метрики, superlative claims, benchmarks,
  медицинские, юридические, регуляторные, обвинительные и
  репутационно чувствительные утверждения помечаются для обязательной
  ручной проверки.
- **Решение:** прямой цитатой может быть только дословный фрагмент
  источника. Нельзя создавать, реконструировать, склеивать цитаты или
  оформлять пересказ в кавычках.
- **Решение:** новые generations ссылаются на server-generated
  evidence units только по стабильным ID; source evidence
  восстанавливает сервер. Модель не возвращает свободный evidence
  fragment. Direct quote проверяется только как точный substring
  указанной unit; fuzzy и semantic matching запрещены.
- **Причина:** детерминированные references исключают расхождения при
  копировании evidence моделью, не ослабляя source-grounding.
- **Решение:** материал нельзя публиковать без редактуры и актуального
  human review.

## Автор и изображения

- **Решение:** default displayed author — `Julia U.`. AI не меняет
  уже выбранного автора; default применяется только к draft без
  автора, а оператор может назначить другого.
- **Решение:** image pipeline исключён из текущего этапа. Не добавлять
  provider, prompts, image generation, source-image ingestion, stock
  integration или image metadata. Featured image остаётся ручным
  этапом.

## Unspoken backend

- **Решение:** Unspoken scanner использует только MIT Technology
  Review, WIRED, BBC Technology и The Verge и остаётся выключенным по
  умолчанию.
- **Решение:** допустимы только tracks `documented_harm`,
  `failure_or_reversal`, `economic_model_failure`,
  `legal_or_governance_conflict` и `labor_or_social_cost`; catch-all
  отсутствует.
- **Решение:** кроме global AI gate обязательны конкретный негативный
  сигнал, подтверждённое событие, attribution/evidence, свежесть и
  значимость. Высокий score не заменяет ни один обязательный gate.
- **Решение:** если негативный аспект является центральной новостью,
  основным разделом остаётся Unspoken. `secondary_section` является
  только рекомендацией; фактический раздел меняет оператор.
- **Решение:** single-source allegation допускается только в preview
  при явном evidence signal и маркируется `Single-source allegation`
  и `Requires reputational review`. Scanner не подтверждает истинность
  обвинения и не заменяет Human Review.

## Безопасность автоматизации

- **Решение:** автоматические scanners пока остаются выключенными; не
  включать и не запускать автоматизацию без отдельного решения.
- **Причина:** семантика AI gate и технические проверки ещё не
  завершены.

- **Решение:** lint, static checks и synthetic tests не должны
  создавать кандидатов, drafts или публикации.
- **Причина:** верификация должна быть изолированной и не менять
  редакционные данные.

## Public frontend

- **Решение:** staging artifact обязан включать standalone, `.next/static`,
  `.next/BUILD_ID` и `public`; candidate до switch проверяет checksum, build
  identity и HTTP 200 для всех CSS/JS, referenced homepage.
- **Причина:** standalone output сам по себе не содержит `.next/static` и
  может отдать current HTML без стилей и client bundles.

- **Решение:** legacy article URLs обслуживаются App Router route handler
  `app/article/[legacy]/route.js`, а не Proxy или generic redirect в
  `next.config.mjs`. Он редиректит только проверенные Base44 IDs и canonical
  fallback slugs из `data/articles.json`; любые неизвестные значения получают
  404.
- **Причина:** generic redirect срабатывает раньше Proxy и не позволяет
  сохранить fail-closed distinction между известными legacy IDs и неизвестными
  путями.

- **Решение:** главная страница показывает в общей хронологии последние
  опубликованные материалы разделов News, People, Tech, Places, Unspoken и
  Podcast.
- **Решение:** Access является отдельной страницей, а не editorial section,
  и не участвует в homepage article feed.
- **Решение:** фактический раздел отображается на каждой mixed-feed карточке;
  общий переход ведёт в Archive. Section pages остаются отфильтрованными.
- **Причина:** homepage является общим входом в REVELATIONS и не должна
  скрывать публикации ни одного раздела главного меню.

## Editorial taxonomy and Related content

- **Решение:** topics, series и locations являются разными editorial
  dimensions; обычные WordPress tags не переиспользуются.
- **Решение:** primary topic хранится отдельно от множества assigned topics,
  чтобы модель могла выразить один главный topic без потери secondary topics.
- **Решение:** manual Related overrides имеют приоритет над automatic scoring,
  но применяются только для явно заданных source articles и сохраняют порядок.
- **Решение:** importer по умолчанию выполняет только validate/plan dry-run;
  apply требует отдельного явного разрешения и confirmation token.
- **Решение:** новые опубликованные статьи могут временно отсутствовать в
  утверждённых taxonomy/tag snapshots и получают статус governance pending.
  Они не блокируют frontend deploy. Исчезновение ранее утверждённой статьи,
  расхождение двух maps или drift утверждённых metadata остаются блокирующими.
- **Причина:** публикация нового контента не должна делать любой последующий
  frontend deploy невозможным до отдельного taxonomy-review этапа.

## Cache revalidation

- **Решение:** production использует единый shared HMAC secret для WordPress
  sender и Next endpoint. Secret хранится только в закрытых runtime-
  конфигурациях; synthetic signed smoke без публикации допустим как end-to-end
  проверка. Sender остаётся fail-open, а frontend TTL остаётся fallback.

- **Решение:** HTML entities из WordPress декодируются общей функцией
  ровно один раз только для plain-text полей. HTML article content
  этим decoder не обрабатывается.
- **Причина:** одинаковые decoded значения должны использоваться в
  UI, metadata, JSON-LD и accessibility attributes без raw HTML
  rendering или двойного декодирования.

## SEO Stage 1A

- **Решение:** frontend получает полный published CMS collection через
  pagination, а не только первую страницу из 100 items. Pagination
  metadata используется, когда endpoint её возвращает; fallback и
  loop guard сохраняют безопасное поведение при её отсутствии.
- **Решение:** sitemap содержит только canonical public routes. Static
  routes не получают искусственную текущую дату; section/article dates
  происходят только из реальных article timestamps.
- **Решение:** News sitemap ограничен 48 часами от publication date;
  modified date не может вернуть старую публикацию в Google News sitemap.
- **Решение:** News Sitemap использует тот же editorial section registry,
  что и homepage: News, People, Tech, Places, Unspoken и Podcast.
  Access и неизвестные sections исключаются.
- **Решение:** Cover Image — единственный article-specific social
  image. Branded logo допустим только как Open Graph/Twitter fallback,
  но не как `Article.image`.
- **Решение:** author без displayed author представляется ссылкой на
  publisher Organization, а не вымышленным Person `REVELATIONS`.
- **Решение:** WebSite и Organization используют стабильные IDs
  `/#website` и `/#organization`; SearchAction и неподтверждённые
  Organization details не публикуются.

## Publication review and categories

- **Решение:** Article имеет ровно одну editorial category из registry
  `revelations_editorial_sections()`; `Uncategorized`, пустой,
  неизвестный и multiple selection блокируют publication. Tags остаются
  множественными. Legacy published records не мигрируются автоматически.
- **Решение:** Human Review покрывает title, content, excerpt, category,
  tags, SEO meta, displayed author и current AI review metadata через
  per-field SHA-256 fingerprints. Featured Image, post status, revision
  и modified timestamps исключены; image остаётся отдельным readiness
  requirement.
- **Решение:** Gutenberg REST publication проверяется только в
  `rest_pre_insert_post`. Его internal save не проходит повторно classic
  fallback; non-REST classic/WP-CLI path сохраняет guard и нормализует
  slashed request text через `wp_unslash`.

## Production deploy safety

- **Решение:** production artifact не содержит runtime `.env` или секреты.
  Изолированный candidate запускается только с генерируемыми безопасными
  значениями production site URL и CMS API URL.
- **Решение:** полный runtime `.env.production` переносится только на сервере
  из текущего active release после остановки candidate и до atomic switch.
  Перенос подтверждается `cmp`, а значение secret не читается и не выводится.
- **Решение:** public verification является fail-closed. Любое несовпадение с
  candidate manifest, ошибка service/loopback/Nginx/CMS или потеря staging
  `noindex` вызывает явный failure и rollback; одного `set -e` недостаточно.
- **Решение:** список проверяемых taxonomy hubs берётся из фактического sitemap
  candidate, а не из зафиксированного количества. После switch публичный
  sitemap и ключевые metadata/schema должны совпасть с candidate manifest.
- **Причина:** SEO 3A rollout показал, что static candidate может выглядеть
  исправным без полного runtime environment, а неявная shell-обработка ошибки
  может ошибочно зафиксировать неуспешный rollout как успешный.

## Taxonomy discovery indexes

- **Решение:** `/topics` является индексируемой public collection page и
  получает список только из существующего eligible Topics builder. Ручной
  список topic slugs и отдельная CMS-конфигурация не создаются.
- **Решение:** стабильные входы в Topics размещаются в `Footer → Explore` и
  в компактном блоке `Explore by topic` на `/archive`. Primary desktop/mobile
  header остаётся сфокусированным на редакционных разделах.
- **Решение:** `/topics` входит в основной sitemap и signed frontend
  revalidation. Individual Topic URLs, eligibility thresholds, article
  membership, News sitemap и taxonomy storage не меняются.
- **Решение:** отдельные `/series` и `/locations` могут быть рассмотрены
  позднее. `/tags` не создаётся как общий публичный каталог на этом этапе,
  чтобы entity tags оставались контекстной навигацией, а не tag cloud.
- **Причина:** SEO 3B audit подтвердил, что `/topics` возвращал 404, а все
  девять public Topic hubs не имели стабильного non-article entry point.

## Production service socket detection

- **Решение:** post-switch loopback port определяется по PID-ам активного
  systemd cgroup (`ControlGroup` + recursive `cgroup.procs`), а не по `cwd`
  или наличию release path в process command line.
- **Решение:** все listening sockets, принадлежащие процессам unit cgroup,
  рассматриваются как кандидаты; deploy выбирает первый порт, который
  действительно отвечает по `127.0.0.1`.
- **Причина:** standalone Next.js может создавать дочерний процесс, чей `cwd`
  и command line не содержат live release path. Старый эвристический поиск
  поэтому не находил рабочий порт 3002 и запускал rollback после успешного
  service start.

## Production service readiness retry

- **Решение:** после atomic switch готовность production service означает не
  только `systemctl is-active`, но одновременно наличие listening socket,
  принадлежащего PID-ам systemd cgroup, и успешный HTTP-ответ по loopback.
- **Решение:** service state, cgroup membership, sockets и loopback HTTP
  проверяются повторно до 40 раз с паузой в одну секунду. Пустой список
  sockets во время запуска считается промежуточным состоянием, а не
  немедленной ошибкой.
- **Причина:** systemd может отметить unit активным раньше, чем дочерний
  standalone Next.js process войдёт в cgroup и откроет порт. Одноразовый
  socket lookup дважды вызывал безопасный rollback исправного candidate.

## CMS failure semantics for ISR

- **Решение:** transient CMS failures must throw after bounded retries; they
  must never be converted into a successful empty CMS collection.
- **Решение:** each CMS page request receives three attempts with incremental
  delays and a 15-second timeout per attempt.
- **Причина:** returning `[]` on a temporary CMS failure caused taxonomy
  regeneration to build a valid-looking `notFound()` result from legacy-only
  data. Next.js then cached that false result and public hubs became soft-404.
- **Поведение:** when CMS configuration is intentionally absent, local legacy
  data remains available. When a configured CMS becomes unavailable, build or
  regeneration fails explicitly so Next.js can keep the last successful
  cached output and retry later.


## Brand identity

## Podcast SEO

- **Решение:** `/podcast` uses one `PodcastSeries` entity with only the
  existing publisher relationship, canonical URL and approved page metadata.
  No host, RSS, platform listing, duration or episode count is asserted.
- **Решение:** an episode page substitutes the generic Article JSON-LD with a
  single `PodcastEpisode` only when its existing title, SEO description or
  excerpt, publication date, cover image and YouTube URL are present. This
  prevents schema duplication and unsupported episode claims.
- **Решение:** thin episode bodies are identified for reporting only; this SEO
  stage never rewrites them or changes CMS records.

- **Решение:** canonical entity — REVELATIONS, a Dubai-based
  future-facing media publication.
- **Решение:** JULS указывается как publishing brand and parent
  organization, но не как зарегистрированное legal entity.
- **Решение:** official Instagram, X and YouTube profiles are connected
  through visible links and Organization `sameAs`.
- **Решение:** public identity copy is kept materially consistent across
  metadata, About, Contact, footer and structured data.

- **Решение:** REVELATIONS is always uppercase in publication-owned UI, SEO,
  metadata, schema, publisher and generated editorial copy. REVELATIONS is the
  sole public brand name; the former `REVELATIONS Media` public alias is
  superseded. `revelations.me` remains only the technical domain, not a
  display-brand variant.
- **Решение:** all public editorial copy uses only ASCII hyphen-minus (`-`),
  never en/em dashes. This includes public quotations and historical public CMS
  copy. Private source evidence remains verbatim; URLs, href/src values, slugs,
  IDs, UUIDs, filenames, API endpoints, code and other machine-readable values
  remain excluded from normalization.
- **Решение:** `Born as a podcast. Built as a media platform.` is the exact
  canonical tagline. `Future-Facing Media from Dubai` is the separate semantic
  descriptor. The root NewsMediaOrganization exposes the tagline via Schema.org
  `slogan`; WebSite/Organization names, stable IDs, URLs and publisher
  relationships remain unchanged.


## Byte-preserving historical dash migration

- **Решение:** the approved normalization of historical raw CMS fields used one
  controlled storage-level transaction with exact raw SHA-256 guards for every
  affected field in the record. WordPress API writes under altered
  capabilities are rejected because capability changes do not prove that KSES,
  save filters or plugins preserve unrelated historical bytes.
- **Решение:** a field-specific API/storage hybrid is not used for this bounded
  migration. Its hooks and external side effects cannot share the rollback
  semantics of the storage transaction. Exact mode-0600 backup data replaces
  automatic WordPress revisions as the authoritative recovery source.
- **Решение:** markup, Gutenberg comments, href/src and other tag attributes,
  URLs and code-like elements are protected regions. A forbidden dash in such a
  region blocks that field from automatic migration instead of normalizing it.

## Manual social distribution

- **Решение:** social distribution starts with copy-ready drafts rather
  than direct API publication.
- **Решение:** X is implemented as an independent article-level entity.
- **Решение:** all published WordPress articles are eligible, including
  legacy articles imported before Editorial Desk existed; unpublished
  posts require an Editorial Desk candidate link.
- **Решение:** generation is explicit and never triggered automatically
  by WordPress publication.
- **Решение:** canonical article URL is appended and validated by the
  server rather than trusted to the model.
- **Решение:** an X draft cannot be marked posted when its source article
  has changed since generation or manual save.
