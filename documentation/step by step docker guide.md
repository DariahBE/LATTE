# Deploying LATTE components in Docker. 
This guide describes the full process of deploying the three LATTE subcomponents using Docker and connecting them on a single network layer. This guide should be followed precisely to ensure the three components can exchange data with each other.

## 1. Prerequisites: 
- Docker installed on your machine.
- Admin rights. 

## 2. Component overview: 
Latte use three separate components which are all deployed using docker. 
1) NEO4J backend: database where all the data is held and can be queried using Cypher commands. 
2) LATTE web: The web based interface where research data can be queried, updated, created and deleted. 
3) LATTE connector: Python based entity extractor running in the backend to extract entities from a text. 

## 3. Component installation: 

### 3.1. NEO4J backend:
LATTE used NEO4J extended with APOC procedures to store research data. User data is stored in a separate SQLITE database. The latter work automagically if you have everything set up in your Apache configuration. The NEO4J database takes some time to set up, but all the heavy lifting is handled by Docker. These instructions are written for Windows environments. If you wish to use Docker to set up NEO4J it is required to have it installed on your machine. It is possible to deploy NEO4J without using NEO4J, note that you'll need to manually configure it and you will need to set up the APOC-plugin. If you have little or not experience with NEO4J it is recommended to use Docker. This guide assumes you have docker installed. Deploying on other OS's is possible and only require minimal changes. When deploying multiple backend containers, step 3.1 (with all subcontent) should be repeated in full. When doing so, pay close attention to steps 3.1.3 and 3.1.4. 

#### 3.1.1 Configuring the docker-compose file.
A docker-compose file holds the configuration for the docker container. Here you determine the authentication between the database and the application as well as the directory which is used as a persistent volume to save data, to load the APOC-plugin from and to read the NEO4J configuration. 
Please not that APOC is required for the PHP-app to work. The following sections describe how to set these parts up:

