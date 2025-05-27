<?php
$url = $_GET['url'] ?? '';
if (!$url) {
    http_response_code(400);
    exit("Missing 'url' parameter");
}

// Get the extension from the URL path
$ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

// Set appropriate content type headers
if ($ext === 'm3u8') {
    header('Content-Type: application/vnd.apple.mpegurl');
} elseif ($ext === 'ts') {
    header('Content-Type: video/MP2T');
} else {
    header('Content-Type: application/octet-stream');
}

// Fetch the remote content
$stream = @file_get_contents($url);
if ($stream === false) {
    http_response_code(500);
    exit("Unable to fetch content");
}

// If it's an M3U8 playlist, rewrite segment URLs to route through this proxy
if ($ext === 'm3u8') {
    $base = rtrim(dirname($url), '/');
    
    // Rewrite every non-comment line (that is not starting with '#')
    $stream = preg_replace_callback('/^(?!#)(.+)$/m', function ($matches) use ($base) {
        $segmentUrl = $matches[1];
        
        // If segment URL is relative, prepend base URL
        if (parse_url($segmentUrl, PHP_URL_SCHEME) === null) {
            $segmentUrl = $base . '/' . ltrim($segmentUrl, '/');
        }
        
        return 'proxy.php?url=' . urlencode($segmentUrl);
    }, $stream);
}

echo $stream;
