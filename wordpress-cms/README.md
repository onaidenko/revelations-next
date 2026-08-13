# REVELATIONS CMS

CMS work is governed by [`../docs/PROJECT_CONSTITUTION.md`](../docs/PROJECT_CONSTITUTION.md),
including the Human Review and public/private evidence boundaries.

Version-controlled source snapshot of the custom WordPress MU plugins used by cms.revelations.me.

The live WordPress directory is not a Git repository.

Live source:
`/var/www/revelations-cms/public/wp-content/mu-plugins`

Production deployment uses the canonical executable procedure:

`bin/deploy-to-server.sh --dry-run --files <mu-plugin-file> [...]`

Run its matching `--deploy` command only after explicit production
authorization. A full MU-plugin sync requires explicit `--full`; scope is
never implicit. Repository safety rules and authorization requirements are in
[`../AGENTS.md`](../AGENTS.md).
