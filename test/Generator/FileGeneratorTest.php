<?php

/**
 * @see       https://github.com/laminas/laminas-code for the canonical source repository
 * @copyright https://github.com/laminas/laminas-code/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-code/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Code\Generator;

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\FileGenerator;
use Laminas\Code\Reflection\FileReflection;

/**
 * @category   Laminas
 * @package    Laminas_Code_Generator
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2012 Laminas (https://www.zend.com)
 * @license    https://getlaminas.org/license/new-bsd     New BSD License
 *
 * @group Laminas_Code_Generator
 * @group Laminas_Code_Generator_Php
 * @group Laminas_Code_Generator_Php_File
 */
class FileGeneratorTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruction()
    {
        $file = new FileGenerator();
        $this->assertEquals('Laminas\Code\Generator\FileGenerator', get_class($file));
    }

    public function testSourceContentGetterAndSetter()
    {
        $file = new FileGenerator();
        $file->setSourceContent('Foo');
        $this->assertEquals('Foo', $file->getSourceContent());
    }

    public function testIndentationGetterAndSetter()
    {
        $file = new FileGenerator();
        $file->setIndentation('        ');
        $this->assertEquals('        ', $file->getIndentation());
    }

    public function testToString()
    {
        $codeGenFile = FileGenerator::fromArray(array(
                                                     'requiredFiles' => array('SampleClass.php'),
                                                     'class' => array(
                                                         'flags' => ClassGenerator::FLAG_ABSTRACT,
                                                         'name' => 'SampleClass',
                                                         'extendedClass' => 'ExtendedClassName',
                                                         'implementedInterfaces' => array('Iterator', 'Traversable')
                                                     )
                                                ));


        $expectedOutput = <<<EOS
<?php

require_once 'SampleClass.php';

abstract class SampleClass extends ExtendedClassName implements Iterator, Traversable
{


}


EOS;

        $output = $codeGenFile->generate();
        $this->assertEquals($expectedOutput, $output, $output);
    }

    public function testFromReflection()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'UnitFile');

        $codeGenFile = FileGenerator::fromArray(array(
                                                     'class' => array(
                                                         'name' => 'SampleClass'
                                                     )
                                                ));

        file_put_contents($tempFile, $codeGenFile->generate());

        require_once $tempFile;

        $fileGenerator = FileGenerator::fromReflection(new FileReflection($tempFile));

        unlink($tempFile);

        $this->assertEquals('Laminas\Code\Generator\FileGenerator', get_class($fileGenerator));
        $this->assertEquals(1, count($fileGenerator->getClasses()));

    }

    public function testFromFileReflection()
    {
        $this->markTestIncomplete('Some scanning capabilities are incomplete, including file DocBlock comment retrieval and method scanning');

        $file = __DIR__ . '/TestAsset/TestSampleSingleClass.php';
        require_once $file;

        $codeGenFileFromDisk = FileGenerator::fromReflection($fileRefl = new FileReflection($file));

        $codeGenFileFromDisk->getClass()->addMethod('foobar');

        $expectedOutput = <<<EOS
<?php
/**
 * File header here
 *
 * @author Ralph Schindler <ralph.schindler@zend.com>
 */



/* Laminas_Code_Generator_FileGenerator-ClassMarker: {LaminasTest\Code\Generator\TestAsset\TestSampleSingleClass} */


namespace LaminasTest\Code\Generator\TestAsset;

/**
 * class docblock
 *
 * @package Laminas_Reflection_TestSampleSingleClass
 *
 */
class TestSampleSingleClass
{

    /**
     * Enter description here...
     *
     * @return bool
     *
     */
    public function someMethod()
    {
        /* test test */
    }

    public function foobar()
    {
    }


}


EOS;
        $this->assertEquals($expectedOutput, $codeGenFileFromDisk->generate());
    }

    public function testFileLineEndingsAreAlwaysLineFeed()
    {
        $codeGenFile = FileGenerator::fromArray(array(
            'requiredFiles' => array('SampleClass.php'),
            'class' => array(
                'abstract' => true,
                'name' => 'SampleClass',
                'extendedClass' => 'ExtendedClassName',
                'implementedInterfaces' => array('Iterator', 'Traversable')
                )
            ));

        // explode by newline, this would leave CF in place if it were generated
        $lines = explode("\n", $codeGenFile->generate());

        $targetLength = strlen('require_once \'SampleClass.php\';');
        $this->assertEquals($targetLength, strlen($lines[2]));
        $this->assertEquals(';', $lines[2]{$targetLength-1});
    }

    /**
     * @group Laminas-11218
     */
    public function testGeneratesUseStatements()
    {
        $file = new FileGenerator();
        $file->setUse('My\Baz')
             ->setUses(array(
                 array('Your\Bar', 'bar'),
             ));
        $generated = $file->generate();
        $this->assertContains('use My\\Baz;', $generated);
        $this->assertContains('use Your\\Bar as bar;', $generated);
    }

    public function testGeneratesNamespaceStatements()
    {
        $file = new FileGenerator();
        $file->setNamespace('Foo\Bar');
        $generated = $file->generate();
        $this->assertContains('namespace Foo\\Bar', $generated, $generated);
    }
}
