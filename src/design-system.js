/*
 * Gemeinsames Design-System (JS-Teil) aller DAS-DA-Web-Apps: Modal-Handling,
 * Toasts, Bestätigungs-, Hinweis- und Eingabedialog (Ersatz für alert,
 * confirm und prompt). Siehe design-system.css für die Klassen.
 */
(function (global) {
    'use strict';

    function openModal(id, options) {
        options = options || {};
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('active');

        var defaultBtn = options.defaultButtonId
            ? document.getElementById(options.defaultButtonId)
            : modal.querySelector('.btn-default') || modal.querySelector('.modal-footer .btn');
        if (defaultBtn) {
            defaultBtn.classList.add('btn-default');
            window.setTimeout(function () { defaultBtn.focus(); }, 0);
        }

        function onKeydown(e) {
            if (e.key === 'Escape' && options.dismissible !== false) {
                closeModal(id);
                modal.dispatchEvent(new CustomEvent('ds-modal-dismiss'));
            }
        }
        modal.__dsKeydownHandler = onKeydown;
        document.addEventListener('keydown', onKeydown);

        if (options.dismissible !== false) {
            modal.onclick = function (e) {
                if (e.target === modal) {
                    closeModal(id);
                    modal.dispatchEvent(new CustomEvent('ds-modal-dismiss'));
                }
            };
        }
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('active');
        if (modal.__dsKeydownHandler) {
            document.removeEventListener('keydown', modal.__dsKeydownHandler);
            modal.__dsKeydownHandler = null;
        }
    }

    function ensureToastContainer() {
        var container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            container.setAttribute('role', 'status');
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }
        return container;
    }

    function showToast(message, options) {
        options = options || {};
        var type = options.type || 'info';
        var duration = options.duration || (options.action ? 8000 : 4000);

        var container = ensureToastContainer();
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        var msgEl = document.createElement('span');
        msgEl.className = 'toast-message';
        msgEl.textContent = message;
        toast.appendChild(msgEl);

        var timeoutId;
        function remove() {
            window.clearTimeout(timeoutId);
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }

        if (options.action && typeof options.onAction === 'function') {
            var actionBtn = document.createElement('button');
            actionBtn.type = 'button';
            actionBtn.className = 'toast-action';
            actionBtn.textContent = options.action;
            actionBtn.onclick = function () {
                options.onAction();
                remove();
            };
            toast.appendChild(actionBtn);
        }

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'toast-close';
        closeBtn.setAttribute('aria-label', 'Schließen');
        closeBtn.textContent = '×';
        closeBtn.onclick = remove;
        toast.appendChild(closeBtn);

        container.appendChild(toast);
        timeoutId = window.setTimeout(remove, duration);
        return remove;
    }

    /*
     * Generischer Bestätigungsdialog für destruktive Aktionen (Regel 3).
     * Erwartet im DOM ein Modal mit id="dsConfirmModal" (wird bei Bedarf
     * automatisch erzeugt) - primäre/destruktive Aktion links, Abbrechen
     * rechts außen (Regel 1).
     */
    function ensureConfirmModal() {
        var modal = document.getElementById('dsConfirmModal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'dsConfirmModal';
        modal.className = 'modal';
        modal.innerHTML =
            '<div class="modal-content">' +
                '<div class="modal-header" id="dsConfirmTitle"></div>' +
                '<div class="modal-body"><p id="dsConfirmMessage"></p></div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-danger" id="dsConfirmOkBtn"></button>' +
                    '<button type="button" class="btn btn-secondary" id="dsConfirmCancelBtn">Abbrechen</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function confirmDestructive(options) {
        options = options || {};
        var modal = ensureConfirmModal();
        document.getElementById('dsConfirmTitle').textContent = options.title || 'Wirklich fortfahren?';
        document.getElementById('dsConfirmMessage').textContent = options.message || '';

        var okBtn = document.getElementById('dsConfirmOkBtn');
        okBtn.textContent = options.confirmLabel || 'Löschen';
        var cancelBtn = document.getElementById('dsConfirmCancelBtn');
        cancelBtn.textContent = options.cancelLabel || 'Abbrechen';

        var handleOk = function () {
            closeModal('dsConfirmModal');
            cleanup();
            if (typeof options.onConfirm === 'function') options.onConfirm();
        };
        var handleCancel = function () {
            closeModal('dsConfirmModal');
            cleanup();
            if (typeof options.onCancel === 'function') options.onCancel();
        };
        function cleanup() {
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
        }

        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);

        /*
         * Sicherheitsnetz bei destruktiven/irreversiblen Aktionen: Abbrechen
         * bekommt den Tastatur-Fokus (Enter löst NICHT die Löschung aus),
         * die destruktive Aktion bleibt aber links positioniert (Regel 1).
         * Ist ein onCancel-Callback gesetzt (z.B. "Entwurf laden" statt
         * reinem Abbrechen), ist Escape/Backdrop-Klick deaktiviert, damit
         * keine der beiden Optionen unbeabsichtigt übersprungen wird.
         */
        openModal('dsConfirmModal', {
            defaultButtonId: 'dsConfirmCancelBtn',
            dismissible: typeof options.onCancel !== 'function'
        });
    }

    /*
     * Hinweis-Dialog mit einem OK-Button (gleiche Optik wie die Rueckfragen).
     * options: { title, message, okLabel, onClose }
     */
    function ensureInfoModal() {
        var modal = document.getElementById('dsInfoModal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'dsInfoModal';
        modal.className = 'modal';
        modal.innerHTML =
            '<div class="modal-content">' +
                '<div class="modal-header" id="dsInfoTitle"></div>' +
                '<div class="modal-body"><p id="dsInfoMessage"></p></div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-primary" id="dsInfoOkBtn">OK</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function showInfoDialog(options) {
        options = options || {};
        ensureInfoModal();
        document.getElementById('dsInfoTitle').textContent = options.title || 'Hinweis';
        document.getElementById('dsInfoMessage').textContent = options.message || '';
        var okBtn = document.getElementById('dsInfoOkBtn');
        okBtn.textContent = options.okLabel || 'OK';
        var handleOk = function () {
            okBtn.removeEventListener('click', handleOk);
            closeModal('dsInfoModal');
            if (typeof options.onClose === 'function') options.onClose();
        };
        okBtn.addEventListener('click', handleOk);
        openModal('dsInfoModal', { defaultButtonId: 'dsInfoOkBtn', dismissible: true });
    }

    /*
     * Eingabedialog als Ersatz für prompt().
     * options: { title, message, label, value, placeholder, okLabel,
     *            cancelLabel, required, onConfirm(value), onCancel }
     * Enter bestätigt, Esc/Abbrechen bricht ab.
     */
    function ensurePromptModal() {
        var modal = document.getElementById('dsPromptModal');
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'dsPromptModal';
        modal.className = 'modal';
        modal.innerHTML =
            '<div class="modal-content">' +
                '<div class="modal-header" id="dsPromptTitle"></div>' +
                '<form class="modal-body" id="dsPromptForm" novalidate>' +
                    '<p id="dsPromptMessage"></p>' +
                    '<label><span id="dsPromptLabel"></span>' +
                        '<input type="text" id="dsPromptInput" autocomplete="off">' +
                    '</label>' +
                '</form>' +
                '<div class="modal-footer">' +
                    '<button type="submit" form="dsPromptForm" class="btn btn-primary" id="dsPromptOkBtn">OK</button>' +
                    '<button type="button" class="btn btn-secondary" id="dsPromptCancelBtn">Abbrechen</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);
        return modal;
    }

    function promptDialog(options) {
        options = options || {};
        var modal = ensurePromptModal();
        var form = document.getElementById('dsPromptForm');
        var input = document.getElementById('dsPromptInput');
        var okBtn = document.getElementById('dsPromptOkBtn');
        var cancelBtn = document.getElementById('dsPromptCancelBtn');
        var msg = document.getElementById('dsPromptMessage');

        document.getElementById('dsPromptTitle').textContent = options.title || 'Eingabe';
        msg.textContent = options.message || '';
        msg.style.display = options.message ? '' : 'none';
        document.getElementById('dsPromptLabel').textContent = options.label || '';
        input.value = options.value != null ? String(options.value) : '';
        input.placeholder = options.placeholder || '';
        input.classList.remove('field-invalid');
        okBtn.textContent = options.okLabel || 'OK';
        cancelBtn.textContent = options.cancelLabel || 'Abbrechen';

        function cleanup() {
            form.removeEventListener('submit', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
            modal.removeEventListener('ds-modal-dismiss', handleCancel);
        }
        function handleOk(e) {
            e.preventDefault();
            var value = input.value.trim();
            if (options.required !== false && value === '') {
                input.classList.add('field-invalid');
                input.focus();
                return;
            }
            cleanup();
            closeModal('dsPromptModal');
            if (typeof options.onConfirm === 'function') options.onConfirm(value);
        }
        function handleCancel() {
            cleanup();
            closeModal('dsPromptModal');
            if (typeof options.onCancel === 'function') options.onCancel();
        }

        form.addEventListener('submit', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
        modal.addEventListener('ds-modal-dismiss', handleCancel);

        openModal('dsPromptModal', { defaultButtonId: 'dsPromptOkBtn', dismissible: true });
        window.setTimeout(function () { input.focus(); input.select(); }, 0);
    }

    /*
     * Sortieren per Ziehen (Maus, Finger, Stift) - Ersatz fuer Hoch/Runter-Pfeile.
     * Die Komponente verschiebt keine Daten: sie meldet nur onReorder(from, to),
     * die App aendert ihr Datenmodell (z.B. mit moveArrayItem) und rendert neu.
     * Nach jedem Neu-Rendern makeSortable() erneut aufrufen (idempotent).
     *
     * Optionen: items (Selektor der direkten Kind-Elemente), handle (Selektor
     * des Griffs, Standard .ds-sort-handle), canMove(from), canDrop(from, to),
     * onReorder(from, to), label(index) fuer die Screenreader-Ansage,
     * key (stellt den Fokus nach Tastatur-Verschieben wieder her, Standard: id).
     */
    var SORT_GRIP_SVG = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>';
    var SORT_START_DISTANCE = 4;
    var SORT_SCROLL_EDGE = 48;
    var SORT_SCROLL_MAX_SPEED = 16;
    var sortLiveRegion = null;

    function moveArrayItem(arr, from, to) {
        if (from === to || from < 0 || from >= arr.length) return arr;
        var item = arr.splice(from, 1)[0];
        arr.splice(Math.max(0, Math.min(to, arr.length)), 0, item);
        return arr;
    }

    function createSortHandle(title) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ds-sort-handle';
        btn.title = title || 'Ziehen zum Verschieben (oder Pfeiltasten)';
        btn.setAttribute('aria-label', 'Verschieben');
        btn.innerHTML = SORT_GRIP_SVG;
        return btn;
    }

    function announceSort(text) {
        if (!sortLiveRegion) {
            sortLiveRegion = document.createElement('div');
            sortLiveRegion.className = 'ds-sr-only';
            sortLiveRegion.setAttribute('aria-live', 'assertive');
            document.body.appendChild(sortLiveRegion);
        }
        sortLiveRegion.textContent = '';
        window.setTimeout(function () { sortLiveRegion.textContent = text; }, 30);
    }

    function findScrollParent(el) {
        var node = el.parentElement;
        while (node && node !== document.body && node !== document.documentElement) {
            var style = window.getComputedStyle(node);
            if (/(auto|scroll)/.test(style.overflowY) && node.scrollHeight > node.clientHeight) return node;
            node = node.parentElement;
        }
        return null;
    }

    function makeSortable(container, options) {
        if (!container) return null;
        if (container.__dsSortable) container.__dsSortable.destroy();
        options = options || {};
        var itemSel = options.items || ':scope > *';
        var handleSel = options.handle || '.ds-sort-handle';
        var key = options.key || container.id || '';
        if (key) container.setAttribute('data-ds-sortable', key);

        function getItems(root) {
            return Array.prototype.filter.call((root || container).children, function (el) {
                return el.matches(itemSel);
            });
        }
        function canMove(i) { return typeof options.canMove !== 'function' || options.canMove(i) !== false; }
        function canDrop(from, to) { return typeof options.canDrop !== 'function' || options.canDrop(from, to) !== false; }
        function itemLabel(i) { return typeof options.label === 'function' ? options.label(i) : 'Eintrag ' + (i + 1); }

        // Nur Griffe von direkten Eintraegen dieser Liste; Griffe verschachtelter
        // Listen (z.B. Unterpunkte mit gleicher Klasse) gehoeren deren eigenem Aufruf
        function resolve(target) {
            var handle = target.closest ? target.closest(handleSel) : null;
            if (!handle || !container.contains(handle)) return null;
            for (var node = handle.parentElement; node && node !== container; node = node.parentElement) {
                if (node.__dsSortable) return null;
            }
            var item = handle.closest(itemSel);
            if (!item || item.parentElement !== container) return null;
            return { handle: handle, item: item, index: getItems().indexOf(item) };
        }

        getItems().forEach(function (item, i) {
            Array.prototype.forEach.call(item.querySelectorAll(handleSel), function (h) {
                if (h.closest(itemSel) !== item) return;
                if (canMove(i)) h.removeAttribute('aria-disabled');
                else h.setAttribute('aria-disabled', 'true');
            });
        });

        function finishMove(from, to) {
            if (from === to || !canDrop(from, to)) return false;
            if (typeof options.onReorder === 'function') options.onReorder(from, to);
            announceSort(itemLabel(to) + ' an Position ' + (to + 1));
            return true;
        }

        function refocus(index) {
            window.setTimeout(function () {
                var root = key ? document.querySelector('[data-ds-sortable="' + key + '"]') : container;
                if (!root || !root.isConnected) return;
                var item = getItems(root)[index];
                var handle = item && item.querySelector(handleSel);
                if (handle) handle.focus();
            }, 0);
        }

        var drag = null;

        function onPointerDown(e) {
            if (drag || (e.pointerType === 'mouse' && e.button !== 0)) return;
            var hit = resolve(e.target);
            if (!hit || hit.index < 0 || !canMove(hit.index)) return;
            e.preventDefault();
            e.stopPropagation();
            try { hit.handle.setPointerCapture(e.pointerId); } catch (err) { /* ignorieren */ }
            drag = {
                pointerId: e.pointerId,
                handle: hit.handle,
                item: hit.item,
                from: hit.index,
                to: hit.index,
                startY: e.clientY,
                pointerY: e.clientY,
                started: false,
                origNext: hit.item.nextSibling,
                grab: 0,
                scrollParent: null,
                raf: 0
            };
            // Auf window lauschen: das Umhaengen des Eintrags im DOM hebt das Pointer-Capture auf
            window.addEventListener('pointermove', onPointerMove, true);
            window.addEventListener('pointerup', onPointerUp, true);
            window.addEventListener('pointercancel', onPointerCancel, true);
            document.addEventListener('keydown', onDragKeydown, true);
        }

        function startDrag() {
            drag.started = true;
            drag.grab = drag.startY - drag.item.getBoundingClientRect().top;
            drag.scrollParent = findScrollParent(container);
            drag.item.classList.add('ds-sort-dragging');
            document.body.classList.add('ds-sorting');
            drag.raf = window.requestAnimationFrame(tick);
        }

        function onPointerMove(e) {
            if (!drag || e.pointerId !== drag.pointerId) return;
            e.preventDefault();
            drag.pointerY = e.clientY;
            if (!drag.started) {
                if (Math.abs(e.clientY - drag.startY) < SORT_START_DISTANCE) return;
                startDrag();
            }
            update();
        }

        function autoScroll() {
            var sp = drag.scrollParent;
            var box = sp ? sp.getBoundingClientRect() : { top: 0, bottom: window.innerHeight };
            var y = drag.pointerY;
            var delta = 0;
            if (y < box.top + SORT_SCROLL_EDGE) {
                delta = -Math.ceil(SORT_SCROLL_MAX_SPEED * (1 - Math.max(0, y - box.top) / SORT_SCROLL_EDGE));
            } else if (y > box.bottom - SORT_SCROLL_EDGE) {
                delta = Math.ceil(SORT_SCROLL_MAX_SPEED * (1 - Math.max(0, box.bottom - y) / SORT_SCROLL_EDGE));
            }
            if (!delta) return false;
            if (sp) sp.scrollTop += delta; else window.scrollBy(0, delta);
            return true;
        }

        function tick() {
            if (!drag || !drag.started) return;
            if (autoScroll()) update();
            drag.raf = window.requestAnimationFrame(tick);
        }

        // Eintrag im DOM live an die Zielstelle setzen und per transform unter dem Zeiger halten
        function update() {
            var item = drag.item;
            var others = getItems().filter(function (el) { return el !== item; });
            var target = 0;
            for (var i = 0; i < others.length; i++) {
                var r = others[i].getBoundingClientRect();
                if (drag.pointerY > r.top + r.height / 2) target = i + 1;
            }
            if (target !== drag.to && canDrop(drag.from, target)) {
                var before = others.map(function (el) { return el.getBoundingClientRect().top; });
                if (target < others.length) {
                    container.insertBefore(item, others[target]);
                } else {
                    var last = others[others.length - 1];
                    container.insertBefore(item, last ? last.nextSibling : null);
                }
                drag.to = target;
                others.forEach(function (el, idx) {
                    var dy = before[idx] - el.getBoundingClientRect().top;
                    if (!dy) return;
                    el.style.transition = 'none';
                    el.style.transform = 'translateY(' + dy + 'px)';
                    el.getBoundingClientRect();
                    el.style.transition = 'transform 150ms ease';
                    el.style.transform = '';
                });
            }
            item.style.transform = 'none';
            var natural = item.getBoundingClientRect().top;
            item.style.transform = 'translateY(' + (drag.pointerY - drag.grab - natural) + 'px) scale(1.02)';
        }

        function endDrag(commit) {
            var d = drag;
            drag = null;
            window.cancelAnimationFrame(d.raf);
            window.removeEventListener('pointermove', onPointerMove, true);
            window.removeEventListener('pointerup', onPointerUp, true);
            window.removeEventListener('pointercancel', onPointerCancel, true);
            document.removeEventListener('keydown', onDragKeydown, true);
            try { d.handle.releasePointerCapture(d.pointerId); } catch (err) { /* ignorieren */ }
            if (!d.started) return;
            d.item.classList.remove('ds-sort-dragging');
            document.body.classList.remove('ds-sorting');
            getItems().forEach(function (el) { el.style.transition = ''; el.style.transform = ''; });
            // DOM auf den Ausgangszustand zuruecksetzen; die App rendert nach onReorder selbst neu
            container.insertBefore(d.item, d.origNext && d.origNext.parentNode === container ? d.origNext : null);
            if (commit) finishMove(d.from, d.to);
        }

        function onPointerUp(e) { if (drag && e.pointerId === drag.pointerId) endDrag(true); }
        function onPointerCancel(e) { if (drag && e.pointerId === drag.pointerId) endDrag(false); }
        function onDragKeydown(e) {
            if (drag && e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); endDrag(false); }
        }

        function onKeydown(e) {
            if (drag) return;
            var hit = resolve(e.target);
            if (!hit || hit.handle !== e.target || hit.index < 0) return;
            var count = getItems().length;
            var to;
            if (e.key === 'ArrowUp') to = hit.index - 1;
            else if (e.key === 'ArrowDown') to = hit.index + 1;
            else if (e.key === 'Home') to = 0;
            else if (e.key === 'End') to = count - 1;
            else return;
            e.preventDefault();
            e.stopPropagation();
            if (!canMove(hit.index)) return;
            // Pos1/Ende: bis zur naechsten erlaubten Stelle zuruecktasten
            var step = to < hit.index ? 1 : -1;
            while (to >= 0 && to < count && to !== hit.index && !canDrop(hit.index, to)) to += step;
            if (to < 0 || to >= count) return;
            if (finishMove(hit.index, to)) refocus(to);
        }

        // Klick auf den Griff loest keine Zeilen-Aktion aus (z.B. Frage auswaehlen)
        function onClick(e) {
            if (resolve(e.target)) e.stopPropagation();
        }

        container.addEventListener('pointerdown', onPointerDown);
        container.addEventListener('keydown', onKeydown);
        container.addEventListener('click', onClick, true);

        var api = {
            destroy: function () {
                if (drag) endDrag(false);
                container.removeEventListener('pointerdown', onPointerDown);
                container.removeEventListener('keydown', onKeydown);
                container.removeEventListener('click', onClick, true);
                container.__dsSortable = null;
            }
        };
        container.__dsSortable = api;
        return api;
    }

    global.makeSortable = makeSortable;
    global.moveArrayItem = moveArrayItem;
    global.createSortHandle = createSortHandle;
    global.promptDialog = promptDialog;
    global.openModal = openModal;
    global.showInfoDialog = showInfoDialog;
    global.closeModal = closeModal;
    global.showToast = showToast;
    global.confirmDestructive = confirmDestructive;
})(window);
