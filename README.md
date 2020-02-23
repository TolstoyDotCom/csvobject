An easy way to read unfamiliar CSV files. Generates classes representing the CSV file and the rows. Requires PHP 7.2 or greater and Composer.

For instance, if a CSV has headers 'Make' and 'Model', the generated class representing the rows will have 'getMake' and 'getModel' methods.


Usage
------

* Install Composer if necessary.

* Create a new directory, open a command line window in that directory, and type:

```sh
git clone https://github.com/TolstoyDotCom/csvobject.git
cd csvobject
```

* (Alternatively, download this project into a local directory)

* (To use this in your own project: `composer require tolstoydotcom/csvobject`)

* In the root directory of this project, type:

```sh
composer update
```

* If the PHP extension mbstring isn't installed, either install it or type:

```sh
composer require symfony/polyfill-mbstring
```

* Make the `output` directory writable.

* Run this:

```sh
php generate.php -i data/test.csv -n MyTest -o output
```

That command takes `data/test.csv` as input, writes files tp `output`, and uses `MyTest` as the base name for the generated files.

Then, run this to see a demo of the `MyTestRow` methods:

```sh
php output/RunnerMyTest.php
```

Running generate.php produces three files in the output directory:

* MyTest.php: has the MyTest class that represents the CSV file, has methods to read each row, read all rows, etc.

* MyTestRow.php: has the MyTestRow class that represents the CSV.

* RunnerMyTest.php: uses MyTest to show the first few rows of the CSV.


Limitations
------

* This is just a simple library meant for local use and only with trusted CSVs. No attempt has been made to sanitize output, etc. If you install this on a public server, securing the installation is up to you.

* It assumes the headers are in the first row.

* Name collisions are possible.

* You might need to change the paths in the files if you move them around.

* Writing to the CSV isn't supported.


Licensing
------
The source code is licensed under the The Apache Software License, Version 2.0, see LICENSE. The application includes many components from others and those are covered under their licenses.
