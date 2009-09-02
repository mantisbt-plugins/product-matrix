/*
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
*/

$(document).ready( function() {
	var speed = 200;
	var statusrows = $('tr.pvmstatusrow');
	var products = $('table.pvmproduct tr.row-category');

	// default all children versions to collapsed 
	statusrows.each( function(index) {
			if ( $(this).hasClass("pvmchild") ) {
				$(this).hide();
			}
			if ( $(this).attr("collapse") != "" ) {
				$(this).removeClass("pvmexpanded");
				$(this).addClass("pvmcollapsed");
			}
		});

	products.each( function(index) {
			$(this).removeClass("pvmexpanded");
			$(this).addClass("pvmcollapsed");
		});

	/**
	 * Handle collapsing of child version status rows based on the list of
	 * child ids in the 'collapse' attribute.
	 */
	function PVMStatusCollapse( item, action ) {
		if ( $(item).attr("collapse") == "" && action == "" ) { return; }
		var children = $(item).attr("collapse").split(":");

		/**
		 * Filters out the status rows that are children of the given row.
		 */
		function PVMStatusCollapseFilter(index) {
			var item_name = $(this).attr("id");

			for( i=0; i < children.length; i++) {
				var child_name = "pvmversion" + children[ i ];
				if ( item_name == child_name ) {
					return true;
				}
			}

			return false;
		}

		var statuses = statusrows.filter( PVMStatusCollapseFilter );
		var collapsed = $(item).hasClass("pvmcollapsed") ? "yes" : "no";

		// initial action
		if ( action == "" ) {

			// expand a block
			if ( collapsed == "yes" ) {
				statuses.each( function(index) {
						PVMStatusCollapse( this, "expand" );
					});
				$(item).removeClass("pvmcollapsed");
				$(item).addClass("pvmexpanded");

			// collapse a block
			} else if ( collapsed == "no" ) {
				statuses.each( function(index) {
						PVMStatusCollapse( this, "collapse" );
					});
				$(item).removeClass("pvmexpanded");
				$(item).addClass("pvmcollapsed");
			}

		// expanding child blocks
		} else if ( action == "expand" ) {
			$(item).fadeIn(speed);

			// leave collapsed children alone
			if ( collapsed == "yes" ) {

			// expand uncollapsed children
			} else if ( collapsed == "no" ) {
				statuses.each( function(index) {
						PVMStatusCollapse( this, "expand" );
					});
			}

		// collapsing child blocks
		} else if ( action == "collapse" ) {
			$(item).fadeOut(speed);

			// leave collapsed children alone
			if ( collapsed == "yes" ) {

			// expand uncollapsed children
			} else if ( collapsed == "no" ) {
				statuses.each( function(index) {
						PVMStatusCollapse( this, "collapse" );
					});
			}

		}
	}

	// Add the collapse behavior to appropriate rows
	statusrows.click( function() { PVMStatusCollapse( this, "" ); } );

	// Add the collapse-all behavior to product labels
	products.click( function() {
			var statuses = $(this).siblings("tr.pvmstatusrow");
			var collapsed = $(this).hasClass("pvmcollapsed") ? "yes" : "no";

			if ( collapsed == "yes" ) {
				statuses.each( function(index) {
						$(this).fadeIn(speed);
						if ( $(this).attr("collapse") != "" ) {
							$(this).removeClass("pvmcollapsed");
							$(this).addClass("pvmexpanded");
						}
					} );
				$(this).removeClass("pvmcollapsed");
				$(this).addClass("pvmexpanded");

			} else if ( collapsed == "no" ) {
				statuses.each( function(index) {
						if ( $(this).hasClass("pvmtoplevel") ) {
						} else {
							$(this).fadeOut(speed);
						}
						if ( $(this).attr("collapse") != "" ) {
							$(this).removeClass("pvmexpanded");
							$(this).addClass("pvmcollapsed");
						}
					} );
				$(this).removeClass("pvmexpanded");
				$(this).addClass("pvmcollapsed");
			}
		} );
} );

