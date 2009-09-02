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

/**
 * Product Version Matrix API
 * @author John Reese
 */

/**
 * Natural sorting comparison for objects with 'name' properties.
 * @param object Object 1
 * @param object Object 2
 * @return int Standard comparison number
 */
function PVMNaturalSort( $p_object_1, $p_object_2 ) {
	return strnatcmp( $p_object_1->name, $p_object_2->name );
}

/**
 * Reverse natural sorting comparison for objects with 'name' properties.
 * @param object Object 1
 * @param object Object 2
 * @return int Standard comparison number
 */
function PVMNaturalSortReverse( $p_object_1, $p_object_2 ) {
	return strnatcmp( $p_object_2->name, $p_object_1->name );
}

/**
 * Reimplementation of the DATETIME column handling that has been
 * removed from MantisBT 1.2.x.
 */
function PVMDBDate( $p_date=null, $p_gmt=false ) {
	global $g_db;

	if( null !== $p_date ) {
		$p_timestamp = $g_db->UnixTimeStamp( $p_date, $p_gmt );
	} else {
		$p_timestamp = time();
	}
	return $g_db->BindTimeStamp( $p_timestamp );
}


/**
 * Object representation of a product.
 * A product can contain a list of platforms, and a hierarchical
 * list of versions.
 */
class PVMProduct {
	var $id;
	var $name;
	var $platforms = array();
	var $versions = array();
	var $version_tree = array();

	/**
	 * Initialize a product object.
	 * @param string Product name
	 */
	function __construct( $p_name ) {
		$this->id = 0;
		$this->name = $p_name;
	}

	/**
	 * Save a product to the database, and recursively save child
	 * platforms and versions.
	 */
	function save() {
		if ( is_blank( $this->name ) ) {
			plugin_error( 'ProductNameEmpty', ERROR, 'ProductMatrix' );
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

		foreach( $this->platforms as $t_platform ) {
			if ( 0 == $t_platform->product_id ) {
				$t_platform->product_id = $this->id;
			}

			$t_platform->save();
		}
	}

	/**
	 * Load child versions and build a version tree.
	 */
	function load_versions() {
		if ( 0 == $this->id ) {
			plugin_error( 'ProductIDNotSet', ERROR, 'ProductMatrix' );
		}

		$this->versions = PVMVersion::load_by_product( $this->id );
		$this->build_version_tree();
	}

	/**
	 * Build a hierarchical version tree of loaded versions.
	 */
	function build_version_tree() {
		foreach( $this->versions as $t_version ) {
			$t_parent_id = $t_version->parent_id;
			if ( !isset( $this->version_tree[ $t_parent_id ] ) ) {
				$this->version_tree[ $t_parent_id ] = array();
			}

			$this->version_tree[ $t_parent_id ][ $t_version->id ] = $t_version;
		}
	}

	/**
	 * Build and return a full tree of loaded versions including recursed children.
	 */
	function full_version_tree() {
		if ( !isset( $this->__full_version_tree ) ) {
			$this->__full_version_tree = array();

			foreach( $this->versions as $t_version ) {
				$t_child_version_ids = array();
				$t_queue = array( $t_version->id );

				while( $t_next = array_shift( $t_queue ) ) {
					if ( is_null( $t_next ) ) {
						break;
					}

					if ( !isset( $this->version_tree[ $t_next ] ) ) {
						continue;
					}

					$t_child_versions = $this->version_tree[ $t_next ];

					foreach( $t_child_versions as $t_child_version ) {
						$t_child_version_ids[] = $t_child_version->id;
						$t_queue[] = $t_child_version->id;
					}
				}

				$this->__full_version_tree[ $t_version->id ] = $t_child_version_ids;
			}
		}

		return $this->__full_version_tree;
	}

	/**
	 * Recursively generate a flat list of version/depth pairs
	 * for representing the hierarchical structure for display.
	 * @return array Array of version/depth pairs
	 */
	function version_tree_list() {
		if ( !isset( $this->__version_tree_list ) ) {
			$this->__version_tree_list = $this->version_tree_section( $this->version_tree[0] );
		}
		return $this->__version_tree_list;
	}

	/**
	 * Recursive portion of version_tree_list().
	 * @param array Version tree
	 * @return array Array of version/depth pairs
	 */
	private function version_tree_section( $t_versions, $t_depth=0 ) {
		if ( !is_array( $t_versions ) || count( $t_versions ) < 1 ) {
			return array();
		}

		$t_list = array();
		foreach( $t_versions as $t_version_id => $t_version ) {
			$t_list[] = array( $t_version, $t_depth );
			if ( isset( $this->version_tree[ $t_version_id ] ) ) {
				$t_list = array_merge( $t_list, $this->version_tree_section( $this->version_tree[ $t_version_id ], $t_depth+1 ) );
			}
		}

		return $t_list;
	}

	/**
	 * Load a product object from the database.
	 * @param int Product ID
	 * @param boolean Load child versions
	 * @return object Product object
	 */
	static function load( $p_id, $p_load_versions=true ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'ProductNotFound', ERROR, 'ProductMatrix' );
		}

		$t_row = db_fetch_array( $t_result );

		$t_product = new PVMProduct( $t_row['name'] );
		$t_product->id = $t_row['id'];

		$t_product->platforms = PVMPlatform::load_by_product( $t_product->id );

