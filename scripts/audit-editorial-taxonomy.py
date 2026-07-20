#!/usr/bin/env python3
from __future__ import annotations

import argparse
import csv
import html
import json
import re
import sys
import time
import urllib.request
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

EXPECTED_ARTICLES = 52

TOPIC_KEYWORDS: dict[str, list[str]] = {
    "ai-data": [
        "artificial intelligence",
        "generative ai",
        "machine learning",
        "deep learning",
        "large language model",
        "llm",
        "neural network",
        "computer vision",
        "ai-powered",
        "ai powered",
        "ai-driven",
        "ai driven",
        "openai",
        "openxai",
        "anthropic",
        "qwen",
        "algorithm",
        "dataset",
        " ai ",
    ],
    "health-longevity-medtech": [
        "longevity",
        "medtech",
        "healthcare",
        "medical",
        "medicine",
        "patient",
        "surgeon",
        "surgery",
        "dentist",
        "lungs",
        "wellness",
        "sleep",
        "burnout",
        "health",
        "clinical",
    ],
    "startups-founders-investment": [
        "venture capital",
        "venture fund",
        "funding round",
        "investment",
        "investor",
        "startup",
        "founder",
        "co-founder",
        "raised",
        "funding",
        "business model",
        "entrepreneur",
        "scaleup",
    ],
    "future-work-leadership": [
        "future of work",
        "leadership",
        "workplace",
        "company culture",
        "toxic culture",
        "board",
        "team",
        "management",
        "office",
        "career",
        "employee",
    ],
}


def canonical_text(value: Any) -> str:
    text = "" if value is None else str(value)
    for _ in range(3):
        decoded = html.unescape(text)
        if decoded == text:
            break
        text = decoded
    return re.sub(r"\s+", " ", text).strip()


def strip_html(value: Any) -> str:
    text = canonical_text(value)
    text = re.sub(r"<script\b[^>]*>.*?</script>", " ", text, flags=re.I | re.S)
    text = re.sub(r"<style\b[^>]*>.*?</style>", " ", text, flags=re.I | re.S)
    text = re.sub(r"<[^>]+>", " ", text)
    return canonical_text(text)


def normalized(value: Any) -> str:
    return f" {strip_html(value).casefold()} "


def term_slug(value: Any) -> str:
    return value.get("slug", "") if isinstance(value, dict) else ""


def term_name(value: Any) -> str:
    return canonical_text(value.get("name", "")) if isinstance(value, dict) else ""


def term_slugs(values: Any) -> list[str]:
    if not isinstance(values, list):
        return []
    return sorted(value for item in values if (value := term_slug(item)))


def term_names(values: Any) -> list[str]:
    if not isinstance(values, list):
        return []
    return sorted(value for item in values if (value := term_name(item)))


def fetch_json(url: str, attempts: int = 4) -> Any:
    last: Exception | None = None
    for attempt in range(1, attempts + 1):
        try:
            request = urllib.request.Request(
                url,
                headers={
                    "Accept": "application/json",
                    "User-Agent": "revelations-taxonomy-audit/1.0",
                },
            )
            with urllib.request.urlopen(request, timeout=35) as response:
                if response.status != 200:
                    raise RuntimeError(f"HTTP {response.status}: {url}")
                return json.loads(response.read().decode("utf-8"))
        except Exception as error:
            last = error
            if attempt < attempts:
                time.sleep(attempt * 2)
    raise RuntimeError(f"request failed: {url}: {last}")


def fetch_articles(cms_api: str) -> list[dict[str, Any]]:
    articles: list[dict[str, Any]] = []
    page = 1
    while True:
        payload = fetch_json(f"{cms_api}/articles?page={page}&per_page=100")
        items = payload.get("items")
        if not isinstance(items, list):
            raise RuntimeError("invalid CMS article payload")
        articles.extend(items)
        total_pages = int(payload.get("pagination", {}).get("total_pages") or 1)
        if page >= total_pages:
            break
        page += 1
    return articles


