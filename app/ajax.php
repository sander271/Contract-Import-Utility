<?php
session_start();
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
            echo "<h1>The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.</h1>";
            ob_flush();
            flush();
            $_SESSION['filename'] = $_FILES["fileToUpload"]["name"];
            $success = true;
        } else {
            echo "Sorry, there was an error uploading your file.";
            $success = false;
        }
    }
}
uploadFile();
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
        function loadXMLDoc()
        {
            var xmlhttp;
            if (window.XMLHttpRequest)
            {// code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp=new XMLHttpRequest();
            }
            xmlhttp.onreadystatechange=function()
            {
                if (xmlhttp.readyState==4 && xmlhttp.status==200)
                {
                    document.getElementById("myDiv").innerHTML=xmlhttp.responseText;
                }
            }
            xmlhttp.open("GET","process.php",true);
            xmlhttp.send();
        }
    </script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="//code.jquery.com/jquery-1.10.2.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <link rel="stylesheet" href="/resources/demos/style.css">
    <script>
        function runBar(){
            loadXMLDoc();
            $(function() {
                $( "#progressbar" ).progressbar({
                    value: false
                });
            });
        }
    </script>
    <script>
        function killBar(){
            $(function() {
                $( "#progressbar" ).progressbar({
                    disabled: true
                });
            });
        }
    </script>
</head >
<body onload="runBar()">
    <div onload="runBar()" id="progressbar"></div>
    <div onload="killBar()" id="myDiv">

    </div>
</body>
</html>