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

?>
<html>
    <head>
        <title>Eva tool - Image databases management</title>

    <link href="estilo.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>

    <script language="Javascript">

        function confirma(id) {
            continua = confirm('Are you sure you want to delete database '+id+'?');
            if (continua) {
                window.location = "exclui_base_imagens.php?id="+id;
            }
        }
    </script>

    </head>

<body>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
    <div id="bloco2">
    <!-- inicio - elemento -->
    <div class="top-left"></div><div class="top-right"></div>
    <div class="inside">
        <p class="notopgap">&nbsp;
    <!--************************ BORDAS ARREDONDADAS! ************************-->


        <h1>Image databases management</h1>

        <hr size="1"/>

<?
    include "util.php";
    // Connecting, selecting database
    $dbconn = connect();

    // Performing SQL query
    $query = 'SELECT * FROM imagedatabase ORDER BY id';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>

            <h4>Databases</h4>
            <table border="0" cellspacing="1" cellpadding="5" width="90%" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th>id</th>
                <th>Name</th>
                <th>Path</th>
                <th>Description</th>
                <th>Categorized?</th>
                <th width="1">Delete</th>
            </tr>
<?
                $imgdb_count=0;
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                <tr>
                    <td><?=$line['id']?></td>
                    <td><?=$line['name']?></td>
                    <td><?=$line['path']?></td>
                    <td><?=$line['descr']?></td>
                    <td style="text-align:center;"><?=($line['classified'])==t?"YES":"NO"?></td>
                    <td><a href="javascript:confirma('<?=$line['id']?>')">X</a></td>
                </tr>
<?
                    $imgdb_count++;
                }
                if ($imgdb_count == 0) {
                    echo "<tr><td colspan=6>No image databases yet.</td></tr>";
                }

    // Free resultset
    pg_free_result($result);

    // Closing connection
    pg_close($dbconn);
?>
            </table><br/>

    <hr size="1"/>

    <a href="cadastra_base_imagens.php">Insert new image database</a>

    <hr size="1"/>

    <a href="index.htm">Back</a>

    <!--************************ BORDAS ARREDONDADAS! ************************-->
        </p><p class="nobottomgap"></p>
    </div>
    <div class="bottom-left"></div><div class="bottom-right"></div>
    <!-- fim - elemento -->
    <!--************************ BORDAS ARREDONDADAS! ************************-->

    <br/>

</body>

</html>
