Feature: Prepare Magento for Tests
      Set Checkout Keys, Create Account

Scenario: I setup Magento for tests
      Given I set the viewport and timeout
      Given I go to the backend of Checkout's plugin
      Given I set the sandbox keys
      Then I save settings
      # Then I create an account
      Then I logout of the account
    