/**
 * Anvil Live - UI Module
 * Top bar, sidebar, and canvas handlers
 */
(function() {
    'use strict';
    const AL = window.AnvilLive;
    if (!AL) return console.error('AnvilLive core not loaded');

    AL.UI = {
        init: function() {
            this.initTopBar();
            this.initSidebar();
            this.initCanvas();
        },

        initTopBar: function() {
            document.getElementById('anvil-live-toggle-sidebar')?.addEventListener('click', () => {
                document.body.classList.toggle('anvil-live-sidebar-open');
            });

            document.getElementById('anvil-live-title')?.addEventListener('input', () => AL.markDirty());
            document.getElementById('anvil-live-save')?.addEventListener('click', () => AL.Save.saveContent());
            document.getElementById('anvil-live-preview')?.addEventListener('click', () => AL.Save.previewContent());
            document.getElementById('anvil-live-undo')?.addEventListener('click', () => AL.Save.undo());
            document.getElementById('anvil-live-redo')?.addEventListener('click', () => AL.Save.redo());

            document.querySelectorAll('.anvil-live-device-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.anvil-live-device-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    document.body.className = document.body.className.replace(/anvil-live-device-\w+/g, '');
                    document.body.classList.add('anvil-live-device-' + btn.dataset.device);
                });
            });

            document.querySelector('.anvil-live-exit-btn')?.addEventListener('click', (e) => {
                if (AL.state.isDirty) {
                    e.preventDefault();
                    AL.Modals.showUnsaved();
                }
            });
        },

        initSidebar: function() {
            const self = this;
            
            document.querySelectorAll('.anvil-live-sidebar-tab').forEach(tab => {
                tab.addEventListener('click', () => self.switchTab(tab.dataset.tab));
            });

            document.querySelectorAll('.anvil-live-block-category-header').forEach(header => {
                header.addEventListener('click', () => header.parentElement.classList.toggle('collapsed'));
            });

            document.getElementById('anvil-live-block-search')?.addEventListener('input', (e) => {
                const q = e.target.value.toLowerCase().trim();
                document.querySelectorAll('.anvil-live-block-item').forEach(item => {
                    const label = item.querySelector('.anvil-live-block-item-label')?.textContent.toLowerCase() || '';
                    item.style.display = (!q || label.includes(q)) ? '' : 'none';
                });
                document.querySelectorAll('.anvil-live-block-category').forEach(cat => {
                    const hasVisible = cat.querySelectorAll('.anvil-live-block-item:not([style*="display: none"])').length > 0;
                    cat.style.display = hasVisible ? '' : 'none';
                });
            });

            // Block items - click to add, drag to position
            document.querySelectorAll('.anvil-live-block-item').forEach(item => {
                let isDragging = false;
                let startX = 0;
                let startY = 0;
                
                item.addEventListener('click', (e) => {
                    if (isDragging) {
                        isDragging = false;
                        return;
                    }
                    
                    const blockType = item.dataset.blockType;
                    if (blockType) {
                        const insertAfter = window._anvilLiveInsertAfter;
                        window._anvilLiveInsertAfter = undefined;
                        AL.Blocks.add(blockType, insertAfter !== undefined ? insertAfter : -1);
                    }
                });

                item.addEventListener('mousedown', (e) => {
                    if (e.button !== 0) return;
                    
                    isDragging = false;
                    startX = e.clientX;
                    startY = e.clientY;
                    
                    const blockType = item.dataset.blockType;
                    const label = item.querySelector('.anvil-live-block-item-label')?.textContent || blockType;
                    
                    const onMouseMove = (moveEvent) => {
                        const dx = Math.abs(moveEvent.clientX - startX);
                        const dy = Math.abs(moveEvent.clientY - startY);
                        
                        if (dx > 5 || dy > 5) {
                            isDragging = true;
                            item.classList.add('dragging');
                            AL.clearColumnContext();
                            AL.Drag.start('new', blockType, null, label, moveEvent);
                            document.removeEventListener('mousemove', onMouseMove);
                        }
                    };
                    
                    const onMouseUp = () => {
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        item.classList.remove('dragging');
                    };
                    
                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        },

        switchTab: function(tabName) {
            document.querySelectorAll('.anvil-live-sidebar-tab').forEach(t => {
                t.classList.toggle('active', t.dataset.tab === tabName);
            });
            document.querySelectorAll('.anvil-live-sidebar-panel').forEach(p => {
                p.classList.toggle('active', p.dataset.panel === tabName);
            });
        },

        initCanvas: function() {
            const self = this;
            const canvas = document.getElementById('anvil-live-canvas');
            if (!canvas) return;

            canvas.addEventListener('click', (e) => {
                if (e.target === canvas || e.target.classList.contains('anvil-live-blocks') || e.target.classList.contains('anvil-live-empty-state')) {
                    AL.Editor.endEditing();
                    AL.Blocks.deselectAll();
                    AL.clearColumnContext();
                }
                
                if (e.target.closest('.anvil-live-empty-state')) {
                    self.switchTab('blocks');
                    document.body.classList.add('anvil-live-sidebar-open');
                }
                
                // Handle column clicks
                const column = e.target.closest('.anvil-column');
                
                if (column) {
                    const columnsBlock = column.closest('.anvil-live-block[data-block-type="columns"]');
                    const nestedBlock = e.target.closest('.anvil-live-block');
                    const isNestedBlockClick = nestedBlock && nestedBlock !== columnsBlock && column.contains(nestedBlock);
                    
                    if (isNestedBlockClick) {
                        const blockId = nestedBlock.dataset.blockId;
                        if (blockId && !e.target.closest('.anvil-live-block-action') && !e.target.closest('.anvil-live-block-handle')) {
                            AL.Blocks.select(blockId);
                            self.switchTab('settings');
                            document.body.classList.add('anvil-live-sidebar-open');
                        }
                        return;
                    }
                    
                    if (columnsBlock) {
                        const blockId = columnsBlock.dataset.blockId;
                        let columnIndex = parseInt(column.dataset.columnIndex);
                        if (isNaN(columnIndex)) {
                            const siblings = Array.from(column.parentElement.querySelectorAll('.anvil-column'));
                            columnIndex = siblings.indexOf(column);
                        }
                        
                        AL.setColumnContext(blockId, columnIndex);
                        self.switchTab('blocks');
                        document.body.classList.add('anvil-live-sidebar-open');
                        
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }
                }
                
                // Handle block clicks
                const clickedBlock = e.target.closest('.anvil-live-block');
                if (clickedBlock && !e.target.closest('.anvil-live-block-action') && !e.target.closest('.anvil-live-block-handle')) {
                    const blockId = clickedBlock.dataset.blockId;
                    if (blockId) {
                        AL.Blocks.select(blockId);
                        self.switchTab('settings');
                        document.body.classList.add('anvil-live-sidebar-open');
                    }
                }
            });

            // Block actions delegation
            document.addEventListener('click', (e) => {
                const actionBtn = e.target.closest('.anvil-live-block-action');
                if (actionBtn) {
                    const blockEl = actionBtn.closest('.anvil-live-block');
                    const blockId = blockEl?.dataset.blockId;
                    const action = actionBtn.dataset.action;

                    if (action === 'edit') AL.Blocks.select(blockId);
                    else if (action === 'duplicate') AL.Blocks.duplicate(blockId);
                    else if (action === 'delete') AL.Blocks.delete(blockId);
                    return;
                }

                const addBetweenBtn = e.target.closest('.anvil-live-add-between-btn');
                if (addBetweenBtn) {
                    window._anvilLiveInsertAfter = parseInt(addBetweenBtn.dataset.afterIndex, 10);
                    self.switchTab('blocks');
                    document.body.classList.add('anvil-live-sidebar-open');
                    document.getElementById('anvil-live-block-search')?.focus();
                }
            });

            // Block drag handles
            document.addEventListener('mousedown', (e) => {
                const handle = e.target.closest('.anvil-live-block-handle');
                if (handle && e.button === 0) {
                    const blockEl = handle.closest('.anvil-live-block');
                    if (blockEl) {
                        const blockId = blockEl.dataset.blockId;
                        const blockType = blockEl.dataset.blockType;
                        const blockDef = AL.config.blocks?.blocks?.[blockType] || { label: blockType };
                        
                        AL.Drag.start('move', blockType, blockId, blockDef.label, e);
                        e.preventDefault();
                    }
                }
            });
        }
    };

})();
