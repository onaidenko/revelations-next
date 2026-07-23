# Архитектурные и редакционные решения

## Общая AI-направленность

- **Решение:** AI должен быть центральной темой материала во всех
  разделах. Фонового упоминания недостаточно: материал должен
  содержательно относиться к AI-индустрии, её участникам, технологиям,
  внедрениям или последствиям.
- **Причина:** разделы меняют редакционный угол, но не общую
  AI-направленность REVELATIONS.

- **Решение:** global AI relevance gate остаётся обязательным этапом
  автоматического отбора и выполняется до section-specific scoring.
- **Причина:** неподходящий материал не должен получать section score
  или становиться qualified candidate.

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

- **Решение:** canonical entity — REVELATIONS, a Dubai-based
  future-facing media publication.
- **Решение:** JULS указывается как publishing brand and parent
  organization, но не как зарегистрированное legal entity.
- **Решение:** official Instagram, X and YouTube profiles are connected
  through visible links and Organization `sameAs`.
- **Решение:** public identity copy is kept materially consistent across
  metadata, About, Contact, footer and structured data.


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
