<?php
/**
 * Created by PhpStorm.
 * User: asanders
 * Date: 6/2/2015
 * Time: 2:13 PM
 */
session_start();
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
</head>
<body>
    <img src="Autotask%20Logo.jpg"/>
    <form action="enter.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Enter your Autotask username and password:</legend>
            <input type="text" name="username" required><span class="error"> *</span>
            <input type="password" name="password" required><span class="error"> *</span>
            <input type="submit" value="Submit" name="submit">
        </fieldset>
    </form>
</body>
</html>
