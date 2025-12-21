<?php
/**
 * Home Page Template
 * 
 * @package Flavor
 * @version 2.0.0
 */

get_header();

// Get recent posts
$recentPosts = Post::query([
    'post_type' => 'post',
    'status' => 'published',
    'limit' => 3,
    'orderby' => 'created_at',
    'order' => 'DESC'
]);
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg">
        <div class="hero-gradient"></div>
        <div class="hero-grid"></div>
        <div class="hero-glow hero-glow-1"></div>
        <div class="hero-glow hero-glow-2"></div>
    </div>
    
    <div class="hero-content">
        <div class="hero-badge animate-fade-in">
            <span class="badge-dot"></span>
            <span>Introducing VoidForge CMS</span>
        </div>
        
        <h1 class="hero-title animate-fade-in" style="animation-delay: 0.1s">
            Build websites that<br>
            <span class="gradient-text">stand out</span>
        </h1>
        
        <p class="hero-description animate-fade-in" style="animation-delay: 0.2s">
            A lightweight, blazing-fast content management system built for creators who refuse to compromise. No bloat, no complexity—just pure performance.
        </p>
        
        <div class="hero-actions animate-fade-in" style="animation-delay: 0.3s">
            <a href="<?php echo site_url('/admin'); ?>" class="btn btn-primary btn-xl">
                Start Building Free
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
            <a href="#demo" class="btn btn-ghost btn-xl">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polygon points="10 8 16 12 10 16 10 8" fill="currentColor" stroke="none"/>
                </svg>
                Watch Demo
            </a>
        </div>
        
        <div class="hero-stats animate-fade-in" style="animation-delay: 0.4s">
            <div class="hero-stat">
                <span class="stat-value">&lt;50ms</span>
                <span class="stat-label">Page Load</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-value">~350KB</span>
                <span class="stat-label">Total Size</span>
            </div>
            <div class="stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-value">100%</span>
                <span class="stat-label">Open Source</span>
            </div>
        </div>
    </div>
    
    <!-- Hero Visual -->
    <div class="hero-visual animate-fade-in" style="animation-delay: 0.5s">
        <div class="hero-browser">
            <div class="browser-header">
                <div class="browser-dots">
                    <span></span><span></span><span></span>
                </div>
                <div class="browser-url">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span>voidforge.dev/admin</span>
                </div>
            </div>
            <div class="browser-content">
                <div class="mock-sidebar">
                    <div class="mock-logo"></div>
                    <div class="mock-nav">
                        <div class="mock-nav-item active"></div>
                        <div class="mock-nav-item"></div>
                        <div class="mock-nav-item"></div>
                        <div class="mock-nav-item"></div>
                        <div class="mock-nav-item"></div>
                    </div>
                </div>
                <div class="mock-main">
                    <div class="mock-header"></div>
                    <div class="mock-cards">
                        <div class="mock-card"></div>
                        <div class="mock-card"></div>
                        <div class="mock-card"></div>
                    </div>
                    <div class="mock-table">
                        <div class="mock-row"></div>
                        <div class="mock-row"></div>
                        <div class="mock-row"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Logos Section -->
