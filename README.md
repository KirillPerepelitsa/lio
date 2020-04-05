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
composer require lamp-io/lio
```

##### Download as a PHAR
[lio.phar](https://github.com/lamp-io/lio/releases/latest/download/lio.phar)

Usage
------------
```sh
lio command [options] [arguments]
```

Authentication
------------

Tokens can be generated at [https://www.lamp.io/tokens](https://www.lamp.io/tokens)

How lio can handle tokens

1.  Store token in a environment variable

    ```sh
    export LAMP_IO_TOKEN={TOKEN_VALUE}
    ```

2.  Store token in a file

    Call auth command, it will ask you to prompt token
    ```sh
    lio auth
    ```
    Token will be stored in $HOME/.config/lamp.io/token

Commands
------------
### Global options

* `[-j][--json]` ***(bool)*** Output as a raw json
* `[-h][--help]` ***(bool)*** Display this help message
* `[-q][--quiet]` ***(bool)*** Do not output any message
* `[-V][--version]` ***(bool)*** Display this application version
* `[--ansi]` ***(bool)*** Force ANSI output
* `[--no-ansi]` ***(bool)*** Disable ANSI output
* `[-n][--no-interaction]` ***(bool)*** Do not ask any interactive question
* `[-v|vv|vvv][--verbose]` ***(bool)*** Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

### Auth

1. #### auth [-u][--update_token]

    Set auth token.
    
    Get your token at https://www.lamp.io/ on settings page

    Options:

    * `[-u][--update_token]` ***(bool)*** Update existing token
    * `[-t][--token]` ***(string)*** Set/Update auth token, in noninteractive mode

### Apps

1. #### apps:new <organization_id> [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--github_webhook_secret] [--webhook_run_command] [--hostname] [--hostname_certificate_valid] [--public] [--delete_protection]

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
    * `[--delete_protection]` ***(bool)*** When enabled the app can not be deleted

2. #### apps:update <app_id> <organization_id>  [-d][--description] [--httpd_conf] [--max_replicas] [-m][--memory] [--min_replicas] [--php_ini] [-r][--replicas] [--vcpu] [--vcpu] [--github_webhook_secret] [--webhook_run_command] [--hostname] [--hostname_certificate_valid] [--public] [--delete_protection]

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
    * `[--delete_protection]` ***(bool)*** When enabled the app can not be deleted

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
    
    Options:
    
    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

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
    
### Autodeploys

1.  #### autodeploys:new <github_repository> [--organization_id][-o] [--create_app_on_pr][-c] [--delete_app_on_branch_delete][-d]

    Create an autodeploy for an organization
    
    Api reference https://www.lamp.io/api#/autodeploys/autoDeploysCreate
    
    Arguments:

    * `<github_repository>` ***(string)*** The repository this autodeploy uses
    
    Options:
    
    * `[-o][--organization_id]` ***(string)*** One organization_id. If omitted defaults to user's default organization
    * `[-c][--create_app_on_pr]` ***(bool)*** Create lamp.io app when a PR is created
    * `[-d][--delete_app_on_branch_delete]` ***(bool)*** Delete lamp.io app when branch is deleted

2.  #### autodeploys:update <autodeploy_id> [--github_repository][-g] [--organization_id][-o] [--create_app_on_pr][-c]

    Update an autodeploy
    
    Api reference https://www.lamp.io/api#/autodeploys/autoDeploysUpdate
    
    Arguments:

    * `<autodeploy_id>` ***(string)*** The ID of the autodeploy
    
    Options:
    
    * `[-g][--github_repository]` ***(string)*** The repository this autodeploy uses
    * `[-c][--create_app_on_pr]` ***(bool)*** Create lamp.io app when a PR is created
    * `[-d][--delete_app_on_branch_delete]` ***(bool)*** Delete lamp.io app when branch is deleted
    
3.  #### autodeploys:delete <autodeploy_id>

    Delete an autodeploy
    
    Api reference https://www.lamp.io/api#/autodeploys/autoDeploysDelete
    
    Arguments:
    
    * `<autodeploy_id>` ***(string)*** The ID of the autodeploy

4.  #### autodeploys:list [--organization_id][-o]

    Return autodeploys
    
    Api reference https://www.lamp.io/api#/autodeploys/autoDeploysList
    
    Options:
        
    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

5.  #### autodeploys:describe

    Returns an autodeploy
    
    Api reference https://www.lamp.io/api#/autodeploys/autoDeploysShow
    
    Arguments:

    * `<autodeploy_id>` ***(string)*** The ID of the autodeploy
    
### AutodeploysBranch <app_id> <autodeploy_id> <branch> [--organization_id][-o]

1. ### autodeploysBranch:new

    Create an autodeployBranch for an organization
    
    Api reference https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchCreate
    
    Arguments:

    * `<app_id>` ***(string)*** The ID of the app this branch deploys to
    * `<autodeploy_id>` ***(string)*** The ID of the autodeploy
    * `<branch>` ***(string)*** Branch name
    
    Options:
        
    * `[-o][--organization_id]` ***(string)*** One organization_id. If omitted defaults to user's default organization
    
2. ### autodeploysBranch:update <autodeploy_branch_id> [--app_id][-a] [--branch][-b]

    Update an autodeploys_branch
    
    Api reference https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchUpdate
    
    Arguments:
    
    * `<autodeploy_branch_id>` ***(string)*** The ID of the autodeployBranch
    
    Options:
            
    * `[-a][--app_id]` ***(string)*** The ID of the app to deploy against
    * `[-b][--branch]` ***(string)*** Branch name
    
3. ### autodeploysBranch:delete <autodeploy_branch_id>

    Delete an autodeploys_branch
    
    Api reference https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchDelete
    
    Arguments:
    
    * `<autodeploy_branch_id>` ***(string)*** The ID of the autodeployBranch

4. ### autodeploysBranch:list [--organization_id][-o]

    Return autodeploys_branch
    
    Api reference https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchList
    
    Options:
            
    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

5. ### autodeploysBranch:describe <autodeploy_branch_id>

    Return an autodeploy_branch
    
    Api reference https://www.lamp.io/api#/autodeploys_branch/autoDeploysBranchShow
    
    Arguments:
    
    * `<autodeploy_branch_id>` ***(string)*** The ID of the autodeployBranch

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
    
    Get an app backup 
    
    Api reference https://www.lamp.io/api#/app_backups/appBackupsShow

    Arguments:

    * `<app_backup_id>` ***(string)*** The ID of the app backup

### App restores

1.  #### app_restores:new <app_id> <app_backup_id>

    Restore files to an app
    
    Api reference https://www.lamp.io/api#/app_restores/appRestoresCreate

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<app_backup_id>` ***(string)*** The ID of the app backup

