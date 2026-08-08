# Byte-preserving CMS dash migration design

This design implements `ASCII_HYPHEN_ONLY` without passing historical raw HTML
through WordPress save-time normalization. It records the architecture used by
the completed production migration and the invariants retained by the reusable
read-only audit/maintenance tooling.

## Architecture decision

Three approaches were evaluated:

1. A controlled storage-level update provides exact byte control, one database
   transaction, prepared statements, raw SHA-256 concurrency guards and exact
   rollback. It bypasses WordPress revisions, save hooks and automatic cache
   invalidation, so those responsibilities must be explicit.
2. A WordPress API write under an unfiltered capability preserves normal hooks,
   revisions and cache behavior, but is not a byte-preservation proof. KSES and
   other filters are context- and plugin-dependent; incident recovery already
   demonstrated unrelated historical HTML changes in CLI context.
3. A field-specific hybrid could use APIs for fields whose round trip is proven
   and storage writes for legacy content. It splits failure and rollback
   semantics: hooks may escape the database transaction and partial external
   side effects cannot be rolled back atomically.

The selected architecture is option 1 for every affected field in one approved
plan. This is the only option that gives uniform atomicity and makes the
planned raw value the exact stored value. No capability manipulation or KSES
filter removal is part of the design.

## Applied transaction contract

The completed operation followed this fail-closed contract:

- Re-run the raw scanner immediately before approval and store the plan and a
  complete affected-record snapshot as mode `0600` files outside web roots.
- Snapshot exact affected values plus protected post columns, all meta rows,
  taxonomy relationships and current raw hashes. Record database/table identity
  and plan SHA-256 without recording secrets.
- Begin one InnoDB transaction. Lock all affected `wp_posts` rows and relevant
  `wp_postmeta` rows with `SELECT ... FOR UPDATE`, then verify every affected
  field of each record against its approved `current_raw_sha256`. Any mismatch
  aborts the entire transaction before the first update.
- Use `$wpdb->prepare()` for narrowly scoped updates. Posts use `WHERE ID = %d
  AND BINARY column = %s`; meta uses the unique `meta_id`, expected key and
  `BINARY meta_value`. Require exactly one affected row for every update.
- Read all values back inside the transaction and require their SHA-256 hashes
  to equal `planned_raw_sha256`. Recheck the protected-state snapshot. Roll back
  on any mismatch; otherwise commit once.
- Do not update `post_modified`, `post_modified_gmt`, slugs, status, authors,
  GUIDs, taxonomy, unrelated metadata or private evidence. Storage-level writes
  do not create WordPress revisions; the exact backup and manifest are the
  authoritative rollback source. This limitation must be explicit in approval.
- After commit only, call `clean_post_cache()` for affected IDs and send the
  existing signed frontend revalidation. Then verify raw hashes again, audit the
  public CMS API and representative public pages. A failure after commit uses a
  new guarded transaction whose expected values are the applied hashes and whose
  targets are the exact backup values; never use `wp_update_post()` for recovery.

The separately authorized one-off apply helper failed closed unless the approved
plan hash and an explicit confirmation token were supplied. It accepted no
table names, columns or meta keys outside the fixed field registry. That helper
is not retained in the repository and the completed migration must not be
rerun. The retained plan builder and pure normalization library can be reused
for read-only audits; any future mutation requires a new, separately authorized
and equally guarded operational procedure.
