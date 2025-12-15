<?php
/**
 * API Keys Management - VoidForge CMS
 * 
 * @package VoidForge
 * @since 0.2.1
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/user.php';
require_once CMS_ROOT . '/includes/post.php';
require_once CMS_ROOT . '/includes/media.php';
require_once CMS_ROOT . '/includes/plugin.php';
require_once CMS_ROOT . '/includes/rest-api.php';

Post::init();
Plugin::init();
RestAPI::init();

User::startSession();
User::requireRole('admin');

$currentPage = 'api-keys';
$pageTitle = 'API Keys';

$currentUser = User::current();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $expiresAt = $_POST['expires_at'] ?? null;
            
            if (empty($name)) {
                $message = 'API key name is required.';
                $messageType = 'error';
            } else {
                $expiresAt = !empty($expiresAt) ? $expiresAt . ' 23:59:59' : null;
                $result = RestAPI::generateApiKey($currentUser['id'], $name, $expiresAt);
                $_SESSION['new_api_key'] = $result;
                $message = 'API key created successfully.';
                $messageType = 'success';
            }
            break;

        case 'revoke':
            $keyId = (int)($_POST['key_id'] ?? 0);
            if ($keyId && RestAPI::revokeApiKey($keyId, $currentUser['id'])) {
                $message = 'API key revoked.';
                $messageType = 'success';
            } else {
                $message = 'Failed to revoke API key.';
                $messageType = 'error';
            }
            break;

        case 'toggle':
            $keyId = (int)($_POST['key_id'] ?? 0);
            if ($keyId && RestAPI::toggleApiKey($keyId, $currentUser['id'])) {
                $message = 'API key status updated.';
                $messageType = 'success';
            } else {
                $message = 'Failed to update API key.';
                $messageType = 'error';
            }
            break;
    }
}

// Get user's API keys
$apiKeys = RestAPI::getUserApiKeys($currentUser['id']);

// Check for newly created key
$newKey = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['new_api_key']);

// Count stats
$totalKeys = count($apiKeys);
$activeKeys = count(array_filter($apiKeys, fn($k) => $k['is_active'] && (!$k['expires_at'] || strtotime($k['expires_at']) > time())));
$expiredKeys = count(array_filter($apiKeys, fn($k) => $k['expires_at'] && strtotime($k['expires_at']) < time()));

include ADMIN_PATH . '/includes/header.php';
?>

<style>
/* API Keys Page Styles */
.api-keys-page { padding: 0; }

.api-keys-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.api-keys-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.api-keys-header h1 svg {
    color: var(--forge-primary);
}

/* Stats Cards */
.api-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.stat-card .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}
.stat-card .stat-label {
    font-size: 0.8125rem;
    color: var(--text-muted);
    font-weight: 500;
}
.stat-card.active .stat-value { color: var(--forge-success); }
.stat-card.expired .stat-value { color: var(--text-muted); }

/* New Key Alert */
.new-key-alert {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(139, 92, 246, 0.08) 100%);
    border: 1px solid var(--forge-primary);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.new-key-alert-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    color: var(--forge-primary);
    font-weight: 600;
    font-size: 1rem;
}
.new-key-alert-header svg { flex-shrink: 0; }
.new-key-alert p {
    color: var(--text-secondary);
    font-size: 0.875rem;
    margin-bottom: 1rem;
}
.credential-row {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 1rem;
}
.credential-row:last-of-type { margin-bottom: 0; }
.credential-row label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}
.credential-input-group {
    display: flex;
    gap: 0.5rem;
}
.credential-input-group input {
    flex: 1;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-card);
    font-family: 'SF Mono', 'Consolas', monospace;
    font-size: 0.8125rem;
    color: var(--text-primary);
}
.btn-copy {
    padding: 0.625rem 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-secondary);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.btn-copy:hover {
    border-color: var(--forge-primary);
    color: var(--forge-primary);
}
.btn-copy.copied {
    background: var(--forge-success);
    border-color: var(--forge-success);
    color: white;
}
.usage-example {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 1rem;
    margin-top: 1rem;
}
.usage-example strong {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}
.usage-example pre {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--text-secondary);
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

/* Main Card */
.api-keys-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
}
.api-keys-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card-header);
}
.api-keys-card-header h2 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
}

