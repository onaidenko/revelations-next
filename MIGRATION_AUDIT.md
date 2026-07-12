# REVELATIONS migration audit — phase 1

## Inputs audited
- Base44 ZIP: 146 source files, snapshot dated 2026-06-07.
- Article CSV: 41 rows, 20 columns; all 41 marked `published`; no duplicate or missing slugs.
- Current sitemap log: 50 article URLs. The CSV is missing 9 later articles.

## Data findings
- Sections: news 9, people 4, tech 5, places 6, unspoken 7, podcast 10.
- 34 cover images use Base44-hosted URLs; 7 use Unsplash.
- 10 rows have no inline article content; these are mostly podcast entries with YouTube URLs.
- 10 rows have no excerpt and 10 have no author.
- 1 row references a separate `content_file_url`.
- No gated or sample articles in the export.

## Code findings
- Public frontend depends on Base44 for Article reads and Testimonial reads.
- Admin depends on Base44 Article CRUD, file upload and Base44 authentication.
- Editorial Desk depends on 5 Base44 function invocations and 4 editorial entities.
- 10 backend functions are included in the ZIP.
- The global app wraps even public pages in Base44 AuthContext, so a direct Vite deployment would remain coupled to Base44.
- Existing routes can be preserved exactly: `/`, six sections, `/archive`, static pages and root-level `/{slug}` articles.

## Migration decision
Phase 1 intentionally removes Base44 from the public site while keeping content in local JSON. This gives a safe staging build with server-rendered SEO before database/admin work begins.

## Generated starter
- Next.js 16.2 App Router.
- 41 articles normalized into `data/articles.json`.
- Dynamic server metadata and JSON-LD.
- Real 404 responses.
- Generated sitemap and robots.
- Docker Compose bound to `127.0.0.1:3001`.
- Nginx staging virtual host.
- PostgreSQL schema prepared for phase 2.

## Missing articles
- bwiga-will-be-held-in-montenegro-again-in-september
- daria-barkova-art-profiling-turns-the-inner-world-into-a-visual-map
- elys-life-to-host-private-launch-event-in-dubai
- global-tech-weekend-tbilisi-2026-how-a-citywide-festival-united-the-regions-tech-ecosystem
- global-tech-weekend-tbilisi-how-the-city-became-an-interface-for-a-new-tech-region
- global-tech-weekend-tbilisi-returns-on-june-19-21
- mena-blockchain-week-2026-concludes-how-dubais-first-city-wide-blockchain-initiative-drew-5000-attendees-across-40-events
- sam-kaploushenko-the-future-of-money-is-invisible
- the-brands-and-speakers-you-dont-want-to-miss-at-global-tech-weekend-tbilisi-2026

## Safety boundaries
- No DNS changes.
- No Vultr or FlyFlyFriends changes.
- No production deployment.
- No deletion from Base44.

## CSV rows requiring content review
- `mena-blockchain-week`: missing_content, external_content_file
- `new-era-of-digital-money-and-happiness`: missing_content, missing_excerpt, missing_author
- `33-qs-for-timur-makhmudi`: missing_content, missing_excerpt, missing_author
- `how-openxai-will-change-the-worlds-perceptions`: missing_content, missing_excerpt, missing_author
- `33-qs-for-sergei-medvedev`: missing_content, missing_excerpt, missing_author
- `33-qs-for-sergey-khusnetdinov`: missing_content, missing_excerpt, missing_author
- `dariush-soudi-whats-really-behind-this-success`: missing_content, missing_excerpt, missing_author
- `33-qs-for-anna-lou`: missing_content, missing_excerpt, missing_author
- `33-qs-for-metakse-ghambaryan`: missing_content, missing_excerpt, missing_author
- `33-qs-for-arman-mamyan`: missing_content, missing_excerpt, missing_author
- `33-qs-for-andranik-togramadzhian`: missing_excerpt, missing_author
- `what-investors-dont-tell-you-after-they-pass`: missing_publication_date
- `the-co-founder-divorce-nobody-talks-about`: missing_publication_date
- `i-raised-8m-and-still-failed`: missing_publication_date
- `why-we-killed-our-best-product`: missing_publication_date
- `burnout-doesnt-look-like-burnout`: missing_publication_date
- `the-board-that-betrayed-its-founder`: missing_publication_date
- `the-toxic-culture-we-accidentally-built`: missing_publication_date
