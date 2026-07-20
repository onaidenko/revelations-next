#!/usr/bin/env python3
from __future__ import annotations

import argparse
import html
import json
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from html.parser import HTMLParser
from pathlib import Path
from typing import Any

DEFAULT_ORIGIN = "https://revelations.me"
TAXONOMY_PREFIXES = (
    "/topics/",
    "/series/",
    "/locations/",
    "/tags/",
)
RESERVED_ROOT_PATHS = {
    "",
    "about",
    "access",
    "advertise",
    "archive",
    "contact",
    "news",
    "people",
    "places",
    "podcast",
    "tech",
    "unspoken",
}


def clean_text(value: Any) -> str:
    text = "" if value is None else str(value)
    for _ in range(4):
        decoded = html.unescape(text)
        if decoded == text:
            break
        text = decoded
    return re.sub(r"\s+", " ", text).strip()


def normalized_url(value: str) -> str:
    parsed = urllib.parse.urlsplit(value)
    path = parsed.path or "/"
    if path != "/":
        path = path.rstrip("/")
    return urllib.parse.urlunsplit(
        (
            parsed.scheme.lower(),
            parsed.netloc.lower(),
            path,
            "",
            "",
        )
    )


@dataclass
class FetchResult:
    status: int
    final_url: str
    body: str
    content_type: str
    error: str = ""


class PageParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.title_parts: list[str] = []
        self.h1s: list[str] = []
        self.paragraphs: list[str] = []
        self.canonicals: list[str] = []
        self.meta: list[dict[str, str]] = []
        self.jsonld_raw: list[str] = []
        self._in_title = False
        self._in_h1 = False
        self._in_p = False
        self._in_jsonld = False
        self._buffer: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        attributes = {
            key.lower(): (value or "")
            for key, value in attrs
        }
        tag = tag.lower()

        if tag == "title":
            self._in_title = True
        elif tag == "h1":
            self._in_h1 = True
            self._buffer = []
        elif tag == "p":
            self._in_p = True
            self._buffer = []
        elif tag == "link":
            rel = set(
                attributes.get("rel", "").casefold().split()
            )
            if "canonical" in rel and attributes.get("href"):
                self.canonicals.append(attributes["href"])
        elif tag == "meta":
            self.meta.append(attributes)
        elif (
            tag == "script"
            and attributes.get("type", "").casefold()
            == "application/ld+json"
        ):
            self._in_jsonld = True
            self._buffer = []

    def handle_endtag(self, tag: str) -> None:
        tag = tag.lower()

        if tag == "title":
            self._in_title = False
        elif tag == "h1" and self._in_h1:
            self._in_h1 = False
            value = clean_text("".join(self._buffer))
            if value:
                self.h1s.append(value)
        elif tag == "p" and self._in_p:
            self._in_p = False
            value = clean_text("".join(self._buffer))
            if value:
                self.paragraphs.append(value)
        elif tag == "script" and self._in_jsonld:
            self._in_jsonld = False
            value = "".join(self._buffer).strip()
            if value:
                self.jsonld_raw.append(value)

    def handle_data(self, data: str) -> None:
        if self._in_title:
            self.title_parts.append(data)
        if self._in_h1 or self._in_p or self._in_jsonld:
            self._buffer.append(data)

    @property
    def title(self) -> str:
        return clean_text("".join(self.title_parts))

    def meta_values(
        self,
        *,
        name: str | None = None,
        property_name: str | None = None,
    ) -> list[str]:
        result: list[str] = []
        for item in self.meta:
            if (
                name
                and item.get("name", "").casefold()
                == name.casefold()
            ):
                result.append(
                    clean_text(item.get("content"))
                )
            if (
                property_name
                and item.get("property", "").casefold()
                == property_name.casefold()
            ):
                result.append(
                    clean_text(item.get("content"))
                )
        return [value for value in result if value]


