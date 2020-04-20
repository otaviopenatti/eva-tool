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
import util #arquivo de funcoes uteis ao python

img_extensions = ('jpg','png','gif','tif','ppm','JPG') ##!!!CUIDADO COM EXTENSOES EM LETRAS MAIUSCULAS!
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
    if not os.path.isdir(root) and (root[-3:] in img_extensions):  ##!!!!!CUIDADO COM EXTENSOES COM TAMANHO DIFERENTE DE 3 LETRAS
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
    #print "adicionando: ",path
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
# Parametros principais:
# - Lista de descritores (ids dos descritores)
# - Lista de bases de imagens (ids ou path das bases de imagens)
# - Lista de medidas a serem extraidas no processo de extracao
#
# SEM O if __name__ nao dah pra chamar pelo shell!!!!!!!!!!
#if __name__=="__main__":
def main():

    ini_total = datetime.now()

    #PATH da Raiz do projeto

    #Path descritores
    path_descriptors = "descriptors/"

    #Path img_databases
    path_img_databases = "img_databases/"

    #Path dos resultados (fvs gerados)
    id_exp = sys.argv[1]
    path_results = "results/"+id_exp+"/"

    print "\n*******************************************"
    print "************** Extraction *****************"

    # estes parametros serao passados pelo programa principal
    descritores = []
    bases = []
    medidas = []

    #USA ARQUIVO DE CONFIGURACAO PARA PEGAR OS DADOS
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

    print "descritores =", descritores
    print "bases =", bases
    print "classes = ", usaClasses
    #FALTA DADOS DAS MEDIDAS - por enquanto eles nao fazem diferenca

    select_n_img_paths(1000000, bases)
    print "Collection: \n", collection, "\n"
    collection_size = len(collection)
    print "SIZE of collection: ", collection_size, "\n"

    #Tratando colecao vazia
    if (collection_size == 0):
        print "Colecao Vazia!"
        sys.exit(1)

    #acumulador de tempos - inicializa com zero
    tempo = {}
    tempos = {} #guarda todos os tempos calculados
    for desc in descritores:
        tempo[desc] = {'total':0, 'avg':0}
        tempos[desc] = {}


    last_progress = 0 #indica o progresso do processo de extracao (muda de 10 em 10%)
    print "ext_progress:",last_progress

    ###################################
    ### PERCORRE IMAGENS DA COLECAO ###
    for img in collection:

        print "\n",img

        #converte a imagem para ppm - descritores de cor exigem PPM P6

        #sempre que o processo avançar 10\%, atualiza o arquivo de progresso
        #BUG - pode nao indicar corretamente o progresso qdo encontra uma img invalida
        if ( ( ( (collection.index(img)+1)/collection_size) - last_progress) >= (10/100)):
            last_progress = (collection.index(img)+1)/collection_size
            print "ext_progress:",str(last_progress)

        try:

            #pega apenas o nome do arquivo (sem extensao, sem path)
            nome_img = img[1].split("/")[-1:][0].split(".")[0]

            #por padrao, toda imagem eh considerada PPM
            isPPM = 1 # flag que indica se a imagem é originalmente PPM ou nao

            # Se a extensao da imagem for diferente de PPM, deve converte-la para PPM  // alterei de [1].upper() para [-1].upper()
            if (img[1].split("/")[-1:][0].split(".")[-1].upper() != "PPM"):

                isPPM = 0 #nao eh originalmente PPM

                #converte pra PPM
                im = Image.open(img[1])
                im = im.convert("RGB")
                #salva no diretorio /tmp/ - 
                im.save("/tmp/" + nome_img + ".ppm") 
                print "vai salvar img: ",nome_img

            if (isPPM == 1):
                img_path = img[1]
            else:
                img_path = "/tmp/" + nome_img + ".ppm"

            #corrige nomes de imagens com \\
            img_path = img_path.replace("\\","\\\\")

            #talvez tenha problemas com aspas no nome do arquivo

            ################################
            #### APLICANDO DESCRITORES #####
            ################################
            # Colocar abaixo as chamadas para a extracao de cada descritor assim se aproveita a imagem PPM criada
            ################################

            for desc in descritores:
                print "Descritor:",desc,"..."

                nome_desc = desc

                #ajusta path do fv de acordo com o experimento, a base de imagens e o descritor
                dir_path = path_results + "fv_" + id_exp + "_" + img[0] + "_" + nome_desc

                #alterando o nome do arquivo de fv para guardar todo o caminho da imagem
                fv_path  = dir_path +"/" + img[1].replace('/','::') + ".txt"

                #verifica se o diretorio de FV já existe.
                if (os.path.isdir(dir_path) == False):
                    os.mkdir(dir_path)   #se nao existe, cria-o

                #corrige nomes de imagens com \\
                fv_path = fv_path.replace("\\","\\\\")

                print "fv_path=", fv_path
                print "fv_path_size=",len(fv_path)

                #se o fv ja foi extraido, nao extrai novamente... isso permite continuar a extracao de onde ela parou
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

                    #Constante: qtde de execucoes medidas
                    num_exec = 3

                    #tempo das 3 execucoes
                    t = timeit.Timer(stmt=cmd, setup=setup)
                    avg_time = t.timeit(number=num_exec)

                    #GARBAGE COLLECTOR - PODE SER NECESSARIO EM ALGUNS CASOS
                    #import gc
                    #gc.collect()

                    #acumula medias das 3 execucoes em 'total'
                    tempo[desc]['total'] += avg_time/num_exec

                    #guarda todos os tempos calculados (no caso, apenas a medias das 3 execucoes)
                    print "(indice) imagem atual=",collection.index(img)
                    tempos[desc][collection.index(img)] = avg_time/num_exec
                    print "calculado -> tempos[",desc,"][",collection.index(img),"] = ",avg_time/num_exec

                else:
                    print "fv ja foi extraido... nao extrai novamente."

            ################################
            #FIM- APLICANDO DESCRITORES -FIM
            ################################

            if (isPPM == 0):
                #remove o arquivo PPM criado
                os.remove("/tmp/" + nome_img + ".ppm")
            #imagens que já eram PPM nao podem ser removidas!

            print "quantidade de imagens no vetor tempos do descritor", desc, "=", len(tempos[desc])

        except IOError:
            #print "Está truncada. Ignorando ela e continuando com a proxima imagem..."
            print "Problemas com o arquivo da imagem. Ignorando e continuando com a proxima imagem..."
            collection_size -= 1
            #raise
            #pass

        except:
            print "Erro ao processar a imagem..."
            collection_size -= 1
            #raise
            #pass

    print "ext_progress: 1"

    #CONEXAO COM O POSTGRESQL
    try:
        conn=util.connect()
    except:
        print "Erro na conexao com o banco de dados"
        raise
        sys.exit()
    cur = conn.cursor()


    print "TAMANHO DA COLECAO (collection_size):",collection_size
    print "TAMANHO REAL DA COLECAO FINAL (len(collection)):",len(collection)

    print "\n================="
    print "Extraction times:"
    for desc in descritores:
        try:
            #tempo[desc]['avg'] = (tempo[desc]['total']/collection_size)
            tempo[desc]['avg'] = (tempo[desc]['total']/len(tempos[desc])) #divide pela qtde de tempos no vetor, pois, se o experimento foi reiniciado,
                                                                          #a qtde sera diferente do tamanho da colecao
            print "\n",desc, ":\ntotal time = ", tempo[desc]['total']
            print "total avg time = ", tempo[desc]['avg']
            print "collection size = ", collection_size
            print "qtd_tempos = ",len(tempos[desc])

            #calcula desvio padrao
            soma = 0
            for i, t in tempos[desc].iteritems():
                #print "media imagem ",i,":", t
                soma += pow((t-tempo[desc]['avg']), 2)

            desvio_padrao = pow(soma/len(tempos[desc]), 0.5)
            print desc, "desvio = ", desvio_padrao

            #POSTGRESQL - cadastra tempo medio de 1 extracao e desvio padrao
            query = "INSERT INTO experimenttime (idexperiment, iddescriptor, idevaluationmeasure, value, stddev) VALUES ("+str(id_exp)+",'"+desc+"',1,"+str(tempo[desc]['avg'])+","+str(desvio_padrao)+")"
            cur.execute(query)

        except:
            print "Vetor de tempos vazio! Motivos possiveis: (1)nao precisou extrair pois ja havia extraido anteriormente ou (2)a extracao do descritor esta com algum bug."


    print "EXTRACAO - SUCESSO!!!"

    #POSTGRESQL - fecha conexao
    conn.commit()  # commit das alteracoes
    cur.close()
    conn.close()

    fim_total = datetime.now()
    print "[EXTRACTION] Inicio do experimento:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[EXTRACTION] Fim do experimento:   ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second

    

if __name__=="__main__":
    main()

