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

     public function testClearArray()
     {
         $result = \QUI\Utils\Security\Orthos::clearArray(array(
             'a test string \' %%% **',
             'support@pcsg.de',
         ));


         if ( $result[0] != 'a test string  %%% **' ) {
             $this->fail( '\QUI\Utils\Security\Orthos::testClearArray fail' );
         }

         if ( $result[1] != 'support@pcsg.de' ) {
             $this->fail( '\QUI\Utils\Security\Orthos::testClearArray fail' );
         }
     }
}
