{$translate->_('cerb5blog.convert_auditlog.config.number_of_records')} <b>{$cal_number_of_records}</b><br><br>

{$translate->_('cerb5blog.convert_auditlog.config.number_to_convert')} 
<input type="text" name="number_to_convert" maxlength="6" size="4" value="{$cal_number_to_convert}">
<br>
<br>

{$translate->_('cerb5blog.convert_auditlog.config.all_enteries.warn')}<br>
<label><input type="radio" name="cal_all_enteries" value="1" {if $cal_all_enteries}checked="checked"{/if}> {$translate->_('cerb5blog.convert_auditlog.config.all_enteries.all')|capitalize}</label>
<label><input type="radio" name="cal_all_enteries" value="0" {if !$cal_all_enteries}checked="checked"{/if}> {$translate->_('cerb5blog.convert_auditlog.config.all_enteries.activity')}</label>

<br>
<br>