#### 3.1.2 Preparing your system: 
- The `docker-compose.yml` file is included in the GitHub repo in `documentation/assets`.
- Download the dockerfile from the GitHub repo.
- Create a folder on your hard drive that's designated to hold the Docker data and configuration (You can keep all your docker containers in this folder - so if it exists already, you can use it again as long as the subfolder in here is unique.). 
    - e.g. `C:\dockerFiles\`
- In that folder create a subfolder of your choosing. In this document we'll be using `NEO4J_backend` as a clear descriptive name for the folder. The full path is now: `C:\dockerFiles\NEO4J_backend\`. All data and configuration related to the Docker based instance of NEO4J is now stored in a dedicated folder. 
- Move the downloaded `docker-compose.yml` file and put it in the created `NEO4J_backend` folder. To configure it, open it with an editor of your choice (e.g. [Notepad++](https://notepad-plus-plus.org/) ). You'll need to change the sections related to authentication and copy config files to their designated folders.

#### 3.1.3 Authentication: setting up a database user and password. 
-  **It's important to update the password of our Docker instance and the compose file uses a weak default**. 
- In the **environment** section you find the following entry: 
    - `- NEO4J_AUTH=neo4j/password`
change the password to something more secure. And optionally change the username too. A abstract authentication string is: `NEO4J_AUTH=<username>/<password>` Imagine the username and password for the NEO4J database are "neo4j" and "H7SG$0djE3vS" you should change the entry to `NEO4J_AUTH=neo4j/H7SG$0djE3vS` The given username and related password will have to be set in the web application `config.inc.php` file. It's importan to keep these details handy. Passing these details to the web app is covered in section *5 LATTE Web configuration*.

### 3.1.4 Backend configuration: exposed ports and persistent folders.
In the same file that was edited in step 3.1.3, you'll proceed to configure the exposed ports and persistent folders. 

**Ports**
- By using docker it is possible to deploy multiple NEO4J instances on a single machine by modifying the exposed port. When two or more NEO4J containers are deployed it's important to map the two containers to two distinct ports. Furthermore you should avoid configuring the container to use a port that's already in use by another application. By default NEO4J is exposed to ports 7474 and 7687. These ports are fine to use when running a first NEO4J container. When a second container is added you should use different port numbers.
- In the *ports* section of the config file you'll see two entries that look like this: ```7474:7474``` and ```7687:7687```. In a Docker context, these can be interpreted as ```<exposed port to host>:<internal container port>```. You are only supposed to modify the part of the exposed ports. A typical use case where modification is required is when two or more NEO4J containers are deployed. The given ports will have to be set in the web application `config.inc.php` file. It's importan to keep these details handy. Passing these details to the web app is covered in section *3.2 LATTE web*.

**Folders**
- By setting persistent folders you can garantee that data is kept across container restarts. This task should be done in tandem with deploying the APOC dependency. The folder names used in this document are `plugins`, `data` and `conf` and are just suggestions. Unlike the ports these can be kept the same across multiple containers as the path to them is unique. You are free to use other names. 
- The APOC dependency should be deployed to the plugins folder: 
    The plugins folder is required to load the APOC plugin. The correct version of APOC comes with the GitHub repo and can be found in `/documentation/assets/`. It comes as a .jar file with the name `apoc-4.4.0.1-all.jar`. Unless you have good reason to change the name of the plugins folder and volume you can leave these settings as they are. You are however required to create a folder `plugins` and copy the `apoc-4.4.0.1-all.jar` file in this directory. The full path to this `plugins` folder is `C:\dockerFiles\NEO4J_backend\plugins\`.
- Another folder you need to create is a persisten data folder. You can call this folder `data`, in this case the full path to this folder becomes: `C:\dockerFiles\NEO4J_backend\data\`. This is the folder where research data is kept as a cypher database. This folder should be treated with care and ideally becomes part of a redundant backup strategy. You do not need to move any file into this folder. 
- A third and final folder you need to create is a config folder called `conf`. Here the configuration details of the database are kept. 
    The config folder is required to set the behaviour of the NEO4J database. A template version is included in the GitHub repon and can be found in `/documentation/assets/`. It comes as a .conf file with te name `neo4j.conf`. Unless you have good reason to change the name of the config folder and volume you can leave these settings as they are. It is possible to modify the settings in `neo4.conf` for this please refer the Neo4J manual. If you want to import data from a .csv file you should modify to the import configuration in `neo4j.conf` to (temporarily) facilitate imports from multiple directories. Or configure docker to attach an `/import/` volume. The name of the created database by default is `latte`. It can be changed in `neo4j.conf` in the `dbms.default_database` key. Whatever value set to this key should be passed to the `config.inc.php` file in the web application. The configured `neo4j.conf` file should be placed in a `conf` folder the full path to this folder is `C:\dockerFiles\NEO4J_backend\conf\`.
    For the name of the database use only alphanumerical characters.
If you changed the name of any of these three folders, you'll need to update the config file to reflect the changes you made. Folder mappings are present under the *volumes* section of the config file where you'll see three entries that look like this: ```./data:/data```, with the other folder names following a similar structure. In a Docker context, these can be interpreted as ```<exposed folder>:<internal folder>```. You are only supposed to modify the part of the exposed folders. It's important to note that the exposed folder is written relative to the docker compose file. So if you create the data, conf and plugin folders in the same folder as the docker-compose.yml file, you'll want to write `./data`, `./conf` and `./plugins`.

#### 3.1.5 Installing the container according to your configuration
If all the required folders have been created, the plugin and config files been copied to the correct locations and the password has been changed, you can now install the container. 
- Open a PowerShell window in `C:\dockerFiles\NEO4J_backend\` and type the following command: `docker-compose up -d`
- This will create a new docker Container as defined in the docker-compose file. 
- If all went succesfull, the newly created instance should be visible in Docker. 

### 3.2. LATTE connector:
Once the database has been deployed succesfully, you can build the entity extraction tool. This is a python based tool and has its own virtual environment managed from within the Docker container. By keeping it in Docker, it does not directly interact with your Python installation - in fact, it doesn't even matter if you have python installed or not. 
The LATTE connector can be downloaded from GitHub, once downloaded, extract it to a folder in your dockerfiles folders. In this section we'll assume that the dockerfiles are kept at `C:\dockerFiles` and that the name of the subfolder for the connector will be `LATTE_conn`. The name of the image we'll be making will be `latte_conn_img`. You are free to choose other names and directories. 

1) Extract the downloaded zip file to your docker folder. 
2) Open a PowerShell instance in your unzipped folder. You can check if you're in the right folder by using the ```ls``` command. You should see the content of your extracted folder listed. 
3) Build the docker image using the following command:
    docker build -t \<name of the latte image\> .
    As a name for the latte image we'll use: *latte_conn_img* so the command becomes: 
    ```docker build -t latte_conn_img .```

4) Deploy the image as a container using the following command: If you kept your port numbers of the LATTE Connector unchanged in the dockerfile, you can keep the portnumbers as they are in the example.
    docker run -d -p 8000:8000 --name \<containername\> \<choose an imagename\>
    ```docker run -d -p 8000:8000 --name latte_connector latte_conn_img```

remember the name of the container as you'll it later. 



The image is built, but will still need to be integrated with the database backend. This procedure is described step by step in section 4. For now remember the name of the image you created. 

### 3.3. LATTE web:
If the two previous steps completed without problems, you can proceed to deploying the web app. The web app is a PHP based tool that allows visual interaction with the database. For this document we'll assume the Web App is being deployed to `C:\dockerFiles\Web\LATTE`. For the sake of clarity in this document an imaginary URL was used *latte-tagger.test*, wherever you encounter this, replace it by your own deployment URL. 

1) Copy the content of the `/assets/webdeployment` folder into the docker folder you'd like to use for deployment. The three files and both folders should all be copied over. The entire LATTE content, excluding the *documentation* folder should be placed into the `html` folder. SSL certificates should be deployed to the `certs` folder. 
- If you do not plan on using SSL certificates (not recommended), comment out the following sections by prepending the lines with `#` in the `apache-config.conf` file: 
    ```
    RewriteEngine On
    RewriteCond %{HTTPS} !=on
    RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R=301,L]
    ```
    and
    ```
    SSLEngine On
    SSLCertificateFile /etc/letsencrypt/live/latte-tagger.test/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/latte-tagger.test/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf
    ```
