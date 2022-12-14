<?php

/**
 *
 * Enable this filter to scan images (or <i>xmp sidecar</i> files) for metadata.
 *
 * Relevant metadata found will be incorporated into the image (or album object).
 * See <i>{@link http://www.adobe.com/devnet/xmp.html  Adobe XMP Specification}</i>
 * for xmp metadata description. This plugin attempts to map the <i>xmp metadata</i> to Zenphoto or IPTC fields.
 *
 * If a sidecar file exists, it will take precedence (the image file will not be
 * examined.) The sidecar file should reside in the same folder, have the same <i>prefix</i> name as the
 * image (album), and the suffix <var>.xmp</var>. Thus, the sidecar for <i>image</i>.jpg would be named
 * <i>image</i><var>.xmp</var>.
 *
 * NOTE: dynamic albums have an <var>.alb</var> suffix. Append <var>.xmp</var> to that name so
 * that the dynamic album sidecar would be named <i>album</i><var>.alb.xmp</var>.
 *
 * There are two options for this plugin
 * 	<ul>
 * 		<li>The suffix of the metadata sidecar file</li>
 * 		<li>A list of image file suffixes that may contain metadata</li>
 * 	</ul>
 * Check each image type you wish the plugin to search within for
 * an <i>xmp block</i>. These are disabled by default because scanning image files can add considerably to the
 * processing time.
 *
 * The plugin does not present any theme interface.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins
 * @subpackage media
 */
$plugin_is_filter = 9 | CLASS_PLUGIN;
$plugin_description = gettext('Extracts <em>XMP</em> metadata from images and <code>XMP</code> sidecar files.');
$plugin_author = "Stephen Billard (sbillard)";

$option_interface = 'xmpMetadata';

zp_register_filter('album_instantiate', 'xmpMetadata::album_instantiate');
zp_register_filter('new_album', 'xmpMetadata::new_album');
zp_register_filter('album_refresh', 'xmpMetadata::new_album');
zp_register_filter('image_instantiate', 'xmpMetadata::image_instantiate');
zp_register_filter('image_metadata', 'xmpMetadata::new_image');
zp_register_filter('upload_filetypes', 'xmpMetadata::sidecars');
zp_register_filter('save_album_utilities_data', 'xmpMetadata::putXMP');
zp_register_filter('edit_album_utilities', 'xmpMetadata::create');
zp_register_filter('save_image_utilities_data', 'xmpMetadata::putXMP');
zp_register_filter('edit_image_utilities', 'xmpMetadata::create');
zp_register_filter('bulk_image_actions', 'xmpMetadata::bulkActions');
zp_register_filter('bulk_album_actions', 'xmpMetadata::bulkActions');

require_once(dirname(dirname(__FILE__)) . '/exif/exif.php');

define('XMP_EXTENSION', strtolower(getOption('xmpMetadata_suffix')));

/**
 * Plugin option handling class
 *
 */
class xmpMetadata {

