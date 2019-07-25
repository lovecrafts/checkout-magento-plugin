import { expect } from 'chai';
import Globals from '../../config/globals';

export default function () {
  this.Given(/^I enable card payments$/, () => {
    const { selector, enable } = Globals.selector.backend.card;
    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(enable)) {
      browser.selectByValue(enable, 1);
    } else {
      browser.click(selector);
      browser.selectByValue(enable, 1);
    }
  });

  this.Given(/^I complete a payment flow until payment as a (.*)$/, (type) => {
    const { url, guest, timeout_out } = Globals.value;
    const {
      product_add,
      go_checkout,
      continue_guest,
      firstname,
      lastname,
      email,
      address,
      city,
      state,
      postcode,
      phone,
      go_shipping,
      go_pay,
    } = Globals.selector.frontend.order;

    browser.url(url.magento_base + url.product_path);
    browser.click(product_add);
    browser.waitUntil(() => browser.isVisible(go_checkout), timeout_out, 'expected go_checkout button to be visible');
    browser.click(go_checkout);

    if (type === 'guest') {
      browser.waitUntil(() => browser.isVisible(continue_guest), timeout_out, 'expected continue_guest button to be visible');
      browser.click(continue_guest);
      browser.setValue(firstname, guest.name);
      browser.setValue(lastname, guest.lastname);
      browser.setValue(email, guest.email);
      browser.setValue(address, guest.address);
      browser.setValue(address, guest.address);
      browser.setValue(city, guest.city);
      browser.selectByValue(state, 1);
      browser.setValue(postcode, guest.postcode);
      browser.setValue(phone, guest.phone);
      browser.waitUntil(() => browser.isVisible(go_shipping), timeout_out, 'expected go shipping button to be visible');
      browser.click(go_shipping);
      browser.waitUntil(() => browser.isVisible(go_pay), timeout_out, 'expected go pay button to be visible');
      browser.click(go_pay);
    } else {
      browser.waitUntil(() => browser.isVisible(go_shipping), timeout_out, 'expected go shipping button to be visible');
      browser.click(go_shipping);
      browser.waitUntil(() => browser.isVisible(go_pay), timeout_out, 'expected go pay button to be visible');
      browser.click(go_pay);
    }
  });

  this.Given(/^I complete a payment flow for the 3D magic value as a (.*)$/, (type) => {
    const { url, guest, timeout_out } = Globals.value;
    const {
      product_add,
      go_checkout,
      continue_guest,
      firstname,
      lastname,
      email,
      address,
      city,
      state,
      postcode,
      phone,
      go_shipping,
      go_pay,
    } = Globals.selector.frontend.order;

    browser.url(url.magento_base + url.three_d_downgrade_product);
    browser.click(product_add);
    browser.waitUntil(() => browser.isVisible(go_checkout), timeout_out, 'expected go_checkout button to be visible');
    browser.click(go_checkout);

    if (type === 'guest') {
      browser.waitUntil(() => browser.isVisible(continue_guest), timeout_out, 'expected continue_guest button to be visible');
      browser.click(continue_guest);
      browser.setValue(firstname, guest.name);
      browser.setValue(lastname, guest.lastname);
      browser.setValue(email, guest.email);
      browser.setValue(address, guest.address);
      browser.setValue(address, guest.address);
      browser.setValue(city, guest.city);
      browser.selectByValue(state, 1);
      browser.setValue(postcode, guest.postcode);
      browser.setValue(phone, guest.phone);
      browser.waitUntil(() => browser.isVisible(go_shipping), timeout_out, 'expected go shipping button to be visible');
      browser.click(go_shipping);
      browser.waitUntil(() => browser.isVisible(go_pay), timeout_out, 'expected go pay button to be visible');
      browser.click(go_pay);
    } else {
      browser.waitUntil(() => browser.isVisible(go_shipping), timeout_out, 'expected go shipping button to be visible');
      browser.click(go_shipping);
      browser.waitUntil(() => browser.isVisible(go_pay), timeout_out, 'expected go pay button to be visible');
      browser.click(go_pay);
    }
  });

  this.Then(/^I chose to pay using (.*)$/, (type) => {
    const { card_option, apms_option, googleplay_option } = Globals.selector.frontend.order;
    const { timeout_out } = Globals.value;
    if (type === 'card') {
      browser.waitUntil(() => browser.isVisible(card_option), timeout_out, 'expected to see card payment as a payment option');
      browser.click(card_option);
    } else if (type === 'apms') {
      browser.waitUntil(() => browser.isVisible(apms_option), timeout_out, 'expected to see APMS as a payment option');
      browser.click(apms_option);
    } else if (type === 'google play') {
      browser.waitUntil(() => browser.isVisible(googleplay_option), timeout_out, 'expected to see Google Pay as a payment option');
      browser.click(googleplay_option);
    }
  });

  this.Then(/^I enter my (.*) card details$/, (scheme) => {
    const { order } = Globals.selector.frontend;
    const { timeout_out } = Globals.value;
    const { visa } = Globals.value.card;
    let card;
    let month;
    let year;
    let cvv;
    browser.waitUntil(() => browser.isVisible(order.checkout_iframe_selector), timeout_out, 'the embedded form should be visible');
    const iframe = browser.element(order.checkout_iframe_selector);
    browser.frame(iframe.value);
    browser.pause(1000); // avoid context switch delay
    switch (scheme) {
      case 'visa':
        card = browser.element(order.embedded_fields.card_number);
        card.setValue(visa.card_number);
        browser.pause(500);
        month = browser.element(order.embedded_fields.month);
        month.setValue(visa.month);
        browser.pause(500);
        year = browser.element(order.embedded_fields.year);
        year.setValue(visa.year);
        browser.pause(500);
        cvv = browser.element(order.embedded_fields.cvv);
        cvv.setValue(visa.cvv);
        browser.pause(500);
        break;
      default:
        card = browser.element(order.embedded_fields.card_number);
        card.setValue(visa.card_number);
        browser.pause(500);
        month = browser.element(order.embedded_fields.month);
        month.setValue(visa.month);
        browser.pause(500);
        year = browser.element(order.embedded_fields.year);
        year.setValue(visa.year);
        browser.pause(500);
        cvv = browser.element(order.embedded_fields.cvv);
        cvv.setValue(visa.cvv);
        browser.pause(500);
        break;
    }
    browser.frameParent();
    browser.pause(1000); // avoid context switch delay
  });

  this.Then(/^I go to order review$/, () => {
    const { go_review } = Globals.selector.frontend.order;
    const { timeout_out } = Globals.value;
    browser.waitUntil(() => browser.isVisible(go_review), timeout_out, 'expected to see the go to review button');
    browser.click(go_review);
  });

  this.Then(/^I submin order$/, () => {
    const { go_card_place_order, success_page } = Globals.selector.frontend.order;
    const { timeout_out } = Globals.value;
    browser.waitUntil(() => browser.isVisible(go_card_place_order), timeout_out, 'expected to see the place order button');
    browser.click(go_card_place_order);
  });

  this.Then(/^I should see the success page$/, () => {
    const { timeout_out, url } = Globals.value;
    browser.waitUntil(() => browser.getUrl() === `${url.magento_base}/index.php/checkout/onepage/success/`, timeout_out, 'expected to see the success page');
  });

  this.Then(/^I (.*) 3ds$/, (option) => {
    const { card, all_in_one, configuration } = Globals.selector.backend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(all_in_one), timeout_out, 'expected plugin settings should be visible');

    // if the section is not expanded, expand it and enable it
    if (!browser.isVisible(configuration.selector)) {
      browser.click(all_in_one);
    }

    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }
    if (option === 'enable') {
      browser.selectByValue(card.threed, 1);
    } else {
      browser.selectByValue(card.threed, 0);
    }
  });

  this.Then(/^I (.*) attemptN3D$/, (option) => {
    const { card, configuration, all_in_one } = Globals.selector.backend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(all_in_one), timeout_out, 'expected plugin settings should be visible');

    // if the section is not expanded, expand it and enable it
    if (!browser.isVisible(configuration.selector)) {
      browser.click(all_in_one);
    }

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    if (option === 'enable') {
      browser.selectByValue(card.attemptN3D, 1);
    } else {
      browser.selectByValue(card.attemptN3D, 0);
    }
  });

  this.Then(/^I complete 3ds details$/, () => {
    const { order } = Globals.selector.frontend;
    const { timeout_out, admin } = Globals.value;

    browser.pause(3000);
    browser.setValue(order.three_d_password, admin.three_d_password);
    browser.click(order.three_d_submit);
  });

  this.Then(/^I set the payment option title$/, () => {
    const { card } = Globals.selector.backend;
    const { timeout_out, card_payment_option_title } = Globals.value;

    browser.waitUntil(() => browser.isVisible(card.selector), timeout_out, 'expected plugin card section to be visible');

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    browser.setValue(card.option_title, card_payment_option_title);
  });

  this.Then(/^I should see the card option title changed$/, () => {
    const { order } = Globals.selector.frontend;
    const { timeout_out, card_payment_option_title } = Globals.value;
    browser.waitUntil(() => browser.isVisible(order.card_option), timeout_out, 'expected plugin card section to be visible');
    expect(browser.element(order.card_option_title).getText()).to.equal(card_payment_option_title);
  });

  this.Then(/^I (.*) saved cards$/, (option) => {
    const { card } = Globals.selector.backend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(card.selector), timeout_out, 'expected plugin card section to be visible');

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    if (option === 'enable') {
      browser.selectByValue(card.saved_cards, 1);
    } else {
      browser.selectByValue(card.saved_cards, 0);
    }
  });

  this.Then(/^I set the saved cards option title$/, () => {
    const { card } = Globals.selector.backend;
    const { timeout_out, saved_cards_option_title } = Globals.value;

    browser.waitUntil(() => browser.isVisible(card.selector), timeout_out, 'expected plugin card section to be visible');

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    browser.setValue(card.saved_cards_title, saved_cards_option_title);
  });

  this.Then(/^I set the saved card helper label$/, () => {
    const { card } = Globals.selector.backend;
    const { timeout_out, saved_card_helper_label } = Globals.value;

    browser.waitUntil(() => browser.isVisible(card.selector), timeout_out, 'expected plugin card section to be visible');

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    browser.setValue(card.saved_card_label, saved_card_helper_label);
  });

  this.Then(/^I chose to save the card$/, () => {
    const { order } = Globals.selector.frontend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(order.save_this_card), timeout_out, 'expected the save card check to be visible');

    browser.click(order.save_this_card);
  });


  this.Then(/^I chose a saved card$/, () => {
    const { order } = Globals.selector.frontend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(order.saved_card_one), timeout_out, 'expected the save card check to be visible');

    browser.click(order.saved_card_one);
  });

  this.Then(/^I select the new card option if it's available$/, () => {
    const { order } = Globals.selector.frontend;

    if (browser.isVisible(order.new_card_option)) {
      browser.click(order.new_card_option);
    }
  });

  this.Then(/^I should see the saved save helper label change$/, () => {
    const { save_card_helper_label } = Globals.selector.frontend.order;
    const { timeout_out, saved_card_helper_label } = Globals.value;

    browser.waitUntil(() => browser.isVisible(save_card_helper_label), timeout_out, 'expected saved card label to be visible');
    expect(browser.element(save_card_helper_label).getText()).to.equal(saved_card_helper_label);
  });

  this.Then(/^I should see the save card title change$/, () => {
    const { save_cards_option_label } = Globals.selector.frontend.order;
    const { timeout_out, saved_cards_option_title } = Globals.value;

    browser.waitUntil(() => browser.isVisible(save_cards_option_label), timeout_out, 'expected saved cards title to be visible');
    expect(browser.element(save_cards_option_label).getText()).to.equal(saved_cards_option_title);
  });

  this.Then(/^I (.*) the require of cvv for saved card payments$/, (option) => {
    const { card, configuration, all_in_one } = Globals.selector.backend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(all_in_one), timeout_out, 'expected plugin settings should be visible');

    // if the section is not expanded, expand it and enable it
    if (!browser.isVisible(configuration.selector)) {
      browser.click(all_in_one);
    }


    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(card.enable)) {
      browser.selectByValue(card.enable, 1);
    } else {
      browser.click(card.selector);
      browser.selectByValue(card.enable, 1);
    }

    if (option === 'enable') {
      browser.selectByValue(card.require_cvv, 1);
    } else {
      browser.selectByValue(card.require_cvv, 0);
    }
  });

  this.Then(/^I complete the (.*) cvv$/, (option) => {
    const { cvv_input } = Globals.selector.frontend.order;
    const { timeout_out, card } = Globals.value;

    browser.waitUntil(() => browser.isVisible(cvv_input), timeout_out, 'expected cvv input to be visible');

    browser.pause(500); // animation delay
    if (option === 'visa') {
      browser.setValue(cvv_input, card.visa.cvv);
    }
  });

  this.Then(/^I remove saved card$/, () => {
    const { remove_saved_card } = Globals.selector.frontend.create_account;
    const { timeout_out, url } = Globals.value;
    browser.url(url.magento_base + url.saved_cards);
    browser.waitUntil(() => browser.isVisible(remove_saved_card), timeout_out, 'expected the card to be visible for the customer');
    browser.click(remove_saved_card);
  });

  this.Given(/^I enable apm payments$/, () => {
    const { selector, enable } = Globals.selector.backend.apms;
    const { configuration, all_in_one } = Globals.selector.backend;
    const { timeout_out } = Globals.value;

    browser.waitUntil(() => browser.isVisible(all_in_one), timeout_out, 'expected plugin settings should be visible');

    // if the section is not expanded, expand it and enable it
    if (!browser.isVisible(configuration.selector)) {
      browser.click(all_in_one);
    }

    // if the section is not expanded, expand it and enable it
    if (browser.isVisible(enable)) {
      browser.selectByValue(enable, 1);
    } else {
      browser.click(selector);
      browser.selectByValue(enable, 1);
    }
  });


  this.Then(/^I enable iDeal$/, () => {
    const { apms_selector } = Globals.selector.backend.apms;
    let element = browser.$('[value="ideal"]');
    element.doubleClick()
  });

  this.Then(/^I chose (.*) out of the apms$/, (option) => {
    const { ideal  } = Globals.selector.frontend.order;

    if(option === 'iDeal') {
      browser.click(ideal)
      browser.pause(2000);
      browser.selectByIndex('#issuer-id', 0);
    }
  });

  this.Then(/^I confirm iDeal transaction$/, () => {
    const { timeout_out, url } = Globals.value;

    const { ideal_confirm_transaction } = Globals.selector.frontend.order;
    browser.waitUntil(() => browser.isVisible(ideal_confirm_transaction), timeout_out, 'expected to land on the iDeal confirmation page');
    browser.click(ideal_confirm_transaction)
  });

}
