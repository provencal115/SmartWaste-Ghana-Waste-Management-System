'use strict';

/**
 * Optional dev test — requires: npm install jsdom (no package.json needed; run once from project root).
 * Skips gracefully when jsdom is not installed.
 */
let JSDOM;
try {
    ({ JSDOM } = require('jsdom'));
} catch (e) {
    console.log('SKIP: jsdom not installed — run "npm install jsdom" to enable this test');
    process.exit(0);
}

const fs = require('fs');
const path = require('path');

const html = `<!DOCTYPE html><html><head></head><body>
<form id="registerForm">
  <div class="password-field-group" data-password-group>
    <input type="password" name="password" id="registerPassword" data-password-enhanced>
  </div>
</form>
<link rel="stylesheet" href="file://${path.join(__dirname, '../assets/css/password-field.css').replace(/\\/g, '/')}">
</body></html>`;

const dom = new JSDOM(html, {
    runScripts: 'dangerously',
    resources: 'usable',
    url: 'http://localhost/',
});

const { window } = dom;
const { document } = window;

const script = fs.readFileSync(path.join(__dirname, '../assets/js/password-field.js'), 'utf8');
const scriptEl = document.createElement('script');
scriptEl.textContent = script;
document.body.appendChild(scriptEl);

window.SmartWastePassword.init();

function report(label) {
    const panel = document.querySelector('.password-strength-panel');
    const style = panel ? window.getComputedStyle(panel) : null;
    console.log(label, {
        panelExists: !!panel,
        hidden: panel?.hidden,
        className: panel?.className || null,
        display: style?.display || null,
        label: panel?.querySelector('.password-strength-label')?.textContent || null,
    });
}

report('TEST 1 - after init (empty)');

const input = document.getElementById('registerPassword');
input.focus();
report('TEST 2 - focus empty');

input.value = 'A';
input.dispatchEvent(new window.Event('input', { bubbles: true }));
report('TEST 3 - typed A');

input.value = 'Abc12345!';
input.dispatchEvent(new window.Event('input', { bubbles: true }));
report('TEST 4 - strong password');

input.value = '';
input.dispatchEvent(new window.Event('input', { bubbles: true }));
report('TEST 5 - cleared');

let failed = 0;
const panelAfterInit = document.querySelector('.password-strength-panel');
if (panelAfterInit) {
    console.error('FAIL: panel should not exist when empty');
    failed++;
}
input.value = 'x';
input.dispatchEvent(new window.Event('input', { bubbles: true }));
if (!document.querySelector('.password-strength-panel')) {
    console.error('FAIL: panel should exist when typing');
    failed++;
}
input.value = '';
input.dispatchEvent(new window.Event('input', { bubbles: true }));
if (document.querySelector('.password-strength-panel')) {
    console.error('FAIL: panel should be removed when cleared');
    failed++;
}

console.log(failed === 0 ? 'ALL TESTS PASSED' : failed + ' TEST(S) FAILED');
process.exit(failed === 0 ? 0 : 1);
