<?php session_start(); ?>
<?php
	include_once '../lib/config/config.php';
	include_once '../../system/libraries/SMSSender.php';
	
		if(isset($_POST['registerComplaint'])){
				if(!isset($_SESSION['timestamp'])) $_SESSION['timestamp']='';
				if($_SESSION['timestamp']!=$_POST['timestamp']){
					$_SESSION['timestamp']=$_POST['timestamp'];
					$customer_id = $_POST['customer_id'];
					$pin = $_POST['customer_pin'];
					$msg = $_POST['msg'];
					$_SESSION['timestamp']=$_POST['timestamp'];

					if(authorise_customer($customer_id,$pin)){
						$smssender = new SMSSender();
							$customer = getCustomerDetails($customer_id);
							$dealer_id = $customer['dealer_id'];
							if($dealer_id!='-1' || $dealer_id!=NULL )
							{
								$mob = $customer['mobile_no'];
								$tkt_number = generate_ticket($customer_id,$dealer_id);
								$res = register_complaint($customer_id,$dealer_id,$msg,$tkt_number);
								if($res==1){ echo 'Complaint Posted to the Cable Operator Successfully.</br><font color="red">Please note your ticket ID :'.$tkt_number.'</font>';
										
								$customer_mobile = $customer['mobile_no'];
								$customer_name = $customer['first_name']. ' '.$customer['last_name'];
								$customer_city = $customer['city'].'/'.$customer['district'];
								sendBalanceNotification($customer_id,$dealer_id,$customer_mobile);
								notifyServicePersonal($dealer_id,$tkt_number,$customer_mobile,$customer_name,$customer_city);
								/*
								$sms = 'Your Complaint has been registered with the ticket number : '.$tkt_number;
								sendNotification($customer_mobile,$dealer_id,$sms);*/
								$data = array('ticket_number'=>$tkt_number);
								$smssender->sendSMS(1,$data,$customer_mobile,$dealer_id);
								}
								else echo 'Error in saving complaint!';
						}else{
								echo 'Cannot Process your Request at the moment!';
							}

					}else{
						echo 'You are Unautorised to put a complaint';
					}



				}else{
					echo 'The complaint already registered! or you have refreshed(F5) the page. Please reload the page.';
				}
	}
?>
<html>
<body>
<form method="post" name="createcomplaint">
		<input type="hidden" name="timestamp" value="<?=time();?>" />
		<table>
			<tr>
				<td>Enter Your ID</td>
				<td>:</td>
				<td><input type="text" name="customer_id"></td>
			</tr>
			<tr>
				<td>Enter Your PIN<br/>(Personal Identification Number)</td>
				<td>:</td>
				<td><input type="text" name="customer_pin"></td>
			</tr>
			<tr>
				<td>Enter Complaint</td>
				<td>:</td>
				<td>
					<textarea name="msg"></textarea>
				</td>
			</tr>
			<tr>
			<td></td>
			<td></td>
			<td>
				<input type="submit" name="registerComplaint" value="Place Complaint"/>
			</td>
			</tr>
		</table>
	</form>
</body>
</html>
<?php
function authorise_customer($customer_id,$pin){
	$sql = "SELECT count(customer_id) as num_rows FROM customer WHERE customer_id=$customer_id AND pin=$pin";
	$res = mysql_fetch_object(mysql_query($sql));
	$res = $res->num_rows;
	if($res==1){
		return 1;
	}else{
		return 0;
	}
	
}
function register_complaint($customer_id,$dealer_id,$msg,$tkt){
	$sql = "INSERT INTO complaint(`customer_id`,`tkt_number`,`dealer_id`,`date`,`description`) VALUES($customer_id,'$tkt',$dealer_id,now(),'$msg')";
	$res = mysql_query($sql) or die(mysql_error());
	if($res)
	return 1;
	else
	return 0;
}
function get_dealer_id($customer_id){
	$sql = "SELECT dealer_id FROM customer WHERE customer_id=$customer_id";
	$res = mysql_query($sql);
	if(mysql_num_rows($res)){
		$val = mysql_result($res,0);
		return $val;
	}else{
		return '-1';
	}
}
function generate_ticket($customer_id,$dealer_id){
	$prefix = 'CTS';
	$tkt_number = $prefix.'/'.$customer_id.'/'.$dealer_id.'/'.date('ymdHis');
	return $tkt_number;
}
function cusmobile($customer_id){
	$sql = "SELECT mobile_no FROM customer WHERE customer_id=$customer_id";
	$res = mysql_query($sql);
	if(mysql_num_rows($res)){
		$val = mysql_result($res,0);
		return $val;
	}else{
		return 0;
	}

}
function customerdue($customer_id,$dealer_id){
	$sql = "Select coalesce(b.total_amount,0) - SUM(coalesce(d.paid_amount,0)) thism_pending
			from acc_billing b 
			LEFT JOIN acc_payment_details d ON b.billing_id=d.billing_id 
			Where b.billing_id = (SELECT MAX(`billing_id`) FROM `acc_billing` WHERE `customer_id`=$customer_id)
			And b.dealer_id=$dealer_id 
			And b.customer_id=$customer_id";
	$res = mysql_query($sql);
	if(mysql_num_rows($res)){
		$val = mysql_result($res,0);
		return $val;
	}else{
		return 0;
	}

}

