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
//Se ja houver alguma sessao iniciada, destroi-a
if (isset($_SESSION['plugin_file'])) {
    session_destroy();
}

session_start();
//echo "session_id=".session_id();
?>
<html>
    <head>
        <title>Eva tool - Descriptors Management</title>

    <link rel="stylesheet" type="text/css" href="liquidcorners.css">
    <link rel="SHORTCUT ICON" href="favicon.ico"/>
    <link href="estilo.css" rel="stylesheet" />

    <script language="Javascript">

        function confirma(id) {
            continua = confirm('Are you sure you want to delete descriptor '+id+'?');
            if (continua) {
                window.location = "exclui_descritor.php?id="+id;
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

        <h1>Descriptors Management</h1>

        <hr size="1"/>


<?
    include "util.php";
    // Connecting, selecting database
    $dbconn = connect();

    // Performing SQL query
    $query = 'SELECT * FROM descriptor ORDER BY type,id,name';
    $result = pg_query($query) or die('Query failed: ' . pg_last_error());

?>
            <h4>Descriptors</h4>
            <table border="0" cellspacing="1" cellpadding="5" width="90%" bgcolor="#CCCCCC" class="cadastro" align="center">
            <tr>
                <th>id</th>
                <th>Name</th>
                <th>Author</th>
                <th>Type</th>
                <th width="1">Delete</th>
            </tr>
<?
                $desc_count=0;
                while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
?>
                <tr>
                    <td><?=$line['id']?></td>
                    <td><?=$line['name']?></td>
                    <td><?=$line['author']?></td>
                    <td style="text-align:center">
                        <?
                            if ($line['type'] == 0) {
                                echo "COLOR";
                            } else if ($line['type'] == 1) {
                                echo "TEXTURE";
                            } else {
                              echo "SHAPE";
                            }
                        ?>
                    </td>
                    <td style="text-align:center"><a href="javascript:confirma('<?=$line['id']?>')">X</a></td>
                </tr>
<?
                    $desc_count++;
                }
                if ($desc_count == 0) {
                    echo "<tr><td colspan=5>No descriptor yet.</td></tr>";
                }

    // Free resultset
    pg_free_result($result);

    // Closing connection
    pg_close($dbconn);
?>
            </table>
            <br/>

    <hr size="1"/>

    <h4><a href="cadastra_descritor.php">Insert new descriptor</a></h4>

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
