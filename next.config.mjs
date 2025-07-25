/** @type {import('next').NextConfig} */
const nextConfig = {
  webpack: (config, { isServer }) => {
    // Only on server-side
    if (isServer) {
      config.externals.push('printer');
    }
    return config;
  }
};

export default nextConfig;
