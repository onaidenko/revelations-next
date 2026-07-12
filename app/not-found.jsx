import Link from 'next/link'; import SiteHeader from '@/components/site-header';
export const metadata={title:'Not found',robots:{index:false,follow:false}};
export default function NotFound(){return <div className="min-h-screen"><SiteHeader/><main className="pt-56 text-center"><h1 className="font-display text-5xl mb-6">Not found</h1><Link href="/" className="font-mono text-xs uppercase text-rose">Return home</Link></main></div>}
