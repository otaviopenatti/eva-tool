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
    //destroy session to avoid errors
    session_destroy();
}

?>
<html>
    <head>
        <title>Eva tool - Experimento details</title>

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


        <h1>Experiment details</h1>
        <hr size="1"/>

<?

    $arq_details = "results/".$_GET['id_exp']."/exp_details_".$_GET['id_exp'].".log";
    if (file_exists($arq_details)) {
        //check 'extraction' progress
        exec("cat ".$arq_details." | grep \"ext_progress\" | tail -n 1", $ext_prog);
        $ext_prog = split(":", $ext_prog[0]);

        //check 'distance' progress
        exec("cat ".$arq_details." | grep \"dist_progress\" | tail -n 1", $dist_prog);
        $dist_prog = split(":", $dist_prog[0]);
        if ((round(($dist_prog[1]*100),2)) == 100) {
            $fim_dist = 1;
        }

    }

    include "util.php";
    // Connecting, selecting database
    $dbconn = connect();

    // Select experiment data
    $query = 'SELECT id, descr, email FROM experiment WHERE id='.$_GET['id_exp'];
    $result_exp = pg_query($query) or die('Query failed: ' . pg_last_error());

    if (!$line_exp=pg_fetch_array($result_exp, null, PGSQL_ASSOC)) {
        echo "Experimento ".$_GET['id_exp']." n&atilde;o existe!";
    } else {
?>
    <h4>Experiment metadata</h4>
    <b>ID: <?=$line_exp['id']?></b><br/>
    <b>Description:</b> <?=$line_exp['descr']?><br/>
    <b>Responsible:</b> <?=$line_exp['email']?>

    <table border="0" cellspacing="1" cellpadding="3" width="98%" bgcolor="#CCCCCC" class="cadastro" align="center">
    <tr>
        <th width="20%">Descriptors used</th>
        <th width="20%">Image databases used</th>
        <th width="20%">Evaluation measures computed</th>
    </tr>
    <tr>
    <td>
<?

        // Select all descriptors used
        $query = 'SELECT iddescriptor FROM experimentdescriptor WHERE idexperiment='.$line_exp['id']." ORDER BY iddescriptor";
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
    </tr>
    </table>

    <hr size="1"/>
    <b>Progress:</b>
    <ul>
        <li>Extraction: <b><?=round(($ext_prog[1]*100),2)?></b>%</li>
        <li>Distance: <b><?=round(($dist_prog[1]*100),2)?></b>%</li>
    </ul>
    <a href="results/<?=$line_exp['id']?>">LOG</a>
    <hr size="1"/>

<?
        pg_free_result($result_exp);
        pg_free_result($result_desc);
        pg_free_result($result_img);
        pg_free_result($result_m);

        // Select extraction times from the experiments
        $query = 'SELECT * FROM experimenttime WHERE idexperiment='.$_GET['id_exp'].' AND idevaluationmeasure=1 ORDER BY iddescriptor';
        $result_extraction = pg_query($query) or die('Query failed: ' . pg_last_error());

?>

            <h4>Extraction times</h4>
            <table border="0" cellspacing="1" cellpadding="3" width="600" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th width="30%">Descriptor</th>
                <th width="35%">Average time for 1 extraction</th>
                <th width="35%">Standard deviation</th>
            </tr>
<?
                while ($line_extraction = pg_fetch_array($result_extraction, null, PGSQL_ASSOC)) {
?>
                <tr>
                    <td style="text-align:center;"><?=$line_extraction['iddescriptor']?></td>
                    <td style="text-align:right;font-family:courier;font-weight:bold"><?=$line_extraction['value']?></td>
                    <td style="text-align:right;font-family:courier;font-weight:bold"><?=$line_extraction['stddev']?></td>
                </tr>
<?
    }
?>
            </table><br/>
            <hr size="1"/>

<?
    // Select distance times from the experiments
    $query = 'SELECT * FROM experimenttime WHERE idexperiment='.$_GET['id_exp'].' AND idevaluationmeasure=2 ORDER BY iddescriptor';
    $result_distance = pg_query($query) or die('Query failed: ' . pg_last_error());
?>

            <h4>Distance times</h4>
            <table border="0" cellspacing="1" cellpadding="3" width="600" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th width="30%">Descriptor</th>
                <th width="35%">Average time for 1 distance computation</th>
                <th width="35%">Standard deviation</th>
            </tr>
<?
                while ($line_distance = pg_fetch_array($result_distance, null, PGSQL_ASSOC)) {
?>
                <tr>
                    <td style="text-align:center;"><?=$line_distance['iddescriptor']?></td>
                    <td style="text-align:right;font-family:courier;font-weight:bold"><?=$line_distance['value']?></td>
                    <td style="text-align:right;font-family:courier;font-weight:bold"><?=$line_distance['stddev']?></td>
                </tr>
<?
    }
?>
            </table><br/>
            <hr size="1"/>
            <h4>Other results</h4>
<?
                        /////////////////////////////////////////////////////////////////////////////
                        //Checking if the distance table already existed when the experiment was executed
                        //(this checking was probably necessary when the tool was using distance files instead of a database)
                        echo "\t<ul>\n";
                        $query = 'SELECT idexperiment FROM distance WHERE idexperiment='.$line_exp['id'].' LIMIT 1';
                        $result_distance = pg_query($query) or die('Query failed: ' . pg_last_error());
                        $i=0;
                        if (pg_fetch_array($result_distance, null, PGSQL_ASSOC)) {
                            $action = "codes/view_images_bd.php";
                        } else {
                            $action = "codes/view_images.php";
                        }
                        //pg_free_result($result_distance);

                        echo "    <form method=\"post\" name=\"".$line_exp['id']."\" action=\"$action\">\n";

                        //put experiment id in a hidden form
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";

                        //put each descriptor id used in the experiment in a hidden form
                        $i=0;
                        foreach ($descritores as $desc_id) {
                            echo "\t\t\t  <input type=\"hidden\" name=\"desc".$i."\" value=\"".$desc_id."\"/>\n";
                            $i++;
                        }

                        if ($fim_dist == 1) {
                            echo "\t\t\t<li><input type=\"submit\" value=\"View retrieved images\"/></li>";
                        }
                        echo "</form>\n";

                        ////////////////////////////////
                        //Link for user evaluation
                        echo "    <form method=\"post\" name=\"form_name\" action=\"codes/view_images_feedback.php\">\n";
                        //put experiment id in a hidden form
                        echo "\t\t\t  <input type=\"hidden\" name=\"exp\" value=\"".$line_exp['id']."\"/>\n";
                        //put each descriptor id used in the experiment in a hidden form
                        $i=0;
                        foreach ($descritores as $desc_id) {
                            echo "\t\t\t  <input type=\"hidden\" name=\"desc".$i."\" value=\"".$desc_id."\"/>\n";
                            $i++;
                        }
                        $arq_query_list = "results/".$line_exp['id']."/query_list.txt";
                        if ( ($fim_dist==1 && file_exists($arq_query_list))) {
                            echo "<li><input type=\"submit\" value=\"Subjective evaluation\"/></li>";
                        }
                        $arq_queryImagesClasses = "results/".$line_exp['id']."/queryImagesClasses.txt";
                        if ($fim_dist==1 && file_exists($arq_queryImagesClasses)) {
                            //distance computation finished, but distance file generation may be in execution yet
                            //check progress in order to know it the precision x recall measure was already computed
                            exec("cat ".$arq_details." | grep \"pr_progress\" | tail -n 1", $pr_prog);
                            $pr_prog = split(":", $pr_prog[0]);
                            echo "<br/><li>Precision x Recall progress = <font color=#cc0000>".round(($pr_prog[1]*100),2)."%</font> (click in LOG to view the generated files; trec_eval results have the suffix \"'descName'_results.trec\")</li>";
                        }
                        echo "\t</ul>\n";
                        //////////////

        //////////////////////////////////////////////////////////////////////////////
        // Free resultset
        pg_free_result($result_extraction);
        pg_free_result($result_distance);
    }
    // Closing connection
    pg_close($dbconn);
?>
    <hr size="1"/>
    <a href="ver_experimentos_realizados.php">Back</a>
    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <br/>

</body>

</html>