		if ( $p_load_versions ) {
			$t_product->load_versions();
		}

		return $t_product;
	}

	/**
	 * Load all product objects from the database.
	 * @param boolean Load child versions
	 * @return array Product objects
	 */
	static function load_all( $p_load_versions=false ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table";
		$t_result = db_query_bound( $t_query );

		$t_products = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_product = new PVMProduct( $t_row['name'] );
			$t_product->id = $t_row['id'];

			$t_product->platforms = PVMPlatform::load_by_product( $t_product->id );

			if ( $p_load_versions ) {
				$t_product->load_versions();
			}

			$t_products[ $t_product->id ] = $t_product;
		}

		uasort( $t_products, 'PVMNaturalSort' );

		return $t_products;
	}

	/**
	 * Load all product objects from the database that have child
	 * versions from a given list.
	 * @param array Version IDs
	 * @return array Product objects
	 */
	static function load_by_version_ids( $p_version_ids ) {
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
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_version_ids = array();
		foreach( $p_version_ids as $t_version_id ) {
			if ( is_numeric( $t_version_id ) ) {
				$t_version_ids[] = $t_version_id;
			}
		}

		$t_version_list = join( ', ', $t_version_ids );

		$t_query = "SELECT DISTINCT( p.id ), p.name FROM $t_product_table AS p
			JOIN $t_version_table AS v ON p.id=v.product_id
			WHERE v.id IN ( $t_version_list )";
		$t_result = db_query_bound( $t_query );

		$t_products = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_product = new PVMProduct( $t_row['name'] );
			$t_product->id = $t_row['id'];

			$t_product->platforms = PVMPlatform::load_by_product( $t_product->id );
			$t_product->load_versions();
			$t_products[$t_product->id] = $t_product;
		}

		uasort( $t_products, 'PVMNaturalSort' );

		return $t_products;
	}

	/**
	 * Delete a product object from the database.
	 * @param int Product ID
	 */
	static function delete( $p_id ) {
		PVMPlatform::delete_by_product( $p_id );
		PVMVersion::delete_by_product( $p_id );

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_product_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

	/**
	 * Display <option> tags for a dropdown list using all
	 * of the product's child versions.
	 * @param int Selected child version ID
	 */
	function select_versions( $p_default_id=0 ) {
		echo '<option value="0">--</option>';
		foreach( $this->version_tree_list() as $t_node ) {
			list( $t_version, $t_depth ) = $t_node;

			echo '<option value="', $t_version->id, '" ',
				( $t_version->id == $p_default_id ? 'selected="selected" ' : '' ),
				'>', str_pad( ' ', $t_depth+1, '-', STR_PAD_LEFT ), $t_version->name, '</option>';
		}
	}

	/**
	 * Display <option> tags for a dropdown list using all
	 * of the version's child versions.
	 * @param int Parent version ID
	 * @param int Selected child version ID
	 */
	function select_child_versions( $p_version_id, $p_default_id=0 ) {
		echo '<option value="0">auto</option>';
		foreach( $this->version_tree[ $p_version_id ] as $t_version ) {
			echo '<option value="', $t_version->id, '" ',
				( $t_version->id == $p_default_id ? 'selected="selected" ' : '' ),
				'>', $t_version->name, '</option>';
		}
	}
}

/**
 * Object representation of a product platform.
 */
class PVMPlatform {
	var $id;
	var $product_id;
	var $name;
	var $obsolete;

	/**
	 * Initialize a platform object.
	 * @param int Product ID
	 * @param string Platform name
	 */
	function __construct( $p_product_id, $p_name ) {
		$this->id = 0;
		$this->product_id = $p_product_id;
		$this->name = $p_name;
		$this->obsolete = false;
	}

	/**
	 * Save a platform object to the database.
	 */
	function save() {
		if ( is_blank( $this->name ) ) {
			plugin_error( 'PlatformNameEmpty', ERROR, 'ProductMatrix' );
		}

		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_platform_table (
					product_id,
					name,
					obsolete
				) VALUES (" .
					db_param() . ',' .
					db_param() . ',' .
					db_param() .
				')';
			db_query_bound( $t_query, array(
				$this->product_id,
				$this->name,
				$this->obsolete
			) );

			$this->id = db_insert_id( $t_platform_table );

		} else { #update
			$t_query = "UPDATE $t_platform_table SET
					product_id=" . db_param() . ',
					name=' . db_param() . ',
					obsolete=' . db_param() . '
				WHERE id=' . db_param();
			db_query_bound( $t_query, array(
				$this->product_id,
				$this->name,
				$this->obsolete,
				$this->id
			) );
		}
	}