def request(
    base: str,
    path: str,
    *,
    token: str,
    accept: str,
    attempts: int = 3,
) -> FetchResult:
    separator = "&" if "?" in path else "?"
    url = (
        base.rstrip("/")
        + path
        + separator
        + "release_probe="
        + urllib.parse.quote(token)
    )

    last_error = ""
    for attempt in range(1, attempts + 1):
        req = urllib.request.Request(
            url,
            headers={
                "Accept": accept,
                "Accept-Encoding": "identity",
                "Cache-Control": "no-cache",
                "Pragma": "no-cache",
                "User-Agent": (
                    "REVELATIONS-Production-Release-Verifier/1.0"
                ),
            },
        )

        try:
            with urllib.request.urlopen(
                req,
                timeout=30,
            ) as response:
                return FetchResult(
                    status=int(response.status),
                    final_url=response.geturl(),
                    body=response.read().decode(
                        "utf-8",
                        errors="replace",
                    ),
                    content_type=response.headers.get(
                        "Content-Type",
                        "",
                    ),
                )
        except urllib.error.HTTPError as error:
            return FetchResult(
                status=int(error.code),
                final_url=error.geturl(),
                body=error.read().decode(
                    "utf-8",
                    errors="replace",
                ),
                content_type=error.headers.get(
                    "Content-Type",
                    "",
                ),
                error=str(error),
            )
        except Exception as error:
            last_error = str(error)
            if attempt < attempts:
                time.sleep(attempt)

    return FetchResult(
        status=0,
        final_url=url,
        body="",
        content_type="",
        error=last_error or "request failed",
    )


def parse_sitemap(xml_text: str) -> list[str]:
    root = ET.fromstring(xml_text)
    values: list[str] = []
    for element in root.iter():
        if element.tag.rsplit("}", 1)[-1] != "loc":
            continue
        value = clean_text(element.text)
        if value:
            values.append(normalized_url(value))
    return values


def iter_jsonld_nodes(value: Any):
    if isinstance(value, dict):
        yield value
        graph = value.get("@graph")
        if isinstance(graph, list):
            for item in graph:
                yield from iter_jsonld_nodes(item)
    elif isinstance(value, list):
        for item in value:
            yield from iter_jsonld_nodes(item)


def jsonld_types(node: dict[str, Any]) -> set[str]:
    value = node.get("@type")
    if isinstance(value, str):
        return {value}
    if isinstance(value, list):
        return {
            item
            for item in value
            if isinstance(item, str)
        }
    return set()


def parse_jsonld(
    raw_values: list[str],
) -> list[dict[str, Any]]:
    nodes: list[dict[str, Any]] = []
    for raw in raw_values:
        value = json.loads(raw)
        nodes.extend(iter_jsonld_nodes(value))
    return nodes


