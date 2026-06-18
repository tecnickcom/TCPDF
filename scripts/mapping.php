<?php

declare(strict_types=1);

/**
 * Delegation mapping table generator for the TCPDF compatibility facade.
 *
 * The TCPDF_METHOD_MAP constant below is the single source of truth for the
 * facade work: one entry per public TCPDF method with the implementation
 * family, the tc-lib-pdf delegation target, the current status and notes.
 *
 * Statuses:
 *   stub             not implemented yet (empty body)
 *   delegated        direct delegation to a tc-lib method
 *   adapter          delegation with argument/return translation
 *   shim             reimplemented locally (state layer or no usable target)
 *   intentional-noop legacy behavior deliberately dropped (documented)
 *   blocked          cannot be delegated yet; reason recorded
 *
 * The script verifies the map against the real TCPDF class via reflection
 * (every public method mapped, no unknown entries) and regenerates MAPPING.md.
 * It exits non-zero when the map and the class are out of sync.
 *
 * Usage: php scripts/mapping.php
 *
 * @package com.tecnick.tcpdf
 */

error_reporting(E_ALL);

require_once dirname(__DIR__) . '/tcpdf.php';

/** Implementation family display names. */
const TCPDF_FAMILIES = [
    'bootstrap' => 'Bootstrap/Lifecycle/Output',
    'pagestate' => 'Document/Page state',
    'header' => 'Header/Footer',
    'text' => 'Text/Font/metrics',
    'html' => 'HTML/CSS',
    'graphics' => 'Graphics/Transforms/Images',
    'forms' => 'Forms/Annotations/Links/JS',
    'security' => 'Signatures/Security',
    'barcode' => 'Barcodes',
    'toc' => 'Columns/TOC/Templates/Regions',
];

/**
 * Map of every public TCPDF method: family, tc-lib target, status, notes.
 * Order follows the declaration order in tcpdf.php.
 *
 * @var array<string, array{string, string, 'delegated'|'adapter'|'shim'|'intentional-noop'|'blocked'|'stub', string}>
 */
