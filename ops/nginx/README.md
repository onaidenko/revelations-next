# REVELATIONS production Nginx rules

Read [`../../docs/PROJECT_CONSTITUTION.md`](../../docs/PROJECT_CONSTITUTION.md)
before operational work. User-visible frontend changes require staging review
and explicit product-owner approval before a separately authorized production
deployment.

Production Nginx configuration:

    /etc/nginx/sites-available/revelations-production

Required case-sensitive legacy rule:

    location = /About {
        return 301 https://revelations.me/about;
    }

Expected behavior:

- /about returns 200
- /About returns one 301
- final URL is https://revelations.me/about

Do not implement this redirect in next.config.mjs.

Next.js redirect matching is case-insensitive. A rule from /About to
/about would also match its own destination and create an infinite loop.

After changing Nginx:

    sudo nginx -t
    sudo systemctl reload nginx
    npm run verify:production-legacy-paths

Every future production deploy also verifies this contract before it
declares the release successful.
