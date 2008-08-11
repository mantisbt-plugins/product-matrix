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

	function hooks() {
		return array(
			'EVENT_VIEW_BUG_DETAILS'	=> 'view_bug',
			'EVENT_UPDATE_BUG_FORM'		=> 'update_bug_form',
			'EVENT_UPDATE_BUG'			=> 'update_bug',
		);
	}

	function view_bug( $p_event, $p_bug_id ) {
		var_dump( $p_bug_id );
	}

	function update_bug_form( $p_event, $p_bug_id ) {
		var_dump( $p_bug_id );
	}

	function update_bug( $p_event, $p_bug ) {
		var_dump( $p_bug );
	}

	function schema() {
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'product' ), "
				id		I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				name	C(128)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'version' ), "
				id			I		NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
				product_id	I		NOTNULL UNSIGNED DEFAULT '0',
				name		C(128)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'status' ), "
				bug_id		I		NOTNULL UNSIGNED PRIMARY,
				version_id	I		NOTNULL UNSIGNED PRIMARY,
				status		I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}