	private static $XML_trans = array(
					'&#128;'	 => '???',
					'&#130;'	 => '???',
					'&#131;'	 => '??',
					'&#132;'	 => '???',
					'&#133;'	 => '???',
					'&#134;'	 => '???',
					'&#135;'	 => '???',
					'&#136;'	 => '??',
					'&#137;'	 => '???',
					'&#138;'	 => '??',
					'&#139;'	 => '???',
					'&#140;'	 => '??',
					'&#142;'	 => '??',
					'&#145;'	 => '???',
					'&#146;'	 => '???',
					'&#147;'	 => '???',
					'&#148;'	 => '???',
					'&#149;'	 => '???',
					'&#150;'	 => '???',
					'&#151;'	 => '???',
					'&#152;'	 => '??',
					'&#153;'	 => '???',
					'&#154;'	 => '??',
					'&#155;'	 => '???',
					'&#156;'	 => '??',
					'&#158;'	 => '??',
					'&#159;'	 => '??',
					'&#161;'	 => '??',
					'&#162;'	 => '??',
					'&#163;'	 => '??',
					'&#164;'	 => '??',
					'&#165;'	 => '??',
					'&#166;'	 => '??',
					'&#167;'	 => '??',
					'&#168;'	 => '??',
					'&#169;'	 => '??',
					'&#170;'	 => '??',
					'&#171;'	 => '??',
					'&#172;'	 => '??',
					'&#173;'	 => '??',
					'&#174;'	 => '??',
					'&#175;'	 => '??',
					'&#176;'	 => '??',
					'&#177;'	 => '??',
					'&#178;'	 => '??',
					'&#179;'	 => '??',
					'&#180;'	 => '??',
					'&#181;'	 => '??',
					'&#182;'	 => '??',
					'&#183;'	 => '??',
					'&#184;'	 => '??',
					'&#185;'	 => '??',
					'&#186;'	 => '??',
					'&#187;'	 => '??',
					'&#188;'	 => '??',
					'&#189;'	 => '??',
					'&#190;'	 => '??',
					'&#191;'	 => '??',
					'&#192;'	 => '??',
					'&#193;'	 => '??',
					'&#194;'	 => '??',
					'&#195;'	 => '??',
					'&#196;'	 => '??',
					'&#197;'	 => '??',
					'&#198;'	 => '??',
					'&#199;'	 => '??',
					'&#200;'	 => '??',
					'&#201;'	 => '??',
					'&#202;'	 => '??',
					'&#203;'	 => '??',
					'&#204;'	 => '??',
					'&#205;'	 => '??',
					'&#206;'	 => '??',
					'&#207;'	 => '??',
					'&#208;'	 => '??',
					'&#209;'	 => '??',
					'&#210;'	 => '??',
					'&#211;'	 => '??',
					'&#212;'	 => '??',
					'&#213;'	 => '??',
					'&#214;'	 => '??',
					'&#215;'	 => '??',
					'&#216;'	 => '??',
					'&#217;'	 => '??',
					'&#218;'	 => '??',
					'&#219;'	 => '??',
					'&#220;'	 => '??',
					'&#221;'	 => '??',
					'&#222;'	 => '??',
					'&#223;'	 => '??',
					'&#224;'	 => '??',
					'&#225;'	 => '??',
					'&#226;'	 => '??',
					'&#227;'	 => '??',
					'&#228;'	 => '??',
					'&#229;'	 => '??',
					'&#230;'	 => '??',
					'&#231;'	 => '??',
					'&#232;'	 => '??',
					'&#233;'	 => '??',
					'&#234;'	 => '??',
					'&#235;'	 => '??',
					'&#236;'	 => '??',
					'&#237;'	 => '??',
					'&#238;'	 => '??',
					'&#239;'	 => '??',
					'&#240;'	 => '??',
					'&#241;'	 => '??',
					'&#242;'	 => '??',
					'&#243;'	 => '??',
					'&#244;'	 => '??',
					'&#245;'	 => '??',
					'&#246;'	 => '??',
					'&#247;'	 => '??',
					'&#248;'	 => '??',
					'&#249;'	 => '??',
					'&#250;'	 => '??',
					'&#251;'	 => '??',
					'&#252;'	 => '??',
					'&#253;'	 => '??',
					'&#254;'	 => '??',
					'&#255;'	 => '??',
					'&#256;'	 => '??',
					'&#257;'	 => '??',
					'&#258;'	 => '??',
					'&#259;'	 => '??',
					'&#260;'	 => '??',
					'&#261;'	 => '??',
					'&#262;'	 => '??',
					'&#263;'	 => '??',
					'&#264;'	 => '??',
					'&#265;'	 => '??',
					'&#266;'	 => '??',
					'&#267;'	 => '??',
					'&#268;'	 => '??',
					'&#269;'	 => '??',
					'&#270;'	 => '??',
					'&#271;'	 => '??',
					'&#272;'	 => '??',
					'&#273;'	 => '??',
					'&#274;'	 => '??',
					'&#275;'	 => '??',
					'&#276;'	 => '??',
					'&#277;'	 => '??',
					'&#278;'	 => '??',
					'&#279;'	 => '??',
					'&#280;'	 => '??',
					'&#281;'	 => '??',
					'&#282;'	 => '??',
					'&#283;'	 => '??',
					'&#284;'	 => '??',
					'&#285;'	 => '??',
					'&#286;'	 => '??',
					'&#287;'	 => '??',
					'&#288;'	 => '??',
					'&#289;'	 => '??',
					'&#290;'	 => '??',
					'&#291;'	 => '??',
					'&#292;'	 => '??',
					'&#293;'	 => '??',
					'&#294;'	 => '??',
					'&#295;'	 => '??',
					'&#296;'	 => '??',
					'&#297;'	 => '??',
					'&#298;'	 => '??',
					'&#299;'	 => '??',
					'&#300;'	 => '??',
					'&#301;'	 => '??',
					'&#302;'	 => '??',
					'&#303;'	 => '??',
					'&#304;'	 => '??',
					'&#305;'	 => '??',
					'&#306;'	 => '??',
					'&#307;'	 => '??',
					'&#308;'	 => '??',
					'&#309;'	 => '??',
					'&#310;'	 => '??',
					'&#311;'	 => '??',
					'&#312;'	 => '??',
					'&#313;'	 => '??',
					'&#314;'	 => '??',
					'&#315;'	 => '??',
					'&#316;'	 => '??',
					'&#317;'	 => '??',
					'&#318;'	 => '??',
					'&#319;'	 => '??',
					'&#320;'	 => '??',
					'&#321;'	 => '??',
					'&#322;'	 => '??',
					'&#323;'	 => '??',
					'&#324;'	 => '??',
					'&#325;'	 => '??',
					'&#326;'	 => '??',
					'&#327;'	 => '??',
					'&#328;'	 => '??',
					'&#329;'	 => '??',
					'&#330;'	 => '??',
					'&#331;'	 => '??',
					'&#332;'	 => '??',
					'&#333;'	 => '??',
					'&#334;'	 => '??',
					'&#335;'	 => '??',
					'&#336;'	 => '??',
					'&#337;'	 => '??',
					'&#338;'	 => '??',
					'&#339;'	 => '??',
					'&#340;'	 => '??',
					'&#341;'	 => '??',
					'&#342;'	 => '??',
					'&#343;'	 => '??',
					'&#344;'	 => '??',
					'&#345;'	 => '??',
					'&#346;'	 => '??',
					'&#347;'	 => '??',
					'&#348;'	 => '??',
					'&#349;'	 => '??',
					'&#34;'		 => '"',
					'&#350;'	 => '??',
					'&#351;'	 => '??',
					'&#352;'	 => '??',
					'&#353;'	 => '??',
					'&#354;'	 => '??',
					'&#355;'	 => '??',
					'&#356;'	 => '??',
					'&#357;'	 => '??',
					'&#358;'	 => '??',
					'&#359;'	 => '??',
					'&#360;'	 => '??',
					'&#361;'	 => '??',
					'&#362;'	 => '??',
					'&#363;'	 => '??',
					'&#364;'	 => '??',
					'&#365;'	 => '??',
					'&#366;'	 => '??',
					'&#367;'	 => '??',
					'&#368;'	 => '??',
					'&#369;'	 => '??',
					'&#370;'	 => '??',
					'&#371;'	 => '??',
					'&#372;'	 => '??',
					'&#373;'	 => '??',
					'&#374;'	 => '??',
					'&#375;'	 => '??',
					'&#377;'	 => '??',
					'&#378;'	 => '??',
					'&#379;'	 => '??',
					'&#380;'	 => '??',
					'&#381;'	 => '??',
					'&#382;'	 => '??',
					'&#383;'	 => '??',
					'&#38;'		 => '&',
					'&#39;'		 => '\'',
					'&#402;'	 => '??',
					'&#439;'	 => '??',
					'&#452;'	 => '??',
					'&#453;'	 => '??',
					'&#454;'	 => '??',
					'&#455;'	 => '??',
					'&#456;'	 => '??',
					'&#457;'	 => '??',
					'&#458;'	 => '??',
					'&#459;'	 => '??',
					'&#460;'	 => '??',
					'&#478;'	 => '??',
					'&#479;'	 => '??',
					'&#484;'	 => '??',
					'&#485;'	 => '??',
					'&#486;'	 => '??',
					'&#487;'	 => '??',
					'&#488;'	 => '??',
					'&#489;'	 => '??',
					'&#494;'	 => '??',
					'&#495;'	 => '??',
					'&#497;'	 => '??',
					'&#499;'	 => '??',
					'&#500;'	 => '??',
					'&#501;'	 => '??',
					'&#506;'	 => '??',
					'&#507;'	 => '??',
					'&#508;'	 => '??',
					'&#509;'	 => '??',
					'&#510;'	 => '??',
					'&#511;'	 => '??',
					'&#60;'		 => '<',
					'&#62;'		 => '>',
					'&#636;'	 => '??',
					'&#64257;' => '???',
					'&#64258;' => '???',
					'&#658;'	 => '??',
					'&#728;'	 => '??',
					'&#729;'	 => '??',
					'&#730;'	 => '??',
					'&#731;'	 => '??',
					'&#732;'	 => '??',
					'&#733;'	 => '??',
					'&#7682;'	 => '???',
					'&#7683;'	 => '???',
					'&#7690;'	 => '???',
					'&#7691;'	 => '???',
					'&#7696;'	 => '???',
					'&#7697;'	 => '???',
					'&#7710;'	 => '???',
					'&#7711;'	 => '???',
					'&#7728;'	 => '???',
					'&#7729;'	 => '???',
					'&#7744;'	 => '???',
					'&#7745;'	 => '???',
					'&#7766;'	 => '???',
					'&#7767;'	 => '???',
					'&#7776;'	 => '???',
					'&#7777;'	 => '???',
					'&#7786;'	 => '???',
					'&#7787;'	 => '???',
					'&#7808;'	 => '???',
					'&#7809;'	 => '???',
					'&#7810;'	 => '???',
					'&#7811;'	 => '???',
					'&#7812;'	 => '???',
					'&#7813;'	 => '???',
					'&#7922;'	 => '???',
					'&#7923;'	 => '???',
					'&#8213;'	 => '???',
					'&#8227;'	 => '???',
					'&#8252;'	 => '???',
					'&#8254;'	 => '???',
					'&#8260;'	 => '???',
					'&#8319;'	 => '???',
					'&#8355;'	 => '???',
					'&#8356;'	 => '???',
					'&#8359;'	 => '???',
					'&#8453;'	 => '???',
					'&#8470;'	 => '???',
					'&#8539;'	 => '???',
					'&#8540;'	 => '???',
					'&#8541;'	 => '???',
					'&#8542;'	 => '???',
					'&#8592;'	 => '???',
					'&#8593;'	 => '???',
					'&#8594;'	 => '???',
					'&#8595;'	 => '???',
					'&#8706;'	 => '???',
					'&#8710;'	 => '???',
					'&#8719;'	 => '???',
					'&#8721;'	 => '???',
					'&#8729;'	 => '???',
					'&#8730;'	 => '???',
					'&#8734;'	 => '???',
					'&#8735;'	 => '???',
					'&#8745;'	 => '???',
					'&#8747;'	 => '???',
					'&#8776;'	 => '???',
					'&#8800;'	 => '???',
					'&#8801;'	 => '???',
					'&#8804;'	 => '???',
					'&#8805;'	 => '???',
					'&#94;'		 => '^',
					'&#9792;'	 => '???',
					'&#9794;'	 => '???',
					'&#9824;'	 => '???',
					'&#9827;'	 => '???',
					'&#9829;'	 => '???',
					'&#9830;'	 => '???',
					'&#9833;'	 => '???',
					'&#9834;'	 => '???',
					'&#9836;'	 => '???',
					'&#9837;'	 => '???',
					'&#9839;'	 => '???',
					'&498;'		 => '??',
					'&AElig;'	 => '??',
					'&Aacute;' => '??',
					'&Acirc;'	 => '??',
					'&Agrave;' => '??',
					'&Aring;'	 => '??',
					'&Atilde;' => '??',
					'&Auml;'	 => '??',
					'&Ccedil;' => '??',
					'&Dagger;' => '???',
					'&ETH;'		 => '??',
					'&Eacute;' => '??',
					'&Ecirc;'	 => '??',
					'&Egrave;' => '??',
					'&Euml;'	 => '??',
					'&Iacute;' => '??',
					'&Icirc;'	 => '??',
					'&Igrave;' => '??',
					'&Iuml;'	 => '??',
					'&Ntilde;' => '??',
					'&OElig;'	 => '??',
					'&Oacute;' => '??',
					'&Ocirc;'	 => '??',
					'&Ograve;' => '??',
					'&Oslash;' => '??',
					'&Otilde;' => '??',
					'&Ouml;'	 => '??',
					'&THORN;'	 => '??',
					'&Uacute;' => '??',
					'&Ucirc;'	 => '??',
					'&Ugrave;' => '??',
					'&Uuml;'	 => '??',
					'&Yacute;' => '??',
					'&Yuml;'	 => '??',
					'&aacute;' => '??',
					'&acirc;'	 => '??',
					'&acute;'	 => '??',
					'&aelig;'	 => '??',
					'&agrave;' => '??',
					'&amp;'		 => '&',
					'&aring;'	 => '??',
					'&atilde;' => '??',
					'&auml;'	 => '??',
					'&brvbar;' => '??',
					'&ccedil;' => '??',
					'&cedil;'	 => '??',
					'&cent;'	 => '??',
					'&clubs;'	 => '???',
					'&copy;'	 => '??',
					'&curren;' => '??',
					'&dagger;' => '???',
					'&darr;'	 => '???',
					'&dbquo;'	 => '???',
					'&deg;'		 => '??',
					'&diams;'	 => '???',
					'&divide;' => '??',
					'&eacute;' => '??',
					'&ecirc;'	 => '??',
					'&egrave;' => '??',
					'&eth;'		 => '??',
					'&euml;'	 => '??',
					'&euro;'	 => '???',
					'&frac12;' => '??',
					'&frac14;' => '??',
					'&frac34;' => '??',
					'&gt;'		 => '>',
					'&hearts;' => '???',
					'&iacute;' => '??',
					'&icirc;'	 => '??',
					'&iexcl;'	 => '??',
					'&igrave;' => '??',
					'&iquest;' => '??',
					'&iuml;'	 => '??',
					'&laquo;'	 => '??',
					'&larr;'	 => '???',
					'&ldquo;'	 => '???',
					'&lsaquo;' => '???',
					'&lsquo;'	 => '???',
					'&lt;'		 => '<',
					'&macr;'	 => '??',
					'&mdash;'	 => '???',
					'&micro;'	 => '??',
					'&middot;' => '??',
					'&ndash;'	 => '???',
					'&not;'		 => '??',
					'&ntilde;' => '??',
					'&oacute;' => '??',
					'&ocirc;'	 => '??',
					'&oelig;'	 => '??',
					'&ograve;' => '??',
					'&oline;'	 => '???',
					'&ordf;'	 => '??',
					'&ordm;'	 => '??',
					'&oslash;' => '??',
					'&otilde;' => '??',
					'&ouml;'	 => '??',
					'&para;'	 => '??',
					'&permil;' => '???',
					'&plusmn;' => '??',
					'&pound;'	 => '??',
					'&quot;'	 => '"',
					'&raquo;'	 => '??',
					'&rarr;'	 => '???',
					'&rdquo;'	 => '???',
					'&reg;'		 => '??',
					'&rsaquo;' => '???',
					'&rsquo;'	 => '???',
					'&sbquo;'	 => '???',
					'&sect;'	 => '??',
					'&shy;'		 => '??',
					'&spades;' => '???',
					'&sup1;'	 => '??',
					'&sup2;'	 => '??',
					'&sup3;'	 => '??',
					'&szlig;'	 => '??',
					'&thorn;'	 => '??',
					'&tilde'	 => '??',
					'&tilde;'	 => '??',
					'&times;'	 => '??',
					'&trade;'	 => '???',
					'&uacute;' => '??',
					'&uarr;'	 => '???',
					'&ucirc;'	 => '??',
					'&ugrave;' => '??',
					'&uml;'		 => '??',
					'&uuml;'	 => '??',
					'&yacute;' => '??',
					'&yen;'		 => '??',
					'&yuml;'	 => '??'
	);

