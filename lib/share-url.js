export function resolveShareUrl(location, fallbackUrl = '') {
  if (
    location &&
    typeof location.origin === 'string' &&
    typeof location.pathname === 'string' &&
    /^https?:\/\//i.test(location.origin)
  ) {
    return `${location.origin}${location.pathname}`;
  }

  try {
    const fallback = new URL(fallbackUrl);

    if (fallback.protocol === 'http:' || fallback.protocol === 'https:') {
      return `${fallback.origin}${fallback.pathname}`;
    }
  } catch {
    // An unavailable browser location and an invalid fallback leave no safe URL.
  }

  return '';
}
