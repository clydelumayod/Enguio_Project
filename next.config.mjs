/** @type {import('next').NextConfig} */
const nextConfig = {
  async rewrites() {
    return [
      {
        source: '/Api/print-receipt.php',
        destination: 'http://localhost/Enguio_Project/Api/print-receipt.php'
      }
    ]
  }
};

export default nextConfig;
