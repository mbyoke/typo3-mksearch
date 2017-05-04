<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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



/**
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-business.de>
 */
class tx_mksearch_util_Filter
{

    /**
     *
     * @var string
     */
    protected $sortField = false;

    /**
     *
     * @var string
     */
    protected $sortOrder = 'asc';

    /**
     * Fügt den Suchstring zu dem Filter hinzu.
     *
     * dazu folgendes TS Setup
     *
         # Die hier konfigurierten Parameter werden als Marker im Term-String verwendet
        # Es sind verschiedene Extensions möglich
        $confId.params.mksearch {
            term = TEXT
            term {
                field = term
            }
        }
        # So wurden auch alle Parameter von tt_news ausgelesen
        #$confId.params.tt_news {
        #}

        $confId.fields {
            # Die Marker haben das Format ###PARAM_EXTQUALIFIER_PARAMNAME###
            ### beim DisMaxRequestHandler darf hier nur ###PARAM_MKSEARCH_TERM### stehen!
            term = contentType:* ###PARAM_MKSEARCH_TERM###
        }
     *
     * @param   string                      $termTemplate
     * @param   array                       $options
     * @param   tx_rnbase_IParameters       $parameters
     * @param   tx_rnbase_configurations    $configurations
     * @param   string                      $confId
     *
     * @return string
     */
    public function parseTermTemplate($termTemplate, $parameters, $configurations, $confId)
    {
        $templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
        // Welche Extension-Parameter werden verwendet?
        $extQualifiers = $configurations->getKeyNames($confId.'params.');
        foreach ($extQualifiers as $qualifier) {
            $params = $parameters->getAll($qualifier);
            $termTemplate = $templateMarker->parseTemplate(
                $termTemplate,
                $params,
                $configurations->getFormatter(),
                $confId.'params.'.$qualifier.'.',
                'PARAM_'.strtoupper($qualifier)
            );
            //wenn keine params gesetzt sind, kommt der marker ungeparsed raus.
            //also ersetzen wir diesen prinzipiell am ende durch ein leeren string.
            $termTemplate = preg_replace(
                '/(###PARAM_'.strtoupper($qualifier).'_)\w.*###/',
                '',
                $termTemplate
            );
        }

        return $termTemplate;
    }

    /**
     * Rendert Formularfelder. Derzeit kann man damit das Seiten-Limit und die Sortierung einstellen
     *
     * @param string $template
     * @param string $confId
     * @param string $markerName
     * @return string
     */
    public function parseCustomFilters($template, tx_rnbase_configurations $configurations, $confId, $markerName = 'SEARCH_FILTER')
    {
        if (!tx_rnbase_util_BaseMarker::containsMarker($template, $markerName)) {
            return $template;
        }

        $parameters = $configurations->getParameters();
        $formfields = $configurations->getKeyNames($confId . 'formfields.');

        if (!is_array($formfields) || empty($formfields)) {
            return $template;
        }

        $markArray = array();
        /* @var $listBuilder tx_rnbase_util_ListBuilder */
        $listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
        foreach ($formfields as $field) {
            $fieldMarker = $markerName . '_' . strtoupper($field);
            $fieldConfId = $confId . 'formfields.' . $field . '.';
            if (!tx_rnbase_util_BaseMarker::containsMarker($template, $fieldMarker)) {
                continue;
            }
            $activeMark = $configurations->get($fieldConfId . 'activeMark', true);
            $activeMark = ''.$activeMark == '' ? 'selected="selected"' : $activeMark;
            $fieldActive = $parameters->getCleaned($field);
            $markArray['###' . $fieldMarker . '_FORM_NAME###'] = $configurations->getQualifier() . '[' . $field . ']';
            $markArray['###' . $fieldMarker . '_FORM_VALUE###'] = $fieldActive;
            $fieldItems = array();
            $fieldValues = $configurations->get($fieldConfId . 'values.');
            if (is_array($fieldValues)) {
                foreach ($fieldValues as $value => $config) {
                    $fieldActive = empty($fieldActive) ? $configurations->get($fieldConfId . 'default') : $fieldActive;
                    $fieldId = is_array($config) ? $config['value'] : $value;
                    $fieldItems[] = tx_rnbase::makeInstance(
                        'tx_rnbase_model_base',
                        array(
                            'uid' => $fieldId,
                            'caption' => is_array($config) ? $config['caption'] : $config,
                            'selected' => $fieldId == $fieldActive ? $activeMark : '',
                        )
                    );
                }

                $template = $listBuilder->render(
                    $fieldItems,
                    false,
                    $template,
                    'tx_rnbase_util_SimpleMarker',
                    $confId . 'formfields.' . $field . '.',
                    ($fieldMarker),
                    $configurations->getFormatter()
                );
            }
        }

        $formValues = array('term', 'place');
        foreach ($formValues as $formField) {
            $formMarker = $markerName . '_' . strtoupper($formField);
            if (!tx_rnbase_util_BaseMarker::containsMarker($template, $formMarker)) {
                continue;
            }
            $markArray['###' . $formMarker . '_FORM_VALUE###'] = $parameters->getCleaned($formField);
        }

        if (tx_rnbase_util_BaseMarker::containsMarker($template, $markerName . '_SPATIAL')) {
            $markArray['###' . $markerName . '_SPATIAL_VALUE###'] = $this->getSpatialPoint();
        }

        if (!empty($markArray)) {
            $template = tx_rnbase_util_Templates::substituteMarkerArrayCached(
                $template,
                $markArray
            );
        }

        return $template;
    }

