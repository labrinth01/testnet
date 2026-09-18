<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
    "NAME" => GetMessage("CUSTOM_SEO_REPORT_NAME"),
    "DESCRIPTION" => GetMessage("CUSTOM_SEO_REPORT_DESC"),
    "CACHE_PATH" => "Y",
    "COMPLEX" => "N",
    "PATH" => array(
        "ID" => "custom",
        "NAME" => GetMessage("CUSTOM_SEO_REPORT_PATH"),
        "CHILD" => array(
            "ID" => "catalog_tools",
            "NAME" => GetMessage("CUSTOM_SEO_REPORT_PATH_CHILD"),
        ),
    ),
);
