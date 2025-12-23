/**
 * Anvil Live - Save Module
 * Saving, autosave, and undo/redo functionality
 */
(function() {
    'use strict';
    const AL = window.AnvilLive;
    if (!AL) return console.error('AnvilLive core not loaded');

    AL.Save = {
        init: function() {
            this.initAutosave();
        },

        updateStatus: function(text, className) {
            const status = document.getElementById('anvil-live-save-status');
            if (status) {
                status.className = 'anvil-live-save-status ' + className;
                const textEl = status.querySelector('.anvil-live-save-status-text');
                if (textEl) textEl.textContent = text;
            }
        },

        saveState: function() {
            AL.state.undoStack.push(JSON.stringify(AL.state.blocks));
            if (AL.state.undoStack.length > 50) AL.state.undoStack.shift();
            AL.state.redoStack = [];
            this.updateUndoRedoButtons();
        },

        undo: function() {
            if (AL.state.undoStack.length < 2) return;
            AL.state.redoStack.push(AL.state.undoStack.pop());
            AL.state.blocks = JSON.parse(AL.state.undoStack[AL.state.undoStack.length - 1]);
            AL.markDirty();
            AL.Blocks.render();
        },

        redo: function() {
            if (AL.state.redoStack.length === 0) return;
            const state = AL.state.redoStack.pop();
            AL.state.undoStack.push(state);
            AL.state.blocks = JSON.parse(state);
            AL.markDirty();
            AL.Blocks.render();
        },

        updateUndoRedoButtons: function() {
            const undoBtn = document.getElementById('anvil-live-undo');
            const redoBtn = document.getElementById('anvil-live-redo');
            if (undoBtn) undoBtn.disabled = AL.state.undoStack.length < 2;
            if (redoBtn) redoBtn.disabled = AL.state.redoStack.length === 0;
        },

        saveContent: async function() {
            if (AL.state.activeEditor && AL.Editor) AL.Editor.syncBlockContent();
            
            this.updateStatus('Saving...', 'saving');

            const title = document.getElementById('anvil-live-title')?.value || AL.config.postTitle;

            try {
                const response = await fetch(AL.config.apiUrl + '/anvil-live/save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': AL.config.nonce },
                    body: JSON.stringify({ 
                        post_id: AL.config.postId, 
                        blocks: AL.state.blocks, 
                        title, 
                        pageSettings: AL.state.pageSettings 
                    })
                });

                const data = await response.json();

                if (data.success) {
                    AL.state.isDirty = false;
                    this.updateStatus('Saved', 'saved');
                } else {
                    this.updateStatus('Error: ' + (data.error || 'Unknown'), 'error');
                }
            } catch (err) {
                console.error('Save failed:', err);
                this.updateStatus('Save failed', 'error');
            }
        },

        previewContent: async function() {
            if (AL.state.isDirty) {
                await this.saveContent();
            }
            window.open(AL.config.exitUrl, '_blank');
        },

        autosave: async function() {
            if (!AL.state.isDirty) return;
            if (AL.state.activeEditor && AL.Editor) AL.Editor.syncBlockContent();

            try {
                await fetch(AL.config.apiUrl + '/anvil-live/autosave', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': AL.config.nonce },
                    body: JSON.stringify({ post_id: AL.config.postId, blocks: AL.state.blocks })
                });
            } catch (err) {
                console.error('Autosave failed:', err);
            }
        },

        initAutosave: function() {
            const self = this;
            window.addEventListener('beforeunload', (e) => {
                if (AL.state.isDirty) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        startAutosaveTimer: function() {
            if (AL.state.autosaveTimer) clearTimeout(AL.state.autosaveTimer);
            AL.state.autosaveTimer = setTimeout(() => this.autosave(), 30000);
        }
    };

    // Override markDirty to include autosave timer
    const originalMarkDirty = AL.markDirty;
    AL.markDirty = function() {
        originalMarkDirty.call(AL);
        AL.Save.updateStatus('Unsaved changes', '');
        AL.Save.updateUndoRedoButtons();
        AL.Save.startAutosaveTimer();
    };

})();
