<?php
echo '1111111111111';
echo '2222222222222222';



?>
<!DOCTYPE html>
<html>
<header>
    <meta charset="UTF-8">
    <title>Лог действий</title>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/jquery-ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="/js/jquery-ui-1.13.0.css">

    <script src="/js/working-time.js"></script>
    <link rel="stylesheet" type="text/css" href="/js/working-time.css">
</header>
<body>
<div class="content">
    <div id="tabs">
        <ul>
            <li><a href="#tabs-1">Main</a></li>
            <li><a href="#tabs-2">Dict</a></li>
            <li><a href="#tabs-3">Reports</a></li>
        </ul>
        <div id="tabs-1">
            <div>
                <div>Текущее:
                    <div>Перекур</div>
                    Время:
                    <div>0:01:22</div>
                </div>
            </div>
            <table>
                <tbody>
                <tr>
                    <th>Действие</th>
                    <th>За тек. сутки</th>
                    <th>Последнее</th>
                </tr>
                </tbody>
                <tbody class="_ptr" style="display: none">
                <tr>
                    <td class="_name"></td>
                    <td class="_total">0</td>
                    <td class="_last"></td>
                </tr>
                </tbody>
                <tbody id="log-data" class="_data">
                <tr>
                    <td>Перекур</td>
                    <td>1:20:32</td>
                    <td>15:30</td>
                </tr>
                </tbody>
            </table>

        </div>
        <div id="tabs-2">
            <table>
                <tbody>
                <tr>
                    <th>Акт.</th>
                    <th>Действие</th>
                    <th>Лимит(мин)</th>
                    <th id="add-action">+</th>
                </tr>
                <tr id="data-ptr" style="display: none">
                    <td class="_act"></td>
                    <td class="_name"></td>
                    <td class="_limit"></td>
                    <td></td>
                </tr>
                </tbody>
                <tbody id="data-rows"></tbody>
            </table>
        </div>
        <div id="tabs-3">
            <p>Mauris eleifend est et turpis. Duis id erat. Suspendisse potenti. Aliquam vulputate, pede vel vehicula
                accumsan, mi neque rutrum erat, eu congue orci lorem eget lorem. Vestibulum non ante. Class aptent
                taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Fusce sodales. Quisque eu
                urna vel enim commodo pellentesque. Praesent eu risus hendrerit ligula tempus pretium. Curabitur lorem
                enim, pretium nec, feugiat nec, luctus a, lacus.</p>

            <p>Duis cursus. Maecenas ligula eros, blandit nec, pharetra at, semper at, magna. Nullam ac lacus. Nulla
                facilisi. Praesent viverra justo vitae neque. Praesent blandit adipiscing velit. Suspendisse potenti.
                Donec mattis, pede vel pharetra blandit, magna ligula faucibus eros, id euismod lacus dolor eget odio.
                Nam scelerisque. Donec non libero sed nulla mattis commodo. Ut sagittis. Donec nisi lectus, feugiat
                porttitor, tempor ac, tempor vitae, pede. Aenean vehicula velit eu tellus interdum rutrum. Maecenas
                commodo. Pellentesque nec elit. Fusce in lacus. Vivamus a libero vitae lectus hendrerit hendrerit.</p>
        </div>
    </div>

</div>

<div id="forms-box" style="display: none">
    <div id="action-box">
        <table>
            <tr>
                <td>Акт.</td><td><input type="checkbox" name="activity"></td>
            </tr>
            <tr>
                <td>Действие:</td><td><input type="text" name="action"></td>
            </tr>
            <tr>
                <td>Лимит(мин):</td><td><input type="number" name="limit"></td>
            </tr>
        </table>
    </div>

</div>


</body>
</html>