<?php

class BulkOperation extends CI_Controller {

    var $skipped_rows_cust = "";
    var $error_string = "";

    public function __construct() {
        parent::__construct();
        /* if(!isset($_SESSION['user_data'])) redirect(base_url());
          if($_SESSION['user_data']->pwdchangereq) redirect('welcome/changePassword');
          if(isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits']) redirect(base_url()); */
        $this->load->model('bulkoperationmodel', 'blkopr');
        $this->load->model('dasmodel');
        $this->load->model('productsmodel');
        $this->load->model('channelsmodel');
        $this->load->model('change_pass_model');
        $this->load->model('EmployeeModel');
        $this->load->model('inventorymodel');
        $this->load->model('stb_messaging_model');
        $this->load->model('customersmodel');
        $this->load->model('channelsmodel');
        $this->load->model('Process_Model');
        $this->load->model('PaymentsModel');
        $this->load->model('Customer_verification_model');
        $this->load->model('lco_billing_model');
        $this->load->model('postpaid_billing_model');
        $this->load->model('wsModel');
        $this->load->library('upload');
        $this->load->helper(array('form', 'url'));
    }

    public function test() {
        $this->load->model('bulkoperationmodel', 'blkopr');
        $blkopr = new bulkoperationmodel();
        $blkopr->bulkActivations();
    }

    public function bulkOperations() {
        //!((strcasecmp($_SESSION['user_data']->users_type,'DEALER')==0) || (strcasecmp($_SESSION['user_data']->users_type,'ADMIN')==0))?redirect('welcome/accessdenied'):0;
        !((isset($_SESSION['dealer_setting']->SHOW_BULK_OPERATIONS) && $_SESSION['dealer_setting']->SHOW_BULK_OPERATIONS == 1)) ? redirect('welcome/accessdenied') : '';
        !((isset($_SESSION['casAccess']->BULK_ACT) && ($_SESSION['casAccess']->BULK_ACT == 1)) || (isset($_SESSION['casAccess']->BULK_DEACT) && ($_SESSION['casAccess']->BULK_DEACT == 1)) || (isset($_SESSION['casAccess']->BULK_REACT) && ($_SESSION['casAccess']->BULK_REACT == 1))) ? redirect('welcome/accessdenied') : '';
        $is_user_blocked = $this->employeemodel->isUserBlocked($_SESSION['user_data']->employee_id, $_SESSION['user_data']->users_type, $_SESSION['user_data']->dealer_id);
        ($is_user_blocked == 0) ? "" : redirect('welcome/accessdenied');
        //code added by chakri to hide the add service operation from view for unpaid lco 
        $hideactivationdeactivation = 0;
        if (isset($_SESSION['user_data']->users_type) && $_SESSION['user_data']->users_type == "RESELLER") {//to check is unpaid lco
            $this->load->model(array('cronjobmodel'));
            $cron = new Cronjobmodel();
            $current_date = date('Y-m-d');
            $res = $cron->getlcos($current_date, 2, $_SESSION['user_data']->employee_id);
            //echo $this->db->last_query(); count($res);exit;
            if (isset($res) && count($res) > 0 && isset($res[0]->is_unpaidlco) && $res[0]->is_unpaidlco == 1) {
                $hideactivationdeactivation = 1;
            } else {
                $hideactivationdeactivation = 0;
            }
        }


        $data = array(
            'title' => 'ezybill | Bulk Activation Deactivations',
            'content' => 'content/inventory/bulkoprn',
            'hideactivationdeactivation' => $hideactivationdeactivation
        );

        $data['show'] = 1;
        $current_day = date('d');
        $data['bill_edit_date'] = $bill_edit_date = isset($_SESSION['dealer_setting']->BILL_EDIT_DATE) ? $_SESSION['dealer_setting']->BILL_EDIT_DATE : 0;
        $data['bill_edit_date1'] = '';
        if (isset($_SESSION['dealer_setting']->LCO_BILLTYPE) && $_SESSION['dealer_setting']->LCO_BILLTYPE == 1) {
            if ($bill_edit_date > 0 && $current_day > $bill_edit_date) {
                $data['show'] = 0;
                $data['bill_edit_date1'] = "<div class=\"bill_edit_note\">Sorry for Inconvenience.<br/> You are not Authorised to Bulk Activation After " . $bill_edit_date;

                if ($bill_edit_date == 1 || $bill_edit_date == 21 || $bill_edit_date == 31) {
                    $data['bill_edit_date1'] .= "st </div>";
                } else if ($bill_edit_date == 2 || $bill_edit_date == 22) {
                    $data['bill_edit_date1'] .= "nd </div>";
                } else if ($bill_edit_date == 3 || $bill_edit_date == 23) {
                    $data['bill_edit_date1'] .= "rd </div>";
                } else {
                    $data['bill_edit_date1'] .= "th </div>";
                }
            }
        }

        $data['enable_unpaid_cust_deactivation'] = $enable_unpaid_cust_deactivation = (isset($_SESSION['dealer_setting']->ENABLE_UNPAID_CUSTOMER_DEACTIVATION)) ? $_SESSION['dealer_setting']->ENABLE_UNPAID_CUSTOMER_DEACTIVATION : 1; // getting lov value ENABLE_UNPAID_CUSTOMER_DEACTIVATION - by archana 2018-04-13
        //added by Ashwin to show or hide bulk operation activate, deactivate options based on bill_edit_date lov value

        if (!isset($_SESSION['form_token'])) {
            $token = md5(uniqid(rand(), TRUE));
            $_SESSION['form_token'] = $token;
            $data['form_token'] = $_SESSION['form_token'];
        } else {
            $data['form_token'] = $_SESSION['form_token'];
        }

        $data['servers'] = $this->dasmodel->count_servers();
        $data['is_user_blocked'] = $is_user_blocked = $this->employeemodel->isUserBlocked($_SESSION['user_data']->employee_id, $_SESSION['user_data']->users_type, $_SESSION['user_data']->dealer_id);

        $data['users_type'] = $_SESSION['user_data']->users_type;

        $blkopr = new bulkoperationmodel();
        $com = new change_pass_model();
        if ($this->input->post('cancel')) {
            unset($_SESSION['data_type']);
            unset($_SESSION['oprn']);
            unset($_SESSION['trans_id']);
            unset($_SESSION['reason_id']);
            unset($_SESSION['remarks']);
            unset($_SESSION['product_id']);
            unset($_SESSION['server']);
            unset($_SESSION['deact_product_id']);
            unset($_SESSION['reason_name']);

            redirect('bulkoperation/bulkOperations');
        }

        if ($this->input->post('uploadCsv')) {

            $data_type = $this->input->post('Data'); //get the serial number or vc number
            $status = $this->input->post('oprn'); //condition for activate or deactivation
            $server = $this->input->post('stype'); //condition for activate or deactivation
            $product_id = ($this->input->post('product_id')) ? $this->input->post('product_id') : '0'; //get the product id
            $deact_product_id = array();

            if ($status == 2) {
                $reason_id = $this->input->post('reason_id');
                if ($reason_id == 17 && $enable_unpaid_cust_deactivation == 0) { // unpaid customer deactivation condition added by archana - 13-04-2018
                    $this->session->set_flashdata("success_msg", "Unpaid customer deactivation failed");
                    redirect('bulkoperation/bulkOperationsnew');
                }
                $deact_product_id = ($this->input->post('deact_product_id')) ? $this->input->post('deact_product_id') : array(); //get the product id for deactivation
                if (in_array("0", $deact_product_id)) {
                    $deact_product_id = array();
                }
            } else if ($status == 1) {
                $reason_id = $this->input->post('reason_for_act');
            }
            $remarks = $this->input->post('remarks');
            $file = $_FILES['csvFile']['tmp_name'];
            $handle = fopen($file, "r");
            $isFirstRecord = true;
            //get the serial numbers or vc numbers
            $serial_numbers = array();
            $_SESSION['data_type'] = $data_type;
            $_SESSION['oprn'] = $status;
            $_SESSION['reason_id'] = $reason_id;
            $_SESSION['remarks'] = $remarks;
            $_SESSION['product_id'] = $product_id;
            $_SESSION['server'] = $server;
            $_SESSION['deact_product_id'] = $deact_product_id;
            $deactivation_reason = $this->CustomersModel->getReasons($reason_id);

            if (count($deactivation_reason) > 0) {
                $reason_name = $deactivation_reason[0]->reason;
            } else {
                $reason_name = '';
            }

            $_SESSION['reason_name'] = $reason_name;
            unset($_SESSION['trans_id']);

            $trans_id = time();
            $_SESSION['trans_id'] = $trans_id;

            //get the product mapping reseller_id
            //loop through the csv file and insert into database			
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                if (!$isFirstRecord) {

                    $num = count($data);
                    for ($c = 0; $c < $num; $c++) {
                        //echo trim($data[$c]);
                        if (trim($data[$c]) != "") {
                            $serial_numbers[] = "'" . trim($data[$c]) . "'";
                        }
                    }
                    //$bulk_act_deact_trans_id=$blkopr->stbExists(trim($data[$c]),$data_type,$status,$trans_id);
                }
                $isFirstRecord = false;
            }
            $bulk_act_deact_trans_id = $blkopr->stbExists($serial_numbers, $data_type, $status, $trans_id, $product_id, $reason_name, $deact_product_id, $server);
            redirect('bulkoperation/bulkOperations');
        }

        if ($this->input->post('start') && isset($_SESSION['form_token']) && ($_SESSION['form_token'] == $this->input->post('form_token'))) {

            $data_type = $this->input->post('data_type'); //get the serial number or vc number
            $status = $this->input->post('operation'); //condition for activate or deactivation
            $reason_id = $_SESSION['reason_id'];
            $remarks = $_SESSION['remarks'];
            $deact_product_ids = isset($_SESSION['deact_product_id']) ? $_SESSION['deact_product_id'] : array();
            //$product_id = (isset($_SESSION['product_id']))?$_SESSION['product_id']:'0';			
            $product_id = ($this->input->post('product')) ? $this->input->post('product') : '0';
            $bulk_operation_process_id = $this->input->post('bulk_operation_process_id');
            $trans_id = $_SESSION['trans_id'];
            $deactivation_reason = $this->CustomersModel->getReasons($reason_id);
            if (count($deactivation_reason) > 0) {
                $reason_name = $deactivation_reason[0]->reason;
            } else {
                $reason_name = '';
            }
            if ($reason_name == 'Unpaid Customer' || $reason_name == 'Temporary Deactivation' || $reason_name == 'Temporary Activation') {
                $product_id = 0;
            }
            $users_type = (isset($_SESSION['user_data']->users_type)) ? $_SESSION['user_data']->users_type : '';

            $setting_value = (isset($_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO)) ? $_SESSION['dealer_setting']->SHOW_BLOCKED_PRODUCT_FOR_MSO : 0;

            $activated = $blkopr->bulkActivateDeactivate($trans_id, $status, $reason_id, $remarks, $product_id, $is_user_blocked, $bulk_operation_process_id, $reason_name, $users_type, $setting_value);
            $counts = explode('@@', $activated);
            $count_message = '';
            $fail_message = '';

            if ($status == 1) {
                $var = 'Activation';
                if (isset($counts[0]) && $counts[0] > 0) {
                    $count_message.=$counts[0] . ' STBs activated successfully.';
                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has done " . $counts[0] . " STBs activated";
                    $com->updateLog($comment);
                }
                if (isset($counts[1]) && $counts[1] > 0) {
                    $fail_message.=$counts[1] . ' STBs failed.';
                }
            } else if ($status == 2) {
                $var = 'Deactivation';
                if (count($deact_product_ids) == 0) {
                    $deactivation_by = 'STBs';
                } else {
                    $deactivation_by = 'Package(s)';
                }

                if ($counts[0] > 0) {
                    $count_message = "$counts[0] $deactivation_by deactivated successfully.";
                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has done " . $counts[0] . " STBs deactivated";
                    $com->updateLog($comment);
                }
                if ($counts[1] > 0) {
                    $fail_message.="$counts[1] STBs failed.";
                }
            } else if ($status == 3) {
                $var = 'Reactivation';

                if ($counts[0] > 0) {
                    $count_message = $counts[0] . ' STBs Reactivated successfully.';
                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has  Reactivated " . $counts[0] . " STBs ";
                    $com->updateLog($comment);
                }
                if ($counts[1] > 0) {
                    $fail_message.=$counts[1] . ' STBs failed.';
                }
            }
            if ($counts[0] > 0) {
                $message = 'Bulk ' . $var . ' is done successfully.';
            } else {
                $message = 'Invalid Data.';
            }

            $messages = $message . ' ' . $count_message . ' ' . $fail_message;
            $this->Process_Model->updateProcessMessage($bulk_operation_process_id, $messages);
            $this->session->set_flashdata("success_msg", $messages);

            $invalid_data_string = '';
            $invalid_data_string = $blkopr->get_invalid_data($trans_id);
            //echo $this->db->last_query();
            //echo $invalid_data_string;exit;
            //function to save error file by Ashwin
            //echo "<pre>";print_r($invalid_data_string);
            //echo $invalid_data_string['remarks'];die;
            if ($invalid_data_string) {

                //echo $invalid_data_string;exit;

                /*                 * ***Start Code Storing Result In A File,Displaying In a Pop Up **** */
                $bulk_operation_process_id = $this->input->post('bulk_operation_process_id');
                $upload_path = uploads_path();
                $uppath = $upload_path . 'temp/';
                $basename = time();
                $file_name = "$uppath/invalid_data_$basename.html";
                $fname = "invalid_data_$basename.html";
                $fh = fopen($file_name, 'w+') or die("can't open file");
                //echo $invalid_data_string;die;
                fwrite($fh, $invalid_data_string['remarks']);
                //die();
                fclose($fh);
                $link = base_url() . $file_name;
                //$res .="<a href='$link'>$file_name</a>";


                $this->Process_Model->updateProcessFilename($bulk_operation_process_id, $fname);

                $this->session->set_flashdata("invalid_data_file", "<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here to see the invalid data !</a><br/>");


                //*****End Code Storing Result In A File,Displaying In a Pop Up *****/
            }



            unset($_SESSION['data_type']);
            unset($_SESSION['oprn']);
            unset($_SESSION['trans_id']);
            unset($_SESSION['reason_id']);
            unset($_SESSION['remarks']);
            unset($_SESSION['product_id']);
            unset($_SESSION['reason_name']);
            unset($_SESSION['server']);
            unset($_SESSION['deact_product_id']);
            unset($_SESSION['form_token']);
            redirect('bulkoperation/bulkOperations');
        }

        $data['active_remarks'] = isset($_SESSION['activation']) ? $_SESSION['activation'] : array();
        $data['deactive_remarks'] = isset($_SESSION['deactivation']) ? $_SESSION['deactivation'] : array();

        //$reactive_remarks=array('Reactivation'=>($_SESSION['reactivation']));
        //$data['reasons']=array_merge($data['active_remarks'],$data['deactive_remarks']);

        $data['product_list'] = $blkopr->get_all_product($is_user_blocked, $status = 1);

