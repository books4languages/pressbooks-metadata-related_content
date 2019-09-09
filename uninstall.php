<?php

/**
 * Unistall the plugin
 *
 * Description. (use period)
 *
 * @link URL
 *
 * @package simple-metadata-education
 * @subpackage unistall
 * @since 1.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}


if(is_plugin_active('simple-metadata/simple-metadata.php')){
	//Simple metadata is installed
	//add dependency
	include_once   WP_PLUGIN_DIR . "/simple-metadata/inc/smd-uninstall-functions.php";
}
else{
	exit;
}

//get all the sites for multisite, if not a multisite, set blog id to 1
if (is_multisite()) {
	$blogs_ids = get_sites();
  smd_delete_network_options('smde_'); // see: simple metadata library
} else {
	$blogs_ids = [1];
}
smd_delete_local_options_and_post_meta($blogs_ids, 'smde_'); // see: smd_general_function.php library
