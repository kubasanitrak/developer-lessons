/**
 * Vimeo play tracking for lesson pages.
 */
(function ($) {
    'use strict';

    var config = window.dl_video_analytics || {};
    if (!config.lesson_id) {
        return;
    }

    var trackedIframes = new WeakSet();
    var milestoneState = {};

    function loadVimeoApi(callback) {
        if (window.Vimeo && window.Vimeo.Player) {
            callback();
            return;
        }

        var script = document.createElement('script');
        script.src = 'https://player.vimeo.com/api/player.js';
        script.onload = callback;
        document.head.appendChild(script);
    }

    function getVimeoId(iframe) {
        var match = (iframe.src || '').match(/vimeo\.com\/video\/(\d+)/);
        if (match) {
            return match[1];
        }

        var host = iframe.closest('.vi-lazyload');
        if (host && host.dataset.id) {
            return String(host.dataset.id);
        }

        return '';
    }

    function sendEvent(eventType, vimeoId, percent) {
        $.post(config.ajax_url, {
            action: 'dl_track_video_event',
            nonce: config.nonce,
            lesson_id: config.lesson_id,
            vimeo_id: vimeoId,
            event_type: eventType,
            percent: percent || ''
        });
    }

    function bindPlayer(iframe) {
        if (trackedIframes.has(iframe)) {
            return;
        }

        trackedIframes.add(iframe);

        var vimeoId = getVimeoId(iframe);
        if (!vimeoId || !iframe.src) {
            return;
        }

        loadVimeoApi(function () {
            try {
                var player = new Vimeo.Player(iframe);
                var key = vimeoId + ':' + config.lesson_id;

                milestoneState[key] = {
                    25: false,
                    50: false,
                    75: false
                };

                player.on('play', function () {
                    sendEvent('video_play_start', vimeoId);
                });

                player.on('timeupdate', function (data) {
                    var percent = (data.percent || 0) * 100;
                    [25, 50, 75].forEach(function (mark) {
                        if (!milestoneState[key][mark] && percent >= mark) {
                            milestoneState[key][mark] = true;
                            sendEvent('video_progress_' + mark, vimeoId, percent);
                        }
                    });
                });
            } catch (error) {
                return;
            }
        });
    }

    function scanForIframes(root) {
        var scope = root || document;
        scope.querySelectorAll('iframe[src*="player.vimeo.com"]').forEach(bindPlayer);
    }

    $(function () {
        scanForIframes(document);

        var container = document.querySelector('.video-container') || document.body;
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    if (node.tagName === 'IFRAME' && (node.src || '').indexOf('player.vimeo.com') !== -1) {
                        bindPlayer(node);
                        return;
                    }

                    scanForIframes(node);
                });
            });
        });

        observer.observe(container, {
            childList: true,
            subtree: true
        });
    });
})(jQuery);
