<?php if (!defined('PmWiki')) exit();

/*
 * Code for the PageStore
 * This requires ImageMagick or exiftool to read the meta-data
 * of the image.
 */

class ThumbShoePageStore extends PageStore {
    var $galleryGroup;
    var $imgRx;
    var $IMFormat;
    var $hidemeta;
    var $cache;
    var $cachefmt;
    function ThumbShoePageStore($galleryGroup,$hidemeta=false) { 
        global $UploadPrefixFmt;
        global $ThumbShoeFields, $ThumbShoeImgExt, $ThumbShoeCacheFmt;
        global $ThumbShoeKeywordsGroup;
        $this->iswrite = true;
        $this->galleryGroup = $galleryGroup;
        $this->hidemeta = $hidemeta;
        $this->cachefmt = $ThumbShoeCacheFmt;

        // see if we can find ImageMagick
        $sout = shell_exec('convert -version');
        if ( strpos($sout,'ImageMagick') === FALSE ) Abort('?no ImageMagick convert command found');
        $this->imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';

        $format = '';
        foreach ($ThumbShoeFields as $fn=>$fmt) {
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

    function pagefile($pagename, $must_exist=true) {
        if( $pagename=="" ) return "";
        $pagename = str_replace('/', '.', $pagename);

        $name = PageVar($pagename, '$Name');
        if (preg_match('/(.*)_' . $this->imgRx . '$/i', $name, $m))
        {
            $filename = PageVar($pagename, '$TSPageImage');
            $dir = PageVar($pagename, '$TSUploadDir');
            $fullname = $dir . '/' . $filename;
            if (file_exists($fullname) || !$must_exist)
            {
                return $fullname;
            }
            else
            {
                $filename = preg_replace('/^(.)/e', "strtolower('$1')", $filename);
                $fullname = $dir . '/' . $filename;
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
        global $ThumbShoeThumbPrefix, $ThumbShoeKeywordsGroup;
        if (preg_match('/' . $this->imgRx . '$/i', $pagename))
        {
            StopWatch("ThumbShoePageStore::read begin $pagename");
            $pagefile = $this->pagefile($pagename);
            if ($pagefile)
            {
                // check the cache first
                $page = $this->get_from_cache($pagename);
                if (!$page)
                {
                    $name = PageVar($pagename, '$Name');
                    $basename = basename($pagefile);
                    $thumbfile = $ThumbShoeThumbPrefix . $name . ".png";
                    $sout = shell_exec("identify -format '" . $this->IMFormat . "' $pagefile");
                    $text = '';
                    if ($this->hidemeta)
                    {
                        $text = preg_replace('/^\(:\w+::\)$/m', '', $sout);
                        $text = "(:ThumbFile:" . $thumbfile . ":)\n" . $text;
                        $text = "(:ImageFile:" . $basename . ":)\n" . $text;
                    }
                    else
                    {
                        $text = preg_replace('/^:\w+:$/m', '', $sout);
                        $text = ":ThumbFile:" . $thumbfile . "\n" . $text;
                        $text = ":ImageFile:" . $basename . "\n" . $text;
                    }
                    // get rid of the extra newlines
                    $text = preg_replace('/\n\n+/', "\n", $text);
                    $text = trim($text);

                    // Find the keywords, if any
                    if (preg_match('/Keywords:(.*)/', $text, $m))
                    {
                        $keywords=$m[1];
                        if ($ThumbShoeKeywordsGroup && $keywords)
                        {
                            $cats = explode(';',$keywords);
                            $catpages = array();
                            foreach((array)$cats as $k)
                            {
                                $tpn = MakePageName(
                                $ThumbShoeKeywordsGroup . '.' . $ThumbShoeKeywordsGroup, $k);
                                $catpages[] = $tpn;
                                StopWatch("ThumbShoePageStore::read targets $tpn");
                            }
                            $page['targets'] = implode(',',$catpages);
                        }
                    }

                    $page['name'] = $pagename;
                    $page['text'] = $text;

                    // Create the thumbnail if needed
                    ThumbShoeMakeThumb($pagename,$pagefile);

                    $this->add_to_cache($pagename, $page);
                    $this->write_cachefile($pagename, $page);
                }
                StopWatch("ThumbShoePageStore::read end $pagename");
                return $page;
            }
        }
        return;
    }
    function delete($pagename) {
        global $Now;
        global $ThumbShoeThumbPrefix;

        $pagefile = $this->pagefile($pagename);
        @rename($pagefile,"$pagefile,del-$Now");

        // also remove the cachefile and the thumbnail
        $cachefile = $this->cachefile($pagename);
        @unlink($cachefile);

        $uploaddir = PageVar($pagename, '$TSUploadDir');
        $name = PageVar($pagename, '$Name');
        $thumbpath = "$uploaddir/${ThumbShoeThumbPrefix}${name}.png";
        @unlink($thumbpath);
    }
    function ls($pats=NULL) {
        global $UploadDir, $UploadPrefixFmt;
        global $GroupPattern, $NamePattern;
        global $ThumbShoeThumbPrefix, $ThumbShoePageSep;
        StopWatch("ThumbShoePageStore::ls begin {$this->galleryGroup}");
        $pats=(array)$pats; 
        $topdir = PageVar($this->galleryGroup . '.' . $this->galleryGroup,
                          '$TSUploadTopDir');

        StopWatch("ThumbShoePageStore::ls topdir=$topdir");
        $out = array();
        $o = array();
        $dfp = @opendir($topdir);
        if ($dfp)
        {
            while ( ($file = readdir($dfp)) !== false) {
                if ($file{0} == '.') continue;
                if (is_dir("$topdir/$file")) {
                    $sub_dfp = @opendir("$topdir/$file");
                    while ( ($subfile = readdir($sub_dfp)) !== false) {
                        if ($subfile{0} == '.') continue;
                        if (is_dir("$topdir/$file/$subfile")) continue;
                        if (!preg_match("/$this->ImgRx$/", $subfile)) continue;
                        if (preg_match("/^$ThumbShoeThumbPrefix/", $subfile)) continue;
                        $pn = str_replace('.', '_', $subfile);
                        $pn = "${file}${ThumbShoePageSep}" . $pn;
                        $pn = MakePageName($this->galleryGroup . '.' . $this->galleryGroup,
                                           $pn);
                        $o[] = $pn;
                        StopWatch("ThumbShoePageStore::ls pn=$pn");
                    }
                    closedir($sub_dfp);
                }
                else
                {
                    if (!preg_match("/$this->ImgRx$/", $file)) continue;
                    if (preg_match("/^$ThumbShoeThumbPrefix/", $file)) continue;
                    $pn = str_replace('.', '_', $file);
                    $pn = MakePageName($this->galleryGroup . '.' . $this->galleryGroup,
                                       $pn);
                    $o[] = $pn;
                    StopWatch("ThumbShoePageStore::ls pn=$pn");
                }
            }
            closedir($dfp);
        }
        StopWatch("ThumbShoePageStore::ls merge {$this->galleryGroup}");
        $out = array_merge($out, MatchPageNames($o, $pats));
        StopWatch("ThumbShoePageStore::ls end {$this->galleryGroup}");
        return $out;
    }

    function rename($pagename,$newpagename) {
        global $ThumbShoeThumbPrefix;

        $newgroup = PageVar($newpagename, '$Group');
        if ($newgroup != $this->galleryGroup)
        {
            Abort("Cannot rename $pagename to $newpagename; groups do not match");
        }

        $pagefile = $this->pagefile($pagename);
        if (preg_match('/\.(\w+)$/',$pagefile,$m1))
        {
            $ext = $m1[1];
        }
        $newpagefile = $this->pagefile($newpagename,false);
        if (!$newpagefile) // probably has no extension
        {
            $newpagename = $newpagename . '_' . $ext;
            $newpagefile = $this->pagefile($newpagename,false);
            if (!$newpagefile) // Huh?
            {
                Abort("Cannot rename $pagename to $newpagename; cannot calculate new filename");
            }
        }
        else if (preg_match('/(.*)\.(\w+)$/',$newpagefile,$m2))
        {
            $newbase = $m2[1];
            $newext = $m2[2];
            if ($ext != $newext) // not allowed to change extensions
            {
                $newpagefile = $newbase . '.' . $ext;
                $newpagename = preg_replace("/$newext$/", $ext, $newpagename);
            }
        }

        @rename($pagefile,$newpagefile);

        // remove the old cachefile and the thumbnail
        $cachefile = $this->cachefile($pagename);
        @unlink($cachefile);

        $uploaddir = PageVar($pagename, '$TSUploadDir');
        $name = PageVar($pagename, '$Name');
        $thumbpath = "$uploaddir/${ThumbShoeThumbPrefix}${name}.png";
        @unlink($thumbpath);
        
        return $newpagename;
    } // rename

    /* ============================================================ */
    function add_to_cache($pagename, $page) {
        global $ThumbShoeCacheDir;
        foreach($page as $k=>$v) 
        {
            $this->cache[$pagename][$k]=$v;
        }
    } // add_to_cache

    function cachefile( $pagename ) {
        $cfmt = $this->cachefmt;
        if ($pagename > '') {
            $pagename = str_replace('/', '.', $pagename);
            ## optimizations for standard locations
            if ( $cfmt == 'thumbshoe.d/{$FullName}' )				return "thumbshoe.d/$pagename";
            if ( $cfmt == 'thumbshoe.d/{$Group}/{$FullName}' )	return preg_replace( '/([^.]+).*/', 'thumbshoe.d/$1/$0', $pagename );
        }
        return FmtPageName( $cfmt, $pagename );
    }

    function write_cachefile( $pagename, &$page ) {
        global $Version;
        $cachefile = $this->cachefile($pagename);
        $dir = dirname($cachefile);
        mkdirp($dir);
        if ( !file_exists("$dir/.htaccess") && ( $fp = @fopen( "$dir/.htaccess", 'w' ) ) ) {
            fwrite( $fp, "Order Deny,Allow\nDeny from all\n" );
            fclose($fp);
        }

        $st = FALSE;
        if ( $cachefile && ( $fp = fopen( "$cachefile,new", 'w' ) ) ) {
            $r0 = array( '%',   "\n",  '<' );
            $r1 = array( '%25', '%0a', '%3c' );
            $x = "version=$Version fmt=thumbshoe\n";
            $st = true && fputs( $fp, $x );
            $tz = strlen($x);

            // uksort( $page, 'CmpPageAttr' );
            foreach( $page as $k => $v ) if (
                ( $k > '' ) && ( $k[0] != '=' ) &&
                ( $k != 'version' ) && ( $k != 'text' ) && ( $k != 'newline' )
                ) {
                    if (strpos( $k, ':' )) break;
                    $x = str_replace( $r0, $r1, "$k=$v" ) . "\n";
                    $st = $st && fputs( $fp, $x );
                    $tz += strlen($x);
                }

            $text = str_replace( $this->r0, $this->r1, $page['text'] );
            $st = $st && fputs( $fp, "\n$text\n" );
            $tz += 2 + strlen($text);

            $st = fclose($fp) && $st;
            $st = $st && ( filesize("$cachefile,new") > $tz * 0.95 );
            if (file_exists( $cachefile )) $st = $st && unlink($cachefile);
            $st = $st && rename( "$cachefile,new", $cachefile );
        }
        if ($st) {
            fixperms($cachefile);
        } else Abort("Cannot write page $pagename cache to ($cachefile)...");
    } // write_cachefile

    function get_from_cache($pagename) {
        global $ThumbShoeCacheDir;
        if ($this->cache && array_key_exists($pagename, $this->cache))
        {
            return $this->cache[$pagename];
        }
        else
        {
            $pagefile = $this->pagefile($pagename); if (empty($pagefile)) return;
            $cachefile = $this->cachefile($pagename); if (empty($cachefile)) return;
            if ( !file_exists($pagefile) ) return;
            if ( !file_exists($cachefile) ) return;
            if ( filemtime($cachefile) > filemtime($pagefile) )
            {
                $page = $this->read_cachefile($pagename);
                $this->add_to_cache($pagename, $page);
                return $this->cache[$pagename];
            }
        }
        return;
    }

    function read_cachefile( $pagename, $since=0 ) {
        $urlencoded = FALSE;
        $cachefile = $this->cachefile($pagename);
        if ( $cachefile && ( $ft = @fopen($cachefile,'r') ) ) {
            $page = $this->attr;
            while ( !feof($ft) ) {
## headers
                $line = fgets( $ft, 4096 );
                while ( ( substr( $line, -1, 1 ) != "\n" ) && !feof($ft) ) $line .= fgets( $ft, 4096 );
                $line = rtrim($line);
                if (!$line) break;	## empty line indicates end of headers
                if ($urlencoded) $line = urldecode(str_replace( '+', '%2b', $line ));
                @list($k,$v) = explode( '=', $line, 2 );
                if (!$k) continue;
                if ( $k == 'version' ) $urlencoded = ( strpos( $v, 'urlencoded=1' ) !== FALSE );
                $page[$k] = $v;
            }
            $page['text'] = '';
            while (!feof( $ft )) $page['text'] .= fgets( $ft, 4096 );
            $page['text'] = str_replace( $this->r1, $this->r0, $page['text'] );
            if ( substr( $page['text'], -1 ) == "\n" ) $page['text'] = substr( $page['text'], 0, -1 );
            fclose($ft);
            return $page;
        }
    }
} // ThumbShoePageStore

