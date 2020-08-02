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

    //checks if there is a query image list 
    if (file_exists("../results/".$id_experimento."/query_list.txt")) {
        //creates a combo box with the images from the query list
        $arq_query_list = file("../results/".$id_experimento."/query_list.txt");
        $lista_consultas = explode("##",$arq_query_list[0]);
    }

    include "../util.php";
    $dbconn = connect();

    //if GET is empty, uses a default query image
    if ($_GET['query_img']) {
        $img_consulta = $_GET['query_img'];
    } else if (file_exists("../results/".$id_experimento."/query_list.txt")) {
        $img_consulta = $lista_consultas[0]; //uses the first image from the list instead of selecting from the database (faster)
    } else {
        //using first query from the database
        $consulta_inicial = "SELECT fv1 FROM distance WHERE idexperiment=".$id_experimento." LIMIT 1";
        $result_inicial = pg_query($consulta_inicial) or die('Query failed: ' . pg_last_error());
        $line = pg_fetch_array($result_inicial, null, PGSQL_ASSOC);
        $img_consulta = $line[fv1];

        // Free resultset
        pg_free_result($result_inicial);
    }

    $img_consulta_file = AdjustImageSource($img_consulta);


    /***** VISUALIZATION PARAMETERS *****/
    $qtd_colunas_resultados = 5; //number of columns in the table of results
    $qtd_resultados = 70;

    //dealing with simple quote of back slash in the file name
    if (preg_match("/'/",$img_consulta)) {
        $img_consulta = str_replace("'","''",$img_consulta);
        $img_consulta = str_replace("\\","",$img_consulta);
    }

    $consulta = "SELECT * FROM (SELECT fv2, distance FROM distance WHERE idexperiment=".$id_experimento." AND iddescriptor='".$id_descritor."' ";
    $consulta.= "AND fv1='".$img_consulta."' ORDER BY distance LIMIT ".$qtd_resultados.") AS interna ORDER BY distance,fv2";
    $result = pg_query($consulta) or die('Query failed: ' . pg_last_error());

?>

<html>
<head>
  <title>Eva tool - Query results</title>
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


    <h2>Eva tool - Query results</h2>

    <table cellspacing="1" cellpadding="4" align="center" class="consulta">
      <tr>
      <td>Descriptor responsible for the retrieved images: 
        <h2>
        <? echo strtoupper($id_descritor);  ?>
        </h2>
        <br/><br/><br/><br/><hr size="1" color="#A4B4CD" width="90%"/><br/>
        <ul>
        <li>View images retrieved by other descriptor:
        <?$action = "view_images_bd.php"?>
        <form method="get" action="<?=$action?>">
            <select name="descritor" size="1">
                <!-- this list depends on the experiment - get descriptors used -->
                <option value="">--select--</option>
                <?
                echo "\n";
                foreach ($descritores as $desc) {
                    echo "\t\t<option value=\"".$desc."\">".$desc."</option>\n";
                }
                ?>
            </select>

            <input type="hidden" name="query_img" value="<?=$img_consulta?>"/>
            <input type="submit" name="Submit" value="OK"/>
        </form></li>
        <br/><br/>
        <li>
        <a href="../ver_experimentos_realizados.php">View other experiments</a>
        </li>
        </ul>
         </td>
      <td>
           Query image:<br/>
           <img src="<?=$img_consulta_file?>" alt="<?=$img_consulta?>" border="1" align="middle" style=\"max-width:300px;\" height=200/>
           <br/><br/>
           Name: <br/><?=$img_consulta?><br/>
           <!-- Classe: <?//=$_GET['class']?>-->

            <!-- VERIFICATION OF THE QUERY LIST -->
            <?
                //if there is query list, creates the combo box
                if ($lista_consultas) {
            ?>
            <br/><br/>
            <hr size="1" color="#A4B4CD" width="90%"/>
            Select another query image:
            <form method="get" action="<?=$action?>">
                <select name="query_img" size="1">
                    <option value="">--select--</option>
                    <?
                    echo "\n";
                    foreach ($lista_consultas as $lista_item) {
                        //image name should be only file name, no path included
                        $lista_item_nome = explode("/",$lista_item);
                        $lista_item_dir = $lista_item_nome[count($lista_item_nome)-2];
                        $lista_item_nome = $lista_item_nome[count($lista_item_nome)-1];

                        echo "\t\t<option value=\"".$lista_item."\">".$lista_item_dir."/".$lista_item_nome."</option>\n";
                    }
                    ?>
                </select>
                <input type="submit" name="Submit" value="OK"/>
            </form>
            <?
                }
            ?>
            <!-- END - VERIFICATION OF THE QUERY LIST -->

         </td></tr>
    </table>
    <br/><br/>

    <table cellspacing="3" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTS</td></tr>
    <tr>
<?

    $cont=1;
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {

        //file name is adjusted for the image database local directory
        $img_file = AdjustImageSource($line[fv2]);

        //image name should be only file name, no path included
        $img_name = explode("/",$img_file);
        $img_name = $img_name[count($img_name)-1];

        //image name
        echo "<td>";
        if (!$lista_consultas) { //if there is not query list, all images are hyperlinks
            echo "  <a href=\"view_images_bd.php?query_img=".$line[fv2]."\">";
        }
        echo "    <img src=\"".$img_file."\" alt=\"".$img_name."\" border=\"1\" style=\"max-width:180px;\" height=130/>";
        if (!$lista_consultas) {
            echo "</a>";
        }
        echo "<br/><br/>".$img_name;
        echo "<br/>".$line[distance]."<br/>";
        echo "</td>\n";


        if (!($cont%$qtd_colunas_resultados)) {
            echo "</tr>\n<tr>";
        }
        $cont++;
    }

    // Free resultset
    pg_free_result($result);

    // Closing connection
    pg_close($dbconn);

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
