<?php
class ajax extends CI_Controller{
	public function __construct(){
		parent::__construct();
		// $ref = $_SERVER['HTTP_REFERER'];
		// $refArr = explode('/', $ref);
		// if(!(end($refArr)=='new-account.php')){
			// if(!isset($_SESSION['user_data']))
			// {
				// redirect('session_logout/logout_session');
			// }
		// }
		$this->load->model('wsModel');
		$this->load->model('MachineModel');
		$this->load->model('CustomersModel');
		$this->load->model('ordermodel');
		$this->load->model('EmployeeModel');
		$this->load->model('change_pass_model');
		$this->load->model('Paf_model');
		$this->load->model('ProductsModel');
		$this->load->model('inventorymodel');
		$this->load->model('dasmodel');
		$this->load->model('lco_complaints_model');
		$this->load->model('receiptmodel');
		$this->load->model('Process_Model');
		
        //$this->load->model('Notification_model');
		$this->load->model('bulkoperationmodel','blkopr');
                $this->load->library('Stb');
		$this->load->library('email');
		$this->load->library('CommonFunctions');
		$this->load->model('generic_users_model');
	}
	public function check(){
		$val = $_POST['newuser'];	
		$sql="SELECT count(username) as num FROM employee WHERE username = '$val'";
		$query=$this->db->query($sql);
		$unm=$query->row();
		//check if the name exists or not
		if($unm->num == 0)
			echo 1;
		else
			echo 0;
	}

	
	
	
	//function added by deepak
	public function list_stbs(){

		$processmodel = new Process_Model();

		$trans_search_id=$this->input->post('transaction_id_search');
		$operation_name='osd_bg_process';
		$data['records']=$processmodel->getTransProcessList($trans_search_id,$operation_name);
		$vals="";
		$records=$data['records'];
		if(count($records)>0){
			$vals="<table cellspacing='1' cellpadding='2' border='0' class='mygrid' >
			<thead> 
					<tr>
						
						<th>Transaction Id</th>
						<th>Process</th>
						<th>Start date</th>
						<!--<th>End date</th>-->
						<th>status</th>
						<th>Ip address</th>
                                                <th>Error File</th>
					</tr>
				</thead>";
			foreach($records as $record){
				if(!empty($record->file_name)){
                                                $upload_path = uploads_path();
                                                if (base_url() =="/")
                                                {
                                                    $base_url = realpath(".").base_url();
                                                }
                                                else
                                                $base_url = base_url();	 	
                                                $path = $base_url.$upload_path."error_files"."/".$record->file_name;
                                                $path = str_replace(" ", "", $path);
                                                $handle = @fopen($path,'r');
                                                if($handle !== false){
                                                   $file_content= '<a title="Download File" href="javascript:void(0)" class="download_error" id='.$record->process_id.'>'.$record->file_name.'</a>';
                                                } else {
                                                   $file_content= '-';
                                                }
                                            }else{
                                               $file_content= '-';
                                            }


				$vals.="<tbody>

                                     
									<td>".$record->trans_id."</td>



									<td>".$record->process."</td>
									<td>".getDateFormat($record->start_time, 1)."</td>

									<td>".$record->status."</td>
									<td>".$record->ip_address."</td>                                      
									<td> ".$file_content ."
									</td>

									</tr>
				</tbody>";		

			}
			$vals.="</table>";

		}
		echo $vals;

		

		
		
           
	}
	
	
	
	
	public function check_mobile() {
         $mobile = $_POST['mob'];
        $cust_id = isset($_POST['cust_id']) ? $_POST['cust_id'] : 0;
        $old_mobile = isset($_POST['old_mobile']) ? $_POST['old_mobile'] : '';
         $response=$this->CustomersModel->check_mobile($mobile, $cust_id,$type=1,$old_mobile);
        echo $response;
        
    }

    public function check_employee_mobile() {
        $emp_cond = "";
        $mobile = $_POST['mob'];
        $unique_mobile= $_SESSION['dealer_setting']->USER_MOBILENUMBER_UNIQUENESS;
        //  $code=$_SESSION['dealer_setting']->COUNTRY_ISD_CODE;
        //$mobile =$code.$mobile;
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : 0;
        $did = $_SESSION['user_data']->dealer_id;
        if ($employee_id != 0) {
            $emp_cond = " AND employee_id!=$employee_id";
        }
        $sql_str = "select count(mobile_no) res_mobile,mobile_no from employee where mobile_no='$mobile' and dealer_id=$did $emp_cond ";
        $query = $this->db->query($sql_str);
        $res = $query->row();
        //print_r($res);
        if (($unique_mobile == 1 && $res->res_mobile == 0) || (!$unique_mobile) ) {
            echo 1;
        } else {
            echo 0;
        }
    }
	public function check_business_name()
	{
		$emp_cond = "";
		$business_name= trim($_POST['business_name']);
		
		$employee_id = isset($_POST['employee_id'])?$_POST['employee_id']:0;
		$did = $_SESSION['user_data']->dealer_id;
		if($employee_id!=0){
			$emp_cond = " AND employee_id!=$employee_id";
		}
		$sql_str = "select count(employee_id) employee_id,mobile_no from employee where business_name='$business_name' and dealer_id=$did $emp_cond ";
		$query = $this->db->query($sql_str);
		$res = $query->row();
		//print_r($res);
		if($res->employee_id==0)
		{
			echo 1;
		}
		else
		{
			echo  0;
		}
	}
	public function check_zone_code()
	{
		$zone_code = $_POST['zone'];
		$did = $_SESSION['user_data']->dealer_id;
		$sql_str = "SELECT count(zone_code) AS res_code,zone_code from employee where zone_code='$zone_code' and dealer_id=$did";
		$query = $this->db->query($sql_str);
		$res = $query->row();
		if($res->res_code==0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
		
	}
	 public function check_lco_code() {
        $zone_code = $_POST['zone'];
        $did = $_SESSION['user_data']->dealer_id;
        $cond='';
        $employee_id=  (isset($_POST['employee_id'])) ? trim($_POST['employee_id']) : 0;
        $parent_id=  (isset($_POST['parent_id'])) ? trim($_POST['parent_id']) : 0;
        if($employee_id>0)
        {
            $cond="and employee_id<>$employee_id";
        }
        $sql_str = "SELECT count(lco_code_manual) AS res_code,lco_code_manual from employee where lco_code_manual='$zone_code' and parent_id='$parent_id' and dealer_id=$did $cond";
        $query = $this->db->query($sql_str);
        $res = $query->row();
        if ($res->res_code == 0) {
            echo 1;
        } else {
            echo 0;
        }
    }
	
	
	
	 public function check_dist_subdist_code() {
        $lco_code = $_POST['lco_code'];
        $user_type = $_POST['user_type'];
        $cond='';
        $employee_id=  (isset($_POST['employee_id'])) ? trim($_POST['employee_id']) : 0;
        if($employee_id>0)
        {
            $cond="and employee_id<>$employee_id";
        }
        $did = $_SESSION['user_data']->dealer_id;
        $sql_str = "SELECT count(lco_code) AS res_code,lco_code from employee where users_type='$user_type' and lco_code='$lco_code' and dealer_id=$did $cond";;
        $query = $this->db->query($sql_str);
        $res = $query->row();
        if ($res->res_code == 0) {
           echo 1;
        } else {
            echo 0;
        }
    }
	public function check_dist_lco_code()
	{
		$lco_code = $_POST['lco'];
		$did = $_SESSION['user_data']->dealer_id;
		$sql_str = "SELECT count(lco_code) AS res_code,lco_code from employee where users_type='DISTRIBUTOR' and lco_code='$lco_code' and dealer_id=$did";
		$query = $this->db->query($sql_str);
		//echo $this->db->last_query();
		$res = $query->row();
		if($res->res_code==0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
		
	}
        // check account number modified by pratap
	public function check_accnum()
	{
		$cond='';
                $customerId=(isset($_POST['custId']))?trim($_POST['custId']):0;
                $did = $_SESSION['user_data']->dealer_id;
                $anum=(isset($_POST['num']))?$_POST['num']:'';
                $r_len=strlen(str_replace(' ', '', $anum));
                $a_len=strlen($anum);
                if($customerId>0){
                     $cond = "AND customer_id<>$customerId";
                }
		$sql_str = "select count(account_number) acc_num from customer where account_number='$anum' and dealer_id=$did $cond";
		$query = $this->db->query($sql_str);
		$res = $query->row();
		//print_r($res);
		if($res->acc_num==0 && $r_len==$a_len)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}

	}
	
	public function insert_machine(){
		$machine = new MachineModel();
		
		echo $machine->insertmachine(str_replace(' ', '', trim($_POST['n'])),trim($_POST['d']),$_POST['h'],$_POST['disableInactive'],$_POST['id']);	
		
		
	}
		
	public function isSame(){
		$machine = new MachineModel();
		$num = $machine->isSame($_POST['e'],$_POST['m']);		
		if($num == 1 ) echo '0';
		else if($num == 0){
			$res=$machine->assignmachine($_POST['e'],$_POST['m']);
			echo $res;
			//echo 'Machine Record Updated!';
		}
	}
        public function customNumberExists(){
                $custom_number = $_POST['cn'];	
                //$dealer_id = $_SESSION['user_data']->dealer_id;
				//$sql="SELECT count(customnumber) as count FROM customer WHERE customnumber = '$custom_number' AND dealer_id=$dealer_id";
				//$query=$this->db->query($sql);
				//if($query->row()){
                    //check if the name exists or not
                //    if($unm->count > 0)
                //            echo 1;
                //    else
                //            echo 0;
                //}else{
                //    echo 1;
                //}
                $customer = new CustomersModel();
                echo $customer->customNumberExists($custom_number);
        }
        
        public function setting_preference(){			
			$coun=$_POST['coun'];
			$ds = isset($_POST['ds'])?$_POST['ds']:0;
			$sql_str=$this->db->query("SELECT id,name FROM eb_location_states where country_code='$coun'");
			$res = $sql_str->result();
			if($res){
				$states='<option value="0">Select State</option>';
				foreach($res as $row)
					$states .= '<option value="'.$row->id.'"'.(($ds)?$ds==$row->id?' selected="SELECTED"':'':'').'>'.$row->name.'</option>';
				echo $states;
			}else{
				echo 0;
			} 
		
		}
        
                
	 public function select_locations(){	
		 $did = $_SESSION['user_data']->dealer_id;
		$state=$_POST['state'];
		$ds = isset($_POST['ds'])?$_POST['ds']:0;
		if($ds>0)
		{
			$sql_str=$this->db->query("SELECT city FROM customer where customer_id=$ds and dealer_id=$did order by location_name");
			$r= $sql_str->result();
		}
		if($r)
			$d=$r[0]->city;
		else
			$d='';
		$sql_str=$this->db->query("SELECT location_id,location_name FROM eb_location_locations where state_id=$state and dealer_id=$did order by location_name");
		$res = $sql_str->result();

		if($res){
			$loc='<option value="-1">Select location</option>';
			foreach($res as $row)
				$loc .= '<option value="'.$row->location_id.'"'.(($d)?$d==$row->location_id?' selected="SELECTED"':'':'').'>'.$row->location_name.'</option>';
			echo $loc;
		}else{
			echo 0;
		} 
	
		}
	public function getstock_item(){
		$loc=$_POST['stockloc'];
                $d=$this->input->post('item'); 

   
		if($loc){
				
			$sql="SELECT esi.item_name,es.item_id FROM eb_stock es INNER JOIN eb_stock_items  esi ON esi.item_id = es.item_id WHERE esi.dealer_id=".$_SESSION['user_data']->dealer_id." AND es.stock_location='$loc' group by es.item_id";
                        //$sql="SELECT esi.item_name,es.item_id FROM eb_stock es INNER JOIN eb_stock_items  esi ON esi.item_id = es.item_id WHERE esi.dealer_id=".$_SESSION['user_data']->dealer_id." AND es.stock_location='$loc' AND esi.is_setup_box<>1 group by es.item_id";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>-- Select Item --</option>";
			for($i=0;$i<$count_res;$i++){
				//echo $r[$i]->item_name;
				$loc .= '<option value="'.$r[$i]->item_id.'"'.(($d)?$d==$r[$i]->item_id?' selected="SELECTED"':'':'').'>'.$r[$i]->item_name.'</option>';
			
			}echo $loc;

		}
	}
        public function getstock_item1(){
		$loc=$_POST['stockloc'];
		$d=$this->input->post('item'); 

   
		if($loc){
				
			
                        $sql="SELECT esi.item_name,es.item_id FROM eb_stock es INNER JOIN eb_stock_items  esi ON esi.item_id = es.item_id WHERE esi.dealer_id=".$_SESSION['user_data']->dealer_id." AND es.stock_location='$loc' AND esi.is_setup_box<>1 group by es.item_id";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>-- Select Item --</option>";
			for($i=0;$i<$count_res;$i++){
				//echo $r[$i]->item_name;
				$loc .= '<option value="'.$r[$i]->item_id.'"'.(($d)?$d==$r[$i]->item_id?' selected="SELECTED"':'':'').'>'.$r[$i]->item_name.'</option>';
			
			}echo $loc;

		}
	}
	public function getstock_stb_item(){
		$loc=$_POST['stockloc'];
                $d=$this->input->post('item'); 

   
		if($loc){
				
			$sql="SELECT esi.item_name,es.item_id FROM eb_stock es INNER JOIN eb_stock_items  esi ON esi.item_id = es.item_id WHERE esi.dealer_id=".$_SESSION['user_data']->dealer_id." AND es.stock_location='$loc' AND esi.is_setup_box='1' group by es.item_id";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>Select</option>";
			for($i=0;$i<$count_res;$i++){
				//echo $r[$i]->item_name;
				$loc .= '<option value="'.$r[$i]->item_id.'"'.(($d)?$d==$r[$i]->item_id?' selected="SELECTED"':'':'').'>'.$r[$i]->item_name.'</option>';
			
			}echo $loc;

		}
	}


public function getstock_ast_item(){
		$loc=$_POST['stockloc'];
                $d=$this->input->post('item'); 
  $did=$_SESSION['user_data']->dealer_id;
   
		if($loc){
				
			/*$sql="SELECT a.stock_serial_number, i.item_name  FROM  `eb_stock_assets` a
                              INNER JOIN eb_stock s ON a.stock_serial_number=s.serial_number and s.dealer_id=$did
                              INNER JOIN eb_stock_items i ON i.item_id=a.item_id
                              where  s.stock_location='$loc' and  i.dealer_id='$did' ";*/
                        $sql="SELECT a.stock_serial_number, i.item_name  FROM  `eb_stock_assets` a
                              INNER JOIN eb_stock s ON a.stock_serial_number=s.serial_number and s.dealer_id=$did
                              INNER JOIN eb_stock_items i ON i.item_id=a.item_id
                              where a.stock_serial_number NOT IN (SELECT sr.againist_stock FROM eb_stock_replacement sr INNER JOIN eb_stock s1 ON s1.serial_number=sr.againist_stock and s1.dealer_id=$did)
                              and s.stock_location='$loc' and  i.dealer_id='$did' ";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>Select</option>";
			for($i=0;$i<$count_res;$i++){
				//echo $r[$i]->item_name;
				$loc .= '<option value="'.$r[$i]->stock_serial_number.'">'.$r[$i]->item_name.'('.$r[$i]->stock_serial_number.')</option>';
			
			}echo $loc;

		   }
	}



public function get_realted_items(){
		$m=$_POST['customer'];
                $did=$_SESSION['user_data']->dealer_id;
		if($m){
				
			$sql="SELECT s.item_name FROM  `eb_stock_outward_log` l INNER JOIN eb_stock_items s ON s.`item_id` = l.item_id WHERE l.`outward_to` ='$m'";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>Select</option>";
			for($i=0;$i<$count_res;$i++){
				//echo $r[$i]->item_name;
                                  /*if($r[$i]->serial_number)
                                  {              $serial= $r[$i]->serial_number;
                                                 $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial' and dealer_id=$did");
                                                 @$rs = $sql1->row()->mac_address;
                                                 $mac=($rs)?'( '.$rs.' )':'';             
                                   }*/
				$loc .= '<option value="'.$r[$i]->serial_number.'">'.$r[$i]->serial_number.'</option>';
			}echo $loc;
		}
	}

	
	public function get_items()
	{
		$item=$_POST['itemid'];

		$serial_num = array();
		if(!empty($_POST['serial']))
		{
			$serial=$_POST['serial'];
			$serial_num=explode(",",$serial);
		}	

		
		$sip = -1;
		if(isset($_SESSION['modules']['CAS']) && $_SESSION['modules']['CAS']){
			$sip=isset($_POST['sip'])?$_POST['sip']:-1;
		}
		$stockloc=$_POST['stockloc'];
		$flag = $this->input->post('flag')?$this->input->post('flag'):0;
		$supplierid = ($this->input->post('supplierid') && $this->input->post('supplierid') !='-1')?$this->input->post('supplierid'):0;
		$modelno = ($this->input->post('modelno') && $this->input->post('modelno') != '-1')?$this->input->post('modelno'):'';
		$cond = "";
		$cond1 = "";
		$loc = "";
		$usemac='';
		$backendsid="";
		$joinCondition = '';
		$invflag = $this->input->post('invflag')?$this->input->post('invflag'):'';
		if(isset($_SESSION['modules']['CAS']) && $_SESSION['modules']['CAS']){
			
				if($sip !='-1')
				{
					$cond .= " AND bs.backend_setup_id=$sip ";
					$cond1 = " AND s.backend_setup_id=$sip ";

				}
				
		}
		/*$cond .= (isset($_POST['defective']) && $_POST['defective']=='1')?" AND (s.defective_stock = 1 OR s.isSurrended=1) ":" AND (s.defective_stock=0 OR s.isSurrended!=1)  AND serial_number NOT IN (
		   SELECT cd.box_number FROM customer_device cd 
		   INNER JOIN eb_stock s ON s.serial_number= cd.box_number AND s.stock_location = $stockloc AND s.backend_setup_id=$sip
		   WHERE cd.device_closed_on IS NULL
		)";*/
		if($flag == 1)
		{
			if($supplierid!=0)
			{
				$joinCondition .= " INNER JOIN eb_suppliers es ON es.supplier_id=s.supplier_id ";
				$cond .= " AND s.supplier_id=$supplierid ";
			}
			if($modelno!='')
			{
				$cond .= " AND s.model_number='$modelno' ";
			}
		}
		$cond .= (isset($_POST['defective']) && $_POST['defective']==1)?" AND (s.defective_stock = 1 OR s.isSurrended=1) AND s.show_in_service_center=0   ":" AND (s.defective_stock=0 AND s.isSurrended!=1) AND s.show_in_service_center=0  AND serial_number NOT IN (
		   SELECT cd.box_number FROM customer_device cd 
		   INNER JOIN eb_stock s ON s.serial_number= cd.box_number AND s.stock_location = $stockloc $cond1
		   WHERE cd.device_closed_on IS NULL 
		) ";
		if($item){
				
			$sql="SELECT s.stock_id,s.serial_number,s.mac_address,s.vc_number,s.stock_status,s.batch,s.batch_date,bs.use_mac FROM eb_stock s 
			 INNER JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id
			 $joinCondition 
			WHERE s.dealer_id=".$_SESSION['user_data']->dealer_id." AND s.is_trash=0 AND s.item_id=$item AND s.stock_location=$stockloc and s.is_temp_blocked=0 and s.status=2 $cond";		

			
			$res=$this->db->query($sql);

		//	echo $this->db->last_query();
			
			$r=$res->result();
			$count_res=count($res->result());
			if($flag == 0){
				$loc = "<option value='-1'>Select</option>";
				for($i=0;$i<$count_res;$i++){
					//echo $r[$i]->item_name;
									  /*   if($r[$i]->serial_number)
									  {              $serial= $r[$i]->serial_number;
													 $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial' and dealer_id=".$_SESSION['user_data']->dealer_id);
													 @$rs = $sql1->row()->mac_address;
													 $mac=($rs)?'( '.$rs.' )':'';             
									   }  */
					$loc .= '<option value="'.$r[$i]->serial_number.'">'.$r[$i]->serial_number .'</option>';
				
				}
			}
			else
			{
				$i = 1;
				if(count($r)>0)
				{
					foreach($r as $res)
					{
							$loc .= '<tr ><td width="27" style="text-align:center;"><input type="checkbox" name="serial1[]" class="check_all" value="'.$res->serial_number.'"'.(in_array($res->serial_number,$serial_num)?' checked="CHECKED"':'').'/></td>
							
								<td width="23">'.$i++.'</td>
								<td width="94">'.$res->serial_number.'</td>
								<td width="108">'.(($res->use_mac==1)?$res->mac_address:$res->vc_number).'</td>
								<td width="30">'.$res->stock_status.'</td>
								<td width="69">'.$res->batch.'</td>
								<td width="50">'.$res->batch_date.'</td></tr>';
						
					}
				}
				else
				{
					$loc = '<tr><td colspan="7">No Records..</td></tr>';
				}
			}
			echo $loc;
		}
	}
	public function get_items_inv()
	{
		$item=$_POST['itemid'];
		$stockloc=$_POST['stockloc'];
		$flag = $this->input->post('flag')?$this->input->post('flag'):0;
		$supplierid = ($this->input->post('supplierid') && $this->input->post('supplierid') !='-1')?$this->input->post('supplierid'):0;
		$modelno = ($this->input->post('modelno') && $this->input->post('modelno') != '-1')?$this->input->post('modelno'):'';
		//$cond = "";
		$loc = "";
		$usemac='';
		$backendsid="";
		$joinCondition = '';
		$invflag = $this->input->post('invflag')?$this->input->post('invflag'):'';
		
		
		if($flag == 1)
		{
			if($supplierid!=0)
			{
				$joinCondition .= " INNER JOIN eb_suppliers es ON es.supplier_id=s.supplier_id ";
				$cond .= " AND s.supplier_id=$supplierid ";
			}
			if($modelno!='')
			{
				$cond .= " AND s.model_number='$modelno' ";
			}
		}
		if($item){
				
			$sql="SELECT s.stock_id,s.serial_number,s.mac_address,s.vc_number,s.stock_status,s.batch,s.batch_date  FROM eb_stock s 
			 $joinCondition 
			
			WHERE s.dealer_id=".$_SESSION['user_data']->dealer_id." AND s.item_id=$item AND s.stock_location='$stockloc' and s.in_stock='1' and s.status!='3' ";		
			$res=$this->db->query($sql);
			//echo $this->db->last_query();
			$r=$res->result();
			$count_res=count($res->result());
			if($flag == 0){
				$loc = "<option value='-1'>Select</option>";
				for($i=0;$i<$count_res;$i++){
					//echo $r[$i]->item_name;
									  /*   if($r[$i]->serial_number)
									  {              $serial= $r[$i]->serial_number;
													 $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial' and dealer_id=".$_SESSION['user_data']->dealer_id);
													 @$rs = $sql1->row()->mac_address;
													 $mac=($rs)?'( '.$rs.' )':'';             
									   }  */
					$loc .= '<option value="'.$r[$i]->serial_number.'">'.$r[$i]->serial_number .'</option>';
				
				}
			}
			else
			{
				$i = 1;
				if(count($r)>0)
				{
					foreach($r as $res)
					{
						$loc .= '<tr><td><input type="checkbox" name="serial1[]" value="'.$res->serial_number.'"/></td>
								<td>'.$i++.'</td>
								<td>'.(($res->use_mac==1)?$res->mac_address:$res->vc_number).'</td>
								<td>'.$res->serial_number.'</td>
								<td>'.$res->stock_status.'</td>
								<td>'.$res->batch.'</td>
								<td>'.$res->batch_date.'</td></tr>';
					}
				}
				else
				{
					$loc = '<tr><td colspan="7">No Records..</td></tr>';
				}
			}
			echo $loc;
		}
	}
	public function get_item_type(){
		$item=$_POST['itemid'];
		if($item){
				
			$sql="SELECT is_individual FROM eb_stock_items WHERE dealer_id=".$_SESSION['user_data']->dealer_id." AND item_id=$item";
			$res=$this->db->query($sql);
			echo $res->row()->is_individual;
		} else {
			echo "0";
		}
	}
    public function get_item_type1(){
            $serial = $_POST['serial'];
            $location = $_POST['location'];
            $did=$_SESSION['user_data']->dealer_id;
            $sql=$this->db->query("SELECT  SUM(sol.`qty`)as sum, m.measure_name FROM eb_stock_assets sol 
                                INNER JOIN eb_stock s ON s.serial_number = sol.stock_serial_number and s.dealer_id=$did
                                INNER JOIN eb_stock_items si ON si.item_id=s.item_id 
                                INNER JOIN eb_measures m ON m.measure_id = si.unit_of_measure  and m.dealer_id=$did
                                WHERE sol.stock_serial_number='$serial' and sol.location_id=$location");
        // echo $this->db->last_query();
            $out_sum=$sql->row()->sum;  
            echo $out_sum."/".$sql->row()->measure_name;
         	
	}
	public function get_total_available_stock(){
		$item=$_POST['itemid'];
		$sip = $_POST['sip'];
		$cond1 = "";
		$stockloc=$_POST['stockloc'];
		$cond = "";
		if($sip !='-1')
		{
			$cond=" AND backend_setup_id=$sip";
			$cond1=" AND s.backend_setup_id=$sip";
			
		}
		$cond .= (isset($_POST['defective']) && $_POST['defective']==1)?" AND (defective_stock=1 OR isSurrended=1) AND show_in_service_center=0 ":" AND (defective_stock=0 AND isSurrended!=1) AND show_in_service_center=0  AND serial_number NOT IN (
		   SELECT cd.box_number FROM customer_device cd 
		   INNER JOIN eb_stock s ON s.serial_number= cd.box_number AND s.stock_location = $stockloc $cond1
		   WHERE cd.device_closed_on IS NULL
		) ";


		if($item)
		{
				
			$sql="SELECT serial_number FROM eb_stock WHERE dealer_id=".$_SESSION['user_data']->dealer_id." AND item_id=$item AND stock_location='$stockloc'  and status='2' $cond  AND is_trash=0";
		
			$res=$this->db->query($sql);
			//echo $this->db->last_query();
			echo $res->num_rows();
		}
	}


	public function getCustomerOutItems(){
		$cid = $this->input->post('cid');
                $ritem = $this->input->post('ritem');
				$did= $_SESSION['user_data']->dealer_id;
		$sel_qry = "select si.item_id, si.item_name from eb_stock_items si 
			    INNER JOIN  eb_stock_inventory_movement sol ON sol.item_id=si.item_id 
                            where sol.customer_id='$cid' AND sol.purpose=1 AND si.dealer_id=$did 
                            AND sol.inward_serial_number NOT IN (SELECT sr.againist_stock FROM eb_stock_replacement sr INNER JOIN eb_stock s1 ON s1.serial_number=sr.againist_stock and s1.dealer_id=$did) 
                            group by sol.item_id";
                /*$sel_qry = "select si.item_id, si.item_name from eb_stock_items si 
			    INNER JOIN eb_stock_outward_log sol ON sol.item_id=si.item_id where sol.outward_to='$cid' AND sol.dealer_id=$did  group by sol.item_id";*/
	
		$query = $this->db->query($sel_qry);
		$loc = "<option value='-1'>-- Select Item --</option>";
		foreach($query->result() as $row){
                        $select = ($ritem!='-1' && $ritem!='' && $row->item_id==$ritem)?'SELECTED':'';
			$loc .= "<option value='".$row->item_id."' $select >".$row->item_name."</option>";
		}
		echo $loc;
	}

public function getCustomerOutserials(){
		$cid = $this->input->post('cid');
                $itemid = $this->input->post('itemid');
                $did=$_SESSION['user_data']->dealer_id;
             // echo $itemid;
		$sel_qry = "SELECT ol.`inward_serial_number`, sl.location_name, sl.location_id  FROM `eb_stock_inventory_movement` ol 
                            INNER JOIN  eb_stock_location sl ON sl.location_id = ol.location_id
                            INNER JOIN eb_stock_items si ON si.item_id= ol.item_id AND si.dealer_id=$did 
                            WHERE ol.`item_id` ='$itemid'  AND ol.purpose=1 AND ol.`customer_id` = '$cid'
                            AND ol.inward_serial_number NOT IN (SELECT sr.againist_stock FROM eb_stock_replacement sr INNER JOIN eb_stock s1 ON s1.serial_number=sr.againist_stock and s1.dealer_id=$did)";
		$query = $this->db->query($sel_qry);
		$loc = "<option value='-1'>-- Select Serial Number --</option>";
		
              if($query)
              {
                foreach($query->result() as $row){
                  $loc .= '<option value="'.$row->inward_serial_number.'/'.$row->location_id.'">'.$row->inward_serial_number.' ('.$row->location_name.')</option>';
		}
               }
		echo $loc;
	}




        public function get_serials(){

  $item=$_POST['itemid'];
		
		if($item){
				
			$sql="SELECT serial_number FROM eb_stock WHERE dealer_id=".$_SESSION['user_data']->dealer_id." AND item_id=$item  AND in_stock <>'0' and status!='3'";
		
			$res=$this->db->query($sql);
			$r=$res->result();
			$count_res=count($res->result());
			$loc = "<option value='-1'>-- Select Serial Number --/option>";
			for($i=0;$i<$count_res;$i++){
                        //echo $r[$i]->item_name;
                        /* if($r[$i]->serial_number)
                            {              $serial= $r[$i]->serial_number;
                                            $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial'");
                                            @$rs = $sql1->row()->mac_address;
                                            $mac=($rs)?'( '.$rs.' )':'';             
                            }*/
                        //$loc .= '<option value="'.$r[$i]->serial_number.'">'.$r[$i]->serial_number.$mac.'</option>';
                        $loc .= '<option value="'.$r[$i]->serial_number.'">'.$r[$i]->serial_number.'</option>';
			}echo $loc;
		}
	}

         public function get_serial_quantity(){
		$serial = $this->input->post('serial');
                $location = $this->input->post('lid');
                $did=$_SESSION['user_data']->dealer_id;
		//$serial=$_POST['serial'];

		if($serial !="" && $serial !="-1"){
                    /* $sql1=$this->db->query("SELECT  * FROM eb_stock_location WHERE location_id='$location'");
                            foreach($sql1->result() as $loc)
                            {
                                $location_id=$loc->location_id;
                            }   */
                     $sql1=$this->db->query("SELECT  * FROM eb_stock WHERE stock_location='$location' and serial_number='$serial' AND dealer_id=$did");
                            foreach($sql1->result() as $loc)
                            {
                                $sid=$loc->stock_id;     
                            }   
    
					
			 $sql="SELECT item_qty FROM eb_stock WHERE serial_number='$serial' and  stock_id=$sid AND dealer_id=$did ";
		//die();
			$res=$this->db->query($sql);
			@$rs = $res->row()->item_qty;
			
                      
		
                       $sql1=$this->db->query("SELECT  SUM(`units`)as sum FROM eb_stock_outward_log WHERE serial_number='$serial' and location_id=$location AND dealer_id=$did" );
                                    
                           foreach($sql1->result() as $row)
					   {
                                                  
					     $out_sum=$row->sum;
				           }   
                             
                          
                              $rs=$rs-$out_sum; 
                                  
                             
                              echo $rs;
                   

                   }
	}  

        
         public function get_serial_warranty(){
		$serial = $_POST['id'];
                $did=$_SESSION['user_data']->dealer_id;
                  if($serial!='' && $serial!='-1'){                  
                             // echo $serial;
                   $sql=$this->db->query("SELECT `warranty_in_months`  FROM `eb_stock` WHERE `serial_number`='$serial' and dealer_id=$did");
                
                   $res = $sql->row()->warranty_in_months;
                       // echo $res;
                    if($res=='1') { 
            $sql1=$this->db->query("SELECT DATEDIFF( DATE_ADD(  `purchase_date` , INTERVAL  `warranty_period` MONTH ) , NOW( ) ) as day FROM  `eb_stock` 
					WHERE  `serial_number` =  '$serial' and dealer_id=$did "); 
                           $val = $sql1->row()->day;
                           
                               if($val < 0) echo" <font color='red'> Stock is Out of Warranty  </font> "; 
                               else echo" <font color='yellow'> Stock is In Warranty of ".$val." Days </font> "; 


                       }else if($res=='2'){

                      $sql1=$this->db->query("SELECT DATEDIFF( DATE_ADD(  `purchase_date` , INTERVAL  `warranty_period` DAY ) , NOW( ) ) as day FROM  `eb_stock` 
		                  		WHERE  `serial_number` =  '$serial' and dealer_id=$did"); 
                                   $val = $sql1->row()->day;
                           
                               if($val < 0) { echo" <font color='red'> Stock is Out of Warranty  </font> "; }
                               else { echo" <font color='yellow'> Stock is In Warranty ".$val." Days </font> ";}



                        } 
                  }       



                   
	}  
public function getItemLocation(){
	$location_id=$_POST['location_id'];
        $did=$_SESSION['user_data']->dealer_id;
        if($location_id!='' && $location_id!='-1'){    
		$sql = $this->db->query("select sl.location_id, sl.location_name FROM eb_stock_location sl  WHERE sl.location_id = '$location_id' and sl.dealer_id=$did");
		//echo $this->db->last_query();
		echo $sql->row()->location_id."/".$sql->row()->location_name;
        } else {
            echo "";
        }
}
		public function get_isd_code(){                      
			$coun=$_POST['coun'];			
			$sql_str=$this->db->query("SELECT  `isd_code`  FROM `eb_location_countries` WHERE  `iso`='$coun'");
			$res = $sql_str->row();
			if($res){
				$code=$res->isd_code;   
				echo $code;


			}else{
				echo 0;
			}		
		}
		
		public function select_projects()
		{
		$res = array();
		$select='';
			$reseller=$_POST['reseller'];
                        $project_id=$this->input->post('project_id')?$this->input->post('project_id'):'0';
                       $did = $_SESSION['user_data']->dealer_id;
                     $loc='<option value="0">Select Any Project</option>';
                 
			$sql_str=$this->db->query("SELECT * FROM `eb_reseller_projects` WHERE `reseller_id`=$reseller  and dealer_id=$did order by project_description");
			if($sql_str && $sql_str->num_rows()>0){
				$res = $sql_str->result();	
			}
			if(count($res)>0){
				foreach($res as $row){
                                        if($row->reseller_project_id==$project_id) $select="Selected=Selected";
					$loc .= '<option value="'.$row->reseller_project_id.'" '.$select.' >'.$row->project_description.'</option>';}
				
			}
                        echo $loc;
		}
                
        public function get_reports()
		{
		
			$module=$_POST['module'];
                        $did = $_SESSION['user_data']->dealer_id;
			$sql_str=$this->db->query("SELECT * FROM `accon_reports` WHERE `module_id`=$module and dealer_id=$did");
			if($res){
			$res = $sql_str->result();
			
				$loc='<option value="0">Select Report</option>';
				foreach($res as $row)
					$loc .= '<option value="'.$row->report_id.'">'.$row->report_name.'</option>';
				echo $loc;
			}else{
                            $loc='<option value="0">Select Report</option>';
				echo $loc;
			} 
		
		}
                
                  public function get_users()
		{
		
			$type=$_POST['type'];
                        $did = $_SESSION['user_data']->dealer_id;
                        if($type=='group')
                            $sql_str=$this->db->query("SELECT group_id as id,group_name as value FROM `groups` WHERE  dealer_id=$did");
                        else if($type=='user_type')
                            $sql_str=$this->db->query("SELECT `lovid` as id,`value` FROM `lovtable` WHERE setting='USERTYPES' and dealer_id=$did");
                        else if($type=='employee')
                            $sql_str=$this->db->query("SELECT 	employee_id as id,first_name as value FROM `employee` WHERE  dealer_id=$did and users_type!='DEALER'");
			if($res){
			$res = $sql_str->result();
			
				$loc='<option value="0">Select any option</option>';

				foreach($res as $row)
					$loc .= '<option value="'.$row->id.'">'.$row->value.'</option>';
				echo $loc;
			}else{
				echo 0;
			} 
		
		}
		public function getCountries(){
			$cnt = $_POST['cnt'];
			$sql_str = "SELECT * FROM `eb_location_countries` order by name";
			$query = $this->db->query($sql_str);
			//$default_country = ($_SESSION['dealer_setting']->DEFAULT_COUNTRY!='')?$_SESSION['dealer_setting']->DEFAULT_COUNTRY:'';
			$default_country = isset($cnt)?$cnt:$_SESSION['dealer_setting']->DEFAULT_COUNTRY;
			$vals = '<option value="-1">Select Country</option>';
			if($query){
				$res = $query->result();				
				foreach($res as $country){			
					$vals .= '<option value="'.$country->iso.'"'.(($default_country!='')?($default_country==$country->iso)?' SELECTED="selected"':'':'').'>'.$country->name.'</option>';
				}
			}
			echo $vals;
			
		}
		public function getSelectedCountryStates(){
			$selected_country = $_POST['cnt'];
			$selected_state = $_POST['dflt_st'];
            $vals = '<option value="-1">Select State</option>';
			if($selected_country!=''){
				$sql_str = "SELECT * FROM eb_location_states WHERE country_code='$selected_country' order by name";
				$query = $this->db->query($sql_str);
				if($query){
					$res = $query->result();
					
					foreach($res as $state){
						$vals .= '<option value="'.$state->id.'"'.(($selected_state!='')?($selected_state == $state->id)?' SELECTED="selected"':'':'').'>'.$state->name.'</option>';
					}
					
				}
			}
			echo $vals;
		}

		public function getSelectedStateDistricts(){
			$selected_country = $_POST['cnt'];
			$selected_state = $_POST['dflt_st'];
			$selected_district = isset($_POST['dflt_distr'])?$_POST['dflt_distr']:'';
            $vals = '<option value="-1">Select District</option>';
			if($selected_country!=''){
				$sql_str = "SELECT * FROM eb_districts WHERE iso='$selected_country' and state_id=$selected_state order by district_name";
				$query = $this->db->query($sql_str);
				if($query){
					$res = $query->result();
					
					foreach($res as $state){
						//$vals .= '<option value="'.$state->district_id.'">'.$state->district_name.'</option>';
						$vals .= '<option value="'.$state->district_id.'"'.(($selected_district!='')?($selected_district == $state->district_id)?' SELECTED="selected"':'':'').'>'.$state->district_name.'</option>';
					}
					
				}
			}
			echo $vals;
		}
		public function getSelectedCity(){
			$selected_country = $_POST['cnt'];
			$selected_state = $_POST['dflt_st'];
			$selected_district = $_POST['dflt_distr'];
			$selected_city = isset($_POST['dflt_city'])?$_POST['dflt_city']:'';
                        $reseller_id = isset($_POST['reseller_id'])?$_POST['reseller_id']:0;
                        $join='';
                        if($reseller_id >0){
                         $join="INNER JOIN eb_location_lco_mapping as lcm ON lcm.location_id=l.location_id and lcm.employee_id=$reseller_id";   
                        }
            $vals = '<option value="-1">Select City</option>';
			if($selected_country!=''){
                                $sql_str = "SELECT l.* FROM eb_location_locations as l $join
                                WHERE l.state_id='$selected_state' AND l.dealer_id=".$_SESSION['user_data']->dealer_id."  order by l.location_name";        
				//$sql_str = "SELECT * FROM eb_location_locations WHERE state_id='$selected_state' AND dealer_id=".$_SESSION['user_data']->dealer_id."  order by location_name";
				$query = $this->db->query($sql_str);
				//echo $this->db->last_query();
				if($query){
					$res = $query->result();
					
					foreach($res as $city){
						$vals .= '<option value="'.$city->location_id	.'"'.(($selected_city!='')?($selected_city == $city->location_id)?' SELECTED="selected"':'':'').'>'.$city->location_name.'</option>';
					}
					
				}
			}
			echo $vals;
		}
		public function getStatesOfCountry(){
			$selected_country = $_POST['cnt'];
			$default_state = (isset($_POST['ds']))?$_POST['ds']:0;
			$sql_str = "SELECT * FROM eb_location_states WHERE country_code='$selected_country' order by name";
			$query = $this->db->query($sql_str);
			if($query){
				$res = $query->result();
				$vals = '<option value="-1">Select State</option>';
				foreach($res as $state){
					$vals .= '<option value="'.$state->id.'"'.($default_state==$state->id?' selected="SELECTED"':'').'>'.$state->name.'</option>';
				}
				echo $vals;
			}
		}
		public function getLocationsOfSatate(){
			$selected_state = $_POST['st'];
			$c = (isset($_POST['city']))?$_POST['city']:0;
                        $reseller_id = isset($_POST['reseller_id'])?$_POST['reseller_id']:0;
			$vals = '<option value="-1">Select City</option>';
			$join='';
                        if($reseller_id >0){
                         $join="INNER JOIN eb_location_lco_mapping as lcm ON lcm.location_id=l.location_id and lcm.employee_id=$reseller_id";   
                        }
			if($selected_state!=''){
                           $sql_str = "SELECT l.* FROM eb_location_locations as l $join
                           WHERE l.state_id='$selected_state' AND l.dealer_id=".$_SESSION['user_data']->dealer_id."  order by l.location_name";             
			  //$sql_str = "SELECT * FROM eb_location_locations WHERE state_id='$selected_state' AND dealer_id=".$_SESSION['user_data']->dealer_id."  order by location_name";

			  $query = $this->db->query($sql_str);
			  if($query){
				  $res = $query->result();
				  
				  foreach($res as $city){
					  $vals .= '<option value="'.$city->location_id.'"'.(($c)?$c==$city->location_id?' selected="SELECTED"':'':'').'>'.$city->location_name.'</option>';
					 
				  }
				 
			  }
			}
			 echo $vals;
		}
	
		
		/*public function getSelectedStateLocation(){
			$selected_state = $_POST['st'];
			$sql_str = "SELECT * FROM eb_location_locations WHERE state_id='$selected_state' AND dealer_id=".$_SESSION['user_data']->dealer_id;
			$query = $this->db->query($sql_str);
			if($query){
				$res = $query->result();
				$vals = '<option value="-1">Select Location</option>';
				foreach($res as $city){
					$vals .= '<option value="'.$city->location_id.'">'.$city->location_name.'</option>';
				}
				echo $vals;
			}
		}*/
		//methods for search customer page.. start
			public function getSearchCountries()
			{
				$selVal = '';
				if(isset($_POST['selVal'])){
					$selVal = $_POST['selVal'];
				}
				$sql_str = "SELECT iso,name FROM eb_location_countries";
				$query = $this->db->query($sql_str);
				if($query)
				{
					$res = $query->result();
					$vals = '<option value="-1">Select Country</option>';
					foreach($res as $country)
					{
						$vals .= '<option value="'.$country->iso.'"'.($selVal!=''?$selVal==$country->iso?' selected="SELECTED"':'':'').'>'.$country->name.'</option>';
					}
					echo $vals;
				}
			}
			public function getSearchCountryStates()
			{
				$country_code = $_POST['country'];

				$selVal = isset($_POST['selVal'])?$_POST['selVal']:'-1';
				$sql_str = "SELECT id,name FROM eb_location_states WHERE country_code='".$country_code."'";
				$query = $this->db->query($sql_str);
				if($query)
				{
					$res = $query->result();
					$vals = '<option value="-1"'.(($selVal==-1)?' selected="SELECTED"':'').'>Select State</option>';
					foreach($res as $state)
					{
						$vals .= '<option value="'.$state->id.'"'.(($selVal==$state->id)?' selected="SELECTED"':'').'>'.$state->name.'</option>';
					}
					echo $vals;
				}
			}
			public function getCountryStateCities()
			{
				$state = $_POST['state'];
				$selVal = isset($_POST['selVal'])?$_POST['selVal']:'-1';
				$sql_str = "SELECT location_id,location_name FROM eb_location_locations WHERE state_id=$state AND dealer_id=".$_SESSION['user_data']->dealer_id;
				$query = $this->db->query($sql_str);
				if($query)
				{
					$res = $query->result();
					$vals = '<option value="-1" '.(($selVal==-1)?' selected="SELECTED"':'').'>Select City</option>';
					foreach($res as $city)
					{
						$vals .= '<option value="'.$city->location_id.'"'.(($selVal==$city->location_id)?' selected="SELECTED"':'').'>'.$city->location_name.'</option>';
					}
					echo $vals;
				}
			}
		//methods for search customer page.. end
		
		public function getCustomerDetails(){
		if(isset($_POST['cid'])){
			$ord = new ordermodel();
			$vals = $ord->fetch_customer_details($_POST['cid']);
			if($vals){
				$customer_id=($vals->customer_id=='')?' ':$vals->customer_id;
				$business_name=($vals->business_name=='')?' ':$vals->business_name;
				$f=($vals->first_name=='')?' ':$vals->first_name;
				$l=($vals->last_name=='')?' ':$vals->last_name;
				$a1=($vals->address1=='')?' ':$vals->address1;
				$a2=($vals->address2=='')?' ':$vals->address2;
				$a3=($vals->address3=='')?' ':$vals->address3;
				$c=($vals->country=='')?' ':$vals->country;
				$s=($vals->state=='')?' ':$vals->state;
				$ct=($vals->city=='')?' ':$vals->city;
				$p=($vals->pin_code=='')?' ':$vals->pin_code;
				$ph=($vals->phone_no=='')?' ':$vals->phone_no;
				$m=($vals->mobile_no=='')?' ':$vals->mobile_no;
				$e=($vals->email=='')?' ':$vals->email;
				
				$is_reseller=$vals->is_reseller;
				$reseller_id=$vals->reseller_id;
				$project_id=$vals->reseller_project_id;
			$xml = '
					<customer>
					<customer_id>'.$vals->customer_id.'</customer_id>
					<buisiness_name>'.$vals->business_name.'</buisiness_name>
					<first_name>'.$f.'</first_name>

					<last_name>'.$l.'</last_name>
					<address1>'.$a1.'</address1>
					<address2>'.$a2.'</address2>
					<address3>'.$a3.'</address3>
					<country>'.$c.'</country>
					<state>'.$s.'</state>
					<city>'.$ct.'</city>
					<pin_code>'.$p.'</pin_code>
					<phone_no>'.$ph.'</phone_no>
					<mobile_no>'.$m.'</mobile_no>
					<email>'.$e.'</email>
					
					<is_reseller>'.$is_reseller.'</is_reseller>
					<reseller_id>'.$reseller_id.'</reseller_id>
					<project_id>'.$project_id.'</project_id>
					</customer>';
			echo $xml;
			}else{
				echo '-1';
			}
		}else{
			echo '-1';
		}
	}
	public function getSalesCustomerDetails(){
		if(isset($_POST['cid'])){
			$ord = new ordermodel();
			$vals = $ord->fetch_sales_details($_POST['cid']);
			if($vals){
				$f=($vals->first_name=='')?' ':$vals->first_name;
				$l=($vals->last_name=='')?' ':$vals->last_name;
				$b=($vals->business_name=='')?' ':$vals->business_name;
				$a=($vals->address=='')?' ':$vals->address;
				$c=($vals->country=='')?' ':$vals->country;
				$s=($vals->state=='')?' ':$vals->state;
				$ct=($vals->city=='')?' ':$vals->city;
				$p=($vals->postal_code=='')?' ':$vals->postal_code;
				$ph=($vals->phone_no=='')?' ':$vals->phone_no;
				$m=($vals->mobile_no=='')?' ':$vals->mobile_no;
				$e=($vals->email=='')?' ':$vals->email;
				$xml = '
						<customer>
						<lead_id>'.$vals->lead_id.'</lead_id>
						<first_name>'.$f.'</first_name>
						<last_name>'.$l.'</last_name>
						<business_name>'.$b.'</business_name>
						<address>'.$a.'</address>
						<country>'.$c.'</country>
						<state>'.$s.'</state>
						<city>'.$ct.'</city>
						<postal_code>'.$p.'</postal_code>
						<phone_no>'.$ph.'</phone_no>
						<mobile_no>'.$m.'</mobile_no>
						<email>'.$e.'</email>
						</customer>';
				echo $xml;
			}else{
				echo '-1';
			}
		}else{
			echo '-1';
		}
	}
        public function getProducts(){
               $cond="";
               $empid="";
                if($_SESSION['user_data']->users_type=='DEALER' || $_SESSION['user_data']->users_type=='ADMIN' ){
				   if($_POST['eid'] !=0){
				   $eid=$_POST['eid'];
				   
				  // $sip=isset($_POST['sip'])?$_POST['sip']:-1;
				  $empid= "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = p.product_id AND pm.employee_id=$eid";
				}
			}
               if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER'){
                   if($_SESSION['user_data']->employee_parent_type=='RESELLER'){
                       $reseller_employee_id = $_SESSION['user_data']->employee_parent_id;
                   }else{
                        $reseller_employee_id = $_SESSION['user_data']->employee_id;
                   }
                        $cond =  "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = p.product_id AND pm.employee_id=$reseller_employee_id";
                }
                //$sql_str = "SELECT product_id,pname,sku,pricing_structure_type FROM eb_products WHERE dealer_id=".$_SESSION['user_data']->dealer_id." order by sku, pname";
               $sql_str = "SELECT p.product_id,p.pname,p.sku,p.pricing_structure_type FROM eb_products p $cond $empid WHERE p.status=1 AND p.dealer_id=".$_SESSION['user_data']->dealer_id." order by sku, pname";
                $query = $this->db->query($sql_str);
                //echo $this->db->last_query();die();
                $options = '<option value="-1" style="width:160px;position:fixed;">-- Select a Product --</option>';
                if($query){
                        foreach($query->result() as $row){
						if(isset($_SESSION['modules']['CAS']) && $_SESSION['modules']['CAS']){
							$options .= '<option value="'.$row->product_id.'">'.$row->sku.' &raquo; '.$row->pname.'</option>';
						}else{
							if($row->pricing_structure_type !=3){
                                $options .= '<option value="'.$row->product_id.'">'.$row->sku.' &raquo; '.$row->pname.'</option>';
							}
                        }
					}
                }

        echo $options;
	}
	public function getRetailerProducts(){
		$s = $this->uri->segment(3);
		$cid = $this->uri->segment(4);
		$did = $_SESSION['user_data']->dealer_id;
		if($s==2) {   
		$sel_qry = "select is_reseller, reseller_id from customer where customer_id='$cid' and dealer_id=$did";
	   
			$query = $this->db->query($sel_qry);
		
			$res = $query->row();
			if($res->is_reseller=='1' && $res->reseller_id!='0'){
				$rid=$res->reseller_id;
				
				/*$sql_str="select p.product_id ,p.pname ,p.sku,p.pricing_structure_type from eb_products p 
						  INNER JOIN eb_product_group pg ON pg.product_id=p.product_id
						  INNER JOIN customer_group cg ON cg.group_id=pg.group_id and cg.customer_id='$cid'
						  INNER JOIN eb_reseller_product_mapping rp ON rp.product_id=p.product_id and rp.employee_id='$rid'
						  where p.dealer_id=".$_SESSION['user_data']->dealer_id;*/
				$sql_str="select p.product_id ,p.pname ,p.sku,p.pricing_structure_type from eb_products p 
						  INNER JOIN eb_reseller_product_mapping rp ON rp.product_id=p.product_id and rp.employee_id='$rid'
						  where p.status=1 AND p.dealer_id=".$_SESSION['user_data']->dealer_id;
				
			} else {
				/*$sql_str = "SELECT p.product_id ,p.pname ,p.sku,p.pricing_structure_type FROM eb_products p 
							INNER JOIN eb_product_group pg ON p.product_id=pg.product_id
							INNER JOIN customer_group cg ON pg.group_id=cg.group_id
							INNER JOIN customer c ON c.customer_id=cg.customer_id
							WHERE p.dealer_id='".$_SESSION['user_data']->dealer_id."' and c.customer_id='$cid'";*/
				$sql_str = "SELECT p.product_id ,p.pname ,p.sku,p.pricing_structure_type FROM eb_products p 
							INNER JOIN eb_product_group pg ON p.product_id=pg.product_id
							INNER JOIN customer_group cg ON pg.group_id=cg.group_id
							INNER JOIN customer c ON c.customer_id=cg.customer_id
							WHERE p.status=1 AND p.dealer_id='".$_SESSION['user_data']->dealer_id."' and c.customer_id='$cid'";
				
			}
		  $query = $this->db->query($sql_str);
			 //echo $this->db->last_query();
				$options = '<option value="-1">-- Select a Product --</option>';
				if($query){
					foreach($query->result() as $row){
						if(isset($_SESSION['modules']['CAS']) && $_SESSION['modules']['CAS']){
							$options .= '<option value="'.$row->product_id.'">'.$row->sku.' &raquo; '.$row->pname.'</option>';
						}else{
							if($row->pricing_structure_type !=3){
								$options .= '<option value="'.$row->product_id.'">'.$row->sku.' &raquo; '.$row->pname.'</option>';
							}
						}
					}
				}
				echo $options;
			}
			else
			echo '';
	}
	
        
        public function getOrders(){
			$sql_str = "SELECT order_id,order_date,order_number FROM eb_orders WHERE dealer_id=".$_SESSION['user_data']->dealer_id;
			$query = $this->db->query($sql_str);
			$options = '<option value="0">-- Select Order --</option>';
			if($query){
				foreach($query->result() as $row){
					$options .= '<option value="'.$row->order_id.'">['.$row->order_date.']'.($row->order_number?' - ('.$row->order_number.')':'').' &raquo; '.$row->order_id.'</option>';
				}
			}	
			echo $options;
		}
		public function getInventories(){
                    
            $sql_str= "select s.item_id,s.price, si.item_name,si.is_individual from eb_stock s INNER JOIN eb_stock_items si ON s.item_id=si.item_id where s.dealer_id='".$_SESSION['user_data']->dealer_id."' group by s.item_id";
                    
			//$sql_str = "SELECT inv_id,inv_name,priceperunit FROM inventory WHERE dealer_id=".$_SESSION['user_data']->dealer_id." AND status<>0";
			$query = $this->db->query($sql_str);
			$options = '<option value="0">-- Select Inventory --</option>';
			if($query){
				foreach($query->result() as $row){
					
                     $options .= '<option value="'.$row->item_id.'" price="'.$row->price.'" is_indv="'.$row->is_individual.'">'.$row->item_name.'</option>';

				}
			}	
			echo $options;
                    
		}
                public function getAmountOfItem(){
                    
                    $dealer_id=  $this->input->post('dealer_id');
                    $item_id=  $this->input->post('item');
                    $did = $_SESSION['user_data']->dealer_id;
                    $req_qty = is_numeric($this->input->post('qty'))?$this->input->post('qty'):0;
                    
                    
                    
                    if($req_qty>0){
			$sql = "SELECT s.item_id,s.price, s.serial_number, SUM( s.item_qty ) - SUM( COALESCE( o.units, 0 ) ) remaining_stock
                                FROM eb_stock s
                                LEFT JOIN eb_stock_outward_log o ON s.item_id = o.item_id
                                AND s.serial_number = o.serial_number
                                WHERE s.item_id ='$item_id' and s.dealer_id=$did
                                GROUP BY s.item_id, s.serial_number, o.item_id, o.serial_number order by s.created_date";
			$res = $this->db->query($sql);
                        $fetch = $res->result();
                        $item_arr = array();
                        
                        foreach($fetch as $row){
                            $item_arr[] = array('qty'=>$row->remaining_stock,'price'=>$row->price);
                    
                        }
               		
			
			
			$n=0;
			$num_rec=count($item_arr);
			$last_price = 0;
			$curr_req = $req_qty;
			$total_price = 0;
			
			
			
			do{
				
				if($n >= $num_rec){
					$total_price += $curr_req * $last_price;			
					
					$curr_req = 0;			
				}else{
					if($curr_req<$item_arr[$n]['qty']){
						$total_price += $curr_req * $item_arr[$n]['price'] ;
						
					}else{
						$total_price += $item_arr[$n]['qty'] * $item_arr[$n]['price'];
						
					}		
					$last_price = $item_arr[$n]['price'];
					$curr_req = $curr_req - $item_arr[$n]['qty'];
				}
				
				
				$n++;		
			}while($curr_req > 0);
			
			
			echo $total_price;
                    }
                    
                }
		public function getBillOfMaterials(){
			$dealer_id=$_POST['di'];
			$order_id=$_POST['oi'];
			$sql_str = "SELECT oi.*,si.item_name FROM eb_order_indent oi 
			INNER JOIN  eb_stock_items si ON oi.inventory_id = si.item_id
			WHERE oi.dealer_id=$dealer_id AND order_id=$order_id";
			$query = $this->db->query($sql_str);
			$options = '';
			if($query){
				$total = 0;
				foreach($query->result() as $row){
					$options .= '<tr class="dyn_row">
					<td>'.$row->item_name.'
					<input type="hidden" name="particulars[]" value="'.$row->inventory_id.'"/>
					</td>
					<td align="center">'.$row->quantity.'
					<input type="hidden" name="quantities[]" value="'.$row->quantity.'"/>
					</td>
					<td align="right">'.$row->price.'
					<input type="hidden" name="prices[]" value="'.$row->price.'"/>
					</td>
					<td align="right">'.$row->total_amount.'
					<input type="hidden" name="amounts[]" value="'.$row->total_amount.'" class="amt"/>
					</td>
					<td></td></tr>';
					$total = $total + $row->total_amount;
				}
			}	
			echo $options.'><><><'.$total;
		}
		public function getBaseStations(){
			$sql = "SELECT base_station_id,base_station_name FROM eb_base_stations WHERE dealer_id=".$_SESSION['user_data']->dealer_id;
			$query = $this->db->query($sql);
			if($query){
				$result = '<option value="-1">Select a Basestation</option>';
				foreach($query->result() as $row){
						$result .= '<option value="'.$row->base_station_id.'">'.$row->base_station_name.'</option>';
				}
				echo $result;
			}else{
				echo '<option value="-1">Select a Basestation</option>';
			}
		}
		public function exportToExcel(){
			echo $sql = $_SESSION['srch_qry'];
			//header("Content-type: application/ms-excel");
			//header("Content-Disposition: attachment; filename=customers.xls");			
			$result = mysql_query($sql) or die(mysql_error());
			// Print the column names as the headers of a table
			echo '<table border="1"><tr style="background:#000;color:#fff; font-size:13px;font-weight:bold">';
			echo '<td align="center">Sr#</td>';
			for($i = 0; $i < mysql_num_fields($result); $i++) {
				$field_info = mysql_fetch_field($result, $i);
				echo "<td align=\"center\">{$field_info->name}</td>";
			}
			echo '</tr>';
			// Print the data
			$i=0;
			$cnt=0;
			while($row = mysql_fetch_row($result)) {
				if($i==1) $i=0;
				else $i++;
				echo "<tr".(($i)?' class="alt"':'').">";
				echo "<td>".(++$cnt)."</td>";
				foreach($row as $_column) {
					echo "<td>{$_column}</td>";
				}
				echo "</tr>";
			}

			echo "</table>";
			
		}
		public function getdepartmentemp(){
			if(isset($_POST['deptid'])){
				$empid=$_POST['selemp'];
                                $empidarr= explode(",", $empid);
				$ord = new ordermodel();
				$vals = $ord->getempdeptstatus($_POST['deptid']);
                                $options = "";
				if($vals){
					foreach($vals as $emp){	
                                            $options .= '<option value="'.$emp->employee_id.'"'.(in_array($emp->employee_id, $empidarr)?'selected="SELECTED"':'').'>'.$emp->first_name.' '.$emp->last_name.'</option>';
                                            //$vals .= '<option value="'.$emp->employee_id.'"'.(($empid==$emp->employee_id)?' selected="SELECTED"':'').'>'.$emp->first_name.' '.$emp->last_name.'</option>';
					}
				} 
				echo $options;
			}
		}
		public function changeStatus(){
			if(isset($_POST['saveAuth']) && $_POST['saveAuth']=='1'){
				$curStatus = $_POST['curStatus'];
				$curEmp = $_POST['curEmp'];
				$curDept = $_POST['curDept'];
				$icid = $_POST['icid'];
				$remarks = $_POST['remarks'];
				
				$sel_tck="select internal_tck from eb_internal_complaints where internal_complaint_id='$icid'";
				$result= mysql_query($sel_tck);
				$fetch = mysql_fetch_array($result);
				$intln_tck=$fetch['internal_tck'];
				//$dept_id=$fetch['department_id'];
				
				$sel_dept="select department_name from eb_departments where department_id='$curDept'";
				$result_dept= mysql_query($sel_dept);
				$fetch_dept = mysql_fetch_array($result_dept);
				$dept_name=$fetch_dept['department_name'];
				
				$sel_emp="select first_name, last_name from employee where employee_id='$curEmp'";
				$result_emp= mysql_query($sel_emp);
				$fetch_emp = mysql_fetch_array($result_emp);
				$emp_name=$fetch_emp['first_name']." ".$fetch_emp['last_name'];
				
				$auth_statuses = array(-1=>'New',1=>'In Progress',2=>'Closed',3=>'Department Change',4=>'Issue Not Resolved',5=>'Task Completed by Employee');
				switch($curStatus)
				{
					case -1: break;
					case  1: 
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', '$remarks', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$auth_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." assigned to ".$emp_name;
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							$msg = "Employee Changed";
							
							break;
					case  2:
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', 'CLOSED', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$auth_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." CLOSED";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$msg = "$intln_tck complaint closed";
							
							break;
					case  3:
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '0', 'Complaint transfered to $dept_name department', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus',department_id='$curDept' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$auth_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Transfered to ".$dept_name." Department";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$sql_qry4 = "insert into eb_internal_complaint_department (icomplaint_id, department_id) values ('$icid','$curDept')";
							mysql_query($sql_qry4);
							
							$msg = "$intln_tck complaint transfered to $dept_name department";
							
							break;
					case  4:
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', '$remarks', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$auth_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." not resolved by current employee. So complaint assigned to ".$emp_name." in the same department";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$msg = "$intln_tck complaint reassigned to another employee $emp_name";
							


							break;
					case  5:
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', '$remarks', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$auth_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." Current Employee resolved assigned issue. Now it is assigned to $emp_name";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$msg = "$intln_tck complaint reassigned to another employee $emp_name";
							
							break;
				}
				
				
			}
			
			if(isset($_POST['saveEmp']) && $_POST['saveEmp']=='1'){
				$curStatus = $_POST['curStatus'];
				$curEmp = $_SESSION['user_data']->employee_id;
				$curDept = $_POST['curDept'];
				$icid = $_POST['icid'];
				$remarks = $_POST['remarks'];
				
				$sel_tck="select internal_tck from eb_internal_complaints where internal_complaint_id='$icid'";
				$result= mysql_query($sel_tck);
				$fetch = mysql_fetch_array($result);
				$intln_tck=$fetch['internal_tck'];
				//$dept_id=$fetch['department_id'];
				
				$sel_dept="select department_name from eb_departments where department_id='$curDept'";
				$result_dept= mysql_query($sel_dept);
				$fetch_dept = mysql_fetch_array($result_dept);
				$dept_name=$fetch_dept['department_name'];
				
				$sel_emp="select first_name, last_name from employee where employee_id='$curEmp'";
				$result_emp= mysql_query($sel_emp);
				$fetch_emp = mysql_fetch_array($result_emp);
				$emp_name=$fetch_emp['first_name']." ".$fetch_emp['last_name'];
				
				$emp_statuses = array(4=>'Issue Not Resolved',5=>'Task Completed');
				switch($curStatus)
				{
					case 4: 
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', '$remarks', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$emp_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." not resolved by me($emp_name).";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$msg = "Status Changed";
							break;
					case  5:
							$sql_qry1 = "insert into eb_internal_complaint_history (icomplaint_id, department_id, employee_id, remarks, status) values ('$icid', '$curDept', '$curEmp', '$remarks', '$curStatus')";
							mysql_query($sql_qry1);
							
							$sql_qry2 = "update eb_internal_complaints set status = '$curStatus' where internal_complaint_id='$icid'";
							mysql_query($sql_qry2);
							
							$log =$emp_statuses[$curStatus]." Int.Complaint Ticket#: ".$intln_tck." | Dept.: ".$dept_name." resolved by me($emp_name)";
							$sql_qry3 = "insert into eb_internal_complaint_log (icomplaint_id, log_text) values ('$icid','$log')";
							mysql_query($sql_qry3);
							
							$msg = "Status Changed";
							
							break;
				}
			}
			echo $msg;
			
		}
                public function checkBoxNumber()
                {
                    $boxNumber = $_POST['boxNumber'];
                    $sql_str = "SELECT count(box_number) countdeviceid FROM customer_device WHERE box_number='$boxNumber' AND dealer_id=".$_SESSION['user_data']->dealer_id;
                    $query = $this->db->query($sql_str);
                    echo $countDevices = $query->row()->countdeviceid;
                }
		public function getSupplierdetails(){
			$sid=$_POST['supplierid'];
			if($sid){
				$sql="SELECT * FROM eb_suppliers WHERE dealer_id=".$_SESSION['user_data']->dealer_id;
			}
		}
                public function cus_doc_types(){
                    $did=$this->input->post('dealer_id');
                    
                  // $query = $this->db->query("SELECT * FROM eb_customer_doc_types WHERE  dealer_id = ".$did." order by full_name");
				    $query = $this->db->query("SELECT * FROM eb_id_types order by type");
                    $res = $query->result();
                    $options = "<option value='-1'>Select Document Type</option>";
                    foreach($res as $row){
                       // $options .= "<option value='".$row->abbr."'>".$row->full_name."(".$row->abbr.")</option>";
					    $options .= "<option value='".$row->id_type_id."'>".$row->type."</option>";
                    }
					$options .= "<option value='CAF'>CUSTOMER APPLICATION FORM</option>
					             <option value='CUSTOMER_IMAGE'>CUSTOMER IMAGE</option>
								 <option value='CUSTOMER_SIGN'>CUSTOMER SIGNATURE</option>";
                    echo $options;
               }
	
		 public function employee_doc_types() {
        $did = $this->input->post('dealer_id');
        // $query = $this->db->query("SELECT * FROM eb_customer_doc_types WHERE  dealer_id = ".$did." order by full_name");
        $query = $this->db->query("SELECT * FROM eb_id_types order by type");
        $res = $query->result();
        $options = "<option value='-1'>Select Document Type</option>";
        foreach ($res as $row) {
            // $options .= "<option value='".$row->abbr."'>".$row->full_name."(".$row->abbr.")</option>";
            $options .= "<option value='" . $row->id_type_id . "'>" . $row->type . "</option>";
        }
       
        echo $options;
    }

	 	public function get_issuing_items(){
			
			$loc	=	$this->input->post('stockloc'); 
			$item	=	$this->input->post('item'); 
			$units	=	$this->input->post('unit'); 
 		        $did	=	$_SESSION['user_data']->dealer_id;
			$sql	=	"SELECT * FROM  eb_stock WHERE  `item_id`='$item' and stock_location='$loc' and in_stock='1' and status!='3' and dealer_id='$did' order by stock_id"; 
					$query 	=	$this->db->query($sql);
                        $sq	=	"SELECT SUM( item_qty ) total_qty FROM eb_stock WHERE  `item_id` =  '$item' AND stock_location =  '$loc' AND in_stock =  '1' and status!='3' AND dealer_id='$did' ORDER BY stock_id"; 
			$query1 =	$this->db->query($sq);
                         
			$items 	=  	"";
			if($query){
				$count	=	$query1->row()->total_qty;
				if($count>=$units){
					foreach($query->result() as $row){

                                        $serial=$row->serial_number;
                                          /*if($serial)
                                          {            
                                                 $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial' and dealer_id=$did");
                                                 $rs = $sql1->row()->mac_address;
                                                 $mac=($rs)?'<br>( '.$rs.' )':'';             
                                          }*/
                          
                                          $items .= "<div class='item-list'><input type='checkbox' name='serial[]' class='serial' value='".$serial."'/>".$serial."</div>";	
                                         }
					echo $items;
				}else{
					echo '<div class="error">Required Quantity('.$units.') is more than available Quantity('.$count.')</div>';
				}
			}else{
				echo '<div class="error">There is no item for the given criteria...</div>';
			}
			
		}
           public function get_status_items(){
			
			$loc	=	$this->input->post('stockloc'); 
			$item	=	$this->input->post('item'); 
			$units	=	$this->input->post('unit'); 
  			$status =  	($this->input->post('status'))?$this->input->post('status'):'0';
                        $did	=	$_SESSION['user_data']->dealer_id;
                         if($status=="1")
 			   {   
					$sql	=	"SELECT * FROM  eb_stock WHERE  `item_id`='$item' and stock_location='$loc' and in_stock='1' and status='1' and dealer_id='$did' order by stock_id"; 
					$query 	=	$this->db->query($sql);
                           }
                         else if($status=="2")
                           {   
					$sql	=	"SELECT * FROM  eb_stock WHERE  `item_id`='$item' and stock_location='$loc' and in_stock='1' and status='2' and dealer_id='$did' order by stock_id"; 
					$query 	=	$this->db->query($sql);
                           }
                           else 
                           {   
					$sql	=	"SELECT * FROM  eb_stock WHERE  `item_id`='$item' and stock_location='$loc' and in_stock='1' and status!='3' and dealer_id='$did' order by stock_id"; 
					$query 	=	$this->db->query($sql);
                           }   


                        $sq	=	"SELECT SUM( item_qty ) FROM eb_stock WHERE  `item_id` =  '$item' AND stock_location =  '$loc' AND in_stock =  '1' and status!='3' AND dealer_id='$did' ORDER BY stock_id"; 
			$query1 	=	$this->db->query($sq);
                         
			$items 	=  	"";
			if($query){
				$count	=	$query1->result();
				if($count>=$units){
					foreach($query->result() as $row){

                                            $serial=$row->serial_number;
                                          /*if($serial)
                                          {            
                                                 $sql1=$this->db->query("select mac_address from eb_stock where serial_number='$serial'");
                                                 $rs = $sql1->row()->mac_address;
                                                 $mac=($rs)?'<br>( '.$rs.' )':'';             
                                          }*/
                          
			$items .= "<div class='item-list'><input type='checkbox' name='serial[]' class='serial' value='".$serial."'/>".$serial."</div>";	
	


                                                 }
					echo $items;
				}else{
					echo '<div class="error">Required Quantity('.$units.') is more than available Quantity('.$count.')</div>';
				}
			}else{
				echo '<div class="error">There is no item for the given criteria...</div>';
			}
			
		}

	public function getCategorizedChannels()
	{
		$categoryId = $this->input->post('categoryId');
		$channelId = ($this->input->post('channelId'))?$this->input->post('channelId'):-1;
		$sql_str = "SELECT channel_id,channel_name FROM eb_channels WHERE category_id=$categoryId AND dealer_id=".$_SESSION['user_data']->dealer_id;
		$query = $this->db->query($sql_str);
		$vals = '<option value="-1">Select</option>';
		if($query->num_rows()>0)
		{
			$res = $query->result();
			foreach($res as $result)
			{
				$vals .= '<option value="'.$result->channel_id.'"'.(($channelId==$result->channel_id)?' selected="SELECTED"':'').'>'.$result->channel_name.'</option>';
			}
		}
		echo $vals;
	}
	public function getCustomerBoxDeails()
	{
		$stockId = $this->input->post('stockId');
		$customerId = $this->input->post('customerId');
		$sql_str = "SELECT es.stock_id, es.serial_number,es.mac_address,es.make,es.model_number,es.vc_number,es.box_number 
						FROM eb_stock es
						INNER JOIN eb_stock_items esi ON es.item_id = esi.item_id
						AND esi.is_setup_box =1
						INNER JOIN eb_stock_inventory_movement esim ON es.item_id = esim.item_id
						AND es.serial_number = esim.inward_serial_number
						WHERE es.in_stock =0
						AND esim.customer_id =$customerId
						AND es.stock_id=$stockId
						AND es.dealer_id =".$_SESSION['user_data']->dealer_id; 
               
		$query = $this->db->query($sql_str);
		$vals = '';
		if($query && $query->num_rows()>0)
		{
			$res = $query->row();
			$vals .= '    
                        <box_details>
                                <serial_number>'.$res->serial_number.'</serial_number>
                                <mac_address>'.$res->mac_address.'</mac_address>
                                <make>'.$res->make.'</make>
                                <model_number>'.$res->model_number.'</model_number>  
                                <box_number>'.$res->box_number.'</box_number>  
                                <vc_number>'.$res->vc_number.'</vc_number>  
                        </box_details>
                     ';
					 echo $vals;
		}
		else
			echo '-1';
		
	}
        public function is_individual_item(){
             $item_id = $_POST['item_id'];
             $did = $_SESSION['user_data']->dealer_id;
             $sql=$this->db->query("SELECT is_individual,is_setup_box FROM  `eb_stock_items` WHERE  `item_id`='$item_id' and dealer_id=$did"); 
             $is_individual=$sql->row()->is_individual;
             $is_setup_box=$sql->row()->is_setup_box;
             echo $is_individual.'_'.$is_setup_box;
             
        }
        public function get_reseller_groups(){
             $reseller_id =$this->input->post('reseller_id');
             if($reseller_id!=0)
                 $employee_id=$reseller_id;
             else
                 $employee_id=$_SESSION['user_data']->employee_id;
             $dealer_id = $_SESSION['user_data']->dealer_id;
             
             $sql=$this->db->query("SELECT g.group_name, g.group_id FROM groups g 
                     INNER JOIN employee_group eg ON eg.group_id=g.group_id 
                     WHERE  eg.employee_id='$employee_id' and eg.dealer_id='$dealer_id'"); 
             $options ='';
             if($sql){
                 $res = $sql->result();
                 if(count($res)>0){
                 $options .= "<option value=\"-1\">All</option>";
                    foreach($res as $row){
                        $options .= "<option value='".$row->group_id."'>".$row->group_name."</option>";
                    }
                 }
                    
             }
             echo $options;
       }
        public function get_reseller_groups_for_reassign(){
             $reseller_id =$this->input->post('reseller_id');
             if($reseller_id!=0)
                 $employee_id=$reseller_id;
             else
                 $employee_id=$_SESSION['user_data']->employee_id;
             $dealer_id = $_SESSION['user_data']->dealer_id;
             
             $sql=$this->db->query("SELECT g.group_name, g.group_id FROM groups g 
                     INNER JOIN employee_group eg ON eg.group_id=g.group_id 
                     WHERE  eg.employee_id='$employee_id' and eg.dealer_id='$dealer_id'"); 
             $options =array();
             if($sql){
                 $res = $sql->result();
                 if(count($res)>0){                 
                    foreach($res as $row){
                        $options[]= $row->group_id;
                    }
                 }
                    
             }
             //print_r($options)	;
             echo $options[0];
       }
       // checking duplicate caf number <Anurag>
		public function check_caf_number(){
			$caf='';
			$caf= trim(strtolower($_POST['caf']));
			
			$sel_qry = "select count(caf_no) as c from customer where caf_no='$caf' AND dealer_id=".$_SESSION['user_data']->dealer_id;;
			$query=$this->db->query($sel_qry);
			//echo $this->db->last_query(); die();
			if($query){
			   $c = $query->row()->c;
			   if($c>0){
				   echo 1;
			   }else {
				   echo 0;
				}			   
			}
		}	
       public function getBroadCasters(){
           $dealer_id = $_SESSION['user_data']->dealer_id;
           $sql=$this->db->query("SELECT b.broadcaster_id, b.broadcaster_name FROM eb_broadcasters b 
                                  WHERE b.dealer_id='$dealer_id' and b.broadcaster_id NOT IN (select broadcaster_id from eb_employee_broadcaster)"); 
             $options ='';
             if($sql){
                 $res = $sql->result();
                 if(count($res)>0){
                    $options .= "<option value=\"-1\">Select</option>";
                    foreach($res as $row){
                        $options .= "<option value='".$row->broadcaster_id."'>".$row->broadcaster_name."</option>";
                    }
                 }
                    
             }
             echo $options;
       }
	   public function getBroadCasterDetails(){
		   $bid = $_POST['bid'];
		   $dealer_id = $_SESSION['user_data']->dealer_id;
		   $sql=$this->db->query("SELECT * FROM eb_broadcasters  
								  WHERE dealer_id='$dealer_id' and broadcaster_id=$bid"); 
			 $options ='';
			 if($sql){
				 $res = $sql->row();
				 if(count($res)>0){
					
					$options.='broadcaster_name:'.$res->broadcaster_name.',';
					$options.='broadcaster_phone:'.$res->broadcaster_phone.',';
					$options.='contact_name:'.$res->contact_name.',';
					$options.='contact_phone:'.$res->contact_phone.',';
					$options.='email:'.$res->email.',';
					$options.='city:'.$res->city.',';
					$options.='state:'.$res->state.',';
					$options.='country:'.$res->country.',';
				   
					$options.='address1:'.$res->address1.',';
					$options.='address2:'.$res->address2.',';
					$options.='address3:'.$res->address3.',';
					$options.='pincode:'.$res->pincode.',';
					
				 }
					
			 }
			 echo rtrim($options,',');
	}
	public function getpackage_channel(){
		$product = $_POST['package'];
		if($product!='No Channels'){
			$dealer_id = $_SESSION['user_data']->dealer_id;
			$sql=$this->db->query("SELECT c.channel_name,p.sku,p.pname, p.cas_product_id FROM eb_products p
									INNER JOIN eb_product_channels pc ON pc.product_id=p.product_id
									INNER JOIN eb_channels c ON c.channel_id=pc.channel_id
									WHERE (p.sku='$product' OR p.pname='$product' OR p.cas_product_id='$product') AND p.dealer_id=$dealer_id");
			//echo $this->db->last_query();
			$res=$sql->result();
			$val='';
			$i=0;
			if($res){
				$val .="<div style='text-align:left;' class='title'>".$res[0]->pname."(".$res[0]->cas_product_id.")</div>";
				foreach ($res as $rw){
					$i++;
					$val .='<div style="width:30%; float:left; padding:2px;">'.$i.'. '.$rw->channel_name.'</div>';
				}
				echo $val;
			}
		}else{
			echo '<div style="width:30%; float:left; padding:2px;">No Channels Found</div>';
		}
	}
        public function getpackage_percentages(){
		$package_id = $_POST['package_id'];
                $reseller_id = $_POST['reseller_id'];
                $dealer_id = $_SESSION['user_data']->dealer_id;
		if($package_id!=0 && $reseller_id!=0){
			$dealer_id = $_SESSION['user_data']->dealer_id;
			$sql=$this->db->query("SELECT *  FROM eb_bp_package_percent pp
                                                WHERE pp.product_id='$package_id' AND pp.reseller_id='$reseller_id' AND pp.dealer_id=$dealer_id");
			//echo $this->db->last_query();
			$res=$sql->result();
			$val='<tr><th>Sr.No.</th><th>Percentage</th><th>Start Date</th><th>End Date</th></tr>';
			$i=0;
			if($res){
				
				foreach ($res as $rw){
					$i++;
					$val .= '<tr>';
                                        $val .= '<td>'.$i.'</td>';
                                        $val .= '<td>'.$rw->percentage.'%</td>';
                                        $val .= '<td>'.$rw->from_date.'</td>';
                                        $val .= '<td>'.$rw->to_date.'</td>';
                                        $val .= '</tr>';
				}
				echo $val;
			}
		}else{
			echo "Percentages Are Not Added";
		}
	}
	public function getMacidofGroup(){
	    $gid= $_POST['groupid'];
            $config_id= $_POST['config_id'];
	    $did= $_SESSION['user_data']->dealer_id;
            $options = "";
            $sql = "select s.mac_address, s.vc_number, bs.use_mac from eb_stock s 
		    INNER JOIN customer_device cd ON cd.box_number=s.serial_number and cd.dealer_id=$did 
		    INNER JOIN customer_group cg ON cg.customer_id = cd.customer_id
                    INNER JOIN eb_cas_msg_config cm ON cm.backend_setup_id = s.backend_setup_id
                    INNER JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id
		    where s.status=1 and s.dealer_id='$did' and cg.group_id='$gid' and cm.config_id=$config_id";
            $query = $this->db->query($sql);
            
            if($query){
                if($query->num_rows()>0){
                    $result = $query->result();
			foreach($result as $res){
                                $mac = ($res->use_mac==1)?$res->mac_address:$res->vc_number;
                                if($mac!='')
				$options .= "<option value='".$res->mac_address."' selected='selected'>".$mac."</option>";
		        }
                    
                } 
            } 
		echo $options;
	}
        public function getCasServerDetails(){
            $id = $_POST['id']; 
            $sql = "select hostname_ip,cas_server_type,EMM,use_mac,left_trim,right_trim, user_name, password,display_name,priority,is_primary from backend_setups where backend_setup_id='$id' and setup_for=5 and dealer_id=".$_SESSION['user_data']->dealer_id;
            $query = $this->db->query($sql);
            if($query){
               if($query->num_rows()>0){
                   // $result = $query->row()->hostname_ip.'XXX'.$query->row()->cas_server_type.'XXX'.$query->row()->EMM.'XXX'.$query->row()->use_mac.'XXX'.$query->row()->left_trim.'XXX'.$query->row()->right_trim.'XXX'.$query->row()->user_name.'XXX'.$query->row()->password.'XXX'.$query->row()->display_name.'XXX'.$query->row()->priority.'XXX'.$query->row()->is_primary;
               	//changing to @@@ as there is issue with words ending with X
                   $result = $query->row()->hostname_ip.'@@@'.$query->row()->cas_server_type.'@@@'.$query->row()->EMM.'@@@'.$query->row()->use_mac.'@@@'.$query->row()->left_trim.'@@@'.$query->row()->right_trim.'@@@'.$query->row()->user_name.'@@@'.$query->row()->password.'@@@'.$query->row()->display_name.'@@@'.$query->row()->priority.'@@@'.$query->row()->is_primary;
                   echo $result;
               } else echo '0';
            } else echo '0';
        }
        public function getLocationDetails() {
        $id = $_POST['id'];
        $sql_str = "SELECT ell.location_id, ell.location_code,ell.location_name,ell.phase_id,ep.phase_name,elc.name countryName,els.name statename, elc.iso, els.id, m.mandal_id,m.district_id,ell.pin_code FROM eb_location_locations ell
						INNER JOIN eb_location_states els ON els.id = ell.state_id
						LEFT JOIN eb_phases ep ON ep.phase_id = ell.phase_id
                        LEFT JOIN eb_mandals m ON m.mandal_id = ell.mandal_id 
						INNER JOIN eb_location_countries elc ON elc.iso=els.country_code where ell.dealer_id=" . $_SESSION['user_data']->dealer_id . " and ell.location_id=$id";
        $query = $this->db->query($sql_str);
        if ($query) {
            if ($query->num_rows() > 0) {
                $result = $query->row()->iso . 'XXX' . $query->row()->id . 'XXX' . $query->row()->location_code . 'XXX' . $query->row()->location_name . 'XXX' . $query->row()->location_id . 'XXX' . $query->row()->phase_name . 'XXX' . $query->row()->phase_id . 'XXX' . $query->row()->mandal_id  . 'XXX' . $query->row()->district_id. 'XXX' . $query->row()->pin_code;
                echo $result;
            } else
                echo '0';
        } else
            echo '0';
    }
        public function CheckCasServerDetails(){
            $id = $_POST['id']; 
            $did= $_SESSION['user_data']->dealer_id;
            $sql1 = $this->db->query("select count(stock_id) c1 from eb_stock where backend_setup_id='$id' and status=1 and dealer_id=$did");
            $sql2 = $this->db->query("select count(product_id) c2 from  eb_products  where backend_setup_id='$id' and dealer_id=$did");
		    if($sql1->row()->c1==0 && $sql2->row()->c2==0){
			    $this->db->query("delete from backend_setups where backend_setup_id='$id'");
			    echo '1';
		    }  else echo '0';
        } 
        public function CheckChannelDetails(){
            $cid = $_POST['cid'];
            $sql1 = $this->db->query("select count(product_channels_id) c1 from eb_product_channels where channel_id='$cid' and dealer_id=".$_SESSION['user_data']->dealer_id);
            
		    if($sql1->row()->c1==0){
                       echo '1';
		    }  else echo '0';
        }
            
	public function is_setopbox(){
		$itemid = $_POST['itemid'];
		$sql = "select is_setup_box from eb_stock_items where item_id='$itemid' and dealer_id=".$_SESSION['user_data']->dealer_id;
		$query = $this->db->query($sql);		
		if($query && $query->num_rows()>0)
		echo $query->row()->is_setup_box;
		else echo 0;

	}
        public function chkServerType(){
            $stock_id = $_POST['stock_id'];
            $product_id = $_POST['product_id'];

            
            $sql1 = $this->db->query("select backend_setup_id from eb_stock where stock_id='$stock_id'");
            $res1 = $sql1->row()->backend_setup_id;
            
            $sql2 = $this->db->query("select backend_setup_id from eb_products where product_id='$product_id'");
            $res2 = $sql2->row()->backend_setup_id;
            
            if($res1=='0' && $res2=='0')
            echo 1;
            else if($res1=='0' && $res2!='0')
            echo 2;
            else if($res1!='0' && $res2=='0')
            echo 3;
            else if($res1 != $res2)
            echo 4;
            else if($res1 == $res2)
            echo 5;
        }
        public function chkServerTypes($product_id=0, $bes_id=0){            
            $besid = isset($_POST['besid'])?$_POST['besid']:$bes_id;
            $pid = isset($_POST['pid'])?$_POST['pid']:$product_id;
            if($pid==0){
                $data= "1";
            }
			else{
				$sql2 = $this->db->query("select backend_setup_id from eb_products where product_id='$pid'");
				$res2 = $sql2->row()->backend_setup_id;
				if($res2==$besid){
					$data ="1";
				} else {
					$data ="2";
				}
            }
			if(isset($_POST['pid'])){
				echo $data;
			}
			else{
				return $data;
			}
            
        }
        public function chkSTBServerType(){
            
			$stock_id = $_POST['stock_id'];        
            $sql1 = $this->db->query("select backend_setup_id from eb_stock where stock_id='$stock_id'");
            $res1 = $sql1->row()->backend_setup_id;
           
            if($res1==0){
                echo "2";
            } else {
                echo "5";
            }
            
            
        }
        public function getProductName(){
            $product_id = $_POST['product_id'];
            //echo $product_id;
            $sql1 = $this->db->query("select cas_product_id as pname from eb_products where product_id='$product_id'");
            $pname = $sql1->row()->pname;
            echo $pname;
            
        }
        public function chkServices(){
            $stock_id = $_POST['stock_id'];
            
            $sql_qry="SELECT COUNT(cd.customer_device_id) c FROM  customer_device cd
                      INNER JOIN eb_stock s ON s.serial_number = cd.box_number
                      WHERE s.stock_id='$stock_id'";
            $query = $this->db->query($sql_qry);
            
            if($query){
                if($query->row()->c>0){
                   $sql_qry1= "SELECT COUNT(csbd.customer_service_id) c FROM  eb_customer_service_box_details csbd
                        INNER JOIN  eb_customer_service cs ON cs.customer_service_id=csbd.customer_service_id
                        AND cs.status=1
                        INNER JOIN customer_device cd ON cd.customer_device_id = csbd.box_number
                        INNER JOIN eb_stock s ON s.serial_number = cd.box_number
                        WHERE s.stock_id='$stock_id'";
                        $query1 = $this->db->query($sql_qry1);
                        if($query1){
                            if($query1->row()->c>0){
                                echo 0;
                            } else {echo 1;}
                        } 
                } else{
                   $sql_qry2 = "SELECT product_name FROM eb_stock where stock_id='$stock_id'"; 
                   $query2 = $this->db->query($sql_qry2);
                   
                    if($query2){
                        $pname =$query2->row()->product_name;
                        if($pname=='NULL' || $pname=='All Channels' || $pname=='No Channels' || $pname==''){
                            echo 1;
                        } else {echo 0;}
                    }
                }
            }
          }
          public function getBroadcasterProducts(){
			  $bc_id = ($_POST['bc_id'])?$_POST['bc_id']:'-1';
			  $join='';$condition='';
                           $dealer_id = $_SESSION['user_data']->dealer_id;
                           $broadcaster_cond ="";
                          if($bc_id!='-1'){
                              
                              $broadcaster_cond = " AND bc.broadcaster_id='$bc_id' ";
                          }
                          if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
		
                                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                            }else{
                                    $int_employee_id = $_SESSION['user_data']->employee_id;
                            }
			  if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" ) { 
				$join ="INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = p.product_id
						INNER JOIN employee e ON e.employee_id = rpm.employee_id
						INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
				$condition = " AND (e.parent_id =$int_employee_id OR e1.parent_id =$int_employee_id) ";  
			  }
                          if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER" ){ //IF LCO LOGIN ----TRIDEEP
				$join = "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = p.product_id AND pm.employee_id = $int_employee_id
                                        INNER JOIN employee e ON e.employee_id = pm.employee_id" ;
                                       $condition = " AND (e.employee_id =$int_employee_id )"; 
			}
			  $options = "<option value=\"-1\">Select</option>";
			  $sql = "SELECT DISTINCT p.pname, p.product_id,bs.cas_server_type,bs.display_name from eb_products p
                                        INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id
					  INNER JOIN eb_product_channels pc ON p.product_id=pc.product_id
					  INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=pc.channel_id $join
					  WHERE p.dealer_id=$dealer_id $condition $broadcaster_cond";
			  $query = $this->db->query($sql);
			  if($query && $query->num_rows()>0){
				  $res = $query->result();
				  
                    foreach($res as $row){
                        $select = ((isset($_SESSION['packages'])) && $_SESSION['packages'] == $row->product_id )?'SELECTED':'';
                        $options .= "<option value='".$row->product_id."' $select >".$row->pname.'('.$row->display_name.')'."</option>";
                    }
			  }
			  echo $options;
		  }
    public function getBroadcasterProductsChannels(){
            $bc_id = $_POST['bc_id'];
            $package_id = $_POST['package_id'];
			$join='';$condition='';
                        if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
		
                                $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                        }else{
                                $int_employee_id = $_SESSION['user_data']->employee_id;
                        }
			//Below condition check for distributor login or subdistributor login-and add dealer_id in query---TRIDEEP
			if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" ) { 
				$join ="INNER JOIN eb_product_channels pc ON pc.channel_id = c.channel_id
						INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = pc.product_id
						INNER JOIN employee e ON e.employee_id = rpm.employee_id
						INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
				$condition = " (e.parent_id =$int_employee_id OR e1.parent_id =$int_employee_id) AND ";  
			}
                        if($_SESSION['user_data']->users_type=='RESELLER'  || $_SESSION['user_data']->employee_parent_type=="RESELLER"  ) { 
				$join ="INNER JOIN eb_product_channels pc ON pc.channel_id = c.channel_id
						INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = pc.product_id
						INNER JOIN employee e ON e.employee_id = rpm.employee_id ";
				$condition = " (e.employee_id =$int_employee_id ) AND ";  
			}
            $options = "<option value=\"-1\">Select</option>";
            $sql = "select c.channel_name, c.channel_id from eb_channels c
                            INNER JOIN eb_product_channels pc ON c.channel_id=pc.channel_id AND pc.product_id='$package_id'
                            INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=pc.channel_id $join
                            WHERE $condition bc.broadcaster_id='$bc_id'";
            $query = $this->db->query($sql);
            if($query && $query->num_rows()>0){
                    $res = $query->result();

    foreach($res as $row){
        $options .= "<option value='".$row->channel_id."'>".$row->channel_name."</option>";
    }
            }
            echo $options;

    }
   public function getBroadcasterChannels(){
                $bc_id = $_POST['bc_id'];
				$cond = '';
				$join="";
		        $dealer_id = $_SESSION['user_data']->dealer_id;
		        if ($bc_id > 0) {
		            $cond = " AND bc.broadcaster_id='$bc_id'";
		            $join="INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=c.channel_id";
		        }
                $options = "<option value=\"-1\">Select</option>";
                $sql = "SELECT c.channel_name, c.channel_id 
                from eb_channels c
                $join
                where c.dealer_id=$dealer_id  $cond ORDER BY c.channel_name
                                ";
                $query = $this->db->query($sql);
                if($query && $query->num_rows()>0){
                        $res = $query->result();
					foreach($res as $row){
						$select = ((isset($_SESSION['channels']) && $_SESSION['channels'] == $row->channel_id) || (isset($_SESSION['bc_channelId']) && $_SESSION['bc_channelId'] == $row->channel_id))?'SELECTED':'';
						$options .= "<option value='".$row->channel_id."' $select>".$row->channel_name."</option>";
					}
                }
                echo $options;

    }
	 public function getCASChannels()
	{
		$sql_str = "SELECT c.channel_id, c.channel_name FROM eb_channels c
					WHERE c.dealer_id=".$_SESSION['user_data']->dealer_id." order by c.channel_name";
		$query = $this->db->query($sql_str);
		 if($query && $query->num_rows()>0){
                        $res = $query->result();
						$options .= "<option value='-1' >Select</option>";
					foreach($res as $row){
						$options .= "<option value='".$row->channel_id."' >".$row->channel_name."</option>";
					}
                }
                echo $options;
	}
    public function getCustomerHavingQuantity(){
        $serial = $_POST['serial'];
        $customer = $_POST['cid'];
        $location_id = $_POST['location_id'];
        $did=$_SESSION['user_data']->dealer_id;
        if($serial!='' && $serial!='-1'){
        $sql=$this->db->query("SELECT  SUM(sol.`units`)as sum, m.measure_name FROM eb_stock_outward_log sol 
                               INNER JOIN eb_stock s ON s.serial_number = sol.serial_number and s.dealer_id=$did
                               INNER JOIN eb_stock_items si ON si.item_id=s.item_id 
                               INNER JOIN eb_measures m ON m.measure_id = si.unit_of_measure  and m.dealer_id=$did
                               WHERE sol.serial_number='$serial' and sol.dealer_id=$did and sol.outward_to=$customer and sol.location_id=$location_id");
        //echo $this->db->last_query();
        $out_sum=$sql->row()->sum;  
        echo $out_sum."/".$sql->row()->measure_name;
		}
	}
    public function getAvailableQuantity(){
        $serial = $_POST['serial'];
        $location_id = $_POST['location_id'];
        $did=$_SESSION['user_data']->dealer_id;
        if($serial!='' && $serial!='-1'){
        $sql=$this->db->query("SELECT  SUM(sol.`units`)as sum, m.measure_name FROM eb_stock_outward_log sol 
                               INNER JOIN eb_stock s ON s.serial_number = sol.serial_number and s.dealer_id=$did
                               INNER JOIN eb_stock_items si ON si.item_id=s.item_id 
                               INNER JOIN eb_measures m ON m.measure_id = si.unit_of_measure  and m.dealer_id=$did
                               WHERE sol.serial_number='$serial' and sol.location_id=$location_id and sol.dealer_id=$did");
        $out_sum=$sql->row()->sum;  

        $sql1=$this->db->query("SELECT item_qty,stock_id FROM eb_stock WHERE serial_number='$serial' and dealer_id=$did and stock_location=$location_id");
        $qty=$sql1->row()->item_qty;

        $available=$qty-$out_sum; 
        echo $available."/".$sql->row()->measure_name;}
    }
	/*public function sendSTBDetails()
	{
		$cname = $_POST['name'];
		$srno = $_POST['srno'];
		$mac= $_POST['mac'];
		$isactive = $_POST['isactive'];
		$activateddate = $_POST['activateddate'];
		$installationaddress = $_POST['installationaddress'];
		$mobile = $_POST['mobile'];
		$cas = $_POST['cas'];
		$to = $_POST['email'];
		$sub = "STB Details";
		$mail = new SendMails();
		$body =$strMessage1="
							<table>
									<tr>
										<td>Dear $first_name ,</td>
										<td></td>
										<td></td>
									</tr>
									<tr>
										<td>
										<strong>Your New Credentials of Ezybill is:</strong>
										</td>
									</tr>
									<tr>
										<td>
										Username : $unm
										</td>
									</tr>
								<tr>
										<td>
										Password : <b>$pas</b>
										</td>
									</tr>
								<tr><td><hr/></td></tr>
									<tr>
										<td>
										Thank You 
										</td>
									</tr>
								
								<tr><td>Pleasure to secure you</td></tr>	
								<tr><td>ITP Staff</td></tr>	
									
								</table>
						";;
		$mail->send_Email($to,$sub,$body);
	}*/

    public function getPackageChannels()
	{
		// $packageWiseCount variable used to get broadcaster channels
        $packageWiseCount = isset($_POST['packageWiseCount'])?$_POST['packageWiseCount']:0;
        $broadcaster = isset($_POST['broadcaster'])?$_POST['broadcaster']:0;
        $pkg_type = isset($_POST['pkg_type'])?$_POST['pkg_type']:0;
        $join="";
        
        $pid = $_POST['pid'];
        $end_date = ($_POST['end_date'])?getDateFormat($_POST['end_date'], 3):date('Y-m-d');
        $curDate = date('Y-m-d');
        if($pkg_type == 5){
        	$bundle_sql = "select group_concat(child_product_id) as cntproduct_id from eb_product_combo_details where master_product_id=$pid and status=1";
        	$bundle_query = $this->db->query($bundle_sql);
        	 if($bundle_query && $bundle_query->num_rows()>0){
        	 	$pid = $bundle_query->row()->cntproduct_id;
        	 }
        }
        if($packageWiseCount>0 && $broadcaster>0){
          $join ="INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=c.channel_id AND bc.end_date>= '$end_date' and bc.broadcaster_id=$broadcaster";  
        }
      $sql = "select c.channel_name from eb_channels c 
                $join
                INNER JOIN eb_product_channels_statuses cs ON cs.channel_id = c.channel_id
                AND cs.channel_status_id = (select MAX(channel_status_id) from eb_product_channels_statuses where product_id in ($pid) AND channel_id=cs.channel_id And date(changed_datetime)<='$end_date')
               
                WHERE cs.product_id in ($pid)  AND cs.status=1 AND cs.dealer_id=".$_SESSION['user_data']->dealer_id;

        //echo $sql;
        $query = $this->db->query($sql);
		$i =1;
		
		//$channels2 = '<div style=" border: 1px solid #315C7C; width:98%; overflow:auto; overflow-y: hidden;">';
        $channels = '<p style=" background:#315C7C; color:#E6F0F3; border: 1px solid #315C7C; font-size:13px; font-weight:bold; padding:5px; width:98%; ">Channels in this package :</p>';
		foreach($query->result() as $row){
            $channels .= "<div style='float:left;  width:30%; text-align:left; padding:5px;'>$i. ".$row->channel_name."</div>";
			$i++;
        }
        $channels .= '</div>';
		
        echo $channels;
       
		
		
	}
        public function isChannelAssocToProduct(){
            $channel_id = $_POST['channel_id'];
            $bid = $_POST['bid'];
            $sql = "select count(product_channels_id) c from eb_product_channels where channel_id=$channel_id and  dealer_id = ".$_SESSION['user_data']->dealer_id;
            $query = $this->db->query($sql);
            //echo $this->db->last_query(); 
            if($query ){
                if($query->row()->c>0)
                echo 1;
                else echo 0;
            } else echo 0;
        }
		public function custmap(){
			$cm = $_POST['mapcust'];
			$val='';			
			$sql = "SELECT  customer_id, first_name, last_name,account_number FROM customer c WHERE c.customer_id NOT IN (select customer_id FROM customer_device WHERE dealer_id=".$_SESSION['user_data']->dealer_id.") AND dealer_id=".$_SESSION['user_data']->dealer_id." AND (first_name LIKE '".$cm."%' )";
            $query = $this->db->query($sql);
			$val .="<tr  >";
			$val .="<th width='60' align='center'>Select </th>";
			$val .="<th width='100' align='center'>Customer ID</th>";
			$val .="<th width='120' align='center'>Customer Name</th>";			
			$val .="</tr>";
			foreach($query->result() as $cus){
				$val .= "<tr>";
				$val .= "<td align='center'><input type='radio' name='cus'  cid='".$cus->customer_id."' value='".$cus->first_name. '  '.$cus->last_name." ' first_name='".$cus->first_name."' last_name='".$cus->last_name."'/></td>";
				$val .= "<td align='center'>".$cus->customer_id." (".$cus->account_number.")</td>";
				$val .="<td align='center'>".$cus->first_name." ".$cus->last_name."</td>";				
				$val .= "</tr>";				
			}			
			echo $val;			
		}		
		// this function for getting the STbs in map customer stb,
		// query modified (if stb is not paired [from stb managment ]then it is not showing in mapcustomerSTb) by anurag 
	public function stbmap(){
		$sm=$_POST['mapstb'];

		$val='';
		$whr='';	

		if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER'){ //IF LCO LOGIN ----TRIDEEP
                      if($_SESSION['user_data']->employee_parent_type=='RESELLER'){
                       $reseller_employee_id = $_SESSION['user_data']->employee_parent_id;
                   }else{
                        $reseller_employee_id = $_SESSION['user_data']->employee_id;
                   }
			$whr =" AND l.reseller_id=$reseller_employee_id";
        	}
		$sql ="SELECT s.vc_number, s.box_number, s.mac_address,s.make, s.backend_setup_id,l.reseller_id, 
				s.stock_id,s.serial_number , s.stock_location, b.cas_server_type, g.group_id 
				FROM eb_stock s 
				INNER JOIN backend_setups b ON b.backend_setup_id=s.backend_setup_id
				INNER Join eb_stock_location l ON s.stock_location=l.location_id 
				INNER JOIN employee e ON e.employee_id = l.reseller_id AND e.is_authorized=0
				INNER JOIN employee_group eg ON eg.employee_id=e.employee_id
				INNER JOIN groups g ON g.group_id=eg.group_id
		        WHERE l.reseller_id<>0 $whr AND s.box_number<>'' AND s.box_number IS NOT NULL AND s.defective_stock =0 AND s.not_paired_in_cas =0  AND  s.serial_number NOT IN  (select box_number  FROM customer_device  WHERE dealer_id=".$_SESSION['user_data']->dealer_id.") AND s.dealer_id=".$_SESSION['user_data']->dealer_id." AND s.serial_number LIKE  '".$sm."%'";
		//$sql="SELECT vc_number, box_number, mac_address,make,backend_setup_id,stock_id FROM eb_stock  WHERE   box_number NOT IN  (select box_number FROM customer_device  WHERE dealer_id=".$_SESSION['user_data']->dealer_id.")  AND dealer_id=".$_SESSION['user_data']->dealer_id." AND (vc_number LIKE '".$sm."%')";
		$query = $this->db->query($sql);
		//echo $this->db->last_query(); die();
		$val .="<tr><th width='60' align='center'>Select </th>  
		<th align='left'>Serial Number (".$_SESSION['mac_vc_column'].")</th>
		<th>Server Type</th></tr>";
		
		foreach($query->result() as $stb){
			if($stb->vc_number !=''){
			$val .= "<tr>";
			$val .= "<td align='center'><input type='radio' name='stb1' id='stb[]'   vc='".$stb->vc_number."'  make= '".$stb->make."' value='".$stb->serial_number."' stock_id= '".$stb->stock_id."'  backend_setup_id= '".$stb->backend_setup_id."'   box_number='".$stb->box_number."'reseller_id='".$stb->reseller_id."'group_id='".$stb->group_id."'mac_address='".$stb->mac_address."' stock_location='".$stb->stock_location."'/></td>";
			$val .= "<td >".$stb->serial_number."  (".$stb->vc_number.")</td>";
			$val .="<td>".$stb->cas_server_type."</td>";
			$val .="</tr>";
		}else{
			$val .= "<tr>";
			$val .= "<td align='center'><input type='radio' name='stb1' id='stb[]'   vc='".$stb->vc_number."'  make= '".$stb->make."' value='".$stb->serial_number."' stock_id= '".$stb->stock_id."'  backend_setup_id= '".$stb->backend_setup_id."'   box_number='".$stb->box_number."'group_id='".$stb->group_id."'mac_address='".$stb->mac_address."' stock_location='".$stb->stock_location."'/></td>";
			$val .= "<td >".$stb->serial_number."  (".$stb->mac_address.")</td>";
			$val .="</tr>";
			}
		}
		echo $val;	
	}
	
	public function getAllCasProducts(){
		$cond = ''; $options=""; $taxes='';
		$obj_products = new productsmodel();
		if(isset($_SESSION['modules']['CAS']))
		{
			$cond.= " p.cas_product_id IS NOT NULL AND ";
		}
		
		//product blocking conditions only applicable in LCO login by SRAVANI
		
		if(isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO) && $_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO==0)
		{
			$cond.= " pm.is_blocked In (0,2) AND ";
		}
		else{
			if(isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO) && $_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO==2)
			{
				if($_SESSION['user_data']->users_type=='RESELLER')
				{
					$cond.= " pm.is_blocked In (0,2)  AND ";
				}
			}
			else
			if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || 
   $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || 
   $_SESSION['user_data']->users_type=='RESELLER' ||
                        $_SESSION['user_data']->employee_parent_type=='RESELLER' || 
                         $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR' || 
                         $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR')
			{
				$cond.= " pm.is_blocked In (0,2) AND ";
			}
		}
         
		
		
		// if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || 
   // $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || 
   // $_SESSION['user_data']->users_type=='RESELLER' ||
                        // $_SESSION['user_data']->employee_parent_type=='RESELLER' || 
                         // $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR' || 
                         // $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR' || 
   // !(isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO) && $_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO==1))
		// {
			// $cond.=" pm.is_blocked=0 AND ";
		// }
		$reseller_id = $_POST['reseller_id'];
		$besid = $_POST['besid'];
		$stb_type_id = $_POST['stb_type_id'];
		$is_user_blocked = isset($_POST['is_user_blocked'])?$_POST['is_user_blocked']:0;
                $is_customer_verified = isset($_POST['is_customer_verified'])?$_POST['is_customer_verified']:'';
                 //getting default verification lov value by GOPI
                $dealer_id = $_SESSION['user_data']->dealer_id;
                $lov_vaue = 'DEFAULT_VERIFICATION';
                 $default_verification = $this->change_pass_model->getLovValue($lov_vaue,$dealer_id);
                 if($is_customer_verified!=''){
                     $default_verification = $is_customer_verified;
                 }
                // if($default_verification==0){
                     $cond.=" is_verified=$default_verification AND ";
                // }
               // $default_verification = isset($_SESSION['dealer_setting']->DEFAULT_VERIFICATION)?$_SESSION['dealer_setting']->DEFAULT_VERIFICATION:1;
		//echo $default_verification;
		
		/**** the below two joins are commented for remove package group concept by SRAVANI***/
		
		//--INNER JOIN employee_group eg ON eg.group_id=epg.group_id AND eg.employee_id=$reseller_id 
		//INNER JOIN eb_product_group epg ON pm.product_id = epg.product_id
		
		/**** the below two joins are commented for remove package group concept***/
		
		$sql_query = "select p.*,bs.cas_server_type from  eb_products p 
		INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id 
		INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = p.product_id AND pm.employee_id=$reseller_id 
		INNER JOIN eb_product_channels epc ON epc.product_id = p.product_id
		INNER JOIN eb_product_to_stbtype_mapping psm ON psm.product_id=p.product_id  
		where $cond p.plugin_id=4 and p.pricing_structure_type<>3 AND p.status=1 AND p.backend_setup_id=$besid AND p.dealer_id=".$_SESSION['user_data']->dealer_id. " AND psm.stb_type_id=$stb_type_id GROUP BY p.product_id  order by pname";
		$query=$this->db->query($sql_query);
		//echo $this->db->last_query();
		 $options = "<option value=\"-1\" style = 'color :#315C7C;'>Select Package</option>";
		if($query && $query->num_rows()>0){
			$res = $query->result();
			$selected=($query->num_rows()==1)?"selected":"";
			foreach($res as $row){
			$taxes='';
			
				if((isset($_SESSION['dealer_setting']->TAX1)) && $_SESSION['dealer_setting']->TAX1!='TAX1')
				{   
					$taxes .= ($row->is_taxrate==1)?$_SESSION['dealer_setting']->TAX1." ".$row->tax_rate."%, ":$_SESSION['dealer_setting']->TAX1." Rs.".$row->tax_rate.',';
				}
				if((isset($_SESSION['dealer_setting']->TAX2)) && $_SESSION['dealer_setting']->TAX2!='TAX2' )
				{
					$taxes .= ($row->is_taxrate1==1)?$_SESSION['dealer_setting']->TAX2." ".$row->tax2."%, ":$_SESSION['dealer_setting']->TAX2." Rs.".$row->tax2.',';
				}
				if((isset($_SESSION['dealer_setting']->TAX3)) && $_SESSION['dealer_setting']->TAX3!='TAX3')
				{
					$taxes .= ($row->is_taxrate2==1)?$_SESSION['dealer_setting']->TAX3." ".$row->tax3."%, ":$_SESSION['dealer_setting']->TAX3." :Rs.".$row->tax3.',';
				}
				if((isset($_SESSION['dealer_setting']->TAX4)) && $_SESSION['dealer_setting']->TAX4!='TAX4')
				{
					$taxes .= ($row->is_taxrate3==1)?$_SESSION['dealer_setting']->TAX4." ".$row->tax4." %, ":$_SESSION['dealer_setting']->TAX4." :Rs.".$row->tax4.',';
				}
				if((isset($_SESSION['dealer_setting']->TAX5)) && $_SESSION['dealer_setting']->TAX5!='TAX5' )
				{
					$taxes .= ($row->is_taxrate4==1)?$_SESSION['dealer_setting']->TAX5." ".$row->tax5."%, ":$_SESSION['dealer_setting']->TAX5." :Rs.".$row->tax5.',';
				}
				if((isset($_SESSION['dealer_setting']->TAX6)) && $_SESSION['dealer_setting']->TAX6!='TAX6')
				{
					$taxes .= ($row->is_taxrate5==1)?$_SESSION['dealer_setting']->TAX6." ".$row->tax6."%, ":$_SESSION['dealer_setting']->TAX6." :Rs.".$row->tax6.',';
				}
				if($is_user_blocked==0){
					$font_colour = ($row->base_price>0)?'orange':'#315C7C';
					$font = ($row->base_price>0)?'bold':'normal';
					$options .= "<option  $selected value=".$row->product_id." style = 'color :$font_colour;font-weight:$font' base_price='".$row->base_price."' show_taxes='".$taxes."' pricing_structure='".$row->pricing_structure_type."' show_baseprice='".$row->show_baseprice."' alacarte_check='".$row->alacarte."'> ".$row->pname."(".$row->cas_server_type.")</option>";
				}else{
					//if user is blocked then provide the activation which are having zero bill amount
					$total_amount = $obj_products->CheckAmountForBlockedUser($row);
					
					if($total_amount==0){
						$font_colour = ($row->base_price>0)?'orange':'#315C7C';
						$font = ($row->base_price>0)?'bold':'normal';
						$options .= "<option  $selected value=".$row->product_id." style = 'color :$font_colour;font-weight:$font' base_price='".$row->base_price."' show_taxes='".$taxes."' pricing_structure='".$row->pricing_structure_type."' show_baseprice='".$row->show_baseprice."' alacarte_check='".$row->alacarte."'> ".$row->pname."(".$row->cas_server_type.")</option>";
					}
				}
			}
		}
		echo $options;		
	}
	
	
	public function getPackagesOfServer(){
		$back_setup_id=$_POST['back_setup_id'];
		$reseller_id = $_POST['reseller_id'];
		$options = "<option value=\"0\">Select Package</option>";
		 $sql = "SELECT p.pname, p.product_id, p.cas_product_id , b.cas_server_type
		FROM eb_products p
		INNER JOIN backend_setups b ON p.backend_setup_id = b.backend_setup_id
		INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = p.product_id AND pm.employee_id=$reseller_id 
		WHERE p.backend_setup_id =$back_setup_id and p.pricing_structure_type <> 3 AND p.status=1 and p.dealer_id=".$_SESSION['user_data']->dealer_id;
		$query = $this->db->query($sql);

		if($query && $query->num_rows()>0){
				$res = $query->result();

			foreach($res as $row){
				
				$options .= "<option value=".$row->product_id.">".$row->pname."(".$row->cas_server_type.")"."</option>";
			}
		}
		echo $options;
   }

   
   /***** Package Name *****/
   
   public function check_package()
	{
        $pname= mysql_real_escape_string($_POST['pname']);
		$cond2 = "";
		$stype = $_POST['stype'];
		$did = $_SESSION['user_data']->dealer_id;
		if($stype!=0)
		$cond2 = "AND backend_setup_id IN ($stype)";
		$sql_str = "select count(pname) packagename from eb_products where pname='$pname' $cond2 AND dealer_id=$did";
		$query = $this->db->query($sql_str);
		$res = $query->row();
		//print_r($res);
		if($res->packagename==0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
   
   /***** Package Name *****/
   /***** validating sku *****/
   
   public function check_sku()
	{
        $sku= mysql_real_escape_string($_POST['sku']);
		$cond2 = "";
		$stype = $_POST['stype'];
		$did = $_SESSION['user_data']->dealer_id;
		if($stype!=0)
		$cond2 = "AND backend_setup_id IN ($stype)";
		$sql_str = "select count(sku) skuCount from eb_products where sku='$sku' $cond2 AND dealer_id=$did";
		$query = $this->db->query($sql_str);
		if($query && $query->row()->skuCount==0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
   
   /***** validating sku *****/  
   /***** Cas Product Id *****/
   
   public function check_casProductId()
	{
		$stype="";
		$cond2 = "";
        $casProductId= $_POST['casProductId'];
		$stype = $_POST['stype'];
		$did = $_SESSION['user_data']->dealer_id;
		if($stype!="")
		$cond2 = "AND backend_setup_id = $stype";
		$sql_str = "select count(cas_product_id) casProductId from eb_products where cas_product_id='$casProductId' $cond2 AND dealer_id=$did";
		$query = $this->db->query($sql_str);		
		$res = $query->row();
		//print_r($res);
		if($res->casProductId==0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
   
   /***** Cas Product Id *****/
 			
	 public function view_mapping_product() {
        $eid = $_SESSION['user_data']->employee_id;
        $id = $_SESSION['user_data']->dealer_id;
        $reseller = $_POST['reseller_id'];
        $product = $_POST['product_id'];
        $query = $this->db->query("SELECT ebp.amount_type,ebp.percentage,ebp.from_date,ebp.to_date,CONCAT(e.first_name,' ', COALESCE(e.last_name, ''))created_by,e.users_type 	
									FROM eb_bp_package_percent ebp
									INNER JOIN employee e ON e.employee_id=ebp.created_by
									WHERE ebp.reseller_id=$reseller AND ebp.product_id=$product AND  ebp.dealer_id=$id");
        //AND ebp.created_by=$eid
        $val = "";

        if ($query && $query->num_rows() > 0) {
            $val = '<table class="mygrid" cellspacing="1" cellpadding="1" ><tr><th>Start Date</th><th>End Date</th><th>Share Value</th><th>Created By</th></tr>';
            foreach ($query->result() as $row) {
                $val .= '<tr>';
                $val .= '<td width="25%">' . $row->from_date . '</td>';
                $val .= '<td width="25%">' . $row->to_date . '</td>';
				if($row->amount_type == 1){
               		 $val .= '<td>' . $row->percentage . '%</td>'; 
				}else { $val .= '<td>Rs.' . $row->percentage . '</td>';  }
                $val .= '<td>' . $row->created_by . '(' . $row->users_type . ')</td>';
                $val .= '</tr>';
            }
            $val .= "</table>";
        }
        echo $val;
    }
	
	/***** Check Customer Products *****/
	public function check_customer_products()
	{
		$product_id = $_POST['product_id'];
		$did = $_SESSION['user_data']->dealer_id;
		$sql_str = "select count(user_service_id) cp 
		from eb_customer_products 
		where product_id ='$product_id' 
		AND status=1
		AND dealer_id=$did";
		$query = $this->db->query($sql_str);
		$res = $query->row();
		//print_r($res);
		if($res->cp >= 1)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
	/***** Check Customer Products *****/
	
	/***** check_products_mapped_customer *****/
	public function check_products_mapped_customer()
	{
		$employee_id = $_POST['employee_id'];
		$did = $_SESSION['user_data']->dealer_id;
		$selected = $_POST['selected'];
		$res_str = "";
		//print_r($selected);
		foreach($selected as $product_id)
		{
			$sql_str = "select count(c.customer_id) cm from customer c 
			 INNER JOIN eb_customer_products cp ON cp.customer_id = c.customer_id 
			 where cp.product_id =$product_id AND c.reseller_id=$employee_id AND cp.status <> 0 AND c.dealer_id=$did";
			  //echo "<br/>";
			$query = $this->db->query($sql_str);
			
			$res = $query->row();
			if($res->cm >= 1)
			{
				$sql1 = "select pname from eb_products where product_id=$product_id";
				$query = $this->db->query($sql1);
				if($query)
				{
					$res_str .= $query->row()->pname.', ';
					
				}
			}
		}
		$res_str=rtrim($res_str,', ');
		echo $res_str;
	}
	/***** check_products_mapped_customer *****/
	
	/***** delete_mapped_products *****/
	public function delete_mapped_products()
	{
		$com = new change_pass_model();
		$comment="";
		$employee_id = $_POST['employee_id'];
		$did = $_SESSION['user_data']->dealer_id;
		$selected = $_POST['selected'];
		//print_r($selected);
		$delete1=0;
		$delete2=0;
		foreach($selected as $product_id)
		{
			$sql_str = "select count(c.customer_id) cm from customer c 
			INNER JOIN eb_customer_products cp ON cp.customer_id = c.customer_id where cp.product_id =$product_id AND c.reseller_id=$employee_id AND cp.status=1 AND c.dealer_id=$did";
			$query = $this->db->query($sql_str);
			if($query->row()->cm==0){
				$this->db->query("update eb_products set package_update=1 where dealer_id=$did and product_id = $product_id");
				$sql_digi=$this->db->query("SELECT * FROM eb_reseller_product_mapping WHERE employee_id=$employee_id AND product_id=$product_id AND dealer_id=$did AND digi_downloaded=1");
				if($sql_digi->num_rows()==0){
				$delete_prcentage = "DELETE FROM eb_bp_package_percent WHERE reseller_id=$employee_id AND dealer_id='$did' AND product_id=$product_id";
				$delete1 = $this->db->query($delete_prcentage);
				
				$delete_mapping = "DELETE FROM eb_reseller_product_mapping WHERE employee_id=$employee_id AND dealer_id='$did' AND product_id=$product_id";
				$delete2 = $this->db->query($delete_mapping);
				}
			}
			//echo "<br/>";
			//$query = $this->db->query($sql_str);
			//$res = $query->row();	
		}
		if($delete1 && $delete2)
		{
			// LOGS
				$sql_str = "SELECT employee_id,username, concat(first_name,' ',last_name) AS Name FROM employee WHERE employee_id=$employee_id AND dealer_id=".$_SESSION['user_data']->dealer_id; 
				$query = $this->db->query($sql_str);
				//echo $this->db->last_query();
				//die();
				if($query && $query->num_rows()>0)
				{
					$name = $query->row()->Name;
					$empId = $query->row()->employee_id;
					$userName = $query->row()->username;
					$comment = "User Name:".$_SESSION['user_data']->employee->username." ".$_SESSION['user_data']->first_name." ".$_SESSION['user_data']->last_name." has Un-Mapped the Product(s) For - User Name:".$userName.", Name: $name";
				}
				$com->updateLog($comment);
			// END LOGS
			
			echo 1;
		}
		else 
		{
			echo 0;
		}
	}
	/***** delete_mapped_products *****/
	
	
	/***** check_employee_customers (checking Weather The User Is Having Customers, STB, VC Numbers, LCO due amount AND Customer Due amount) *****/
	public function check_employee_customers()
	{
		$com = new change_pass_model();
        $lco_payment = new PaymentsModel(); 
		$reseller_cond = ""; $join="";
		$employee_id = $_POST['employee_id'];
		$users_type = $_POST['users_type'];
		$dealer_id = $_SESSION['user_data']->dealer_id;
		
		if($users_type=='RESELLER'){
			$reseller_cond .=" AND e.employee_id=$employee_id";
		}else if($users_type=='SUBDISTRIBUTOR'){
			$reseller_cond .=" AND (e.employee_id=$employee_id OR e.parent_id=$employee_id)";
			
		}elseif($users_type=='DISTRIBUTOR') { 
		
			$join = "LEFT JOIN employee d ON d.employee_id = e.parent_id ";
			$reseller_cond .= " AND (e.employee_id=$employee_id OR e.parent_id=$employee_id  
			OR d.parent_id =$employee_id  ) ";
		}
		
		//check stock from eb_stock location
		$sql_str = "select count(es.stock_id) stock from eb_stock es
		INNER JOIN eb_stock_location esl ON esl.location_id=es.stock_location
		INNER JOIN employee e ON e.employee_id=esl.reseller_id
		$join
		where e.dealer_id=$dealer_id $reseller_cond";
		$query = $this->db->query($sql_str);
		//echo $this->db->last_query();
		$res1 = $query->row();
		//check vc's 
		$sql_str1 = "select count(av.all_vc_id) vcs from eb_all_vcs av
		INNER JOIN employee e ON e.employee_id=av.reseller_id 
		$join
		where e.dealer_id=$dealer_id AND av.is_trash=0 $reseller_cond";
		$query = $this->db->query($sql_str1);
		//echo $this->db->last_query();
		$res2 = $query->row();
		if($res1->stock >0 || $res2->vcs > 0 )
		{
			echo 1;
		}else {
			   $lco_join="";
			   $lco_paid_join="";
			if($users_type=='RESELLER' || $users_type=='DISTRIBUTOR' || $users_type=='SUBDISTRIBUTOR'){
            if($users_type=='DISTRIBUTOR'){
                $lco_join .="INNER JOIN employee e ON e.employee_id = c.reseller_id";
				$lco_join .="INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
				$lco_join .="LEFT JOIN employee e2 ON e2.employee_id = e1.parent_id";
				$employee_cond = "(e1.employee_id=$employee_id OR e2.employee_id=$employee_id)";
				$lco_paid_join .="INNER JOIN employee e ON e.employee_id = apd.employee_id";
				$lco_paid_join .="INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
				$lco_paid_join .="LEFT JOIN employee e2 ON e2.employee_id = e1.parent_id";
				$lco_paid_cond ="(e1.employee_id=$employee_id OR e2.employee_id=$employee_id)";
            }
            if($users_type=='SUBDISTRIBUTOR'){
                $lco_join .="INNER JOIN employee e ON e.employee_id = c.reseller_id";
				$employee_cond = "e.parent_id=$employee_id";
				$lco_paid_join .="INNER JOIN employee e ON e.employee_id = apd.employee_id";
				$lco_paid_cond ="e.parent_id=$employee_id";
            }
            if($users_type=='RESELLER'){
               $employee_cond = "c.reseller_id=$employee_id";
               $lco_paid_cond = "apd.employee_id=$employee_id";
            }
			//Customer BILL AMOUNT, LCO BILL AMOUNT calculation
			$due = $this->db->query("select COALESCE(sum(total_amount), 0) customer_bill, COALESCE(sum(mso_share), 0) lco_bill from acc_billing ab
			INNER JOIN customer c ON c.customer_id = ab.customer_id
			$lco_join
			where $employee_cond");
			//Customer PAID AMOUNT calculation
			$cust_paid = $this->db->query("select COALESCE(sum(paid_amount), 0) customer_paid from acc_payment_details apd
			INNER JOIN customer c ON c.customer_id = apd.customer_id
			$lco_join
			where $employee_cond");
			//echo $this->db->last_query();
			//LCO PAID AMOUNT calculation
			$lco_paid = $this->db->query("select COALESCE(sum(amount), 0) lco_paid from acc_lco_payments apd
			$lco_paid_join
			where $lco_paid_cond");
			//echo $this->db->last_query();
			$due_amount = $due->row();
			if($cust_paid && $cust_paid->num_rows()>0){
				$cust_paid_amount = $cust_paid->row()->customer_paid;
			} else{
				$cust_paid_amount = 0;
			}
			if($lco_paid && $lco_paid->num_rows()>0){
				$lco_paid_amount = $lco_paid->row()->lco_paid;
			} else{
				$lco_paid_amount = 0;
			}
			$customer_bill_amount=$due_amount->customer_bill;
			$lco_bill_amount=$due_amount->lco_bill;
			$customer_due_amount = $customer_bill_amount-$cust_paid_amount;
			$lco_due_amount = $lco_bill_amount-$lco_paid_amount;
			//echo $customer_due_amount;
			//echo $lco_due_amount;
			if($customer_due_amount>0 || $lco_due_amount>0){
				echo 2;
			} else{
				echo 3;
			}
		   } else {
				echo 3;
		   }
		}
		
	}
	
	public function getResellerGroup()
	{
	$reseller_id=$_POST['reseller_id'];
        $employeegroup=isset($_POST['employeegroup'])?$_POST['employeegroup']:'';
	//
	$did = $_SESSION['user_data']->dealer_id;
	
	$sqlstr = "SELECT g.group_id, g.group_name, g.is_default
	FROM groups g  
	WHERE g.parent_id = $reseller_id AND g.dealer_id =$did AND g.status=1 order by g.group_name";
	
	/*$sqlstr = "SELECT g.group_id, g.group_name, g.is_default
	FROM employee_group eg
	INNER JOIN  groups g  ON eg.group_id=g.group_id and g.parent_id = $reseller_id
	WHERE eg.employee_id =$reseller_id AND g.dealer_id =$did order by group_name";*/
	
	$query = $this->db->query($sqlstr);

	$group='<select name="employeegroup" id="group" class="txtBox-new">';
	if($query)
	{
		if(count($query->result())==1)
		{
			$groups = $query->result();
			$group .= "<option value='".$groups[0]->group_id."' SELECTED='selected'> ".$groups[0]->group_name."</option>";
		}
		else
		{
			$group .='<option value="-1">--Select Group--</option>';
			foreach($query->result() as $row)
			{
					$group .= "<option value='".$row->group_id."' ". (($row->is_default==1 || $employeegroup==$row->group_id)?"SELECTED='selected'":"")." > ".$row->group_name."</option>";
			}
		}
		
	}
	$group .='</select>';
		echo $group;
	
	}
		
	/***** check_employee_customers (checking Weather The User Is Having Customers Or Not) *****/
	//method to check whether that product is alacarte or not
	
	public function checkIsAlaCarte($product_id=0)
	{
		$productId = ($this->input->post('productId'))?$this->input->post('productId'):$product_id;
		

		$sql_str = "SELECT alacarte FROM eb_products WHERE product_id=$productId AND  dealer_id=".$_SESSION['user_data']->dealer_id;

		$query = $this->db->query($sql_str);
		if($query && $query->num_rows()>0)
		{
			$val = $query->row()->alacarte;
		}
		else
		{
			$val = 0;
		}
		if($this->input->post('productId')){
			echo $val;
		}
		else{
			return $val;
		}
		
	}
	
	//added by nikhilesh for is_base_package checking on 12/09/2013
	public function checkIsBasePackage($product_id=0,$other_product_ids=''){
			
		$data= 0; 
		$base_pack_pid= isset($_POST['base_pack_pid'])?$_POST['base_pack_pid']:$product_id; 
		$product = isset($_POST['other_product_ids'])?rtrim($_POST['other_product_ids'],','):$other_product_ids;   
		
		//echo $product;
        $dealer = $_SESSION['user_data']->dealer_id; 
        $query=$this->db->query("SELECT is_base_package FROM eb_products WHERE product_id=$base_pack_pid 
        AND dealer_id=$dealer"); 
         
        if($query && $query->num_rows()>0){             
            $is_base=$query->row()->is_base_package;     
            if($is_base>0){ 
                    $check_sql=$this->db->query("SELECT COUNT(product_id) get FROM eb_products WHERE is_base_package= $is_base AND product_id IN($product) AND product_id != $base_pack_pid AND dealer_id=$dealer");
					
                    if($check_sql && $check_sql->num_rows()>0 ){ 
                        $data= $check_sql->row()->get; 

                    }else{ 
                        $data= 0; 
                    }
            }else{ 
                $data= 0; 
            }
        }
		if(isset($_POST['base_pack_pid'])){
			echo $data;
		}
		else{
			return $data;
		}
	
	}
	
	//added by nikhilesh for is_base_package for edit checking on 13/09/2013
	public function checkIsBasePackageForEdit($product_id=0,$stock_id=0){
			
		
		$productId = ($this->input->post('productId'))?$this->input->post('productId'):$product_id;
		$stock_id = ($this->input->post('stockId'))?$this->input->post('stockId'):$stock_id;		
		
		$dealer = $_SESSION['user_data']->dealer_id; 
        $sql=$this->db->query("SELECT is_base_package FROM eb_products WHERE product_id=$productId 
        AND dealer_id=$dealer"); 
         
        if($sql && $sql->num_rows()>0){
			
			$bp =  $sql->row()->is_base_package; 
			if($bp==1)
			{ 
			    $sql_str="SELECT count(s.stock_id) as get FROM eb_stock_cas_services s force index (eb_stock_cas_services_sid)
				INNER JOIN eb_products p ON p.product_id=s.product_id
				WHERE s.stock_id=$stock_id  AND p.is_base_package=1 AND s.deactivation_date IS NULL";
				
				$query = $this->db->query($sql_str);
				if($query && $query->num_rows()>0){
					$val =  $query->row()->get; 
					
				}else{
					$val = 0;
					
				}
			}
			else $val=0;
		}
		else $val=0;
		if($this->input->post('productId')){
			echo $val;
		}else{
			return $val;
		}
	
		
	}
	
	//method to check whether that product is onetime or not
	public function checkIsOnetime($stock_id=0,$productId=0,$cust_id=0)
	{
		
		$productId = ($this->input->post('productId'))?$this->input->post('productId'):$productId;
		$stock_id = ($this->input->post('stock_id'))?$this->input->post('stock_id'):$stock_id;
		$cust_id = ($this->input->post('cust_id'))?$this->input->post('cust_id'):$cust_id;
		$used_days = '';
		//geeting used days for one time product
		
		
		$sql_str = "SELECT pricing_structure_type, validity_days,validity_days_type_id FROM eb_products WHERE product_id=$productId AND  dealer_id=".$_SESSION['user_data']->dealer_id;
		//echo $sql_str;die;
		$query = $this->db->query($sql_str);
		$val1=1;$val2 = 2;
		if($query && $query->num_rows()>0)
		{	
			$val1 = $query->row()->pricing_structure_type;
			if($cust_id!=0){
				$customer = new CustomersModel();
				$used_days = $customer->getUsedDays($stock_id,$productId,$cust_id);
				if($used_days!=''){
					$val = date('Y-m-d',strtotime($used_days)); 
					if($val1==1){					
						$val2 = 3;//used to disable the quantity and end date
					}
					
				}else{
					$cust_id=0;
				}
			}
			
			if($cust_id==0){				
				// For One time package START
				if($val1==1 || $val1==2) { //if one time or recurring
					//$val='';
					$validity_days_type_id=$query->row()->validity_days_type_id;
					$validity_days = $query->row()->validity_days;
					if($validity_days>0) {
					//echo $validity_days_type_id."haiiiii";die;
						if($validity_days_type_id==1) //Months calculation
						{ 
							$installation_date = date('Y-m-d');
							if(date('t',strtotime($installation_date))==date('d',strtotime($installation_date)) || date('d',strtotime($installation_date)) == 30){ 
								$enddate 				= null;
								$curr_yr 				= date('Y',strtotime($installation_date));
								$curr_month 			= date('m',strtotime($installation_date));
								$month					= date('m', strtotime($installation_date .'+'.$validity_days.' month'));
								$ending_month 			= ($curr_month + $validity_days) > 12 ? $month : $curr_month + $validity_days ;
								
								//$ending_yr 				= floor($curr_yr + ($curr_month + $validity_days) / 12);
								 //ceil value condtion value edited by pardhu
                                $ceil_val=($curr_month + $validity_days) / 12;
                                if($ceil_val==1){
                                    $ceil_val=0;
                                 } 
                                $ending_yr = floor($curr_yr + $ceil_val);
								$ending_dt 				= date('t',strtotime($ending_yr.'-'.$ending_month.'-01'));
								
								if($ending_dt != 28 && $ending_dt != 29){
									$ending_dt = $ending_dt -01;
								}
								if(date('d',strtotime($installation_date)) == 30 && $ending_dt == 30){
									$ending_dt = $ending_dt -01;
								}
								//echo '<br/>»» End Date: '.$enddate 	= $ending_yr.'-'.$ending_month.'-'.$ending_dt ;
								$val = $enddate 	= $ending_yr.'-'.$ending_month.'-'.$ending_dt ;
								
							}else{ 
								$val = Date('Y-m-d', strtotime("+".$validity_days ."months-1day"));
							}
							
							
						}elseif($validity_days_type_id==2) //Years calculation
						{
							$val = Date('Y-m-d', strtotime("+".$validity_days ."years-1day"));
							
						}elseif($validity_days_type_id==3) //Days calculation
						{
							$val = Date('Y-m-d', strtotime("+".$validity_days ."days-1day"));
							
						}
						//$val =  Date('Y-m-d', strtotime("+".$validity_days ."days"));
						if($val1==1)//if one time product
						{
							$val2 = 1;
						}
					}else{
						$val = date('Y-m-d',strtotime(date("Y-m-d", strtotime(date('Y-m-d'))) . " +1 year -1 day"));
						$val2 = 2;
					}
				}// For One time package START
				//else { // For recurring package START
					//$val =  date('Y-m-d',strtotime(date("Y-m-d", strtotime(date('Y-m-d'))) . " +1 year -1 day"));
					//$val2 = 2;
				//} // For recurring package END
			}
		}
		else
		{
			$val = date('Y-m-d',strtotime(date("Y-m-d", strtotime(date('Y-m-d'))) . " +1 year -1 day"));
			$val2 = 2;
		}
		//echo $val;die;
		if($this->input->post('productId')){
			echo 'XXX'.$val2.'XXX'.$val;
		}else{
			return 'XXX'.$val2.'XXX'.$val;
		}
		
	}
	//method to check the channel exist for already running services
	public function checkIsAlaCarteProductChannelExist()
	{
		$cid = ($this->input->post('cid'))?$this->input->post('cid'):0;
		$productId = ($this->input->post('productId'))?$this->input->post('productId'):0;
					$sql_str ="SELECT COUNT( channel_id ) alaChannelCount
					FROM eb_product_channels
					WHERE product_id
					IN (

					SELECT product_id
					FROM eb_customer_products
					WHERE customer_id =$cid
					AND STATUS =1
					)
					AND channel_id = ( 
					SELECT channel_id
					FROM eb_product_channels
					WHERE product_id =$productId )";
					//echo $sql_str;
		$query = $this->db->query($sql_str);
		if($query && $query->num_rows()>0)
		{
			$val = $query->row()->alaChannelCount;
		}
		else
		{
			$val = NULL;
		}
		echo $val;
	}
	
	
	public function get_customerDetails()
	{
		$customer_id = $_POST['customer_id'];
		$serial_number = $_POST['serial_number'];
		$mac_id = $_POST['mac_id'];
		$cond = ""; 
		
		if($customer_id!=0 && $customer_id!='') $cond .= "AND (c.customer_id = '$customer_id' OR c.account_number = '$customer_id')";
		if($serial_number!=0 && $serial_number!='') $cond .= "AND s.serial_number = '$serial_number'";
		if($mac_id!=0 && $mac_id!='')  $cond .= " AND (REPLACE(s.mac_address,':','') LIKE  '%".str_replace (':','',$mac_id)."%' OR s.vc_number LIKE '%".str_replace (':','',$mac_id)."%')"; 
                
		$did = $_SESSION['user_data']->dealer_id;	
		$reseller_id = $_SESSION['user_data']->employee_id;
		$val = "";
                if($cond!=""){
                        $query =$this->db->query("select  CONCAT(c.first_name, '', c.last_name) AS customer_name,s.serial_number, s.box_number, s.stock_id,s.vc_number, s.mac_address,s.make,
                                            cd.customer_device_id, c.customer_id,
                                            CASE bs.use_mac WHEN '1' THEN s.mac_address ELSE s.vc_number END AS mac_vc_number,
                                            CONCAT(e.first_name, '', e.last_name) AS reseller_name,
                                            sl.location_name, bs.cas_server_type,bs.backend_setup_id,sl.reseller_id
                                            from customer c 
                                            INNER JOIN customer_device cd ON cd.customer_id = c.customer_id AND cd.dealer_id= $did
                                            INNER JOIN eb_stock s ON s.serial_number = cd. box_number
                                            INNER JOIN backend_setups bs ON bs.backend_setup_id = s. backend_setup_id
                                            INNER JOIN eb_stock_location sl ON sl.location_id = s.stock_location
                                            INNER JOIN employee e ON e.employee_id = sl. reseller_id
                                            WHERE  c.status=1 $cond AND c.dealer_id = $did LIMIT 1");	
                                        
                        if($query && $query->num_rows()>0)
                        {
                                $row = $query->row();
                                $backend_id = $row->backend_setup_id;
                                $reseller_id = $row->reseller_id;


                                $qry = $this->db->query("SELECT s.serial_number,s.stock_id,s.vc_number,
                                CASE bs.use_mac WHEN '1' THEN s.mac_address ELSE s.vc_number END AS mac_vc_number
                                FROM eb_stock s 
                                INNER JOIN backend_setups bs ON bs.backend_setup_id = s. backend_setup_id
                                INNER JOIN eb_stock_location sl ON  sl.location_id = s.stock_location
                                WHERE bs.backend_setup_id=$backend_id AND sl.reseller_id=$reseller_id AND s.dealer_id=$did AND s.defective_stock=0 AND s.serial_number NOT IN (select box_number from customer_device where dealer_id=$did)");
								
								
								//$val .="<tr><td colspan='4'><hr align='left' width='100%'/></td></tr>";
								$val .= "<tr class='remove'><td> Customer Name : </td><td>".$row->customer_name ."</td></tr>";
                                $val .= "<tr class='remove'><td> Serial Number (".$_SESSION['mac_vc_column'].") : </td><td>".$row->serial_number ." (".$row->mac_vc_number.")</td></tr>";
                                $val .= "<tr class='remove'><td> Lco Location (Lco Name) : </td><td>".$row->location_name ." (".$row->reseller_name.")</td></tr>";
                                $val .= "<tr class='remove'><td> Server Type : </td><td>".$row->cas_server_type ."</td></tr>";


                                $val .='<tr class="remove"><td>Replace With : </td><td><select name="stock_id" id="stock_id" >';

                                if($qry && $qry->num_rows()>0)
                                {	
                                    $val .= "<option value='-1'>Select STB</option>";
                                                                foreach($qry->result() as $row1)
                                    {
                                            $val .= "<option value='".$row1->stock_id."'> ".$row1->serial_number." (".$row1->mac_vc_number.")</option>";
                                    }	
                                }
                                $val .='</select></td></tr>';
                                $val .='<tr class="remove"><td></td><td class="tx">';
                                $val .='<input type="hidden" name="old_stock_id" id="old_stock_id" value="'.$row->stock_id.'"/>';
                                $val .='<input type="hidden" name="customer_id"  value="'.$row->customer_id.'"/>';
                                $val .='<input type="hidden" name="device_id" id="device_id" value="'.$row->customer_device_id.'"/>';
                                $val .='<input type="hidden" name="box_number" id="box_number" value="'.$row->box_number.'"/>';
                                $val .='<input type="hidden" name="macid" id="macid" value="'.$row->mac_address.'"/>';
                                $val .='<input type="hidden" name="vcnumber" id="vcnumber" value="'.$row->vc_number.'"/>';
                                $val .='<input type="hidden" name="location_name" id="location_name" value="'.$row->location_name.'"/>';
                                $val .='<input type="hidden" name="besid" id="besid" value="'.$row->backend_setup_id.'"/>';
                                $val .='<input type="hidden" name="make" id="make" value="'.$row->make.'"/>';
                                $val .='<input type="submit" name="replace_stb" id="replace_stb" class="rs" value="Replace"/>&nbsp;&nbsp;&nbsp;<input type="submit" name="clearbtn" id="clearbtn" value="Clear"/></td></tr>';
								

                        }
                        else{
                        
                                $val .= "<tr class='remove'><td colspan='2' align='center' style='color:red;'>No Records</td></tr>";

                        }
                
                }
                else{
                        
                        $val .= "<tr class='remove'><td colspan='2' align='center' style='color:red;'>No Records</td></tr>";
                        
                }
                echo $val;
	
	}
	//created by rakesh for getting the source location name during stock transfer
	public function getSourceLocations()
	{
		$emp_model = new EmployeeModel();
		 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
		 $orderby= "e.dist_subdist_lcocode";
		 }
		 else {
		 $orderby= "el.location_name";
		 }
		$sourceUser = ($this->input->post('sourceId'))?$this->input->post('sourceId'):0; 
		$is_selected = $this->input->post('is_selected')?$this->input->post('is_selected'):0;
		$sql_str = ''; $r_cond = "";
		$dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $eid = $_SESSION['user_data']->employee_id;//get the employee id
         if($_SESSION['user_data']->users_type=='RESELLER' || 
                 $_SESSION['user_data']->users_type=='DISTRIBUTOR' || 
                 $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' ||
                  $_SESSION['user_data']->employee_parent_type=='RESELLER' || 
                         $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR' || 
                         $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR'  ){
             if($_SESSION['user_data']->employee_parent_type!=''){
                 $eid = $_SESSION['user_data']->employee_parent_id;
             }else{
                  $eid = $_SESSION['user_data']->employee_id;
             }
			 if($this->input->post('backward_transfer')==1){
				$r_cond =" AND (e.parent_id IN(select employee_id from employee where parent_id=$eid) OR e.parent_id=$eid)";
			 }else{
				 $r_cond =" AND el.reseller_id=$eid";
			}
		}
		$query = '';
		
		//code change for pull stock
		$pullStock = $this->input->post('pullStock')?$this->input->post('pullStock'):0;
		$data['employee_codes_array'] = $emp_model->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
		
         if($pullStock == 1){
			$sourceUser = 5;
		}elseif($pullStock ==2){
			$sourceUser = 6;
		}elseif($pullStock ==3){
			$sourceUser = 7;
		}
		
		switch($sourceUser)
		{
			//for getting mso location i.e, primary location
			case 1:

				$sql_str = "SELECT el.location_id,el.location_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.location_name,e.business_name,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.dealer_id=e.dealer_id where el.dealer_id=? AND el.reseller_id = ? AND e.users_type=? ORDER BY $orderby";
                $query = $this->db->query($sql_str,array($dealerId,0,'DEALER'));
				break;
			//for getting distributor locations
			case 2:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? $r_cond ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'DISTRIBUTOR'));
				break;
			//for getting sub distributor locations
			case 3:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? $r_cond ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR'));			
				break;
			//for getting lco locations
			case 4:
                                
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? $r_cond ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER'));				
				break;	
			//for pull stock if distributor
			case 5:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.employee_id=(SELECT case when COALESCE( e2.employee_id, 0 )!=0 then COALESCE( e2.employee_id, 0 ) else COALESCE( e1.employee_id, 0 ) end as distributor 
				FROM employee e
				LEFT JOIN employee e1 ON e.parent_id = e1.employee_id
				LEFT JOIN employee e2 ON e1.parent_id = e2.employee_id
				WHERE e.employee_id=? and e.dealer_id =? AND e.status=1 AND e.parent_status=1) ";
				$query = $this->db->query($sql_str,array($dealerId,0,1,$eid,$dealerId,'DISTRIBUTOR'));
				break;
			//for pull stock if sub distributor
			case 6:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.employee_id=(select parent_id from employee WHERE employee_id=? and dealer_id=?) AND e.users_type=?";
				$query = $this->db->query($sql_str,array($dealerId,0,1,$eid,$dealerId,'SUBDISTRIBUTOR'));
				break;
			//for pull stock if MSO
			case 7:
			    $sql_str = "SELECT el.location_id,el.location_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.location_name,e.business_name,e.parent_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.dealer_id=e.dealer_id where el.dealer_id=? AND el.reseller_id = ? AND e.users_type=? ORDER BY $orderby";
                $query = $this->db->query($sql_str,array($dealerId,0,'DEALER'));
			
				break;
				
		}
		
		$val = '';
		$val .= "<option value='-1'>Select</option>";
		//Getting popupcode @yamini
		//echo "<pre>";print_r($query->result());exit;
		if($query && $query->num_rows()>0)
		{
				
				/*if($is_selected ==1 && count($query->result()) ==1){ //commented as it should be in foreach and condition is also wrong
					$selected = 'selected';
				}*/
			foreach($query->result() as $res)
			{
				$selected='';
				if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
						if($res->users_type=='DEALER'){
								$title = "";
								$name = " ";
								$value = $res->location_name;
						} else{
								 $value=$res->dist_subdist_lcocode;
								 $name = "(".trim($res->location_name).")";
								 $title = trim($res->empname);
						 }
				 }  
			    else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
			    { 	
					$value=$res->location_name;
					$title = 	$res->dist_subdist_lcocode;
					 $name = "(".trim($res->empname).")";
			    }
				else {
					$value=$res->location_name;
					$title = trim($res->empname);
					$name = "(".$res->dist_subdist_lcocode.")";
				}

				if($is_selected > 0 && $is_selected ==$res->location_id){
                    $selected = 'selected';
                }
				if($res->users_type == "DEALER"){
					 $val .= "<option  value='" . $res->location_id . "' employeeid='" . $res->employee_id . "'  parent_id='" . $res->parent_id ."' resellerId='" . $res->reseller_id . "' " . $selected . ">" . $value . "</option>";
				}else{
					 $val .= "<option title='" . $title . "' value='" . $res->location_id . "' employeeid='" . $res->employee_id . "'  parent_id='" . $res->parent_id ."' resellerId='" . $res->reseller_id . "' " . $selected . ">" . $value . $name . "</option>";
				}
				//$val .= "<option value='".$res->location_id."' employeeid='".$res->employee_id."'  parent_id='".$res->parent_id."'>".$res->business_name."".$name."</option>";
             
			}
		}
	
		echo $val;
	}
	//created by rakesh for getting the destination location name during stock transfer
	public function getDestinationLocations()
	{
		$pullstock="";
		$pullStockCondition="";
		$emp_model = new EmployeeModel();
		$destinationUser = ($this->input->post('destinationId'))?$this->input->post('destinationId'):0;
		$employeeId = ($this->input->post('employeeId'))?$this->input->post('employeeId'):0;
		$parentId = $this->input->post('parentId')?$this->input->post('parentId'):0;
		$sql_str = '';
		$dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$emp_id = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
		$is_selected = $this->input->post('is_selected') ? $this->input->post('is_selected') : 0;
		$query = '';
		$condition = '';
		 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
		 $orderby= "e.dist_subdist_lcocode";
		 }
		 else {
		 $orderby= "el.location_name";
		 }
                 if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
		
                        $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                        $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		$data['employee_codes_array'] = $emp_model->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
		if($employeeId != 0 && $destinationUser != 6)
		{
			//$condition = " ";
			$condition = " AND e.parent_id=$employeeId ";
		}
		//code change for pull stock
		$pullStock = $this->input->post('pullStock')?1:0;
		if($pullStock == 1)
		{
			$destinationUser = 11;
		}
		
		switch($destinationUser)
		{
			//for getting mso location i.e, primary location
			case 1:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.dealer_id=e.dealer_id where el.dealer_id=? AND el.reseller_id = ? AND e.users_type=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,'DEALER'));
				break;
			//for getting distributor locations
			case 2:
			$transfer_stbs_in_same_hierarchy = (isset($_SESSION['dealer_setting']->TRANSFER_STB_IN_SAME_HIERARCHY)) ? $_SESSION['dealer_setting']->TRANSFER_STB_IN_SAME_HIERARCHY : 0;
                if($transfer_stbs_in_same_hierarchy==0){
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'DISTRIBUTOR'));
				}
				else{
					$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND e.employee_id!=? ORDER BY $orderby";
                	$query = $this->db->query($sql_str, array($dealerId, 0, 1, 'DISTRIBUTOR', $employeeId));
				}
				break;
			//for getting sub distributor locations
			case 3:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? $condition ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR'));			
				break;
			//for getting lco locations
			case 4:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? $condition $pullStockCondition ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER'));	break;	
			//for getting distributor locations when sub distributor transferring to distributor
			case 5:
			
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND (e.employee_id = (select parent_id from employee e1 where e1.employee_id=?) or e.employee_id = (select parent_id from employee e1 where e1.employee_id=?)) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'DISTRIBUTOR',$employeeId,$parentId));
				break;
			//for getting reseller locations when reseller transferring to reseller
			case 6:
				if ($_SESSION['user_data']->users_type == 'RESELLER' || $_SESSION['user_data']->employee_parent_type == 'RESELLER') {
                    if ($_SESSION['user_data']->employee_parent_type == 'RESELLER') {
                        $details = $this->EmployeeModel->getEmployeeDetails($emp_id);
                        $emp_id = $details->employee_parent_id;
                    }
                    $query = $this->db->query("SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el 
					INNER JOIN employee e ON el.reseller_id=e.employee_id 
					WHERE FIND_IN_SET(e.employee_id, (SELECT reseller_ids FROM eb_intra_lco_mapping WHERE FIND_IN_SET($emp_id ,reseller_ids)>0)) AND el.dealer_id=$dealerId  AND e.users_type='RESELLER' AND e.employee_id !=$emp_id ORDER BY $orderby");
                    break;
                } else {
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=? AND e.users_type=? AND e.status=? AND e.parent_status=1 AND e.parent_id=(SELECT parent_id FROM employee WHERE employee_id=? AND dealer_id=?) AND e.employee_id !=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,'RESELLER',1,$employeeId,$dealerId,$employeeId));
				break;	
				}
			//for getting mso locations when sub distributor transferring to mso
			case 7:
				/*$sql_str = "SELECT el.location_id,el.location_name,e.employee_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=? AND e.users_type=? AND e.status=? AND e.parent_id=(SELECT parent_id FROM employee WHERE employee_id=? AND dealer_id=?) AND e.employee_id !=?";
				$query = $this->db->query($sql_str,array($dealerId,0,'DEALER',1,$employeeId,$dealerId,$employeeId));*/
				$query = $this->db->query("SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type, e.employee_id, e.parent_id,el.reseller_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM eb_stock_location el
				LEFT JOIN employee e ON el.reseller_id = e.employee_id
				WHERE el.dealer_id =$dealerId
				AND e.users_type = 'DEALER'
				AND e.status =1 AND e.parent_status=1
				AND e.parent_id =$employeeId
				UNION SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type,  e.employee_id, e.parent_id, el.reseller_id,CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM employee e
				LEFT JOIN eb_stock_location el ON el.dealer_id = e.dealer_id
				AND el.reseller_id =0
				WHERE e.users_type = 'DEALER'
				AND e.dealer_id =$dealerId") ;
				break;		
			//for getting mso locations when lco transferring to mso
			case 8:
				/*$sql_str = "SELECT el.location_id,el.location_name,e.employee_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=? AND e.users_type=? AND e.status=? AND e.parent_id=($parentId) AND e.employee_id =?";
				$query = $this->db->query($sql_str,array($dealerId,0,'DEALER',1,$employeeId,$dealerId,$employeeId));*/
				$query = $this->db->query("SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type, e.employee_id, e.parent_id, el.reseller_id,CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM eb_stock_location el
				LEFT JOIN employee e ON el.reseller_id = e.employee_id
				WHERE el.dealer_id =$dealerId
				AND e.users_type = 'DEALER'
				AND e.status =1 AND e.parent_status=1
				AND e.parent_id =$employeeId
				UNION SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type,  e.employee_id, e.parent_id,el.reseller_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM employee e
				LEFT JOIN eb_stock_location el ON el.dealer_id = e.dealer_id
				AND el.reseller_id =0
				WHERE e.users_type = 'DEALER'
				AND e.dealer_id =$dealerId") ;
				break;
			case 9:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND e.employee_id=(SELECT parent_id FROM employee WHERE employee_id=? AND dealer_id=?) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR',$employeeId,$dealerId));
				break;
			case 10:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=?  ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER'));
				break;	
			//for pull stock
			case 11:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND e.employee_id=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER',$int_employee_id));	
				break;
				//for getting lco location when source is distributor implemented by GOPI
			case 12:
				$sql_str="SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.dist_subdist_lcocode,e.users_type,e.employee_id,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname 
				from eb_stock_location el 
				INNER JOIN employee e ON el.reseller_id=e.employee_id 
				where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND (e.parent_id IN(select employee_id from employee where parent_id=?) OR e.parent_id=?) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER',$employeeId,$employeeId));	
				break;
			//for getting subdistributor locations when sub distributor transferring to sub distributor by hemalatha 8-10-2018
            case 13:

                $sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,el.reseller_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.parent_status=1 AND e.users_type=? AND (e.parent_id = (select parent_id from employee e1 where e1.employee_id=?)) AND e.employee_id!='$employeeId' ORDER BY $orderby";
                $query = $this->db->query($sql_str, array($dealerId, 0, 1, 'SUBDISTRIBUTOR', $employeeId, $parentId));
                break;	

		}
		//echo $this->db->last_query();
		$val = '';
		$val .= "<option value='-1'>Select</option>";
		if($query && $query->num_rows()>0)
		{
			foreach($query->result() as $res)
			{
			    $selected = '';    						  
				if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
						if($res->users_type=='DEALER'){
								$title = "";
								$name = " ";
								$value = $res->location_name;
								} else {
						 $value=$res->dist_subdist_lcocode;
						 $name = "(".trim($res->location_name).")";
						 $title = trim($res->empname);
						 }
				 }  
			    else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
			    { 	
					$value=$res->location_name;
					$title = 	$res->dist_subdist_lcocode;
					 $name = "(".trim($res->empname).")";
			    }
				else {
					$value=$res->location_name;
					$title = trim($res->empname);
					$name = "(".$res->dist_subdist_lcocode.")";
				}

				if($is_selected > 0 && $is_selected ==$res->location_id){
                    $selected = 'selected';
                }
				if($res->users_type == "DEALER"){
					$val .= "<option  value='" . $res->location_id . "' employeeid='" . $res->employee_id . "'  parentid='" . $res->parent_id ."' resellerid='" . $res->reseller_id . "'  " . $selected . ">" . $value . "</option>";
				}else{
					$val .= "<option title='" . $title . "' value='" . $res->location_id . "' employeeid='" . $res->employee_id . "'  parentid='" . $res->parent_id ."' resellerid='" . $res->reseller_id . "' " . $selected . " >" . $value . ' ' . $name . "</option>";
				}
			
				//$val .= "<option title='".$title."' value='".$res->location_id."' employeeid='".$res->employee_id."'  parentid='".$res->parent_id."'>".$res->business_name."".$name."</option>";

			}
		}
		
		echo $val;
	}

	 public function getallBroadcasterProducts(){
			  $bc_id = $_POST['bc_id'];
			  $options = "<option value='-1'>All</option>";
			  $sql = "select DISTINCT p.pname, p.product_id,bs.cas_server_type from eb_products p
                                            INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id
					  INNER JOIN eb_product_channels pc ON p.product_id=pc.product_id
					  INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=pc.channel_id
					  WHERE bc.broadcaster_id='$bc_id'";
			  $query = $this->db->query($sql);
			  if($query && $query->num_rows()>0){
				  $res = $query->result();
				  
                    foreach($res as $row){
                        //$select = ($_SESSION['packages'] && $_SESSION['packages'] == $row->product_id )?'SELECTED':'';
                        $options .= "<option value='".$row->product_id."'>".$row->pname.'('.$row->cas_server_type.')'."</option>";
                    }
			  }
			  echo $options;
		  }

	/***** Task No : 190  ADDING LCO FILTER IN MESSAGING *****/
	
	public function check_lco_groups()
	{
		$did = $_SESSION['user_data']->dealer_id;
		$cond=''; $join = "";
		$val = '';
		$binding = array($did);
		$location_id = $_POST['location_id'];
		$loc_id='';
		if (is_array($location_id) && count($location_id) > 0) 
		{	
			$loc_id = implode($location_id,',');
			/*$join = "INNER JOIN employee_group eg ON eg.group_id = g.group_id
					 INNER JOIN eb_stock_location sl ON sl.reseller_id = eg.employee_id";
			$cond = " sl.location_id IN (?)  AND";
			$binding = array($loc_id, $did);*/
			$select_option = "<option value='lco_unpaid'>Unpaid Customers</option>";
		}else{
			$select_option = "";
            $loc_id = $location_id;
		}
		//getting groups when distributor login
		$int_employee_id = $_SESSION['user_data']->employee_id;
		$str_join_condition = "";
		//get the stock when distributor/subdistributor/lco login 
		$str_condition = "";
		if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || 
                        $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || 
                        $_SESSION['user_data']->users_type=='RESELLER' ||
                        $_SESSION['user_data']->employee_parent_type=='RESELLER' || 
                         $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR' || 
                         $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR') {
                        if( $_SESSION['user_data']->employee_parent_type!=''){
                            $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                        }else{
                            $int_employee_id = $_SESSION['user_data']->employee_id;
                        }
			/*$str_join_condition = "INNER JOIN employee_group eg1 ON eg1.group_id = g.group_id
			INNER JOIN employee e ON e.employee_id = eg1.employee_id";*/
			$str_condition = "AND (e.parent_id IN (select employee_id from employee where parent_id=$int_employee_id)
			OR e.employee_id=$int_employee_id OR e.employee_id IN (select employee_id from employee where parent_id=$int_employee_id  ))";
		
		}
		
		if($loc_id > 0){
        $emp_det = $this->employeemodel->getEmployeeInfo($loc_id);
        if(is_object($emp_det)){
            $query = $this->db->query("SELECT g.group_id,g.group_name,e.business_name FROM groups g INNER JOIN employee e ON e.employee_id = g.parent_id WHERE g.parent_id IN ($emp_det->employee_id) ");
            }
        }
		/*$query = $this->db->query("SELECT g.group_id,g.group_name FROM groups g
				$join $str_join_condition
				WHERE $cond g.dealer_id=? $str_condition GROUP BY g.group_id order by g.group_name ",$binding);*/
				
				
		
		$val .= "<select name='group' id='group'>";
        $val .= "<option value='0'>All STBs</option>";
        $val .= "<option value='2'>Active STBs</option>";
        $val .= "<option value='3'>Deactive STBs</option>";
        $val .= "<option value='4'>All Unassigned STBs</option>";
        $val .= "<option value='5'>All Customers</option>";
        $val .= $select_option;
        $val .= "<option value='1'>Individual</option>";
        if (isset($_SESSION['dealer_setting']->USE_CUSTOM_MESSAGING) && $_SESSION['dealer_setting']->USE_CUSTOM_MESSAGING == 1) {
            $val .= "<option value='7'>Custom</option>";
        }
		   
		$val .= "<optgroup label='Child Groups'>";
		if($query && $query->num_rows()>0)
		{	
			foreach($query->result() as $row1)
			{
				$val .="<option value='".$row1->group_id."'>".$row1->group_name."(".$row1->business_name.")</option>";
			}	
		}
		
		$val .= "</select>";
		echo $val;
		
	}
	
	/***** Bulk Pairing - Trail Products *****/
	public function getCasTrailProducts()
	{
		$did = $_SESSION['user_data']->dealer_id;
		$eid = $_SESSION['user_data']->employee_id;
		$sip = $_POST['sip'];
		$val = '';
		$join='';
		if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER')
		{
                     if( $_SESSION['user_data']->employee_parent_type!=''){
                            $eid = $_SESSION['user_data']->employee_parent_id;
                        }else{
                            $eid = $_SESSION['user_data']->employee_id;
                        }
			$join = "INNER JOIN eb_reseller_product_mapping erp ON erp.product_id=ep.product_id AND erp.is_blocked In (0,2) AND erp.employee_id =$eid";
		}
		$query = $this->db->query("select ep.*,bs.cas_server_type,bs.display_name from eb_products ep 
		INNER JOIN backend_setups bs ON ep.backend_setup_id=bs.backend_setup_id
		$join
		where ep.plugin_id=4 and ep.pricing_structure_type=3 AND ep.status=1 AND ep.backend_setup_id=? AND ep.dealer_id=? order by pname",array($sip,$did));
		
		//echo $this->db->last_query();
		
		$val .= "<option value='-1'>-- Select --</option>";
		if($query && $query->num_rows()>0)
		{	
			foreach($query->result() as $row1)
			{
				$val .="<option value='".$row1->product_id."'>".$row1->pname.' ('.$row1->display_name.')'."</option>";
			}	
		}
		echo $val;
    }

	public function customer_name(){
		$customer =$_POST['cus_name'];
		$did = $_SESSION['user_data']->dealer_id;
		$val='';
		if($customer !=''){	
			$query  = $this->db->query("select customer_id,first_name,last_name FROM customer WHERE dealer_id=$did AND ((first_name LIKE '%".$customer."%') OR (last_name LIKE '%".$customer."%')) order by first_name");
			//$query = $this->db->query("select customer_id,first_name,last_name,business_name,account_number FROM customer WHERE dealer_id=? AND ((first_name LIKE ?'%') OR (last_name LIKE ?'%')) order by first_name",array($did,$customer,$customer) );
			//echo $this->db->last_query();
			if($query && $query->num_rows()>0){
			foreach($query->result() as $cus){
				$val .= "<option> ".$cus->first_name." ".$cus->last_name." </option>";
				}
			}
		}	
		echo $val;
	}
	//For  Business name in LIST CUSTOMER Autofill search  
	public function busness_name(){
		$business =$_POST['bus_name'];
		$did = $_SESSION['user_data']->dealer_id;
		$val='';
		if($business !=''){		
			$query  = $this->db->query("select customer_id,business_name FROM customer WHERE dealer_id=$did AND (business_name LIKE '%".$business."%') order by business_name");
			//$query = $this->db->query("select customer_id,first_name,last_name,business_name,account_number FROM customer WHERE dealer_id=? AND ((first_name LIKE ?'%') OR (last_name LIKE ?'%')) order by first_name",array($did,$customer,$customer) );
			//echo $this->db->last_query();
			if($query && $query->num_rows()>0){
				foreach($query->result() as $bus){
					
					$val .= "<option> ".$bus->business_name." </option>";
				}
			}
		}			
		echo $val;
	}
	

	public function get_usersname(){
		$employeeModel = new EmployeeModel();
		$data['employee_codes_array'] = $employeeModel->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
		$userstype = $_POST['usertype'];
		
                $userid = (isset($_POST['userid']) && $_POST['userid']!=0 )?$_POST['userid']:0;
                $val='';
                $join='';
                $condition='';
                $order_by = "";
				$int_employee_id= "";
		if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                    $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR") { 
			$condition =" (e.parent_id=$int_employee_id OR e.employee_id=$int_employee_id OR e.parent_id IN ( select employee_id from employee where parent_id=$int_employee_id ) ) AND";
                       
		}
		else if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR"){
			$condition =" (e.parent_id=$int_employee_id OR e.employee_id=$int_employee_id ) AND";
                        
		}
		else if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
			$condition="e.employee_id=$int_employee_id AND ";
                       
		}
                
	
//added by Nagender 27thSep2016 for sub distibutor activity log employee type
                if($userstype=='EMPLOYEE' && ( $_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->users_type=='RESELLER') )
		{   
                    
                    //$condition = "";
                    $condition = "e.employee_parent_id=$int_employee_id AND";
		}		
		 if ($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "BNAME") {
            $order_by = "ORDER BY trim(e.dist_subdist_lcocode)";
        } else {
            $order_by = "ORDER BY trim(e.business_name)";
        }
                
		$sql="SELECT e.employee_parent_type,e.employee_id,e.first_name,e.last_name,e.business_name,e.dist_subdist_lcocode,e.users_type,COALESCE(e1.dist_subdist_lcocode, '') as lcocode FROM employee e LEFT JOIN employee e1 ON e1.employee_id = e.employee_parent_id
		$join
		WHERE $condition e.users_type='$userstype' AND e.status=1  AND e.dealer_id=".$_SESSION['user_data']->dealer_id. " $order_by";
		$qry=$this->db->query($sql);
		if($qry && $qry->num_rows()>0)
		{
                        $val = "<option value='0'>Select</option>";
			foreach($qry->result() as $users){
			        
			         $codes = "";
					if($users->users_type=='RESELLER' || $users->users_type=='DISTRIBUTOR' || $users->users_type=='SUBDISTRIBUTOR'){
				    foreach($data['employee_codes_array'] as $code){
						if($code->employee_id==$users->employee_id) $codes = $code->employee_code;
							}
							  }							  
							  /*if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
							  {  $title = 	$codes;

								 $name = trim($users->first_name." ".$users->last_name);
							  }
							  else {
							  $title = trim($users->first_name." ".$users->last_name);
							  $name = "(".$codes.")";
							  }*/
							  $code=$codes;
							 $business_name = trim($users->business_name);
							 $uname = trim($users->first_name." ".$users->last_name);	
							 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
									 $value=$code;
									 $name = "(".$business_name.")";
									 $title = $uname;
							 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
									 $title = $code;
									 $name = "(".$uname.")";
									 $value= $business_name;
							  }
							  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
									$title = $uname;
									$name = "(".$code.")";
									$value= $business_name;
							  }	
							  else {
									$title = 	$users->dist_subdist_lcocode;
									$name = "(".$uname.")";
									$value= $business_name;
							  }
						 $lcocode = " (".$users->lcocode.")";
                          $usertype = " (".$users->employee_parent_type.")"; 
                         if(!empty($users->lcocode)) { $dist_subdist_lcocode = $lcocode;}else {$dist_subdist_lcocode="";}
                         if(!empty($users->employee_parent_type)) { $utype = $usertype;}else {$utype="";}							  
							  
               if($users->users_type=='ADMIN' || $users->users_type=='EMPLOYEE' || $users->users_type=='SALES' || $users->users_type=='SERVICES' || $users->users_type=='DEALER'){
				 $val .="<option  value='".$users->employee_id."'  ".(((isset($userid) && $userid!=0 && $userid==$users->employee_id)||($users->users_type=='DEALER'))?"selected":"").">".$users->first_name." ".$users->last_name."</option>"; }  
			else{
				$val .="<option title='".$title."' value='".$users->employee_id."' ".((isset($userid) && $userid!=0 && $userid==$users->employee_id)?"selected":"").">".$value .$name . "</option>";}
			}
			
		}else{
			$val = "<option value='0'>Select</option>";
		}
		echo $val;
	}
	public function get_selectedusersname(){
	    $employeeModel = new EmployeeModel();
		if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
		$order_by= "TRIM(e.dist_subdist_lcocode)";
		}
		else {
		$order_by= "TRIM(e.business_name)";
		}
		//$data['employee_codes_array'] = $employeeModel->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
		$userstype = $_POST['usertype'];$val='';$join='';$condition='';
		$userid = isset($_POST['userid'])?$_POST['userid']:'0';
		
		$from_stb_mgmt = isset($_POST['from_stb_mgmt'])?$_POST['from_stb_mgmt']:'0';
		
		$val .=  "<option value='0'>Select</option>";
                if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                    $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR') { 
			if($userstype=="DISTRIBUTOR"){
			
				$condition ="e.parent_id=0 AND e.employee_id=$int_employee_id AND";
			}else{
				$condition ="(e.parent_id IN(select employee_id from employee where parent_id=$int_employee_id) OR e.parent_id=$int_employee_id) AND";
			}
			//$condition ="e.parent_id=0 AND e.employee_id=".$_SESSION['user_data']->employee_id." AND";
		}
		else if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR'){
			if($userstype=="SUBDISTRIBUTOR"){
				$condition ="( e.employee_id=$int_employee_id) AND";
			}else{
				$condition ="e.parent_id=$int_employee_id  AND";
				
			}
			//$join="INNER JOIN employee e1 ON e.employee_id = e1.parent_id AND e1.employee_id=".$_SESSION['user_data']->employee_id;
		}
		else if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER'){
			$condition="e.employee_id=$int_employee_id AND ";
		}
		if($userstype=="DEALER"){
			$sql="SELECT * FROM `eb_stock_location` WHERE `dealer_id` =".$_SESSION['user_data']->dealer_id." AND `reseller_id` =0 ORDER BY location_name";
			$qry=$this->db->query($sql);
			foreach($qry->result() as $users){
				$val .= "<option value=".$users->location_id;			
				if($userid!='0' && $userid == $users->location_id) 
				$val .= " selected";
				$val .= ">";
				$val .= $users->location_name."</option>";
				//$val .="<option  value='".$users->location_id."' >".$users->location_name."</option>";
			}
		}else{
			$sql="SELECT e.employee_id,esl.location_id,e.first_name,e.last_name,e.business_name,e.users_type,e.dist_subdist_lcocode FROM employee e 
			$join
			INNER JOIN eb_stock_location esl ON esl.reseller_id = e.employee_id
			WHERE $condition e.users_type='$userstype'  AND e.dealer_id=".$_SESSION['user_data']->dealer_id." ORDER BY $order_by";
			$qry=$this->db->query($sql);
			foreach($qry->result() as $users){
			
			
						$codes = "";
						if($users->users_type=='RESELLER' || $users->users_type=='DISTRIBUTOR' || $users->users_type=='SUBDISTRIBUTOR'){
						/*foreach($data['employee_codes_array'] as $code){
							if($code->employee_id==$users->employee_id) $codes = $code->employee_code;
								}*/
								  }							  
								 /* if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
								  {  $title = 	$users->dist_subdist_lcocode;
									 $name = "(".trim($users->first_name." ".$users->last_name).")";
								  }
								  else {
								  $title = trim($users->first_name.$users->last_name);
								  $name = "(".$users->dist_subdist_lcocode.")";
								  }	*/
					  $code=$users->dist_subdist_lcocode;
					 $business_name = trim($users->business_name);
					 $uname = trim($users->first_name." ".$users->last_name);	
					 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
							 $value=$code;
							 $name = "(".$business_name.")";
							 $title = $uname;
					 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
							 $title = $code;
							 $name = "(".$uname.")";
							 $value= $business_name;
					  }
					  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
							$title = $uname;
							$name = "(".$code.")";
							$value= $business_name;
					  }	
					  else {
							$title = 	$users->dist_subdist_lcocode;
							$name = "(".$uname.")";
							$value= $business_name;
					  }
			    if($from_stb_mgmt==1){
					$val .= "<option title='".$title."' value=".$users->location_id;			
					if($userid!='0' && $userid == $users->location_id) 
					$val .= " selected";
					$val .= ">";
					$val .= $value.' '.$name."</option>";
				}else{
					$val .= "<option title='".$title."' value=".$users->employee_id;			
					if($userid!='0' && $userid == $users->employee_id) 
					$val .= " selected";
					$val .= ">";
					$val .= $value.' '.$name."</option>";
				}
			
				
			}
		}
		echo $val;
	}
	public function get_usertype(){
		
		$user_type = $_POST['usertype'];
		$from_stb_mgmt = isset($_POST['from_stb_mgmt'])?$_POST['from_stb_mgmt']:'0';
		$lco_deposite_flag = isset($_POST['lco_deposite_flag'])?$_POST['lco_deposite_flag']:"";
		$val='<option value="-1">Select</option>';
		if($user_type=="DEALER"){ 
			$sql="SELECT * FROM `eb_stock_location` WHERE `dealer_id` =".$_SESSION['user_data']->dealer_id." AND `reseller_id` =0 ORDER BY trim(location_name)"; 
			$qry=$this->db->query($sql);
			foreach($qry->result() as $users){
				$val .="<option  value='".$users->location_id."' >".$users->location_name."</option>";
			}
		}else{
			
		$result = $this->CustomersModel->getLcoLocationsnew($user_type,$status=1, $employee_id=0,$lco_deposite_flag);
		
		foreach($result as $users){
		
			
					 $code=$users->dist_subdist_lcocode;
					 $business_name = trim($users->business_name);
					 $uname = trim($users->first_name." ".$users->last_name);	
					 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
							 $value=$code;
							 $name = "(".$business_name.")";
							 $title = $uname;
					 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
							 $title = $code;
							 $name = "(".$uname.")";
							 $value= $business_name;
					  }
					  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
							$title = $uname;
							$name = "(".$code.")";
							$value= $business_name;
					  }	
					  else {
							$title = 	$users->dist_subdist_lcocode;
							$name = "(".$uname.")";
							$value= $business_name;
					  }
					  
					  
			     if($from_stb_mgmt==1){
					$val .="<option  value='".$users->location_id."' title='".$title."'>".$value." ".$name."</option>";
				 }else{
					$val .="<option  value='".$users->employee_id."' title='".$title."'>".$value.' '.$name."</option>";
				 }
			}
		}
		echo $val;
	}
    
	public function customer_report_invoice(){
		$customer =$_POST['cus_name'];
		$val='';
			//$sql=$this->db->query("SELECT account_number,customer_id,first_name,business_name FROM customer Where status=1 AND dealer_id='".$_SESSION['user_data']->dealer_id."' AND reseller_id='".$_SESSION['user_data']->employee_id."' AND ((first_name LIKE '".$customer."%') OR (last_name LIKE '".$customer."%')) order by first_name" );
			$sql=$this->db->query("SELECT account_number,customer_id,first_name,last_name,business_name FROM customer Where status=1 AND dealer_id=".$_SESSION['user_data']->dealer_id." AND ((first_name LIKE '%".$customer."%') OR (last_name LIKE '%".$customer."%') OR (concat(first_name,' ',last_name) LIKE '%".$customer."%')) order by first_name" );
		
		//echo $this->db->last_query();
		if($sql && $sql->num_rows()>0)
		{
			foreach($sql->result() as $cus){
				//$val .="<option> ".$cus->first_name." ".$cus->last_name."".($cus->business_name !='')?"(".$cus->business_name.")":''."</option>";
				$val .="<option> ".$cus->first_name." ".$cus->last_name."</option>";
			}
		}
		
		echo $val;
	}
	//created by rakesh to check whether the box exist in the from location or not
	public function isBoxExist()
	{
		$cond = ""; $values = array();
                $str_join="";
		$str_condition='';
		   if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                            $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                        }else{
                            $int_employee_id = $_SESSION['user_data']->employee_id;
                        }
              if($_SESSION['user_data']->users_type=='RESELLER'  || $_SESSION['user_data']->employee_parent_type=='RESELLER'){ 
                        $str_join = " INNER JOIN employee e ON e.employee_id = sl.reseller_id ";
				$str_condition = "AND e.employee_id=$int_employee_id";
		}elseif($_SESSION['user_data']->users_type=='DISTRIBUTOR'  || $_SESSION['user_data']->employee_parent_type=='DISTRIBUTOR'){
		        $str_join = " INNER JOIN employee e ON e.employee_id = sl.reseller_id ";	
			$str_condition = " AND (e.parent_id IN (select employee_id from employee where parent_id=$int_employee_id)
			OR e.employee_id=$int_employee_id OR e.parent_id=$int_employee_id)";
		}
		else if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=='SUBDISTRIBUTOR')
		{
			//e.employee_parent_id IN( select employee_id from employee where parent_id=$employee_id)
                        $str_join = " INNER JOIN employee e ON e.employee_id = sl.reseller_id ";
			$str_condition=" AND (e.parent_id=$int_employee_id OR e.employee_id=$int_employee_id)";
		}
		$serial_num = $this->input->post('serialnumber')?$this->input->post('serialnumber'):'';
		$from = $this->input->post('stockloc')?$this->input->post('stockloc'):'';
		$mac_vc_number = $this->input->post('mac_vc_number')?$this->input->post('mac_vc_number'):'';
		$sip = $this->input->post('sip')?$this->input->post('sip'):'';
		$did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$from_fp_or_msg = $this->input->post('from_fp_or_msg')?$this->input->post('from_fp_or_msg'):'0';
		
		$isDefective = ($this->input->post('defective') && $this->input->post('defective')==1)?"1":"0";
		//echo $from;
		$values[] = $did;
		if($serial_num!=''){
           		$cond .= " AND (s.serial_number=?) "; 
			$values[] = $serial_num;
		}
		//var_dump($from);die;
		if($from!='' && $from !="null"){
			$cond .= " AND s.stock_location=? ";
			$values[] = $from;
		}
		if($mac_vc_number !=''){
			$mac_vc_number=str_replace(':','',$mac_vc_number);
			//echo $mac_vc_number;die;
			$cond .= " AND (s.vc_number=? OR REPLACE(s.mac_address,':','')=?) ";
			$values[] = $mac_vc_number;
			$values[] = $mac_vc_number;
		}
		if($sip !='' && $sip != '-1' && $sip != 0){
			$cond .= " AND s.backend_setup_id=?  ";
			$values[] = $sip;
		}
		
		if(!$from_fp_or_msg){
			if($isDefective){
				$cond .= " AND s.defective_stock=1  ";
			}else{

				$cond .= " AND s.defective_stock=0  AND s.serial_number NOT IN (select  box_number from  customer_device WHERE device_closed_on IS NULL)";

			}
		}else{
			$cond .= " AND s.defective_stock=0 ";
		}
		
		$sql_str = "select s.stock_id from eb_stock s
		INNER JOIN eb_stock_location sl ON sl.location_id = s.stock_location 
		$str_join
		where s.show_in_service_center=0 AND s.is_temp_blocked=0 AND s.dealer_id=? $str_condition $cond ";
		$query = $this->db->query($sql_str,$values);
		//echo $this->db->last_query();die;
		if($query && $query->num_rows()==1 && ($serial_num!='' || $mac_vc_number !=''))
		{
			echo $query->row()->stock_id;
		}
		else
		{
			echo 0;
		}
	}
	// method for checking the box is paired or assign to other customer Anurag **
	public function isPairedAndTransfered()
	{
		$cond = ""; $values = array();
		$pairing_cond = "";
		$stock_id=0;
		$serial_num = $this->input->post('serialnumber')?trim($this->input->post('serialnumber')):'';
		$vc_num = $this->input->post('vc_number')?trim($this->input->post('vc_number')):'';
		$from = $this->input->post('stockloc')?$this->input->post('stockloc'):'';
		
		if(($serial_num!='' && $vc_num=='') || ($serial_num=='' && $vc_num!='')){
			if($vc_num!=''){
				$vc_num=str_replace(':','',$vc_num);
				$serial_vc_cond = " AND  vc_number='$vc_num' OR REPLACE(mac_address,':','')='$vc_num' ";
			}else{
				$serial_vc_cond = " AND serial_number='$serial_num' ";
			}
		}else{
			echo "error_4";
			exit;
		}
		$did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$check_query=$this->db->query("select stock_id from eb_stock where dealer_id=$did  $serial_vc_cond ");
		// OR vc_number='$serial_num' OR REPLACE(mac_address,':','')='$serial_num'
		
		if($check_query && $check_query->num_rows()==1)
		{
			$stock_id=$check_query->row()->stock_id;
		}else{
			echo "error_0";
			exit;
		}
		
		//check temporary stb blocking
		$blocking_qry = $this->db->query("select is_temp_blocked from eb_stock where stock_id=$stock_id");
		if($blocking_qry && $blocking_qry->num_rows()>0)
		{
			if($blocking_qry->row()->is_temp_blocked==1)
			{
				echo "error_5";
				exit;
			}
		}
		
		$isfree_query=$this->db->query("select stock_id from eb_stock where dealer_id=$did AND defective_stock=0  AND isSurrended=2 AND is_trash=0 AND status<>3   AND stock_id=$stock_id");
		if($isfree_query && $isfree_query->num_rows()==1)
		{
			$stock_id=$isfree_query->row()->stock_id;
		}else{
			echo "error";
			exit;
		}
		/************Add pairing condition by Gopi******************/
		
		$pairing_query = $this->db->query("SELECT bs.pairing
		FROM `eb_stock` s
		INNER JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id
		WHERE s.stock_id=$stock_id");
		if($pairing_query && $pairing_query->num_rows()>0){
			$pairing =$pairing_query->row()->pairing;
			if($pairing==1){
				$pairing_cond ="AND vc_number <> '' AND box_number <> '' AND not_paired_in_cas=0";
			}else{
				$pairing_cond ="AND mac_address <> '' ";
			}
		}
		
		/*****************************/
		$query=$this->db->query("select stock_id from eb_stock where dealer_id=$did  $pairing_cond AND stock_id=$stock_id ");
		if($query && $query->num_rows()==0)
		{
			echo "error_1";
			exit;
		}
		$location_query=$this->db->query("select stock_id from eb_stock where stock_location=$from AND stock_id= $stock_id");
		if($location_query && $location_query->num_rows()==1)
		{
			$stock=$location_query->row()->stock_id;
		}else{
			echo "error_2";
			exit;
		}
		$is_box_free_query=$this->db->query("select stock_id from eb_stock where serial_number NOT IN (select box_number from customer_device WHERE device_closed_on IS NULL) AND stock_id= $stock");
		if($is_box_free_query && $is_box_free_query->num_rows()==1){
			echo $is_box_free_query->row()->stock_id;
		}else{
			echo "error_3";
		}
		
	}
	/***** Checking Whether the IP Address is alive or dead *****/
	public function Checkipaddress()
	{
		$id =$_POST['id'];
		$ip =$_POST['ip'];
		exec("ping -c 4 " . $ip, $output, $result);
		if ($result == 0)
		{
			echo "The IP address, $ip, is alive";
		}
		else
		{
			 echo "The IP address, $ip, is dead";
		}
	}


	// Checking server has pairing or not
	public function isServerHasPairing(){
		$sip =$_POST['sip'];
		$did = $_SESSION['user_data']->dealer_id;
		$sql = $this->db->query("select pairing from backend_setups where backend_setup_id=$sip and dealer_id = $did");
		if($sql && $sql->num_rows()>0){
			echo $sql->row()->pairing;
		}else {
			echo 0;
		}
	}
	// Checking server has card less or not
	public function isServerHasCardless(){
		$sip =$_POST['sip'];
		$did = $_SESSION['user_data']->dealer_id;
		$cas_type = $this->inventorymodel->isServerHasCardless($sip,$did);
		
               echo $cas_type;
	}
	// Checking Packages for Broadcaster 
	public function getBroadcasterPackages()
	{
		$join="";
		$cond="";
		$bc_id = $_POST['bc_id'];
		$pid = isset($_POST['pid'])?$_POST['pid']:"";
		//echo $pid;
		$arr= array();
                 if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                    $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                   
                   $join .= "INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = p.product_id "
                                            . " INNER JOIN employee e ON e.employee_id = rpm.employee_id ";
                    $cond .= " AND e.employee_id= $int_employee_id";
                   
                }	
		elseif($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR"){
                     $join .= "INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = p.product_id "
                                            . " INNER JOIN employee e ON e.employee_id = rpm.employee_id ";
                        $cond .= " AND e.parent_id= $int_employee_id ";
                }
                elseif($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR"){
                   
                     $join .= "INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = p.product_id "
                                            . " INNER JOIN employee e ON e.employee_id = rpm.employee_id "
                             . " LEFT JOIN employee d on e.parent_id = d.employee_id ";
                        $cond .= " AND (d.parent_id= $int_employee_id OR e.parent_id=$int_employee_id )";
                   
                }
		if($bc_id!='-1')
		{
			$join .=" INNER JOIN eb_product_channels pc ON p.product_id=pc.product_id
					 INNER JOIN eb_broadcaster_channels bc ON bc.channel_id=pc.channel_id";
			$cond .=" AND bc.broadcaster_id='$bc_id'"; 
		}
			$options = "<option value=\"-1\">All</option>";
			$sql1 = "select DISTINCT p.pname, p.product_id,bs.cas_server_type from eb_products p
                            INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id
					$join
					WHERE p.alacarte=1 AND p.plugin_id=4 AND p.status=1 $cond AND p.dealer_id=".$_SESSION['user_data']->dealer_id." ORDER BY p.pname";
				  
			$sql2 = "select DISTINCT p.pname, p.product_id,bs.cas_server_type from eb_products p
                             INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id
					 $join
					WHERE p.alacarte=0 AND p.plugin_id=4 AND p.status=1 $cond AND p.dealer_id=".$_SESSION['user_data']->dealer_id." ORDER BY p.pname";
				  
		  $query1 = $this->db->query($sql1);
		  $query2 = $this->db->query($sql2);
		  //echo $this->db->last_query();
		 $arr = explode(",", $pid);
		 $options .= "<optgroup label='A-La-Carte Packages'>";
		  if($query1 && $query1->num_rows()>0){
			  $res = $query1->result();
			  
				foreach($res as $row){
					$options .= '<option value="'.$row->product_id.'" '.(($pid!="" && in_array($row->product_id,$arr))?"selected":"").' >'.$row->pname.'('.$row->cas_server_type.')'.'</option>';
				}
		  }
		  $options .= "<optgroup label='Other Packages'>";
		  if($query2 && $query2->num_rows()>0){
			  $res = $query2->result();
			  	foreach($res as $row){
					$options .= '<option value="'.$row->product_id.'" '.(($pid!="" && in_array($row->product_id,$arr))?"selected":"").' >'.$row->pname.'('.$row->cas_server_type.')'.'</option>';
				}
		  }
			echo $options;
	}
	public function getCASProducts()
	{
		$dealer_id=$_SESSION['user_data']->dealer_id;
		$sql_str = "SELECT ep.* FROM eb_products ep
					WHERE ep.status=1 AND ep.plugin_id=4 AND ep.dealer_id=$dealer_id group by ep.pname";
		 $query = $this->db->query($sql_str);
		if($query && $query->num_rows()>0){
			  $res = $query->result();
			  $options .= '<option value="-1" >All</option>';
			  	foreach($res as $row){
					$options .= '<option value="'.$row->product_id.'" >'.$row->pname.'</option>';
				}
		  }
		echo $options;
	}
	//get Business partner related group on change o business partner   <Anurag> 
	public function get_employee_groups(){
			$options='';	   
			$emp=$_POST['eid'];
			if($emp !=0){
				$eid=$emp;
			}else{
				$eid = $_SESSION['user_data']->employee_id;
				}
            $did = $_SESSION['user_data']->dealer_id;
            $sqlstr = "SELECT eg.group_id, g.group_name FROM employee_group eg, groups g  WHERE eg.group_id = g.group_id AND eg.employee_id =$eid AND g.dealer_id =$did";
            $query = $this->db->query($sqlstr);
           // echo $this->db->last_query();
            if($query && $query->num_rows()>0)
            {
				$options .= '<option value="-1">Select</option>';
				foreach($query->result() as $group){
					$options .= '<option value="'.$group->group_id.'">'.$group->group_name.'</option>';
				}
			echo $options;
			}
        }
                
        //Active Services for customer by Ashwin
        public function getActiveServicesForCustomer(){

            $did = $_SESSION['user_data']->dealer_id;
            $old_stock_id = $_POST['old_stock_id'];
            $new_stock_id = $_POST['new_stock_id'];
            $services = ""; $i =0;
            $cond = '';

            $result = $this->dasmodel->getActiveServicesForCustomer($old_stock_id,$new_stock_id);
            
            if(count($result)>0){
                $services = "<table> ";
                foreach($result as $row){
                    $i++;
                    $services .= "<tr><td style='background-color:#ffffff; width:20px;'>".$i. ".</td><td style='background-color:#ffffff;'> " .$row->pname. "</td></tr>";
                }
                $services .= "</table>";
            }
            echo $services;
        }


		public function saveReportColumns(){
			$did = $_SESSION['user_data']->dealer_id;
			$columns = $_POST['columns'];
			$report_name = $_POST['report_name'];
			$eid = $_SESSION['user_data']->employee_id;
			//echo $columns;
			$query = $this->db->query("SELECT report_name,id FROM eb_reports WHERE report_name='$report_name' AND dealer_id=$did ");
			// echo $query->num_rows();die();
			
			if($query && $query->num_rows()==0)
			{
			$res=$this->db->query("INSERT INTO eb_reports (report_name,columns,dealer_id,created_on,created_by) VALUES ('$report_name','$columns',$did,NOW(),$eid)");
			}
			else
			{
			 $id=$query->row()->id;
			  $res = $this->db->query("UPDATE eb_reports SET columns ='$columns' WHERE id =$id AND report_name='$report_name' AND dealer_id=$did;"); 
			}

			if($res) 
			{
				$_SESSION['existing_reports'][$report_name] = explode(',',$columns);
			    $_SESSION['column_names']=explode(',', $columns );
			}
			

			//echo $columns;
			echo $res;
	
		}

		public function getDestinationLocations_pullstock()
	{
		$pullstock="";
		$pullStockCondition="";
		$emp_model = new EmployeeModel();
		$destinationUser = ($this->input->post('destinationId'))?$this->input->post('destinationId'):0;
		$employeeId = ($this->input->post('employeeId'))?$this->input->post('employeeId'):0;
		$parentId = $this->input->post('parentId')?$this->input->post('parentId'):0;
		$sql_str = '';
		$dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$query = '';
		$condition = '';
		$employee_condition = '';
		$orderby='';
		if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
		 $orderby= "e.dist_subdist_lcocode";
		 }
		 else {
		 $orderby= "el.location_name";
		 }
		if((isset($_SESSION['user_data']->users_type) && $_SESSION['user_data']->users_type=="DISTRIBUTOR") ||(isset($_SESSION['user_data']->users_type) && $_SESSION['user_data']->users_type=="SUBDISTRIBUTOR") )
		{
			$employee_condition = "SELECT employee_id FROM employee WHERE employee_id=$employeeId AND dealer_id=$dealerId";
		}
		else
		{
			$employee_condition = "SELECT parent_id FROM employee WHERE employee_id=$employeeId AND dealer_id=$dealerId";
		}
		$data['employee_codes_array'] = $emp_model->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
		if($employeeId != 0 && $destinationUser != 6)
		{
			//$condition = " ";
			$condition = " AND e.parent_id=$employeeId ";
		}
		//code change for pull stock
		$pullStock = $this->input->post('pullStock')?1:0;
		if($pullStock == 1)
		{
			$destinationUser = 11;
		}
		
		switch($destinationUser)
		{
			//for getting mso location i.e, primary location
			case 1:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.dealer_id=e.dealer_id where el.dealer_id=? AND el.reseller_id = ? AND e.users_type=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,'DEALER'));
				break;
			//for getting distributor locations
			case 2:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'DISTRIBUTOR'));
				break;
			//for getting sub distributor locations
			case 3:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? $condition ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR'));			
				break;
			//for getting lco locations
			case 4:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? $condition $pullStockCondition ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER'));	break;	
			//for getting distributor locations when sub distributor transferring to distributor
			case 5:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? AND e.employee_id=($employee_condition) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'DISTRIBUTOR'));
				break;
			//for getting distributor locations when reseller transferring to distributor
			case 6:
				/*$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=? AND e.users_type=? AND e.status=? AND e.parent_id=(SELECT parent_id FROM employee WHERE employee_id=? AND dealer_id=?) AND e.employee_id !=? ORDER BY el.location_name";
				$query = $this->db->query($sql_str,array($dealerId,0,'RESELLER',1,$employeeId,$dealerId,$employeeId));*/
				$query = $this->db->query("SELECT el.location_id, el.location_name, e.business_name, e.parent_id, e.employee_id,e.dist_subdist_lcocode, e.users_type, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM eb_stock_location el
				LEFT JOIN employee e ON el.reseller_id = e.employee_id
				WHERE el.dealer_id =$dealerId
				AND e.users_type = 'DISTRIBUTOR'
				AND el.is_primary =0
				AND e.status =1
				AND (e.employee_id = (select parent_id from employee e1 where e1.employee_id=$employeeId) or e.employee_id = (select parent_id from employee e1 where e1.employee_id=$parentId))
				ORDER BY $orderby
				") ;
				break;	
			//for getting mso locations when sub distributor transferring to mso
			case 7:
				$query = $this->db->query("SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type, e.employee_id, e.parent_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM eb_stock_location el
				LEFT JOIN employee e ON el.reseller_id = e.employee_id
				WHERE el.dealer_id =$dealerId
				AND e.users_type = 'DEALER'
				AND e.status =1
				AND e.parent_id =$employeeId
				UNION SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type,  e.employee_id, e.parent_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM employee e
				LEFT JOIN eb_stock_location el ON el.dealer_id = e.dealer_id
				AND el.reseller_id =0
				WHERE e.users_type = 'DEALER'
				AND e.dealer_id =$dealerId") ;
				//$query = $this->db->query($sql_str,array($dealerId,0,'DEALER',1,$employeeId,$dealerId,$employeeId));
				break;		
			//for getting mso locations when lco transferring to mso
			case 8:
			$query = $this->db->query("SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type, e.employee_id, e.parent_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM eb_stock_location el
				LEFT JOIN employee e ON el.reseller_id = e.employee_id
				WHERE el.dealer_id =$dealerId
				AND e.users_type = 'DEALER'
				AND e.status =1
				AND e.parent_id =$employeeId
				UNION SELECT el.location_id, el.location_name,e.business_name,e.dist_subdist_lcocode,e.users_type,  e.employee_id, e.parent_id, CONCAT( e.first_name, ' ', e.last_name ) AS empname
				FROM employee e
				LEFT JOIN eb_stock_location el ON el.dealer_id = e.dealer_id
				AND el.reseller_id =0
				WHERE e.users_type = 'DEALER'
				AND e.dealer_id =$dealerId") ;
				/*$sql_str = "SELECT el.location_id,el.location_name,e.employee_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=? AND e.users_type=? AND e.status=? AND e.parent_id=($parentId) AND e.employee_id =?";
				$query = $this->db->query($sql_str,array($dealerId,0,'DEALER',1,$employeeId));*/
				break;
			case 9:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? AND e.employee_id=(SELECT parent_id FROM employee WHERE employee_id=? AND dealer_id=?) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR',$employeeId,$dealerId));
				break;
			case 10:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=?  ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER'));
				break;	
			//for pull stock
			case 11:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? AND e.employee_id=? ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER',$_SESSION['user_data']->employee_id));	
				break;
				//for getting lco location when source is distributor implemented by GOPI
			case 12:
				$sql_str="SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.dist_subdist_lcocode,e.users_type,e.employee_id,CONCAT(e.first_name,' ' , e.last_name) AS empname 
				from eb_stock_location el 
				INNER JOIN employee e ON el.reseller_id=e.employee_id 
				where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? AND (e.parent_id IN(select employee_id from employee where parent_id=?) OR e.parent_id=?) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'RESELLER',$employeeId,$employeeId));	
				break;	
				//for getting distributor locations when sub distributor transferring to distributor
			case 13:
				$sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.parent_id,e.employee_id,e.dist_subdist_lcocode,e.users_type,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el LEFT JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND el.is_primary=?  AND e.status=? AND e.users_type=? AND e.employee_id=($employee_condition) ORDER BY $orderby";
				$query = $this->db->query($sql_str,array($dealerId,0,1,'SUBDISTRIBUTOR'));
				break;
		}
		//echo $this->db->last_query();
		$val = '';
		$val .= ((isset($_SESSION['user_data']->users_type) && $_SESSION['user_data']->users_type=='DISTRIBUTOR')||(isset($_SESSION['user_data']->users_type) && $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR'))?"":"<option value='-1'>Select</option>";
		if($query && $query->num_rows()>0)
		{
			foreach($query->result() as $res)
			{
			       						  
				/*if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
				{ 	
					$title = $res->dist_subdist_lcocode;
					$name = " (".trim($res->empname).")";
				}
				else {
					$title = trim($res->empname);
					$name = "(".$res->dist_subdist_lcocode.")";
				}	*/
				 $code=$res->dist_subdist_lcocode;
				 $business_name = trim($res->business_name);
				 $uname = trim($res->empname);	
				 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
						 $value=$code;
						 $name = "(".$business_name.")";
						 $title = $uname;
				 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
						 $title = $code;
						 $name = "(".$uname.")";
						 $value= $business_name;
				  }
				  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
						$title = $uname;
						$name = "(".$code.")";
						$value= $business_name;
				  }	
				  else {
						$title = 	$users->dist_subdist_lcocode;
						$name = "(".$uname.")";
						$value= $business_name;
				  }

				if($res->users_type == 'DEALER'){
					$val .= "<option value='".$res->location_id."' employeeid='".$res->employee_id."'  parentid='".$res->parent_id."'>".$res->location_name."</option>";
				}else{
					$val .= "<option title='".$title."' value='".$res->location_id."' employeeid='".$res->employee_id."'  parentid='".$res->parent_id."'>".$value." ".$name."</option>";
				}
				//$val .= "<option value='".$res->location_id."' employeeid='".$res->employee_id."'  parentid='".$res->parent_id."'>".$res->business_name."".$name."</option>";

			}
		}
		
		echo $val;
	}

	// For getting Master table Record [Anurag]
	//Modified by pradeep
		public function master_table(){
		$val=''; $col1=''; $col2=''; $col3='';$col4='';
		$join=''; $join2=''; $col= ''; $set_cond='';
		$where_type=''; $where='';$group='';
		$status= "status";
		$tableName = $_POST['tableName'];
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$reseller = (isset($_POST['reseller']) && $_POST['reseller'] !='-1')?$_POST['reseller']:'-1';
		$city = (isset($_POST['city']) && $_POST['city'] !=-1)?$_POST['city']:'-1';
		$customer_type = (isset($_POST['customer_type']) &&$_POST['customer_type'] !='-1')? $_POST['customer_type']:'-1';
		$hotel_name = (isset($_POST['hotel_name']) && $_POST['hotel_name'] != '')?$_POST['hotel_name']:'';
		$max_stb = (isset($_POST['max_stb']) && $_POST['max_stb']!='')?$_POST['max_stb']:'';
		$code = (isset($_POST['code']) && $_POST['code']!='')?$_POST['code']:'';

		if($code!='' && $reseller  == '-1' ){
			$this->load->model('EmployeeModel');
			$get_employee_details = $this->EmployeeModel->getEmployeeDetails($id=0, $user_name='', $did=0,$code,$lco_code_manual='',$parent_id=0,$tousertype='',$deposit_transfer_access=0);
			if(isset($get_employee_details) && count($get_employee_details)>0){
				$reseller = isset($get_employee_details->employee_id)?$get_employee_details->employee_id:'';
			}
		}
		
		if($reseller  !='-1' || $code !=''){
			$where=" WHERE t.location_id <> '-1'";
			$where_type .= " AND t.reseller_id=$reseller ";
		}
		
		if($city  !='-1'){
			$where=" WHERE t.location_id <> '-1'";
			$where_type .= " AND t.location_id = $city ";
		}
		if($customer_type  !='-1'){
			$where=" WHERE t.location_id <> '-1'";
			$where_type .= " AND t.customer_type_id =$customer_type ";
		}
		if($hotel_name  !=''){
			$where=" WHERE t.location_id <> '-1'";
			$where_type .= " AND t.name='$hotel_name'";
		}
		if($max_stb  !=''){
			$where=" WHERE t.location_id <> '-1'";
			$where_type .= " AND t.max_box_count=$max_stb ";
		}
		if($tableName == 'eb_customer_types')
		{
			$col1 = "customer_type";
			$col2 = "description";
			$col3 = "customer_type_id";
		}
		else if($tableName == 'eb_id_types')
		{
			$col1 = "type";
			$col2 = "value";
			$col3 = "id_type_id";
		}
		else if($tableName == 'eb_stb_types')
		{
			$col1 = "type";
			$col2 = "description";
			$col3 = "stb_type_id";
			$col4 = "display_name";
			$col5 = "device_type";
		}
                else if($tableName == 'eb_customerSLA') //DURGA--> CUSTOMER SLA TABLE & columns are CALLING 
		{
			$col1 = "sla_name";
			$col2 = "sla_description";
			$col3 = "customersla_id";
			$col4 = "sla_priority";			 
			$where .=" where dealer_id =" . $dealer_id;
			}	
		else if($tableName == 'eb_customercare_call_category')
		{
			$col1 = "category_description";
			$col2 = "category_val";
			$col3 = "call_category_id";
		}
		else if($tableName == 'eb_lco_complaint_categories'){
			$col1 = "dealer_id";
			$col2 = "category";
			$col3 = "complaint_category_id";

		}
		else if ($tableName == 'eb_customer_type_of_types'){
			$join = " INNER JOIN employee e ON e.employee_id= t.reseller_id
					  INNER JOIN eb_location_locations l ON l.location_id=t.location_id
					  INNER JOIN eb_customer_types ct ON ct.customer_type_id= t.customer_type_id ";
			$group = " GROUP BY ename,t.name";
			$col =" , CONCAT(e.business_name,' (', e.first_name,' ',e.last_name,')')as ename,e.dist_subdist_lcocode,l.location_name,ct.customer_type ";			
			$col3 = "customer_type_types_id";
			$status = "is_active";
			$set_cond= ", modified_by=".$_SESSION['user_data']->employee->employee_id.", modified_on=NOW()";
		}
		
		if(!empty($_POST['tableId']) && empty($_POST['edit']))
		{
			$tableId = $_POST['tableId'];
			if($tableId!="")
			{
				if($_POST['timestamp']!=''){
					$stat = $_POST['status'];
					if($stat==1){
						//check the stb type condition
						$query_stb_type = $this->db->query("SELECT count(*) cnt FROM eb_product_to_stbtype_mapping where stb_type_id=".$tableId);
						if($query_stb_type->row()->cnt==0){
							$query=$this->db->query("UPDATE $tableName SET $status=0 $set_cond WHERE $col3=$tableId");						
							//	echo $this->db->last_query();die();
							if($query && $this->db->affected_rows()>0){							
							//$this->session->set_flashdata('msg','Record Deactivated Successfully.');
								echo "<div style='color:green; font-weight:bold'>Record deactivated successfully.</div>";
							}else{
								echo "<div style='color:green; font-weight:bold'>Record not deactivated .</div>";
							}
						}else{
							echo "<div style='color:red; font-weight:bold'>STB type is mapped to product , please unmap from the product(s) to deactivate.</div>";
						}
						

					}
					else if($stat==0){
						$query=$this->db->query("UPDATE $tableName SET $status=1 $set_cond WHERE $col3=$tableId");
						//	echo $this->db->last_query();die();
						if($query && $this->db->affected_rows()>0){
						//$this->session->set_flashdata('msg','Record Activated Successfully.');
						echo "<div style='color:green; font-weight:bold'>Record activated successfully.</div>";
						}else{
							echo "<div style='color:green; font-weight:bold'>Record not activated.</div>";
						}
					}
				}
			}
		}
		if(!empty($_POST['edit']))
		{
			$tableId = $_POST['tableId'];
			if($tableName != 'eb_customer_type_of_types'){
				$update_sql=$this->db->query("SELECT * FROM $tableName  WHERE $col3=$tableId");
			}else{
				$update_sql = $this->db->query(" SELECT t.*,l.state_id,e.dist_subdist_lcocode,e.state,e.district FROM $tableName t INNER JOIN eb_location_locations l ON l.location_id=t.location_id INNER JOIN employee e ON e.employee_id=t.reseller_id WHERE $col3=$tableId");
			}
			
			if($update_sql && $update_sql->num_rows()>0){
				if($tableName != 'eb_customer_type_of_types'){
					echo $update_col_1 = $update_sql->row()->$col1."@@";
					echo $update_col_2 = $update_sql->row()->$col2."@@";
					echo $update_status = $update_sql->row()->$col3."@@";
                                        // CUSTOMER SLA COLUMN VALUES ARE RETURN TO INPUT TAGS FOR EDITING ---DURGA...
					if($tableName == 'eb_customerSLA')
					{						
					echo $update_col_3 = $update_sql->row()->$col4."@@";
					echo $update_col_4 = $update_sql->row()->auto_extension."@@";
					}
					if($tableName == 'eb_stb_types'){
						echo $display_name = $update_sql->row()->$col4."@@";
						echo $device_type = $update_sql->row()->$col5 ."@@";
					}
					if($tableName == 'eb_customer_types'){				
						echo $update_status = $update_sql->row()->is_commercial_multi_box."@@";
					}
				}else{
					echo $update_col_1 = $update_sql->row()->reseller_id."@@";					
					echo $update_col_1 = $update_sql->row()->location_id."@@";	
					echo $update_col_1 = $update_sql->row()->customer_type_types_id."@@";				
					echo $update_col_1 = $update_sql->row()->customer_type_id."@@";
					echo $update_col_1 = $update_sql->row()->name."@@";
					echo $update_col_1 = $update_sql->row()->max_box_count."@@";					
					//echo $update_col_1 = $update_sql->row()->state_id."@@";					
					echo $update_col_1 = $update_sql->row()->dist_subdist_lcocode."@@";					
					echo $update_col_1 = $update_sql->row()->state."@@";					
					echo $update_col_1 = $update_sql->row()->district."@@";					
				}
			}
		}
		
		$query=$this->db->query("SELECT t.* $col FROM $tableName t $join $where $where_type ");		
		if($query && $query->num_rows()>0){
		if($tableName=='eb_customercare_call_category' || $tableName=='eb_lco_complaint_categories'){
                        $val .="<table width='50%' cellpadding='1' cellspacing='1' border='0' class='mygrid'>";
                        $val .="<tr >";
                        $val .="<th>Category Value</th>";
						if($tableName=='eb_customercare_call_category')
                        $val .="<th>Description</th>";	
                        $val .="<th>Operations</th>";			
                        $val .="</tr>";
                        foreach($query->result() as $table_record){
                        $val .= "<tr align='left'>";
                        $val .= "<td>".$table_record->$col2." </td>";
						if($tableName=='eb_customercare_call_category'){
                        $val .= "<td >".$table_record->$col1."</td>";
						$val .="<td ><a href='".base_url()."index.php/setting/managemasters/".$table_record->$col3."/".$tableName."/".$table_record->status."/edit'  title='Edit' onclick='javascript: edit();'><img src='".base_url()."assets/images/operations-edit.png' border='0'></a></td> ";}
						else{
						$val .="<td ><a href='".base_url()."index.php/setting/managemasters/".$table_record->$col3."/".$tableName."/1/edit'  title='Edit' onclick='javascript: edit();'><img src='".base_url()."assets/images/operations-edit.png' border='0'></a></td> ";
						}
                        }
                        $val .= "</tr></table>";
		}
                //DURGA CustomerSLA grid view
		else if($tableName == 'eb_customerSLA'){
                        $val .="<table width='50%' cellpadding='1' cellspacing='1' border='0' class='mygrid'>";
                        $val .="<tr >";
                        $val .="<th>SLA NAME</th>";
                        $val .="<th>SLA Description</th>";
                        $val .="<th>SLA Priority</th>";
						$val .="<th>Apply Auto Extenction</th>";
						$val .="<th>Operations</th>";
                        $val .="</tr>";
                        foreach($query->result() as $table_record)
						{	
								$val .= "<tr>";;
                                $val .= "<td>".$table_record->sla_name." </td>";
                                $val .= "<td>".$table_record->sla_description." </td>";
                                $val .= "<td>".$table_record->sla_priority." </td>";												
                                $val .="<td >".(($table_record->auto_extension==1)?'Yes':'No')."</td>";                                
								$val .="<td><a href='".base_url()."index.php/setting/managemasters/".$table_record->$col3."/".$tableName."/".$table_record->$col4."/edit'  title='Edit' onclick='javascript: edit();'><img src='".base_url()."assets/images/operations-edit.png' border='0'></a></td> ";
                                
                        } 
						$val .= "</tr></table>";
                }
		
		//ENDING SLA
		else if($tableName == 'eb_customer_type_of_types'){
                        $val .="<table width='50%' cellpadding='1' cellspacing='1' border='0' class='mygrid'>";
                        $val .="<tr >";
                        $val .="<th>LCO</th>";
                        $val .="<th>Location</th>";
                        $val .="<th>Type</th>";		
                        $val .="<th>Name</th>";			
                        $val .="<th>Maximum STB</th>";			
                        $val .="<th>Status</th>";			
                        $val .="<th>Operations</th>";			
                        $val .="</tr>";
                        foreach($query->result() as $table_record){	
                                $val .= "<td>".$table_record->ename." [".$table_record->dist_subdist_lcocode."] </td>";
                                $val .= "<td>".$table_record->location_name." </td>";
                                $val .= "<td>".$table_record->customer_type." </td>";
                                $val .= "<td>".$table_record->name." </td>";
                                $val .= "<td>".$table_record->max_box_count." </td>";
                                $val .= "<td>".(($table_record->is_active==1)?'Activated':'Deactivated')." </td>";
                                $val .="<td ><a href='".base_url()."index.php/setting/managemasters/".$table_record->$col3."/".$tableName."/".$table_record->$status."/edit'  title='Edit' onclick='javascript: edit();'><img src='".base_url()."assets/images/operations-edit.png' border='0'></a> ";
                                if($table_record->$status==1){							
                                $val .="<img src='".base_url()."assets/images/operations-delete.png' border='0' title='deactivate' id='delete' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->$status."' onclick='javascript: deactivate(".$table_record->$col3.");'>";	
                                }else{
                                        $val .="<img src='".base_url()."assets/images/operations-save.png' border='0' title='activate' id='act_record' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->$status."' onclick='javascript: activate(".$table_record->$col3.");'></td>";
                                }					
                                $val .= "</tr>";
                        } 
                }else{
                        $val .="<table width='50%' cellpadding='1' cellspacing='1' border='0' class='mygrid'>";
                        $val .="<tr >";
                        $val .="<th>Type</th>";
						($tableName == 'eb_stb_types')?$val .="<th>Display Name</th>":'';
                        $val .="<th>Description</th>";
                        if($tableName == 'eb_customer_types'){				
                        $val .="<th width='200px'>Is Commercial Multi Box</th>";
                        }
                        $val .="<th>Status</th>";	
                        $val .="<th>Operations</th>";			
                        $val .="</tr>";
                        foreach($query->result() as $table_record){					
                                $tabid = $table_record->$col3;					
                                $val .= "<tr align='left'>";
                                $val .= "<td>".$table_record->$col1." </td>";
                                ($tableName == 'eb_stb_types')?$val .= "<td>".$table_record->$col4." </td>":'';
                                $val .= "<td >".$table_record->$col2."</td>";
                                if($tableName == 'eb_customer_types'){				
                                        $val .="<td >".(($table_record->is_commercial_multi_box==1)?'Yes':'No')."</td>";
                                }
                                $val .="<td >".(($table_record->status==1)?'Activated':'Deactivated')."</td>";

                                $val .="<td ><a href='".base_url()."index.php/setting/managemasters/".$table_record->$col3."/".$tableName."/".$table_record->status."/edit'  title='Edit' onclick='javascript: edit();'><img src='".base_url()."assets/images/operations-edit.png' border='0'></a> ";
                                $query_stb_type = $this->db->query("SELECT count(*) cnt FROM eb_product_to_stbtype_mapping where stb_type_id=".$table_record->$col3);
                                        if($query_stb_type->row()->cnt==0 && $tableName=="eb_stb_types"){
                                                if($table_record->status==1){							
                                                $val .="<img src='".base_url()."assets/images/operations-delete.png' border='0' title='deactivate' id='delete' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->status."' onclick='javascript: deactivate(".$table_record->$col3.");'>";	
                                                }else{
                                                        $val .="<img src='".base_url()."assets/images/operations-save.png' border='0' title='activate' id='act_record' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->status."' onclick='javascript: activate(".$table_record->$col3.");'></td>";
                                                }	}else if($tableName!="eb_stb_types"){
                                                        if($table_record->status==1){							
                                                        $val .="<img src='".base_url()."assets/images/operations-delete.png' border='0' title='deactivate' id='delete' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->status."' onclick='javascript: deactivate(".$table_record->$col3.");'>";	
                                                        }else{
                                                                $val .="<img src='".base_url()."assets/images/operations-save.png' border='0' title='activate' id='act_record' columnid='".$table_record->$col3."' table_name='".$tableName."' status='".$table_record->status."' onclick='javascript: activate(".$table_record->$col3.");'></td>";
                                                        }
                                                }				
                                $val .= "</tr>";				
                        }
                }
		}			
		echo $val;	
	}
	public function setLabel(){
		
		$retval = "";
		$retval1 = "";
		$retval2 = "";
		$retval3 = "";
		$serverType = $_POST['serType'];
		$fromwhichPage = $_POST['fromwhichPage'];
		
		if($fromwhichPage == "transfer_stb")
		{
			if(!empty($_SESSION['cas_term']))
			{	
				if (isset($_SESSION['cas_term']['for_serialno'])) {
					if(array_key_exists($serverType,$_SESSION['cas_term']['for_serialno']))
					$retval = $_SESSION['cas_term']['for_serialno'][$serverType];
					if($retval=="")
					{			
						$retval = "Serial Number";
					}
				}
			}
			else {
				$retval = "Serial Number";
			}
		}
		else if($fromwhichPage == "authorised_Broadcasters")
		{
			if(!empty($_SESSION['cas_term']))
			{		
				if (isset($_SESSION['cas_term']['vc_number'])) {
					if(array_key_exists($serverType,$_SESSION['cas_term']['vc_number']))
					$retval = $_SESSION['cas_term']['vc_number'][$serverType];
					if($retval=="")
					{
						if($_SESSION['mac_vc_column']!="")
						$retval = $_SESSION['mac_vc_column'];
					}
					if($retval=="")
					{			
						$retval = "Mac Address / VC Number";
					}
				}
			}
			else {
				$retval = "Mac Address / VC Number";
			}
		}
		else if($fromwhichPage == "stb_list")
		{
			if(!empty($_SESSION['cas_term']))
			{	
				if (isset($_SESSION['cas_term']['for_serialno'])) {
					if(array_key_exists($serverType,$_SESSION['cas_term']['for_serialno']))
					$retval1 = $_SESSION['cas_term']['for_serialno'][$serverType];
					if($retval1=="")
					{			
						$retval1 = "Serial Number";
					}
				}
				if (isset($_SESSION['cas_term']['vc_number'])) {
					if(array_key_exists($serverType,$_SESSION['cas_term']['vc_number']))
					$retval2 = $_SESSION['cas_term']['vc_number'][$serverType];
					if($retval2=="")
					{
						if($_SESSION['mac_vc_column']!="")
						$retval2 = $_SESSION['mac_vc_column'];
					}
					if($retval2=="")
					{			
						$retval2 = "Mac Address / VC Number";
					}
				}
				
				if (isset($_SESSION['cas_term']['for_reactivation'])) {
					if(array_key_exists($serverType,$_SESSION['cas_term']['for_reactivation']))
					$retval3 = $_SESSION['cas_term']['for_reactivation'][$serverType];
					if($retval3=="")
					{			
						$retval3 = "Reactivate";
					}
				}
				$retval = $retval1."@@".$retval2."@@".$retval3;
			}
			else {
				$retval1 = "Serial Number";
				$retval2 = "Mac Address / VC Number";
				$retval3 = "Reactivate";
				$retval = $retval1."@@".$retval2."@@".$retval3;
			}
		}
		
		echo $retval;
		
	
	}


    /**** Function to get the mapped product to uprade services start ****/ // ----- Suchisnata 
    
    public function get_mapped_product(){
     $obj_products = new productsmodel();
     $cond = "";
	 $join ="";
	 
	  $user_id = isset($_POST['user_id'])?$_POST['user_id']:0;
	  $dealer_id = $_SESSION['user_data']->dealer_id;
	  $users_type = isset($_POST['users_type'])?$_POST['users_type']:'';
		$stb_type = isset($_POST['stb_type']) ? $_POST['stb_type'] : '-1';//by ravula
        //added for filter products in migration upgrade and deactive services by Ravula on 21/07/17
        if($stb_type!='-1'){
            $join .= " INNER JOIN eb_product_to_stbtype_mapping ps ON ps.product_id=prd.product_id AND ps.status=1 ";
            $cond .= " AND ps.stb_type_id=$stb_type";
        }
           $get_recurring_only = isset($_POST['get_recurring_only'])?$_POST['get_recurring_only']:0;
           if($get_recurring_only==1){
               $cond .= " AND (prd.pricing_structure_type =2 )";
           }else{
               $cond .= " AND prd.pricing_structure_type in (1, 2)";
           }
          	$is_user_blocked = isset($_POST['is_user_blocked'])?$_POST['is_user_blocked']:0;
      $option='<option value="-1">Select</option>';
	  
	   if($user_id>0 && $users_type=='RESELLER')
	  {
		$setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO))?$_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO:0;
			if($setting_value==0)
			{
				$cond .= " AND pm.is_blocked In (0,2) ";
			}
			else if($setting_value==2)
			{
				if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER")
				{
					$cond .= " AND pm.is_blocked In (0,2) ";
				}
			}
			else{
				if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER")
				{
					$cond .= " AND pm.is_blocked In (0,2) ";
				}
			}
	  }
	  
	 if($user_id>0){
		 $cond .= "AND (e.parent_id IN (select employee_id from employee where parent_id=$user_id) 
                      OR e.employee_id=$user_id 
                      OR e.employee_id IN (select employee_id from employee where parent_id=$user_id))";
					  
		 $join .= "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = prd.product_id  
				   INNER JOIN employee e ON pm.employee_id=e.employee_id AND e.status=1"; 
	 }
    
	  $sql_str = "SELECT DISTINCT prd.product_id ,prd.pname as product_name,prd.sku,prd.base_price , prd.pricing_structure_type,prd.backend_setup_id,bs.cas_server_type,bs.display_name,prd.*
                 FROM eb_products prd 
				 INNER JOIN backend_setups bs ON bs.backend_setup_id=prd.backend_setup_id
				 $join
                 where prd.dealer_id=$dealer_id AND prd.status=1 $cond 
				 order by prd.pname,prd.backend_setup_id";
				   
	 $qry = $this->db->query($sql_str);
	 //echo $this->db->last_query();
	  $mapped_product_result = $qry->result();
	
	  foreach($mapped_product_result as $mapped_product){
			if($is_user_blocked==0){
				$option .="<option value='".$mapped_product->product_id."' price_type='".$mapped_product->pricing_structure_type."' backend_setup_id='".$mapped_product->backend_setup_id."'>".$mapped_product->product_name." (".$mapped_product->display_name.")</option>";

			}else{
				//if user is blocked then provide the activation which are having zero bill amount
				$total_amount = $obj_products->CheckAmountForBlockedUser($mapped_product);
				if($total_amount==0){
						$option .="<option value='".$mapped_product->product_id."' price_type='".$mapped_product->pricing_structure_type."' backend_setup_id='".$mapped_product->backend_setup_id."'>".$mapped_product->product_name." (".$mapped_product->display_name.")</option>";

				}
		   }
	  }	
	  
	  echo $option;
    }
    
    /**** Function to get the mapped product uprade services end  ****/ // ----- Suchisnata 
    
    /**** Function to get upgarde mapped product to uprade services start  ****/ // ---- Suchisnata
    public function get_upgrade_mapped_product(){
		
		$join="";
		 $cond = '';
    $dealer_id = $_SESSION['user_data']->dealer_id;
	  $user_id = isset($_POST['user_id'])?$_POST['user_id']:0;	
	  $mapped_product_id = $_POST['mapped_product_id'];	
	  $price_type = $_POST['price_type'];
	  $backend_setup_id = $_POST['backend_setup_id'];
	  $users_type = ($_POST['users_type'])?$_POST['users_type']:'';
		$stb_type = isset($_POST['stb_type']) ? $_POST['stb_type'] : '-1';//by ravula
		//added for filter products in migration upgrade and deactive services by Ravula on 21/07/17
		if($stb_type!='-1'){
			$join .=" INNER JOIN eb_product_to_stbtype_mapping ps ON ps.product_id=prd.product_id AND ps.status=1 ";
			$cond.=" AND ps.stb_type_id=$stb_type ";
		}
	  
	  $upgrade_option='<option value="-1">Select</option>';
	 
	  if($user_id>0 && $users_type=='RESELLER')
	  {
		$setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO))?$_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO:0;
			if($setting_value==0)
			{
				$cond .= " AND pm.is_blocked In (0,2) ";
			}
			elseif($setting_value==2)
			{
				if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER")
				{
					$cond .= " AND pm.is_blocked In (0,2) ";
				}
			}
			else
			{
				if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER")
				{
					$cond .= " AND pm.is_blocked In (0,2) ";
				}
			}
	  }
	  if($user_id>0){
		  $cond .= "AND (e.parent_id IN (select employee_id from employee where parent_id=$user_id)
					  OR e.employee_id=$user_id OR e.employee_id IN (select employee_id from employee where parent_id=$user_id))";
		  $join .= "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = prd.product_id  
					INNER JOIN employee e ON pm.employee_id=e.employee_id AND e.status=1"; 
	  }
	  
  
	   $sql_str = "SELECT DISTINCT prd.product_id,prd.pname as product_name,prd.sku,prd.base_price,bs.cas_server_type,bs.display_name
                 FROM eb_products prd 
				 INNER JOIN backend_setups bs ON bs.backend_setup_id=prd.backend_setup_id
				 $join
                 where prd.dealer_id=$dealer_id  AND prd.product_id !=$mapped_product_id
				 AND prd.status=1  AND prd.pricing_structure_type=$price_type AND prd.backend_setup_id=$backend_setup_id
				 $cond order by prd.pname,prd.backend_setup_id";
				   
				   

	  $qry = $this->db->query($sql_str);
	  //echo $this->db->last_query();
	  $mapped_product_result = $qry->result();
	  foreach($mapped_product_result as $mapped_product){
	  	 $upgrade_option .="<option value='".$mapped_product->product_id."' price_type='".$price_type."'>".$mapped_product->product_name." (".$mapped_product->display_name.")</option>";
	  }	
	  

	  
	  echo $upgrade_option;
    	
    }
	/**** Function to get upgarde mapped product to uprade services end  ****/ //---- Suchisnata
	
	public function get_upgrade_mapped_product_details(){
		$obj_products = new productsmodel();
		$obj_channels = new channelsmodel();
		
		$upgrade_product_id = $_POST['upgrade_product_id'];	
		$upgrade_product_details = $obj_products->getproducts($upgrade_product_id);
		$pname = $this->input->post('pname');
		$selectedChannels = $obj_channels->getProductChannels($upgrade_product_id);
		
		
		$html='';
		$html.='<input type="hidden" name="products" id="products" value="'.$upgrade_product_id.'">
		      <input type="hidden" name="From_date" id="From_date" value="'.Date("Y-m-d H:i:s").'">
		     
		      <input type="hidden" name="sku" id="sku" value="'.$upgrade_product_details[0]->sku.'">
		      <input type="hidden" name="pname" id="pname" value="'.$upgrade_product_details[0]->pname.'">
		      <input type="hidden" name="pst" id="pst" value="'.$upgrade_product_details[0]->pricing_structure_type.'">
		      <input type="hidden" name="bp" id="bp" value="'.$upgrade_product_details[0]->base_price.'">
		      <input type="hidden" name="sp" id="sp" value="'.$upgrade_product_details[0]->setup_price.'">
		      <input type="hidden" name="it" id="it" value="'.$upgrade_product_details[0]->is_taxable.'">
		      <input type="hidden" name="tr" id="tr" value="'.$upgrade_product_details[0]->tax_rate.'">
		      <input type="hidden" name="tr2" id="tr2" value="'.$upgrade_product_details[0]->tax2.'">
		      <input type="hidden" name="tr3" id="tr3" value="'.$upgrade_product_details[0]->tax3.'">
		      <input type="hidden" name="taxrate1" id="taxrate1" value="'.$upgrade_product_details[0]->is_taxrate.'">
		      <input type="hidden" name="taxrate2" id="taxrate2" value="'.$upgrade_product_details[0]->is_taxrate1.'">
		      <input type="hidden" name="taxrate3" id="taxrate3" value="'.$upgrade_product_details[0]->is_taxrate2.'">
		      <input type="hidden" name="sd" id="sd" value="'.$upgrade_product_details[0]->short_description.'">
		      <input type="hidden" name="fd" id="fd" value="'.$upgrade_product_details[0]->full_description.'">
		      
			  <input type="hidden" name="scbs" id="scbs" value="'.$upgrade_product_details[0]->sub_change_billing_schedule.'">
			  <input type="hidden" name="ssc" id="ssc" value="'.$upgrade_product_details[0]->sub_service_cancelation.'">
			  <input type="hidden" name="smtc" id="smtc" value="'.$upgrade_product_details[0]->sub_multi_time_cart.'">
			  <input type="hidden" name="pt" id="pt" value="'.$upgrade_product_details[0]->product_thumbnail.'">
			  <input type="hidden" name="co" id="co" value="'.$upgrade_product_details[0]->created_on.'">
			  <input type="hidden" name="cb" id="cb" value="'.$upgrade_product_details[0]->created_by.'">
			  <input type="hidden" name="lmb" id="lmb" value="'.$upgrade_product_details[0]->last_modified_by.'">
			  <input type="hidden" name="lmf" id="lmf" value="'.$upgrade_product_details[0]->last_modified_on.'">
			  
			  <input type="hidden" name="is_multiple" id="is_multiple" value="'.$upgrade_product_details[0]->multiple.'">
			  <input type="hidden" name="advance_billing" id="advance_billing" value="'.$upgrade_product_details[0]->advance_billing.'">
			  <input type="hidden" name="inclusive_of_tax" id="inclusive_of_tax" value="'.$upgrade_product_details[0]->inclusive_of_tax.'">
			  
		
			  <input type="hidden" name="selDefaultSchedule" id="selDefaultSchedule" value="'.$upgrade_product_details[0]->default_schedule.'">
			  <input type="hidden" name="selRecSchedule" id="selRecSchedule" value="'.$upgrade_product_details[0]->recurring_schedule.'">
			  <input type="hidden" name="calendar_month" id="calendar_month" value="'.$upgrade_product_details[0]->is_calendar_month.'">
			  <input type="hidden" name="calendar_type" id="calendar_type" value="'.$upgrade_product_details[0]->calendar_type.'">
			  <input type="hidden" name="selFixedDate" id="selFixedDate" value="'.$upgrade_product_details[0]->fixed_date.'">
			  <input type="hidden" name="chkAlacarte" id="chkAlacarte" value="'.$upgrade_product_details[0]->alacarte.'">
			  <input type="hidden" name="is_yearly" id="is_yearly" value="'.$upgrade_product_details[0]->monthly_or_yearly.'">
			
		      ';
	  
	    foreach($selectedChannels as $selectedChannel) {
		   $html.='<input type="hidden" name="channels['.$selectedChannel->channel_id.']" value="'.$selectedChannel->channel_category_id.'"/>';
						
			}      
		echo $html;
	}
	//method to getting To_date based on quantity ---->By Gopi 
	public function change_quantity_enddate()
	{
		$from_stb_mgmt = ($this->input->post('from_stb_mgmt'))?$this->input->post('from_stb_mgmt'):0;
		$product_type = ($this->input->post('pdct_type'))?$this->input->post('pdct_type'):0;

                $end_date = "";
		if($from_stb_mgmt == 1)
		{
			$productId = ($this->input->post('productId'))?$this->input->post('productId'):0;
			$quantity = ($this->input->post('quantity'))?$this->input->post('quantity'):0;
                        $end_date = ($this->input->post('end_date'))?$this->input->post('end_date'):'';
                        if($end_date!=''){
                            $end_date = Date('Y-m-d', strtotime("+1 day",strtotime($end_date)));
                        }
			$sql_str = "SELECT pricing_structure_type, validity_days,validity_days_type_id FROM eb_products WHERE product_id=$productId AND  dealer_id=".$_SESSION['user_data']->dealer_id;
			$query = $this->db->query($sql_str);
			if($query && $query->num_rows()>0)
			{
				
				$val1 = $query->row()->pricing_structure_type;
				if($val1==1) {
					$validity_days_type_id=$query->row()->validity_days_type_id;
					$validity_days = $query->row()->validity_days;
					if($validity_days>0) {
						if($quantity>0){
							$quantity=$validity_days*$quantity;
						}	
					}
				}else{
				$validity_days_type_id=$query->row()->validity_days_type_id;;
				}
			}else{
				$val = date('Y-m-d',strtotime(date("Y-m-d", strtotime(date('Y-m-d'))) . " +1 year -1 day"));
				$val2 = 2;
			}
						
			
		}else{
			$date_str = ($this->input->post('date_str'))?$this->input->post('date_str'):0;
			$validity_days = ($this->input->post('validity_days'))?$this->input->post('validity_days'):0;
			$validity_days_type_id = ($this->input->post('validity_days_type_id'))?$this->input->post('validity_days_type_id'):0;
			$quantity = ($this->input->post('quantity'))?$this->input->post('quantity'):0;
			$quantity=$quantity*$validity_days;
		}
		//echo $validity_days_type_id; exit;
		if($product_type==2 && $validity_days_type_id==2) //Recurring Quantity edit by niharika 
		{
			$validity_days_type_id=1;
		}
		 
		$new_quantity = 1;
		$new_quantity = ($quantity!='' && is_numeric($quantity) &&  $quantity>0 )? $quantity : $validity_days;
		$val2 = 1;
		$val3 = "";
			if($validity_days_type_id==1)
			{
				//february end date ..
                                if($end_date!=""){
                                    $installation_date = $end_date;
                                }else{
                                    $installation_date = date('Y-m-d');
                                }
				
				//$installation_date = date('2013-12-30');
				//if(date('t',strtotime(date('Y-m-d')))==date('d',strtotime(date('Y-m-d'))) || date('d',strtotime(date('Y-m-d'))) == 30){
				if(date('t',strtotime($installation_date))==date('d',strtotime($installation_date)) || date('d',strtotime($installation_date)) == 30){
					$enddate 				= null;
					$curr_yr 				= date('Y',strtotime($installation_date));
					$curr_month 			= date('m',strtotime($installation_date));
					$curr_day			= date('d',strtotime($installation_date));
					$toatal_months      	=$curr_month + $new_quantity;
					$months      			= ($curr_month + $new_quantity)% 12;
					if($new_quantity <=12){
						$ending_month = $toatal_months;
						$ending_yr = $curr_yr;
						if($ending_month>12){
							$ending_yr = $curr_yr + (floor(($ending_month) / 12));
							$ending_month = $months;
						}
					} else{
						if($new_quantity %12==0){
							$ending_yr = $curr_yr + (floor(($new_quantity) / 12));
							$ending_month = $curr_month;
						} else {
							if($toatal_months%12==0){
								$ending_yr = $curr_yr + (floor(($new_quantity) / 12));
								$ending_month = $curr_month+($new_quantity%12);
							} else {
								$ending_yr = $curr_yr + (floor(($toatal_months) / 12));
								$ending_month = $months;
							}
						}
					}
					//$ending_yr 				= $curr_yr + (floor(($curr_month + $new_quantity) / 12));
					if($ending_month==2){
					$ending_dt 				= cal_days_in_month(CAL_GREGORIAN,$ending_month,$ending_yr);
					} else {
					$ending_dt = $curr_day -01;
					}
					//$ending_dt 				= date('t',strtotime($ending_yr.'-'.$ending_month.'-01'));
					// if($ending_dt != 28 && $ending_dt != 29){
						// $ending_dt = $ending_dt -01;
					// }
					// if(date('d',strtotime($installation_date)) == 30 && $ending_dt == 30){
						// $ending_dt = $ending_dt -01;
					// }
					//echo '<br/>»» End Date: '.$enddate 	= $ending_yr.'-'.$ending_month.'-'.$ending_dt ;
					$val = $enddate 	= $ending_yr.'-'.$ending_month.'-'.$ending_dt ;
				}else{
                                    if($end_date!=""){
                                         $val = Date('Y-m-d', strtotime("+".$new_quantity ."months-1day",strtotime($end_date)));
                                    }else{
                                        $val = Date('Y-m-d', strtotime("+".$new_quantity ."months-1day"));
                                    }
				}
				if(strtotime($val) <= strtotime(Date('y-m-d')))
				{
					$val2=2;
                                        if($end_date!=""){
                                            $val3 = Date('Y-m-d', strtotime("+".$validity_days ."months-1day",strtotime($end_date)));
                                        }else{
                                            $val3 = Date('Y-m-d', strtotime("+".$validity_days ."months-1day"));
                                        }
					
				}else{
					$val2=1;
				}
				
			}elseif($validity_days_type_id==2)
			{
                                if($end_date!=""){
                                    $val = Date('Y-m-d', strtotime("+".$new_quantity ."years-1day",strtotime($end_date)));
                                }else{
                                    $val = Date('Y-m-d', strtotime("+".$new_quantity ."years-1day"));
                                }
				
				if(strtotime($val) <= strtotime(Date('y-m-d')))
				{
					$val2=2;
                                         if($end_date!=""){
                                             $val3 = Date('Y-m-d', strtotime("+".$validity_days ."years-1day",strtotime($end_date)));
                                         }else{
                                             $val3 = Date('Y-m-d', strtotime("+".$validity_days ."years-1day"));
                                         }
					
				}else{
					$val2=1;
				}
			}elseif($validity_days_type_id==3)
			{
                                   if($end_date!=""){
                                       $val = Date('Y-m-d', strtotime("+".$new_quantity ."days-1day",strtotime($end_date)));
                                   }else{
                                       $val = Date('Y-m-d', strtotime("+".$new_quantity ."days-1day"));
                                   }
				
				if(strtotime($val) <= strtotime(Date('y-m-d')))
				{
					$val2=2;
                                        if($end_date!=""){
                                            $val3 = Date('Y-m-d', strtotime("+".$validity_days ."days-1day",strtotime($end_date)));
                                        }else{
                                            $val3 = Date('Y-m-d', strtotime("+".$validity_days ."days-1day"));
                                        }
					
				}else{
					$val2=1;
				}
			}else{
                                if($end_date!=""){
                                    $val = Date('Y-m-d', strtotime("+".$new_quantity ."months-1day",strtotime($end_date)));
                                }else{
                                    $val = Date('Y-m-d', strtotime("+".$new_quantity ."months-1day"));
                                }
				
			}
			//echo Date('y-m-d');
			if(strtotime($val) <= strtotime(Date('y-m-d')))
			{
				$val2=2;
			}else{
			$val2=1;
			}
			
		echo "xxx".$val."xxx".$val2."xxx".$val3;
	}


    public function getLcoDeposite(){ // to get the lco deposite and display in the popup of assign customer ---- Suchisnata
		$did = $_SESSION['user_data']->dealer_id;
		$reseller_id=$_POST['reseller_id'];
		$sql = $this->db->query("select deposit_amount from employee e where users_type='RESELLER' and dealer_id=$did and employee_id=$reseller_id");
		//echo $this->db->last_query();
		$deposite_amount_result = $sql->row();
		$deposite_amount = $deposite_amount_result->deposit_amount;
		
		if($sql && $sql->num_rows()>0)
		echo sprintf('%.2f',$deposite_amount);
		else
		echo 0;
	}
	public function validatePassword(){
		$login_employee_id = $_SESSION['user_data']->employee_id;
		$password = md5($_POST['password']);
		$sql = $this->db->query("select count(employee_id) e from employee where employee_id=$login_employee_id and password = '$password'");
		if($sql){
			echo $sql->row()->e;
		}else{
			echo 0;
		}
	}
	
	public function validateAdditionalPassword()
	{
	
		$user_name = $_SESSION['user_data']->username;
				
		$emp_details = $this->EmployeeModel->getEmployeeDetails(0, $user_name);
		$login_employee_id = $emp_details->employee_id;	
		$addtional_password = md5($_POST['password']);
		$result = $this->change_pass_model->validateAdditionalPassword($login_employee_id,$addtional_password);
	}


	/********************Get customer device info by GOPI***************************/
	public function getCustomerDevice(){
		$options='';
		$customers = new CustomersModel();
		$cust_id = $_POST['cust_id'];
		$customer_devices = $customers->get_customer_devices($cust_id);
		$options.="<option value='-1'>Select</option>";
		 foreach($customer_devices as $customer_device){
                     if($customer_device->stb_blocked_for_lco==1 && ($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER')){
                         continue;
                     } 
		 $options .= '<option stb_number="'.$customer_device->serial_number.'" value="'.$customer_device->stock_id.'" stb_type_id="'.$customer_device->stb_type_id.'">'.$customer_device->serial_number."(".$customer_device->mac_vc_number.")".'</option>';
		 }
		 echo $options;
	}
	/********************Get customer service products info by GOPI***************************/
	public function getCustomerProducts(){
		$options='';
		$product_list = '';
		$did=$_SESSION['user_data']->dealer_id;
		$obj_products = new productsmodel();
		$cust_id = $_POST['cust_id'];
		$stock_id = $_POST['stock_id'];
		$product_id = $_POST['product_id'];
		$stb_type_id = $_POST['stb_type_id'];
		$users_type = $_POST['users_type'];
		$setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO))?$_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO:0;
		$is_user_blocked = isset($_POST['is_user_blocked'])?$_POST['is_user_blocked']:0;
		//query for get the customer service products
		$products_qry = $this->db->query("SELECT DISTINCT `product_id`
						FROM eb_stock_cas_services scs
						INNER JOIN eb_customer_service s ON s.`customer_service_id` = scs.`customer_service_id`
						WHERE s.customer_id =$cust_id
						AND scs.stock_id =$stock_id
						AND scs.deactivation_date IS NULL
						AND s.status =1
						AND s.dealer_id =$did");
		if($products_qry && $products_qry->num_rows>0){
			$products = $products_qry->result();
			foreach($products as $product){
				$product_list .= $product->product_id.",";
			}
			$product_list =rtrim($product_list ,',');
		}else{
			$product_list .=0 ;
		}
		$product_list =$product_list.','.$product_id;
		//query for get the backend set up id
		$qry = $this->db->query("SELECT backend_setup_id FROM eb_stock WHERE stock_id= $stock_id");
		$besid = $qry->row()->backend_setup_id;
		
		$customer_products = $obj_products->get_customerservice_products($cust_id,$besid,$product_list,$did,$stb_type_id,0,$users_type,$setting_value);
		
		
		$options.="<option value='-1' style='color :#315C7C'>Select</option>";
		 foreach($customer_products as $customer_product){
			$taxes='';
			if((isset($_SESSION['dealer_setting']->TAX1)) && $_SESSION['dealer_setting']->TAX1!='TAX1')
			{   
				$taxes .= ($customer_product->is_taxrate==1)?$_SESSION['dealer_setting']->TAX1." ".$customer_product->tax_rate."%, ":$_SESSION['dealer_setting']->TAX1." :Rs.".$customer_product->tax_rate.',';
				
			}
			if((isset($_SESSION['dealer_setting']->TAX2)) && $_SESSION['dealer_setting']->TAX2!='TAX2' )
			{
				$taxes .= ($customer_product->is_taxrate1==1)?$_SESSION['dealer_setting']->TAX2." ".$customer_product->tax2."%, ":$_SESSION['dealer_setting']->TAX2." :Rs.".$customer_product->tax2.',';
				
			}
			if((isset($_SESSION['dealer_setting']->TAX3)) && $_SESSION['dealer_setting']->TAX3!='TAX3')
			{
				$taxes .= ($customer_product->is_taxrate2==1)?$_SESSION['dealer_setting']->TAX3." ".$customer_product->tax3."%, ":$_SESSION['dealer_setting']->TAX3." :Rs.".$customer_product->tax3.',';
				
			}
			if((isset($_SESSION['dealer_setting']->TAX4)) && $_SESSION['dealer_setting']->TAX4!='TAX4')
			{
				$taxes .= ($customer_product->is_taxrate3==1)?$_SESSION['dealer_setting']->TAX4." ".$customer_product->tax4."%, ":$_SESSION['dealer_setting']->TAX4." :Rs.".$customer_product->tax4.',';
				
			}
			if((isset($_SESSION['dealer_setting']->TAX5)) && $_SESSION['dealer_setting']->TAX5!='TAX5' )
			{
				$taxes .= ($customer_product->is_taxrate4==1)?$_SESSION['dealer_setting']->TAX5." ".$customer_product->tax5."%, ":$_SESSION['dealer_setting']->TAX5." :Rs.".$customer_product->tax5.',';
				
			}
			if((isset($_SESSION['dealer_setting']->TAX6)) && $_SESSION['dealer_setting']->TAX6!='TAX6')
			{
				$taxes .= ($customer_product->is_taxrate5==1)?$_SESSION['dealer_setting']->TAX6." ".$customer_product->tax6."%, ":$_SESSION['dealer_setting']->TAX6." :Rs.".$customer_product->tax6.',';
				
			}
			
			if($is_user_blocked==0){
				$font_colour = ($customer_product->base_price>0)?'orange':'#315C7C';
				$font = ($customer_product->base_price>0)?'bold':'normal';
				$options .= '<option value="'.$customer_product->product_id.'" 
				style="color :'.$font_colour.';font-weight:'.$font.'" alacarte_check="'.$customer_product->alacarte.'"base_price="'.$customer_product->base_price.'" show_taxes="'.$taxes.'"show_baseprice="'.$customer_product->show_baseprice.'">'.$customer_product->pname.'</option>';
			 }else{
				//if user is blocked then provide the activation which are having zero bill amount
				$total_amount = $obj_products->CheckAmountForBlockedUser($customer_product);
				
				if($total_amount==0){
				$font_colour = ($customer_product->base_price>0)?'orange':'#315C7C';
				$font = ($customer_product->base_price>0)?'bold':'normal';
				$options .= '<option value="'.$customer_product->product_id.'" style="color :'.$font_colour.';font-weight:'.$font.'" alacarte_check="'.$customer_product->alacarte.'"base_price="'.$customer_product->base_price.'" show_taxes="'.$taxes.'"show_baseprice="'.$customer_product->show_baseprice.'">'.$customer_product->pname.'</option>';
				}
			 }
		 }
		 echo $options;
		
	}
	
	/***********isProductChannelExist By Anurag ************/
	public function isProductChannelExist($stock_id=-1,$other_product_ids='',$productid=0){
		
         
        $al_carte = isset($_POST['al_carte_id'])?$_POST['al_carte_id']:$productid; 
        $stock_id = isset($_POST['stock_id'])?$_POST['stock_id']:$stock_id; 
		//if stock id exist get previous alacarte product channels list By Gopi
		if($stock_id!=-1)
		{
			$product = isset($_POST['other_product_ids'])?$_POST['other_product_ids']:$other_product_ids ;
			$query=$this->db->query("SELECT product_id from eb_stock_cas_services where stock_id=$stock_id AND product_id=$al_carte AND deactivation_date IS NULL");
			$previous_product_exist = $query->result();
			foreach($previous_product_exist as $row){
				 $product = $product.$row->product_id .','; 
			}
			$product = rtrim($product,',');  
		}else{
			$product = isset($_POST['other_product_ids'])?rtrim($_POST['other_product_ids'],','):$other_product_ids;   
		}
		//echo $product;
        $dealer = $_SESSION['user_data']->dealer_id; 
        $query=$this->db->query("SELECT channel_id FROM eb_product_channels WHERE product_id=$al_carte AND dealer_id=$dealer"); 
         
        if($query && $query->num_rows()>0){             
            $channel_id=$query->row()->channel_id;     
             
           // $check_sql=$this->db->query("SELECT COUNT(channel_id)get FROM eb_product_channels WHERE channel_id= $channel_id AND product_id IN($product) AND dealer_id=$dealer");     
            $check_sql=$this->db->query("SELECT channel_id as get FROM eb_product_channels WHERE channel_id= $channel_id AND product_id IN($product) AND dealer_id=$dealer");     
            //echo $this->db->last_query(); 
            if($check_sql && $check_sql->num_rows()>0){ 
                $data= $check_sql->row(); 
                 return $data; 
            }else{ 
                $data= 0; 
            }
			/*if($_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS==0){
				if(isset($_POST['al_carte_id'])){
					echo $data;
				}else{
					return $data;
				}
			}else{
				return 0;
			}*/
			
        }
		
	}
// Any changes done in this function should reflect in apimodel same function
public function isProductChannelExistforEdit($other_products='',$porduct_id=0,$stock_id=-1){ 
         
        $al_carte = (isset($_POST['al_carte_id']))?$_POST['al_carte_id']:$porduct_id; 
        $stock_id = isset($_POST['stock_id'])?$_POST['stock_id']:$stock_id; 
		$data= 0;
		//if stock id exist get previous alacarte product channels list By Gopi
		if($stock_id!=-1)
		{
			$product = (isset($_POST['other_product_ids']))?$_POST['other_product_ids']:$other_products;
			$query=$this->db->query("SELECT product_id from eb_stock_cas_services where stock_id=$stock_id AND deactivation_date IS NULL");
			$previous_product_exist = $query->result();
			foreach($previous_product_exist as $row){
				 $product = $product.$row->product_id .','; 
			}
			$product = rtrim($product,',');  
		}else{
			$product = (isset($_POST['other_product_ids']))?rtrim($_POST['other_product_ids'],','):$other_products;   
		}
		//echo $product;
        $dealer = $_SESSION['user_data']->dealer_id; 
        $query=$this->db->query("SELECT channel_id FROM eb_product_channels WHERE product_id=$al_carte AND dealer_id=$dealer"); 
         
        if($query && $query->num_rows()>0){             
            $channel_id=$query->row()->channel_id;     
             
            $check_sql=$this->db->query("SELECT COUNT(channel_id)get FROM eb_product_channels WHERE channel_id= $channel_id AND product_id IN($product) AND dealer_id=$dealer");     
            //echo $this->db->last_query(); 
            if($check_sql && $check_sql->num_rows()>0){ 
                $data= $check_sql->row()->get; 
            }else{ 
                $data= 0; 
            } 
        }
	if($_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS==0){
		if(isset($_POST['al_carte_id'])){
			echo $data;
		}else{
			return $data;
		}
	}else{
			return 0;
		}
	}
	
	//check duplicate channel existence
	public function checkChannel($stock_id=-1,$productid='',$other_product_ids='')
	{
		$al_carte = isset($_POST['al_carte_id'])?$_POST['al_carte_id']:$productid; 
        $stock_id = isset($_POST['stock_id'])?$_POST['stock_id']:$stock_id; 
		$count=0;
		if($stock_id!=-1)
		{
			$product = (isset($_POST['other_product_ids']))?$_POST['other_product_ids']:$other_product_ids ;
			$query=$this->db->query("SELECT escs.product_id from eb_stock_cas_services escs inner join eb_products ep on ep.product_id = escs.product_id 
			where stock_id=$stock_id AND ep.alacarte=1 AND deactivation_date IS NULL");
			if($query && $query->num_rows()>0)
			{
				$al_carte_pkgs = $query->result();
				foreach($al_carte_pkgs as $pkg)
				{
					$product = $product.$pkg->product_id .','; 
				}
				$product = rtrim($product,','); 
			}
			else
			{
				$product = isset($_POST['other_product_ids'])?rtrim($_POST['other_product_ids'],','):$other_product_ids;
			}
		}
		else
		{
			$product = isset($_POST['other_product_ids'])?rtrim($_POST['other_product_ids'],','):$other_product_ids;
		}
		 $dealer = $_SESSION['user_data']->dealer_id; 
		 $channel_array = array();
		 $sql_str=$this->db->query("SELECT epc.channel_id FROM eb_product_channels epc
		 INNER JOIN eb_products ep ON ep.product_id = epc.product_id
		 WHERE ep.alacarte=1 AND epc.product_id IN ($product) AND epc.dealer_id=$dealer"); 
		 if($sql_str && $sql_str->num_rows()>0)
		 {
			foreach($sql_str->result() as $chanl)
			{
				$channel_array[] = $chanl->channel_id;
			}
		 }
		 $sql_qry = $this->db->query("SELECT channel_id FROM eb_product_channels WHERE product_id=$al_carte AND dealer_id=$dealer");
		if($sql_qry && $sql_qry->num_rows()>0)
		{
			foreach($sql_qry->result() as $channel)
			{
				if(in_array($channel->channel_id, $channel_array)){
					$count++;
				}
			}
		}
		if(isset($_POST['al_carte_id'])){
			echo $count;
		}else{
			return $count;
		}
	
	}
//getting the old customer record for giving more stb's <Anurag>
    public function old_customer_record() {

        $this->load->model('customersmodelnew');
		$this->load->model('wsmodel');
        $last_name = $this->change_pass_model->get_last_name();
        if ($last_name == '') {
            $last_name = 'Last Name';
        }
        $val = '1';
        $value = 0;
        $cus_cond = '';
        $condition = '';
        $hotel_stb_count = 0;
        $dealer = $_SESSION['user_data']->dealer_id;
      
			
		
        $customer_number = isset($_POST['customer_number'])?$_POST['customer_number']:'';
        //echo $customer_number;exit;
        $exist_customer_id = isset($_POST['exist_customer_id']) ? $_POST['exist_customer_id'] : 0;
        //$vc_number = $_POST['vc_number'];
        $vc_number = isset($_POST['vc_number']) ? $_POST['vc_number'] : '';
        $reseller_id = $_POST['reseller_id'];
        $is_surr = $_POST['is_surr']; // for surrender
        //$lco_customer_ids = $_POST['lco_customer_ids']; // for surrender
         $lco_customer_ids = isset($_POST['lco_customer_ids']) ? $_POST['lco_customer_ids'] : '';
		//check individual deposits(deposits on for all lco's or vice versa) - by prasad (13/12/2017)
        $checkindividualdepostits =isset($_SESSION['dealer_setting']->CHECK_INDIVIDUAL_DEPOSITS)?$_SESSION['dealer_setting']->CHECK_INDIVIDUAL_DEPOSITS:0;
        $checkaccesslcodeposits = (isset($checkindividualdepostits) && $checkindividualdepostits ==1)?$this->wsmodel->checklcodeposits($reseller_id):1;
        
        $caf_no = isset($_POST['caf_no'])?$_POST['caf_no']:'';
		
        $is_limit_exceeded = 1;
        $date_format = $data['date_format'] = (isset($_SESSION['dealer_setting']->DATE_FORMAT)) ? $_SESSION['dealer_setting']->DATE_FORMAT : 1;
        $jdate_format = getJqueryDateFormat($date_format);

        $login_users_type = $_SESSION['user_data']->users_type;
        $login_user_parent_type = $_SESSION['user_data']->employee_parent_type;
        $enable_stb_discount = isset($_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT) ? $_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT : 0;
        $show_discount = (isset($_SESSION['dealer_setting']->SHOW_DISCOUNT)) ? $_SESSION['dealer_setting']->SHOW_DISCOUNT : 1; //if 0->Don't show in any login 
        //if 1->Show in MSO login
        //if 2->Show in MSO and LCO logins

        $stb_setting_val = isset($_SESSION['dealer_setting']->EXTRA_BOXES) ? $_SESSION['dealer_setting']->EXTRA_BOXES : 0;
        $is_data_from_master_table = isset($_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE) ? $_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE : 0;
        $commercial_box_count = isset($_SESSION['dealer_setting']->INDUSTRIAL_CUSTOMER_MAX_COUNT) ? $_SESSION['dealer_setting']->INDUSTRIAL_CUSTOMER_MAX_COUNT : 0;


        $oldCustomerDetails = $this->customersmodelnew->getOldCustomerDetails($dealer, $reseller_id, $customer_number, $lco_customer_ids, $vc_number, $is_surr,$tempActivation=0,$caf_flag=0,$show_caf_val=0,$customerId='',$caf_no);
    
     //print_r($oldCustomerDetails);
        /* if($customer_number !=''){
          $customer_number = strtolower($customer_number);
          if(isset($_SESSION['dealer_setting']->SHOW_CAF) && $_SESSION['dealer_setting']->SHOW_CAF==1){
          $cus_cond.= "OR LOWER(c.caf_no)='$customer_number'";
          }else{
          $cus_cond.= "OR REPLACE(LOWER(c.customer_account_id),'-','')= '".str_replace('-','',$customer_number)."' ";

          }
          $condition =" AND (c.customer_id='$customer_number' OR LOWER(c.account_number)='$customer_number' $cus_cond) ";
          }

          if($lco_customer_ids!='')
          {
          $lco_customer_ids = strtolower(trim($lco_customer_ids));
          $condition.=" AND (c.baid='$lco_customer_ids') ";
          }

          if($vc_number !=''){

          //$stock_condition=" AND (s.serial_number='$vc_number' OR s.mac_address='$vc_number' OR s.vc_number='$vc_number')";
          $customer=$this->db->query("SELECT customer_id FROM customer_device WHERE device_closed_on IS NULL AND  (REPLACE(mac_address,':','')='".str_replace (':','',$vc_number)."' OR box_number='$vc_number' OR vc_number='$vc_number')");

          if($customer && $customer->num_rows()>0){
          $condition=" AND c.customer_id=".$customer->row()->customer_id."";
          }

          }


          //For Existing customer
          if($is_surr == 0){




          $query=$this->db->query("SELECT c.*,ct.customer_type,ct.is_commercial_multi_box,it.value,ctt.name as hotel_hospital,
          c.address1,ll.location_name,cd.sales_date,ls.name as state,lc.printable_name,d.district_name,g.group_name,
          CONCAT(e.business_name,' (',e.first_name,' ',e.last_name,')')as emp_name,
          sl.location_id,g.group_id
          FROM customer c
          INNER JOIN employee e ON e.employee_id=c.reseller_id
          LEFT JOIN customer_device cd ON cd.customer_id=c.customer_id
          LEFT JOIN eb_location_locations ll ON ll.location_id = c.city
          INNER JOIN eb_location_states ls ON ls.id = c.state
          INNER JOIN eb_location_countries lc ON lc.iso = c.country
          LEFT JOIN eb_districts d ON d.district_id = c.district
          LEFT JOIN eb_customer_type_of_types ctt ON ctt.customer_type_types_id=c.customer_type_types_id
          INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
          INNER JOIN customer_group cg ON cg.customer_id=c.customer_id
          INNER JOIN groups g ON g.group_id=cg.group_id
          LEFT JOIN eb_customer_types ct ON ct.customer_type_id=c.customer_type_id
          LEFT JOIN eb_id_types it ON it.id_type_id=c.id_type
          WHERE c.dealer_id=$dealer  $condition
          AND cd.device_closed_on IS NULL   AND c.is_lco_transferred=0
          AND c.reseller_id=$reseller_id GROUP BY c.customer_id");
          }else{  //For Reuse surrender customer
          $query=$this->db->query("SELECT c.*,ct.customer_type,ct.is_commercial_multi_box,it.value,ctt.name as hotel_hospital,
          c.address1,ll.location_name,ls.name as state,lc.printable_name,d.district_name,g.group_name,
          CONCAT(e.business_name,' (',e.first_name,' ',e.last_name,')')as emp_name,
          sl.location_id,g.group_id
          FROM customer c
          LEFT JOIN eb_location_locations ll ON ll.location_id = c.city
          INNER JOIN eb_location_states ls ON ls.id = c.state
          INNER JOIN eb_location_countries lc ON lc.iso = c.country
          LEFT JOIN eb_id_types it ON it.id_type_id=c.id_type
          LEFT JOIN eb_districts d ON d.district_id = c.district
          INNER JOIN employee e ON e.employee_id=c.reseller_id
          INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
          INNER JOIN customer_group cg ON cg.customer_id=c.customer_id
          INNER JOIN groups g ON g.group_id=cg.group_id
          LEFT JOIN eb_customer_types ct ON ct.customer_type_id=c.customer_type_id
          LEFT JOIN eb_customer_type_of_types ctt ON ctt.customer_type_types_id=c.customer_type_types_id
          WHERE c.dealer_id=$dealer $condition
          AND c.stb_count = 0 AND c.is_lco_transferred=0 AND c.new_customer_id = 0
          AND c.reseller_id=$reseller_id GROUP BY c.customer_id");
          } */

        if (!empty($oldCustomerDetails)) {
            if ($exist_customer_id > 0) {  //Below value will be returned For Reuse surrender customer 
                $val = '0 xxxx' . $oldCustomerDetails->customer_id;
            } else { //below conditions for existing customer option
				$old_mandal = isset($oldCustomerDetails->mandal_name)?$oldCustomerDetails->mandal_name:'Select Mandal / TALUK';
                if ($oldCustomerDetails->is_commercial_multi_box == 1) {
                    if ($is_data_from_master_table) {
                        //getting number of stb whith lco
                        $hotel_query_for_stb = $this->db->query("SELECT max_box_count FROM eb_customer_type_of_types WHERE customer_type_types_id=" . $oldCustomerDetails->customer_type_types_id . " AND reseller_id=$reseller_id");
                        if ($hotel_query_for_stb && $hotel_query_for_stb->num_rows() > 0) {
                            //$hotel_stb_count = ($hotel_query_for_stb->row()->max_box_count +1);
                            $hotel_stb_count = ($hotel_query_for_stb->row()->max_box_count);
                        }
                        if ($oldCustomerDetails->customer_type_types_id != 0 && $hotel_stb_count > $oldCustomerDetails->stb_count)
                            $is_limit_exceeded = 0;
                    }else {
                        if ($commercial_box_count > $oldCustomerDetails->stb_count)
                            $is_limit_exceeded = 0;
                    }
                }else {
                    if (($stb_setting_val + 1) > $oldCustomerDetails->stb_count)
                        $is_limit_exceeded = 0;
                }

                if ($is_limit_exceeded == 0) {

                    if ($oldCustomerDetails->gender == 1) {
                        $gender = "Male";
                    } else if ($oldCustomerDetails->gender == 2) {
                        $gender = "Female";
                    } else {
                        $gender = '';
                    }
                    if ($oldCustomerDetails->bill_type == 3) {
                        $bill_type = "Pre paid";
                    } else {
                        $bill_type = "Post paid";
                    }

                    $ezyaccount = isset($_SESSION['dealer_setting']->EZYBILL_ACCOUNT) ? $_SESSION['dealer_setting']->EZYBILL_ACCOUNT : 0;
                    $style_margin = ($ezyaccount == 0) ? " height: 40px; margin-bottom:5px; margin-top:5px;" : " height: 20px; margin-bottom:0;";

                    $val = '<br><div class="title" style="' . $style_margin . ' ">
				<div style="margin-bottom:0px !important;float:left;font-size:12px;">Create Customer [ LCO : ' . $oldCustomerDetails->emp_name . '] ';
                    if ($is_surr == 0) {
                        $val .= ', &nbsp;&nbsp;&nbsp;&nbsp; Assigned STB(s) :&nbsp;' . $oldCustomerDetails->stb_count . ',  &nbsp;&nbsp;&nbsp;&nbsp; Maximum STB Count :&nbsp;
					';
                        if ($oldCustomerDetails->is_commercial_multi_box == 1 && isset($_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE) && $_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE == 1) {
                            $val .= ($hotel_stb_count != 0) ? $hotel_stb_count : ($stb_setting_val + 1);
                        } elseif ($oldCustomerDetails->is_commercial_multi_box == 1) {
                            $val .= $commercial_box_count;
                        } else {
                            $val .= ($stb_setting_val + 1);
                        }
                    }

                    $val .= '</div>';


                    $val .= '</div>';

                    if (isset($_SESSION['dealer_setting']->USE_LCO_DEPOSITS) && $_SESSION['dealer_setting']->USE_LCO_DEPOSITS == 1 && $checkaccesslcodeposits ==1) {
                        $val .= '<div style="float:left;margin-left:10px;font-size:14px;"><b>LCO balance deposit amount is Rs. <span class="lco_deposite"></span></b></div>';
                    }
                    $val .= '<div style="clear:both"></div>';

                    $val .= "<div style='padding:3px;'><fieldset><legend>Customer Details : </legend><table width='100%' border='0' align='left' cellspacing='0' cellpadding='0'  style='margin:0px !important;' >";
                    $val .= "<tr>
					<td width='20%' align='left'>Customer type";
                    if ($oldCustomerDetails->is_commercial_multi_box == 1) {
                        $val .= "<div>" . $oldCustomerDetails->customer_type . "</div>";
                    }
                    $val .= "</td>
					<td width='3%'>:</td>
					<td width='30%' align='left'><select name='cus_type' id='cus_type' ><option>" . $oldCustomerDetails->customer_type . "</option></select>
					<input type='hidden' name='customer_id' value='" . $oldCustomerDetails->customer_id . "'>";

                    if ($oldCustomerDetails->is_commercial_multi_box == 1 && $oldCustomerDetails->customer_type_types_id == 0) {
                        $val .= "<div><input type='text' value=" . $oldCustomerDetails->customer_type_description . "></div>";
                    }
                    if ($oldCustomerDetails->is_commercial_multi_box == 1 && $oldCustomerDetails->customer_type_types_id != 0) {
                        $val .= "<div><select disabled><option>" . $oldCustomerDetails->hotel_hospital . "</option></select></div>";
                    }
                    $val .= "</td>";
                    if (isset($_SESSION['dealer_setting']->CAFNO_CREATION) && $_SESSION['dealer_setting']->CAFNO_CREATION == 'MANUAL') {
                        $val .= "<td  width='20%' align='left'>CAF Number</td>
					<td  width='3%'>:</td>
					<td  width='30%' align='left'><input type='text'  value='" . $oldCustomerDetails->caf_no . "'readonly></td></tr> ";
                    }

                    $val .= "<tr>
					<td align='left'>";
                    if (isset($_SESSION['dealer_setting']->IS_LAST_NAME_SHOW) && $_SESSION['dealer_setting']->IS_LAST_NAME_SHOW == 1) {
                        $val .= "First Name";
                    } else {
                        $val .= "Customer Name";
                    }
                    $val .= "	</td>
					<td>:</td>

					<td align='left'><input type='text' ' value='" . $oldCustomerDetails->first_name . "'readonly name='First_Name' id='First_Name'></td> ";
                    if (isset($_SESSION['dealer_setting']->IS_LAST_NAME_SHOW) && $_SESSION['dealer_setting']->IS_LAST_NAME_SHOW == 1) {
                        $val .= "<td align='left'>" . ucwords($last_name) . "</td>
							<td>:</td>
							<td align='left'><input name='Last_Name' id='Last_Name' type='text' value='" . $oldCustomerDetails->last_name . "'readonly></td>
							
						</tr>";
                    } else {
                        $val .= "<td align='left'>Business Name</td>
						<td>:</td>
						<td align='left'><input type='text' value='" . $oldCustomerDetails->business_name . "'readonly></td>
						
					</tr>";
                    }

                    $val .= "<tr>
					<td align='left'>ID Type</td>
					<td>:</td>
					<td align='left'><select disabled><option>" . $oldCustomerDetails->value . "</option></select></td> 
					<td align='left'>ID Number</td><td>:</td><td align='left'><input type='text'  value='" . $oldCustomerDetails->id_number . "' readonly></td>
				</tr>";
                    $val .= "<tr>
					<td align='left'>Gender</td>
					<td>:</td>
					<td align='left'><select disabled><option>" . $gender . "</option></select></td>
					<td  width='20%'align='left'>Father's Name</td>
					<td  width='3%' >:</td>
					<td  width='30%' align='left'><input type='text'  value='" . $oldCustomerDetails->fathers_name . "'readonly></td>
				</tr>";
                    $val .= "<tr>
					<td align='left'>Group</td>
					<td>:</td>
					<td align='left'><select disabled><option>" . $oldCustomerDetails->group_name . "</option></select></td>
					<td>Sales Date</td>
					<td>:</td>
					<td>
					<input type='text' name='existing_sales_date' id='existing_sales_date'  readonly  onclick='check_sales_date()' />
					</td>
					
				</tr>";
                    $val .= "<tr>
					<td align='left'>Longitude</td>
					<td>:</td>
					<td align='left'><input type='text'  value='" . $oldCustomerDetails->longitude . "'readonly></td>
					<td align='left'>Latitude</td>
					<td>:</td><td align='left'><input type='text'  value='" . $oldCustomerDetails->latitude . "'readonly></td>
				</tr>";
                    if (isset($_SESSION['dealer_setting']->SHOW_CUSTOMER_BILL_TYPE) && $_SESSION['dealer_setting']->SHOW_CUSTOMER_BILL_TYPE == 1) {
                        $val .= "<tr>
					<td align='left'>Bill Type</td>
					<td>:</td>
					<td align='left'><select disabled><option>" . $bill_type . "</option></select></td>
					
                                 </tr>";
                    }

                    if (isset($show_discount) && $show_discount != 0) {
                        if ($show_discount == 1 && ($login_users_type == 'DEALER' || $login_users_type == 'ADMIN' || ($login_users_type == 'EMPLOYEE' && ($login_user_parent_type == '' || $login_user_parent_type == NULL))) || ($show_discount == 2)) {
                            if (isset($enable_stb_discount) && $enable_stb_discount == 1) {
                                $val .= "<tr>
								<td align='left'>Discount Amount</td>
								<td>:</td><td align='left'><input type='text' name='txtDiscount_ff' id='txtDiscount_f' value='" . $oldCustomerDetails->discount . "'></td>
								</tr>  ";
                            } else {
                                $val .= "<tr>
								<td align='left'>Discount Percentage</td>
								<td>:</td><td align='left'><input type='text'  name='txtDiscount_ff' id='txtDiscount_f' value='" . $oldCustomerDetails->discount . "'readonly></td>
								</tr>  ";
                            }
                        }
                    }
                    $val .= "<tr>
                    <td align='left'>languages</td>
                    <td>:</td>
                    <td><select disabled>                    
                    <option value='".$oldCustomerDetails->language_id."'>".$oldCustomerDetails->language."</option>                   
                    </select></td></tr>";

                    $val .= "</table></fieldset></div>";

                    $val .= "<div style='padding:3px;'><fieldset><legend>Address Details</legend>
				<table width='100%' border='0' align='left' cellspacing='0' cellpadding='0'  style='margin:0px !important;' ><tr>
					<td  width='20%' align='left'>Country</td>
					<td width='3%'>:</td>
					<td  width='30%' align='left'><select id='country' name='country' class='country' disabled><option>" . $oldCustomerDetails->printable_name . "</option></select></td>
					<td  width='20%' align='left'>State</td>
					<td  width='3%' >:</td>
					<td  width='30%' align='left'><select id='state' class='states' name='state' disabled><option>" . $oldCustomerDetails->state . "</option></select></td>
					
				</tr>";

                    $val .= "<tr>
					<td align='left'>District</td>
					<td>:</td>
					<td align='left'><select id='district' name='district' class='districts' disabled><option>" . $oldCustomerDetails->district_name . "</option></select></td> 
					<td align='left'>Mandal/Taluk</td><td>:</td><td align='left'><select disabled><option>" . $old_mandal . "</option></select></td>

				</tr>";
                    //country_code
                    $country_code = substr($oldCustomerDetails->mobile_no, 0, 2);
                    $mobile = substr($oldCustomerDetails->mobile_no, 2);
                    $val .= "<tr>
                    <td align='left'>City</td>
                    <td>:</td>
                    <td align='left'><select disabled><option>" . $oldCustomerDetails->location_name . "</option></select></td>
                    
					<td align='left'>Phone</td>
					<td>:</td>
					<td align='left'><input type='text'  value='" . $oldCustomerDetails->phone_no . "'readonly></td> 
					
				</tr>";
                    $val .= "<tr>
                    <td align='left'>Mobile</td>
                    <td>:</td>
                    <td align='left'><input type='text' name='country_code' style='width:30px; background:#9FDEF8;'value='" . $country_code . "' readonly> <input type='text' id='mobile_no' name='mobile_no' style='width:114px;'value='" . $mobile . "'readonly></td>
					<td align='left'>Email ID</td>
					<td>:</td>
					<td align='left'><input type='text'  value='" . $oldCustomerDetails->email . "'readonly></td> 
					
					
				</tr>";
                    //getting the date of burth in year  month  date formate  ayear			
                    $date = explode('-', $oldCustomerDetails->date_of_birth);
                    $adate = explode('-', $oldCustomerDetails->anniversary_date);
                    $val .= "<tr>
					<td align='left'>Date Of Birth</td>
					<td>:</td>";
                    if ($date[0] == '0000' || $oldCustomerDetails->date_of_birth == '') {
                        $val .= "<td align='left'><select style='width:55px;' disabled><option value='-1'>YYYY</option></select><select style='width:55px;' disabled><option value='-1'>MM</option></select><select name='date' style='width:55px;' id='date' disabled><option value='-1'>DD</option></select></td>";
                    } else {
                        $val .= "<td align='left'><select style='width:55px;' disabled><option value='-1'>" . $date[0] . "</option></select><select style='width:55px;' disabled><option value='-1'>" . $date[1] . "</option></select><select name='date' style='width:55px;' id='date' disabled><option value='-1'>" . $date[2] . "</option></select></td>";
                    }
                    $val .= "<td align='left'>Anniversary Date</td><td>:</td>";
                    if ($date[0] == '0000' || $oldCustomerDetails->anniversary_date == '') {
                        $val .= "<td align='left'><select style='width:55px;' disabled><option value='-1'>YYYY</option></select><select style='width:55px;' disabled><option value='-1'>MM</option></select><select name='date' style='width:55px;' id='date' disabled><option value='-1'>DD</option></select></td></tr>";
                    } else {
                        $val .= "<td align='left'><select style='width:55px;' disabled><option value='-1'>" . $adate[0] . "</option></select><select style='width:55px;' disabled><option value='-1'>" . $adate[1] . "</option></select><select name='date' style='width:55px;' id='date' disabled><option value='-1'>" . $adate[2] . "</option></select></td></tr>";
                    }

                    $val .= "<tr>
                    <td align='left'>PIN</td><td>:</td><td align='left'><input type='text' id='Pin_Code' class='txtBox-new' name='Pin_Code'  value='" . $oldCustomerDetails->pin_code . "'readonly></td>
					<td align='left'>Billing Address</td>
					<td>:</td>
					<td align='left'><textarea id='Address1' name='Address1' readonly>" . $oldCustomerDetails->address1 . "</textarea></td>
					
					
					</tr>";
                    $val .= "<tr>
                    <td align='left'>Installation Address</td>
                    <td>:</td>
                    <td align='left'><textarea id='installation_address' name='installation_address' readonly>" . $oldCustomerDetails->installation_address . "</textarea></td>
					<td align='left'>Remarks</td>
					<td>:</td>
					<td align='left'><textarea  readonly>" . $oldCustomerDetails->remarks . "</textarea></td> 
					<input type='hidden' name='payment_amount' id='payment_amt' value='" . $oldCustomerDetails->balance . "'>
				</tr>";
                    $val .= "</table></fieldset></div>";
                    $val .= "<script>
				$( document ).ready(function() {
				var  sales_date = $('#existing_sales_date').val();
				 $('#existing_sales_date').datepicker({
                         dateFormat:'$jdate_format',
                        changeMonth: true
                });
                });
				</script>";
                } else {
                    //if($oldCustomerDetails->customer_type_id==1 || $oldCustomerDetails->customer_type_id==8 ){
                    $val = 2;
                    //}
                }
            }
        } else {

            $val = '';
        }

        echo $val;
    } 
	
	//repair defective or surrendered stb <Anurag>
	public function repair_stb(){
				
		$employee_id = $_SESSION['user_data']->employee_id;
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$stock_id=$_POST['stock_val'];
		$status=$_POST['status_val'];
		$remark=$_POST['remark'];
		$for_trash=$_POST['for_trash'];
		$result = $this->dasmodel->updateRepairStb($for_trash,$status,$dealer_id,$employee_id,$stock_id,$remark);
		echo $result;
				
	}
       
	//Satyan - For CAF Number Validation At CAF Entry
	public function isCAFUnique(){
	
		if(!isset($_POST['caf'])) {echo 0; exit;}
		$chk = mysql_real_escape_string($_POST['caf']);
		$did = $_SESSION['user_data']->dealer_id;
		$sql_str = "SELECT COUNT(caf_no) cnt FROM customer WHERE caf_no='$chk' AND dealer_id=$did";
		$query = $this->db->query($sql_str);	
		//echo $sql_str;
		if($query){
			$row =$query->row();
			if($row->cnt > 0) {echo 0;exit;}
			else {echo 1;exit;}
		}else{
			echo 0;exit;
		}
	}

	
	// Get active remarks in popup ----- STB Management ---- Suchisnata
	public function getActiveRemarks(){
		if(isset($_POST['stock_id']) && $_POST['stock_id']!=''){
		$stock_id=$_POST['stock_id'];
		}else{
		$stock_id='';	
		}
		$action=$_POST['action'];
		if(isset($_POST['package']) && $_POST['package']!=''){
		 $package=$_POST['package'];
		}
		if(isset($_POST['backend_setup_id']) && $_POST['backend_setup_id']!=''){
		$backend_setup_id=$_POST['backend_setup_id'];
		}
		$html='';
		$option='';
		
		if($stock_id!=''){
		$query =$this->db->query("SELECT s.serial_number,CASE bs.use_mac WHEN '1' THEN s.mac_address ELSE s.vc_number END AS vc_number
		                         FROM eb_stock s
		                         LEFT JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id 
		                         WHERE s.stock_id=$stock_id");
		$stb_details=$query->row();
		}
		if(isset($_SESSION['activation']) && count($_SESSION['activation'])>0){
			foreach($_SESSION['activation'] as $k=>$v){
				  if ($k != 22) { //Temporary Activation comparing with key changed by chakri
					$option.="<option value='".$k."'>".$v."</option>";	
				  }
			}
		}
		if(isset($_POST['backend_setup_id']) && $_POST['backend_setup_id']!=''){
		$serial_no_label = (isset($_SESSION['cas_term']['for_serialno'][$backend_setup_id]) ? $_SESSION['cas_term']['for_serialno'][$backend_setup_id] : 'Serial Number');
		$vc_mac_label = (isset($_SESSION['cas_term']['vc_number'][$backend_setup_id])) ? ($_SESSION['cas_term']['vc_number'][$backend_setup_id]) : (isset($_SESSION['mac_vc_column'])?$_SESSION['mac_vc_column']:'VC Number');
		}
		if (isset($_POST['product']) && $_POST['product'] != '') {
            $product = $_POST['product'];
        } else {
            $product = '';
        }
        if (isset($_POST['cname']) && $_POST['cname'] != '') {
            $cname = $_POST['cname'];
        } else {
            $cname = '';
        }
        if (isset($_POST['product_charges']) && $_POST['product_charges'] != '') {
            $product_charges = $_POST['product_charges'];
        } else {
            $product_charges = '';
        }
        $html .= "<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
		if($stock_id!=''){
		$html.="   <tr>
		               <td align='left'>$serial_no_label</td>
		               <td align='center'>:</td>
		               <td align='left'>
		               <input type='hidden' name='stockid' id='stockid' value='".$stock_id."'>
		               <input type='hidden' name='action' id='action' value='".$action."'>
		               <input type='hidden' name='package' id='package' value='".$package."'>
		               <input type='hidden' name='serial' id='serial' value='".$stb_details->serial_number."'>
		               ".$stb_details->serial_number."</td>
		            </tr>
		            <tr>
		               <td align='left'>$vc_mac_label</td>
		               <td align='center'>:</td>
		               <td align='left'>".$stb_details->vc_number."</td>
		            </tr>";
		}	
		if ($product != '') {
			$html .= " <input type='hidden' name='product' id='product' value='".$product."'>
			<input type='hidden' name='product_charges' id='product_charges' value='".$product_charges."'>
			<input type='hidden' name='cname' id='cname' value='".$cname."'>";
		}

        $html.="<tr>
		           <td align='left'>Activation Reason</td>
		           <td align='center'>:</td>
		           <td align='left'>
		             <select name='activation_reason' id='activation_reason'>
		               <option value=''>Select Reason</option>
		               ".$option."
		             </select>
		           </td>
        		</tr>
		        <tr>
		          <td colspan='3'>&nbsp;</td>
		        </tr>
		        <tr>
		          <td align='left'>Remarks</td>
		          <td align='center'>:</td>
		          <td align='left'><textarea name='remark_txt' id='remark_txt'></textarea></td>
		        </tr>
	    	</table>";
	     
		       
		
		echo $html;
	}
	
	public function getDeactiveRemarks(){
		$dealer_id=$_SESSION['user_data']->dealer_id;
		$stock_id=$_POST['stock_id'];
		if(isset($_POST['serial']) && $_POST['serial']!=''){
			$serial=$_POST['serial'];
		}
		if(isset($_POST['show_osd']) && $_POST['show_osd']>0){
			$show_osd=$_POST['show_osd'];
		}else{
			$show_osd=0;
		}
		
		$action=$_POST['action'];
                $is_from_deact=isset($_POST['is_from_deact'])?$_POST['is_from_deact']:0;
                 $is_from_temp_deact=isset($_POST['is_from_temp_deact'])?$_POST['is_from_temp_deact']:0;
		$backend_setup_id=0;
		$serial_number = "";
		$serial_id = "";
		$vc_number = "";
		$html='';
		$option='';
		
		$serial_no_label = (isset($_SESSION['cas_term']['for_serialno'][$backend_setup_id]) ? $_SESSION['cas_term']['for_serialno'][$backend_setup_id] : 'Serial Number');
		$vc_mac_label = (isset($_SESSION['cas_term']['vc_number'][$backend_setup_id])) ? ($_SESSION['cas_term']['vc_number'][$backend_setup_id]) : ($_SESSION['mac_vc_column']);
		
		if($stock_id!=''){
			$stock_ids = explode(',',$stock_id);
			foreach($stock_ids as $stock_id){
				$query =$this->db->query("SELECT s.serial_number,CASE bs.use_mac WHEN '1' THEN s.mac_address ELSE s.vc_number END AS vc_number
				 FROM eb_stock s
				 LEFT JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id 
				 WHERE s.stock_id=$stock_id");
				$stb_details=$query->row();
				$serial_number.=$stb_details->serial_number.','.'<br/>';
				$vc_number.=$stb_details->vc_number.','.'<br/>';
				$serial_id.=$stock_id.',';
			}
			$serial_number = rtrim($serial_number,', <br/>');
			$vc_number = rtrim($vc_number,', <br/>');
			$serial_id = rtrim($serial_id,',');
		
		}
		if(isset($_SESSION['deactivation'])){
		foreach($_SESSION['deactivation'] as $k=>$v){
                    $arr_reason_details = $this->DasModel->getReasonDetails($k);
                    $int_action_type = isset($arr_reason_details->action_type)?$arr_reason_details->action_type:0;
                    $int_global_reason = isset($arr_reason_details->global_reason)?$arr_reason_details->global_reason:0;
                    if($is_from_deact>0){
                         $action_type = $int_action_type;
                    }else{
                        $action_type = 0;
                    }
                  //check the condition for temporary deactivation
                   if (isset($int_global_reason) && $int_global_reason == 1) {//'Temporary Deactivation' code added by Venkat 17-11-2020
                       if($is_from_temp_deact>0){
                           $is_temp_exist = 1;
                       }else{
                           $is_temp_exist = 0;
                       }
                       
                   }else{
                       $is_temp_exist=1;
                   }
                   
                   //get action type to check the condition for unpaid customer by GOPI
                   if($action_type!=2){
                       if($is_temp_exist==1){
                            $option.="<option value='".$k."'>".$v."</option>"; 
                       }
                   }
		}
	}
		
		
		$html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
		          if($stock_id !=''){
		            $html.="<tr>
		               <td align='left'>".$serial_no_label."</td>
		               <td align='center'>:</td>
		               <td align='left'>
		               <input type='hidden' name='stockid' id='stockid' value='".$serial_id."'>
		               <input type='hidden' name='action' id='action' value='".$action."'>
		               <input type='hidden' name='serial' id='serial' value='".$serial."'>
		                ".$serial_number."</td>
		            </tr>
		            <tr>
		               <td align='left'>".$vc_mac_label."</td>
		               <td align='center'>:</td>
		               <td align='left'>".$vc_number."</td>
		            </tr>";
		            } 
		            if(isset($_POST['customer_id']) && $_POST['customer_id']!='' && isset($_POST['csid']) && $_POST['csid'] !=''){
		             $html.="<input type='hidden' name='customer_id' id='customer_id' value='".$_POST['customer_id']."'>
		                     <input type='hidden' name='csid' id='csid' value='".$_POST['csid']."'>";	
		            }
		           $html.="<tr>
		               <td align='left'>Deactivation Reason<span class=important_field>*</span></span></td>
		               <td align='center'>:</td>
		               <td align='left'>
		                 <select name='deactivation_reason' id='deactivation_reason'>
		                   <option value=''>Select Reason</option>
		                   ".$option."
		                 </select>
		               </td>
		            </tr>
		            <tr>
		              <td align='left'>Remarks</td>
		              <td align='center'>:</td>
		              <td align='left'><textarea name='remark_txt' id='remark_txt'></textarea></td>
		            </tr>
					<tr>";
					$setting='SEND_OSD_AFTER_DEACTIVATION';
					$lovValue = $this->change_pass_model->getLovValue($setting,$dealer_id);
					if($lovValue>0 && $show_osd>0){
						$html.=" <td colspan='2'></td>
						  <td align='left'> <input style='text-align:center; vertical-align:middle' type='checkbox' name='send_osd' id='send_osd' value='1'> Send OSD</td>
						</tr>";
					}   
		       $html.="</table>";
		
		echo $html;
	}
	public function getReactiveRemarks(){
		$stock_id=$_POST['stock_id'];
		$action=$_POST['action'];
		$backend_setup_id=$_POST['backend_setup_id'];
		
		$serial_no_label = (isset($_SESSION['cas_term']['for_serialno'][$backend_setup_id]) ? $_SESSION['cas_term']['for_serialno'][$backend_setup_id] : 'Serial Number');
		$vc_mac_label = (isset($_SESSION['cas_term']['vc_number'][$backend_setup_id])) ? ($_SESSION['cas_term']['vc_number'][$backend_setup_id]) : ($_SESSION['mac_vc_column']);
		
		$html='';
		$option='';
		
		$query =$this->db->query("SELECT s.serial_number,CASE bs.use_mac WHEN '1' THEN s.mac_address ELSE s.vc_number END AS vc_number
		                         FROM eb_stock s
		                         LEFT JOIN backend_setups bs ON bs.backend_setup_id = s.backend_setup_id 
		                         WHERE s.stock_id=$stock_id");
		$stb_details=$query->row();
		
		foreach($_SESSION['reactivation'] as $k=>$v){
		   $option.="<option value='".$k."'>".$v."</option>";	
		}
		
		
		$html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>
		            <tr>
		               <td align='left'>".$serial_no_label."</td>
		               <td align='center'>:</td>
		               <td align='left'>
		               <input type='hidden' name='stockid' id='stockid' value='".$stock_id."'>
		               <input type='hidden' name='action' id='action' value='".$action."'>
		               ".$stb_details->serial_number."</td>
		            </tr>
		            <tr>
		               <td align='left'>".$vc_mac_label."</td>
		               <td align='center'>:</td>
		               <td align='left'>".$stb_details->vc_number."</td>
		            </tr>
		            <tr>
		               <td align='left'>Reactivation Reason</td>
		               <td align='center'>:</td>
		               <td align='left'>
		                 <select name='reactivation_reason' id='reactivation_reason'>
		                   <option value=''>Select Reason</option>
		                   ".$option."
		                 </select>
		               </td>
		            </tr>
		            <tr>
		              <td align='left'>Remarks</td>
		              <td align='center'>:</td>
		              <td align='left'><textarea name='remark_txt' id='remark_txt'></textarea></td>
		            </tr>
		            
		        </table>";
		
		echo $html;
	}
	public function details_of_stbs(){
		$html ='';
		$serial_label=isset($_SESSION['cas_term']['for_serialno'][0]) ? $_SESSION['cas_term']['for_serialno'][0] :'Serial Number';
		$mac_label=(isset($_SESSION['cas_term']['vc_number'][0]) ? $_SESSION['cas_term']['vc_number'][0] : $_SESSION['mac_vc_column']);
		$customer_id=$_POST['customer_id'];
		$date_format = (isset($_SESSION['dealer_setting']->DATE_FORMAT))?$_SESSION['dealer_setting']->DATE_FORMAT:1;
			
		$query=$this->db->query("SELECT s.serial_number,bs.cas_server_type,bs.display_name,
		CASE bs.use_mac WHEN 1 THEN s.mac_address ELSE s.vc_number END AS mac_vc_number,st.type,st.display_name AS stb_display_name,cd.sales_date
		FROM customer_device cd 
		INNER JOIN eb_stock s ON s.serial_number=cd.box_number 
		INNER JOIN eb_stb_types st ON st.stb_type_id =s.stb_type_id
		INNER JOIN backend_setups bs ON s.backend_setup_id=bs.backend_setup_id 
		WHERE cd.customer_id=$customer_id AND cd.device_closed_on IS NULL");
		if($query && $query->num_rows()>0){
			$html .="<table  cellpadding='10' cellspacing='10' border='0'>";
			$html .="<tr align='left'>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>".$serial_label."</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>".$mac_label."</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>CAS</b></th>";			
			$html .="<th style='color:#315C7C;font-size:14px;'><b>STB Type</b></th>";	
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Sales Date</b></th>";			
			$html .="</tr>";
			foreach($query->result() as $record){
				if($record->sales_date!='0000-00-00')
				{
			     $sales_date = getDateFormat($record->sales_date,$date_format);
				}else
				{
					$sales_date = "NA";
				}
			
				$html .="<tr align='left'>";
				$html .="<td style='background-color:white;'>".$record->serial_number."</td>";
				$html .="<td style='background-color:white;'>".$record->mac_vc_number."</th>";
				$html .="<td style='background-color:white;'>".$record->display_name."</td>";			
				$html .="<td style='background-color:white;'>".$record->stb_display_name."</td>";
				$html .="<td style='background-color:white;'>".$sales_date
				."</td>";				
				$html .="</tr>";
			}			
			$html .="</table>";
			
		}
		echo $html;
	}
	/******function for cheque number validation By Gopi********************/
	public function validateCheque(){
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$chq_dd_number = $_POST['chq_dd'];
		$sql = $this->db->query("select count(payment_id) id from acc_lco_payments  where dealer_id=$dealer_id and instrument_number = '$chq_dd_number'");
		if($sql){
			echo $sql->row()->id;
		}else{
			echo 0;
		}
	}

	//Get the package of broadcaster <Anurag>
	public function get_package(){
		$join='';$condition='';$val='';$package_id='';
		$broad_id = $_POST['broad_id'];
		$package_id = $_POST['package_id'];
		$val='';
		if($broad_id != 'all_broadcaster' && $broad_id != -1){
			$cond=" AND b.broadcaster_id=$broad_id ";
		}
		if($broad_id == 'all_broadcaster'){
			$cond="";
		}		
		$val .= "<option value='-1'>Select</option>";
                 if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                    $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		//Below condition check for distributor login or subdistributor login----TRIDEEP
		if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR") { 
			$join ="INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = ep.product_id
					INNER JOIN employee e ON e.employee_id = rpm.employee_id
					INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
			$condition .= " (e.parent_id =$int_employee_id OR e1.parent_id =$int_employee_id) AND ";  
		}
		if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
			$join = "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = ep.product_id AND pm.employee_id=$int_employee_id";
		}
		$qry = $this->db->query("SELECT ep.product_id,ep.pname FROM eb_products ep 
		INNER JOIN eb_product_channels epcs ON ep.product_id=epcs.product_id		
		INNER JOIN eb_broadcaster_channels bc ON epcs.channel_id = bc.channel_id
		INNER JOIN eb_broadcasters b ON b.broadcaster_id = bc.broadcaster_id
		$join
		WHERE ep.dealer_id=".$_SESSION['user_data']->dealer_id." $cond GROUP BY ep.product_id ORDER BY ep.pname");		
		if($qry && $qry->num_rows()>0)
		{			
			foreach($qry->result() as $record){				
				$val .= '<option value="'.$record->product_id.'"'.(($package_id==$record->product_id)?' selected="SELECTED"':'').'>'.$record->pname.'</option>';
			}
		}
		echo $val;
		
	}

	/********check the status for stb or customer in stb management by Gopi**********/
	public function check_customer_stb_status(){
		$val='';
		$value=0;
		$cus_cond='';
		$condition='';
			
		$dealer = $_SESSION['user_data']->dealer_id;
        $customer_number = isset($_POST['customer_number'])?$_POST['customer_number']:'';
        $vc_number = isset($_POST['vc_number'])?$_POST['vc_number']:'';
        $reseller_id = isset($_POST['reseller_id'])?$_POST['reseller_id']:0;
        $is_surr = $_POST['is_surr'];
        $lco_customer_ids = isset($_POST['lco_customer_ids'])?$_POST['lco_customer_ids']:'';
        $caf_no = isset($_POST['caf_no'])?$_POST['caf_no']:'';
        $extra_boxes = isset($_POST['extra_boxes'])?$_POST['extra_boxes']:0;
		
		$lov_setting='VERIFICATION_PERIOD';
                $verification_period  = $this->change_pass_model->getLovValue($lov_setting,$dealer);
                 //check the condition for not verified customers
                $is_verified_customer=1;
                $today = date('Y-m-d');
				$condition .= " AND c.reseller_id=$reseller_id  ";
		if($customer_number !='' || $lco_customer_ids!='' || $caf_no != ''){
		
			 if ($customer_number != '') {
                $customer_number = strtolower(trim($customer_number));
                if (isset($_SESSION['dealer_setting']->SHOW_CAF) && $_SESSION['dealer_setting']->SHOW_CAF == 1) {
                    $cus_cond .= "c.customer_id='$customer_number' OR LOWER(c.account_number)='$customer_number' OR LOWER(c.caf_no)='$customer_number'";
                } else {
                    $cus_cond .= "c.customer_id='$customer_number' OR REPLACE(LOWER(c.customer_account_id),'-','')= '" . str_replace('-', '', $customer_number) . "' ";
                }
            } else if ($lco_customer_ids != '') {
                $lco_customer_ids = strtolower(trim($lco_customer_ids));
                $cus_cond .= " LOWER(c.baid)='$lco_customer_ids'";
            }else if ($caf_no != '') {
                
                $cus_cond .= " c.caf_no='$caf_no'";
            }
            if($extra_boxes == 0 && $is_surr == 0)
            {
                $condition .= " AND c.stb_count = 0 ";
            }
			$customer_qry = $this->db->query("select c.stb_count,c.status,c.reseller_id,c.is_lco_transferred,c.new_customer_id,c.is_verified,c.created_date,c.customer_id,c.total_bill_amount,c.total_paid_amount from customer c where ( $cus_cond) $condition ");
			if($customer_qry && $customer_qry->num_rows()>0){
                            
                                //check customer verification
                                if($customer_qry->row()->is_verified==0){
                                    $created_date = $customer_qry->row()->created_date;
                                   
                                     if($verification_period>0){
                                        $customer_verification_period = Date('Y-m-d', strtotime("+$verification_period day",strtotime($created_date))); 
                                         if(strtotime($today)>strtotime($customer_verification_period)){
                                             $is_verified_customer=0;
                                         }
                                     }
                                }
                                
				if($is_surr ==0){
					/*if($customer_qry->row()->stb_count==0){
						echo 2;//if stb count is 0
						exit;
					}else */
                                        if($customer_qry->row()->status==0){
						echo 3;//if customer status is deactivated
						exit;
					}else if($customer_qry->row()->reseller_id!=$reseller_id){
						echo 4;//reseller is not same
						exit;
					}
				}
				else if($is_surr ==1){
					if($customer_qry->row()->reseller_id!=$reseller_id){
						echo 4;//reseller is not same
						exit;
					}//$customer_qry->row()->is_lco_transferred == 0 && $customer_qry->row()->new_customer_id == 0 && 
					else if($customer_qry->row()->is_lco_transferred == 0 && $customer_qry->row()->new_customer_id == 0 && $customer_qry->row()->stb_count==0 ){
						echo 1;//success for surrender
						exit;
					}else{
						echo 11;//fail surrender
						exit;
					}
				}
                                if($is_verified_customer==0){
                                    echo 12;//if customer is deactivated due to non verification
                                    exit;
                                }
				
			}else{
				//echo $this->db->last_query();
				echo 5;//customer id doesn't exist
				exit;
			}
			
			
		}
		if($vc_number !=''){


			$stb_qry = $this->db->query("SELECT COALESCE(c.reseller_id,'')as reseller_id, COALESCE(c.is_verified,0)as is_verified,COALESCE(c.created_date,'')as created_date,s.isSurrended,s.is_trash,s.defective_stock,COALESCE(c.status, '0')as status,COALESCE(c.customer_id, '0')as customer_id FROM eb_stock s 

			LEFT JOIN customer_device cd ON cd.box_number = s.serial_number AND device_closed_on IS NULL 
			LEFT JOIN customer c ON c.customer_id = cd.customer_id WHERE 
			(REPLACE(s.mac_address,':','')='".str_replace(':','',$vc_number)."' OR s.serial_number='$vc_number' OR s.vc_number='$vc_number') $condition ");
			
			//echo $this->db->last_query();
			if($stb_qry && $stb_qry->num_rows()>0){
                                 //check customer verification
                                if($stb_qry->row()->is_verified==0){
                                    $created_date = $stb_qry->row()->created_date;
                                   
                                     if($verification_period>0){
                                        $customer_verification_period = Date('Y-m-d', strtotime("+$verification_period day",strtotime($created_date))); 
                                         if(strtotime($today)>strtotime($customer_verification_period)){
                                             $is_verified_customer=0;
                                         }
                                     }
                                }
                             
				if($stb_qry->row()->is_trash==1){
					echo 6;//stb is in trash
					exit;
				}else if($stb_qry->row()->defective_stock==1){
					echo 7;//stb is defective
					exit;
				}else if($stb_qry->row()->isSurrended!=2){
					echo 8;//stb is surrendered
					exit;
				}else if($stb_qry->row()->reseller_id!=$reseller_id){
					echo 4;//reseller is not same
					exit;
				}else if($stb_qry->row()->customer_id==0){
					echo 9;//if customer is not there for STB
					exit;
				}else if($stb_qry->row()->status==0 || $stb_qry->row()->status==""){
					echo 3;//if customer is deactive state
					exit;
				}
                                if($is_verified_customer==0){
                                    echo 12;//if customer is deactivated due to non verification
                                    exit;
                                }
							
			}else{
				echo 10;//stb doesn't exist
				exit;
			}

		}
		
		echo 1;
    }
	/******function for check lco outstanding month By Gopi********************/
	public function check_lco_outstanding_month(){
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$month = $_POST['month'];
		$lco_id = $_POST['lco_id'];
		$month = rtrim($month,',');
		$date_month = explode(',',$month);
		$check = 0;
		foreach($date_month as $month){
			$month_format = date('01-'.$month);
			$month = date('Y-m-d',strtotime($month_format));
			$sql = $this->db->query("select count(out_standing_id) id from eb_reseller_outstanding_amounts  where  date(out_standing_month) = '$month' and reseller_id = $lco_id and (total_bill<>0.00 or total_payment<>0.00)");
			//echo $this->db->last_query();
			if($sql && $sql->row()->id > 0){
				$check++;
			}
		}
		if($check>0){
			echo $check;
		}else{
			echo 0;
		}
	}
	/******function for check edit lco outstanding month By Gopi********************/
	public function check_editlco_outstanding_month(){
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$month = $_POST['month'];
		$lco_id = $_POST['lco_id'];
		$newlco_id = $_POST['newlco_id'];
		$edit_month = $_POST['edit_month'];
		if(($month!=$edit_month) || ($lco_id!=$newlco_id)){
			$month_format = date('01-'.$month);
			$month = date('Y-m-d',strtotime($month_format));
			$sql = $this->db->query("select count(out_standing_id) id from eb_reseller_outstanding_amounts  where  date(out_standing_month) = '$month' and reseller_id = $lco_id");
			//echo $this->db->last_query();
			if($sql && $sql->row()->id > 0){
				echo 1;
			}else{
				echo 0;
			}
		}else{
			echo 0;
		}
		
		
	}
	//added by nikhilesh to get values from lco_payments in outstanding screen on 09/10/2013
	public function getLCOPayment()
	{		
		$month = $_POST['month'];
		$lco_id = $_POST['lco_id'];		
		
		$current_out = 0.00;
		$month_format = date('01-'.$month);
		$month = date('Y-m-d',strtotime($month_format));
		$sql = $this->db->query("select * from eb_reseller_outstanding_amounts  where  date(out_standing_month) = '$month' and reseller_id = $lco_id ");
		//echo $this->db->last_query();
		if($sql && $sql->num_rows() > 0){
			$details=$sql->row();
            if($details->total_bill == 0.00 && $details->total_payment==0.00)
            $response = $details->previous_outstanding;
            else
            {
			     $current_out = $details->previous_outstanding+$details->total_bill-$details->total_payment;
			     $response = $details->previous_outstanding.','.$details->total_bill.','.$details->total_payment.','.$current_out.','.$details->remarks;
            }
			
		}else{
			$response = "";
		}
		echo $response;
		
	}
	public function tax_on(){
		
		$l= $_POST['tax_rate'];
		$html='';
		$html.='on&nbsp;&nbsp;<select name="tax'.$l.'_on" id="tax'.$l.'_on" onchange="tax_on_valid('.$l.');">
				<option value="">-Select-</option>';				  
			    for($k=1;$k<=6;$k++) {
				  	if($k!=$l){
				     $tax='TAX'.$k;
					
	         $html.='<option value="'.$k.'" tax_name="';
	         if(isset($_SESSION['dealer_setting']->$tax)){
	         	$html.=$_SESSION['dealer_setting']->$tax;
			  }else{
	         	$html.=$tax;
			  }	
	         $html.='" tax_on="'.$k.'">';
	         if(isset($_SESSION['dealer_setting']->$tax))
	          {
	          	$html.=$_SESSION['dealer_setting']->$tax;
			  }else{
			  	$html.=$tax;
			  }	
	         $html.='</option>';
			      }}
		 $html.='</select>';
		 
		 echo $html;
		
	}

        /*************get employee wise details by gopi**********************/
	public function get_employeewise_details(){
		$employeeModel = new EmployeeModel();
		$parent_join ='';
		$parent_cond = '';
		$usersid = $_POST['usertype'];
		$userid = (isset($_POST['userid']))?$_POST['userid']:'-1';
		$employee_id = (isset($_POST['employee_id']))?$_POST['employee_id']:'0';
		$parent_id = (isset($_POST['parent_id']))?$_POST['parent_id']:'0';
                $boxNumber = (isset($_POST['boxNumber']))?$_POST['boxNumber']:'';
                
		$box_number_cond='';
		$employee_type_cond="";
		$select_employee = "";
		$select_parent_employee = "";
		$dealerid = $_SESSION['user_data']->dealer_id;
		if($boxNumber !=''){
                    $box_number_cond ="INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
                    INNER JOIN eb_stock s ON s.stock_location = sl.location_id AND s.serial_number='$boxNumber'  
                    ";
                }
		if($parent_id>0){
		
		
		$select_parent_employee .= "AND e.parent_id = $parent_id";
		
		}
		if($employee_id>0){
		
		$select_employee .= "AND e.employee_id = $employee_id";
		
		}
		$val='<option value="-1">Select</option>';
		$join='';
		$condition='';
		if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
		$order_by= "trim(e.dist_subdist_lcocode)";
		}
		else {
		$order_by= "trim(e.business_name)";
		}
		 if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){
                        
                    $int_employee_id = $_SESSION['user_data']->employee_parent_id;
                }else{
                    $int_employee_id = $_SESSION['user_data']->employee_id;
                }
		switch($usersid){
			CASE 1:
					$employee_type_cond="AND e.users_type='DEALER' $select_employee";
					break;
			CASE 2:
					$employee_type_cond="AND e.users_type='ADMIN' $select_employee";
					break;
			CASE 3:
					$employee_type_cond="AND e.users_type='EMPLOYEE' $select_employee";
					break;
			CASE 4:
					$employee_type_cond="AND e.users_type='RESELLER' $select_employee $select_parent_employee";
					break;
			CASE 5:
				   if ($boxNumber != '') {
                    $employee_type_cond =1;
                    $select = "SELECT e2.employee_id,e2.business_name,e2.users_type,concat(e2.first_name,' ',e2.last_name) as name,e2.dist_subdist_lcocode,e2.employee_parent_type  
                        FROM `employee` as e
                        join employee e1 on e1.employee_id=e.parent_id
                        left join employee e2 on (e2.employee_id=e1.parent_id or e2.employee_id=e.parent_id)
                        INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
                        INNER JOIN eb_stock s ON s.stock_location = sl.location_id  
                        WHERE e.dealer_id =$dealerid AND e2.users_type='DISTRIBUTOR' AND s.serial_number='$boxNumber'";
                        
	                }else{

	                   $employee_type_cond = "AND e.users_type='DISTRIBUTOR' $select_employee"; 
	                }
					break;
			CASE 6:
					if ($boxNumber != '') {
                    $employee_type_cond =1;
                      $select ="select employee_id,business_name,users_type,concat(first_name,' ',last_name) as name,dist_subdist_lcocode,employee_parent_type from employee where parent_id in (select e2.employee_id from `employee` as e
                        join employee e1 on e1.employee_id=e.parent_id
                        left join employee e2 on e2.employee_id=e1.parent_id
                        INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
                        INNER JOIN eb_stock s ON s.stock_location = sl.location_id  
                        WHERE e.dealer_id =$dealerid AND e2.users_type='DISTRIBUTOR' AND s.serial_number='$boxNumber') and employee_parent_type='DISTRIBUTOR'";
                   } else {
                      $box_number_cond = "";
                        $parent_cond = ",e1.business_name as parent_business_name,e1.users_type as parent_user_type,concat(e1.first_name,' ',e1.last_name) as parent_name,e1.dist_subdist_lcocode as parent_code";
                        $employee_type_cond = "AND e.users_type='EMPLOYEE' AND e.employee_parent_type='DISTRIBUTOR' $select_employee";
                        $parent_join = "INNER JOIN employee e1 on e1.employee_id = e.parent_id";
                   }
					break;
			CASE 7:
				  if($boxNumber != ''){
                    $employee_type_cond =1;
                    $select = "SELECT e1.employee_id,e1.business_name,e1.users_type,concat(e1.first_name,' ',e1.last_name) as name,e1.dist_subdist_lcocode,e1.employee_parent_type  
                        FROM `employee` as e
                        join employee e1 on e1.employee_id=e.parent_id
                        INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
                        INNER JOIN eb_stock s ON s.stock_location = sl.location_id  
                        WHERE e.dealer_id =$dealerid AND e1.users_type='SUBDISTRIBUTOR' AND s.serial_number='$boxNumber'";
	                }else{

	                   $employee_type_cond = "AND e.users_type='SUBDISTRIBUTOR' $select_employee "; 
	                }
					break;
			CASE 8:
				    if($boxNumber != ''){
                    $employee_type_cond =1;
                    $select="select employee_id,business_name,users_type,concat(first_name,' ',last_name) as name,dist_subdist_lcocode,employee_parent_type from employee where parent_id in (SELECT e1.employee_id 
                        FROM `employee` as e
                        join employee e1 on e1.employee_id=e.parent_id
                        INNER JOIN eb_stock_location sl ON sl.reseller_id = e.employee_id
                        INNER JOIN eb_stock s ON s.stock_location = sl.location_id  
                        WHERE e.dealer_id =$dealerid AND e1.users_type='SUBDISTRIBUTOR' AND s.serial_number='$boxNumber') and employee_parent_type='SUBDISTRIBUTOR'";
	                }else{
	                    $box_number_cond = "";
	                    $parent_cond = ",e1.business_name as parent_business_name,e1.users_type as parent_user_type,concat(e1.first_name,' ',e1.last_name) as parent_name,e1.dist_subdist_lcocode as parent_code";
	                    $employee_type_cond = "AND e.users_type='EMPLOYEE' AND e.employee_parent_type='SUBDISTRIBUTOR' $select_employee";
	                    $parent_join = "INNER JOIN employee e1 on e1.employee_id = e.parent_id"; 
	                }
					break;
			CASE 9: //LCO Employee
					$parent_cond = ",e1.business_name as parent_business_name,e1.users_type as parent_user_type,concat(e1.first_name,' ',e1.last_name) as parent_name,e1.dist_subdist_lcocode as parent_code";
					$employee_type_cond="AND e.users_type='EMPLOYEE' AND e.employee_parent_type='RESELLER' $select_employee $select_parent_employee";
					$parent_join = "INNER JOIN employee e1 on e1.employee_id = e.parent_id";
					if($boxNumber !=''){
                    $box_number_cond ="INNER JOIN eb_stock_location sl ON sl.reseller_id = e1.employee_id
                    INNER JOIN eb_stock s ON s.stock_location = sl.location_id AND s.serial_number='$boxNumber'  
                    ";
					}
					break;
			CASE 10: //MSO Employee
					$box_number_cond="";
					$employee_type_cond="AND e.users_type='EMPLOYEE' AND e.parent_id=0 $select_employee";
					break;	
			CASE 11://to get distributor in sub distributor login
					 if ($_SESSION['user_data']->employee_parent_type != "SUBDISTRIBUTOR") {
						$int_employee_id = $_SESSION['user_data']->parent_id;
					}
					$employee_type_cond = "AND e.users_type='DISTRIBUTOR' $select_employee";
                break;
		}
               
		if(($usersid!=1 && $usersid!=2 && $usersid!=10)  && ($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR") ) { 
			/*if($userstype=="DISTRIBUTOR"){
				$condition ="e.parent_id=0 AND e.employee_id=".$_SESSION['user_data']->employee_id." AND";
			}else{
				$condition ="(e.parent_id IN(select employee_id from employee where parent_id=".$_SESSION['user_data']->employee_id.") OR e.parent_id=".$_SESSION['user_data']->employee_id.") AND";
			}*/
			$condition="AND (e.employee_id=$int_employee_id  
			OR e.parent_id =$int_employee_id 
			OR e.parent_id IN (select employee_id from employee where parent_id=$int_employee_id) 
			OR e.employee_parent_id=$int_employee_id 
			OR e.employee_parent_id IN (SELECT employee_id from employee where (parent_id = $int_employee_id 
			OR parent_id IN (SELECT employee_id from employee where parent_id=$int_employee_id))))";		
			
		}
		else if(($usersid!=1 && $usersid!=2 && $usersid!=10) && ($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR")){
			
			/*if($userstype=="SUBDISTRIBUTOR"){
				$condition ="( e.employee_id=".$_SESSION['user_data']->employee_id.") AND";
			}else{
				$condition ="e.parent_id=".$_SESSION['user_data']->employee_id."  AND";
			}*/
		 	$condition="AND (e.employee_id=$int_employee_id 
			OR e.parent_id =$int_employee_id 
			OR e.employee_parent_id=$int_employee_id  
			OR e.employee_parent_id IN (SELECT employee_id from employee where parent_id = $int_employee_id)) ";
			  
			
		}
		else if(($usersid!=1 && $usersid!=2 && $usersid!=10) && ($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=="RESELLER")){
				$condition="AND( e.parent_id=$int_employee_id OR e.employee_id=$int_employee_id)";
		}
		if($employee_type_cond!=""){
			if($select=='')
            {
                $sql = "SELECT e.employee_id,e.business_name,e.users_type,concat(e.first_name,' ',e.last_name) as name,e.dist_subdist_lcocode,e.employee_parent_type $parent_cond FROM `employee` as e
                $parent_join
                        $box_number_cond    
            WHERE e.dealer_id =" . $_SESSION['user_data']->dealer_id . " $employee_type_cond $condition ORDER BY $order_by";
                
            }else{
                
                $sql = "$select"; 
            }
			$qry=$this->db->query($sql);
			//echo $this->db->last_query();
			
			if($qry && $qry->num_rows()>0){
				$selected = '';
				if($qry->num_rows()==1){
					$selected = 'selected';
				}
				foreach($qry->result() as $users){
				if($users->users_type=='RESELLER' || $users->users_type=='DISTRIBUTOR' || $users->users_type=='SUBDISTRIBUTOR')
				{
					$code=$users->dist_subdist_lcocode;
					 $business_name = trim($users->business_name);
					 $uname = trim($users->name);	
					 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
							 $value=$code;
							 $name = "(".$business_name.")";
							 $title = $uname;
					 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
							 $title = $code;
							 $name = "(".$uname.")";
							 $value= $business_name;
					  }
					  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
							$title = $uname;
							$name = "(".$code.")";
							$value= $business_name;
					  }	
					  else {
							$title = 	$users->dist_subdist_lcocode;
							$name = "(".$uname.")";
							$value= $business_name;
					  }
					
					//$val .="<option  value='".$users->employee_id."' title='".$title."'>".$value." ".$name."</option>";
				
					$dist_subdist_code = (isset($users->dist_subdist_lcocode) && $users->dist_subdist_lcocode!="" )? "(".$users->dist_subdist_lcocode.")":"";
					//$val .="<option  value='".$users->employee_id."'  >".$users->name.$dist_subdist_code." </option>";
					//$val .= "<option value=".$users->employee_id;		
						  
					$val .="<option title='".$title."' value='".$users->employee_id."' ".((isset($userid) && $userid>0 && $userid==$users->employee_id)?"selected":"$selected").">".$value .$name . "</option>";
				}else if($users->users_type=='EMPLOYEE' && ($users->employee_parent_type=='RESELLER'||$users->employee_parent_type=='DISTRIBUTOR'||$users->employee_parent_type=='SUBDISTRIBUTOR')){
					$code=$users->parent_code;
					 $business_name = trim($users->parent_business_name);
					 $uname = trim($users->parent_name);	
					 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
							 $value=$code;
							 $name = "(".$business_name.")";
					 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
							 $title = $code;
							 $name = "(".$uname.")";
					  }
					  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
							$title = $uname;
							$name = "(".$code.")";
					  }	
					  else {
							$title = 	$users->dist_subdist_lcocode;
							$name = "(".$uname.")";
					  }
					
					 	 $val .="<option title='".$title."' value='".$users->employee_id."' ".((isset($userid) && $userid>0 && $userid==$users->employee_id)?"selected":"$selected").">".$users->name .$name . "</option>";
                     
				}
				else
				{
				
					$val .= "<option value=".$users->employee_id;			
					if($userid!='-1' && $userid == $users->employee_id){ 
					$val .= " selected";
					}else{
					$val .= " $selected";
					}
					$val .= ">";
					$val .= $users->name."</option>";
				}
					
				}
			}
		}
		echo $val;
	}

	
	 public function get_unpaired_vc_transfer() {
        if (isset($_POST['vcnumber']) && $_POST['vcnumber'] != '') {
            $unpaired_vc_number = $_POST['vcnumber'];
        } else {
            $unpaired_vc_number = '';
        }
        if ($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "BNAME") {
            $orderby = "dist_subdist_lcocode";
        } else {
            $orderby = "business_name";
        }

        $r_cond = "";
        $dealerId = isset($_SESSION['user_data']->dealer_id) ? $_SESSION['user_data']->dealer_id : 1;
        $eid = $_SESSION['user_data']->employee_id; //get the employee id
        if ($_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "RESELLER") {

            $int_employee_id = $_SESSION['user_data']->employee_parent_id;
        } else {
            $int_employee_id = $_SESSION['user_data']->employee_id;
        }
        //if ($_SESSION['user_data']->users_type == 'RESELLER' || $_SESSION['user_data']->users_type == 'DISTRIBUTOR' || $_SESSION['user_data']->users_type == 'SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "RESELLER") {
          //  $r_cond = " OR (e.parent_id IN(select employee_id from employee where parent_id=$int_employee_id) OR e.parent_id=$int_employee_id)";
        //}
        
        
         $distibutor_condition = '';
        if ($_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "RESELLER") {

            $int_employee_id = $_SESSION['user_data']->employee_parent_id;
        } else {
            $int_employee_id = $_SESSION['user_data']->employee_id;
        }
        if ($_SESSION['user_data']->users_type == 'RESELLER' || $_SESSION['user_data']->employee_parent_type == "RESELLER") {
            if ($_SESSION['user_data']->employee_parent_type == "RESELLER") {
                $employee_id = $_SESSION['user_data']->employee_parent_id;
            } else {
                $employee_id = $_SESSION['user_data']->employee_id;
            }
            //lco employee login show his record and his parent record only added by pardhu  
            if(isset($_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER) && $_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER==1 && isset($_SESSION['user_data']->employee_parent_type) && $_SESSION['user_data']->employee_parent_type == "RESELLER"){//lco employee login when LCO_EMPLOYEE_GROUP_CUSTOMER-1
                $employee_parent_id = $_SESSION['user_data']->employee_parent_id;
                $int_employee_id = $_SESSION['user_data']->employee_id;
                $r_cond = " AND (e.employee_id=$int_employee_id OR e.employee_id=$employee_parent_id)";
            }else{//lco,lco employee login when LCO_EMPLOYEE_GROUP_CUSTOMER-0
                $r_cond = " AND (e.employee_id=$employee_id OR e.employee_parent_id=$employee_id)";
            }
        } elseif ($_SESSION['user_data']->users_type == 'SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR") {

            $r_cond = " AND (e.employee_id=$int_employee_id OR e.parent_id =$int_employee_id OR e.employee_parent_id=$int_employee_id OR e.employee_parent_id IN (SELECT employee_id from employee where parent_id = $int_employee_id))";
        } elseif ($_SESSION['user_data']->users_type == 'DISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR") {
            $r_cond = "AND (e.employee_id=$int_employee_id OR e.parent_id =$int_employee_id OR e.parent_id IN (select employee_id from employee where parent_id=$int_employee_id) OR e.employee_parent_id=$int_employee_id OR e.employee_parent_id IN (SELECT employee_id from employee where (parent_id = $int_employee_id OR parent_id IN (SELECT employee_id from employee where parent_id=$int_employee_id))) )";
        } 


        $html = '';
        $val = '';
        $dest_val = '';
        $reseller_id = '';

        if ($unpaired_vc_number != '') {
			// query add by mahendra
            $sql = "SELECT eav.*,bs.display_name FROM eb_all_vcs eav inner join backend_setups bs on bs.backend_setup_id=eav.backend_setup_id WHERE eav.vc_number='" . $unpaired_vc_number . "' AND eav.dealer_id=" . $_SESSION['user_data']->dealer_id . "";
            $query = $this->db->query($sql);
		
			//echo $server_type;
            if ($query->num_rows() > 0) {
					$server_type=$query->row()->display_name;
                if ($query->row()->reseller_id > 0) {
					
                    $reseller_id = $query->row()->reseller_id;

                    $sql_str = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,
            	e.users_type,e.parent_id,CONCAT(e.first_name,' ' , e.last_name) AS empname
            	from eb_stock_location el
            	INNER JOIN employee e ON el.reseller_id=e.employee_id
            	where el.dealer_id=? 
            	AND el.reseller_id=$reseller_id $r_cond ORDER BY el.location_name";
                    $query = $this->db->query($sql_str, array($dealerId));

                    $dest_sql = "select * from ((SELECT 1 as employee_loc,location_id,location_name,'' as business_name,'' as employee_id,'' as dist_subdist_lcocode,'' as users_type,'' as parent_id,'' AS empname
				from eb_stock_location where reseller_id=0 and is_primary=1 and dealer_id=$dealerId)
				UNION
				(SELECT 2 as employee_loc,el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,CONCAT(e.first_name,' ' , e.last_name) AS empname
				from eb_stock_location el
				INNER JOIN employee e ON el.reseller_id=e.employee_id AND e.users_type='RESELLER' AND e.status=1 
				where el.dealer_id=$dealerId 
				AND el.reseller_id !=0 AND e.status=1 AND e.parent_status=1
				AND el.reseller_id!=$reseller_id $r_cond))x ORDER BY employee_loc,$orderby";
                    $dest_query = $this->db->query($dest_sql);
                } elseif ($query->row()->reseller_id == 0) {
					
                    $sql_str = "SELECT el.location_id,el.location_name,'' as business_name,'' as employee_id,'' as dist_subdist_lcocode,'' as users_type,'' as parent_id,'' as empname from eb_stock_location el  where el.dealer_id=? AND el.is_primary=? AND el.reseller_id=0";
                    $query = $this->db->query($sql_str, array($dealerId, 1));
                    $dest_sql = "SELECT el.location_id,el.location_name,e.business_name,e.employee_id,e.dist_subdist_lcocode,e.users_type,e.parent_id,CONCAT(e.first_name,' ' , e.last_name) AS empname from eb_stock_location el
				INNER JOIN employee e ON el.reseller_id=e.employee_id where el.dealer_id=? AND e.users_type=? $r_cond ORDER BY el.location_name";
                    $dest_query = $this->db->query($dest_sql, array($dealerId, 'RESELLER'));
                }


                if ($query && $query->num_rows() > 0) {

                    foreach ($query->result() as $res) {

                        if (isset($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO) && $_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "NAME") {
                            $title = $res->dist_subdist_lcocode;
                            if ($res->empname != '') {
                                $name = "(" . trim($res->empname) . ")";
                            } else {
                                $name = '';
                            }
                        } else {
                            $title = trim($res->empname);
                            if ($res->dist_subdist_lcocode != '') {
                                $name = "(" . $res->dist_subdist_lcocode . ")";
                            } else {
                                $name = '';
                            }
                        }
						//echo '<pre>';
						//print_r($res->business_name);die;
                        if ($res->users_type == "DEALER") {
                            $val .= "<option  value='" . $res->location_id . "'>" . $res->location_name . "</option>";
                        } else {
                            $val .= "<option title='" . $title . "' value='" . $res->location_id . "'>" . $res->location_name . $name . "</option>";
                        }
                    }
                }


                if ($dest_query && $dest_query->num_rows() > 0) {

                    foreach ($dest_query->result() as $res) {

                        if (isset($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO) && $_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "NAME") {
                            $title = $res->dist_subdist_lcocode;
                            if ($res->empname != '') {
                                $name = "(" . trim($res->empname) . ")";
                            } else {
                                $name = '';
                            }
                        } else {
                            $title = trim($res->empname);
                            if ($res->dist_subdist_lcocode) {
                                $name = "(" . $res->dist_subdist_lcocode . ")";
                            } else {
                                $name = '';
                            }
                        }
                        if ($res->users_type == "DEALER") {
                            $dest_val .= "<option  value='" . $res->location_id . "'>" . $res->location_name . "</option>";
                        } else {
                            $dest_val .= "<option title='" . $title . "' value='" . $res->location_id . "'>" . $res->location_name . $name . "</option>";
                        }
                    }
                }
				// server type add by mahendra
                $html .= '<table border="0" cellspacing="0" cellpadding="0">
						   <tr>
			                  <td>Server Type</td>
		                      <td>:</td>
		                      <td><select name="server_type" id="server_type" >;
									 <option value="'.$server_type.'">'.$server_type.'</option>;
                  					</select></td>
	                       </tr>
			               <tr>
			                  <td>Unpaired VC Number</td>
		                      <td>:</td>
		                      <td><input type="text" name="trasfer_vc_number" id="trasfer_vc_number" value="' . $unpaired_vc_number . '" readonly="readonly"/></td>
	                       </tr>
	                       <tr>
			                  <td>Source Location<span class="important_field">*</span></td>
		                      <td>:</td>
		                      <td>
		                         <select name="vc_source_location" id="vc_source_location" >';

                $html .= $val . '</select>
		                         
		                      </td>
	                       </tr>
	                       <tr>
			                  <td>Destination Location<span class="important_field">*</span></td>
		                      <td>:</td>
		                      <td>
		                         <select name="vc_dest_location" id="vc_dest_location">
		                             <option value="">Select</option>';
                $html .= $dest_val . '</select>
		                      </td>
	                       </tr>
	                       <tr>
	                          <td>Remarks<span class="important_field">*</span></td>
	                          <td>:</td>
	                          <td><textarea name="vc_transfer_remarks" id="vc_transfer_remarks"></textarea>
	                          </td>
	                       </tr>
	                       <tr>
		                      <td>&nbsp;</td>
		                      <td>&nbsp;</td>
		                      <td><input type="submit" name="vc_transfer" id="vc_transfer" value="Transfer"/></td>
	                       </tr>
			         </table>';
            } else {
                $html .= '1';
            }
        }

        echo $html;
    }
	
    public function get_prepaid_packages()
    {        
        $pid =0;
        $old_lco = "";
        $new_lco = "";
        $stocks_id = "";
        
        if(isset($_POST['old_lco']) && $_POST['old_lco']!='')
        $old_lco = $_POST['old_lco'];
        
        if(isset($_POST['new_lco']) && $_POST['new_lco']!='')
        $new_lco = $_POST['new_lco'];
        
        if(isset($_POST['stocks_id']) && $_POST['stocks_id']!='')
        $stocks_id = $_POST['stocks_id'];
		
		$customer_id = $_POST['customer_id'];
        $dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:1;
        $customer_info = $this->CustomersModel->getCustomerDetails($customer_id);
        $customer_verification = $customer_info->is_verified;
        $lov_vaue = 'DEFAULT_VERIFICATION';
        $default_verification = $this->change_pass_model->getLovValue($lov_vaue,$dealerId);
        $get_send_cas_lovvalue = $this->change_pass_model->getLovValue('SEND_CAS_COMMAND',$dealerId);
        //$get_send_cas_lovvalue = 1;
		$r_cond = "";
		
        $eid = $_SESSION['user_data']->employee_id;//get the employee id
		$html='';
		$val='';
		$dest_val='';
		$reseller_id='';
        $new_lco_prods = array();
          $old_prods_verification = array();
		$old_lco_prods = array();
        $arr_stock_id=explode(',',$stocks_id);
		
		$setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO))?$_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO:0;
		if($setting_value==0)
		{
			$r_cond = " AND epm.is_blocked In (0,2) ";
		}
		elseif($setting_value==2){
			if($_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER')
			{
				$r_cond = " AND epm.is_blocked In (0,2) ";
			}
		}
		else{
		
			if($_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='RESELLER' || $_SESSION['user_data']->employee_parent_type=='RESELLER')
			{
				$r_cond = " AND epm.is_blocked In (0,2) ";
			}
		}
		foreach($arr_stock_id as $stock_id)
        {
            $current_stock_id = $stock_id;
			//AND (p.`pricing_structure_type` =1 OR p.`pricing_structure_type` =2)
			$sql_str = "SELECT p.product_id, p.pname,s.serial_number,s.stock_id,CASE bs.use_mac WHEN  '1' THEN  s.mac_address ELSE  s.vc_number END AS mac_vc_number 
                            FROM eb_products p
                            INNER JOIN eb_reseller_product_mapping epm ON p.product_id = epm.product_id
                            INNER JOIN eb_stock_cas_services ecs ON p.product_id = ecs.product_id
                            INNER JOIN eb_customer_service cs ON cs.customer_service_id = ecs.customer_service_id
							INNER JOIN eb_stock s ON s.stock_id=$stock_id
							INNER JOIN eb_product_to_stbtype_mapping psm ON psm.product_id = ecs.product_id 
							INNER JOIN backend_setups bs ON s.backend_setup_id = bs.backend_setup_id
                            WHERE epm.dealer_id =$dealerId AND epm.employee_id=$new_lco  AND ecs.stock_id=$stock_id $r_cond
							AND (p.`pricing_structure_type` =1 OR p.`pricing_structure_type` =2)
							AND s.not_paired_in_cas = 0 AND cs.parent_service_id = 0 
							AND psm.stb_type_id=(SELECT stb_type_id FROM eb_stock WHERE stock_id=$stock_id AND dealer_id =$dealerId)
                            ORDER BY p.is_base_package DESC,p.is_master DESC,p.alacarte ASC";           
            $query = $this->db->query($sql_str);
           
			
            if($query && $query->num_rows()>0)
            {            
                foreach($query->result() as $res)
                {
                    $pid = $res->product_id;
                    $pname = $res->pname;
                    $new_lco_prods[$stock_id][$pid] = $pname;
                }
            }
           //AND (p.`pricing_structure_type` =1 OR p.`pricing_structure_type` =2)
            $int_box_status = 2;
            $box_status = $this->db->query("select status from eb_stock where stock_id=$stock_id");
           // print_r($this->db->last_query())	;
           //   echo "<br>";
            if($box_status && $box_status->num_rows()>0){
            	$int_box_status=$box_status->row()->status;
            }
            $join_cond_new = "AND DATE(ecs.deactivation_date) = (SELECT MAX(DATE(deactivation_date)) FROM eb_stock_cas_services WHERE stock_id = $stock_id)";
            $where_cond_new = "AND case WHEN (p.cutoff_date IS NULL or p.cutoff_date ='0000-00-00')  THEN  Date(cs.used_days) > DATE(CURDATE()) ELSE p.cutoff_date > DATE(CURDATE()) END";
            if($int_box_status == 1){
            	$join_cond_new = "";
            	$where_cond_new  = "";
            }
            //echo $int_box_status;
           // echo "<br>";

            $sql_str_oldlco = "SELECT p.product_id,p.is_verified, p.pname,s.serial_number,s.stock_id,CASE bs.use_mac WHEN  '1' THEN  s.mac_address ELSE  s.vc_number END AS mac_vc_number 
                            FROM eb_products p
                            INNER JOIN eb_reseller_product_mapping epm ON p.product_id = epm.product_id
                            INNER JOIN eb_stock_cas_services ecs ON p.product_id = ecs.product_id  and ecs.deactivation_date is null
							$join_cond_new
							INNER JOIN eb_customer_service cs ON cs.customer_service_id=ecs.customer_service_id and cs.status=1
							INNER JOIN eb_stock s ON s.stock_id=$stock_id
							INNER JOIN eb_product_to_stbtype_mapping psm ON psm.product_id = ecs.product_id 
							INNER JOIN backend_setups bs ON s.backend_setup_id = bs.backend_setup_id
                            WHERE epm.dealer_id =$dealerId AND epm.employee_id=$old_lco AND ecs.stock_id=$stock_id 
							AND (p.`pricing_structure_type` =1 OR p.`pricing_structure_type` =2)
							AND s.not_paired_in_cas = 0 AND cs.parent_service_id = 0 
                          	$where_cond_new
							AND cs.customer_id = $customer_id
							AND psm.stb_type_id=(SELECT stb_type_id FROM eb_stock WHERE stock_id=$stock_id AND dealer_id =$dealerId)
                            ORDER BY p.is_base_package DESC,p.is_master DESC,p.alacarte ASC";
           //AND Date(cs.used_days) > DATE(CURDATE())
            $query_oldlco = $this->db->query($sql_str_oldlco);
         //   print_r($this->db->last_query())	;
            
            if($query_oldlco && $query_oldlco->num_rows()>0)
            {  
				$oldlco=$query_oldlco->result();
                foreach($oldlco as $res_oldlco)
                {
                    $pid_oldlco = $res_oldlco->product_id;                   
                    $pname_oldlco = $res_oldlco->pname;
                    $pname_verified = $res_oldlco->is_verified;
                    $old_lco_prods[$stock_id][$pid_oldlco] = $pname_oldlco;
                    $old_prods_verification[$stock_id][$pid_oldlco] = $pname_verified;
                }
            }
           	
           	$style = "";
           	$disabled_status="";
           //	echo "get_send_cas_lovvalue================".$get_send_cas_lovvalue;
           	if($get_send_cas_lovvalue == 0){
           		$style = "checked = checked"; 
           		$disabled_status="disabled = disabled";
           	}
           	//echo "disabled_status================".$disabled_status;
          // 	print_r($old_lco_prods);
            if(count($old_lco_prods)>0)
            {
                //foreach($old_lco_prods as $pid=>$pname)
                $i = 0;
				foreach($old_lco_prods as $stk_id=>$old_lco_parray)
                {                   
                    
					if($stk_id == $current_stock_id){
					
					$basePkgCount=$this->productsmodel->isBasePkgExistOnSTB($current_stock_id);
					
                                        if($basePkgCount>0){
					 $html.='<tr>                                      
                      <th colspan="2" align="left"> Serial Number '.$oldlco[0]->serial_number.', MAC/VC Number '. $oldlco[0]->mac_vc_number.'</th>  
                      </tr>'; 
						//$new_lco_parray = $new_lco_prods[$stk_id];
						$new_lco_parray = isset($new_lco_prods[$stk_id])?$new_lco_prods[$stk_id]:array();
						// print_r($new_lco_parray);
						
						foreach($old_lco_parray as $pid=>$pname){
							$is_base_pkg=$this->dasmodel->checkIsBasePackageBypid($pid);
                                                        //check customer verification and product verification condition
                                                        $is_product_verified = isset($old_prods_verification[$stk_id][$pid])?$old_prods_verification[$stk_id][$pid]:$default_verification;
                                                        
							if(isset($new_lco_parray[$pid]) && $pname == $new_lco_parray[$pid])
							{
                                                            //echo $old_prods_verification[$stk_id][$pid].'<br/>';
                                                            //echo $is_product_verified.'<br/>';
                                                            if($customer_verification==$is_product_verified){
                                                                $html.='<tr>                                      
									    <td width="250px">'.$pname.'</td><td><input type="checkbox" class="prod_id" name="pids[]" id="pid_'.$oldlco[0]->stock_id.'_'.$pid.'" serial="'.$oldlco[0]->serial_number.'" is_base_pkg="'.$is_base_pkg.'"
										stock_id="'.$oldlco[0]->stock_id.'"
										value="'.$pid.'" '.$style.' '.$disabled_status.'></td>  
									    </tr>';
                                                            }else {
                                                            	
								$html.='<tr><td>'.$pname.'</td><td>Not Applicable</td></tr>';
                                                            }
								
							}
							else
							{	
								if($get_send_cas_lovvalue == 0){	
									$i++;
								}
								$html.='<tr><td>'.$pname.'</td><td>Not Applicable</td></tr>';
							}
					
						}
					}
					
					}
					
					
				}
            }
            
        }

        
        if($i > 0){
        	$html =  '<tr><td>Activated packages are not mapped to new LCO.Intra lco transfer stoped for this customer</td></tr>';
        	echo "0@@$html";
        }else{
        	echo "1@@$html";
        }
		
    }
	/* public function get_isbasebase_pkg()
	 {
		$pid = $_POST['product_id'];
		$product_ids = implode($pid, ','); 
		$query = $this->db->query("select product_id from eb_products where product_id in ($product_ids) and is_base_package=1");
		
		if($query && $query->num_rows()>0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	 }
	*/
    public function get_remarks(){
    			
    		$response = "";	
    		$outid = $_POST['outid'];	
    		$sql_str = "SELECT remarks from eb_reseller_outstanding_amounts
                    WHERE out_standing_id = $outid";
            $query = $this->db->query($sql_str);
			
            if($query && $query->num_rows()>0)
            {            
				$res = $query->row();
				//print_r($res);
				if($res->remarks!="")
				{
					$response = $res->remarks; 
				}				
			}
			
			echo $response;
    }
	public function getMakeName(){
    			
    		$response = "";	
    		$makeid = $_POST['makeid'];	
    		$sql_str = "SELECT make_name from eb_makes WHERE make_id = $makeid";
            $query = $this->db->query($sql_str);
			
            if($query && $query->num_rows()>0)
            {            
				$res = $query->row();
				//print_r($res);
				if($res->make_name!="")
				{
					$response = $res->make_name; 
				}				
			}
			
			echo $response;
    }
	public function getModelName(){
    			
    		$response = "";	
    		$modelid = $_POST['modelid'];	
    		$sql_str = "SELECT model_name,device_type from eb_models WHERE model_id = $modelid";
            $query = $this->db->query($sql_str);
			
            if($query && $query->num_rows()>0)
            {            
				$res = $query->row();
				//print_r($res);
				if($res->model_name!="")
				{
					$response = $res->model_name; 
					echo $device_type = $res->device_type."@@";
				}				
			}
			
			echo $response;
    }
	public function delMake(){
    			
    		$response = 0;	
    		$makeid = $_POST['makeid'];	
    		$sql_str = "UPDATE eb_makes SET status = 0 WHERE make_id = $makeid";
            $query = $this->db->query($sql_str);			
            if($query)
            {            
				$response = 1;		
			}
			else $response = 0;		
			echo $response;
    }
	public function delModel(){
    			
    		$response = 0;	
    		$modelid = $_POST['modelid'];	
    		$sql_str = "UPDATE eb_models SET status = 0 WHERE model_id = $modelid";
            $query = $this->db->query($sql_str);			
            if($query)
            {            
				$response = 1;		
			}
			else $response = 0;		
			echo $response;
    }
	
    //get hotel and Hospital list with LCO
	public function getHotelHospital(){
		$var='';//$selected_id='-1';
		$reseller_id=$_POST['reseller_id'];
		$type_id=$_POST['type_id'];
		$selected_id=($_POST['selected_id']!='-1')?$_POST['selected_id']:'-1';
		//echo $selected_id;
		$is_multy_type_query =  $this->db->query(" SELECT is_commercial_multi_box FROM eb_customer_types WHERE customer_type_id = $type_id");
		if($is_multy_type_query && $is_multy_type_query->num_rows()>0 && $is_multy_type_query->row()->is_commercial_multi_box == 1){
			if(isset($_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE) && $_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE==1) {
				$query=$this->db->query("SELECT ctt.customer_type_types_id,ctt.reseller_id,ctt.name FROM eb_customer_type_of_types ctt
					left join customer c ON c.customer_type_types_id= ctt.customer_type_types_id
					WHERE ctt.reseller_id=$reseller_id AND ctt.is_active=1 AND ctt.customer_type_id=$type_id AND c.customer_type_types_id IS NULL");
				//echo $this->db->last_query();exit;
				$var .="<option value='-1'>Select</option>";
				if($query && $query->num_rows()>0){
					foreach($query->result() as $hotel){
						
						//if customer type types id is already assigned to customer then no need to display in select box
						
							$var .="<option value='".$hotel->customer_type_types_id."' ".(($hotel->customer_type_types_id==$selected_id)?'SELECTED':'').">".$hotel->name."</option>";
						

					}			
				}
				else{
					echo "none";
					exit;
				}
			}else{
				$var .= $is_multy_type_query->row()->is_commercial_multi_box;
			}
		}else{
			$var ='';
		}
		echo $var;
	}
	
	//get hotel and Hospital list with LCO
	public function getHotelHospitalsInEdit(){
		$var='';//$selected_id='-1';
		$reseller_id=$_POST['reseller_id'];
		$type_id=$_POST['type_id'];
		$selected_id=($_POST['selected_id']!='-1')?$_POST['selected_id']:'-1';
		//echo $selected_id;
		$is_multy_type_query =  $this->db->query(" SELECT is_commercial_multi_box FROM eb_customer_types WHERE customer_type_id = $type_id");
		if($is_multy_type_query && $is_multy_type_query->num_rows()>0 && $is_multy_type_query->row()->is_commercial_multi_box == 1){
			if(isset($_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE) && $_SESSION['dealer_setting']->DATA_FROM_MASTER_TABLE==1) {
				$query=$this->db->query("SELECT ctt.customer_type_types_id,ctt.reseller_id,ctt.name FROM eb_customer_type_of_types ctt
					
					WHERE ctt.reseller_id=$reseller_id AND ctt.is_active=1 AND ctt.customer_type_id=$type_id ");
				//echo $this->db->last_query();exit;
				$var .="<option value='-1'>Select</option>";
				if($query && $query->num_rows()>0){
					foreach($query->result() as $hotel){
						
						//if customer type types id is already assigned to customer then no need to display in select box
						
							$var .="<option value='".$hotel->customer_type_types_id."' ".(($hotel->customer_type_types_id==$selected_id)?'SELECTED':'').">".$hotel->name."</option>";
						

					}			
				}				
			}else{
				$var .= $is_multy_type_query->row()->is_commercial_multi_box;
			}
		}else{
			$var ='';
		}
		echo $var;
	}
	public function chk_Dup_Name(){
		$where='';		
		$reseller_id='-1';
		$hotel_name=strtoupper($_POST['hotel_name']);
		$id_val=(isset($_POST['id_val']))?$_POST['id_val']:'';
		$reseller_id=$_POST['reseller_id'];
		if($reseller_id != '-1'){
			$where .=" AND reseller_id=$reseller_id ";
		}
		if($id_val !='' && $id_val !=0){
			$where .=" AND customer_type_types_id <> $id_val";
		}
		$query = $this->db->query("SELECT COUNT(customer_type_types_id)num FROM eb_customer_type_of_types WHERE UPPER(name) ='$hotel_name' $where");
		//echo $this->db->last_query();
		if($query && $query->num_rows()>0){
			if($query->row()->num ==0){
				echo "0";
			}else{
				echo "1";
			}
		}
	
	}
	public function chk_Dup_Type(){
		$where='';		
		$cus_type = strtoupper($_POST['cus_type']);
		$id_val=(isset($_POST['id_val']))?$_POST['id_val']:'';	
		
		if($id_val !='' && $id_val !=0){
			$where .=" AND customer_type_id <> $id_val";
		}
		$query = $this->db->query("SELECT COUNT(customer_type)num FROM eb_customer_types WHERE UPPER(customer_type) ='$cus_type' $where");
		//echo $this->db->last_query();
		if($query && $query->num_rows()>0){
			if($query->row()->num ==0){
				echo "0";
			}else{
				echo "1";
			}
		}
	
	}
	
	public function checkHotelWithCustomer(){
		$customer_id = 0;
		$cus_cond = '';
		$hotel_id = $_POST['hotel_id'];
		$customer_id = (isset($_POST['customer_id']))?$_POST['customer_id']:0;
		if($customer_id !=0){
			$cus_cond = " AND customer_id <> $customer_id";
		}
		$query = $this->db->query("SELECT COUNT(customer_id)num FROM customer WHERE customer_type_types_id=$hotel_id $cus_cond");
		//echo $this->db->last_query();
		if($query && $query->num_rows()>0 && $query->row()->num>0){
			echo "1";
		}
		else{
			echo "0";
		}
		
	}
	
	public function codeWithEmp(){
		$code = '';
		$emp = 0;
		$where = '';
		$emp = (isset($_POST['emp']))?$_POST['emp']:0;
		$code = (isset($_POST['code']))?strtoupper($_POST['code']):'';
		if($code !=''){
			$where .= " AND UPPER(dist_subdist_lcocode) = '$code' ";
		}
		if($emp != 0){
			$where .=" AND employee_id = $emp ";
		}
		$query = $this->db->query("SELECT employee_id,dist_subdist_lcocode,city,state FROM employee WHERE dealer_id=".$_SESSION['user_data']->dealer_id." $where ");
		//echo $this->db->last_query();
		if($query && $query->num_rows()>0){
			echo $query->row()->employee_id."@@".$query->row()->dist_subdist_lcocode."@@".$query->row()->city."@@".$query->row()->state ;
		}else{
			echo '';
		}
	}
	
	public function getActiveStbcount(){		
		$hotel_id = $_POST['id'];
		$max_lim = $_POST['max_lim'];
		
		$query=$this->db->query("SELECT COUNT(cd.customer_device_id)num FROM eb_customer_type_of_types ct INNER JOIN customer c ON c.customer_type_types_id=ct.customer_type_types_id INNER JOIN customer_device cd ON cd.customer_id=c.customer_id AND cd.device_closed_on IS NULL WHERE ct.customer_type_types_id=$hotel_id");
		if($query && $query->num_rows()>0){
			if(($query->row()->num -1) < $max_lim ){
				echo 0;
			}else{				
				echo $query->row()->num;
			}		
		}
	}
	//get lco location for unpaired vc transfer added by Gopi
	public function getLcoLocations(){
		$source_loc = $_POST['source_loc'];
		$reseller_id = $_POST['reseller_id'];
		$val = '<option value=-1> -- Select -- </option>';
		$get_lco_location = $this->dasmodel->getLcoStockLocations($from_vc=1);
		if(count($get_lco_location))
		{
			foreach($get_lco_location as $res)
			{
			    if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME") 
				{
					if($res->users_type=='DEALER'){
						$title = "";
						$name = " ";
						$business_name = $res->location_name;
					}else{
						$title =$res->reseller_name;
						$name = " (".trim($res->business_name).")";
						$business_name = $res->dist_subdist_lcocode;
					}
				}
				else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") 
				{
					if($res->users_type=='DEALER'){
						$title = "";
						$name = " ";
						$business_name = $res->location_name;
					}else{
						$title = $res->dist_subdist_lcocode;
						$name = " (".trim($res->reseller_name).")";
						$business_name = $res->business_name;
					}
				}
				else {
					if($res->users_type=='DEALER'){
						$title = "";
						$name = " ";
						$business_name = $res->location_name;
					}else{
						$title = trim($res->reseller_name);
						$name = "(".$res->dist_subdist_lcocode.")";
						$business_name = $res->business_name;
					}
					
				}  
				if($res->reseller_id!=$reseller_id){
					$val .= "<option title='".$title."' value='".$res->location_id."'  >".$business_name.$name."</option>";
				}
				
				
			}
		}
		
		echo $val;
	}
	//function for get reseller products added by Gopi
	function getResellerProducts(){
		$int_reseller_id = $_POST['reseller_id'];
		$product_id = $_POST['product_id'];
		$int_dealer_id = $_SESSION['user_data']->dealer_id;
		$val = "<option value='-1'>Select</option>";
		$query = $this->db->query("SELECT rpm.product_id, p.pname,bs.cas_server_type
			FROM `eb_reseller_product_mapping` rpm
			INNER JOIN eb_products p ON p.product_id = rpm.product_id
			INNER JOIN backend_setups bs ON bs.backend_setup_id=p.backend_setup_id
			WHERE rpm.employee_id  IN ($int_reseller_id)
			AND rpm.dealer_id =$int_dealer_id ");
			if($query && $query->num_rows>0){
				foreach($query->result() as $row){
					$val .= '<option value="'.$row->product_id.'"'.(($row->product_id==$product_id)?' selected="SELECTED"':'').'>'.$row->pname.'['.$row->cas_server_type.']</option>';
					
				}
			}
		
			echo $val;
	}

	//function by sravani for getting date difference
	function getDateDiff()
	{
		$start_date = $_POST['start_date'].'-01';
		$end_date = $_POST['end_date'].'-01';
		$ts1 = strtotime($start_date);
		$ts2 = strtotime($end_date);
		$year1 = date('Y', $ts1);
		$year2 = date('Y', $ts2);
		$month1 = date('m', $ts1);
		$month2 = date('m', $ts2);
		$diff = (($year2 - $year1) * 12) + ($month2 - $month1);
		echo $diff;
	}

	// function for make it normal added by Gopi 
	public function makeItNormal(){
		$int_dealer_id = $_SESSION['user_data']->dealer_id;
		$int_employee_id = $_SESSION['user_data']->employee_id;
		$stock = $_POST['stock_id'];
		$remarks = $_POST['remark'];
		$stock_details = explode(',',$stock);
		$success_count =0;
		$failure_count =0;
		$total_count = count($stock_details);
		foreach($stock_details as $stock){
			$query = $this->db->query("SELECT show_in_service_center,isSurrended,defective_stock  FROM eb_stock WHERE stock_id='$stock'");
			$is_in_service_center = 0;
			$isSurrended = 0;
			$defective_stock = 0;
			if($query && $query->num_rows()>0)
			{
				$is_in_service_center = $query->row()->show_in_service_center;
				$isSurrended = $query->row()->isSurrended;
				$defective_stock = $query->row()->defective_stock;
			}
			if($is_in_service_center==0 && ($isSurrended==1 || $defective_stock==1))
			{
				$sql = "UPDATE eb_stock SET show_in_service_center = 0,defective_stock=0,isSurrended=2 WHERE stock_id='$stock' ";
				$query = $this->db->query($sql);
				//echo $this->db->last_query();
				if($query){
					$date_format = date('Y-m-d H:i:s');
					$defective_log_id = $this->inventorymodel->getMaximumDefectiveStockId($stock);
					if($defective_log_id!=0){
						$this->db->query("UPDATE eb_defective_stock_logs SET modified_date='$date_format',status=1 ,modified_by=$int_employee_id,normalremarks='$remarks' WHERE defective_log_id=$defective_log_id");
					}
					$success_count++;
				}
			}
		}
		if($success_count > 0){
				// start logs
			$comment = "User Name:".$_SESSION['user_data']->employee->username.", ".$_SESSION['user_data']->first_name." ".$_SESSION['user_data']->last_name." has made $success_count STB(S) as Normal STB(S) ";
			$this->change_pass_model->updateLog($comment);	
		}
		$failure_count = $total_count - $success_count;
		echo $success_count.'xxx'.$failure_count;
	}
		// function for send to service center added by Gopi 
	public function sendToServiceCenter(){
	
		$int_dealer_id = $_SESSION['user_data']->dealer_id;
		$int_employee_id = $_SESSION['user_data']->employee_id;
		if($_SESSION['user_data']->employee_parent_type!='' && $_SESSION['user_data']->employee_parent_type!='null'){
			$employee_details = $this->EmployeeModel->getEmployeeDetails(0,$_SESSION['user_data']->username);
			$employee_id = $employee_details->employee_id;
			} else {
				$employee_id = $_SESSION['user_data']->employee_id;
			}
		$stock = $_POST['stock_id'];
		$remarks = isset($_POST['remark'])?$_POST['remark']:'';
		$stock_details = explode(',',$stock);
		$success_count =0;
		$failure_count =0;
		$total_count = count($stock_details);
		foreach($stock_details as $stock){
			$query = $this->db->query("SELECT defective_stock, isSurrended  FROM eb_stock WHERE stock_id='$stock'");
			$is_defective_stock = 0;
			$isSurrended  = 0;
			if($query && $query->num_rows()>0)
			{
				$is_defective_stock = $query->row()->defective_stock;
				$isSurrended = $query->row()->isSurrended;
			}
			if($is_defective_stock==1 || $isSurrended==1)
			{
				$sql = "UPDATE eb_stock SET show_in_service_center = 1 WHERE stock_id='$stock' ";
				$query = $this->db->query($sql);
				//echo $this->db->last_query();
				if($query){
					$res = $this->inventorymodel->insertServiceCenterData($stock ,$remarks,$employee_id);
					if($res){
						$success_count++;
					}
				}
			}
			
		}
		if($success_count > 0){
				// start logs
			$comment = "User Name:".$_SESSION['user_data']->employee->username.", ".$_SESSION['user_data']->first_name." ".$_SESSION['user_data']->last_name." has done transfer $success_count STB(S) from MSO to service center ";
			$this->change_pass_model->updateLog($comment);	
		}
		
				
		$failure_count = $total_count - $success_count;
		echo $success_count.'xxx'.$failure_count;
	}
	
	// to search reseller month wise invoices in invoice table
	public function searchInvoice(){		
		$bulk_obj = new bulkoperationmodel();
		$dealer_id = $_POST['dealer_id'];
		$reseller_id = $_POST['reseller_id'];
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$months_array = array();
		$employee_id = $_SESSION['user_data']->employee_id;
		$display = "";
		//echo "dealer_id : ". $dealer_id . "<br>";
		//echo "reseller_id : ". $reseller_id . "<br>";
		//echo "start_date : ". $start_date . "<br>";
		//echo "end_date : ". $end_date . "<br>";
			$months_array = $bulk_obj->getArrayofMonths($start_date, $end_date);
			if(count($months_array)>0){
			
				$display .= "<table class='mygrid'  cellpadding='1' cellspacing='1' border='0' style='width:25%'>";
				$display .= "<tr>";
				$display .= "<th>Bill Month</th>";
				$display .= "<th>Invoice Status</th>";
				$display .= "</tr>";
			
				foreach($months_array as $bill_month){
					$invoice_details = $bulk_obj->invoice_details($dealer_id, $reseller_id, $bill_month);
					if(count($invoice_details)>0){
						$status = $invoice_details->status;
						$file_path = $invoice_details->file_path;
						$file_name = basename($file_path);
						$invoice_id = $invoice_details->invoice_id;
						
						
						$display .= "<tr><td>".$bill_month."</td>";
						if($status==0){
							
							$display .= "<td>New Entry</td></tr>";
						}elseif($status==1){
							
							$display .= "<td>Processing...</td></tr>";
							
						}elseif($status==2){
							
							$display .= "<td><a class='download_file' rid='$reseller_id' bmnth='$bill_month' href='javascript: void(0)'>".$file_name."</a></td></tr>";
						}
						
						
						// $status = 0; Invoice Request Inserted 
						// $status = 1; Invoice Request Processing
						// $status = 2; Invoice generated 
						
					}else{
						//Invoice not generated
						//Insert request to generate invoice
						$bulk_obj->insert_request($dealer_id, $reseller_id, $bill_month, $employee_id);
						$status = 0;
						$display .= "<tr><td>".$bill_month."</td>";
						$display .= "<td>New Entry</td></tr>";
					}
				}
				$display .= "</table>";
			}
		echo $display;
		
	}

	
	public function check_channel_id_map()
	{
		$selected = $_POST['selected'];
		$res_str = '';
		$is_checked=0;
		if(count($selected)>0){
			$channels_ids = implode($selected, ','); 
			$sql_str = "SELECT COALESCE(GROUP_CONCAT(channel_name SEPARATOR ', '), 'NA') channel_names FROM eb_channels
			WHERE channel_id IN ($channels_ids) AND cisco_channel_id = ''
			";
			$query = $this->db->query($sql_str);
			if($query && $query->num_rows()>0)
			{
				echo $query->row()->channel_names;
			}
		}
	}

	// to get serial number lengths
	public function getSerialnumberLengths(){	
	
		$sip = $_POST['sip'];
		
		$did = $_SESSION['user_data']->dealer_id;
		$sql = $this->db->query("select pairing,sl_no_min,sl_no_max,min_vc_length,max_vc_length from backend_setups where backend_setup_id=$sip and dealer_id = $did");
		if($sql && $sql->num_rows()>0){
			
			echo $sql->row()->pairing.'*'.$sql->row()->sl_no_min.'*'.$sql->row()->sl_no_max.'*'.$sql->row()->min_vc_length.'*'.$sql->row()->max_vc_length;
		}else {
			echo 0;
		}
	}
	//check stb status
        public function checkStbStatus(){
            $stock_id = $_POST['stock_id'];
            $sql = $this->db->query("SELECT * from eb_stock where status=3 and stock_id=$stock_id");
            if($sql && $sql->num_rows()>0){
                        echo 1;
	    }else {
			echo 0;
	    }
        }
	function activate_model(){
			$response = 0;	
    		$modelid = $_POST['modelid'];	
    		$sql_str = "UPDATE eb_models SET status = 1 WHERE model_id = $modelid";
            $query = $this->db->query($sql_str);			
            if($query)
            {            
				$response = 1;		
			}
			else $response = 0;		
			echo $response;
		
	}
	function activate_make(){
    			
    		$response = 0;	
    		$makeid = $_POST['makeid'];	
    		$sql_str = "UPDATE eb_makes SET status = 1 WHERE make_id = $makeid";
            $query = $this->db->query($sql_str);			
            if($query)
            {            
				$response = 1;		
			}
			else $response = 0;		
			echo $response;
    }
	function check_unpiar()
	{
		
		$stock_id=$_POST['stock_id'];
		$sql_str="SELECT serial_number,box_number,vc_number, not_paired_in_cas FROM eb_stock WHERE stock_id=$stock_id  AND dealer_id=".$_SESSION['user_data']->dealer_id;
            $query = $this->db->query($sql_str);
            if($query && $query->num_rows()>0)
            {            
				if($query->row()->not_paired_in_cas!=1 && $query->row()->vc_number!='' && $query->row()->box_number!='' ){
				echo 1;
				}
			}
			else echo 0;
	}
	function isVCPairedWithSerialNumber()
	{
		$stock_id=$_POST['stock_id'];
		$sql_str="SELECT vc_number FROM eb_stock WHERE stock_id=$stock_id  AND dealer_id=".$_SESSION['user_data']->dealer_id;
            $query = $this->db->query($sql_str);
            if($query && $query->num_rows()>0)
            {   
				echo $query->row()->vc_number;
			}
			else echo 0;
		
	}
	function isassigned()
	{
		$cust = new CustomersModel();
		$serial_number = $_POST['serial_number'];
		$reseller_id_from_ui = $_POST['reseller_id'];
		$stock_id = $_POST['stock_id'];
		if($cust->isSTB_free($serial_number)==0)
		{
			$stockdetails=$this->dasmodel->getStockDetails($stock_id);
			$stock_reseller_id=$stockdetails->reseller_id;
			$is_trash=$stockdetails->is_trash;
			$isSurrended=$stockdetails->isSurrended;
			$defective_stock=$stockdetails->defective_stock;
			$not_paired_in_cas=$stockdetails->not_paired_in_cas;
			$pairing=$stockdetails->pairing;
			$vc_number=$stockdetails->vc_number;
			$box_number=$stockdetails->box_number;
			//echo $pairing;
			if($stock_reseller_id !=$reseller_id_from_ui)
			{
			echo -6;
			}
			else if($is_trash==1)
			{
				echo -1;
			}
			else if($isSurrended==1)
			{
				echo -2;
			}
			else if($defective_stock==1)
			{
				echo -3;
			}
			else if($pairing==1 && ($not_paired_in_cas==1 || $vc_number=='' || $box_number=='') )
			{  
				echo -4;
			}
			else{
				echo 1;
			}
      }
		else{
			echo -5;//"STB is already assigned to another customer";
		}
	}
	//check product channels when update the product details added by Gopi
	public function checkProductChannels(){
		$product_id = ($_POST['product_id'])?$_POST['product_id']:'0';
		$dealer_id = $_SESSION['user_data']->dealer_id;
		if($product_id!=0){
			$query = $this->db->query("SELECT count(product_channels_id) cnt from eb_product_channels where product_id=$product_id and dealer_id=$dealer_id");
			if($query && $query->num_rows()>0){
				
				echo $query->row()->cnt;//if channels are there for select product
			}else{
				echo 0;
			}
		}else{
			echo 0;
		}
	}

	//check location is_primary value by sravani
	public function checkLocationIsPrimary()
	{
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$query = $this->db->query("SELECT location_name,location_id FROM eb_stock_location WHERE is_primary	= 1 AND dealer_id = $dealer_id");
		if($query && $query->num_rows()>0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}

	//set session values from dashboard By Gopi
	public function setSessionValues(){
		$search_option = ($_POST['search_option'])?$_POST['search_option']:'';
		$search_with = ($_POST['search_with'])?$_POST['search_with']:'0';
		$from_dashboard = ($_POST['from_dashboard'])?$_POST['from_dashboard']:'0';
		$_SESSION['search_option'] = $search_option;
		$_SESSION['search_with'] = $search_with;
		$_SESSION['from_dashboard_search'] = $from_dashboard;
	} 
	//un set session values from dashboard By Gopi
	public function unsetSessionValues(){
		$global_search = ($_POST['global_search'])?$_POST['global_search']:'0';
		if($global_search==1){
			unset($_SESSION['global_search_stb']);
		}else if($global_search==2){
			unset($_SESSION['global_search_customer_no']);
		}
		unset($_SESSION['from_dashboard_search']);
		unset($_SESSION['search_with']);
		unset($_SESSION['search_option']);
	} 
	//set session values from dashboard for links By Gopi
	public function setDashboardSessionvalue(){
		
		$from_dashboard = isset($_POST['from_dashboard'])?$_POST['from_dashboard']:'0';
		$paid_or_unpaid = isset($_POST['paid_or_unpaid'])?$_POST['paid_or_unpaid']:'1';
		$_SESSION['from_dashboard'] = $from_dashboard;
		$_SESSION['paid_or_unpaid'] = $paid_or_unpaid;
	} 
	public function checkLocationisExist()
	{
		$location_name = trim($_POST['textBox']);
		$location_id = ($_POST['location_id'])?$_POST['location_id']:0;
		$inventory = $this->inventorymodel->isInventoryLocationExist($location_name,$location_id);
		if($inventory>0)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
	}
	public function remove_channels()
	{
		$obj_channels = new channelsmodel();
		$product_id = $_POST['product_id'];
		$channels = $_POST['selected'];
		$selectedChannels = $obj_channels->getProductChannels($product_id);
		$mapped_channels = "";
		$unmapped_channels = "";
		$old_channels_array = array();
		$get_channels_arr = array();
		foreach($selectedChannels as $row){
			$old_channels_array[] = $row->channel_id;
			if(!(in_array($row->channel_id,$channels))){
				$unmapped_channels.= $row->channel_name.',';
			}
		} 
		
		/*foreach($channels as $old_channel_type){
			if(!(in_array($old_channel_type,$old_channels_array))){
				$mapped_channels.= $get_channels_arr[$old_channel_type].',';
			}
		}
		$mapped_channels = rtrim($mapped_channels,',');*/
		$unmapped_channels = rtrim($unmapped_channels,',');
		
		if($unmapped_channels!=""){
			echo $unmapped_channels;
		}else{
			echo 0;
		}
	}
	public function getVCScrapPopup()
	{
		$das_obj = new DasModel(); 
		$comment = "";
		$com = new change_pass_model();
		$vc_number = $_POST['vc_number'];
		$reseller_id = $_POST['reseller_id'];
		$vcArray = $das_obj->checkVcNumber($vc_number);
		
		$is_valid =0;
		$html='';
		$option='';
		if(count($vcArray)>0)
		{
			$vcResellerId = $vcArray->reseller_id;
			$is_trash = $vcArray->is_trash;
			if($is_trash==1)
			{   //echo = "VC Number is already trashed.";
				$is_valid = 0;
				echo 2;
				exit;
			}elseif($vcResellerId!=$reseller_id){
				$is_valid = 0;
				//echo "VC Number does not exist in the STB location.";
				echo 1;
				exit;
			}else{
				$is_valid = 1;
			}
			
			
		}
		else
		{
			$is_valid = 0;
			//echo "VC Number is already paired with another STB.";
			echo 3;
			exit;
		}
		if($is_valid==1)
		{
			$vc_mac_label = (isset($_SESSION['cas_term']['vc_number'][0]) ? $_SESSION['cas_term']['vc_number'][0] : $_SESSION['mac_vc_column']);
			$html="<table border='0' cellspacing='5' cellpadding='5' width='100%'>
		           
		            <tr>
		               <td align='left'>$vc_mac_label</td>
		               <td align='center'>:</td>
		               <td align='left'>".$vc_number."</td>
					    <input type='hidden' name='vc_number' id='vc_number' value='".$vc_number."'>
					    <input type='hidden' name='reseller_id' id='reseller_id' value='".$reseller_id."'>
					    
		            </tr>
		           
		            <tr>
		              <td align='left'>Remarks<span class='important_field'>*</span></td>
		              <td align='center'>:</td>
		              <td align='left'><textarea name='remark_txt' id='remark_txt'></textarea></td>
		            </tr>
		            
		        </table>";
				echo $html;
		}
		
		
	}
	//function by sravani for checking is vc scrapped or not
	public function isVCScrapped()
	{
		$das = new dasmodel();
		$vcnumber = $_POST['vcnumber'];
		$isscrapped = $das->checkVcNumber($vcnumber);
		if($isscrapped->is_trash==1)
		{
			echo 1;
		}
		else
		{
			echo 0;
		}
		
	}
	//function by sravani for display popup while deactivating customer stb
	public function details_of_customer()
	{
		
		$html='';
		$option='';
		$dealer_id=$_SESSION['user_data']->dealer_id;
                 $is_from_deact=isset($_POST['is_from_deact'])?$_POST['is_from_deact']:0;
		foreach($_SESSION['deactivation'] as $k=>$v){
                     if($is_from_deact>0){
                         $action_type = $this->DasModel->getActionType($k);
                    }else{
                        $action_type = 0;
                    }
                    //get action type to check the condition for unpaid customer by GOPI
                   if($action_type!=2){
                       if($v!='Temporary Deactivation'){
                            $option.="<option value='".$k."'>".$v."</option>";
                       }
                   }
		}
		$customer_fname = $_POST['first_name'];
		$customer_lname = ($_POST['last_name'])?$_POST['last_name']:'';
		$customer_name = $customer_fname.''.$customer_lname;
		$customer_id = $_POST['customer_id'];
		$customer_account_no = $_POST['account_number'];
		$customer_caf = $_POST['caf_no'];
		$customer_crf = $_POST['customer_account_id'];
		
		if(isset($_SESSION['dealer_setting']->SHOW_CAF) && $_SESSION['dealer_setting']->SHOW_CAF==1){
			$crf_caf = 'CAF Number';
			$crf_caf_val = $customer_caf;
			
		}
		else
		{
			$crf_caf = 'CRF Number';
			$crf_caf_val = $customer_account_no;
		}
		
		$html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>
		         
		            <tr>
		               <td align='left'>Customer Name</td>
		               <td align='center'>:</td>
		               <td align='left'>
		               ".$customer_name."
		               </td>
					   <input type='hidden' id='cust_name' value='".$customer_name."'>
					   <input type='hidden' id='cust_id' value='".$customer_id."'>
		            </tr>
		            <tr>
		               <td align='left'>Customer Id/Account Number</td>
		               <td align='center'>:</td>
		               <td align='left'>".$customer_id.' ( '.$customer_account_no.' )'."</td>
		            </tr>
		           
					<tr>
		               <td align='left'>$crf_caf</td>
		               <td align='center'>:</td>
		               <td align='left'>".$crf_caf_val."</td>
		            </tr>
					
		            <tr>
		               <td align='left'>Deactivation Reason<span class='important_feild'>*</span></td>
		               <td align='center'>:</td>
		               <td align='left'>
		                 <select name='deactivation_reason' id='deactivation_reason'>
		                   <option value=''>Select Reason</option>
		                   ".$option."
		                 </select>
		               </td>
		            </tr>
		            <tr>
		              <td align='left'>Remarks<span class='important_feild'>*</span></td>
		              <td align='center'>:</td>
		              <td align='left'><textarea name='remark_txt' id='remark_txt'></textarea></td>
		            <tr>";
					$setting='SEND_OSD_AFTER_DEACTIVATION';
					$lovValue = $this->change_pass_model->getLovValue($setting,$dealer_id);
					if($lovValue>0){
						$html.=" <td colspan='2'></td>
						  <td align='left'> <input style='text-align:center; vertical-align:middle' type='checkbox' name='send_osd' id='send_osd' value='1'> Send OSD</td>
						</tr>";
					}   
		       $html.="</table>";
		
		echo $html;
	}
	//function by gopi
	public function checkCustomerCheckedStbs(){
		$product_id = $_POST['product_id'];
		$stb_types = $_POST['stb_type'];
		$stb_types = rtrim($stb_types,',');
		$stb_type = array();
		$stb_type =explode(',',$stb_types);
		
		$check_customer_stb_type = $this->inventorymodel->checkCustomerStbType($product_id,$stb_type);
			if($check_customer_stb_type!=""){
				echo $check_customer_stb_type;
			}else{
				echo '';
			}
	}
	//function by sravani for delete location
	public function delete_location()
	{
		$location_id = $_POST['location_id'];
		$is_stock_exist = $this->db->query("SELECT count(stock_id)cnt FROM eb_stock es INNER JOIN eb_stock_location esl ON 
											esl.location_id = es.stock_location WHERE esl.location_id=$location_id ");
		if($is_stock_exist && $is_stock_exist->num_rows()>0)
		{
			$count = $is_stock_exist->row()->cnt;
			if($count>0)
			{
				echo 1;
			}
			else
			{
				echo 0;
			}
		}
		else
		{
			echo 0;
		}
	}

	//function to check stb type mapping
	public function check_stb_type(){
		$obj_products = new productsmodel();
		$product_id = $_POST['product_id'];
		$stock_id = $_POST['stock_id'];
		//check stb type mapped with product or not
		$check_stbtype_mapping=$obj_products->checkStbTypeMapping($stock_id,$product_id);
		if($check_stbtype_mapping==1){
			echo 1;
		}else{
			echo 0;
		}
	}
        //function to check user is eligible to activate the services or not
       
        public function checkIsEligibleUserToActivate($product_id=0,$stock_id=0,$cust_id=0,$reason_id=0){
            //$customers = new CustomersModel();
			$cf= new CommonFunctions();
			//print_r($_SESSION['user_data']);
			$employeeId=isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
			$dealerId=isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
			$productId = (isset($_POST['productId'])) ? $_POST['productId'] : $product_id;
			$stock_id = (isset($_POST['stock_id'])) ? $_POST['stock_id'] : $stock_id;
			$cid = (isset($_POST['cust_id'])) ? $_POST['cust_id'] : $cust_id;
			/*$where = array();
			$where[]=' cs.status=0';
			if($stock_id>0)
			{
				$where[]=' scs.stock_id="'.$stock_id.'"';
			}
			if($productId>0)
			{
				$where[]=' scs.product_id="'.$productId.'"';
			}
			if($cid>0)
			{
				$where[]=' cs.customer_id="'.$cid.'"';
			}
			if(count($where)>0)
			{
				$whereCondition = ' where '.implode(" and ",$where);
			}
			else
			{
				$whereCondition ='';
			}*/
			$check_is_eligible_user = $cf->checkIsEligibleUserToActivateNew($employeeId,$dealerId,$cid,null,$stock_id,$productId);
			//echo $this->db->last_query();
			//$check_is_eligible_user = $customers->checkIsEligibleUserToActivate($cid, $stock_id, $productId);
			$data = '';

			/*if (!$check_is_eligible_user) {
				$query = $this->db->query("SELECT cs.updated_by,e.users_type,e.employee_parent_type ,e.business_name,e.first_name,e.last_name,e.dist_subdist_lcocode
							  from eb_customer_service cs
							  INNER JOIN eb_stock_cas_services scs ON scs.customer_service_id=cs.customer_service_id  
							  LEFT JOIN employee e ON e.employee_id=cs.updated_by
							 $whereCondition
							 ORDER  BY scs.customer_service_id desc LIMIT 1");
				if ($query && $query->num_rows() > 0) {
					if ($query->row()->updated_by == -1) {
						$data = "You don't have access to activate this service, because service is deactivated previously by your higher Authority.";
					} else {
						$users_type = $query->row()->users_type;
						$employee_parent_type = $query->row()->employee_parent_type;
						$code = $query->row()->dist_subdist_lcocode;
						$business_name = ($query->row()->business_name) ? trim($query->row()->business_name) : $query->row()->first_name . ' ' . $query->row()->last_name;
						$uname = trim($query->row()->first_name . " " . $query->row()->last_name);
						if ($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "BNAME") {
							$value = $code;
							$name = "(" . $business_name . ")";
							$title = $uname;
						} else if ($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "NAME") {
							$title = $code;
							$name = "(" . $uname . ")";
							$value = $business_name;
						} else if ($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO == "CODE") {
							$title = $uname;
							$name = "(" . $code . ")";
							$value = $business_name;
						} else {
							$title = $code;
							$name = "(" . $uname . ")";
							$value = $business_name;
						}

						if ($users_type == 'RESELLER' || $users_type == 'DISTRIBUTOR' || $users_type == 'SUBDISTRIBUTOR' || $employee_parent_type == 'DISTRIBUTOR' || $employee_parent_type == 'SUBDISTRIBUTOR' || $employee_parent_type == 'RESELLER') {
							$data = "You don't have access to activate this service,because service is deactivated previously by $users_type [$name $value]";
						} else {
							$data = "You don't have access to activate this service,because service is deactivated previously by $users_type [$business_name]";
						}
					}
				}
			}*/

			if(!$check_is_eligible_user){
				$data = "You don't have access to activate this service,because service is deactivated previously by Higher Authority";
			}else {
				$data = "";
			}
			if (isset($_POST['productId']) || isset($_POST['deactive_check'])) {
				echo $data;
			} else {
				return $data;
			}
        }
    
	  //function to check stb type mapping
    public function getcustomerBillAmount() {
        $das_obj = new DasModel();
        $obj_products = new productsmodel();
        $product_ids = $_POST['product_ids'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $quantity = $_POST['quantity'];
        $stock_id = $_POST['stock_id'];
        $reseller_id = $_POST['reseller_id'];
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : 0;
        $customer_bill_type = isset($_POST['customer_bill_type']) ? $_POST['customer_bill_type'] : 0;
        
         $int_discount_type = isset($_POST['discount_type']) ? $_POST['discount_type'] : 0;
        $int_discount_val = isset($_POST['discount_val']) ? $_POST['discount_val'] : 0;
        $date_discount_start_date = isset($_POST['discount_start_date']) ? $_POST['discount_start_date'] : '';
        $date_discount_end_date = isset($_POST['discount_end_date']) ? $_POST['discount_end_date'] : '';
        
        log_message("debug","=========int_discount_type============".$int_discount_type);
        log_message("debug","=========int_discount_val============".$int_discount_val);
        log_message("debug","=========int_discount_val============".$date_discount_start_date);
        log_message("debug","=========int_discount_val============".$date_discount_end_date);
        log_message("debug","=========product_ids============".json_encode($product_ids));
        
        $int_enable_stb_discount = isset($_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT) ? $_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT : 0;
        
        $total_amount_values = '';
        $date_values = '';
        $total_base_prices = '';
        $end_date_val = date('Y-m-t');
        $active_count_date = 0;
        $calender_billing = 0;
        $dealerId = isset($_SESSION['user_data']->dealer_id) ? $_SESSION['user_data']->dealer_id : 0;
        if($dealerId>0){
            $active_count_date = $this->change_pass_model->getLovValue('BILLING_ACTIVE_COUNT_DATE',$dealerId);
            $calender_billing = $this->change_pass_model->getLovValue('CALENDER_BILLING',$dealerId);
            $addon_after_base_pack_int = $this->change_pass_model->getLovValue('ADDON_AFTER_BASEPACK',$dealerId);
            if(date('d')<$active_count_date){
               // If the bill generating before bill bill date PRASANNA 09-04-2019
               $actual_bill_period_start_date = date("Y-m-d", strtotime("-1 month", strtotime(date('Y-m-'.$active_count_date))));
               $end_date_val = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-'.$active_count_date))));
//               $days_in_current_month = abs((strtotime($actual_bill_period_start_date)-strtotime($actual_bill_period_end_date))/86400)+1;
//               $bill_month = date("Y-m-01", strtotime("-1 month", strtotime(date('Y-m-'.$active_count_date))));
            }
            else{
               // If the bill generating after bill bill date PRASANNA 09-04-2019
               $actual_bill_period_start_date = date('Y-m-'.$active_count_date);
               $end_date_val = date("Y-m-d", strtotime('-1 day',strtotime("+1 month", strtotime(date('Y-m-'.$active_count_date)))));
//               $days_in_current_month = abs((strtotime($actual_bill_period_start_date)-strtotime($actual_bill_period_end_date))/86400)+1;
//               $bill_month = date('Y-m-01');
            }
        }
        $get_extra_params['active_count_date']=$active_count_date;
        $get_extra_params['calender_billing']=$calender_billing;
        /* echo '<pre>';print_r($product_ids);
          echo '<pre>';print_r($start_date);
          echo '<pre>';print_r($end_date);
          echo '<pre>';print_r($quantity);
          echo '<pre>';print_r($stock_id); 
          echo "addon_after_base_pack_int".$addon_after_base_pack_int;*/
          $j=0;
        $end_dates_replaced_arr=array();
    	if($addon_after_base_pack_int==1){
    		foreach($product_ids as $key => $product_id) 
        	{
        		$end_date_view_int = isset($end_date[$key])?$end_date[$key]:'';
        		$stock_id_int = isset($stock_id[$key])?$stock_id[$key]:'';
        		$end_dates_view_arr[$j]=$end_date_view_int;
        		$stock_ids[$j]=$stock_id_int;
        		$j++;
        	}
    		$end_dates_replaced_arr=$obj_products->compare_addon_base_dates($stock_ids,$product_ids,$end_dates_view_arr,$customer_id,$dealerId);
    	}
    	//echo "<pre>";print_r($end_dates_replaced_arr);exit;
    	write_to_file("In getcustomerBillAmount product_ids---".json_encode($product_ids));
    	write_to_file("In getcustomerBillAmount end_dates_replaced_arr---".json_encode($end_dates_replaced_arr));

        foreach ($product_ids as $key => $product_id) {

            $product_details_ary = $obj_products->getservice($product_id);
            
            $product_details = $product_details_ary[0];
            $base_price = isset($product_details->base_price)?$product_details->base_price:0;
            $total_base_prices .= round($base_price, 2) . ',';
            $pst = $product_details->pricing_structure_type;
            $bill_type = $product_details->bill_type;
            //write_to_file("In getcustomerBillAmount foreach products enddate---".json_encode($end_dates_replaced_arr[$product_id])."product_id===".$product_id);
            if(isset($end_dates_replaced_arr[$product_id]) && $end_dates_replaced_arr[$product_id]!=''){
				$end_date1 = $end_dates_replaced_arr[$product_id];
			}else{
				$end_date1 = $end_date[$key];
			}
			write_to_file("In getcustomerBillAmount foreach products end_date1---".json_encode($end_date1)."product_id===".$product_id."end_date_val===".$end_date_val);
            if($end_date1>$end_date_val && $product_details->pricing_structure_type==2){
                $end_date1 = $end_date_val;
            }
            write_to_file("In getcustomerBillAmount foreach products at last end_date1---".json_encode($end_date1)."end_date_val==".$end_date_val."product_id===".$product_id);
            $bill_period_start = $this->CustomersModel->getBillperiodStartDate($start_date[$key], $end_date1, $product_id, $customer_id, $stock_id[$key],$pst);	
            if($bill_period_start==""){
            	echo rtrim(0, ',') . '@@' . rtrim(0, ',') . '##' . rtrim($total_base_prices, ',');exit;
            }
            
             //DISCOUTN RELATED CODE ADDE BY VENKAT (08-04-2020)
            $int_new_customer_flag = 1;
            if($customer_id > 0){
              $int_new_customer_flag = 0;  
            }
            $arr_discount_info = array('discount_type'=>$int_discount_type,
                                          'discount_val'=>$int_discount_val,
                                          'discount_start_date'=>$date_discount_start_date,
                                          'discount_end_date'=>$date_discount_end_date,
                                          'new_customer_flag'=>$int_new_customer_flag);
            
            $arr_discount_details = $das_obj->getDiscount(0, $int_enable_stb_discount,$customer_id, $stock_id[$key], $dealerId, $discount_value_int = 0, $discount_type_int = 0,$product_id,$reseller_id,$customer_discount_type=1,$bill_date='',$arr_discount_info);
            
             $float_get_discount = (isset($arr_discount_details) && $arr_discount_details['discount_val']) ? $arr_discount_details['discount_val'] : 0;
             $int_type_of_discount = (isset($arr_discount_details) && $arr_discount_details['type_of_discount']) ? $arr_discount_details['type_of_discount'] : 1;
                                    
            write_to_file("In getcustomerBillAmount foreach products befor calling getBasePriceAmount enddate---".json_encode($end_date1)."product_id===".$product_id."key--".$key);                   
            $bill_amount = $das_obj->getBasePriceAmount(array($product_id), $reseller_id, array($quantity[$key]), array($stock_id[$key]), array($bill_period_start), array($end_date1), $customer_id, $get_amount = 1,$csid=0,$dealerId,$get_extra_params,array($int_type_of_discount),array($float_get_discount),$serviceExtWithLcoDeposit=0,$customer_bill_type);
            log_message("debug","=========arr_discount_details============".json_encode($bill_amount));
            $bill_amount = isset($bill_amount['total_amount']) ? $bill_amount['total_amount'] : 0;
            $total_amount_values .= round($bill_amount, 2) . ',';
            
            $qty = $quantity[$key];
            if ($pst == 2) {
                $start_date_val = $bill_period_start;
                //$end_date_val = date('Y-m-t');
                $time1 = strtotime($start_date_val);
                if (strtotime($end_date_val) > strtotime($end_date1)) {
                    $time2 = strtotime($end_date1);
                } else {
                    $time2 = strtotime($end_date_val);
                }

                $daycount = floor(($time2 - $time1) / 86400) + 1;
                if ($bill_type == 1) {
                    $date_values .= ' for ' . $daycount . ' day(s) ,';
                } else {
                    $date_values .= '  ,';
                }
            } else if ($pst == 1) {
                $validity_days_type_id = $product_details->validity_days_type_id;
                $valid_days = ($product_details->validity_days) ? $product_details->validity_days : 0;
                if ($validity_days_type_id == 1) { // For Monthly quantity
                    $daycount = $qty * $valid_days;
                    $date_values .= ' for ' . $daycount . ' month(s) ,';
                } elseif ($validity_days_type_id == 2) { // For Yearly quantity
                    $daycount = $qty * $valid_days;
                    $date_values .= ' for ' . $daycount . ' year(s) ,';
                } else { // For Days quantity
                    $daycount = $qty * $valid_days;
                    $date_values .= ' for ' . $daycount . ' day(s) ,';
                }
            }
        }
        if ($total_amount_values != '') {
        	//$total_base_prices added by Swaroop Feb 8 for showing in popup
            echo rtrim($total_amount_values, ',') . '@@' . rtrim($date_values, ',') . '##' . rtrim($total_base_prices, ',');
            // echo .'@@'.rtrim($date_values,',');
        } else {
            echo '';
        }
    }

        //function to get user's due amount
        public function getUsersDueAmount(){
            $employee_id = $_POST['employee_id'];
            $employee_type = $_POST['employee_type'];
            $emp_model = new EmployeeModel();
            
            $result = $emp_model->getEmployeesDueAmount($employee_id,$employee_type,$_SESSION['user_data']->dealer_id, $_SESSION['user_data']->users_type, $_SESSION['user_data']->employee_id);
            echo $result;
            
            
        } 

	
	
		public function details_of_scheme(){
		$html ='';
		$scheme_id=$_POST['scheme_id'];
		$query=$this->db->query("SELECT eism.price,eism.distributor_limit,eism.subdistributor_limit,eism.reseller_limit,eism.model_limit,em.model_name
		FROM eb_indent_scheme_models  eism 
		INNER JOIN  eb_models em ON em.model_id=eism.model_id	 
		WHERE eism.scheme_id=$scheme_id");
		if($query && $query->num_rows()>0){
			$html .="<table width='95%' cellpadding='10' cellspacing='10' border='0'>";
			$html .="<tr align='left'>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Model</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Price</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Model Limit</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Distributor Limit</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Subdistributor Limit</b></th>";			
			$html .="<th style='color:#315C7C;font-size:14px;'><b>LCO Limit</b></th>";			
			$html .="</tr>";
			foreach($query->result() as $record){
				$dl=($record->distributor_limit>0)?$record->distributor_limit:"Unlimited";
				$sdl=($record->subdistributor_limit>0)?$record->subdistributor_limit:"Unlimited";
				$rl=($record->reseller_limit>0)?$record->reseller_limit:"Unlimited";
				$ml=($record->model_limit>0)?$record->model_limit:"Unlimited";
				$html .="<tr align='left'>";
				$html .="<td style='background-color:white;'>".$record->model_name."</td>";
				$html .="<td style='background-color:white;'>".$record->price."</td>";
				$html .="<td style='background-color:white;'>".$ml."</td>";
				$html .="<td style='background-color:white;'>".$dl."</td>";			
				$html .="<td style='background-color:white;'>".$sdl."</td>";			
				$html .="<td style='background-color:white;'>".$rl."</td>";			
				$html .="</tr>";
			}			
			$html .="</table>";
			
		}
		echo $html;
	}
	public function details_of_indent(){
		$html ='';
		$scheme_array=$_POST['scheme_array'];
		$model_array=$_POST['model_array'];
		$quantity_array=$_POST['quantity_array'];
		$price_array=$_POST['price_array'];
			$html .="<table width='95%' cellpadding='10' cellspacing='10' border='0'>";
			$html .="<tr align='left'>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Scheme</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Model</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Quantity</b></th>";
			$html .="<th style='color:#315C7C;font-size:14px;'><b>Total Price</b></th>";			
			$html .="</tr>";
			foreach($scheme_array as $key=>$scheme){
				$html .="<tr align='left'>";
				$html .="<td style='background-color:white;'>".$scheme."</td>";
				$html .="<td style='background-color:white;'>".$model_array[$key]."</td>";
				$html .="<td style='background-color:white;'>".$quantity_array[$key]."</td>";			
				$html .="<td style='background-color:white;'>".$price_array[$key]."</td>";
				$html .="</tr>";
			}			
			$html .="</table>";
			
		echo $html;
	}

	//function by sravani for deactivating services
	public function checkIsNonBasePack()
	{
		if(isset($_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK) && $_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK==1){
			$stock_id = $_POST['stock_ids'];
			$isAddonPkgExist=$this->dasmodel->isExistAddonPkgs($stock_id);
			if($isAddonPkgExist>0)
			{
				echo 1;
			}
			else
			{
				echo 0;
			}
		 }
		else
		 {
			 echo 0;
		 }
	}

	public function details_of_payement(){
		$html ='';
		$indent_id=$_POST['indent_id'];
		$query=$this->db->query("SELECT i.payment_mode,i.paid_amount,i.bank,i.branch,DATE_FORMAT(i.instrument_date, '%d-%m-%Y') AS instrument_date,i.instrument_no,i.order_number,e.business_name,e.dist_subdist_lcocode,e.first_name,e.last_name
		FROM indent i
		inner join employee e on e.employee_id=i.employee_id
		WHERE indent_id=$indent_id");
		if($query && $query->num_rows()>0){
							 $code=$query->row()->dist_subdist_lcocode;
							 $business_name = trim($query->row()->business_name);
							 $uname = trim($query->row()->first_name.$query->row()->first_name);	
							 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
									 $value=$code;
									 $name = "(".$business_name.")";
									 $title = $uname;
							 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
									 $title = $code;
									 $name = "(".$uname.")";
									 $value= $business_name;
							  }
							  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
									$title = $uname;
									$name = "(".$code.")";
									$value= $business_name;
							  }	
							  else {
									$title = 	$users->dist_subdist_lcocode;
									$name = "(".$uname.")";
									$value= $business_name;
							  }

			$html .="<table width='95%' cellpadding='10' cellspacing='10' border='0'>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Requested By</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>$value $name</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Order Number</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->order_number."</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Payment Mode</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->payment_mode."</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Amount</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->paid_amount."</td>";
			$html .="</tr>";
			if($query->row()->payment_mode=='bank'){
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Bank</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->bank."</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Branch</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->branch."</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Cheque/DD Number</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->instrument_no."</td>";
			$html .="</tr>";
			$html .="<tr align='left'>";
			$html .="<td style='background-color:white;'>Instrument Date</td>";
			$html .="<td style='background-color:white;'>:</td>";
			$html .="<td style='background-color:white;'>".$query->row()->instrument_date."</td>";
			$html .="</tr>";
			}
			$html .="</table>";
			
		}
		echo $html;
	}

	        public function check_lco(){
			$employee = new EmployeeModel();
            $lco = $_POST['lco'];
			$lco_details = $employee->getEmployeeDetails($lco);
			echo $lco_details->dist_subdist_lcocode;
        } 
		 public function check_code(){
		 $com = new lco_complaints_model();
            $lco_code = $_POST['lco_code'];
			$lco_id = $com->getEmployeeId($lco_code);
			echo $lco_id;
        }
        //check is trial pack exist
        public function checkIsTrailPackExist(){
            $das_obj = new DasModel();
            $stock_id = $_POST['stock_id'];
            $result = $das_obj->isTrialPackExist($stock_id);
            echo $result;
        }

//function by sravani for checking addon pkg after base pkg	
	public function addnAfterBasePkg($pro_id='',$stock_id=0,$cust_id=0)
	{
		//$productId = $_POST['product'];
		$stck_id = (isset($_POST['stck_id']))?$_POST['stck_id']:$stock_id;
		$cust_id = (isset($_POST['cust_id']))?$_POST['cust_id']:$cust_id;
		$other_product_ids=(isset($_POST['product']))?$_POST['product']:$pro_id;
		//$productId=explode(',',$_POST['product']);
		$productId=explode(',',$other_product_ids);
		$addOnAfterBasePkg = $this->dasmodel->addonpkg_after_basepkg($productId,$stck_id,$cust_id);
		//echo $this->db->last_query();
		if($addOnAfterBasePkg==1)
		{
			$data= 1;
		}
		else
		{
			$data= 0;
		}
		if(isset($_POST['stck_id'])){
			echo $data;
		}else{
			return $data;
		}
		
	}
   /* public function check_token()
	{
		$das_obj = new DasModel();
                 $product_id= $_POST['product_id'];
                 $usersname=$_POST['usersname'];
		$token_number = trim($_POST['token_number']);
                $reason = $_POST['reason'];
                if($reason==3){
                    $result = $das_obj->isTokenExist($product_id,$token_number,$usersname);
                }else{
                     $result = 1;
                }
		
		echo $result;
	}
        public function getTokens(){
            $product = $_POST['product'];
            $users_name = $_POST['users_name'];
            $token_number = isset($_POST['token_number'])?trim($_POST['token_number']):'';
            $result="";
           $cond="";
		   $employee_code="";
             $lco_query = $this->db->query("SELECT e.dist_subdist_lcocode ,e1.dist_subdist_lcocode parent_code ,
			e2.dist_subdist_lcocode parent_parent_code,e.employee_id,e1.employee_id parent_id ,e2.employee_id parent_parent_id from employee e
			LEFT JOIN employee e1 ON e1.employee_id=e.parent_id
			LEFT JOIN employee e2 ON e2.employee_id=e1.parent_id
			where e.employee_id=$users_name");
         
            if($lco_query && $lco_query->num_rows()>0){
                 if( $_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO =="CODE"){
					 if($lco_query->row()->dist_subdist_lcocode!=''){
						$employee_code.="'".$lco_query->row()->dist_subdist_lcocode."',";
					 }
					 if($lco_query->row()->parent_code!=''){
						 $employee_code.="'".$lco_query->row()->parent_code."',";
					}
					 if($lco_query->row()->parent_parent_code!=''){
						 $employee_code.="'".$lco_query->row()->parent_parent_code."',";
					}
                        
                   }else{
					 if($lco_query->row()->employee_id!=''){
						$employee_code.="'".$lco_query->row()->employee_id."',";
					 }
					 if($lco_query->row()->parent_id!=''){
						 $employee_code.="'".$lco_query->row()->parent_id."',";
					}
					 if($lco_query->row()->parent_parent_id!=''){
						 $employee_code.="'".$lco_query->row()->parent_parent_id."',";
					}
                       //$employee_code.=$lco_query->row()->employee_id.",".$lco_query->row()->parent_id.",".$lco_query->row()->parent_parent_id.",";
                   }
            }
            
		$employee_code=rtrim($employee_code,',');   
		   /* Old query
		   SELECT t.*
            FROM eb_stock_cas_services scs
            INNER JOIN eb_tokens t ON t.id = scs.deactivation_token
            WHERE t.act_type='D' and scs.product_id=$product and t.token NOT IN(SELECT dependent_token from eb_tokens where act_type='A' and dependent_token!='' ) GROUP BY t.id
		   */
		/**** 
                 * token: K16A041-6-1399961211
                 * SUBSTRING_INDEX( token, '-', 1 ) means get the first occurance value i.e K16A041
                 * SUBSTRING(token, LOCATE( '-', token )+1, LENGTH(SUBSTRING_INDEX( token, '-', 2 ))-LOCATE( '-',token))
                 * getting second occurance value i.e 6
                 * LOCATE( '-', token ) gives position for first occurance i.e  8.
                 * *****/   
          /*  if(isset($_SESSION['user_data']->users_type) && ($_SESSION['user_data']->users_type =='RESELLER' || $_SESSION['user_data']->users_type =='SUBDISTRIBUTOR' || $_SESSION['user_data']->users_type =='DISTRIBUTOR')){
                       $employee_id=$_SESSION['user_data']->employee_id;
                       $cond=" AND created_by= $employee_id ";
                   }
            $res_query = $this->db->query("SELECT * from eb_tokens
			WHERE act_type='D' And SUBSTRING_INDEX( token, '-', 1 ) IN ($employee_code) 
			And SUBSTRING(token, LOCATE( '-', token )+1, LENGTH(SUBSTRING_INDEX( token, '-', 2 ))-LOCATE( '-',token))=$product
			$cond ");
			//and token NOT IN(SELECT dependent_token from eb_tokens where act_type='A' and dependent_token!='' )
			//echo $this->db->last_query();
				if($res_query && $res_query->num_rows()>0){
                 $result.= "";
                 foreach($res_query->result() as $row){
                        $activated_services = 0;
						
						//sum(coalesce(effected_service_count,0))
						$dep_token = $row->token;
						$sql_qry = $this->db->query("SELECT coalesce(sum(effected_service_count),0) as activated_services FROM eb_tokens WHERE dependent_token = '$dep_token'");
						//echo $this->db->last_query();echo "<br/>";
						
						if($sql_qry && $sql_qry->num_rows()>0)
						{
							$activated_services = $sql_qry->row()->activated_services;
						}
						//echo "activated services:".$activated_services;echo "<br/>";
						//echo "activation from query:".$row->effected_service_count;echo "<br/>";
							if($token_number==$row->token){
							   $token_check= 'checked';

							}else{ 
								$token_check='';

							}
						
						if($activated_services<$row->effected_service_count)
						{
							$result.="<p class='chk_stb_type'><input type='checkbox' name='check_token' id='check_token' class='check_token' token_val='$row->token'   $token_check value='$row->id' ><span>$row->token</span></p>";
						}
						
                 }
                
                 
                 if($result!=""){
                     echo $result;
                 }else{
                      echo "<tr><td><h4>No Tokens.</h4></td></tr>";
                 }
                 
            }else{
                echo "<tr><td>No Tokens.</td></tr>";
            }
        }*/
	//function by sravani for trail pack activation restrictions
	public function getCountTrailPackActivation()
	{
		 $das_obj = new DasModel();
         $stock_id = $_POST['stock_id'];
         $result = $das_obj->dateDiffForTrailPack($stock_id);
         echo $result;
	}
// To list Mapped LCOs for intra lco BY VAMSI 17-june-2014
public function detailsOfIntralcoMapping()
{
$html ='';
$dealer_id=$_SESSION['user_data']->dealer_id;
$i=0;
$query=$this->db->query("SELECT mapping_id,created_on,business_name, dist_subdist_lcocode,CONCAT(first_name,'',last_name) as reseller_name
     from eb_intra_lco_mapping ilm 
INNER JOIN employee e  ON e.dealer_id=ilm.dealer_id
WHERE FIND_IN_SET(e.employee_id,reseller_ids) 
order by mapping_id");


if($query && $query->num_rows()>0)
{
        $html .="<table class='mygrid' border='0' cellpadding ='0' cellspacing ='1'>";
        $html .="<thead>";
        $html .="<tr align='left'>";
        $html .="<th>S/N</th>";
        $html .="<th align='center'>Business Name</th>";
        $html .="</tr>";
        $html .="</thead>";

        $prev_mapping_id = 0;
        $mapped_lcos = "";
        foreach($query->result() as $record)
        {
                $mapping_id = $record->mapping_id;
                $code = $record->dist_subdist_lcocode;

                 $business_name = trim($record->business_name);
                 $uname = trim($record->reseller_name);	
                 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
                                 $value=$code;
                                 $name = "(".$business_name.")";
                                 $title = $uname;
                 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
                                 $title = $code;
                                 $name = "(".$uname.")";

                                 $value= $business_name;
                  }
                  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
                                $title = $uname;
                                $name = "(".$code.")";


                                $value= $business_name;
                  }	
                  else {
                                $title = 	$users->dist_subdist_lcocode;
                                $name = "(".$uname.")";
                                $value= $business_name;
                  }

                        if($prev_mapping_id != $mapping_id){

                                if($prev_mapping_id != 0){
                                $html = rtrim($html, ',');
                                $html .="</td>";
                                $html .="</tr>";
                                }

                                $html .="<tr align='left'>";
                                $html .="<td >".++$i;"</td>";
                                $html .="<td >";

                                $prev_mapping_id = $mapping_id;	

                        }

                        if($prev_mapping_id == $mapping_id){
                        $var = $value.$name;

                        $html .= "<span title='".$title."'>".$value.$name." </span>, ";

                        }


        }

        $html .="</td>";
        $html .="</tr>";			
        $html .="</table>";

}
else
{
 echo 'No records...';


}
        echo $html;
}
	//function by sravani to allow duplicate cas product id 
	public function CheckISCasProductIdExist()
	{
		$cas_prod_id = trim($_POST['cas_prod_id']);
		$product_id = trim($_POST['product_id']);
		$hdnProdId = trim($_POST['hdnProdId']);
		$backend_setup_id = trim($_POST['backend_setup_id']);
		$cond='';
		
		if($product_id!=0 || $product_id!='')
		{
			$cond = " AND product_id!=$product_id";
		}
		$str_query = $this->db->query("SELECT ep.cas_product_id FROM eb_products ep 
									   WHERE ep.cas_product_id = '$cas_prod_id' AND ep.backend_setup_id=$backend_setup_id $cond");
		
		if($str_query && $str_query->num_rows()>0)
		{
			if($cas_prod_id!=$hdnProdId)
			{
				echo 1;
			}
			else
			{
				echo 0;
			}
		}
		else
		{
			echo 0;
		}
		
	}
        //function to check cas service index id for NAGRA CAS by GOPI
	public function CheckISCasServiceIndexIdExist()
	{
		$cas_prod_id = trim($_POST['cas_prod_id']);
                $cas_service_index_id = trim($_POST['cas_service_index_id']);
		$product_id = trim($_POST['product_id']);
		$hdnProdId = trim($_POST['hdnProdId']);
                $hdnServiceIndexId = trim($_POST['hdnServiceIndexId']);
                
		$cond='';
		
		if($cas_service_index_id!=0 || $cas_service_index_id!='')
		{
			$cond = " AND cas_product_id='$cas_prod_id'";
		}
		$str_query = $this->db->query("SELECT cas_product_id FROM eb_products WHERE cas_service_index_id = '$cas_service_index_id' $cond");
                    //echo $this->db->last_query();
		if($str_query && $str_query->num_rows()>0)
		{
                        if($hdnServiceIndexId!=$cas_service_index_id){
                            echo 1;
                        }else if($hdnProdId!=$cas_prod_id){
                            echo 1;
                        }else{
                            echo 0;
                        }
                        
			
		}
		else
		{
			echo 0;
		}
		
	}
	//BY SRAVANI
	public function getEmpGroup()
	{
		$html ='';
		$reseller_id=$_POST['reseller_id'];
		$group_id=$_POST['group_id'];
		$sql_str = $this->db->query("SELECT g.group_name,g.group_id FROM employee_group eg INNER JOIN groups g ON g.group_id = eg.group_id WHERE eg.employee_id = $reseller_id AND eg.group_id!=$group_id");
		if($sql_str && $sql_str->num_rows()>0)
		{
			$html .="<table width='95%' cellpadding='10' cellspacing='10' border='0'>";
			$html .="<tr align='left'>";
			$html .="<td style='color:#315C7C;font-size:14px;'><b>Group:</b></td>";
			$html .="<td><select name='search_grp' id='search_grp'>
			<option value=-1>--Select--<option>
			";			
			
			
			foreach($sql_str->result() as $grp)
			{
				$html .='<option value="'.$grp->group_id.'">'.$grp->group_name.'</option>';
			}
			$html .="</table>";
			$html .="</tr>";
		}
		echo $html;
	}
	//function by sravani for edit customer group
	public function customerGroupEdit()
	{
		$customer_id=$_POST['customer_id'];
		$group_id=$_POST['group_id'];
		 
		$sql_str = $this->db->query("DELETE FROM customer_group WHERE customer_id = $customer_id ");
		 
		$data = array(
		  'group_id'=>$group_id,
		  'dealer_id'=>$_SESSION['user_data']->dealer_id,
		  'customer_id'=>$customer_id,
		  'created_by'=>$_SESSION['user_data']->employee_id,
		  'created_on'=>date('Y-m-d H:i:s')
		);   
		$sql_str = $this->db->insert('customer_group',$data);
		if($this->db->affected_rows()>0)
		{
			echo  1;
		}	
		else
		{
			return 0;
		}
	}
	//function by sravani for getting reseller employee group
	public function getResellerEmpGrp()
	{
		$html ='';
		$reseller_id=$_POST['employee_id'];
		$group_id=$_POST['group_id'];
		$query = $this->db->query("SELECT e.employee_id,concat(e.first_name,'',e.last_name) as reseller_employee 
		FROM employee e 
		INNER JOIN employee_group eg ON eg.employee_id = e.employee_id
		INNER JOIN groups g ON g.group_id = eg.group_id AND g.parent_id = $reseller_id
		WHERE e.users_type = 'EMPLOYEE' AND e.employee_parent_type='RESELLER' AND e.employee_parent_id = $reseller_id AND eg.group_id = $group_id ");
		if($query && $query->num_rows()>0){
			$i=1;
			$html .="<table width='95%' cellpadding='10' cellspacing='10' border='0'>";
			// $html .="<tr align='left'>";
			// $html .="<th><b>S/N</b></th>";
			// $html .="<th><b>Employee</b></th>";
			// $html .="</tr>";
			foreach($query->result() as $record){
				$html .="<tr align='center'>";
				$html .="<td>".$i++."</td>";
				$html .="<td style='background-color:white;'>".$record->reseller_employee."</td>";
				$html .="</tr>";
			}			
			$html .="</table>";
			
		}
		else
		{
			$html = "No Records.";
		}
		echo $html;
	}
	//function by sravani for groups selected
	public function getSelectedGroup()
	{
		$employee_id = isset($_POST['employee_id'])?$_POST['employee_id']:0;
		$is_location_id = isset($_POST['is_location_id'])?$_POST['is_location_id']:'0';
		//echo "<pre>",print_r($_POST);
		if($is_location_id>0)//if location id is exists need to get reseller id to that location
		{
			$sql_str = $this->db->query("SELECT reseller_id FROM eb_stock_location WHERE location_id = $employee_id");
			if($sql_str && $sql_str->num_rows()>0)
			{
				$employee_id = $sql_str->row()->reseller_id;
			}
		}
		$val='';
		$group_id = isset($_POST['group_id'])?$_POST['group_id']:'0';
		$val .=  "<option value='-1'>Select Group</option>";
		
		if($employee_id>0){
			$lco_groups = $this->CustomersModel->getEmpGroup($employee_id,0,$status=1);
			
             //get group model function added by pardhu
            if ($_SESSION['user_data']->employee_parent_type == 'RESELLER') {
                $lco_groups = $this->CustomersModel->get_groups();
            }
		if(count($lco_groups)>0)
		{
			foreach($lco_groups as $lco_group)
			{
				$val .= "<option value=".$lco_group->group_id;			
				if($group_id!='0' && $group_id == $lco_group->group_id) 
				$val .= " selected";
				$val .= ">";
				$val .= $lco_group->group_name."</option>";
			}
		}
			}
		echo $val;	
	}
	
	public function getMSOShare()
	{
		$service_id = $_POST['customer_service_id'];
		$quantity = $_POST['quantity'];
        $mso_share = 0;
		$sql_str = $this->db->query("select mso_share, quantity from acc_billing where billing_id=(select max(billing_id) from acc_billing where customer_service_id=$service_id)");
		if($sql_str && $sql_str->num_rows()>0)
		{
			$mso_share = $sql_str->row()->mso_share;
			$bill_quantity = $sql_str->row()->quantity;
			$mso_share = $mso_share/$bill_quantity;
			$mso_share = round($mso_share,2);
		}
		if($mso_share>0)
		{
			$mso_share = $mso_share*$quantity;
		}
		echo $mso_share;
	}

	//function for getting MSO Share in service extension page by Swaroop ON Apr 4 2019
	public function getProductMSOShare()
	{
		$this->load->model('Extend_service_model');
		$productsmodel = new productsmodel();
		$CustomersModel = new CustomersModel();
		$das_object = new dasmodel();
		$Extend_service_model = new Extend_service_model();

		$login_employee_id = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $dealer_id = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;

		$qty = ($this->input->post('qty'))?$this->input->post('qty'):1;
		$productIds = ($this->input->post('pids'))?explode(',',$this->input->post('pids')):array();
		$customerIds = ($this->input->post('cids'))?explode(',',$this->input->post('cids')):array();
		$stockIds = ($this->input->post('stock_ids'))?explode(',',$this->input->post('stock_ids')):array();
		$check_bill_exist = $this->input->post('check_bill_exist');
		$addon_after_base_pack_int =isset($_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK)?$_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK:0;
		
		$product_details_array = array();
		$calculated_end_dates = array();
		$product_mso_shares = array();
		$mso_share = 0;
		$start_date = date('Y-m-d');

		if(count($productIds)>0){
			$i = 0;  
			foreach($productIds as $product)
			{
			  if (!in_array($product, array_keys($product_details_array))) { //avoiding repeated checks   
			    $product_details = $productsmodel->getservice($product,$dealer_id,$deposit_flag = 1);
			    if(count($product_details)>0){
			    	$pricing_structure_type = isset($product_details[0]->pricing_structure_type)?$product_details[0]->pricing_structure_type:0;
				    $validity_days = isset($product_details[0]->validity_days)?$product_details[0]->validity_days:1;
				    $validity_days_type_id = isset($product_details[0]->validity_days_type_id)?$product_details[0]->validity_days_type_id:1;
				    $is_base_package = isset($product_details[0]->is_base_package)?$product_details[0]->is_base_package:1;
				    $product_details_array[$product] = array('pricing_structure_type'=>$pricing_structure_type,'validity_days'=>$validity_days,'validity_days_type_id'=>$validity_days_type_id,'is_base_package'=>$is_base_package);
			    }else{
			    	continue;
			    }			    
			  }else{
			    $pricing_structure_type = isset($product_details_array[$product]['pricing_structure_type'])?$product_details_array[$product]['pricing_structure_type']:0;
			    $validity_days = isset($product_details_array[$product]['validity_days'])?$product_details_array[$product]['validity_days']:1;
			    $validity_days_type_id = isset($product_details_array[$product]['validity_days_type_id'])?$product_details_array[$product]['validity_days_type_id']:1;
			    $is_base_package = isset($product_details_array[$product]['is_base_package'])?$product_details_array[$product]['is_base_package']:0;
			  }

			  if (!in_array($product, array_keys($calculated_end_dates))) { //avoiding repeated checks   
			    $end_date = $CustomersModel->quantity_enddate($qty,$product,$pricing_structure_type,$validity_days,$validity_days_type_id,$stockIds[$i],$start_date, $cust_id = 0,$dealer_id=0,$fromCustomerPortal=0,$customerIds[$i]);
			    $calculated_end_dates[$product] = $end_date;//push to array
			  }else{
			    $end_date = $calculated_end_dates[$product];
			  }

			  if(1==$addon_after_base_pack_int && $is_base_package==0){
                   $end_date=$productsmodel->compare_base_addondate($stockIds[$i],$end_date);
                   $calculated_end_dates[$product] = $end_date;
              }
              //echo "start_date==".$start_date."end_dateaaa===".$end_date."mso_share====".$mso_share."<br>";

			  if (!in_array($product, array_keys($product_mso_shares))) { //avoiding repeated checks
			    $basePriceAmounts = $das_object->getBasePriceAmount(array($product),$login_employee_id,array($qty),array($stockIds[$i]),array($start_date),array($end_date),$customerIds[$i],$get_bill_amount=1,$csid=0,$dealer_id, $get_extra_params = array(),$discount_type=0,$discount_value=0,$serviceExtWithLcoDeposit=0,$customer_bill_type=0,$fromCustomerPortal=0,$chk_ncf_deduct_deposit=0,$present_ncf_mso_share=0,$use_lco_deposits_lov=0,$checkaccesslcodeposits=0,$check_bill_exist);
			    $msoShare = isset($basePriceAmounts['mso_share'])?round($basePriceAmounts['mso_share'],2):0;
			    $product_mso_shares[$product] = $msoShare;//push to array
			  }else{
			    $msoShare = $product_mso_shares[$product];
			  }

			  $mso_share += isset($msoShare)?round($msoShare,2):0;
			  $i++;
			}
		}
		echo $mso_share;
	}
		
	//function by SRAVANI for getting lco Groups
	public function getLCOGroups()
	{
		$employee_id = $_POST['employee_id'];
		$get_select = isset($_POST['get_select'])?$_POST['get_select']:'0';
		$is_location_id = isset($_POST['is_location_id'])?$_POST['is_location_id']:'0';
		if($is_location_id>0)//if location id is exists need to get reseller id to that location
		{
			$sql_str = $this->db->query("SELECT reseller_id FROM eb_stock_location WHERE location_id = $employee_id");
			if($sql_str && $sql_str->num_rows()>0)
			{
				$employee_id = $sql_str->row()->reseller_id;
			}
		}
		$val='';
		//$session_val = ($_POST['session_val'])?$_POST['session_val']:'';
		//echo $session_val;echo "<br/>";		
		if($employee_id>0)
		{
			$lco_groups = $this->CustomersModel->getEmpGroup($employee_id,0,$status=1);//print_r($lco_groups);exit;
			if(count($lco_groups)>0)
			{
				// commented group not to be selected by default
				// if(count($lco_groups)==1 && $get_select>0)
				// {
				// 	$val .="<option  value='".$lco_groups[0]->group_id."' SELECTED='selected' >".$lco_groups[0]->group_name." </option>";
				// }
				// else
				// {
				$val='<option value="-1">Select Group</option>';
				foreach($lco_groups as $lco_group)
				{
			//$val .= '<option value="'.$lco_group->group_id.'"'.(($session_val)?$session_val==$lco_group->group_id?' selected="SELECTED"':'':'').'>'.$lco_group->group_name.'</option>';
					
					$val .="<option  value='".$lco_group->group_id."'  >".$lco_group->group_name." </option>";
				}
				// }
			}
			
		}
		echo $val;
	}
	public function check_folio_number()
	{
		$folioNumber = $_POST['folioNumber'];
		$employee_id = ($_POST['employee_id'])?$_POST['employee_id']:0;
		$did = $_SESSION['user_data']->dealer_id;
		$result = $this->EmployeeModel->check_folio_number($folioNumber,$did,$employee_id);
		echo $result;
		
	}
	//function by SRAVANI for checking account number duplication
	public function check_account_number()
	{
		$account_no= trim($_POST['account_no']);
		$customer_account_number = $this->CustomersModel->customNumberExists($account_no);
		echo $customer_account_number;
	}
	//function by SRAVANI for getting packages in bulkoperations
	public function getLCOPackages()
	{
		//$val = '';
		$bulk_obj = new bulkoperationmodel();
		$is_user_blocked = $_POST['is_user_blcoked'];
		$status = $_POST['mapped_products'];
		$session_val = isset($_POST['session_val'])?$_POST['session_val']:'0';
		$pacagelist = $bulk_obj->get_all_product($is_user_blocked,$status);
		$val='<option value="-1">Select</option>';
		if(count($pacagelist)>0){
		foreach($pacagelist as $row){
			$val .= '<option value="'.$row->product_id.'"'.(($session_val == $row->product_id)?' selected="SELECTED"':'').'>'.$row->pname.'('.$row->pricing_type.') ('.$row->cas_server_type.')</option>';
			}
		}
		echo $val;
		//return $val;
	}

        //get temporary deactivated services
        public function getTemporaryDeactivatedServices(){
            $customer_id = $_POST['customer_id'];
            $reason_id = 8;//default temp deactivated services

            $service_activation = isset($_POST['service_activation'])?$_POST['service_activation']:0;
            if($service_activation==1){//unpaid reason deactivated services
            	$reason_id = 2;
            }

            if($service_activation==2){//expired services reason deactivated services
            	$reason_id = 3;
            }

            $packages='';
            $dealer_id = $_SESSION['user_data']->dealer_id; 
            $result_array = $this->CustomersModel->get_deact_customer_services($customer_id,$reason_id);

            if($service_activation==0){//default temp deactivated services
            	//get records whose product status is 0 and update reason to 9 in eb_customer_service table
	            $deactive_result_array = $this->CustomersModel->get_deact_customer_services($customer_id,$reason_id,$customer_service_id=array(),$status=0);
	            if(count($deactive_result_array)>0){
	            	$customer_service_ids = '';
	            	foreach($deactive_result_array as $deactive){
	            		$customer_service_ids .= $deactive->customer_service_id;
	            	}
	            	$customer_service_ids = rtrim($customer_service_ids,',');
	            	if($customer_service_ids!=''){
	            		$this->CustomersModel->update_customer_service_reason($customer_id,$customer_service_ids);
	            	}
	            }	
            }

            if(count($result_array)){
                $serial_number='';
                $stock_id=0;
                foreach($result_array as $row){
                 $stock = $row->stock_id;
                    // echo  $stock = $stock_id.'------11----------<br/>';
                    if($stock!=$stock_id){
                        $serial_number = 'STB :'.$row->serial_number.' Service(s) ';
                    }else{
                        $serial_number='';
                        
                    }
                    $stock_id = $row->stock_id;
                    $packages.= $serial_number.$row->pname.',';
                }
                $packages = rtrim($packages,',');
            }
            echo $packages;
        }
       
	//function by SRAVANI for getting parents group
	public function getParentGroups()
	{
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$employee_id = isset($_POST['employee_id'])?$_POST['employee_id']:'0';
		$session_val = isset($_POST['session_val'])?$_POST['session_val']:'0';
		if($employee_id>0)
		{
			$parent_groups = $this->CustomersModel->getEmpGroup($employee_id,0,$static=1);
		}
		else
		{
			$parent_groups = $this->CustomersModel->listParentGroups($dealer_id);
		}
		if($employee_id==0){ //by ravula for fp
			$val='<option value="-1">All</option>';
		}
		if(count($parent_groups)>0){
		foreach($parent_groups as $row){
			$val .= '<option value="'.$row->group_id.'"'.(($session_val == $row->group_id)?' selected="SELECTED"':'').'>'.$row->group_name.'</option>';
			}
		}
		echo $val;
	}

	//get black list options by SRAVANI
	public function getBlackListOptions()
	{
		$html="";
		$serial_number = isset($_POST['serial_number'])?$_POST['serial_number']:'0';
		$vc_number = isset($_POST['vc_number'])?$_POST['vc_number']:'0';
		$stock_id = isset($_POST['stock_id'])?$_POST['stock_id']:'0';
		$mac = isset($_POST['mac'])?$_POST['mac']:'0';
		$box_number = isset($_POST['box_number'])?$_POST['box_number']:'0';
		$backend_setup_id = isset($_POST['backend_setup_id'])?$_POST['backend_setup_id']:'0';
		$get_server_name = $this->EmployeeModel->get_server_name($backend_setup_id);
		if(count($get_server_name)>0)
		{
			$server_name = $get_server_name->display_name;
		}
		else
		{
			$server_name = '';
		}
		$mac = isset($_POST['mac'])?$_POST['mac']:'0';
		$cas_term = isset($_SESSION['cas_term']['for_serialno'][0]) ? $_SESSION['cas_term']['for_serialno'][0] :'Serial Number';
		$html.="<table  cellpadding='3' cellspacing='0'>
				<tr>                                      
                      <th colspan='4' align='left'> ".$cas_term." :".$serial_number.", MAC/VC Number :". $vc_number.", Server :".$server_name."</th>  
                      </tr>
				<tr>";
				if($serial_number!='')
				{
					$html.="<td><input type='radio' name='stb' id='stb' value='1' serial_number='".$serial_number."' vc_number='".$vc_number."' stock_id='".$stock_id."' backend_setup_id='".$backend_setup_id."' mac='".$mac."' box_number='".$box_number."'  />
					Only STB</td>
					";
				}
				if($vc_number!='')
				{
					$html.="<td><input type='radio' name='stb' id='stb' value='2' serial_number='".$serial_number."' vc_number='".$vc_number."' stock_id='".$stock_id."' backend_setup_id='".$backend_setup_id."'  mac='".$mac."' box_number='".$box_number."' />
					Only VC</td>
					";
				}
				if($serial_number!='' && $vc_number!='')
				{
					$html.="<td><input type='radio' name='stb' id='stb' value='3' serial_number='".$serial_number."' vc_number='".$vc_number."' stock_id='".$stock_id."' backend_setup_id='".$backend_setup_id."'  mac='".$mac."' box_number='".$box_number."' />
					Both</td>";
				}	
					
				$html.="</tr>
				</table>";
		echo $html;
	}

	public function check_parent_groups()
	{
		$did = $_SESSION['user_data']->dealer_id;
		$cond=''; $join = "";
		$val = '';

		$parent_groups = $this->CustomersModel->listParentGroups($did);
		
		$val .= "<select name='group' id='group'>";
		$val .= "<option value='all'>All STBs</option>";
		$val .="<option value='active_stbs'>Active STBs</option>";
        $val .="<option value='deactive_stbs'>Deactive STBs</option>";
		$val .= "<option value='unassigned'>All Unassigned STBs</option>";
		$val .= "<option value='all_customers'>All Customers</option>";
		$val .= "<option value='individual'>Individual</option>";		
		 if(isset($_SESSION['dealer_setting']->USE_CUSTOM_MESSAGING) && $_SESSION['dealer_setting']->USE_CUSTOM_MESSAGING==1){ 
            $val .= "<option value='custom'>Custom</option>";
           }
		$val .= "<optgroup label='Parent Groups'>";
		if(count($parent_groups)>0)
		{	
			foreach($parent_groups as $row1)
			{
				$val .="<option value='".$row1->group_id."'>".$row1->group_name."</option>";
			}	
		}
		
		$val .= "</select>";
		echo $val;
		
	}
	//function by sravani for bulk operations 
	  //function by sravani for bulk operations 
    public function get_packages() {
        $is_user_blocked = isset($_POST['is_user_blocked'])?$_POST['is_user_blocked']:0;
        $product_session = isset($_POST['product_session'])?$_POST['product_session']:0;
        $act_type = isset($_POST['act'])?$_POST['act']:0;
        $server = isset($_POST['server']) ? $_POST['server'] : 0;
        $pricing_type = isset($_POST['pricingType']) ? $_POST['pricingType'] : 0;
        $frombulkoprns = isset($_POST['frombulkoprns']) ? $_POST['frombulkoprns'] : 0;
        $package_type = isset($_POST['package_type']) ? $_POST['package_type'] : 0;
        $product_list = $this->blkopr->get_all_product($is_user_blocked, $status = 1, $pricing_type, $server,$package_type);
        
        if($frombulkoprns==1) {
            $val='';
        }else{
            if ($act_type == 1) {
                $val = '<option value="0">Select</option>';
            } else if ($act_type == 2) {
                $val = '<option value="0" selected >All</option>';
            } else if($act_type == 3){
                $val = '<option value="0">--Select--</option>';
            }
        }
        
        if (count($product_list) > 0) {
            foreach ($product_list as $row) {

                $val .= '<option pricingtype="'.$row->pricing_structure_type.'" value="' . $row->product_id . '"' . (($product_session == $row->product_id) ? ' selected="SELECTED"' : '') . '>' . $row->pname . '(' . $row->pricing_type . ')(' . $row->display_name . ')' . '</option>';
            }
        }
        echo $val;
    }
	public function get_deact_packages()
	{
		$val='';
		$is_user_blocked = $_POST['is_user_blocked'];
		$server = isset($_POST['server'])?$_POST['server']:0;
                $frombulkoprns = isset($_POST['frombulkoprns']) ? $_POST['frombulkoprns'] : 0;
		$deact_product_list = isset($_SESSION['deact_product_id'])?$_SESSION['deact_product_id']:array();
		$product_list = $this->blkopr->get_all_product($is_user_blocked,$status=1,$pricing_type=0,$server);
		if(count($deact_product_list)==0 && $frombulkoprns!=1){
                    $val='<option value="0">All</option>';
		}
		if(count($product_list)>0){
				foreach($product_list as $row){
					if (in_array($row->product_id, $deact_product_list))
					{
						$val .= '<option value="'.$row->product_id.'" >'.$row->pname.'('.$row->pricing_type.')('.$row->display_name.')'.'</option>';
					}
				}
		}
		echo $val;
	}
	//function by SRAVANI for one time service extension blocked condition in lco login
	public function checkProductBlocking()
	{
		$product_id = $_POST['product_id'];
		$stock_id = $_POST['stock_id'];
		//$reseller_id = $_SESSION['user_data']->employee_id;
		$reseller_id = $this->dasmodel->getEmployeeId($stock_id);
		$setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO))?$_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO:0;
		$users_type = $_SESSION['user_data']->users_type;
		$check_block_cond = $this->dasmodel->isValidProduct($setting_value,$users_type,array($product_id),$reseller_id);
		//$check_block_cond = $this->dasmodel->checkIsProductBlocked(array($product_id),$reseller_id);
		//echo $this->db->last_query();
		echo $check_block_cond;
	}
   //function by Gowtham OSD Templates 
	public function get_osd_templates()
	{
		$html='';
		$group = $_POST['group'];
		$templates=$this->dasmodel->getMessageTemplates($group);
		if(count($templates)==0){
			$templates=$this->dasmodel->getMessageTemplates('default');
		}
		if(count($templates)>0){
			if(count($templates)==1){
				$html .='1**';
				$html .= $templates[0]->title."*".$templates[0]->message;
			}else{
				$html .='2**<table>';
				foreach($templates as $template){ 
					$html .="<tr>
						<td><input type='radio' id='$template->osd_template_id' name='tmplate_id' title='$template->title' message='$template->message' value='$template->osd_template_id' ></td>
						<th>$template->title</th>
					</tr>";
					$html .="<tr>
						<td></td>
						<td>$template->message</td>
					</tr>";
				}
				$html .='</table>';
			}
		}else{
			$html .='0**';
		}
		echo $html;
	}
	public function amount_to_words()
	{
		$library = new CommonFunctions();
		$deposite_amount = $_POST['deposit_amount'];
		$amount_in_words = $library->convert_number_to_words($deposite_amount);
		echo $amount_in_words;
	}
	public function checkPAFDuplication()
	{
		$is_duplicate=0;
		$paf = (isset($_POST['paf']))?trim($_POST['paf']):'';
		if($paf!='')
		{
			$trim_paf = preg_replace('/\s+/', '', $paf);//remove white spaces from group name				
			$paf_number= strtolower($trim_paf);
			$is_duplicate = $this->Paf_model->checkISDuplicatePAF($paf_number);
			//echo $this->db->last_query();
		}
		echo $is_duplicate;
	}
	public function getUserDetails(){
            $this->load->model('generic_users_model');
		$val='';
		$dealer_id = $_SESSION['user_data']->dealer_id;
		$code= $_POST['code'];
		$details = $this->generic_users_model->getUserDetails($code,$dealer_id);
		if(count($details)>0){
			$user_type = $details->users_type;
			$employee_id = $details->employee_id;
			$employee_location_id = $details->location_id;
			$val .="$employee_location_id";
			$val .='**';
			$val .="$user_type";
			}
			echo $val;
	}
                //Function for getPincodeValidation by ptatap         
        	/*public function getPincodeValidation(){
                     $country=($_POST['country'])?$_POST['country']:0;
                     $sql_str=$this->db->query("select country_code,pincode_length from eb_country_validations where country_code='$country'");
                        $res = $sql_str->row();
			if($res){
				$code=$res->pincode_length;   
				echo $code;
                                }else{
				echo 6;
			}
		}*/
                 //Function for checking duplicate username by ptatap  
                   public function checkUnameAvaliability()
	           {
                   $cond='';
                   $customerId=(isset($_POST['custId']))?trim($_POST['custId']):0;
		   $uName=(isset($_POST['username']))?$_POST['username']:'';
                   $rName_len=strlen(str_replace(' ', '', $uName));
                   $uName_len=strlen($uName);
                   if($customerId>0){
                        $cond = "AND customer_id<>$customerId";
                   }
                   $did = $_SESSION['user_data']->dealer_id;
                   $checkDuplicate="SELECT count(customer_id) as cnt FROM `customer` WHERE `user_name`='$uName' and `dealer_id`=$did $cond";
		   $query= $this->db->query($checkDuplicate);
                   $result = $query->row();
                   if($result->cnt==0 && $uName_len==$rName_len)
		   {
			echo 1;
		   }
                   else
                    {
                            echo 0;
                    }
	      }
              //Function for checking duplicate username by Swamy  
                   public function checkLCOAvaliability()
	           {
				  
				   $customerId=(isset($_POST['custId']))?trim($_POST['custId']):0;
				   $lco=(isset($_POST['lco']))?trim(str_replace(' ','',$_POST['lco'])):'';
                                   $resellerId=(isset($_POST['reseller']))?trim($_POST['reseller']):0; //get reseller id added by Durga
                                   $dealerId = (isset($_POST['dealerId']))?trim($_POST['dealerId']):0; //get dealer id added by Durga
			$res=$this->CustomersModel->checkLCOCustId($lco,$customerId,$dealerId,$resellerId);
			if($res)
				echo 1;
			else
				echo 0;
		
	      }
        //function by SRAVANI to get previous sms info
		public function sendSMSEmail()
		{
			 $employee_id=(isset($_POST['emp_id']))?trim($_POST['emp_id']):0;
			 if($employee_id>0)
			 {
				$query = $this->db->query("SELECT max(sms_sent_date) as senddate FROM eb_deposit_reminders WHERE employee_id=$employee_id");
				if($query && $query->num_rows()>0)
				{
					$date = $query->row()->senddate;
					if($date!='' && $date!='NULL')
					{
						$last_date = date("d-m-Y h:i:s", strtotime($date));
						echo $last_date;
					}
					else
					{
						echo '';
					}
				}
				else
				{
					echo '';
				}
			 }
			 else
			 { 
				echo '';
			 }

		}
		// function for machine exist checking 
		public function machineNumberCheck()
		{
			$machine_id=0;
			$machine_no=$this->input->post('machine_no')?$this->input->post('machine_no'):0;

					$query=$this->db->query("select machine_no from machine where machine_no ='$machine_no' ");
					if($query->num_rows()>0)
					{
						echo  1;
					}
					else
					{
						echo  0;
					}
		}
		//for displaying error messages
		public function isValidBox()
		{
			 $serial_num=(isset($_POST['serialnumber']))?trim($_POST['serialnumber']):'';
			  $vc_num=(isset($_POST['mac_vc_number']))?trim($_POST['mac_vc_number']):'';
			$is_success = $this->inventorymodel->isValidBox($serial_num,$vc_num);
			//echo $this->db->last_query();
			echo $is_success;
		}
		public function isSTBBlocked()
		{
			$is_blocked=0;
			 $stok_id=(isset($_POST['stock_id']))?trim($_POST['stock_id']):0;
			 if($stok_id>0)
			 {
				$is_blocked = $this->inventorymodel->isSTBBlocked($stok_id);
			 }
			echo  $is_blocked;
		}
                
                //Function for checking duplicate Head by ptatap  
                 public function checkHead()
	         {
                   $cond='';
                   $headId=(isset($_POST['hId']))?$_POST['hId']:0;
                   $hName=(isset($_POST['hName']))?$_POST['hName']:'';
                   $hName = str_replace(" ","",$hName);
                   if($headId>0){
                       $cond .= "AND head_id<>$headId";
                   }
                   $checkDuplicate="SELECT count(head_id) as head FROM `eb_heads` WHERE REPLACE(`head_name`, ' ', '')='$hName' $cond";
		   $query= $this->db->query($checkDuplicate);
                   $result = $query->row();
                  
                   if($result->head==0)
		   {
                       echo 1;
		   }
                   else
                   {
                            echo 0;
                   }
	         }
              
               //Function for getHeadAmount by ptatap         
        	public function getHeadAmount(){
                     $head=($_POST['head'])?$_POST['head']:0;
                     $empId=(isset($_POST['emp']))?trim($_POST['emp']):0; 
                     $sql_str=$this->db->query("select employee_id,head_id,amount from eb_head_deposits where head_id='$head' and employee_id='$empId'");
                     $result = $sql_str->row();
                     if(count($result)>0){
                        $depositAmt=$result->amount;   
                        echo $depositAmt;
                        }else{
                        echo '0.00';
                    }
		}
                
                //check unique server name by pratap on 7/13/2015
                public function checkUnqDisplayName(){
                 $uniqueName=0;
                 $backend_setup_id  =(isset($_POST['bsid']))?$_POST['bsid']:0;
                 $setup_id          =(isset($_POST['setupid']))?$_POST['setupid']:0;
                 $displayName       =(isset($_POST['dname']))?$_POST['dname']:'';
                 if($displayName!='' && $setup_id>0){
                  $uniqueName= $this->change_pass_model->checkUniqueDisplayName($displayName,$setup_id,$backend_setup_id);
                 }
                echo $uniqueName;
             }
		//Function to check the unique and with in the range of receipt book of a LCO.	 
		public function checkreceiptnumber(){
			$error_msg='';
			$valid=0;
			$customer_id=$_POST['custid'];
			$receipt_no=trim($_POST['receipt_no']);
			//Code to check the uniqueness of the receipt number for the payment of the customer.
			$range=$this->receiptmodel->checkReceiptRange($receipt_no,$customer_id);
			if($range==0){
				$error_msg="Receipt Number <b>".$receipt_no."</b> doesnot belongs to the LCO range.<br>";	
				$valid=1;
			}
			//code to check whether the receipt number is with in the range of LCO or not.
			if($valid==0){
				$uniq=$this->receiptmodel->checkUniqueReceitp($receipt_no);					
				if($uniq==1){				
					$error_msg="Receipt Number <b>".$receipt_no."</b> already used for the payment.<br>";
				}
			}
			echo $error_msg;
		}
		
		//Function for consolidated validations of package in CAF [Nagaraj].
		public function checkPackageValidations(){
			$error_code=0;$msg='';
			$stock_id=$_POST['stock_id'];
			$pro_id=$_POST['allProductIds'];
			$besid=$_POST['besid'];
			$product_id=$_POST['product_id'];
			$chk_len=$_POST['chk_length'];
			
			//check whether it is base package or not.
			if($chk_len>1){
				$basePack=$this->checkIsBasePackage($product_id,$pro_id);
				if($basePack>0){
					echo $error_code=5; exit();
				}
			}
			//check the server types.
			$server_type=$this->chkServerTypes($product_id,$besid);
			if($server_type==1){
			}
			if($server_type==2){
				echo $error_code=6; exit();
			}
			//check whether it is alacarte package or not.
			$is_ala_carte=$this->checkIsAlaCarte($product_id);
			 if($is_ala_carte==1){
			 	$allow_duplicate_channels = (isset($_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS)) ? $_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS : 0;
				if(isset($allow_duplicate_channels) && $allow_duplicate_channels==0) {

				 	if(count($pro_id)>1){
				 		// check whether product channel exists or not.
				 		$isChannelExists=$this->isProductChannelExist($stock_id,$pro_id,$product_id);						
				 		if($isChannelExists>0){
				 			echo $error_code=1; exit();
				 		}
				 	}
			 	}									
				
			 }
			 else{
				//check whether the channel exists or not.
				$checkchannel=$this->checkChannel($stock_id,$pro_id,$product_id);
				if($checkchannel>0){
					echo $error_code=4; exit();
				}
			}
			//check whether it is one time package or not.
			$alacarte_onetime=$this->checkIsOnetime($stock_id,$product_id);
			$value=explode('XXX',$alacarte_onetime);
			$msg=$value[2];
			if($value[1]!=''){
				if($value[1]==1){
					$error_code=2;
				}else{
					$error_code=3;
				}
				echo $error_code.'@#'.$msg; exit();
			}
			
		}
		//Function for consolidated validations of package in providing services for existing customer [Nagaraj].
		public function checkPackageValidationsForCustomer(){ 
			$error_code=0;$msg='';
			$stock_id=$_POST['stock_id'];
			$pro_id=$_POST['allProductIds'];
			$product_id=$_POST['product_id'];
			$cust_id=$_POST['cust_id'];
			$check=$_POST['check'];
			$chk_len=$_POST['chk_length'];
			$dealer=$_SESSION['user_data']->dealer_id;
			//check whether it is base package or not.
			if($chk_len>1){
				$basePack=$this->checkIsBasePackage($product_id,$pro_id);
				if($basePack>0){
					echo $error_code=8; exit();
				}
			}
			$basePackEdit=$this->checkIsBasePackageForEdit($product_id,$stock_id);
			if($basePackEdit>0){
				echo $error_code=9; exit();
			}
			$addon=$this->addnAfterBasePkg($pro_id,$stock_id,$cust_id);
			if($addon==0){
				echo $error_code=10; exit();
			}
			
			//check whether the user is eligible fo activation or not.
			$userEligible=$this->checkIsEligibleUserToActivate($product_id,$stock_id,$cust_id);
			if($userEligible!=''){						
				$error_code=1;
				echo $error_code.'@#'.$userEligible;	exit();
			}					
			//check whether it is alacarte package or not.
			$is_ala_carte=$this->checkIsAlaCarte($product_id);
			//echo $check.'  '.$is_ala_carte.'  '.$pro_id;//die;
			if($check==1){
				//if($is_ala_carte==1){						
					// if($pro_id !=''){
					// 	$check_prod_exist_chanel_array=array();$check_alacarte_exist_array=array();$result=array();
					// 	// check whether product channel exists or not.
					// 	$check_prod_exist_chanel = $this->ProductsModel->checkIsAlaCarteProductChannellist($stock_id,$dealer);
					// 	//echo $this->db->last_query();
					// 	$isChannelExists_channel = $this->isProductChannelExist($stock_id, $pro_id, $product_id);

					// 	if(count($check_prod_exist_chanel)>0){
					// 		foreach ($check_prod_exist_chanel as $value) 
					// 		{
					// 			$check_prod_exist_chanel_array[] = $value->channel_ids;
					// 		}
					// 	}
					// 	if(count($isChannelExists_channel)>0){
					// 		//foreach ($isChannelExists_channel as $value){ 
	    //                     	$check_alacarte_exist_array[] = $isChannelExists_channel->get;
	    //                 	//}
     //                	}
					// 	if(count($check_prod_exist_chanel_array)>0){
					// 		$result=array_intersect($check_prod_exist_chanel_array,$check_alacarte_exist_array);
					// 	}
					// 	// print_r($check_prod_exist_chanel_array);
     //                	//print_r($check_alacarte_exist_array);
     //               		// print_r($result);
					// 	if(count($result)>0){
					// 		$isChannelExists=1;
					// 	}else{
					// 		$isChannelExists=0;
					// 	}
					// 	if($isChannelExists>0){
					// 		echo $error_code=2; exit();
					// 	}
					// 	//check whether the channel can be editable or not.
					// 	//$ChannelEdit=$this->isProductChannelExistforEdit($pro_id,$product_id,$stock_id);						
					// 	//if($ChannelEdit>0){
					// 	//	echo $error_code=3; exit();
					// 	//}
					// }									
					
				// }
				// else{
				$allow_duplicate_channels = (isset($_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS)) ? $_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS : 0;
				if(isset($allow_duplicate_channels) && $allow_duplicate_channels==0) {	
					//check whether the channel exists or not.
					$checkchannel=$this->checkChannel($stock_id,$pro_id,$product_id);
					if($checkchannel>0){
						echo $error_code=7; exit();
					}
				}
				//}
			}				
						
			//check whether it is one time package or not.
			$alacarte_onetime=$this->checkIsOnetime($stock_id,$product_id,$cust_id);
			$value=explode('XXX',$alacarte_onetime);
			$msg=$value[2];
			if($value[1]!=''){
				if($value[1]==3){
					$error_code=4;
				}
				else if($value[1]==1){
					$error_code=5;
				}else{
					$error_code=6;
				}
				echo $error_code.'@#'.$msg; exit();
			}
		}
		//function for validating customer for stock movement by SWAROOP
		public function validate_customer()
		{
			$cond = '';
			$value = $_POST['value'];
			$flag = $this->input->post('search_flag'); 
			$dealer_id = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
			

			if($value!=''){
				if($flag==1){//customer_id
					$cond = " AND customer_id = $value ";
				}
				if($flag==2){//CRF or CAF
					$cond = " AND (REPLACE((customer_account_id),'-','') = '$value' || caf_no = '$value') ";
				}
				if($flag==3){//LCO Customer ID
					$cond = " AND baid = '$value' ";
				}
				if($flag==4){//Account Number
					$cond = " AND account_number = '$value' ";
				}
				
				$query = $this->db->query("select customer_id from customer where status = 1 AND dealer_id=$dealer_id $cond");
				if($query && $query->num_rows()>0)
				{	
					echo $query->row()->customer_id;
				}else{
					echo 0;
				}
			}else{
				echo 0;
			}	
		}
	//To get packages based on package type added by Sowmya on 8/8/16 
/*	function get_packages_with_type(){
    		$this->load->model('reportsmodel');
			$package_type = isset($_POST['pkgtype'])?$_POST['pkgtype']:-1;	
			$sel_package = isset($_SESSION['productId'])?$_SESSION['productId']:-1;	
			$response.='<option value="-1">Select</option>';
			
			$result= $this->productsmodel->getCASProducts($productId=-1,$package_type);
			foreach($result as $res)
			{
				$response .= '<option value="'.$res->product_id.'"'.(($sel_package == $res->product_id)?' selected="SELECTED"':'').'>'.$res->pname.'</option>';
							
			}
    		
			echo $response;
		} */
	
	   function get_packages_with_type() {
        $response = '';
        $this->load->model('reportsmodel');
        $package_type = isset($_POST['pkgtype']) ? $_POST['pkgtype'] : -1;
        $server_id = isset($_POST['serverid']) ? $_POST['serverid'] : -1; 
        $pluginid = isset($_POST['pluginid']) ? $_POST['pluginid'] :0;
        $sel_package = isset($_SESSION['productId']) ? $_SESSION['productId'] : -1;
        $response .= '<option value="-1">Select</option>';

        $result = $this->productsmodel->getCASProducts($productId = -1, $package_type,$server_id,$pluginid);
        foreach ($result as $res) {
            $response .= '<option value="' . $res->product_id . '"' . (($sel_package == $res->product_id) ? ' selected="SELECTED"' : '') . '>' . $res->pname . '</option>';
        }

        echo $response;
    }
                
                
			// Written by fayaz.
	function products_by_pricing_structure()
	{
		$this->load->model('BulkoperationModel');
		$pricing_structure_type = $this->input->post('pricing_structure');
		if($pricing_structure_type == -1)
		{
			$pricing_structure_type = 0;
		}
		$is_user_blocked = $this->EmployeeModel->isUserBlocked($_SESSION['user_data']->employee_id, 
$_SESSION['user_data']->users_type, $_SESSION['user_data']->dealer_id );
		$data['get_all_products'] = $this->BulkoperationModel->get_all_product($is_user_blocked,$status=0,
$pricing_structure_type);
		echo json_encode($data['get_all_products']); 
	}
//function to deactivate group added by Sowmya	
function deactivate_group(){
    			
    		$response = 0;	
    		$group_id = isset($_POST['gid'])?$_POST['gid']:0;	
			$sql_qry= $this->db->query("select count(group_id) cnt from employee_group where group_id=$group_id ");
			
			 if($sql_qry &&  $sql_qry->row()->cnt==0)
			 {
				 $sql_str = "UPDATE groups SET status = 0,updated_by = ".$_SESSION['user_data']->employee->employee_id.", updated_on = NOW() WHERE group_id = $group_id ";
				 $query = $this->db->query($sql_str);	
				 if($query && $this->db->affected_rows()>0)
				 {            
					 $response = 1;		
				 }
			
		    }	
			 echo $response;
		}	
	//function to activate group added by Sowmya		
		function activate_group(){
    		$response = 0;	
    		$group_id = isset($_POST['gid'])?$_POST['gid']:0;	
			$sql_str = "UPDATE groups SET status = 1,updated_by = ".$_SESSION['user_data']->employee->employee_id.", updated_on = NOW() WHERE group_id = $group_id ";
			$query = $this->db->query($sql_str);
			if($query && $this->db->affected_rows()>0)
				{            
					$response = 1;
					$success = "Activated";
                    $_SESSION['group_info'] = $success;		
				}
			
			echo $response;
		}	
//function to deactivate group from child added by Sowmya	
function deactivate_group_child(){
    			
    		$response = 0;	
    		$group_id = isset($_POST['gid'])?$_POST['gid']:0;	
			$sql_qry= $this->db->query("select count(group_id) cnt from employee_group where group_id=$group_id ");
			$sql_qry_customer= $this->db->query("select count(group_id) child_cnt from customer_group where group_id=$group_id ");
			 if($sql_qry &&  $sql_qry->row()->cnt==0 && $sql_qry_customer && $sql_qry_customer->row()->child_cnt==0)
			 {
				 $sql_str = "UPDATE groups SET status = 0,updated_by = ".$_SESSION['user_data']->employee->employee_id.", updated_on = NOW() WHERE group_id = $group_id ";
				 $query = $this->db->query($sql_str);			
				 if($query && $this->db->affected_rows()>0)
				 {            
					 $response = 1;	
					 $success = "Deactivated";
                     $_SESSION['group_info'] = $success;	
				 }
			
		    }	
			 echo $response;
		}	
	
//code to check the lco deposit receipt number duplicate written by chakri
    public function checkduplicatereceiptnum() {
           $receipt_num ='';
           $reseller_id ='';
           $res = -1;
           $receipt_num = $_POST['receipt_num'];
           $reseller_id = $_POST['reseller_id'];
           $employeeModel = new EmployeeModel();
           $res = $employeeModel->checkisduplicatereceipt($receipt_num,$reseller_id);
           if($res == -1){ //means duplicate receipt num
               echo -1;
           }
           
       }
    //code to get the complaint templates written by chakri
    public function get_complaint_templates() {
        $this->load->model('simplecomplaints_model');
        $html = '';
        $ComplaintCategory = $_POST['ComplaintCategory'];
        $templates = $this->simplecomplaints_model->getComplaintTemplates('',$ComplaintCategory,1);
       
        if (count($templates) > 0) {
            if (count($templates) == 1) {
                $html .= '1**';
                $html .= $templates[0]->title . "*" . $templates[0]->message;
            } else {
                $html .= '2**<table>';
                foreach ($templates as $template) {
                    $html .= "<tr>
						<td><input type='radio' id='$template->complaint_template_id' name='tmplate_id' title='$template->title' message='$template->message' value='$template->complaint_template_id' ></td>
						<th>$template->title</th>
					</tr>";
                    $html .= "<tr>
						<td></td>
						<td>$template->message</td>
					</tr>";
                }
                $html .= '</table>';
            }
        } else {
            $html .= '0**';
        }
        echo $html;
    }
	
	//delete secondary mobile numbers
   
     function delete_mobile() {
        
        $response = 0;
        $did = $_SESSION['user_data']->dealer_id;
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : 0;
        $mobile_no = isset($_POST['mobile_no']) ? $_POST['mobile_no'] : 0;
        $sql_qry = $this->db->query("DELETE FROM customer_mobile WHERE customer_id=$customer_id AND dealer_id='$did' AND mobile_no='$mobile_no' AND is_primary=0");
        if ($sql_qry && $this->db->affected_rows() > 0 ) {
            
                $response = 1;
            }
        
        echo $response;
    }
    
   // change as primary mobile
    function change_primary_mobile() { 
        $response = 0;
        $did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $unique_mobile=isset($_SESSION['dealer_setting']->MOBILE_UNIQUE)?$_SESSION['dealer_setting']->MOBILE_UNIQUE:0;
        $last_updated_by= isset($_SESSION['user_data']->employee->employee_id)?$_SESSION['user_data']->employee->employee_id:0;
        $last_updated_date= date('Y-m-d H:i:s');
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : 0;
        $mobile_no = isset($_POST['mobile_no']) ? $_POST['mobile_no'] : "";
        if($unique_mobile == 1)
        {
            $cond = " ";
        }else
        {
            $cond = "AND customer_id=$customer_id ";
        }
        $unique_query = $this->db->query("select count(customer_id) as cnt from customer where dealer_id=$did and  mobile_no='$mobile_no' $cond ");
        if($unique_query && $unique_query->row()->cnt>0)
        {
            $response = 0; 
            
        }else
        { 
            $query1=$this->db->query("select customer_mobile_id from customer_mobile WHERE customer_id=$customer_id AND dealer_id='$did' and  mobile_no='$mobile_no'");
            if($query1->num_rows()>0)
            {
                $sql_qry1 = $this->db->query("update customer_mobile  set is_primary=0 WHERE customer_id=$customer_id AND dealer_id='$did'");
                $sql_qry2 = $this->db->query("update customer  set mobile_no='$mobile_no',customer_edit_update=1,last_updated_by= '$last_updated_by',last_updated_date='$last_updated_date' WHERE customer_id=$customer_id AND dealer_id='$did' ");
                $sql_qry3 = $this->db->query("update customer_mobile  set is_primary=1,modified_by='$last_updated_by',modified_date='$last_updated_date' WHERE customer_id=$customer_id AND dealer_id='$did' AND mobile_no='$mobile_no'");

                if ($sql_qry3 && $this->db->affected_rows() > 0 ) {

                        $response = 1;
                    } else {
                    $response = 0;
                }
            }
        }
        echo $response;
    }
 
	   
 //edit secondary mobile number   
 function edit_mobile() {
        
        $response = 0;
        $did = $_SESSION['user_data']->dealer_id;
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : 0;
        $new_mobile_no = isset($_POST['new_mobile_no']) ? $_POST['new_mobile_no'] : 0;
        $cntry_code = isset($_POST['cntry_code']) ? $_POST['cntry_code'] : 0;
        $customer_mobile_id = isset($_POST['customer_mobile_id']) ? $_POST['customer_mobile_id'] : 0;
         $old_mobile = isset($_POST['old_mob_no']) ? $_POST['old_mob_no'] : '';
        $updated_mobile=$cntry_code.$new_mobile_no;
        $unique=$this->CustomersModel->check_mobile($updated_mobile, $customer_id,$type=2,$old_mobile);
         if($unique==0){
        
             $last_updated_by= isset($_SESSION['user_data']->employee->employee_id)?$_SESSION['user_data']->employee->employee_id:0;
            $last_updated_date= date('Y-m-d H:i:s');
            $sql_qry = $this->db->query(" update customer_mobile set mobile_no='$updated_mobile',modified_by='$last_updated_by',modified_date='$last_updated_date' WHERE customer_id=$customer_id AND dealer_id='$did' AND customer_mobile_id='$customer_mobile_id'");
            if ($sql_qry && $this->db->affected_rows() > 0 ) {
            
               $response = 1;
             }
            
         }
        
        echo $response;
    }  
	function update_session_id()
        {
             $res = $this->EmployeeModel->update_session_id();
            if ($res)
                echo 1;
            else
             echo 0;
        }
	

 //Wrapper function for Temporary Activation of STB by SWAROOP
    public function WS_temporaryActivation() {
        $cf_obj = new CommonFunctions();
        $customer_id = $_POST['customer_id'] = urldecode($_POST['customer_id']);
        $dealer_id = $_POST['dealer_id'] = urldecode($_POST['dealer_id']);
        $employee_id = $_POST['employee_id'] = urldecode($_POST['employee_id']);
        $_POST['authToken'] = urldecode($_POST['authToken']);
        if ($customer_id > 0 && $dealer_id > 0 && $employee_id > 0) {
            $cf_obj->setUserData($employee_id, $dealer_id);
            $cf_obj->setUserAccess();
            $cf_obj->setDealerSettings();
            $cf_obj->setCasAccess();

            $result = $this->activateTempDeactivatedServices();
            //write_to_file("In ajax after activateTempDeactivatedServices result===".$result."==post==".json_encode($_POST));
            if ($result == 1) {
                echo 1;
            } else {
                echo 2;
            }
        } else {
            echo 3;
        }
        UNSET($_SESSION);
        UNSET($_POST);
    }

    //function for activating packages which are deactivated using temporary deactivation reason for DIGI by Swaroop ON May 28 2019
    public function WS_temporaryActivationFromDIGI() {
    	log_message('debug','in WS_temporaryActivationFromDIGI function start');
        log_message('debug','in WS_temporaryActivationFromDIGI function start post values - '.json_encode($_POST));
        $return_array = array();
        $temp_activation_details = isset($_POST['temp_activation_details']) ?json_decode($_POST['temp_activation_details'], true):array();
        if(isset($temp_activation_details) && count($temp_activation_details)>0)
        {               
            // log_message('debug','in WS_serviceDeactivationFromDIGI function in main if');
            foreach($temp_activation_details as $record){  
                $msg = '';  
                $package_activation_id = isset($record['package_activation_id']) ?($record['package_activation_id']):'';
                $authToken = $_POST['authToken'] = isset($record['authToken']) ?($record['authToken']):'';
                $customerId = $_POST['customer_id'] = isset($record['customerId']) ?($record['customerId']):'';
                $dealerId = $_POST['dealer_id'] = isset($record['dealerId']) ?($record['dealerId']):'';
                $employeeId = $_POST['employee_id'] = isset($record['employeeId']) ?($record['employeeId']):'';


                $cf_obj = new CommonFunctions();
		        $customer_id = $_POST['customer_id'];
		        $dealer_id = $_POST['dealer_id'];
		        $employee_id = $_POST['employee_id'];
		        $_POST['authToken'] = $_POST['authToken'];
		        if ($customer_id > 0 && $dealer_id > 0 && $employee_id > 0) {
		            $cf_obj->setUserData($employee_id, $dealer_id);
		            $cf_obj->setUserAccess();
		            $cf_obj->setDealerSettings();
		            $cf_obj->setCasAccess();

		            $result = $this->activateTempDeactivatedServices();
		            log_message('debug','in WS_temporaryActivationFromDIGI function result - '.json_encode($result));
		            if ($result == 1) {
		                $msg .= "Success";
		            } else {
		                $msg .= "STB Activation Failed";
		            }
		        } else {
		            $msg .= "Invalid Post Values";
		        }

		        $return_array[] = array('package_activation_id'=>$package_activation_id,'msg'=>$msg);
                log_message('debug','in WS_boxDeactivationFromDIGI function start return_array - '.json_encode($return_array));

			}
		}

		UNSET($_SESSION);
        UNSET($_POST);

        if(count($return_array)>0){
            $response = array('status_code' => 0,'final_return_array'=>json_encode($return_array));
        } else {
            $response = array('status_code' => 1,'final_return_array'=>array());
        }
        //log_message('debug','ezybill final_return_array result '.json_encode($response));
        echo json_encode($response);
    }

public function activateTempDeactivatedServices() { 
      	$customer_id = $_POST['customer_id'];
      	//default temp activation - START
        $deact_reason_id = 8;
        $act_reason_id = 9;
        $get_full_bill = 0;
        //default temp activation - END
        $dealer_id = $_SESSION['user_data']->dealer_id;

        $service_activation = isset($_POST['service_activation'])?$_POST['service_activation']:0;
        if($service_activation==1){
        	//unpaid customer services activation - START
	        $deact_reason_id = 2;
	        $act_reason_id = 0;
	        $get_full_bill = 1;
	        //unpaid customer services activation - END	
        }  

        if($service_activation==2){
        	//expired customer services activation - START
	        $deact_reason_id = 3;
	        $act_reason_id = 0;
	        $get_full_bill = 1;
	        //expired customer services activation - END	
        }        
        if($service_activation==0){
        	$get_deact_customer_services = $this->CustomersModel->get_deact_customer_services($customer_id, $deact_reason_id,array(),1,0,0);	
        }else{
        	$get_deact_customer_services = $this->CustomersModel->get_deact_customer_services($customer_id, $deact_reason_id, $customer_service_id=array(),$status=1,$device_close_cond=1);
        	
        	if($service_activation==1){//unpaid customer previous services change
				//UPDATE REASON = 4 for deactivate services in customer service table
            	$this->CustomersModel->updateCustomerServicesReason($customer_id, $service_reason = 4, $dealer_id, 0, $previous_reason_id = 2);
			}
        }
        
        $count_get_customer_services = count($get_deact_customer_services);
		$add_active_services = 0;
		$status=0;
	 	$add_active_services = array();
        if ($count_get_customer_services > 0) {
			$stock_array = array();
			foreach($get_deact_customer_services as $stock_ids){
				array_push($stock_array,$stock_ids->stock_id);
			}

			$max_stock = 0;
			if(count($stock_array)>0){
				$max_stock = max($stock_array);
			}

			$add_active_services = $this->CustomersModel->add_activate_services($customer_id, $get_deact_customer_services, $act_reason_id,$setting_value = 0, $users_type = '', $get_full_bill,$max_stock,$deducted_from = 4,0,0,$depositelovvalue=0,$showblockproduct=0,$allowduplicatechannel=0,$addonafterbasepack=0,$extraboxes=0,$enablestbdiscout=0,$enablehead=0,$showpaf=0,$digi_activation_from=0,$check_deposit_inside=1,$service_activation,$default_quantity=1);        
			
			$message ='';
			foreach($add_active_services as $key=>$val){
				$message.=$val['message']."\n";
			}
			
            foreach($add_active_services as $status_val){
                $status=$status_val['status'];
			if($status==1)
           	break;
            }
            //write_to_file("In ajax activateTempDeactivatedServices status===".$status);
			if($status>0){
				if($service_activation==0){//default temp services reason change
					//UPDATE REASON = 9 for deactivate services in customer service table
      				$this->CustomersModel->updateCustomerServicesReason($customer_id, $act_reason_id, $dealer_id, 0, $previous_reason_id = 8);
				}

				if($service_activation==2){//expired customer previous services change
					//UPDATE REASON = 10 for expired deactivate services in customer service table
	            	$this->CustomersModel->updateCustomerServicesReason($customer_id, $service_reason = 10, $dealer_id, 0, $previous_reason_id = 3);
				}
			}
        }else{ //message is added by ramya
            $message="There is no service to activate.Please check the box status";
        }									   
        
        if ($this->input->post('authToken')) {
           // if ($add_active_services > 0) {
			if ($status > 0) {
                return "1";
            } else {
                return "0";
            }
        } else {
            //if ($add_active_services > 0) {
				 if ($status > 0 && $service_activation==0) {
                /* APPLY REACTIVATION CHARGES */
                                                  
                $did = isset($_SESSION['user_data'])?$_SESSION['user_data']->dealer_id:0;
                $eid = isset($_SESSION['user_data'])?$_SESSION['user_data']->employee_id:0;
                $cf_obj = new CommonFunctions();
                $cf_obj->special_charges('TEMPORARY_ACT',$customer_id,$did,$eid,$remarks='');
				 }          
                /* END OF CHARGES */
                //echo 1;
           // } else {
                //echo 0;
				 echo  $message;	 
            
        }
    }

    public function getMandalDetails() {
        $id = $_POST['id'];
        $sql_str = "SELECT m.mandal_id, m.mandal_code,m.mandal_name,m.district_id,elc.name countryName,els.name statename, elc.iso, els.id FROM eb_mandals m
                        LEFT JOIN eb_districts d ON d.district_id = m.district_id
                        LEFT JOIN eb_location_states els ON els.id = d.state_id
                        LEFT JOIN eb_location_countries elc ON elc.iso=els.country_code where dealer_id=" . $_SESSION['user_data']->dealer_id . " and m.mandal_id=$id";
        $query = $this->db->query($sql_str);
        if ($query) {
            if ($query->num_rows() > 0) {
                $result = $query->row()->iso . 'XXX' . $query->row()->id . 'XXX' . $query->row()->mandal_code . 'XXX' . $query->row()->mandal_name . 'XXX' . $query->row()->mandal_id . 'XXX' .  $query->row()->district_id;
                echo $result;
            } else
                echo '0';
        } else
            echo '0';
    }

    public function getMandalsOfDistrict($district_id)
    {

        $vals = '<option value="-1">Select Mandal</option>';
        if ($district_id != '') {
            $sql_str = "SELECT mandal_id,mandal_name FROM  eb_mandals  WHERE district_id='$district_id' order by mandal_name";
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();

                foreach ($res as $mnd) {
                    $vals .= '<option value="' . $mnd->mandal_id . '"'  . '>' . $mnd->mandal_name . '</option>';
                }
            }
        }
        echo $vals;
    }

    public function getAllLocOfSelectedDistrict()
    {
       $selected_district = isset($_POST['dflt_distr']) ? $_POST['dflt_distr'] : '';
       $mnd_loc = array('mandals','locations');
        $vals = '<option value="-1">Select Mandal</option>';
        if ($selected_district != '') {
            $sql_str = "SELECT * FROM  eb_mandals  WHERE district_id='$selected_district' order by mandal_name";
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();
                $mnd_loc['mandals'] = array();
                foreach ($res as $mnd) {
                    // $vals .= '<option value="' . $mnd->mandal_id . '"'  . '>' . $mnd->mandal_name . '</option>';
                    $mnd_loc['mandals'][] = array('mandal_id'=>$mnd->mandal_id,'mandal_name'=>$mnd->mandal_name); 
                }
            }

            $sql_str = "SELECT ll.location_id,ll.location_name,mnd.district_id FROM eb_mandals mnd "
                      ." INNER JOIN eb_location_locations ll ON ll.mandal_id = mnd.mandal_id "
                      ." WHERE mnd.district_id =$selected_district";

            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();
                $mnd_loc['locations'] = array();
                foreach ($res as $loc) {
                    $mnd_loc['locations'][] = array('location_id'=>$loc->location_id,'location_name'=>$loc->location_name); 
                }
            }
        }
        echo json_encode($mnd_loc);
    }

    public function getLocationsOfSelectedMandal() {
        $mandal_id = (isset($_POST['mandal_id'])) ? $_POST['mandal_id'] : 0;
        $employee_id = (isset($_POST['employee_id'])) ? $_POST['employee_id'] : 0;
        $location_id = (isset($_POST['location_id'])) ? $_POST['location_id'] : 0;
        $vals = '<option value="-1">Select City</option>';
        $where = "  ";
        if ($mandal_id != '' && $mandal_id != -1) {
            $sql_str = "SELECT ell.location_id,ell.state_id,ell.dealer_id,ell.location_name,ell.location_code,ell.last_reseller_id,ell.phase_id,ell.mandal_id FROM  eb_location_locations ell ";
            if($employee_id != 0)
            {
                $sql_str .= " inner join eb_location_lco_mapping llm ON ell.location_id = llm.location_id ";
                $where .= " llm.employee_id = $employee_id AND ";
            }

            $sql_str = $sql_str." WHERE ".$where." ell.mandal_id='$mandal_id' order by ell.location_name";

            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();
                if(count($res) == 1 )
                {
                    $vals = '';
                }
            foreach ($res as $loc) {
                $selected = ( $location_id == $loc->location_id )? ' SELECTED ' : ''; 
                $vals .= '<option value="' . $loc->location_id . '"' . $selected . '>' . $loc->location_name . '</option>';
            }
            }
        }
        echo $vals;
    }

    public function getDistrictsOfSatate() {
        $selected_state = $_POST['st'];
        $c = (isset($_POST['dis_id'])) ? $_POST['dis_id'] : 0;

        $vals = '<option value="-1">Select District</option>';
        if ($selected_state != '') {
            $sql_str = "SELECT * FROM  eb_districts  WHERE state_id='$selected_state' order by district_name";
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();
                if(count($res) == 1)
                {
                    $vals = '';
                }
                foreach ($res as $city) {
                    $vals .= '<option value="' . $city->district_id . '"' . (($c) ? $c == $city->district_id ? ' selected="SELECTED"' : '' : '') . '>' . $city->district_name . '</option>';
                }
            }
        }
        echo $vals;
    }

    public function getSelectedDistrictMandals()
    {
        $selected_district = isset($_POST['dflt_distr']) ? $_POST['dflt_distr'] : '';
        $vals = '<option value="-1">Select Mandal</option>';
        if ($selected_district != '') {
            $sql_str = "SELECT * FROM  eb_mandals  WHERE district_id='$selected_district' order by mandal_name";
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();

                foreach ($res as $mnd) {
                    $vals .= '<option value="' . $mnd->mandal_id . '"'  . '>' . $mnd->mandal_name . '</option>';
                }
            }
        }
        echo $vals;
    }

    //get products of particular server by hemalatha
    public function getSelectedServerproducts() {
       $backend_setup_id=$_POST['backend_setup_id'];
       $vals='<option value="">Select Product</option>';
       if($backend_setup_id!=""){
           $sql="SELECT * from eb_products where backend_setup_id='$backend_setup_id' and status=1";
            $query=$this->db->query($sql);
            if($query && $query->num_rows()>0) {
                $res=$query->result();
                foreach ($res as $result){
                    $vals.='<option value="'.$result->product_id.'">'.$result->pname.'</option>';
                }
            }
       } 
       echo $vals;  
    }

    public function getMandalsOfSelectedDistrict() {
        $c = (isset($_POST['dis_id'])) ? $_POST['dis_id'] : 0;
        $mandal_id = (isset($_POST['mandal_id'])) ? $_POST['mandal_id'] : 0;
        $vals = '<option value="-1">Select Mandal</option>';
        if ($c != '') {
            $sql_str = "SELECT * FROM  eb_mandals  WHERE district_id='$c' order by mandal_name";
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->result();
                
                foreach ($res as $mnd) {
                    $selected = ( $mandal_id == $mnd->mandal_id )? ' SELECTED ' : ''; 
                    $vals .= '<option value="' . $mnd->mandal_id . '"' . $selected . '>' . $mnd->mandal_name . '</option>';
                }
            }
        }
        echo $vals;
    }
	//Function to get Vc list written by venkat.
	public function getVclist() {


        $vc_numbers = array();
        if (!empty($_POST['vcnumber'])) {
            $vcnumber = $_POST['vcnumber'];
            $vc_numbers = explode(",", $vcnumber);
        }

        $cond ='';
        $cond1 ='';
        $sip = -1;
        if (isset($_SESSION['modules']['CAS']) && $_SESSION['modules']['CAS']) {
            $sip = isset($_POST['sip']) ? $_POST['sip'] : -1;
        }
         
         if ($sip != '-1') {
                $cond .= " AND bs.backend_setup_id=$sip ";
                $cond1 = " AND v.backend_setup_id=$sip ";
            }
         
         $search_vcnumber = isset($_POST['search_vcnumber']) ? $_POST['search_vcnumber'] : '';
        $loc = "";
        $usemac = '';
        $backendsid = "";
        $joinCondition = '';
         if(isset($search_vcnumber) && $search_vcnumber !=''){
             $joinCondition = " AND v.vc_number = '$search_vcnumber'";
         }
        $stockloc_resellerid = $this->input->post('resellerid') ? $this->input->post('resellerid') : 0;
            $sql = "SELECT v.all_vc_id,v.vc_number,v.reseller_id,v.is_trash,v.is_blocked,v.created_on
                    FROM eb_all_vcs v 
                    INNER JOIN backend_setups bs ON bs.backend_setup_id = v.backend_setup_id
                    WHERE v.dealer_id=" . $_SESSION['user_data']->dealer_id . " 
                    AND v.is_trash=0 
                    $joinCondition
                    $cond
                    $cond1
                    AND v.reseller_id=$stockloc_resellerid 
                    ";
            $res = $this->db->query($sql);

         if( $res && $res->num_rows() > 0){
            $r = $res->result();
             $count_res = count($r);
         }
            
          
                $i = 1;
                if (isset($r) && count($r) > 0) {
                    foreach ($r as $res) {
                        $loc .= '<tr ><td width="27" style="text-align:center;"><input type="checkbox" name="vcnumber1[]" class="check_all" value="' . $res->vc_number . '"' . (in_array($res->vc_number, $vc_numbers) ? ' checked="CHECKED"' : '') . '/></td>
							
								<td width="23">' . $i++ . '</td>
								<td width="94">' . $res->vc_number . '</td>
								<td width="50">' . $res->created_on . '</td></tr>';
                    }
                } else {
                    $loc = '<tr><td colspan="7">No Records..</td></tr>';
                }
            
            echo $loc;
       
    }
       public function get_total_available_vclist() {
        $serverIp = isset($_POST['sip'])?$_POST['sip']:0;
        $stockloc = isset($_POST['stockloc'])?$_POST['stockloc']:0;
        $stockloc_resellerid = isset($_POST['resellerid'])?$_POST['resellerid']:0;
     
        $cond = "";
        if ($serverIp != '-1') {
            $cond = " AND backend_setup_id = $serverIp";
        }
        if ($stockloc) {
            $sql = "SELECT all_vc_id FROM eb_all_vcs WHERE dealer_id=" . $_SESSION['user_data']->dealer_id . "  AND reseller_id='$stockloc_resellerid' $cond  AND is_trash=0";
            $res = $this->db->query($sql);
            echo $res->num_rows();
        }
    }
    //Function to check is Vc number exist or not written by Venkat.
    public function isVcnumberExist(){
        $this->load->model('inventorymodel');
        $vcResult = array();$vcnumberId = 0;
        $resellerid = $this->input->post('resellerid')?$this->input->post('resellerid'):0;
        $vc_number = $this->input->post('vc_number')?$this->input->post('vc_number'):'';
        $serverIp = $this->input->post('sip')?$this->input->post('sip'):'';
        $dealerId = $_SESSION['user_data']->dealer_id;
        $vcResult = $this->inventorymodel->getVcDetails($resellerid,$vc_number,$serverIp,$dealerId);
        if(isset($vcResult) && count($vcResult)>0){
          $vcnumberId = $vcResult->all_vc_id;  
        } 
        echo $vcnumberId;
    }
 //to get the lco due amount & advance amount & max billing id - archana
    public function get_lco_due_amount(){
       $this->load->model(array('lco_billing_model','paymentsmodel'));			
       $lco_id = $_POST['lco_id'];
       $did = $_SESSION['user_data']->dealer_id;
       $advance = 0;
	   $deposit_for_billing=0;$bill_amount=0;$tds_deduction=0;$get_tds_deduction_accdb=array(); $due = 0;
       $due_amount_res = $this->lco_billing_model->lco_due_amount($lco_id,$did);
       $billing_id = $due_amount_res[0]->billing_id;
       $due = $due_amount_res[0]-> due;
	 
	   //$deposit_for_billing=$due_amount_res[0]->tds;
	   $tax_paid_by=$due_amount_res[0]->tax_paid_by;
		$special_charge=$due_amount_res[0]->special_charge;//by ravula for special charge
	   $bill_amount=$due_amount_res[0]->bill_amount-$due_amount_res[0]->discount_amount+$special_charge;//adding special charege
		
	   if($due>0){
	   $tds_deduction=$this->paymentsmodel->tds_deduction_lco_payment($lco_id);
		}
		
       if($due < 0 ){          
           $advance = -($due);                      
           echo $billing_id.'**'.sprintf('%0.2f', $due).'**'.$advance.'**'.$deposit_for_billing.'**'.sprintf('%0.2f',$tds_deduction).'**'.sprintf('%0.2f',$bill_amount);
       } else {               
          echo $billing_id.'**'.sprintf('%0.2f', $due).'**'.$advance.'**'.$deposit_for_billing.'**'.sprintf('%0.2f',$tds_deduction).'**'.sprintf('%0.2f',$bill_amount);
       }
       
    }  
    
	public function checkDuplicateReceipt(){
        $result=0;
        $receipt_no = isset($_POST['receipt_no']) ? $_POST['receipt_no'] : ''; 
        $dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        if ($receipt_no!='') {
            $sql = "SELECT replacement_id FROM eb_replaced_stb WHERE dealer_id=$dealerId  AND receipt_number='$receipt_no' ";
           $res = $this->db->query($sql);
           if ($res && $res->num_rows() > 0 ) {
                $result = 1;
            }
            
         }
        echo $result;
       }
	
	//added function for get child groups through employeeid by Ravula on 17/07/2017
	
	 public function getChildGroups() {
        $employee_id = $this->input->post('employee_id');
        $child_groups = $this->CustomersModel->listChildGroups($employee_id);
         $cgroups = array();
        foreach ($child_groups as $grp) {
            if(array_key_exists($grp->employee_id, $cgroups))
            {
                $cgroups[$grp->employee_id][] = array('group_id'=>$grp->group_id,'group_name'=>$grp->group_name,'business_name'=>$grp->business_name,'lcocode'=>$grp->dist_subdist_lcocode);
            }
            else
            {
                $cgroups[$grp->employee_id] = array();
                $cgroups[$grp->employee_id][] = array('group_id'=>$grp->group_id,'group_name'=>$grp->group_name,'business_name'=>$grp->business_name,'lcocode'=>$grp->dist_subdist_lcocode);
            }
        }
        
        $session_val = isset($_POST['session_val']) ? $_POST['session_val'] : '0';
            
        $val = '<option value="-1">Select Child Group</option>';
        if (count($cgroups) > 0) {
           
                
            foreach ($cgroups as $key => $row) {
                
                    $val .= '<optgroup label="'.$row[0]['business_name'].'( '.$row[0]['lcocode'].' )'.'" id ="parent">';
                // unset($row[0]);
                foreach($row as $ind => $grprow){
                    $val .= '<option value="' . $grprow['group_id'] . '" >' . $grprow['group_name'] . '</option>';
                }
                    $val .= '</optgroup>';
            }
        }
        echo $val;
    }
	public function serverwisecount()
	{
	 $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : 0;
	 $employee_name = isset($_POST['employee_name']) ? $_POST['employee_name'] : '';
	 $duedate = isset($_POST['duedate']) ? $_POST['duedate'] : '';
	 $lcobillingid = isset($_POST['lcobillingid']) ? $_POST['lcobillingid'] : '';
	 $code = isset($_POST['code']) ? $_POST['code'] : '';
		for($i=0;$i<75;$i++)
		{
			if($i%5 == 0)
			{
				$arr = $i;
			}
		}
	
	if($employee_id > 0)
	{
		$records = $this->backend_setup_model->getcaswiseactivestbcount($employee_id);
		$inserted_logscount = $this->backend_setup_model->getcout_of_insertedlogs($employee_id);
		$c = array();
		for($i=0;$i<75;$i++){
		if($i%5 == 0){ $c[$i] = $i; }

			}
        
       
		$val = "";
		 if($inserted_logscount == count($records))
		{
			$val = "<label>Deactivation Process is in Prgoress.Please Try After Sometime.</label>";
		}
		$val .= '<form name="deactivate_lco" id="deactivate_lco" method="post" action="">';
		$val .= '<input type="hidden" name="deactivate_lco" value="1">';
		$val .= '<input type="hidden" name="employee_id" id="employee_id" value="'.$employee_id.'">';
		$val .= '<input type="hidden" name="employee_name" id="employee_name" value="'.$employee_name.'">';
		$val .= '<input type="hidden" name="code" id="code" value="'.$code.'">';
		$val .= '<input type="hidden" name="duedate" value="'.$duedate.'">';
		$val .= '<input type="hidden" name="lcobillingid" value="'.$lcobillingid.'">';
		$val .= '<input type="hidden" name="deactivate_lco" value="1">';
		$val .= '<label>LCO Name : '.$employee_name.'('.$code.')</label>';
		$val .= '<table border="0" cellspacing="5" cellpadding="5" width="100%" class="mygrid" id="mygrid" style="background:none;"><input type="hidden" name="product" id="product" value="BIlling Slots 4 Re">';
		$val .= '<thead><tr><th>Server</th><th>Active Box Count</th><th>Percentage</th></tr>	</thead>';
		      foreach($records as $sl) { 
				 $backend_id =  $sl->backend_setup_id;
				 $result =  $this->backend_setup_model->getdeactive_records($employee_id,$backend_id);
				 
				 if($result)
				 {
					
				  $disable = "disabled=disabled";
				 }
				 else
				 {
					 
				  $disable = "";
				 }
				$val .=  '<tr><td class="servertype"> ' .  $sl->cas_server_type . ' </td>';
				$val .=	'<td class="count">'. $sl->stbcount.'</td>';
				$val .=	 '<td><select class="percentage"  '.$disable.' name="percentage_'.$sl->backend_setup_id.'" stbcount='.$sl->stbcount.' servername='.$sl->cas_server_type.'>';
				 if($result)
				  {
					  foreach($result as $r){
					   $val .= "<option>$r->deactivation_percentage</option>";
					  }
				 
				  } 
				  else {
					for($i=0;$i<=75;$i++){
						if($i%5 == 0){ 
						
                      $val .= "<option>$i</option>";
						}
					} }
				  $val.='</select></td></tr>';
				
				 } 
	
		$val .=  '<tr>
		              <td align="left">Remarks</td>
		              <td align="center">:</td>
		              <td align="left"><textarea name="remark_txt" id="remark_txt"></textarea></td>
		            </tr>
				</table></form>';


	
       
		echo $val; }
	}
	public function blocklco()
	{
		$response = 0;
		$dealer_id=$_SESSION['user_data']->dealer_id;	
		$employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : 0;
		$remarks=isset($_POST['remarks']) ? $_POST['remarks'] : '';
		$data= array("action_type"=>8,
					 "remarks"=>$remarks,
					 "employee_id"=>$employee_id,					
					"created_by"=>$dealer_id,
					"created_on"=>date('Y-m-d H:i:s'),
					"modified_by"=>$dealer_id,
					 "modified_on"=>date('Y-m-d H:i:s')
					);
		$this->db->insert('employee_status_logs',$data);
		$query = $this->db->query("update employee set is_unpaidlco = 1  where employee_id = $employee_id");
		if($query && $this->db->affected_rows() > 0) {
		 $response = 1;
	}
		echo $response;
	}
	public function unblocklco()
	{
		$response = 0;
		$msg = '';
		$today = date("Y-m-d");
		$dealer_id=$_SESSION['user_data']->dealer_id;		
	    $remarks=isset($_POST['remarks']) ? $_POST['remarks'] : '';
		$employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : 0;
		$remarks=isset($_POST['remarks']) ? $_POST['remarks'] : '';
		$data= array("action_type"=>9,
					 "remarks"=>$remarks,
					 "employee_id"=>$employee_id,					
					"created_by"=>$dealer_id,
					"created_on"=>date('Y-m-d H:i:s'),
					"modified_by"=>$dealer_id,
					 "modified_on"=>date('Y-m-d H:i:s')
					);
		$this->db->insert('employee_status_logs',$data);
		$count = $this->employeemodel->getactivedcustomerservices($employee_id,$today);
		if($count > 0)
		{
				 $msg = "You can not perform this operation again as deactivation is already in progress";  
				echo $msg;
		}
		else
		{
			
		$data= array("action_type"=>9,
					 "remarks"=>$remarks,
					 "employee_id"=>$employee_id,					
					"created_by"=>$dealer_id,
					"created_on"=>date('Y-m-d H:i:s'),
					"modified_by"=>$dealer_id,
					 "modified_on"=>date('Y-m-d H:i:s')
					);
		$this->db->insert('employee_status_logs',$data);
		$query = $this->db->query("update employee set is_unpaidlco = 0  where employee_id = $employee_id");
		if($query && $this->db->affected_rows() > 0) {
			
	 $response = 1;
		
		} 
		echo $response; }
	
	}
	 public function getpolicycontent()
    {
        $type = isset($_POST['ptype'])?$_POST['ptype']:'';
        if($type)
        {
            $did = $_SESSION['user_data']->dealer_id;
            $result = $this->inventorymodel->privacypolicylist($did,1,$type);
            foreach($result as $r){ echo $r->description; }
            
        }
        
    }
//select the operation to set network id and smart pin  
    //select the operation to set network id and smart pin  
     public function get_select_options() {
        $server_type = $_POST['server_type'];
        $val = '';
        // added by Ajeet
        $refresh_option='';
      if(isset($_SESSION['casAccess']->REFRESH_OPTION_CAS) && ($_SESSION['casAccess']->REFRESH_OPTION_CAS==1)){
         $refresh_option="<option value='4'>Refresh</option>";
      }

        
        if ($server_type == 'ICAS') {

            $val .= '<option value="-1">Select option</option>';
            $val .= '<option value="1">Network Change</option>';
            $val .= '<option value="3">Message ID Reset</option>';
            $val .= '<option value="4">Pin Reset</option>';
            $val .= '<option value="5">Bulk enableStb</option>';
            $val .= '<option value="100">suspended</option>';
            
        } else if ($server_type == 'NAGRA') {

            $val .= '<option value="1">Network Change</option>';
            
        } else if ($server_type == 'ABV') {
            
            $val .= '<option value="2">IPIN change</option>';
            
        } else if ($server_type == 'LogicEastern') {
            
            $val .= '<option value="6">Bulk SurrenderSubscriber</option>';
            $val .= '<option value="100">Status Refresh</option>';
            
        } else if ($server_type == 'Catvision') { //added by ravula for Catvission Open , Cless and unpair Command Mislenious Operations
            
            $val .= '<option value="-1">Select option</option>';
            $val .= '<option value="101">Register Smart Card</option>';
            $val .= '<option value="66">Smart Card & High Security Chip ID</option>';
            $val .= '<option value="20">Unpair Subscriber</option>';
        } elseif ($server_type == 'CDCAS'){

       		$val .= '<option value="-1">Select option</option>';
            //$val .= '<option value="100">FREEZE</option>';
            //$val .= '<option value="102">UNFREEZE</option>';
             $val .= '<option value="2">IPIN change</option>';
	      $val .= $refresh_option; // added by Ajeet

        } elseif($server_type == "Only1"){
        	
            $val .= '<option value="-1">Select option</option>';
            $val .= '<option value="100">suspended</option>';
            $val .= '<option value="102">unsuspended</option>';

        }elseif($server_type == "KingVon"){

        	 $val .= $refresh_option; // added by Ajeet
        }
        else if ($server_type == 'IRDETO') {
		$val .= '<option value="-1">Select option</option>';
		$val .= '<option value="200">Atribute OSD</option>'; 
		$val .= '<option value="2">IPIN change</option>';
		 $val .= $refresh_option; // added by Ajeet 
        }
	else if ($server_type == 'NDS') {
		$val .= '<option value="-1">Select option</option>';
		$val .= '<option value="2">IPIN change</option>';  
		 $val .= $refresh_option; // added by Ajeet
        }
	else if ($server_type == 'TOPREAL') {
		$val .= '<option value="-1">Select option</option>';
		$val .= '<option value="2">IPIN change</option>'; 
		$val .= '<option value="3">PAIR REFRESH</option>';
		 $val .= $refresh_option; // added by Ajeet
        }
	else if ($server_type == 'CISCO') {
		$val .= '<option value="-1">Select option</option>';
		$val .= '<option value="2">IPIN change</option>';  
		 $val .= $refresh_option; // added by Ajeet
        }

        else {
            
            $val = '<option value="-1">Select option</option>';
            
        }
        echo $val;
    }

     //function to get the paymentgateway backendsetup form by hemalatha
    public function getpaymentgatewaydetails(){
        $this->load->model('backend_setup_model');
        $paytype=isset($_POST['paytype'])?$_POST['paytype']:0;
        $select_paymenttype =isset($_POST['select_paymenttype'])?$_POST['select_paymenttype']:1;
        if($select_paymenttype == 0){
        	 $select_paymenttype = 1;
        }
        $pguri_segment=$this->backend_setup_model->get_backend_setup_id($paytype,$select_paymenttype);
        //  print_r($this->db->last_query());
        // echo "<pre>";print_r($pguri_segment);
        // echo "<br>";exit();
        $pgFormDetails = $this->buildform($paytype,$pguri_segment,$select_paymenttype);

        // print_r($this->db->last_query());
        // echo "<pre>";print_r($pgFormDetails);
        // exit;


        $result = '';
        if(count($pgFormDetails) > 0){
        $pginfo = $this->backend_setup_model->getPaymentGatewayDetails_new($paymentid=0,$pguri_segment,$pgFormDetails['tagnames'],$select_paymenttype);
        // print_r($this->db->last_query());
        // echo "<pre>";print_r($pgFormDetails);
        //  echo "<pre>";print_r($pginfo);exit;
        $i=0;
        if(count($pginfo)>0){
            foreach($pginfo[0] as $key => $value){
                    $pgDetails[$key] = $value;
                    $i++;
            }
        }
        $result='<form name="new_gateway_frm" id="new_gateway_frm" method="post">';
                $result.= '<table class="table_pg">';
						for($i=0;$i<count($pgFormDetails['captions']);$i++){
                         $value=isset($pgDetails[$pgFormDetails['tagnames'][$i]])?$pgDetails[$pgFormDetails['tagnames'][$i]]:'';
                                                    $result.='<tr>';
						     $result.='<td>';
                                                     $result.=ucwords (str_replace('_',' ',$pgFormDetails['captions'][$i]));
                                                    $result.='</td>';
								$result.=' <td><input type="text" name="'.$pgFormDetails['tagnames'][$i].'" id="'.$pgFormDetails['tagnames'][$i].'" value="'.$value.'" required/></td>';
							 $result.='</tr>';
						 }
							  $result.='<tr>
							  <td colspan="2" align="center">
							  <input type="hidden"  class="k-button"  name="payment_gateway" id="payment_gateway"  value="'. $paytype.'"/>
<input type="hidden"  class="k-button"  name="backend_setup_id" id="backend_setup_id"  value="'. $pguri_segment.'"/>   
<input type="hidden"  class="k-button"  name="pglco_customer_status" id="select_paymenttype"  value="'. $select_paymenttype.'"/>                                                              
<input type="submit"  class="k-button"  name="new_gateway_update" id="new_gateway_update"  value="Update"/>
							  <input type="submit" name="btnCancel" id="btnCancel" value="Cancel"/></td></tr>	
          </table>
               </form>';
           }
                                                          echo $result;
    }
    
    public function buildform($paytype,$pguri_segment,$payment_select_type){
		if(!isset($_SESSION['user_data'])) redirect(base_url());
		if($_SESSION['user_data']->pwdchangereq) redirect('welcome/changePassword');
		$this->accessPrivilage = (isset($_SESSION['access']))?$_SESSION['access']:"";
		$pgDetails = $this->backend_setup_model->getPGFormDetails($paytype,$pguri_segment,$FromCustomerPortal=0,$payment_select_type);
			if(count($pgDetails)>0){
			$requestparams =$pgDetails[0]->requestparams;
			$requestparamsarr = explode(',',$requestparams);
			for($i=0;$i<count($requestparamsarr);$i++){
				$requestparams = explode('|',$requestparamsarr[$i]);
				$tagnames[$i] = isset($requestparams[0])?$requestparams[0]:'';
				$captions[$i] = isset($requestparams[1])?$requestparams[1]:'';
				
			}
			return array('tagnames'=>$tagnames,'captions'=>$captions);
		}else {
			return array();
		}
	}
function getPackageActivationData() {
        $serviceId  = isset($_POST['service_id'])?$_POST['service_id']:0;
        $stock_id  = isset($_POST['stock_id'])?$_POST['stock_id']:0;
        $employeeId = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $dealerId   = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $customerId = isset($_POST['customerId'])?$_POST['customerId']:0;
        $cf = new CommonFunctions();
         //$result = $CustomersModel->checkServiceActivationBy($serviceId);
         $checkResponse=1;
         if($stock_id ==0)
         {
             $serviceId=$serviceId;
         }
         if($serviceId==0)
         {
             $stock_ids = explode(",",$stock_id);
         }
         else
         {
             $stock_ids = array();
         }
         
         //for($i=0;$i<count($serviceId);$i++)
         //{
            $result = $cf->checkIsEligibleUserToDeActivate($employeeId,$dealerId,$customerId,$serviceId,$stock_ids);
            //$this->db->last_query();
            //if($result==0)
            //{
                //$checkResponse=0;
                //break;
           // }
         //}
        if (!$result) {
            
                    $resultArray['check_data'] =1;
                    $resultArray['data'] = "Your higher authority has activated the package. Deactivation access has been removed";
                } else {
                   // echo "hi";
                    $resultArray['data'] = '0';
                    $resultArray['check_data'] =0;
                }
        echo json_encode($resultArray);
    }

    public function getBasePrice()
    {
    	$customerInfo = json_decode($_POST['data'],true);
    	write_to_file("In ajax/getBaseprice customerInfo".json_encode($customerInfo));
        // echo $customerInfo->reseller_id; exit;
    	$obj = new wsModel();
    	$obj1 = new DasModel();
    	$obj2 = new productsmodel();
    	$obj3 = new CustomersModel();
    	//load special charge model for NCF - Archana B 2018-12-20
        $this->load->model('special_charges_model');
        $spl_model = new special_charges_model();
        $ncf_total_amount = 0;
        $ncf_mso_share = 0;
        $ncf_lco_share = 0;
         $encf_total_amount = 0;
        $encf_mso_share = 0;
        //load special charge model for NCF - Archana B 2018-12-20
    	$employeeId = 0;
    	$dealerId = 0;
    	$basePriceAmount = array();
    	$start_dates = array();
    	$end_dates = array();
    	$stock_ids = array();
    	$quantitys = array();
    	$end_dates_view_arr = array();
    	$mso_share = 0;
    	$pend_mso_share = 0;
    	$lco_share = 0;
        $tax_amount = 0;
    	$bill_amount = 0;
    	$total_amount = 0;
        $flaot_tax1 = 0;
        $flaot_tax2 = 0;
        $flaot_tax3 = 0;
        $flaot_tax4 = 0;
        $flaot_tax5 = 0;
        $flaot_tax6 = 0;
        $float_total_tax = 0;
        $float_customer_discount=0;
        $flaot_total_amount_before_discount=0;

        //get employee id and dealer id
    	$employeeId =  $customerInfo[0]['employee_id'];
    	$reactivationReq = isset($customerInfo[1]['react'])?1:0;
    	$cid = isset($customerInfo[1]['customer_id'])?$customerInfo[1]['customer_id']:0;
        //************customer billtype condition added by venkat.
        $customer_bill_type = isset($customerInfo[2]['customer_bill_type'])?$customerInfo[2]['customer_bill_type']:0;
        $from_page_array = array('edit_service','create_caf');
        $from_page = isset($customerInfo[3]['from_page'])?$customerInfo[3]['from_page']:'';
        $int_discount_type = isset($customerInfo[4]['discount_type'])?$customerInfo[4]['discount_type']:0;
        $int_discount_val = isset($customerInfo[5]['discount_val'])?$customerInfo[5]['discount_val']:0;
        $date_discount_start_date = isset($customerInfo[6]['discount_start_date'])?$customerInfo[6]['discount_start_date']:'';
        $date_discount_end_date = isset($customerInfo[7]['discount_end_date'])?$customerInfo[7]['discount_end_date']:'';
    	unset($customerInfo[0]);
    	if($reactivationReq == 1){
    		unset($customerInfo[1]);
    	}
    	if(isset($customerInfo[1]) && $cid>0){
    		unset($customerInfo[1]);
    	}
		if(isset($customerInfo[2]) && $customer_bill_type > 0){
    		unset($customerInfo[2]);
    	}
        if(isset($customerInfo[3]) && !empty($from_page)){
    		unset($customerInfo[3]);
    	}
        if(isset($customerInfo[4]) && $int_discount_val>0){
    		unset($customerInfo[4]);
    	}
        if(isset($customerInfo[5]) && $int_discount_val>0){
    		unset($customerInfo[5]);
    	}
        if(isset($customerInfo[6]) && $int_discount_val>0){
    		unset($customerInfo[6]);
    	}
        if(isset($customerInfo[7]) && $int_discount_val>0){
    		unset($customerInfo[7]);
    	}
        log_message("debug","============customerInfo==========".$employeeId);
    	//echo"<pre>";print_r($customerInfo);
        log_message("debug","============customerInfo==========".json_encode($customerInfo));
    	//echo"<pre>";print_r($customerInfo);
	
        	
    	$statusCode = 1;
    	$statusMessage = 'No Details Found';

    	$dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
    	$eid = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
    	//start - get the NCF & ENCF special charge values - archana 2018-12-18
        //$check_ncf_end_date = ''; //get the max end date from selected prepaid packages for NCF by archana - 2018-12-19       
        $ncf_charge_details = $this->change_pass_model->getSpecialCharge('NCF',$dealerId);
        $encf_charge_details = $this->change_pass_model->getSpecialCharge('ENCF',$dealerId);
        $active_count_date = $this->change_pass_model->getLovValue('BILLING_ACTIVE_COUNT_DATE',$dealerId);
        $calender_billing = $this->change_pass_model->getLovValue('CALENDER_BILLING',$dealerId);
        $addon_after_base_pack_int = $this->change_pass_model->getLovValue('ADDON_AFTER_BASEPACK',$dealerId);
        $int_allow_mso_adj = $this->change_pass_model->getLovValue('BILL_EDIT_CUSTOMER_ADJUSTMENT',$dealerId);
        $current_day = date('d');
        $actual_bill_period_start_date = date('Y-m-01');
        $actual_bill_period_end_date = date('Y-m-t');;
        //write_to_file('$active_count_date : '.$active_count_date);
        //write_to_file('$calender_billing : '.$calender_billing);
        if($active_count_date>0 && $active_count_date<=31 && $calender_billing==1){
                             
            if($current_day<$active_count_date){
               // If the bill generating before bill bill date PRASANNA 09-04-2019
               $actual_bill_period_start_date = date("Y-m-d", strtotime("-1 month", strtotime(date('Y-m-'.$active_count_date))));
               $actual_bill_period_end_date = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-'.$active_count_date))));
               //$days_in_current_month = abs((strtotime($actual_bill_period_start_date)-strtotime($actual_bill_period_end_date))/86400)+1;
               //$bill_month = date("Y-m-01", strtotime("-1 month", strtotime(date('Y-m-'.$active_count_date))));
            }
            else{
               // If the bill generating after bill bill date PRASANNA 09-04-2019
               $actual_bill_period_start_date = date('Y-m-'.$active_count_date);
               $actual_bill_period_end_date = date("Y-m-d", strtotime('-1 day',strtotime("+1 month", strtotime(date('Y-m-'.$active_count_date)))));
               //$days_in_current_month = abs((strtotime($actual_bill_period_start_date)-strtotime($actual_bill_period_end_date))/86400)+1;
               //$bill_month = date('Y-m-01');
            }
            
        }
        $configuration_values = isset($ncf_charge_details->configuration_values)?json_decode($ncf_charge_details->configuration_values,true):array();
        $ncf_prorata = isset($configuration_values['prorata'])?$configuration_values['prorata']:0;
        log_message('debug','ncf_prorata'.$ncf_prorata);
        $ncf_display_name = isset($ncf_charge_details->display_name)?$ncf_charge_details->display_name:'Network Capacity Fee';
        $encf_display_name = isset($encf_charge_details->display_name)?$encf_charge_details->display_name:'Additional Network Capacity Fee';

        //End - get the NCF & ENCF special charge values - archana 2018-12-18
    	if($employeeId > 0 && $dealerId > 0)
    	{
    		$start_date = date('Y-m-d');
    		$quantity = 1;
			write_to_file("In ajax/getBaseprice count of customerInfo---".count($customerInfo));
    		foreach($customerInfo as $serial_nos)
            {	//loop runs for every serial number

            	write_to_file("In ajax/getBaseprice In customerInfo foreach customerInfo as serial_nos---".json_encode($serial_nos));
            	foreach($serial_nos as $serial_no => $product_ids_data){
            		if(count($product_ids_data)>0){
            		write_to_file("In ajax/getBaseprice In foreach serial_nos as serial_no---".json_encode($serial_no)."product_ids_data---".json_encode($product_ids_data));
                    if(in_array($from_page,$from_page_array))
                    {
                    write_to_file("In ajax/getBaseprice In if in_array frompage");    
                        $product_ids = array();
                        if(!is_array($product_ids_data))
                        {
                        	write_to_file("In ajax/getBaseprice In if !is_array product_ids_data");
                            continue;
                        }
                        write_to_file("In ajax/getBaseprice product_ids_data".json_encode($product_ids_data));
                        foreach($product_ids_data as $key=>$value)
                        {
                        	write_to_file("In ajax/getBaseprice value".json_encode($value));
                            foreach($value as $key1=>$value1)
                            {
                                $product_ids[]=$key1;
                                $product_ids_qty[$key1]=isset($value1['qty'])?$value1['qty']:1;
                                $product_ids_edates[$key1]=isset($value1['edates'])?$value1['edates']:'';
                                $product_ids_servicetype[$key1]=isset($value1['servicetype'])?$value1['servicetype']:1;

                            }  
                        }
                    }
                    else
                    {
                    	write_to_file("In ajax/getBaseprice In else of in_array frompage");
                        $product_ids = $product_ids_data;
                    }
                        //print_r($product_ids);
                     write_to_file("In ajax/getBaseprice product_ids".json_encode($product_ids));   
                        
            		$disc = array();
                	$disct = array();
            		if(isset($cid) && $cid>0){
            			$customer_id = $cid;
            		}else{
            		 	$customer_id = $obj->getCustomerId($serial_no,$dealerId);
            		}
	                $stock_id = $obj->getStockIds($serial_no,$dealerId);//here we are getting stock id
	                /* pending mso share */
	                if($customer_id > 0 ){
	                	$msoshare_existpkgs = $obj1->getmsoshare_existpkgs($customer_id, $int_allow_mso_adj);

	                	if(!empty($msoshare_existpkgs))
	                	{
	                		$pend_mso_share += isset($msoshare_existpkgs['tot_mso_share'])?round($msoshare_existpkgs['tot_mso_share'],2):0;
	                	}
	                }else{
	                	$pend_mso_share = 0;
	                }
	                $prds = array();
	                if($reactivationReq == 1){
	                	$srvs = array();
	                	$disc = array();
	                	$disct = array();
		                if(is_array($product_ids) || is_object($product_ids)){
		                	foreach($product_ids as $prdsrv)
		                	{
		                		$prdsrvarr = explode('#',$prdsrv);
		                		$prds[] = $prdsrvarr[0];
		                		$srvs[] = $prdsrvarr[1];

		                	}
		                }
	                	$product_ids = $prds;
	                }
	                else
	                {
	                	//echo"<pre>";print_r($product_ids);
	                	write_to_file("In ajax/getBaseprice in else of reactivationReq==1 product_ids".json_encode($product_ids)); 
	                	if(is_array($product_ids) || is_object($product_ids)){
		                	foreach($product_ids as $prddis)
		                	{
		                		$prddisarr = explode('_',$prddis);
		                		$prds[] = $prddisarr[0];
		                		$disc[] = isset($prddisarr[1])?$prddisarr[1]:'';
		                		$disct[] = isset($prddisarr[2])?$prddisarr[2]:'';

		                	}
		                }
	                	$product_ids = $prds;
	                }    
                	$prds = $product_ids;
                write_to_file("In ajax/getBaseprice before if stock_id>0 product_ids".json_encode($product_ids)); 	
                if($stock_id>0)
                {   
                    //we are preparing an array by using comma separated product_ids
                	$curent_date = date("Y-m")."-01";
                	$i = 0;
                	$j=0;
                	$selected_channels_count = 0;
                	$onetime_channels_count = 0;
                    $ncf_end_dates_array = array();
                    $productids_array = array();

                    //if addon_after_base_pack_int ==1 then check if addon package end date is more than base pack if addon pack end date is more then replace with base pack end date
                    //echo "<pre>";print_r($product_ids_edates);
                    $end_dates_replaced_arr=array();
                	if($addon_after_base_pack_int==1){
                		foreach($product_ids as $product)
	                	{
	                		$end_date_view_int = isset($product_ids_edates[$product])?$product_ids_edates[$product]:'';
	                		$end_dates_view_arr[$j]=$end_date_view_int;
	                		$stock_ids[$j]=$stock_id;
	                		$j++;
	                	}
                		$end_dates_replaced_arr=$obj2->compare_addon_base_dates($stock_ids,$prds,$end_dates_view_arr,$customer_id,$dealerId);
                	}
                        
                        //Customer discount related chnages added bY Venkat
                         //DISCOUTN RELATED CODE ADDE BY VENKAT (08-04-2020)
                            $int_new_customer_flag = 1;
                            if($customer_id > 0){
                              $int_new_customer_flag = 0;  
                            }
                          $arr_discount_info = array('discount_type'=>$int_discount_type,
                                          'discount_val'=>$int_discount_val,
                                          'discount_start_date'=>$date_discount_start_date,
                                          'discount_end_date'=>$date_discount_end_date,
                                          'new_customer_flag'=>$int_new_customer_flag);
                         $int_enable_stb_discount = isset($_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT) ? $_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT : 0;
                         $stock_ids = array();
                         $arr_discount_type = array();
                         $arr_discount_value = array();
                	//echo "<pre>";print_r($end_dates_replaced_arr);exit;
                    //get the max end date from selected prepaid packages for NCF by archana - 2018-12-19
                	foreach($product_ids as $product)
                	{
						$quantity = isset($product_ids_qty[$product])?$product_ids_qty[$product]:1;
						$serviceType = isset($product_ids_servicetype[$product])?$product_ids_servicetype[$product]:1;
						if(isset($end_dates_replaced_arr[$product]) && $end_dates_replaced_arr[$product]!=''){
							$end_date_view = $end_dates_replaced_arr[$product];
						}else{
							$end_date_view = isset($product_ids_edates[$product])?$product_ids_edates[$product]:'';
						}
                        
						$product_details = $obj2->getservice($product,$dealerId);
                		write_to_file("In ajax/getbaseprice product===".$product."end_date_view===".$end_date_view);
                		//$bill_exist = $obj1->isBillExistForPeriod(date('Y-m-d'),$product_details[0]->product_id,$customer_id,0,$intra_lco_customer_id=0,$product_details[0]->pricing_structure_type, $stock_id );
                		if(in_array($from_page,$from_page_array)) //from createcaf and edit services page
	                    {
	                    	if($serviceType==2 || $serviceType==3 || $product_details[0]->pricing_structure_type==1){
                                    $end_date=$end_date_view;
	                        }else{
	                			$end_date = $obj3->quantity_enddate($quantity,$product,$product_details[0]->pricing_structure_type,$product_details[0]->validity_days,$product_details[0]->validity_days_type_id,$stock_id,$start_date);
	                	    }
	                    }else{ //from temporary deactivation
	                    	$end_date = $obj3->quantity_enddate($quantity,$product,$product_details[0]->pricing_structure_type,$product_details[0]->validity_days,$product_details[0]->validity_days_type_id,$stock_id,$start_date);
	                    }
                		
                	    write_to_file("In ajax/getbaseprice product===".$product."end_date===".$end_date."actual_bill_period_end_date====".$actual_bill_period_end_date."producttype===".$product_details[0]->pricing_structure_type);
						if($end_date>$actual_bill_period_end_date && $product_details[0]->pricing_structure_type==2){
							$end_date = $actual_bill_period_end_date;
						}
						//Get bill period start date PRASANNA 09-08-2019
						$bill_period_start = $obj1->getBillPeriodStartdate($start_date,$end_date,$product_details[0]->product_id,$customer_id,0,$intra_lco_customer_id=0,$product_details[0]->pricing_structure_type, $stock_id);
						if($bill_period_start=='')
                		{
                			unset($prds[$i]);
                		} 
						else{
							$end_dates[$i]=$end_date;
                            $stock_ids[$i]=$stock_id;
                            $quantitys[$i]=$quantity;
                            $start_dates[$i]=$bill_period_start;
							//array_push($start_dates,$bill_period_start);
                		}
						
                		//start - condition added by archana to get the selected prepaid packages end dates for NCF by archana 2018-12-19 ->bill_type
                		array_push($productids_array,$product_details[0]->product_id);
                        $ncf_end_date = $spl_model->get_product_ncf_end_date($end_date,$product_details[0]->pricing_structure_type,$customer_bill_type,$ncf_prorata, $active_count_date, $calender_billing);
                        if($ncf_end_date != ''){
                            array_push($ncf_end_dates_array,$ncf_end_date);
                        }

                        log_message('debug','ncf_end_dates_array'.json_encode($ncf_end_dates_array));


                        $product_channels_count = ($product_details[0]->sd_channels_count + (2*$product_details[0]->hd_channels_count)); // 1 HD channel = 2 SD channels 
                        $selected_channels_count += $product_channels_count;
                        if($product_details[0]->pricing_structure_type == 1){
                        	$onetime_channels_count += $product_channels_count;
                        }
                        
                        
                        
                         $arr_discount_details = $obj1->getDiscount(0, $int_enable_stb_discount,$customer_id, $stock_id, $dealerId, $discount_value_int = 0, $discount_type_int = 0,$product,$employeeId,$customer_discount_type=1,$bill_date='',$arr_discount_info);
                         
                         $float_get_discount = (isset($arr_discount_details) && $arr_discount_details['discount_val']) ? $arr_discount_details['discount_val'] : 0;
                         $int_type_of_discount = (isset($arr_discount_details) && $arr_discount_details['type_of_discount']) ? $arr_discount_details['type_of_discount'] : 1;
              
                          $arr_discount_type[$i] = $int_type_of_discount;
                          $arr_discount_value[$i] = $float_get_discount;
                       
                		$i++;
                	}

                    //for getting lco share,mso share,total bill amounts


                	if(count($disc)>0)
                	{
                		$dis_counter = 0;
                		foreach($disc as $d)
                		{
                			if($disct[$dis_counter] == 'per')
                			{
                				$disct[$dis_counter] = 1;
                			}
                			elseif($disct[$dis_counter] == 'rs')
                			{
                				$disct[$dis_counter] = 2;

                			}
                		}
                		$dis_counter++;
                	}
                        //echo "before calling getBasePriceAmount prds".json_encode($prds)."employeeId".$employeeId."quantitys".json_encode($quantitys)."stock_ids".json_encode($stock_ids)."start_dates".json_encode($start_dates)."end_dates".json_encode($end_dates)."customer_id".json_encode($customer_id);
                        $get_extra_params['active_count_date']=$active_count_date;
                        $get_extra_params['calender_billing']=$calender_billing;
                       write_to_file("In ajax/getbaseprice before calling getBasePriceAmount prds".json_encode($prds)."employeeId".$employeeId."quantitys".json_encode($quantitys)."stock_ids".json_encode($stock_ids)."start_dates".json_encode($start_dates)."end_dates".json_encode($end_dates)."customer_id".json_encode($customer_id));
                	$basePriceAmounts = $obj1->getBasePriceAmount($prds,$employeeId,$quantitys,$stock_ids,$start_dates,$end_dates,$customer_id,$get_bill_amount=1,$csid=0,$dealerId,$get_extra_params,$arr_discount_type,$arr_discount_value,$serviceExtWithLcoDeposit=0,$customer_bill_type);
                	$mso_share += isset($basePriceAmounts['mso_share'])?round($basePriceAmounts['mso_share'],2):0;
                        $lco_share += isset($basePriceAmounts['lco_share'])?round($basePriceAmounts['lco_share'],2):0;

                        $tax_amount += isset($basePriceAmounts['tax_amount'])?round($basePriceAmounts['tax_amount'],2):0;
                        $bill_amount += isset($basePriceAmounts['bill_amount'])?round($basePriceAmounts['bill_amount'],2):0;

                	$total_amount += isset($basePriceAmounts['total_amount'])?round($basePriceAmounts['total_amount'],2):0;
                         //IND TAX CODE ADDED BY VENKAT (21-10-2019)
                        $flaot_tax1 += isset($basePriceAmounts['mso_tax1'])?round($basePriceAmounts['mso_tax1'],2):0;
                        $flaot_tax2 += isset($basePriceAmounts['mso_tax2'])?round($basePriceAmounts['mso_tax2'],2):0;
                        $flaot_tax3 += isset($basePriceAmounts['mso_tax3'])?round($basePriceAmounts['mso_tax3'],2):0;
                        $flaot_tax4 += isset($basePriceAmounts['mso_tax4'])?round($basePriceAmounts['mso_tax4'],2):0;
                        $flaot_tax5 += isset($basePriceAmounts['mso_tax5'])?round($basePriceAmounts['mso_tax5'],2):0;
                        $flaot_tax6 += isset($basePriceAmounts['mso_tax6'])?round($basePriceAmounts['mso_tax6'],2):0;
                        
                        $float_customer_discount += isset($basePriceAmounts['customer_discount'])?round($basePriceAmounts['customer_discount'],2):0;
                        $flaot_total_amount_before_discount += isset($basePriceAmounts['total_amount_before_discount'])?round($basePriceAmounts['total_amount_before_discount'],2):0;
                        
                	//start - calculate NCF estimate values archana 2018-12-20
                    if(count($ncf_end_dates_array) > 0){  //condition for checking slab type max value                                     
                      $check_ncf_bill_exist = $spl_model->calculate_ncf_encf($customer_id,$dealerId,$stock_id,$ncf_end_dates_array,$productids_array,$selected_channels_count,$eid,$for_estimation=1,$cron_bill_date='',$from_postpaid_cron=0,$billing_cron=0,$onetime_channels_count);
                       log_message('debug','check_ncf_bill_exist'.json_encode($check_ncf_bill_exist));
                      if(count($check_ncf_bill_exist) > 0){
                        $ncf_total_amount += isset($check_ncf_bill_exist['total_amount'])?round($check_ncf_bill_exist['total_amount'],2):0;
                        $ncf_mso_share += isset($check_ncf_bill_exist['mso_share'])?round($check_ncf_bill_exist['mso_share'],2):0;       
                        $encf_total_amount += isset($check_ncf_bill_exist['encf_total_amt'])?round($check_ncf_bill_exist['encf_total_amt'],2):0;
                        $encf_mso_share += isset($check_ncf_bill_exist['encf_mso_share'])?round($check_ncf_bill_exist['encf_mso_share'],2):0;                                       
                      }

                      log_message('debug','ncf_total_amount'.json_encode($ncf_total_amount));

                    }
                    //end - calculate NCF estimate values archana 2018-12-20
                	$stock_ids = array();

                	$statusCode = 0;
                	$statusMessage = 'Success';
                }
                else{//if invalid stb entered
                	$statusCode = 0;
                	$statusMessage = 'Success';
                	$mso_share = $mso_share + 0;
                	$pending_mso_share = $pend_mso_share + 0;
                	$lco_share = $lco_share + 0;
                	$total_amount = $total_amount + 0;
                }
                }else{
                    $statusCode = 0;
                	$statusMessage = 'Success';
                	$mso_share = $mso_share + 0;
                	$pending_mso_share = $pend_mso_share + 0;
                	$lco_share = $lco_share + 0;
                	$tax_amount = $tax_amount + 0;
                	$bill_amount = $bill_amount + 0;
                	$total_amount = $total_amount + 0;
                }
            }
        }
        $float_total_tax = $flaot_tax1+$flaot_tax2+$flaot_tax3+$flaot_tax4+$flaot_tax5+$flaot_tax6;
        //write_to_file('$total_amount : '.$total_amount);
        log_message("debug","==========ncf_mso_share===============".$ncf_mso_share);
        log_message("debug","==========encf_mso_share===============".$encf_mso_share);
        $basePriceAmount = array('lco_share'=>$lco_share,'mso_share'=>($mso_share+$ncf_mso_share+$encf_mso_share),'tax_amount'=>$tax_amount,'bill_amount'=>$bill_amount,'total_amount'=>$total_amount, 'pending_mso_share' => $pend_mso_share, 'total_ncf_encf'=>$ncf_total_amount, 'encf_total_amount'=>$encf_total_amount, 'ncf_display_name'=>$ncf_display_name, 'encf_display_name'=>$encf_display_name,'tax1'=>$flaot_tax1,'tax2'=>$flaot_tax2,'tax3'=>$flaot_tax3,'tax4'=>$flaot_tax4,'tax5'=>$flaot_tax5,'tax6'=>$flaot_tax6,'total_tax'=>$float_total_tax,'customer_discount'=>$float_customer_discount,'total_amount_before_discount'=>$flaot_total_amount_before_discount);
        log_message("debug","==========basePriceAmountAAAAAAAA===============".json_encode($basePriceAmount));
    }
    else
    {
    	$statusCode = 1;
    	$statusMessage = 'Dealer or Employee does not exist';
    }       
    echo json_encode (array('statusCode'=>$statusCode,'statusMessage'=>$statusMessage,'basePrice'=>(object)$basePriceAmount)); exit;
}


public function getBasePrice_extension()
    {
    	$customerInfo = json_decode($_POST['data'],true);
        $obj = new wsModel();
    	$obj1 = new DasModel();
    	$obj2 = new productsmodel();
    	$obj3 = new CustomersModel();
    	//load special charge model for NCF - Archana B 2018-12-20
        $this->load->model('special_charges_model');
        $spl_model = new special_charges_model();
        $ncf_total_amount = 0;
        $ncf_mso_share = 0;
        $ncf_lco_share = 0;
         $encf_total_amount = 0;
        $encf_mso_share = 0;
        //load special charge model for NCF - Archana B 2018-12-20
    	$employeeId = 0;
    	$dealerId = 0;
    	$basePriceAmount = array();
    	$start_dates = array();
    	$end_dates = array();
    	$stock_ids = array();
    	$quantitys = array();
    	$mso_share = 0;
    	$pend_mso_share = 0;
    	$lco_share = 0;
    	$tax_amount = 0;
        $bill_amount = 0;
    	$total_amount = 0;
        $flaot_tax1 = 0;
        $flaot_tax2 = 0;
        $flaot_tax3 = 0;
        $flaot_tax4 = 0;
        $flaot_tax5 = 0;
        $flaot_tax6 = 0;
        $float_total_tax = 0;
        $float_customer_discount =0;
        $flaot_total_amount_before_discount = 0;
        //get employee id and dealer id
    	$employeeId =  $customerInfo[0]['employee_id'];
    	$reactivationReq = isset($customerInfo[1]['react'])?1:0;
    	$cid = isset($customerInfo[1]['customer_id'])?$customerInfo[1]['customer_id']:0;
        //************customer billtype condition added by venkat.
        $customer_bill_type = isset($customerInfo[2]['customer_bill_type'])?$customerInfo[2]['customer_bill_type']:0;
        $start_date = isset($customerInfo[3]['start_date'][0])?$customerInfo[3]['start_date'][0]:'';
        $post_enddate = isset($customerInfo[4]['end_date'][0])?$customerInfo[4]['end_date'][0]:'';
        $quantity = isset($customerInfo[5]['quantity'][0])?$customerInfo[5]['quantity'][0]:'';

    	unset($customerInfo[0]);
    	if($reactivationReq == 1){
    		unset($customerInfo[1]);
    	}
    	if(isset($customerInfo[1]) && $cid>0){
    		unset($customerInfo[1]);
    	}
		if(isset($customerInfo[2]) && $customer_bill_type > 0){
    		unset($customerInfo[2]);
    	}
    	if(isset($customerInfo[3]) && $start_date !=''){
    		unset($customerInfo[3]);
    	}
        if(isset($customerInfo[4]) && $post_enddate !=''){
    		unset($customerInfo[4]);
    	}
        if(isset($customerInfo[5]) && $quantity !=''){
    		unset($customerInfo[5]);
    	}

    	//echo"<pre>";print_r($customerInfo);
		
    	$statusCode = 1;
    	$statusMessage = 'No Details Found';

    	$dealerId = $_SESSION['user_data']->dealer_id;
    	$eid = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
    	//start - get the NCF & ENCF special charge values - archana 2018-12-18
        //$check_ncf_end_date = ''; //get the max end date from selected prepaid packages for NCF by archana - 2018-12-19       
        $ncf_charge_details = $this->change_pass_model->getSpecialCharge('NCF',$dealerId);
        $encf_charge_details = $this->change_pass_model->getSpecialCharge('ENCF',$dealerId);
        $active_count_date = $this->change_pass_model->getLovValue('BILLING_ACTIVE_COUNT_DATE',$dealerId);
        $calender_billing = $this->change_pass_model->getLovValue('CALENDER_BILLING',$dealerId);
        $int_allow_mso_adj = $this->change_pass_model->getLovValue('BILL_EDIT_CUSTOMER_ADJUSTMENT',$dealerId);
        
        $configuration_values = isset($ncf_charge_details->configuration_values)?json_decode($ncf_charge_details->configuration_values,true):array();
        $ncf_prorata = isset($configuration_values['prorata'])?$configuration_values['prorata']:0;
        log_message('debug','ncf_prorata'.$ncf_prorata);
        $ncf_display_name = isset($ncf_charge_details->display_name)?$ncf_charge_details->display_name:'Network Capacity Fee';
        $encf_display_name = isset($encf_charge_details->display_name)?$encf_charge_details->display_name:'Additional Network Capacity Fee';

        //End - get the NCF & ENCF special charge values - archana 2018-12-18
    	if($employeeId > 0 && $dealerId > 0)
    	{
    		$start_date=date('Y-m-d', strtotime("+1 day",strtotime($start_date)));
	
    		foreach($customerInfo as $serial_nos)
            {	//loop runs for every serial number

            	foreach($serial_nos as $serial_no => $product_ids){
            		$disc = array();
                	$disct = array();
            		if(isset($cid) && $cid>0){
            			$customer_id = $cid;
            		}else{
            		 	$customer_id = $obj->getCustomerId($serial_no,$dealerId);
            		}
	                $stock_id = $obj->getStockIds($serial_no,$dealerId);//here we are getting stock id
	                /* pending mso share */
	                if($customer_id > 0 ){
	                	$msoshare_existpkgs = $obj1->getmsoshare_existpkgs($customer_id, $int_allow_mso_adj);

	                	if(!empty($msoshare_existpkgs))
	                	{
	                		$pend_mso_share += isset($msoshare_existpkgs['tot_mso_share'])?round($msoshare_existpkgs['tot_mso_share'],2):0;
	                	}
	                }else{
	                	$pend_mso_share = 0;
	                }
	                $prds = array();
	                if($reactivationReq == 1){
	                	$srvs = array();
	                	$disc = array();
	                	$disct = array();
		                if(is_array($product_ids) || is_object($product_ids)){
		                	foreach($product_ids as $prdsrv)
		                	{
		                		$prdsrvarr = explode('#',$prdsrv);
		                		$prds[] = $prdsrvarr[0];
		                		$srvs[] = $prdsrvarr[1];

		                	}
		                }
	                	$product_ids = $prds;
	                }
	                else
	                {
	                	if(is_array($product_ids) || is_object($product_ids)){
		                	foreach($product_ids as $prddis)
		                	{
		                		$prddisarr = explode('_',$prddis);
		                		$prds[] = $prddisarr[0];
		                		$disc[] = isset($prddisarr[1])?$prddisarr[1]:'';
		                		$disct[] = isset($prddisarr[2])?$prddisarr[2]:'';

		                	}
		                }
	                	$product_ids = $prds;
	                }    
                	$prds = $product_ids;

                if($stock_id>0)
                {   
                    //we are preparing an array by using comma separated product_ids
                	$curent_date = date("Y-m")."-01";
                	$i = 0;
                	$selected_channels_count = 0;
                	$onetime_channels_count = 0;
                    $ncf_end_dates_array = array();
                    $productids_array = array();
                    $arr_discount_type = array();
                $arr_discount_value = array();
                $int_enable_stb_discount = isset($_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT) ? $_SESSION['dealer_setting']->ENABLE_STB_DISCOUNT : 0;
                    //get the max end date from selected prepaid packages for NCF by archana - 2018-12-19
                	foreach($product_ids as $product)
                	{
                        
						$product_details = $obj2->getservice($product,$dealerId);
                		//$bill_exist = $obj1->isBillExistForPeriod(date('Y-m-d'),$product_details[0]->product_id,$customer_id,0,$intra_lco_customer_id=0,$product_details[0]->pricing_structure_type, $stock_id );
                		$end_date = $obj3->quantity_enddate($quantity,$product,$product_details[0]->pricing_structure_type,$product_details[0]->validity_days,$product_details[0]->validity_days_type_id,$stock_id,$start_date);
                		$bill_period_start = $obj1->getBillPeriodStartdate($start_date,$end_date,$product_details[0]->product_id,$customer_id,0,$intra_lco_customer_id=0,$product_details[0]->pricing_structure_type, $stock_id);
						if($bill_period_start=='')
                		{
                			unset($prds[$i]);
                		} 
						else{
							$end_dates[$i]=$end_date;
							$stock_ids[$i]=$stock_id;
							$quantitys[$i]=$quantity;
							$start_dates[$i]=$bill_period_start;
							//array_push($start_dates,$bill_period_start);
                		}
						
						
						
						
                		//start - condition added by archana to get the selected prepaid packages end dates for NCF by archana 2018-12-19 ->bill_type
                		array_push($productids_array,$product_details[0]->product_id);
                        $ncf_end_date = $spl_model->get_product_ncf_end_date($end_date,$product_details[0]->pricing_structure_type,$customer_bill_type,$ncf_prorata, $active_count_date, $calender_billing);
                        if($ncf_end_date != ''){
                            array_push($ncf_end_dates_array,$ncf_end_date);
                        }

                        log_message('debug','ncf_end_dates_array'.json_encode($ncf_end_dates_array));


                        $product_channels_count = ($product_details[0]->sd_channels_count + (2*$product_details[0]->hd_channels_count)); // 1 HD channel = 2 SD channels 
                        $selected_channels_count += $product_channels_count;
                        if($product_details[0]->pricing_structure_type == 1){
                        	$onetime_channels_count += $product_channels_count;
                        }

                        
                        //End - condition added by archana to get the selected prepaid packages end dates for NCF by archana 2018-12-19
                         $arr_discount_details = $obj1->getDiscount(0, $int_enable_stb_discount,$customer_id, $stock_id, $dealerId, $discount_value_int = 0, $discount_type_int = 0,$product,$employeeId,$customer_discount_type=1,$bill_date='');
                         
                        $float_get_discount = (isset($arr_discount_details) && $arr_discount_details['discount_val']) ? $arr_discount_details['discount_val'] : 0;
                        $int_type_of_discount = (isset($arr_discount_details) && $arr_discount_details['type_of_discount']) ? $arr_discount_details['type_of_discount'] : 1;

                         $arr_discount_type[$i] = $int_type_of_discount;
                         $arr_discount_value[$i] = $float_get_discount;
                		$i++;
                	}

                    //for getting lco share,mso share,total bill amounts


                	if(count($disc)>0)
                	{
                		$dis_counter = 0;
                		foreach($disc as $d)
                		{
                			if($disct[$dis_counter] == 'per')
                			{
                				$disct[$dis_counter] = 1;
                			}
                			elseif($disct[$dis_counter] == 'rs')
                			{
                				$disct[$dis_counter] = 2;

                			}
                		}
                		$dis_counter++;
                	}
                        //echo "before calling getBasePriceAmount prds".json_encode($prds)."employeeId".$employeeId."quantitys".json_encode($quantitys)."stock_ids".json_encode($stock_ids)."start_dates".json_encode($start_dates)."end_dates".json_encode($end_dates)."customer_id".json_encode($customer_id);
                        $get_extra_params['active_count_date']=$active_count_date;
                        $get_extra_params['calender_billing']=$calender_billing;
                	$basePriceAmounts = $obj1->getBasePriceAmount($prds,$employeeId,$quantitys,$stock_ids,$start_dates,$end_dates,$customer_id,$get_bill_amount=1,$csid=0,$dealerId,$get_extra_params,$arr_discount_type,$arr_discount_value,$serviceExtWithLcoDeposit=0,$customer_bill_type);
                	$mso_share += isset($basePriceAmounts['mso_share'])?round($basePriceAmounts['mso_share'],2):0;
                    $lco_share += isset($basePriceAmounts['lco_share'])?round($basePriceAmounts['lco_share'],2):0;
                    $tax_amount += isset($basePriceAmounts['tax_amount'])?round($basePriceAmounts['tax_amount'],2):0;
                    $bill_amount += isset($basePriceAmounts['bill_amount'])?round($basePriceAmounts['bill_amount'],2):0;
	
                	$total_amount += isset($basePriceAmounts['total_amount'])?round($basePriceAmounts['total_amount'],2):0;
                        
                        //This code added by venkat (21-10-2019)
                        $flaot_tax1 += isset($basePriceAmounts['mso_tax1'])?round($basePriceAmounts['mso_tax1'],2):0;
                        $flaot_tax2 += isset($basePriceAmounts['mso_tax2'])?round($basePriceAmounts['mso_tax2'],2):0;
                        $flaot_tax3 += isset($basePriceAmounts['mso_tax3'])?round($basePriceAmounts['mso_tax3'],2):0;
                        $flaot_tax4 += isset($basePriceAmounts['mso_tax4'])?round($basePriceAmounts['mso_tax4'],2):0;
                        $flaot_tax5 += isset($basePriceAmounts['mso_tax5'])?round($basePriceAmounts['mso_tax5'],2):0;
                        $flaot_tax6 += isset($basePriceAmounts['mso_tax6'])?round($basePriceAmounts['mso_tax6'],2):0;
                        
                        
                        $float_customer_discount += isset($basePriceAmounts['customer_discount'])?round($basePriceAmounts['customer_discount'],2):0;
                $flaot_total_amount_before_discount += isset($basePriceAmounts['total_amount_before_discount'])?round($basePriceAmounts['total_amount_before_discount'],2):0;
                
                        
                	//start - calculate NCF estimate values archana 2018-12-20
                    if(count($ncf_end_dates_array) > 0){  //condition for checking slab type max value                                     
                      $check_ncf_bill_exist = $spl_model->calculate_ncf_encf($customer_id,$dealerId,$stock_id,$ncf_end_dates_array,$productids_array,$selected_channels_count,$eid,$for_estimation=1,$cron_bill_date='',$from_postpaid_cron=0,$billing_cron=0,$onetime_channels_count);
                       log_message('debug','check_ncf_bill_exist'.json_encode($check_ncf_bill_exist));
                      if(count($check_ncf_bill_exist) > 0){
                        $ncf_total_amount += isset($check_ncf_bill_exist['total_amount'])?round($check_ncf_bill_exist['total_amount'],2):0;
                        $ncf_mso_share += isset($check_ncf_bill_exist['mso_share'])?round($check_ncf_bill_exist['mso_share'],2):0;       
                        $encf_total_amount += isset($check_ncf_bill_exist['encf_total_amt'])?round($check_ncf_bill_exist['encf_total_amt'],2):0;
                        $encf_mso_share += isset($check_ncf_bill_exist['encf_mso_share'])?round($check_ncf_bill_exist['encf_mso_share'],2):0;                                       
                      }

                      log_message('debug','ncf_total_amount'.json_encode($ncf_total_amount));

                    }
                    //end - calculate NCF estimate values archana 2018-12-20
                	$stock_ids = array();

                	$statusCode = 0;
                	$statusMessage = 'Success';
                }
                else{//if invalid stb entered
                	$statusCode = 0;
                	$statusMessage = 'Success';
                	$mso_share = $mso_share + 0;
                	$pending_mso_share = $pend_mso_share + 0;
                	$lco_share = $lco_share + 0;
                	$tax_amount = $tax_amount + 0;
                	$bill_amount = $bill_amount + 0;
                	$total_amount = $total_amount + 0;
                }
            }
        }
        $float_total_tax = $flaot_tax1+$flaot_tax2+$flaot_tax3+$flaot_tax4+$flaot_tax5+$flaot_tax6;
        //write_to_file('$total_amount : '.$total_amount);
        //total_amount_before_discount ADDED BY VENKAT
        $basePriceAmount = array('lco_share'=>$lco_share,'mso_share'=>($mso_share+$ncf_mso_share+$encf_mso_share),'tax_amount'=>$tax_amount,'bill_amount'=>$bill_amount,'total_amount'=>$total_amount, 'pending_mso_share' => $pend_mso_share, 'total_ncf_encf'=>$ncf_total_amount, 'encf_total_amount'=>$encf_total_amount, 'ncf_display_name'=>$ncf_display_name, 'encf_display_name'=>$encf_display_name,'tax1'=>$flaot_tax1,'tax2'=>$flaot_tax2,'tax3'=>$flaot_tax3,'tax4'=>$flaot_tax4,'tax5'=>$flaot_tax5,'tax6'=>$flaot_tax6,'total_tax'=>$float_total_tax,'customer_discount'=>$float_customer_discount,'total_amount_before_discount'=>$flaot_total_amount_before_discount);
    }
    else
    {
    	$statusCode = 1;
    	$statusMessage = 'Dealer or Employee does not exist';
    }       
    echo json_encode (array('statusCode'=>$statusCode,'statusMessage'=>$statusMessage,'basePrice'=>(object)$basePriceAmount)); exit;
}
	
	   //function to get uploaded document of customer in customer list pop up - riya
    public function getdoc_details()
    {
        $html = '';
        $customer_id = $_POST['customer_id'];
        $reseller_businessname = $_POST['reseller_businessname'];
        $cusdetails = $this->db->query("select first_name,caf_no,business_name,mobile_no from customer where customer_id = $customer_id");
        $cusdetails = $cusdetails->result();
        $html .= "<b>Name    : </b>".$cusdetails[0]->first_name."<br/><br/>";
        $html .= "<b>LCO    : </b>".$reseller_businessname."<br/><br/>";
        $html .= "<b>Caf Number           : </b>".$cusdetails[0]->caf_no."<br/><br/>";
        $html .= "<b>Mobile Number           : </b>".$cusdetails[0]->mobile_no."<br/><br/>";
        $html .= "<b>Document details : </b><br/>";
        $query = $this->db->query("select distinct ecd.doc_type,eid.type from eb_customer_documents ecd inner join eb_id_types eid on ecd.doc_type = eid.id_type_id where customer_id = $customer_id and eid.customer_status = 1 and eid.req_for_customer = 1");
         if ($query && $query->num_rows() > 0) {
               foreach ($query->result() as $record) {
                   $html .=  "<label>".$record->type."</label>";
                   $html .= "<br/>";
               }
         }
         else
         {
             $html .= "No Documents Found";
         }
        
             echo $html;
         
         
    }
	
    
     //function to get uploaded document of customer in customer list pop up - riya



     //To get autopopulated list added by pardhu
    function getlcocodelist($lco_location_id=0)
    {
        $result=array();
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $usersname = (isset($_POST['usersname']))?$_POST['usersname']:"";
        $usersname=trim($usersname);
        $user_type = (isset($_POST['users_type']))?$_POST['users_type']:-1;
        $is_location_id = isset($_POST['is_location_id'])?$_POST['is_location_id']:'0';
        $attr_flag=isset($_POST['attr_flag'])?$_POST['attr_flag']:'0';
        $selectedEmployeeId = isset($_POST['selectedEmployeeId'])?$_POST['selectedEmployeeId']:0;
        $status = isset($_POST['status'])?$_POST['status']:'1';
        $vals="";
        
         if($user_type=="DEALER" || $user_type=='EMPLOYEE' || $user_type=='ADMIN' || $user_type=='SERVICE' || $user_type=='SALES' || $user_type=='BROADCASTER' || $user_type=="OUTLET"){
           // is_location_id =1 and except users_type="outlet"  
           if(1==$is_location_id && $user_type!="OUTLET"){
                $result = $this->generic_users_model->getDealerLocations_businessname($dealer_id,$usersname);
                foreach($result as $users){
                    $vals.='<option   users_type="'.$user_type.'" reseller_id="'.$users->location_id.'" value="'.$users->location_name.'"></option>';
                }
            }else {
            $result = $this->generic_users_model->getDealerDetails_businessname($user_type,$dealer_id,$usersname);
            foreach($result as $users){
                    $vals.='<option   users_type="'.$user_type.'" reseller_id="'.$users->employee_id.'" value="'.$users->first_name.' '.$users->last_name.'"></option>';
                }
            }
         //reseller employee ,distributor employee,subdistributor employee
        }elseif($user_type!='RESELLER' && $user_type!='DISTRIBUTOR' && $user_type!='SUBDISTRIBUTOR'){
            $details = $this->generic_users_model->get_employeewise_details_businessname($user_type,$dealer_id,$usersname);
            foreach($details as $row){
                 $code=$row->parent_code;
                     $business_name = trim($row->name);
                     $uname = trim($row->parent_business_name); 
                     $uname_employee=$row->first_name.' '.$row->last_name.'('.$code.')';
                     $sel_val = $row->employee_id;
                     $vals.='<option lco_code="" users_type="'.$user_type.'" reseller_id="'.$sel_val.'" value="'.$uname_employee.'"></option>';
                }
        }else{
        //reseller  ,distributor ,subdistributor 
        $result = $this->generic_users_model->getLcoDetails_businessname($user_type,$dealer_id,$status,$usersname);
        foreach($result as $users){
                     $code=$users->dist_subdist_lcocode;
                     $business_name = trim($users->business_name);
                     $uname = trim($users->first_name." ".$users->last_name);   
                     $employee_id = $users->employee_id; 
                     if(1==$is_location_id){
                     $sel_val = $users->location_id;
                     } else {
                     $sel_val = $users->employee_id;
                     }
                    $vals.='<option   lco_code="'.$code.'" users_type="'.$user_type.'" reseller_id="'.$sel_val.'" value="'.$business_name.'('.$uname.')'.'"></option>';
            }  
        } 
        //echo $this->db->last_query();
        echo $vals;
    }
    //genaric search filter added by pardhu
    public function getUserDetails_business_name()
    {
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $code= isset($_POST['code'])?$_POST['code']:'';
        $attr_flag=isset($_POST['attr_flag'])?$_POST['attr_flag']:'0';
        $is_location_id = isset($_POST['is_location_id'])?$_POST['is_location_id']:'0';
        $user_type = (isset($_POST['users_type']))?$_POST['users_type']:-1;
        $details = $this->generic_users_model->getEmployeeDetails_businessname($code,$dealer_id,$user_type);
        //echo $this->db->last_query();
        $val="";
        if(count($details)>0){
                //checks location is 1 then load location_id in reseller id
                if($is_location_id==1){
                    $res_id=$details->location_id;
                }else{
                    $res_id=$details->employee_id;
                }
                $val .=$details->business_name. "(".$details->first_name." ".$details->last_name.")";
                $val .='**';
                $val .=$details->users_type;
                $val .='**';
                $val .=$res_id;
        }
            echo $val;
    }
    //session selection options loading added by pardhu
      public function getUserDetails_users_lco_code_employee_id()
    {
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $selectedEmployeeid= isset($_POST['selectedEmployeeid'])?$_POST['selectedEmployeeid']:'';
        $selectedUser= isset($_POST['selectedUser'])?$_POST['selectedUser']:'';
        $attr_flag=isset($_POST['attr_flag'])?$_POST['attr_flag']:'0';
        $is_location_id = isset($_POST['is_location_id'])?$_POST['is_location_id']:'0';
        $val="";
         if($selectedUser=="DEALER" || $selectedUser=='EMPLOYEE' || $selectedUser=='ADMIN' || $selectedUser=='SERVICE' || $selectedUser=='SALES' || $selectedUser=='BROADCASTER' || $selectedUser=="OUTLET"){
            if(1==$is_location_id && $selectedUser!="OUTLET"){
                    $details = $this->generic_users_model->getDealerLocations_businessname($dealer_id,$usersname="",$selectedEmployeeid);
            }else{
                $details = $this->generic_users_model->getDealerDetails_businessname($selectedUser,$dealer_id,$usersname="",$selectedEmployeeid);
            }
        }elseif($selectedUser!='RESELLER' && $selectedUser!='DISTRIBUTOR' && $selectedUser!='SUBDISTRIBUTOR'){
            $details = $this->generic_users_model->get_employeewise_details_businessname($selectedUser,$dealer_id,$usersname="",$selectedEmployeeid);
        }else{
            $details = $this->generic_users_model->getUserDetails_users_lco_code_employee_id($selectedEmployeeid,$dealer_id,$is_location_id);
        }
        //echo $this->db->last_query();
        if(count($details)>0){
            if($selectedUser=="DEALER" || $selectedUser=='EMPLOYEE' || $selectedUser=='ADMIN' || $selectedUser=='SERVICE' || $selectedUser=='SALES' || $selectedUser=='BROADCASTER' || $selectedUser=="OUTLET"){
                foreach($details as $detail){
                    $val .=$selectedUser;
                    $val .='**';
                    $val .='**';
                    if(1==$is_location_id && $selectedUser!="OUTLET"){
                         $val .=$detail->location_id;
                    }else{
                        $val .=$detail->employee_id;
                    }
                    $val .='**';
                    if(1==$is_location_id && $selectedUser!="OUTLET"){
                        $val .=$detail->location_name;
                    }else{
                        $val .=$detail->first_name.' '.$detail->last_name;
                    }
                }
            }elseif($selectedUser!='RESELLER' && $selectedUser!='DISTRIBUTOR' && $selectedUser!='SUBDISTRIBUTOR'){
                foreach($details as $detail){

                    $val .=$selectedUser;
                    $val .='**';
                    $val .='**';
                    $val .=$detail->employee_id;
                    $val .='**';
                    $code=$detail->parent_code;
                    $uname_employee=$detail->first_name.' '.$detail->last_name.'('.$code.')';
                    $val .=$uname_employee;
                }
            }else{
                if($is_location_id==1){
                    $res_id=$details->location_id;
                }else{
                    $res_id=$details->employee_id;
                }
                $val .=$selectedUser;
                $val .='**';
                $val .=$details->dist_subdist_lcocode;
                $val .='**';
                $val .=$res_id;
                $val .='**';
                $val .=$details->business_name. "(".$details->first_name." ".$details->last_name.")";
            }

        }
            echo $val;
    }
	
	 //get product deatils for approval purpose added by Ravula
      public function getproduct_detailsforapprove(){
        log_message('debug', 'ajax/getproduct_detailsforapprove controller function initialized');
        $data = "";
        $prod_Id = $this->input->post('product_id');
        $result = $this->productsmodel->get_productsforall($prod_Id);//for product details 
        if (isset($result) && count($result) > 0) {
            $pname=isset($result[0]->pname)?$result[0]->pname:'';
            $price=isset($result[0]->base_price)?$result[0]->base_price:0;
            $server=isset($result[0]->display_name)?$result[0]->display_name:0;
            $price_structure=isset($result[0]->pricing_structure_type)?$result[0]->pricing_structure_type:0;
            $base_package=isset($result[0]->is_base_package)?$result[0]->is_base_package:0;
            $alacarte=isset($result[0]->alacarte)?$result[0]->alacarte:0;
            
            if($price_structure==1){
                $price_structure="One Time";
            }else if($price_structure==2){
                $price_structure="Recurring";
            }else{
                $price_structure="Trail Pack";
            }
            
            if($base_package==1){
                $base_package="YES";
            }else{
                $base_package="NO";
            }
            if($alacarte==1){
                $alacarte="YES";
            }else{
                $alacarte="NO";
            }
            
        }
        $returnValue = array('name' => $pname, 'price' => $price,'server'=>$server,'price_structure'=>$price_structure,"base_package"=>$base_package,"alacarte"=>$alacarte);
        echo json_encode($returnValue);
        exit;
    }
    
    //get product deatils for approval purpose added by Ravula
    public function getUser_detailsforapprove(){
        log_message('debug', 'ajax/getUser_detailsforapprove controller function initialized');
        $data = "";
        $Id = $this->input->post('user_id');
        $result = $this->employeemodel->get_EmployeeList($Id);
        //echo $this->db->last_query();die;
        if (isset($result) && count($result) > 0) {
            $name=isset($result[0]->first_name)?$result[0]->first_name.' '.$result[0]->last_name:'';
            $code=isset($result[0]->dist_subdist_lcocode)?$result[0]->dist_subdist_lcocode:'';
            $type=isset($result[0]->users_type)?$result[0]->users_type:'';
            $business=isset($result[0]->business_name)?$result[0]->business_name:'';
            $city=isset($result[0]->city_name)?$result[0]->city_name:0;
            $mobile=isset($result[0]->mobile_no)?$result[0]->mobile_no:'';
            $pin=isset($result[0]->pin_code)?$result[0]->pin_code:'';
            $username=isset($result[0]->username)?$result[0]->username:'';
        }
        $returnValue = array('name' => $name, 'code' => $code,'type'=>$type,'business'=>$business,"city"=>$city,"mobile"=>$mobile,"pin"=>$pin,"username"=>$username);
        echo json_encode($returnValue);
        exit;
    }
	
	//get Vc Number for selected customer id added by Ravula
    public function getVcsbyLcocustID(){
        log_message('debug', 'ajax/getvcbylcocustid controller function initialized');
        $data = "";
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $user_Id = $this->input->post('user_id');
        if(is_array($user_Id) && count($user_Id)>0){
            $user_Id=implode(",",$user_Id);
        }
        $employee_id = $this->inventorymodel->getReseller_id($user_Id,$dealer_id);
        $cust_id = $this->input->post('cust_id');
        $server_id = $this->input->post('server_id');
        if(isset($employee_id)){
            $result = $this->inventorymodel->getvcsbylcocustid($cust_id,$employee_id,$dealer_id,$server_id);
        }
        //echo $this->db->last_query();die;
        if (isset($result) && count($result) > 0) {
            echo json_encode($result);
        }else {
            echo 0;
        }
        exit;
    }
	
	//function to get unmapped products by server type //eb_product_channels join added by chakri to get only having atleast one channel products
    public function getUnmappedProducts() {
        $stype = $this->input->post('stype');
        $sel_query = "SELECT DISTINCT p.product_id,p.pname FROM eb_products p INNER JOIN eb_product_channels epc on epc.product_id=p.product_id
                WHERE p.pricing_structure_type != 3 and p.is_master=0 and status=1 and p.backend_setup_id=$stype";
        $query = $this->db->query($sel_query);
        if ($query && $query->num_rows() > 0) {
            echo json_encode($query->result());
            exit;
        }
    }
	
	 public function getProductsOfBundle($bundle_id) {
        $prod_q = $this->db->query("SELECT p.product_id,p.sku,p.pname,p.created_on,p.created_by, "
                . "bs.cas_server_type,bs.display_name "
                . "FROM eb_product_combo_details pc "
                . " INNER JOIN eb_products p ON p.product_id = pc.child_product_id "
                . "INNER JOIN backend_setups bs ON bs.backend_setup_id = p.backend_setup_id "
                . " WHERE pc.master_product_id = $bundle_id and pc.status=1");
//        echo $this->db->last_query(); exit;
        $r = $prod_q->result();
        echo json_encode($r);
    }
	
	  public function gethistory_levels(){
       $val = '';
       $requestid = $_POST['request_id'];
		 
       $approval_type = 'STB REPLACEMENT';
       $date_format = (isset($_SESSION['dealer_setting']->DATE_FORMAT)) ? $_SESSION['dealer_setting']->DATE_FORMAT : 1;
       $gettoallevels = $this->inventorymodel->gettotal_level($approval_type); 
      //echo $this->db->last_query();
       $result = $this->inventorymodel->getall_level_approvaldata($requestid);
      // echo $this->db->last_query();die;
       $maxid = $this->inventorymodel->approvals_maxid($requestid);
       
       if(count($result) > 0){
        $i=1;
       $val .= '<table border="0" cellspacing="5" cellpadding="5" width="100%" class="mygrid" id="mygrid" style="background:none;">';
               $val .= '<thead><tr><th>Status</th><th>Sent/Approved/Rejected By</th><th>Date</th><th>Level</th></tr>	</thead>';
           foreach($result as $res){ 
          // echo "total"; echo $gettoallevels; echo "status";echo $res->status; echo "level"; echo $res->level_id;die;
             /*  if($res->status == 1 && $gettoallevels > 1 && $res->level_id >= 1) { $val .=	'<td>Approved</td>'; }
               else if($res->status == 1) { $val .=	'<td>Pending</td>'; }
               else if($res->status == 2){ $val .=	'<td>Approved</td>';  } 
               else if($res->status == 3) { $val .=	'<td>Rejected</td>';  } 
               else if($res->status == 4) { $val .=	'<td>Repalced</td>';  } 
               else if($res->status == 5){  $val .=	'<td>Failed</td>'; } */
           //if($i == 1 || ($res->new == $maxid && $gettoallevels != $maxid)) {
           if($i == 1) { $val .= '<td>Reuest</td>'; }
               else if($res->status == 1)  { $val .= '<td>'.$res->level_status.'</td>';  }
                else if($res->status == 2) {$val .=	'<td>Approved</td>'; } else if($res->status == 3){ $val .=	'<td>Rejected</td>';  } else if($res->status == 4) { $val .=	'<td>Repalced</td>'; } else { $val .= '<td>'.$res->level_status.'</td>'; }
               $val .=	'<td>'. $res->first_name.' '.$res->last_name.'</td>';
                $modifydate_date = date("d-m-Y H:i:s", strtotime($res->created_on)); 
               $val .=	'<td>'. $modifydate_date.'</td>';
               if($i == 1){
                   $val .= '<td class="count">--</td>';
	                
               } 
           else if($i > 1 && ($res->status == 1 || $res->status == 2 || $res->status == 3)){
                   $val .=	'<td class="count">'. $res->level_name.'<td>';
                        }
               else
               {
                   $val .=	'<td class="count">--</td>';
               }
               //$val .=	'<td class="count">'. $res->new.'maxlevel'.$maxid.'<td>';
               $val .= '</tr>';
               $i++;
           }
           $val .= '</table>';
           echo $val;
       }
       else{
           echo "No History Found";
       }
       
    }
	
	//function to update failed status if after approval changes - riya (27-4-2018)
    public function updatefailed_afterapproval(){
        //print_r($_POST);die;
          $oldid = $this->input->post('oldstock');
          $newid = $this->input->post('newstock');
          $requestid = $this->input->post('requestid');
          $remarks = $this->input->post('remarks');
          $did = $_SESSION['user_data']->dealer_id;
          $employee_id = $_SESSION['user_data']->dealer_id;
          $this->db->query("update stb_replacement_request set status=5,replacement_date=now(),replaced_by=$employee_id  where stb_replacement_request_id = $requestid");
          $this->db->query("insert into stb_replacement_approvals (stb_replacement_request_id,level_id,employee_id,dealer_id,remarks,created_on,status) values ($requestid,0,$employee_id,$did,'$remarks',now(),5)");
         
    }
	  //function to get levels to insert data using view employee level mapping   - riya (27-4-2018)
    public function get_levels()
    {
        $val = "<option value='-1'>Select </option>";
         $approval_types = $this->input->post('approval_types');
          $result = $this->inventorymodel->getlevels_approvaltype($approval_types);
         foreach($result as $levels){
            //$vals.='<option  value="'.$levels->level_id.'">..</option>';
            $val .= '<option value='.$levels->level_id.'>'.$levels->display_name.'</option>';
        }
       echo $val;
         
    }
    
    public function delete_level_mapping(){
        $mappedid = $this->input->post('id');
        $result = $this->inventorymodel->delete_levelmapping($mappedid);
        if($result > 0){
            echo 1;
        }
        else
        {
            echo 0;
        }
    }
	
	 //Function to get operation types based on access values written By Venkat.
    public function getOperationTypes(){
       //Load the models 
        $this->load->model(array('send_model'));
       //read the post values
        $cronJob = $this->input->post('cronJob')?$this->input->post('cronJob'):'';
        $int_dealer_id = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $options = '';$module_name = '';
        if($cronJob !='' && $int_dealer_id>0){
            $options .= '<option value="-1">Select</option>';
        if(isset($cronJob) && $cronJob == "Unpaid Customer Remainder"){
            $module_name = "UNPAID_CUSTOMER_REMINDER";
        } else if(isset($cronJob) && $cronJob == "Expiry Service Remainder"){
            $module_name = "EXPIRY_SERVICES_REMINDER";
        } else if(isset($cronJob) && $cronJob == "Low Balance Customer Remainder"){
            $module_name = "LOW_BALANCE_CUSTOMERS_REMINDER";
        }
        if($module_name != ''){
        $access_details_array = $this->send_model->getCasAccessValues($int_dealer_id, $module_name);
        }
        $int_osd_access = isset($access_details_array->osd_access) ? $access_details_array->osd_access : 0; //Get osd access value
        $int_cas_email_access = isset($access_details_array->cas_email_access) ? $access_details_array->cas_email_access : 0; //Get cas email access value
        $int_email_access = isset($access_details_array->email_access) ? $access_details_array->email_access : 0; //Get email access value
        $int_sms_access = isset($access_details_array->sms_access) ? $access_details_array->sms_access : 0; //Get sms access value
        if(isset($int_osd_access) && $int_osd_access == 1){
        $options .='<option value="1">OSD</option>';
        }
        if(isset($int_cas_email_access) && $int_cas_email_access == 1){
        $options .='<option value="2">CAS EMAIL</option>';
        }
        if(isset($int_sms_access) && $int_sms_access == 1){
        $options .='<option value="3">SMS</option>';
        }
        if(isset($int_email_access) && $int_email_access == 1){
        $options .='<option value="4">EMAIL</option>';
        }
        
        }
        echo $options;
    }

    public function pluginBasedservertypes()
    {
        $plugin = $_POST['pluginid'];
        if(isset($plugin) && $plugin>0)
            {
               if($plugin==1){
                $setup_for ="9";  
               } else if($plugin==3){
                  $setup_for ="8";   
               } else if($plugin==4){
                  $setup_for ="5";   
               }
        }else{
              $setup_for="5";
        }
        $did = $_SESSION['user_data']->dealer_id;
        $sql = $this->db->query("SELECT backend_setup_id,cas_server_type,scroll_type,use_mac,display_name,priority  FROM backend_setups WHERE dealer_id=$did AND setup_for = $setup_for order by cas_server_type");
        $val='';
        if ($sql && $sql->num_rows() > 0) {
            $result =  $sql->result();
            $val='<option value="-1">Select</option>';
            foreach($result as $server){
                $val .="<option  value='".$server->backend_setup_id."' >".$server->display_name."</option>";
            }
        } 
        echo $val;
    }

    public function pluginBasedStbTypes()
    {
        $plugin = $_POST['pluginid'];
        if(isset($plugin) && $plugin>0)
        {
               if($plugin==1){
                $device_type ="3";  
               } else if($plugin==3){
                  $device_type ="2";   
               } else if($plugin==4){
                  $device_type ="1";   
               }
        }else{
              $device_type="1";
        }
        $val='';
        $sql_qry = $this->db->query("SELECT * FROM eb_stb_types WHERE device_type=$device_type");
        if ($sql_qry && $sql_qry->num_rows() > 0) {
            $result =  $sql_qry->result();
            $val='<option value="-1">Select</option>';
            foreach($result as $stb_type){
                $val .="<option  value='".$stb_type->stb_type_id."' >".$stb_type->display_name."</option>";
            }
        } 

        echo $val;

    }
    public function pluginBasedBroadcasters()
    {
        $plugin = $_POST['pluginid'];
        if(isset($plugin) && $plugin>0)
        {
               if($plugin==1){
                $broadcast_type ="3";  
               } else if($plugin==3){
                  $broadcast_type ="2";   
               } else if($plugin==4){
                  $broadcast_type ="1";   
               }
        }else {
            
              $broadcast_type="1";
        }

        $join='';$condition='';
            if($_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="RESELLER"){

                $int_employee_id = $_SESSION['user_data']->employee_parent_id;
            }else{
                $int_employee_id = $_SESSION['user_data']->employee_id;
            }
            //$_SESSION['additionalInfo']='';
            //Below condition check for distributor login or subdistributor login-and add dealer_id in query---TRIDEEP
            if($_SESSION['user_data']->users_type=='DISTRIBUTOR' || $_SESSION['user_data']->users_type=='SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type=="DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type=="SUBDISTRIBUTOR") { 
                $join ="
                INNER JOIN eb_broadcaster_channels ebc ON ebc.broadcaster_id = eb.broadcaster_id
                INNER JOIN eb_product_channels pc ON pc.channel_id = ebc.channel_id
                INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = pc.product_id
                INNER JOIN employee e ON e.employee_id = rpm.employee_id
                INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
                $condition = " (e.parent_id =$int_employee_id OR e1.parent_id =$int_employee_id) AND ";  
            }
            else if($_SESSION['user_data']->users_type=='RESELLER'|| $_SESSION['user_data']->employee_parent_type=="RESELLER"  ){
            // in lco login getting lco mapped products related braodcasters by SRAVANI
                $join ="
                INNER JOIN eb_broadcaster_channels ebc ON ebc.broadcaster_id = eb.broadcaster_id
                INNER JOIN eb_product_channels pc ON pc.channel_id = ebc.channel_id
                INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = pc.product_id AND rpm.employee_id = $int_employee_id
                INNER JOIN employee e ON e.employee_id = rpm.employee_id";
                $condition = " (e.employee_id =$int_employee_id) AND ";
            }
            $sql = $this->db->query("select eb.broadcaster_id, eb.broadcaster_name from eb_broadcasters eb 
                $join
                WHERE $condition eb.dealer_id=".$_SESSION['user_data']->dealer_id." AND eb.broadcast_type = $broadcast_type group by eb.broadcaster_id order by broadcaster_name " );
            $val='';
            if($sql && $sql->num_rows() > 0)
            {
                $result = $sql->result();
                $val='<option value="-1">Select</option>';
                foreach($result as $broadcaster){
                    $val .="<option  value='".$broadcaster->broadcaster_id."' >".$broadcaster->broadcaster_name."</option>";
                }
            }

            echo $val;
            
    }

    public function pluginBasedCasPackages($productId = -1, $package_type = -1,$server_id=-1)
    {
        $plugin = $_POST['pluginid'];
        if(isset($plugin) && $plugin>0)
        {
            $plugin_id =  $plugin;  
        }else{
              $plugin_id= 4;
        }

        $condition = '';
        $join = '';
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $employee_id = $_SESSION['user_data']->employee_id;
       
       
        if ($_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "RESELLER") {

            $employee_id = $_SESSION['user_data']->employee_parent_id;

        } else {
            $employee_id = $_SESSION['user_data']->employee_id;
        }
        if ($_SESSION['user_data']->users_type == 'RESELLER' || $_SESSION['user_data']->employee_parent_type == "RESELLER") {
            $join = "INNER JOIN eb_reseller_product_mapping pm ON pm.product_id = ep.product_id AND pm.employee_id=?";
        }
        //Below condition check for distributor login or subdistributor login----TRIDEEP
        if ($_SESSION['user_data']->users_type == 'DISTRIBUTOR' || $_SESSION['user_data']->users_type == 'SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR") {
            $join = "INNER JOIN eb_reseller_product_mapping rpm ON rpm.product_id = ep.product_id
                    INNER JOIN employee e ON e.employee_id = rpm.employee_id
                    INNER JOIN employee e1 ON e1.employee_id = e.parent_id";
            $condition .= " (e.parent_id =? OR e1.parent_id =?) AND ";
        }
        $sql_str = "SELECT ep.*,bs.cas_server_type,bs.display_name FROM eb_products ep INNER JOIN backend_setups bs ON bs.backend_setup_id=ep.backend_setup_id 
                    $join
                    WHERE $condition ep.status=1 AND ep.plugin_id= $plugin_id AND ep.dealer_id=? group by ep.product_id ORDER BY ep.pname";
        if ($_SESSION['user_data']->users_type == 'DISTRIBUTOR' || $_SESSION['user_data']->users_type == 'SUBDISTRIBUTOR' || $_SESSION['user_data']->employee_parent_type == "DISTRIBUTOR" || $_SESSION['user_data']->employee_parent_type == "SUBDISTRIBUTOR") {            

                $query = $this->db->query($sql_str, array($employee_id, $employee_id, $dealer_id)); 

        } else if ($_SESSION['user_data']->users_type == 'RESELLER' || $_SESSION['user_data']->employee_parent_type == "RESELLER") {
           
                $query = $this->db->query($sql_str, array($employee_id, $dealer_id)); //SQL Injectiion ----TRIDEEP
        
        } else {

                $query = $this->db->query($sql_str, array($dealer_id)); //SQL Injectiion ----TRIDEEP
        }
        if ($query && $query->num_rows() > 0)
        {
            $result =  $query->result();
                $val='<option value="-1">Select</option>';
            foreach($result as $pack) {
                $val .="<option  value='".$pack->product_id."' >".$pack->pname."(".$pack->display_name.")"."</option>";
            }
        }
        echo $val;
    }

    // added by rajasekhar 6-12-2018

    public function pluginBasedModels($model_id=0)
    {
         $plugin = $_POST['pluginid'];
        // added by rajasekhar 24-10-2018
        if(isset($plugin) && $plugin>0)
            {
               $plugin_id = $plugin;
               if($plugin_id==4)
               {
                 $device_type=1;
               }
               if($plugin_id==3)
               {
                 $device_type=2;
               }
               
           }else{
              $device_type=1;
           }

        $cond =' ';
        if($model_id>0){
                $cond =" AND model_id=$model_id";
        }
        $did = $_SESSION['user_data']->dealer_id;
        $sql=$this->db->query("SELECT * FROM  `eb_models` WHERE dealer_id = $did AND status=1 $cond And device_type= $device_type ORDER BY model_name");
            if($sql && $sql->num_rows()>0) {
                $result = $sql->result();
                 $val='<option value="-1">Select</option>';
                foreach($result as $model) {
                $val .="<option  value='".$model->model_id."' >".$model->model_name."</option>";
                }
              }
            
        echo $val;
    }
    //added by hemalatha 24-10-2018
    function setunpaid_lco_scroll(){
        $employeeId = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $user_type = isset($_SESSION['user_data']->users_type)?$_SESSION['user_data']->users_type:'';
        if($user_type=='RESELLER'){
            $cf = new CommonFunctions();
            $cf->setunpaid_lco_scroll($employeeId, $did,$user_type);
        }
    }
	
	//lco deposit change employee details ramya on april 13
    public function lcodepositchange_details(){
        $this->load->model('Lco_billdeductions_model');
        $employee_id = $this->input->post("employee_id");
        //$employee_id=7944;
        $response_data = array();
         if($employee_id>0){
            $employee_details =  $this->Lco_billdeductions_model->getemployee_details($employee_id);
            $dealer_id =$employee_details->dealer_id;
            $deposit_amount = $employee_details->deposit_amount;
            $accpayment_details = $this->Lco_billdeductions_model->getacc_payment_details($employee_id);
            $months_details = $this->Lco_billdeductions_model->getmonths_details($employee_id,$dealer_id);
            if(count($months_details)>0)
            {
                $max_bill_month= $months_details->max_bill_month;
                $min_bill_month= $months_details->min_bill_month;
                $get_mso_share = $this->Lco_billdeductions_model->get_mso_share_employee($employee_id,$dealer_id,$max_bill_month,$min_bill_month);
                foreach ($get_mso_share as $mso_share) {
                    $mshare = $mso_share->mso_share;
                }
            }
            echo $deposit_amount."@".$accpayment_details."@".$mshare;
           
        }
        
        
    }
    
    //For customer payment update from one customer to another for new customer details added by Ramya on oct 29 2018
    public function getnew_customer_details(){
        $this->load->model('CustomersModel');
        $int_select_criteria = $this->input->post('select_for') ? $this->input->post('select_for') : 0;
        $string_customer_caf_crf=$this->input->post('crf_caf_no') ? $this->input->post('crf_caf_no') : '';
        $dealer_id=$this->input->post('dealer_id') ? $this->input->post('dealer_id') : 0;
        $int_bill_type=$this->input->post('bill_type') ? $this->input->post('bill_type') : 0;
        $int_old_customer_id=$this->input->post('old_customer_id') ? $this->input->post('old_customer_id') : 0;
        $adjustment_customer_details_array =  $this->CustomersModel->getadjustment_customer_details($int_select_criteria,$string_customer_caf_crf,$dealer_id,$int_bill_type);
        $checkcustomer_id = isset($adjustment_customer_details_array->customer_id)?$adjustment_customer_details_array->customer_id:0;
       //echo $this->db->last_query();
        //echo $adjustment_customer_details_array->customer_id;
        $result = 0;
        if(count($adjustment_customer_details_array)  == 0 || $checkcustomer_id ==0 ){
            echo $result;
        }else{
            if($int_old_customer_id == $checkcustomer_id){
                $result = 0;
            }else{
                $crf_no = isset($adjustment_customer_details_array->crf)?str_replace('-', '',$adjustment_customer_details_array->crf):'';
                $caf_no = isset($adjustment_customer_details_array->caf_no)?$adjustment_customer_details_array->caf_no:'';
                $account_number = isset($adjustment_customer_details_array->account_number)?$adjustment_customer_details_array->account_number:'';
                $balance = isset($adjustment_customer_details_array->balance)?$adjustment_customer_details_array->balance:0;
                $name = isset($adjustment_customer_details_array->first_name)?$adjustment_customer_details_array->first_name:'';
                $business_name = isset($adjustment_customer_details_array->business_name)?$adjustment_customer_details_array->business_name:'';
                $serial_number = isset($adjustment_customer_details_array->serial_number)?$adjustment_customer_details_array->serial_number:'';
                $mac_vc_number = isset($adjustment_customer_details_array->vc_number)?$adjustment_customer_details_array->vc_number:'';
                $result = "<br><br><table style='font-size: 12px;font-weight: bold;width: 70%;color: #2E5E79;' id='tstyle'>
                        <tr>
                            <td>Customer CRF / CAF No.</td>
                            <td>".$crf_no."/".$caf_no."</td>
                        </tr>
                        <tr>
                            <td>Account Number</td>
                            <td>".$account_number."</td>
                        </tr>
                        <tr>
                            <td>Name</td>
                            <td>".$name."</td>
                        </tr>
                         <tr>
                            <td>LCO</td>
                            <td>".$business_name."</td>
                        </tr>
                        <tr>
                            <td>Current Balance</td>
                            <td>".$balance."</td>
                        </tr>
                        <tr>
                            <td>Devices</td>
                            <td>".$serial_number."(".$mac_vc_number.")</td>
                        </tr>
                        
                       
                    </table> <br><br>";
            }
            
            echo $result;
        }

    }
	
	 //by ravula for to get employee is blocked or not on 22 nov 2018.
    public function getBlockedselectedUser(){
       //read the post values
        $int_location_id = $this->input->post('location_id')?$this->input->post('location_id'):0;
        //get the selected employee is blocked or suspended checking 
        $result = $this->employeemodel->is_Blockedornot($int_location_id);
        if($result > 0){
            echo 1;
        }else{
            echo 0;
        }
    }

    // added by rajasekhar get broad caster price, pricemin,max,carriage price min,max 18-12-2018

    public function getChannelbrodcasterprice()
    {
       $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
       $str_channelname = $_POST['channelname'];
       $this->obj_channels= new channelsmodel(); 
       $broadcasterprice = $this->obj_channels->getBroadcasterchannelprices($int_did,$str_channelname);
       $maximumalacateprice = $this->obj_channels->getmaximumAlacateyprice($int_did);
       $priceminmaxvalue = $this->obj_channels->getPriceminmaxvalues($int_did);
       
       $data=array(
          'broadcasterprice'=>$broadcasterprice,
          'minprice'=>isset($priceminmaxvalue->min_value)?$priceminmaxvalue->min_value:'',
          'maxprice'=>isset($priceminmaxvalue->max_value)?$priceminmaxvalue->max_value:'',
          'alacatevalue'=>isset($maximumalacateprice->max_value)?$maximumalacateprice->max_value:''
       );
       echo json_encode($data);
    }

    // added by rajasekhar get carrage price to get min max vales on 18-12-2018

    public function getCarriagepriceminmax()
    {
        $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $this->obj_channels= new channelsmodel();

        $carragepriceminmax = $this->obj_channels->getCarriagepriceminmax($int_did);

        $data=array(
          'minprice'=>isset($carragepriceminmax->min_value)?$carragepriceminmax->min_value:'',
          'maxprice'=>isset($carragepriceminmax->max_value)?$carragepriceminmax->max_value:'',
       );
       echo json_encode($data);
    }

    // added by rajasekhar for get Sd channels

    public function getSdchannels()
    {
        $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $this->obj_channels= new channelsmodel();
        $int_channelid = $_POST['channelid'];
          
        $sdchannels = $this->obj_channels->getSdchannels($int_channelid,$int_did);


        $mappedchanelid = $this->obj_channels->getmappedchannelid($int_channelid,$int_did);

        $mappedchannelname = $this->obj_channels->getmappedchannelname($mappedchanelid,$int_did);

        $channelname  = $this->obj_channels->getmappedchannelname($int_channelid,$int_did);

        if(count($sdchannels) > 0)
        {

            if($mappedchannelname!='')
            {
                $text = " <b>Mapped Channel Name</b> : ".$mappedchannelname."";
            }
            else{
            	$text='';
            }
            if($channelname!='')
            {
                $text1 = " <b>Selected Channel Will Be Mapped to</b> : ".$channelname."";
            }else{
            	$text1 ='';
            }
            $val="<div style='width:100%;font-size:16px;margin-bottom:5px;'>$text1</div><div style='width:100%;font-size:16px;margin-bottom:5px;'>$text</div><table style='font-size: 12px;font-weight: bold;width:100%;border: 1px solid black;border-collapse:collapse;
' id='tstyle'>
                    <thead style='border-bottom:1px solid black'>
                        <tr>
                         <td style='border: 1px solid black ;text-align:center;font-size:16px'>Select</td>
                        <td style='border: 1px solid black ;text-align:center;font-size:16px'>Channel Name</td>
                
                 </tr>
                    </thead>
                 <tbody>";
            foreach ($sdchannels as $key => $value) {

                 if($value->channel_id==$mappedchanelid)
                 {
                    $checked = "checked";
                 }else{
                    $checked = "";
                 }
                 if($value->is_pay_channel==2)
                 {
                    $color="green";
                 }else{
                     $color="";
                 }
               $val .="
               <tr style='border: 1px solid black ;'>
                     <td style='border: 1px solid black ;text-align:center;font-size:16px'><input type='radio' class='form-control' name='sel_channel_id' id='sel_channel_id'  value='".$value->channel_id."' $checked/>
                         <input type='hidden' name='parent_channel_id' id='parent_channel_id' value='".$int_channelid."'/>
                     </td>
                      <td style='border: 1px solid black;font-size:16px'><p style='color:$color'>".$value->channel_name."</p></td>
                 </tr>";
            }

             $val .="</tbody></table>";

            echo  $val;
           
        } else {

           echo $val='SD Channels Not Found';
        }
    }

    // added function by rajasekhar for map sd channel to hd on 18-12-2018

    public function mapSdChannel()
    {
        $this->obj_channels= new channelsmodel();
        $int_mappedid = $_POST['mappedid'];
        $int_channel_id = $_POST['parentid'];

        $sdstatus = $this->obj_channels->mapsdChannel($int_channel_id,$int_mappedid);
        echo $sdstatus;
        
    }
    
    // added by function rajasekhar on 19-12-2018

    public function getMappedchannels()
    {
        $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $this->obj_channels= new channelsmodel();
        $mappedchannels = $this->obj_channels->mappedChannel($int_did);


        
         if(count($mappedchannels) > 0)
        {

            $val="<table style='font-size: 12px;font-weight: bold;width:100%;border: 1px solid black;border-collapse:collapse;
' id='tstyle'>
                    <thead style='border-bottom:1px solid black'>
                        <tr>
                         <td style='border: 1px solid black ;text-align:center;font-size:16px'>HD Channel Name</td>
                        <td style='border: 1px solid black ;text-align:center;font-size:16px'>Mapped SD Channel Name</td>
                
                 </tr>
                    </thead>
                 <tbody>";
            foreach ($mappedchannels as $key => $value) {

                   if($value->hdpay==2)
                   {
                     $hdpay="green";
                   }else{
                     $hdpay="grenn";
                   }
                   if($value->sdpay==2)
                   {
                      $sdpay ="green"; 
                   }else{
                      $sdpay="";
                   }

               $val .="
               <tr style='border: 1px solid black ;'>
                     <td style='border: 1px solid black;font-size:16px'>
                         <p style='color:$hdpay'>".$value->mappedchannelname."</p>
                     </td>
                      <td style='border: 1px solid black;font-size:16px'><p style='color:$sdpay'>".$value->channelname."</p></td>
                 </tr>";
            }

             $val .="</tbody></table>";

            echo  $val;
           
        } else {
           echo $val="<div style='width:100%;font-size:16px;text-align:center;margin-bottom:5px;'>Mapped Channels Not Found</div>";
        }
    
    }

    //Function to check is selllected channels mapped with another channels or not
    //this function used in plugins creation in product edit added by venkat (18-12-2018)
    
    public function channelpricechecking($flag=0,$channel_list_array=array(),$base_price=0,$channel_type=0){
        
      $this->load->model(array('channelsmodel'));  
      
      if($flag == 0){
      $channel_list_array = $this->input->post('channel_list')?$this->input->post('channel_list'):array();  
      $base_price = $this->input->post('base_price')?$this->input->post('base_price'):0;     
      $channel_type = $this->input->post('channel_type')?$this->input->post('channel_type'):0;     
      }
      $dealerId   = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0; 
      $allow_base_price_for_fta_int = (isset($_SESSION['dealer_setting']->ALLOW_BASE_PRICE_FOR_FTA)) ? $_SESSION['dealer_setting']->ALLOW_BASE_PRICE_FOR_FTA : 1;
      $channel_list_array = array_unique($channel_list_array);
      $message = ""; 
      $minimum_package_amount = 0;
      $maximum_package_amount = 0;
      $float_total_channels_amount = 0;
      if(count($channel_list_array)){
          
       $array_channelDetails = $this->channelsmodel->getChannelPriceDetails($channel_list_array,$dealerId,$falg=2);
       log_message("debug","======getChannelPriceDetails====".$this->db->last_query());
       if(isset($array_channelDetails) && count($array_channelDetails)>0){
        $float_total_channels_amount = $array_channelDetails->totalamount;
       }
        $message_type = $this->input->post('message_type')?$this->input->post('message_type'):0;
        $validation_key="PACKAGE_PRICE";
        $array_validation_Details = $this->channelsmodel->getPackagePrice($validation_key,$dealerId);
        log_message("debug","======getPackagePrice====".$this->db->last_query());
        log_message("debug","======channel_type====".$channel_type);
        if($channel_type == 2){
        if(isset($array_validation_Details) && count($array_validation_Details)>0){
            
            //both are percenrtage values.
            $int_min_percentage = $array_validation_Details->min_value;
            $int_max_percentage = $array_validation_Details->max_value;
            
            $minimum_package_amount = ($int_min_percentage/100) * $float_total_channels_amount;
            $maximum_package_amount = ($int_max_percentage/100) * $float_total_channels_amount;
            
            if($base_price < $minimum_package_amount){
             
                if($message_type == 1){
					$message =  "Package price should be more than $int_min_percentage% of the sum of broadcaster defined channel MRP.
                            Please enter amount greater than Rs $minimum_package_amount to be TRAI compliant.";
                                } else{
              $message =  "<p><span style='color:red;'>WARNING:</span> Package price should be more than $int_min_percentage% of the sum of broadcaster defined channel MRP.</p>
                            <p style='margin-left:13%;'>Please enter amount greater than Rs $minimum_package_amount to be TRAI compliant.</p>";
                                }
              if($flag == 0){
              echo "1@@$message@@$minimum_package_amount@@$maximum_package_amount";
              } else{
               return "1@@$message@@$minimum_package_amount@@$maximum_package_amount";   
              }
            } else{
               $message = ""; 
               if($flag == 0){
               echo "0@@$message@@$minimum_package_amount@@$maximum_package_amount";
               } else{
                return "0@@$message@@$minimum_package_amount@@$maximum_package_amount";   
               }
            }
            
        } else{
          if($flag == 0){  
          echo "0@@$message@@$minimum_package_amount@@$maximum_package_amount";  
          }else{
              return "0@@$message@@$minimum_package_amount@@$maximum_package_amount"; 
          }
        }
        } else if($channel_type == 3){
            
            if ($base_price > 0 && $allow_base_price_for_fta_int==0) { //FOR FTA package type check allobase_price_for_fta and base_price
                    if($message_type == 1){
                      $message = "For FTA channels  Base price should be zero.";  
                    }else{
                    $message = "<p><span style='color:red;'>WARNING:</span>For FTA channels Base price should be zero.</p>";
                    }
                
                if ($flag == 0) {
                    echo "1@@$message@@0@@0";
                } else {
                    return "1@@$message@@0@@0";
                }
            } else {
                if ($flag == 0) {
                    echo "0@@$message@@0@@0";
                } else {
                    return "0@@$message@@0@@0";
                }
            }
        }
      }
    }
     //Function to update base price written By Venkat
    public function updateBasePrice(){
		
      $this->load->model('inventorymodel');      
      $channel_list_array = $this->input->post('channel_list')?$this->input->post('channel_list'):array();  
      $base_price = $this->input->post('base_price')?$this->input->post('base_price'):0;  
      $product_id = $this->input->post('product_id')?$this->input->post('product_id'):0;  
      $alacarte_flag = $this->input->post('alacarte_flag')?$this->input->post('alacarte_flag'):0;  
      $dealerId   = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0; 
      $channel_list_array = array_unique($channel_list_array);
	  $channel_type = $this->input->post('channel_type')?$this->input->post('channel_type'):0;
	  $is_broadcaster_package = $this->input->post('is_broadcaster_package');
	  if($is_broadcaster_package == -1 || $is_broadcaster_package == ''){
		  $is_broadcaster_package = 1;
	  }
       log_message("debug","==============channel_type========".$channel_type);
       log_message("debug","==============is_broadcaster_package========".$is_broadcaster_package);
      $is_valid = 0; 
	  if($alacarte_flag == 0){
      $channelPrice_details = $this->channelpricechecking($flag_val=1,$channel_list_array,$base_price,$channel_type);
        log_message("debug","==============channelpricechecking========".$this->db->last_query());
        log_message("debug","==============channelpricecheckingRES========".json_encode($channelPrice_details));
       $re_channelPrice_details = explode("@@", $channelPrice_details);
       $resvalue = $re_channelPrice_details[0];
       $resmessage = $re_channelPrice_details[1];  
       $minimum_package_amount = $re_channelPrice_details[2];  
       $maximum_package_amount = $re_channelPrice_details[3];
       
       if($base_price < $minimum_package_amount ){
          echo 0; 
       } else{
            
         $is_valid = 1;      
       }
	  } else{
		 $channelPrice_details = $this->checkChannelPriceForAlacarte($flag_val=1,$channel_type,$base_price,$channel_list_array); 
		  $re_channelPrice_details = explode("@@", $channelPrice_details);
		  
		  $resvalue = $re_channelPrice_details[0];
          $resmessage = $re_channelPrice_details[1];
		  
		  if($resvalue == 0){
			  $is_valid = 1;
		  } else{
			  echo 0;
		  }
	  }
	  
	   if($is_valid == 1){
	   $arr_update_params = array('base_price' => $base_price);
	   $arr_condition = array('product_id' => $product_id);
	   $is_updated = $this->inventorymodel->simpleUpdate($tablename = 'eb_products', $arr_update_params, $arr_condition);
	   
	    echo $is_updated;
	   } else{
		   echo 0;
	   }
	   
    }
    
    
    //Function to check channel price for Alacarte written By venkat(19-12-2018)
     //Function to check channel price for Alacarte written By venkat(19-12-2018)
    public function checkChannelPriceForAlacarte($flag = 0, $channel_type = 0, $base_price = 0, $channel_list_array = array()) {
        $float_total_channels_amount = 0;
        $message = "";
        $this->load->model(array('channelsmodel'));
        if ($flag == 0) {
            $base_price = $this->input->post('baseprice') ? $this->input->post('baseprice') : 0;
            $channel_type = $this->input->post('channel_type') ? $this->input->post('channel_type') : 0;

            $channel_list_array = $this->input->post('channel_list') ? $this->input->post('channel_list') : array();

            $channel_list_array = array_unique($channel_list_array);
        }
        $message_type = $this->input->post('message_type') ? $this->input->post('message_type') : 0;
        
        $dealerId = isset($_SESSION['user_data']->dealer_id) ? $_SESSION['user_data']->dealer_id : 0;
        $allow_base_price_for_fta_int = (isset($_SESSION['dealer_setting']->ALLOW_BASE_PRICE_FOR_FTA)) ? $_SESSION['dealer_setting']->ALLOW_BASE_PRICE_FOR_FTA : 1;
        $array_channelDetails = $this->channelsmodel->getChannelPriceDetails($channel_list_array, $dealerId, $flag_val = 2);

        if (isset($array_channelDetails) && count($array_channelDetails) > 0) {
            $float_total_channels_amount = $array_channelDetails->totalamount;
        }

        if ($channel_type == 2) {

            if ($base_price > $float_total_channels_amount) {
                if($message_type == 1){
                    $message = "Base price should be less than or equal to broadcaster channel MRP For A-la-carte channel.your broadcaster channel MRP $float_total_channels_amount Rs";
                } else{
 
                    $message = "<p><span style='color:red;'>WARNING:</span>Base price should be less than or equal to broadcaster channel MRP For A-la-carte channel.</p><p style='margin-left:13%;'>your broadcaster channel MRP $float_total_channels_amount.</P>";
                }

                if ($flag == 0) {
                    echo "1@@$message";
                } else {
                    return "1@@$message";
                }
            } else {
                if ($flag == 0) {
                    echo "0@@$message";
                } else {
                    return "0@@$message";
                }
            }
        } else if ($channel_type == 3) {
            if ($base_price > 0 && $allow_base_price_for_fta_int==0) { //FOR FTA package type check allobase_price_for_fta and base_price
                    if($message_type == 1){
                      $message = "Base price should be zero.";  
                    }else{
                    $message = "<p><span style='color:red;'>WARNING:</span>Base price should be zero.</p>";
                    }
                
                if ($flag == 0) {
                    echo "1@@$message";
                } else {
                    return "1@@$message";
                }
            } else {
                if ($flag == 0) {
                    echo "0@@$message";
                } else {
                    return "0@@$message";
                }
            }
        }
    }

    function getspecialcharge_details(){
        $sp_chrge_id = isset($_POST['sp_chrge_id']) ? $_POST['sp_chrge_id'] : '';
        if ($sp_chrge_id != '') {
            $sql_str = "SELECT sc.special_charge_id,sc.charge_type,sc.amount,sc.min_value,sc.max_value,sc.amount_type,sc.tax_template,sc.lco_share,sc.edit_amount FROM  special_charges sc  WHERE sc.special_charge_id='$sp_chrge_id'" ;
            $query = $this->db->query($sql_str);
            if ($query) {
                $res = $query->row();
                $special_charges = array('amount'=>$res->amount,'min_value'=>$res->min_value,'max_value'=>$res->max_value,'edit_amount'=>$res->edit_amount,'charge_type'=>$res->charge_type); 
            }
        }
        echo json_encode($special_charges);
    }

    //channel deactivation
    function deactivate_channel() {

        $response = 0;
        $channel_id = isset($_POST['cid']) ? $_POST['cid'] : 0;
        $sql_qry = $this->db->query("select count(*) cnt from eb_broadcaster_channels where channel_id=$channel_id and DATE(start_date)<=CURDATE() and date(end_date)>=CURDATE()");
        if ($sql_qry && $sql_qry->num_rows() > 0 && $sql_qry->row()->cnt == 0) {
            $sql_str = "UPDATE eb_channels SET status = 0 WHERE channel_id = $channel_id";
            $query = $this->db->query($sql_str);
            if ($query && $this->db->affected_rows() > 0) {
                $response = 1;
            }
        }
        echo $response;
    }

    //channel activation
    function activate_channel() {

        
        $channel_id = isset($_POST['cid']) ? $_POST['cid'] : 0;

        $sql_str = "UPDATE eb_channels SET status = 1 WHERE channel_id = $channel_id";
        $query = $this->db->query($sql_str);
        if ($query && $this->db->affected_rows() > 0) {
            $response = 1;
        }else{
            $response = 0;
        }
        echo $response;
    }

    
   //function getspecialcharge_details(){
       // $sp_chrge_id = isset($_POST['sp_chrge_id']) ? $_POST['sp_chrge_id'] : '';
       // if ($sp_chrge_id != '') {
          //  $sql_str = "SELECT sc.special_charge_id,sc.charge_type,sc.amount,sc.min_value,sc.max_value,sc.amount_type,sc.tax_template,sc.lco_share,sc.edit_amount FROM  special_charges sc  WHERE sc.special_charge_id='$sp_chrge_id'" ;
          //  $query = $this->db->query($sql_str);
           // if ($query) {
            //    $res = $query->row();
           //     $special_charges = array('amount'=>$res->amount,'min_value'=>$res->min_value,'max_value'=>$res->max_value,'edit_amount'=>$res->edit_amount,'charge_type'=>$res->charge_type); 
           // }
       // }
      //  echo json_encode($special_charges);
   // }
	
	
	
    // For bulk channel edit added by Ramya on Dec 20 2018
    public function channeldata_insert($channel_id){
        $return_val= 'Failed';
        $this->load->model(array('channelsmodel'));
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $channel_name = ($this->input->post('channel_name'))?$this->input->post('channel_name'):'';
        $channel_category = ($this->input->post('channel_category'))?$this->input->post('channel_category'):0;
        $language = ($this->input->post('language'))?$this->input->post('language'):0;
        $channel_number = ($this->input->post('channel_number'))?$this->input->post('channel_number'):0;
        $channel_type = ($this->input->post('channel_type'))?$this->input->post('channel_type'):0;
        $channel_price = ($this->input->post('channel_price'))?$this->input->post('channel_price'):0;
        $carriage_price = ($this->input->post('carriage_price'))?$this->input->post('carriage_price'):0;
        $is_hd = ($this->input->post('is_hd'))?$this->input->post('is_hd'):0;
        $is_mandatory = ($this->input->post('is_mandatory'))?$this->input->post('is_mandatory'):0;
      //  $uploaded_file = ($this->input->post('uploaded_file'))?$this->input->post('uploaded_file'):'';
        $validation_key = 'maximum_alacte_price';
        $validation_details  = $this->channelsmodel->getPackagePrice($validation_key,$dealer_id);
        $alacate_price =0;
		$alacate_status  = 0;
		
        $is_valid = 1;
        if(count($validation_details) > 0){
            $alacate_price = isset($validation_details->max_value)?$validation_details->max_value:0;
			$alacate_status = isset($validation_details->status)?$validation_details->status:0;
        }
        $is_alacate=0;
        if($channel_price > $alacate_price && $alacate_status==1){
            $is_alacate=1;
        }

        if($channel_type ==2){
			$channel_price = 0;
		}

        //channel Name validation
        if($is_valid == 1){
            $duplicate_names = $this->channelsmodel->checkChannelName($channel_name,$dealer_id,$channel_id);
            if($duplicate_names>0){
                $return_val = json_encode(array('msg'=>"Invalid data. Duplicate channel name"));
                $is_valid = 0;
            }
            
        }

        //channel Number validation
        if($is_valid == 1){
            $duplicate_nums = $this->channelsmodel->checkChannelNumber($channel_number,$dealer_id,$channel_id);
            if($duplicate_nums>0){
                $return_val = json_encode(array('msg'=>"Invalid data. Duplicate channel number."));
                $is_valid = 0;
            }
        }

        if($is_valid == 1){
            $update_channel_id = $this->channelsmodel->updateChannel($dealer_id,$channel_id,$channel_name,$channel_category,$channel_number,$channel_type,$is_local=0,$is_hd,$uploaded_file='',$sdate='',$edate='',$language,$channel_price,$carriage_price,$is_alacate=0,$is_mandatory);

           if($update_channel_id == 1){
                $channeldetails = $this->channelsmodel->getChannelDetails($dealer_id,$channel_id);
			   log_message('debug','return_val'.$this->db->last_query());
                $return_val= json_encode($channeldetails[0]);
           }
        }
        log_message('debug','return_val'.$return_val);
        echo  $return_val;



    }

    // added by rajasekhar 30-10-2018

    function getCreditnote()
    {
         
         $int_lcobillingid = $_POST['lcobillingid'];
         $int_employeeid = $_POST['employeeid'];
         $billmonth = $_POST['billmonth'];
         $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
         $details = $this->employeemodel->getlcodetailsforcreditnote($int_lcobillingid,$int_employeeid,$billmonth,$int_did);
            
           $reseller = isset($details->reseller_name)?$details->reseller_name:"";
           $business_name = isset($details->business_name)?$details->business_name:"";
           $dist_subdist_lcocode = isset($details->dist_subdist_lcocode)?$details->dist_subdist_lcocode:"";
           $address = isset($details->address)?$details->address:"";
           $bill_month = isset($details->bill_month)?$details->bill_month:"";
           $total_amount = isset($details->total_amount)?$details->total_amount:"";
           $paid_amount = isset($details->paid_amount)?$details->paid_amount:"";
           $val="";

          if($details)
          {

            $val ="<br><table style='font-size: 12px;width:80%;line-height: 1.6;' id='tstyle'>
                            <tr>
                                <td><b>Name</b> </td>
                                <td>:</td>
                                <td>&nbsp".  $reseller."</td>
                            </tr>
                            <tr>
                                <td><b>Business Name</b></td>
                                <td>:</td>
                                <td>&nbsp".  $business_name."</td>
                            </tr>
                            <tr>
                                <td><b>Code</b></td>
                                <td>:</td>
                                <td>&nbsp".  $dist_subdist_lcocode."</td>
                                <input type='hidden' name='businessname' id='businessname' value=".$business_name."/>
                                <input type='hidden' name='lcocode' id='lcocode' value='$dist_subdist_lcocode'/>
                            </tr>

                            <tr>
                                <td><b>Address</b></td>
                                <td>:</td>
                                <td>&nbsp".  $address."</td>
                            </tr>
                            <tr>
                                <td><b>Action Type</b></td>
                                <td>:</td>
                                <td>&nbspCredit Note </td>
                            </tr>
                             <tr>
                                <td><b>Bill Month</b></td>
                                <td>:</td>
                                <td>&nbsp".  date("d-m-Y", strtotime($bill_month))."</td>
                            </tr>
                            <tr>
                                <td><b>Total Bill</b></td>
                                <td>:</td>
                                <td>&nbsp".  $total_amount."</td>
                            </tr>
                            <tr>
                                <td><b>Paid Amount</b></td>
                                <td>:</td>
                                <td>&nbsp".  $paid_amount."</td>
                            </tr>
                            <tr>
                            <td><b>Remarks</b></td>
                            <td>:</td>
                             <td> &nbsp<textarea id='lco_remarks' class='form-control' name='lco_remarks' style='width:100%'></textarea></td>
                            </tr>
                           
                        </table> <br><br>";

            // $val ='<p style="text-align:left"><label for="comment">Name</label> : '. $reseller .'</p>
            //<p style="text-align:left"><label for="comment">Business Name</label> : '.$business_name.'</p>
           // <p style="text-align:left"><label for="comment">Code</label> : '.$dist_subdist_lcocode.'</p>
            // <input type="hidden" name="businessname" id="businessname" value="'.$business_name.'"/>
            // <input type="hidden" name="lcocode" id="lcocode" value="'.$dist_subdist_lcocode.'"/>
            //<p style="text-align:left"><label for="comment">Address</label> : '.$address.' </p>
           // <p style="text-align:left"><label for="comment">Action Type</label> : Credit Note </p>
           // <p style="text-align:left"><label for="comment">Bill Month</label> : '.$bill_month.'</p>
           // <p style="text-align:left"><label for="comment">Total Bill</label> : '.$total_amount.' </p>
           // <p style="text-align:left"><label for="comment">Paid Amount </label> : '.$paid_amount.'</p>';
          } 

          echo $val;
    }

    // added by rajasekhar  checking additional password 19-11-2018

    public function checkAdditionalpassword()
    {
        $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $int_employeeid = $_POST['employeeid'];
        $additionalpassword = md5($_POST['person']);
       $additionaldetails = $this->employeemodel->checkadditionPassword($int_did,$int_employeeid,$additionalpassword);
       echo $additionaldetails;
    }
    // added by rajasekhar insert credit note 19-11-2018
    public function insertcreditnote()
    {
        $int_did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $int_created_by = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $int_employeeid = $_POST['employeeid'];
        $int_lcobillingid = $_POST['lcobillingid'];
        $billmonth = $_POST['billmonth'];
        $str_remarks = $_POST['remarks'];
        $month = date('m');
        if($month=='01' || $month=='02' || $month=='03')
        {
            
           $presentyear = date('Y',strtotime('-1 year')); 
           $postyear = date('Y');

        } else {
            
            $presentyear = date('Y');
            $postyear = date('Y',strtotime('+1 year'));
        }
        $startfinantialyear = $presentyear.'-04-01';
        $endfinantialyear = $postyear.'-03-31';
        $st = date('Y-m-d', strtotime($startfinantialyear));
        $ed = date('Y-m-d', strtotime($endfinantialyear));
        $month = date('m');
        $billmonth = date('Y-m-d', strtotime($billmonth));
        $created_date = date('Y-m-d');
        
        if (($billmonth >= $st) && ($billmonth <= $ed)){

             if($month==1 || $month==2 || $month==3)
             {
                $year = date('Y', strtotime('-1 year'));
             } else {
                $year = date('Y');
             }
             $creditnotecount = $this->employeemodel->getcreditnotenumber($year,$int_did);
             if($creditnotecount > 0)
             {
                 $creditnotecount++;
             } else {
                $creditnotecount=1;
             }
             $creditnote = $this->employeemodel->insertCreditnote($int_did,$int_employeeid,$int_lcobillingid,$int_created_by,$billmonth,$str_remarks,$year,$created_date,$creditnotecount);
             echo $creditnote;
         }

     }
     
      public function getServersList(){
        $pluginId = 0;
        $dealer_id = $_SESSION['user_data']->dealer_id;
        if ($this->input->post('pluginId')) {
            $pluginId = $this->input->post('pluginId');
        }
        
        $backend_setups = array();
        $setup_for = 5;
        if($pluginId == 3){
          $setup_for = 8;  //OTT Server
        } else if($pluginId == 4){
          $setup_for = 5;  //CAS Server
          $backend_setups = $this->input->post('cas_backend_setups')?explode(',',$this->input->post('cas_backend_setups')):array();
        }else if($pluginId == 1){
          $setup_for = 9;  
          $backend_setups = $this->input->post('isp_backend_setups')?explode(',',$this->input->post('isp_backend_setups')):array();
        } else if($pluginId == 1){
          $setup_for = 9;  //ISP server
        }

        $serversList = $this->inventorymodel->getServersList($setup_for,$dealer_id);
      
         $options = "";
         if (isset($serversList) && count($serversList) > 0) {
            
            if(isset($serversList) && count($serversList)==1){
                foreach ($serversList as $ser) {
                  $options.= "<option value='" . $ser->backend_setup_id . "' selected='selected'>" . $ser->display_name . '</option>';  
                }
            } else{
                //$options.= '<option value="-1">--Select--</option>';
                foreach ($serversList as $ser) {
            	  $selected = (in_array($ser->backend_setup_id, $backend_setups))?'selected=selected':'';       
                  $options.= "<option value='" . $ser->backend_setup_id . "' <?php $selected ?>" . $ser->display_name . "</option>";
                }
            }
         } else{
             $options.= '<option value="-1">No Servers Available</option>';
         }
         echo $options;
        exit;
    }

    //function for getting cas,isp products based on servers by Swaroop on Jan 10 2019
    public function getMappedProducts(){
        $pluginId = 0;
        $dealer_id = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $bundle_stype = $this->input->post('bundle_stype')?(is_array($this->input->post('bundle_stype'))?implode(',',$this->input->post('bundle_stype')):$this->input->post('bundle_stype')):'';
        if($bundle_stype==''){
            echo '';exit;
        }

        $this->load->model(array('productsmodel'));
        $bundle_stype_array = explode(',',$bundle_stype);
        $isp_products = $this->input->post('isp_products')?$this->input->post('isp_products'):'0';
        $product_ids = array();
        if($this->input->post('isp_product_ids')){
            $product_ids = explode(',',$this->input->post('isp_product_ids'));
        }
        if($this->input->post('cas_product_ids')){
            $cas_product_ids = $this->input->post('cas_product_ids');
            if(count($bundle_stype_array)==1){
                $product_ids = explode(',',$cas_product_ids);
            }
            else if(count($bundle_stype_array)>1){
                $cas_products = $this->productsmodel->getProductsBasedOnMultipleServers($plugin_type=4,$dealer_id,$bundle_stype,$bundle_stype_array,$total_cas_products='',$cas_product_ids);
                if(count($cas_products)>0){
                    foreach($cas_products as $product){
                        array_push($product_ids,$product->primary_server_product_id);
                    }
                }
            }
        }

        $html = "<table border='0' cellspacing='5' cellpadding='5' class='bundle_product_search' width='100%'><tr>";
        if(count($bundle_stype_array)==1 || $isp_products==1){
            $plugin_type=4;//CAS
            $input_type = 'checkbox';
            $input_name = 'bundle_cas_products[]';
            $input_id = 'bundle_cas_products';
            if($isp_products==1){
                $plugin_type=1;//ISP
                $input_type = 'radio';
                $input_name = 'bundle_isp_products[]';
                $input_id = 'bundle_isp_products';
            }
            $products = $this->productsmodel->getProductsBasedOnServer($plugin_type,$dealer_id,$bundle_stype);
            if(count($products)>0){
                $val = 0;
                foreach($products as $product){
                    $checked = (in_array($product->product_id, $product_ids))?'checked=checked':'';
                    $val = $val+1;
                    $html .= "<td><input type=$input_type class='bundles_checked' name=$input_name id=$input_id value='" . $product->product_id . "' <?php $checked?>&nbsp;" . $product->pname . "</td>";
                    if($val % 3 ==0){
                        $html .= "</tr><tr>";
                    }
                }
            }
        }
        else if(count($bundle_stype_array)>1){
            $products = $this->productsmodel->getProductsBasedOnMultipleServers($plugin_type=4,$dealer_id,$bundle_stype,$bundle_stype_array);
            if(count($products)>0){
                $val = 0;
                foreach($products as $product){
                    $checked = (in_array($product->primary_server_product_id, $product_ids))?'checked=checked':'';
                    $val = $val+1;
                    $html .= "<td><input type='checkbox' class='bundles_checked' name='bundle_cas_products[]' id='bundle_cas_products' value='" . $product->primary_server_product_id . "' <?php $checked?>&nbsp;" . $product->pname . "</td>";
                    if($val % 3 ==0){
                        $html .= "</tr><tr>";
                    }
                }
            }
        }
        $html .= "</table>";
        
        echo $html;
        exit;
    }
	//Function to get package channel information written By venkat (19-02-2018)
     public function getpackagedetails(){
		
        //get employee id and dealer Id from user login-prasad  
      
        $dealerId = isset($_SESSION['user_data']->dealer_id) ? $_SESSION['user_data']->dealer_id : 0;
        $product_id = $this->input->post('product_id') ? $this->input->post('product_id') : 0;

        $channel_information = $this->wsmodel->getChannelInformation($product_id, $dealerId);
        $package_information = $this->wsmodel->getPackageInformation($product_id, $dealerId);
 
        $package_name = isset($package_information->pname) ? $package_information->pname : '';
        $sd_channels_count = isset($package_information->sd_channels_count) ? $package_information->sd_channels_count : 0;
        $hd_channels_count = isset($package_information->hd_channels_count) ? $package_information->hd_channels_count : 0;
        $tot_channels_count = isset($package_information->tot_channels_count) ? $package_information->tot_channels_count : 0;
        $is_base_package = isset($package_information->is_base_package) ? $package_information->is_base_package : 0;
        $is_alacarte = isset($package_information->alacarte) ? $package_information->alacarte : 0;
        $type = "NA";
        if(isset($is_base_package)&& $is_base_package==1){
            $type = "Base";
        } else{
        if(isset($is_alacarte) && $is_alacarte == 1){
            $type = "Alacarte";
        } else{
            $type = "Addon";
        }
        }
        $data['package_name'] = $package_name;
        $data['sd_channels_count'] = $sd_channels_count;
        $data['hd_channels_count'] = $hd_channels_count;
        $data['tot_channels_count'] = $tot_channels_count;
        $data['channel_information'] = $channel_information;
        $data['type'] = $type;
        echo $this->load->view('content/partial_view/packageInfo.php', $data);
    }

    //get slots while adding commission
    public function getSlots()
    {   
        $sel_module_id = isset($_POST['module_id']) ? $_POST['module_id'] : 0;
        $int_did = isset($_POST['dealer']) ? $_POST['dealer'] : 0;
        $this->load->model(array('commissionsmodel'));
        if($int_did > 0) {
            $get_slots  = $this->commissionsmodel->getcommissionSlots($int_did);           
            if(count($get_slots)>0){
                $val = '';
                foreach ($get_slots as $key => $value) {

                    $slot_id = $value->commission_slot_id;

                    $show_slot = $value->from_val.' TO '.$value->to_val;

                  
                    $val .='<div class="row"> <section class="col col-2">                        
                                <label class="label"> </label>                                
                                <label class="input"> 
                                    <span><b> Slot - '.$show_slot.'</b></span>
                                </label> 
                                                            
                            </section>
                            <section class="col col-2">
                                <label class="label">Slab Type<span class="important_feild">*</span></label>
                                <label class="select" >
                                    <select name="flat_count_'.$slot_id.'" id="flat_count_'.$slot_id.'" onchange="checkVal1(this.value,'.$slot_id.','.$value->to_val.')">
                                        <option value="-1">Select</option>
                                        <option value="1" selected>%</option>
                                            <option value="2">Flat Based</option>                             
                                    </select>   
                                    <i></i>
                                </label>
                                
                            </section><section class="col col-2">                        
                                <label class="label"> Distributor Share</label>                                
                                <label class="input"> 
                                    <input type="text" name="dist_share_'.$slot_id.'" id="dist_share_'.$slot_id.'"  onkeyup="validateR(this,'."''". '),checkVal(this,this.value,'.$slot_id.','.$value->to_val.')" ruleset="[^0-9.]" cron_slot_id="'.$slot_id.'"/>
                                </label>                                
                            </section>
                            <section class="col col-2">                        
                                <label class="label"> Sub Distributor Share</label>                                
                                <label class="input"> 
                                    <input type="text" name="sub_dist_share_'.$slot_id.'" id="sub_dist_share_'.$slot_id.'" onkeyup="validateR(this,'."''". '),checkVal(this,this.value,'.$slot_id.','.$value->to_val.')" ruleset="[^0-9.]" cron_slot_id="'.$slot_id.'"/>
                                </label>                                
                            </section>
                            <section class="col col-2">                        
                                <label class="label"> LCO Share</label>                                
                                <label class="input"> 
                                    <input type="text" name="lco_share_'.$slot_id.'" id="lco_share_'.$slot_id.'" onkeyup="validateR(this,'."''". '),checkVal(this,this.value,'.$slot_id.','.$value->to_val.')" ruleset="[^0-9.]" cron_slot_id="'.$slot_id.'"/>
                                </label>                                
                            </section></div>';

            }

            echo  $val;
        }        
        

    }
}
	
	     //Function to check selected broadcastr channels exists ot not in product 
 //Written By venkat.
    public function checkSelectedChannels(){
        
        $broadcaster_id = ($this->input->post('broadcaster_id'))?$this->input->post('broadcaster_id'):0;
         $channel_ids_array = $this->input->post('channel_ids_array') ? $this->input->post('channel_ids_array') : array();
        $is_valid = 1;
       
        $channel_list_array =  $this->productsmodel->getChannelsBroadcaster($channel_ids_array,$broadcaster_id);
      
       //echo $this->db->last_query();die;
        
       //If channels list counts not matched  then product have another broadcaster chnanels
       if(isset($channel_list_array) && (count($channel_list_array)!= count($channel_ids_array))){
           $is_valid = 0;
       }
       echo $is_valid;
    }
    
    //Function to get last send OSD date written By venkat.(08-03-2019)
    
    public function getLastSendOsdDate(){
         if(!isset($_SESSION['user_data'])) redirect(base_url());
        $int_dealer_id = $_SESSION['user_data']->dealer_id;
        $int_int_stock_id = ($this->input->post('stock_id'))?$this->input->post('stock_id'):0;
        $int_int_backend_setup_id = $this->input->post('int_backend_setup_id') ? $this->input->post('int_backend_setup_id') : 0;
        $date_activated_date = $this->input->post('date_activated_date') ? $this->input->post('date_activated_date') : '';
        
       $last_sent_osd_date = "";
       $date_format = (isset($_SESSION['dealer_setting']->DATE_FORMAT))?$_SESSION['dealer_setting']->DATE_FORMAT:1;
        if($int_int_stock_id >0 && $int_int_backend_setup_id>0){
            
          $last_sent_osd_date =  $this->wsModel->getLastSendOsdDate($int_int_stock_id,$int_int_backend_setup_id,$int_dealer_id,$date_activated_date);
          
         $last_sent_osd_date =  getDateFormat($last_sent_osd_date,$date_format,1);
        }
        if($last_sent_osd_date!=''){
          echo $last_sent_osd_date;
        } else{
            echo "No data available.";
        }
    }
    
    public function getDuplicateChannels_details($customer_id=0, $dealer_id=0){
        $customer_id=isset($_POST['customer_id'])?$_POST['customer_id']:$customer_id;
        $dealer_id=isset($_POST['dealer_id'])?$_POST['dealer_id']:$dealer_id;
        $dupicate_prod = $this->productsmodel->getDuplicateChannels_details($customer_id, $dealer_id);
        echo $dupicate_prod;
    }
    
    public function getDuplicateChannels(){
        //$dealer = $_SESSION['user_data']->dealer_id;
        $temp_product_ids = array();
        $check_serial_number = $this->input->post('serial_number');
        $cid=isset($_POST['customer_id'])?$_POST['customer_id']:0;
        $dealer_id=isset($_POST['dealer_id'])?$_POST['dealer_id']:0;
        $products = $this->input->post('products');
        $fromeditservices=isset($_POST['fromeditservices'])?$_POST['fromeditservices']:0;
        $arr_stock_ids = array();
        //echo "<pre>";print_r($_POST);
        foreach ($check_serial_number as $key => $check_stock_id) {

            $temp_product_ids[$check_stock_id][] = $products[$key];
            $arr_stock_ids[] = $check_stock_id;
        }
        //get product id based on stock id's
        //echo "<pre>";print_r($temp_product_ids);
        $err='';
        foreach ($temp_product_ids as $stock_id => $product_arr) {
            $new_prod_id=array();
            foreach ($product_arr as $temp_product_id) {
                $new_prod_id[] = $temp_product_id;
            }
            //echo $temp_product_id;
            //print_r($new_prod_id);
            $check_alacarte_exist = 0;
            $prod_new_ids = 0;
            $prod_new_ids = implode(',', $new_prod_id);
            //echo $prod_new_ids;
            //if(!$this->input->post('authToken')){
            
            $serial_number_str=$this->productsmodel->getserial_number($stock_id);
            $allow_duplicate_channels = (isset($_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS)) ? $_SESSION['dealer_setting']->ALLOW_DUPLICATE_CHANNELS : 0;
            $dupicate_prod = 0;
            $show_details = 1;
            if (isset($allow_duplicate_channels) && $allow_duplicate_channels == 0) {

                $dupicate_prod = $this->productsmodel->checkDuplicateProducts($new_prod_id, $cid, $stock_id, $dealer_id, $deactiveserviceid = 0, $show_details = 1);
            }
            if ($show_details == 0) {
                if ($dupicate_prod == 1) {
                    $err .= "Duplicate Products Exist in selected (Bundles or Products).";
                } else if ($dupicate_prod == 2) {
                    $err .= "Duplicate Channels exist in selected (Bundles or Products).";
                } else if ($dupicate_prod == 3) {
                    $err .= "Trying to add existing (Bundles or Products) which are in Active state.";
                } else if ($dupicate_prod == 4) {
                    $err .= "Duplicate Channels exist in selected (Bundles or Products) which are in Active state.";
                }
            } else {
                if($dupicate_prod!=''){
                    if($fromeditservices==1){
                        $err .= "<b>$serial_number_str : </b>".$dupicate_prod;
                    }else{
                        $err .= $dupicate_prod;
                    }
                }
            }
            
        }
        $return_val= json_encode(array('msg'=>$err));
        echo $return_val;
    }
    
    
     //Function to get current month  paid customers written By Venkat.
    public function getCurrentMonthPaidCustomers(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $paid_customers_count =  $this->dashboardmodel->getPaidCustomers($int_dealerid, $int_employee_id);
        
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$paid_customers_count</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
     //Function to get current month  paid customers written By Venkat.
    public function getCurrentMonthUnPaidCustomers(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $unpaid_customers_count =  $this->dashboardmodel->getUnpaidCustomers($int_dealerid, $int_employee_id);
        
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$unpaid_customers_count</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
     //Function to get total stb's written By Venkat.
    public function getTotalStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $falg = $this->input->post("falg")?$this->input->post("falg"):0;
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        $show_goto_report = 0;
      $total_stbs =  $this->dashboardmodel->totalStbs('',$int_dealerid, $int_employee_id, '',$new_dashboard=0,$is_setup_box=1,$falg);
       
      if($total_stbs<=25000){
          $show_goto_report = 1;
      }
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo "$show_goto_report@@$html";
    }
    
     //Function to get total assigned stb's written By Venkat.
    public function getTotalAssignedStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalAssignedStbs($int_dealerid, $int_employee_id,0,$is_setup_box=1);
        
      $show_goto_report = 0;
     if($total_stbs<=25000){
          $show_goto_report = 1;
      }
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo "$show_goto_report@@$html";
    }
     //Function to get total unassigned stb's written By Venkat.
    public function getTotalUnassignedStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalNotAssignedStbs($int_dealerid, $int_employee_id,0,$is_setup_box=1);
      
      $show_goto_report = 0;
    if($total_stbs<=25000){
      $show_goto_report = 1;
    }
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo "$show_goto_report@@$html";
    }
   
     //Function to get total Active stb's written By Venkat.
    public function getTotalActiveStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalActiveSTBs($int_dealerid, $int_employee_id);
      $show_goto_report = 0;
if($total_stbs<=25000){
$show_goto_report = 1;
}
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
     echo "$show_goto_report@@$html";
    }
      //Function to get total Active stb's written By Venkat.
    public function getTotalDeActiveStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalDeactiveSTBs($int_dealerid, $int_employee_id);
      $show_goto_report = 0;
if($total_stbs<=25000){
$show_goto_report = 1;
}

       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
     echo "$show_goto_report@@$html";
    }
    
      //Function to get cuurrent month billing written By Venkat.
    public function getCurrentMonthBilling(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_currentMonth_bill =  $this->dashboardmodel->totalCurrentMonthBill($int_dealerid, $int_employee_id);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_currentMonth_bill</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
      //Function to get total complaints  written By Venkat.
    public function getTotalComplaints(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_complaints =  $this->dashboardmodel->totalComplaints($int_dealerid, $int_employee_id);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_complaints</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
       //Function to get total expiry services  written By Venkat.
    public function getTotalExpiryServices(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        
        $days = isset($_SESSION['dealer_setting']->ABOUT_TO_EXPIRED_SERVICES_DAYS)?$_SESSION['dealer_setting']->ABOUT_TO_EXPIRED_SERVICES_DAYS:1;
        $start_date = date('Y-m-d');
        //$days = $days+1;
        $end_date = Date('Y-m-d', strtotime("+$days days"));
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_expiry_services =  $this->dashboardmodel->count_service_going_to_expire($start_date, $end_date,$int_employee_id,$int_dealerid,$str_user_type);
        
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_expiry_services</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
       //Function to get total OTT devices  written By Venkat.
    public function getTotalDevices(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalStbs('',$int_dealerid, $int_employee_id, 0,0,$is_setup_box=2);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
    
        //Function to get total OTT devices  written By Venkat.
    public function getActiveDevices(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalActiveSTBs($int_dealerid, $int_employee_id, 0,$is_setup_box=2);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
           //Function to get total OTT devices  written By Venkat.
    public function getDeActiveDevices(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_stbs =  $this->dashboardmodel->totalDeactiveSTBs($int_dealerid, $int_employee_id, 0,$is_setup_box=2);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
            //Function to get total OTT devices  written By Venkat.
    public function getTotalClosedComplaints(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_complaints =  $this->dashboardmodel->totalClosedComplaints($int_dealerid, $int_employee_id);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_complaints</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
    public function getTotalPaidCustomers(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $lco_groups = 0;
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
            if($str_employee_parent_type=='RESELLER' && (isset($_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER) && $_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER==1) ){
                $lco_groups=isset($_SESSION['lco_employee_groups'])?$_SESSION['lco_employee_groups']:0;
            }
        }
        
      $total_paidCustomers =  $this->dashboardmodel->paidCustomers($int_dealerid, $int_employee_id, $str_user_type,$lco_groups);
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_paidCustomers</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
    public function getTotalUnpaidCustomers(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $lco_groups = 0;
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
            if($str_employee_parent_type=='RESELLER' && (isset($_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER) && $_SESSION['dealer_setting']->LCO_EMPLOYEE_GROUP_CUSTOMER==1) ){
                $lco_groups=isset($_SESSION['lco_employee_groups'])?$_SESSION['lco_employee_groups']:0;
            }
        }
        
      $total_unpaid =  $this->dashboardmodel->unpaidCustomers($int_dealerid, $int_employee_id, $str_user_type,$lco_groups);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_unpaid</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
    public function getTotalDueAmount(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        
      $total_amount =  $this->dashboardmodel->totalDueAmount($int_dealerid, $int_employee_id);
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_amount</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
    
    public function getTotalLcoDueAmount(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
        $lco_due_amount = 0;
      $lco_billing_list =  $this->EmployeeModel->lco_billing_list($int_employee_id,$str_user_type, $bill_startmonth="", $bill_endmonth = "",$paid_unpaid = 0,$lco_bill_flag=1);
       // echo $this->db->last_query();die;
      foreach($lco_billing_list as $list)
                              {
                                  $lco_due_amount=$list->lco_due_amount;
                              }
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$lco_due_amount</b></td></tr>";

      $html.="</table>";
      
      echo $html;
    }
        
     //Function to get total unassigned stb's written By Venkat.
    public function getTotalInventoryStbs(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $html = '';  
        $this->load->model('dashboardmodel');
        $int_employee_id = $_SESSION['user_data']->employee_id;
        $str_user_type = $_SESSION['user_data']->users_type;
        $int_dealerid = $_SESSION['user_data']->dealer_id;
        $str_employee_parent_type = $_SESSION['user_data']->employee_parent_type;
        $int_employee_parent_id = $_SESSION['user_data']->employee_parent_id;
        if ($str_employee_parent_type != '' && $str_employee_parent_type != NULL) {
            $str_user_type = $str_employee_parent_type;
            $int_employee_id = $int_employee_parent_id;
        }
       $int_flag = $this->input->post('flag')?$this->input->post('flag'):0; 
      $total_stbs =  $this->dashboardmodel->totalNotAssignedStbs($int_dealerid, $int_employee_id,$int_flag);
      //echo $this->db->last_query();die;
      $show_goto_report = 0;
    if($total_stbs<=25000){
      $show_goto_report = 1;
    }
       // echo $this->db->last_query();die;
      $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$total_stbs</b></td></tr>";

      $html.="</table>";
      
      echo "$html";
    }   
    /* Get Box Numbers for commission report -- Madhu 10-04-2019*/
    function get_commsion_box_details()
    {
    	$this->load->model('commissionsmodel');
    	$dealer_id = $_POST['dealer_id'];
    	$employee_id = $_POST['employee_id'];
		$commission_modules_id = $_POST['commission_modules_id'];
		$bill_month = $_POST['bill_month'];
		$slot_id = $_POST['slot_id'];
		$slot_range = $_POST['slot_range'];
		$users_type = $_POST['users_type'];
		$html='';
		$total_stbs =  $this->commissionsmodel->get_commsion_box_details($dealer_id, $employee_id,$commission_modules_id,$bill_month,$slot_id,$slot_range,$users_type);
		if(count($total_stbs)>0)
		{
			$html.="<ol class='stb_list'>";
			foreach($total_stbs as $stb)
			{
				$html.="<li>".$stb->serial_number."</li>";
				//$html.="<li>".$stb->serial_number."</li>";
			}
			$html.="</ol>";
		}
		echo $html;

    } 
    
    /**** Function to get reseller mapped packages  ****/ //---- Hemalatha
	
	public function get_reseller_mapped_packages(){
		$obj_emp = new employeemodel();
		$did = $_SESSION['user_data']->dealer_id;
		$package_type = $_POST['package_type'];	
		$reseller_mapped_packages = $obj_emp->getallResellerMappedPackages($did,$package_type);
		
		
		$html='';
		
	  
	     foreach($reseller_mapped_packages as $packages){  
		 
		 
                  if (isset($packages->display_name) && $packages->display_name != '') {
                            $pkg_dispalyname = "(".$packages->display_name.")";

                                    } else {
                                             $pkg_dispalyname = '';
                                       }

                                     
		   //$html.='<input type="hidden" name="channels['.$selectedChannel->channel_id.']" value="'.$selectedChannel->channel_category_id.'"/>';
		   $html.='<tr>
            <td class="label">'.$package_name=isset($packages->pname)?$packages->pname.$pkg_dispalyname:''.'</td>';
            $html.='<td><select name="perc_amt['.$packages->product_id.']" id="perc_amt" class="perc_amt" style="width:75px;">
              <option value="-1" > --Select-- </option><option value="1" >%</option><option value="2" >Rs.</option></select>';
              $html.=' <input type="text" name="amount['.$packages->product_id.']" id="amount_'.$packages->product_id.'" prod_id="'.$packages->product_id.'"  class="amount" value="" ruleset="[^0-9.]" style="width:105px;">'
                      . '<input type="hidden" name="package_name['.$packages->product_id.']" value="'.$package_name.'" class="perc_amt_package"></td>'; 
        $html.='</tr>';
						
			}      
		echo $html;
	}

	/*
 * check server is primary or not - srikanth
 */
public function check_has_primary_server(){
    
    $did = $_SESSION['user_data']->dealer_id;
    $q = "select count(*) as cnt from backend_setups where setup_for = 5 and dealer_id = $did and is_primary=1";
    $query = $this->db->query($q);
    $res = $query->row();
    if ($res->cnt == 0) {
       echo 0;
    } else {
        echo 1;
    }
    
}

/*
 * check server is primary or not -- srikanth
 */
public function chk_isprimary_has_mapping(){
    
    $did = $_SESSION['user_data']->dealer_id;
    $casid = $this->input->get('casid');
    $q = "select count(*) as cnt from backend_setups where setup_for = 5 and dealer_id = $did and is_primary=1";
    $query = $this->db->query($q);
    $res = $query->row();
    if ($res->cnt == 0) {
       //will allow to change
        echo 0;
    } else {
        
        //check it is primary if yes - check has mapped products if no 
        $chk_prim_qry = "select count(*) as cnt from backend_setups where setup_for = 5 and dealer_id = $did and is_primary=1 and backend_setup_id = $casid";
        $is_primary_query = $this->db->query($chk_prim_qry);
        $res1 = $is_primary_query->row();
        if($res1->cnt == 1){
            //it is primary
            //again check it has mapped products or not
            // $chk_has_mapp_prod = "select count(*) as cnt from backend_setups bs
            //         INNER JOIN eb_products p ON p.backend_setup_id = bs.backend_setup_id
            //         INNER JOIN eb_product_to_product_mapping pm ON pm.primary_server_product_id = p.product_id
            //         where bs.setup_for = 5 and bs.dealer_id = $did and bs.is_primary=1 and bs.backend_setup_id = $casid";
            
            
           $chk_has_mapp_prod = "select count(*) as cnt from eb_product_to_product_mapping pm
                   INNER JOIN eb_products p ON p.product_id = pm.primary_server_product_id
                   INNER JOIN backend_setups bs ON bs.backend_setup_id = p.backend_setup_id
                   where bs.setup_for = 5 and bs.dealer_id = $did and bs.is_primary=1 and bs.backend_setup_id = $casid";
            
            
            $chk_has_prod = $this->db->query($chk_has_mapp_prod);
            $res2 = $chk_has_prod->row();
            if($res2->cnt >= 1){
                //mapped products //will not allow to change
                echo 1;
            } else {
                //no mapped products //will allow to change
                echo 0;
            }
            
            
        }else{
            //it has primary server but this is not primary - we don't allow to change drop down - it will be disabled(will not allow to change) bcz it has mapped products and it is no
            echo 1;
        }
    }
    
}

//Function to check addon_after_base package validation in service extension added by Venkat (06-06-2019)
public function checkAddonAfterBaseInServiceExtension(){
    
    $this->load->model('dasmodel');
    
    $int_dealer_id = $_SESSION['user_data']->dealer_id;
    $int_stock_id = isset($_POST['stock_id'])?$_POST['stock_id']:'0';
    $addon_after_basepack_lov = isset($_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK) ? $_SESSION['dealer_setting']->ADDON_AFTER_BASEPACK : 0;
    //base package checking added by Swaroop ON May 1 2019
    $checkBaseExistStb = 1;
    
    if (isset($addon_after_basepack_lov) && $addon_after_basepack_lov == 1) {
        
        $checkBaseExistStb = $this->dasmodel->checkBaseExistOnStb($int_stock_id);
        
    }
    
    echo $checkBaseExistStb;
    
}
//payment gateway lco
public function paymentgatewayLco(){
          
$item_id  = $_POST['item_id'];
$val        = $_POST['val'];
$type       = $_POST['type'];
if(isset($item_id)&&isset($val)&&isset($type)){
$lco_query = $this->db->query('update paymenttype p ,backend_setups b set p.'.$type.' ='.$val.' where p.idpaymenttype  = b.payment_gateway and p.idpaymenttype ='. $item_id);
 //$this->db->last_query();  
 if($lco_query){
				return 1;
			} else {
				return 0;
			}
		}else{  return 0; }

}  
//Fetching Only MSO share for current month --Madhu 13-06-2019
public function fetchCurrentMonthMsoSharing()
{
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $this->load->model('dashboardmodel');
        $employeeId = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $loginUsersType = isset($_SESSION['user_data']->users_type)?$_SESSION['user_data']->users_type:'';
        if($loginUsersType=='DEALER')
        {
            $employeeId = 0;
        }
        $html= "";
        $obj_dash = new dashboardmodel();
        $current_month_msoShare = $obj_dash->current_month_msoShare($dealerId, $employeeId);
         $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$current_month_msoShare</b></td></tr>";

      $html.="</table>";
      
      echo "$html";
}
public function fetchMonthOutStandingAmount()
{
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $this->load->model('dashboardmodel');
        $employeeId = isset($_SESSION['user_data']->employee_id)?$_SESSION['user_data']->employee_id:0;
        $dealerId = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $loginUsersType = isset($_SESSION['user_data']->users_type)?$_SESSION['user_data']->users_type:'';
        if($loginUsersType=='DEALER')
        {
            $employeeId = 0;
        }
        $html= "";
        $obj_dash = new dashboardmodel();
        $current_month_outstanding = $obj_dash->current_month_outstanding($dealerId, $employeeId);
        $html.="<table border='0' cellspacing='5' cellpadding='5' width='100%'>";
	$html.="<tr><td align='center'><b>$current_month_outstanding</b></td></tr>";
        $html.="</table>";
      
      echo "$html";
}
    public function checkCustomerDueForSurrender()
    {
        $this->load->model('paymentsmodel');
        $customer_id = isset($_POST['customer_id'])?$_POST['customer_id']:'0';
        $customer_due_amount = isset($_SESSION['dealer_setting']->CHK_DUE_FOR_SURRENDER) ? $_SESSION['dealer_setting']->CHK_DUE_FOR_SURRENDER : 0;
        $invalid_data_string = "";
        if($customer_due_amount)
        {    
            $due_amount = $this->PaymentsModel->getCustomerDueAmountDetails($customer_id);
            $customer_due = $due_amount->amount;
            if ($customer_due > 0) {
                $invalid_data_string = "STB failed due to customer has pending amount";
            }
        }    
        echo trim($invalid_data_string);
    }
    
  //Function to check product Reconciliation status and how many customers have this product written By venkat (03-08-2019)
    public function checkReconciliationStatus(){
        
        if(!isset($_SESSION['user_data'])) redirect(base_url());
        $this->load->model('productsmodel');
        $int_dealer_id = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        $int_product_id = $this->input->post('product_id') ? $this->input->post('product_id') : 0;
        $date_curDate = date('Y-m-d');
        $status_result = $this->productsmodel->checkReconciliationStatus($int_product_id,$int_dealer_id,$date_curDate,$flag=1);
        //echo $this->db->last_query();
        //echo "<pre>";print_r($status_result);die;
        if(count($status_result)>0){ //If count graterthan means previous request not finished .
            if(isset($status_result->is_success) && $status_result->is_success==0){
                echo   "0@@This request assigned only one time for day.";
            } else{
                echo   "0@@Process already assigend.";
            }
          
            
        } else{
            
            $res = $this->productsmodel->checkproductsmappedcustomer($int_product_id,$int_dealer_id,$flag=1);
            
            if($res > 0){
               echo "1@@This product having $res customers.</br> NOTE:This request assigned only one time for day.";
            } else{
                 echo "2@@Do you want to process";
            
            }
        }
        
       
        
    }

    //function to get the amounts of employees after revertion
        function getRevertionAmount() {
        $paymentId  = isset($_POST['paymentId'])?$_POST['paymentId']:0;
        $result=array();
        $cf = new CommonFunctions();
         //$result = $CustomersModel->checkServiceActivationBy($serviceId);
        if($paymentId>0){
            $result = $cf->get_amounts_after_revertion($paymentId);
        }
        echo json_encode($result);
    }
    function disableAttribute()
    {
        $id=$_POST['id'];
        $result=$this->inventorymodel->disableAttribute($id);

    }

	/*
     * get team lead options srikanth
     */
    public function getTeamLeads(){
           $dealer_id = $_SESSION['user_data']->dealer_id;
           $sql=$this->db->query("SELECT e.employee_id, e.first_name FROM employee e 
                                  WHERE e.dealer_id='$dealer_id' and e.users_type='TEAMLEAD' and status=1"); 
             $options ='';
             if($sql){
                 $res = $sql->result();
                 if(count($res)>0){
                    $options .= "<option value=\"-1\">Select</option>";
                    foreach($res as $row){
                        $options .= "<option value='".$row->employee_id."'>".$row->first_name."</option>";
                    }
                 }
                    
             }
             echo $options;
       }

    // start Ramya on Oct 11 2019 
    public function getComplaintsubCategory(){
		$int_complaintcategory = ($this->input->post('complaintcategory'))?$this->input->post('complaintcategory'):0;
		$dealer_id= isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$sel_qry = "SELECT complaint_category_id,category,complaint_category_id,parent_category_id FROM complaint_categories WHERE dealer_id=$dealer_id  AND parent_category_id=$int_complaintcategory  order by category";
                
		$query = $this->db->query($sel_qry);

		//print_r($this->db->last_query());
		$category_data = 0;
		if($query && $query->num_rows()>0){
			if($query->num_rows() > 1) { $category_data = "<option value='-1'>Select Sub Category</option>"; }
			foreach($query->result() as $row){
				$category_data .= "<option value='".$row->complaint_category_id."' >".$row->category."</option>";
			}
		}
		echo $category_data;
	}

	// end Ramya on Oct 11 2019 

	// start Ramya on Oct 11 2019 

	public function getcategory_mappingemployee(){
		$int_complaintsubcategory = ($this->input->post('complaintsubcategory'))?$this->input->post('complaintsubcategory'):0;
		$int_customer_reseller_id = ($this->input->post('customer_reseller_id'))?$this->input->post('customer_reseller_id'):0;
		$customer_id = ($this->input->post('cid'))?$this->input->post('cid'):0;

		$dealer_id= isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
		$employee_category_data = 0;

		if($int_complaintsubcategory){
			// $qry = $this->db->query("select e.employee_id,e.dist_subdist_lcocode,e.business_name,e.first_name,e.last_name,concat(e.first_name,' ',e.last_name) as name
			// 	from customer_group cg
			// 	inner join customer_group_service_employee_map gs on gs.customer_group_id = cg.group_id and gs.complaint_category_id = $int_complaintsubcategory
			// 	inner join employee e on e.employee_id = gs.employee_id
			// 	where cg.customer_id=$customer_id and e.users_type='SERVICE' and e.dealer_id=$dealer_id");
			// if($qry && $qry->num_rows()>0){
			// 	if($qry->num_rows() > 1) { 
			// 		$employee_category_data = "<option value='-1'>Select Employee</option>";
			// 	}
			// 	foreach ($qry->result() as $values) {
			// 		$employee_category_data .= "<option  value='".$values->employee_id."'>".$values->name . "</option>";
			// 	}
				
			// }
			// else{
				$sel_qry = "SELECT e.employee_id,e.dist_subdist_lcocode,e.business_name,e.first_name,e.last_name,concat(e.first_name,' ',e.last_name) as name FROM employee_lco_map elm
					INNER JOIN employee e ON e.employee_id = elm.employee_id AND e.users_type='SERVICE'
					INNER JOIN employee_category_map ecm ON ecm.employee_id = elm.employee_id
					INNER JOIN complaint_categories cc  ON cc.complaint_category_id = ecm.complaint_category_id
		 			WHERE e.dealer_id=$dealer_id  AND elm.lco_employee_id=$int_customer_reseller_id 
		 			AND ecm.complaint_category_id =$int_complaintsubcategory AND cc.parent_category_id>0 ";
				$query = $this->db->query($sel_qry);
				if($query && $query->num_rows()>0){
					if($query->num_rows() > 1) { 
						$employee_category_data = "<option value='-1'>Select Employee</option>";
					}
					foreach ($query->result() as $values) {
						$employee_category_data .= "<option  value='".$values->employee_id."'>".$values->name . "</option>";
					}
					
				}
			//}
		}
		
		echo $employee_category_data;
	}
	// end Ramya on Oct 11 2019 
        
        /*
         * get employees under employee -- srikanth
         */
        public function get_users_under_emp(){
		
		$user_type = $_POST['usertype'];
		$from_stb_mgmt = isset($_POST['from_stb_mgmt'])?$_POST['from_stb_mgmt']:'0';
		$lco_deposite_flag = isset($_POST['lco_deposite_flag'])?$_POST['lco_deposite_flag']:"";
                $employee_id = isset($_POST['empid'])? $_POST['empid']: 0;
                $dist_employee_id = isset($_POST['distempid'])? $_POST['distempid']: 0;
                $serviceEmpId = isset($_POST['serviceEmpId'])? $_POST['serviceEmpId']: 0;
                $reseller_empid = isset($_POST['reseller_empid'])? $_POST['reseller_empid']: 0;
		$val='<option value="-1">Select</option>';
	
                
		$result = $this->CustomersModel->getLcoLocationEmp($user_type,$status=1,$employee_id,$lco_deposite_flag, $dist_employee_id, $serviceEmpId,$reseller_empid);
		//print_r($this->db->last_query()); exit;
		foreach($result as $users){
		
			
					 $code=$users->dist_subdist_lcocode;
					 $business_name = trim($users->business_name);
					 $uname = trim($users->first_name." ".$users->last_name);	
					 if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
							 $value=$code;
							 $name = "(".$business_name.")";
							 $title = $uname;
					 } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
							 $title = $code;
							 $name = "(".$uname.")";
							 $value= $business_name;
					  }
					  else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
							$title = $uname;
							$name = "(".$code.")";
							$value= $business_name;
					  }	
					  else {
							$title = 	$users->dist_subdist_lcocode;
							$name = "(".$uname.")";
							$value= $business_name;
					  }
					  
					  
			     if($from_stb_mgmt==1){
					$val .="<option  value='".$users->location_id."' title='".$title."'>".$value." ".$name."</option>";
				 }else{
					$val .="<option  value='".$users->employee_id."' title='".$title."'>".$value.' '.$name."</option>";
				 }
			}
		
		echo $val;
	}


        /*
         * get mapped list(employee lco) -- srikanth
         */
        public function get_emp_lco_mapped_list(){
            $service_emp_id = $_POST['emp_id'];
            $employee_lco_data = '';
            $sel_qry = "select e.lco_employee_id,mp.employee_id,mp.users_type,mp.business_name,mp.first_name,mp.last_name,mp.dist_subdist_lcocode from employee_lco_map e inner join employee mp on e.lco_employee_id = mp.employee_id  where e.employee_id = $service_emp_id AND e.status=1 ";
            $query = $this->db->query($sel_qry);
            if($query && $query->num_rows()>0){ 
                    $employee_lco_data = "<option value='-1'>Select</option>";
                    foreach ($query->result() as $users) {
                        $code=$users->dist_subdist_lcocode;
                        $business_name = trim($users->business_name);
                        $uname = trim($users->first_name." ".$users->last_name);	
                        if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="BNAME"){
                                        $value=$code;
                                        $name = "(".$business_name.")";
                                        $title = $uname;
                        } else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="NAME") { 
                                        $title = $code;
                                        $name = "(".$uname.")";
                                        $value= $business_name;
                         }
                         else if($_SESSION['dealer_setting']->IDENTIFICATION_FOR_LCO=="CODE"){
                                       $title = $uname;
                                       $name = "(".$code.")";
                                       $value= $business_name;
                         }	
                         else {
                                       $title = 	$users->dist_subdist_lcocode;
                                       $name = "(".$uname.")";
                                       $value= $business_name;
                         }
                        
                            $employee_lco_data .="<option  value='".$users->employee_id."' title='".$title."'>".$value.' '.$name."</option>";
                    }

            }
            echo $employee_lco_data;
            
        }
        
        
        /*
         * get mapped list(employee category) -- srikanth
         */
        public function get_emp_category_mapped_list(){
            $service_emp_id = $_POST['emp_id'];
           // $parentCateID = isset($_POST['parentCatID'])? $_POST['parentCatID'] : -1;
            $employee_category_data = '';
            
            $cond = '';
           /* if($parentCateID != -1){
                $cond .= " AND cc.parent_category_id = $parentCateID";
            }*/
            
            
            $sel_qry = "select cc.complaint_category_id, concat(cc.category, ' (',p.category, ')') as category from employee_category_map e inner join complaint_categories cc on cc.complaint_category_id = e.complaint_category_id inner join complaint_categories p on p.complaint_category_id = cc.parent_category_id  where e.employee_id = $service_emp_id $cond and e.status=1 order by category";
            $query = $this->db->query($sel_qry);
//            print_r($this->db->last_query()); exit;
            if($query && $query->num_rows()>0){ 
                    $employee_category_data = "<option value='-1'>Select</option>";
                    foreach ($query->result() as $cat) {
                            $employee_category_data .= "<option  value='".$cat->complaint_category_id."'>".$cat->category . "</option>";
                    }

            }
            echo $employee_category_data;
            
        }
        
        public function get_emp_category_to_map_list(){
            $service_emp_id = $_POST['emp_id'];
            $parentCateID = isset($_POST['parentCatID'])? $_POST['parentCatID'] : -1;
            $cate_list = '';
            /*$sel_qry = "select * from complaint_categories cc "
                    . "left join  employee_category_map ecm on ecm.complaint_category_id = cc.complaint_category_id "
                    . "left join  employee e on e.employee_id = ecm.employee_id where e.employee_id = $service_emp_id and ecm.complaint_category_id is not null";
            */
            
            $cond = '';
            if($parentCateID != -1){
                $cond .= " AND cc.parent_category_id = $parentCateID";
            }
            
            $sel_qry = "select cc.complaint_category_id,concat(cc.category, ' (',p.category, ')') as category from complaint_categories cc inner join complaint_categories p on p.complaint_category_id = cc.parent_category_id left join employee_category_map ecm on ecm.complaint_category_id = cc.complaint_category_id and ecm.employee_id = $service_emp_id and ecm.status=1 where ecm.complaint_category_id is null and cc.dealer_id=" . $_SESSION['user_data']->dealer_id . " $cond";
            
            $query = $this->db->query($sel_qry);
//            print_r($this->db->last_query()); exit;
            if($query && $query->num_rows()>0){ 
                    $cate_list = "<option value='-1'>Select</option>";
                    foreach ($query->result() as $cat) {
                            $cate_list .= "<option  value='".$cat->complaint_category_id."'>".$cat->category . "</option>";
                    }

            }
            echo $cate_list;
        }
        
        /*
         * get service emp data with mobile no
         */
        public function getserviceEmpData(){
            $mobileNo = $_POST['mobileNo'];
            $serviceEmpID = $this->EmployeeModel->getServiceEmpVthMobileNo($mobileNo);
            echo $serviceEmpID;
            
        }

        
        /*
         * get service emp data with mobile no
         */
        public function getUserLcoDetails(){
            $code = $_POST['code'];
            
            $result = 0;
            $sel_qry = "select e.employee_id from employee e  where e.dist_subdist_lcocode='$code'";
            $query = $this->db->query($sel_qry);
            if($query && $query->num_rows()>0){ 
                    $result =  $query->row()->employee_id;
            }
            echo $result;
            
        }


        //function for getting lco payment and deduction partial views by Swaroop ON oct 15 2019
        public function getLCODepositLedgerView(){
			$emp_model = new EmployeeModel();

	        $primary_key = $this->input->post('primary_key') ? $this->input->post('primary_key') : 0;
	        $credit_debit_flag = $this->input->post('credit_debit_flag') ? $this->input->post('credit_debit_flag') : 0;
	        // $primary_key = 128775;
	        // $credit_debit_flag = 0;

	        $data['lco_payment_details'] = array();
	        $data['deduction_details'] = array();

	        if($credit_debit_flag==1){
	        	$data['lco_payment_details'] = $emp_model->getLcoPaymentDetails($primary_key);
	        	echo $this->load->view('content/partial_view/lco_payment_details.php', $data);
	        }
	        else{
	        	$data['deduction_details'] = $emp_model->getDeductionDetails($primary_key);
	        	$data['date_format'] = (isset($_SESSION['dealer_setting']->DATE_FORMAT))?$_SESSION['dealer_setting']->DATE_FORMAT:1;
	        	echo $this->load->view('content/partial_view/deduction_details.php', $data);
	        }
	    }
            
     /*
      * get service employee data
      */  
       public function get_service_emp_details(){
           $serviceEmpID = $_POST['emp_id'];
           $serviceEmpMobileNumber = $this->EmployeeModel->getServiceEmpmobilenumber($serviceEmpID);
           echo $serviceEmpMobileNumber;
       }


    
    // Ajeet : get invoice details 17-10-2019

       public function getInvoiceDetails()
       {
        $invoice_details_array=array();
        $this->load->model('dasmodel');
       	$serial_number = $this->input->post('serial_number');
       	$mac=$this->input->post('mac');
       	$invoice_details_array=$this->dasmodel->getInvoiceDetails($serial_number);
       //	print_r($invoice_details_array);die;
       	if(count($invoice_details_array)>0)
       	{ 
       		$result =" <p style='color: #1B548D;background: #E6F0F3; width: 88%; padding: 10px 10px'>Invoice</p>
                        <span style='float: left;margin-left: 15px;padding: 10px 20px'>Serial Number:$serial_number ,  VC Number : $mac</span>
                       
       		           <table style='width:90%;' cellspacing='1' cellpadding='2' border='0' class='mygrid'>
                       
                        <th>Invoice Date </th>
                       <th>Invoice Number</th>
                        <th>Product Name</th>  
                        <th>Bill Amount</th>
                        <th>Tax Amount</th>
                        <th>Total Amount</th>
                        <th>Remarks</th>";
             
                  foreach ($invoice_details_array as $row) {
               	$result .=" <tr>
                        <td align='middle'>$row->bill_date</td>
                        <td align='middle'> $row->billing_id</td>
                        <td align='middle'>$row->pname</td>
                        <td align='right'>$row->bill_amount</td>
                         <td align='right'>$row->tax_amount</td>
                        <td align='right'>$row->total_amount</td>
                        <td align='middle'>$row->remarks</td>
                       </tr>";
                 }

                 $result .="</table>";

                 echo $result;

       	  } 
       }

        //function which checks the given addon date is greater than basepack end date of the stock and return 0 or 1 by hemalatha 2019-10-10
    public function checkbasedate_greater_thanaddon(){
        
        $product_id = $_POST['product_id'];
        $stock_id = $_POST['stock_id'];
        $end_date = $_POST['end_date'];
        $cid = $_POST['customer_id'];
        $dealer_id = isset($_SESSION['dealer_id'])?$_SESSION['dealer_id']:1;
        $base_prod_end_date_str='';
        $get_selected_product_array = $this->productsmodel->getproducts($product_id);
        $selected_prod_is_base_package=$get_selected_product_array[0]->is_base_package;
        $addon_after_base_pack_int = $this->change_pass_model->getLovValue('ADDON_AFTER_BASEPACK',$dealer_id);
        //If ADDON_AFTER_BASEPACK lov is 1 and extending an addon then check the basepack end date is greater than addon
            //if extending  basepack no nedd to check the conditio whether basepack end date is greater by hemalatha 15-10-2019
        if($addon_after_base_pack_int==1 && $selected_prod_is_base_package==0){
            $active_products=$this->productsmodel->getActiveProducts($cid,$stock_id,$dealer_id);
            if(count($active_products)>0){ // if customer already have active services then check that base pack end date
                foreach($active_products as $ac){
                    $acproduct_id=$ac->product_id;
                    $get_product_array = $this->productsmodel->getproducts($acproduct_id);
                    $prod_is_base_package=$get_product_array[0]->is_base_package;
                    if(1==$prod_is_base_package){
                        $prod_is_base_package_selected=1;
                        //$base_prod_end_date_str=date('d-m-Y',strtotime($ac->service_end_date));
                        $base_prod_end_date_str=date('Y-m-d',strtotime($ac->service_end_date));
                        break;
                    }
                }
            }
            $compare_end_date=date('Y-m-d',strtotime($end_date));
            /*echo "base_prod_end_date_str".$base_prod_end_date_str."end_date==".$compare_end_date;
            if($compare_end_date==$base_prod_end_date_str){
            	echo "equal";
            }
            else if($compare_end_date>$base_prod_end_date_str){
            	echo "more";
            }
            else if($compare_end_date<$base_prod_end_date_str){
            	echo "less";
            }*/
            if($base_prod_end_date_str!='' && $compare_end_date>=$base_prod_end_date_str){
                echo 1; 
            }else{
                echo 0;
            }
        }else{
            echo 0;
        }
    } 

 public function getReasonDetails(){
        $this->load->model('dasmodel');
        $int_reason_id = $this->input->post('reason_id')?$this->input->post('reason_id'):0;
        $arr_reason_details = $this->dasmodel->getReasonDetails($int_reason_id);
        $int_global_reason = isset($arr_reason_details->global_reason)?$arr_reason_details->global_reason:0;
        echo $int_global_reason;
    }

    public function check_isprimary_selected()
    {
        $result=array();
        $result['status'] = 0;
        $cond2 = "";
        $stype = isset($_POST['server_str'])?$_POST['server_str']:0;
        $stype=rtrim($stype,',');
        $did = isset($_SESSION['user_data']->dealer_id)?$_SESSION['user_data']->dealer_id:0;
        if($stype!=0)
        $cond2 = "AND backend_setup_id IN ($stype)";
        $sql_str = "select count(backend_setup_id) cnt from backend_setups where backend_setup_id IN ($stype) AND dealer_id=$did AND setup_for=5 AND is_primary=1";
        $query = $this->db->query($sql_str);
       //print_r($this->db->last_query());exit;
        $intrescount = $query->row()->cnt;
        if($intrescount>0){
           $result['status'] = 1; 
        }else{
            //check if db atlease one is_primary is available or not
            $sql_str_pm = "select count(backend_setup_id) cnt,display_name from backend_setups where dealer_id=$did AND setup_for=5 AND is_primary=1";
            $query_pm = $this->db->query($sql_str_pm);
            $intrescount_pm = $query_pm->row()->cnt;
            if($intrescount_pm==0){
                $result['status'] = 0;
                $result['message'] = "Please map a server as primary and add product in multiple servers. Use setting->preferences to add a server as Primary";
            }else{
                $display_name = $query_pm->row()->display_name;
                $result['status'] = 0;
                $result['message'] = "Please select a primary server type in mulitple servers selected (Server Type Dropdown).";
                //$result['message'] = "Please select a primary server type in mulitple servers selected.".$display_name." is primary server";
            }
        }
        //print_r($result);exit;
        echo json_encode($result);
        //echo json_encode(array('status_message'=>$statusMessage, 'statusRes' => $statusRes));
    }
                        
       
} 
?>
