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

    if (!isset($_GET['id'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=gerencia_descritor.php\"\">\n</head>\n</html>";
    } else {

        include "util.php";

        // Connecting, selecting database
        $dbconn = connect();

?>
<html>
    <head>
        <title>Eva tool - Descriptors Management</title>
        <link rel="stylesheet" type="text/css" href="liquidcorners.css">
        <link rel="SHORTCUT ICON" href="favicon.ico"/>
        <link href="estilo.css" rel="stylesheet" />
    </head>
<body>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <h1>Delete descriptor</h1>

<?
       
        // SQL to check if the descriptor is in use in any experiment
        $query =  "SELECT COUNT(iddescriptor) FROM experimentdescriptor WHERE iddescriptor='$_GET[id]'";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        $line = pg_fetch_array($result, null, PGSQL_ASSOC);
        pg_free_result($result);
        if ($line['count'] != 0) {
            //descriptor is in use in some experiment, therefore it cannot be deleted
?>
            PROBLEMS DELETING DESCRIPTOR <b><?=$_GET['id']?></b>: 
            <blockquote>
            The descriptor is in use in <?=$line['count']?> experiments. To delete it, no experiment (running or completed) can be using the descriptor.<br/>
            </blockquote>
<?
        } else {

            // SQL to delete the descriptor
            $query = "DELETE FROM descriptor WHERE id='$_GET[id]'";
            $result = pg_query($query) or die('Query failed: ' . pg_last_error());
            pg_free_result($result);

            //delete descriptor file
            unlink("descriptors/$_GET[id].so");
?>
            Descriptor <b><?=$_GET['id']?></b> successfully deleted!<br/>
<?
        }
?>
            <a href="gerencia_descritor.php">Back</a>
        

    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    </div>
    <!--************************ BORDAS ARREDONDADAS! ************************-->

</body>
</html>
<?
        // Closing connection
        pg_close($dbconn);
    }
?>
