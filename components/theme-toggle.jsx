'use client';
import { useEffect, useState } from 'react';
export default function ThemeToggle() {
  const [theme, setTheme] = useState('dark');
  useEffect(() => { const saved = localStorage.getItem('revelations-theme') || 'dark'; setTheme(saved); document.documentElement.className = saved; }, []);
  function toggle() { const next = theme === 'dark' ? 'light' : 'dark'; setTheme(next); localStorage.setItem('revelations-theme', next); document.documentElement.className = next; }
  return <button onClick={toggle} aria-label="Toggle theme" className="text-muted-foreground hover:text-foreground transition-colors w-8 h-8">{theme === 'dark' ? '☀' : '◐'}</button>;
}
