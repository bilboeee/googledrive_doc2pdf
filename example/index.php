<?php		
	if( !empty( $_POST ) && isset($_FILES['file']) ){
		//Set the include path for the Google Api
		set_include_path("../" . PATH_SEPARATOR . get_include_path());
		
		//Load the Class
		require_once('GoogleDriveApi.php');
				
		//Init Infos from Google Account
		//https://developers.google.com/drive/web/quickstart/quickstart-php
		$clientID = '731771463536-tseu3krgjmme6rsrn7s46cg57qu0jo49.apps.googleusercontent.com';
		$serviceAccountName = '731771463536-tseu3krgjmme6rsrn7s46cg57qu0jo49@developer.gserviceaccount.com';
		$keyFile = 'bca603a45b4ea8ba1dd1b6f8015bf139d12bbb98-privatekey.p12';
		
		//Get the instance of the Class
		$GoogleDriveApi = GoogleDriveApi::getInstance( $clientID, $serviceAccountName, $keyFile );
		
		//Create the file into Google Drive
		$file = $GoogleDriveApi->createFile( $_FILES['file'] );
		
		//Get the download URL
		if( $_FILES['file']['type'] == 'application/pdf'){
			$downloadUrl = $file->downloadUrl;
		}
		else{
			$downloadUrl = $GoogleDriveApi->getFileExportLink( 'application/pdf' );
		}
		
		//Name the file for the server
		$newFile = "files/".$file->title;

		//Copy the file into the server
		$file = file_get_contents( $downloadUrl );
		file_put_contents($newFile, $file);

		//Delete File
		$GoogleDriveApi->deleteFile();
	}
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Convert a file with Google Drive API</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

		<link href="http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,800,700,400italic,600italic,700italic,800italic,300italic" rel="stylesheet" type="text/css">
        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

		<div id="page" style="width:600px; margin:auto; padding:50px;">
			<h1>Convert a file with Google Drive API</h1>
			<?php 
				if( isset( $newFile) ) {
					echo '<p class="success">File converted, <a href="'.$newFile.'">download it</a></p>';
				}
			?>
			        
	        <!-- start form -->
	        <form id="testForm" name="testForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
	        	<p class="title">Choose a file to convert </p>
	        	
	        	<div class="content">
	        		<label for="file">File : </label>
					<input type="file" id="file" name="file" />
	        	
	        		<input type="submit" id="submit" name="Submit" value="Convert" />
	        	</div>
	        </form>
	        <!-- end form -->
		</div>
    </body>
</html>
