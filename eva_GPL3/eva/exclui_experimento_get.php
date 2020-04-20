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

    if (!isset($_GET['id_exp'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=ver_experimentos_realizados.php\"\">\n</head>\n</html>";
    } else {

        include "util.php";

        // Connecting, selecting database
        $dbconn = connect();

        //Apaga avaliacao com usuarios!
        $query = "DELETE FROM experimentuserevaluation WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga do relacionamento com as medidas de avaliacao
        $query = "DELETE FROM experimentevaluationmeasure WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga do relacionamento com as bases de imagens
        $query = "DELETE FROM experimentimagedatabase WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga do relacionamento com os descritores
        $query = "DELETE FROM experimentdescriptor WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga do relacionamento com os tempos
        $query = "DELETE FROM experimenttime WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga do relacionamento com as distancias
        $query = "DELETE FROM distance WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        // Apaga da tabela experimento
        $query = "DELETE FROM experiment WHERE id=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //FALTA APAGAR ARQUIVOS DO EXPERIMENTO
        //ATUALMENTE ESSA DELECAO EH SOH PRA LIMPAR UM POUCO A LISTA EXIBIDA NA PAGINA DE EXPERIMENTOS REALIZADOS
        //unlink("descriptors/$_GET[id].so");

        // Closing connection
        pg_close($dbconn);

        echo "Experiment ".$_GET['id_exp']." deleted!<br/>";
        //echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        //echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=ver_experimentos_realizados.php?del=".$_GET['id_exp_del']."\"\">\n</head>\n</html>";

    }
?>
