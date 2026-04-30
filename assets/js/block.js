(function (blocks, element, i18n, blockEditor, components, serverSideRender) {
    const el = element.createElement;
    const __ = i18n.__;
    const InspectorControls = blockEditor.InspectorControls;
    const PanelBody = components.PanelBody;
    const ToggleControl = components.ToggleControl;
    const RangeControl = components.RangeControl;
    const TextControl = components.TextControl;
    const CheckboxControl = components.CheckboxControl;
    const Spinner = components.Spinner;
    const Disabled = components.Disabled;

    blocks.registerBlockType("advanced-html-sitemap/block", {
        title: __("Advanced HTML Sitemap", "advanced-html-sitemap"),
        description: __("Insert an HTML sitemap using Advanced HTML Sitemap.", "advanced-html-sitemap"),
        icon: {
            src: wp.element.createElement(
                "svg",
                { width: 24, height: 24, viewBox: "0 0 24 24", xmlns: "http://www.w3.org/2000/svg" },
                wp.element.createElement("path", {
                    d: "M4 4h6v2H4V4zm0 5h6v2H4V9zm0 5h6v2H4v-2zm10-8h6v2h-6V6zm0 5h6v2h-6v-2zm-2-7h2v14h-2V4z",
                    fill: "currentColor"
                })
            )
        },
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

            const availablePostTypes = (window.AHS_BLOCK_POST_TYPES && Array.isArray(window.AHS_BLOCK_POST_TYPES))
                ? window.AHS_BLOCK_POST_TYPES
                : ["page", "post"];

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
                            label: __("Enable caching (front-end)", "advanced-html-sitemap"),
                            checked: !!attrs.cache,
                            onChange: (v) => setAttributes({ cache: v }),
                        })
                    )
                ),

                // ✅ Live preview (server-side render)
                el(
                    "div",
                    { className: "ahs-block-preview" },
                    el(Disabled, {},
                        el(serverSideRender, {
                            block: "advanced-html-sitemap/block",
                            attributes: attrs,
                            LoadingResponsePlaceholder: function () {
                                return el("div", { style: { padding: "12px" } }, el(Spinner, {}));
                            }
                        })
                    )
                ),
            ];
        },

        save: function () {
            return null; // dynamic render
        },
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.i18n,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.serverSideRender
);
