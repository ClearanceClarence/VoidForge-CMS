/**
 * Anvil Live - Editor Module
 * Rich text toolbar and inline editing
 */
(function() {
    'use strict';
    const AL = window.AnvilLive;
    if (!AL) return console.error('AnvilLive core not loaded');

    let savedSelection = null;

    AL.Editor = {
        init: function() {
            this.createRichTextToolbar();
            this.createLinkPopup();
            this.initInlineEditing();
        },

        createRichTextToolbar: function() {
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

            const self = this;
            toolbar.querySelectorAll('.anvil-live-rte-btn').forEach(btn => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const command = btn.dataset.command;
                    if (command === 'createLink') {
                        self.showLinkPopup();
                    } else {
                        self.execCommand(command);
                    }
                    self.updateToolbarState();
                });
            });
        },

        createLinkPopup: function() {
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

            const self = this;
            document.getElementById('anvil-live-link-cancel').addEventListener('click', () => self.hideLinkPopup());
            document.getElementById('anvil-live-link-insert').addEventListener('click', () => self.insertLink());
            document.getElementById('anvil-live-link-url').addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); self.insertLink(); }
                else if (e.key === 'Escape') { self.hideLinkPopup(); }
            });
        },

        showLinkPopup: function() {
            savedSelection = this.saveSelection();
            
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
        },

        hideLinkPopup: function() {
            document.getElementById('anvil-live-link-popup').classList.remove('active');
            if (savedSelection) this.restoreSelection(savedSelection);
        },

        insertLink: function() {
            const url = document.getElementById('anvil-live-link-url').value.trim();
            this.hideLinkPopup();
            if (savedSelection) this.restoreSelection(savedSelection);
            
            if (url) {
                const finalUrl = /^https?:\/\//i.test(url) ? url : 'https://' + url;
                this.execCommand('createLink', finalUrl);
            }
            this.syncBlockContent();
        },

        saveSelection: function() {
            const sel = window.getSelection();
            return sel.rangeCount > 0 ? sel.getRangeAt(0).cloneRange() : null;
        },

        restoreSelection: function(range) {
            if (range) {
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            }
        },

        execCommand: function(command, value = null) {
            const activeEditor = AL.state.activeEditor;
            
            // Handle justify commands
            if (command.startsWith('justify') && activeEditor) {
                const alignmentRaw = command.replace('justify', '');
                const alignment = alignmentRaw.charAt(0).toLowerCase() + alignmentRaw.slice(1).toLowerCase();
                const alignMap = { 'left': 'left', 'center': 'center', 'right': 'right', 'full': 'justify' };
                const textAlignValue = alignMap[alignment] || 'left';
                
                activeEditor.style.textAlign = textAlignValue;
                
                const blockEl = activeEditor.closest('.anvil-live-block');
                if (blockEl) {
                    const blockId = blockEl.dataset.blockId;
                    const location = AL.Utils.findBlockLocation(blockId);
                    if (location) {
                        location.block.attributes = location.block.attributes || {};
                        location.block.attributes.align = textAlignValue;
                    }
                }
                
                AL.markDirty();
                this.updateToolbarState();
                return;
            }
            
            document.execCommand(command, false, value);
            this.syncBlockContent();
            AL.markDirty();
        },

        showToolbar: function(element) {
            const toolbar = document.getElementById('anvil-live-rte-toolbar');
            if (!toolbar) return;
            
            const rect = element.getBoundingClientRect();
            toolbar.style.left = (rect.left + rect.width / 2) + 'px';
            toolbar.style.top = (rect.top - 50 + window.scrollY) + 'px';
            toolbar.classList.add('active');
            this.updateToolbarState();
        },

        hideToolbar: function() {
            document.getElementById('anvil-live-rte-toolbar')?.classList.remove('active');
            this.hideLinkPopup();
        },

        updateToolbarState: function() {
            const toolbar = document.getElementById('anvil-live-rte-toolbar');
            const activeEditor = AL.state.activeEditor;
            if (!toolbar) return;
            
            toolbar.querySelectorAll('.anvil-live-rte-btn[data-command]').forEach(btn => {
                const command = btn.dataset.command;
                try {
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
                } catch (e) {}
            });
        },

        initInlineEditing: function() {
            const self = this;
            
            document.addEventListener('click', (e) => {
                const editable = e.target.closest('[contenteditable="true"]');
                const blockContent = e.target.closest('.anvil-live-block-content[data-editable="true"]');
                
                if (editable && blockContent) {
                    const blockEl = blockContent.closest('.anvil-live-block');
                    if (blockEl) self.startEditing(blockEl, editable);
                }
            });

            document.addEventListener('selectionchange', () => {
                if (AL.state.activeEditor) self.updateToolbarState();
            });
        },

        makeBlocksEditable: function() {
            const self = this;
            const editableContents = document.querySelectorAll('.anvil-live-block-content[data-editable="true"]');
            
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
                
                if (editableEl && !editableEl.hasAttribute('contenteditable')) {
                    editableEl.setAttribute('contenteditable', 'true');
                    editableEl.setAttribute('data-placeholder', self.getPlaceholder(blockType));
                    
                    editableEl.addEventListener('focus', (e) => self.handleEditableFocus(e));
                    editableEl.addEventListener('blur', (e) => self.handleEditableBlur(e));
                    editableEl.addEventListener('input', (e) => self.handleEditableInput(e));
                    editableEl.addEventListener('keydown', (e) => self.handleEditableKeydown(e));
                    editableEl.addEventListener('paste', (e) => self.handleEditablePaste(e));
                }
            });
        },

        getPlaceholder: function(blockType) {
            const placeholders = {
                paragraph: 'Type something...',
                heading: 'Heading',
                quote: 'Enter a quote...',
                button: 'Button text',
                list: 'List item'
            };
            return placeholders[blockType] || 'Type here...';
        },

        startEditing: function(blockEl, editableEl) {
            if (AL.state.activeEditor && AL.state.activeEditor !== editableEl) this.endEditing();
            
            AL.state.activeEditor = editableEl;
            blockEl.classList.add('editing');
            AL.Blocks.select(blockEl.dataset.blockId);
            this.showToolbar(editableEl);
        },

        endEditing: function() {
            if (AL.state.activeEditor) {
                const blockEl = AL.state.activeEditor.closest('.anvil-live-block');
                if (blockEl) blockEl.classList.remove('editing');
                this.syncBlockContent();
                AL.state.activeEditor = null;
            }
            this.hideToolbar();
        },

        handleEditableFocus: function(e) {
            const blockEl = e.target.closest('.anvil-live-block');
            if (blockEl) this.startEditing(blockEl, e.target);
        },

        handleEditableBlur: function(e) {
            const self = this;
            setTimeout(() => {
                const toolbar = document.getElementById('anvil-live-rte-toolbar');
                const linkPopup = document.getElementById('anvil-live-link-popup');
                
                if (!toolbar?.contains(document.activeElement) && 
                    !linkPopup?.contains(document.activeElement) &&
                    document.activeElement !== e.target) {
                    self.endEditing();
                }
            }, 100);
        },

        handleEditableInput: function(e) {
            AL.markDirty();
            this.syncBlockContent();
            if (AL.state.activeEditor === e.target) this.showToolbar(e.target);
        },

        handleEditableKeydown: function(e) {
            const self = this;
            const blocks = AL.state.blocks;
            
            if (e.ctrlKey || e.metaKey) {
                switch (e.key.toLowerCase()) {
                    case 'b': e.preventDefault(); this.execCommand('bold'); break;
                    case 'i': e.preventDefault(); this.execCommand('italic'); break;
                    case 'u': e.preventDefault(); this.execCommand('underline'); break;
                    case 'k': e.preventDefault(); this.showLinkPopup(); break;
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
                    
                    this.syncBlockContent();
                    
                    const blockIndex = blocks.findIndex(b => b.id === blockEl.dataset.blockId);
                    const newBlock = {
                        id: AL.Utils.generateBlockId(),
                        type: 'paragraph',
                        attributes: { content: '' }
                    };
                    
                    blocks.splice(blockIndex + 1, 0, newBlock);
                    AL.Save.saveState();
                    AL.markDirty();
                    AL.Blocks.render();
                    
                    setTimeout(() => {
                        const newBlockEl = document.querySelector(`[data-block-id="${newBlock.id}"]`);
                        const newEditable = newBlockEl?.querySelector('[contenteditable]');
                        if (newEditable) {
                            if (afterContent.textContent) {
                                newEditable.appendChild(afterContent);
                                self.syncBlockContent();
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
                            
                            AL.Save.saveState();
                            AL.markDirty();
                            AL.Blocks.render();
                            
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
        },

        handleEditablePaste: function(e) {
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
                content = AL.Utils.escapeHtml(content).replace(/\n/g, '<br>');
            }
            
            document.execCommand('insertHTML', false, content);
            this.syncBlockContent();
            AL.markDirty();
        },

        syncBlockContent: function() {
            const activeEditor = AL.state.activeEditor;
            if (!activeEditor) return;
            
            const blockEl = activeEditor.closest('.anvil-live-block');
            if (!blockEl) return;
            
            const blockId = blockEl.dataset.blockId;
            const location = AL.Utils.findBlockLocation(blockId);
            if (!location) return;
            
            const block = location.block;
            
            if (block.type === 'button') {
                block.attributes.text = activeEditor.textContent;
            } else {
                block.attributes.content = activeEditor.innerHTML;
            }
            
            if (activeEditor.style.textAlign) {
                block.attributes.align = activeEditor.style.textAlign;
            }
        }
    };

})();
