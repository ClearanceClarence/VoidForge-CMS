/**
 * Anvil Live - Build Script
 * Run with: node build.js
 * 
 * Concatenates all modules into a single anvil-live.js file
 */

const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, 'modules');
const outputFile = path.join(__dirname, 'anvil-live.built.js');

// Module load order (important!)
const moduleOrder = [
    'anvil-live-core.js',      // Must be first - establishes namespace
    'anvil-live-drag.js',       // Drag and drop
    'anvil-live-editor.js',     // Rich text editing
    'anvil-live-ui.js',         // UI handlers
    'anvil-live-save.js',       // Save functionality
    'anvil-live-blocks.js',     // Block operations
    // 'anvil-live-settings.js', // Settings panel (TODO)
    // 'anvil-live-modals.js',   // Modals (TODO)
    // 'anvil-live-page-settings.js', // Page settings (TODO)
];

console.log('Building Anvil Live from modules...\n');

let output = `/**
 * Anvil Live - Frontend Visual Editor (Built from modules)
 * VoidForge CMS v0.2.4
 * 
 * Built on: ${new Date().toISOString()}
 * 
 * Modules included:
 * ${moduleOrder.map(m => ' - ' + m).join('\n * ')}
 */

`;

let totalLines = 0;

moduleOrder.forEach(moduleName => {
    const modulePath = path.join(modulesDir, moduleName);
    
    if (!fs.existsSync(modulePath)) {
        console.log(`⚠️  Skipping ${moduleName} (not found)`);
        return;
    }
    
    const content = fs.readFileSync(modulePath, 'utf8');
    const lines = content.split('\n').length;
    totalLines += lines;
    
    output += `\n// ============================================================================\n`;
    output += `// MODULE: ${moduleName}\n`;
    output += `// ============================================================================\n\n`;
    output += content;
    output += '\n';
    
    console.log(`✓ Added ${moduleName} (${lines} lines)`);
});

fs.writeFileSync(outputFile, output);

console.log(`\n✓ Build complete!`);
console.log(`  Output: ${outputFile}`);
console.log(`  Total: ${totalLines} lines`);
console.log(`\nTo use: Replace anvil-live.js with anvil-live.built.js`);
