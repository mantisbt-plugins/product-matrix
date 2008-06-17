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
		$this->name = 'Product Version Matrix';
		$this->description = 'Product Version Matrix';

		$this->version = '0.1';
		$this->requires = array(
			'MantisCore' => '1.2.0',
		);

		$this->author = 'John Reese';
		$this->contact = 'jreese@leetcode.net';
		$this->url = 'http://leetcode.net';
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
				name		C(128)	NOTNULL DEFAULT \" '' \"
				" ) ),
			array( 'CreateTableSQL', array( plugin_table( 'status' ), "
				bug_id		I		NOTNULL UNSIGNED PRIMARY,
				product_id	I		NOTNULL UNSIGNED PRIMARY,
				version_id	I		NOTNULL UNSIGNED PRIMARY,
				status		I		NOTNULL UNSIGNED
				" ) ),
		);
	}
}
