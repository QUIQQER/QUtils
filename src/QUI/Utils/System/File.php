<?php

/**
 * This file contains QUI\Utils\System\File
 */

namespace QUI\Utils\System;

use Exception;
use QUI;

use function array_change_key_case;
use function array_merge;
use function basename;
use function closedir;
use function copy;
use function count;
use function dirname;
use function explode;
use function fclose;
use function feof;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function filesize;
use function finfo_close;
use function finfo_file;
use function finfo_open;
use function flush;
use function fnmatch;
use function fopen;
use function fread;
use function fseek;
use function function_exists;
use function fwrite;
use function get_headers;
use function getimagesize;
use function gmdate;
use function header;
use function ini_get;
use function intval;
use function is_dir;
use function is_file;
use function is_string;
use function is_writable;
use function ksort;
use function mb_substr;
use function microtime;
use function mime_content_type;
use function mkdir;
use function opendir;
use function pathinfo;
use function readdir;
use function readfile;
use function realpath;
use function rename;
use function rmdir;
use function round;
use function scandir;
use function set_time_limit;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strrchr;
use function strrpos;
use function strtolower;
use function substr;
use function trim;
use function unlink;
use function usleep;

/**
 * File Object
 * Contains methods for file operations
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @author  www.pcsg.de (Moritz Leutz)
 */
class File
{
    /**
     * @var array
     */
    protected array $files;

    /**
     * @var string
     */
    protected string $start_folder;

