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

?>
<html>
    <head>
        <title>Eva tool - Insert image database</title>

    <link href="estilo.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>

    <script language="JavaScript">
        function Valida() {
            if (document.form_metadados.nome.value == '' || document.form_metadados.path.value == '' || document.form_metadados.descr.value == '') {
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


        <h1>Insert image database</h1>

        Image database details:<br/>

        <form enctype="multipart/form-data" action="cadastra_base_imagens_bd.php" name="form_metadados" method="POST" onSubmit="return Valida()">
        <table width="800" align="center" cellspacing="1" class="cadastro">
<!--
            <tr>
                <td width="30%">Sigla (ou ID):</td>
                <td><input type="text" name="id" size="20" maxlength="20"/>
                    <a class="explica" id="explica_sigla_link" href="javascript:swap('explica_sigla')"><b>?</b></a>
                    <i class="explica" id="explica_sigla" style="display:none">
                        Ser&aacute; o identificador principal da base no sistema de arquivos. A ferramenta usar&aacute; este id para gerar os arquivos de 
                        caracter&iacute;sticas dos descritores.
                    </i>
                </td>
            </tr>
-->
            <tr>
                <td width="20%">Name:</td>
                <td><input type="text" name="nome" size="60" maxlength="45"/></td>
            </tr>
            <tr>
                <td valign="top"><b>Full</b> path in the filesystem:</td>
                <td>
                    <b><ul>
                       <li>Use the path that passes through the <i>img_databases</i> directory, which is inside the Eva tool in the Web server. If you do not want to create a copy of your database, use symbolic links;</li>
                            <a class="explica" id="explica_sigla_link" href="javascript:swap('explica_sigla')"><b>?</b></a>
                            <i class="explica" id="explica_sigla" style="display:none">
                                Eva works even if the path does not passes through the img_databases directory. However, in this case the images cannot be showed by the web browser. Therefore, it is better to use the path the passes through the img_databases directory.<br/><br/>
                                Example: if your image collection is at /home/user/bases/base1/, create a symbolic link inside the img_databases directory that points to your collection. Then, insert here the path /var/www/eva/img_databases/base1/. (supposing that Eva is at /var/www/)
                            </i>
                       <li>Include the final slash ('/').</li>
                    </ul></b>
                    <input type="text" name="path" size="60" maxlength="255"/>
                </td>
            </tr>
            <tr>
                <td>Description:</td>
                <td><input type="text" name="descr" size="60" maxlength="255"/></td>
            </tr>
            <tr>
                <td>Categorized?</td>
                <td>
                    <select name="classificada">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                            <a class="explica" id="explica_category_link" href="javascript:swap('explica_category')"><b>?</b></a>
                            <i class="explica" id="explica_category" style="display:none">
                                ps.: each category must correspond to one subdirectory into the database directory. For example, a database 
                                with 3 classes must have 3 subdirectories inside of its root directory: class01 containing images from category 1, 
                                class02 containing images from category 2, and class03 containing images from category 3. The name of the 
                                subdirectories does not necessarily have to be numbers.
                            </i>
                    
                </td>
            </tr>

            <tr>
                <td colspan="2"><input type="submit" value="Send"/></td>
            </tr>
        </table>
        </form>

        <hr size="1"/>


    <a href="gerencia_base_imagens.php">Back</a>


    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <br/>

</body>

</html>