def page_signature(
    *,
    path: str,
    body: str,
    origin: str,
) -> tuple[dict[str, Any] | None, list[str]]:
    parser = PageParser()
    parser.feed(body)
    parser.close()

    errors: list[str] = []
    canonical = normalized_url(
        origin.rstrip("/") + path
    )
    title = parser.title
    descriptions = parser.meta_values(
        name="description"
    )
    robots = parser.meta_values(name="robots")
    og_urls = parser.meta_values(
        property_name="og:url"
    )
    og_descriptions = parser.meta_values(
        property_name="og:description"
    )
    twitter_descriptions = parser.meta_values(
        name="twitter:description"
    )

    if not title:
        errors.append("HTML title is missing")
    if title.casefold().startswith("not found"):
        errors.append("page rendered the Not found title")

    normalized_canonicals = [
        normalized_url(value)
        for value in parser.canonicals
    ]
    if normalized_canonicals != [canonical]:
        errors.append(
            "canonical mismatch: "
            + repr(normalized_canonicals)
        )

    if len(parser.h1s) != 1:
        errors.append(
            f"expected one H1, got {parser.h1s!r}"
        )

    if len(descriptions) != 1:
        errors.append(
            "expected one meta description, got "
            + repr(descriptions)
        )
    description = (
        descriptions[0] if len(descriptions) == 1 else ""
    )

    if og_urls != [canonical]:
        errors.append(
            "Open Graph URL mismatch: "
            + repr(og_urls)
        )
    if description and og_descriptions != [description]:
        errors.append(
            "Open Graph description mismatch"
        )
    if (
        description
        and twitter_descriptions != [description]
    ):
        errors.append(
            "Twitter description mismatch"
        )

    if any(
        "noindex" in value.casefold()
        for value in robots
    ):
        errors.append("robots noindex found")

    if not parser.paragraphs:
        errors.append("visible page paragraph is missing")

    try:
        nodes = parse_jsonld(parser.jsonld_raw)
    except Exception as error:
        errors.append(f"invalid JSON-LD: {error}")
        nodes = []

    types = {
        type_name
        for node in nodes
        for type_name in jsonld_types(node)
    }
    if "BreadcrumbList" not in types:
        errors.append("BreadcrumbList JSON-LD missing")

    collections = [
        node
        for node in nodes
        if "CollectionPage" in jsonld_types(node)
    ]
    if len(collections) != 1:
        errors.append(
            "expected one CollectionPage JSON-LD, got "
            f"{len(collections)}"
        )
    elif normalized_url(
        clean_text(collections[0].get("url"))
    ) != canonical:
        errors.append(
            "CollectionPage canonical URL mismatch"
        )

    if "https://staging.revelations.me" in body:
        errors.append("staging-domain leakage")

    if errors:
        return None, errors

    collection_description = clean_text(
        collections[0].get("description")
    )

    return {
        "path": path,
        "title": title,
        "canonical": canonical,
        "h1": parser.h1s[0],
        "description": description,
        "og_description": og_descriptions[0],
        "twitter_description": (
            twitter_descriptions[0]
        ),
        "first_paragraph": parser.paragraphs[0],
        "collection_description": (
            collection_description
        ),
        "jsonld_types": sorted(types),
    }, []


def representative_article_path(
    sitemap_urls: list[str],
    origin: str,
) -> str:
    origin_host = urllib.parse.urlsplit(origin).netloc

    for value in sitemap_urls:
        parsed = urllib.parse.urlsplit(value)
        path = parsed.path.rstrip("/")
        root = path.strip("/")

        if parsed.netloc != origin_host:
            continue
        if not root or "/" in root:
            continue
        if root in RESERVED_ROOT_PATHS:
            continue
        if root in {
            "sitemap.xml",
            "news-sitemap.xml",
            "robots.txt",
        }:
            continue
        return path

    return ""


