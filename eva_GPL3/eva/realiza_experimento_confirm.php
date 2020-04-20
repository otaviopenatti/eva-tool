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


    $descritores = split('[,]', $_POST[ids_desc]);
    $bases = split('[,]', $_POST[ids_bases]);
    $medidas = split('[,]', $_POST[ids_medidas]);

    $_SESSION['descritores'] = $descritores;
    $_SESSION['bases'] = $bases;
    $_SESSION['medidas'] = $medidas;

/*
echo "SEPARANDO IDS DOS DESCRITORES<pre>";
print_r (split('[,]', $_POST[ids_desc]));
echo "</pre><br/><br/><br/>";


echo "SEPARANDO IDS DAS BASES<pre>";
print_r (split('[,]', $_POST[ids_bases]));
echo "</pre><br/><br/><br/>";


echo "Chamar scripts python com os parametros acima";
*/
?>
<html>
    <head>
        <title>Eva tool - Run Experiment - Confirmation</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>
    <link href="estilo.css" rel="stylesheet" />

    <script language="Javascript">
        function checkMail(email) {
            var x = email;
            var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            if (filter.test(x)) 
                return true;
            else
                return false;
        }

        function Valida() {
            if (document.form_exp.description.value == '') {
                alert("Insert a brief description about the experiment!");
                return false;
            }
            if (!checkMail(document.form_exp.email.value)) {
                alert("Invalid e-mail!");
                return false;
            }
            /*verificacao da lista de consultas*/
            //if (document.form_exp.consultas_lista.value == '') {
            //    window.alert('Escolha o arquivo com a lista de imagens de consultas');
            //    return false;
            //} else {
                //window.alert(document.form_exp.consultas_lista.value);
                document.form_exp.caminho_consultas_lista.value = document.form_exp.consultas_lista.value;
            //}
            return true;
        }
    </script>
    </head>

<body>
<?
    include "util.php";
    $dbconn = connect();
?>


    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <h1>Run experiment - Confirmation</h1>



        <table border="0" cellspacing="1" cellpadding="5" width="90%" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th width="33%">
                    Descriptor(s) selected
                </th>
                <th width="33%">
                    Image database(s) selected
                </th>
                <th width="33%">
                    Evaluation measure(s) selected
                </th>
            </tr>
            <tr>
                <td>
                <ul>
<?
                foreach ($descritores as $desc) {
                    echo "<li>$desc</li>";
                }
?>
                </ul>
                </td>
                <td>
                    <ul>
<?
                        //por padrao, faz de conta que existe uma base classificada na lista
                        //isto eh feito pois a opcao de considerar a divisao da base soh pode
                        //ser ativada caso todas as bases escolhidas sejam classificadas!
                        //entao, qdo aparecer uma base nao classificada, ja desativa
                        $existe_base_classificada = 1;

                        //recupera os nomes das bases
                        $query = "SELECT id, name, classified FROM imagedatabase WHERE id IN (".$_POST[ids_bases].")";
                        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

                        foreach ($bases as $base) {
                            $line = pg_fetch_array($result, null, PGSQL_ASSOC);
                            echo "<li>$base - ".$line['name']."</li>";

                            //Verificar se existe alguma base nao classificada
                            if ($line['classified']==f) {
                                $existe_base_classificada = 0; //se existir ja desativa a opcao de base classificada
                            }
                        }
                        // Free resultset
                        pg_free_result($result);
?>
                    </ul>
                </td>

                <td>
                    <ul>
<?
                        //recupera os nomes das medidas de avaliacao
                        $query = "SELECT id, name FROM evaluationmeasure WHERE id IN (".$_POST[ids_medidas].")";
                        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

                        foreach ($medidas as $med) {
                            $line = pg_fetch_array($result, null, PGSQL_ASSOC);
                            echo "<li>$med - ".$line['name']."</li>";
                        }
                        // Free resultset
                        pg_free_result($result);
?>
                    </ul>
                </td>
            </tr>
        <tr>
            <td colspan="3" align="center" bgcolor="#DDDDDD">
        <form enctype="multipart/form-data" action="realiza_experimento_run.php" name="form_exp" method="POST" onSubmit="return Valida()">
                <table width="100%" cellspacing="0" cellpadding="5">
                    <tr>
                        <td valign="top" width="30%">Experiment description:</td><td><textarea name="description" rows="2" cols="60"></textarea></td>
                    </tr>
                    <tr><td colspan="2"><hr size="1"/></td></tr>
                    <tr>
                        <td colspan="2"><b>Query images</b></td>
                    </tr>
                    <tr>
                        <td valign="top">Quantity of query images</td>
                        <td>
                            <select name="consultas" size="1">
                                <option value="0">All</option>
                                <option value="1000">1000</option>
                            </select> ps.: this option will be ignored if a pre-defined list of query images is used
                            <br/><br/><b>OR</b><br/>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">Pre-defined list of query images</td>
                        <td>
                            <input type="hidden" name="caminho_consultas_lista"/>
                            <input type="hidden" name="MAX_FILE_SIZE" value="512000"/>
                            <input type="file" name="consultas_lista"/><br/>
                            Ps.:
                            <ul>
                                <li>upload a .txt file</li>
                                <li>the list must contain only images that belong to the image database selected</li>
                                <li>use the full path for each image (the path in the server)</li>
                                <li>separate the image paths by ## (no blank spaces before or after the symbols)</li>
                                <li>do not insert blank spaces before or after the images paths</li>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"></td>
                        <td>
                            <input type="checkbox" name="cross_validation"/><i>Cross-validation</i><br/>
                            Ps.: this option is valid only if a pre-defined list of query images is used.
                        </td>
                    </tr>
<?
    if ($existe_base_classificada) {
?>
                    <tr><td colspan="2"><hr size="1"/></td></tr>
                    <tr><td colspan="2"><b>Categorized image database:</b></td></tr>
                    <tr>
                        <td valign="top" colspan="2"><input type="checkbox" name="classes"/> Consider database categorization (computes Precision x Recall curves and trec_eval measures)</td>
                    </tr>
<?
    }
?>
                    <tr><td colspan="2"><hr size="1"/></td></tr>
                    <tr>
                        <td valign="top">E-mail for notification</td><td><input type="text" name="email" size="60" maxlength="50"/></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <center>
                    <input type="submit" value="Start experiment"/>
                </center>
            </td>
        </tr>
        </form>
    </table>

<?
    // Closing connection
    pg_close($dbconn);
?>


    <hr size="1"/>

    <br/>
    <a href="realiza_experimento.php">Back</a>

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
