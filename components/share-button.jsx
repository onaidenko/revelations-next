'use client';
import { useState } from 'react';
export default function ShareButton({url}){const[copied,setCopied]=useState(false);async function copy(){await navigator.clipboard.writeText(url);setCopied(true);setTimeout(()=>setCopied(false),1500)}return <button onClick={copy} className="font-mono text-[10px] tracking-[0.18em] uppercase text-muted-foreground border border-border/40 rounded-full px-4 py-1.5">{copied?'Copied ✓':'Copy link'}</button>}
