<?php

class OrthosTest extends PHPUnit_Framework_TestCase
{
     public function testClear()
     {
         $result = \QUI\Utils\Security\Orthos::clear( 'a test string \' %%% **' );

         if ( $result != 'a test string  %%% **' ) {
            $this->fail( '\QUI\Utils\Security\Orthos::clear fail' );
         }
     }
}