    /**
     *
     * @param tx_rnbase_IParameters $parameters
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     * @param int $defaultValue
     */
    public function getPageLimit(tx_rnbase_IParameters $parameters, $configurations, $confId, $defaultValue)
    {
        $pageLimit = $parameters->getInt('pagelimit');
        // Die möglichen Wert suchen
        $limitValues = array();
        $limits = $configurations->get($confId . 'formfields.pagelimit.values.');
        if (is_array($limits) && count($limits) > 0) {
            foreach ($limits as $cfg) {
                $limitValues[] = $cfg['value'];
            }
            if (!in_array($pageLimit, $limitValues)) {
                // Der Defaultwert kommt jetzt letztendlich aus dem Flexform
                $pageLimit = $defaultValue;
            }
        } else {
            $pageLimit = $defaultValue;
        }
        if ($pageLimit === -1) {
            // ein unset führt durch den default von 10 in Apache_Solr_Service::search nicht zum erfolg,
            // wir müssen zwingend ein limit setzen
            $pageLimit = 999999;
        } // -2 = 0
        // durch typo3 und das casten von werten,
        // kann im flexform 0 nicht explizit angegeben werden.
        elseif ($pageLimit === -2) {
            $pageLimit = 0;
        }


        return $pageLimit;
    }
    /**
     * Fügt die Sortierung zu dem Filter hinzu.
     *
     * @TODO: das klappt zurzeit nur bei einfacher sortierung!
     *
     * @param   array                   $options
     * @param   tx_rnbase_IParameters   $parameters
     *
     * @return string
     */
    public function getSortString(array &$options, tx_rnbase_IParameters &$parameters)
    {
        $sortString = '';
        // die parameter nach einer sortierung fragen
        $sort = trim($parameters->get('sort'));
        // wurden keine parameter gefunden, nutzen wir den default des filters
        $sort = $sort ? $sort : $this->sortField;
        if ($sort) {
            list($sort, $sortOrder) = explode(' ', $sort);
            // wenn order nicht mit gesetzt wurde, aus den parametern holen
            $sortOrder = $sortOrder ? $sortOrder : $parameters->get('sortorder');
            // den default order nutzen!
            $sortOrder = $sortOrder ? $sortOrder : $this->sortOrder;
            // sicherstellen, das immer desc oder asc gesetzt ist
            $sortOrder = (strtolower($sortOrder) === 'desc') ? 'desc' : 'asc';
            // wird beim parsetemplate benötigt
            $this->sortField = $sort;
            $this->sortOrder = $sortOrder;

            $sortString = $sort.' '.$sortOrder;
        }

        return $sortString;
    }