<section class="logos-section">
    <div class="container">
        <p class="logos-label">Built with technologies you trust</p>
        <div class="logos-grid">
            <div class="logo-item">
                <svg viewBox="0 0 128 128" fill="currentColor"><path d="M64 0C28.7 0 0 28.7 0 64s28.7 64 64 64c11.2 0 21.7-2.9 30.8-7.9L48.4 55.3v36.6h-6.8V41.8h6.8l50.5 75.8C116.4 106.2 128 86.5 128 64c0-35.3-28.7-64-64-64zm22.1 84.6l-7.5-11.3V41.8h7.5v42.8z"/></svg>
                <span>PHP</span>
            </div>
            <div class="logo-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                <span>Open Source</span>
            </div>
            <div class="logo-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-.036 4.966h.072c2.612.011 5.043 1.312 6.47 3.46.172.259.072.609-.199.76l-2.07 1.153a.557.557 0 01-.722-.166c-.939-1.271-2.412-2.014-3.994-2.014h-.042c-1.598.009-3.068.765-3.999 2.065a.556.556 0 01-.722.167L4.693 9.238c-.272-.151-.374-.5-.203-.76C5.914 6.31 8.351 4.989 10.966 4.966h.998zm.033 4.508c1.655 0 3.007 1.352 3.007 3.007s-1.352 3.007-3.007 3.007-3.007-1.352-3.007-3.007 1.352-3.007 3.007-3.007zm-5.63 4.018c-.308 0-.557.249-.557.557v4.462c0 .308.249.557.557.557h11.259c.308 0 .557-.249.557-.557v-4.462a.557.557 0 00-.557-.557H6.367z"/></svg>
                <span>MySQL</span>
            </div>
            <div class="logo-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.405-.6-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/></svg>
                <span>JavaScript</span>
            </div>
            <div class="logo-item">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>
                <span>CSS3</span>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Features</span>
            <h2 class="section-title">Everything you need.<br>Nothing you don't.</h2>
            <p class="section-description">
                Powerful tools designed with simplicity in mind. Build faster, ship sooner.
            </p>
        </div>
        
        <div class="features-grid">
            <!-- Feature 1 -->
            <div class="feature-card feature-card-large">
                <div class="feature-visual">
                    <div class="blocks-demo">
                        <div class="block-item block-heading">
                            <div class="block-handle"></div>
                            <div class="block-content-mock"></div>
                        </div>
                        <div class="block-item block-para">
                            <div class="block-handle"></div>
                            <div class="block-content-mock"></div>
                            <div class="block-content-mock short"></div>
                        </div>
                        <div class="block-item block-image">
                            <div class="block-handle"></div>
                            <div class="block-img-mock"></div>
                        </div>
                    </div>
                </div>
                <div class="feature-info">
                    <div class="feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">Visual Block Editor</h3>
                    <p class="feature-description">
                        21+ customizable blocks with drag-and-drop editing. Build beautiful layouts without touching code.
                    </p>
                    <a href="#" class="feature-link">
                        Learn more
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                        <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                        <circle cx="11" cy="11" r="2"/>
                    </svg>
                </div>
                <h3 class="feature-title">Live Frontend Editing</h3>
                <p class="feature-description">
                    Edit content directly on your live site. See changes in real-time without switching contexts.
                </p>
            </div>
            
            <!-- Feature 3 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                </div>
                <h3 class="feature-title">16+ Custom Fields</h3>
                <p class="feature-description">
                    Text, media, repeaters, relationships, and more. Create any content structure you need.
                </p>
            </div>
            
            <!-- Feature 4 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"/>
                        <polyline points="8 6 2 12 8 18"/>
                    </svg>
                </div>
                <h3 class="feature-title">REST API</h3>
                <p class="feature-description">
                    Full CRUD operations with token authentication. Build headless or integrate with anything.
                </p>
            </div>
            
            <!-- Feature 5 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3 class="feature-title">Plugin System</h3>
                <p class="feature-description">
                    WordPress-compatible hooks with 90+ actions. Extend functionality without core modifications.
                </p>
            </div>
            
            <!-- Feature 6 -->
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Lightning Fast</h3>
                <p class="feature-description">
                    Sub-50ms page loads. No bloated frameworks, no unnecessary dependencies. Pure speed.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">How It Works</span>
            <h2 class="section-title">Up and running in minutes</h2>
            <p class="section-description">
                From zero to published in three simple steps.
            </p>
        </div>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">01</div>
                <div class="step-content">
                    <h3 class="step-title">Upload & Install</h3>
                    <p class="step-description">
                        Drop the files on your server and run the installer. Database configured automatically.
                    </p>
                </div>
                <div class="step-visual">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </div>
            </div>
            
            <div class="step-connector">
                <svg viewBox="0 0 100 20" fill="none">
                    <path d="M0 10h100" stroke="currentColor" stroke-width="2" stroke-dasharray="6 4"/>
                    <path d="M90 5l10 5-10 5" fill="currentColor"/>
                </svg>
            </div>
            
            <div class="step-card">
                <div class="step-number">02</div>
                <div class="step-content">
                    <h3 class="step-title">Create Content</h3>
                    <p class="step-description">
                        Use the visual editor to craft pages and posts. Drag blocks, add media, style with ease.
                    </p>
                </div>
                <div class="step-visual">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </div>
            </div>
            
            <div class="step-connector">
                <svg viewBox="0 0 100 20" fill="none">
                    <path d="M0 10h100" stroke="currentColor" stroke-width="2" stroke-dasharray="6 4"/>
                    <path d="M90 5l10 5-10 5" fill="currentColor"/>
                </svg>
            </div>
            
            <div class="step-card">
                <div class="step-number">03</div>
                <div class="step-content">
                    <h3 class="step-title">Publish & Share</h3>
                    <p class="step-description">
                        Hit publish and your content is live. Fast, secure, and ready for the world.
                    </p>
                </div>
                <div class="step-visual">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M2 12h20"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Code Showcase Section -->
