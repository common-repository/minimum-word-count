<?php
/*
Plugin Name: Minimum Word Count
Plugin URI: http://www.clearcode.com/wordpress/plugins/minimum-word-count/
Description: Set the minimum word count required to publish a post.
Version: 0.7.0
Author: ClearCode Software
Author URI: http://www.clearcode.com/
*/

global $mwc_version;
$mwc_version = "0.7.0";

function mwc_install()
{
	update_option("minimum_word_count_min", 0);
}
register_activation_hook(__FILE__,'mwc_install');


function minimum_word_count($post_id)
{
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;

	// verify that this is not a revision
	if (get_post_type($post_id) == "revision") return; //wp_die ( __("post is a revision"));
	if (wp_is_post_revision($post_id)) return;

	$minimum_word_count_min = get_option("minimum_word_count_min", 0);
	$minimum_word_count_admin_exempt = get_option("minimum_word_count_admin_exempt", false);

	if (!is_admin() || (is_admin() && !$minimum_word_count_admin_exempt)) {
		if ($minimum_word_count_min > 0) {
			$content = $_POST['content'];
			$content_word_count = str_word_count(strip_tags($content));
			if ($content_word_count < $minimum_word_count_min) {
				$mwc_post_date = get_post($post_id)->post_date_gmt;
				$mwc_now = time();
				$mwc_post_date = strtotime($mwc_post_date);
				$mwc_date_diff = ($mwc_now - $mwc_post_date);

				if ($mwc_date_diff < 2) {
					// newly published post
					wp_update_post(array( 'ID' => $post_id, 
										  'post_status' => 'draft', 
										  'post_date_gmt' => 0,
										  'post_name' => ''
										  ));
					update_option('mwc_errors', "<strong>Error:</strong> post is below the minimum word count requirement. It must be over ".number_format($minimum_word_count_min, 0)." words and currently has ".number_format($content_word_count, 0)." words.");
				} else {
					// previously published post
					update_option('mwc_warnings', "<strong>Warning:</strong> post is below the minimum word count requirement. It should be over ".number_format($minimum_word_count_min, 0)." words and currently has ".number_format($content_word_count, 0)." words.");
				}
			}
		}
	}
}
add_action('publish_post', 'minimum_word_count');
add_action('publish_page', 'minimum_word_count');

// Display any errors
function mwc_admin_notice_handler() {
    $warnings = get_option('mwc_warnings');
    $errors = get_option('mwc_errors');
    if($warnings) {
        echo '<div class="error"><p>' . $warnings . '</p></div>';
    }
    if($errors) {
        echo '<div class="error"><p>' . $errors . '</p></div>';
    }
    update_option('mwc_warnings', "");
    update_option('mwc_errors', "");
}
add_action( 'admin_notices', 'mwc_admin_notice_handler' );

// remove "Page published" message if errors
function mwc_redirect_post_location_handler($location) {
	$errors = get_option('mwc_errors');
    if($errors) {
    	$location = remove_query_arg('message', $location);
    }
    return $location;
}
add_filter('redirect_post_location', 'mwc_redirect_post_location_handler');


/*      ADMIN SECTION       */

add_action( 'admin_menu', 'mwc_admin_menu' );

function mwc_admin_menu() {
	add_options_page('Minimum Word Count Options', 'Min Word Count', 'manage_options', 'mwc_admin', 'mwc_admin_page');
}

