<?php
require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main\Loader;

header("Content-Type: text/html; charset=UTF-8");

global $USER;
if (!is_object($USER) || !$USER->IsAdmin()) {
    http_response_code(403);
    echo "Доступ только для администратора. Войдите в /bitrix/admin/ и откройте эту страницу снова.";
    die();
}

if (!Loader::includeModule("iblock")) {
    die("Модуль iblock не установлен");
}

function seoSeedTextColor($im, $bg)
{
    $r = ($bg >> 16) & 0xFF;
    $g = ($bg >> 8) & 0xFF;
    $b = $bg & 0xFF;
    $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b);
    if ($luma > 140) {
        return imagecolorallocate($im, 22, 22, 22);
    }
    return imagecolorallocate($im, 255, 255, 255);
}

function seoSeedMakePicture($title, $hex)
{
    if (!function_exists("imagecreatetruecolor")) {
        return false;
    }
    $im = imagecreatetruecolor(800, 800);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, 800, 800, $white);
    $rgb = sscanf($hex, "#%02x%02x%02x");
    $bg = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);
    imagefilledrectangle($im, 60, 60, 740, 740, $bg);
    $tmpBase = tempnam(sys_get_temp_dir(), "seoimg");
    $tmp = $tmpBase . ".jpg";
    @rename($tmpBase, $tmp);
    imagejpeg($im, $tmp, 90);
    imagedestroy($im);
    $file = CFile::MakeFileArray($tmp);
    if (!is_array($file)) {
        @unlink($tmp);
        return false;
    }
    $file["name"] = $title . ".jpg";
    $file["type"] = "image/jpeg";
    $file["MODULE_ID"] = "iblock";
    return $file;
}

function seoSeedProductPicture($code, $title, $hex)
{
    $path = $_SERVER["DOCUMENT_ROOT"] . "/upload/seo-products/" . $code . ".jpg";
    if (file_exists($path) && filesize($path) > 1000) {
        $file = CFile::MakeFileArray($path);
        if (is_array($file)) {
            $file["name"] = $code . ".jpg";
            $file["type"] = "image/jpeg";
            $file["MODULE_ID"] = "iblock";
            return $file;
        }
    }
    return seoSeedMakePicture($title, $hex);
}

$type = new CIBlockType();
$rsType = CIBlockType::GetByID("catalog");
if (!$rsType->Fetch()) {
    $type->Add(array(
        "ID" => "catalog",
        "SECTIONS" => "Y",
        "IN_RSS" => "N",
        "SORT" => 100,
        "LANG" => array(
            "ru" => array("NAME" => "Каталог", "ELEMENT_NAME" => "Товар", "SECTION_NAME" => "Раздел"),
            "en" => array("NAME" => "Catalog", "ELEMENT_NAME" => "Product", "SECTION_NAME" => "Section"),
        ),
    ));
}

$iblockId = 0;
$rs = CIBlock::GetList(array(), array("TYPE" => "catalog", "CODE" => "demo_shop", "CHECK_PERMISSIONS" => "N"));
if ($row = $rs->Fetch()) {
    $iblockId = intval($row["ID"]);
}

if ($iblockId <= 0) {
    $ib = new CIBlock();
    $iblockId = $ib->Add(array(
        "ACTIVE" => "Y",
        "NAME" => "Демо-магазин",
        "CODE" => "demo_shop",
        "IBLOCK_TYPE_ID" => "catalog",
        "SITE_ID" => array(SITE_ID ? SITE_ID : "s1"),
        "SORT" => 100,
        "GROUP_ID" => array("2" => "R"),
        "INDEX_ELEMENT" => "Y",
        "WORKFLOW" => "N",
        "BIZPROC" => "N",
        "VERSION" => 2,
        "LIST_PAGE_URL" => "/catalog/",
        "DETAIL_PAGE_URL" => "/catalog/#ELEMENT_CODE#/",
        "SECTION_PAGE_URL" => "/catalog/",
    ));
    if (!$iblockId) {
        die("Не удалось создать инфоблок: ".$ib->LAST_ERROR);
    }
}

$neededProps = array(
    "BRAND" => "Бренд",
    "MATERIAL" => "Материал",
    "COLOR" => "Цвет",
    "COUNTRY" => "Страна",
    "META_TITLE" => "Meta title",
    "META_DESCRIPTION" => "Meta description",
    "PRICE" => "Цена",
);

