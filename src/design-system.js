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

    global.promptDialog = promptDialog;
    global.openModal = openModal;
    global.showInfoDialog = showInfoDialog;
    global.closeModal = closeModal;
    global.showToast = showToast;
    global.confirmDestructive = confirmDestructive;
})(window);
