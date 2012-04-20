<?php if (!defined('PmWiki')) exit();

/*
 * Code for the PageStore
 * This requires ImageMagick to read the meta-data
 * of the image.
 */

class ThumbShoePageStore extends PageStore {
    var $galleryGroup;
    var $imgRx;
    var $IMFormat;
    var $hidemeta;
    var $mcache;
    function ThumbShoePageStore($galleryGroup,$hidemeta=false) { 
        global $UploadPrefixFmt;
        global $ThumbShoeFields, $ThumbShoeImgExt, $ThumbShoeDirFmt;
        global $ThumbShoeKeywordsGroup;
        $this->iswrite = true;
        $this->galleryGroup = $galleryGroup;
        $this->hidemeta = $hidemeta;
        $this->dirfmt = $ThumbShoeDirFmt;

        // see if we can find ImageMagick
        $sout = shell_exec('identify -version');
        if ( strpos($sout,'ImageMagick') === FALSE ) Abort('?no ImageMagick identify command found');
        $this->imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';

        $format = '';
        foreach ($ThumbShoeFields as $fn=>$fmt) {
            $format .= $fn . '=' . $fmt . '\\n';
        }
        $this->IMFormat = $format;
    }

    function pagefile($pagename) {
        return $this->imagefile($pagename);
    }

    function read($pagename, $since=0) {
        if( $pagename=="" ) return "";
        global $ThumbShoeThumbPrefix, $ThumbShoeKeywordsGroup;
        if (preg_match('/' . $this->imgRx . '$/i', $pagename))
        {
            StopWatch("ThumbShoePageStore::read begin $pagename");
            $imagefile = $this->imagefile($pagename);
            if ($imagefile)
            {
                // check the cache first
                $metadata = $this->get_metadata_from_cache($pagename);
                if (!$metadata)
                {
                    $imgdata = $this->read_image_data($pagename);
                    $otherdata = $this->read_metadata_file($pagename);

                    // Create the thumbnail if needed
                    ThumbShoeMakeThumb($pagename,$imagefile);

                    // save to cache
                    $this->add_to_mcache($pagename, $imgdata, $otherdata);
                    $this->write_cache_file($pagename, $imgdata);
                    $metadata = $this->get_metadata_from_cache($pagename);
                } // did we get metadata from cache?

                // now convert the metadata into page data
                $page = $this->metadata_to_page($pagename, $metadata, $since);

                StopWatch("ThumbShoePageStore::read end $pagename");
                return $page;
            } // page exists?
        } // is an image page?
        return;
    } // read

    function write($pagename,$page) {
        global $Now, $Version, $Charset;
        $page['charset'] = $Charset;
        $page['name'] = $pagename;
        $page['time'] = $Now;
        $page['host'] = $_SERVER['REMOTE_ADDR'];
        $page['agent'] = @$_SERVER['HTTP_USER_AGENT'];
        $page['rev'] = @$page['rev']+1;
        unset($page['version']); unset($page['newline']);
        uksort($page, 'CmpPageAttr');

        $metadata = $this->page_to_metadata($pagename, $page);
        $this->write_metadata_file($pagename,$metadata);
    } // write

    function delete($pagename) {
        global $Now;
        global $ThumbShoeThumbPrefix;

        // Rename with deletion name; both image and datafile
        // This is in case they want to restore the deletion
        $imagefile = $this->imagefile($pagename);
        @rename($imagefile,"$imagefile,$Now"); // consistent with attachtable

        $datafile = $this->datafile($pagename);
        @rename($datafile,"$datafile,del-$Now");

        // Remove the cache file, and the thumbnail
        // These can both be reconstructed from the image
        $cachefile = $this->cachefile($pagename);
        @unlink($cachefile);

        $uploaddir = PageVar($pagename, '$TSAttachDir');
        $name = PageVar($pagename, '$Name');
        $thumbpath = "$uploaddir/${ThumbShoeThumbPrefix}${name}.png";
        @unlink($thumbpath);
    } // delete

    function ls($pats=NULL) {
        global $UploadDir, $UploadPrefixFmt;
        global $GroupPattern, $NamePattern;
        global $ThumbShoeThumbPrefix, $ThumbShoePageSep;
        StopWatch("ThumbShoePageStore::ls begin {$this->galleryGroup}");
        $pats=(array)$pats; 
        $topdir = PageVar($this->galleryGroup . '.' . $this->galleryGroup,
                          '$TSAttachTopDir');

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
    } // ls

