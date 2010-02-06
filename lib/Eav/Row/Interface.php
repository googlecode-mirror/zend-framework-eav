<?php

interface Eav_Row_Interface
{
    public function setOptionValue($optionId, $value);
    public function hasOptionValue($optionId);
    public function getOptionValue($optionId);
}