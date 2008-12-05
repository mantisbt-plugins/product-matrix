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

class ProductMatrixPlugin extends MantisPlugin {
	function register() {
		$this->name = plugin_lang_get( 'title' );
		$this->description = plugin_lang_get( 'description' );

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
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

	function config() {
		return array(
			'view_threshold' => VIEWER,
			'manage_threshold' => MANAGER,

			'status' => array(
				10 => 'open',
				30 => 'confirmed',
				50 => 'in work',
				70 => 'testing',
				80 => 'suspended',
				90 => 'resolved',
				),
			'status_color' => array(
				10 => '#ffcdcd',
				30 => '#ffeccd',
				50 => '#feffcd',
				70 => '#cde2ff',
				80 => '#dadada',
				90 => '#daf5e7',
				),
		);
	}

	function hooks() {
		return array(
			'EVENT_LAYOUT_RESOURCES'	=> 'css',
			'EVENT_MENU_MAIN'			=> 'menu',
			'EVENT_VIEW_BUG_DETAILS'	=> 'view_bug',
			'EVENT_UPDATE_BUG_FORM'		=> 'update_bug_form',
			'EVENT_UPDATE_BUG'			=> 'update_bug',
			'EVENT_REPORT_BUG_FORM'		=> 'report_bug_form',
			'EVENT_REPORT_BUG'			=> 'report_bug',
		);
	}

	function init() {
		require_once( 'ProductMatrix.API.php' );
	}

	function css() {
		return '<link rel="stylesheet" href="' . plugin_file( 'default.css' ) . '" type="text/css" />';
	}

	function menu() {
		return '<a href="' . plugin_page( 'products' ) . '">Products</a>';
	}

	function view_bug( $p_event, $p_bug_id ) {
		$matrix = new ProductMatrix( $p_bug_id );
		$matrix->view();
	}

	function update_bug_form( $p_event, $p_bug_id ) {
		$matrix = new ProductMatrix( $p_bug_id );
		$matrix->view_form();
	}

	function update_bug( $p_event, $p_bug_data, $p_bug_id ) {
		$matrix = new ProductMatrix( $p_bug_id, false );
		$matrix->process_form();
		$matrix->save();
	}

	function report_bug_form( $p_event ) {
		$matrix = new ProductMatrix();
		$matrix->view_report_form();
	}

	function report_bug( $p_event, $p_bug_data, $p_bug_id ) {
		$matrix = new ProductMatrix( $p_bug_id, false );
		$matrix->process_form();
		$matrix->save();
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
				date		T		NOTNULL DEFAULT '" . db_null_date() . "',
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
				platform_id	I		NOTNULL UNSIGNED PRIMARY
				" ) ),
		);
	}
}