def semantic_scores(article: dict[str, Any]) -> dict[str, int]:
    fields = [
        (normalized(article.get("title")), 4),
        (normalized(article.get("excerpt")), 2),
        (normalized(article.get("content")), 1),
    ]
    scores: dict[str, int] = {}
    for topic, keywords in TOPIC_KEYWORDS.items():
        score = 0
        for keyword in keywords:
            needle = keyword.casefold()
            for text, weight in fields:
                if needle in text:
                    score += weight
        scores[topic] = score
    return scores


def reviewed_assignment_keys(decisions: dict[str, Any]) -> set[tuple[str, str, tuple[str, ...]]]:
    keys: set[tuple[str, str, tuple[str, ...]]] = set()
    for item in decisions.get("reviewed_semantic_assignments", []):
        keys.add(
            (
                item["slug"],
                item["primary_topic"],
                tuple(sorted(item.get("secondary_topics", []))),
            )
        )
    return keys


def run_audit(args: argparse.Namespace) -> dict[str, Any]:
    cms_api = args.cms_api.rstrip("/")
    taxonomy = json.loads(args.map.read_text(encoding="utf-8"))
    tags = json.loads(args.tag_map.read_text(encoding="utf-8"))
    decisions = json.loads(args.decisions.read_text(encoding="utf-8"))

    health = fetch_json(f"{cms_api}/health")
    if health.get("status") != "ok":
        raise RuntimeError(f"CMS health failed: {health}")

    articles = fetch_articles(cms_api)
    if len(articles) != EXPECTED_ARTICLES:
        raise RuntimeError(
            f"expected {EXPECTED_ARTICLES} published articles, got {len(articles)}"
        )

    taxonomy_by_slug = {
        item["slug"]: item for item in taxonomy.get("assignments", [])
    }
    tags_by_slug = {
        item["slug"]: item for item in tags.get("assignments", [])
    }
    related_by_slug = {
        item["source_slug"]: item.get("target_slugs", [])
        for item in taxonomy.get("manual_related", [])
    }
    reviewed = reviewed_assignment_keys(decisions)

    live_slugs = {article["slug"] for article in articles}
    findings: list[dict[str, Any]] = []
    article_rows: list[dict[str, Any]] = []

    if live_slugs != set(taxonomy_by_slug):
        findings.append(
            {
                "severity": "critical",
                "code": "taxonomy_map_slug_drift",
                "slug": None,
                "message": "Live CMS slugs differ from the approved taxonomy map.",
            }
        )
    if live_slugs != set(tags_by_slug):
        findings.append(
            {
                "severity": "critical",
                "code": "tag_map_slug_drift",
                "slug": None,
                "message": "Live CMS slugs differ from the approved entity-tag map.",
            }
        )

    for article in articles:
        slug = article["slug"]
        expected = taxonomy_by_slug.get(slug)
        expected_tags = tags_by_slug.get(slug)

        primary = term_slug(article.get("primary_topic"))
        all_topics = term_slugs(article.get("topics") or [])
        secondary = sorted(topic for topic in all_topics if topic != primary)
        series = term_slug(article.get("series"))
        locations = term_names(article.get("locations") or [])
        entity_tags = term_names(article.get("tags") or [])
        manual_related = [
            term_slug(item)
            for item in (article.get("manual_related") or [])
            if term_slug(item)
        ]

        article_findings: list[dict[str, Any]] = []

        def add(severity: str, code: str, message: str, expected_value: Any = None, actual_value: Any = None) -> None:
            item = {
                "severity": severity,
                "code": code,
                "slug": slug,
                "title": strip_html(article.get("title")),
                "message": message,
            }
            if expected_value is not None:
                item["expected"] = expected_value
            if actual_value is not None:
                item["actual"] = actual_value
            findings.append(item)
            article_findings.append(item)

        if not primary or primary not in all_topics:
            add(
                "critical",
                "primary_topic_invalid",
                "Primary Topic is missing or is not assigned to the article.",
                all_topics,
                primary,
            )

        if expected is None:
            add(
                "critical",
                "taxonomy_assignment_missing",
                "Article is absent from the approved taxonomy map.",
            )
        else:
            comparisons = [
                (
                    "primary_topic_drift",
                    expected.get("primary_topic") or "",
                    primary,
                ),
                (
                    "secondary_topics_drift",
                    sorted(expected.get("secondary_topics") or []),
                    secondary,
                ),
                (
                    "series_drift",
                    expected.get("series") or "",
                    series,
                ),
                (
                    "location_drift",
                    [expected["location"]] if expected.get("location") else [],
                    locations,
                ),
                (
                    "public_eligibility_drift",
                    expected.get("public_topic_eligible") is not False,
                    article.get("public_topic_eligible") is not False,
                ),
                (
                    "taxonomy_status_drift",
                    expected.get("taxonomy_status") or "proposed",
                    article.get("taxonomy_status") or "proposed",
                ),
                (
                    "manual_related_drift",
                    related_by_slug.get(slug, []),
                    manual_related,
                ),
            ]
            for code, wanted, actual in comparisons:
                if wanted != actual:
                    add(
                        "high",
                        code,
                        "CMS taxonomy differs from the approved map.",
                        wanted,
                        actual,
                    )

        if expected_tags is None:
            add(
                "critical",
                "tag_assignment_missing",
                "Article is absent from the approved entity-tag map.",
            )
        else:
            wanted_tags = sorted(
                canonical_text(value)
                for value in expected_tags.get("target_tags", [])
            )
            if wanted_tags != entity_tags:
                add(
                    "high",
                    "entity_tags_drift",
                    "CMS entity tags differ from the approved tag map.",
                    wanted_tags,
                    entity_tags,
                )

        scores = semantic_scores(article)
        assignment_key = (slug, primary, tuple(secondary))

        if (
            "ai-data" in all_topics
            and scores.get("ai-data", 0) == 0
        ):
            add(
                "high",
                "ai_topic_without_text_evidence",
                "AI & Data is assigned, but no configured AI/data signal was found.",
                actual_value=all_topics,
            )

        ranked = sorted(scores.items(), key=lambda item: (-item[1], item[0]))
        best_topic, best_score = ranked[0]
        primary_score = scores.get(primary, 0)
        if (
            primary
            and primary_score == 0
            and best_score >= 10
            and best_topic != primary
            and assignment_key not in reviewed
        ):
            add(
                "high",
                "primary_topic_semantic_mismatch",
                "Deterministic text signals support another primary Topic.",
                {"review_candidate": best_topic, "score": best_score},
                {"primary_topic": primary, "score": primary_score},
            )

        article_rows.append(
            {
                "slug": slug,
                "title": strip_html(article.get("title")),
                "url": f"https://revelations.me/{slug}",
                "primary_topic": primary,
                "secondary_topics": secondary,
                "series": series,
                "locations": locations,
                "entity_tags": entity_tags,
                "taxonomy_status": article.get("taxonomy_status") or "proposed",
                "semantic_scores": scores,
                "findings": article_findings,
            }
        )

    severity_counts = Counter(item["severity"] for item in findings)
    issue_counts = Counter(item["code"] for item in findings)
    review_slugs = sorted(
        {
            item["slug"]
            for item in findings
            if item.get("slug")
            and item["severity"] in {"critical", "high"}
        }
    )

    result = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "mode": "read-only",
        "cms_api": cms_api,
        "summary": {
            "published_articles": len(articles),
            "review_candidates": len(review_slugs),
            "review_slugs": review_slugs,
            "critical": severity_counts.get("critical", 0),
            "high": severity_counts.get("high", 0),
            "issue_counts": dict(sorted(issue_counts.items())),
            "drift_total": sum(
                count
                for code, count in issue_counts.items()
                if code.endswith("_drift")
            ),
        },
        "findings": findings,
        "articles": sorted(article_rows, key=lambda item: item["slug"]),
        "decisions_version": decisions.get("version"),
    }
    return result


