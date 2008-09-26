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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_manage = access_has_global_level( plugin_config_get( 'manage_threshold' ) );

$f_product_id = gpc_get_int( 'id' );
$t_product = PVMProduct::load( $f_product_id );

html_page_top1();
html_page_top2();
?>

<br/>
<?php if ( $t_can_manage ) { ?>
<form action="<?php echo plugin_page( 'product_update' ) ?>" method="post"/>
<?php echo form_security_field( 'ProductMatrix_product_update' ) ?>
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>
<?php } ?>

<table class="width50" align="center" cellspacing="1">

<tr>
<?php if ( $t_can_manage ) { ?>
<td class="form-title" colspan="4">View Product: <input name="product_name" value="<?php echo $t_product->name ?>"/></td>
<?php } else { ?>
<td class="form-title" colspan="4">View Product: <?php echo $t_product->name ?></td>
<?php } ?>
</tr>

<tr class="row-category">
<td>Version</td>
<td>Released</td>
<td>Obsolete</td>
<td>Actions</td>
</tr>

<?php foreach( $t_product->versions as $t_version ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<?php if ( $t_can_manage ) { ?>
<td><?php echo '<input name="version_', $t_version->id, '_name" value="', $t_version->name, '"/>' ?></td>
<?php } else { ?>
<td><?php echo $t_version->name ?></td>
<?php } ?>

<td class="center <?php echo $t_version->released ? 'PVMreleased' : '' ?>">
<?php if ( $t_can_manage ) { echo '<input type="checkbox" name="version_', $t_version->id, '_released" ',
	( $t_version->released ? ' checked="checked"' : '' ), '/>'; } ?>
</td>
<td class="center <?php echo $t_version->obsolete ? 'PVMobsolete' : '' ?>">
<?php if ( $t_can_manage ) { echo '<input type="checkbox" name="version_', $t_version->id, '_obsolete" ',
	( $t_version->obsolete ? ' checked="checked"' : '' ), '/>'; } ?>
</td>
<td class="center"><?php
echo print_bracket_link( plugin_page( 'version_delete' ) .
	'&id=' . $t_version->id . form_security_param( 'ProductMatrix_version_delete' ), plugin_lang_get( 'delete' ) );
?></td>
</tr>
<?php } ?>

<tr>
<td colspan="2">
	<?php if ( $t_can_manage ) { ?>
	<input type="submit" value="Update Product"/>
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
<table class="width50" align="center" cellspacing="1">
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
	<option value="0">--</option>
	<?php foreach( $t_product->versions as $t_version ) { ?>
	<option value="<?php echo $t_version->id ?>"><?php echo $t_version->name ?></option>
	<?php } ?>
	</select>
</td>
</tr>
<?php } else { ?>
<input type="hidden" name="parent_id" value="0"/></td>
<?php } ?>

<tr>
<td class="center" colspan="2"><input type="submit"/></td>
</tr>

</table>
</form>
<?php } ?>

<?php
html_page_bottom1();

