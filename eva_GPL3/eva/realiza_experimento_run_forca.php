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
    //Runs an experiment by hand. Useful when you need to re-run part of the experiment
    //BE CAREFULL!!!!


    //TO RUN AN EXPERIMENT, CHANGE THE VALUES BELOW
    $rodar = 0;
    $ID_EXP = 3;

    if ($rodar) {

        echo "RUNNING EXPERIMENT!<br/>";
        echo "idexperiment = ".$ID_EXP."<br/>";


        //$comando = "python codes/executa_experimento.py ".$id_exp." 2>&1 | cat > /exp/otavio/results/".$id_exp."/exp_details_".$id_exp.".log &";
        $comando = "python codes/executa_experimento.py ".$ID_EXP." 2>&1 | cat >> /exp/otavio/results/".$ID_EXP."/exp_details_".$ID_EXP.".log &";

        // REMEMBER TO APPEND IN THE LOG FILE, INSTEAD OF STARTING A NEW ONE (USE '>>' INSTEAD OF '>')
        // CHANGE, IF NECESSARY, THE executa_experimento.py FILE (SELECT WHAT IS GOING TO BE EXECUTED AGAIN)

        //echo "COMMENTED: TO EXECUTE, UNCOMMENT THE LINE BELOW!<br/>";
        exec($comando);

    } else {
        echo "Lines commented! To run the experiment, change the php file!<br/>";
    }

?>
