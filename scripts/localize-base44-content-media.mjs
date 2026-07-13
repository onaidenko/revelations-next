import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);

const ROOT = process.cwd();
const ARTICLES_PATH = path.join(ROOT, 'data/articles.json');
const PUBLIC_PATH = path.join(ROOT, 'public');

const URL_PATTERN = /https:\/\/[^\s)"'<>]+/g;

const ALLOWED_EXTENSIONS = new Set([
  '.jpg',
  '.jpeg',
  '.png',
  '.webp',
  '.gif',
  '.avif',
]);

function isBase44Url(value) {
  try {
    const hostname = new URL(value).hostname.toLowerCase();

    return (
      hostname === 'base44.app' ||
      hostname.endsWith('.base44.app') ||
      hostname === 'base44.com' ||
      hostname.endsWith('.base44.com')
    );
  } catch {
    return false;
  }
}

function extensionFromUrl(url) {
  let extension = path
    .extname(new URL(url).pathname)
    .toLowerCase();

  if (extension === '.jpeg') {
    extension = '.jpg';
  }

  if (!ALLOWED_EXTENSIONS.has(extension)) {
    throw new Error(
      `Unsupported image extension "${extension}" for ${url}`
    );
  }

  return extension;
}

function absolutePublicPath(publicPath) {
  return path.join(
    PUBLIC_PATH,
    publicPath.replace(/^\/+/, '')
  );
}

function formatBytes(bytes) {
  return `${(bytes / 1024 / 1024).toFixed(2)} MB`;
}

async function downloadImage(url, targetPath, label) {
  await fs.mkdir(path.dirname(targetPath), {
    recursive: true,
  });

  const temporaryPath = `${targetPath}.download`;

  await fs.rm(temporaryPath, {
    force: true,
  });

  try {
    await execFileAsync(
      'curl',
      [
        '--fail',
        '--location',
        '--silent',
        '--show-error',
        '--retry',
        '3',
        '--retry-delay',
        '2',
        '--connect-timeout',
        '30',
        '--max-time',
        '180',
        '--user-agent',
        'Mozilla/5.0 REVELATIONS migration',
        '--output',
        temporaryPath,
        url,
      ],
      {
        maxBuffer: 1024 * 1024,
      }
    );

    const { stdout } = await execFileAsync(
      'file',
      [
        '--brief',
        '--mime-type',
        temporaryPath,
      ]
    );

    const mimeType = stdout.trim();

    if (!mimeType.startsWith('image/')) {
      throw new Error(
        `Downloaded file is not an image: ${mimeType}`
      );
    }

    const stats = await fs.stat(temporaryPath);

    if (stats.size < 100) {
      throw new Error(
        `Downloaded file is too small: ${stats.size} bytes`
      );
    }

    await fs.rm(targetPath, {
      force: true,
    });

    await fs.rename(
      temporaryPath,
      targetPath,
    );

    return {
      size: stats.size,
      mimeType,
    };
  } catch (error) {
    await fs.rm(temporaryPath, {
      force: true,
    });

    throw new Error(
      `Failed to download ${label}: ${error.message}`
    );
  }
}

const articles = JSON.parse(
  await fs.readFile(
    ARTICLES_PATH,
    'utf8',
  )
);

let affectedArticles = 0;
let localizedImages = 0;
let totalBytes = 0;

for (const article of articles) {
  const content =
    typeof article.content === 'string'
      ? article.content
      : '';

  const allUrls = content.match(URL_PATTERN) || [];

  const base44Urls = [
    ...new Set(
      allUrls.filter(isBase44Url)
    ),
  ];

  if (base44Urls.length === 0) {
    continue;
  }

  if (
    !article.slug ||
    !/^[a-z0-9-]+$/.test(article.slug)
  ) {
    throw new Error(
      `Unsafe or missing slug: ${article.slug}`
    );
  }

  affectedArticles += 1;

  console.log();
  console.log(article.slug);
  console.log(
    `Inline Base44 images: ${base44Urls.length}`
  );

  let updatedContent = content;

  for (
    let index = 0;
    index < base44Urls.length;
    index += 1
  ) {
    const sourceUrl = base44Urls[index];
    const extension = extensionFromUrl(sourceUrl);

    const imageNumber = String(index + 1)
      .padStart(2, '0');

    const localPublicPath =
      `/media/content/${article.slug}/image-${imageNumber}${extension}`;

    const targetPath =
      absolutePublicPath(localPublicPath);

    console.log(
      `  [${index + 1}/${base44Urls.length}] ${localPublicPath}`
    );

    const result = await downloadImage(
      sourceUrl,
      targetPath,
      `${article.slug} image ${imageNumber}`,
    );

    updatedContent = updatedContent
      .split(sourceUrl)
      .join(localPublicPath);

    localizedImages += 1;
    totalBytes += result.size;

    console.log(
      `      ${result.mimeType}, ${formatBytes(result.size)}`
    );
  }

  article.content = updatedContent;
}

const serializedArticles =
  `${JSON.stringify(articles, null, 2)}\n`;

const remainingUrls = (
  serializedArticles.match(URL_PATTERN) || []
).filter(isBase44Url);

if (remainingUrls.length > 0) {
  throw new Error(
    `Base44 URLs remain in articles.json: ${remainingUrls.length}`
  );
}

const temporaryArticlesPath =
  `${ARTICLES_PATH}.tmp`;

await fs.writeFile(
  temporaryArticlesPath,
  serializedArticles,
  'utf8',
);

await fs.rename(
  temporaryArticlesPath,
  ARTICLES_PATH,
);

console.log();
console.log('Inline media localization completed');
console.log('-----------------------------------');
console.log(
  `Affected articles:       ${affectedArticles}`
);
console.log(
  `Localized images:        ${localizedImages}`
);
console.log(
  `Downloaded total:        ${formatBytes(totalBytes)}`
);
console.log(
  `Base44 URLs remaining:   ${remainingUrls.length}`
);