def write_outputs(result: dict[str, Any], output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)

    json_path = output_dir / "seo-2c-taxonomy-audit.json"
    csv_path = output_dir / "seo-2c-taxonomy-audit.csv"
    md_path = output_dir / "seo-2c-taxonomy-audit.md"
    summary_path = output_dir / "seo-2c-summary.txt"

    json_path.write_text(
        json.dumps(result, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )

    fields = [
        "severity",
        "code",
        "slug",
        "title",
        "message",
        "expected",
        "actual",
    ]
    with csv_path.open("w", newline="", encoding="utf-8-sig") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields)
        writer.writeheader()
        for finding in result["findings"]:
            writer.writerow(
                {
                    field: (
                        json.dumps(finding.get(field), ensure_ascii=False)
                        if field in {"expected", "actual"} and field in finding
                        else finding.get(field, "")
                    )
                    for field in fields
                }
            )

    summary = result["summary"]
    lines = [
        "# SEO 2C — Taxonomy governance audit",
        "",
        f"- Generated: `{result['generated_at']}`",
        f"- Published articles: **{summary['published_articles']}**",
        f"- Review candidates: **{summary['review_candidates']}**",
        f"- Critical: **{summary['critical']}**",
        f"- High: **{summary['high']}**",
        f"- CMS/map drift: **{summary['drift_total']}**",
        "",
        "> Read-only audit. Semantic findings are review signals, not automatic changes.",
        "",
        "## Review queue",
        "",
    ]

    actionable = [
        item
        for item in result["findings"]
        if item["severity"] in {"critical", "high"}
    ]
    if not actionable:
        lines.append("No actionable findings.")
    else:
        for item in actionable:
            lines.append(
                f"- **{item['severity'].upper()} — {item['code']}** "
                f"`{item.get('slug') or 'global'}`: {item['message']}"
            )

    lines.extend(
        [
            "",
            "## Issue counts",
            "",
        ]
    )
    for code, count in summary["issue_counts"].items():
        lines.append(f"- `{code}`: {count}")

    lines.extend(
        [
            "",
            "No CMS, database, repository or production mutation was performed by this audit.",
            "",
        ]
    )
    md_path.write_text("\n".join(lines), encoding="utf-8")

    summary_text = "\n".join(
        [
            "===== SEO 2C TAXONOMY AUDIT COMPLETE =====",
            f"published_articles={summary['published_articles']}",
            f"review_candidates={summary['review_candidates']}",
            f"review_slugs={','.join(summary['review_slugs'])}",
            f"critical={summary['critical']}",
            f"high={summary['high']}",
            f"drift_total={summary['drift_total']}",
            f"report={md_path}",
            f"json={json_path}",
            f"csv={csv_path}",
            "cms_writes=0",
            "database_writes=0",
        ]
    ) + "\n"
    summary_path.write_text(summary_text, encoding="utf-8")
    print(summary_text, end="")