    /**
     * Return an array with all available mime types and their endings
     *
     * @return array
     */
    public static function getMimeTypes(): array
    {
        return [
            '.3dmf' => 'x-world/x-3dmf',
            '.a' => 'application/octet-stream',
            '.aab' => 'application/x-authorware-bin',
            '.aam' => 'application/x-authorware-map',
            '.aas' => 'application/x-authorware-seg',
            '.abc' => 'text/vnd.abc',
            '.acgi' => 'text/html',
            '.afl' => 'video/animaflex',
            '.ai' => 'application/postscript',
            '.aif' => 'audio/aiff',
            '.aifc' => 'audio/aiff',
            '.aiff' => 'audio/aiff',
            '.aim' => 'application/x-aim',
            '.aip' => 'text/x-audiosoft-intra',
            '.ani' => 'application/x-navi-animation',
            '.aos' => 'application/x-nokia-9000-communicator-add-on-software',
            '.aps' => 'application/mime',
            '.arc' => 'application/octet-stream',
            '.arj' => 'application/arj',
            '.art' => 'image/x-jg',
            '.asf' => 'video/x-ms-asf',
            '.asm' => 'text/x-asm',
            '.asp' => 'text/asp',
            '.asx' => 'video/x-ms-asf',
            '.au' => 'audio/x-au',
            '.avi' => 'video/avi',
            '.avs' => 'video/avs-video',
            '.bcpio' => 'application/x-bcpio',
            '.bin' => 'application/x-binary',
            '.bmp' => 'image/bmp',
            '.bm' => 'image/bmp',
            '.boo' => 'application/book',
            '.book' => 'application/book',
            '.boz' => 'application/x-bzip2',
            '.bsh' => 'application/x-bsh',
            '.bz' => 'application/x-bzip',
            '.bz2' => 'application/x-bzip2',
            '.c' => 'text/plain',
            '.c++' => 'text/plain',
            '.cat' => 'application/vnd.ms-pki.seccat',
            '.cc' => 'text/plain',
            '.ccad' => 'application/clariscad',
            '.cco' => 'application/x-cocoa',
            '.cdf' => 'application/cdf',
            '.cer' => 'application/pkix-cert',
            '.cha' => 'application/x-chat',
            '.chat' => 'application/x-chat',
            '.class' => 'application/java',
            '.com' => 'text/plain',
            '.conf' => 'text/plain',
            '.cpio' => 'application/x-cpio',
            '.cpp' => 'text/x-c',
            '.cpt' => 'application/x-cpt',
            '.crl' => 'application/pkix-crl',
            '.crt' => 'application/pkix-cert',
            '.csh' => 'application/x-csh',
            '.css' => 'text/css',
            '.cxx' => 'text/plain',
            '.dcr' => 'application/x-director',
            '.deepv' => 'application/x-deepv',
            '.def' => 'text/plain',
            '.der' => 'application/x-x509-ca-cert',
            '.dif' => 'video/x-dv',
            '.dir' => 'application/x-director',
            '.dl' => 'video/dl',
            '.doc' => 'application/msword',
            '.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            '.dot' => 'application/msword',
            '.dp' => 'application/commonground',
            '.drw' => 'application/drafting',
            '.dump' => 'application/octet-stream',
            '.dv' => 'video/x-dv',
            '.dvi' => 'application/x-dvi',
            '.dwf' => 'drawing/x-dwf',
            '.dwg' => 'image/x-dwg',
            '.dxf' => 'image/x-dwg',
            '.dxr' => 'application/x-director',
            '.el' => 'text/x-script.elisp',
            '.elc' => 'application/x-elc',
            '.env' => 'application/x-envoy',
            '.eps' => 'application/postscript',
            '.es' => 'application/x-esrehber',
            '.etx' => 'text/x-setext',
            '.evy' => 'application/envoy',
            '.exe' => 'application/octet-stream',
            '.f' => 'text/plain',
            '.f77' => 'text/x-fortran',
            '.f90' => 'text/x-fortran',
            '.fdf' => 'application/vnd.fdf',
            '.fif' => 'image/fif',
            '.fli' => 'video/fli',
            '.flo' => 'image/florian',
            '.flx' => 'text/vnd.fmi.flexstor',
            '.fmf' => 'video/x-atomic3d-feature',
            '.for' => 'text/x-fortran',
            '.fpx' => 'image/vnd.fpx',
            '.frl' => 'application/freeloader',
            '.funk' => 'audio/make',
            '.g' => 'text/plain',
            '.g3' => 'image/g3fax',
            '.gif' => 'image/gif',
            '.gl' => 'video/gl',
            '.gsd' => 'audio/x-gsm',
            '.gsm' => 'audio/x-gsm',
            '.gsp' => 'application/x-gsp',
            '.gss' => 'application/x-gss',
            '.gtar' => 'application/x-gtar',
            '.gz' => 'application/x-gzip',
            '.gzip' => 'application/x-gzip',
            '.h' => 'text/plain',
            '.hdf' => 'application/x-hdf',
            '.help' => 'application/x-helpfile',
            '.hgl' => 'application/vnd.hp-hpgl',
            '.hh' => 'text/plain',
            '.hlb' => 'text/x-script',
            '.hlp' => 'application/hlp',
            '.hpg' => 'application/vnd.hp-hpgl',
            '.hpgl' => 'application/vnd.hp-hpgl',
            '.hqx' => 'application/binhex',
            '.hta' => 'application/hta',
            '.htc' => 'text/x-component',
            '.htm' => 'text/html',
            '.html' => 'text/html',
            '.htmls' => 'text/html',
            '.htt' => 'text/webviewhtml',
            '.htx' => 'text/html',
            '.ice' => 'x-conference/x-cooltalk',
            '.ico' => 'image/x-icon',
            '.idc' => 'text/plain',
            '.ief' => 'image/ief',
            '.iefs' => 'image/ief',
            '.iges' => 'application/iges',
            '.igs' => 'application/iges',
            '.ima' => 'application/x-ima',
            '.imap' => 'application/x-httpd-imap',
            '.inf' => 'application/inf',
            '.ins' => 'application/x-internett-signup',
            '.ip' => 'application/x-ip2',
            '.isu' => 'video/x-isvideo',
            '.it' => 'audio/it',
            '.iv' => 'application/x-inventor',
            '.ivr' => 'i-world/i-vrml',
            '.ivy' => 'application/x-livescreen',
            '.jam' => 'audio/x-jam',
            '.jav' => 'text/plain',
            '.java' => 'text/plain',
            '.jcm' => 'application/x-java-commerce',
            '.jpg' => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.jfif' => 'image/jpeg',
            '.jfif-tbnl' => 'image/jpeg',
            '.jps' => 'image/x-jps',
            '.js' => 'application/x-javascript',
            '.jut' => 'image/jutvision',
            '.kar' => 'audio/midi',
            '.ksh' => 'application/x-ksh',
            '.la' => 'audio/nspaudio',
            '.lam' => 'audio/x-liveaudio',
            '.latex' => 'application/x-latex',
            '.lha' => 'application/lha',
            '.lhx' => 'application/octet-stream',
            '.list' => 'text/plain',
            '.lma' => 'audio/nspaudio',
            '.log' => 'text/plain',
            '.lsp' => 'application/x-lisp',
            '.lst' => 'text/plain',
            '.lsx' => 'text/x-la-asf',
            '.ltx' => 'application/x-latex',
            '.lzh' => 'application/x-lzh',
            '.lzx' => 'application/x-lzx',
            '.m' => 'text/plain',
            '.m1v' => 'video/mpeg',
            '.m2a' => 'audio/mpeg',
            '.m2v' => 'video/mpeg',
            '.m3u' => 'audio/x-mpequrl',
            '.man' => 'application/x-troff-man',
            '.map' => 'application/x-navimap',
            '.mar' => 'text/plain',
            '.mbd' => 'application/mbedlet',
            '.mcd' => 'application/mcad',
            '.mcf' => 'image/vasa',
            '.mcp' => 'application/netmc',
            '.me' => 'application/x-troff-me',
            '.mht' => 'message/rfc822',
            '.mhtml' => 'message/rfc822',
            '.mid' => 'audio/midi',
            '.midi' => 'audio/midi',
            '.mif' => 'application/x-mif',
            '.mime' => 'www/mime',
            '.mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
            '.mjpg' => 'video/x-motion-jpeg',
            '.mm' => 'application/x-meme',
            '.mme' => 'application/b|ase64',
            '.mod' => 'audio/mod',
            '.moov' => 'video/quicktime',
            '.mov' => 'video/quicktime',
            '.movie' => 'video/x-sgi-movie',
            '.mp2' => 'video/mpeg',
            '.mp3' => 'audio/mpeg3',
            '.mpa' => 'video/mpeg',
            '.mpc' => 'application/x-project',
            '.mpeg' => 'video/mpeg',
            '.mpe' => 'video/mpeg',
            '.mpg' => 'video/mpeg',
            '.mpga' => 'audio/mpeg',
            '.mpp' => 'application/vnd.ms-project',
            '.mpt' => 'application/x-project',
            '.mpv' => 'application/x-project',
            '.mpx' => 'application/x-project',
            '.mrc' => 'application/marc',
            '.ms' => 'application/x-troff-ms',
            '.mv' => 'video/x-sgi-movie',
            '.my' => 'audio/make',
            '.mzz' => 'application/x-vnd.audioexplosion.mzz',
            '.nap' => 'image/naplps',
            '.naplps' => 'image/naplps',
            '.nc' => 'application/x-netcdf',
            '.ncm' => 'application/vnd.nokia.configuration-message',
            '.nif' => 'image/x-niff',
            '.niff' => 'image/x-niff',
            '.nix' => 'application/x-mix-transfer',
            '.nsc' => 'application/x-conference',
            '.nvd' => 'application/x-navidoc',
            '.o' => 'application/octet-stream',
            '.oda' => 'application/oda',
            '.odt' => 'application/vnd.oasis.opendocument.text',
            '.omc' => 'application/x-omc',
            '.omcd' => 'application/x-omcdatamaker',
            '.omcr' => 'application/x-omcregerator',
            '.p' => 'text/x-pascal',
            '.p10' => 'application/x-pkcs10',
            '.p12' => 'application/x-pkcs12',
            '.p7a' => 'application/x-pkcs7-signature',
            '.p7c' => 'application/x-pkcs7-mime',
            '.p7m' => 'application/x-pkcs7-mime',
            '.p7r' => 'application/x-pkcs7-certreqresp',
            '.p7s' => 'application/pkcs7-signature',
            '.part' => 'application/pro',
            '.pas' => 'text/pascal',
            '.pbm' => 'image/x-portable-bitmap',
            '.pcl' => 'application/x-pcl',
            '.pct' => 'image/x-pict',
            '.pcx' => 'image/x-pcx',
            '.pdb' => 'chemical/x-pdb',
            '.pdf' => 'application/pdf',
            '.pfunk' => 'audio/make',
            '.pgm' => 'image/x-portable-graymap',
            '.pic' => 'image/pict',
            '.pict' => 'image/pict',
            '.pkg' => 'application/x-newton-compatible-pkg',
            '.pko' => 'application/vnd.ms-pki.pko',
            '.pl' => 'text/plain',
            '.plx' => 'application/x-pixclscript',
            '.pm' => 'image/x-xpixmap',
            '.pm4' => 'application/x-pagemaker',
            '.pm5' => 'application/x-pagemaker',
            '.png' => 'image/png',
            '.pnm' => 'application/x-portable-anymap',
            '.pot' => 'application/vnd.ms-powerpoint',
            '.pov' => 'model/x-pov',
            '.ppa' => 'application/vnd.ms-powerpoint',
            '.ppm' => 'image/x-portable-pixmap',
            '.pps' => 'application/mspowerpoint',
            '.ppt' => 'application/powerpoint',
            '.ppz' => 'application/mspowerpoint',
            '.pre' => 'application/x-freelance',
            '.prt' => 'application/pro',
            '.ps' => 'application/postscript',
            '.psd' => 'application/octet-stream',
            '.pvu' => 'paleovu/x-pv',
            '.pwz' => 'application/vnd.ms-powerpoint',
            '.py' => 'text/x-script.phyton',
            '.pyc' => 'applicaiton/x-bytecode.python',
            '.qcp' => 'audio/vnd.qcelp',
            '.qd3' => 'x-world/x-3dmf',
            '.qd3d' => 'x-world/x-3dmf',
            '.qif' => 'image/x-quicktime',
            '.qt' => 'video/quicktime',
            '.qtc' => 'video/x-qtc',
            '.qti' => 'image/x-quicktime',
            '.qtif' => 'image/x-quicktime',
            '.ra' => 'audio/x-realaudio',
            '.ram' => 'audio/x-pn-realaudio',
            '.ras' => 'image/cmu-raster',
            '.rast' => 'image/cmu-raster',
            '.rexx' => 'text/x-script.rexx',
            '.rf' => 'image/vnd.rn-realflash',
            '.rgb' => 'image/x-rgb',
            '.rm' => 'audio/x-pn-realaudio',
            '.rmi' => 'audio/mid',
            '.rmm' => 'audio/x-pn-realaudio',
            '.rmp' => 'audio/x-pn-realaudio',
            '.rng' => 'application/ringing-tones',
            '.rnx' => 'application/vnd.rn-realplayer',
            '.roff' => 'application/x-troff',
            '.rp' => 'image/vnd.rn-realpix',
            '.rpm' => 'audio/x-pn-realaudio-plugin',
            '.rt' => 'text/richtext',
            '.rtf' => 'application/rtf',
            '.rtx' => 'application/rtf',
            '.rv' => 'video/vnd.rn-realvideo',
            '.s' => 'text/x-asm',
            '.s3m' => 'audio/s3m',
            '.saveme' => 'application/octet-stream',
            '.sbk' => 'application/x-tbook',
            '.scm' => 'video/x-scm',
            '.sdml' => 'text/plain',
            '.sdp' => 'application/sdp',
            '.sdr' => 'application/sounder',
            '.sea' => 'application/sea',
            '.set' => 'application/set',
            '.sgm' => 'text/sgml',
            '.sgml' => 'text/sgml',
            '.sh' => 'application/x-sh',
            '.shar' => 'application/x-shar',
            '.shtml' => 'text/html',
            '.sid' => 'audio/x-psid',
            '.sit' => 'application/x-sit',
            '.skd' => 'application/x-koan',
            '.skm' => 'application/x-koan',
            '.skp' => 'application/x-koan',
            '.skt' => 'application/x-koan',
            '.sl' => 'application/x-seelogo',
            '.smi' => 'application/smil',
            '.smil' => 'application/smil',
            '.snd' => 'audio/basic',
            '.sol' => 'application/solids',
            '.spc' => 'text/x-speech',
            '.spl' => 'application/futuresplash',
            '.spr' => 'application/x-sprite',
            '.sprite' => 'application/x-sprite',
            '.src' => 'application/x-wais-source',
            '.ssi' => 'text/x-server-parsed-html',
            '.ssm' => 'application/streamingmedia',
            '.sst' => 'application/vnd.ms-pki.certstore',
            '.step' => 'application/step',
            '.stl' => 'application/sla',
            '.stp' => 'application/step',
            '.sv4cpio' => 'application/x-sv4cpio',
            '.sv4crc' => 'application/x-sv4crc',
            '.svf' => 'image/x-dwg',
            '.swf' => 'application/x-shockwave-flash',
            '.t' => 'application/x-troff',
            '.talk' => 'text/x-speech',
            '.tar' => 'application/x-tar',
            '.tbk' => 'application/x-tbook',
            '.tcl' => 'application/x-tcl',
            '.tcsh' => 'text/x-script.tcsh',
            '.tex' => 'application/x-tex',
            '.texi' => 'application/x-texinfo',
            '.texinfo' => 'application/x-texinfo',
            '.text' => 'text/plain',
            '.tgz' => 'application/x-compressed',
            '.tif' => 'image/tiff',
            '.tiff' => 'image/tiff',
            '.tr' => 'application/x-troff',
            '.tsi' => 'audio/tsp-audio',
            '.tsp' => 'application/dsptype',
            '.tsv' => 'text/tab-separated-values',
            '.turbot' => 'image/florian',
            '.txt' => 'text/plain',
            '.uil' => 'text/x-uil',
            '.uni' => 'text/uri-list',
            '.unis' => 'text/uri-list',
            '.unv' => 'application/i-deas',
            '.uri' => 'text/uri-list',
            '.uris' => 'text/uri-list',
            '.ustar' => 'application/x-ustar',
            '.uu' => 'application/octet-stream',
            '.uue' => 'text/x-uuencode',
            '.vcd' => 'application/x-cdlink',
            '.vcs' => 'text/x-vcalendar',
            '.vda' => 'application/vda',
            '.vdo' => 'video/vdo',
            '.vew' => 'application/groupwise',
            '.viv' => 'video/vivo',
            '.vivo' => 'video/vivo',
            '.vmd' => 'application/vocaltec-media-desc',
            '.vmf' => 'application/vocaltec-media-file',
            '.voc' => 'audio/voc',
            '.vos' => 'video/vosaic',
            '.vox' => 'audio/voxware',
            '.vqe' => 'audio/x-twinvq-plugin',
            '.vqf' => 'audio/x-twinvq',
            '.vql' => 'audio/x-twinvq-plugin',
            '.vrml' => 'application/x-vrml',
            '.vrt' => 'x-world/x-vrt',
            '.vsd' => 'application/x-visio',
            '.vst' => 'application/x-visio',
            '.vsw' => 'application/x-visio',
            '.w60' => 'application/wordperfect6.0',
            '.w61' => 'application/wordperfect6.1',
            '.w6w' => 'application/msword',
            '.wav' => 'audio/wav',
            '.wb1' => 'application/x-qpro',
            '.wbmp' => 'image/vnd.wap.wbmp',
            '.web' => 'application/vnd.xara',
            '.wiz' => 'application/msword',
            '.wk1' => 'application/x-123',
            '.wmf' => 'windows/metafile',
            '.wml' => 'text/vnd.wap.wml',
            '.wmlc' => 'application/vnd.wap.wmlc',
            '.wmls' => 'text/vnd.wap.wmlscript',
            '.wmlsc' => 'application/vnd.wap.wmlscriptc',
            '.word' => 'application/msword',
            '.wp' => 'application/wordperfect',
            '.wp5' => 'application/wordperfect',
            '.wp6' => 'application/wordperfect',
            '.wpd' => 'application/wordperfect',
            '.wq1' => 'application/x-lotus',
            '.wri' => 'application/x-wri',
            '.wrl' => 'application/x-world',
            '.wrz' => 'model/vrml',
            '.wsc' => 'text/scriplet',
            '.wsrc' => 'application/x-wais-source',
            '.wtk' => 'application/x-wintalk',
            '.xbm' => 'image/xbm',
            '.xdr' => 'video/x-amt-demorun',
            '.xgz' => 'xgl/drawing',
            '.xif' => 'image/vnd.xiff',
            '.xl' => 'application/excel',
            '.xla' => 'application/excel',
            '.xlb' => 'application/excel',
            '.xlc' => 'application/excel',
            '.xld' => 'application/excel',
            '.xlk' => 'application/excel',
            '.xll' => 'application/excel',
            '.xlm' => 'application/excel',
            '.xls' => 'application/excel',
            '.xlt' => 'application/excel',
            '.xlv' => 'application/excel',
            '.xlw' => 'application/excel',
            '.xm' => 'audio/xm',
            '.xml' => 'text/xml',
            '.xmz' => 'xgl/movie',
            '.xpix' => 'application/x-vnd.ls-xpix',
            '.xpm' => 'image/xpm',
            '.x-png' => 'image/png',
            '.xsr' => 'video/x-amt-showrun',
            '.xwd' => 'image/x-xwd',
            '.xyz' => 'chemical/x-pdb',
            '.z' => 'application/x-compressed',
            '.zip' => 'application/x-zip-compressed',
            '.zoo' => 'application/octet-stream',
            '.zsh' => 'text/x-script.zsh'
        ];
    }

