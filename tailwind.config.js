/** @type {import('tailwindcss').Config} */
const tailwindConfig = {
  darkMode: ['class'],
  content: ['./app/**/*.{js,jsx}', './components/**/*.{js,jsx}', './lib/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        background: 'hsl(var(--background))', foreground: 'hsl(var(--foreground))',
        card: 'hsl(var(--card))', 'card-foreground': 'hsl(var(--card-foreground))',
        border: 'hsl(var(--border))', muted: 'hsl(var(--muted))',
        'muted-foreground': 'hsl(var(--muted-foreground))', rose: 'hsl(var(--rose))',
        sky: 'hsl(var(--sky))', pink: 'hsl(var(--pink))', carbon: 'hsl(var(--carbon))'
      },
      fontFamily: { display: ['var(--font-display)'], body: ['var(--font-body)'], mono: ['var(--font-mono)'] },
      animation: { marquee: 'marquee 45s linear infinite' },
      keyframes: { marquee: { '0%': { transform: 'translateX(0)' }, '100%': { transform: 'translateX(-50%)' } } }
    }
  },
  plugins: []
};

export default tailwindConfig;