<section class="code-section">
    <div class="container">
        <div class="code-section-grid">
            <div class="code-section-content">
                <span class="section-badge">Developer Friendly</span>
                <h2 class="section-title">Simple theming.<br>Zero magic.</h2>
                <p class="section-description">
                    No proprietary template language to learn. Just PHP, HTML, and CSS—the tools you already know. Create a theme in minutes, not days.
                </p>
                
                <ul class="code-features">
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Standard PHP templates</span>
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>WordPress-style functions</span>
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>No build step required</span>
                    </li>
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>Full access to all data</span>
                    </li>
                </ul>
                
                <a href="<?php echo site_url(); ?>/docs" class="btn btn-primary">
                    Read the Docs
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            
            <div class="code-showcase">
                <div class="code-tabs">
                    <button class="code-tab active" data-tab="single">single.php</button>
                    <button class="code-tab" data-tab="loop">loop example</button>
                    <button class="code-tab" data-tab="custom">custom fields</button>
                </div>
                <div class="code-panels">
                    <div class="code-panel active" id="panel-single">
                        <pre><code><span class="code-php">&lt;?php</span> <span class="code-fn">get_header</span>(); <span class="code-php">?&gt;</span>

<span class="code-tag">&lt;article</span> <span class="code-attr">class</span>=<span class="code-str">"post"</span><span class="code-tag">&gt;</span>
  <span class="code-tag">&lt;h1&gt;</span><span class="code-php">&lt;?php</span> <span class="code-kw">echo</span> <span class="code-fn">esc</span>(<span class="code-var">$post</span>[<span class="code-str">'title'</span>]); <span class="code-php">?&gt;</span><span class="code-tag">&lt;/h1&gt;</span>
  
  <span class="code-tag">&lt;div</span> <span class="code-attr">class</span>=<span class="code-str">"content"</span><span class="code-tag">&gt;</span>
    <span class="code-php">&lt;?php</span> <span class="code-fn">the_content</span>(); <span class="code-php">?&gt;</span>
  <span class="code-tag">&lt;/div&gt;</span>
<span class="code-tag">&lt;/article&gt;</span>

<span class="code-php">&lt;?php</span> <span class="code-fn">get_footer</span>(); <span class="code-php">?&gt;</span></code></pre>
                    </div>
                    <div class="code-panel" id="panel-loop">
                        <pre><code><span class="code-php">&lt;?php</span>
<span class="code-var">$posts</span> = <span class="code-class">Post</span>::<span class="code-fn">query</span>([
  <span class="code-str">'post_type'</span> => <span class="code-str">'post'</span>,
  <span class="code-str">'status'</span>    => <span class="code-str">'published'</span>,
  <span class="code-str">'limit'</span>     => <span class="code-num">10</span>
]);

<span class="code-kw">foreach</span> (<span class="code-var">$posts</span> <span class="code-kw">as</span> <span class="code-var">$post</span>):
<span class="code-php">?&gt;</span>
  <span class="code-tag">&lt;a</span> <span class="code-attr">href</span>=<span class="code-str">"<span class="code-php">&lt;?php</span> <span class="code-kw">echo</span> <span class="code-class">Post</span>::<span class="code-fn">permalink</span>(<span class="code-var">$post</span>); <span class="code-php">?&gt;</span>"</span><span class="code-tag">&gt;</span>
    <span class="code-php">&lt;?php</span> <span class="code-kw">echo</span> <span class="code-fn">esc</span>(<span class="code-var">$post</span>[<span class="code-str">'title'</span>]); <span class="code-php">?&gt;</span>
  <span class="code-tag">&lt;/a&gt;</span>
<span class="code-php">&lt;?php</span> <span class="code-kw">endforeach</span>; <span class="code-php">?&gt;</span></code></pre>
                    </div>
                    <div class="code-panel" id="panel-custom">
                        <pre><code><span class="code-comment">// Get a custom field value</span>
<span class="code-var">$price</span> = <span class="code-fn">get_field</span>(<span class="code-str">'price'</span>, <span class="code-var">$post</span>[<span class="code-str">'id'</span>]);

