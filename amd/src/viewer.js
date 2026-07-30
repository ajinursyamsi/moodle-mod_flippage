// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Flip Page PDF viewer.
 *
 * @module     mod_flippage/viewer
 * @copyright  2026 Aji Nursyamsi <aji.nursyamsi17@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Load a non-AMD browser script without letting anonymous define() leak into RequireJS.
 *
 * @param {String} url Script URL.
 * @returns {Promise<void>}
 */
const loadBrowserScript = url => new Promise((resolve, reject) => {
    if (window.St && window.St.PageFlip) {
        resolve();
        return;
    }

    const originalDefine = window.define;
    if (originalDefine && originalDefine.amd) {
        window.define = undefined;
    }

    const script = document.createElement('script');
    script.src = url;
    script.async = true;
    script.onload = () => {
        if (originalDefine) {
            window.define = originalDefine;
        }
        resolve();
    };
    script.onerror = () => {
        if (originalDefine) {
            window.define = originalDefine;
        }
        reject(new Error('Flip page library could not be loaded.'));
    };
    document.head.appendChild(script);
});

/**
 * Load PDF.js through a native module script, outside RequireJS.
 *
 * @param {String} url Loader module URL.
 * @returns {Promise<Object>}
 */
const loadPdfJs = url => new Promise((resolve, reject) => {
    if (window.modFlipPagePdfJs) {
        resolve(window.modFlipPagePdfJs);
        return;
    }

    const onLoaded = () => {
        window.removeEventListener('mod_flippage_pdfjs_loaded', onLoaded);
        resolve(window.modFlipPagePdfJs);
    };

    window.addEventListener('mod_flippage_pdfjs_loaded', onLoaded);

    const script = document.createElement('script');
    script.type = 'module';
    script.src = url;
    script.onerror = () => {
        window.removeEventListener('mod_flippage_pdfjs_loaded', onLoaded);
        reject(new Error('PDF renderer could not be loaded.'));
    };
    document.head.appendChild(script);
});

/**
 * Initialise the Flip Page activity viewer.
 *
 * @param {Object} config Viewer configuration from view.php.
 */