2. #### app_restores:delete <app_restore_id> [--yes][-y]

    Delete an app backup
    
    Api reference https://www.lamp.io/api#/app_restores/appRestoresDelete

    Arguments:

    * `<app_restore_id>` ***(string)*** The ID of the app restore

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

3.  #### app_restores:list [-o][--organization_id]

    Return app restores
    
    Api reference https://www.lamp.io/api#/app_restores/appRestoresList

    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    
4. #### app_restores:describe <app_restore_id>
    
    Return an app restore
    
    Allow you to get an app backup, api reference https://www.lamp.io/api#/app_backups/appRestoresShow

    Arguments:

    * `<app_restore_id>` ***(string)*** The ID of the app restore

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

3. ### app_runs:list [--page_number] [--page_size] [--organization_id][-o] [--output_lines]

    Return all app runs for all user's organizations
    
    Api reference https://www.lamp.io/api#/app_runs/appRunsList
    
   Options:

   * `[--page_number]` ***(int)*** Pagination page, default value 1
   * `[--page_size]` ***(int)*** Count per paginated page, default value 100
   * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
   * `[--output_lines]` ***(int)*** Maximum number of lines returned. 1 is Unlimited. Default 5

4. ### app_runs:describe

    Return app run
    
    Api reference https://www.lamp.io/api#/app_runs/appRunsShow

    Arguments:
    
    * `<app_run_id>` ***(string)*** ID of app run

### Databases

