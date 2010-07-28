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

form_security_validate( 'ProductMatrix_platform_delete' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_platform_id = gpc_get_int( 'id' );
$t_platform = PVMPlatform::load( $f_platform_id );
$t_product = PVMProduct::load( $t_platform->product_id );

helper_ensure_confirmed( plugin_lang_get( 'ensure_delete_platform' ) .
	$t_product->name . ' ' . $t_platform->name, plugin_lang_get( 'delete' ) );

PVMPlatform::delete( $f_platform_id );
form_security_purge( 'ProductMatrix_platform_delete' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );


