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
      className="group inline-flex items-center gap-2 border-b border-border/50 pb-2 font-mono text-[10px] uppercase tracking-[0.18em] text-muted-foreground transition-colors hover:border-rose/70 hover:text-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-rose"
    >
      <span
        aria-hidden="true"
        className="grid h-4 w-4 place-items-center border border-current text-[9px] leading-none transition-transform group-hover:-translate-y-0.5"
      >
        ↗
      </span>
      {copied ? 'Copied ✓' : 'Copy link'}
    </button>
  );
}
