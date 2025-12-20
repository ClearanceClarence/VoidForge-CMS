/**
 * Anvil Live - Drag & Drop Module
 * Handles drag and drop for blocks
 */
(function() {
    'use strict';
    const AL = window.AnvilLive;
    if (!AL) return console.error('AnvilLive core not loaded');

    AL.Drag = {
        init: function() {
            this.createDropIndicator();
            document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
            document.addEventListener('mouseup', (e) => this.handleMouseUp(e));
            document.addEventListener('dragover', (e) => e.preventDefault());
            document.addEventListener('drop', (e) => e.preventDefault());
        },

        createDropIndicator: function() {
            const indicator = document.createElement('div');
            indicator.id = 'anvil-live-drop-indicator';
            indicator.className = 'anvil-live-drop-indicator';
            document.body.appendChild(indicator);
            AL.state.dragState.indicator = indicator;
        },

        showDropIndicator: function(element, position) {
            const indicator = AL.state.dragState.indicator;
            if (!indicator || !element) return;

            const rect = element.getBoundingClientRect();
            const container = document.getElementById('anvil-live-blocks');
            const containerRect = container.getBoundingClientRect();
            
            indicator.style.left = containerRect.left + 'px';
            indicator.style.width = containerRect.width + 'px';
            indicator.style.top = (position === 'above' ? rect.top - 2 : rect.bottom - 2) + window.scrollY + 'px';
            indicator.classList.add('active');
        },

        hideDropIndicator: function() {
            if (AL.state.dragState.indicator) {
                AL.state.dragState.indicator.classList.remove('active');
            }
            document.querySelectorAll('.drop-above, .drop-below, .drop-target').forEach(el => {
                el.classList.remove('drop-above', 'drop-below', 'drop-target');
            });
        },

        createGhost: function(blockType, label) {
            const ghost = document.createElement('div');
            ghost.className = 'anvil-live-drag-ghost';
            ghost.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <span>${AL.Utils.escapeHtml(label)}</span>
            `;
            ghost.style.display = 'none';
            document.body.appendChild(ghost);
            return ghost;
        },

        start: function(type, blockType, blockId, label, e) {
            const ds = AL.state.dragState;
            ds.active = true;
            ds.type = type;
            ds.blockType = blockType;
            ds.blockId = blockId;
            ds.dropIndex = -1;
            ds.ghost = this.createGhost(blockType, label);
            
            if (type === 'move' && blockId) {
                const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
                if (blockEl) blockEl.classList.add('dragging');
            }
            
            document.body.style.cursor = 'grabbing';
            document.body.classList.add('anvil-live-dragging');
            this.handleMouseMove(e);
        },

        handleMouseMove: function(e) {
            const ds = AL.state.dragState;
            if (!ds.active) return;

            // Update ghost position
            if (ds.ghost) {
                ds.ghost.style.display = 'flex';
                ds.ghost.style.left = e.clientX + 'px';
                ds.ghost.style.top = e.clientY + 'px';
            }

            ds.dropColumn = null;
            
            const blocksContainer = document.getElementById('anvil-live-blocks');
            if (!blocksContainer) return;

            const containerRect = blocksContainer.getBoundingClientRect();
            
            // Check if outside container
            if (e.clientX < containerRect.left || e.clientX > containerRect.right ||
                e.clientY < containerRect.top || e.clientY > containerRect.bottom) {
                this.hideDropIndicator();
                ds.dropIndex = -1;
                return;
            }

            // Check for column drop
            const elementUnderMouse = document.elementFromPoint(e.clientX, e.clientY);
            const column = elementUnderMouse?.closest('.anvil-column');
            
            if (column) {
                const columnsBlock = column.closest('.anvil-live-block[data-block-type="columns"]');
                if (columnsBlock) {
                    let columnIndex = parseInt(column.dataset.columnIndex);
                    if (isNaN(columnIndex)) {
                        const siblings = Array.from(column.parentElement.querySelectorAll('.anvil-column'));
                        columnIndex = siblings.indexOf(column);
                    }
                    
                    ds.dropColumn = { blockId: columnsBlock.dataset.blockId, columnIndex };
                    ds.dropIndex = -1;
                    
                    document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));
                    column.classList.add('drop-target');
                    this.hideDropIndicator();
                    return;
                }
            }
            
            document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));

            // Handle empty state
            const emptyState = blocksContainer.querySelector('.anvil-live-empty-state');
            if (emptyState) {
                emptyState.classList.add('drop-target');
                ds.dropIndex = 0;
                this.hideDropIndicator();
                return;
            }

            // Find drop position
            const blockElements = blocksContainer.querySelectorAll(':scope > .anvil-live-block');
            let foundDrop = false;
            const blocks = AL.state.blocks;

            for (let i = 0; i < blockElements.length; i++) {
                const blockEl = blockElements[i];
                const rect = blockEl.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;

                if (ds.type === 'move' && blockEl.dataset.blockId === ds.blockId) continue;

                if (e.clientY < midY) {
                    this.showDropIndicator(blockEl, 'above');
                    ds.dropIndex = i;
                    
                    if (ds.type === 'move') {
                        const currentIndex = blocks.findIndex(b => b.id === ds.blockId);
                        if (currentIndex !== -1 && currentIndex < i) ds.dropIndex = i - 1;
                    }
                    foundDrop = true;
                    break;
                }
            }

            if (!foundDrop && blockElements.length > 0) {
                const lastBlock = blockElements[blockElements.length - 1];
                this.showDropIndicator(lastBlock, 'below');
                ds.dropIndex = blocks.length;
                
                if (ds.type === 'move') {
                    const currentIndex = blocks.findIndex(b => b.id === ds.blockId);
                    if (currentIndex !== -1) ds.dropIndex = blocks.length - 1;
                }
            }
        },

        handleMouseUp: function(e) {
            const ds = AL.state.dragState;
            if (!ds.active) return;

            // Cleanup
            document.body.style.cursor = '';
            document.body.classList.remove('anvil-live-dragging');
            this.hideDropIndicator();
            document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));

            if (ds.ghost) {
                ds.ghost.remove();
                ds.ghost = null;
            }

            document.querySelectorAll('.anvil-live-block.dragging').forEach(el => el.classList.remove('dragging'));

            // Execute drop
            if (ds.dropColumn) {
                if (ds.type === 'new' && ds.blockType) {
                    this.addBlockToColumn(ds.blockType, ds.dropColumn.blockId, ds.dropColumn.columnIndex);
                } else if (ds.type === 'move' && ds.blockId) {
                    this.moveBlockToColumn(ds.blockId, ds.dropColumn.blockId, ds.dropColumn.columnIndex);
                }
            } else if (ds.dropIndex >= 0) {
                if (ds.type === 'new' && ds.blockType) {
                    AL.Blocks.addAt(ds.blockType, ds.dropIndex);
                } else if (ds.type === 'move' && ds.blockId) {
                    this.moveBlockToMain(ds.blockId, ds.dropIndex);
                }
            }

            // Reset state
            ds.active = false;
            ds.type = null;
            ds.blockType = null;
            ds.blockId = null;
            ds.dropIndex = -1;
            ds.dropColumn = null;
        },

        addBlockToColumn: function(type, parentBlockId, columnIndex) {
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
            
            this.insertBlockToColumn(parentBlockId, columnIndex, newBlock);
        },

        insertBlockToColumn: function(parentBlockId, columnIndex, newBlock) {
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
            AL.Blocks.render();
            
            setTimeout(() => {
                const el = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                const editable = el?.querySelector('[contenteditable]');
                if (editable) editable.focus();
                else AL.Blocks.select(newBlock.id);
            }, 50);
        },

        moveBlockToColumn: function(blockId, parentBlockId, columnIndex) {
            const block = this.removeBlockFromAnywhere(blockId);
            if (!block) return;
            
            const blocks = AL.state.blocks;
            const parentIndex = blocks.findIndex(b => b.id === parentBlockId);
            if (parentIndex === -1) {
                blocks.push(block);
                AL.Blocks.render();
                return;
            }
            
            const parentBlock = blocks[parentIndex];
            if (parentBlock.type !== 'columns') {
                blocks.push(block);
                AL.Blocks.render();
                return;
            }
            
            if (!Array.isArray(parentBlock.attributes.columns)) {
                parentBlock.attributes.columns = [];
            }
            
            while (parentBlock.attributes.columns.length <= columnIndex) {
                parentBlock.attributes.columns.push([]);
            }
            
            parentBlock.attributes.columns[columnIndex].push(block);
            
            AL.Save.saveState();
            AL.markDirty();
            AL.Blocks.render();
            AL.Blocks.select(blockId);
        },

        moveBlockToMain: function(blockId, toIndex) {
            const block = this.removeBlockFromAnywhere(blockId);
            if (!block) return;
            
            AL.state.blocks.splice(toIndex, 0, block);
            
            AL.Save.saveState();
            AL.markDirty();
            AL.Blocks.render();
            AL.Blocks.select(blockId);
        },

        removeBlockFromAnywhere: function(blockId) {
            const blocks = AL.state.blocks;
            
            // Check main array
            const mainIndex = blocks.findIndex(b => b.id === blockId);
            if (mainIndex !== -1) {
                return blocks.splice(mainIndex, 1)[0];
            }
            
            // Check columns
            for (let i = 0; i < blocks.length; i++) {
                if (blocks[i].type === 'columns' && Array.isArray(blocks[i].attributes?.columns)) {
                    for (let colIdx = 0; colIdx < blocks[i].attributes.columns.length; colIdx++) {
                        const column = blocks[i].attributes.columns[colIdx];
                        if (Array.isArray(column)) {
                            const blockIdx = column.findIndex(b => b.id === blockId);
                            if (blockIdx !== -1) {
                                return column.splice(blockIdx, 1)[0];
                            }
                        }
                    }
                }
            }
            
            return null;
        },

        moveBlock: function(blockId, toIndex) {
            const blocks = AL.state.blocks;
            const fromIndex = blocks.findIndex(b => b.id === blockId);
            if (fromIndex === -1 || fromIndex === toIndex) return;

            const [moved] = blocks.splice(fromIndex, 1);
            blocks.splice(fromIndex < toIndex ? toIndex : toIndex, 0, moved);

            AL.Save.saveState();
            AL.markDirty();
            AL.Blocks.render();
            AL.Blocks.select(blockId);
        }
    };

})();
