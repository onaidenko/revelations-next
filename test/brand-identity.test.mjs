import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

function read(pathname) {
  return fs.readFileSync(
    new URL(`../${pathname}`, import.meta.url),
    'utf8'
  );
}

test(
  'public identity consistently connects REVELATIONS, Dubai, JULS and official profiles',
  () => {
    const site = read('lib/site.js');
    const seo = read('lib/seo.js');
    const about = read('app/about/page.jsx');
    const contact = read('app/contact/page.jsx');
    const footer = read(
      'components/site-footer.jsx'
    );

    assert.match(
      site,
      /Future-Facing Media from Dubai/
    );

    assert.match(
      site,
      /PUBLISHER_BRAND_NAME = 'JULS'/
    );

    for (
      const url of [
        'https://www.instagram.com/revelations_me/',
        'https://x.com/revelations_new',
        'https://www.youtube.com/@revelations_podcast',
      ]
    ) {
      assert.match(site, new RegExp(url));
    }

    assert.match(
      seo,
      /'@type': 'NewsMediaOrganization'/
    );

    assert.match(
      seo,
      /alternateName/
    );

    assert.match(
      seo,
      /slogan/
    );

    assert.match(
      seo,
      /sameAs/
    );

    assert.doesNotMatch(
      seo,
      /legalName/
    );

    assert.match(
      about,
      /Published by/
    );

    assert.match(
      about,
      /What we cover/
    );

    assert.match(
      contact,
      /Published by/
    );

    assert.match(footer, /BRAND_NAME/);
    assert.match(footer, /BRAND_TAGLINE/);
    assert.match(footer, /SOCIAL_PROFILES\.map/);
    assert.doesNotMatch(footer, /DEFAULT_DESCRIPTION/);
  }
);
