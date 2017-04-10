# jBPMBundle

A symfony2 bundle to communicate to JBPM 6 API.


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
