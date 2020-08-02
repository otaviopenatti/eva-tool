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
/*
$ cat novo2_id2_distancias_teste12.txt | grep "^class2/class1_10_689_000" | sort -k 3 | head -n 80
                                               ^query image name                          ^number of results
*/


// Parameters: id_experimento, id_base, id_descritor, nome_base


    //if came from a page different than this one
    if ($_POST) {
        $post_keys = array_keys($_POST); //get keys from POST
        $i=0;
        $desc_count=0;
        foreach ($_POST as $post) {
            //if key is 'desc', the current position is a descriptor id
            if (preg_match("/desc/",$post_keys[$i]) > 0) {
                $descritores[$desc_count] = $post;
                $desc_count++;
            } else if (preg_match("/exp/",$post_keys[$i]) > 0) {
                $id_experimento = $post;
            }
            $i++;
        }
        //register data in the SESSION to avoid losing information when changes descriptor or query image
        $_SESSION['id_experimento'] = $id_experimento;
        $_SESSION['descritores'] = $descritores;
    }

    //local variables for important values
    $id_experimento = $_SESSION['id_experimento'];
    $descritores = $_SESSION['descritores'];

    //checks which descriptor was selected 
    if ($_GET['descritor']) {
        $id_descritor = $_GET['descritor'];
    } else if ($_SESSION['id_descritor']) {
        $id_descritor = $_SESSION['id_descritor'];
    } else {
        $id_descritor = $descritores[0];
    }
    $_SESSION['id_descritor'] = $id_descritor; //registers descriptor in the SESSION

    $path_distancias = "/exp/otavio/results/".$id_experimento."/distances_".$id_descritor."_comClasses.txt";

    //if GET is empty, uses a default query image
    if ($_GET['query_img']) {
        $img_consulta = $_GET['query_img'];
    } else {
        $img_consulta = "/exp/otavio/img_databases/yahoo_2000/jpg00111.jpg"; //REMEMBER TO UPDATE HERE
    }

    $img_consulta_file = explode("/exp/otavio/", $img_consulta);
    $img_consulta_file = "../".$img_consulta_file[1];

    /***** VISUALIZATION PARAMETERS *****/
    $qtd_colunas_resultados = 5; //number of columns in the table of results
    $qtd_resultados = 70;

    $consulta = "cat ".$path_distancias." | grep \"^".$img_consulta."\"";
    exec($consulta, $output);

    if (!$output) {
        echo "<td><h1>Error in results!</h1></td>";
        exit(1);
    }

    //splits the rows and columns of the array
    $cont=0;
    foreach ($output as $linha) {
        $array_final[$cont] = split("\t", $linha);
        $array_consulta[$cont] = $array_final[$cont][0];
        $array_result[$cont] = $array_final[$cont][1];
        $array_dist[$cont] = $array_final[$cont][2];
        $cont++;

        //col 0 = query image (img_consulta)
        //col 1 = result image (img_resultado)
        //col 2 = distance between them

    }
    array_multisort($array_dist, SORT_ASC, $array_final); //sorts the array by the "distance" col
    $array_final = array_slice($array_final, 0, $qtd_resultados);
?>

<html>
<head>
  <title>CBIR Color Image Descriptors</title>
  <link rel="stylesheet" type="text/css" href="../liquidcorners.css">
  <link rel="SHORTCUT ICON" href="favicon.ico"/>
  <link href="../estilo.css" rel="stylesheet" />
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


    <h2>CBIR Color Image Descriptors</h2>

    <table cellspacing="1" cellpadding="4" align="center" class="consulta">
      <tr>
      <td>Descritor respons&aacute;vel pelos resultados abaixo: 
        <h2>
        <? echo strtoupper($id_descritor);  ?>
        </h2>
        <br/><br/><br/><br/><hr size="1"/><br/>
        <ul>
        <li>Ver resultados de outro descritor:
        <?$action = "view_images.php"?>
        <form method="get" action="<?=$action?>">
            <select name="descritor" size="1">
                <!-- this list depends on the experiment - get descriptors used -->
                <option value="">--escolha--</option>
                <?
                echo "\n";
                foreach ($descritores as $desc) {
                    echo "\t\t<option value=\"".$desc."\">".$desc."</option>\n";
                }
                ?>
            </select>
            <?/*
            echo "\n";
            $i=0;
            foreach ($descritores as $desc_id) {
                echo "\t    <input type=\"hidden\" name=\"desc".$i."\" value=\"".$desc_id."\"/>\n";
                $i++;
            }*/
            ?>
            <input type="hidden" name="query_img" value="<?=$img_consulta?>"/>
            <input type="submit" name="Submit" value="OK"/>
        </form></li>
        <br/><br/>
        <li>
        <a href="../ver_experimentos_realizados.php">Go to other experiments conducted</a>
        </li>
        </ul>
         </td>
      <td>
           Query image:<br/>
           <img src="<?=$img_consulta_file?>" alt="<?=$img_consulta?>" border="1" align="middle" style=\"max-width:300px;\" height=200/>
           <br/><br/>
           Name: <br/><?=$img_consulta?><br/>
           <!-- Class: <?//=$_GET['class']?>-->
         </td></tr>
    </table>
    <br/><br/>

    <table cellspacing="1" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTS</td></tr>
    <tr>
<?
    $cont=1;
    foreach ($array_final as $linha) {

        //file name is adjusted for the image database local directory
        //removes the common part to all images
        $img_file = explode("/exp/otavio/",$linha[1]);
        $img_file = "../".$img_file[1];

        //image name should be only file name, no path included
        $img_name = explode("/",$img_file);
        $img_name = $img_name[count($img_name)-1];

        //replace spaces by %20 in file names
        $img_file = str_replace(" ", "%20", $img_file);

        //image name
        echo "<td>";
        echo "  <a href=\"view_images.php?query_img=".$linha[1]."\">";
        echo "    <img src=".$img_file." alt=".$img_name." border=\"1\" style=\"max-width:180px;\" height=130/>";
        echo "</a><br/>";
        echo "<br/>".$img_name;
        echo "<br/>".$linha[2]."<br/>";
        echo "</td>\n";


        if (!($cont%$qtd_colunas_resultados)) {
            echo "</tr>\n<tr>";
        }
        $cont++;
    }
?>

    </tr></table>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    </div>
    <!--************************ BORDAS ARREDONDADAS! ************************-->

</body>

</html>
