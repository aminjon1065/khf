/**
 * Matomo analytics bootstrap. Configuration is passed via <meta name="matomo-url"> and
 * <meta name="matomo-site-id"> so this stays a static 'self' file and the CSP needs no
 * 'unsafe-inline' script-src (ТЗ §12.2, §15.2). No-op if the meta tags are absent.
 */
(function () {
    var urlMeta = document.querySelector('meta[name="matomo-url"]');
    var siteMeta = document.querySelector('meta[name="matomo-site-id"]');

    if (!urlMeta || !siteMeta || !urlMeta.content || !siteMeta.content) {
        return;
    }

    var u = urlMeta.content;
    if (u.charAt(u.length - 1) !== '/') {
        u += '/';
    }

    var _paq = (window._paq = window._paq || []);
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    _paq.push(['setTrackerUrl', u + 'matomo.php']);
    _paq.push(['setSiteId', siteMeta.content]);

    var d = document,
        g = d.createElement('script'),
        s = d.getElementsByTagName('script')[0];
    g.async = true;
    g.src = u + 'matomo.js';
    s.parentNode.insertBefore(g, s);
})();
