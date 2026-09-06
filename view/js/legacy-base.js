/**
 * This file is part of FSFramework
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 *
 * legacy_support - legacy base.js helpers.
 *
 * Moved from core view/js/base.js — legacy API for old plugins; loaded via
 * head_extra_js before core base.js. Reproduces the parts of the old base.js
 * ready block that core no longer ships (modal-iframe wiring, Bootstrap 3
 * tooltip/popover auto-init, [data-confirm] click guard) plus the
 * fs_confirm / fs_alert / fs_prompt / ajax_form helpers.
 *
 * Requires jQuery; the dialog helpers use bootbox (or the core fs-dialogs.js
 * shim) at call time, so load order does not matter for them.
 */
/*global jQuery, $, bootbox */

/**
 * Show confirmation dialog
 * @param {string} message - Confirmation message
 * @param {function} callback - Callback on confirm
 */
function fs_confirm(message, callback) {
    if (typeof bootbox !== 'undefined') {
        bootbox.confirm({
            message: message,
            buttons: {
                cancel: {
                    label: 'Cancelar',
                    className: 'btn-default'
                },
                confirm: {
                    label: 'Aceptar',
                    className: 'btn-primary'
                }
            },
            callback: function(result) {
                if (result && typeof callback === 'function') {
                    callback();
                }
            }
        });
    } else if (confirm(message)) {
        if (typeof callback === 'function') {
            callback();
        }
    }
}

/**
 * Show alert dialog
 * @param {string} message - Alert message
 * @param {function} callback - Optional callback on close
 */
function fs_alert(message, callback) {
    if (typeof bootbox !== 'undefined') {
        bootbox.alert({
            message: message,
            callback: callback
        });
    } else {
        alert(message);
        if (typeof callback === 'function') {
            callback();
        }
    }
}

/**
 * Show prompt dialog
 * @param {string} message - Prompt message
 * @param {function} callback - Callback with value
 * @param {string} defaultValue - Default value
 */
function fs_prompt(message, callback, defaultValue) {
    if (typeof bootbox !== 'undefined') {
        bootbox.prompt({
            title: message,
            value: defaultValue || '',
            callback: function(result) {
                if (result !== null && typeof callback === 'function') {
                    callback(result);
                }
            }
        });
    } else {
        var result = prompt(message, defaultValue);
        if (result !== null && typeof callback === 'function') {
            callback(result);
        }
    }
}

/**
 * Initialize modal iframe
 */
function init_modal_iframe() {
    $('#modal_iframe').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var url = button.data('url');
        var title = button.data('title') || 'Modal';

        var modal = $(this);
        modal.find('.modal-title').text(title);
        modal.find('iframe').attr('src', url);
    });

    $('#modal_iframe').on('hidden.bs.modal', function() {
        $(this).find('iframe').attr('src', '');
    });
}

/**
 * AJAX form submission helper
 * @param {string} formSelector - Form selector
 * @param {object} options - Options (success, error callbacks)
 */
function ajax_form(formSelector, options) {
    options = options || {};

    $(formSelector).on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('[type="submit"]');

        $btn.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (typeof options.success === 'function') {
                    options.success(response);
                }
            },
            error: function(xhr, status, error) {
                if (typeof options.error === 'function') {
                    options.error(xhr, status, error);
                } else {
                    fs_alert('Error: ' + error);
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
}

/**
 * Document ready initialization (moved ready-block parts of old base.js)
 */
$(document).ready(function() {
    // Initialize modal iframe
    init_modal_iframe();

    // Initialize tooltips
    if (typeof $.fn.tooltip !== 'undefined') {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize popovers
    if (typeof $.fn.popover !== 'undefined') {
        $('[data-toggle="popover"]').popover();
    }

    // Confirm delete actions
    $('[data-confirm]').on('click', function(e) {
        var message = $(this).data('confirm') || '¿Está seguro?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
});
