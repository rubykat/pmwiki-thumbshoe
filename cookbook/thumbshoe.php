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
$RecipeInfo['Thumbshoe']['Version'] = '2012-03-16';

SDV($ThumbshoeThumbBg, "grey");
SDV($ThumbshoeThumbPrefix, "thumb_");
SDVA($ThumbshoeImgExt, array(
'gif',
'png',
'jpg',
'jpeg',
'bmp',
'xbm',
'eps',
'svg',
));
SDVA($ThumbshoeFields, array(
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
'Transparent' => '%A',
'Compression' => '%C',
'CanvasHeight' => '%H',
'CanvasWidth' => '%W',
'CanvasX' => '%X',
'CanvasY' => '%Y',
'ImageWidth' => '%[EXIF:ImageWidth]',
'ImageLength' => '%[EXIF:ImageLength]',
'BitsPerSample' => '%[EXIF:BitsPerSample]',
'Compression' => '%[EXIF:Compression]',
'PhotometricInterpretation' => '%[EXIF:PhotometricInterpretation]',
'FillOrder' => '%[EXIF:FillOrder]',
'DocumentName' => '%[EXIF:DocumentName]',
'ImageDescription' => '%[EXIF:ImageDescription]',
'Make' => '%[EXIF:Make]',
'Model' => '%[EXIF:Model]',
'StripOffsets' => '%[EXIF:StripOffsets]',
'Orientation' => '%[EXIF:Orientation]',
'SamplesPerPixel' => '%[EXIF:SamplesPerPixel]',
'RowsPerStrip' => '%[EXIF:RosePerStrip]',
'StripByteCounts' => '%[EXIF:StripByteCounts]',
'XResolution' => '%[EXIF:XResolution]',
'YResolution' => '%[EXIF:YResolution]',
'PlanarConfiguration' => '%[EXIF:PlanarConfiguration]',
'ResolutionUnit' => '%[EXIF:ResolutionUnit]',
'TransferFunction' => '%[EXIF:TransferFunction]',
'Software' => '%[EXIF:Software]',
'DateTime' => '%[EXIF:DateTime]',
'Artist' => '%[EXIF:Artist]',
'WhitePoint' => '%[EXIF:WhitePoint]',
'PrimaryChromaticities' => '%[EXIF:PrimaryChromaticities]',
'TransferRange' => '%[EXIF:TransferRange]',
'JPEGProc' => '%[EXIF:JPEGProc]',
'JPEGInterchangeFormat' => '%[EXIF:JPEGInterchangeFormat]',
'JPEGInterchangeFormatLength' => '%[EXIF:JPEGInterchangeFormatLength]',
'YCbCrCoefficients' => '%[EXIF:YCbCrCoefficients]',
'YCbCrSubSampling' => '%[EXIF:YCbCrSubSampling]',
'YCbCrPositioning' => '%[EXIF:YCbCrPositioning]',
'ReferenceBlackWhite' => '%[EXIF:ReferenceBlackWhite]',
'CFARepeatPatternDim' => '%[EXIF:CFARepeatPatternDim]',
'CFAPattern' => '%[EXIF:CFAPattern]',
'BatteryLevel' => '%[EXIF:BatteryLevel]',
'Copyright' => '%[EXIF:Copyright]',
'ExposureTime' => '%[EXIF:ExposureTime]',
'FNumber' => '%[EXIF:FNumber]',
'EXIFOffset' => '%[EXIF:EXIFOffset]',
'InterColorProfile' => '%[EXIF:InterColorProfile]',
'ExposureProgram' => '%[EXIF:ExposureProgram]',
'SpectralSensitivity' => '%[EXIF:SpectralSensitivity]',
'GPSInfo' => '%[EXIF:GPSInfo]',
'ISOSpeedRatings' => '%[EXIF:ISOSpeedRatings]',
'OECF' => '%[EXIF:OECF]',
'EXIFVersion' => '%[EXIF:EXIFVersion]',
'DateTimeOriginal' => '%[EXIF:DateTimeOriginal]',
'DateTimeDigitized' => '%[EXIF:DateTimeDigitized]',
'ComponentsConfiguration' => '%[EXIF:ComponentsConfiguration]',
'CompressedBitsPerPixel' => '%[EXIF:CompressedBitsPerPixel]',
'ShutterSpeedValue' => '%[EXIF:ShutterSpeedValue]',
'ApertureValue' => '%[EXIF:ApertureValue]',
'BrightnessValue' => '%[EXIF:BrightnessValue]',
'ExposureBiasValue' => '%[EXIF:ExposureBiasValue]',
'MaxApertureValue' => '%[EXIF:MaxApertureValue]',
'SubjectDistance' => '%[EXIF:SubjectDistance]',
'MeteringMode' => '%[EXIF:MeteringMode]',
'LightSource' => '%[EXIF:LightSource]',
'Flash' => '%[EXIF:Flash]',
'FocalLength' => '%[EXIF:FocalLength]',
'MakerNote' => '%[EXIF:MakerNote]',
'UserComment' => '%[EXIF:UserComment]',
'SubSecTime' => '%[EXIF:SubSecTime]',
'SubSecTimeOriginal' => '%[EXIF:SubSecTimeOriginal]',
'SubSecTimeDigitized' => '%[EXIF:SubSecTimeDigitized]',
'FlashPixVersion' => '%[EXIF:FlashPixVersion]',
'ColorSpace' => '%[EXIF:ColorSpace]',
'EXIFImageWidth' => '%[EXIF:EXIFImageWidth]',
'EXIFImageLength' => '%[EXIF:EXIFImageLength]',
'InteroperabilityOffset' => '%[EXIF:InteroperabilityOffset]',
'FlashEnergy' => '%[EXIF:FlashEnergy]',
'SpatialFrequencyResponse' => '%[EXIF:SpatialFrequencyResponse]',
'FocalPlaneXResolution' => '%[EXIF:FocalPlaneXResolution]',
'FocalPlaneYResolution' => '%[EXIF:FocalPlaneYResolution]',
'FocalPlaneResolutionUnit' => '%[EXIF:FocalPlaneResolutionUnit]',
'SubjectLocation' => '%[EXIF:SubjectLocation]',
'ExposureIndex' => '%[EXIF:ExposureIndex]',
'SensingMethod' => '%[EXIF:SensingMethod]',
'FileSource' => '%[EXIF:FileSource]',
'SceneType' => '%[EXIF:SceneType]',
'SubjectReference' => '%[IPTC:2:12]',
'Category' => '%[IPTC:2:15]',
'SupplementalCategory' => '%[IPTC:2:20]',
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

## Add a custom page storage location and some bundled wikipages.
#@include("thumbshoe/bundlepages.php");

