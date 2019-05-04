<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Messages_model extends BF_Model {

	protected $table		= "messages";
	protected $key			= "id";
	protected $soft_deletes	= false;
	protected $date_format	= "datetime";
	protected $set_created	= false;
	protected $set_modified = false;
    
	
	function get_statuses()
	{
		$statuses=array(
							'-1'		=>	'Select Status',
							'active'	=>	'Active',
							'blocked'	=>	'Blocked'						
						);	
		return $statuses;
	}
	function get_types()
	{
		$types=array(
							'-1'		=>	'Select Type',
							'email'		=>	'Email',
							'text'		=>	'Text',						
							'printed'	=>	'Printed'
						);	
		return $types;
	}

	function get_all_messages()
	{
		$criteria['institute_id']=$this->auth->institute_id();
        $criteria['campus_id']=$this->auth->campus_id();
		$criteria['status']='active';
		$result=$this->db->get_where($this->table,$criteria);
		//echo $this->db->last_query();
		$messages=array();
		$messages[-1]='Select a message';
		foreach($result->result() as $message)
		{
			$messages[$message->id]=$message->title;
		}
		return $messages;
	}
    function    get_predefined_message($criteria)
    {
   	    $result=$this->db->get_where('messages',$criteria);
        return  $result;
    }
    function get_messages($criteria,$order_by)
	{
		$criteria['institute_id']=$this->auth->institute_id();
        if($order_by=='')
        {
            $this->db->order_by('sent_by','asc');    
        }
        else
        {
            $this->db->order_by($order_by,'desc'); 
        }
        
        $this->db->order_by('message_type','asc');
		$result=$this->db->get_where('outbox',$criteria);
	   
		return $result;
	}
	
	function get_body($message_id)
	{
	//	$criteria['institute_id']=$this->auth->institute_id();
		$criteria['id']=$message_id;
		$result=$this->db->get_where($this->table,$criteria);
		//echo $this->db->last_query();
		$row=$result->row();
		
		$body=$row->body;
		return $body;
	}
	function get_title($message_id)
	{
		$criteria['institute_id']=$this->auth->institute_id();
		$criteria['id']=$message_id;
		$result=$this->db->get_where($this->table,$criteria);
		//echo $this->db->last_query();
		$row=$result->row();
		
		$title=$row->title;
		
		return $title;
	}

	function log_message($data,$as_notification=false)
	{
        $data['contact']=str_replace('-','',$data['contact']);
        if($as_notification)
        {
            $data['is_notification']='1';
            
        }
        if($data['contact']=='')
        {
            $data['message_type']='';
            $this->db->insert('outbox',$data);
            
        }
        if($data['contact']!='' )
        {
            $this->db->insert('outbox',$data);
           // return; 
            $item_id=$this->db->insert_id();
            //return 1;
            $contact=$this->messages_model->get_contact($item_id);
            $message=$this->messages_model->get_message($item_id);
            return  0;
            $status=$this->messages_model->send_message($data['contact'],$data['body'],$item_id);
            if($status)
            {
            	$this->messages_model-> update_status('sent',$item_id);	
            	
            }
            else
            {
            	$this->messages_model-> update_status('failed',$item_id);	
            }

        }
        $this->activity_model->log_activity($this->auth->user_id(), 'Message sent to outbox'.': ' . $this->db->insert_id() . ' : ' . $this->input->ip_address(), 'messages');
	
	}
	function get_welcome_message()
	{
		$criteria['institute_id']=$this->auth->institute_id();
		$criteria['title']='Welcome';
		$result=$this->db->get_where($this->table,$criteria);
		$row=$result->row();
		$message_id=$row->id;
		return	$message_id;
				
	}
    function get_message_id($title)
	{
	   
		$criteria['institute_id']=$this->auth->institute_id();
		
		$result=$this->db->get_where($this->table,$criteria);
        
        foreach($result->result() as $message)
        {
           
            if(humanize($message->tilte)==humanize($title))
            {
                $message_id=$message->id;
                return $message_id;
            }
        }
		$row=$result->row();
		$message_id=$row->id;
		return	$message_id;
				
	}
	function update_status($status,$item_id)
	{
		
		if($status)
		{
//		    echo 'in update';
			date_default_timezone_set('asia/karachi');
			$data=array(
						'status'	=>	'sent',
						'sent_by'	=>	$this->auth->user_id(),
						'sent_on'	=>	date("Y-m-d H:i:s"),
					);
            
	       $criteria['id']=$item_id;
      
            $this->db->update('outbox',$data,$criteria);
            $this->activity_model->log_activity($this->auth->user_id(), 'Message sent to user'. ' : ' . $this->input->ip_address(), 'messages');
		
		}
      
		
	}
	function send_message_deprecated($cell,$message)
	{
		$institute_id=$this->auth->institute_id();
		$campus_id=$this->auth->campus_id();
		
		$loaded_credits=$this->credits_model->get_loaded_credits($institute_id,$campus_id);
		
		$used_credits=$this->credits_model->get_used_credits($institute_id,$campus_id);
		
		$credits=$loaded_credits-$used_credits;
		
		if($credits>0)
		{
			if($this->send($cell,$message))
			{
				
				return TRUE;
			}
			else
			{
				
				return FALSE;
			}						
		}
		else
		{	
				?>
				<script language="javascript" >
                	alert('You do not have credits or active SMS service. Contact Shujjat.com for more details.');
                </script>
				<?php
				return FALSE;
		}
	}
    function deduct_credits($needed_credits,$criteria)
    {
        $query='
                    UPDATE  credits
                    SET     credits=credits-'.$needed_credits.'
                    WHERE   institute_id="'.$criteria['institute_id'].'"    
                ';
        if($criteria['campus_id']!='')
        {
            $query=$query.' 
                                AND campus_id="'.$criteria['campus_id'].'"
                            ;';
        }
       // echo $query;
       $this->db->query($query);
       
    }
	function send_message($to,$body,$item_id,$institute_id,$campus_id)
    { 
        
        $criteria=array();
        if($institute_id=='')
        {
            $criteria['institute_id']=$this->auth->institute_id();
            
            
        }
        else
        {
            $criteria['institute_id']=$institute_id;
            
            
        }     
        $institute_id=$criteria['institute_id'];   
        if($campus_id=='')
        {
            $criteria['campus_id']=$this->auth->campus_id();
            
        }
        else
        {
            $criteria['campus_id']=$campus_id;
        }
      // print_r($criteria);
          $available_credits=$this->credits_model->get_available_credits($criteria);
        //die();
       //die();
        if($available_credits<=0 )
        {
            	?>
				<script language="javascript" >
                	alert('You do not have credits or active SMS service. Contact Shujjat.com for more details.');
                </script>
				<?php
				return FALSE;
        }
        else
        {
        
          
            
            $first_digit=substr($to,0,1);
            if($first_digit==='0')
            {
                $to=substr($to,1);
                
            }
       //    echo 'innn';
          	$sms_alias=$this->institutes_model->get_name($institute_id);// return 1;
    	//	$message = $body.'[From: '.$sms_alias."@jugnoo.net]";
            //$message = $sms_alias.': '.$body;
          //   date_default_timezone_set('Asia/Karachi');
            $message =$body;
            $service_provider='zong';
            
            switch($institute_id)
            {
                        
                      case 44:
                        
                        $url=('http://fastsmsalerts.com/quicksms');
                        
                        $data='id=rchcclhr&pass=rchcclhr1281&mask=Central-Clg&to=92'.$to.'&portable=&lang=english&msg='.(urlencode($message)).'type=json/xml';
                       
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        
                        $result = curl_exec($ch);
                        
                       
                        
                        curl_close($ch);
                       
                        if(!strpos($result,'300'))
                        {
                            
                            return  true;
                        }                            
                        else
                        {
                            return false;
                        }
                        break;
                      case 2:
                        break;
                                               
                      default:
                          
                            $type = "xml";
                            $id = "cimsapi";
                            $pass = "cimsapi";
                            $lang = "English";
                            $mask = "CIMS Gulbrg";
                            
                            $message = urlencode($message);
                            $mask = urlencode($mask);
                            // Prepare data for POST request
                            $data ="id=".$id."&pass=".$pass."&msg=".$message."&to=92".$to."&lang=".$lang."&mask=".$mask."&type=".$type;
                           
                            //echo $data;die();
                            $ch = curl_init('http://www.brandedsmsportal.com/API/api-ch.php');
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            //curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/soap+xml', 'charset=utf-8'));
                            $result = curl_exec($ch);
                           
                            curl_close($ch);
                            
                            if(strpos($result,'300'))
                            {
                                return  true;
                            }                            
                            else
                            {
                                return false;
                            }
                            

                       break;
            }
        }
    }
    function update_message_info($body_length)
    {
        $allowed_length=160;
        
        $length=strlen($body);
        if($length<=$allowed_length)
        {
            $needed_credits=1;
        }
        else
        {
              $needed_credits=$length/$allowed_length;
                   
        }
        
        
        if($needed_credits==0){$needed_credits=1;}
        
        $this->deduct_credits($needed_credits,$criteria);
                            
                           
    }
    function objectToArray($d)
	{
        
		if (is_object($d))
		{
			$d = get_object_vars($d);
		}
 
		if (is_array($d))
		{
			//return array_map(__FUNCTION__, $d);
            return $d;
		}
		else
		{
			return $d;
		}	
    }
	function send_sms ($cell, $message) 
	{
		/* THE SMS API WORK BEGINS HERE */
		include_once"sms.php";
		 
		$apikey = "d2b4d558520e80a39d52";	//Your API KEY
		$sms = new sendsmsdotpk($apikey);	//Making a new sendsms dot pk object
		
		//TESTING isValid
		if ($sms->isValid())
		{
		//	echo "Your key IS VALID";
		}
		else
		{
			//echo "KEY: " . $apikey . " IS NOT VALID";
		}
		
		//echo $cell;//TESTING SENDSMS
      
        
		if ($sms->sendsms($cell, $message, 0))
		{
			
			return true;
		}
		else
		{	
				
			return false;
		}

		/* THE SMS API WORK ENDS HERE */
		
		
	}
	
	
	
	function get_contact($item_id)
	{
	  
	//	$criteria=$this->auth->criteria();
		$criteria['id']=$item_id;
		$result=$this->db->get_where('outbox',$criteria);
		$row=$result->row();
		$contact=$row->contact;
		return $contact;
	}
	function get_message($item_id)
	{
	
	//	$criteria=$this->auth->criteria();
		$criteria['id']=$item_id;
		$result=$this->db->get_where('outbox',$criteria);
		$row=$result->row();
		$message_id=$row->message_id;
		$alias=$this->institutes_model->get_alias();
		if($message_id!='')
		{
			$title=$this->get_title($message_id);
			$body=$this->get_body($message_id);			
			
			$message=$body."[".$alias."]";	
		}
		else
		{
			$body=$row->body;
			$message=$body.'['.$alias.']';
			
			
		}
		return $message;
	}
    function get_outbox($criteria)
    {
        if($criteria['created_on']!='')
        {
            $criteria['created_on']=substr($criteria['created_on'],0,10);
            $this->db->where('SUBSTR(created_on,1,10) = ', substr($criteria['created_on'],0,10));
            unset($criteria['created_on']);
        }
        if($criteria['from']!='')
        {
            $this->db->where('SUBSTR(created_on,1,10) >=', substr($criteria['from'],0,10));
           unset($criteria['from']);
        } 
        if($criteria['to']!='')
        {
            $this->db->where('SUBSTR(created_on,1,10) <=', substr($criteria['to'],0,10));
            unset($criteria['to']);
        }
        $outbox=$this->db->get_where('outbox',$criteria);
        return $outbox;
    }
    function get_message_count($criteria)
    {
      
        $outbox=$this->get_outbox($criteria);
        $messages=$outbox->num_rows();
        return $messages;
    }
    function get_date_range($from,$to)
    {
        if($from==$to)
        {
            return $from;
        }
        else
        {
            $start = strtotime($from);
            $end = strtotime($to);
            $dates = array();
            for ($i = $start; $i <= $end; $i += 24 * 3600)
            {
                  $dates[]= date("Y-m-d ", $i);
            }
            //print_r($dates);
            return $dates;
        }
    }


    function express_send_message($message_data)
    {
      //  print_r($message_data); 
      /*  $fp = fopen("test.txt", "a");
        fwrite($fp, 'sending');
      */
        $this->messages_model->log_message($message_data);
        $item_id=$this->db->insert_id();
        $contact=$this->messages_model->get_contact($item_id);
        $message=$this->messages_model->get_message($item_id);
        $institute_id=$message_data['institute_id'];
        $campus_id=$message_data['campus_id'];
        /*    $fp = fopen("test.txt", "a");
        fwrite($fp, $contact);
        */    
        $status=$this->messages_model->send_message($contact,$message,$item_id,$institute_id,$campus_id);
        if($status)
		{
			$this->messages_model->update_status('sent',$item_id);	
			
		}
		else
		{
			$this->messages_model->update_status('failed',$item_id);	
		}
        
      //  die();
    }
    function respond_gateway()
    {
        /**
        $this->sms_queue_model->prepare_output();
        $this->load->library('/envaya/example/www/gateway.php');
      */ 
    }
    function send_delayed_messages()
    {
        $query='
                    SELECT  *
                    FROM    outbox
                    WHERE   status="pending";
                ';
        $result=$this->db->query($query);
        foreach($result->result() as $row)
        {
            $item_id=$row->id;
            $body=$row->body;
            $contact=$row->contact;
            $institute_id=$row->institute_id;
            $campus_id=$this->campus_id;
            $sms_sending_mode=$this->institutes_model->get_sms_service($institute_id);
            $ims_gateways_not_available=TRUE;
            if($sms_sending_mode=='fast'  or $ims_gateways_not_available)
            {
                $status=$this->send_message($contact,$body,$item_id,$institute_id,$campus_id);
                $this->update_status($status,$item_id);
            }
             
        }
        
    }
    function zong()
    {
        $message='Allah ';
         $client = new SoapClient("http://115.186.182.11/csws/service.asmx?wsdl");
                       
                        $config=array(
                        "Src_nbr" => "923114467881",
                        "Password" => "hafizgno011",
                        "Dst_nbr" => '92'.'3234532952',
                        //"Dst_nbr" => '92'.$to,
                        "Mask" => $mask,
                        "Message" => $message,
                        "TransactionID" => time().((int) rand(0,99)),
                        );
             //           print_r($config);
                        //die();
                        $sms_sending_result = $client->SendSMS($config);
                       
                       $response_arr = $this->objectToArray($sms_sending_result);
                        
                        $d = get_object_vars($sms_sending_result);
                        $a=get_object_vars($d['SendSMSResult']);
                           print_r($a);
    }
    function    send_inquiry_follow_up_message($student_id,$follow_up_counter,$cron=false)
    {
        
        $student_name=$this->students_model->get_name($student_id);
        switch($follow_up_counter)
        {
            case    '1':
                $criteria=array(
                            
                            'title'         =>  'First Follow Up'
                        );
                break;
            case    '2':
                $criteria=array(
                            
                            'title'         =>  'Second Follow Up'
                        );
                break;
             case    '3':
                $criteria=array(
                            
                            'title'         =>  'Third Follow Up'
                        );
                break;
        }
        if(!$cron)
        {
            $institute_id=$this->auth->institute_id();  
            $campus_id=$this->auth->campus_id();  
        }
        else
        {
            $student=$this->students_model->get_student($student_id);
            $institute_id=$student->institute_id;
            $campus_id=$student->campus_id;
            
            
        }
        $criteria['institute_id']=$institute_id;
        $message=$this->get_predefined_message($criteria);
        $row=$message->row();
        $body=$row->body;
        $contact=$this->students_model->get_contact($student_id);
        $body=str_replace('<name>',$student_name,$body);  
        $message_data=array(
								'institute_id'	=>	$institute_id,
								'campus_id'		=>	$campus_id,	
								'recipient_id'	=>	$student_id,
								'recipient_type'	=>	'students',
								'contact'		=>	$contact,
								'body'			=>	$body,
								'message_type'	=>	'text',
								'status'		=>	'pending',
								'created_by'	=>	$this->auth->user_id(),
								'created_on'    =>  date("Y-m-d H:i:s")
				            );
        
        $this->log_message($message_data);
                 
    }
    function     send_message_by_title($title,$student_id)
    {
        $criteria=array(
                            'institute_id'	=>	$this->auth->institute_id(),
                            'title'         =>  $title
                        );
        
        $message=$this->get_predefined_message($criteria);
      
        $message=$message->row();
        $body=$message->body; 
        $body=str_replace('<id>',$student_id,$body); 
        $contact=$this->students_model->get_contact($student_id);    
        $message_data=array(
								'institute_id'	=>	$this->auth->institute_id(),
								'campus_id'		=>	$this->auth->campus_id(),	
								'recipient_id'	=>	$student_id,
								'recipient_type'	=>	'students',
								'contact'		=>	$contact,
								'body'			=>	$body,
								'message_type'	=>	'text',
								'status'		=>	'pending',
								'created_by'	=>	$this->auth->user_id(),
								'created_on'    =>  date("Y-m-d H:i:s")
				            );
        //var_Dump($criteria,$message_data);die();
        $this->log_message($message_data);
    }  
    function    send_scheduled_messages()
    {
        $result=$this->students_model->incomplete_enrollment('','',true);
        $now = time(); // or your date as well
         foreach ($result->result() as $row)
         {
            $criteria=array('id'=>$row->id);  
            if($row->first_follow_up=='' )
            {
                $sending_date= $row->created_on;
                $datediff = $now - $sending_date;
                $days= round($datediff / (60 * 60 * 24));
                if($days>=$this->institutes_model->get_setting('institutes','','first_follow_up_sending_delay'))
                {
                    $data=array(
                                    'first_follow_up_remarks'   =>  'Automatic SMS dispatch by the Robot',
                                    'second_follow_up_date'      =>  $now,
                                );
                    
                    $this->db->update('students',$data,$criteria);
                    $this->messages_model->send_inquiry_follow_up_message($student_id,1,true);
                } 
            }
            elseif($row->first_follow_up!='' )
            {
                if($row->second_follow_up=='' )
                {
                    $sending_date= $row->first_follow_up_date;
                    $datediff = $now - $sending_date;
                    $days= round($datediff / (60 * 60 * 24));
                    if($days>=$this->institutes_model->get_setting('institutes','','second_follow_up_sending_delay'))
                    {
                        $data=array(
                                        'second_follow_up_remarks'   =>  'Automatic SMS dispatch by the Robot',
                                        'second_follow_up_date'      =>  $now,
                                    );
                        $criteria=array('id'=>$student_id);
                        $this->db->update('students',$data,$criteria);
                        $this->messages_model->send_inquiry_follow_up_message($student_id,2,true);
                    } 
                }
                elseif($row->third_follow_up=='' )
                {
                    $sending_date= $row->second_follow_up_date;
                    $datediff = $now - $sending_date;
                    $days= round($datediff / (60 * 60 * 24));
                    if($days>=$this->institutes_model->get_setting('institutes','','third_follow_up_sending_delay'))
                    {
                        $data=array(
                                        'third_follow_up_remarks'   =>  $third_follow_up_remarks,
                                        'third_follow_up_date'      =>  $now,
                                    );
                        $criteria=array('id'=>$student_id);
                        $this->db->update('students',$data,$criteria);
                        $this->messages_model->send_inquiry_follow_up_message($student_id,3,true);
                    } 
                }   
            }    
            
         }
    }   
      
}

