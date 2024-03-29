<?php

/**
 * This file contains QUI\Utils\Image
 */

namespace QUI\Utils;

// Image Bibliothek feststellen welche verwendet werden soll
if (!class_exists('Imagick')) {
    exec(escapeshellcmd('convert'), $im_console);
}

if (class_exists('Imagick')) {
    define('IMAGE_LIBRARY', 'IMAGICK_PHP');
} elseif (is_array($im_console) && count($im_console)) {
    define('IMAGE_LIBRARY', 'IMAGICK_SYSTEM');
} elseif (function_exists('imagecopyresampled')) {
    define('IMAGE_LIBRARY', 'GDLIB');
} else {
    /**
     * IMAGE_LIBRARY defines which image library is available
     * IMAGICK_PHP    = Image Magick is via php available;
     * IMAGICK_SYSTEM = Image Magick is on the system shell available;
     * GDLIB          = GDLib is via php available;
     * false          = no image library is available :(
     */
    define('IMAGE_LIBRARY', false);
}

/**
 * Helper for image handling
 * resize / convert / relect images, set watermarks
 *
 * @uses    Imagick, if it enabled
 * @uses    GDLIB, if it enabled
 * @uses    imagick on the shell, if it enabled
 *
 * @author  www.pcsg.de (Henning Leutz)
 *
 * @deprecated better use http://image.intervention.io/
 *
 * @todo    docu translation
 */
class Image
{
    /**
     * Bildgrösse ändern
     *
     * @param string $original - Pfad zum original Bild
     * @param string $new_image - Pfad zum neuen Bild
     * @param integer $new_width
     * @param integer $new_height
     *
     * @return boolean
     *
     * @throws \QUI\Exception
     */
    public static function resize(
        $original,
        $new_image,
        $new_width = 0,
        $new_height = 0
    ) {
        if (!\file_exists($original)) {
            return false;
        }

        $new_width = (int)$new_width;
        $new_height = (int)$new_height;

        // Bild Informationen bekommen
        $info = \QUI\Utils\System\File::getInfo($original);

        //wenn nur höhe oder nur breite übergeben wird gegenstück berechnen
        if ($new_height <= 0 && $new_width <= 0) {
            return false;
        }

        if ($new_height <= 0) {
            $resize_by_percent = ($new_width * 100) / $info['width'];
            $new_height = (int)\round(($info['height'] * $resize_by_percent) / 100);
        } elseif ($new_width <= 0) {
            $resize_by_percent = ($new_height * 100) / $info['height'];
            $new_width = (int)\round(($info['width'] * $resize_by_percent) / 100);
        }

        // no resize to make the image larger
        if ($new_height > $info['height']) {
            $new_height = $info['height'];
        }

        if ($new_width > $info['width']) {
            $new_width = $info['width'];
        }

        if ($new_height == $info['height'] && $new_width == $info['width']) { // Falls Höhe und Breite gleich der original Grösse ist dann nur ein Copy
            if ($original == $new_image) {
                return true;
            }

            if (!\copy($original, $new_image)) {
                throw new \QUI\Exception(
                    'Copy failed ' . $original . '-->' . $new_image
                );
            }

            return true;
        }

        if (IMAGE_LIBRARY == 'IMAGICK_PHP') { // Image Magick
            try {
                $thumb = new \Imagick();
                $thumb->readImage($original);
                $thumb->resizeImage(
                    $new_width,
                    $new_height,
                    \Imagick::FILTER_LANCZOS,
                    1
                );

                if (
                    strpos($new_image, 'jpg') !== false
                    || strpos($new_image, 'JPG') !== false
                    || strpos($new_image, 'jpeg') !== false
                    || strpos($new_image, 'JPEG') !== false
                ) {
                    $thumb->setImageCompression(\Imagick::COMPRESSION_JPEG);
                    $thumb->setImageCompressionQuality(80);
                }

                $thumb->writeImage($new_image);
                $thumb->destroy();

                return true;
            } catch (\Exception $e) {
                throw new \QUI\Exception(
                    'Resized width ' . IMAGE_LIBRARY . ' failed. ' . $original . '-->'
                    . $new_image . ' ## WIDTH: ' . $new_width . ' HEIGHT:' . $new_height
                    . "\n" . $e->getMessage()
                );
            }
        } elseif (IMAGE_LIBRARY == 'IMAGICK_SYSTEM') { // Image Magick - Console
            $size = \getimagesize($original);
            $orig_width = $size[0];
            $orig_height = $size[1];

            $exec = 'convert -size ' . $orig_width . 'x' . $orig_height . ' \'' . $original
                . '\' -thumbnail ' . $new_width . 'x' . $new_height . ' \'' . $new_image
                . '\'';

            \exec(\escapeshellcmd($exec), $return);

            if (\file_exists($new_image)) {
                return true;
            }

            throw new \QUI\Exception(
                'Resized width ' . IMAGE_LIBRARY . ' failed. ' . $original . '-->'
                . $new_image . "\n\n ERROR:" . print_r($return, true) . "\n\nEXEC: "
                . $exec
            );
        } elseif (IMAGE_LIBRARY == 'GDLIB') { // GD Lib - sehr schlechte Quali
            $size = getimagesize($original);
            $orig_width = $size[0];
            $orig_height = $size[1];

            switch ($size[2]) {
                case 1:
                    // GIF
                    try {
                        $old = ImageCreateFromGif($original);
                        $new = ImageCreate($new_width, $new_height);

                        ImageCopyResized(
                            $new,
                            $old,
                            0,
                            0,
                            0,
                            0,
                            $new_width,
                            $new_height,
                            $orig_width,
                            $orig_height
                        );

                        ImageGif($new, $new_image, 80);

                        // Speicher wieder freigeben
                        imagedestroy($old);
                        imagedestroy($new);

                        return true;
                    } catch (\Exception $e) {
                        throw new \QUI\Exception(
                            'Resized width ' . IMAGE_LIBRARY . ' failed. ' . $original
                            . '-->' . $new_image
                        );
                    }
                    break;

                case 2:
                    // JPG
                    try {
                        $old = ImageCreateFromJPEG($original);
                        $new = imagecreatetruecolor($new_width, $new_height);

                        ImageCopyResized(
                            $new,
                            $old,
                            0,
                            0,
                            0,
                            0,
                            $new_width,
                            $new_height,
                            $orig_width,
                            $orig_height
                        );

                        ImageJPEG($new, $new_image, 80);

                        // Speicher wieder freigeben
                        imagedestroy($old);
                        imagedestroy($new);

                        return true;
                    } catch (\Exception $e) {
                        throw new \QUI\Exception(
                            'Resized width ' . IMAGE_LIBRARY . ' failed. ' . $original
                            . '-->' . $new_image
                        );
                    }
                    break;

                case 3:
                    // PNG
                    try {
                        $old = ImageCreateFromPNG($original);
                        $new = imagecreatetruecolor($new_width, $new_height);

                        ImageCopyResized(
                            $new,
                            $old,
                            0,
                            0,
                            0,
                            0,
                            $new_width,
                            $new_height,
                            $orig_width,
                            $orig_height
                        );

                        ImagePNG($new, $new_image, 8);

                        // Speicher wieder freigeben
                        imagedestroy($old);
                        imagedestroy($new);

                        return true;
                    } catch (\Exception $e) {
                        throw new \QUI\Exception(
                            'Resized width ' . IMAGE_LIBRARY . ' failed. ' . $original
                            . '-->' . $new_image
                        );
                    }
                    break;

                default:
                    throw new \QUI\Exception('Image not supported ' . $original);
            }
        } else {
            throw new \QUI\Exception('No Image Library');
        }
    }

