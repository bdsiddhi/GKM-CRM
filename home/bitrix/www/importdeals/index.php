<?php
require_once (__DIR__.'/crest.php');

$result = CRest::call('profile');

if($result['result']['ID']){
	
	$filename = 'CSV_'.date('Ymd',strtotime('-1 day')).'.csv';
	
	$ch = curl_init("https://gk-werkzeugmaschinen.com/Bitrix/".$filename);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$fileData = curl_exec($ch);
	curl_close($ch);
	
	if(!empty($fileData)){
	
		$deals = $dealDatas = array();	
		if(($open = fopen("https://gk-werkzeugmaschinen.com/Bitrix/".$filename, "r")) !== FALSE){
	    	while(($data = fgetcsv($open, 1000, ";")) !== FALSE){        
	      		$deals[] = $data; 
	    	}
	    	fclose($open);
	  	}
		
		$headers = $deals[0];
		array_splice($deals,0,1);
		
		foreach($deals as $key=>$value){
			foreach($headers as $k=>$v){
				/*$dealDatas[$key][utf8_decode($v)] = utf8_decode($value[$k]);*/
				$dealDatas[$key][utf8_encode($v)] = utf8_encode($value[$k]);	
			}
		}
		
		/*echo '<pre>';print_r($dealDatas);echo '</pre>'; exit;*/
		
		if(count($dealDatas) > 0){
			
			$response = $log_data = array();
			
			foreach($dealDatas as $dealData){
				
				$responsible = $dealData['Verantwortliche'];
				
				if($responsible == "Stephan Althaus"){
					$id = 199;
				}else if($responsible == "Gerhard Kraft"){
					$id = 33;
				}else if($responsible == "Nils Rückert"){
					$id = 1;
				}else if($responsible == "Thomas Sturm"){
					$id = 7;
				}else if($responsible == "Claus Commercon"){
					$id = 29;
				}else if($responsible == "Michael Kirbach"){
					$id = 25;
				}else if($responsible == "Siegbert Tiedtke"){
					$id = 160;
				}else{
					$id = 1;
				}
				
				$data = array(
					'fields' =>array(
						'TITLE' => $dealData['Auftragsname'],
						'ASSIGNED_BY_ID' => $id,
						'DATE_CREATE' => date('Y-m-d',strtotime($dealData['Anfangsdatum'])),
						'OPPORTUNITY' => $dealData['Betrag'],
						'COMMENTS' => $dealData['Produkt'],
						'SECOND_NAME' => $dealData['Vorname'],
						'LAST_NAME' => $dealData['Nachname'],
						'HONORIFIC' => $dealData['Anrede'],
						'EMAIL' => array(array('VALUE'=>$dealData['Email dienstlich'],'VALUE_TYPE'=>'WORK')),
						'COMPANY_TITLE' => $dealData['Unternehmen'],
						'ADDRESS' => $dealData['Straße']?$dealData['Straße']:$dealData['Stra&szlige'],
						'ADDRESS_POSTAL_CODE' => $dealData['PLZ'],
						'ADDRESS_COUNTRY' => $dealData['Land'],
						'PHONE' => array(array('VALUE'=>$dealData['Telefon dienstlich'],'VALUE_TYPE'=>'WORK')),
						'SOURCE_ID' => 4
			      	)
				);
				
				/*echo '<pre>';print_r($data);echo '</pre>'; exit;*/
				
				$lead = CRest::call('crm.lead.add',$data);
				
				/*echo '<pre>';print_r($lead);echo '</pre>'; exit;*/
				
				$response[] = $lead['result'];
				
				$dealData['responsible_person_with_id'] = $responsible.':'.$id;
				$log_data[] = $dealData;
			}
			
			if(count($response) > 0){
						
				echo 'Success: Data imported!'; 
				
				echo "\r\n\r\n";
				
				echo 'Generated CRM Lead IDs: '.implode(', ',$response);
				
				$ch = curl_init("https://gk-werkzeugmaschinen.com/Bitrix/DeleteCSV.php?file=".$filename);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				
				$logs  = "Automation script log: ".date("Y-m-d H:i:s").PHP_EOL
						.print_r($log_data,true).PHP_EOL.
				        "----------------------------------------------".PHP_EOL;
				file_put_contents('script.log',$logs,FILE_APPEND);
				
				exit;
				
			}else{
				echo 'Error: Something went wrong to import data in CRM!'; exit;	
			}
			
		}else{
			echo 'Error: Correct data not found in CSV file!'; exit;
		}	
		
	}else{
		echo 'Error: '.$filename.' file does not exist at specified path!'; exit;
	}	
	
}else{
	echo '<pre>';print_r($result);echo '</pre>'; exit;
}
