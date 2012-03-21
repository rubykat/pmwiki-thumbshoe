<?php if (!defined('PmWiki')) exit();
/*
 * Make a "cluster" of ThumbShoe PageStores.
 * This makes a new ThumbShoe group for each sub-directory of
 * the given directory, naming convetion compatible with Cluster.
 */
function ThumbShoeMakeCluster ($galleryGroup,$picDir,$picUrl,$hidemeta=false) {
    global $ClusterSeparator;

    $sep = ($ClusterSeparator ? $ClusterSeparator : '-');
    if (file_exists($picDir) && is_dir($picDir))
    {
        ThumbShoeMakeGroup($galleryGroup,$picDir,$picUrl,$hidemeta);
        $dfp = @opendir($picDir);
        if ($dfp)
        {
            while ( ($file = readdir($dfp)) !== false) {
                if ($file{0} == '.') continue;
                if (is_dir("$picDir/$file"))
                {
                    $newdir = "$picDir/$file";
                    $newurl = "$picUrl/$file";
                    $pagename = MakePageName(
                        "${galleryGroup}.${galleryGroup}",
                        $file);
                    $bits = explode('.', $pagename);
                    $subgroup = $bits[1];
                    $newgroup = "${galleryGroup}${sep}${subgroup}";
                    ThumbShoeMakeCluster($newgroup,$newdir,$newurl,$hidemeta);
                }
            }
            closedir($dfp);
        }
    }
} // ThumbShoeMakeCluster

function ThumbShoeMakeGroup ($galleryGroup,$picDir,$picUrl,$hidemeta=false) {
    global $WikiLibDirs;
    $WikiLibDirs[] = new ThumbShoePageStore(
        $galleryGroup,
        $picDir,
        $picUrl,
        $hidemeta);
} // ThumbShoeMakeGroup
