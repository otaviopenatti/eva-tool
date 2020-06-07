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

    //Variaveis com os caminhos dos arquivos fv e das imagens
    $path_distancias = "/exp/otavio/results/".$id_experimento."/distances_".$id_descritor."_comClasses.txt";
    //$path_distancias = "/exp/otavio/results/185/distances_bic_comClasses.txt";


    //caso GET esteja vazio, coloca imagem de consulta padrao
    if ($_GET['query_img']) {
        $img_consulta = $_GET['query_img'];
    } else {
        $img_consulta = "/exp/otavio/img_databases/yahoo_2000/jpg00111.jpg";
    }

    //arquivo da imagem de consulta - depende do valor de $img_consulta
    $img_consulta_file = explode("/exp/otavio/", $img_consulta);
    $img_consulta_file = "../".$img_consulta_file[1];


    /*****PARAMETROS DE VISUALIZACAO*****/
    //Quantidade de colunas na tabela de resultados
    $qtd_colunas_resultados = 5;
    $qtd_resultados = 70;


    //$consulta = "cat ".$path_distancias." | grep \"^".$img_consulta."\" | head -n ".$qtd_resultados;
    //como o sort do shell nao funcionou muito bem, o head teve que ser tirado
    $consulta = "cat ".$path_distancias." | grep \"^".$img_consulta."\"";

    exec($consulta, $output);

    //precisa ordenar os resultados!!!
    //usar array_multisort()
    //array_multisort($output, SORT_ASC);

    if (!$output) {
        echo "<td><h1>Erro nos resultados! <br/> Avise o administrador do sistema!</h1></td>";
        exit(1);
    }

/*
    echo "Output<pre>";
    print_r($output);
    echo "</pre>";
*/

    //separa as linhas e colunas do array
    $cont=0;
    foreach ($output as $linha) {
        $array_final[$cont] = split("\t", $linha);
        $array_consulta[$cont] = $array_final[$cont][0];
        $array_result[$cont] = $array_final[$cont][1];
        $array_dist[$cont] = $array_final[$cont][2];
        $cont++;

        //coluna 0 = img_consulta
        //coluna 1 = img_resultado
        //coluna 2 = distancia entre elas

    }
    //ordena o array final pela coluna da distancia
    array_multisort($array_dist, SORT_ASC, $array_final);

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
                <!-- esta lista depende do experimento - pegar descritores usados -->
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
        <a href="../ver_experimentos_realizados.php">Ver outros experimentos realizados</a>
        </li>
        </ul>
         </td>
      <td>
           Imagem de consulta:<br/>
           <img src="<?=$img_consulta_file?>" alt="<?=$img_consulta?>" border="1" align="middle" style=\"max-width:300px;\" height=200/>
           <br/><br/>
           Nome: <br/><?=$img_consulta?><br/>
           <!-- Classe: <?//=$_GET['class']?>-->
         </td></tr>
    </table>
    <br/><br/>

    <table cellspacing="1" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTADOS</td></tr>
    <tr>
<?


    /*
    echo "<hr/>ArrayFinal<pre>";
    print_r($array_final);
    echo "</pre>";
    */

    $cont=1;
    foreach ($array_final as $linha) {

        //nome do arquivo eh ajustado para o dir local da base de imagens
        //remove-se a parte comum, a todas as imagens...
        $img_file = explode("/exp/otavio/",$linha[1]);
        $img_file = "../".$img_file[1];

        //o nome da imagem eh apenas o nome do arquivo sem a estrutura de dir
        $img_name = explode("/",$img_file);
        $img_name = $img_name[count($img_name)-1];

        //substitui espacos por %20 nos nomes de arquivos das imagens
        $img_file = str_replace(" ", "%20", $img_file);

        //nome da imagem
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
