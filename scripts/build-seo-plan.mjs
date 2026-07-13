import fs from 'node:fs/promises';
import path from 'node:path';

const ROOT = process.cwd();

const ARTICLES_PATH = path.join(
  ROOT,
  'data/articles.json'
);

const JSON_PATH = path.join(
  ROOT,
  'data/seo-metadata-plan.json'
);

const MARKDOWN_PATH = path.join(
  ROOT,
  'SEO_METADATA_PLAN.md'
);

const TITLE_MAX = 65;
const TITLE_SUFFIX = ' — REVELATIONS';
const TITLE_CONTENT_MAX =
  TITLE_MAX - [...TITLE_SUFFIX].length;

const DESCRIPTION_MIN = 50;
const DESCRIPTION_MAX = 170;

const articles = JSON.parse(
  await fs.readFile(ARTICLES_PATH, 'utf8')
);

const plan = articles
  .filter(
    (article) =>
      article.status === 'published' &&
      article.slug
  )
  .map((article) => {
    const effectiveTitle =
      article.seo_title || article.title || '';

    const effectiveDescription =
      article.seo_description ||
      article.excerpt ||
      '';

    const titleLength =
      [...effectiveTitle].length;

    const renderedTitleLength =
      [...`${effectiveTitle}${TITLE_SUFFIX}`].length;

    const descriptionLength =
      [...effectiveDescription].length;

    const needsTitle =
      renderedTitleLength > TITLE_MAX;

    const needsDescription =
      descriptionLength < DESCRIPTION_MIN ||
      descriptionLength > DESCRIPTION_MAX;

    return {
      slug: article.slug,
      section: article.section,
      visible_title: article.title,
      current_seo_title:
        article.seo_title,
      effective_title:
        effectiveTitle,
      title_length:
        titleLength,
      rendered_title_length:
        renderedTitleLength,
      needs_seo_title:
        needsTitle,
      visible_excerpt:
        article.excerpt,
      current_seo_description:
        article.seo_description,
      effective_description:
        effectiveDescription,
      description_length:
        descriptionLength,
      needs_seo_description:
        needsDescription,
      proposed_seo_title:
        article.seo_title || '',
      proposed_seo_description:
        article.seo_description || '',
    };
  })
  .filter(
    (article) =>
      article.needs_seo_title ||
      article.needs_seo_description
  );

const titleIssues = plan.filter(
  (article) => article.needs_seo_title
).length;

const descriptionIssues = plan.filter(
  (article) =>
    article.needs_seo_description
).length;

await fs.writeFile(
  JSON_PATH,
  `${JSON.stringify(plan, null, 2)}\n`,
  'utf8'
);

const lines = [
  '# REVELATIONS SEO Metadata Plan',
  '',
  'This file covers article metadata only.',
  'The static `/people` page is handled separately.',
  '',
  '## Summary',
  '',
  `- Articles requiring SEO work: ${plan.length}`,
  `- Articles requiring SEO title: ${titleIssues}`,
  `- Articles requiring SEO description: ${descriptionIssues}`,
  `- Target SEO title before suffix: up to ${TITLE_CONTENT_MAX} characters`,
  `- Final rendered title limit: up to ${TITLE_MAX} characters`,
  `- Automatic title suffix: ${TITLE_SUFFIX}`,
  `- Target description length: ${DESCRIPTION_MIN}–${DESCRIPTION_MAX} characters`,
  '',
];

for (const [index, article] of plan.entries()) {
  lines.push(
    `## ${index + 1}. ${article.slug}`,
    '',
    `- Section: ${article.section}`,
    `- Needs SEO title: ${article.needs_seo_title ? 'yes' : 'no'}`,
    `- Current title length before suffix: ${article.title_length}`,
    `- Final title length with suffix: ${article.rendered_title_length}`,
    `- Needs SEO description: ${article.needs_seo_description ? 'yes' : 'no'}`,
    `- Current description length: ${article.description_length}`,
    '',
    '**Visible title**',
    '',
    article.visible_title || '—',
    '',
    '**Current description**',
    '',
    article.visible_excerpt || '—',
    '',
    '**Proposed SEO title**',
    '',
    article.proposed_seo_title || '[TO BE WRITTEN]',
    '',
    '**Proposed SEO description**',
    '',
    article.proposed_seo_description || '[TO BE WRITTEN]',
    ''
  );
}

await fs.writeFile(
  MARKDOWN_PATH,
  `${lines.join('\n')}\n`,
  'utf8'
);

console.log('SEO metadata plan created');
console.log('-------------------------');
console.log(
  `Articles requiring work:       ${plan.length}`
);
console.log(
  `SEO title issues:              ${titleIssues}`
);
console.log(
  `SEO description issues:        ${descriptionIssues}`
);
console.log('');
console.log('Created:');
console.log('  SEO_METADATA_PLAN.md');
console.log('  data/seo-metadata-plan.json');
