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
# Parametros principais:
# - ID do experimento
#
# SEM O if __name__ nao dah pra chamar pelo shell!
#if __name__=="__main__":
def main():

    ini_total = datetime.now()

    #CONEXAO COM O POSTGRESQL
    try:
        conn=util.connect()
    except:
        print "Erro na conexao com o banco de dados"
        raise
        sys.exit()
    cur = conn.cursor()

    #Path dos resultados (fvs gerados)
    id_exp = sys.argv[1]
    path_results = "results/"+id_exp+"/"

    print "\n**********************************************"
    print "**** Geracao Arquivos de Distancia *************"

    #USAR ARQUIVO DE CONFIGURACAO PARA PEGAR OS DADOS
    from ConfigParser import ConfigParser
    cfg = ConfigParser()
    cfg.readfp(open(path_results+"exp_cfg.ini"))
    descritores = cfg.get("Experiment","descritores").split(",")
    bases = cfg.get("Experiment","bases").split(",")
    usaClasses = int(cfg.get("Experiment","classes"))
    if (usaClasses!=1):
        print "Nao vai considerar classificacao da base. Portantao nao serao gerados os arquivos de distancia"
        sys.exit()

    n_query_imgs = int(cfg.get("Experiment","consultas"))
    if (n_query_imgs==0):
        n_query_imgs = 1000000 #caso sejam todas as imagens de consulta, usa um numero bem grande

    print "pr_progress: 0.0"

    iBases = 0
    for b in bases:
        bases[iBases] = b.split(":")
        iBases = iBases + 1

    print "descritores =", descritores
    print "bases =", bases

    #VERIFICA QTDE DE CONSULTAS E MONTA UMA LISTA DE CONSULTAS!!!
    fv1_collection = []
    sql = "SELECT DISTINCT fv1 FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+descritores[0]+"'"
    cur.execute(sql)
    resultados = cur.fetchall()
    for linha in resultados:  #CRIA lista de consultas (fv1) - eh a mesma para todos os descritores do experimento
        fv1_collection.append(linha[0]) #adiciona linha na colecao
    query_collection_size = len(fv1_collection)
    print "Tamanho da colecao de consulta=",str(query_collection_size)
    cur.close()

    #Cria arquivo de consultas!
    #Arquivo com as imagens de consulta - eh necessario para o analyze - NAO EH O MESMO ARQUIVO USADO PELO view_images_feedback.php
    nome_arquivo_consultas = path_results + "queryImagesClasses.txt"
    arquivo_consultas = open(nome_arquivo_consultas, "w")

    #adiciona na 1a linha do arquivo de consultas a qtde de imagens de consulta
    arquivo_consultas.write(str(query_collection_size)+"\n")
    for fv1 in fv1_collection: #adiciona cada fv1 da colecao no arquivo de consultas
        #print "fv1=", fv1
        #print "bases=", bases
        #Ajusta nome de fv1
        #Retira de fv1 o caminho da base de imagens. Sobra o caminho a partir das classes
        fv1_final = fv1.split(bases[0][1])[1]
        fv1_img    = fv1_final.split("/")[-1] #Pega o nome da imagem de consulta (sem os caminhos) - quebra nas '/' e pega o ultimo elemento
        fv1_classe = fv1_final.split("/")[0]  #Pega a classe
        fv1_final = fv1_classe+"/"+fv1_classe+"_"+fv1_img    #Nome ajustado, conforme observacao abaixo
        arquivo_consultas.write(fv1_final)
        arquivo_consultas.write("\n")
    arquivo_consultas.flush()
    #Finaliza o arquivo de consultas
    arquivo_consultas.close()
    print "Gerou arquivo de consultas."

    #CONTA QTDE DE IMGS DA BASE (COMPARADAS COM FV1)
    cur = conn.cursor()
    sql = "SELECT DISTINCT COUNT(fv2) FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+descritores[0]+"' AND fv1='"+fv1_collection[0]+"'"
    cur.execute(sql)
    resultados = cur.fetchall()
    collection_size = str(resultados[0][0])
    cur.close()
    print "Tamanho da colecao=", collection_size

    print "pr_progress: 0.05"

    #Se esta usando uma lista de imagens de consulta:
    #TALVEZ NAO PRECISE DAS LINHAS ABAIXO QUE PEGAM O ARQUIVO DE CONSULTAS
    #ESTA PEGANDO DIRETO DO BD
    #try:
    #    query_list_file_path = cfg.get("Experiment","consultas_lista")

    #    #Copia dados do arquivo de imgs de consulta para uma variavel
    #    print "query_list_file_path=", query_list_file_path
    #    query_list_file = open(query_list_file_path,"r")
    #    query_list = query_list_file.read().split("##")  #imgs separadas por ##
    #    print "query_list = ", query_list
    #except:
    #    print "Nao esta usando lista de imagens de consulta"
    #    query_list = []  #lista eh vazia


    #FALTA DADOS DAS MEDIDAS - por enquanto eles nao fazem diferenca

    desc_size = len(descritores)

    #Dados do ANALYZER
    caminho_analyzer = "codes/analyzer/./analyze.so"
    dis_sim = 0 #indica se o arquivo de distancias esta com valores de distancia ou de similaridade entre as imagens
                #pela padronizacao da ferramenta, o valor eh sempre de distancia, mas
                #o programa "analyze" permite valores de similaridade tambem

    #Dados do TREC_EVAL
    caminho_trec_eval = "codes/trec_eval/"

    ###################################
    for desc in descritores:
        #cria cursor para o descritor
        cur = conn.cursor()

        #Cria arquivo e ajusta nome dele de acordo o descritor
        nome_arquivo = path_results + "distances_" + desc + "_comClasses.txt"
        dist_file = open(nome_arquivo, "w")

        #coloca nas 2 primeiras linhas do arquivo: qtd de consultas \n qtd de imgs na base
        dist_file.write(str(query_collection_size)+"\n")
        dist_file.write(str(collection_size)+"\n")


        for fv1_item in fv1_collection:

            #Ajusta nome de fv1
            #Retira de fv1 o caminho da base de imagens. Sobra o caminho a partir das classes
            fv1_final = fv1_item.split(bases[0][1])[1]
            fv1_img    = fv1_final.split("/")[-1] #Pega o nome da imagem de consulta (sem os caminhos) - quebra nas '/' e pega o ultimo elemento
            fv1_classe = fv1_final.split("/")[0]  #Pega a classe
            fv1_final = fv1_classe+"/"+fv1_classe+"_"+fv1_img    #Nome ajustado, conforme observacao abaixo

            #Selecionar da tabela distance todas as linhas com o id do experimento e o id do descritor em questao, ordenando por fv1
            sql = "SELECT fv1,fv2,distance FROM distance WHERE idexperiment="+id_exp+" AND iddescriptor='"+desc+"' AND fv1='"+fv1_item+"' ORDER BY fv1,fv2"
            #print "sql=",sql
            cur.execute(sql)
            resultados = cur.fetchall()

            #Para cada linha retornada para aquele fv1:
            for linha in resultados:
                #Para fv1 e fv2, alterar os caminhos de forma a manter apenas: "classe/nome_img" (retirar raiz da base de fv1 e fv2 = path da tabela imagedatabase)

                #Ajusta nome de fv2
                #Retira de fv2 o caminho da base de imagens. Sobra o caminho a partir das classes
                fv2_final = linha[1].split(bases[0][1])[1]
                fv2_img    = fv2_final.split("/")[-1] #Pega o nome da imagem de consulta (sem os caminhos) - quebra nas '/' e pega o ultimo elemento
                fv2_classe = fv2_final.split("/")[0]  #Pega a classe
                fv2_final = fv2_classe+"/"+fv2_classe+"_"+fv2_img    #Nome ajustado, conforme observacao abaixo

                distance = linha[2]

                #- OBSERVACAO: a classe deve estar presente antes do nome da imagem no arquivo;
                #  por exemplo: "2/2_nome_img.txt" (2=nome da classe);
                #  ou seja, adicionar "nomeClasse_" antes do nome da imagem.

                #SALVA EM ARQUIVO: caminho FV1 caminho FV2 distance
                dist_file.write(fv1_final)
                dist_file.write("\t")
                dist_file.write(fv2_final)
                dist_file.write("\t")
                dist = "%6.10f"%distance
                dist_file.write(dist)
                dist_file.write("\n")

                dist_file.flush()
                #Acabou linha atual, vai para a proxima

        #Fim das linhas do descritor atual.
        #Fecha arquivo do descritor atual
        dist_file.close()

        #Depois que terminar a geracao do arquivo de distancias txt, submete-o ao 'analyzer'
        ctypes = __import__('ctypes')
        lib = ctypes.CDLL(caminho_analyzer)
        nome_arquivo_resultados = path_results + "precision_recall_" + desc + ".txt"
        lib.run((path_results+desc), nome_arquivo_consultas, nome_arquivo, dis_sim, nome_arquivo_resultados)
        print "Rodando analyzer:\nnome_arquivo_consultas:",nome_arquivo_consultas,"\nnome_arquivo_distancias:",nome_arquivo,"\ndis_sim:",str(dis_sim),"\nnome_arquivo_resultados:",nome_arquivo_resultados
        #os.system(caminho_analyzer+" "+nome_arquivo_consultas+" "+nome_arquivo+" "+str(dis_sim)+" "+nome_arquivo_resultados)

        print "pr_progress: ", ((descritores.index(desc)+1)/desc_size)
        cur.close() #fecha cursor

        ###TREC_EVAL!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        #se for usar o trec_eval, precisar executar varios passos:
        #SERIA MELHOR DEIXAR ALGUMAS PARTES FORA DO LACO, POIS A GERACAO DO map E DO qrels SAO IGUAIS PARA TODOS OS DESCRITORES
        trec_eval = 1 #apenas para testar... ver como fazer depois!!!
        if (trec_eval == 1):
            print "Gerando arquivos para o trec_eval..."
            #Gera arquivo de mapeamento (generateMapFile.sh caminho_base formato_arquivos)
            #pega formato de 1 das imagens, pois eh possivelmente o formato de todas as imagens da base (MAS PODE NAO SER!!!!)
            formato = fv1_img.split(".")[-1]
            print "path_results="+ path_results
            print "bases[0][0]="+ bases[0][0]
            print "bases[0][1]="+ bases[0][1]
            os.system(caminho_trec_eval+"./generateMapFile.sh "+bases[0][1]+" "+formato+" "+path_results+bases[0][0])
            print "\tgerou map file: "+path_results+str(bases[0][0])+".map"

            #gera qrels - imagens relevantes para cada consulta
            #print "java --->>>java "+caminho_trec_eval+"WasQrelsGenerator "+path_results+str(bases[0][0])+".map "+path_results+str(bases[0][0])+".qrels"
            os.system("java -jar "+caminho_trec_eval+"QrelsGen.jar "+path_results+str(bases[0][0])+".map "+path_results+str(bases[0][0])+".qrels "+formato)
            print "\tgerou qrels file: "+str(bases[0][0])+".qrels"

            #NAO PRECISA MAIS!!!
            #qrels eh sempre gerado com .ppm nas extensoes das imagens...
            #portanto, precisa-se substituir os .ppm pela extensao correta das imagens
            #if (formato != "ppm"):
                #print "\tSubstituindo .ppm por ."+formato+"..."
                #os.system("sed -i \"s/.ppm/."+formato+"/g\" "+path_results+str(bases[0][0])+".qrels")

            #calculando medidas usando o trec
            print "\tCalculando medidas usando o trec_eval..."
            os.system(caminho_trec_eval+"./trec_eval "+path_results+str(bases[0][0])+".qrels "+path_results+desc+"_distances.trec > "+path_results+desc+"_results.trec -q")

            print "Fim do trec_eval."
        #FIM do descritor atual


    #POSTGRESQL - fecha conexao
    #conn.commit()  # commit das alteracoes
    #cur.close()
    conn.close()

    print "pr_progress: 1.0"

    fim_total = datetime.now()
    print "[GENERATION] Inicio da geracao:", ini_total.year, "/", ini_total.month,"/",ini_total.day," - ",ini_total.hour,":",ini_total.minute,":",ini_total.second
    print "[GENERATION] Fim da geracao:   ", fim_total.year, "/", fim_total.month,"/",fim_total.day," - ",fim_total.hour,":",fim_total.minute,":",fim_total.second


if __name__=="__main__":
    main()


