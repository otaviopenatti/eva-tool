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
  char distTrecName[100]; //saves the file name of the distance files from trec
  double *max_distance; //vector to store the max distance for each query - this is used to converts the distance to similarity for the trec_eval

  //reading query file
  fsearch = fopen(queries, "r");
  if (fsearch == NULL) {
    fprintf(stderr,"Cannot open %s.\n",queries);
    exit(-1);
  }

  //reading distance file
  fresult = fopen(distances, "r");
  if (fresult == NULL) {
    fprintf(stderr,"Cannot open %s.\n",distances);
    exit(-1);
  }

  //reading distance=0 or similarity=1 option
  if (dis_sim != 0 && dis_sim != 1) {
    fprintf(stderr,"Choose 0 for distance or 1 for similarity\n");
    exit(-1);
  }

  fscanf(fsearch,"%d\n",&num_searches); //number of queries in the query file

  fscanf(fresult,"%d\n",&i); //number of queries in the distance file
  if (i != num_searches) {  //they must be the same in the two files
    fprintf(stderr,"Number of searches in %s and %s are different.\n",queries,distances);
    exit(-1);   //if they are different, abort execution
  }

  fscanf(fresult,"%d\n",&num_results); //number of database images in the distance file

  //creates vector of queries
  searches = (Search *) calloc(num_searches, sizeof(Search));

  max_distance = (double *) calloc(num_searches, sizeof(double));

  //for each query
  for (i=0;i<num_searches;i++) {

    max_distance[i] = -1; //initializes the vector of max distances

    fgets(buffer, MAX_BUFFER_SIZE, fsearch);  //reading query file

    //reading the query image class
    strng = strtok(buffer,"/"); //splits in the first '/' char
    searches[i].cls_name = (char *) calloc(strlen(strng)+1, sizeof(char));
    sprintf(searches[i].cls_name, "%s", strng);

    //reading query image name
    strng = strtok(buffer+strlen(strng)+1,"\n");  //gets the rest of the line until '\n' (from query file)
    searches[i].key_base = (char *) calloc(strlen(strng)+1, sizeof(char));
    sprintf(searches[i].key_base, "%s", strng);

    //creates structure for the results of the current query
    searches[i].results = (Result *) calloc(num_results, sizeof(Result));
    searches[i].num_relevants = 0;  //initializes the counter of relevants with zero

    //for each image that was compared with the current query:
    for (j=0;j<num_results;j++) {
        //gets the distance between the query i and the image j
        fgets(buffer, MAX_BUFFER_SIZE, fresult); //reads the results file
        searches[i].results[j].distance = atof(strrchr(buffer,'\t')+1);  //finds the last '\t' occurrence and inserts the distance between query and image in the results file

        //checking max distance for the descriptor
        if (searches[i].results[j].distance > max_distance[i]) {
            max_distance[i] = searches[i].results[j].distance;
        }

        //reading image class
        strng = strtok(strchr(buffer,'\t')+1,"/");  //finds '\t' and, from this occurrence, splits the line in the '/' character

        searches[i].results[j].cls_name = (char *) calloc(strlen(strng)+1, sizeof(char));
        sprintf(searches[i].results[j].cls_name, "%s", strng);

        //reading image name
        strng = strtok(strchr(buffer,'\t')+1+strlen(strng)+1,"\t");
        searches[i].results[j].key_name = (char *) calloc(strlen(strng)+1, sizeof(char));
        sprintf(searches[i].results[j].key_name, "%s", strng);

        //checking if query and image classes are the same
        if (strcmp(searches[i].cls_name,searches[i].results[j].cls_name) == 0) {
            searches[i].results[j].relevant = 1;  //indicates the classes are the same
            searches[i].num_relevants++; //increments the number of relevant images for the query
        }
        else
            searches[i].results[j].relevant = 0; //indicates the classes are different
    }
  }

    //creating file to store distances in the trec_eval format
    if (treceval) {
        sprintf(distTrecName, "%s_distances.trec", desc_name);
        fdistTrec = fopen(distTrecName, "w");
        if (fdistTrec == NULL) {
            fprintf(stderr,"Cannot create %s.\n",distTrecName);
            exit(-1);
        }
    }

  //for each query
  for (i=0;i<num_searches;i++) {
    if (dis_sim == 0) {  //if distance
      qsort(searches[i].results,num_results,sizeof(Result),&ResultCompare); //sort results
    } else {             //if similarity
      qsort(searches[i].results,num_results,sizeof(Result),&ResultCompareSim); //sort results in inverse order
    }
    if (treceval) {
        for (j=0;j<num_results;j++) {
            //creating input for trec_eval: query_name x class_img/img_name index similarity text
            fprintf(fdistTrec, "%s %d %s/%s %d %f %s\n", searches[i].key_base, 1, searches[i].results[j].cls_name, searches[i].results[j].key_name, j, ((max_distance[i]-searches[i].results[j].distance)/max_distance[i]), "trec_run");
        }
    }

    num_relevants = 0; //initializes relevants counter
    j = 0; //initializes j - counter of retrieved images
    float recall=0.0, precision=0.0;
    int last_k = 0;  //saves the last recall which already have precision metric computed
    while ((j<num_results) && (recall<1.0)) { //scans each result, until the end or until k achieving an average value
      if (searches[i].results[j].relevant) { //if retrieved images is relevant, increments num_relevantes
        num_relevants++;
      }

      precision = (float) num_relevants/(float)(j+1); //computes current precision --> (number of relevants retrieved / total retrieved)
      recall = (float) num_relevants / searches[i].num_relevants;  //computes current recall
      for (k=last_k; k<=((int)(recall*100.0)/10); k++) { //copia o mesmo precision para todos até a parte inteira de (recall/10)
        searches[i].precision[k] = precision;
      }
      last_k = k; //updates the last recall with the computed precision

      j++; //goes to the next retrieved image
    }
  } //end - scan of queries

  //computes the average of precision values for all queries
  for (j=0;j<NUM_AVERAGE_POINTS;j++) {
    precision[j] = 0.0;
    for (i=0;i<num_searches;i++)
      precision[j] += searches[i].precision[j]; //cumulates the precision values of all queries
    precision[j] /= (float)num_searches; //and divides by the number of queries
  }

  //Free memory
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

}