    /**
     * Formats the size of a file into a readable output format and append the ending
     *
     * @param integer $size - number in bytes
     * @param integer $round
     *
     * @return string
     */
    public static function formatSize(int $size, int $round = 0): string
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0, $len = count($sizes); $i < $len - 1 && $size >= 1024; $i++) {
            $size /= 1024;
        }

        return round($size, $round) . ' ' . $sizes[$i];
    }

    /**
     * Returns the Bytes of a php ini value
     *
     * @param int|string $val - 129M
     *
     * @return integer
     */
    public static function getBytes(int | string $val): int
    {
        $last = '';

        if (is_string($val)) {
            $val = trim($val);
            $last = strtolower(mb_substr($val, -1));
        }

        $val = (int)$val;

        switch ($last) {
            case 'g':
                $val *= 1024;
            // go on
            case 'm':
                $val *= 1024;
            // go on
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * Deletes a file or an entire folder
     * Only for QUIQQER use
     *
     * the unlink method unlink the file not really
     * it makes move to the tmp folder, because a move is faster
     *
     * @param string $file - Pfad zur Datei
     *
     * @return boolean
     *
     * @throws QUI\Exception
     */
    public static function unlink(string $file): bool
    {
        if (!file_exists($file)) {
            return true;
        }

        if (!is_dir($file)) {
            return unlink($file);
        }

        if (!defined('VAR_DIR')) {
            return false;
        }

        // create a var_dir temp folder
        $var_folder = VAR_DIR . 'tmp/' . str_replace([' ', '.'], '', microtime());

        while (file_exists($var_folder)) {
            $var_folder = VAR_DIR . 'tmp/' . str_replace([' ', '.'], '', microtime());
        }

        // move to var dir, its faster
        return self::move($file, $var_folder);
    }

    /**
     * Move a file
     *
     * @param string $from - original file
     * @param string $to - target
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public static function move(string $from, string $to): bool
    {
        if (!file_exists($from) || file_exists($to)) {
            throw new QUI\Exception(
                "Can't move File: " . $from . ' -> ' . $to,
                500
            );
        }

        if (is_file($from)) {
            return rename($from, $to);
        }

        // Using rename on directories across filesystems fails with an error
        if (@rename($from, $to)) {
            return true;
        }

        // Fallback: Copy and delete directory - works across filesystems
        $dirCopyErrors = self::dircopy($from, $to);

        if (is_array($dirCopyErrors)) {
            throw new QUI\Exception("Could not copy directory: $from -> $to", 500, $dirCopyErrors);
        }

        return self::deleteDir($from);
    }

    /**
     * Copies a file, overwrite no file!
     * so the target may not exist
     *
     * @param string $from
     * @param string $to
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public static function copy(string $from, string $to): bool
    {
        if (file_exists($to)) {
            throw new QUI\Exception(
                'Can\'t copy File. File exists ' . $to,
                500
            );
        }

        if (!file_exists($from)) {
            throw new QUI\Exception(
                'Can\'t copy File. File not exists ' . $from,
                500
            );
        }

        return copy($from, $to);
    }

    /**
     * Get information about the file
     *
     * @param string $file - Path to file
     * @param array $params - (optional) ->
     *                       filesize=Dateigrösse;
     *                       imagesize=Bildgrösse;
     *                       mime_type=mime_type
     *
     * @return array
     * @throws QUI\Exception
     */
    public static function getInfo(string $file, array $params = []): array
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(
                'QUI\Utils\System\File::getInfo()  File "' . $file
                . '" does not exist',
                500
            );
        }

        $info = [];

        if (isset($params['pathinfo']) || !$params) {
            $p = pathinfo($file);

            $info = [
                'dirname' => false,
                'basename' => false,
                'extension' => false,
                'filename' => false
            ];

            if (isset($p['dirname'])) {
                $info['dirname'] = $p['dirname'];
            }

            if (isset($p['basename'])) {
                $info['basename'] = $p['basename'];
            }

            if (isset($p['extension'])) {
                $info['extension'] = $p['extension'];
            }

            if (isset($p['filename'])) {
                $info['filename'] = $p['filename'];
            }
        }

        if (isset($params['filesize']) || !$params) {
            $info['filesize'] = filesize($file);
        }

        if (isset($params['imagesize']) || !$params) {
            try {
                $r = getimagesize($file);

                if ($r && is_array($r)) {
                    $info['width'] = $r[0];
                    $info['height'] = $r[1];
                }
            } catch (Exception) {
                // ignore if not an image
            }
        }

        if (isset($params['mime_type']) || !$params) {
            if (function_exists('mime_content_type')) { // PHP interne Funktionen
                $info['mime_type'] = mime_content_type($file);
            } elseif (
                function_exists('finfo_open')
                && function_exists('finfo_file')
            ) { // PECL
                $finfo = finfo_open(FILEINFO_MIME);
                $part = explode(';', finfo_file($finfo, $file));
                $info['mime_type'] = $part[0];
            }

            // Falls beides nicht vorhanden ist
            // BAD
            if (empty($info['mime_type'])) {
                $file = strtolower($file);
                $mimtypes = self::getMimeTypes();

                if (isset($mimtypes[strrchr($file, '.')])) {
                    $info['mime_type'] = $mimtypes[strrchr($file, '.')];
                } else {
                    $info['mime_type'] = false;
                }
            }
        }

        return $info;
    }

    /**
     * Return the file ending for a mimetype
     *
     * @param string $mime
     *
     * @return string
     */
    public static function getEndingByMimeType(string $mime): string
    {
        if ($mime === 'text/plain') {
            return '.txt';
        }

        $mimetypes = self::getMimeTypes();

        foreach ($mimetypes as $ending => $mimetype) {
            if ($mimetype == $mime) {
                return $ending;
            }
        }

        return '';
    }

    /**
     * Find files via fnmatch
     *
     * @param string $path
     * @param string $find
     *
     * @return array
     */
    public static function find(string $path, string $find): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $dh = opendir($path);
        $result = [];

        while (($file = readdir($dh)) !== false) {
            if (str_starts_with($file, '.')) {
                continue;
            }

            $rFile = "$path/$file";

            if (is_dir($rFile)) {
                $result = array_merge($result, self::find($rFile, $find));
                continue;
            }

            if (fnmatch($find, $file)) {
                $result[] = $rFile;
            }
        }

        closedir($dh);

        return $result;
    }

    /**
     * Dateien rekursiv aus einem Ordner lesen
     *
     * @param string $folder - Pfad zum Ordner
     * @param boolean $flatten - no assoziativ folder array, return the array as flat array
     *
     * @return array
     */
    public function readDirRecursiv(string $folder, bool $flatten = false): array
    {
        if (!str_ends_with($folder, '/')) {
            $folder .= '/';
        }

        $this->files = [];
        $this->start_folder = $folder;

        $this->readDirRecursiveHelper($folder);

        ksort($this->files);


        if ($flatten) {
            $list = [];

            foreach ($this->files as $dir => $files) {
                foreach ($files as $file) {
                    $list[] = $dir . $file;
                }
            }

            return $list;
        }

        return $this->files;
    }

    /**
     * Helper Methode für readDirRecursive
     *
     * @param string $folder
     */
    private function readDirRecursiveHelper(string $folder): void
    {
        $_f = $this->readDir($folder);
        $_tmp = str_replace($this->start_folder, '', $folder);

        foreach ($_f as $f) {
            if (!str_ends_with($folder, '/')) {
                $folder .= '/';
            }

            $dir = $folder . $f;

            if (is_dir($dir)) {
                $this->readDirRecursiveHelper($dir . '/');
            } else {
                if ($folder == $this->start_folder) {
                    $this->files['/'][] = $f;
                } else {
                    $this->files[$_tmp][] = $f;
                }
            }
        }
    }

    /**
     * Dateien eines Ordners auf dem Filesystem lesen
     *
     * @param string $folder - Ordner welcher ausgelesen werdens oll
     * @param boolean $only_files - Nur Dateien auslesen
     * @param boolean $order_by_date - Nach Daum sortiert zurück geben
     *
     * @return array
     */
    public static function readDir(
        string $folder,
        bool $only_files = false,
        bool $order_by_date = false
    ): array {
        if (!is_dir($folder)) {
            return [];
        }

        $folder = '/' . trim($folder, '/') . '/';

        $handle = opendir($folder);
        $files = [];

        while ($file = readdir($handle)) {
            if ($file == "." || $file == "..") {
                continue;
            }

            if ($only_files) {
                if (is_file($folder . $file) && !$order_by_date) {
                    $files[] = $file;
                }

                if (is_file($folder . $file) && $order_by_date) {
                    $files[filemtime($folder . $file)] = $file;
                }

                continue;
            }

            if ($order_by_date) {
                $files[filemtime($folder . $file)] = $file;
                continue;
            }

            $files[] = $file;
        }

        if ($order_by_date) {
            ksort($files);
        }

        closedir($handle);

        return $files;
    }

    /**
     * Löscht ein Verzeichnis rekursiv
     *
     * @param string $dir - path to dir
     *
     * @return boolean
     *
     * @todo use RecursiveDirectoryIterator
     */
    public static function deleteDir(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            $dirs = self::deleteDir(
                $dir . DIRECTORY_SEPARATOR . $item
            );

            if (!$dirs) {
                return false;
            }
        }

        return rmdir($dir);
    }

    /**
     * Return the size of a folder
     *
     * @param $path
     * @return int
     * @deprecated Use `QUI\Utils\System\Folder::getFolderSize($path)` instead.
     *
     */
    public static function getDirectorySize($path): int
    {
        return Folder::getFolderSize($path, true);
    }

    /**
     * Lädt eine Datei per HTTP herrunter und legt diese an einen bestimmten Ort
     *
     * @param string $host
     * @param string $path
     * @param string $local
     * @param bool $https
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public static function download(
        string $host,
        string $path,
        string $local,
        bool $https = false
    ): bool {
        if (file_exists($local)) {
            throw new QUI\Exception(
                'Conflicting Request; Local File exist;',
                409
            );
        }

        $protocol = 'https://';

        if ($https === false) {
            $protocol = 'http://';
        }

        $content = file_get_contents($protocol . $host . '/' . $path);
        file_put_contents($local, $content);

        if (file_exists($local)) {
            return true;
        }

        throw new QUI\Exception('Could not download the file');
    }

    /**
     * Send a file as download to the browser (maybe limited in speed)
     *
     * @param string $filePath
     * @param integer $rate speedlimit in KB/s
     * @param string|null $downloadFileName (optional)
     *
     * @return void
     * @throws QUI\Exception
     *
     * found on:
     * http://www.phpgangsta.de/dateidownload-via-php-mit-speedlimit-und-resume
     */
    public static function send(string $filePath, int $rate = 0, null | string $downloadFileName = null): void
    {
        // Check if file exists
        if (!is_file($filePath)) {
            throw new QUI\Exception('File not found.'); // #locale
        }

        // get more information about the file
        $filename = basename($filePath);

        if (!empty($downloadFileName)) {
            $filename = $downloadFileName;
        }

        $size = filesize($filePath);
        $fInfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($fInfo, realpath($filePath));

        finfo_close($fInfo);

        // Create file handle
        $fp = fopen($filePath, 'rb');

        $seekStart = 0;
        $seekEnd = $size;

        // Check if only a specific part should be sent
        if (isset($_SERVER['HTTP_RANGE'])) {
            // If so, calculate the range to use
            $range = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
            $seekStart = intval($range[0]);

            if ($range[1] > 0) {
                $seekEnd = intval($range[1]);
            }

            // Seek to the start
            fseek($fp, $seekStart);

            // Set headers incl range info
            header('HTTP/1.1 206 Partial Content');
            header(
                sprintf(
                    'Content-Range: bytes %d-%d/%d',
                    $seekStart,
                    $seekEnd,
                    $size
                )
            );
        } else {
            // Set headers for full file
            header('HTTP/1.1 200 OK');
        }

        // Output some headers
        header('Cache-Control: private');
        header('Content-Type: ' . $mimetype . '; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header("Content-Description: File Transfer");
        header('Content-Length: ' . ($seekEnd - $seekStart));
        header('Accept-Ranges: bytes');
        header(
            'Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath))
            . ' GMT'
        );

        $block = 1024;
        // limit download speed
        if ($rate > 0) {
            $block *= $rate;
        }

        // disable timeout before download starts
        set_time_limit(0);

        // Send file until end is reached
        while (!feof($fp)) {
            $timeStart = microtime(true);
            echo fread($fp, $block);
            flush();
            $wait = (microtime(true) - $timeStart) * 1000000;

            // if speedlimit is defined, make sure to only send specified bytes per second
            if ($rate > 0) {
                usleep((int)(1000000 - $wait));
            }
        }

        // Close handle
        fclose($fp);
    }

    /**
     * Kopiert einen kompletten Ordner mit Unteordner
     *
     * @param string $srcDir
     * @param string $dstDir
     *
     * @return boolean|array
     */
    public static function dircopy(string $srcDir, string $dstDir): bool | array
    {
        QUI\Utils\System\File::mkdir($dstDir);

        if (!str_ends_with($dstDir, '/')) {
            $dstDir = $dstDir . '/';
        }

        if (!str_ends_with($srcDir, '/')) {
            $srcDir = $srcDir . '/';
        }

        $File = new QUI\Utils\System\File();
        $Files = $File->readDirRecursiv($srcDir);
        $errors = [];

        foreach ($Files as $folder => $file) {
            $File->mkdir($dstDir . $folder);

            // files kopieren
            for ($i = 0, $len = count($file); $i < $len; $i++) {
                $from = $srcDir . $folder . $file[$i];
                $to = $dstDir . $folder . $file[$i];

                try {
                    self::copy($from, $to);
                } catch (QUI\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        if (count($errors)) {
            return $errors;
        }

        return true;
    }

    /**
     * Creates a folder
     * It can be given a complete path
     *
     * @param string $path - Path which is to be created
     * @param bool|int $mode - Permissions for the folder
     *
     * @return boolean
     */
    public static function mkdir(string $path, bool | int $mode = false): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
        $return = self::mkdir($prev_path, $mode);

        if ($return && is_writable($prev_path)) {
            if ($mode === false) {
                return mkdir($path);
            }

            return mkdir($path, $mode);
        }

        return false;
    }

    /**
     * Erstellt eine Datei
     *
     * @param string $file - path to the file
     *
     * @return boolean
     */
    public static function mkfile(string $file): bool
    {
        if (file_exists($file)) {
            return true;
        }

        self::mkdir(dirname($file));

        return file_put_contents($file, '');
    }

    /**
     * Returns the content of a file, if file not exist, it returns an empty string
     *
     * @param string $file - path to file
     *
     * @return string
     */
    public static function getFileContent(string $file): string
    {
        if (!file_exists($file)) {
            return '';
        }

        return file_get_contents($file);
    }

    /**
     * Write the $line to the end of the file
     *
     * @param string $file - Datei
     * @param string $line - String welcher geschrieben werden soll
     */
    public static function putLineToFile(string $file, string $line = ''): void
    {
        $fp = fopen($file, 'a');

        fwrite($fp, $line . "\n");
        fclose($fp);
    }

    /**
     * Prüft ob die Datei innerhalb von open_basedir ist
     *
     * @param string $path - Pfad der geprüft werden soll
     *
     * @return boolean
     */
    public static function checkOpenBaseDir(string $path): bool
    {
        $obd = ini_get('open_basedir');

        if (empty($obd)) {
            return true;
        }

        $obd = explode(':', $obd);

        foreach ($obd as $dir) {
            if (str_starts_with($path, $dir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enter description here...
     *
     * @param string $file
     * @param bool $deleteFile (optional) - Delete file after send [default: false]
     *
     * @throws QUI\Exception
     */
    public static function downloadHeader(string $file, bool $deleteFile = false): void
    {
        if (!file_exists($file)) {
            throw new QUI\Exception('File not exist ' . $file, 404);
        }

        $finfo = self::getInfo($file);

        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Content-type: application/' . $finfo['extension']);
        header(
            'Content-Disposition: attachment; filename="' . basename($file)
            . '"'
        );

        // Inhalt des gespeicherten Dokuments senden
        readfile($file);

        if ($deleteFile) {
            unlink($file);
        }

        exit;
    }

    /**
     * Send a header for the file
     *
     * @param string $file - Path to file
     *
     * @throws QUI\Exception
     */
    public static function fileHeader(string $file): void
    {
        if (!file_exists($file) || !is_file($file)) {
            throw new QUI\Exception('File not exist ' . $file, 404);
        }

        $finfo = self::getInfo($file);

        header("Content-Type: " . $finfo['mime_type']);
        header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Accept-Ranges: bytes");
        header(
            "Content-Disposition: inline; filename=\"" . pathinfo($file, PATHINFO_BASENAME) . "\""
        );
        header("Content-Size: " . $finfo['filesize']);
        header("Content-Length: " . $finfo['filesize']);
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Connection: Keep-Alive");

        $fo_file = fopen($file, "r");
        $fr_file = fread($fo_file, filesize($file));
        fclose($fo_file);

        echo $fr_file;
        exit;
    }

    /**
     * FileSize einer Datei bekommen (auch über eine URL)
     *
     * @param string $url
     * @return int|string
     */
    public static function getFileSize(string $url): int | string
    {
        if (str_starts_with($url, 'http')) {
            $x = array_change_key_case(
                get_headers($url, true),
                CASE_LOWER
            );

            if (!isset($x['content-length'])) {
                $x['content-length'] = '0';
            }

            if (strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0) {
                $x = $x['content-length'][1];
            } else {
                $x = $x['content-length'];
            }
        } else {
            $x = @filesize($url);
        }

        return $x;
    }
}
