import { promises as fs } from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';

import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';

const projectRoot = process.cwd();

const outputDir =
  process.argv[2] ||
  '/tmp/revelations-wordpress-import';

const mediaDir = path.join(outputDir, 'media');

const articlesPath = path.join(
  projectRoot,
  'data',
  'articles.json'
);

const MIME_EXTENSIONS = {
  'image/jpeg': '.jpg',
  'image/png': '.png',
  'image/webp': '.webp',
  'image/avif': '.avif',
  'image/gif': '.gif',
};

function cleanSlug(value) {
  return String(value || 'media')
    .toLowerCase()
    .replace(/[^a-z0-9-]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 150);
}

function sha256(buffer) {
  return crypto
    .createHash('sha256')
    .update(buffer)
    .digest('hex');
}

function renderMarkdown(markdown) {
  if (!markdown) {
    return '';
  }

  return renderToStaticMarkup(
    React.createElement(
      ReactMarkdown,
      {
        remarkPlugins: [remarkGfm],
      },
      markdown
    )
  );
}

function extractInlineImages(markdown) {
  const results = [];

  if (!markdown) {
    return results;
  }

  const imagePattern =
    /!\[([^\]]*)\]\(([^)\s]+)(?:\s+["'][^"']*["'])?\)/g;

  for (const match of markdown.matchAll(imagePattern)) {
    results.push({
      alt: match[1] || '',
      sourceKey: match[2],
    });
  }

  return results;
}

async function readLocalMedia(sourceKey) {
  const relativePath = sourceKey.replace(/^\/+/, '');

  const absolutePath = path.join(
    projectRoot,
    'public',
    relativePath
  );

  const buffer = await fs.readFile(absolutePath);

  return {
    buffer,
    extension:
      path.extname(absolutePath).toLowerCase() ||
      '.bin',
  };
}

async function downloadRemoteMedia(sourceKey) {
  const response = await fetch(sourceKey, {
    redirect: 'follow',

    headers: {
      Accept:
        'image/avif,image/webp,image/png,image/jpeg,image/gif,*/*',

      'User-Agent':
        'REVELATIONS CMS migration/1.0',
    },
  });

  if (!response.ok) {
    throw new Error(
      `Unable to download ${sourceKey}: HTTP ${response.status}`
    );
  }

  const contentType = (
    response.headers.get('content-type') || ''
  )
    .split(';')[0]
    .trim()
    .toLowerCase();

  const buffer = Buffer.from(
    await response.arrayBuffer()
  );

  if (buffer.length < 100) {
    throw new Error(
      `Downloaded file is unexpectedly small: ${sourceKey}`
    );
  }

  const urlExtension = path
    .extname(new URL(sourceKey).pathname)
    .toLowerCase();

  const extension =
    MIME_EXTENSIONS[contentType] ||
    urlExtension ||
    '.jpg';

  return {
    buffer,
    extension,
    contentType,
  };
}

const sourceArticles = JSON.parse(
  await fs.readFile(articlesPath, 'utf8')
);

await fs.rm(outputDir, {
  recursive: true,
  force: true,
});

await fs.mkdir(mediaDir, {
  recursive: true,
});

const mediaBySourceKey = new Map();
const mediaItems = [];

async function addMedia({
  sourceKey,
  articleSlug,
  role,
  position = 0,
  alt = '',
}) {
  if (!sourceKey) {
    return null;
  }

  if (mediaBySourceKey.has(sourceKey)) {
    return mediaBySourceKey.get(sourceKey);
  }

  const isRemote = /^https?:\/\//i.test(
    sourceKey
  );

  const media = isRemote
    ? await downloadRemoteMedia(sourceKey)
    : await readLocalMedia(sourceKey);

  const suffix =
    position > 0
      ? `-${String(position).padStart(2, '0')}`
      : '';

  const filename =
    `${cleanSlug(articleSlug)}--${role}${suffix}` +
    media.extension;

  const destinationPath = path.join(
    mediaDir,
    filename
  );

  await fs.writeFile(
    destinationPath,
    media.buffer
  );

  const item = {
    source_key: sourceKey,
    file: `media/${filename}`,
    role,
    article_slug: articleSlug,
    alt,
    remote_source: isRemote,
    bytes: media.buffer.length,
    sha256: sha256(media.buffer),
  };

  mediaItems.push(item);
  mediaBySourceKey.set(sourceKey, item);

  return item;
}

