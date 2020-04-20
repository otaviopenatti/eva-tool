/*
*    This file is part of Eva tool.
*
*    Eva is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    Eva is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with Eva. If not, see <http://www.gnu.org/licenses/>.
*
*    For commercial use of Eva, please contact me.
*
*    COPYRIGHT 2010-2013  - Otavio A. B. Penatti - otavio_at_penatti_dot_com
*/

#include <stdio.h>
#include <malloc.h>
#include <string.h>
#include <stdlib.h>

#define treceval 1

/*-------------------- Pooled storage allocator ---------------------------*/

/* The following routines allow for the efficient allocation of
     storage in small chunks from a named pool.  Rather than requiring
     each structure to be freed individually, an entire pool of
     storage is freed at once.
   This method has two advantages over just using malloc() and free().
     First, it is far more efficient for allocating small objects, as
     there is no overhead for remembering all the information needed
     to free each object.  Second, the decision about how long to keep
     an object is made at the time of allocation by assigning it to a
     pool, and there is no need to track down all the objects to free
     them.  In practice, this leads to code with little chance of
     memory leaks.

   Example of how to use the pooled storage allocator:
     Each pool is given a name that is a small integer (in header file):
       #define IMAGE_POOL 2
     Following allocates memory of "size" bytes from pool, IMAGE_POOL:
       mem = MallocPool(size, IMAGE_POOL);
     Following releases all memory in pool IMAGE_POOL for reuse:
       FreeStoragePool(IMAGE_POOL);
*/

/* We maintain memory alignment to word boundaries by requiring that
   all allocations be in multiples of the machine wordsize.  WORDSIZE
   is the maximum size of the machine word in bytes (must be power of 2).
   BLOCKSIZE is the minimum number of bytes requested at a time from
   the system (should be multiple of WORDSIZE).
*/
#define WORDSIZE 8  
#define BLOCKSIZE 2048

/* Following defines the maximum number of different storage pools. */
#define POOLNUM 100

/* Pointers to base of current block for each storage pool (C automatically
   initializes static memory to NULL).
*/
static char *PoolBase[POOLNUM];

/* Number of bytes left in current block for each storage pool (initialized
   to 0). */
static int PoolRemain[POOLNUM];


/* Returns a pointer to a piece of new memory of the given size in bytes
   allocated from a named pool. 
*/
void *MallocPool(int size, int pool)
{
    char *m, **prev;
    int bsize;

    /* Round size up to a multiple of wordsize.  The following expression
       only works for WORDSIZE that is a power of 2, by masking last bits of
       incremented size to zero.
    */
    size = (size + WORDSIZE - 1) & ~(WORDSIZE - 1);

    /* Check whether new block must be allocated.  Note that first word of
       block is reserved for pointer to previous block. */
    if (size > PoolRemain[pool]) {
	bsize = (size + sizeof(char **) > BLOCKSIZE) ?
	           size + sizeof(char **) : BLOCKSIZE;
	m = (char*) malloc(bsize);
	if (! m) {
	  fprintf(stderr, "ERROR: Ran out of memory.\n");
	  abort();
	}
	PoolRemain[pool] = bsize - sizeof(void *);
	/* Fill first word of new block with pointer to previous block. */
	prev = (char **) m;
	prev[0] = PoolBase[pool];
	PoolBase[pool] = m;
    }
    /* Allocate new storage from end of the block. */
    PoolRemain[pool] -= size;
    return (PoolBase[pool] + sizeof(char **) + PoolRemain[pool]);
}


/* Free all storage that was previously allocated with MallocPool from
   a particular named pool. 
*/
void FreeStoragePool(int pool)
{
    char *prev;

    while (PoolBase[pool] != NULL) {
	prev = *((char **) PoolBase[pool]);  /* Get pointer to prev block. */
	free(PoolBase[pool]);
	PoolBase[pool] = prev;
    }
    PoolRemain[pool] = 0;
}

/*-------------------- Precision Recall Analyzer --------------------------*/

/* Given the name of a structure, NEW allocates space for it in the
   given pool (see util.c) and returns a pointer to the structure.
*/
#define NEW(s,pool) ((struct s *) MallocPool(sizeof(struct s),pool))

