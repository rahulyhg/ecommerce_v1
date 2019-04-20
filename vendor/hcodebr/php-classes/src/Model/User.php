<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;



class User extends Model
{

	const SESSION = "User";

	# CHAVE DE ENCRIPTAÇÃO TEM QUE TER 16 CARACTERES
	const SECRET = "HcodePhp7_Secret";

	const ERROR = "UserError";

	const ERROR_REGISTER = "UserErrorRegister";

	const SUCCESS = "UserSuccess";







	public static function getFromSession()
	{
		$user = new User();

		if( 

			isset($_SESSION[User::SESSION])
			&& 
			(int)$_SESSION[User::SESSION]['iduser'] > 0
		)
		{

			$user->setData($_SESSION[User::SESSION]);

		}//end if

		return $user;

	}//END getFromSession









	# Apenas verifica se está logado, nada mais
	public static function checkLogin( $inadmin = true )
	{
		if(

			!isset($_SESSION[User::SESSION])
			|| 
			!$_SESSION[User::SESSION]
			|| 
			!(int)$_SESSION[User::SESSION]["iduser"] > 0

		)
		{

			# Em qualquer das condições acima, não está logado
			return false;
	
		}//end if
		else
		{
			if(

				$inadmin === true 
				&& 
				(bool)$_SESSION[User::SESSION]['inadmin'] === true

			)
			{

				# Está logado e é administrador
				return true;

			}//end if
			else if( $inadmin === false )
			{
				
				# Está logado, mas não é administrador
				return true;

			}//end else if
			else
			{
				# Não é administrador, nem cliente, e não está logado
				return false;

			}//end else

		}//end else

	}//END checkLogin











	/*
	# ANTES DA AULA 117
	public static function login($login, $password)
	{
		$sql = new Sql();

		$results = $sql->select("

			SELECT * FROM tb_users 
			WHERE deslogin = :LOGIN

			", array(

			":LOGIN"=>$login
		));

		if(count($results) === 0)
		{
			throw new \Exception("Usuário inexistente ou senha inválida");
			
		}#END if

		$data = $results[0];

		if(password_verify($password, $data["despassword"]) === true)
		{
			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else

		{
			throw new \Exception("Usuário inexistente ou senha inválida");
			
		}

	}//END login
	*/









	# AULA 117
	public static function login( $login, $password )
	{

		$sql = new Sql();

		$results = $sql->select("

			SELECT * FROM tb_users a
			INNER JOIN tb_persons b
			ON a.idperson = b.idperson
			WHERE a.deslogin = :LOGIN

			",  
			
			array(

				":LOGIN"=>$login

			)//end array

		);//end select

		if( count($results) === 0 )
		{
			throw new \Exception("Usuário inexistente ou senha inválida");
			
		}//end if

		$data = $results[0];
		

		if( password_verify( $password, $data["despassword"] ) === true )
		{
			$user = new User();

			# DEBUG	

			# $user->setiduser($data['iduser']);
			# var_dump($user);
			# exit;

			$data['desperson'] = utf8_encode($data['desperson']);

			$user->setData($data);

			# echo "<pre>";
			# var_dump($user);
			# exit;

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		}//end if
		else

		{
			throw new \Exception("Usuário inexistente ou senha inválida");
			
		}//end else

	}//END login








	/* 
	# Antes da aula 117
	public static function verifyLogin($inadmin = true)
	{
		if(!User::checkLogin($inadmin))		
		{
			header("Location: /admin/login/");
			exit;

		}#END if verifyLogin

	}//END verifyLogin
	*/










	# Aula 117
	public static function verifyLogin( $inadmin = true )
	{
		if( !User::checkLogin($inadmin) )		
		{
			
			if( $inadmin )
			{

				header("Location: /admin/login");

			}//end if
			else
			{

				header("Location: /login");

			}//end else

			exit;

		}//end if

	}//END verifyLogin










	public static function logout()
	{
		$_SESSION[User::SESSION] = NULL;
		
	}//END logout










	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("

			SELECT * FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson;

		");//end select
		
	}//END listAll











	public function save()
	{
		$sql = new Sql();
		/* 
		pdesperson VARCHAR(64), 
		pdeslogin VARCHAR(64), 
		pdespassword VARCHAR(256), 
		pdesemail VARCHAR(128), 
		nrphone BIGINT, 
		pinadmin TINYINT 
		*/

		$results = $sql->select("

			CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)
			
			", 
			
			array(

				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()
			
			)//end array

		);//end select

