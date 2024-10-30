jQuery(function()
{
	mwc_init();
});

// Minimum Word Count Init
function mwc_init(){

	// change "Word Count" to "Live Word Count"
	// also changed interior span from class "word-count" to id "mwc-word-count"
	// this disables wordpress word count function which is overriden
	jQuery("#wp-word-count").html('Live Word Count: <span id="mwc-word-count">0</span>');

	// update word count on keyup event for content box
	jQuery("#content").bind("keyup", function() {
		mwc_do_word_count(0, jQuery("#content").val());
	});

	// override wpcountwords function
	jQuery(document).bind( 'wpcountwords', function(e, txt) {
		// pass parameters to custom word count function
		mwc_do_word_count(0, txt);
	});
}

// word count callback function
function mwc_do_word_count(e, txt) {

	//check for empty txt
	if (!txt) {
		jQuery("#mwc-word-count").html("0");
		return;
	}

	// strip HTML using browser
	var mwc_tmp_div = document.createElement("DIV");
	mwc_tmp_div.innerHTML = jQuery.trim(txt);
	mwc_tmp_text = mwc_tmp_div.textContent||mwc_tmp_div.innerText;

	// display current word count
	mwc_temp_word_count = mwc_tmp_text.split(' ').length;

	// mwc_options.min passed in from php script
	if (mwc_temp_word_count < mwc_options.min) {
		jQuery("#mwc-word-count").html('<span style="color:#ff0000;">' + mwc_temp_word_count + '</span>');
	} else {
		jQuery("#mwc-word-count").html(mwc_temp_word_count);
	}
}