<!--<div class="well shallow-well">
	<?php render_filter_first_letter(lang('us_filter_first_letter')); ?>
</div>
-->
<div class="admin-box">
	<h3><?php echo lang('messages') ?></h3>

	<ul class="nav nav-tabs" >
		<li <?php echo $filter=='all_messages' ? 'class="active"' : ''; ?>><a href="<?php echo site_url().'/'.SITE_AREA?>/content/messages/outbox?filter=all_messages"><?php echo lang('all_messages')?></a></li>
		<li <?php echo $status=='pending' ? 'class="active"' : ''; ?>><a href="<?php echo $current_url .'?status=pending&type='.$this->input->get('type').'&campus_id='. $campus_id; ?>"><?php echo lang('pending')?></a></li>
        <li <?php echo $status=='queued' ? 'class="active"' : ''; ?>><a href="<?php echo $current_url .'?status=queued&type='.$this->input->get('type').'&campus_id='. $campus_id; ?>"><?php echo lang('queued')?></a></li>
		<li <?php echo $status=='failed' ? 'class="active"' : ''; ?>><a href="<?php echo $current_url .'?status=failed&type='.$this->input->get('type').'&campus_id='. $campus_id; ?>"><?php echo lang('failed')?></a></li>
		<li <?php echo $status=='sent' ? 'class="active"' : ''; ?>><a href="<?php echo $current_url .'?status=sent&type='.$this->input->get('type').'&campus_id='. $campus_id; ?>"><?php echo lang('sent')?></a></li>
		<li class="<?php echo $filter=='type' ? 'active ' : ''; ?>dropdown">
			<a href="#" class="drodown-toggle" data-toggle="dropdown">
				<?php echo lang('by_type')?> <?php echo isset($type) ? ": $type" : ''; ?>
				<b class="caret light-caret"></b>
			</a>
			<ul class="dropdown-menu">
			<?php
             $types=array(
                                'text'  =>  'Text',
                               // 'email'  =>  'Email',
                               // 'post'  =>  'Post',
                                
                            );
             $keys=array_keys($types);
             foreach ($keys as $key) : ?>
				<li>
					<a href="<?php echo site_url(SITE_AREA .'/content/messages/outbox?type='. $key.'&status='.$status.'&campus_id='. $campus_id) ?>">
						<?php echo $types[$key]; ?>
					</a>
				</li>
			<?php endforeach; ?>
			</ul>
		</li>
        
	</ul>

	<?php echo form_open($this->uri->uri_string()) ;?>

	<table class="table table-striped">
		<thead>
			<tr>
                <th><?php echo lang('sr_no');?></th>
                <?php if($this->auth->has_permission('Messages.Content.Send')  ) : ?>
					<th class="column-check"><input type="checkbox" id="check-all"/></th>
					<?php endif;?>
                <th><?php echo lang('message');?></th>
                <th><?php echo lang('recipient');?></th>
                <th><?php echo lang('recipient_type');?></th>
                <th><?php echo lang('status');?></th>
                <th><?php echo lang('contact');?></th>
                <th><?php echo lang('scheduled_on');?></th>
                <th><?php //echo lang('scheduled_by');?></th>
                <th><?php //echo lang('sent_on');?></th>
                <th><?php //echo lang('sent_by');?></th>
                <th><?php echo lang('action');?></th>
			</tr>
		</thead>
		<tbody>
  <?php
    $count=$this->uri->segment(5)+1;
    if(!is_numeric($count))
    {
        $count=1;
    }
    
	foreach($result->result() as $item)
	{
		$item_id=$item->id;
		$recipient_id=$item->recipient_id;
		$recipient_type=$item->recipient_type;
        if($recipient_type=='students')
        {
            $name=$this->students_model->get_name($recipient_id);
        }
        elseif($recipient_type=='employees')
        {
            $name=$this->employees_model->get_name($recipient_id);
        }
        elseif($recipient_type=='parents')
        {
            $name=$this->parents_model->get_parent_name($recipient_id);
        }
		$contact=$item->contact;
		$message_id=$item->message_id;
		$message_type=$item->message_type;
		$status=$item->status;
		$scheduled_by=$item->created_by;
		$scheduled_on=$item->created_on;
		$sent_by=$item->sent_by;
		$sent_on=$item->sent_on;
		?>
	<tr>
    <td>
  	<?php echo $count;?>
    </td>
	<?php if ($this->auth->has_permission('Messages.Content.Send') and $status!='sent') 
    { ?>
					<td><input type="checkbox" name="checked[]" value="<?php echo $item->id ?>" /></td>
					
 <?php }
    else
    {
        ?><td>&nbsp;</td><?php
    }
                    ?>
					
    <td>
  <?php if($message_id!='')
		{
			echo $this->messages_model->get_title($message_id);
		} 
		if($message_id=='' or $message_id=='-1')
		{
			echo $item->body;		
		}
        
			?>  
    </td>
    <td>
   	<?php 
		if($recipient_type!='anonymous')
		{
			echo $name;
            echo '<br />ID: '.$recipient_id;
		}
		else
		{
			echo 'Anonymous';
		}
		
	?>
    </td>
    <td>
   	<?php echo $recipient_type; ?>
    </td>
    <td>
   	<?php echo $status; ?>

    </td>
	<td>
   	<?php echo $contact; ?>
    </td>
	<td>
   	<?php echo $scheduled_on; ?>

    </td>
	<td>
   	<?php //echo $this->employees_model->get_name($this->ims_users_model->get_in_type_id($scheduled_by,'employees')); ?>

    </td>
	<td>
   	<?php 
	if($sent_on=='0000-00-00 00:00:00')
	{
		//echo "Not sent";
	}
	else
	{
		//echo $sent_on;
	}
	?>

    </td>
	<td>
<?php 
	if($sent_by=='0' or $sent_by='')
	{
		//echo "Not sent";
	}
	else
	{
        //echo $this->employees_model->get_name($this->ims_users_model->get_in_type_id($item->sent_by,'employees')); 
	}
	?>

    </td>
    <td>
    <?php
	if($status=='pending') 
	{
	   
	   $pending_exists=TRUE;
       
		echo anchor('admin/content/messages/send/'.$item_id,lang('send'));
		echo "|";
		echo anchor('admin/content/messages/update_status/deleted/'.$item_id,lang('delete'));
		echo "|";
		echo anchor('admin/content/messages/update_status/cancelled/'.$item_id,lang('messages_cancel'));
	}
	elseif($status=='cancelled')
	{
		echo anchor('admin/content/messages/send/'.$item_id,lang('send'));
		echo "|";
		echo anchor('admin/content/messages/update_status/deleted/'.$item_id,lang('delete'));
	}
	elseif($status=='deleted')
	{
		echo anchor('admin/content/messages/send/'.$item_id,lang('send'));
		echo "|";
		echo anchor('admin/content/messages/update_status/pending/'.$item_id,lang('pending'));
		
	}
	elseif($status=='failed')
	{
		echo anchor('admin/content/messages/send/'.$item_id,'Re Send');
		echo "|";
		echo anchor('admin/content/messages/update_status/deleted/'.$item_id,lang('delete'));
		echo "|";
	//	echo anchor('admin/content/messages/update_status/cancelled/'.$item_id,'Cancel');		
	}
	elseif($status=='sent')
	{
		echo 'Sent';
	}
	?>
    </td>
	</tr>
		<?php		
	$count=$count+1;		
	}
	?>
		</tbody>
        	<?php if (isset($result) ) : ?>
			<tfoot>
				<?php if ($this->auth->has_permission('Messages.Content.Send')) : 
                if($status=='failed')
                {
        
                ?>
				<tr>
					<td colspan="12">
						<?php 
                        echo lang('with_selected') ?>
						<input type="submit" name="send" id="delete-me" class="btn btn-primary" value="Re <?php echo lang('send') ?>" />
					</td>
				</tr>
				<?php
                }
                 endif;
                if($pending_exists and $this->auth->has_permission('Messages.Content.Delete'))
                {
        
                ?>
				<tr>
					<td colspan="12">
						<?php 
                        echo lang('with_selected') ?>
						<input type="submit" name="delete" id="delete-me" class="btn btn-danger" value=" <?php echo lang('delete') ?>" />
					</td>
				</tr>
				<?php
                }
                ?>
			</tfoot>
		<?php endif;?>

	</table>
	<?php echo form_close(); ?>

	<?php 
    $extra_string='?status='.$this->input->get('status').'&type='.$this->input->get('type').'&campus_id='.$this->input->get('campus_id');
    echo $this->pagination->create_links($extra_string); ?>

</div>
