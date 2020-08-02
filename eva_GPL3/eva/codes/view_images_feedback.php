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
include "../util.php";

//session expires after 120 minutes of inactivity
ini_set('session.gc_maxlifetime', 120*60);
session_start();

$DEBUG = 0;

//class definition used for the final list
class Imagem{
    var $fv2;
    var $descritores; //vector with index --> $id_descritor and
                                  //value --> string with positions separated by coma
                      
}

function CriaListaFinal($conn, $id_experimento, $descritores, $img_consulta, $qtd_resultados) {
    $lista_final = array();
    $cont_final = 1;
    $DEBUG = $GLOBALS[DEBUG];  //debug value comes from outside of the function

    foreach ($descritores as $id_descritor) {
        $consulta = "SELECT * FROM (SELECT fv2, distance FROM distance WHERE idexperiment=".$id_experimento." AND iddescriptor='".$id_descritor."' ";
        $consulta.= "AND fv1='".trim($img_consulta)."' ORDER BY distance LIMIT ".$qtd_resultados.") AS interna ORDER BY distance,fv2";
        if ($DEBUG) echo "consulta=".$consulta."<br>";
        $result = pg_query($conn, $consulta) or die('Query failed: ' . pg_last_error());
        //Creates a list with top 30 images of the descriptor
        //below, we have the top 30 images of each descriptor, just need to put them in a list
        if ($DEBUG) {
            echo "LIST FROM DESCRIPTOR: ".$id_descritor.":<br/>";
            echo "<ul>";
        }

        //counter for the vector of each descriptor
        $cont_desc = 1;
        while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
            //list of each descriptor

            //inserting all fv2's in the final list without repetitions
            $flag = 0;
            //scans the list of values
            foreach($lista_final as $fv2){
                //if this fv2 is already in the list, breaks the loop with flag=1;
                if ((isset($fv2->fv2)) && (strcmp($fv2->fv2,$line['fv2'])==0)) {
                    //using as index the descriptor id and saving the positions
                    $fv2->descritores[$id_descritor].= $cont_desc; 
                    $flag=1;
                    break;
                }

            }

            //$flag is 0 if fv2 was not found, and inserts the value in another position in the vector
            if ($flag == 0){
                $lista_final[$cont_final] = new Imagem;
                $lista_final[$cont_final]->fv2 = $line['fv2'];
                //using as index the descriptor id and saving the positions
                $lista_final[$cont_final]->descritores = array($id_descritor => $cont_desc);
                $cont_final++;
            }
            //incrementing the counter of positions
            $cont_desc++;
            if ($DEBUG) echo "<li>".$line['fv2']."</li>";
        }
        if ($DEBUG) {
            echo "</ul>";
            echo "<br/>";
        }
    }
    pg_free_result($result);
    return $lista_final;
}

function CalculaPrecision($lista_final, &$p10, &$p20, &$p30) {
    $DEBUG = $GLOBALS[DEBUG];  //debug value comes from outsite the function

    if ($DEBUG) {
        echo "<hr/>SELECTED<pre>";
        print_r($_POST["selecionadas"]);
        echo "</pre><hr/>";
    }

    foreach ($_POST["selecionadas"] as $selecionada) {
        if ($DEBUG) echo "evaluating selected: <b>".($selecionada-1)."</b><br/><blockquote>";
        foreach ($lista_final[($selecionada-1)]->descritores as $descritor => $posicao){
            if ($DEBUG) echo "selected->descriptors: ".$descritor."-".$posicao."<br/>";
                if ($posicao<=10){
                    if (isset($p10[$descritor])){
                        $p10[$descritor]++;
                    }else{
                        $p10[$descritor] = 1;
                    }
                    if (isset($p20[$descritor])){
                        $p20[$descritor]++;
                    }else{
                        $p20[$descritor] = 1;
                    }
                    if (isset($p30[$descritor])){
                        $p30[$descritor]++;
                    }else{
                        $p30[$descritor] = 1;
                    }
                }elseif ($posicao<=20){
                    if (isset($p20[$descritor])){
                        $p20[$descritor]++;
                    }else{
                        $p20[$descritor] = 1;
                    }
                    if (isset($p30[$descritor])){
                        $p30[$descritor]++;
                    }else{
                        $p30[$descritor] = 1;
                    }
                }elseif ($posicao<=30){
                    if (isset($p30[$descritor])){
                        $p30[$descritor]++;
                    }else{
                        $p30[$descritor] = 1;
                    }
                }
        }
        if ($DEBUG) echo "</blockquote>";
    }
}