    /**
     * Legt ein Wasserzeichen auf ein Bild
     *
     * @param string $image - Bild welches verändert werden soll
     * @param string $watermark - Wasserzeichen
     * @param string|boolean $newImage - if it set, a new image would be generated
     * @param integer $top - x position of the watermark
     * @param integer $left - y position of the watermark
     *
     * @throws \QUI\Exception
     */
    public static function watermark(
        $image,
        $watermark,
        $newImage = false,
        $top = 0,
        $left = 0
    ) {
        if (!file_exists($image)) {
            throw new \QUI\Exception('Original Image not exist. ' . $image);
        }

        if (!file_exists($watermark)) {
            throw new \QUI\Exception('Watersign Image not exist. ' . $watermark);
        }

        if (IMAGE_LIBRARY == 'IMAGICK_PHP') { // Image Magick
            try {
                $_image = new \Imagick($image);
                $_watermark = new \Imagick($watermark);

                $_image->compositeImage(
                    $_watermark,
                    $_watermark->getImageCompose(),
                    $left,
                    $top
                );

                if ($newImage) {
                    $_image->writeImage($newImage);
                } else {
                    $_image->writeImage($image);
                }

                $_image->destroy(); // ausm ram raus
                $_watermark->destroy();
            } catch (\ImagickException $e) {
                throw new \QUI\Exception($e->getMessage());
            }
        } elseif (IMAGE_LIBRARY == 'IMAGICK_SYSTEM') { // Image Magick - Console
            $exec
                = 'composite -gravity center ' . $watermark . ' ' . $image . ' ' . $image;
            exec(escapeshellcmd($exec), $return);

            if (is_array($return) && count($return)) {
                return true;
            }

            throw new \QUI\Exception('PT_File::watermark(); Could not create');
        } elseif (IMAGE_LIBRARY == 'GDLIB') { // GD Lib - sehr schlechte Quali
            $size = getimagesize($image);
            $w_size = getimagesize($watermark);

            // TrueColor Fix
            self::convertToTrueColor($image);
            self::convertToTrueColor($watermark);

            switch ($size[2]) {
                case 1:
                    // GIF
                    $old_image = imagecreatefromgif($image);
                    break;

                case 2:
                    // JPG
                    $old_image = imagecreatefromjpeg($image);
                    break;

                case 3:
                    // PNG
                    $old_image = imagecreatefrompng($image);
                    break;
            }

            // Wasserzeichen
            switch ($w_size[2]) {
                case 1:
                    // GIF
                    $wasserzeichen = imagecreatefromgif($watermark);
                    break;

                case 2:
                    // JPG
                    $wasserzeichen = imagecreatefromjpeg($watermark);
                    break;

                case 3:
                    // PNG
                    $wasserzeichen = imagecreatefrompng($watermark);
                    break;
            }

            // Breite und Höhe des Bilds ermitteln
            $width = imagesx($old_image);
            $height = imagesy($old_image);

            $w_width = imagesx($wasserzeichen);
            $w_height = imagesy($wasserzeichen);

            // Neues Bild erstellen
            $new_image = imagecreatetruecolor($width, $height);

            // Bild in das Neuerstellte einfügen
            imagecopy($new_image, $old_image, 0, 0, 0, 0, $width, $height);

            // Wasserzeichen einfügen
            imagecopy(
                $new_image,
                $wasserzeichen,
                0,
                0,
                0,
                0,
                $w_width,
                $w_height
            );

            // Erstellen
            switch ($size[2]) {
                case 1:
                    // GIF
                    imagegif($new_image, $image);
                    break;

                case 2:
                    // JPG
                    imagejpeg($new_image, $image);
                    break;

                case 3:
                    // PNG
                    imagepng($new_image, $image);
                    break;
            }

            imagedestroy($new_image);
            imagedestroy($old_image);
        }
    }

