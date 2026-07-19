const VIDEO_ID = /^[A-Za-z0-9_-]{6,}$/;
const YOUTUBE_HOSTS = new Set([
  'www.youtube.com',
  'youtube.com',
  'youtu.be',
]);

export function parseYouTubeVideoId(value) {
  if (typeof value !== 'string' || !value.trim()) return '';

  try {
    const url = new URL(value.trim());
    const host = url.hostname.toLowerCase();
    if (!YOUTUBE_HOSTS.has(host)) return '';
    const id = host === 'youtu.be'
      ? url.pathname.split('/').filter(Boolean)[0]
      : url.pathname.startsWith('/watch')
        ? url.searchParams.get('v')
        : url.pathname.match(/^\/(?:shorts|embed)\/([^/]+)/)?.[1];
    return id && VIDEO_ID.test(id) ? id : '';
  } catch {
    return '';
  }
}

export function buildYouTubeEmbedUrl(videoId) {
  return VIDEO_ID.test(videoId || '')
    ? `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1`
    : '';
}

export function buildYouTubeThumbnailUrl(videoId) {
  return VIDEO_ID.test(videoId || '')
    ? `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`
    : '';
}
