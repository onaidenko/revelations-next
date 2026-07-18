const DEFAULT_PAGE_SIZE = 100;
const DEFAULT_MAX_PAGES = 100;

function stableItemKey(item, index) {
  if (item && item.id !== undefined && item.id !== null && item.id !== '') {
    return `id:${item.id}`;
  }

  if (item && typeof item.slug === 'string' && item.slug) {
    return `slug:${item.slug}`;
  }

  return `page-item:${index}`;
}

function totalPagesFromPayload(payload) {
  const value = Number(payload?.pagination?.total_pages);

  return Number.isInteger(value) && value > 0 ? value : null;
}

/**
 * Read every available CMS page without requiring pagination metadata.
 * A repeated page signature and a finite page cap prevent a malformed
 * endpoint from creating an unbounded request loop.
 */
export async function fetchAllCmsArticlePages(
  fetchPage,
  {
    pageSize = DEFAULT_PAGE_SIZE,
    maxPages = DEFAULT_MAX_PAGES,
  } = {}
) {
  const items = [];
  const seenItems = new Set();
  const seenPageSignatures = new Set();

  for (let page = 1; page <= maxPages; page += 1) {
    const payload = await fetchPage(page, pageSize);
    const pageItems = payload?.items;

    if (!Array.isArray(pageItems)) {
      throw new Error('CMS response does not contain items');
    }

    if (pageItems.length === 0) {
      break;
    }

    const pageKeys = pageItems.map(stableItemKey);
    const signature = pageKeys.join('|');

    if (seenPageSignatures.has(signature)) {
      break;
    }

    seenPageSignatures.add(signature);

    pageItems.forEach((item, index) => {
      const key = pageKeys[index];

      if (!seenItems.has(key)) {
        seenItems.add(key);
        items.push(item);
      }
    });

    const totalPages = totalPagesFromPayload(payload);

    if (totalPages !== null && page >= totalPages) {
      break;
    }

    if (totalPages === null && pageItems.length < pageSize) {
      break;
    }
  }

  return items;
}
