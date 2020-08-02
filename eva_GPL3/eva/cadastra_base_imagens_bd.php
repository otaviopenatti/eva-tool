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

    if (!isset($_POST['nome']) || !isset($_POST['path']) || !isset($_POST['descr'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_base_imagens.php\"\">\n</head>\n</html>";
    } else {
        /*
        print "Dados enviados:<br/>";
        //print "Sigla ou (id): ".$_POST["id"]."<br/>";
        print "Nome: ".$_POST["nome"]."<br/>";
        print "Path: ".$_POST["path"]."<br/>";
        print "Descricao: ".$_POST["descr"]."<br/>";
        */

        include "util.php";

        // Connecting, selecting database
        $dbconn = connect();

        // Performing SQL query
        if ($_POST['classificada'] == 0)
            $classificada="false";
        else
            $classificada="true";
        $query = "INSERT INTO imagedatabase (name, path, descr, classified) VALUES ('$_POST[nome]', '$_POST[path]', '$_POST[descr]', ".$classificada.")";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>
<html>
    <head>
        <title>Eva tool - Insert image database</title>
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

    <h1>Insert image database</h1>

        <center>
            Image database <b><?=$_POST['nome']?></b> successfully inserted!<br/>
            <a href="gerencia_base_imagens.php">Back to image databases management</a>
        </center><br/>

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

        session_destroy();

    }

?>
