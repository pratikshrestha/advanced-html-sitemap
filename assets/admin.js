(function () {

    function AdvancedHTMLSitemapGenerateShortcode() {
        const form = document.getElementById("sitemap-shortcode-generator");
        if (!form) return;

        const attrs = [];

        // Collect checked post types
        const postTypeChecks = form.querySelectorAll('input[name="post_types[]"]:checked');
        if (postTypeChecks.length) {
            const postTypes = Array.from(postTypeChecks).map(el => el.value);
            attrs.push(`post_types="${postTypes.join(",")}"`);
        }

        // Collect other fields
        const inputs = form.querySelectorAll("input, select");
        inputs.forEach((el) => {
            if (!el.name || el.name === "post_types[]") return;

            if (el.type === "checkbox") {
                attrs.push(`${el.name}="${el.checked ? "true" : "false"}"`);
            } else if (el.value) {
                attrs.push(`${el.name}="${el.value}"`);
            }
        });

        const shortcode = `[advanced_html_sitemap ${attrs.join(" ")}]`;
        document.getElementById("generated-shortcode").value = shortcode;
        
        const copyBtn = document.getElementById("ahs-copy-shortcode");
        if (copyBtn) {
            copyBtn.disabled = false;
        }
    }

    window.AdvancedHTMLSitemapGenerateShortcode = AdvancedHTMLSitemapGenerateShortcode;

})();

document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("ahs-generate");
    if (!btn) return;
    btn.addEventListener("click", () => {
        if (typeof window.AdvancedHTMLSitemapGenerateShortcode === "function") {
            window.AdvancedHTMLSitemapGenerateShortcode();
        }
    });
});

(function () {

    function copyToClipboard(text) {
        // Modern browsers
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        // Fallback for older browsers
        return new Promise((resolve, reject) => {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                document.execCommand("copy");
                resolve();
            } catch (err) {
                reject(err);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const output = document.getElementById("generated-shortcode");
        const copyBtn = document.getElementById("ahs-copy-shortcode");
        const status = document.getElementById("ahs-copy-status");

        if (!output || !copyBtn) return;

        // Enable copy button when shortcode is generated
        const enableCopyIfReady = () => {
            if (output.value.trim()) {
                copyBtn.disabled = false;
            }
        };

        // Watch for changes (when Generate button runs)
        const observer = new MutationObserver(enableCopyIfReady);
        observer.observe(output, { attributes: true, childList: true, subtree: true });

        // Also check on input/change
        output.addEventListener("input", enableCopyIfReady);

        copyBtn.addEventListener("click", () => {
            const text = output.value.trim();
            if (!text) return;

            copyToClipboard(text).then(() => {
                if (status) {
                    status.style.display = "inline";
                    setTimeout(() => {
                        status.style.display = "none";
                    }, 1500);
                }
            });
        });
    });

})();