const TCPDF_METHOD_MAP = [
    // --- Bootstrap / lifecycle -------------------------------------------------
    '__construct' => [
        'bootstrap',
        '\Com\Tecnick\Pdf\Tcpdf::__construct',
        'adapter',
        'map (orientation,unit,format,unicode,encoding,diskcache,pdfa) onto (unit,isunicode,subsetfont,compress,mode); orientation/format become per-page defaults',
    ],
    '__destruct' => ['bootstrap', '-', 'shim', 'release delegate'],
    '__get' => [
        'bootstrap',
        '-',
        'shim',
        'legacy protected properties (AutoPageBreak, lMargin, ...) read by user subclasses',
    ],
    '__isset' => ['bootstrap', '-', 'shim', 'companion of __get'],
    'Error' => ['bootstrap', '-', 'shim', 'throw/die per K_TCPDF_THROW_EXCEPTION_ERROR'],
    'Open' => ['bootstrap', '-', 'shim', 'legacy state flag only'],
    'Close' => [
        'bootstrap',
        '-',
        'adapter',
        'renders deferred header/footer decorations for all pages, then builds the PDF',
    ],
    'Output' => [
        'bootstrap',
        'Output::getOutPDFString/savePDF/renderPDF/downloadPDF',
        'adapter',
        'dest switch I/D/F/S/FI/FD/E',
    ],
    'getPDFData' => ['bootstrap', 'Output::getOutPDFString', 'delegated', ''],
    'setCompression' => [
        'bootstrap',
        'Tcpdf::$compress',
        'intentional-noop',
        'compression is always enabled at engine construction; legacy off-switch dropped',
    ],
    'setSRGBmode' => ['bootstrap', 'MetaInfo::setSRGB', 'delegated', ''],
    'setDocInfoUnicode' => ['bootstrap', '-', 'intentional-noop', 'tc-lib handles doc-info encoding internally'],
    'setTitle' => ['bootstrap', 'MetaInfo::setTitle', 'delegated', ''],
    'setSubject' => ['bootstrap', 'MetaInfo::setSubject', 'delegated', ''],
    'setAuthor' => ['bootstrap', 'MetaInfo::setAuthor', 'delegated', ''],
    'setKeywords' => ['bootstrap', 'MetaInfo::setKeywords', 'delegated', ''],
    'setCreator' => ['bootstrap', 'MetaInfo::setCreator', 'delegated', ''],
    'setAllowLocalFiles' => [
        'bootstrap',
        'Tcpdf::$fileOptions',
        'intentional-noop',
        'engine file access is constructor-bound; facade passes legacy-compatible allowed paths',
    ],
    'setDisplayMode' => ['bootstrap', 'Tcpdf::setDisplayMode', 'delegated', ''],
    'setPDFVersion' => ['bootstrap', 'Base::setPDFVersion', 'delegated', ''],
    'setViewerPreferences' => ['bootstrap', 'MetaInfo::setViewerPreferences', 'adapter', ''],
    'setExtraXMP' => ['bootstrap', 'MetaInfo::setCustomXMP', 'adapter', ''],
    'setExtraXMPRDF' => ['bootstrap', 'MetaInfo::setCustomXMP', 'adapter', ''],
    'setExtraXMPPdfaextension' => ['bootstrap', 'MetaInfo::setCustomXMP', 'adapter', ''],
    'setDocCreationTimestamp' => [
        'bootstrap',
        'MetaInfo::$doctime',
        'shim',
        'facade state; the engine stamps output with its own clock',
    ],
    'setDocModificationTimestamp' => [
        'bootstrap',
        'MetaInfo::$docmodtime',
        'shim',
        'facade state; the engine stamps output with its own clock',
    ],
    'getDocCreationTimestamp' => ['bootstrap', 'MetaInfo::$doctime', 'shim', ''],
    'getDocModificationTimestamp' => ['bootstrap', 'MetaInfo::$docmodtime', 'shim', ''],
    'setLanguageArray' => [
        'bootstrap',
        'Tcpdf::setLanguageArray',
        'adapter',
        'English defaults built in; overridable via K_TCPDF_DEFAULT_LANGUAGE or this method',
    ],
    'setSpacesRE' => ['bootstrap', 'Tcpdf::setSpaceRegexp', 'delegated', ''],
    'startTransaction' => [
        'bootstrap',
        '-',
        'shim',
        'deep snapshot via serialize/unserialize of the facade+engine graph',
    ],
    'commitTransaction' => ['bootstrap', '-', 'shim', ''],
    'rollbackTransaction' => ['bootstrap', '-', 'shim', ''],

    // --- Document / page state -------------------------------------------------
    'setPageUnit' => ['pagestate', '-', 'shim', 're-creates the engine; only valid before the first page'],
    'setPageOrientation' => ['pagestate', 'Page (per-page data)', 'shim', ''],
    'setPage' => ['pagestate', 'Page::setCurrentPage', 'adapter', ''],
    'lastPage' => ['pagestate', 'Page::setCurrentPage', 'adapter', ''],
    'getPage' => ['pagestate', 'Page::getPageId', 'adapter', '1-based vs 0-based'],
    'getNumPages' => ['pagestate', 'Page::getPages', 'adapter', ''],
    'AddPage' => ['pagestate', 'Text::addPage', 'adapter', 'apply facade margins/format; re-emit font+colors'],
    'endPage' => ['pagestate', '-', 'shim', 'headers/footers deferred to Close(); resets tocpage flag'],
    'startPage' => ['pagestate', 'Text::addPage', 'adapter', ''],
    'setPageMark' => ['pagestate', 'Page::addContentMark', 'adapter', ''],
    'PageNo' => ['pagestate', 'Page::getPageId', 'adapter', ''],
    'getPageDimensions' => ['pagestate', 'Page::getPage', 'adapter', 'legacy array shape'],
    'getPageWidth' => ['pagestate', 'Page::getPage', 'adapter', ''],
    'getPageHeight' => ['pagestate', 'Page::getPage', 'adapter', ''],
    'getBreakMargin' => ['pagestate', 'Page::getPage', 'adapter', ''],
    'getScaleFactor' => ['pagestate', 'Tcpdf::$kunit', 'shim', 'points per unit'],
    'setMargins' => ['pagestate', 'Page margins', 'shim', 'facade state + future pages'],
    'setLeftMargin' => ['pagestate', 'Page margins', 'shim', ''],
    'setTopMargin' => ['pagestate', 'Page margins', 'shim', ''],
    'setRightMargin' => ['pagestate', 'Page margins', 'shim', ''],
    'getMargins' => ['pagestate', '-', 'shim', 'facade state accessor'],
    'getOriginalMargins' => ['pagestate', '-', 'shim', ''],
    'setCellPadding' => ['pagestate', 'Cell::setDefaultCellPadding', 'adapter', ''],
    'setCellPaddings' => ['pagestate', 'Cell::setDefaultCellPadding', 'adapter', ''],
    'getCellPaddings' => ['pagestate', 'Tcpdf::$defcell', 'shim', ''],
    'setCellMargins' => ['pagestate', 'Cell::setDefaultCellMargin', 'adapter', ''],
    'getCellMargins' => ['pagestate', 'Tcpdf::$defcell', 'shim', ''],
    'setAutoPageBreak' => ['pagestate', 'Page margins (CB)', 'adapter', ''],
    'getAutoPageBreak' => ['pagestate', '-', 'shim', ''],
    'Ln' => ['pagestate', '-', 'shim', 'cursor shim'],
    'GetX' => ['pagestate', '-', 'shim', 'cursor shim'],
    'GetAbsX' => ['pagestate', '-', 'shim', ''],
    'GetY' => ['pagestate', '-', 'shim', ''],
    'setX' => ['pagestate', '-', 'shim', ''],
    'setY' => ['pagestate', '-', 'shim', ''],
    'setXY' => ['pagestate', '-', 'shim', ''],
    'setAbsX' => ['pagestate', '-', 'shim', ''],
    'setAbsY' => ['pagestate', '-', 'shim', ''],
    'setAbsXY' => ['pagestate', '-', 'shim', ''],
    'AcceptPageBreak' => ['pagestate', '-', 'shim', 'page-break policy hook'],
    'movePage' => [
        'pagestate',
        'Page::move',
        'adapter',
        'engine move() only supports moving pages backward (same as legacy)',
    ],
    'deletePage' => ['pagestate', 'Page::delete', 'adapter', ''],
    'copyPage' => ['pagestate', '-', 'adapter', 'clones format/margins/content into a new page'],
    'startPageGroup' => ['pagestate', 'Page group', 'shim', 'group applies from the next added page'],
    'setStartingPageNumber' => ['pagestate', '-', 'shim', ''],
    'getAliasRightShift' => ['pagestate', '-', 'shim', 'legacy alias machinery'],
    'getAliasNbPages' => [
        'pagestate',
        '-',
        'shim',
        'returns real total during deferred decoration; placeholder otherwise (body substitution pending)',
    ],
    'getAliasNumPage' => [
        'pagestate',
        '-',
        'shim',
        'returns real number during deferred decoration; placeholder otherwise',
    ],
    'getPageGroupAlias' => ['pagestate', '-', 'shim', 'real total during deferred decoration; placeholder otherwise'],
    'getPageNumGroupAlias' => [
        'pagestate',
        '-',
        'shim',
        'real number during deferred decoration; placeholder otherwise',
    ],
    'getGroupPageNo' => ['pagestate', '-', 'adapter', ''],
    'getGroupPageNoFormatted' => ['pagestate', '-', 'shim', ''],
    'PageNoFormatted' => ['pagestate', '-', 'shim', ''],
    'setBooklet' => ['pagestate', 'Page margins (booklet)', 'shim', ''],
    'setLastH' => ['pagestate', '-', 'shim', 'cursor shim'],
    'getLastH' => ['pagestate', '-', 'shim', ''],
    'resetLastH' => ['pagestate', '-', 'shim', ''],
    'getCellHeight' => ['pagestate', '-', 'shim', 'fontsize*ratio+padding'],
    'setCellHeightRatio' => ['pagestate', '-', 'shim', ''],
    'getCellHeightRatio' => ['pagestate', '-', 'shim', ''],

    // --- Header / footer ---------------------------------------------------------
    'setHeaderData' => ['header', '-', 'shim', 'facade-rendered page decoration'],
    'setFooterData' => ['header', '-', 'shim', ''],
    'getHeaderData' => ['header', '-', 'shim', ''],
    'setHeaderMargin' => ['header', '-', 'shim', ''],
    'getHeaderMargin' => ['header', '-', 'shim', ''],
    'setFooterMargin' => ['header', '-', 'shim', ''],
    'getFooterMargin' => ['header', '-', 'shim', ''],
    'setPrintHeader' => ['header', '-', 'shim', 'per-page flag frozen when the page starts'],
    'setPrintFooter' => ['header', '-', 'shim', 'per-page flag, updatable until the page ends'],
    'getImageRBX' => ['header', '-', 'shim', 'image bottom-right cursor'],
    'getImageRBY' => ['header', '-', 'shim', ''],
    'resetHeaderTemplate' => [
        'header',
        '-',
        'intentional-noop',
        'facade renders headers directly, no cached XObject template',
    ],
    'setHeaderTemplateAutoreset' => [
        'header',
        '-',
        'intentional-noop',
        'facade renders headers directly, no cached XObject template',
    ],
    'Header' => ['header', '-', 'shim', 'deferred to Close(); painted behind the body content (legacy z-order)'],
    'Footer' => [
        'header',
        '-',
        'shim',
        'deferred rendering at Close-time; real page numbers (no alias substitution needed)',
    ],
    'setHeaderFont' => ['header', '-', 'shim', ''],
    'getHeaderFont' => ['header', '-', 'shim', ''],
    'setFooterFont' => ['header', '-', 'shim', ''],
    'getFooterFont' => ['header', '-', 'shim', ''],

    // --- Text / font / metrics ---------------------------------------------------
    'setRTL' => ['text', 'Base::setRTL', 'adapter', 'plus resetx cursor handling'],
    'getRTL' => ['text', 'Tcpdf::$rtl', 'shim', ''],
    'setTempRTL' => ['text', '-', 'shim', 'forcedir on text calls'],
    'isRTLTextDir' => ['text', '-', 'shim', ''],
    'GetStringWidth' => ['text', 'Font metrics', 'adapter', ''],
    'GetArrStringWidth' => ['text', 'Font metrics', 'adapter', ''],
    'GetCharWidth' => ['text', 'Font metrics', 'adapter', ''],
    'getRawCharWidth' => ['text', 'Font metrics', 'adapter', ''],
    'GetNumChars' => ['text', 'Unicode conv', 'adapter', ''],
    'AddFont' => [
        'text',
        'Font\Stack::insert',
        'adapter',
        'tc-lib font model only (breaking change): import new fonts with the tc-lib-pdf-font importer',
    ],
    'setFont' => [
        'text',
        'Font\Stack::insert',
        'adapter',
        'tc-lib font model only (breaking change): legacy .php/.z font definitions unsupported',
    ],
    'setFontSize' => ['text', 'Font\Stack::insert', 'adapter', ''],
    'getFontBBox' => ['text', 'Font metrics', 'adapter', ''],
    'getAbsFontMeasure' => ['text', 'Font metrics', 'shim', ''],
    'getCharBBox' => ['text', 'Font metrics', 'adapter', ''],
    'getFontDescent' => ['text', 'Font metrics', 'adapter', ''],
    'getFontAscent' => ['text', 'Font metrics', 'adapter', ''],
    'isCharDefined' => ['text', 'Font metrics', 'adapter', ''],
    'replaceMissingChars' => ['text', 'Font metrics', 'adapter', ''],
    'setDefaultMonospacedFont' => ['text', '-', 'shim', 'state'],
    'getFontSize' => ['text', '-', 'shim', 'state accessor'],
    'getFontSizePt' => ['text', '-', 'shim', ''],
    'getFontFamily' => ['text', '-', 'shim', ''],
    'getFontStyle' => ['text', '-', 'shim', ''],
    'getFontFamilyName' => ['text', '-', 'delegated', 'family name normalization'],
    'isUnicodeFont' => ['text', 'Font metrics', 'delegated', ''],
    'setFontSubsetting' => ['text', 'Font\Stack subset flag', 'shim', ''],
    'getFontSubsetting' => ['text', '-', 'shim', ''],
    'setFontStretching' => [
        'text',
        'Font\Stack::insert stretching',
        'shim',
        'ambient Tz text-state operator (engine spacing/stretching conventions are inconsistent)',
    ],
    'getFontStretching' => ['text', '-', 'shim', ''],
    'setFontSpacing' => [
        'text',
        'Font\Stack::insert spacing',
        'shim',
        'ambient Tc text-state operator (engine spacing/stretching conventions are inconsistent)',
    ],
    'getFontSpacing' => ['text', '-', 'shim', ''],
    'Text' => ['text', 'Text::getTextCell', 'adapter', 'absolute-position Cell'],
    'Cell' => [
        'text',
        'Text::getTextCell',
        'adapter',
        'cursor model + page-break in facade; stretch modes 1/2 via horizontal-scaling CTM, 3/4 via ambient Tc spacing',
    ],
    'MultiCell' => [
        'text',
        'Text::getTextCell',
        'adapter',
        'height grows to fit text (legacy minimum-height semantics); ln=0 returns to the starting page',
    ],
    'Write' => ['text', 'Text::getTextCell', 'adapter', 'flowing text'],
    'getNumLines' => ['text', 'Text line split', 'shim', 'greedy-wrap approximation; engine splitLines is protected'],
    'getStringHeight' => ['text', 'Text line split', 'shim', 'derived from getNumLines approximation'],
    'hyphenateText' => [
        'text',
        'Text::setTexHyphenPatterns',
        'adapter',
        'configures engine TeX patterns; engine hyphenates at render time, text returned unchanged',
    ],
    'setTextRenderingMode' => ['text', 'getTextCell stroke/fill/clip args', 'shim', ''],
    'setTextShadow' => [
        'text',
        'getTextCell shadow arg',
        'adapter',
        'native engine shadow for Cell/MultiCell/Write/Text; HTML shadows emulated with an offset alpha-blended copy of the rendered text objects; header/footer render shadow-free (legacy parity)',
    ],
    'getTextShadow' => ['text', '-', 'shim', ''],
    'stringLeftTrim' => ['text', 'HTML::strTrimLeft', 'delegated', ''],
    'stringRightTrim' => ['text', 'HTML::strTrimRight', 'delegated', ''],
    'stringTrim' => ['text', 'HTML::strTrim', 'delegated', ''],

    // --- HTML / CSS ----------------------------------------------------------------
    'writeHTML' => [
        'html',
        'HTML::addHTMLCell',
        'adapter',
        'markup pre-processing: malformed hex colors repaired, relative resource paths resolved, styled inline parents with nested children flattened (engine paints inline backgrounds only at depth 1)',
    ],
    'writeHTMLCell' => [
        'html',
        'HTML::addHTMLCell',
        'adapter',
        'ln=0 returns to the starting page (legacy column flows); tcpdf-in-HTML tags executed when K_TCPDF_CALLS_IN_HTML is set',
    ],
    'fixHTMLCode' => ['html', 'HTML::tidyHTML', 'delegated', ''],
    'addHtmlLink' => ['html', '-', 'shim', 'rendered via Write() with link color/style state'],
    'unhtmlentities' => ['html', '-', 'shim', 'html_entity_decode'],
    'getCSSPadding' => ['html', 'CSS helpers', 'shim', 'CSS shorthand parser'],
    'getCSSMargin' => ['html', 'CSS helpers', 'shim', 'CSS shorthand parser'],
    'getCSSBorderMargin' => ['html', 'CSS helpers', 'shim', 'CSS shorthand parser'],
    'getHTMLFontUnits' => ['html', 'CSS helpers', 'shim', ''],
    'getHTMLUnitToUnits' => ['html', 'CSS helpers', 'shim', ''],
    'serializeTCPDFtag' => ['html', '-', 'shim', 'payload format consumed by the writeHTML tcpdf-tag executor'],
    'setLIsymbol' => ['html', 'HTML::setULLIDot', 'delegated', ''],
    'setHtmlVSpace' => ['html', 'HTML::setHtmlVSpace', 'adapter', ''],
    'setListIndentWidth' => [
        'html',
        '-',
        'intentional-noop',
        'list indentation is computed by the engine HTML renderer',
    ],
    'setOpenCell' => [
        'html',
        '-',
        'intentional-noop',
        'block continuation borders are handled by the engine HTML renderer',
    ],
    'setHtmlLinksStyle' => ['html', '-', 'shim', 'state consumed by addHtmlLink'],
    'setDefaultTableColumns' => [
        'html',
        '-',
        'intentional-noop',
        'table layout is computed by the engine HTML renderer',
    ],
    'pixelsToUnits' => ['html', '-', 'shim', 'px / (k * imgscale)'],

    // --- Graphics / transforms / images ----------------------------------------------
    'getAllSpotColors' => ['graphics', 'Color spot registry', 'delegated', ''],
    'AddSpotColor' => [
        'graphics',
        'Color spot registry',
        'adapter',
        'CMYK components 0-100 mapped to the engine color model',
    ],
    'setSpotColor' => ['graphics', 'Color spot registry', 'adapter', ''],
    'setDrawSpotColor' => ['graphics', 'Color spot registry', 'adapter', ''],
    'setFillSpotColor' => ['graphics', 'Color spot registry', 'adapter', ''],
    'setTextSpotColor' => ['graphics', 'Color spot registry', 'adapter', ''],
    'setColorArray' => ['graphics', 'Color\Pdf', 'adapter', 'legacy array conventions (gray/RGB/CMYK)'],
    'setDrawColorArray' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setFillColorArray' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setTextColorArray' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setColor' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setDrawColor' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setFillColor' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setTextColor' => ['graphics', 'Color\Pdf', 'adapter', ''],
    'setLineWidth' => ['graphics', 'Graph style', 'adapter', ''],
    'GetLineWidth' => ['graphics', '-', 'shim', ''],
    'setLineStyle' => ['graphics', 'Graph style', 'adapter', 'legacy style array translation'],
    'Line' => ['graphics', 'Graph\Draw::getLine', 'adapter', ''],
    'Rect' => ['graphics', 'Graph\Draw::getRect', 'adapter', ''],
    'Curve' => ['graphics', 'Graph\Draw raw ops', 'adapter', ''],
    'Polycurve' => ['graphics', 'Graph\Draw raw ops', 'adapter', ''],
    'Ellipse' => ['graphics', 'Graph\Draw::getEllipse', 'adapter', ''],
    'Circle' => ['graphics', 'Graph\Draw::getCircle', 'adapter', ''],
    'PolyLine' => ['graphics', 'Graph\Draw::getPolygon', 'adapter', ''],
    'Polygon' => [
        'graphics',
        'Graph\Draw::getPolygon',
        'adapter',
        'per-segment line styles collapsed to a single style',
    ],
    'RegularPolygon' => ['graphics', 'Graph\Draw::getRegularPolygon', 'adapter', ''],
    'StarPolygon' => ['graphics', 'Graph\Draw::getStarPolygon', 'adapter', ''],
    'RoundedRect' => ['graphics', 'Graph\Draw::getRoundedRect', 'adapter', ''],
    'RoundedRectXY' => ['graphics', 'Graph\Draw::getRoundedRect', 'adapter', ''],
    'Arrow' => ['graphics', 'Graph\Draw', 'adapter', ''],
    'StartTransform' => ['graphics', 'Graph\Draw::getStartTransform', 'delegated', ''],
    'StopTransform' => ['graphics', 'Graph\Draw::getStopTransform', 'delegated', ''],
    'ScaleX' => ['graphics', 'Graph transform', 'adapter', ''],
    'ScaleY' => ['graphics', 'Graph transform', 'adapter', ''],
    'ScaleXY' => ['graphics', 'Graph transform', 'adapter', ''],
    'Scale' => ['graphics', 'Graph transform', 'adapter', 'legacy percentages mapped to engine ratios'],
    'MirrorH' => ['graphics', 'Graph transform', 'adapter', ''],
    'MirrorV' => ['graphics', 'Graph transform', 'adapter', ''],
    'MirrorP' => ['graphics', 'Graph transform', 'adapter', ''],
    'MirrorL' => ['graphics', 'Graph transform', 'adapter', ''],
    'TranslateX' => ['graphics', 'Graph transform', 'adapter', ''],
    'TranslateY' => ['graphics', 'Graph transform', 'adapter', ''],
    'Translate' => ['graphics', 'Graph transform', 'adapter', ''],
    'Rotate' => ['graphics', 'Graph transform', 'adapter', ''],
    'SkewX' => ['graphics', 'Graph transform', 'adapter', ''],
    'SkewY' => ['graphics', 'Graph transform', 'adapter', ''],
    'Skew' => ['graphics', 'Graph transform', 'adapter', ''],
    'setAlpha' => ['graphics', 'Graph ExtGState', 'adapter', ''],
    'getAlpha' => ['graphics', '-', 'shim', ''],
    'setOverprint' => ['graphics', 'Graph ExtGState', 'adapter', ''],
    'getOverprint' => ['graphics', '-', 'shim', ''],
    'setVisibility' => ['graphics', 'Tcpdf::newLayer', 'adapter', 'mapped onto optional-content layers'],
    'startLayer' => ['graphics', 'Tcpdf::newLayer', 'adapter', ''],
    'endLayer' => ['graphics', 'Tcpdf::closeLayer', 'adapter', ''],
    'colorRegistrationBar' => ['graphics', 'Graph\Draw', 'adapter', 'legacy letter list mapped to engine color pairs'],
    'cropMark' => ['graphics', 'Graph\Draw', 'adapter', ''],
    'registrationMark' => [
        'graphics',
        'Graph\Draw::getRegistrationMark',
        'adapter',
        'secondary color ignored (engine derives the inverse)',
    ],
    'registrationMarkCMYK' => ['graphics', 'Graph\Draw::getCmykRegistrationMark', 'delegated', ''],
    'LinearGradient' => ['graphics', 'Graph gradients', 'adapter', ''],
    'RadialGradient' => ['graphics', 'Graph gradients', 'adapter', ''],
    'CoonsPatchMesh' => [
        'graphics',
        'Graph gradients',
        'adapter',
        'corner order verified against the reference renderer (engine names use PDF y-up coordinates)',
    ],
    'Gradient' => ['graphics', 'Graph gradients', 'adapter', ''],
    'PieSector' => ['graphics', 'Graph\Draw::getPieSector', 'adapter', 'legacy cw/origin angle convention translated'],
    'PieSectorXY' => [
        'graphics',
        'Graph\Draw::getPieSector',
        'shim',
        'elliptical sector approximated by scaling a circular sector',
    ],
    'Image' => [
        'graphics',
        'Image\Import::add + Graph',
        'adapter',
        'placement, @-string data, dpi/imgscale sizing, fitbox, palign, masks via engine',
    ],
    'ImageEps' => [
        'graphics',
        '-',
        'blocked',
        'unsupported (breaking change): no PostScript interpreter in the engine — convert EPS/AI artwork to SVG and use ImageSVG(); SVG/raster inputs are dispatched to ImageSVG()/Image()',
    ],
    'ImageSVG' => ['graphics', 'SVG::addSVG', 'adapter', ''],
    'setImageScale' => ['graphics', '-', 'shim', 'state'],
    'getImageScale' => ['graphics', '-', 'shim', ''],
    'setJPEGQuality' => ['graphics', 'Image quality arg', 'shim', ''],
    'setRasterizeVectorImages' => ['graphics', '-', 'intentional-noop', 'the engine renders vector images natively'],

    // --- Forms / annotations / links / JS ---------------------------------------------
    'AddLink' => [
        'forms',
        'JavaScript::addInternalLink',
        'adapter',
        'facade tokens resolved to engine internal links at use time',
    ],
    'setLink' => ['forms', 'JavaScript::addInternalLink', 'adapter', ''],
    'Link' => ['forms', 'JavaScript::setLink', 'adapter', ''],
    'Annotation' => [
        'forms',
        'JavaScript::setAnnotation',
        'adapter',
        'option keys lowercased; unreadable attachment sources skipped (legacy parity)',
    ],
    'EmbedFile' => ['forms', 'JavaScript::addEmbeddedFile', 'adapter', ''],
    'EmbedFileFromString' => ['forms', 'JavaScript::addContentAsEmbeddedFile', 'delegated', ''],
    'IncludeJS' => ['forms', 'JavaScript::appendRawJavaScript', 'delegated', ''],
    'addJavascriptObject' => ['forms', 'JavaScript::addRawJavaScriptObj', 'delegated', ''],
    'setFormDefaultProp' => ['forms', 'JavaScript::setDefJSAnnotProp', 'adapter', ''],
    'getFormDefaultProp' => ['forms', 'JavaScript::getDefJSAnnotProp', 'shim', ''],
    'TextField' => ['forms', 'JavaScript::addFFText/addJSText', 'adapter', ''],
    'RadioButton' => ['forms', 'JavaScript::addFFRadioButton', 'adapter', ''],
    'ListBox' => ['forms', 'JavaScript::addFFListBox', 'adapter', ''],
    'ComboBox' => ['forms', 'JavaScript::addFFComboBox', 'adapter', ''],
    'CheckBox' => ['forms', 'JavaScript::addFFCheckBox', 'adapter', ''],
    'Button' => ['forms', 'JavaScript::addFFButton', 'adapter', ''],
    'setDestination' => ['forms', 'JavaScript::setNamedDestination', 'adapter', ''],
    'getDestination' => ['forms', '-', 'shim', ''],
    'setBookmark' => ['forms', 'JavaScript::setBookmark', 'adapter', ''],
    'Bookmark' => ['forms', 'JavaScript::setBookmark', 'adapter', ''],

    // --- Signatures / security -----------------------------------------------------
    'setProtection' => [
        'security',
        'Encrypt\Encrypt (constructor)',
        'adapter',
        're-initializes the engine with an Encrypt object; must be called before pages; legacy RC4 deprecation muted',
    ],
    'setUserRights' => ['security', 'Tcpdf::setUserRights', 'adapter', ''],
    'setSignature' => ['security', 'Tcpdf::setSignature', 'adapter', ''],
    'setSignatureAppearance' => ['security', 'Tcpdf::setSignatureAppearance', 'adapter', ''],
    'addEmptySignatureAppearance' => ['security', 'Tcpdf::addEmptySignatureAppearance', 'adapter', ''],
    'setTimeStamp' => ['security', 'Tcpdf::setSignTimeStamp', 'adapter', ''],

    // --- Barcodes -------------------------------------------------------------------
    'setBarcode' => ['barcode', '-', 'shim', 'header barcode state'],
    'getBarcode' => ['barcode', '-', 'shim', ''],
    'write1DBarcode' => [
        'barcode',
        'Tcpdf::getBarcode',
        'adapter',
        'natural width from module count x xres; fg/bg colors, border, position, label text; legacy-only types skipped',
    ],
    'write2DBarcode' => [
        'barcode',
        'Tcpdf::getBarcode',
        'adapter',
        'RAW/RAW2 mapped to engine SRAW; fg/bg colors, border, position',
    ],

    // --- Columns / TOC / templates / regions ------------------------------------------
    'addTOCPage' => ['toc', '-', 'shim', ''],
    'endTOCPage' => ['toc', '-', 'shim', ''],
    'addTOC' => [
        'toc',
        'Tcpdf::addTOC',
        'adapter',
        'engine builds the TOC from the bookmark outline; filler/font args not applied',
    ],
    'addHTMLTOC' => ['toc', '-', 'shim', 'approximated with the engine bookmark TOC; HTML templates not applied'],
    'setEqualColumns' => [
        'toc',
        'Page regions (columns)',
        'adapter',
        'columns become regions of pages added after the call',
    ],
    'resetColumns' => ['toc', 'Page regions', 'shim', ''],
    'setColumnsArray' => ['toc', 'Page regions', 'adapter', 'applies to pages added after the call'],
    'selectColumn' => ['toc', 'Page regions', 'adapter', ''],
    'getColumn' => ['toc', 'Page regions', 'adapter', ''],
    'getNumberOfColumns' => ['toc', 'Page regions', 'adapter', ''],
    'startTemplate' => [
        'toc',
        'JavaScript::newXObjectTemplate',
        'adapter',
        'facade routes content and registers fonts/images/extgstates in the XObject resources; page breaks suppressed inside templates',
    ],
    'endTemplate' => ['toc', 'JavaScript::exitXObjectTemplate', 'adapter', ''],
    'printTemplate' => ['toc', 'JavaScript::getXObjectTemplate', 'adapter', ''],
    'getPageRegions' => ['toc', 'Page regions', 'shim', ''],
    'setPageRegions' => [
        'toc',
        'Page regions',
        'blocked',
        'legacy regions are no-write exclusion zones; the engine only supports writable regions - stored for page creation, exclusion semantics not reproduced',
    ],
    'addPageRegion' => ['toc', 'Page regions', 'blocked', 'see setPageRegions'],
    'removePageRegion' => ['toc', 'Page regions', 'shim', ''],
];

