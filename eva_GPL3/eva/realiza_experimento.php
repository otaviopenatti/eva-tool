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
session_start();
//echo "session_id=".session_id();
?>
<html>
    <head>
        <title>Eva tool - Run Experiment</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>
    <link href="estilo.css" rel="stylesheet" />

    <script language="JavaScript">
        function PreencheHidden(hidden, array) {
            hidden.value = array[0];
            for (var i=1; i<array.length; i++) {
                hidden.value = hidden.value + "," + array[i];
            }
            //alert('array='+hidden.value);
        }


        function ValidaSelecoes(checkset, array) {
            count = 0;
            for (var i=0; i<checkset.length; i++) {
                if (checkset[i].checked) {
                    count++;
                    array.push(checkset[i].value);
                }
            }
            if (count == 0) {
                return false;
            } else {
                return true;
            }
        }

        function Valida() {
            var descritores = new Array();
            var bases = new Array();
            var medidas = new Array();
            if (!ValidaSelecoes(document.form_exp.desc, descritores) ||
                !ValidaSelecoes(document.form_exp.imgdb, bases) ||
                !ValidaSelecoes(document.form_exp.measure, medidas)) {
                alert('Select at least one descriptor, at least one image database, and at least one evaluation measure!');

                return false;
            }
            //alert('descritores='+descritores.length);
            //alert('bases='+bases.length);
	    //alert('medidas='+medidas.length);

            //Gera listas com os ids dos descritores e das bases
            PreencheHidden(document.form_exp.ids_desc, descritores);
            PreencheHidden(document.form_exp.ids_bases, bases);
            PreencheHidden(document.form_exp.ids_medidas, medidas);

            return true;
        }

    </script>

    </head>

<body>
<?
    include "util.php";

    // Connecting, selecting database
    $dbconn = connect();

?>


    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <h1>Run experiment</h1>

    <form enctype="multipart/form-data" action="realiza_experimento_confirm.php" name="form_exp" method="POST" onSubmit="return Valida()">

<!-- *********************************************************************** -->
<!-- ********************** DESCRITORES ************************************ -->
    <h3>Select the descriptor(s)</h3>

        <table border="0" cellspacing="1" cellpadding="5" width="100%" bgcolor="#CCCCCC" class="cadastro">
        <input type="hidden" name="ids_desc" value=""/>
        <input type="hidden" name="desc" value="-1"/> <!--evita problemas qdo houver apenas 1 descritor -->
            <tr>
                <td bgcolor="#EEEEEE" width="34%" valign="top">
<?
                //DESCRITORES DE COR!!!!!!!!!!!!!!!
                $query = 'SELECT id, name FROM descriptor WHERE type=0 ORDER BY id';
                $result = pg_query($query) or die('Query failed: ' . pg_last_error());
                $desc_cor_count=0;
                echo "<b>COLOR</b><br/>";
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                    <input type="checkbox" name="desc" value="<?=$line['id']?>"/><?=$line['id']?> (<?=$line['name']?>)
                    <br/>
<?
                    $desc_cor_count++;
                }
                if ($desc_cor_count == 0) echo "No color descriptor available.<br/>";
                pg_free_result($result);
?>
                </td>
                <td bgcolor="#EEEEEE" width="34%" valign="top">
<?
                //DESCRITORES DE TEXTURA!!!!!!!!!!!!!!!
                $query = 'SELECT id, name FROM descriptor WHERE type=1 ORDER BY id';
                $result = pg_query($query) or die('Query failed: ' . pg_last_error());
                $desc_textura_count=0;
                echo "<b>TEXTURE</b><br/>";
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                    <input type="checkbox" name="desc" value="<?=$line['id']?>"/><?=$line['id']?> (<?=$line['name']?>)
                    <br/>
<?
                    $desc_textura_count++;
                }
                if ($desc_textura_count == 0) echo "No texture descriptor available.<br/>";
                pg_free_result($result);
?>
                </td>
                <td bgcolor="#EEEEEE" width="34%" valign="top">
