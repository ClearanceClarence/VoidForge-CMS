/**
 * Anvil Block Editor - VoidForge CMS
 * A modular block-based content editor
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // =========================================================================
    // Anvil Editor Class
    // =========================================================================
    
    class AnvilEditor {
        constructor(container, options = {}) {
            this.container = container;
            this.options = {
                blocks: options.blocks || {},
                categories: options.categories || {},
                initialContent: options.initialContent || [],
                onChange: options.onChange || (() => {}),
                mediaLibrary: options.mediaLibrary || null,
                ...options
            };
            
            this.blocks = []; // Current blocks
            this.selectedBlockId = null;
            this.clipboard = null;
            this.history = [];
            this.historyIndex = -1;
            this.maxHistory = 50;
            
            this.init();
        }
        
        init() {
            this.render();
            this.bindEvents();
            this.loadContent(this.options.initialContent);
            this.initSortable();
        }
        
        // =====================================================================
        // Rendering
        // =====================================================================
        
        render() {
            this.container.innerHTML = `
                <div class="anvil-editor">
                    <div class="anvil-toolbar">
                        <div class="anvil-toolbar-left">
                            <button type="button" class="anvil-toolbar-btn" data-action="undo" title="Undo (Ctrl+Z)" disabled>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 7v6h6"></path>
                                    <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"></path>
                                </svg>
                            </button>
                            <button type="button" class="anvil-toolbar-btn" data-action="redo" title="Redo (Ctrl+Y)" disabled>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 7v6h-6"></path>
                                    <path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="anvil-toolbar-center">
                            <span class="anvil-block-count">${this.blocks.length} blocks</span>
                        </div>
                        <div class="anvil-toolbar-right">
                            <button type="button" class="anvil-toolbar-btn anvil-toolbar-btn-primary" data-action="add-block" title="Add Block">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Add Block</span>
                            </button>
                        </div>
                    </div>
                    <div class="anvil-canvas">
                        <div class="anvil-blocks"></div>
                        <div class="anvil-empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="12" y1="8" x2="12" y2="16"></line>
                                <line x1="8" y1="12" x2="16" y2="12"></line>
                            </svg>
                            <h3>Start building your content</h3>
                            <p>Click "Add Block" or press Enter to add your first block</p>
                            <button type="button" class="anvil-btn anvil-btn-primary" data-action="add-first-block">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Block
                            </button>
                        </div>
                    </div>
                </div>
                <div class="anvil-block-library">
                    <div class="anvil-library-header">
                        <h3>Add Block</h3>
                        <button type="button" class="anvil-library-close" data-action="close-library">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="anvil-library-search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" placeholder="Search blocks..." class="anvil-library-search-input">
                    </div>
                    <div class="anvil-library-content"></div>
                </div>
                <div class="anvil-block-settings">
                    <div class="anvil-settings-header">
                        <h3>Block Settings</h3>
                        <button type="button" class="anvil-settings-close" data-action="close-settings">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="anvil-settings-content"></div>
                </div>
                <div class="anvil-overlay"></div>
            `;
            
            // Cache elements
            this.elements = {
                editor: this.container.querySelector('.anvil-editor'),
                toolbar: this.container.querySelector('.anvil-toolbar'),
                canvas: this.container.querySelector('.anvil-canvas'),
                blocksContainer: this.container.querySelector('.anvil-blocks'),
                emptyState: this.container.querySelector('.anvil-empty-state'),
                library: this.container.querySelector('.anvil-block-library'),
                libraryContent: this.container.querySelector('.anvil-library-content'),
                librarySearch: this.container.querySelector('.anvil-library-search-input'),
                settings: this.container.querySelector('.anvil-block-settings'),
                settingsContent: this.container.querySelector('.anvil-settings-content'),
                overlay: this.container.querySelector('.anvil-overlay'),
                blockCount: this.container.querySelector('.anvil-block-count'),
                undoBtn: this.container.querySelector('[data-action="undo"]'),
                redoBtn: this.container.querySelector('[data-action="redo"]'),
            };
            
            this.renderLibrary();
        }
        
        renderLibrary() {
            const categories = this.options.categories;
            const blocks = this.options.blocks;
            
            let html = '';
            
            // Group blocks by category
            const grouped = {};
            for (const [slug, cat] of Object.entries(categories)) {
                grouped[slug] = { category: cat, blocks: [] };
            }
            
            for (const [name, block] of Object.entries(blocks)) {
                const cat = block.category || 'text';
                if (!grouped[cat]) {
                    grouped[cat] = { category: { label: cat }, blocks: [] };
                }
                grouped[cat].blocks.push({ name, ...block });
            }
            
            for (const [slug, group] of Object.entries(grouped)) {
                if (group.blocks.length === 0) continue;
                
                html += `
                    <div class="anvil-library-category" data-category="${slug}">
                        <div class="anvil-library-category-header">${group.category.label}</div>
                        <div class="anvil-library-blocks">
                            ${group.blocks.map(block => `
                                <button type="button" class="anvil-library-block" data-block="${block.name}">
                                    <span class="anvil-library-block-icon">${this.getIcon(block.icon)}</span>
                                    <span class="anvil-library-block-name">${block.label}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            this.elements.libraryContent.innerHTML = html;
        }
        
        renderBlocks() {
            if (this.blocks.length === 0) {
                this.elements.blocksContainer.innerHTML = '';
                this.elements.emptyState.style.display = 'flex';
                this.elements.blockCount.textContent = '0 blocks';
                return;
            }
            
            this.elements.emptyState.style.display = 'none';
            this.elements.blockCount.textContent = `${this.blocks.length} block${this.blocks.length !== 1 ? 's' : ''}`;
            
            const html = this.blocks.map((block, index) => this.renderBlockEditor(block, index)).join('');
            this.elements.blocksContainer.innerHTML = html;
            
            // Re-init sortable
            this.initSortable();
        }
        
        renderBlockEditor(block, index) {
            const blockDef = this.options.blocks[block.type];
            if (!blockDef) return '';
            
            const isSelected = block.id === this.selectedBlockId;
            
            return `
                <div class="anvil-block ${isSelected ? 'is-selected' : ''}" data-block-id="${block.id}" data-block-type="${block.type}">
                    <div class="anvil-block-toolbar">
                        <div class="anvil-block-toolbar-left">
                            <span class="anvil-block-drag-handle" title="Drag to reorder">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="5" r="1"></circle>
                                    <circle cx="9" cy="12" r="1"></circle>
                                    <circle cx="9" cy="19" r="1"></circle>
                                    <circle cx="15" cy="5" r="1"></circle>
                                    <circle cx="15" cy="12" r="1"></circle>
                                    <circle cx="15" cy="19" r="1"></circle>
                                </svg>
                            </span>
                            <span class="anvil-block-type">
                                ${this.getIcon(blockDef.icon)}
                                <span>${blockDef.label}</span>
                            </span>
                        </div>
                        <div class="anvil-block-toolbar-right">
                            <button type="button" class="anvil-block-btn" data-action="move-up" title="Move up" ${index === 0 ? 'disabled' : ''}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="18 15 12 9 6 15"></polyline>
                                </svg>
                            </button>
                            <button type="button" class="anvil-block-btn" data-action="move-down" title="Move down" ${index === this.blocks.length - 1 ? 'disabled' : ''}>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </button>
                            <button type="button" class="anvil-block-btn" data-action="duplicate" title="Duplicate">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                            <button type="button" class="anvil-block-btn" data-action="settings" title="Settings">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"></circle>
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                                </svg>
                            </button>
                            <button type="button" class="anvil-block-btn anvil-block-btn-danger" data-action="delete" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="anvil-block-content">
                        ${this.renderBlockContent(block)}
                    </div>
                    <div class="anvil-block-inserter">
                        <button type="button" class="anvil-block-inserter-btn" data-action="insert-after" title="Add block">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }
        
        renderBlockContent(block) {
            const attrs = block.attributes || {};
            
            switch (block.type) {
                case 'paragraph':
                    return `
                        <div class="anvil-paragraph-editor" 
                             contenteditable="true" 
                             data-placeholder="Start writing..."
                             data-attr="content">${attrs.content || ''}</div>
                    `;
                    
                case 'heading':
                    const level = attrs.level || 2;
                    return `
                        <div class="anvil-heading-wrapper">
                            <select class="anvil-heading-level" data-attr="level">
                                ${[1,2,3,4,5,6].map(l => `<option value="${l}" ${l === level ? 'selected' : ''}>H${l}</option>`).join('')}
                            </select>
                            <div class="anvil-heading-editor anvil-heading-h${level}" 
                                 contenteditable="true" 
                                 data-placeholder="Heading"
                                 data-attr="content">${attrs.content || ''}</div>
                        </div>
                    `;
                    
                case 'list':
                    const items = attrs.items || [''];
                    const ordered = attrs.ordered || false;
                    return `
                        <div class="anvil-list-wrapper">
                            <div class="anvil-list-controls">
                                <button type="button" class="anvil-list-type-btn ${!ordered ? 'active' : ''}" data-list-type="unordered" title="Bulleted list">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <circle cx="3" cy="6" r="1" fill="currentColor"></circle>
                                        <circle cx="3" cy="12" r="1" fill="currentColor"></circle>
                                        <circle cx="3" cy="18" r="1" fill="currentColor"></circle>
                                    </svg>
                                </button>
                                <button type="button" class="anvil-list-type-btn ${ordered ? 'active' : ''}" data-list-type="ordered" title="Numbered list">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="10" y1="6" x2="21" y2="6"></line>
                                        <line x1="10" y1="12" x2="21" y2="12"></line>
                                        <line x1="10" y1="18" x2="21" y2="18"></line>
                                        <path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="anvil-list-items" data-ordered="${ordered}">
                                ${items.map((item, i) => `
                                    <div class="anvil-list-item" data-index="${i}">
                                        <span class="anvil-list-marker">${ordered ? (i + 1) + '.' : '•'}</span>
                                        <div class="anvil-list-item-content" contenteditable="true" data-attr="items" data-index="${i}">${item}</div>
                                        <button type="button" class="anvil-list-item-remove" data-action="remove-list-item" title="Remove">×</button>
                                    </div>
                                `).join('')}
                            </div>
                            <button type="button" class="anvil-list-add" data-action="add-list-item">+ Add item</button>
                        </div>
                    `;
                    
                case 'quote':
                    return `
                        <div class="anvil-quote-wrapper">
                            <div class="anvil-quote-content" 
                                 contenteditable="true" 
                                 data-placeholder="Write quote..."
                                 data-attr="content">${attrs.content || ''}</div>
                            <input type="text" class="anvil-quote-citation" 
                                   placeholder="Citation (optional)"
                                   data-attr="citation"
                                   value="${this.escapeAttr(attrs.citation || '')}">
                        </div>
                    `;
                    
                case 'code':
                    return `
                        <div class="anvil-code-wrapper">
                            <select class="anvil-code-language" data-attr="language">
                                <option value="">Plain text</option>
                                <option value="html" ${attrs.language === 'html' ? 'selected' : ''}>HTML</option>
                                <option value="css" ${attrs.language === 'css' ? 'selected' : ''}>CSS</option>
                                <option value="javascript" ${attrs.language === 'javascript' ? 'selected' : ''}>JavaScript</option>
                                <option value="php" ${attrs.language === 'php' ? 'selected' : ''}>PHP</option>
                                <option value="python" ${attrs.language === 'python' ? 'selected' : ''}>Python</option>
                                <option value="sql" ${attrs.language === 'sql' ? 'selected' : ''}>SQL</option>
                                <option value="json" ${attrs.language === 'json' ? 'selected' : ''}>JSON</option>
                                <option value="bash" ${attrs.language === 'bash' ? 'selected' : ''}>Bash</option>
                            </select>
                            <textarea class="anvil-code-editor" 
                                      data-attr="content"
                                      placeholder="Enter code..."
                                      spellcheck="false">${this.escapeHtml(attrs.content || '')}</textarea>
                        </div>
                    `;
                    
                case 'image':
                    const hasImage = attrs.url || attrs.mediaId;
                    return `
                        <div class="anvil-image-wrapper ${hasImage ? 'has-image' : ''}">
                            ${hasImage ? `
                                <figure class="anvil-image-preview">
                                    <img src="${attrs.url || ''}" alt="${this.escapeAttr(attrs.alt || '')}">
                                    ${attrs.caption ? `<figcaption>${this.escapeHtml(attrs.caption)}</figcaption>` : ''}
                                </figure>
                                <div class="anvil-image-actions">
                                    <button type="button" class="anvil-btn anvil-btn-secondary" data-action="change-image">Change</button>
                                    <button type="button" class="anvil-btn anvil-btn-secondary" data-action="remove-image">Remove</button>
                                </div>
                                <input type="text" class="anvil-image-caption" placeholder="Caption (optional)" data-attr="caption" value="${this.escapeAttr(attrs.caption || '')}">
                                <input type="text" class="anvil-image-alt" placeholder="Alt text" data-attr="alt" value="${this.escapeAttr(attrs.alt || '')}">
                            ` : `
                                <div class="anvil-image-placeholder" data-action="select-image">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <span>Click to select image</span>
                                </div>
                            `}
                        </div>
                    `;
                    
                case 'gallery':
                    const images = attrs.images || [];
                    const columns = attrs.columns || 3;
                    return `
                        <div class="anvil-gallery-wrapper">
                            <div class="anvil-gallery-controls">
                                <label>Columns: 
                                    <select data-attr="columns">
                                        ${[2,3,4,5,6].map(c => `<option value="${c}" ${c === columns ? 'selected' : ''}>${c}</option>`).join('')}
                                    </select>
                                </label>
                            </div>
                            <div class="anvil-gallery-grid" style="grid-template-columns: repeat(${columns}, 1fr);">
                                ${images.map((img, i) => `
                                    <div class="anvil-gallery-item" data-index="${i}">
                                        <img src="${img.url || ''}" alt="${this.escapeAttr(img.alt || '')}">
                                        <button type="button" class="anvil-gallery-item-remove" data-action="remove-gallery-image" data-index="${i}">×</button>
                                    </div>
                                `).join('')}
                                <div class="anvil-gallery-add" data-action="add-gallery-images">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>Add images</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                case 'video':
                    const hasVideo = attrs.url || attrs.mediaId;
                    return `
                        <div class="anvil-video-wrapper ${hasVideo ? 'has-video' : ''}">
                            ${hasVideo ? `
                                <video src="${attrs.url || ''}" ${attrs.controls !== false ? 'controls' : ''}></video>
                                <div class="anvil-video-actions">
                                    <button type="button" class="anvil-btn anvil-btn-secondary" data-action="change-video">Change</button>
                                    <button type="button" class="anvil-btn anvil-btn-secondary" data-action="remove-video">Remove</button>
                                </div>
                            ` : `
                                <div class="anvil-video-placeholder" data-action="select-video">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                        <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                                    </svg>
                                    <span>Click to select video</span>
                                </div>
                            `}
                        </div>
                    `;
                    
                case 'spacer':
                    const height = attrs.height || 50;
                    return `
                        <div class="anvil-spacer-wrapper">
                            <div class="anvil-spacer-preview" style="height: ${height}px;">
                                <span class="anvil-spacer-label">${height}px</span>
                            </div>
                            <input type="range" class="anvil-spacer-slider" min="10" max="300" value="${height}" data-attr="height">
                        </div>
                    `;
                    
                case 'separator':
                    const style = attrs.style || 'default';
                    return `
                        <div class="anvil-separator-wrapper">
                            <hr class="anvil-separator anvil-separator-${style}">
                            <div class="anvil-separator-controls">
                                <select data-attr="style">
                                    <option value="default" ${style === 'default' ? 'selected' : ''}>Default</option>
                                    <option value="wide" ${style === 'wide' ? 'selected' : ''}>Wide</option>
                                    <option value="dots" ${style === 'dots' ? 'selected' : ''}>Dots</option>
                                </select>
                            </div>
                        </div>
                    `;
                    
                case 'button':
                    return `
                        <div class="anvil-button-wrapper">
                            <input type="text" class="anvil-button-text" placeholder="Button text" data-attr="text" value="${this.escapeAttr(attrs.text || 'Click me')}">
                            <input type="text" class="anvil-button-url" placeholder="Link URL" data-attr="url" value="${this.escapeAttr(attrs.url || '#')}">
                            <div class="anvil-button-preview">
                                <a href="#" class="anvil-btn anvil-btn-${attrs.style || 'primary'}">${this.escapeHtml(attrs.text || 'Click me')}</a>
                            </div>
                        </div>
                    `;
                    
                case 'html':
                    return `
                        <div class="anvil-html-wrapper">
                            <div class="anvil-html-tabs">
                                <button type="button" class="anvil-html-tab active" data-tab="edit">Edit</button>
                                <button type="button" class="anvil-html-tab" data-tab="preview">Preview</button>
                            </div>
                            <textarea class="anvil-html-editor" data-attr="content" placeholder="Enter HTML...">${this.escapeHtml(attrs.content || '')}</textarea>
                            <div class="anvil-html-preview" style="display: none;"></div>
                        </div>
                    `;
                    
                case 'embed':
                    return `
                        <div class="anvil-embed-wrapper">
                            <input type="text" class="anvil-embed-url" placeholder="Enter embed URL (YouTube, Vimeo, etc.)" data-attr="url" value="${this.escapeAttr(attrs.url || '')}">
                            ${attrs.url ? `<div class="anvil-embed-preview">${this.getEmbedPreview(attrs.url)}</div>` : ''}
                        </div>
                    `;
                    
                case 'columns':
                    const columnCount = attrs.columnCount || 2;
                    const cols = attrs.columns || Array(columnCount).fill([]);
                    return `
                        <div class="anvil-columns-wrapper">
                            <div class="anvil-columns-controls">
                                <label>Columns: 
                                    <select data-attr="columnCount">
                                        ${[2,3,4].map(c => `<option value="${c}" ${c === columnCount ? 'selected' : ''}>${c}</option>`).join('')}
                                    </select>
                                </label>
                            </div>
                            <div class="anvil-columns-grid" style="grid-template-columns: repeat(${columnCount}, 1fr);">
                                ${cols.slice(0, columnCount).map((col, i) => `
                                    <div class="anvil-column" data-column="${i}">
                                        <div class="anvil-column-inner">
                                            ${col.length > 0 ? col.map(b => `<div class="anvil-column-block">${b.type || 'Block'}</div>`).join('') : '<p class="anvil-column-empty">Drop blocks here</p>'}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                    
                case 'table':
                    const rows = attrs.rows || [['', ''], ['', '']];
                    const hasHeader = attrs.hasHeader || false;
                    return `
                        <div class="anvil-table-wrapper">
                            <div class="anvil-table-controls">
                                <label><input type="checkbox" data-attr="hasHeader" ${hasHeader ? 'checked' : ''}> Header row</label>
                                <button type="button" class="anvil-btn anvil-btn-sm" data-action="add-row">+ Row</button>
                                <button type="button" class="anvil-btn anvil-btn-sm" data-action="add-column">+ Column</button>
                            </div>
                            <table class="anvil-table-editor">
                                ${rows.map((row, ri) => `
                                    <tr data-row="${ri}">
                                        ${row.map((cell, ci) => `
                                            <${hasHeader && ri === 0 ? 'th' : 'td'}>
                                                <input type="text" value="${this.escapeAttr(cell)}" data-row="${ri}" data-col="${ci}">
                                            </${hasHeader && ri === 0 ? 'th' : 'td'}>
                                        `).join('')}
                                        <td class="anvil-table-row-actions">
                                            <button type="button" class="anvil-table-remove-row" data-action="remove-row" data-row="${ri}" title="Remove row">×</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </table>
                        </div>
                    `;
                    
                default:
                    return `<div class="anvil-unknown-block">Unknown block type: ${block.type}</div>`;
            }
        }
        
        // =====================================================================
        // Event Binding
        // =====================================================================
        
        bindEvents() {
            // Toolbar actions
            this.container.addEventListener('click', (e) => {
                const action = e.target.closest('[data-action]')?.dataset.action;
                if (!action) return;
                
                this.handleAction(action, e);
            });
            
            // Block library selection
            this.elements.libraryContent.addEventListener('click', (e) => {
                const blockBtn = e.target.closest('[data-block]');
                if (blockBtn) {
                    this.insertBlock(blockBtn.dataset.block);
                }
            });
            
            // Library search
            this.elements.librarySearch.addEventListener('input', (e) => {
                this.filterLibrary(e.target.value);
            });
            
            // Content editable changes
            this.container.addEventListener('input', (e) => {
                const target = e.target;
                const attr = target.dataset.attr;
                
                if (attr && target.closest('.anvil-block')) {
                    const blockEl = target.closest('.anvil-block');
                    this.updateBlockAttribute(blockEl.dataset.blockId, attr, target.innerHTML || target.value, target.dataset.index);
                }
            });
            
            // Select/input changes
            this.container.addEventListener('change', (e) => {
                const target = e.target;
                const attr = target.dataset.attr;
                
                if (attr && target.closest('.anvil-block')) {
                    const blockEl = target.closest('.anvil-block');
                    let value = target.type === 'checkbox' ? target.checked : target.value;
                    if (target.type === 'number' || target.tagName === 'SELECT' && !isNaN(value)) {
                        value = parseInt(value) || value;
                    }
                    this.updateBlockAttribute(blockEl.dataset.blockId, attr, value);
                }
            });
            
            // Block selection
            this.container.addEventListener('click', (e) => {
                const blockEl = e.target.closest('.anvil-block');
                if (blockEl && !e.target.closest('button') && !e.target.closest('a')) {
                    this.selectBlock(blockEl.dataset.blockId);
                }
            });
            
            // Overlay click
            this.elements.overlay.addEventListener('click', () => {
                this.closeLibrary();
                this.closeSettings();
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (!this.container.contains(document.activeElement)) return;
                
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'z') {
                        e.preventDefault();
                        this.undo();
                    } else if (e.key === 'y' || (e.shiftKey && e.key === 'z')) {
                        e.preventDefault();
                        this.redo();
                    }
                }
                
                if (e.key === 'Escape') {
                    this.closeLibrary();
                    this.closeSettings();
                }
            });
        }
        
        handleAction(action, e) {
            const blockEl = e.target.closest('.anvil-block');
            const blockId = blockEl?.dataset.blockId;
            
            switch (action) {
                case 'add-block':
                case 'add-first-block':
                    this.openLibrary();
                    break;
                    
                case 'insert-after':
                    this.insertPosition = this.blocks.findIndex(b => b.id === blockId) + 1;
                    this.openLibrary();
                    break;
                    
                case 'close-library':
                    this.closeLibrary();
                    break;
                    
                case 'close-settings':
                    this.closeSettings();
                    break;
                    
                case 'move-up':
                    this.moveBlock(blockId, -1);
                    break;
                    
                case 'move-down':
                    this.moveBlock(blockId, 1);
                    break;
                    
                case 'duplicate':
                    this.duplicateBlock(blockId);
                    break;
                    
                case 'delete':
                    this.deleteBlock(blockId);
                    break;
                    
                case 'settings':
                    this.openSettings(blockId);
                    break;
                    
                case 'undo':
                    this.undo();
                    break;
                    
                case 'redo':
                    this.redo();
                    break;
                    
                case 'select-image':
                case 'change-image':
                    this.openMediaSelector(blockId, 'image');
                    break;
                    
                case 'remove-image':
                    this.updateBlockAttribute(blockId, 'url', '');
                    this.updateBlockAttribute(blockId, 'mediaId', 0);
                    this.renderBlocks();
                    break;
                    
                case 'select-video':
                case 'change-video':
                    this.openMediaSelector(blockId, 'video');
                    break;
                    
                case 'remove-video':
                    this.updateBlockAttribute(blockId, 'url', '');
                    this.updateBlockAttribute(blockId, 'mediaId', 0);
                    this.renderBlocks();
                    break;
                    
                case 'add-gallery-images':
                    this.openMediaSelector(blockId, 'gallery');
                    break;
                    
                case 'remove-gallery-image':
                    this.removeGalleryImage(blockId, parseInt(e.target.dataset.index));
                    break;
                    
                case 'add-list-item':
                    this.addListItem(blockId);
                    break;
                    
                case 'remove-list-item':
                    const itemEl = e.target.closest('.anvil-list-item');
                    this.removeListItem(blockId, parseInt(itemEl.dataset.index));
                    break;
                    
                case 'add-row':
                    this.addTableRow(blockId);
                    break;
                    
                case 'add-column':
                    this.addTableColumn(blockId);
                    break;
                    
                case 'remove-row':
                    this.removeTableRow(blockId, parseInt(e.target.dataset.row));
                    break;
            }
        }
        
        // =====================================================================
        // Block Operations
        // =====================================================================
        
        insertBlock(type, position = null) {
            const blockDef = this.options.blocks[type];
            if (!blockDef) return;
            
            // Build default attributes
            const attrs = {};
            for (const [key, config] of Object.entries(blockDef.attributes || {})) {
                attrs[key] = config.default;
            }
            
            const block = {
                id: this.generateId(),
                type: type,
                attributes: attrs,
            };
            
            this.saveHistory();
            
            const insertAt = position !== null ? position : (this.insertPosition !== undefined ? this.insertPosition : this.blocks.length);
            this.blocks.splice(insertAt, 0, block);
            delete this.insertPosition;
            
            this.closeLibrary();
            this.renderBlocks();
            this.selectBlock(block.id);
            this.triggerChange();
            
            // Focus content if applicable
            setTimeout(() => {
                const blockEl = this.container.querySelector(`[data-block-id="${block.id}"]`);
                const editable = blockEl?.querySelector('[contenteditable="true"]');
                if (editable) {
                    editable.focus();
                }
            }, 50);
        }
        
        updateBlockAttribute(blockId, attr, value, index = null) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            if (!block.attributes) {
                block.attributes = {};
            }
            
            if (index !== null && Array.isArray(block.attributes[attr])) {
                block.attributes[attr][index] = value;
            } else {
                block.attributes[attr] = value;
            }
            
            // Special handling for some attributes
            if (attr === 'level' && block.type === 'heading') {
                const editor = this.container.querySelector(`[data-block-id="${blockId}"] .anvil-heading-editor`);
                if (editor) {
                    editor.className = `anvil-heading-editor anvil-heading-h${value}`;
                }
            }
            
            if (attr === 'height' && block.type === 'spacer') {
                const preview = this.container.querySelector(`[data-block-id="${blockId}"] .anvil-spacer-preview`);
                const label = this.container.querySelector(`[data-block-id="${blockId}"] .anvil-spacer-label`);
                if (preview) preview.style.height = value + 'px';
                if (label) label.textContent = value + 'px';
            }
            
            this.triggerChange();
        }
        
        moveBlock(blockId, direction) {
            const index = this.blocks.findIndex(b => b.id === blockId);
            if (index === -1) return;
            
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.blocks.length) return;
            
            this.saveHistory();
            
            const [block] = this.blocks.splice(index, 1);
            this.blocks.splice(newIndex, 0, block);
            
            this.renderBlocks();
            this.triggerChange();
        }
        
        duplicateBlock(blockId) {
            const index = this.blocks.findIndex(b => b.id === blockId);
            if (index === -1) return;
            
            this.saveHistory();
            
            const original = this.blocks[index];
            const duplicate = {
                ...JSON.parse(JSON.stringify(original)),
                id: this.generateId(),
            };
            
            this.blocks.splice(index + 1, 0, duplicate);
            this.renderBlocks();
            this.selectBlock(duplicate.id);
            this.triggerChange();
        }
        
        deleteBlock(blockId) {
            const index = this.blocks.findIndex(b => b.id === blockId);
            if (index === -1) return;
            
            this.saveHistory();
            
            this.blocks.splice(index, 1);
            this.selectedBlockId = null;
            
            this.renderBlocks();
            this.triggerChange();
        }
        
        selectBlock(blockId) {
            this.selectedBlockId = blockId;
            
            this.container.querySelectorAll('.anvil-block').forEach(el => {
                el.classList.toggle('is-selected', el.dataset.blockId === blockId);
            });
        }
        
        // =====================================================================
        // Library & Settings Panel
        // =====================================================================
        
        openLibrary() {
            this.elements.library.classList.add('is-open');
            this.elements.overlay.classList.add('is-visible');
            this.elements.librarySearch.value = '';
            this.filterLibrary('');
            this.elements.librarySearch.focus();
        }
        
        closeLibrary() {
            this.elements.library.classList.remove('is-open');
            this.elements.overlay.classList.remove('is-visible');
            delete this.insertPosition;
        }
        
        filterLibrary(query) {
            const q = query.toLowerCase().trim();
            
            this.elements.libraryContent.querySelectorAll('.anvil-library-block').forEach(btn => {
                const name = btn.querySelector('.anvil-library-block-name').textContent.toLowerCase();
                const blockType = btn.dataset.block;
                const blockDef = this.options.blocks[blockType];
                const desc = (blockDef?.description || '').toLowerCase();
                
                const matches = !q || name.includes(q) || blockType.includes(q) || desc.includes(q);
                btn.style.display = matches ? '' : 'none';
            });
            
            // Hide empty categories
            this.elements.libraryContent.querySelectorAll('.anvil-library-category').forEach(cat => {
                const hasVisible = cat.querySelector('.anvil-library-block:not([style*="display: none"])');
                cat.style.display = hasVisible ? '' : 'none';
            });
        }
        
        openSettings(blockId) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            const blockDef = this.options.blocks[block.type];
            if (!blockDef) return;
            
            // Render settings panel
            let html = `<div class="anvil-settings-block-info">
                <span class="anvil-settings-block-icon">${this.getIcon(blockDef.icon)}</span>
                <span class="anvil-settings-block-name">${blockDef.label}</span>
            </div>`;
            
            // Common settings
            if (blockDef.supports?.includes('className')) {
                html += `
                    <div class="anvil-settings-field">
                        <label>CSS Class</label>
                        <input type="text" class="anvil-settings-input" data-setting="className" value="${this.escapeAttr(block.attributes?.className || '')}">
                    </div>
                `;
            }
            
            if (blockDef.supports?.includes('anchor')) {
                html += `
                    <div class="anvil-settings-field">
                        <label>HTML Anchor</label>
                        <input type="text" class="anvil-settings-input" data-setting="anchor" value="${this.escapeAttr(block.attributes?.anchor || '')}">
                    </div>
                `;
            }
            
            if (blockDef.supports?.includes('align')) {
                const currentAlign = block.attributes?.align || 'left';
                html += `
                    <div class="anvil-settings-field">
                        <label>Alignment</label>
                        <div class="anvil-settings-align">
                            <button type="button" class="anvil-settings-align-btn ${currentAlign === 'left' ? 'active' : ''}" data-setting="align" data-value="left" title="Left">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
                            </button>
                            <button type="button" class="anvil-settings-align-btn ${currentAlign === 'center' ? 'active' : ''}" data-setting="align" data-value="center" title="Center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="10" x2="6" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="18" y1="18" x2="6" y2="18"></line></svg>
                            </button>
                            <button type="button" class="anvil-settings-align-btn ${currentAlign === 'right' ? 'active' : ''}" data-setting="align" data-value="right" title="Right">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="7" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="7" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>
                `;
            }
            
            this.elements.settingsContent.innerHTML = html;
            this.elements.settings.classList.add('is-open');
            this.elements.settings.dataset.blockId = blockId;
            
            // Bind settings events
            this.elements.settingsContent.querySelectorAll('[data-setting]').forEach(el => {
                if (el.tagName === 'BUTTON') {
                    el.addEventListener('click', () => {
                        this.updateBlockAttribute(blockId, el.dataset.setting, el.dataset.value);
                        this.elements.settingsContent.querySelectorAll(`[data-setting="${el.dataset.setting}"]`).forEach(btn => {
                            btn.classList.toggle('active', btn.dataset.value === el.dataset.value);
                        });
                    });
                } else {
                    el.addEventListener('input', () => {
                        this.updateBlockAttribute(blockId, el.dataset.setting, el.value);
                    });
                }
            });
        }
        
        closeSettings() {
            this.elements.settings.classList.remove('is-open');
        }
        
        // =====================================================================
        // Media Handling
        // =====================================================================
        
        openMediaSelector(blockId, type = 'image') {
            this.pendingMediaBlockId = blockId;
            this.pendingMediaType = type;
            
            // Use the global media modal if available
            if (typeof openMediaModal === 'function') {
                window.anvilMediaCallback = (id, url) => {
                    this.handleMediaSelected(id, url);
                };
                openMediaModal();
            } else {
                // Fallback: simple file URL prompt
                const url = prompt('Enter media URL:');
                if (url) {
                    this.handleMediaSelected(0, url);
                }
            }
        }
        
        handleMediaSelected(mediaId, mediaUrl) {
            const blockId = this.pendingMediaBlockId;
            const type = this.pendingMediaType;
            
            if (!blockId) return;
            
            const block = this.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            this.saveHistory();
            
            if (type === 'gallery') {
                if (!block.attributes.images) {
                    block.attributes.images = [];
                }
                block.attributes.images.push({
                    id: mediaId,
                    url: mediaUrl,
                    alt: '',
                });
            } else {
                block.attributes.mediaId = mediaId;
                block.attributes.url = mediaUrl;
            }
            
            this.renderBlocks();
            this.triggerChange();
            
            delete this.pendingMediaBlockId;
            delete this.pendingMediaType;
        }
        
        removeGalleryImage(blockId, index) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block || !block.attributes.images) return;
            
            this.saveHistory();
            block.attributes.images.splice(index, 1);
            this.renderBlocks();
            this.triggerChange();
        }
        
        // =====================================================================
        // List Operations
        // =====================================================================
        
        addListItem(blockId) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            if (!block.attributes.items) {
                block.attributes.items = [];
            }
            
            this.saveHistory();
            block.attributes.items.push('');
            this.renderBlocks();
            this.triggerChange();
            
            // Focus new item
            setTimeout(() => {
                const items = this.container.querySelectorAll(`[data-block-id="${blockId}"] .anvil-list-item-content`);
                const lastItem = items[items.length - 1];
                if (lastItem) lastItem.focus();
            }, 50);
        }
        
        removeListItem(blockId, index) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block || !block.attributes.items) return;
            
            if (block.attributes.items.length <= 1) return; // Keep at least one item
            
            this.saveHistory();
            block.attributes.items.splice(index, 1);
            this.renderBlocks();
            this.triggerChange();
        }
        
        // =====================================================================
        // Table Operations
        // =====================================================================
        
        addTableRow(blockId) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block) return;
            
            if (!block.attributes.rows) {
                block.attributes.rows = [['', '']];
            }
            
            this.saveHistory();
            const cols = block.attributes.rows[0]?.length || 2;
            block.attributes.rows.push(Array(cols).fill(''));
            this.renderBlocks();
            this.triggerChange();
        }
        
        addTableColumn(blockId) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block || !block.attributes.rows) return;
            
            this.saveHistory();
            block.attributes.rows.forEach(row => row.push(''));
            this.renderBlocks();
            this.triggerChange();
        }
        
        removeTableRow(blockId, rowIndex) {
            const block = this.blocks.find(b => b.id === blockId);
            if (!block || !block.attributes.rows) return;
            
            if (block.attributes.rows.length <= 1) return;
            
            this.saveHistory();
            block.attributes.rows.splice(rowIndex, 1);
            this.renderBlocks();
            this.triggerChange();
        }
        
        // =====================================================================
        // Drag & Drop
        // =====================================================================
        
        initSortable() {
            if (this.sortable) {
                this.sortable.destroy();
            }
            
            if (typeof Sortable === 'undefined') return;
            
            this.sortable = new Sortable(this.elements.blocksContainer, {
                handle: '.anvil-block-drag-handle',
                animation: 150,
                ghostClass: 'anvil-block-ghost',
                chosenClass: 'anvil-block-chosen',
                dragClass: 'anvil-block-drag',
                onEnd: (evt) => {
                    if (evt.oldIndex === evt.newIndex) return;
                    
                    this.saveHistory();
                    
                    const [block] = this.blocks.splice(evt.oldIndex, 1);
                    this.blocks.splice(evt.newIndex, 0, block);
                    
                    this.triggerChange();
                },
            });
        }
        
        // =====================================================================
        // History (Undo/Redo)
        // =====================================================================
        
        saveHistory() {
            // Remove any future history if we're not at the end
            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }
            
            // Save current state
            this.history.push(JSON.stringify(this.blocks));
            
            // Limit history size
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }
            
            this.updateHistoryButtons();
        }
        
        undo() {
            if (this.historyIndex < 0) return;
            
            // Save current state if at the end
            if (this.historyIndex === this.history.length - 1) {
                this.history.push(JSON.stringify(this.blocks));
            }
            
            this.blocks = JSON.parse(this.history[this.historyIndex]);
            this.historyIndex--;
            
            this.renderBlocks();
            this.updateHistoryButtons();
            this.triggerChange();
        }
        
        redo() {
            if (this.historyIndex >= this.history.length - 2) return;
            
            this.historyIndex++;
            this.blocks = JSON.parse(this.history[this.historyIndex + 1]);
            
            this.renderBlocks();
            this.updateHistoryButtons();
            this.triggerChange();
        }
        
        updateHistoryButtons() {
            if (this.elements.undoBtn) {
                this.elements.undoBtn.disabled = this.historyIndex < 0;
            }
            if (this.elements.redoBtn) {
                this.elements.redoBtn.disabled = this.historyIndex >= this.history.length - 2;
            }
        }
        
        // =====================================================================
        // Utilities
        // =====================================================================
        
        generateId() {
            return 'block-' + Math.random().toString(36).substr(2, 16);
        }
        
        escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        escapeAttr(str) {
            return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        getIcon(name) {
            const icons = {
                'align-left': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>',
                'heading': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h8M4 18V6M12 18V6M17 12l3-2v8"></path></svg>',
                'list': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
                'quote': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V21z"></path><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3z"></path></svg>',
                'code': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>',
                'code-2': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 16 4-4-4-4"></path><path d="m6 8-4 4 4 4"></path><path d="m14.5 4-5 16"></path></svg>',
                'image': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
                'grid': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
                'video': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>',
                'columns': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="3" x2="12" y2="21"></line></svg>',
                'arrow-down-up': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 16 4 4 4-4"></path><path d="M7 20V4"></path><path d="m21 8-4-4-4 4"></path><path d="M17 4v16"></path></svg>',
                'minus': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
                'square': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>',
                'external-link': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>',
                'table': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"></path><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M3 9h18"></path><path d="M3 15h18"></path></svg>',
                'type': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
                'layout': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>',
                'folder': '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>',
            };
            
            return icons[name] || icons['square'];
        }
        
        getEmbedPreview(url) {
            // YouTube
            const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
            if (ytMatch) {
                return `<iframe src="https://www.youtube.com/embed/${ytMatch[1]}" frameborder="0" allowfullscreen></iframe>`;
            }
            
            // Vimeo
            const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) {
                return `<iframe src="https://player.vimeo.com/video/${vimeoMatch[1]}" frameborder="0" allowfullscreen></iframe>`;
            }
            
            return `<div class="anvil-embed-placeholder">Preview not available</div>`;
        }
        
        triggerChange() {
            this.options.onChange(this.blocks);
        }
        
        // =====================================================================
        // Public API
        // =====================================================================
        
        loadContent(content) {
            if (typeof content === 'string') {
                try {
                    content = JSON.parse(content);
                } catch (e) {
                    content = [];
                }
            }
            
            this.blocks = Array.isArray(content) ? content : [];
            this.history = [];
            this.historyIndex = -1;
            
            this.renderBlocks();
            this.updateHistoryButtons();
        }
        
        getContent() {
            return this.blocks;
        }
        
        getContentJSON() {
            return JSON.stringify(this.blocks);
        }
        
        destroy() {
            if (this.sortable) {
                this.sortable.destroy();
            }
            this.container.innerHTML = '';
        }
    }
    
    // Export
    window.AnvilEditor = AnvilEditor;
    
})();
