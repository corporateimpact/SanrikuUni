<?php
ini_set("max_execution_time", 180);
// date_default_timezone_set('Asia/Tokyo');

session_start();
if (!isset($_SESSION['USER'])) {
  header('Location: http://160.16.239.88/index.php');
  exit;
}

$dateStr = date("Ymd");
$timeStr = date("Hi00");
$org_date = date("Y/m/d");
if (isset($_POST['date'])) {
  if ($_POST['date'] != "") {
    $dateStr = str_replace("/", "", $_POST['date']);
    $org_date = $_POST['date'];
    $timeStr = "000000";
  }
}
if (isset($_GET['date'])) {
  if ($_GET['date'] != "") {
    $dateStr = str_replace("/", "", $_GET['date']);
    $org_date = $_GET['date'];
    $timeStr = "000000";
  }
}
if (isset($_POST['time'])) {
  if ($_POST['time'] != "") {
    $timeStr = str_replace(":", "", $_POST['time']);
  }
}
if (isset($_GET['time'])) {
  if ($_GET['time'] != "") {
    $timeStr = str_replace(":", "", $_GET['time']);
  }
}

$org_date2 = $dateStr;
if (isset($_POST['date_from'])) {
  if ($_POST['date_from'] != "") {
    $dateStr = str_replace("/", "", $_POST['date_from']);
    $org_date2 = $_POST['date_from'];
    $timeStr = "000000";
  }
}

if (isset($_GET['date_from'])) {
  if ($_GET['date_from'] != "") {
    $dateStr = str_replace("/", "", $_GET['date_from']);
    $org_date2 = $_GET['date_from'];
    $timeStr = "000000";
  }
}

$org_date3 = $dateStr;
if (isset($_POST['date_to'])) {
  if ($_POST['date_to'] != "") {
    $dateStr = str_replace("/", "", $_POST['date_to']);
    $org_date3 = $_POST['date_to'];
    $timeStr = "000000";
  }
}
if (isset($_GET['date_to'])) {
  if ($_GET['date_to'] != "") {
    $dateStr = str_replace("/", "", $_GET['date_to']);
    $org_date3 = $_GET['date_to'];
    $timeStr = "000000";
  }
}

$dArray;

$max =  array_fill(1, 10, -999);
$min =  array_fill(1, 10, 999);
$data = array();
for ($i = 0; $i < 1440; $i++) {
  $h = str_pad(floor($i / 60), 2, 0, STR_PAD_LEFT);
  $m = str_pad(floor($i % 60), 2, 0, STR_PAD_LEFT);
  if ($m % 10 == 0) {
    if ($m == "00") {
      $label .= "'" . $h . "時',";
    } else {
      $label .= "'',";
    }
    if (isset($dArray{
      $h . $m . "00"})) {
      for ($j = 1; $j < 10; $j++) {
        if (isset($dArray{
          $h . $m . "00"}[$j]) && $dArray{
          $h . $m . "00"}[$j] != "") {
          $data[$j] .= "'" . $dArray{
            $h . $m . "00"}[$j] . "',";
          if ($max[$j] < $dArray{
            $h . $m . "00"}[$j]) {
            $max[$j] = ceil($dArray{
              $h . $m . "00"}[$j]);
          }
          if ($min[$j] > $dArray{
            $h . $m . "00"}[$j]) {
            $min[$j] = floor($dArray{
              $h . $m . "00"}[$j]);
          }
        } else {
          $data[$j] .= ",";
        }
      }
    } else {
      for ($j = 1; $j < 10; $j++) {
        $data[$j] .= ",";
      }
    }
  }
}

// MySQLより該当日の測定値(平均)を取得（グラフ表示で使用）
$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'ksfoods');
$sql = "select substring(date_format(time,'%H:%i'),1,4) AS JIKAN,round(AVG(water_temp),2) as water_temp,round(AVG(salinity),2) as salinity,round(AVG(do),2) as do from ksfoods.data where day = '";
$sql = $sql . str_replace("/", "-", $org_date);
$sql = $sql . "' group by substring(date_format(time,'%H:%i'),1,4) order by JIKAN";
$res = $mysqli->query($sql);
$water_temp = "";   //水温
$salinity = "";     //塩分濃度
$do = "";           //溶存酸素濃度