    /**
     * Wandelt ein Bild in TrueColor um
     *
     * @param string $image - Path zum Bild
     */
    public static function convertToTrueColor($image)
    {
        $size = getimagesize($image);
        $w_size = getimagesize($image);

        switch ($size[2]) {
            case 1:
                // GIF
                $img = imagecreatefromgif($image);
                break;

            case 2:
                // JPG
                $img = imagecreatefromjpeg($image);
                break;

            case 3:
                // PNG
                $img = imagecreatefrompng($image);
                break;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        if (!imageistruecolor($img)) {
            $original_transparency = imagecolortransparent($img);

            //we have a transparent color
            if ($original_transparency >= 0) {
                //get the actual transparent color
                $rgb = imagecolorsforindex($img, $original_transparency);
                $original_transparency
                    = ($rgb['red'] << 16) | ($rgb['green'] << 8) | $rgb['blue'];
                //change the transparent color to black, since transparent goes to black anyways (no way to remove transparency in GIF)
                imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));
            }

            //create truecolor image and transfer
            $truecolor = imagecreatetruecolor($w, $h);
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagecopy($truecolor, $img, 0, 0, 0, 0, $w, $h);
            imagedestroy($img);

            $img = $truecolor;
            //remake transparency (if there was transparency)

            if ($original_transparency >= 0) {
                imagealphablending($img, false);
                imagesavealpha($img, true);

                for ($x = 0; $x < $w; $x++) {
                    for ($y = 0; $y < $h; $y++) {
                        if (
                            imagecolorat($img, $x, $y)
                            == $original_transparency
                        ) {
                            imagesetpixel($img, $x, $y, 127 << 24);
                        }
                    }
                }
            }
        }
    }

    /**
     * Enter description here...
     *
     * @param string $original
     * @param string $new
     * @param array $params - array(
     *                       'background' => '#FFFFFF',
     *                       'radius'     => 10
     *                       )
     * @throws \QUI\Exception
     */
    public static function roundCorner($original, $new, $params)
    {
        $radius = 10;
        $bgcolor = '#000000';

        if (isset($params['background'])) {
            $bgcolor = $params['background'];
        }

        if (isset($params['radius'])) {
            $radius = (int)$params['radius'];
        }

        $info = \QUI\Utils\System\File::getInfo($original);

        // bottomright
        $width = $info['width'];
        $height = $info['height'];

        // PHP ImageMagick
        $_tmp = explode('.', $original);
        $_micro = str_replace([' ', '.'], '', microtime());

        $tmp_image = str_replace('.' . end($_tmp), $_micro . '.png', $original);

        if (IMAGE_LIBRARY == 'IMAGICK_PHP') { // Image Magick
            try {
                $Im = new \Imagick($original);
                $Im->setimagebackgroundcolor(new \ImagickPixel($bgcolor));
                $Im->roundCorners($radius, $radius);
                $Im->writeImage($tmp_image);

                $Bg = new \Imagick();
                $Bg->newImage($width, $height, new \ImagickPixel($bgcolor));
                $Bg->compositeImage($Im, imagick::COMPOSITE_OVER, 0, 0);
                $Bg->writeImage($new);

                $Bg->destroy();
                $Im->destroy();

                unlink($tmp_image);
            } catch (\ImagickPixelException $e) {
                throw new \QUI\Exception(
                    $e->getMessage(),
                    $e->getCode()
                );
            }
        } elseif (IMAGE_LIBRARY == 'IMAGICK_SYSTEM') { // Image Magick - Console
            $exec = 'convert ' . $original . '
                \( +clone  -threshold -1   -draw
                    \'fill black polygon 0,0 0,' . $radius . ' ' . $radius
                . ',0 fill white circle ' . $radius . ',' . $radius . ' ' . $radius . ',0\'
                    \( +clone -flip \) -compose Multiply -composite
                    \( +clone -flop \) -compose Multiply -composite
                \) +matte -compose CopyOpacity -composite ' . $tmp_image;

            $exec = str_replace(
                ["\n", "\r", "\t"],
                ['', '', ' '],
                $exec
            );
            exec($exec, $return);

            $exec = 'convert -fill "' . $bgcolor . '" -opaque none ' . $tmp_image . ' '
                . $new;
            exec($exec, $return);

            unlink($tmp_image);
        } elseif (IMAGE_LIBRARY == 'GDLIB') {
            // not supported at the Moment
        }
    }

    /**
     * Spiegeleffekt für Bilder
     *
     * @param string $from
     * @param string $to
     * @param array $params
     *
     * @throws \QUI\Exception
     */
    public static function reflection($from, $to, $params = [])
    {
        if (!file_exists($from)) {
            throw new \QUI\Exception('Originalbild existiert nicht: ' . $from);
        }

        // default
        if (!isset($params['shadow'])) {
            $params['shadow'] = 0.5;
        }

        if (IMAGE_LIBRARY == 'IMAGICK_PHP') { // Image Magick
            // PNG draus machen
            $Im = new \Imagick($from);
            $width = $Im->getImageWidth();
            $height = $Im->getImageHeight();

            $Trans = new \Imagick(PT_PATH . 'types/trans.png');
            $Trans->newImage($width, $height, new \ImagickPixel('none'), "png");
            $Trans->compositeImage($Im, \Imagick::COMPOSITE_SRCOVER, 0, 0);
            $Trans->writeImage($to);

            $from = $to;

            $Im = new \Imagick($from);
            //$Im->setImageFormat('png');
            //$Im->thumbnailImage($Im->getImageWidth(), null);


            $Reflection = $Im->clone();
            $Reflection->flipImage();

            $Gradient = new \Imagick();
            $Gradient->newPseudoImage(
                $Reflection->getImageWidth(),
                $Reflection->getImageHeight() * $params['shadow'],
                "gradient:transparent-black"
            );

            $Reflection->compositeImage(
                $Gradient,
                \Imagick::COMPOSITE_DSTOUT,
                0,
                0
            );

            $Gradient->newPseudoImage(
                $Reflection->getImageWidth() + 10,
                $Reflection->getImageHeight() * 0.5,
                "gradient:black"
            );

            $Reflection->compositeImage(
                $Gradient,
                imagick::COMPOSITE_DSTOUT,
                0,
                $Reflection->getImageHeight() * 0.5
            );

            //$Reflection->compositeImage($Gradient, imagick::COMPOSITE_OVER, 0, 0);
            //$Reflection->setImageOpacity(0.3);

            $Canvas = new Imagick();

            $width = $Im->getImageWidth() * 1.5;
            $height = $Im->getImageHeight() * 1.5;

            $Canvas->newImage($width, $height, new ImagickPixel('none'), "png");

            $Canvas->compositeImage($Im, imagick::COMPOSITE_SRCOVER, 0, 0);
            $Canvas->compositeImage(
                $Reflection,
                imagick::COMPOSITE_SRCOVER,
                0,
                $Im->getImageHeight()
            );

            //$Canvas->compositeImage($Im, imagick::COMPOSITE_OVER, 0, 0);
            //$Canvas->compositeImage($Reflection, imagick::COMPOSITE_OVER, 0, $Im->getImageHeight() + 0);

            $Canvas->setImageDepth(8);
            $Canvas->writeImage($to);


            $Im->destroy();
            $Trans->destroy();
            $Gradient->destroy();
            $Canvas->destroy();
        }
    }
}
