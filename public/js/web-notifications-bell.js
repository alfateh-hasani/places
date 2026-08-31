/**
 * Staff notification bell for the dashboard topbar. Polls the in-app
 * web_notifications feed, renders the badge + dropdown list, and marks items read.
 * Config comes from window.WebNotifConfig (injected by topbar_right_content.blade.php).
 */
(function () {
    'use strict';

    var config = window.WebNotifConfig || {};
    if (!config.indexUrl) {
        return;
    }

    var badge = document.getElementById('web-notif-badge');
    var list = document.getElementById('web-notif-list');
    var markAll = document.getElementById('web-notif-mark-all');
    var POLL_MS = 30000;

    function postJson(url) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': config.csrf, 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
    }

    function renderBadge(count) {
        if (!badge) {
            return;
        }
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = '';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderList(items) {
        if (!list) {
            return;
        }
        list.innerHTML = '';

        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'dropdown-item text-muted small';
            empty.textContent = 'لا توجد إشعارات';
            list.appendChild(empty);
            return;
        }

        items.forEach(function (item) {
            var row = document.createElement('a');
            row.className = 'dropdown-item' + (item.read ? '' : ' font-weight-bold');
            row.href = item.url || '#';
            row.style.whiteSpace = 'normal';
            row.style.borderBottom = '1px solid rgba(0,0,0,.05)';

            var title = document.createElement('div');
            title.textContent = item.title || '';
            row.appendChild(title);

            if (item.body) {
                var body = document.createElement('div');
                body.className = 'small text-muted';
                body.textContent = item.body;
                row.appendChild(body);
            }

            if (item.created_at) {
                var time = document.createElement('div');
                time.className = 'small text-muted';
                time.style.opacity = '0.7';
                time.textContent = item.created_at;
                row.appendChild(time);
            }

            row.addEventListener('click', function () {
                if (!item.read) {
                    postJson(config.readUrlBase + '/' + item.id + '/read');
                }
                // Let the browser follow item.url normally; if none, just refresh.
                if (!item.url) {
                    setTimeout(load, 150);
                }
            });

            list.appendChild(row);
        });
    }

    function load() {
        fetch(config.indexUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) {
                    return;
                }
                renderBadge(data.unread_count || 0);
                renderList(data.notifications || []);
            })
            .catch(function () { /* ignore transient errors */ });
    }

    if (markAll) {
        markAll.addEventListener('click', function (e) {
            e.preventDefault();
            postJson(config.readAllUrl).then(load);
        });
    }

    load();
    setInterval(load, POLL_MS);
})();
