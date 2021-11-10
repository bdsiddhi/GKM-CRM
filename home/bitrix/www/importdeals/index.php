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
			
			$response = array();
			
			foreach($dealDatas as $dealData){
				
				$data = array(
					'fields' =>array(
						'TITLE' => $dealData['Auftragsname'],
						'ASSIGNED_BY_ID' => '1',
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
			}
			
			if(count($response) > 0){
						
				echo 'Success: Data imported!'; 
				
				echo "\r\n\r\n";
				
				echo 'Generated CRM Lead IDs: '.implode(', ',$response);
				
				$ch = curl_init("https://gk-werkzeugmaschinen.com/Bitrix/DeleteCSV.php?file=".$filename);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($ch);
				curl_close($ch);
				
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
