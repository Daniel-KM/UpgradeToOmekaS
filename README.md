Upgrade from Omeka Classic to Omeka Semantic
============================================

[Upgrade To Omeka Semantic] is a plugin for [Omeka Classic] that allows to
upgrade automatically your installation from Omeka 2 to Omeka S.

Some plugins and themes are not upgraded yet, but a compatibility layer is
available for common themes.

One important benefit against the standard upgrade process is that the id of
items are kept, so the common urls publicly used are not lost. The id of
collections and files are lost. This is related to the fact that Omeka Semantic
uses a single id for all resources. Anyway, it's always recommended you set your
own single and permanent identifiers that don't depend on an internal position
in a database. The element Dublin Core Identifier is designed for that and a
record can have multiple single identifiers. There are numerous solution: named
number like in library, isbn for books, or random id like with ark, noid, doi,
etc. And they can be displayed in the public url with the plugin [Clean Url].

A second benefit is that the process is quick and automatic and this is mostly
transparent to the users. They won't see anything change. Conversely, the
curators will benefits from all the new features of Omeka S. The machines too:
Omeka S is designed to share its resources larger and in a more standardized way
than Omeka 2.


TODO
----

* End of the install and copy of settings, records and files.
* Add a prefix in the core of Omeka S
* Update `.htaccess` when Omeka Semantic is installed in a subfolder of Omeka Classic
* Import, conversion and copy
* Compatibility layer

* Fix the bug on the password, asked twice with a separate database in the form.


Installation
------------

Uncompress files and rename plugin folder `UpgradeToOmekaS`.

Then install it like any other Omeka plugin and follow the config instructions.

*IMPORTANT*: Even if the original files and the original database are only read,
backup your database AND your files before the process and check them before the
process.


Usage
-----

The process is automatic from an up-to-date Omeka install. Log in as a super
user, then simply click on "Upgrade" in the top admin bar, fill the short form,
click the `Submit` button, wait from a few seconds to a few tens of seconds, and
click on the provided url.


Upgrade Process
---------------

The upgrade process follows these steps. All steps are automatic except the
first and the last.

* *IMPORTANT*: backup your database and your files manually and check them
* Optional: create manually the database if you want a separate one, else, the
  database will be shared with the one of Omeka Classic. This is not a problem,
  but currently, Omeka S doesn't manage prefixes. Fortunately, there was one by
  default in Omeka 2 ("omeka_"). Furthermore, Omeka 2 creates tables with plural
  names (like "users") and Omeka S with singular names ("user"), so even if the
  prefix was removed during install, there will be no issue. Anyway, it's
  recommended to create a separate database.
* Checks
  * rights to write in the specified directory
  * version and config of Omeka
  * version of plugins
* Set the site down, except for super user
* Fetch last release of Omeka S from Github, uncompress it and install it
* Set parameters
  * new database or current one
  * main settings
* Conversion of all Omeka Classic tables (users, records, plugins...) into the
Omeka Semantic ones via simple mysql queries
* Copy of files, hard links (recommended on linux servers), soft links or dummy
  files
* Install the compatibility layers and copy themes
* Check manually main settings and parameters of each modules of Omeka Semantic

The site will be available automatically at the end of the process with the
specified link.

Enjoy it!


Warning
-------

Use it at your own risk.

It's always recommended to backup your files and database regularly so you can
roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software's author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user's
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)


Copyright
---------

* Copyright Daniel Berthereau, 2017


[Upgrade To Omeka Semantic]: https://github.com/Daniel-KM/UpgradeToOmekaS
[Omeka]: https://www.omeka.org
[Omeka S]: https://omeka.org/s
[Clean Url]: https://github.com/Daniel-KM/CleanUrl
[plugin issues]: https://github.com/Daniel-KM/UpgradeToOmekaS/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
