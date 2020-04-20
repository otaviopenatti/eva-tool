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

if (!isset($_SESSION['plugin_file']) || !isset($_SESSION['verifica'])) {
    echo "<html>\n<head>\n<title>Redirecting...</title>\n";
    echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=cadastra_descritor.php\"\">\n</head>\n</html>";

    session_destroy();

} else {

?>
<html>
    <head>
        <title>Eva tool - Insert descriptor</title>

    <link href="estilo.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>

    <script language="JavaScript">
        function Valida() {
            if (document.form_metadados.nome.value == '' || document.form_metadados.autor.value == '') {
                window.alert('All fields are mandatory!');
                return false;
            }
            return true;
        }

        function swap( id ) {
            var browser = navigator.appName;
            if (browser == "Microsoft Internet Explorer") {
                displayType = ( document.getElementById( id ).style.display == 'none' ) ? 'block' : 'none';
            } else {
                displayType = ( document.getElementById( id ).style.display == 'none' ) ? 'table-row' : 'none';
            }
            document.getElementById( id ).style.display = displayType;

            id_titulo = id + '_link';
            classe = ( document.getElementById( id_titulo ).className == 'abstract_inactive' ) ? 'abstract_active' : 'abstract_inactive';
            document.getElementById( id_titulo ).className = classe;
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


        <h1>Insert descriptor</h1>

        <h3>File sent: <?=$_SESSION['plugin_file']?></h3>

        Descriptor's information:<br/>

        <form enctype="multipart/form-data" action="cadastra_descritor_metadados_bd.php" name="form_metadados" method="POST" onSubmit="return Valida()">
        <table width="60%" align="center" cellspacing="1" class="cadastro">
            <tr>
                <td width="30%">id:</td>
                <td><?=substr($_SESSION['plugin_file'],0,strpos($_SESSION['plugin_file'], "."))?><br/>
                    <i class="explica">the id is based on the file name of the uploaded file</i><br/>
                    <a class="explica" id="explica_sigla_link" href="javascript:swap('explica_sigla')"><b>?</b></a>
                    <i class="explica" id="explica_sigla" style="display:none">
                        The id is based on the file name of the uploaded file because Eva's internal functions use this file in the extraction and distance functions.
                    </i>
                </td>
            </tr>
            <tr>
                <td>Name:</td>
                <td><input type="text" name="nome" size="60" maxlength="60"/></td>
            </tr>
            <tr>
                <td>Author:</td>
                <td><input type="text" name="autor" size="60" maxlength="255"/></td>
            </tr>
            <tr>
                <td>Type:</td>
                <td>
                    <select name="tipo">
                        <option value="0">Color</option>
                        <option value="1">Texture</option>
                        <option value="2">Shape</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2"><input type="submit" value="Send"/></td>
            </tr>
        </table>
        </form>
        <br/>

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
