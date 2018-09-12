<?php

//network settings functionality

use \vocabularies\SMDE_Metadata_Educational as lrmi_meta;

defined ("ABSPATH") or die ("No script assholes!");

/**
 * Function for adding network settings page
 */
function smde_add_network_settings() {
	// Create our options page.
    add_submenu_page( 'settings.php', 'Simple Metadata Network Settings',
    'Metadata', 'manage_network_options',
    'smde_net_set_page', 'smde_render_network_settings');

    //adding settings metaboxes and settigns sections
    add_meta_box('smde-metadata-network-location', 'Location Of Metadata', 'smde_network_render_metabox_schema_locations', 'smde_net_set_page', 'normal', 'core');
    add_meta_box('smde-network-metadata-lrmi-properties', 'LRMI Properties Management', 'smde_network_render_metabox_lrmi_properties', 'smde_net_set_page', 'normal', 'core');

    add_settings_section( 'smde_network_meta_locations', '', '', 'smde_network_meta_locations' );

    add_settings_section( 'smde_network_meta_lrmi_properties', '', '', 'smde_network_meta_lrmi_properties' );

    //registering settings
    register_setting('smde_network_meta_locations', 'smde_net_locations');
	register_setting ('smde_network_meta_lrmi_properties', 'smde_net_lrmi_shares');
	register_setting ('smde_network_meta_lrmi_properties', 'smde_net_lrmi_freezes');

	// getting options values from DB
	$post_types = smde_get_all_post_types();
	$locations = get_option('smde_net_locations');
	$shares_lrmi = get_option('smde_net_lrmi_shares');
	$freezes_lrmi = get_option('smde_net_lrmi_freezes');

	//adding settings for locations
	foreach ($post_types as $post_type) {
		if ('metadata' == $post_type){
			$label = 'Book Info';
		} else {
			$label = ucfirst($post_type);
		}
		add_settings_field ('smde_net_locations['.$post_type.']', $label, function () use ($post_type, $locations){
			$checked = isset($locations[$post_type]) ? true : false;
			?>
				<input type="checkbox" name="smde_net_locations[<?=$post_type?>]" id="smde_net_locations[<?=$post_type?>]" value="1" <?php checked(1, $checked);?>>
			<?php
		}, 'smde_network_meta_locations', 'smde_network_meta_locations');
	}

	//adding settings for properties management
	foreach (lrmi_meta::$lrmi_properties as $key => $data) {
		add_settings_field ('smde_net_lrmi_'.$key, ucfirst($data[1]), function () use ($key, $shares_lrmi, $freezes_lrmi){
			$checked_lrmi_share = isset($shares_lrmi[$key]) ? true : false;
			$checked_lrmi_freeze = isset($freezes_lrmi[$key]) ? true : false;
			?>
				<label for="smde_net_lrmi_shares[<?=$key?>]"><i>Share</i> <input type="checkbox" name="smde_net_lrmi_shares[<?=$key?>]" id="smde_net_lrmi_shares[<?=$key?>]" value="1" <?php checked(1, $checked_lrmi_share);?>></label>
				<label for="smde_net_lrmi_freezes[<?=$key?>]"><i>Freeze</i> <input type="checkbox" name="smde_net_lrmi_freezes[<?=$key?>]" id="smde_net_lrmi_freezes[<?=$key?>]" value="1" <?php checked(1, $checked_lrmi_freeze);?>></label>
			<?php
		}, 'smde_network_meta_lrmi_properties', 'smde_network_meta_lrmi_properties');
	}
}

/**
 * Function for rendering settings page
 */
function smde_render_network_settings(){
	wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
	    ?>
	    <div class="wrap">
	    	<?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) { ?>
        	<div class="notice notice-success is-dismissible"> 
				<p><strong>Settings saved.</strong></p>
			</div>
			<?php } ?>
		    <div class="metabox-holder">
			    <?php
			    	do_meta_boxes('smde_net_set_page', 'normal','');
			    ?>
		    </div>
	    </div>
	    <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                // close postboxes that should be closed
                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                // postboxes setup
                postboxes.add_postbox_toggles('<?php echo 'smde_net_set_page'; ?>');
            });
            //]]>
		</script>
		<?php
}

