# Codex repository rules

`docs/PROJECT_CONSTITUTION.md` is the permanent project-governance contract.
Read and obey it before work. In particular, any user-visible frontend change
is staging-first and requires explicit product-owner visual approval before a
separately authorized production deployment.

## REVELATIONS editorial style

For any editorial, UI copy, SEO, metadata, CMS generation, AI generation or
publisher identity task, read and obey `docs/editorial-style.md` before
editing. It is the authoritative contract for the REVELATIONS spelling,
brand assets, hyphen typography and treatment of quotations and historical
editorial content.

- В начале каждой задачи прочитать `docs/context/`.
- Всегда проверить `pwd`, текущую ветку, `git status` и незакоммиченный diff.
- Фактический код и состояние Git имеют приоритет над текстовым контекстом.
- Не перезаписывать и не отменять незакоммиченные изменения пользователя.
- Не использовать `git reset --hard`, `git clean`, destructive checkout или массовое удаление без явного разрешения.
- Не выполнять merge, rebase, deploy или изменения базы данных без
  отдельного разрешения.
- SSH и scp без отдельной остановки допустимы только для изолированного
  PHP lint или diagnostics явно согласованного технического этапа и
  только по правилам удалённых проверок ниже; остальные SSH/scp
  требуют отдельного разрешения.
- Commit и push требуют отдельного разрешения, кроме явно
  согласованного технического этапа, который соответствует всем
  условиям автономного цикла ниже.
- Для разрешённых удалённых технических проверок использовать только SSH-алиас `revelations-prod`, никогда не сырой IP.
- Не запрашивать и не сохранять SSH-пароли.
- Передавать проверяемые файлы только в новую уникальную директорию внутри удалённого `/tmp`; не изменять рабочий WordPress, базу или серверную конфигурацию.
- Не считать проверку выполненной, если command pipeline оборвался до её запуска.
- Перед правкой изучить целевой файл и связанные определения, вызовы и потребителей.
- Делать минимальные точечные изменения.
- После изменений проверить diff и выполнить доступные проверки.
- После значимой задачи обновить `docs/context/current-state.md`.
- При архитектурном решении обновить `docs/context/decisions.md` и `docs/context/architecture.md`.
- Не хранить в контекстных документах секреты, пароли, ключи или полные длинные логи.
- После получения задачи автономно пройти безопасный цикл: pre-flight → анализ → минимальные изменения → доступные проверки → обновление контекста → итоговый отчёт.
- Не останавливаться после каждого безопасного шага и не запрашивать подтверждение на обычное чтение, локальное редактирование workspace, `git diff` и статические проверки.
- После явного согласования технического этапа можно самостоятельно
  пройти полный цикл: pre-flight → implementation → diagnostics → PHP
  lint → context update → commit → push → final verification.
- Commit и push внутри такого цикла разрешены только в ветке
  `admin-editorial`, только для файлов согласованного этапа, при чистом
  исходном working tree, отсутствии чужих или неожиданных изменений,
  успешных проверках и `git diff --check`, отсутствии секретов и
  несвязанных изменений; push выполнять только в
  `origin/admin-editorial`.
- Всегда останавливаться перед deploy, изменением базы или рабочего
  WordPress, платным OpenAI API request, destructive Git operation,
  изменением продуктового контракта, а также при неожиданной ошибке
  или несвязанном изменении.

## Production и remote safety

- Перед любой production-операцией явно разделять read-only аудит,
  deploy и изменение runtime-конфигурации; для каждой mutation нужно
  отдельное разрешение с точным target и scope.
- Перед CMS или frontend deploy подтвердить текущий release, создать
  timestamped backup и сохранить понятный rollback path. Не удалять
  previous release или backup без отдельного явного разрешения.
- Frontend deploy выполнять только из проверенного production build.
  Проверять, что artifact использует `https://revelations.me`, не
  содержит `https://staging.revelations.me`, и не брать production
  secrets из repository `.env`-файлов.
- Перед atomic frontend switch запускать candidate только в новой
  изолированной `/tmp`-директории и на свободном loopback port. Candidate
  не получает runtime secret; его отсутствие не считать ошибкой.
- После switch выполнять health-check service и loopback port, `nginx -t`,
  public HTTPS audit, CMS API check и безопасную функциональную проверку.
  Sitemap URLs проверять по фактическому текущему sitemap, а не по
  заранее зафиксированному количеству.
- При сбое соединения, approval-сервиса или command pipeline сначала
  read-only установить, что реально успело выполниться. Не повторять
  deploy/switch вслепую.
- Runtime secrets допустимо проверять только по факту наличия/загрузки.
  Никогда не читать, не выводить, не передавать в Git и не записывать в
  context их значения.
- Не выполнять OpenAI generation/test connection, scanner runs, публикацию
  или WordPress/DB writes во время технического deploy/audit, если это не
  разрешено отдельно.
- Staging — самостоятельный контур: не менять его build, service, Nginx,
  TLS или `noindex` при production deploy без отдельного разрешения.
