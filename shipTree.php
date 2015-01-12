<?php
	/**************************************************************
	Developer: Rutherford Le
	Project: Magento eCommerce
	Core language: PHP, JavaScript, HTML, CSS
	System developed: Windows 7
	Purpose: This application utilizes Magento API to pass shipment 
	from Oracle to Magento
	Comments:
	**************************************************************/
	
	try{
			
		//CONNECTION

		include_once 'import_includes/connectAPISoap.php';

		//SHIPMENT FROM ORACLE TO MAGENTO

		$start = date('Y-m-d H:i:s');
		$shipFlag = 0;
		echo 'shipTree Start: '.$start.'<br/>';

		$resultOracle = oci_parse($connect, "SELECT eship.WAYBILL_NUMBER, eship.WEBSITE_ORDER_NUMBER, eship.WEBSITE_ORDER_LINE_ID, eship.CARRIER, 
											eship.QUANTITY, eship.PROCESS_STATUS, eship.WEBSITE_CUST_ID, eship.BILL_TO_CUST_NUMBER, 
											eship.SHIP_TO_CUST_NUMBER, orders.IN_PROCESS_STATUS
											FROM ECOMF_STG_SHIP_AND_CONTRACTS eship,ecomf_stg_headers_all orders 
											WHERE (eship.PROCESS_STATUS = 'SHIPPED' OR eship.PROCESS_STATUS = 'PARTIAL_SHIPPED')
											AND eship.WEBSITE_ORDER_NUMBER = orders.WEBSITE_ORDER_NUMBER"); //new values or flag set to modified
		$r = oci_execute($resultOracle);
				if (!$r) { 
					$e = oci_error($resultOracle);
					throw new Exception($e['message']);
				}

		while (($row1 = oci_fetch_array($resultOracle, OCI_BOTH)) != false) {
			try{
				$waybillNumber = $row1['WAYBILL_NUMBER'];
				$notShipedOrderId = $row1['WEBSITE_ORDER_NUMBER'];
				$websiteLineID = $row1['WEBSITE_ORDER_LINE_ID'];
				$carrier = $row1['CARRIER'];
				$quantity = $row1['QUANTITY'];
				$status = $row1['PROCESS_STATUS'];
				$customer_id = $row1['WEBSITE_CUST_ID'];
				$bill_number = $row1['BILL_TO_CUST_NUMBER'];
				$ship_number = $row1['SHIP_TO_CUST_NUMBER'];
				$orderStatus = $row1['IN_PROCESS_STATUS'];
				/*
				if($status == 'PARTIAL_SHIPPED'){
					$shipFlag = 1;
				}
				*/
				// Create new shipment
				$orderIncrementId = $notShipedOrderId;
				$itemsQty = array($websiteLineID => $quantity);
				
				$newShipmentId = $proxy->call(
					$sessionId,
					'order_shipment.create',
					array(
						$orderIncrementId,
						$itemsQty
					)
				);

				 //var_dump($newShipmentId);
				// View new shipment

				if (preg_match("/(fed+)/i", $carrier, $match)){
					$carrier = 'fedex';
				}
				if (preg_match("/(ups+)/i", $carrier, $match)){
					$carrier = 'ups';
				}
				if (preg_match("/(usps+)/i", $carrier, $match)){
					$carrier = 'usps';
				}
				if (preg_match("/(dhl+)/i", $carrier, $match)){
					$carrier = 'dhl';
				}

				//$resultShip = $proxy->salesOrderShipmentAddTrack($sessionId, $newShipmentId, $carrier, $carrier, $waybillNumber);
				$resultShip = $proxy->call($sessionId, 'sales_order_shipment.addTrack', array('shipmentIncrementId' => $newShipmentId, 'carrier' => $carrier, 'title' => $carrier, 'trackNumber' => $waybillNumber));

				$sql="UPDATE ECOMF_STG_SHIP_AND_CONTRACTS SET PROCESS_STATUS = 'COMPLETED' WHERE WEBSITE_ORDER_LINE_ID = :websiteLineID";

				$stid = oci_parse($connect, $sql);

				// Bind to increase security. Binding treats binded variable so it not part of the SQL statement. Will not require quoting or escaping.
				
				oci_bind_by_name($stid, ":websiteLineID", $websiteLineID);

				$r = oci_execute($stid);
				if (!$r) { 
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
				
			

				echo 'Shipments added<br/>';

				if($customer_id != NULL){
					$result = $proxy->call($sessionId, 'customer.update', array('customerId' => $customer_id, 'customerData' => array('bill_number' => $bill_number, 'ship_number' => $ship_number)));
				}

				if($orderStatus == 'SHIPPED'){
					$comment = 'The order was successfully shipped';
					$result = $proxy->call($sessionId, 'sales_order.addComment', array('orderIncrementId' => $notShipedOrderId, 'status' => 'complete'));
					//$result = $proxy->salesOrderAddComment($sessionId, $notShipedOrderId, 'complete');
				}
			
			}
	
			catch (Exception $e) {
				$error = 'Caught exception: '.  $e->getMessage();
				$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
												VALUES
												('FCPAECOMMERCE', 'Shipment', :websiteLineID, :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
				$stid = oci_parse($connect, $sql);
				
				oci_bind_by_name($stid, ":websiteLineID", $websiteLineID);
				oci_bind_by_name($stid, ":error", $error);
				
				$r = oci_execute($stid);
				if (!$r) {
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
				$error = oci_parse($connect, "UPDATE ECOMF_STG_SHIP_AND_CONTRACTS SET IN_PROCESS_STATUS = 'ERROR' WHERE WEBSITE_ORDER_LINE_ID = :websiteLineID"); //new values or flag set to modified
				oci_bind_by_name($error, ":websiteLineID", $websiteLineID);
				oci_execute($error);
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
										('FCPAECOMMERCE', 'Shipment', :error, 'AWAITING_ACTION', 'HIGH', sysdate)";
		$stid = oci_parse($connect, $sql);
		
		oci_bind_by_name($stid, ":error", $error1);
		
		$r = oci_execute($stid);
		if (!$r) {
			$e = oci_error($stid);
			trigger_error(htmlentities($e['message']), E_USER_ERROR);
		}
	}
	
	$finish = date('Y-m-d H:i:s');
	echo 'Shipment Optional Status Complete: '.$finish.'<br/>';

	oci_close($connect);
	$proxy->endSession($sessionId);

?>