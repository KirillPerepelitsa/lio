# Command line interface, [Lamp-io](https://www.lamp.io/) platform

[![Build Status](https://travis-ci.com/lamp-io/lio.svg?branch=master)](https://travis-ci.com/lamp-io/lio)
[![Latest Stable Version](https://poser.pugx.org/lamp-io/lio/version)](https://packagist.org/packages/lamp-io/lio)
[![License](https://poser.pugx.org/lamp-io/lio/license)](https://packagist.org/packages/lamp-io/lio)


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

    Deploy your app to lamp.io

    Deploy command will create [lamp.yo.yaml](#lampioyaml) config file, inside of your project root, deploy process can be controlled by editing this file, you can check doc here [lamp.yo.yaml](#lampioyaml)

    Arguments:

    * `[<dir>]` ***(string)***  Path to a directory of your application, default value current working directory

    Options:
    * `[--laravel]` ***(bool)***  Deploy laravel app
    * `[--symfony]` ***(bool)*** Deploy symfony app

2. #### deploy:list <app_id>

    Get list of available deploys

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app

### Auth

1.  #### auth [-u][--update_token]

    Set auth token.
    
    Get your token at https://www.lamp.io/ on settings page

    Options:

    * `[-u][--update_token]` ***(bool)*** Update existing token

### Apps

1. #### apps:new <organization_id> [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--github_webhook_secret] [--webhook_run_command] [--hostname] [--hostname_certificate_valid] [--public]

    Creates a new app
    
    Api reference https://www.lamp.io/api#/apps/appsCreate

    Arguments:

    * `[<organization_id>]` ***(string)*** The ID of the organization this app belongs to

    Options:

    * `[-d][--description]` ***(string)*** A description 
    * `[--httpd_conf]` ***(string)*** Path to your httpd.conf
    * `[--max_replicas]` ***(int)*** The maximum number of auto-scaled replicas
    * `[-m][--memory]` ***(string)*** The amount of memory available (example: 1Gi)  (default: 128Mi) 
    * `[--min_replicas]` ***(int)*** The minimum number of auto-scaled replicas (default: 1)
    * `[--php_ini]` ***(string)*** Path to your php.ini
    * `[-r][--replicas]` ***(int)*** The number current number replicas available. 0 stops app (default: 1)
    * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret]` ***(string)*** Github web-hook secret token
    * `[--webhook_run_command]` ***(string)*** Github web-hook command
    * `[--hostname]` ***(string)*** The hostname for the app
    * `[--hostname_certificate_valid]` ***(bool)*** Is hostname certificate valid
    * `[--public]` ***(bool)***  Public for read-only

2. #### apps:update <app_id> <organization_id>  [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--vcpu] [--github_webhook_secret] [--webhook_run_command] [--hostname] [--hostname_certificate_valid] [--public]

    Update app
    
    Api reference https://www.lamp.io/api#/apps/appsCreate

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `[<organization_id>]` ***(string)*** The ID of the organization this app belongs to

    Options:

    * `[--httpd_conf]` ***(string)*** Path to your httpd.conf
    * `[--max_replicas]` ***(int)*** The maximum number of auto-scaled replicas
    * `[-m][--memory]` ***(string)*** The amount of memory available (example: 1Gi)  (default: 128Mi) 
    * `[--min_replicas]` ***(int)*** The minimum number of auto-scaled replicas (default: 1)
    * `[--php_ini]` ***(string)*** Path to your php.ini
    * `[-r][--replicas]` ***(int)*** The number current number replicas available. 0 stops app (default: 1)
    * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (maximum: 4, minimum: 0.25)
    * `[--github_webhook_secret]` ***(string)*** Github web-hook secret token
    * `[--webhook_run_command]` ***(string)*** Github web-hook command
    * `[--hostname]` ***(string)*** The hostname for the app
    * `[--hostname_certificate_valid]` ***(bool)*** Is hostname certificate valid
    * `[--public]` ***(bool)***  Public for read-only

3. #### apps:delete <app_id> [--yes][-y]

    Delete an app
    
    Api reference https://www.lamp.io/api#/apps/appsDestroy

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

4.  #### apps:list

    Returns the apps for an organization
    
    Api reference https://www.lamp.io/api#/apps/appsList

5.  #### apps:describe <app_id>

    Return your app
    
    Api reference https://www.lamp.io/api#/apps/appsShow

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app

### Apps sub commands:

1. ### apps:update:status <app_id> [--enable] [--disable]

    Enable/disable app
    
    Api reference https://www.lamp.io/api#/apps/appsUpdate

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app

    Options:

    * `[--enable]` ***(bool)*** Enable your stopped app
    * `[--disable]` ***(bool)*** Disable your running app

### App backups

1.  #### app_backups:new <app_id>

    Back up files in app
    
    Api reference https://www.lamp.io/api#/app_backups/appBackupsCreate

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app

2. #### app_backups:download <app_backup_id> <dir>

   Download an app backup
   
   Api reference https://www.lamp.io/api#/app_backups/appBackupsShow

    Arguments:

    * `<app_backup_id>` ***(string)*** The ID of the app backup
    * `[<dir>]` ***(string)*** Local path for downloaded file. (default: current working directory)

3. #### app_backups:delete <app_backup_id> [--yes][-y]

    Delete an app backup
    
    Api reference https://www.lamp.io/api#/app_backups/appBackupsShow

    Arguments:

    * `<app_backup_id>` ***(string)*** The ID of the app backup

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

4.  #### app_backups:list [-o][--organization_id]

    Return app backups
    
    Api reference https://www.lamp.io/api#/app_backups/appBackupsList

    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
5. #### app_backups:describe <app_backup_id>
    
    Return an app backup
    
    Allow you to get an app backup, api reference https://www.lamp.io/api#/app_backups/appBackupsShow

    Arguments:

    * `<app_backup_id>` ***(string)*** The ID of the app backup

### App runs

1. ### app_runs:new <app_id> <exec>

    Run command on app
    
    Api reference https://www.lamp.io/api#/app_backups/appRunsCreate

    Arguments:
    
    * `<app_id>` ***(string)*** The ID of the app
    * `<exec>` ***(string)*** Command to run

2. ### app_runs:delete <app_run_id>

    Delete app run
    
    Api reference https://www.lamp.io/api#/app_runs/appRunsDelete

    Arguments:
    
    * `<app_run_id>` ***(string)*** ID of app run

3. ### app_runs:list

    Return all app runs for all user's organizations
    
    Api reference https://www.lamp.io/api#/app_runs/appRunsList

4. ### app_runs:describe

    Return app run
    
    Api reference https://www.lamp.io/api#/app_runs/appRunsShow

    Arguments:
    
    * `<app_run_id>` ***(string)*** ID of app run

### Databases

1. ### databases:new  [-d][--description] [-m][--memory] [--organization_id] [--mysql_root_password] [--my_cnf] [--ssd] [--vcpu]

   Create a new database
   
   Api reference https://www.lamp.io/api#/databases/databasesCreate

   Options:

   * `[-d][--description]` ***(string)*** Description of your database
   * `[-m][--memory]` ***(string)*** Amount of virtual memory on your database (default: 512Mi)
   * `[--organization_id]` ***(string)*** Name of your organization
   * `[--mysql_root_password]` ***(string)*** Your root password for mysql
   * `[--my_cnf]` ***(string)*** Path to your database config file
   * `[--ssd]` ***(string)*** Size of ssd storage (default: 1Gi)
   * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (default: 0.25)

2. ### databases:delete <database_id> [--yes][-y]

    Delete a database
    
    Api reference https://www.lamp.io/api#/databases/databasesDelete
    
    Arguments:

    *`<database_id>` ***(string)*** The ID of database

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

3. ### databases:update <database_id> [-d][--description] [-m][--memory] [--organization_id] [--my_cnf] [--mysql_root_password] [--ssd] [--vcpu]

    Update a database.
    
    Api reference https://www.lamp.io/api#/databases/databasesUpdate

    Arguments:

    *`<database_id>)` ***(string)*** The ID of database

    Options:
       
  * `[-d][--description]` ***(string)*** Description of your database
  * `[-m][--memory]` ***(string)*** Amount of virtual memory on your database (default: 512Mi)
  * `[--organization_id]` ***(string)*** Name of your organization
  * `[--mysql_root_password]` ***(bool)*** If you need to update root password, set it as true
  * `[--my_cnf]` ***(string)*** Path to your database config file
  * `[--ssd]` ***(string)*** Size of ssd storage (default: 1Gi)
  * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (default: 0.25)

4. ### databases:list [--organization_id]

    Returns all databases
    
    Api reference https://www.lamp.io/api#/databases/databasesList

    Options:

    * `<organization_id>` ***(string)*** Filter output by organization id value

5. ### databases:describe <database_id>

    Returns a database
    
    Api reference https://www.lamp.io/api#/databases/databasesShow

    Arguments:

    * `<database_id>` ***(string)*** The ID of database

### Database backups

1. ### db_backups:new <database_id>

    Back up database
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsCreate
    
    Arguments:

    *`<database_id>` ***(string)*** The id of database

2. ### db_backups:delete <db_backup_id> [--yes][-y]

    Delete a db backup
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsDelete

    Arguments:

    *`<db_backup_id>` ***(string)*** The ID of the db backup

    Options:

    * `[--yes][-y]` ***(string)*** Skip confirm delete question

3. ###db_backups:list [--organization_id][-o]

    Return db backups
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsList

    Options:

    * `[--organization_id][-o]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

4. ### db_backups:describe <db_backup_id>

    Return a database backup
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsShow

    Arguments:

    * `<db_backup_id>` ***(string)*** The ID of the db backup

### Database restore jobs

1. ### db_restores:new

    Create database restore job (restore backup to a database)
    
    Api reference https://www.lamp.io/api#/db_restores/dbRestoresCreate

    Arguments:

    * `<database_id>` ***(string)*** The id of database
    * `<db_backup_id>` ***(string)*** The ID of the db backup

2. ### db_restores:delete <db_restore_id>

    Delete a db restore job
    
    Api reference https://www.lamp.io/api#/db_restores/dbRestoresDelete

    Arguments:

    * `<db_restore_id>` ***(string)*** The ID of the db restore

3. ### db_restores:list [--organization_id][-o]

    Return db restore jobs
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsList

    Options:

    * `[--organization_id][-o]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

4. ### db_restores:describe <db_restore_id>

    Return a db restore job
    
    Api reference https://www.lamp.io/api#/db_backups/dbRestoresShow

    Arguments:

    * `<db_restore_id>` ***(string)*** The ID of the db restore

### Files

1. #### files:list <app_id> <file_id> [-l][--limit] [--human-readable] [-r][--recursive]

    Return files from the root of an app
    
    Api reference https://www.lamp.io/api#/files/filesList

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** The ID of the file. The ID is also the file path relative to its app root (default: app root)

    Options:

    * `[-l][--limit]` ***(int)*** The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out (default: 1000)
    * `[--human-readable]` ***(bool)*** Format size values from raw bytes to human readable format
    * `[-r][--recursive]` ***(bool)*** Command is performed on all files or objects under the specified path

2. #### files:upload <file> <app_id> <file_id>

    Creates new file
    
    Api reference https://www.lamp.io/api#/files/filesCreate

    Arguments:

    * `<file>` ***(string)*** Local path of file to upload
    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to save

3. #### files:download <app_id> <file_id> <dir>

    Download files as zip.
    
    Api reference https://www.lamp.io/api#/files/filesShow

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** The ID of the file. The ID is also the file path relative to its app root
    * `<dir>` ***(string)*** Local path for downloaded file (default: current working dir)

4. #### files:update <app_id> [<file_id>] [<local_file>] [-r][--recur] [--command] [--source]

   Update file at file_id(file path including file name, relative to app root)
   
   Api reference https://www.lamp.io/api#/files/filesUpdateID
   
   Arguments:

   * `<app_id(string)>` ***(string)***  The ID of the app
   * `[<file_id>]` ***(string)*** File ID of file to update. If omitted, update app root directory
   * `[<local_file>]` ***(string)***  Path to a local file; this is uploaded to remote_path

5. #### files:delete <app_id> <file_id> [--yes][-y]

    Remove file/directory from your app
    
    Api reference https://www.lamp.io/api#/files/filesDestroy
    
    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to delete

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

### Files sub commands:

1. #### files:update:unarchive <app_id> <file_id>

    Extract archive file
    
    Api reference https://www.lamp.io/api#/files/filesUpdateID

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to unarchive

2. ### files:update:fetch <app_id> <file_id> <source>

    Fetch file from URL
    
    Api reference https://www.lamp.io/api#/files/filesUpdateID

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to fetch
    * `<source>` ***(string)*** URL to fetch

3. ### files:update:move <app_id> <file_id> <move_path>

    Move file to another directory

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to move
    * `<move_path>` ***(string)*** The target File ID to move to. NOTE: The target directory must exist

### Logs

1. ### logs:list [--organization_id][-o] [--pod_name][-p] [--start_time] [--end_time]

    Return logs
    
    Api reference https://www.lamp.io/api#/logs/logsList

    Options

    * `[--organization_id][-o]` ***(string)*** One organization_id. If omitted defaults to user's default organization
    * `[--pod_name][-p]` ***(string)*** One pod_name. Uses wildcard prefix match
    * `[--start_time]` ***(string)*** Start time conforming to RFC3339 (default: 10 minutes in the past)
    * `[--end_time]` ***(string)*** End time conforming to RFC3339. (default: current date)

### Organizations

1. ### organizations:update <organization_id> [--name] [--promo_code] [--payment][-p]

    Update an organization
    
    Api reference https://www.lamp.io/api#/organizations/organizationsUpdate

    Arguments:

    * `<organization_id>` ***(string)*** The ID of the organization

    Options:

    * `[--name]` ***(string)*** New organization name
    * `[--promo_code]` ***(string)***  Apply promo code
    * `[--payment][-p]` ***(string)*** Stripe source id

2. ### organizations:list

    Returns this user's organizations
    
    Api reference https://www.lamp.io/api#/organizations/organizationsList

###Organization users

1. ### organization_users:update <organization_user_id> [--admin]

    Update an organization/user relationship (Allow to set/remove selected user role as an organization admin)
    
    Api reference https://www.lamp.io/api#/organization_users/organizationUsersUpdate

    Arguments:

    * `<organization_user_id>` ***(string)*** The ID of the organization_use

    Options:

    * `[--admin]` ***(bool)*** Set selected user as admin of organization (if you need to remove admin role from selected user, just omit this option)

2. ### organizations_users:list [--organization_id]

    Returns organization/user relationships
    
    Api reference https://www.lamp.io/api#/organization_users/organizationUsersList

    Options:

    * `[--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

3. ### organizations_users:describe <organization_user_id>

    Returns a organization/user relationship
    
    Api reference https://www.lamp.io/api#/organization_users/organizationUsersShow

    Arguments:

    * `<organization_user_id>` ***(string)*** The ID of the organization_use

### Tokens

1. ### tokens:new [--description][-d] [--enable]

    Creates a new token
    
    Api reference https://www.lamp.io/api#/tokens/tokensCreate

    Options:

    * `[--description][-d]` ***(string)*** Token description
    * `[--enable]` ***(bool)*** Enable new token

2. ### tokens:delete <token_id> [-yes][-y]

     Delete a token
     
     Api reference https://www.lamp.io/api#/tokens/tokensDelete

     Arguments:

     * `<token_id>` ***(string)*** The ID of the token

     Options:

     * `[-yes][-y]` ***(bool)*** Skip confirm delete question

3. ### tokens:update <token_id> [--enable] [--disable]

    Update a token
    
    Api reference https://www.lamp.io/api#/tokens/tokensList

    Arguments:

     * `<token_id>` ***(string)*** The ID of the token

     Options:

     * `[--enable]` ***(bool)*** Enable token
     * `[--disable]` ***(bool)*** Disable token

4. ### tokens:list

    Returns all tokens for this user
    
    Api reference https://www.lamp.io/api#/tokens/tokensList

5. ### tokens:describe <token_id>

    Returns a token

    Arguments:

     * `<token_id>` ***(string)*** The ID of the token

### Users

1. #### users:list [--organization_id][-o] [--email][-e]

    Returns users
    
    Api reference https://www.lamp.io/api#/users/usersList
    
    Options:

    * `[--organization_id][-o]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    * `[--email][-e]` ***(string)*** Email address to filter for

### Phar updates

1. `self-update` Update your phar build to the latest release (will work only if you use phar build)


Composer scripts
------------

1. `composer build` Create phar build

# Lamp.io.yaml

* `release` ***(int)*** Id of your release (this value will be set automatically)
* `type` ***(string)*** Type of your application (e.g laravel, symfony)
* `app` ***(dictionary)*** Settings related to your lamp-io app
    * `attributes` ***(dictionary)*** Lamp-io app attributes, it use same values has [apps:new](#appsnew-organization_id--d--description---httpd_conf---max_replicas--m--memory---min_replicas---php_ini--r--replicas---vcpu---github_webhook_secret---webhook_run_command---hostname---hostname_certificate_valid---public)
     command options
    * `id` ***(string)*** Lamp-io app id
    * `url` ***(string)*** Web app url (this value will be set automatically)
* `database` ***(dictionary)*** Settings related to your database
    * `id` ***(string)*** Lamp-io database id
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

# Examples

* Example of lamp.io.yaml config for deploy with sqlite db (other values will be added during deploy process)
```yaml
database:
    type: internal
    system: sqlite
```

* Example of lamp.io.yaml config for deploy with external mysql db (other values will be added during deploy process)
```yaml
database:
    type: external
    system: mysql
```


## License

The Lamp-io/lio command line interface is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
