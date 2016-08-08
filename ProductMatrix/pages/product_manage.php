<?php
# Copyright (C) 2008-2010, 2016  John Reese
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

access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

$t_reverse_inherit = plugin_config_get( 'reverse_inheritence' );

$f_product_id = gpc_get_int( 'id' );
$t_product = PVMProduct::load( $f_product_id );

html_page_top1();
html_page_top2();
?>

<br/>
<form action="<?php echo plugin_page( 'product_update' ) ?>" method="post"/>
<?php echo form_security_field( 'ProductMatrix_product_update' ) ?>
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>

<table class="productmatrix width90" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="3">Manage Product: <input name="product_name" value="<?php echo $t_product->name ?>"/></td>
<td class="right"><?php print_bracket_link( plugin_page( 'product_view' ) . '&id=' . $t_product->id, 'Back' ) ?></td>
</tr>

<?php if ( count( $t_product->platforms ) > 0 ) { ?>
<tr class="row-category">
<td colspan="2">Platform</td>
<td>Obsolete</td>
<td <?php echo $t_reverse_inherit ? 'colspan="2"' : '' ?>></td>
<td>Delete</td>
</tr>

<?php foreach( $t_product->platforms as $t_platform ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td colspan="2">
	<input name="platform_<?php echo $t_platform->id ?>_name" value="<?php echo $t_platform->name ?>" size="15"/>
</td>
<td class="center <?php echo $t_platform->obsolete ? 'PVMobsolete' : '' ?>">
	<input type="checkbox" name="platform_<?php echo $t_platform->id ?>_obsolete" <?php echo $t_platform->obsolete ? ' checked="checked"' : '' ?>/>
</td>
<td <?php echo $t_reverse_inherit ? 'colspan="2"' : '' ?>></td>
<td class="center">
	<input type="checkbox" name="platform_<?php echo $t_platform->id ?>_delete" />
</td>
</tr>
<?php } ?>

<tr><td class="spacer"></td><tr>

<?php } ?>

<tr class="row-category">
<td>Version</td>
<td>Released</td>
<td>Obsolete</td>
<td>Parent</td>
<?php if ( $t_reverse_inherit ) { ?>
<td>Inherit From</td>
<?php } ?>
<td>Delete</td>
</tr>

<?php foreach( $t_product->version_tree_list() as $t_node ) { list( $t_version, $t_depth ) = $t_node; ?>
<tr <?php echo helper_alternate_class() ?>>
<td><?php echo str_pad( '', $t_depth, '-' ), ' <input name="version_', $t_version->id, '_name" value="', $t_version->name, '" size="10"/>' ?></td>

<td class="center <?php echo $t_version->released ? 'PVMreleased' : '' ?>">
<?php echo '<input type="checkbox" name="version_', $t_version->id, '_released" ',
	( $t_version->released ? ' checked="checked"' : '' ), '/>'; ?>
</td>
<td class="center <?php echo $t_version->obsolete ? 'PVMobsolete' : '' ?>">
<?php echo '<input type="checkbox" name="version_', $t_version->id, '_obsolete" ',
	( $t_version->obsolete ? ' checked="checked"' : '' ), '/>'; ?>
</td>
<td class="center">
	<select name="version_<?php echo $t_version->id ?>_parent">
	<?php $t_product->select_versions( $t_version->parent_id ) ?>
	</select>
</td>
<?php if ( $t_reverse_inherit ) { ?>
<td>
	<?php if ( count( $t_product->version_tree[ $t_version->id ] ) ) { ?>
	<select name="version_<?php echo $t_version->id ?>_inherit_id">
	<?php $t_product->select_child_versions( $t_version->id, $t_version->inherit_id ) ?>
	</select>
	<?php } ?>
</td>
<?php } ?>
<td class="center">
	<input type="checkbox" name="version_<?php echo $t_version->id ?>_delete" />
</td>
</tr>
<?php } ?>

<tr>
<td colspan="5" class="center">
	<input type="submit" value="Update Product"/>
</td>
</tr>

</table>
</form>

<?php
html_page_bottom1();

