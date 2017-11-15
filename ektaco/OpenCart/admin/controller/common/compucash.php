<?php
	class ControllerCommonCompuCash extends Controller {

	    public function productImport() {

	        if (!defined("OPENCART_CLI_MODE") || OPENCART_CLI_MODE === FALSE) {

	            echo "Please run method from command line."; exit;
	        }

		$this->importCCProducts();
	    }

	    public function categoryImport() {

	        if (!defined("OPENCART_CLI_MODE") || OPENCART_CLI_MODE === FALSE) {

	            echo "Please run method from command line."; exit;
	        }

		$this->importCCCategories();

	    }

		protected function getIdentityToken()
		{
		        $clientId = CLIENT_ID;
			$secret = SECRET;
		        $identityServerUrl = IDENTITYSERVER_URL;

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $identityServerUrl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('grant_type'=>'client_credentials', 'client_id'=>$clientId, 'scope'=>'cc5api', 'client_secret'=>$secret));

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec ($ch);

			curl_close ($ch);

			$obj = json_decode($output);

			return $obj->access_token;
		}

		protected function getProducts($idtoken)
		{

			$authorization = "Authorization: Bearer " . $idtoken;
			$jsonHeader = "Content-Type: application/json";

			$headers = array($jsonHeader);

			array_push($headers, $authorization);

			$cc5ApiUrl = CC5API_URL;

			$url = $cc5ApiUrl . "Products";

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_FOLLOWLOCATION => true
			));

			$result = curl_exec($ch);
			curl_close($ch);

			return json_decode($result);
		}

		protected function getProductGroups($idtoken)
		{
			$authorization = "Authorization: Bearer " . $idtoken;
			$jsonHeader = "Content-Type: application/json";

			$headers = array($jsonHeader);

			array_push($headers, $authorization);

			$cc5ApiUrl = CC5API_URL;

			$url = $cc5ApiUrl . "ProductGroups";

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_FOLLOWLOCATION => true
			));

			$result = curl_exec($ch);
			curl_close($ch);

			return json_decode($result);
		}

	    protected function updateProductExternalCode($idtoken, $productMapping)
	    {

		$authorization = "Authorization: Bearer " . $idtoken;

		$jsonHeader = "Content-Type: application/json";
		$headers = array($jsonHeader);
		array_push($headers, $authorization);

		$cc5ApiUrl = CC5API_URL;
		//API Url
		$url = $cc5ApiUrl . 'Products/ExternalIdUpdList';

		//Initiate cURL.
		$ch = curl_init($url);

		//Encode the array into JSON.
		$jsonDataEncoded = json_encode($productMapping);

		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

		//Set the content type to application/json
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		//Execute the request
		$result = curl_exec($ch);

	    }

	    protected function updateProductGroupExternalCode($idtoken, $productGroupMapping)
	    {

		$authorization = "Authorization: Bearer " . $idtoken;

		$jsonHeader = "Content-Type: application/json";
		$headers = array($jsonHeader);
		array_push($headers, $authorization);

		$cc5ApiUrl = CC5API_URL;
		//API Url
		$url = $cc5ApiUrl . 'ProductGroups/ExternalPgIdUpdList';

		//Initiate cURL.
		$ch = curl_init($url);

		//Encode the array into JSON.
		$jsonDataEncoded = json_encode($productGroupMapping);

		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

		//Set the content type to application/json
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		//Execute the request
		$result = curl_exec($ch);

	    }

        public function importCCCategories(){

		$idtoken = $this->getIdentityToken();

	        $productGroups = $this->getProductGroups($idtoken);
                //echo $productGroups;

	   	$this->load->model('catalog/category');

                if (count($productGroups->data) > 0)
		{
			$producGrouptMappings = array();

			foreach ($productGroups->data as $productGroup)
			{
				$productGid = (!empty($productGroup->productGid)) ? $productGroup->productGid : '';
				$masterGid = (!empty($productGroup->masterGid)) ? $productGroup->masterGid : '';
				$externalGid = (!empty($productGroup->externalGid)) ? $productGroup->externalGid : '';
				$productGroupName = (!empty($productGroup->productGroupName)) ? trim($productGroup->productGroupName) : '';

				$categoryData = array(
							'parent_id'=>0,
							'column'=>1,
							'sort_order' => 0,
					    		'status' => 1,
							'keyword' => $productGroupName,
							'category_description' => array(
									 array(
									'name' => $productGroupName,
									'description' => $productGroupName,
							    		'meta_title' => $productGroupName,
									'meta_description' => $productGroupName,
							    		'meta_keyword' => ''
									),
									array(
									'name' => $productGroupName,
									'description' => $productGroupName,
							    		'meta_title' => $productGroupName,
									'meta_description' => $productGroupName,
							    		'meta_keyword' => ''
									),

								)
							);




					if(empty($externalGid))
					{
						$newExternalCategoryId = $this->model_catalog_category->addCategory($categoryData);

						$productGroupMap = new ProductGroupMapping();
						$productGroupMap->productGId = $productGid;
						$productGroupMap->externalProductGId = $newExternalCategoryId;
		                                array_push($producGrouptMappings, $productGroupMap);
					}
					else
					{
						$result = $this->model_catalog_category->getCategory($externalGid);
                                                if ($result)
						{
							$this->model_catalog_category->editCategory($externalGid, $categoryData);
						}
						else
						{
							$newExternalCategoryId = $this->model_catalog_category->addCategory($categoryData);

							$productGroupMap = new ProductGroupMapping();
							$productGroupMap->productGId = $productGid;
							$productGroupMap->externalProductGId = $newExternalCategoryId;
				                        array_push($producGrouptMappings, $productGroupMap);
						}

					}


			}
			$this->updateProductGroupExternalCode($idtoken, $producGrouptMappings);
		}

        }

	public function importCCProducts(){

           $idtoken = $this->getIdentityToken();
	   $products = $this->getProducts($idtoken);

           $this->load->model('catalog/product');


		if (count($products) > 0)
		{
			foreach ($products as $product) {
				$productId = (!empty($product->productId)) ? $product->productId : '';
				$externalProductId = (!empty($product->externalProductId)) ? $product->externalProductId : '';
				$sku = (!empty($product->sku)) ? $product->sku : '';
				$productNumber = (!empty($product->productNumber)) ? $product->productNumber : '';
				$productName = (!empty($product->productName)) ? $product->productName : '';
				$shortDescription = (!empty($product->shortDescription)) ? $product->shortDescription : '';
				$longDescription = (!empty($product->longDescription)) ? $product->longDescription : '';
				$salePrice = (!empty($product->salePrice)) ? $product->salePrice : 0;
				$quantity = (!empty($product->quantity)) ? $product->quantity : 0;
				$isInStock = (!empty($product->isInStock)) ? $product->isInStock : 0;
				$weight = (!empty($product->weight)) ? $product->weight : 0;

                                if ($isInStock == 0)
                                {
                                $stockStatusCode = 'Out Of Stock';
                                $stock_status_id = 5;
                                }
                                else
				{
				$stockStatusCode = 'In Stock';
				$stock_status_id = 7;
				}

				$productData = array(

								'name' => $productName,
								'keyword' => $productName,
								'quantity' => $quantity,
								'price' => $salePrice,
								'model' => $productNumber,
								'sku' => $sku,
								'date_available' => date("Y-m-d"),
                                				'upc' => '',
 		               					'ean' => '',
								'jan' => '',
								'isbn' => '',
								'mpn' => '',
								'minimum' => 0,
								'subtract' => 0,
								'manufacturer_id' => 0,
								'points' => 0,
								'weight' => 0,
								'weight_class_id' => 0,
								'length' => 0,
								'width' => 0,
								'height' => 0,
								'length_class_id' => 0,
								'status' => 1,
								'tax_class_id' => 9,
								'location' => '',
'sort_order' => 0,

								'stock_status' => $stockStatusCode,
								'stock_status_id' => $stock_status_id,
								'language_id' => 1,
										'shipping' => 1,
								'product_description' => array(
										array(

											'name' => $productName,
											'description' => $longDescription,
											'meta_description' => $longDescription,
											'meta_title' => $sku,
											'meta_keyword' => $sku,
											'tag' => $sku,

										),
										array(

											'name' => $productName,
											'description' => $longDescription,
											'meta_description' => $longDescription,
											'meta_title' => $sku,
											'meta_keyword' => $sku,
											'tag' => $sku,

										),
									)
				);

                try {
                                $productMappings = array();
				if(!empty($externalProductId))
				{
					$result = $this->model_catalog_product->getProduct((int) $externalProductId);
					if ($result)
					{
						$this->model_catalog_product->editProduct($externalProductId, $productData);
					}
					else
					{
						$newExternalProductId = $this->model_catalog_product->addProduct($productData);

						$productMap = new ProductMapping();
						$productMap->productId = $productId;
						$productMap->externalProductId = $newExternalProductId;
                                                array_push($productMappings, $productMap);

					}
				}
				else
				{
						$newExternalProductId = $this->model_catalog_product->addProduct($productData);

						$productMap = new ProductMapping();
						$productMap->productId = $productId;
						$productMap->externalProductId = $newExternalProductId;
                                                array_push($productMappings, $productMap);
				}

						$this->updateProductExternalCode($idtoken, $productMappings);
				} catch (Exception $e) {
					$this->log->write($e->getMessage());
				}


			}
		}


	}

	}

	class productMapping
	{
		public $productId;
		public $externalProductId;
	}

	class productGroupMapping
	{
		public $productGId;
		public $externalProductGId;
	}


?>
