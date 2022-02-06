/**
 *
 * @param params
 * @param successCallback
 * @param errorCallback
 */
function sendAjax(params, successCallback, errorCallback) {
    var waitMsg = params.waitMsg ? params.waitMsg : 'Отправка данных';
    if (waitMsg !== '-') {
        showWaitOverlay(waitMsg, true);
    }
    try {
        var ajaxParams = {
            url: params.url,
            type: params.method,
            data: params.data,
            processData: (params.process === undefined) ? true : params.process,
            xhr: function () {
                return $.ajaxSettings.xhr();
            },
            error: function (response) {
                showWaitOverlay('', false);
                if (typeof errorCallback === 'function') {
                    errorCallback(response);
                } else {
                    jqAlert('Сообщение об ошибке', response.statusText + '(' + response.status + ')', true);
                    //alert('Произошла ошибка: ' + response.statusText + '(' + response.status + ')');
                }
            },
            success: function (response) {
                showWaitOverlay('', false);
                if (response.error) {
                    jqAlert('Сообщение об ошибке', response.error, true);
                }
                if (response['user-msg']) {
                    response.error = true;
                    jqAlert('Сообщение об ошибке', response['user-msg'], false);
                }
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            }
        };
        if (params.contentType !== undefined) {
            ajaxParams['contentType'] = params.contentType;
        }
        $.ajax(ajaxParams);

    } catch (e) {
        showWaitOverlay('', false);
        alert(e.message);
    }
}

/**
 *
 * @param text
 */
function showWaitOverlay(text) {
    var $overlay = $('.wait-overlay');
    if (text) {
        $overlay.find('.wait-overlay-text').text(text);
        $overlay.css({display: 'flex'});
    } else {
        $overlay.css({display: 'none'});
    }
}

/**
 * Вывод формы с сообщением
 * @param title
 * @param text
 * @param asError
 * @param onClose
 */
function jqAlert(title, text, asError, onClose) {
    var $dialog = $('#jq-alert');
    $dialog.find('.error-msg-label').toggle(asError);
    $dialog.find('.detail-text').text(text);
    $dialog.dialog({
        autoOpen: true,
        title: title,
        dialogClass: 'shadow',
        closeText: '',
        modal: true,
        width: 500,
        close: function(){
            if (onClose){
                onClose();
            }
        },
        buttons: [{
            text: "Закрыть",
            click: function () {
                $(this).dialog("close");
            }
        }]
    });
}


function secondsToTime(inputSeconds) {
    var days = parseInt(inputSeconds / 86400),
        hourSeconds = inputSeconds % 86400,
        hours = parseInt(hourSeconds / 3600),
        minuteSeconds = hourSeconds % 3600,
        minutes = parseInt(minuteSeconds / 60),
        remainingSeconds = minuteSeconds % 60,
        seconds = parseInt(remainingSeconds),
    obj = {
        d: parseInt(days),
        h: parseInt(hours),
        m: parseInt(minutes),
        s: parseInt(seconds)
    };

    return obj;
}


/**
 * Выполнение серверной функции
 * @param fun
 * @param params
 * @param id
 * @param msg
 * @param success
 * @param fail
 */
function execRemoteFun(fun, params, id, msg, success, fail) {
    var _this = this;
    showWaitOverlay(msg);

    var data = JSON.stringify({
        "jsonrpc": "2.0",
        "method": fun,
        "params": params,
        "id" : id
    });

    $.ajax({
        url: window.location.protocol + '//' + window.location.host + '/api.php',
        type: "POST",
        contentType: 'application/json',
        async: false,
        data: data,
        error: function(data){
            showWaitOverlay('');
            if (fail) {
                fail(data);
            } else {
                alert(data.statusText);
            }
        },
        complete: function(data) {
            var content = data.responseText,
                obj;
            if (content[0] === '{'){
                obj = $.parseJSON(content);
            } else {
                $('#error-box').html(content);
                return;
            }
            showWaitOverlay('');
            if (obj['error']){
                alert(obj.error);
            } else {
                if (success) {
                    success(obj);
                }
            }
        }
    });
}

function leadingZeros(val, len) {
    return ('00000' + val).slice(len * -1);
}

function dateToStr(date) {
    if (!date) return date;
    var _date = '00' + date.getDate(),
        month = '00' + (date.getMonth() + 1);
    return [_date.slice(-2), month.slice(-2), date.getFullYear()].join('-');
}

/**
 *
 * @param time   unix timestamp
 * @returns {*}
 */
function datetimeToStr(time) {
    if (!time) return time;
    var date = new Date(time * 1000);
    return leadingZeros(date.getDate(), 2) + '-' + leadingZeros(date.getMonth() + 1, 2) + '-' +
        date.getFullYear() + ' ' + leadingZeros(date.getHours(), 2) + ':' + leadingZeros(date.getMinutes(), 2) +
        ':' + leadingZeros(date.getSeconds(), 2);
}