<?php    

// this page most commonly called via a rewrite rule. Linking URL is generally
// in the format of download/FILEID and is rewritten to download.php?fileId=FILEID

// include our config.
include('Page.php');
include('Database.php');
        
$download = new Page();

// Has the user requested a download?   
if (filter_has_var(INPUT_GET, 'fileId')) {
    // get the id
    $fileId   = filter_input(INPUT_GET, 'fileId', FILTER_SANITIZE_NUMBER_INT);
    
	$fileArray = $download->getFileById($fileId);
	
	$fileName = basename($fileArray[0]['filename']);
    $filePath = $download->getSetting('UPLOAD_PATH') . $fileName;
                
    if (is_file($filePath)) {
        // Set the headers for file download
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Content-Length: ' . filesize($filePath));
                
        // clear the output buffer (should be clean anyways though) and send the headers to the client
        ob_clean();
        flush();
                
        // send the file to the client.
        readfile($filePath);

        // exit after the file has been downloaded.
		$download->logMessage("File \"$filePath\" downloaded.");
		$download->addTransaction('download', $fileId);
		
        exit;
        
    } else {
        $message = "File \"$fileName\" not found.";
        $download->logMessage("File \"$filePath\" requested for download, but not found.");
    }
} else {
    // We didn't get a file request from the user.
    $message = "No file requested for download.";
    $download->logMessage($message);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">  

<head>  
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<meta http-equiv="Pragma" CONTENT="no-cache">   
	<title>CNB File Exchange</title>
	<link rel="stylesheet" type="text/css" href="style.css" /> 
</head>

<body>  

    <div id="container">
        
        <h1>Error</h1>
        
        <p class="message"><?=$message; ?></p>
            
    </div>

</body>  