/**
 * Function for rendering metabox of locations
 */
function smde_network_render_metabox_schema_locations(){
	?>
	<div id="smde_network_meta_locations" class="smde_network_meta_locations">
		<form method="post" action="edit.php?action=smde_update_network_locations">
			<?php
			settings_fields( 'smde_network_meta_locations' );
			do_settings_sections( 'smde_network_meta_locations' );
			submit_button();
			?>
		</form>
		<p></p>
	</div>
	<?php
}

/**
 * Function for rendering metabox for properties management
 */
function smde_network_render_metabox_lrmi_properties(){
	?>
	<div id="smde_network_meta_lrmi_properties" class="smde_network_meta_lrmi_properties">
		<form method="post" action="edit.php?action=smde_update_network_options">
			<?php
			settings_fields( 'smde_network_meta_lrmi_properties' );
			submit_button();
			do_settings_sections( 'smde_network_meta_lrmi_properties' );
			?>
		</form>
		<p></p>
	</div>
	<?php
}

/**
 * Handler for locations settings update
 */
function smde_update_network_locations() {

	check_admin_referer('smde_network_meta_locations-options');

	//Wordpress Database variable for database operations
    global $wpdb;

	$locations = isset($_POST['smde_net_locations']) ? $_POST['smde_net_locations'] : array();

	update_blog_option(1, 'smde_net_locations', $locations);

	//Grabbing all the site IDs
    $siteids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

    //Going through the sites
    foreach ($siteids as $site_id) {
    	if (1 == $site_id){
    		continue;
    	}

    	switch_to_blog($site_id);

    	$locations_local = get_option('smde_locations') ?: array();

    	$locations_local = array_merge($locations_local, $locations);

    	update_option('smde_locations', $locations_local);

    }

    restore_current_blog();

	// At the end we redirect back to our options page.
    wp_redirect(add_query_arg(array('page' => 'smde_net_set_page',
    'settings-updated' => 'true'), network_admin_url('settings.php')));

    exit;
}

/**
 * Handler for properties settings update
 */
function smde_update_network_options() {

	check_admin_referer('smde_network_meta_lrmi_properties-options');

	//Wordpress Database variable for database operations
    global $wpdb;

    $freezes = isset($_POST['smde_net_lrmi_freezes']) ? $_POST['smde_net_lrmi_freezes'] : array();
    $shares = isset($_POST['smde_net_lrmi_shares']) ? $_POST['smde_net_lrmi_shares'] : array();

	update_blog_option(1, 'smde_net_lrmi_freezes', $freezes);
	update_blog_option(1, 'smde_net_lrmi_shares', $shares);

	//Grabbing all the site IDs
    $siteids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

    //Going through the sites
    foreach ($siteids as $site_id) {

    	if (1 == $site_id){
    		continue;
    	}

    	switch_to_blog($site_id);

    	$freezes_local = get_option('smde_lrmi_freezes') ?: array();
    	$frezees_local = array_merge($freezes_local, $freezes);

    	$shares_local = get_option('smde_lrmi_shares') ?: array();
    	$shares_local = array_merge($shares_local, $shares);

    	update_option('smde_lrmi_freezes', $frezees_local);
    	update_option('smde_lrmi_shares', $shares_local);

    	smde_update_overwrites();
    }

    restore_current_blog();

	// At the end we redirect back to our options page.
    wp_redirect(add_query_arg(array('page' => 'smde_net_set_page',
    'settings-updated' => 'true'), network_admin_url('settings.php')));

    exit;
}


add_action( 'network_admin_menu', 'smde_add_network_settings');
add_action( 'network_admin_edit_smde_update_network_locations', 'smde_update_network_locations');
add_action( 'network_admin_edit_smde_update_network_options', 'smde_update_network_options');