<?php
//download.php
//content type
header('Content-type: text/plain');
//open/save dialog box
header('Content-Disposition: attachment; filename="Contract Import Template File.xlsx"');
//read from server and write to buffer
readfile('Contract Import Template File.xlsx');