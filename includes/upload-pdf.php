<?php
/*
 * Manges file upload
 */

$upload_dir = $_POST['upload_dir'];

if( !isset($_FILES['Filedata']) ){
	header("HTTP/1.1 500 Internal Server Error");
	echo "No file selected";
	exit;
}

if ($_FILES["Filedata"]["error"] > 0){
	header("HTTP/1.1 500 Internal Server Error");
	echo "Error: " . $_FILES["Filedata"]["error"];
	exit;
}

$file_ext = getFileExtension($_FILES["Filedata"]["name"]);

if(  !in_array( $file_ext ,array('pdf', 'zip' ) ) ) {
	error_log( $file_ext, 0 );
	header("HTTP/1.1 500 Internal Server Error");
	echo "Uploaded file is not valid";
	exit;
}

$uploadfile = $upload_dir .'/'. basename($_FILES['Filedata']['name']);

if (move_uploaded_file($_FILES['Filedata']['tmp_name'], $uploadfile)) {
	var_dump(basename($_FILES['Filedata']['name']).$_FILES["Filedata"]["type"]);
	exit;
	} else {
	header("HTTP/1.1 500 Internal Server Error");
	echo "Upload failed.";
}

function getFileExtension($filename){
	$pathinfo = pathinfo( $filename );
	return strtolower($pathinfo["extension"]);
}
?>