/* Table */
.api-keys-table {
    width: 100%;
    border-collapse: collapse;
}
.api-keys-table th {
    text-align: left;
    padding: 0.75rem 1.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    background: var(--bg-card-header);
    border-bottom: 1px solid var(--border-color);
}
.api-keys-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.875rem;
    vertical-align: middle;
}
.api-keys-table tr:last-child td { border-bottom: none; }
.api-keys-table tr:hover td { background: var(--bg-hover); }

.key-name {
    font-weight: 600;
    color: var(--text-primary);
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}
.status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--forge-success);
}
.status-badge.disabled {
    background: rgba(100, 116, 139, 0.1);
    color: var(--text-muted);
}
.status-badge.expired {
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
}
.status-badge svg { width: 12px; height: 12px; }

.meta-text {
    color: var(--text-muted);
    font-size: 0.8125rem;
}

/* Actions */
.key-actions {
    display: flex;
    gap: 0.375rem;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.15s;
}
.btn-action:hover {
    border-color: var(--forge-primary);
    color: var(--forge-primary);
}
.btn-action.danger:hover {
    border-color: var(--forge-danger);
    color: var(--forge-danger);
    background: rgba(239, 68, 68, 0.05);
}
.btn-action svg { width: 16px; height: 16px; }

/* Empty State */
.empty-state {
    padding: 4rem 2rem;
    text-align: center;
}
.empty-state svg {
    width: 48px;
    height: 48px;
    color: var(--text-muted);
    opacity: 0.5;
    margin-bottom: 1rem;
}
.empty-state p {
    color: var(--text-muted);
    margin: 0;
}

/* Docs Section */
.api-docs {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    margin-top: 1.5rem;
    overflow: hidden;
}
.api-docs-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-card-header);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.api-docs-header h2 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.api-docs-header svg.chevron {
    transition: transform 0.2s;
}
.api-docs.collapsed .api-docs-header svg.chevron {
    transform: rotate(-90deg);
}
.api-docs-body {
    padding: 1.5rem;
}
.api-docs.collapsed .api-docs-body { display: none; }

.api-docs h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.75rem;
}
.api-docs h3:not(:first-of-type) {
    margin-top: 1.5rem;
}
.api-docs p {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin: 0 0 0.75rem;
    line-height: 1.6;
}
.api-docs code {
    background: var(--bg-body);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-size: 0.8125rem;
    font-family: 'SF Mono', 'Consolas', monospace;
    color: var(--forge-primary);
}
.api-docs pre {
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 1rem;
    margin: 0.75rem 0;
    overflow-x: auto;
    font-size: 0.8125rem;
}
.api-docs pre code {
    background: none;
    padding: 0;
    color: var(--text-secondary);
}

.endpoints-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
    margin: 0.75rem 0;
}
.endpoints-table th {
    text-align: left;
    padding: 0.625rem 0.75rem;
    background: var(--bg-body);
    border: 1px solid var(--border-color);
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--text-muted);
}
.endpoints-table td {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}
.endpoints-table td code {
    font-size: 0.75rem;
}

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}
.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}
.modal-content {
    background: var(--bg-card);
    border-radius: var(--border-radius-lg);
    width: 90%;
    max-width: 440px;
    box-shadow: var(--shadow-xl);
    transform: translateY(20px) scale(0.95);
    transition: all 0.2s;
}
.modal-overlay.active .modal-content {
    transform: translateY(0) scale(1);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
}
.modal-header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
}
.modal-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s;
}
.modal-close:hover {
    background: var(--bg-body);
    color: var(--text-primary);
}
.modal-body { padding: 1.5rem; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card-header);
}

.form-group { margin-bottom: 1.25rem; }
.form-group:last-child { margin-bottom: 0; }
.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}
.form-group label .required { color: var(--forge-danger); }
.form-group input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-input);
    font-size: 0.875rem;
    color: var(--text-primary);
    transition: border-color 0.15s;
}
.form-group input:focus {
    outline: none;
    border-color: var(--forge-primary);
}
.form-group .form-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.375rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
}
.btn-primary {
    background: linear-gradient(135deg, var(--forge-primary), var(--forge-secondary));
    color: white;
}
.btn-primary:hover { opacity: 0.9; }
.btn-secondary {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}
.btn-secondary:hover {
    border-color: var(--text-muted);
    color: var(--text-primary);
}
.btn svg { width: 16px; height: 16px; }

