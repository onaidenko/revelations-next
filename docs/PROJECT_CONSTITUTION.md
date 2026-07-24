# REVELATIONS Project Constitution

This is the canonical and permanent project-governance document. Every AI,
agent, contributor, deployment and editorial workflow must obey it. If a task
conflicts with this Constitution, stop and report the conflict. Only explicit
product-owner approval may amend a constitutional rule.

## Brand and identity

`REVELATIONS_BRAND_NAME_REQUIRED`

- The canonical public brand name is `REVELATIONS`.
- Use that exact uppercase spelling in REVELATIONS-owned UI, metadata, schema,
  generated editorial content and publisher copy. URLs, slugs, filenames,
  code identifiers, API/database keys, third-party quotations and genuine
  proper names are exceptions.

`ASCII_HYPHEN_ONLY`

- REVELATIONS-authored public copy uses ASCII hyphen-minus (`-`), never en or
  em dashes. Preserve punctuation in verbatim quotations, evidence and
  historical imported content.

`CANONICAL_BRAND_TAGLINE_REQUIRED`

- The exact tagline is `Born as a podcast. Built as a media platform.` Do not
  paraphrase or substitute it.
- `Future-Facing Media from Dubai` is the distinct semantic descriptor. Use it
  in appropriate semantic, metadata, About and publisher contexts, but do not
  mechanically repeat it beside the tagline.
- Homepage identity is the header's REVELATIONS mark, exact tagline and
  navigation. Do not restore a duplicate identity block below the header.

`CANONICAL_JULIA_UPITERSKAYA`

- The canonical public identity is `Julia Upiterskaya`, slug
  `julia-upiterskaya`. `Julia U.` and `Julia Yupiterskaya` are legacy aliases
  only and must never be newly emitted as canonical public output.
- Public listings, article bylines, profiles and schema must use the resolved
  canonical author relation. Julia and Alina are Persons; Editorial Team is an
  Organization and must never be emitted as a Person.
- Do not fabricate biographies, affiliations, roles, social links, images,
  sources, disclosures, entity properties or organization relationships. Leave
  unverified data absent.

## Editorial and evidence boundaries

- Current editorial priorities are Artificial Intelligence and Wellness within
  Emerging Technology. Historical Web3/crypto coverage stays factually intact,
  but is not automatically the primary current brand signal. Art is a future
  direction, not an approved top-level section.
- AI may suggest content but cannot silently approve or publish protected
  editorial fields. Reuse the canonical Human Review implementation; never
  forge review hashes, permissions or reviewer identities.

`PUBLIC_PRIVATE_EVIDENCE_BOUNDARY`

- Internal source text, snapshots, evidence units, fact-check material,
  private reasoning, prompts, responses, AI metadata and review metadata are
  never public fallback content. Public sources come only from explicitly
  approved public fields.
- Do not rewrite legacy CMS data merely for cosmetic normalization when public
  canonical resolution already gives correct behavior.

## Staging-first visual policy

`STAGING_VISUAL_APPROVAL_REQUIRED`

Every user-visible frontend change, including a one-pixel spacing, typography,
layout, responsive, button, image or taxonomy adjustment, follows this order:

1. Local implementation and automated checks.
2. Production-configured local build.
3. Deployment to staging only.
4. Staging functional and responsive visual QA.
5. Explicit product-owner approval.
6. Only then, separately authorized production deployment and smoke test.

Production is never a visual design-review environment. Screenshots and
automated checks do not replace product-owner approval. If screenshots cannot
be captured, report the staging URLs and stop. Do not change production CMS,
production cache or the production frontend for visual experimentation.

## Enforcement

- Governance and instruction documents must prominently reference this file.
- `test/project-constitution.test.mjs` protects the required constitutional
  markers and critical governance surfaces.
- The current task text does not silently supersede this Constitution. Report
  conflicts for an explicit product-owner decision.
