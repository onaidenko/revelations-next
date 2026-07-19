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
      className="font-mono text-[10px] tracking-[0.18em] uppercase text-muted-foreground border border-border/40 rounded-full px-4 py-1.5"
    >
      {copied ? 'Copied ✓' : 'Copy link'}
    </button>
  );
}
