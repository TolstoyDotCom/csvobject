<?php
/*
 * Copyright 2020 Chris Kelly
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */

require_once( __DIR__ . '/vendor/autoload.php' );

use TolstoyDotCom\csvobject\Generator;
use Garden\Cli\Cli;

try {
	$cli = new Cli();

	$cli->description( 'Generates PHP classes to read a CSV file.' )
		->opt( 'input:i', 'The CSV file, either a relative or absolute path. Example: data/test.csv', TRUE )
		->opt( 'name:n', 'The base name of of the generated PHP files. Example: MyTest', TRUE )
		->opt( 'output:o', 'The output directory, either a relative or absolute path. Must be an existing directory and must be writable. Example: output', TRUE )
		->opt( 'main_template:mt', 'Path to custom version of reader.template.' )
		->opt( 'runner_template:rt', 'Path to custom version of runner.template.' );

	$options = $cli->parse( $argv, TRUE );

	if ( !$options[ 'mainClassTemplatePath' ] ) {
		$options[ 'mainClassTemplatePath' ] = __DIR__ . '/reader.template';
	}

	if ( !$options[ 'runnerClassTemplatePath' ] ) {
		$options[ 'runnerClassTemplatePath' ] = __DIR__ . '/runner.template';
	}

	$generator = new Generator( $options );
	$generator->generate();

	exit( 0 );
}
catch ( Exception $e ) {
	echo $e->getMessage() . "\n";

	exit( -1 );
}
