Feature: Test card payments
      Guest Checkout


Scenario: Basic card payment as a guest with 3D
      # Given I go to the backend of Checkout's plugin
      # Then I enable 3ds
      # Then I save settings
      # Then I clear cache
      # Then I clear cache
      # Then I logout of the account
      # Then I complete a payment flow until payment as a guest
      # Then I chose to pay using card
      # Then I enter my visa card details
      # Then I go to order review
      # Then I submin order
      # Then I complete 3ds details
      # Then I should see the success page

Scenario: Basic card payment as a guest with no 3D
      Given I go to the backend of Checkout's plugin
      Then I disable 3ds
      Then I save settings
      Then I clear cache
      Then I clear cache
      Given I go to the backend of Checkout's plugin
      Then I logout of the account
      Then I complete a payment flow until payment as a guest
      Then I chose to pay using card
      Then I select the new card option if it's available
      Then I enter my visa card details
      Then I go to order review
      Then I submin order
      Then I should see the success page


Scenario: Basic card payment as a registered user with no 3D
      Given I go to the backend of Checkout's plugin
      Then I disable 3ds
      Then I save settings
      Then I clear cache
      Then I logout of the account
      Then I login to the account
      Then I complete a payment flow until payment as a registered user
      Then I chose to pay using card
      Then I select the new card option if it's available
      Then I enter my visa card details
      Then I go to order review
      Then I submin order
      Then I should see the success page


Scenario: Basic card payment as a registered user with 3D
      # Given I go to the backend of Checkout's plugin
      # Then I enable 3ds
      # Then I save settings
      # Then I clear cache
      # Then I logout of the account
      # Then I login to the account
      # Then I complete a payment flow until payment as a registered user
      # Then I chose to pay using card
      # Then I select the new card option if it's available
      # Then I enter my visa card details
      # Then I go to order review
      # Then I submin order
      # Then I complete 3ds details
      # Then I should see the success page


Scenario: Basic card payment as a guest downgraded from 3D to non 3D
      Given I go to the backend of Checkout's plugin
      Then I enable 3ds
      Then I enable attemptN3D
      Then I save settings
      Then I clear cache
      Then I logout of the account
      Then I complete a payment flow for the 3D magic value as a guest
      Then I chose to pay using card
      Then I enter my visa card details
      Then I go to order review
      Then I submin order
      Then I should see the success page


Scenario: Basic card payment as a regirtered user downgraded from 3D to non 3D
      # Given I go to the backend of Checkout's plugin
      # Then I enable 3ds
      # Then I enable attemptN3D
      # Then I save settings
      #  Then I clear cache
      # Then I logout of the account
      # Then I login to the account
      # Then I complete a payment flow for the 3D magic value as a registered user
      # Then I chose to pay using card
      # Then I select the new card option if it's available
      # Then I enter my visa card details
      # Then I go to order review
      # Then I submin order
      # Then I should see the success page

 
Scenario: Set the card payment option title
      # Given I go to the backend of Checkout's plugin
      # Given I set the payment option title
      # Then I save settings
      # Then I clear cache
      # Then I logout of the account
      # Then I complete a payment flow until payment as a guest
      # Then I should see the card option title changed

@watch
Scenario: Pay and save card then pay again using your saved card
      Given I go to the backend of Checkout's plugin
      Given I disable 3ds
      Given I enable saved cards
      Given I set the saved cards option title
      Given I set the saved card helper label
      Then I save settings
      Then I clear cache
      Then I logout of the account
      Then I login to the account
      Then I complete a payment flow until payment as a registered user
      Then I chose to pay using card
      Then I select the new card option if it's available
      Then I enter my visa card details
      Then I chose to save the card
      Then I should see the saved save helper label change
      Then I go to order review
      Then I submin order
      Then I should see the success page
      Then I logout of the account
      Then I login to the account
      Then I complete a payment flow until payment as a registered user
      Then I chose to pay using card
      Then I chose a saved card
      Then I should see the save card title change
      Then I go to order review
      Then I submin order
      Then I should see the success page
      Then I remove saved card
      Then I logout of the account


Scenario: Pay by saved card with cvv
      # Given I go to the backend of Checkout's plugin
      # Given I disable 3ds
      # Given I enable saved cards
      # Given I set the saved cards option title
      # Given I set the saved card helper label
      # Then I save settings
      # Then I clear cache
      # Then I logout of the account
      # Then I login to the account
      # Then I complete a payment flow until payment as a registered user
      # Then I chose to pay using card
      # Then I select the new card option if it's available
      # Then I enter my visa card details
      # Then I chose to save the card
      # Then I should see the saved save helper label change
      # Then I go to order review
      # Then I submin order
      # Then I should see the success page
      # Given I go to the backend of Checkout's plugin
      # Given I disable 3ds
      # Given I enable saved cards
      # Given I enable the require of cvv for saved card payments
      # Then I save settings
      # Then I clear cache
      # Then I logout of the account
      # Then I login to the account
      # Then I complete a payment flow until payment as a registered user
      # Then I chose to pay using card
      # Then I chose a saved card
      # Then I complete the visa cvv
      # Then I go to order review
      # Then I submin order
      # Then I should see the success page
      # Given I go to the backend of Checkout's plugin
      # Given I disable the require of cvv for saved card payments
      # Then I save settings
      # Then I remove saved card
      # Then I logout of the account