import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const source = fs.readFileSync(
  new URL('../app/page.jsx', import.meta.url),
  'utf8'
);

test(
  'homepage displays the latest published articles from every section',
  () => {
    assert.match(
      source,
      /import \{ getPublishedArticles \} from '@\/lib\/cms-articles'/
    );

    assert.match(
      source,
      /await getPublishedArticles\(\)/
    );

    assert.match(
      source,
      /import \{ isEditorialSection \} from '@\/lib\/sections'/
    );

    assert.match(
      source,
      /isEditorialSection\(article\.section\)/
    );

    assert.doesNotMatch(
      source,
      /getArticlesBySection\(['"]news['"]\)/
    );

    assert.match(
      source,
      /formatSectionLabel\(hero\)/
    );

    assert.match(
      source,
      /formatSectionLabel\(article\)/
    );

    assert.match(
      source,
      /Latest Stories/
    );

    assert.match(
      source,
      /href="\/archive"/
    );
  }
);