2) Edit the `apache-config.conf` file to edit the host to listen to. This should be done for both the entry on port 80 (http) and 443 (https). If your domain is 'latte-tagger.test' the correct notation would be: 
```
    ServerAdmin webmaster@latte-tagger.test
    ServerName latte-tagger.test
    ServerAlias www.latte-tagger.test
```

3) Open a PowerShell window in the folder where you'll deploy the web app to and enter the build-command: 
`docker-compose up --build -d`



## 4. Component integration: 
The three components are now installed in separate, unconnected containers. By creating a network, these three containers will be able to exchange data with each other. Creating the network and linking the all three components is a trivial process. 
1) Create a docker network layer: 
    Use a clear and descriptive name for this network. We'll name it 'latte_connector_flask_network', you're free to choose another name as long as it's clear to you and technical staff. 

    docker network create \<your chosen network name\>: 

    as an example:
    ```docker network create latte_connector_flask_network```
2) Connect your earlier created cypher database to this network. You'll need the full name of the cypher container. 
    To find the full name of the cypher database, use the command: 
    ````docker ps```
    The names of the available docker containers are in the right column rendered in the Terminal under the heading *NAMES*. As an example, we'll use *neo4j-neo4j-1* as name. 
    The command to connect your cypher container to the newly created network is: 
    docker network connect \<your chosen network name\> \<name of neo4J container recovered using ps command (created in step 3.1).\>

    as example:
    ```docker network connect latte_connector_flask_network neo4j-neo4j-1```
3) Connect your earlier created container of the LATTE connector to this network, you can recover the name in a similar fashion as in step 2. 
    docker network connect \<your chosen network name\> \<name of LATTE connector container (created in step 3.2).\>

    as example: 
    ```docker network connect latte_connector_flask_network latte_connector```

4) Connect the web instance of LATTE to this network, recover the name of it as described in step 2: 
    docker network connect \<your chosen network name\> \<name of LATTE web container (created in step 3.3.)\>

    as example:
    ```docker network connect latte_connector_flask_network latte_final_writeup_doc-web-1```
3) Inspect the result of the network attachment: 
    use the command: docker network inspect \<name of the network made in step4\>
    ```docker network inspect latte_connector_flask_network```
    you should see the name of your three container printed out into the terminal. 

If all went well the containers added into this network can exchange the necessary data to perform their intended use. Communcation between docker containers is based on the name of the container; these container names need to be added in the config file of the latte web app. 

## 5. LATTE Web configuration (passing container names)
The LATTE web app needs to know the names of the other two containers to be able to communicate. For this you need to update the `config.inc.php`-file which you deployed to the `html/config/` directory in the container. On top of that the connection details to authenticate with the NEO4J backend are required too. 

### 5.1 NEO4J authentication: 
You need to provide a username, password and database name to the config file. The procedure to set these values were discussed in section 3.1.3 and 3.1.4 (subsection Folders). If you don't remember them, you can recover them from the relevant files created by you. The relevant sections to update are bundled together at the top of the file. 

To set the **username**, modify the following section of the config.inc.php file. 
$userName = '\<username here\>';

example: 
    ```$userName = 'neo4j';```

To set the **password** modify the following section of the config.inc.php file. 
$userPaswrd = '\<password here\>';

example:
    ```$userPaswrd = 'J9jd6b!Ax8*yVZ?m';```

To set the **database name** modify the following section of the config.inc.php file. 
$databaseName = '\<dbname.db here\>';
example: 
    ```$databaseName = 'latte.db';```

### 5.2 NEO4J connection: 
You'll also need to set the hostname, hostport and URI values to be able to connect to the actual container. 
Modify the **hostname** to match the name of the Docker container that holds the NEO4J datase. If you don't know the name of the container any more you can recover it by using the ```docker ps``` command or by inspecting the network: ```docker inspect network <networkname>``` (with \<networkname\> being the actual name of the Docker network.)
$hostname = '\<name of the neo4J container\>';
example: 
    ```$hostname = 'neo4j_backend-neo4j-1';```

**hostport**: 
Unless you modified the host port in the neo4j.conf file there's no need to modify this value. You can keep it at 7687. Should you have changed the port it can be updated like this: 
$hostport = \<portnumber here\>; 
example: 
```$hostport = 7687; ```

**latteConnector**: 
By default the port latteConnector exposes to is 8000; so if you didn't change the value for this in the Dockerfile, you can trust on it it'll be 8000. 
You'll need to provide two values separeted by `:`: 
Here you pass the name of the Latte connector container - the container created and set up in step *3.2*. The name of this container can be recoverred using the inspect command described above. To pass the correct value update the following section. 
$latteConnector = '\<containername\>:\<exposedport\>'; 
*Tip: * the container name is also required when you connect it to your network. 
example:
    ```$latteConnector = 'latte_networked_flask_connector:8000';```


