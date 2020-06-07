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

if ($_SESSION['id_experimento']) {
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

    //get max and min of experiment ids
    $query_min = "SELECT MIN(id) FROM experiment";
    $result_min = pg_query($query_min) or die('Query failed: ' . pg_last_error());
    $min = pg_fetch_array($result_min, null, PGSQL_ASSOC);
    pg_free_result($result_min);

    $query_max = "SELECT MAX(id) FROM experiment";
    $result_max = pg_query($query_max) or die('Query failed: ' . pg_last_error());
    $max = pg_fetch_array($result_max, null, PGSQL_ASSOC);
    pg_free_result($result_max);

    //check if ant and prox are valid
    if ($_GET['ant']<$min['min'] OR $_GET['ant']>$max['max']) {
        unset($_GET['ant']);
    }
     if ($_GET['prox']<=$min['min'] OR $_GET['prox']>$max['max']) {
         unset($_GET['prox']);
     }

    //Select all experiments executed (at each 10)
    if ($_GET['ant']) {
        //to select the 10 previous ones
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
                    $descritores = ""; //set to empty the variable that stores the descriptors of each experiment
                    $fim_dist = 0;  //initialized variable that indicates if the experiment is concluded or not
                    $fim_ext = 0;

                    //saves the id of the first experiment shown in the screen
                    if ($primeiro_exp_exibido == -1) {
                        $primeiro_exp_exibido = $line_exp['id'];
                    }
?>
                <tr>
                    <td><?=$line_exp['id']?></td>
                    <td><?=$line_exp['descr']?></td>
                    <td><?=$line_exp['email']?></td>
                    <td>
                        <?
                        // Select all descriptors used
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
                        // Select all image databases used
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
                        // Select all evaluation measures used
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

                        //Check experiment progress
                        $fim_ext = 0;
                        $fim_dist = 0;

                        //adjustment to avoid showing progress of 100% when only some descriptors finished
                        $query_progress = "SELECT COUNT(iddescriptor) from experimenttime where idexperiment=".$line_exp['id']." GROUP BY iddescriptor ORDER BY COUNT(iddescriptor) LIMIT 1";
                        $result_progress = pg_query($query_progress) or die('Query failed: ' . pg_last_error());
                        $valor = pg_fetch_array($result_progress, null, PGSQL_ASSOC);
                        //if the number returned in the query equals 2, all descriptors already have the two measures in the time table
                        //which means that the experiment has already finished
                        if ($valor['count'] == 2) {
                            echo "<font color=#0c8f0f>E=100%</font><br/>";
                            $fim_ext = 1;
                            echo "<font color=#0c8f0f>D=100%</font><br/>";
                            $fim_dist = 1;
                        }
                        pg_free_result($result_progress);

                        /*** PROGRESS CHECKING ***/
                        $arq_details = "results/".$line_exp['id']."/exp_details_".$line_exp['id'].".log";

                        //if there is not time registered in the database, search for progress in the log file
                        if ($fim_ext != 1) {
                            if (file_exists($arq_details)) {
                                exec("cat ".$arq_details." | grep \"ext_progress\" | tail -n 1", $ext_prog);
                                $ext_prog = split(":", $ext_prog[0]);
                                echo "<font color=#cc0000>E=</font>".round(($ext_prog[1]*100),2)."%<br/>";
                            } else {
                                echo "E=0%<br/>";
                            }


                        }

                        //if there is not time registered in the database, search for progress in the log file
                        if ($fim_dist != 1) {
                            if (file_exists($arq_details)) { 
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
                    ?>
                    </td>
                    <td>
                        - <a href="detalhes.php?id_exp=<?=$line_exp['id']?>">Details</a><br/>
                        - <a href="results/<?=$line_exp['id']?>/">LOG</a><br/>

                    <?
                        $action = "codes/view_images_bd.php";
                        echo "    <form method=\"post\" name=\"".$line_exp['id']."\" action=\"$action\">\n";

                        //experiment id in a hidden form
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";

                        //descriptors id in a hidden form
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
                        echo "    <form method=\"post\" name=\"form_name\" action=\"codes/view_images_feedback.php\">\n";

                        //experiment id in a hidden form
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";
                        //descriptors id in a hidden form
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
                            //distance is concluded, but generation of distance file may be ongoing
                            //check progress if the precision x recall measure was already computed
                            exec("cat ".$arq_details." | grep \"pr_progress\" | tail -n 1", $pr_prog);
                            $pr_prog = split(":", $pr_prog[0]);
                            echo "<font color=#cc0000>PR=</font>".round(($pr_prog[1]*100),2)."%";
                        }
                        $pr_prog = "";

                        //get the id of the last experiment shown in the screen
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
    //pages for the list of experiments
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
    <!-- form to delete and experiment in the database -->
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
