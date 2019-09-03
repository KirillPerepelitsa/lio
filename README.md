# Command line interface, [Lamp-io](https://www.lamp.io/) platform

Installation
------------
##### As a Global Composer Install
```sh
$ composer global require lamp-io/lio 
```

##### As local composer package
```sh
composer require lamp-il/lio
```

##### Download as a PHAR
[lio.phar]()

Usage
------------
```sh
lio command [options] [arguments]
```

Commands
------------
### Global options
    
    * [-j][--json] Output as a raw json
    * [-h][--help](bool) Display this help message
    * [-q][--quiet](bool) Do not output any message
    * [-V][--version](bool) Display this application version
    * [--ansi](bool) Force ANSI output
    * [--no-ansi](bool) Disable ANSI output
    * [-n][--no-interaction](bool) Do not ask any interactive question
    * [-v|vv|vvv][--verbose](bool) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

### Deploy
1. #### deploy [--laravel] [\<dir>]

    Deploy your app.
    
    Deploy command will create [lamp.yo.yaml](#lampioyaml) config file, inside of your project root, deploy process can be controlled by editing this file, you can check doc here [lamp.yo.yaml](#lampioyaml)
    
    Arguments:
    
    * `<dir>[optional](string){$PWD}` Path to your project, default your current working directory
    
    Options:
    * `[--laravel](bool){false}` Specify your app as a laravel project 

2. #### deploy:list <app_id>

    Get list of available deploys
    
    Arguments:
        
    * `<app_id>(string)` The ID of the app

### Auth
1.  #### auth [-u][--update_token]

    Will ask you to input your auth token

    Options:

    * `[-u][--update_token](bool){false}` will allow to override your current auth token
    
### Apps
1.  #### apps:list

    Will output you all your apps associated to your token
    

2.  #### apps:describe <app_id>

    Will output all app info

    Arguments:

    * `<app_id>(string)` The ID of the app
    

3. #### apps:new <organization_id> [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--github_webhook_secret] [--webhook_run_command]

    Will allow you to create an app

    Arguments:

    * `<organization_id>[optional](string)` The ID(uuid) of the organization this app belongs to

    Options:

    * `[-d][--description](string){Default}` A description of your app
    * `[--httpd_conf](string){default appache config}` Path to your httpd.conf
    * `[--max_replicas](int){1}` The maximum number of auto-scaled replicas
    * `[-m][--memory](string){128Mi}` The amount of memory available (example: 1Gi)
    * `[--min_replicas](string){1}` The minimum number of auto-scaled replicas
    * `[--php_ini]{default php.ini}` Path to your php.ini
    * `[-r][--replicas](int){1}` The number current number replicas available. 0 stops app.
    * `[--vcpu](float){0.25} `The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret](string){''}` Github web-hook secret token
    * `[--webhook_run_command](string){''}` Github web-hook command
    
4. #### apps:update <app_id> <organization_id>  [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu]
    
    Will allow you to update selected app. Command should be executed with at least one option
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<organization_id>[optional](string)` The ID(uuid) of the organization this app belongs to
    
    Options:
    
    * `[-d][--description](string){Default}` A description of your app
    * `[--httpd_conf](string){default appache config}` Path to your httpd.conf
    * `[--max_replicas](int){1}` The maximum number of auto-scaled replicas
    * `[-m][--memory](string){128Mi}` The amount of memory available (example: 1Gi)
    * `[--min_replicas](string){1}` The minimum number of auto-scaled replicas
    * `[--php_ini]{default php.ini}` Path to your php.ini
    * `[-r][--replicas](int){1}` The number current number replicas available. 0 stops app.
    * `[--vcpu](float){0.25} `The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret](string){''}` Github web-hook secret token
    * `[--webhook_run_command](string){''}` Github web-hook command
    
5. #### apps:delete <app_id> [--yes][-y]

    Delete an app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    
    Options:
    
    * `[--yes][-y](bool)` Skip confirm delete question

### Files sub commands: 

1. ### apps:update:status <app_id> [--enable] [--disable]

    Enable/disable selected app
    
    Arguments:
        
    * `<app_id>(string)` The ID of the app
    
    Options:
    
    * `[--enable](bool)` Enable your stopped app
    * `[--disable](bool)` Disable your running app
    
### App backups

1.  #### app_backups:create <app_id>

    Back up files in app
    
    Arguments:
        
    * `<app_id>(string)` The ID of the app

2.  #### app_backups:list [-o][--organization_id]
   
    Return list of all your app backups
    
    Options:
    
    * `[-o][--organization_id](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

3. #### app_backups:describe <app_backup_id> 

    Return an app backup
    
    Arguments:
    
    * `<app_backup_id>(string)` The ID of the app backup
    
    
4. #### app_backups:download <app_backup_id> <dir>
    
   Download an app backup 
    
    Arguments:
        
    * `<app_backup_id>(string)` The ID of the app backup
    * `<dir>(string){$PWD}` Path to directory, where should be stored downloaded file. Default value current working directory
    
5. #### app_backups:delete <app_backup_id> [--yes][-y]

    Delete an app backup
    
    Arguments:
        
    * `<app_backup_id>(string)` The ID of the app backup
    
    Options:
        
    * `[--yes][-y](bool)` Skip confirm delete question
    
6.  #### app_backups:list [-o][--organization_id]

    Return list of all your app backups
    
    Options:
    
    * `[-o][--organization_id](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
### App runs

1. ### app_runs:new <app_id> <exec>

    Run command on app'
    
    Arguments:
    * `<app_id>(string)` The ID of the app
    * `<exec>(string)` Command that will be ran
    
2. ### app_runs:delete <app_run_id>
    
    Delete runned command
    
    Arguments:
    * `<app_run_id>(string)` ID of runned command
    
3. ### app_runs:list

    Get all runned commands on all apps associated to your token
    
4. ### app_runs:describe

    Run command on app
    
    Arguments:
    * `<app_run_id>(string)` ID of runned command
    
### Databases 

1. ### databases:new  [-d][--description] [-m][--memory] [--organization_id] [--mysql_root_password] [--my_cnf] [--ssd] [--vcpu]

   Create a new database
    
   Options:
   
   * `[-d][--description](string)` Description of your database
   * `[-m][--memory](string){512Mi}` Amount of virtual memory on your database
   * `[--organization_id](string)` Name of your organization
   * `[--mysql_root_password](string/bool)` Your root password for mysql 
   * `[--my_cnf](string)` Path to your database config file
   * `[--ssd](string){1Gi}` Size of ssd storage
   * `[--vcpu](float){0.25}` The number of virtual cpu cores available, default 0.25
   
2. ### databases:delete <database_id> [--yes][-y]

    Delete a database
    
    Arguments:
    
    *`<database_id>(string)` The id of database
    
    Options:
        
    * `[--yes][-y](bool)` Skip confirm delete question
    
3. ### databases:update <database_id> [-d][--description] [-m][--memory] [--organization_id] [--my_cnf] [--mysql_root_password] [--ssd] [--vcpu]
    
    Update a database. Command should be executed with at least one option
    
    Arguments:
        
    *`<database_id>(string)` The id of database
    
    Options:
    
   * `[-d][--description](string)` Description of your database
   * `[-m][--memory](string)` Amount of virtual memory on your database
   * `[--organization_id](string)` Name of your organization
   * `[--my_cnf](string)` Path to your database config file
   * `[--mysql_root_password](bool)` If you need to update root password, set it as true
   * `[--ssd](string)` Size of ssd storage
   * `[--vcpu](float)` The number of virtual cpu cores available, default 0.25
    
4. ### databases:list [--organization_id]

    Returns all allowed databases
    
    Options:
    
    * `organization_id` Filter output by organization id value
    
5. ### databases:describe <database_id>

    Returns a database
    
    Arguments:
    
    *`<database_id>(string)` The id of database

### Database backups

1. ### db_backups:new <database_id>

    Back up files in database
    
    Arguments:
    
    *`<database_id>(string)` The id of database
    
2. ### db_backups:delete <db_backup_id> [--yes][-y]

    Delete a db backup
    
    Arguments:
        
    *`<db_backup_id>(string)` The ID of the db backup
    
    Options:
        
    * `[--yes][-y](bool)` Skip confirm delete question
    
3. ###db_backups:list [--organization_id][-o]

    Return db backups
    
    Options:
    
    * `organization_id` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
4. ### db_backups:describe <db_backup_id>

    Return a db backup
    
    Arguments:
    
    *`<db_backup_id>(string)` The ID of the db backup
    
### Database restore jobs

1. ### db_restores:new 

    Create db restore job (restore a db backup to a database)
    
    Arguments:
    
    *`<database_id>(string)` The id of database
    *`<db_backup_id>(string)` The ID of the db backup
    
2. ### db_restores:delete <db_restore_id>

    Delete a db restore job
    
    Arguments:
    
    *`<db_restore_id>(string)` The ID of the db restore
      
3. ### db_restores:list [--organization_id][-o]

    Return db restore jobs
    
    Options:
    
    * `organization_id` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
4. ### db_restores:describe <db_restore_id>

    Return a db restore job
    
    Arguments:
    
    *`<db_restore_id>(string)` The ID of the db restore

### Files

1. #### files:list [-l][--limit] [--human-readable] [-r][--recursive] <app_id> <file_id>

    Return files from the root of an app (if not define <file_id>)
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `file_id(string){/}` The ID of the file. The ID is also the file path relative to its app root
    
    Options:
    
    * `[-l][--limit](int){1000}` The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out
    * `[--human-readable](bool){false}` Format size values from raw bytes to human readable format
    * `[-r][--recursive](bool){false}` Command is performed on all files or objects under the specified path
    
2. #### files:upload <file> <app_id> <remote_path>

    Upload file to selected app
    
    Arguments:
    
    * `<file>(string)` Path to file, that should be uploaded
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Path on app, where uploaded file should be saved
    
3. #### files:download <app_id> <file_id> <dir>

    Download files from selected app in a zip archive.
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<file_id>(string)` The ID of the file. The ID is also the file path relative to its app root.
    * `<dir>(string){$PWD}` Path to directory, where should be stored downloaded file. Default value current working directory
    
4. #### files:update <app_id> [<remote_path>] [<local_file>] [-r][--recur] [--command] [--source]
    
   This will update the file at specified file ID (file path including file name, relative to app root)
   
   Arguments:
       
   * `<app_id(string)>` The ID of the app
   * `<remote_path>(string)[optional]{}` File path on app, that should be updated
   * `<local_file>(string)[optional]{}` Path to a local file, which content will sent to remote. If not specified, will make your <remote_path> appache writable

5. #### files:delete <app_id> <remote_path> [--yes][-y]

    Delete file/directory on selected app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` Remote path on app, what file/directory you need to delete
    
    Options:
        
    * `[--yes][-y](bool)` Skip confirm delete question

### Files sub commands: 

1. #### files:update:unarchive <app_id> <remote_path>

    Extract your archived file, on your app
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` File path on app, that should be unarchived
    
2. ### files:update:fetch <app_id> <remote_path> <source>

    Fill file with fetched data
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` File path on app, that should be filled with fetched data
    * `<source>(string)` A URL that should be fetched

3. ### files:update:move <app_id> <remote_path> <move_path>

    Move file on app to another directory
    
    Arguments:
    
    * `<app_id>(string)` The ID of the app
    * `<remote_path>(string)` File path on app, that should be filled with fetched data
    * `<move_path>(string)` File path on app, which should be moved. NOTE: * target directory MUST exists, * move path MUST have same name as a target file

### Logs

1. ### logs:list [--organization_id][-o] [--pod_name][-p] [--start_time] [--end_time]

    Return logs
    
    Options
    
    * `[--organization_id][-o](string)` One organization_id. If omitted defaults to user's default organization
    * `[--pod_name][-p](string)` One pod_name. Uses wildcard prefix match
    * `[--start_time](string){date - 10min}` Start time conforming to RFC3339. Defaults to 10 minutes in the past
    * `[--end_time](string){date}` End time conforming to RFC3339. Defaults to now

### Organizations

1. ### organizations:update <organization_id> [--name] [--promo_code] [-p][--payment]

    Update an organization
    
    Arguments:
    
    * `<organization_id>(string)` The ID of the organization
    
    Options:
    
    * `[--name](string)` New organization name
    * `[--promo_code]`  Apply promo code
    * `[-p][--payment]` New Stripe source id

2. ### organizations:list

    Returns this user's organizations
    
###Organization users

1. ### organization_users:update <organization_user_id> [--admin]

    Update an organization/user relationship (Allow to set/remove selected user role as an organization admin)
    
    Arguments:
    
    * `<organization_user_id>(string)` The ID of the organization_use
    
    Options:
    
    * `[--admin](bool)` Set selected user as admin of organization (if you need to remove admin role from selected user, just omit this option)
    
2. ### organizations_users:list [--organization_id]

    Returns organization/user relationships
    
    Options:
    
    * `[--organization_id](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
3. ### organizations_users:describe <organization_user_id> 

    Returns a organization/user relationship
    
    Arguments:
    
    * `<organization_user_id>(string)` The ID of the organization_use
    
### Tokens

1. ### tokens:new [--description][-d] [--enable]

    Creates a new token
    
    Options:
    
    * `[--description][-d](string)` Token description
    * `[--enable](bool)` Enable new token (By default created token will be disabled)
    
2. ### tokens:delete <token_id> [-yes][-y]

     Delete a token
     
     Arguments:
         
     * `<token_id>(string)` The ID of the token
     
     Options:
     
     * `[-yes][-y](bool)` Skip confirm delete question
     
3. ### tokens:update <token_id> [--enable] [--disable]
 
    Update a token. If execute without options, it will disable token
    
    Arguments:
             
     * `<token_id>(string)` The ID of the token
     
     Options:
     
     * `[--enable](bool)` Enable new token
     * `[--disable](bool)` Disable token
    
4. ### tokens:list 

    Returns all tokens for this user
    
5. ### tokens:describe <token_id>
    
    Returns a token
    
    Arguments:
             
     * `<token_id>(string)` The ID of the token
    
### Users

1. #### users:list [--organization_id][-o] [--email][-e]

    Get all users from your account
    
    Options:
    
    * `[--organization_id][-o](string)` Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    * `[--email][-e](string)` Format size values from raw bytes to human readable format
    
### Phar updates

1. `self-update` Update your phar build to the latest release (will work only if you use phar build)


Composer scripts
------------

1. `composer build` Create phar build

# Lamp.io.yaml

* `release` ***(int)*** Id of your release (this value will be set automatically)
* `type` ***(string)*** Type of your application (e.g laravel, symfony)
* `app` ***(dictionary)*** Settings related to your lamp-io app
    * `attributes` ***(dictionary)*** Lamp-io app attributes, it use same values has [apps:new](#appsnew-organization_id--d--description---httpd_conf---max_replicas--m--memory---min_replicas---php_ini--r--replicas---vcpu---github_webhook_secret---webhook_run_command) command options
    * `id` ***(string)*** Lamp-io app id
    * `url` ***(string)*** Web app url (this value will be set automatically)
* `database` ***(dictionary)*** Settings related to your database
    * `id` ***(string)*** Lamp-io database id 
    * `connection` ***(dictionary)*** Connection params for database
        * `host` ***(string)*** DB host
        * `user` ***(string)*** DB user
        * `password` ***(string)*** DB password
    * `attributes` ***(dictionary)*** Lamp-io database attributes, it use same values has [database:new](#databasesnew---d--description--m--memory---organization_id---mysql_root_password---my_cnf---ssd---vcpu) command options
    * `sql_dump` ***(string)*** Absolute path to your sql dump, that you need to have imported to remote database
    * `type` ***(enum)*** Internal(db hosted on lamp-io platform) or external(db hosted outside of lamp-io platform) DB
    * `system` ***(string)*** DB engine (e.g mysql, sqlite)
    * `root_password` ***(string)*** DB root password
* `environment` ***(dictionary)*** Environment variables that will be added to .env file (e.g foo: bar)
* `apache_permissions_dir` ***(list)*** Select a dir that need to has apache permissions to write on it. NOTE: all default files that required apache write permissions, will be updated automatically
* `commands` ***(dictionary)*** Commands that should be runt before symlink release (Please note that you can not add here migrate commands, it will be runt by default)
* `no_migrations` ***(bool)*** Is need to run migrations, default TRUE
* `retain` ***(int)*** How many old releases should be kept (Default value 10)
    
