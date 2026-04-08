// Gallery Modal - Dynamic Photos
(function () {
    'use strict';

    // Elements
    var modal = document.getElementById('Gallery-Modal');
    var backdrop = document.getElementById('modal-backdrop');
    var mainImg = document.getElementById('modal-main-image');
    var counterEl = document.getElementById('modal-counter');
    var totalEl = document.getElementById('modal-total');
    var closeBtn = document.getElementById('closeModal');
    var prevBtn = document.getElementById('prev-photo');
    var nextBtn = document.getElementById('next-photo');
    var thumbs = document.querySelectorAll('.modal-thumb');
    var mainThumb = document.querySelector('#Gallery > button');
    var seeAllBtn = document.getElementById('see-all-photos-btn');
    var photosData = document.getElementById('photos-data');

    // Get all photos from hidden data
    var images = [];
    var total = 0;

    if (photosData) {
        var thumbSrc = photosData.getAttribute('data-thumb');
        var photosJson = photosData.getAttribute('data-photos');
        var photos = [];

        try {
            photos = JSON.parse(photosJson);
        } catch (e) {
            photos = [];
        }

        // Build images array: thumbnail + all photos
        if (thumbSrc) images.push(thumbSrc);
        photos.forEach(function (p) {
            if (p) images.push(p);
        });

        total = images.length;
    }

    var currentIdx = 0;

    // Update total display
    if (totalEl) {
        totalEl.textContent = total;
    }

    // Validate index
    function isValid(idx) {
        return typeof idx === 'number' && !isNaN(idx) && idx >= 0 && idx < total;
    }

    // Update UI
    function show(idx) {
        if (!isValid(idx)) idx = 0;
        currentIdx = idx;

        if (mainImg && images[idx]) mainImg.src = images[idx];
        if (counterEl) counterEl.textContent = idx + 1;

        thumbs.forEach(function (t, i) {
            if (i === idx) {
                t.style.border = '2px solid #CEF27F';
                t.style.opacity = '1';
            } else {
                t.style.border = '2px solid #e0e0e0';
                t.style.opacity = '0.5';
            }
        });
    }

    // Open modal
    function open(idx) {
        show(idx);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    // Close modal
    function close() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Navigate
    function prev() {
        var i = currentIdx - 1;
        if (i < 0) i = total - 1;
        show(i);
    }

    function next() {
        var i = currentIdx + 1;
        if (i >= total) i = 0;
        show(i);
    }

    // Events
    if (mainThumb) {
        mainThumb.onclick = function (e) {
            e.preventDefault();
            open(0);
        };
    }

    // Grid buttons (first 4 photos)
    document.querySelectorAll('.gallery-btn').forEach(function (btn) {
        btn.onclick = function (e) {
            e.preventDefault();
            var idx = parseInt(this.getAttribute('data-photo-index'));
            open(idx + 1);
        };
    });

    // See All button
    if (seeAllBtn) {
        seeAllBtn.onclick = function (e) {
            e.preventDefault();
            var idx = parseInt(this.getAttribute('data-photo-index'));
            open(idx + 1);
        };
    }

    // Thumbnails
    thumbs.forEach(function (t) {
        t.onclick = function () {
            var idx = parseInt(this.getAttribute('data-index'));
            show(idx);
        };
    });

    // Navigation
    if (prevBtn)
        prevBtn.onclick = function (e) {
            e.stopPropagation();
            prev();
        };
    if (nextBtn)
        nextBtn.onclick = function (e) {
            e.stopPropagation();
            next();
        };
    if (closeBtn) closeBtn.onclick = close;
    if (backdrop) backdrop.onclick = close;

    // Keyboard
    document.onkeydown = function (e) {
        if (modal.style.display === 'flex') {
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        }
    };
})();
