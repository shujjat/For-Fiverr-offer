<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class content extends Admin_Controller {

	//--------------------------------------------------------------------

	public function __construct() 
	{
		parent::__construct();
        date_default_timezone_set('Asia/Karachi');
		$this->auth->restrict('Messages.Content.View');
		$this->load->model('messages_model', null, true);
		$this->load->model('campuses/campuses_model', null, true);
		$this->load->model('institutes/institutes_model');
        $this->load->model('students/students_model');        
		$this->load->model('credits/credits_model');
        $this->load->model('employees/employees_model');
        $this->load->model('ims_users/ims_users_model');
        $this->load->model('parents/parents_model');
       // $this->load->model('sms_queue_model');
        
        $this->lang->load('messages/school','english');
        //$this->lang->load('messages/school','pure_urdu');
		
		
		Assets::add_css('flick/jquery-ui-1.8.13.custom.css');
		Assets::add_css('jquery-ui-timepicker.css');
		Assets::add_js('jquery-ui-timepicker-addon.js');
        Assets::add_js('ajax.js');
        Template::set_block('sub_nav', 'content/_sub_nav');
	}
	
	//--------------------------------------------------------------------

	/*
		Method: index()
		
		Displays a list of form data.
	*/
	public function index() 
	{
	   Template::set('toolbar_title', "Manage Messages");
	   Template::render();
	}
	
	//--------------------------------------------------------------------

	/*
		Method: create()
		
		Creates a Messages object.
	*/
	public function create() 
	{
		$this->auth->restrict('Messages.Content.Create');

		if ($this->input->post('submit_button'))
		{
			if ($insert_id = $this->save_messages())
			{
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('messages_act_create_record').': ' . $insert_id . ' : ' . $this->input->ip_address(), 'messages');
					
				Template::set_message(lang("messages_create_success"), 'success');
				Template::redirect(SITE_AREA .'/content/messages');
			}
			else 
			{
				Template::set_message(lang('messages_create_failure') . $this->messages_model->error, 'error');
			}
		}
	
		
		Template::set('toolbar_title', lang('messages_create') . ' Messages');
		Template::render();
	}
	
	//--------------------------------------------------------------------

	/*
		Method: edit()
		
		Allows editing of Messages data.
	*/
	public function edit() 
	{
		$this->auth->restrict('Messages.Content.Edit');

		$id = (int)$this->uri->segment(5);
		
		if (empty($id))
		{
			Template::set_message(lang('messages_invalid_id'), 'error');
			redirect(SITE_AREA .'/content/messages');
		}
	
		if ($this->input->post('submit_button'))
		{
			if ($this->save_messages('update', $id))
			{
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('messages_act_edit_record').': ' . $id . ' : ' . $this->input->ip_address(), 'messages');
					
				Template::set_message(lang('messages_edit_success'), 'success');
			}
			else 
			{
				Template::set_message(lang('messages_edit_failure') . $this->messages_model->error, 'error');
			}
		}
		
		Template::set('messages', $this->messages_model->find($id));
	
		Template::set('toolbar_title', lang('messages_edit_heading'));
		Template::set('toolbar_title', lang('messages_edit') . ' Messages');
		Template::render();		
	}
	
	//--------------------------------------------------------------------

	/*
		Method: delete()
		
		Allows deleting of Messages data.
	*/
	public function delete($id) 
	{	
		$this->auth->restrict('Messages.Content.Delete');

		$this->load->model('statusmanager/status_model');
        if (!empty($id))
		{	
		   if ($this->status_model->change_status($id,'deleted','messages'))
            {
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('messages_act_delete_record').': ' . $id . ' : ' . $this->input->ip_address(), 'messages');
					
				Template::set_message(lang('messages_delete_success'), 'success');
			} else
			{
				Template::set_message(lang('messages_delete_failure') . $this->messages_model->error, 'error');
			}
		}
		
	
	}
    
    public function restore($id) 
	{	
		$this->auth->restrict('Messages.Content.Restore');

		$this->load->model('statusmanager/status_model');
        if (!empty($id))
		{	
		   if ($this->status_model->change_status($id,'restore','messages'))
            {
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('messages_act_delete_record').': ' . $id . ' : ' . $this->input->ip_address(), 'messages');
					
				Template::set_message(lang('messages_delete_success'), 'success');
			} else
			{
				Template::set_message(lang('messages_delete_failure') . $this->messages_model->error, 'error');
			}
		}
		
	
	}
	
	//--------------------------------------------------------------------
   

	//--------------------------------------------------------------------
	// !PRIVATE METHODS
	//--------------------------------------------------------------------
	
	/*
		Method: save_messages()
		
		Does the actual validation and saving of form data.
		
		Parameters:
			$type	- Either "insert" or "update"
			$id		- The ID of the record to update. Not needed for inserts.
		
		Returns:
			An INT id for successful inserts. If updating, returns TRUE on success.
			Otherwise, returns FALSE.
	*/
	private function save_messages($type='insert', $id=0) 
	{	
					
		$this->form_validation->set_rules('title','Title','required|max_length[50]');			
		$this->form_validation->set_rules('body','Body','required');			
		$this->form_validation->set_rules('institute_id','Institute Id','max_length[11]');			
		$this->form_validation->set_rules('campus_id','Campus Id','max_length[11]');			
		$this->form_validation->set_rules('remarks','Remarks','');			
		$this->form_validation->set_rules('status','Status','max_length[150]');			
		$this->form_validation->set_rules('created_by','Created By','max_length[11]');			
		$this->form_validation->set_rules('created_on','Created On','max_length[11]');

		if ($this->form_validation->run() === FALSE)
		{
			return FALSE;
		}
		
		// make sure we only pass in the fields we want
		date_default_timezone_set('Asia/Karachi');
		$data = array();
		$data['title']        = $this->input->post('title');
		$data['body']        = $this->input->post('body');
		$data['institute_id']        = $this->auth->institute_id();
		if($this->auth->has_permission('Campuses.Content.View'))
		{
			$data['campus_id']=$this->input->post('campus_id');
		}
		else
		{
			$data['campus_id']        = $this->auth->campus_id();
		}		
		$data['remarks']        = $this->input->post('remarks');
		$data['status']        = $this->input->post('status');
		$data['created_by']        = $this->auth->user_id();
		$data['created_on']        =date("Y-m-d H:i:s");
	
		if ($type == 'insert')
		{
			$id = $this->messages_model->insert($data);
			
			if (is_numeric($id))
			{
				$return = $id;
			} else
			{
				$return = FALSE;
			}
		}
		else if ($type == 'update')
		{
		unset($data['created_on'],$data['created_by']);

			$return = $this->messages_model->update($id, $data);
		}
		
		return $return;
	}

	//--------------------------------------------------------------------
    function send_a_message()
	{
	   Template::set('toolbar_title', lang('send_a_message'));
       if($this->input->post('submit_button'))
       {
            $message_type='text';
            $recipient_type='students';
            $status='pending';                                            
            $message_id=$this->input->post('message');
            $student_status='active';
            if($message_id!=-1)
            {
                $body=$this->messages_model->get_body($message_id);
                $outbox_data=array( 
                                    'institute_id'      =>  $this->auth->institute_id(),
                                    'message_id'        =>  $message_id,
                                    'body'              =>  $body,
                                    'message_type'      =>  $message_type,
                                    'status'            =>  $status,
                                    'recipient_type'    =>  $recipient_type,
                                    'created_by'        =>  $this->auth->user_id(),
                                    'created_on'        =>  date("Y-m-d H:i:s"),                                                                                                            
                                    );
    		if(count($this->auth->show_campuses()>1))
           {
        		$campuses=$this->auth->show_campuses();        
           }
           else
           {
                $campus_id=$this->auth->campus_id();
                $campuses[$campus_id]=$campus_name;
           }
           $campus_ids=array_keys($campuses);
           foreach($campus_ids as $campus_id)
           {
                if($campuses[$campus_id]!='Select Campus')
                {
                    unset($check_box_name);
                    $check_box_name='campus_id_'.$campus_id;
                    if($this->input->post($check_box_name) and false)
                    {
                        $criteria=array('campus_id'=>$campus_id,'status'=>  $student_status,);
                        $students=$this->students_model->get_students_in_($criteria);
                        foreach($students->result() as $student)
                        {
                            
                            $student_id=$student->id;
                            $is_enrolled=$this->students_model->is_enrolled($student_id);
                            if($is_enrolled==FALSE)
                            {
                                continue;
                            }
                            $contact=$this->students_model->get_contact($student_id);
                            $outbox_data['contact']=$contact;
                            $outbox_data['recipient_id']=$student_id;
                            $outbox_data['campus_id']=$campus_id;
                            $this->messages_model->log_message($outbox_data);                                                                                    
                                                                                                                                                                                                                                                                                                                                                                           
                                                                                                                
                        }
                    }
                    else
                    {
       					$departments=$this->departments_model->get_all_departments($campus_id);
        				$department_ids=array_keys($departments);
        				foreach($department_ids as $department_id)
        				{
        				    
        					if($departments[$department_id]!='Select Department')
        					{
      					         unset($check_box_name);
                                 $check_box_name='campus_id_'.$campus_id.'_department_id_'.$department_id;
        						  if($this->input->post($check_box_name) and false)
                                  {
                                    $criteria=array(
                                                        'campus_id'     =>  $campus_id,
                                                        'department_id' =>  $department_id,
                                                        'status'=>  $student_status,
                                                    
                                                    );
                                    $students=$this->students_model->get_students_in_($criteria);
                                    foreach($students->result() as $student)
                                    {
                                        $student_id=$student->id;
                                        $is_enrolled=$this->students_model->is_enrolled($student_id);
                                        if($is_enrolled==FALSE)
                                        {
                                            continue;
                                        }
                                        $contact=$this->students_model->get_contact($student_id);
                                        $outbox_data['contact']=$contact;
                                        $outbox_data['recipient_id']=$student_id;
                                        $outbox_data['campus_id']=$campus_id;
                                        $this->messages_model->log_message($outbox_data);                                                                                    
                                                                                                                                                                                                                                                                                                                                                                                       
                                                                                                                            
                                    }
                                  }
                                  else
                                  {	
        						  	   $programs=$this->programs_model->get_all_programs_in_department($department_id);
        							    $program_ids=array_keys($programs);
            							foreach($program_ids as $program_id)
            							{
            								if($programs[$program_id]!='Select Program')
            								{
            										unset($check_box_name);
            										$check_box_name='campus_id_'.$campus_id.'_department_id_'.$department_id.'_program_id_'.$program_id;
                                                    if($this->input->post($check_box_name) and false)
                                                    {
                                                        $criteria=array(
                                                                    'campus_id'     =>  $campus_id,
                                                                    'department_id' =>  $department_id,
                                                                    'program_id'    =>  $program_id,
                                                                    'status'=>  $student_status,
                                                    
                                                                    );
                                                        $students=$this->students_model->get_students_in_($criteria);
                                                        foreach($students->result() as $student)
                                                        {
                                                            $student_id=$student->id;
                                                            $is_enrolled=$this->students_model->is_enrolled($student_id);
                                                            if($is_enrolled==FALSE)
                                                            {
                                                                continue;
                                                            }
                                                            $contact=$this->students_model->get_contact($student_id);
                                                            $outbox_data['contact']=$contact;
                                                            $outbox_data['recipient_id']=$student_id;
                                                            $outbox_data['campus_id']=$campus_id;
                                                            $this->messages_model->log_message($outbox_data,true); 
                                                            $this->notifications_model->log_notification($outbox_data,true);                                                                                   
                                                                                                                                                                                                                                                                                                                                                                                                           
                                                                                                                                                
                                                        }
                                                    }
                                                    else
                                                    {
                                                         if(!($this->auth->institute_type()=='school' or $this->auth->institute_type()=='academy'))
                                                        {
                                                           $semesters=$this->programs_model->get_all_semesters_in_program($program_id);   
                                                        } 
                                                        else
                                                        {
                                                            $semesters=array('1'=>1);
                                                        }
                										
                										$semester_nos=array_keys($semesters);
                										foreach($semester_nos as $semester_no)
                										{           
                											unset($check_box_name);
                											$check_box_name='campus_id_'.$campus_id.'_department_id_'.$department_id.'_program_id_'.$program_id.'_semester_no_'.$semester_no;
                                                            if($this->input->post($check_box_name) and false)
                                                            {
                                                                $criteria=array(
                                                                    'campus_id'     =>  $campus_id,
                                                                    'department_id' =>  $department_id,
                                                                    'program_id'    =>  $program_id,
                                                                    'semester_no'   =>  $semester_no,
                                                                    'status'=>  $student_status,
                                                    
                                                                    );
                                                                    $students=$this->students_model->get_students_in_($criteria);
                                                                    foreach($students->result() as $student)
                                                                    {
                                                                        $student_id=$student->id;
                                                                        $is_enrolled=$this->students_model->is_enrolled($student_id);
                                                                        if($is_enrolled==FALSE)
                                                                        {
                                                                            continue;
                                                                        }
                                                                        $contact=$this->students_model->get_contact($student_id);
                                                                        $outbox_data['contact']=$contact;
                                                                        $outbox_data['recipient_id']=$student_id;
                                                                        $outbox_data['campus_id']=$campus_id;
                                                                        $this->messages_model->log_message($outbox_data);                                                                                    
                                                                                                                                                                                                                                                                                                                                                                                                                       
                                                                                                                                                            
                                                                    }
                                                            }
                                                            else
                                                            {
                                                                
                                                            
                    											$batches=$this->batches_model->get_all_batches($department_id,$program_id,$semester_no);
                    											$batch_ids=array_keys($batches);
                    											foreach($batch_ids as $batch_id)
                   												 {
                    													if($batches[$batch_id]!='Select Batch')
                    													{
                    													   $check_box_name='campus_id_'.$campus_id.'_department_id_'.$department_id.'_program_id_'.$program_id.'_semester_no_'.$semester_no.'_batch_id_'.$batch_id;
                                                                           
                    													   if($this->input->post($check_box_name))
                                                                           {
                                                                            
                                                                                $criteria=array(
                                                                                    'campus_id'     =>  $campus_id,
                                                                                    'department_id' =>  $department_id,
                                                                                    'program_id'    =>  $program_id,
                                                                                    'semester_no'   =>  $semester_no,
                                                                                    'batch_id'      =>  $batch_id,
                                                                                    'status'=>  $student_status,
                                                                                    );
                                                                                $students=$this->students_model->get_students_in_($criteria);
                                                                                
                                                                                foreach($students->result() as $student)
                                                                                {
                                                                                    
                                                                                    $student_id=$student->id;
                                                                                    $is_enrolled=$this->students_model->is_enrolled($student_id);
                                                                                    if($is_enrolled==FALSE)
                                                                                    {
                                                                                        continue;
                                                                                    }
                                                                                    
                                                                                    if(in_array('students',array_keys($_POST)))
                                                                                    {
                                                                                        $contact=$this->students_model->get_contact($student_id);
                                                                                        $outbox_data['contact']=$contact;
                                                                                        $outbox_data['recipient_id']=$student_id;
                                                                                        $outbox_data['campus_id']=$campus_id;
                                                                                        
                                                                                        $this->messages_model->log_message($outbox_data,true);
                                                                                    }
                                                                                    if(in_array('parents',array_keys($_POST)))
                                                                                    {
                                                                                                                                                                        
                                                                                        $parent_id=$this->students_model->get_father_id($student_id);
                                                                                        if($parent_id!='')
                                                                                        {
                                                                                            $contact=$this->parents_model->get_cell_number($parent_id);
                                                                                            $outbox_data['recipient_type']='parents';
                                                                                            $outbox_data['contact']=$contact;
                                                                                            $outbox_data['recipient_id']=$student_id;
                                                                                            $outbox_data['campus_id']=$campus_id;
                                                                                            $this->messages_model->log_message($outbox_data,true);    
                                                                                        }
                                                                                        
                                                                                        
                                                                                            
                                                                                        
                                                                                        
                                                                                        
                                                                                      }
                                                                                    $outbox_data['recipient_type']='students';                                                                                                                                                                                                                                                                                                                                        
                                                                                                                                                                        
                                                                                }
                                                                          }
                                                                          
                														}
                  												  }
                                                            }   
               											}
                                                     }		
            									}
    									   }
                                        }//end Semesters loop
    								}
     							}//end Programs loop
                            }
			    	    }
    			     }
                }
                else
                {
                   $errors[0]='no_message_selected';
                }
          if($criteria=='')
          {
            $errors[1]='no_recipient_selected';
          }
	      if($errors=='')
          {
            Template::set_message('Messages Sent','success');
          }
          else
          {
            Template::set('errors',$errors);  
          }
          
       }
       
       Template::render();
    }
    function outbox()
	{
       
        Template::set('toolbar_title', lang('outbox'));
        if($this->input->post('send'))
        {
        	$checked = $this->input->post('checked');
        	if (is_array($checked) && count($checked))
        	{
        		$result = FALSE;
        		foreach ($checked as $pid)
        		{
                    $query="
                                UPDATE  outbox
                                SET     status='pending'
                                WHERE   id='".$pid."'
                            ";
                    $this->db->query($query);
                    
        		}
                
                
        		if ($result)
        		{
        			Template::set_message(count($checked) .' '. lang('messages_sent_success'), 'success');
        		}
        		else
        		{
        			//Template::set_message(lang('messages_sent_failure') . $this->credits_model->error, 'error');
        		}
        	}
        	else
        	{
        		//Template::set_message(lang('messages_sent_error') . $this->credits_model->error, 'error');
        	}
        
        }
        $type=$this->input->get('type');
        $filter=$this->input->get('filter');
        $status=$this->input->get('status');
        if(count($this->auth->show_campuses()>1))
        {
            $campus_id=$this->input->get('campus_id');
        }
        else
        {
            $campus_id=$this->auth->campus_id();
        }
        if($campus_id=='')
        {
            $campus_id=$this->auth->campus_id();
        }
        $criteria=array('institute_id'=>$this->auth->institute_id());
        if($type!='')
        {
            Template::set('type',$type);
            $criteria['message_type']=$type;
        }
        if(isset($campus_id))
        {
            Template::set('campus_id',$campus_id);
            $criteria['campus_id']=$campus_id;
        }
        if($status!='')
        {
            Template::set('status',$status);
            $criteria['status']=$status;
        }
        elseif($filter=='')
        {
            Template::set('status','pending');
            $criteria['status']='pending';
        }
        if($filter!='')
        {
            Template::set('filter',$filter);
            
        }
        $per_page=1000;
        $this->db->order_by('created_on','desc');
        $offset=$this->uri->segment(5);
        $result=$this->db->get_where('outbox',$criteria);
        $this->load->helper('ui/ui');
        $this->load->library('pagination');
		$this->pager['base_url'] = site_url(SITE_AREA .'/content/messages/outbox');
		$this->pager['total_rows'] = $result->num_rows();
		$this->pager['per_page'] = $per_page;
		$this->pager['uri_segment']	= 5;

		$this->pagination->initialize($this->pager);
        $this->db->limit($per_page,$offset);
        $this->db->order_by('created_on','desc');
        $result=$this->db->get_where('outbox',$criteria);
        Template::set('result',$result);
       	
        
        Template::render();
 	}
	function real_outbox()
	{
 	   Template::set('toolbar_title', lang('outbox'));
	   if($this->input->post('send'))
       {
   				$checked = $this->input->post('checked');
	       		if (is_array($checked) && count($checked))
    			{
					$result = FALSE;
					foreach ($checked as $pid)
					{
                        
                        $result=$this->send($pid);
                        
					}
                    
                    
					if ($result)
					{
						Template::set_message(count($checked) .' '. lang('messages_sent_success'), 'success');
					}
					else
					{
						Template::set_message(lang('messages_sent_failure') . $this->credits_model->error, 'error');
					}
				}
				else
				{
					Template::set_message(lang('messages_sent_error') . $this->credits_model->error, 'error');
				}

       }
		if($this->input->post('submit_button'))
        {
            if($this->auth->has_permission('Campuses.Content.EditOthers') and $this->input->post('campus_id')==-1)
            {
                $errors[1]='campus_not_selected';
            }
            $date_from=$this->input->post('date_from');
            $year=substr($date_from,0,4);
            $month=substr($date_from,5,2);
            $day=substr($date_from,8,2);
            if(!checkdate($month,$day,$year))
            {
                $errors[2]='start_date_not_selected';
            }
            $date_to=$this->input->post('date_to');
            $year=substr($date_to,0,4);
            $month=substr($date_to,5,2);
            $day=substr($date_to,8,2);
            if(!checkdate($month,$day,$year))
            {
                $errors[3]='end_date_not_selected';
            }
            if(!is_null($errors))
            {
                Template::set('errors',$errors);
            }
        }
      
		Template::render();	
	}
	function update_status()
	{
		$status=$this->uri->segment(5);	
		$item_id=$this->uri->segment(6);	
		$this->messages_model->update_status($status,$item_id);
		Template::redirect(SITE_AREA .'/content/messages/outbox');

	}
	function send($item_id)
	{
	   if($item_id=='')
       {
            $item_id=$this->uri->segment(5);
       }
        $contact=$this->messages_model->get_contact($item_id);
		$message=$this->messages_model->get_message($item_id);
		$status=$this->messages_model->send_message($contact,$message,$item_id);
		if($status)
		{
			$this->messages_model-> update_status('sent',$item_id);	
			
		}
		else
		{
			$this->messages_model-> update_status('failed',$item_id);	
		}
		if($this->uri->segment(5)!='')
        {
	       Template::redirect(SITE_AREA .'/content/messages/outbox');
        }
        else
        {
            return $status;
        }	
	}
	function send_all()
	{
		$campus_id=$this->input->post('campus_id');
		$message_type=$this->uri->segment(5);
		$recipient_type=$this->uri->segment(6);	
		$status=$this->uri->segment(7);	
		
		if($campus_id!=-1)
		{
			$criteria['campus_id']=$campus_id;
			
		}
		if($message_type!=-1)
		{
			$criteria['message_type']=$message_type;
			
		}
		
		
		if($recipient_type!=-1)
		{
			$criteria['recipient_type']=$recipient_type;
			
		}
		if($status!=-1)
		{
			$criteria['status']=$status;
			
		}
		$this->db->where('status !=','sent');

		$result=$this->db->get_where('outbox',$criteria);
		//echo $this->db->last_query();
		foreach($result->result() as $item)
		{
			
			$item_id=$item->id;
			$contact=$this->messages_model->get_contact($item_id);
		
			$message=$this->messages_model->get_message($item_id);
			
			$message_status=$this->messages_model->send_message($contact,$message);
			if($message_status)
			{
				$this->messages_model-> update_status('sent',$item_id);	
				
			}
			else
			{
				$this->messages_model-> update_status('failed',$item_id);	
			}
		//	die();
		}
		Template::set_view('content/outbox');
		Template::render();
		
	}
    function sms_anonymus()
    {
      Template::set('toolbar_title', lang('sms_anonymus'));
        $tos=$this->input->post('to');
        $value=$this->input->post('message');
        if($this->input->post('submit_button'))
        {
            if($value=='')
            {
                $errors[1]='message_body_not_set';
            }
            if($tos=='')
            {
                $errors[2]='message_recipient_not_set';
            }
            if($errors!='')
            {
                Template::set('errors',$errors);
            }
            else
            {
                $to = strtok($tos, ",");
                while ($to !== false) 
                {   
                    $count++;
                    $outbox_data=array(
                                        'institute_id'  =>  $this->auth->institute_id(),
                                        'campus_id'     =>  $this->auth->campus_id(),
                                        'recipient_type'=>  'anonymus',
                                        'contact'       =>  $to,
                                        'body'          =>  $value,
                                        'message_type'  =>  'text',
                                        'status'        =>  'pending',
                                        'created_by'    =>  $this->auth->user_id(),
                                        'created_on'    =>  date("Y-m-d H:i:s"),
                                    );
                  //  print_r($outbox_data);die();
                    $this->messages_model->log_message($outbox_data); 
                    $to = strtok(",");          
                }
                //die();
                Template::set_message($count.' message(s) scheduled','success');
            }
        }
         
        Template::render();
    }
    function sms_to_employees()
    {
        Template::set('toolbar_title', lang('sms_to_employees'));
      
        if($this->input->post('submit_button'))
        {
           $message=$this->input->post('message');
           $errors=array();
           if($message=='')
           {
                $errors[1]='message_body_not_set';
                Template::set('errors',$errors);              
           }
           else
           {
                $employees=$this->input->post('employees');
                $cmapuses=$this->input->post('campus');
                $employees=array_keys($employees);
                foreach($employees as $employee_id)
                {
                    $employee=$this->employees_model->get_employee($employee_id);
                    $campus_id=$employee->campus_id;
                    $contacts_criteria=array(
                                                    'institute_id'  =>  $this->auth->institute_id(),
                                                    'campus_id'     =>  $campus_id,
                                                    'employee_id'   =>  $employee_id,
                                                    'type'          =>  'cell',
                                                    'status'        =>  'active'
                                                );
                        //print_r($contacts_criteria);    
                        unset($contacts,$contact);                    
                        $contacts=$this->employees_model->get_contacts($contacts_criteria);
                        if($contacts!='')
                        {
                            
                           
                           $contact=$contacts->row();
                            {
                                $value=$contact->value;
                                $outbox=array
                                            (
                                                'institute_id'      =>  $this->auth->institute_id(),
                                                'campus_id'         =>  $campus_id,
                                                'recipient_id'      =>  $employee_id,
                                                'recipient_type'    =>  'employees',
                                                'contact'           =>  $value,
                                                'body'              =>  $message,
                                                'message_type'      =>  'text',
                                                'status'            =>  'pending',
                                                'created_by'        =>  $this->auth->user_id(),
                                               	'created_on'        =>  date("Y-m-d H:i:s")
                                            );
                            
                                //var_dump($outbox);
                                $this->messages_model->log_message($outbox);
                               
                            }
                            
                        }
                        else
                        {
                            $invalid_contacts++;
                        }
                }
                if($invalid_contacts>0)
                {
                    $user_message=$invalid_coantacts.' message[s] could not be sent.'.(count($employees)-$invalid_coantacts).'sent.';
                    $user_message_type='error';
                }
                else
                {
                    $user_message=count($employees).' message[s] queued.';
                    $user_message_type='success';
                }
                $user_message='Out of '.count($employees).' messages[s], '.$user_message.' ';
                Template::set_message($user_message, $user_message_type);
               
           }
        }
        Template::render();
      }
    
    function _sms_to_employees()
    {
        Template::set('toolbar_title', lang('sms_to_employees'));
      
        if($this->input->post('submit_button'))
        {
           $message=$this->input->post('message');
           $errors=array();
           if($message=='')
           {
                $errors[1]='message_body_not_set';
                Template::set('errors',$errors);              
           }
           else
           {
                if(count($this->auth->show_campuses()>1))
                {
                	$campuses=$this->auth->show_campuses();        
                }
                else
                {
                    $campus_id=$this->auth->campus_id();
                    $campus_name=$this->campuses_model->get_name($campus_id);
                    $campuses[$campus_id]=$campus_name;
                }
                $keys=array_keys($campuses);
               
                foreach($keys as $campus_id)
                {
                   
                    if($campuses[$campus_id]!='Select Campus')
                    {
                        $check_box_name='campus_id_'.$campus_id;
                        if($this->input->post($check_box_name))
                        {
                          $criteria=array(
                                                'institute_id'  =>  $this->auth->institute_id(),
                                                'campus_id'     =>  $campus_id,
                                            );
                          $employees=$this->employees_model->get_employees_in_($criteria);
                          foreach($employees->result() as $employee)
                          {
                               $employee_id=$employee->id;
                               $contacts_criteria=array(
                                                            'institute_id'  =>  $this->auth->institute_id(),
                                                            'campus_id'     =>  $campus_id,
                                                            'employee_id'   =>  $employee_id,
                                                            'type'          =>  'cell',
                                                            'status'        =>  'active'
                                                        );
                                //print_r($contacts_criteria);                        
                                $contacts=$this->employees_model->get_contacts($contacts_criteria);
                                if($contacts!='')
                                {
                                    
                                   // foreach($contacts->result() as $contact)
                                   $contact=$contacts->row();
                                    {
                                        $value=$contact->value;
                                        $outbox=array
                                                    (
                                                        'institute_id'      =>  $this->auth->institute_id(),
                                                        'campus_id'         =>  $campus_id,
                                                        'recipient_id'      =>  $employee_id,
                                                        'recipient_type'    =>  'employees',
                                                        'contact'           =>  $value,
                                                        'body'              =>  $message,
                                                        'message_type'      =>  'text',
                                                        'status'            =>  'pending',
                                                        'created_by'        =>  $this->auth->user_id(),
                                                       	'created_on'        =>  date("Y-m-d H:i:s")
                                                    );
                                    
                                             
                                        $this->messages_model->log_message($outbox);
                                       
                                    }
                                    
                                }    
                          }
                          
                        }
                						            
                    }
                } 
           }
          
               
        }
  
        Template::render();
    }
    function all_messages()
    {
        		
  
		if ($action = $this->input->post('delete'))
		{
		 
			if ($action == lang('messages_delete'))
			{
				$checked = $this->input->post('checked');

				if (is_array($checked) && count($checked))
				{
					$result = FALSE;
					foreach ($checked as $pid)
					{
						$result = $this->change_status($pid,'deleted');
                        
					}

					if ($result)
					{
						Template::set_message(count($checked) .' '. lang('messages_delete_success'), 'success');
					}
					else
					{
						Template::set_message(lang('messages_delete_failure') . $this->messages_model->error, 'error');
					}
				}
				else
				{
					Template::set_message(lang('messages_delete_error') . $this->messages_model->error, 'error');
				}
			}
            
		}
        
        //Template::set_view('content/outbox');
        $this->outbox();
        Assets::add_js($this->load->view('content/js', null, true), 'inline');
		
        $criteria['institute_id']=$this->auth->institute_id();
        $criteria['campus_id']=$this->auth->campus_id();
        $criteria['status']='active';
        $records=$this->db->get_where('messages',$criteria);
        
		Template::set('records', $records);
		Template::set('toolbar_title', "Manage Messages");
        Template::render();
    }
    function display_contacts()
    {
        $this->auth->restrict('Students.Content.View');
        $query='select  distinct(value ) as value
            from students, students_contacts
            where students.institute_id=41
            AND 	type="cell"
            AND students.id=students_contacts.student_id
            AND     students.status="active"
            ';
        $result=$this->db->query($query);
        $output='';
        foreach($result->result() as $row)
        {
            $output=$output.','.$row->value;
        }
        echo $output;
    }
    function send_message_to_selected_students()
    {
        Template::set('toolbar_title',humanize('send_message_to_selected_students'));
        if($this->input->post('submit_button'))
        {
            $tos=$this->input->post('tos');
            $message_type='text';
            $recipient_type='students';
            $status='pending';                                            
            $message_id=$this->input->post('message');
            $student_status='active';
            if($message_id!=-1 and $tos!='')
            {
                
                $body=$this->messages_model->get_body($message_id);
                $tos=explode(',',$tos);
                $outbox_data=array( 
                                    'institute_id'      =>  $this->auth->institute_id(),
                                    'message_id'        =>  $message_id,
                                    'body'              =>  $body,
                                    'message_type'      =>  $message_type,
                                    'status'            =>  $status,
                                    'recipient_type'    =>  $recipient_type,
                                    'created_by'        =>  $this->auth->user_id(),
                                    'created_on'        =>  date("Y-m-d H:i:s"),                                                                                                            
                                    );
                
                foreach($tos as $student_id)
                {
                    $student=$this->students_model->get_student($student_id);
                    $campus_id=$student->campus_id;
                    $contact=$this->students_model->get_contact($student_id);
                    $outbox_data['contact']=$contact;
                    $outbox_data['recipient_id']=$student_id;
                    $outbox_data['campus_id']=$campus_id;
                
                    $this->messages_model->log_message($outbox_data,true);
                }
            }
            
           
            if($message_id==-1)
            {
                $errors[0]='no_message_selected';
            }
            if($tos=='')
            {
                $errors[1]='no_recipient_selected';
            }
	        if($errors=='')
            {
                Template::set_message('Messages Sent','success');
            }
            else
            {
                Template::set('errors',$errors);  
            }
        }
        //die();
        Template::render();
    }
    function zong()
    {
        $this->messages_model->zong();
    }  
} 