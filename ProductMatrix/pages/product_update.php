<?php
# Copyright (C) 2008-2010	John Reese
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

form_security_validate( 'ProductMatrix_product_update' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$t_reverse_inherit = plugin_config_get( 'reverse_inheritence' );

$f_product_id = gpc_get_int( 'product_id' );
$t_product = PVMProduct::load( $f_product_id );

$t_product->name = gpc_get_string( 'product_name', $t_product->name );

$t_delete_platforms = array();
foreach( $t_product->platforms as $t_platform ) {
	$t_prefix = 'platform_' . $t_platform->id . '_';

	$t_platform->name = gpc_get_string( $t_prefix . 'name', $t_platform->name );
	$t_platform->obsolete = gpc_get_bool( $t_prefix . 'obsolete', false );

	if ( gpc_get_bool( $t_prefix . 'delete', false ) ) {
		$t_delete_platforms[] = $t_platform->id;
	}
}

$t_delete_versions = array();
foreach( $t_product->versions as $t_version ) {
	$t_prefix = 'version_' . $t_version->id . '_';
	$t_version->name = gpc_get_string( $t_prefix . 'name', $t_version->name );
	$t_version->released = gpc_get_bool( $t_prefix . 'released', false );
	$t_version->obsolete = gpc_get_bool( $t_prefix . 'obsolete', false );
	$t_version->parent_id = gpc_get_int( $t_prefix . 'parent', 0 );

	if ( $t_reverse_inherit ) {
		$t_version->inherit_id = gpc_get_int( $t_prefix . 'inherit_id', 0 );
	}

	if ( gpc_get_bool( $t_prefix . 'delete', false ) ) {
		$t_delete_versions[] = $t_version->id;
	}
}

$t_product->save();

if ( count( $t_delete_platforms ) > 0 || count( $t_delete_versions ) > 0 ) {
	$t_message = plugin_lang_get( 'ensure_delete_members' ) . $t_product->name . ':<br/>';

	foreach( $t_delete_platforms as $t_id ) {
		$t_message .= plugin_lang_get( 'platform' ) . ' ' . $t_product->platforms[$t_id]->name . '<br/>';
	}

	foreach( $t_delete_versions as $t_id ) {
		$t_message .= plugin_lang_get( 'version' ) . ' ' . $t_product->versions[$t_id]->name . '<br/>';
	}

	helper_ensure_confirmed( $t_message, plugin_lang_get( 'delete' ) );

	foreach( $t_delete_platforms as $t_id ) {
		PVMPlatform::delete( $t_id );
	}

	foreach( $t_delete_versions as $t_id ) {
		PVMVersion::delete( $t_id );
	}
}

form_security_purge( 'ProductMatrix_product_update' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

