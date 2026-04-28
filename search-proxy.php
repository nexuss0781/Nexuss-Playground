<?php
/**
 * Nexus Pro — Search Proxy
 * InfinityFree-compatible PHP proxy for web search
 * 
 * Endpoints:
 *   POST q={query}  -> Returns JSON search results
 * 
 * Features:
 *   - DuckDuckGo Lite scraping (no API key needed)
 *   - 5-minute file-based caching
 *   - CORS headers for cross-origin requests
 *   - Rate limiting via cache
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$query = isset($_POST['q']) ? trim($_POST['q']) : '';
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query parameter "q" is required']);
    exit;
}

// Sanitize query
$query = substr($query, 0, 200);
$cacheKey = md5($query);
$cacheDir = __DIR__ . '/../cache/';
$cacheFile = $cacheDir . $cacheKey . '.json';
$cacheTTL = 300; // 5 minutes

// Ensure cache directory exists
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// Check cache
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    $cached = @file_get_contents($cacheFile);
    if ($cached) {
        echo $cached;
        exit;
    }
}

// Fetch from DuckDuckGo Lite
$searchUrl = 'https://duckduckgo.com/html/?q=' . urlencode($query) . '&kl=us-en';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $searchUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: identity',
        'Connection: keep-alive',
    ]
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($html === false || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Search service unavailable',
        'details' => $error ?: 'HTTP ' . $httpCode,
        'results' => []
    ]);
    exit;
}

// Parse results
$results = parseDuckDuckGo($html);

$response = [
    'query' => $query,
    'count' => count($results),
    'results' => $results
];

$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Save cache
@file_put_contents($cacheFile, $json, LOCK_EX);

echo $json;

/**
 * Parse DuckDuckGo Lite HTML results
 */
function parseDuckDuckGo($html) {
    $results = [];

    // Use DOMDocument for robust parsing
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    // DuckDuckGo lite result structure
    $nodes = $xpath->query('//div[contains(@class, "result")]');

    $count = 0;
    foreach ($nodes as $node) {
        if ($count >= 8) break; // Limit to 8 results

        $titleNode = $xpath->query('.//a[contains(@class, "result__a")]', $node)->item(0);
        $snippetNode = $xpath->query('.//a[contains(@class, "result__snippet")]', $node)->item(0);
        $urlNode = $xpath->query('.//a[contains(@class, "result__url")]', $node)->item(0);

        if ($titleNode && $snippetNode) {
            $title = cleanText($titleNode->textContent);
            $snippet = cleanText($snippetNode->textContent);
            $url = $titleNode->getAttribute('href');

            // Resolve relative URLs
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            } elseif (strpos($url, 'http') !== 0) {
                $url = 'https://duckduckgo.com' . $url;
            }

            // Extract actual URL from DuckDuckGo redirect
            if (strpos($url, 'duckduckgo.com/l/?') !== false) {
                parse_str(parse_url($url, PHP_URL_QUERY), $params);
                if (isset($params['uddg'])) {
                    $url = urldecode($params['uddg']);
                }
            }

            $results[] = [
                'title' => $title,
                'url' => $url,
                'snippet' => $snippet
            ];
            $count++;
        }
    }

    // Fallback: regex parsing if DOM fails
    if (empty($results)) {
        $results = parseWithRegex($html);
    }

    return $results;
}

function parseWithRegex($html) {
    $results = [];

    // Match result blocks
    preg_match_all('/<div class="result[^"]*"[^>]*>.*?<\/div>\s*<\/div>/s', $html, $blocks);

    foreach ($blocks[0] as $i => $block) {
        if ($i >= 8) break;

        preg_match('/<a[^>]*class="result__a"[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/s', $block, $titleMatch);
        preg_match('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $block, $snippetMatch);

        if ($titleMatch && $snippetMatch) {
            $url = html_entity_decode($titleMatch[1]);
            $title = strip_tags($titleMatch[2]);
            $snippet = strip_tags($snippetMatch[1]);

            if (strpos($url, '//') === 0) $url = 'https:' . $url;

            $results[] = [
                'title' => cleanText($title),
                'url' => $url,
                'snippet' => cleanText($snippet)
            ];
        }
    }

    return $results;
}

function cleanText($text) {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}
