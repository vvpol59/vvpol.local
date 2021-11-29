"use strict";

(function () {

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
                    alert(data.errorMessage);
                }
            },
            complete: function(data) {
                var obj = $.parseJSON(data.responseText);  // todo Правильно обработать ответ не в JSON
                showWaitOverlay('');
                if (obj.error){
                    alert(obj.error.message);
                } else {
                    if (success) {
                        success(obj);
                    }
                }
            }
        });

    }


    /**
     *
     */
    function loadLog() {

    }

    /**
     *
     */
    function killAction() {

    }

    /**
     *
     */
    function editAction() {

    }

    /**
     *
     */
    function changeAction(){

    }

    /**
     * Загрузка справочника действий
     */
    function loadActions() {
        function onSuccess(response) {
            if (!response.error){
                var data = response.result,
                    $actionList = $('#data-rows').empty(), $ptr;
                for (var i = 0; i < data.length; i++){
                    $ptr = $('#data-ptr').css('display', '')
                        .clone()
                        .removeAttr('id');
                    $ptr.data('id', data[i].id);
                    $ptr.find('._name').text(data[i].name);
                    $ptr.find('._limit').text(data[i]['max_time']);
                    $ptr.appendTo($actionList);
                }
            }
            //alert(data);
            //console.log(data);
        }

        execRemoteFun('actionList', [], 1,'Загрузка справочников', onSuccess);

        /*
        var data = JSON.stringify({
            jsonrpc: "2.0",
            method: 'actionList',
            params: [],
            id : 1
        });



        sendAjax({
            method: 'POST',
            url: window.location.protocol + '//' + window.location.host + '/api.php',
            waitMsg: 'Выборка данных',
            dataType: 'json',
            data: data,
            contentType: 'application/json',
            process: true
        }, onSuccessSend);
        */
    }


    /**
     *
     */
    function addAction() {
        function onSuccess(response) {
            alert(response);
            console.log(response);

        }

        function insertAction() {
            execRemoteFun('actionAdd', 1, 'Отправка данных', onSuccess);

            /*
            var data = JSON.stringify({
                jsonrpc: "2.0",
                method: 'actionAdd',
                //params: [1,2],
                params: [$dialog.find('[name=action]').val(), $dialog.find('[name=limit]').val()],
                id : 1
            });


            sendAjax({
                method: 'POST',
                url: window.location.protocol + '//' + window.location.host + '/api.php',
                waitMsg: 'Сохранение данных',
                //dataType: 'json',
                data: data,
                //contentType: 'application/json',
                process: true
            }, onSuccessSend);
            */
        }

        var $dialog = $('#action-box');

        $dialog.dialog({
            autoOpen: true,
            title: 'Добавить действие',
            dialogClass: 'shadow',
            closeText: '',
            modal: true,
            width: 500,
            maxHeight: 700,
            buttons: [
                {
                    text: "Закрыть",
                    click: function () {
                        $(this).dialog("close");
                    }
                },
                {
                    text: "Сохранить",
                    click: insertAction
                }
            ]
        });
    }


    $(document).ready(function () {
        $("#tabs").tabs();
        $('#add-action').on('click', addAction);
        loadActions();
    })
})();