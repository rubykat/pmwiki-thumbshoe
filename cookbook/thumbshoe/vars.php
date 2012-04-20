<?php if (!defined('PmWiki')) exit();
/*
 * Page variables and conditions
 */

$FmtPV['$KeywordsLinked'] = "ThumbShoeKeywords(\$pn, 'LinkedTitle')";
$FmtPV['$KeywordsLinkedName'] = "ThumbShoeKeywords(\$pn, 'LinkedName')";
$FmtPV['$KeywordsName'] = "ThumbShoeKeywords(\$pn, 'Name')";

function ThumbShoeKeywords($pagename, $label='LinkedName') {
        global $ThumbShoeKeywordsGroup;
	$inval = PageTextVar($pagename, 'Keywords');
	$out = '';
	// don't process if there are already links there
	if (strpos($inval, '[[') !== false)
	{
		$out = $inval;
	}
	else
	{
            $array_sep = '';
            if (strpos($inval, ';') !== false)
            {
                $array_sep = ';';
            }
            $oo = array();
	    if ($label == 'Name') // one page name, not parts
	    {
	    	$pn = str_replace($array_sep, ' ', $inval);
		$cpage = MakePageName($pagename, "$ThumbShoeKeywordsGroup.$pn");
		$out = PageVar($cpage, '$Name');
	    }
	    else
	    {
		$parts = ($array_sep
			  ? explode($array_sep, $inval)
			  : array($inval));
		foreach($parts as $part)
		{
		    $part = trim($part);
		    if ($part)
		    {
			$cpage = MakePageName($pagename, "$ThumbShoeKeywordsGroup.$part");
			if ($label == 'LinkedTitle')
			{
			    $oo[] = "[[$cpage|+]]";
			}
			else
			{
			    $oo[] = "[[$cpage|$part]]";
			}
		    }
		}
	    }
	    if ($array_sep == ',' or $array_sep == ';')
	    {
		$out .= implode("$array_sep ", $oo);
	    }
	    else if ($array_sep == '/' or $array_sep == ' ')
	    {
		$out .= implode($array_sep, $oo);
	    }
	    else
	    {
		$out .= implode(" $array_sep ", $oo);
	    }
	}
	rtrim($out);
	return $out;
}

$FmtPV['$TSAttachPage'] = "ThumbShoeAttachPage(\$pn, 'Page')";
$FmtPV['$TSAttachDir'] = "ThumbShoeAttachPage(\$pn, 'Dir')";
$FmtPV['$TSAttachTopDir'] = "ThumbShoeAttachPage(\$pn, 'TopDir')";
$FmtPV['$TSPageImage'] = "ThumbShoeAttachPage(\$pn, 'Image')";
$FmtPV['$TSPageImageBase'] = "ThumbShoeAttachPage(\$pn, 'ImageBase')";

function ThumbShoeAttachPage($pagename, $label='Page') {
    global $UploadDir, $UploadPrefixFmt;
    global $ThumbShoePageSep, $ThumbShoeImgExt, $NamePattern;

    $name = PageVar($pagename, '$Name');
    $group = PageVar($pagename, '$Group');

    $imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';

    $upload_page = '';
    $dir = '';
    $img_name = '';
    if ($UploadPrefixFmt == '/$Group')
    {
        $dir = "$UploadDir/$group";
        $topdir = "$UploadDir/$group";
        $upload_page = "${group}.${group}";
    }
    else if ($UploadPrefixFmt == '/$Group/$Name')
    {
        $dir = "$UploadDir/$group/$name";
        $topdir = "$UploadDir/$group";
        $upload_page = $pagename;
    }
    else if ($UploadPrefixFmt == '')
    {
        $dir = $UploadDir;
        $topdir = $UploadDir;
        $upload_page = "${group}.${group}";
    }
    else // give up
    {
        $dir = FmtPageName("$UploadDir$UploadPrefixFmt", $pagename);
        $topdir = $dir;
        $upload_page = $pagename;
    }
    if (preg_match("/(.*)_$imgRx$/i", $name, $m1))
    {
        $base = $m1[1];
        $ext = $m1[2];
        if (preg_match("/^($NamePattern)$ThumbShoePageSep(\w+)$/i", $base, $m2))
        {
            $ul_name = $m2[1];
            $upload_page = "${group}.${ul_name}";
            $img_name = $m2[2] . '.' . $ext;
            $img_base = $m2[2];
            $dir = FmtPageName("$UploadDir$UploadPrefixFmt", $upload_page);
        }
        else
        {
            $img_name = $base . '.' . $ext;
            $img_base = $base;
        }

    }
    if ($label == 'Page')
    {
        return $upload_page;
    }
    else if ($label == 'Dir')
    {
        return $dir;
    }
    else if ($label == 'TopDir')
    {
        return $topdir;
    }
    else if ($label == 'Image')
    {
        return $img_name;
    }
    else
    {
        return $img_base;
    }
} // ThumbShoeAttachPage

# -------------------- Conditions -------------------------------

# Provide imagepage conditional (:if imagepage PageName:)
$Conditions['imagepage'] = 'ImagePageCondition($pagename, $condparm)';

function ImagePageCondition($pagename, $arg) {
    global $ThumbShoeImgExt;

    $arg = ParseArgs($arg);

    $check = @$arg[''][0];

    $imgRx = '(' . implode('|', $ThumbShoeImgExt) . ')';

    $name = PageVar($check, '$Name', $pagename);
    if (preg_match('/(.*)_' . $imgRx . '$/i', $name))
    {
    	return true;
    }
    return false;
}
