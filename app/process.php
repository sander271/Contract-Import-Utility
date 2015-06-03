<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:47 PM
 */
require_once "../vendor/autoload.php";
include "fileparser.php";
//session_start();
function uploadFile(){
    global $success;
    $target_dir = "CSV/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = pathinfo($target_file,PATHINFO_EXTENSION);
    $success = false;
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    // Allow certain file formats
    if($fileType != "csv") {
        echo "Sorry, only csv files are allowed." . "<br/>";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
            $_SESSION['filename'] = $_FILES["fileToUpload"]["name"];
            $success = true;
        } else {
            echo "Sorry, there was an error uploading your file.";
            $success = false;
        }
    }
}
function processFile(){
    $username = $_SESSION['username'];
    $password = $_SESSION['password'];
    $authWsdl = 'https://webservices.autotask.net/atservices/1.5/atws.wsdl';
    $opts = array('trace' => 1);
    $client = new ATWS\Client($authWsdl, $opts);
    $zoneInfo = $client->getZoneInfo($username);
//    print_r($zoneInfo);

    $authOpts = array(
        'login' => $username,
        'password' => $password,
        'trace' => 1,   // Allows us to debug by getting the XML requests sent
    );
    $wsdl = str_replace('.asmx', '.wsdl', $zoneInfo->getZoneInfoResult->URL);
    $client = new ATWS\Client($wsdl, $authOpts);
    $lines = file("CSV/". $_SESSION['filename']);
    $accountNames = explode(',',$lines[1]);
    $contactNames = explode(',',$lines[2]);
    $contractNames = explode(',',$lines[3]);
    $descriptions = explode(',',$lines[4]);
    $contractTypes = explode(',',$lines[5]);
    $contractNumbers = explode(',',$lines[6]);
    $startDates = explode(',',$lines[7]);
    $endDates = explode(',',$lines[8]);
    $billingPreferences = explode(',',$lines[9]);
    $estimatedCosts = explode(',',$lines[10]);
    $estimatedHours = explode(',',$lines[11]);
    $estimatedRevenue = explode(',',$lines[12]);
    $contractPeriodTypes = explode(',',$lines[13]);
    $setupFees = explode(',',$lines[14]);
    $timeReportingRequires = explode(',',$lines[15]);
    $isDefaultContracts = explode(',',$lines[16]);

    $contract = new ATWS\AutotaskObjects\Contract();

    $query = new ATWS\AutotaskObjects\Query('Account');
    $queryField = new ATWS\AutotaskObjects\QueryField('AccountName');
    $queryField->addExpression('Equals', $accountNames[4]);
    $query->addField($queryField);
    $account = $client->query($query);
    $accountID = $account->queryResult->EntityResults->Entity->id;

    $contract->AccountID = $accountID;
    $contract->ContactName = $contactNames[4];
    switch($billingPreferences[4]){
        case "Immediately":
            $contract->BillingPreference = 1;
            break;
        case "Reconcile":
            $contract->BillingPreference = 2;
            break;
        case "Timesheet":
            $contract->BillingPreference = 3;
            break;
        default:
            echo "<h2>Error: Your Billing Preference is not one of the accepted values.</h2>";
            exit(1);
            break;
    }
    $allowedPeriodType = true;
    $contract->ContractName = $contractNames[4];
    $contract->ContractNumber = $contractNumbers[4];
    switch($contractTypes[4]){
        case "Time and Materials":
            $contract->ContractType = 1;
            $allowedPeriodType = false;
            break;
        case "Recurring":
            $contract->ContractType = 7;
            break;
        case "Incident":
            $contract->ContractType = 8;
            break;
        case "Block Hour":
            $contract->ContractType = 4;
            break;
        case "Retainer":
            $contract->ContractType = 6;
            break;
        case "Fixed Price":
            $contract->ContractType = 3;
            break;
        default:
            echo "<h2>Error: Your Contract Type is not one of the accepted values.</h2>";
            exit(1);
            break;
    }
    if($allowedPeriodType) {
        switch ($contractPeriodTypes[4]) {
            case "Monthly":
                $contract->ContractPeriodType = "m";
                break;
            case "Quarterly":
                $contract->ContractPeriodType = "q";
                break;
            case "Semi-Annually":
                $contract->ContractPeriodType = "s";
                break;
            case "Yearly":
                $contract->ContractPeriodType = "y";
                break;
            default:
                echo "<h2>Error: Your Contract Period Type is not one of the accepted values.</h2>";
                exit(1);
                break;
        }
    }
    $contract->Description = $descriptions[4];
    $endDate = new DateTime($endDates[4], new DateTimeZone('America/New_York'));
    $contract->EndDate = $endDate->format(DateTime::W3C);
    $contract->EstimatedCost = $estimatedCosts[4];
    $contract->EstimatedHours = $estimatedHours[4];
    $contract->EstimatedRevenue = $estimatedRevenue[4];
    switch($isDefaultContracts[4]){
        case "Yes":
            $contract->IsDefaultContract = true;
            break;
        case "No":
            $contract->IsDefaultContract = false;
            break;
        default:
            echo "<h2>Error: The isDefaultContract field must be either \"Yes\" or \"No\".</h2>";
            exit(1);
            break;
    }
    $startDate = new DateTime($startDates[4], new DateTimeZone('America/New_York'));
    $contract->StartDate = $startDate->format(DateTime::W3C);
    $contract->SetupFee = $setupFees[4];
    $number = 0;
    if(!is_numeric($timeReportingRequires[4])){
        if(strcmp($timeReportingRequires[4], "yes")){
            $number = 1;
        }
        else{
            $number = 0;
        }
    }
    $contract->TimeReportingRequiresStartAndStopTimes = $number;
    $contract->Status = 1;
    $contract->id = 0;

//    print_r($contract);
    $query = new ATWS\AutotaskObjects\Query('Contract');
    $queryField = new ATWS\AutotaskObjects\QueryField('ContractName');
    $queryField->addExpression('Equals', $contract->ContractName);
    $query->addField($queryField);
    $exist = $client->query($query);
    print_r($exist->queryResult->EntityResults->Entity->ContractName);
    if(!strcmp($exist->queryResult->EntityResults->Entity->ContractName, $contract->ContractName)){
        echo "<h2>Error: {$contract->ContractName} is a contract that already exists.</h2>";
    }
    else{
        print_r($client->create($contract));
    }

}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Autotask Contract Import Utility</title>
    <style>
        @import url(//fonts.googleapis.com/css?family=Lato:700);

        body {
            margin:0;
            font-family:'Lato', sans-serif;
            text-align:center;
            color: #999;
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
    </style>
</head>
<body>
    <h1>This is where the file will be parsed.</h1>
    <?php uploadFile() ?>
    <br/>
    <?php
    global $success;
    if(!$success){
        echo "<input type=\"button\" value=\"back\" onclick=\"location.replace('enter.php')\">";
    }
    else{
        processFile();
    }
    ?>

</body>
</html>