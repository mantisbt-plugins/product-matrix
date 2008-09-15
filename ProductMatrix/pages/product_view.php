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
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2">View Product: <?php echo $t_product->name ?></td>
</tr>

<tr class="row-category">
<td>Version</td>
<td>Actions</td>
</tr>

<?php foreach( $t_product->versions as $t_version ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td><?php echo $t_version->name ?></td>
<td></td>
</tr>
<?php } ?>

<tr>
<td colspan="2">
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
<form method="post" action="<?php echo plugin_page( 'version_create' ) ?>">
<table class="width50" align="center" cellspacing="1">
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>

<tr>
<td class="form-title" colspan="2">Create Version</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">Name</td>
<td><input name="version_name"/></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit"/></td>
</tr>

</table>
</form>
<?php } ?>

<?php
html_page_bottom1();

