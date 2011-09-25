<?php

class Cerb5BlogConvertAuditLogCron extends CerberusCronPageExtension {
    const EXTENSION_ID = 'cerb5blog.convert_auditlog.cron';

	function run() {
		$logger = DevblocksPlatform::getConsoleLog();
		$db = DevblocksPlatform::getDatabaseService();
        $translate = DevblocksPlatform::getTranslationService();
		$logger = DevblocksPlatform::getConsoleLog();
		$logger->info("[Cerb5Blog.com] Running Convert Audit Log Cron Task.");

		@ini_set('memory_limit','128M');
        
 /*
        @$ac_only_unassigned = $this->getParam('only_unassigned', 0);

       $sql = "SELECT t.id ";
		$sql .= "FROM ticket t ";
		$sql .= sprintf("WHERE t.updated_date < %d  ", $close_time);
		$sql .= "AND t.is_waiting = 1 ";
		$sql .= "AND t.is_closed = 0 ";
		$sql .= "GROUP BY t.id ";
		$sql .= "ORDER BY t.id ";
		$logger->info("[Cerb5Blog.com] SQL = " . $sql);
		
		$rs = $db->Execute($sql);
		while($row = mysql_fetch_assoc($rs)) {
			// Loop though the records.
			$id = intval($row['id']);
			
            $context_workers = CerberusContexts::getWatchers(CerberusContexts::CONTEXT_TICKET, $id);
			if(($ac_only_unassigned == 1) && (count($context_workers)>0)) {
				$logger->info("[Cerb5Blog.com] Worker assigned but we are only closing tickets without a worker.");
			} else {
                if($ac_open_or_close) {
                    $logger->info("[Cerb5Blog.com] " . $translate->_('cerb5blog.auto_close.cron.close') . " " . $id);
                    $close_message = $translate->_('cerb5blog.auto_close.cron.auto_close');
                } else {
                    $logger->info("[Cerb5Blog.com] " . $translate->_('cerb5blog.auto_close.cron.open') . " " . $id);
                    $close_message = $translate->_('cerb5blog.auto_close.cron.auto_open');
                }
				if (class_exists('DAO_TicketAuditLog',true)):
					// Code that requires time tracker to be enabled.
					$fields = array(
						DAO_TicketAuditLog::TICKET_ID => $id,
						DAO_TicketAuditLog::WORKER_ID => 0,
						DAO_TicketAuditLog::CHANGE_DATE => time(),
						DAO_TicketAuditLog::CHANGE_FIELD => "cerb5blog.auto_close.auto_closed",
						DAO_TicketAuditLog::CHANGE_VALUE => $close_message,
					);
					$log_id = DAO_TicketAuditLog::create($fields);
					unset($fields);
				endif;

                if($ac_open_or_close) {
                    $fields[DAO_Ticket::IS_CLOSED] = 1;
                } else {
                    $fields[DAO_Ticket::IS_CLOSED] = 0;
                }	
                $fields[DAO_Ticket::IS_WAITING] = 0;
                $fields[DAO_Ticket::IS_DELETED] = 0;
                DAO_Ticket::update($id, $fields);
                unset($fields);
			}
		}
*/
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

        
		@$cal_number_of_records = 179;
		$tpl->assign('cal_number_of_records', $cal_number_of_records);
 
		@$cal_number_to_convert = $this->getParam('cal_number_to_convert', '100');
		$tpl->assign('cal_number_to_convert', $cal_number_to_convert);
        
		$tpl->display($tpl_path . 'cron.tpl');
	}
 
	function saveConfigurationAction() {
		@$cal_number_to_convert = DevblocksPlatform::importGPC($_REQUEST['number_to_convert'],'integer',7);
		
		$this->setParam('cal_number_to_convert', $cal_number_to_convert);
  }
};
