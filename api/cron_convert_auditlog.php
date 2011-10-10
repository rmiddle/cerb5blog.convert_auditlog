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
        
 		@$cal_number_to_convert = $this->getParam('cal_number_to_convert', '1000');
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
		    $ticket_id = intval($row['ticket_id']);
            $ticket = DAO_Ticket::get($ticket_id);
            $worker_id = intval($row['worker_id']);
            $worker = DAO_Worker::get($worker_id);
            $worker_name = ((!empty($worker) && $worker instanceof Model_Worker) ? $worker->getName() : '(auto)');            
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
            $logger->info("[Cerb5Blog.com] Processing Audit Log id: " . $id . " TIcket Id: " . $ticket_id);

            switch($change_field) {
                case 'next_worker_id':
                    $logger->info("[Cerb5Blog.com] Audit_log processing next_worker_id, ticket_id = " . $ticket_id);
                    $save = true;
                    if ($change_value) {
                        $activity_point = 'watcher.assigned';	
                        $watcher = DAO_Worker::get($change_value);
                        $watcher_name = ((!empty($watcher) && $watcher instanceof Model_Worker) ? $watcher->getName() : '(auto)');
                        $logger->info("[Cerb5Blog.com] Audit_log next_worker_id, ticket_id = " . $ticket_id . " watcher_name = " . $watcher_name);
                        //{{actor}} added {{watcher}} as a watcher to {{target_object}} {{target}}
                        $message = "activities.watcher.assigned";
                    } else {
                        $activity_point = "watcher.unassigned";	
                        $logger->info("[Cerb5Blog.com] Audit_log next_worker_id worker removed, ticket_id = " . $ticket_id);
                        $watcher_name = "Unknown";
                        //{{actor}} removed {{watcher}} as a watcher from {{target_object}} {{target}}
                        $message = "activities.watcher.unassigned";
                    }
					$entry = array(
						'message' => $message,
						'variables' => array(
							'target' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
                            'actor' => $worker_name,
                            'target_object' => "Ticket ",
							'watcher' => $watcher_name,
							),
						'urls' => array(
                           'target' => ('c=display&mask=' . $ticket->mask),
                           'actor' => ('c=profiles&type=worker&who=' . $who),
							)
					);
                    if ($worker_id) {
                        $actor_context_id = $worker->id;
                    } else {
                        $actor_context_id = 0;
                    }
                    $actor_context = 'cerberusweb.contexts.worker';
                    break;
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
                           'actor' => ('c=profiles&type=worker&who=' . $who),
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
                           'actor' => ('c=profiles&type=worker&who=' . $who),
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
                           'actor' => ('c=profiles&type=worker&who=' . $who),
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
                case 'cerb5blog.last_action_and_audit_log.type.merge':
                    $logger->info("[Cerb5Blog.com] Audit_log processing cerb5blog.last_action_and_audit_log.type.merge, ticket_id = " . $ticket_id);
                    $activity_point = 'ticket.merge';
                    $save = true;
                    $entry = array(
                        //{{actor}} merged ticket {{source}} with ticket {{target}}
                        'message' => 'activities.ticket.merge',
                        'variables' => array(
                            'source' => sprintf("[%s] %s", $ticket->mask, $ticket->subject),
                            'actor' => $worker_name,
                            'target' => sprintf("%s", $change_value),
                            ),
                        'urls' => array(
                            'source' => 'c=display&mask='.$ticket->mask,
                            'actor' => ('c=profiles&type=worker&who=' . $who),
                            )
                    );                    
                    $actor_context = 'cerberusweb.contexts.worker';
                    if ($worker_id) {
                        $actor_context_id = $worker->id;
                    } else {
                        $actor_context_id = 0;
                    }
                    break;
                case 'answernet.last_action_and_audit_log.type.comment':
                    $logger->info("[Cerb5Blog.com] Audit_log processing answernet.last_action_and_audit_log.type.comment = " . $ticket_id);
                    $activity_point = 'comment.create';
                    $save = true;
               		$context = DevblocksPlatform::getExtension('cerberusweb.contexts.ticket', true); /* @var $context Extension_DevblocksContext */
                    $meta = $context->getMeta($ticket->id);

                    $entry = array(
                        //{{actor}} commented on {{object}} {{target}}: {{content}}
                        'message' => 'activities.comment.create',
                        'variables' => array(
                            'object' => mb_convert_case($context->manifest->name, MB_CASE_LOWER),
                            'target' => $meta['name'],
                            'actor' => $addy_name,
                            'content' => $change_value,
                        ),
                        'urls' => array(
                            'actor' => $url_writer->writeNoProxy('c=profiles&type=worker&who='.$who, true),
                        )
                    );
                    $actor_context = 'cerberusweb.contexts.worker';
                    if ($worker_id) {
                        $actor_context_id = $worker->id;
                    } else {
                        $actor_context_id = 0;
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
                            $message = '{{worker}} moved ticket ({{ticket}}) to group ({{group}})';
                        } else {
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
                            $message = '{{worker}} moved ticket ({{ticket}}) to bucket ({{bucket}})';
                        } else {
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
                                    'actor' => $worker_name,
                                    ),
                                'urls' => array(
                                    'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                    'actor' => ('c=profiles&type=worker&who=' . $who),
                                    )
                                );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $ticket->group_id;
                        break;
                    case 'spam_training':
                        $logger->info("[Cerb5Blog.com] Audit_log spam_training processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.custom.spam_training';	
                        $save = true;
                        if ($change_value == 'N') {
                            $spam_training = "Not Spam";
                        } else {
                            $spam_training = "Spam";
                        }
                        $entry = array(
                            //{{actor}} replied to ticket {{target}}
                            'message' => 'Ticket {{ticket}} trained to {{spam_training}}',
                            'variables' => array(
                                'ticket' => sprintf("[%s]", $ticket->mask),
                                'spam_training' => sprintf("(%s)", $spam_training),
                                'actor' => $worker_name,
                                ),
                            'urls' => array(
                                'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                'actor' => ('c=profiles&type=worker&who=' . $who),                                    )
                            );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $ticket->group_id;
                        break;
                    case 'subject':
                        $logger->info("[Cerb5Blog.com] Audit_log subject processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.custom.subject';	
                        $save = true;
                        if ($worker_id) {
                            $message = 'Ticket {{ticket}} subject changed to  {{subject}} by {{worker}}';
                        } else {
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
                    case 'due_date':
                        $logger->info("[Cerb5Blog.com] Audit_log due_date processed, ticket_id = " . $ticket_id . " Due Date: " . $change_value);
                        $activity_point = 'ticket.custom.due_date';	
                        $save = true;
                        if ($change_value != 0) {
                            $message = "Ticket {{ticket}} due date change to {{due_date}} by {{worker}}";
                        }else {
                            $message = "Ticket {{ticket}} due date removed by {{worker}}";
                        }
                        $entry = array(
                            'message' => $message,
                            'variables' => array(
                                'ticket' => sprintf("[%s]", $ticket->mask),
                                'due_date' => sprintf("%s", date('Y-m-d H:i:s', $change_value)),
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
                    case 'created_date':
                        $logger->info("[Cerb5Blog.com] Audit_log created_date processed, ticket_id = " . $ticket_id);
                        $activity_point = 'ticket.custom.spam_score';	
                        $save = true;
                        $entry = array(
                            //{{actor}} replied to ticket {{target}}
                            'message' => 'Ticket {{ticket}} created at {{created_date}}',
                                'variables' => array(
                                'ticket' => sprintf("[%s]", $ticket->mask),
                                'created_date' => sprintf("(%s)", date('Y-m-d H:i:s', $change_value)),
                                'actor' => $worker_name,
                                ),
                            'urls' => array(
                                'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                'actor' => ('c=profiles&type=worker&who=' . $who),
                                )
                            );
                        $actor_context = 'cerberusweb.contexts.group';
                        $actor_context_id = $ticket->group_id;
                        break;
                    default:
                        if ($save == false) {
                            $logger->info("[Cerb5Blog.com] Audit_log default processed, ticket_id = " . $ticket_id);
                            $activity_point = 'ticket.custom.default';	
                            $save = true;
                            $entry = array(
                                'message' => 'Ticket {{ticket}}: {{change_field}} - {{change_value}}',
                                'variables' => array(
                                    'ticket' => sprintf("[%s]", $ticket->mask),
                                    'change_value' => sprintf("(%s)", $change_value),
                                    'change_field' => sprintf("(%s)", $change_field),
                                    ),
                                'urls' => array(
                                    'ticket' => $url_writer->writeNoProxy('c=display&mask='.$ticket->mask, true),
                                    )
                                );
                            $actor_context = 'cerberusweb.contexts.group';
                            $actor_context_id = $ticket->group_id;
                        }
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
            $logger->info("[Cerb5Blog.com] Removing Audit Log id: " . $id . " TIcket Id: " . $ticket_id);
            $db->Execute(sprintf("DELETE QUICK FROM ticket_audit_log WHERE id = (%d)", $id));
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
        
		@$cal_number_to_convert = $this->getParam('cal_number_to_convert', '1000');
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
