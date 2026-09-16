{{-- Un-clips the row-action "Change status" dropdown in the bookings DataTable
     WITHOUT touching any container's overflow (so horizontal scrolling is never affected).
     The table cell / .dataTables_scrollBody clip the menu (they have overflow, and the
     table is only one row tall on a searched list). No ancestor uses a CSS transform, so
     switching the menu to position:fixed lets it escape the clip cleanly.
     Listeners are attached on `document` because #crudTable_wrapper is created later by
     DataTables — attaching at parse time would find nothing. --}}
<script>
    (function () {
        var floating = null;
        var IMPORTANT = 'important';
        var TOGGLE_SEL = '.dropdown-toggle, [data-toggle="dropdown"], [data-bs-toggle="dropdown"]';

        function closeFloating() {
            if (!floating) { return; }
            ['position', 'transform', 'margin', 'z-index', 'display',
             'top', 'left', 'right', 'bottom', 'inset', 'will-change'].forEach(function (p) {
                floating.style.removeProperty(p);
            });
            floating = null;
        }

        function floatMenu(toggle) {
            var group = toggle.closest('.btn-group, .dropdown');
            var menu = group ? group.querySelector('.dropdown-menu') : null;
            if (!menu) { return; }

            // Let Bootstrap/Popper open it first, then take over positioning.
            requestAnimationFrame(function () {
                if (!menu.classList.contains('show')) { return; }

                menu.style.setProperty('position', 'fixed', IMPORTANT);
                menu.style.setProperty('transform', 'none', IMPORTANT);   // neutralise Popper
                menu.style.setProperty('will-change', 'auto', IMPORTANT);
                menu.style.setProperty('margin', '0', IMPORTANT);
                menu.style.setProperty('z-index', '2000', IMPORTANT);
                menu.style.setProperty('display', 'block', IMPORTANT);

                var r = toggle.getBoundingClientRect();
                var mw = menu.offsetWidth;
                var mh = menu.offsetHeight;

                var left = r.right - mw;                 // RTL: align menu's right edge to the button
                if (left < 4) { left = 4; }
                var top = r.bottom;                      // default: drop down
                if (top + mh > window.innerHeight - 4 && r.top - mh > 4) {
                    top = r.top - mh;                    // not enough room below → flip up
                }

                // NOTE: do not set the `inset` shorthand here — it would reset top/left/right/bottom.
                menu.style.setProperty('right', 'auto', IMPORTANT);
                menu.style.setProperty('bottom', 'auto', IMPORTANT);
                menu.style.setProperty('top', top + 'px', IMPORTANT);
                menu.style.setProperty('left', left + 'px', IMPORTANT);

                floating = menu;
            });
        }

        // Delegate on document (capture) so it works no matter when DataTables builds the table.
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest(TOGGLE_SEL);
            if (toggle && toggle.closest('#crudTable_wrapper')) {
                closeFloating();
                floatMenu(toggle);
                return;
            }
            // click elsewhere → let the floating menu revert (Bootstrap also closes it)
            if (floating && !floating.contains(e.target)) {
                closeFloating();
            }
        }, true);

        window.addEventListener('scroll', closeFloating, true);
        window.addEventListener('resize', closeFloating, true);
    })();
</script>
