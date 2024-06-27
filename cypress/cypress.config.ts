import { defineConfig } from "cypress";

export default defineConfig({
  viewportWidth: 1280,
  viewportHeight: 720,
  video: true,
  screenshotOnRunFailure: true,
  fixturesFolder: 'fixtures',
  e2e: {
    specPattern: 'e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'support/e2e.{js,jsx,ts,tsx}',
    setupNodeEvents(on, config) {
      // This function will be called when setting up Node events
      config.viewportWidth = Number(config.env.VIEW_WIDTH) || config.viewportWidth;
      config.viewportHeight = Number(config.env.VIEW_HEIGHT) || config.viewportHeight;
      return config;
    },
  },
});
