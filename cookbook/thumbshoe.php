<?php if (!defined('PmWiki')) exit();
/*
+----------------------------------------------------------------------+
| See cookbook/thumbshoe/README.txt for information.
| See cookbook/thumbshoe/LICENSE.txt for licence.
+----------------------------------------------------------------------+
| Copyright 2012 Kathryn Andersen
| This program is free software; you can redistribute it and/or modify
| it under the terms of the GNU General Public License, Version 2, as
| published by the Free Software Foundation.
| http://www.gnu.org/copyleft/gpl.html
| This program is distributed in the hope that it will be useful,
| but WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
| GNU General Public License for more details.
+----------------------------------------------------------------------+
*/

## Version of this recipe
$RecipeInfo['ThumbShoe']['Version'] = '2012-03-16';

SDV($ThumbShoeThumbBg, "grey");
SDV($ThumbShoeThumbPrefix, "thumb_");
SDV($ThumbShoeKeywordsGroup, "Category");
SDV($ThumbShoePageSep, '-');
SDV($ThumbShoeDirFmt, 'thumbshoe.d/{$Group}/{$FullName}');
SDVA($ThumbShoeImgExt, array(
'gif',
'png',
'jpg',
'jpeg',
'bmp',
'xbm',
'eps',
'svg',
));
SDVA($ThumbShoeFields, array(
'Filename' => '%f',
'Size' => '%b',
'Comment' => '%c',
'Ext' => '%e',
'Height' => '%h',
'Colours' => '%k',
'Label' => '%l',
'FileFormat' => '%m',
'Class' => '%r',
'Basename' => '%t',
'Width' => '%w',
'X' => '%x',
'Y' => '%y',
'Z' => '%z',
'DocumentName' => '%[EXIF:DocumentName]',
'ImageDescription' => '%[EXIF:ImageDescription]',
'Make' => '%[EXIF:Make]',
'Model' => '%[EXIF:Model]',
'Orientation' => '%[EXIF:Orientation]',
'XResolution' => '%[EXIF:XResolution]',
'YResolution' => '%[EXIF:YResolution]',
'ResolutionUnit' => '%[EXIF:ResolutionUnit]',
'Software' => '%[EXIF:Software]',
'DateTime' => '%[EXIF:DateTime]',
'Artist' => '%[EXIF:Artist]',
'Copyright' => '%[EXIF:Copyright]',
'GPSInfo' => '%[EXIF:GPSInfo]',
'Flash' => '%[EXIF:Flash]',
'MakerNote' => '%[EXIF:MakerNote]',
'UserComment' => '%[EXIF:UserComment]',
'FileSource' => '%[EXIF:FileSource]',
'Category' => '%[IPTC:2:15]',
'Keywords' => '%[IPTC:2:25]',
'DateCreated' => '%[IPTC:2:55]',
'TimeCreated' => '%[IPTC:2:60]',
'ByLine' => '%[IPTC:2:80]',
'City' => '%[IPTC:2:90]',
'SubLocation' => '%[IPTC:2:92]',
'ProvinceState' => '%[IPTC:2:95]',
'Country' => '%[IPTC:2:101]',
'Credit' => '%[IPTC:2:110]',
'Source' => '%[IPTC:2:115]',
'Caption' => '%[IPTC:2:120]',
));

include("thumbshoe/pagestore.php");
include("thumbshoe/thumbs.php");
include("thumbshoe/vars.php");
include("thumbshoe/rename.php");
include("thumbshoe/delete.php");

