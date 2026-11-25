import { defineConfig } from "vitest/config";

/**
 * JS unit tests. The e2e/ specs are Playwright tests - they must not be
 * picked up by the unit runner (yarn test:e2e runs them).
 */
export default defineConfig({
  test: {
    include: ["src/**/*.{test,spec}.{js,jsx,ts,tsx}"],
    exclude: ["**/node_modules/**", "**/build/**", "tests/e2e/**"],
  },
});
