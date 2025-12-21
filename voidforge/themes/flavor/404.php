<?php
/**
 * 404 Error Page Template
 * 
 * @package Flavor
 * @version 2.0.0
 */

get_header();
?>

<main class="content-area">
    
    <div class="error-page-wrapper">
        <div class="error-page animate-fade-in">
            
            <!-- Animated illustration -->
            <div class="error-illustration">
                <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#6366f1"/>
                            <stop offset="100%" style="stop-color:#a78bfa"/>
                        </linearGradient>
                        <linearGradient id="grad2" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#e0e7ff"/>
                            <stop offset="100%" style="stop-color:#c7d2fe"/>
                        </linearGradient>
                    </defs>
                    
                    <!-- Background shapes -->
                    <circle cx="200" cy="150" r="120" fill="url(#grad2)" opacity="0.3" class="pulse-slow"/>
                    <circle cx="200" cy="150" r="80" fill="url(#grad2)" opacity="0.5" class="pulse-slow" style="animation-delay: 0.5s"/>
                    
                    <!-- Floating elements -->
                    <g class="float" style="animation-delay: 0s">
                        <rect x="50" y="80" width="40" height="40" rx="8" fill="#a78bfa" opacity="0.6"/>
                    </g>
                    <g class="float" style="animation-delay: 0.3s">
                        <circle cx="340" cy="100" r="20" fill="#6366f1" opacity="0.5"/>
                    </g>
                    <g class="float" style="animation-delay: 0.6s">
                        <rect x="320" y="200" width="30" height="30" rx="6" fill="#818cf8" opacity="0.5" transform="rotate(15 335 215)"/>
                    </g>
                    <g class="float" style="animation-delay: 0.9s">
                        <circle cx="70" cy="220" r="15" fill="#c4b5fd" opacity="0.6"/>
                    </g>
                    
                    <!-- Main 404 text with style -->
                    <text x="200" y="165" text-anchor="middle" font-family="-apple-system, BlinkMacSystemFont, sans-serif" font-size="100" font-weight="800" fill="url(#grad1)">404</text>
                    
                    <!-- Broken link illustration -->
                    <g transform="translate(145, 190)">
                        <path d="M30 25 L50 25 M60 25 L80 25" stroke="#6366f1" stroke-width="4" stroke-linecap="round" class="broken-link"/>
                        <circle cx="55" cy="25" r="8" fill="none" stroke="#a78bfa" stroke-width="3" stroke-dasharray="4 2"/>
                    </g>
                    
                    <!-- Stars/sparkles -->
                    <g class="twinkle">
                        <path d="M100 50 L102 56 L108 58 L102 60 L100 66 L98 60 L92 58 L98 56 Z" fill="#a78bfa"/>
                    </g>
                    <g class="twinkle" style="animation-delay: 0.5s">
                        <path d="M300 70 L301.5 74 L306 75.5 L301.5 77 L300 81 L298.5 77 L294 75.5 L298.5 74 Z" fill="#6366f1"/>
                    </g>
                    <g class="twinkle" style="animation-delay: 1s">
                        <path d="M350 180 L351 183 L354 184 L351 185 L350 188 L349 185 L346 184 L349 183 Z" fill="#818cf8"/>
                    </g>
                </svg>
            </div>
            
            <div class="error-content">
                <h1 class="error-title">Page Not Found</h1>
                
                <p class="error-message">
                    The page you're looking for seems to have wandered off into the void. 
                    It might have been moved, deleted, or never existed in the first place.
                </p>
                
                <div class="error-actions">
                    <a href="<?php echo site_url(); ?>" class="btn btn-primary btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Go Home
                    </a>
                    <button onclick="history.back()" class="btn btn-outline btn-lg">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"/>
                            <polyline points="12 19 5 12 12 5"/>
                        </svg>
                        Go Back
                    </button>
                </div>
                
                <div class="error-suggestions">
                    <p class="suggestions-title">You might want to try:</p>
                    <ul>
                        <li>Checking the URL for typos</li>
                        <li>Navigating from the homepage</li>
                        <li>Using the search feature</li>
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
    
</main>

<style>
/* 404 Page Styles */
.error-page-wrapper {
    min-height: calc(100vh - var(--header-height) - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-8) var(--space-6);
}

.error-page {
    text-align: center;
    max-width: 600px;
}

.error-illustration {
    width: 100%;
    max-width: 400px;
    margin: 0 auto var(--space-6);
}

.error-illustration svg {
    width: 100%;
    height: auto;
}

.error-title {
    font-size: var(--text-4xl);
    font-weight: 800;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--space-4);
}

.error-message {
    font-size: var(--text-lg);
    color: var(--color-text-secondary);
    line-height: 1.7;
    margin-bottom: var(--space-8);
}

.error-actions {
    display: flex;
    gap: var(--space-4);
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: var(--space-10);
}

.error-suggestions {
    text-align: left;
    background: var(--color-bg-subtle);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    border: 1px solid var(--color-border-light);
}

.suggestions-title {
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: var(--space-3);
    font-size: var(--text-sm);
}

.error-suggestions ul {
    list-style: none;
    margin: 0;
    padding: 0;
}

.error-suggestions li {
    color: var(--color-text-secondary);
    font-size: var(--text-sm);
    padding: var(--space-2) 0;
    padding-left: var(--space-6);
    position: relative;
}

.error-suggestions li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: var(--color-primary);
    border-radius: 50%;
}

/* Animations */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

@keyframes pulse-slow {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.05); opacity: 0.5; }
}

@keyframes twinkle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(0.8); }
}

@keyframes broken-link {
    0%, 100% { stroke-dashoffset: 0; }
    50% { stroke-dashoffset: 10; }
}

.float {
    animation: float 3s ease-in-out infinite;
}

.pulse-slow {
    animation: pulse-slow 4s ease-in-out infinite;
    transform-origin: center;
}

.twinkle {
    animation: twinkle 2s ease-in-out infinite;
}

.broken-link {
    stroke-dasharray: 10 5;
    animation: broken-link 2s linear infinite;
}

/* Button outline variant */
.btn-outline {
    background: transparent;
    border: 2px solid var(--color-border-dark);
    color: var(--color-text);
}

.btn-outline:hover {
    background: var(--color-bg-muted);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

/* Responsive */
@media (max-width: 640px) {
    .error-title {
        font-size: var(--text-3xl);
    }
    
    .error-message {
        font-size: var(--text-base);
    }
    
    .error-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-actions .btn {
        width: 100%;
        max-width: 280px;
    }
}
</style>

<?php get_footer(); ?>
