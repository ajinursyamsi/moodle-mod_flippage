(function() {
    'use strict';

    var config = window.modFlipPageConfig || {};
    var book = document.getElementById('flippage-book');
    var status = document.querySelector('[data-flippage-status]');
    var counter = document.querySelector('[data-flippage-counter]');
    var prev = document.querySelector('[data-flippage-prev]');
    var next = document.querySelector('[data-flippage-next]');
    var exit = document.querySelector('[data-flippage-exit]');
    var viewport = document.querySelector('[data-flippage-viewport]');
    var zoomIn = document.querySelector('[data-flippage-zoom-in]');
    var zoomOut = document.querySelector('[data-flippage-zoom-out]');
    var zoomReset = document.querySelector('[data-flippage-zoom-reset]');
    var zoomValue = document.querySelector('[data-flippage-zoom-value]');
    var pageFlip = null;
    var totalPages = 0;
    var lastReportedPage = 0;
    var zoom = 1;

    function setStatus(message) {
        if (status) {
            status.textContent = message || '';
            status.hidden = !message;
        }
    }

    function setCounter(page, total) {
        if (!counter) {
            return;
        }
        var template = config.strings && config.strings.counter ? config.strings.counter : 'Page __PAGE__ of __TOTAL__';
        counter.textContent = template.replace('__PAGE__', page).replace('__TOTAL__', total);
    }

    function setExitVisibility(visible) {
        if (exit) {
            exit.hidden = !visible;
        }
    }

    function getVisibleEndPage(pageIndex) {
        var page = Math.max(1, pageIndex + 1);
        if (pageFlip && pageFlip.getOrientation && pageFlip.getOrientation() === 'landscape') {
            page = Math.min(totalPages, page + 1);
        }
        return page;
    }

    function postProgress(page, total) {
        page = Math.max(1, page);
        total = Math.max(1, total);
        if (page <= lastReportedPage && page < total) {
            return;
        }
        lastReportedPage = Math.max(lastReportedPage, page);

        var data = new FormData();
        data.append('cmid', config.cmid);
        data.append('sesskey', config.sesskey);
        data.append('page', page);
        data.append('total', total);

        fetch(config.markurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).catch(function() {
            // Progress is retried when the next page event is fired.
        });
    }

    function applyZoom(nextZoom) {
        zoom = Math.max(0.6, Math.min(2.5, nextZoom));
        book.style.setProperty('--flippage-zoom', zoom.toString());
        book.classList.toggle('is-zoomed', zoom > 1.01);
        if (zoomValue) {
            zoomValue.textContent = Math.round(zoom * 100) + '%';
        }
        if (pageFlip && pageFlip.update) {
            window.setTimeout(function() {
                pageFlip.update();
            }, 60);
        }
    }

    function bindZoomControls() {
        if (zoomIn) {
            zoomIn.addEventListener('click', function() {
                applyZoom(zoom + 0.1);
            });
        }
        if (zoomOut) {
            zoomOut.addEventListener('click', function() {
                applyZoom(zoom - 0.1);
            });
        }
        if (zoomReset) {
            zoomReset.addEventListener('click', function() {
                applyZoom(1);
                if (viewport) {
                    viewport.scrollLeft = 0;
                    viewport.scrollTop = 0;
                }
            });
        }
    }

    function initialiseFlip(elements) {
        totalPages = elements.length;
        book.innerHTML = '';
        elements.forEach(function(element) {
            book.appendChild(element);
        });

        pageFlip = new window.St.PageFlip(book, {
            width: 720,
            height: 960,
            size: 'stretch',
            minWidth: 260,
            maxWidth: 900,
            minHeight: 340,
            maxHeight: 1200,
            showCover: false,
            mobileScrollSupport: true,
            drawShadow: true,
            flippingTime: 700
        });

        pageFlip.loadFromHTML(elements);
        pageFlip.on('flip', function(event) {
            var pageIndex = event.data || 0;
            var page = pageIndex + 1;
            var visibleEndPage = getVisibleEndPage(pageIndex);
            setCounter(page, totalPages);
            setExitVisibility(visibleEndPage >= totalPages);
            postProgress(visibleEndPage, totalPages);
        });
        pageFlip.on('changeOrientation', function() {
            var pageIndex = pageFlip.getCurrentPageIndex ? pageFlip.getCurrentPageIndex() : 0;
            var visibleEndPage = getVisibleEndPage(pageIndex);
            setExitVisibility(visibleEndPage >= totalPages);
            postProgress(visibleEndPage, totalPages);
        });

        if (prev) {
            prev.addEventListener('click', function() {
                pageFlip.flipPrev();
            });
        }
        if (next) {
            next.addEventListener('click', function() {
                pageFlip.flipNext();
            });
        }
        bindZoomControls();
        applyZoom(1);

        var startPage = Math.max(0, (config.progress && config.progress.currentpage ? config.progress.currentpage : 1) - 1);
        if (startPage > 0 && startPage < totalPages) {
            pageFlip.turnToPage(startPage);
        }
        setCounter(startPage + 1, totalPages);
        setExitVisibility(getVisibleEndPage(startPage) >= totalPages);
        postProgress(getVisibleEndPage(startPage), totalPages);
        setStatus('');
    }

    function pageElement(child) {
        var page = document.createElement('div');
        page.className = 'flippage-page';
        page.appendChild(child);
        return page;
    }

    async function pdfPages(file) {
        var pdfjs = await import(config.pdfjsurl);
        pdfjs.GlobalWorkerOptions.workerSrc = config.pdfworkerurl;
        var pdf = await pdfjs.getDocument(file.url).promise;
        var pages = [];

        for (var pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
            var pdfPage = await pdf.getPage(pageNumber);
            var viewport = pdfPage.getViewport({scale: 1.5});
            var canvas = document.createElement('canvas');
            var context = canvas.getContext('2d');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await pdfPage.render({canvasContext: context, viewport: viewport}).promise;
            pages.push(pageElement(canvas));
        }

        return pages;
    }

    async function start() {
        if (!window.St || !window.St.PageFlip) {
            setStatus('Flip page library could not be loaded.');
            return;
        }

        var files = config.files || [];
        if (!files.length) {
            setStatus('');
            return;
        }

        setStatus(config.strings && config.strings.loading ? config.strings.loading : 'Loading document...');
        var pdfs = files.filter(function(file) {
            return file.ispdf;
        });

        try {
            var pages = pdfs.length ? await pdfPages(pdfs[0]) : [];
            if (!pages.length) {
                setStatus('No supported pages were found.');
                return;
            }
            initialiseFlip(pages);
        } catch (error) {
            setStatus(error && error.message ? error.message : 'The document could not be loaded.');
        }
    }

    start();
}());
