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
			plugin_error( 'ProductNameEmpty', ERROR );
		}

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_product_table ( name ) VALUES (" .
				db_param() . ')';
			db_query_bound( $t_query, array( $this->name ) );

			$this->id = db_insert_id( $t_product_table );

		} else { #update
			$t_query = "UPDATE $t_product_table SET name=" . db_param() .
				' WHERE id=' . db_param();
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
			plugin_error( 'ProductIDNotSet', ERROR );
		}

		$this->versions = PVMVersion::load_by_product( $this->id );
	}

	static function load( $p_id, $p_load_versions=true ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'ProductNotFound', ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_product = new PVMProduct( $t_row['name'] );
		$t_product->id = $t_row['id'];

		if ( $p_load_versions ) {
			$t_product->load_versions();
		}

		return $t_product;
	}

	static function load_all( $p_load_versions=false ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table";
		$t_result = db_query_bound( $t_query );

		$t_products = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_product = new PVMProduct( $t_row['name'] );
			$t_product->id = $t_row['id'];

			if ( $p_load_versions ) {
				$t_product->load_versions();
			}

			$t_products[ $t_product->id ] = $t_product;
		}

		return $t_products;
	}

	static function load_by_version_ids( $p_version_ids, $p_load_all_versions=false ) {
		if ( !is_array( $p_version_ids ) ) {
			if ( !is_numeric( $p_version_ids ) && is_blank( $p_version_ids ) ) {
				return null;
			}

			$p_version_ids = array( $p_version_ids );
		} else {
			if ( count( $p_version_ids ) < 1 ) {
				return null;
			}
		}

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_version_list = join( ',', $p_version_ids );

		$t_query = "SELECT * FROM $t_product_table WHERE id IN ( " . db_param() . ' ) ORDER BY name ASC';
		$t_result = db_query_bound( $t_query, $t_version_list );

		$t_products = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_product = new PVMProduct( $t_row['name'] );
			$t_product->id = $t_row['id'];

			# TODO: Replace this with code to only load the necessary version objects in a single query,
			# rather than loading them in one query per-product and them deleting unneeded ones later.
			$t_product->load_versions();

			if ( !$p_load_all_versions ) {
				$t_product_version_ids = array_keys( $t_product->versions );
				foreach( $t_product_version_ids as $t_version_id ) {
					if ( !in_array( $t_version_id, $p_version_ids ) ) {
						unset( $t_product->versions[ $t_version_id ] );
					}
				}
			}

			$t_products[$t_product->id] = $t_product;
		}

		return $t_products;
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
			plugin_error( 'VersionNameEmpty', ERROR );
		}

		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_version_table ( product_id, name ) VALUES (" .
				db_param() . ',' . db_param() . ')';
			db_query_bound( $t_query, array( $this->product_id, $this->name ) );

			$this->id = db_insert_id( $t_version_table );

		} else { #update
			$t_query = "UPDATE $t_version_table SET product_id=" . db_param() .
				', name=' . db_param() . ' WHERE id=' . db_param();
			db_query_bound( $t_query, array( $this->product_id, $this->name, $this->id ) );
		}
	}

	static function load( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'VersionNotFound', ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'] );
		$t_version->id = $t_row['id'];

		return $t_version;
	}

	static function load_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE product_id=" . db_param() . ' ORDER BY name ASC';
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_versions = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'] );
			$t_version->id = $t_row['id'];

			$t_versions[$t_version->id] = $t_version;
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
	var $products = array();

	function __construct( $p_bug_id ) {
		$this->bug_id = $p_bug_id;

		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_status_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->matrix[ $t_row['version_id'] ] = $t_row['status'];
		}

		$t_version_ids = array_keys( $this->matrix );
		$this->products = PVMProduct::load_by_version_ids( $t_version_ids );
	}
}

