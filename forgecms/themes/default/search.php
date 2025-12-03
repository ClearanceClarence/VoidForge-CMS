<?php
/**
 * Search Results Template - Forge CMS
 * A beautiful, modern search results page
 */

defined('CMS_ROOT') or die('Direct access not allowed');

$siteTitle = getOption('site_title', 'My Site');
$customCss = getOption('custom_frontend_css', '');

// Get search query
$searchQuery = isset($searchQuery) ? $searchQuery : '';
$pageTitle = 'Search: ' . $searchQuery;

// Search both posts and pages
$posts = [];
$pages = [];
$totalResults = 0;

if (!empty($searchQuery)) {
    $posts = Post::query([
        'post_type' => 'post',
        'status' => 'published',
        'search' => $searchQuery,
        'limit' => 20
    ]);
    
    $pages = Post::query([
        'post_type' => 'page',
        'status' => 'published',
        'search' => $searchQuery,
        'limit' => 10
    ]);
    
    $totalResults = count($posts) + count($pages);
}

/**
 * Highlight search terms in text
 */
function highlightSearch($text, $query) {
    if (empty($query)) return $text;
    $words = preg_split('/\s+/', $query);
    foreach ($words as $word) {
        if (strlen($word) >= 2) {
            $text = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<mark>$1</mark>', $text);
        }
    }
    return $text;
}

/**
 * Get excerpt with search context
 */
