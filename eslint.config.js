const wordpress = require("@wordpress/eslint-plugin");

module.exports = [
  {
    ignores: [
      "eslint.config.js",
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
