(function () {
    var root = document.querySelector(".seo-report");
    if (!root) return;
    var overlay = root.querySelector("[data-overlay]");

    function closeAll() {
        root.querySelectorAll(".seo-report__panel").forEach(function (p) {
            p.hidden = true;
        });
        if (overlay) overlay.hidden = true;
        document.body.style.overflow = "";
    }

    root.addEventListener("click", function (e) {
        var openBtn = e.target.closest("[data-open]");
        if (openBtn) {
            closeAll();
            var panel = document.getElementById("seo-panel-" + openBtn.getAttribute("data-open"));
            if (panel) {
                panel.hidden = false;
                if (overlay) overlay.hidden = false;
                document.body.style.overflow = "hidden";
            }
            return;
        }
        if (e.target.closest("[data-close]") || e.target === overlay) {
            closeAll();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") closeAll();
    });
})();
