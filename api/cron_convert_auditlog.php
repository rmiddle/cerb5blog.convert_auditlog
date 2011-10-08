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
		@$cal_all_enteries = $this->getParam('cal_all_enteries', '1');

        $sql = "SELECT * ";
		$sql .= "FROM ticket_audit_log ";
		$sql .= "ORDER BY id DESC";
	    $rs = $db->SelectLimit($sql, $cal_number_to_convert, 0) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg());
		$logger->info("[Cerb5Blog.com] SQL = " . $sql . " with a limit of " . $cal_number_to_convert);
		
		while($row = mysql_fetch_assoc($rs)) {
			// Loop though the records.
            $save = false;
			$id = intval($row['id']);
            $logger->info("[Cerb5Blog.com] Processing id: " . $id);
		    $ticket_id = intval($row['ticket_id']);
            $ticket = DAO_Ticket::get($ticket_id);
            $worker_id = intval($row['worker_id']);
		    $change_date = intval($row['change_date']);
		    $change_field = $row['change_field'];
		    $change_value = $row['change_value'];
            $groups = DAO_Group::getAll();
            $buckets = DAO_Bucket::getAll();
            $url_writer = DevblocksPlatform::getUrlService();

            switch($change_field) {
                case 'cron.maint':
                    break;
            }

            if ($cal_all_enteries) {
                switch($change_field) {
                    case 'team_id':
                        $logger->info("[Cerb5Blog.com] Audit_log team_id processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.group.moved';	
                        if ($worker_id) {
                            $worker = DAO_Worker::get($worker_id);
                            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : '');
                       		$who = sprintf("%d-%s",
                                $worker->id,
                                DevblocksPlatform::strToPermalink($worker_name)
                            ); 
                            $message = '{{worker}} moved ticket ({{ticket}}) to group ({{group}})';
                        } else {
                            $worker_name = '';
                            $message = 'The System moved ticket ({{ticket}}) to group ({{group}})';
                        }
                        @$ticket_group = $groups[$change_value]; /* @var $ticket_group Model_Group */
                        $entry = array(
                        'message' => $message,
						'variables' => array(
							'ticket' => sprintf("[%s]", $ticket->mask),
							'group' => sprintf("%s", $ticket_group->name),
							'worker' => $worker_name,
							),
						'urls' => array(
							'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
							'worker' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
							)
                        );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $change_value;
                        $save = true;
                        break;
                    case 'category_id':
                        $logger->info("[Cerb5Blog.com] Audit_log category_id processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.bucket.moved';	
                        if ($worker_id) {
                            $worker = DAO_Worker::get($worker_id);
                            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : '(auto)');
                       		$who = sprintf("%d-%s",
                                $worker->id,
                                DevblocksPlatform::strToPermalink($worker_name)
                            ); 
                            $message = '{{worker}} moved ticket ({{ticket}}) to bucket ({{bucket}})';
                        } else {
                            $worker_name = '';
                            $message = 'The System moved ticket ({{ticket}}) to bucket ({{bucket}})';
                        }
                        if ($change_value) {
                            @$ticket_bucket = $buckets[$change_value]; /* @var $ticket_group Model_Group */
                            $bucket_name = $ticket_bucket->name;
                        } else {
                            $bucket_name = "Inbox";
                        }
                        $entry = array(
                        'message' => $message,
						'variables' => array(
							'ticket' => sprintf("[%s]", $ticket->mask),
							'bucket' => sprintf("%s", $bucket_name),
							'worker' => $worker_name,
							),
						'urls' => array(
							'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
							'worker' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
							)
                        );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $change_value;
                        $save = true;
                        break;                        
                    default:
                        break;
                }                
            }
            if ($save == true) {
                DAO_ContextActivityLog::create(array(
                    DAO_ContextActivityLog::ACTIVITY_POINT => $activity_point,
                    DAO_ContextActivityLog::CREATED => $change_date,
                    DAO_ContextActivityLog::ACTOR_CONTEXT => $actor_context,
                    DAO_ContextActivityLog::ACTOR_CONTEXT_ID =>$actor_context_id,
                    DAO_ContextActivityLog::TARGET_CONTEXT => 'cerberusweb.contexts.ticket',
                    DAO_ContextActivityLog::TARGET_CONTEXT_ID => $ticket_id,
                    DAO_ContextActivityLog::ENTRY_JSON => json_encode($entry),
                ));
            }
            $logger->info("[Cerb5Blog.com] Removing id: " . $id);
            //$db->Execute(sprintf("DELETE QUICK FROM ticket_audit_log WHERE id = (%d)", $id));
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
        
		@$cal_all_enteries = $this->getParam('cal_all_enteries', '1');
		$tpl->assign('cal_all_enteries', $cal_all_enteries);
        
		$tpl->display($tpl_path . 'cron.tpl');
	}
 
	function saveConfigurationAction() {
		@$cal_number_to_convert = DevblocksPlatform::importGPC($_REQUEST['number_to_convert'],'integer',7);
		@$cal_all_enteries= DevblocksPlatform::importGPC($_REQUEST['cal_all_enteries'],'integer',1);
		
		$this->setParam('cal_number_to_convert', $cal_number_to_convert);
		$this->setParam('cal_all_enteries', $cal_all_enteries);
  }
};
