<?php if (!defined('PmWiki')) exit();

/*
 * Code for the PageStore
 * This requires ImageMagick or exiftool to read the meta-data
 * of the image.
 */

class ThumbshoePageStore extends PageStore {
    var $galleryGroup;
    var $picDir;
    var $picDirUrl;
    var $imgRx;
    var $IMFormat;
    var $hidemeta;
    function ThumbshoePageStore($galleryGroup,$picDir,$picUrl,$hidemeta=false) { 
        global $ThumbshoeFields, $ThumbshoeImgExt;
        $this->galleryGroup = $galleryGroup;
        $this->picDir = $picDir;
        $this->picDirUrl = $picUrl;
        $this->hidemeta = $hidemeta;

        // see if we can find ImageMagick
        $sout = shell_exec('convert -version');
        if ( strpos($sout,'ImageMagick') === FALSE ) Abort('?no ImageMagick convert command found');
        $this->imgRx = '(' . implode('|', $ThumbshoeImgExt) . ')';

        $format = '';
        foreach ($ThumbshoeFields as $fn=>$fmt) {
            if ($hidemeta)
            {
                $format .= '(:' . $fn . ':' . $fmt . ':)\\n';
            }
            else
            {
                $format .= ':' . $fn . ':' . $fmt . '\\n';
            }
        }
        $this->IMFormat = $format;
    }
    function pagefile($pagename) {
        if( $pagename=="" ) return "";
        $pagename = str_replace('/', '.', $pagename);

        $name = FmtPageName('{$Name}', $pagename);
        if (preg_match('/(.*)_' . $this->imgRx . '$/i', $name, $m))
        {
            $filename = $m[1] . '.' . $m[2];
            $fullname = $this->picDir . '/' . $filename;
            if (file_exists($fullname))
            {
                return $fullname;
            }
            else
            {
                $filename = preg_replace('/^(.)/e', "strtolower('$1')", $filename);
                $fullname = $this->picDir . '/' . $filename;
                if (file_exists($fullname))
                {
                    return $fullname;
                }
            }
        }
        return '';
    }
    function read($pagename, $since=0) {
        if( $pagename=="" ) return "";
        global $ThumbshoeThumbPrefix;
        if (preg_match('/' . $this->imgRx . '$/i', $pagename))
        {
            $pagefile = $this->pagefile($pagename);
            if ($pagefile)
            {
                $name = FmtPageName('{$Name}', $pagename);
                $basename = basename($pagefile);
                $thumbfile = $ThumbshoeThumbPrefix . $name . ".png";
                $sout = shell_exec("identify -format '" . $this->IMFormat . "' $pagefile");
                if ($this->hidemeta)
                {
                    $text = preg_replace('/^\(:\w+::\)$/m', '', $sout);
                    $text = "(:ThumbFile:" . $thumbfile . ":)\n" . $text;
                    $text = "(:ImageUrl:" . $this->picDirUrl . "/" . $basename . ":)\n" . $text;
                }
                else
                {
                    $text = preg_replace('/^:\w+:$/m', '', $sout);
                    $text = ":ThumbFile:" . $thumbfile . "\n" . $text;
                    $text = ":ImageUrl:" . $this->picDirUrl . "/" . $basename . "\n" . $text;
                }
                $page['name'] = $pagename;
                $page['text'] = $text;

                // Create the thumbnail if needed
                ThumbshoeMakeThumb($pagename,$pagefile);

                return $page;
            }
        }
        return;
    }
    function ls($pats=NULL) {
        global $GroupPattern, $NamePattern;
        global $ThumbshoeThumbPrefix;
        StopWatch("ThumbshoePageStore::ls begin {$this->picDir}");
        $pats=(array)$pats; 
        array_push($pats, "/$this->ImgRx$/");
        $dir = $this->picDir;
        $out = array();
        $o = array();
        $dfp = @opendir($dir);
        if ($dfp)
        {
            while ( ($pagefile = readdir($dfp)) !== false) {
                if ($pagefile{0} == '.') continue;
                if (is_dir("$dir/$pagefile")) continue;
                if (preg_match("/^$ThumbshoeThumbPrefix/", $pagefile)) continue;
                $pn = str_replace('.', '_', $pagefile);
                $pn = MakePageName($this->galleryGroup . '.' . $this->galleryGroup,
                                   $pn);
                $o[] = $pn;
            }
            closedir($dfp);
        }
        StopWatch("ThumbshoePageStore::ls merge {$this->picDir}");
        $out = array_merge($out, MatchPageNames($o, $pats));
        StopWatch("ThumbshoePageStore::ls end {$this->picDir}");
        return $out;
    }
} // ThumbshoePageStore