$i_next = 0;
$j_next = 0;
while ($row = $res->fetch_array()) {
  for ($i = $i_next; $i < 25; $i++) {
    for ($j = $j_next; $j < 6; $j++) {
      if (substr($row[0], 0, 2) == $i and substr($row[0], 3, 1) == $j) {
        $water_temp = $water_temp . $row[1] . ",";
        $salinity = $salinity . $row[2] . ",";
        $do = $do . $row[3] . ",";
        if ($j == 3) {
          $j_next = 0;
          $i_next = $i + 1;
        } else {
          $j_next = $j + 1;
          $i_next = $i;
        }
        break 2;
      } elseif (substr($row[0], 0, 2) > $i) {
        $water_temp = $water_temp . ",";
        $salinity = $salinity . ",";
        $do = $do . ",";
        if ($j == 3) {
          $j_next = 0;
        }
      } elseif (substr($row[0], 0, 2) >= $i and substr($row[0], 3, 1) > $j) {
        $water_temp = $water_temp . ",";
        $salinity = $salinity . ",";
        $do = $do . ",";
        if ($j == 3) {
          $j_next = 0;
        }
      }
    }
  }
}

//MySQLより最新の測定値情報を取得
$sql = "select * from ksfoods.data order by day desc,time desc limit 1";
$res = $mysqli->query($sql);
$row = $res->fetch_array();


// ここで取得した値はグラフ上側の現在値の表示に利用します
$fact_id = $row[0];
$tank_no = $row[1];
$day_now = $row[2];
$time_now = $row[3];
$water_temp_now = $row[4];
$salinity_now = $row[5];
$do_now = $row[6];

$mysqli->close();


?>
<!DOCTYPE html>
<html>

<head>
  <meta http-equiv="Refresh" content="60">
  <title>グラフ</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="css/jquery-ui.min.css" />

  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/chart.js"></script>

  <script src="js/jquery.ui.core.min.js"></script>
  <script src="js/jquery.ui.datepicker.min.js"></script>
  <script src="js/jquery.ui.datepicker-ja.min.js"></script>
  <!--単体フォーム用-->
  <script type="text/javascript">
    $(function() {
      $("#xxdate").datepicker({
        changeYear: true, // 年選択をプルダウン化
        changeMonth: true // 月選択をプルダウン化
      });

      $("#xxdate2").datepicker({
        changeYear: true, // 年選択をプルダウン化
        changeMonth: true // 月選択をプルダウン化
      });

      $("#xxdate3").datepicker({
        changeYear: true, // 年選択をプルダウン化
        changeMonth: true // 月選択をプルダウン化
      });

      // 日本語化
      $.datepicker.regional['ja'] = {
        closeText: '閉じる',
        prevText: '<前',
        nextText: '次>',
        currentText: '今日',
        monthNames: ['1月', '2月', '3月', '4月', '5月', '6月',
          '7月', '8月', '9月', '10月', '11月', '12月'
        ],
        monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月',
          '7月', '8月', '9月', '10月', '11月', '12月'
        ],
        dayNames: ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
        dayNamesShort: ['日', '月', '火', '水', '木', '金', '土'],
        dayNamesMin: ['日', '月', '火', '水', '木', '金', '土'],
        weekHeader: '週',
        dateFormat: 'yy/mm/dd',
        firstDay: 0,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: '年'
      };
      $.datepicker.setDefaults($.datepicker.regional['ja']);
    });

    /**
     * メイン画面へ遷移する処理
     */
    function goMovie() {
      aForm.action = "main.php";
      aForm.submit();
    }

    /**
     * グラフ画面に遷移する処理
     */
    function onGraph() {
      aForm.action = "graph.php";
      aForm.submit();
    }

    /**
     * CSVダウンロード処理
     */
    function onDownload() {
      aForm.action = "csvdownload.php";
      aForm.submit();
    }

    /**
     * 養殖日誌画面に遷移する処理
     */
    function onList() {
      aForm.action = "list.php";
      aForm.submit();
    }
  </script>
  <style>
    /* 年プルダウンの変更 */
    select.ui-datepicker-year {
      height: 2em !important;
      /* 高さ調整 */
      margin-right: 5px !important;
      /* 「年」との余白設定 */
      width: 70px !important;
      /* 幅調整 */
    }

    /* 月プルダウンの変更 */
    select.ui-datepicker-month {
      height: 2em !important;
      /* 高さ調整 */
      margin-left: 5px !important;
      /* 「年」との余白設定 */
      width: 70px !important;
      /* 幅調整 */
    }
  </style>

</head>

