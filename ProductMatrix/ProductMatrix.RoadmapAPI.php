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

/**
 * Returns product name for a given product ID
 *
 * @param integer $p_product_id
 * @return string Product name
 */
function PRM_product_get_name( $p_product_id ){

	$t_product_tbl = plugin_table( 'product', 'ProductMatrix' );

	$t_query = "SELECT * FROM $t_product_tbl WHERE id=" . db_param();

	$t_result = db_query( $t_query, array( $p_product_id ) );
	$t_row = db_fetch_array( $t_result );

	return $t_row['name'];
}

/**
 * Retrieves all Product names
 *
 * @return array of Products
 */
function PRM_product_get_all_rows(){
	$t_product_tbl = plugin_table( 'product', 'ProductMatrix' );

	$t_query = "SELECT * FROM $t_product_tbl";
	$t_result = db_query( $t_query );

	$t_products = array();

	while( $t_rows = db_fetch_array( $t_result ) ){
		$t_products[] = $t_rows;
	}

	return $t_products;
}

/**
 * Prints product name
 *
 * @param string $p_product_name
 */
function PRM_print_product_name( $p_product_name ){
	echo '<br /><b>Product: </b>' . string_display( $p_product_name ) . '<br />';
}

/**
 * Return all versions for the specified project
 *
 * @param integer $p_product_id
 * @return array from database query
 */
function PRM_product_version_get_all_rows( $p_product_id ) {

	$t_version_table = plugin_table( 'version', 'ProductMatrix' );

	$t_query = "SELECT * FROM $t_version_table WHERE product_id=" . db_param();
	$t_result = db_query( $t_query, array( $p_product_id ) );
	$t_count = db_num_rows( $t_result );

	$t_rows = array();

	for( $i = 0; $i < $t_count; $i++ ) {
		$t_row = db_fetch_array( $t_result );
		$t_row['date'] = PVMDBDate( $t_row['date'] );
		$t_rows[] = $t_row;
	}

	return $t_rows;
}

/**
 * Prints hyperlinked version name
 * to filter page to a version.
 *
 * @param string $p_version_name
 * @param integer $p_product_id
 * @param string $p_href_page page to link
 */
function PRM_print_version_header( $p_version_name, $p_product_id, $p_href_page='' ) {

	$t_release_title_without_hyperlinks = plugin_lang_get('roadmap_product_version') .
		string_display( $p_version_name );

	$t_href_page = $p_href_page . '&name=';
	$t_version_name = string_url($p_version_name);

	$t_release_title = '<a href=' . plugin_page( $t_href_page ) . $t_version_name .
		'&id=' . $p_product_id . '>' . plugin_lang_get( 'roadmap_product_version' ) .
		string_display( $p_version_name ) . '</a>';

	echo '<br />' . $t_release_title . '<br />';
	echo '<tt>';
	echo str_pad( '', strlen( $t_release_title_without_hyperlinks ), '=' ), '<br />';
	echo '</tt>';
}

/**
 * Retrieves version and status info
 * from database
 *
 * @param string $p_version
 * @param integer $p_product_id
 * @return db query results
 */
function PRM_roadmap_query( $p_version, $p_product_id ) {
	$t_bug_tbl	= db_get_table( 'bug' );
	$t_status_tbl = plugin_table( 'status', 'ProductMatrix' );
	$t_version_tbl = plugin_table( 'version', 'ProductMatrix' );

	$t_select_columns = "version.*,status.status AS s_status, status.*, bug.*, bug.id AS t_bug_id";
	$t_query = "SELECT $t_select_columns FROM $t_bug_tbl AS bug
				JOIN $t_status_tbl AS status ON bug.id=status.bug_id
				JOIN $t_version_tbl AS version ON status.version_id=version.id
				WHERE bug.id=status.bug_id AND version.name=" . db_param() .
				' AND version.product_id=' . db_param() .
				' ORDER BY bug.id DESC';

	$t_result = db_query( $t_query, array( $p_version, $p_product_id ) );
	return $t_result;
}

