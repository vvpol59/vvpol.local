"use strict";

(function () {
    var $logData, debugPar;



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
     * Смена действия
     */
    function changeAction(){
        function onSuccess(response){
            console.log(response);
            if (!response.error){
                var duration = $curRow.data('duration') + response.result['duration'],
                    last = response.result['last'],
                    total = secondsToTime(duration);
                if ($newRow.is('tr')) {
                    $newRow.addClass('active');
                    $('#stop-actions').removeClass('not-visible');
                } else {
                    $('#stop-actions').addClass('not-visible');
                }
                $curRow.data('duration', duration);
                $curRow.find('._total').text(
                    leadingZeros(total.h,2) + ':' + leadingZeros(total.m, 2) + ':' + leadingZeros(total.s, 2)
                );
                $curRow.find('._last').text(datetimeToStr(last));
            }
        }
        //================================================
        var $newRow = $(this),
            $curRow = $logData.find('.active'),
            curId = $curRow.data('id'),
            newId;
        if (!$newRow.hasClass('active')){
            newId = $newRow.data('id');
            $logData.find('tr').removeClass('active');
            execRemoteFun('changeAction', [curId, newId], 1,'Смена действия', onSuccess);
        }
    }

    /**
     * Загрузка сгрупированного лога
     */
    function loadActionsGroup(begin, end) {
        function onSuccess(response) {
            console.log(response);
            if (!response.error){
                var data = response.result,
                    //$actionList = $('#data-rows').empty(),
                    $logData = $('#log-data').empty(),
                    $ptr, total;
                for (var i = 0; i < data.length; i++){
                    total = secondsToTime(data[i]['sum']);
                    $ptr = $('tbody._ptr tr').clone();
                    $ptr.data('id', data[i].id)
                        .data('duration', data[i]['sum'])
                        .data('max_time', data[i]['max_time']);
                    $ptr.find('._name').text(data[i].name);
                    $ptr.find('._total').text(
                        leadingZeros(total.h,2) + ':' + leadingZeros(total.m, 2) + ':' + leadingZeros(total.s, 2)
                    );
                    $ptr.appendTo($logData);
                    //-----------
                    //$ptr = $('#tabs-1').find('._ptr tr').clone();
                    //$ptr.data('id', data[i].id);
                    //$ptr.find('._name').text(data[i].name);
                    //$ptr.appendTo($logData);
                }
            }
            //alert(data);
            //console.log(data);
        }
        var uBegin = dateToStr(begin),
            uEnd = dateToStr(end);
        execRemoteFun('actionGroupLog', [uBegin, uEnd], 1,'Загрузка лога', onSuccess);
    }

    function loadActionLog(begDate, endDate) {
        function onSuccess(response) {
            console.log(response)
        }

        execRemoteFun('actionList', [begDate, endDate], 1,'Загрузка данных', onSuccess);
    }



    /**
     * Добавить дйствие
     */
    function addAction() {
        function onSuccess(response) {
            if (!response.error) {




            }
        }





        /**
         * Отправка запроса на добавление
         */
        function insertAction() {
            var data = [$(this).find('[name=action]').val(), $(this).find('[name=limit]').val()];
            execRemoteFun('actionAdd', data, 'Отправка данных', onSuccess);
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

    //======================================================
    $(document).ready(function () {
        debugPar = location.search;
        $logData = $('#log-data');
        $("#tabs").tabs();
        $('#add-action').on('click', addAction);
        $('#stop-actions').on('click', changeAction);
        $('#reload').on('click', function () {
            loadActionsGroup(new Date(), null);
        });
        $logData.on('click', 'tr', changeAction);
        $('#error-box').on('click', function () {
            $(this).empty();
        });
        loadActionsGroup(new Date(), null);

        //loadActionLog('0', '1');
    })
})();