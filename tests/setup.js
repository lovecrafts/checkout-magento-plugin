import Globals from '../../config/globals';

export default function () {
	this.Given(/^I set the viewport and timeout$/, () => {
	  const {width, height} = Globals.value;
	  this.setDefaultTimeout(120 * 1000);
	  browser.setViewportSize({
		width: VAL.resolution_w,
		height: VAL.resolution_h,
	  }, true);
	});
}