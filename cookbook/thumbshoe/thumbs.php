<?php if (!defined('PmWiki')) exit();

/*
 * Code for creating thumbnails.
 * Note that this will not check for updates;
 * it will only create a thumbnail if a thumbnail doesn't exist.
 */

SDV($UploadDir,'uploads');

function
ThumbshoeMakeThumb($pagename,$picpath,$w=128,$h=128) {
    global $ThumbshoeThumbBg, $ThumbshoeThumbPrefix;
    global $UploadDir, $UploadPrefixFmt;

    $uploaddir = FmtPageName("$UploadDir$UploadPrefixFmt", $pagename);
    $name = FmtPageName('{$Name}', $pagename);

    $thumbpath = "$uploaddir/${ThumbshoeThumbPrefix}${name}.png";
    if (file_exists($thumbpath) || !file_exists($picpath))
    {
        return;
    }
    if (!file_exists($uploaddir))
    {
        mkdirp($uploaddir);
    }

    $bg = $ThumbshoeThumbBg;
    $tmp1 = "$uploaddir/${name}_tmp.png";
    $area = $w * $h;
    
    # Need to use the following conversion instead because of
    # ImageMagick version earlier than 6.3
    $cmdfmt = 'convert -thumbnail \'%dx%d>\' -bordercolor %s -background %s -border 50 -gravity center  -crop %dx%d+0+0 +repage %s %s';
    $cl = sprintf($cmdfmt, $w, $h, $bg, $bg, $w, $h, $picpath, $tmp1);

    $r = exec($cl, $o, $status);
    if(intval($status)!=0)
    {
        Abort("convert returned <pre>$r\n".print_r($o, true)
              ."'</pre> with a status '$status'.<br/> Command line was '$cl'.");
    }
    if (!file_exists($tmp1))
    {
        Abort("Failed to create '$tmp1';<br/> Command line was '$cl'.");
    }

    // fluff
    $cmdfmt = 'convert -mattecolor %s -frame 6x6+3+0 %s %s';

    $cl = sprintf($cmdfmt, $bg, $tmp1, $thumbpath);
    $r = exec($cl, $o, $status);
    if(intval($status)!=0)
    {
        Abort("convert returned <pre>$r\n".print_r($o, true)
              ."'</pre> with a status '$status'.<br/> Command line was '$cl'.");
    }
    unlink($tmp1);
} # ThumbshoeMakeThumb
