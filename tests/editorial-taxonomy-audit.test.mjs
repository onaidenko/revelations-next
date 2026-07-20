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

test("approved SEO 2C taxonomy corrections are canonical", () => {
  const cofounder = bySlug.get(
    "the-co-founder-divorce-nobody-talks-about",
  );
  assert.equal(
    cofounder.primary_topic,
    "startups-founders-investment",
  );
  assert.deepEqual(
    cofounder.secondary_topics,
    ["future-work-leadership"],
  );

  const quantum = bySlug.get(
    "quantum-computing-is-finally-trying-to-be-useful-starting-with-medicine",
  );
  assert.equal(
    quantum.primary_topic,
    "health-longevity-medtech",
  );
  assert.deepEqual(quantum.secondary_topics, []);
});

test("reviewed semantic exception is explicit and narrow", () => {
  assert.deepEqual(
    decisions.reviewed_semantic_assignments.map((item) => item.slug),
    ["burnout-doesnt-look-like-burnout"],
  );
  assert.deepEqual(
    decisions.manual_review.map((item) => item.slug),
    ["33-qs-for-sergei-medvedev"],
  );
});

test("taxonomy audit has a stable package command", () => {
  assert.equal(
    packageJson.scripts["audit:taxonomy"],
    "python3 scripts/audit-editorial-taxonomy.py",
  );
});
