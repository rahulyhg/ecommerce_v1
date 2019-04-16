<?php 

namespace Hcode;


class Model
{

	private $values = [];




	public function __call( $name, $args )
	{

		# O parametro '0' do exemplo abaixo é a posição 
		# O parametro '3' do exemplo não significa posição, mas quantidade de caracteres
		$method = substr( $name, 0, 3 );

		$fieldName = substr( $name, 3, strlen($name) );

		# DEBUG 

		# var_dump($method);
		# echo '<br><br>';
		# var_dump($fieldName);
		# exit;

		switch( $method )
		{
			case "get":
				return (isset($this->values[$fieldName])) ? $this->values[$fieldName] : NULL;
				break;

			case "set":
				$this->values[$fieldName] = $args[0];

		}//end switch

	}//END __call





	public function setData( $data = array() )
	{

		foreach( $data as $key => $value )
		{
			# code...
			$this->{"set".$key}($value);

		}//end foreach

	}//END setData






	public function getValues()
	{

		return $this->values;
		
	}//END getValues






}//END class Model



 ?>