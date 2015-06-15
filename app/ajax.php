<?php
set_time_limit(0);
session_start();
//print_r($_SESSION);
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
    <script>
        function runBar(){
            $(function() {
                $( "#progressbar" ).progressbar({
                    value: false
                });
            });
        }
    </script>
</head>
<body onload="runBar()">
    <br/>
    <h2>Depending on how many contracts you are importing this may take a while, please wait.</h2>
    <br/>
    <div id="progressbar"></div>
    <div id="myDiv0"></div>
    <div id="myDiv1"></div>
    <div id="myDiv2"></div>
    <div id="myDiv3"></div>
    <div id="myDiv4"></div>
    <div id="myDiv5"></div>
    <div id="myDiv6"></div>
    <div id="myDiv7"></div>
    <div id="myDiv8"></div>
    <div id="myDiv9"></div>
    <div id="myDiv10"></div>
    <div id="myDiv11"></div>
    <div id="myDiv12"></div>
    <div id="myDiv13"></div>
    <div id="myDiv14"></div>
    <div id="myDiv15"></div>
    <div id="myDiv16"></div>
    <div id="myDiv17"></div>
    <div id="myDiv18"></div>
    <div id="myDiv19"></div>
    <div id="myDiv20"></div>
    <div id="myDiv21"></div>
    <div id="myDiv22"></div>
    <div id="myDiv23"></div>
    <div id="myDiv24"></div>
    <div id="myDiv25"></div>
    <div id="myDiv26"></div>
    <div id="myDiv27"></div>
    <div id="myDiv28"></div>
    <div id="myDiv29"></div>
    <div id="myDiv30"></div>
    <div id="myDiv31"></div>
    <div id="myDiv32"></div>
    <div id="myDiv33"></div>
    <div id="myDiv34"></div>
    <div id="myDiv35"></div>
    <div id="myDiv36"></div>
    <div id="myDiv37"></div>
    <div id="myDiv38"></div>
    <div id="myDiv39"></div>
    <div id="myDiv40"></div>
    <?php echo"
        <script>
        var index = 0;
        function check(){
            var lable = \"#myDiv\" + index;
                $(lable).load( \"process.php\", function( response, status, xhr ) {
                    if ( status == \"error\" ) {
                        var msg = \"Sorry but there was an error: \";
                        $(lable).html( msg + xhr.status + \" \" + xhr.statusText );
                        clearInterval(myVar);
                    }
                    else{
                    }
                });
                index++;
        }
        check();
        var myVar = setInterval(check, 25000);
    </script>";
    ?>
</body>
</html>