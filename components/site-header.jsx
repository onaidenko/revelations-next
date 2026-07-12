'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useState } from 'react';
import ThemeToggle from './theme-toggle';
const LOGO_URL = 'https://media.base44.com/images/public/69dbf76f393b4827a5591a8c/a3882d98d_Untitleddesign.png';
const links = [['News','/news'],['People','/people'],['Tech','/tech'],['Places','/places'],['Unspoken','/unspoken'],['Podcast','/podcast']];
export default function SiteHeader() {
  const path = usePathname(); const [scrolled,setScrolled]=useState(false); const [open,setOpen]=useState(false);
  useEffect(()=>{const f=()=>setScrolled(window.scrollY>20); window.addEventListener('scroll',f); return()=>window.removeEventListener('scroll',f)},[]);
  useEffect(()=>setOpen(false),[path]);
  return <header className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${scrolled?'bg-background/95 backdrop-blur-md border-b border-border/20':'bg-transparent'}`}>
    <div className="max-w-7xl mx-auto px-6 md:px-12">
      <div className="hidden md:flex flex-col items-center pt-8 pb-3 gap-2"><Link href="/"><img src={LOGO_URL} alt="REVELATIONS" className="h-14 md:h-20 w-auto dark:invert"/></Link><span className="font-mono text-[9px] tracking-[0.22em] uppercase text-muted-foreground/60">Born as a podcast. Built as a media platform.</span></div>
      <nav className="hidden md:flex justify-center items-center gap-10 pb-6">{links.map(([label,href])=><Link key={href} href={href} className={`font-mono text-[11px] tracking-[0.2em] uppercase transition-colors ${path===href?'text-foreground':'text-muted-foreground hover:text-foreground'}`}>{label}</Link>)}<Link href="/access" className="font-mono text-[11px] tracking-[0.2em] uppercase text-muted-foreground hover:text-foreground">Access</Link><ThemeToggle/></nav>
      <div className="flex md:hidden h-20 items-center justify-between"><Link href="/"><img src={LOGO_URL} alt="REVELATIONS" className="h-10 w-auto dark:invert"/></Link><div className="flex items-center gap-2"><ThemeToggle/><button onClick={()=>setOpen(!open)} aria-label="Toggle menu" className="w-10 h-10 text-xl">☰</button></div></div>
    </div>
    {open&&<nav className="md:hidden bg-background/98 border-b border-border/20 px-6 py-8 flex flex-col gap-6">{links.map(([label,href])=><Link key={href} href={href} className="font-mono text-sm tracking-[0.18em] uppercase text-muted-foreground">{label}</Link>)}<Link href="/access" className="font-mono text-sm tracking-[0.18em] uppercase text-muted-foreground">Access</Link></nav>}
  </header>;
}
