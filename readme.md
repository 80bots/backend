![80bots backend](public/80bots-logo.svg)

# 80bots backend

## Brief information:

  Currently, data is stored in  MySQL DB  ( AWS DocumentDB service is used). It is needed to clone functions and restore instance, after its removal from AWS EC2 

#### After signing up or signing in, the user gets to bots management menu and has access to the following pages:
  - Users - Viewing and managing users ;
  - Running Bots - Managing launched instances with bots;
  - Deploy Bots - Viewing available bots and starting one or several instances with a selected bot with parameters;
  - Add Bot - Custom bot creation page;
  - Bots Settings - Viewing regions with limits displaying and an option to choose AMI by default for every region from existing ones on AWS. Regions syncing;
  - Bots Schedule - Creating a schedule for launched instances with an option to specify when to start or stop instance with a bot during a week;
  - Bots Sessions - Viewing instances scheduled start/stop history.

#### The functionality performed in the background by schedule (CRON): 
  1. AwsSyncAmis ( aws:sync-amis ) is launched every 30 min 
  (Synchronizes AMI's list, which are available in each region for selecting in the region settings);

  2. CalculateInstancesUpTime ( instance:calculate-up-time ) is launched every 10 min 
  (Calculates the Uptime time of all launched instances);

  3. CleanUpUnused ( instance:clean ) - is launched hourly 
  (Cleans up unused AWS security groups);

  4. InstanceStartScheduling ( instance:start ) is launched per minute 
  (Runs users' instances, which are specified for a scheduled launch considering the time and time zone chosen by the user); 

  5. InstanceStopScheduling ( instance:stop ) is launched per minute 
  (Stops users' instances, which are specified for a scheduled stop considering the time and time zone chosen by the user);

  6. InstanceSyncScheduling ( instance:sync ) is launched every 5 min: 
  - Synchronizes all instances in all regions; 
  - If the user's email is specified in the tags and such instance is missing in DB - we create it;
  - Remove all the instances with no data (if the issue occurred and the instance wasn't created on AWS);
  - Apply Terminated status - if such instance exists in our DB, but was removed on AWS; 

  7. SyncDataFolders ( sync:folders ) is launched per minute 
  (Synchronizes the structure of folders, screenshots, logs and JSON files stored on AWS S3 with our DB);

  8. SyncLocalBots ( bots:sync-local ) is launched once a day 
  (Synchronizes bots list and their parameters using puppeteer GIT repo, which is set up on the project); 

####The functionality performed in the background ( JOBS ):

  1. InstanceChangeStatus - changes an instance status ( 'terminated', 'running', 'stopped' );
  
  2. RestoreUserInstance - restores instance after its removal with the same Tag Name - it allows
  to get old data from AWS S3);
  
  3. StoreUserInstance - creates and starts an instance on AWS;
  
  4. SyncBotInstances - manual start of all instances syncing;
  
  5. SyncLocalBots - manual start of all bots syncing;
  
  6. SyncS3Objects - syncing our database with objects on AWS S3 (Screenshots, logs and JSON files);
  
  7. UpdateInstanceSecurityGroup - adding user's IP, which he used for entering,
  to his instances Security Group on AWS.

#### Laravel Echo Server Setup:

  1. In order to start Laravel Echo Server, a config file should be generated; 

  2. Specify Laravel Echo Server constants in .env config file, a list of possible config constants can be found here: /backend/config/echo-server.php
  You can find a full list of Laravel Echo Server settings here: https://github.com/tlaverdure/laravel-echo-server

  3. Run the commands after specifying the necessary settings in order to generate laravel-echo-server.json file:
```
cd saas-laravel
php artisan echo-server:init
```

  4. The config file laravel-echo-server.json will be generated in the project root directory;
   
  5. Start the server with the current settings.
```
laravel-echo-server start
```
