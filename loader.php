<?php

/**
 * Plugin Name: All in One Invite Codes BuddyForms
 * Plugin URI:  https://themekraft.com/all-in-one-invite-codes/
 * Description: Create Invite only Forms
 * Version: 0.1
 * Author: ThemeKraft
 * Author URI: https://themekraft.com/
 * Licence: GPLv3
 * Network: false
 * Text Domain: all-in-one-invite-codes
 *
 * ****************************************************************************
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
 *
 ****************************************************************************
 */

if ( ! function_exists( 'bfe_fs' ) ) {
	// Create a helper function for easy SDK access.
	function bfe_fs() {
		global $bfe_fs;

		if ( ! isset( $bfe_fs ) ) {
			// Include Freemius SDK.
			if ( file_exists( dirname( dirname( __FILE__ ) ) . '/all-in-one-invite-codes/includes/resources/freemius/start.php' ) ) {
				// Try to load SDK from parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/all-in-one-invite-codes/includes/resources/freemius/start.php';
			} elseif ( file_exists( dirname( dirname( __FILE__ ) ) . '/all-in-one-invite-codes-premium/includes/resources/freemius/start.php' ) ) {
				// Try to load SDK from premium parent plugin folder.
				require_once dirname( dirname( __FILE__ ) ) . '/all-in-one-invite-codes-premium/includes/resources/freemius/start.php';
			}

			$bfe_fs = fs_dynamic_init( array(
				'id'                  => '3325',
				'slug'                => 'all-in-one-invite-codes-buddyforms',
				'premium_slug'        => 'buddyforms-form-element-premium',
				'type'                => 'plugin',
				'public_key'          => 'pk_2e932fa681295bbf58137fa222313',
				'is_premium'          => true,
				'is_premium_only'     => true,
				'has_paid_plans'      => true,
				'is_org_compliant'    => false,
				'trial'               => array(
					'days'               => 7,
					'is_require_payment' => true,
				),
				'parent'              => array(
					'id'         => '3322',
					'slug'       => 'all-in-one-invite-codes',
					'public_key' => 'pk_955be38b0c4d2a2914a9f4bc98355',
					'name'       => 'All in One Invite Codes',
				),
				'menu'                => array(
					'support'        => false,
				)
			) );
		}

		return $bfe_fs;
	}
}

function bfe_fs_is_parent_active_and_loaded() {
	// Check if the parent's init SDK method exists.
	return function_exists( 'all_in_one_invite_codes_core_fs' );
}

function bfe_fs_is_parent_active() {
	$active_plugins = get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
	}

	foreach ( $active_plugins as $basename ) {
		if ( 0 === strpos( $basename, 'all-in-one-invite-codes/' ) ||
		     0 === strpos( $basename, 'all-in-one-invite-codes-premium/' )
		) {
			return true;
		}
	}

	return false;
}

function bfe_fs_init() {
	if ( bfe_fs_is_parent_active_and_loaded() ) {
		// Init Freemius.
		bfe_fs();

		// Parent is active, add your init code here.
	} else {
		// Parent is inactive, add your error handling here.
	}
}

if ( bfe_fs_is_parent_active_and_loaded() ) {
	// If parent already included, init add-on.
	bfe_fs_init();
} else if ( bfe_fs_is_parent_active() ) {
	// Init add-on only after the parent is loaded.
	add_action( 'all_in_one_invite_codes_core_fs_loaded', 'bfe_fs_init' );
} else {
	// Even though the parent is not activated, execute add-on for activation / uninstall hooks.
	bfe_fs_init();
}


/*
 * Create the new Form Builder Form Element
 */
add_filter( 'buddyforms_form_element_add_field', 'all_in_one_invite_codes_buddyforms_create_new_form_builder_form_element', 1, 5 );
function all_in_one_invite_codes_buddyforms_create_new_form_builder_form_element( $form_fields, $form_slug, $field_type, $field_id ) {
	global $buddyforms;

	switch ( $field_type ) {

		case 'invite_codes':

			unset( $form_fields );

			$name                           = isset( $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['name'] ) ? $buddyforms[ $form_slug ]['form_fields'][ $field_id ]['name'] : 'Invite Codes';
			$form_fields['general']['name'] = new Element_Textbox( '<b>' . __( 'Name', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][name]", array( 'value' => $name ) );

			$form_fields['general']['slug'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][slug]", 'invite_codes' );
			$form_fields['general']['type'] = new Element_Hidden( "buddyforms_options[form_fields][" . $field_id . "][type]", $field_type );

			break;

	}

	return $form_fields;
}


/*
 * Display the new Form Element in the Frontend Form
 *
 */
add_filter( 'buddyforms_create_edit_form_display_element', 'all_in_one_invite_codes_buddyforms_create_frontend_form_element', 1, 2 );
function all_in_one_invite_codes_buddyforms_create_frontend_form_element( $form, $form_args ) {
	global $buddyforms, $field;

	extract( $form_args );

	$post_type = $buddyforms[ $form_slug ]['post_type'];

	if ( ! $post_type ) {
		return $form;
	}

	if ( ! isset( $customfield['type'] ) ) {
		return $form;
	}

	switch ( $customfield['type'] ) {
		case 'invite_codes':
			//$form->addElement( new Element_Hidden( $customfield['slug'], $customfield['invite_codes'] ) );
			$form->addElement( new Element_Textbox( $customfield['name'], $customfield['slug'] ) );
			break;
	}

	return $form;
}

/*
 * Add the xprofile Form ELement to the Form Element Select
 */
add_filter( 'buddyforms_add_form_element_select_option', 'all_in_one_invite_codes_buddyforms_members_add_form_element_to_select', 1, 2 );
function all_in_one_invite_codes_buddyforms_members_add_form_element_to_select( $elements_select_options ) {
	global $post;

	if ( $post->post_type != 'buddyforms' ) {
		return $elements_select_options;
	}

	$elements_select_options['buddyforms']['label']                  = 'Invite Codes';
	$elements_select_options['buddyforms']['class']                  = 'bf_show_if_f_type_all';
	$elements_select_options['buddyforms']['fields']['invite_codes'] = array(
		'label'  => __( 'Invite codes', 'buddyforms' ),
		'unique' => 'unique'
	);

	return $elements_select_options;
}


add_filter( 'buddyforms_form_custom_validation', 'all_in_one_invite_codes_buddyforms_server_validation', 2, 2 );


function all_in_one_invite_codes_buddyforms_server_validation( $valid, $form_slug ) {
	global $buddyforms;

	$form_field = buddyforms_get_form_field_by_slug( $form_slug, 'invite_codes' );
	if ( $form_field ) {

		$result = all_in_one_invite_codes_validate_code( $_POST[ $form_field['slug'] ], $_POST[ $form_field['user_mail'] ] );

		if ( isset( $result['error'] ) ) {
			;
			Form::setError( 'buddyforms_form_' . $form_slug, $result['error'], $form_field['name'] );

			return false;
		}


	}

	return true;
}