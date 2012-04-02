<?php if (!defined('PmWiki')) exit();

/*
 * Code for renaming images/pages
 */

SDV($UploadDir,'uploads');

$HandleActions['renamets'] = 'HandleThumbShoeRename';
$HandleActions['postrenamets'] = 'HandleThumbShoePostRename';

SDV($ThumbShoeRenameFmt,
    "<h1 class='wikiaction'>$[Rename] \$ImgName</a></h1>
    <form action='\$PageUrl' method='post'>
    <input type='hidden' name='n' value='\$FullName' />
    <input type='hidden' name='action' value='postrenamets' />
    \$RenamePage
    <input type='text' name='newname' value='\$ImgName' size='25' /><br/>
    <input type='submit' value='$[Rename]' /></form>
    ");

function HandleThumbShoeRename($pagename,$auth='edit') {
    global $WikiLibDirs, $LastModFile;
    global $ThumbShoeImgExt;
    global $TSHandleRenameFmt,$PageStartFmt,$ThumbShoeRenameFmt,$PageEndFmt;
    $page = RetrieveAuthPage($pagename, $auth, true, READPAGE_CURRENT);
    if (!$page) { Abort("?cannot rename $pagename; not authorized"); return; }

    $imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';
    if (!preg_match("/(.*)_$imgRx$/i", $pagename, $m1))
    {
        Abort("Cannot rename $pagename; is not an Image page"); return;
    }

    $tsdir = '';
    foreach((array)$WikiLibDirs as $dir)
    {
        if ($dir->exists($pagename) and $dir->iswrite)
        {
            $tsdir = $dir;
            break;
        }
    }
    if (!$tsdir)
    {
        Abort("Cannot rename $pagename; cannot find page"); return;
    }

    // Okay, should be able to rename this page now.

    $img_name = PageVar($pagename,'$TSPageImage');
    SDV($TSHandleRenameFmt,array(&$PageStartFmt,&$ThumbShoeRenameFmt,&$PageEndFmt));
    $ThumbShoeRenameFmt = str_replace('$FullName', $pagename, $ThumbShoeRenameFmt);
    $ThumbShoeRenameFmt = str_replace('$ImgName', $img_name, $ThumbShoeRenameFmt);
    $ThumbShoeRenameFmt = str_replace(
        '$RenamePage',
        TSFmtPageList(
            $pagename,
            $tsdir->galleryGroup,
            array('o'=>'fmt=pickpage')),$ThumbShoeRenameFmt);
    PrintFmt($pagename,$TSHandleRenameFmt);
} # HandleThumbShoeRename

## usage: ?action=postrenamets&newname=filename&newpage=pagename
function HandleThumbShoePostRename($pagename,$auth = 'edit') {
    global $WikiLibDirs;
    global $ThumbShoePageSep;
    global $HandleAuth, $UploadFileFmt, $LastModFile, $TimeFmt;

    $newname = $_REQUEST['newname'];
    if ($newname=='') Abort("?no new image name");
    $newname = str_replace('.', '_', $newname);

    $newpage = $_REQUEST['newpage'];
    if ($newpage=='') Abort("?no new image page");

    $newimgpage = $newpage . $ThumbShoePageSep . $newname;

    $tsdir = '';
    foreach((array)$WikiLibDirs as $dir)
    {
        if ($dir->exists($pagename) and $dir->iswrite)
        {
            $tsdir = $dir;
            break;
        }
    }
    if (!$tsdir)
    {
        Abort("Cannot rename $pagename to $newimgpage; cannot find page"); return;
    }

    ## check authorization
    if ( !RetrieveAuthPage( $newimgpage, $auth, TRUE, READPAGE_CURRENT ) )
        Abort("?cannot rename image page from $pagename to $newimgpage");
    $newnewpage = @$tsdir->rename($pagename, $newimgpage);
    if ($newnewpage)
    {
        Redirect( $newnewpage );
    }
} # HandleThumbShoePostRename

function TSFmtPageList($pagename,$gallery_group,$opt) {
    global $SearchPatterns,$FPLFunctions;
    global $ThumbShoeImgExt;

    $imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';
    $pat = (array)@$SearchPatterns['normal'];
    $pat['galleryGroup'] = "/^$gallery_group\./";
    $pat['notImages'] = "!$imgRx\$!";

    $pagelist = ListPages($pat);
    sort($pagelist);
    $matches = array();
    foreach ($pagelist as $pagefile) $matches[] = array('pagename' => $pagefile);
    if (preg_match('/^([^=]*)=(.*?)$/',$opt['o'],$mat)) $f[$mat[1]] = $mat[2];
    $fmtfn = @$FPLFunctions[$f['fmt']];
    if (!function_exists($fmtfn)) $fmtfn='TSFPLPickPage';
    return $fmtfn($pagename,$matches,$opt);
}

function TSFPLPickPage($pagename,&$pagelist,$opt) {
    global $PagePickListFmt;
    SDV($PagePickListFmt,'<option$Select>$FullName</option>');
    $uploadpage = PageVar($pagename, '$TSUploadPage');
    $currentpage = FmtPageName($PagePickListFmt,$uploadpage);
    $out = array();
    foreach($pagelist as $item) {
        $ppage = FmtPageName($PagePickListFmt,$item['pagename']);
        $s = ($ppage==$currentpage) ? " selected='selected'" : '';
        $out[] = str_replace('$Select',$s,$ppage);
    }
    return "<select name='newpage'>" . implode('',$out) . "</select> ";
}