function mwc_admin_page()
{
	global $mwc_version;
	$hidden_field_name = 'mwc_submit_hidden';

	if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

		$mwc_new_min = $_POST['mwc_min'];
		if (!is_numeric($mwc_new_min)) {
			$mwc_error = "<strong>Error:</strong> Minimum Word Count value '".$mwc_new_min."' is invalid. Please enter a number.";
		} else if ($mwc_new_min < 0) {
			$mwc_error = "<strong>Error:</strong> Minimum Word Count value '".$mwc_new_min."' is invalid. Please enter a positive number.";
		} else {
			update_option("minimum_word_count_min", (int) $mwc_new_min);
		}

		$mwc_admin_exempt = $_POST['mwc_admin_exempt'];
		if ($mwc_admin_exempt == "on") {
			update_option("minimum_word_count_admin_exempt", true);
		} else {
			update_option("minimum_word_count_admin_exempt", false);
		}

		$mwc_updated = true;
	}

	$minimum_word_count_min = get_option("minimum_word_count_min", 0);
	$minimum_word_count_admin_exempt = get_option("minimum_word_count_admin_exempt", false);

	?>

	<style type="text/css">
	table.mwc_table tr td {padding:5px 10px 5px 0; vertical-align:top;}
	a.mwc_link {text-decoration:none;}
	a.mwc_link:hover {text-decoration:underline;}
	</style>

	<div class="wrap">

		<h2>Minimum Word Count Settings</h2>

		<p>
			<?php
				if (isset($mwc_error)) {
					?>
						<div class="error"><p><?php echo $mwc_error; ?></p></div>
					<?php
				} else if ($mwc_updated == true) {
					?>
						<div class="updated"><p>Settings have been updated.</p></div>
					<?php
				} 
			?>
		</p>

		<div id="poststuff" class="metabox-holder has-right-sidebar">

			<div class="inner-sidebar">

				<div class="postbox">
					<h3>About This Plugin</h3>
					<div class="inside">

						<p><a href="http://www.clearcode.com/wordpress/plugins/minimum-word-count/" target="_blank">Visit the Plugin Homepage</a></p>

						<p>This plugin allows you to set a minimum word count required to publish a post. You have the option to exempt administrators from this requirement.</p>

						<p>As part of this plugin, a "Word Count" column is added to the post listing pages.</p>

						<p>Also, on the post editing pages, the "Word Count" field just below the content section is now changed to "Live Word Count" and updated as you type. If the current post is below the minimum word count setting, the count will turn red.</p>

						<p>Developed by <a href="http://www.clearcode.com" target="_blank" class="mwc_link">ClearCode Software</a></p>

						<p>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
							<input type="hidden" name="cmd" value="_s-xclick">
							<input type="hidden" name="hosted_button_id" value="L2P2UTVG4UCNA">
							<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="Donate to ClearCode">
							<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
							</form>
						</p>

						<p style="text-align:right; font-size:0.9em; color:#cccccc;">v<?php echo $mwc_version; ?></p>

					</div>
				</div>

			</div>

			<form name="mwc_form" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<div id="post-body-content" class="has-sidebar-content">

				<div class="postbox">
					<h3>Settings</h3>
					<div class="inside">
						<table class="mwc_table">
							<tr>
								<td><strong>Minimum Word Count:</strong></td>
								<td><input type="text" name="mwc_min" value="<?php echo $minimum_word_count_min; ?>"><br />
									Set the minimum word count required to publish a post or page (0 to disable minimum).
								</td>
							</tr>
							<tr>
								<td><strong>Admin Exempt:</strong></td>
								<td><input type="checkbox" name="mwc_admin_exempt" <?php echo ($minimum_word_count_admin_exempt == true ? "checked=\"checked\"" : ""); ?>><br />
									Allow administrators to publish posts/pages below the minimum word count requirement.
								</td>
							</tr>
							<tr>
								<td></td>
								<td><input type="submit" value=" Save "></td>
							</tr>
						</table>
					</div>
				</div>

			</div>
			</form>

		</div>

	</div>

	<?php
}

function mwc_set_plugin_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ($file == $plugin) {
		return array_merge(
			$links,
			array( sprintf( '<a href="options-general.php?page=mwc_admin">%s</a>', __('Settings') ) )
		);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'mwc_set_plugin_meta', 10, 2 );


// word count column
function mwc_column_header($mwclabel) {
    $mwclabel['mwc_column'] = 'Word Count';
    return $mwclabel;
}
add_filter('manage_posts_columns', 'mwc_column_header');
add_filter('manage_pages_columns', 'mwc_column_header');

function mwc_column($column_name, $post_id) {
	
    if($column_name == 'mwc_column') {
		$minimum_word_count_min = get_option("minimum_word_count_min", 0);
		$content_word_count = str_word_count(strip_tags(get_post($post_id)->post_content));
		if ($content_word_count < $minimum_word_count_min) {
			echo "<span style='color:#ff0000;'>{$content_word_count}</span>";
		} else {
			echo "{$content_word_count}";
		}
    }
}
add_action('manage_posts_custom_column', 'mwc_column', 10, 2);
add_action('manage_pages_custom_column', 'mwc_column', 10, 2);


// live word count
function mwc_enqueue($hook) {
    if( !($hook == 'post.php' || $hook == 'post-new.php') )
        return;
    wp_enqueue_script( 'mwc_custom_script', plugins_url('/minimum-word-count.js', __FILE__) );

    // pass variables to the word count javascript
	$mwc_options = array( 'min' => get_option("minimum_word_count_min", 0) );
	wp_localize_script( 'mwc_custom_script', 'mwc_options', $mwc_options );
}
add_action( 'admin_enqueue_scripts', 'mwc_enqueue' );

?>