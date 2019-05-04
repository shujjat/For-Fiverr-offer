<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class content extends Admin_Controller {

	//--------------------------------------------------------------------

	public function __construct() 
	{
		parent::__construct();

		$this->auth->restrict('Employees.Content.View');
		$this->load->model('employees_model', null, true);
		$this->load->model('institutes/institutes_model', null, true);
		$this->load->model('campuses/campuses_model', null, true);
		$this->load->model('departments/departments_model', null, true);
		$this->load->model('programs/programs_model', null, true);
		$this->load->model('batches/batches_model', null, true);
		$this->load->model('students/students_model', null, true);
		$this->load->model('courses/courses_model', null, true);
        $this->load->model('combinations/combinations_model', null, true);
  		$this->load->model('ims_users/ims_users_model', null, true);
        $this->load->model('ims_sessions/ims_sessions_model', null, true);
		
        $this->lang->load('employees/school','english');
        //$this->lang->load('employees/school','pure_urdu');
	   	Assets::add_js('ajax.js');	
		Assets::add_css('flick/jquery-ui-1.8.13.custom.css');
        Template::set_block('sub_nav', 'content/_sub_nav');
        
        date_default_timezone_set('asia/karachi');
        Assets::add_js('ajax.js');
	}
	
	//--------------------------------------------------------------------

	/*
		Method: index()
		
		Displays a list of form data.
	*/
    public function index()
	{
	   

		// Deleting anything?
		if ($action = $this->input->post('delete'))
		{
			if ($action == 'Delete')
			{
				$checked = $this->input->post('checked'); 

				if (is_array($checked) && count($checked))
				{
					$result = FALSE;
					foreach ($checked as $pid)
					{
						$result = $this->delete($pid);
					}

					if ($result)
					{
						Template::set_message(count($checked) .' '. lang('employees_delete_success'), 'success');
					}
					
				}
				else
				{
					Template::set_message(lang('employees_delete_error') . $this->employees_model->error, 'error');
				}
			}
		}
        
        
        $criteria['institute_id']   =$this->auth->institute_id();
        $criteria['status']         ='active';
        $records=$this->db->get_where('employees',$criteria);

		Template::set('records', $records);
		Template::set('toolbar_title', lang('employees_manage'));
		Template::render();
	}
	
	
	//--------------------------------------------------------------------

	/*
		Method: create()
		
		Creates a Employees object.
	*/
	public function create() 
	{
		$this->auth->restrict('Employees.Content.Create');

		if ($this->input->post('submit_button'))
		{
			if ($insert_id = $this->save_employees())
			{
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('employees_act_create_record').': ' . $insert_id . ' : ' . $this->input->ip_address(), 'employees');
					
				Template::set_message(lang("employees_create_success"), 'success');
				Template::redirect(SITE_AREA .'/content/employees');
			}
			else 
			{
				Template::set_message(lang('employees_create_failure') . $this->employees_model->error, 'error');
			}
		}
	
		Template::set('toolbar_title', lang('employees_create_new_button'));
		Template::set('toolbar_title', lang('employees_create') );
		Template::render();
	}
	
	//--------------------------------------------------------------------

	/*
		Method: edit()
		
		Allows editing of Employees data.
	*/
	public function edit() 
	{
		$this->auth->restrict('Employees.Content.Edit');

		$id = (int)$this->uri->segment(5);
		
		if (empty($id))
		{
			Template::set_message(lang('employees_invalid_id'), 'error');
			redirect(SITE_AREA .'/content/employees');
		}
	
		if ($this->input->post('submit_button'))
		{
			if ($this->save_employees('update', $id))
			{
				// Log the activity
				$this->activity_model->log_activity($this->auth->user_id(), lang('employees_act_edit_record').': ' . $id . ' : ' . $this->input->ip_address(), 'employees');
					
				Template::set_message(lang('employees_edit_success'), 'success');
			}
			else 
			{
				Template::set_message(lang('employees_edit_failure') . $this->employees_model->error, 'error');
			}
		}
		
		Template::set('employees', $this->employees_model->find($id));
	
		Template::set('toolbar_title', lang('employees_edit_heading'));
		Template::set('toolbar_title', lang('employees_edit') . ' Employees');
		Template::set_view('content/create');
		Template::render();		
	}
	
	//--------------------------------------------------------------------

	/*
		Method: delete()
		
		Allows deleting of Employees data.
	*/
	public function delete($id)
	{
		$this->auth->restrict('Employees.Content.Delete');
       	$this->load->model('statusmanager/status_model');
        if (!empty($id))
		{	
		   if ($this->status_model->change_status($id,'deleted',' employees'))
            {
			
				// Log the activity
					$this->activity_model->log_activity($this->auth->user_id(), lang('employees_act_delete_record').': ' . $id . ' : ' . $this->input->ip_address(), 'employees');
					
				Template::set_message(lang('employees_delete_success'), 'success');
			} 
            else
			{
				Template::set_message(lang('employees_delete_failure') . $this->employees_model->error, 'error');
			}
		}
		

		
	}
   
   
   public function restore($id)
	{
		$this->auth->restrict('Employees.Content.Restore');
       	$this->load->model('statusmanager/status_model');
        if (!empty($id))
		{	
		   if ($this->status_model->change_status($id,'restore',' employees'))
            {
			
				// Log the activity
					$this->activity_model->log_activity($this->auth->user_id(), lang('employees_act_delete_record').': ' . $id . ' : ' . $this->input->ip_address(), 'employees');
					
				Template::set_message(lang('employees_delete_success'), 'success');
			} 
            else
			{
				Template::set_message(lang('employees_delete_failure') . $this->employees_model->error, 'error');
			}
		}
		

		
	}
	
	//--------------------------------------------------------------------
   

	//--------------------------------------------------------------------
	// !PRIVATE METHODS
	//--------------------------------------------------------------------
	
	/*
		Method: save_employees()
		
		Does the actual validation and saving of form data.
		
		Parameters:
			$type	- Either "insert" or "update"
			$id		- The ID of the record to update. Not needed for inserts.
		
		Returns:
			An INT id for successful inserts. If updating, returns TRUE on success.
			Otherwise, returns FALSE.
	*/
	private function save_employees($type='insert', $id=0) 
	{	
		//Set Personal Information Rules ///
		if($this->input->post('campus_id')=='' or  $this->input->post('campus_id')=='-1')
        {
            unset($_POST['campus_id']);
        }
        if($this->input->post('status')=='' or  $this->input->post('status')=='-1')
        {
            unset($_POST['status']);
        }
        
        if($this->input->post('joining_date')=='')
        {
            unset($_POST['joining_date']);
        }
        //die();
		$this->form_validation->set_rules('first_name','First Name','required');			
		$this->form_validation->set_rules('last_name','Last Name','');			
		$this->form_validation->set_rules('nationality','Nationality','');			
		$this->form_validation->set_rules('dob','DOB','max_length[11]|required');			
		$this->form_validation->set_rules('height','Height','max_length[11]');			
		$this->form_validation->set_rules('blood_group','Blood Group','max_length[10]|');			
				
		$this->form_validation->set_rules('cnic','CNIC','max_length[25]|is_numeric');	
				
		//Set family Information Rules ///	
		
		$this->form_validation->set_rules('spouse_name','Spouse Name','');			
		$this->form_validation->set_rules('spouse_cnic','Spouse Cnic','max_length[25]');			
		$this->form_validation->set_rules('father_name','Father Name','');			
		$this->form_validation->set_rules('father_cnic','Father Cnic','max_length[25]');			
		$this->form_validation->set_rules('marital_status','Marital Status','max_length[10]');			
		$this->form_validation->set_rules('gender','Gender','max_length[10]');			
		
		//Set family Information Rules ///	
		$this->form_validation->set_rules('permanent_address','Permanent Address','');			
		$this->form_validation->set_rules('temporary_address','Temporary Address','');			
		$this->form_validation->set_rules('zip_code','Zip Code','max_length[50]|is_number');			
		//$this->form_validation->set_rules('email','Email','max_length[250]|valid_email');
			
		//Set job description Rules ///	
		$this->form_validation->set_rules('experience','Experience','');			
		$this->form_validation->set_rules('joining_date','Joining Date','');			
		$this->form_validation->set_rules('basic_pay','Basic Pay','max_length[11]');			
		$this->form_validation->set_rules('shift','Shift','');			
		//$this->form_validation->set_rules('work_hours','Work Hours','max_length[10]|required');
		//$this->form_validation->set_rules('institute_id','Institute','max_length[11]');			
		$this->form_validation->set_rules('campus_id','Campus','required');			
		$this->form_validation->set_rules('remarks','Remarks','');			
		$this->form_validation->set_rules('status','Status','required');			
	//	$this->form_validation->set_rules('created_by','Created By','max_length[11]');			
	//	$this->form_validation->set_rules('created_on','Created On','max_length[11]');			

		if ($this->form_validation->run() === FALSE)
		{
			return FALSE;
		}
		
		// make sure we only pass in the fields we want
		date_default_timezone_set('asia/karachi');

		$data = array();
		$data['institute_id']        = $this->auth->institute_id();
		
		if(count($this->auth->show_campuses()>1))
		{
			$data['campus_id']			=$this->input->post('campus_id');
		}
		else
		{
			$data['campus_id']        	= $this->auth->campus_id();
		}
		
		
		$data['remarks']        		= $this->input->post('remarks');
		$data['status']        			= $this->input->post('status');
		$data['created_by']         	= $this->auth->user_id();
		$data['created_on']        		= date("Y-m-d H:i:s");
		$data['first_name']         	= $this->input->post('first_name');
		$data['last_name']        		= $this->input->post('last_name');
		$data['nationality']        	= $this->input->post('nationality');
		$data['dob']        			= $this->input->post('dob');
		$data['height']        			= $this->input->post('height');
		$data['blood_group']        	= $this->input->post('blood_group');
		$data['bank_account_no']    	= $this->input->post('bank_account_no');
		$data['cnic']        			= $this->input->post('cnic');
		$data['experience']        	 	= $this->input->post('experience');
		$data['spouse_name']       	 	= $this->input->post('spouse_name');
		$data['spouse_cnic']        	= $this->input->post('spouse_cnic');
		$data['father_name']        	= $this->input->post('father_name');
		$data['father_cnic']        	= $this->input->post('father_cnic');
		$data['marital_status']     	= $this->input->post('marital_status');
		$data['gender']        			= $this->input->post('gender');
		$data['permanent_address']      = $this->input->post('permanent_address');
		$data['temporary_address']      = $this->input->post('residential_address');
		$data['zip_code']        		= $this->input->post('zip_code');
		//$data['email']        			= $this->input->post('email');
		$data['joining_date']        	= $this->input->post('joining_date');
		$data['basic_pay']        		= $this->input->post('basic_pay');
		$data['shift']        			= $this->input->post('shift');
        $data['employment_type']        			= $this->input->post('employment_type');
		$data['work_hours']        		= $this->input->post('work_hours');
        $data['arrival_time']        		= $this->input->post('arrival_time');
        
        if ($data['arrival_time']=='')
        {
            
            $data['arrival_time']=$this->employees_model->get_setting('employees',$this->auth->campus_id(),'arrival_time');
        }
        
		if($data['height']=='')
        {
            unset($data['height']);
        }
        if($data['bank_account_no']=='')
        {
            unset($data['bank_account_no']);
        }
		if ($type == 'insert')
		{
			 $employee_id = $this->employees_model->insert($data);
			
		    $total_contacts=$this->input->post('count');
			$count=1;
			while($count<=$total_contacts)  /////employee contact array
			{
			
			 	$key='employee_contact_'.$count.'_type';
				$contact_type=$this->input->post($key);
				$key='employee_contact_'.$count.'_value';
				$value=$this->input->post($key);
				
				$this->employees_model->save_contact($data['campus_id'],$employee_id,$contact_type,$value,$default);
			 	$count=$count+1;
				
			}
            $role_id=$this->ims_users_model->get_id_by_name('Employees');
            if($role_id=='')
            {
                $role_id=$this->ims_users_model->get_id_by_name('employees');
            }
            $this->ims_users_model->make_user($this->auth->institute_id(),$data['campus_id'],$employee_id,'employees',$role_id);
			if (is_numeric($employee_id))
			{
				$this->upload_image($employee_id);
				$return = $employee_id;
                
                return $return;
			} 
			else
			{
				$return = FALSE;
			}
            
		}
		else if ($type == 'update')
		{
		//	unset($data['created_on'],$data['created_by']);
			$employee_id=$this->uri->segment(5);
			$this->employees_model->update($employee_id, $data);
     		$employee_total_contacts=$this->input->post('count');
			$employee_contacts=$this->employees_model->get_employee_all_contacts($id); 
			//$employee_total_contacts=$employee_contacts->num_rows();
			$count=1;
            if($employee_contacts!='')
            {
    			foreach($employee_contacts->result() as $employee_contact)
    			{
    				$contact_id=$employee_contact->id;
    				$key='employee_contact_'.$count.'_type_'.$contact_id;
    				$type=$this->input->post($key);
                    $key='employee_contact_'.$count.'_value_'.$contact_id;
    			    $value=$this->input->post($key);	
    				$contact=array(
    						
    								'type'	=>	$type,
    								'value'	=>	$value,
    							);
    	           
    				$this->db->where('id', $contact_id);
    				$this->db->update('employees_contacts', $contact); 
    
    				$count=$count+1;
    			}
                $employee_old_contacts=$employee_contacts->num_rows();
            }

		
		 	$new_employee_contacts=$employee_total_contacts - $employee_old_contacts;
			$new_contacts_count=$employee_old_contacts+1;
			while($new_contacts_count<=$employee_total_contacts)
			{
			     
				 $key='employee_contact_'.$new_contacts_count.'_type';
				 $type=$this->input->post($key);
                 $key='employee_contact_'.$new_contacts_count.'_value';
				 $value=$this->input->post($key);
					
				 $contact=array(
								'institute_id'	=>	$this->auth->institute_id(),
								'campus_id'		=>	$data['campus_id'],
								'type'			=>	$type,
								'value'			=>	$value,
								'employee_id'	=>	$employee_id,
								'status'		=>	'active',
								'created_by'	=>	$data['created_by'],
								'created_on'	=>	$data['created_on'],
                                'is_default'    =>  1								
							);
					
				$this->db->insert('employees_contacts',$contact);
				$new_contacts_count=$new_contacts_count+1;
              
			
			}
		}
        $image=$this->input->post('userfile');
        if($image!='')
        {
           $this->upload_image($employee_id);
        }
		 return 1;
	
	}

	
	function search_employee()
	{
		if($this->input->post('search_button'))
		{
			$employee_id=$this->input->post('employee_id');
			$employee=$this->employees_model->get_employee($employee_id);
			$employee=$employee->row();
			Template::set('employee',$employee);
			Template::set_view('content/employee_details');
		}
		Template::render();	
	}
	function allocate_courses_to_lecturers()
	{
        $this->auth->restrict('Employees.Content.AllocateCourses');
       	$campus_id=$this->input->post('campus_id');
        $lecturers=$this->employees_model->get_all_employees_of_role_id('28',$campus_id);
        $courses_buffer=$this->courses_model->get_all_courses();
        $courses=$courses_buffer;
        
		if($this->input->post('allocate'))
		{
        	
    		date_default_timezone_set('asia/karachi');
    		
    		foreach($lecturers->result() as $lecturer)
			{
			    
				$lecturer_id=$lecturer->in_type_id;
				$courses=$courses_buffer;
        		foreach($courses->result() as $course)
				{
					$course_id=$course->id;
					$key='lecturer_id_'.$lecturer_id.'_course_id_'.$course_id;
                    
					if($this->input->post($key))
					{
						$data=array(
									'institute_id'	=>	$this->auth->institute_id(),
									'campus_id'		=>	$campus_id,
									'session_id'	=>	$this->auth->default_ims_session_id(),
									'lecturer_id'	=>	$lecturer_id,
									'course_id'		=>	$course_id,
									'status'		=> 'active',
									'remarks'		=> '',
									'created_by'	=>	$this->auth->user_id(),
									'created_on'	=>	date("Y-m-d H:i:s"),
									
									);
                        
						
						$this->employees_model->allocate_courses($data);
						
					}
                    else
                    {
                        $criteria=array(
									'institute_id'	=>	$this->auth->institute_id(),
									'campus_id'		=>	$campus_id,
									'session_id'	=>	$this->auth->default_ims_session_id(),
									'lecturer_id'	=>	$lecturer_id,
									'course_id'		=>	$course_id,
									
									
									);
						//var_dump($data);die();
						
						$this->employees_model->deallocate_courses($criteria);   
                    }
					
				}

			}
		}
        
        Template::set('lecturers',$lecturers);
        
        Template::set('courses',$courses_buffer);
        Template::set('campus_id',$campus_id);
		Template::set('toolbar_title','Course Allocation to Lecturers');
		Template::render();	
	
	}
   
	function allocate_lecturers_to_classes()
	{
	    
		date_default_timezone_set('asia/karachi');
	    $campuses=$this->campuses_model->get_show_campuses_dropdown_array();
        unset($campuses[-1]);
        //$this->employees_model->get_employees_by_role($campus_id,$role);
        $campuses=array_keys($campuses);
        $lecturers=array();
        $batches=array();
        foreach($campuses as $campus_id)
        {
            
            $lecturers[$campus_id]=$this->employees_model->get_employees_by_role($campus_id,'lecturer');
           
            $batches_general[$campus_id]=$this->batches_model->get_batches_in_campus($campus_id);
            
            
        }
        // var_dump($lecturers);die();
        if($this->input->post('submit_button'))
        {
            $this->db->trans_start();
            foreach($campuses as $campus_id)
            {
                foreach($lecturers[$campus_id]->result() as $lecturer)
                {
                    //var_Dump($lecturer);die();
                    $lecturer_id=$lecturer->in_type_id;
                    $criteria=array(
                                    'institute_id'      =>  $this->auth->institute_id(),
                                    'campus_id'         =>  $campus_id,
                                    'lecturer_id'       =>  $lecturer_id,
                                    
                                );
                    $this->db->delete('lecturers_classes',$criteria);
                }
                
            }
            $batches_raw=$this->input->post('batches');
            $batches_raw=array_keys($batches_raw);
            
            foreach($batches_raw as $batches)
            {
                   
                $batches=explode('_',$batches);
                
                $campus_id=$batches[0];
                $department_id=$batches[1];
                $program_id=$batches[2];
                $semester_no=$batches[3];
                $lecturer_id=$batches[4];
                $batch_id=$batches[5];
                $course_id=$batches[6];
                /** Remove this line **/
               // if($course_id==''){ $course_id=-1;}
               /** ----------------- **/
                $criteria=array(
                                'institute_id'      =>  $this->auth->institute_id(),
                                'campus_id'         =>  $campus_id,
                                'session_id'        =>  $this->auth->default_ims_session_id(),
                                'department_id'     =>  $department_id,
                                'program_id'        =>  $program_id,
                                'semester_no'       =>  $semester_no,
                                'lecturer_id'       =>  $lecturer_id,
                                'batch_id'          =>  $batch_id,
                                'course_id'         =>  $course_id
                                
                                  
                            );
                if($this->employees_model->allocation_exists($criteria))
                {
                   
                    $data=array(
                                    'status'            =>  'active',
                                    
                                );
                    $this->db->update('lecturers_classes',$data,$criteria);
                }
                else
                {
                    $data=array(
                                    'institute_id'      =>  $this->auth->institute_id(),
                                    'campus_id'         =>  $campus_id,
                                    'session_id'        =>  $this->auth->default_ims_session_id(),
                                    'department_id'     =>  $department_id,
                                    'program_id'        =>  $program_id,
                                    'semester_no'       =>  $semester_no,
                                    'lecturer_id'       =>  $lecturer_id,
                                    'batch_id'          =>  $batch_id,
                                    'course_id'         =>  $course_id,
                                    'status'            =>  'active',
                                    'remarks'           =>  '',
                                    'created_on'        =>  date("Y-m-d H:i:s"),
                                    'created_by'	=>	$this->auth->user_id(),
                                      
                                );
                    $this->db->insert('lecturers_classes',$data);
                    
                }
                
                    
                
          } 
          $this->db->trans_complete(); 
          Template::set_message('Teachers successfully allocated to the classes','success');
        }
        //var_Dump($campuses,$batches,$lecturers);die();
        Template::set('campuses',$campuses);
        Template::set('batches',$batches_general);
        //var_dump($batches);
        Template::set('lecturers',$lecturers);
		Template::set('toolbar_title',humanize('allocate_lecturers_to_classes'));
		Template::render();
	}
	
	function contact_form($contact_id='',$type='',$value='',$count)
	{
	   
		if($count=='')
		{
			$count=$this->uri->segment(5);
		}
    
		$name='employee_contact_'.$count.'_type';
		
		$options=array(
						'-1'	=>	'Select Type',
						'email'	=>	'Email',
						'cell'	=>	'Cell',
						'land'	=>	'Land',
						);
		if($contact_id!='' and $type!='' and $value!='')
		{
			//echo $name;
			$name='employee_contact_'.$count.'_type_'.$contact_id;
			echo form_dropdown($name, $options, set_value($name, $type), 'Type');
			$name='employee_contact_'.$count.'_value_'.$contact_id;
			echo form_input($name, set_value($name, $value), 'Value'); 
		/*	$name='contact_'.$count.'_id';
			echo form_input($name, set_value($name, $contact_id), 'ID'); */
			
							
		}
		else
		{
	       	$key='employee_contact_'.$count.'_type';
            $type=$this->input->post($key);
			echo form_dropdown($key, $options, set_value($key, $type), 'Type');
            $key='employee_contact_'.$count.'_value';
			echo form_input($key, set_value($key, $value), 'Value'); 
			$count=$count+1;
			$key='employee_contact_'.$count;
			?>
			<div id="<?php echo $key?>" ></div>
			<?php 				
		}		
				
	}
    function employees_attendance()
    {
        Template::set('toolbar_title', lang('employee_attedance') );
        if($this->input->post('show_attendance_sheet'))
        {
            $data['institute_id']        = $this->auth->institute_id();
        	if(count($this->auth->show_campuses()>1))
            {
                 $data['campus_id']        = $this->input->post('campus_id');
            }
            else
            {
    		  $data['campus_id']          = $this->auth->campus_id();
            } 
           // $data['department_id']        = $this->input->post('department_id');
            $data['date']                 = $this->input->post('date');
            $data['shift']                 = $this->input->post('shift');
            $errors=array();
            if($data['department_id']==-1)
            {
                $errors[]='department_not_given';
            }
            if($data['shift']==-1)
            {
                $errors[]='shift_not_given';
            }
            if($data['date']=='mm/dd/yyyy')
            {
                $errors[]='date_not_given';
            }
            if(!$errors)
            {
               
               
                if($data['department_id']=='*')
                {
                    unset($criteria['department_id']);
                }
                $presence_statuses=$this->employees_model->get_attendance_statuses();
                
                $criteria=array(
        								'institute_id'	=>	$data['institute_id'],	
        								'campus_id'		=>	$data['campus_id'],
        							    
                                        'date_on'          =>  $data['date'],			
                                );
                 $attendances=$this->employees_model->get_attendance($criteria);
                 
                 if($attendances->num_rows()>0)
                 {
                    
                    Template::set('attendances',$attendances);
                    Template::set_message('The attendance has already been taken.','danger');
                    Template::set_view('content/attendance_sheet_edit');
                    
                 }  
                 else
                 {
                    $criteria=array(
        								'institute_id'	=>	$data['institute_id'],	
        								'campus_id'		=>	$data['campus_id'],
        							    'status'        =>   'active'								
        															
        															
                        	);
                    $employees=$this->employees_model->get_employees_in_($criteria);
                    Template::set('employees',$employees);
                    Template::set_view('content/attendance_sheet');
                 }
                
            }
            
            
            Template::set('employees_criteria',$data);
            Template::set('presence_statuses',$presence_statuses);
            Template::set('errors',$errors);
        }
        if(isset($_POST['submit_button']))
        {   
           
            $data['institute_id']        = $this->auth->institute_id();
	        if($this->auth->has_permission('Campuses.Content.View'))
            {
                $data['campus_id']        = $this->input->post('campus_id');
            }
            else
            {
    		  $data['campus_id']          = $this->auth->campus_id();
            } 
            $data['department_id']        = $this->input->post('department_id');
            $data['date_on']                 = $this->input->post('date');
            $data['shift']                 = $this->input->post('shift');
            $data['session_id']           = $this->auth->default_ims_session_id();
            //$data['created_by']        = $this->auth->user_id();
//    		$data['created_on']        = date("Y-m-d H:i:s");
            $criteria=array(
    								'institute_id'	=>	$data['institute_id'],	
    								'campus_id'		=>	$data['campus_id'],
    							//	'department_id'	=>	$data['department_id'],								
    															
    														
          	                 );
            $data['created_by']        = $this->auth->user_id();
    		$data['created_on']        = date("Y-m-d H:i:s");
            
            $result=$this->employees_model->get_employees_in_($criteria); 
            
            
            foreach($result->result() as $row)
			{
               
			    unset(	$data['batch_id'],$data['department_id'],$data['program_id'], $data['semester_no'],$data['batch_id'],$data['course_id'],$status);
				$employee_id=$row->id;
                $department_id=$row->department_id;
				$name=$this->employees_model->get_name($employee_id);
				$employee=$this->employees_model->get_employee($employee_id);
                $allocation_criteria=array(
                            'lecturer_id'   =>  $employee_id,
                            'session_id'    =>  $this->auth->default_ims_session_id(),
                            'status'        =>  'active'
                        );
                $course_allocations=$this->employees_model->get_allocated_courses($allocation_criteria);
                
                if($course_allocations->num_rows()>0 )
                {
                   
                    foreach($course_allocations->result() as $allocation)
                    {
                        
                        $batch_id=$allocation->batch_id;
                        $course_id=$allocation->course_id;
                        $name='employee_id_'.$employee_id.'_batch_id_'.$batch_id.'_course_id_'.$course_id;
                        $batch=$this->batches_model->get_batch($batch_id);
                        $department_id=$batch->department_id;
                        $program_id=$batch_id->program_id;
                        $semester_no=$batch->semester_no;
                        $data['batch_id']=$batch_id;
                        $data['department_id']=$department_id;
                        $data['program_id']=$program_id;
                        $data['semester_no']=$semester_no;
                        $data['batch_id']=$batch_id;
                        $data['course_id']=$course_id;
                       
                        if(in_array($name,array_keys($_POST)))
                        {
                            
                            $status='present';
                            
                        }
                        else
                        {
                            $status='absent';
                        }
                        $data['employee_id']=$employee_id;
                        $data['department_id']=$department_id;
        				$data['status']=$status;
                        
        				$this->db->insert('employees_attendance',$data);
                        
                    }
                }
                else
                {
                    $status=$this->input->post('employee_id_'.$employee_id);
                    $data['employee_id']=$employee_id;
                    $data['department_id']=$department_id;
    				$data['status']=$status;
                    $criteria=array(
                                        'date_on'       =>  $data['date_on'],
                                        'employee_id'   =>  $data['employee_id'],
                                        'institute_id'	=>	$data['institute_id'],	
                                        
                                    
                                    );
                    if($this->employees_model->attendance_exists($criteria))
                    {
                        if($this->auth->has_permission('Employees.Content.EditAttendance') )
                        {
                            $this->employees_model->update_attendance($criteria,$status);    
                        }
                        
                    }
                    else
                    {
                        
                        
        				$this->db->insert('employees_attendance',$data);
                    }
                    
                    
                }
			
                			
				
            }
            Template::set_message('Attendance saved for '.$result->num_rows().' employees.','success');
        }
     
       Template::render();
    
    }
    function upload_image($employee_id)
    {
          
            $this->load->helper('path');
            $path= set_realpath($path).'bonfire/modules/employees/assets/images';   
            $config['upload_path'] = $path;
    		$config['allowed_types'] = 'jpg';
    		$config['max_size']	= '10000';
    		$config['max_width']  = '10000';
    		$config['max_height']  = '10000';
            $config['file_name']  = $employee_id;
           	$config['overwrite']  = true;
            
    		$this->load->library('upload', $config);
            $this->upload->do_upload();
            
    	
    }
    function all_employees()
    {
        if ($action = $this->input->post('delete'))
		{
		 
			if ($action == lang('employees_delete'))
			{
				$checked = $this->input->post('checked');

				if (is_array($checked) && count($checked))
				{
					$result = FALSE;
					foreach ($checked as $pid)
					{
						$result = $this->delete($pid);
                        
					}

					if ($result)
					{
						Template::set_message(count($checked) .' '. lang('employees_delete_success'), 'success');
					}
					else
					{
						Template::set_message(lang('employees_delete_failure') . $this->courses_model->error, 'error');
					}
				}
				else
				{
					Template::set_message(lang('employees_delete_error') . $this->courses_model->error, 'error');
				}
			}
		}
        $criteria['institute_id']=$this->auth->institute_id();
        $criteria['campus_id']=$this->auth->campus_id();
        $criteria['status']='active';
        $records=$this->db->get_where('employees',$criteria);
        if($this->input->post('update_roles') and $this->auth->has_permission('IMS_Users.Content.Edit') )
        {
            foreach ($records->result() as $record)
            {
                
                $user_id=$this->ims_users_model->get_user_id($record->id,'employees');
                $this->ims_users_model->update_role($user_id,$_POST['role_id_'.$user_id]);
            }
        }
        
        Assets::add_js($this->load->view('content/js', null, true), 'inline');
		
        
        
        $roles=$this->ims_users_model->get_all_roles();
		Template::set('records', $records);
        Template::set('roles', $roles);
		Template::set('toolbar_title', lang('employees_manage'));
        Template::render();        
    }
    function recycle_bin()
    {
        if ($this->input->post('restore'))
        { 
         
          $checked = $this->input->post('checked');
           
    		if (is_array($checked) && count($checked))
    		{
    		  
					foreach ($checked as $pid)
					{
						$result = $this->restore($pid);
                        
					}

					if ($result)
					{
						Template::set_message(count($checked) .' '. lang('employees_delete_success'), 'success');
					}
					
				}
				else
				{
					Template::set_message(lang('employees_delete_error') . $this->courses_model->error, 'error');
				}
			}
	
        
        $criteria['institute_id']   =$this->auth->institute_id();
        $criteria['status']         ='deleted';
        $records=$this->db->get_where('employees',$criteria);
        
        Template::set_view('content/all_employees');
		Template::set('records', $records);
		Template::set('toolbar_title', lang('recycle_bin'));
		Template::render();        
    }
    
    function employees_hierarchy()
    {
        $this->employees_model->get_employees_hierarchy();
    }
    
    function    course_wise_attendance()
    {
        
        Template::set('toolbar_title', 'Course Wise Attendance');
		Template::render(); 
    }
    function    manage_leaves()
    {
        $this->auth->restrict('Employees.Content.LeavesView');
        $employee_id=$this->uri->segment(5);
        if($this->input->post('submit_button') and $this->auth->has_permission('Employees.Content.LeavesCreate'))
        {
            $from=$this->input->post('from');
            $to=$this->input->post('to');
            $reason=$this->input->post('reason');
            $status=$this->input->post('status');
            $data=array(
                            'institute_id'  =>  $this->auth->institute_id(),
                            'employee_id'  =>  $employee_id,
                            'from'      =>  $from,
                            'to'        =>  $to,
                            'reason'    =>  $reason,
                            'ims_session_id'    =>  $this->auth->default_ims_session_id(),
                            'status'    =>  $status,
                            'created_by'    =>  $this->auth->user_id()
                        
                        );
                
            $this->db->insert('employees_leaves',$data);
            
        }
       
        
        $leaves=$this->employees_model->get_employee_leaves($employee_id);
        Template::set('employee_id',$employee_id);
        Template::set('records',$leaves);
        Template::render();
        
    }
    function     edit_leave()
    {
        $this->auth->restrict('Employees.Content.LeavesEdit');
        $employee_id=$this->uri->segment(5);
        $leave_id=$this->uri->segment(6);
        if($this->input->post('submit_button') )
        {
            $from=$this->input->post('from');
            $to=$this->input->post('to');
            $reason=$this->input->post('reason');
            $status=$this->input->post('status');
            $data=array(
                            
                            'from'      =>  $from,
                            'to'        =>  $to,
                            'reason'    =>  $reason,
                            'status'    =>  $status,
                          
                        
                        );
                
            $this->db->update('employees_leaves',$data,array('id'=>$leave_id));
            
        }
        
        
        $leave=$this->employees_model->get_leave($leave_id);
        $leaves=$this->employees_model->get_employee_leaves($employee_id);
        Template::set('employee_id',$employee_id);
        Template::set('leave',$leave);
        Template::set('records',$leaves);
        Template::set_view('content/manage_leaves');
        Template::render();
        
    }
}