<span class="code-comment">// Get a repeater field</span>
<span class="code-var">$features</span> = <span class="code-fn">get_field</span>(<span class="code-str">'features'</span>, <span class="code-var">$post</span>[<span class="code-str">'id'</span>]);

<span class="code-kw">foreach</span> (<span class="code-var">$features</span> <span class="code-kw">as</span> <span class="code-var">$item</span>):
<span class="code-php">?&gt;</span>
  <span class="code-tag">&lt;div</span> <span class="code-attr">class</span>=<span class="code-str">"feature"</span><span class="code-tag">&gt;</span>
    <span class="code-tag">&lt;h3&gt;</span><span class="code-php">&lt;?php</span> <span class="code-kw">echo</span> <span class="code-fn">esc</span>(<span class="code-var">$item</span>[<span class="code-str">'title'</span>]); <span class="code-php">?&gt;</span><span class="code-tag">&lt;/h3&gt;</span>
    <span class="code-tag">&lt;p&gt;</span><span class="code-php">&lt;?php</span> <span class="code-kw">echo</span> <span class="code-fn">esc</span>(<span class="code-var">$item</span>[<span class="code-str">'desc'</span>]); <span class="code-php">?&gt;</span><span class="code-tag">&lt;/p&gt;</span>
  <span class="code-tag">&lt;/div&gt;</span>
<span class="code-php">&lt;?php</span> <span class="code-kw">endforeach</span>; <span class="code-php">?&gt;</span></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Section -->
<section class="comparison-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Why VoidForge</span>
            <h2 class="section-title">The lightweight alternative</h2>
            <p class="section-description">
                See how VoidForge compares to traditional content management systems.
            </p>
        </div>
        
        <div class="comparison-table">
            <div class="comparison-header">
                <div class="comparison-cell"></div>
                <div class="comparison-cell highlight">
                    <span class="comparison-logo">VoidForge</span>
                </div>
                <div class="comparison-cell">Traditional CMS</div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Install Size</div>
                <div class="comparison-cell highlight">
                    <span class="value good">~350 KB</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">50+ MB</span>
                </div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Page Load Time</div>
                <div class="comparison-cell highlight">
                    <span class="value good">&lt;50ms</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">200-500ms</span>
                </div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Dependencies</div>
                <div class="comparison-cell highlight">
                    <span class="value good">PHP + MySQL</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">Multiple</span>
                </div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Learning Curve</div>
                <div class="comparison-cell highlight">
                    <span class="value good">Minutes</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">Days/Weeks</span>
                </div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Template Language</div>
                <div class="comparison-cell highlight">
                    <span class="value good">Plain PHP</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">Proprietary</span>
                </div>
            </div>
            
            <div class="comparison-row">
                <div class="comparison-cell label">Cost</div>
                <div class="comparison-cell highlight">
                    <span class="value good">Free Forever</span>
                </div>
                <div class="comparison-cell">
                    <span class="value">Free / Paid</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Posts Section -->