    function rename($pagename,$newpagename) {
        global $ThumbShoeThumbPrefix;

        $newgroup = PageVar($newpagename, '$Group');
        if ($newgroup != $this->galleryGroup)
        {
            Abort("Cannot rename $pagename to $newpagename; groups do not match");
        }

        $imagefile = $this->imagefile($pagename);
        if (preg_match('/\.(\w+)$/',$imagefile,$m1))
        {
            $ext = $m1[1];
        }
        $newimagefile = $this->imagefile($newpagename,false);
        if (!$newimagefile) // probably has no extension
        {
            $newpagename = $newpagename . '_' . $ext;
            $newimagefile = $this->imagefile($newpagename,false);
            if (!$newimagefile) // Huh?
            {
                Abort("Cannot rename $pagename to $newpagename; cannot calculate new filename");
            }
        }
        else if (preg_match('/(.*)\.(\w+)$/',$newimagefile,$m2))
        {
            $newbase = $m2[1];
            $newext = $m2[2];
            if ($ext != $newext) // not allowed to change extensions
            {
                $newimagefile = $newbase . '.' . $ext;
                $newpagename = preg_replace("/$newext$/", $ext, $newpagename);
            }
        }
        $oldmetadata = $this->get_metadata_from_cache($pagename);
        if (!$oldmetadata)
        {
            $oldmetadata = $this->read_metadata_file($pagename);
        }

        // rename the image
        @rename($imagefile,$newimagefile);

        // move the old thumbnail
        $uploaddir = PageVar($pagename, '$TSAttachDir');
        $name = PageVar($pagename, '$Name');
        $thumbpath = "$uploaddir/${ThumbShoeThumbPrefix}${name}.png";
        $newuploaddir = PageVar($newpagename, '$TSAttachDir');
        $newname = PageVar($newpagename, '$Name');
        $newthumbpath = "$newuploaddir/${ThumbShoeThumbPrefix}${newname}.png";
        @rename($thumbpath,$newthumbpath);

        // update the cachefile
        // This needs to be done because moving the file
        // could change its basename, which is given in the cachefile.
        $newcachefile = $this->cachefile($newpagename);
        $newimgdata = $this->read_image_data($newpagename);
        $this->write_cache_file($pagename, $newimgdata);

        $oldcachefile = $this->cachefile($pagename);
        if (file_exists($oldcachefile))
        {
            unlink($oldcachefile);
        }

        // update the datafile
        // remember the old user data!
        $oldmetadata['image'] = $newimgdata;
        $newpagedata = $this->metadata_to_page($newpagename,$oldmetadata);
        $this->write($newpagename,$newpagedata);

        $olddatafile = $this->datafile($pagename);
        if (file_exists($olddatafile))
        {
            @unlink($olddatafile);
        }

        // Avoid having to reload the page
        $np = $this->read($newpagename);

        return $newpagename;
    } // rename

    /* ============================================================ */