		$this->setData($results[0]);

	}//END save










	# Aula 117
	public function get( $iduser )
	{
		$sql = new Sql();

		$results = $sql->select("
		
			SELECT * FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			WHERE a.iduser = :iduser
			
			", 
		
			array(

				":iduser"=>$iduser

			)//end array

		);//end select

		$data = $results[0];

		$data['desperson'] = utf8_encode($data['desperson']);

		# Recuperar value com acentuação na view admin/users-update
		//$this->setData($results[0]);
		$this->setData($data);

	}//END get









	/*
	# Antes da aula 117
	public function get($iduser)
	{
		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(

			":iduser"=>$iduser

		));

		$this->setData($results[0]);

	}//END get
	*/









	public function update()
	{
		$sql = new Sql();

		$results = $sql->select("
		
			CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)
			
			", 
			
			array(

				":iduser"=>$this->getiduser(),
				":desperson"=>utf8_decode($this->getdesperson()),
				":deslogin"=>$this->getdeslogin(),
				":despassword"=>User::getPasswordHash($this->getdespassword()),
				":desemail"=>$this->getdesemail(),
				":nrphone"=>$this->getnrphone(),
				":inadmin"=>$this->getinadmin()

			)//end array
		
		);//end select

		$this->setData($results[0]);

	}//END update









	public function delete()
	{
		$sql = new Sql();

		$sql->query("
		
			CALL sp_users_delete(:iduser)
		
			", 
		
			array(

				":iduser"=>$this->getiduser()
			
			)//end array
		
		);//end query

	}//END delete






	/*
	CREATE PROCEDURE `sp_users_delete`(
	piduser INT
	)
	BEGIN
	    
	    DECLARE vidperson INT;
	    
	    SET FOREIGN_KEY_CHECKS = 0;
	 
	    SELECT idperson INTO vidperson
	    FROM tb_users
	    WHERE iduser = piduser;
	 
	    DELETE FROM tb_persons WHERE idperson = vidperson;
	    
	    DELETE FROM tb_userspasswordsrecoveries WHERE iduser = piduser;
	    DELETE FROM tb_users WHERE iduser = piduser;
	    
	    SET FOREIGN_KEY_CHECKS = 1;
	    
	END
	*/







	public static function getForgot( $email, $inadmin = true )
	{
		$sql = new Sql();
		
		$results = $sql->select("

			SELECT * FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;

			", 
			
			array(

				":email"=>$email
		
			)//end array
		
		);//end select

		if( count($results) === 0 )
		{
			
			throw new \Exception("Não foi possível recuperar a senha");

		}//end if
		else
		{

			$data = $results[0];
			
			$results2 = $sql->select("
			
				CALL sp_userspasswordsrecoveries_create(:iduser, :desip)
				
				", 
				
				array(
					
					":iduser"=>$data['iduser'],
					":desip"=>$_SERVER['REMOTE_ADDR']

				)//end array
			
			);//end select

			if ( count($results2) === 0 )
			{

				throw new \Exception("Não foi possível recuperar a senha.");

			}//end if
			else
			{

				$dataRecovery = $results2[0];
				
				$iv = random_bytes( openssl_cipher_iv_length('aes-256-cbc') );
				
				$code = openssl_encrypt(
					
					$dataRecovery['idrecovery'], 

					'aes-256-cbc', 

					User::SECRET, 

					0, 

					$iv
				
				);//end openssl_encrypt
				
				$result = base64_encode($iv.$code);
				
				if( $inadmin === true ) 
				{

					$link = "http://ecommerce_full.com.br/admin/forgot/reset?code=$result";

				}//end if
				else
				{

					$link = "http://ecommerce_full.com.br/forgot/reset?code=$result";
				
				}//end else

				$mailer = new Mailer(
					
					$data['desemail'], 
					$data['desperson'], 
					"Redefinir senha da Loja Virtual",
					# template do e-mail em si na /views/email/ e não da administração
					"forgot", 
					
					array(

						"name"=>$data['desperson'],
						"link"=>$link

					)//end array
				
				);//end Mailer
				
				$mailer->send();
				
				# Aula 106 (28:16)
				// return $link;
				return $data;

			}//end else

		}//end else

	}//END getForgot






	/*
	public static function getForgot($email, $inadmin = true)
	{
		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
			", array(
				":email"=>$email
			));

		if(count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha");
			
		}#end if
		else
		{
			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data["iduser"],
				":desip"=>$_SERVER["REMOTE_ADDR"],
			));

			if(count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha");
				
			}#end if
			else
			{
				$dataRecovery = $results2[0];

				/*	# DEPRECATED #
					base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, User::SECRET, $dataRecovery["idrecovery"], MCRYPT_MODE_ECB));
				

					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";

					$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da Hcode Store", "forgot", array(
						"name"=>$data["desperson"],
						"link"=>$link
					));

					$mailer->send();

					return $data;
				

				$iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		        
		        $code = openssl_encrypt($dataRecovery['idrecovery'], 'aes-256-cbc', User::SECRET, 0, $iv);
		        
		        $result = base64_encode($iv.$code);
		        
		        if($inadmin === true)
		        {
		        	$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$result";
		        }#end if
		        else
		        {
		        	$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$result";
		        }#end else
		        
		        $mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
		        	"name"=>$data['desperson'],
		            "link"=>$link
		        )); 
		        
		        $mailer->send();
		        
		        return $link;

			}#end else

		}#end else

	}//END getForgot
	*/







	public static function validForgotDecrypt( $result )
	{
	    $result = base64_decode($result);
	    
	    $code = mb_substr(
			
			$result, 

			openssl_cipher_iv_length('aes-256-cbc'),

			null, 

			'8bit'
		
		);//end mb_substr
		
		
	    $iv = mb_substr(
			
			$result, 

			0, 

			openssl_cipher_iv_length('aes-256-cbc'),

			'8bit'

		);//end mb_substr
	
		
	    $idrecovery = openssl_decrypt(
			
			$code, 

			'aes-256-cbc', 
			
			User::SECRET, 
			
			0, 
			
			$iv
		
		);//end openssl_decrypt
	    
	    $sql = new Sql();
	    
		$results = $sql->select("
		
	        SELECT * FROM tb_userspasswordsrecoveries a
	        INNER JOIN tb_users b USING(iduser)
	        INNER JOIN tb_persons c USING(idperson)
	        WHERE a.idrecovery = :idrecovery AND
	        a.dtrecovery IS NULL AND
			DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
			
			", 
			
			array(

	        	":idrecovery"=>$idrecovery

			)//end array
		
		);//end select

	    if( count($results) === 0 )
	    {

			throw new \Exception("Não foi possível recuperar a senha");
			
	    }//end if
	    else
	    {

	    	return $results[0];

	    }//end else

	}//END validForgotDecrypt








	public static function setForgotUsed( $idrecovery )
	{
		$sql = new Sql();

		$sql->query("
		
			UPDATE tb_userspasswordsrecoveries 
			SET dtrecovery = NOW()
			WHERE idrecovery = :idrecovery
			
			", 
			
			array(

				":idrecovery"=>$idrecovery

			)//end array
		
		);//end query

	}//END setForgotUsed









	public function setPassword( $password )
	{
		$sql = new Sql();

		$sql->query("
		
			UPDATE tb_users 
			SET despassword = :password
			WHERE iduser = :iduser
			
			", 
			
			array(

				"password"=>$password,
				":iduser"=>$this->getiduser()

			)//end array
		
		);//end query

	}//END setPassword









	public static function setError( $msg )
	{

		$_SESSION[User::ERROR] = $msg;

	}//END setError








	public static function getError()
	{

		$msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';

		User::clearError();

		return $msg;

	}//END getError








	public static function clearError()
	{
		$_SESSION[User::ERROR] = NULL;

	}//END clearError
	







	public static function setSuccess( $msg )
	{

		$_SESSION[User::SUCCESS] = $msg;

	}//END setSuccess










	public static function getSuccess()
	{

		$msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';

		User::clearSuccess();

		return $msg;

	}//END getSuccess








	public static function clearSuccess()
	{
		$_SESSION[User::SUCCESS] = NULL;

	}//END clearSuccess










	public static function setErrorRegister( $msg )
	{
		$_SESSION[User::ERROR_REGISTER] = $msg;
		
	}//END setErrorRegister








	public static function getErrorRegister()
	{
		$msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';

		User::clearErrorRegister();

		return $msg;

	}//END getErrorRegister








	public static function clearErrorRegister()
	{
		$_SESSION[User::ERROR_REGISTER] = NULL;
		
	}//END clearErrorRegister









	public static function checkLoginExists( $login )
	{
		$sql = new Sql();

		$results = $sql->select("

			SELECT * FROM tb_users
			WHERE deslogin = :deslogin;

			", 
			
			[

				':deslogin'=>$login

			]
		
		);//end select

		# Colocar o 'count' entre parênteses equivale a um if.
		# If count count($results) > 0 , retorna TRUE
		# If count count($results) = 0 , retorna FALSE
		
		return ( count($results) > 0 );

	}//END checkLoginExists









	public static function getPasswordHash( $password )
	{
		return password_hash(
			
			$password, 
			
			PASSWORD_DEFAULT, 
			
			[

				'cost'=>12

			]
		
		);//end password_hash

	}//END getPasswordHash








	public function getOrders()
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
			WHERE a.iduser = :iduser

			", 
			
			[

				':iduser'=>$this->getiduser()

			]
		
		);//end select

		foreach( $results as &$row )
		{
			# code...		
			$row['desperson'] = utf8_encode($row['desperson']);
			$row['desaddress'] = utf8_encode($row['desaddress']);
			$row['descity'] = utf8_encode($row['descity']);
			$row['desdistrict'] = utf8_encode($row['desdistrict']);

		}//end foreach

		return $results;

	}//END getOrders








	public static function getPage( $page = 1, $itensPerPage = 10 )
	{
		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson) 
			ORDER BY b.desperson
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
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson)
			WHERE b.desemail = :search OR b.desperson LIKE :searchlike OR a.deslogin LIKE :searchlike
			ORDER BY b.desperson
			LIMIT $start, $itensPerPage;

			", 
			
			[

				':searchlike'=>'%'.$search.'%',
				':search'=>$search

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







	/*
	# Aula 126
	public static function getPageSearch($search, $page = 1, $itensPerPage = 10)
	{
		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_users a 
			INNER JOIN tb_persons b USING(idperson)
			WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
			ORDER BY b.desperson
			LIMIT $start, $itensPerPage;

			", [

				':search'=>$search

			]);

		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [

			'data'=>$results,
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)

		];


	}//END getPageSearch
	*/







}//END class User


 ?>