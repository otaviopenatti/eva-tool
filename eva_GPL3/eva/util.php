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
//Functions used in the image visualization tools

//Adjust image source - replace directory by Apache directory
//only removes the first part of image source path (before '/img_databases/')
//input: $img = original image source path
function AdjustImageSource($img) {

    //split on 'img_databases'; this can have problems if other parts of the path have the 'img_databases' string
    $img_db_dir = explode("img_databases/", $img);
    $img_db_dir = $img_db_dir[count($img_db_dir)-1]; //image path

    //includes '../img_databases/' in the path string
    $img_file = "../img_databases/".$img_db_dir;

    //treating cases with single quotes or backslash 
    if (preg_match("/'/",$img_file)) {
        $img_file = str_replace("\'","'",$img_file);
    } else {
        //cases with backslash
        $img_file = str_replace("\\","#",$img_file);
        $img_file = str_replace("##","\\",$img_file);
    }

    //replace spaces by %20
    $img_file = str_replace(" ", "%20", $img_file);

    return $img_file;

}

?>
