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

form_security_validate( 'ProductMatrix_projects_update' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'product_id' );
$f_project_ids = gpc_get_int_array( 'project_ids', array() );

$t_product = PVMProduct::load( $f_product_id );
$t_product->projects = null;

if ( count( $f_project_ids ) > 0 ) {
	foreach( $f_project_ids as $t_project_id ) {
		if ( $t_project_id > 0 ) {
			if ( is_null( $t_product->projects ) ) {
				$t_product->projects = array();
			}

			$t_product->projects[] = $t_project_id;
		}
	}
}

$t_product->save();

form_security_purge( 'ProductMatrix_projects_update' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

