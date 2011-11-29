<?php

interface Eav_Row_Interface
{
    public function setAttributeValue($attributeId, $value);
    public function hasAttributeValue($attributeId);
    public function getAttributeValue($attributeId);
}