	/**
	 * Class instantiation function
	 *
	 * @return xmpMetadata_options
	 */
	function __construct() {
		setOptionDefault('xmpMetadata_suffix', 'xmp');
	}

	/**
	 * Option interface
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		global $_zp_supported_images, $_zp_extra_filetypes;
		$list = $_zp_supported_images;
		foreach (array('gif', 'wbmp') as $suffix) {
			$key = array_search($suffix, $list);
			if ($key !== false)
				unset($list[$key]);
		}
		natcasesort($list);
		$types = array();
		foreach ($_zp_extra_filetypes as $suffix => $type) {
			if ($type == 'Video')
				$types[] = $suffix;
		}
		natcasesort($types);
		$list = array_merge($list, $types);
		$listi = array();
		foreach ($list as $suffix) {
			$listi[$suffix] = 'xmpMetadata_examine_images_' . $suffix;
		}
		return array(gettext('Sidecar file extension')	 => array('key'	 => 'xmpMetadata_suffix', 'type' => OPTION_TYPE_TEXTBOX,
										'desc' => gettext('The plugin will look for files with <em>image_name.extension</em> and extract XMP metadata from them into the <em>image_name</em> record.')),
						gettext('Process extensions')			 => array('key'				 => 'xmpMetadata_examine_imagefile', 'type'			 => OPTION_TYPE_CHECKBOX_UL,
										'checkboxes' => $listi,
										'desc'			 => gettext('If no sidecar file exists and the extension is enabled, the plugin will search within that type <em>image</em> file for an <code>XMP</code> block. <strong>Warning</strong> do not set this option unless you require it. Searching image files can be computationally intensive.'))
		);
	}

	/**
	 * Custom option handler
	 *
	 * @param string $option
	 * @param mixed $currentValue
	 */
	function handleOption($option, $currentValue) {

	}