1. ### databases:new  [-d][--description] [-m][--memory] [-o][--organization_id] [--mysql_root_password] [--my_cnf] [--ssd] [--vcpu] [--delete_protection]

   Create a new database
   
   Api reference https://www.lamp.io/api#/databases/databasesCreate

   Options:

   * `[-d][--description]` ***(string)*** Description of your database
   * `[-m][--memory]` ***(string)*** Amount of virtual memory on your database (default: 512Mi)
   * `[-o][--organization_id]` ***(string)*** Name of your organization
   * `[--mysql_root_password]` ***(string)*** Your root password for mysql
   * `[--my_cnf]` ***(string)*** Path to your database config file
   * `[--ssd]` ***(string)*** Size of ssd storage (default: 1Gi)
   * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (default: 0.25)
   * `[--delete_protection]` ***(bool)*** When enabled the database can not be deleted

2. ### databases:delete <database_id> [--yes][-y]

    Delete a database
    
    Api reference https://www.lamp.io/api#/databases/databasesDelete
    
    Arguments:

    *`<database_id>` ***(string)*** The ID of database

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question

3. ### databases:update <database_id> [-d][--description] [-m][--memory] [-o][--organization_id] [--my_cnf] [--mysql_root_password] [--ssd] [--vcpu] [--delete_protection]

    Update a database.
    
    Api reference https://www.lamp.io/api#/databases/databasesUpdate

    Arguments:

    *`<database_id>)` ***(string)*** The ID of database

    Options:
       
  * `[-d][--description]` ***(string)*** Description of your database
  * `[-m][--memory]` ***(string)*** Amount of virtual memory on your database (default: 512Mi)
  * `[-o][--organization_id]` ***(string)*** Name of your organization
  * `[--mysql_root_password]` ***(bool)*** If you need to update root password, set it as true
  * `[--my_cnf]` ***(string)*** Path to your database config file
  * `[--ssd]` ***(string)*** Size of ssd storage (default: 1Gi)
  * `[--vcpu]` ***(float)*** The number of virtual cpu cores available (default: 0.25)
  * `[--delete_protection]` ***(bool)*** When enabled the database can not be deleted

4. ### databases:list [-o][--organization_id]

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

3. ###db_backups:list [-o][--organization_id]

    Return db backups
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsList

    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

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

3. ### db_restores:list [-o][--organization_id]

    Return db restore jobs
    
    Api reference https://www.lamp.io/api#/db_backups/dbBackupsList

    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

4. ### db_restores:describe <db_restore_id>

    Return a db restore job
    
    Api reference https://www.lamp.io/api#/db_backups/dbRestoresShow

    Arguments:

    * `<db_restore_id>` ***(string)*** The ID of the db restore

### Files

1. ### files:new  <app_id> <file_id> [<contents>] [--apache_writable] [--source]

    Create a file on your app
        
    Api reference https://www.lamp.io/api#/files/filesCreate
    
    Arguments:
        
    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of a file to create'
    * `[<contents>]` ***(string)*** File content
    
    Options:
    
     * `[--source]` ***(string)*** A URL to that will be retrieved for fetch content
     * `[--apache_writable]` ***(bool)*** Allow apache to write to the file ID

2. #### files:list <app_id> <file_id> [-l][--limit] [--human-readable] [-r][--recursive]

    Return files from the root of an app
    
    Api reference https://www.lamp.io/api#/files/filesList

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** The ID of the file. The ID is also the file path relative to its app root (default: app root)

    Options:

    * `[-l][--limit]` ***(int)*** The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out (default: 1000)
    * `[--human-readable]` ***(bool)*** Format size values from raw bytes to human readable format
    * `[-r][--recursive]` ***(bool)*** Command is performed on all files or objects under the specified path

3. #### files:upload <file> <app_id> <file_id>

    Creates new file
    
    Api reference https://www.lamp.io/api#/files/filesCreate

    Arguments:

    * `<file>` ***(string)*** Local path of file to upload
    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to save

4. #### files:download <app_id> <file_id> <dir>

    Download files as zip.
    
    Api reference https://www.lamp.io/api#/files/filesShow

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** The ID of the file. The ID is also the file path relative to its app root
    * `<dir>` ***(string)*** Local path for downloaded file (default: current working dir)

