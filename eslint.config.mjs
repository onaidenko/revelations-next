import { defineConfig, globalIgnores } from 'eslint/config';
import nextCoreWebVitals from 'eslint-config-next/core-web-vitals';

export default defineConfig([
  ...nextCoreWebVitals,
  globalIgnores([
    '.next/**',
    'out/**',
    'build/**',
    'coverage/**',
    'deploy-package/**',
    'deploy-production/**',
  ]),
  {
    rules: {
      // CMS image URLs are rendered unoptimized intentionally; their original
      // dimensions and formats are controlled by WordPress.
      '@next/next/no-img-element': 'off',
    },
  },
]);
