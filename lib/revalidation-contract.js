import {
  createHmac,
  timingSafeEqual,
} from 'node:crypto';

export const MAX_REVALIDATION_BODY_BYTES =
  16 * 1024;
export const REVALIDATION_WINDOW_SECONDS = 300;
export const EDITORIAL_SECTIONS = new Set([
  'news',
  'people',
  'tech',
  'places',
  'unspoken',
  'podcast',
]);

const ACTIONS = new Set([
  'publish',
  'update',
  'unpublish',
  'delete',
]);
const SLUG =
  /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
const TAXONOMY_PATH =
  /^\/(?:topics|series|locations|tags)\/[a-z0-9]+(?:-[a-z0-9]+)*$/;
const MAX_TAXONOMY_PATHS = 128;

export function validSignature(
  signature,
  timestamp,
  body,
  secret
) {
  if (
    !/^sha256=[a-f0-9]{64}$/.test(
      signature || ''
    ) ||
    !secret
  ) {
    return false;
  }

  const expected = `sha256=${createHmac(
    'sha256',
    secret
  )
    .update(`${timestamp}.${body}`)
    .digest('hex')}`;

  return timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expected)
  );
}

export function validTimestamp(
  value,
  now = Date.now()
) {
  if (!/^\d+$/.test(String(value || ''))) {
    return false;
  }

  return (
    Math.abs(
      now / 1000 - Number(value)
    ) <= REVALIDATION_WINDOW_SECONDS
  );
}

function nullableSlug(value) {
  return (
    value === null ||
    (typeof value === 'string' &&
      SLUG.test(value))
  );
}

function nullableSection(value) {
  return (
    value === null ||
    (typeof value === 'string' &&
      EDITORIAL_SECTIONS.has(value))
  );
}

function taxonomyPaths(value) {
  if (value === undefined) {
    return [];
  }

  if (
    !Array.isArray(value) ||
    value.length > MAX_TAXONOMY_PATHS
  ) {
    return null;
  }

  const paths = [];

  for (const path of value) {
    if (
      typeof path !== 'string' ||
      !TAXONOMY_PATH.test(path)
    ) {
      return null;
    }

    if (!paths.includes(path)) {
      paths.push(path);
    }
  }

  return paths;
}

export function parsePayload(body) {
  let payload;

  try {
    payload = JSON.parse(body);
  } catch {
    return null;
  }

  const oldTaxonomyPaths = taxonomyPaths(
    payload?.old_taxonomy_paths
  );
  const newTaxonomyPaths = taxonomyPaths(
    payload?.new_taxonomy_paths
  );

  if (
    !payload ||
    payload.version !== 1 ||
    typeof payload.event_id !== 'string' ||
    !payload.event_id ||
    payload.event_id.length > 200 ||
    !Number.isInteger(payload.post_id) ||
    payload.post_id <= 0 ||
    payload.post_type !== 'post' ||
    !ACTIONS.has(payload.action) ||
    typeof payload.old_status !== 'string' ||
    typeof payload.new_status !== 'string' ||
    payload.old_status.length > 80 ||
    payload.new_status.length > 80 ||
    !nullableSlug(payload.old_slug) ||
    !nullableSlug(payload.new_slug) ||
    !nullableSection(payload.old_section) ||
    !nullableSection(payload.new_section) ||
    typeof payload.occurred_at !== 'string' ||
    Number.isNaN(
      Date.parse(payload.occurred_at)
    ) ||
    oldTaxonomyPaths === null ||
    newTaxonomyPaths === null
  ) {
    return null;
  }

  return {
    ...payload,
    old_taxonomy_paths: oldTaxonomyPaths,
    new_taxonomy_paths: newTaxonomyPaths,
  };
}

export function affectedPaths(payload) {
  const paths = new Set([
    '/',
    '/archive',
    '/topics',
    '/sitemap.xml',
    '/news-sitemap.xml',
  ]);

  for (const value of [
    payload.old_slug,
    payload.new_slug,
    payload.old_section,
    payload.new_section,
  ]) {
    if (value) {
      paths.add(`/${value}`);
    }
  }

  for (const path of [
    ...(payload.old_taxonomy_paths || []),
    ...(payload.new_taxonomy_paths || []),
  ]) {
    paths.add(path);
  }

  return [...paths];
}
