/**
 * Anvil Live - Blocks Module
 * Block CRUD operations, selection, settings, and rendering
 */
(function() {
    'use strict';
    const AL = window.AnvilLive;
    if (!AL) return console.error('AnvilLive core not loaded');

    AL.Blocks = {
        init: function() {
            // Blocks module initialized by render
        },

        // =====================================================================
        // BLOCK SELECTION
        // =====================================================================

        select: function(blockId) {
            this.deselectAll();
            const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
            if (blockEl) {
                blockEl.classList.add('selected');
                AL.state.selectedBlockId = blockId;
                this.showSettings(blockId);
                AL.UI.switchTab('settings');
            }
        },

        deselectAll: function() {
            document.querySelectorAll('.anvil-live-block.selected').forEach(el => el.classList.remove('selected'));
            AL.state.selectedBlockId = null;
            
            const settingsPanel = document.getElementById('anvil-live-block-settings');
            if (settingsPanel) {
                settingsPanel.innerHTML = `
                    <div class="anvil-live-no-selection">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M12 1v2m0 18v2m11-11h-2M3 12H1m17.07-7.07l-1.41 1.41M6.34 17.66l-1.41 1.41m12.73 0l-1.41-1.41M6.34 6.34L4.93 4.93"/>
                        </svg>
                        <p>Click on any block to edit it directly<br>or drag blocks to reorder</p>
                    </div>
                `;
            }
        },

        // =====================================================================
        // BLOCK CRUD
        // =====================================================================

        add: function(type, afterIndex = -1) {
            const blockDef = AL.config.blocks?.blocks?.[type];
            if (!blockDef) return;

            const newBlock = { id: AL.Utils.generateBlockId(), type, attributes: {} };
            
            const attrs = blockDef.attributes || {};
            for (const [key, attrDef] of Object.entries(attrs)) {
                newBlock.attributes[key] = attrDef.default ?? '';
            }
            
            // Special init for columns
            if (type === 'columns') {
                const colCount = newBlock.attributes.columnCount || 2;
                newBlock.attributes.columns = [];
                for (let i = 0; i < colCount; i++) newBlock.attributes.columns.push([]);
            }
            
            // Check column context
            const columnContext = AL.getColumnContext();
            if (columnContext && columnContext.blockId) {
                AL.clearColumnContext();
                this.addToColumn(columnContext.blockId, columnContext.columnIndex, newBlock);
                return;
            }

            const blocks = AL.state.blocks;
            if (afterIndex >= 0 && afterIndex < blocks.length) {
                blocks.splice(afterIndex + 1, 0, newBlock);
            } else {
                blocks.push(newBlock);
            }

            AL.Save.saveState();
            AL.markDirty();
            this.render();

            setTimeout(() => {
                const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                const editable = newBlockEl?.querySelector('[contenteditable]');
                if (editable) editable.focus();
                else this.select(newBlock.id);
            }, 50);
        },

        addAt: function(type, index) {
            const blockDef = AL.config.blocks?.blocks?.[type];
            if (!blockDef) return;

            const newBlock = { id: AL.Utils.generateBlockId(), type, attributes: {} };
            
            const attrs = blockDef.attributes || {};
            for (const [key, attrDef] of Object.entries(attrs)) {
                newBlock.attributes[key] = attrDef.default ?? '';
            }
            
            if (type === 'columns') {
                const colCount = newBlock.attributes.columnCount || 2;
                newBlock.attributes.columns = [];
                for (let i = 0; i < colCount; i++) newBlock.attributes.columns.push([]);
            }
            
            AL.state.blocks.splice(index, 0, newBlock);
            
            AL.Save.saveState();
            AL.markDirty();
            this.render();
            
            setTimeout(() => {
                const el = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                const editable = el?.querySelector('[contenteditable]');
                if (editable) editable.focus();
                else this.select(newBlock.id);
            }, 50);
        },

        addToColumn: function(parentBlockId, columnIndex, newBlock) {
            const blocks = AL.state.blocks;
            const parentIndex = blocks.findIndex(b => b.id === parentBlockId);
            if (parentIndex === -1) return;
            
            const parentBlock = blocks[parentIndex];
            if (parentBlock.type !== 'columns') return;
            
            if (!Array.isArray(parentBlock.attributes.columns)) {
                parentBlock.attributes.columns = [];
            }
            
            while (parentBlock.attributes.columns.length <= columnIndex) {
                parentBlock.attributes.columns.push([]);
            }
            
            parentBlock.attributes.columns[columnIndex].push(newBlock);
            
            AL.Save.saveState();
            AL.markDirty();
            this.render();
            
            setTimeout(() => {
                const el = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                const editable = el?.querySelector('[contenteditable]');
                if (editable) editable.focus();
                else this.select(newBlock.id);
            }, 50);
        },

        duplicate: function(blockId) {
            const location = AL.Utils.findBlockLocation(blockId);
            if (!location) return;
            
            const blocks = AL.state.blocks;
            const newBlock = JSON.parse(JSON.stringify(location.block));
            newBlock.id = AL.Utils.generateBlockId();
            
            if (location.type === 'main') {
                blocks.splice(location.index + 1, 0, newBlock);
            } else if (location.type === 'column') {
                blocks[location.parentIndex].attributes.columns[location.columnIndex].splice(location.index + 1, 0, newBlock);
            }
            
            AL.Save.saveState();
            AL.markDirty();
            this.render();
            this.select(newBlock.id);
        },

        delete: function(blockId) {
            const location = AL.Utils.findBlockLocation(blockId);
            if (!location) return;
            
            const blocks = AL.state.blocks;
            
            if (location.type === 'main') {
                blocks.splice(location.index, 1);
            } else if (location.type === 'column') {
                blocks[location.parentIndex].attributes.columns[location.columnIndex].splice(location.index, 1);
            }
            
            if (AL.state.selectedBlockId === blockId) {
                this.deselectAll();
            }
            
            AL.Save.saveState();
            AL.markDirty();
            this.render();
        },

        updateAttribute: function(blockId, key, value) {
            const location = AL.Utils.findBlockLocation(blockId);
            if (!location) return;
            
            location.block.attributes = location.block.attributes || {};
            location.block.attributes[key] = value;
            
            AL.markDirty();
            this.renderSingle(blockId);
        },

        // =====================================================================
        // RENDERING
        // =====================================================================

        render: function() {
            const container = document.getElementById('anvil-live-blocks');
            if (!container) return;

            if (AL.Editor) AL.Editor.endEditing();

            const blocks = AL.state.blocks;
            if (blocks.length === 0) {
                container.innerHTML = `
                    <div class="anvil-live-empty-state">
                        <div class="anvil-live-empty-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                <path d="M12 8v8M8 12h8"/>
                            </svg>
                        </div>
                        <h3 style="margin:0 0 8px;font-size:18px;font-weight:600;">Start Building</h3>
                        <p style="margin:0;color:#64748b;">Drag a block from the sidebar and drop it here</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = blocks.map((block, index) => this.renderBlockHTML(block, index)).join('');
            
            if (AL.Editor) AL.Editor.makeBlocksEditable();
        },

        renderSingle: function(blockId) {
            const location = AL.Utils.findBlockLocation(blockId);
            if (!location) {
                this.render();
                return;
            }
            
            const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
            if (!blockEl) {
                this.render();
                return;
            }

            const index = location.index !== undefined ? location.index : 0;
            const temp = document.createElement('div');
            temp.innerHTML = this.renderBlockHTML(location.block, index);
            const newBlockEl = temp.firstElementChild;

            blockEl.replaceWith(newBlockEl);
            if (AL.Editor) AL.Editor.makeBlocksEditable();
            
            if (AL.state.selectedBlockId === blockId) {
                newBlockEl.classList.add('selected');
            }
        },

        renderBlockHTML: function(block, index) {
            const type = block.type;
            const id = block.id;
            const blockDef = AL.config.blocks?.blocks?.[type] || { label: type };
            const attrs = block.attributes || {};
            const isEditable = ['paragraph', 'heading', 'quote', 'list', 'button'].includes(type);
            const textAlign = attrs.align ? `text-align:${attrs.align};` : '';
            const escapeHtml = AL.Utils.escapeHtml;
            const normalizeUrl = AL.Utils.normalizeUrl;

            let content = '';
            
            switch (type) {
                case 'paragraph':
                    content = `<p style="margin:0;min-height:1.5em;${textAlign}">${attrs.content || ''}</p>`;
                    break;
                case 'heading':
                    const level = attrs.level || 2;
                    content = `<h${level} style="margin:0;min-height:1.2em;${textAlign}">${attrs.content || ''}</h${level}>`;
                    break;
                case 'image':
                    if (attrs.url) {
                        content = `<figure style="margin:0;"><img src="${escapeHtml(attrs.url)}" alt="${escapeHtml(attrs.alt || '')}" style="max-width:100%;height:auto;border-radius:8px;"></figure>`;
                    } else {
                        content = `<div style="padding:40px;background:#f1f5f9;text-align:center;color:#64748b;border-radius:8px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                            <p style="margin:0">Click Settings to add image URL</p>
                        </div>`;
                    }
                    break;
                case 'button':
                    content = `<div style="text-align:${attrs.align || 'left'}">
                        <a href="${escapeHtml(attrs.url || '#')}" style="display:inline-block;padding:12px 24px;background:var(--al-primary);color:white;text-decoration:none;border-radius:6px;font-weight:500;">${attrs.text || 'Button'}</a>
                    </div>`;
                    break;
                case 'separator':
                    content = `<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">`;
                    break;
                case 'spacer':
                    content = `<div style="height:${attrs.height || 50}px;background:repeating-linear-gradient(45deg,transparent,transparent 10px,#f1f5f9 10px,#f1f5f9 20px);border-radius:4px;"></div>`;
                    break;
                case 'quote':
                    content = `<blockquote style="border-left:4px solid var(--al-primary);padding-left:20px;margin:0;font-style:italic;color:#475569;min-height:1.5em;${textAlign}">${attrs.content || ''}</blockquote>`;
                    break;
                case 'code':
                    content = `<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow-x:auto;margin:0;"><code>${escapeHtml(attrs.content || '// Code here')}</code></pre>`;
                    break;
                case 'list':
                    const tag = attrs.ordered ? 'ol' : 'ul';
                    const items = (attrs.content || 'Item 1\nItem 2\nItem 3').split('\n').map(i => `<li>${i}</li>`).join('');
                    content = `<${tag} style="margin:0;padding-left:24px;">${items}</${tag}>`;
                    break;
                case 'video':
                    if (attrs.url) {
                        content = `<video src="${escapeHtml(attrs.url)}" controls style="width:100%;border-radius:8px;"></video>`;
                    } else {
                        content = `<div style="padding:40px;background:#f1f5f9;text-align:center;color:#64748b;border-radius:8px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M10 9l5 3-5 3V9z"/></svg>
                            <p style="margin:0">Add video URL in sidebar</p>
                        </div>`;
                    }
                    break;
                case 'columns':
                    const columnCount = Math.max(2, Math.min(6, parseInt(attrs.columnCount) || 2));
                    const columns = attrs.columns || [];
                    const vAlign = attrs.verticalAlign || 'top';
                    const alignValue = vAlign === 'center' ? 'center' : vAlign === 'bottom' ? 'end' : 'start';
                    
                    let columnsHtml = '';
                    for (let i = 0; i < columnCount; i++) {
                        const colBlocks = columns[i] || [];
                        let colContent = '';
                        
                        if (Array.isArray(colBlocks) && colBlocks.length > 0) {
                            colContent = colBlocks.map((b, idx) => this.renderBlockHTML(b, idx)).join('');
                        }
                        
                        columnsHtml += `<div class="anvil-column" data-column-index="${i}">${colContent}</div>`;
                    }
                    
                    content = `<div class="anvil-columns" style="display:grid;grid-template-columns:repeat(${columnCount},1fr);gap:24px;align-items:${alignValue};">${columnsHtml}</div>`;
                    break;
                case 'alert':
                    const alertType = attrs.type || 'info';
                    const alertTitle = attrs.title || '';
                    const alertContent = attrs.content || 'This is an alert message.';
                    const alertColors = { info: '#3b82f6', success: '#22c55e', warning: '#f59e0b', error: '#ef4444' };
                    const alertBgColors = { info: 'rgba(59,130,246,0.1)', success: 'rgba(34,197,94,0.1)', warning: 'rgba(245,158,11,0.1)', error: 'rgba(239,68,68,0.1)' };
                    const alertIcons = {
                        info: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
                        success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
                        warning: '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                        error: '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
                    };
                    const alertTitleHtml = alertTitle ? `<div style="font-weight:600;margin-bottom:4px;">${escapeHtml(alertTitle)}</div>` : '';
                    content = `<div style="display:flex;align-items:flex-start;gap:12px;padding:16px;border-radius:8px;border-left:4px solid ${alertColors[alertType]};background:${alertBgColors[alertType]};">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="${alertColors[alertType]}" stroke-width="2" style="flex-shrink:0;margin-top:2px;">${alertIcons[alertType]}</svg>
                        <div style="flex:1;">${alertTitleHtml}<div>${escapeHtml(alertContent)}</div></div>
                    </div>`;
                    break;
                case 'card':
                    const cardImage = attrs.imageUrl || '';
                    const cardTitle = attrs.title || 'Card Title';
                    const cardContent = attrs.content || 'Card description text goes here.';
                    const cardBtnText = attrs.buttonText || '';
                    const cardBtnUrl = attrs.buttonUrl || '#';
                    const cardImageHtml = cardImage ? `<div style="height:200px;overflow:hidden;"><img src="${escapeHtml(cardImage)}" alt="${escapeHtml(attrs.imageAlt || '')}" style="width:100%;height:100%;object-fit:cover;"></div>` : '';
                    const cardBtnHtml = cardBtnText ? `<a href="${escapeHtml(cardBtnUrl)}" style="display:inline-block;padding:8px 16px;background:#6366f1;color:white;text-decoration:none;border-radius:6px;font-size:14px;font-weight:500;">${escapeHtml(cardBtnText)}</a>` : '';
                    content = `<div style="background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
                        ${cardImageHtml}
                        <div style="padding:20px;">
                            <h3 style="margin:0 0 8px;font-size:1.25rem;font-weight:700;">${escapeHtml(cardTitle)}</h3>
                            <p style="margin:0 0 16px;color:#64748b;line-height:1.6;">${escapeHtml(cardContent)}</p>
                            ${cardBtnHtml}
                        </div>
                    </div>`;
                    break;
                default:
                    content = `<div style="padding:20px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;text-align:center;color:#64748b;">${escapeHtml(type)} block</div>`;
            }

            const blockStyles = AL.Utils.getBlockStyles(attrs);
            const blockClasses = AL.Utils.getBlockClasses(attrs);
            const blockCssId = AL.Utils.getBlockCssId(attrs);
            const idAttr = blockCssId ? `id="${escapeHtml(blockCssId)}"` : '';

            return `
                <div class="anvil-live-block ${blockClasses}" ${idAttr} data-block-id="${escapeHtml(id)}" data-block-type="${escapeHtml(type)}" data-block-index="${index}" style="${blockStyles}">
                    <div class="anvil-live-block-toolbar">
                        <div class="anvil-live-block-toolbar-left">
                            <span class="anvil-live-block-handle" title="Drag to reorder">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/>
                                    <circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/>
                                </svg>
                            </span>
                            <span class="anvil-live-block-type">${escapeHtml(blockDef.label)}</span>
                        </div>
                        <div class="anvil-live-block-toolbar-right">
                            <button type="button" class="anvil-live-block-action" data-action="edit" title="Settings">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="3"/><path d="M12 1v2m0 18v2m11-11h-2M3 12H1m17.07-7.07l-1.41 1.41M6.34 17.66l-1.41 1.41m12.73 0l-1.41-1.41M6.34 6.34L4.93 4.93"/>
                                </svg>
                            </button>
                            <button type="button" class="anvil-live-block-action" data-action="duplicate" title="Duplicate">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                </svg>
                            </button>
                            <button type="button" class="anvil-live-block-action anvil-live-block-action-delete" data-action="delete" title="Delete">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"/><path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="anvil-live-block-content" data-editable="${isEditable}">
                        ${content}
                    </div>
                    <div class="anvil-live-add-between">
                        <button type="button" class="anvil-live-add-between-btn" data-after-index="${index}" title="Add block">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        },

        // =====================================================================
        // BLOCK SETTINGS (Simplified - uses external Settings module)
        // =====================================================================

        showSettings: function(blockId) {
            // Delegate to Settings module if available
            if (AL.Settings && AL.Settings.showBlockSettings) {
                AL.Settings.showBlockSettings(blockId);
            }
        }
    };

})();