<body>
  <form action="main.php" method="post" name="aForm">
    <input type="text" name="date" id="xxdate" readonly="readonly" value="<?php echo $org_date; ?>">
    <input type="button" value="　撮影画像　" onClick="goMovie();">
    <input type="button" value="　グラフ　" onClick="onGraph();">
    <!-- <input type="button" value="　養殖日誌　" onClick="onList();"> -->
    <hr>
    <input type="button" value="グラフデータダウンロード" onclick="onDownload();"> <input type="text" name="date_from" id="xxdate2" readonly="readonly" value="<?php echo $org_date; ?>"> ～ <input type="text" name="date_to" id="xxdate3" readonly="readonly" value="<?php echo $org_date; ?>">
  </form>

  <?php echo $org_date; ?>

  <style type="text/css">
    span.abc {
      display: inline-block;
    }
  </style>

  <strong>
    <font color="white" size="5">
      <div align="center">
        <span class="abc" style="background-color:#000000"><?php echo $day_now . " " . substr($time, 0, 5) . " 時点"; ?></span>
        <span class="abc" style="background-color:#000000">気温：<?php echo $water_temp_now . "℃"; ?></span>
        <span class="abc" style="background-color:#000000">水温：<?php echo $water_temp_now . "℃"; ?></span>
        <span class="abc" style="background-color:#000000">塩分濃度：<?php echo $salinity_now . "％"; ?></span>
        <span class="abc" style="background-color:#000000">溶存酸素濃度：<?php echo $do_now . ""; ?></span>
      </div>

    </font>
  </strong>

  <canvas id="myChart1"></canvas>
  <canvas id="myChart2"></canvas>


</body>

</html>
<script>
  var complexChartOption1 = {    //上側グラフの設定
    responsive: false,
    maintainAspectRatio: false,
    scales: {
      xAxes: [ // 　Ｘ軸設定
        {
          display: true,
          barPercentage: 1,
          //categoryPercentage: 1.8,
          gridLines: {
            display: false
          },
        }
      ],
      yAxes: [{
        id: "y-axis-1",
        type: "linear",
        position: "left",
        scaleLabel: {
          display: true,
          labelString: "気温・水温（℃）"
        },
        ticks: {
          max: 50,
          min: -10,
          stepSize: 10
        },
        gridLines: {
          drawOnChartArea: true,
        }
      }],
    }
  };
  var complexChartOption2 = {    //下側グラフの設定
    responsive: false,
    maintainAspectRatio: false,
    scales: {
      xAxes: [ // Ｘ軸設定
        {
          display: true,
          barPercentage: 0.9,
          //categoryPercentage: 1.8,
          gridLines: {
            display: false
          },
        }
      ],
      yAxes: [{
        id: "y-axis-1",
        type: "linear",
        position: "left",
        scaleLabel: {
          display: true,
          labelString: "塩分濃度（％）"
        },
        ticks: {
          max: 5,
          min: 0,
          stepSize: 1
        },
      }, {
        id: "y-axis-2",
        type: "linear",
        position: "right",
        scaleLabel: {
          display: true,
          labelString: "溶存酸素濃度"
        },
        ticks: {
          max: 15.0,
          min: 0.0,
          stepSize: 1.0
        },
        gridLines: {
          drawOnChartArea: false,
        }
      }],
    }
  };
</script>

<script>
  var ctx = document.getElementById("myChart1").getContext("2d");
  ctx.canvas.width = window.innerWidth - 69;
  ctx.canvas.height = 350;
  var myChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: [<?php echo $label; ?>],
      datasets: [{
          type: "line",
          label: "水温(℃)",
          data: [<?php echo $water_temp; ?>],
          borderColor: "rgba(0, 255, 255,0.4)",
          backgroundColor: "rgba(0, 255, 255,0.4)",
          fill: false, // 中の色を抜く　
          yAxisID: "y-axis-1",
        },
        {
          type: "line",
          label: "気温(℃)",
          data: [<?php echo $water_temp; ?>],
          borderColor: "rgba(255,255,0,0.4)",
          backgroundColor: "rgba(255,255,0,0.4)",
          fill: false, // 中の色を抜く
          yAxisID: "y-axis-1",
        }
      ]
    },
    options: complexChartOption1
  });

  var ctx = document.getElementById("myChart2").getContext("2d");
  ctx.canvas.width = window.innerWidth - 20;
  ctx.canvas.height = 350;
  var myChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: [<?php echo $label; ?>],
      datasets: [{
          type: "line",
          label: "塩分濃度(%)",
          data: [<?php echo $salinity; ?>],
          borderColor: "rgba(0, 255, 0,0.4)",
          backgroundColor: "rgba(0, 255, 0,0.4)",
          fill: false, // 中の色を抜く
          yAxisID: "y-axis-1",
        },
        {
          type: "bar",
          label: "溶存酸素濃度",
          data: [<?php echo $do; ?>],
          borderColor: "rgba(128,128,0,0.4)",
          backgroundColor: "rgba(128,128,0,0.4)",
          fill: false, // 中の色を抜く
          yAxisID: "y-axis-2",
        }
      ]
    },
    options: complexChartOption2
  });
</script>