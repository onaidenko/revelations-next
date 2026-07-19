import { decodeWordPressText } from './decode-wordpress-text.js';

export function normalizeCmsTerm(term) {
  if (!term || typeof term !== 'object') {
    return null;
  }

  const slug = String(term.slug || '').trim();
  const name = decodeWordPressText(term.name || '').trim();

  return slug
    ? {
        slug,
        name: name || slug,
      }
    : null;
}

export function normalizeCmsTerms(values) {
  const terms = [];
  const seen = new Set();

  for (const value of Array.isArray(values) ? values : []) {
    const term = normalizeCmsTerm(value);

    if (!term || seen.has(term.slug)) {
      continue;
    }

    seen.add(term.slug);
    terms.push(term);
  }

  return terms;
}
