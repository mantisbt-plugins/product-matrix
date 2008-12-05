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
	var $platforms = array();
	var $versions = array();
	var $version_tree = array();

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

		foreach( $this->platforms as $t_platform ) {
			if ( 0 == $t_platform->product_id ) {
				$t_platform->product_id = $this->id;
			}

			$t_platform->save();
		}
	}

	function load_versions() {
		if ( 0 == $this->id ) {
			plugin_error( 'ProductIDNotSet', ERROR );
		}

		$this->versions = PVMVersion::load_by_product( $this->id );
		$this->build_version_tree();
	}

	function build_version_tree() {
		foreach( $this->versions as $t_version ) {
			$t_parent_id = $t_version->parent_id;
			if ( !isset( $this->version_tree[ $t_parent_id ] ) ) {
				$this->version_tree[ $t_parent_id ] = array();
			}

			$this->version_tree[ $t_parent_id ][ $t_version->id ] = $t_version;
		}
	}

	function version_tree_list() {
		if ( !isset( $this->__version_tree_list ) ) {
			$this->__version_tree_list = $this->version_tree_section( $this->version_tree[0] );
		}
		return $this->__version_tree_list;
	}

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

		$t_product->platforms = PVMPlatform::load_by_product( $t_product->id );

		if ( $p_load_versions ) {
			$t_product->load_versions();
		}

		return $t_product;
	}

	static function load_all( $p_load_versions=false ) {
		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_product_table ORDER BY name ASC";
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

		return $t_products;
	}

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
			WHERE v.id IN ( $t_version_list )
			ORDER BY name ASC";
		$t_result = db_query_bound( $t_query );

		$t_products = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_product = new PVMProduct( $t_row['name'] );
			$t_product->id = $t_row['id'];

			$t_product->platforms = PVMPlatform::load_by_product( $t_product->id );
			$t_product->load_versions();
			$t_products[$t_product->id] = $t_product;
		}

		return $t_products;
	}

	static function delete( $p_id ) {
		PVMPlatform::delete_by_product( $p_id );
		PVMVersion::delete_by_product( $p_id );

		$t_product_table = plugin_table( 'product', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_product_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

	function select_versions( $p_default_id=0 ) {
		echo '<option value="0">--</option>';
		foreach( $this->version_tree_list() as $t_node ) {
			list( $t_version, $t_depth ) = $t_node;

			echo '<option value="', $t_version->id, '" ',
				( $t_version->id == $p_default_id ? 'selected="selected" ' : '' ),
				'>', str_pad( ' ', $t_depth+1, '-', STR_PAD_LEFT ), $t_version->name, '</option>';
		}
	}
}

class PVMPlatform {
	var $id;
	var $product_id;
	var $name;
	var $obsolete;

	function __construct( $p_product_id, $p_name ) {
		$this->id = 0;
		$this->product_id = $p_product_id;
		$this->name = $p_name;
		$this->obsolete = false;
	}

	function save() {
		if ( is_blank( $this->name ) ) {
			plugin_error( 'PlatformNameEmpty', ERROR );
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

	static function load( $p_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_platform_table WHERE id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_id ) );

		if ( db_num_rows( $t_result ) < 1 ) {
			plugin_error( 'PlatformNotFound', ERROR );
		}

		$t_row = db_fetch_array( $t_result );

		$t_platform = new PVMPlatform( $t_row['product_id'], $t_row['name'] );
		$t_platform->id = $t_row['id'];
		$t_platform->obsolete = $t_row['obsolete'];

		return $t_platform;
	}

	static function load_by_product( $p_product_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_platform_table WHERE product_id=" . db_param() . ' ORDER BY name ASC';
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_platforms = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_platform = new PVMPlatform( $t_row['product_id'], $t_row['name'] );
			$t_platform->id = $t_row['id'];
			$t_platform->obsolete = $t_row['obsolete'];

			$t_platforms[$t_platform->id] = $t_platform;
		}

		return $t_platforms;
	}

	static function delete( $p_id ) {
		$t_platform_table = plugin_table( 'platform', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_affects_table WHERE platform_id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );

		$t_query = "DELETE FROM $t_platform_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

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

class PVMVersion {
	var $id;
	var $parent_id = 0;
	var $product_id;
	var $name;
	var $date;
	var $released = false;
	var $obsolete = false;

	function __construct( $p_product_id, $p_name, $p_parent_id=0 ) {
		$this->id = 0;
		$this->parent_id = $p_parent_id;
		$this->product_id = $p_product_id;
		$this->name = $p_name;
		$this->date = date( 'Y-m-d' );
	}

	function save() {
		if ( is_blank( $this->name ) ) {
			plugin_error( 'VersionNameEmpty', ERROR );
		}

		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		if ( 0 == $this->id ) { #create
			$t_query = "INSERT INTO $t_version_table (
					parent_id,
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
					db_param() .
				')';
			db_query_bound( $t_query, array(
				$this->parent_id,
				$this->product_id,
				$this->name,
				db_timestamp( $this->date ),
				db_prepare_bool( $this->released ),
				db_prepare_bool( $this->obsolete ),
			) );

			$this->id = db_insert_id( $t_version_table );

		} else { #update
			$t_query = "UPDATE $t_version_table SET
					parent_id=" . db_param() . ',
					product_id=' . db_param() . ',
					name=' . db_param() . ',
					date=' . db_param() . ',
					released=' . db_param() . ',
					obsolete=' . db_param() . '
				WHERE id=' . db_param();
			db_query_bound( $t_query, array(
				$this->parent_id,
				$this->product_id,
				$this->name,
				db_timestamp( $this->date ),
				db_prepare_bool( $this->released ),
				db_prepare_bool( $this->obsolete ),
				$this->id
			) );
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

		$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'], $t_row['parent_id'] );
		$t_version->id = $t_row['id'];
		$t_version->date = $t_row['date'];
		$t_version->released = $t_row['released'];
		$t_version->obsolete = $t_row['obsolete'];

		return $t_version;
	}

	static function load_by_product( $p_product_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_version_table WHERE product_id=" . db_param() . ' ORDER BY name ASC';
		$t_result = db_query_bound( $t_query, array( $p_product_id ) );

		$t_versions = array();
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_version = new PVMVersion( $t_row['product_id'], $t_row['name'], $t_row['parent_id'] );
			$t_version->id = $t_row['id'];
			$t_version->date = $t_row['date'];
			$t_version->released = $t_row['released'];
			$t_version->obsolete = $t_row['obsolete'];

			$t_versions[$t_version->id] = $t_version;
		}

		return $t_versions;
	}

	static function delete( $p_id ) {
		$t_version_table = plugin_table( 'version', 'ProductMatrix' );
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = "DELETE FROM $t_status_table WHERE version_id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );

		$t_query = "DELETE FROM $t_version_table WHERE id=" . db_param();
		db_query_bound( $t_query, array( $p_id ) );
	}

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

class ProductMatrix {
	var $bug_id;
	var $status;
	var $__status;
	var $affects;
	var $__affects;
	var $products;

	function __construct( $p_bug_id=0, $p_load_products=true ) {
		$this->bug_id = $p_bug_id;
		$this->__status = array();
		$this->status = array();
		$this->__affects = array();
		$this->affects = array();
		$this->products = array();

		if ( !$p_bug_id ) {
			return;
		}

		$t_status_table = plugin_table( 'status', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_status_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->status[ $t_row['version_id'] ] = $t_row['status'];
			$this->__status[ $t_row['version_id'] ] = $t_row['status'];
		}

		$t_query = "SELECT * FROM $t_affects_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->affects[ $t_row['platform_id'] ] = true;
			$this->__affects[ $t_row['platform_id'] ] = true;
		}

		if ( $p_load_products ) {
			$this->load_products();
		}
	}

	function save() {
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );
		$t_affects_table = plugin_table( 'affects', 'ProductMatrix' );

		$this->load_products();
		$this->products_to_versions();
		$this->products_to_platforms();

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

		foreach( $this->affects as $t_platform_id => $t_affected ) {
			if ( !isset( $this->__affects[ $t_platform_id ] ) ) { # new platform
				$t_query = "INSERT INTO $t_affects_table ( bug_id, platform_id )
					VALUES ( " . join( ',', array( db_param(), db_param() ) ) . ' )';
				db_query_bound( $t_query, array( $this->bug_id, $t_platform_id ) );

				$this->history_log_platform( $t_platform_id, true );
				$this->__affects[ $t_platform_id ] = true;

			} else if ( false == $t_affected ) { # removed platform
				$t_query = "DELETE FROM $t_affects_table WHERE bug_id=" . db_param() . ' AND platform_id=' . db_param();
				db_query_bound( $t_query, array( $this->bug_id, $t_platform_id ) );

				$this->history_log_platform( $t_platform_id, false );
				unset( $this->affects[ $t_platform_id ] );
				unset( $this->__affects[ $t_platform_id ] );
			}
		}
	}

	function load_products() {
		$t_version_ids = array_keys( $this->status );
		$this->products = PVMProduct::load_by_version_ids( $t_version_ids );
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
	 * Create a reverse-association of platform ID to products.
	 */
	function products_to_platforms() {
		if ( isset( $this->platforms ) ) {
			return;
		}

		$this->platforms = array();
		foreach( $this->products as $t_product ) {
			foreach( $t_product->platforms as $t_platform ) {
				$this->platforms[ $t_platform->id ] = $t_product;
			}
		}
	}

	function history_log_platform( $t_platform_id, $t_affected ) {
		$t_product_name = $this->platforms[ $t_platform_id ]->name;
		$t_platform_name = $this->platforms[ $t_platform_id ]->platforms[ $t_platform_id ]->name;

		$t_history_string = "$t_product_name: $t_platform_name";

		if ( $t_affected ) {
			$t_field = 'history_platform_affected';
		} else {
			$t_field = 'history_platform_unaffected';
		}

		plugin_history_log( $this->bug_id, $t_field, $t_history_string );
	}

	function history_log_version( $t_version_id, $t_old, $t_new ) {
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
	}

	/**
	 * Prune unused platforms and versions from products in the matrix.
	 */
	function prune() {
		$t_platform_ids = array_keys( $this->affects );
		$t_version_ids = array_keys( $this->status );

		foreach( $this->products as $t_product ) {
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

	function view() {
		if ( count( $this->status ) < 1 || count( $this->products ) < 1 ) {
			return null;
		}

		$this->prune();

		$t_version_count = 0;
		foreach( $this->products as $t_product ) {
			$t_version_count = max( count( $t_product->versions ), $t_version_count );
			$t_product->__versions = $t_product->versions;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5">';

		collapse_open( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $this->products as $t_product ) {
			echo '<td colspan="2">', $t_product->name, '</td>';
		}

		echo '</tr>';

		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );

		for( $i = 0; $i < $t_version_count; $i++ ) {
			echo '<tr ', helper_alternate_class(), '><td></td>';

			foreach( $this->products as $t_product ) {
				if ( count( $t_product->__versions ) ) {
					$t_version = array_shift( $t_product->__versions );
					$t_status = $this->status[$t_version->id];

					echo '<td class="category">', $t_version->name, '</td><td bgcolor="',
						$t_status_colors[$t_status], '">', $t_status_array[$t_status], '</td>';

				} else {
					echo '<td></td><td></td>';
				}
			}

			echo '</tr>';
		}

		echo '<tr ', helper_alternate_class(), '><td></td>';

		foreach( $this->products as $t_product ) {
			if ( count( $t_product->platforms ) ) {
				echo '<td class="category">Affects</td><td>';
				$t_first = true;
				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo ', '; }
					echo $t_platform->name;
					$t_first = false;
				}
				echo '</td>';
			} else {
				echo '<td></td><td></td>';
			}
		}

		echo '</tr></table>';

		collapse_closed( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $this->products as $t_product ) {
			echo '<td>', $t_product->name, '</td>';
		}

		echo '</tr></table>';

		collapse_end( 'view', 'ProductMatrix' );

		echo '</td></tr>';
	}

	function view_form() {
		$t_products = PVMProduct::load_all( true );

		if ( count( $t_products ) < 1 ) {
			return null;
		}

		$t_version_trees = array();
		$t_version_count = 0;
		foreach( $t_products as $t_product ) {
			$t_version_tree = $t_product->version_tree_list();

			$t_version_count = max( count( $t_version_tree ), $t_version_count );
			$t_version_trees[ $t_product->id ] = $t_version_tree;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5">';

		collapse_open( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $t_products as $t_product ) {
			echo '<td colspan="2">', $t_product->name, '</td>';
		}

		echo '</tr>';

		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );

		for( $i = 0; $i < $t_version_count; $i++ ) {
			echo '<tr ', helper_alternate_class(), '><td></td>';

			foreach( $t_products as $t_product ) {
				$t_shown = false;
				if( count( $t_version_trees[ $t_product->id ] ) ) {
					list( $t_version, $t_depth ) = array_shift( $t_version_trees[ $t_product->id ] );

					echo '<td class="category">', str_pad( '', $t_depth, '-' ), ' ', $t_version->name, '</td><td>',
						'<select name="Product', $t_product->id, 'Version', $t_version->id, '">';

					if ( isset( $this->status[$t_version->id] ) ) {
						$t_status = $this->status[$t_version->id];
					} else {
						$t_status = 0;
					}

					echo '<option value="0"', ( $t_status ? '' : ' selected="selected"' ), '>', plugin_lang_get( 'status_na' ), '</option>';
					foreach( $t_status_array as $t_status_value => $t_status_name ) {
						echo '<option value="', $t_status_value, '"',
							( $t_status == $t_status_value ? ' selected="selected"' : '' ),
							'>', $t_status_name, '</option>';
					}

					echo '</select></td>';

				} else {
					echo '<td></td><td></td>';
				}
			}

			echo '</tr>';
		}

		echo '<tr ', helper_alternate_class(), '><td></td>';

		foreach( $t_products as $t_product ) {
			if ( count( $t_product->platforms ) ) {
				echo '<td class="category">Affects</td><td>';
				$t_first = true;
				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo '<br/>'; }
					echo '<label><input type="checkbox" name="Product', $t_product->id, 'Platform' , $t_platform->id, '" ',
						( isset( $this->affects[ $t_platform->id ] ) ? ' checked="checked"' : '' ), '/> ',
						$t_platform->name, '</label>';
					$t_first = false;
				}
				echo '</td>';
			} else {
				echo '<td></td><td></td>';
			}
		}

		echo '</tr></table>';

		collapse_closed( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $t_products as $t_product ) {
			echo '<td>', $t_product->name, '</td>';
		}

		echo '</tr></table>';

		collapse_end( 'view', 'ProductMatrix' );

		echo '</td></tr>';
	}

	function view_report_form() {
		$t_products = PVMProduct::load_all( true );

		if ( count( $t_products ) < 1 ) {
			return null;
		}

		$t_version_trees = array();
		$t_version_count = 0;
		foreach( $t_products as $t_product ) {
			$t_version_tree = $t_product->version_tree_list();

			$t_version_count = max( count( $t_version_tree ), $t_version_count );
			$t_version_trees[ $t_product->id ] = $t_version_tree;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">',
			plugin_lang_get( 'product_status' ), '</td><td colspan="5">';

		collapse_open( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $t_products as $t_product ) {
			echo '<td colspan="2">', $t_product->name, '</td>';
		}

		echo '</tr>';

		$t_status_array = plugin_config_get( 'status' );
		$t_status_colors = plugin_config_get( 'status_color' );
		$t_status_default = array_shift( array_keys( $t_status_array ) );

		for( $i = 0; $i < $t_version_count; $i++ ) {
			echo '<tr ', helper_alternate_class(), '><td></td>';

			foreach( $t_products as $t_product ) {
				$t_shown = false;
				if( count( $t_version_trees[ $t_product->id ] ) ) {
					list( $t_version, $t_depth ) = array_shift( $t_version_trees[ $t_product->id ] );

					echo '<td class="category">', str_pad( '', $t_depth, '-' ), ' ', $t_version->name, '</td><td>',
						'<input type="checkbox" name="Product', $t_product->id, 'Version', $t_version->id, '" value="', $t_status_default, '"/>',
						'</td>';

				} else {
					echo '<td></td><td></td>';
				}
			}

			echo '</tr>';
		}

		echo '<tr ', helper_alternate_class(), '><td></td>';

		foreach( $t_products as $t_product ) {
			if ( count( $t_product->platforms ) ) {
				echo '<td class="category">Affects</td><td>';
				$t_first = true;
				foreach( $t_product->platforms as $t_platform ) {
					if ( !$t_first ) { echo '<br/>'; }
					echo '<label><input type="checkbox" name="Product', $t_product->id, 'Platform' , $t_platform->id, '" ',
						( isset( $this->affects[ $t_platform->id ] ) ? ' checked="checked"' : '' ), '/> ',
						$t_platform->name, '</label>';
					$t_first = false;
				}
				echo '</td>';
			} else {
				echo '<td></td><td></td>';
			}
		}

		echo '</tr></table>';

		collapse_closed( 'view', 'ProductMatrix' );

		echo '<table class="productmatrix" cellspacing="1"><tr class="row-category"><td>';
		collapse_icon( 'view', 'ProductMatrix' );
		echo '</td>';

		foreach( $t_products as $t_product ) {
			echo '<td>', $t_product->name, '</td>';
		}

		echo '</tr></table>';

		collapse_end( 'view', 'ProductMatrix' );

		echo '</td></tr>';
	}

	function process_form() {
		$t_products = PVMProduct::load_all( true );

		foreach( $t_products as $t_product ) {
			$t_form_prefix = 'Product' . $t_product->id . 'Platform';

			foreach( $t_product->platforms as $t_platform ) {
				$t_form_item = $t_form_prefix . $t_platform->id;
				$t_affects = gpc_get_bool( $t_form_item, 0 );

				$t_affects_set = isset( $this->affects[$t_platform->id] );
				$t_affects_cleared = $t_affects < 1;

				if ( $t_affects_cleared ) {
					if ( $t_affects_set ) {
						$this->affects[$t_platform->id] = false;
					}
				} else {
					$this->affects[$t_platform->id] = true;
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
}

