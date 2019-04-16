<?php 

namespace Hcode\Model;


use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;



class Order extends Model
{

	# Session
	const SESSION = "OrderSession";

	# Error - Success
	const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";





	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("

			CALL sp_orders_save(

				:idorder,
				:idcart,
				:iduser,
				:idstatus,
				:idaddress,
				:vltotal

			)", 
			
			[

				':idorder'=>$this->getidorder(),
				':idcart'=>$this->getidcart(),
				':iduser'=>$this->getiduser(),
				':idstatus'=>$this->getidstatus(),
				':idaddress'=>$this->getidaddress(),
				':vltotal'=>$this->getvltotal()

			]
		
		);//end select

		if( count($results) > 0 )
		{

			$this->setData($results[0]);

		}//end if


	}//END save







	public function get( $idorder )
	{

		$sql = new Sql();

		$results = $sql->select("

			SELECT *
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder

			", 
			
			[

				':idorder'=>$idorder

			]
		
		);//end select

		$results[0]['desperson'] = utf8_encode($results[0]['desperson']);
		$results[0]['desaddress'] = utf8_encode($results[0]['desaddress']);
		$results[0]['descity'] = utf8_encode($results[0]['descity']);
		$results[0]['desdistrict'] = utf8_encode($results[0]['desdistrict']);

		if( count($results) > 0 )
		{

			$this->setData($results[0]);
			
		}//end if

	}//END get







	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("

			SELECT * FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC

		");//end select

	}//END listAll






	public function delete()
	{

		$sql = new Sql();

		$sql->query("

			DELETE FROM tb_orders
			WHERE idorder = :idorder

			", 
			
			[

				'idorder'=>$this->getidorder()

			]
		
		);//end query

	}//END delete







	public function getCart()
	{

		$cart = new Cart();

		$cart->get((int)$this->getidcart());

		return $cart;
		
	}//END getCart








	public static function setError( $msg )
	{

		$_SESSION[Order::ERROR] = $msg;

	}//END setError









	public static function getError()
	{

		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

		Order::clearError();

		return $msg;

	}//END getError







	public static function clearError()
	{
		$_SESSION[Order::ERROR] = NULL;

	}//END clearError








	public static function setSuccess($msg)
	{

		$_SESSION[Order::SUCCESS] = $msg;

	}//END setSuccess






	public static function getSuccess()
	{

		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

		Order::clearSuccess();

		return $msg;

	}//END getSuccess







	public static function clearSuccess()
	{
		$_SESSION[Order::SUCCESS] = NULL;

	}//END clearSuccess 








	public static function getPage( $page = 1, $itensPerPage = 10 )
	{

		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $itensPerPage;

		");//end select

		$resultTotal = $sql->select("
		
			SELECT FOUND_ROWS() AS nrtotal;
			
		");//end select

		return [

			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)

		];//end return


	}//END getPage








	public static function getPageSearch( $search, $page = 1, $itensPerPage = 10 )
	{

		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a
			INNER JOIN tb_ordersstatus b USING(idstatus)
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :id OR f.desperson LIKE :search 
			ORDER BY a.dtregister DESC
			LIMIT $start, $itensPerPage;

			", 
			
			[

				':search'=>'%'.$search.'%',
				':id'=>$search

			]
		
		);//end select

		$resultTotal = $sql->select("
		
			SELECT FOUND_ROWS() AS nrtotal;
			
		");//end select

		return [

			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)

		];//end return


	}//END getPageSearch






	public function toSession()
	{
		$_SESSION[Order::SESSION] = $this->getValues();

	}//END toSession







	public function getFromSession()
	{

		$this->setData($_SESSION[Order::SESSION]);

	}//END getFromSession







	public function getAddress()
	{

		$address = new Address();

		$address->setData($this->getValues());

		return $address;

	}//END getAddress







}//END class Order




 ?>