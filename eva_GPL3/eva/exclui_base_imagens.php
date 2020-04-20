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

    For commencial use of Eva, please contact me.

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
        <title>Eva tool - Image databases management</title>
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

    <h1>Delete Image Database</h1>

<?
        // SQL para verificar se a base de imagens esta em uso em algum experimento
        $query =  "SELECT COUNT(idimagedatabase) FROM experimentimagedatabase WHERE idimagedatabase='$_GET[id]'";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        $line = pg_fetch_array($result, null, PGSQL_ASSOC);
        pg_free_result($result);
        if ($line['count'] != 0) {
            //base de imagens esta em uso, portanto nao pode ser apagada do bd
?>
            PROBLEMS DELETING THE DATABASE IMAGE <b><?=$_GET['id']?></b>:
            <blockquote>
            The image database is in use in <?=$line['count']?> experiments. To delete it, no experiment (running or completed) can be using the image database.<br/>
            </blockquote>
<?
        } else {
            //SQL para apagar a base de imagens do bd
            $query = "DELETE FROM imagedatabase WHERE id='$_GET[id]'";
            $result = pg_query($query) or die('Query failed: ' . pg_last_error());
            pg_free_result($result);
?>
            Image database <b><?=$_GET['id']?></b> successfully deleted!<br/>
<?
        }
?>
            <a href="gerencia_base_imagens.php">Back</a>


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