<?php if (!empty($recentPosts)): ?>
<section class="posts-section">
    <div class="container">
        <div class="section-header-row">
            <div class="section-header-left">
                <span class="section-badge">Blog</span>
                <h2 class="section-title">Latest from the blog</h2>
            </div>
            <a href="<?php echo site_url('/posts'); ?>" class="btn btn-outline">
                View All
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        
        <div class="posts-grid">
            <?php foreach ($recentPosts as $index => $post): 
                $author = User::find($post['author_id']);
                $thumbnail = Post::featuredImage($post);
            ?>
            <article class="post-card <?php echo $index === 0 ? 'post-card-featured' : ''; ?>">
                <?php if ($thumbnail): ?>
                <div class="post-image">
                    <a href="<?php echo Post::permalink($post); ?>">
                        <img src="<?php echo esc($thumbnail); ?>" alt="<?php echo esc($post['title']); ?>" loading="lazy">
                    </a>
                </div>
                <?php else: ?>
                <div class="post-image post-image-placeholder">
                    <a href="<?php echo Post::permalink($post); ?>">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <div class="post-meta">
                        <time datetime="<?php echo $post['created_at']; ?>">
                            <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                        </time>
                        <?php if ($author): ?>
                        <span class="meta-sep">&middot;</span>
                        <span class="post-author"><?php echo esc($author['display_name'] ?? $author['username']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="post-title">
                        <a href="<?php echo Post::permalink($post); ?>">
                            <?php echo esc($post['title']); ?>
                        </a>
                    </h3>
                    
                    <p class="post-excerpt">
                        <?php echo esc(flavor_excerpt($post, $index === 0 ? 180 : 100)); ?>
                    </p>
                    
                    <a href="<?php echo Post::permalink($post); ?>" class="post-link">
                        Read article
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">FAQ</span>
            <h2 class="section-title">Questions? Answers.</h2>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h3 class="faq-question">Is VoidForge really free?</h3>
                <p class="faq-answer">Yes, 100% free and open source under the MIT license. Use it for personal or commercial projects without restrictions.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">What are the server requirements?</h3>
                <p class="faq-answer">PHP 8.0+, MySQL 5.7+ or MariaDB 10.3+, and a web server (Apache/Nginx). That's it—no complex dependencies.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Can I migrate from WordPress?</h3>
                <p class="faq-answer">Yes! Export your WordPress content and use our import tools. The hook system is WordPress-compatible for easy plugin adaptation.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">How do I create custom themes?</h3>
                <p class="faq-answer">Themes are simple PHP templates. If you know HTML and basic PHP, you can build anything. Check our documentation for guides.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Is there multi-language support?</h3>
                <p class="faq-answer">The admin interface is English, but you can create content in any language. Full i18n support is on our roadmap.</p>
            </div>
            
            <div class="faq-item">
                <h3 class="faq-question">Where can I get support?</h3>
                <p class="faq-answer">Check our documentation, browse GitHub issues, or join our community Discord. We're here to help.</p>
            </div>
        </div>
    </div>
</section>

<style>
/* ========================================
   Hero Section
   ======================================== */
.hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: var(--space-20) 0 var(--space-12);
    overflow: hidden;
}

.hero-bg {
    position: absolute;
    inset: 0;
    z-index: -1;
}

.hero-gradient {
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99, 102, 241, 0.15), transparent),
        radial-gradient(ellipse 60% 40% at 80% 60%, rgba(139, 92, 246, 0.1), transparent);
}

.hero-grid {
    position: absolute;
    inset: 0;
    background-image: 
        linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 70% 70% at 50% 30%, black, transparent);
}

.hero-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    animation: glow-pulse 8s ease-in-out infinite;
}

.hero-glow-1 {
    width: 400px;
    height: 400px;
    background: rgba(99, 102, 241, 0.3);
    top: 10%;
    left: 20%;
}

.hero-glow-2 {
    width: 300px;
    height: 300px;
    background: rgba(139, 92, 246, 0.25);
    top: 40%;
    right: 15%;
    animation-delay: 4s;
}

@keyframes glow-pulse {
    0%, 100% { opacity: 0.4; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}

.hero-content {
    max-width: var(--container-max);
    margin: 0 auto;
    padding: 0 var(--space-6);
    text-align: center;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    background: rgba(99, 102, 241, 0.1);
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: var(--radius-full);
    font-size: var(--text-sm);
    font-weight: 500;
    color: var(--color-primary);
    margin-bottom: var(--space-6);
}

.badge-dot {
    width: 8px;
    height: 8px;
    background: var(--color-primary);
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.hero-title {
    font-size: clamp(2.5rem, 8vw, 4.5rem);
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: var(--space-6);
    color: var(--color-text);
}

.gradient-text {
    background: linear-gradient(135deg, #6366f1 0%, #a78bfa 50%, #6366f1 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradient-shift 3s ease-in-out infinite;
}

@keyframes gradient-shift {
    0%, 100% { background-position: 0% center; }
    50% { background-position: 100% center; }
}

.hero-description {
    max-width: 640px;
    margin: 0 auto var(--space-8);
    font-size: var(--text-xl);
    color: var(--color-text-secondary);
    line-height: 1.6;
}

.hero-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-4);
    margin-bottom: var(--space-10);
}

.btn-xl {
    padding: var(--space-4) var(--space-8);
    font-size: var(--text-base);
}

.btn-ghost {
    background: transparent;
    color: var(--color-text);
    border: none;
}

.btn-ghost:hover {
    background: var(--color-bg-muted);
}

.hero-stats {
    display: inline-flex;
    align-items: center;
    gap: var(--space-6);
    padding: var(--space-4) var(--space-8);
    background: var(--color-bg);
    border: 1px solid var(--color-border-light);
    border-radius: var(--radius-full);
    box-shadow: var(--shadow-lg);
}

.hero-stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: var(--text-xl);
    font-weight: 700;
    color: var(--color-text);
    font-family: var(--font-mono);
}

