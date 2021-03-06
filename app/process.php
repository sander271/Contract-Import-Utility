<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:47 PM
 */
//Gets access to the Autotask API classes.
require_once "../vendor/autoload.php";
error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);
session_start();
//print_r($_SESSION['index']);
//This is the fuction that parses the file and creates the contracts in the user's Autotask database.
function processFile()
{
    //creates a connection to the Autotask database associated with the username and password provided by the user.
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
    //The following lines break up the input file into separate lines and further breaks those lines up
    //into individual tokens. The lines are predetermined by the rows of the import template file. If there are any
    //changes in the import file template there must be corresponding changes made here.
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
    $recurringServices = explode(',', $lines[18]);
    $blockHourRates = explode(',', $lines[19]);
    $blockHourPurchases = explode(',', $lines[20]);
    $retainerPurchases = explode(',', $lines[21]);
    $ticketPurchases = explode(',', $lines[22]);
    $milestones = explode(',', $lines[23]);


    $_SESSION['contracts'] = $contractNames;
    $index = $_SESSION['index'] + 4;
    $actualAddress = $_SESSION['index'] + 5;
    $_SESSION['max'] = count($contractNames);
    $count = 0;
    //This is the loop that goes through each column and parses the data in that column. It then creates the contract and
    // other related contract content that was supplied in the input file.
    while($index < count($contractNames) && !empty($contractNames[$index]) && $count < 5){
        $contract = new ATWS\AutotaskObjects\Contract();
        $query = new ATWS\AutotaskObjects\Query('Account');
        $queryField = new ATWS\AutotaskObjects\QueryField('AccountName');
        $queryField2 = new ATWS\AutotaskObjects\QueryField('AccountName');
        if(stristr($accountNames[$index], "&") != false){
            $accountName = explode(' ', trim($accountNames[$index]));
            $queryField->addExpression('BeginsWith', $accountName[0]);
            $query->addField($queryField);
            $queryField2->addExpression('EndsWith', $accountName[count($accountName)-1]);
            $query->addField($queryField2);
        }
        else{
            $queryField->addExpression('Equals', trim($accountNames[$index]));
            $query->addField($queryField);
        }
        $account = $client->query($query);
        $accountID = $account->queryResult->EntityResults->Entity->id;
        $contract->AccountID = $accountID;
        $contract->ContractName = trim($contractNames[$index]);

        $query = new ATWS\AutotaskObjects\Query('Contract');
        $queryField = new ATWS\AutotaskObjects\QueryField('ContractName');
        $queryField->addExpression('Equals', $contract->ContractName);
        $queryField2 = new ATWS\AutotaskObjects\QueryField('AccountID');
        $queryField2->addExpression('Equals', $accountID);
        $query->addField($queryField);
        $query->addField($queryField2);
        $exist = $client->query($query);

        if (!strcmp($exist->queryResult->EntityResults->Entity->ContractName, $contract->ContractName)) {
            echo "<h2>Error: {$contract->ContractName} is a contract that already exists for the selected account.</h2>";
        }
        else{
            $allowedPeriodType = true;
            switch (strtolower(trim($contractTypes[$index]))) {
                case "time and materials":
                    $contract->ContractType = 1;
                    $allowedPeriodType = false;
                    break;
                case "recurring":
                    $contract->ContractType = 7;
                    $allowedPeriodType = true;
                    break;
                case "incident":
                    $contract->ContractType = 8;
                    break;
                case "block hour":
                    $contract->ContractType = 4;
                    $allowedPeriodType = false;
                    break;
                case "retainer":
                    $contract->ContractType = 6;
                    break;
                case "fixed price":
                    $contract->ContractType = 3;
                    break;
                default:
                    echo "<h2 class='error'>Error: Your Contract Type in column {$actualAddress} is not one of the accepted values.</h2>";
                    printExit();
                    exit(1);
                    break;
            }
            $contract->ContactName = trim($contactNames[$index]);
            switch (strtolower(trim($billingPreferences[$index]))) {
                case "immediately":
                    $contract->BillingPreference = 1;
                    break;
                case "reconcile":
                    $contract->BillingPreference = 2;
                    break;
                case "timesheet":
                    $contract->BillingPreference = 3;
                    break;
                default:
                    if($contract->ContractType == 1 || $contract->ContractType == 4 || $contract->ContractType == 6 ||
                        $contract->ContractType == 8){
                        echo "<h2 class='error'>Error: Your Billing Preference in column {$actualAddress} is not one of the accepted
 values.</h2>";
                        printExit();
                        exit(1);
                    }
                    else
                        break;
            }
            $contract->ContractNumber = trim($contractNumbers[$index]);
            if ($allowedPeriodType) {
                switch (strtolower(trim($contractPeriodTypes[$index]))) {
                    case "monthly":
                        $contract->ContractPeriodType = "m";
                        break;
                    case "quarterly":
                        $contract->ContractPeriodType = "q";
                        break;
                    case "semi-annual":
                        $contract->ContractPeriodType = "s";
                        break;
                    case "yearly":
                        $contract->ContractPeriodType = "y";
                        break;
                    default:
                        if($contract->ContractType == 7){
                            echo "<h2 class='error'>Error: Your Contract Period Type in column {$actualAddress} is not one of the
accepted values.</h2>";
                            printExit();
                            exit(1);
                        }
                        break;
                }
            }
            $contract->Description = $descriptions[$index];
            try{
                $endDate = new DateTime(trim($endDates[$index]), new DateTimeZone('America/New_York'));
                $contract->EndDate = $endDate->format(DateTime::W3C);
            }
            catch (Exception $e){
                switch($e->getCode()){
                    case 0:
                        echo "<h2 class='error'>Error: The End Date in column {$actualAddress} is formatted incorrectly.</h2>";
                        printExit();
                        exit(1);
                        break;
                    default:
                        echo "<h2 class='error'>Error: An unrecorded error has occured with the End Date in column {$actualAddress}.</h2>";
                        printExit();
                        exit(1);
                        break;
                }
            }
            switch (strtolower(trim($isDefaultContracts[$index]))) {
                case "yes":
                    $contract->IsDefaultContract = true;
                    break;
                case "no":
                    $contract->IsDefaultContract = false;
                    break;
                default:
                    $contract->IsDefaultContract = false;
                    break;
            }
            try{
                $startDate = new DateTime(trim($startDates[$index]), new DateTimeZone('America/New_York'));
                $contract->StartDate = $startDate->format(DateTime::W3C);
            }
            catch (Exception $e){
                switch($e->getCode()){
                    case 0:
                        echo "<h2 class='error'>Error: The Start Date in column {$actualAddress} is formatted incorrectly.</h2>";
                        printExit();
                        exit(1);
                        break;
                    default:
                        echo "<h2 class='error'>Error: An unrecorded error has occured with the Start Date in column {$actualAddress}.</h2>";
                        printExit();
                        exit(1);
                        break;
                }
            }
            if($contract->ContractType == 7){
                if(empty($setupFees[$index])){
                    $contract->SetupFee = 0;
                }
                else{
                    $contract->SetupFee = trim($setupFees[$index]);
                }
            }
            $number = 0;
            if (!is_numeric($timeReportingRequires[$index])) {
                if (strcmp(strtolower(trim($timeReportingRequires[$index])), "yes")) {
                    $number = 1;
                } else {
                    $number = 0;
                }
            }
            $contract->TimeReportingRequiresStartAndStopTimes = $number;
            $contract->Status = 1;
            if(empty($estimatedHours[$index])){
                $contract->EstimatedHours = 0;
            }
            else{
                $contract->EstimatedHours = trim($estimatedHours[$index]);
            }
            if(empty($estimatedCosts[$index])){
                $contract->EstimatedCost = 0;
            }
            else{
                $contract->EstimatedCost = trim($estimatedCosts[$index]);

            }
            if(empty($estimatedRevenue[$index])){
                $contract->EstimatedRevenue = 0;
            }
            else{
                $contract->EstimatedRevenue = trim($estimatedRevenue[$index]);
            }
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
            removeEmpty($contract);
            $contract->id = 0;
            $newContract = $client->create($contract);
            if($newContract->createResult->ReturnCode != 1){
//                print_r($newContract->createResult->Errors->ATWSError->Message);
                printExit();
                switch($newContract->createResult->Errors->ATWSError->Message){
                    case "Value does not exist for the required field AccountID. ; on record number [1].":
                        echo "<h2 class='error'>Error: The Customer Name field is not correct. Check column number {$actualAddress}.</h2>";
                        exit(1);
                        break;
                    case "Contract EndDate must be after StartDate.":
                        echo "<h2 class='error'>Error: Contract EndDate must be after StartDate in column {$actualAddress}.</h2>";
                        exit(1);
                        break;
                    case "Conversion from type 'Object' to type 'String' is not valid.":
                        echo "<h2 class='error'>Error: An error has occurred in column {$actualAddress}, check to make sure that all required
fields have been completed and the type of information in each field is correct.</h2>";
                        exit(1);
                        break;
                    default:
                        echo "<h2 class='error'>Error: An unrecorded error has occurred in column {$actualAddress}, check
to make sure you have filled out all required information correctly.</h2>";
                        exit(1);
                        break;
                }
            }
            else{
                echo "<h2>{$newContract->createResult->EntityResults->Entity->ContractName} was created successfully.</h2>";
                ob_flush();
                flush();
                $createdContract = $newContract->createResult->EntityResults->Entity;
                if(($createdContract->ContractType == 1 || $createdContract->ContractType == 6 ||
                        $createdContract->ContractType == 3) && !empty($roleRates[$index])){
                    $contractRates = explode(';', trim($roleRates[$index]));
                    foreach ($contractRates as $rate) {
                        $contractRate = new ATWS\AutotaskObjects\ContractRate();
                        $info = explode('|', trim($rate));

                        $query1 = new ATWS\AutotaskObjects\Query('Role');
                        $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                        $queryField1->addExpression('Equals', trim($info[0]));
                        $query1->addField($queryField1);
                        $role = $client->query($query1);

                        $contractRate->RoleID = $role->queryResult->EntityResults->Entity->id;
                        $contractRate->ContractHourlyRate = trim($info[1]);
                        $contractRate->id = 0;
                        $contractRate->ContractID = $createdContract->id;
                        $creation = $client->create($contractRate);
                        if($creation->createResult->ReturnCode != 1){
//                            print_r($creation->createResult->Errors->ATWSError->Message);
                            printExit();
                            switch($creation->createResult->Errors->ATWSError->Message){
                                case "Value does not exist for the required field RoleID. ; on record number [1].":
                                    echo "<h2 class='error'>Error: The Role Name in column {$actualAddress} is not an existing Role Name.
To fix this you must first delete the {$contract->ContractName} contract from Autotask that you were trying to add this to,
 and then reload the import file with a correct Role Name.</h2>";
                                    printExit();
                                    exit(1);
                                    break;
                                case "Can not convert data to numeric in field: ContractHourlyRate. ; on record number [1].":
                                    echo "<h2 class='error'>Error: The Contract Hourly Billing Rate in column {$actualAddress} is incorrect.
Make sure it is a numeric value. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Contract Hourly Billing Rate.</h2>";
                                    printExit();
                                    exit(1);
                                    break;
                                case "Conversion from type 'Object' to type 'String' is not valid.":
                                    echo "<h2 class='error'>Error: There is a mistake with the Role Rate in column
{$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask,then reload
 the import file with a correct Role Rate.</h2>";
                                    printExit();
                                    exit(1);
                                    break;
                                default:
                                    echo "<h2 class='error'>Error: An unrecorded error has occurred with the Role Rate
in column {$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Role Rate.</h2>";
                                    printExit();
                                    exit(1);
                                    break;
                            }
                        }
                    }
                }
                if($createdContract->ContractType == 7){
                    if(!empty($recurringServices[$index])){
                        $contractServices = explode(';', trim($recurringServices[$index]));
                        foreach($contractServices as $service){
                            $contractService = new ATWS\AutotaskObjects\ContractServiceAdjustment();
                            $info = explode('|', trim($service));

                            $query1 = new ATWS\AutotaskObjects\Query('Service');
                            $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                            $queryField1->addExpression('Equals', trim($info[0]));
                            $query1->addField($queryField1);
                            $serviceEntity = $client->query($query1);

                            $contractService->ServiceID = $serviceEntity->queryResult->EntityResults->Entity->id;
                            $contractService->id = 0;
                            $contractService->ContractID = $createdContract->id;
                            $contractService->AdjustedUnitCost = trim($info[1]);
                            $contractService->AdjustedUnitPrice = trim($info[2]);
                            $contractService->UnitChange = trim($info[3]);
                            $date = new DateTime('now', new DateTimeZone('America/New_York'));
                            $contractService->EffectiveDate = $date->format(DateTime::W3C);
                            removeEmpty($contractService);
                            $creation = $client->create($contractService);
                            if($creation->createResult->ReturnCode != 1){
//                                print_r($creation);
                                printExit();
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    case "Value does not exist for the required field ServiceID. ; on record number [1].":
                                        echo "<h2 class='error'>Error: The Service Name for the Recurring Service in column {$actualAddress} is
 not an existing service. To fix this you must first delete the {$contract->ContractName} contract from Autotask
  and then reload the import file with a correct Service Name.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                    case "ContractServiceAdjustment must have a value for at least one of the following: UnitChange, AdjustedUnitPrice, or AdjustedUnitCost.":
                                        echo "<h2 class='error'>Error: For the Recurring Service in column {$actualAddress}, you must
have a value for at least one of the following: Unit Cost, Unit Price, Units. To fix this you must first delete
 the {$contract->ContractName} contract from Autotask and then reload the import file with a correct Recurring Service.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Recurring
Services in cloumn {$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Recurring Service.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                }
                if($createdContract->ContractType == 4){
                    if(!empty($blockHourRates[$index])){
                        $contractFactors = explode(';', trim($blockHourRates[$index]));
                        foreach($contractFactors as $factor){
                            $blockHourRate = new ATWS\AutotaskObjects\ContractFactor();
                            $info = explode('|', trim($factor));

                            $query1 = new ATWS\AutotaskObjects\Query('Role');
                            $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                            $queryField1->addExpression('Equals', trim($info[0]));
                            $query1->addField($queryField1);
                            $role = $client->query($query1);

                            $blockHourRate->RoleID = $role->queryResult->EntityResults->Entity->id;
                            $blockHourRate->id = 0;
                            $blockHourRate->ContractID = $createdContract->id;
                            $blockHourRate->BlockHourFactor = trim($info[1]);
                            removeEmpty($blockHourRate);
                            $creation = $client->create($blockHourRate);
                            if($creation->createResult->ReturnCode != 1){
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    case "Value does not exist for the required field RoleID. ; on record number [1].":
                                        echo "<h2 class='error'>Error: The Role Name for the Block Hour Rates in column {$actualAddress}
does not exist. To fix this you must first delete the {$contract->ContractName} contract from Autotask and then
reload the import file with a correct Role Name in the Block Hour Rates.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                    case "Missing Required Field: BlockHourFactor. ; on record number [1].":
                                        echo "<h2 class='error'>Error: You are missing a Contract Block Hour Multiplier for the Block Hour
Rates in column {$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask
 and then reload the import file with a correct Contract Block Hour Multiplier in the Block Hour Rates.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                    case "Can not convert data to numeric in field: BlockHourFactor. ; on record number [1].":
                                        echo "<h2 class='error'>Error: The Contract Block Hour Multiplier for the Block Hour Rates in column
{$actualAddress} must be a numeric value. To fix this you must first delete the {$contract->ContractName} contract from
 Autotask and then reload the import file with a correct Contract Block Hour Multiplier in the Block Hour Rates.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Block
Hour Rates in column {$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Block Hour Rate.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                    if(!empty($blockHourPurchases[$index])){
                        $contractBlocks = explode(';', trim($blockHourPurchases[$index]));
                        foreach($contractBlocks as $block){
                            $blockHourPurchase = new ATWS\AutotaskObjects\ContractBlock();
                            $info = explode('|', trim($block));
                            $blockHourPurchase->ContractID = $createdContract->id;
                            $date = new DateTime('now', new DateTimeZone('America/New_York'));
                            $blockHourPurchase->DatePurchased = $date->format(DateTime::W3C);
                            $endDate = new DateTime(trim($info[1]), new DateTimeZone('America/New_York'));
                            $blockHourPurchase->EndDate = $endDate->format(DateTime::W3C);
                            $blockHourPurchase->HourlyRate = trim($info[3]);
                            $blockHourPurchase->Hours = trim($info[2]);
                            $blockHourPurchase->id = 0;
                            $blockHourPurchase->IsPaid = 0;
                            $startDate = new DateTime(trim($info[0]), new DateTimeZone('America/New_York'));
                            $blockHourPurchase->StartDate = $startDate->format(DateTime::W3C);
                            switch(strtolower(trim($info[4]))){
                                case "true":
                                    $blockHourPurchase->Status = 1;
                                    break;
                                case "false":
                                    $blockHourPurchase->Status = 0;
                                    break;
                                default:
                                    echo "<h2 class='error'>Error: The Block Hour Purchase active field in {$actualAddress} can only be
 true or false. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Block Hour Purchase active field.</h2>";
                                    printExit();
                                    exit(1);
                            }
                            removeEmpty($blockHourPurchase);
                            $creation = $client->create($blockHourPurchase);
                            if($creation->createResult->ReturnCode != 1){
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                printExit();
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    case "Missing Required Field: HourlyRate. ; on record number [1].":
                                        echo "<h2 class='error'>Error: You are missing a Hourly Rate in the Block Hour Purchases in column
{$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask and then reload
 the import file with a correct Hourly Rate in the Block Hour Purchases.</h2>";
                                        exit(1);
                                        break;
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Block
Hour Purchases in column {$actualAddress}. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Block Hour Purchase.</h2>";
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                }
                if($createdContract->ContractType == 6){
                    if(!empty($retainerPurchases[$index])){
                        $contractRetainers = explode(';', trim($retainerPurchases[$index]));
                        foreach($contractRetainers as $retainer){
                            $info = explode('|', trim($retainer));
                            $retainerPurchase = new ATWS\AutotaskObjects\ContractRetainer();
                            $retainerPurchase->ContractID = $createdContract->id;
                            $date = new DateTime('now', new DateTimeZone('America/New_York'));
                            $retainerPurchase->DatePurchased = $date->format(DateTime::W3C);
                            $endDate = new DateTime(trim($info[1]), new DateTimeZone('America/New_York'));
                            $retainerPurchase->EndDate = $endDate->format(DateTime::W3C);
                            $retainerPurchase->id = 0;
                            $retainerPurchase->IsPaid = 0;
                            $startDate = new DateTime(trim($info[0]), new DateTimeZone('America/New_York'));
                            $retainerPurchase->StartDate = $startDate->format(DateTime::W3C);
                            switch(strtolower(trim($info[3]))){
                                case "true":
                                    $retainerPurchase->Status = 1;
                                    break;
                                case "false":
                                    $retainerPurchase->Status = 0;
                                    break;
                                default:
                                    echo "<h2 class='error'>Error: A Retainer Purchase active field can only be true or false. To fix this you must first delete the {$contract->ContractName} contract from Autotask,
then reload the import file with a correct Retainer Purchase active field.</h2>";
                                    printExit();
                                    exit(1);
                            }
                            $retainerPurchase->Amount = trim($info[2]);
                            removeEmpty($retainerPurchase);
                            $creation = $client->create($retainerPurchase);
                            if($creation->createResult->ReturnCode != 1){
                                printExit();
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Retainer Purchases
in column {$actualAddress}.</h2>";
                                        printExit();
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                }
                if($createdContract->ContractType == 8){
                    if(!empty($ticketPurchases[$index])){
                        $contractTickets = explode(';', trim($ticketPurchases[$index]));
                        foreach($contractTickets as $ticket){
                            $info = explode('|', trim($ticket));
                            $contractTicket = new ATWS\AutotaskObjects\ContractTicketPurchase();
                            $contractTicket->ContractID = $createdContract->id;
                            $date = new DateTime('now', new DateTimeZone('America/New_York'));
                            $contractTicket->DatePurchased = $date->format(DateTime::W3C);
                            $endDate = new DateTime(trim($info[1]), new DateTimeZone('America/New_York'));
                            $contractTicket->EndDate = $endDate->format(DateTime::W3C);
                            $contractTicket->id = 0;
                            $contractTicket->IsPaid = 0;
                            $startDate = new DateTime(trim($info[0]), new DateTimeZone('America/New_York'));
                            $contractTicket->StartDate = $startDate->format(DateTime::W3C);
                            switch(strtolower(trim($info[4]))){
                                case "true":
                                    $contractTicket->Status = 1;
                                    break;
                                case "false":
                                    $contractTicket->Status = 0;
                                    break;
                                default:
                                    echo "<h2 class='error'>Error: A Ticket Purchase active field can only be true or false.</h2>";
                                    printExit();
                                    exit(1);
                            }
                            $contractTicket->PerTicketRate = trim($info[3]);
                            $contractTicket->TicketsPurchased = trim($info[2]);
                            removeEmpty($contractTicket);
                            $creation = $client->create($contractTicket);
                            if($creation->createResult->ReturnCode != 1){
                                printExit();
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Ticket
Purchases in column {$actualAddress}.</h2>";
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                }
                if($createdContract->ContractType == 3){
                    if(!empty($milestones[$index])){
                        $contractMilestones = explode(';', trim($milestones[$index]));
                        foreach($contractMilestones as $miles){
                            $info = explode('|', trim($miles));
                            $contractMilestone = new ATWS\AutotaskObjects\ContractMilestone();
                            $contractMilestone->ContractID = $createdContract->id;
                            $dueDate = new DateTime(trim($info[2]), new DateTimeZone('America/New_York'));
                            $contractMilestone->DateDue = $dueDate->format(DateTime::W3C);
                            $contractMilestone->id = 0;
                            $contractMilestone->IsInitialPayment = false;
                            $contractMilestone->Title = trim($info[0]);
                            $contractMilestone->Amount = trim($info[1]);

                            $query1 = new ATWS\AutotaskObjects\Query('AllocationCode');
                            $queryField1 = new ATWS\AutotaskObjects\QueryField('Name');
                            $queryField1->addExpression('Equals', trim($info[3]));
                            $query1->addField($queryField1);
                            $allocation = $client->query($query1);

                            $contractMilestone->AllocationCodeID = $allocation->queryResult->EntityResults->Entity->id;
                            switch(strtolower(trim($info[4]))){
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
                                    echo "<h2 class='error'>Error: The Milestone Status is not one of the accepted values.</h2>";
                                    printExit();
                                    exit(1);
                            }
                            removeEmpty($contractMilestone);
                            $creation = $client->create($contractMilestone);
                            if($creation->createResult->ReturnCode != 1){
                                printExit();
//                                print_r($creation->createResult->Errors->ATWSError->Message);
                                switch($creation->createResult->Errors->ATWSError->Message){
                                    default:
                                        echo "<h2 class='error'>Error: An unrecorded error has occurred with the Milestones
in column {$actualAddress}.</h2>";
                                        exit(1);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        $_SESSION['index'] = $_SESSION['index'] + 1;
        $count++;
        $index++;
        $actualAddress++;
        }
}
function removeEmpty($item){
    foreach($item as $var => $value) {
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
            $var != "id" && $var != "IsPaid" && $var != "IsInitialPayment" && $var != "SetupFee" &&
            $var != "TimeReportingRequiresStartAndStopTimes") {
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
function printExit(){
    echo
    "<script>
            clearInterval(myVar);
            $(\"#progressbar\").hide();
            $(\"#end\").replaceWith(\"<h1>Import Ended</h1>\");
        </script>";
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
            width: 100%;
        }
        form{
            padding-top: 3em;
        }
        .error{
            color: red;
        }
    </style>
    <?php
    if(empty($_SESSION['contracts'][$_SESSION['index'] + 5])){
        printExit();
    }
    ?>
</head>
<body>
    <br id="boom"/>
    <?php
    processFile();
    ?>
</body>
</html>