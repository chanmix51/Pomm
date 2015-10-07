=================================================
POMM: The PHP Object Model Manager for Postgresql
=================================================

.. image:: https://secure.travis-ci.org/chanmix51/Pomm.png?branch=1.2
   :target: http://travis-ci.org/#!/chanmix51/Pomm

.. image:: https://scrutinizer-ci.com/g/chanmix51/Pomm/badges/quality-score.png?s=5766ac7091629c3af205bbcca8623bd2e8cfe85e
   :target: https://scrutinizer-ci.com/g/chanmix51/Pomm/

.. image:: https://poser.pugx.org/Pomm/Pomm/version.png
   :target: https://poser.pugx.org/

.. image:: https://poser.pugx.org/Pomm/Pomm/d/total.png
   :target: https://packagist.org/packages/pomm/pomm

This branch is the latest stable branch of Pomm 1.x. The 1.3 version will be the last version of this package. The stable `Pomm 2.0<https://github.com/pomm-project>`_ is the new generation of Pomm Model Manager.

What is Pomm ?
**************

Pomm is an open source Postgresql access framework for PHP. It is not an ORM, it is an Object Model Manager. Pomm offers an alternative approach than ORM to using database in object oriented web developments. `Read more here <http://www.pomm-project.org/about>`_.

Pomm 1.2 works with PHP 5.3 and Postgresql 9.0 and above.

You can reach

* `installation guide <http://www.pomm-project.org/howto/install>`_
* `documentation <http://www.pomm-project.org/documentation/manual-1.2>`_
* `code examples <http://www.pomm-project.org/documentation/examples>`_
* `mailing list <https://groups.google.com/forum/#!forum/pommproject>`_

=====================
How to install Pomm ?
=====================

The easy way: composer
**********************
Using `composer <http://packagist.org/>`_ installer and autoloader is probably the easiest way to install Pomm and get it running. What you need is just a ``composer.json`` file in the root directory of your project:

::

  {
  "require": {
      "pomm/pomm": "1.2.*"
    }
  }

Invoking ``composer.phar`` will automagically download Pomm, install it in a ``vendor`` directory and set up the according autoloader.

Using Pomm with a PHP framework
*******************************

* Silex `PommServiceProvider <https://github.com/chanmix51/PommServiceProvider>`_
* Symfony2 `PommBundle <https://github.com/chanmix51/PommBundle>`_

With Silex, it is possible to bootstrap a kitchen sink using this `gist <https://gist.github.com/chanmix51/3402026>`, in an empty directory just issue the command::

    wget -O - 'https://gist.github.com/chanmix51/3402026/raw/3cf2125316687be6d3ab076e66f291b68b422ce7/create-pomm-silex.sh' | bash

And follow the instructions.

===========================
How to contribute to Pomm ?
===========================

That's very easy with github:

* Send feedback to `@PommProject <https://twitter.com/#!/PommProject>`_ on twitter or by mail at <hubert DOT greg AT gmail DOT com>
* Report bugs (very appreciated)
* Fork and PR (very very appreciated)
* Send vacuum tubes to the author (actual preferred are russians 6Π21C (6P21S) and 6Π30B (6P30B))

Running tests
*************

.. code-block:: sh

    psql -c 'CREATE DATABASE pomm_test' -U postgres -h 127.0.0.1
    psql -c 'CREATE EXTENSION hstore' -U postgres -h 127.0.0.1 pomm_test
    psql -c 'CREATE EXTENSION ltree' -U postgres -h 127.0.0.1 pomm_test

    phpunit --configuration tests/phpunit.travis.xml
