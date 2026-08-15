import assert from "node:assert/strict";
import fs from "node:fs";
import test from "node:test";

const taxonomy = JSON.parse(
  fs.readFileSync("data/seo/editorial-taxonomy-v2.json", "utf8"),
);
const decisions = JSON.parse(
  fs.readFileSync(
    "data/seo/editorial-taxonomy-audit-decisions-v1.json",
    "utf8",
  ),
);
const packageJson = JSON.parse(fs.readFileSync("package.json", "utf8"));

const bySlug = new Map(
  taxonomy.assignments.map((assignment) => [assignment.slug, assignment]),
);

test("approved taxonomy map reflects the current production CMS state", () => {
  const sergei = bySlug.get("33-qs-for-sergei-medvedev");
  assert.equal(
    sergei.primary_topic,
    "blockchain-fintech-digital-money",
  );
  assert.deepEqual(
    sergei.secondary_topics,
    ["startups-founders-investment"],
  );

  const ashton = bySlug.get(
    "ashton-hettiarachi-how-openxai-will-change-the-worlds-perceptions",
  );
  assert.equal(
    ashton.series,
    "revelations-podcast",
  );
  assert.equal(bySlug.has("burnout-doesnt-look-like-burnout"), false);
});

test("superseded taxonomy review exceptions are absent", () => {
  assert.deepEqual(decisions.reviewed_semantic_assignments, []);
  assert.deepEqual(decisions.manual_review, []);
});

test("taxonomy audit has a stable package command", () => {
  assert.equal(
    packageJson.scripts["audit:taxonomy"],
    "python3 scripts/audit-editorial-taxonomy.py",
  );
});
