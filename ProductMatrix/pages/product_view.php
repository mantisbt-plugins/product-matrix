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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_manage = access_has_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'id' );
$t_product = PVMProduct::load( $f_product_id );

html_page_top1();
html_page_top2();
?>

<br/>
<table class="productmatrix width60" align="center" cellspacing="1">
<tr>
<td class="form-title" colspan="3">View Product: <?php echo $t_product->name ?></td>
<td class="right"><?php print_bracket_link( plugin_page( 'change_log&id=' ) . $f_product_id, plugin_lang_get( 'change_log' ) ) .
	print_bracket_link( plugin_page( 'roadmap&id=' ) . $f_product_id, plugin_lang_get( 'roadmap' ) ) .
	print_bracket_link( plugin_page( 'products' ), 'Back' ) ?></td>
</tr>

<?php if ( count( $t_product->platforms ) > 0 ) { ?>
<tr class="row-category">
<td colspan="2">Platform</td>
<td>Obsolete</td>
<td>Actions</td>
</tr>

<?php foreach( $t_product->platforms as $t_platform ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td colspan="2"><?php echo $t_platform->name ?></td>

<td class="center <?php echo $t_platform->obsolete ? 'PVMobsolete' : '' ?>"></td>
<td class="center"><?php if ( $t_can_manage ) {
echo print_bracket_link( plugin_page( 'platform_delete' ) .
	'&id=' . $t_platform->id . form_security_param( 'ProductMatrix_platform_delete' ), plugin_lang_get( 'delete' ) );

} ?></td>
</tr>
<?php } ?>

<tr><td class="spacer"></td><tr>

<?php } ?>

<tr class="row-category">
<td>Version</td>
<td>Released</td>
<td>Obsolete</td>
<td>Actions</td>
</tr>

<?php foreach( $t_product->version_tree_list() as $t_node ) { list( $t_version, $t_depth ) = $t_node; ?>
<tr <?php echo helper_alternate_class() ?>>
<td><?php echo str_pad( '', $t_depth, '-' ), ' ', $t_version->name ?></td>

<td class="center <?php echo $t_version->released ? 'PVMreleased' : '' ?>"></td>
<td class="center <?php echo $t_version->obsolete ? 'PVMobsolete' : '' ?>"></td>
<td class="center"><?php if ( $t_can_manage ) {
echo print_bracket_link( plugin_page ( 'roadmap&name=' ) . $t_version->name . '&id=' . $f_product_id, plugin_lang_get( 'roadmap' ) ) .
print_bracket_link( plugin_page( 'version_delete' ) .
	'&id=' . $t_version->id . form_security_param( 'ProductMatrix_version_delete' ), plugin_lang_get( 'delete' ) );
} ?></td>
</tr>
<?php } ?>

<tr>
<td colspan="2">
	<?php if ( $t_can_manage ) { ?>
	<form method="post" action="<?php echo plugin_page( 'product_manage' ), '&id=', $t_product->id ?>">
	<input type="submit" value="Edit Product"/>
	</form>
	<?php } ?>
</td>
<td colspan="2" class="right">
	<?php if ( $t_can_manage ) { ?>
	<form method="post" action="<?php echo plugin_page( 'product_delete' ) ?>"/>
	<?php echo form_security_field( 'ProductMatrix_product_delete' ) ?>
	<input type="hidden" name="id" value="<?php echo $t_product->id ?>"/>
	<input type="submit" value="Delete Product"/>
	</form>
	<?php } ?>
</td>
</tr>

</table>

<?php if ( $t_can_manage ) { ?>
<br/>
<form method="post" action="<?php echo plugin_page( 'version_add' ) ?>">
<?php echo form_security_field( 'ProductMatrix_version_add' ) ?>
<table class="width60" align="center" cellspacing="1">
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>

<tr>
<td class="form-title" colspan="2">Add Version</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">Name</td>
<td><input name="version_name"/></td>
</tr>

<?php if ( count( $t_product->versions ) > 0 ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="category">Parent Version</td>
<td>
	<select name="parent_id">
	<?php $t_product->select_versions() ?>
	</select>
</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">Migrate Status</td>
<td>
	<select name="migrate_id">
	<?php $t_product->select_versions() ?>
	</select>
</td>
</tr>

<?php } else { ?>
<input type="hidden" name="parent_id" value="0"/>
<input type="hidden" name="migrate_id" value="0"/>
<?php } ?>

<tr>
<td class="center" colspan="2"><input type="submit"/></td>
</tr>

</table>
</form>

<br/>
<form method="post" action="<?php echo plugin_page( 'platform_add' ) ?>">
<?php echo form_security_field( 'ProductMatrix_platform_add' ) ?>
<table class="width60" align="center" cellspacing="1">
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>

<tr>
<td class="form-title" colspan="2">Add Platform</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">Name</td>
<td><input name="platform_name"/></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit"/></td>
</tr>

</table>
</form>
<?php } ?>

<?php
html_page_bottom1();

