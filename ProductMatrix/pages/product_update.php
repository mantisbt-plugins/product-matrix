<?php
# Copyright (C) 2008	John Reese
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

$f_product_id = gpc_get_int( 'product_id' );
$t_product = PVMProduct::load( $f_product_id );

$t_product->name = gpc_get_string( 'product_name', $t_product->name );

foreach( $t_product->versions as $t_version ) {
	$t_prefix = 'version_' . $t_version->id . '_';
	$t_version->name = gpc_get_string( $t_prefix . 'name', $t_version->name );
	$t_version->released = gpc_get_bool( $t_prefix . 'released', false );
	$t_version->obsolete = gpc_get_bool( $t_prefix . 'obsolete', false );
	$t_version->parent_id = gpc_get_int( $t_prefix . 'parent', 0 );
}

$t_product->save();
form_security_purge( 'ProductMatrix_product_update' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

