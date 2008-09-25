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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_version_id = gpc_get_int( 'id' );
$t_product = array_shift( PVMProduct::load_by_version_ids( $f_version_id ) );
$t_version = PVMVersion::load( $f_version_id );

helper_ensure_confirmed( plugin_lang_get( 'ensure_delete_version' ) .
	$t_product->name . ' ' . $t_version->name, plugin_lang_get( 'delete' ) );

form_security_validate( 'ProductMatrix_version_delete' );
PVMVersion::delete( $f_version_id );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

