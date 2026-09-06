/**
 * This file is part of FSFramework
 * Copyright (C) 2025 Javier Trujillo <mistertekcom@gmail.com>
 *
 * legacy_support - legacy datepicker auto-initialization.
 *
 * Reproduces the datepicker init block that used to live in core
 * view/js/base.js, for old plugin views that still emit class="datepicker"
 * text inputs. Requires jQuery, bootstrap-datepicker and jQuery UI, loaded
 * earlier via the head_extra_js globals of header.html.twig.
 *
 * Native <input type="date"> fields are intentionally NOT downgraded here:
 * core migrated them to native inputs; only legacy .datepicker class fields
 * are initialized.
 */
/*global jQuery */
(function ($) {
    'use strict';

    $(document).ready(function () {
        if (typeof $.fn.datepicker === 'undefined') {
            // Should not happen with the plugin assets loaded; fail silently.
            return;
        }

        $('.datepicker').each(function () {
            $(this).datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true,
                language: 'es'
            });
        });
    });
})(jQuery);
