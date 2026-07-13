'use client';

import { useEffect, useState } from 'react';

export default function ThemeToggle() {
  const [theme, setTheme] = useState('dark');

  useEffect(() => {
    const saved =
      localStorage.getItem('revelations-theme') || 'dark';

    setTheme(saved);
    document.documentElement.className = saved;
  }, []);

  function toggle() {
    const next =
      theme === 'dark' ? 'light' : 'dark';

    setTheme(next);
    localStorage.setItem(
      'revelations-theme',
      next
    );

    document.documentElement.className = next;
  }

  const label =
    theme === 'dark'
      ? 'Switch to light theme'
      : 'Switch to dark theme';

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={label}
      title={label}
      className="
        flex h-8 w-8 items-center justify-center
        text-muted-foreground
        transition-colors
        hover:text-foreground
      "
    >
      {theme === 'dark' ? (
        <svg
          aria-hidden="true"
          width="19"
          height="19"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.6"
          strokeLinecap="round"
        >
          <circle cx="12" cy="12" r="3.5" />
          <path d="M12 2v2" />
          <path d="M12 20v2" />
          <path d="m4.93 4.93 1.42 1.42" />
          <path d="m17.65 17.65 1.42 1.42" />
          <path d="M2 12h2" />
          <path d="M20 12h2" />
          <path d="m4.93 19.07 1.42-1.42" />
          <path d="m17.65 6.35 1.42-1.42" />
        </svg>
      ) : (
        <svg
          aria-hidden="true"
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.6"
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7Z" />
        </svg>
      )}
    </button>
  );
}
