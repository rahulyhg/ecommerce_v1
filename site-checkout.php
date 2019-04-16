<?php

use \Hcode\Page;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;




$app->get( "/checkout", function()
{

	User::verifyLogin(false);

	$address = new Address();

	$cart = Cart::getFromSession();

	if( !isset($_GET['zipcode']) )
	{

		$_GET['zipcode'] = $cart->getdeszipcode();

	}//end if



	if ( isset($_GET['zipcode']) )
	{

		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal();

	}//end if




	if( !$address->getdesaddress() ) $address->setdesaddress('');
	if( !$address->getdesnumber() ) $address->setdesnumber('');
	if( !$address->getdescomplement() ) $address->setdescomplement('');
	if( !$address->getdesdistrict() ) $address->setdesdistrict('');
	if( !$address->getdescity() ) $address->setdescity('');
	if( !$address->getdesstate() ) $address->setdesstate('');
	if( !$address->getdescountry() ) $address->setdescountry('');
	if( !$address->getdeszipcode() ) $address->setdeszipcode('');



	$page = new Page();

	$page->setTpl(
		
		"checkout", 
		
		[
			
			'cart'=>$cart->getValues(),
			'address'=>$address->getValues(),
			'products'=>$cart->getProducts(),
			'error'=>Address::getMsgError()
			
		]
	
	);//end setTpl

});//END route





$app->post( "/checkout", function()
{

	User::verifyLogin(false);

	if( 
		
		!isset($_POST['zipcode']) 
		|| 
		$_POST['zipcode'] === ''
	)
	{

		Address::setMsgError("Informe o CEP.");
		header('Location: /checkout');
		exit;
		
	}//end if




	if(
		!isset($_POST['desaddress']) 
		|| 
		$_POST['desaddress'] === ''
		
	)
	{

		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;

	}//end if


	

	if(
		
		!isset($_POST['desnumber']) 
		|| 
		$_POST['desnumber'] === ''
		
	)
	{

		Address::setMsgError("Informe o número.");
		header('Location: /checkout');
		exit;

	}//end if




	if(
		
		!isset($_POST['desdistrict']) 
		|| 
		$_POST['desdistrict'] === ''
		
	)
	{

		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;

	}//end if




	if(
		
		!isset($_POST['descity']) 
		|| 
		$_POST['descity'] === ''
		
	)
	{

		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;

	}//end if



	if(
		
		!isset($_POST['desstate']) 
		|| 
		$_POST['desstate'] === ''
		
	)
	{

		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;

	}//end if




	if(
		
		!isset($_POST['descountry']) 
		|| 
		$_POST['descountry'] === ''
		
	)
	{

		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;

	}//end if

	$user = User::getFromSession();

	$address = new Address();

	# Backup Aula 28 PS
	$_POST['desaddress'] = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"), $_POST['desaddress']);

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();

	$order = new Order();

	$order->setData([

		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::AGUARDANDO_PAGAMENTO,
		'vltotal'=>$cart->getvltotal()

	]);//end setData

	$order->save();


	# Aula 10 curso PagSeguro
	// $order->toSession();





	# Aula 10 Curso pagseguro (tirando de boleto pelo BoletoPHP e indo pro PagSeguro) - Na Revisão mudei este fluxo e voltei pro BoletoPHP
	/*
	header("Location: /payment");
	exit;
	*/




	# Aula 122 curso PHP7 (Direciona para Boleto)
	// header("Location: /order/".$order->getidorder());
	// exit;





	# Aula 133 curso PHP7 (Direconia para Integração PagSguro HTML)
	//header("Location: /order/".$order->getidorder()."/pagseguro");
	//exit;

	

	# Aula 134 - PayPal (antes não tinha esse switch, ia direto para Pagseguro)
	switch( (int)$_POST['payment-method'] )
	{

		case 1:
		header("Location: /order/".$order->getidorder()."/pagseguro");
		break;

		case 2;
		header("Location: /order/".$order->getidorder()."/paypal");
		break;

	}//end switch

	exit;



});//END route







$app->get( "/order/:idorder", function( $idorder )
{

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$page = new Page();

	$page->setTpl(
		
		"payment", 
		
		[

			'order'=>$order->getValues()

		]
	
	);//end setTpl

});//END route






$app->get( "/boleto/:idorder", function( $idorder )
{

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 




	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');





	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula






	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();







	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";






	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";




	

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . 
	DIRECTORY_SEPARATOR . "res" . 
	DIRECTORY_SEPARATOR . "boletophp" . 
	DIRECTORY_SEPARATOR . "include" . 
	DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");



});//END route







# Aula 133 - Rota do PagSeguro

$app->get( "/order/:idorder/pagseguro", function( $idorder ) 
{
	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();
	
	$page = new Page([

		'header'=>false,
		'footer'=>false

	]);//end Page

	$page->setTpl(
		
		"payment-pagseguro", 
		
		[

			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts(),
			'phone'=>[

				'areaCode'=>substr($order->getnrphone(), 0, 2),
				'number'=>substr($order->getnrphone(), 2, strlen($order->getnrphone()))

			]

		]
	
	);//end setTpl

});//END route







# Aula 134 - Rota do PayPal
$app->get( "/order/:idorder/paypal", function( $idorder ) 
{
	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();
	
	$page = new Page([

		'header'=>false,
		'footer'=>false

	]);//end Page

	$page->setTpl(
		
		"payment-paypal", 
		
		[

			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()

		]
	
	);//end setTpl

});//END route










?>