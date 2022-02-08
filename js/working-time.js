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
                var data = response.result,
                    duration = data['end'] - data['begin'] + $curRow.data('duration'),   //$curRow.data('duration') + response.result['duration'],
                    //last = response.result['last'],
                    total = secondsToStrTime(duration);
                if ($newRow.is('tr')) {
                    $newRow.addClass('active');
                    $('#stop-actions').removeClass('not-visible');
                } else {
                    $('#stop-actions').addClass('not-visible');
                }
                $curRow.data('duration', duration);
                $curRow.find('._total').text(total);
                    //leadingZeros(total.h,2) + ':' + leadingZeros(total.m, 2) + ':' + leadingZeros(total.s, 2)
                //);
                $curRow.find('._last').text(secondsToStrTime(data['begin']) + ' - ' + secondsToStrTime(data['end']));
                $newRow.find('._last').text(secondsToStrTime(data['end']));
            }
        }
        //================================================
        var $newRow = $(this).closest('tr'),
            $curRow = $logData.find('.active'),
            curId = $curRow.data('id'),
            newId;
        if (!$newRow.hasClass('active')){
            newId = $newRow.data('id');
            $logData.find('tr').removeClass('active');
            execRemoteFun('changeAction', [curId, newId], 1,'Смена действия', onSuccess);
        }
    }

    function addLogRow(rowData) {
        var total = secondsToStrTime(rowData['sum']),  //secondsToTime(rowData['sum']),
        $ptr = $('tbody._ptr tr').clone();
        $ptr.data('id', rowData['id'])
            .data('duration', rowData['sum'])
            .data('max_time', rowData['max_time']);
        $ptr.find('._name').text(rowData['name']);
        $ptr.find('._total').text(total);
            //total
            //leadingZeros(total.h,2) + ':' + leadingZeros(total.m, 2) + ':' + leadingZeros(total.s, 2)
        //);
        $ptr.appendTo($logData);
    }


    /**
     * Загрузка сгрупированного лога
     */
    function loadActionsGroup(begin, end) {
        function onSuccess(response) {
            if (!response.error){
                var data = response.result,
                    $ptr, total;
                $logData.empty();
                for (var i = 0; i < data.length; i++){
                    addLogRow(data[i]);
                    //var rowData = data[i];
/*
                    total = secondsToTime(rowData['sum']);
                    $ptr = $('tbody._ptr tr').clone();
                    $ptr.data('id', rowData[i].id)
                        .data('duration', rowData['sum'])
                        .data('max_time', rowData['max_time']);
                    $ptr.find('._name').text(rowData['name']);
                    $ptr.find('._total').text(
                        leadingZeros(total.h,2) + ':' + leadingZeros(total.m, 2) + ':' + leadingZeros(total.s, 2)
                    );
                    $ptr.appendTo($logData);
*/
                }
            }
        }
        var uBegin = dateToStr(begin),
            uEnd = dateToStr(end);
        execRemoteFun('actionGroupLog', [uBegin, uEnd], 1,'Загрузка лога', onSuccess);
    }

    /*
    function loadActionLog(begDate, endDate) {
        function onSuccess(response) {
            console.log(response)
        }

        execRemoteFun('actionList', [begDate, endDate], 1,'Загрузка данных', onSuccess);
    }
*/


    /**
     * Добавить дйствие
     */
    function addAction() {

        /**
         * Отправка запроса на добавление
         */
        function insertAction() {
            function onSuccess(response) {
                if (!response.error) {
                    var rowData = {
                        name: data[0],
                        id: response.result,
                        sum: 0,
                        max_time: data[1]
                    };
                    addLogRow(rowData);
                    $('#action-box').dialog('close');
                }
            }
            var data = [$(this).find('[name=action]').val(), $(this).find('[name=limit]').val()];
            execRemoteFun('actionAdd', data, 1, 'Отправка данных', onSuccess);
        }
        //====================================
        $('#action-box').dialog({
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

    /**
     *  Удалить акцию
     */
    function delAction() {
        function onSuccess(response) {
            if (!response.error){
                $row.remove();
            }
        }
        if (confirm('Удалить действие ?')) {
            var $row = $(this).closest('tr');
            execRemoteFun('actionDel', [$row.data('id')], 1, 'Отправка данных', onSuccess);
        }
    }

    /**
     * Подробности по акции
     */
    function showActionDetail() {
        function onSuccess(response) {
            console.log(response);
            if (!response.error){
                var $detailBox = $('#detail-box'),
                    $details = $('#detail-data').empty(),
                    data = response.result,
                    duration;
                for (var i = 0; i < data.length; i++){
                    duration = data[i].stop_work - data[i].begin_work;
                    $('<tr><td>' + secondsToStrTime(data[i].begin_work) + '</td><td>' + secondsToStrTime(data[i].stop_work) + '</td><td>' + secondsToStrTime(duration) + '</td>')
                        .appendTo($details);
                }
                $detailBox.dialog({
                    autoOpen: true,
                    title: 'Подробности по ' + $row.find('._name').text(),
                    dialogClass: 'shadow',
                    closeText: '',
                    modal: true,
                    width: 320,
                    maxHeight: 700,
                    buttons: [
                        {
                            text: "Закрыть",
                            click: function () {
                                $(this).dialog("close");
                            }
                        }
                    ]
                });

            }
        }
        var $row = $(this).closest('tr');
        execRemoteFun('actionGetDetail', [$row.data('id')], 1, 'Отправка данных', onSuccess);
    }

    //======================================================
    $(document).ready(function () {
        debugPar = location.search;
        $logData = $('#log-data');
        $("#tabs").tabs();
        $('#add-action').on('click', addAction);
        $('#stop-actions').on('click', changeAction);
        $logData.on('click', '._delete', delAction);
        $('#reload').on('click', function () {
            loadActionsGroup(new Date(), null);
        });
        $logData.on('click', '._name', changeAction);
        $logData.on('click', '._total', showActionDetail);
        $('#error-box').on('click', function () {
            $(this).empty();
        });
        loadActionsGroup(new Date(), null);

        //loadActionLog('0', '1');
    })
})();