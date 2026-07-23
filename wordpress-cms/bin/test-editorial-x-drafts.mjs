import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

function read(relativePath) {
  return fs.readFileSync(
    new URL(`../${relativePath}`, import.meta.url),
    'utf8'
  );
}

test(
  'Editorial Desk exposes an isolated manual X draft workflow',
  () => {
    const desk = read(
      'mu-plugins/revelations-editorial-desk.php'
    );

    const xDrafts = read(
      'mu-plugins/revelations-editorial-x-drafts.php'
    );

    assert.match(
      desk,
      /'social'\s*=>\s*'Social Drafts'/
    );

    assert.match(
      desk,
      /'social' === \$active_view/
    );

    assert.match(
      desk,
      /revelations_editorial_render_social_drafts/
    );

    assert.match(
      xDrafts,
      /_revelations_x_draft_text/
    );

    assert.match(
      xDrafts,
      /_revelations_x_draft_source_hash/
    );

    assert.match(
      xDrafts,
      /_revelations_x_draft_previous/
    );

    assert.match(
      xDrafts,
      /Generate with AI/
    );

    assert.match(
      xDrafts,
      /Mark posted manually/
    );

    assert.match(
      xDrafts,
      /Nothing is published to X automatically\./
    );

    assert.doesNotMatch(
      xDrafts,
      /api\.x\.com|api\.twitter\.com/
    );
  }
);
