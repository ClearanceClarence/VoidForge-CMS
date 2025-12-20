# Anvil Live JavaScript Modules

The Anvil Live editor JavaScript has been split into modular files for better maintainability. 

## Module Structure

### Current Modules (in `modules/` directory)

| Module | Lines | Description |
|--------|-------|-------------|
| `anvil-live-core.js` | ~300 | Core namespace, state management, utilities |
| `anvil-live-drag.js` | ~280 | Drag and drop functionality |
| `anvil-live-editor.js` | ~400 | Rich text toolbar and inline editing |
| `anvil-live-ui.js` | ~230 | Top bar, sidebar, canvas handlers |
| `anvil-live-save.js` | ~120 | Save, autosave, undo/redo |
| `anvil-live-blocks.js` | ~350 | Block CRUD, selection, rendering |

### Still in Original File (to be extracted)

- **Settings Panel** (~1200 lines) - Block settings controls, typography, colors, borders, etc.
- **Modals** (~80 lines) - Modal dialogs
- **Color Picker** (~650 lines) - Custom color picker component
- **Page Settings** (~200 lines) - Page-level settings panel
- **Keyboard Shortcuts** (~40 lines) - Keyboard shortcut handling

## Using Modules

### Option 1: Use Original File (Recommended for now)
The original `anvil-live.js` file is still complete and working. Use it until all modules are extracted and tested.

### Option 2: Build from Modules
```bash
cd includes/anvil-live/assets/js
node build.js
```
This creates `anvil-live.built.js` from the modules.

### Option 3: Load Modules Individually
Update PHP to load modules in order:
```php
$modules = ['core', 'drag', 'editor', 'ui', 'save', 'blocks'];
foreach ($modules as $module) {
    echo '<script src="' . $baseUrl . '/js/modules/anvil-live-' . $module . '.js"></script>';
}
```

## Module Load Order

Modules must be loaded in this order:
1. `anvil-live-core.js` - Establishes `window.AnvilLive` namespace
2. `anvil-live-drag.js` - Depends on: Core
3. `anvil-live-editor.js` - Depends on: Core, Blocks
4. `anvil-live-ui.js` - Depends on: Core, Drag, Editor, Blocks, Save, Modals
5. `anvil-live-save.js` - Depends on: Core, Blocks, Editor
6. `anvil-live-blocks.js` - Depends on: Core, Utils, Save, Editor, Settings

## Namespace Structure

All modules attach to `window.AnvilLive`:

```javascript
window.AnvilLive = {
    // Configuration (from PHP)
    config: { ... },
    
    // Shared state
    state: {
        blocks: [],
        selectedBlockId: null,
        isDirty: false,
        undoStack: [],
        redoStack: [],
        // ...
    },
    
    // Modules
    Utils: { ... },
    Drag: { ... },
    Editor: { ... },
    UI: { ... },
    Save: { ... },
    Blocks: { ... },
    Settings: { ... },
    Modals: { ... },
    PageSettings: { ... },
    
    // Core methods
    init: function() { ... },
    markDirty: function() { ... },
    // ...
};
```

## Development Tips

1. Each module should check for `window.AnvilLive` before using it
2. Cross-module calls use the namespace: `AL.Blocks.render()`
3. State is shared via `AL.state`
4. Configuration is in `AL.config`

## TODO

- [ ] Extract Settings module (large, complex)
- [ ] Extract Color Picker module
- [ ] Extract Modals module
- [ ] Extract Page Settings module
- [ ] Add unit tests
- [ ] Add source maps for debugging
