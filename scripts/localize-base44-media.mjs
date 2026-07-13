import fs from 'node:fs/promises';
import path from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);

const ROOT = process.cwd();
const ARTICLES_PATH = path.join(ROOT, 'data/articles.json');
const HEADER_PATH = path.join(ROOT, 'components/site-header.jsx');
const SITE_PATH = path.join(ROOT, 'lib/site.js');
const PUBLIC_PATH = path.join(ROOT, 'public');

const BRAND_SOURCE =
  'https://media.base44.com/images/public/69dbf76f393b4827a5591a8c/a3882d98d_Untitleddesign.png';

const BRAND_PUBLIC_PATH =
  '/media/brand/revelations-logo.png';

const ALLOWED_EXTENSIONS = new Set([
  '.jpg',
  '.jpeg',
  '.png',
  '.webp',
  '.gif',
  '.avif',
]);

function isBase44Url(value) {
  if (typeof value !== 'string' || !value.trim()) {
    return false;
  }

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

function prepareReferenceUpdate(
  content,
  fileName,
) {
  if (content.includes(BRAND_SOURCE)) {
    return content
      .split(BRAND_SOURCE)
      .join(BRAND_PUBLIC_PATH);
  }

  if (content.includes(BRAND_PUBLIC_PATH)) {
    return content;
  }

  throw new Error(
    `Neither old nor local logo reference found in ${fileName}`
  );
}

async function downloadImage(
  url,
  targetPath,
  label,
) {
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

    const fileStats = await fs.stat(temporaryPath);

    if (fileStats.size < 100) {
      throw new Error(
        `Downloaded file is unexpectedly small: ${fileStats.size} bytes`
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
      size: fileStats.size,
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

const headerSource = await fs.readFile(
  HEADER_PATH,
  'utf8',
);

const siteSource = await fs.readFile(
  SITE_PATH,
  'utf8',
);

const updatedHeader = prepareReferenceUpdate(
  headerSource,
  'components/site-header.jsx',
);

const updatedSite = prepareReferenceUpdate(
  siteSource,
  'lib/site.js',
);

const articlesToLocalize = articles.filter(
  (article) =>
    isBase44Url(article.cover_image)
);

console.log(
  `Base44 article covers found: ${articlesToLocalize.length}`
);

let totalBytes = 0;

for (
  let index = 0;
  index < articlesToLocalize.length;
  index += 1
) {
  const article = articlesToLocalize[index];

  if (
    !article.slug ||
    !/^[a-z0-9-]+$/.test(article.slug)
  ) {
    throw new Error(
      `Unsafe or missing slug: ${article.slug}`
    );
  }

  const originalUrl = article.cover_image;
  const extension = extensionFromUrl(originalUrl);

  const localPublicPath =
    `/media/articles/${article.slug}${extension}`;

  const localAbsolutePath =
    absolutePublicPath(localPublicPath);

  console.log(
    `[${index + 1}/${articlesToLocalize.length}] ${article.slug}`
  );

  const result = await downloadImage(
    originalUrl,
    localAbsolutePath,
    article.slug,
  );

  article.cover_image = localPublicPath;
  totalBytes += result.size;

  console.log(
    `    ${result.mimeType}, ${formatBytes(result.size)}`
  );
}

console.log('Downloading local brand logo');

const brandResult = await downloadImage(
  BRAND_SOURCE,
  absolutePublicPath(BRAND_PUBLIC_PATH),
  'REVELATIONS logo',
);

totalBytes += brandResult.size;

console.log(
  `    ${brandResult.mimeType}, ${formatBytes(brandResult.size)}`
);

for (const article of articles) {
  if (
    typeof article.cover_image === 'string' &&
    article.cover_image.startsWith('/')
  ) {
    const localPath =
      absolutePublicPath(article.cover_image);

    try {
      const fileStats = await fs.stat(localPath);

      if (!fileStats.isFile() || fileStats.size === 0) {
        throw new Error('Empty or invalid file');
      }
    } catch {
      throw new Error(
        `Local cover does not exist: ${article.cover_image}`
      );
    }
  }
}

const articlesTemporaryPath =
  `${ARTICLES_PATH}.tmp`;

await fs.writeFile(
  articlesTemporaryPath,
  `${JSON.stringify(articles, null, 2)}\n`,
  'utf8',
);

await fs.rename(
  articlesTemporaryPath,
  ARTICLES_PATH,
);

await fs.writeFile(
  HEADER_PATH,
  updatedHeader,
  'utf8',
);

await fs.writeFile(
  SITE_PATH,
  updatedSite,
  'utf8',
);

const remainingBase44Covers = articles.filter(
  (article) =>
    isBase44Url(article.cover_image)
);

console.log();
console.log('Media localization completed');
console.log('----------------------------');
console.log(
  `Localized article covers: ${articlesToLocalize.length}`
);
console.log('Localized brand images:  1');
console.log(
  `Downloaded total:        ${formatBytes(totalBytes)}`
);
console.log(
  `Base44 covers remaining: ${remainingBase44Covers.length}`
);