/* Alert */
.alert {
    padding: 0.875rem 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.alert svg { flex-shrink: 0; width: 18px; height: 18px; }
.alert.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--forge-success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}
.alert.error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--forge-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

@media (max-width: 768px) {
    .api-stats { grid-template-columns: repeat(3, 1fr); }
    .api-keys-table th:nth-child(4),
    .api-keys-table td:nth-child(4),
    .api-keys-table th:nth-child(5),
    .api-keys-table td:nth-child(5) { display: none; }
}
</style>

<div class="api-keys-page">
    
    <!-- Header -->
    <div class="api-keys-header">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            API Keys
        </h1>
        <button type="button" class="btn btn-primary" id="createKeyBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create Key
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>">
            <?php if ($messageType === 'success'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php endif; ?>
            <?= esc($message) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="api-stats">
        <div class="stat-card">
            <span class="stat-value"><?= $totalKeys ?></span>
            <span class="stat-label">Total Keys</span>
        </div>
        <div class="stat-card active">
            <span class="stat-value"><?= $activeKeys ?></span>
            <span class="stat-label">Active</span>
        </div>
        <div class="stat-card expired">
            <span class="stat-value"><?= $expiredKeys ?></span>
            <span class="stat-label">Expired</span>
        </div>
    </div>

    <?php if ($newKey): ?>
        <!-- New Key Alert -->
        <div class="new-key-alert">
            <div class="new-key-alert-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Save Your Credentials Now
            </div>
            <p>These credentials will only be shown once. Store them securely.</p>
            
            <div class="credential-row">
                <label>API Key</label>
                <div class="credential-input-group">
                    <input type="text" value="<?= esc($newKey['api_key']) ?>" readonly id="newApiKey">
                    <button type="button" class="btn-copy" onclick="copyText('newApiKey', this)">Copy</button>
                </div>
            </div>
            
            <div class="credential-row">
                <label>API Secret</label>
                <div class="credential-input-group">
                    <input type="text" value="<?= esc($newKey['api_secret']) ?>" readonly id="newApiSecret">
                    <button type="button" class="btn-copy" onclick="copyText('newApiSecret', this)">Copy</button>
                </div>
            </div>

            <div class="usage-example">
                <strong>Usage Example</strong>
                <pre><code>curl -H "Authorization: Bearer <?= esc($newKey['api_key']) ?>" \
     <?= SITE_URL ?>/api/v1/posts</code></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- Keys Table -->
    <div class="api-keys-card">
        <div class="api-keys-card-header">
            <h2>Your API Keys</h2>
        </div>
        
        <?php if (empty($apiKeys)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                <p>No API keys yet. Create one to get started.</p>
            </div>
        <?php else: ?>
            <table class="api-keys-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Last Used</th>
                        <th>Expires</th>
                        <th>Created</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                        <?php 
                            $isExpired = $key['expires_at'] && strtotime($key['expires_at']) < time();
                            $statusClass = $isExpired ? 'expired' : ($key['is_active'] ? 'active' : 'disabled');
                            $statusText = $isExpired ? 'Expired' : ($key['is_active'] ? 'Active' : 'Disabled');
                        ?>
                        <tr>
                            <td><span class="key-name"><?= esc($key['name']) ?></span></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?php if ($statusClass === 'active'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <?php elseif ($statusClass === 'expired'): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                                    <?php endif; ?>
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td>
                                <span class="meta-text">
                                    <?= $key['last_used_at'] ? formatDatetime($key['last_used_at']) : 'Never' ?>
                                </span>
                            </td>
                            <td>
                                <span class="meta-text">
                                    <?= $key['expires_at'] ? formatDate($key['expires_at']) : 'Never' ?>
                                </span>
                            </td>
                            <td>
                                <span class="meta-text"><?= formatDate($key['created_at']) ?></span>
                            </td>
                            <td>
                                <div class="key-actions">
                                    <form method="post" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                        <button type="submit" class="btn-action" title="<?= $key['is_active'] ? 'Disable' : 'Enable' ?>">
                                            <?php if ($key['is_active']): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Revoke this API key? This cannot be undone.');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="revoke">
                                        <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                        <button type="submit" class="btn-action danger" title="Revoke">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- API Docs -->
    <div class="api-docs" id="apiDocs">
        <div class="api-docs-header">
            <h2>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                API Documentation
            </h2>
            <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </div>
        <div class="api-docs-body">
            <h3>Authentication</h3>
            <p>Include your API key in the Authorization header:</p>
            <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>

            <h3>Base URL</h3>
            <pre><code><?= SITE_URL ?>/api/v1</code></pre>

            <h3>Endpoints</h3>
            <table class="endpoints-table">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>Methods</th>
                        <th>Auth</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td><code>/posts</code></td><td>GET, POST</td><td>POST</td></tr>
                    <tr><td><code>/posts/{id}</code></td><td>GET, PUT, DELETE</td><td>PUT/DELETE</td></tr>
                    <tr><td><code>/pages</code></td><td>GET, POST</td><td>POST</td></tr>
                    <tr><td><code>/pages/{id}</code></td><td>GET, PUT, DELETE</td><td>PUT/DELETE</td></tr>
                    <tr><td><code>/media</code></td><td>GET, POST</td><td>POST</td></tr>
                    <tr><td><code>/media/{id}</code></td><td>GET, PUT, DELETE</td><td>PUT/DELETE</td></tr>
                    <tr><td><code>/users</code></td><td>GET, POST</td><td>Yes (admin)</td></tr>
                    <tr><td><code>/users/me</code></td><td>GET</td><td>Yes</td></tr>
                    <tr><td><code>/comments</code></td><td>GET, POST</td><td>No</td></tr>
                    <tr><td><code>/taxonomies</code></td><td>GET</td><td>No</td></tr>
                    <tr><td><code>/taxonomies/{tax}/terms</code></td><td>GET, POST</td><td>POST</td></tr>
                    <tr><td><code>/menus</code></td><td>GET, POST</td><td>POST</td></tr>
                    <tr><td><code>/menus/location/{loc}</code></td><td>GET</td><td>No</td></tr>
                    <tr><td><code>/search?q=</code></td><td>GET</td><td>No</td></tr>
                    <tr><td><code>/settings</code></td><td>GET, PUT</td><td>Yes (admin)</td></tr>
                    <tr><td><code>/info</code></td><td>GET</td><td>No</td></tr>
                </tbody>
            </table>

            <h3>Query Parameters</h3>
            <p>Collection endpoints support: <code>page</code>, <code>per_page</code>, <code>orderby</code>, <code>order</code>, <code>search</code>, <code>status</code></p>

            <h3>Rate Limits</h3>
            <p>Read operations: <strong>1000/hour</strong> · Write operations: <strong>100/hour</strong></p>
        </div>
    </div>

</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create API Key</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="name">Key Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" placeholder="e.g., Mobile App, Website Integration" required>
                    <p class="form-hint">A descriptive name to identify this key</p>
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Expiration Date</label>
                    <input type="date" id="expires_at" name="expires_at" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    <p class="form-hint">Leave empty for a key that never expires</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="modalCancelBtn">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Key</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('createModal');
    const createBtn = document.getElementById('createKeyBtn');
    const closeBtn = document.getElementById('modalCloseBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');
    const nameInput = document.getElementById('name');
    const apiDocs = document.getElementById('apiDocs');
    
    function openModal() {
        modal.classList.add('active');
        setTimeout(function() { nameInput.focus(); }, 100);
    }
    
    function closeModal() {
        modal.classList.remove('active');
    }
    
    // Button event listeners
    if (createBtn) createBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    // Close modal on overlay click
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }
    
    // Close modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
    
    // Toggle docs
    if (apiDocs) {
        var docsHeader = apiDocs.querySelector('.api-docs-header');
        if (docsHeader) {
            docsHeader.addEventListener('click', function() {
                apiDocs.classList.toggle('collapsed');
            });
        }
    }
    
    // Copy functionality
    window.copyText = function(inputId, btn) {
        var input = document.getElementById(inputId);
        input.select();
        input.setSelectionRange(0, 99999);
        
        try {
            document.execCommand('copy');
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(function() {
                btn.textContent = 'Copy';
                btn.classList.remove('copied');
            }, 2000);
        } catch (err) {
            alert('Failed to copy');
        }
    };
});
</script>

<?php include ADMIN_PATH . '/includes/footer.php'; ?>
