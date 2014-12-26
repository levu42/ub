# Abstract

`ub` is a tool to manage bibtex files and make the import from different sources
like Google Books, university libaries etc. easier.

## Features

* multiple BibTeX files possible
* basic git integration for commit & push BibTeX files after update
* import entries from Google Books
* import entries from the library database of the library of the University Frankfurt
* find entries via ISBN and other identifiers
* edit entries regardless of in which BibTeX file they are saved
* list all books in all or one BibTeX file(s)
* copy entries between BibTeX files
* easy – also scriptable – export of books
* tweet about every added book if wanted
* import entry from stdin or file

See [usage description](ub.usage.txt) for technical and usage details.

# Install

needs PHP5 to work. developed with 5.6.4, so maybe it will not work with older versions ¯\\\_(ツ)\_/¯

clone repository and symlink `ub` into a PATH directory.

symlink `ub.web.php` into a web server document root. It's highly recommended to setup a `web.password_sha256` in the `~/.ubconfig.json` file.

setup [twitter app](https://apps.twitter.com) for twitter integration.

ensure git, [bibsort](http://ftp.math.utah.edu/pub/bibsort/) and POSIX extension for PHP installed.

## Example usage

`ub` – let's first run ub once without any argument to create a basic config file

`ub db add mylibrary /path/to/mylibrary.bib` add `mylibrary.bib` (with the name `mlibrary`) 

`ub db link mylibrary main` set mylibrary as the main database

if you want, you can now add more databases and link them to different functions such as `barcode`, which is the database the `ub.web.php` uses to store books into. The purpose of this web service is to handle requests given by e.g. an [android barcode app](https://play.google.com/store/apps/details?id=com.google.zxing.client.android).

`ub db list` now shows all configured databases.

`ub add [ISBN]` adds the bibtex snippet imported from Google Books for the given ISBN to the main library.

`ub get [ISBN]` gives you the bibtex snippet for the given ISBN. works also with the bibtex keys, of course.

# Todo

* GoogleBooks: solid ISBN handling (eliminate - and spaces in ISBN), get book by Gooogle Books URL)
* make webinterface nice not just proof of concepty.
* add ub set command to set specific elements of an entry
* add ub comment command as abbreveation for ub set comment
* create possibility to manage pdf files on the hard disk with ub
* add twitter setup function
* add cli for configuring plugins (enable / disable plugins, …)
* add plugin for laws (dejure / gesetze-im-internet.de / …)
* add plugin for openjur, bundesgerichtshof.de, bundesverfassungsgericht.de, …
* add plugin for other libraries such as DNB, …
* add cronjob to automatically import books i lent
* add cronjob to remind me of books that are due soon 
* create NFC reading app based on https://github.com/scriptotek/nfcbookscanner
* auto add books when not found on get (useful for e.g. laws)
* make a link for every installed plugin (e.g. all googlebook imports will be imported to googlebooks link)
 