	/**
	 * Parses xmp metadata for interesting tags
	 *
	 * @param string $xmpdata
	 * @return array
	 */
	private static function extract($xmpdata) {
		$desiredtags = array(
						'EXIFLensType'					 => '<aux:Lens>',
						'EXIFLensInfo'					 => '<aux:LensInfo>',
						'EXIFArtist'						 => '<dc:creator>',
						'IPTCCopyright'					 => '<dc:rights>',
						'IPTCImageCaption'			 => '<dc:description>',
						'IPTCObjectName'				 => '<dc:title>',
						'IPTCKeywords'					 => '<dc:subject>',
						'EXIFExposureTime'			 => '<exif:ExposureTime>',
						'EXIFFNumber'						 => '<exif:FNumber>',
						'EXIFAperatureValue'		 => '<exif:ApertureValue>',
						'EXIFExposureProgram'		 => '<exif:ExposureProgram>',
						'EXIFISOSpeedRatings'		 => '<exif:ISOSpeedRatings>',
						'EXIFDateTimeOriginal'	 => '<exif:DateTimeOriginal>',
						'EXIFExposureBiasValue'	 => '<exif:ExposureBiasValue>',
						'EXIFGPSLatitude'				 => '<exif:GPSLatitude>',
						'EXIFGPSLongitude'			 => '<exif:GPSLongitude>',
						'EXIFGPSAltitude'				 => '<exif:GPSAltitude>',
						'EXIFGPSAltituedRef'		 => '<exif:GPSAltitudeRef>',
						'EXIFMeteringMode'			 => '<exif:MeteringMode>',
						'EXIFFocalLength'				 => '<exif:FocalLength>',
						'EXIFContrast'					 => '<exif:Contrast>',
						'EXIFSharpness'					 => '<exif:Sharpness>',
						'EXIFExposureTime'			 => '<exif:ShutterSpeedValue>',
						'EXIFSaturation'				 => '<exif:Saturation>',
						'EXIFWhiteBalance'			 => '<exif:WhiteBalance>',
						'IPTCLocationCode'			 => '<Iptc4xmpCore:CountryCode>',
						'IPTCSubLocation'				 => '<Iptc4xmpCore:Location>',
						'rating'								 => '<MicrosoftPhoto:Rating>',
						'IPTCSource'						 => '<photoshop:Source>',
						'IPTCCity'							 => '<photoshop:City>',
						'IPTCState'							 => '<photoshop:State>',
						'IPTCLocationName'			 => '<photoshop:Country>',
						'IPTCImageHeadline'			 => '<photoshop:Headline>',
						'IPTCImageCredit'				 => '<photoshop:Credit>',
						'EXIFMake'							 => '<tiff:Make>',
						'EXIFModel'							 => '<tiff:Model>',
						'EXIFOrientation'				 => '<tiff:Orientation>',
						'EXIFImageWidth'				 => '<tiff:ImageWidth>',
						'EXIFImageHeight'				 => '<tiff:ImageLength>',
						'owner'									 => '<zp:Owner>',
						'thumb'									 => '<zp:Thumbnail>',
						'watermark'							 => '<zp:Watermark>',
						'watermark_use'					 => '<zp:Watermark_use>',
						'watermark_thumb'				 => '<zp:Watermark_thumb>',
						'custom_data'						 => '<zp:CustomData',
						'codeblock'							 => '<zp:Codeblock>'
		);
		$xmp_parsed = array();
		while (!empty($xmpdata)) {
			$s = strpos($xmpdata, '<');
			$e = strpos($xmpdata, '>', $s);
			$tag = substr($xmpdata, $s, $e - $s + 1);
			$xmpdata = substr($xmpdata, $e + 1);
			$key = array_search($tag, $desiredtags);
			if ($key !== false) {
				$close = str_replace('<', '</', $tag);
				$e = strpos($xmpdata, $close);
				$meta = trim(substr($xmpdata, 0, $e));
				$xmpdata = substr($xmpdata, $e + strlen($close));
				if (strpos($meta, '<') === false) {
					$xmp_parsed[$key] = self::decode($meta);
				} else {
					$elements = array();
					while (!empty($meta)) {
						$s = strpos($meta, '<');
						$e = strpos($meta, '>', $s);
						$tag = substr($meta, $s, $e - $s + 1);
						$meta = substr($meta, $e + 1);
						if (strpos($tag, 'rdf:li') !== false) {
							$e = strpos($meta, '</rdf:li>');
							$elements[] = self::decode(trim(substr($meta, 0, $e)));
							$meta = substr($meta, $e + 9);
						}
					}
					$xmp_parsed[$key] = $elements;
				}
			} else { // look for shorthand elements
				if (strpos($tag, '<rdf:Description') !== false) {
					$meta = substr($tag, 17); // strip off the description tag leaving the elements
					while (preg_match('/^[a-zA-z0-9_]+\:[a-zA-z0-9_]+\=".*"/', $meta, $element)) {
						$item = $element[0];
						$meta = trim(substr($meta, strlen($item)));
						$i = strpos($item, '=');
						$tag = '<' . substr($item, 0, $i) . '>';
						$v = self::decode(trim(substr($item, $i + 2, -1)));
						$key = array_search($tag, $desiredtags);
						if ($key !== false) {
							$xmp_parsed[$key] = $v;
						}
					}
				}
			}
		}
		return ($xmp_parsed);
	}