<?
                //DESCRITORES DE FORMA!!!!!!!!!!!!!!!
                $query = 'SELECT id, name FROM descriptor WHERE type=2 ORDER BY id';
                $result = pg_query($query) or die('Query failed: ' . pg_last_error());
                $desc_forma_count=0;
                echo "<b>SHAPE</b><br/>";
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                    <input type="checkbox" name="desc" value="<?=$line['id']?>"/><?=$line['id']?> (<?=$line['name']?>)
                    <br/>
<?
                    $desc_forma_count++;
                }
                if ($desc_forma_count == 0) echo "No shape descriptor available<br/>";
                pg_free_result($result);

                //se nao existe nenhum descritor cadastrado, deixa como nula a contagem de descritores
                if ($desc_cor_count==0 && $desc_textura_count==0 && $desc_forma_count==0) {
                    $desc_count = 0;
                } else {
                    $desc_count = 1;
                }
?>
                </td>
            </tr>
            </table>


<!-- *********************************************************************** -->
<!-- ********************** BASES DE IMAGENS ******************************* -->
    <hr size="1"/>
    <h3>Select the image database(s)</h3>

<?
    // Performing SQL query
    $query = 'SELECT id, name FROM imagedatabase ORDER BY name';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>

            <table border="0" cellspacing="1" cellpadding="5" width="400" bgcolor="#CCCCCC" class="cadastro">
            <input type="hidden" name="ids_bases" value=""/>
	    <input type="hidden" name="imgdb" value="-1"/> <!--evita problemas qdo houver apenas 1 img_db -->
            <tr>
                <td bgcolor="#EEEEEE" width="50%">
<?
                $imgdb_count=0;
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                    <input type="checkbox" name="imgdb" value="<?=$line['id']?>"/><?=$line['name']?><br/>
<?
                    $imgdb_count++;
                }
                if ($imgdb_count == 0) {
                    echo "No image database available.<br/>";
                }
?>
                </td>
            </tr>
            </table>

<?
    // Free resultset
    pg_free_result($result);

?>

<!-- *********************************************************************** -->
<!-- ********************** MEDIDAS de AVALIACAO *************************** -->
    <hr size="1"/>
    <h3>Select the evaluation measure(s)</h3>

<?
    // Performing SQL query
    $query = 'SELECT id, name FROM evaluationmeasure';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>

            <table border="0" cellspacing="1" cellpadding="5" width="400" bgcolor="#CCCCCC" class="cadastro">
            <input type="hidden" name="ids_medidas" value=""/>
	    <input type="hidden" name="measure" value="-1"/> <!--evita problemas qdo houver apenas 1 medida -->
            <tr>
                <td bgcolor="#EEEEEE" width="50%">
<?
                $measure_count=0;
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                    <input type="checkbox" name="measure" value="<?=$line['id']?>" checked="true" disabled="true"/><?=$line['name']?><br/>
<?
                    $measure_count++;
                }
                if ($measure_count == 0) {
                    echo "No evaluation measure available.<br/>";
                }
?>
                </td>
            </tr>
            </table>

<?
    // Free resultset
    pg_free_result($result);

?>

    <hr size="1"/>
    <table border="0" cellspacing="1" cellpadding="5" width="96%" bgcolor="#CCCCCC" class="cadastro" align="center">
        <tr>
            <td colspan="2" align="center" bgcolor="#DDDDDD">
                <center>
<?
                if ($desc_count == 0 || $imgdb_count == 0 || $measure_count == 0) {
?>
                    To run an experiment it is necessary to insert at least one image descriptor, at least one image database and at least one evaluation measure into Eva tool.
<?
                } else {
?>
                    <input type="submit" value="Send" class="botao">
<?
                }
?>
                </center>
            </td>
        </tr>
    </table>
    </form>

<?
    // Closing connection
    pg_close($dbconn);
?>


    <hr size="1"/>

    <br/>
    <a href="index.htm">Back</a>

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
