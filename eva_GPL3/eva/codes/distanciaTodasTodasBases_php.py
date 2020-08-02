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
import timeit
import sys
from datetime import datetime
import psycopg2
import util #useful functions

file_extensions = ('txt')
#collection = []

def process_path(limit, action, collection, root):
    """Recursively visit the subpaths from root, identifying
    files with extensions in file_extensions, and applying the function
    action to each of them.
    """
    #print "->", root
    if len(collection)>=limit:
        raise StopIteration("Limite atingido")
    if not os.path.exists(root):
        #print "path does not exist", root
        return 
    if not os.path.isdir(root) and (root[-3:] in file_extensions):
        #print "path ok", root
        action(root, collection)
        return 
    if os.path.isdir(root):
        for f in os.listdir(root):
           process_path(limit, action, collection, os.path.join(root,f))
    else:
        return 

def save_in_collection(path, collection):
    """For each file print its path and save its path in
    the global variable collection.
    """
    #print "->", path
    collection.append(path)


def select_n_img_paths(n, bases, collection):
    #for comparing all vs all from all image databases, just add all paths in the 'roots' variable
    for root in bases:
        try:
            process_path(n, save_in_collection, collection, root)
        except StopIteration:
            pass


#########################################################################
# Main parametros:
# - List of descriptors (descriptors ids)
# - List of image databases (ids or paths of database images)
# - List of evaluation metrics to be computed
#
def main():

    ini_total = datetime.now()

    #Path descriptors
    path_descriptors = "descriptors/"

    #Path image databases
    path_img_databases = "img_databases/"

    #Path results (generated feature vectors)
    id_exp = sys.argv[1]
    path_results = "results/"+id_exp+"/"

    print "\n*******************************************"
    print "*************** Distance ******************"

    # these parameters will be provided by the main program
    descritores = []
    bases = []
    medidas = []

    #uses a configuration file to get experiment information
    from ConfigParser import ConfigParser
    cfg = ConfigParser()
    cfg.readfp(open(path_results+"exp_cfg.ini"))
    descritores = cfg.get("Experiment","descritores").split(",")
    bases = cfg.get("Experiment","bases").split(",")
    usaClasses = int(cfg.get("Experiment","classes"))
    n_query_imgs = int(cfg.get("Experiment","consultas"))
    if (n_query_imgs==0):
        n_query_imgs = 1000000 #large number when all images are queries

    #if using a list of query images
    try:
        query_list_file_path = cfg.get("Experiment","consultas_lista")

        #copies the query image file to a variable
        print "query_list_file_path=", query_list_file_path
        query_list_file = open(query_list_file_path,"r")
        query_list = query_list_file.read().split("##")  #imgs separated by ##
        #checks if there is a line break ('\n') in the end of the file
        if (query_list[-1][-1:] == '\n'): 
            print "removing line break at the end of the file..."
            query_list[-1] = query_list[-1][:-1]  #removes \n from the end of the last query image
        print "query_list = ", query_list

        #checks if cross validation will be used
        cross_validation = int(cfg.get("Experiment","cross_validation"))

    except:
        print "No query images is used."
        #raise
        query_list = []  #empty list

    iBases = 0
    for b in bases:
        bases[iBases] = b.split(":")
        iBases = iBases + 1

    print "descriptors =", descritores
    print "databases =", bases
    #Still missing data from evaluation metrics - no impact currently

    desc_size = len(descritores)

    #time counters
    tempo = {}
    tempos = {} #stores all times computed
    tempo_fora = {}
    tempo_insert = {}
    for desc in descritores:
        tempo[desc] = {'total':0}
        tempos[desc] = {}
        tempo_fora[desc] = {'total':0}
        tempo_insert[desc] = {'total':0}

    last_progress = 0
    print "dist_progress:",last_progress

    #Connecting to postgresql
    try:
        conn=util.connect() 
    except:
        print "Error in database connection"
        raise
        sys.exit()
    cur = conn.cursor()

    ###################################
    for desc in descritores:

        ### BEGIN - updating the collection
        #based on the database information, composes the names of the fv directories
        collection = [] #cleans the collection to avoid using fvs from other descriptors
        roots = []
        query_collection = []
        for base in bases:
            dir = path_results + "fv_" + id_exp + "_" + base[0] + "_" + desc + "/"
            roots.append(dir)
        print "roots = ",roots

        select_n_img_paths(1000000, roots, collection) #inserts all images in the collection
        collection.sort()
        collection_size = len(collection)
        print "SIZE of collection: ", collection_size, "\n"

        #inserts the QUERY images in the collection
        #checks if the query images are pre-defined
        if (len(query_list) == 0):  # if empty list
            query_collection = collection.__getslice__(0,n_query_imgs) #gets the first images from the whole collection
        else:
            #pre-defined list of query images
            for query in query_list: #copies the query images to the query_collection
                query_collection.append(roots[0]+query.replace('/','::')+'.txt')  #replacing '/' by '::' (because of fv files)

            #if a query list is used, cross validation can also be used
            if (cross_validation==1):
                print "Using CROSS-VALIDATION: removing the query_collection from the collection..."
                
                #removes the query_collection from the collection... so the query images are not considered as images from outside of the database
                for item in query_collection:
                    collection.remove(item) #removes item by item
                collection_size = len(collection)
                print "New collection size:", collection_size

        query_collection.sort()
        query_collection_size = len(query_collection)
        #print "query_collection=", query_collection
        print "SIZE of query collection: ", query_collection_size, "\n"
        ### END - updating the collection

        #Computing the number of comparisons to be done for each descriptor - considering half matrix
        total_comparacoes = (query_collection_size * (collection_size + (collection_size-query_collection_size)+1)) / 2
        print "Number of distance calculations for each descriptor:",total_comparacoes

        print "Computing distance values for descriptor ", desc, "..."

        #initializes the counter of comparisons - initialized for each descriptor
        qtd_comparacoes = 0

        for fv1 in query_collection:
            #print "fv1 (",collection.index(fv1), ")  :", fv1

            #Updating progress
            progresso_atual = ((descritores.index(desc)+1)*qtd_comparacoes) / (total_comparacoes*desc_size)

            print "current query fv1=",fv1

            #progress is updated at some steps (at each 2% currently)
            if ((progresso_atual-last_progress) >= (2/100)):
                last_progress = progresso_atual
                print "dist_progress:",last_progress

            for fv2 in collection:

                #increments the counter of comparisons - it is used to index the vector of elapsed times
                qtd_comparacoes = qtd_comparacoes + 1

                ################################
                ####  APPLYING DESCRIPTOR  #####
                ################################

                ### MEASURING TIME - not inside descriptor code
                ini = datetime.now()
                ### MEASURING TIME 

                ctypes = __import__('ctypes')
                plugin = "./"+desc+".so"
                lib = ctypes.CDLL(path_descriptors+plugin)
                lib.Distance.restype = ctypes.c_double
                distance = lib.Distance(fv1, fv2)

                ### MEASURING TIME - not inside descriptor code
                fim = datetime.now()
                #!!print "dist (out of timeit):", (fim-ini).microseconds
                ### MEASURING TIME

                ### MEASURING TIME - in order to check TIMEIT
                ini = datetime.now()

                #Using descriptor WITH time measurement
                setup = """
ctypes = __import__('ctypes')
plugin = "./"+"%s"+".so"
lib = ctypes.CDLL("%s"+plugin)
lib.Distance.restype = ctypes.c_double
fv1 = "%s"
fv2 = "%s"
                """%(desc, path_descriptors, fv1.replace("\\","\\\\"), fv2.replace("\\","\\\\"))

                cmd = '''
distance = lib.Distance(fv1, fv2)
                '''
                #Constant: number of times the time measurement will be used
                num_exec = 3

                t = timeit.Timer(stmt=cmd, setup=setup)
                avg_time = t.timeit(number=num_exec)

                ### MEASURING TIME - in order to check TIMEIT
                fim = datetime.now()
                #print "time (out of timeit):", ((fim-ini).microseconds)/num_exec
                tempo_fora[desc]['total'] += (((fim-ini).microseconds)/num_exec)
                ### MEASURING TIME - in order to theck TIMEIT

                tempo[desc]['total'] += (avg_time/num_exec)

                #saves the average time among the "num_exec" executions
                #index the time vector using the counter of comparisons
                tempos[desc][(qtd_comparacoes-1)] = avg_time/num_exec
                ###!print "dist_time_"+str(desc)+"="+str(avg_time/num_exec)

                fv1_final = "/"+fv1.split("/::")[1][:-4].replace("::","/")
                fv2_final = "/"+fv2.split("/::")[1][:-4].replace("::","/")

                dist = "%6.10f"%distance

                #cases of single quotes in image name
                if (fv1_final.rfind("\'") != -1):
                    fv1_final = fv1_final.replace("\'","\'\'")
                if (fv2_final.rfind("\'") != -1):
                    fv2_final = fv2_final.replace("\'","\'\'")

                ### TIME for INSERTING
                ini = datetime.now()

                #POSTGRESQL - registers the computed distance
                query_dist = "INSERT INTO distance (idexperiment, iddescriptor, fv1, fv2, distance) VALUES ("+str(id_exp)+",'"+desc+"','"+fv1_final+"','"+fv2_final+"',"+str(dist)+")"
                #print "query_dist: ",query_dist
                cur.execute(query_dist)

                #avoids inserting the same line twice (when fv1=fv2 --> image compared against itself)
                if (fv1_final!=fv2_final and len(query_list)==0): #also avoids inserting the inverted query, when a query list is used
                    #also inserts in the database the oposite query (from fv2 to fv1), as distance measures are considered symmetric
                    query_dist = "INSERT INTO distance (idexperiment, iddescriptor, fv1, fv2, distance) VALUES ("+str(id_exp)+",'"+desc+"','"+fv2_final+"','"+fv1_final+"',"+str(dist)+")"
                    cur.execute(query_dist)

                ### TIME for INSERTING
                fim = datetime.now()
                #print "time for inserting:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"
                tempo_insert[desc]['total'] += (((fim-ini).microseconds)/num_exec)

            print "finished query fv1=",fv1

            #as fv1,fv2 and fv2,fv1 are stored in the database, it is not necessary to scan the whole distance matrix (just half of it)
            #for that, removes from the collection the current query image, as it was already compared against all the others
            if (len(query_list) == 0):  #only removes if there is no query list
                print "removing fv1 from the collection"
                collection.remove(fv1)

            ### TIME for INSERTING
            ini = datetime.now()            
            #Commits when changing the query image
            conn.commit()
            ### TIME for INSERTING
            fim = datetime.now()
            print "commit time (for insert commands):", (fim-ini).seconds, "(seconds) e ",(fim-ini).microseconds, "(microseconds)"

        print "\nDistance times:"
        print "\n", desc, ":\ntotal time = ", tempo[desc]['total']
        tempo_medio = (tempo[desc]['total']/qtd_comparacoes) #total de comparacoes feitas esta na variavel qtd_comparacoes
        print "total avg time = ", tempo_medio  #tempo medio por calculo de distancia
        print "average time out of timeit = ", (tempo_fora[desc]['total'])/(qtd_comparacoes), "(microseconds)"
        print "time for inserts = ", (tempo_insert[desc]['total'])/(qtd_comparacoes), "(microseconds)"
        ##############

        #Computing standard deviation
        soma = 0
        for i, t in tempos[desc].iteritems():
            soma += pow((t-tempo_medio), 2)

        desvio_padrao = pow(soma/(qtd_comparacoes), 0.5)
        print desc, "standard deviation = ", desvio_padrao, "\n"

        ### TIME FOR INSERTING EXPERIMENT TIME
        ini = datetime.now()
        #POSTGRESQL - registers the average time for 1 distance calculation and the standard deviation
        query = "INSERT INTO experimenttime (idexperiment, iddescriptor, idevaluationmeasure, value, stddev) VALUES ("+str(id_exp)+",'"+desc+"',2,"+str(tempo_medio)+","+str(desvio_padrao)+")"
        cur.execute(query)
        ### TIME FOR INSERTING EXPERIMENT TIME
        fim = datetime.now()
        print "time for inserting experimenttime:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"

        #counter of comparisons
        print "for ",desc," descriptor, ",qtd_comparacoes," comparisons were performed."

    print "dist_progress: 1"

    print "DISTANCE - SUCESS!!!"
    print "*******************************************"


    ### TIME - FINAL COMMIT
    ini = datetime.now()
    conn.commit()  # commit modifications
    cur.close()
    conn.close()
    ### TIME - FINAL COMMIT
    fim = datetime.now()
    print "time for final commit, closing:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"

    fim_total = datetime.now()
    print "[DISTANCE] Experiment started:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[DISTANCE] Experiment concluded: ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second


if __name__=="__main__":
    main()


