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

class PVMProductFilter extends MantisFilter {
	public $field = 'product';
	public $title = 'Product';
	public $type = FILTER_TYPE_MULTI_INT;
	public $default = array();

	public function query( $p_filter_input ) {
		if ( !is_array( $p_filter_input ) ) {
			return;
		}

		$t_products = PVMProduct::load_all( true );
		$t_version_ids = array();

		foreach( $p_filter_input as $t_product_id ) {
			$t_version_ids = array_merge( $t_version_ids, array_keys( $t_products[ (int)$t_product_id ]->versions ) );
		}
		$t_version_ids = join( ',', $t_version_ids );

		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = array(
			'join' => "LEFT JOIN $t_status_table ON $t_bug_table.id=$t_status_table.bug_id",
			'where' => "( $t_status_table.version_id IN ( $t_version_ids ) AND $t_status_table.status>0 )",
		);

		return $t_query;
	}

	public function display( $p_filter_value ) {
		$t_options = $this->options();

		if ( isset( $t_options[ $p_filter_value ] ) ) {
			return $t_options[ $p_filter_value ];
		}

		return $p_filter_value;
	}

	public function options() {
		static $s_options = null;

		if ( is_null( $s_products ) ) {
			$t_products = PVMProduct::load_all();
			$s_options = array();

			foreach( $t_products as $t_id => $t_product ) {
				$s_options[ $t_id ] = $t_product->name;
			}
		}

		return $s_options;
	}
}

class PVMVersionFilter extends MantisFilter {
	public $field = 'version';
	public $title = 'Version';
	public $type = FILTER_TYPE_MULTI_INT;
	public $default = array();

	public function query( $p_filter_input ) {
		if ( !is_array( $p_filter_input ) ) {
			return;
		}

		$t_version_ids = array();

		foreach( $p_filter_input as $t_version_id ) {
			$t_version_ids[ (int)$t_version_id ] = true;
		}

		$t_version_ids = join( ',', array_keys( $t_version_ids ) );

		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = array(
			'join' => "LEFT JOIN $t_status_table ON $t_bug_table.id=$t_status_table.bug_id",
			'where' => "( $t_status_table.version_id IN ( $t_version_ids ) AND $t_status_table.status>0 )",
		);

		return $t_query;
	}

	public function display( $p_filter_value ) {
		$t_options = $this->options();

		if ( isset( $t_options[ $p_filter_value ] ) ) {
			return $t_options[ $p_filter_value ];
		}

		return $p_filter_value;
	}

	public function options() {
		static $s_options = null;

		if ( is_null( $s_options ) ) {
			$t_products = PVMProduct::load_all( true );
			$s_options = array();

			$t_status_table = plugin_table( 'status', 'ProductMatrix' );

			$t_versions = array();
			$t_query = "SELECT DISTINCT version_id FROM $t_status_table";
			$t_result = db_query_bound( $t_query );
			while( $t_row = db_fetch_array( $t_result ) ) {
				$t_versions[ $t_row['version_id'] ] = true;
			}

			foreach( $t_products as $t_product ) {
				foreach( $t_product->versions as $t_id => $t_version ) {
					if ( isset( $t_versions[ $t_id ] ) ) {
						$s_options[ $t_id ] = $t_product->name . ' ' . $t_version->name;
					}
				}
			}
		}

		return $s_options;
	}
}

class PVMStatusFilter extends MantisFilter {
	public $field = 'status';
	public $title = 'Product Version Status';
	public $type = FILTER_TYPE_MULTI_INT;
	public $default = array();

	private $status_array;

	public function __construct() {
		plugin_push_current( 'ProductMatrix' );
		$this->status_array = plugin_config_get( 'status' );
		plugin_pop_current();
	}

	public function query( $p_filter_input ) {
		if ( !is_array( $p_filter_input ) ) {
			return;
		}

		$t_statuses = array();

		foreach( $p_filter_input as $t_status ) {
			if ( isset( $this->status_array[ $t_status ] ) ) {
				$t_statuses[] = $t_status;
			}
		}

		if ( count( $t_statuses ) < 1 ) {
			return;
		}

		$t_statuses = join( ',', $t_statuses );

		$t_bug_table = db_get_table( 'mantis_bug_table' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = array(
			'join' => "LEFT JOIN $t_status_table ON $t_bug_table.id=$t_status_table.bug_id",
			'where' => "$t_status_table.status IN ( $t_statuses )",
		);

		return $t_query;
	}

	public function display( $p_filter_value ) {
		if ( isset( $this->status_array[ $p_filter_value ] ) ) {
			return $this->status_array[ $p_filter_value ];
		}

		return $p_filter_value;
	}

	public function options() {
		return $this->status_array;
	}
}

class PVMStatusColumnFilter extends MantisFilter {
	public $field = 'statuscolumn';
	public $title = 'Version Status Column';
	public $type = FILTER_TYPE_MULTI_INT;
	public $default = array();

	public static function inputs( $p_inputs=null ) {
		static $s_inputs = array();

		if ( func_num_args() ) {
			$s_inputs = $p_inputs;
		} else {
			return $s_inputs;
		}
	}

	public function validate( $p_filter_input ) {
		self::inputs( $p_filter_input );

		$t_column_names = array();
		foreach( $p_filter_input as $t_input ) {
			$t_id = (int) $t_input;
			if ( $t_id < 1 ) {
				continue;
			}

			$t_column_names[] = 'productmatrix_status' . $t_id;
		}

		if ( count( $t_column_names ) > 0 ) {
			$t_project_id = helper_get_current_project();
			$t_user_id = auth_get_current_user_id();
			$t_user_columns = config_get( 'view_issues_page_columns', $t_project_id, $t_user_id );
			$t_user_columns = array_unique( array_merge( $t_user_columns, $t_column_names ) );
			config_set_cache( 'view_issues_page_columns', serialize($t_user_columns), CONFIG_TYPE_COMPLEX, $t_user_id, $t_project_id );
		}

		return true;
	}

	public function query( $p_filter_input ) {
	}

	public function display( $p_filter_value ) {
		$t_options = $this->options();

		if ( isset( $t_options[ $p_filter_value ] ) ) {
			return $t_options[ $p_filter_value ];
		}

		return $p_filter_value;
	}

	public function options() {
		static $s_options = null;

		if ( is_null( $s_options ) ) {
			$t_products = PVMProduct::load_all( true );
			$s_options = array();

			$t_status_table = plugin_table( 'status', 'ProductMatrix' );

			$t_versions = array();
			$t_query = "SELECT DISTINCT version_id FROM $t_status_table";
			$t_result = db_query_bound( $t_query );
			while( $t_row = db_fetch_array( $t_result ) ) {
				$t_versions[ $t_row['version_id'] ] = true;
			}

			foreach( $t_products as $t_product ) {
				foreach( $t_product->versions as $t_id => $t_version ) {
					if ( isset( $t_versions[ $t_id ] ) ) {
						$s_options[ $t_id ] = $t_product->name . ' ' . $t_version->name;
					}
				}
			}
		}

		return $s_options;
	}
}