$ref = new ReflectionClass('TCPDF');
$methods = [];
foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $met) {
    $methods[] = $met->getName();
}

$methodmap = TCPDF_METHOD_MAP;
/** @var array<string, string> $familymap */
$familymap = TCPDF_FAMILIES;

$mapped = array_keys($methodmap);
$missing = array_diff($methods, $mapped);
$unknown = array_diff($mapped, $methods);
if ($missing !== [] || $unknown !== []) {
    foreach ($missing as $name) {
        fwrite(STDERR, 'NOT MAPPED: ' . $name . "\n");
    }

    foreach ($unknown as $name) {
        fwrite(STDERR, 'UNKNOWN METHOD IN MAP: ' . $name . "\n");
    }

    exit(1);
}

// Build the report.
/** @var array<string, int> $bystatus */
$bystatus = [];
/** @var array<string, array<string, array{0: string, 1: string, 2: 'delegated'|'adapter'|'shim'|'intentional-noop'|'blocked'|'stub', 3: string}>> $byfamily */
$byfamily = [];
foreach ($methodmap as $name => $row) {
    $byfamily[$row[0]][$name] = $row;
    $bystatus[$row[2]] = ($bystatus[$row[2]] ?? 0) + 1;
}

$out = [];
$out[] = '# TCPDF -> tc-lib-pdf Delegation Mapping';
$out[] = '';
$out[] = 'Generated by `php scripts/mapping.php` from the curated map in that script';
$out[] = '(single source of truth). Do not edit this file manually.';
$out[] = '';
$out[] = 'Statuses: `stub` (not implemented), `delegated`, `adapter`, `shim`,';
$out[] = '`intentional-noop`, `blocked`.';
$out[] = '';
$out[] = '## Summary';
$out[] = '';
$out[] = '| Family | Total | Delegated | Adapter | Shim | Noop | Blocked | Stub |';
$out[] = '|---|---:|---:|---:|---:|---:|---:|---:|';