function sendNotification($mobile,$dealer_id,$sms)
	{
		
		$data = getSMSDetails($dealer_id);
		If ((strlen($data['user_name'])>0) && (strlen($data['password'])>0))
		{

			$url = "http://enterprise.smsgupshup.com/GatewayAPI/rest?method=SendMessage&send_to=";
			$url .= $mobile;
			$url .= "&msg=".urlencode($sms);
			$url .= "&msg_type=TEXT&userid=".$data['user_name']."&auth_scheme=plain&password=".$data['password']."&v=1.1&format=text";
			$ch = curl_init($url);
			//echo '<br>'.$url;

			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
		}
	}

	function getSMSDetails($d){
		$sql = "SELECT l1.value SMS_USER,l2.value SMS_PASSWORD FROM lovtable l1 
			LEFT JOIN lovtable l2 On l1.dealer_id=l2.dealer_id AND l2.setting = 'SMS_PASSWORD'
			LEFT JOIN lovtable l3 On l1.dealer_id=l3.dealer_id 
			WHERE l1.dealer_id=$d AND l1.setting = 'SMS_USER'";
		$res = mysql_query($sql);
		$user_name = '';
		$password = '';
		
		if(mysql_num_rows($res)){
			$user_name=mysql_result($res,0);
			$password=mysql_result($res,0,1);
			
		}else
		{die('getSMSDetails-1-Error!');}
		$data = array(
			'user_name' => $user_name,
			'password' => $password,
			
		);
		return $data;
	}
	function getCustomerDetails($cid){
		$sql = "SELECT * FROM customer WHERE customer_id=$cid";
		$res = mysql_query($sql);
		if($res) return mysql_fetch_array($res);
		else return NULL;
	}
	function sendBalanceNotification($customer_id,$dealer_id,$customer_mobile)
	{
			$sql = "Select coalesce(b.total_amount,0) - SUM(coalesce(d.paid_amount,0)) pending_amount
					from acc_billing b 
					LEFT JOIN acc_payment_details d ON b.billing_id=d.billing_id 
					Where b.billing_id = (SELECT MAX(`billing_id`) FROM `acc_billing` WHERE `customer_id`=$customer_id)
					And b.dealer_id=$dealer_id 
					And b.customer_id=$customer_id";
			$res = mysql_query($sql);
			if(mysql_num_rows($res)){
				$pending_amount = mysql_result($res,0);
				if($pending_amount>0){
						$msg = "Your account has a balance due of. : ".$pending_amount;
						sendNotification($customer_mobile,$dealer_id,$msg);
				}
			}
	}
	function notifyServicePersonal($did,$tkt_number,$customer_mobile,$customer_name,$customer_city){
			$sql_servicetype = "SELECT value FROM lovtable WHERE dealer_id=$did AND setting='USERTYPE_SERVICE'";
			$res = mysql_query($sql_servicetype);
			if(mysql_num_rows($res)){
				$service_users = mysql_result($res,0);

				$sql = "SELECT mobile_no phone,dealer_id FROM employee WHERE dealer_id = $did AND users_type='".$service_users."';";
				$res = mysql_query($sql);
				if(mysql_num_rows($res)){
					while($row = mysql_fetch_array($res)){
						$msg = "New Complaint has been registered with ticket no. : ".$tkt_number." for ".$customer_name." in ".$customer_city.". Customer Mobile: ".$customer_mobile;
						sendNotification($row['phone'],$row['dealer_id'],$msg);
					}
				}
			}
	}
	
?>

