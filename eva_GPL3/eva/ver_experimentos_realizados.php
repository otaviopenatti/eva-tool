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

if ($_SESSION['id_experimento']) {
    //limpa a sessao para evitar erros
    session_destroy();
}

?>
<html>
    <head>
        <title>Eva tool - View experiments</title>

    <link href="estilo.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>

    </head>

<body>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->


        <h1>Experiments</h1>

        <hr size="1"/>
        <a href="index.htm">Index</a>
<?
        if ($_GET['del']) {
?>
            <table align="center" cellspacing="1" cellpadding="4">
                <tr><td>Experiment <?=$_GET['del']?> successfully deleted!</td></tr>
            </table>
<?
        }

?>
        <hr size="1"/>

<?
    include "util.php";
    // Connecting, selecting database
    $dbconn = connect();

    //pega o max e o min id dos experimentos do bd
    $query_min = "SELECT MIN(id) FROM experiment";
    $result_min = pg_query($query_min) or die('Query failed: ' . pg_last_error());
    $min = pg_fetch_array($result_min, null, PGSQL_ASSOC);
    pg_free_result($result_min);

    $query_max = "SELECT MAX(id) FROM experiment";
    $result_max = pg_query($query_max) or die('Query failed: ' . pg_last_error());
    $max = pg_fetch_array($result_max, null, PGSQL_ASSOC);
    pg_free_result($result_max);

    //echo "max=".$max['max']." - min=".$min['min']."<br/>";
    //verifica se ant e prox sao validos
    if ($_GET['ant']<$min['min'] OR $_GET['ant']>$max['max']) {
        unset($_GET['ant']);
    }
     if ($_GET['prox']<=$min['min'] OR $_GET['prox']>$max['max']) {
         unset($_GET['prox']);
     }

    // Seleciona todos os experimentos realizados - pega de 10 em 10
    if ($_GET['ant']) {
        //para selecionar os 10 anteriores
        $query = "SELECT * FROM (SELECT * FROM experiment WHERE id>".$_GET['ant']." ORDER BY id LIMIT 10) as q2 ORDER BY id DESC";
    } else {
        if ($_GET['prox']) {
            $query_where = "WHERE id<".$_GET['prox'];
        }
        $query = 'SELECT * FROM experiment '.$query_where.' ORDER BY id DESC LIMIT 10';
    }
    $result_exp = pg_query($query) or die('Query failed: ' . pg_last_error());

?>

            <h4>Experimentos details</h4>
            <table border="0" cellspacing="1" cellpadding="3" width="98%" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th>id</th>
                <th width="30%">Description</th>
                <th width="10%">E-mail responsible</th>
                <th width="20%">Descriptors used</th>
                <th width="20%">Image datases used</th>
                <th width="20%">Measures extracted</th>
                <th width="1">Progress</th>
                <th width="1">Results</th>
            </tr>
