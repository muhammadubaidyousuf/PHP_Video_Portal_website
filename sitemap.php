<?php
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
// Dynamic site URL
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST']);
require_once __DIR__.'/config/database.php';

// Use mysqli from config/database.php
$conn = $conn ?? null;
if (!$conn) {
    echo '<!-- Database connection error -->';
    exit;
}
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?= htmlspecialchars(SITE_URL . '/') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars(SITE_URL . '/about.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars(SITE_URL . '/disclaimer.php') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <!-- Categories and Videos -->
    <?php
    // Get all active categories
    $cat_q = $conn->query("SELECT id, slug FROM categories");
    while ($cat = $cat_q->fetch_assoc()):
        $cat_url = SITE_URL . '/video/' . urlencode($cat['slug']) . '/';
    ?>
    <url>
        <loc><?= htmlspecialchars($cat_url) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
        // Get all videos in this category
        $vid_stmt = $conn->prepare("SELECT slug, updated_at FROM videos WHERE category_id=? AND status='active'");
        $vid_stmt->bind_param('i', $cat['id']);
        $vid_stmt->execute();
        $vid_res = $vid_stmt->get_result();
        while ($video = $vid_res->fetch_assoc()):
            $vid_url = SITE_URL . '/video/' . urlencode($cat['slug']) . '/' . urlencode($video['slug']) . '/';
            $lastmod = date('Y-m-d', strtotime($video['updated_at']));
    ?>
    <url>
        <loc><?= htmlspecialchars($vid_url) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endwhile; $vid_stmt->close(); endwhile; ?>
</urlset>