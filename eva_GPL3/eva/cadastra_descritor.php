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

<?
//Se ja houver alguma sessao iniciada, destroi-a
if (isset($_SESSION['plugin_file'])) {
    session_destroy();
}

session_start();
//echo "session_id=".session_id();
?>
<html>
    <head>
        <title>Eva tool - Insert descriptor</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>
    <link href="estilo.css" rel="stylesheet" />

    <script language="JavaScript">
        function Valida() {
            if (document.form_upload_descritor.plugin_file.value == '') {
                window.alert('Select the plugin file (.so or .dll)');
                return false;
            } else {
                //window.alert(document.form_upload_descritor.plugin_file.value);
                document.form_upload_descritor.caminho.value = document.form_upload_descritor.plugin_file.value;
                return true;
            }
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

    <h3>Step 1. Instructions</h3>

    Please, follow carefully the instructios in this page. To insert a descriptor into Eva, a plugin file needs to be generated. This plugin must follow the specifications below.

    <ol>
    <li>The plugin must have two functions. The headings of these functions are the following ones:
    <blockquote class="code">
        void   Extraction(char *img_path, char *fv_path);<br/>
        double Distance(char *fv1_path, char *fv2_path);
    </blockquote>

    The <i>Extraction</i> function is the responsible to extract the feature vector from an image. The path of this image in the filesystem is the first argument. The second argument is the path of the feature vector file being generated. The feature vector file will store the extracted visual properties.

    The <i>Distance</i> function is the responsible to compare two feature vectors. Its arguments are the path of the feature vector files to be compared. The returned value is of <i>double</i> type and it is the <b>distance</b> between the imagens. 
    </li>

    <li>Since you have the source code containing these two functions, you are able to generate the plugin to be inserted into Eva. The plugin generation (DLL or SO) is made when compiling the source code. Below, there are examples of how to compile the source code ir order to generate the plugin. (ps.: in the examples, we consider that the mentioned functions are in the file 'descriptor.c')<br/>

    <ul>
        <li>Example of how to generate the plugin file (.so) when all the source code is into a single file:<br/>
            <a class="code">$ gcc descriptor.c -fpic -shared -o descriptor.so</a>
        </li>

        <li>Example of how to generate the plugin file (.so) when the source code is organized with includes and libraries:<br/>
            <a class="code">$ gcc descriptor.c -fpic -shared -o descriptor.so -I../include -L../lib -ldescriptors</a><br/>
            ps.: the last compilation parameter ("-ldescriptors") is the name of the library file (".a") without the extension and without the "lib" prefix.</li>
        <li><b>The compilation must be made for 64 bits.</b></li>
    </ul>

    </li>

    <li>The plugin was generated.</li>

    </ol>

    <hr size="1"/>

    <h3>Step 2. Plugin upload</h3>

    <form enctype="multipart/form-data" action="cadastra_descritor_upload.php" name="form_upload_descritor" method="POST" onSubmit="return Valida()">
        <input type="hidden" name="caminho">
        <table border="0" cellspacing="1" cellpadding="5" align="center" width="600" bgcolor="#CCCCCC">
          <tr>
             <td bgcolor="#EEEEEE">
               Select the plugin file:<br/>
               <input name="plugin_file" type="file">
               <input type="hidden" name="MAX_FILE_SIZE" value="30000">
               <br/>ps.: the plugin file name must have at most 20 characters.
             </td>
          </tr>
          <tr>
             <td colspan="2" align="center" bgcolor="#DDDDDD">
               <input type="submit" value="Send" class="botao">
             </td>
          </tr>
        </table>
    </form>

    <hr size="1"/>

    <a href="gerencia_descritor.php">Back</a>


    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    </div>
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

</body>

</html>