	/**
	 * Load a platform object from the database.
	 * @param int Platform ID
	 * @return object Platform object
	 */
	static function load( $p_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_platform_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'PlatformNotFound', ERROR, 'ProductMatrix' );
		}

		$t_row = db_fetch_array( $t_result );

		$t_platform = new PVMPlatform( $t_row['product_id'], $t_row['name'] );
		$t_platform->id = $t_row['id'];
		$t_platform->obsolete = $t_row['obsolete'];

		return $t_platform;
	}

	/**
	 * Load all platform objects from the database associated
	 * with the given product ID.
	 * @param int Product ID
	 * @return array Platform objects
	 */
	static function load_by_product( $p_product_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_platform_table WHERE product_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_platforms = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_platform = new PVMPlatform( $t_row['product_id'], $t_row['name'] );
			$t_platform->id = $t_row['id'];
			$t_platform->obsolete = $t_row['obsolete'];

			$t_platforms[$t_platform->id] = $t_platform;
		}

		uasort( $t_platforms, 'PVMNaturalSort' );

		return $t_platforms;
	}

	/**
	 * Delete a platform object from the database.
	 * @param int Platform ID
	 */
	static function delete( $p_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_affects_table WHERE platform_id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );

		$t_query = "DELETE FROM $t_platform_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

	/**
	 * Delete all platform object from the database associated
	 * with the given product ID.
	 * @param int Product ID
	 */
	static function delete_by_product( $p_product_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$t_product = PVMProduct::load( $p_product_id, true );
		$t_platform_ids = array_keys( $t_product->platforms );

		$t_query = "DELETE FROM $t_affects_table WHERE platform_id IN (" .
			join( ',', $t_platform_ids ) . ' )';
		db_query_bound( $t_query );

		$t_query = "DELETE FROM $t_platform_table WHERE product_id=" . db_param();
		db_query_bound( $t_query, array( $p_product_id ) );
	}
}

/**
 * Object represontation of a product version.
 */
class PVMVersion {
	var $id;
	var $parent_id = 0;
	var $inherit_id = 0;
	var $product_id;
	var $name;
	var $date;
	var $released = false;
	var $obsolete = false;

	var $migrate_id = 0;

	/**
	 * Initialize a version object.
	 * @param int Product ID
	 * @param string Version name
	 * @param int Parent version ID
	 */
	function __construct( $p_product_id, $p_name, $p_parent_id=0, $p_migrate_id=0 ) {
		$this->id = 0;
		$this->parent_id = $p_parent_id;
		$this->product_id = $p_product_id;
		$this->name = $p_name;
		$this->date = date( 'Y-m-d' );
		$this->migrate_id = $p_migrate_id;
	}

	/**
	 * Save a version to the database.
	 */
	function save() {
		if ( is_blank( $this->name ) ) {
			plugin_error( 'VersionNameEmpty', ERROR, 'ProductMatrix' );
		}

		$t_version_table = plugin_table( 'version', 'ProductMatrix' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_version_table (
					parent_id,
					inherit_id,
					product_id,
					name,
					date,
					released,
					obsolete
				) VALUES (" .
					db_param() . ',' .
					db_param() . ',' .
					db_param() . ',' .
					db_param() . ',' .
					db_param() . ',' .
					db_param() . ',' .
					db_param() .
				')';
			db_query_bound( $t_query, array(
				$this->parent_id,
				$this->inherit_id,
				$this->product_id,
				$this->name,
				PVMDBDate( $this->date ),
				db_prepare_bool( $this->released ),
				db_prepare_bool( $this->obsolete ),
			) );

			$this->id = db_insert_id( $t_version_table );

			# handle migrating existing version statuses to the new version
			if ( $this->migrate_id ) {
				$t_query = "INSERT INTO $t_status_table ( bug_id, version_id, status )
					SELECT bug_id, " . db_param() . ", status
					FROM $t_status_table WHERE version_id=" . db_param();
				db_query_bound( $t_query, array( $this->id, $this->migrate_id ) );
			}

		} else { #update
			$t_query = "UPDATE $t_version_table SET
					parent_id=" . db_param() . ',
					inherit_id=' . db_param() . ',
					product_id=' . db_param() . ',
					name=' . db_param() . ',
					date=' . db_param() . ',
					released=' . db_param() . ',
					obsolete=' . db_param() . '
				WHERE id=' . db_param();
			db_query_bound( $t_query, array(
				$this->parent_id,
				$this->inherit_id,
				$this->product_id,
				$this->name,
				PVMDBDate( $this->date ),
				db_prepare_bool( $this->released ),
				db_prepare_bool( $this->obsolete ),
				$this->id
			) );
		}
	}

	/**
	 * Load a version object from the database.
	 * @param int Version ID
	 * @return object Version object
	 */
	static function load( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'VersionNotFound', ERROR, 'ProductMatrix' );
		}

		$t_row = db_fetch_array( $t_result );

		$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'], $t_row['parent_id'] );
		$t_version->id = $t_row['id'];
		$t_version->inherit_id = $t_row['inherit_id'];
		$t_version->date = $t_row['date'];
		$t_version->released = $t_row['released'];
		$t_version->obsolete = $t_row['obsolete'];

		return $t_version;
	}

	/**
	 * Load all version objects from the database associated
	 * with the given product ID.
	 * @param int Product ID
	 * @return array Version objects
	 */
	static function load_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE product_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_versions = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'], $t_row['parent_id'] );
			$t_version->id = $t_row['id'];
			$t_version->inherit_id = $t_row['inherit_id'];
			$t_version->date = $t_row['date'];
			$t_version->released = $t_row['released'];
			$t_version->obsolete = $t_row['obsolete'];

			$t_versions[$t_version->id] = $t_version;
		}

		uasort( $t_versions, 'PVMNaturalSort' );

		return $t_versions;
	}

	/**
	 * Delete a version object from the database.
	 * @param int Version ID
	 */
	static function delete( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_status_table WHERE version_id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );

		$t_query = "DELETE FROM $t_version_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

	/**
	 * Delete all version objects from the database associated
	 * with the given product ID.
	 * @param int Product ID
	 */
	static function delete_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_product = PVMProduct::load( $p_product_id, true );
		$t_version_ids = array_keys( $t_product->versions );

		$t_query = "DELETE FROM $t_status_table WHERE version_id IN (" .
			join( ',', $t_version_ids ) . ' )';
		db_query_bound( $t_query );

		$t_query = "DELETE FROM $t_version_table WHERE product_id=" . db_param();
		db_query_bound( $t_query, array( $p_product_id ) );
	}
}