        $this->load->view('common/inner-template.php', $data);
    }

    public function bulkOperationsnew($int_dealerId=0,$int_employeeId=0,$from_lco_portal=0) {


        // Below code for bulk operation from lco portal 
        if ($int_dealerId > 0 && $int_employeeId > 0 && $from_lco_portal == 1) {
            $_SESSION['from_lco_portal']=$from_lco_portal;
            $_SESSION['int_dealerId']=$int_dealerId;
            $_SESSION['int_employeeId']=$int_employeeId;
            redirect('bulkoperation/bulkOperationsnew');
        }  
        ////////////////////////////////////////////////
        
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
      
        //!((strcasecmp($_SESSION['user_data']->users_type,'DEALER')==0) || (strcasecmp($_SESSION['user_data']->users_type,'ADMIN')==0))?redirect('welcome/accessdenied'):0;
        !((isset($_SESSION['dealer_setting']->SHOW_BULK_OPERATIONS) && $_SESSION['dealer_setting']->SHOW_BULK_OPERATIONS == 1)) ? redirect('welcome/accessdenied') : '';
        !((isset($_SESSION['casAccess']->BULK_ACT) && ($_SESSION['casAccess']->BULK_ACT == 1)) || (isset($_SESSION['casAccess']->BULK_DEACT) && ($_SESSION['casAccess']->BULK_DEACT == 1)) || (isset($_SESSION['casAccess']->BULK_REACT) && ($_SESSION['casAccess']->BULK_REACT == 1))) ? redirect('welcome/accessdenied') : '';
        $is_user_blocked = $this->employeemodel->isUserBlocked($_SESSION['user_data']->employee_id, $_SESSION['user_data']->users_type, $_SESSION['user_data']->dealer_id);
        ($is_user_blocked == 0) ? "" : redirect('welcome/accessdenied');


        $data = array(
            'title' => 'ezybill | Bulk Activation Deactivations',
            'content' => 'content/inventory/newbulkoprn'
        );
        //below variable will be used for hiding header, footer, menu bars for service extension page when accessed from lco portal
        $data['from_lco_portal'] = isset($_SESSION['from_lco_portal'])?$_SESSION['from_lco_portal']:0;
        $trans_id = time().rand(10,1000);
        $_SESSION['trans_id'] = $trans_id;
        $data['show'] = 1;
        $current_day = date('d');
        $data['bill_edit_date'] = $bill_edit_date = isset($_SESSION['dealer_setting']->BILL_EDIT_DATE) ? $_SESSION['dealer_setting']->BILL_EDIT_DATE : 0;
        $data['bill_edit_date1'] = '';
        if (isset($_SESSION['dealer_setting']->LCO_BILLTYPE) && $_SESSION['dealer_setting']->LCO_BILLTYPE == 1) {
            if ($bill_edit_date > 0 && $current_day > $bill_edit_date) {
                $data['show'] = 0;
                $data['bill_edit_date1'] = "<div class=\"bill_edit_note\">Sorry for Inconvenience.<br/> You are not Authorised to Bulk Activation After " . $bill_edit_date;

                if ($bill_edit_date == 1 || $bill_edit_date == 21 || $bill_edit_date == 31) {
                    $data['bill_edit_date1'] .= "st </div>";
                } else if ($bill_edit_date == 2 || $bill_edit_date == 22) {
                    $data['bill_edit_date1'] .= "nd </div>";
                } else if ($bill_edit_date == 3 || $bill_edit_date == 23) {
                    $data['bill_edit_date1'] .= "rd </div>";
                } else {
                    $data['bill_edit_date1'] .= "th </div>";
                }
            }
        }
        $data['enable_exp_services_activation'] = isset($_SESSION['dealer_setting']->ENABLE_EXPIRY_SERVICES_ACTIVATION)?$_SESSION['dealer_setting']->ENABLE_EXPIRY_SERVICES_ACTIVATION:0;
        $data['enable_unpaid_cust_deactivation'] = $enable_unpaid_cust_deactivation = (isset($_SESSION['dealer_setting']->ENABLE_UNPAID_CUSTOMER_DEACTIVATION)) ? $_SESSION['dealer_setting']->ENABLE_UNPAID_CUSTOMER_DEACTIVATION : 1; // getting lov value ENABLE_UNPAID_CUSTOMER_DEACTIVATION - by archana 2018-04-13
        //added by Ashwin to show or hide bulk operation activate, deactivate options based on bill_edit_date lov value
        
         $data['allow_multiple_products_in_bulkoperations_int'] = $allow_multiple_products_in_bulkoperations_int = (isset($_SESSION['dealer_setting']->ALLOW_MULTIPLE_PRODUCTS_IN_BULKOPERATIONS)) ? $_SESSION['dealer_setting']->ALLOW_MULTIPLE_PRODUCTS_IN_BULKOPERATIONS : 0;

        if (!isset($_SESSION['form_token'])) {
            $token = md5(uniqid(rand(), TRUE));
            $_SESSION['form_token'] = $token;
            $data['form_token'] = $_SESSION['form_token'];
        } else {
            $data['form_token'] = $_SESSION['form_token'];
        }

        $data['servers'] = $this->dasmodel->count_servers();
        $data['is_user_blocked'] = $is_user_blocked = $this->employeemodel->isUserBlocked($_SESSION['user_data']->employee_id, $_SESSION['user_data']->users_type, $_SESSION['user_data']->dealer_id);

        $data['users_type'] = $_SESSION['user_data']->users_type;

        $mapped_unblock_lcos = $this->productsmodel->getmappedLCOs(209, $is_blocked = 0);
        //get lco which are mapped but blocked to selected product by SRAVANI
        $mapped_blocked_lcos = $this->productsmodel->getmappedLCOs(209, $is_blocked = 1);

        $data['mapped_unblock_lcos'] = $mapped_unblock_lcos;
        $data['mapped_blocked_lcos'] = $mapped_blocked_lcos;
        $blkopr = new bulkoperationmodel();
        $com = new change_pass_model();
        if ($this->input->post('cancel')) {
            unset($_SESSION['data_type']);
            unset($_SESSION['oprn']);
            unset($_SESSION['trans_id']);
            unset($_SESSION['reason_id']);
            unset($_SESSION['remarks']);
            unset($_SESSION['product_id']);
            unset($_SESSION['server']);
            unset($_SESSION['deact_product_id']);
            unset($_SESSION['reason_name']);

            redirect('bulkoperation/bulkOperationsnew');
        }

        if ($this->input->post('sbtUploadCsv')) {
            //echo "<pre>";print_r($_POST);exit;
            $data_type = $this->input->post('Data'); //get the serial number or vc number
            $status = $this->input->post('oprn'); //condition for activate or deactivation
            $server = $this->input->post('stype'); //condition for activate or deactivation
            if($allow_multiple_products_in_bulkoperations_int==0){
                if($status==1)
                    $product_id = ($this->input->post('products_actdeact_to_single')) ? array($this->input->post('products_actdeact_to_single')) : array(); //get the product id  
                else
                  $product_id = ($this->input->post('products_actdeact_to_single')) ? $this->input->post('products_actdeact_to_single') : array(); //get the product id
            }else{
                $product_id = ($this->input->post('products_actdeact_to')) ? $this->input->post('products_actdeact_to') : array(); //get the product id
            }
            

            if ($status == 2) {
                $reason_id = $this->input->post('reason_id');                
            } else if ($status == 1) {
                $reason_id = $this->input->post('reason_for_act');
            }
            $remarks = $this->input->post('remarks');
            $transaction_id = $this->input->post('transaction_id');
            $file = $_FILES['csvFile']['tmp_name'];
            $handle = fopen($file, "r");
            $isFirstRecord = true;
            //get the serial numbers or vc numbers
            $serial_numbers = array();
            $_SESSION['data_type'] = $data_type;
            $_SESSION['oprn'] = $status;
            $_SESSION['reason_id'] = $reason_id;
            $_SESSION['remarks'] = $remarks;
            $_SESSION['product_id'] = $product_id;
            $_SESSION['server'] = $server;
            $deactivation_reason = $this->CustomersModel->getReasons($reason_id);

            if (count($deactivation_reason) > 0) {
                $reason_name = $deactivation_reason[0]->reason;
            } else {
                $reason_name = '';
            }

            $_SESSION['reason_name'] = $reason_name;


            //get the product mapping reseller_id
            //loop through the csv file and insert into database			
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

                if (!$isFirstRecord) {

                    $num = count($data);
                    for ($c = 0; $c < $num; $c++) {
                        //echo trim($data[$c]);
                        if (trim($data[$c]) != "") {
                            $sno = str_replace(array("'", "\"", "&quot;", "^"), "", htmlspecialchars($data[$c]));
                            $serial_numbers[] = "'" . trim($sno) . "'";
                        }
                    }
                    //$bulk_act_deact_trans_id=$blkopr->stbExists(trim($data[$c]),$data_type,$status,$trans_id);
                }
                $isFirstRecord = false;
            }
            //echo "<pre>";print_r($serial_numbers);
            
            $bulk_act_deact_trans_id = $blkopr->stbExistsnew($serial_numbers, $data_type, $status, $transaction_id, $product_id, $reason_name, $server, $remarks, $reason_id);
            
            if($bulk_act_deact_trans_id==-1){
                $this->session->set_flashdata("invalid_data_file", "Stock does not exist or error in file csv");
            }

            unset($_SESSION['data_type']);
            unset($_SESSION['oprn']);
            unset($_SESSION['trans_id']);
            unset($_SESSION['reason_id']);
            unset($_SESSION['remarks']);
            unset($_SESSION['product_id']);
            unset($_SESSION['server']);
            unset($_SESSION['deact_product_id']);
            unset($_SESSION['reason_name']);
            unset($_SESSION['form_token']);
            redirect('bulkoperation/bulkOperationsnew');
        }
        
        if(isset($_SESSION['from_lco_portal'])){
            
            $this->load->model('InitialValModel');
            $initialvalmodel = new InitialValModel();
            $initialvalmodel->setSessionRemark($_SESSION['from_lco_portal']);
            //print_r($_SESSION['activation']);
        }
        
        $data['active_remarks'] = isset($_SESSION['activation']) ? $_SESSION['activation'] : array();
        $data['deactive_remarks'] = isset($_SESSION['deactivation']) ? $_SESSION['deactivation'] : array();

        //$reactive_remarks=array('Reactivation'=>($_SESSION['reactivation']));
        //$data['reasons']=array_merge($data['active_remarks'],$data['deactive_remarks']);

        $data_format = $data['date_format'] = (isset($_SESSION['dealer_setting']->DATE_FORMAT)) ? $_SESSION['dealer_setting']->DATE_FORMAT : 1;
        $data['product_list'] = $blkopr->get_all_product($is_user_blocked, $status = 1);
        $trans_search_id = "";
        if ($this->input->post('submit_trans')) {
            $trans_search_id = $this->input->post('transaction_id_search');
        }
        if ($this->input->post('trans_clear')) {
            redirect('bulkoperation/bulkoperationsnew');
        }
        //echo "<pre>";print_r($_POST);exit;
        //$data['records']=$this->Process_Model->getTransProcessList($trans_search_id);

        $operation_name = 'bulk_operation_with_file_upload';
        $users_type = $_SESSION['user_data']->users_type;
        $data['records'] = $this->Process_Model->getTransProcessList($trans_search_id, $operation_name, $users_type);

        $this->load->view('common/inner-template.php', $data);
    }

    //function for bulkoperationscronjob
    public function bulkOperationscron() {
        $controller_name = 'bulkoperation';
        $function_name = 'bulkOperationsCronjobStart';
        $file_to_lock = 'lock_bulkoperation.lock';
        $this->lock_file($controller_name, $function_name, $file_to_lock);
    }

    //function to create a process and lock it by sending the function and controller names as arguments to stop concurrent execution of same cronjob by hemalatha
    public function lock_file($controller_name, $function_name, $file_to_lock) {
        $real_path = $_SERVER['DOCUMENT_ROOT'];
        $basename = $_SERVER['PHP_SELF'];
        $fullpath = $real_path . $basename;
        $cmd_path = substr($fullpath, 0, strrpos($fullpath, "index.php"));
        // echo "in lock_file ".$trans_id.' '.$pid.' '.$start_val.' '.$limit;
        $this->Cmd_path = $cmd_path . "index.php " . $controller_name . " ";
        $job_path = "/usr/bin/php " . $this->Cmd_path . $function_name;

        $upload_path = uploads_path();
        $lockFilePath = realpath("./") . "/" . $upload_path;
        $lock_file = $lockFilePath . $file_to_lock;
        $filehandle = fopen($lock_file, "a+");
        if ($filehandle === False) {   //echo "File Open Failed\n";
            die();
        }
        if (flock($filehandle, LOCK_EX | LOCK_NB)) {
            ftruncate($filehandle, 0);
            fputs($filehandle, getmypid());
            $out_array = array();
            exec($job_path, $out_array);
            print_r($out_array); //Gets the out put from executed file
            flock($filehandle, LOCK_UN); // don't forget to release the lock
        } else {
            // throw an exception here to stop the next cron job
            exec("ps auxwww|grep $controller_name |grep -v grep|grep $function_name", $pslist);
            if (count($pslist) >0){
                // already some process is running wait until it is completed
                write_to_file("already some process is running wait until it is completed");
            }else{
                //it means no process is running release the lock
                write_to_file("no process is running release the lock");
                flock($filehandle, LOCK_UN);
            }
        }
        fclose($filehandle);
    }

    //function to send bulkoperations to activatedeactivate from process by hemalatha
    public function bulkOperationsActivatieDeactivate() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $this->db->save_queries = FALSE;
        $trans_id = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $bulk_operation_pid = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
        $start_val = ($this->uri->segment(5)) ? $this->uri->segment(5) : 0;
        $limit = ($this->uri->segment(6)) ? $this->uri->segment(6) : 0;
        $server_id = ($this->uri->segment(7)) ? $this->uri->segment(7) : 1;
        $backend_setup_id = ($this->uri->segment(8)) ? $this->uri->segment(8) : 0;
        $int_activated=0;
        $blkopr = new bulkoperationmodel();
        $uploaded_opns_details = $blkopr->getTransactionDetails($trans_id);
        
        if(count($uploaded_opns_details)>0){
            $status = $uploaded_opns_details->act_type;
            $dealerId = $uploaded_opns_details->dealer_id;
            write_to_file("In Bulkoperations controller bulkOperationsActivatieDeactivate trans_id ".$trans_id." bulk_operation_pid ".$bulk_operation_pid." start_val".$start_val."  limit".$limit.' $status '.$status);
            $operation_id = $this->Process_Model->getoperation_id('bulk_operation_with_file_upload',$dealerId);
            if ($status == 1) { //1 means activations
                $int_activated = $blkopr->bulkActivations($trans_id, $bulk_operation_pid, $start_val, $limit, $server_id, $backend_setup_id,0,$operation_id);
            } else if ($status == 2) { // 2 for deactivations
                $int_activated = $blkopr->bulkDeactivations($trans_id, $bulk_operation_pid, $start_val, $limit, $server_id, $backend_setup_id,0,$operation_id);
            } else if ($status == 3) { // 3 for reactivations
                $int_activated = $blkopr->bulkReactivations($trans_id, $bulk_operation_pid, $start_val, $limit, $server_id, $backend_setup_id,0,$operation_id);
            }
        }
        echo $int_activated;
    }

    public function bulkOperationsCronjobStart() {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $this->db->save_queries = FALSE;

        $this->load->model('appsmodel');        
        $process_model = new Process_Model();
        $post_paid = new Postpaid_Billing_Model();
        $fname = '';
        //$com = new change_pass_model();
        //get all the process which are in status=1(Running)
        $dealer_values = $post_paid->getDealerValues();
        foreach ($dealer_values as $get_dealervalue) {
            $dealer_id = $get_dealervalue->dealer_id;
            $operation_name = 'bulk_operation_with_file_upload';
            $operationid = $process_model->getoperation_id($operation_name, $dealer_id);
            $getCasWiseTransactions = $this->Process_Model->getCasWiseProcessBulkopnsList($operationid);
            
            $cas_servers_count = count($getCasWiseTransactions);
            if($cas_servers_count > 0){
                $fork_cas_ids_all = array();
                foreach ($getCasWiseTransactions as $cas_wise_trans_details){
                    $backend_setup_id = isset($cas_wise_trans_details->backend_setup_id)?$cas_wise_trans_details->backend_setup_id:0;
                    $transaction_ids = isset($cas_wise_trans_details->transaction_ids)?$cas_wise_trans_details->transaction_ids:'';
                    $serialized_transaction_ids = serialize($transaction_ids);
                    $encoded_transaction_ids = urlencode($serialized_transaction_ids);
                    $cas_status = isset($cas_wise_trans_details->cas_status)?$cas_wise_trans_details->cas_status:1;
                    if (count($fork_cas_ids_all) >= $cas_servers_count) {
                        $fork_cas_id_single = pcntl_waitpid(-1, $child_fork_status);
                        unset($fork_cas_ids_all[$fork_cas_id_single]); // Remove PID that exited from the list
                    }
                    $fork_cas_id_single = pcntl_fork();                  
                    if ($fork_cas_id_single) { // Parent
                        if ($fork_cas_id_single < 0) {
                            // Unable to fork process, handle error here
                            continue;
                        } else {
                            // Add child PID to tracker array
                            // Use PID as key for easy use of unset()
                            $fork_cas_ids_all[$fork_cas_id_single] = $fork_cas_id_single;
                        }
                    } else {
                        $this->db->reconnect();//Reconnecting the db connection each child process
                        $function_name = 'casWiseBulkOperationsStart'; 
                        $fullpath = $_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'];
                        $cmd_path =  substr($fullpath,0,strrpos($fullpath, "index.php"));                                   
                        $job_path = "/usr/bin/php ".$cmd_path."index.php bulkoperation ".$function_name.' '.$backend_setup_id.' '.$encoded_transaction_ids.' '.$cas_status.' '.$operationid;  
                        //write_to_file('======Unpaid_Customer===$job_path==========='.$job_path);
                        $out_array = array();
                        exec($job_path, $out_array);
                        print_r($out_array);
                        exit(0);                        
                    }
                }
                foreach ($fork_cas_ids_all as $fork_id) {
                    pcntl_waitpid($fork_id, $child_fork_status);
                    unset($fork_cas_ids_all[$fork_id]);
                }
            }
            while(pcntl_waitpid(0, $child_fork_status) != -1);
            $this->db->reconnect(); // Reconnecting the db connection after child process completed            
        }
        echo "Cron job execution Completed";
    }

    public function bulkproductmapping() {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        !(isset($_SESSION['casAccess']->BULK_PRODUCT_MAPPING_UNMAPPING) && ($_SESSION['casAccess']->BULK_PRODUCT_MAPPING_UNMAPPING == 1)) ? redirect('welcome/accessdenied') : '';

        !($_SESSION['user_data']->users_type == 'DEALER' || $_SESSION['user_data']->users_type == 'ADMIN' || ($_SESSION['user_data']->users_type == 'EMPLOYEE' && $_SESSION['user_data']->employee_parent_id == 0)) ? redirect('welcome/accessdenied') : '';


        $res = "<div class='error'>";
        $stringData = "";
        $skipedcustomers = 0;
        $map = 0;
        $employee_id = $_SESSION['user_data']->employee_id;
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $objchangepass = new change_pass_model();
        $cf = new CommonFunctions();
        $this->load->helper(array('static_text_helper'));
        $process_log_path = getConfigValues('process_log_url');
        $process_log_message = getConfigValues('process_log_message');

        //conditions for bulk product edit - riya
        // condition for bulk product mapping added by Swaroop
        if ($this->input->post('editbutton')) {

            try {

                $guid_value = $this->input->post('guid_value_unmapping') ? $this->input->post('guid_value_unmapping') : '';
                $guiDetals = $this->inventorymodel->getGuidDetails($guid_value);

                if (isset($guiDetals) && count($guiDetals) > 0) {

                    throw new Exception($process_log_message);
                } else {

                    $operation_name = 'Bulk_product_mapping_Unmapping';

                    $gui_post_details = array('guid' => $guid_value,
                        'operation_name' => $operation_name,
                        'dealer_id' => $dealer_id,
                        'createdby' => $employee_id
                    );

                    $gui_responseId = $cf->insertGuidDetails($gui_post_details);
                }

                $this->load->library('FileHandling');
                $file_handle = new FileHandling();
                $csvfile = $file_handle->csvFileReading($_FILES);
                //echo '<pre>';print_r($csvfile);die;
                $map = 1;
                $isValidFile = true;
                if ($_FILES['uploaded_file']['name'] != "") {
                    $max_file_size = 26214400;
                    $file_size = $_FILES['uploaded_file']['size'];
                    if ($file_size > 0) {
                        $allowedExtensions = array("csv");
                        if (!in_array(end(explode(".", strtolower($_FILES['uploaded_file']['name']))), $allowedExtensions)) {
                            $res .= 'Invalid file. Please upload CSV file. <br/>';
                        } else if ($file_size >= $max_file_size && $file_size > 0) {
                            $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['file']['size'] . " Bytes.";
                        } else if (count($csvfile) == 0) {
                            $res .= "Empty file uploaded";
                        } else {

                            $res .= $this->populatebulkProductEdit($csvfile) . ' Product(s) Edited.<br/>';

                            for ($i = 0; $i < $csvfile; $i++) {
                                $productsInserted = $res;
                                //echo $this->skipped_rows_cust;die();
                                $this->skipped_rows_cust = rtrim($this->skipped_rows_cust, ",");
                                if (trim($this->skipped_rows_cust) != '')
                                    $skipedcustomers = count(explode(",", $this->skipped_rows_cust));

                                $sql_str = "SELECT employee_id,username, concat(first_name,' ',last_name) AS Name FROM employee WHERE employee_id=" . $_SESSION['user_data']->employee->employee_id . " AND dealer_id=" . $_SESSION['user_data']->dealer_id;
                                $query = $this->db->query($sql_str);
                                if ($query && $query->num_rows() > 0) {
                                    $name = $query->row()->Name;
                                    $empId = $query->row()->employee_id;
                                    $userName = $query->row()->username;
                                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has done bulk product editing.| $productsInserted | $skipedcustomers :Products are not edited ";
                                }
                                $objchangepass->updateLog($comment);


                                if (($skipedcustomers > 0)) {
                                    $res .= " Product(s) Rows ($this->skipped_rows_cust) not Mapped!<br/>";
                                    /////// Start Code Storing Result In A File,Displaying In a Pop Up //////
                                    $upload_path = uploads_path();

                                    $path = $upload_path . "temp";
                                    $basename = time();
                                    //$file_name = "$path/stbCustomer_$basename.html";
                                    $file_name = "$path/Bulk_Product_$basename.txt";
                                    $fh = fopen($file_name, 'w+') or die("can't open file");
                                    $stringData .= $this->error_string;
                                    fwrite($fh, $stringData);
                                    fclose($fh);
                                    $link = base_url() . $file_name;
                                    //$res .="<a href='$link'>$file_name</a>";
                                    $res .= "<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here To Check Errors  !</a>";
                                    //////////End Code Storing Result In A File,Displaying In a Pop Up //////////
                                }
                                break;
                            }
                        }
                    } else {
                        $res .= "The uploaded file size should be less than or equal to 2Mb";
                    }
                } else {
                    $res .= "Please upload a file";
                }
            } catch (Exception $ex) {

                $exception_msg = $ex->getMessage();
                write_to_file($exception_msg, $process_log_path);
            }
        }



        // condition for bulk product mapping added by Swaroop
        if ($this->input->post('btnimport')) {

            try {
                $guid_value = $this->input->post('guid_value_mapping') ? $this->input->post('guid_value_mapping') : '';
                $guiDetals = $this->inventorymodel->getGuidDetails($guid_value);

                if (isset($guiDetals) && count($guiDetals) > 0) {

                    throw new Exception($process_log_message);
                } else {

                    $operation_name = 'Bulk_product_mapping_Unmapping';

                    $gui_post_details = array('guid' => $guid_value,
                        'operation_name' => $operation_name,
                        'dealer_id' => $dealer_id,
                        'createdby' => $employee_id
                    );

                    $gui_responseId = $cf->insertGuidDetails($gui_post_details);
                }

                $this->load->library('FileHandling');
                $file_handle = new FileHandling();
                $csvfile = $file_handle->csvFileReading($_FILES);
                $map = 1;
                $isValidFile = true;
                if ($_FILES['uploaded_file']['name'] != "") {
                    $max_file_size = 26214400;
                    $file_size = $_FILES['uploaded_file']['size'];
                    if ($file_size > 0) {
                        $allowedExtensions = array("csv");
                        if (!in_array(end(explode(".", strtolower($_FILES['uploaded_file']['name']))), $allowedExtensions)) {
                            $res .= 'Invalid file. Please upload CSV file. <br/>';
                        } else if ($file_size >= $max_file_size && $file_size > 0) {
                            $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['file']['size'] . " Bytes.";
                        } else if (count($csvfile) == 0) {
                            $res .= "Empty file uploaded";
                        } else {

                            $res .= $this->populateProductMapping($csvfile) . ' Product(s) Mapped<br/>';

                            for ($i = 0; $i < $csvfile; $i++) {
                                $productsInserted = $res;
                                //echo $this->skipped_rows_cust;die();
                                $this->skipped_rows_cust = rtrim($this->skipped_rows_cust, ",");
                                if (trim($this->skipped_rows_cust) != '')
                                    $skipedcustomers = count(explode(",", $this->skipped_rows_cust));

                                $sql_str = "SELECT employee_id,username, concat(first_name,' ',last_name) AS Name FROM employee WHERE employee_id=" . $_SESSION['user_data']->employee->employee_id . " AND dealer_id=" . $_SESSION['user_data']->dealer_id;
                                $query = $this->db->query($sql_str);
                                if ($query && $query->num_rows() > 0) {
                                    $name = $query->row()->Name;
                                    $empId = $query->row()->employee_id;
                                    $userName = $query->row()->username;
                                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has done bulk product mapping.| $productsInserted | $skipedcustomers :Products are not mapped ";
                                }
                                $objchangepass->updateLog($comment);


                                if (($skipedcustomers > 0)) {
                                    $res .= " Product(s) Rows ($this->skipped_rows_cust) not Mapped!<br/>";
                                    /////// Start Code Storing Result In A File,Displaying In a Pop Up //////
                                    $upload_path = uploads_path();

                                    $path = $upload_path . "temp";
                                    $basename = time();
                                    //$file_name = "$path/stbCustomer_$basename.html";
                                    $file_name = "$path/Bulk_Product_$basename.txt";
                                    $fh = fopen($file_name, 'w+') or die("can't open file");
                                    $stringData .= $this->error_string;
                                    fwrite($fh, $stringData);
                                    fclose($fh);
                                    $link = base_url() . $file_name;
                                    //$res .="<a href='$link'>$file_name</a>";
                                    $res .= "<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here To Check Errors  !</a>";
                                    //////////End Code Storing Result In A File,Displaying In a Pop Up //////////
                                }
                                break;
                            }
                        }
                    } else {
                        $res .= "The uploaded file size should be less than or equal to 2Mb";
                    }
                } else {
                    $res .= "Please upload a file";
                }
            } catch (Exception $ex) {

                $exception_msg = $ex->getMessage();
                write_to_file($exception_msg, $process_log_path);
            }
        }

        // condition for bulk product unmapping added by Swaroop

        if ($this->input->post('import')) {

            try {

                $guid_value = $this->input->post('guid_value_edit') ? $this->input->post('guid_value_edit') : '';
                $guiDetals = $this->inventorymodel->getGuidDetails($guid_value);

                if (isset($guiDetals) && count($guiDetals) > 0) {

                    throw new Exception($process_log_message);
                } else {

                    $operation_name = 'Bulk_product_mapping_Unmapping';

                    $gui_post_details = array('guid' => $guid_value,
                        'operation_name' => $operation_name,
                        'dealer_id' => $dealer_id,
                        'createdby' => $employee_id
                    );

                    $gui_responseId = $cf->insertGuidDetails($gui_post_details);
                }


                $map = 2;

                $isValidFile = true;
                if ($_FILES['uploaded_file']['name'] != "") {
                    $this->load->library('FileHandling');
                    $file_handle = new FileHandling();
                    $csvfile = $file_handle->csvFileReading($_FILES);
                    //$max_file_size = 2097152;
                    $max_file_size = 26214400;
                    $file_size = $_FILES['uploaded_file']['size'];
                    if ($file_size > 0) {
                        $allowedExtensions = array("csv");
                        if (!in_array(end(explode(".", strtolower($_FILES['uploaded_file']['name']))), $allowedExtensions)) {
                            $res .= 'Invalid file. Please upload CSV file. <br/>';
                        } else if ($file_size >= $max_file_size && $file_size > 0) {
                            $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['file']['size'] . " Bytes.";
                        } else if (count($csvfile) == 0) {
                            $res .= "Empty file uploaded";
                        } else {

                            $res .= $this->populateProductUnmapping($csvfile) . 'Product(s) UnMapped<br/>';

                            for ($i = 0; $i < $csvfile; $i++) {
                                $productsInserted = $res;
                                $this->skipped_rows_cust = rtrim($this->skipped_rows_cust, ",");
                                if (trim($this->skipped_rows_cust) != '')
                                    $skipedcustomers = count(explode(",", $this->skipped_rows_cust));

                                $sql_str = "SELECT employee_id,username, concat(first_name,' ',last_name) AS Name FROM employee WHERE employee_id=" . $_SESSION['user_data']->employee->employee_id . " AND dealer_id=" . $_SESSION['user_data']->dealer_id;
                                $query = $this->db->query($sql_str);
                                if ($query && $query->num_rows() > 0) {
                                    $name = $query->row()->Name;
                                    $empId = $query->row()->employee_id;
                                    $userName = $query->row()->username;
                                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has done bulk product unmapping.| $productsInserted | $skipedcustomers :Products are not mapped ";
                                }
                                $objchangepass->updateLog($comment);


                                if (($skipedcustomers > 0)) {
                                    $res .= " Product(s) Rows ($this->skipped_rows_cust) not Unmapped!<br/>";
                                    /////// Start Code Storing Result In A File,Displaying In a Pop Up //////
                                    $upload_path = uploads_path();
                                    $path = $upload_path . "temp";
                                    $basename = time();
                                    //$file_name = "$path/stbCustomer_$basename.html";
                                    $file_name = "$path/Bulk_Product_$basename.txt";
                                    $fh = fopen($file_name, 'w+') or die("can't open file");
                                    $stringData .= $this->error_string;
                                    fwrite($fh, $stringData);
                                    fclose($fh);
                                    $link = base_url() . $file_name;
                                    //$res .="<a href='$link'>$file_name</a>";
                                    $res .= "<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here To Check Errors  !</a>";
                                    //////////End Code Storing Result In A File,Displaying In a Pop Up //////////
                                }
                                break;
                            }
                        }
                    } else {
                        $res .= "The uploaded file size should be less than or equal to 2Mb";
                    }
                } else {
                    $res .= "Please upload a file";
                }
            } catch (Exception $ex) {

                $exception_msg = $ex->getMessage();
                write_to_file($exception_msg, $process_log_path);
            }
        }
    
        $res .= "</div>";
        $data = array('title' => 'Bulk Product Mapping',
            'content' => 'content/Bulkoperations/bulkproductmapping',
            'res' => $res,
            'map' => $map,
        );
        
        $data['guiId'] =$guid = $cf->getGUID();
        //$data['servers'] = $das_object->count_servers();
        $this->load->view('common/inner-template', $data);
    }

    // Bulk product mapping checking starts here by Ashok
    public function populateProductMapping($worksheet) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());

        $amount_lovvalue = isset($_SESSION['dealer_setting']->ALLOW_AMOUNT_FOR_LCO_SHARE) ? $_SESSION['dealer_setting']->ALLOW_AMOUNT_FOR_LCO_SHARE : 0;
        $number_of_columns = 8;
        $product_id = 0;
        $i = 0;
        $objbulk = new bulkoperationmodel();
        $_SESSION['bpm_transaction_id'] = time();
        $did = $_SESSION['user_data']->dealer_id;
        $isFirstRow = true;
        $i = 1;
        $row_counter = 0;
        $invalidData = 0;
        // foreach ($worksheet as $key=> $val) {
        $cid = 0;
        $isEmptyRow = false;
        // if (!$isFirstRow) {
        $row_counter++;

        $slno = "";
        $slno1 = "";
        $username = "";
        $product_name = "";
        $percentage = "";
        $start_date = "";
        $end_date = "";
        $remarks = "";

        $cas = "";
        $backend_setup_id = 0;

        $index = 1;

        foreach ($worksheet as $key => $val) {
            $headername_array = $val;
            $headername_validationarray = array("*SlNo" => "1", '*User Name' => "2", '*Product Name' => "3", '*Server' => "4", '*Amount Type(1-Percentage,2-Amount)' => "5", '*Percentage' => "6", '*Start Date' => "7", '*End Date' => "8", 'Remarks' => "9");
            $missing_columns_array = array_values(array_flip(array_diff_key($headername_validationarray, $headername_array)));
            if (!empty($missing_columns_array)) {
                $error = "Invalid File Uploaded.";
                if (isset($error) && $error != '') {
                    $this->session->set_flashdata("fail", $error);
                    redirect('bulkoperation/bulkproductmapping');
                }
            }
            $invalidData = 0;
            $slno = trim($val['*SlNo']);
            $username = trim($val['*User Name']);
            $product_name = trim($val['*Product Name']);
            $cas = trim($val['*Server']);
            $amount_type = trim($val['*Amount Type(1-Percentage,2-Amount)']);
            $percentage = trim($val['*Percentage']);
            $int_dis_percentage = (isset($val['Distributor Percentage']) && $val['Distributor Percentage']!='')? trim($val['Distributor Percentage']):0;
            $int_sub_dist_percentage = (isset($val['Subdistributor Percentage']) && $val['Subdistributor Percentage']!='')?trim($val['Subdistributor Percentage']):0;
            $start_date = trim($val['*Start Date']);
            $end_date = trim($val['*End Date']);
            $remarks = trim($val['Remarks']);

            //if any of the fields are empty
            //	$remarks is optional so that we are not checking here

            if ($slno == '' || $username == '' || $product_name == '' || $amount_type == '' || $percentage == '' || $start_date == '' || $end_date == '' || $cas == '') {
                $this->error_string .= $slno . ") Please fill all the fields in the excel sheet";
                $remarks = "Please fill all the fields in the excel sheet";
                $invalidData = 1;
            }

            //if lovvalue off for ALLOW_AMOUNT_FOR_LCO_SHARE give error for that row
            if (!$invalidData) {
                if ($amount_lovvalue != 1) {
                    if ($amount_type == 2) {
                        $this->error_string .= $slno . ") Amount type is invalid.";
                        $remarks = "Amount type is invalid.";
                        $invalidData = 1;
                    }
                }
            }

            //LCO code checking goes here by Ashok
            if (!$invalidData) {
                if ($username == '') {
                    $this->error_string .= $slno . ") User Name is empty ";
                    $remarks = "User Name is empty";
                    $invalidData = 1;
                } else {
                    $isUserExist = $objbulk->isuserNameExist($username);
                    if (!$isUserExist) {
                        $this->error_string .= $slno . ") User Name is not available.";
                        $remarks = "User Name is not available";
                        $invalidData = 1;
                    } else {
                        $reseller_id = $isUserExist;
                    }
                }
            }

            // Server Validation checks here by Ashok
            //changed by sravani for server validation on 31-10-2013
            if (!$invalidData) {
                if ($cas != '') {
                    $backend_setup_id = $this->dasmodel->getback_id($cas, $_SESSION['user_data']->dealer_id);
                    if ($backend_setup_id == 0) {
                        $this->error_string .= $slno . ") Server Name is invalid ";
                        $remarks = "Server Name is invalid.";
                        $invalidData = 1;
                    }
                } else {
                    $this->error_string .= $slno . ") Server Name will not be empty ";
                    $remarks = "Proivde the Server Name.";
                    $invalidData = 1;
                }
            }

            // product Name  validation checks here by Ashok 
            if (!$invalidData) {
                $product_id = $this->inventorymodel->isProductExist($product_name, $cas = "", $backend_setup_id);
                //echo "ProductId..........".$product_id;exit;
                if ($product_id == -1) {
                    $this->error_string .= $slno . ") The Duplicate Product Name Exist.";
                    $remarks = " The Duplicate Product name Exist";
                    $invalidData = 1;
                } else if ($product_id == 0) {
                    $this->error_string .= $slno . ") The package does not Exist OR Server name is wrong.";
                    $remarks = "$product_name Package does not Exist. OR Server name is wrong";
                    $invalidData = 1;
                }
            }

            //percentage validation checks here by Ashok
            //lco percentage validation modified by sravani
            if (!$invalidData) {
                if ($percentage != '' && ($percentage < 0 || $percentage > 100)) {
                    $this->error_string .= $slno . ") Please check the Percentage should not Empty or greater than 100. ";
                    $remarks = "Please check the Percentage will not be Empty and not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }
            if (!$invalidData) {
                if ($int_dis_percentage != '' && ($int_dis_percentage < 0 || $int_dis_percentage > 100)) {
                    $this->error_string .= $slno . ") Please check the Distributor Percentage should not greater than 100. ";
                    $remarks = "Please check the Distributor Percentage will not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }
            if (!$invalidData) {
                if ($int_sub_dist_percentage != '' && ($int_sub_dist_percentage < 0 || $int_sub_dist_percentage > 100)) {
                    $this->error_string .= $slno . ") Please check the Sub Distributor Percentage should not greater than 100. ";
                    $remarks = "Please check the Sub Distributor Percentage will not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }
            //start date validation checks here by Ashok
            if (!$invalidData) {
                $start_date = date("Y-m-d", strtotime(str_replace('/', '-', $start_date)));
                //if given date is greater than today

                if ($start_date < date('Y-m-d')) {
                    $this->error_string .= $slno . ") Start Date should be greater than or equal to Current Date.";
                    $remarks = "Start Date should be greater than or equal to Current Date.";
                    $invalidData = 1;
                }
            }
            // End Date validation checks here by Ashok
            if (!$invalidData) {
                $end_date = date("Y-m-d", strtotime(str_replace('/', '-', $end_date)));
                //if given date is greater than today
                if ($end_date < $start_date) {
                    $this->error_string .= $slno . ") End Date should be greater than  to Start Date.";
                    $remarks = "End Date should be greater than  to Start Date.";
                    $invalidData = 1;
                }
            }

            //check product is mapped to lco or not  by Ashok
            if (!$invalidData) {
                if ($this->inventorymodel->chechkproductmapping($product_id, $reseller_id)) {
                    $this->error_string .= $slno . ") Package is already mapped to the employee. ";
                    $remarks = "  Package is already mapped to the employee. ";
                    $invalidData = 1;
                }
            }
            //map that product to that LCO
            if (!$invalidData) {
                
                if ($this->EmployeeModel->employeeProductMapping($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks,$int_dis_percentage,$int_sub_dist_percentage)) {
                    $this->error_string .= $slno . ") Package is mapped to the employee. ";
                    $remarks = "  Package is successfully mapped to the employee.";
                    $invalidData = 0;
                }
            }

            if ($invalidData) {
                $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                $objbulk->bulkmapping_error_log($username, $product_name, $amount_type, $percentage, $remarks, $start_date, $end_date, $isValid = 0);
            } else {
                $i++;
                $objbulk->bulkmapping_error_log($username, $product_name, $amount_type, $percentage, $remarks, $start_date, $end_date, $isValid = 1);
            }
        }



        return ($i - 1);
    }

    // Bulk product unmapping checking starts here by Swaroop
    public function populateProductUnmapping($worksheet) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());


        $number_of_columns = 4;
        $product_id = 0;
        $i = 0;
        $objbulk = new bulkoperationmodel();
        $_SESSION['bpm_transaction_id'] = time();
        $did = $_SESSION['user_data']->dealer_id;
        $i = 1;
        $row_counter = 0;
        $invalidData = 0;
        $cid = 0;
        $isEmptyRow = false;
        $row_counter++;
        $slno = "";
        $slno1 = "";
        $username = "";
        $product_name = "";

        $cas = "";
        $backend_setup_id = 0;
        $index = 1;

        foreach ($worksheet as $key => $val) {
            $headername_array = $val;
            $headername_validationarray = array("*SlNo" => "1", '*User Name' => "2", '*Product Name' => "3", '*Server' => "4");
            $missing_columns_array = array_values(array_flip(array_diff_key($headername_validationarray, $headername_array)));
            if (!empty($missing_columns_array)) {
                $error = "Invalid File Uploaded.";
                if (isset($error) && $error != '') {
                    $this->session->set_flashdata("fail", $error);
                    redirect('bulkoperation/bulkproductmapping');
                }
            }
            $invalidData = 0;
            $slno = trim($val['*SlNo']);
            $username = trim($val['*User Name']);
            $product_name = trim($val['*Product Name']);
            $cas = trim($val['*Server']);



            //if any of the fields are empty
            //	$remarks is optional so that we are not checking here

            if ($slno == '' || $username == '' || $product_name == '' || $cas == '') {
                $this->error_string .= $slno . ") Please fill all the fields in the excel sheet";
                $remarks = "Please fill all the fields in the excel sheet";
                $invalidData = 1;
            }
            //LCO code checking goes here by Swaroop
            if (!$invalidData) {
                if ($username == '') {
                    $this->error_string .= $slno . ") User Name is empty ";
                    $remarks = "User Name is empty";
                    $invalidData = 1;
                } else {
                    $isUserExist = $objbulk->isuserNameExist($username);
                    if (!$isUserExist) {
                        $this->error_string .= $slno . ") User Name is not available.";
                        $remarks = "User Name is not available";
                        $invalidData = 1;
                    } else {
                        $reseller_id = $isUserExist;
                    }
                }
            }

            // Server Validation checks here by Swaroop

            if (!$invalidData) {
                if ($cas != '') {
                    $backend_setup_id = $this->dasmodel->getback_id($cas, $_SESSION['user_data']->dealer_id);
                    if ($backend_setup_id == 0) {
                        $this->error_string .= $slno . ") Server Name is invalid ";
                        $remarks = "Server Name is invalid.";
                        $invalidData = 1;
                    }
                } else {
                    $this->error_string .= $slno . ") Server Name will not be empty ";
                    $remarks = "Proivde the Server Name.";
                    $invalidData = 1;
                }
            }

            // product Name  validation checks here by Swaroop
            if (!$invalidData) {
                $product_id = $this->inventorymodel->isProductExist($product_name, $cas = "", $backend_setup_id);

                if ($product_id == -1) {
                    $this->error_string .= $slno . ") The Duplicate Product Name Exist.";
                    $remarks = " The Duplicate Product name Exist";
                    $invalidData = 1;
                } else if ($product_id == 0) {
                    $this->error_string .= $slno . ") The package does not Exist OR Server name is wrong.";
                    $remarks = "$product_name Package does not Exist. OR Server name is wrong";
                    $invalidData = 1;
                }
            }

            //check product is mapped to lco or not  by Swaroop
            if (!$invalidData) {
                if (!($this->inventorymodel->chechkproductmapping($product_id, $reseller_id))) {
                    $this->error_string .= $slno . ") Package does not map to the employee. ";
                    $remarks = "  Package does not map to the employee. ";
                    $invalidData = 1;
                }
            }

            //check product is mapped to customer or not  by Swaroop
            if (!$invalidData) {
                if (($this->inventorymodel->check_products_mapped_to_customer($product_id, $reseller_id, $_SESSION['user_data']->dealer_id))) {
                    $this->error_string .= $slno . ") Package mapped to customer, unable to delete. ";
                    $remarks = " Package mapped to customer unable to delete. ";
                    $invalidData = 1;
                }
            }


            //Un map product from LCO
            if (!$invalidData) {
                $isUnmapped = $this->EmployeeModel->product_unmapping(array($product_id), $reseller_id);
                if ($isUnmapped) {
                    $this->error_string .= $slno . ") Package is unmapped to the employee. ";
                    $remarks = "  Package is successfully unmapped to the employee.";
                    $invalidData = 0;
                    $i++;
                }
            }

            if ($invalidData) {
                $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
            }
        }

        return ($i - 1);
    }

    // //bulk product edit checking starts here - riya
    public function populatebulkProductEdit($worksheet) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $amount_lovvalue = isset($_SESSION['dealer_setting']->ALLOW_AMOUNT_FOR_LCO_SHARE) ? $_SESSION['dealer_setting']->ALLOW_AMOUNT_FOR_LCO_SHARE : 0;
        $number_of_columns = 8;
        $product_id = 0;
        $i = 0;
        $objbulk = new bulkoperationmodel();
        $_SESSION['bpm_transaction_id'] = time();
        $did = $_SESSION['user_data']->dealer_id;
        $isFirstRow = true;
        $i = 1;
        $row_counter = 0;
        $invalidData = 0;
        // foreach ($worksheet as $key=> $val) {
        $cid = 0;
        $isEmptyRow = false;
        // if (!$isFirstRow) {
        $row_counter++;

        $slno = "";
        $slno1 = "";
        $username = "";
        $product_name = "";
        $percentage = "";
        $start_date = "";
        $end_date = "";
        $remarks = "";

        $cas = "";
        $backend_setup_id = 0;

        $index = 1;
       
        foreach ($worksheet as $key => $val) { // function to check multiple records in excel - start - added by saikanth  - 050919
            $duplicate = 0;
            $username1 = '';
            $product_name1 = '';
            $username1 = trim($val['*User Name']);
            $product_name1 = trim($val['*Product Name']);
            
             foreach ($worksheet as $key2 => $val2) { 
                 $username2 = '';
                 $product_name2 = '';
                 $username2 = trim($val2['*User Name']);
                 $product_name2 = trim($val2['*Product Name']);
                 if(($username1 == $username2) && ($product_name1 == $product_name2)){
                     $duplicate++;
                 }
                 if($duplicate>1){
                     $this->session->set_flashdata("fail", 'Error: multiple records found for same product, please add single record for each product.');
                     redirect('bulkoperation/bulkproductmapping');                     
                 }
             }            
        } // END - added by saikanth  - 050919
        
        foreach ($worksheet as $key => $val) {
            $headername_array = $val;
            $headername_validationarray = array("*SlNo" => "1", '*User Name' => "2", '*Product Name' => "3", '*Server' => "4", '*Amount Type(1-Percentage,2-Amount)' => "5", '*Percentage' => "6",'Distributor Percentage' => "7",'Subdistributor Percentage' => "8", '*Start Date' => "9", '*End Date' => "10", 'Remarks' => "11");
            $missing_columns_array = array_values(array_flip(array_diff_key($headername_validationarray, $headername_array)));
            if (!empty($missing_columns_array)) {
                $error = "Invalid File Uploaded.";
                if (isset($error) && $error != '') {
                    $this->session->set_flashdata("fail", $error);
                    redirect('bulkoperation/bulkproductmapping');
                }
            }
            $invalidData = 0;

            $slno = trim($val['*SlNo']);
            $username = trim($val['*User Name']);
            $product_name = trim($val['*Product Name']);
            $cas = trim($val['*Server']);
            $amount_type = trim($val['*Amount Type(1-Percentage,2-Amount)']);
            $percentage = trim($val['*Percentage']);
            
            $int_dis_percentage = (isset($val['Distributor Percentage']) && $val['Distributor Percentage']!='')? trim($val['Distributor Percentage']):0;
            $int_sub_dist_percentage = (isset($val['Subdistributor Percentage']) && $val['Subdistributor Percentage']!='')?trim($val['Subdistributor Percentage']):0;
            $start_date = trim($val['*Start Date']);
            $end_date = trim($val['*End Date']);
            $remarks = trim($val['Remarks']);
            
            //if any of the fields are empty
            //$remarks is optional so that we are not checking here

            if ($slno == '' || $username == '' || $product_name == '' || $amount_type == '' || $percentage == '' || $start_date == '' || $end_date == '' || $cas == '') {
                $this->error_string .= $slno . ") Please fill all the fields in the excel sheet";
                $remarks = "Please fill all the fields in the excel sheet";
                $invalidData = 1;
            }



            //if lovvalue off for ALLOW_AMOUNT_FOR_LCO_SHARE give error for that row

            if (!$invalidData) {

                if ($amount_type <= 0 || $amount_type > 2) {
                    $this->error_string .= $slno . ") Amount type is invalid.";
                    $remarks = "Amount type is invalid.";
                    $invalidData = 1;
                }
            }

            if (!$invalidData) {
                if ($amount_lovvalue != 1) {
                    if ($amount_type == 2) {
                        $this->error_string .= $slno . ") Amount type is invalid.";
                        $remarks = "Amount type is invalid.";
                        $invalidData = 1;
                    }
                }
            }


            //LCO code checking goes here by Ashok
            if (!$invalidData) {
                if ($username == '') {
                    $this->error_string .= $slno . ") User Name is empty ";
                    $remarks = "User Name is empty";
                    $invalidData = 1;
                } else {
                    $isUserExist = $objbulk->isuserNameExist($username);
                    if (!$isUserExist) {
                        $this->error_string .= $slno . ") User Name is not available.";
                        $remarks = "User Name is not available";
                        $invalidData = 1;
                    } else {
                        $reseller_id = $isUserExist;
                    }
                }
            }

            // Server Validation checks here by Ashok
            //changed by sravani for server validation on 31-10-2013
            if (!$invalidData) {
                if ($cas != '') {
                    $backend_setup_id = $this->dasmodel->getback_id($cas, $_SESSION['user_data']->dealer_id);
                    if ($backend_setup_id == 0) {
                        $this->error_string .= $slno . ") Server Name is invalid ";
                        $remarks = "Server Name is invalid.";
                        $invalidData = 1;
                    }
                } else {
                    $this->error_string .= $slno . ") Server Name will not be empty ";
                    $remarks = "Proivde the Server Name.";
                    $invalidData = 1;
                }
            }

            // product Name  validation checks here by Ashok 
            if (!$invalidData) {
                $product_id = $this->inventorymodel->isProductExist($product_name, $cas = "", $backend_setup_id);
                //echo "ProductId..........".$product_id;exit;
                if ($product_id == -1) {
                    $this->error_string .= $slno . ") The Duplicate Product Name Exist.";
                    $remarks = " The Duplicate Product name Exist";
                    $invalidData = 1;
                } else if ($product_id == 0) {
                    $this->error_string .= $slno . ") The package does not Exist OR Server name is wrong.";
                    $remarks = "$product_name Package does not Exist. OR Server name is wrong";
                    $invalidData = 1;
                }
            }

            //check product is mapped to lco or not  by Swaroop
            if (!$invalidData) {
                if (!($this->inventorymodel->chechkproductmapping($product_id, $reseller_id))) {
                    $this->error_string .= $slno . ") Package does not map to the employee. ";
                    $remarks = "  Package does not map to the employee. ";
                    $invalidData = 1;
                }
            }


            //percentage validation checks here by Ashok
            //lco percentage validation modified by sravani
            if (!$invalidData) {
                if ($percentage != '' && ($percentage < 0 || $percentage > 100) && $amount_type == 1) {
                    $this->error_string .= $slno . ") Please check the Percentage should not Empty or greater than 100. ";
                    $remarks = "Please check the Percentage will not be Empty and not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }
            
            if (!$invalidData) {
                if ($int_dis_percentage != '' && ($int_dis_percentage < 0 || $int_dis_percentage > 100)) {
                    $this->error_string .= $slno . ") Please check the Distributor Percentage should not greater than 100. ";
                    $remarks = "Please check the Distributor Percentage will not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }
            if (!$invalidData) {
                if ($int_sub_dist_percentage != '' && ($int_sub_dist_percentage < 0 || $int_sub_dist_percentage > 100)) {
                    $this->error_string .= $slno . ") Please check the Sub Distributor Percentage should not greater than 100. ";
                    $remarks = "Please check the Sub Distributor Percentage will not greater than 100 and not less than 0. ";
                    $invalidData = 1;
                }
            }

            //start date validation checks here by Ashok
            if (!$invalidData) {
                $start_date = date("Y-m-d", strtotime(str_replace('/', '-', $start_date)));
                //if given date is greater than today

                if ($start_date < date('Y-m-d')) {
                    $this->error_string .= $slno . ") Start Date should be greater than or equal to Current Date.";
                    $remarks = "Start Date should be greater than or equal to Current Date.";
                    $invalidData = 1;
                }
            }
            // End Date validation checks here by Ashok
            if (!$invalidData) {
                $end_date = date("Y-m-d", strtotime(str_replace('/', '-', $end_date)));
                //if given date is greater than today
                if ($end_date < $start_date) {
                    $this->error_string .= $slno . ") End Date should be greater than  to Start Date.";
                    $remarks = "End Date should be greater than  to Start Date.";
                    $invalidData = 1;
                }
            }

            //Edit data to that product 
            //accroding to date insert or update product 
            if (!$invalidData) {
                $same_Date = $this->inventorymodel->check_samedates($product_id, $reseller_id, $start_date, $end_date); //if dates are same
                if($same_Date != 1){ // added by saikanth - 060919
                    $delete_shares = $this->inventorymodel->delete_product_shares($reseller_id, $product_id,$start_date);
                }
                $result = $this->inventorymodel->chechkmaxdate($product_id, $reseller_id); // check with maxdates
                $maxend_date = $result[0]->max_end_date;
                $maxfrom_date = $result[0]->max_from_date;
                $prev_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
                
                // future dates edit - START - added by saikanth - 060919
                if ($same_Date == 1) { 
                    
                        $update_record = $this->inventorymodel->update_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks,$int_dis_percentage,$int_sub_dist_percentage);
                        if ($update_record == 1) {
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }                                          
                } else if (($maxfrom_date == $start_date) && ($maxend_date != $end_date)) {
                      
                   $update_record = $this->inventorymodel->update_enddate_record($product_id, $reseller_id, $maxfrom_date, $end_date,$percentage,$int_dis_percentage,$int_sub_dist_percentage);
                   if ($update_record == 1) {
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }     
                        
                } else if( ($start_date <= $maxend_date) && ($start_date > $maxfrom_date) ) {   //condition 1
                     
                       $newday = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
                       $update_record = $this->inventorymodel->update_enddates_record($product_id, $reseller_id, $maxfrom_date, $newday);
                       if ($update_record == 1) {
                            $inserted_Record = $this->inventorymodel->insert_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks,$int_dis_percentage,$int_sub_dist_percentage);
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }              
                
                } /*else if( ($start_date < $maxend_date) && ($start_date < $maxfrom_date) && ($end_date > $maxfrom_date) && ($end_date < $maxend_date) )  { // condition 2
                                                
                        $newday = date('Y-m-d', strtotime('+1 day', strtotime($end_date)));
                        $update_record = $this->inventorymodel->update_enddates_record_data($product_id, $reseller_id, $maxend_date, $newday);
                        if ($update_record == 1) {
                            $inserted_Record = $this->inventorymodel->insert_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks);
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }  
                        
                 } else if( ($start_date < $maxfrom_date) && ($end_date > $maxend_date) )  { // condition 3
                       
                        
                 } else if ($maxfrom_date > $end_date) { // condition 4
                        $inserted_Record = $this->inventorymodel->insert_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks);
                        if ($inserted_Record == 1) {
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }
                } */ else if ($maxend_date < $start_date) { // condition 5
                        $inserted_Record = $this->inventorymodel->insert_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks,$int_dis_percentage,$int_sub_dist_percentage);
                        if ($inserted_Record == 1) {
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }
                } else {
                        $this->error_string .= $slno . ") Invalid condition, please change dates.";
                        $remarks = "Invalid condition, please change dates.";
                        $invalidData = 1;
                }
                /* else if ($maxend_date >= $start_date) {

                   if ($maxfrom_date < $start_date) { // added by saikanth

                        $update_record = $this->inventorymodel->update_record($product_id, $reseller_id, $maxend_date, $start_date);

                        $inserted_Record = $this->inventorymodel->insert_product_percent($reseller_id, $product_id, $amount_type, $percentage, $start_date, $end_date, $remarks);

                        if ($inserted_Record == 1 && $update_record == 1) {
                            $this->error_string .= $slno . ") Package is Edited successfully. ";
                            $remarks = "  Package is successfully edited.";
                            $invalidData = 0;
                        }
                    } else {
                        $this->error_string .= $slno . ") Package Not Edited.Dates are not proper ";
                        $remarks = "Dates are not proper. Package is Not edited.";
                        $invalidData = 1;
                    }
                } */
                // future dates edit - END - added by saikanth - 060919
            }



            if ($invalidData) {
                $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                $objbulk->bulkmapping_error_log($username, $product_name, $amount_type, $percentage, $remarks, $start_date, $end_date, $isValid = 0);
            } else {
                $i++;
                $objbulk->bulkmapping_error_log($username, $product_name, $amount_type, $percentage, $remarks, $start_date, $end_date, $isValid = 1);
            }
        }



        return ($i - 1);
    }

    //bulk product edit checking ends here - riya



    public function bulkCustomer_STB_Update() {

        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $objchangepass = new change_pass_model();
        $res = "<div class='error'>";
        $process_details = array();
        $stringData = "";

        if ($this->input->post('btnimport')) {
            $isValidFile = true;
            if ($_FILES['file']['name'] != "") {

                //$max_file_size = 2097152;
                $max_file_size = 26214400;
                $file_size = $_FILES['file']['size'];
                if ($file_size > 0) {
                    $allowedExtensions = array("xml");
                    if (!in_array(end(explode(".", strtolower($_FILES['file']['name']))), $allowedExtensions)) {
                        $res .= 'Invalid file. Please upload XML file. Please save your excel file to xml using 2003 XML spread sheet format <br/>';
                    } else if ($file_size >= $max_file_size && $file_size > 0) {
                        $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['file']['size'] . " Bytes.";
                    } else {
                        $dom = DOMDocument::load($_FILES['file']['tmp_name']);
                        $worksheets = $dom->getElementsByTagName('Worksheet');

                        foreach ($worksheets as $worksheet) {
                            $worksheetName = $worksheet->getAttribute('Name');
                            if (strlen($worksheetName) == 0)
                                $worksheetName = $worksheet->getAttribute('ss:Name'); {
                                $process_details = $this->customer_MultipleSTB_Updation($worksheet); //getting the result	
                                if (count($process_details) == 0) {
                                    $res .="All customers tagged successfully.";
                                }
                                $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->
                                        last_name . " has done stb multiple customerId import ";
                                $objchangepass->updateLog($comment);
                            }
                        }
                    }
                } else {
                    $res .= "The uploaded file size should be less than or equal to 2Mb";
                }
            } else {
                $res .= "Please upload a file";
            }
        }
        $res .= "</div>";
        $data = array('title' => 'Bulk Product Mapping',
            'content' => 'content/Bulkoperations/bulkcustomerupdation',
            'res' => $res,
            'process_details' => $process_details,
        );
        //$data['servers'] = $das_object->count_servers();
        $this->load->view('common/inner-template', $data);
    }

    // This function is used for customer ID Updation for MultipleSTBs who is having more than one STB  Developed by Ashok

    public function customer_MultipleSTB_Updation($worksheet) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $number_of_columns = 9; //number of column		
        $eid = $_SESSION['user_data']->employee_id;
        $cdate = date('Y-m-d H:i:s');
        $objbulk = new bulkoperationmodel();
        $bcpm_transaction_id = time();
        $process_msg = '';
        $i = 0;
        $rows = $worksheet->getElementsByTagName('Row');
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('Cell');
            foreach ($cells as $cell) {
                $i++;
            }
            if ($i < $number_of_columns)
                $process_msg .="Invalid Number of columns. ";
            break;
        }
        $did = $_SESSION['user_data']->dealer_id;
        $isFirstRow = true;
        $row_counter = 0;
        $invalidData = 0;
        foreach ($rows as $row) {
            $cid = 0;
            $isEmptyRow = false;
            if (!$isFirstRow) {
                $row_counter++;
                $customer_id = "";
                $caf_number = "";
                $customer_name = "";
                $serial_number = "";
                $vc_number = "";
                $parent_customer_id = "";
                $parent_caf_number = "";
                $box_type = "";
                $remarks = "";
                $invalidData = 0;
                $cells = $row->getElementsByTagName('Cell');
                $index = 1;

                foreach ($cells as $cell) {

                    $ind = $cell->getAttribute('Index');
                    $indb = $cell->getAttribute('ss:Index');
                    if ($ind != null)
                        $index = $ind;
                    if ($indb != null)
                        $index = $indb;

                    if ($index == 1)
                        $customer_name = trim($cell->nodeValue);
                    $customer_name = trim($customer_name);
                    if ($index == 2)
                        $serial_number = trim($cell->nodeValue);
                    $serial_number = trim($serial_number);
                    if ($index == 3)
                        $vc_number = trim($cell->nodeValue);
                    $vc_number = trim($vc_number);
                    if ($index == 4)
                        $customer_id = trim($cell->nodeValue);
                    $customer_id = trim($customer_id);
                    if ($index == 5)
                        $parent_customer_id = trim($cell->nodeValue);
                    $parent_customer_id = trim($parent_customer_id);
                    if ($index == 6)
                        $caf_number = trim($cell->nodeValue);
                    $caf_number = trim($caf_number);
                    if ($index == 7)
                        $parent_caf_number = trim($cell->nodeValue);
                    $parent_caf_number = trim($parent_caf_number);
                    if ($index == 8)
                        $box_type = trim($cell->nodeValue);
                    $box_type = trim($box_type);
                    if ($index == 9)
                        $remarks = trim($cell->nodeValue);
                    $remarks = trim($remarks);

                    $index += 1;
                }
                $slno = "\r\n" . $customer_id;
                //validation for exl sheet
                if ($invalidData == 0) {
                    if ($customer_name == '') {
                        $process_msg .="Please fill customer_name. ";
                        $invalidData = 1;
                    }
                }
                if ($invalidData == 0) {
                    if ($customer_id == '' && $caf_number == '') {
                        $process_msg .="Please fill customer_id or caf_number for  $customer_name. ";
                        $invalidData = 1;
                    }
                    if ($customer_id == '' && $caf_number != '') {
                        $customer_id = $objbulk->get_customer_id($caf_number);
                    }
                }
                if ($invalidData == 0) {
                    if ($serial_number == '') {
                        $process_msg .="Please fill serial_number for  $customer_name. ";
                        $invalidData = 1;
                    }
                }
                if ($invalidData == 0) {
                    if ($vc_number == '') {
                        $process_msg .="Please fill vc_number for  $customer_name. ";
                        $invalidData = 1;
                    }
                }
                if ($invalidData == 0) {
                    if ($parent_customer_id == '' && $parent_caf_number == '') {
                        $process_msg .="Please fill parent_customer_id or parent_caf_number for  $customer_name. ";
                        $invalidData = 1;
                    }
                    if ($parent_customer_id == '' && $parent_caf_number != '') {
                        $parent_customer_id = $objbulk->get_customer_id($parent_caf_number);
                    }
                }
                if ($invalidData == 0) {
                    if ($box_type == '') {
                        $process_msg .="Please fill box_type  for  $customer_name. ";
                        $invalidData = 1;
                    }
                }
                //inserting intodatabase
                if ($invalidData == 0) {
                    $objbulk->multipleSTBimport($customer_id, $caf_number, $customer_name, $serial_number, $vc_number, $parent_customer_id, $parent_caf_number, $remarks, $cdate, $box_type);
                }
            }
            $isFirstRow = false;
        }
        if (isset($process_msg) && $process_msg != '') {
            $err_msg = explode(". ", $process_msg);
            $process = $objbulk->processPriSecSTB(); //tagging start 
            $result = array_merge($process, $err_msg);
            return $result;
        } else {
            $process = $objbulk->processPriSecSTB(); //tagging start
            return $process;
        }
    }

    //function added by sravani for bulk invoice print
    public function bulkInvoice() {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        !($_SESSION['user_data']->users_type == 'DEALER' || $_SESSION['user_data']->users_type == 'ADMIN') ? redirect('welcome/accessdenied') : '';
        $data = array(
            'title' => 'Ezybill.net | Bulk Invoice Print ',
            'content' => 'content/Bulkoperations/bulkInvoice',
        );
        if ($this->input->post('sbt_clear')) {
            redirect('bulkoperation/bulkInvoice');
        }
        $blkopr = new bulkoperationmodel();
        $dealer = $_SESSION['user_data']->dealer_id;
        $data['employee_codes_array'] = $this->EmployeeModel->getDistrOrSubDistrCode(0, $_SESSION['user_data']->dealer_id);
        $data['locations'] = $this->CustomersModel->getLcoLocations();


        //if($this->input->post('sbt_search'))
        //{
        if ($this->input->post('lcolocation') && $this->input->post('lcolocation') > 0) {
            $start_date = ($this->input->post('bill_st_date') && trim($this->input->post('bill_st_date')) != '') ? $this->input->post('bill_st_date') . '-01' : date('Y-m-01');
            $data['start_date'] = $start_date;
            $end_date = ($this->input->post('bill_end_date') && trim($this->input->post('bill_end_date')) != '') ? $this->input->post('bill_end_date') . '-01' : date('Y-m-01');
            $data['end_date'] = $end_date;
            $lco_location = ($this->input->post('lcolocation') ) ? $this->input->post('lcolocation') : -1;
            $data['lco_location'] = $lco_location;
            $data['bulkInvoice'] = $blkopr->getBulkInvoice($dealer, $lco_location, $start_date, $end_date);
        }

        //}
        $this->load->view('common/inner-template', $data);
    }

    //function by sravani for print bulk invoice - modified by Ashwin
    public function printInvoice() {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $bulk_obj = new bulkoperationmodel();
        $objchangepass = new change_pass_model();
        $payment = new PaymentsModel();

        $data = array(
            'title' => 'Ezybill.net | Bulk Invoice Print',
        );

        $time = time();
        $upload_path = uploads_path();
        $dir_to_save = $upload_path . "temp/invoice_$time";
        $dir_to_save_all = $upload_path . "static_invoice";
        $file_name = 'Allinvoice_';

        $reseller_id = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $start_month = ($this->uri->segment(4) && trim($this->uri->segment(4)) != '') ? $this->uri->segment(4) : date('Y-m-01');
        $end_month = ($this->uri->segment(5) && trim($this->uri->segment(5)) != '') ? $this->uri->segment(5) : date('Y-m-01');
        $record = $payment->print_view1($cid = 0, $start_month, $dealer_id = 0, $end_month, $reseller_id);
        $did = isset($_SESSION['user_data']->dealer_id) ? $_SESSION['user_data']->dealer_id : 0;

        $this->load->library("MPDF56/mpdf.php");
        $dir_check_pass = False;
        $dir_check_pass_all = False;
        $rebuild_pdf = False;
        if (file_exists($dir_to_save)) {
            $dir_check_pass = True;
        } else {
            if (mkdir($dir_to_save, 0777)) {
                $dir_check_pass = True;
            }
        }
        if (!$dir_check_pass)
            die("Directory Creation Failed");

        $data['term_condition'] = $objchangepass->get_term_condition();
        $data['tax_names'] = $this->change_pass_model->getTaxNames($did);
        foreach ($record as $records) {
            set_time_limit(0);
            $data['record'] = NULL;
            $data['subscriptions'] = NULL;
            $data['outstanding_amount'] = NULL;
            $html = NULL;
            $data['record'][] = $records;
            $data['subscriptions'] = $payment->get_subscriptions_of_customers($records->customer_id, $records->bill_month);
            $data['outstanding_amount'] = $payment->getCustomerNetpayableAmount($records->customer_id, $did, $records->bill_month);
            $data['def_sch'] = $payment->Getbillingschedule();

            $format = $this->change_pass_model->getLovValue('INVOICE_FORMAT', $did);
            $format = strtolower(trim($format));
            if ($format != '') {
                $html = $this->load->view('content/payments/invoice_templates/' . $format, $data, TRUE);
            } else {
                $html = $this->load->view('content/payments/invoice_templates/default', $data, TRUE);
            }

            $mpdf = new mPDF('', 'a4', 0, '', 10, 10, 12, 12, 6, 6);
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->list_indent_first_level = 0; // 1 or 0 - whether to indent the first level of a list
            $mpdf->SetHTMLFooter('<div style="text-align: right; font-weight: bold;font-size:12px;">Powered by : Ezybill.net</div>', '0');
            $mpdf->WriteHTML($html);
            $mpdf->Output($dir_to_save . '/' . $file_name . date("Y-m-d") . time() . '.pdf', 'F');
            $mpdf->destroy();
            $mpdf = Null;
            $rebuild_pdf = True;
        }

        if ($rebuild_pdf) {
            if (file_exists($dir_to_save_all)) {
                $dir_check_pass_all = True;
            } else {
                if (mkdir($dir_to_save_all, 0777)) {
                    $dir_check_pass_all = True;
                }
            }
            if (!$dir_check_pass_all)
                die("Directory Creation Failed");
            $file_name = $file_name . date("Y-m-d") . $time . ".pdf";
            $file_path = $dir_to_save_all . "/" . $file_name;
            $this->MergePDF($dir_to_save, $file_path);
            if (file_exists($dir_to_save)) {
                $files = glob($dir_to_save . '/*'); // get all file names
                foreach ($files as $file) { // iterate files
                    if (is_file($file))
                        unlink($file); // delete file dlm folder
                }
                rmdir($dir_to_save);
            }
            $this->load->helper('download');
            $data = file_get_contents($file_path);
            force_download($file_name, $data);
        }
    }

    private function MergePDF($datadir, $outfile) {
        $outputName = $outfile;
        //echo $datadir."<br>";
        //echo $outputName."<br>";

        $fileArray = $this->directoryToArray($datadir, False);
        sort($fileArray);
        //echo "<pre>";
        //print_r($fileArray);
        $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
        //Add each pdf file to the end of the command
        foreach ($fileArray as $file) {
            $cmd .= $file . " ";
        }
        //echo $cmd."<br>";
        $result = shell_exec($cmd);
        return $result;
    }

    private function directoryToArray($directory, $recursive) {
        $array_items = array();
        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($directory . "/" . $file)) {
                        if ($recursive) {
                            $array_items = array_merge($array_items, directoryToArray($directory . "/" . $file, $recursive));
                        }
                        $file = $directory . "/" . $file;
                        $array_items[] = preg_replace("/\/\//si", "/", $file);
                    } else {
                        $file = $directory . "/" . $file;
                        $array_items[] = preg_replace("/\/\//si", "/", $file);
                    }
                }
            }
            closedir($handle);
        }
        return $array_items;
    }

    //function by SRAVANI for bulk defective
    public function bulkDefective() {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        ($_SESSION['user_data']->users_type != 'DEALER' && $_SESSION['user_data']->users_type != 'ADMIN' && $_SESSION['user_data']->users_type != 'EMPLOYEE' ) ? redirect('welcome/accessdenied') : '';
        $message_list = '';
        $res = "";
        $comment = "";
        $success_count = 0;
        if ($this->input->post('cancel')) {
            unset($_SESSION['data_type']);
            redirect('bulkoperation/bulkDefective');
        }
        if ($this->input->post('upload_file')) {
            $remarks = ($this->input->post('remarks')) ? $this->input->post('remarks') : 'Bulk Defective.';
            $data_type = $this->input->post('Data');
            $_SESSION['data_type'] = $data_type;
            if ($_FILES['uploaded_file']['name'] != "") {
                $max_file_size = 2097152;
                $file_size = $_FILES['uploaded_file']['size'];
                $allowedExtensions = array("xml");
                if (!in_array(end(explode(".", strtolower($_FILES['uploaded_file']['name']))), $allowedExtensions)) {
                    $res .= 'Invalid file. Please upload XML file. Please save your excel file to xml using 2003 XML spread sheet format <br/>';
                } else if ($file_size >= $max_file_size) {
                    $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['uploaded_file']['size'] . " Bytes.";
                } else {
                    $dom = DOMDocument::load($_FILES['uploaded_file']['tmp_name']);
                    $worksheets = $dom->getElementsByTagName('Worksheet');
                    foreach ($worksheets as $worksheet) {
                        $worksheetName = $worksheet->getAttribute('Name');
                        if (strlen($worksheetName) == 0)
                            $worksheetName = $worksheet->getAttribute('ss:Name');
                        $success_count = $this->populateStbBulkDefective($worksheet, $data_type, $remarks);
                        $message_list .= $success_count . ' STB(s) have been made DEFECTIVE.  <br/>';


                        // START LOGS CODE
                        $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has made " . $success_count . " STB(s) as DEFECTIVE.";
                        $this->change_pass_model->updateLog($comment);
                        // END LOGS CODE

                        $this->skipped_rows_cust = rtrim($this->skipped_rows_cust, ",");
                        if (strlen($this->skipped_rows_cust) > 0) {
                            $message_list .=" STB(s) Rows ($this->skipped_rows_cust) not defective <br/>";

                            if (strlen($this->skipped_serials) > 0) {
                                $res .= $this->skipped_serials;
                            }
                        }
                    }
                }
            }
            if ($res) {
                $upload_path = uploads_path();
                $path = $upload_path . "temp";
                $basename = time();
                $file_name = "$path/transferstb1_$basename.html";
                $fh = fopen($file_name, 'w+') or die("can't open file");
                $error_msg = $res;
                fwrite($fh, $error_msg);
                fclose($fh);
                $link = base_url() . $file_name;
                $message_list .="<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here To Check Serial Numbers Which could not be made DEFECTIVE .<br/></a>";
            }
        }
        $data = array(
            'title' => 'Ezybill.net | Bulk Defective ',
            'content' => 'content/Bulkoperations/bulk_defective',
            'msg' => $message_list
        );
        $this->load->view('common/inner-template', $data);
    }

    //function by SRAVANI	
    public function populateStbBulkDefective($worksheet, $data_type, $remarks) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $i = 1;
        $rows = $worksheet->getElementsByTagName('Row');
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('Cell');
            $i = 0;
            foreach ($cells as $cell) {
                $i++;
            }
            if ($i < 1)
                return "Invalid Number of columns, 0";
            break;
        }
        if ($_SESSION['user_data']->employee_parent_type != '' && $_SESSION['user_data']->employee_parent_type != 'null') {
            $employee_details = $this->EmployeeModel->getEmployeeDetails(0, $_SESSION['user_data']->username);
            $employee_id = $employee_details->employee_id;
        } else {
            $employee_id = $_SESSION['user_data']->employee_id;
        }
        $dealer_id = $_SESSION['user_data']->dealer_id;
        $isFirstRow = true;
        $i = 1;
        $this->sr_nos = "(";
        $row_counter = 0;
        $invalidData = 0;
        foreach ($rows as $row) {
            $isEmptyRow = false;
            if (!$isFirstRow) {
                $invalidData = 0;

                $serial_number = 0;

                $index = 1;
                $cells = $row->getElementsByTagName('Cell');
                foreach ($cells as $cell) {
                    $ind = $cell->getAttribute('Index');
                    $indb = $cell->getAttribute('ss:Index');
                    if ($ind != null)
                        $index = $ind;
                    if ($indb != null)
                        $index = $indb;
                    if ($index == 1)
                        $serial_number = trim($cell->nodeValue);
                    $index += 1;
                }
                $display_serial = $serial_number;
                $isExist = $this->blkopr->isStockExist($serial_number, $data_type, $dealer_id);

                if (count($isExist) == 0) {
                    $row_counter++;
                    $this->skipped_serials .= $row_counter . ') ' . $serial_number . ' : Stock does not exist.<br/>';
                    $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                    $invalidData = 1;
                } else {
                    if ($isExist->defective_stock == 1) {
                        $row_counter++;
                        $this->skipped_serials .= $row_counter . ') ' . $serial_number . ' : is already DEFECTIVE.<br/>';
                        $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                        $invalidData = 1;
                    } else if ($isExist->is_trash == 1) {
                        $row_counter++;
                        $this->skipped_serials .= $row_counter . ') ' . $serial_number . ' : is in unrepairable state (trash).<br/>';
                        $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                        $invalidData = 1;
                    } else if ($isExist->isSurrended == 1) {
                        $row_counter++;
                        $this->skipped_serials .= $row_counter . ') ' . $serial_number . ' : is SURRENDERED.<br/>';
                        $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                        $invalidData = 1;
                    } else if ($isExist->status == 3) {
                        $row_counter++;
                        $this->skipped_serials .= $row_counter . ') ' . $serial_number . ' : is BLOCKED.<br/>';
                        $this->skipped_rows_cust = $this->skipped_rows_cust . $row_counter . ",";
                        $invalidData = 1;
                    }

                    if ($serial_number != "" && $invalidData == 0) {
                        $replacement_type = 1;
                        $stock_id = $isExist->stock_id;
                        $mac_addr = $isExist->mac_address;
                        $vc_number = $isExist->vc_number;
                        $backend_setup_id = $isExist->backend_setup_id;
                        $stock_location = $isExist->stock_location;
                        $defective_result = $this->blkopr->make_defective_stb($stock_id, $serial_number, $mac_addr, $remarks, $dealer_id, $employee_id, $stock_location);
                        if ($defective_result == 1) {
                            $result = $this->blkopr->setReplacermentUpdates($replacement_type, $stock_id, $remarks);
                        }
                        $i++;
                    }
                }
            }
            $isFirstRow = false;
        }
        return ($i - 1);
    }

    //function by SRAVANI for bulk upgrade
    public function bulkUpgrade() {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        if ($this->input->post('cancel')) {
            unset($_SESSION['data_type']);
            redirect('bulkoperation/bulkUpgrade');
        }
        $message_list = '';
        $res = '';
        $error_msg = '';
        if ($this->input->post('upload_file')) {
            $remarks = ($this->input->post('remarks')) ? $this->input->post('remarks') : 'Bulk Defective.';
            $data_type = $this->input->post('Data');
            $_SESSION['data_type'] = $data_type;
            if ($_FILES['uploaded_file']['name'] != "") {
                $max_file_size = 2097152;
                $file_size = $_FILES['uploaded_file']['size'];
                $allowedExtensions = array("xml");
                if (!in_array(end(explode(".", strtolower($_FILES['uploaded_file']['name']))), $allowedExtensions)) {
                    $res .= 'Invalid file. Please upload XML file. Please save your excel file to xml using 2003 XML spread sheet format <br/>';
                } else if ($file_size >= $max_file_size) {
                    $res .= 'The file Size Exceeds the Prescribed Limit of : 2097152 Bytes (2 MB)! <br/> The Current File Size is : ' . $_FILES['uploaded_file']['size'] . " Bytes.";
                } else {
                    $dom = DOMDocument::load($_FILES['uploaded_file']['tmp_name']);
                    $worksheets = $dom->getElementsByTagName('Worksheet');
                    foreach ($worksheets as $worksheet) {
                        $worksheetName = $worksheet->getAttribute('Name');
                        if (strlen($worksheetName) == 0)
                            $worksheetName = $worksheet->getAttribute('ss:Name');
                        $success_count = $this->populateStbBulkUpgrade($worksheet, $data_type, $remarks);
                        $message_list .= $success_count . ' STB(s) have been replaced with new STB(s).  <br/>';


                        // START LOGS CODE
                        $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has made " . $success_count . " STB(s) as UPGRADE.";
                        $this->change_pass_model->updateLog($comment);
                        // END LOGS CODE
                        //$this->skipped_rows_cust = rtrim($this->skipped_rows_cust,",");
                        //if (strlen($this->skipped_rows_cust) > 0)
                        //{
                        //$message_list .=" STB(s) Rows ($this->skipped_rows_cust) not UPGRADE <br/>";

                        if (strlen($this->skipped_serials) > 0) {
                            $res .= $this->skipped_serials;
                        }

                        //}
                    }
                }
            }
            if ($res) {
                $upload_path = uploads_path();
                $path = $upload_path . "temp";
                $basename = time();
                $file_name = "$path/transferstb1_$basename.html";
                $fh = fopen($file_name, 'w+') or die("can't open file");
                $error_msg = $res;
                fwrite($fh, $error_msg);
                fclose($fh);
                $link = base_url() . $file_name;
                $message_list .="<a href=javascript:window.open('$link','error_page','width=500,height=500')>Click Here To Check Serial Numbers Which could not be made UPGRADE .<br/></a>";
            }
        }

        $data = array(
            'title' => 'Ezybill.net | Bulk Upgrade ',
            'content' => 'content/Bulkoperations/bulk_upgrade',
            'msg' => $message_list
        );
        $this->load->view('common/inner-template', $data);
    }

    //function by SRAVANI
    /* public function populateStbBulkUpgrade1($worksheet,$data_type,$remarks)
      {
      $i=1;
      $rows = $worksheet->getElementsByTagName('Row');
      foreach($rows as $row){
      $cells = $row->getElementsByTagName('Cell');
      $i=0;
      foreach($cells as $cell){$i++;}
      if($i<1) return "Invalid Number of columns, 0";
      break;
      }
      $isFirstRow = true;
      $i =1;
      $this->sr_nos="(";
      $row_counter = 0;
      $invalidData = 0;
      $old_stock_id = 0;
      $new_stock_id = 0;
      $customer_id = 0;
      $old_serial_number = '';
      $new_serial_number ='';
      $dealer_id = $_SESSION['user_data']->dealer_id;
      foreach($rows as $row){
      $isEmptyRow = false;
      if(!$isFirstRow){
      $invalidData = 0;



      $index = 1;
      $cells = $row->getElementsByTagName( 'Cell' );
      foreach( $cells as $cell )
      {
      $ind = $cell->getAttribute( 'Index' );
      $indb = $cell->getAttribute( 'ss:Index' );
      if ( $ind != null ) $index = $ind;
      if ( $indb != null ) $index = $indb;
      if ( $index == 1 ) 	$old_serial_number = trim($cell->nodeValue);
      if ( $index == 2 ) 	$new_serial_number = trim($cell->nodeValue);
      $index += 1;
      }
      $replacement_type = 2;
      $old_stb_details = $this->blkopr->isStockExist($old_serial_number,$data_type,$dealer_id);

      $old_stock_id=0;
      $old_vc_number='';
      $old_stock_status=0;
      $box_number='';
      $backend_setup_id=0;
      $mac='';
      if(count($old_stb_details)>0)
      {
      $old_stock_id = $old_stb_details->stock_id;
      $old_vc_number = $old_stb_details->vc_number;
      $old_stock_status = $old_stb_details->status;
      $box_number = $old_stb_details->box_number;
      $backend_setup_id = $old_stb_details->backend_setup_id;
      $mac = $old_stb_details->mac_address;
      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : Old STB does not exist.<br/>';
      // $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      $getCustomer_details = $this->dasmodel->get_customerDetails($customer_id='',$old_serial_number,$old_vc_number);

      $new_stb_details = $this->blkopr->isStockExist($new_serial_number,$data_type,$dealer_id);

      $serial_number='';
      $new_box_vc_number='';
      $new_box_paired_in_cas=0;
      $new_box_pairing=0;
      $new_box_number='';
      $stb_status=0;
      $is_defective=0;
      if(count($getCustomer_details)>0 && $getCustomer_details->customer_id>0){
      if(count($new_stb_details)>0)
      {
      $new_stock_id = $new_stb_details->stock_id;
      $box_details = $this->blkopr->getStockDetails($new_stock_id);
      if(count($box_details)>0)
      {
      $serial_number=$box_details->serial_number;
      $new_box_vc_number=$box_details->vc_number;
      $new_box_paired_in_cas=$box_details->not_paired_in_cas;
      $new_box_pairing=$box_details->pairing;
      $new_box_number=$box_details->box_number;
      $stb_status=$box_details->status;
      $is_defective = $box_details->defective_stock;
      }
      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : Stock does not exist.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      $customer_id = $getCustomer_details->customer_id;
      $defective_serial_number = $this->inventorymodel->getSerialNumber($old_stock_id);
      $isBoxExistWithCustomer=$this->inventorymodel->isBoxExistWithCustomer($customer_id,$defective_serial_number);
      $device = $this->blkopr->getDeviceId($customer_id,$old_serial_number);
      if(count($device)>0)
      {
      $device_id = $device->customer_device_id;
      }
      //getting active services with stb type id to activate on new stb
      $active_services = $this->dasmodel->getActiveServicesofCustomer($old_stock_id);

      if(count($active_services)==0){
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : New STB type is not matching with product stb types. Please refresh the page..<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      //check blacked stb condition
      if($stb_status==3){
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : New STB is BLOCKED.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      if($is_defective==1)
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : New STB is in DEFECTIVE state.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      $check_old_stock_loc = $this->blkopr->isSTBExistInLocation($old_serial_number);
      $check_new_stcok_loc = $this->blkopr->isSTBExistInLocation($new_serial_number);
      if(count($check_old_stock_loc)>0 && count($check_new_stcok_loc)>0)
      {
      if($check_old_stock_loc->employee_id!=$check_new_stcok_loc->employee_id)
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') Old serial number and new serail number does not exist in same location.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      }
      // else
      // {
      // $row_counter++;
      // $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : Stock does not exist.<br/>';
      // $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      // $invalidData = 1;
      // }
      $is_paired = 0;
      if($new_box_pairing==1){ // For Other than CISCO
      if($new_box_paired_in_cas==0 && $new_box_vc_number!='' && $new_box_number!=''){
      $is_paired = 1;
      }else{
      $is_paired = 0;
      }
      }else{ //For CISCO
      $is_paired = 1;
      }

      if(count($check_old_stock_loc)>0 && count($check_new_stcok_loc)>0){
      if($check_old_stock_loc->employee_id==$check_new_stcok_loc->employee_id)
      {
      if($is_paired ){
      if($new_stock_id>0) {
      $isTrailPacksExist = $this->blkopr->getTrailPacks($new_stock_id);
      if(count($isTrailPacksExist)>0)
      {

      $res1=0;
      //If old stb does not exist with customer don't deactivate new stb.
      if($isBoxExistWithCustomer==1)// checking with old stb
      {
      $res1 = $this->dasmodel->deactivateEntireBox($isTrailPacksExist->box_number,$isTrailPacksExist->backend_setup_id,$isTrailPacksExist->mac_address,0,$act='D',$new_stock_id);
      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : Old STB does not exist with customer.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;

      }
      if(!$res1){
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$isTrailPacksExist->box_number.' : Trail pack deactivation of new STB.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      }

      if($old_stock_status==1)
      {

      //getting active services to deactivation
      $active_services_old_stb = $this->dasmodel->getActiveServicesofCustomer($old_stock_id);
      $res=0;
      if(count($active_services_old_stb)>0){
      $act = 'D';
      if($isBoxExistWithCustomer==1)
      {

      $res = $this->dasmodel->deactivateEntireBox($box_number,$backend_setup_id,$mac,$device_id,$act,$old_stock_id);
      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : STB does not exist with customer..<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }
      if(!$res){
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : REPLACEMENT of old STB has failed.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;

      }


      }else {

      $res = 1;
      }
      $instock=$this->dasmodel->stb_moved_in_stock($old_stock_id);

      //If deactivation is succesfull Activate Same packages with new stock
      if($res){
      //echo $keepServices;
      $first = 1; $flag = false;
      $package_count = count($active_services);
      $pids=array();
      foreach($active_services as $product_id)
      {
      $pids[] = $product_id->product_id;
      }
      $isbasepkgExist=$this->dasmodel->addonpkg_after_basepkg($pids,$new_stock_id,$customer_id);
      if($isbasepkgExist==0)
      {
      if($isBoxExistWithCustomer==1){
      $keep_status=1;
      $this->dasmodel->updateBox('2','',$old_stock_id);
      $res = $this->dasmodel->update_device_stock($new_stock_id,$customer_id,$check_new_stcok_loc->employee_id,$device_id,$remarks,$keep_status);

      $this->dasmodel->saveInventoryLogs($new_stock_id,$mac,$serial_number,$customer_id,$check_new_stcok_loc->employee_id);
      }
      $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id,$new_stock_id,$remarks );

      }

      foreach($active_services as $service){
      $pid = $productId = $service->product_id;

      $price_type = $this->productsmodel->getservice($productId);

      $data['check'] =$this->productsmodel->check_product($productId,$customer_id);

      $product = $this->productsmodel->getproducts($productId);

      $cust_product_details = $this->productsmodel->getservice_id($productId,$customer_id);

      $start_date = date('Y-m-d');

      $end_date = $service->service_end_date;
      if($end_date<date('Y-m-d')) $end_date =  date("Y-m-d",strtotime(date("Y-m-d", strtotime($start_date)) . " +3 month"));


      if(isset($product[0]->plugin_id) && ($product[0]->plugin_id == 4))
      {
      $selectedChannels = $this->channelsmodel->getProductChannels($productId);

      }
      $pid=$productId;
      $adv_billing = $price_type[0]->advance_billing;
      $bp = $price_type[0]->base_price;
      $sp = $price_type[0]->setup_price;
      $tr = $price_type[0]->tax_rate;
      $tr2 = $price_type[0]->tax2;
      $tr3 = $price_type[0]->tax3;
      $taxrate1 = $price_type[0]->is_taxrate;
      $taxrate2 = $price_type[0]->is_taxrate1;
      $taxrate3 = $price_type[0]->is_taxrate2;
      $is_yearly = $price_type[0]->monthly_or_yearly;
      $btype = $price_type[0]->pricing_structure_type;
      $billschedule = ($price_type[0]->default_schedule!='')?$price_type[0]->default_schedule:-1;
      $rschedule = ($price_type[0]->recurring_schedule!='')?$price_type[0]->recurring_schedule:0;
      $bdate = ($price_type[0]->fixed_date!='')?$price_type[0]->fixed_date:0;
      $bill_date = date('Y-m-d');
      $calendar_type = ($price_type[0]->calendar_type!='')?$price_type[0]->calendar_type:0;
      $is_calendar = ($price_type[0]->is_calendar_month!='')?$price_type[0]->is_calendar_month:0;
      $inclusive_of_tax = ($price_type[0]->inclusive_of_tax!='')?$price_type[0]->inclusive_of_tax:0;
      $selectedChannels_array = $this->channelsmodel->getProductChannels($productId);
      $selectedChannels = array();
      foreach($selectedChannels_array as $c)
      {
      $selectedChannels[$c->channel_id] = $c->category_id;
      }
      $plid = 4;
      $qty = $cust_product_details->quantity;
      $valid_days = isset($plugins[0]->validity_days)?$plugins[0]->validity_days:$product[0]->validity_days;
      $plugins= array();
      $alaCarteChannelExist = 0;
      if($this->productsmodel->checkIsAlaCarte($productId)==1 && $this->productsmodel->checkIsAlaCarteProductChannelExist($customer_id,$productId)!=0)
      {
      $alaCarteChannelExist = 1;
      }
      if($alaCarteChannelExist == 0)
      {
      if($price_type[0]->pricing_structure_type == 1)
      {
      $btype = 1;

      $settings=array();
      $pins = isset($_POST['pin'])?$_POST['pin']:0;
      $i=0;

      $pid=$productId;
      $service_count = 1;
      $res_csid = $this->dasmodel->saveservices($customer_id,$start_date,$end_date,$settings,$pins,$productId,$plid,$qty,$valid_days,$plugins,$product,$selectedChannels,$first,$package_count,$device_id,$price_type,$is_old_customer=0,$base_price="",$reason_id=0,$remarks1="",$productId, $is_stb_replace=1,$service_count,$new_stock_id);

      if($res_csid > 0){	$flag= true; $i++; }
      //else{ $flag= false;}


      }
      else if($price_type[0]->pricing_structure_type == 2)
      {
      $btype = 2;
      $cid = $customer_id;
      // for recurring products
      $plugins= array();
      $settings = array();
      $pins = isset($_POST['pin'])?$_POST['pin']:0;
      $plid = 4;
      $service_count=0;
      //service count added by Gopi
      if($price_type[0]->bill_type == 1){
      $service_count=$this->productsmodel->getAddonStbProductCount($productId,$customer_id);
      $service_count=$service_count+1;
      }
      $res_csid = $this->dasmodel->saveservices($customer_id,$start_date,$end_date,$settings,$pins,$productId,$plid,$qty,$valid_days,$plugins,$product,$selectedChannels,$first,$package_count,$device_id,$price_type,$is_old_customer=0,$base_price="",$reason_id=0,$remarks1="",$productId, $is_stb_replace=1,$service_count,$new_stock_id);
      if($res_csid > 0){ $flag= true;  $i++;	}
      //else{ $flag= false;}

      }
      if($price_type[0]->pricing_structure_type == 3)
      {
      $btype = 1;

      $settings=array();
      $pins = isset($_POST['pin'])?$_POST['pin']:0;
      $i=0;

      $pid=$productId;
      $res_csid = $this->dasmodel->saveservices($customer_id,$start_date,$end_date,$settings,$pins,$productId,$plid,$qty,$valid_days,$plugins,$product,$selectedChannels,$first,$package_count,$device_id,$price_type,$is_old_customer=0,$base_price="",$reason_id=0,$remarks1="",$productId, $is_stb_replace=1,$service_count = 1, $new_stock_id);

      if($res_csid > 0){	$flag= true; $i++; }
      //else{ $flag= false;}


      }
      }
      $first++;
      }

      // Update old stock as defective stock
      if($flag){
      //if($isBoxExistWithCustomer==1)
      //{
      $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id,$new_stock_id,$remarks );

      $comment = "User Name:".$_SESSION['user_data']->employee->username.", ".$_SESSION['user_data']->first_name." ".$_SESSION['user_data']->
      last_name." has made ".$defective_serial_number." as Upgrade   ";
      $this->change_pass_model->updateLog($comment);

      }else{

      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$serial_number.' : New STB ACTIVATION has failed.<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }

      }


      }
      else if($old_stock_status==2)
      {
      if($isBoxExistWithCustomer==1){
      $res = $this->dasmodel->update_device_stock($new_stock_id,$customer_id,$check_new_stcok_loc->employee_id,$device_id,$remarks);
      $instock=$this->dasmodel->stb_moved_in_stock($old_stock_id);
      $this->dasmodel->saveInventoryLogs($new_stock_id,$mac,$serial_number,$customer_id,$check_new_stcok_loc->employee_id);

      $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id,$new_stock_id,$remarks );
      $comment = "User Name:".$_SESSION['user_data']->employee->username.", ".$_SESSION['user_data']->first_name." ".$_SESSION['user_data']->last_name." has made ".$defective_serial_number." as Upgrade   ";
      $this->change_pass_model->updateLog($comment);
      }

      }
      else
      {

      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : Replacement cannot be done with UNPAIRED STB..<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;

      }





      }

      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$new_serial_number.' : Replacement cannot be done with UNPAIRED STB..<br/>';
      $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;

      }
      }
      }





      }
      else
      {
      $row_counter++;
      $this->skipped_serials .= $row_counter.') '.$old_serial_number.' : STB not assigned to any customer.<br/>';
      // $this->skipped_rows_cust = $this->skipped_rows_cust.$row_counter .",";
      $invalidData = 1;
      }

      }
      $isFirstRow = false;
      }
      return ($i-1);
      }
     **/
    public function populateStbBulkUpgrade($worksheet, $data_type, $remarks) {
        if (!isset($_SESSION['user_data']))
            redirect(base_url());
        if ($_SESSION['user_data']->pwdchangereq)
            redirect('welcome/changePassword');
        if (isset($_SESSION['prerequisits']) && !$_SESSION['prerequisits'])
            redirect(base_url());
        $i = 1;
        if ($_SESSION['user_data']->employee_parent_type != '' && $_SESSION['user_data']->employee_parent_type != 'null') {
            $employee_details = $this->EmployeeModel->getEmployeeDetails(0, $_SESSION['user_data']->username);
            $employee_id = $employee_details->employee_id;
        } else {
            $employee_id = $_SESSION['user_data']->employee_id;
        }
        $rows = $worksheet->getElementsByTagName('Row');
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('Cell');
            $i = 0;
            foreach ($cells as $cell) {
                $i++;
            }
            if ($i < 1) {
                return "Invalid Number of columns, 0";
            }
            break;
        }
        $isFirstRow = true;
        $i = 1;
        //$this->sr_nos="(";
        $row_counter = 0;
        $invalidData = 0;
        $old_stock_id = 0;
        $new_stock_id = 0;
        $customer_id = 0;
        $old_serial_number = '';
        $new_serial_number = '';
        $dealer_id = $_SESSION['user_data']->dealer_id;
        foreach ($rows as $row) {
            $isEmptyRow = false;
            if (!$isFirstRow) {
                $invalidData = 0;
                $index = 1;
                $cells = $row->getElementsByTagName('Cell');
                foreach ($cells as $cell) {
                    $ind = $cell->getAttribute('Index');
                    $indb = $cell->getAttribute('ss:Index');
                    if ($ind != null)
                        $index = $ind;
                    if ($indb != null)
                        $index = $indb;
                    if ($index == 1)
                        $old_serial_number = trim($cell->nodeValue);
                    if ($index == 2)
                        $new_serial_number = trim($cell->nodeValue);
                    $index += 1;
                }
                if ($old_serial_number != '' && $new_serial_number != '') {
                    $replacement_type = 2;
                    $old_stb_details = $this->blkopr->isStockExist($old_serial_number, $data_type, $dealer_id);
                    $new_stb_details = $this->blkopr->isStockExist($new_serial_number, $data_type, $dealer_id);
                    $old_stock_id = 0;
                    $old_vc_number = '';
                    $old_stock_status = 0;
                    $old_box_number = '';
                    $old_backend_setup_id = 0;
                    $mac = '';

                    $is_old_STB_trash = 0;
                    $is_old_STB_defective = 0;
                    $is_old_STB_surrended = 0;
                    $old_stock_location = 0;
                    if (count($old_stb_details) > 0) {
                        $old_stock_id = $old_stb_details->stock_id;
                        $old_stb_id = $old_stb_details->stb_id;
                        $old_vc_number = $old_stb_details->vc_number;
                        $old_stock_status = $old_stb_details->status;
                        $old_box_number = $old_stb_details->box_number;
                        $old_backend_setup_id = $old_stb_details->backend_setup_id;
                        $mac = $old_stb_details->mac_address;
                        $is_old_STB_trash = $old_stb_details->is_trash;
                        $is_old_STB_defective = $old_stb_details->defective_stock;
                        $is_old_STB_surrended = $old_stb_details->isSurrended;
                        $old_stock_location = $old_stb_details->stock_location;
                        if ($is_old_STB_defective == 1) {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB is DEFECTIVE.<br/>';
                            $invalidData = 1;
                        }

                        if (!$invalidData && $is_old_STB_trash == 1) {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB is in unrepairable state (trash).<br/>';
                            $invalidData = 1;
                        }
                        if (!$invalidData && $is_old_STB_surrended == 1) {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB is SURRENDERED.<br/>';
                            $invalidData = 1;
                        }

                        if (!$invalidData && $old_stock_status == 3) {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB is BLOCKED.<br/>';

                            $invalidData = 1;
                        }
                        if ($invalidData == 0) {
                            $getCustomer_details = $this->dasmodel->get_customerDetails($customer_id = '', $old_serial_number, $old_vc_number);
                            if (!(count($getCustomer_details) > 0 && $getCustomer_details->customer_id > 0)) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB is not assigned to customer.<br/>';
                                $invalidData = 1;
                            }
                        }
                    } else {
                        $row_counter++;
                        $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Old STB does not exist.<br/>';
                        $invalidData = 1;
                    }
                    $new_backend_setup_id = 0;
                    $new_serial_num = '';
                    $new_box_vc_number = '';
                    $new_box_paired_in_cas = 0;
                    $new_box_pairing = 0;
                    $new_box_number = '';
                    $new_stb_status = 0;
                    $is_new_STB_defective = 0;
                    $is_new_STB_surrended = 0;
                    $is_new_STB_trash = 0;
                    $new_stock_id = 0;
                    $new_stock_location = 0;
                    if ($invalidData == 0) {
                        if (count($new_stb_details) > 0) {
                            $new_stock_id = $new_stb_details->stock_id;
                            $new_stb_id = $new_stb_details->stb_id;
                            $new_serial_num = $new_stb_details->serial_number;
                            $new_box_vc_number = $new_stb_details->vc_number;
                            $new_box_paired_in_cas = $new_stb_details->not_paired_in_cas;
                            $new_box_pairing = $new_stb_details->pairing;
                            $new_box_number = $new_stb_details->box_number;
                            $new_stb_status = $new_stb_details->status;
                            $is_new_STB_defective = $new_stb_details->defective_stock;
                            $is_new_STB_surrended = $new_stb_details->isSurrended;
                            $is_new_STB_trash = $new_stb_details->is_trash;
                            $new_backend_setup_id = $new_stb_details->backend_setup_id;
                            $new_stock_location = $new_stb_details->stock_location;
                            if ($is_new_STB_defective == 1) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB is DEFECTIVE.<br/>';
                                $invalidData = 1;
                            }
                            if (!$invalidData && $is_new_STB_trash == 1) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB is in unrepairable state (trash).<br/>';
                                $invalidData = 1;
                            }
                            if (!$invalidData && $is_new_STB_surrended == 1) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB is SURRENDERED.<br/>';
                                $invalidData = 1;
                            }
                            if (!$invalidData && $new_stb_status == 3) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB is BLOCKED.<br/>';

                                $invalidData = 1;
                            }
                            if ($invalidData == 0) {
                                $getnewBoxCustomer_details = $this->dasmodel->get_customerDetails($customer_id = '', $new_serial_num, $new_box_vc_number);
                                if (count($getnewBoxCustomer_details) > 0 && $getnewBoxCustomer_details->customer_id > 0) {
                                    $row_counter++;
                                    $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB is assigned to customer.<br/>';
                                    $invalidData = 1;
                                }
                            }
                        } else {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB does not exist.<br/>';
                            $invalidData = 1;
                        }
                    }
                    //$getCustomer_details = $this->dasmodel->get_customerDetails($customer_id='',$old_serial_number,$old_vc_number);
                    $customer_id = 0;
                    $device_id = 0;
                    if ($invalidData == 0) {
                        if (count($getCustomer_details) > 0 && $getCustomer_details->customer_id > 0) {
                            $customer_id = $getCustomer_details->customer_id;
                            $device = $this->blkopr->getDeviceId($customer_id, $old_serial_number);
                            if (count($device) > 0) {
                                $device_id = $device->customer_device_id;
                            }
                            //getting active services with stb type id to activate on new stb
                            $active_services = $this->dasmodel->getActiveServicesofCustomer($old_stock_id, $new_stock_id);
                            if (count($active_services) == 0) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : New STB type is not matching with product stb types. Please refresh the page.<br/>';
                                $invalidData = 1;
                            }
                            //$check_old_stock_loc = $this->blkopr->isSTBExistInLocation($old_serial_number);
                            //$check_new_stcok_loc = $this->blkopr->isSTBExistInLocation($new_serial_number);
                            // if(count($check_old_stock_loc)>0 && count($check_new_stcok_loc)>0)
                            // {
                            if ($old_stock_location != $new_stock_location) {
                                $row_counter++;
                                $this->skipped_serials .= $row_counter . ') Old serial number and new serail number does not exist in same location.<br/>';
                                $invalidData = 1;
                            }
                            //}
                            $is_paired = 0;
                            if ($new_box_pairing == 1) { // For Other than CISCO
                                if ($new_box_paired_in_cas == 0 && $new_box_vc_number != '' && $new_box_number != '') {
                                    $is_paired = 1;
                                } else {
                                    $is_paired = 0;
                                }
                            } else { //For CISCO
                                $is_paired = 1;
                            }
                            //if(count($check_old_stock_loc)>0 && count($check_new_stcok_loc)>0){
                            if ($old_stock_location == $new_stock_location) {
                                if ($is_paired) {
                                    if ($new_stock_id > 0) {
                                        $isTrailPacksExist = $this->blkopr->getTrailPacks($new_stock_id);
                                        if (count($isTrailPacksExist) > 0) {

                                            $res1 = 0;
                                            //If old stb does not exist with customer don't deactivate new stb.
                                            $extra_params = array('user_name' => '',
                                                'password' => '',
                                                'email' => '',
                                                'pin_code' => '',
                                                'user_id' => '',
                                                'stb_id' => $new_stb_id,
                                                'customer_id' => 0,
                                                'stock_id' => $new_stock_id);
                                            //$reason_id='0',$remarks='',$product_id='0',$product_name='',$dd=0, $ed=0,$is_from_black_list=0,$extra_params=array()
                                            $res1 = $this->dasmodel->deactivateEntireBox($isTrailPacksExist->box_number, $isTrailPacksExist->backend_setup_id, $isTrailPacksExist->mac_address, 0, $act = 'D', $new_stock_id, 0, '', 0, '', 0, 0, 0, $extra_params);

                                            if (!$res1) {
                                                $row_counter++;
                                                $this->skipped_serials .= $row_counter . ') ' . $isTrailPacksExist->box_number . ' : Trail pack deactivation of new STB.<br/>';
                                                $invalidData = 1;
                                            }
                                        }
                                        if ($invalidData == 0) {
                                            if ($old_stock_status == 1) {

                                                //getting active services to deactivation
                                                $active_services_old_stb = $this->dasmodel->getActiveServicesofCustomer($old_stock_id);
                                                $res = 0;
                                                if (count($active_services_old_stb) > 0) {
                                                    $act = 'D';
                                                    $extra_params1 = array('user_name' => $getCustomer_details->user_name,
                                                        'password' => $getCustomer_details->password,
                                                        'email' => $getCustomer_details->email,
                                                        'pin_code' => $getCustomer_details->pin_code,
                                                        'user_id' => $getCustomer_details->user_id,
                                                        'stb_id' => $old_stb_id,
                                                        'customer_id' => $customer_id,
                                                        'stock_id' => $old_stock_id);
                                                    $res = $this->dasmodel->deactivateEntireBox($old_box_number, $old_backend_setup_id, $mac, $device_id, $act, $old_stock_id, 0, '', 0, '', 0, 0, 0, $extra_params1);

                                                    if (!$res) {
                                                        $row_counter++;
                                                        $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : REPLACEMENT of old STB has failed.<br/>';

                                                        $invalidData = 1;
                                                    }
                                                } else {

                                                    $res = 1;
                                                }
                                                $instock = $this->dasmodel->stb_moved_in_stock($old_stock_id);

                                                //If deactivation is succesfull Activate Same packages with new stock							
                                                if ($res) {
                                                    //echo $keepServices;
                                                    $first = 1;
                                                    $flag = false;
                                                    $package_count = count($active_services);
                                                    $pids = array();
                                                    foreach ($active_services as $product_id) {
                                                        $pids[] = $product_id->product_id;
                                                    }
                                                    $isbasepkgExist = $this->dasmodel->addonpkg_after_basepkg($pids, $new_stock_id, $customer_id);
                                                    if ($isbasepkgExist == 0) {

                                                        $keep_status = 1;
                                                        $this->dasmodel->updateBox('2', '', $old_stock_id);
                                                        $res = $this->dasmodel->update_device_stock($new_stock_id, $customer_id, $check_new_stcok_loc->employee_id, $device_id, $remarks, $keep_status);

                                                        $this->dasmodel->saveInventoryLogs($new_stock_id, $mac, $new_serial_num, $customer_id, $check_new_stcok_loc->employee_id);
                                                        $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id, $new_stock_id, $remarks, 2, $employee_id);
                                                    }


                                                    if ($old_backend_setup_id == $new_backend_setup_id) {

                                                        foreach ($active_services as $service) {
                                                            $pid = $productId = $service->product_id;

                                                            $price_type = $this->productsmodel->getservice($productId);

                                                            $data['check'] = $this->productsmodel->check_product($productId, $customer_id);

                                                            $product = $this->productsmodel->getproducts($productId);

                                                            $cust_product_details = $this->productsmodel->getservice_id($productId, $customer_id);

                                                            $start_date = date('Y-m-d');

                                                            $end_date = $service->service_end_date;
                                                            if ($end_date < date('Y-m-d'))
                                                                $end_date = date("Y-m-d", strtotime(date("Y-m-d", strtotime($start_date)) . " +3 month"));


                                                            if (isset($product[0]->plugin_id) && ($product[0]->plugin_id == 4)) {
                                                                $selectedChannels = $this->channelsmodel->getProductChannels($productId);
                                                            }
                                                            $pid = $productId;
                                                            $adv_billing = $price_type[0]->advance_billing;
                                                            $bp = $price_type[0]->base_price;
                                                            $sp = $price_type[0]->setup_price;
                                                            $tr = $price_type[0]->tax_rate;
                                                            $tr2 = $price_type[0]->tax2;
                                                            $tr3 = $price_type[0]->tax3;
                                                            $taxrate1 = $price_type[0]->is_taxrate;
                                                            $taxrate2 = $price_type[0]->is_taxrate1;
                                                            $taxrate3 = $price_type[0]->is_taxrate2;
                                                            $is_yearly = $price_type[0]->monthly_or_yearly;
                                                            $btype = $price_type[0]->pricing_structure_type;
                                                            $billschedule = ($price_type[0]->default_schedule != '') ? $price_type[0]->default_schedule : -1;
                                                            $rschedule = ($price_type[0]->recurring_schedule != '') ? $price_type[0]->recurring_schedule : 0;
                                                            $bdate = ($price_type[0]->fixed_date != '') ? $price_type[0]->fixed_date : 0;
                                                            $bill_date = date('Y-m-d');
                                                            $calendar_type = ($price_type[0]->calendar_type != '') ? $price_type[0]->calendar_type : 0;
                                                            $is_calendar = ($price_type[0]->is_calendar_month != '') ? $price_type[0]->is_calendar_month : 0;
                                                            $inclusive_of_tax = ($price_type[0]->inclusive_of_tax != '') ? $price_type[0]->inclusive_of_tax : 0;
                                                            $selectedChannels_array = $this->channelsmodel->getProductChannels($productId);
                                                            $selectedChannels = array();
                                                            foreach ($selectedChannels_array as $c) {
                                                                $selectedChannels[$c->channel_id] = $c->category_id;
                                                            }
                                                            $plid = 4;
                                                            $qty = $cust_product_details->quantity;
                                                            $valid_days = isset($plugins[0]->validity_days) ? $plugins[0]->validity_days : $product[0]->validity_days;
                                                            $plugins = array();
                                                            $alaCarteChannelExist = 0;
                                                            if ($this->productsmodel->checkIsAlaCarte($productId) == 1 && $this->productsmodel->checkIsAlaCarteProductChannelExist($customer_id, $productId) != 0) {
                                                                $alaCarteChannelExist = 1;
                                                            }
                                                            if ($alaCarteChannelExist == 0) {
                                                                if ($price_type[0]->pricing_structure_type == 1) {
                                                                    $btype = 1;

                                                                    $settings = array();
                                                                    $pins = isset($_POST['pin']) ? $_POST['pin'] : 0;
                                                                    $i = 0;

                                                                    $pid = $productId;
                                                                    $service_count = 1;
                                                                    $res_csid = $this->dasmodel->saveservices($customer_id, $start_date, $end_date, $settings, $pins, $productId, $plid, $qty, $valid_days, $plugins, $product, $selectedChannels, $first, $package_count, $device_id, $price_type, $is_old_customer = 0, $base_price = "", $reason_id = 0, $remarks1 = "", $productId, $is_stb_replace = 1, $service_count, $new_stock_id);

                                                                    if ($res_csid > 0) {
                                                                        $flag = true;
                                                                    }
                                                                    //else{ $flag= false;}
                                                                } else if ($price_type[0]->pricing_structure_type == 2) {
                                                                    $btype = 2;
                                                                    $cid = $customer_id;
                                                                    // for recurring products
                                                                    $plugins = array();
                                                                    $settings = array();
                                                                    $pins = isset($_POST['pin']) ? $_POST['pin'] : 0;
                                                                    $plid = 4;
                                                                    $service_count = 0;
                                                                    //service count added by Gopi
                                                                    if ($price_type[0]->bill_type == 1) {
                                                                        $service_count = $this->productsmodel->getAddonStbProductCount($productId, $customer_id);
                                                                        $service_count = $service_count + 1;
                                                                    }
                                                                    $res_csid = $this->dasmodel->saveservices($customer_id, $start_date, $end_date, $settings, $pins, $productId, $plid, $qty, $valid_days, $plugins, $product, $selectedChannels, $first, $package_count, $device_id, $price_type, $is_old_customer = 0, $base_price = "", $reason_id = 0, $remarks1 = "", $productId, $is_stb_replace = 1, $service_count, $new_stock_id);
                                                                    if ($res_csid > 0) {
                                                                        $flag = true;
                                                                    }
                                                                    //else{ $flag= false;}
                                                                }
                                                                if ($price_type[0]->pricing_structure_type == 3) {
                                                                    $btype = 1;

                                                                    $settings = array();
                                                                    $pins = isset($_POST['pin']) ? $_POST['pin'] : 0;
                                                                    $i = 0;

                                                                    $pid = $productId;
                                                                    $res_csid = $this->dasmodel->saveservices($customer_id, $start_date, $end_date, $settings, $pins, $productId, $plid, $qty, $valid_days, $plugins, $product, $selectedChannels, $first, $package_count, $device_id, $price_type, $is_old_customer = 0, $base_price = "", $reason_id = 0, $remarks1 = "", $productId, $is_stb_replace = 1, $service_count = 1, $new_stock_id);

                                                                    if ($res_csid > 0) {
                                                                        $flag = true;
                                                                    }
                                                                    //else{ $flag= false;}
                                                                }
                                                            }
                                                            $first++;
                                                        }
                                                    } else {
                                                        $row_counter++;
                                                        $this->skipped_serials .= $row_counter . ') Old STB is replaced with new STB but services are not activated due to different server.<br/>';
                                                        $invalidData = 1;
                                                    }
                                                    // Update old stock as defective stock 

                                                    $i++;

                                                    $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id, $new_stock_id, $remarks, 2, $employee_id);

                                                    $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->
                                                            last_name . " has made " . $old_serial_number . " as Upgrade   ";
                                                    $this->change_pass_model->updateLog($comment);
                                                }
                                            } else if ($old_stock_status == 2) {

                                                $res = $this->dasmodel->update_device_stock($new_stock_id, $customer_id, $check_new_stcok_loc->employee_id, $device_id, $remarks);
                                                $instock = $this->dasmodel->stb_moved_in_stock($old_stock_id);
                                                $this->dasmodel->saveInventoryLogs($new_stock_id, $mac, $new_serial_num, $customer_id, $check_new_stcok_loc->employee_id);

                                                $this->dasmodel->setReplacermentUpdates($replacement_type, $customer_id, $old_stock_id, $new_stock_id, $remarks, 2, $employee_id);
                                                $comment = "User Name:" . $_SESSION['user_data']->employee->username . ", " . $_SESSION['user_data']->first_name . " " . $_SESSION['user_data']->last_name . " has made " . $old_serial_number . " as Upgrade   ";
                                                $this->change_pass_model->updateLog($comment);
                                                $i++;
                                            } else {

                                                $row_counter++;
                                                $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : Replacement cannot be done with UNPAIRED STB..<br/>';

                                                $invalidData = 1;
                                            }
                                        }
                                    }
                                } else {
                                    $row_counter++;
                                    $this->skipped_serials .= $row_counter . ') ' . $new_serial_number . ' : Replacement cannot be done with UNPAIRED STB..<br/>';

                                    $invalidData = 1;
                                }
                            }
                            //}
                        } else {
                            $row_counter++;
                            $this->skipped_serials .= $row_counter . ') ' . $old_serial_number . ' : STB not assigned to any customer.<br/>';
                            $invalidData = 1;
                        }
                    }
                }
            }
            $isFirstRow = false;
        }
        return ($i - 1);
    }

    
    /* public function testcronfile() {
        $controller_name = 'bulkoperation';
        $function_name = 'testcronstart';
        $file_to_lock = 'lock_testfilecron.lock';
        $this->lock_filetest($controller_name, $function_name, $file_to_lock);
    }
    
    public function lock_filetest($controller_name, $function_name, $file_to_lock) {
        $real_path = $_SERVER['DOCUMENT_ROOT'];
        $basename = $_SERVER['PHP_SELF'];
        $fullpath = $real_path . $basename;
        $cmd_path = substr($fullpath, 0, strrpos($fullpath, "index.php"));
        // echo "in lock_file ".$trans_id.' '.$pid.' '.$start_val.' '.$limit;
        $this->Cmd_path = $cmd_path . "index.php " . $controller_name . " ";
        $job_path = "/usr/bin/php " . $this->Cmd_path . $function_name;

        $upload_path = uploads_path();
        $lockFilePath = realpath("./") . "/" . $upload_path;
        $lock_file = $lockFilePath . $file_to_lock;
        $filehandle = fopen($lock_file, "a+");
        echo "<br>filehandle ".$filehandle; 
        if ($filehandle === False) {   //echo "File Open Failed\n";
            die();
        }
        if (flock($filehandle, LOCK_EX | LOCK_NB)) {
            echo "<br>file is locked first if";
            ftruncate($filehandle, 0);
            fputs($filehandle, getmypid());
            $out_array = array();
            exec($job_path, $out_array);
            print_r($out_array); //Gets the out put from executed file
            flock($filehandle, LOCK_UN); // don't forget to release the locktestcronstart
        } else {
            // throw an exception here to stop the next cron job
            //check if already process is running  if no process is running remove lock on file
            exec("ps auxwww|grep $controller_name |grep -v grep|grep $function_name", $pslist);
            echo "<br>count of pslist is".count($pslist);
            if (count($pslist) >0){
                // already some process is running wait until it is completed
                echo "<br>already some process is running wait until it is completed";
            }else{
                //it means no process is running
                echo "<br>it means no process is running";
                flock($filehandle, LOCK_UN);
            }
        }
        fclose($filehandle);
    }
    
    public function testcronstart(){
        echo "came to testcronstart";
        sleep(60);
    } */
    public function killNewCron()
    {
        $process_id = $this->input->post('process_id')?$this->input->post('process_id'):0;
        $response = 0;
        if($process_id>0)
        {
          $statusCheck=$this->Process_Model->getProcessIds($process_id);
          if($statusCheck== 1)
          {  
              $response = $this->Process_Model->updateProcessIds($process_id, $kill_status = 2); //updating the process to 2 -Kill
          }   
        }    
        echo $response;
    }

    public function casWiseBulkOperationsStart(){
        $backend_setup_id = ($this->uri->segment(3))?$this->uri->segment(3):0;
        $transaction_ids = ($this->uri->segment(4) && $this->uri->segment(4)!='' )? unserialize(urldecode($this->uri->segment(4))) : '';
        $cas_status = $this->uri->segment(5);
        $operation_id = ($this->uri->segment(6))?$this->uri->segment(6):0;
        $transaction_ids = explode(',',$transaction_ids);
        $blkopr = new bulkoperationmodel();
        $obj = new wsModel();      
        if($cas_status == 0){ //cas is in deactive state
            //loop through all transaction ids under cas
            foreach($transaction_ids as $inactive_cas_tarnsid){
                $update_inactive_cas_child_trans_status = $this->Process_Model->updateChildTransStatus($inactive_cas_tarnsid, 3,$backend_setup_id,$remarks = 'CAS is blocked'); //updating the process to 3 completed in eb_bulk_act_deact_file_content table 
                //check any active child trans exist or not.If not then update main transaction status to completed in eb_processes table
                $get_active_count_child_trans = $this->Process_Model->getActiveChildTransCount($inactive_cas_tarnsid);
                if($get_active_count_child_trans == 0){
                    $this->Process_Model->updateProcessTransIds($inactive_cas_tarnsid, $kill_status = 3); //updating the process to 3 completed and end_time
                }
            }
        } else { //cas is in active state

            if($backend_setup_id > 0 && count($transaction_ids)>0 && $operation_id>0) {
                foreach($transaction_ids as $trans_id){
                    $get_process_details = $this->Process_Model->getProcessDetails($trans_id,$operation_id);
                    if(count($get_process_details)){
                    $pid = $get_process_details[0]->pid;
                    $bulk_operation_process_id = $get_process_details[0]->process_id;
                    $uploaded_opns_details = $this->Process_Model->getProcessTransactionDetails($trans_id,$operation_id,$backend_setup_id);   
                    //print_r($uploaded_opns_details);exit;                 
                    if (0 == count($uploaded_opns_details)) {
                        continue;
                    } else {
                        $dealerid = isset($uploaded_opns_details->dealer_id) ? $uploaded_opns_details->dealer_id : 0;
                        $employee_id = isset($uploaded_opns_details->uploaded_by) ? $uploaded_opns_details->uploaded_by : 0;
                        $status = isset($uploaded_opns_details->act_type) ? $uploaded_opns_details->act_type : 0; //condition for activate or deactivation                        
                       /* $employee_details = $blkopr->getEmployeeDetails($employee_id);
                        $username = $employee_details->username;
                        $firstname = $employee_details->first_name;
                        $lastname = $employee_details->last_name;*/
                        if (1 == $status) {
                            $var = 'Activation';
                        } else if (2 == $status) {
                            $var = 'Deactivation';
                        } else if (3 == $status) {
                            $var = 'Reactivation';
                        }
                        //below code added for server wise splitting records based on app_server_id by Swaroop ON May 1 2019 - START    
                        $server_id = 1;
                        //check count of servers inserted in server_details table 
                        $allowed_servers = $this->appsmodel->getserver_details();//echo count($allowed_servers);
                        if(count($allowed_servers)>0){//ip should match with server_ip in server_details table
                            $server_id = 0;
                            $CommonFunctions = new CommonFunctions();
                            $interface_list = $CommonFunctions->get_interface_list();//echo '<pre>';print_r($interface_list);
                            if(count($interface_list)>0){
                                foreach($interface_list as $list){
                                    $ip_address = $CommonFunctions->get_ip_addr($list);//echo 'ipaddress - '.$ip_address.'----';
                                    if($ip_address != '(none)'){
                                        $server_id = $this->appsmodel->checkServerId($ip_address);
                                    }
                                    if($server_id>0){
                                        break;
                                    }
                                }
                            }
                        }

                        if($server_id==0){
                            echo "Cronjob not allowed to execute on this server";exit;
                        }
                        //below code added for server wise splitting records based on app_server_id by Swaroop ON May 1 2019 - END  

                        $trans_details = $blkopr->getcounts_on_transaction($trans_id,$server_id,$backend_setup_id);
                        $trans_max_count = (isset($trans_details) && count($trans_details) > 0) ? $trans_details['cnt'] : 0;
                        //$limit=1000;//This has to be changed based on count and how many processes we have to divide
                        $limit = $blkopr->get_limit_value_for_bulkoperations(); 
                        write_to_file('limit '.$limit);                       
                        $count = 1;
                        if ($limit > 0) {
                            $count_val = ceil($trans_max_count / $limit);
                            if ($count_val > 0) {
                                $count = $count_val;
                            }
                        } else {
                            $limit = $trans_max_count;
                        }

                        $start_val = (isset($trans_details) && count($trans_details) > 0) ? $trans_details['min_id'] : 0;
                        $no_of_fork_child_process = $obj->getLovValue('NO_OF_FORK_CHILD_PROCESS', $dealerid);
                        $maxChildren = (isset($no_of_fork_child_process) && $no_of_fork_child_process > 0) ? $no_of_fork_child_process : 1;   // Max number of forked processes
                        $fork_ids_all = array();     // Child process tracking array
                        //$end_val = 0;             
                        $peak_count = 1;
                        $counts = array();
                        $counts[0] = 0;
                        $counts[1] = 0;
                        write_to_file("BulkOperations " . $var . " :: Count " . $trans_max_count . " :: Memory :: " . memory_get_usage());
                        for ($i = 0; $i < $count; $i++) {
                            if ($i > 0) {
                                $start_val = $start_val + $limit;
                            } else {
                                $start_val = $start_val - 1;
                            }
                            $end_val = $start_val + $limit;
                            if (count($fork_ids_all) >= $maxChildren) {
                                $fork_id_single = pcntl_waitpid(-1, $child_status);
                                unset($fork_ids_all[$fork_id_single]); // Remove PID that exited from the list
                            }
                            // Fork the process
                            $fork_id_single = pcntl_fork();

                            if ($fork_id_single) { // Parent
                                if ($fork_id_single < 0) {
                                    // Unable to fork process, handle error here
                                    continue;
                                } else {
                                    // Add child PID to tracker array
                                    // Use PID as key for easy use of unset()
                                    $fork_ids_all[$fork_id_single] = $fork_id_single;
                                }
                            } else { // Child
                                $this->db->reconnect(); //Reconnecting the db connection each child process 
                                $controller_name = 'bulkoperation';
                                $function_name = 'bulkOperationsActivatieDeactivate';
                                $real_path = $_SERVER['DOCUMENT_ROOT'];
                                $basename = $_SERVER['PHP_SELF'];
                                $fullpath = $real_path . $basename;
                                $cmd_path = substr($fullpath, 0, strrpos($fullpath, "index.php"));

                                $this->Cmd_path = $cmd_path . "index.php " . $controller_name . " ";
                                $job_path = "/usr/bin/php " . $this->Cmd_path . $function_name . ' ' . $trans_id . ' ' . $pid . ' ' . $start_val . ' ' . $end_val. ' ' .$server_id.' '.$backend_setup_id;                               
                                exec($job_path, $out_array);
                                print_r($out_array);
                                exit(0);
                            }
                        }//end forloop
                        // Now wait for the child processes to exit. This approach may seem overly
                        // simple, but because of the way it works it will have the effect of
                        // waiting until the last process exits and pretty much no longer
                        foreach ($fork_ids_all as $fork_id) {
                            pcntl_waitpid($fork_id, $child_status);
                            unset($fork_ids_all[$fork_id]);
                        }
                        write_to_file("In Bulkoperations " . $var . " memory usage after the end of all process and after for loop " . memory_get_usage());

                        /* End of Process split */                        
                    }
                    //need to uncomment
                    while (pcntl_waitpid(0, $child_status) != -1);
                    //$this->load->database();
                    $this->db->reconnect(); // Reconnecting the db connection after child process completed  

                    write_to_file('transaction forking completed - '.$trans_id. ' backend setup id '.$backend_setup_id);
                    $other_active_child_trans = $this->Process_Model->getOtherChildTransIds($trans_id,3,$not_flag=1);
                     if($other_active_child_trans == 0){
                        $this->prepare_error_file($dealerid,$employee_id,$trans_id,$bulk_operation_process_id,$var);    
                    } else {
                        //$error_file_tries = 5;
                       // foreach($i=1;$i<=$error_file_tries,$i++){
                            sleep(2); //wait for 2 sec
                            $other_active_child_trans = $this->Process_Model->getOtherChildTransIds($trans_id,3,$not_flag=1);
                            if($other_active_child_trans == 0){
                                $this->prepare_error_file($dealerid,$employee_id,$trans_id,$bulk_operation_process_id,$var);    
                            }
                       // }                        
                    }                                   
                }
            }
            }          

        }
    } 

    public function prepare_error_file($dealerid,$employee_id,$trans_id,$bulk_operation_process_id,$var){
        $blkopr = new bulkoperationmodel();
       // write_to_file('transaction forking completed - '.$trans_id. ' backend setup id '.$backend_setup_id);
        $other_active_child_trans = $this->Process_Model->getOtherChildTransIds($trans_id,3,$not_flag=1);
       // write_to_file('transaction forking completed cheking other_active_child_trans status - '.$other_active_child_trans); 
        if($other_active_child_trans == 0){
            $employee_details = $blkopr->getEmployeeDetails($employee_id);
            $username = $employee_details->username;
            $firstname = $employee_details->first_name;
            $lastname = $employee_details->last_name;
            //write_to_file('transaction forking completed employee_details count - '.json_encode($employee_details));
            //write_to_file('transaction forking completed cheking inside other_active_child_trans - ');
            $this->Process_Model->updateProcessTransIds($trans_id,$kill_status = 3); //updating the process to 3 completed and end_time
            //update eb_process
            $successcount = 0;
            $count_message = '';
            $invalid_data_array = array();
            $invalid_data_array = $blkopr->get_invalid_data($trans_id);
            $fname='';
            if (count($invalid_data_array) > 0) {

                $invalid_data_string = isset($invalid_data_array['remarks']) ? $invalid_data_array['remarks'] : '';
                $successcount = isset($invalid_data_array['successcount']) ? $invalid_data_array['successcount'] : 0;
                $failedcount = isset($invalid_data_array['failedcount']) ? $invalid_data_array['failedcount'] : 0;

                /*                         * ***Start Code Storing Result In A File,Displaying In a Pop Up **** */
                //$bulk_operation_process_id = $this->input->post('bulk_operation_process_id');
                if ($failedcount > 0) {
                    $upload_path = uploads_path();
                    $uppath = $upload_path . 'error_files/';
                    $basename = time();
                    $file_name = "$uppath/invalid_data_$basename.html";
                    $fname = "invalid_data_$basename.html";
                    $fh = fopen($file_name, 'w+') or die("can't open file");

                    fwrite($fh, $invalid_data_string);
                    //die();
                    fclose($fh);
                    $link = base_url() . $file_name;
                }
            }

            $messages = '';
            $smsg = '';
            $fmsg = '';
            if ($successcount > 0) {
                $smsg = $successcount . ' STBs ' . $var . ' successfully.';
            }

            if ($failedcount > 0) {
                $fmsg = $failedcount . 'STBs failed.';
            }
                      
            $messages .= $smsg . ' ' . $fmsg;
            $this->Process_Model->updateProcessFilename($bulk_operation_process_id, $fname, $messages);

            $comment = "User Name:" . $username . ", " . $firstname . " " . $lastname . " has done " . $successcount . " STBs activated";
            $this->change_pass_model->updateLog($comment, $dealerid, $employee_id);  

        }
    }
}

?>
