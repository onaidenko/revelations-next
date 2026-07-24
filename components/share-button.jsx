'use client';

import { useState } from 'react';
import { resolveShareUrl } from '@/lib/share-url';

export default function ShareButton({ url }) {
  const [copied, setCopied] = useState(false);

  async function copy() {
    const shareUrl = resolveShareUrl(
      typeof window === 'undefined' ? null : window.location,
      url
    );

    if (!shareUrl || !navigator.clipboard?.writeText) {
      return;
    }

    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      // Keep the control available when a browser blocks clipboard access.
    }
  }

  return (
    <button
      onClick={copy}
      type="button"
      className="font-mono text-[10px] uppercase tracking-[0.18em] text-muted-foreground underline decoration-border/60 underline-offset-4 transition-colors hover:decoration-rose hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-rose"
    >
      {copied ? 'Copied ✓' : 'Copy link'}
    </button>
  );
}
