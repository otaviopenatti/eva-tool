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

<?
//If session already exists, destroy it
if (isset($_SESSION['plugin_file'])) {
    session_destroy();
}

session_start();
?>
<html>
    <head>
        <title>Eva tool - Database</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>
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

        <h1>Database - tables sizes</h1>

        <hr size="1"/>


<?
    include "util.php";
    // Connecting, selecting database
    $dbconn = connect();

    // Performing SQL query
    //Selects the 20 biggest tables (in terms of in disk space)
    $query = 'SELECT relname, relfilenode, relpages FROM pg_class ORDER BY relpages DESC LIMIT 20';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>
            <h4>Largest tables</h4>
            <table border="0" cellspacing="1" cellpadding="5" width="70%" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th>relname</th>
                <th>relfilenode</th>
                <th>relpages</th>
                <th>size in GB</th>
            </tr>
<?
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
                    //computes the table size in GB: multiply the number of pages per 8KB and divide by 1024 twice
                    $tamanho = (($line['relpages']*8) / 1024) / 1024;
?>
                <tr>
                    <td><?=$line['relname']?></td>
                    <td style="text-align:right"><?=$line['relfilenode']?></td>
                    <td style="text-align:right"><?=$line['relpages']?></td>
                    <td style="text-align:right"><?=round($tamanho,2)?></td>
                </tr>
<?
                }
    // Free resultset
    pg_free_result($result);

    // Closing connection
    pg_close($dbconn);
?>
            </table>
            <br/>

    <hr size="1"/>


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
