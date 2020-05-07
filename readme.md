**Completed Features**

Currently, data is stored in both MySQL DB and MongoDB ( AWS DocumentDB service is used) 
MongoDB has one "instances" table, storing the necessary info about instances. It is needed to clone functions and restore instance, after its removal from AWS EC2 

=====================================

For user with " USER" role 
1) After signing up or signing in, the user gets to bots management menu and has access to the following pages:
- Running Bots ( managing launched instances with bots )
- Available Bots ( viewing available bots and launching one or several instances with a selected bot with parameters )
- Bots Schedule ( creating a schedule for launched instances with an option to specify when to start or stop instance with a bot during a week)
- My Subscription ( selecting monthly subscription with a payment )
- Credit Usage History ( viewing a credit usage history )

An advanced menu is available for the user with "Admin" role: 

- Users ( Viewing and managing users )
- Running Bots ( Managing launched instances with bots)
- Available Bots ( Viewing available bots and starting one or several instances with a selected bot with parameters )
- Bots Settings ( Viewing regions with limits displaying and an option to choose AMI by default for every region from existing ones on AWS. Regions syncing) 
- Bots Schedule ( Creating a schedule for launched instances with an option to specify when to start or stop instance with a bot during a week)
- Bots Sessions ( Viewing instances scheduled start/stop history )
- Subscription Plans (Subscription plans management)
- Credit Usage History ( Viewing a credit usage history )
- Low Credit Notifications ( Setting a percentage (credit balance on the account) for notifying users via email) Currently, it is not used since the functionality was postponed
- CMS ( Pages/posts management system )

=====================================

The functionality performed in the background by schedule (CRON) 
1) AwsSyncAmis ( aws:sync-amis ) is launched every 30 min 
Synchronizes AMI's list, which are available in each region for selecting in the region settings 

2) CalculateInstancesUpTime ( instance:calculate-up-time ) is launched every 10 min 
Calculates the Uptime time of all launched instances and how many credits the current instance requires (the payment is hourly charged, one credit is charged for every next hour) 

3) CalculateUserCreditScore ( instance:calculate-user-credit-score ) is launched every 10 min 
Checks for the number of credits the user has in his account and charges credits for using the instances. If the user lacks credits - all of his instances are stopped 

4) CleanUpUnused ( instance:clean ) - is launched hourly 
Cleans up unused AWS security groups

5) InstanceStartScheduling ( instance:start ) is launched per minute 
Runs users' instances, which are specified for a scheduled launch considering the time and time zone chosen by the user 

6) InstanceStopScheduling ( instance:stop ) is launched per minute 
Stops users' instances, which are specified for a scheduled stop considering the time and time zone chosen by the user 

7) InstanceSyncScheduling ( instance:sync ) is launched every 5 min 

- Synchronizes all instances in all regions 
- If the user's email is specified in the tags and such instance is missing in DB - we create it 
- Remove all the instances with no data (if the issue occurred and the instance wasn't created on AWS) 
- Apply Terminated status - if such instance exists in our DB, but was removed on AWS 

8) SyncDataFolders ( sync:folders ) is launched per minute 
Synchronizes the structure of folders, screenshots, logs and JSON files stored on AWS S3 with our DB 

9) SyncLocalBots ( bots:sync-local ) is launched once a day 
Synchronizes bots list and their parameters using puppeteer GIT repo, which is set up on the project 

=====================================

The functionality performed in the background ( JOBS )

1) InstanceChangeStatus - changes an instance status ( 'terminated', 'running', 'stopped' )
2) RestoreUserInstance - restores instance after its removal with the same Tag Name - it allows
to get old data from AWS S3)
3) StoreUserInstance - creates and starts an instance on AWS 
4) SyncBotInstances - manual start of all instances syncing
5) SyncLocalBots - manual start of all bots syncing
6) SyncS3Objects - syncing our database with objects on AWS S3 (Screenshots, logs and JSON files)
7) UpdateInstanceSecurityGroup - adding user's IP, which he used for entering,
to his instances Security Group on AWS 
