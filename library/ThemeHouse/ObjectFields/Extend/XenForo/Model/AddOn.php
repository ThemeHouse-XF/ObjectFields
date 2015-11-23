<?php

class ThemeHouse_ObjectFields_Model_AddOn extends XFCP_ThemeHouse_ObjectFields_Model_AddOn
{
	/**
	 * @param array $addOn Add-on info
	 *
	 * @return DOMDocument
	 */
	public function getAddOnXml(array $addOn)
	{
		/* @var $document DOMDocument */
		$document = parent::getAddOnXml($addOn);

		$rootNode = $document->getElementsByTagName('addon')->item(0);
		$addOnId = $rootNode->attributes->getNamedItem('addon_id')->textContent;

		$dataNode = $document->createElement('object_fields');
		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField')->appendFieldsAddOnXml($dataNode, $addOnId);
		$this->_appendNodeAlphabetically($rootNode, $dataNode);

		return $document;
	}

	protected function _appendNodeAlphabetically(DOMElement $rootNode, DOMElement $newNode)
	{
	    if ($newNode->hasChildNodes()) {
	        $refNode = null;
	        foreach ($rootNode->childNodes as $child) {
	            if ($child instanceof DOMElement && $child->tagName > $newNode->tagName) {
	                $refNode = $child;
	                break;
	            }
	        }
	        if ($refNode) {
	            $rootNode->insertBefore($newNode, $refNode);
	        } else {
	            $rootNode->appendChild($newNode);
	        }
	    }
	}

	/**
	 * @param SimpleXMLElement $xml Root node that contains all of the "data" nodes below
	 * @param string $addOnId Add-on to import for
	 */
	public function importAddOnExtraDataFromXml(SimpleXMLElement $xml, $addOnId)
	{
		parent::importAddOnExtraDataFromXml($xml, $addOnId);

		$this->getModelFromCache('ThemeHouse_ObjectFields_Model_ObjectField')->importFieldsAddOnXml($xml->object_fields, $addOnId);
	}
}