/**
 * Object representation of all product, platform, and version objects
 * affected by an issue, and the bug status for each version.
 */
class ProductMatrix {
	var $bug_id;
	var $status;
	var $__status;
	var $affects;
	var $__affects;
	var $products;

	var $reverse_inheritence;

	/**
	 * Initialize a matrix object.
	 * @param int Bug ID
	 * @param boolean Load product objects
	 */
	function __construct( $p_bug_id=0, $p_load_products=true ) {
		plugin_push_current( 'ProductMatrix' );

		$this->bug_id = $p_bug_id;
		$this->__status = array();
		$this->status = array();
		$this->__affects = array();
		$this->affects = array();
		$this->products = array();

		$this->reverse_inheritence = plugin_config_get( 'reverse_inheritence' );

		if ( $p_load_products ) {
			$this->load_products( true );
		}

		if ( !$p_bug_id ) {
			plugin_pop_current();
			return;
		}

		$t_status_table = plugin_table( 'status' );
		$t_affects_table = plugin_table( 'affects' );

		$t_query = "SELECT * FROM $t_status_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->status[ $t_row['version_id'] ] = $t_row['status'];
			$this->__status[ $t_row['version_id'] ] = $t_row['status'];
		}

		$t_query = "SELECT * FROM $t_affects_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->affects[ $t_row['product_id'] ][ $t_row['platform_id'] ] = true;
			$this->__affects[ $t_row['product_id'] ][ $t_row['platform_id'] ] = true;
		}

		plugin_pop_current();
	}

	/**
	 * Save a matrix object to the database.
	 * Log history entries for any affected platform or version status
	 * that has changed state since the matrix was last saved.
	 */
	function save() {
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$this->load_products( true );
		$this->products_to_versions();

		foreach( $this->status as $t_version_id => $t_status ) {
			if ( !isset( $this->__status[ $t_version_id ] ) ) { # new status
				$t_query = "INSERT INTO $t_status_table ( bug_id, version_id, status )
					VALUES ( " . join( ',', array( db_param(), db_param(), db_param() ) ) . ' )';
				db_query_bound( $t_query, array( $this->bug_id, $t_version_id, $t_status ) );

				$this->history_log_version( $t_version_id, null, $t_status );
				$this->__status[ $t_version_id ] = $t_status;

			} else if ( is_null( $t_status ) ) { # deleted status
				$t_query = "DELETE FROM $t_status_table WHERE bug_id=" . db_param() . ' AND version_id=' . db_param();
				db_query_bound( $t_query, array( $this->bug_id, $t_version_id ) );

				$this->history_log_version( $t_version_id, $this->__status[ $t_version_id ], null );
				unset( $this->status[ $t_version_id ] );
				unset( $this->__status[ $t_version_id ] );

			} else if ( $t_status != $this->__status[ $t_version_id ] ) { # updated status
				$t_query = "UPDATE $t_status_table SET status=" . db_param() .
					' WHERE bug_id=' . db_param() . ' AND version_id=' . db_param();
				db_query_bound( $t_query, array( $t_status, $this->bug_id, $t_version_id ) );

				$this->history_log_version( $t_version_id, $this->__status[ $t_version_id ], $t_status );
				$this->__status[ $t_version_id ] = $t_status;
			}
		}

		foreach( $this->affects as $t_product_id => $t_platforms ) {
			foreach( $t_platforms as $t_platform_id => $t_affected ) {
				if ( !isset( $this->__affects[ $t_product_id ][ $t_platform_id ] ) ) { # new platform
					$t_query = "INSERT INTO $t_affects_table ( bug_id, product_id, platform_id )
						VALUES ( " . join( ',', array( db_param(), db_param(), db_param() ) ) . ' )';
					db_query_bound( $t_query, array( $this->bug_id, $t_product_id, $t_platform_id ) );

					$this->history_log_platform( $t_product_id, $t_platform_id, true );
					$this->__affects[ $t_product_id ][ $t_platform_id ] = true;

				} else if ( false == $t_affected ) { # removed platform
					$t_query = "DELETE FROM $t_affects_table WHERE bug_id=" . db_param() .
						' AND product_id=' . db_param() . ' AND platform_id=' . db_param();
					db_query_bound( $t_query, array( $this->bug_id, $t_product_id, $t_platform_id ) );

					$this->history_log_platform( $t_product_id, $t_platform_id, false );
					unset( $this->affects[ $t_product_id ][ $t_platform_id ] );
					unset( $this->__affects[ $t_product_id ][ $t_platform_id ] );
				}
			}
		}
	}

	/**
	 * Load product objects for all versions affected by the bug.
	 * @param boolean Load all products
	 */
	function load_products( $p_load_all=false ) {
		if ( $p_load_all ) {
			$this->products = PVMProduct::load_all( true );
		} else {
			$t_version_ids = array_keys( $this->status );
			$this->products = PVMProduct::load_by_version_ids( $t_version_ids );
		}
	}

	/**
	 * Create a reverse-association of version ID to products.
	 */
	function products_to_versions() {
		if ( isset( $this->versions ) ) {
			return;
		}

		$this->versions = array();
		foreach( $this->products as $t_product ) {
			foreach( $t_product->versions as $t_version ) {
				$this->versions[ $t_version->id ] = $t_product;
			}
		}
	}

	/**
	 * Log an affected platform change to the bug history.
	 */
	function history_log_platform( $t_product_id, $t_platform_id, $t_affected ) {
		plugin_push_current( 'ProductMatrix' );

		$t_product_name = $this->products[ $t_product_id ]->name;
		if ( $t_platform_id > 0 ) {
			$t_platform_name = $this->products[ $t_product_id ]->platforms[ $t_platform_id ]->name;
		} else {
			$t_platform_name = plugin_lang_get( 'common' );
		}

		$t_history_string = "$t_product_name: $t_platform_name";

		if ( $t_affected ) {
			$t_field = 'history_platform_affected';
		} else {
			$t_field = 'history_platform_unaffected';
		}

		plugin_history_log( $this->bug_id, $t_field, $t_history_string );

		plugin_pop_current();
	}

	/**
	 * Log an affected version status change to the bug history.
	 */
	function history_log_version( $t_version_id, $t_old, $t_new ) {
		plugin_push_current( 'ProductMatrix' );

		$t_status = plugin_config_get( 'status' );

		$t_product_name = $this->versions[ $t_version_id ]->name;
		$t_version_name = $this->versions[ $t_version_id ]->versions[ $t_version_id ]->name;

		$t_history_string = "$t_product_name $t_version_name: ";

		if ( is_null( $t_old ) ) {
			$t_field = 'history_version_tracked';

			$t_old = $t_history_string . $t_status[ $t_new ];
			$t_new = null;
		} else if ( is_null( $t_new ) ) {
			$t_field = 'history_version_ignored';

			$t_old = $t_history_string . $t_status[ $t_old ];
			$t_new = null;
		} else {
			$t_field = 'history_version_updated';

			$t_old = $t_history_string . $t_status[ $t_old ];
			$t_new = $t_status[ $t_new ];
		}

		plugin_history_log( $this->bug_id, $t_field, $t_old, $t_new );

		plugin_pop_current();
	}

	/**
	 * Prune unused platforms and versions from products in the matrix.
	 */
	function prune() {
		$t_version_ids = array_keys( $this->status );

		foreach( $this->products as $t_product ) {
			$t_platform_ids = isset( $this->affects[ $t_product->id ] )
				? array_keys( $this->affects[ $t_product->id ] ) : array();
			$t_product_platform_ids = array_keys( $t_product->platforms );
			foreach( $t_product_platform_ids as $t_platform_id ) {
				if ( !in_array( $t_platform_id, $t_platform_ids ) ) {
					unset( $t_product->platforms[ $t_platform_id ] );
				}
			}

			$t_product_version_ids = array_keys( $t_product->versions );
			foreach( $t_product_version_ids as $t_version_id ) {
				if ( !in_array( $t_version_id, $t_version_ids ) ) {
					unset( $t_product->versions[ $t_version_id ] );
				}
			}
		}
	}


	/**
	 * Returns the top level status for a given product
	 *
	 * @param int Product ID
	 * @return string top level status
	 */
	function product_status( $p_product_id ){
		if ( is_null( $this->versions ) ) {
			$this->products_to_versions();
		}

		$t_versions = $this->products[ $p_product_id ]->version_tree[0];
		$t_status = 0;

		while( count( $t_versions ) && !$t_status ) {
			$t_version = array_pop( $t_versions );
			$t_status = $this->version_status( $t_version->id );
		}

		return $t_status;
	}

	/**
	 * Determine a version's status in the matrix, using reverse inheritence if needed.
	 */
	function version_status( $p_version_id ) {
		# return status immediatly if no inheritence in use
		if ( !$this->reverse_inheritence || isset( $this->status[ $p_version_id ] ) ) {
			return $this->status[ $p_version_id ];
		}

		# handle dead versions
		if ( !isset( $this->versions[ $p_version_id ] ) ) {
			return null;
		}

		# get product/version info
		$t_product = $this->versions[ $p_version_id ];
		$t_inherit_id = $t_product->versions[ $p_version_id ]->inherit_id;

		# see if the version has any children
		if ( isset( $t_product->version_tree[ $p_version_id ] ) ) {
			$t_version_tree = $t_product->version_tree[ $p_version_id ];

			# recurse the version tree, either by specific inherit_id or latest by name
			if ( count( $t_version_tree ) > 0 ) {
				if ( $t_inherit_id && isset( $t_version_tree[ $t_inherit_id ] ) ) {
					$t_inherited_status = $this->version_status( $t_inherit_id );
					if ( !is_null( $t_inherited_status ) ) {
						return $t_inherited_status;
					}
				}

				# sort children by version name, and find the first with a real status to inherit from
				uasort( $t_version_tree, 'PVMNaturalSortReverse' );

				$t_child_status = 0;
				foreach( $t_version_tree as $t_child_id => $t_child ) {
					$t_child_status = $this->version_status( $t_child_id );
					if ( $t_child_status ) break;
				}

				return $t_child_status;
			}
		}

		# no children, return current status
		return $this->status[ $p_version_id ];
	}

	/**
	 * Determine if a version status can be changed, adhering to inheritence if enabled.
	 * @param int Version ID
	 * @return boolean True if version status is mutable
	 */
	function version_mutable( $p_version_id ) {
		# without inheritence, all versions are valid to set
		if ( !$this->reverse_inheritence || isset( $this->status[ $p_version_id ] ) ) {
			return true;
		}

		# see if the version has any children
		$t_product = $this->versions[ $p_version_id ];
		if ( isset( $t_product->version_tree[ $p_version_id ] ) && count( $t_product->version_tree[ $p_version_id ] ) > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Return a list of child version ids for a given version ID.
	 * @param int Version ID
	 * @return array Child version IDs
	 */
	function version_child_ids( $p_version_id ) {
		$t_product = $this->versions[ $p_version_id ];

		if ( isset( $t_product->version_tree[ $p_version_id ] ) ) {
			$t_collapse_versions = $t_product->version_tree[ $p_version_id ];
			$t_collapse_ids = array();

			foreach( $t_collapse_versions as $t_collapse_version ) {
				$t_collapse_ids[] = $t_collapse_version->id;
			}

			return $t_collapse_ids;

		} else {
			return array();
		}
	}

	/**
	 * Display a tabular matrix of affected, products, platforms, and versions.
	 */
	function view() {
		if ( count( $this->status ) < 1 || count( $this->products ) < 1 ) {
			return null;
		}

		plugin_push_current( 'ProductMatrix' );

		$this->products_to_versions();
		$t_common_enabled = plugin_config_get( 'common_platform' );
		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );

		$t_version_count = 0;
		foreach( $this->products as $t_product ) {
			$t_product->__versions = array();
			$t_product->__status = array();

			foreach( $t_product->versions as $t_version ) {
				$t_status = $this->version_status( $t_version->id );

				if ( $t_status ) {
					$t_product->__versions[] = $t_version;
					$t_product->__status[ $t_version->id ] = $t_status;
				}
			}

			#Sets Product Top Level Status
			if( plugin_config_get( 'product_status' ) ){
				$t_product->top_status = $this->product_status( $t_product->id );
			}
		}


		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5"><div class="productmatrix">';

		foreach( $this->products as $t_product ) {
			if ( count( $t_product->__versions ) < 1 ) {
				continue;
			}

			echo '<table class="pvmproduct" cellspacing="0">',

				'<tr class="row-category">
					<td>', $t_product->name, '</td>
					<td bgcolor=', $t_status_colors[$t_product->top_status], '>', $t_status_array[$t_product->top_status], '</td>
				</tr>';

			foreach( $t_product->version_tree_list() as $t_node ) {
				list( $t_version, $t_depth ) = $t_node;
				$t_status = $t_product->__status[ $t_version->id ];

				if ( !$t_status ) {
					continue;
				}

				$t_collapse_ids = join( ':', $this->version_child_ids( $t_version->id ) );

				echo '<tr id="pvmversion', $t_version->id, '" class="pvmstatus ', $t_depth < 1 ? 'pvmtoplevel' : 'pvmchild',
					'" collapse="', $t_collapse_ids, '"><td class="category pvmdepth', $t_depth, '">', $t_version->name, '</td>',
					'</td><td bgcolor="', $t_status_colors[$t_status], '">', $t_status_array[$t_status], '</td></tr>';

			}

			if ( count( $t_product->platforms ) ) {
				$t_first = true;
				echo '<tr class="pvmaffected"><td class="category">Affects</td><td>';
				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo ', '; }
					echo $t_platform->name;
					$t_first = false;
				}
				if ( $t_common_enabled && isset( $this->affects[ $t_product->id ][0] ) ) {
					echo ', ', plugin_lang_get( 'common' );
				}
				echo '</td></tr>';
			}

			echo '</table>';
		}

		echo '</div></td></tr>';

		plugin_pop_current();
	}

	/**
	 * Display a form when updating bugs allowing the user to specify affected
	 * products, platforms, and version statuses for a bug.
	 */
	function view_form( $p_status_default=null ) {
		$t_products = PVMProduct::load_all( true );

		if ( count( $t_products ) < 1 ) {
			return null;
		}

		plugin_push_current( 'ProductMatrix' );

		$this->products_to_versions();
		$t_common_enabled = plugin_config_get( 'common_platform' );
		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );
		$t_status_default = $p_status_default === null ? 0 : $p_status_default;

		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5"><div class="productmatrix">',
			'<input type="hidden" name="ProductMatrix" value="1"/>';

		foreach( $this->products as $t_product ) {

			#Sets Product Top Level Status
			if( plugin_config_get( 'product_status' ) ){
				$t_product->top_status = $this->product_status( $t_product->id );
			}

			echo '<table class="pvmproduct" cellspacing="0">',
				'<tr class="row-category"><td>', $t_product->name, '</td>
				<td bgcolor=', $t_status_colors[$t_product->top_status], '>', $t_status_array[$t_product->top_status], '</td>
				</tr>';

			foreach( $t_product->version_tree_list() as $t_node ) {
				list( $t_version, $t_depth ) = $t_node;
				$t_status = $t_product->__status[ $t_version->id ];

				$t_collapse_ids = join( ':', $this->version_child_ids( $t_version->id ) );

				echo '<tr id="pvmversion', $t_version->id, '" class="pvmstatus ', $t_depth < 1 ? 'pvmtoplevel' : 'pvmchild',
					'" collapse="', $t_collapse_ids, '"><td class="category">', str_pad( '', $t_depth, '-' ), ' ', $t_version->name, '</td>';

				if ( $this->version_mutable( $t_version->id ) ) {
					if ( isset( $this->status[$t_version->id] ) ) {
						$t_status = $this->status[$t_version->id];
					} else {
						$t_status = $t_status_default;
					}

					echo '<td bgcolor="', $t_status_colors[$t_status], '"><select name="Product', $t_product->id, 'Version', $t_version->id, '">';

					$t_possible_workflow = $this->generate_possible_workflow( $t_status );

					if ( isset( $t_possible_workflow[0] ) ) {
						echo '<option value="0"', ( $t_status ? '' : ' selected="selected"' ), '>', plugin_lang_get( 'status_na' ), '</option>';
					}

					foreach( $t_possible_workflow as $t_status_value => $t_status_name ) {
						if ( $t_status_value < 1 ) { continue; }
						echo '<option value="', $t_status_value, '"',
							( $t_status == $t_status_value ? ' selected="selected"' : '' ),
							'>', $t_status_name, '</option>';
					}

					echo '</select></td>';

				} else {
					$t_status = $this->version_status( $t_version->id );

					if ( is_null( $t_status ) ) {
						if ( $t_status_default > 0 ) {
							echo '<td bgcolor="', $t_status_colors[$t_status_default],'">', $t_status_array[$t_status_default], '</td>';
						} else {
							echo '<td>', plugin_lang_get( 'status_na' ), '</td>';
						}
					} else {
						echo '<td bgcolor="', $t_status_colors[$t_status], '">', $t_status_array[$t_status], '</td>';
					}
				}

				echo '</tr>';
			}

			if ( count( $t_product->platforms ) ) {
				echo '<tr class="pvmaffected"><td class="category">Affects</td><td>';

				$t_first = true;
				$t_platform_temp_ids = array();

				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo '<br/>'; }
					echo '<label><input type="checkbox" id="ProductPlatform', $t_platform_temp_id,
						'" name="Product', $t_product->id, 'Platform' , $t_platform->id, '" ',
						( isset( $this->affects[ $t_product->id ][ $t_platform->id ] ) ? ' checked="checked"' : '' ), '/> ',
						$t_platform->name, '</label>';

					$t_first = false;
					$t_platform_temp_ids[] = $t_platform_temp_id;
					$t_platform_temp_id++;
				}

				if ( $t_common_enabled ) {
					$t_onclick = '';
					foreach( $t_platform_temp_ids as $t_temp_id ) {
						$t_onclick .= "document.getElementById('ProductPlatform$t_temp_id').checked = true;";
					}
					$t_onclick = "onclick=\" \n\nif ( this.checked ) {\n\t$t_onclick\n}\"";

					echo '<br/><label><input type="checkbox" name="Product', $t_product->id, 'Platform0" ', $t_onclick,
						( isset( $this->affects[ $t_product->id ][0] ) ? ' checked="checked"' : '' ), '/> ',
						plugin_lang_get( 'common' ), '</label>';
				}
				echo '</td></tr>';
			}

			echo '</table>';
		}

		echo '</div></td></tr>';

		plugin_pop_current();
	}

	/**
	 * Display a form when reporting new bugs allowing the user to specify affected
	 * products, platforms, and version statuses for a bug.
	 */
	function view_report_form( $p_status_default=null ) {
		$t_products = PVMProduct::load_all( true );

		if ( count( $t_products ) < 1 ) {
			return null;
		}

		plugin_push_current( 'ProductMatrix' );

		$this->products_to_versions();
		$t_common_enabled = plugin_config_get( 'common_platform' );
		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );
		$t_status_default = $p_status_default === null ? array_shift( array_keys( $t_status_array ) ) : $p_status_default;

		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5"><div class="productmatrix">',
			'<input type="hidden" name="ProductMatrix" value="1"/>';

		foreach( $this->products as $t_product ) {
			echo '<table class="pvmproduct" cellspacing="0">',
				'<tr class="row-category"><td colspan="2">', $t_product->name, '</td></tr>';

			foreach( $t_product->version_tree_list() as $t_node ) {
				list( $t_version, $t_depth ) = $t_node;

				$t_collapse_ids = join( ':', $this->version_child_ids( $t_version->id ) );

				echo '<tr id="pvmversion', $t_version->id, '" class="pvmstatus ', $t_depth < 1 ? 'pvmtoplevel' : 'pvmchild',
					'" collapse="', $t_collapse_ids, '"><td class="category">', str_pad( '', $t_depth, '-' ), ' ', $t_version->name, '</td>';

				if ( $this->version_mutable( $t_version->id ) ) {
					echo '<td><input type="checkbox" name="Product', $t_product->id, 'Version', $t_version->id, '" value="', $t_status_default, '"/></td>';
				} else {
					echo '<td></td>';
				}

				echo '</tr>';
			}

			if ( count( $t_product->platforms ) ) {
				echo '<tr class="pvmaffected"><td class="category">Affects</td><td>';

				$t_first = true;
				$t_platform_temp_ids = array();

				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo '<br/>'; }
					echo '<label><input type="checkbox" id="ProductPlatform', $t_platform_temp_id,
						'" name="Product', $t_product->id, 'Platform' , $t_platform->id, '"/> ',
						$t_platform->name, '</label>';

					$t_first = false;
					$t_platform_temp_ids[] = $t_platform_temp_id;
					$t_platform_temp_id++;
				}

				if ( $t_common_enabled ) {
					$t_onclick = '';
					foreach( $t_platform_temp_ids as $t_temp_id ) {
						$t_onclick .= "document.getElementById('ProductPlatform$t_temp_id').checked = true;";
					}
					$t_onclick = "onclick=\" \n\nif ( this.checked ) {\n\t$t_onclick\n}\"";

					echo '<br/><label><input type="checkbox" name="Product', $t_product->id, 'Platform0" ', $t_onclick, '/> ',
						plugin_lang_get( 'common' ), '</label>';
				}
				echo '</td></tr>';
			}

			echo '</table>';
		}

		echo '</div></td></tr>';

		plugin_pop_current();
	}

	/**
	 * Update the affected platform and version status matrices as indicated
	 * by a form submitted by the user when reporting or updating a bug.
	 * Does not save the changes to the database; ProductMatrix::save()
	 * *must* be called to persist the changes to the matrices.
	 */
	function process_form() {
		$this->products = PVMProduct::load_all( true );

		if ( !gpc_get_int( 'ProductMatrix', 0 ) ) {
			return;
		}

		plugin_push_current( 'ProductMatrix' );

		$t_common_enabled = plugin_config_get( 'common_platform' );

		plugin_pop_current();

		foreach( $this->products as $t_product ) {
			$t_form_prefix = 'Product' . $t_product->id . 'Platform';

			foreach( $t_product->platforms as $t_platform ) {
				$t_form_item = $t_form_prefix . $t_platform->id;
				$t_affects = gpc_get_bool( $t_form_item, 0 );

				$t_affects_set = isset( $this->affects[$t_product->id][$t_platform->id] );
				$t_affects_cleared = $t_affects < 1;

				if ( $t_affects_cleared ) {
					if ( $t_affects_set ) {
						$this->affects[$t_product->id][$t_platform->id] = false;
					}
				} else {
					$this->affects[$t_product->id][$t_platform->id] = true;
				}
			}

			if ( $t_common_enabled ) {
				$t_form_item = $t_form_prefix . '0';
				$t_affects = gpc_get_bool( $t_form_item, 0 );

				$t_affects_set = isset( $this->affects[$t_product->id][0] );
				$t_affects_cleared = $t_affects < 1;

				if ( $t_affects_cleared ) {
					if ( $t_affects_set ) {
						$this->affects[$t_product->id][0] = false;
					}
				} else {
					$this->affects[$t_product->id][0] = true;
				}
			}

			$t_form_prefix = 'Product' . $t_product->id . 'Version';

			foreach( $t_product->versions as $t_version ) {
				$t_form_item = $t_form_prefix . $t_version->id;
				$t_status = gpc_get_int( $t_form_item, 0 );

				$t_status_set = isset( $this->status[$t_version->id] );
				$t_status_cleared = $t_status < 1;

				if ( $t_status_cleared ) {
					if ( $t_status_set ) {
						$this->status[$t_version->id] = null;
					}
				} else {
					$this->status[$t_version->id] = $t_status;
				}
			}
		}
	}

	/**
	 * Generate a list of status levels that are available in the current workflow configuration.
	 * @param int Status
	 * @return array Possible status levels, keyed as id=>name
	 */
	function generate_possible_workflow( $p_status ) {
		static $s_status_array = null;
		static $s_status_workflow = null;
		static $s_workflows = array();

		if ( isset( $s_workflows[ $p_status ] ) ) {
			return $s_workflows[ $p_status ];
		}

		plugin_push_current( 'ProductMatrix' );

		if ( is_null( $s_status_array ) ) {
			$s_status_array = plugin_config_get( 'status' );
		}
		if ( is_null( $s_status_workflow ) ) {
			$s_status_workflow = plugin_config_get( 'status_workflow' );
		}

		plugin_pop_current();

		if ( ( $p_status == 0 && !isset( $s_status_workflow[ 0 ] ) ) || empty( $s_status_workflow[ $p_status ] ) ) {
			$t_possible_workflow = $s_status_array;
			$t_possible_workflow[0] = true;

		} else {
			$t_empty_workflow = false;

			$t_possible_workflow = array();
			sort( $t_available_workflow = $s_status_workflow[ $p_status ] );

			foreach( $t_available_workflow as $t_available_status ) {
				if ( $t_available_status == 0 ) {
					$t_possible_workflow[0] = true;
				}
				if ( isset( $s_status_array[ $t_available_status ] ) ) {
					$t_possible_workflow[ $t_available_status ] = $s_status_array[ $t_available_status ];
				}
			}

			if ( !isset( $t_possible_workflow[ $p_status ] ) && isset( $s_status_array[ $p_status ] ) ) {
				$t_possible_workflow[ $p_status ] = $s_status_array[ $p_status ];
			}
		}

		$s_workflows[ $p_status ] = $t_possible_workflow;
		return $t_possible_workflow;
	}

}