<?
                $exp_count=0;
                $primeiro_exp_exibido = -1;
                while ($line_exp = pg_fetch_array($result_exp, null, PGSQL_ASSOC)) {
                    $descritores = ""; //esvazia variavel que guarda os descritores de cada experimento
                    $fim_dist = 0;  //inicializa variavel que indica se o experimento foi terminado ou nao
                    $fim_ext = 0;

                    //guarda o id do primeiro exp exibido na tela
                    if ($primeiro_exp_exibido == -1) { //guarda apenas qdo o primeiro_exp_exibido nao foi inicializado ainda
                        $primeiro_exp_exibido = $line_exp['id'];
                    }
?>
                <tr>
                    <td><?=$line_exp['id']?></td>
                    <td><?=$line_exp['descr']?></td>
                    <td><?=$line_exp['email']?></td>
                    <td>
                        <?
                        // Seleciona todos os descritores utilizados
                        $query = 'SELECT iddescriptor FROM experimentdescriptor WHERE idexperiment='.$line_exp['id'];
                        $result_desc = pg_query($query) or die('Query failed: ' . pg_last_error());
                        $i=0;
                        while ($line_desc = pg_fetch_array($result_desc, null, PGSQL_ASSOC)) {
                            echo "<li>".$line_desc['iddescriptor']."</li>";
                            $descritores[$i] = $line_desc['iddescriptor'];
                            $i++;
                        }
                        ?>
                    </td>
                    <td>
                        <?
                        // Seleciona todas as bases utilizadas
                        $query = 'SELECT img.name FROM experimentimagedatabase ei, imagedatabase img WHERE idexperiment='.$line_exp['id'];
                        $query.= ' AND ei.idimagedatabase=img.id';
                        $result_img = pg_query($query) or die('Query failed: ' . pg_last_error());
                        //echo "<ul>";
                        while ($line_img = pg_fetch_array($result_img, null, PGSQL_ASSOC)) {
                            echo "<li>".$line_img['name']."</li>";
                        }
                        //echo "</ul>";
                        ?>
                    </td>
                    <td>
                        <?
                        // Seleciona todas as medidas utilizadas
                        $query = 'SELECT ev.name FROM experimentevaluationmeasure em, evaluationmeasure ev WHERE idexperiment='.$line_exp['id'];
                        $query.= ' AND em.idevaluationmeasure=ev.id';
                        $result_m = pg_query($query) or die('Query failed: ' . pg_last_error());
                        //echo "<ul>";
                        while ($line_m = pg_fetch_array($result_m, null, PGSQL_ASSOC)) {
                            echo "<li>".$line_m['name']."</li>";
                        }

                        ?>
                    </td>
                    <td>
                    <?

                        //Verificando progresso dos experimentos
                        $fim_ext = 0;
                        $fim_dist = 0;


                        //AJUSTA PARA EVITAR MOSTRAR 100% QDO NAO TERMINARAM TODOS OS DESCRITORES (QDO APENAS 1 TERMINOU)
                        $query_progress = "SELECT COUNT(iddescriptor) from experimenttime where idexperiment=".$line_exp['id']." GROUP BY iddescriptor ORDER BY COUNT(iddescriptor) LIMIT 1";
                        $result_progress = pg_query($query_progress) or die('Query failed: ' . pg_last_error());
                        $valor = pg_fetch_array($result_progress, null, PGSQL_ASSOC);
                        //se a qtd retornada na consulta for igual a 2, eh pq todos os descritores ja estao com as duas medidas na tabela de tempos
                        //portanto o experimento ja terminou
                        if ($valor['count'] == 2) {
                            echo "<font color=#0c8f0f>E=100%</font><br/>";
                            $fim_ext = 1;
                            echo "<font color=#0c8f0f>D=100%</font><br/>";
                            $fim_dist = 1;
                        }
                        pg_free_result($result_progress);
/*
                        //se ja existir registros na tabela de tempos, o experimento pode ja ter terminado
                        $query = 'SELECT idevaluationmeasure FROM experimenttime WHERE idexperiment='.$line_exp['id'];
                        $result_progress = pg_query($query) or die('Query failed: ' . pg_last_error());
                        $i=0;
                        while ($valor = pg_fetch_array($result_progress, null, PGSQL_ASSOC)) {
                            if ($valor['idevaluationmeasure'] == 1 && $fim_ext != 1) {
                                echo "<font color=#0c8f0f>E=100%</font><br/>";
                                $fim_ext = 1;
                            }
                            if ($valor['idevaluationmeasure'] == 2 && $fim_dist != 1) {
                                echo "<font color=#0c8f0f>D=100%</font><br/>";
                                $fim_dist = 1;
                            }
                        }
*/
                        //pg_free_result($result_progress);

                        /****VERIFICACAO DE PROGRESSO **********/
                        $arq_details = "results/".$line_exp['id']."/exp_details_".$line_exp['id'].".log";

                        //se nao tem registro de tempo no bd, busca progresso em arquivo
                        if ($fim_ext != 1) {
                            if (file_exists($arq_details)) {
                                exec("cat ".$arq_details." | grep \"ext_progress\" | tail -n 1", $ext_prog);
                                $ext_prog = split(":", $ext_prog[0]);
                                echo "<font color=#cc0000>E=</font>".round(($ext_prog[1]*100),2)."%<br/>";
                            } else {
                                echo "E=0%<br/>";
                            }


                        }

                        //se nao tem registro de tempo no bd, busca progresso em arquivo
                        if ($fim_dist != 1) {
                            if (file_exists($arq_details)) {   //testando progresso no arquivo de detalhes
                                exec("cat ".$arq_details." | grep \"dist_progress\" | tail -n 1", $dist_prog);
                                $dist_prog = split(":", $dist_prog[0]);
                                echo "<font color=#cc0000>D=</font>".round(($dist_prog[1]*100),2)."%<br/>";
                                if (round(($dist_prog[1]*100),2) == 100) {
                                    $fim_dist = 1;
                                }
                            } else {
                                echo "D=0%";
                                $fim_dist = 0;
                            }
                        }
                        $e_atual = "";
                        $e_total = "";
                        $d_atual = "";
                        $ext_prog = "";
                        $dist_prog = "";
/***************/
                    ?>
                    </td>
                    <td>
                        - <a href="detalhes.php?id_exp=<?=$line_exp['id']?>">Details</a><br/>
                        - <a href="results/<?=$line_exp['id']?>/">LOG</a><br/>

                    <?
                        $action = "codes/view_images_bd.php";
                        echo "    <form method=\"post\" name=\"".$line_exp['id']."\" action=\"$action\">\n";

                        //coloca o id do experimento num form hidden
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";

                        //coloca o id de cada descritor usado no experimento num form hidden
                        $i=0;
                        foreach ($descritores as $desc_id) {
                            echo "\t\t\t  <input type=\"hidden\" name=\"desc".$i."\" value=\"".$desc_id."\"/>\n";
                            $i++;
                        }

                        if ($fim_dist == 1) {
                            echo "<input type=\"submit\" value=\"IMGS\"/>";
                        }
                        echo "</form>\n";

                        ////////////////////////////////
                        echo "    <form method=\"post\" name=\"seila\" action=\"codes/view_images_feedback.php\">\n";

                        //coloca o id do experimento num form hidden
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";
                        //coloca o id de cada descritor usado no experimento num form hidden
                        $i=0;
                        foreach ($descritores as $desc_id) {
                            echo "\t\t\t  <input type=\"hidden\" name=\"desc".$i."\" value=\"".$desc_id."\"/>\n";
                            $i++;
                        }
                        $arq_query_list = "results/".$line_exp['id']."/query_list.txt";
                        if ( ($fim_dist==1 && file_exists($arq_query_list)) ) {
                            echo "<input type=\"submit\" value=\"EVAL\"/>";
                        }
                        $arq_queryImagesClasses = "results/".$line_exp['id']."/queryImagesClasses.txt";
                        if ($fim_dist==1 && file_exists($arq_queryImagesClasses)) {
                            //distancia acabou. mas geracao do arquivo de distancias pode estar em execucao ainda
                            //verifica progresso para saber se calculo do precision x recall ja foi calculado
                            exec("cat ".$arq_details." | grep \"pr_progress\" | tail -n 1", $pr_prog);
                            $pr_prog = split(":", $pr_prog[0]);
                            echo "<font color=#cc0000>PR=</font>".round(($pr_prog[1]*100),2)."%";
                        }
                        $pr_prog = "";
                        //////////////
                        //////////////////////////////////

                        //Pega o ID do ultimo exp exibido
                        $ultimo_exp_exibido = $line_exp['id'];

                    ?>
                        </form>
                    </td>


                </tr>
<?
                    $exp_count++;

                }
                if ($exp_count == 0) {
                    echo "<tr><td colspan=8 style=\"text-align:center\">No experiment yet.</td></tr>";
                    $ultimo_exp_exibido = 1;
                } else {

                    // Free resultset
                    pg_free_result($result_exp);
                    pg_free_result($result_desc);
                    pg_free_result($result_img);
                    pg_free_result($result_m);
                }

?>
            </table>
    <center>
<?  
    //adiciona paginacao dos resultados
    if (($primeiro_exp_exibido < $max['max']) && ($exp_count!=0)) {  ?>
        <a href="ver_experimentos_realizados.php?ant=<?=$primeiro_exp_exibido?>">&lt; previous page</a>
<?  }
    if (($ultimo_exp_exibido > $min['min']) && ($exp_count!=0)) {
?>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="ver_experimentos_realizados.php?prox=<?=$ultimo_exp_exibido?>">next page &gt;</a>
<?  }  ?>
    </center>

    <br/>
    <!-- formulario que apaga um experimento da base -->
    <form method="post" name="exp_del" action="exclui_experimento.php">
    <table align="center" cellspacing="1" cellpadding="5">
        <tr>
            <td>Delete experiment from database (does not delete experiment files)</td>
        </tr>
        <tr>
            <td>
                ID: <input type="text" size="10" name="id_exp_del"/> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type="submit" value="DELETE"/>
            </td>
        </tr>
    </table>
    </form>

    <hr size="1"/>

    <a href="index.htm">Index</a>
<?
    // Closing connection
    pg_close($dbconn);
?>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <br/>

</body>

</html>
