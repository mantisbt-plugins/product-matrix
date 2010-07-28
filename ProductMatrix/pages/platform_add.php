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

form_security_validate( 'ProductMatrix_platform_add' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'product_id' );
$f_platform_name = gpc_get_string( 'platform_name' );

$t_product = PVMProduct::load( $f_product_id );

$t_platform = new PVMPlatform( $t_product->id, $f_platform_name );
$t_platform->save();

form_security_purge( 'ProductMatrix_platform_add' );

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );


