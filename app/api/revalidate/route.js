import { revalidatePath, revalidateTag } from 'next/cache';
import { CMS_ARTICLES_CACHE_TAG } from '@/lib/cache-tags';
import { MAX_REVALIDATION_BODY_BYTES, affectedPaths, parsePayload, validSignature, validTimestamp } from '@/lib/revalidation-contract';

const response = (body, status) => Response.json(body, { status, headers: { 'Cache-Control': 'no-store' } });

export async function POST(request) {
  const secret = process.env.REVELATIONS_REVALIDATION_SECRET;
  if (!secret) return response({ ok: false, error: 'revalidation_unavailable' }, 503);
  const body = await request.text();
  if (Buffer.byteLength(body) > MAX_REVALIDATION_BODY_BYTES) return response({ ok: false, error: 'payload_too_large' }, 413);
  const timestamp = request.headers.get('x-revelations-timestamp') || '';
  const signature = request.headers.get('x-revelations-signature') || '';
  if (!validTimestamp(timestamp) || !validSignature(signature, timestamp, body, secret)) return response({ ok: false, error: 'unauthorized' }, 401);
  const payload = parsePayload(body);
  if (!payload) return response({ ok: false, error: 'invalid_request' }, 400);
  try {
    revalidateTag(CMS_ARTICLES_CACHE_TAG, { expire: 0 });
    const paths = affectedPaths(payload); paths.forEach(revalidatePath);
    return response({ ok: true, event_id: payload.event_id, revalidated: true, path_count: paths.length }, 200);
  } catch { return response({ ok: false, error: 'revalidation_failed' }, 500); }
}
