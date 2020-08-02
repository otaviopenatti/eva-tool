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

if (!isset($_SESSION['plugin_file']) || !isset($_SESSION['verifica'])) {

    echo "<html>\n<head>\n<title>Redirecting...</title>\n";
    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_descritor.php\"\">\n</head>\n</html>";
    session_destroy();

} else {

    if (!isset($_POST['nome']) || !isset($_POST['autor'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_descritor_metadados.php\"\">\n</head>\n</html>";
    } else {

        /*
        print "Dados enviados:<br/>";
        print "Sigla (id): ".substr($_SESSION['plugin_file'],0,strpos($_SESSION['plugin_file'], "."))."<br/>";
        print "Nome: ".$_POST["nome"]."<br/>";
        print "Autor: ".$_POST["autor"]."<br/>";
        */

        //file name without extension
        $id_descriptor = substr($_SESSION['plugin_file'],0,strpos($_SESSION['plugin_file'], "."));

        include "util.php";

        // Connecting, selecting database
        $dbconn = connect();

        // Performing SQL query
        $query = "INSERT INTO descriptor (id, name, author, type) VALUES ('$id_descriptor', '$_POST[nome]', '$_POST[autor]', '$_POST[tipo]')";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>
<html>
    <head>
        <title>Eva tool - Insert descriptor</title>
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

    <h1>Insert descriptor</h1>

        <center>
            Descriptor <b><?=$_POST['nome']?></b> successfully inserted!<br/>
            <a href="gerencia_descritor.php">Back to descriptors management</a>
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
}

?>
