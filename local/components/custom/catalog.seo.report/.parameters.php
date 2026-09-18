<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!CModule::IncludeModule("iblock")) {
    return;
}

$arTypes = CIBlockParameters::GetIBlockTypes();
$arIBlocks = array();
$iblockType = isset($arCurrentValues["IBLOCK_TYPE"]) ? $arCurrentValues["IBLOCK_TYPE"] : "";

$rs = CIBlock::GetList(
    array("SORT" => "ASC"),
    array("SITE_ID" => $_REQUEST["site"], "TYPE" => $iblockType)
);
while ($ar = $rs->Fetch()) {
    $arIBlocks[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

$arComponentParameters = array(
    "GROUPS" => array(
        "FILTER" => array(
            "NAME" => GetMessage("CUSTOM_SEO_GROUP_FILTER"),
        ),
    ),
    "PARAMETERS" => array(
        "IBLOCK_TYPE" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("CUSTOM_SEO_IBLOCK_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arTypes,
            "DEFAULT" => "catalog",
            "REFRESH" => "Y",
        ),
        "IBLOCK_ID" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("CUSTOM_SEO_IBLOCK_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ),
        "DETAIL_URL" => CIBlockParameters::GetPathTemplateParam(
            "DETAIL",
            "DETAIL_URL",
            GetMessage("CUSTOM_SEO_DETAIL_URL"),
            "",
            "URL_TEMPLATES"
        ),
        "CACHE_TIME" => array("DEFAULT" => 0),
        "CACHE_GROUPS" => array(
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("CUSTOM_SEO_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ),
    ),
);
