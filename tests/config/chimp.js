module.exports = {
  path: './features',
  screenshotsOnError: true,
  saveScreenshotsToDisk: true,
  saveScreenshotsToReport: true,
  webdriverio: {
    logLevel: 'silent',
    screenshotPath: 'screenshots',
    deprecationWarnings: false,
    desiredCapabilities: {
      chromeOptions: {
        args: ['headless', 'disable-gpu', '--no-sandbox']
      },
      isHeadless: true
    },
    debug: true
  }
};
