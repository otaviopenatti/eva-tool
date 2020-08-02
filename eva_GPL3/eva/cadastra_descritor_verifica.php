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

if (!isset($_SESSION['plugin_file'])) {
    echo "<html>\n<head>\n<title>Redirecting...</title>\n";
    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_descritor.php\"\">\n</head>\n</html>";

    session_destroy();

} else {


    include "util.php";

    // Connecting, selecting database
    $dbconn = connect();

    // Performing SQL query
    $query = "SELECT id FROM descriptor WHERE id='".substr($_SESSION['plugin_file'],0,strpos($_SESSION['plugin_file'], "."))."'";
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>
<html>
    <head>
        <title>Eva tool - Insert descriptor</title>

    <link href="estilo.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>

    </head>

<body>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->


        <h1>Insert descriptor</h1>

<?
    $nomeArqDescritor = $_SESSION['plugin_file'];

    if ($line = pg_fetch_array($result, null, PGSQL_ASSOC) || file_exists($nomeArqDescritor)) {
        echo "<center>Eva already has a descriptor with the same name!<br/>";
        echo "<a href=cadastra_descritor.php>Back</a></center><br/>";
    } else {
?>

        <h3>file sent: <?=$_SESSION['plugin_file']?></h3>

        Testing plugin with two images:<br/>

        <table width="60%" height="300" align="center">
            <tr>
                <td width="50%"><img src="images/house_bin.jpg"/><br/>house_bin.ppm</td>
                <td width="50%"><img src="images/car.jpg"/><br/>car.ppm</td>
            </tr>
        </table>
        <br/>
<?
    $filename = "/tmp/resultado_verifica_".$_SESSION['plugin_file'].".txt";
    $comando = "python verifica/verifica.py ".$_SESSION['plugin_file']." 2>&1 | cat";
    exec($comando, $output, $status);

    if (file_exists($filename)) {

        $handle = fopen($filename, "r");
        $status = fread($handle, filesize($filename));
        fclose($handle);
        unlink($filename);

    } else {
        $status = 8; //error in plugin cause python to abort
    }
    ?>
    Plugin verification:
    <table class="code" width="95%" align="center">
       <tr>
            <!--return_var = $status?<br/> -->
            <?
            if ($status != 0) {
                print "<td class=\"erro\"><b>ERROR! See log below!</b></td>";
            } else {
                print "<td class=\"sucesso\"><b>Verification finished successfully!</b></td>";
            }
            ?>
        </tr>
       <tr>
       <td><b>Verification log:</b><br/>

    <?
    //echo "<pre>";
    if ($status == 8) { ?>
        Possible plugin errors:<br/>
        <ul>
            <li>Segmentation fault</li>
            <li>Problem reading or writing files</li>
            <li>Problem alocating or freeing memory</li>
        </ul>
    <?
    } else {
        foreach ($output as $linha) {
            echo $linha."<br/>\n";
        }
    }
    //echo "</pre>\n";

    ?>
            </td>
           </tr>
        </table>

    <br/>
    <br/>
    <center>
        <b>
    <?
    if ($status != 0) {
    ?>
        <a href="cadastra_descritor.php">Read the instructions again and send a new version.</a>
    <?
        //delete uploaded file
        unlink("descriptors/$_SESSION[plugin_file]");

    } else {
    ?>
        <a href="cadastra_descritor_metadados.php">Next step</a>
    <?
        $_SESSION['verifica'] = 1; //verification is OK
    }
    ?>
        </b>
    </center>

<?
    }
    // Free resultset
    pg_free_result($result);

    // Closing connection
    pg_close($dbconn);

?>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <br/>

</body>

</html>

<?
}
?>
