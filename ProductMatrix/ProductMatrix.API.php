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

class PVMProduct {
	var $id;
	var $name;
	var $versions = array();

	function __construct( $p_name ) {
		$this->id = 0;
		$this->name = $p_name;
	}

	function save() {
		if ( is_blank( $this->name ) ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_product_table ( name ) VALUES (" .
				db_param() . ')';
			db_query_bound( $t_query, array( $this->name ) );

			$this->id = db_insert_id( $t_product_table );

		} else { #update
			$t_query = "UPDATE $t_product_table SET name=" . db_param() .
				'WHERE id=' . db_param();
			db_query_bound( $t_query, array( $this->name, $this->id ) );
		}

		foreach( $this->versions as $t_version ) {
			if ( 0 == $t_version->product_id ) {
				$t_version->product_id = $this->id;
			}

			$t_version->save();
		}
	}

	function load_versions() {
		if ( 0 == $this->id ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$this->versions = PVMVersion::load_by_product( $this->id );
	}

	static function load( $p_id, $p_load_versions=true ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_product = new PVMProduct( $t_row['name'] );
		$t_product->id = $t_row['id'];

		if ( $p_load_versions ) {
			$this->load_versions();
		}

		return $t_product;
	}

	static function delete( $p_id ) {
		PVMVersion::delete_by_product( $p_id );

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_product_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}
}

class PVMVersion {
	var $id;
	var $product_id;
	var $name;

	function __construct( $p_product_id, $p_name ) {
		$this->id = 0;
		$this->product_id = $p_product_id;
		$this->name = $p_name;
	}

	function save() {
		if ( is_blank( $this->name ) ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_version_table ( product_id, name ) VALUES (" .
				db_param() . ',' . db_param() . ')';
			db_query_bound( $t_query, array( $this->product_id, $this->name ) );

			$this->id = db_insert_id( $t_version_table );

		} else { #update
			$t_query = "UPDATE $t_version_table SET product_id=" . db_param() .
				' name=' . db_param() . 'WHERE id=' . db_param();
			db_query_bound( $t_query, array( $this->product_id, $this->name, $this->id ) );
		}
	}

	static function load( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			trigger_error( ERROR_GENERIC, ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'] );
		$t_version->id = $t_row['id'];

		return $t_version;
	}

	static function load_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE product_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_versions = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'] );
			$t_version->id = $t_row['id'];

			$t_versions[] = $t_version;
		}

		return $t_versions;
	}

	static function delete( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_version_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

	static function delete_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_version_table WHERE product_id=" . db_param();
		db_query_bound( $t_query, array( $p_product_id ) );
	}
}

class ProductMatrix {
	var $bug_id;
	var $matrix = array();

	function __construct( $p_bug_id ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = "SELECT *, v.name AS vname, p.name AS pname
			FROM $t_status_table AS s
			JOIN $t_version_table AS v ON v.id=s.version_id
			JOIN $t_product_table AS p ON p.id=v.product_id
			WHERE s.bug_id=" . db_param();

		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			var_dump( $t_row );
		}
	}
}

