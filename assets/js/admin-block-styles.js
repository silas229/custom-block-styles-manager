/* eslint-env browser */
(() => {
    "use strict";

    const sanitize = (v = "") =>
        String(v)
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, "-")
            .replace(/^-+|-+$/g, "");

    const boilerplateFor = (slug) => (slug ? `.is-style-${slug} {\n\n}\n` : "");

    const looksLikeBoilerplate = (s) => {
        if (!s) return true;
        const t = s.trim();
        return t === "" || /^\.is-style-[a-z0-9_-]+\s*\{\s*\}$/i.test(t);
    };

    const run = () => {
        const settings = window?.cbsmCodeEditorSettings?.settings;
        let editor = null;
        if (window?.wp?.codeEditor && settings) {
            try {
                // store and reuse the initialized editor instance to avoid relying solely on wp.codeEditor.instances
                editor = window.wp.codeEditor.initialize("cbsm-custom-css", settings);
            } catch (e) {
                console.debug?.(e);
            }
        }

        const slugEl = document.getElementById("post_name");
        const previewEl = document.getElementById("cbsm-style-class-preview");
        const cssEl = document.getElementById("cbsm-custom-css");
        const placeholder = previewEl?.dataset?.placeholder ?? "is-style-{slug}";

        const update = (force = false) => {
            const slug = sanitize(slugEl?.value);
            if (previewEl)
                previewEl.textContent = slug ? `is-style-${slug}` : placeholder;
            if (!cssEl) return;

            const current = cssEl.value;
            const bp = boilerplateFor(slug);
            if (force || looksLikeBoilerplate(current)) {
                cssEl.value = bp;
                if (editor) {
                    try {
                        editor.setValue(bp);
                    } catch (err) {
                        console.debug?.(err);
                    }
                }
            }
        };

        slugEl?.addEventListener("input", () => update(false));
        slugEl?.addEventListener("change", () => update(false));
        update(true);
    };

    if (document.readyState === "loading")
        document.addEventListener("DOMContentLoaded", run);
    else run();
})();