5. #### files:update <app_id> [<file_id>] [<local_file>] [-r][--recur] [--command] 

   Update file at file_id(file path including file name, relative to app root)
   
   Api reference https://www.lamp.io/api#/files/filesUpdateID
   
   Arguments:

   * `<app_id>` ***(string)***  The ID of the app
   * `<file_id>` ***(string)*** File ID of file to update. If omitted, update app root directory
   * `[<local_file>]` ***(string)***  Path to a local file; this is uploaded to remote_path
   
   Options:
   
   * `[-r][--recursive]` ***(bool)*** Recur into directories (works only with [--apache_writable] option)
   * `[--apache_writable]` ***(bool)*** Allow apache to write to the file ID

6. #### files:delete <app_id> <file_id> [--yes][-y]

    Remove file/directory from your app
    
    Api reference https://www.lamp.io/api#/files/filesDestroy
    
    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to delete

    Options:

    * `[--yes][-y]` ***(bool)*** Skip confirm delete question
    
### Files sub commands:

1. ### files:new:dir <app_id> <file_id> [--apache_writable]

    Create a directory on your app
    
    Api reference https://www.lamp.io/api#/files/filesCreate
    
    Arguments:
    
    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of directory to create
    
    Options:
    
    * `[--apache_writable]` ***(bool)*** Allow apache to write to the file ID
    
2. ### files:new:symlink <app_id> <file_id> <target> [--apache_writable]

    Create a symlink on your app
    
    Api reference https://www.lamp.io/api#/files/filesCreate
    
    Arguments:
    
    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of a symlink to create
    * `<target>` ***(string)*** Symlink target file ID
    
    Options:
    
    * `[--apache_writable]` ***(bool)*** Allow apache to write to the file ID
    
3. ### files:update:symlink <app_id> <file_id> <target> [--apache_writable]
   
   Update a symlink on your app
   
   Api reference https://www.lamp.io/api#/files/filesUpdate
   
   Arguments:
   
   * `<app_id>` ***(string)*** The ID of the app
   * `<file_id>` ***(string)*** File ID of a symlink to update
   * `<target>` ***(string)*** Symlink target file ID
   
   Options:
   
   * `[--apache_writable]` ***(bool)*** Allow apache to write to the file ID

4. #### files:update:unarchive <app_id> <file_id>

    Extract archive file
    
    Api reference https://www.lamp.io/api#/files/filesUpdateID

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to unarchive

5. ### files:update:fetch <app_id> <file_id> <source>

    Fetch file from URL
    
    Api reference https://www.lamp.io/api#/files/filesUpdateID

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to fetch
    * `<source>` ***(string)*** URL to fetch

6. ### files:update:move <app_id> <file_id> <move_path>

    Move file to another directory

    Arguments:

    * `<app_id>` ***(string)*** The ID of the app
    * `<file_id>` ***(string)*** File ID of file to move
    * `<move_path>` ***(string)*** The target File ID to move to. NOTE: The target directory must exist
    
### Keys
    
1. ### keys:new [-o][organization_id] [-d][--description]

    Creates a new key
    
    Api reference https://www.lamp.io/api#/keys/keysCreate
    
    Options:
    
    * `[-o][--organization_id]` ***(string)*** One organization_id. If omitted defaults to user's default organization
    * `[-d][--description]` ***(string)*** An immutable description for this key

2. ### keys:update <key_id> [-d][--description]

    Update a key
    
    Api reference https://www.lamp.io/api#/keys/keysUpdate
    
    Arguments:
    
    * `<key_id>` ***(string)*** The ID of the key
    
    Options:
    
    * `[-d][--description]` ***(string)*** An immutable description for this key
    
3. ### keys:delete <key_id>

    Delete a key
    
    Api reference https://www.lamp.io/api#/keys/keysDelete
    
    Arguments:
        
    * `<key_id>` ***(string)*** The ID of the key
    
4. ### keys:list [-o][--organization_id]

    Returns keys for organization
    
    Api reference https://www.lamp.io/api#/keys/keysList
    
    Options
    
    * `[-o][--organization_id]` ***(string)*** One organization_id. If omitted defaults to user's default organization
    
5. ### keys:describe <key_id>

    Returns a key
    
    Api reference https://www.lamp.io/api#/keys/keysShow
    
    Arguments:
            
    * `<key_id>` ***(string)*** The ID of the key

