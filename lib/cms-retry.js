const DEFAULT_ATTEMPTS = 3;
const DEFAULT_DELAY_MS = 250;

function wait(milliseconds) {
  return new Promise((resolve) => {
    setTimeout(resolve, milliseconds);
  });
}

export async function retryCmsOperation(
  operation,
  {
    attempts = DEFAULT_ATTEMPTS,
    delayMs = DEFAULT_DELAY_MS,
    sleep = wait,
  } = {}
) {
  if (typeof operation !== 'function') {
    throw new TypeError('CMS operation must be a function');
  }

  if (!Number.isInteger(attempts) || attempts < 1) {
    throw new RangeError('CMS retry attempts must be a positive integer');
  }

  let lastError;

  for (let attempt = 1; attempt <= attempts; attempt += 1) {
    try {
      return await operation(attempt);
    } catch (error) {
      lastError = error;

      if (attempt < attempts) {
        await sleep(delayMs * attempt);
      }
    }
  }

  throw lastError;
}
