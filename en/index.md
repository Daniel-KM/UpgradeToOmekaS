---
layout: page
title: From Omeka Classic to Omeka S
lang: en
order: 1
menu: false
---

{% include css_js.html %}

[Omeka](https://omeka.org) is a tool for managing and publishing of digitized document and data collections for libraries, museums, museums, galleries, archives and universities. It makes it easy to create standardized records for documents without any special knowledge and to promote them in online exhibitions. It features a wide range of modules and themes to meet most needs, from students, for example to present their work with documents that can be easily consulted, to large inter-university libraries, to share their collections without any physical constraints and without giving the rights of its own heritage to Google via the Google Books digitization program.

The development of the first version of Omeka began in 2006, and [version 1](https://github.com/omeka/Omeka/commits/1.0) was released on June 2, 2009 as a standalone content management system (_CMS_), in the style of websites of the time such as Wordpress. The [version 2](https://github.com/omeka/Omeka/commits/stable-2.0) was released on January 24, 2013. This was the first complete rewrite of the software (_application_), but based on the fairly popular [Zend framework](https://framework.zend.com/). The choice to use a platform meant that many components were available, and therefore the Omeka team could concentrate on developing features related to the tool itself. Finally, in view of new needs, particularly in terms of metadata standardization, multi-site and multilingual management, and the development of the semantic web, a rethink was quickly undertaken and a new rewrite began on August 12, 2013, with the [new version](https://github.com/omeka/omeka-s/tree/release-1.0) released on November 20, 2017, still based on Zend, which later became [Laminas](https://getlaminas.org/), but with the powerful [Doctrine](https://www.doctrine-project.org/) ORM for database management.

Today, the use of Omeka 2, renamed Omeka Classic, for new collections is no longer justified. The technical underpinnings are obsolete, in particular version 2 of Zend has been abandoned, and the software no longer works with current environments, notably officially maintained versions of PHP. Similarly, the majority of plugins and themes are no longer maintained. All Omeka Classic features are now implemented in Omeka S and much more ([IIIF](https://iiif.io) integration, native [RDF](https://en.wikipedia.org/wiki/Semantic_Web) data with [JSON-LD](https://json-ld.org/) REST API, etc.). Numerous tools have been developed to facilitate migration, starting in 2017 with the [Upgrade To Omeka S](https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS) plugin and the [Upgrade From Omeka Classic](https://github.com/Daniel-KM/Omeka-S-module-UpgradeFromOmekaClassic) module, and next the [Bulk Import](https://github.com/Daniel-KM/Omeka-S-module-BulkImport) module and the [Omeka 2 Importer](https://github.com/omeka-s-modules/Omeka2Importer) module from the Omeka team. In addition, in 2021 the Omeka team integrated a new interface for Omeka Classic, that is very close to the one of Omeka S (version 3 of Omeka 2), and migrated the main official themes.

This space contains all the information you need [to map old themes and extensions with new ones]({{ site.baseurl }}{% link en/omeka_mapping.md %}), plus a tool [to install Omeka S in one line or one file]({{ site.baseurl }}{% link en/omeka_s_install.md %}). It also includes all Omeka [plugins]({{ site.baseurl }}{% link en/omeka_plugins.md %}), [modules]({{ site.baseurl }}{% link en/omeka_s_modules.md %}), [classic themes]({{ site.baseurl }}{% link en/omeka_themes.md %}) and [themes]({{ site.baseurl }}{% link en/omeka_s_themes.md %}) that users may not have requested to be added on the [official site](https://omeka.org).