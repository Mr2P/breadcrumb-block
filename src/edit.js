/**
 * External dependencies
 */
import clsx from "clsx";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useBlockProps, InspectorControls } from "@wordpress/block-editor";
import {
  ToggleControl,
  TextControl,
  __experimentalUnitControl as UnitControl,
  __experimentalToggleGroupControl as ToggleGroupControl,
  __experimentalToggleGroupControlOption as ToggleGroupControlOption,
  __experimentalToolsPanel as ToolsPanel,
  __experimentalToolsPanelItem as ToolsPanelItem,
} from "@wordpress/components";
import { useEffect } from "@wordpress/element";

/**
 * Style
 */
import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({
  attributes: {
    gap = ".4em",
    separator,
    hideHomePage,
    hideCurrentPage,
    homeText,
    labels = {},
  },
  setAttributes,
  isSelected,
}) {
  // Migrate homeText to labels.home
  useEffect(() => {
    if (homeText && !labels?.home) {
      setAttributes({ homeText: "", labels: { ...labels, home: homeText } });
    }
  }, []);

  const separatorOptions = [
    { label: "/", value: "/" },
    { label: "•", value: "•" },
    { label: "→", value: "→" },
    { label: "‣", value: "‣" },
    {
      label: (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="currentColor"
          width="1em"
          height="1em"
          viewBox="0 0 16 16"
        >
          <path d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z" />
        </svg>
      ),
      value: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="1em" height="1em" viewBox="0 0 16 16">
      <path d="m12.14 8.753-5.482 4.796c-.646.566-1.658.106-1.658-.753V3.204a1 1 0 0 1 1.659-.753l5.48 4.796a1 1 0 0 1 0 1.506z"/>
    </svg>`,
    },
    {
      label: (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="currentColor"
          width="1em"
          height="1em"
          viewBox="0 0 16 16"
        >
          <path
            fillRule="evenodd"
            d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"
          />
        </svg>
      ),
      value: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="1em" height="1em" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
    </svg>`,
    },
    {
      label: (
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="currentColor"
          width="1em"
          height="1em"
          viewBox="0 0 16 16"
        >
          <path
            fillRule="evenodd"
            d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"
          />
        </svg>
      ),
      value: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="1em" height="1em" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
    </svg>`,
    },
  ];

  const gapLabel = __("Gap", "breadcrumb-block");
  const separatorLabel = __("Separator", "breadcrumb-block");
  const hideHomePageLabel = __("Hide home page link", "breadcrumb-block");
  const currentPageLabel = __("Hide current page link", "breadcrumb-block");
  const homeLabel = __("Home", "breadcrumb-block");
  const error404Label = __("Page not found", "breadcrumb-block");
  const searchResultLabel = __("Search results", "breadcrumb-block");
  const postsTaggedLabel = __("Posts tagged", "breadcrumb-block");
  const authorLabel = __("Author", "breadcrumb-block");
  const pageLabel = __("Page", "breadcrumb-block");
  const productsTaggedLabel = __("Products tagged", "breadcrumb-block");
  return (
    <>
      {isSelected && (
        <>
          <InspectorControls>
            <ToolsPanel
              label={__("Block settings", "breadcrumb-block")}
              resetAll={() => {
                setAttributes({
                  gap: ".4em",
                  separator: "/",
                  hideHomePage: false,
                  hideCurrentPage: false,
                  homeText: "",
                });
              }}
            >
              <ToolsPanelItem
                label={gapLabel}
                hasValue={() => gap !== ".4em"}
                onDeselect={() =>
                  setAttributes({
                    gap: ".4em",
                  })
                }
                isShownByDefault={true}
              >
                <UnitControl
                  label={gapLabel}
                  value={gap}
                  onChange={(gap) => setAttributes({ gap })}
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={separatorLabel}
                hasValue={() => separator !== "/"}
                onDeselect={() =>
                  setAttributes({
                    separator: "/",
                  })
                }
                isShownByDefault={true}
              >
                <ToggleGroupControl
                  label={separatorLabel}
                  value={separator}
                  onChange={(separator) => setAttributes({ separator })}
                  isBlock
                  className="separator-toggle"
                  __next40pxDefaultSize
                >
                  {separatorOptions.map(({ label, value }) => (
                    <ToggleGroupControlOption
                      key={value}
                      value={value}
                      label={label}
                      __next40pxDefaultSize
                    />
                  ))}
                </ToggleGroupControl>
              </ToolsPanelItem>
              <ToolsPanelItem
                label={hideHomePageLabel}
                hasValue={() => !!hideHomePage}
                onDeselect={() =>
                  setAttributes({
                    hideHomePage: false,
                  })
                }
                isShownByDefault={true}
              >
                <ToggleControl
                  label={hideHomePageLabel}
                  checked={hideHomePage}
                  onChange={(hideHomePage) => setAttributes({ hideHomePage })}
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={currentPageLabel}
                hasValue={() => !!hideCurrentPage}
                onDeselect={() =>
                  setAttributes({
                    hideCurrentPage: false,
                  })
                }
                isShownByDefault={true}
              >
                <ToggleControl
                  label={currentPageLabel}
                  checked={hideCurrentPage}
                  onChange={(value) =>
                    setAttributes({ hideCurrentPage: value })
                  }
                />
              </ToolsPanelItem>
            </ToolsPanel>
            <ToolsPanel
              label={__("Labels", "breadcrumb-block")}
              resetAll={() => {
                setAttributes({
                  labels: {
                    home: "",
                    error404: "",
                    searchResult: "",
                  },
                });
              }}
            >
              <ToolsPanelItem
                label={homeLabel}
                hasValue={() => !!labels?.home}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, home: "" },
                  })
                }
              >
                <TextControl
                  label={homeLabel}
                  value={labels?.home || ""}
                  onChange={(home) =>
                    setAttributes({ labels: { ...labels, home } })
                  }
                  placeholder={homeLabel}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={error404Label}
                hasValue={() => !!labels?.error404}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, error404: "" },
                  })
                }
              >
                <TextControl
                  label={error404Label}
                  value={labels?.error404 || ""}
                  onChange={(error404) =>
                    setAttributes({ labels: { ...labels, error404 } })
                  }
                  placeholder={error404Label}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={searchResultLabel}
                hasValue={() => !!labels?.searchResult}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, searchResult: "" },
                  })
                }
              >
                <TextControl
                  label={searchResultLabel}
                  value={labels?.searchResult || ""}
                  onChange={(searchResult) =>
                    setAttributes({ labels: { ...labels, searchResult } })
                  }
                  placeholder={__(
                    'Search results for "%s"',
                    "breadcrumb-block",
                  )}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={postsTaggedLabel}
                hasValue={() => !!labels?.postsTagged}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, postsTagged: "" },
                  })
                }
              >
                <TextControl
                  label={postsTaggedLabel}
                  value={labels?.postsTagged || ""}
                  onChange={(postsTagged) =>
                    setAttributes({ labels: { ...labels, postsTagged } })
                  }
                  placeholder={__('Posts tagged "%s"', "breadcrumb-block")}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={authorLabel}
                hasValue={() => !!labels?.author}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, author: "" },
                  })
                }
              >
                <TextControl
                  label={authorLabel}
                  value={labels?.author || ""}
                  onChange={(author) =>
                    setAttributes({ labels: { ...labels, author } })
                  }
                  placeholder={__('Author: "%s"', "breadcrumb-block")}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={pageLabel}
                hasValue={() => !!labels?.page}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, page: "" },
                  })
                }
              >
                <TextControl
                  label={pageLabel}
                  value={labels?.page || ""}
                  onChange={(page) =>
                    setAttributes({ labels: { ...labels, page } })
                  }
                  placeholder={__("Page %d", "breadcrumb-block")}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
              <ToolsPanelItem
                label={productsTaggedLabel}
                hasValue={() => !!labels?.productsTagged}
                onDeselect={() =>
                  setAttributes({
                    labels: { ...labels, productsTagged: "" },
                  })
                }
              >
                <TextControl
                  label={productsTaggedLabel}
                  value={labels?.productsTagged || ""}
                  onChange={(productsTagged) =>
                    setAttributes({ labels: { ...labels, productsTagged } })
                  }
                  placeholder={__('Products tagged "%s"', "breadcrumb-block")}
                  autoComplete="off"
                  __next40pxDefaultSize
                />
              </ToolsPanelItem>
            </ToolsPanel>
          </InspectorControls>
        </>
      )}
      <div
        {...useBlockProps({
          style: {
            "--bb--crumb-gap": gap,
          },
          className: clsx({
            "hide-current-page": hideCurrentPage,
            "hide-home-page": hideHomePage,
          }),
        })}
      >
        <nav role="navigation" aria-label="breadcrumb" className="breadcrumb">
          <ol className="breadcrumb-items">
            <li className="breadcrumb-item breadcrumb-item--home">
              <a href="#">
                <span className="breadcrumb-item-name">
                  {labels?.home ? labels.home : __("Home", "breadcrumb-block")}
                </span>
              </a>
              <span
                className="sep"
                dangerouslySetInnerHTML={{ __html: separator }}
              />
            </li>
            <li className="breadcrumb-item breadcrumb-item--parent">
              <a href="#">
                <span className="breadcrumb-item-name">
                  {__("Parent page", "breadcrumb-block")}
                </span>
              </a>
              <span
                className="sep"
                dangerouslySetInnerHTML={{ __html: separator }}
              />
            </li>
            <li className="breadcrumb-item breadcrumb-item--current">
              <span className="breadcrumb-item-name">
                {__("Current page", "breadcrumb-block")}
              </span>
            </li>
          </ol>
        </nav>
      </div>
    </>
  );
}