function InserePrecisionBD($conn, $descritores, $p10, $p20, $p30) {
    $DEBUG = $GLOBALS[DEBUG];  //debug value comes from outsite the function

    //precision is computed from the total number of images per descriptor (10,20,30)

    if ($DEBUG) {
        echo "<hr/>";
        echo "PRECISION VALUES COMPUTED FOR EACH DESCRIPTOR<br/>";
        echo "query image: ".$_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']]."<br>";
    }
    foreach($descritores as $desc) {
        if ($DEBUG) {
            echo "descriptor=".$desc."<br/>";
            echo "p10 = ".($p10[$desc]/10)."<br/>";
            echo "p20 = ".($p20[$desc]/20)."<br/>";
            echo "p30 = ".($p30[$desc]/30)."<br/>";
        }

        if ($_SESSION['lista_consultas_indice'] < $_POST['proxima_consulta']) {
            $insere = "INSERT INTO experimentuserevaluation (idexperiment,iddescriptor,fvquery,p10,p20,p25,insertion_timestamp, user_email) VALUES ";
            $insere.= "(".$_SESSION['id_experimento'].",'".$desc."','".($_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']])."',".($p10[$desc]/10).",".($p20[$desc]/20).",".($p30[$desc]/30).",CURRENT_TIMESTAMP,'".$_SESSION['user_email']."')";
            if ($DEBUG) echo "insere=".$insere."<br/>";
            $result_insere = pg_query($conn, $insere) or die('Query failed: ' . pg_last_error());
            pg_free_result($result_insere);
            if ($DEBUG) echo "<hr/>";
        } else {
            if ($DEBUG) echo "Reload/refresh used or browser's back button used!<br/>";
            //in this case, avoids inserting the data again in the database
        }
    }
}

function PegaExperimentoDescritores() {
    $DEBUG = $GLOBALS[DEBUG];  //debug value comes from outsite the function

    if ($DEBUG) {
        echo "Entered in the IF POST, but already have the following data:<br/>";
        echo "id_experimento=".$_SESSION['id_experimento']."<br/>";
        echo "Descriptors<pre>";
        print_r($_SESSION['descritores']);
        echo "</pre>";
    }

    //gets only the keys of each position in the POST
    $post_keys = array_keys($_POST);
    $i=0;
    $desc_count=0;
    foreach ($_POST as $post) {
        //"desc" key indicates that the current position is a descriptor id
        if (preg_match("/desc/",$post_keys[$i]) > 0) {
            $descritores[$desc_count] = $post;
            $desc_count++;
        } else if (preg_match("/exp/",$post_keys[$i]) > 0) {
            $id_experimento = $post;
        }
        $i++;
    }
    //register the data in the SESSION to avoid losing information when changing descriptor or query image
    $_SESSION['id_experimento'] = $id_experimento;
    if ($desc_count > 0)
        $_SESSION['descritores'] = $descritores;

    //registers the evaluator's e-mail in the SESSION 
    if ($_POST['email']) {
        $_SESSION['user_email'] = $_POST['email'];
    } else if (!$_SESSION['user_email']) { //if there is no e-mail in the POST and there is no SESSION yet, register a default e-mail
                                           //this happens if this page is accessed from the list of executed experiments
        $_SESSION['user_email'] = "padrao@padrao.com";
    }
}

function PegaListaConsultas($id_experimento) {
    $DEBUG = $GLOBALS[DEBUG];  //debug value comes from outsite the function

    //check if there is a query list (file)
    if (file_exists("../results/".$id_experimento."/query_list.txt")) {

        //creates a combo box with the query images
        $arq_query_list = file("../results/".$id_experimento."/query_list.txt");
        $lista_consultas = explode("##",$arq_query_list[0]);

        //Remember to advance in this list every time the user concludes the evaluation of a given query image
        //Maybe, if we store the query list in the SESSION, it will be simpler

        $_SESSION['lista_consultas'] = $lista_consultas;

        //indicates the index of the current query image
        $_SESSION['lista_consultas_indice'] = 0;

    } else {
        echo "ERROR: There is not query list file!<br/>"
        exit(1);
    }
}

