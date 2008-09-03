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

		$t_query = "SELECT * FROM $t_product_table ORDER BY name ASC";
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
	var $status;
	var $__status;
	var $products;

	function __construct( $p_bug_id, $p_load_products=true ) {
		$this->bug_id = $p_bug_id;
		$this->__status = array();
		$this->status = array();

		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		$t_query = "SELECT * FROM $t_status_table WHERE bug_id=" . db_param();
		$t_result = db_query_bound( $t_query, array( $p_bug_id ) );

		while( $t_row = db_fetch_array( $t_result ) ) {
			$this->status[ $t_row['version_id'] ] = $t_row['status'];
			$this->__status[ $t_row['version_id'] ] = $t_row['status'];
		}

		if ( $p_load_products ) {
			$t_version_ids = array_keys( $this->status );
			$this->products = PVMProduct::load_by_version_ids( $t_version_ids );
		}
	}

	function save() {
		$t_status_table = plugin_table( 'status', 'ProductMatrix' );

		foreach( $this->status as $t_version_id => $t_status ) {
			if ( !isset( $this->__status[ $t_version_id ] ) ) { # new status
				$t_query = "INSERT INTO $t_status_table ( bug_id, version_id, status )
					VALUES ( " . join( ',', array( db_param(), db_param(), db_param() ) ) . ' )';
				db_query_bound( $t_query, array( $this->bug_id, $t_version_id, $t_status ) );

				$this->__status[ $t_version_id ] = $t_status;

			} else if ( is_null( $t_status ) ) { # deleted status
				$t_query = "DELETE FROM $t_status_table WHERE bug_id=" . db_param() . ' AND version_id=' . db_param();
				db_query_bound( $t_query, array( $this->bug_id, $t_version_id ) );

				unset( $this->status[ $t_version_id ] );
				unset( $this->__status[ $t_version_id ] );

			} else if ( $t_status != $this->__status[ $t_version_id ] ) { # updated status
				$t_query = "UPDATE $t_status_table SET status=" . db_param() .
					' WHERE bug_id=' . db_param() . ' AND version_id=' . db_param();
				db_query_bound( $t_query, array( $t_status, $this->bug_id, $t_version_id ) );

				$this->__status[ $t_version_id ] = $t_status;

			}
		}
	}

	function view() {
		if ( count( $this->status ) < 1 || count( $this->products ) < 1 ) {
			return null;
		}

		$t_version_count = 0;
		foreach( $this->products as $t_product ) {
			$t_version_count = max( count( $t_product->versions ), $t_version_count );
			$t_product->__versions = $t_product->versions;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">,',
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

		echo '</table>';

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

		$t_version_count = 0;
		foreach( $t_products as $t_product ) {
			$t_version_count = max( count( $t_product->versions ), $t_version_count );
			$t_product->__versions = $t_product->versions;
		}

		echo '<tr ', helper_alternate_class(), '><td class="category">,',
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
				if ( count( $t_product->__versions ) ) {
					$t_version = array_shift( $t_product->__versions );

					echo '<td class="category">', $t_version->name, '</td><td>',
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

		echo '</table>';

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
}

