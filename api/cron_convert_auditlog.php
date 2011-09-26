<?php

class Cerb5BlogConvertAuditLogCron extends CerberusCronPageExtension {
    const EXTENSION_ID = 'cerb5blog.convert_auditlog.cron';

	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$db = DevblocksPlatform::getDatabaseService();
        $translate = DevblocksPlatform::getTranslationService();
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Cerb5Blog.com] Running Convert Audit Log Cron Task.");
        $tables = $db->metaTables();

		@ini_set('memory_limit','128M');
        
        if(!isset($tables['ticket_audit_log'])) {
            $logger->info("[Cerb5Blog.com] Finished processing No tables to convert.");
            return;
        }
        
 		@$cal_number_to_convert = $this->getParam('cal_number_to_convert', '100');

        $sql = "SELECT * ";
		$sql .= "FROM ticket_audit_log ";
		$sql .= "ORDER BY id DESC";
	    $rs = $db->SelectLimit($sql, $cal_number_to_convert, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$logger->info("[Cerb5Blog.com] SQL = " . $sql . " with a limit of " . $cal_number_to_convert);
		
		while($row = mysql_fetch_assoc($rs)) {
			// Loop though the records.
			$id = intval($row['id']);
		    $ticket_id = intval($row['ticket_id']);
		    $worker_id = intval($row['worker_id']);
		    $change_date = intval($row['change_date']);
		    $change_field = $row['change_field'];
		    $change_value = $row['change_value'];

            $logger->info("[Cerb5Blog.com] Looking at id = " . $id);			
/*
            DAO_ContextActivityLog::create(array(
                DAO_ContextActivityLog::ACTIVITY_POINT => $activity_point,
                DAO_ContextActivityLog::CREATED => $change_date,
                DAO_ContextActivityLog::ACTOR_CONTEXT => $actor_context,
                DAO_ContextActivityLog::ACTOR_CONTEXT_ID =>$actor_context_id,
                DAO_ContextActivityLog::TARGET_CONTEXT => 'cerberusweb.contexts.ticket',
                DAO_ContextActivityLog::TARGET_CONTEXT_ID => $id,
                DAO_ContextActivityLog::ENTRY_JSON => json_encode($entry_array),
            ));
*/
		}
		$logger->info("[Cerb5Blog.com] Finished processing Convert Audit Log Cron Job.");
  }
 
	function configure($instance) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $tpl_path);
        $db = DevblocksPlatform::getDatabaseService();
        $tables = $db->metaTables();

        if(!isset($tables['ticket_audit_log'])) {
            $tpl->display($tpl_path . 'no_audit_log.tpl');
            return;
        }

        
        $sql = "SELECT COUNT(id) AS number_of_records ";
		$sql .= "FROM ticket_audit_log ";
		$rs = $db->Execute($sql);
		$row = mysql_fetch_assoc($rs);
        
		@$cal_number_of_records = $row['number_of_records'];
		$tpl->assign('cal_number_of_records', $cal_number_of_records);
 
        if($cal_number_of_records < 1) {
            $tpl->display($tpl_path . 'no_audit_log.tpl');
            return;
        }
        
		@$cal_number_to_convert = $this->getParam('cal_number_to_convert', '100');
		$tpl->assign('cal_number_to_convert', $cal_number_to_convert);
        
		@$cal_all_enteries = $this->getParam('cal_all_enteries', '0');
		$tpl->assign('cal_all_enteries', $cal_all_enteries);
        
		$tpl->display($tpl_path . 'cron.tpl');
	}
 
	function saveConfigurationAction() {
		@$cal_number_to_convert = DevblocksPlatform::importGPC($_REQUEST['number_to_convert'],'integer',7);
		@$cal_all_enteries= DevblocksPlatform::importGPC($_REQUEST['cal_all_enteries'],'integer',0);
		
		$this->setParam('cal_number_to_convert', $cal_number_to_convert);
		$this->setParam('cal_all_enteries', $cal_all_enteries);
  }
};