	/**
	 * insures that the metadata is a string
	 *
	 * @param mixed $meta
	 * @return string
	 */
	private static function to_string($meta) {
		if (is_array($meta)) {
			$meta = implode(',', $meta);
		}
		return trim($meta);
	}

	/**
	 * Filter called when an album object is instantiated
	 * sets the sidecars to include xmp files
	 *
	 * @param $album album object
	 * @return $object
	 */
	static function album_instantiate($album) {
		$album->sidecars[XMP_EXTENSION] = XMP_EXTENSION;
		return $album;
	}

	/**
	 * Filter for handling album objects
	 *
	 * @param object $album
	 * @return object
	 */
	static function new_album($album) {
		$metadata_path = dirname($album->localpath) . '/' . basename($album->localpath) . '*';
		$files = safe_glob($metadata_path);
		if (count($files) > 0) {
			foreach ($files as $file) {
				if (strtolower(getSuffix($file)) == XMP_EXTENSION) {
					$source = file_get_contents($file);
					$metadata = self::extract($source);
					if (array_key_exists('IPTCImageCaption', $metadata)) {
						$album->setDesc(self::to_string($metadata['IPTCImageCaption']));
					}
					if (array_key_exists('IPTCImageHeadline', $metadata)) {
						$album->setTitle(self::to_string($metadata['IPTCImageHeadline']));
					}
					if (array_key_exists('IPTCLocationName', $metadata)) {
						$album->setLocation(self::to_string($metadata['IPTCLocationName']));
					}
					if (array_key_exists('IPTCKeywords', $metadata)) {
						$tags = $metadata['IPTCKeywords'];
						if (!is_array($tags)) {
							$tags = explode(',', $tags);
						}
						$album->setTags($tags);
					}
					if (array_key_exists('EXIFDateTimeOriginal', $metadata)) {
						$album->setDateTime($metadata['EXIFDateTimeOriginal']);
					}
					if (array_key_exists('thumb', $metadata)) {
						$album->setThumb($metadata['thumb']);
					}
					if (array_key_exists('owner', $metadata)) {
						$album->setOwner($metadata['owner']);
					}
					if (array_key_exists('custom_data', $metadata)) {
						$album->setCustomData($metadata['custom_data']);
					}
					if (array_key_exists('codeblock', $metadata)) {
						$album->setCodeblock($metadata['codeblock']);
					}
					if (array_key_exists('watermark', $metadata)) {
						$album->setWatermark($metadata['watermark']);
					}
					if (array_key_exists('watermark_thumb', $metadata)) {
						$album->setWatermarkThumb($metadata['watermark_thumb']);
					}
					if (array_key_exists('rating', $metadata)) {
						$v = min(getoption('rating_stars_count'), $metadata['rating']) * min(1, getOption('rating_split_stars'));
						$album->set('total_value', $v);
						$album->set('rating', $v);
						$album->set('total_votes', 1);
					}
					$album->save();
					break;
				}
			}
			return $album;
		}
	}

