CREATE TABLE IF NOT EXISTS articles (
  id text PRIMARY KEY,
  slug text UNIQUE NOT NULL,
  title text NOT NULL,
  section text NOT NULL CHECK (section IN ('news','people','tech','places','unspoken','podcast')),
  excerpt text,
  content text,
  cover_image text,
  author text,
  tags jsonb NOT NULL DEFAULT '[]'::jsonb,
  youtube_url text,
  featured boolean NOT NULL DEFAULT false,
  is_gated boolean NOT NULL DEFAULT false,
  status text NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published')),
  publication_date timestamptz,
  created_date timestamptz,
  updated_date timestamptz
);
CREATE INDEX IF NOT EXISTS articles_status_publication_idx ON articles(status, publication_date DESC);
CREATE INDEX IF NOT EXISTS articles_section_publication_idx ON articles(section, publication_date DESC);
