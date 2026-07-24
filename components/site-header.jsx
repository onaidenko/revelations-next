'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useState } from 'react';
import ThemeToggle from './theme-toggle';
import { BRAND_TAGLINE } from '@/lib/site';

const LOGO_URL =
  '/media/brand/revelations-logo.png';

const NAV_LINKS = [
  { label: 'News', href: '/news' },
  { label: 'People', href: '/people' },
  { label: 'Tech', href: '/tech' },
  { label: 'Places', href: '/places' },
  { label: 'Unspoken', href: '/unspoken' },
  { label: 'Podcast', href: '/podcast', accent: true },
];

export default function SiteHeader() {
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [menuState, setMenuState] = useState({
    pathname,
    open: false,
  });
  const menuOpen =
    menuState.pathname === pathname &&
    menuState.open;

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);

    handleScroll();
    window.addEventListener('scroll', handleScroll);

    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${
        scrolled
          ? 'border-b border-border/20 bg-background/95 backdrop-blur-md'
          : 'bg-transparent'
      }`}
    >
      <div className="mx-auto max-w-7xl px-6 md:px-12">
        <div className="hidden flex-col items-center gap-2 pb-3 pt-8 md:flex">
          <Link href="/" aria-label="REVELATIONS home">
            <img
              src={LOGO_URL}
              alt="REVELATIONS"
              className="h-14 w-auto dark:invert md:h-20"
            />
          </Link>

          <span className="font-mono text-[9px] uppercase tracking-[0.22em] text-muted-foreground/60 md:text-[10px]">
            {BRAND_TAGLINE}
          </span>
        </div>

        <div className="hidden items-center justify-center pb-6 md:flex">
          <nav className="flex items-center gap-10">
            {NAV_LINKS.map((link) => {
              const active = pathname === link.href;

              return (
                <Link
                  key={link.href}
                  href={link.href}
                  className={
                    link.accent
                      ? `rounded-full border px-4 py-1 font-mono text-[11px] uppercase tracking-[0.2em] transition-colors duration-300 ${
                          active
                            ? 'border-rose/50 bg-rose/10 text-foreground'
                            : 'border-rose/30 text-foreground/80 hover:border-rose/50 hover:bg-rose/10 hover:text-foreground'
                        }`
                      : `font-mono text-[11px] uppercase tracking-[0.2em] transition-colors duration-300 ${
                          active
                            ? 'text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                        }`
                  }
                >
                  {link.label}
                </Link>
              );
            })}

            <Link
              href="/access"
              className={`font-mono text-[11px] uppercase tracking-[0.2em] transition-colors duration-300 ${
                pathname === '/access'
                  ? 'text-foreground'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              Access
            </Link>

            <ThemeToggle />
          </nav>
        </div>

        <div className="flex h-20 items-center justify-between md:hidden">
          <Link href="/" aria-label="REVELATIONS home">
            <img
              src={LOGO_URL}
              alt="REVELATIONS"
              className="h-10 w-auto dark:invert"
            />
          </Link>

          <div className="flex items-center gap-3">
            <ThemeToggle />

            <button
              type="button"
              onClick={() =>
                setMenuState({
                  pathname,
                  open: !menuOpen,
                })
              }
              aria-label="Toggle menu"
              aria-expanded={menuOpen}
              className="flex h-10 w-10 items-center justify-center"
            >
              <span className="relative flex h-5 w-5 flex-col items-center justify-center gap-1.5">
                <span
                  className={`block h-px w-5 bg-foreground transition-all duration-300 ${
                    menuOpen ? 'absolute rotate-45' : ''
                  }`}
                />
                <span
                  className={`block h-px w-5 bg-foreground transition-all duration-300 ${
                    menuOpen ? 'opacity-0' : ''
                  }`}
                />
                <span
                  className={`block h-px w-5 bg-foreground transition-all duration-300 ${
                    menuOpen ? 'absolute -rotate-45' : ''
                  }`}
                />
              </span>
            </button>
          </div>
        </div>
      </div>

      {menuOpen && (
        <div className="border-b border-border/20 bg-background/98 px-6 py-8 backdrop-blur-xl md:hidden">
          <nav className="flex flex-col gap-6">
            {NAV_LINKS.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={`font-mono text-sm uppercase tracking-[0.18em] transition-colors ${
                  pathname === link.href
                    ? 'text-foreground'
                    : 'text-muted-foreground hover:text-foreground'
                }`}
              >
                {link.label}
              </Link>
            ))}

            <Link
              href="/access"
              className={`font-mono text-sm uppercase tracking-[0.18em] transition-colors ${
                pathname === '/access'
                  ? 'text-foreground'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
            >
              Access
            </Link>
          </nav>
        </div>
      )}
    </header>
  );
}
