/*
Live Code Editor 
By: Coding Design

You can do whatever you want with the code. However if you love my content, you can subscribed my YouTube Channel
🌎link: www.youtube.com/codingdesign

*/

const html_code = document.querySelector('.html-code textarea');
const css_code = document.querySelector('.css-code textarea');
const js_code = document.querySelector('.js-code textarea');
const result = document.querySelector('#result');

function escapeClosingScriptTags(code) {
    // Prevent breaking out of <script> when embedding user JS in srcdoc
    return String(code ?? '').replace(/<\/script/gi, '<\\/script');
}

function addBasicLoopProtector(js) {
    // Best-effort only (regex-based); helps against common infinite loops with braces.
    const src = String(js ?? '');
    return src
        .replace(/for\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
        .replace(/while\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
        .replace(/do\s*\{/g, (m) => `${m}\n__loopProtect();`);
}

function buildSrcDoc(html, css, js) {
    const safeJs = escapeClosingScriptTags(addBasicLoopProtector(js));

    return `<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>${css ?? ''}</style>
  </head>
  <body>
    ${html ?? ''}
    <script>
      (function () {
        // Show runtime errors in the iframe itself (and console).
        function showError(msg) {
          try {
            console.error(msg);
            var pre = document.getElementById('__live_editor_error__');
            if (!pre) {
              pre = document.createElement('pre');
              pre.id = '__live_editor_error__';
              pre.style.cssText = 'white-space:pre-wrap;padding:12px;margin:12px;border:1px solid #ff4d4f;background:#fff1f0;color:#a8071a;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-size:12px;';
              document.body.prepend(pre);
            }
            pre.textContent = String(msg);
          } catch (_) {}
        }

        window.addEventListener('error', function (e) {
          showError(e && e.error ? (e.error.stack || e.error.message) : (e.message || 'Error'));
        });
        window.addEventListener('unhandledrejection', function (e) {
          var r = e && e.reason;
          showError(r && r.stack ? r.stack : (r ? String(r) : 'Unhandled promise rejection'));
        });

        // Basic loop protector
        var __lpStart = Date.now();
        window.__loopProtect = function () {
          if (Date.now() - __lpStart > 500) {
            throw new Error('Loop protector: kemungkinan infinite loop (stop setelah 500ms).');
          }
        };

        try {
          ${safeJs}
        } catch (err) {
          showError(err && err.stack ? err.stack : err);
        }
      })();
    </script>
  </body>
</html>`;
}

function run() {
    const html = html_code.value ?? '';
    const css = css_code.value ?? '';
    const js = js_code.value ?? '';

    // Storing data in Local Storage
    localStorage.setItem('html_code', html);
    localStorage.setItem('css_code', css);
    localStorage.setItem('js_code', js);

    // Execute safely inside sandboxed iframe via srcdoc (no parent eval)
    result.srcdoc = buildSrcDoc(html, css, js);
}

function debounce(fn, waitMs) {
    let t;
    return () => {
        clearTimeout(t);
        t = setTimeout(fn, waitMs);
    };
}

const runDebounced = debounce(run, 250);

// Checking if user is typing anything in input field
html_code.onkeyup = () => runDebounced();
css_code.onkeyup = () => runDebounced();
js_code.onkeyup = () => runDebounced();

// Accessing data stored in Local Storage. To make it more advanced you could check if there is any data stored in Local Storage.
html_code.value = localStorage.getItem('html_code') || '';
css_code.value = localStorage.getItem('css_code') || '';
js_code.value = localStorage.getItem('js_code') || '';

// First render
run();
