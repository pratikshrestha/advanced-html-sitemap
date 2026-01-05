(function () {
  function AdvancedHTMLSitemapGenerateShortcode() {
    const form = document.getElementById("sitemap-shortcode-generator");
    if (!form) return;

    const formData = new FormData(form);
    const attrs = [];

    for (const [key, valueRaw] of formData.entries()) {
      if (!key) continue;

      const el = form.elements[key];
      if (!el) continue;

      let value = valueRaw;

      if (el.type === "checkbox") {
        value = el.checked ? "true" : "false";
      } else {
        if (!value) continue;
      }

      attrs.push(`${key}="${value}"`);
    }

    const shortcode = `[html_sitemap ${attrs.join(" ")}]`;
    const out = document.getElementById("generated-shortcode");
    if (out) out.value = shortcode;
  }

  window.AdvancedHTMLSitemapGenerateShortcode = AdvancedHTMLSitemapGenerateShortcode;
})();
