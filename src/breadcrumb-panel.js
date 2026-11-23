/**
 * WordPress dependencies
 */
import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { SelectControl, TextControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useEntityProp } from "@wordpress/core-data";
import { __ } from "@wordpress/i18n";

/**
 * @typedef {import("@wordpress/element").WPElement} WPElement
 */

/**
 * Find the first breadcrumb block, including nested blocks.
 *
 * @param {Array} blocks Block list.
 * @return {Object|null} The breadcrumb block, or null.
 */
const findBreadcrumbBlock = (blocks) => {
  for (const block of blocks) {
    if (block.name === "boldblocks/breadcrumb-block") {
      return block;
    }

    const found = findBreadcrumbBlock(block.innerBlocks ?? []);
    if (found) {
      return found;
    }
  }

  return null;
};

/**
 * The breadcrumb panel for the post editor sidebar: short title and primary term.
 *
 * The primary term dropdown follows the taxonomy that the render will use:
 * the breadcrumb block's setting in the post content, else in the post's
 * template, else the type default - and lists the terms assigned to the post.
 *
 * @return {WPElement} The panel element.
 */
const BreadcrumbPanel = () => {
  const postId = useSelect(
    (select) => select("core/editor").getCurrentPostId(),
    []
  );
  const postType = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("type"),
    []
  );
  const templateSlug = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("template"),
    []
  );
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  // Tier 1: the breadcrumb block's taxonomy setting in the post content.
  const contentTaxonomy = useSelect(
    (select) =>
      findBreadcrumbBlock(select("core/block-editor").getBlocks() ?? [])
        ?.attributes?.taxonomy ?? "",
    []
  );

  // Tier 2: the breadcrumb block's taxonomy setting in the post's template.
  const templateTaxonomy = useSelect(
    (select) => {
      let candidates = [`single-${postType}`, "single", "singular"];

      if (postType === "page") {
        candidates = ["page", "singular", "single"];
      }

      if (templateSlug) {
        candidates = [templateSlug];
      }

      const templates =
        select("core").getEntityRecords("postType", "wp_template", {
          per_page: -1,
        }) ?? [];

      for (const slug of candidates) {
        const template = templates.find((item) => item.slug === slug);

        if (template) {
          const record = select("core").getEditedEntityRecord(
            "postType",
            "wp_template",
            template.id
          );
          const found = findBreadcrumbBlock(record?.blocks ?? []);

          if (found) {
            return found.attributes?.taxonomy ?? "";
          }
        }
      }

      return "";
    },
    [templateSlug, postType]
  );

  // Tier 3: the type default (matches the server-side single_taxonomy_for()).
  let defaultTaxonomy = "";

  if (postType === "product") {
    defaultTaxonomy = "product_cat";
  } else if (postType === "post") {
    defaultTaxonomy = "category";
  }

  const selectedTaxonomy =
    contentTaxonomy || templateTaxonomy || defaultTaxonomy;

  // The terms of the selected taxonomy assigned to the post.
  const terms = useSelect(
    (select) =>
      selectedTaxonomy
        ? (select("core").getEntityRecords("taxonomy", selectedTaxonomy, {
            post: postId,
            per_page: -1,
            hide_empty: false,
          }) ?? [])
        : [],
    [selectedTaxonomy, postId]
  );

  // The meta keys are registered only for public post types.
  if (meta === undefined) {
    return null;
  }

  const termOptions = [
    { label: __("Default", "breadcrumb-block"), value: "0" },
    ...terms.map((term) => ({ label: term.name, value: String(term.id) })),
  ];

  return (
    <PluginDocumentSettingPanel
      name="breadcrumb-block-panel"
      title={__("Breadcrumb", "breadcrumb-block")}
    >
      <TextControl
        label={__("Breadcrumb short title", "breadcrumb-block")}
        help={__(
          "Optional. Shown in the breadcrumb trail instead of the full title.",
          "breadcrumb-block"
        )}
        value={meta.breadcrumb_short_title ?? ""}
        onChange={(nextValue) => setMeta({ breadcrumb_short_title: nextValue })}
        __next40pxDefaultSize
      />
      {selectedTaxonomy && (
        <SelectControl
          label={__("Primary term", "breadcrumb-block")}
          help={__(
            "Optional. The term whose path leads to this post in the breadcrumb trail.",
            "breadcrumb-block"
          )}
          value={String(meta.breadcrumb_primary_term ?? 0)}
          options={termOptions}
          onChange={(nextValue) =>
            setMeta({ breadcrumb_primary_term: Number(nextValue) })
          }
          __next40pxDefaultSize
        />
      )}
    </PluginDocumentSettingPanel>
  );
};

registerPlugin("breadcrumb-block-panel", { render: BreadcrumbPanel });
