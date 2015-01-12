<?php
	/**************************************************************
	Developer: Rutherford Le
	Project: Magento eCommerce
	Core language: PHP, JavaScript, HTML, CSS
	System developed: Windows 7
	Purpose: This application utilizes Magento API to add products
	or modify them. Information received from Oracle.
	Comments:
	**************************************************************/

	try{
				
		//CONNECTION

		include_once 'import_includes/connectAPI.php';

		//ADD CATEGORY ROOT */

		$start = date('Y-m-d H:i:s');
		$source = array();
		$sourceMod = array();
		
		echo 'productTreeCreate Start: '.$start.'<br/>';
		try{
			$result = oci_parse($connect, 'SELECT product_model, PRODUCT_TYPE FROM 
											(SELECT product_model, PRODUCT_TYPE FROM ecomf_product_master
											UNION 
											select product_model, PRODUCT_TYPE FROM ecomf_related_products)
											ORDER BY PRODUCT_MODEL'); //new values or flag set to modified
			$r = oci_execute($result);
			if (!$r) {
				$e = oci_error($result);
				throw new Exception($e['message']);
			}
			
			$catTypeOne = array();
			$y = 0;
			$result1 = oci_parse($connect, 'SELECT TYPE from ecomf_category_tree WHERE ORDER_TYPE = 1'); //new values or flag set to modified
			$r = oci_execute($result1);
			if (!$r) {
				$e = oci_error($result1);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($result1, OCI_BOTH)) != false) {
				$catTypeOne[$y] = $row['TYPE'];
				$y++;
			}
/*
			$resultMod = oci_parse($connect, 'SELECT DISTINCT(SOURCE) 
								FROM ecomf_product_master'); //new values or flag set to modified
								
			$r = oci_execute($resultMod);
			if (!$r) {
				$e = oci_error($resultMod);
				throw new Exception($e['message']);
			}
			$x = 0;
			while (($row = oci_fetch_array($resultMod, OCI_BOTH)) != false) {
			
				$source = explode('/', $row['SOURCE']);
				
					foreach($source as $sources){
						$sourceMod[$x]= $sources;
					}
				
				$x++;
				
			}
*/	
			
			$result1 = oci_parse($connect, "SELECT VALUE 
											FROM ecomf_category_tree WHERE CATEGORY_TYPE = 'UPDATE'"); //new values or flag set to modified
			$r = oci_execute($result1);
			if (!$r) {
				$e = oci_error($result1);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($result1, OCI_BOTH)) != false) {
				$update = $row['VALUE'];
			}
			if($update == 1){
				$result4 = $proxy->catalogCategoryTree($sessionId);
				foreach($result4->children as $row){

					$scanCatID[$row->category_id] = $row->name;
					foreach($row->children as $row1){

						$scanCatID[$row1->category_id] = $row1->name;
						
						foreach($row1->children as $row2){
							$scanCatID[$row2->category_id] = $row2->name;

						}
						
					}

				}
				
				$duplicate = 0;
				
				foreach($scanCatID as $key => $value){
				
				$result1 = oci_parse($connect, 'SELECT CATEGORY_ID, VALUE 
												FROM ecomf_category_tree WHERE CATEGORY_ID = :catID'); //new values or flag set to modified
				oci_bind_by_name($result1, ":catID", $key);
				$r = oci_execute($result1);
				if (!$r) {
					$e = oci_error($result1);
					throw new Exception($e['message']);
				}
				while (($row = oci_fetch_array($result1, OCI_BOTH)) != false) {
					if($value == $row['VALUE']){
						$duplicate = 1;
					}
				}
				
				
				if($duplicate != 1){
				foreach($scanCatID as $key => $value){
					$sql="INSERT INTO ecomf_category_tree (CATEGORY_ID, VALUE) 
										VALUES 
										(:catID, :catName)";

								$stid = oci_parse($connect, $sql);
								
								// Bind to increase security. Binding treats binded variable so it not part of the SQL statement. Will not require quoting or escaping.
								
								oci_bind_by_name($stid, ":catID", $key);
								oci_bind_by_name($stid, ":catName", $value);
								
									
								$r = oci_execute($stid);
								if (!$r) { 
									$e = oci_error($stid);
									throw new Exception($e['message']);
								}
							}
						}
				}	
			}
			else if($update == 0){
				$result1 = oci_parse($connect, 'SELECT CATEGORY_ID, VALUE 
												FROM ecomf_category_tree'); //new values or flag set to modified

				$r = oci_execute($result1);
				if (!$r) {
					$e = oci_error($result1);
					throw new Exception($e['message']);
				}
				while (($row = oci_fetch_array($result1, OCI_BOTH)) != false) {
					$scanCatID[$row['CATEGORY_ID']] = $row['VALUE'];
				}
			}
			
			//PRODUCT TYPE ENTRY, NO ARRAY
			$compare = oci_parse($connect, 'SELECT DISTINCT(PRODUCT_TYPE) FROM ecomf_product_master'); //new values or flag set to modified

			$r = oci_execute($compare);
			if (!$r) {
				$e = oci_error($compare);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($compare, OCI_BOTH)) != false) {
				$duplicate = 0;
				$prodType = $row['PRODUCT_TYPE'];
				$compare1 = oci_parse($connect, 'SELECT TYPE FROM ecomf_category_tree WHERE TYPE IS NOT NULL'); //new values or flag set to modified

				$r = oci_execute($compare1);
				if (!$r) {
					$e = oci_error($compare1);
					throw new Exception($e['message']);
				}
				while (($row1 = oci_fetch_array($compare1, OCI_BOTH)) != false) {
					if($prodType == $row1['TYPE'])
						$duplicate = 1;
				}
				if($duplicate != 1){
					$compare2 = oci_parse($connect, "INSERT INTO ecomf_category_tree (SOURCE, TYPE, CATEGORY_TYPE, ORDER_TYPE, VALUE, CATEGORY_ID, VALUE2)
													VALUES
													('SPARES CONSUMABLES', :prodType, 'SPARE', 2, 'By Model', 3, 'By Family')"); //new values or flag set to modified

					oci_bind_by_name($compare2, ":prodType", $prodType);
					$r = oci_execute($compare2);
					if (!$r) {
						$e = oci_error($compare2);
						throw new Exception($e['message']);
					}
				}
			}

			//PRODUCT TYPE END
			
			$resultMod = oci_parse($connect, 'SELECT TYPE,VALUE, VALUE2
								FROM ecomf_category_tree'); //new values or flag set to modified
								
			$r = oci_execute($resultMod);
			if (!$r) {
				$e = oci_error($resultMod);
				throw new Exception($e['message']);
			}

			while (($row = oci_fetch_array($resultMod, OCI_BOTH)) != false) {

				$type = @$row['TYPE'];
				$value = $row['VALUE'];
				$value2 = @$row['VALUE2'];
				
				$sourceMod[$type] = $value;
				$sourceFam[$type] = $value2;
				
			}
			
			//UPDATE/ADDING MODELS
			while (($row = oci_fetch_array($result, OCI_BOTH)) != false) {
				$product_model = @$row['PRODUCT_MODEL']; // French Cuff
				$sourceType = $row['PRODUCT_TYPE'];
				if(!(in_array($sourceType,$catTypeOne))){
					$sourceCat = $sourceMod[$sourceType];	//By Model
					
					//preg_match('/[\/](.+)/i', @$row['SOURCE'], $match);
					//$key = $match[1];
					
					$categoryDuplicate = 0;
					$newkey = array_search($sourceCat, $scanCatID);

					$result3 = $proxy->catalogCategoryTree($sessionId, $newkey);
					foreach($result3->children as $row){

						$catName = $row->name;
						
						if($catName == $product_model){
							$categoryDuplicate = 1;
						}
						
					}

					// Check for duplicates in order to add categories
					if ($product_model == '')
						continue;
					if($categoryDuplicate != 1){
						$result1 = $proxy->catalogCategoryCreate($sessionId, $newkey, array(
						'name' => $product_model,
						'is_active' => 1,
						'position' => 1,
						//<!-- position parameter is deprecated, category anyway will be positioned in the end of list
						//and you can not set position directly, use catalog_category.move instead -->
						'available_sort_by' => array('position'),
						'custom_design' => null,
						'custom_apply_to_products' => null,
						'custom_design_from' => null,
						'custom_design_to' => null,
						'custom_layout_update' => null,
						'default_sort_by' => 'position',
						//'description' => 'Category description',
						'display_mode' => null,
						'is_anchor' => 1,
						'landing_page' => null,
						//'meta_description' => 'Category meta description',
						//'meta_keywords' => 'Category meta keywords',
						//'meta_title' => 'Category meta title',
						'page_layout' => '',
						//'url_key' => 'url-key',
						'include_in_menu' => 1,
						));
					}
				}
			}

			$result2 = oci_parse($connect, 'SELECT PRODUCT_FAMILY, PRODUCT_TYPE FROM 
                                            (SELECT PRODUCT_FAMILY, PRODUCT_TYPE FROM ecomf_product_master
                                            UNION 
                                            select PRODUCT_FAMILY, PRODUCT_TYPE FROM ecomf_related_products)
                                            ORDER BY PRODUCT_FAMILY'); //new values or flag set to modified
					
			$r = oci_execute($result2);
			if (!$r) {
				$e = oci_error($result2);
				throw new Exception($e['message']);
			}
			while (($row = oci_fetch_array($result2, OCI_BOTH)) != false) {
				$product_family = @$row['PRODUCT_FAMILY']; // French Cuff
				$sourceType = $row['PRODUCT_TYPE'];
				if(!(in_array($sourceType,$catTypeOne))){
					$sourceFamily = $sourceFam[$sourceType];	//By Family

					//preg_match('/[\/](.+)/i', @$row['SOURCE'], $match);
					//$key = $match[1];
					
					$categoryDuplicate = 0;
					$newkey = array_search($sourceFamily, $scanCatID);

					$result3 = $proxy->catalogCategoryTree($sessionId, $newkey);
					foreach($result3->children as $row){

						$catName = $row->name;
						
						if($catName == $product_family){
							$categoryDuplicate = 1;
						}
						
					}

					// Check for duplicates in order to add categories
					if ($product_family == '')
						continue;
					if($categoryDuplicate != 1){
						$result1 = $proxy->catalogCategoryCreate($sessionId, $newkey, array(
						'name' => $product_family,
						'is_active' => 1,
						'position' => 1,
						//<!-- position parameter is deprecated, category anyway will be positioned in the end of list
						//and you can not set position directly, use catalog_category.move instead -->
						'available_sort_by' => array('position'),
						'custom_design' => null,
						'custom_apply_to_products' => null,
						'custom_design_from' => null,
						'custom_design_to' => null,
						'custom_layout_update' => null,
						'default_sort_by' => 'position',
						//'description' => 'Category description',
						'display_mode' => null,
						'is_anchor' => 1,
						'landing_page' => null,
						//'meta_description' => 'Category meta description',
						//'meta_keywords' => 'Category meta keywords',
						//'meta_title' => 'Category meta title',
						'page_layout' => '',
						//'url_key' => 'url-key',
						'include_in_menu' => 1,
						));
					}
				}
			}
			
		}
		catch (Exception $e) {
			$error = 'Caught exception: '.  $e->getMessage();
			$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, ERROR_SUBCATEGORY, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
											VALUES
											('FCPAECOMMERCE', 'Parts', :model, 'Adding/Modifying Models - Model', :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
			$stid = oci_parse($connect, $sql);
			
			oci_bind_by_name($stid, ":model", $product_model);
			oci_bind_by_name($stid, ":error", $error);
			
			$r = oci_execute($stid);
			if (!$r) {
				$e = oci_error($stid);
				trigger_error(htmlentities($e['message']), E_USER_ERROR);
			}
		}
		
		
			echo 'Categories Added<br/>';

		try{
		/*RECEIVE "SELECT SCANNERS" CATEGORY ID

		$result5 = $proxy->catalogCategoryTree($sessionId);
		foreach($result5->children as $row){

			$catID = $row->category_id;
			if ($row->name == 'Spares & Consumables'){
				$result6 = $proxy->catalogCategoryTree($sessionId, $catID);
				foreach($result6->children as $row){

						if($row->name == 'Select Scanners')
							 $scanCatID = $row->category_id;
						
				}
			}
			
		}
		*/

		//PRODUCT CREATION/MODIFICATION 
			$result4 = oci_parse($connect, "SELECT PART_NUMBER, INVENTORY_ITEM_ID, PART_DESCRIPTION, SHORT_DESCRIPTION, PRODUCT_WEIGHT, 
											PRODUCT_STATUS, PRICE, PRODUCT_TYPE, QUANTITY, UPDATE_FLAG
											FROM ecomf_product_master 
											WHERE PROCESS_STATUS = 'WAITING_UPLOAD'"); //new values or flag set to modified
			$r = oci_execute($result4);

			if (!$r) { 
				$e = oci_error($result4);
				throw new Exception($e['message']);
				echo error;
			}

			$i=0;
			$attributeSets = $proxy->catalogProductAttributeSetList($sessionId);

			$attributeSet = current($attributeSets);
			$result = $proxy->catalogProductAttributeList($sessionId, $attributeSet->set_id);
			foreach ($result as $row5){
				if($row5->code == 'producttype')
						$attID = $row5->attribute_id;
					}
			$mageArray = array();
			while (($row = oci_fetch_array($result4, OCI_BOTH)) != false) {
				 $mageArray[$i]['PART_NUMBER'] = $row['PART_NUMBER']; // French Cuff
				 $partID = $row['PART_NUMBER'];
				// $mageArray[$i]['PRODUCT_MODEL'] = $row['PRODUCT_MODEL']; // French Cuff
				 $mageArray[$i]['INVENTORY_ITEM_ID'] = $row['INVENTORY_ITEM_ID'];
				 $mageArray[$i]['PART_DESCRIPTION'] = $row['PART_DESCRIPTION'];
				 $mageArray[$i]['SHORT_DESCRIPTION'] = $row['SHORT_DESCRIPTION'];
				// $shortDescription = $row['SHORT_DESCRIPTION'];
				 $mageArray[$i]['PRODUCT_WEIGHT'] = @$row['PRODUCT_WEIGHT']; //1.0000
				 $mageArray[$i]['PRODUCT_STATUS'] = $row['PRODUCT_STATUS']; // 1
				// $urlKey = $row['URL_KEY']; // french-cuff-cotton-twill-oxford
				// $urlPath = $row['URL_PATH']; // french-cuff-cotton-twill-oxford.html
				// $visibility = $row['VISIBILITY']; // 1
				 $mageArray[$i]['PRICE'] = $row['PRICE']; // 400.0000
				 $mageArray[$i]['PRODUCT_TYPE'] = ucfirst(strtolower($row['PRODUCT_TYPE'])); // SPARE to Spare
				// $taxClassID = $row['TAX_CLASS_ID']; // 2
				// $metaTitle = @$row['META_TITLE'];  
				// $metaKey = @$row['META_KEYWORD']; 
				// $metaDescription = @$row['META_DESCRIPTION']; 

				 $mageArray[$i]['QUANTITY'] = @$row['QUANTITY']; // 400.0000
				 $mageArray[$i]['UPDATE_FLAG'] = @$row['UPDATE_FLAG']; // MODIFIED OR NULL
				
				$i++;
			}
		}
		
		catch (Exception $e) {
			$error = 'Caught exception: '.  $e->getMessage();
			$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, ERROR_SUBCATEGORY, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
											VALUES
											('FCPAECOMMERCE', 'Parts', :partID, 'Oracle to Array - part ID', :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
			$stid = oci_parse($connect, $sql);
			
			oci_bind_by_name($stid, ":partID", $partID);
			oci_bind_by_name($stid, ":error", $error);
			
			$r = oci_execute($stid);
			if (!$r) {
				$e = oci_error($stid);
				trigger_error(htmlentities($e['message']), E_USER_ERROR);
			}
			$error = oci_parse($connect, "UPDATE ecomf_product_master SET PROCESS_STATUS = 'ERROR' WHERE PART_NUMBER = :partID"); //new values or flag set to modified
			oci_bind_by_name($stid, ":partID", $partID);
			oci_execute($error);
			if (!$r) {
				$e = oci_error($stid);
				trigger_error(htmlentities($e['message']), E_USER_ERROR);
			}
		}
		
		try{
			$catTree = array();
			
			foreach($scanCatID as $key => $scan){
				$scanID = array_search($scan, $scanCatID);
				$result6 = $proxy->catalogCategoryTree($sessionId, $scanID);
				foreach($result6->children as $row2){
					$catTree[$scanID][$row2->name] = $row2->category_id;
				}
				
			}
			
			$y=0;
			for($y=0;$y<=@$i;$y++){
				$pSKU = @$mageArray[$y]['INVENTORY_ITEM_ID'];
				$result5 = oci_parse($connect, 'SELECT PRODUCT_TYPE, INVENTORY_ITEM_ID, PRODUCT_MODEL, PRODUCT_FAMILY FROM (
												SELECT distinct INVENTORY_ITEM_ID || PRODUCT_MODEL AS key, PRODUCT_TYPE, INVENTORY_ITEM_ID, PRODUCT_MODEL, PRODUCT_FAMILY FROM ecomf_product_master)
												WHERE INVENTORY_ITEM_ID = :sku
												UNION
												SELECT PRODUCT_TYPE, INVENTORY_ITEM_ID, PRODUCT_MODEL, PRODUCT_FAMILY FROM (
												SELECT distinct INVENTORY_ITEM_ID || PRODUCT_MODEL AS key, PRODUCT_TYPE, INVENTORY_ITEM_ID, PRODUCT_MODEL, PRODUCT_FAMILY FROM ecomf_related_products)
												WHERE INVENTORY_ITEM_ID = :sku'); //new values or flag set to modified
												
				oci_bind_by_name($result5, ":sku", $pSKU);

				oci_execute($result5);

				if($result5 === FALSE) {
					die(mysql_error()); // better error handling
				}

				while (($row = oci_fetch_array($result5, OCI_BOTH)) != false) {
	
					$product_model = @$row['PRODUCT_MODEL'];
					$product_type = @$row['PRODUCT_TYPE'];
					if(!(in_array($product_type,$catTypeOne))){
						$product_family = @$row['PRODUCT_FAMILY'];

						$product_cat = $sourceMod[$product_type];	//By Model
						$product_fam = $sourceFam[$product_type];	//By Family
						
						//preg_match('/[\/](.+)/i', @$row['SOURCE'], $match);
						//$key = $match[1];
						
						$categoryDuplicate = 0;
						$newkey = array_search($product_cat, $scanCatID);
						$famkey = array_search($product_family, $scanCatID);
						$famkey1 = array_search($product_fam, $scanCatID);
						
						//preg_match('/[\/](.+)/i', @$row['SOURCE'], $match);
						//$scanSource = array_search($match[1], $scanCatID);
						//$catID = array_keys($catTree, $product_model);
						
						$value = $catTree[$newkey][$product_model];
						
						$mageArray[$y]['catID'][$value] = $value;
						$mageArray[$y]['catID'][$newkey] = $newkey;
						$mageArray[$y]['catID'][$famkey] = $famkey;
						$mageArray[$y]['catID'][$famkey1] = $famkey1;
					}
					else{

						$product_cat = $sourceMod[$product_type]; //REFURBISHED SCANNERS
						$newkey = array_search($product_cat, $scanCatID);
						$mageArray[$y]['catID'][$newkey] = $newkey;
									
					}
					
				}

			}

		}

		
		
		catch (Exception $e) {
			$error = 'Caught exception: '.  $e->getMessage();
			$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, KEY_VALUE2, ERROR_SUBCATEGORY, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
											VALUES
											('FCPAECOMMERCE', 'Parts', :pSKU, :product_model, 'Related Models to Array - sku, model', :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
			$stid = oci_parse($connect, $sql);
			
			oci_bind_by_name($stid, ":pSKU", $pSKU);
			oci_bind_by_name($stid, ":product_model", $product_model);
			oci_bind_by_name($stid, ":error", $error);
			
			$r = oci_execute($stid);
			if (!$r) {
				$e = oci_error($stid);
				trigger_error(htmlentities($e['message']), E_USER_ERROR);
			}
			$error = oci_parse($connect, "UPDATE ecomf_product_master SET PROCESS_STATUS = 'ERROR' WHERE PART_NUMBER = :partID"); //new values or flag set to modified
			oci_bind_by_name($stid, ":partID", $partID);
			oci_execute($error);
			if (!$r) {
				$e = oci_error($stid);
				trigger_error(htmlentities($e['message']), E_USER_ERROR);
			}
		}

		echo 'Oracle Information received<br/>';

		$result3 = $proxy->catalogProductList($sessionId);

		//var_dump($result1);
		//echo '<pre>' . var_export($result1, true) . '</pre>';

		$x=0;
		//$sku = 'Hello product'; //Sample product
		
		while($x < count($mageArray)){
		
		try{

			$newProd = 0;
				$mageItemID = $mageArray[$x]['INVENTORY_ITEM_ID'];

			foreach ($result3 as $row){
				if( $mageItemID == $row->sku){ //Checks DB to see if SKU is matches Magento SKU, this will signify that product already exist
					 $newProd = 1;
				}
			}

				if ($mageArray[$x]['PRODUCT_WEIGHT'] == ''){
					$mageArray[$x]['PRODUCT_WEIGHT'] = 1;
				}
				// get attribute set
				if($newProd != 1 || $mageArray[$x]['UPDATE_FLAG']  == 1){

				//if($newProd != 1){
				$quantity = $mageArray[$x]['QUANTITY']	;	
				if($quantity > 0){
					$is_in_stock = 1;
				}
				else{
					$is_in_stock = 0;
				}
				
				$result2 = $proxy->catalogProductAttributeOptions($sessionId, $attID);

				foreach ($result2 as $row4){
					if($row4->label == $mageArray[$x]['PRODUCT_TYPE'])
						$attributeOptID = $row4->value;
					else
						$attributeOptID = $mageArray[$x]['PRODUCT_TYPE'];
				}
					$attributeSets = $proxy->catalogProductAttributeSetList($sessionId);
					$attributeSet = current($attributeSets);
					$catalogProductCreateEntity = new stdClass(); // New object

					$catalogProductCreateEntity->categories = @$mageArray[$x]['catID'];
					$catalogProductCreateEntity->websites = array(1);
					$catalogProductCreateEntity->name = $mageArray[$x]['SHORT_DESCRIPTION'].', '.$mageArray[$x]['PART_NUMBER'];
					$catalogProductCreateEntity->description = $mageArray[$x]['PART_DESCRIPTION'];
					$catalogProductCreateEntity->short_description = $mageArray[$x]['SHORT_DESCRIPTION'];
					$catalogProductCreateEntity->weight = $mageArray[$x]['PRODUCT_WEIGHT'];
					$catalogProductCreateEntity->status = $mageArray[$x]['PRODUCT_STATUS'];
					//$catalogProductCreateEntity->url_key = $urlKey;
					//$catalogProductCreateEntity->url_path = $urlPath;
					$catalogProductCreateEntity->visibility = 4;
					$catalogProductCreateEntity->price = $mageArray[$x]['PRICE'];
					$catalogProductCreateEntity->tax_class_id = 2;
					//$catalogProductCreateEntity->meta_title = $metaTitle;
					//$catalogProductCreateEntity->meta_keyword = $metaKey;
					//$catalogProductCreateEntity->meta_description = $metaDescription;

					$stockData = array( // New array
					'qty'   => $quantity,
					'is_in_stock' => $is_in_stock
					); 
					
					$catalogProductCreateEntity->additional_attributes = array
					(
					'single_data' => array(
					array('key' => 'producttype', 'value' => $attributeOptID)
					)
					);

					$catalogProductCreateEntity->stock_data = $stockData ; // Array within created object
					
					$sql="UPDATE ecomf_product_master SET PROCESS_STATUS = 'COMPLETED', UPDATE_FLAG = 1 
						WHERE INVENTORY_ITEM_ID = :sku";

					$stid = oci_parse($connect, $sql);

					// Bind to increase security. Binding treats binded variable so it not part of the SQL statement. Will not require quoting or escaping.

					oci_bind_by_name($stid, ":sku", $mageArray[$x]['INVENTORY_ITEM_ID']);

					$r = oci_execute($stid);
					if (!$r) { 
						$e = oci_error($stid);
						throw new Exception($e['message']);
						}

					if($newProd != 1){
						$result1 = $proxy->catalogProductCreate($sessionId, 'simple', $attributeSet->set_id, $mageArray[$x]['INVENTORY_ITEM_ID'], $catalogProductCreateEntity); // Attach to object Result1
						//echo '<br/ >'.$mageArray[$x]['INVENTORY_ITEM_ID'].' Product Added';
						}
					if ($mageArray[$x]['UPDATE_FLAG'] == 1){

						$result1 = $proxy->catalogProductUpdate($sessionId, $mageArray[$x]['INVENTORY_ITEM_ID'], $catalogProductCreateEntity, '', 'sku');
						$sql="UPDATE ecomf_product_master SET PROCESS_STATUS = 'COMPLETED' 
							WHERE INVENTORY_ITEM_ID = :sku";
							
						//echo '<br/ >'.$mageArray[$x]['INVENTORY_ITEM_ID'].' Product Modified';
						$stid = oci_parse($connect, $sql);

						oci_bind_by_name($stid, ":sku", $mageArray[$x]['INVENTORY_ITEM_ID']);
								
						$r = oci_execute($stid);
						if (!$r) {
							$e = oci_error($stid);
							throw new Exception($e['message']);
						}
						$r = oci_commit($connect);
						if (!$r) {
							$e = oci_error($connect);
							throw new Exception($e['message']);
						}
					}
							


				}
				$x++;
			}
		

			catch (Exception $e) {
				$inventID = strval($mageArray[$x]['INVENTORY_ITEM_ID']);
				$error = 'Caught exception: '.  $e->getMessage();
				$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, KEY_VALUE1, ERROR_SUBCATEGORY, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
												VALUES
												('FCPAECOMMERCE', 'Parts', :inventID, 'Array to Magento - invent ID', :error, 'AWAITING_ACTION', 'MEDIUM', sysdate)";
				$stid = oci_parse($connect, $sql);
				
				oci_bind_by_name($stid, ":inventID", $inventID);
				oci_bind_by_name($stid, ":error", $error);
				
				$r = oci_execute($stid);
				if (!$r) {
					$e = oci_error($stid);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
				$error = oci_parse($connect, "UPDATE ecomf_product_master SET PROCESS_STATUS = 'ERROR' WHERE INVENTORY_ITEM_ID = :sku"); //new values or flag set to modified
				oci_bind_by_name($stid, ":sku", $inventID);
				oci_execute($error);
				if (!$r) {
					$e = oci_error($error);
					trigger_error(htmlentities($e['message']), E_USER_ERROR);
				}
			}
		}
	}
	catch (Exception $e) {
		$error1 = 'Connection Error: '.  $e->getMessage();
		$sql="INSERT INTO ECOMF_INTERFACE_ERRORS (SOURCE, INTERFACE, ERROR_MESSAGE, PROCESS_STATUS, ERROR_SEVERITY, CREATION_DATE)
										VALUES
										('FCPAECOMMERCE', 'Parts', :error, 'AWAITING_ACTION', 'HIGH', sysdate)";
		$stid = oci_parse($connect, $sql);
		
		oci_bind_by_name($stid, ":error", $error1);
		
		$r = oci_execute($stid);
		if (!$r) {
			$e = oci_error($stid);
			trigger_error(htmlentities($e['message']), E_USER_ERROR);
		}
	}
	$finish = date('Y-m-d H:i:s');
	echo 'productTreeCreate Complete: '.$finish.'<br/>';

	oci_close($connect);
	$proxy->endSession($sessionId);
?>