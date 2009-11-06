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

class PVMStatusColumn extends MantisColumn {
	public $title = 'Version Status';
	public $column = 'status';
	public $sortable = true;

	public $id = 0;

	protected static $ids = array();
	protected static $names = array();
	protected static $cache = null;

	/**
	 * Keep a list of version ID's used by loaded columns
	 */
	public function __construct() {
		self::$ids[ $this->id ] = true;
	}

	/**
	 * Cache status data for the given set of issues.
	 * @param array Bug objects
	 */
	public function cache( $p_bugs ) {
		if ( self::$cache === null ) {
			self::$cache = array();

			if ( count( $p_bugs ) > 0 ) {
				plugin_push_current( 'ProductMatrix' );

				$t_bug_ids = array();
				foreach( $p_bugs as $t_bug ) {
					$t_bug_ids[] = (int)$t_bug->id;
					self::$cache[ (int)$t_bug->id ] = array();
				}
				$t_bug_ids = implode( ',', $t_bug_ids );
				$t_ver_ids = implode( ',', array_keys( self::$ids ) );

				$t_status_table = plugin_table( 'status' );

				$t_query = "SELECT * FROM $t_status_table WHERE bug_id IN ($t_bug_ids) AND version_id IN ($t_ver_ids)";
				$t_result = db_query_bound( $t_query );

				while( $t_row = db_fetch_array( $t_result ) ) {
					self::$cache[ $t_row['bug_id'] ][ $t_row['version_id'] ] = $t_row['status'];
				}

				plugin_pop_current();
			}
		}
	}

	/**
	 * Display status data for a given issue.
	 * @param BugData Bug object
	 */
	public function display( $p_bug, $p_columns_target ) {
		static $status, $color;

		if ( !isset( $status ) ) {
			plugin_push_current( 'ProductMatrix' );

			$status = plugin_config_get('status');
			$color = plugin_config_get('status_color');

			plugin_pop_current();
		}

		if ( isset( self::$cache[ $p_bug->id ][ $this->id ] ) ) {
			$t_status = self::$cache[ $p_bug->id ][ $this->id ];
			echo '<span class="pvmstatuscolumn" statuscolor="', $color[ $t_status ], '">', $status[ $t_status ], '</span>';
		}
	}

	public function sortquery( $p_dir ) {
		$t_version_id = $this->id;
		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		return array(
			'join' => "LEFT JOIN $t_status_table pvmst ON $t_bug_table.id=pvmst.bug_id AND pvmst.version_id=$t_version_id",
			'order' => "pvmst.status $p_dir",
		);
	}
}

