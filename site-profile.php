<?php

use Hcode\Page;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\Cart;



$app->get( "/profile", function()
{

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl(
		
		"profile", 
		
		[
		
			'user'=>$user->getValues(),
			'profileMsg'=>User::getSuccess(),
			'profileError'=>User::getError()

		]
	
	);//end setTpl

});//END route






$app->post( "/profile", function()
{

	User::verifyLogin(false);

	if(
		
		!isset($_POST['desperson']) 
		|| 
		$_POST['desperson'] === ''
		
	)
	{

		User::setError("Preencha o seu nome.");
		header('Location: /profile');
		exit;

	}//end if


	if(
		
		!isset($_POST['desemail']) 
		|| 
		$_POST['desemail'] === ''
		
	){

		User::setError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;

	}//end if


	$user = User::getFromSession();

	if( $_POST['desemail'] !== $user->getdesemail() )
	{

		if( User::checkLoginExists($_POST['desemail']) === true )
		{

			User::setError("Este endereço de e-mail já está cadastrado.");
			header('Location: /profile');
			exit;

		}//end if

	}//end if

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];

	$user->setData($_POST);

	# Hcode colocou $user->save(); Aula 120
	$user->update();

	User::setSuccess("Dados alterados com sucesso!");

	header('Location: /profile');
	exit;

});//END route






$app->get( "/profile/orders", function()
{

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl(
		
		"profile-orders", 
		
		[

			'orders'=>$user->getOrders()

		]
	
	);//end setTpl

});//END route






$app->get( "/profile/orders/:idorder", function( $idorder )
{

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl(
		
		"profile-orders-detail", 
		
		[
			
			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()

		]
	
	);//end setTpl

});//END route





$app->get( "/profile/change-password", function()
{

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl(
		
		"profile-change-password", 
		
		[

			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()

		]
	
	);//end setTpl

});//END route






$app->post( "/profile/change-password", function()
{

	User::verifyLogin(false);

	if( 
		
		!isset($_POST['current_pass']) 
		|| 
		$_POST['current_pass'] === ''
		
	)
	{

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if(
		
		!isset($_POST['new_pass']) 
		|| 
		$_POST['new_pass'] === ''
		
	)
	{

		User::setError("Você não digitou a nova senha. Por favor, preencha os dados novamente.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if(
		
		!isset($_POST['new_pass_confirm'])
		|| 
		$_POST['new_pass_confirm'] === ''
		
	)
	{

		User::setError("Você não confirmou a nova senha. Por favor, preencha os dados novamente.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if( $_POST['current_pass'] === $_POST['new_pass'] )
	{

		User::setError("A sua nova senha deve ser diferente da atual. Por favor, preencha os dados novamente.");
		header("Location: /profile/change-password");
		exit;		

	}//end if

	$user = User::getFromSession();

	if( !password_verify( $_POST['current_pass'], $user->getdespassword() ) )
	{

		User::setError("A senha atual inserida está inválida. Por favor, preencha os dados novamente.");
		header("Location: /profile/change-password");
		exit;			

	}//end if

	$user->setdespassword($_POST['new_pass']);

	$user->update();

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /profile/change-password");
	exit;

});//END route






$app->get( "/profile/orders", function()
{

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl(
		
		"profile-orders", 
		
		[

			'orders'=>$user->getOrders()

		]
	
	);//end setTpl

});//END route






$app->get( "/profile/orders/:idorder", function( $idorder )
{

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl(
		
		"profile-orders-detail", 
		
		[

			'order'=>$order->getValues(),
			'cart'=>$cart->getValues(),
			'products'=>$cart->getProducts()

		]
	
	);//end setTpl

});//END route






$app->get( "/profile/change-password", function()
{

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl(
		
		"profile-change-password", 
		
		[

			'changePassError'=>User::getError(),
			'changePassSuccess'=>User::getSuccess()

		]
	
	);//end setTpl

});//END route






$app->post( "/profile/change-password", function()
{

	User::verifyLogin(false);

	if(
		
		!isset($_POST['current_pass']) 
		|| 
		$_POST['current_pass'] === ''
	
	)
	{

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if(
		
		!isset($_POST['new_pass']) 
		|| 
		$_POST['new_pass'] === '')
	
	{

		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if(
		
		!isset($_POST['new_pass_confirm']) 
		|| 
		$_POST['new_pass_confirm'] === ''
		
	)
	{

		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;

	}//end if


	if( $_POST['current_pass'] === $_POST['new_pass'] )
	{

		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;		

	}//end if


	$user = User::getFromSession();

	if( !password_verify($_POST['current_pass'], $user->getdespassword() ) )
	{

		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;			

	}//end if
	

	$user->setdespassword($_POST['new_pass']);

	$user->update();

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /profile/change-password");
	exit;

});//END route




?>