    function imagefile($pagename, $must_exist=true) {
        if( $pagename=="" ) return "";
        $pagename = str_replace('/', '.', $pagename);

        $name = PageVar($pagename, '$Name');
        if (preg_match('/(.*)_' . $this->imgRx . '$/i', $name, $m))
        {
            $filename = PageVar($pagename, '$TSPageImage');
            $dir = PageVar($pagename, '$TSAttachDir');
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
    } // imagefile

    function datafile( $pagename ) {
        $dfmt = $this->dirfmt;
        if ($pagename > '') {
            $pagename = str_replace('/', '.', $pagename);
            ## optimizations for standard locations
            if ( $dfmt == 'thumbshoe.d/{$FullName}' )				return "thumbshoe.d/$pagename";
            if ( $dfmt == 'thumbshoe.d/{$Group}/{$FullName}' )	return preg_replace( '/([^.]+).*/', 'thumbshoe.d/$1/$0', $pagename );
        }
        return FmtPageName( $dfmt, $pagename );
    }
    function cachefile( $pagename ) {
        $cfmt = $this->dirfmt . '=cache';
        if ($pagename > '') {
            $pagename = str_replace('/', '.', $pagename);
            ## optimizations for standard locations
            if ( $cfmt == 'thumbshoe.d/{$FullName}' )	return "thumbshoe.d/${pagename}=cache";
        }
        return FmtPageName( $cfmt, $pagename );
    }

    function read_image_data($pagename) {
        if ( $pagename=="" ) return "";
        global $ThumbShoeThumbPrefix;
        if (preg_match('/' . $this->imgRx . '$/i', $pagename))
        {
            $imagefile = $this->imagefile($pagename);
            if ($imagefile)
            {
                $name = PageVar($pagename, '$Name');
                $basename = basename($imagefile);
                $thumbfile = $ThumbShoeThumbPrefix . $name . ".png";
                $sout = shell_exec("identify -format '" . $this->IMFormat . "' $imagefile");
                $data = array();
                $data['Keywords'] = '';
                $data['Comment'] = '';
                $data['ThumbFile'] = $thumbfile;
                $data['ImageFile'] = $basename;
                if (preg_match_all('/^(\w+)=(.*)$/m', $sout, $matches, PREG_SET_ORDER))
                {
                    foreach ($matches as $val)
                    {
                        if ($val[2])
                        {
                            $data[$val[1]] = $val[2];
                        }
                    }
                }
            } // image exists
            return $data;
        } // is image
        return;
    } // read_image_data

    function metadata_to_page($pagename, $metadata, $since=0)
    {
        global $ThumbShoeKeywordsGroup;
        $page['name'] = $pagename;
        if ($metadata['pmwiki'])
        {
            foreach ($metadata['pmwiki'] as $k => $v)
            {
                if (preg_match('/_(\\d+)/', $k, $m))
                {
                    if ($since > 0 && $m[1] < $since)
                    {
                        continue;
                    }
                    // The "history" values are given with ':'
                    // but the .ini file format doesn't allow that,
                    // so they are saved as '_'
                    $k2 = str_replace('_', ':', $k);
                    $page[$k2] = $v;
                }
                else
                {
                    $page[$k] = $v;
                }
            }
        }
        $keywords = '';
        $comment = '';
        $text = '';
        foreach ($metadata['image'] as $k => $v)
        {
            if ($k == 'Keywords')
            {
                $keywords = $v;
            }
            else if ($k == 'Comment')
            {
                $comment = $v;
            }
            else
            {
                if ($this->hidemeta)
                {
                    $text .= "(:$k:$v:)\n";
                }
                else
                {
                    $text .= ":$k:$v\n";
                }
            }
        }
        if ($metadata['user'])
        {
            foreach ($metadata['user'] as $k => $v)
            {
                // User keywords override image keywords
                if ($k == "Keywords")
                {
                    $keywords = $v;
                }
                // User comments override image comments
                else if ($k == "Comment")
                {
                    $comment = $v;
                }
                else
                {
                    if ($this->hidemeta)
                    {
                        $text .= "(:$k:$v:)\n";
                    }
                    else
                    {
                        $text .= ":$k:$v\n";
                    }
                }
            }
        }

        // add the keywords and comment
        if ($this->hidemeta)
        {
            $text .= "(:Keywords:$keywords:)\n";
            $text .= "(:Comment:$comment:)\n";
        }
        else
        {
            $text .= ":Keywords:$keywords\n";
            $text .= ":Comment:$comment\n";
        }
        $page['text'] = $text;

        // Set the keyword pages as targets.
        // We do this now rather than when a metadata-file is written
        // because keywords can exist in the image itself
        // when there is no metadata-file
        if ($ThumbShoeKeywordsGroup && $keywords)
        {
            $cats = explode(';',$keywords);
            $catpages = array();
            foreach((array)$cats as $k)
            {
                $tpn = MakePageName(
                    $ThumbShoeKeywordsGroup . '.' . $ThumbShoeKeywordsGroup, $k);
                $catpages[] = $tpn;
            }
            $page['targets'] = implode(',',$catpages);
        }
        return $page;
    } // metadata_to_page

    function page_to_metadata($pagename, &$page)
    {
        $metadata = array();
        $metadata['pmwiki'] = array();
        $metadata['user'] = array();
        $text = $page['text'];
        foreach ($page as $k => $v)
        {
            if ($k != 'text')
            {
                if ($k > '' && $k{0} != '=') {
                    // The "history" values are given with ':'
                    // but the .ini file format doesn't allow that,
                    // so they are saved as '_'
                    $k2 = str_replace(':', '_', $k);
                    $metadata['pmwiki'][$k2] = $v;
                }
            }
        }
        $metadata['user']['Keywords'] = '';
        $metadata['user']['Comment'] = '';
        if (preg_match_all('/^\(:(Keywords|Comment):(.*):\)$/m', $text, $matches, PREG_SET_ORDER))
        {
            foreach ($matches as $val)
            {
                $metadata['user'][$val[1]] = $val[2];
            }
        }
        if (preg_match_all('/^:(Keywords|Comment):(.*)$/m', $text, $matches, PREG_SET_ORDER))
        {
            foreach ($matches as $val)
            {
                $metadata['user'][$val[1]] = $val[2];
            }
        }
        return $metadata;
    } // page_to_metadata

    function write_metadata_file($pagename, $metadata)
    {
        global $Version;
        $datafile = $this->datafile($pagename);
        $dir = dirname($datafile);
        mkdirp($dir);
        if ( !file_exists("$dir/.htaccess")
            && ( $fp = @fopen( "$dir/.htaccess", 'w' ) ) )
        {
            fwrite( $fp, "Order Deny,Allow\nDeny from all\n" );
            fclose($fp);
        }
        $st = false;
        if ( $datafile && ( $fp = fopen( "$datafile,new", 'w' ) ) )
        {
            $st = true;
            $metadata['pmwiki']['version'] = "$Version fmt=thumbshoe";

            if ($metadata['pmwiki'])
            {
                $st = $st && fwrite($fp, "[pmwiki]\n");
                foreach ($metadata['pmwiki'] as $k => $v)
                {
                    $st = $st && fwrite($fp, "$k = " . '"' . $v . '"' . "\n");
                }
                $st = $st && fwrite($fp, "\n");
            }
            if ($metadata['user'])
            {
                $st = $st && fwrite($fp, "[user]\n");
                foreach ($metadata['user'] as $k => $v)
                {
                    $st = $st && fwrite($fp, "$k = " . '"' . $v . '"' . "\n");
                }
                $st = $st && fwrite($fp, "\n");
            }
            $st = fclose($fp) && $st;
            if (file_exists( $datafile )) $st = $st && unlink($datafile);
            $st = $st && rename( "$datafile,new", $datafile );
        }
        if ($st) {
            fixperms($datafile);
        } else Abort("Cannot write page $pagename to ($datafile)...");

    } // write_metadata_file

    function write_cache_file($pagename, $imgdata)
    {
        global $Version;
        $cachefile = $this->cachefile($pagename);
        $dir = dirname($cachefile);
        mkdirp($dir);
        if ( !file_exists("$dir/.htaccess")
            && ( $fp = @fopen( "$dir/.htaccess", 'w' ) ) )
        {
            fwrite( $fp, "Order Deny,Allow\nDeny from all\n" );
            fclose($fp);
        }
        $st = false;
        if ( $cachefile && ( $fp = fopen( "$cachefile,new", 'w' ) ) )
        {
            $st = true;
            if ($imgdata)
            {
                $st = $st && fwrite($fp, "[image]\n");
                foreach ($imgdata as $k => $v)
                {
                    $st = $st && fwrite($fp, "$k = " . '"' . $v . '"' . "\n");
                }
                $st = $st && fwrite($fp, "\n");
            }
            $st = fclose($fp) && $st;
            if (file_exists( $cachefile )) $st = $st && unlink($cachefile);
            $st = $st && rename( "$cachefile,new", $cachefile );
        }
        if ($st) {
            fixperms($cachefile);
        } else Abort("Cannot write page $pagename cache to ($cachefile)...");

    } // write_cache_file
    
    function read_metadata_file($pagename)
    {
        $datafile = $this->datafile($pagename);
        if (!file_exists($datafile))
        {
            return false;
        }
        $metadata = array();
        $metadata = parse_ini_file($datafile, true);
        return $metadata;
    }

    function read_cache_file($pagename)
    {
        $cachefile = $this->cachefile($pagename);
        if (!file_exists($cachefile))
        {
            return false;
        }
        $data = array();
        $data = parse_ini_file($cachefile, true);
        return $data;
    }

    function add_to_mcache($pagename, $imgdata, $metadata) {
        $this->mcache[$pagename]['image'] = array();
        if ($imgdata['image'])
        {
            foreach($imgdata['image'] as $k=>$v) 
            {
                $this->mcache[$pagename]['image'][$k]=$v;
            }
        }
        else if ($imgdata)
        {
            foreach($imgdata as $k=>$v) 
            {
                $this->mcache[$pagename]['image'][$k]=$v;
            }
        }
        $this->mcache[$pagename]['pmwiki'] = array();
        $this->mcache[$pagename]['user'] = array();
        if ($metadata)
        {
            if ($metadata['pmwiki'])
            {
                foreach($metadata['pmwiki'] as $k=>$v) 
                {
                    $this->mcache[$pagename]['pmwiki'][$k]=$v;
                }
            }
            if ($metadata['user'])
            {
                foreach($metadata['user'] as $k=>$v) 
                {
                    $this->mcache[$pagename]['user'][$k]=$v;
                }
            }
        }
    } // add_to_mcache

    function get_metadata_from_cache($pagename) {
        global $ThumbShoeDataDir;
        if ($this->mcache && array_key_exists($pagename, $this->mcache))
        {
            return $this->mcache[$pagename];
        }
        else
        {
            $imagefile = $this->imagefile($pagename); if (empty($imagefile)) return;
            $datafile = $this->datafile($pagename); if (empty($datafile)) return;
            $cachefile = $this->cachefile($pagename); if (empty($cachefile)) return;
            if ( !file_exists($imagefile) ) return;
            if ( !file_exists($cachefile) ) return;
            if ( filemtime($cachefile) > filemtime($imagefile) )
            {
                // the image is older than the image cache file
                $imgdata = $this->read_cache_file($pagename);
                if ( file_exists($datafile) )
                {
                    $metadata = $this->read_metadata_file($pagename);
                }

                $this->add_to_mcache($pagename, $imgdata, $metadata);
                return $this->mcache[$pagename];
            }
        }
        return;
    }
} // ThumbShoePageStore

