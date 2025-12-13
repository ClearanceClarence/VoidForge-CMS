<?php
/**
 * Nova Theme - Homepage / Landing Page
 * A modern, light theme landing page
 */

defined('CMS_ROOT') or die;

$siteTitle = getOption('site_title', 'VoidForge CMS');
$siteDescription = getOption('site_description', 'Modern Content Management');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteTitle) ?> — <?= esc($siteDescription) ?></title>
    <meta name="description" content="<?= esc($siteDescription) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <?php Plugin::doAction('vf_head'); ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white: #ffffff;
            --gray-25: #fcfcfd;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --indigo-50: #eef2ff;
            --indigo-100: #e0e7ff;
            --indigo-200: #c7d2fe;
            --indigo-300: #a5b4fc;
            --indigo-400: #818cf8;
            --indigo-500: #6366f1;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --violet-50: #f5f3ff;
            --violet-100: #ede9fe;
            --violet-400: #a78bfa;
            --violet-500: #8b5cf6;
            --violet-600: #7c3aed;
            --purple-500: #a855f7;
            --emerald-50: #ecfdf5;
            --emerald-400: #34d399;
            --emerald-500: #10b981;
            --emerald-600: #059669;
            --amber-50: #fffbeb;
            --amber-400: #fbbf24;
            --amber-500: #f59e0b;
            --rose-50: #fff1f2;
            --rose-400: #fb7185;
            --rose-500: #f43f5e;
            --cyan-50: #ecfeff;
            --cyan-400: #22d3ee;
            --cyan-500: #06b6d4;
            --blue-50: #eff6ff;
            --blue-400: #60a5fa;
            --blue-500: #3b82f6;
        }

        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--white); color: var(--gray-800); line-height: 1.6; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 2rem; }
        .gradient-text { background: linear-gradient(135deg, var(--indigo-600) 0%, var(--violet-500) 50%, var(--purple-500) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* Animated Background */
        .page-bg { position: fixed; inset: 0; z-index: -10; overflow: hidden; background: linear-gradient(180deg, var(--gray-25) 0%, var(--white) 50%, var(--gray-50) 100%); }
        .bg-gradient-orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.5; animation: orbFloat 20s ease-in-out infinite; }
        .bg-gradient-orb-1 { width: 600px; height: 600px; background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.1)); top: -200px; right: -100px; }
        .bg-gradient-orb-2 { width: 500px; height: 500px; background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(168,85,247,0.08)); bottom: -150px; left: -100px; animation-delay: -7s; }
        .bg-gradient-orb-3 { width: 400px; height: 400px; background: linear-gradient(135deg, rgba(59,130,246,0.08), rgba(99,102,241,0.1)); top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: -14s; }
        @keyframes orbFloat { 0%, 100% { transform: translate(0, 0) scale(1); } 25% { transform: translate(30px, -30px) scale(1.05); } 50% { transform: translate(-20px, 20px) scale(0.95); } 75% { transform: translate(20px, 30px) scale(1.02); } }
        .bg-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(99,102,241,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(99,102,241,0.03) 1px, transparent 1px); background-size: 60px 60px; mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black, transparent); }

        /* Header */
        .header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; padding: 1rem 0; transition: all 0.3s ease; }
        .header-blur { position: absolute; inset: 0; background: rgba(255,255,255,0.7); backdrop-filter: blur(20px) saturate(180%); border-bottom: 1px solid transparent; transition: all 0.3s ease; }
        .header.scrolled .header-blur { background: rgba(255,255,255,0.85); border-bottom-color: var(--gray-200); box-shadow: 0 4px 30px rgba(0,0,0,0.03); }
        .header-inner { position: relative; display: flex; align-items: center; justify-content: space-between; }
        .logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: var(--gray-900); }
        .logo-icon { width: 42px; height: 42px; background: linear-gradient(135deg, var(--indigo-500) 0%, var(--violet-500) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 15px rgba(99,102,241,0.35), inset 0 1px 0 rgba(255,255,255,0.2); }
        .logo-text { font-weight: 800; font-size: 1.375rem; letter-spacing: -0.03em; background: linear-gradient(135deg, var(--gray-900) 0%, var(--gray-700) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav { display: flex; align-items: center; gap: 2.5rem; }
        .nav-links { display: flex; gap: 0.5rem; list-style: none; }
        .nav-links a { display: block; padding: 0.5rem 1rem; text-decoration: none; color: var(--gray-600); font-weight: 500; font-size: 0.9375rem; border-radius: 8px; transition: all 0.2s; }
        .nav-links a:hover { color: var(--gray-900); background: var(--gray-100); }
        .nav-cta { display: flex; gap: 0.75rem; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-primary { background: linear-gradient(135deg, var(--indigo-500) 0%, var(--violet-500) 100%); color: white; box-shadow: 0 4px 15px rgba(99,102,241,0.35), inset 0 1px 0 rgba(255,255,255,0.15); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99,102,241,0.4); }
        .btn-secondary { background: var(--white); color: var(--gray-700); border: 1px solid var(--gray-200); box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .btn-secondary:hover { background: var(--gray-50); border-color: var(--gray-300); }
        .btn-xl { padding: 1rem 2rem; font-size: 1rem; border-radius: 14px; }

        /* Hero */
        .hero { padding: 11rem 0 7rem; position: relative; }
        .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center; }
        .hero-text { max-width: 580px; }
        .hero-eyebrow { display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.5rem 0.5rem 0.75rem; background: linear-gradient(135deg, var(--indigo-50), var(--violet-50)); border: 1px solid var(--indigo-100); border-radius: 100px; margin-bottom: 1.75rem; animation: fadeInUp 0.6s ease; }
        .hero-eyebrow-dot { width: 8px; height: 8px; background: var(--emerald-500); border-radius: 50%; box-shadow: 0 0 0 3px rgba(16,185,129,0.2); animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 0 0 3px rgba(16,185,129,0.2); } 50% { box-shadow: 0 0 0 6px rgba(16,185,129,0.1); } }
        .hero-eyebrow-text { font-size: 0.8125rem; font-weight: 600; color: var(--indigo-700); }
        .hero-eyebrow-badge { padding: 0.25rem 0.625rem; background: linear-gradient(135deg, var(--indigo-500), var(--violet-500)); color: white; font-size: 0.6875rem; font-weight: 700; border-radius: 100px; text-transform: uppercase; letter-spacing: 0.05em; }
        .hero h1 { font-size: 4rem; font-weight: 800; line-height: 1.08; letter-spacing: -0.035em; color: var(--gray-900); margin-bottom: 1.5rem; animation: fadeInUp 0.6s ease 0.1s both; }
        .hero-description { font-size: 1.25rem; color: var(--gray-500); line-height: 1.7; margin-bottom: 2.5rem; animation: fadeInUp 0.6s ease 0.2s both; }
        .hero-buttons { display: flex; gap: 1rem; margin-bottom: 3.5rem; animation: fadeInUp 0.6s ease 0.3s both; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .hero-metrics { display: flex; gap: 3.5rem; animation: fadeInUp 0.6s ease 0.4s both; }
        .hero-metric { position: relative; padding-left: 1rem; }
        .hero-metric::before { content: ''; position: absolute; left: 0; top: 0.25rem; bottom: 0.25rem; width: 3px; background: linear-gradient(180deg, var(--indigo-400), var(--violet-400)); border-radius: 2px; }
        .hero-metric-value { font-size: 2rem; font-weight: 800; color: var(--gray-900); letter-spacing: -0.03em; line-height: 1.2; }
        .hero-metric-label { font-size: 0.875rem; color: var(--gray-500); font-weight: 500; }

        /* Hero Visual */
        .hero-visual { position: relative; animation: fadeInUp 0.8s ease 0.3s both; }
        .hero-visual-glow { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 120%; height: 120%; background: radial-gradient(ellipse, rgba(99,102,241,0.12) 0%, transparent 60%); pointer-events: none; }
        .hero-window { position: relative; background: var(--white); border-radius: 20px; box-shadow: 0 0 0 1px rgba(0,0,0,0.03), 0 2px 4px rgba(0,0,0,0.02), 0 12px 24px rgba(0,0,0,0.06), 0 48px 80px rgba(0,0,0,0.08); overflow: hidden; transform: perspective(1200px) rotateY(-8deg) rotateX(4deg); transition: transform 0.5s ease; }
        .hero-window:hover { transform: perspective(1200px) rotateY(-2deg) rotateX(1deg); }
        .window-chrome { display: flex; align-items: center; gap: 0.5rem; padding: 1rem 1.25rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200); }
        .window-dot { width: 12px; height: 12px; border-radius: 50%; }
        .window-dot.red { background: #ff5f57; }
        .window-dot.yellow { background: #ffbd2e; }
        .window-dot.green { background: #28c840; }
        .window-url { flex: 1; margin-left: 1rem; padding: 0.5rem 1rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: 8px; font-size: 0.75rem; color: var(--gray-500); font-family: 'JetBrains Mono', monospace; }
        .window-body { display: grid; grid-template-columns: 200px 1fr; min-height: 380px; }
        .window-sidebar { background: var(--gray-900); padding: 1.25rem; }
        .sidebar-brand { display: flex; align-items: center; gap: 0.625rem; padding: 0.5rem; margin-bottom: 1.25rem; }
        .sidebar-brand-icon { width: 32px; height: 32px; background: linear-gradient(135deg, var(--indigo-500), var(--violet-500)); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .sidebar-brand-text { font-size: 0.875rem; font-weight: 700; color: var(--white); }
        .sidebar-nav { display: flex; flex-direction: column; gap: 0.25rem; }
        .sidebar-item { display: flex; align-items: center; gap: 0.625rem; padding: 0.625rem 0.875rem; border-radius: 8px; font-size: 0.8125rem; color: var(--gray-400); transition: all 0.2s; }
        .sidebar-item:hover { color: var(--gray-200); background: rgba(255,255,255,0.05); }
        .sidebar-item.active { background: rgba(99,102,241,0.2); color: var(--indigo-300); }
        .sidebar-item svg { width: 18px; height: 18px; opacity: 0.7; }
        .window-main { background: var(--gray-50); padding: 1.5rem; }
        .main-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .main-title { font-size: 1.125rem; font-weight: 700; color: var(--gray-900); }
        .main-btn { padding: 0.5rem 0.875rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: 8px; font-size: 0.75rem; font-weight: 600; color: var(--gray-600); }
        .main-btn.primary { background: linear-gradient(135deg, var(--indigo-500), var(--violet-500)); border: none; color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem; }
        .stat-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
        .stat-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .stat-card-icon.indigo { background: var(--indigo-50); color: var(--indigo-500); }
        .stat-card-icon.emerald { background: var(--emerald-50); color: var(--emerald-500); }
        .stat-card-icon.amber { background: var(--amber-50); color: var(--amber-500); }
        .stat-card-badge { padding: 0.125rem 0.5rem; font-size: 0.625rem; font-weight: 600; border-radius: 100px; background: var(--emerald-50); color: var(--emerald-600); }
        .stat-card-value { font-size: 1.375rem; font-weight: 800; color: var(--gray-900); letter-spacing: -0.02em; }
        .stat-card-label { font-size: 0.6875rem; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; }
        .chart-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 12px; padding: 1rem; }
        .chart-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .chart-title { font-size: 0.875rem; font-weight: 600; color: var(--gray-800); }
        .chart-badge { display: flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: var(--emerald-50); border-radius: 100px; font-size: 0.625rem; font-weight: 600; color: var(--emerald-600); }
        .chart-bars { display: flex; align-items: flex-end; gap: 0.375rem; height: 80px; }
        .chart-bar { flex: 1; background: linear-gradient(180deg, var(--indigo-400), var(--indigo-500)); border-radius: 4px 4px 0 0; transition: height 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }

        /* Floating Elements */
        .floating-element { position: absolute; background: var(--white); border-radius: 14px; padding: 1rem 1.25rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1), 0 0 0 1px rgba(0,0,0,0.02); animation: float 5s ease-in-out infinite; }
        .floating-element-1 { top: 5%; right: -30px; }
        .floating-element-2 { bottom: 10%; left: -40px; animation-delay: 2s; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
        .floating-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.625rem; }
        .floating-icon.gradient-purple { background: linear-gradient(135deg, var(--indigo-500), var(--violet-500)); color: white; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
        .floating-icon.gradient-emerald { background: linear-gradient(135deg, var(--emerald-400), var(--emerald-500)); color: white; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .floating-label { font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.125rem; }
        .floating-value { font-size: 1.25rem; font-weight: 700; color: var(--gray-900); }

        /* Features */
        .features { padding: 8rem 0; }
        .section-header { text-align: center; max-width: 700px; margin: 0 auto 5rem; }
        .section-eyebrow { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.375rem 0.875rem; background: var(--indigo-50); border-radius: 100px; font-size: 0.75rem; font-weight: 700; color: var(--indigo-600); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1.25rem; }
        .section-title { font-size: 3rem; font-weight: 800; letter-spacing: -0.035em; color: var(--gray-900); margin-bottom: 1.25rem; line-height: 1.15; }
        .section-description { font-size: 1.125rem; color: var(--gray-500); line-height: 1.7; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .feature-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 20px; padding: 2rem; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .feature-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--indigo-500), var(--violet-500)); transform: scaleX(0); transform-origin: left; transition: transform 0.4s ease; }
        .feature-card:hover { border-color: var(--indigo-200); box-shadow: 0 25px 50px -12px rgba(99,102,241,0.15); transform: translateY(-8px); }
        .feature-card:hover::before { transform: scaleX(1); }
        .feature-icon-wrapper { position: relative; width: 56px; height: 56px; margin-bottom: 1.5rem; }
        .feature-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; position: relative; z-index: 1; }
        .feature-icon svg { width: 26px; height: 26px; }
        .feature-icon-glow { position: absolute; inset: -8px; border-radius: 20px; opacity: 0.3; filter: blur(12px); z-index: 0; }
        .feature-icon.indigo { background: linear-gradient(135deg, var(--indigo-500), var(--indigo-600)); }
        .feature-icon.indigo + .feature-icon-glow { background: var(--indigo-500); }
        .feature-icon.violet { background: linear-gradient(135deg, var(--violet-500), var(--violet-600)); }
        .feature-icon.violet + .feature-icon-glow { background: var(--violet-500); }
        .feature-icon.emerald { background: linear-gradient(135deg, var(--emerald-400), var(--emerald-500)); }
        .feature-icon.emerald + .feature-icon-glow { background: var(--emerald-500); }
        .feature-icon.amber { background: linear-gradient(135deg, var(--amber-400), var(--amber-500)); }
        .feature-icon.amber + .feature-icon-glow { background: var(--amber-500); }
        .feature-icon.cyan { background: linear-gradient(135deg, var(--cyan-400), var(--cyan-500)); }
        .feature-icon.cyan + .feature-icon-glow { background: var(--cyan-500); }
        .feature-icon.rose { background: linear-gradient(135deg, var(--rose-400), var(--rose-500)); }
        .feature-icon.rose + .feature-icon-glow { background: var(--rose-500); }
        .feature-icon.blue { background: linear-gradient(135deg, var(--blue-400), var(--blue-500)); }
        .feature-icon.blue + .feature-icon-glow { background: var(--blue-500); }
        .feature-icon.purple { background: linear-gradient(135deg, var(--violet-400), var(--purple-500)); }
        .feature-icon.purple + .feature-icon-glow { background: var(--purple-500); }
        .feature-title { font-size: 1.25rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.625rem; letter-spacing: -0.02em; }
        .feature-description { font-size: 0.9375rem; color: var(--gray-500); line-height: 1.65; margin-bottom: 1.25rem; }
        .feature-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .feature-tag { padding: 0.25rem 0.625rem; background: var(--gray-100); border-radius: 6px; font-size: 0.6875rem; font-weight: 600; color: var(--gray-600); }

        /* Showcase */
        .showcase { padding: 5rem 0 8rem; background: var(--gray-50); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200); }
        .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 1.5rem; }
        .bento-card { background: var(--white); border: 1px solid var(--gray-200); border-radius: 24px; padding: 2rem; overflow: hidden; transition: all 0.3s ease; }
        .bento-card:hover { box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
        .bento-card-xl { grid-column: span 8; grid-row: span 2; padding: 2.5rem; }
        .bento-card-tall { grid-column: span 4; grid-row: span 2; }
        .bento-card-wide { grid-column: span 6; }
        .bento-eyebrow { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.6875rem; font-weight: 700; color: var(--indigo-600); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.875rem; }
        .bento-title { font-size: 1.5rem; font-weight: 700; color: var(--gray-900); margin-bottom: 0.625rem; letter-spacing: -0.02em; }
        .bento-description { font-size: 0.9375rem; color: var(--gray-500); line-height: 1.6; margin-bottom: 1.5rem; }

        /* Code Block */
        .code-block { background: var(--gray-900); border-radius: 16px; overflow: hidden; }
        .code-header { display: flex; align-items: center; justify-content: space-between; padding: 0.875rem 1.25rem; background: var(--gray-800); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .code-dots { display: flex; gap: 0.375rem; }
        .code-dots span { width: 10px; height: 10px; border-radius: 50%; }
        .code-dots span:nth-child(1) { background: #ff5f57; }
        .code-dots span:nth-child(2) { background: #ffbd2e; }
        .code-dots span:nth-child(3) { background: #28c840; }
        .code-filename { font-family: 'JetBrains Mono', monospace; font-size: 0.75rem; color: var(--gray-400); }
        .code-content { padding: 1.5rem; font-family: 'JetBrains Mono', monospace; font-size: 0.8125rem; line-height: 1.9; overflow-x: auto; white-space: pre; color: #e5e7eb; margin: 0; }
        .code-content .comment { color: #6b7280; }
        .code-content .keyword { color: #c084fc; }
        .code-content .function { color: #67e8f9; }
        .code-content .string { color: #86efac; }
        .code-content .variable { color: #fcd34d; }
        .code-content .operator { color: #f472b6; }

        /* Stats Bento */
        .bento-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .bento-stat { background: var(--gray-50); border-radius: 14px; padding: 1.25rem; text-align: center; transition: all 0.3s; }
        .bento-stat:hover { background: var(--indigo-50); }
        .bento-stat-value { font-size: 2.25rem; font-weight: 800; color: var(--gray-900); letter-spacing: -0.03em; line-height: 1.2; }
        .bento-stat-value span { color: var(--indigo-500); }
        .bento-stat-label { font-size: 0.8125rem; color: var(--gray-500); font-weight: 500; }

        /* Theme Cards */
        .theme-cards { display: flex; gap: 1rem; }
        .theme-card { flex: 1; border-radius: 14px; overflow: hidden; border: 2px solid var(--gray-200); transition: all 0.3s; cursor: pointer; }
        .theme-card:hover { border-color: var(--indigo-300); transform: translateY(-4px); }
        .theme-card-preview { height: 100px; display: flex; align-items: center; justify-content: center; }
        .theme-card.dark .theme-card-preview { background: linear-gradient(135deg, #0f172a, #1e1b4b); }
        .theme-card.light .theme-card-preview { background: linear-gradient(135deg, #f8fafc, #e2e8f0); }
        .theme-card-label { padding: 0.875rem; font-size: 0.8125rem; font-weight: 600; color: var(--gray-700); text-align: center; background: var(--gray-50); }

        /* Field Tags */
        .field-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .field-tag { padding: 0.5rem 1rem; background: var(--gray-100); border-radius: 100px; font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); transition: all 0.2s; }
        .field-tag:hover { background: var(--indigo-100); color: var(--indigo-700); transform: translateY(-2px); }

        /* CTA */
        .cta { padding: 8rem 0; }
        .cta-card { background: linear-gradient(135deg, var(--gray-900) 0%, #1a1a2e 50%, var(--gray-900) 100%); border-radius: 32px; padding: 5rem; text-align: center; position: relative; overflow: hidden; }
        .cta-card::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at 20% 20%, rgba(99,102,241,0.2) 0%, transparent 50%), radial-gradient(ellipse at 80% 80%, rgba(139,92,246,0.15) 0%, transparent 50%); }
        .cta-card::after { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 40px 40px; }
        .cta-content { position: relative; z-index: 1; }
        .cta h2 { font-size: 3.25rem; font-weight: 800; color: var(--white); letter-spacing: -0.035em; margin-bottom: 1.25rem; line-height: 1.15; }
        .cta p { font-size: 1.25rem; color: var(--gray-400); margin-bottom: 3rem; max-width: 520px; margin-left: auto; margin-right: auto; }
        .cta-buttons { display: flex; justify-content: center; gap: 1rem; }
        .cta .btn-primary { background: linear-gradient(135deg, var(--indigo-400), var(--violet-400)); box-shadow: 0 8px 30px rgba(99,102,241,0.4); }
        .cta .btn-secondary { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: var(--white); }
        .cta .btn-secondary:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25