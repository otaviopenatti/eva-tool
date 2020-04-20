## Installation instructions for Eva
**(reminder: this instructions were created several years ago)**

Packages required (Linux environment):
- Apache
- Postgresql
- PHP5
- php5-pgsql
- libapache2-mod-php5
- python
- python-imaging
- python-ctypes
- python-psycopg2
- python-timeit
- jre (version > 5) (example: sun-java6-jre)


**1. Directories:**
 - Unpack the Eva's package and copy the "eva" directory to the Apache public area. For example, copy the "eva" directory into "/var/www".
 - Inside the "eva" directory there are the source codes.
    - The "img_databases" directory stores the image databases managed by Eva. It can be a real directory or a symbolic link. Also, it can be a real directory containing a symbolic link for each image database to be used.
    - The "results" directory stores the experiments results. Attention: the results can become large depending on the kind of experiment; maybe, using "results" as a symbolic link can be a solution for storage limitations.

 - Change permissions and ownership for files into "eva" directory:
    - chown -R <user>:<group> * (<user> can be the user who is going to eventually edit the files; maybe this user is the same as the database owner)
    - chmod o+w results         (this makes apache user able to create and change files into these directories)
    - chmod o+w descriptors
    - the directory of an image database must have execution permission (+x)
    - trec_eval files (codes/trec_eval/) must have execution permission (+x)


**2. Database creation:**
 - Create a database in Postgres
    - Login as "postgres" user (using a terminal session)
       - If you do not have the "postgres" user password, maybe is because this password has not been created yet. In this case, use the "passwd postgres" command to create a password for "postgres" user in Linux (you need root for that)
       - Login in as "postgres" user: "su postgres"
    - Create a user to be the Eva's database owner: "createuser otavio -P" ("otavio" is just an example). The "-P" option requests the creation of a password for the user. Keep this password because it will be necessary for Python and PHP to connect into the database. During the user creation, use "Yes" ("y") only for the question that asks for the user permission to create databases.

    - Login as the user just created (the database owner) or user the "-U" option to explicit it in the following commands:
       - Create the Eva's database: "createdb eva" (the name can be different)
       - Run the SQL script for the tables creation:
           - psql -d eva < eva_database_creation.sql

Ps.: Postgres, by default, stores the databases into the same disk partition where it is installed. As some Eva's tables can become very large (like the "distance" table), Eva's database can consume too much space from the system partition (place where Postgres is usually installed). If desired, it is possible to change Postgres' storing destination.


**3. Connection to the database:**
  - Edit "util.php" file adjusting the user and password for the database
     - Update the parameters from "pg_connect" function (host, dbname, user, password). The "abcd" function can be used as explained the in the function comments.
  - Edit "codes/util.py" file adjusting the user and password for the database
     - Update the parameters for "connect" function (host, dbname, user, password)
   
Ps.: Depending on your Postgres configurations, it will be necessary to make changes in Postgres configuration files for it to accept PHP connections (probably in the "postgresql.conf" and/or "pg_hba.conf" files)


**4. Server architecture issues:**
  - Recompile the Precision x Recall curves generation (analyzer). Depending on your server's architecture, the compiled version supplied will not work.
    - Inside "codes/analyzer" directory there is the file "analyze_plugin.c"
    - Recompile it as follows:
       - gcc analyze_plugin.c -fpic -shared -o analyze.so

  - Adjust the warning message in the insert descriptor interface: in item 2 from "Insert Descriptor" interface (cadastrar_descritor.php), there is the warning message for the plugins compilation
    - If the server that is running Eva is 64 bits, no changing is necessary
    - If the server is 32 bits, change the message into the "cadastrar_descritor.php" file
    - The plugins must be compiled in the same architecture where they will be executed!

**5. Finish!**
  - The installation is ready. Verify if Eva is accessible by your web browser. Use the URL of your Apache: "http://localhost/eva" or "http://server:1234/eva", for example.
  - To start using Eva, insert image descriptors (following the instruction in the Eva's interface) and insert image databases. After that, it is possible to run experiments.
  - The execution log of an experiment is put into the "results/<id>/exp_details_<id>.log" file, where <id> is the experiment id. The log can also be seen by Eva's interface. If the experiment fails to complete, take a look at the log file.
  - Having doubts or problems, send me an e-mail: otavio@lis.ic.unicamp.br or penatti@ic.unicamp.br
  - More details about Eva are in the MIR2010 paper and some information is in the web page you have downloaded Eva.

Ps.: Eva uses session variables in some pages, mainly in the image visualization pages. Therefore, try not to open more than one browser tab with Eva when viewing the retrieved images. Some PHP files clean the session when loaded.




--------------------- 
## Using the interface for the user-oriented evaluation:

After running an experiment that uses a pre-define list of query images, Eva will generate a button inside the experiment details interface or in the list of run experiments. This button is called "Avaliação com usuários" or "AVAL", respectively. Any of them will open the user-oriented effectiveness evaluation interface.

As this button is only available inside Eva, in case you need to give this interface for the users to evaluate the descriptors, you will need a more friendly way for them to access it.
For that, follow the steps below:
  - Create a copy of the file "user_evaluation.php" that is inside the "codes" directory
  - Edit the file just created and, at line 8, put the id of the experiment you want to use the user-oriented evaluation. Remember that only experiments that used a pre-defined list of query images can be used.
  - Save the file and pass the link to this file for the users. The interface in this file has instructions for the evaluation process. Also, it requests the user e-mail before starting the evaluation.

After the evaluations were made, you need to get the results directly inside the "experimentuserevaluation" table.

To select all the evaluations of a certain experiment, use a SQL like this:
SELECT * FROM experimentuserevaluation WHERE idexperiment=28;   ('28' is an example)

Table "experimentuserevaluation" stores the P@10, P@20, and P@30 of each evaluation, that is, it stores the results of the evaluation for each query image, each descriptor, and each user.
Postgres has some grouping functions like AVG, MAX and MIN that can be used to get, for example, the average P@10 of a given descriptor.

The details about the user-oriented effectiveness evaluation interface are in the MIR2010 paper.