/* Assign a unique number to each pool of storage needed for this application. 
*/
#define PERM_POOL  0     /* Permanent storage that is never released. */

#define MAX_BUFFER_SIZE    1000
#define NUM_AVERAGE_POINTS   11

typedef struct _result {
  char  relevant;
  char *cls_name;
  char *key_name;
  float distance;
} Result;

typedef struct _search {
  char   *cls_name;
  char   *key_base;
  int     num_relevants;
  Result *results;
  float   precision[NUM_AVERAGE_POINTS];
} Search;

int ResultCompare(const void *result1, const void *result2)
{
  float distance;

  distance = ((Result *)result1)->distance - ((Result *)result2)->distance;
  if (distance > 0.0)
    return  1;
  else if (distance < 0.0)
    return -1; 
  else
    return  0;
}

int ResultCompareSim(const void *result1, const void *result2)
{
  float distance;

  distance = ((Result *)result1)->distance - ((Result *)result2)->distance;
  if (distance < 0.0)
    return  1;
  else if (distance > 0.0)
    return -1; 
  else
    return  0;
}

//int main(int argc, char** argv)
void run(char *desc_name, char *queries, char *distances, int dis_sim, char *results)
{
  FILE *fsearch;
  FILE *fresult;
  FILE *fdistTrec;
  FILE *fcurve;
  int num_searches, num_results, num_relevants;
  float precision[NUM_AVERAGE_POINTS];
  char *strng, buffer[MAX_BUFFER_SIZE];
  Search *searches;
  int i, j, k;
  char distTrecName[100]; //guarda nome do arquivo de distancias do trec
  double *max_distance; //vetor para guardar a maior distancia de cada consulta - usada para transformar distancia em similaridade pro trec_eval

  /*if (argc != 5) {
    fprintf(stderr,"usage: analyze <searches> <results> <dis|sim> <curve>\n");
    exit(-1);
  }*/

  //leitura do arquivo de consultas
  fsearch = fopen(queries, "r");
  if (fsearch == NULL) {
    fprintf(stderr,"Cannot open %s.\n",queries);
    exit(-1);
  }

  //leitura do arquivo de distancias
  fresult = fopen(distances, "r");
  if (fresult == NULL) {
    fprintf(stderr,"Cannot open %s.\n",distances);
    exit(-1);
  }

  //leitura da opcao de distancia=0 ou similaridade=1
  //int dis_sim = atoi(argv[3]);
  if (dis_sim != 0 && dis_sim != 1) {
    fprintf(stderr,"Choose 0 for distance or 1 for similarity\n");
    exit(-1);
  }

  //le a qtd de consultas do arquivo de consultas
  fscanf(fsearch,"%d\n",&num_searches);

  //le a qtd de consultas do arquivo de distancias
  fscanf(fresult,"%d\n",&i);
  if (i != num_searches) {  //qtdes devem ser iguais nos 2 arquivos
    fprintf(stderr,"Number of searches in %s and %s are differ.\n",queries,distances);
    exit(-1);   //se forem diferentes, sai do programa...
  }

  //le a qtd de imagens da base do arquivo de distancias
  fscanf(fresult,"%d\n",&num_results);

  //aloca o vetor de consultas com qtd_consultas posicoes
  //searches = (Search *)MallocPool(num_searches * sizeof(Search), PERM_POOL);
  searches = (Search *) calloc(num_searches, sizeof(Search));

  max_distance = (double *) calloc(num_searches, sizeof(double));

  //para cada consulta...
  for (i=0;i<num_searches;i++) {

    max_distance[i] = -1; //inicializa vetor de maximas distancias

    fgets(buffer, MAX_BUFFER_SIZE, fsearch);  //Leitura do arquivo de consultas

    //Lendo classe da imagem de consulta...
    strng = strtok(buffer,"/");     //quebra a primeira linha no caractere '/'
    //!!printf("\nclasse da consulta %d = %s\n", i, strng);
    //searches[i].cls_name = (char *)MallocPool(strlen(strng), PERM_POOL);  //aloca string para a classe da consulta
    searches[i].cls_name = (char *) calloc(strlen(strng)+1, sizeof(char));
    //strcpy(searches[i].cls_name,strng);                 //copia a string tokenizada para a estrutura
    sprintf(searches[i].cls_name, "%s", strng);
    //!!printf("classe da consulta SEARCHES %d = %s\n", i, searches[i].cls_name); //ateh aqui esta ok!!

    //Lendo nome da imagem de consulta...
    strng = strtok(buffer+strlen(strng)+1,"\n");  //pega o resto da linha até o '\n' - do arquivo de consultas
    //!!printf("img de consulta %d = %s\n", i, strng);
    //searches[i].key_base = (char *)MallocPool(strlen(strng), PERM_POOL);  //aloca string para o nome da img de consulta
    searches[i].key_base = (char *) calloc(strlen(strng)+1, sizeof(char));
    //strcpy(searches[i].key_base,strng);  //copia string para a estrutura
    sprintf(searches[i].key_base, "%s", strng);
    //!!printf("img de consulta %d na ESTRUTURA= %s\n", i, searches[i].key_base);

    //Aloca estrutura para os resultados da consulta atual...
    //searches[i].results = (Result *)MallocPool(num_results * sizeof(Result), PERM_POOL);  //cria um pool para os resultados da consulta atual
    searches[i].results = (Result *) calloc(num_results, sizeof(Result));
    searches[i].num_relevants = 0;       //inicializa o contador de relevantes com zero

    //Para cada img comparada com a consulta atual...
    for (j=0;j<num_results;j++) {
        //PEGA A DISTANCIA ENTRE A CONSULTA i E A IMG j
        fgets(buffer, MAX_BUFFER_SIZE, fresult);  //le do arquivo de resultados
        searches[i].results[j].distance = atof(strrchr(buffer,'\t')+1);  //procura a ultima ocorrencia do caractere '\t' e coloca no vetor de resultados a distancia entre a consulta e a img atual
        //!!printf("\n\tdistancia entre consulta %d e img %d = %f\n", i, j, searches[i].results[j].distance);

        //VERIFICANDO MAIOR DISTANCIA DO DESCRITOR
        if (searches[i].results[j].distance > max_distance[i]) {
            max_distance[i] = searches[i].results[j].distance; //atualiza maior distancia da consulta atual
        }

        //LENDO A CLASSE DA IMG ATUAL...
        strng = strtok(strchr(buffer,'\t')+1,"/");  //busca '\t' e,  a partir dele, quebra a linha lida no '/'
        //printf("\n\tACHOU ALGO:\tbuffer=\"%s\"\tstrchr=\"%s\"\tstrchr+1=\"%s\"\tstrtok=\"%s\"\tstr_final=\"%s\"", buffer, strchr(buffer,'\t'), (strchr(buffer,'\t')+1), strtok(strchr(buffer,'\t')+1,"/"), strng);
        //printf("\n\tACHOU ALG2:\tbuffer=\"%s\"\tstrchr=\"%s\"\tstrchr+1=\"%s\"\tstrtok=\"%s\"\tstr_final=\"%s\"", buffer, strchr(buffer,'\t'), (strchr(buffer,'\t')+1), strtok(strchr(buffer,'\t')+1,"/"), strng);

        //!!printf("\n\tclasse da img %d = %s\n", j, strng);
        //searches[i].results[j].cls_name = (char *)MallocPool(strlen(strng), PERM_POOL);  //aloca string para a classe da img j
        searches[i].results[j].cls_name = (char *) calloc(strlen(strng)+1, sizeof(char));
        //strcpy(searches[i].results[j].cls_name,strng);  //copia string para a classe da img j
        sprintf(searches[i].results[j].cls_name, "%s", strng);
        //!!printf("\tclasse da img %d na estrutura = %s\n", j, searches[i].results[j].cls_name);

        //LENDO O NOME DA IMG ATUAL...
        strng = strtok(strchr(buffer,'\t')+1+strlen(strng)+1,"\t");  //pega o nome da img j
        //!!printf("\tnome da img %d = %s\n", j, strng);
        //searches[i].results[j].key_name = (char *)MallocPool(strlen(strng), PERM_POOL);  //aloca string para o nome da img j
        searches[i].results[j].key_name = (char *) calloc(strlen(strng)+1, sizeof(char));
        //strcpy(searches[i].results[j].key_name,strng);  //copia string para a estrutura
        sprintf(searches[i].results[j].key_name, "%s", strng);
        //!!printf("\tnome da img %d na estrutura = %s\n", j, searches[i].results[j].key_name);

        //VERIFICA SE A CLASSE DA CONSULTA EH IGUAL A CLASSE DA IMG ATUAL
        //!!printf("\tclasse da img %d na estrutura (DEPOIS)= %s\n", j, searches[i].results[j].cls_name);  //AQUI, QDO O NOME DA IMG EH PEQUENO A CLASSE ESTA FICANDO SEM VALOR!!!!
                                                                                                    //ESTRANHO, POIS NENHUM VALOR EH ATRIBUIDO A cls_name desde a linha 235.
      if (strcmp(searches[i].cls_name,searches[i].results[j].cls_name) == 0) {
        searches[i].results[j].relevant = 1;  //marca se a img j eh da mesma classe que a img de consulta i
        searches[i].num_relevants++; //conta a qtd de imagens relevantes para aquela consulta
      }
      else
        searches[i].results[j].relevant = 0;  //marca se a img j nao eh da mesma classe que a img de consulta i
    }

    //!!printf("classe da consulta %d (DEPOIS DE LER OS RESULTADOS)= %s\n", i, searches[i].cls_name); //ateh aqui esta ok!!

  }

  //se vai usar o trec_eval, cria arquivo pra guardar distancias
  if (treceval) {
    //criando arquivo para guardar as distancias no formato do trec
    sprintf(distTrecName, "%s_distances.trec", desc_name);
    fdistTrec = fopen(distTrecName, "w");
    if (fdistTrec == NULL) {
        fprintf(stderr,"Cannot create %s.\n",distTrecName);
        exit(-1);
    }
  }

  //Percorre as consultas
  for (i=0;i<num_searches;i++) {
    if (dis_sim == 0) {  //se o arquivo de distancias tiver valores de distancia
      qsort(searches[i].results,num_results,sizeof(Result),&ResultCompare); //ordena os resultados
    } else {  //senao, os valores sao de similaridade, entao ordena de maneira inversa
      qsort(searches[i].results,num_results,sizeof(Result),&ResultCompareSim);
    }
    if (treceval) {
        for (j=0;j<num_results;j++) {
            //gerando entrada para o trec_eval:
            //nome_query x class_img/nome_img indice similaridade text
            fprintf(fdistTrec, "%s %d %s/%s %d %f %s\n", searches[i].key_base, 1, searches[i].results[j].cls_name, searches[i].results[j].key_name, j, ((max_distance[i]-searches[i].results[j].distance)/max_distance[i]), "trec_run");
        }
    }

    /*printf("resultados da consulta %d ordenados pela distancia - classe consulta=%s\tqtd_relevantes=%d\n", i, searches[i].cls_name, searches[i].num_relevants);
    for (j=0;j<num_results;j++) {
        printf("\tresult[%d]\tnome=%s\tclasse=%s\tdistance=%f\trelevante?=%d\n", j, searches[i].results[j].key_name, searches[i].results[j].cls_name, searches[i].results[j].distance, searches[i].results[j].relevant);
    }*/

//     LACO ORIGINAL!!!!!!!!!!!!!!!!!!!!!
//     num_relevants = 0; //inicializa a qtd de relevantes
//     for (j=0,k=0;j<num_results && k<NUM_AVERAGE_POINTS;j++) { //percorre cada resultado, ateh todos os resultados ou ateh k chegar em um valor de media
//       if (searches[i].results[j].relevant) { //se a imagem retornada eh relevante, incrementa num_relevantes
//         num_relevants++;
//       }
//       printf("\tj=%d\tnum_relevants=%d\tk=%d\ttotal_relevants=%d--\tnumRel/(j+1)=%f\n", j, num_relevants, k, searches[i].num_relevants, num_relevants/(float)(j+1));
//       if ((10 * num_relevants >= k * searches[i].num_relevants)) { //se a qtd de relevantes retornadas for maior ou igual a k*a qtd de relevantes existente, 
//         searches[i].precision[k] = num_relevants/(float)(j+1);   //calcula precision para k: divide qtd de relevantes retornadas por qtd de imgs retornadas ateh o momento
//         k++;
//       }
//     }

    num_relevants = 0; //inicializa a qtd de relevantes
    j = 0; //inicializa j - contador das imgs retornadas
    float recall=0.0, precision=0.0;
    int last_k = 0;  //guarda o ultimo recall ateh o qual ja tem precision calculado
    while ((j<num_results) && (recall<1.0)) { //percorre cada resultado, ateh todos os resultados ou ateh k chegar em um valor de media
      if (searches[i].results[j].relevant) { //se a imagem retornada eh relevante, incrementa num_relevantes
        num_relevants++;
      }

      precision = (float) num_relevants/(float)(j+1); //calcula precision atual --> qtd_relevantes_retornadas / total_retornadas
      recall = (float) num_relevants / searches[i].num_relevants;  //calcula recall atual
      //!!printf("img[%d]\tprecision=%f\trecall=%f\trecall/10=%d\trecall_resto_10=%d\n", j, precision, recall, ((int)(recall*100.0)/10), ((int)(recall*10.0)%10));

      //if (((int)(recall*10.0)%10)) { //se recall for um valor nao exato entre 10 e 10%, precisa copiar valor para os mais proximos anteriores
     //     printf("\t\tnao eh exato --> resto=%d\t vai copiar ateh recall<%d\n", ((int)(recall*10.0)%10), ((int)(recall*100.0)/10));
          //!!printf("\tcolocando precision=%f de recall=%d ateh %d\n", precision, last_k, ((int)(recall*100.0)/10));
          for (k=last_k; k<=((int)(recall*100.0)/10); k++) { //copia o mesmo precision para todos até a parte inteira de (recall/10)
                searches[i].precision[k] = precision;
          }
          last_k = k; //atualiza ultimo recall com precision calculado
      //} else {
      //    printf("\t\teh exato --> %d\t vai copiar no recall=%d\n", ((int)(recall*10.0)%10), ((int)(recall*100.0)/10));
      //    k = ((int)(recall*100.0)/10); //k recebe a parte inteira do recall/10   >> subtrai 1 para usar de indice
      //    searches[i].precision[k] = precision;  //apenas o ponto especifico de recall recebe o precision
      //}

      j++; //vai pra proxima img retornada
    }
  } //fim - percorre consultas

  //deve ficar claro que a qtd de imagens retornadas varia ateh se encontrar os valores de recall.
  /*for (i=0;i<num_searches;i++) {
    printf("\nconsulta[%d]\n", i);
    for (j=0;j<NUM_AVERAGE_POINTS;j++) {
      printf("\tprecision[%d]=%f\n", j, searches[i].precision[j]);
    }
  }*/

  //Faz a media dos precision de todas as consultas
  for (j=0;j<NUM_AVERAGE_POINTS;j++) {
    precision[j] = 0.0;
    for (i=0;i<num_searches;i++)
      precision[j] += searches[i].precision[j]; //acumula os precision de toddas as consultas
    precision[j] /= (float)num_searches;   //e divide pela qtd de consultas
  }

  //Liberando memoria
  for (i=0;i<num_searches;i++) {
    free(searches[i].cls_name);
    free(searches[i].key_base);
    for (j=0;j<num_results;j++) {
        free(searches[i].results[j].cls_name);
        free(searches[i].results[j].key_name);
    }
    free(searches[i].results);
  }
  free(searches);
  free(max_distance);

  fclose(fsearch);
  fclose(fresult);
  fclose(fdistTrec);

  fcurve = fopen(results, "w");
  if (fcurve == NULL) {
    fprintf(stderr,"Cannot open %s.\n",results);
    exit(-1);
  }
  for (i=0;i<NUM_AVERAGE_POINTS;i++)
    fprintf(fcurve,"%f %f\n",0.1 * i,precision[i]);

  fclose(fcurve);

  //return(0);
}
