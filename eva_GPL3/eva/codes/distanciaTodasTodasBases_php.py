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
import util #arquivo de funcoes uteis ao python

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
        #print "path nao existe", root
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
    #Para fazer uma comparacao todas x todas de todas as bases de imagens, basta adicionar todos os path na variavel "roots"
    for root in bases:
        try:
            process_path(n, save_in_collection, collection, root)
        except StopIteration:
            pass


#########################################################################
# Parametros principais:
# - Lista de descritores (ids dos descritores)
# - Lista de bases de imagens (ids ou path das bases de imagens)
# - Lista de medidas a serem extraidas no processo de extracao
#
# SEM O if __name__ nao dah pra chamar pelo shell!
#if __name__=="__main__":
def main():

    ini_total = datetime.now()

    #Path descritores
    path_descriptors = "descriptors/"

    #Path img_databases
    path_img_databases = "img_databases/"

    #Path dos resultados (fvs gerados)
    id_exp = sys.argv[1]
    path_results = "results/"+id_exp+"/"

    print "\n*******************************************"
    print "*************** Distance ******************"

    # estes parametros serao passados pelo programa principal
    descritores = []
    bases = []
    medidas = []

    #USA ARQUIVO DE CONFIGURACAO PARA PEGAR OS DADOS DO EXPERIMENTO
    from ConfigParser import ConfigParser
    cfg = ConfigParser()
    cfg.readfp(open(path_results+"exp_cfg.ini"))
    descritores = cfg.get("Experiment","descritores").split(",")
    bases = cfg.get("Experiment","bases").split(",")
    usaClasses = int(cfg.get("Experiment","classes"))
    n_query_imgs = int(cfg.get("Experiment","consultas"))
    if (n_query_imgs==0):
        n_query_imgs = 1000000 #caso sejam todas as imagens de consulta, usa um numero bem grande

    #Se esta usando uma lista de imagens de consulta:
    try:
        query_list_file_path = cfg.get("Experiment","consultas_lista")

        #Copia dados do arquivo de imgs de consulta para uma variavel
        print "query_list_file_path=", query_list_file_path
        query_list_file = open(query_list_file_path,"r")
        query_list = query_list_file.read().split("##")  #imgs separadas por ##
        #verifica se tem uma quebra de linha no final do arquivo ('\n')
        if (query_list[-1][-1:] == '\n'): 
            print "tem quebra de linha no final do arquivo; removendo..."
            query_list[-1] = query_list[-1][:-1]  #remove o \n do final da ultima imagem da lista
        print "query_list = ", query_list

        #verifica se vai usar cross validation
        cross_validation = int(cfg.get("Experiment","cross_validation"))

    except:
        print "Nao esta usando lista de imagens de consulta"
        #raise
        query_list = []  #lista eh vazia

    #CORTANDO A LISTA PRA TENTAR CONTINUAR DE ONDE PAROU!
    #query_list = query_list[-1:]  #pega soh a 1 ultimas q faltaram

    iBases = 0
    for b in bases:
        bases[iBases] = b.split(":")
        iBases = iBases + 1

    print "descritores =", descritores
    print "bases =", bases
    #FALTA DADOS DAS MEDIDAS - por enquanto eles nao fazem diferenca

    desc_size = len(descritores)

    #contadores de tempo
    tempo = {}
    tempos = {} #guarda todos os tempos calculados
    tempo_fora = {}
    tempo_insert = {}
    for desc in descritores:
        tempo[desc] = {'total':0}
        tempos[desc] = {}
        tempo_fora[desc] = {'total':0}
        tempo_insert[desc] = {'total':0}

    last_progress = 0
    print "dist_progress:",last_progress

    #CONEXAO COM O POSTGRESQL
    try:
        conn=util.connect() 
    except:
        print "Erro na conexao com o banco de dados"
        raise
        sys.exit()
    cur = conn.cursor()

    ###################################
    for desc in descritores:

        ### INICIO - atualizacao da colecao
        #baseado nos dados das bases, compoe-se os nomes dos dirs de fv
        collection = [] #limpa a colecao para evitar que fv de outros descritores sejam usados
        roots = []
        query_collection = []
        for base in bases:
            dir = path_results + "fv_" + id_exp + "_" + base[0] + "_" + desc + "/"
            roots.append(dir)
        print "roots = ",roots

        #coloca todas as imagens na colecao
        select_n_img_paths(1000000, roots, collection)
        collection.sort()
        collection_size = len(collection)
        print "SIZE of collection: ", collection_size, "\n"
        ### FIM - atualizacao da colecao

        #coloca as imagens de CONSULTA na colecao
        #verifica se as imgs de consulta sao pre-estabelecidas
        if (len(query_list) == 0):  # lista vem vazia
            query_collection = collection.__getslice__(0,n_query_imgs) #pega as primeiras imagens da colecao inteira
        else:
            #lista de imgs de consulta veio pre-estabelecida
            for query in query_list:                              #copia imgs de consulta para a query_collection
                query_collection.append(roots[0]+query.replace('/','::')+'.txt')  #substituindo '/' por '::' (por causa dos arquivos de fv)

            #se usa lista de consultas, pode querer fazer cross-validation
            if (cross_validation==1):
                print "Usando CROSS-VALIDATION: removendo da collection a query_collection..."
                #remove da collection a query_collection... assim as imgs de consulta nao sao consideradas como de fora da base
                for item in query_collection:
                    collection.remove(item) #remove elemento por elemento da collection
                collection_size = len(collection)
                print "Novo tamanho da collection:", collection_size

        query_collection.sort()
        query_collection_size = len(query_collection)
        #print "query_collection=", query_collection
        print "SIZE of query collection: ", query_collection_size, "\n"
        ### FIM - atualizacao da colecao

        #Calculando o total de comparacoes que sera feito por descritor - considerando MEIA MATRIZ
        #formula da soma de uma PA considerando um possivel tamanho diferente entre query_collection e collection
        total_comparacoes = (query_collection_size * (collection_size + (collection_size-query_collection_size)+1)) / 2
        print "Total de calculos de distancia a serem realizados para cada descritor:",total_comparacoes

        print "Calculando distancias com o ", desc, "..."

        #inicializa contador de comparacoes - inicializado para cada descritor
        qtd_comparacoes = 0

        for fv1 in query_collection:
            #print "fv1 (",collection.index(fv1), ")  :", fv1

            #Atualizando progresso
            progresso_atual = ((descritores.index(desc)+1)*qtd_comparacoes) / (total_comparacoes*desc_size)

            print "consulta atual fv1=",fv1

            #soh atualiza o progresso qdo avancar uma certa porcentagem em relacao ao ultimo progresso
            if ((progresso_atual-last_progress) >= (2/100)):
                last_progress = progresso_atual
                print "dist_progress:",last_progress

            for fv2 in collection:

                #incrementa contador de comparacoes - usado tb para indexar vetor de tempos
                qtd_comparacoes = qtd_comparacoes + 1

                ################################
                ####  APLICANDO DESCRITOR  #####
                ################################

                ### MEDINDO O TEMPO POR FORA
                ini = datetime.now()
                ###MEDINDO TEMPO POR FORA

                ctypes = __import__('ctypes')
                plugin = "./"+desc+".so"
                lib = ctypes.CDLL(path_descriptors+plugin)
                lib.Distance.restype = ctypes.c_double
                distance = lib.Distance(fv1, fv2)

                ###MEDINDO TEMPO POR FORA
                fim = datetime.now()
                #!!print "tempo dist (fora do timeit):", (fim-ini).microseconds
                ###MEDINDO TEMPO POR FORA

                ###MEDINDO TEMPO POR FORA - PRA VERIFICAR O TIMEIT
                ini = datetime.now()

                #Uso do descritor COM medicao de tempo
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
                #Constante: quantidade de vezes que ser� rodada a medi��o de tempo
                num_exec = 3

                t = timeit.Timer(stmt=cmd, setup=setup)
                avg_time = t.timeit(number=num_exec)

                ###MEDINDO TEMPO POR FORA - PRA VERIFICAR O TIMEIT
                fim = datetime.now()
                #print "tempo do timeit (fora do timeit):", ((fim-ini).microseconds)/num_exec
                tempo_fora[desc]['total'] += (((fim-ini).microseconds)/num_exec)
                ###MEDINDO TEMPO POR FORA - PRA VERIFICAR O TIMEIT

                tempo[desc]['total'] += (avg_time/num_exec)

                #guarda todos os tempos calculados (no caso, apenas as medias das 3 execucoes)
                #indexa vetor de tempos pelo contador de comparacoes
                tempos[desc][(qtd_comparacoes-1)] = avg_time/num_exec
                ###!print "dist_time_"+str(desc)+"="+str(avg_time/num_exec)

                fv1_final = "/"+fv1.split("/::")[1][:-4].replace("::","/")
                fv2_final = "/"+fv2.split("/::")[1][:-4].replace("::","/")

                dist = "%6.10f"%distance

                #tratando casos com aspas simples no nome da imagem
                if (fv1_final.rfind("\'") != -1):
                    fv1_final = fv1_final.replace("\'","\'\'")
                if (fv2_final.rfind("\'") != -1):
                    fv2_final = fv2_final.replace("\'","\'\'")

                ### TEMPO INSERT
                ini = datetime.now()

                #POSTGRESQL - cadastra distancia calculada
                query_dist = "INSERT INTO distance (idexperiment, iddescriptor, fv1, fv2, distance) VALUES ("+str(id_exp)+",'"+desc+"','"+fv1_final+"','"+fv2_final+"',"+str(dist)+")"
                #print "query_dist: ",query_dist
                cur.execute(query_dist)

                #Evita que se insira duas vezes a mesma linha... isso acontece qdo fv1=fv2 (ela comparada com ela mesmo)
                if (fv1_final!=fv2_final and len(query_list)==0): #evita tb inserir a consulta inversa qdo se tem uma lista de consultas
                    #Insere no bd a consulta inversa: de fv2 para fv1, pois distancia entre fv1 e fv2 independe da ordem
                    query_dist = "INSERT INTO distance (idexperiment, iddescriptor, fv1, fv2, distance) VALUES ("+str(id_exp)+",'"+desc+"','"+fv2_final+"','"+fv1_final+"',"+str(dist)+")"
                    #print "query_dist_invertida: ",query_dist
                    cur.execute(query_dist)

                ### TEMPO INSERT
                fim = datetime.now()
                #print "tempo insert:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"
                tempo_insert[desc]['total'] += (((fim-ini).microseconds)/num_exec)

            print "acabou consulta fv1=",fv1

            #ao inserir fv1,fv2 e fv2,fv2 no bd nao precisamos percorrer a matriz toda de distancias
            #basta apenas metade da matriz
            #para isso, remove-se da collection a atual imagem de consulta, pois ela ja foi comparada com todas
            if (len(query_list) == 0):  #soh remove qdo nao ha lista de consultas
                print "removendo fv1 da collection"
                collection.remove(fv1)

            ### TEMPO INSERT
            ini = datetime.now()            
            #Da um commit sempre que mudar a img de consulta
            conn.commit()
            ### TEMPO INSERT
            fim = datetime.now()
            print "tempo commit (dos inserts):", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"

        print "\nDistance times:"
        print "\n", desc, ":\ntotal time = ", tempo[desc]['total']
        tempo_medio = (tempo[desc]['total']/qtd_comparacoes) #total de comparacoes feitas esta na variavel qtd_comparacoes
        print "total avg time = ", tempo_medio  #tempo medio por calculo de distancia
        print "tempo medio fora do timeit = ", (tempo_fora[desc]['total'])/(qtd_comparacoes), "(microsegundos)"
        print "tempo inserts = ", (tempo_insert[desc]['total'])/(qtd_comparacoes), "(microsegundos)"
        ##############

        #calcula desvio padrao
        soma = 0
        for i, t in tempos[desc].iteritems():
            soma += pow((t-tempo_medio), 2)

        desvio_padrao = pow(soma/(qtd_comparacoes), 0.5)
        print desc, "desvio = ", desvio_padrao, "\n"

        ### TEMPO INSERT EXPERIMENT TIME
        ini = datetime.now()
        #POSTGRESQL - cadastra tempo medio de 1 calculo de distancia e o desvio padrao
        query = "INSERT INTO experimenttime (idexperiment, iddescriptor, idevaluationmeasure, value, stddev) VALUES ("+str(id_exp)+",'"+desc+"',2,"+str(tempo_medio)+","+str(desvio_padrao)+")"
        cur.execute(query)
        #POSTGRESQL - commit das alteracoes
        ### TEMPO INSERT EXPERIMENT TIME
        fim = datetime.now()
        print "tempo insert experimenttime:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"

        #contador de comparacoes
        print "para descritor",desc,"fez",qtd_comparacoes,"comparacoes"

    print "dist_progress: 1"

    print "DISTANCIA - SUCESSO!!!"
    print "*******************************************"


    ### TEMPO COMMIT FINAL
    ini = datetime.now()
    #POSTGRESQL - fecha conexao
    conn.commit()  # commit das alteracoes
    cur.close()
    conn.close()
    ### TEMPO COMMIT FINAL
    fim = datetime.now()
    print "tempo commit final, closing:", (fim-ini).seconds, "(segundos) e ",(fim-ini).microseconds, "(microsegundos)"

    fim_total = datetime.now()
    print "[DISTANCE] Inicio do experimento:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[DISTANCE] Fim do experimento:   ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second


if __name__=="__main__":
    main()


