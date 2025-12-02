<?php
/**
 * 404 Not Found Template - Forge CMS
 * A stunning, animated 404 page
 */

$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - <?= esc(getOption('site_name', 'Forge CMS')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= THEME_URL ?>/assets/css/404.css">
</head>
<body class="error-page">
    <div class="error-container">
        <!-- Animated Background -->
        <div class="bg-effects">
            <div class="gradient-orb orb-1"></div>
            <div class="gradient-orb orb-2"></div>
            <div class="gradient-orb orb-3"></div>
            <div class="grid-overlay"></div>
        </div>

        <!-- Content -->
        <div class="error-content">
            <!-- Glitch 404 Number -->
            <div class="error-code" data-text="404">
                <span class="digit">4</span>
                <span class="digit zero">
                    <svg viewBox="0 0 120 120" class="gear">
                        <circle cx="60" cy="60" r="45" fill="none" stroke="currentColor" stroke-width="8" stroke-dasharray="20 10"/>
                        <circle cx="60" cy="60" r="25" fill="none" stroke="currentColor" stroke-width="6"/>
                        <circle cx="60" cy="60" r="8" fill="currentColor"/>
                    </svg>
                </span>
                <span class="digit">4</span>
            </div>

            <h1 class="error-title">Lost in the Digital Void</h1>
            <p class="error-message">The page you're seeking has vanished into the ether, or perhaps it never existed at all.</p>

            <!-- Animated Search Bar Illustration -->
            <div class="search-illustration">
                <div class="search-bar">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <div class="search-text">
                        <span class="typing-text"></span>
                        <span class="cursor">|</span>
                    </div>
                </div>
                <div class="search-results">
                    <div class="result-item empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                        <span>No results found</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="error-actions">
                <a href="<?= SITE_URL ?>" class="btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Return Home
                </a>
                <button onclick="history.back()" class="btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Go Back
                </button>
            </div>

            <!-- Fun Stats -->
            <div class="error-stats">
                <div class="stat">
                    <span class="stat-number" data-count="404">0</span>
                    <span class="stat-label">Error Code</span>
                </div>
                <div class="stat">
                    <span class="stat-number" data-count="∞">∞</span>
                    <span class="stat-label">Pages Exist</span>
                </div>
                <div class="stat">
                    <span class="stat-number" data-count="1">0</span>
                    <span class="stat-label">Is Missing</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="error-footer">
            <div class="footer-brand">
                <svg width="24" height="24" viewBox="0 0 48 48">
                    <defs>
                        <linearGradient id="fg" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1"/>
                            <stop offset="100%" style="stop-color:#8b5cf6"/>
                        </linearGradient>
                    </defs>
                    <rect x="4" y="4" width="40" height="40" rx="12" fill="url(#fg)"/>
                    <path d="M14 12h20v5h-13v6h10v5h-10v10h-7V12z" fill="white" opacity="0.95"/>
                </svg>
                <span>Forge CMS</span>
            </div>
        </footer>
    </div>

    <script>
    // Typing animation
    const texts = ['page not found', 'looking...', 'searching...', '???'];
    let textIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    const typingEl = document.querySelector('.typing-text');

    function type() {
        const currentText = texts[textIndex];
        
        if (isDeleting) {
            typingEl.textContent = currentText.substring(0, charIndex - 1);
            charIndex--;
        } else {
            typingEl.textContent = currentText.substring(0, charIndex + 1);
            charIndex++;
        }

        let typeSpeed = isDeleting ? 50 : 100;

        if (!isDeleting && charIndex === currentText.length) {
            typeSpeed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            textIndex = (textIndex + 1) % texts.length;
            typeSpeed = 500;
        }

        setTimeout(type, typeSpeed);
    }
    type();

    // Count up animation
    document.querySelectorAll('.stat-number[data-count]').forEach(el => {
        const target = el.dataset.count;
        if (target === '∞') return;
        
        let current = 0;
        const increment = Math.ceil(parseInt(target) / 30);
        const timer = setInterval(() => {
            current += increment;
            if (current >= parseInt(target)) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = current;
            }
        }, 30);
    });
    </script>
</body>
</html>
