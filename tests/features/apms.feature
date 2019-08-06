Feature: Test card payments
      Guest Checkout


Scenario: iDeal payment flow as a guest
      Given I go to the backend of Checkout's plugin
      Then I enable apm payments
      Then I enable iDeal
      Then I save settings
      Then I clear cache
      Then I logout of the account
      Then I complete a payment flow until payment as a guest
      Then I chose to pay using apms
      Then I chose iDeal out of the apms
      Then I go to order review
      Then I submin order
      Then I confirm iDeal transaction
      Then I should see the success page