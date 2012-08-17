<?php

/*
	View Index
*/
Route::get('/', function() {
	Registry::set('forums', new Items(Forum::all()));
	
	return new Template('index');
});

/*
	View forum
*/
Route::get(array('forum/(:any)', 'forum/(:any)/(:num)'), function($slug, $page = 1) {

	list($id, $slug) = parse_slug($slug);

	if( ! $forum = Forum::find($id)) {
		return Response::error(404);
	}

	Registry::set('forum', $forum);
	
	$perpage = 10;

	$topics = Topic::where('forum', '=', $forum->id)
		->sort('votes', 'desc')
		->sort('lastpost', 'desc')
		->take($perpage)
		->skip(--$page * $perpage)
		->get();
		
	Registry::set('topics', new Items($topics));
	
	return new Template('forum');
});

/*
	Login
*/
Route::get('login', function() {
	return new Template('login');
});

Route::post('login', function() {
	if( ! Auth::attempt(Input::get('username'), Input::get('password'))) {
		Input::flash();
		
		Notify::error('Invalid details');

		return Response::redirect('login');
	}

	return Response::redirect('/');
});

/*
	Logout
*/
Route::get('logout', function() {
	Auth::logout();

	return Response::redirect('/');
});

/*
	Register
*/
Route::get('register', function() {
	return new Template('register');
});

Route::post('register', function() {
	$input = array(
		'name' => Input::get('name'), 
		'email' => Input::get('email'),
		'username' => Input::get('username'), 
		'password' => Input::get('password')
	);

	$validator = new Validator($input);

	$validator->check('name')
		->is_max(3, 'Please enter your name');

	$validator->check('email')
		->is_email('Please enter your email address');

	$validator->add('unquie_username', function($str) {
		$user = User::search(array('username' => $str));

		return ! isset($user->id);
	});

	$validator->check('username')
		->is_unquie_username('Username is already taken')
		->is_max(5, 'Please enter a username');

	$validator->check('password')
		->is_max(6, 'Please enter a secure password');

	if($errors = $validator->errors()) {
		Input::flash();

		Notify::error($errors);

		return Response::redirect('register');
	}

	User::create(array(
		'role' => 'user',
		'registered' => date('c'),
		'name' => $input['name'],
		'email' => $input['email'],
		'username' => $input['username'],
		'password' => Hash::make($input['password'])
	));

	$user = User::search(array('username' => $input['username']));

	Session::put(Auth::$session, $user);

	Notify::success('Your account has been created');

	return Response::redirect('/');
});

/*
	404 catch all
*/
Route::any('*', function() {
	return Response::error(404);
});