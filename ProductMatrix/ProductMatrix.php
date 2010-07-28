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

class ProductMatrixPlugin extends MantisPlugin {
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );
		$this->page = 'config_page';

		$this->version = '0.3';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);
		$this->uses = array(
			'jQuery' => '1.3',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
	}

	function errors() {
		return array(
			'ProductNotFound' => 'The product was not found.',
			'VersionNotFound' => 'The version was not found.',
			'PlatformNotFound' => 'The platform was not found.',
			'ProductNameEmpty' => 'The product\'s name must not be empty.',
			'VersionNameEmpty' => 'The version\'s name must not be empty.',
			'PlatformNameEmpty' => 'The platform\'s name must not be empty.',
			'ProductIDNotSet' => 'The product has an invalid ID.',
		);
	}

	function events() {
		return array(
			'EVENT_PRODUCTMATRIX_PRESAVE' => EVENT_TYPE_EXECUTE,
		);
	}

	function config() {
		return array(
			'view_threshold' => VIEWER,
			'update_threshold' => DEVELOPER,
			'manage_threshold' => MANAGER,

			'report_status' => OFF,
			'common_platform' => ON,
			'reverse_inheritence' => OFF,
			'product_status' => OFF,
			'status_cascade' => ON,
			'status_column_show_na' => OFF,

			'status' => array(
				10 => 'open',
				20 => 'confirmed',
				30 => 'in work',
				40 => 'testing',
				50 => 'failed',
				60 => 'resolved',
				70 => 'suspended',
				),
			'status_color' => array(
				10 => '#ffcdcd',
				20 => '#ffeccd',
				30 => '#feffcd',
				40 => '#cde2ff',
				50 => '#efcdff',
				60 => '#daf5e7',
				70 => '#dadada',
				),

			'status_workflow' => array(
				10 => array(),
				20 => array(),
				30 => array(),
				40 => array(),
				50 => array(),
				60 => array(),
				70 => array(),
				),

			'status_default' => 0,
		);
	}

	function hooks() {
		return array(
			'EVENT_LAYOUT_RESOURCES'	=> 'resources',
			'EVENT_MENU_MAIN'			=> 'menu',
			'EVENT_VIEW_BUG_DETAILS'	=> 'view_bug',
			'EVENT_UPDATE_BUG_FORM'		=> 'update_bug_form',
			'EVENT_UPDATE_BUG'			=> 'update_bug',
			'EVENT_REPORT_BUG_FORM'		=> 'report_bug_form',
			'EVENT_REPORT_BUG'			=> 'report_bug',
			'EVENT_FILTER_FIELDS'		=> 'filter_fields',
			'EVENT_ACCOUNT_PREF_UPDATE_FORM'	=> 'update_prefs_form',
			'EVENT_ACCOUNT_PREF_UPDATE'	=> 'update_prefs',
			'EVENT_FILTER_COLUMNS'		=> 'filter_columns',
		);
	}

	function filter_fields( $p_event ) {
		require_once( 'ProductMatrix.FilterAPI.php' );
		return array(
			'PVMProductFilter',
			'PVMVersionFilter',
			'PVMStatusFilter',
			'PVMStatusColumnFilter',
		);
	}

	function filter_columns( $p_event ) {
		require_once( 'ProductMatrix.FilterAPI.php' );
		require_once( 'ProductMatrix.ColumnAPI.php' );

		$t_class_definitions = '';
		$t_columns = array();
		$t_products = PVMProduct::load_all();
		$t_version_names = array();
		$t_inputs = PVMStatusColumnFilter::inputs();

		if ( count( $t_inputs ) < 1 ) {
			return;
		}

		$t_version_table = plugin_table( 'version' );
		$t_query = "SELECT id, product_id, name FROM $t_version_table";
		$t_result = db_query_bound( $t_query );
		while( $t_row = db_fetch_array( $t_result ) ) {
			$t_names[ $t_row['id'] ] = $t_products[ $t_row['product_id'] ]->name . ' ' . $t_row['name'];
		}

		foreach( $t_inputs as $t_input ) {
			$t_id = (int) $t_input;
			if ( $t_id < 1 ) {
				continue;
			}

			$t_class = 'PVMStatusColumn' . $t_id;
			$t_title = $t_names[$t_id];

			$t_class_definitions .= <<<EOC
class ${t_class} extends PVMStatusColumn {
	public \$title = '${t_title}';
	public \$column = 'status${t_id}';
	public \$id = ${t_id};
}
EOC;

			$t_columns[] = $t_class;
		}

		if ( count( $t_columns ) > 0 ) {
			eval( $t_class_definitions );
			return $t_columns;
		}

	}

	function init() {
		require_once( 'ProductMatrix.API.php' );
	}

	function resources() {
		$t_resources = '<link rel="stylesheet" href="' . plugin_file( 'default.css' ) . '" type="text/css" />';

		if ( plugin_dependency( 'jQuery', '1.3', true ) ) {
			$t_resources .= '<script type="text/javascript" src="' . plugin_file( 'behavior.js' ) . '"></script>';
		}

		return $t_resources;
	}

	function menu() {
		return '<a href="' . plugin_page( 'products' ) . '">Products</a>';
	}

	function view_bug( $p_event, $p_bug_id ) {
		if ( access_has_bug_level( plugin_config_get( 'view_threshold' ), $p_bug_id ) ) {
			$matrix = new ProductMatrix( $p_bug_id );
			$matrix->view();
		}
	}

	function update_bug_form( $p_event, $p_bug_id ) {
		if ( access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			$matrix = new ProductMatrix( $p_bug_id );
			$matrix->view_form( PVM_UPDATE );
		}
	}

	function update_bug( $p_event, $p_bug_data, $p_bug_id ) {
		if ( access_has_bug_level( plugin_config_get( 'update_threshold' ), $p_bug_id ) ) {
			$matrix = new ProductMatrix( $p_bug_id, false );
			$matrix->process_form();
			$matrix->save( PVM_UPDATE );
		}

		return $p_bug_data;
	}

	function report_bug_form( $p_event ) {
		if ( access_has_project_level( plugin_config_get( 'update_threshold' ) ) ) {
			$matrix = new ProductMatrix();
			if ( plugin_config_get( 'report_status' ) ) {
				$matrix->view_form( PVM_REPORT, plugin_config_get( 'status_default', 0 ) );
			} else {
				$matrix->view_report_form( plugin_config_get( 'status_default', 0 ) );
			}
		}
	}

	function report_bug( $p_event, $p_bug_data, $p_bug_id ) {
		if ( access_has_project_level( plugin_config_get( 'update_threshold' ) ) ) {
			$matrix = new ProductMatrix( $p_bug_id, false );
			$matrix->process_form();
			$matrix->save( PVM_REPORT );
		}
	}

	function update_prefs_form( $p_event, $p_user_id ) {
		$t_user_prefs = new PVMUserPrefs( $p_user_id );

		echo '<tr ', helper_alternate_class(), '><td class="category">', plugin_lang_get( 'versions_sort_order' ),
			'</td><td><input type="radio" name="version_order" value=', ASCENDING, ' ', check_checked( $t_user_prefs->version_order, ASCENDING ), '/>Ascending',
			'<input type="radio" name="version_order" value=', DESCENDING, ' ',  check_checked( $t_user_prefs->version_order, DESCENDING ), '/>Descending</td></tr>';
	}

	function update_prefs( $p_event, $p_user_id ) {
		$f_order = gpc_get_int( 'version_order', ASCENDING );

		$t_user_prefs = new PVMUserPrefs( $p_user_id );
		$t_user_prefs->version_order = $f_order;
		$t_user_prefs->save();
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'product' ), "
				id		I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				name	C(128)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'version' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				parent_id	I		NOTNULL UNSIGNED DEFAULT '0',
				product_id	I		NOTNULL UNSIGNED DEFAULT '0',
				name		C(128)	NOTNULL DEFAULT \" '' \",
				date		T		NOTNULL,
				released	L		NOTNULL DEFAULT '0',
				obsolete	L		NOTNULL DEFAULT '0'
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'status' ), "
				bug_id		I		NOTNULL UNSIGNED PRIMARY,
				version_id	I		NOTNULL UNSIGNED PRIMARY,
				status		I		NOTNULL UNSIGNED
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'platform' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				product_id	I		NOTNULL UNSIGNED,
				name		C(128)	NOTNULL DEFAULT \" '' \",
				obsolete	L		NOTNULL DEFAULT '0'
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'affects' ), "
				bug_id		I		NOTNULL UNSIGNED PRIMARY,
				product_id	I		NOTNULL UNSIGNED PRIMARY,
				platform_id	I		NOTNULL UNSIGNED PRIMARY
				" ) ),
			# 2009-04-10 - Allow versions to select what version they inherit status from
			array( 'AddColumnSQL', array( plugin_table( 'version' ), "
				inherit_id	I		NOTNULL UNSIGNED DEFAULT '0'
				" ) ),

			# 2009-10-30 - Add user preference for version display order
			array( 'CreateTableSQL', array( plugin_table( 'user' ), "
				user_id			I		NOTNULL UNSIGNED PRIMARY,
				version_order	I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}
