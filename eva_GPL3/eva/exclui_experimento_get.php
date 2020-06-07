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

    if (!isset($_GET['id_exp'])) {
        echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=ver_experimentos_realizados.php\"\">\n</head>\n</html>";
    } else {

        include "util.php";

        // Connecting, selecting database
        $dbconn = connect();

        //Delete user evaluation
        $query = "DELETE FROM experimentuserevaluation WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete relationship with the evaluation measures
        $query = "DELETE FROM experimentevaluationmeasure WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete relationship with image databases
        $query = "DELETE FROM experimentimagedatabase WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete relationship with descriptors
        $query = "DELETE FROM experimentdescriptor WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete relationship with times
        $query = "DELETE FROM experimenttime WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete relationship with distances
        $query = "DELETE FROM distance WHERE idexperiment=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Delete experiment table
        $query = "DELETE FROM experiment WHERE id=".$_GET['id_exp'];
        $result = pg_query($query) or die('Query failed: ' . pg_last_error());

        //Still needs to delete experiment files!
        //unlink("descriptors/$_GET[id].so");

        // Closing connection
        pg_close($dbconn);

        echo "Experiment ".$_GET['id_exp']." deleted!<br/>";
        //echo "<html>\n<head>\n<title>Redirecting...</title>\n";
        //echo "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;URL=ver_experimentos_realizados.php?del=".$_GET['id_exp_del']."\"\">\n</head>\n</html>";

    }
?>
