CREATE TABLE Experiment (
  id serial NOT NULL PRIMARY KEY,
  descr VARCHAR(255) NULL,
  email VARCHAR(50) NULL
);

CREATE TABLE ImageDatabase (
  id serial NOT NULL PRIMARY KEY,
  name VARCHAR(45) NULL,
  path VARCHAR(255) NULL,
  descr VARCHAR(255) NULL,
  classified boolean
);

CREATE TABLE Descriptor (
  id VARCHAR(20) NOT NULL PRIMARY KEY,
  name VARCHAR(60) NULL,
  author VARCHAR(255) NULL,
  type integer NULL
);

CREATE TABLE EvaluationMeasure (
  id serial NOT NULL PRIMARY KEY,
  name VARCHAR(45) NULL
);

INSERT INTO evaluationmeasure VALUES (1, 'Extraction time');
INSERT INTO evaluationmeasure VALUES (2, 'Distance time');

CREATE TABLE ExperimentImageDatabase (
  IdImageDatabase serial NOT NULL,
  IdExperiment serial NOT NULL,
  FOREIGN KEY(IdImageDatabase)
    REFERENCES ImageDatabase(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(IdExperiment)
    REFERENCES Experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);

CREATE TABLE ExperimentDescriptor (
  IdDescriptor VARCHAR(20) NOT NULL,
  IdExperiment serial NOT NULL,
  FOREIGN KEY(IdDescriptor)
    REFERENCES Descriptor(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(IdExperiment)
    REFERENCES Experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);

CREATE TABLE ExperimentEvaluationMeasure (
  IdExperiment serial NOT NULL,
  IdEvaluationMeasure serial NOT NULL,
  FOREIGN KEY(IdExperiment)
    REFERENCES Experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(IdEvaluationMeasure)
    REFERENCES EvaluationMeasure(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);

CREATE TABLE experimentuserevaluation (
  id serial NOT NULL PRIMARY KEY,
  idexperiment serial NOT NULL,
  iddescriptor character varying(20) NOT NULL,
  fvquery text NOT NULL,
  p10 double precision NOT NULL,
  p20 double precision NOT NULL,
  p25 double precision NOT NULL,
  insertion_timestamp timestamp NOT NULL,
  user_email character varying(50) NOT NULL,
  FOREIGN KEY(idexperiment)
    REFERENCES experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(iddescriptor)
    REFERENCES descriptor(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);

CREATE TABLE experimenttime (
  idtime serial NOT NULL,
  idexperiment serial NOT NULL,
  iddescriptor character varying(20) NOT NULL,
  idevaluationmeasure serial NOT NULL,
  value double precision NOT NULL,
  stddev double precision NOT NULL,
  FOREIGN KEY(idexperiment)
    REFERENCES experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(iddescriptor)
    REFERENCES descriptor(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(idevaluationmeasure)
    REFERENCES evaluationmeasure(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);CREATE TABLE distance (
  iddistance serial NOT NULL PRIMARY KEY,
  idexperiment serial NOT NULL,
  iddescriptor character varying(20) NOT NULL,
  fv1 text NOT NULL,
  fv2 text NOT NULL,
  distance double precision NOT NULL,
  FOREIGN KEY(idexperiment)
    REFERENCES experiment(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION,
  FOREIGN KEY(iddescriptor)
    REFERENCES descriptor(id)
      ON DELETE NO ACTION
      ON UPDATE NO ACTION
);


CREATE INDEX index_distance_experiment_descriptor ON distance (idexperiment, iddescriptor);

CREATE INDEX index_distance_experiment ON distance (idexperiment);

CREATE INDEX index_distance_experiment_descriptor_fv1_0 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 0);

CREATE INDEX index_distance_experiment_descriptor_fv1_1 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 1);

CREATE INDEX index_distance_experiment_descriptor_fv1_2 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 2);

CREATE INDEX index_distance_experiment_descriptor_fv1_3 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 3);

CREATE INDEX index_distance_experiment_descriptor_fv1_4 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 4);

CREATE INDEX index_distance_experiment_descriptor_fv1_5 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 5);

CREATE INDEX index_distance_experiment_descriptor_fv1_6 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 6);

CREATE INDEX index_distance_experiment_descriptor_fv1_7 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 7);

CREATE INDEX index_distance_experiment_descriptor_fv1_8 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 8);

CREATE INDEX index_distance_experiment_descriptor_fv1_9 ON distance (idexperiment, iddescriptor, fv1) WHERE ((idexperiment % 10) = 9);