### Logs

1. ### logs:list [-o][--organization_id][--pod_name][-p] [--start_time] [--end_time]

    Return logs
    
    Api reference https://www.lamp.io/api#/logs/logsList

    Options

    * `[-o][--organization_id]` ***(string)*** One organization_id. If omitted defaults to user's default organization
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

2. ### organizations_users:list [-o][--organization_id]

    Returns organization/user relationships
    
    Api reference https://www.lamp.io/api#/organization_users/organizationUsersList

    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization

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

1. #### users:list [-o][--organization_id][--email][-e]

    Returns users
    
    Api reference https://www.lamp.io/api#/users/usersList
    
    Options:

    * `[-o][--organization_id]` ***(string)*** Comma-separated list of requested organization_ids. If omitted defaults to user's default organization
    * `[--email][-e]` ***(string)*** Email address to filter for

### Phar updates

1. `self-update` Update your phar build to the latest release (will work only if you use phar build)

# CI/CD systems integration examples

## Laravel app deploy examples

## TravisCI

Requires 2 environment variables

* APP_ID **required** Your lamp.io App Id

* LAMP_IO_TOKEN **required** The lamp.io access token

.travis.yaml
```yaml
language: php
sudo: false
php:
  - "7.3"
jobs:
  include:
    - stage: deploy
      before_script:
        - composer install
        - chmod +x build.sh
      script: ./deploy.sh
```
deploy.sh
```bash
#!/bin/sh -l
set -e
composer install --optimize-autoloader
composer global require lamp-io/lio dev-master --update-with-dependencies
alias lio=$HOME/.composer/vendor/bin/lio

cp .env .env.live
app=$APP_ID
release="$(date +%Y%m%d%H%m%s)";

# zip up the app for uploading
zip -r /tmp/artifact.zip . \
  -x "*\.env*" \
  -x "*\.git*" \
  --quiet

# create the new release directory in the lamp.io app
lio files:new:dir $app releases/$release

# upload the zip into it
lio files:upload /tmp/artifact.zip $app releases/$release/artifact.zip

# unzip it remotely
lio files:update:unarchive $app releases/$release/artifact.zip

# clean up
lio files:delete $app releases/$release/artifact.zip -y

# check if the (shared) storage directory already exists
if ! lio files:list $app storage -q; then
  # if not, use the one from this release to get started
  lio files:update:move $app releases/$release/storage /storage
fi

# delete this releases storage directory and symlink it to the shared one
lio files:delete $app releases/$release/storage -y -q
lio files:new:symlink $app releases/$release/storage ../../storage

# make sure the app can write to things
lio files:update $app storage --apache_writable=true --recursive
lio files:update $app releases/$release/bootstrap/cache --apache_writable=true --recursive

# upload the local .env.live file to be the remote .env file
lio files:upload .env.live $app releases/$release/.env

# run artisan remotely
# lio app_runs:new $app "cd releases/$release && php artisan migrate"

# get the current state of the docroot (public)
public="$(lio files:list $app public -j)"

# if there's a directory already there
if [ "$(echo $public | jq -r '.data.attributes.is_dir')" = 'true' ]; then
  # back it up as a pre-release
  lio files:new:dir $app releases/$release-pre
  lio files:update:move $app public releases/$release-pre/public
  # and then symlink public to this new release
  lio files:new:symlink $app public releases/$release/public

# else if there's a symlink already there
elif [ "$(echo $public | jq -r '.data.attributes.is_symlink')" = 'true' ]; then
  # update it to point to this new release
  lio files:update:symlink $app public releases/$release/public
fi
```

## Github actions

Basic workflow example:

```yaml
on: [push]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v1
      - name: Lio deploy
        uses: lamp-io/action-lio-deploy@master
        with:
          auth_token: ${{ secrets.lamp_io_token }}
          app_id: $({secrets.lamp_io_app_id})

```

You can get more details on [lamp-io/lio_deploy](https://github.com/lamp-io/action-lio-deploy) action, repository page

Composer scripts
------------

1. `composer build` Create phar build

## License

The Lamp-io/lio command line interface is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
