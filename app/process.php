<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:47 PM
 */
//Gets access to the Autotask API classes.
require_once "../vendor/autoload.php";
include "fileparser.php";
error_reporting(0);
//session_start();
//This fuction uploads the file picked in the last form. It checks to make sure it is of the format .csv
//and if it is not the upload is cancelled.
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
//This is the fuction that parses the file and creates the contracts in the user's Autotask database.
function processFile()
{
    //creates a connection to the Autotask database associated with the user.
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
    //The following line break up the input file into separate lines and further breaks the lines up
    //into individual tokens.
    $lines = file("CSV/" . $_SESSION['filename']);
    $accountNames = explode(',', $lines[1]);
    $contactNames = explode(',', $lines[2]);
    $contractNames = explode(',', $lines[3]);
    $descriptions = explode(',', $lines[4]);
    $contractTypes = explode(',', $lines[5]);
    $contractNumbers = explode(',', $lines[6]);
    $startDates = explode(',', $lines[7]);
    $endDates = explode(',', $lines[8]);
    $billingPreferences = explode(',', $lines[9]);
    $estimatedCosts = explode(',', $lines[10]);
    $estimatedHours = explode(',', $lines[11]);
    $estimatedRevenue = explode(',', $lines[12]);
    $contractPeriodTypes = explode(',', $lines[13]);
    $setupFees = explode(',', $lines[14]);
    $timeReportingRequires = explode(',', $lines[15]);
    $isDefaultContracts = explode(',', $lines[16]);
    $roleRates = explode(',', $lines[17]);
    $recuringServices = explode(',', $lines[18]);
    $blockHourRates = explode(',', $lines[19]);
    $blockHourPurchases = explode(',', $lines[20]);
    $retainerPurchases = explode(',', $lines[21]);
    $ticketPurchases = explode(',', $lines[22]);
    $milestones = explode(',', $lines[23]);

//add loop to create multiple contracts here
    $index = 4;
    while($index < count($contactNames) && !empty($contractNames[$index])){

//    }
//    for($index = 4; $index < count($contractNames); $index++){
        $contract = new ATWS\AutotaskObjects\Contract();

        $query = new ATWS\AutotaskObjects\Query('Account');
        $queryField = new ATWS\AutotaskObjects\QueryField('AccountName');
        $queryField->addExpression('Equals', $accountNames[$index]);
        $query->addField($queryField);
        $account = $client->query($query);
        $accountID = $account->queryResult->EntityResults->Entity->id;

        $contract->AccountID = $accountID;
        $allowedPeriodType = true;
        switch ($contractTypes[$index]) {
            case "Time and Materials":
                $contract->ContractType = 1;
                $allowedPeriodType = false;
                break;
            case "Recurring":
                $contract->ContractType = 7;
                $allowedPeriodType = true;
                break;
            case "Incident":
                $contract->ContractType = 8;
                break;
            case "Block Hour":
                $contract->ContractType = 4;
                $allowedPeriodType = false;
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
        $contract->ContactName = $contactNames[$index];
        switch ($billingPreferences[$index]) {
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
                if($contract->ContractType == 1 || $contract->ContractType == 4 || $contract->ContractType == 6 || $contract->ContractType == 8){
                    echo "<h2>Error: Your Billing Preference is not one of the accepted values.</h2>";
                    exit(1);
                }
                else
                    break;
        }
        $contract->ContractName = $contractNames[$index];
        $contract->ContractNumber = $contractNumbers[$index];
        if ($allowedPeriodType) {
            switch ($contractPeriodTypes[$index]) {
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
                    if($contract->ContractType == 7){
                        echo "<h2>Error: Your Contract Period Type is not one of the accepted values.</h2>";
                        exit(1);
                    }
                    break;
            }
        }
        $contract->Description = $descriptions[$index];
        $endDate = new DateTime($endDates[$index], new DateTimeZone('America/New_York'));
        $contract->EndDate = $endDate->format(DateTime::W3C);
        switch ($isDefaultContracts[$index]) {
            case "Yes":
                $contract->IsDefaultContract = true;
                break;
            case "No":
                $contract->IsDefaultContract = false;
                break;
            default:
                $contract->IsDefaultContract = false;
                break;
                break;
        }
        $startDate = new DateTime($startDates[$index], new DateTimeZone('America/New_York'));
        $contract->StartDate = $startDate->format(DateTime::W3C);
        if($contract->ContractType == 7){
            $contract->SetupFee = $setupFees[$index];
        }
        $number = 0;
        if (!is_numeric($timeReportingRequires[$index])) {
            if (strcmp($timeReportingRequires[$index], "yes")) {
                $number = 1;
            } else {
                $number = 0;
            }
        }
        $contract->TimeReportingRequiresStartAndStopTimes = $number;
        $contract->Status = 1;
        $contract->EstimatedHours = $estimatedHours[$index];
        $contract->EstimatedCost = $estimatedCosts[$index];
        $contract->EstimatedRevenue = $estimatedRevenue[$index];
        if($contract->ContractType == 7){
            if(empty($contract->EstimatedHours)){
                unset($contract->EstimatedHours);
            }
            if(empty($contract->EstimatedCost)){
                unset($contract->EstimatedCost);
            }
            if(empty($contract->EstimatedRevenue)){
                unset($contract->EstimatedRevenue);
            }
        }
        //this loop removes empty values before the create function
        removeEmpty($contract);
        $contract->id = 0;

        $query = new ATWS\AutotaskObjects\Query('Contract');
        $queryField = new ATWS\AutotaskObjects\QueryField('ContractName');
        $queryField->addExpression('Equals', $contract->ContractName);
        $query->addField($queryField);
        $exist = $client->query($query);
        if (!strcmp($exist->queryResult->EntityResults->Entity->ContractName, $contract->ContractName)) {
            echo "<h2>Error: {$contract->ContractName} is a contract that already exists.</h2>";
        } else {
            $newContract = $client->create($contract);
            if($newContract->createResult->ReturnCode != 1){
                print_r($newContract->createResult->Errors);
            }
            else{
                echo "<h2>{$newContract->createResult->EntityResults->Entity->ContractName} was created successfully.</h2>";
            }
            $createdContract = $newContract->createResult->EntityResults->Entity;
            if($createdContract->ContractType == 1 || $createdContract->ContractType == 6 || $createdContract->ContractType == 3){
                $contractRates = explode(';', $roleRates[$index]);
                foreach ($contractRates as $rate) {
                    $contractRate = new ATWS\AutotaskObjects\ContractRate();
                    $info = explode('|', $rate);
                    $query1 = new ATWS\AutotaskObjects\Query('Role');
                    $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                    $queryField1->addExpression('Equals', $info[0]);
                    $query1->addField($queryField1);
                    $role = $client->query($query1);
                    $contractRate->RoleID = $role->queryResult->EntityResults->Entity->id;
                    $contractRate->ContractHourlyRate = $info[1];
                    $contractRate->id = 0;
                    $contractRate->ContractID = $createdContract->id;
                    $creation = $client->create($contractRate);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
            if($createdContract->ContractType == 7){
                $contractServices = explode(';', $recuringServices[$index]);
                foreach($contractServices as $service){
                    $contractService = new ATWS\AutotaskObjects\ContractServiceAdjustment();
                    $info = explode('|', $service);
                    $query1 = new ATWS\AutotaskObjects\Query('Service');
                    $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                    $queryField1->addExpression('Equals', $info[0]);
                    $query1->addField($queryField1);
                    $serviceEntity = $client->query($query1);
                    $contractService->ServiceID = $serviceEntity->queryResult->EntityResults->Entity->id;
                    $contractService->id = 0;
                    $contractService->ContractID = $createdContract->id;
                    $contractService->AdjustedUnitCost = $info[1];
                    $contractService->AdjustedUnitPrice = $info[2];
                    $contractService->UnitChange = $info[3];
                    $date = new DateTime('now', new DateTimeZone('America/New_York'));
                    $contractService->EffectiveDate = $date->format(DateTime::W3C);
                    removeEmpty($contractService);
                    $creation = $client->create($contractService);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
            if($createdContract->ContractType == 4){
                $contractFactors = explode(';', $blockHourRates[$index]);
                foreach($contractFactors as $factor){
                    $blockHourRate = new ATWS\AutotaskObjects\ContractFactor();
                    $info = explode('|', $factor);
                    $query1 = new ATWS\AutotaskObjects\Query('Role');
                    $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                    $queryField1->addExpression('Equals', $info[0]);
                    $query1->addField($queryField1);
                    $role = $client->query($query1);
                    $blockHourRate->RoleID = $role->queryResult->EntityResults->Entity->id;
                    $blockHourRate->id = 0;
                    $blockHourRate->ContractID = $createdContract->id;
                    $blockHourRate->BlockHourFactor = $info[1];
                    removeEmpty($blockHourRate);
                    $creation = $client->create($blockHourRate);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
                $contractBlocks = explode(';', $blockHourPurchases[$index]);
                foreach($contractBlocks as $block){
                    $blockHourPurchase = new ATWS\AutotaskObjects\ContractBlock();
                    $info = explode('|', $block);
                    $blockHourPurchase->ContractID = $createdContract->id;
                    $date = new DateTime('now', new DateTimeZone('America/New_York'));
                    $blockHourPurchase->DatePurchased = $date->format(DateTime::W3C);
                    $endDate = new DateTime($info[1], new DateTimeZone('America/New_York'));
                    $blockHourPurchase->EndDate = $endDate->format(DateTime::W3C);
                    $blockHourPurchase->HourlyRate = $info[3];
                    $blockHourPurchase->Hours = $info[2];
                    $blockHourPurchase->id = 0;
                    switch(strtolower($info[5])){
                        case "true":
                            $blockHourPurchase->IsPaid = 1;
                            break;
                        case "false":
                            $blockHourPurchase->IsPaid = 0;
                            break;
                        default:
                            echo "<h2>Error: A Block Hour Purchase billed field can only be true or false.</h2>";
                            exit(1);
                    }
                    $startDate = new DateTime($info[0], new DateTimeZone('America/New_York'));
                    $blockHourPurchase->StartDate = $startDate->format(DateTime::W3C);
                    switch(strtolower($info[4])){
                        case "true":
                            $blockHourPurchase->Status = 1;
                            break;
                        case "false":
                            $blockHourPurchase->Status = 0;
                            break;
                        default:
                            echo "<h2>Error: A Block Hour Purchase active field can only be true or false.</h2>";
                            exit(1);
                    }
                    removeEmpty($blockHourPurchase);
                    $creation = $client->create($blockHourPurchase);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
            if($createdContract->ContractType == 6){
                $contractRetainers = explode(';', $retainerPurchases[$index]);
                foreach($contractRetainers as $retainer){
                    $info = explode('|', $retainer);
                    $retainerPurchase = new ATWS\AutotaskObjects\ContractRetainer();
                    $retainerPurchase->ContractID = $createdContract->id;
                    $date = new DateTime('now', new DateTimeZone('America/New_York'));
                    $retainerPurchase->DatePurchased = $date->format(DateTime::W3C);
                    $endDate = new DateTime($info[1], new DateTimeZone('America/New_York'));
                    $retainerPurchase->EndDate = $endDate->format(DateTime::W3C);
                    $retainerPurchase->id = 0;
                    switch(strtolower($info[4])){
                        case "true":
                            $retainerPurchase->IsPaid = 1;
                            break;
                        case "false":
                            $retainerPurchase->IsPaid = 0;
                            break;
                        default:
                            echo "<h2>Error: A Retainer Purchase billed field can only be true or false.</h2>";
                            exit(1);
                    }
                    $startDate = new DateTime($info[0], new DateTimeZone('America/New_York'));
                    $retainerPurchase->StartDate = $startDate->format(DateTime::W3C);
                    switch(strtolower($info[3])){
                        case "true":
                            $retainerPurchase->Status = 1;
                            break;
                        case "false":
                            $retainerPurchase->Status = 0;
                            break;
                        default:
                            echo "<h2>Error: A Retainer Purchase active field can only be true or false.</h2>";
                            exit(1);
                    }
                    $retainerPurchase->Amount = $info[2];
                    removeEmpty($retainerPurchase);
                    $creation = $client->create($retainerPurchase);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
            if($createdContract->ContractType == 8){
                $contractTickets = explode(';', $ticketPurchases[$index]);
                foreach($contractTickets as $ticket){
                    $info = explode('|', $ticket);
                    $contractTicket = new ATWS\AutotaskObjects\ContractTicketPurchase();
                    $contractTicket->ContractID = $createdContract->id;
                    $date = new DateTime('now', new DateTimeZone('America/New_York'));
                    $contractTicket->DatePurchased = $date->format(DateTime::W3C);
                    $endDate = new DateTime($info[1], new DateTimeZone('America/New_York'));
                    $contractTicket->EndDate = $endDate->format(DateTime::W3C);
                    $contractTicket->id = 0;
                    switch(strtolower($info[5])){
                        case "true":
                            $contractTicket->IsPaid = 1;
                            break;
                        case "false":
                            $contractTicket->IsPaid = 0;
                            break;
                        default:
                            echo "<h2>Error: A Ticket Purchase billed field can only be true or false.</h2>";
                            exit(1);
                    }
                    $startDate = new DateTime($info[0], new DateTimeZone('America/New_York'));
                    $contractTicket->StartDate = $startDate->format(DateTime::W3C);
                    switch(strtolower($info[4])){
                        case "true":
                            $contractTicket->Status = 1;
                            break;
                        case "false":
                            $contractTicket->Status = 0;
                            break;
                        default:
                            echo "<h2>Error: A Ticket Purchase active field can only be true or false.</h2>";
                            exit(1);
                    }
                    $contractTicket->PerTicketRate = $info[3];
                    $contractTicket->TicketsPurchased = $info[2];
                    removeEmpty($contractTicket);
                    $creation = $client->create($contractTicket);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
            if($createdContract->ContractType == 3){
                $contractMilestones = explode(';', $milestones[$index]);
                foreach($contractMilestones as $miles){
                    $info = explode('|', $miles);
                    $contractMilestone = new ATWS\AutotaskObjects\ContractMilestone();
                    $contractMilestone->ContractID = $createdContract->id;
                    $dueDate = new DateTime($info[2], new DateTimeZone('America/New_York'));
                    $contractMilestone->DateDue = $dueDate->format(DateTime::W3C);
                    $contractMilestone->id = 0;
                    $contractMilestone->IsInitialPayment = false;
                    $contractMilestone->Title = $info[0];
                    $contractMilestone->Amount = $info[1];
                    $query1 = new ATWS\AutotaskObjects\Query('AllocationCode');
                    $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                    $queryField1->addExpression('Equals', $info[3]);
                    $query1->addField($queryField1);
                    $allocation = $client->query($query1);
                    $contractMilestone->AllocationCodeID = $allocation->queryResult->EntityResults->Entity->id;
                    switch(strtolower($info[4])){
                        case "in progress":
                            $contractMilestone->Status = 1;
                            break;
                        case "ready to bill":
                            $contractMilestone->Status = 2;
                            break;
                        case "billed":
                            $contractMilestone->Status = 3;
                            break;
                        default:
                            echo "<h2>Error: The Milestone Status is not one of the accepted values.</h2>";
                            exit(1);
                    }
                    removeEmpty($contractMilestone);
                    $creation = $client->create($contractMilestone);
                    if($creation->createResult->ReturnCode != 1){
                        print_r($creation->createResult->Errors);
                    }
                }
            }
        }
        $index++;
    }
}
function removeEmpty($item){
    foreach($item as $var => $value) {
//            echo $var . "<br/>";
        if($var == "UserDefinedFields") {
            foreach($item->UserDefinedFields as $var1 => $value1) {
                if(empty($value1)){
                    try {
                        unset($item->UserDefinedFields->{$var1});
                    } catch (Exception $e) {
                        echo 'Exception ' . $e;
                    }
                }
            }
        } elseif($var != "AccountID" && $var != "EstimatedHours" && $var != "EstimatedCost" && $var != "EstimatedRevenue" &&
            $var != "id" && $var != "IsPaid" && $var != "IsInitialPayment") {
            if (empty($value)) {
                try {
                    unset($item->{$var});
                } catch (Exception $e) {
                    echo 'Exception ' . $e;
                }
            }
        }
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