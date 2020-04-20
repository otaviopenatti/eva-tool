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
include "../util.php";

/*
OBSERVACAO!!!
Passei a usar Precision@30 no lugar de Precision@25 pois, segundo o artigo do BIC, 30 eh um 
numero usado em conferencias TREC (similar ao pooling method)
*/

//indica que a sessao ira expirar depois de 120 min de inatividade
ini_set('session.gc_maxlifetime', 120*60);
session_start();

//echo "SESSION.GC_MAXLIFETIME=".ini_get('session.gc_maxlifetime')."<br/>";
//echo "SESSION_CACHE_LIMITER =".session_cache_limiter();

$DEBUG = 0;

//definicao da classe utilizada para a lista final
class Imagem{
    var $fv2;
    var $descritores; //vetor com o indice sendo o $id_descritor e valor sendo
                //uma string com as posicoes separadas por virgula
}

function CriaListaFinal($conn, $id_experimento, $descritores, $img_consulta, $qtd_resultados) {
    $lista_final = array();
    $cont_final = 1;
    $DEBUG = $GLOBALS[DEBUG];  //pega o valor do debug de fora da funcao

    foreach ($descritores as $id_descritor) {
        $consulta = "SELECT * FROM (SELECT fv2, distance FROM distance WHERE idexperiment=".$id_experimento." AND iddescriptor='".$id_descritor."' ";
        $consulta.= "AND fv1='".trim($img_consulta)."' ORDER BY distance LIMIT ".$qtd_resultados.") AS interna ORDER BY distance,fv2";
        if ($DEBUG) echo "consulta=".$consulta."<br>";
        $result = pg_query($conn, $consulta) or die('Query failed: ' . pg_last_error());
        //CRIAR UMA LISTA COM AS 30 PRIMEIRAS IMGS DO DESCRITOR
        //ABAIXO SAO LISTADAS AS 30 PRIMEIRAS IMGS DO DESCRITOR, BASTA COLOCA-LAS NUMA LISTA
        if ($DEBUG) {
            echo "LISTA DO DESCRITOR ".$id_descritor.":<br/>";
            echo "<ul>";
        }

        //contador do vetor de cada descritor
        $cont_desc = 1;
        while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
            //lista de cada descritor


            //colocando todos os fv2's na lista final sem repeticoes
            $flag = 0;
            //percorre a lista de valores
            foreach($lista_final as $fv2){
                //Caso este fv2 ja esteja na lista sai do loop com flag = 1;
                if ((isset($fv2->fv2)) && (strcmp($fv2->fv2,$line['fv2'])==0)) {
                    //utilizando como indice o id do descritor e guardando as posicoes...
                    $fv2->descritores[$id_descritor].= $cont_desc;//verificar como concatenar os dados aqui!!!!
                    $flag=1;
                    break;
                }

            }

            //$flag = 0 no caso de nao ter encontrado, e insere o valor em mais uma posicao do vetor
            if ($flag == 0){
                $lista_final[$cont_final] = new Imagem;
                $lista_final[$cont_final]->fv2 = $line['fv2'];
                //utilizando como indice o id do descritor e guardando as posicoes...
                $lista_final[$cont_final]->descritores = array($id_descritor => $cont_desc);
                $cont_final++;
            }
            //incrementando o contador de posicoes...
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
    $DEBUG = $GLOBALS[DEBUG];  //pega o valor do debug de fora da funcao
    //$DEBUG = 1;

    if ($DEBUG) {
        echo "<hr/>SELECIONADAS<pre>";
        print_r($_POST["selecionadas"]);
        echo "</pre><hr/>";
    }

    foreach ($_POST["selecionadas"] as $selecionada) {
        if ($DEBUG) echo "avaliando selecionada: <b>".($selecionada-1)."</b><br/><blockquote>";
        //foreach ($lista_final[$selecionada]->descritores as $descritor => $lista_posicoes){
        foreach ($lista_final[($selecionada-1)]->descritores as $descritor => $posicao){
            if ($DEBUG) echo "selecionada->descritores: ".$descritor."-".$posicao."<br/>";
            //$posicoes = explode($lista_posicoes);
            //foreach($posicoes as $posicao){
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
            //}
        }
        if ($DEBUG) echo "</blockquote>";
    }
}

function InserePrecisionBD($conn, $descritores, $p10, $p20, $p30) {
    $DEBUG = $GLOBALS[DEBUG];  //pega o valor do debug de fora da funcao

    //precision calculado pelo numero total (10,20,30) de imagens de cada descritor

    if ($DEBUG) {
        echo "<hr/>";
        echo "VALORES DE PRECISION CALCULADOS PARA CADA DESCRITOR<br/>";
        echo "imagem considerada como consulta: ".$_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']]."<br>";
    }
    foreach($descritores as $desc) {
        if ($DEBUG) {
            echo "descritor=".$desc."<br/>";
            echo "p10 = ".($p10[$desc]/10)."<br/>";
            echo "p20 = ".($p20[$desc]/20)."<br/>";
            echo "p30 = ".($p30[$desc]/30)."<br/>";
        }

        //echo "indice atual:    ".$_SESSION['lista_consultas_indice']."<br/>";
        //echo "indice prox consulta: ".$_POST['proxima_consulta']."<br>";
        if ($_SESSION['lista_consultas_indice'] < $_POST['proxima_consulta']) {
        //if (!array_search($_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']],$_SESSION['lista_consultas_avaliadas'])) {
            $insere = "INSERT INTO experimentuserevaluation (idexperiment,iddescriptor,fvquery,p10,p20,p25,insertion_timestamp, user_email) VALUES ";
            $insere.= "(".$_SESSION['id_experimento'].",'".$desc."','".($_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']])."',".($p10[$desc]/10).",".($p20[$desc]/20).",".($p30[$desc]/30).",CURRENT_TIMESTAMP,'".$_SESSION['user_email']."')";
            if ($DEBUG) echo "insere=".$insere."<br/>";
            $result_insere = pg_query($conn, $insere) or die('Query failed: ' . pg_last_error());
            pg_free_result($result_insere);
            if ($DEBUG) echo "<hr/>";

            //atualiza a lista de consultas ja avaliadas - insere na lista a consulta avaliada
            /*if ($_SESSION['lista_consultas_avaliadas'][count($_SESSION['lista_consultas_avaliadas'])-1] != $_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']]) {
                array_push($_SESSION['lista_consultas_avaliadas'], $_SESSION['lista_consultas'][$_SESSION['lista_consultas_indice']]);
            }*/

        } else {
            if ($DEBUG) echo "VOCE DEU UM RELOAD NA PAGINA OU USOU O BOTAO DE VOLTAR DO BROWSER<br/>";
            //nesse caso evita que os dados sejam reinseridos no banco
        }
    }
}

function PegaExperimentoDescritores() {
    $DEBUG = $GLOBALS[DEBUG];  //pega o valor do debug de fora da funcao

    if ($DEBUG) {
        echo "Entrou no IF POST, mas ja tem os seguintes dados:<br/>";
        echo "id_experimento=".$_SESSION['id_experimento']."<br/>";
        echo "Descritores<pre>";
        print_r($_SESSION['descritores']);
        echo "</pre>";
    }

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
    if ($desc_count > 0)
        $_SESSION['descritores'] = $descritores;

    //registra email do avaliador na secao
    if ($_POST['email']) {
        $_SESSION['user_email'] = $_POST['email'];
    } else if (!$_SESSION['user_email']) { //se nao veio email por post e nao existe na secao ainda, registra email padrao
                                           //isto acontece qdo uso acesso este php pela lista de experimentos realizados
        $_SESSION['user_email'] = "padrao@padrao.com";
    }
}

function PegaListaConsultas($id_experimento) {
    $DEBUG = $GLOBALS[DEBUG];  //pega o valor do debug de fora da funcao

    //verifica se existe lista de imagens de consulta (em arquivo)
    if (file_exists("../results/".$id_experimento."/query_list.txt")) {

        //cria um combo_box com as imgs de consulta da lista
        $arq_query_list = file("../results/".$id_experimento."/query_list.txt");
        $lista_consultas = explode("##",$arq_query_list[0]);

        //LEMBRAR DE AVANCAR NESTA LISTA A CADA VEZ QUE O USUÁRIO TERMINAR DE AVALIAR OS RESULTADOS DE UMA DETERMINADA CONSULTA
        //TALVEZ, SE GUARDAR A LISTA NA SECAO, FIQUE MAIS SIMPLES.

        $_SESSION['lista_consultas'] = $lista_consultas;

        //indica o indice da imagem atual de consulta
        $_SESSION['lista_consultas_indice'] = 0;
        //$_SESSION['lista_consultas_avaliadas'] = array(); //usado para prevenir dupla insercao em caso de reload da pagina

    } else {
        echo "ERRO: nao existe arquivo com as imagens de consulta. Impossivel realizar avaliacao!<br/>";
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
        //altera classe da celula
        classe = ( document.getElementById( id ).className == 'unselected' ) ? 'selected' : 'unselected';
        document.getElementById( id ).className = classe;

        //altera checked
        selected = document.imagens['selecionadas[]'];
        //alert('checkvalue['+id+']='+selected[id-1].checked);
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
    $fim_exp = 0; //indica qdo o experimento acaba

    //CONEXAO COM O BD
    $dbconn = connect();

    if ($DEBUG) {
        if ($_SESSION) echo "possui sessao<br/>";
        else echo "sem sessao<br/>";
    }

    //Se ele passou pelo menos pela primeira pagina, mas nao existe sessao, eh pq a sessao expirou
    if (isset($_POST["proxima_consulta"]) && !$_SESSION) {
        echo "Sess&atilde;o expirou!<br/> Imposs&iacute;vel continuar avalia&ccedil;&atilde;o!<br/>";
        exit();
    }

    ////////////////////////////////////////////////////////////////
    ///////////TRATAMENTO DAS SELECIONADAS ////////////////////////
    if (isset($_POST["selecionadas"])) {
        if ($DEBUG) {
            if ($_SESSION) echo "veio post selecionadas e tem sessão, pode continuar...";
            else  echo "veio post selecionadas, mas nao tem sessao!";
        }

        if ($DEBUG) {
            echo "<hr/>";
            echo "lista de selecionadas";
            echo "<pre>";
            print_r($_POST["selecionadas"]);
            echo "</pre>";
        }
        $lista_final = $_SESSION["lista_final"];
        if ($DEBUG) {
            echo "<hr/>LISTA FINAL USADA PARA VERIFICAR AS SELECIONADAS";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
        }

        //cada um com o indice [$id_descritor]
        $p10 = array(); $p20 = array(); $p30 = array();
        CalculaPrecision($lista_final, &$p10, &$p20, &$p30); //calcula o precision de todos os descritores usados
        InserePrecisionBD($dbconn, $_SESSION['descritores'], $p10, $p20, $p30); //insere no bd os valores de precision calculados
    } else {
        //CASO NAO TENHA SELECIONADO NENHUMA, NAO ESTAVA INSERINDO NO BD. PRECISA INSERIR ZEROZERO
        if ($_SESSION['lista_consultas']) { //se ja existe a lista de consultas eh pq ja avaliou alguma consulta
            if ($DEBUG) echo "nao selecionou nenhuma mas possui uma lista na secao!!<br>";
            //insere zero nos precisions no bd
            InserePrecisionBD($dbconn, $_SESSION['descritores'], $p10, $p20, $p30); //insere no bd os precision iguais a zero
        } else {
            if ($DEBUG) echo "nao selecionou nenhuma e NAO possui a lista na secao!!<br>";
        }
    }
    ///////////TRATAMENTO DAS SELECIONADAS  - FIM//////////////////
    ///////////////////////////////////////////////////////////////


    ////////////////////////////////////////////////////////////////
    //////////VERIFICACAO DO POST - QDO VEM DA PAG INICIAL /////////
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
    //////VERIFICACAO DO POST - QDO VEM DA PAG INICIAL  - FIM ///////
    ////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////
    ///////VERIFICACAO DA LISTA DE CONSULTAS ///////////////////////
    //verifica se a lista de imagens de consulta ja esta na secao
    if (!$_SESSION['lista_consultas']) {
        PegaListaConsultas($id_experimento); //pega lista de imgs de consulta em arquivo e registra-a na sessao
                                             //assim como o indice da consulta atual sendo avaliada
    } else {
        //como a lista de consultas ja esta na secao, copia o indice recebido por POST para o indice atual
        if ($_POST['proxima_consulta']) {

            $_SESSION['lista_consultas_indice'] = $_POST['proxima_consulta'];  //realiza copia

            if ($DEBUG) {
                echo "proxima_consulta=".$_POST['proxima_consulta']."<br/>";
                echo "qtd consultas=".count($_SESSION['lista_consultas'])."<br/>";
            }

            if ($_POST['proxima_consulta'] == count($_SESSION['lista_consultas'])) { //verifica se chegou na ultima consulta
                $fim_exp = 1;
            }
        }
    }
    ///////VERIFICACAO DA LISTA DE CONSULTAS - FIM /////////////////
    ////////////////////////////////////////////////////////////////
    if ($DEBUG) echo "FIM_EXP=".$fim_exp."<br>";
    if (!$fim_exp) { //se o experimento ainda nao acabou, monta pagina

        $lista_consultas = $_SESSION['lista_consultas'];

        //pega a consulta considerando o indice da secao
        $img_consulta = $lista_consultas[$_SESSION['lista_consultas_indice']];
        $img_consulta_file = AdjustImageSource($img_consulta);  

        //Trata casos com imgs com aspas simples ou barra ao contrario no nome do arquivo
        if (preg_match("/'/",$img_consulta)) {
            $img_consulta = str_replace("'","''",$img_consulta);
            $img_consulta = str_replace("\\","",$img_consulta);
        }

        /*****PARAMETROS DE VISUALIZACAO*****/
        //Quantidade de colunas na tabela de resultados
        $qtd_colunas_resultados = 5;
        $qtd_resultados = 30; //MUDEI de 25 para 30 e alterei os nomes das variaveis $p25 para $p30

        //CONSULTA: para experimento em questao e considerando o descritor id_descritor, ordena os resultados pela distancia e...
        //...pega apenas as 'qtd_resultados' primeiras imagens; ordena entao estas imagens pela distancia e pelo nome.
        //dessa maneira fica mais rápido do que ordenar por distance e fv2 de uma só vez.
        //neste caso, considerar apenas as 30 primeiras imagens: qtd_resultados=30;
        //FAZER ISSO PARA CADA DESCRITOR DO EXPERIMENTO:

        ///////CRIANDO LISTA COMBINADA DOS DESCRITORES /////////////////
        //Para a lista final um vetor
        $lista_final = CriaListaFinal($dbconn, $id_experimento, $descritores, $img_consulta, $qtd_resultados);
        //colocando a lista final na sessão
        //$_SESSION["lista_final"] = $lista_final;
        //IMPRIMINDO A LISTA FINAL PARA VER SE ELA FICOU MONTADA CORRETAMENTE
        if ($DEBUG) {
            echo "LISTA COMBINADA DOS DESCRITORES (lista_final -> size=".count($lista_final)."):<br>";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
            echo "<hr/>";
        }

        //cria lista de indices randomicos
        srand((double) microtime()*1000000);
        $indices = range(1,count($lista_final)); //cria uma lista de numeros sequenciais de 1 ao tamanho da lista final
        shuffle($lista_final); //embaralha a lista - ATENCAO!!! shuffle faz o array iniciar do zero
                                                //no meu caso isto influencia no calculo das selecionadas!!!
        if ($DEBUG) {
            echo "LISTA FINAL EMBARALHADA:<br>";
            echo "<pre>";
            print_r($lista_final);
            echo "</pre>";
            echo "<hr/>";
        }
        //colocando a lista final embaralhada na sessão
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
           <? //tratando nome da imagem de consulta para exibir apenas nome do arquivo
               $img_temp_name = explode("/", $img_consulta);
               $img_temp_name = $img_temp_name[count($img_temp_name)-1];
           ?>
           Name: <br/><?=$img_temp_name//$img_consulta?><br/>
         </td></tr> 
   </table>
    <br/><br/>
    <form action="view_images_feedback.php" method="post" name="imagens">
        <input type="hidden" name="id_experimento" value="<?=$id_experimento;?>">
        <!-- nao sei se ja existe esse campo, mas para ter certeza...eh q eu nao encontrei -->
        <input type="hidden" name="consulta_anterior" value="<?=$img_consulta;?>">
    <table cellspacing="3" cellpadding="4" align="center" width="98%">
    <tr><td colspan="<?=$qtd_colunas_resultados?>" class="resultados_titulo">RESULTS</td></tr>
    <tr>
<?
        $cont=1;
        //ESSE WHILE DEVE PERCORRER AS IMAGENS DA LISTA FINAL, E NAO USAR NADA DO BANCO, POIS OS DADOS DO BANCO JA FORAM OBTIDOS ACIMA
        $cont_lista_final = 1;
        foreach ($lista_final as $fv2) {
            //$img_file = AdjustImageSource($fv2->fv2, &$img_name);
            //$img_file = AdjustImageSource($img_db_root, $fv2->fv2);
            $img_file = AdjustImageSource($fv2->fv2);
            //o nome da imagem eh apenas o nome do arquivo sem a estrutura de dir
            $img_name = explode("/",$img_file);
            $img_name = $img_name[count($img_name)-1];

            echo "<td id=\"".$cont_lista_final."\">";
            echo "    <img src=\"".$img_file."\" alt=\"".$img_name."\" border=\"1\" style=\"max-width:180px;\" height=130 onClick=\"swap(".$cont_lista_final.")\"/><br/>";
            echo "    <input type=\"checkbox\" name=\"selecionadas[]\" value=\"".$cont_lista_final."\" width=\"100\" class=\"checkHidden\"/>";
            echo "<br/>".$img_name;
            if ($DEBUG) {
                echo "<br/>descritores que a recuperaram (id e posicao):<br/>";
                echo "<pre>";
                print_r($fv2->descritores);
                echo "</pre>";
            }
            //echo "<br/>".$line[distance]."<br/>";
            //ACHO QUE NAO VAMOS EXIBIR A DISTANCIA
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
                //echo "qtde_consultas=".count($_SESSION['lista_consultas'])."<br/>";
                //echo "consulta_atual=".$_SESSION['lista_consultas_indice']."<br/>";
                //verifica se existe proxima consulta, ou se ja percorreu todas as consultas do arquivo
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
        //FIM DO EXPERIMENTO - ultima consulta foi avaliada
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
    //fecha conexao
    pg_close($dbconn);
?>
