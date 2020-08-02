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
#    Thanks to Rodrigo Senra (rodsenra_at_gmail_dot_com), who helped me with this script
#

# -*- coding: utf-8 -*-
from __future__ import division
import Image #Python Image Library
import timeit
import os
import sys
from datetime import datetime
import psycopg2
import util #useful functions

img_extensions = ('jpg','png','gif','tif','ppm','JPG') ##ATTENTION with extensions in upper case
collection = []
base_id = ""


def process_path(limit, action, collection, root):
    """Recursively visit the subpaths from root, identifying
    files with extensions in img_extensions, and applying the function
    action to each of them.
    """
    #print "->", root, ":: ", os.path.getsize(root)
    if len(collection)>=limit:
        raise StopIteration("Limite atingido")
    if not os.path.exists(root):
        return 
    if not os.path.isdir(root) and (root[-3:] in img_extensions):  ###ATTENTION with file extensions having more than 3 characters
         action(root)
         return 
    if os.path.isdir(root):
        for f in os.listdir(root):
           process_path(limit, action, collection, os.path.join(root,f))
    else:
        return

def save_in_collection(path):
    """For each file print its path and save its path in
    the global variable collection.
    """
    #print "added: ",path
    if (os.path.getsize(path) > 0):
        collection.append([base_id, path])


