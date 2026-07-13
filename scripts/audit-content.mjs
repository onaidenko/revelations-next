import fs from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();

function readJson(relativePath, fallback) {
  const fullPath = path.join(ROOT, relativePath);

  if (!fs.existsSync(fullPath)) {
    return fallback;
  }

  try {
    return JSON.parse(fs.readFileSync(fullPath, 'utf8'));
  } catch (error) {
    console.error(`Cannot read ${relativePath}:`, error.message);
    process.exitCode = 1;
    return fallback;
  }
}

function hasValue(value) {
  if (Array.isArray(value)) {
    return value.length > 0;
  }

  return value !== undefined &&
    value !== null &&
    String(value).trim() !== '';
}

function displayArticle(article) {
  return article.slug || article.title || article.id || 'unknown article';
}

const articles = readJson('data/articles.json', []);
const missingArticles = readJson('data/missing-articles.json', []);

if (!Array.isArray(articles)) {
  console.error('data/articles.json must contain an array.');
  process.exit(1);
}

const knownSections = [
  'news',
  'people',
  'tech',
  'places',
  'unspoken',
  'podcast',
];

const published = articles.filter(
  (article) =>
    article.status === 'published' &&
    hasValue(article.slug)
);

const sectionCounts = Object.fromEntries(
  knownSections.map((section) => [
    section,
    published.filter((article) => article.section === section).length,
  ])
);

const slugGroups = new Map();

for (const article of articles) {
  if (!hasValue(article.slug)) continue;

  const slug = String(article.slug).trim();

  if (!slugGroups.has(slug)) {
    slugGroups.set(slug, []);
  }

  slugGroups.get(slug).push(article);
}

const duplicateSlugs = [...slugGroups.entries()]
  .filter(([, group]) => group.length > 1)
  .map(([slug, group]) => ({
    slug,
    count: group.length,
    titles: group.map((article) => article.title || 'Untitled'),
  }));

const invalidSections = articles
  .filter(
    (article) =>
      hasValue(article.section) &&
      !knownSections.includes(article.section)
  )
  .map((article) => ({
    article: displayArticle(article),
    section: article.section,
  }));

const checks = {
  noTitle: [],
  noSlug: [],
  noSection: [],
  noStatus: [],
  noDate: [],
  noExcerpt: [],
  noAuthor: [],
  noCoverImage: [],
  noContent: [],
};

for (const article of articles) {
  const name = displayArticle(article);

  if (!hasValue(article.title)) checks.noTitle.push(name);
  if (!hasValue(article.slug)) checks.noSlug.push(name);
  if (!hasValue(article.section)) checks.noSection.push(name);
  if (!hasValue(article.status)) checks.noStatus.push(name);

  if (
    !hasValue(article.publication_date) &&
    !hasValue(article.created_date)
  ) {
    checks.noDate.push(name);
  }

  if (!hasValue(article.excerpt)) checks.noExcerpt.push(name);
  if (!hasValue(article.author)) checks.noAuthor.push(name);
  if (!hasValue(article.cover_image)) checks.noCoverImage.push(name);
  if (!hasValue(article.content)) checks.noContent.push(name);
}

const imageHostCounts = {};
const localImages = [];
const base44Images = [];
const invalidImageUrls = [];

for (const article of articles) {
  if (!hasValue(article.cover_image)) continue;

  const image = String(article.cover_image).trim();

  if (image.startsWith('/')) {
    const localFilePath = path.join(
      ROOT,
      'public',
      image.replace(/^\/+/, '')
    );

    if (
      fs.existsSync(localFilePath) &&
      fs.statSync(localFilePath).isFile()
    ) {
      localImages.push({
        article: displayArticle(article),
        image,
      });
    } else {
      invalidImageUrls.push({
        article: displayArticle(article),
        image,
      });
    }

    continue;
  }

  try {
    const url = new URL(image);

    imageHostCounts[url.hostname] =
      (imageHostCounts[url.hostname] || 0) + 1;

    if (url.hostname.includes('base44')) {
      base44Images.push({
        article: displayArticle(article),
        image,
      });
    }
  } catch {
    invalidImageUrls.push({
      article: displayArticle(article),
      image,
    });
  }
}

const audit = {
  generatedAt: new Date().toISOString(),
  totals: {
    allRecords: articles.length,
    publishedWithSlug: published.length,
    listedAsMissing: Array.isArray(missingArticles)
      ? missingArticles.length
      : 0,
  },
  sectionCounts,
  duplicateSlugs,
  invalidSections,
  missingFields: checks,
  imageHostCounts,
  localImages,
  base44Images,
  invalidImageUrls,
};

fs.writeFileSync(
  path.join(ROOT, 'data/content-audit.json'),
  `${JSON.stringify(audit, null, 2)}\n`
);

const lines = [
  '# REVELATIONS Content Audit',
  '',
  `Generated: ${audit.generatedAt}`,
  '',
  '## Totals',
  '',
  `- All records: ${audit.totals.allRecords}`,
  `- Published records with slug: ${audit.totals.publishedWithSlug}`,
  `- Records listed as missing: ${audit.totals.listedAsMissing}`,
  '',
  '## Published articles by section',
  '',
  ...knownSections.map(
    (section) => `- ${section}: ${sectionCounts[section]}`
  ),
  '',
  '## Data problems',
  '',
  `- Duplicate slugs: ${duplicateSlugs.length}`,
  `- Invalid sections: ${invalidSections.length}`,
  `- Missing title: ${checks.noTitle.length}`,
  `- Missing slug: ${checks.noSlug.length}`,
  `- Missing section: ${checks.noSection.length}`,
  `- Missing status: ${checks.noStatus.length}`,
  `- Missing date: ${checks.noDate.length}`,
  `- Missing excerpt: ${checks.noExcerpt.length}`,
  `- Missing author: ${checks.noAuthor.length}`,
  `- Missing cover image: ${checks.noCoverImage.length}`,
  `- Missing content: ${checks.noContent.length}`,
  `- Invalid cover-image URLs: ${invalidImageUrls.length}`,
  '',
  '## Image locations',
  '',
  `- Local project files: ${localImages.length}`,
  ...Object.entries(imageHostCounts)
    .sort((a, b) => b[1] - a[1])
    .map(([host, count]) => `- ${host}: ${count}`),
  '',
  `## Base44-hosted cover images: ${base44Images.length}`,
  '',
  ...base44Images.map(
    (item) => `- ${item.article}\n  - ${item.image}`
  ),
];

fs.writeFileSync(
  path.join(ROOT, 'CONTENT_AUDIT.md'),
  `${lines.join('\n')}\n`
);

console.log('REVELATIONS content inventory');
console.log('-----------------------------');
console.log(`All records:              ${articles.length}`);
console.log(`Published with slug:      ${published.length}`);
console.log(
  `Listed as missing:        ${
    Array.isArray(missingArticles) ? missingArticles.length : 0
  }`
);
console.log(`Duplicate slugs:          ${duplicateSlugs.length}`);
console.log(`Invalid sections:         ${invalidSections.length}`);
console.log(`Missing content:          ${checks.noContent.length}`);
console.log(`Missing cover image:      ${checks.noCoverImage.length}`);
console.log(`Local cover images:       ${localImages.length}`);
console.log(`Base44 cover images:      ${base44Images.length}`);
console.log('');
console.log('By section:');

for (const section of knownSections) {
  console.log(
    `  ${section.padEnd(10)} ${String(sectionCounts[section]).padStart(3)}`
  );
}

console.log('');
console.log('Created:');
console.log('  CONTENT_AUDIT.md');
console.log('  data/content-audit.json');
