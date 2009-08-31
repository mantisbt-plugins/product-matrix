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

		if ( is_null( $s_products ) ) {
			$t_products = PVMProduct::load_all( true );
			$s_options = array();

			foreach( $t_products as $t_product ) {
				foreach( $t_product->versions as $t_id => $t_version ) {
					$s_options[ $t_id ] = $t_product->name . $t_version->name;
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

