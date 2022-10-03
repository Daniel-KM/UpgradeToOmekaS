Upgrade to Omeka Semantic
=========================

This repository contains the sources to build the [pages that list the plugins,
modules and themes] of [Omeka Classic] and [Omeka S].

To get the plugin that allows to upgrade automatically an Omeka Classic
installation into Omeka Semantic, go to [this page].


Update of the list of plugins, modules and themes
-------------------------------------------------

The lists are automatically generated from public sources on github and gitlab.
Nevertheless, the new addons should be well referenced (via a readme or via a
topic). To be sure it will be included, add the topic `omeka`, `omeka-s`,
`omeka-plugin`, `omeka-s-module`, `omeka-theme` or `omeka-s-theme` to it on the
main page of the addon, or use "Omeka" in the main readme of the addon
repository.

To add bad referenced addons, simply fill its main url in the matching csv file
in the directory `_data/` and run the php script:

```
php -f _scripts/update_data.php
```

You may need to add a file with a token from your github account in `_scripts/token_github.txt`
to be allowed to fetch more than 50 results.


TODO
----

- [ ] Add a column for the Omeka directory name.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [issues] page on GitHub.


License
-------

This tool is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software’s author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user’s
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Copyright
---------

* Copyright Daniel Berthereau, 2017-2020 (see [Daniel-KM] on GitHub)


[pages that list the plugins, modules and themes]: https://daniel-km.github.io/UpgradeToOmekaS/
[Omeka Classic]: https://omeka.org/classic
[Omeka S]: https://omeka.org/s
[this page]: https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS
[issues]: https://github.com/Daniel-KM/UpgradeToOmekaS/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