foreach ($neededProps as $code => $name) {
    $exists = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $iblockId, "CODE" => $code))->Fetch();
    if (!$exists) {
        $prop = new CIBlockProperty();
        $prop->Add(array(
            "IBLOCK_ID" => $iblockId,
            "NAME" => $name,
            "CODE" => $code,
            "ACTIVE" => "Y",
            "SORT" => 100,
            "PROPERTY_TYPE" => "S",
            "FILTRABLE" => "Y",
            "SEARCHABLE" => "Y",
        ));
    }
}

$products = array(
    array("Pulse Air", "pulse-air", "Беспроводные наушники Pulse Air с активным шумоподавлением и автономностью до 28 часов. Драйверы 10 мм, Bluetooth 5.3, быстрая зарядка USB-C. Подходят для города, поездок и работы. В комплекте чехол, кабель и сменные амбушюры трёх размеров. Гарантия 12 месяцев.", "Наушники Pulse Air — купить беспроводные с ANC", "Беспроводные наушники Pulse Air с шумоподавлением, 28 часов работы и Bluetooth 5.3. Доставка по России, гарантия 12 месяцев.", array("Pulse", "пластик/силикон", "графит", "Китай"), true, "#2b2b2b"),
    array("Умные часы North Watch S2", "north-watch-s2", "Умные часы North Watch S2 с AMOLED 1.43\", датчиком ЧСС, GPS и защитой IP68. До 10 дней без подзарядки. Спорт-режимы, уведомления, NFC-оплата. Ремешок 22 мм, сменный.", "Умные часы North Watch S2 с GPS и NFC", "Купить North Watch S2: AMOLED, GPS, NFC, защита IP68. Автономность до 10 дней. Официальная гарантия.", array("North", "алюминий", "чёрный", "Вьетнам"), true, "#1a1a1a"),
    array("Кофемашина Barista Mini", "barista-mini", "Компактная рожковая кофемашина Barista Mini для дома. Давление 15 бар, бойлер 1.1 л, капучинатор. Корпус из нержавеющей стали. Готовит эспрессо и капучино за минуту.", "Кофемашина", "Кофемашина домой.", array("Barista", "нержавеющая сталь", "серебро", "Италия"), true, "#c4c4c4"),
    array("Рюкзак Transit 24L", "transit-24", "Городской рюкзак 24 литра.", "Рюкзак Transit 24L городской для ноутбука 15 дюймов купить недорого с доставкой по России официальный магазин", "Городской рюкзак Transit 24L с отделением для ноутбука 15\", водоотталкивающая ткань, вес 780 г. Доставка и возврат 14 дней.", array("Transit", "полиэстер", "оливковый", "Вьетнам"), true, "#556b2f"),
    array("Настольная лампа Halo Desk", "halo-desk", "Настольная LED-лампа Halo Desk с температурой света 3000–6500K и яркостью 800 лм. USB-C, сенсорная панель, таймер. Подходит для работы за компьютером и чтения.", "Настольная лампа Halo Desk LED 800 лм", "LED-лампа Halo Desk: 3000–6500K, 800 лм, USB-C, сенсорное управление. Для работы и чтения.", array("Halo", "алюминий", "белый", "Китай"), false, "#eeeeee"),
    array("Электрочайник Glass 1.7", "glass-17", "Стеклянный чайник 1.7 л с подсветкой и съёмным фильтром от накипи. Мощность 2200 Вт, автоотключение, блокировка включения без воды. Подставка 360°.", "", "", array("HomeLine", "стекло", "прозрачный", "Китай"), true, "#88aacc"),
    array("Коврик для йоги Studio Pro", "studio-pro-mat", "", "Коврик для йоги Studio Pro 6 мм", "Нескользящий коврик Studio Pro толщиной 6 мм. Для йоги, пилатеса и растяжки. Размер 183×61 см.", array("Studio", "TPE", "пыльная роза", "Тайвань"), true, "#c9a0a0"),
    array("Клавиатура Type Compact", "type-compact", "Низкопрофильная механическая клавиатура Type Compact на переключателях brown. Раскладка ANSI, Bluetooth + USB-C, аккумулятор на 40 часов. Алюминиевый корпус, горячая замена свитчей.", "Клавиатура Type Compact механическая Bluetooth", "Механическая клавиатура Type Compact: brown-свитчи, Bluetooth, 40 часов, hot-swap. Для работы и набора текста.", array("Type", "", "серый", ""), true, "#6e6e6e"),
    array("Пылесос Stick One", "stick-one", "Вертикальный беспроводной пылесос Stick One с циклонным фильтром и съёмным аккумулятором. До 45 минут работы, турбощетка для ковра, насадка для мебели. Вес 1.6 кг без трубы.", "Пылесос Stick One вертикальный беспроводной", "Stick One: до 45 минут автономности, циклон, турбощетка. Лёгкий вертикальный пылесос для квартиры.", array("Stick", "пластик", "белый", "Китай"), true, "#f2f2f2"),
    array("Термокружка Day 450", "day-450", "Держит тепло 6 часов.", "Кружка", "Термо.", array("", "", "", ""), false, "#d0d0d0"),
    array("Монитор Frame 27 QHD", "frame-27", "27-дюймовый монитор Frame QHD 2560×1440, IPS, 75 Гц, USB-C 65 Вт. Тонкие рамки, VESA 100, регулировка высоты. Для дизайна, кода и офиса. Покрытие антиблик.", "Монитор Frame 27 QHD IPS USB-C", "Монитор 27\" QHD IPS 75 Гц с USB-C 65 Вт. Тонкие рамки, регулировка высоты, VESA. Для работы и творчества.", array("Frame", "пластик", "чёрный", "Китай"), true, "#111111"),
    array("Кроссовки Run Lite", "run-lite", "Лёгкие кроссовки Run Lite для ежедневных пробежек до 10 км. Пена EVA, сетчатый верх, дроп 8 мм. Размеры 36–45. Стелька съёмная.", "Кроссовки Run Lite для бега", "Бег до 10 км: кроссовки Run Lite с пеной EVA и сетчатым верхом. Размеры 36–45, дроп 8 мм.", array("Run", "текстиль/резина", "белый/чёрный", "Вьетнам"), true, "#e8e8e8"),
    array("Увлажнитель Mist 4L", "mist-4l", "Ультразвуковой увлажнитель Mist на 4 литра с таймером и ночной подсветкой. Расход до 300 мл/ч, площадь до 35 м². Тихий режим 28 дБ. Верхний залив воды.", "", "Увлажнитель воздуха Mist 4L, до 35 м², тихий режим 28 дБ, верхний залив. Для спальни и детской.", array("Mist", "пластик", "белый", "Китай"), true, "#dbeafe"),
    array("Набор посуды Cast 6", "cast-6", "Набор кастрюль Cast 6 из нержавеющей стали 18/10: 1.5, 2.5 и 4 л с крышками. Капсульное дно, подходит для индукции. Ручки клёпаные, не нагреваются.", "Набор посуды Cast 6 нержавеющая сталь", "", array("Cast", "нержавеющая сталь", "сталь", "Португалия"), true, "#b8b8b8"),
    array("Велосипедный фонарь Beam 400", "beam-400", "Передний фонарь Beam 400 люмен, USB-C, 5 режимов, влагозащита IPX6. Крепление на руль 22–35 мм. До 12 часов в эко-режиме. Алюминиевый корпус.", "Велофонарь Beam 400 люмен USB-C IPX6", "Фонарь Beam 400 лм для велосипеда: USB-C, IPX6, 5 режимов, до 12 часов. Крепление на руль в комплекте.", array("Beam", "алюминий", "чёрный", "Тайвань"), false, "#222222"),
    array("Плед Wool 180", "wool-180", "Тёплый плед.", "Плед Wool 180", "Плед из шерсти.", array("Wool", "шерсть 80%", "", ""), true, "#7a3e32"),
    array("Роутер Mesh Duo", "mesh-duo", "Двухдиапазонный Mesh-роутер Duo Wi-Fi 6, покрытие до 120 м² на узел. 4 гигабитных порта, EasyMesh. Гостевая сеть, родительский контроль, приложение на телефон.", "Роутер Mesh Duo Wi-Fi 6 EasyMesh", "Wi-Fi 6 Mesh Duo: до 120 м², гигабитные порты, приложение. Расширяемая домашняя сеть.", array("Mesh", "пластик", "белый", "Китай"), true, "#f7f7f7"),
    array("Блендер Jar 800", "jar-800", "Стационарный блендер Jar 800 Вт, чаша 1.5 л Tritan, 3 скорости + импульс. Ножи из нержавеющей стали, подходит для смузи, супов и льда. Нескользящее основание.", "Блендер Jar 800 Вт чаша 1.5 л", "Блендер Jar 800 Вт с чашей Tritan 1.5 л. Смузи, суп, лёд. Три скорости и импульсный режим.", array("Jar", "Tritan/сталь", "графит", "Китай"), true, "#4a4a4a"),
    array("Настенные часы Quiet 30", "quiet-30", "", "", "", array("", "", "дерево", ""), true, "#c4a574"),
    array("Powerbank Volt 20k", "volt-20k", "Внешний аккумулятор Volt 20 000 мА·ч с USB-C PD 30 Вт и двумя USB-A. Заряд ноутбука, телефона и наушников. Индикатор процентов, защита от перегрева. Вес 340 г.", "Powerbank Volt 20k PD 30 Вт", "Powerbank 20 000 мА·ч Volt: PD 30 Вт, USB-C и 2×USB-A. Заряд телефона и ноутбука в дороге.", array("Volt", "пластик", "графит", "Китай"), true, "#2f2f2f"),
);

