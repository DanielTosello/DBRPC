<?php
	/**
	 * Aim: create a table-level generation and permission system which utilizes PAM and built in SQL features for maximum flexibility and security.
	 * v0.1 Features: 
	 * 	* Verify user credentials
	 * 	* Handle new users
	 * 	* Create new databases & assign permission
	 *	* Drop existing databases if requested by owner
	 * 	* Return a list of databases owned by the querying user
	 * 
	 * This class is intended to be used with a generic php RPC handler and has not yet been fully tested 
  	 **/ 

  class dbrpc {

            //TODO: add explicit logging & debugging
            //TODO: export the following params to a config and import it at runtime

         //NOTE: the following creator account should have the following permissions ONLY.
	 //CREATE, CREATE USER, GRANT OPTION, RELOAD, SHOW DATABASES on *.*
	 //SELECT on mysql and INFORMATION_SCHEMA

	    private $creatorName = "dbcreator";
            private $creatorPass = "dbcreate_pass";

            private $dbLocation = "mysql://localhost:3306";

	/**
	 * function:MysqlCheckUserExists
	 *    Checks that $user exists in mysql.user table using $this->creatorName permission
	 * @param string $user 
	 * @return boolean
	 *
 	 **/


	private function MysqlCheckUserExists($user) {
		$PDO = new PDO($this->dbLocation, $this->creatorName, $this->creatorPass); 	
		$result = $PDO->query("SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = '" . $user . "')");
		if ($result && $result->fetch()[0]) {
			return true;
		} return false;
	
	}

	/**
	 * function: MysqlCheckDatabaseExists
	 *    Checks that $databaseName exists in database using $this->creatorName permission
	 * @param string $databaseName
	 * @return boolean
	 * 
	 **/

	private function MysqlCheckDatabaseExists($databaseName) {
		$PDO = new PDO($this->dbLocation, $this->creatorName, $this->creatorPass);
		$result = $PDO->query("SELECT EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='" .
			$databaseName ."')");
		if ($result && $result->fetch()[0]) {
			return true;
		} return false;
	}

            /**
             * function: checkCredentials
             *   test $username and $password against auth stack. Throws auth exception on faliure.
             * @param string $username
	     * @param string $password
             * @return boolean
             *
             *
             **/

        private function checkCredentials($username, $password) {
            //do the obvious things:
            //  if( check against authentication authority ) {
            //      if ( !user exists in db ) {
            //          add user to db
            //      }
            //      do the thing
            //  } else {
            //      reject user with auth error
            //  }
            //
            $output = false;

            if ($username == $this->creatorName && $password == $this->creatorPass) { //obvious placeholder
                //check if user exists in remote db
		if(!$this->MysqlCheckUserExists($username)) {
	                $PDO = new PDO($this->dbLocation, $this->creatorName, $this->creatorPass);
		//	$PDO->query("CREATE USER " . $username . " IDENTIFIED WITH authentication_pam"); //with pam	
			$PDO->query("CREATE USER " . $username . " IDENTIFIED BY '". $password . "'"); //sans pam for testing

		}	

		$output = true;

            } else throw new Exception("invalid credentials");

            return $output;

        }


            /**
             * function: createDatabase
	     *    Use $this->creatorName to generate new database named by $databaseName 
	     *    and grant all to $username
             * @param string $username
	     * @param string $password
	     * @param string $databaseName
             *
             *
             **/

            public function createDatabase($username, $password, $databaseName){

                    //check credentials are valid
		if ($this->checkCredentials($username, $password)){
                 	//if database exists return error
			if($this->MysqlCheckDatabaseExists($databaseName)) { Throw new Exception("The database name '" . $databaseName ."' is already in use."); }
			//use creator credentals to create database
			$PDO = new PDO($this->dbLocation, $this->creatorName, $this->creatorPass);
			$PDO->query("CREATE DATABASE " . $databaseName);
			//assign ownership to $user
			$PDO->query("GRANT ALL on " . $databaseName . ".* to " . $username . "@localhost");
			//TODO: explicitly revoke non-essential rights from $this->dbcreator account
		}
                    //return success
		return true;
            }

            /**
             * function: dropDatabase
	     *    Use $username permission to attempt to exec drop on $databaseName
             * @param string $username
	     * @param string $password 
	     * @param string $databaseName
             *
             *
             **/

            public function dropDatabase($username, $password, $databaseName) {

                    //check credentials are valid
		if ($this->checkCredentials($username, $password)){
                    //if database does not exist return error
			if(!$this->MysqlCheckDatabaseExists($databaseName)) { Throw new Exception("This database does not exist");}
		    	//check user has rights to drop database (alt: use user credentials to drop it)
	                //drop database
			$PDO = new PDO($this->dbLocation, $username, $password);
			$PDO->query("DROP DATABASE ". $databaseName);
		}
		
            }


            /**
             * function: grantPermission
	     *   Use $username permission to assign $permission over $databaseName to $user
             * @param string $username
	     * @param string $password
	     * @param string $databaseName 
	     * @param string $user 
	     * @param string $permission
             *
             *
             **/

            public function grantPermission($username, $password, $databaseName, $user, $permission='select'){
			//TODO: support arrays in $permission

                    //check credentials are valid
		throw new Exception ("not yet implemented");
		if ($this->checkCredentials($username, $password)){
                    //check $permission is one of privilege_name (e.g. 'select', 'insert', 'update', 'delete')
			if ($permission == "select" || $permission == "insert" || $permission == "update" || $permission == "delete") {


	                    //use $username & $password to grant permissions
				
        	            //  if $username lacks permission return error
                	    //return success
			}
		}
            }

	   /**
	    * function: getDatabases - return databases $username has rights to access
            * @param string username
	    * @param string password
	    * @return string that i'll format properly later
	    *
	    **/

	    public function getDatabases($username, $password) {
		//check credentials are valid
		if($this->checkCredentials($username, $password)){
			//check databases user has full control over
			$PDO = new PDO($this->dbLocation, $username, $password);
			$result = $PDO->query("Show Databases");
			if($result) { 
				$cat_string = "";
				foreach($result->fetchAll() as $value) { 
					//foreach($value as $value) { $cat_string .= $value . " "; } 
					$cat_string .= $value[0] . "\n";
				}
				return $cat_string;
				
			} 
			return "";
			
		}
				
	    }

 }




?>

