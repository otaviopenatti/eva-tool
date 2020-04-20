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
session_start();

    if (!isset($_FILES['plugin_file']['name'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_descritor.php\"\">\n</head>\n</html>";

        // Finally, destroy the session.
        session_destroy();

    } else {
        $uploaddir = 'descriptors/';

        $nome_desc_size = strlen(substr($_FILES['plugin_file']['name'],0,strpos($_FILES['plugin_file']['name'],".")));

        //echo "tamanho nome desc=".$nome_desc_size."<br/>";
        //echo "dir=".$uploaddir.$_FILES['plugin_file']['name']."<br/>";
        if (file_exists($uploaddir.$_FILES['plugin_file']['name'])) {
            echo "Eva already has a descriptor with the same name!<br>";
            echo "<a href=\"cadastra_descritor.php\">Back</a>";
            exit(0);
        } else if ($nome_desc_size <= 20) {

            if (move_uploaded_file($_FILES['plugin_file']['tmp_name'], $uploaddir . $_FILES['plugin_file']['name'])) {
                //print "O arquivo &eacute; valido e foi carregado com sucesso. Aqui esta alguma informacao:\n";
                //print_r($_FILES);

                $destino = "cadastra_descritor_verifica.php";
                $_SESSION['plugin_file'] = $_FILES['plugin_file']['name'];

                //print "<br/><br/><br/><br/><br/><br/><br/><hr/>";
                //print_r($_SESSION);
                //print "<hr/><br/><br/>session_id=".session_id()."<br/><br/><br/><br/>";

                echo "<html>\n<head>\n<title>Redirecting...</title>\n";
                echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=$destino\"\">\n</head>\n</html>";

            } else {
                print "<pre>";
                print "Poss&iacute;vel ataque de upload! Aqui esta alguma informa&ccedil;&atilde;o:\n";
                print_r($_FILES);
                print "</pre>";
            }
        } else {
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

                <h1>Insert descriptor - Error</h1>

                    <center>
                        The maximun length of the plugin file name is 20!<br/>
                        <a href="cadastra_descritor.php">Back</a>
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
        }
    }
?> 
