import { defineConfig } from "cypress";

export default defineConfig({
  reporter: 'mochawesome',
  e2e: {
    baseUrl: "https://twint-shopware-version-65.dev.nfq-asia.com/",
    setupNodeEvents(on, config) {
      // implement node event listeners here
    },
  },
});
