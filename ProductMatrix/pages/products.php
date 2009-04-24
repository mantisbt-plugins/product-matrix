<?php
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

access_ensure_global_level( plugin_config_get( 'view_threshold' ) );
$t_can_manage = access_has_global_level( plugin_config_get( 'manage_threshold' ) );

$t_products = PVMProduct::load_all( true );

html_page_top1();
html_page_top2();
?>

<br/>
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title">Products</td>
<td class="right" colspan="2"><?php if ( $t_can_manage ) print_bracket_link( plugin_page( 'config_page' ), plugin_lang_get( 'configuration' ) ); ?></td>
</tr>

<tr class="row-category">
<td>Name</td>
<td>Versions</td>
<td>Platforms</td>
</tr>

<?php foreach( $t_products as $t_product ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td class="center"><a href="<?php echo plugin_page( 'product_view' ), '&id=', $t_product->id ?>"><?php echo $t_product->name ?></a></td>
<td class="center"><?php echo count( $t_product->versions ) ?></td>
<td class="center"><?php echo count( $t_product->platforms ) ?></td>
</tr>
<?php } ?>

</table>

<?php if ( $t_can_manage ) { ?>
<br/>
<form method="post" action="<?php echo plugin_page( 'product_add' ) ?>">
<?php echo form_security_field( 'ProductMatrix_product_add' ) ?>
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2">Add Product</td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category">Name</td>
<td><input name="product_name"/></td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit"/></td>
</tr>

</table>
</form>
<?php } ?>

<?php
html_page_bottom1();