.stat-label {
    font-size: var(--text-xs);
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.stat-divider {
    width: 1px;
    height: 32px;
    background: var(--color-border);
}

/* Hero Visual */
.hero-visual {
    max-width: 900px;
    margin: var(--space-12) auto 0;
    padding: 0 var(--space-6);
}

.hero-browser {
    background: var(--color-bg-dark);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: 
        0 0 0 1px rgba(255,255,255,0.1),
        0 20px 50px -10px rgba(0,0,0,0.4),
        0 0 100px rgba(99, 102, 241, 0.1);
}

.browser-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-4);
    background: rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.browser-dots {
    display: flex;
    gap: 6px;
}

.browser-dots span {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
}

.browser-dots span:first-child { background: #ff5f57; }
.browser-dots span:nth-child(2) { background: #febc2e; }
.browser-dots span:last-child { background: #28c840; }

.browser-url {
    flex: 1;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-md);
    font-size: var(--text-xs);
    color: rgba(255,255,255,0.5);
}

.browser-url svg {
    color: #28c840;
}

.browser-content {
    display: flex;
    height: 320px;
}

.mock-sidebar {
    width: 200px;
    background: rgba(255,255,255,0.03);
    border-right: 1px solid rgba(255,255,255,0.05);
    padding: var(--space-4);
}

.mock-logo {
    height: 32px;
    background: linear-gradient(90deg, rgba(99,102,241,0.5), rgba(139,92,246,0.5));
    border-radius: var(--radius-md);
    margin-bottom: var(--space-6);
}

.mock-nav {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.mock-nav-item {
    height: 36px;
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-md);
}

.mock-nav-item.active {
    background: rgba(99,102,241,0.3);
}

.mock-main {
    flex: 1;
    padding: var(--space-4);
}

.mock-header {
    height: 40px;
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-4);
}

.mock-cards {
    display: flex;
    gap: var(--space-3);
    margin-bottom: var(--space-4);
}

.mock-card {
    flex: 1;
    height: 80px;
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-md);
}

.mock-table {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.mock-row {
    height: 44px;
    background: rgba(255,255,255,0.03);
    border-radius: var(--radius-sm);
}

/* Logos Section */
.logos-section {
    padding: var(--space-12) 0;
    border-bottom: 1px solid var(--color-border-light);
}

.logos-label {
    text-align: center;
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin-bottom: var(--space-6);
}

.logos-grid {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-10);
    flex-wrap: wrap;
}

.logo-item {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-text-muted);
    font-size: var(--text-sm);
    font-weight: 500;
}

.logo-item svg {
    width: 24px;
    height: 24px;
}

/* Features Section */
.features-section {
    padding: var(--space-24) 0;
}

.section-header {
    text-align: center;
    max-width: 640px;
    margin: 0 auto var(--space-16);
}

.section-badge {
    display: inline-block;
    padding: var(--space-1) var(--space-3);
    background: var(--color-primary-subtle);
    color: var(--color-primary);
    font-size: var(--text-xs);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: var(--radius-full);
    margin-bottom: var(--space-4);
}

.section-title {
    font-size: var(--text-4xl);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: var(--space-4);
}

.section-description {
    font-size: var(--text-lg);
    color: var(--color-text-secondary);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
}

.feature-card {
    background: var(--color-bg);
    border: 1px solid var(--color-border-light);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    transition: all var(--duration-normal) var(--ease-out);
}

.feature-card:hover {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 1px var(--color-primary), var(--shadow-lg);
    transform: translateY(-2px);
}

.feature-card-large {
    grid-column: span 3;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-8);
    padding: var(--space-10);
    background: linear-gradient(135deg, var(--color-bg) 0%, var(--color-bg-subtle) 100%);
}

.feature-visual {
    background: var(--color-bg-dark);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.blocks-demo {
    width: 100%;
    max-width: 300px;
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.block-item {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
    padding: var(--space-3);
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-md);
    border: 1px solid rgba(255,255,255,0.1);
}

.block-handle {
    width: 4px;
    height: 100%;
    min-height: 20px;
    background: var(--color-primary);
    border-radius: 2px;
    flex-shrink: 0;
}

.block-content-mock {
    height: 12px;
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
    flex: 1;
}

.block-content-mock.short {
    width: 60%;
    margin-top: var(--space-2);
}

.block-img-mock {
    height: 60px;
    background: linear-gradient(135deg, rgba(99,102,241,0.3), rgba(139,92,246,0.3));
    border-radius: var(--radius-sm);
    flex: 1;
}

.feature-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.feature-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: var(--color-primary-subtle);
    color: var(--color-primary);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-4);
}

