/**
 * WordPress dependencies
 */
import { registerPlugin } from "@wordpress/plugins";
import { PluginDocumentSettingPanel } from "@wordpress/editor";
import { TextControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { useEntityProp } from "@wordpress/core-data";
import { __ } from "@wordpress/i18n";

/**
 * @typedef {import("@wordpress/element").WPElement} WPElement
 */

/**
 * The breadcrumb short title panel for the post editor sidebar.
 *
 * Renders only where the `breadcrumb_short_title` meta is registered
 * (public post types).
 *
 * @return {WPElement} The panel element.
 */
const ShortTitlePanel = () => {
  const postType = useSelect(
    (select) => select("core/editor").getEditedPostAttribute("type"),
    []
  );
  const [meta, setMeta] = useEntityProp("postType", postType, "meta");

  // The meta key is registered only for public post types.
  if (meta === undefined || meta.breadcrumb_short_title === undefined) {
    return null;
  }

  return (
    <PluginDocumentSettingPanel
      name="breadcrumb-short-title"
      title={__("Breadcrumb short title", "breadcrumb-block")}
    >
      <TextControl
        label={__("Breadcrumb short title", "breadcrumb-block")}
        help={__(
          "Optional. Shown in the breadcrumb trail instead of the full title.",
          "breadcrumb-block"
        )}
        value={meta.breadcrumb_short_title}
        onChange={(nextValue) => setMeta({ breadcrumb_short_title: nextValue })}
        __next40pxDefaultSize
      />
    </PluginDocumentSettingPanel>
  );
};

registerPlugin("breadcrumb-block-short-title", { render: ShortTitlePanel });