$totals = [
    'total' => 0,
    'delegated' => 0,
    'adapter' => 0,
    'shim' => 0,
    'intentional-noop' => 0,
    'blocked' => 0,
    'stub' => 0,
];
foreach ($familymap as $key => $title) {
    $rows = $byfamily[$key] ?? [];
    $count = ['delegated' => 0, 'adapter' => 0, 'shim' => 0, 'intentional-noop' => 0, 'blocked' => 0, 'stub' => 0];
    foreach ($rows as $row) {
        $count[$row[2]] = ($count[$row[2]] ?? 0) + 1;
    }

    $out[] =
        '| '
        . $title
        . ' | '
        . count($rows)
        . ' | '
        . $count['delegated']
        . ' | '
        . $count['adapter']
        . ' | '
        . $count['shim']
        . ' | '
        . $count['intentional-noop']
        . ' | '
        . $count['blocked']
        . ' | '
        . $count['stub']
        . ' |';
    $totals['total'] += count($rows);
    foreach ($count as $status => $num) {
        $totals[$status] = ($totals[$status] ?? 0) + $num;
    }
}

$out[] =
    '| **Total** | **'
    . $totals['total']
    . '** | **'
    . $totals['delegated']
    . '** | **'
    . $totals['adapter']
    . '** | **'
    . $totals['shim']
    . '** | **'
    . $totals['intentional-noop']
    . '** | **'
    . $totals['blocked']
    . '** | **'
    . $totals['stub']
    . '** |';
$out[] = '';

foreach ($familymap as $key => $title) {
    $out[] = '## ' . $title;
    $out[] = '';
    $out[] = '| TCPDF method | tc-lib target | Status | Notes |';
    $out[] = '|---|---|---|---|';
    foreach ($byfamily[$key] ?? [] as $name => $row) {
        $out[] = '| ' . $name . ' | `' . $row[1] . '` | ' . $row[2] . ' | ' . $row[3] . ' |';
    }

    $out[] = '';
}

file_put_contents(dirname(__DIR__) . '/MAPPING.md', implode("\n", $out) . "\n");

echo 'Mapped methods: ' . count($mapped) . "\n";
foreach ($bystatus as $status => $num) {
    echo '  ' . str_pad($status, 18) . ' ' . $num . "\n";
}

echo 'MAPPING.md regenerated.' . "\n";
