/**
 * Anvil Live - Core Module
 * VoidForge CMS v0.2.4
 * 
 * Establishes the AnvilLive namespace and shared state
 */
(function() {
    'use strict';

    // Create global namespace
    const AL = window.AnvilLive = window.AnvilLive || {};

    // Configuration from PHP
    AL.config = window.AnvilLiveConfig || {};

    // Shared state
    AL.state = {
        blocks: window.AnvilLiveBlocks || [],
        selectedBlockId: null,
        isDirty: false,
        undoStack: [],
        redoStack: [],
        autosaveTimer: null,
        activeEditor: null,
        pageSettings: AL.config.pageSettings || {},
        columnContext: null,
        dragState: {
            active: false,
            type: null,
            blockType: null,
            blockId: null,
            dropIndex: -1,
            dropColumn: null,
            ghost: null,
            indicator: null
        }
    };

    // =========================================================================
    // STATE ACCESS METHODS
    // =========================================================================

    AL.getBlocks = () => AL.state.blocks;
    AL.setBlocks = (b) => { AL.state.blocks = b; };
    AL.getSelectedBlockId = () => AL.state.selectedBlockId;
    AL.setSelectedBlockId = (id) => { AL.state.selectedBlockId = id; };
    AL.isDirtyState = () => AL.state.isDirty;
    AL.getPageSettings = () => AL.state.pageSettings;
    AL.setPageSettings = (s) => { AL.state.pageSettings = s; };
    AL.getColumnContext = () => AL.state.columnContext;
    AL.getDragState = () => AL.state.dragState;

    AL.markDirty = function() {
        AL.state.isDirty = true;
        const saveBtn = document.getElementById('anvil-live-save');
        if (saveBtn) saveBtn.classList.add('has-changes');
    };

    AL.markClean = function() {
        AL.state.isDirty = false;
        const saveBtn = document.getElementById('anvil-live-save');
        if (saveBtn) saveBtn.classList.remove('has-changes');
    };

    // =========================================================================
    // COLUMN CONTEXT
    // =========================================================================

    AL.setColumnContext = function(blockId, columnIndex) {
        AL.state.columnContext = { blockId, columnIndex };
        document.getElementById('anvil-live-sidebar')?.classList.add('adding-to-column');
        console.log('AnvilLive: Column context set', AL.state.columnContext);
    };

    AL.clearColumnContext = function() {
        AL.state.columnContext = null;
        document.getElementById('anvil-live-sidebar')?.classList.remove('adding-to-column');
        console.log('AnvilLive: Column context cleared');
    };

    // =========================================================================
    // UTILITIES
    // =========================================================================

    AL.Utils = {
        generateBlockId: function() {
            return 'block-' + Math.random().toString(36).substr(2, 12);
        },

        escapeHtml: function(str) {
            if (typeof str !== 'string') return str;
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        normalizeUrl: function(url, type = 'url') {
            if (!url || typeof url !== 'string') return url;
            url = url.trim();
            if (!url) return url;
            if (type === 'email') {
                return url.startsWith('mailto:') ? url : 'mailto:' + url;
            }
            if (!url.match(/^https?:\/\//i) && !url.startsWith('/') && !url.startsWith('#')) {
                return 'https://' + url;
            }
            return url;
        },

        formatLabel: function(key) {
            return key.replace(/([A-Z])/g, ' $1').replace(/[_-]/g, ' ').replace(/^\w/, c => c.toUpperCase()).trim();
        },

        deepClone: function(obj) {
            return JSON.parse(JSON.stringify(obj));
        },

        findBlockLocation: function(blockId) {
            const blocks = AL.state.blocks;
            const mainIndex = blocks.findIndex(b => b.id === blockId);
            if (mainIndex !== -1) {
                return { type: 'main', index: mainIndex, block: blocks[mainIndex] };
            }
            for (let i = 0; i < blocks.length; i++) {
                if (blocks[i].type === 'columns' && Array.isArray(blocks[i].attributes?.columns)) {
                    for (let colIdx = 0; colIdx < blocks[i].attributes.columns.length; colIdx++) {
                        const column = blocks[i].attributes.columns[colIdx];
                        if (Array.isArray(column)) {
                            const blockIdx = column.findIndex(b => b.id === blockId);
                            if (blockIdx !== -1) {
                                return { type: 'column', parentIndex: i, columnIndex: colIdx, index: blockIdx, block: column[blockIdx] };
                            }
                        }
                    }
                }
            }
            return null;
        },

        getBlockStyles: function(attrs) {
            let style = '';
            
            // Margin
            if (attrs.margin) {
                const m = attrs.margin;
                const unit = m.unit || 'px';
                if (m.top) style += `margin-top:${m.top}${unit};`;
                if (m.right) style += `margin-right:${m.right}${unit};`;
                if (m.bottom) style += `margin-bottom:${m.bottom}${unit};`;
                if (m.left) style += `margin-left:${m.left}${unit};`;
            }
            
            // Padding
            if (attrs.padding) {
                const p = attrs.padding;
                const unit = p.unit || 'px';
                if (p.top) style += `padding-top:${p.top}${unit};`;
                if (p.right) style += `padding-right:${p.right}${unit};`;
                if (p.bottom) style += `padding-bottom:${p.bottom}${unit};`;
                if (p.left) style += `padding-left:${p.left}${unit};`;
            }
            
            // Typography
            if (attrs.typography) {
                const t = attrs.typography;
                if (t.fontSize) style += `font-size:${t.fontSize}${t.fontSizeUnit || 'px'};`;
                if (t.fontWeight) style += `font-weight:${t.fontWeight};`;
                if (t.lineHeight) style += `line-height:${t.lineHeight};`;
                if (t.letterSpacing) style += `letter-spacing:${t.letterSpacing}px;`;
                if (t.textTransform) style += `text-transform:${t.textTransform};`;
                if (t.fontStyle) style += `font-style:${t.fontStyle};`;
            }
            
            // Colors
            if (attrs.colors) {
                const c = attrs.colors;
                if (c.textColor) style += `color:${c.textColor};`;
                if (c.backgroundColor) style += `background-color:${c.backgroundColor};`;
            }
            
            // Border
            if (attrs.border) {
                const b = attrs.border;
                if (b.style && b.style !== 'none') {
                    style += `border-style:${b.style};`;
                    if (b.width) style += `border-width:${b.width}px;`;
                    if (b.color) style += `border-color:${b.color};`;
                }
                if (b.radius) style += `border-radius:${b.radius}px;`;
            }
            
            // Box Shadow
            if (attrs.boxShadow) {
                const s = attrs.boxShadow;
                if (s.preset && s.preset !== 'custom') {
                    const presets = {
                        sm: '0 1px 2px 0 rgba(0,0,0,0.05)',
                        md: '0 4px 6px -1px rgba(0,0,0,0.1)',
                        lg: '0 10px 15px -3px rgba(0,0,0,0.1)',
                        xl: '0 20px 25px -5px rgba(0,0,0,0.1)'
                    };
                    if (presets[s.preset]) style += `box-shadow:${presets[s.preset]};`;
                } else if (s.preset === 'custom') {
                    style += `box-shadow:${s.x||0}px ${s.y||4}px ${s.blur||6}px ${s.spread||0}px ${s.color||'rgba(0,0,0,0.1)'};`;
                }
            }
            
            // Z-index
            if (attrs.customAttributes?.zIndex) {
                style += `z-index:${attrs.customAttributes.zIndex};`;
            }
            
            // Background
            if (attrs.background) {
                const bg = attrs.background;
                if (bg.type === 'color' && bg.color) {
                    style += `background-color:${bg.color};`;
                } else if (bg.type === 'gradient') {
                    const c1 = bg.gradientColor1 || '#6366f1';
                    const c2 = bg.gradientColor2 || '#a855f7';
                    const angle = bg.gradientAngle || 135;
                    if (bg.gradientType === 'radial') {
                        style += `background:radial-gradient(circle, ${c1}, ${c2});`;
                    } else {
                        style += `background:linear-gradient(${angle}deg, ${c1}, ${c2});`;
                    }
                } else if (bg.type === 'image' && bg.imageUrl) {
                    style += `background-image:url('${bg.imageUrl}');`;
                    style += `background-position:${bg.imagePosition || 'center center'};`;
                    style += `background-size:${bg.imageSize || 'cover'};`;
                    style += `background-repeat:${bg.imageRepeat || 'no-repeat'};`;
                }
            }
            
            // Sizing
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
            
            // Transform
            if (attrs.transform) {
                const tr = attrs.transform;
                const transforms = [];
                if (tr.rotate) transforms.push(`rotate(${tr.rotate}deg)`);
                if (tr.scale && tr.scale !== 1) transforms.push(`scale(${tr.scale})`);
                if (tr.translateX) transforms.push(`translateX(${tr.translateX}px)`);
                if (tr.translateY) transforms.push(`translateY(${tr.translateY}px)`);
                if (tr.skewX) transforms.push(`skewX(${tr.skewX}deg)`);
                if (tr.skewY) transforms.push(`skewY(${tr.skewY}deg)`);
                if (transforms.length) style += `transform:${transforms.join(' ')};`;
            }
            
            // Animation
            if (attrs.animation?.transitionDuration) {
                style += `transition:all ${attrs.animation.transitionDuration}ms ease;`;
            }
            
            return style;
        },

        getBlockClasses: function(attrs) {
            let classes = [];
            if (attrs.customAttributes?.cssClasses) classes.push(attrs.customAttributes.cssClasses);
            if (attrs.responsive) {
                if (attrs.responsive.hideDesktop) classes.push('anvil-hide-desktop');
                if (attrs.responsive.hideTablet) classes.push('anvil-hide-tablet');
                if (attrs.responsive.hideMobile) classes.push('anvil-hide-mobile');
            }
            if (attrs.animation) {
                if (attrs.animation.entrance) classes.push(`anvil-anim-${attrs.animation.entrance}`);
                if (attrs.animation.hover) classes.push(`anvil-hover-${attrs.animation.hover}`);
            }
            return classes.join(' ');
        },

        getBlockCssId: function(attrs) {
            return attrs.customAttributes?.cssId || '';
        }
    };

    // =========================================================================
    // INITIALIZATION
    // =========================================================================

    AL.init = function() {
        console.log('AnvilLive: Initializing editor');
        
        document.body.classList.add('anvil-live-editing', 'anvil-live-sidebar-open');
        
        // Initialize modules in order
        const modules = ['Drag', 'Editor', 'UI', 'Blocks', 'Settings', 'ColorPicker', 'Modals', 'PageSettings'];
        modules.forEach(mod => {
            if (AL[mod] && typeof AL[mod].init === 'function') {
                AL[mod].init();
            }
        });
        
        // Make blocks editable
        if (AL.Editor) AL.Editor.makeBlocksEditable();
        
        // Save initial state for undo
        if (AL.Save) AL.Save.saveState();
        
        // Init keyboard shortcuts
        if (AL.Keyboard) AL.Keyboard.init();
        
        // Init autosave
        if (AL.Save) AL.Save.initAutosave();
        
        console.log('AnvilLive: Editor ready with', AL.state.blocks.length, 'blocks');
    };

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', AL.init);
    } else {
        setTimeout(AL.init, 10);
    }

})();
