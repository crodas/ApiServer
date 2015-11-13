var Server = (function API_Client() {
    var url = undefined;
    var ns = {};
    var wait;
    var queue = [];

    function doExecute() {
        var currentQueue = queue;
        var reqBody = currentQueue.map(function(stmt) {
            return [stmt[0], stmt[1]];
        });
        queue = [];

        var xhr = new XMLHttpRequest();
        xhr.onerror = function() {

        };
        xhr.onload = function() {
            var response = JSON.parse(this.response || this.responseText);
            if (typeof response === "number") {
                // general error
                // TODO: retry or give up
                return;
            }
            for (var i = 0; i < response.length; ++i) {
                if (typeof response[i] === "object" && response[i].error) {
                    currentQueue[i][3](response[i]);
                } else {
                    currentQueue[i][2](response[i]);
                }
            }
        };

        var _url = url;

        if (localStorage.sessionId) {
            _url += "?s=" + localStorage.sessionId;
        }

        xhr.open("POST", _url, true);
        xhr.send(JSON.stringify(reqBody));
    }


    ns.setUrl = function(_url) {
        url = _url;
        return this;
    };


    ns.exec = function(method, args, callback) {
        var promise = new Promise(function(resolve, reject) {
            queue.push([method, args, resolve, reject]);
        });

        if (typeof url === "undefined") {
            throw new Error("Please set the server (server.url(<url>)");
        }

        if (typeof callback === "function") {
            promise.then(callback);
        }

        clearTimeout(wait);
        wait = setTimeout(doExecute, 50);

        return promise;
    };

    return ns;
})();
