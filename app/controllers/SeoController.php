<?php

declare(strict_types=1);

class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');

        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /app\n";
        echo "Disallow: /database\n";
        echo 'Sitemap: ' . full_url('sitemap.xml') . "\n";
        exit;
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');

        $urls = [
            full_url(),
            full_url('menu'),
            full_url('contact'),
        ];

        $today = date('Y-m-d');

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            echo '<url>';
            echo '<loc>' . e($url) . '</loc>';
            echo '<lastmod>' . $today . '</lastmod>';
            echo '<changefreq>weekly</changefreq>';
            echo '<priority>' . ($url === full_url() ? '1.0' : '0.8') . '</priority>';
            echo '</url>';
        }

        echo '</urlset>';
        exit;
    }
}
