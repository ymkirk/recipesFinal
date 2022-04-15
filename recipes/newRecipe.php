<?php
include '..\admin\config.php';
 if(!$conn)  {
    echo "Failed to connect to MySQL: ". mysqli_connect_error();
}

$pageTitle = "New Recipe";
$recipeTitle = $RecipeContent = $recipeImage = NULL;
$invalid_recipeTitle = $invalid_recipeContent = NULL;
$pageContent = NULL;
$msg = NULL;

if(isset($_SESSION['userID'])) {
   $userID = $_SESSION['userID'];
}else {
   $userID = 20;//testing purpose change later
}

if(filter_has_var(INPUT_POST, 'edit'))  {
   $edit = TRUE;
}  else  {
   $edit = FALSE;
}


if(filter_has_var(INPUT_POST, 'recipeID'))  {
   $recipeID = filter_input(INPUT_POST, 'recipeID');
}elseif(filter_has_var(INPUT_GET, 'recipeID'))  {
   $recipeID = filter_input(INPUT_GET, 'recipeID');
}else {
   $recipeID = NULL;
}

if ($recipeID) {
	$stmt = $conn->stmt_init();
   if ($stmt->prepare("SELECT `recipeTitle`, `recipeContent` FROM `recipe_table` WHERE `recipeID` = ?")) {
      $stmt->bind_param("i", $recipeID);
      $stmt->execute();
      $stmt->bind_result($title, $content);
      $stmt->fetch();
      $stmt->close();
   }
$buttons = <<<HERE
      <div class="form-group">
         <input type="hidden" name"recipeID" value="$recipeID">
         <input type="hidden" name="process">
         <input type="submit" name="update" value="Update Post" class="btn btn-info">
      </div>
HERE;
} else {
   $buttons = <<<HERE
      <div class="form-group">
         <input type="hidden" name="process">
         <input type="submit" name="insert" value="Save Recipe" class="btn btn-success">
      </div>
HERE;
}

// check delete Recipe posted
if(filter_has_var(INPUT_POST, 'delete'))  {
   $stmt = $conn->stmt_init();
   if ($stmt->prepare("DELETE FROM `recipe_table` WHERE = ?")){ 
      $stmt->bind_param("i", $recipeID);
      $stmt->execute();
      $stmt->close();
   }
   header ("newRecipe.php");
   exit();
}
if(filter_has_var(INPUT_POST, 'process'))  {
   $valid = TRUE;
   $title = mysqli_real_escape_string($conn, trim($_POST['recipeTitle'])); 
   if (empty($RecipeTitle))  {
         $invalid_title = '<span class="error">Required</span>';
         $valid = FALSE;
      }
   
   $content = mysqli_real_escape_string($conn, trim($_POST['RecipeContent'])); 
   if (empty($content))  {
         $invalid_recipeContent = '<span class="error">Required</span>';
         $valid = FALSE;
      }
   if($valid)  {
      if(filter_has_var(INPUT_POST, 'insert'))  {
         $stmt = $conn->stmt_init();
         if ($stmt->prepare("INSERT INTO `recipe_table`(`recipeTitle`, `recipeContent`, `authorID`) VALUES (?, ?, ?)")) {
            $stmt->bind_param("ssi", $recipeTitle, $recipeContent, $userID);
            $stmt->execute();
            $stmt->close();
         }
         $postID = mysqli_insert_id($conn);
         header ("Location: newRecipe.php?recipeID=$recipeID");
         exit();
      }
      if(filter_has_var(INPUT_POST, 'update'))  {
         $stmt = $conn->stmt_init();
         if ($stmt->prepare("UPDATE `recipe_table` SET `recipeTitle`= ?, `recipeContent`= ? WHERE `recipeID` = ?")) {
            $stmt->bind_param("ssi", $recipeTitle, $recipeContent, $recipeID);
            $stmt->execute();
            $stmt->close();
         }
         header ("Location: newRecipe.php?recipeID=$recipeID");
         exit();
      }
   } 
}

if ($edit) {
   $pageContent .= <<<HERE
   <section class="container-fluid">
      $msg
      <p>Add your own recipes here</p>
      <form action="newRecipe.php" method="post">
         <div class="form-group">
            <label for="recipeTitle">recipe Title</label>
            <input type="text" name="recipeTitle" id="recipeTitle" value="$recipeTitle" placeholder="Recipe Title" class ="form-control">$invalid_recipeTitle
         </div>
         <div class="form-group">
            <label for="recipeContent">Recipe Content</label>
            <textarea name="recipeContent" id="recipeContent" class="form-control">$recipeContent</textarea>$invalid_recipeContent
         </div>
         $buttons
      </form>
      <form action="newRecipe.php" method="post">
         <div class="form-group">
            <input type="submit" name="cancel" value="Show Recipe List" class="btn btn-warning">
         </div>
      </form>
   </section>\n
HERE;
} elseif ($recipeID) {
	$pageContent .= <<<HERE
   <h2>$recipeTitle</h2>
   <p>$title</p>
   <p>$content</p>
   <form action="newRecipe.php" method="post">
      <div class="form-group">
         <input type="hidden" name="postID" value="$postID">
         <input type="submit" name="edit" value="Edit Post" class="btn btn-info">
      </div>
   </form>
   <form action="newRecipe.php" method="post">
      <div class="form-group">
         <input type="submit" name="cancel" value="Recipe List" class="btn btn-warning">
      </div>
   </form>
   <form action="newRecipe.php" method="post">
   <div class="form-group">
      <input type="hidden" name="postID" value="$recipeID">
      <input type="submit" name="delete" value="Delete Post" class="btn btn-danger">
   </div>
   </form>
HERE;
} else {
// 	select data from db
// 	load default list
   $where = 1;
   $stmt = $conn->stmt_init();
   if ($stmt->prepare("SELECT `recipeID`, `recipeTitle` FROM `recipe_table` WHERE ?")) {
      $stmt->bind_param("i", $where);
      $stmt->execute();
      $stmt->bind_result($recipeID, $recipeTitle);
      $stmt->store_result();
      $classList_row_cnt = $stmt->num_rows();

      if($classList_row_cnt > 0){ // make sure we have at least 1 record
         $selectPost = <<<HERE
         <ul>\n
HERE;
         while($stmt->fetch()){ // loop through the result set to build our list
         $selectPost .= <<<HERE
            <li><a href="newRecipe.php?recipeID=$recipeID">$recipeTitle</a></li>\n
HERE;
         }
         $selectPost .= <<<HERE
         </ul>\n
HERE;
      } else {
         $selectPost = "<p>There are no recipes to see.</p>";
      }
      $stmt->free_result();
      $stmt->close();
   } else {
      $selectPost = "<p>Recipe system is down now. Please try again later.</p>";
   }

   $pageContent .= <<<HERE
   <h2>My Recipes</h2>
   $selectPost
   <form action="newRecipe.php" method="post">
   <div class="form-group">
      <input type="submit" name="edit" value="Create New Post" class="btn btn-success">
   </div>
   </form>
HERE;
}

// assemble html
//superglobals
// $pageContent .= "<pre>";
// $pageContent .= print_r($_POST, true);
// $pageContent .= "</pre>";
include 'recipeTemplate.html';
?>