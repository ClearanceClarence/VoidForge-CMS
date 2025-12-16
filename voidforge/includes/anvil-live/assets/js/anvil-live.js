/**
 * Anvil Live - Frontend Visual Editor
 * VoidForge CMS v0.2.2
 * 
 * Inline editing with rich text toolbar and visual drag-drop
 */
(function() {
    'use strict';

    const config = window.AnvilLiveConfig || {};
    let blocks = window.AnvilLiveBlocks || [];
    let selectedBlockId = null;
    let isDirty = false;
    let undoStack = [];
    let redoStack = [];
    let autosaveTimer = null;
    let activeEditor = null;

    // Drag state
    let dragState = {
        active: false,
        type: null,        // 'new' (from sidebar) or 'move' (reorder)
        blockType: null,   // For new blocks
        blockId: null,     // For moving existing blocks
        dropIndex: -1,     // Where to drop
        dropColumn: null,  // Column drop target: { blockId, columnIndex }
        ghost: null,       // Drag preview element
        indicator: null    // Drop indicator element
    };

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    function init() {
        console.log('AnvilLive: Initializing editor');
        
        document.body.classList.add('anvil-live-editing', 'anvil-live-sidebar-open');
        
        createDropIndicator();
        createRichTextToolbar();
        createLinkPopup();
        initTopBar();
        initSidebar();
        initCanvas();
        initDragAndDrop();
        initInlineEditing();
        initModals();
        initKeyboardShortcuts();
        initAutosave();
        
        // Make initial PHP-rendered blocks editable
        makeBlocksEditable();
        
        saveState();
        
        console.log('AnvilLive: Editor ready with', blocks.length, 'blocks');
    }

    // =========================================================================
    // DROP INDICATOR
    // =========================================================================

    function createDropIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'anvil-live-drop-indicator';
        indicator.className = 'anvil-live-drop-indicator';
        document.body.appendChild(indicator);
        dragState.indicator = indicator;
    }

    function showDropIndicator(element, position) {
        const indicator = dragState.indicator;
        if (!indicator || !element) return;

        const rect = element.getBoundingClientRect();
        const container = document.getElementById('anvil-live-blocks');
        const containerRect = container.getBoundingClientRect();
        
        indicator.style.left = containerRect.left + 'px';
        indicator.style.width = containerRect.width + 'px';
        
        if (position === 'above') {
            indicator.style.top = (rect.top - 2 + window.scrollY) + 'px';
        } else {
            indicator.style.top = (rect.bottom - 2 + window.scrollY) + 'px';
        }
        
        indicator.classList.add('active');
    }

    function hideDropIndicator() {
        if (dragState.indicator) {
            dragState.indicator.classList.remove('active');
        }
        // Clear any drop zone classes
        document.querySelectorAll('.drop-above, .drop-below, .drop-target').forEach(el => {
            el.classList.remove('drop-above', 'drop-below', 'drop-target');
        });
    }

    // =========================================================================
    // DRAG AND DROP SYSTEM
    // =========================================================================

    function initDragAndDrop() {
        // Global mouse move for drag preview
        document.addEventListener('mousemove', handleGlobalMouseMove);
        document.addEventListener('mouseup', handleGlobalMouseUp);

        // Prevent default drag behavior
        document.addEventListener('dragover', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => e.preventDefault());
    }

    function createDragGhost(blockType, label) {
        const ghost = document.createElement('div');
        ghost.className = 'anvil-live-drag-ghost';
        ghost.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M12 8v8M8 12h8"/>
            </svg>
            <span>${escapeHtml(label)}</span>
        `;
        ghost.style.display = 'none';
        document.body.appendChild(ghost);
        return ghost;
    }

    function startDrag(type, blockType, blockId, label, e) {
        dragState.active = true;
        dragState.type = type;
        dragState.blockType = blockType;
        dragState.blockId = blockId;
        dragState.dropIndex = -1;
        
        // Create ghost
        dragState.ghost = createDragGhost(blockType, label);
        
        // Add dragging class
        if (type === 'move' && blockId) {
            const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
            if (blockEl) blockEl.classList.add('dragging');
        }
        
        document.body.style.cursor = 'grabbing';
        document.body.classList.add('anvil-live-dragging');
        
        // Trigger initial position
        handleGlobalMouseMove(e);
    }

    function handleGlobalMouseMove(e) {
        if (!dragState.active) return;

        // Update ghost position
        if (dragState.ghost) {
            dragState.ghost.style.display = 'flex';
            dragState.ghost.style.left = e.clientX + 'px';
            dragState.ghost.style.top = e.clientY + 'px';
        }

        // Reset drop targets
        dragState.dropColumn = null;
        
        // Find drop target
        const blocksContainer = document.getElementById('anvil-live-blocks');
        if (!blocksContainer) return;

        const containerRect = blocksContainer.getBoundingClientRect();
        
        // Check if mouse is over the blocks container
        if (e.clientX < containerRect.left || e.clientX > containerRect.right ||
            e.clientY < containerRect.top || e.clientY > containerRect.bottom) {
            hideDropIndicator();
            dragState.dropIndex = -1;
            return;
        }

        // First, check if we're over a column
        const elementUnderMouse = document.elementFromPoint(e.clientX, e.clientY);
        const column = elementUnderMouse?.closest('.anvil-column');
        
        if (column) {
            const columnsBlock = column.closest('.anvil-live-block[data-block-type="columns"]');
            if (columnsBlock) {
                // We're over a column - highlight it
                let columnIndex = parseInt(column.dataset.columnIndex);
                if (isNaN(columnIndex)) {
                    const siblings = Array.from(column.parentElement.querySelectorAll('.anvil-column'));
                    columnIndex = siblings.indexOf(column);
                }
                
                // Store column drop target
                dragState.dropColumn = {
                    blockId: columnsBlock.dataset.blockId,
                    columnIndex: columnIndex
                };
                dragState.dropIndex = -1; // Not dropping in main array
                
                // Highlight the column
                document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));
                column.classList.add('drop-target');
                hideDropIndicator();
                
                return;
            }
        }
        
        // Clear column highlights if not over a column
        document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));

        // Handle empty state
        const emptyState = blocksContainer.querySelector('.anvil-live-empty-state');
        if (emptyState) {
            emptyState.classList.add('drop-target');
            dragState.dropIndex = 0;
            hideDropIndicator();
            return;
        }

        // Find which block we're over (only top-level blocks)
        const blockElements = blocksContainer.querySelectorAll(':scope > .anvil-live-block');
        let foundDrop = false;

        for (let i = 0; i < blockElements.length; i++) {
            const blockEl = blockElements[i];
            const rect = blockEl.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;

            // Skip the block being dragged
            if (dragState.type === 'move' && blockEl.dataset.blockId === dragState.blockId) {
                continue;
            }

            if (e.clientY < midY) {
                // Drop above this block
                showDropIndicator(blockEl, 'above');
                dragState.dropIndex = i;
                
                // Adjust for moving blocks
                if (dragState.type === 'move') {
                    const currentIndex = blocks.findIndex(b => b.id === dragState.blockId);
                    if (currentIndex !== -1 && currentIndex < i) {
                        dragState.dropIndex = i - 1;
                    }
                }
                
                foundDrop = true;
                break;
            }
        }

        // If not found, drop at end
        if (!foundDrop && blockElements.length > 0) {
            const lastBlock = blockElements[blockElements.length - 1];
            showDropIndicator(lastBlock, 'below');
            dragState.dropIndex = blocks.length;
            
            if (dragState.type === 'move') {
                const currentIndex = blocks.findIndex(b => b.id === dragState.blockId);
                if (currentIndex !== -1) {
                    dragState.dropIndex = blocks.length - 1;
                }
            }
        }
    }

    function handleGlobalMouseUp(e) {
        if (!dragState.active) return;

        // Clean up
        document.body.style.cursor = '';
        document.body.classList.remove('anvil-live-dragging');
        hideDropIndicator();
        document.querySelectorAll('.anvil-column.drop-target').forEach(c => c.classList.remove('drop-target'));

        if (dragState.ghost) {
            dragState.ghost.remove();
            dragState.ghost = null;
        }

        document.querySelectorAll('.anvil-live-block.dragging').forEach(el => {
            el.classList.remove('dragging');
        });

        // Perform the drop action
        if (dragState.dropColumn) {
            if (dragState.type === 'new' && dragState.blockType) {
                // Dropping new block into a column
                console.log('AnvilLive: Dropping new block into column', dragState.dropColumn);
                addBlockToColumnDrop(dragState.blockType, dragState.dropColumn.blockId, dragState.dropColumn.columnIndex);
            } else if (dragState.type === 'move' && dragState.blockId) {
                // Moving existing block into a column
                console.log('AnvilLive: Moving block into column', dragState.blockId, dragState.dropColumn);
                moveBlockToColumn(dragState.blockId, dragState.dropColumn.blockId, dragState.dropColumn.columnIndex);
            }
        } else if (dragState.dropIndex >= 0) {
            if (dragState.type === 'new' && dragState.blockType) {
                // Add new block to main area
                addBlockAt(dragState.blockType, dragState.dropIndex);
            } else if (dragState.type === 'move' && dragState.blockId) {
                // Move existing block (could be from column to main or within main)
                moveBlockToMain(dragState.blockId, dragState.dropIndex);
            }
        }

        // Reset state
        dragState.active = false;
        dragState.type = null;
        dragState.blockType = null;
        dragState.blockId = null;
        dragState.dropIndex = -1;
        dragState.dropColumn = null;
    }
    
    // Add block to column via drag-drop
    function addBlockToColumnDrop(type, parentBlockId, columnIndex) {
        const blockDef = config.blocks?.blocks?.[type];
        if (!blockDef) return;

        const newBlock = { id: generateBlockId(), type, attributes: {} };
        
        const attrs = blockDef.attributes || {};
        for (const [key, attrDef] of Object.entries(attrs)) {
            newBlock.attributes[key] = attrDef.default ?? '';
        }
        
        // Special initialization for columns block (nested columns)
        if (type === 'columns') {
            const colCount = newBlock.attributes.columnCount || 2;
            newBlock.attributes.columns = [];
            for (let i = 0; i < colCount; i++) {
                newBlock.attributes.columns.push([]);
            }
        }
        
        addBlockToColumn(parentBlockId, columnIndex, newBlock);
    }
    
    // Move an existing block into a column
    function moveBlockToColumn(blockId, parentBlockId, columnIndex) {
        // First, find and remove the block from its current location
        const block = removeBlockFromAnywhere(blockId);
        if (!block) {
            console.error('AnvilLive: Block not found for moving:', blockId);
            return;
        }
        
        // Now add it to the target column
        const parentIndex = blocks.findIndex(b => b.id === parentBlockId);
        if (parentIndex === -1) {
            console.error('AnvilLive: Parent column block not found:', parentBlockId);
            // Put the block back in main array
            blocks.push(block);
            renderBlocks();
            return;
        }
        
        const parentBlock = blocks[parentIndex];
        if (parentBlock.type !== 'columns') {
            console.error('AnvilLive: Parent is not a columns block');
            blocks.push(block);
            renderBlocks();
            return;
        }
        
        // Ensure columns array exists
        if (!Array.isArray(parentBlock.attributes.columns)) {
            parentBlock.attributes.columns = [];
        }
        
        // Ensure the column exists
        while (parentBlock.attributes.columns.length <= columnIndex) {
            parentBlock.attributes.columns.push([]);
        }
        
        // Add block to column
        parentBlock.attributes.columns[columnIndex].push(block);
        
        saveState();
        markDirty();
        renderBlocks();
        selectBlock(blockId);
    }
    
    // Move a block to the main blocks array (possibly from a column)
    function moveBlockToMain(blockId, toIndex) {
        // First, find and remove the block from its current location
        const block = removeBlockFromAnywhere(blockId);
        if (!block) {
            console.error('AnvilLive: Block not found for moving:', blockId);
            return;
        }
        
        // Insert at the target position
        blocks.splice(toIndex, 0, block);
        
        saveState();
        markDirty();
        renderBlocks();
        selectBlock(blockId);
    }
    
    // Remove a block from anywhere (main array or any column) and return it
    function removeBlockFromAnywhere(blockId) {
        // Check main blocks array first
        const mainIndex = blocks.findIndex(b => b.id === blockId);
        if (mainIndex !== -1) {
            const [block] = blocks.splice(mainIndex, 1);
            return block;
        }
        
        // Check inside columns
        for (let i = 0; i < blocks.length; i++) {
            if (blocks[i].type === 'columns' && Array.isArray(blocks[i].attributes?.columns)) {
                for (let colIdx = 0; colIdx < blocks[i].attributes.columns.length; colIdx++) {
                    const column = blocks[i].attributes.columns[colIdx];
                    if (Array.isArray(column)) {
                        const blockIdx = column.findIndex(b => b.id === blockId);
                        if (blockIdx !== -1) {
                            const [block] = column.splice(blockIdx, 1);
                            return block;
                        }
                    }
                }
            }
        }
        
        return null;
    }

    function moveBlock(blockId, toIndex) {
        const fromIndex = blocks.findIndex(b => b.id === blockId);
        if (fromIndex === -1 || fromIndex === toIndex) return;

        const [moved] = blocks.splice(fromIndex, 1);
        
        // Adjust target index if needed
        const adjustedIndex = fromIndex < toIndex ? toIndex : toIndex;
        blocks.splice(adjustedIndex, 0, moved);

        saveState();
        markDirty();
        renderBlocks();
        selectBlock(blockId);
    }

    // =========================================================================
    // RICH TEXT TOOLBAR
    // =========================================================================

    function createRichTextToolbar() {
        const toolbar = document.createElement('div');
        toolbar.id = 'anvil-live-rte-toolbar';
        toolbar.className = 'anvil-live-rte-toolbar';
        toolbar.innerHTML = `
            <button type="button" class="anvil-live-rte-btn" data-command="bold" title="Bold (Ctrl+B)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="italic" title="Italic (Ctrl+I)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="underline" title="Underline (Ctrl+U)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="strikeThrough" title="Strikethrough">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.3 4.9c-2.3-.6-4.4-1-6.2-.9-2.7 0-5.3.7-5.3 3.6 0 1.5 1.8 3.3 3.6 3.9h.2"/><path d="M4 12h16"/><path d="M6.7 19.1c2.3.6 4.4 1 6.2.9 2.7 0 5.3-.7 5.3-3.6 0-1.5-1.8-3.3-3.6-3.9h-.2"/></svg>
            </button>
            <span class="anvil-live-rte-divider"></span>
            <button type="button" class="anvil-live-rte-btn" data-command="createLink" title="Insert Link (Ctrl+K)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="unlink" title="Remove Link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.84 12.25l1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
            <span class="anvil-live-rte-divider"></span>
            <button type="button" class="anvil-live-rte-btn" data-command="justifyLeft" title="Align Left">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="15" y2="12"/><line x1="3" y1="18" x2="18" y2="18"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="justifyCenter" title="Align Center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            </button>
            <button type="button" class="anvil-live-rte-btn" data-command="justifyRight" title="Align Right">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="6" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="anvil-live-rte-divider"></span>
            <button type="button" class="anvil-live-rte-btn" data-command="removeFormat" title="Clear Formatting">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
            </button>
        `;
        document.body.appendChild(toolbar);

        toolbar.querySelectorAll('.anvil-live-rte-btn').forEach(btn => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const command = btn.dataset.command;
                console.log('AnvilLive: RTE button clicked', { command, activeEditor: !!activeEditor });
                if (command === 'createLink') {
                    showLinkPopup();
                } else {
                    execCommand(command);
                }
                updateToolbarState();
            });
        });
    }

    function createLinkPopup() {
        const popup = document.createElement('div');
        popup.id = 'anvil-live-link-popup';
        popup.className = 'anvil-live-link-popup';
        popup.innerHTML = `
            <input type="text" id="anvil-live-link-url" placeholder="Enter URL (https://...)">
            <div class="anvil-live-link-popup-buttons">
                <button type="button" class="anvil-live-link-popup-btn secondary" id="anvil-live-link-cancel">Cancel</button>
                <button type="button" class="anvil-live-link-popup-btn" id="anvil-live-link-insert">Insert Link</button>
            </div>
        `;
        document.body.appendChild(popup);

        document.getElementById('anvil-live-link-cancel').addEventListener('click', hideLinkPopup);
        document.getElementById('anvil-live-link-insert').addEventListener('click', insertLink);
        document.getElementById('anvil-live-link-url').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); insertLink(); }
            else if (e.key === 'Escape') { hideLinkPopup(); }
        });
    }

    let savedSelection = null;

    function showLinkPopup() {
        savedSelection = saveSelection();
        
        const popup = document.getElementById('anvil-live-link-popup');
        const toolbar = document.getElementById('anvil-live-rte-toolbar');
        const input = document.getElementById('anvil-live-link-url');
        
        const toolbarRect = toolbar.getBoundingClientRect();
        popup.style.left = toolbarRect.left + 'px';
        popup.style.top = (toolbarRect.bottom + 8) + 'px';
        
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const anchor = selection.anchorNode.parentElement.closest('a');
            input.value = anchor ? anchor.href : '';
        }
        
        popup.classList.add('active');
        input.focus();
        input.select();
    }

    function hideLinkPopup() {
        document.getElementById('anvil-live-link-popup').classList.remove('active');
        if (savedSelection) restoreSelection(savedSelection);
    }

    function insertLink() {
        const url = document.getElementById('anvil-live-link-url').value.trim();
        hideLinkPopup();
        if (savedSelection) restoreSelection(savedSelection);
        
        if (url) {
            const finalUrl = /^https?:\/\//i.test(url) ? url : 'https://' + url;
            execCommand('createLink', finalUrl);
        }
        syncBlockContent();
    }

    function saveSelection() {
        const sel = window.getSelection();
        return sel.rangeCount > 0 ? sel.getRangeAt(0).cloneRange() : null;
    }

    function restoreSelection(range) {
        if (range) {
            const sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    function execCommand(command, value = null) {
        console.log('AnvilLive: execCommand called', { command, activeEditor: !!activeEditor });
        
        // Handle justify commands specially - apply to the block element
        if (command.startsWith('justify') && activeEditor) {
            const alignmentRaw = command.replace('justify', '');
            const alignment = alignmentRaw.charAt(0).toLowerCase() + alignmentRaw.slice(1).toLowerCase();
            console.log('AnvilLive: Applying alignment', { alignmentRaw, alignment });
            
            // Map justify commands to text-align values
            const alignMap = {
                'left': 'left',
                'center': 'center', 
                'right': 'right',
                'full': 'justify'
            };
            const textAlignValue = alignMap[alignment] || 'left';
            console.log('AnvilLive: Setting text-align to', textAlignValue);
            
            activeEditor.style.textAlign = textAlignValue;
            
            // Also update the block's align attribute
            const blockEl = activeEditor.closest('.anvil-live-block');
            if (blockEl) {
                const blockId = blockEl.dataset.blockId;
                const location = findBlockLocation(blockId);
                if (location) {
                    location.block.attributes = location.block.attributes || {};
                    location.block.attributes.align = textAlignValue;
                }
            }
            
            markDirty();
            updateToolbarState();
            return;
        }
        
        document.execCommand(command, false, value);
        syncBlockContent();
        markDirty();
    }

    function showToolbar(element) {
        console.log('AnvilLive: showToolbar called for element:', element);
        const toolbar = document.getElementById('anvil-live-rte-toolbar');
        if (!toolbar) {
            console.error('AnvilLive: Toolbar element not found!');
            return;
        }
        
        const rect = element.getBoundingClientRect();
        console.log('AnvilLive: Element rect:', rect);
        
        toolbar.style.left = (rect.left + rect.width / 2) + 'px';
        toolbar.style.top = (rect.top - 50 + window.scrollY) + 'px';
        toolbar.classList.add('active');
        
        console.log('AnvilLive: Toolbar positioned at', toolbar.style.left, toolbar.style.top);
        console.log('AnvilLive: Toolbar has active class:', toolbar.classList.contains('active'));
        
        updateToolbarState();
    }

    function hideToolbar() {
        document.getElementById('anvil-live-rte-toolbar').classList.remove('active');
        hideLinkPopup();
    }

    function updateToolbarState() {
        const toolbar = document.getElementById('anvil-live-rte-toolbar');
        if (!toolbar) return;
        
        toolbar.querySelectorAll('.anvil-live-rte-btn[data-command]').forEach(btn => {
            const command = btn.dataset.command;
            try {
                // Handle justify buttons specially
                if (command.startsWith('justify')) {
                    if (activeEditor) {
                        const alignmentRaw = command.replace('justify', '');
                        const alignment = alignmentRaw.charAt(0).toLowerCase() + alignmentRaw.slice(1).toLowerCase();
                        const currentAlign = activeEditor.style.textAlign || 'left';
                        btn.classList.toggle('active', currentAlign === alignment);
                    } else {
                        btn.classList.remove('active');
                    }
                } else {
                    btn.classList.toggle('active', document.queryCommandState(command));
                }
            } catch (e) {
                console.error('AnvilLive: Error updating toolbar state', e);
            }
        });
    }

    // =========================================================================
    // INLINE EDITING
    // =========================================================================

    function initInlineEditing() {
        document.addEventListener('click', (e) => {
            const editable = e.target.closest('[contenteditable="true"]');
            const blockContent = e.target.closest('.anvil-live-block-content[data-editable="true"]');
            
            if (editable && blockContent) {
                const blockEl = blockContent.closest('.anvil-live-block');
                if (blockEl) startEditing(blockEl, editable);
            }
        });

        document.addEventListener('selectionchange', () => {
            if (activeEditor) updateToolbarState();
        });

        makeBlocksEditable();
    }

    function makeBlocksEditable() {
        const editableContents = document.querySelectorAll('.anvil-live-block-content[data-editable="true"]');
        console.log('AnvilLive: makeBlocksEditable found', editableContents.length, 'editable blocks');
        
        editableContents.forEach(content => {
            const blockType = content.closest('.anvil-live-block')?.dataset.blockType;
            let editableEl = null;
            
            switch (blockType) {
                case 'paragraph': editableEl = content.querySelector('p'); break;
                case 'heading': editableEl = content.querySelector('h1, h2, h3, h4, h5, h6'); break;
                case 'quote': editableEl = content.querySelector('blockquote'); break;
                case 'button': editableEl = content.querySelector('a, button'); break;
                case 'list': editableEl = content.querySelector('ul, ol'); break;
            }
            
            console.log('AnvilLive: Block type', blockType, '- found element:', editableEl?.tagName);
            
            if (editableEl && !editableEl.hasAttribute('contenteditable')) {
                editableEl.setAttribute('contenteditable', 'true');
                editableEl.setAttribute('data-placeholder', getPlaceholder(blockType));
                
                editableEl.addEventListener('focus', handleEditableFocus);
                editableEl.addEventListener('blur', handleEditableBlur);
                editableEl.addEventListener('input', handleEditableInput);
                editableEl.addEventListener('keydown', handleEditableKeydown);
                editableEl.addEventListener('paste', handleEditablePaste);
                
                console.log('AnvilLive: Made element editable:', editableEl);
            }
        });
    }

    function getPlaceholder(blockType) {
        const placeholders = {
            paragraph: 'Type something...',
            heading: 'Heading',
            quote: 'Enter a quote...',
            button: 'Button text',
            list: 'List item'
        };
        return placeholders[blockType] || 'Type here...';
    }

    function startEditing(blockEl, editableEl) {
        console.log('AnvilLive: startEditing called', { blockEl, editableEl });
        if (activeEditor && activeEditor !== editableEl) endEditing();
        
        activeEditor = editableEl;
        blockEl.classList.add('editing');
        selectBlock(blockEl.dataset.blockId);
        showToolbar(editableEl);
    }

    function endEditing() {
        console.log('AnvilLive: endEditing called');
        if (activeEditor) {
            const blockEl = activeEditor.closest('.anvil-live-block');
            if (blockEl) blockEl.classList.remove('editing');
            syncBlockContent();
            activeEditor = null;
        }
        hideToolbar();
    }

    function handleEditableFocus(e) {
        console.log('AnvilLive: handleEditableFocus', e.target);
        const blockEl = e.target.closest('.anvil-live-block');
        if (blockEl) startEditing(blockEl, e.target);
    }

    function handleEditableBlur(e) {
        setTimeout(() => {
            const toolbar = document.getElementById('anvil-live-rte-toolbar');
            const linkPopup = document.getElementById('anvil-live-link-popup');
            
            if (!toolbar.contains(document.activeElement) && 
                !linkPopup.contains(document.activeElement) &&
                document.activeElement !== e.target) {
                endEditing();
            }
        }, 100);
    }

    function handleEditableInput(e) {
        markDirty();
        syncBlockContent();
        if (activeEditor === e.target) showToolbar(e.target);
    }

    function handleEditableKeydown(e) {
        if (e.ctrlKey || e.metaKey) {
            switch (e.key.toLowerCase()) {
                case 'b': e.preventDefault(); execCommand('bold'); break;
                case 'i': e.preventDefault(); execCommand('italic'); break;
                case 'u': e.preventDefault(); execCommand('underline'); break;
                case 'k': e.preventDefault(); showLinkPopup(); break;
            }
        }
        
        if (e.key === 'Enter' && !e.shiftKey) {
            const blockEl = e.target.closest('.anvil-live-block');
            const blockType = blockEl?.dataset.blockType;
            
            if (blockType === 'paragraph' || blockType === 'heading') {
                e.preventDefault();
                
                const selection = window.getSelection();
                const range = selection.getRangeAt(0);
                const afterContent = range.extractContents();
                
                syncBlockContent();
                
                const blockIndex = blocks.findIndex(b => b.id === blockEl.dataset.blockId);
                const newBlock = {
                    id: generateBlockId(),
                    type: 'paragraph',
                    attributes: { content: '' }
                };
                
                blocks.splice(blockIndex + 1, 0, newBlock);
                saveState();
                markDirty();
                renderBlocks();
                
                setTimeout(() => {
                    const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                    const newEditable = newBlockEl?.querySelector('[contenteditable]');
                    if (newEditable) {
                        if (afterContent.textContent) {
                            newEditable.appendChild(afterContent);
                            syncBlockContent();
                        }
                        newEditable.focus();
                        const r = document.createRange();
                        r.setStart(newEditable, 0);
                        r.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(r);
                    }
                }, 50);
            }
        }
        
        if (e.key === 'Backspace') {
            const selection = window.getSelection();
            const range = selection.getRangeAt(0);
            
            if (range.collapsed && range.startOffset === 0) {
                const blockEl = e.target.closest('.anvil-live-block');
                const blockId = blockEl?.dataset.blockId;
                const blockIndex = blocks.findIndex(b => b.id === blockId);
                
                if (blockIndex > 0) {
                    e.preventDefault();
                    
                    const currentContent = e.target.innerHTML;
                    const prevBlock = blocks[blockIndex - 1];
                    
                    if (prevBlock.type === 'paragraph' || prevBlock.type === 'heading') {
                        prevBlock.attributes.content = (prevBlock.attributes.content || '') + currentContent;
                        blocks.splice(blockIndex, 1);
                        
                        saveState();
                        markDirty();
                        renderBlocks();
                        
                        setTimeout(() => {
                            const prevBlockEl = document.querySelector(`[data-block-id="${prevBlock.id}"]`);
                            const prevEditable = prevBlockEl?.querySelector('[contenteditable]');
                            if (prevEditable) {
                                prevEditable.focus();
                                const r = document.createRange();
                                r.selectNodeContents(prevEditable);
                                r.collapse(false);
                                selection.removeAllRanges();
                                selection.addRange(r);
                            }
                        }, 50);
                    }
                }
            }
        }
    }

    function handleEditablePaste(e) {
        e.preventDefault();
        
        let content = e.clipboardData.getData('text/html');
        
        if (content) {
            const temp = document.createElement('div');
            temp.innerHTML = content;
            temp.querySelectorAll('script, style, meta, link').forEach(el => el.remove());
            temp.querySelectorAll('*').forEach(el => {
                const href = el.getAttribute('href');
                Array.from(el.attributes).forEach(attr => {
                    if (attr.name !== 'href') el.removeAttribute(attr.name);
                });
            });
            content = temp.innerHTML;
        } else {
            content = e.clipboardData.getData('text/plain');
            content = escapeHtml(content).replace(/\n/g, '<br>');
        }
        
        document.execCommand('insertHTML', false, content);
        syncBlockContent();
        markDirty();
    }

    function syncBlockContent() {
        if (!activeEditor) return;
        
        const blockEl = activeEditor.closest('.anvil-live-block');
        if (!blockEl) return;
        
        const blockId = blockEl.dataset.blockId;
        
        // Find block anywhere (main array or columns)
        const location = findBlockLocation(blockId);
        if (!location) return;
        
        const block = location.block;
        
        if (block.type === 'button') {
            block.attributes.text = activeEditor.textContent;
        } else {
            block.attributes.content = activeEditor.innerHTML;
        }
        
        // Save text alignment if set
        if (activeEditor.style.textAlign) {
            block.attributes.align = activeEditor.style.textAlign;
        }
    }

    // =========================================================================
    // TOP BAR
    // =========================================================================

    function initTopBar() {
        document.getElementById('anvil-live-toggle-sidebar')?.addEventListener('click', () => {
            document.body.classList.toggle('anvil-live-sidebar-open');
        });

        document.getElementById('anvil-live-title')?.addEventListener('input', () => markDirty());
        document.getElementById('anvil-live-save')?.addEventListener('click', () => saveContent());
        document.getElementById('anvil-live-undo')?.addEventListener('click', () => undo());
        document.getElementById('anvil-live-redo')?.addEventListener('click', () => redo());

        document.querySelectorAll('.anvil-live-device-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.anvil-live-device-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.body.className = document.body.className.replace(/anvil-live-device-\w+/g, '');
                document.body.classList.add('anvil-live-device-' + btn.dataset.device);
            });
        });

        document.querySelector('.anvil-live-exit-btn')?.addEventListener('click', (e) => {
            if (isDirty) {
                e.preventDefault();
                showUnsavedModal();
            }
        });
    }

    // =========================================================================
    // SIDEBAR
    // =========================================================================

    function initSidebar() {
        document.querySelectorAll('.anvil-live-sidebar-tab').forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
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
            
            // Click to add at end (or in column context)
            item.addEventListener('click', (e) => {
                // Only process click if we weren't dragging
                if (isDragging) {
                    isDragging = false;
                    return;
                }
                
                const blockType = item.dataset.blockType;
                if (blockType) {
                    console.log('AnvilLive: Block clicked in sidebar:', blockType);
                    console.log('AnvilLive: Current column context:', getColumnContext());
                    
                    const insertAfter = window._anvilLiveInsertAfter;
                    window._anvilLiveInsertAfter = undefined;
                    addBlock(blockType, insertAfter !== undefined ? insertAfter : -1);
                }
            });

            // Mouse down - prepare for potential drag
            item.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                
                isDragging = false;
                startX = e.clientX;
                startY = e.clientY;
                
                const blockType = item.dataset.blockType;
                const label = item.querySelector('.anvil-live-block-item-label')?.textContent || blockType;
                
                // Start drag only after mouse moves
                const onMouseMove = (moveEvent) => {
                    const dx = Math.abs(moveEvent.clientX - startX);
                    const dy = Math.abs(moveEvent.clientY - startY);
                    
                    // Only start drag if moved more than 5px
                    if (dx > 5 || dy > 5) {
                        isDragging = true;
                        item.classList.add('dragging');
                        // Clear column context when dragging - will use drop position instead
                        clearColumnContext();
                        startDrag('new', blockType, null, label, moveEvent);
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
    }

    function switchTab(tabName) {
        document.querySelectorAll('.anvil-live-sidebar-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.tab === tabName);
        });
        document.querySelectorAll('.anvil-live-sidebar-panel').forEach(p => {
            p.classList.toggle('active', p.dataset.panel === tabName);
        });
    }

    // =========================================================================
    // CANVAS
    // =========================================================================

    function initCanvas() {
        const canvas = document.getElementById('anvil-live-canvas');
        if (!canvas) return;

        canvas.addEventListener('click', (e) => {
            console.log('AnvilLive: Canvas click on', e.target.className);
            
            if (e.target === canvas || e.target.classList.contains('anvil-live-blocks') || e.target.classList.contains('anvil-live-empty-state')) {
                endEditing();
                deselectAllBlocks();
                // Clear column context when clicking outside
                clearColumnContext();
            }
            
            if (e.target.closest('.anvil-live-empty-state')) {
                switchTab('blocks');
                document.body.classList.add('anvil-live-sidebar-open');
            }
            
            // Handle clicks on columns - check BEFORE checking for blocks
            const column = e.target.closest('.anvil-column');
            
            if (column) {
                const columnsBlock = column.closest('.anvil-live-block[data-block-type="columns"]');
                
                // Check if click is on a nested block INSIDE the column
                const clickedBlock = e.target.closest('.anvil-live-block');
                const isNestedBlockClick = clickedBlock && clickedBlock !== columnsBlock && column.contains(clickedBlock);
                
                // Only trigger if not clicking on a nested block inside the column
                if (columnsBlock && !isNestedBlockClick) {
                    const blockId = columnsBlock.dataset.blockId;
                    
                    // Get column index - from attribute or by position
                    let columnIndex = parseInt(column.dataset.columnIndex);
                    if (isNaN(columnIndex)) {
                        const siblings = Array.from(column.parentElement.querySelectorAll('.anvil-column'));
                        columnIndex = siblings.indexOf(column);
                    }
                    
                    console.log('AnvilLive: Setting column context', { blockId, columnIndex });
                    
                    // Store column context using dedicated function
                    setColumnContext(blockId, columnIndex);
                    
                    switchTab('blocks');
                    document.body.classList.add('anvil-live-sidebar-open');
                    
                    e.preventDefault();
                    e.stopPropagation();
                    return;
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

                if (action === 'edit') selectBlock(blockId);
                else if (action === 'duplicate') duplicateBlock(blockId);
                else if (action === 'delete') deleteBlock(blockId);
                return;
            }

            const addBetweenBtn = e.target.closest('.anvil-live-add-between-btn');
            if (addBetweenBtn) {
                window._anvilLiveInsertAfter = parseInt(addBetweenBtn.dataset.afterIndex, 10);
                switchTab('blocks');
                document.body.classList.add('anvil-live-sidebar-open');
                document.getElementById('anvil-live-block-search')?.focus();
            }
        });

        // Block drag handles for reordering
        document.addEventListener('mousedown', (e) => {
            const handle = e.target.closest('.anvil-live-block-handle');
            if (handle && e.button === 0) {
                const blockEl = handle.closest('.anvil-live-block');
                if (blockEl) {
                    const blockId = blockEl.dataset.blockId;
                    const blockType = blockEl.dataset.blockType;
                    const blockDef = config.blocks?.blocks?.[blockType] || { label: blockType };
                    
                    startDrag('move', blockType, blockId, blockDef.label, e);
                    e.preventDefault();
                }
            }
        });
    }

    // =========================================================================
    // BLOCK OPERATIONS
    // =========================================================================

    function selectBlock(blockId) {
        deselectAllBlocks();
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (blockEl) {
            blockEl.classList.add('selected');
            selectedBlockId = blockId;
            showBlockSettings(blockId);
        }
    }

    function deselectAllBlocks() {
        document.querySelectorAll('.anvil-live-block.selected').forEach(el => el.classList.remove('selected'));
        selectedBlockId = null;
        
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
    }

    function showBlockSettings(blockId) {
        // Find block anywhere (main array or columns)
        const location = findBlockLocation(blockId);
        if (!location) return;
        
        const block = location.block;

        const blockDef = config.blocks?.blocks?.[block.type];
        if (!blockDef) return;

        const settingsPanel = document.getElementById('anvil-live-block-settings');
        if (!settingsPanel) return;

        let html = `<h4 style="margin:0 0 16px;font-size:14px;font-weight:600;">${escapeHtml(blockDef.label)} Settings</h4>`;

        const attrs = blockDef.attributes || {};
        for (const [key, attrDef] of Object.entries(attrs)) {
            // Skip content/text (edited inline) and complex arrays like columns
            if (key === 'content' || key === 'text' || key === 'columns') continue;
            
            const value = block.attributes?.[key] ?? attrDef.default ?? '';
            const label = formatLabel(key);

            html += `<div class="anvil-live-settings-group">`;
            html += `<label class="anvil-live-settings-label">${escapeHtml(label)}</label>`;

            if (attrDef.type === 'boolean') {
                html += `<label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" class="anvil-live-setting-input" data-attr="${escapeHtml(key)}" ${value ? 'checked' : ''}>
                    <span>Enable</span>
                </label>`;
            } else if (key === 'align') {
                html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                    <option value="left" ${value==='left'?'selected':''}>Left</option>
                    <option value="center" ${value==='center'?'selected':''}>Center</option>
                    <option value="right" ${value==='right'?'selected':''}>Right</option>
                </select>`;
            } else if (key === 'level') {
                html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                    ${[1,2,3,4,5,6].map(l => `<option value="${l}" ${value==l?'selected':''}>H${l}</option>`).join('')}
                </select>`;
            } else if (key === 'columnCount') {
                html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                    ${[2,3,4,5,6].map(n => `<option value="${n}" ${value==n?'selected':''}>${n} Columns</option>`).join('')}
                </select>`;
            } else if (key === 'verticalAlign') {
                html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                    <option value="top" ${value==='top'?'selected':''}>Top</option>
                    <option value="center" ${value==='center'?'selected':''}>Center</option>
                    <option value="bottom" ${value==='bottom'?'selected':''}>Bottom</option>
                </select>`;
            } else if (key === 'height' || key === 'width' || attrDef.type === 'number' || attrDef.type === 'integer') {
                html += `<input type="number" class="anvil-live-settings-input anvil-live-setting-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value))}">`;
            } else {
                html += `<input type="text" class="anvil-live-settings-input anvil-live-setting-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value))}">`;
            }

            html += `</div>`;
        }

        settingsPanel.innerHTML = html;

        settingsPanel.querySelectorAll('.anvil-live-setting-input').forEach(input => {
            const handler = () => {
                // Find block anywhere
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;

                const attr = input.dataset.attr;
                let value = input.type === 'checkbox' ? input.checked : 
                           input.type === 'number' ? parseInt(input.value) || 0 : input.value;

                block.attributes = block.attributes || {};
                
                // Special handling for columnCount - adjust columns array
                if (attr === 'columnCount') {
                    value = parseInt(value) || 2;
                    const currentColumns = block.attributes.columns || [];
                    const newColumns = [];
                    for (let i = 0; i < value; i++) {
                        newColumns.push(currentColumns[i] || []);
                    }
                    block.attributes.columns = newColumns;
                }
                
                block.attributes[attr] = value;

                markDirty();
                renderSingleBlock(blockId);
            };
            input.addEventListener('change', handler);
            if (input.type === 'text' || input.type === 'number') {
                input.addEventListener('input', handler);
            }
        });
    }

    function addBlock(type, afterIndex = -1) {
        console.log('AnvilLive: addBlock called', { type, afterIndex });
        
        const blockDef = config.blocks?.blocks?.[type];
        if (!blockDef) {
            console.error('AnvilLive: Block type not found:', type);
            return;
        }

        const newBlock = { id: generateBlockId(), type, attributes: {} };
        
        const attrs = blockDef.attributes || {};
        for (const [key, attrDef] of Object.entries(attrs)) {
            newBlock.attributes[key] = attrDef.default ?? '';
        }
        
        // Special initialization for columns block
        if (type === 'columns') {
            const colCount = newBlock.attributes.columnCount || 2;
            newBlock.attributes.columns = [];
            for (let i = 0; i < colCount; i++) {
                newBlock.attributes.columns.push([]);
            }
        }
        
        // Check if we're adding to a column - use helper function
        const columnContext = getColumnContext();
        console.log('AnvilLive: Column context at addBlock:', columnContext);
        
        if (columnContext && columnContext.blockId) {
            console.log('AnvilLive: Adding to column context:', columnContext);
            clearColumnContext();
            addBlockToColumn(columnContext.blockId, columnContext.columnIndex, newBlock);
            return;
        }

        console.log('AnvilLive: Adding to main blocks array');
        
        if (afterIndex >= 0 && afterIndex < blocks.length) {
            blocks.splice(afterIndex + 1, 0, newBlock);
        } else {
            blocks.push(newBlock);
        }

        saveState();
        markDirty();
        renderBlocks();

        setTimeout(() => {
            const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
            const editable = newBlockEl?.querySelector('[contenteditable]');
            if (editable) {
                editable.focus();
            } else {
                selectBlock(newBlock.id);
            }
        }, 50);
    }
    
    function addBlockToColumn(parentBlockId, columnIndex, newBlock) {
        console.log('AnvilLive: Adding block to column', { parentBlockId, columnIndex, newBlock });
        
        const parentIndex = blocks.findIndex(b => b.id === parentBlockId);
        if (parentIndex === -1) {
            console.error('AnvilLive: Parent block not found:', parentBlockId);
            return;
        }
        
        const parentBlock = blocks[parentIndex];
        if (parentBlock.type !== 'columns') {
            console.error('AnvilLive: Parent is not a columns block:', parentBlock.type);
            return;
        }
        
        // Ensure columns array exists
        if (!Array.isArray(parentBlock.attributes.columns)) {
            parentBlock.attributes.columns = [];
        }
        
        // Ensure the column exists
        while (parentBlock.attributes.columns.length <= columnIndex) {
            parentBlock.attributes.columns.push([]);
        }
        
        // Add block to column
        parentBlock.attributes.columns[columnIndex].push(newBlock);
        
        console.log('AnvilLive: Block added to column, new columns state:', parentBlock.attributes.columns);
        
        saveState();
        markDirty();
        renderBlocks();
        
        setTimeout(() => {
            const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
            const editable = newBlockEl?.querySelector('[contenteditable]');
            if (editable) {
                editable.focus();
            } else {
                selectBlock(newBlock.id);
            }
        }, 50);
    }

    function addBlockAt(type, index) {
        const blockDef = config.blocks?.blocks?.[type];
        if (!blockDef) return;

        const newBlock = { id: generateBlockId(), type, attributes: {} };
        
        const attrs = blockDef.attributes || {};
        for (const [key, attrDef] of Object.entries(attrs)) {
            newBlock.attributes[key] = attrDef.default ?? '';
        }
        
        // Special initialization for columns block
        if (type === 'columns') {
            const colCount = newBlock.attributes.columnCount || 2;
            newBlock.attributes.columns = [];
            for (let i = 0; i < colCount; i++) {
                newBlock.attributes.columns.push([]);
            }
        }

        blocks.splice(index, 0, newBlock);

        saveState();
        markDirty();
        renderBlocks();

        setTimeout(() => {
            const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
            const editable = newBlockEl?.querySelector('[contenteditable]');
            if (editable) {
                editable.focus();
            } else {
                selectBlock(newBlock.id);
            }
        }, 50);
    }

    function duplicateBlock(blockId) {
        // Find block anywhere (main array or columns)
        const location = findBlockLocation(blockId);
        if (!location) return;
        
        const originalBlock = location.block;
        const newBlock = {
            id: generateBlockId(),
            type: originalBlock.type,
            attributes: JSON.parse(JSON.stringify(originalBlock.attributes || {}))
        };
        
        // Clear nested block IDs if it's a columns block
        if (newBlock.type === 'columns' && Array.isArray(newBlock.attributes.columns)) {
            newBlock.attributes.columns = newBlock.attributes.columns.map(col => 
                Array.isArray(col) ? col.map(b => ({...b, id: generateBlockId()})) : []
            );
        }

        if (location.type === 'main') {
            blocks.splice(location.index + 1, 0, newBlock);
        } else if (location.type === 'column') {
            blocks[location.parentIndex].attributes.columns[location.columnIndex].splice(location.index + 1, 0, newBlock);
        }
        
        saveState();
        markDirty();
        renderBlocks();
        selectBlock(newBlock.id);
    }

    function deleteBlock(blockId) {
        // Find and remove block from anywhere
        const removed = removeBlockFromAnywhere(blockId);
        if (!removed) return;
        
        if (selectedBlockId === blockId) deselectAllBlocks();
        
        saveState();
        markDirty();
        renderBlocks();
    }
    
    // Find a block's location (main array or inside a column)
    function findBlockLocation(blockId) {
        // Check main blocks array
        const mainIndex = blocks.findIndex(b => b.id === blockId);
        if (mainIndex !== -1) {
            return { type: 'main', index: mainIndex, block: blocks[mainIndex] };
        }
        
        // Check inside columns
        for (let i = 0; i < blocks.length; i++) {
            if (blocks[i].type === 'columns' && Array.isArray(blocks[i].attributes?.columns)) {
                for (let colIdx = 0; colIdx < blocks[i].attributes.columns.length; colIdx++) {
                    const column = blocks[i].attributes.columns[colIdx];
                    if (Array.isArray(column)) {
                        const blockIdx = column.findIndex(b => b.id === blockId);
                        if (blockIdx !== -1) {
                            return { 
                                type: 'column', 
                                parentIndex: i, 
                                columnIndex: colIdx, 
                                index: blockIdx, 
                                block: column[blockIdx] 
                            };
                        }
                    }
                }
            }
        }
        
        return null;
    }

    // =========================================================================
    // RENDERING
    // =========================================================================

    function renderBlocks() {
        const container = document.getElementById('anvil-live-blocks');
        if (!container) return;

        endEditing();

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

        container.innerHTML = blocks.map((block, index) => renderBlockHTML(block, index)).join('');
        
        makeBlocksEditable();
    }

    function renderSingleBlock(blockId) {
        const block = blocks.find(b => b.id === blockId);
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (!block || !blockEl) return;

        const index = blocks.findIndex(b => b.id === blockId);
        const temp = document.createElement('div');
        temp.innerHTML = renderBlockHTML(block, index);
        const newBlockEl = temp.firstElementChild;

        blockEl.replaceWith(newBlockEl);
        makeBlocksEditable();
        
        if (selectedBlockId === blockId) {
            newBlockEl.classList.add('selected');
        }
    }

    function renderBlockHTML(block, index) {
        const type = block.type;
        const id = block.id;
        const blockDef = config.blocks?.blocks?.[type] || { label: type };
        const attrs = block.attributes || {};
        const isEditable = ['paragraph', 'heading', 'quote', 'list', 'button'].includes(type);
        const textAlign = attrs.align ? `text-align:${attrs.align};` : '';

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
                        colContent = colBlocks.map((b, idx) => renderBlockHTML(b, idx)).join('');
                    }
                    
                    columnsHtml += `<div class="anvil-column" data-column-index="${i}">${colContent}</div>`;
                }
                
                content = `<div class="anvil-columns" style="display:grid;grid-template-columns:repeat(${columnCount},1fr);gap:24px;align-items:${alignValue};">${columnsHtml}</div>`;
                break;
            default:
                content = `<div style="padding:20px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;text-align:center;color:#64748b;">${escapeHtml(type)} block</div>`;
        }

        return `
            <div class="anvil-live-block" data-block-id="${escapeHtml(id)}" data-block-type="${escapeHtml(type)}" data-block-index="${index}">
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
    }

    // =========================================================================
    // MODALS
    // =========================================================================

    function initModals() {
        document.querySelectorAll('.anvil-live-modal-close').forEach(btn => {
            btn.addEventListener('click', closeAllModals);
        });

        document.querySelectorAll('.anvil-live-modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeAllModals();
            });
        });

        document.getElementById('anvil-live-discard-changes')?.addEventListener('click', () => {
            window.location.href = config.exitUrl;
        });
        document.getElementById('anvil-live-save-and-exit')?.addEventListener('click', async () => {
            await saveContent();
            window.location.href = config.exitUrl;
        });
    }

    function showUnsavedModal() {
        document.getElementById('anvil-live-unsaved-modal')?.classList.add('active');
    }

    function closeAllModals() {
        document.querySelectorAll('.anvil-live-modal.active').forEach(m => m.classList.remove('active'));
    }

    // =========================================================================
    // SAVE & AUTOSAVE
    // =========================================================================

    function markDirty() {
        isDirty = true;
        updateSaveStatus('Unsaved changes', '');
        updateUndoRedoButtons();

        if (autosaveTimer) clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(autosave, 30000);
    }

    function updateSaveStatus(text, className) {
        const status = document.getElementById('anvil-live-save-status');
        if (status) {
            status.className = 'anvil-live-save-status ' + className;
            const textEl = status.querySelector('.anvil-live-save-status-text');
            if (textEl) textEl.textContent = text;
        }
    }

    async function saveContent() {
        if (activeEditor) syncBlockContent();
        
        updateSaveStatus('Saving...', 'saving');

        const title = document.getElementById('anvil-live-title')?.value || config.postTitle;

        try {
            const response = await fetch(config.apiUrl + '/anvil-live/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': config.nonce },
                body: JSON.stringify({ post_id: config.postId, blocks, title })
            });

            const data = await response.json();

            if (data.success) {
                isDirty = false;
                updateSaveStatus('Saved', 'saved');
            } else {
                updateSaveStatus('Error: ' + (data.error || 'Unknown'), 'error');
            }
        } catch (err) {
            console.error('Save failed:', err);
            updateSaveStatus('Save failed', 'error');
        }
    }

    async function autosave() {
        if (!isDirty) return;
        if (activeEditor) syncBlockContent();

        try {
            await fetch(config.apiUrl + '/anvil-live/autosave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': config.nonce },
                body: JSON.stringify({ post_id: config.postId, blocks })
            });
        } catch (err) {
            console.error('Autosave failed:', err);
        }
    }

    function initAutosave() {
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    // =========================================================================
    // UNDO / REDO
    // =========================================================================

    function saveState() {
        undoStack.push(JSON.stringify(blocks));
        if (undoStack.length > 50) undoStack.shift();
        redoStack = [];
        updateUndoRedoButtons();
    }

    function undo() {
        if (undoStack.length < 2) return;
        redoStack.push(undoStack.pop());
        blocks = JSON.parse(undoStack[undoStack.length - 1]);
        markDirty();
        renderBlocks();
    }

    function redo() {
        if (redoStack.length === 0) return;
        const state = redoStack.pop();
        undoStack.push(state);
        blocks = JSON.parse(state);
        markDirty();
        renderBlocks();
    }

    function updateUndoRedoButtons() {
        const undoBtn = document.getElementById('anvil-live-undo');
        const redoBtn = document.getElementById('anvil-live-redo');
        if (undoBtn) undoBtn.disabled = undoStack.length < 2;
        if (redoBtn) redoBtn.disabled = redoStack.length === 0;
    }

    // =========================================================================
    // KEYBOARD SHORTCUTS
    // =========================================================================

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveContent();
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey && !activeEditor) {
                e.preventDefault();
                undo();
            }

            if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey)) && !activeEditor) {
                e.preventDefault();
                redo();
            }

            if ((e.key === 'Delete' || e.key === 'Backspace') && selectedBlockId && !activeEditor) {
                const active = document.activeElement;
                if (!active || (active.tagName !== 'INPUT' && active.tagName !== 'TEXTAREA' && !active.isContentEditable)) {
                    e.preventDefault();
                    deleteBlock(selectedBlockId);
                }
            }

            if (e.key === 'Escape') {
                endEditing();
                deselectAllBlocks();
                closeAllModals();
            }
        });
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    function generateBlockId() {
        return 'block-' + Math.random().toString(36).substr(2, 12);
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return str;
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatLabel(key) {
        return key.replace(/([A-Z])/g, ' $1').replace(/[_-]/g, ' ').replace(/^\w/, c => c.toUpperCase()).trim();
    }

    // Column context management - store in DOM to persist across events
    function setColumnContext(blockId, columnIndex) {
        const sidebar = document.getElementById('anvil-live-sidebar');
        if (sidebar) {
            sidebar.dataset.columnBlockId = blockId;
            sidebar.dataset.columnIndex = columnIndex;
            sidebar.classList.add('adding-to-column');
            console.log('AnvilLive: Column context stored in sidebar', { blockId, columnIndex });
        }
        // Also store in window as backup
        window._anvilLiveColumnContext = { blockId, columnIndex };
    }
    
    function getColumnContext() {
        // Try sidebar first
        const sidebar = document.getElementById('anvil-live-sidebar');
        if (sidebar && sidebar.dataset.columnBlockId) {
            return {
                blockId: sidebar.dataset.columnBlockId,
                columnIndex: parseInt(sidebar.dataset.columnIndex) || 0
            };
        }
        // Fallback to window
        return window._anvilLiveColumnContext || null;
    }
    
    function clearColumnContext() {
        const sidebar = document.getElementById('anvil-live-sidebar');
        if (sidebar) {
            delete sidebar.dataset.columnBlockId;
            delete sidebar.dataset.columnIndex;
            sidebar.classList.remove('adding-to-column');
        }
        window._anvilLiveColumnContext = undefined;
        console.log('AnvilLive: Column context cleared');
    }

    // =========================================================================
    // INITIALIZE
    // =========================================================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
