<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;



class Category extends Model
{


	public static function listAll()
	{
		$sql = new Sql();

		return $sql->select("
		
			SELECT * FROM tb_categories 
			ORDER BY descategory
		
		");//end select
		
	}//END listAll








	public function save()
	{
		$sql = new Sql();

		$results = $sql->select("
		
			CALL sp_categories_save(:idcategory, :descategory)", 
			
			array(

				":idcategory"=>$this->getidcategory(),
				":descategory"=>$this->getdescategory()

			)
		
		);//end select

		$this->setData($results[0]);

		Category::updateFile();

	}//END save








	public function get( $idcategory )
	{
		$sql = new Sql();

		$results = $sql->select("
		
			SELECT * FROM tb_categories
			WHERE idcategory = :idcategory", 
			
			[

				':idcategory'=>$idcategory

			]
		
		);//end select

		$this->setData($results[0]);

	}//END get








	# DELETE não recebe parâmetro porque espera-se que o objeto
	# já esteja carregado
	public function delete()
	{
		$sql = new Sql();

		$sql->query("
		
			DELETE FROM tb_categories
			WHERE idcategory = :idcategory",
			
			[

				':idcategory'=>$this->getidcategory()

			]
		
		);//end query

		Category::updateFile();

	}//END delete






	public static function updateFile()
	{

		$categories = Category::listAll();

		$html = [];

		foreach( $categories as $row )
		{
			# code...
			array_push(
				
				$html, 
				
				'<li><a href="/categories/'.
				$row['idcategory'].'">'.
				$row['descategory'].
				'</a></li>'
			
			);//end array_push

		}//end foreach

		file_put_contents(
			
			$_SERVER['DOCUMENT_ROOT'] . 
			DIRECTORY_SEPARATOR . 
			"views" . DIRECTORY_SEPARATOR . 
			"categories-menu.html", 
			
			implode('', $html)
		
		);//end file_put_contents

	}//END updateFile







	public function getProducts( $related = true )
	{

		$sql = new Sql();

		if( $related === true )
		{
			return $sql->select("

				SELECT * 
				FROM tb_products 
				WHERE idproduct IN(

					SELECT a.idproduct 
					FROM tb_products a
					INNER JOIN tb_productscategories b
					ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory

				);", 
				
				[

					'idcategory'=>$this->getidcategory()

				]

			);//end select

		}//end if
		else
		{
			return $sql->select("

				SELECT * 
				FROM tb_products 
				WHERE idproduct NOT IN(

					SELECT a.idproduct 
					FROM tb_products a
					INNER JOIN tb_productscategories b
					ON a.idproduct = b.idproduct
					WHERE b.idcategory = :idcategory

				);", 
				
				[

					'idcategory'=>$this->getidcategory()

				]

			);//end select

		}//end else

	}//END getProducts







	public function getProductsPage( $page = 1, $itensPerPage = 2 )
	{

		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_products a
			INNER JOIN tb_productscategories b
			ON a.idproduct = b.idproduct
			INNER JOIN tb_categories c
			ON c.idcategory = b.idcategory
			WHERE c.idcategory = :idcategory
			LIMIT $start, $itensPerPage;

			", 
			
			[

				':idcategory'=>$this->getidcategory()

			]
		
		);//end select

		$resultTotal = $sql->select("
		
			SELECT FOUND_ROWS() AS nrtotal;"
		
		);//end select

		return [

			'data'=>Product::checkList($results),
			'total'=>(int)$resultTotal[0]["nrtotal"],
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itensPerPage)

		];//end return


	}//END getProductsPage







	public function addProduct( Product $product )
	{

		$sql = new Sql();

		$sql->query("

			INSERT INTO tb_productscategories (idcategory, idproduct) 
			VALUES(:idcategory, :idproduct)
			
			", 
			
			[

				':idcategory'=>$this->getidcategory(),
				':idproduct'=>$product->getidproduct()

			]
		
		);//end query

	}//END addProduct






	public function removeProduct( Product $product )
	{
		$sql = new Sql();

		$sql->query("

			DELETE FROM tb_productscategories
			WHERE idcategory = :idcategory
			AND idproduct = :idproduct
			
			", 
			
			[

				':idcategory'=>$this->getidcategory(),
				':idproduct'=>$product->getidproduct()

			]
		
		);//end query
		
	}//END removeProduct







	public static function getPage( $page = 1, $itensPerPage = 10 )
	{
		$start = ($page - 1) * $itensPerPage;

		$sql = new Sql();

		$results = $sql->select("

			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_categories 
			ORDER BY descategory
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
			FROM tb_categories 
			WHERE descategory LIKE :search
			ORDER BY descategory
			LIMIT $start, $itensPerPage;

			", 
			
			[

				':search'=>'%'.$search.'%'

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







}//END class Category



 ?>