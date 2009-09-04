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

auth_reauthenticate();
access_ensure_global_level( plugin_config_get( 'manage_threshold' ) );

html_page_top1( plugin_lang_get( 'title' ) );
html_page_top2();

print_manage_menu();

$t_status_default = plugin_config_get( 'status_default' );
$t_status_cascade = plugin_config_get( 'status_cascade' );
?>

<br/>
<form action="<?php echo plugin_page( 'config_update' ) ?>" method="post">
<?php echo form_security_field( 'plugin_ProductMatrix_config_update' ) ?>
<table class="width75" align="center" cellspacing="1">

<tr>
<td class="form-title" colspan="2"><?php echo plugin_lang_get( 'title' ), ': ', plugin_lang_get( 'configuration' ) ?></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'view_threshold' ) ?></td>
<td><select name="view_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'view_threshold' ) ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'update_threshold' ) ?></td>
<td><select name="update_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'update_threshold' ) ) ?></select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'manage_threshold' ) ?></td>
<td><select name="manage_threshold"><?php print_enum_string_option_list( 'access_levels', plugin_config_get( 'manage_threshold' ) ) ?></select></td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'status_default' ) ?></td>
<td><select name="status_default">
	<option value="0"><?php echo plugin_lang_get( 'status_na' ) ?></option>
	<?php foreach( plugin_config_get( 'status' ) as $t_status_id => $t_status_name ) { ?>
	<option value="<?php echo (int) $t_status_id ?>" <?php echo $t_status_default == $t_status_id ? 'selected="selected"' : '' ?>><?php echo string_display_line( $t_status_name ) ?></option>
	<?php } ?>
</select></td>
</tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'status_cascade' ) ?></td>
<td><select name="status_cascade">
	<option value="<?php echo OFF ?>" <?php echo $t_status_cascade == OFF ? 'selected="selected"' : ''?>><?php echo plugin_lang_get( 'status_cascade_off' ) ?></option>
	<option value="<?php echo ON ?>" <?php echo $t_status_cascade == ON ? 'selected="selected"' : ''?>><?php echo plugin_lang_get( 'status_cascade_on' ) ?></option>
	<option value="<?php echo PVM_REPORT ?>" <?php echo $t_status_cascade == PVM_REPORT ? 'selected="selected"' : ''?>><?php echo plugin_lang_get( 'status_cascade_report' ) ?></option>
	<option value="<?php echo PVM_UPDATE ?>" <?php echo $t_status_cascade == PVM_UPDATE ? 'selected="selected"' : ''?>><?php echo plugin_lang_get( 'status_cascade_edit' ) ?></option>
</select></td>
</tr>

<tr><td class="spacer"></td></tr>

<tr <?php echo helper_alternate_class() ?>>
<td class="category"><?php echo plugin_lang_get( 'enabled_features' ) ?></td>
<td>
	<label><input type="checkbox" name="common_platform" <?php echo ( plugin_config_get( 'common_platform' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'common_platform' ) ?></label><br/>

	<label><input type="checkbox" name="reverse_inheritence" <?php echo ( plugin_config_get( 'reverse_inheritence' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'reverse_inheritence' ) ?></label><br/>

	<label><input type="checkbox" name="report_status" <?php echo ( plugin_config_get( 'report_status' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'report_status' ) ?></label><br/>

	<label><input type="checkbox" name="product_status" <?php echo ( plugin_config_get( 'product_status' ) ? 'checked="checked" ' : '' ) ?>/>
	<?php echo plugin_lang_get( 'product_status' ) ?></label><br/>
</td>
</tr>

<tr>
<td class="center" colspan="2"><input type="submit" value="<?php echo plugin_lang_get( 'update' ), ' ', plugin_lang_get( 'configuration' ) ?>"/></td>
</tr>

</table>
</form>

<?php
html_page_bottom1( __FILE__ );