$prices = array(
    "pulse-air" => 7990,
    "north-watch-s2" => 12490,
    "barista-mini" => 21990,
    "transit-24" => 4590,
    "halo-desk" => 3290,
    "glass-17" => 2490,
    "studio-pro-mat" => 1890,
    "type-compact" => 6490,
    "stick-one" => 15990,
    "day-450" => 1290,
    "frame-27" => 28990,
    "run-lite" => 6990,
    "mist-4l" => 3990,
    "cast-6" => 8990,
    "beam-400" => 2190,
    "wool-180" => 3490,
    "mesh-duo" => 9990,
    "jar-800" => 4290,
    "quiet-30" => 1590,
    "volt-20k" => 3790,
);

$el = new CIBlockElement();
$created = 0;
$updated = 0;

foreach ($products as $p) {
    list($name, $code, $desc, $metaTitle, $metaDesc, $specs, $withPhoto, $color) = $p;
    $exist = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $iblockId, "CODE" => $code, "CHECK_PERMISSIONS" => "N"), false, false, array("ID"))->Fetch();

    $fields = array(
        "IBLOCK_ID" => $iblockId,
        "NAME" => $name,
        "CODE" => $code,
        "ACTIVE" => "Y",
        "DETAIL_TEXT" => $desc,
        "DETAIL_TEXT_TYPE" => "text",
        "PREVIEW_TEXT" => $desc,
        "PREVIEW_TEXT_TYPE" => "text",
        "PROPERTY_VALUES" => array(
            "BRAND" => $specs[0],
            "MATERIAL" => $specs[1],
            "COLOR" => $specs[2],
            "COUNTRY" => $specs[3],
            "META_TITLE" => $metaTitle,
            "META_DESCRIPTION" => $metaDesc,
            "PRICE" => isset($prices[$code]) ? $prices[$code] : 0,
        ),
        "IPROPERTY_TEMPLATES" => array(
            "ELEMENT_META_TITLE" => $metaTitle,
            "ELEMENT_META_DESCRIPTION" => $metaDesc,
        ),
    );

    if ($withPhoto) {
        $file = seoSeedProductPicture($code, $name, $color);
        if ($file) {
            $fields["PREVIEW_PICTURE"] = $file;
            $fields["DETAIL_PICTURE"] = $file;
        }
    }

    if ($exist) {
        $el->Update($exist["ID"], $fields);
        $updated++;
    } else {
        $id = $el->Add($fields);
        if ($id) {
            $created++;
        } else {
            echo "Ошибка ".$code.": ".$el->LAST_ERROR."<br>";
        }
    }
}

$constFile = $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/seo_iblock.php";
file_put_contents($constFile, "<?php\nif (!defined(\"SEO_REPORT_IBLOCK_ID\")) {\n    define(\"SEO_REPORT_IBLOCK_ID\", ".$iblockId.");\n}\n");

echo "<!DOCTYPE html><html lang='ru'><head><meta charset='utf-8'><title>Сидер каталога</title></head><body style='font-family:sans-serif;padding:32px;'>";
echo "<h1>Каталог готов</h1>";
echo "<p>Инфоблок ID: <b>".$iblockId."</b></p>";
echo "<p>Создано: ".$created.", обновлено: ".$updated."</p>";
echo "<p><a href='/seo-report/'>Открыть SEO-отчёт</a> · <a href='/catalog/'>Каталог</a></p>";
echo "<p>Если отчёт пустой, в параметрах компонента на /seo-report/ укажите IBLOCK_ID = ".$iblockId."</p>";
echo "</body></html>";
