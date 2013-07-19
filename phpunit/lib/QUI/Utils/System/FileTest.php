<?php

use QUI\Utils\System\File as File;

class FileTest extends PHPUnit_Framework_TestCase
{
    public function testGetMimeTypes()
    {
        $mimetypes = File::getMimeTypes();

        if ( !is_array( $mimetypes ) ) {
            $this->fail( 'no mimetypes exist' );
        }
    }
}
