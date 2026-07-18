import he from 'he';

const MAX_DECODE_PASSES = 3;

/**
 * Decode WordPress entities in plain-text API fields.
 *
 * WordPress data can contain both normal entities and legacy values that were
 * encoded more than once. A small fixed pass limit handles both without
 * turning this helper into an unbounded parser. React still escapes the
 * returned text when it is rendered.
 */
export function decodeWordPressText(value) {
  if (typeof value !== 'string' || value === '') {
    return value;
  }

  let decoded = value;

  for (let pass = 0; pass < MAX_DECODE_PASSES; pass += 1) {
    const next = he.decode(decoded);

    if (next === decoded) {
      break;
    }

    decoded = next;
  }

  return decoded;
}
