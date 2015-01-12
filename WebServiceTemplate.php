<?php 
	/**************************************************************
	Developer: Rutherford Le
	Project: Magento eCommerce
	Core language: PHP, JavaScript, HTML, CSS
	System developed: Windows 7
	Purpose: This application sends product update information request
	Comments:
	**************************************************************/
	try{
		/* Create a class for your webservice structure, in this case: Contact */


        // Open log file
        $logfh = fopen("ecom.log", 'a') or die("can't open log file");

        // Initiate cURL session
        $service = "http://133.164.64.128:9000/ManagementRESTService.svc/";
        //$service = "http://156.79.67.241:9000/ManagementRESTService.svc/";

		include '/includes/connect.php';
        $url = $service . $request;
        $ch = curl_init($url);
		
		try{
			// Optional settings for debugging
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_STDERR, $logfh); // logs curl messages

			//Required POST request settings
			curl_setopt($ch, CURLOPT_POST, True);
			//$passwordStr = "SessionCookie:$sessionCookie";
			//curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);

			//POST data
			curl_setopt($ch, CURLOPT_HTTPHEADER,
							  array("Content-type: application/text"));
			$xmlStr = ""; //"<workspace><name>test_php</name></workspace>";
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr);

			//POST return code
			$successCode = 200;

			$buffer = curl_exec($ch); // Execute the curl request

			// Check for errors and process results
			$info = curl_getinfo($ch);
									//echo $resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($info['http_code'] != $successCode) {
			  $msgStr = "# Unsuccessful cURL request to ";
			  $msgStr .= $url." [". $info['http_code']. "]\n";
			  fwrite($logfh, $msgStr);
			} else {
			  $msgStr = "# Successful cURL request to ".$url."\n";
			  fwrite($logfh, $msgStr);
			}
			fwrite($logfh, $buffer."\n");

			$responseXML = simplexml_load_string($buffer);

			//echo $response . '<br><br><br>';

			$sessionCookie = $responseXML->SessionCookie;

			//curl_close($ch); // free resources if curl handle will not be reused
					
		}

		catch (Exception $e) {
			$error = 'Caught exception: '.  $e->getMessage();
			fwrite($logfh, $error);
		}

		//ECOM UPDATE

		try{
			$request1 = "EcomCreateLicense?"; // to add a new workspace

			$url = $service . $request1;
			$ch = curl_init($url);

			// Optional settings for debugging
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //option to return string
			curl_setopt($ch, CURLOPT_VERBOSE, true);
			curl_setopt($ch, CURLOPT_STDERR, $logfh); // logs curl messages

			//Required POST request settings
			curl_setopt($ch, CURLOPT_POST, True);
			//$passwordStr = "SessionCookie:$sessionCookie";
			//curl_setopt($ch, CURLOPT_USERPWD, $passwordStr);

			$i = 0;
			$licArray = array();
			
			$result1 = oci_parse($connect, "SELECT LOG_ID
											FROM ECOMF_INTERFACE_LOGS 
											WHERE LAST_ACTIVATION_DATE IS NOT NULL AND PROCESS_STATUS = 'WAITING_TO_PROCESS'"); //new values or flag set to modified
			$r = oci_execute($result1);
			if (!$r) {
				$e = oci_error($result1);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($result1, OCI_BOTH)) != false) {
				try{
					$logID = $row['LOG_ID'];
				}
				catch (Exception $e) {
					$error = 'Caught exception: '.  $e->getMessage();
					fwrite($logfh, $error);
				}
			}
			
			$result = oci_parse($connect, "SELECT STAGING_ID, CUSTOMER_NAME, VERSION_NUMBER, CUSTOMER_NUMBER, 
											EVAL_DAYS, EVAL_USAGE_COUNT, LINE_NUMBER, CONTRACT_END_DATE, 
											ORDER_RMA_DATE, ORDER_RMA_NUMBER, ORDER_TYPE, PRODUCT_NAME, QUANTITY
											FROM ECOMF_STG_LICENSE_SERVER 
											WHERE PROCESS_STATUS = 'WAITING_TO_PROCESS'"); //new values or flag set to modified
			$r = oci_execute($result);
			if (!$r) {
				$e = oci_error($result);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($result, OCI_BOTH)) != false) {
				try{
					$licArray[$i]['STAGING_ID'] = $row['STAGING_ID'];
					$licArray[$i]['CUSTOMER_NAME'] = $row['CUSTOMER_NAME'];
					$licArray[$i]['VERSION_NUMBER'] = $row['VERSION_NUMBER'];
					$licArray[$i]['CUSTOMER_NUMBER'] = $row['CUSTOMER_NUMBER'];
					$licArray[$i]['EVAL_DAYS'] = $row['EVAL_DAYS']; // SHOULD BE 0 if EVAL, ELSE IT BECOMES A LICENSE
					$licArray[$i]['EVAL_USAGE_COUNT'] = $row['EVAL_USAGE_COUNT'];
					//$licArray[$i]['LINE_NUMBER'] = $row['LINE_NUMBER'];
					$licArray[$i]['CONTRACT_END_DATE'] = $row['CONTRACT_END_DATE'];
					$licArray[$i]['ORDER_RMA_DATE'] = $row['ORDER_RMA_DATE'];
					$licArray[$i]['ORDER_RMA_NUMBER'] = $row['ORDER_RMA_NUMBER'];
					$licArray[$i]['ORDER_TYPE'] = $row['ORDER_TYPE'];
					$licArray[$i]['PRODUCT_NAME'] = $row['PRODUCT_NAME'];
					$licArray[$i]['QUANTITY'] = $row['QUANTITY'];
				}
				catch (Exception $e) {
					$error = 'Caught exception: '.  $e->getMessage();
					fwrite($logfh, $error);
				}
				$i++;
			}
			$x = 0;
			@$xmlStr = '<EcomCreateLicenseRequest xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
						  <Version>'.$licArray[$x]['VERSION_NUMBER'].'</Version>
						  <LineItems>';
			
			while($x < count($licArray)){
			/* EXAMPLE VALUES
			<EcomOrderLineItem>
			  <CustomerName>SuperAcme, Inc</CustomerName>
			  <CustomerNumber>1001</CustomerNumber>
			  <EvalDays>0</EvalDays>
			  <EvalUsageCount>0</EvalUsageCount>
			  <LineNumber>0</LineNumber>
			  <MaintenanceContractExpirationDateString>2015-11-23T14:34:43.0261953-08:00</MaintenanceContractExpirationDateString>
			  <OrderDateString>2014-11-23T14:34:43.0213125-08:00</OrderDateString>
			  <OrderNumber>10001</OrderNumber>
			  <OrderType>STANDARD</OrderType>
			  <ProductName>PSCP-WG-0001</ProductName>
			  <Quantity>3</Quantity>
			</EcomOrderLineItem>
			*/
				try{
					$xmlStr .= '<EcomOrderLineItem>
								  <CustomerName>'.$licArray[$x]['CUSTOMER_NAME'].'</CustomerName>
								  <CustomerNumber>'.$licArray[$x]['CUSTOMER_NUMBER'].'</CustomerNumber>
								  <EvalDays>'.$licArray[$x]['EVAL_DAYS'].'</EvalDays>
								  <EvalUsageCount>'.$licArray[$x]['EVAL_USAGE_COUNT'].'</EvalUsageCount>
								  <LineNumber>'.$x.'</LineNumber>
								  <MaintenanceContractExpirationDateString>'.$licArray[$x]['CONTRACT_END_DATE'].'</MaintenanceContractExpirationDateString>
								  <OrderDateString>'.$licArray[$x]['ORDER_RMA_DATE'].'</OrderDateString>
								  <OrderNumber>'.$licArray[$x]['ORDER_RMA_NUMBER'].'</OrderNumber>
								  <OrderType>'.$licArray[$x]['ORDER_TYPE'].'</OrderType>
								  <ProductName>'.$licArray[$x]['PRODUCT_NAME'].'</ProductName>
								  <Quantity>'.$licArray[$x]['QUANTITY'].'</Quantity>
								</EcomOrderLineItem>';
				}
				catch (Exception $e) {
					$error = 'Caught exception: '.  $e->getMessage();
					fwrite($logfh, $error);
				}
				$x++;
			}

				$xmlStr .= '</LineItems>
						</EcomCreateLicenseRequest>
						';

			$base64 = base64_encode( $xmlStr);

			$encoded = "<Binary>$base64</Binary>";

			curl_setopt($ch, CURLOPT_POSTFIELDS, "$encoded");  //rawurlencode($xmlStr));

			//POST data
		   curl_setopt($ch, CURLOPT_HTTPHEADER, array(
											"Content-Type: text/xml",
											"SessionCookie: $sessionCookie",
											"Content-length: " . strlen($encoded)

			));
			//curl_setopt($ch, CURLOPT_POSTFIELDS, rawurlencode($xmlStr));

			//POST return code
			$successCode = 200;

			$buffer = curl_exec($ch); // Execute the curl request

			// Check for errors and process results
			$info = curl_getinfo($ch);
			if ($info['http_code'] != $successCode) {
			  $msgStr = "# Unsuccessful cURL request to ";
			  $msgStr .= $url." [". $info['http_code']. "]\n";
			  fwrite($logfh, $msgStr);
			} else {
			  $msgStr = "# Successful cURL request to ".$url."\n";
			  fwrite($logfh, $msgStr);
			}
			fwrite($logfh, $buffer."\n");
			$responseXML = simplexml_load_string($buffer);
			
			$statusCode = strval($responseXML->ErrorInfo->Code);
			$statusMessage = strval($responseXML->ErrorInfo->Message);

			foreach($responseXML->LicenseCodes as $response){
				foreach ($response->EcomLicenseCodeItem as $key => $value){
					try{
						$lineNumber = strval($value->LineNumber);
						$licenseCode =  strval($value->LicenseCode);
						$stagingID = $licArray[$lineNumber]['STAGING_ID'];
							//var_dump($value);
						$sql="UPDATE ECOMF_STG_LICENSE_SERVER SET
								LOG_ID = :logID, PROCESS_STATUS = 'PROCESSED', LICENSE_CODE = :licenseCode, 
								ERROR_MESSAGE = :statusMessage, ERROR_CODE = :statusCode,
								CREATION_DATE = sysdate, CREATED_BY = -1, LAST_UPDATE_DATE = sysdate, LAST_UPDATED_BY = -1
								WHERE STAGING_ID = :stagingID";

						$stid = oci_parse($connect, $sql);

						// Bind to increase security. Binding treats binded variable so it not part of the SQL statement. Will not require quoting or escaping.

						oci_bind_by_name($stid, ":stagingID", $stagingID);
						oci_bind_by_name($stid, ":logID", $logID);
						oci_bind_by_name($stid, ":licenseCode", $licenseCode);
						oci_bind_by_name($stid, ":statusCode", $statusCode);
						oci_bind_by_name($stid, ":statusMessage", $statusMessage);

						$r = oci_execute($stid);
						if (!$r) { 
							$e = oci_error($stid);
							throw new Exception($e['message']);
						}
					}
					catch (Exception $e) {
						$error = 'Caught exception: '.  $e->getMessage();
						fwrite($logfh, $error);
					}
				}
			
			}
		}
		catch (Exception $e) {
			$error = 'Caught exception: '.  $e->getMessage();
			fwrite($logfh, $error);
		}
        //var_dump($responseXML);
	}
	catch (Exception $e) {
		$error1 = 'Connection Error: '.  $e->getMessage();
		fwrite($logfh, $error1);
	}
	curl_close($ch); // free resources if curl handle will not be reused
	fclose($logfh);  // close logfile



?>
