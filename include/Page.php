<?php

require_once 'X937/X937File.php';

/**
 * Class for controlling a page.
 *
 * @author astanley
 */
class Page {
	private $settings;		// array with the settings in it.
	private $message;		// Session message;
	private $database;		// PDO database object
	
	private $ipAddress;
	private $userName;
	
	// Constructor
	public function page($configLocation = '/etc/file-exch-config.ini') {
		// parses in settings for File Exchange
		$this->settings = parse_ini_file($configLocation);
		
		// open the session and check for a message
		session_start();
		if(isset($_SESSION['message'])) {
			$this->message = $_SESSION['message'];
		}
		
		// set convience variables
		$this->ipAddress = $_SERVER['REMOTE_ADDR'];
		$this->userName  = $_SERVER['REMOTE_USER'];
		
		// create DB connection
		$dbName = $this->getSetting('DB_NAME');
		$dbHost = $this->getSetting('DB_HOST');
		$dbUser = $this->getSetting('DB_USER');
		$dbPass = $this->getSetting('DB_PASS');
		
		$dsn = "mysql:dbname=$dbName;host=$dbHost";

		$this->database = new PDO($dsn, $dbUser, $dbPass);
	}
	
	// getters
	public function getSetting($setting) { return $this->settings[$setting]; }
	public function getMessage($unsetMessage = TRUE) {
		// we store the session message here because unsetMessage unsets it.
		$message = $this->message;
		
		if ($unsetMessage) { 
			$this->unsetMessage();
		}
		
		return $message;
	}
	
	// set the session message
	public function setMessage($message) {
		$this->message       = $message;
		$_SESSION['message'] = $message;
	}
	
	// unset message
	public function unsetMessage() {
		if(isset($_SESSION['message'])) {
			unset($_SESSION['message']);
		}
		$this->message = NULL;
	}

	public function logMessage($message) {
		$logMessage = date(DATE_ISO8601) . ' ' . $_SERVER['REMOTE_ADDR']  . ' '. $_SERVER['REMOTE_USER'] . ' ' . $_SERVER['SCRIPT_FILENAME'] . ' - ' .  $message . PHP_EOL;
    
		$logFileHandle = fopen($this->getSetting('LOG_PATH'), 'a');
		fwrite($logFileHandle, $logMessage);
		fclose($logFileHandle);
	}
	
	public function addTransaction($action, $fileId = '') {		
		$sqlInsert = "INSERT INTO `transactions` (`user_name`, `action`, `ip`, `file_id`)";
		$sqlValues = "VALUES ('$this->userName', '$action', INET_ATON('$this->ipAddress'), '$fileId')";		
		$sql = $sqlInsert . ' ' . $sqlValues;
		
		$this->database->exec($sql);
	}
	
	public function addFile($filename, $tempPath) {
		// set filepath (move destination)
		$filePath = $this->getSetting('UPLOAD_PATH') . $filename;
		
		// get details
		$fileX937 = new X937File($tempPath);

		$fileItemCount   = $fileX937->getFileItemCount();
		$fileTotalAmount = $fileX937->getFileTotalAmount();

		$fileInfo = new SplFileInfo($tempPath);		// although X937 contains this data, we want to doublecheck

		$fileHash = sha1_file($tempPath);
		$fileSize = $fileInfo->getSize();
		
		$sqlColums[] = '`filename`';
		$sqlColums[] = '`user_name`';
		$sqlColums[] = '`file_hash`';
		$sqlColums[] = '`file_size`';
		$sqlColums[] = '`total_item_count`';
		$sqlColums[] = '`total_item_amount`';
		$sqlInsert = "INSERT INTO `files` (" . implode(', ', $sqlColums) . ")";
		$sqlValues = "VALUES ('$filename', '$this->userName', '$fileHash', '$fileSize', '$fileItemCount', '$fileTotalAmount')";
		
		$sql = $sqlInsert . ' ' . $sqlValues;
		
		$this->database->beginTransaction();
		
		$rowCount = $this->database->exec($sql);
		$fileId   = $this->database->lastInsertId();
		
		// our insert should result in one row being added, if it didn't we have problems.
		if($rowCount === 1) {
			$this->addTransaction('upload', $fileId);
		} else {
			$errorMessage = $this->database->errorInfo();
			$this->setMessage('Error adding file to database. Possible duplicate file.');
			$this->logMessage('Error adding file to database:' . ' ' . $errorMessage[2]);
			$this->database->rollback();
			return false;
		}

		if (move_uploaded_file($tempPath, $filePath)) {
			$message = "The file <span class=\"u\">$filename</span> has been uploaded";
			$from    = 'From: ' . $this->getSetting('MAIL_FROM');
        
			$subject = 'File Exchange - File uploaded.';
			$mailMessage = "User $this->userName has succesfully uploaded a file to File-Exchange." . PHP_EOL
						 . "File Name: $filename" . PHP_EOL
					     . "File Total: $fileTotalAmount" . PHP_EOL
					     . "File Item Count: $fileItemCount" . PHP_EOL
					     . "File Size: $fileSize" . PHP_EOL
					     . "File SHA1: $fileHash";
	        foreach ($this->getSetting('MAIL_LIST') as $mailTo) {
		        mail($mailTo, $subject, $mailMessage, $from);
	        }
			
			$this->setMessage($message);
			
			$this->database->commit();
			
			$this->transferFile($fileId);
			
			return true;
		} else {
			$message = "There was an error uploading the file <span class=\"u\">$filename</span>, please try again!";
			
			$this->setMessage($message);
			$this->logMessage("Error moving file $filePath");
			$this->database->rollback();
			
			return false;
		}
	}
	
