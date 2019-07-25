import Globals from '../../config/globals';

export default function () {
  this.Given(/^I set the viewport and timeout$/, () => {
    const { resolution_w, resolution_h } = Globals.value;
    this.setDefaultTimeout(120 * 1000);
    browser.setViewportSize({
      width: resolution_w,
      height: resolution_h,
    }, true);
  });

  this.Given(/^I go to the backend of Checkout's plugin$/, () => {
    const { url, admin } = Globals.value;
    const {
      admin_username,
      admin_password,
      admin_login
    } = Globals.selector.backend;
    browser.url(url.magento_base + url.payments_path);
    if (browser.isVisible(admin_username)) {
      browser.setValue(admin_username, admin.username);
      browser.setValue(admin_password, admin.password);
      browser.click(admin_login);
      browser.url(url.magento_base + url.payments_path);
    }
  });

  this.Given(/^I set the sandbox keys$/, () => {
    const { secret_key, public_key, private_shared_key } = Globals.value.admin;
    const { timeout_out } = Globals.value;
    const { configuration, all_in_one } = Globals.selector.backend;

    browser.waitUntil(() => browser.isVisible(all_in_one), timeout_out, 'expected plugin settings should be visible');

    // if the section is not expanded, expand it and enable it
    if (!browser.isVisible(configuration.selector)) {
      browser.click(all_in_one);
    }
    if (!browser.isVisible(configuration.environment)) {
      browser.click(configuration.selector);
    }

    browser.setValue(configuration.secret_key, secret_key);
    browser.setValue(configuration.public_key, public_key);
    browser.setValue(configuration.private_shared_key, private_shared_key);
  });

  this.Then(/^I save settings$/, () => {
    const { save } = Globals.selector.backend;
    browser.pause(1000); // avoid issues with save
    browser.doubleClick(save);
    browser.pause(5000); // avoid issues with save
  });

  this.Then(/^I clear cache$/, () => {
    const { url } = Globals.value;
    const { clear_cache } = Globals.selector.backend;
    browser.url(url.magento_base + url.cache);
    browser.pause(3000);
    browser.click(clear_cache);
    browser.pause(3000);
  });

  this.Then(/^I create an account$/, () => {
    const { customer, url } = Globals.value;
    const {
      create_button,
      first_name,
      last_name,
      email,
      password,
      confirm_password,
      register,
      address_book,
      phone,
      street,
      city,
      state,
      postcode,
      save_account
    } = Globals.selector.frontend.create_account;
    browser.url(url.magento_base + url.sign_in_path);
    browser.click(create_button);
    browser.setValue(first_name, customer.name);
    browser.setValue(last_name, customer.lastname);
    browser.setValue(email, customer.email);
    browser.setValue(password, customer.password);
    browser.setValue(confirm_password, customer.password);
    browser.click(register);
    browser.click(address_book);
    browser.setValue(phone, customer.phone);
    browser.setValue(street, customer.street);
    browser.setValue(city, customer.city);
    browser.selectByValue(state, 1);
    browser.setValue(postcode, customer.postcode);
    browser.click(save_account);
  });

  this.Then(/^I login to the account$/, () => {
    const { url, customer } = Globals.value;
    const { frontend } = Globals.selector;
    browser.url(url.magento_base + url.sign_in_path);
    browser.setValue(frontend.login.email, customer.email);
    browser.setValue(frontend.login.password, customer.password);
    browser.click(frontend.login.loginButton);
  });

  this.Then(/^I logout of the account$/, () => {
    const { url } = Globals.value;
    browser.url(url.magento_base + url.sign_out_path);
  });
}
