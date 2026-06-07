"use strict";
$(document).on("click", ".print-Div", function () {
    if ($("html").attr("dir") === "rtl") {
        $("html").attr("dir", "ltr");
        let printContents = document.getElementById("printableArea").innerHTML;
        let originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        $(".initial-38-1").attr("dir", "rtl");
        window.print();
        document.body.innerHTML = originalContents;
        $("html").attr("dir", "rtl");
        location.reload();
    } else {
        let printContents = document.getElementById("printableArea").innerHTML;
        let originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
});