def snapshot(
    *,
    base: str,
    origin: str,
) -> tuple[dict[str, Any] | None, list[str]]:
    token = str(time.time_ns())
    errors: list[str] = []

    sitemap_result = request(
        base,
        "/sitemap.xml",
        token=token,
        accept="application/xml,text/xml",
    )
    if sitemap_result.status != 200:
        return None, [
            "main sitemap returned "
            f"HTTP {sitemap_result.status}"
        ]

    try:
        sitemap_urls = parse_sitemap(
            sitemap_result.body
        )
    except Exception as error:
        return None, [
            f"main sitemap is invalid XML: {error}"
        ]

    taxonomy_paths = sorted(
        {
            urllib.parse.urlsplit(value).path.rstrip("/")
            for value in sitemap_urls
            if urllib.parse.urlsplit(value).path.startswith(
                TAXONOMY_PREFIXES
            )
        }
    )

    if not taxonomy_paths:
        errors.append(
            "main sitemap contains no taxonomy hubs"
        )

    family_counts = {
        prefix.strip("/"): sum(
            path.startswith(prefix)
            for path in taxonomy_paths
        )
        for prefix in TAXONOMY_PREFIXES
    }
    missing_families = [
        name
        for name, count in family_counts.items()
        if count == 0
    ]
    if missing_families:
        errors.append(
            "taxonomy families missing from sitemap: "
            + ", ".join(missing_families)
        )

    pages: dict[str, dict[str, Any]] = {}
    for path in taxonomy_paths:
        result = request(
            base,
            path,
            token=token,
            accept="text/html",
        )
        if result.status != 200:
            errors.append(
                f"{path}: HTTP {result.status}"
            )
            continue

        signature, page_errors = page_signature(
            path=path,
            body=result.body,
            origin=origin,
        )
        if page_errors:
            errors.extend(
                f"{path}: {item}"
                for item in page_errors
            )
        elif signature:
            pages[path] = signature

    news_result = request(
        base,
        "/news-sitemap.xml",
        token=token,
        accept="application/xml,text/xml",
    )
    if news_result.status != 200:
        errors.append(
            "news sitemap returned "
            f"HTTP {news_result.status}"
        )
    elif re.search(
        r"/(?:topics|series|locations|tags)/",
        news_result.body,
    ):
        errors.append(
            "news sitemap contains taxonomy hubs"
        )

    unknown_path = (
        "/topics/release-verifier-missing-"
        + token
    )
    unknown_result = request(
        base,
        unknown_path,
        token=token,
        accept="text/html",
    )
    if unknown_result.status != 404:
        errors.append(
            "unknown taxonomy hub returned "
            f"HTTP {unknown_result.status}"
        )

    for path in (
        "/",
        representative_article_path(
            sitemap_urls,
            origin,
        ),
    ):
        if not path:
            errors.append(
                "representative article path was not found"
            )
            continue
        result = request(
            base,
            path,
            token=token,
            accept="text/html",
        )
        if result.status != 200:
            errors.append(
                f"{path}: HTTP {result.status}"
            )
        elif (
            "https://staging.revelations.me"
            in result.body
        ):
            errors.append(
                f"{path}: staging-domain leakage"
            )

    if errors:
        return None, errors

    return {
        "version": 1,
        "origin": origin.rstrip("/"),
        "taxonomy_paths": taxonomy_paths,
        "family_counts": family_counts,
        "pages": pages,
        "representative_article": (
            representative_article_path(
                sitemap_urls,
                origin,
            )
        ),
    }, []


def verify(
    *,
    base: str,
    manifest: dict[str, Any],
) -> list[str]:
    current, errors = snapshot(
        base=base,
        origin=manifest["origin"],
    )
    if errors or current is None:
        return errors

    if (
        current["taxonomy_paths"]
        != manifest["taxonomy_paths"]
    ):
        errors.append(
            "taxonomy sitemap set differs from candidate: "
            f"missing={sorted(set(manifest['taxonomy_paths']) - set(current['taxonomy_paths']))} "
            f"extra={sorted(set(current['taxonomy_paths']) - set(manifest['taxonomy_paths']))}"
        )

    for path in manifest["taxonomy_paths"]:
        expected = manifest["pages"].get(path)
        actual = current["pages"].get(path)
        if expected is None:
            errors.append(
                f"{path}: missing candidate signature"
            )
        elif actual is None:
            errors.append(
                f"{path}: missing public signature"
            )
        elif actual != expected:
            differing = sorted(
                key
                for key in expected
                if actual.get(key) != expected.get(key)
            )
            errors.append(
                f"{path}: public signature differs "
                f"in {differing}"
            )

    if (
        current["representative_article"]
        != manifest["representative_article"]
    ):
        errors.append(
            "representative article differs from candidate"
        )

    return errors


def print_errors(
    *,
    mode: str,
    errors: list[str],
) -> int:
    print(f"verification_mode={mode}")
    print(f"errors={len(errors)}")
    for error in errors:
        print(f"ERROR {error}")
    return 1


