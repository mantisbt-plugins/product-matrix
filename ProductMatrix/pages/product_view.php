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

$f_product_id = gpc_get_int( 'id' );
$t_product = PVMProduct::load( $f_product_id );

html_page_top1();
html_page_top2();
?>

<br/>
<table class="width50" align="center" cellspacing="0">

<tr>
<td>Name</td>
<td><?php echo $t_product->name ?></td>
</tr>

<?php foreach( $t_product->versions as $t_version ) { ?>
<tr>
<td><?php echo $t_version->name ?></td>
</tr>
<?php } ?>

</table>

<?php if ( $t_can_manage ) { ?>
<br/>
<form method="post" action="<?php echo plugin_page( 'version_create' ) ?>">
<table class="width50" align="center" cellspacing="0">
<input type="hidden" name="product_id" value="<?php echo $t_product->id ?>"/>

<tr>
<td>Name</td>
</tr>

<tr>
<td><input name="version_name"/></td>
</tr>

<tr>
<td><input type="submit"/></td>
</tr>

</table>
</form>
<?php } ?>

<?php
html_page_bottom1();