def select_n_img_paths(n, bases):

    for base in bases:

        global base_id
        base_id = base[0]
        base_root = base[1]

        try:
            process_path(n, save_in_collection, collection, base_root)
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
    print "************** Extraction *****************"

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
    iBases = 0
    for b in bases:
        bases[iBases] = b.split(":")
        iBases = iBases + 1

    print "descriptors =", descritores
    print "image databases =", bases
    print "classes = ", usaClasses
    #Still missing data from evaluation metrics - no impact currently

    select_n_img_paths(1000000, bases)
    print "Collection: \n", collection, "\n"
    collection_size = len(collection)
    print "SIZE of collection: ", collection_size, "\n"

    #Dealing with empty collection
    if (collection_size == 0):
        print "Empty Collection!"
        sys.exit(1)

    #time accumulator - initializes with zero
    tempo = {}
    tempos = {} #saves all computed times
    for desc in descritores:
        tempo[desc] = {'total':0, 'avg':0}
        tempos[desc] = {}


    last_progress = 0 #indicates the extraction progress (steps of 10%)
    print "ext_progress:",last_progress

    ##########################################
    ### SCANS ALL IMAGES IN THE COLLECTION ###
    for img in collection:

        print "\n",img

        #converts the image to ppm - descriptors need PPM P6 input

        #whenever 10% of progress is achieved, updates the progress
        #BUG - when an invalid image is found, progress counter can become incorrect
        if ( ( ( (collection.index(img)+1)/collection_size) - last_progress) >= (10/100)):
            last_progress = (collection.index(img)+1)/collection_size
            print "ext_progress:",str(last_progress)

        try:

            #gets only file name (no extension, no path)
            nome_img = img[1].split("/")[-1:][0].split(".")[0]

            #by default, every image is considered as PPM
            isPPM = 1 # flag to indicate if the image is PPM or not

            #if image is not PPM (from file name extension), converts to PPM
            if (img[1].split("/")[-1:][0].split(".")[-1].upper() != "PPM"):

                isPPM = 0 #flag now indicating that image was not PPM

                #converts to PPM
                im = Image.open(img[1])
                im = im.convert("RGB")
                #save on temp directory /tmp/ - 
                im.save("/tmp/" + nome_img + ".ppm") 
                print "saving img: ",nome_img

            if (isPPM == 1):
                img_path = img[1]
            else:
                img_path = "/tmp/" + nome_img + ".ppm"

            #correcting names with \\
            img_path = img_path.replace("\\","\\\\")
            #problems may happen when there are single quotes in file name

            ################################
            #### APPLYING DESCRIPTORS ######
            ################################
            # All descriptors are applied below, in order to use the same PPM image just created (if it was not originally PPM)
            ################################

            for desc in descritores:
                print "Descriptor:",desc,"..."

                nome_desc = desc

                #adjusts the fv path to have information about the experiments, image database and descriptor
                dir_path = path_results + "fv_" + id_exp + "_" + img[0] + "_" + nome_desc

                #adjusts the file name to have all image path
                fv_path  = dir_path +"/" + img[1].replace('/','::') + ".txt"

                #checks if the fv directory already exists
                if (os.path.isdir(dir_path) == False):
                    os.mkdir(dir_path)   #if not, creates it

                #correcting names with \\
                fv_path = fv_path.replace("\\","\\\\")

                print "fv_path=", fv_path
                print "fv_path_size=",len(fv_path)

                #if the feature vector (fv) was already extracted, does not extract again; this allows restarting the extraction process from where it stopped
                if not (os.path.exists(fv_path)):

                    setup = """
ctypes = __import__('ctypes')
plugin = "%s"+".so"
lib = ctypes.CDLL("%s"+plugin)
img_path = "%s"
fv_path = "%s"
                    """%(desc, path_descriptors, img_path, fv_path.replace("\\","\\\\"))

                    cmd = '''
lib.Extraction(img_path, fv_path)
                    '''

                    #Constant: number of times the time measurement will be used
                    num_exec = 3

                    t = timeit.Timer(stmt=cmd, setup=setup)
                    avg_time = t.timeit(number=num_exec)

                    #averages of 3 executions are accumulated in 'total' 
                    tempo[desc]['total'] += avg_time/num_exec

                    #saves all computed times (only the averages of the 3 executions)
                    print "(index) current image =",collection.index(img)
                    tempos[desc][collection.index(img)] = avg_time/num_exec
                    print "computed -> tempos[",desc,"][",collection.index(img),"] = ",avg_time/num_exec

                else:
                    print "fv was already extracted; does not extract again."

            ################################
            ## END - APPLYING DESCRIPTORS ##
            ################################

            if (isPPM == 0):
                #removes the PPM file created
                os.remove("/tmp/" + nome_img + ".ppm")
            #images that were originally PPM cannot be removed!

            print "number of images in the vectors of time for the descriptor", desc, "=", len(tempos[desc])

        except IOError:
            print "Problems with image file. Skipping to the next image..."
            collection_size -= 1
            #raise
            #pass

        except:
            print "Error processing image..."
            collection_size -= 1
            #raise
            #pass

    print "ext_progress: 1"

    #POSTGRESQL CONNECTION
    try:
        conn=util.connect()
    except:
        print "Error connecting to the database"
        raise
        sys.exit()
    cur = conn.cursor()


    print "COLLECTION SIZE (collection_size):",collection_size
    print "FINAL COLLECTION SIZE (len(collection)):",len(collection) #there may be difference if some images could not be processed

    print "\n================="
    print "Extraction times:"
    for desc in descritores:
        try:
            #tempo[desc]['avg'] = (tempo[desc]['total']/collection_size)
            tempo[desc]['avg'] = (tempo[desc]['total']/len(tempos[desc])) #divides by the number of times in the vector, because if the experiment was restarted, 
                                                                          #the quantity will be different than the collection size
            print "\n",desc, ":\ntotal time = ", tempo[desc]['total']
            print "total avg time = ", tempo[desc]['avg']
            print "collection size = ", collection_size
            print "number of times = ",len(tempos[desc])

            #computes the standard deviation
            soma = 0
            for i, t in tempos[desc].iteritems():
                soma += pow((t-tempo[desc]['avg']), 2)

            desvio_padrao = pow(soma/len(tempos[desc]), 0.5)
            print desc, "standard deviation = ", desvio_padrao

            #POSTGRESQL - registers the average time for 1 feature extraction and the standard deviation
            query = "INSERT INTO experimenttime (idexperiment, iddescriptor, idevaluationmeasure, value, stddev) VALUES ("+str(id_exp)+",'"+desc+"',1,"+str(tempo[desc]['avg'])+","+str(desvio_padrao)+")"
            cur.execute(query)

        except:
            print "Empty vector of times! Possible reasons: (1)extraction was not necessary because it was already done before or (2)there is a bug in the descriptor extraction function."


    print "EXTRACTION - SUCESS!!!"

    #POSTGRESQL - closing connection
    conn.commit()
    cur.close()
    conn.close()

    fim_total = datetime.now()
    print "[EXTRACTION] Experiment started:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[EXTRACTION] Experiment concluded: ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second

    

if __name__=="__main__":
    main()

