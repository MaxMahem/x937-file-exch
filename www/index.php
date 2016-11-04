<?php

// include our config.
require_once 'Page.php';
require_once 'X937/X937File.php';

$index = new Page();

/** LIST UPLOADED FILES **/

$files = $index->getFiles();

// get the page message (if any) and unset it.
$message = $index->getMessage(TRUE);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">  

<html xmlns="http://www.w3.org/1999/xhtml">  

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<meta http-equiv="Pragma" CONTENT="no-cache"> 
	<link rel="stylesheet" type="text/css" href="style.css" />
	<link rel="shortcut icon" href="favicon.ico" />
	<script src="sorttable.js"></script>
	<title>CNB File Exchange</title>
</head>

<body>

<div id="container">
	<h1>CNB File Exchange</h1>  

	<?php
	if (!is_null($message)) {
		echo "<div class='message'>$message</div>";
	}
	?>

	<fieldset>
		<legend>Upload a File</legend>  
		<form method="post" action="upload.php" enctype="multipart/form-data">
			<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
			<div>
				<label for="name">Select file</label><br />
				<input class="first" type="file" name="file" />
				<input type="submit" name="submit" value="Upload" />
			</div>
		</form>
	</fieldset>

	<fieldset>
		<legend>Uploaded Files</legend>  

		<table class="files sortable">
			<thead>
				<th>File Name</th>
				<th>Uploaded</th>
				<th>By</th>
				<th>Size</th>
				<th class="total">Total</th>
				<th class="count">Count</th>
			</thead>
<? if(count($files) === 0) { echo "<tr><td class=\"name\"><em>No files found</td></tr>"; } ?>
<? foreach ($files as $file) { ?>
			<tr>
				<td class="name">
					<a href="download/<?=$file['id'];?>" title="<?=$file['filename'];?>"><?=$file['filename'];?></a>
					<div class="hidden">SHA1 Hash: <span class='fixed'><?=$file['file_hash'];?></span>
				</td>
				<td class="date"><?=$file['file_uploaded'];?></td>
				<td class="user"><?=$file['file_upload_username'];?></td>
				<td class="size"><?=number_format($file['file_size']/1024);?> kB</div>
				</td>
				<td class="total">$<?=number_format($file['total_item_amount']/2, 2);?></td>
				<td class="count"><?=number_format($file['total_item_count']);?></td>
				<td class="lastaction">
					<span class="<?=($file['action'] === 'download') ? 'download' : 'upload';?>"><?=($file['action'] === 'download') ? '&#x21e9' : '&#x21e7;';?></span>
					<div class="hidden">Last action: <?=$file['action'];?> at <?=$file['last_timestamp'];?> by <?=$file['last_transaction_username'];?> from <?=$file['ip'];?></div>
				</td>
			</tr>
<?
	}         // close files foreach
?>
		</table>
	</fieldset>

	<div class="link"><a href="help.php">Help</a></div>

	<div style="clear:both;"></div>
</div>
</body>
</html>