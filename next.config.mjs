/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  images: { unoptimized: true },
  allowedDevOrigins: ['172.20.10.4'],
  async redirects() {
    return [
      { source: '/Home', destination: '/', permanent: true },
      { source: '/Access', destination: '/access', permanent: true },
      { source: '/article/:slug', destination: '/:slug', permanent: true },
      { source: '/section/:sectionId', destination: '/:sectionId', permanent: true },
    ];
  },
};
export default nextConfig;
