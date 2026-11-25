const wordpress = require("@wordpress/eslint-plugin");

module.exports = [
  {
    ignores: [
      "eslint.config.js",
      "vitest.config.ts",
      "tests/e2e/**",
      "tests/playwright.config.ts",
      "**/build/**",
      "**/node_modules/**",
      "**/vendor/**",
    ],
  },
  { files: ["**/*.jsx", "**/*.ts", "**/*.tsx", "**/*.mts", "**/*.cts"] },

  ...wordpress.configs.recommended,

  {
    rules: {
      "@wordpress/no-unsafe-wp-apis": "off",
    },
  },
];
