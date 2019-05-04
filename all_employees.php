<div class="admin-box">
	<h3><?php echo lang('employees');?></h3>
	<?php echo form_open($this->uri->uri_string()); ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<?php if ($this->auth->has_permission('Employees.Content.Delete') && isset($records) ) : ?>
					<th class="column-check"><input class="check-all" type="checkbox" /></th>
					<?php endif;?>
					
				   	<th><?php echo lang('edit');?></th>
                    <th><?php echo lang('campus_id');?></th>
                    
            		<th>Name</th>
                    <th>Role</th>
            		<th><?php echo lang('bank_account_no');?></th>
            		<th><?php echo lang('cnic');?></th>
            		<th><?php echo lang('permanent_address');?></th>
            		<th><?php echo lang('temporary_address');?></th>
            		<th><?php echo lang('email');?></th>
            		<th><?php echo lang('status');?></th>
            					
				</tr>
			</thead>
			<?php if (isset($records) ) : ?>
			<tfoot>
				<?php if ($this->auth->has_permission('Employees.Content.Delete')) : ?>
				<tr>
					<td colspan="16">
						<?php echo lang('with_selected');
                        if($this->auth->has_permission('IMS_Users.Content.Edit'))
                        {
                            ?>
                            <input type="submit" name="update_roles" id="update-role" class="btn btn-primary" value="Update Roles" onclick="return confirm('Do you want to update these roles?')">
                            
                            <?php
                        }
                        
                        if($this->uri->segment(4)!='recycle_bin')
                        {
                            ?>
						  <input type="submit" name="delete" id="delete-me" class="btn btn-danger" value="<?php echo lang('employees_delete') ?>" onclick="return confirm('<?php echo lang('employees_delete_confirm'); ?>')">
                            <?php 
                        }
                        else
                        {
                            ?>
						  <input type="submit" name="restore" id="restore-me" class="btn btn-primary" value="<?php echo lang('restore') ?>" />
                            <?php
                        }
                        ?>
					</td>
				</tr>
				<?php endif;?>
			</tfoot>
			<?php endif; ?>
			<tbody>
            <?php $serial_no=0; ?>
			<?php if (isset($records) ) : ?>
			<?php foreach ($records->result() as $record) : ?>
				<tr>
                    <?php $serial_no=$serial_no+1; ?>
					<?php if ($this->auth->has_permission('Employees.Content.Delete')) : ?>
					<td><input type="checkbox" name="checked[]" value="<?php echo $record->id ?>" /></td>
					<?php endif;?>
					
				<?php if ($this->auth->has_permission('Employees.Content.Edit')) : ?>
				<td><?php echo anchor(SITE_AREA .'/content/employees/edit/'. $record->id, '<i class="icon-pencil">&nbsp;</i>' .  $serial_no) ?></td>
				<?php else: ?>
				<td><?php //echo $record->institute_id ?></td>
				<?php endif; ?>
			
				
				<td><?php echo $this->campuses_model->get_name($record->campus_id);?></td>
                
                <td>
                    
                    <?php 
                        echo $record->first_name.' '.$record->last_name;
                        echo '<hr />';
                        
                        echo anchor(SITE_AREA.'/content/employees/manage_leaves/'.$record->id,'<span class="glyphicon glyphicon-stop"></span>Leaves');
                        if($user->role_id==28)
                        {
                            
                            echo '<br />'.anchor(SITE_AREA .'/reports/academic_calendar/view_lecturer_time_table/'.$record->id,'<span class="glyphicon glyphicon-stop"></span>Time Table');
                        } 
                
                    ?>
                </td>
                
                
                
                <td>
                <?php
                    
                    
                    if ($this->auth->has_permission('IMS_Users.Content.Edit') ) 
                    {
                        $user_id=$this->ims_users_model->get_user_id($record->id,'employees');
                        $user=$this->ims_users_model->get_user($user_id);
                        echo anchor('admin/settings/users/edit/'.$user_id,'Reset Password'); 
                        echo '<br />';                
                        if($user->active=='1')
                        {
                            echo 'Active';
                            echo anchor('admin/content/ims_users/block/employees/'.$user_id,'|Block');
                        }
                        elseif($user->active=='0')
                        {
                            echo 'Blocked';
                            echo anchor('admin/content/ims_users/activate/employees/'.$user_id,'|Activate');
                            
                        } 
                        $criteria=array('role_id'=>$user->role_id);
                        $role_result=$this->db->get_where('roles',$criteria);
                        $role=$role_result->row();
                        $role_name=$role->role_name;
                        //var_dump($user);
                     //  print_r($roles);
       	                echo form_dropdown('role_id_'.$user_id, $roles, $user->role_id);
                        ?>
                        
                        <?php
                    }
                    else
                    {
                        echo $this->ims_users_model->get_role_name($this->ims_users_model->get_role_id($user_id));    
                    } 
                                       
                ?>
                </td>
                <td><?php echo $record->bank_account_no?></td>
                <td><?php echo $record->cnic?></td>
                <td><?php echo $record->permanent_address?></td>
                <td><?php echo $record->temporary_address?></td>
                <td><?php echo $record->email?></td>
                <td><?php echo $record->status?></td>
				
				</tr>
			<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="16">No records found that match your selection.</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	<?php echo form_close(); ?>
</div>