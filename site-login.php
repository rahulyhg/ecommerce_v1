<?php

use Hcode\Page;
use Hcode\Model\User;



$app->get( "/login", function()
{

	$page = new Page();

	$page->setTpl(
		
		"login", 
		
		[

			'error'=>User::getError(),
			'errorRegister'=>User::getErrorRegister(),
			'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']

		]
	
	);//end setTpl

});//END route







$app->post( "/login", function()
{

	try
	{

		User::login($_POST['login'], $_POST['password']);

	}//end try
	catch( Exception $e )
	{

		User::setError($e->getMessage());

	}//end catch

	header("Location: /checkout");
	exit;

});//END route







$app->get( "/logout", function()
{

	User::logout();

	header("Location: /login");
	exit;

});//END route







$app->post( "/register", function()
{

	$_SESSION['registerValues'] = $_POST;



	if( 
		
		!isset($_POST['name']) 
		|| 
		$_POST['name'] == ''
	)
	{

		User::setErrorRegister("Preencha o seu nome.");
		header("Location: /login");
		exit;

	}//end if




	if(
		
		!isset($_POST['email']) 
		|| 
		$_POST['email'] == ''
	)
	{

		User::setErrorRegister("Preencha o seu e-mail.");
		header("Location: /login");
		exit;

	}//end if





	if(
		
		!isset($_POST['password']) 
		|| 
		$_POST['password'] == ''
		
	)
	{

		User::setErrorRegister("Preencha a senha.");
		header("Location: /login");
		exit;

	}//end if





	if( User::checkLoginExists($_POST['email']) === true )
	{

		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;

	}//end if




	$user = new User();

	$user->setData([

		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']

	]);//end setData

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header('Location: /checkout');
	exit;

});//END route







$app->get( "/forgot", function()
{

	$page = new Page();

	$page->setTpl("forgot");	

});//END route





$app->post( "/forgot", function()
{

	# getForgot com parâmetro false para não usar link de verificação do admin
	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;

});//END route






$app->get( "/forgot/sent", function()
{

	$page = new Page();

	$page->setTpl("forgot-sent");	

});//END route







$app->get( "/forgot/reset", function()
{

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl(
		
		"forgot-reset", 
		
		array(

			"name"=>$user["desperson"],
			"code"=>$_GET["code"]
		)
	
	);//end setTpl

});//END route






$app->post( "/forgot/reset", function()
{

	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = User::getPasswordHash($_POST["password"]);

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success");

});//END route




?>