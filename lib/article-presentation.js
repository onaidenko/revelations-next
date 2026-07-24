const WORDS_PER_MINUTE = 220;

function normalizedLabel(value) {
  return String(value || '')
    .trim()
    .toLowerCase();
}

function addUniqueLabel(labels, label) {
  if (!label?.name) {
    return;
  }

  const value = normalizedLabel(label.name);

  if (!value || labels.some((item) => normalizedLabel(item.name) === value)) {
    return;
  }

  labels.push(label);
}

export function buildArticleHeaderLabels({
  section,
  sectionName,
  taxonomy,
}) {
  const labels = [];

  addUniqueLabel(labels, {
    name: sectionName || section || '',
    href: section ? `/${section}` : null,
  });

  addUniqueLabel(labels, taxonomy?.primaryTopic);

  for (const term of taxonomy?.secondaryTopics || []) {
    if (labels.length >= 3) {
      break;
    }

    addUniqueLabel(labels, term);
  }

  for (const terms of [
    taxonomy?.series,
    taxonomy?.locations,
    taxonomy?.tags,
  ]) {
    if (labels.length >= 3) {
      break;
    }

    for (const term of terms || []) {
      if (labels.length >= 3) {
        break;
      }

      addUniqueLabel(labels, term);
    }
  }

  return labels.slice(0, 3);
}

export function countArticleWords(content) {
  if (typeof content !== 'string' || !content.trim()) {
    return 0;
  }

  const text = content
    .replace(/<[^>]*>/g, ' ')
    .replace(/!\[[^\]]*\]\([^)]*\)/g, ' ')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/[`*_>#~]/g, ' ');

  return text.match(/[\p{L}\p{N}]+(?:['’][\p{L}\p{N}]+)*/gu)?.length || 0;
}

export function readingTimeMinutes(content) {
  const words = countArticleWords(content);

  return words > 0
    ? Math.max(1, Math.ceil(words / WORDS_PER_MINUTE))
    : null;
}

export function formatReadingTime(content) {
  const minutes = readingTimeMinutes(content);

  if (!minutes) {
    return '';
  }

  return `${minutes} min read`;
}
