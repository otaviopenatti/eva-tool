#
#    This file is part of Eva tool.
#
#    Eva is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    Eva is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with Eva. If not, see <http://www.gnu.org/licenses/>.
#
#    For commercial use of Eva, please contact me.
#
#    COPYRIGHT 2010-2013  - Otavio A. B. Penatti - otavio_at_penatti_dot_com
#

# -*- coding: utf-8 -*-
from __future__ import division
import os
import sys
from datetime import datetime
import psycopg2
import util #arquivo de funcoes uteis ao python

file_extensions = ('txt')

#########################################################################
# Main parameter
# - Experiment id
#
def main():

    ini_total = datetime.now()

    #POSTGRESQL connection
    try:
        conn=util.connect()
    except:
        print "Error connecting to the database!"
        raise
        sys.exit()
    cur = conn.cursor()

    #Path for the results (generated feature vectors)
    id_exp = sys.argv[1]
    path_results = "results/"+id_exp+"/"

    print "\n**********************************************"
    print "****** Generating distance files *************"

    #uses a configuration file to get experiment information
    from ConfigParser import ConfigParser
    cfg = ConfigParser()
    cfg.readfp(open(path_results+"exp_cfg.ini"))
    descritores = cfg.get("Experiment","descritores").split(",")
    bases = cfg.get("Experiment","bases").split(",")
    usaClasses = int(cfg.get("Experiment","classes"))
    if (usaClasses!=1):
        print "Databae classification will not be used, therefore, no distance files will be generated!"
        sys.exit()

    n_query_imgs = int(cfg.get("Experiment","consultas"))
    if (n_query_imgs==0):
        n_query_imgs = 1000000 #large number when all images are queries

    print "pr_progress: 0.0"

    iBases = 0
    for b in bases:
        bases[iBases] = b.split(":")
        iBases = iBases + 1

    print "descriptors =", descritores
    print "image databases =", bases

    #checks number of queries and creates a list of queries
    fv1_collection = []
    sql = "SELECT DISTINCT fv1 FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+descritores[0]+"'"
    cur.execute(sql)
    resultados = cur.fetchall()
    for linha in resultados:  #creates query list (fv1) - the same for all descriptors in the experiment
        fv1_collection.append(linha[0])
    query_collection_size = len(fv1_collection)
    print "Size of query collection=",str(query_collection_size)
    cur.close()

    #Creates query file!
    #this file is necessary for the script that computes evaluation measures (analyzer)
    nome_arquivo_consultas = path_results + "queryImagesClasses.txt"
    arquivo_consultas = open(nome_arquivo_consultas, "w")

    arquivo_consultas.write(str(query_collection_size)+"\n") #number of query images
    for fv1 in fv1_collection: #adding each fv1 in the query file
        #print "fv1=", fv1
        #print "bases=", bases
        #Adjusts fv1 name
        #removes full path from fv1; class name is kept (considering that class name is the image parent directory)
        fv1_final = fv1.split(bases[0][1])[1]
        fv1_img    = fv1_final.split("/")[-1] #Gets query image name  (no path) - splits in '/' and get the last item
        fv1_classe = fv1_final.split("/")[0]  #Gets the class
        fv1_final = fv1_classe+"/"+fv1_classe+"_"+fv1_img  
        arquivo_consultas.write(fv1_final)
        arquivo_consultas.write("\n")
    arquivo_consultas.flush()
    #closes query file
    arquivo_consultas.close()
    print "Query file generated."

    #counting the number of images compared with fv1
    cur = conn.cursor()
    sql = "SELECT DISTINCT COUNT(fv2) FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+descritores[0]+"' AND fv1='"+fv1_collection[0]+"'"
    cur.execute(sql)
    resultados = cur.fetchall()
    collection_size = str(resultados[0][0])
    cur.close()
    print "Collection size=", collection_size

    print "pr_progress: 0.05"

    desc_size = len(descritores)

    #ANALYZER - for computing evaluation metrics
    caminho_analyzer = "codes/analyzer/./analyze.so"
    dis_sim = 0 #indicates if the distance file has distance of similarity values; 
                #Eva tool is standardized to use always distance values; however, the 'analyze' program also allows similarity values

    #TREC_EVAL
    caminho_trec_eval = "codes/trec_eval/"

    ###################################
    for desc in descritores:
        #creates cursor for the descriptor
        cur = conn.cursor()

        #file is created for the current descriptor
        nome_arquivo = path_results + "distances_" + desc + "_comClasses.txt"
        dist_file = open(nome_arquivo, "w")

        #first 2 file rows have: number of queries and number of images in the database
        dist_file.write(str(query_collection_size)+"\n")
        dist_file.write(str(collection_size)+"\n")

        for fv1_item in fv1_collection:

            #Adjusts fv1 name
            #removes full path from fv1; class name is kept (considering that class name is the image parent directory)
            fv1_final = fv1_item.split(bases[0][1])[1]
            fv1_img    = fv1_final.split("/")[-1] #Gets query image name  (no path) - splits in '/' and get the last item
            fv1_classe = fv1_final.split("/")[0]  #Gets the class
            fv1_final = fv1_classe+"/"+fv1_classe+"_"+fv1_img 

            #Selects from distance table, all lines corresponding to the current experiment and descriptor, sorted by fv1
            sql = "SELECT fv1,fv2,distance FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+desc+"' AND fv1='"+fv1_item+"' ORDER BY fv1,fv2"
            #print "sql=",sql
            cur.execute(sql)
            resultados = cur.fetchall()

            #For each line retrieved for that fv1:
            for linha in resultados:
                #Adjusts fv1 and fv2 to have only "classe/nome_img" (removes image database path)

                #Adjusts fv2 name
                #removes full path from fv1; class name is kept (considering that class name is the image parent directory)
                fv2_final = linha[1].split(bases[0][1])[1]
                fv2_img    = fv2_final.split("/")[-1] #Gets query image name  (no path) - splits in '/' and get the last item
                fv2_classe = fv2_final.split("/")[0]  #Gets the class
                fv2_final = fv2_classe+"/"+fv2_classe+"_"+fv2_img 

                distance = linha[2]

                #REMARK: the class name must appear before the image name in the file
                # for example: "2/2_nome_img.txt" (2=class name);
                # that is, add "className_" before image name

                #saves in a file: path fv1 path fv 2 distance
                dist_file.write(fv1_final)
                dist_file.write("\t")
                dist_file.write(fv2_final)
                dist_file.write("\t")
                dist = "%6.10f"%distance
                dist_file.write(dist)
                dist_file.write("\n")

                dist_file.flush()
                ##end of current line, goes to the next

        #End of lines for the current descriptor
        dist_file.close()

        #after generating the distance file (txt), uses it as input to the 'analyzer'
        ctypes = __import__('ctypes')
        lib = ctypes.CDLL(caminho_analyzer)
        nome_arquivo_resultados = path_results + "precision_recall_" + desc + ".txt"
        lib.run((path_results+desc), nome_arquivo_consultas, nome_arquivo, dis_sim, nome_arquivo_resultados)
        print "Running analyzer:\nquery_file_name:",nome_arquivo_consultas,"\ndistance_file_name:",nome_arquivo,"\ndis_sim:",str(dis_sim),"\nresults_file_name:",nome_arquivo_resultados

        print "pr_progress: ", ((descritores.index(desc)+1)/desc_size)
        cur.close() #closes cursor

        ###TREC_EVAL! (program to compute other evaluation metrics)
        #for running trec_eval, several steps are necessary
        #IMPROVEMENT point: some parts could be out of this loop; e.g., generation of map and qrels files are the same for all descriptors
        trec_eval = 1 
        if (trec_eval == 1):
            print "Generating files for trec_eval..."
            #Generates mapping file (generateMapFile.sh base_path file_format)
            #gets the format of 1 image (assuming that all images in the database have the same format (RISKY!)
            formato = fv1_img.split(".")[-1]
            print "path_results="+ path_results
            print "bases[0][0]="+ bases[0][0]
            print "bases[0][1]="+ bases[0][1]
            os.system(caminho_trec_eval+"./generateMapFile.sh "+bases[0][1]+" "+formato+" "+path_results+bases[0][0])
            print "\tmap file generated: "+path_results+str(bases[0][0])+".map"

            #Generates qrels - relevant images for each query
            #print "java --->>>java "+trec_eval_path+"WasQrelsGenerator "+path_results+str(bases[0][0])+".map "+path_results+str(bases[0][0])+".qrels"
            os.system("java -jar "+caminho_trec_eval+"QrelsGen.jar "+path_results+str(bases[0][0])+".map "+path_results+str(bases[0][0])+".qrels "+formato)
            print "\tqrels file generated: "+str(bases[0][0])+".qrels"

            #running trec_eval
            print "\tComputing evaluation metrics with trec_eval..."
            os.system(caminho_trec_eval+"./trec_eval "+path_results+str(bases[0][0])+".qrels "+path_results+desc+"_distances.trec > "+path_results+desc+"_results.trec -q")

            print "End of trec_eval."
        #END of current descriptor


    #POSTGRESQL - closing connection
    conn.close()

    print "pr_progress: 1.0"

    fim_total = datetime.now()
    print "[GENERATION] Start:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[GENERATION] End:   ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second


if __name__=="__main__":
    main()