	public function transferFile($fileId) {
		$fileRow = $this->getFileById($fileId);
		
		$fileName = basename($fileRow[0]['filename']);
		$filePath = $this->getSetting('UPLOAD_PATH') . $fileName;
		
		$destPath = $this->getSetting('TRANSFER_PATH') . $fileName;
		
		$result = copy($filePath, $destPath);
		
		if ($result) {
			$this->addTransaction('transfer', $fileId);
		}
		
		return $result;
	}
	
	/**
	 * Gets all transactions for a file 
	 * @param int $fileId Id of the file to get transactions of
	 * @return array associateve array of matching transactions
	 */
	public function getTransactionsByFileId($fileId) {
		$sql = "SELECT * FROM `transactions` WHERE `transactions`.`file_id` = :fileId";
		
		$statement = $this->database->prepare($sql);
		$statement->bindParam(':fileId', $fileId, PDO::PARAM_INT);
		$statement->execute();
		
		$return = $statement->fetchAll();		
		return $return;
	}

	/**
	 * Gets all files matching our id (should only be one)
	 * @param int $id ID of the file to retrieve transactions of.
	 * @return array associateve array of matching file.
	 */
	public function getFileById($id) {
		$sql = "SELECT * FROM `files` WHERE `files`.`id` = :id";
		
		$statement = $this->database->prepare($sql);
		$statement->bindParam(':id', $id, PDO::PARAM_INT);
		$statement->execute();
		
		$return = $statement->fetchAll();		
		if (count($return) > 1) {
			throw new Exception('Database returned more then 1 row for an index!');
		}
		
		return $return;
	}
	
	/**
	 * Gets all files from the database.
	 * @return array associtive array of all files in the DB
	 */
	public function getFiles() {
		// bad ass query, gets just the files and their latest transaction.
		$sql = "SELECT `transactions`.`action`, `transactions`.`user_name` AS `last_transaction_username`, `last_transactions`.`last_timestamp`, INET_NTOA(`transactions`.`ip`) AS `ip`,
				       `files`.`id`, `files`.`filename`, `files`.`total_item_count`, `files`.`total_item_amount`, `files`.`user_name` AS `file_upload_username`, `files`.`file_hash`, `files`.`file_uploaded`, `files`.`file_size`
					FROM (
						SELECT `file_id`, MAX(`timestamp`) AS `last_timestamp`
						FROM `transactions`
						GROUP BY `file_id`
					) AS `last_transactions`
				JOIN `transactions`
				ON
					`transactions`.`file_id` = `last_transactions`.`file_id`
					AND `transactions`.`timestamp` = `last_transactions`.`last_timestamp`
				JOIN `files`
				ON `files`.`id` = `transactions`.`file_id`
				ORDER BY `files`.`file_uploaded` DESC
				LIMIT 30";
		
		$statement = $this->database->prepare($sql);
		$statement->execute();
		
		return $statement->fetchAll();
	}
}