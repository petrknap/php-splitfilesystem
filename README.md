# Split Filesystem for PHP

* [About resolved issue](#about-resolved-issue)
    * [Advantages](#advantages)
    * [Disadvantages](#disadvantages)
* [Usage of php-splitfilesystem](#usage-of-php-splitfilesystem)
    * [Standard usage](#standard-usage)
* [How to install](#how-to-install)



## About resolved issue

> I need to use something where around 60,000 files with average size of 30kb are stored in a single directory (this is a requirement so can't simply break into sub-directories with smaller number of files).
>
> The files will be accessed randomly, but once created there will be no writes to the same filesystem. I'm currently using Ext3 but finding it very slow. Any suggestions?
>
> -- [Filesystem large number of files in a single directory - bugmenot77, voretaq7]

This file storage solves this issue simply - it **creates virtual layer between file system and application**. Every path is converted into path which is composed from many directories which contains only small amount of sub-directories.

If you wish to store 1 000 000 files in single directory, this file storage converts paths and stores them in tree-structure. Every directory contains only small amount of directories and files (depends on configuration).

### Advantages

 * Can store a huge amount of files in single directory
 * Naturally protects files outside the storage
 * Every user can has separated and isolated file storage
 * Fully compatible and based on [League\Flysystem]

### Disadvantages

 * Real file structure is not user-friendly



## Usage of php-splitfilesystem

### Standard usage

```php
<?php

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use PetrKnap\Php\SplitFilesystem\SplitFilesystem;

$optionalConfig = new Config([
    SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_DIRECTORIES => 3, // up to 1024 sub-nodes
    SplitFilesystem::CONFIG_HASH_PART_LENGTH_FOR_FILES => 2, // up to 256 sub-nodes
    SplitFilesystem::CONFIG_HASH_PARTS_FOR_DIRECTORIES => 1, // 1-level sub-tree
    SplitFilesystem::CONFIG_HASH_PARTS_FOR_FILES => 3, // 3-level sub-tree
]);

$fileSystem = new SplitFilesystem(new Local(__DIR__ . '/temp'), $optionalConfig);

$fileSystem->write('file.txt', null);
$fileSystem->update('file.txt', 'Hello World!');

printf('%s', $fileSystem->read('file.txt'));

foreach ($fileSystem->listContents() as $metadata) {
    $fileSystem->delete($metadata['path']);
}
```


## How to install

Run `composer require petrknap/php-splitfilesystem` or merge this JSON code with your project `composer.json` file manually and run `composer install`. Instead of `dev-master` you can use [one of released versions].

```json
{
    "require": {
        "petrknap/php-splitfilesystem": "dev-master"
    }
}
```

Or manually clone this repository via `git clone https://github.com/petrknap/php-splitfilesystem.git` or download [this repository as ZIP] and extract files into your project.



[one of released versions]:https://github.com/petrknap/php-splitfilesystem/releases
[this repository as ZIP]:https://github.com/petrknap/php-splitfilesystem/archive/master.zip




[Filesystem large number of files in a single directory - bugmenot77, voretaq7]:http://serverfault.com/q/43133
[League\Flysystem]:https://github.com/thephpleague/flysystem
