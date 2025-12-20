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
    let pageSettings = config.pageSettings || {};

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
        initPageSettings();
        
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
        document.getElementById('anvil-live-preview')?.addEventListener('click', () => previewContent());
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
                const nestedBlock = e.target.closest('.anvil-live-block');
                const isNestedBlockClick = nestedBlock && nestedBlock !== columnsBlock && column.contains(nestedBlock);
                
                // If clicking on a nested block, select it and show settings
                if (isNestedBlockClick) {
                    const blockId = nestedBlock.dataset.blockId;
                    if (blockId && !e.target.closest('.anvil-live-block-action') && !e.target.closest('.anvil-live-block-handle')) {
                        console.log('AnvilLive: Clicking on nested block in column', blockId);
                        selectBlock(blockId);
                        switchTab('settings');
                        document.body.classList.add('anvil-live-sidebar-open');
                    }
                    return;
                }
                
                // Only trigger column context if not clicking on a nested block
                if (columnsBlock) {
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
            
            // Handle clicking on a block to select it and show settings
            const clickedBlock = e.target.closest('.anvil-live-block');
            if (clickedBlock && !e.target.closest('.anvil-live-block-action') && !e.target.closest('.anvil-live-block-handle')) {
                const blockId = clickedBlock.dataset.blockId;
                if (blockId) {
                    selectBlock(blockId);
                    switchTab('settings');
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
            switchTab('settings');
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

        const attrs = blockDef.attributes || {};
        
        // Blocks that edit content inline (skip content field in settings)
        const inlineContentBlocks = ['paragraph', 'heading', 'list', 'quote', 'code'];
        const skipContent = inlineContentBlocks.includes(block.type);
        
        // Categorize attributes into tabs
        const contentAttrs = [];
        const styleAttrs = [];
        
        // Style-related keys
        const styleKeys = ['style', 'size', 'align', 'verticalAlign', 'iconColor', 'color', 'backgroundColor'];
        
        for (const [key, attrDef] of Object.entries(attrs)) {
            if ((key === 'content' || key === 'text') && skipContent) continue;
            if (key === 'columns' || key === 'items') continue;
            
            if (styleKeys.includes(key) || key.toLowerCase().includes('color') || key.toLowerCase().includes('style')) {
                styleAttrs.push({ key, attrDef });
            } else {
                contentAttrs.push({ key, attrDef });
            }
        }

        // Build tabs HTML
        let html = `
            <div class="anvil-live-settings-tabs">
                <button type="button" class="anvil-live-settings-tab active" data-settings-tab="content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Content
                </button>
                <button type="button" class="anvil-live-settings-tab" data-settings-tab="style">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                    Style
                </button>
                <button type="button" class="anvil-live-settings-tab" data-settings-tab="advanced">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Advanced
                </button>
            </div>
            
            <div class="anvil-live-settings-content">
                <!-- Content Tab -->
                <div class="anvil-live-settings-section active" data-settings-section="content">
                    <div class="anvil-live-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/></svg>
                        ${escapeHtml(blockDef.label)}
                    </div>
        `;
        
        // Render content attributes
        if (contentAttrs.length > 0) {
            contentAttrs.forEach(({ key, attrDef }) => {
                html += renderSettingField(key, attrDef, block);
            });
        } else {
            html += '<p style="color:#6b7280;font-size:13px;text-align:center;padding:20px 0;">No content settings available</p>';
        }
        
        // Special handling for accordion items in content tab
        if (block.type === 'accordion') {
            html += renderAccordionItemsEditor(block);
        }
        
        html += `
                </div>
                
                <!-- Style Tab -->
                <div class="anvil-live-settings-section" data-settings-section="style">
        `;
        
        // Block-specific style attributes first
        if (styleAttrs.length > 0) {
            html += `<div class="anvil-live-section-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                Block Style
            </div>`;
            styleAttrs.forEach(({ key, attrDef }) => {
                html += renderSettingField(key, attrDef, block);
            });
        }
        
        // Typography Section
        html += renderTypographyControls(block);
        
        // Colors Section
        html += renderColorControls(block);
        
        // Border Section
        html += renderBorderControls(block);
        
        // Box Shadow Section
        html += renderBoxShadowControls(block);
        
        // Background controls
        html += renderBackgroundControls(block);
        
        html += `
                </div>
                
                <!-- Advanced Tab -->
                <div class="anvil-live-settings-section" data-settings-section="advanced">
                    <div class="anvil-live-section-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
                        Layout
                    </div>
                    ${renderSpacingControls(block)}
                    
                    ${renderResponsiveControls(block)}
                    
                    ${renderSizingControls(block)}
                    
                    ${renderAnimationControls(block)}
                    
                    ${renderTransformControls(block)}
                    
                    ${renderCustomAttributesControls(block)}
                </div>
            </div>
        `;

        settingsPanel.innerHTML = html;
        
        // Bind tab switching
        settingsPanel.querySelectorAll('.anvil-live-settings-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.settingsTab;
                settingsPanel.querySelectorAll('.anvil-live-settings-tab').forEach(t => t.classList.toggle('active', t === tab));
                settingsPanel.querySelectorAll('.anvil-live-settings-section').forEach(s => s.classList.toggle('active', s.dataset.settingsSection === tabName));
            });
        });
        
        // Bind accordion item handlers if present
        if (block.type === 'accordion') {
            bindAccordionItemHandlers(blockId);
        }
        
        // Bind spacing control handlers
        bindSpacingHandlers(blockId);
        
        // Bind all style control handlers
        bindStyleControlHandlers(blockId);
        
        // Initialize attribute-based color pickers
        initializeAttrColorPickers(blockId);

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
            if (input.type === 'text' || input.type === 'number' || input.type === 'color' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', handler);
            }
        });
    }
    
    // =========================================================================
    // TYPOGRAPHY CONTROLS
    // =========================================================================
    
    function renderTypographyControls(block) {
        const typography = block.attributes?.typography || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                Typography
            </div>
            <div class="anvil-style-controls" data-control-group="typography">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Font Size</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="typography" data-style-prop="fontSize" value="${typography.fontSize || ''}" placeholder="16" style="flex:1;">
                            <select class="anvil-live-settings-select anvil-style-input" data-style-group="typography" data-style-prop="fontSizeUnit" style="width:60px;">
                                <option value="px" ${(typography.fontSizeUnit || 'px') === 'px' ? 'selected' : ''}>px</option>
                                <option value="em" ${typography.fontSizeUnit === 'em' ? 'selected' : ''}>em</option>
                                <option value="rem" ${typography.fontSizeUnit === 'rem' ? 'selected' : ''}>rem</option>
                                <option value="%" ${typography.fontSizeUnit === '%' ? 'selected' : ''}>%</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Font Weight</label>
                        <select class="anvil-live-settings-select anvil-style-input" data-style-group="typography" data-style-prop="fontWeight">
                            <option value="" ${!typography.fontWeight ? 'selected' : ''}>Default</option>
                            <option value="300" ${typography.fontWeight === '300' ? 'selected' : ''}>Light (300)</option>
                            <option value="400" ${typography.fontWeight === '400' ? 'selected' : ''}>Normal (400)</option>
                            <option value="500" ${typography.fontWeight === '500' ? 'selected' : ''}>Medium (500)</option>
                            <option value="600" ${typography.fontWeight === '600' ? 'selected' : ''}>Semi Bold (600)</option>
                            <option value="700" ${typography.fontWeight === '700' ? 'selected' : ''}>Bold (700)</option>
                            <option value="800" ${typography.fontWeight === '800' ? 'selected' : ''}>Extra Bold (800)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Line Height</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="typography" data-style-prop="lineHeight" value="${typography.lineHeight || ''}" placeholder="1.5">
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Letter Spacing</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="typography" data-style-prop="letterSpacing" value="${typography.letterSpacing || ''}" placeholder="0" step="0.1" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">px</span>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Text Transform</label>
                        <select class="anvil-live-settings-select anvil-style-input" data-style-group="typography" data-style-prop="textTransform">
                            <option value="" ${!typography.textTransform ? 'selected' : ''}>Default</option>
                            <option value="uppercase" ${typography.textTransform === 'uppercase' ? 'selected' : ''}>UPPERCASE</option>
                            <option value="lowercase" ${typography.textTransform === 'lowercase' ? 'selected' : ''}>lowercase</option>
                            <option value="capitalize" ${typography.textTransform === 'capitalize' ? 'selected' : ''}>Capitalize</option>
                            <option value="none" ${typography.textTransform === 'none' ? 'selected' : ''}>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Font Style</label>
                        <select class="anvil-live-settings-select anvil-style-input" data-style-group="typography" data-style-prop="fontStyle">
                            <option value="" ${!typography.fontStyle ? 'selected' : ''}>Default</option>
                            <option value="normal" ${typography.fontStyle === 'normal' ? 'selected' : ''}>Normal</option>
                            <option value="italic" ${typography.fontStyle === 'italic' ? 'selected' : ''}>Italic</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // COLOR CONTROLS
    // =========================================================================
    
    function renderColorControls(block) {
        const colors = block.attributes?.colors || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                Colors
            </div>
            <div class="anvil-style-controls" data-control-group="colors">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Text Color</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="colors" data-style-prop="textColor" data-color="${colors.textColor || ''}"></div>
                            <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="colors" data-style-prop="textColor" value="${colors.textColor || ''}" placeholder="#000000" style="flex:1;">
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Background</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="colors" data-style-prop="backgroundColor" data-color="${colors.backgroundColor || ''}"></div>
                            <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="colors" data-style-prop="backgroundColor" value="${colors.backgroundColor || ''}" placeholder="transparent" style="flex:1;">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="anvil-live-settings-label">Link Color</label>
                    <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                        <div class="anvil-color-picker-trigger" data-style-group="colors" data-style-prop="linkColor" data-color="${colors.linkColor || ''}"></div>
                        <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="colors" data-style-prop="linkColor" value="${colors.linkColor || ''}" placeholder="#6366f1" style="flex:1;">
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // BORDER CONTROLS
    // =========================================================================
    
    function renderBorderControls(block) {
        const border = block.attributes?.border || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                Border
            </div>
            <div class="anvil-style-controls" data-control-group="border">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Border Style</label>
                        <select class="anvil-live-settings-select anvil-style-input" data-style-group="border" data-style-prop="style">
                            <option value="" ${!border.style ? 'selected' : ''}>None</option>
                            <option value="solid" ${border.style === 'solid' ? 'selected' : ''}>Solid</option>
                            <option value="dashed" ${border.style === 'dashed' ? 'selected' : ''}>Dashed</option>
                            <option value="dotted" ${border.style === 'dotted' ? 'selected' : ''}>Dotted</option>
                            <option value="double" ${border.style === 'double' ? 'selected' : ''}>Double</option>
                        </select>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Border Width</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="border" data-style-prop="width" value="${border.width || ''}" placeholder="1" min="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">px</span>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Border Color</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="border" data-style-prop="color" data-color="${border.color || ''}"></div>
                            <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="border" data-style-prop="color" value="${border.color || ''}" placeholder="#e2e8f0" style="flex:1;">
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Border Radius</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="border" data-style-prop="radius" value="${border.radius || ''}" placeholder="0" min="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">px</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // BOX SHADOW CONTROLS
    // =========================================================================
    
    function renderBoxShadowControls(block) {
        const shadow = block.attributes?.boxShadow || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M21 12h3M21 6h3M21 18h3"/></svg>
                Box Shadow
            </div>
            <div class="anvil-style-controls" data-control-group="boxShadow">
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Shadow Preset</label>
                    <select class="anvil-live-settings-select anvil-style-input" data-style-group="boxShadow" data-style-prop="preset">
                        <option value="" ${!shadow.preset ? 'selected' : ''}>None</option>
                        <option value="sm" ${shadow.preset === 'sm' ? 'selected' : ''}>Small</option>
                        <option value="md" ${shadow.preset === 'md' ? 'selected' : ''}>Medium</option>
                        <option value="lg" ${shadow.preset === 'lg' ? 'selected' : ''}>Large</option>
                        <option value="xl" ${shadow.preset === 'xl' ? 'selected' : ''}>Extra Large</option>
                        <option value="custom" ${shadow.preset === 'custom' ? 'selected' : ''}>Custom</option>
                    </select>
                </div>
                <div class="anvil-shadow-custom" style="display:${shadow.preset === 'custom' ? 'block' : 'none'};">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="anvil-live-settings-label">Horizontal</label>
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="boxShadow" data-style-prop="x" value="${shadow.x || '0'}" placeholder="0">
                        </div>
                        <div>
                            <label class="anvil-live-settings-label">Vertical</label>
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="boxShadow" data-style-prop="y" value="${shadow.y || '4'}" placeholder="4">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="anvil-live-settings-label">Blur</label>
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="boxShadow" data-style-prop="blur" value="${shadow.blur || '6'}" placeholder="6" min="0">
                        </div>
                        <div>
                            <label class="anvil-live-settings-label">Spread</label>
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="boxShadow" data-style-prop="spread" value="${shadow.spread || '0'}" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Shadow Color</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="boxShadow" data-style-prop="color" data-color="${shadow.color || ''}"></div>
                            <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="boxShadow" data-style-prop="color" value="${shadow.color || ''}" placeholder="rgba(0,0,0,0.1)" style="flex:1;">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // RESPONSIVE CONTROLS
    // =========================================================================
    
    function renderResponsiveControls(block) {
        const responsive = block.attributes?.responsive || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Responsive
            </div>
            <div class="anvil-style-controls" data-control-group="responsive">
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Visibility</label>
                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                        <label class="anvil-live-settings-checkbox">
                            <input type="checkbox" class="anvil-style-input" data-style-group="responsive" data-style-prop="hideDesktop" ${responsive.hideDesktop ? 'checked' : ''}>
                            <span style="display:flex;align-items:center;gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                Hide on Desktop
                            </span>
                        </label>
                        <label class="anvil-live-settings-checkbox">
                            <input type="checkbox" class="anvil-style-input" data-style-group="responsive" data-style-prop="hideTablet" ${responsive.hideTablet ? 'checked' : ''}>
                            <span style="display:flex;align-items:center;gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                Hide on Tablet
                            </span>
                        </label>
                        <label class="anvil-live-settings-checkbox">
                            <input type="checkbox" class="anvil-style-input" data-style-group="responsive" data-style-prop="hideMobile" ${responsive.hideMobile ? 'checked' : ''}>
                            <span style="display:flex;align-items:center;gap:8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                Hide on Mobile
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // CUSTOM ATTRIBUTES CONTROLS
    // =========================================================================
    
    function renderCustomAttributesControls(block) {
        const custom = block.attributes?.customAttributes || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 18l6-6-6-6"/><path d="M8 6l-6 6 6 6"/></svg>
                Attributes
            </div>
            <div class="anvil-style-controls" data-control-group="customAttributes">
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">CSS ID</label>
                    <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="customAttributes" data-style-prop="cssId" value="${custom.cssId || ''}" placeholder="my-element">
                </div>
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">CSS Classes</label>
                    <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="customAttributes" data-style-prop="cssClasses" value="${custom.cssClasses || ''}" placeholder="class-1 class-2">
                </div>
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Z-Index</label>
                    <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="customAttributes" data-style-prop="zIndex" value="${custom.zIndex || ''}" placeholder="auto">
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // BACKGROUND CONTROLS
    // =========================================================================
    
    function renderBackgroundControls(block) {
        const bg = block.attributes?.background || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                Background
            </div>
            <div class="anvil-style-controls" data-control-group="background">
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Background Type</label>
                    <select class="anvil-live-settings-select anvil-style-input anvil-bg-type-select" data-style-group="background" data-style-prop="type">
                        <option value="" ${!bg.type ? 'selected' : ''}>None</option>
                        <option value="color" ${bg.type === 'color' ? 'selected' : ''}>Color</option>
                        <option value="gradient" ${bg.type === 'gradient' ? 'selected' : ''}>Gradient</option>
                        <option value="image" ${bg.type === 'image' ? 'selected' : ''}>Image</option>
                    </select>
                </div>
                
                <div class="anvil-bg-color-controls" style="display:${bg.type === 'color' ? 'block' : 'none'};">
                    <div class="anvil-live-settings-group">
                        <label class="anvil-live-settings-label">Background Color</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="background" data-style-prop="color" data-color="${bg.color || ''}"></div>
                            <input type="text" class="anvil-live-settings-input anvil-style-input anvil-color-text-input" data-style-group="background" data-style-prop="color" value="${bg.color || ''}" placeholder="#ffffff" style="flex:1;">
                        </div>
                    </div>
                </div>
                
                <div class="anvil-bg-gradient-controls" style="display:${bg.type === 'gradient' ? 'block' : 'none'};">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="anvil-live-settings-label">Color 1</label>
                            <div class="anvil-color-picker-trigger" data-style-group="background" data-style-prop="gradientColor1" data-color="${bg.gradientColor1 || '#6366f1'}" style="width:100%;"></div>
                        </div>
                        <div>
                            <label class="anvil-live-settings-label">Color 2</label>
                            <div class="anvil-color-picker-trigger" data-style-group="background" data-style-prop="gradientColor2" data-color="${bg.gradientColor2 || '#a855f7'}" style="width:100%;"></div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div>
                            <label class="anvil-live-settings-label">Type</label>
                            <select class="anvil-live-settings-select anvil-style-input" data-style-group="background" data-style-prop="gradientType">
                                <option value="linear" ${(bg.gradientType || 'linear') === 'linear' ? 'selected' : ''}>Linear</option>
                                <option value="radial" ${bg.gradientType === 'radial' ? 'selected' : ''}>Radial</option>
                            </select>
                        </div>
                        <div>
                            <label class="anvil-live-settings-label">Angle</label>
                            <div style="display:flex;gap:4px;">
                                <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="background" data-style-prop="gradientAngle" value="${bg.gradientAngle || '135'}" min="0" max="360" style="flex:1;">
                                <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">°</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="anvil-bg-image-controls" style="display:${bg.type === 'image' ? 'block' : 'none'};">
                    <div class="anvil-live-settings-group">
                        <label class="anvil-live-settings-label">Image URL</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="background" data-style-prop="imageUrl" value="${bg.imageUrl || ''}" placeholder="https://...">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label class="anvil-live-settings-label">Position</label>
                            <select class="anvil-live-settings-select anvil-style-input" data-style-group="background" data-style-prop="imagePosition">
                                <option value="center center" ${(bg.imagePosition || 'center center') === 'center center' ? 'selected' : ''}>Center</option>
                                <option value="top left" ${bg.imagePosition === 'top left' ? 'selected' : ''}>Top Left</option>
                                <option value="top center" ${bg.imagePosition === 'top center' ? 'selected' : ''}>Top Center</option>
                                <option value="top right" ${bg.imagePosition === 'top right' ? 'selected' : ''}>Top Right</option>
                                <option value="center left" ${bg.imagePosition === 'center left' ? 'selected' : ''}>Center Left</option>
                                <option value="center right" ${bg.imagePosition === 'center right' ? 'selected' : ''}>Center Right</option>
                                <option value="bottom left" ${bg.imagePosition === 'bottom left' ? 'selected' : ''}>Bottom Left</option>
                                <option value="bottom center" ${bg.imagePosition === 'bottom center' ? 'selected' : ''}>Bottom Center</option>
                                <option value="bottom right" ${bg.imagePosition === 'bottom right' ? 'selected' : ''}>Bottom Right</option>
                            </select>
                        </div>
                        <div>
                            <label class="anvil-live-settings-label">Size</label>
                            <select class="anvil-live-settings-select anvil-style-input" data-style-group="background" data-style-prop="imageSize">
                                <option value="cover" ${(bg.imageSize || 'cover') === 'cover' ? 'selected' : ''}>Cover</option>
                                <option value="contain" ${bg.imageSize === 'contain' ? 'selected' : ''}>Contain</option>
                                <option value="auto" ${bg.imageSize === 'auto' ? 'selected' : ''}>Auto</option>
                            </select>
                        </div>
                    </div>
                    <div class="anvil-live-settings-group">
                        <label class="anvil-live-settings-label">Repeat</label>
                        <select class="anvil-live-settings-select anvil-style-input" data-style-group="background" data-style-prop="imageRepeat">
                            <option value="no-repeat" ${(bg.imageRepeat || 'no-repeat') === 'no-repeat' ? 'selected' : ''}>No Repeat</option>
                            <option value="repeat" ${bg.imageRepeat === 'repeat' ? 'selected' : ''}>Repeat</option>
                            <option value="repeat-x" ${bg.imageRepeat === 'repeat-x' ? 'selected' : ''}>Repeat X</option>
                            <option value="repeat-y" ${bg.imageRepeat === 'repeat-y' ? 'selected' : ''}>Repeat Y</option>
                        </select>
                    </div>
                    <div class="anvil-live-settings-group">
                        <label class="anvil-live-settings-label">Overlay Color</label>
                        <div class="anvil-color-field" style="display:flex;gap:8px;align-items:center;">
                            <div class="anvil-color-picker-trigger" data-style-group="background" data-style-prop="overlayColor" data-color="${bg.overlayColor || ''}"></div>
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="background" data-style-prop="overlayOpacity" value="${bg.overlayOpacity || ''}" placeholder="0.5" min="0" max="1" step="0.1" style="flex:1;">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // SIZING CONTROLS
    // =========================================================================
    
    function renderSizingControls(block) {
        const sizing = block.attributes?.sizing || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 3H3v18h18V3z"/><path d="M9 3v18"/><path d="M3 9h18"/></svg>
                Sizing
            </div>
            <div class="anvil-style-controls" data-control-group="sizing">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Width</label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="width" value="${sizing.width || ''}" placeholder="auto" style="flex:1;">
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Height</label>
                        <div style="display:flex;gap:4px;">
                            <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="height" value="${sizing.height || ''}" placeholder="auto" style="flex:1;">
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Max Width</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="maxWidth" value="${sizing.maxWidth || ''}" placeholder="none">
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Max Height</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="maxHeight" value="${sizing.maxHeight || ''}" placeholder="none">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Min Width</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="minWidth" value="${sizing.minWidth || ''}" placeholder="0">
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Min Height</label>
                        <input type="text" class="anvil-live-settings-input anvil-style-input" data-style-group="sizing" data-style-prop="minHeight" value="${sizing.minHeight || ''}" placeholder="0">
                    </div>
                </div>
                <div class="anvil-live-settings-group" style="margin-top:12px;">
                    <label class="anvil-live-settings-label">Overflow</label>
                    <select class="anvil-live-settings-select anvil-style-input" data-style-group="sizing" data-style-prop="overflow">
                        <option value="" ${!sizing.overflow ? 'selected' : ''}>Default</option>
                        <option value="visible" ${sizing.overflow === 'visible' ? 'selected' : ''}>Visible</option>
                        <option value="hidden" ${sizing.overflow === 'hidden' ? 'selected' : ''}>Hidden</option>
                        <option value="scroll" ${sizing.overflow === 'scroll' ? 'selected' : ''}>Scroll</option>
                        <option value="auto" ${sizing.overflow === 'auto' ? 'selected' : ''}>Auto</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // ANIMATION/MOTION CONTROLS
    // =========================================================================
    
    function renderAnimationControls(block) {
        const animation = block.attributes?.animation || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Motion Effects
            </div>
            <div class="anvil-style-controls" data-control-group="animation">
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Entrance Animation</label>
                    <select class="anvil-live-settings-select anvil-style-input" data-style-group="animation" data-style-prop="entrance">
                        <option value="" ${!animation.entrance ? 'selected' : ''}>None</option>
                        <optgroup label="Fade">
                            <option value="fadeIn" ${animation.entrance === 'fadeIn' ? 'selected' : ''}>Fade In</option>
                            <option value="fadeInUp" ${animation.entrance === 'fadeInUp' ? 'selected' : ''}>Fade In Up</option>
                            <option value="fadeInDown" ${animation.entrance === 'fadeInDown' ? 'selected' : ''}>Fade In Down</option>
                            <option value="fadeInLeft" ${animation.entrance === 'fadeInLeft' ? 'selected' : ''}>Fade In Left</option>
                            <option value="fadeInRight" ${animation.entrance === 'fadeInRight' ? 'selected' : ''}>Fade In Right</option>
                        </optgroup>
                        <optgroup label="Zoom">
                            <option value="zoomIn" ${animation.entrance === 'zoomIn' ? 'selected' : ''}>Zoom In</option>
                            <option value="zoomInUp" ${animation.entrance === 'zoomInUp' ? 'selected' : ''}>Zoom In Up</option>
                            <option value="zoomInDown" ${animation.entrance === 'zoomInDown' ? 'selected' : ''}>Zoom In Down</option>
                        </optgroup>
                        <optgroup label="Slide">
                            <option value="slideInUp" ${animation.entrance === 'slideInUp' ? 'selected' : ''}>Slide In Up</option>
                            <option value="slideInDown" ${animation.entrance === 'slideInDown' ? 'selected' : ''}>Slide In Down</option>
                            <option value="slideInLeft" ${animation.entrance === 'slideInLeft' ? 'selected' : ''}>Slide In Left</option>
                            <option value="slideInRight" ${animation.entrance === 'slideInRight' ? 'selected' : ''}>Slide In Right</option>
                        </optgroup>
                        <optgroup label="Bounce">
                            <option value="bounceIn" ${animation.entrance === 'bounceIn' ? 'selected' : ''}>Bounce In</option>
                            <option value="bounceInUp" ${animation.entrance === 'bounceInUp' ? 'selected' : ''}>Bounce In Up</option>
                        </optgroup>
                        <optgroup label="Rotate">
                            <option value="rotateIn" ${animation.entrance === 'rotateIn' ? 'selected' : ''}>Rotate In</option>
                            <option value="flipInX" ${animation.entrance === 'flipInX' ? 'selected' : ''}>Flip In X</option>
                            <option value="flipInY" ${animation.entrance === 'flipInY' ? 'selected' : ''}>Flip In Y</option>
                        </optgroup>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Duration (ms)</label>
                        <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="animation" data-style-prop="duration" value="${animation.duration || ''}" placeholder="1000" min="0" step="100">
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Delay (ms)</label>
                        <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="animation" data-style-prop="delay" value="${animation.delay || ''}" placeholder="0" min="0" step="100">
                    </div>
                </div>
                
                <div class="anvil-live-section-header" style="margin-top:16px;padding-top:16px;border-top:1px solid #3f4451;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    Hover Effects
                </div>
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Hover Animation</label>
                    <select class="anvil-live-settings-select anvil-style-input" data-style-group="animation" data-style-prop="hover">
                        <option value="" ${!animation.hover ? 'selected' : ''}>None</option>
                        <option value="grow" ${animation.hover === 'grow' ? 'selected' : ''}>Grow</option>
                        <option value="shrink" ${animation.hover === 'shrink' ? 'selected' : ''}>Shrink</option>
                        <option value="pulse" ${animation.hover === 'pulse' ? 'selected' : ''}>Pulse</option>
                        <option value="float" ${animation.hover === 'float' ? 'selected' : ''}>Float</option>
                        <option value="sink" ${animation.hover === 'sink' ? 'selected' : ''}>Sink</option>
                        <option value="rotate" ${animation.hover === 'rotate' ? 'selected' : ''}>Rotate</option>
                        <option value="shake" ${animation.hover === 'shake' ? 'selected' : ''}>Shake</option>
                        <option value="wobble" ${animation.hover === 'wobble' ? 'selected' : ''}>Wobble</option>
                        <option value="buzz" ${animation.hover === 'buzz' ? 'selected' : ''}>Buzz</option>
                    </select>
                </div>
                <div class="anvil-live-settings-group">
                    <label class="anvil-live-settings-label">Transition Duration</label>
                    <div style="display:flex;gap:4px;">
                        <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="animation" data-style-prop="transitionDuration" value="${animation.transitionDuration || ''}" placeholder="300" min="0" step="50" style="flex:1;">
                        <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">ms</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // TRANSFORM CONTROLS
    // =========================================================================
    
    function renderTransformControls(block) {
        const transform = block.attributes?.transform || {};
        
        return `
            <div class="anvil-live-section-header" style="margin-top:20px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                Transform
            </div>
            <div class="anvil-style-controls" data-control-group="transform">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Rotate</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="rotate" value="${transform.rotate || ''}" placeholder="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">°</span>
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Scale</label>
                        <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="scale" value="${transform.scale || ''}" placeholder="1" step="0.1" min="0">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Translate X</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="translateX" value="${transform.translateX || ''}" placeholder="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">px</span>
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Translate Y</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="translateY" value="${transform.translateY || ''}" placeholder="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">px</span>
                        </div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label class="anvil-live-settings-label">Skew X</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="skewX" value="${transform.skewX || ''}" placeholder="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">°</span>
                        </div>
                    </div>
                    <div>
                        <label class="anvil-live-settings-label">Skew Y</label>
                        <div style="display:flex;gap:4px;">
                            <input type="number" class="anvil-live-settings-input anvil-style-input" data-style-group="transform" data-style-prop="skewY" value="${transform.skewY || ''}" placeholder="0" style="flex:1;">
                            <span style="padding:8px 12px;background:#3f4451;border-radius:6px;color:#9ca3af;font-size:12px;">°</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // =========================================================================
    // STYLE CONTROL HANDLERS
    // =========================================================================
    
    function bindStyleControlHandlers(blockId) {
        // Initialize color pickers
        initializeColorPickers(blockId);
        
        // Handle all style inputs
        document.querySelectorAll('.anvil-style-input').forEach(input => {
            const handler = () => {
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                
                const group = input.dataset.styleGroup;
                const prop = input.dataset.styleProp;
                
                if (!group || !prop) return;
                
                // Initialize the group if it doesn't exist
                if (!block.attributes[group]) {
                    block.attributes[group] = {};
                }
                
                // Get the value based on input type
                let value;
                if (input.type === 'checkbox') {
                    value = input.checked;
                } else if (input.type === 'number') {
                    value = input.value ? parseFloat(input.value) : '';
                } else {
                    value = input.value;
                }
                
                block.attributes[group][prop] = value;
                
                // Special handling for box shadow preset
                if (group === 'boxShadow' && prop === 'preset') {
                    const customSection = document.querySelector('.anvil-shadow-custom');
                    if (customSection) {
                        customSection.style.display = value === 'custom' ? 'block' : 'none';
                    }
                }
                
                // Special handling for background type
                if (group === 'background' && prop === 'type') {
                    const colorControls = document.querySelector('.anvil-bg-color-controls');
                    const gradientControls = document.querySelector('.anvil-bg-gradient-controls');
                    const imageControls = document.querySelector('.anvil-bg-image-controls');
                    
                    if (colorControls) colorControls.style.display = value === 'color' ? 'block' : 'none';
                    if (gradientControls) gradientControls.style.display = value === 'gradient' ? 'block' : 'none';
                    if (imageControls) imageControls.style.display = value === 'image' ? 'block' : 'none';
                }
                
                markDirty();
                renderSingleBlock(blockId);
            };
            
            input.addEventListener('change', handler);
            if (input.type === 'text' || input.type === 'number' || input.type === 'color') {
                input.addEventListener('input', handler);
            }
        });
    }
    
    function initializeColorPickers(blockId) {
        document.querySelectorAll('.anvil-color-picker-trigger:not(.anvil-attr-color-trigger)').forEach(trigger => {
            if (trigger.dataset.initialized) return;
            trigger.dataset.initialized = 'true';
            
            const group = trigger.dataset.styleGroup;
            const prop = trigger.dataset.styleProp;
            const initialColor = trigger.dataset.color || '';
            
            // Skip if no style group (handled by initializeAttrColorPickers)
            if (!group) return;
            
            // Find the associated text input
            const textInput = trigger.parentElement?.querySelector('.anvil-color-text-input');
            
            AnvilColorPicker.create(trigger, initialColor, (color) => {
                // Update text input
                if (textInput) {
                    textInput.value = color;
                }
                
                // Update block attributes
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                
                if (!block.attributes[group]) {
                    block.attributes[group] = {};
                }
                
                block.attributes[group][prop] = color;
                
                markDirty();
                renderSingleBlock(blockId);
            });
        });
        
        // Also bind text input changes to sync with color picker
        document.querySelectorAll('.anvil-color-text-input').forEach(input => {
            input.addEventListener('change', () => {
                const trigger = input.parentElement.querySelector('.anvil-color-picker-trigger');
                if (trigger) {
                    const picker = trigger.querySelector('.anvil-color-trigger-inner');
                    if (picker) {
                        picker.style.background = input.value || 'transparent';
                    }
                }
            });
        });
    }
    
    function initializeAttrColorPickers(blockId) {
        document.querySelectorAll('.anvil-attr-color-trigger').forEach(trigger => {
            if (trigger.dataset.initialized) return;
            trigger.dataset.initialized = 'true';
            
            const attr = trigger.dataset.attr;
            const initialColor = trigger.dataset.color || '';
            
            // Find the associated text input
            const textInput = trigger.parentElement.querySelector('.anvil-color-text-input');
            
            AnvilColorPicker.create(trigger, initialColor, (color) => {
                // Update text input
                if (textInput) {
                    textInput.value = color;
                }
                
                // Update block attributes
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                block.attributes[attr] = color;
                
                markDirty();
                renderSingleBlock(blockId);
            });
        });
    }
    function renderSettingField(key, attrDef, block) {
        const value = block.attributes?.[key] ?? attrDef.default ?? '';
        const label = formatLabel(key);
        
        let html = `<div class="anvil-live-settings-group">`;
        html += `<label class="anvil-live-settings-label">${escapeHtml(label)}</label>`;

        if (attrDef.type === 'boolean') {
            html += `<label class="anvil-live-settings-checkbox">
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
        } else if (key === 'type' && block.type === 'alert') {
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                <option value="info" ${value==='info'?'selected':''}>Info</option>
                <option value="success" ${value==='success'?'selected':''}>Success</option>
                <option value="warning" ${value==='warning'?'selected':''}>Warning</option>
                <option value="error" ${value==='error'?'selected':''}>Error</option>
            </select>`;
        } else if (key === 'style') {
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                <option value="default" ${value==='default'?'selected':''}>Default</option>
                <option value="bordered" ${value==='bordered'?'selected':''}>Bordered</option>
                <option value="filled" ${value==='filled'?'selected':''}>Filled</option>
                <option value="minimal" ${value==='minimal'?'selected':''}>Minimal</option>
            </select>`;
        } else if (key === 'size') {
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                <option value="small" ${value==='small'?'selected':''}>Small</option>
                <option value="medium" ${value==='medium'?'selected':''}>Medium</option>
                <option value="large" ${value==='large'?'selected':''}>Large</option>
            </select>`;
        } else if (key === 'target') {
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                <option value="_self" ${value==='_self'?'selected':''}>Same Window</option>
                <option value="_blank" ${value==='_blank'?'selected':''}>New Tab</option>
            </select>`;
        } else if (key === 'icon' && block.type === 'iconbox') {
            const icons = ['star','heart','check','zap','shield','award','globe','users','settings','mail','phone','clock','target','trending-up','lock','cpu','cloud','code','layers'];
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                ${icons.map(i => `<option value="${i}" ${value===i?'selected':''}>${i.charAt(0).toUpperCase() + i.slice(1).replace('-', ' ')}</option>`).join('')}
            </select>`;
        } else if (key === 'rating') {
            html += `<select class="anvil-live-settings-select anvil-live-setting-input" data-attr="${escapeHtml(key)}">
                ${[0,1,2,3,4,5].map(n => `<option value="${n}" ${value==n?'selected':''}>${n} Star${n!==1?'s':''}</option>`).join('')}
            </select>`;
        } else if (key === 'iconColor' || key.toLowerCase().includes('color')) {
            html += `<div class="anvil-color-field" style="display:flex;align-items:center;gap:8px;">
                <div class="anvil-color-picker-trigger anvil-attr-color-trigger" data-attr="${escapeHtml(key)}" data-color="${escapeHtml(String(value || ''))}"></div>
                <input type="text" class="anvil-live-settings-input anvil-live-setting-input anvil-color-text-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value || ''))}" style="flex:1;" placeholder="#000000">
            </div>`;
        } else if (key === 'height' || key === 'width' || attrDef.type === 'number' || attrDef.type === 'integer') {
            html += `<input type="number" class="anvil-live-settings-input anvil-live-setting-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value))}" placeholder="0">`;
        } else if (key.toLowerCase().includes('url') || key.toLowerCase().includes('image')) {
            html += `<input type="text" class="anvil-live-settings-input anvil-live-setting-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value))}" placeholder="https://...">`;
        } else if (key === 'content') {
            html += `<textarea class="anvil-live-settings-textarea anvil-live-setting-input" data-attr="${escapeHtml(key)}" rows="4">${escapeHtml(String(value))}</textarea>`;
        } else {
            html += `<input type="text" class="anvil-live-settings-input anvil-live-setting-input" data-attr="${escapeHtml(key)}" value="${escapeHtml(String(value))}">`;
        }

        html += `</div>`;
        return html;
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
        // Find block anywhere (main array or columns)
        const location = findBlockLocation(blockId);
        if (!location) {
            // Fallback to full re-render
            renderBlocks();
            return;
        }
        
        const block = location.block;
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        
        if (!blockEl) {
            renderBlocks();
            return;
        }

        const index = location.index !== undefined ? location.index : 0;
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
            case 'accordion':
                const accordionItems = attrs.items || [
                    { title: 'Accordion Item 1', content: 'Content for the first accordion item.' },
                    { title: 'Accordion Item 2', content: 'Content for the second accordion item.' }
                ];
                const accordionStyle = attrs.style || 'default';
                let accordionHtml = '';
                accordionItems.forEach((item, i) => {
                    const isOpen = i === 0 ? ' open' : '';
                    accordionHtml += `<details class="anvil-accordion-item" style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;overflow:hidden;"${isOpen}>
                        <summary style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;font-weight:600;cursor:pointer;background:#f8fafc;list-style:none;">
                            <span>${escapeHtml(item.title || 'Item')}</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </summary>
                        <div style="padding:12px 16px;border-top:1px solid #e2e8f0;">${escapeHtml(item.content || '')}</div>
                    </details>`;
                });
                content = `<div class="anvil-block-accordion anvil-accordion--${escapeHtml(accordionStyle)}">${accordionHtml}</div>`;
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
            case 'testimonial':
                const testContent = attrs.content || 'This is an amazing product!';
                const testName = attrs.authorName || 'John Doe';
                const testRole = attrs.authorRole || '';
                const testCompany = attrs.authorCompany || '';
                const testImage = attrs.authorImage || '';
                const testRating = Math.min(5, Math.max(0, parseInt(attrs.rating) || 5));
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    const fill = i <= testRating ? '#fbbf24' : 'none';
                    const stroke = i <= testRating ? '#fbbf24' : '#e2e8f0';
                    starsHtml += `<svg width="18" height="18" viewBox="0 0 24 24" fill="${fill}" stroke="${stroke}" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`;
                }
                const testMeta = [testRole, testCompany].filter(Boolean).join(', ');
                const testImageHtml = testImage 
                    ? `<img src="${escapeHtml(testImage)}" alt="${escapeHtml(testName)}" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">`
                    : `<div style="width:48px;height:48px;border-radius:50%;background:#6366f1;color:white;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:1.25rem;">${escapeHtml(testName.charAt(0))}</div>`;
                content = `<div style="padding:24px;background:#f8fafc;border-radius:12px;">
                    ${testRating > 0 ? `<div style="display:flex;gap:4px;margin-bottom:12px;">${starsHtml}</div>` : ''}
                    <blockquote style="margin:0 0 16px;font-size:1.125rem;line-height:1.7;font-style:italic;">"${escapeHtml(testContent)}"</blockquote>
                    <div style="display:flex;align-items:center;gap:12px;padding-top:16px;border-top:1px solid #e2e8f0;">
                        ${testImageHtml}
                        <div>
                            <div style="font-weight:600;">${escapeHtml(testName)}</div>
                            ${testMeta ? `<div style="font-size:0.875rem;color:#64748b;">${escapeHtml(testMeta)}</div>` : ''}
                        </div>
                    </div>
                </div>`;
                break;
            case 'iconbox':
                const iconboxTitle = attrs.title || 'Feature Title';
                const iconboxContent = attrs.content || 'Describe your feature here.';
                const iconboxColor = attrs.iconColor || '#6366f1';
                const iconboxAlign = attrs.align || 'center';
                const iconboxIcon = attrs.icon || 'star';
                const iconboxIcons = {
                    star: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
                    heart: '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
                    check: '<polyline points="20 6 9 17 4 12"/>',
                    zap: '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
                    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                    award: '<circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>',
                    globe: '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
                    users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                    settings: '<circle cx="12" cy="12" r="3"/>',
                    mail: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
                    phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
                    clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
                    target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
                    lock: '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                    code: '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
                    cloud: '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>',
                    layers: '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>'
                };
                const iconSvg = iconboxIcons[iconboxIcon] || iconboxIcons.star;
                content = `<div style="text-align:${iconboxAlign};padding:24px;background:#f8fafc;border-radius:12px;">
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:72px;height:72px;background:${escapeHtml(iconboxColor)};border-radius:12px;margin-bottom:16px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">${iconSvg}</svg>
                    </div>
                    <h4 style="margin:0 0 8px;font-size:1.125rem;font-weight:700;">${escapeHtml(iconboxTitle)}</h4>
                    <p style="margin:0;color:#64748b;line-height:1.6;">${escapeHtml(iconboxContent)}</p>
                </div>`;
                break;
            case 'sociallinks':
                const socialPlatforms = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'github', 'tiktok', 'email'];
                const socialIcons = {
                    facebook: '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
                    twitter: '<path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>',
                    instagram: '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
                    linkedin: '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
                    youtube: '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>',
                    github: '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>',
                    tiktok: '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>',
                    email: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'
                };
                const socialColors = { facebook: '#1877f2', twitter: '#1da1f2', instagram: '#e4405f', linkedin: '#0a66c2', youtube: '#ff0000', github: '#333', tiktok: '#000', email: '#6366f1' };
                const socialSize = attrs.size === 'small' ? 36 : attrs.size === 'large' ? 52 : 44;
                const socialAlign = attrs.align || 'center';
                let socialLinksHtml = '';
                socialPlatforms.forEach(platform => {
                    let url = attrs[platform] || '';
                    if (!url) return;
                    // Normalize URLs
                    url = normalizeUrl(url, platform === 'email' ? 'email' : 'url');
                    socialLinksHtml += `<a href="${escapeHtml(url)}" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;justify-content:center;width:${socialSize}px;height:${socialSize}px;background:#f1f5f9;border-radius:8px;color:#64748b;transition:all 0.2s;" onmouseover="this.style.background='${socialColors[platform]}';this.style.color='white';" onmouseout="this.style.background='#f1f5f9';this.style.color='#64748b';">
                        <svg width="${socialSize * 0.5}" height="${socialSize * 0.5}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${socialIcons[platform]}</svg>
                    </a>`;
                });
                if (!socialLinksHtml) {
                    socialLinksHtml = '<span style="color:#94a3b8;font-style:italic;">Add social URLs in settings</span>';
                }
                content = `<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:${socialAlign === 'center' ? 'center' : socialAlign === 'right' ? 'flex-end' : 'flex-start'};">${socialLinksHtml}</div>`;
                break;
            default:
                content = `<div style="padding:20px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;text-align:center;color:#64748b;">${escapeHtml(type)} block</div>`;
        }

        // Generate all block styles
        const blockStyles = getBlockStyles(attrs);
        const blockClasses = getBlockClasses(attrs);
        const blockCssId = getBlockId(attrs);
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
                body: JSON.stringify({ post_id: config.postId, blocks, title, pageSettings })
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

    async function previewContent() {
        // Save first if there are unsaved changes
        if (isDirty) {
            await saveContent();
        }
        
        // Open preview in new tab (exitUrl is the permalink without editor params)
        window.open(config.exitUrl, '_blank');
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

    function normalizeUrl(url, type = 'url') {
        if (!url || typeof url !== 'string') return url;
        url = url.trim();
        if (!url) return url;
        
        if (type === 'email') {
            return url.startsWith('mailto:') ? url : 'mailto:' + url;
        }
        
        // Add https:// if no protocol and not a relative path
        if (!url.match(/^https?:\/\//i) && !url.startsWith('/') && !url.startsWith('#')) {
            return 'https://' + url;
        }
        return url;
    }
    
    function getSpacingStyle(attrs) {
        return getBlockStyles(attrs);
    }
    
    function getBlockStyles(attrs) {
        let style = '';
        
        // Generate margin styles
        if (attrs.margin) {
            const m = attrs.margin;
            const unit = m.unit || 'px';
            if (m.top) style += `margin-top:${m.top}${unit};`;
            if (m.right) style += `margin-right:${m.right}${unit};`;
            if (m.bottom) style += `margin-bottom:${m.bottom}${unit};`;
            if (m.left) style += `margin-left:${m.left}${unit};`;
        }
        
        // Generate padding styles
        if (attrs.padding) {
            const p = attrs.padding;
            const unit = p.unit || 'px';
            if (p.top) style += `padding-top:${p.top}${unit};`;
            if (p.right) style += `padding-right:${p.right}${unit};`;
            if (p.bottom) style += `padding-bottom:${p.bottom}${unit};`;
            if (p.left) style += `padding-left:${p.left}${unit};`;
        }
        
        // Typography styles
        if (attrs.typography) {
            const t = attrs.typography;
            if (t.fontSize) style += `font-size:${t.fontSize}${t.fontSizeUnit || 'px'};`;
            if (t.fontWeight) style += `font-weight:${t.fontWeight};`;
            if (t.lineHeight) style += `line-height:${t.lineHeight};`;
            if (t.letterSpacing) style += `letter-spacing:${t.letterSpacing}px;`;
            if (t.textTransform) style += `text-transform:${t.textTransform};`;
            if (t.fontStyle) style += `font-style:${t.fontStyle};`;
        }
        
        // Color styles
        if (attrs.colors) {
            const c = attrs.colors;
            if (c.textColor) style += `color:${c.textColor};`;
            if (c.backgroundColor) style += `background-color:${c.backgroundColor};`;
        }
        
        // Border styles
        if (attrs.border) {
            const b = attrs.border;
            if (b.style && b.style !== 'none') {
                style += `border-style:${b.style};`;
                if (b.width) style += `border-width:${b.width}px;`;
                if (b.color) style += `border-color:${b.color};`;
            }
            if (b.radius) style += `border-radius:${b.radius}px;`;
        }
        
        // Box shadow styles
        if (attrs.boxShadow) {
            const s = attrs.boxShadow;
            if (s.preset && s.preset !== 'custom') {
                const shadowPresets = {
                    sm: '0 1px 2px 0 rgba(0,0,0,0.05)',
                    md: '0 4px 6px -1px rgba(0,0,0,0.1)',
                    lg: '0 10px 15px -3px rgba(0,0,0,0.1)',
                    xl: '0 20px 25px -5px rgba(0,0,0,0.1)'
                };
                if (shadowPresets[s.preset]) {
                    style += `box-shadow:${shadowPresets[s.preset]};`;
                }
            } else if (s.preset === 'custom') {
                const x = s.x || 0;
                const y = s.y || 4;
                const blur = s.blur || 6;
                const spread = s.spread || 0;
                const color = s.color || 'rgba(0,0,0,0.1)';
                style += `box-shadow:${x}px ${y}px ${blur}px ${spread}px ${color};`;
            }
        }
        
        // Z-index
        if (attrs.customAttributes?.zIndex) {
            style += `z-index:${attrs.customAttributes.zIndex};`;
        }
        
        // Background styles
        if (attrs.background) {
            const bg = attrs.background;
            if (bg.type === 'color' && bg.color) {
                style += `background-color:${bg.color};`;
            } else if (bg.type === 'gradient') {
                const c1 = bg.gradientColor1 || '#6366f1';
                const c2 = bg.gradientColor2 || '#a855f7';
                const angle = bg.gradientAngle || 135;
                const gtype = bg.gradientType || 'linear';
                if (gtype === 'linear') {
                    style += `background:linear-gradient(${angle}deg, ${c1}, ${c2});`;
                } else {
                    style += `background:radial-gradient(circle, ${c1}, ${c2});`;
                }
            } else if (bg.type === 'image' && bg.imageUrl) {
                const pos = bg.imagePosition || 'center center';
                const size = bg.imageSize || 'cover';
                const repeat = bg.imageRepeat || 'no-repeat';
                style += `background-image:url('${bg.imageUrl}');`;
                style += `background-position:${pos};`;
                style += `background-size:${size};`;
                style += `background-repeat:${repeat};`;
            }
        }
        
        // Sizing styles
        if (attrs.sizing) {
            const sz = attrs.sizing;
            if (sz.width) style += `width:${sz.width};`;
            if (sz.height) style += `height:${sz.height};`;
            if (sz.maxWidth) style += `max-width:${sz.maxWidth};`;
            if (sz.maxHeight) style += `max-height:${sz.maxHeight};`;
            if (sz.minWidth) style += `min-width:${sz.minWidth};`;
            if (sz.minHeight) style += `min-height:${sz.minHeight};`;
            if (sz.overflow) style += `overflow:${sz.overflow};`;
        }
        
        // Transform styles
        if (attrs.transform) {
            const tr = attrs.transform;
            const transforms = [];
            if (tr.rotate) transforms.push(`rotate(${tr.rotate}deg)`);
            if (tr.scale && tr.scale !== 1) transforms.push(`scale(${tr.scale})`);
            if (tr.translateX) transforms.push(`translateX(${tr.translateX}px)`);
            if (tr.translateY) transforms.push(`translateY(${tr.translateY}px)`);
            if (tr.skewX) transforms.push(`skewX(${tr.skewX}deg)`);
            if (tr.skewY) transforms.push(`skewY(${tr.skewY}deg)`);
            if (transforms.length > 0) {
                style += `transform:${transforms.join(' ')};`;
            }
        }
        
        // Animation/transition styles
        if (attrs.animation) {
            const anim = attrs.animation;
            if (anim.transitionDuration) {
                style += `transition:all ${anim.transitionDuration}ms ease;`;
            }
        }
        
        return style;
    }
    
    function getBlockClasses(attrs) {
        let classes = [];
        
        // Custom CSS classes
        if (attrs.customAttributes?.cssClasses) {
            classes.push(attrs.customAttributes.cssClasses);
        }
        
        // Responsive visibility classes
        if (attrs.responsive) {
            if (attrs.responsive.hideDesktop) classes.push('anvil-hide-desktop');
            if (attrs.responsive.hideTablet) classes.push('anvil-hide-tablet');
            if (attrs.responsive.hideMobile) classes.push('anvil-hide-mobile');
        }
        
        // Animation classes
        if (attrs.animation) {
            if (attrs.animation.entrance) classes.push(`anvil-anim-${attrs.animation.entrance}`);
            if (attrs.animation.hover) classes.push(`anvil-hover-${attrs.animation.hover}`);
        }
        
        return classes.join(' ');
    }
    
    function getBlockId(attrs) {
        return attrs.customAttributes?.cssId || '';
    }

    function formatLabel(key) {
        return key.replace(/([A-Z])/g, ' $1').replace(/[_-]/g, ' ').replace(/^\w/, c => c.toUpperCase()).trim();
    }

    // =========================================================================
    // MODERN COLOR PICKER (jQuery)
    // =========================================================================
    
    const AnvilColorPicker = {
        activePopup: null,
        currentColor: { h: 0, s: 100, v: 100, a: 1 },
        previousColor: null,
        callback: null,
        mode: 'hex',
        
        presets: [
            '#000000', '#434343', '#666666', '#999999', '#b7b7b7', '#cccccc', '#d9d9d9', '#efefef', '#f3f3f3', '#ffffff',
            '#980000', '#ff0000', '#ff9900', '#ffff00', '#00ff00', '#00ffff', '#4a86e8', '#0000ff', '#9900ff', '#ff00ff',
            '#e6b8af', '#f4cccc', '#fce5cd', '#fff2cc', '#d9ead3', '#d0e0e3', '#c9daf8', '#cfe2f3', '#d9d2e9', '#ead1dc',
            '#dd7e6b', '#ea9999', '#f9cb9c', '#ffe599', '#b6d7a8', '#a2c4c9', '#a4c2f4', '#9fc5e8', '#b4a7d6', '#d5a6bd'
        ],
        
        init() {
            // Create global overlay for closing
            if (!document.getElementById('anvil-color-overlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'anvil-color-overlay';
                overlay.className = 'anvil-color-overlay';
                document.body.appendChild(overlay);
                
                overlay.addEventListener('click', () => this.close());
            }
        },
        
        create(triggerEl, initialColor, onChange) {
            this.init();
            this.callback = onChange;
            this.previousColor = initialColor || '#000000';
            
            // Parse initial color
            this.setColorFromString(initialColor || '#000000');
            
            // Create trigger wrapper if needed
            let wrap = triggerEl.closest('.anvil-color-picker-wrap');
            if (!wrap) {
                wrap = document.createElement('div');
                wrap.className = 'anvil-color-picker-wrap';
                triggerEl.parentNode.insertBefore(wrap, triggerEl);
                wrap.appendChild(triggerEl);
            }
            
            // Style trigger
            triggerEl.classList.add('anvil-color-trigger');
            triggerEl.innerHTML = `<div class="anvil-color-trigger-inner" style="background:${this.toRgbaString()}"></div>`;
            
            // Add click handler
            triggerEl.addEventListener('click', (e) => {
                e.stopPropagation();
                this.open(wrap, triggerEl);
            });
            
            return {
                setColor: (color) => {
                    this.setColorFromString(color);
                    triggerEl.querySelector('.anvil-color-trigger-inner').style.background = this.toRgbaString();
                },
                getColor: () => this.toHex()
            };
        },
        
        open(wrap, triggerEl) {
            this.close();
            
            const popup = document.createElement('div');
            popup.className = 'anvil-color-popup';
            popup.innerHTML = this.buildHTML();
            
            // Append to body for fixed positioning
            document.body.appendChild(popup);
            
            // Position popup using fixed coordinates
            const rect = wrap.getBoundingClientRect();
            let top = rect.bottom + 8;
            let left = rect.left;
            
            // Adjust if popup would go off bottom of screen
            if (top + 400 > window.innerHeight) {
                top = rect.top - 400 - 8;
            }
            
            // Adjust if popup would go off right of screen
            if (left + 280 > window.innerWidth) {
                left = rect.right - 280;
            }
            
            // Ensure popup doesn't go off left edge
            if (left < 8) {
                left = 8;
            }
            
            popup.style.top = top + 'px';
            popup.style.left = left + 'px';
            
            // Show popup and overlay
            requestAnimationFrame(() => {
                popup.classList.add('active');
                document.getElementById('anvil-color-overlay').classList.add('active');
            });
            
            this.activePopup = popup;
            this.bindEvents(popup, triggerEl);
            this.updateUI(popup);
        },
        
        close() {
            if (this.activePopup) {
                this.activePopup.remove();
                this.activePopup = null;
            }
            const overlay = document.getElementById('anvil-color-overlay');
            if (overlay) overlay.classList.remove('active');
        },
        
        buildHTML() {
            const presetsHtml = this.presets.map(c => 
                `<div class="anvil-color-preset" style="background:${c}" data-color="${c}"></div>`
            ).join('');
            
            return `
                <div class="anvil-color-gradient" style="background-color: hsl(${this.currentColor.h}, 100%, 50%)">
                    <div class="anvil-color-gradient-pointer"></div>
                </div>
                
                <div class="anvil-color-sliders">
                    <div class="anvil-color-slider-wrap">
                        <span class="anvil-color-slider-label">Hue</span>
                        <div class="anvil-color-slider anvil-color-hue">
                            <div class="anvil-color-slider-thumb"></div>
                        </div>
                    </div>
                    <div class="anvil-color-slider-wrap">
                        <span class="anvil-color-slider-label">Opacity</span>
                        <div class="anvil-color-slider anvil-color-opacity">
                            <div class="anvil-color-opacity-gradient"></div>
                            <div class="anvil-color-slider-thumb"></div>
                        </div>
                    </div>
                </div>
                
                <div class="anvil-color-preview-row">
                    <div class="anvil-color-preview">
                        <div class="anvil-color-preview-inner">
                            <div class="anvil-color-preview-current"></div>
                            <div class="anvil-color-preview-previous" title="Click to restore"></div>
                        </div>
                    </div>
                    <div class="anvil-color-inputs">
                        <div class="anvil-color-mode-toggle">
                            <button type="button" class="anvil-color-mode-btn ${this.mode === 'hex' ? 'active' : ''}" data-mode="hex">HEX</button>
                            <button type="button" class="anvil-color-mode-btn ${this.mode === 'rgb' ? 'active' : ''}" data-mode="rgb">RGB</button>
                            <button type="button" class="anvil-color-mode-btn ${this.mode === 'hsl' ? 'active' : ''}" data-mode="hsl">HSL</button>
                        </div>
                        <div class="anvil-color-input-fields"></div>
                    </div>
                </div>
                
                <div class="anvil-color-presets">${presetsHtml}</div>
                
                <div class="anvil-color-actions">
                    <button type="button" class="anvil-color-btn anvil-color-btn-clear">Clear</button>
                    <button type="button" class="anvil-color-btn anvil-color-btn-apply">Apply</button>
                </div>
            `;
        },
        
        bindEvents(popup, triggerEl) {
            const gradient = popup.querySelector('.anvil-color-gradient');
            const hueSlider = popup.querySelector('.anvil-color-hue');
            const opacitySlider = popup.querySelector('.anvil-color-opacity');
            
            // Gradient area
            const handleGradient = (e) => {
                const rect = gradient.getBoundingClientRect();
                const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                const y = Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height));
                this.currentColor.s = x * 100;
                this.currentColor.v = (1 - y) * 100;
                this.updateUI(popup);
                this.emitChange(triggerEl);
            };
            
            gradient.addEventListener('mousedown', (e) => {
                handleGradient(e);
                const move = (e) => handleGradient(e);
                const up = () => {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                };
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
            });
            
            // Hue slider
            const handleHue = (e) => {
                const rect = hueSlider.getBoundingClientRect();
                const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                this.currentColor.h = x * 360;
                this.updateUI(popup);
                this.emitChange(triggerEl);
            };
            
            hueSlider.addEventListener('mousedown', (e) => {
                handleHue(e);
                const move = (e) => handleHue(e);
                const up = () => {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                };
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
            });
            
            // Opacity slider
            const handleOpacity = (e) => {
                const rect = opacitySlider.getBoundingClientRect();
                const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                this.currentColor.a = x;
                this.updateUI(popup);
                this.emitChange(triggerEl);
            };
            
            opacitySlider.addEventListener('mousedown', (e) => {
                handleOpacity(e);
                const move = (e) => handleOpacity(e);
                const up = () => {
                    document.removeEventListener('mousemove', move);
                    document.removeEventListener('mouseup', up);
                };
                document.addEventListener('mousemove', move);
                document.addEventListener('mouseup', up);
            });
            
            // Mode toggle
            popup.querySelectorAll('.anvil-color-mode-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.mode = btn.dataset.mode;
                    popup.querySelectorAll('.anvil-color-mode-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    this.updateInputFields(popup);
                });
            });
            
            // Presets
            popup.querySelectorAll('.anvil-color-preset').forEach(preset => {
                preset.addEventListener('click', () => {
                    this.setColorFromString(preset.dataset.color);
                    this.updateUI(popup);
                    this.emitChange(triggerEl);
                });
            });
            
            // Previous color restore
            popup.querySelector('.anvil-color-preview-previous').addEventListener('click', () => {
                this.setColorFromString(this.previousColor);
                this.updateUI(popup);
                this.emitChange(triggerEl);
            });
            
            // Clear button
            popup.querySelector('.anvil-color-btn-clear').addEventListener('click', () => {
                if (this.callback) this.callback('');
                triggerEl.querySelector('.anvil-color-trigger-inner').style.background = 'transparent';
                this.close();
            });
            
            // Apply button
            popup.querySelector('.anvil-color-btn-apply').addEventListener('click', () => {
                this.previousColor = this.toRgbaString();
                this.close();
            });
            
            // Stop propagation on popup
            popup.addEventListener('click', (e) => e.stopPropagation());
        },
        
        updateUI(popup) {
            const { h, s, v, a } = this.currentColor;
            
            // Update gradient background
            popup.querySelector('.anvil-color-gradient').style.backgroundColor = `hsl(${h}, 100%, 50%)`;
            
            // Update gradient pointer
            const pointer = popup.querySelector('.anvil-color-gradient-pointer');
            pointer.style.left = `${s}%`;
            pointer.style.top = `${100 - v}%`;
            
            // Update hue thumb
            const hueThumb = popup.querySelector('.anvil-color-hue .anvil-color-slider-thumb');
            hueThumb.style.left = `${(h / 360) * 100}%`;
            hueThumb.style.background = `hsl(${h}, 100%, 50%)`;
            
            // Update opacity slider gradient
            const rgb = this.hsvToRgb(h, s, v);
            const opacityGradient = popup.querySelector('.anvil-color-opacity-gradient');
            opacityGradient.style.background = `linear-gradient(to right, transparent, rgb(${rgb.r}, ${rgb.g}, ${rgb.b}))`;
            
            // Update opacity thumb
            const opacityThumb = popup.querySelector('.anvil-color-opacity .anvil-color-slider-thumb');
            opacityThumb.style.left = `${a * 100}%`;
            
            // Update preview
            const currentPreview = popup.querySelector('.anvil-color-preview-current');
            currentPreview.style.background = this.toRgbaString();
            
            const previousPreview = popup.querySelector('.anvil-color-preview-previous');
            previousPreview.style.background = this.previousColor;
            
            // Update input fields
            this.updateInputFields(popup);
        },
        
        updateInputFields(popup) {
            const container = popup.querySelector('.anvil-color-input-fields');
            const { h, s, v, a } = this.currentColor;
            const rgb = this.hsvToRgb(h, s, v);
            
            let html = '';
            
            if (this.mode === 'hex') {
                html = `
                    <div class="anvil-color-input-row">
                        <div class="anvil-color-input-group hex-group">
                            <label>Hex</label>
                            <input type="text" class="anvil-color-hex-input" value="${this.toHex()}" maxlength="9">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>A</label>
                            <input type="number" class="anvil-color-alpha-input" value="${Math.round(a * 100)}" min="0" max="100">
                        </div>
                    </div>
                `;
            } else if (this.mode === 'rgb') {
                html = `
                    <div class="anvil-color-input-row">
                        <div class="anvil-color-input-group">
                            <label>R</label>
                            <input type="number" class="anvil-color-r-input" value="${rgb.r}" min="0" max="255">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>G</label>
                            <input type="number" class="anvil-color-g-input" value="${rgb.g}" min="0" max="255">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>B</label>
                            <input type="number" class="anvil-color-b-input" value="${rgb.b}" min="0" max="255">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>A</label>
                            <input type="number" class="anvil-color-alpha-input" value="${Math.round(a * 100)}" min="0" max="100">
                        </div>
                    </div>
                `;
            } else if (this.mode === 'hsl') {
                const hsl = this.hsvToHsl(h, s, v);
                html = `
                    <div class="anvil-color-input-row">
                        <div class="anvil-color-input-group">
                            <label>H</label>
                            <input type="number" class="anvil-color-h-input" value="${Math.round(hsl.h)}" min="0" max="360">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>S</label>
                            <input type="number" class="anvil-color-s-input" value="${Math.round(hsl.s)}" min="0" max="100">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>L</label>
                            <input type="number" class="anvil-color-l-input" value="${Math.round(hsl.l)}" min="0" max="100">
                        </div>
                        <div class="anvil-color-input-group">
                            <label>A</label>
                            <input type="number" class="anvil-color-alpha-input" value="${Math.round(a * 100)}" min="0" max="100">
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
            
            // Bind input change handlers
            this.bindInputHandlers(popup);
        },
        
        bindInputHandlers(popup) {
            const triggerEl = popup.closest('.anvil-color-picker-wrap').querySelector('.anvil-color-trigger');
            
            // Hex input
            const hexInput = popup.querySelector('.anvil-color-hex-input');
            if (hexInput) {
                hexInput.addEventListener('change', () => {
                    this.setColorFromString(hexInput.value);
                    this.updateUI(popup);
                    this.emitChange(triggerEl);
                });
            }
            
            // RGB inputs
            const rInput = popup.querySelector('.anvil-color-r-input');
            const gInput = popup.querySelector('.anvil-color-g-input');
            const bInput = popup.querySelector('.anvil-color-b-input');
            if (rInput && gInput && bInput) {
                [rInput, gInput, bInput].forEach(input => {
                    input.addEventListener('change', () => {
                        const r = parseInt(rInput.value) || 0;
                        const g = parseInt(gInput.value) || 0;
                        const b = parseInt(bInput.value) || 0;
                        const hsv = this.rgbToHsv(r, g, b);
                        this.currentColor.h = hsv.h;
                        this.currentColor.s = hsv.s;
                        this.currentColor.v = hsv.v;
                        this.updateUI(popup);
                        this.emitChange(triggerEl);
                    });
                });
            }
            
            // HSL inputs
            const hInput = popup.querySelector('.anvil-color-h-input');
            const sInput = popup.querySelector('.anvil-color-s-input');
            const lInput = popup.querySelector('.anvil-color-l-input');
            if (hInput && sInput && lInput) {
                [hInput, sInput, lInput].forEach(input => {
                    input.addEventListener('change', () => {
                        const h = parseInt(hInput.value) || 0;
                        const s = parseInt(sInput.value) || 0;
                        const l = parseInt(lInput.value) || 0;
                        const hsv = this.hslToHsv(h, s, l);
                        this.currentColor.h = hsv.h;
                        this.currentColor.s = hsv.s;
                        this.currentColor.v = hsv.v;
                        this.updateUI(popup);
                        this.emitChange(triggerEl);
                    });
                });
            }
            
            // Alpha input
            const alphaInput = popup.querySelector('.anvil-color-alpha-input');
            if (alphaInput) {
                alphaInput.addEventListener('change', () => {
                    this.currentColor.a = (parseInt(alphaInput.value) || 0) / 100;
                    this.updateUI(popup);
                    this.emitChange(triggerEl);
                });
            }
        },
        
        emitChange(triggerEl) {
            const color = this.currentColor.a < 1 ? this.toRgbaString() : this.toHex();
            triggerEl.querySelector('.anvil-color-trigger-inner').style.background = this.toRgbaString();
            if (this.callback) this.callback(color);
        },
        
        // Color conversion utilities
        setColorFromString(str) {
            if (!str) {
                this.currentColor = { h: 0, s: 0, v: 100, a: 1 };
                return;
            }
            
            str = str.trim().toLowerCase();
            
            // Handle hex
            if (str.startsWith('#')) {
                const rgb = this.hexToRgb(str);
                if (rgb) {
                    const hsv = this.rgbToHsv(rgb.r, rgb.g, rgb.b);
                    this.currentColor = { ...hsv, a: rgb.a !== undefined ? rgb.a : 1 };
                }
                return;
            }
            
            // Handle rgba
            const rgbaMatch = str.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
            if (rgbaMatch) {
                const r = parseInt(rgbaMatch[1]);
                const g = parseInt(rgbaMatch[2]);
                const b = parseInt(rgbaMatch[3]);
                const a = rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1;
                const hsv = this.rgbToHsv(r, g, b);
                this.currentColor = { ...hsv, a };
                return;
            }
            
            // Handle hsla
            const hslaMatch = str.match(/hsla?\((\d+),\s*(\d+)%?,\s*(\d+)%?(?:,\s*([\d.]+))?\)/);
            if (hslaMatch) {
                const h = parseInt(hslaMatch[1]);
                const s = parseInt(hslaMatch[2]);
                const l = parseInt(hslaMatch[3]);
                const a = hslaMatch[4] ? parseFloat(hslaMatch[4]) : 1;
                const hsv = this.hslToHsv(h, s, l);
                this.currentColor = { ...hsv, a };
            }
        },
        
        toHex() {
            const { h, s, v } = this.currentColor;
            const rgb = this.hsvToRgb(h, s, v);
            const toHex = (n) => n.toString(16).padStart(2, '0');
            return `#${toHex(rgb.r)}${toHex(rgb.g)}${toHex(rgb.b)}`;
        },
        
        toRgbaString() {
            const { h, s, v, a } = this.currentColor;
            const rgb = this.hsvToRgb(h, s, v);
            if (a < 1) {
                return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${a.toFixed(2)})`;
            }
            return `rgb(${rgb.r}, ${rgb.g}, ${rgb.b})`;
        },
        
        hexToRgb(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
            }
            if (hex.length === 8) {
                return {
                    r: parseInt(hex.substr(0, 2), 16),
                    g: parseInt(hex.substr(2, 2), 16),
                    b: parseInt(hex.substr(4, 2), 16),
                    a: parseInt(hex.substr(6, 2), 16) / 255
                };
            }
            if (hex.length === 6) {
                return {
                    r: parseInt(hex.substr(0, 2), 16),
                    g: parseInt(hex.substr(2, 2), 16),
                    b: parseInt(hex.substr(4, 2), 16)
                };
            }
            return null;
        },
        
        rgbToHsv(r, g, b) {
            r /= 255; g /= 255; b /= 255;
            const max = Math.max(r, g, b), min = Math.min(r, g, b);
            const d = max - min;
            let h = 0, s = max === 0 ? 0 : d / max, v = max;
            
            if (max !== min) {
                switch (max) {
                    case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                    case g: h = (b - r) / d + 2; break;
                    case b: h = (r - g) / d + 4; break;
                }
                h /= 6;
            }
            
            return { h: h * 360, s: s * 100, v: v * 100 };
        },
        
        hsvToRgb(h, s, v) {
            h /= 360; s /= 100; v /= 100;
            let r, g, b;
            const i = Math.floor(h * 6);
            const f = h * 6 - i;
            const p = v * (1 - s);
            const q = v * (1 - f * s);
            const t = v * (1 - (1 - f) * s);
            
            switch (i % 6) {
                case 0: r = v; g = t; b = p; break;
                case 1: r = q; g = v; b = p; break;
                case 2: r = p; g = v; b = t; break;
                case 3: r = p; g = q; b = v; break;
                case 4: r = t; g = p; b = v; break;
                case 5: r = v; g = p; b = q; break;
            }
            
            return {
                r: Math.round(r * 255),
                g: Math.round(g * 255),
                b: Math.round(b * 255)
            };
        },
        
        hsvToHsl(h, s, v) {
            s /= 100; v /= 100;
            const l = v * (1 - s / 2);
            const sl = l === 0 || l === 1 ? 0 : (v - l) / Math.min(l, 1 - l);
            return { h, s: sl * 100, l: l * 100 };
        },
        
        hslToHsv(h, s, l) {
            s /= 100; l /= 100;
            const v = l + s * Math.min(l, 1 - l);
            const sv = v === 0 ? 0 : 2 * (1 - l / v);
            return { h, s: sv * 100, v: v * 100 };
        }
    };
    
    // jQuery plugin for easy use
    if (typeof jQuery !== 'undefined') {
        jQuery.fn.anvilColorPicker = function(options) {
            return this.each(function() {
                const $el = jQuery(this);
                const initialColor = options?.color || $el.val() || '#000000';
                const onChange = options?.onChange || function() {};
                
                const picker = AnvilColorPicker.create(this, initialColor, (color) => {
                    $el.val(color);
                    onChange(color);
                });
                
                $el.data('anvilColorPicker', picker);
            });
        };
    }
    
    // Make globally available
    window.AnvilColorPicker = AnvilColorPicker;

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
    // ACCORDION ITEMS EDITOR
    // =========================================================================
    
    function renderAccordionItemsEditor(block) {
        const items = block.attributes?.items || [
            { title: 'Accordion Item 1', content: 'Content for the first accordion item.' },
            { title: 'Accordion Item 2', content: 'Content for the second accordion item.' }
        ];
        
        let html = `
            <div class="anvil-live-settings-group" style="margin-top:16px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
                <label class="anvil-live-settings-label">Accordion Items</label>
                <div id="accordion-items-list">`;
        
        items.forEach((item, index) => {
            html += `
                <div class="anvil-accordion-item-editor" data-index="${index}" style="background:rgba(0,0,0,0.2);border-radius:8px;padding:12px;margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <span style="font-size:12px;font-weight:600;color:rgba(255,255,255,0.7);">Item ${index + 1}</span>
                        <button type="button" class="anvil-accordion-remove-btn" data-index="${index}" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                    <input type="text" class="anvil-live-settings-input anvil-accordion-title-input" data-index="${index}" value="${escapeHtml(item.title || '')}" placeholder="Title" style="margin-bottom:8px;">
                    <textarea class="anvil-live-settings-input anvil-accordion-content-input" data-index="${index}" placeholder="Content" rows="2" style="resize:vertical;">${escapeHtml(item.content || '')}</textarea>
                </div>`;
        });
        
        html += `
                </div>
                <button type="button" id="add-accordion-item-btn" style="width:100%;padding:8px;background:rgba(99,102,241,0.2);border:1px dashed rgba(99,102,241,0.5);border-radius:6px;color:#a5b4fc;cursor:pointer;font-size:13px;margin-top:8px;">
                    + Add Item
                </button>
            </div>`;
        
        return html;
    }
    
    function bindAccordionItemHandlers(blockId) {
        // Add item button
        const addBtn = document.getElementById('add-accordion-item-btn');
        if (addBtn) {
            addBtn.onclick = () => {
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                block.attributes.items = block.attributes.items || [];
                block.attributes.items.push({ title: 'New Item', content: 'Enter content here.' });
                
                markDirty();
                renderBlocks();
                showBlockSettings(blockId);
            };
        }
        
        // Title inputs
        document.querySelectorAll('.anvil-accordion-title-input').forEach(input => {
            input.oninput = () => {
                const index = parseInt(input.dataset.index);
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                if (block.attributes?.items?.[index]) {
                    block.attributes.items[index].title = input.value;
                    markDirty();
                    renderBlocks();
                }
            };
        });
        
        // Content inputs
        document.querySelectorAll('.anvil-accordion-content-input').forEach(input => {
            input.oninput = () => {
                const index = parseInt(input.dataset.index);
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                if (block.attributes?.items?.[index]) {
                    block.attributes.items[index].content = input.value;
                    markDirty();
                    renderBlocks();
                }
            };
        });
        
        // Remove buttons
        document.querySelectorAll('.anvil-accordion-remove-btn').forEach(btn => {
            btn.onclick = () => {
                const index = parseInt(btn.dataset.index);
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                if (block.attributes?.items && block.attributes.items.length > 1) {
                    block.attributes.items.splice(index, 1);
                    markDirty();
                    renderBlocks();
                    showBlockSettings(blockId);
                }
            };
        });
    }

    // =========================================================================
    // SPACING CONTROLS (Margin & Padding)
    // =========================================================================
    
    function renderSpacingControls(block) {
        const margin = block.attributes?.margin || { top: '', right: '', bottom: '', left: '', unit: 'px', linked: false };
        const padding = block.attributes?.padding || { top: '', right: '', bottom: '', left: '', unit: 'px', linked: false };
        
        return `
            <div class="anvil-spacing-section">
                ${renderSpacingGroup('Margin', 'margin', margin)}
                ${renderSpacingGroup('Padding', 'padding', padding)}
            </div>
        `;
    }
    
    function renderSpacingGroup(label, type, values) {
        const units = ['px', 'em', '%', 'rem'];
        const currentUnit = values.unit || 'px';
        const isLinked = values.linked || false;
        
        return `
            <div class="anvil-spacing-group" data-spacing-type="${type}" style="margin-bottom:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                    <label style="font-size:12px;font-weight:600;color:#e0e0e0;">${label}</label>
                    <div style="display:flex;gap:2px;">
                        ${units.map(u => `
                            <button type="button" class="anvil-spacing-unit-btn" data-unit="${u}" data-spacing-type="${type}"
                                style="padding:4px 8px;font-size:10px;border:none;border-radius:4px;cursor:pointer;
                                       background:${currentUnit === u ? '#a78bfa' : '#3f4451'};
                                       color:${currentUnit === u ? 'white' : '#9ca3af'};transition:0.2s;">
                                ${u.toUpperCase()}
                            </button>
                        `).join('')}
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <div style="flex:1;display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
                        <div style="text-align:center;">
                            <input type="number" class="anvil-spacing-input" data-spacing-type="${type}" data-side="top"
                                   value="${values.top || ''}" placeholder="-"
                                   style="width:100%;padding:8px 4px;font-size:12px;text-align:center;background:#2c313a;border:1px solid #3f4451;border-radius:6px;color:#e0e0e0;">
                            <span style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-top:4px;display:block;">Top</span>
                        </div>
                        <div style="text-align:center;">
                            <input type="number" class="anvil-spacing-input" data-spacing-type="${type}" data-side="right"
                                   value="${values.right || ''}" placeholder="-"
                                   style="width:100%;padding:8px 4px;font-size:12px;text-align:center;background:#2c313a;border:1px solid #3f4451;border-radius:6px;color:#e0e0e0;">
                            <span style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-top:4px;display:block;">Right</span>
                        </div>
                        <div style="text-align:center;">
                            <input type="number" class="anvil-spacing-input" data-spacing-type="${type}" data-side="bottom"
                                   value="${values.bottom || ''}" placeholder="-"
                                   style="width:100%;padding:8px 4px;font-size:12px;text-align:center;background:#2c313a;border:1px solid #3f4451;border-radius:6px;color:#e0e0e0;">
                            <span style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-top:4px;display:block;">Bottom</span>
                        </div>
                        <div style="text-align:center;">
                            <input type="number" class="anvil-spacing-input" data-spacing-type="${type}" data-side="left"
                                   value="${values.left || ''}" placeholder="-"
                                   style="width:100%;padding:8px 4px;font-size:12px;text-align:center;background:#2c313a;border:1px solid #3f4451;border-radius:6px;color:#e0e0e0;">
                            <span style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-top:4px;display:block;">Left</span>
                        </div>
                    </div>
                    <button type="button" class="anvil-spacing-link-btn" data-spacing-type="${type}" title="Link values"
                            style="padding:8px;background:${isLinked ? '#a78bfa' : '#3f4451'};border:none;border-radius:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:0.2s;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="${isLinked ? 'white' : '#9ca3af'}" stroke-width="2">
                            ${isLinked 
                                ? '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
                                : '<path d="M18.84 12.25l1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/>'
                            }
                        </svg>
                    </button>
                </div>
            </div>
        `;
    }
    
    function bindSpacingHandlers(blockId) {
        // Unit buttons
        document.querySelectorAll('.anvil-spacing-unit-btn').forEach(btn => {
            btn.onclick = () => {
                const type = btn.dataset.spacingType;
                const unit = btn.dataset.unit;
                
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                block.attributes[type] = block.attributes[type] || { top: '', right: '', bottom: '', left: '', unit: 'px', linked: false };
                block.attributes[type].unit = unit;
                
                // Update button styles
                document.querySelectorAll(`.anvil-spacing-unit-btn[data-spacing-type="${type}"]`).forEach(b => {
                    const isActive = b.dataset.unit === unit;
                    b.style.background = isActive ? '#a78bfa' : '#3f4451';
                    b.style.color = isActive ? 'white' : '#9ca3af';
                });
                
                markDirty();
                renderSingleBlock(blockId);
            };
        });
        
        // Link buttons
        document.querySelectorAll('.anvil-spacing-link-btn').forEach(btn => {
            btn.onclick = () => {
                const type = btn.dataset.spacingType;
                
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                block.attributes[type] = block.attributes[type] || { top: '', right: '', bottom: '', left: '', unit: 'px', linked: false };
                block.attributes[type].linked = !block.attributes[type].linked;
                
                const isLinked = block.attributes[type].linked;
                
                // Update button style
                btn.style.background = isLinked ? '#a78bfa' : '#3f4451';
                btn.querySelector('svg').setAttribute('stroke', isLinked ? 'white' : '#9ca3af');
                btn.querySelector('svg').innerHTML = isLinked 
                    ? '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
                    : '<path d="M18.84 12.25l1.72-1.71h-.02a5.004 5.004 0 0 0-.12-7.07 5.006 5.006 0 0 0-6.95 0l-1.72 1.71"/><path d="M5.17 11.75l-1.71 1.71a5.004 5.004 0 0 0 .12 7.07 5.006 5.006 0 0 0 6.95 0l1.71-1.71"/><line x1="2" y1="2" x2="22" y2="22"/>';
                
                // If linking, set all values to the first non-empty value
                if (isLinked) {
                    const values = block.attributes[type];
                    const firstValue = values.top || values.right || values.bottom || values.left || '';
                    values.top = values.right = values.bottom = values.left = firstValue;
                    
                    // Update inputs
                    document.querySelectorAll(`.anvil-spacing-input[data-spacing-type="${type}"]`).forEach(input => {
                        input.value = firstValue;
                    });
                    
                    markDirty();
                    renderSingleBlock(blockId);
                }
            };
        });
        
        // Value inputs
        document.querySelectorAll('.anvil-spacing-input').forEach(input => {
            const updateHandler = () => {
                const type = input.dataset.spacingType;
                const side = input.dataset.side;
                const value = input.value;
                
                const location = findBlockLocation(blockId);
                if (!location) return;
                
                const block = location.block;
                block.attributes = block.attributes || {};
                block.attributes[type] = block.attributes[type] || { top: '', right: '', bottom: '', left: '', unit: 'px', linked: false };
                
                const spacing = block.attributes[type];
                
                if (spacing.linked) {
                    // Update all sides
                    spacing.top = spacing.right = spacing.bottom = spacing.left = value;
                    document.querySelectorAll(`.anvil-spacing-input[data-spacing-type="${type}"]`).forEach(inp => {
                        inp.value = value;
                    });
                } else {
                    spacing[side] = value;
                }
                
                markDirty();
                renderSingleBlock(blockId);
            };
            
            input.addEventListener('input', updateHandler);
            input.addEventListener('change', updateHandler);
        });
    }

    // =========================================================================
    // PAGE SETTINGS
    // =========================================================================

    function initPageSettings() {
        // Initialize form values from pageSettings
        const fullWidthCheckbox = document.getElementById('page-content-width-full');
        const widthInput = document.getElementById('page-content-width');
        const widthUnitSelect = document.getElementById('page-content-width-unit');
        const customWidthGroup = document.getElementById('page-width-custom-group');
        
        const paddingTop = document.getElementById('page-padding-top');
        const paddingRight = document.getElementById('page-padding-right');
        const paddingBottom = document.getElementById('page-padding-bottom');
        const paddingLeft = document.getElementById('page-padding-left');
        const paddingUnit = document.getElementById('page-padding-unit');
        
        const marginTop = document.getElementById('page-margin-top');
        const marginRight = document.getElementById('page-margin-right');
        const marginBottom = document.getElementById('page-margin-bottom');
        const marginLeft = document.getElementById('page-margin-left');
        const marginUnit = document.getElementById('page-margin-unit');
        
        // Set initial values
        if (fullWidthCheckbox) {
            fullWidthCheckbox.checked = pageSettings.contentWidthFull === true || pageSettings.contentWidthFull === 'true';
            toggleFullWidth(fullWidthCheckbox.checked);
            
            fullWidthCheckbox.addEventListener('change', function() {
                pageSettings.contentWidthFull = this.checked;
                toggleFullWidth(this.checked);
                applyPageSettings();
                markDirty();
            });
        }
        
        if (widthInput) {
            widthInput.value = pageSettings.contentWidth || '1200';
            widthInput.addEventListener('input', function() {
                pageSettings.contentWidth = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        
        if (widthUnitSelect) {
            widthUnitSelect.value = pageSettings.contentWidthUnit || 'px';
            widthUnitSelect.addEventListener('change', function() {
                pageSettings.contentWidthUnit = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        
        // Padding
        if (paddingTop) {
            paddingTop.value = pageSettings.paddingTop || '0';
            paddingTop.addEventListener('input', function() {
                pageSettings.paddingTop = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (paddingRight) {
            paddingRight.value = pageSettings.paddingRight || '0';
            paddingRight.addEventListener('input', function() {
                pageSettings.paddingRight = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (paddingBottom) {
            paddingBottom.value = pageSettings.paddingBottom || '0';
            paddingBottom.addEventListener('input', function() {
                pageSettings.paddingBottom = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (paddingLeft) {
            paddingLeft.value = pageSettings.paddingLeft || '0';
            paddingLeft.addEventListener('input', function() {
                pageSettings.paddingLeft = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (paddingUnit) {
            paddingUnit.value = pageSettings.paddingUnit || 'px';
            paddingUnit.addEventListener('change', function() {
                pageSettings.paddingUnit = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        
        // Margin
        if (marginTop) {
            marginTop.value = pageSettings.marginTop || '0';
            marginTop.addEventListener('input', function() {
                pageSettings.marginTop = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (marginRight) {
            marginRight.value = pageSettings.marginRight || 'auto';
            marginRight.addEventListener('input', function() {
                pageSettings.marginRight = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (marginBottom) {
            marginBottom.value = pageSettings.marginBottom || '0';
            marginBottom.addEventListener('input', function() {
                pageSettings.marginBottom = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (marginLeft) {
            marginLeft.value = pageSettings.marginLeft || 'auto';
            marginLeft.addEventListener('input', function() {
                pageSettings.marginLeft = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        if (marginUnit) {
            marginUnit.value = pageSettings.marginUnit || 'px';
            marginUnit.addEventListener('change', function() {
                pageSettings.marginUnit = this.value;
                applyPageSettings();
                markDirty();
            });
        }
        
        function toggleFullWidth(isFull) {
            if (customWidthGroup) {
                customWidthGroup.classList.toggle('hidden', isFull);
            }
        }
        
        // Apply settings on initial load
        applyPageSettings();
    }
    
    function applyPageSettings() {
        const blocksContainer = document.getElementById('anvil-live-blocks');
        if (!blocksContainer) return;
        
        const styles = [];
        const isFull = pageSettings.contentWidthFull === true || pageSettings.contentWidthFull === 'true';
        
        // Toggle full width class for seamless blending
        blocksContainer.classList.toggle('anvil-live-full-width', isFull);
        
        // Content width
        if (isFull) {
            styles.push('max-width: 100%');
            styles.push('width: 100%');
        } else {
            const width = pageSettings.contentWidth || '1200';
            const unit = pageSettings.contentWidthUnit || 'px';
            styles.push('max-width: ' + width + unit);
            styles.push('width: 100%');
        }
        
        // Padding
        const pUnit = pageSettings.paddingUnit || 'px';
        const pTop = pageSettings.paddingTop || '0';
        const pRight = pageSettings.paddingRight || '0';
        const pBottom = pageSettings.paddingBottom || '0';
        const pLeft = pageSettings.paddingLeft || '0';
        
        // Always set padding (even if 0) so it overrides default CSS
        styles.push('padding: ' + (pTop || '0') + pUnit + ' ' + (pRight || '0') + pUnit + ' ' + (pBottom || '0') + pUnit + ' ' + (pLeft || '0') + pUnit);
        
        // Margin
        const mUnit = pageSettings.marginUnit || 'px';
        const mTop = pageSettings.marginTop || '0';
        const mRight = pageSettings.marginRight || 'auto';
        const mBottom = pageSettings.marginBottom || '0';
        const mLeft = pageSettings.marginLeft || 'auto';
        
        const mTopVal = mTop === 'auto' ? 'auto' : (mTop || '0') + mUnit;
        const mRightVal = mRight === 'auto' ? 'auto' : (mRight || '0') + mUnit;
        const mBottomVal = mBottom === 'auto' ? 'auto' : (mBottom || '0') + mUnit;
        const mLeftVal = mLeft === 'auto' ? 'auto' : (mLeft || '0') + mUnit;
        
        styles.push('margin: ' + mTopVal + ' ' + mRightVal + ' ' + mBottomVal + ' ' + mLeftVal);
        
        blocksContainer.style.cssText = styles.join('; ');
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