	/**
	 * Finds and returns xmp metadata
	 *
	 * @param int $j
	 * @return string
	 */
	private static function extractXMP($f) {
		if (preg_match('~<.*?xmpmeta~', $f, $m)) {
			$open = $m[0];
			$close = str_replace('<', '</', $open);
			$j = strpos($f, $open);
			if ($j !== false) {
				$k = strpos($f, $close, $j + 4);
				$meta = substr($f, $j, $k + 14 - $j);
				$l = 0;
				return $meta;
			}
		}
		return false;
	}

	/**
	 * convert a fractional representation to something more user friendly
	 *
	 * @param $element string
	 * @return string
	 */
	private static function rationalNum($element) {
		// deal with the fractional representation
		$n = explode('/', $element);
		$v = sprintf('%f', $n[0] / $n[1]);
		for ($i = strlen($v) - 1; $i > 1; $i--) {
			if ($v{$i} != '0')
				break;
		}
		if ($v{$i} == '.')
			$i--;
		return substr($v, 0, $i + 1);
	}

	private static function encode($str) {
		return strtr($str, array_flip(self::$XML_trans));
	}

	private static function decode($str) {
		return strtr($str, self::$XML_trans);
	}

	static function image_instantiate($image) {
		$image->sidecars[XMP_EXTENSION] = XMP_EXTENSION;
		return $image;
	}