def self_test() -> int:
    origin = DEFAULT_ORIGIN
    path = "/topics/ai-data"
    canonical = origin + path
    description = (
        "Explore REVELATIONS coverage of AI and data "
        "through systems, infrastructure, and practical use."
    )
    html_doc = f"""<!doctype html>
<html>
<head>
<title>AI &amp; Data — REVELATIONS</title>
<link rel="canonical" href="{canonical}">
<meta name="description" content="{description}">
<meta property="og:url" content="{canonical}">
<meta property="og:description" content="{description}">
<meta name="twitter:description" content="{description}">
<script type="application/ld+json">{{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": []
}}</script>
<script type="application/ld+json">{{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "url": "{canonical}",
  "description": "{description}"
}}</script>
</head>
<body>
<h1>AI &amp; Data</h1>
<p>Editorial introduction for the collection.</p>
</body>
</html>"""

    signature, errors = page_signature(
        path=path,
        body=html_doc,
        origin=origin,
    )
    assert not errors
    assert signature is not None
    assert signature["h1"] == "AI & Data"
    assert signature["canonical"] == canonical

    broken, broken_errors = page_signature(
        path=path,
        body=html_doc.replace(
            canonical,
            origin + "/wrong",
            1,
        ),
        origin=origin,
    )
    assert broken is None
    assert any(
        "canonical mismatch" in item
        for item in broken_errors
    )

    sitemap = """<?xml version="1.0"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc>https://revelations.me/topics/ai-data</loc></url>
<url><loc>https://revelations.me/tags/openai</loc></url>
</urlset>"""
    assert parse_sitemap(sitemap) == [
        "https://revelations.me/topics/ai-data",
        "https://revelations.me/tags/openai",
    ]

    print("production_release_verifier_self_test=passed")
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    subparsers = parser.add_subparsers(
        dest="command",
        required=True,
    )

    snapshot_parser = subparsers.add_parser(
        "snapshot"
    )
    snapshot_parser.add_argument(
        "--base",
        required=True,
    )
    snapshot_parser.add_argument(
        "--origin",
        default=DEFAULT_ORIGIN,
    )
    snapshot_parser.add_argument(
        "--output",
        required=True,
    )

    verify_parser = subparsers.add_parser(
        "verify"
    )
    verify_parser.add_argument(
        "--base",
        required=True,
    )
    verify_parser.add_argument(
        "--manifest",
        required=True,
    )

    subparsers.add_parser("self-test")

    args = parser.parse_args()

    if args.command == "self-test":
        return self_test()

    if args.command == "snapshot":
        manifest, errors = snapshot(
            base=args.base,
            origin=args.origin,
        )
        if errors or manifest is None:
            return print_errors(
                mode="snapshot",
                errors=errors,
            )

        output_path = Path(args.output)
        output_path.parent.mkdir(
            parents=True,
            exist_ok=True,
        )
        output_path.write_text(
            json.dumps(
                manifest,
                indent=2,
                ensure_ascii=False,
            )
            + "\n",
            encoding="utf-8",
        )
        print("verification_mode=snapshot")
        print(
            "taxonomy_hubs="
            + str(len(manifest["taxonomy_paths"]))
        )
        print(
            "taxonomy_families="
            + json.dumps(
                manifest["family_counts"],
                sort_keys=True,
            )
        )
        print(f"manifest={output_path}")
        return 0

    manifest = json.loads(
        Path(args.manifest).read_text(
            encoding="utf-8"
        )
    )
    errors = verify(
        base=args.base,
        manifest=manifest,
    )
    if errors:
        return print_errors(
            mode="verify",
            errors=errors,
        )

    print("verification_mode=verify")
    print(
        "taxonomy_hubs="
        + str(len(manifest["taxonomy_paths"]))
    )
    print("candidate_public_match=yes")
    print("sitemap=correct")
    print("news_sitemap=clean")
    print("unknown_hub_404=correct")
    print("staging_leakage=none")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        raise SystemExit(130)
    except Exception as error:
        print(
            f"ERROR verifier_internal_failure: {error}",
            file=sys.stderr,
        )
        raise SystemExit(1)
