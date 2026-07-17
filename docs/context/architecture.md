# Подтверждённая архитектура

## WordPress CMS

Редакционная CMS реализована набором WordPress MU plugins в `wordpress-cms/mu-plugins/`. Файлы хранятся в Git как снимок исходников; live-каталог WordPress не является Git-репозиторием.

## Scanner subsystem

- `revelations-editorial-scanner-engine.php` — общий dry-run RSS engine: загрузка feeds, нормализация, валидация, глобальная дедупликация, AI gate, вызов section scorer, сортировка и формирование результата.
- `revelations-editorial-{news,people,tech,places}-scanner.php` — источники, ключевые слова, пороги и scoring соответствующего раздела. Каждый из этих scanner-файлов вызывает общий engine.
- `revelations-editorial-scanner-settings.php` — профили разделов: включение, preview limit, активные и отключённые источники, keyword groups и thresholds. Settings перечисляют News, People, Tech, Places и Unspoken; подтверждённые scanner backends существуют для первых четырёх. Unspoken имеет пустой default profile.
- `revelations-editorial-preview-engine.php` — registry и общий запуск preview для Tech, News, People и Places, runtime-настройки, transient preview и run logging.
- `revelations-editorial-{news,people,tech,places}-preview.php` — административные preview-интерфейсы разделов.

## Preview candidate persistence

- `revelations-editorial-preview-candidate-save.php` — общий backend
  сохранения одного qualified preview candidate для News, People,
  Tech и Places.
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

## Порядок обработки истории

1. Нормализовать section и limits.
2. Загрузить существующие duplicate keys.
3. Получить RSS feed и элементы источника.
4. Очистить title и summary, проверить обязательные поля и вычислить дату.
5. Вычислить duplicate key и удалить дубли.
6. Сформировать общий story record.
7. Выполнить обязательный глобальный AI relevance gate.
8. Только прошедшую gate историю передать section-specific scoring callback.
9. Отдельно учесть section hard filters, отсортировать scores и выбрать qualified stories.
10. Вернуть dry-run result без создания кандидатов.

Глобальный AI gate расположен в общем engine непосредственно перед `call_user_func($score_story, $story)`, поэтому он предшествует section-specific scoring для News, People, Tech и Places.
