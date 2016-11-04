<?php

// include our config.
include('Page.php');

$upload = new Page();

// Has the user uploaded something?   
if (isset($_FILES['file'])) {
	// get file name and temp path
	$tempPath = $_FILES['file']['tmp_name'];
	$filename = basename($_FILES['file']['name']);
	
//    $filePath = $upload->getSetting('UPLOAD_PATH') . $fileName;
	
	$upload->addFile($filename, $tempPath);

//    //Try to move the uploaded file into the designated folder   
//    if(move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {   
//        $message = "The file <span class=\"u\">$fileName</span> has been uploaded";
//        $from    = 'From: ' . $upload->getSetting('MAIL_FROM');
//        
//        $subject = 'File Exchange - File uploaded.';
//        $mailMessage = "File $fileName has been succesfully uploaded to FileExchange.";
//        foreach ($upload->getSetting('MAIL_LIST') as $mailTo) {
//            mail($mailTo, $subject, $mailMessage, $from);
//        }
//		
//		// get file attributes
//		$upload->addFile($filePath);
//        $upload->logMessage($mailMessage);
//    } else {   
//        $message = "There was an error uploading the file <span class=\"u\">$fileName</span>, please try again!";
//        $upload->logMessage("Error uploading file $filePath");
//    }
} else {
    $message = "No file was submitted for upload.";
    $upload->logMessage($message);
}

// Put the message in the Session variable.
// $upload->setMessage($message);

// redirect
$redirectURL = 'https://' . $upload->getSetting('FQDN') . '/' . $upload->getSetting('APP_ROOT');
header("Location: $redirectURL", 303);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">  

<html xmlns="http://www.w3.org/1999/xhtml">  

<head>  
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<meta http-equiv="Pragma" CONTENT="no-cache"> 
	<link rel="stylesheet" type="text/css" href="style.css" />
	<title>CNB File Exchange</title>  
</head>  

<body>  

<div id="container">  
	<h1>CNB File Exchange - Upload</h1>  

        <p class='error'>You should not have gotten here!</p>
</div>   

</body>  

</html>
