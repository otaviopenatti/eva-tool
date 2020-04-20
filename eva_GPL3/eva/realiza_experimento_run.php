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

    if (!isset($_SESSION['descritores']) || !isset($_SESSION['bases']) || !isset($_SESSION['medidas']) || !isset($_POST['email']) || !isset($_POST['description'])) {

        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=realiza_experimento.php\"\">\n</head>\n</html>";
        session_destroy();
    } else {


    //echo "usar classes? -> ".$_POST['classes']."<br>";
    $DEBUG = 0;

    include "util.php";
    $dbconn = connect();

    //cria um experimento na base de dados
    $query = "INSERT INTO experiment (descr, email) VALUES ('$_POST[description]', '$_POST[email]')";
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());
    pg_free_result($result);

    //pega o id deste experimento
    $query = "SELECT MAX(id) FROM experiment";
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());
    $line = pg_fetch_array($result, null, PGSQL_ASSOC);
    $id_exp = $line['max'];
    if ($DEBUG) echo "<br/>id_exp = ".$id_exp."<br/>";
    pg_free_result($result);


?>
<html>
    <head>
        <title>Eva tool - Experiment running...</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <!-- <link rel="SHORTCUT ICON" href="favicon.ico"/> -->
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

    <h1>Experiment running...</h1>

    An e-mail will be sent to <b><?=$_POST['email']?></b> when the experiment is completed (NOT IMPLEMENTED YET).
    <br/><br/>
    <a href="detalhes.php?id_exp=<?=$id_exp?>">View experiment details</a><br/><br/>
    <a href="ver_experimentos_realizados.php">View all other experiments</a>
    <br/>
    <br/>
    <a href="index.htm">Back</a>

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
<?


/*
CONTINUACAO DO CODIGO QUE APARECE ANTES DO HTML
/**/


    //diretorio de resultados do experimento
    $dir_results = "results/".$id_exp."/";

    //configuracoes do arquivo
    $conf_text = "[Experiment]\n";

    //cadastra na base de dados informacoes sobre os componentes do experimento:
    //--descritores, bases e medidas usadas
    $conf_text.= "descritores=";
    foreach ($_SESSION['descritores'] as $id_desc) {
        $conf_text.= $id_desc.",";
        if ($DEBUG) echo "id_desc=".$id_desc."<br/>";
        $query = "INSERT INTO experimentdescriptor (iddescriptor, idexperiment) VALUES ('$id_desc', $id_exp)";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        pg_free_result($result);
    }
    $conf_text = substr($conf_text, 0, -1)."\nbases=";
    foreach ($_SESSION['bases'] as $id_base) {
        //pega path da base
        $query = "SELECT path FROM imagedatabase WHERE id=".$id_base;
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        $line = pg_fetch_array($result, null, PGSQL_ASSOC);
        $conf_text.= $id_base.":".$line['path'].",";
        pg_free_result($result);

        if ($DEBUG) echo "id_base=".$id_base."<br/>";
        $query = "INSERT INTO experimentimagedatabase (idimagedatabase, idexperiment) VALUES ($id_base, $id_exp)";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        pg_free_result($result);
    }
    $conf_text = substr($conf_text, 0, -1)."\nmedidas=";
    foreach ($_SESSION['medidas'] as $id_medida) {
        $conf_text.= $id_medida.",";
        if ($DEBUG) echo "id_medida=".$id_medida."<br/>";
        $query = "INSERT INTO experimentevaluationmeasure (idexperiment, idevaluationmeasure) VALUES ($id_exp, $id_medida)";
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());
        pg_free_result($result);
    }
    $conf_text = substr($conf_text, 0, -1)."\n";
    //verifica qtde de imagens de consulta
    $conf_text.= "consultas=".$_POST['consultas']."\n";
    if ($_POST['caminho_consultas_lista'] != '') {
        //$conf_text.= "consultas_lista=".$dir_results.$_FILES['consultas_lista']['name']."\n";
        $conf_text.= "consultas_lista=".$dir_results."query_list.txt"."\n";

        //Se usou uma lista de consultas, verifica se quer realizar cross-validation
        if ($_POST['cross_validation']) {
            $conf_text.= "cross_validation=1\n";
        } else {
            $conf_text.= "cross_validation=0\n";
        }
    }

    //verifica se deve usar divisao de classes da base
    if ($_POST['classes']) {
        $conf_text.= "classes=1\n";
    } else {
        $conf_text.= "classes=0\n";
    }
    $conf_text.= "\n[Info]\nemail=".$_POST[email]."\n";

    if ($DEBUG) echo "config file=<br/><pre>".$conf_text."</pre>";


    //cria diretorio de resultados do experimento
    if (!mkdir($dir_results, 0755)) {
        echo "Erros creating the experiment results directory ($dir_results)!";
        exit;
    }

    //faz o upload do arquivo com as imgs de consulta (se necessario)
    if ($_POST['caminho_consultas_lista'] != '') {
        $uploaddir = $dir_results;
        //usa nome de arquivo padrao = query_list.txt
        if (move_uploaded_file($_FILES['consultas_lista']['tmp_name'], $uploaddir . "query_list.txt")) {
        //if (move_uploaded_file($_FILES['consultas_lista']['tmp_name'], $uploaddir . $_FILES['consultas_lista']['name'])) {
            //print "O arquivo &eacute; valido e foi carregado com sucesso. Aqui esta alguma informacao:\n";
            //print_r($_FILES);
        } else {
            print "<pre>";
            print "Poss&iacute;vel ataque de upload! Aqui esta alguma informa&ccedil;&atilde;o:\n";
            print_r($_FILES);
            print "</pre>";
        }
    }

    //cria arquivo de configuracoes do experimento
    if (!$cfg_file = fopen($dir_results."exp_cfg.ini", "w")) {
        echo "Error opening file ".$dir_results."exp_cfg.ini!";
        exit;
    }

    if (fwrite($cfg_file, $conf_text) == FALSE) {
        echo "Error writing into file ".$dir_results."exp_cfg.ini!";
        exit;
    }

    if ($DEBUG) echo "file and directories created<br/>";

    //executa o script de extracao
    $comando = "python codes/executa_experimento.py ".$id_exp." 2>&1 | cat > results/".$id_exp."/exp_details_".$id_exp.".log &";

    exec($comando);


    // Closing connection
    pg_close($dbconn);

    }
?>