	/**
	 * Filter for handling image objects
	 *
	 * @param object $image
	 * @return object
	 */
	static function new_image($image) {
		global $_zp_exifvars;
		$source = '';
		$metadata_path = '';
		$files = safe_glob(substr($image->localpath, 0, strrpos($image->localpath, '.')) . '.*');
		if (count($files) > 0) {
			foreach ($files as $file) {
				if (strtolower(getSuffix($file)) == XMP_EXTENSION) {
					$metadata_path = $file;
					break;
				}
			}
		}
		if (!empty($metadata_path)) {
			$source = self::extractXMP(file_get_contents($metadata_path));
		} else if (getOption('xmpMetadata_examine_images_' . strtolower(substr(strrchr($image->localpath, "."), 1)))) {
			$f = file_get_contents($image->localpath);
			$l = filesize($image->localpath);
			$abort = 0;
			$i = 0;
			while ($i < $l && $abort < 200 && !$source) {
				$tag = bin2hex(substr($f, $i, 2));
				$size = hexdec(bin2hex(substr($f, $i + 2, 2)));
				switch ($tag) {
					case 'ffe1': // EXIF
					case 'ffe2': // EXIF extension
					case 'fffe': // COM
					case 'ffe0': // IPTC marker
						$source = self::extractXMP($f);
						$i = $i + $size + 2;
						$abort = 0;
						break;
					default:
						if ($f{$i} == '<') {
							$source = self::extractXMP($f);
						}
						$i = $i + 1;
						$abort++;
						break;
				}
			}
		}
		if (!empty($source)) {
			$metadata = self::extract($source);
			$image->set('hasMetadata', count($metadata > 0));
			foreach ($metadata as $field => $element) {
				if (array_key_exists($field, $_zp_exifvars)) {
					if (!$_zp_exifvars[$field][5]) {
						continue; //	the field has been disabled
					}
				}
				$v = self::to_string($element);

				switch ($field) {
					case 'EXIFDateTimeOriginal':
						$image->setDateTime($element);
						break;
					case 'IPTCImageCaption':
						$image->setDesc($v);
						break;
					case 'IPTCCity':
						$image->setCity($v);
						break;
					case 'IPTCState':
						$image->setState($v);
						break;
					case 'IPTCLocationName':
						$image->setCountry($v);
						break;
					case 'IPTCSubLocation':
						$image->setLocation($v);
						break;
					case 'EXIFExposureTime':
						$v = formatExposure(self::rationalNum($element));
						break;
					case 'EXIFFocalLength':
						$v = self::rationalNum($element) . ' mm';
						break;
					case 'EXIFAperatureValue':
					case 'EXIFFNumber':
						$v = 'f/' . self::rationalNum($element);
						break;
					case 'EXIFExposureBiasValue':
					case 'EXIFGPSAltitude':
						$v = self::rationalNum($element);
						break;
					case 'EXIFGPSLatitude':
					case 'EXIFGPSLongitude':
						$ref = substr($element, -1, 1);
						$image->set($field . 'Ref', $ref);
						$element = substr($element, 0, -1);
						$n = explode(',', $element);
						if (count($n) == 3) {
							$v = $n[0] + ($n[1] + ($n[2] / 60) / 60);
						} else {
							$v = $n[0] + $n[1] / 60;
						}
						break;
					case 'rating':
						$v = min(getoption('rating_stars_count'), $v) * min(1, getOption('rating_split_stars'));
						$image->set('total_value', $v);
						$image->set('total_votes', 1);
						break;
					case 'watermark':
					case 'watermark_use':
					case 'custom_data':
					case 'codeblock':
					case 'owner':
						$image->set($field, $v);
						break;
					case 'IPTCKeywords':
						if (!is_array($element)) {
							$element = explode(',', $element);
						}
						$image->setTags($element);
						break;
				}
				if (array_key_exists($field, $_zp_exifvars)) {
					$image->set($field, $v);
				}
			}
			$image->save();
		}
		return $image;
	}

