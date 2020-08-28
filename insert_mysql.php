<?php
/*
  Rui Santos
  Complete project details at https://RandomNerdTutorials.com/esp32-esp8266-mysql-database-php/
  
  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files.
  
  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.
*/
//ini_set("display_errors", 1);
//ini_set("error_reporting", E_ALL);

require '../vendor/autoload.php'; //used composer to install PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$dbname = "DB NAME HERE";
$username = "DB USERNAME HERE";
$password = "DB PASSWORD HERE";
$tablename = "DB TABLE NAME HERE";

// Keep this API Key value to be compatible with the Arduino code provided in the project page. 
// If you change this value, the Arduino sketch needs to match
$api_key_value = "SERVER API KEY HERE";

$api_key = $humidity_1 = $tempF_1 = $humidity_2 = $tempF_2 = $door_locked = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $api_key = TestInput($_POST["api_key"]);
    if($api_key == $api_key_value) {
        $humidity_1 = TestInput($_POST["humidity_1"]);
        $tempF_1 = TestInput($_POST["tempF_1"]);
        $humidity_2 = TestInput($_POST["humidity_2"]);
        $tempF_2 = TestInput($_POST["tempF_2"]);
        $door_locked = TestInput($_POST["door_locked"]);
        $battery_status = TestInput($_POST["battery"]);
        
    //If it is between 8pm and 8:30pm (21 hours) send an email
		$nowTime = new DateTime('now', new DateTimeZone('America/Detroit'));
		$nowTimeTest = $nowTime->format('Gi');
    if ($nowTimeTest >= 2000 && $nowTimeTest <= 2030) {
        SendUpdateEmail($door_locked);
    }

    	$thing_data = [
    		'api_key'	=> 'THINGSPEAK WRITE API KEY HERE',
    		'field1'	=> $humidity_1,
    		'field2'	=> $tempF_1,
    		'field3'	=> $humidity_2,
    		'field4'	=> $tempF_2,
    		'field5'	=> $door_locked,
    		'field6'	=> $battery_status,
    	];
		//'field7'	=> $nowTime->format('h:i A m/d/y')


    	SendToThingSpeak($thing_data);
		
        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        } 
        
        $sql = "INSERT INTO env_data (humidity_1, temp_1, humidity_2, temp_2, door_locked, battery_status)
        VALUES ('" . $humidity_1 . "', '" . $tempF_1 . "', '" . $humidity_2 . "', '" . $tempF_2 . "', '" . $door_locked . "', '" . $battery_status . "')";
        
        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } 
        else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    
        $conn->close();
    }
    else {
        echo "Wrong API Key provided.";
    }


}
else {
    echo "No data posted with HTTP POST.";
}

function TestInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function SendUpdateEmail($doorlocked) {
	
	$lockDateTime = new DateTime('now', new DateTimeZone('America/Detroit'));
	//$lockDateTime = $lockDate->format('l, m-d-y g:i a');
	
	$mail = new PHPMailer();

	try {
	    //Server settings
    	//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    	$mail->isSMTP();                                            // Send using SMTP
    	$mail->Host       = 'smtp.gmail.com';                    	// Set the SMTP server to send through
    	$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    	$mail->Username   = 'EMAIL USERNAME HERE';                  // SMTP username
    	$mail->Password   = 'EMAIL PASSWORD HERE';                  // SMTP password
    	$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    	$mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

    	//Recipients
    	$mail->setFrom('SEND FROM EMAIL HERE', 'Coop');
    	$mail->addAddress('EMAIL ADDRESS HERE', 'NAME HERE');     		// Add a recipient
    	$mail->addAddress('EMAIL ADDRESS HERE');               	// Name is optional
    	//$mail->addReplyTo('info@example.com', 'Information');
    	//$mail->addCC('cc@example.com');
    	//$mail->addBCC('bcc@example.com');

    	// Attachments
    	//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    	//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    	// Content
    	$mail->isHTML(false);                                  // Set email format to HTML
    	//$mail->Subject = 'Coop Lock Status';
    	if ($doorlocked == 0) {
    		$mail->Subject = "Coop is UNLOCKED";
        	$mail->Body    = "Coop door is UNLOCKED at " . $lockDateTime->format('g:i a') . " on " . $lockDateTime->format('l, m-d-y') . ".";
    	} elseif ($doorlocked == 1) {
    		$mail->Subject = "Coop is locked";
    		$mail->Body    = "Coop Door is locked at " . $lockDateTime->format('g:i a') . " on " . $lockDateTime->format('l, m-d-y') . ".";
    	}
    	
    	//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    	$mail->send();
    	echo 'Message has been sent. ';
	} catch (Exception $e) {
    	echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	}
}

function SendToThingSpeak($thing_data) {
	$url = 'https://api.thingspeak.com/update.json';

	$thing_data_json = json_encode($thing_data);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $thing_data_json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}
?>
