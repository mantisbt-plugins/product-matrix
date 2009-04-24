<?php
# Copyright (C) 2008-2009	John Reese
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

form_security_validate( 'plugin_ProductMatrix_config_update' );
auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$f_view_threshold = gpc_get_int( 'view_threshold' );
$f_update_threshold = gpc_get_int( 'update_threshold' );
$f_manage_threshold = gpc_get_int( 'manage_threshold' );

function maybe_set_option( $name, $value ) {
	if ( $value != plugin_config_get( $name ) ) {
		plugin_config_set( $name, $value );
	}
}

maybe_set_option( 'view_threshold', $f_view_threshold );
maybe_set_option( 'update_threshold', $f_update_threshold );
maybe_set_option( 'manage_threshold', $f_manage_threshold );

maybe_set_option( 'common_platform', gpc_get_bool( 'common_platform', OFF ) );
maybe_set_option( 'reverse_inheritence', gpc_get_bool( 'reverse_inheritence', OFF ) );
maybe_set_option( 'report_status', gpc_get_bool( 'report_status', OFF ) );

form_security_purge( 'plugin_ProductMatrix_config_update' );
print_successful_redirect( plugin_page( 'config_page', true ) );

