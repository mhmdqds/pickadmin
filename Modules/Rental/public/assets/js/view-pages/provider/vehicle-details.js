"use strict";
$(document).ready(function () {

    $('.see-more').on('click', function (e) {
        e.preventDefault();

        const $descriptionText = $(this).closest('.description-text');
        const $shortDescription = $descriptionText.find('.short-description');
        const $fullDescription = $descriptionText.find('.full-description');

        $shortDescription.toggle();
        $fullDescription.toggle();

        if ($fullDescription.is(':visible')) {
            $(this).text('See less');
        } else {
            $(this).text('See more');
        }
    });
    async function renderFileThumbnail(element) {
        const fileUrl = element.getAttribute("data-pdf-url");
        const canvas = element.querySelector(".pdf-preview");
        const thumbnail = element.querySelector(".pdf-thumbnail");
        const fileNameSpan = element.querySelector(".file-name");
        const downloadButton = element.querySelector(".download-btn");

        const fullFileName = fileUrl.split('/').pop();
        const fileExtension = fullFileName.split('.').pop().toLowerCase();
        const fileNameWithoutExtension = fullFileName.replace(/\.[^/.]+$/, '');

        const truncatedFileName =
            fileNameWithoutExtension.length > 20 ?
                `${fileNameWithoutExtension.substring(0, 17)}...` :
                fileNameWithoutExtension;
        const displayedFileName = `${truncatedFileName}.${fileExtension}`;

        fileNameSpan.textContent = displayedFileName;
        downloadButton.setAttribute("title", fullFileName);

        if (fileExtension === "pdf") {
            const ctx = canvas.getContext("2d");

            try {
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({
                    scale: 0.5
                });
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({
                    canvasContext: ctx,
                    viewport
                }).promise;

                thumbnail.src = canvas.toDataURL();
            } catch (error) {
                console.error("Error rendering PDF thumbnail:", error);
                thumbnail.src = "{{ asset('public/assets/admin/img/blank2.png') }}";
            }
        } else if (["jpg", "jpeg", "png", "gif", "bmp"].includes(fileExtension)) {
            thumbnail.src = fileUrl;
        } else {
            const fileIconPath = `{{ asset('public/assets/admin/img/icons') }}/${fileExtension}.png`;
            const fallbackIconPath =
                "{{ asset('public/assets/admin/img/blank2.png') }}";

            const iconExists = await checkFileIconExistence(fileIconPath);

            thumbnail.src = iconExists ? fileIconPath : fallbackIconPath;
        }

        thumbnail.style.display = "block";
        canvas.style.display = "none";
    }

    async function checkFileIconExistence(iconPath) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = iconPath;
        });
    }

    document.querySelectorAll(".pdf-single").forEach(renderFileThumbnail);

    window.openPdf = function(fileUrl) {
        window.open(fileUrl, "_blank");
    };

    window.downloadPdf = function(event, buttonElement) {
        event.stopPropagation();

        const fileUrl = buttonElement.closest(".pdf-single").getAttribute("data-pdf-url");
        const link = document.createElement("a");
        link.href = fileUrl;
        link.download = fileUrl.split("/").pop();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };


    function imageZoom() {
        let elements = document.querySelectorAll(".cz-image-zoom");
        for (let i = 0; i < elements.length; i++) {
            new Drift(elements[i], {
                paneContainer: elements[i].parentElement.querySelector(
                    ".cz-image-zoom-pane"
                ),
            });
        }
    }

    imageZoom();


    $(document).on('click', '.pdf-single', function() {
        const pdfUrl = $(this).data('pdf-url');
        openPdf(pdfUrl);
    });

    $(document).on('click', '.download-btn', function(event) {
        event.stopPropagation();
        downloadPdf(event, this);
    });


    const themeDirection = $("html").attr("dir");

    function renderOwlCarouselSilder() {
        var sync1 = $("#sync1");
        var sync2 = $("#sync2");
        var thumbnailItemClass = ".owl-item";
        var slides = sync1.owlCarousel({
            startPosition: 12,
            items: 1,
            loop: false,
            margin: 0,
            mouseDrag: true,
            touchDrag: true,
            pullDrag: false,
            scrollPerPage: true,
            autoplayHoverPause: false,
            nav: false,
            dots: false,
            rtl: themeDirection && themeDirection.toString() === "rtl",
        })
            .on("changed.owl.carousel", syncPosition);

        function syncPosition(el) {
            var owl_slider = $(this).data("owl.carousel");
            var loop = owl_slider.options.loop;

            var current = el.item.index;

            var owl_thumbnail = sync2.data("owl.carousel");
            var itemClass = "." + owl_thumbnail.options.itemClass;

            var thumbnailCurrentItem = sync2
                .find(itemClass)
                .removeClass("synced")
                .eq(current);
            thumbnailCurrentItem.addClass("synced");

            if (!thumbnailCurrentItem.hasClass("active")) {
                var duration = 500;
                sync2.trigger("to.owl.carousel", [current, duration, true]);
            }

            setTimeout(function() {
                imageZoom();
            }, 500);
        }

        var thumbs = sync2.owlCarousel({
            startPosition: 12,
            items: 4,
            loop: false,
            margin: 10,
            autoplay: false,
            nav: false,
            dots: false,
            rtl: themeDirection && themeDirection.toString() === "rtl",
            responsive: {
                576: {
                    items: 4,
                },
                768: {
                    items: 4,
                },
                992: {
                    items: 4,
                },
                1200: {
                    items: 5,
                },
                1400: {
                    items: 5,
                },
            },
            onInitialized: function(e) {
                var thumbnailCurrentItem = $(e.target)
                    .find(thumbnailItemClass)
                    .eq(this._current);
                thumbnailCurrentItem.addClass("synced");
            },
        })
            .on("click", thumbnailItemClass, function(e) {
                e.preventDefault();
                var duration = 500;
                var itemIndex = $(e.target).parents(thumbnailItemClass).index();
                sync1.trigger("to.owl.carousel", [itemIndex, duration, true]);
            })
            .on("changed.owl.carousel", function(el) {
                var number = el.item.index;
                var owl_slider = sync1.data("owl.carousel");
                owl_slider.to(number, 500, true);
            });

        sync1.owlCarousel();
    }

    renderOwlCarouselSilder();

});