function getSearchExcerpt($content, $query, $length = 200) {
    $text = strip_tags($content);
    $words = preg_split('/\s+/', $query);
    
    // Find first occurrence of any search word
    $pos = false;
    foreach ($words as $word) {
        if (strlen($word) >= 2) {
            $wordPos = stripos($text, $word);
            if ($wordPos !== false && ($pos === false || $wordPos < $pos)) {
                $pos = $wordPos;
            }
        }
    }
    
    // If found, center excerpt around that position
    if ($pos !== false) {
        $start = max(0, $pos - 60);
        $excerpt = substr($text, $start, $length);
        if ($start > 0) $excerpt = '...' . $excerpt;
        if (strlen($text) > $start + $length) $excerpt .= '...';
    } else {
        $excerpt = substr($text, 0, $length);
        if (strlen($text) > $length) $excerpt .= '...';
    }
    
    return highlightSearch($excerpt, $query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> - <?= esc($siteTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= THEME_URL ?>/assets/css/theme.css">
    <style>
        .search-hero {
            padding: 5rem 0 3rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, #fff 100%);
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }
        
        .search-form {
            display: flex;
            gap: 0.75rem;
            max-width: 600px;
        }
        
        .search-input {
            flex: 1;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            background: #fff;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .search-btn {
            padding: 1rem 1.75rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        
        .search-stats {
            margin-top: 1rem;
            color: var(--text-secondary);
            font-size: 0.9375rem;
        }
        
        .search-stats strong {
            color: var(--primary);
        }
        
        .results-section {
            padding: 3rem 0;
        }
        
        .results-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .results-header svg {
            color: var(--primary);
        }
        
        .results-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .results-count {
            background: var(--gray-100);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .result-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-item:hover {
            background: var(--gray-50);
            margin: 0 -1.5rem;
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            border-bottom-color: transparent;
        }
        
        .result-type {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }
        
        .result-type.page {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .result-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .result-title a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .result-title a:hover {
            color: var(--primary);
        }
        
        .result-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }
        
        .result-meta span {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .result-excerpt {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 0.9375rem;
        }
        
        .result-excerpt mark {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-dark);
            padding: 0.125rem 0.25rem;
            border-radius: 2px;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .no-results-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .no-results p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .suggestions {
            max-width: 400px;
            margin: 0 auto;
            text-align: left;
        }
        
        .suggestions h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }
        
        .suggestions ul {
            list-style: none;
            padding: 0;
        }
        
        .suggestions li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .suggestions li svg {
            color: var(--primary);
            flex-shrink: 0;
        }
        
        @media (max-width: 768px) {
            .search-hero h1 {
                font-size: 1.75rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-btn {
                justify-content: center;
            }
            
            .result-item:hover {
                margin: 0;
                padding: 1.5rem 0;
            }
        }
    </style>
    <?php if ($customCss): ?>
    <style id="custom-frontend-css"><?= $customCss ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="page-wrapper">
        <nav class="navbar scrolled">
            <div class="container">
                <a href="<?= SITE_URL ?>" class="nav-logo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <span><?= esc($siteTitle) ?></span>
                </a>
                <div class="nav-links">
                    <a href="<?= SITE_URL ?>">Home</a>
                    <a href="<?= ADMIN_URL ?>" class="btn btn-primary btn-sm">Dashboard</a>
                </div>
            </div>
        </nav>
        
        <!-- Search Hero -->
        <section class="search-hero">
            <div class="container">
                <h1>
                    <?php if (empty($searchQuery)): ?>
                        Search
                    <?php else: ?>
                        Search Results
                    <?php endif; ?>
                </h1>
                
                <form action="<?= SITE_URL ?>" method="GET" class="search-form">
                    <input type="text" name="s" class="search-input" placeholder="Search posts, pages..." value="<?= esc($searchQuery) ?>" autofocus>
                    <button type="submit" class="search-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Search
                    </button>
                </form>
                
                <?php if (!empty($searchQuery)): ?>
                <p class="search-stats">
                    Found <strong><?= $totalResults ?></strong> result<?= $totalResults !== 1 ? 's' : '' ?> for "<strong><?= esc($searchQuery) ?></strong>"
                </p>
                <?php endif; ?>
            </div>
        </section>
        
        <main class="page-content" style="padding: 0;">
            <div class="container">
                <?php if (empty($searchQuery)): ?>
                    <!-- Empty search -->
                    <section class="results-section">
                        <div class="no-results">
                            <div class="no-results-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                            </div>
                            <h3>What are you looking for?</h3>
                            <p>Enter a search term above to find posts and pages.</p>
                        </div>
                    </section>
                    
                <?php elseif ($totalResults === 0): ?>
                    <!-- No results -->
                    <section class="results-section">
                        <div class="no-results">
                            <div class="no-results-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            </div>
                            <h3>No results found</h3>
                            <p>We couldn't find anything matching "<strong><?= esc($searchQuery) ?></strong>"</p>
                            
                            <div class="suggestions">
                                <h4>Suggestions:</h4>
                                <ul>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Check your spelling
                                    </li>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Try more general keywords
                                    </li>
                                    <li>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        Try fewer keywords
                                    </li>
                                </ul>
                            </div>
                            
                            <a href="<?= SITE_URL ?>" class="btn btn-primary" style="margin-top: 2rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                                Back to Home
                            </a>
                        </div>
                    </section>
                    
                <?php else: ?>
                    <!-- Results found -->
                    
                    <?php if (!empty($posts)): ?>
                    <section class="results-section">
                        <div class="results-header">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <h2>Posts</h2>
                            <span class="results-count"><?= count($posts) ?></span>
                        </div>
                        
                        <?php foreach ($posts as $result): 
                            $author = Post::getAuthor($result);
                        ?>
                        <article class="result-item">
                            <span class="result-type">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                </svg>
                                Post
                            </span>
                            <h3 class="result-title">
                                <a href="<?= SITE_URL ?>/post/<?= esc($result['slug']) ?>"><?= highlightSearch(esc($result['title']), $searchQuery) ?></a>
                            </h3>
                            <div class="result-meta">
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <?= formatDate($result['published_at'] ?? $result['created_at'], 'M j, Y') ?>
                                </span>
                                <?php if ($author): ?>
                                <span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <?= esc($author['display_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <p class="result-excerpt"><?= getSearchExcerpt($result['content'], $searchQuery) ?></p>
                        </article>
                        <?php endforeach; ?>
                    </section>
                    <?php endif; ?>
                    
                    <?php if (!empty($pages)): ?>
                    <section class="results-section">
                        <div class="results-header">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <h2>Pages</h2>
                            <span class="results-count"><?= count($pages) ?></span>
                        </div>
                        
                        <?php foreach ($pages as $result): ?>
                        <article class="result-item">
                            <span class="result-type page">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                </svg>
                                Page
                            </span>
                            <h3 class="result-title">
                                <a href="<?= SITE_URL ?>/<?= esc($result['slug']) ?>"><?= highlightSearch(esc($result['title']), $searchQuery) ?></a>
                            </h3>
                            <p class="result-excerpt"><?= getSearchExcerpt($result['content'], $searchQuery) ?></p>
                        </article>
                        <?php endforeach; ?>
                    </section>
                    <?php endif; ?>
                    
                <?php endif; ?>
            </div>
        </main>
        
<?php include __DIR__ . '/footer.php'; ?>
