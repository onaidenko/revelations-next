'use client';

import { useState } from 'react';
import {
  buildYouTubeEmbedUrl,
  buildYouTubeThumbnailUrl,
  parseYouTubeVideoId,
} from '@/lib/youtube';

export default function YouTubeEmbed({ url }) {
  const [playing, setPlaying] = useState(false);
  const videoId = parseYouTubeVideoId(url);
  const embedUrl = buildYouTubeEmbedUrl(videoId);
  const thumbnail = buildYouTubeThumbnailUrl(videoId);

  if (!videoId || !embedUrl || !thumbnail) return null;

  return (
    <div className="mt-12">
      <span className="mb-4 block font-mono text-[10px] uppercase tracking-[0.2em] text-rose">Watch</span>
      <div className="aspect-video overflow-hidden bg-black">
        {playing ? (
          <iframe
            className="h-full w-full"
            src={embedUrl}
            title="YouTube video player"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowFullScreen
          />
        ) : (
          <button type="button" onClick={() => setPlaying(true)} className="relative h-full w-full" aria-label="Play YouTube video">
            <img src={thumbnail} alt="" className="h-full w-full object-cover" loading="lazy" />
            <span className="absolute inset-0 grid place-items-center bg-black/25 text-sm font-mono uppercase tracking-[0.16em] text-white">Play</span>
          </button>
        )}
      </div>
      <a href={url} target="_blank" rel="noopener noreferrer" className="mt-4 inline-flex font-mono text-xs uppercase tracking-[0.15em] text-foreground">Watch on YouTube <span className="ml-3 text-rose">→</span></a>
    </div>
  );
}
