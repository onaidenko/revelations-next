import { createHmac, timingSafeEqual } from 'node:crypto';

export const MAX_REVALIDATION_BODY_BYTES = 16 * 1024;
export const REVALIDATION_WINDOW_SECONDS = 300;
export const EDITORIAL_SECTIONS = new Set(['news', 'people', 'tech', 'places', 'unspoken', 'podcast']);
const ACTIONS = new Set(['publish', 'update', 'unpublish', 'delete']);
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export function validSignature(signature, timestamp, body, secret) {
  if (!/^sha256=[a-f0-9]{64}$/.test(signature || '') || !secret) return false;
  const expected = `sha256=${createHmac('sha256', secret).update(`${timestamp}.${body}`).digest('hex')}`;
  return timingSafeEqual(Buffer.from(signature), Buffer.from(expected));
}
export function validTimestamp(value, now = Date.now()) {
  if (!/^\d+$/.test(String(value || ''))) return false;
  return Math.abs(now / 1000 - Number(value)) <= REVALIDATION_WINDOW_SECONDS;
}
function nullableSlug(value) { return value === null || (typeof value === 'string' && SLUG.test(value)); }
function nullableSection(value) { return value === null || (typeof value === 'string' && EDITORIAL_SECTIONS.has(value)); }
export function parsePayload(body) {
  let payload; try { payload = JSON.parse(body); } catch { return null; }
  if (!payload || payload.version !== 1 || typeof payload.event_id !== 'string' || !payload.event_id || payload.event_id.length > 200 || !Number.isInteger(payload.post_id) || payload.post_id <= 0 || payload.post_type !== 'post' || !ACTIONS.has(payload.action) || typeof payload.old_status !== 'string' || typeof payload.new_status !== 'string' || payload.old_status.length > 80 || payload.new_status.length > 80 || !nullableSlug(payload.old_slug) || !nullableSlug(payload.new_slug) || !nullableSection(payload.old_section) || !nullableSection(payload.new_section) || typeof payload.occurred_at !== 'string' || Number.isNaN(Date.parse(payload.occurred_at))) return null;
  return payload;
}
export function affectedPaths(payload) {
  const paths = new Set(['/', '/archive', '/sitemap.xml', '/news-sitemap.xml']);
  for (const value of [payload.old_slug, payload.new_slug, payload.old_section, payload.new_section]) if (value) paths.add(`/${value}`);
  return [...paths];
}
