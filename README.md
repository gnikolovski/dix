# dix (Database Import eXport)

dix is a console application for exporting and importing MySQL databases. It
is written in PHP, and it is based on Symfony console component. For database
operations it uses mysql command line tool, so from all this it is quite clear
that dix has the solid foundation. While designing this application, my goal was
to leverage existing well known software.

Using dix is easy, and you should be ready to use it after reading this whole
document. dix will help you to easily backup database and restore it later.
It is designed for web developers who work with PHP frameworks or content
managment systems. Before doing something that might damage your database you
can easily create a backup, and then start to experiment all you want without
worrying about your data. Of course, you can find another creative uses for this
application.

## Installation

To build dix phar file I used Box2. In the root of the project you can find
box.json file which contains app definition. You can build it yourself by
downloading box.phar file and executing:

```
php box.phar build
```

If phar.readonly option in your php.ini is set to true then use the following
command to build dix:

```
php -d phar.readonly=0 box.phar build
```

If you don't want to build the application yourself, you can download pre-built
file form the following address:

```
https://gorannikolovski.com/apps/dix.phar
```

If you want to access dix from anywhere on your system place it in your PATH
and set the appropriate permissions. Example:

```
mv dix.phar /usr/local/bin/dix
chmod +x /usr/local/bin/dix
```

## Commands

dix has four commands. To see the list of commands and options use the following
command:

```
dix
```

### 1. Config

Every application needs to be configured, and the same is with dix. Application
config is pretty simple, it consist of directory path where you will export
databases, and the list of eventual database credentials. You will probably use
this app on dev server, where you might have one password for all databases, so
configuration will only have a directory path and one set of database
credentials.

To see your current configuration use config command without any options:

```
dix config
```

Command alias is:

```
dix cf
```

To set the export directory where dix will save database files, use the
following command:

```
dix config --dir=path/to/directory
```

If you have a different credentials for every database on your server then you
must set credentials for each. To set a specific database credentials use:

```
dix config --dbname=DATABASE_NAME --user=DATABASE_USERNAME --pass=DATABASE_PASSWORD
```

To set the global database credentials use asterisk as wildcard:

```
dix config --dbname=* --user=DATABASE_USERNAME --pass=DATABASE_PASSWORD
```

To delete database credentials specify database name without username and
password:

```
dix config --dbname=DATABASE_NAME --user --pass
```

or shorter:

```
dix config --dbname=DATABASE_NAME
```

YOU DON'T HAVE TO SET DATABASE CREDENTIALS HERE - YOU CAN SPECIFY USERNAME AND
PASSWORD ON EACH EXPORT IF YOU WANT TO AVOID SAVING SENSITIVE DATA. Please keep
in mind that if you decide to save passwords, they will be stored in a plain
text Yml file.

### 2. Log

Every time you export a database, a log entry will be created. It consist of
export hash ID, database name, path to the sql file, date and the log message.
Log command is read only - you can't change log in any way from it.

To show full log in descending date order execute the following command:

```
dix log
```

Command alias is:

```
dix lg
```

Sort log entries in ascending date order:

```
dix log --sort=asc
```

Because you probably don't want to see everything in your log, there are some
filtering options:

Limit the number of showed log entries:

```
dix log --lim=NUMBER_OF_ENTRIES
```

Filter by database name:

```
dix log --dbname=DATABASE_NAME
```

Filter by date:

```
dix log --date=DATE
```

Date should be in one of the following formats: Y.m.d, Y-m-d or Ymd

Filtery by message:

```
dix log --msg="MESSAGE TEXT"
```

Multiword search terms should be enclosed in the quotes.

### 3. Export

To export a database you must set the directory path by using config command.
You can specify database credentials in this command or you could set them with
config command if you don't want to type it every time you are exporting a
database. To export a database if the credentials are stored in config use:

```
dix export --dbname=DATABASE_NAME
```

Command alias is:

```
dix ex
```

If you want to specify credentials in this command use:

```
dix export --dbname=DATABASE_NAME --user=DATABASE_USERNAME --pass=DATABASE_PASSWORD
```

Use the latter command if you don't want to store passwords in the config file.
Every export can have a message associated with it, which can later be read from
the log. To add message use:

```
dix export --dbname=DATABASE_NAME --msg="EXPORT MESSAGE"
```

Multiword export message should be enclosed in the quotes. Every export has
unique ID which can later be used when importing database. You can find this ID
in the log.

### 4. Import

You can import database by specifying dbname or export ID. If you specify the
database name, then the last exported database with that name will be imported.
On the other hand, if you specify the ID, then database with that ID will be
imported.

To import last exported database with specific name use:

```
dix import --dbname=DATABASE_NAME
```

Command alias is:

```
dix im
```

To import database with specific ID use:

```
dix import --id=EXPORT_ID
```

You don't have to type in the whole ID - it is enough to use only first 5
characters. You can find ID if you call log command. Please note that dbname and
id are mutually exclusive commands.

You can specify username and password in the import command:

```
dix import --dbname=DATABASE_NAME --user=DATABASE_USERNAME --pass=DATABASE_PASSWORD
```

If existing database in not empty, then import command will fail. To force
import use:

```
dix import --dbname=DATABASE_NAME --force=true
```

Even with this option, you will be asked for a confirmation, because you can
potentially lose important data if you overwrite existing database.

#### Notice

This application is tested only on Linux.
