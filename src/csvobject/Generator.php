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

namespace TolstoyDotCom\csvobject;

use EasyCSV\Reader;
use Jawira\CaseConverter\Convert;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use Webmozart\PathUtil\Path;
use LogicException;

class Generator {
	private const BODY_CTOR = <<<'END'
	$this->data = array_values( $data );
END;

	private const BODY_GET_BY_INDEX = <<<'END'
	if ( $index < 0 || $index >= self::NUM_COLS ) {
	throw new \OutOfRangeException( 'Index out of bounds' );
}

return $this->data[ $index ];
END;

	private const BODY_GET_BY_KEY = <<<'END'
	if ( !isset( self::KEY_TO_INDEX[ $key ] ) ) {
	throw new \LogicException( 'Key does not exist' );
}

return $this->data[ self::KEY_TO_INDEX[ $key ] ];
END;

	private $options;

	public function __construct( $options ) {
		$this->options = $options;
	}

	public function generate() {
		$inputFile = $this->options[ 'input' ];
		$outputDir = $this->options[ 'output' ];
		$baseName = $this->options[ 'name' ];
		$readerTemplateFile = $this->options[ 'mainClassTemplatePath' ];
		$runnerTemplateFile = $this->options[ 'runnerClassTemplatePath' ];

		$rowClassName = $baseName . 'Row';
		$mainClassName = $baseName;
		$runnerClassName = 'Runner' . $baseName;

		$reader = new Reader( $inputFile, 'r' );
		$headers = $reader->getHeaders();
		if ( !$headers || !is_array( $headers ) ) {
			throw new LogicException( 'No headers found; does the first row have headers?' );
		}

		$substitutions = [
			'%%MAIN_CLASS_NAME%%' => $mainClassName,
			'%%SOURCE_PATH%%' => $inputFile,
			'%%ROW_CLASS_NAME%%' => $rowClassName,
			'%%OUTPUT_DIR%%' => $outputDir,
		];

		$rowClassContents = '<?php' . "\n\n" . $this->makeRowClass( $headers, $rowClassName );
		$mainClassContents = $this->makeTemplate( $substitutions, $readerTemplateFile );
		$runnerClassContents = $this->makeTemplate( $substitutions, $runnerTemplateFile );

		$this->saveFile( $outputDir, $mainClassName . '.php', $mainClassContents );
		$this->saveFile( $outputDir, $rowClassName . '.php', $rowClassContents );
		$this->saveFile( $outputDir, $runnerClassName . '.php', $runnerClassContents );
	}

	public function getOptions() {
		return $this->options;
	}

	public function setOptions( $options ) {
		$this->options = $options;
	}

	/**
	 * @param array $substitutions
	 */
	protected function makeTemplate( array $substitutions, string $templatePath ) : string {
		$contents = file_get_contents( $templatePath );
		if ( !$contents ) {
			throw new LogicException( 'Template ' . $templatePath . ' not found or not readable' );
		}

		return str_replace( array_keys( $substitutions ), array_values( $substitutions ), $contents );
	}

	/**
	 * @param array $headers
	 * @param string $rowClassName
	 */
	protected function makeRowClass( array $headers, string $rowClassName ) : string {
		$class = new ClassType( $rowClassName );

		$class->addComment( 'Represents a row from the CSV' );

		$class->addProperty( 'data' )
			->setVisibility( 'private' );

		$class->addConstant( 'NUM_COLS', count( $headers ) )
			->setVisibility( 'public' );

		$class->addMethod( '__construct' )
			->setBody( self::BODY_CTOR )
			->setVisibility( 'public' )
			->addParameter( 'data' );

		$class->addMethod( 'getByIndex' )
			->addComment( 'Return the value at the given index' )
			->addComment( '@return string' )
			->setVisibility( 'public' )
			->setBody( self::BODY_GET_BY_INDEX )
			->addParameter( 'index' );

		$class->addMethod( 'getByKey' )
			->addComment( 'Return the value for the given key' )
			->addComment( '@return string' )
			->setVisibility( 'public' )
			->setBody( self::BODY_GET_BY_KEY )
			->addParameter( 'key' );

		$methodNames = [];
		$keyToIndex = [];
		$count = 0;
		foreach ( $headers as $header ) {
			$keyToIndex[ $header ] = $count;

			$methodName = $this->buildMethodName( $header );
			$methodNames[] = $methodName;

			$class->addMethod( $methodName )
				->addComment( 'Return the value of ' . $header . ' (column #' . $count . ')' )
				->addComment( '@return string' )
				->setVisibility( 'public' )
				->setBody( 'return $this->data[ ' . $count . ' ];' );

			$count++;
		}

		$class->addMethod( 'demo' )
			->addComment( 'Demonstrates the methods' )
			->addComment( '@return array' )
			->setVisibility( 'public' )
			->setBody( $this->buildDemoBody( $methodNames ) );

		$class->addConstant( 'KEY_TO_INDEX', $keyToIndex )
			->setVisibility( 'private' );

		$printer = new Printer;

		return $printer->printClass($class);
	}

	protected function buildMethodName( string $input ) : string {
		preg_match( '/^([0-9]{4})-([0-9]{2})$/', $input, $matches );

		if ( !empty( $matches[ 1 ] ) && !empty( $matches[ 2 ] ) ) {
			return 'getY' . $matches[ 1 ] . 'M' . $matches[ 2 ];
		}

		$converter = new Convert( 'get' . $input );

		return $converter->toCamel();
	}

	/**
	 * @param array $methodNames
	 */
	protected function buildDemoBody( array $methodNames ) : string {
		$ret = "\t\t\$ary = [\n";

		foreach ( $methodNames as $methodName ) {
			$ret .= "\t'$methodName' => \$this->$methodName(),\n";
		}

		$ret .= "];\n\nreturn \$ary;";

		return $ret;
	}

	protected function saveFile( string $dir, string $name, string $contents ) {
		$path = Path::join( $dir, $name );

		$fp = fopen( $path, 'w' );
		if ( !$fp ) {
			throw new LogicException( 'Cannot open ' . $path . ' for writing' );
		}

		if ( fwrite( $fp, $contents ) === FALSE ) {
			throw new LogicException( 'Cannot write to ' . $path );
		}

		fclose( $fp );
	}
}