def self_test() -> None:
    assert canonical_text("Dolce &amp; Gabbana") == "Dolce & Gabbana"
    assert canonical_text("Dolce &amp;amp; Gabbana") == "Dolce & Gabbana"
    sample = {
        "title": "How OpenxAI Will Change the World",
        "excerpt": "",
        "content": "",
    }
    assert semantic_scores(sample)["ai-data"] > 0
    print("self_test=passed")


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--cms-api",
        default="https://cms.revelations.me/wp-json/revelations/v1",
    )
    parser.add_argument(
        "--map",
        type=Path,
        default=Path("data/seo/editorial-taxonomy-v2.json"),
    )
    parser.add_argument(
        "--tag-map",
        type=Path,
        default=Path("wordpress-cms/data/seo/editorial-post-tags-v1.json"),
    )
    parser.add_argument(
        "--decisions",
        type=Path,
        default=Path("data/seo/editorial-taxonomy-audit-decisions-v1.json"),
    )
    parser.add_argument(
        "--output-dir",
        type=Path,
        default=Path("artifacts/seo-2c-taxonomy-audit"),
    )
    parser.add_argument("--self-test", action="store_true")
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    if args.self_test:
        self_test()
        return 0
    result = run_audit(args)
    write_outputs(result, args.output_dir)
    return 0


if __name__ == "__main__":
    sys.exit(main())
