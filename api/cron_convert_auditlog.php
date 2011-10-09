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
            $worker = DAO_Worker::get($worker_id);
            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : 'unknown');            
		    $change_date = intval($row['change_date']);
		    $change_field = $row['change_field'];
		    $change_value = $row['change_value'];
            $groups = DAO_Group::getAll();
            $buckets = DAO_Bucket::getAll();
            $url_writer = DevblocksPlatform::getUrlService();
            $who = sprintf("%d-%s",
                $worker->id,
                DevblocksPlatform::strToPermalink($worker_name)
                );

            switch($change_field) {
                case 'is_waiting':
                    $logger->info("[Cerb5Blog.com] Audit_log processing is_waiting, ticket_id = " . $ticket_id);
                    $save = true;
                    if ($change_value) {
                        $status_to = "Waiting for reply";
                        $activity_point = 'ticket.status.waiting';	
                        $logger->info("[Cerb5Blog.com] Audit_log Status set to waiting for reply, ticket_id = " . $ticket_id);
                    } else {
                        $status_to = "Open";
                        $activity_point = 'ticket.status.open';	
                        $logger->info("[Cerb5Blog.com] Audit_log Status set to open, ticket_id = " . $ticket_id);
                    }
					$entry = array(
						//{{actor}} changed ticket {{target}} to status {{status}}
						'message' => 'activities.ticket.status',
						'variables' => array(
							'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
                            'actor' => $worker_name,
							'status' => $status_to,
							),
						'urls' => array(
                           'target' => ('c=display&mask=' . $ticket->mask),
                           'actor' => ('c=profiles&type=worker&who=' . $ticket->mask),
							)
					);
                    if ($worker_id) {
                        $actor_context_id = $worker->id;
                    } else {
                        $actor_context_id = 0;
                    }
                    $actor_context = 'cerberusweb.contexts.worker';
                    break;
                case 'is_closed':
                    $logger->info("[Cerb5Blog.com] Audit_log processing is_closed, ticket_id = " . $ticket_id);
                    $save = true;
                    if ($change_value) {
                        $status_to = "Closed";
                        $activity_point = 'ticket.status.closed';	
                        $logger->info("[Cerb5Blog.com] Audit_log Status set to closed, ticket_id = " . $ticket_id);
                    } else {
                        $status_to = "Open";
                        $activity_point = 'ticket.status.open';	
                        $logger->info("[Cerb5Blog.com] Audit_log Status set to open, ticket_id = " . $ticket_id);
                    }
					$entry = array(
						//{{actor}} changed ticket {{target}} to status {{status}}
						'message' => 'activities.ticket.status',
						'variables' => array(
							'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
                            'actor' => $worker_name,
							'status' => $status_to,
							),
						'urls' => array(
                           'target' => ('c=display&mask=' . $ticket->mask),
                           'actor' => ('c=profiles&type=worker&who=' . $ticket->mask),
							)
					);
                    $actor_context = 'cerberusweb.contexts.worker';
                    if ($worker_id) {
                        $actor_context_id = $worker->id;
                    } else {
                        $actor_context_id = 0;
                    }
                    break;
                case 'is_deleted':
                    $logger->info("[Cerb5Blog.com] Audit_log processing is_deleted, ticket_id = " . $ticket_id);
                    if ($change_value) {
                        $logger->info("[Cerb5Blog.com] Audit_log Status set to deleted, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.status.deleted';	
                        $save = true;
                        $status_to = "Deleted";
					$entry = array(
						//{{actor}} changed ticket {{target}} to status {{status}}
						'message' => 'activities.ticket.status',
						'variables' => array(
							'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
                            'actor' => $worker_name,
							'status' => $status_to,
							),
						'urls' => array(
                           'target' => ('c=display&mask=' . $ticket->mask),
                           'actor' => ('c=profiles&type=worker&who=' . $ticket->mask),
							)
					);
                        $actor_context = 'cerberusweb.contexts.worker';
                        if ($worker_id) {
                            $actor_context_id = $worker->id;
                        } else {
                            $actor_context_id = 0;
                        }
                    }
                    break;
                case 'last_action_code':
                    if (($change_value == "O") || ($change_value == "R")) {
                        $logger->info("[Cerb5Blog.com] Audit_log processing last_action_code type O or R, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.message.inbound';	
                        $save = true;
                        $addy_name = "Unknown";
                        $entry = array(
                        	//{{actor}} replied to ticket {{target}}
                            'message' => 'activities.ticket.message.inbound',
                            'variables' => array(
                                'target' => sprintf("[%s]", $ticket->mask),
                                'actor' => $addy_name,
                                ),
                            'urls' => array(
                                'target' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                )
                            );
                        $actor_context = 'cerberusweb.contexts.address';
                        $actor_context_id = 0;
                    } else if ($change_value == "W") {
                        $logger->info("[Cerb5Blog.com] Audit_log processing last_action_code type W, ticket_id = " . $ticket_id);
                        $save = true;
                        $activity_point = 'ticket.message.outbound';	
                        if ($worker_id) {
                            $worker = DAO_Worker::get($worker_id);
                            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : 'unknown');
                            $who = sprintf("%d-%s",
                                $worker->id,
                                DevblocksPlatform::strToPermalink($worker_name)
                            ); 
                        } else {
                            $worker_name = 'auto';
                        }
                        $entry = array(
                            //{{actor}} responded to ticket {{target}}
                            'message' => 'activities.ticket.message.outbound',
                            'variables' => array(
                                'target' => sprintf("[%s]", $ticket->mask),
                                'actor' => $worker_name,
                                ),
                            'urls' => array(
                                'target' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                'actor' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
                                )
                            );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = 0;
                    } else {
                        // Should never run but just in case 
                        if ($cal_all_enteries) {
                            $logger->info("[Cerb5Blog.com] Audit_log processing last_action_code type Unknown, ticket_id = " . $ticket_id);
                            $activity_point = 'ticket.message.inbound';	
                            $save = true;
                            $addy_name = "Unknown";
                            $entry = array(
                                //{{actor}} replied to ticket {{target}}
                                'message' => 'activities.ticket.message.inbound',
                                'variables' => array(
                                    'target' => sprintf("[%s]", $ticket->mask),
                                    'actor' => $addy_name,
                                    ),
                                'urls' => array(
                                    'target' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                    )
                                );
                            $actor_context = 'cerberusweb.contexts.address';
                            $actor_context_id = 0;
                        } else {
                            $save = false;
                        }
                    }
                    break;
            }

            if ($cal_all_enteries) {
                switch($change_field) {
                    case 'team_id':
                        $logger->info("[Cerb5Blog.com] Audit_log team_id processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.group.moved';	
                        $save = true;
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
                        break;
                    case 'category_id':
                        $logger->info("[Cerb5Blog.com] Audit_log category_id processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.bucket.moved';	
                        $save = true;
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
                        break; 
                    case 'spam_score':
                        $logger->info("[Cerb5Blog.com] Audit_log spam_score processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.custom.spam_score';	
                        $save = true;
                        $entry = array(
                            //{{actor}} replied to ticket {{target}}
                            'message' => 'Ticket {{ticket}} Spam Score is {{spam_score}}',
                                'variables' => array(
                                    'ticket' => sprintf("[%s]", $ticket->mask),
                                    'spam_score' => sprintf("(%s)", $change_value),
                                    ),
                                'urls' => array(
                                    'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                    )
                                );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $ticket->group_id;
                        break;
                    case 'subject':
                        $logger->info("[Cerb5Blog.com] Audit_log subject processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.custom.subject';	
                        $save = true;
                        if ($worker_id) {
                            $worker = DAO_Worker::get($worker_id);
                            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : '(auto)');
                       		$who = sprintf("%d-%s",
                                $worker->id,
                                DevblocksPlatform::strToPermalink($worker_name)
                            ); 
                            $message = 'Ticket {{ticket}} subject changed to  {{subject}} by {{worker}}';
                        } else {
                            $worker_name = '';
                            $message = 'Ticket {{ticket}} subject changed to  {{subject}} by auto';
                        }
                        $entry = array(
                            'message' => $message,
                            'variables' => array(
                                'ticket' => sprintf("[%s]", $ticket->mask),
                                'subject' => sprintf("\"%s\"", $change_value),
                                'worker' => $worker_name,
                                ),
                            'urls' => array(
                                'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                'worker' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
                                )
                            );
                        $actor_context = 'cerberusweb.contexts.worker';
                        $actor_context_id = $worker->id;
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
                    DAO_ContextActivityLog::ACTOR_CONTEXT_ID => $actor_context_id,
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
