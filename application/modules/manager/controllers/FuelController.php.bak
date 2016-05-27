<?php

class Manager_FuelController extends Zend_Controller_Action
{

	protected $_user_auth;
	
	public function init()
	{
		/* Initialize action controller here */
		$storage = new Zend_Auth_Storage_Session();
		$data = $storage->read();
		 
		if(!$data){
			$this->_redirect('auth/login');
		}
		 
		$this->_user_auth = $data;
		
		$this->view->user_auth = $this->_user_auth;

        if($this->_user_auth->type != 'admin') {
            throw new Exception('You are not allowed to access this module.');
        }
    }

    public function indexAction()
    {
        // action body
    }
    
    public function importAction() {
    	if($this->_request->isPost()) {
    		set_time_limit(0);
    		$upload = new Zend_File_Transfer_Adapter_Http();
    		
    		$upload->setDestination('/tmp');
    		
    		
    		if (!$upload->receive()) {
	    		$messages = $upload->getMessages();
	    		die(implode("\n", $messages));
    		} else {
    			$filename = $upload->getFilename();
    			
    			// echo $filename;
    			
    			$file = new SplFileObject($filename);
    			$file->setFlags(SplFileObject::READ_CSV);
    			
    			$saved = array();
    			$orphans = array();
    			$gascard_no_user = array();
    			$gascard_employee = array();
    			
    			$i = 0;
    			
    			foreach ($file as $row) {
    				// preprint($row,1);
    				array_map('trim', $row);
    				
    				if(!$i > 0) {
    					$i++; continue; // skip header row
    				}
    				
    				
    				
    				if(isset($row[7]) && $row[7] != '' && isset($row[15]) && $row[15] != '') {
    					// if(trim($row[7]) == '') continue;
    					$invoice_date = date('Y-m-d h:i:s', strtotime($row[12]));
    					$data = array(
   							'gascard' => $row[7]
    						, 'raw_invoice_date' => $row[12]
    						, 'invoice_date' => $invoice_date 
    						, 'product_quantity' => $row[17]
    						, 'invoice_number' => $row[15]
    						, 'station_name' => $row[13]
    						, 'product' => $row[16]
    						, 'fuel_cost' => $row[18]
    					);
    						
    					if(in_array($row[7], $gascard_no_user)) {
    						$orphans[] = $data;
    						continue;
    					}
    					
    					// echo "<br /> Looking for user with gas card {$row[7]}...";
    					
    					if(isset($gascard_employee[$row[7]])) {
    						$Employee = $gascard_employee[$row[7]];
    					} else {
    						$EmployeeMap = new Messerve_Model_Mapper_Employee();
    						$Employee = $EmployeeMap->findOneByField('gascard',$row[7]);
    						$gascard_employee[$row[7]] = $Employee; 
    					}
    					
    					if($Employee && $Employee->getId() > 0) {
    						$Fuel = new Messerve_Model_Fuelpurchase();
    						
    						$Fuel->getMapper()->findOneByField(
   								array(
                                    'invoice_date'
                                    ,'invoice_number'
                                    ,'employee_id'
                                )
   								, array(
                                    $invoice_date
                                    , "{$row[15]}"
                                    , $Employee->getId()
                                )
   							, $Fuel);

                            if($Fuel->getId() > 0) {
                                echo "Skipped existing fuel record: "; preprint($Fuel->toArray());
                                continue;
                            }

    						$Fuel
    							->setOptions($data)
    							->setEmployeeId($Employee->getId())
    							->save();
    						
    						// preprint($Employee->toArray());
    						
    						$data['employee'] = $Employee->getFirstname() . ' ' . $Employee->getLastname() . ' ' . $Employee->getEmployeeNumber();
    						$saved[] = $data;
    					} else {
    						$gascard_no_user[] = $row[7];
    						$orphans[] = $data;
    					}
    					
    				}
    			}
    			
    			$this->view->saved = $saved;
    			$this->view->orphans = $orphans;

                echo "<h1>SAVED : " . count($saved) . "</h1>";
                // preprint($orphans);
    		}
    	}
    }
}