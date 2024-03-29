<?php 

namespace Hcode\DB;


class Sql {

	
	/*
	# LOCALHOST
	##############################
	const HOSTNAME = "127.0.0.1";
	const USERNAME = "root";
	const PASSWORD = "";
	const DBNAME = "db_ecommerce";
	##############################
	*/
	
	
	
	# HOSTGATOR
	##################################
	const HOSTNAME = "162.241.2.229";
	const USERNAME = "amarca35_user";
	const PASSWORD = "We0@2vtPAzB4";
	const DBNAME = "amarca35_db";
	##################################
	
	
	

	private $conn;



	public function __construct()
	{

		$this->conn = new \PDO(

			"mysql:dbname=".Sql::DBNAME.";host=".Sql::HOSTNAME, 
			Sql::USERNAME,
			Sql::PASSWORD

		);//end PDO

	}//END __construct()





	private function setParams( $statement, $parameters = array() )
	{

		foreach ( $parameters as $key => $value )
		{
			
			$this->bindParam($statement, $key, $value);

		}//end foreach

	}//END setParams






	private function bindParam( $statement, $key, $value )
	{

		$statement->bindParam($key, $value);

	}//END bindParam








	public function query( $rawQuery, $params = array() )
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

	}//END query





	public function select( $rawQuery, $params = array() )
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);

	}//END select






}//END class Sql

 ?>