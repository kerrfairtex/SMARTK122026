/**
 * Vercel Edge Function — Reverse Proxy to Render Backend
 *
 * The SMARTCAMP-K12 landing page is a PHP app served by Render.
 * Vercel acts as a CDN + reverse proxy:
 *   - Serves static assets from Vercel's edge cache (fast global delivery)
 *   - Proxies dynamic PHP requests to the Render backend
 *
 * Static assets served from Vercel:
 *   - /public/css/* (CSS)
 *   - /public/js/* (JavaScript)
 *   - /public/icons/* (PWA icons)
 *   - /public/manifest.json
 *   - /public/favicon.ico
 *   - /assets/images/* (landing photos + logo)
 *   - /apple-touch-icon.png
 *
 * Everything else is proxied to Render:
 *   - /index.php (landing page — needs PHP execution)
 *   - /login.php (RosarioSIS login — needs PHP execution)
 *   - /privacy.php, /contact_api.php, etc.
 *
 * @package SmartCampus
 */

// Render backend URL (configured via env var, fallback to default)
const RENDER_BACKEND = process.env.RENDER_BACKEND || 'https://smartcampk12.onrender.com';

// Paths that are served as static files from Vercel's edge cache
const STATIC_PATHS = [
  '/public/css/',
  '/public/js/',
  '/public/icons/',
  '/public/manifest.json',
  '/public/favicon.ico',
  '/public/favicon-32.png',
  '/assets/images/',
  '/apple-touch-icon.png',
  '/pwabuilder-sw.js',
];

module.exports = async (req, res) => {
  const url = new URL(req.url, RENDER_BACKEND);
  const path = url.pathname;

  // Check if this is a static asset that should be served from Vercel
  const isStatic = STATIC_PATHS.some((prefix) => path.startsWith(prefix));

  if (isStatic) {
    // Let Vercel's static file serving handle this
    // The vercel.json builds config will serve these from root
    return;
  }

  // Proxy dynamic requests to Render backend
  try {
    const backendUrl = new URL(req.url, RENDER_BACKEND);

    const headers = { ...req.headers };
    delete headers.host;
    delete headers['x-forwarded-host'];
    delete headers['x-forwarded-proto'];
    headers['x-forwarded-host'] = 'smartcampk12.onrender.com';
    headers['x-forwarded-proto'] = 'https';

    const response = await fetch(backendUrl, {
      method: req.method,
      headers,
      body: req.method !== 'GET' && req.method !== 'HEAD' ? req.body : undefined,
      redirect: 'manual',
    });

    // Copy response headers
    const responseHeaders = {};
    for (const [key, value] of response.headers.entries()) {
      if (key.toLowerCase() !== 'transfer-encoding') {
        responseHeaders[key] = value;
      }
    }

    res.status(response.status || 200);
    for (const [key, value] of Object.entries(responseHeaders)) {
      res.setHeader(key, value);
    }

    const body = await response.text();
    res.send(body);
  } catch (error) {
    console.error('Proxy error:', error);
    res.status(502);
    res.send('Bad Gateway: Unable to reach Render backend');
  }
};
