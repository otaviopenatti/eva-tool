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

function abcd() {
	//this function is useful only if you want to make it more difficult for others to view the database password
    //return base64_decode("b3Rhdmlv");
    return "otavio";
}


function connect() {
    $dbconn = pg_connect("host=localhost dbname=eva user=otapena password=".abcd())
        or die('Could not connect: ' . pg_last_error());

    //echo "ENCODING = ".pg_client_encoding($dbconn)."<br/>";

    //setting database encoding
    //pg_set_client_encoding($dbconn, "UTF8");
    $result = pg_query("SET CLIENT_ENCODING TO 'LATIN1';") or die('Query failed: ' . pg_last_error());

    return $dbconn;
}

//--------------------------------------------
//FUNCOES USADAS NAS TELAS DE VISUALIZACAO DAS IMGS////////////////

//ajusta source da imagem
//troca o diretorio fisico pelo diretorio do apache
//apenas remove do caminho da imagem a parte anterior a '/img_databases/'
//entrada:         $img = caminho original da imagem
function AdjustImageSource($img) {
    //echo "img=".$img."<br>";

    //quebra path no diretorio img_databases --> isso pode dar problema se o path da base tiver a string 'img_databases'
    $img_db_dir = explode("img_databases/", $img);
    //echo "img_db_dir=<pre>".print_r($img_db_dir)."</pre><br>";
    $img_db_dir = $img_db_dir[count($img_db_dir)-1]; //copia a parte da direita da string (pois esta contem o caminho da imagem)
    //echo "img_db_dir=".$img_db_dir."<br>";

    //adiciona o caminho '../img_databases/' da ferramenta no caminho da imagem
    $img_file = "../img_databases/".$img_db_dir;
    //echo "img_file=".$img_file."<br/>";

    //trata casos com aspas simples ou barra ao contrario no nome
    if (preg_match("/'/",$img_file)) {
        $img_file = str_replace("\'","'",$img_file);
    } else {
        //trata casos com uma barra ao contrario no nome do arquivo 
        $img_file = str_replace("\\","#",$img_file);
        $img_file = str_replace("##","\\",$img_file);
    }

    //substitui espacos por %20 nos nomes de arquivos das imagens
    $img_file = str_replace(" ", "%20", $img_file);

    //echo "img_file=".$img_file."<br/>";
    return $img_file;

}


//
?>
