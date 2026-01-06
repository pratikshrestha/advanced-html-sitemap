(function (blocks, element, i18n, blockEditor, components) {
  const el = element.createElement;
  const __ = i18n.__;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const ToggleControl = components.ToggleControl;
  const RangeControl = components.RangeControl;
  const TextControl = components.TextControl;
  const CheckboxControl = components.CheckboxControl;

  blocks.registerBlockType("advanced-html-sitemap/block", {
    title: __("Advanced HTML Sitemap", "advanced-html-sitemap"),
    description: __("Insert an HTML sitemap using Advanced HTML Sitemap.", "advanced-html-sitemap"),
    icon: "list-view",
    category: "widgets",
    attributes: {
      postTypes: { type: "array", default: ["page", "post"] },
      columns: { type: "number", default: 1 },
      exclude: { type: "string", default: "" },
      showDates: { type: "boolean", default: false },
      hierarchical: { type: "boolean", default: false },
      index: { type: "boolean", default: false },
      excludeNoindex: { type: "boolean", default: true },
      cache: { type: "boolean", default: true },
    },

    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;

      // Minimal options. (We can localize full CPT list from PHP later.)
      const availablePostTypes = ["page", "post"];

      function togglePostType(slug, checked) {
        const current = Array.isArray(attrs.postTypes) ? attrs.postTypes.slice() : [];
        const next = checked
          ? Array.from(new Set(current.concat([slug])))
          : current.filter((x) => x !== slug);

        setAttributes({ postTypes: next.length ? next : ["page", "post"] });
      }

      return [
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: __("Sitemap Settings", "advanced-html-sitemap"), initialOpen: true },
            el(RangeControl, {
              label: __("Columns", "advanced-html-sitemap"),
              min: 1,
              max: 3,
              value: attrs.columns,
              onChange: (v) => setAttributes({ columns: v }),
            }),
            el(TextControl, {
              label: __("Exclude IDs (comma separated)", "advanced-html-sitemap"),
              value: attrs.exclude,
              onChange: (v) => setAttributes({ exclude: v }),
            }),
            el("p", { style: { marginTop: "12px", fontWeight: 600 } }, __("Post Types", "advanced-html-sitemap")),
            availablePostTypes.map((pt) =>
              el(CheckboxControl, {
                label: pt,
                checked: (attrs.postTypes || []).includes(pt),
                onChange: (checked) => togglePostType(pt, checked),
              })
            ),
            el(ToggleControl, {
              label: __("Show index links", "advanced-html-sitemap"),
              checked: !!attrs.index,
              onChange: (v) => setAttributes({ index: v }),
            }),
            el(ToggleControl, {
              label: __("Hierarchical display", "advanced-html-sitemap"),
              checked: !!attrs.hierarchical,
              onChange: (v) => setAttributes({ hierarchical: v }),
            }),
            el(ToggleControl, {
              label: __("Show post dates", "advanced-html-sitemap"),
              checked: !!attrs.showDates,
              onChange: (v) => setAttributes({ showDates: v }),
            }),
            el(ToggleControl, {
              label: __("Exclude noindex content", "advanced-html-sitemap"),
              checked: !!attrs.excludeNoindex,
              onChange: (v) => setAttributes({ excludeNoindex: v }),
            }),
            el(ToggleControl, {
              label: __("Enable caching", "advanced-html-sitemap"),
              checked: !!attrs.cache,
              onChange: (v) => setAttributes({ cache: v }),
            })
          )
        ),

        el(
          "div",
          { className: "components-placeholder" },
          el("strong", {}, __("Advanced HTML Sitemap", "advanced-html-sitemap")),
          el("p", {}, __("The sitemap renders on the front-end using your selected options.", "advanced-html-sitemap"))
        ),
      ];
    },

    save: function () {
      return null; // dynamic render
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components);