    /**
     * Sortierungslinks bereitstellen
     *
         Folgende Marker werden im Template anhand der Beispiel TS Konfiguration bereitgestellt:
        ###SORT_UID_ORDER### = asc
        ###SORT_UID_LINKURL### = index.php?mksearch[sort]=uid&mksearch[sortorder]=asc
        ###SORT_UID_LINK### = wrappedArray mit dem A-Tag
        ###SORT_TITLE_ORDER### = asc
        ###SORT_TITLE_LINKURL### = index.php?mksearch[sort]=title&mksearch[sortorder]=asc
        ###SORT_TITLE_LINK### = wrappedArray mit dem A-Tag
     *
     * @param string $template HTML template
     * @param array $markArray
     * @param array $subpartArray
     * @param array $wrappedSubpartArray
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param string $confId
     * @param string $marker
     */
    public function parseSortFields(
        $template,
        &$markArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$formatter,
        $confId,
        $marker = 'FILTER'
    ) {
        $marker = 'SORT';
        $confId .= 'sort.';
        $configurations = $formatter->getConfigurations();

        // die felder für die sortierung stehen kommasepariert im ts
        $sortFields = $configurations->get($confId.'fields');

        $sortFields = $sortFields ? tx_rnbase_util_Strings::trimExplode(',', $sortFields, true) : array();

        if (!empty($sortFields)) {
            tx_rnbase::load('tx_rnbase_util_BaseMarker');
            $token = md5(microtime());
            $markOrders = array();
            foreach ($sortFields as $field) {
                $isField = ($field == $this->sortField);
                // sortOrder ausgeben
                $markOrders[$field.'_order'] = $isField ? $this->sortOrder : '';

                $fieldMarker = $marker.'_'.strtoupper($field).'_LINK';
                $makeLink = tx_rnbase_util_BaseMarker::containsMarker($template, $fieldMarker);
                $makeUrl = tx_rnbase_util_BaseMarker::containsMarker($template, $fieldMarker.'URL');
                // link generieren
                if ($makeLink || $makeUrl) {
                    // sortierungslinks ausgeben
                    $params = array(
                        'sort' => $field,
                        'sortorder' => $isField && $this->sortOrder == 'asc' ? 'desc' : 'asc',
                    );
                    $link = $configurations->createLink();
                    $link->label($token);
                    $link->initByTS($configurations, $confId.'link.', $params);
                    if ($makeLink) {
                        $wrappedSubpartArray['###'.$fieldMarker.'###'] = explode($token, $link->makeTag());
                    }
                    if ($makeUrl) {
                        $markArray['###'.$fieldMarker.'URL###'] = $link->makeUrl(false);
                    }
                }
            }
            // die sortOrders parsen
            $markOrders = $formatter->getItemMarkerArrayWrapped(
                $markOrders,
                $confId,
                0,
                $marker.'_',
                array_keys($markOrders)
            );
            $markArray = array_merge($markArray, $markOrders);
        }
    }

    /**
     * Prüft den fq parameter auf richtigkeit.
     *     valid:
     *         title:hans
     *         uid:1
     *
     * @param string $sFq
     * @param array $allowedFqParams
     * @return string
     */
    public function parseFqFieldAndValue($sFq, $allowedFqParams)
    {
        if (empty($sFq) || empty($allowedFqParams)) {
            return '';
        }

        $filterQueryPartKeys = array('field', 'value');
        $filterQueryParts = Tx_Rnbase_Utility_Strings::trimExplode(':', $sFq);

        // die initiale fq muss aus $feldName:$feldWert bestehen. Das ist der alte Weg. Der neue Weg
        // der fq soll hier ignoriert werden.
        if (count($filterQueryParts) == 2) {
            $matches = array_combine($filterQueryPartKeys, Tx_Rnbase_Utility_Strings::trimExplode(':', $sFq));
        } else {
            $matches = array();
        }

        tx_rnbase::load('tx_mksearch_util_Misc');

        if (// wurde das feld gefunden?
            isset($matches['field']) && !empty($matches['field'])
            // wurde der wert gefunden?
            && isset($matches['value']) && !empty($matches['value'])
            // das feld muss erlaubt sein!
            && in_array($matches['field'], $allowedFqParams)
            // werte reinigen
            && ($field = tx_mksearch_util_Misc::sanitizeFq($matches['field']))
            && ($term = tx_mksearch_util_Misc::sanitizeFq($matches['value']))
        ) {
            // fq wieder zusammensetzen
            $sFq = $field . ':"'. $term .'"';
        } // kein feld und oder wert gefunden oder feld nicht erlaubt, wir lassen den qs leer!
        else {
            $sFq = '';
        }

        return $sFq;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Filter.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Filter.php']);
}
