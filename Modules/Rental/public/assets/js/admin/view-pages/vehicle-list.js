"use strict";
document.querySelectorAll("select.select-30 option").forEach((option) => {
    if (option.text.length > 30) {
        option.text = option.text.substring(0, 27) + "...";
    }
});
