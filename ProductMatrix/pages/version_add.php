<?php
# Copyright (C) 2008-2010, 2016  John Reese
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

form_security_validate( 'ProductMatrix_version_add' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'product_id' );

$f_version_name = gpc_get_string( 'version_name' );
$f_parent_id = gpc_get_int( 'parent_id' );
$f_migrate_id = gpc_get_int( 'migrate_id' );

$t_product = PVMProduct::load( $f_product_id );

if ( 0 != $f_parent_id && !isset( $t_product->versions[ $f_parent_id ] ) ) {
	$f_parent_id = 0;
	trigger_error( ERROR_GENERIC, ERROR );
}

if ( 0 != $f_migrate_id && !isset( $t_product->versions[ $f_migrate_id ] ) ) {
	$f_migrate_id = 0;
	trigger_error( ERROR_GENERIC, ERROR );
}

$t_version = new PVMVersion( $t_product->id, $f_version_name, $f_parent_id, $f_migrate_id );

$t_version->save();
form_security_purge( 'ProductMatrix_version_add' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

