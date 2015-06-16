<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:13 PM
 */
session_start();
//print_r($_SESSION);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="logos-icons.png"/>
    <link rel="stylesheet" type="text/css" href="css/checkmark.css"/>
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
        function download(){
            window.open("download.php");
        }
    </script>
</head>
<body>
    <div class="wrapper">
        <img src="Autotask%20Logo.jpg"/>
        <div class="check">&#x2713;</div>
    </div>
    <form action="enter.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Enter your Autotask username and password:</legend>
            <input type="text" name="username" required><span class="error"> *</span>
            <input type="password" name="password" required><span class="error"> *</span>
            <input type="submit" value="Submit" name="submit">
        </fieldset>
    </form>

    <h2>Click below to download the import file template.</h2>
    <button type="button" onclick="download()">Download</button>
    <div id="link"></div>
</body>
</html>