.feature-title {
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: var(--space-3);
}

.feature-description {
    color: var(--color-text-secondary);
    line-height: 1.7;
    margin-bottom: var(--space-4);
}

.feature-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-primary);
    font-weight: 500;
    font-size: var(--text-sm);
}

.feature-link:hover {
    gap: var(--space-3);
}

/* How It Works */
.how-section {
    padding: var(--space-24) 0;
    background: var(--color-bg-subtle);
}

.steps-grid {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-4);
}

.step-card {
    flex: 1;
    max-width: 300px;
    text-align: center;
    padding: var(--space-8);
}

.step-number {
    font-size: var(--text-5xl);
    font-weight: 800;
    color: var(--color-primary);
    opacity: 0.2;
    line-height: 1;
    margin-bottom: var(--space-4);
}

.step-title {
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: var(--space-3);
}

.step-description {
    color: var(--color-text-secondary);
    font-size: var(--text-sm);
    line-height: 1.7;
    margin-bottom: var(--space-6);
}

.step-visual {
    color: var(--color-primary);
}

.step-connector {
    width: 100px;
    color: var(--color-border);
    flex-shrink: 0;
}

/* Code Showcase Section */
.code-section {
    padding: var(--space-24) 0;
}

.code-section-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: var(--space-12);
    align-items: center;
}

.code-section-content .section-badge {
    margin-bottom: var(--space-4);
}

.code-section-content .section-title {
    text-align: left;
    margin-bottom: var(--space-4);
}

.code-section-content .section-description {
    text-align: left;
    margin-bottom: var(--space-6);
}

.code-features {
    list-style: none;
    margin: 0 0 var(--space-8);
    padding: 0;
}

.code-features li {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-2) 0;
    color: var(--color-text-secondary);
}

.code-features li svg {
    color: var(--color-primary);
    flex-shrink: 0;
}

.code-showcase {
    background: var(--color-bg-dark);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-2xl);
}

.code-tabs {
    display: flex;
    background: rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.code-tab {
    padding: var(--space-3) var(--space-5);
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.5);
    font-size: var(--text-sm);
    font-family: var(--font-mono);
    cursor: pointer;
    transition: all var(--duration-fast);
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
}

.code-tab:hover {
    color: rgba(255,255,255,0.8);
}

.code-tab.active {
    color: var(--color-primary-light);
    border-bottom-color: var(--color-primary);
    background: rgba(99,102,241,0.1);
}

.code-panels {
    padding: var(--space-6);
}

.code-panel {
    display: none;
}

.code-panel.active {
    display: block;
}

.code-panel pre {
    margin: 0;
    font-family: var(--font-mono);
    font-size: 13px;
    line-height: 1.7;
    overflow-x: auto;
}

.code-panel code {
    color: #e2e8f0;
}

