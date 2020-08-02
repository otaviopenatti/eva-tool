<!--
    This file is part of Eva tool.

    Eva is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Eva is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Eva. If not, see <http://www.gnu.org/licenses/>.

    For commercial use of Eva, please contact me.

    COPYRIGHT 2010-2013 - Otavio A. B. Penatti - otavio_at_penatti_dot_com
-->

<?php
    session_start();
    session_unset();  //delete previous session values

    include "../util.php";
    $dbconn = connect();

    $id_experiment = NN; //experiment id for the user evaluation

?>
<html>
<head>
  <title>Eva tool - User-oriented evaluation</title>
  <link rel="stylesheet" type="text/css" href="../liquidcorners.css">
  <link rel="SHORTCUT ICON" href="favicon.ico"/>
  <link href="../estilo.css" rel="stylesheet" />
  <link href="estilo.css" rel="stylesheet" />

    <script language="Javascript">
        function checkMail(email) {
            var x = email;
            var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (filter.test(x)) 
                return true;
            else
                return false;
        }

        function Valida() {
            if (!checkMail(document.aval.email.value)) {
                alert("Invalid e-mail!");
                return false;
            }
            return true;
        }
    </script>

</head>
<body>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->

        <center>
            <h1>User-oriented evaluation</h1>
        </center>
            <br/>
            <h4>Hello! Before starting the evaluation, please read the instructions below:</h4>
            <ul>
                <li>In the following pages there will be several images. The image in the top of the page is the query image. 
                The images below are images from our database</li>
                <li>You must mark among the database images, which ones you consider similar to the query image. 
                To mark one image, just click on it. The marked images will be highlighted with a green background. If you change your mind, click again on the marked image and the selection will be undone</li>
                <li>After marking the images, click on the button in the bottom of the page. You will be redirected to evaluate the next query image</li>
                <li>The process repeats until all the query images are evaluated</li>
                <li><b>Never use the "Back" button of your browser</b></li>
                <li><b>Never use the "Refresh" button of your browser (neither F5 key)</b></li>
            </ul>

        <center>
            To start the evaluation, type your e-mail address in the field below and click on the START button.<br/><br/>

            <form method="post" name="aval" action="view_images_feedback.php">
            <input type="hidden" name="exp" value="<?=$id_experiment?>"/>
            E-mail: <input type="text" name="email" size="60" maxlength="50"/>
<?
            $query = "SELECT iddescriptor FROM experimentdescriptor WHERE idexperiment=".$id_experiment;
            $result = pg_query($query) or die('Query failed: ' . pg_last_error());
            $i=0;
            while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                echo "\t\t<input type=\"hidden\" name=\"desc".$i."\" value=\"".$line['iddescriptor']."\"/>\n";
                $i++;
            }
            echo "\t\t<br/><input type=\"submit\" value=\"START\"/>";
            pg_free_result($result);
?>

        </center><br/>


    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    </div>
    <!--************************ BORDAS ARREDONDADAS! ************************-->
<?
    // Closing connection
    pg_close($dbconn);
?>

</body>
</html>
