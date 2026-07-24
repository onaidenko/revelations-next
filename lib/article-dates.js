export function formatArticleDate(
  value,
  style = 'long'
) {
  if (!value) {
    return '';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const options =
    style === 'compact'
      ? {
          month: 'short',
          day: 'numeric',
        }
      : style === 'short'
        ? {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
          }
        : {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
          };

  return new Intl.DateTimeFormat(
    'en-US',
    {
      ...options,
      timeZone: 'UTC',
    }
  ).format(date);
}
