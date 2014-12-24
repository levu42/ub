# Abstract

*ub* is a tool to manage bibtex files and make the import from different sources
like Google Books, university libaries etc. easier.

## Features

* multiple BibTeX files possible
* basic git integration for commit & push BibTeX files after update
* import entries from Google Books
* import entries from the library database of the library of the University Frankfurt
* find entries via ISBN and other identifiers
* list all books in all or one BibTeX file(s)
* copy entries between BibTeX files
* easy – also scriptable – export of books
* tweet about every added book if wanted

See [usage description](ub.usage.txt) for technical and usage details.

# Install

clone repository and symlink ub into a PATH directory.

symlink ub.web.php into a web server document root. It's highly recommended to setup a web.password\_sha256 in the ~/.ubconfig.json file.

setup [twitter app](https://apps.twitter.com) for twitter integration.

ensure git (for git support), [bibsort](http://ftp.math.utah.edu/pub/bibsort/) and POSIX extension for PHP installed.

# Todo

* GoogleBooks: solid ISBN handling (eliminate - and spaces in ISBN), get book by Gooogle Books URL)
* make webinterface nice not just proof of concepty.
* create possibility to manage pdf files on the hard disk with ub
* add twitter setup function
* add cli for configuring plugins (enable / disable plugins, …)
* add plugin for laws (dejure / gesetze-im-internet.de / …)
* add plugin for openjur, bundesgerichtshof.de, bundesverfassungsgericht.de, …
* add plugin for other libraries such as DNB, …
* add cronjob to automatically import books i lent
* add cronjob to remind me of books that are due soon 
* create NFC reading app based on https://github.com/scriptotek/nfcbookscanner

 
