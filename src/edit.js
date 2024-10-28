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
  PanelBody,
  Button,
  ButtonGroup,
  BaseControl,
  ToggleControl,
  TextControl,
  __experimentalUnitControl as UnitControl,
} from "@wordpress/components";

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
  attributes: { gap, separator, hideHomePage, hideCurrentPage, homeText },
  setAttributes,
  isSelected,
}) {
  const separatorOptions = [
    { label: "/", value: "/" },
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
  return (
    <>
      {isSelected && (
        <>
          <InspectorControls>
            <PanelBody title={__("Block settings", "breadcrumb-block")}>
              <div className="breadrumb-setings">
                <UnitControl
                  label={__("Gap", "breadcrumb-block")}
                  value={gap}
                  onChange={(gap) => setAttributes({ gap })}
                />
                <div className="toggle-group-control">
                  <BaseControl
                    className="toggle-group-control__label"
                    label={__("Separator", "breadcrumb-block")}
                  />
                  <ButtonGroup
                    aria-label={__("Separator icon", "breadcrumb-block")}
                  >
                    {separatorOptions.map(
                      ({ label, value: optionValue, disabled = false }) => {
                        return (
                          <Button
                            key={optionValue}
                            isSmall
                            variant={
                              optionValue === separator ? "primary" : undefined
                            }
                            onClick={() =>
                              setAttributes({ separator: optionValue })
                            }
                            style={{ verticalAlign: "top" }}
                            disabled={disabled}
                          >
                            {label}
                          </Button>
                        );
                      },
                    )}
                  </ButtonGroup>
                </div>
                <ToggleControl
                  label={__("Hide the home page", "breadcrumb-block")}
                  checked={hideHomePage}
                  onChange={(value) => setAttributes({ hideHomePage: value })}
                />
                {!hideHomePage && (
                  <TextControl
                    label={__("Custom home text", "breadcrumb-block")}
                    value={homeText}
                    onChange={(value) => setAttributes({ homeText: value })}
                    help={__(
                      "Input a custom home text. Leave it blank to use the default text.",
                      "breadcrumb-block",
                    )}
                  />
                )}
                <ToggleControl
                  label={__("Hide current page", "breadcrumb-block")}
                  checked={hideCurrentPage}
                  onChange={(value) =>
                    setAttributes({ hideCurrentPage: value })
                  }
                />
              </div>
            </PanelBody>
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
                  {homeText ? homeText : __("Home", "breadcrumb-block")}
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
                  {__("Dummy parent", "breadcrumb-block")}
                </span>
              </a>
              <span
                className="sep"
                dangerouslySetInnerHTML={{ __html: separator }}
              />
            </li>
            <li className="breadcrumb-item breadcrumb-item--current">
              <span className="breadcrumb-item-name">
                {__("Dummy title", "breadcrumb-block")}
              </span>
            </li>
          </ol>
        </nav>
      </div>
    </>
  );
}
