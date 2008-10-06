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

form_security_validate( 'ProductMatrix_product_delete' );
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'id' );
$t_product = PVMProduct::load( $f_product_id, false );

helper_ensure_confirmed( plugin_lang_get( 'ensure_delete_product' ) . $t_product->name, plugin_lang_get( 'delete' ) );

PVMProduct::delete( $f_product_id );
form_security_purge( 'ProductMatrix_product_delete' );

print_successful_redirect( plugin_page( 'products', true ) );