const manifestArticles = [];

for (const article of sourceArticles) {
  const inlineImages = extractInlineImages(
    article.content || ''
  );

  const coverMedia = await addMedia({
    sourceKey: article.cover_image,
    articleSlug: article.slug,
    role: 'cover',
    alt: article.title || '',
  });

  const inlineSourceKeys = [];

  for (
    let index = 0;
    index < inlineImages.length;
    index += 1
  ) {
    const inlineImage = inlineImages[index];

    const mediaItem = await addMedia({
      sourceKey: inlineImage.sourceKey,
      articleSlug: article.slug,
      role: 'inline',
      position: index + 1,
      alt: inlineImage.alt,
    });

    if (mediaItem) {
      inlineSourceKeys.push(
        mediaItem.source_key
      );
    }
  }

  const tags = Array.isArray(article.tags)
    ? article.tags
        .map((tag) =>
          typeof tag === 'string'
            ? tag
            : tag?.name
        )
        .filter(Boolean)
    : [];

  const publicationDate =
    article.publication_date ||
    article.created_date ||
    '';

  manifestArticles.push({
    legacy_id: String(article.id || ''),
    source_status: article.status || '',

    title: article.title || '',
    slug: article.slug || '',

    content_markdown: article.content || '',
    content_html: renderMarkdown(
      article.content || ''
    ),

    excerpt: article.excerpt || '',
    displayed_author: article.author || '',

    section: article.section || 'news',
    tags,

    cover_source_key:
      coverMedia?.source_key || '',

    inline_source_keys: inlineSourceKeys,

    featured: Boolean(article.featured),
    is_gated: Boolean(article.is_gated),

    youtube_url: article.youtube_url || '',

    seo_title: article.seo_title || '',
    seo_description:
      article.seo_description || '',

    publication_date: publicationDate,

    modified_date:
      article.updated_date ||
      publicationDate,

    import_status: 'draft',
  });
}

const manifest = {
  version: 1,

  generated_at:
    new Date().toISOString(),

  source: {
    project: 'revelations-next',
    branch: 'admin-editorial',
  },

  counts: {
    articles: manifestArticles.length,

    articles_with_content:
      manifestArticles.filter(
        (article) =>
          article.content_html.length > 0
      ).length,

    articles_without_content:
      manifestArticles.filter(
        (article) =>
          article.content_html.length === 0
      ).length,

    media: mediaItems.length,

    covers:
      mediaItems.filter(
        (item) => item.role === 'cover'
      ).length,

    inline_images:
      mediaItems.filter(
        (item) => item.role === 'inline'
      ).length,

    remote_media:
      mediaItems.filter(
        (item) => item.remote_source
      ).length,

    local_media:
      mediaItems.filter(
        (item) => !item.remote_source
      ).length,
  },

  media: mediaItems,
  articles: manifestArticles,
};

await fs.writeFile(
  path.join(outputDir, 'manifest.json'),
  JSON.stringify(manifest, null, 2)
);

await fs.writeFile(
  path.join(outputDir, 'README.txt'),
  [
    'REVELATIONS WordPress import package',
    '',
    `Generated: ${manifest.generated_at}`,
    `Articles: ${manifest.counts.articles}`,
    `Media: ${manifest.counts.media}`,
    '',
    'All imported articles must initially remain drafts.',
    '',
  ].join('\n')
);

console.log(
  JSON.stringify(
    {
      output_directory: outputDir,
      ...manifest.counts,
    },
    null,
    2
  )
);
