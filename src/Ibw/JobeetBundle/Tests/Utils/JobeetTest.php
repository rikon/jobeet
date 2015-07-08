<?php
namespace Ibw\JobeetBundle\Tests\Unils;

use Ibw\JobeetBundle\Utils\Jobeet;

class JobeetTest extends \PHPUnit_Framework_TestCase
{
	public function testSlugify() {
		$this->assertEquals('sensio', Jobeet::slugify('Sensio'));
		$this->assertEquals('n-a', Jobeet::slugify(''));
		$this->assertEquals('n-a', Jobeet::slugify(' - '));
		if (function_exists('iconv')) {
    		$this->assertEquals('developpeur-web', Jobeet::slugify('Développeur Web'));
		}	
	}
}