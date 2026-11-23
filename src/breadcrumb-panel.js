/**
 * WordPress dependencies
 */
import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
/* eslint-disable @wordpress/no-unsafe-wp-apis -- ToolsPanel/ToolsPanelItem/VStack are still exported as __experimental in WordPress core; drop this when they graduate. */
import {
  SelectControl,
  TextControl,
  __experimentalVStack as VStack,
} from "@wordpress/components";
/* eslint-enable @wordpress/no-unsafe-wp-apis */
import { useSelect } from "@wordpress/data";
import { useEntityProp } from "@wordpress/core-data";
import { __ } from "@wordpress/i18n";

/**
 * @typedef {import("@wordpress/element").WPElement} WPElement
 */

/**
 * The breadcrumb panel for the post editor sidebar: short title and primary term.
 *
 * The primary term dropdown follows the taxonomy that the render will use:
 * the per-post-type mapping from the settings, falling back to the type
 * default. It lists the terms assigned to the post.
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
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  // The per-post-type taxonomy mapping from the settings, matching the server.
  const mapping = useSelect(
    (select) =>
      select("core").getEntityRecord("root", "site")
        ?.breadcrumb_block_taxonomies ?? {},
    []
  );

  // The mapped taxonomy for this post type, or the type default.
  let defaultTaxonomy = "";

  if (postType === "product") {
    defaultTaxonomy = "product_cat";
  } else if (postType === "post") {
    defaultTaxonomy = "category";
  }

  const selectedTaxonomy = mapping[postType] || defaultTaxonomy;

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
      <VStack spacing={3}>
        <TextControl
          label={__("Breadcrumb short title", "breadcrumb-block")}
          help={__(
            "Optional. Shown in the breadcrumb trail instead of the full title.",
            "breadcrumb-block"
          )}
          value={meta.breadcrumb_short_title ?? ""}
          onChange={(nextValue) =>
            setMeta({ breadcrumb_short_title: nextValue })
          }
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
      </VStack>
    </PluginDocumentSettingPanel>
  );
};

registerPlugin("breadcrumb-block-panel", { render: BreadcrumbPanel });
