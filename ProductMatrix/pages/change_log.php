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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );

require_once( config_get_global( 'plugin_path' ) . 'ProductMatrix/ProductMatrix.RoadmapAPI.php' );

html_page_top1( plugin_lang_get( 'product_change_log' ) );  #title
html_page_top2();

$f_product_id = gpc_get_int( 'id' );
$t_product_name = PRM_product_get_name( $f_product_id );

PRM_print_product_name( $t_product_name );

$f_version_name = gpc_get_string( 'name', '' );

if ( is_blank( $f_version_name ) ) {
	$t_version_rows = array_reverse( PRM_product_version_get_all_rows( $f_product_id ) ); #retrieve all S/W Versions from db
} else {
	$t_version_rows = array( array( 'name' => $f_version_name ) );
}

$t_bug_resolved = config_get( 'bug_resolved_status_threshold' );

$t_print_strike = false;

foreach( $t_version_rows as $t_version_row ) {

	$t_version = $t_version_row['name'];
	$t_bug_ids = array();
	$t_versions_status = array();

	$t_result = PRM_roadmap_query( $t_version, $f_product_id );

	while( $t_row = db_fetch_array( $t_result ) ){

		if( $t_bug_resolved == $t_row['status'] ){
			$t_bug_ids[] = $t_row['t_bug_id'];
			$t_versions_status[] = $t_row['s_status'];
		} else {
			continue;
		}
	}

	if( count( $t_bug_ids ) > 0 ){
		PRM_print_version_header( $t_version, $f_product_id, 'product_change_log' );
		PRM_roadmap_print_issue( $t_bug_ids, $t_versions_status, $t_print_strike );
	}
}

html_page_bottom1( __FILE__ );
