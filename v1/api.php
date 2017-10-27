<?php
    
	include '../helpers/Database.php';
	include '../helpers/JWT.php';
	include '../helpers/Rest.inc.php';

	class API extends REST {

		private $tokenSecret = 'JWTExampleByTest';

		// it will process the API
		public function processApi()
		{
			$function = explode('/', $_REQUEST['rquest']);

			//$func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
			if((int)method_exists($this,$function[2]) > 0){
				$func = $function[2];
				$this->$func();
			}else{
				$this->response($func,404); 
			}
		}

		// function to validate the token.
		function validateToken($token) {
			try{
				$tokenId = JWT::decode($token , $this->tokenSecret);
			}catch(Exception $e){
				return false;
			}
			
			return $tokenId->id;
		}

		// method to create the token for authenticated users for authentication.
		function createTokenForUser(){
			try{
				$tokenId['id'] = rand(0 , 10000);
				$token = JWT::encode($tokenId, $this->tokenSecret);
			}catch(Exception $e){
				return false;
			}
			return $token;
		}


		// this function will check the key values from request.
		function getIfSet(&$value, $default = null)
		{
		    return isset($value) ? $value : $default;
		}

		// function for logging the user
		private function login()
		{
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST")
			{
				$error = array('status' => "failed", "msg" => "Not allowed" , "data"=>"");
				$this->response(json_encode($error),406);
			}

			// check all the fields
			$email 		= $this->getIfSet($this->_request['email']);
			$password 	= $this->getIfSet($this->_request['pwd']);

			// Input validations
			if(!empty($email) and !empty($password))
			{
				if(filter_var($email, FILTER_VALIDATE_EMAIL)){
					
					$checkData = ['email'=>$email , 'password' => $password];

					// create the object of database helper and call the appropriate function.
					$db = new Database();
					$userInfo = $db->check_exist('users' , $checkData);

					if(!empty($userInfo)){
						// create token
						$token = $this->createTokenForUser();

						if($token){
							// success 
							$success = array('status' => "success", "msg" => "You are authenticated." , "data"=>['token' => $token]);
							$this->response(json_encode($success), 200);
						}else{
							$error = array('status' => "failed", "msg" => "some thing is wrong while creating tokens" , "data"=>"");
							$this->response(json_encode($error), 401);
						}
					}else{
						$error = array('status' => "failed", "msg" => "Wrong credentials !!" , "data"=>"");
						$this->response(json_encode($error), 401);
					}
				}
			}

			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "failed", "msg" => "All the fields are required" , "data"=>"");
			$this->response(json_encode($error), 400);
		}

		public function add(){
			// name , description , price , category_id
			if($this->get_request_method() != "POST")
			{
				$error = array('status' => "failed", "msg" => "Not allowed" , "data"=>"");
				$this->response(json_encode($error),406);
			}

			// first of all check the token after that proceed
			if(!empty($this->getIfSet(getallheaders()['Authorization']))){

				if ($this->validateToken(getallheaders()['Authorization'])) {
					// user is validated, now we can proceed
					// check all the fields and validations
					if( !empty($this->getIfSet($this->_request['name'])) && 
						!empty($this->getIfSet($this->_request['description'])) && 
						!empty($this->getIfSet($this->_request['price'])) && 
						!empty($this->getIfSet($this->_request['category_id']))
					 ){

					 	// check category id exist or not
						$db = new Database();
						$catInfo = $db->check_exist('categories' , ['id' => $this->_request['category_id']]);
						if(empty($catInfo)){
							$error = array('status' => "failed", "msg" => "Cat id is not found" , "data"=>"");
							$this->response(json_encode($error), 400);
						}

						$insertArr = [ 
										'name' 			=> $this->_request['name'] , 
										'description' 	=> $this->_request['description'] , 
										'price' 		=> $this->_request['price'] , 
										'category_id' 	=> $this->_request['category_id'] 
									];

						$db = new Database();
						$productId = $db->insert('products' , $insertArr);
						if($productId){
							$success = array('status' => "success", "msg" => "Product is inserted." , "data"=>"");
							$this->response(json_encode($success), 200);
						}

						$error = array('status' => "failed", "msg" => "Product is not inserted." , "data"=>"");
						$this->response(json_encode($success), 406);
					}

					// If invalid inputs "Bad Request" status message and reason
					$error = array('status' => "failed", "msg" => "All the fields are required" , "data"=>"");
					$this->response(json_encode($error), 400);
				
				}else{
					
					// user is not validated

					$error = array('status' => "failed", "msg" => "token is not validated" , "data"=>"");
					$this->response(json_encode($error), 400);
				}
			}else{
				// token is required

				$error = array('status' => "failed", "msg" => "token is required" , "data"=>"");
				$this->response(json_encode($error), 400);
			}
		}

		public function get(){

			if($this->get_request_method() != "GET")
			{
				$error = array('status' => "failed", "msg" => "Not allowed" , "data"=>"");
				$this->response(json_encode($error),406);
			}

			// if(!empty($this->getIfSet(getallheaders()['Authorization']))){

			// 	if ($this->validateToken(getallheaders()['Authorization'])) {
			// 		// user is validated
				
			// 	}else{
					
			// 		// user is not validated
			// 	}
			// }else{
			// 	// token is required
			// }

			if(!empty($this->getIfSet(getallheaders()['Authorization']))){

				if ($this->validateToken(getallheaders()['Authorization'])) {
					// user is validated
					$db = new Database();
					$productInfo = $db->fetchRows('products');
					
					if(!empty($productInfo)){
						$response = array('status' => "success", "msg" => "Product found." , "data"=>$productInfo);
					}else{
						$response = array('status' => "success", "msg" => "Product not found." , "data"=>"");
					}
					$this->response(json_encode($response), 200);

				}else{
					
					// user is not validated
					$error = array('status' => "failed", "msg" => "token is not validated" , "data"=>"");
					$this->response(json_encode($error), 400);
				}
			}else{
				// token is required
				$error = array('status' => "failed", "msg" => "token is required" , "data"=>"");
				$this->response(json_encode($error), 400);
			}

		}

		public function update(){

			if($this->get_request_method() != "POST")
			{
				$error = array('status' => "failed", "msg" => "Not allowed" , "data"=>"");
				$this->response(json_encode($error),406);
			}

			// first we will validate the token
			if(!empty($this->getIfSet(getallheaders()['Authorization']))){

				if ($this->validateToken(getallheaders()['Authorization'])) {
					// user is validated, we can proceed

					if( !empty($this->getIfSet($this->_request['product_id']))  ){

						//(int)$this->_request['product_id'];

						$updateData = [];

						if( !empty($this->_request['name']) ){
							$updateData['name'] = $this->_request['name'];
						}
						if( !empty($this->_request['description'])  ){
							$updateData['description'] = $this->_request['description'];
						}
						if( !empty($this->_request['price'])  ){
							$updateData['price'] = $this->_request['price'];
						}
						if( !empty($this->_request['category_id'])  ){
							$updateData['category_id'] = $this->_request['category_id'];
							// check category id exist or not
							$db = new Database();
							$catInfo = $db->check_exist('categories' , ['id' => $this->_request['category_id']]);
							if(empty($catInfo)){
								$error = array('status' => "failed", "msg" => "Cat id is not found" , "data"=>"");
								$this->response(json_encode($error), 400);
							}
						}

						if(empty($updateData)){
							$error = array('status' => "failed", "msg" => "Please update atleast one value" , "data"=>"");
							$this->response(json_encode($error), 400);
						}

						$db = new Database();
						$productResult = $db->update('products' , $updateData , ['id' => $this->_request['product_id']]);
						if($productResult){
							$response = array('status' => "success", "msg" => "Updated successfully." , "data"=>"");
							$this->response(json_encode($response), 200);
						}else{
							$response = array('status' => "failed", "msg" => "invalid product id" , "data"=>"");
							$this->response(json_encode($response), 400);
						}
					}else{

						$error = array('status' => "failed", "msg" => "Product id is required" , "data"=>"");
						$this->response(json_encode($error), 400);
					}
				}else{
					
					// user is not validated
					$error = array('status' => "failed", "msg" => "token is not validated" , "data"=>"");
					$this->response(json_encode($error), 400);
				}
			}else{
				// token is required
				$error = array('status' => "failed", "msg" => "token is required" , "data"=>"");
				$this->response(json_encode($error), 400);
			}

		}

		public function delete(){

			if($this->get_request_method() != "POST")
			{
				$error = array('status' => "failed", "msg" => "Not allowed" , "data"=>"");
				$this->response(json_encode($error),406);
			}
			
			// first we will validate the user's token
			if(!empty($this->getIfSet(getallheaders()['Authorization']))){

				if ($this->validateToken(getallheaders()['Authorization'])) {
					// user is validated

					if( !empty($this->getIfSet($this->_request['product_id']))  ){
						$db = new Database();
						// delete product
						$productDeleteInfo = $db->delete('products' , ['id' => $this->_request['product_id']]);
						if(empty($productDeleteInfo)){
							$error = array('status' => "failed", "msg" => "product id not found!!" , "data"=>"");
							$this->response(json_encode($error), 400);
						}else{
							$response = array('status' => "success", "msg" => "deleted successfully." , "data"=>"");
							$this->response(json_encode($response), 200);
						}

					}else{
						$error = array('status' => "failed", "msg" => "Product id is required" , "data"=>"");
						$this->response(json_encode($error), 400);
					}
				
				}else{
					
					// user is not validated
					$error = array('status' => "failed", "msg" => "token is not validated" , "data"=>"");
					$this->response(json_encode($error), 400);
				}
			}else{
				// token is required
				$error = array('status' => "failed", "msg" => "token is required" , "data"=>"");
				$this->response(json_encode($error), 400);
			}
		}
		
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>