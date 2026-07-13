from __future__ import annotations

import csv
import json
import sys
import urllib.request
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[1]
ARTICLES_PATH = ROOT / "data" / "articles.json"
MISSING_PATH = ROOT / "data" / "missing-articles.json"


def parse_bool(value: str | None) -> bool:
    return str(value or "").strip().lower() in {
        "true",
        "1",
        "yes",
    }


def optional_text(value: str | None) -> str | None:
    normalized = str(value or "").strip()
    return normalized or None


def parse_tags(value: str | None) -> list[Any]:
    raw = str(value or "").strip()

    if not raw:
        return []

    try:
        parsed = json.loads(raw)
    except json.JSONDecodeError:
        return [
            item.strip()
            for item in raw.split(",")
            if item.strip()
        ]

    return parsed if isinstance(parsed, list) else []


def download_markdown(url: str, slug: str) -> str:
    print(f"Downloading external content: {slug}")

    request = urllib.request.Request(
        url,
        headers={
            "User-Agent": "Mozilla/5.0 REVELATIONS migration",
        },
    )

    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            raw = response.read()
            charset = response.headers.get_content_charset() or "utf-8"
    except Exception as error:
        raise RuntimeError(
            f"Could not download content for {slug}: {error}"
        ) from error

    content = raw.decode(charset).lstrip("\ufeff").strip()

    if not content:
        raise RuntimeError(
            f"Downloaded content is empty for {slug}"
        )

    lowered = content[:200].lower()

    if "<!doctype html" in lowered or "<html" in lowered:
        raise RuntimeError(
            f"Received HTML instead of Markdown for {slug}"
        )

    print(f"  Downloaded {len(content):,} characters")
    return content


def write_json_atomic(path: Path, value: Any) -> None:
    temporary_path = path.with_suffix(path.suffix + ".tmp")

    temporary_path.write_text(
        json.dumps(
            value,
            ensure_ascii=False,
            indent=2,
        ) + "\n",
        encoding="utf-8",
    )

    temporary_path.replace(path)


if len(sys.argv) != 2:
    print(
        "Usage:\n"
        "  python3 scripts/import-base44-export.py "
        '"/path/to/Article_export.csv"'
    )
    raise SystemExit(1)


csv_path = Path(sys.argv[1]).expanduser().resolve()

if not csv_path.exists():
    raise FileNotFoundError(
        f"CSV file not found: {csv_path}"
    )


with csv_path.open(
    "r",
    encoding="utf-8-sig",
    newline="",
) as file:
    source_rows = list(csv.DictReader(file))


published_rows = [
    row
    for row in source_rows
    if str(row.get("status") or "").strip() == "published"
    and not parse_bool(row.get("is_sample"))
]


source_slugs = [
    str(row.get("slug") or "").strip()
    for row in published_rows
]

duplicate_slugs = sorted({
    slug
    for slug in source_slugs
    if source_slugs.count(slug) > 1
})

if duplicate_slugs:
    raise RuntimeError(
        "Duplicate slugs in export:\n"
        + "\n".join(duplicate_slugs)
    )


old_articles = json.loads(
    ARTICLES_PATH.read_text(encoding="utf-8")
)

old_by_slug = {
    article["slug"]: article
    for article in old_articles
}


if MISSING_PATH.exists():
    missing_slugs = json.loads(
        MISSING_PATH.read_text(encoding="utf-8")
    )
else:
    missing_slugs = []


new_articles = []
downloaded_content = []


for row in published_rows:
    slug = str(row.get("slug") or "").strip()
    title = str(row.get("title") or "").strip()
    section = str(row.get("section") or "").strip()
    cover_image = optional_text(row.get("cover_image"))

    if not slug:
        raise RuntimeError("An exported article has no slug")

    if not title:
        raise RuntimeError(f"Article has no title: {slug}")

    if not section:
        raise RuntimeError(f"Article has no section: {slug}")

    if not cover_image:
        raise RuntimeError(f"Article has no cover image: {slug}")

    content = str(row.get("content") or "").strip()
    content_file_url = optional_text(
        row.get("content_file_url")
    )

    if not content and content_file_url:
        content = download_markdown(
            content_file_url,
            slug,
        )
        downloaded_content.append(slug)

    article = {
        "id": str(row.get("id") or "").strip(),
        "slug": slug,
        "title": title,
        "section": section,
        "excerpt": optional_text(row.get("excerpt")),
        "content": content,
        "cover_image": cover_image,
        "author": optional_text(row.get("author")),
        "tags": parse_tags(row.get("tags")),
        "youtube_url": optional_text(row.get("youtube_url")),
        "featured": parse_bool(row.get("featured")),
        "is_gated": parse_bool(row.get("is_gated")),
        "status": "published",
        "publication_date": optional_text(
            row.get("publication_date")
        ),
        "created_date": optional_text(
            row.get("created_date")
        ),
        "updated_date": optional_text(
            row.get("updated_date")
        ),
        # Текст из внешнего Markdown уже встроен в content.
        "content_file_url": None,
    }

    new_articles.append(article)


new_by_slug = {
    article["slug"]: article
    for article in new_articles
}

new_slugs = set(new_by_slug)
old_slugs = set(old_by_slug)

added_slugs = sorted(new_slugs - old_slugs)
removed_slugs = sorted(old_slugs - new_slugs)

updated_slugs = sorted(
    slug
    for slug in old_slugs & new_slugs
    if old_by_slug[slug] != new_by_slug[slug]
)

unresolved_missing = sorted(
    slug
    for slug in missing_slugs
    if slug not in new_slugs
)

if unresolved_missing:
    raise RuntimeError(
        "The following missing articles are still absent:\n"
        + "\n".join(unresolved_missing)
    )


empty_non_podcast = [
    article["slug"]
    for article in new_articles
    if not article["content"]
    and article["section"] != "podcast"
]

if empty_non_podcast:
    raise RuntimeError(
        "Non-podcast articles still have no content:\n"
        + "\n".join(empty_non_podcast)
    )


write_json_atomic(
    ARTICLES_PATH,
    new_articles,
)

write_json_atomic(
    MISSING_PATH,
    [],
)


base44_cover_count = sum(
    "base44" in article["cover_image"]
    for article in new_articles
)

empty_content_count = sum(
    not article["content"]
    for article in new_articles
)


print()
print("Base44 export imported successfully")
print("-----------------------------------")
print(f"CSV records:                 {len(source_rows)}")
print(f"Published imported:          {len(new_articles)}")
print(f"Previously in project:       {len(old_articles)}")
print(f"Added articles:              {len(added_slugs)}")
print(f"Updated articles:            {len(updated_slugs)}")
print(f"Removed articles:            {len(removed_slugs)}")
print(f"Downloaded Markdown files:   {len(downloaded_content)}")
print(f"Empty content remaining:     {empty_content_count}")
print(f"Base44 cover URLs remaining: {base44_cover_count}")
print(f"Missing articles remaining:  {len(unresolved_missing)}")

if added_slugs:
    print()
    print("Added:")
    for slug in added_slugs:
        print(f"  + {slug}")

if downloaded_content:
    print()
    print("Embedded external content:")
    for slug in downloaded_content:
        print(f"  + {slug}")

if removed_slugs:
    print()
    print("Removed:")
    for slug in removed_slugs:
        print(f"  - {slug}")
