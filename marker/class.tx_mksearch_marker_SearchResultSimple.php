<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


tx_rnbase::load('tx_mksearch_marker_Search');

/**
 * Renders a search result straightforward with all its data, adding a link, if available
 * This class can be extended for other content types e.g. to change the behavior of $this->prepareLinks().
 */
class tx_mksearch_marker_SearchResultSimple
	extends tx_mksearch_marker_Search {

	/**
	 * @param string $template HTML template
	 * @param tx_mksearch_model_SearchHit $item search hit
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId path of typoscript configuration
	 * @param string $marker name of marker
	 * @return string readily parsed template
	 */
	public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'ITEM') {
		if(!is_object($item)) {
			// On default use an empty instance.
			$item = self::getEmptyInstance('tx_mksearch_model_SearchHit');
		}

		$this->prepareItem($item, $formatter->getConfigurations(), $confId);

		// Fill MarkerArray
		$ignore = self::findUnusedCols($item->record, $template, $marker);

		//diese felder werden auch bei nicht vorhanden sein gesetzt damit die market nicht ausgegeben werden
		$initFields = $this->getInitFields($template, $item, $formatter, $confId, $marker);

		$markerArray = $formatter->getItemMarkerArrayWrapped($item->record, $confId , $ignore, $marker.'_', $initFields);

		// subparts erzeugen
		$wrappedSubpartArray = $subpartArray = array();
		$this->prepareSubparts($wrappedSubpartArray, $subpartArray, $template, $item, $formatter, $confId, $marker);

		// Links erzeugen
		$this->prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

		// das Template rendern
		return tx_rnbase_util_Templates::substituteMarkerArrayCached(
			$template,
			$markerArray,
			$subpartArray,
			$wrappedSubpartArray
		);
	}

	/**
	 * Prepare links
	 *
	 * @param tx_mksearch_model_SearchHit $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $template
	 */
	public function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		$config = $formatter->getConfigurations();

		/*
		 * Wenn linkMethod auf generic steht,
		 * werden die Links über den SimpleMarker gerendert.
		 * Damit sind mehrere Links für das ergebnis möglich.
		 *
		 * Andernfalls wird der Alte Weg genutzt.
		 * Hier wurd nur der SHOWLINK gerendert und alle anderen ignoriert.
		 */
		$linkMethod	= $config->get($confId.'linkMethod');
		if ($linkMethod == 'generic') {
			parent::prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);
		} else {
			$linkId = 'show';
			$linkConfId = $confId.'links.'.$linkId.'.';
			//cObject Daten sichern und durch unseren solr record ersetzen
			$sCObjTempData = $config->getCObj()->data;
			$config->getCObj()->data = $item->record;

			$pid = $config->getCObj()->stdWrap($config->get($linkConfId.'pid'), $config->get($linkConfId.'pid.'));

			// Link entfernen, wenn nicht gesetzt
			if(empty($pid)) {
				$remove = intval($formatter->getConfigurations()->get($linkConfId.'removeIfDisabled'));
				$linkMarker = $marker . '_' . strtoupper($linkId).'LINK';
				self::disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, $remove>0);
			} else if(self::checkLinkExistence($linkId, $marker, $template)) {

				// Try to get parameter name from TS
				$paramName = $config->get($linkConfId.'paramName');
				if (!$paramName) $paramName = $item->record['contentType'];
				// Try to get value field name from TS
				$paramField = $config->get($linkConfId.'paramField');
				if (!$paramField) $paramField = 'uid';

				/* Wir lesen weitere Parameter aus dem TS aus. Dabei ist folgendes möglich:
						backPid = TEXT
						backPid.data = TSFE:id
						backPid.require = 1
				 */
				$addParams = $config->get($linkConfId.'additionalParams.', true);
				if (!is_array($addParams)) $addParams = array();
				$addParams[$paramName] = $item->record[$paramField];

				$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, $addParams, $template);
			}
			//cObject Daten wieder zurück
			$config->getCObj()->data = $sCObjTempData;
		}
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_SearchResultSimple.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_SearchResultSimple.php']);
}
