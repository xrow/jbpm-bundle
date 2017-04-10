# jbpm-bundle
Symfony Bundle for JBPM

A Symfony2 bundle to communicate to JBPM 6 API.


## Configuration example

You must configure bundle in app/AppKernel.php

    ```
    $bundles = array(
        ...
        ...
        new jBoss\jBPMBundle\jBossjBPMBundle(),
    );
    ```
You must configure routing in app/config/routing.yml

    ```
    j_bossj_bpm:
        resource: "@jBossjBPMBundle/Resources/config/routing.yml"
        prefix:   /jbpm
    ```
    
## In this bundle we have below features
### 1. API for to get the List the tasks
This API use to get the tasks list from Symfony bundle and you can access the API with following parameters.
API Call (Symfony running server): http://<servername>/jbpm/task/list

#### Required Params:
1. host : http://<jbpm-server>/jbpm-console
2. user : Should be valid username for jBPM console (like: admin,john, etc..)
3. password : Should be valid password for jBPM

#### Optional Params:
1. task_id : It should be numerical value, (1,2,3.....). If multiple status passing using comma separated(,)
2. status : It should be valid status in list (Ready, Inprocess, Reserved, Suspended and
3. Completed and Failed), If multiple status we have to send as comma separated(,)
4. potential_owner : Should be valid user of jBPM console (like: admin,john, etc..)
5. task_owner : Should be valid user of jBPM console (like: admin,john, etc..)
6. process_instance_id : It should be numerical value only. Also should not use negative numbers


### 2. API for to change the task status

This API use to change the task status like “Complete”,”Release”, “Claim”, etc... jBPM have some rules to task status change like below.

API Call (Symfony running server): http://[servername]/jbpm/task/updatestatus

Methods : POST

#### Required Params:
1. host : http://<jbpm-server>/jbpm-console
2. user : Should be valid username for jBPM console (like: admin,john, etc..)
3. password : Should be valid password for jBPM.
4. task_id : It should be numerical value, (1  ,2,3.....). It should be single task id.
5. status : It should be valid status. Change status details as follow.

#### Status Update Conditions
1. If the task in InProcess state, we can only change status to Stop, Suspend, Release or Complete.
2. If the task in Ready state, we can only change status to Start, Suspend or Claim.
3. If the task in Reserved state, we can only change status to Start, Suspend, Resume or Release.
4. If the task in Complete state, we can only change status to Resume.
5. If the task in Suspended state, we can only change status to Resume.



### 3. Task executor
This Symfony interactive command will execute the task automatically. In order to execute the "Reversed" & “InProgress” task in following procedure. 

#### Reversed Task
It will get the "Reversed" tasks for provided user and will "Start" the progress and then mark the task as "Complete" automatically. If there is any error on the doing the mentioned process then we will automatically "Suspend" the task.

#### InProgress Task
It will get the "InProgress" tasks for given user and will mark the task as "Complete". If there is any error on the doing the mentioned process then we will automatically "Suspend" the task.


Below syntax use to run the command

    ```
    php app/console jbpm:task-execute <host> <user> <password>
    ```
