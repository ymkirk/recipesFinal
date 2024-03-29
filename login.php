<?php
include 'config.php';

if (!$conn){
	echo "Failed to connect to database: " . mysqli_connect_error ();
}

$pageTitle = "Login";
$invalid_user =$invalid_password = NULL;
$message = $pageContent = $username =NULL;

if (filter_has_var (INPUT_POST, 'login')){
	$username = strip_tags (filter_input (INPUT_POST, 'username'));
	$passwordSubmit = trim (filter_input (INPUT_POST, 'password'));
	$valid=TRUE;
	
	if ($username ==NULL){
		$invalid_user = '<span class="error"> --Field required </span>';
		$valid=FALSE;
	}
	if ($passwordSubmit==NULL){
		$invalid_password = '<span class="error"> --Field required </span>';
		$valid=FALSE;
	}
if ($valid){
	$stmt = $conn->stmt_init();
	if ($stmt->prepare("SELECT `userID`, `password` FROM `user_table` WHERE `username`=?")) {
		$stmt->bind_param("s", $username);//lookup the user
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($userID, $password);
		$stmt->fetch();
		$stmt->free_result();
		$stmt->close();
	}else{
		$message = <<<HERE
		<h2 class="alert alert-warning" role="alert">We could not find matching credentials. If you are new user, you can register a new account on our site.  </h2>
HERE;
	}
	if (password_verify ($passwordSubmit,$password)) {
		$stmt = $conn->stmt_init();
		if ($stmt->prepare ("SELECT `firstname`, `roleID` FROM `user_table` WHERE `userID`= ?")){
			$stmt->bind_param ("i",$userID);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($firstname, $roleID);
			
			if ($stmt-> num_rows ==1){
				$stmt->fetch();
				$_SESSION ['userID'] = $userID;
				$_SESSION ['roleID'] = $roleID;
				
				setcookie("firstname", $firstname, time()+(3600*3));
				
				header("Location: profile.php?userID=$userID&msg=You are logged in.");
				exit;
				
			}else{
				$message=<<<HERE
				<h2 class="alert alert-warning" role="alert">We could not access the login records. </h2>
HERE;
			}
			$stmt->close();
		}else{
			$message=<<<HERE
			<h2 class="alert alert-warning" role="alert">We could not find your information.  </h2>
HERE;
		}
	}else{
		$message= <<<HERE
			<h2 class="alert alert-warning" role="alert">We could not find matching credentials. If you are new user, you can register a new account on our site.   </h2>
HERE;
	}	
}

}
$pageContent .= <<<HERE
	<main class="container p-5 my-5 bg-light text-secondary rounded">
		<h2>Please Log In</h2>
		$message
		<form action ="login.php" method ="post">
			<div class="mb-3 mt-3">
			<label class="form-label text-dark"> Username </label>
			<input type="text" class="form-control" id="username" name="username" value="$username" required/>
			$invalid_user 
			</div>
			<div class="mb-3 mt-3">
			<label class="form-label text-dark"> Password </label>
			<input type="password" class="form-control" id="password" name="password" required/>
			$invalid_password 
			</div>
			
			<input class="btn btn-success" type="submit" name="login" value="login"/>
		</form>
	</main>
HERE;
include_once 'template.php';
?>