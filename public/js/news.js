/* Homepage "News & Events": loads published announcements from news_api.php.
 * All text is inserted with textContent, never as HTML. */
(function () {
    'use strict';

    var newsList = document.getElementById('newsList');
    var eventList = document.getElementById('eventList');
    if (!newsList || !eventList) return;

    var CATEGORY = { news: 'News', event: 'Event', advisory: 'Advisory' };

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function formatDate(iso, options) {
        var d = new Date(iso + 'T00:00:00');
        return isNaN(d.getTime()) ? '' : d.toLocaleDateString('en-PH', options);
    }

    function empty(list, text) {
        list.replaceChildren(el(list.tagName === 'UL' ? 'li' : 'p', 'news-empty', text));
    }

    function renderNews(items) {
        if (!items.length) return empty(newsList, 'No announcements right now. Please check back soon.');
        newsList.replaceChildren.apply(newsList, items.map(function (item) {
            var article = el('article', 'news-item' + (item.category === 'advisory' ? ' news-item--advisory' : ''));
            var when = item.category === 'event' && item.event_date ? item.event_date : item.posted;
            article.appendChild(el('div', 'news-item__meta', (CATEGORY[item.category] || 'News') + ' · ' + formatDate(when, { year: 'numeric', month: 'long', day: 'numeric' })));
            article.appendChild(el('h4', 'news-item__title', item.title));
            article.appendChild(el('p', 'news-item__body', item.body));
            return article;
        }));
    }

    function renderEvents(items) {
        if (!items.length) return empty(eventList, 'No upcoming events posted yet.');
        eventList.replaceChildren.apply(eventList, items.map(function (item) {
            var li = el('li', 'event-item');
            var date = el('div', 'event-item__date');
            date.setAttribute('aria-hidden', 'true');
            date.appendChild(el('span', 'event-item__day', formatDate(item.event_date, { day: 'numeric' })));
            date.appendChild(el('span', 'event-item__month', formatDate(item.event_date, { month: 'short' })));
            var text = el('div');
            text.appendChild(el('p', 'event-item__title', item.title));
            text.appendChild(el('p', 'event-item__body', formatDate(item.event_date, { weekday: 'long', month: 'long', day: 'numeric' }) + ' — ' + item.body));
            li.appendChild(date);
            li.appendChild(text);
            return li;
        }));
    }

    fetch('/news_api.php', { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function (data) {
            renderNews(data.news || []);
            renderEvents(data.events || []);
        })
        .catch(function () {
            empty(newsList, 'Announcements are unavailable right now. Please try again later.');
            empty(eventList, 'Events are unavailable right now.');
        });
})();