export const init = async config => {
    const book = document.getElementById('flippage-book');
    const status = document.querySelector('[data-flippage-status]');
    const counter = document.querySelector('[data-flippage-counter]');
    const prev = document.querySelector('[data-flippage-prev]');
    const next = document.querySelector('[data-flippage-next]');
    const exit = document.querySelector('[data-flippage-exit]');
    const viewport = document.querySelector('[data-flippage-viewport]');
    const zoomIn = document.querySelector('[data-flippage-zoom-in]');
    const zoomOut = document.querySelector('[data-flippage-zoom-out]');
    const zoomReset = document.querySelector('[data-flippage-zoom-reset]');
    const zoomValue = document.querySelector('[data-flippage-zoom-value]');

    let pageFlip = null;
    let totalPages = 0;
    let lastReportedPage = 0;
    let zoom = 1;

    const setStatus = message => {
        if (status) {
            status.textContent = message || '';
            status.hidden = !message;
        }
    };

    const setCounter = (page, total) => {
        if (!counter) {
            return;
        }
        const template = config.strings && config.strings.counter ? config.strings.counter : 'Page __PAGE__ of __TOTAL__';
        counter.textContent = template.replace('__PAGE__', page).replace('__TOTAL__', total);
    };

    const setExitVisibility = visible => {
        if (exit) {
            exit.hidden = !visible;
        }
    };

    const getVisibleEndPage = pageIndex => {
        let page = Math.max(1, pageIndex + 1);
        if (pageFlip && pageFlip.getOrientation && pageFlip.getOrientation() === 'landscape') {
            page = Math.min(totalPages, page + 1);
        }
        return page;
    };

    const postProgress = (page, total) => {
        page = Math.max(1, page);
        total = Math.max(1, total);
        if (page <= lastReportedPage && page < total) {
            return;
        }
        lastReportedPage = Math.max(lastReportedPage, page);

        Ajax.call([{
            methodname: 'mod_flippage_update_progress',
            args: {
                cmid: config.cmid,
                page,
                total
            }
        }])[0].catch(() => {
            // Progress is retried when the next page event is fired.
        });
    };

    const applyZoom = nextZoom => {
        zoom = Math.max(0.6, Math.min(2.5, nextZoom));
        book.style.setProperty('--flippage-zoom', zoom.toString());
        book.classList.toggle('is-zoomed', zoom > 1.01);
        if (zoomValue) {
            zoomValue.textContent = `${Math.round(zoom * 100)}%`;
        }
        if (pageFlip && pageFlip.update) {
            window.setTimeout(() => {
                pageFlip.update();
            }, 60);
        }
    };

    const bindZoomControls = () => {
        if (zoomIn) {
            zoomIn.addEventListener('click', () => applyZoom(zoom + 0.1));
        }
        if (zoomOut) {
            zoomOut.addEventListener('click', () => applyZoom(zoom - 0.1));
        }
        if (zoomReset) {
            zoomReset.addEventListener('click', () => {
                applyZoom(1);
                if (viewport) {
                    viewport.scrollLeft = 0;
                    viewport.scrollTop = 0;
                }
            });
        }
    };

    const initialiseFlip = elements => {
        totalPages = elements.length;
        book.innerHTML = '';
        elements.forEach(element => book.appendChild(element));

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
        pageFlip.on('flip', event => {
            const pageIndex = event.data || 0;
            const page = pageIndex + 1;
            const visibleEndPage = getVisibleEndPage(pageIndex);
            setCounter(page, totalPages);
            setExitVisibility(visibleEndPage >= totalPages);
            postProgress(visibleEndPage, totalPages);
        });
        pageFlip.on('changeOrientation', () => {
            const pageIndex = pageFlip.getCurrentPageIndex ? pageFlip.getCurrentPageIndex() : 0;
            const visibleEndPage = getVisibleEndPage(pageIndex);
            setExitVisibility(visibleEndPage >= totalPages);
            postProgress(visibleEndPage, totalPages);
        });

        if (prev) {
            prev.addEventListener('click', () => pageFlip.flipPrev());
        }
        if (next) {
            next.addEventListener('click', () => pageFlip.flipNext());
        }
        bindZoomControls();
        applyZoom(1);

        const startPage = Math.max(0, (config.progress && config.progress.currentpage ? config.progress.currentpage : 1) - 1);
        if (startPage > 0 && startPage < totalPages) {
            pageFlip.turnToPage(startPage);
        }
        setCounter(startPage + 1, totalPages);
        setExitVisibility(getVisibleEndPage(startPage) >= totalPages);
        postProgress(getVisibleEndPage(startPage), totalPages);
        setStatus('');
    };

    const pageElement = child => {
        const page = document.createElement('div');
        page.className = 'flippage-page';
        page.appendChild(child);
        return page;
    };

    const pdfPages = async file => {
        const pdfjs = await loadPdfJs(config.pdfjsloaderurl);
        pdfjs.GlobalWorkerOptions.workerSrc = config.pdfworkerurl;
        const pdf = await pdfjs.getDocument(file.url).promise;
        const pages = [];

        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
            const pdfPage = await pdf.getPage(pageNumber);
            const pdfViewport = pdfPage.getViewport({scale: 1.5});
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.width = pdfViewport.width;
            canvas.height = pdfViewport.height;
            await pdfPage.render({canvasContext: context, viewport: pdfViewport}).promise;
            pages.push(pageElement(canvas));
        }

        return pages;
    };

    if (!book) {
        return;
    }

    try {
        await loadBrowserScript(config.pageflipurl);
    } catch (error) {
        setStatus(error.message);
        return;
    }

    if (!window.St || !window.St.PageFlip) {
        setStatus('Flip page library could not be loaded.');
        return;
    }

    const files = config.files || [];
    if (!files.length) {
        setStatus('');
        return;
    }

    setStatus(config.strings && config.strings.loading ? config.strings.loading : 'Loading document...');
    const pdfs = files.filter(file => file.ispdf);

    try {
        const pages = pdfs.length ? await pdfPages(pdfs[0]) : [];
        if (!pages.length) {
            setStatus('No supported pages were found.');
            return;
        }
        initialiseFlip(pages);
    } catch (error) {
        setStatus(error && error.message ? error.message : 'The document could not be loaded.');
    }
};