?>
<html>
<head>
  <title>Eva tool - User-oriented evaluation</title>
  <link rel="stylesheet" type="text/css" href="../liquidcorners.css">
  <link rel="SHORTCUT ICON" href="favicon.ico"/>
  <link href="../estilo.css" rel="stylesheet" />
  <link href="estilo.css" rel="stylesheet" />

  <script language="Javascript">
    function swap( id ) {
        //changes the cell class
        classe = ( document.getElementById( id ).className == 'unselected' ) ? 'selected' : 'unselected';
        document.getElementById( id ).className = classe;

        selected = document.imagens['selecionadas[]'];
        selected[id-1].checked = (selected[id-1].checked == true)?false:true;
    }
  </script>
  <style>
    .unselected {background-color:#15de00;}
    .selected  {}
    .checkHidden {display: none;}
  </style>
</head>
<body>
    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->
<?php
    $fim_exp = 0; //indicates end of experiment

    $dbconn = connect();

    if ($DEBUG) {
        if ($_SESSION) echo "has session<br/>";
        else echo "no session<br/>";
    }

    //if the user passed at least for the 1st page and there is no SESSION, it is because SESSION expired
    if (isset($_POST["proxima_consulta"]) && !$_SESSION) {
        echo "Session expired!<br/>Impossible to continue the evaluation!<br/>";
        exit();
    }

    ////////////////////////////////////////////////////////////////
    ///////////TREATMENT OF SELECTED IMAGES ////////////////////////
    if (isset($_POST["selecionadas"])) {
        if ($DEBUG) {
            if ($_SESSION) echo "POST has selected images and SESSION exists, so can continue...";
            else  echo "POST has selected images but there is no SESSION!";
        }

        if ($DEBUG) {
            echo "<hr/>";
            echo "list of selected images";
            echo "<pre>";
            print_r($_POST["selecionadas"]);
            echo "</pre>";
        }
        $lista_final = $_SESSION["lista_final"];
        if ($DEBUG) {
            echo "<hr/>FINAL LIST USED TO CHECK THE SELECTED IMAGES";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
        }

        //each one with [$id_descritor] as index
        $p10 = array(); $p20 = array(); $p30 = array();
        CalculaPrecision($lista_final, &$p10, &$p20, &$p30); //computes the Precision metric of all descriptors used
        InserePrecisionBD($dbconn, $_SESSION['descritores'], $p10, $p20, $p30); //inserts Precision values in the database
    } else {
        if ($_SESSION['lista_consultas']) { //if query list exists, it is because already evaluated some query image
            if ($DEBUG) echo "none selected but there is a query list in the session!<br>";
            InserePrecisionBD($dbconn, $_SESSION['descritores'], $p10, $p20, $p30); //inserts zero as Precision in the dataset
        } else {
            if ($DEBUG) echo "none selected and there is NO query list in the session!<br>";
        }
    }
    ///////////TREATMENT OF SELECTED IMAGES - END /////////////////
    ///////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////
    //////////VERIFYING THE POST - WHEN COMING FROM INITIAL PAGE ///
    //se veio de uma pagina diferente desta
    if ($_POST && (!$_SESSION['id_experimento'] || !$_SESSION['descritores'])) {
        PegaExperimentoDescritores(); //se eh a primeira consulta, pega dados do post e registra na sessao
    }
    if ($DEBUG) echo "email=".$_SESSION['user_email']."<br/>";

    //usa variaveis locais para valores importantes
    $id_experimento = $_SESSION['id_experimento'];
    $descritores = $_SESSION['descritores'];
    if ($DEBUG) {
        echo "id_experimento = ".$id_experimento."<br/>";
        echo "qtd_descritores = ".count($descritores)."<br/>";
    }
    /////VERIFYING THE POST - WHEN COMING FROM INITIAL PAGE - END //
    ////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////
    ///////VERIFYING THE QUERY LIST ////////////////////////////////
    //checking if the query list is already in the session
    if (!$_SESSION['lista_consultas']) {
        PegaListaConsultas($id_experimento); //register the list of query images in the session (from file)
                                             //as well as the index of the current query images being evaluated
    } else {
        //as the query list is already in the session, copies the index received by POST to the current index
        if ($_POST['proxima_consulta']) {

            $_SESSION['lista_consultas_indice'] = $_POST['proxima_consulta'];  //copying

            if ($DEBUG) {
                echo "proxima_consulta=".$_POST['proxima_consulta']."<br/>";
                echo "qtd consultas=".count($_SESSION['lista_consultas'])."<br/>";
            }

            if ($_POST['proxima_consulta'] == count($_SESSION['lista_consultas'])) { //check if it is the last query image
                $fim_exp = 1;
            }
        }
    }
    ///////VERIFYING THE QUERY LIST - END //////////////////////////
    ////////////////////////////////////////////////////////////////
    
    if ($DEBUG) echo "EXPERIMENT END = ".$fim_exp."<br>";
    if (!$fim_exp) { //if the experiment has not finished yet, creates the page

        $lista_consultas = $_SESSION['lista_consultas'];

        //gets the query images considering the index in the session
        $img_consulta = $lista_consultas[$_SESSION['lista_consultas_indice']];
        $img_consulta_file = AdjustImageSource($img_consulta);  

        //dealing with single quotes of back slash in the file name
        if (preg_match("/'/",$img_consulta)) {
            $img_consulta = str_replace("'","''",$img_consulta);
            $img_consulta = str_replace("\\","",$img_consulta);
        }

        /*****VISUALIZATION PARAMETERS*****/
        $qtd_colunas_resultados = 5; //number of columns in the table of results
        $qtd_resultados = 30;

        ///////CREATING THE COMBINED LIST OF DESCRIPTORS /////////////////
        $lista_final = CriaListaFinal($dbconn, $id_experimento, $descritores, $img_consulta, $qtd_resultados);
        //$_SESSION["lista_final"] = $lista_final; //inserting the final list in the session
        if ($DEBUG) {
            //PRINTING THE FINAL LIST TO CHECK IF IT WAS CORRECTLY CREATED
            echo "Final list of descriptors (lista_final -> size=".count($lista_final)."):<br>";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
            echo "<hr/>";
        }

        //creates a list of random indices
        srand((double) microtime()*1000000);
        $indices = range(1,count($lista_final)); //creates a list of sequential numbers from 1 to the size of the final list
        shuffle($lista_final); //shuffles the list - ATTENTION: shuffle makes the array to start from zero
                                                //this has impact on the computation of the selected images
        if ($DEBUG) {
            echo "SHUFFLED FINAL LIST:<br>";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
            echo "<hr/>";
        }
        //inserting the shuffled final list in the session
        $_SESSION["lista_final"] = $lista_final;

?>
    <h2>User-oriented evaluation</h2>
    <table cellspacing="1" cellpadding="4" align="center" class="consulta">
      <tr><td>Evaluating query <?=$_SESSION['lista_consultas_indice']+1?> of <?=count($_SESSION['lista_consultas'])?></td></tr>
      <tr>
      <td>
           Query image:<br/>
           <img src="<?=$img_consulta_file?>" alt="<?=$img_consulta?>" border="1" align="middle" style=\"max-width:300px;\" height=200/>
           <br/><br/>
           <? //preparing query image name to show only file name
               $img_temp_name = explode("/", $img_consulta);
               $img_temp_name = $img_temp_name[count($img_temp_name)-1];
           ?>
           Name: <br/><?=$img_temp_name//$img_consulta?><br/>
         </td></tr> 
   </table>
    <br/><br/>
    <form action="view_images_feedback.php" method="post" name="imagens">
        <input type="hidden" name="id_experimento" value="<?=$id_experimento;?>">
        <input type="hidden" name="consulta_anterior" value="<?=$img_consulta;?>">
    <table cellspacing="3" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTS</td></tr>
    <tr>
<?
        $cont=1;
        //this loop should scan all images in the final list; and should not use info from the database, as the data in the database were already obtained above
        $cont_lista_final = 1;
        foreach ($lista_final as $fv2) {
            $img_file = AdjustImageSource($fv2->fv2);
            //image name should be only the file name, without the full path
            $img_name = explode("/",$img_file);
            $img_name = $img_name[count($img_name)-1];

            echo "<td id=\"".$cont_lista_final."\">";
            echo "    <img src=\"".$img_file."\" alt=\"".$img_name."\" border=\"1\" style=\"max-width:180px;\" height=130 onClick=\"swap(".$cont_lista_final.")\"/><br/>";
            echo "    <input type=\"checkbox\" name=\"selecionadas[]\" value=\"".$cont_lista_final."\" width=\"100\" class=\"checkHidden\"/>";
            echo "<br/>".$img_name;
            if ($DEBUG) {
                echo "<br/>descriptors that retrieved this image (id and position):<br/>";
                echo "<pre>";
                print_r($fv2->descritores);
                echo "</pre>";
            }
            //echo "<br/>".$line[distance]."<br/>"; //distance value is not shown currently
            echo "</td>\n";
            if (!($cont%$qtd_colunas_resultados)) {
                echo "</tr>\n<tr>";
            }
            $cont++;
            $cont_lista_final++;
        }
?>
        </tr>
        <tr>
            <td colspan="<?=$qtd_colunas_resultados; ?>">
            <?
                //checks if there is a next query or if it has already visited all queries in the file
                if (!(($_SESSION['lista_consultas_indice']+1) >= count($_SESSION['lista_consultas']))) {
            ?>
                    <input type="hidden" name="proxima_consulta" value="<?=($_SESSION['lista_consultas_indice']+1)?>"/>
                    <input type="submit" value="OK and go to the next query image" onClick="javascript:this.value='Please wait...';">
            <?
                } else {
            ?>
                    <input type="hidden" name="proxima_consulta" value="<?=($_SESSION['lista_consultas_indice']+1)?>"/>
                    <input type="submit" value="OK and finish!">
            <?
                }
            ?>
            </td>
        </tr>
    </table>
    </form>
<?
    } else {
        echo "<center><h2>LAST QUERY IMAGE EVALUATED!<br/>END OF THE EXPERIMENT!<br/>THANK YOU!<br/><br/></h2></center>";
    }
?>
    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    </div>
    <!--************************ BORDAS ARREDONDADAS! ************************-->
</body>
</html>

<?
    //close connection
    pg_close($dbconn);
?>
