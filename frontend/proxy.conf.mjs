const target = process.env.API_URL ?? 'http://localhost:8080';

export default {
  '/api': { target, changeOrigin: true },
  '/media': { target, changeOrigin: true },
};
