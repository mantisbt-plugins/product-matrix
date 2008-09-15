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

$f_product_name = gpc_get_string( 'product_name' );

form_security_validate( 'ProductMatrix_product_add' );
$t_product = new PVMProduct( $f_product_name );
$t_product->save();

print_successful_redirect( plugin_page( 'product_view', true ) . '&id=' . $t_product->id );