function PRM_changelog_query( $p_version, $p_product_id ) {
	$t_bug_tbl	= db_get_table( 'mantis_bug_table' );
	$t_status_tbl = plugin_table( 'status', 'ProductMatrix' );
	$t_version_tbl = plugin_table( 'version', 'ProductMatrix' );

	$t_select_columns = "version.*,status.status AS s_status, status.*, bug.*, bug.id AS t_bug_id";
	$t_query = "SELECT $t_select_columns FROM $t_bug_tbl AS bug
				JOIN $t_status_tbl AS status ON bug.id=status.bug_id
				JOIN $t_version_tbl AS version ON status.version_id=version.id
				WHERE bug.id=status.bug_id AND version.name=" . db_param() .
				' AND version.product_id=' . db_param() .
				' AND status.status <=' . config_get( 'bug_resolved_status_threshold' ) .
				' ORDER BY bug.id DESC';

	$t_result = db_query( $t_query, array( $p_version, $p_product_id ) );
	return $t_result;
}

/**
 * Retrieves Bug associated with Version
 *
 * @param string $p_version Version Name
 * @param integer $p_product_id Product Id
 */
function PRM_product_print_bugs( $p_version, $p_product_id ){

	$t_row = array();
	$t_issues_planned = 0;
	$t_issues_resolved = 0;
	$t_issues_counted = array();
	$t_bug_ids = array();
	$t_versions_status = array();

	$t_result = PRM_roadmap_query( $p_version, $p_product_id );

	while( $t_row = db_fetch_array( $t_result ) ){

		if( $t_row['released'] == 1 || $t_row['obsolete'] == 1 ){
			continue;
		}

		if ( !isset( $t_issues_counted[$t_row['t_bug_id']] ) ) {
			$t_issues_planned++;

			if ( bug_is_resolved( $t_row['t_bug_id'] ) ) {
				$t_issues_resolved++;
			}

			$t_issues_counted[$t_row['t_bug_id']] = true;
		}

		$t_bug_ids[] = $t_row['t_bug_id'];
		$t_versions_status[] = $t_row['s_status'];
	}

	#Print Version Headers if it has related bugs otherwise return.
	if( count( $t_bug_ids ) <= 0  ){
		return;
	}

	$t_version_header_printed = false;

	if ( !$t_version_header_printed ) {
		PRM_print_version_header( $p_version, $p_product_id, 'product_roadmap');
		$t_version_header_printed = true;
	}

	$t_progress = $t_issues_planned > 0 ? ( (integer) ( $t_issues_resolved * 100 / $t_issues_planned ) ) : 0;

	# show progress bar
	echo '<div class="progress400">';
	echo '  <span class="bar" style="width: ' . $t_progress . '%;">' . $t_progress . '%</span>';
	echo '</div>';

	#Print bugs
	PRM_roadmap_print_issue( $t_bug_ids, $t_versions_status );

}

/**
 * Prints Bug ID, Bug Summary, and Version status
 * Bug ID is hyperlinked to bug view.
 *
 * @param array $p_bug_ids
 * @param array $p_versions_status
 * @param boolean $p_print_strike
 */
function PRM_roadmap_print_issue( $p_bug_ids, $p_versions_status, $p_print_strike = true ) {

	for ( $j = 0; $j < count( $p_bug_ids ); $j++ ) {

		$t_bug_id = $p_bug_ids[$j];
		$t_version_status = $p_versions_status[$j];

		$p_issue_level = 0;

		$t_version_string = plugin_config_get('status');

		$t_bug = bug_get( $t_bug_id );

		if( bug_is_resolved( $t_bug_id ) && $p_print_strike ) {
			$t_strike_start = '<strike>';
			$t_strike_end = '</strike>';

		} else {
			$t_strike_start = $t_strike_end = '';
		}

		if( $t_bug->category_id ) {
			$t_category_name = category_get_name( $t_bug->category_id );
		} else {
			$t_category_name = '';
		}

		$t_category = is_blank( $t_category_name ) ? '' : '<b>[' . $t_category_name . ']</b> ';

		echo str_pad( '', $p_issue_level * 6, '&nbsp;' ), '- ', $t_strike_start,
			string_get_bug_view_link( $t_bug_id ), ': ', $t_category,
			string_display_line_links( $t_bug->summary );

		if( $t_bug->handler_id != 0 ) {
			echo ' (', prepare_user_name( $t_bug->handler_id ), ')';
		}

		echo ' - ', $t_version_string[$t_version_status], $t_strike_end, '.<br />';
	}
}