	static function sidecars($types) {
		$types[] = XMP_EXTENSION;
		return $types;
	}

	static function putXMP($object, $prefix) {
		if (isset($_POST['xmpMedadataPut_' . $prefix])) {
			self::publish($object);
		}
		return $object;
	}

	static function publish($object) {
		$desiredtags = array('copyright'				 => '<dc:rights>',
						'desc'						 => '<dc:description>',
						'title'						 => '<dc:title>',
						'tags'						 => '<dc:subject>',
						'location'				 => '<Iptc4xmpCore:Location>',
						'city'						 => '<photoshop:City>',
						'state'						 => '<photoshop:State>',
						'country'					 => '<photoshop:Country>',
						'title'						 => '<photoshop:Headline>',
						'credit'					 => '<photoshop:Credit>',
						'thumb'						 => '<zp:Thumbnail>',
						'owner'						 => '<zp:Owner>',
						'watermark'				 => '<zp:Watermark>',
						'watermark_use'		 => '<zp:Watermark_use>',
						'watermark_thumb'	 => '<zp:Watermark_thumb>',
						'custom_data'			 => '<zp:CustomData>',
						'codeblock'				 => '<zp:Codeblock>',
						'date'						 => '<exif:DateTimeOriginal>',
						'rating'					 => '<MicrosoftPhoto:Rating>'
		);
		$process = array('dc', 'Iptc4xmpCore', 'photoshop', 'xap');
		if (isAlbumClass($object)) {
			$file = rtrim($object->localpath, '/');
			$file .= '.xmp';
		} else {
			$file = stripSuffix($object->localpath) . '.xmp';
		}
		@chmod($file, 0777);
		$f = fopen($file, 'w');
		fwrite($f, '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 4.2-c020 1.124078, Tue Sep 11 2007 23:21:40 ">' . "\n");
		fwrite($f, ' <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">' . "\n");
		$last_element = $special = $output = false;
		foreach ($desiredtags as $field => $elementXML) {
			$elementXML = substr($elementXML, 1, -1);
			if ($last_element != $elementXML) {
				if ($output) {
					fwrite($f, '  </rdf:Description>' . "\n");
					fwrite($f, '  <rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n");
				}
				$last_element = $elementXML;
				$output = false;
			}
			$v = self::encode($object->get($field));
			$tag = $elementXML;
			switch ($elementXML) {
				case 'dc:creator':
					$special = 'rdf:Seq';
					$tag = 'rdf:li';
					if ($v) {
						fwrite($f, "   <$elementXML>\n");
						fwrite($f, "    <$special>\n");
						fwrite($f, "     <$tag>$v</$tag>\n");
						fwrite($f, "    </$special>\n");
						fwrite($f, "   </$elementXML>\n");
						$output = true;
					}
					break;
				case 'dc:rights':
				case 'xapRights:UsageTerms':
					$special = 'rdf:Alt';
					$tag = 'rdf:li';
					if ($v) {
						fwrite($f, "   <$elementXML>\n");
						fwrite($f, "    <$special>\n");
						fwrite($f, "     <$tag>$v</$tag>\n");
						fwrite($f, "    </$special>\n");
						fwrite($f, "   </$elementXML>\n");
						$output = true;
					}
					break;
				case 'dc:subject':
					$tags = $object->getTags();
					if (!empty($tags)) {
						fwrite($f, "   <$elementXML>\n");
						fwrite($f, "    <rdf:Bag>\n");
						foreach ($tags as $tag) {
							fwrite($f, "     <rdf:li>" . self::encode($tag) . "</rdf:li>\n");
						}
						fwrite($f, "    </rdf:Bag>\n");
						fwrite($f, "   </$elementXML>\n");
						$output = true;
					}
					break;
				default:
					if ($v) {
						fwrite($f, "   <$tag>$v</$tag>\n");
						$output = true;
					}
					break;
			}
		}
		fwrite($f, '  </rdf:Description>' . "\n");
		fwrite($f, ' </rdf:RDF>' . "\n");
		fwrite($f, '</x:xmpmeta>' . "\n");
		fclose($f);
		clearstatcache();
		@chmod($file, FILE_MOD);
		return gettext('Metadata exported');
	}

	static function create($html, $object, $prefix) {
		if ($html)
			$html .= '<hr />';
		$html .= '<label><input type="checkbox" name="xmpMedadataPut_' . $prefix . '" value="1" /> ' . gettext('Export metadata info to XMP sidecar.') . '</label>';
		return $html;
	}

	static function bulkActions($actions) {
		return array_merge($actions, array(gettext('Export Metadata') => 'xmpMetadataPublish'));
	}

}

?>