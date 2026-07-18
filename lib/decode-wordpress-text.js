import he from 'he';

/**
 * Decode WordPress entities in plain-text API fields.
 *
 * One decode pass is intentional: it fixes the WordPress transport encoding
 * without recursively interpreting text that was meant to contain an entity.
 * React still escapes the returned text when it is rendered.
 */
export function decodeWordPressText(value) {
  if (typeof value !== 'string' || value === '') {
    return value;
  }

  return he.decode(value);
}
