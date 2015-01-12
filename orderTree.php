<?php 
	/**************************************************************
	Developer: Rutherford Le
	Project: Magento eCommerce
	Core language: PHP, JavaScript, HTML, CSS
	System developed: Windows 7
	Purpose: This application utilizes Magento API to pass order 
	information Magento to Oracle
	Comments:
	**************************************************************/
	try{
					
		//CONNECTION

		include_once 'import_includes/connectAPI.php';

		//ORDER FROM MAGENTO TO ORACLE

		$start = date('Y-m-d H:i:s');
		echo 'orderTree Start: '.$start.'<br/>';

		$result = $proxy->salesOrderList($sessionId);


		foreach ($result as $row){

			try {

				$duplicateID = '';
				$incID = $row->increment_id;
				//echo ' ';
				//echo $row->order_id;
				//echo ' ';
				$resultOracle = oci_parse($connect, "SELECT WEBSITE_ORDER_NUMBER FROM ECOMF_STG_HEADERS_ALL WHERE WEBSITE_ORDER_NUMBER = :incID"); //new values or flag set to modified
				oci_bind_by_name($resultOracle, ":incID", $incID);
				oci_execute($resultOracle);

				if($resultOracle === FALSE) {
					die(mysql_error()); // better error handling
				}

				while (($row1 = oci_fetch_array($resultOracle, OCI_BOTH)) != false) {

					if($row1['WEBSITE_ORDER_NUMBER'] != '')
						$duplicateID = $row1['WEBSITE_ORDER_NUMBER'];


				}

				if($incID != '' && $duplicateID == ''){
					unset($orderID,$orderNumber,$created_at,$tax_amount,$grandTotal,$customerID,$customer_email,$billingName,$billing_firstname, $billing_lastname,$billTelephone,
					$billingStreet,$billingCity, $billingState, $billingZip, $billingCountry,$shippingID,$shippingName,$shipping_firstname,$shipping_lastname,$shippingStreet,
					$shippingTelephone,$shippingCity,$shippingState,$shippingZip,$shippingCountry,$total_qty_ordered,$shipping_amount_header,$shipping_method,$amount_ordered,
					$po_number,$created_at,$discount_amount);
					$result2 = $proxy->salesOrderInfo($sessionId, $incID);

					//var_dump($row);
					//echo $result2->order_id; //increment id from object $result2
					//echo '<br/>'.@$result2->shipping_address->street.'<br/>'; //street within shipping address of object $result2
					//echo @$result2->status.'<br/>';
					$api_status  = @$result2->status;
					//if(($api_status == 'processing') || ($api_status == 'pending')){
						$orderID = $row->order_id;
						$orderNumber = strval($row->increment_id);
						$created_at = $row->created_at;
						$tax_amount  = $row->tax_amount ;
						$grandTotal = $row->grand_total;
						//$total_paid  = $row->total_paid ;
						$customerID = @$row->customer_id;
						$customer_email = @$row->customer_email;
						$billingName = $row->billing_name;
						$billing_firstname = $row->billing_firstname;
						$billing_lastname = $row->billing_lastname;
						$billTelephone = @$row->telephone;
						$billingStreet = @$result2->billing_address->street;
						$billingCity = @$result2->billing_address->city;
						$billingState = @$result2->billing_address->region;
						$billingZip = @$result2->billing_address->postcode;
						$billingCountry = @$result2->billing_address->country_id;
						$shippingID = @$row->shipping_address_id;
						$shippingName = @$row->shipping_name;
						$shipping_firstname = @$row->shipping_firstname;
						$shipping_lastname = @$row->shipping_lastname;
						$shippingStreet = @$result2->shipping_address->street;
						$shippingTelephone = @$result2->shipping_address->telephone;
						$shippingCity = @$result2->shipping_address->city;
						$shippingState = @$result2->shipping_address->region;
						$shippingZip = @$result2->shipping_address->postcode;
						$shippingCountry = @$result2->shipping_address->country_id;
						$total_qty_ordered = @$result2->total_qty_ordered;
						$shipping_amount_header = @$result2->shipping_amount;
						$shipping_method  = @$result2->shipping_method;
						$amount_ordered = @$result2->payment->amount_ordered;
						$po_number  = @$result2->payment->po_number;
						//$created_at = @$result2->created_at;
						$discount_amount  = @$result2->discount_amount;

						if(($shippingName == '')  && ($shippingStreet == '')){
							$shippingName = $billingName;
							$shipping_firstname = $billing_firstname;
							$shipping_lastname = $billing_lastname;
							$shippingStreet = $billingStreet;
							$shippingTelephone = $billTelephone;
							$shippingCity = $billingCity;
							$shippingState = $billingState;
							$shippingZip = $billingZip;
							$shippingCountry = $billingCountry;
						}
						
						foreach($result2->status_history as $row){
							$comment = $row->comment;
							if (preg_match('/"([^Transaction ID: "]+)"/', $comment, $trans)) {
								
							}
						}
						$today = date('Y-m-d H:i:s');
							
						//if(($tax_amount == 0) || ($tax_amount == NULL) || ($tax_amount == ''))
							//$taxExemptFlag = 'Y';
						$Spares_Order = 'FCPAECOMMERCE';
						$status = 'WAITING_FOR_SYNC';
						$createdBy = '-1';
						$lastUpdateBy = '-1';
						
						unset($ids, $billNumber, $shipNumber);
						$ids = array();
						$i=0;
						
						if($customerID != ''){
							$result4 = $proxy->customerCustomerInfo($sessionId, $customerID);
							$groupID = $result4->group_id;
							$result3 = $proxy->customerCustomerInfo($sessionId, $customerID, array('bill_number', 'ship_number'));					
							foreach($result3->additional_attributes as $row){
								foreach ($row as $row1){
									
									if($row1 != 'bill_number' && $row1 != 'ship_number'){
										$ids[$i] = $row1;
										$i++;
									}
								}					
							}
						}
						
						 $billNumber =  @$ids[0];
						 $shipNumber =  @$ids[1];
						//Variables set so binding can take place

						$sql="INSERT INTO ECOMF_STG_HEADERS_ALL (ORDER_SOURCE, STAGING_HEADER_ID, WEBSITE_ORDER_ID, ORDERED_DATE, TOTAL_AMOUNT, 
								BILL_TO_CUSTOMER, BILL_TO_ADDRESS1, BILL_TO_CITY, BILL_TO_STATE, BILL_TO_ZIP, BILL_TO_COUNTRY, BILL_TO_CONTACT_FIRST_NAME, 
								BILL_TO_CONTACT_LAST_NAME, BILL_TO_USER_PHONE, BILL_TO_USER_EMAIL,
								SHIP_TO_CUSTOMER, SHIP_TO_ADDRESS1, SHIP_TO_CITY, SHIP_TO_STATE, SHIP_TO_ZIP, SHIP_TO_COUNTRY, SHIP_TO_CONTACT_FIRST_NAME, 
								SHIP_TO_CONTACT_LAST_NAME, SHIP_TO_USER_PHONE, SHIP_METHOD, EXPECTED_ARRIVAL_DATE, IN_PROCESS_STATUS, CREATION_DATE, CREATED_BY, LAST_UPDATE_DATE, LAST_UPDATED_BY,
								SELLER_TRANSACTION_ID, AMOUNT_PAID, TRANSACTION_DATE, BILL_TO_CUST_NUMBER, SHIP_TO_CUST_NUMBER, DISCOUNT_AMOUNT, WEBSITE_CUST_ID, 
								SHIPPING_AMOUNT, WEBSITE_ORDER_NUMBER, CUSTOMER_GROUP_ID, SHIP_TO_USER_EMAIL, PO_NUMBER)
								VALUES 
								(:orderSource, ECOMF_STG_HEADERS_S.nextval, :orderID,  TO_DATE(:created_at,'YYYY-MM-DD HH24:MI:SS'), :grandTotal,
								:billingName, :billingStreet, :billingCity, :billingState, :billingZip, :billingCountry, :billing_firstname, :billing_lastname, :billTelephone, :customer_email,
								:shippingName, :shippingStreet, :shippingCity, :shippingState, :shippingZip, :shippingCountry, :shipping_firstname,
								:shipping_lastname, :shippingTelephone, :shipping_method, sysdate, :status, sysdate, :createdBy, sysdate, :lastUpdateBy, 
								:trans, :amount_ordered, TO_DATE(:created_at,'YYYY-MM-DD HH24:MI:SS'), :billNumber, :shipNumber, :discount_amount, :customerID, :shipping_amount_header, :orderNumber, 
								:groupID, :customer_email, :po_number)";

						$stid = oci_parse($connect, $sql);
						
						// Bind to increase security. Binding treats binded variable so it not part of the SQL statement. Will not require quoting or escaping.
						
						oci_bind_by_name($stid, ":orderSource", $Spares_Order);
						oci_bind_by_name($stid, ":orderID", $orderID);
						oci_bind_by_name($stid, ":orderNumber", $orderNumber);
						//oci_bind_by_name($stid, ":orderID", $created_at);
						//oci_bind_by_name($stid, ":taxExemptFlag", $taxExemptFlag);
						oci_bind_by_name($stid, ":grandTotal", $grandTotal);
						//oci_bind_by_name($stid, ":total_paid", $total_paid);
						oci_bind_by_name($stid, ":billingName", $billingName);
						oci_bind_by_name($stid, ":billingStreet", $billingStreet);
						oci_bind_by_name($stid, ":billingCity", $billingCity);
						oci_bind_by_name($stid, ":billingState", $billingState);
						oci_bind_by_name($stid, ":billingZip", $billingZip);
						oci_bind_by_name($stid, ":billingCountry", $billingCountry);
						oci_bind_by_name($stid, ":billing_firstname", $billing_firstname);
						oci_bind_by_name($stid, ":billing_lastname", $billing_lastname);
						oci_bind_by_name($stid, ":billTelephone", $billTelephone);
						oci_bind_by_name($stid, ":customer_email", $customer_email);
						oci_bind_by_name($stid, ":shippingName", $shippingName);
						oci_bind_by_name($stid, ":shippingStreet", $shippingStreet);
						oci_bind_by_name($stid, ":shippingCity", $shippingCity);
						oci_bind_by_name($stid, ":shippingState", $shippingState);
						oci_bind_by_name($stid, ":shippingZip", $shippingZip);
						oci_bind_by_name($stid, ":shippingCountry", $shippingCountry);
						oci_bind_by_name($stid, ":shipping_firstname", $shipping_firstname);
						oci_bind_by_name($stid, ":shipping_lastname", $shipping_lastname);
						oci_bind_by_name($stid, ":shippingTelephone", $shippingTelephone);
						oci_bind_by_name($stid, ":shipping_method", $shipping_method );
						oci_bind_by_name($stid, ":status", $status);
						oci_bind_by_name($stid, ":createdBy", $createdBy);
						oci_bind_by_name($stid, ":lastUpdateBy", $lastUpdateBy);
						oci_bind_by_name($stid, ":trans", $trans[1]);
						oci_bind_by_name($stid, ":amount_ordered", $amount_ordered);
						oci_bind_by_name($stid, ":created_at", $created_at);
						oci_bind_by_name($stid, ":billNumber", $billNumber);
						oci_bind_by_name($stid, ":shipNumber", $shipNumber);
						oci_bind_by_name($stid, ":discount_amount", $discount_amount );
						oci_bind_by_name($stid, ":customerID", $customerID );
						oci_bind_by_name($stid, ":shipping_amount_header", $shipping_amount_header );
						oci_bind_by_name($stid, ":groupID", $groupID );
						oci_bind_by_name($stid, ":po_number", $po_number );
						
							
						$r = oci_execute($stid);
						if (!$r) { 
							$e = oci_error($stid);
							throw new Exception($e['message']);
						}
							
						foreach($result2->items as $row2){
							if($row2->price!=0){
								$sku = $row2->sku;
										
								$result3 = oci_parse($connect, 'SELECT product_model FROM ecomf_product_master 
																WHERE INVENTORY_ITEM_ID = :sku'); //new values or flag set to modified
																		
								oci_bind_by_name($result3, ":sku", $sku);
								oci_execute($result3);

								if($result3 === FALSE) {
									die(mysql_error()); // better error handling
								}
								while (($row3 = oci_fetch_array($result3, OCI_BOTH)) != false) {
									$catName = $row3['PRODUCT_MODEL']; // French Cuff
								}
								$itemID = $row2->item_id;
								$productName = $row2->name;
								$price = $row2->price;
								$tax_amount  = $row2->tax_amount;
								$qtyOrdered = (int)$row2->qty_ordered;
								$base_row_total = $row2->base_row_total;
								$shipping_amount = @$row2->shipping_amount;
								$tax_amount = $row2->tax_amount;
								$discount_amount = $row2->discount_amount;
								$rowTotal = $row2->row_total;
						
								$getHeader = oci_parse($connect, "SELECT STAGING_HEADER_ID FROM ECOMF_STG_HEADERS_ALL WHERE WEBSITE_ORDER_NUMBER = :orderNumber"); //new values or flag set to modified
									oci_bind_by_name($getHeader, ":orderNumber", $orderNumber);
									oci_execute($getHeader);

								if($getHeader === FALSE) {
									die(mysql_error()); // better error handling
								}

								while (($row2 = oci_fetch_array($getHeader, OCI_BOTH)) != false) {


									$stagingHeaderID = $row2['STAGING_HEADER_ID'];


								}

								$sql="INSERT INTO ECOMF_STG_LINES_ALL (WEBSITE_ORDER_ID, WEBSITE_ORDER_LINE_ID, STAGING_HEADER_ID, STAGING_LINE_ID, INVENTORY_ITEM_ID, MODEL_NUMBER, PART_NUMBER, 
									UNIT_PRICE, QUANTITY, PRODUCT_AMOUNT, FREIGHT_AMOUNT, TAX_AMOUNT, DISCOUNT_AMOUNT, TOTAL_LINE_AMOUNT,
									SCHEDULED_SHIP_DATE, IN_PROCESS_STATUS, CREATION_DATE, CREATED_BY, LAST_UPDATE_DATE, LAST_UPDATED_BY)
									VALUES
									(:orderID, :itemID, :stagingHeaderID, ECOMF_STG_LINES_S.nextval , :sku, :catName, :productName,
									:price, :qtyOrdered, :base_row_total, :shipping_amount, :tax_amount, :discount_amount, :rowTotal,
									sysdate, :status, sysdate, :createdBy, sysdate, :lastUpdateBy)";
									$stid = oci_parse($connect, $sql);
									
								oci_bind_by_name($stid, ":orderID", $orderID);
								oci_bind_by_name($stid, ":itemID", $itemID);
								oci_bind_by_name($stid, ":stagingHeaderID", $stagingHeaderID);
								oci_bind_by_name($stid, ":sku", $sku);
								oci_bind_by_name($stid, ":catName", $catName);
								oci_bind_by_name($stid, ":productName", $productName);
								oci_bind_by_name($stid, ":price", $price);
								oci_bind_by_name($stid, ":qtyOrdered", $qtyOrdered);
								oci_bind_by_name($stid, ":base_row_total", $base_row_total);
								oci_bind_by_name($stid, ":shipping_amount", $shipping_amount);
								oci_bind_by_name($stid, ":tax_amount", $tax_amount);
								oci_bind_by_name($stid, ":discount_amount", $discount_amount);
								oci_bind_by_name($stid, ":rowTotal", $rowTotal);
								oci_bind_by_name($stid, ":status", $status);
								oci_bind_by_name($stid, ":createdBy", $createdBy);
								oci_bind_by_name($stid, ":lastUpdateBy", $lastUpdateBy);
								
								$r = oci_execute($stid);
								if (!$r) { 
									$e = oci_error($stid);
									throw new Exception($e['message']);
								}
							}
						}
						$r = oci_commit($connect);
						if (!$r) {
							$e = oci_error($connect);
							trigger_error(htmlentities($e['message']), E_USER_ERROR);
						}
							//echo 'Grand Total:'.$row->grand_total.'<br/>';
							//echo '<pre>' . var_export($result2, true) . '</pre>';
				//	}

						//echo ' ';
						//echo $customerID = @$row->customer_id;
						//echo ' ';
						//echo $row->status;
						//echo ' ';
						//if($customerID != NULL){
						//$result1 = $proxy->customerAddressList($sessionId,$customerID);
						//foreach($result1 as $row1){
						//echo $row1->street;
						//}
						//}
						//var_dump($result);
						//echo '<br/>';
				}
							//throw new Exception('Increment ID: '.$incID);
			} 
			catch (Exception $e) {
				$error = 'Caught exception: '.  $e->getMessage();
				$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
												VALUES
												('Spares and Consumables', 'Orders', :orderID, :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
				$stid = oci_parse($connect, $sql);
				
				oci_bind_by_name($stid, ":orderID", $orderID);
				oci_bind_by_name($stid, ":error", $error);
				
				$r = oci_execute($stid);
				if (!$r) {
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
				$errorHeader = oci_parse($connect, "UPDATE ECOMF_STG_HEADERS_ALL SET IN_PROCESS_STATUS = 'ERROR' WHERE WEBSITE_ORDER_ID = :orderID"); //new values or flag set to modified
				oci_bind_by_name($errorHeader, ":orderID", $orderID);
				oci_execute($errorHeader);
				if (!$r) {
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
				$errorLines = oci_parse($connect, "UPDATE ECOMF_STG_LINES_ALL SET IN_PROCESS_STATUS = 'ERROR' WHERE WEBSITE_ORDER_ID = :itemID"); //new values or flag set to modified
				oci_bind_by_name($errorLines, ":itemID", $itemID);
				oci_execute($errorLines);
				if (!$r) {
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
			}

		}
	}
	catch (Exception $e) {
		$error1 = 'Connection Error: '.  $e->getMessage();
		$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
										VALUES
										('Spares and Consumables', 'Orders', :error, 'AWAITING_ACTION', 'HIGH', sysdate)";
		$stid = oci_parse($connect, $sql);
		
		oci_bind_by_name($stid, ":error", $error1);
		
		$r = oci_execute($stid);
		if (!$r) {
			$e = oci_error($stid);
			trigger_error(htmlentities($e['message']), E_USER_ERROR);
		}
	}
	$finish = date('Y-m-d H:i:s');
	echo 'orderTree Complete: '.$finish.'<br/>';

	oci_close($connect);
	$proxy->endSession($sessionId);

?>