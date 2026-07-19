import assert from 'node:assert/strict';
import test from 'node:test';
import { buildYouTubeEmbedUrl, buildYouTubeThumbnailUrl, parseYouTubeVideoId } from '../lib/youtube.js';

const id = 'AbCdEf_123-';
test('parses supported YouTube URLs and strips query parameters', () => {
  for (const url of [
    `https://www.youtube.com/watch?v=${id}&t=3`,
    `https://youtube.com/watch?v=${id}`,
    `https://youtu.be/${id}?si=x`,
    `https://www.youtube.com/shorts/${id}`,
    `https://www.youtube.com/embed/${id}`,
  ]) assert.equal(parseYouTubeVideoId(url), id);
});
test('rejects malformed or arbitrary video sources', () => {
  for (const value of ['', null, 'https://evil.example/watch?v=' + id, 'https://youtube.com/watch?v=bad!']) assert.equal(parseYouTubeVideoId(value), '');
});
test('embed and thumbnail URLs are derived only from valid video IDs', () => {
  assert.equal(buildYouTubeEmbedUrl(id), `https://www.youtube-nocookie.com/embed/${id}?autoplay=1`);
  assert.equal(buildYouTubeThumbnailUrl(id), `https://i.ytimg.com/vi/${id}/hqdefault.jpg`);
  assert.equal(buildYouTubeEmbedUrl('https://evil.example'), '');
});