.code-php { color: #f472b6; }
.code-fn { color: #60a5fa; }
.code-kw { color: #c084fc; }
.code-var { color: #4ade80; }
.code-str { color: #fbbf24; }
.code-num { color: #f472b6; }
.code-class { color: #22d3ee; }
.code-tag { color: #f472b6; }
.code-attr { color: #60a5fa; }
.code-comment { color: #64748b; font-style: italic; }

/* Comparison Section */
.comparison-section {
    padding: var(--space-24) 0;
    background: var(--color-bg-subtle);
}

.comparison-table {
    max-width: 800px;
    margin: 0 auto;
    background: var(--color-bg);
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.comparison-header,
.comparison-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
}

.comparison-header {
    background: var(--color-bg-dark);
}

.comparison-header .comparison-cell {
    padding: var(--space-5) var(--space-6);
    color: var(--color-text-inverse);
    font-weight: 600;
    font-size: var(--text-sm);
}

.comparison-header .comparison-cell.highlight {
    background: var(--color-primary);
}

.comparison-logo {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.comparison-row {
    border-bottom: 1px solid var(--color-border-light);
}

.comparison-row:last-child {
    border-bottom: none;
}

.comparison-cell {
    padding: var(--space-4) var(--space-6);
    display: flex;
    align-items: center;
}

.comparison-cell.label {
    font-weight: 500;
    color: var(--color-text);
}

.comparison-cell.highlight {
    background: var(--color-primary-subtle);
}

.comparison-cell .value {
    font-size: var(--text-sm);
    color: var(--color-text-secondary);
}

.comparison-cell .value.good {
    color: var(--color-primary);
    font-weight: 600;
}

/* Posts Section */
.posts-section {
    padding: var(--space-24) 0;
    background: var(--color-bg-subtle);
}

.section-header-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: var(--space-12);
}

.section-header-left .section-title {
    margin-bottom: 0;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-6);
}

.post-card {
    background: var(--color-bg);
    border-radius: var(--radius-xl);
    overflow: hidden;
    border: 1px solid var(--color-border-light);
    transition: all var(--duration-normal) var(--ease-out);
}

.post-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.post-image {
    aspect-ratio: 16/10;
    overflow: hidden;
}

.post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--duration-normal);
}

.post-card:hover .post-image img {
    transform: scale(1.05);
}

.post-image-placeholder {
    background: var(--color-bg-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
}

.post-content {
    padding: var(--space-6);
}

.post-meta {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-size: var(--text-sm);
    color: var(--color-text-muted);
    margin-bottom: var(--space-3);
}

.post-title {
    font-size: var(--text-xl);
    font-weight: 700;
    margin-bottom: var(--space-3);
    line-height: 1.3;
}

.post-title a {
    color: var(--color-text);
}

.post-title a:hover {
    color: var(--color-primary);
}

.post-excerpt {
    color: var(--color-text-secondary);
    font-size: var(--text-sm);
    line-height: 1.7;
    margin-bottom: var(--space-4);
}

.post-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-primary);
    font-weight: 500;
    font-size: var(--text-sm);
}

.post-link:hover {
    gap: var(--space-3);
}

/* FAQ Section */
.faq-section {
    padding: var(--space-24) 0;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--space-6);
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    padding: var(--space-6);
    background: var(--color-bg-subtle);
    border-radius: var(--radius-lg);
}

.faq-question {
    font-size: var(--text-lg);
    font-weight: 600;
    margin-bottom: var(--space-3);
}

.faq-answer {
    color: var(--color-text-secondary);
    line-height: 1.7;
    margin: 0;
}

/* Animations */
.animate-fade-in {
    animation: fadeIn 0.6s ease-out forwards;
    opacity: 0;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .feature-card-large {
        grid-column: span 2;
    }
    
    .steps-grid {
        flex-wrap: wrap;
    }
    
    .step-connector {
        display: none;
    }
    
    .code-section-grid {
        grid-template-columns: 1fr;
        gap: var(--space-8);
    }
    
    .code-section-content .section-title,
    .code-section-content .section-description {
        text-align: center;
    }
    
    .code-features {
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .code-section-content {
        text-align: center;
    }
    
    .posts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .hero {
        padding: var(--space-16) 0 var(--space-8);
        min-height: auto;
    }
    
    .hero-title {
        font-size: var(--text-4xl);
    }
    
    .hero-description {
        font-size: var(--text-lg);
    }
    
    .hero-actions {
        flex-direction: column;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: var(--space-4);
        padding: var(--space-6);
        border-radius: var(--radius-xl);
    }
    
    .stat-divider {
        width: 100%;
        height: 1px;
    }
    
    .hero-visual {
        display: none;
    }
    
    .logos-grid {
        gap: var(--space-6);
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .feature-card-large {
        grid-column: span 1;
        grid-template-columns: 1fr;
    }
    
    .feature-visual {
        order: -1;
    }
    
    .section-header-row {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-4);
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
    
    .comparison-header,
    .comparison-row {
        grid-template-columns: 1.2fr 1fr 1fr;
    }
    
    .comparison-cell {
        padding: var(--space-3) var(--space-4);
        font-size: var(--text-sm);
    }
    
    .comparison-header .comparison-cell {
        padding: var(--space-3) var(--space-4);
    }
    
    .code-tabs {
        overflow-x: auto;
    }
    
    .code-tab {
        white-space: nowrap;
        padding: var(--space-3) var(--space-4);
        font-size: var(--text-xs);
    }
    
    .section-title {
        font-size: var(--text-3xl);
    }
}
</style>

<script>
// Code tabs functionality
document.querySelectorAll('.code-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        
        // Update tabs
        document.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Update panels
        document.querySelectorAll('.code-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('panel-' + tabId).classList.add('active');
    });
});
</script>

<?php get_footer(); ?>
