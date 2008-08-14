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

$t_can_manage = access_has_global_level( ADMINISTRATOR );

$t_products = PVMProduct::load_all();

html_page_top1();
html_page_top2();
?>

<br/>
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title">Products</td>
</tr>

<tr class="row-category">
<td>Name</td>
<td>Actions</td>
</tr>

<?php foreach( $t_products as $t_product ) { ?>
<tr <?php echo helper_alternate_class() ?>>
<td><a href="<?php echo plugin_page( 'product_view' ), '&id=', $t_product->id ?>"><?php echo $t_product->name ?></a></td>
<td></td>
</tr>
<?php } ?>

</table>

<?php if ( $t_can_manage ) { ?>
<br/>
<form method="post" action="<?php echo plugin_page( 'product_create' ) ?>">
<table class="width50" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2">Create Product</td>
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

