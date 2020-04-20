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
/*
//set the cache limiter to 'private'
session_cache_limiter('private');
$cache_limiter = session_cache_limiter();

//set the cache expire to 30 minutes
session_cache_expire(30);
$cache_expire = session_cache_expire();
*/



session_start();
/*
//echo "The cache limiter is now set to $cache_limiter<br/>";
//echo "The cached session pages expire after $cache_expire minutes";
*/

/*
$ cat novo2_id2_distancias_teste12.txt | grep "^class2/class1_10_689_000" | sort -k 3 | head -n 80
                                               ^nome da img de consulta                          ^qtd de resultados
*/




/*
Parametros: id_experimento, id_base, id_descritor, nome_base
*/
    /*
    echo "POST<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "count=".count($_POST)."<br/>";
    echo "keys=<pre>";
    print_r(array_keys($_POST));
    echo "</pre>";
    */

    //se veio de uma pagina diferente desta
    if ($_POST) {
        //pega apenas as chaves de cada posicao do POST
        $post_keys = array_keys($_POST);
        $i=0;
        $desc_count=0;
        foreach ($_POST as $post) {
            //se a chave for "desc" eh pq a posicao atual eh o id de um descritor
            if (preg_match("/desc/",$post_keys[$i]) > 0) {
                $descritores[$desc_count] = $post;
                $desc_count++;
            } else if (preg_match("/exp/",$post_keys[$i]) > 0) {
                $id_experimento = $post;
            }
            $i++;
        }
        //registra dados na secao para evitar perda qdo muda descritor ou imagem de consulta
        $_SESSION['id_experimento'] = $id_experimento;
        $_SESSION['descritores'] = $descritores;
    }

//     echo "id_experimento = ".$id_experimento."<br/>";
//     echo "descritores = <pre>";
//     print_r($descritores);
//     echo "</pre>";
//     echo "descritores[0] = ".$descritores[0]."<br/>";

    //usa variaveis locais para valores importantes
    $id_experimento = $_SESSION['id_experimento'];
    $descritores = $_SESSION['descritores'];

//     echo "<hr/>id_experimento = ".$id_experimento."<br/>";
//     echo "descritores = <pre>";
//     print_r($descritores);
//     echo "</pre>";
//     echo "descritores[0] = ".$descritores[0]."<br/>";

    //verifica qual o descritor escolhido
    if ($_GET['descritor']) {
        $id_descritor = $_GET['descritor'];
    } else if ($_SESSION['id_descritor']) {
        $id_descritor = $_SESSION['id_descritor'];
    } else {
        $id_descritor = $descritores[0];
    }
    //registra na sessao o descritor em uso
    $_SESSION['id_descritor'] = $id_descritor;

    //verifica se existe lista de imagens de consulta
    if (file_exists("../results/".$id_experimento."/query_list.txt")) {
        //cria um combo_box com as imgs de consulta da lista
        $arq_query_list = file("../results/".$id_experimento."/query_list.txt");
        $lista_consultas = explode("##",$arq_query_list[0]);
        //echo "existe lista de consultas<br>";
    }

    //CONEXAO COM O BD
    include "../util.php";
    $dbconn = connect();

    //echo "id_experimento=".$id_experimento."<br>";

    //caso GET esteja vazio, coloca imagem de consulta padrao
    if ($_GET['query_img']) {
        $img_consulta = $_GET['query_img'];
        //echo "img_consulta_get=".$img_consulta."<br>";
        //echo "ja tinha uma imagem de consulta no GET<br/>";
    } else if (file_exists("../results/".$id_experimento."/query_list.txt")) {
        //se houver arquivo de consultas pega a primeira consulta dele, pois é mais rápido do que pegar do bd
        $img_consulta = $lista_consultas[0];
        //echo "pegou img de consulta do arquivo query_list.txt - ".$img_consulta."<br/>";
    } else {
        //echo "pegou a primeira consulta do banco!!!<br/>";
        //img de consulta padrao eh a primeira encontrada como fv1 na tabela de distancias
        //!$img_consulta = "/exp/otavio/img_databases/yahoo_2000/jpg00111.jpg";

        $consulta_inicial = "SELECT fv1 FROM distance WHERE idexperiment=".$id_experimento." LIMIT 1";
        $result_inicial = pg_query($consulta_inicial) or die('Query failed: ' . pg_last_error());
        $line = pg_fetch_array($result_inicial, null, PGSQL_ASSOC);
        $img_consulta = $line[fv1];

        // Free resultset
        pg_free_result($result_inicial);
    }

    //arquivo da imagem de consulta - depende do valor de $img_consulta
    //ajusta o caminho do arquivo
    $img_consulta_file = AdjustImageSource($img_consulta);


    /*****PARAMETROS DE VISUALIZACAO*****/
    //Quantidade de colunas na tabela de resultados
    $qtd_colunas_resultados = 5;
    $qtd_resultados = 70;

    //echo "img_consulta=".$img_consulta."<br/>";

    //Trata casos com imgs com aspas simples ou barra ao contrario no nome do arquivo
    if (preg_match("/'/",$img_consulta)) {
        $img_consulta = str_replace("'","''",$img_consulta);
        $img_consulta = str_replace("\\","",$img_consulta);
    }
    //echo "img_consulta_ok=".$img_consulta."<br/>";
    //$img_consulta = str_replace("\\","",$img_consulta);

    //a consulta acima estava bem mais lenta
    $consulta = "SELECT * FROM (SELECT fv2, distance FROM distance WHERE idexperiment=".$id_experimento." AND iddescriptor='".$id_descritor."' ";
    $consulta.= "AND fv1='".$img_consulta."' ORDER BY distance LIMIT ".$qtd_resultados.") AS interna ORDER BY distance,fv2";
    //echo "consulta: ".$consulta;
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
                <!-- esta lista depende do experimento - pegar descritores usados -->
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

            <!-- VERIFICACAO DA LISTA DE IMGS DE CONSULTA -->
            <?
                //se existe lista de consultas, monta combo box
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
                        //o nome da imagem eh apenas o nome do arquivo sem a estrutura de dir
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
            <!-- FIM - VERIFICACAO DA LISTA DE IMGS DE CONSULTA -->

         </td></tr>
    </table>
    <br/><br/>

    <table cellspacing="3" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTS</td></tr>
    <tr>
<?


    /*
    echo "<hr/>ArrayFinal<pre>";
    print_r($array_final);
    echo "</pre>";
    */

    $cont=1;
    while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
    //!foreach ($array_final as $linha) {

        //nome do arquivo eh ajustado para o dir local da base de imagens
        $img_file = AdjustImageSource($line[fv2]);
        //echo "img_file=".$img_file."<br>";

        //o nome da imagem eh apenas o nome do arquivo sem a estrutura de dir
        $img_name = explode("/",$img_file);
        $img_name = $img_name[count($img_name)-1];

        //substitui espacos por %20 nos nomes de arquivos das imagens
        //$img_file = str_replace(" ", "%20", $img_file);

        //nome da imagem
        echo "<td>";
        if (!$lista_consultas) { //se nao usou uma lista de consultas, todas as imgs sao links
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
