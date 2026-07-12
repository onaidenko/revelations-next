# REVELATIONS — migration phase 1

Independent Next.js staging build created from the Base44 ZIP and Article CSV.

## Current scope
- 41 exported articles in `data/articles.json`
- Existing root-level article URLs preserved (`/{slug}`)
- Server-generated title, description, canonical, Open Graph and Article/NewsArticle JSON-LD
- Real HTTP 404 through `notFound()`
- Generated `/sitemap.xml` and `/robots.txt`
- Legacy redirects preserved
- Docker and Nginx staging configs included
- No Base44 SDK dependency

## Not in phase 1
- 9 articles listed in `data/missing-articles.json`
- PostgreSQL-backed admin panel
- Editorial AI automation
- Local image migration
- Exact final visual QA against production

## Local run
```bash
cp .env.example .env
npm install
npm run dev
```

## Docker staging
```bash
cp .env.example .env
# change NEXT_PUBLIC_SITE_URL to the staging domain
docker compose up -d --build
```

The container binds only to `127.0.0.1:3001`; expose it through the supplied Nginx virtual host.
