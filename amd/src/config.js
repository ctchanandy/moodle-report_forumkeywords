define([], function () {
    window.requirejs.config({
        paths: {
            'd3': M.cfg.wwwroot + '/report/forumkeywords/js/d3.min',
            'cloud': M.cfg.wwwroot + '/report/forumkeywords/js/d3.layout.cloud',
            'Base64': M.cfg.wwwroot + '/report/forumkeywords/js/Base64',
        },
        shim: {
            'd3': {exports: 'd3'},
            'cloud': {exports: 'cloud'},
            'Base64': {exports: 'Base64'},
        }
    });
});