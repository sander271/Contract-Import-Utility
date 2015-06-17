<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:43 PM
 */
require_once "../vendor/autoload.php";
//Starts a session with the user of the browser. The session ends when he user closes their browser.
session_start();
//Gets the POST data from the Autotask login form and saves them in session variables.
foreach ($_REQUEST as $key => $value){
    $_SESSION[$key] = $value;
}
$_SESSION['index'] = 0;
$_SESSION['max'] = 0;
//print_r($_SESSION);
$username = $_SESSION['username'];
$password = $_SESSION['password'];
$authWsdl = 'https://webservices.autotask.net/atservices/1.5/atws.wsdl';
$opts = array('trace' => 1);
$client = new ATWS\Client($authWsdl, $opts);
$zoneInfo = $client->getZoneInfo($username);
//print_r($zoneInfo);

$authOpts = array(
    'login' => $username,
    'password' => $password,
    'trace' => 1,   // Allows us to debug by getting the XML requests sent
);
$wsdl = str_replace('.asmx', '.wsdl', $zoneInfo->getZoneInfoResult->URL);
$client = new ATWS\Client($wsdl, $authOpts);
$authorized = true;
try{
    $client->getThresholdAndUsageInfo();
}
catch(Exception $e){
//    echo $e->getCode() . "<br/>";
//    echo $e->getMessage();
    if(!strcmp($e->getMessage(), "Internal Server Error")){
        echo "<h1>Your username and password combination is incorrect.</h1><br/>";
        echo "<input type='button' onclick='back()' value='back'/>";
    }
    $authorized = false;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="logos-icons.png"/>
    <title>Autotask Contract Import Utility</title>
    <style>
        @import url(//fonts.googleapis.com/css?family=Lato:700);

        body {
            margin:0;
            font-family: 'Lato', sans-serif;
            text-align:center;
            color: #ffffff;
            background-color: #00457c;
        }

        a, a:visited {
            text-decoration:none;
        }

        h1 {
            font-size: 32px;
            margin: 16px 0 0 0;
        }

        fieldset {
            border: 0;
        }
        img{
            width: 60%;
        }
        form{
            padding-top: 3em;
        }
    </style>
    <script>
        function back(){
            location.assign("index.php");
        }
    </script>
</head>
<body>
<!--<img src='Autotask%20Logo.jpg'/>-->
<?php
global $authorized;
if($authorized){
    echo "
    <img src='Autotask%20Logo.jpg'/>
    <form action='ajax.php' method='post' enctype='multipart/form-data'>
        <fieldset>
            <legend>Select the CSV file to upload:</legend>
            <input type='file' name='fileToUpload' id='fileToUpload' required>
            <input type='submit' value='Upload File' name='submit'>
        </fieldset>
    </form>";
}
?>
<!--<form action="ajax.php" method="post" enctype="multipart/form-data">-->
<!--    <fieldset>-->
<!--        <legend>Select the CSV file to upload:</legend>-->
<!--        <input type="file" name="fileToUpload" id="fileToUpload" required>-->
<!--        <input type="submit" value="Upload File" name="submit">-->
<!--    </fieldset>-->
<!--</form>-->
</body>
</html>