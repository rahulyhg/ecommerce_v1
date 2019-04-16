<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;




class Cart extends Model
{

	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";




	public static function getFromSession()
	{
		$cart = new Cart();

		if(

			isset($_SESSION[Cart::SESSION]) 
			&& 
			(int)$_SESSION[Cart::SESSION]['idcart'] > 0
			
		)
		{

			# Recupera o carrinho que já existe
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);

		}//end if
		else
		{

			# Tenta carregar o carrinho a partir do session_id(), se conseguir, pula o if
			$cart->getFromSessionID();

			# Verifica se conseguiu carregar o carrinho
			if( !(int)$cart->getidcart() > 0 )
			{

				# Se o idcard não existir, ou seja, não conseguiu carregar um carrinho, então cria um carrinho novo
				$data = [

					'dessessionid'=>session_id()

				];//end $data

				# Aqui eu verifico se o usuário que está preenchendo o carrinho de compra está logado. Se não estiver logado, não tem problema, pode continuar o fluxo. Porém, caso esteja, é interessante eu resgatar o iduser e preencher esta informação dentro do objeto
				if( User::checkLogin(false) )
				{

					$user = User::getFromSession();

					$data['iduser'] = $user->getiduser();

				}//end if

				$cart->setData($data);

				$cart->save();

				$cart->setToSession();

			}//end if

		}//end else

		return $cart;

	}//END getFromSession






	public function setToSession()
	{

		$_SESSION[Cart::SESSION] = $this->getValues();

	}//END setToSession







	public function getFromSessionID()
	{
		$sql = new Sql();

		$results = $sql->select("

			SELECT * FROM tb_carts 
			WHERE dessessionid = :dessessionid;

			", 
			[

				':dessessionid'=>session_id()

			]
		
		);//end select

		if( count($results) > 0 )
		{

			$this->setData($results[0]);
			
		}//end if

	}//END getFromSessionID






	public function get( $idcart )
	{
		$sql = new Sql();

		$results = $sql->select("

			SELECT * FROM tb_carts 
			WHERE idcart = :idcart;

			", 
			
			[

				':idcart'=>$idcart

			]
		
		);//end select

		if( count($results) > 0 )
		{

			$this->setData($results[0]);

		}//end if

	}//END get






	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("

			CALL sp_carts_save(

				:idcart, 
				:dessessionid, 
				:iduser, 
				:deszipcode, 
				:vlfreight, 
				:nrdays

			)", 
			
			[

				':idcart'=>$this->getidcart(), 
				':dessessionid'=>$this->getdessessionid(), 
				':iduser'=>$this->getiduser(), 
				':deszipcode'=>$this->getdeszipcode(), 
				':vlfreight'=>$this->getvlfreight(), 
				':nrdays'=>$this->getnrdays()

			]
		
		);//end select

		$this->setData($results[0]);

	}//END save






	public function addProduct( Product $product )
	{

		$sql = new Sql();

		$sql->query("

			INSERT INTO tb_cartsproducts (idcart, idproduct) 
			VALUES(:idcart, :idproduct)

			", 
			
			[

				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()

			]
		
		);//end query

		$this->getCalculateTotal();


	}//END addProduct







	public function removeProduct( Product $product, $all = false )
	{

		$sql = new Sql();

		if( $all )
		{
			$sql->query("

				UPDATE tb_cartsproducts 
				SET dtremoved = NOW() 
				WHERE idcart = :idcart 
				AND idproduct = :idproduct 
				AND dtremoved IS NULL

				", 
				
				[

					':idcart'=>$this->getidcart(),
					':idproduct'=>$product->getidproduct()

				]
			
			);//end query

		}//end if
		else
		{
			$sql->query("

				UPDATE tb_cartsproducts 
				SET dtremoved = NOW() 
				WHERE idcart = :idcart 
				AND idproduct = :idproduct 
				AND dtremoved IS NULL 
				LIMIT 1;

				", 
				
				[

					':idcart'=>$this->getidcart(),
					':idproduct'=>$product->getidproduct()

				]
			
			);//end query

		}//end else

		$this->getCalculateTotal();

	}//END removeProduct








	public function getProducts()
	{
		$sql = new Sql();

		$rows = $sql->select("

			SELECT b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight,b.desurl,
			COUNT(*) AS nrqtd,SUM(b.vlprice) as vltotal
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b USING (idproduct) 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL
			GROUP BY b.idproduct,b.desproduct,b.vlprice,b.vlwidth,b.vlheight,b.vllength,b.vlweight,b.desurl
			ORDER BY b.desproduct

			", 
			
			[

				':idcart'=>$this->getidcart()

			]
		
		);//end select

		# Verifica o desphoto (gambiarra)
		return Product::checkList($rows);

	}//END getProducts







	public function getProductsTotals()
	{
		$sql = new Sql();

		$results = $sql->select("

				SELECT 
				SUM(vlprice) AS vlprice,
				SUM(vlwidth) AS vlwidth, 
				SUM(vlheight) AS vlheight, 
				SUM(vllength) AS vllength, 
				SUM(vlweight) AS vlweight, 
				COUNT(*) AS nrqtd
				FROM tb_products a
				INNER JOIN tb_cartsproducts b
				ON a.idproduct = b.idproduct
				WHERE b.idcart = :idcart
				AND dtremoved IS NULL;

			", 
			
			[

				':idcart'=>$this->getidcart()

			]
		
		);//end select

		if( count($results) > 0 )
		{

			return $results[0];

		}//end if
		else
		{

			return [];

		}//end else

	}//END getProductsTotal






	public function setFreight( $nrzipcode )
	{
		
		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductsTotals();

		if( $totals['nrqtd'] > 0 )
		{
			# Regras do Correios
			if($totals['vlheight'] < 2 ) $totals['vlheight'] = 2;
			if($totals['vllength'] < 16 ) $totals['vllength'] = 16;

			$qs = http_build_query([

				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'09853120',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'

			]);//end http_build_query


			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

			
			$result = $xml->Servicos->cServico;

			if( $result->MsgErro != '' )
			{

				Cart::setMsgError($result->MsgErro);

			}//end if
			else
			{

				Cart::clearMsgError();

			}//end else

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;

		}//end if
		else
		{

			echo "Erro Requisição de Frete.......";

		}//end else

	}//END setFreight






	public static function formatValueToDecimal( $value )
	{

		$value = str_replace('.', '', $value);

		return str_replace(',', '.', $value);

	}//END formatValueToDecimal






	public static function setMsgError( $msg )
	{

		$_SESSION[Cart::SESSION_ERROR] = $msg;


	}//END setMsgErro






	
	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

		Cart::clearMsgError();

		return $msg;

	}//END getMsgErro






	public static function clearMsgError()
	{

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}//END clearMsgError







	public function updateFreight()
	{

		if( $this->getdeszipcode() != '' )
		{

			$this->setFreight($this->getdeszipcode());

		}//end if

	}//END updateFreight








	public function getValues()
	{

		$this->getCalculateTotal();

		return parent::getValues();

	}//END getValues








	public function getCalculateTotal()
	{

		$this->updateFreight();

		$totals = $this->getProductsTotals();

		# Soma dos produtos
		$this->setvlsubtotal($totals['vlprice']);

		# Soma dos produtos + valor do frete
		$this->setvltotal($totals['vlprice'] + $this->getvlfreight());

	}//END getCalculateTotal






	

	public static function removeFromSession()
	{

    	$_SESSION[Cart::SESSION] = NULL;
    	
	}//END removeFromSession







}//END class Cart




 ?>