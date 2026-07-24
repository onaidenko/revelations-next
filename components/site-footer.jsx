import Link from 'next/link';
import {
  DEFAULT_DESCRIPTION,
  PUBLISHER_BRAND_NAME,
  SOCIAL_PROFILES,
} from '@/lib/site';

const MEDIA_LINKS = [
  ['News', '/news'],
  ['People', '/people'],
  ['Tech', '/tech'],
  ['Places', '/places'],
];

const EXPLORE_LINKS = [
  ['Topics', '/topics'],
  ['Series', '/series'],
  ['Locations', '/locations'],
  ['Entities', '/tags'],
  ['Unspoken', '/unspoken'],
  ['Podcast', '/podcast'],
];

const PLATFORM_LINKS = [
  ['About', '/about'],
  ['Advertise', '/advertise'],
  ['Access', '/access'],
  ['Contact', '/contact'],
  ['Archive', '/archive'],
];

function FooterGroup({ title, links }) {
  return (
    <div>
      <span className="mb-4 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">
        {title}
      </span>

      <div className="space-y-2">
        {links.map(([label, href]) => (
          <Link
            key={href}
            href={href}
            className="block font-body text-sm text-muted-foreground transition-colors duration-500 hover:text-foreground"
          >
            {label}
          </Link>
        ))}
      </div>
    </div>
  );
}

export default function SiteFooter() {
  return (
    <footer className="border-t border-border/30 py-16 md:py-24">
      <div className="mx-auto max-w-7xl px-6 md:px-12">
        <div className="flex flex-col justify-between gap-12 md:flex-row">
          <div className="max-w-md">
            <h2 className="mb-4 font-display text-2xl tracking-wider text-foreground">
              REVELATIONS
            </h2>

            <p className="font-body text-sm leading-relaxed text-muted-foreground">
              {DEFAULT_DESCRIPTION}
            </p>

            <div className="mt-5 flex flex-wrap gap-4">
              {SOCIAL_PROFILES.map(({ name, url }) => (
                <a
                  key={url}
                  href={url}
                  target="_blank"
                  rel="noreferrer"
                  className="font-mono text-[9px] uppercase tracking-[0.15em] text-muted-foreground transition-colors hover:text-foreground"
                >
                  {name}
                </a>
              ))}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-8 md:grid-cols-3">
            <FooterGroup title="Media" links={MEDIA_LINKS} />
            <FooterGroup title="Explore" links={EXPLORE_LINKS} />
            <FooterGroup title="Platform" links={PLATFORM_LINKS} />
          </div>
        </div>

        <div className="mt-16 flex flex-col items-center justify-between gap-4 border-t border-border/20 pt-8 md:flex-row">
          <span className="font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground">
            © {new Date().getFullYear()} REVELATIONS - Published by {PUBLISHER_BRAND_NAME}
          </span>

          <span className="font-mono text-[10px] uppercase tracking-[0.15em] text-muted-foreground">
            Selective - Intelligent - Cinematic
          </span>
        </div>
      </div>
    </footer>
  );
}
