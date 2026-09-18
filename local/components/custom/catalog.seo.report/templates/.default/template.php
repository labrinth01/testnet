<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CBitrixComponentTemplate $this */

$this->setFrameMode(true);
$f = $arResult["FILTER"];
$action = htmlspecialcharsbx($arResult["FORM_ACTION"]);

if (!function_exists("seoReportUrl")) {
function seoReportUrl($action, $f, $override)
{
    $params = array_merge(
        array(
            "range" => $f["RANGE"],
            "sort" => $f["SORT"],
            "order" => $f["ORDER"],
            "q" => $f["Q"],
        ),
        $override
    );
    $params = array_filter($params, function ($v) {
        return $v !== "" && $v !== null;
    });
    return $action.(strpos($action, "?") === false ? "?" : "&").http_build_query($params);
}
}
?>
<div class="seo-report">
    <a class="seo-report__back" href="/">Назад на главную</a>
    <header class="seo-report__head">
        <p class="seo-report__eyebrow">Каталог / SEO</p>
        <h1 class="seo-report__title">Отчёт по товарам</h1>
        <p class="seo-report__lead">Карточка — фото, название, цена и оценка. Проверки открываются по нажатию.</p>
    </header>

    <section class="seo-report__stats">
        <div class="seo-report__stat">
            <span class="seo-report__stat-value"><?= intval($arResult["ALL_COUNT"]) ?></span>
            <span class="seo-report__stat-label">товаров</span>
        </div>
        <div class="seo-report__stat">
            <span class="seo-report__stat-value"><?= intval($arResult["AVG_SCORE"]) ?>%</span>
            <span class="seo-report__stat-label">средняя оценка</span>
        </div>
        <div class="seo-report__stat seo-report__stat--alert">
            <span class="seo-report__stat-value"><?= intval($arResult["LOW_COUNT"]) ?></span>
            <span class="seo-report__stat-label">слабых</span>
        </div>
        <div class="seo-report__stat">
            <span class="seo-report__stat-value"><?= intval($arResult["READY_COUNT"]) ?></span>
            <span class="seo-report__stat-label">готовых 80%+</span>
        </div>
    </section>

    <div class="seo-report__chips">
        <?php
        $chips = array(
            "all" => "Все",
            "weak" => "Слабые 0–49%",
            "mid" => "Средние 50–79%",
            "ready" => "Готовые 80–100%",
        );
        foreach ($chips as $key => $label):
            $cls = "seo-report__chip";
            if ($f["RANGE"] === $key) {
                $cls .= $key === "weak" ? " seo-report__chip--red" : " seo-report__chip--on";
            }
        ?>
            <a class="<?= $cls ?>" href="<?= htmlspecialcharsbx(seoReportUrl($arResult["FORM_ACTION"], $f, array("range" => $key))) ?>"><?= htmlspecialcharsbx($label) ?></a>
        <?php endforeach; ?>
    </div>

    <form class="seo-report__filters" method="get" action="<?= $action ?>">
        <input type="hidden" name="range" value="<?= htmlspecialcharsbx($f["RANGE"]) ?>">
        <input class="seo-report__search" type="search" name="q" value="<?= htmlspecialcharsbx($f["Q"]) ?>" placeholder="Поиск по названию">
        <select name="sort">
            <option value="seo"<?= $f["SORT"] === "seo" ? " selected" : "" ?>>По оценке</option>
            <option value="price"<?= $f["SORT"] === "price" ? " selected" : "" ?>>По цене</option>
            <option value="name"<?= $f["SORT"] === "name" ? " selected" : "" ?>>По названию</option>
            <option value="id"<?= $f["SORT"] === "id" ? " selected" : "" ?>>По ID</option>
        </select>
        <select name="order">
            <option value="desc"<?= $f["ORDER"] === "desc" ? " selected" : "" ?>>Сначала лучше / больше</option>
            <option value="asc"<?= $f["ORDER"] === "asc" ? " selected" : "" ?>>Сначала хуже / меньше</option>
        </select>
        <button class="seo-report__btn seo-report__btn--primary" type="submit">Применить</button>
        <a class="seo-report__btn" href="<?= $action ?>">Сбросить</a>
    </form>
    <p class="seo-report__count">Показано <?= intval($arResult["SHOWN_COUNT"]) ?> из <?= intval($arResult["ALL_COUNT"]) ?></p>

    <?php if (empty($arResult["ITEMS"])): ?>
        <p class="seo-report__empty">Ничего не найдено. Смените фильтр.</p>
    <?php else: ?>
        <ul class="seo-report__grid">
            <?php foreach ($arResult["ITEMS"] as $item):
                $tone = $item["SCORE"] >= 80 ? "ok" : ($item["SCORE"] >= 50 ? "warn" : "fail");
            ?>
                <li>
                    <button
                        type="button"
                        class="seo-report__card"
                        data-open="<?= intval($item["ID"]) ?>"
                    >
                        <div class="seo-report__media<?= empty($item["PICTURE"]) ? " seo-report__media--empty" : "" ?>">
                            <?php if (!empty($item["PICTURE"])): ?>
                                <img src="<?= htmlspecialcharsbx($item["PICTURE"]["SRC"]) ?>" alt="<?= htmlspecialcharsbx($item["NAME"]) ?>">
                            <?php else: ?>
                                <span>нет фото</span>
                            <?php endif; ?>
                            <span class="seo-report__badge seo-report__badge--<?= $tone ?>"><?= intval($item["SCORE"]) ?>%</span>
                        </div>
                        <h2 class="seo-report__name"><?= htmlspecialcharsbx($item["NAME"]) ?></h2>
                        <p class="seo-report__price"><?= number_format(intval($item["PRICE"]), 0, ".", " ") ?> ₽</p>
                    </button>
                    <a class="seo-report__btn" href="/catalog/detail.php?code=<?= rawurlencode($item["CODE"]) ?>">Открыть карточку товара →</a>

                    <div class="seo-report__panel" id="seo-panel-<?= intval($item["ID"]) ?>" hidden>
                        <button type="button" class="seo-report__back" data-close>Назад</button>
                        <div class="seo-report__panel-head">
                            <h3><?= htmlspecialcharsbx($item["NAME"]) ?></h3>
                            <div class="seo-report__score seo-report__score--<?= $tone ?>"><?= intval($item["SCORE"]) ?>%</div>
                        </div>
                        <p class="seo-report__price"><?= number_format(intval($item["PRICE"]), 0, ".", " ") ?> ₽</p>
                        <ul class="seo-report__checks">
                            <?php foreach ($item["CHECKS"] as $check): ?>
                                <li class="seo-report__check seo-report__check--<?= htmlspecialcharsbx($check["STATUS"]) ?>">
                                    <span class="seo-report__check-label"><?= htmlspecialcharsbx($check["LABEL"]) ?></span>
                                    <span class="seo-report__check-pts"><?= intval($check["POINTS"]) ?>/<?= intval($check["MAX"]) ?></span>
                                    <span class="seo-report__check-detail"><?= htmlspecialcharsbx($check["DETAIL"]) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="seo-report__btn" data-close>Назад к списку</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="seo-report__overlay" data-overlay hidden></div>
</div>
